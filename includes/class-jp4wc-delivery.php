<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.6.4
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
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_date_time_checkout_field' ), 10, 2 );
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
	}

	/**
	 * Delivery date designation.
	 *
	 * @return void
	 */
	public function delivery_date_designation() {
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
					/* translators: %s: Week name */
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
	 * Helper: Update order meta on successful checkout submission
	 *
	 * @param int $order_id Order ID.
	 */
	public function update_order_meta( $order_id ) {

		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' )
		) {
			return;
		}

		$date  = false;
		$time  = false;
		$order = wc_get_order( $order_id );

		if ( isset( $_POST['wc4jp_delivery_date'] ) ) {
			$date = apply_filters( 'wc4jp_delivery_date', sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_date'] ) ), $order_id );
			if ( isset( $date ) && '0' !== $date ) {
				if ( get_option( 'wc4jp-date-format' ) ) {
					$date = strtotime( $date );
					$date = date_i18n( get_option( 'wc4jp-date-format' ), $date );
					$order->update_meta_data( 'wc4jp-delivery-date', esc_attr( htmlspecialchars( $date ) ) );
				}
			} else {
				$order->delete_meta_data( 'wc4jp-delivery-date' );
			}
		}

		if ( isset( $_POST['wc4jp_delivery_time_zone'] ) ) {
			$time = apply_filters( 'wc4jp_delivery_time_zone', sanitize_text_field( wp_unslash( $_POST['wc4jp_delivery_time_zone'] ) ), $order_id );
		}
		if ( ! empty( $time ) && '0' !== $time ) {
			$order->update_meta_data( 'wc4jp-delivery-time-zone', esc_attr( htmlspecialchars( $time ) ) );
		} else {
			$order->delete_meta_data( 'wc4jp-delivery-time-zone' );
		}

		if ( isset( $_POST['wc4jp-tracking-ship-date'] ) ) {
			$ship_date = apply_filters( 'wc4jp_ship_date', sanitize_text_field( wp_unslash( $_POST['wc4jp-tracking-ship-date'] ) ), $order_id );
		}
		if ( isset( $ship_date ) && '0' !== $ship_date ) {
			$order->update_meta_data( 'wc4jp-tracking-ship-date', esc_attr( htmlspecialchars( $ship_date ) ) );
		} else {
			$order->delete_meta_data( 'wc4jp-tracking-ship-date' );
		}
		$order->save();
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
		if ( get_option( 'wc4jp-delivery-date' ) && get_option( 'wc4jp-delivery-date-required' ) && ! jp4wc_is_using_checkout_blocks() ) {
			if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' )
			) {
				return;
			}

			if ( empty( $_POST['wc4jp_delivery_date'] ) ) {
				$errors->add( 'required-field', __( '"Desired delivery date" is a required field. Please enter it.', 'woocommerce-for-japan' ) );
			}
		}
	}

	/**
	 * Frontend: Add date and timeslot to frontend order overview
	 *
	 * @param object $order WP_Order.
	 */
	public function frontend_order_timedate( $order ) {

		if ( ! $this->has_date_or_time( $order ) ) {
			return;
		}

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

		if ( ! $date_time ) {
			return;
		}
		if ( '0' === $date_time['date'] ) {
			$date_time['date'] = get_option( 'wc4jp-unspecified-date' );
		}
		if ( '0' === $date_time['time'] ) {
			$date_time['time'] = get_option( 'wc4jp-unspecified-time' );
		}
		$date_time['date'] = apply_filters( 'wc4jp-unspecified-date', $date_time['date'], $order );
		$date_time['time'] = apply_filters( 'wc4jp-unspecified-time', $date_time['time'], $order );
		$show_title        = apply_filters( 'wc4jp-show-title', $show_title, $date_time['date'], $date_time['time'], $order );

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

		if ( ! $this->has_date_or_time( $order ) ) {
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

		$date = $order->get_meta( 'wc4jp-delivery-date', true );
		$time = $order->get_meta( 'wc4jp-delivery-time-zone', true );

		if ( ( $date && '' !== $date ) ) {
			$meta['date'] = $date;
			$has_meta     = true;
		}

		if ( ( $time && '' !== $time ) ) {
			$meta['time'] = $time;
			$has_meta     = true;
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

		global $post, $the_order;
		if ( empty( $the_order ) || $the_order->get_id() != $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		switch ( $column ) {
			case 'wc4jp_delivery':
				$this->display_date_and_time_zone( $the_order );

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
				$order->update_meta_data( $field['id'], wc_clean( sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) ) );
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
			$date          = $order->get_meta( 'wc4jp-delivery-date', true );
			$time          = $order->get_meta( 'wc4jp-delivery-time-zone', true );
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
}

new JP4WC_Delivery();
