<?php
/**
 * PeachPay uninstall script.
 *
 * @package PeachPay
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

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
	delete_option( 'peachpay_advanced_options' );

	// Floating options.
	delete_option( 'peachpay_payment_settings_initialized' );
	delete_option( 'peachpay_migrate_button_position' );
	delete_option( 'peachpay_connected_stripe_account' );
	delete_option( 'peachpay_paypal_signup' );
	delete_option( 'peachpay_api_access_denied' );
	delete_option( 'peachpay_valid_key' );
	delete_option( 'peachpay_deny_add_to_cart_redirect' );
	delete_option( 'peachpay_migrated_to_enable_stripe_checkbox' );
	delete_option( 'peachpay_apple_pay_settings' );
	delete_option( 'peachpay_apple_pay_settings_v2' );

	// Modules.
	delete_option( 'peachpay_currency_options' );
	delete_option( 'peachpay_field_editor' );
	delete_option( 'peachpay_field_editor_additional' );
	delete_option( 'peachpay_field_editor_billing' );
	delete_option( 'peachpay_field_editor_shipping' );
	delete_option( 'peachpay_one_click_upsell_options' );
}
