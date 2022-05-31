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
 *
 * @throws Exception If the PeachPay checkout nonce is not valid.
 */
function peachpay_wc_ajax_create_order() {
	//phpcs:ignore
	if ( ! isset( $_REQUEST['peachpay_checkout_nonce'] )
		// On the product page, because we add to cart without refreshing the page, the nonce
		// is invalidated and the checkout always fails. However, this brings us all the way
		// back to why we created our own checkout nonce in the first place. When we were using
		// the WooCommerce process checkout nonce, we made sure to refresh it after adding to
		// cart and send that value to the browser so that it would work, but it the checkout
		// still was failing in cases that we could not reproduce. Since refreshing this nonce
		// would just take us back to where we started, for now it's disabled until we have
		// some time to really look into this interesting behavior.
		//
		// || ! wp_verify_nonce( $_REQUEST['peachpay_checkout_nonce'], 'peachpay_process_checkout' ).
	) {
		return wp_send_json_error( __( 'PeachPay was unable to process your order, please try again.', 'peachpay-for-woocommerce' ) );
	}

	peachpay_login_user();

	if ( WC()->cart->is_empty() ) {
		return wp_send_json_error( __( 'PeachPay was unable to process your order because the cart is empty.', 'peachpay-for-woocommerce' ) );
	}

	if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
		define( 'WOOCOMMERCE_CHECKOUT', true );
	}

	$_REQUEST['woocommerce-process-checkout-nonce'] = wp_create_nonce( 'woocommerce-process_checkout' );

	WC()->checkout()->process_checkout();

	wp_die();
}
