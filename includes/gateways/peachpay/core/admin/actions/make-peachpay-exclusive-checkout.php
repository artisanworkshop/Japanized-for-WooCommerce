<?php
/**
 * PeachPay connect payment later action.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Sets test mode to true if the merchant connected the store with the "connect_payment_method_later" GET parameter set.
 */
function peachpay_make_peachpay_exclusive_checkout_admin_action() {
	// PHPCS:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['enable_exclusive_checkout'] ) ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have successfully made PeachPay the only checkout method. You can undo this in the "Payment" tab.', 'peachpay-for-woocommerce' ),
			'success'
		);

		if ( peachpay_is_test_mode() ) {
			peachpay_set_settings_option( 'peachpay_payment_options', 'test_mode', false );
		}
		peachpay_set_settings_option( 'peachpay_express_checkout_window', 'make_pp_the_only_checkout', true );
	}
}
add_action( 'peachpay_settings_admin_action', 'peachpay_make_peachpay_exclusive_checkout_admin_action' );
