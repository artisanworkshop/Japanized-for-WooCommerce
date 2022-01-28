<?php
/**
 * Japanized for WooCommerce Uninstall
 *
 * @package woocommerce-for-japan
 * @category Core
 * @author Artisan Workshop
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

//delete option settings
delete_option('wc4jp-bankjp');
delete_option('woocommerce_bankjp_settings');
delete_option('wc4jp-postofficebank');
delete_option('woocommerce_postofficebankjp_settings');
delete_option('wc4jp-atstore');
delete_option('wc4jp-company-name');
delete_option('wc4jp-yomigana');
delete_option('woocommerce_cod_extra_charge_name');
delete_option('woocommerce_cod_extra_charge_amount');
delete_option('woocommerce_cod_extra_charge_max_cart_value');
delete_option('woocommerce_cod_extra_charge_calc_taxes');
delete_option('woocommerce_cod_settings');

/**
 * PeachPay uninstall script.
 *
 * @package PeachPay
 */

$general_options = get_option( 'peachpay_general_options' );

// When data_retention is true, that means they checked the box to remove plugin
// data upon uninstall.
if ( is_array( $general_options ) && $general_options['data_retention'] ) {
	// Old, deprecated options which were all in one group.
	delete_option( 'peachpay_options' );

	// Current groups of options.
	delete_option( 'peachpay_general_options' );
	delete_option( 'peachpay_payment_options' );
	delete_option( 'peachpay_button_options' );
	delete_option( 'peachpay_related_products_options' );

	// Floating options.
	delete_option( 'peachpay_payment_settings_initialized' );
	delete_option( 'peachpay_migrate_button_position' );
	delete_option( 'peachpay_connected_stripe_account' );
	delete_option( 'peachpay_paypal_signup' );
	delete_option( 'peachpay_api_access_denied' );
	delete_option( 'peachpay_valid_key' );
	delete_option( 'peachpay_deny_add_to_cart_redirect' );
	delete_option( 'peachpay_migrated_to_enable_stripe_checkbox' );
}
