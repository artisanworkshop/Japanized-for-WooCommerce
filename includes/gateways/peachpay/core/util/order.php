<?php
/**
 * PeachPay order helpers
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

define( 'PEACHPAY_PAYMENT_META_KEY', '_peachpay_payment_meta' );

/**
 * Gets any available payment information from an order.
 *
 * @param  int $order_id The original order id that payment information was stored in.
 * @return array|false
 */
function peachpay_get_order_payment_meta( $order_id ) {
	return get_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, true );
}

/**
 * Sets peachpay order payment meta related to stripe
 *
 * @param  string $stripe_customer_id The customer stripe id to store.
 */
function peachpay_build_stripe_order_payment_meta( $stripe_customer_id = '' ) {
	$data = array(
		'payment_type' => 'stripe',
		'customer_id'  => $stripe_customer_id,
	);

	return $data;
}

/**
 * Gets all peachpay order payment meta related to stripe. If the payment
 * was not stripe it then returns a empty string.
 *
 * @param  int $order_id The original order id that payment information was stored in.
 * @return string
 */
function peachpay_get_stripe_order_payment_meta( $order_id ) {
	$data = get_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, true );
	return ( 'stripe' === $data['payment_type'] ? $data['customer_id'] : '' );
}
