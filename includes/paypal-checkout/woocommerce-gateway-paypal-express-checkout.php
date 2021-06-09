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
 * includes/class-wc-gateway-ppec-cart-handler.php : row 256
 * includes/class-wc-gateway-ppec-client.php : row 916 and 1028
*/
/**
 * Following file customized for Japanese Market by Shohei Tanaka
 * includes/class-wc-gateway-ppec-plugins.php : some rows add comment 'Change by Shohei'
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Return instance of WC_Gateway_PPEC_Plugin.
 *
 * @return WC_Gateway_PPEC_Plugin
 */
if ( ! class_exists( 'WC_Gateway_PPEC_Plugin', false ) ) {
    define( 'WC_GATEWAY_PPEC_WC4JP_VERSION', '2.0.3' );

    function wc_gateway_ppec()
    {
        static $plugin;

        if (!isset($plugin)) {
            require_once 'includes/class-wc-gateway-ppec-plugin.php';

            $plugin = new WC_Gateway_PPEC_Plugin(__FILE__, WC_GATEWAY_PPEC_WC4JP_VERSION);
        }

        return $plugin;
    }

    wc_gateway_ppec()->maybe_run();
}
