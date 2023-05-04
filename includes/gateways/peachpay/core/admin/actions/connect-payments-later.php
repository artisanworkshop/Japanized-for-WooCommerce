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
function peachpay_connect_payments_later_admin_action() {
	// PHPCS:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['connect_payment_method_later'] ) ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You can enable test mode below and can finish setting up payment methods for PeachPay from the "Payment methods" tab.', 'peachpay-for-woocommerce' ),
			'info'
		);

		peachpay_set_settings_option( 'peachpay_payment_options', 'test_mode', true );

		$payment_settings = admin_url() . 'admin.php?page=peachpay&tab=payment';
		wp_safe_redirect( $payment_settings );
		exit();
	}
}
add_action( 'peachpay_settings_admin_action', 'peachpay_connect_payments_later_admin_action' );
