<?php
/**
 * Migrate square gateway enabled settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrates square settings to the new individual gateway options.
 */
function peachpay_migrate_square() {
	if ( get_option( 'peachpay_square_gateway_migration', 0 ) === 0 ) {

		$payment_options = get_option( 'peachpay_payment_options', array() );

		if ( isset( $payment_options['square_enable'] ) && 1 === $payment_options['square_enable'] ) {
			$card_settings            = get_option( 'peachpay_peachpay_square_card_settings', array() );
			$card_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_square_card_settings', $card_settings );
		}

		if ( isset( $payment_options['square_apple_pay_payments'] ) && 1 === $payment_options['square_apple_pay_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_applepay_card_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_applepay_card_settings', $applepay_settings );
		}

		if ( isset( $payment_options['square_google_pay_payments'] ) && 1 === $payment_options['square_google_pay_payments'] ) {
			$googlepay_settings            = get_option( 'peachpay_peachpay_square_googlepay_settings', array() );
			$googlepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_square_googlepay_settings', $googlepay_settings );
		}

		update_option( 'peachpay_square_gateway_migration', 1 );
	}
}

