<?php
/**
 * Defines a function that reset the state of the button preferences to its initial state.
 *
 * @package PeachPay
 */

/**
 * Reset button preferences to the defaults
 */
function peachpay_reset_button() {
	$options                                     = get_option( 'peachpay_button_options' );
	$options['button_color']                     = '#ff876c';
	$options['button_icon']                      = 'lock';
	$options['button_border_radius']             = 5;
	$options['peachpay_button_text']             = '';
	$options['button_sheen']                     = false;
	$options['hide_on_product_page']             = false;
	$options['button_hide_payment_method_icons'] = false;
	$options['product_button_position']          = 'left';
	$options['button_width_product_page']        = 220;
	$options['cart_button_position']             = 'full';
	$options['button_width_cart_page']           = 220;
	$options['checkout_button_position']         = 'center';
	$options['button_width_checkout_page']       = 320;
	update_option( 'peachpay_button_options', $options );
}
