<?php
/**
 * PeachPay Authnet functions.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Adds the feature flag for enabling Authorize.net gateway.
 *
 * @param array $feature_list The list of features.
 */
function peachpay_authnet_register_feature( $feature_list ) {

	$feature_list['peachpay_authnet_gateways'] = array(
		'enabled'  => PeachPay_Authnet_Integration::connected(),
		'metadata' => array(
			'accept_src'        => PeachPay_Authnet_Integration::mode() === 'test' ? 'https://jstest.authorize.net/v3/AcceptUI.js' : 'https://js.authorize.net/v3/AcceptUI.js',
			'login_id'          => PeachPay_Authnet_Integration::login_id(),
			'public_client_key' => PeachPay_Authnet_Integration::public_client_key(),
		),
	);

	return $feature_list;
}

/**
 * Displays Authorize.net's charge/order details.
 *
 * @param WC_Order $order The given order to display information for.
 */
function peachpay_authnet_display_order_transaction_details( $order ) {
	if ( did_action( 'woocommerce_admin_order_data_after_billing_address' ) > 1 ) {
		return;
	}
	if ( ! PeachPay_Authnet_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	$transaction_id = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );
	$status         = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );
	if ( null !== $transaction_id || null !== $status ) {
		include PeachPay::get_plugin_path() . 'core/payments/authnet/admin/views/html-authnet-transaction-details.php';
	}
}

/**
 * Displays the Authnet capture/void form on the order dashboard.
 *
 * @param int $order_id Id for the order.
 */
function peachpay_authnet_display_order_transaction_capture_void_form( $order_id ) {
	if ( did_action( 'woocommerce_admin_order_totals_after_total' ) > 1 ) {
		return;
	}

	$order = wc_get_order( $order_id );

	if ( ! PeachPay_Authnet_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	$status = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );
	if ( 'authorizedPendingCapture' === $status ) {
		$amount_capturable = floatval( PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'authAmount' ) );
		include PeachPay::get_plugin_path() . 'core/payments/authnet/admin/views/html-authnet-capture-void.php';
	}
}

/**
 * WP ajax request to capture a Authnet payment.
 */
function peachpay_authnet_handle_capture_payment() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-authnet-capture-payment' ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'Invalid nonce. Please refresh the page and try again.',
			)
		);
	}

	if ( ! isset( $_POST['order_id'] ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'The field "order_id" is missing.',
			)
		);
	}
	$order_id = floatval( wp_unslash( $_POST['order_id'] ) );

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'The field "order_id" did not match any orders.',
			)
		);
	}

	if ( ! isset( $_POST['amount_to_capture'] ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'The field "amount_to_capture" is missing.',
			)
		);
	}
	$amount_to_capture = PeachPay_Authnet::format_amount( floatval( wp_unslash( $_POST['amount_to_capture'] ) ), $order->get_currency() );

	wp_send_json( PeachPay_Authnet::capture_payment( $order, $amount_to_capture ) );
}

/**
 * WP ajax request to void a Authnet payment.
 */
function peachpay_authnet_handle_void_payment() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-authnet-void-payment' ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'Invalid nonce. Please refresh the page and try again.',
			)
		);
	}

	if ( ! isset( $_POST['order_id'] ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'The field "order_id" is missing.',
			)
		);
	}
	$order_id = floatval( wp_unslash( $_POST['order_id'] ) );

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'The field "order_id" did not match any orders.',
			)
		);
	}

	wp_send_json( PeachPay_authnet::void_payment( $order ) );
}

/**
 * Callback function that gets activated when merchant changes order status to cancelled.
 *
 * @param string $order_id The order id of the order that was cancelled.
 */
