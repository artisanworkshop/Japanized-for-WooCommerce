<?php
/**
 * Migrates old data retention settings option.
 *
 * This migration can be deleted after all below merchants have updated the plugin.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrate the data retention setting.
 */
function peachpay_migrate_data_retention() {
	if ( get_option( 'peachpay_data_retention_migration', 0 ) === 0 ) {
		$payment_options = get_option( 'peachpay_payment_options', array() );

		if ( isset( $payment_options['data_retention'] ) && $payment_options['data_retention'] ) {
			$setting                   = get_option( 'peachpay_account_data_admin_settings', array() );
			$setting['data_retention'] = 'yes';
			update_option( 'peachpay_account_data_admin_settings', $setting );
		}

		update_option( 'peachpay_data_retention_migration', 1 );
	}
}
