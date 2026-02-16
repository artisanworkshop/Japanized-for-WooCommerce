<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.8.2
 * @package     Admin Screen
 * @author      ArtisanWorkshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for handling delivery-related functionality in WooCommerce for Japan.
 *
 * This class manages Japanese-specific delivery options and settings for WooCommerce,
 * including shipping date selection, delivery time slots, and address validation
 * specific to Japanese postal addresses.
 *
 * @package Japanized For WooCommerce
 * @since 1.0.0
 */
class JP4WC_Delivery {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Show delivery date and time at checkout page.
		add_action( 'woocommerce_before_order_notes', array( $this, 'delivery_date_designation' ), 10 );
		// Save delivery date and time values to order.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_delivery_data_to_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );
		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'jp4wc_delivery_posted_data' ) );
		// Validate delivery date and time fields at checkout.
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_date_time_checkout_field' ), 10, 2 );
		// Block checkout validation.
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'validate_delivery_fields_block_checkout' ), 10, 2 );
		// Show on order detail at thanks page (frontend).
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'frontend_order_timedate' ) );
		// Show on order detail email (frontend).
		add_filter( 'woocommerce_email_order_meta', array( $this, 'email_order_delivery_details' ), 10, 3 );
		// Shop Order functions.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'shop_order_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_shop_order_columns' ), 2 );
		// display in Order meta box ship date and time (admin).
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 0, 1 );

		add_filter( 'woocommerce_payment_successful_result', array( $this, 'jp4wc_delivery_check_data' ), 10, 1 );
	}

	/**
	 * Delivery date designation.
	 *
	 * @return void
	 */
	public function delivery_date_designation() {
		// Skip if using Checkout Block (additional fields are handled by the block integration).
		if ( function_exists( 'jp4wc_is_using_checkout_blocks' ) && jp4wc_is_using_checkout_blocks() ) {
			return;
		}

		// Use static variable to prevent multiple displays.
		static $displayed = false;

		// Return if already displayed.
		if ( $displayed ) {
			return;
		}

		// Hide for virtual products only.
		$virtual_cnt = 0;
		$product_cnt = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product->is_virtual() ) {
				++$virtual_cnt;
			}
			++$product_cnt;
		}
		if ( $product_cnt === $virtual_cnt ) {
			return;
		}

		// Display delivery date designation.
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
		);
		foreach ( $setting_methods as $setting_method ) {
			$setting[ $setting_method ] = get_option( 'wc4jp-' . $setting_method );
		}
		if ( $setting['delivery-date'] || $setting['delivery-time-zone'] ) {
			echo '<h3>' . esc_html__( 'Delivery request date and time', 'woocommerce-for-japan' ) . '</h3>';
		}
		$this->delivery_date_display( $setting );
		$this->delivery_time_display( $setting );

		// Mark as displayed.
		$displayed = true;
	}

	/**
	 * Display Delivery date select at Checkout.
	 *
	 * @param array $setting Delivery date setting.
	 */
	public function delivery_date_display( array $setting ) {
		if ( get_option( 'wc4jp-delivery-date' ) ) {
			// Set delivery date.
			$today = $this->jp4wc_set_by_delivery_deadline( $setting['delivery-deadline'] );
			// Get delivery start day by holiday settings.
			$delivery_start_day = $this->jp4wc_get_delivery_start_day_by_holiday( $today, $setting );
			// Set delivery start day.
			$start_day = $this->jp4wc_get_earliest_shipping_date( $delivery_start_day );
			if ( isset( $setting['start-date'] ) ) {
				$start_day = date_i18n( 'Y-m-d', strtotime( $start_day . ' ' . $setting['start-date'] . ' day' ) );
			}
			// Set Japanese Week name.
			$week = array(
				__( 'Sun', 'woocommerce-for-japan' ),
				__( 'Mon', 'woocommerce-for-japan' ),
				__( 'Tue', 'woocommerce-for-japan' ),
				__( 'Wed', 'woocommerce-for-japan' ),
				__( 'Thr', 'woocommerce-for-japan' ),
				__( 'Fri', 'woocommerce-for-japan' ),
				__( 'Sat', 'woocommerce-for-japan' ),
			);

			echo '<p class="form-row delivery-date" id="order_wc4jp_delivery_date_field">';
			echo '<label for="wc4jp_delivery_date" class="">' . esc_html__( 'Preferred delivery date', 'woocommerce-for-japan' ) . '</label>';
			echo '<select name="wc4jp_delivery_date" class="input-select" id="wc4jp_delivery_date">';
			if ( '1' !== get_option( 'wc4jp-delivery-date-required' ) ) {
				echo '<option value="0">' . esc_html( $setting['unspecified-date'] ) . '</option>';
			}
			for ( $i = 0; $i <= $setting['reception-period']; $i++ ) {
				$start_day_timestamp = strtotime( $start_day );
				$set_display_date    = date_i18n( 'Y-m-d h:i:s', $start_day_timestamp );
				$value_date[ $i ]    = get_date_from_gmt( $set_display_date, 'Y-m-d' );
				$display_date[ $i ]  = get_date_from_gmt( $set_display_date, __( 'Y/m/d', 'woocommerce-for-japan' ) );
				if ( $setting['day-of-week'] ) {
					$week_name = $week[ date_i18n( 'w', $start_day_timestamp ) ];
					/* translators: %s: The day of the week (e.g., Mon, Tue, Wed). */
					$display_date[ $i ] = $display_date[ $i ] . sprintf( __( '(%s)', 'woocommerce-for-japan' ), $week_name );
				}
				echo '<option value="' . esc_attr( $value_date[ $i ] ) . '">' . esc_html( $display_date[ $i ] ) . '</option>';
				$start_day = date_i18n( 'Y-m-d', strtotime( $start_day . ' 1 day' ) );
			}
			echo '</select>';
			echo '</p>';

			// after display delivery date select action hook.
			do_action( 'after_wc4jp_delivery_date', $setting, $start_day );
		}
	}

	/**
	 * Set delivery date based on delivery deadline.
	 *
	 * This function determines the effective "today" date based on whether
	 * the current time has passed the specified delivery deadline.
	 *
	 * @param string $settung_delivery_deadline The delivery deadline time.
	 * @return string The calculated today date in Y-m-d format.
	 */
	public function jp4wc_set_by_delivery_deadline( $settung_delivery_deadline ) {
		// Get current time.
		$now = date_i18n( 'Y-m-d H:i:s' );
		// Set today by delivery deadline.
		if ( strtotime( $now ) > strtotime( $settung_delivery_deadline ) ) {
			$today = date_i18n( 'Y-m-d', strtotime( '+1 day' ) );
		} else {
			$today = date_i18n( 'Y-m-d' );
		}
		return $today;
	}

	/**
	 * Calculate the delivery start day based on current date and settings.
	 *
	 * This function determines the appropriate delivery start date, taking into account
	 * holidays and other date restrictions specified in the settings.
	 *
	 * @param string $today   The current date string.
	 * @param array  $setting The delivery settings array.
	 * @return string The calculated delivery start date in Y-m-d format.
	 */
	public function jp4wc_get_delivery_start_day_by_holiday( $today, $setting ) {
		// Get delivery start day.
		$delivery_start_day = new DateTime( $today );
		if (
			isset( $setting['holiday-start-date'] ) &&
			isset( $setting['holiday-end-date'] ) &&
			strtotime( $today ) >= strtotime( $setting['holiday-start-date'] ) &&
			strtotime( $today ) <= strtotime( $setting['holiday-end-date'] )
		) {
			$delivery_start_day->setDate(
				substr( $setting['holiday-end-date'], 0, 4 ),
				substr( $setting['holiday-end-date'], 5, 2 ),
				substr( $setting['holiday-end-date'], 8, 2 )
			);
			$delivery_start_day->modify( '+1 day' );
		}
		return $delivery_start_day->format( 'Y-m-d' );
	}

	/**
	 * Calculate the earliest possible shipping date based on prohibited shipping days.
	 *
	 * This function determines the earliest available shipping date from a given start date,
	 * taking into account days of the week when shipping is not allowed.
	 *
	 * @param string $start_date The starting date from which to calculate (default: 'today').
	 * @return string The earliest possible shipping date in Y-m-d format.
	 */
	public function jp4wc_get_earliest_shipping_date( $start_date = 'today' ) {
		// Shipping prohibition day option setting (day of the week in "no-XXX" format).
		$weekday_options = array(
			'0' => 'no-sun', // Sunday.
			'1' => 'no-mon', // Monday.
			'2' => 'no-tue', // Tuesday.
			'3' => 'no-wed', // Wednesday.
			'4' => 'no-thu', // Thursday.
			'5' => 'no-fri', // Friday.
			'6' => 'no-sat', // Saturday.
		);

		$no_ship_weekdays = array();
		foreach ( $weekday_options as $key => $value ) {
			if ( get_option( 'wc4jp-' . $value ) ) {
				$no_ship_weekdays[] = intval( $key );
			}
		}

		// Convert a given start date to a timestamp.
		$start_timestamp = strtotime( $start_date );  // Supports 'today' and 'Y-m-d'.
		if ( false === $start_timestamp ) {
			return '無効な開始日です';
		}

		$days_to_add = 0;

		while ( true ) {
			// $start_timestamp に $daysToAdd 日を加算した日付の曜日を取得
			// date("w") は曜日を数字（0: 日, 1: 月, ..., 6: 土）で返す
			$current_day = date_i18n( 'w', strtotime( "+$days_to_add days", $start_timestamp ) );

			// If the current day is not included in the list of days on which shipments cannot be made, the loop ends.
			if ( ! in_array( intval( $current_day ), $no_ship_weekdays, true ) ) {
				break;
			}
			++$days_to_add;
		}

		// Calculate and format the available shipping date (e.g. "Y-m-d").
		$shipping_date = date_i18n( 'Y-m-d', strtotime( "+$days_to_add days", $start_timestamp ) );
		return $shipping_date;
	}

	/**
	 * Display Delivery time select at checkout
	 *
	 * @param array $setting Delivery time setting.
	 */
	public function delivery_time_display( $setting ) {
		$time_zone_setting = get_option( 'wc4jp_time_zone_details' );
		if ( get_option( 'wc4jp-delivery-time-zone' ) ) {
			echo '<p class="form-row delivery-time" id="order_wc4jp_delivery_time_field">';
			echo '<label for="wc4jp_delivery_time_zone" class="">' . esc_html__( 'Delivery Time Zone', 'woocommerce-for-japan' ) . '</label>';
			echo '<select name="wc4jp_delivery_time_zone" class="input-select" id="wc4jp_delivery_time_zone">';
			if ( get_option( 'wc4jp-delivery-time-zone-required' ) !== '1' ) {
				echo '<option value="0">' . esc_html( $setting['unspecified-time'] ) . '</option>';
			}
			$count_time_zone = count( $time_zone_setting );
			for ( $i = 0; $i <= $count_time_zone - 1; $i++ ) {
				echo '<option value="' . esc_attr( $time_zone_setting[ $i ]['start_time'] ) . '-' . esc_attr( $time_zone_setting[ $i ]['end_time'] ) . '">' . esc_html( $time_zone_setting[ $i ]['start_time'] ) . esc_html__( '-', 'woocommerce-for-japan' ) . esc_html( $time_zone_setting[ $i ]['end_time'] ) . '</option>';
			}
			echo '</select>';
			echo '</p>';
		}
	}

	/**
	 * Save delivery data to order during order creation
	 *
	 * This method is called during woocommerce_checkout_create_order hook,
	 * which ensures the data is saved even when guest creates an account during checkout.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $data  The posted checkout data.
	 * @return void
	 */
	public function save_delivery_data_to_order( $order, $data ) {
		// Check if this is a block checkout request.
		$is_block_checkout = did_action( 'woocommerce_store_api_checkout_update_order_from_request' ) > 0;

		// Skip if block checkout (handled by validate_delivery_fields_block_checkout).
		if ( $is_block_checkout ) {
			return;
		}

		// Process delivery date.
		if ( isset( $data['wc4jp_delivery_date'] ) ) {
			$date = apply_filters( 'wc4jp_delivery_date', $data['wc4jp_delivery_date'], $order->get_id() );
			if ( ! empty( $date ) && '0' !== $date ) {
				if ( get_option( 'wc4jp-date-format' ) ) {
					$date_timestamp = strtotime( $date );
					$formatted_date = date_i18n( get_option( 'wc4jp-date-format' ), $date_timestamp );
					$order->update_meta_data( 'wc4jp-delivery-date', esc_attr( htmlspecialchars( $formatted_date ) ) );
				} else {
					$order->update_meta_data( 'wc4jp-delivery-date', esc_attr( htmlspecialchars( $date ) ) );
				}
			}
		}

		// Process delivery time zone.
		if ( isset( $data['wc4jp_delivery_time_zone'] ) ) {
			$time = apply_filters( 'wc4jp_delivery_time_zone', $data['wc4jp_delivery_time_zone'], $order->get_id() );
			if ( ! empty( $time ) && '0' !== $time ) {
				$order->update_meta_data( 'wc4jp-delivery-time-zone', esc_attr( htmlspecialchars( $time ) ) );
			}
		}

		// Process tracking ship date.
		if ( isset( $data['wc4jp-tracking-ship-date'] ) ) {
			$ship_date = apply_filters( 'wc4jp_ship_date', $data['wc4jp-tracking-ship-date'], $order->get_id() );
			if ( ! empty( $ship_date ) && '0' !== $ship_date ) {
				$order->update_meta_data( 'wc4jp-tracking-ship-date', esc_attr( htmlspecialchars( $ship_date ) ) );
			}
		}
	}

	/**
	 * Helper: Update order meta on successful checkout submission
	 *
	 * @param int $order_id Order ID.
	 */
	public function update_order_meta( $order_id ) {

		// Check if this is a block checkout request.
		$is_block_checkout = did_action( 'woocommerce_store_api_checkout_update_order_from_request' ) > 0;

		// Skip if block checkout (handled by validate_delivery_fields_block_checkout).
		if ( $is_block_checkout ) {
			return;
		}

		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' )
		) {
			return;
		}

		$order = wc_get_order( $order_id );

		// Process delivery date.
		if ( isset( $_POST['wc4jp_delivery_date'] ) ) {
			$date = apply_filters( 'wc4jp_delivery_date', sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_date'] ) ), $order_id );
			if ( ! empty( $date ) && '0' !== $date ) {
				if ( get_option( 'wc4jp-date-format' ) ) {
					$date_timestamp = strtotime( $date );
					$formatted_date = date_i18n( get_option( 'wc4jp-date-format' ), $date_timestamp );
					$order->update_meta_data( 'wc4jp-delivery-date', esc_attr( htmlspecialchars( $formatted_date ) ) );
				} else {
					$order->update_meta_data( 'wc4jp-delivery-date', esc_attr( htmlspecialchars( $date ) ) );
				}
			} else {
				$order->delete_meta_data( 'wc4jp-delivery-date' );
			}
		}

		// Process delivery time zone.
		if ( isset( $_POST['wc4jp_delivery_time_zone'] ) ) {
			$time = apply_filters( 'wc4jp_delivery_time_zone', sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_time_zone'] ) ), $order_id );
			if ( ! empty( $time ) && '0' !== $time ) {
				$order->update_meta_data( 'wc4jp-delivery-time-zone', esc_attr( htmlspecialchars( $time ) ) );
			} else {
				$order->delete_meta_data( 'wc4jp-delivery-time-zone' );
			}
		}

		// Process tracking ship date.
		if ( isset( $_POST['wc4jp-tracking-ship-date'] ) ) {
			$ship_date = apply_filters( 'wc4jp_ship_date', sanitize_text_field( wp_unslash( $_POST['wc4jp-tracking-ship-date'] ) ), $order_id );
			if ( ! empty( $ship_date ) && '0' !== $ship_date ) {
				$order->update_meta_data( 'wc4jp-tracking-ship-date', esc_attr( htmlspecialchars( $ship_date ) ) );
			} else {
				$order->delete_meta_data( 'wc4jp-tracking-ship-date' );
			}
		}

		$order->save();
	}

	/**
	 * Capture delivery date and time from posted checkout data
	 *
	 * @param array $data Posted checkout data.
	 * @return array Modified posted checkout data.
	 */
	public function jp4wc_delivery_posted_data( $data ) {
		$date_value = '';
		$time_value = '';

		if ( isset( $_POST['wc4jp_delivery_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$date_value                  = sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_date'] ) );// phpcs:ignore WordPress.Security.NonceVerification
			$data['wc4jp_delivery_date'] = $date_value;
		}
		if ( isset( $_POST['wc4jp_delivery_time_zone'] ) ) {// phpcs:ignore WordPress.Security.NonceVerification
			$time_value                       = sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_time_zone'] ) );// phpcs:ignore WordPress.Security.NonceVerification
			$data['wc4jp_delivery_time_zone'] = $time_value;
		}

		return $data;
	}

	/**
	 * Validate delivery date and time fields at checkout
	 *
	 * Checks if delivery date is required and has been filled in by the customer.
	 * Adds an error if the field is required but empty.
	 *
	 * @param array    $fields The checkout fields.
	 * @param WP_Error $errors Validation errors.
	 * @return void
	 */
	public function validate_date_time_checkout_field( $fields, $errors ) {
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' )
		) {
			// Log nonce verification failure for debugging.
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->warning(
					'Delivery validation skipped: Nonce verification failed',
					array( 'source' => 'jp4wc_delivery_validation' )
				);
			}
			return;
		}

		// Check if we're dealing with virtual products only.
		$has_physical = false;
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product = $cart_item['data'];
				if ( ! $product->is_virtual() ) {
					$has_physical = true;
					break;
				}
			}
		}

		// Skip validation if only virtual products.
		if ( ! $has_physical ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->info(
					'Delivery validation skipped: Virtual products only',
					array( 'source' => 'jp4wc_delivery_validation' )
				);
			}
			return;
		}

		if ( get_option( 'wc4jp-delivery-date' ) && '1' === get_option( 'wc4jp-delivery-date-required' ) ) {
			// Check both $fields array and direct POST data.
			$date_value = isset( $fields['wc4jp_delivery_date'] ) ? $fields['wc4jp_delivery_date'] : '';

			// Fallback to POST if not in fields array.
			if ( empty( $date_value ) && isset( $_POST['wc4jp_delivery_date'] ) ) {
				$date_value = sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_date'] ) );
			}

			if ( empty( $date_value ) || '0' === $date_value ) {
				$errors->add( 'wc4jp_delivery_date_required', __( '"Desired delivery date" is a required field. Please enter it.', 'woocommerce-for-japan' ), array( 'status' => 400 ) );

				// Log validation failure.
				if ( function_exists( 'wc_get_logger' ) ) {
					$logger = wc_get_logger();
					$logger->warning(
						sprintf(
							'Delivery date validation failed. Fields array: %s, POST data: %s',
							isset( $fields['wc4jp_delivery_date'] ) ? 'exists' : 'missing',
							isset( $_POST['wc4jp_delivery_date'] ) ? 'exists' : 'missing'
						),
						array( 'source' => 'jp4wc_delivery_validation' )
					);
				}
			}
		}

		if ( get_option( 'wc4jp-delivery-time-zone' ) && '1' === get_option( 'wc4jp-delivery-time-zone-required' ) ) {
			// Check both $fields array and direct POST data.
			$time_value = isset( $fields['wc4jp_delivery_time_zone'] ) ? $fields['wc4jp_delivery_time_zone'] : '';

			// Fallback to POST if not in fields array.
			if ( empty( $time_value ) && isset( $_POST['wc4jp_delivery_time_zone'] ) ) {
				$time_value = sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_time_zone'] ) );
			}

			if ( empty( $time_value ) || '0' === $time_value ) {
				$errors->add( 'wc4jp_delivery_time_zone_required', __( '"Desired delivery time zone" is a required field. Please enter it.', 'woocommerce-for-japan' ), array( 'status' => 400 ) );

				// Log validation failure.
				if ( function_exists( 'wc_get_logger' ) ) {
					$logger = wc_get_logger();
					$logger->warning(
						sprintf(
							'Delivery time validation failed. Fields array: %s, POST data: %s',
							isset( $fields['wc4jp_delivery_time_zone'] ) ? 'exists' : 'missing',
							isset( $_POST['wc4jp_delivery_time_zone'] ) ? 'exists' : 'missing'
						),
						array( 'source' => 'jp4wc_delivery_validation' )
					);
				}
			}
		}
	}





	/**
	 * Validate delivery fields for block checkout
	 *
	 * NOTE: Validation for Checkout Block is now handled by class-jp4wc-delivery-blocks-integration.php
	 * This method is kept for backward compatibility but does nothing as validation is performed
	 * via the woocommerce_store_api_validate_additional_field filter hook.
	 *
	 * @param WC_Order        $order   Order object.
	 * @param WP_REST_Request $request Request object.
	 * @return void
	 */
	public function validate_delivery_fields_block_checkout( $order, $request ) {
		// Validation is now handled by JP4WC_Delivery_Blocks_Integration::validate_additional_field()
		// This method is intentionally left empty to avoid duplicate validation.
		return;
	}

	/**
	 * Frontend: Add date and timeslot to frontend order overview
	 *
	 * @param object $order WP_Order.
	 */
	public function frontend_order_timedate( $order ) {
		// Use static variable to prevent multiple displays.
		static $displayed_orders = array();

		$order_id = $order->get_id();

		// Return if already displayed for this order.
		if ( isset( $displayed_orders[ $order_id ] ) ) {
			return;
		}

		$has_date_or_time = $this->has_date_or_time( $order );
		if ( ! $has_date_or_time || ( isset( $has_date_or_time['is_block'] ) && $has_date_or_time['is_block'] ) ) {
			return;
		}

		// Mark as displayed.
		$displayed_orders[ $order_id ] = true;

		$this->display_date_and_time_zone( $order, true );
	}
	/**
	 * Helper: Display Date and Timeslot
	 *
	 * @param object $order WP_Order.
	 * @param bool   $show_title Display title.
	 * @param bool   $plain_text Display as plain text.
	 */
	public function display_date_and_time_zone( $order, $show_title = false, $plain_text = false ) {

		$date_time = $this->has_date_or_time( $order );

		if ( ! $date_time || ( isset( $date_time['is_block'] ) && $date_time['is_block'] ) ) {
			return;
		}
		if ( '0' === $date_time['date'] ) {
			$date_time['date'] = get_option( 'wc4jp-unspecified-date' );
		}
		if ( '0' === $date_time['time'] ) {
			$date_time['time'] = get_option( 'wc4jp-unspecified-time' );
		}
		$date_time['date'] = apply_filters( 'wc4jp_unspecified_date', $date_time['date'], $order );
		$date_time['time'] = apply_filters( 'wc4jp_unspecified_time', $date_time['time'], $order );
		$show_title        = apply_filters( 'wc4jp_show_title', $show_title, $date_time['date'], $date_time['time'], $order );

		$html = '';

		if ( $plain_text ) {

			$html = "\n\n==========\n\n";

			if ( $show_title ) {
				$html .= sprintf( "%s \n", strtoupper( apply_filters( 'wc4jp_delivery_details_text', __( 'Scheduled Delivery date and time', 'woocommerce-for-japan' ), $order ) ) );
			}

			if ( $date_time['date'] ) {
				$html .= sprintf( "\n%s: %s", apply_filters( 'wc4jp_delivery_date_text', __( 'Scheduled Delivery Date', 'woocommerce-for-japan' ), $order ), $date_time['date'] );
			}

			if ( $date_time['time'] ) {
				$html .= sprintf( "\n%s: %s", apply_filters( 'wc4jp_time_zone_text', __( 'Scheduled Time Zone', 'woocommerce-for-japan' ), $order ), $date_time['time'] );
			}

			$html .= "\n\n==========\n\n";

		} else {

			if ( $show_title ) {
				$html .= sprintf( '<h2>%s</h2>', apply_filters( 'wc4jp_delivery_details_text', __( 'Scheduled Delivery date and time', 'woocommerce-for-japan' ), $order ) );
			}

			if ( $date_time['date'] ) {
				$html .= sprintf( '<p class="jp4wc_date"><strong>%s</strong> <br>%s</p>', apply_filters( 'wc4jp_delivery_date_text', __( 'Scheduled Delivery Date', 'woocommerce-for-japan' ), $order ), $date_time['date'] );
			}

			if ( $date_time['time'] ) {
				$html .= sprintf( '<p class="jp4wc_time"><strong>%s</strong> <br>%s</p>', apply_filters( 'wc4jp_time_zone_text', __( 'Scheduled Time Zone', 'woocommerce-for-japan' ), $order ), $date_time['time'] );
			}
		}
		echo wp_kses_post( apply_filters( 'jp4wc_display_date_and_time_zone', $html, $date_time, $show_title ) );
	}

	/**
	 * Frontend: Add date and timeslot to order email
	 *
	 * @param object $order WP_Order.
	 * @param bool   $sent_to_admin Sent to admin.
	 * @param bool   $plain_text Plain text.
	 */
	public function email_order_delivery_details( $order, $sent_to_admin, $plain_text ) {
		$has_date_or_time = $this->has_date_or_time( $order );
		if ( ! $has_date_or_time || isset( $has_date_or_time['is_block'] ) ) {
			return;
		}

		if ( $plain_text ) {
			$this->display_date_and_time_zone( $order, true, true );
		} else {
			$this->display_date_and_time_zone( $order, true );
		}
	}

	/**
	 * Helper: Check if order has date or time
	 *
	 * @param object $order WP_Order.
	 * @return array|bool
	 */
	public function has_date_or_time( $order ) {
		$meta     = array(
			'date' => false,
			'time' => false,
		);
		$has_meta = false;
		$is_block = false;

		// Check both shortcode and Checkout Block meta keys for delivery date.
		$date = $order->get_meta( 'wc4jp-delivery-date', true );
		if ( empty( $date ) ) {
			// Fallback to Checkout Block meta key.
			$date = $order->get_meta( '_wc_other/jp4wc/delivery-date', true );
			if ( ! empty( $date ) ) {
				$is_block = true;
			}
		}

		// Check both shortcode and Checkout Block meta keys for delivery time.
		$time = $order->get_meta( 'wc4jp-delivery-time-zone', true );
		if ( empty( $time ) ) {
			// Fallback to Checkout Block meta key.
			$time = $order->get_meta( '_wc_other/jp4wc/delivery-time', true );
			if ( ! empty( $time ) ) {
				$is_block = true;
			}
		}

		if ( ( $date && '' !== $date ) ) {
			$meta['date'] = $date;
			$has_meta     = true;
		}

		if ( ( $time && '' !== $time ) ) {
			$meta['time'] = $time;
			$has_meta     = true;
		}

		if ( $is_block ) {
			$meta['is_block'] = true;
		}

		if ( $has_meta ) {
			return $meta;
		}

		return false;
	}

	/**
	 * Admin: Add Columns to orders tab
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function shop_order_columns( $columns ) {

		if ( get_option( 'wc4jp-delivery-date' ) || get_option( 'wc4jp-delivery-time-zone' ) ) {
			$columns['wc4jp_delivery'] = __( 'Delivery', 'woocommerce-for-japan' );
		}

		return $columns;
	}

	/**
	 * Admin: Output date and timeslot columns on orders tab
	 *
	 * @param string $column Column.
	 */
	public function render_shop_order_columns( $column ) {
		// Use static variable to prevent multiple displays.
		static $displayed_columns = array();

		global $post, $the_order;
		if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		$order_id = $the_order->get_id();

		// Create unique key for this order and column.
		$display_key = $order_id . '_' . $column;

		// Return if already displayed for this order and column.
		if ( isset( $displayed_columns[ $display_key ] ) ) {
			return;
		}

		switch ( $column ) {
			case 'wc4jp_delivery':
				$this->display_date_and_time_zone( $the_order );
				// Mark as displayed.
				$displayed_columns[ $display_key ] = true;

				break;
		}
	}
	/**
	 * Admin: Display date and timeslot on the admin order page
	 *
	 * @param object $order WP_Order.
	 */
	public function display_admin_order_meta( $order ) {

		$this->display_date_and_time_zone( $order );
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function add_meta_box() {
		if ( get_option( 'wc4jp-delivery-date' ) || get_option( 'wc4jp-delivery-time-zone' ) ) {
			$current_screen = get_current_screen();
			if ( 'shop_order' === $current_screen->id || 'woocommerce_page_wc-orders' === $current_screen->id ) {
				add_meta_box( 'woocommerce-shipping-date-and-time', __( 'Shipping Detail', 'woocommerce-for-japan' ), array( &$this, 'meta_box' ), $current_screen->id, 'side', 'high' );
			}
		}
	}

	/**
	 * Show the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function meta_box() {
		if ( isset( $_GET['post'] ) ) {
			$order_id = absint( sanitize_text_field( wp_unslash( $_GET['post'] ) ) );
		} elseif ( isset( $_GET['id'] ) ) {
			$order_id = absint( sanitize_text_field( wp_unslash( $_GET['id'] ) ) );
		} else {
			$order_id = false;
		}

		if ( $order_id ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = false;
		}
		$shipping_fields = $this->shipping_fields( $order );
		echo '<div id="jp4wc_shipping_data_wrapper">';
		foreach ( $shipping_fields as $key => $value ) {
			if ( 'text' === $value['type'] ) {
				woocommerce_wp_text_input( $value );
			}
		}
		echo '</div>';
	}

	/**
	 * Save the meta box for shipment info on the order page.
	 *
	 * @access public
	 * @param string $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}
		$order           = wc_get_order( $post_id );
		$shipping_fields = $this->shipping_fields( $order );

		foreach ( $shipping_fields as $field ) {
			if ( isset( $_POST[ $field['id'] ] ) && 0 !== $_POST[ $field['id'] ] ) {
				$value = wc_clean( sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) );

				// For delivery date and time, check if this order was created via Checkout Block.
				if ( 'wc4jp-delivery-date' === $field['id'] || 'wc4jp-delivery-time-zone' === $field['id'] ) {
					// Check which meta key format this order uses,.
					$has_block_meta = false;
					if ( 'wc4jp-delivery-date' === $field['id'] ) {
						$has_block_meta = ! empty( $order->get_meta( '_wc_other/jp4wc/delivery-date', true ) );
					} elseif ( 'wc4jp-delivery-time-zone' === $field['id'] ) {
						$has_block_meta = ! empty( $order->get_meta( '_wc_other/jp4wc/delivery-time', true ) );
					}

					// If order has block meta, update both the block meta and shortcode meta for consistency.
					if ( $has_block_meta ) {
						if ( 'wc4jp-delivery-date' === $field['id'] ) {
							$order->update_meta_data( '_wc_other/jp4wc/delivery-date', $value );
						} elseif ( 'wc4jp-delivery-time-zone' === $field['id'] ) {
							$order->update_meta_data( '_wc_other/jp4wc/delivery-time', $value );
						}
					}
				}

				// Always update the standard meta key.
				$order->update_meta_data( $field['id'], $value );
				$order->save();
			}
		}
	}
	/**
	 * Show the meta box for shipment info on the order page
	 *
	 * @access public
	 * @param object $order WP_Order.
	 * @return array
	 */
	public function shipping_fields( $order ) {
		if ( $order ) {
			// Check both shortcode and Checkout Block meta keys for delivery date.
			$date = $order->get_meta( 'wc4jp-delivery-date', true );
			if ( empty( $date ) ) {
				$date = $order->get_meta( '_wc_other/jp4wc/delivery-date', true );
			}

			// Check both shortcode and Checkout Block meta keys for delivery time.
			$time = $order->get_meta( 'wc4jp-delivery-time-zone', true );
			if ( empty( $time ) ) {
				$time = $order->get_meta( '_wc_other/jp4wc/delivery-time', true );
			}

			$delivery_date = $order->get_meta( 'wc4jp-tracking-ship-date', true );
		} else {
			$date          = '';
			$time          = '';
			$delivery_date = '';
		}
		$shipping_fields = array(
			'wc4jp-delivery-date'      => array(
				'type'        => 'text',
				'id'          => 'wc4jp-delivery-date',
				'label'       => __( 'Delivery Date', 'woocommerce-for-japan' ),
				'description' => __( 'Date on which the customer wished delivery.', 'woocommerce-for-japan' ),
				'class'       => 'wc4jp-delivery-date',
				'value'       => ( $date ) ? $date : '',
			),
			'wc4jp-delivery-time-zone' => array(
				'type'        => 'text',
				'id'          => 'wc4jp-delivery-time-zone',
				'label'       => __( 'Time Zone', 'woocommerce-for-japan' ),
				'description' => __( 'Time Zone on which the customer wished delivery.', 'woocommerce-for-japan' ),
				'class'       => 'wc4jp-delivery-time-zone',
				'value'       => ( $time ) ? $time : '',
			),
			'wc4jp-tracking-ship-date' => array(
				'type'        => 'text',
				'id'          => 'wc4jp-tracking-ship-date',
				'label'       => __( 'Tracking Ship Date', 'woocommerce-for-japan' ),
				'description' => __( 'Actually shipped to date', 'woocommerce-for-japan' ),
				'class'       => 'wc4jp-tracking-ship-date',
				'value'       => ( $delivery_date ) ? $delivery_date : '',
			),
		);
		return apply_filters( 'wc4jp_shipping_fields', $shipping_fields, $order );
	}

	/**
	 * Check delivery date data after successful payment and send admin notification if required date is missing.
	 *
	 * @param array $result Payment successful result.
	 * @return array Modified payment result.
	 */
	public function jp4wc_delivery_check_data( $result ) {
		$order_id = $result['order_id'];
		$order    = wc_get_order( $order_id );

		// Check both shortcode and Checkout Block meta keys for delivery date.
		$date = $order->get_meta( 'wc4jp-delivery-date', true );
		if ( empty( $date ) ) {
			$date = $order->get_meta( '_wc_other/jp4wc/delivery-date', true );
		}

		// Check both shortcode and Checkout Block meta keys for delivery time.
		$time = $order->get_meta( 'wc4jp-delivery-time-zone', true );
		if ( empty( $time ) ) {
			$time = $order->get_meta( '_wc_other/jp4wc/delivery-time', true );
		}

		$has_error = false;

		if ( get_option( 'wc4jp-delivery-date' ) && '1' === get_option( 'wc4jp-delivery-date-required' ) ) {
			if ( empty( $date ) ) {
				// Send an email to the administrator.
				$this->send_admin_notification_email( $order_id, 'delivery_date' );
				// Cancel the order and mark it as failed.
				$order->update_status( 'failed', __( 'Order failed: Required delivery date was not provided.', 'woocommerce-for-japan' ) );
				$has_error = true;
			}
		}

		if ( get_option( 'wc4jp-delivery-time-zone' ) && '1' === get_option( 'wc4jp-delivery-time-zone-required' ) ) {
			if ( empty( $time ) ) {
				// Send an email to the administrator.
				$this->send_admin_notification_email( $order_id, 'delivery_time' );
				// Cancel the order and mark it as failed if not already failed.
				if ( ! $has_error ) {
					$order->update_status( 'failed', __( 'Order failed: Required delivery time zone was not provided.', 'woocommerce-for-japan' ) );
				}
				$has_error = true;
			}
		}

		// If there's an error, modify the result to show failure.
		if ( $has_error ) {
			$result['result']   = 'failure';
			$result['messages'] = __( 'Required delivery information is missing. Please contact the store for assistance.', 'woocommerce-for-japan' );
		}

		return $result;
	}

	/**
	 * Send an admin notification email if the required delivery date is not entered.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $type Type of missing information ('delivery_date' or 'delivery_time').
	 */
	private function send_admin_notification_email( $order_id, $type ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Get the administrator's email address and blog name.
		$wc4jp_delivery_notification_email = get_option( 'wc4jp-delivery-notification-email' );
		if ( ! empty( $wc4jp_delivery_notification_email ) ) {
			$admin_email = $wc4jp_delivery_notification_email;
		} else {
			$admin_email = get_option( 'admin_email' );
		}
		$blog_name = get_option( 'blogname' );

		// Determine the required type based on the missing information.
		$required_type = '';
		if ( 'delivery_time' === $type ) {
			$required_type = __( 'delivery time zone', 'woocommerce-for-japan' );
		} elseif ( 'delivery_date' === $type ) {
			$required_type = __( 'delivery date', 'woocommerce-for-japan' );
		}

		if ( '' === $required_type ) {
			return;
		}

		$subject = sprintf(
			/* translators: %1$s: Blog name, %2$s: require type , %3$s: Order number */
			__( '[%1$s] There are orders with no requested %2$s specified (Order number: #%3$s)', 'woocommerce-for-japan' ),
			$blog_name,
			$required_type,
			$order->get_order_number()
		);

		// Email body.
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$is_hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		} else {
			$is_hpos_enabled = false;
		}

		if ( $is_hpos_enabled ) {
			$order_admin_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
		} else {
			$order_admin_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
		}
		$message = sprintf(
			/* translators: %1$s: Order number, %2$s: Order date, %3$s: Customer name, %4$s: Customer email, %5$s: Order detail URL */
			__( "Order Number: #%1\$s\nOrder Date: %2\$s\nCustomer Name: %3\$s\nCustomer Email: %4\$s\n\nThe requested delivery date is a required field but has not been entered.\nPlease review the order details and contact the customer.\n\nOrder Details Page: %5\$s", 'woocommerce-for-japan' ),
			$order->get_order_number(),
			$order->get_date_created()->date_i18n( 'Y年m月d日 H:i' ),
			$order->get_billing_last_name() . ' ' . $order->get_billing_first_name(),
			$order->get_billing_email(),
			$order_admin_url
		);
		$message .= "\n";
		$message .= '========================' . "\n";
		$message .= __( 'Diagnostic Information', 'woocommerce-for-japan' ) . "\n";
		$message .= '========================' . "\n\n";

		// Checkout type detection.
		$message          .= '--- ' . __( 'Checkout Type', 'woocommerce-for-japan' ) . ' ---' . "\n";
		$is_block_checkout = false;
		$block_date        = $order->get_meta( '_wc_other/jp4wc/delivery-date', true );
		$block_time        = $order->get_meta( '_wc_other/jp4wc/delivery-time', true );
		$shortcode_date    = $order->get_meta( 'wc4jp-delivery-date', true );
		$shortcode_time    = $order->get_meta( 'wc4jp-delivery-time-zone', true );

		if ( ! empty( $block_date ) || ! empty( $block_time ) ) {
			$is_block_checkout = true;
		}

		$message .= 'Type: ' . ( $is_block_checkout ? 'Block Checkout' : 'Shortcode Checkout' ) . "\n";
		if ( function_exists( 'jp4wc_is_using_checkout_blocks' ) ) {
			$message .= 'Page Using Blocks: ' . ( jp4wc_is_using_checkout_blocks() ? 'Yes' : 'No' ) . "\n";
		}
		$message .= "\n";

		// Delivery date and time values.
		$message .= '--- ' . __( 'Delivery Data', 'woocommerce-for-japan' ) . ' ---' . "\n";
		$message .= 'Block Date Meta (_wc_other/jp4wc/delivery-date): ' . ( ! empty( $block_date ) ? $block_date : '(empty)' ) . "\n";
		$message .= 'Block Time Meta (_wc_other/jp4wc/delivery-time): ' . ( ! empty( $block_time ) ? $block_time : '(empty)' ) . "\n";
		$message .= 'Shortcode Date Meta (wc4jp-delivery-date): ' . ( ! empty( $shortcode_date ) ? $shortcode_date : '(empty)' ) . "\n";
		$message .= 'Shortcode Time Meta (wc4jp-delivery-time-zone): ' . ( ! empty( $shortcode_time ) ? $shortcode_time : '(empty)' ) . "\n";
		$message .= "\n";

		// Plugin settings.
		$message .= '--- ' . __( 'Plugin Settings', 'woocommerce-for-japan' ) . ' ---' . "\n";
		$message .= 'Delivery Date Enabled: ' . ( get_option( 'wc4jp-delivery-date' ) ? 'Yes' : 'No' ) . "\n";
		$message .= 'Delivery Date Required: ' . ( '1' === get_option( 'wc4jp-delivery-date-required' ) ? 'Yes' : 'No' ) . "\n";
		$message .= 'Delivery Time Enabled: ' . ( get_option( 'wc4jp-delivery-time-zone' ) ? 'Yes' : 'No' ) . "\n";
		$message .= 'Delivery Time Required: ' . ( '1' === get_option( 'wc4jp-delivery-time-zone-required' ) ? 'Yes' : 'No' ) . "\n";
		$message .= "\n";

		// POST data (if available and not block checkout).
		if ( ! $is_block_checkout && ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$message .= '--- ' . __( 'POST Data', 'woocommerce-for-japan' ) . ' ---' . "\n";
			$message .= 'wc4jp_delivery_date: ';
			if ( isset( $_POST['wc4jp_delivery_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$message .= sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_date'] ) ) . "\n"; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$message .= '(not set)' . "\n";
			}
			$message .= 'wc4jp_delivery_time_zone: ';
			if ( isset( $_POST['wc4jp_delivery_time_zone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$message .= sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_time_zone'] ) ) . "\n"; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$message .= '(not set)' . "\n";
			}
			$message .= 'Nonce Present: ' . ( isset( $_POST['woocommerce-process-checkout-nonce'] ) ? 'Yes' : 'No' ) . "\n"; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$message .= "\n";
		}

		// Cart contents.
		if ( WC()->cart ) {
			$message       .= '--- ' . __( 'Cart Information', 'woocommerce-for-japan' ) . ' ---' . "\n";
			$virtual_count  = 0;
			$physical_count = 0;
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product->is_virtual() ) {
					++$virtual_count;
				} else {
					++$physical_count;
				}
			}
			$message .= 'Virtual Products: ' . $virtual_count . "\n";
			$message .= 'Physical Products: ' . $physical_count . "\n";
			$message .= 'Should Show Delivery Fields: ' . ( $physical_count > 0 ? 'Yes' : 'No' ) . "\n";
			$message .= "\n";
		}

		// Environment information.
		$message .= '--- ' . __( 'Environment', 'woocommerce-for-japan' ) . ' ---' . "\n";
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$message .= 'User Agent: ' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) . "\n";
		}
		$message .= 'WordPress Version: ' . get_bloginfo( 'version' ) . "\n";
		$message .= 'WooCommerce Version: ' . WC()->version . "\n";
		$message .= 'PHP Version: ' . PHP_VERSION . "\n";
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$message .= 'REST Request: Yes' . "\n";
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$message .= 'Request URI: ' . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . "\n";
			}
		} else {
			$message .= 'REST Request: No' . "\n";
		}
		$message .= "\n";

		// Check recent logs for validation issues.
		if ( function_exists( 'wc_get_logger' ) ) {
			$message .= '--- ' . __( 'Note', 'woocommerce-for-japan' ) . ' ---' . "\n";
			$message .= __( 'Please check WooCommerce logs (jp4wc_delivery_validation) for detailed validation information.', 'woocommerce-for-japan' ) . "\n";
		}

		// Email headers.
		$jp4wc_admin_email = 'wp-admin@artws.info';

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Bcc: ' . $jp4wc_admin_email,
			'From: ' . $blog_name . ' <' . $admin_email . '>',
		);

		// Send email.
		if ( $subject && $message ) {
			wp_mail( $admin_email, $subject, $message, $headers );

			// Log the event.
			if ( function_exists( 'wc_get_logger' ) ) {
				$logger = wc_get_logger();
				$logger->info(
					sprintf(
						/* translators: %1$s: require type , %2$s: Order ID */
						__( '%1$sAn empty notification email has been sent. Order ID: %2$d', 'woocommerce-for-japan' ),
						$required_type,
						$order_id
					),
					array( 'source' => 'jp4wc_delivery' )
				);
			}
		}
	}
}

new JP4WC_Delivery();
