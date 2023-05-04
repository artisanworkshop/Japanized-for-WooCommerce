<?php
/**
 * Migrate PayPal gateway enabled settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrates PayPal settings to the new individual gateway options.
 */
function peachpay_migrate_paypal() {
	if ( get_option( 'peachpay_paypal_gateway_migration', 0 ) === 0 ) {
		$payment_options = get_option( 'peachpay_payment_options', array() );

		if ( isset( $payment_options['paypal'] ) && $payment_options['paypal'] ) {
			$paypal_settings            = get_option( 'peachpay_peachpay_paypal_wallet_settings', array() );
			$paypal_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_paypal_wallet_settings', $paypal_settings );
		}

		update_option( 'peachpay_paypal_gateway_migration', 1 );
	}
}
