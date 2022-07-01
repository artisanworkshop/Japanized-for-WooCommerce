<?php
/**
 * Support for the Country Based Restrictions for WooCommerce
 * Plugin: https://wordpress.org/plugins/woo-product-country-base-restrictions/
 *
 * @phpcs:disable
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initialize filters for peachpay compatibility.
 */
function peachpay_cbr_init() {
	add_filter( 'peachpay_validation_checks', 'peachpay_update_cbr_country', 1, 2 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_cbr_init' );

function peachpay_update_cbr_country( $request ) {
	$request_data = $request->get_body_params();
	// set this cookie no matter what as if cbr is active this cookie is used over other forms of verification :)
	if( array_key_exists( 'billing_country', $request_data ) && !get_option( 'wpcbr_force_geo_location' ) ) {
		setcookie( 'country', $request_data['billing_country'] , 0, '/' );
	}
}