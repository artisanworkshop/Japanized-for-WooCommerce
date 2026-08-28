<?php
/**
 * Paidy Apply Receiver
 *
 * Handles the REST API endpoints for receiving and processing Paidy applications.
 *
 * @package WooCommerce
 * @category Payment Gateways
 * @author Paidy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paidy Receiver Plugin Class.
 * REST API endpoint class for WordPress plugin.
 */
class WC_Paidy_Apply_Receiver {

	/**
	 * Prefix for the options that store active onboarding state tokens.
	 *
	 * Each token is stored as its own non-autoloaded option (prefix + token,
	 * value = issued UNIX time). One row per token means concurrent onboarding
	 * sessions insert separate rows and cannot overwrite each other — a single
	 * shared array option would lose tokens to read-modify-write races.
	 * Options survive object-cache evictions and have no TTL, unlike transients —
	 * the Paidy review takes days to weeks, far longer than any safe transient TTL.
	 */
	const STATE_OPTION_PREFIX = 'paidy_onboarding_state_';

	/**
	 * Request header carrying the HMAC-SHA256 signature of the callback body.
	 *
	 * The intermediary signs `<timestamp>.<raw JSON body>` with the site hash
	 * shared at application time, so a callback can be authenticated even when
	 * the one-time state token has expired or was never issued (applications
	 * submitted from plugin versions before 2.9.13 sent no state at all, and
	 * 2.9.13–2.9.14 kept it in a 2-day transient that never outlived the review).
	 *
	 * @since 2.9.16
	 */
	const SIGNATURE_HEADER = 'x-paidy-receiver-signature';

	/**
	 * Request header carrying the UNIX timestamp that was signed together with the body.
	 *
	 * @since 2.9.16
	 */
	const TIMESTAMP_HEADER = 'x-paidy-receiver-timestamp';

	/**
	 * Transient prefix for signatures that have already been accepted (replay guard).
	 *
	 * @since 2.9.16
	 */
	const SIGNATURE_USED_PREFIX = 'paidy_receiver_sig_';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Get the lifetime of an onboarding state token in seconds.
	 *
	 * @return int TTL in seconds.
	 */
	public static function get_state_token_ttl() {
		/**
		 * Filters the lifetime of a Paidy onboarding state token.
		 *
		 * The token must outlive the Paidy merchant review, which can take
		 * several weeks between application and the key-delivery callback.
		 *
		 * @param int $ttl Lifetime in seconds. Default 90 days.
		 */
		return (int) apply_filters( 'wc4jp_paidy_onboarding_state_ttl', 90 * DAY_IN_SECONDS );
	}

	/**
	 * Store a one-time onboarding state token.
	 *
	 * Expired entries are pruned on every store so no cron cleanup is needed.
	 *
	 * @param string $token 32-char alphanumeric state token.
	 * @return bool True if the token was persisted, false otherwise.
	 */
	public static function store_state_token( $token ) {
		if ( ! is_string( $token ) || 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
			return false;
		}

		self::prune_expired_state_tokens();

		update_option( self::STATE_OPTION_PREFIX . $token, time(), false );

		// Re-read to verify the token actually persisted — update_option()
		// returns false on a no-change write, so its return value alone
		// cannot distinguish failure from an identical existing value.
		return is_numeric( get_option( self::STATE_OPTION_PREFIX . $token ) );
	}

