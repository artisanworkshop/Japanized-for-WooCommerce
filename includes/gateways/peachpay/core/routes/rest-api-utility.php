<?php
/**
 * Rest-API utilities for PeachPay.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Loads class files needed for using the Woocommerce cart.
 */
function peachpay_wc_load_cart() {
	include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
	include_once WC_ABSPATH . 'includes/class-wc-cart.php';
	if ( is_null( WC()->cart ) ) {
		wc_load_cart();
	}
}
