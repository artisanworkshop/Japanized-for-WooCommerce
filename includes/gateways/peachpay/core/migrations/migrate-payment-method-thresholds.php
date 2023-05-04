<?php
/**
 * Migrates payment settings option to set the minimum/maximum cart totals to show a payment method.
 *
 * This migration can be deleted after all below merchants have updated the plugin.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/** Sets the default transaction limits if the transaction threshold settings haven't been migrated. */
function peachpay_migrate_payment_method_thresholds() {
	$default_transaction_threshholds = (object) array(
		'amazonpay'               => (object) array(
			'pm_min'           => 'n/a',
			'pm_max'           => 'n/a',
			'merchant_min'     => 'n/a',
			'merchant_max'     => 'n/a',
			'default_currency' => 'USD',
		),
		'peachpay_purchase_order' => (object) array(
			'pm_min'           => 'n/a',
			'pm_max'           => 'n/a',
			'merchant_min'     => 'n/a',
			'merchant_max'     => 'n/a',
			'default_currency' => 'USD',
		),
	);

	if ( ! get_option( 'peachpay_migrated_payment_method_thresholds' ) ) {
		foreach ( $default_transaction_threshholds as $payment_method => $min_max_obj ) {
			foreach ( $min_max_obj as $key => $value ) {
				peachpay_set_settings_option( 'peachpay_payment_options', $payment_method . '_' . $key, $value );
			}
		}
		update_option( 'peachpay_migrated_payment_method_thresholds', 1 );
	}
}
