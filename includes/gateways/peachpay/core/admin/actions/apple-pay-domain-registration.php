<?php
/**
 * PeachPay Stripe Apple Pay domain registration.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Sets test mode to true if the merchant connected the store with the "connect_payment_method_later" GET parameter set.
 */
function peachpay_apple_pay_domain_registration_admin_action() {
	// PHPCS:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['register_domain'] ) && 'true' === $_GET['register_domain'] ) {
		do_action( 'peachpay_check_apple_pay_domain' );

		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have initiated an Apple Pay domain registration attempt. Please allow 5-10 seconds before refreshing to see if it succeeds.', 'peachpay-for-woocommerce' ),
			'success'
		);
	}
}
add_action( 'peachpay_settings_admin_action', 'peachpay_apple_pay_domain_registration_admin_action' );
