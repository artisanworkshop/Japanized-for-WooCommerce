<?php
/**
 * PeachPay stripe order-status endpoints hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Amazon Pay payment success hook.
 *
 * @param WC_Order $order The order to operate on.
 * @param array    $request The request data.
 */
function peachpay_handle_amazonpay_success_status( $order, $request ) {

	$amazonpay_meta_result = peachpay_order_add_amazonpay_meta( $order, $request );

	if ( 'success' !== $amazonpay_meta_result[0] ) {
		wp_send_json_error( $amazonpay_meta_result[2], 400 );
		return;
	}

	return '';
}

/**
 * Add amazon pay order status meta
 *
 * @param WC_Order $order The order to operate on.
 * @param array    $request The request data.
 */
function peachpay_order_add_amazonpay_meta( $order, $request ) {
	$amazon_pay = peachpay_array_value( $request, 'amazonpay' );

	if ( ! $amazon_pay ) {
		return array( 'fail', null, 'Missing required Stripe details.' );
	}

	$charge_id = $amazon_pay['chargeId'];
	if ( $charge_id ) {
		$order->add_meta_data( '_pp_amazonpay_charge_id', $charge_id, true );
		$order->set_transaction_id( $charge_id );
		return array( 'success', $charge_id, null );
	}

	return array( 'fail', null, 'No charge id provided for order' );
}
