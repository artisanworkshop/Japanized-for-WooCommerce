<?php
/**
 * PeachPay Poynt endpoints hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Poynt webhook payment success hook.
 *
 * @param WP_REST_Request $request The webhook request data.
 */
function peachpay_rest_api_poynt_webhook( $request ) {
	$order = wc_get_order( $request['order_id'] );
	if ( ! $order ) {
		wp_send_json_error( 'Required field "order_id" was invalid or missing', 400 );
		return;
	}

	PeachPay_Poynt::calculate_payment_state( $order, $request );

	wp_send_json_success(
		array(
			'status' => $order->get_status(),
		)
	);
}
