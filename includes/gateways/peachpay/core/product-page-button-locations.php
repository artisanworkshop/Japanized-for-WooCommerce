<?php
/**
 * This file is for allowing the addition of where the user wants the button to be placed to peachpay_data so the front end can place accordingly
 *
 * @package peachpay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

add_filter( 'peachpay_script_data', 'peachpay_button_before_after_cart', 20, 1 );

/**
 * Adds to the data passed to the front end to allow specification of Peachpay button location.
 *
 * @param array $data The script data.
 */
function peachpay_button_before_after_cart( $data ) {
	$data['product_page_button_before_after'] = peachpay_get_settings_option( 'peachpay_express_checkout_button', 'product_button_position', 'beforebegin' );

	return $data;
}
