<?php
/**
 * REST API of the Security tab.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/class-jp4wc-security-feed.php';
require_once dirname( __DIR__ ) . '/class-jp4wc-login-limiter.php';
require_once dirname( __DIR__ ) . '/class-jp4wc-security-hardening.php';

/**
 * JP4WC_Security_API
 *
 * Gives the Security tab of the settings screen the result of the last
 * security check (GET /jp4wc/v1/security/findings) and asks for a re-check
 * (POST /jp4wc/v1/security/rescan), which runs in WP-Cron.
 */
class JP4WC_Security_API extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'jp4wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'security';

	/**
	 * Seconds that must pass between two re-checks asked for.
	 */
	const RECHECK_INTERVAL = 60;

	/**
	 * Seconds a re-check asked for is shown as running once its event has
	 * started, until it records that it ran (10 minutes).
	 */
	const RECHECK_RUNNING_FOR = 600;

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/findings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => self::with_bundled_translation( array( $this, 'get_findings' ) ),
					'permission_callback' => self::with_bundled_translation( array( $this, 'get_findings_permissions_check' ) ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/rescan',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => self::with_bundled_translation( array( $this, 'rescan' ) ),
					'permission_callback' => self::with_bundled_translation( array( $this, 'rescan_permissions_check' ) ),
				),
			)
		);
	}

	/**
	 * Returns a route callback that runs with the strings the WordPress.org
	 * language pack lacks taken from the bundled translation, so that the
	 * Security tab does not show the error messages in English.
	 *
	 * @param callable $callback Route callback.
	 * @return Closure
	 */
	private static function with_bundled_translation( callable $callback ) {
		return static function ( $request ) use ( $callback ) {
			// During an update, the previous version's includes() can load this file
			// with a main file that lacks the helper.
			if ( ! function_exists( 'jp4wc_with_bundled_translation' ) ) {
				return $callback( $request );
			}
			return jp4wc_with_bundled_translation( $callback, $request );
		};
	}

	/**
	 * Check if a given request has access to the findings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_findings_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- REST permission callback signature.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view the security check.', 'woocommerce-for-japan' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request may ask for a re-check.
	 *
	 * The REST nonce is checked here as well as by WordPress's cookie
	 * authentication, so that the re-check is asked for from the settings
	 * screen only, whatever authenticated the request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function rescan_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to run the security check.', 'woocommerce-for-japan' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'jp4wc_invalid_nonce',
				__( 'The page has expired. Reload it and try again.', 'woocommerce-for-japan' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Returns the result of the last security check.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_findings( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- REST callback signature.
		return rest_ensure_response( self::view() );
	}

	/**
	 * Asks for a re-check.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error 202 with the result shown until the re-check is done.
	 */
	public function rescan( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- REST callback signature.
		if ( ! JP4WC_Security_Feed::is_enabled() ) {
			return new WP_Error(
				'jp4wc_security_disabled',
				__( 'The security check is off.', 'woocommerce-for-japan' ),
				array( 'status' => 409 )
			);
		}

		$wait = self::retry_after();
		if ( $wait > 0 ) {
			return new WP_Error(
				'jp4wc_security_rescan_too_soon',
				sprintf(
					/* translators: %d: seconds to wait */
					_n( 'A re-check was asked for a moment ago. Try again in %d second.', 'A re-check was asked for a moment ago. Try again in %d seconds.', $wait, 'woocommerce-for-japan' ),
					$wait
				),
				array(
					'status'      => 429,
					'retry_after' => $wait,
				)
			);
		}

		$requested_at = JP4WC_Security_Feed::request_recheck();
		if ( is_wp_error( $requested_at ) ) {
			return new WP_Error(
				'jp4wc_security_recheck_not_scheduled',
				__( 'The re-check could not be scheduled in WP-Cron. Try again later.', 'woocommerce-for-japan' ),
				array( 'status' => 500 )
			);
		}
		if ( 0 === $requested_at ) {
			// Turned off by another request meanwhile.
			return new WP_Error(
				'jp4wc_security_disabled',
				__( 'The security check is off.', 'woocommerce-for-japan' ),
				array( 'status' => 409 )
			);
		}
		$response = rest_ensure_response( self::view() );
		$response->set_status( 202 );
		return $response;
	}

	/**
	 * Returns what the Security tab shows.
	 *
	 * @return array{
	 *     enabled: bool,
	 *     checked_at: int,
	 *     score: array<string, int>|null,
	 *     requirements: array<string, array{score: int, status: string, pass: int, fail: int, unknown: int}>|null,
	 *     checks: array<int, array{check_id: string, requirement: string, severity: string, status: string}>,
	 *     vulnerabilities: array|null,
	 *     feed: array{host: string, has_data: bool, synced_at: int, last_attempt: int, failing: bool, unavailable: bool},
	 *     recheck: array{requested_at: int, done_at: int, pending: bool, retry_after: int},
	 *     login_limit: array{enabled: bool, active: bool, protected_by: array{type: string, name: string}|null},
	 *     hardening: array{rest_users: array{enabled: bool}, file_edit: array{enabled: bool, defined_elsewhere: bool}}
	 * }
	 *         Requirement status: pass when every check passed, action when one
	 *         failed, otherwise unknown. vulnerabilities is null without data.
	 *         login_limit (3.0.0): the login attempt limiter's setting, whether
	 *         it runs, and what else protects the login page (then it sleeps).
	 */
	public static function view() {
		$protected_by = JP4WC_Login_Limiter::protected_by();
		$view         = array(
			'login_limit'     => array(
				'enabled'      => JP4WC_Login_Limiter::is_enabled(),
				'active'       => JP4WC_Login_Limiter::is_active(),
				'protected_by' => $protected_by,
			),
			'hardening'       => array(
				'rest_users' => array( 'enabled' => JP4WC_Security_Hardening::hides_rest_users() ),
				'file_edit'  => array(
					'enabled'           => JP4WC_Security_Hardening::disallows_file_edit(),
					'defined_elsewhere' => JP4WC_Security_Hardening::file_edit_defined_elsewhere(),
				),
			),
			'enabled'         => JP4WC_Security_Feed::is_enabled(),
			'checked_at'      => 0,
			'score'           => null,
			'requirements'    => null,
			'checks'          => array(),
			'vulnerabilities' => null,
			'feed'            => array(
				'host'         => (string) wp_parse_url( JP4WC_Security_Feed::base_url(), PHP_URL_HOST ),
				'has_data'     => false,
				'synced_at'    => 0,
				'last_attempt' => 0,
				'failing'      => false,
				'unavailable'  => false,
			),
			'recheck'         => array(
				'requested_at' => 0,
				'done_at'      => 0,
				'pending'      => false,
				'retry_after'  => 0,
			),
		);
		if ( ! $view['enabled'] ) {
			return $view;
		}

		$result = JP4WC_Security_Feed::get_checks();
		if ( null !== $result ) {
			$view['checked_at'] = (int) $result['checked_at'];
			$view['checks']     = self::checks( $result['checks'] );
			$view['score']      = array();
			foreach ( array_merge( array( 'total' ), JP4WC_Security_Checks::REQUIREMENTS ) as $key ) {
				$view['score'][ $key ] = isset( $result['score'][ $key ] ) ? (int) $result['score'][ $key ] : 0;
			}
			$view['requirements'] = self::requirements( $view['checks'], $view['score'] );
		}

		$findings = JP4WC_Security_Feed::get_findings();
		if ( null !== $findings ) {
			$view['vulnerabilities'] = self::vulnerabilities( $findings );
		}

		$state        = JP4WC_Security_Feed::get_state();
		$view['feed'] = array(
			'host'         => $view['feed']['host'],
			'has_data'     => is_array( $state['files'] ) && array() !== $state['files'],
			'synced_at'    => (int) $state['synced_at'],
			'last_attempt' => (int) $state['last_attempt'],
			'failing'      => ! empty( $state['failing_since'] ),
			'unavailable'  => JP4WC_Security_Feed::is_unavailable(),
		);

		$requested_at    = JP4WC_Security_Feed::recheck_requested_at();
		$done_at         = JP4WC_Security_Feed::recheck_done_at();
		$view['recheck'] = array(
			'requested_at' => $requested_at,
			'done_at'      => $done_at,
			// WordPress removes the event before running it: then the time since the request tells.
			'pending'      => $requested_at > $done_at
				&& ( false !== wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) || time() - $requested_at < self::RECHECK_RUNNING_FOR ),
			'retry_after'  => self::retry_after(),
		);

		return $view;
	}

	/**
	 * Returns the seconds to wait before another re-check can be asked for.
	 *
	 * @return int
	 */
	private static function retry_after() {
		$requested_at = JP4WC_Security_Feed::recheck_requested_at();
		if ( $requested_at <= 0 ) {
			return 0;
		}
		// A request time in the future (the clock went back) waits one interval at most.
		return max( 0, min( self::RECHECK_INTERVAL, $requested_at + self::RECHECK_INTERVAL - time() ) );
	}

	/**
	 * Returns the stored checks that are well formed.
	 *
	 * @param array $checks Stored checks.
	 * @return array<int, array{check_id: string, requirement: string, severity: string, status: string}>
	 */
	private static function checks( array $checks ) {
		$valid = array();
		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) || ! isset( $check['check_id'], $check['requirement'], $check['severity'], $check['status'] ) ) {
				continue;
			}
			if ( ! is_string( $check['check_id'] ) || ! is_string( $check['requirement'] ) || ! is_string( $check['severity'] ) || ! in_array( $check['status'], array( 'pass', 'fail', 'unknown' ), true ) ) {
				continue;
			}
			$valid[] = array(
				'check_id'    => $check['check_id'],
				'requirement' => $check['requirement'],
				'severity'    => $check['severity'],
				'status'      => $check['status'],
			);
		}
		return $valid;
	}

	/**
	 * Returns the score, status and counts of each requirement.
	 *
	 * A requirement passes only when all of its checks passed: a check that
	 * could not be run leaves it unknown, though it costs no points.
	 *
	 * @param array $checks Checks, as checks() returns them.
	 * @param array $score  Score of each requirement.
	 * @return array<string, array{score: int, status: string, pass: int, fail: int, unknown: int}>
	 */
	private static function requirements( array $checks, array $score ) {
		$requirements = array();
		foreach ( JP4WC_Security_Checks::REQUIREMENTS as $requirement ) {
			$counts = array(
				'pass'    => 0,
				'fail'    => 0,
				'unknown' => 0,
			);
			foreach ( $checks as $check ) {
				if ( $requirement === $check['requirement'] ) {
					++$counts[ $check['status'] ];
				}
			}
			if ( $counts['fail'] > 0 ) {
				$status = 'action';
			} elseif ( $counts['unknown'] > 0 || 0 === $counts['pass'] ) {
				$status = 'unknown';
			} else {
				$status = 'pass';
			}
			$requirements[ $requirement ] = array_merge(
				array(
					'score'  => $score[ $requirement ],
					'status' => $status,
				),
				$counts
			);
		}
		return $requirements;
	}

	/**
	 * Returns the vulnerability findings the tab shows.
	 *
	 * @param array $findings Findings, as JP4WC_Security_Feed::get_findings() returns them.
	 * @return array{stale: bool, generated_at: string, count: int, items: array, attribution: string, copyrights: array}
	 */
	private static function vulnerabilities( array $findings ) {
		$items = array();
		foreach ( $findings['items'] as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['type'], $item['slug'], $item['name'], $item['version'], $item['vulnerabilities'] ) || ! is_array( $item['vulnerabilities'] ) ) {
				continue;
			}
			$records = array();
			foreach ( $item['vulnerabilities'] as $record ) {
				if ( ! is_array( $record ) || ! isset( $record['id'], $record['title'], $record['reference'], $record['patched'], $record['patched_versions'] ) ) {
					continue;
				}
				$records[] = array(
					'id'               => (string) $record['id'],
					'title'            => (string) $record['title'],
					'reference'        => esc_url_raw( (string) $record['reference'] ),
					'rating'           => isset( $record['rating'] ) ? (string) $record['rating'] : '',
					'score'            => isset( $record['score'] ) ? (float) $record['score'] : null,
					'patched'          => (bool) $record['patched'],
					'patched_versions' => array_values( array_map( 'strval', (array) $record['patched_versions'] ) ),
				);
			}
			$items[] = array(
				'type'            => (string) $item['type'],
				'slug'            => (string) $item['slug'],
				'name'            => (string) $item['name'],
				'version'         => (string) $item['version'],
				'manage_url'      => self::manage_url( $item ),
				'vulnerabilities' => $records,
			);
		}

		$copyrights = array();
		if ( is_array( $findings['copyrights'] ) ) {
			foreach ( $findings['copyrights'] as $copyright ) {
				if ( ! is_array( $copyright ) || ! isset( $copyright['notice'], $copyright['license'], $copyright['license_url'] ) ) {
					continue;
				}
				$copyrights[] = array(
					'notice'      => (string) $copyright['notice'],
					'license'     => (string) $copyright['license'],
					'license_url' => esc_url_raw( (string) $copyright['license_url'] ),
				);
			}
		}

		return array(
			'stale'        => JP4WC_Security_Feed::is_stale( $findings ),
			'generated_at' => is_string( $findings['generated_at'] ) ? $findings['generated_at'] : '',
			'count'        => (int) $findings['count'],
			'items'        => $items,
			'attribution'  => is_string( $findings['attribution'] ) ? $findings['attribution'] : '',
			'copyrights'   => $copyrights,
		);
	}

	/**
	 * Returns where an affected copy is updated: its row in the plugins list,
	 * the theme details or the WordPress update screen.
	 *
	 * @param array $item Affected copy.
	 * @return string
	 */
	private static function manage_url( array $item ) {
		switch ( $item['type'] ) {
			case 'plugin':
				$file = isset( $item['file'] ) ? (string) $item['file'] : '';
				// The id of the plugin's checkbox in the plugins list (WP_Plugins_List_Table::single_row()).
				return admin_url( 'plugins.php' ) . ( '' !== $file ? '#checkbox_' . md5( $file ) : '' );
			case 'theme':
				return add_query_arg( 'theme', rawurlencode( (string) $item['slug'] ), admin_url( 'themes.php' ) );
			default:
				return network_admin_url( 'update-core.php' );
		}
	}
}
