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
		// Register fields immediately when integration is initialized.
		// This is called during woocommerce_blocks_checkout_block_registration which is early enough.
		$this->register_checkout_fields();

		add_filter( 'woocommerce_set_additional_field_value', array( $this, 'set_additional_field_value' ), 10, 4 );
		add_filter( 'woocommerce_validate_additional_field', array( $this, 'validate_additional_field' ), 10, 3 );
		// Use lower priority to ensure this runs after WooCommerce processes additional_fields.
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'save_to_order_meta' ), 100, 2 );
		// Also hook into checkout order created to catch any data.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_from_session' ), 10, 2 );
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
		$this->log_info( 'register_checkout_fields called' );

		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			$this->log_info( 'ERROR: woocommerce_register_additional_checkout_field function not found' );
			return;
		}

		// Get delivery date options.
		$delivery_dates = $this->get_delivery_date_options();
		$time_zones     = $this->get_time_zone_options();

		$this->log_info( 'Delivery dates count: ' . count( $delivery_dates ) );
		$this->log_info( 'Time zones count: ' . count( $time_zones ) );
		$this->log_info( 'Delivery date option: ' . get_option( 'wc4jp-delivery-date' ) );
		$this->log_info( 'Delivery time option: ' . get_option( 'wc4jp-delivery-time-zone' ) );

		// Register delivery date field as select.
		if ( get_option( 'wc4jp-delivery-date' ) && ! empty( $delivery_dates ) ) {
			$result = woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'namespace1/delivery-date',
					'label'         => __( 'Preferred delivery date', 'woocommerce-for-japan' ),
					'location'      => 'order',
					'type'          => 'select',
					'options'       => $delivery_dates,
					'required'      => get_option( 'wc4jp-delivery-date-required' ) === '1',
					'show_in_order' => true,
				)
			);

			$this->log_info( 'Delivery date field registration result: ' . wp_json_encode( $result ) );
			$this->log_info( 'Delivery date field registered with ' . count( $delivery_dates ) . ' options' );
		} else {
			$this->log_info( 'Delivery date field NOT registered. Option: ' . get_option( 'wc4jp-delivery-date' ) . ', Dates: ' . count( $delivery_dates ) );
		}

		// Register delivery time field as select.
		if ( get_option( 'wc4jp-delivery-time-zone' ) && ! empty( $time_zones ) ) {
			$result = woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'namespace1/delivery-time',
					'label'         => __( 'Delivery Time Zone', 'woocommerce-for-japan' ),
					'location'      => 'order',
					'type'          => 'select',
					'options'       => $time_zones,
					'required'      => get_option( 'wc4jp-delivery-time-zone-required' ) === '1',
					'show_in_order' => false,
				)
			);

			$this->log_info( 'Delivery time field registration result: ' . wp_json_encode( $result ) );
			$this->log_info( 'Delivery time field registered with ' . count( $time_zones ) . ' options' );
		} else {
			$this->log_info( 'Delivery time field NOT registered. Option: ' . get_option( 'wc4jp-delivery-time-zone' ) . ', Zones: ' . count( $time_zones ) );
		}
	}

	/**
	 * Filter to set additional field value before saving to order.
	 *
	 * @param mixed  $value The value to set.
	 * @param string $key   The field key.
	 * @param string $group The field group.
	 * @param object $order The order object.
	 * @return mixed The filtered value.
	 */
	public function set_additional_field_value( $value, $key, $group, $order ) {
		$this->log_info( 'set_additional_field_value called - Key: ' . $key . ', Value: ' . $value . ', Group: ' . $group );

		// Handle delivery date formatting.
		if ( 'namespace1/delivery-date' === $key && ! empty( $value ) && '0' !== $value ) {
			// Apply date formatting if set.
			if ( get_option( 'wc4jp-date-format' ) ) {
				$date_timestamp = strtotime( $value );
				$formatted_date = date_i18n( get_option( 'wc4jp-date-format' ), $date_timestamp );
				$order->update_meta_data( 'wc4jp-delivery-date', $formatted_date );
				$this->log_info( 'Set delivery date meta: ' . $formatted_date . ' (Order ID: ' . $order->get_id() . ')' );
			} else {
				$order->update_meta_data( 'wc4jp-delivery-date', $value );
				$this->log_info( 'Set delivery date meta: ' . $value . ' (Order ID: ' . $order->get_id() . ')' );
			}
			$order->save();
		}

		// Handle delivery time.
		if ( 'namespace1/delivery-time' === $key && ! empty( $value ) && '0' !== $value ) {
			$order->update_meta_data( 'wc4jp-delivery-time-zone', $value );
			$this->log_info( 'Set delivery time meta: ' . $value . ' (Order ID: ' . $order->get_id() . ')' );
			$order->save();
		}

		return $value;
	}

	/**
	 * Save additional fields to order meta using Store API hook.
	 *
	 * @param WC_Order        $order   Order object.
	 * @param WP_REST_Request $request Request object.
	 */
	public function save_to_order_meta( $order, $request ) {
		$this->log_info( 'save_to_order_meta called for Order ID: ' . $order->get_id() );

		// Get additional_fields from request.
		$additional_fields = $request->get_param( 'additional_fields' );
		$this->log_info( 'Additional fields from request: ' . wp_json_encode( $additional_fields ) );

		// Check if data was already saved by set_additional_field_value filter.
		$existing_date = $order->get_meta( 'wc4jp-delivery-date' );
		$existing_time = $order->get_meta( 'wc4jp-delivery-time-zone' );

		if ( ! empty( $existing_date ) || ! empty( $existing_time ) ) {
			$this->log_info( 'Data already saved via filter: Date=' . $existing_date . ', Time=' . $existing_time );
			return;
		}

		// Process additional_fields if available.
		if ( ! empty( $additional_fields ) && is_array( $additional_fields ) ) {
			$this->log_info( 'Processing additional fields: ' . wp_json_encode( $additional_fields ) );

			// Save delivery date.
			if ( isset( $additional_fields['namespace1/delivery-date'] ) ) {
				$delivery_date = sanitize_text_field( $additional_fields['namespace1/delivery-date'] );

				if ( ! empty( $delivery_date ) && '0' !== $delivery_date ) {
					if ( get_option( 'wc4jp-date-format' ) ) {
						$date_timestamp = strtotime( $delivery_date );
						$formatted_date = date_i18n( get_option( 'wc4jp-date-format' ), $date_timestamp );
						$order->update_meta_data( 'wc4jp-delivery-date', $formatted_date );
						$this->log_info( 'Saved delivery date: ' . $formatted_date );
					} else {
						$order->update_meta_data( 'wc4jp-delivery-date', $delivery_date );
						$this->log_info( 'Saved delivery date: ' . $delivery_date );
					}
				}
			}

			// Save delivery time.
			if ( isset( $additional_fields['namespace1/delivery-time'] ) ) {
				$delivery_time = sanitize_text_field( $additional_fields['namespace1/delivery-time'] );

				if ( ! empty( $delivery_time ) && '0' !== $delivery_time ) {
					$order->update_meta_data( 'wc4jp-delivery-time-zone', $delivery_time );
					$this->log_info( 'Saved delivery time: ' . $delivery_time );
				}
			}

			$order->save();
			$this->log_info( 'Order saved with delivery data from additional_fields' );
		} else {
			$this->log_info( 'No additional_fields data available in request' );
		}
	}

	/**
	 * Save delivery data from checkout session when order is created.
	 * This is a fallback for when additional_fields is not populated.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $data  Checkout data.
	 */
	public function save_from_session( $order, $data ) {
		$this->log_info( 'save_from_session called for Order ID: ' . $order->get_id() );

		// Check if data was already saved.
		$existing_date = $order->get_meta( 'wc4jp-delivery-date' );
		$existing_time = $order->get_meta( 'wc4jp-delivery-time-zone' );

		if ( ! empty( $existing_date ) || ! empty( $existing_time ) ) {
			$this->log_info( 'Data already exists, skipping: Date=' . $existing_date . ', Time=' . $existing_time );
			return;
		}

		$this->log_info( 'No existing data, attempting to retrieve from checkout data' );
		$this->log_info( 'Checkout data keys: ' . wp_json_encode( array_keys( $data ) ) );

		// Log full data for debugging.
		$this->log_info( 'Full checkout data: ' . wp_json_encode( $data ) );
	}

	/**
	 * Validate additional field value.
	 *
	 * @param bool   $is_valid Whether the field is valid.
	 * @param string $key      The field key.
	 * @param mixed  $value    The field value.
	 * @return bool|WP_Error True if valid, WP_Error if not.
	 */
	public function validate_additional_field( $is_valid, $key, $value ) {
		$this->log_info( 'validate_additional_field called - Key: ' . $key . ', Value: ' . $value );

		// Validate delivery date if required.
		if ( 'namespace1/delivery-date' === $key ) {
			if ( get_option( 'wc4jp-delivery-date-required' ) === '1' ) {
				if ( empty( $value ) || '0' === $value ) {
					return new WP_Error(
						'invalid_delivery_date',
						__( 'Please select a delivery date.', 'woocommerce-for-japan' )
					);
				}
			}
			$this->log_info( 'Validated delivery date: ' . $value );
		}

		// Validate delivery time if required.
		if ( 'namespace1/delivery-time' === $key ) {
			if ( get_option( 'wc4jp-delivery-time-zone-required' ) === '1' ) {
				if ( empty( $value ) || '0' === $value ) {
					return new WP_Error(
						'invalid_delivery_time',
						__( 'Please select a delivery time zone.', 'woocommerce-for-japan' )
					);
				}
			}
			$this->log_info( 'Validated delivery time: ' . $value );
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

		// Add unspecified option if not required.
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
				// translators: %s is the day of the week (e.g., Mon, Tue, Wed).
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
	 * Log informational message.
	 *
	 * @param string $message Message to log.
	 */
	private function log_info( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->info(
				'[JP4WC Delivery Blocks1] ' . $message,
				array( 'source' => 'jp4wc_delivery_block' )
			);
		}
	}
}
