<?php
/**
 * Defines functions that set default options upon a fresh install.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Sets the default values for options that 1) should default to a specific value when its corresponding field is left empty in the settings and/or 2) should default to a specific value upon a fresh install.
 */
function peachpay_set_default_options() {
	peachpay_set_default_button_options();
}

/**
 * Sets the default values for button options.
 */
function peachpay_set_default_button_options() {
	$button_options = get_option( 'peachpay_express_checkout_button', array() );

	if ( ! get_option( 'peachpay_set_default_button_settings' ) ) {
		$button_options['display_on_product_page']             = 0;
		$button_options['cart_page_enabled']                   = 0;
		$button_options['checkout_page_enabled']               = 0;
		$button_options['mini_cart_enabled']                   = 0;
		$button_options['floating_button_enabled']             = 0;
		$button_options['button_display_payment_method_icons'] = 1;
		$button_options['display_checkout_outline']            = 1;
		$button_options['product_button_alignment']            = 'left';
		$button_options['product_button_mobile_position']      = 'default';
		$button_options['product_button_position']             = 'beforebegin';
		$button_options['cart_button_alignment']               = 'full';
		$button_options['floating_button_icon']                = 'shopping_cart';
		$button_options['floating_button_alignment']           = 'right';
		update_option( 'peachpay_set_default_button_settings', 1 );
	}
	update_option( 'peachpay_express_checkout_button', $button_options );
}
