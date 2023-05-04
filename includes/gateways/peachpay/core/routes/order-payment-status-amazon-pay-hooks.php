<?php
/**
 * PeachPay Amazon Pay order-status endpoints hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Hook for custom Amazon Pay success order-status.
 *
 * @param WC_Order $order The order to operate on.
 * @param array    $request The request data.
 */
function peachpay_handle_amazon_pay_success_status( $order, $request ) {

	$amazon_pay = peachpay_array_value( $request, 'amazon_pay' );
	if ( ! $amazon_pay ) {
		wp_send_json_error( 'Required field "amazon_pay" is missing or invalid', 400 );
	}

	$session_id = peachpay_array_value( $amazon_pay, 'session_id' );
	if ( ! $session_id ) {
		wp_send_json_error( 'Required field "amazon_pay.session_id" is missing or invalid', 400 );
	}

	$order->set_transaction_id( $session_id );
}

