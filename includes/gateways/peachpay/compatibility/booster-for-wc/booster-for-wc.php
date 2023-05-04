<?php
/**
 * Support for the Booster for Woocommerce Plugin
 * Plugin: https://booster.io/
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Used as a key for storing any module meta data on the peachpay cart.
 */
const BOOSTER_DATA_KEY = 'wcj_data';

/**
 * Initialize different active modules for peachpay compatibility.
 */
function peachpay_booster_init() {

	if ( isset( WCJ()->modules['product_addons'] ) && WCJ()->modules['product_addons']->is_enabled() ) {
		include_once PEACHPAY_ABSPATH . 'compatibility/booster-for-wc/booster-product-addons.php';
	}

	if ( isset( WCJ()->modules['shipping_description'] ) && WCJ()->modules['shipping_description']->is_enabled() ) {
		include_once PEACHPAY_ABSPATH . 'compatibility/booster-for-wc/booster-shipping-description.php';
	}

	// Initialize module support.
	do_action( 'peachpay_booster_module_init' );
}
add_action( 'peachpay_init_compatibility', 'peachpay_booster_init' );

