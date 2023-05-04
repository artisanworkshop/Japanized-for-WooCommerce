<?php
/**
 * PeachPay Poynt functions.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Adds the feature flag for enabling GoDaddy Poynt gateways.
 *
 * @param array $feature_list The list of features.
 */
function peachpay_poynt_register_feature( $feature_list ) {
	$feature_list['peachpay_poynt_gateways'] = array(
		'enabled'  => PeachPay_Poynt_Integration::connected(),
		'metadata' => array(
			'poynt_src'      => PeachPay_Poynt_Integration::poynt_script_src(),
			'business_id'    => PeachPay_Poynt_Integration::business_id(),
			'application_id' => PeachPay_Poynt_Integration::application_id(),
		),
	);

	return $feature_list;
}

/**
 * Callback function that gets activated when merchant changes order status to completed.
 *
 * @param string $order_id The order id of the order that was completed.
 */
function peachpay_poynt_handle_order_completed( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! PeachPay_Poynt_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	if ( PeachPay_Poynt_Advanced::get_setting( 'capture_on_complete' ) !== 'yes' ) {
		return;
	}

	$status = PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' );

	if ( 'AUTHORIZED' !== $status || ! $order->get_transaction_id() || $order->get_total() > 0 ) {
		return;
	}

	$capture_result = PeachPay_Poynt::capture_payment( $order, $order->get_total() );

	if ( ! $capture_result['success'] ) {
		return;
	}

	$order->save();
}

/**
 * Callback function that gets activated when merchant changes order status to cancelled.
 *
 * @param string $order_id The order id of the order that was cancelled.
 */
function peachpay_poynt_handle_order_cancelled( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! PeachPay_Poynt_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	if ( PeachPay_Poynt_Advanced::get_setting( 'refund_on_cancel' ) !== 'yes' ) {
		return;
	}

	$status = PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' );
	if ( 'VOIDED' === $status || 'REFUNDED' === $status ) {
		return;
	}

	if ( $order->get_transaction_id() && $order->get_total() > 0 ) {

		$refund_amount = $order->get_total() - $order->get_total_refunded();

		if ( 'AUTHORIZED' === $status ) {
			$void_result = PeachPay_Poynt::void_payment( $order );
			if ( ! $void_result['success'] ) {
				$order->add_order_note( $void_result['message'] );
				return;
			}
		} else {
			$refund_result = PeachPay_Poynt::refund_payment( $order, $refund_amount );
			if ( ! $refund_result['success'] ) {
				$order->add_order_note( $refund_result['message'] );
				return;
			}
		}

		$refund = new WC_Order_Refund();
		$refund->set_amount( $refund_amount );
		$refund->set_parent_id( $order->get_id() );
		$refund->set_reason( 'Order was canceled or removed.' );
		$refund->set_refunded_by( get_current_user_id() );
		$refund->set_refunded_payment( true );
		$refund->save();

		$order->save();
	}
}

/**
 * Handle Poynt settings actions.
 *
 * @param array $plugin_capabilities The current capabilities of the PeachPay plugin.
 */
function peachpay_poynt_handle_admin_actions( $plugin_capabilities ) {
	if ( peachpay_plugin_has_capability_config( 'poynt', $plugin_capabilities ) ) {
		$poynt_config = peachpay_plugin_get_capability_config( 'poynt', $plugin_capabilities );
		update_option( 'peachpay_connected_poynt_config', $poynt_config );
	} else {
		delete_option( 'peachpay_connected_poynt_config' );
	}

	// Update Poynt capabilities and account info.
	if ( peachpay_plugin_has_capability( 'poynt', $plugin_capabilities ) ) {
		$poynt_account = peachpay_plugin_get_capability( 'poynt', $plugin_capabilities )['account'];

		update_option( 'peachpay_connected_poynt_account', $poynt_account );
	} else {
		delete_option( 'peachpay_connected_poynt_account' );
	}

	// PHPCS:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
	$linked   = isset( $_GET['connected_poynt'] ) ? sanitize_text_field( wp_unslash( $_GET['connected_poynt'] ) ) : null;
	$unlinked = isset( $_GET['unlink_poynt'] );
	// PHPCS:enable

	if ( $linked && 'true' === $linked && PeachPay_Poynt_Integration::connected() ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have successfully connected your GoDaddy Poynt account. You may set up other payment methods in the "Payments" tab.', 'peachpay-for-woocommerce' ),
			'success'
		);
	} elseif ( $linked && 'false' === $linked ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'Failed to connect your GoDaddy Poynt account. Please try again.', 'peachpay-for-woocommerce' ),
			'error'
		);
	}

	if ( $unlinked && PeachPay_Poynt_Integration::connected() ) {
		PeachPay_Poynt::unlink();
	}
}

