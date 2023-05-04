<?php
/**
 * Functions and hooks related to not showing peachpay in Merchants shops
 *
 * @package peachpay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}



add_filter( 'peachpay_hide_button_on_checkout_page', 'peachpay_hide_checkout_page_from_settings', 20, 1 );
add_filter( 'peachpay_hide_button_on_cart_page', 'peachpay_hide_cart_page_from_settings', 20, 1 );
add_filter( 'peachpay_hide_button_on_product_page', 'peachpay_hide_product_page', 20, 1 );
add_filter( 'peachpay_script_data', 'peachpay_hide_mini_cart_from_settings', 20, 1 );

/**
 * Hide on product page based on settings.
 *
 * @param boolean $bool if it is hidden or not.
 */
function peachpay_hide_product_page( $bool ) {
	return peachpay_get_settings_option( 'peachpay_express_checkout_button', 'display_on_product_page' ) ? true : $bool;
}

/**
 * Hide on checkout page based on settings
 *
 * @param boolean $bool if it is currently hidden or not.
 */
function peachpay_hide_checkout_page_from_settings( $bool ) {
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_button', 'checkout_page_enabled' ) ) {
		return true;
	}

	return $bool;
}

/**
 * Hide peachpay on the cart page based on merchants settings.
 *
 * @param boolean $bool if it is previously hidden or not.
 */
function peachpay_hide_cart_page_from_settings( $bool ) {
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_button', 'cart_page_enabled' ) ) {
		return true;
	}

	return false;
}

/**
 * Since we place the mini cart in the JS side add a param that hides the mini cart button from the peachpay_script data.
 *
 * @param array $data the data being sent to the browser.
 */
function peachpay_hide_mini_cart_from_settings( $data ) {
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_button', 'mini_cart_enabled' ) ) {
		$data['hide_mini_cart'] = true;
	} else {
		$data['hide_mini_cart'] = false;
	}
	return $data;
}
