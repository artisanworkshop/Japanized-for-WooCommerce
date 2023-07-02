<?php
/**
 * PeachPay saved setting banner.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Displays a banner if the PeachPay settings were just saved.
 */
function peachpay_saved_settings_banner_admin_action() {
    // PHPCS:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['settings-updated'] ) ) {
		if ( ! is_array( get_option( 'peachpay_payment_options' ) ) ) {
			update_option( 'peachpay_payment_options', array() );
		}

		add_settings_error(
			'peachpay_messages',
			'peachpay_message-auto_dismiss',
			__( 'Changes were saved!', 'peachpay-for-woocommerce' ),
			'success'
		);
	}
}
add_action( 'peachpay_settings_admin_action', 'peachpay_saved_settings_banner_admin_action' );
