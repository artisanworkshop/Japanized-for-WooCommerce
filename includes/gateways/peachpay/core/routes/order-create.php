<?php
/**
 * Endpoint for creating orders with peachpay.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay endpoint for creating an order.
 */
function peachpay_wc_ajax_create_order() {
	//phpcs:ignore
	peachpay_login_user();

	if ( WC()->cart->is_empty() ) {
		return wp_send_json(
			array(
				'result'   => 'failure',
				'messages' => __( 'PeachPay was unable to process your order because the cart is empty.', 'peachpay-for-woocommerce' ),
			)
		);
	}

	if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
		define( 'WOOCOMMERCE_CHECKOUT', true );
	}

	// This constant is to ensure PeachPay is not excluded from available
	// gateways while within the ?wc-ajax=order-create endpoint.
	define( 'PEACHPAY_CHECKOUT', 1 );

	add_action( 'woocommerce_checkout_update_order_meta', 'peachpay_append_test_mode', 10 );
	add_filter( 'woocommerce_checkout_update_order_meta', 'peachpay_append_payment_method', 1 );
	add_filter( 'woocommerce_checkout_update_order_meta', 'peachpay_append_subscription_data', 1 );

	$_REQUEST['woocommerce-process-checkout-nonce'] = wp_create_nonce( 'woocommerce-process_checkout' );

	WC()->checkout()->process_checkout();

	wp_die();
}

/**
 * Append meta data to the order for the payment method.
 *
 * @param int $order_id the order we want to update.
 */
function peachpay_append_payment_method( $order_id ) {
	//phpcs:ignore
	if ( ! empty( $_POST['payment_method_variation'] ) ) {
		//phpcs:ignore
		update_post_meta( $order_id, 'payment_method_variation', wc_sanitize_term_text_based( wp_unslash( $_POST['payment_method_variation'] ) ) );
	}
}

/**
 * If it's a test mode order append that data.
 *
 * @param int $order_id updates the order id with some meta for if peachpay test mode.
 */
function peachpay_append_test_mode( $order_id ) {
    //phpcs:ignore
	if ( isset( $_REQUEST['peachpay_is_test_mode'] ) ) {
		update_post_meta( $order_id, 'peachpay_is_test_mode', true );
	}
}

/**
 * If order contains a subscription, it will append this has_subscription to true
 *
 * @param int $order_id the order we want to update.
 */
function peachpay_append_subscription_data( $order_id ) {
	$has_subscription = ( is_array( WC()->cart->recurring_carts ) || is_object( WC()->cart->recurring_carts ) ) && 0 < count( WC()->cart->recurring_carts );
	update_post_meta( $order_id, 'has_subscription', $has_subscription );
}
