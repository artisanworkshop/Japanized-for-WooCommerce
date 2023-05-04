<?php
/**
 * PeachPay API Access status.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Updates the plugin api access status.
 *
 * @param array $plugin_capabilities The capabilities for the plugin.
 */
function peachpay_api_key_admin_action( $plugin_capabilities ) {
	// WooCommerce API permissions.
	if ( peachpay_plugin_has_capability( 'woocommerce', $plugin_capabilities ) ) {
		update_option( 'peachpay_valid_key', 1 );
		delete_option( 'peachpay_api_access_denied' );
	} else {
		update_option( 'peachpay_valid_key', 0 );
	}
}
add_action( 'peachpay_settings_admin_action', 'peachpay_api_key_admin_action' );
