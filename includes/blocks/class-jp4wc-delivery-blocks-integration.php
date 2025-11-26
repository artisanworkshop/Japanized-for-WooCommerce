<?php
/**
 * JP4WC Delivery Blocks Integration
 *
 * @package JP4WC\Blocks
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class for integrating delivery date and time fields with WooCommerce Blocks.
 */
class JP4WC_Delivery_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'jp4wc-delivery';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
		$this->extend_store_api();
	}

	/**
	 * Register Store API endpoint data.
	 */
	private function extend_store_api() {
		if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
					'namespace'       => 'jp4wc-delivery',
					'data_callback'   => array( $this, 'extend_checkout_data' ),
					'schema_callback' => array( $this, 'extend_checkout_schema' ),
					'schema_type'     => ARRAY_A,
				)
			);
		}
	}

	/**
	 * Extend checkout endpoint with delivery data.
	 *
	 * @return array
	 */
	public function extend_checkout_data() {
		return array();
	}

	/**
	 * Define schema for delivery extension data.
	 *
	 * @return array
	 */
	public function extend_checkout_schema() {
		return array(
			'wc4jp_delivery_date'      => array(
				'description' => __( 'Delivery date', 'woocommerce-for-japan' ),
				'type'        => array( 'string', 'null' ),
				'required'    => false,
			),
			'wc4jp_delivery_time_zone' => array(
				'description' => __( 'Delivery time zone', 'woocommerce-for-japan' ),
				'type'        => array( 'string', 'null' ),
				'required'    => false,
			),
		);
	}

	/**
	 * Register scripts for the frontend.
	 */
	public function register_block_frontend_scripts() {
		$script_path       = '/assets/js/build/blocks/delivery-block-frontend.js';
		$script_url        = plugins_url( $script_path, JP4WC_PLUGIN_FILE );
		$script_asset_path = dirname( JP4WC_PLUGIN_FILE ) . '/assets/js/build/blocks/delivery-block-frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( dirname( JP4WC_PLUGIN_FILE ) . $script_path ),
			);

		wp_register_script(
			'jp4wc-delivery-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Register and enqueue CSS.
		$style_path = '/assets/js/build/blocks/delivery-block-frontend.css';
		$style_url  = plugins_url( $style_path, JP4WC_PLUGIN_FILE );
		wp_register_style(
			'jp4wc-delivery-block-frontend',
			$style_url,
			array(),
			$script_asset['version']
		);
		wp_enqueue_style( 'jp4wc-delivery-block-frontend' );

		wp_set_script_translations(
			'jp4wc-delivery-block-frontend',
			'woocommerce-for-japan',
			plugin_dir_path( JP4WC_PLUGIN_FILE ) . 'i18n'
		);

		// Enqueue the script data.
		wp_localize_script(
			'jp4wc-delivery-block-frontend',
			'jp4wcDeliveryData',
			$this->get_script_data()
		);
	}

	/**
	 * Register scripts for the editor.
	 */
	public function register_block_editor_scripts() {
		$script_path       = '/assets/js/build/blocks/delivery-block-editor.js';
		$script_url        = plugins_url( $script_path, JP4WC_PLUGIN_FILE );
		$script_asset_path = dirname( JP4WC_PLUGIN_FILE ) . '/assets/js/build/blocks/delivery-block-editor.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( dirname( JP4WC_PLUGIN_FILE ) . $script_path ),
			);

		wp_register_script(
			'jp4wc-delivery-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Enqueue the script data for editor.
		wp_localize_script(
			'jp4wc-delivery-block-editor',
			'jp4wcDeliveryData',
			$this->get_script_data()
		);

		wp_set_script_translations(
			'jp4wc-delivery-block-editor',
			'woocommerce-for-japan',
			plugin_dir_path( JP4WC_PLUGIN_FILE ) . 'i18n'
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'jp4wc-delivery-block-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'jp4wc-delivery-block-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$delivery        = new JP4WC_Delivery();
		$setting_methods = array(
			'delivery-date',
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
			'unspecified-time',
			'date-format',
			'day-of-week',
			'delivery-date-required',
			'delivery-time-zone-required',
		);

		$settings = array();
		foreach ( $setting_methods as $setting_method ) {
			$settings[ str_replace( '-', '_', $setting_method ) ] = get_option( 'wc4jp-' . $setting_method );
		}

		// Get delivery dates.
		$delivery_dates = $this->get_delivery_dates( $settings );

		// Get time zones.
		$time_zones = $this->get_time_zones( $settings );

		return array(
			'settings'       => $settings,
			'deliveryDates'  => $delivery_dates,
			'timeZones'      => $time_zones,
			'isDateEnabled'  => '1' === get_option( 'wc4jp-delivery-date' ),
			'isTimeEnabled'  => '1' === get_option( 'wc4jp-delivery-time-zone' ),
			'isDateRequired' => '1' === get_option( 'wc4jp-delivery-date-required' ),
			'isTimeRequired' => '1' === get_option( 'wc4jp-delivery-time-zone-required' ),
		);
	}

	/**
	 * Get available delivery dates.
	 *
	 * @param array $settings Delivery settings.
	 * @return array
	 */
	private function get_delivery_dates( $settings ) {
		$delivery           = new JP4WC_Delivery();
		$today              = $delivery->jp4wc_set_by_delivery_deadline( $settings['delivery_deadline'] );
		$delivery_start_day = $delivery->jp4wc_get_delivery_start_day_by_holiday( $today, $this->convert_settings_keys( $settings ) );
		$start_day          = $delivery->jp4wc_get_earliest_shipping_date( $delivery_start_day );

		if ( isset( $settings['start_date'] ) ) {
			$start_day = date_i18n( 'Y-m-d', strtotime( $start_day . ' ' . $settings['start_date'] . ' day' ) );
		}

		$dates = array();

		// Add unspecified option if not required.
		if ( '1' !== $settings['delivery_date_required'] ) {
			$dates[] = array(
				'value' => '0',
				'label' => $settings['unspecified_date'],
			);
		}

		$week = array(
			__( 'Sun', 'woocommerce-for-japan' ),
			__( 'Mon', 'woocommerce-for-japan' ),
			__( 'Tue', 'woocommerce-for-japan' ),
			__( 'Wed', 'woocommerce-for-japan' ),
			__( 'Thr', 'woocommerce-for-japan' ),
			__( 'Fri', 'woocommerce-for-japan' ),
			__( 'Sat', 'woocommerce-for-japan' ),
		);

		for ( $i = 0; $i <= $settings['reception_period']; $i++ ) {
			$start_day_timestamp = strtotime( $start_day );
			$value_date          = get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', $start_day_timestamp ), 'Y-m-d' );
			$display_date        = get_date_from_gmt( date_i18n( 'Y-m-d H:i:s', $start_day_timestamp ), __( 'Y/m/d', 'woocommerce-for-japan' ) );

			if ( $settings['day_of_week'] ) {
				$week_name = $week[ date_i18n( 'w', $start_day_timestamp ) ];
				/* translators: %s: Week name */
				$display_date = $display_date . sprintf( __( '(%s)', 'woocommerce-for-japan' ), $week_name );
			}

			$dates[] = array(
				'value' => $value_date,
				'label' => $display_date,
			);

			$start_day = date_i18n( 'Y-m-d', strtotime( $start_day . ' 1 day' ) );
		}

		return $dates;
	}

	/**
	 * Get available time zones.
	 *
	 * @param array $settings Delivery settings.
	 * @return array
	 */
	private function get_time_zones( $settings ) {
		$time_zone_setting = get_option( 'wc4jp_time_zone_details' );
		$time_zones        = array();

		// Add unspecified option if not required.
		if ( '1' !== $settings['delivery_time_zone_required'] ) {
			$time_zones[] = array(
				'value' => '0',
				'label' => $settings['unspecified_time'],
			);
		}

		if ( is_array( $time_zone_setting ) ) {
			foreach ( $time_zone_setting as $time_zone ) {
				$time_zones[] = array(
					'value' => $time_zone['start_time'] . '-' . $time_zone['end_time'],
					'label' => $time_zone['start_time'] . __( '-', 'woocommerce-for-japan' ) . $time_zone['end_time'],
				);
			}
		}

		return $time_zones;
	}

	/**
	 * Convert settings array keys from underscore to hyphen format.
	 *
	 * @param array $settings Settings array.
	 * @return array
	 */
	private function convert_settings_keys( $settings ) {
		$converted = array();
		foreach ( $settings as $key => $value ) {
			$converted[ str_replace( '_', '-', $key ) ] = $value;
		}
		return $converted;
	}
}