	/**
	 * Verify an onboarding state token exists and has not expired.
	 *
	 * Accepts raw request input: anything but a 32-char alphanumeric string is
	 * rejected before any storage key is built.
	 *
	 * @param mixed $token 32-char alphanumeric state token.
	 * @return bool True if the token is valid.
	 */
	public static function verify_state_token( $token ) {
		if ( ! is_string( $token ) || 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
			return false;
		}

		// is_numeric (not is_int): scalar options round-trip through the DB as
		// numeric strings on requests other than the one that stored them.
		$issued_at = get_option( self::STATE_OPTION_PREFIX . $token );
		if ( is_numeric( $issued_at ) && ( time() - (int) $issued_at ) <= self::get_state_token_ttl() ) {
			return true;
		}

		// Legacy fallback: tokens issued by older plugin versions were stored as
		// transients under the same key (transients live in separate, prefixed
		// option rows, so there is no collision). Keep accepting them so an
		// in-flight application submitted before the update still succeeds.
		// TODO: remove after 2-3 releases.
		if ( false !== get_transient( self::STATE_OPTION_PREFIX . $token ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Consume (delete) an onboarding state token so it cannot be reused.
	 *
	 * Accepts raw request input: anything but a 32-char alphanumeric string is
	 * rejected before any storage key is built.
	 *
	 * @param mixed $token 32-char alphanumeric state token.
	 * @return void
	 */
	public static function consume_state_token( $token ) {
		if ( ! is_string( $token ) || 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
			return;
		}

		delete_option( self::STATE_OPTION_PREFIX . $token );

		// Also clear the legacy transient variant. Remove together with the
		// legacy fallback in verify_state_token().
		delete_transient( self::STATE_OPTION_PREFIX . $token );
	}

	/**
	 * Get the maximum allowed clock drift between the signed timestamp and now.
	 *
	 * @since 2.9.16
	 *
	 * @return int Tolerance in seconds.
	 */
	public static function get_signature_tolerance() {
		/**
		 * Filters how far the signed callback timestamp may deviate from the
		 * receiving site's clock before the signature is rejected.
		 *
		 * @since 2.9.16
		 *
		 * @param int $tolerance Tolerance in seconds. Default 10 minutes.
		 */
		return (int) apply_filters( 'wc4jp_paidy_receiver_signature_tolerance', 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Verify an HMAC-SHA256 signature over `<timestamp>.<body>` keyed by the site hash.
	 *
	 * Accepts raw header values: anything but a numeric timestamp within the
	 * tolerance window and a 64-char lowercase hex digest is rejected before
	 * any HMAC is computed. Replays of an already-accepted signature are
	 * rejected as well (see mark_signature_used()).
	 *
	 * @since 2.9.16
	 *
	 * @param mixed  $timestamp UNIX timestamp from the request header.
	 * @param mixed  $signature Hex HMAC digest from the request header.
	 * @param string $body      Raw request body exactly as received.
	 * @param string $site_hash Shared secret established at application time.
	 * @return bool True if the signature is valid, fresh, and unused.
	 */
	public static function verify_request_signature( $timestamp, $signature, $body, $site_hash ) {
		if ( ! is_string( $site_hash ) || '' === $site_hash || ! is_string( $body ) ) {
			return false;
		}
		if ( ! is_string( $signature ) || 1 !== preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return false;
		}
		if ( ! is_string( $timestamp ) && ! is_int( $timestamp ) ) {
			return false;
		}
		if ( 1 !== preg_match( '/^[0-9]{1,12}$/', (string) $timestamp ) ) {
			return false;
		}
		if ( abs( time() - (int) $timestamp ) > self::get_signature_tolerance() ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $body, $site_hash );
		if ( ! hash_equals( $expected, $signature ) ) {
			return false;
		}

		return ! self::is_signature_used( $signature );
	}

	/**
	 * Whether a signature has already been accepted by a successful callback.
	 *
	 * @since 2.9.16
	 *
	 * @param string $signature Hex HMAC digest.
	 * @return bool
	 */
	private static function is_signature_used( $signature ) {
		return false !== get_transient( self::SIGNATURE_USED_PREFIX . substr( $signature, 0, 40 ) );
	}

	/**
	 * Record an accepted signature so the same callback cannot be replayed.
	 *
	 * A transient is appropriate here (unlike the state token) because the
	 * replay window is bounded by the short signature tolerance, not by the
	 * length of the Paidy review.
	 *
	 * @since 2.9.16
	 *
	 * @param mixed $signature Hex HMAC digest from the request header.
	 * @return void
	 */
	public static function mark_signature_used( $signature ) {
		if ( ! is_string( $signature ) || 1 !== preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return;
		}
		set_transient(
			self::SIGNATURE_USED_PREFIX . substr( $signature, 0, 40 ),
			time(),
			2 * self::get_signature_tolerance()
		);
	}

	/**
	 * Delete state token options that are past their TTL or hold invalid values.
	 *
	 * Runs on every store_state_token() call so no cron cleanup is needed.
	 *
	 * @return void
	 */
	private static function prune_expired_state_tokens() {
		global $wpdb;

		// Token options are non-autoloaded and keyed by a random token value,
		// so there is no core API to enumerate them — a direct LIKE query is
		// required. esc_like() makes the underscores match literally instead
		// of acting as single-character LIKE wildcards, so the pattern cannot
		// accidentally match other, similarly named option rows.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( self::STATE_OPTION_PREFIX ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $rows ) ) {
			return;
		}

		$ttl = self::get_state_token_ttl();
		$now = time();
		foreach ( $rows as $row ) {
			if ( ! is_numeric( $row->option_value ) || ( $now - (int) $row->option_value ) > $ttl ) {
				delete_option( $row->option_name );
			}
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'paidy-receiver/v1',
			'/receive',
			array(
				'methods'             => 'GET, POST',
				'callback'            => array( $this, 'handle_receive_data' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Check permissions for the Paidy receiver endpoint.
	 * Verifies site hash is configured and application_id format is valid.
	 *
	 * @param WP_REST_Request $request request object.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		// Require paidy_site_hash to be configured before accepting any data.
		$site_hash = get_option( 'paidy_site_hash' );
		if ( empty( $site_hash ) ) {
			return new WP_Error(
				'paidy_not_configured',
				__( 'Paidy onboarding is not configured.', 'woocommerce-for-japan' ),
				array( 'status' => 403 )
			);
		}

		// Validate application_id format (alphanumeric, hyphens, underscores only).
		$application_id = $request->get_param( 'application_id' );
		if ( empty( $application_id ) || ! preg_match( '/^[a-zA-Z0-9_\-]+$/', $application_id ) ) {
			return new WP_Error(
				'paidy_invalid_request',
				__( 'Invalid application ID format.', 'woocommerce-for-japan' ),
				array( 'status' => 400 )
			);
		}

		// Verify the one-time state token generated when the onboarding form was submitted.
		// Tokens are stored keyed by their own value (set in the admin wizard) so
		// parallel onboarding sessions cannot clobber each other's tokens. Verifying
		// existence of the scoped key is sufficient — no separate value comparison needed.
		//
		// verify_state_token() validates the format internally (32-char alphanumeric,
		// matching wp_generate_password(32, false)) before building any storage key,
		// so non-string values or oversized inputs are rejected there.
		//
		// Do NOT consume the token here — consume it only after the handler
		// completes successfully so a transient DB/decryption failure does not
		// permanently prevent retrying the onboarding callback.
		if ( self::verify_state_token( $request->get_param( 'state' ) ) ) {
			return true;
		}

		// No usable state token. Fall back to the signed-callback path: the
		// intermediary signs the raw body with the site hash it received at
		// application time, so the callback can still be authenticated when the
		// token expired (2.9.13–2.9.14 stored it in a 2-day transient), was
		// never issued (pre-2.9.13 applications), or was lost to a reinstall.
		$signature = $request->get_header( self::SIGNATURE_HEADER );
		$timestamp = $request->get_header( self::TIMESTAMP_HEADER );
		if ( null !== $signature ) {
			if ( self::verify_request_signature( $timestamp, $signature, $request->get_body(), $site_hash ) ) {
				wc_get_logger()->info(
					'Paidy onboarding callback accepted via body signature (state token missing or expired).',
					array( 'source' => 'paidy-wc' )
				);
				return true;
			}

			// A signature was sent but did not verify. The most common field
			// cause is server clock drift beyond the tolerance, so record the
			// drift to make the 403 diagnosable from the site's own log.
			$drift = is_numeric( $timestamp ) ? (string) ( time() - (int) $timestamp ) : 'n/a';
			wc_get_logger()->warning(
				sprintf(
					'Paidy onboarding callback rejected: signature header present but invalid (timestamp drift: %s s, tolerance: %d s). Check the server clock and that paidy_site_hash matches the value sent at application time.',
					$drift,
					self::get_signature_tolerance()
				),
				array( 'source' => 'paidy-wc' )
			);
		}

		return new WP_Error(
			'paidy_invalid_state',
			__( 'Invalid or missing state token or signature for Paidy onboarding.', 'woocommerce-for-japan' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Handle received POST data.
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_receive_data( $request ) {
		try {
			// Get POST parameters from form data.
			$post_params = $request->get_params();

			// Remove WordPress internal parameters if they exist.
			$filtered_params = array();
			$internal_params = array( '_wpnonce', '_wp_http_referer', 'rest_route' );

			foreach ( $post_params as $key => $value ) {
				if ( ! in_array( $key, $internal_params, true ) ) {
					$filtered_params[ $key ] = $value;
				}
			}

			// Check if data exists.
			if ( empty( $filtered_params ) ) {
				return new WP_Error(
					'no_data',
					'No POST data found.',
					array( 'status' => 400 )
				);
			}

			if ( ! isset( $filtered_params['application_id'] ) || empty( $filtered_params['application_id'] ) ) {
				return new WP_Error(
					'missing_application_id',
					'Missing or empty application ID.',
					array( 'status' => 400 )
				);
			}

			// Check if the site hash is set.
			$site_hash = get_option( 'paidy_site_hash' );
			if ( empty( $site_hash ) ) {
				return new WP_Error(
					'missing_site_hash',
					'Site hash is not set.',
					array( 'status' => 400 )
				);
			}

			// Decrypt AES-256-CBC-encoded API keys sent by the Paidy intermediary server.
			$method     = 'AES-256-CBC';
			$aes_key    = substr( hash( 'sha256', $site_hash ), 0, 32 );
			$aes_iv     = substr( hash( 'sha256', $site_hash . 'iv' ), 0, 16 );
			$key_fields = array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' );
			$decrypted  = array();

			foreach ( $key_fields as $field ) {
				if ( ! isset( $filtered_params[ $field ] ) ) {
					// Field absent — store empty string so all four key fields are
					// always present in $decrypted and merged into $filtered_params.
					$decrypted[ $field ] = '';
					continue;
				}

				// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- legitimate AES decryption of Paidy-supplied key data.
				$decoded = base64_decode( (string) $filtered_params[ $field ], true );
				// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				if ( false === $decoded ) {
					return new WP_Error(
						'paidy_invalid_encoding',
						/* translators: %s: API key field name */
						sprintf( __( 'Invalid base64 encoding for field: %s', 'woocommerce-for-japan' ), esc_html( $field ) ),
						array( 'status' => 400 )
					);
				}

				// OPENSSL_RAW_DATA is required because $decoded is already raw binary
				// (we base64-decoded it above). Without this flag openssl_decrypt()
				// would attempt a second base64 decode and fail.
				$result = openssl_decrypt( $decoded, $method, $aes_key, OPENSSL_RAW_DATA, $aes_iv );
				if ( false === $result ) {
					return new WP_Error(
						'paidy_decryption_failed',
						/* translators: %s: API key field name */
						sprintf( __( 'Decryption failed for field: %s', 'woocommerce-for-japan' ), esc_html( $field ) ),
						array( 'status' => 400 )
					);
				}

				$decrypted[ $field ] = $result;
			}

			// Merge all four key fields (present or absent) into $filtered_params so
			// downstream code can access them unconditionally without undefined-index notices.
			$filtered_params = array_merge( $filtered_params, $decrypted );

			if ( isset( $filtered_params['paidy_status'] ) ) {
				$paidy_status         = $filtered_params['paidy_status'];
				$allowed_status_array = array( 'approved', 'rejected', 'canceled' );
				// Validate the paidy_status.
				if ( ! in_array( $paidy_status, $allowed_status_array, true ) ) {
					return new WP_Error(
						'invalid_paidy_status',
						'Invalid paidy_status value. Allowed values are: ' . implode( ', ', $allowed_status_array ),
						array( 'status' => 400 )
					);
				}
				// Additional processing for approved status can be added here if needed.
				$woocommerce_paidy_on_boarding_settings = get_option( 'woocommerce_paidy_on_boarding_settings', array() );
				$current_step                           = isset( $woocommerce_paidy_on_boarding_settings['currentStep'] ) ? $woocommerce_paidy_on_boarding_settings['currentStep'] : 0;
				if ( 'approved' === $paidy_status ) {
					// Require all four key fields to be non-empty after decryption.
					// An approved callback from the intermediary always contains all four
					// keys; an empty string here means the field was absent or the
					// intermediary sent an incomplete payload. Accepting empty keys would
					// silently overwrite existing credentials with blank values, breaking
					// payment processing without any obvious error.
					$required_key_fields = array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' );
					foreach ( $required_key_fields as $key_field ) {
						if ( empty( $filtered_params[ $key_field ] ) ) {
							return new WP_Error(
								'paidy_missing_key',
								/* translators: %s: API key field name */
								sprintf( __( 'Approved response is missing a required API key field: %s', 'woocommerce-for-japan' ), esc_html( $key_field ) ),
								array( 'status' => 400 )
							);
						}
					}

					// Process approved status.
					$woocommerce_paidy_on_boarding_settings['currentStep'] = 3;
					update_option( 'woocommerce_paidy_on_boarding_settings', $woocommerce_paidy_on_boarding_settings );

					$woocommerce_paidy_settings                        = get_option( 'woocommerce_paidy_settings', array() );
					$woocommerce_paidy_settings['api_public_key']      = $filtered_params['public_live_key'];
					$woocommerce_paidy_settings['api_secret_key']      = $filtered_params['secret_live_key'];
					$woocommerce_paidy_settings['test_api_public_key'] = $filtered_params['public_test_key'];
					$woocommerce_paidy_settings['test_api_secret_key'] = $filtered_params['secret_test_key'];
					$woocommerce_paidy_settings['environment']         = '';
					update_option( 'woocommerce_paidy_settings', $woocommerce_paidy_settings );

					do_action( 'paidy_application_approved', $filtered_params );
				} elseif ( 'rejected' === $paidy_status || 'canceled' === $paidy_status ) {
					if ( 'canceled' === $paidy_status ) {
						// Process canceled status.
						delete_option( 'woocommerce_paidy_on_boarding_settings' );
						delete_option( 'paidy_application_id' );
					} else {
						// Process rejected status.
						$woocommerce_paidy_on_boarding_settings['currentStep'] = 99;
						update_option( 'woocommerce_paidy_on_boarding_settings', $woocommerce_paidy_on_boarding_settings );
					}

					$woocommerce_paidy_settings                        = get_option( 'woocommerce_paidy_settings', array() );
					$woocommerce_paidy_settings['api_public_key']      = '';
					$woocommerce_paidy_settings['api_secret_key']      = '';
					$woocommerce_paidy_settings['test_api_public_key'] = '';
					$woocommerce_paidy_settings['test_api_secret_key'] = '';
					$woocommerce_paidy_settings['environment']         = '';
					update_option( 'woocommerce_paidy_settings', $woocommerce_paidy_settings );

					do_action( 'paidy_application_rejected', $filtered_params );
				}
			}

			// Save data to wp_option.
			// update_option() returns false both when the save fails AND when the stored
			// value is already identical to $filtered_params (no-change). Treat the
			// no-change case as success so retries with an identical payload do not
			// incorrectly return a 500 and skip consuming the one-time state token.
			$saved = update_option( 'paidy_received_data', $filtered_params, false );
			if ( false === $saved ) {
				if ( get_option( 'paidy_received_data' ) === $filtered_params ) {
					$saved = true; // Value already identical — treat as success.
				} else {
					// Option does not exist yet — create it.
					$saved = add_option( 'paidy_received_data', $filtered_params, '', 'no' );
				}
			}
			// Check if the data was saved successfully.
			if ( $saved ) {
				// Consume the one-time state token now that the handler has fully
				// succeeded — consuming it here (not in check_permissions) means a
				// transient DB or decryption failure during processing does not
				// permanently prevent the merchant from retrying the callback.
				// consume_state_token() validates the format internally (same rule
				// as verify_state_token) so it never builds a storage key from an
				// unsanitized param.
				self::consume_state_token( $request->get_param( 'state' ) );
				self::mark_signature_used( $request->get_header( self::SIGNATURE_HEADER ) );

				// Success response — omit decrypted API key fields to avoid
				// exposing secrets via response bodies, proxy logs, or intermediaries.
				$sensitive_fields = array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' );
				$safe_received    = array_diff_key( $filtered_params, array_flip( $sensitive_fields ) );

				return new WP_REST_Response(
					array(
						'success'       => true,
						'message'       => 'Data saved successfully.',
						'received_data' => $safe_received,
						'timestamp'     => current_time( 'mysql' ),
					),
					200
				);
			} else {
				// Save failed.
				return new WP_Error(
					'save_failed',
					'Failed to save data.',
					array( 'status' => 500 )
				);
			}
		} catch ( Exception $e ) {
			// Error handling.
			return new WP_Error(
				'server_error',
				'Server error occurred: ' . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Helper method to get saved data.
	 *
	 * @return mixed
	 */
	public function get_received_data() {
		return get_option( 'received_data', array() );
	}

	/**
	 * Helper method to delete saved data.
	 *
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public function delete_received_data() {
		return delete_option( 'received_data' );
	}
}
