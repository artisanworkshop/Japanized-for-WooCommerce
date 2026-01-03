<?php
/**
 * REST API Controller for JP4WC Settings
 *
 * @package Japanized_For_WooCommerce
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JP4WC_Settings_API class.
 */
class JP4WC_Settings_API extends WP_REST_Controller {

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
	protected $rest_base = 'settings';

	/**
	 * Prefix for options.
	 *
	 * @var string
	 */
	protected $prefix = 'wc4jp-';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_settings_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to get settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_settings_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to view settings.', 'woocommerce-for-japan' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to update settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_settings_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to update settings.', 'woocommerce-for-japan' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get all settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_settings( $request ) {
		$settings_keys = $this->get_all_setting_keys();
		$settings      = array();

		foreach ( $settings_keys as $key ) {
			$value            = get_option( $this->prefix . $key, '' );
			$settings[ $key ] = $value;
		}

		// Get time zones separately.
		$settings['timeZones'] = get_option( 'wc4jp_time_zone_details', array() );

		return rest_ensure_response( $settings );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( $request ) {
		$params = $request->get_params();

		// Debug: Log received parameters.
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->debug( 'JP4WC Settings API - Received params: ' . wp_json_encode( $params ), array( 'source' => 'jp4wc-settings-api' ) );
			if ( isset( $params['reception-period'] ) ) {
				$logger->debug( 'JP4WC Settings API - reception-period value: ' . $params['reception-period'], array( 'source' => 'jp4wc-settings-api' ) );
			}
		}

		// Save each setting.
		foreach ( $params as $key => $value ) {
			if ( 'timeZones' === $key ) {
				// Handle time zones separately.
				update_option( 'wc4jp_time_zone_details', $value );
			} else {
				// Save other settings.
				update_option( $this->prefix . $key, $value );
			}
		}

		// Get updated settings.
		return $this->get_settings( $request );
	}

	/**
	 * Get all setting keys.
	 *
	 * @return array
	 */
	private function get_all_setting_keys() {
		return array(
			// General settings.
			'yomigana',
			'yomigana-required',
			'honorific-suffix',
			'company-name',
			'zip2address',
			'yahoo-app-id',
			'no-ja',
			'free-shipping',
			'custom-email-customer-name',
			'billing_postcode',
			'billing_state',
			'billing_city',
			'billing_address_1',
			'billing_address_2',
			'billing_phone',
			'tracking',
			// Shipment settings.
			'delivery-date',
			'delivery-date-required',
			'start-date',
			'reception-period',
			'unspecified-date',
			'delivery-deadline',
			'no-mon',
			'no-tue',
			'no-wed',
			'no-thu',
			'no-fri',
			'no-sat',
			'no-sun',
			'holiday-start-date',
			'holiday-end-date',
			'delivery-time-zone',
			'delivery-time-zone-required',
			'unspecified-time',
			'date-format',
			'day-of-week',
			'delivery-notification-email',
			// Payment settings.
			'bankjp',
			'postofficebank',
			'atstore',
			'cod2',
			'jp4wc-paypal',
			'extra_charge_name',
			'extra_charge_amount',
			'extra_charge_max_cart_value',
			'extra_charge_calc_taxes',
			'extra_charge_tax_class',
			// Law settings.
			'law-shop-name',
			'law-company-name',
			'law-owner-name',
			'law-manager-name',
			'law-location',
			'law-contact',
			'law-tel',
			'law-price',
			'law-payment',
			'law-purchase',
			'law-delivery',
			'law-cost',
			'law-return',
			'law-special',
			// Affiliate settings.
			'affiliate-a8',
			'affiliate-a8-test',
			'affiliate-a8-pid',
			'affiliate-felmat',
			'affiliate-felmat-pid',
		);
	}
}