/**
 * Display GoDaddy Poynt order details.
 *
 * @param WC_Order $order The given order to display information for.
 */
function peachpay_poynt_display_order_transaction_details( $order ) {
	if ( did_action( 'woocommerce_admin_order_data_after_billing_address' ) > 1 ) {
		return;
	}
	if ( ! PeachPay_Poynt_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	$transaction_id = PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' );
	if ( null !== $transaction_id ) {
		include PeachPay::get_plugin_path() . '/core/payments/poynt/admin/views/html-poynt-transaction-details.php';
	}
}

/**
 * Displays the Poynt capture/void form on the order dashboard.
 *
 * @param int $order_id Id for the order.
 */
function peachpay_poynt_display_order_transaction_capture_void_form( $order_id ) {
	if ( did_action( 'woocommerce_admin_order_totals_after_total' ) > 1 ) {
		return;
	}

	$order = wc_get_order( $order_id );

	if ( ! PeachPay_Poynt_Integration::is_payment_gateway( $order->get_payment_method() ) ) {
		return;
	}

	$status = PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' );
	if ( 'AUTHORIZED' === $status ) {
		$currency          = PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['currency'];
		$amount_capturable = PeachPay_Poynt::display_amount( PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['transactionAmount'], $currency );
		include PeachPay::get_plugin_path() . 'core/payments/poynt/admin/views/html-poynt-capture-void.php';
	}
}

/**
 * WP ajax request to capture a poynt payment.
 */
function peachpay_poynt_handle_capture_payment() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-poynt-capture-payment' ) ) {
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
	$amount_to_capture = floatval( wp_unslash( $_POST['amount_to_capture'] ) );

	wp_send_json( PeachPay_Poynt::capture_payment( $order, $amount_to_capture ) );
}

/**
 * WP ajax request to void a Poynt payment.
 */
function peachpay_poynt_handle_void_payment() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-poynt-void-payment' ) ) {
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

	wp_send_json( PeachPay_Poynt::void_payment( $order ) );
}

/**
 * WP ajax request to register/reset Poynt webhook.
 */
function peachpay_poynt_handle_register_webhooks() {
    // PHPCS:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-poynt-register-webhooks' ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'Invalid nonce. Please refresh the page and try again.',
			)
		);
	}
    // PHPCS:enable

	wp_send_json( PeachPay_Poynt::register_webhooks() );
}

/**
 * Filters display payment tokens.
 *
 * @param array $tokens the list of tokens to filter.
 */
function peachpay_poynt_get_customer_payment_tokens( $tokens ) {

	$current_mode = PeachPay_Poynt_Integration::mode();
	$business_id  = PeachPay_Poynt_Integration::business_id();

	foreach ( $tokens as $id => $token ) {
		if ( $token instanceof WC_Payment_Token_PeachPay_Poynt_Card ) {
			if ( $token->get_mode() !== $current_mode ) {
				unset( $tokens[ $id ] );
			}
			if ( $token->get_business_id() !== $business_id ) {
				unset( $tokens[ $id ] );
			}
		}
	}

	return $tokens;
}

/**
 * Controls the output for PeachPay Poynt tokenized payment methods on the my account page.
 *
 * @param  array            $item         Individual list item from woocommerce_saved_payment_methods_list.
 * @param  WC_Payment_Token $payment_token The payment token associated with this method entry.
 * @return array                           Filtered item.
 */
function peachpay_poynt_get_account_saved_payment_methods_list_item( $item, $payment_token ) {
	if ( 'peachpay_poynt_card' === strtolower( $payment_token->get_type() ) ) {
		$item['method']['last4'] = $payment_token->get_last4();
		$item['method']['brand'] = $payment_token->get_card_type();
		$item['expires']         = sprintf( '%s / %s', $payment_token->get_expiry_month(), $payment_token->get_expiry_year() );

		return $item;
	}

	return $item;
}
