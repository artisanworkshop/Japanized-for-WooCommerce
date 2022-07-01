<?php
/**
 * Compatibility adapter for booster shipping method descriptions.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initlizes the PeachPay Booster shipping desctription adapter.
 */
function peachpay_booster_sd_module_init() {
	add_filter( 'peachpay_shipping_method_description', 'peachpay_booster_sd_filter', 10, 2 );
}
add_action( 'peachpay_booster_module_init', 'peachpay_booster_sd_module_init' );

/**
 * Filter function for getting the shipping description.
 *
 * @param string $description The existing description for the shipping method.
 * @param array  $shipping_method The shipping method to check if it has a description.
 */
function peachpay_booster_sd_filter( $description, $shipping_method ) {
	if ( class_exists( 'WCJ_Shipping_Descriptions' ) ) {
		$booster_label = new WCJ_Shipping_Descriptions();
		$custom_label  = $booster_label->is_enabled() ? $booster_label->shipping_description( '', $shipping_method ) : '';
		return $custom_label;
	}

	return $description;
}
