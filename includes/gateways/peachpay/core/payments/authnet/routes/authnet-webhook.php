<?php
/**
 * PeachPay Authorize.net order-status endpoints hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Authorize.net webhook payment success hook.
 *
 * @param WP_REST_Request $request The webhook request data.
 */
function peachpay_rest_api_authnet_webhook( $request ) {
	$order = wc_get_order( $request['order_id'] );
	if ( ! $order ) {
		wp_send_json_error( 'Required field "order_id" was invalid or missing', 400 );
		return;
	}

	PeachPay_Authnet::calculate_payment_state( $order, $request->get_json_params() );

	wp_send_json_success(
		array(
			'status' => $order->get_status(),
		)
	);
}
