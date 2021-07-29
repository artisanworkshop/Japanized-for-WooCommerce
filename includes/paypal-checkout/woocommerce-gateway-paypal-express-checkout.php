<?php
/**
 * Copyright (c) 2019 PayPal, Inc.
 *
 * The name of the PayPal may not be used to endorse or promote products derived from this
 * software without specific prior written permission. THIS SOFTWARE IS PROVIDED ``AS IS'' AND
 * WITHOUT ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
 */
/**
 * Following file customized for Japanese Market by Shohei Tanaka
 * includes/class-wc-gateway-ppec-cart-handler.php : row 261
 * includes/class-wc-gateway-ppec-client.php : row 916 and 1028 : Change by Shohei
 * includes/class-wc-gateway-ppec-plugin.php : some rows add comment 'Change by Shohei'
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WC_Gateway_PPEC_Plugin', false ) ) {
	define( 'WC_GATEWAY_PPEC_JP4WC_VERSION', '2.1.2' );

	/**
	 * Return instance of WC_Gateway_PPEC_Plugin.
	 *
	 * @return WC_Gateway_PPEC_Plugin
	 */
	function wc_gateway_ppec() {
		static $plugin;

		if ( ! isset( $plugin ) ) {
			require_once 'includes/class-wc-gateway-ppec-plugin.php';

			$plugin = new WC_Gateway_PPEC_Plugin( __FILE__, WC_GATEWAY_PPEC_JP4WC_VERSION );
		}

		return $plugin;
	}

	wc_gateway_ppec()->maybe_run();
}
/**
 * Adds the WooCommerce Inbox option on plugin activation
 *
 * @since 2.1.2
 */
if ( ! function_exists( 'add_woocommerce_inbox_variant' ) ) {
    function add_woocommerce_inbox_variant() {
        $option = 'woocommerce_inbox_variant_assignment';

        if ( false === get_option( $option, false ) ) {
            update_option( $option, wp_rand( 1, 12 ) );
        }
    }
}
register_activation_hook( __FILE__, 'add_woocommerce_inbox_variant' );
