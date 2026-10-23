<?php
/**
 * REST API of the free security check (artws-sec/v1).
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Rest_Controller
 *
 * POST /scan registers a site or checks its site token, builds the report and
 * saves it; GET /scan/{token} returns a saved report; DELETE /site deletes a
 * site's data. The routes are public: each request is authenticated by the
 * site token in "Authorization: Bearer", as tests/fixtures/security-feed/README.md
 * describes, not by a WordPress user.
 */
class Artws_Sec_Rest_Controller {

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'artws-sec/v1';

	/**
	 * Largest request body, in bytes.
	 */
	const MAX_BODY_BYTES = 1048576;

	/**
	 * Scans a site may run per window.
	 */
	const SCANS_PER_SITE = 1;

	/**
	 * Scans per window from one client address (many shops on a shared server share one).
	 */
	const SCANS_PER_ADDRESS = 20;

	/**
	 * Registers the routes.
	 */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers the routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/scan',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_scan' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/scan/(?P<token>[A-Za-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_scan' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/site',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'delete_site' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * POST /scan: runs the free security check.
	 *
	 * Nothing is registered and no rate limit is used up until the request is
	 * valid, authenticated and its report built, so that a refused request
	 * costs the site nothing. A re-registration needs no token (the contract
	 * lets a site that lost its token ask for one), so anyone who knows a
	 * site's URL can re-register it: that uses up its hourly scan and deletes
	 * its scans, but never hands out a report it made before.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create_scan( WP_REST_Request $request ) {
		$now = time();

		if ( ! self::is_https() ) {
			return self::https_required();
		}
		if ( strlen( (string) $request->get_body() ) > self::MAX_BODY_BYTES ) {
			return self::error( 'artws_sec_request_too_large', 'The request body is too large.', 413 );
		}
		// Only the JSON body: the query string is not part of the contract.
		$data       = $request->get_json_params();
		$normalized = Artws_Sec_Scan_Request::normalize( $data );
		if ( is_wp_error( $normalized ) ) {
			return self::error( $normalized->get_error_code(), $normalized->get_error_message(), 400 );
		}

		$state = Artws_Sec_Feed_Updater::get_state();
		if ( null === $state || '' === $state['index_version'] ) {
			return self::unavailable();
		}

		$token      = self::bearer_token( $request );
		$site_key   = Artws_Sec_Site_Store::site_key( $normalized['site_url'] );
		$site       = Artws_Sec_Site_Store::find_site_by_key( $site_key );
		$reregister = Artws_Sec_Scan_Request::wants_reregister( $data );
		if ( ! $reregister ) {
			// A token for a site that is not registered: it moved, or the token came with a copy of the site.
			$authorized = null === $site ? null === $token : Artws_Sec_Site_Store::token_matches( $site, $token );
			if ( ! $authorized ) {
				return self::unauthorized();
			}
		}

		// A request over the limit is refused before the work of matching it; the hit is taken after.
		$limits   = self::limits( $site_key );
		$retry_at = Artws_Sec_Rate_Limiter::blocked_until( $limits, $now );
		if ( $retry_at > 0 ) {
			return self::rate_limited( $retry_at, $now );
		}

		$scan_token = Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SCAN_TOKEN_PREFIX );
		try {
			$report = Artws_Sec_Report_Builder::build( $normalized, $state, Artws_Sec_Feed_Updater::get_eol(), $scan_token, $now );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			return self::unavailable();
		}

		$retry_at = Artws_Sec_Rate_Limiter::hit( $limits, $now );
		if ( $retry_at > 0 ) {
			return self::rate_limited( $retry_at, $now );
		}

		$site_token = null;
		if ( null === $site ) {
			$registered = Artws_Sec_Site_Store::register( $normalized['site_url'], $now );
			if ( null === $registered ) {
				return self::server_error();
			}
			list( $site_id, $site_token ) = $registered;
		} else {
			$site_id = (int) $site->id;
			if ( $reregister ) {
				$site_token = Artws_Sec_Site_Store::reregister( $site, $normalized['site_url'], $now );
				if ( null === $site_token ) {
					return self::server_error();
				}
			}
		}

		if ( ! Artws_Sec_Site_Store::save_scan( $site_id, $normalized['site_url'], $scan_token, $normalized, $report, $now ) ) {
			return self::server_error();
		}

		$body = array( 'schema_version' => 1 );
		if ( null !== $site_token ) {
			$body['site_token'] = $site_token;
		}
		$body['report'] = $report;
		return self::response( $body, 200 );
	}

	/**
	 * GET /scan/{token}: returns a saved report.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_scan( WP_REST_Request $request ) {
		if ( ! self::is_https() ) {
			return self::https_required();
		}

		// From the route only: get_param() would let a "token" in the query string replace it.
		$scan = Artws_Sec_Site_Store::find_scan( $request->get_url_params()['token'] ?? null );
		$site = null === $scan ? null : Artws_Sec_Site_Store::find_site( (int) $scan->site_id );
		if ( null === $site ) {
			return self::error( 'artws_sec_scan_not_found', 'No such scan.', 404 );
		}
		if ( ! Artws_Sec_Site_Store::token_matches( $site, self::bearer_token( $request ) ) ) {
			return self::unauthorized();
		}

		$report = json_decode( $scan->report, true );
		if ( ! is_array( $report ) ) {
			return self::server_error();
		}
		return self::response(
			array(
				'schema_version' => 1,
				'report'         => $report,
			),
			200
		);
	}

	/**
	 * DELETE /site: deletes the site of the token and all its scans.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function delete_site( WP_REST_Request $request ) {
		if ( ! self::is_https() ) {
			return self::https_required();
		}

		// The rate limit rows stay, so that deleting and registering again does not reset the limit.
		$site = Artws_Sec_Site_Store::find_site_by_token( self::bearer_token( $request ) );
		if ( null === $site ) {
			return self::unauthorized();
		}
		if ( ! Artws_Sec_Site_Store::delete_site( (int) $site->id ) ) {
			return self::server_error();
		}
		return self::response( null, 204 );
	}

	/**
	 * Returns the length of a rate limit window, in seconds.
	 *
	 * @return int
	 */
	public static function window() {
		/**
		 * Filters the length of the rate limit window of POST /scan.
		 *
		 * @param int $seconds Default one hour.
		 */
		return max( 1, (int) apply_filters( 'artws_sec_scan_window', HOUR_IN_SECONDS ) );
	}

	/**
	 * Returns the rate limits of a scan of a site.
	 *
	 * @param string $site_key Site key.
	 * @return array<int, array{key: string, max: int, window: int}>
	 */
	private static function limits( $site_key ) {
		$window = self::window();
		return array(
			array(
				'key'    => Artws_Sec_Rate_Limiter::key( 'site', $site_key ),
				/**
				 * Filters how many scans a site may run per window.
				 *
				 * @param int $max Default 1.
				 */
				'max'    => max( 1, (int) apply_filters( 'artws_sec_scans_per_site', self::SCANS_PER_SITE ) ),
				'window' => $window,
			),
			array(
				'key'    => Artws_Sec_Rate_Limiter::key( 'ip', Artws_Sec_Rate_Limiter::client_address() ),
				/**
				 * Filters how many scans may come from one client address per window.
				 *
				 * @param int $max Default 20.
				 */
				'max'    => max( 1, (int) apply_filters( 'artws_sec_scans_per_address', self::SCANS_PER_ADDRESS ) ),
				'window' => $window,
			),
		);
	}

	/**
	 * Returns the token of "Authorization: Bearer <token>".
	 *
	 * WordPress also reads REDIRECT_HTTP_AUTHORIZATION, which Apache sets
	 * with the rewrite rule in WordPress's .htaccess.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return string|null Null without the header, '' when it is not a bearer token.
	 */
	private static function bearer_token( WP_REST_Request $request ) {
		$header = $request->get_header( 'authorization' );
		if ( ! is_string( $header ) || '' === trim( $header ) ) {
			return null;
		}
		return 1 === preg_match( '/^Bearer[ \t]+([A-Za-z0-9_-]+)[ \t]*$/iD', trim( $header ), $matches ) ? $matches[1] : '';
	}

	/**
	 * Whether the request came over HTTPS (or HTTPS is not required).
	 *
	 * @return bool
	 */
	private static function is_https() {
		/**
		 * Filters whether the REST API refuses requests that did not come over HTTPS.
		 *
		 * Behind a proxy that ends TLS, make is_ssl() true instead (HTTPS or
		 * HTTP_X_FORWARDED_PROTO in wp-config.php).
		 *
		 * @param bool $required Default true.
		 */
		return is_ssl() || ! apply_filters( 'artws_sec_require_https', true );
	}

	/**
	 * Returns a response that no cache may keep.
	 *
	 * @param array|null $body   Body, or null for none.
	 * @param int        $status HTTP status.
	 * @return WP_REST_Response
	 */
	private static function response( $body, $status ) {
		$response = new WP_REST_Response( $body, $status );
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Returns an error response (error.schema.json).
	 *
	 * @param string $code    Error code.
	 * @param string $message English message for logs.
	 * @param int    $status  HTTP status.
	 * @param array  $data    More data.
	 * @return WP_REST_Response
	 */
	private static function error( $code, $message, $status, array $data = array() ) {
		return self::response(
			array(
				'schema_version' => 1,
				'code'           => $code,
				'message'        => $message,
				'data'           => array( 'status' => $status ) + $data,
			),
			$status
		);
	}

	/**
	 * 401: the token is missing or does not match.
	 *
	 * @return WP_REST_Response
	 */
	private static function unauthorized() {
		$response = self::error( 'artws_sec_unauthorized', 'The site token is missing or does not match.', 401 );
		$response->header( 'WWW-Authenticate', 'Bearer realm="artws-sec"' );
		return $response;
	}

	/**
	 * 403: the request did not come over HTTPS.
	 *
	 * @return WP_REST_Response
	 */
	private static function https_required() {
		return self::error( 'artws_sec_https_required', 'Send the request over HTTPS.', 403 );
	}

	/**
	 * 429: the rate limit is used up.
	 *
	 * @param int $retry_at When the next request is allowed.
	 * @param int $now      Unix time.
	 * @return WP_REST_Response
	 */
	private static function rate_limited( $retry_at, $now ) {
		$at       = gmdate( 'Y-m-d\TH:i:s\Z', $retry_at );
		$response = self::error( 'artws_sec_rate_limited', sprintf( 'Too many scans. Try again after %s.', $at ), 429, array( 'retry_at' => $at ) );
		$response->header( 'Retry-After', (string) max( 1, $retry_at - $now ) );
		return $response;
	}

	/**
	 * 503: the vulnerability data is not ready, or cannot be read.
	 *
	 * @return WP_REST_Response
	 */
	private static function unavailable() {
		return self::error( 'artws_sec_unavailable', 'The vulnerability data is not available.', 503 );
	}

	/**
	 * 500: the data could not be saved or read.
	 *
	 * @return WP_REST_Response
	 */
	private static function server_error() {
		return self::error( 'artws_sec_server_error', 'The server could not save or read the scan.', 500 );
	}
}
