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
		$option_map    = $this->get_option_map();
		$settings      = array();

		foreach ( $settings_keys as $key ) {
			$option_name      = isset( $option_map[ $key ] ) ? $option_map[ $key ] : $this->prefix . $key;
			$settings[ $key ] = get_option( $option_name, '' );
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
		$params         = $request->get_params();
		$allowed_keys   = $this->get_all_setting_keys();
		$allowed_keys[] = 'timeZones';
		$option_map     = $this->get_option_map();

		// Save each setting — only allow whitelisted keys.
		foreach ( $params as $key => $value ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				continue;
			}
			if ( 'timeZones' === $key ) {
				// Handle time zones separately.
				update_option( 'wc4jp_time_zone_details', $value );
			} else {
				$option_name = isset( $option_map[ $key ] ) ? $option_map[ $key ] : $this->prefix . $key;
				update_option( $option_name, $value );
			}
		}

		// Get updated settings.
		return $this->get_settings( $request );
	}

	/**
	 * Get the option name map for keys that use a non-default WP option name.
	 *
	 * Add-on plugins can use the `jp4wc_setting_option_map` filter to map
	 * their REST key (e.g. 'pro-cod') to their existing WP option name
	 * (e.g. 'jp4wc_pro_cod'), preserving backward compatibility.
	 *
	 * @since 2.9.5
	 * @return array Map of key => full WP option name.
	 */
	private function get_option_map() {
		/**
		 * Filters the map of REST setting keys to WordPress option names.
		 *
		 * @since 2.9.5
		 * @param array $map Associative array of key => wp_option_name.
		 */
		return apply_filters( 'jp4wc_setting_option_map', array() );
	}

	/**
	 * Get all setting keys.
	 *
	 * @return array
	 */
	private function get_all_setting_keys() {
		$keys = array(
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

		/**
		 * Filters the list of allowed setting keys for the REST API.
		 *
		 * Add-on plugins (e.g. jp4wc-pro) can use this filter to register
		 * additional setting keys so they are accepted by GET/POST /jp4wc/v1/settings.
		 *
		 * @since 2.9.5
		 * @param array $keys Whitelisted setting key suffixes (without the 'wc4jp-' prefix).
		 */
		return apply_filters( 'jp4wc_allowed_setting_keys', $keys );
	}
}
