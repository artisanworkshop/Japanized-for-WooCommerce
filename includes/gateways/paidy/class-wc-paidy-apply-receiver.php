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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
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
		// The transient is keyed by the token value itself (set in the admin wizard) so
		// parallel onboarding sessions cannot clobber each other's tokens. Verifying
		// existence of the scoped key is sufficient — no separate value comparison needed.
		//
		// Validate format before use: the token must be a 32-char alphanumeric string
		// (matching wp_generate_password(32, false)) to prevent non-string values or
		// oversized inputs from being used as transient key suffixes.
		$request_token = is_string( $request->get_param( 'state' ) ) ? $request->get_param( 'state' ) : '';
		if ( 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $request_token )
			|| false === get_transient( 'paidy_onboarding_state_' . $request_token ) ) {
			return new WP_Error(
				'paidy_invalid_state',
				__( 'Invalid or missing state token for Paidy onboarding.', 'woocommerce-for-japan' ),
				array( 'status' => 403 )
			);
		}

		// Do NOT delete the transient here — consume it only after the handler
		// completes successfully so a transient DB/decryption failure does not
		// permanently prevent retrying the onboarding callback.

		return true;
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
				// Re-validate the format here (same rule as check_permissions) so we
				// never build a transient key from an unsanitized param.
				$state_token = is_string( $request->get_param( 'state' ) ) ? $request->get_param( 'state' ) : '';
				if ( 1 === preg_match( '/^[A-Za-z0-9]{32}$/', $state_token ) ) {
					delete_transient( 'paidy_onboarding_state_' . $state_token );
				}

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
