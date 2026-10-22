<?php
/**
 * Requests the security check sends to the site itself.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * JP4WC_Security_Probe
 *
 * Asks the site itself, with one HEAD request per check, whether a URL that
 * should be closed answers: the login page, XML-RPC, the users REST route,
 * the uploads directory and a WooCommerce log file. Each result is kept for
 * 12 hours. A request that fails gives "unknown", which costs the check no
 * points: many shared servers cannot connect to themselves.
 *
 * Before its first request a run asks for the home page. When even that is
 * refused (a firewall or CDN that challenges the server's own requests, a
 * server that refuses HEAD), a 401 or 403 says nothing about the checked URL,
 * so every result of the run is unknown.
 *
 * Requests go only to this site's own host, so nothing is sent elsewhere
 * (uploads served from another host, for example, stay unknown). One instance
 * serves one run of the checks and saves the results once, at the end.
 */
class JP4WC_Security_Probe {

	/**
	 * Option with the results by check ID: status and checked_at (not autoloaded).
	 */
	const OPTION = 'jp4wc_security_probes';

	/**
	 * How long a result is used, in seconds (12 hours).
	 */
	const TTL = 43200;

	/**
	 * Timeout of one request, in seconds.
	 */
	const TIMEOUT = 5;

	/**
	 * HTTP codes that show a URL is closed: authentication required, forbidden, not found, gone.
	 */
	const CLOSED_CODES = array( 401, 403, 404, 410 );

	/**
	 * HTTP codes of the home page that show the site's own requests are refused:
	 * authentication required, forbidden, method not allowed, too many requests.
	 * Server errors (5xx) count too.
	 */
	const REFUSED_CODES = array( 401, 403, 405, 429 );

	/**
	 * Results by check ID.
	 *
	 * @var array<string, array{status: string, checked_at: int}>
	 */
	private $results;

	/**
	 * Whether a result changed since the instance was created.
	 *
	 * @var bool
	 */
	private $changed = false;

	/**
	 * Whether a request of this run could not connect.
	 *
	 * @var bool
	 */
	private $unreachable = false;

	/**
	 * Whether the home page answered this run's first request normally; null before it was asked.
	 *
	 * @var bool|null
	 */
	private $answers = null;

	/**
	 * Reads the stored results.
	 */
	public function __construct() {
		$results       = get_option( self::OPTION, array() );
		$this->results = is_array( $results ) ? $results : array();
	}

	/**
	 * Returns the status a HEAD request gives a check, from the stored result while it is recent.
	 *
	 * Once a request of this run cannot connect, or the home page refuses it,
	 * the others are not sent: on a server that cannot reach itself each would
	 * wait for the timeout.
	 *
	 * @param string   $check_id Check ID.
	 * @param string   $url      URL on this site.
	 * @param callable $classify Gets the response array and returns pass, fail or unknown.
	 * @return string pass, fail or unknown.
	 */
	public function status( $check_id, $url, callable $classify ) {
		$stored = isset( $this->results[ $check_id ] ) && is_array( $this->results[ $check_id ] ) ? $this->results[ $check_id ] : array();
		if ( isset( $stored['status'], $stored['checked_at'] ) && self::is_status( $stored['status'] ) ) {
			$age = time() - (int) $stored['checked_at'];
			if ( $age >= 0 && $age < self::TTL ) {
				return $stored['status'];
			}
		}

		$status                     = $this->request( $url, $classify );
		$this->results[ $check_id ] = array(
			'status'     => $status,
			'checked_at' => time(),
		);
		$this->changed              = true;
		return $status;
	}

	/**
	 * Saves the results when one changed.
	 */
	public function save() {
		if ( $this->changed ) {
			update_option( self::OPTION, $this->results, false );
			$this->changed = false;
		}
	}

	/**
	 * Returns the status a response's HTTP code gives.
	 *
	 * @param array $response   Response of wp_remote_head().
	 * @param int[] $open_codes Codes that show the URL answers (fail).
	 * @return string fail for an open code, pass for a closed one, otherwise unknown
	 *                (a redirect, a server error, a code the check does not expect).
	 */
	public static function status_for_code( array $response, array $open_codes ) {
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( in_array( $code, $open_codes, true ) ) {
			return 'fail';
		}
		return in_array( $code, self::CLOSED_CODES, true ) ? 'pass' : 'unknown';
	}

	/**
	 * Sends one HEAD request and classifies its response.
	 *
	 * @param string   $url      URL.
	 * @param callable $classify Classifies the response.
	 * @return string
	 */
	private function request( $url, callable $classify ) {
		if ( ! self::is_own_url( $url ) ) {
			return 'unknown';
		}
		if ( null === $this->answers ) {
			$this->answers = $this->home_answers();
		}
		if ( ! $this->answers ) {
			return 'unknown';
		}

		$response = $this->head( $url );
		return null === $response ? 'unknown' : self::classify( $classify, $response );
	}

	/**
	 * Whether the home page answers the site's own request as it would a visitor's.
	 *
	 * @return bool
	 */
	private function home_answers() {
		$response = $this->head( home_url( '/' ) );
		if ( null === $response ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 100 && $code < 500 && ! in_array( $code, self::REFUSED_CODES, true );
	}

	/**
	 * Sends one HEAD request.
	 *
	 * @param string $url URL.
	 * @return array|null The response; null when it could not connect (the rest of the run is then not sent).
	 */
	private function head( $url ) {
		if ( $this->unreachable ) {
			return null;
		}
		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => self::TIMEOUT,
				// A redirect says nothing about the URL itself; it is reported as unknown.
				'redirection' => 0,
				// As WordPress's own requests to itself (WP-Cron, Site Health): a site may use its own certificate.
				'sslverify'   => apply_filters( 'https_local_ssl_verify', false, $url ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
				'user-agent'  => sprintf( 'JP4WC/%s (security check)', defined( 'JP4WC_VERSION' ) ? JP4WC_VERSION : '' ),
			)
		);
		// A pre_http_request filter may answer with anything.
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			$this->unreachable = true;
			return null;
		}
		return $response;
	}

	/**
	 * Classifies a response.
	 *
	 * @param callable $classify Classifier.
	 * @param array    $response Response.
	 * @return string
	 */
	private static function classify( callable $classify, array $response ) {
		$status = $classify( $response );
		return self::is_status( $status ) ? $status : 'unknown';
	}

	/**
	 * Whether a URL is on the host of this site's home or WordPress address.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private static function is_own_url( $url ) {
		$scheme = is_string( $url ) ? wp_parse_url( $url, PHP_URL_SCHEME ) : null;
		$host   = is_string( $url ) ? wp_parse_url( $url, PHP_URL_HOST ) : null;
		if ( ! is_string( $scheme ) || ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) || ! is_string( $host ) || '' === $host ) {
			return false;
		}
		foreach ( array( get_option( 'home' ), get_option( 'siteurl' ) ) as $own ) {
			$own_host = is_string( $own ) ? wp_parse_url( $own, PHP_URL_HOST ) : null;
			if ( is_string( $own_host ) && 0 === strcasecmp( $own_host, $host ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a value is a check status.
	 *
	 * @param mixed $status Value.
	 * @return bool
	 */
	private static function is_status( $status ) {
		return in_array( $status, array( 'pass', 'fail', 'unknown' ), true );
	}
}
