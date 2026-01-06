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
 * Uses WooCommerce Additional Checkout Fields API (no custom React components needed).
 */
class JP4WC_Delivery_Blocks_Integration implements IntegrationInterface {

	/**
	 * Prevent duplicate initialization.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Prevent duplicate field registration.
	 *
	 * @var bool
	 */
	private $fields_registered = false;

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
	 * NOTE: Field registration happens earlier in woocommerce_init hook (see class-jp4wc.php).
	 */
	public function initialize() {
		// Prevent duplicate initialization.
		if ( $this->initialized ) {
			$this->log_info( 'Already initialized - skipping' );
			return;
		}
		$this->initialized = true;

		// Check if Additional Checkout Fields API is available (WooCommerce 9.3+).
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			$this->log_info( 'Additional Checkout Fields API not available - skipping Block integration' );
			return;
		}

		// DO NOT register fields here - they are registered earlier in woocommerce_init hook.
		// This ensures fields are available to the frontend before blocks are initialized.

		// Hide WooCommerce's default display of additional fields (we use our own).
		add_filter( 'woocommerce_order_get_formatted_meta_data', array( $this, 'hide_additional_fields_from_order_meta' ), 10, 2 );
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array();
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}

	/**
	 * Register additional checkout fields using WooCommerce Additional Checkout Fields API.
	 * This automatically creates the UI in the checkout block.
	 */
	public function register_checkout_fields() {
		// Prevent duplicate registration.
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			$this->log_info( 'ERROR: woocommerce_register_additional_checkout_field function not found' );
			return;
		}

		// CRITICAL: Check if woocommerce_blocks_loaded has run.
		// If not, the CheckoutFields class won't be available yet.
		$blocks_loaded = did_action( 'woocommerce_blocks_loaded' );
		if ( ! $blocks_loaded ) {
			$this->log_info( 'Blocks not loaded yet - adding hook to woocommerce_blocks_loaded' );
			add_action(
				'woocommerce_blocks_loaded',
				array( $this, 'register_checkout_fields' ),
				10
			);
			return;
		}

		// Verify CheckoutFields class is available.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) ) {
			$this->log_info( 'ERROR: CheckoutFields class not found even after woocommerce_blocks_loaded' );
			return;
		}

		// Verify Package container is available.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			$this->log_info( 'ERROR: Package class not found' );
			return;
		}

		// Get delivery date options.
		$delivery_dates = $this->get_delivery_date_options();
		$time_zones     = $this->get_time_zone_options();

		// Register delivery date field as select.
		if ( get_option( 'wc4jp-delivery-date' ) && ! empty( $delivery_dates ) ) {
			try {
				woocommerce_register_additional_checkout_field(
					array(
						'id'            => 'jp4wc/delivery-date',
						'label'         => __( 'Preferred delivery date', 'woocommerce-for-japan' ),
						'location'      => 'order',
						'type'          => 'select',
						'options'       => $delivery_dates,
						'required'      => get_option( 'wc4jp-delivery-date-required' ) === '1',
						'show_in_order' => true,
						'attributes'    => array(
							'autoComplete' => 'off',
						),
					)
				);
			} catch ( \Exception $e ) {
				$this->log_info( 'ERROR registering delivery date field: ' . $e->getMessage() );
			}
		} else {
			$this->log_info( 'Delivery date field NOT registered. Option: ' . get_option( 'wc4jp-delivery-date' ) . ', Dates: ' . count( $delivery_dates ) );
		}

		// Register delivery time field as select.
		if ( get_option( 'wc4jp-delivery-time-zone' ) && ! empty( $time_zones ) ) {
			try {
				woocommerce_register_additional_checkout_field(
					array(
						'id'            => 'jp4wc/delivery-time',
						'label'         => __( 'Delivery Time Zone', 'woocommerce-for-japan' ),
						'location'      => 'order',
						'type'          => 'select',
						'options'       => $time_zones,
						'required'      => get_option( 'wc4jp-delivery-time-zone-required' ) === '1',
						'show_in_order' => true,
						'attributes'    => array(
							'autoComplete' => 'off',
						),
					)
				);
			} catch ( \Exception $e ) {
				$this->log_info( 'ERROR registering delivery time field: ' . $e->getMessage() );
			}
		} else {
			$this->log_info( 'Delivery time field NOT registered. Option: ' . get_option( 'wc4jp-delivery-time-zone' ) . ', Zones: ' . count( $time_zones ) );
		}

		// Flag already set at the beginning of this method.
		$this->fields_registered = true;
	}

	/**
	 * Validate delivery fields on checkout for Store API.
	 * Note: This hook is called during total calculation, not just final checkout.
	 * We should NOT throw exceptions here as it causes 500 errors during intermediate updates.
	 *
	 * @param WC_Order        $order   Order object.
	 * @param WP_REST_Request $request Request object.
	 */
	public function validate_on_checkout( $order, $request ) {

		// Get additional_fields from request.
		$additional_fields = $request->get_param( 'additional_fields' );

		if ( ! is_array( $additional_fields ) ) {
			return;
		}
	}

	/**
	 * Validate additional field value.
	 * NOTE: This is called during total calculations too, not just final checkout.
	 * We need to check if this is a final checkout submission before validating.
	 *
	 * @param bool   $is_valid Whether the field is valid.
	 * @param string $key      The field key.
	 * @param mixed  $value    The field value.
	 * @return bool|WP_Error True if valid, WP_Error if not.
	 */
	public function validate_additional_field( $is_valid, $key, $value ) {
		// Check if this is a calc_totals request.
		if ( isset( $_GET['__experimental_calc_totals'] ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['_locale'] ) ) ) {
			$this->log_info( 'Skipping validation - this is a calc_totals request' );
			return $is_valid;
		}

		// Validate delivery date if required.
		if ( 'jp4wc/delivery-date' === $key ) {
			$is_required = get_option( 'wc4jp-delivery-date-required' ) === '1';

			if ( $is_required ) {
				// Check if value is null, empty string, or '0'.
				if ( is_null( $value ) || '' === $value || '0' === $value ) {
					$this->log_info( 'Delivery date validation FAILED - returning WP_Error' );
					return new WP_Error(
						'invalid_delivery_date',
						__( 'Please select a delivery date.', 'woocommerce-for-japan' )
					);
				}
			}
		}

		// Validate delivery time if required.
		if ( 'jp4wc/delivery-time' === $key ) {
			$is_required = get_option( 'wc4jp-delivery-time-zone-required' ) === '1';

			if ( $is_required ) {
				// Check if value is null, empty string, or '0'.
				if ( is_null( $value ) || '' === $value || '0' === $value ) {
					return new WP_Error(
						'invalid_delivery_time',
						__( 'Please select a delivery time zone.', 'woocommerce-for-japan' )
					);
				}
			}
		}
		return $is_valid;
	}

	/**
	 * Get delivery date options in the format required by Additional Checkout Fields API.
	 *
	 * @return array Array of options with 'value' and 'label' keys.
	 */
	private function get_delivery_date_options() {
		$options = array();
		if ( get_option( 'wc4jp-delivery-date-required' ) !== '1' ) {
			$unspecified_date_label = get_option( 'wc4jp-unspecified-date' );
			if ( empty( $unspecified_date_label ) ) {
				$unspecified_date_label = __( 'Not specified', 'woocommerce-for-japan' );
			}
			$options[] = array(
				'value' => '0',
				'label' => $unspecified_date_label,
			);
		}

		// Get delivery settings.
		$delivery_deadline  = get_option( 'wc4jp-delivery-deadline' );
		$start_date_offset  = get_option( 'wc4jp-start-date' ) ? get_option( 'wc4jp-start-date' ) : 0;
		$reception_period   = get_option( 'wc4jp-reception-period' ) ? get_option( 'wc4jp-reception-period' ) : 7;
		$holiday_start_date = get_option( 'wc4jp-holiday-start-date' );
		$holiday_end_date   = get_option( 'wc4jp-holiday-end-date' );
		$show_day_of_week   = get_option( 'wc4jp-day-of-week' );

		// Calculate start date.
		$today     = $this->get_today_by_deadline( $delivery_deadline );
		$start_day = $this->get_delivery_start_day_by_holiday( $today, $holiday_start_date, $holiday_end_date );
		$start_day = $this->get_earliest_shipping_date( $start_day );

		if ( $start_date_offset > 0 ) {
			$start_day = date_i18n( 'Y-m-d', strtotime( $start_day . ' +' . $start_date_offset . ' days' ) );
		}

		// Week names.
		$week = array(
			__( 'Sun', 'woocommerce-for-japan' ),
			__( 'Mon', 'woocommerce-for-japan' ),
			__( 'Tue', 'woocommerce-for-japan' ),
			__( 'Wed', 'woocommerce-for-japan' ),
			__( 'Thr', 'woocommerce-for-japan' ),
			__( 'Fri', 'woocommerce-for-japan' ),
			__( 'Sat', 'woocommerce-for-japan' ),
		);

				// Generate date options.
		for ( $i = 0; $i <= $reception_period; $i++ ) {
			$timestamp    = strtotime( $start_day );
			$value_date   = date_i18n( 'Y-m-d', $timestamp );
			$display_date = date_i18n( __( 'Y/m/d', 'woocommerce-for-japan' ), $timestamp );

			if ( $show_day_of_week ) {
				$week_name = $week[ date_i18n( 'w', $timestamp ) ];
				// translators: %s: The day of the week (e.g., Mon, Tue, Wed).
				$display_date = $display_date . sprintf( __( '(%s)', 'woocommerce-for-japan' ), $week_name );
			}

			$options[] = array(
				'value' => $value_date,
				'label' => $display_date,
			);

			$start_day = date_i18n( 'Y-m-d', strtotime( $start_day . ' +1 day' ) );
		}

		return $options;
	}

	/**
	 * Get time zone options in the format required by Additional Checkout Fields API.
	 *
	 * @return array Array of options with 'value' and 'label' keys.
	 */
	private function get_time_zone_options() {
		$options           = array();
		$time_zone_setting = get_option( 'wc4jp_time_zone_details' );

		if ( empty( $time_zone_setting ) || ! is_array( $time_zone_setting ) ) {
			return $options;
		}

		// Add unspecified option if not required.
		if ( get_option( 'wc4jp-delivery-time-zone-required' ) !== '1' ) {
			$unspecified_time_label = get_option( 'wc4jp-unspecified-time' );
			if ( empty( $unspecified_time_label ) ) {
				$unspecified_time_label = __( 'Not specified', 'woocommerce-for-japan' );
			}
			$options[] = array(
				'value' => '0',
				'label' => $unspecified_time_label,
			);
		}

		// Add time zone options.
		foreach ( $time_zone_setting as $time_zone ) {
			if ( ! isset( $time_zone['start_time'] ) || ! isset( $time_zone['end_time'] ) ) {
				continue;
			}

			$value = $time_zone['start_time'] . '-' . $time_zone['end_time'];
			$label = $time_zone['start_time'] . __( '-', 'woocommerce-for-japan' ) . $time_zone['end_time'];

			$options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}

		return $options;
	}

	/**
	 * Calculate today based on delivery deadline.
	 *
	 * @param string $delivery_deadline Delivery deadline time.
	 * @return string Today's date in Y-m-d format.
	 */
	private function get_today_by_deadline( $delivery_deadline ) {
		$now = date_i18n( 'Y-m-d H:i:s' );
		if ( strtotime( $now ) > strtotime( $delivery_deadline ) ) {
			return date_i18n( 'Y-m-d', strtotime( '+1 day' ) );
		}
		return date_i18n( 'Y-m-d' );
	}

	/**
	 * Calculate delivery start day considering holidays.
	 *
	 * @param string $today             Today's date.
	 * @param string $holiday_start_date Holiday start date.
	 * @param string $holiday_end_date   Holiday end date.
	 * @return string Delivery start date in Y-m-d format.
	 */
	private function get_delivery_start_day_by_holiday( $today, $holiday_start_date, $holiday_end_date ) {
		if (
			! empty( $holiday_start_date ) &&
			! empty( $holiday_end_date ) &&
			strtotime( $today ) >= strtotime( $holiday_start_date ) &&
			strtotime( $today ) <= strtotime( $holiday_end_date )
		) {
			return date_i18n( 'Y-m-d', strtotime( $holiday_end_date . ' +1 day' ) );
		}
		return $today;
	}

	/**
	 * Get earliest shipping date considering prohibited shipping days.
	 *
	 * @param string $start_date Starting date.
	 * @return string Earliest shipping date in Y-m-d format.
	 */
	private function get_earliest_shipping_date( $start_date ) {
		$weekday_options = array(
			'0' => 'no-sun',
			'1' => 'no-mon',
			'2' => 'no-tue',
			'3' => 'no-wed',
			'4' => 'no-thu',
			'5' => 'no-fri',
			'6' => 'no-sat',
		);

		$no_ship_weekdays = array();
		foreach ( $weekday_options as $key => $value ) {
			if ( get_option( 'wc4jp-' . $value ) ) {
				$no_ship_weekdays[] = intval( $key );
			}
		}

		if ( empty( $no_ship_weekdays ) ) {
			return $start_date;
		}

		$start_timestamp = strtotime( $start_date );
		$days_to_add     = 0;

		while ( true ) {
			$current_day = date_i18n( 'w', strtotime( "+$days_to_add days", $start_timestamp ) );
			if ( ! in_array( intval( $current_day ), $no_ship_weekdays, true ) ) {
				break;
			}
			++$days_to_add;
		}

		return date_i18n( 'Y-m-d', strtotime( "+$days_to_add days", $start_timestamp ) );
	}

	/**
	 * Hide additional checkout fields from WooCommerce's default order meta display.
	 * We use our own display logic instead.
	 *
	 * @param array    $formatted_meta Formatted meta data.
	 * @param WC_Order $order Order object.
	 * @return array Modified formatted meta data.
	 */
	public function hide_additional_fields_from_order_meta( $formatted_meta, $order ) {
		$fields_to_hide = array(
			'_wc_other/jp4wc/delivery-date',
			'_wc_other/jp4wc/delivery-time',
		);

		foreach ( $formatted_meta as $key => $meta ) {
			if ( in_array( $meta->key, $fields_to_hide, true ) ) {
				unset( $formatted_meta[ $key ] );
			}
		}

		return $formatted_meta;
	}

	/**
	 * Log informational message.
	 *
	 * @param string $message Message to log.
	 */
	private function log_info( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->info(
				'[JP4WC Delivery Blocks] ' . $message,
				array( 'source' => 'jp4wc_delivery_block' )
			);
		}
	}
}
