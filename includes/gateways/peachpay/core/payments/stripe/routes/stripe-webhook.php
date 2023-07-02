<?php
/**
 * PeachPay stripe order-status endpoints hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/payments/stripe/utils/class-peachpay-stripe-order-data.php';

/**
 * Stripe webhook payment success hook.
 *
 * @param WP_REST_Request $request The webhook request data.
 */
function peachpay_rest_api_stripe_webhook( $request ) {
	$order = wc_get_order( $request['order_id'] );
	if ( ! $order ) {
		wp_send_json_error( 'Required field "order_id" was invalid or missing', 400 );
		return;
	}

	$reason = '';
	if ( isset( $request['status_message'] ) ) {
		$reason = $request['status_message'];
	}

	$is_most_recent_payment_intent = isset( $request['payment_intent_details'] ) && isset( $request['payment_intent_details']['id'] ) && PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'id' ) === $request['payment_intent_details']['id'];
	$is_dispute                    = isset( $request['type'] ) && ( 'charge.dispute.created' === $request['type'] || 'charge.dispute.closed' === $request['type'] );

	// Block old intent ids from modifiying the woocommerce order status, but ignore this restriction for dispute webhooks
	if ( $is_most_recent_payment_intent || $is_dispute ) {
		PeachPay_Stripe::calculate_payment_state( $order, $request, $reason );
	}

	wp_send_json_success(
		array(
			'status' => $order->get_status(),
		)
	);
}