function peachpay_authnet_handle_order_cancelled( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( ! $order || ! PeachPay_Authnet_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	if ( PeachPay_Authnet_Advanced::get_setting( 'refund_on_cancel' ) !== 'yes' ) {
		return;
	}

	if ( $order->get_transaction_id() && $order->get_total() > 0 ) {

		$refund_amount = $order->get_total() - $order->get_total_refunded();

		$refund_params = array(
			'transactionType' => 'refundTransaction',
			'amount'          => $refund_amount,
		);

		$transaction_status = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );
		$transaction_id     = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );

		$refund_result = '';
		if ( 'successfullySettled' === $transaction_status ) {
			$refund_result = PeachPay_Authnet::refund_payment( $order, $refund_params );
		} else {
			$refund_result = PeachPay_Authnet::void_payment( $order );
		}

		if ( ! $refund_result['success'] ) {
			$order->add_order_note( $refund_result['message'] );
			return;
		}

		$refund = new WC_Order_Refund();
		$refund->set_amount( $refund_amount );
		$refund->set_parent_id( $order->get_id() );
		$refund->set_reason( 'Order was canceled or removed.' );
		$refund->set_refunded_by( get_current_user_id() );
		$refund->save();

		// translators: %1$s the payment method title, %3$s Refund amount, %4$s Refund Id.
		$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment refunded/voided %2$s because order was cancelled. (Transaction Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $refund_amount, array( 'currency' => $order->get_currency() ) ), $transaction_id ) );
		$order->save();

	}
}

/**
 * Callback function that gets activated when merchant changes order status to completed.
 *
 * @param string $order_id The order id of the order that was completed.
 */
function peachpay_authnet_handle_order_completed( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! PeachPay_Authnet_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	if ( PeachPay_Authnet_Advanced::get_setting( 'capture_on_complete' ) !== 'yes' ) {
		return;
	}

	$status = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );

	if ( 'authorizedPendingCapture' !== $status || ! $order->get_transaction_id() || $order->get_total() === 0 ) {
		return;
	}

	$capture_result = PeachPay_Authnet::capture_payment( $order, $order->get_total() );

	if ( ! $capture_result['success'] ) {
		return;
	}

	$order->save();
}


/**
 * Handle Authnet settings actions.
 */
function peachpay_authnet_handle_admin_actions() {
	// PHPCS:disable
	$linked   = isset( $_GET['link_authnet'] ) ? sanitize_text_field( wp_unslash( $_GET['link_authnet'] ) ) : null;
	$unlinked = isset( $_GET['unlink_authnet'] );
	// PHPCS:enable

	if ( $linked && 'true' === $linked && PeachPay_Authnet_Integration::connected() ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have successfully connected your Authorize.net account. You may set up other payment methods in the "Payments" tab.', 'peachpay-for-woocommerce' ),
			'success'
		);
	} elseif ( $linked && 'false' === $linked ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'Failed to connect your Authorize.net account. Please try again.', 'peachpay-for-woocommerce' ),
			'error'
		);
	}

	if ( $unlinked && PeachPay_Authnet_Integration::connected() ) {
		PeachPay_Authnet::unlink();
	}
}

/**
 * Handle Authnet plugin capabilities.
 *
 * @param array $plugin_capabilities The current capabilities of the PeachPay plugin.
 */
function peachpay_authnet_handle_plugin_capabilities( $plugin_capabilities ) {
	if ( peachpay_plugin_has_capability_config( 'authnet', $plugin_capabilities ) ) {
		$authnet_config = peachpay_plugin_get_capability_config( 'authnet', $plugin_capabilities );
		update_option( 'peachpay_connected_authnet_config', $authnet_config );
	} else {
		delete_option( 'peachpay_connected_authnet_config' );
	}

	// Update Authnet capabilities and account info.
	if ( peachpay_plugin_has_capability( 'authnet', $plugin_capabilities ) ) {
		$authnet_account = peachpay_plugin_get_capability( 'authnet', $plugin_capabilities )['account'];

		update_option( 'peachpay_connected_authnet_account', $authnet_account );
	} else {
		delete_option( 'peachpay_connected_authnet_account' );
	}
}

/**
 * Truncate product's name/description to its maximum character size.
 *
 * @param string $string The string object to modify if it exceeds the length limit.
 * @param int    $limit The length limit of the string.
 */
function str_truncate( $string, $limit ) {
	if ( strlen( $string ) <= $limit ) {
		return $string;
	}

	return substr( $string, 0, $limit - 3 ) . '...';

}
