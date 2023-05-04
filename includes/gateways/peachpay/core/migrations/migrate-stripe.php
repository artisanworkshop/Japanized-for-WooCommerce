<?php
/**
 * Migrate Stripe gateway enabled settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrates Stripe settings to the new individual gateway options.
 */
function peachpay_migrate_stripe() {
	if ( get_option( 'peachpay_stripe_gateway_migration', 0 ) === 0 ) {
		$payment_options = get_option( 'peachpay_payment_options', array() );

		if ( isset( $payment_options['enable_stripe'] ) && $payment_options['enable_stripe'] ) {
			$card_settings            = get_option( 'peachpay_peachpay_stripe_card_settings', array() );
			$card_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_card_settings', $card_settings );
		}

		if ( isset( $payment_options['us_bank_account_ach_payments'] ) && $payment_options['us_bank_account_ach_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_achdebit_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_achdebit_settings', $applepay_settings );
		}

		if ( isset( $payment_options['affirm_payments'] ) && $payment_options['affirm_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_affirm_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_affirm_settings', $applepay_settings );
		}

		if ( isset( $payment_options['afterpay_clearpay_payments'] ) && $payment_options['afterpay_clearpay_payments'] ) {
			$googlepay_settings            = get_option( 'peachpay_peachpay_stripe_afterpay_settings', array() );
			$googlepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_afterpay_settings', $googlepay_settings );
		}

		if ( isset( $payment_options['stripe_payment_request'] ) && $payment_options['stripe_payment_request'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_applepay_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_applepay_settings', $applepay_settings );

			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_googlepay_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_googlepay_settings', $applepay_settings );
		}

		if ( isset( $payment_options['bancontact_payments'] ) && $payment_options['bancontact_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_bancontact_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_bancontact_settings', $applepay_settings );
		}

		if ( isset( $payment_options['eps_payments'] ) && $payment_options['eps_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_eps_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_eps_settings', $applepay_settings );
		}

		if ( isset( $payment_options['giropay_payments'] ) && $payment_options['giropay_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_giropay_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_giropay_settings', $applepay_settings );
		}

		if ( isset( $payment_options['ideal_payments'] ) && $payment_options['ideal_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_ideal_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_ideal_settings', $applepay_settings );
		}

		if ( isset( $payment_options['klarna_payments'] ) && $payment_options['klarna_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_klarna_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_klarna_settings', $applepay_settings );
		}

		if ( isset( $payment_options['p24_payments'] ) && $payment_options['p24_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_p24_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_p24_settings', $applepay_settings );
		}

		if ( isset( $payment_options['sofort_payments'] ) && $payment_options['sofort_payments'] ) {
			$applepay_settings            = get_option( 'peachpay_peachpay_stripe_sofort_settings', array() );
			$applepay_settings['enabled'] = 'yes';
			update_option( 'peachpay_peachpay_stripe_sofort_settings', $applepay_settings );
		}

		update_option( 'peachpay_stripe_gateway_migration', 1 );
	}
}
