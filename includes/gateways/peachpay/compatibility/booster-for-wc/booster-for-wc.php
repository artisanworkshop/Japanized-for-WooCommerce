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

// Module Keys.
const BPA_MODULE = 'product_addons';


/**
 * Initialize different active modules for peachpay compatibility.
 */
function peachpay_booster_init() {

	if ( WCJ()->modules[ BPA_MODULE ]->is_enabled() ) {
		include_once PEACHPAY_ABSPATH . 'compatibility/booster-for-wc/booster-product-addons.php';
	}

	// Initialize module support.
	do_action( 'peachpay_booster_module_init' );
}
add_action( 'peachpay_init_compatibility', 'peachpay_booster_init' );

