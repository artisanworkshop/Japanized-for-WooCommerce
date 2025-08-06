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
	 * Check permissions.
	 * Add authentication logic as needed.
	 *
	 * @param WP_REST_Request $request request object.
	 * @return bool
	 */
	public function check_permissions( $request ) {
		// Basic example: Allow all requests.
		// Implement proper authentication according to your security requirements.
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
				if ( ! in_array( $key, $internal_params ) ) {
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

			// Encrypt the data using AES-256-CBC.
			$method = 'AES-256-CBC';
			$key    = substr( hash( 'sha256', $site_hash ), 0, 32 );
			$iv     = substr( hash( 'sha256', $site_hash . 'iv' ), 0, 16 );

			$decrypted       = array(
				'public_live_key' => openssl_decrypt( base64_decode( $filtered_params['public_live_key'] ), $method, $key, 0, $iv ),
				'secret_live_key' => openssl_decrypt( base64_decode( $filtered_params['secret_live_key'] ), $method, $key, 0, $iv ),
				'public_test_key' => openssl_decrypt( base64_decode( $filtered_params['public_test_key'] ), $method, $key, 0, $iv ),
				'secret_test_key' => openssl_decrypt( base64_decode( $filtered_params['secret_test_key'] ), $method, $key, 0, $iv ),
			);
			$filtered_params = array_merge(
				$filtered_params,
				array_intersect_key( $decrypted, $filtered_params )
			);
			$logger          = wc_get_logger( 'paidy' );
			$logger->info( 'Paidy recieved result data: ' . json_encode( $filtered_params ) );

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
			$saved = update_option( 'paidy_received_data', $filtered_params, false );
			if ( false === $saved ) {
				// If update_option fails, try to add it.
				$saved = add_option( 'paidy_received_data', $filtered_params, '', 'no' );
			}
			// Check if the data was saved successfully.
			if ( $saved ) {
				// Success response.
				return new WP_REST_Response(
					array(
						'success'       => true,
						'message'       => 'Data saved successfully.',
						'received_data' => $filtered_params,
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
