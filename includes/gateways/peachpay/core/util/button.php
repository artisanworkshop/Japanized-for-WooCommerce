<?php
/**
 * PeachPay Button API
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Reset button preferences to the defaults
 */
function peachpay_reset_button() {
	$options = get_option( 'peachpay_button_options' );

	$options['button_color']                     = '#ff876c';
	$options['button_icon']                      = 'lock';
	$options['button_border_radius']             = 5;
	$options['peachpay_button_text']             = '';
	$options['button_sheen']                     = false;
	$options['button_fade']                      = false;
	$options['disable_default_font_css']         = false;
	$options['hide_on_product_page']             = false;
	$options['button_hide_payment_method_icons'] = false;

	$options['product_button_alignment']  = 'left';
	$options['product_button_position']   = 'beforebegin';
	$options['button_width_product_page'] = 220;

	$options['cart_button_alignment']  = 'full';
	$options['button_width_cart_page'] = 220;

	$options['checkout_button_alignment']  = 'center';
	$options['button_width_checkout_page'] = 320;

	$options['floating_button_size']       = 70;
	$options['floating_button_icon_size']  = 35;
	$options['floating_button_alignment']  = 'left';
	$options['floating_button_bottom_gap'] = 45;
	$options['floating_button_side_gap']   = 45;
	// phpcs:ignore
	// This one is not prefixed with floating button because peachpay_button_hide_html() assumes the option name starts with disabled_
	$options['disabled_floating_button'] = false;

	update_option( 'peachpay_button_options', $options );
}

/**
 * Gets the icon for the floating PeachPay button and defaults to the shopping
 * cart, unlike the icon setting in Button Preferences which defaults to the
 * lock symbol.
 */
function peachpay_button_icon_url() {
	$icon   = peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' );
	$result = '"' . peachpay_url( '' ) . 'public/img/shopping-cart-solid.svg"';

	switch ( $icon ) {
		case ( 'lock' ):
			$result = '"' . peachpay_url( '' ) . 'public/img/lock.svg"';
			break;
		case ( 'baseball' ):
			$result = '"' . peachpay_url( '' ) . 'public/img/baseball-ball-solid.svg"';
			break;
		case ( 'arrow' ):
			$result = '"' . peachpay_url( '' ) . 'public/img/chevron-circle-right-solid.svg"';
			break;
		case ( 'mountain' ):
			$result = '"' . peachpay_url( '' ) . 'public/img/skre.svg"';
			break;
		case ( 'bag' ):
			$result = '"' . peachpay_url( '' ) . 'public/img/briefcase-solid.svg"';
			break;
	}

	return $result;
}

add_filter( 'woocommerce_add_to_cart_fragments', 'peachpay_update_cart_count' );

/**
 * Updates the floating button item count whenever items are added to the cart.
 *
 * @param array $fragments The HTML fragments that we can update.
 */
function peachpay_update_cart_count( $fragments ) {
	$count                       = WC()->cart->cart_contents_count < 99 ? WC()->cart->cart_contents_count : '99+';
	$fragments['#pp-item-count'] = '<span href="' . wc_get_cart_url() . '" id="pp-item-count">' . $count . '</span>';
	return $fragments;
}

/**
 * Render the floating PeachPay button.
 */
function peachpay_render_floating_button() {
	$width  = 'width:' . peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_size', '70' ) . 'px;';
	$height = 'height:' . peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_size', '70' ) . 'px;';
	$color  = '--button-color:' . peachpay_get_settings_option( 'peachpay_button_options', 'button_color', '#FF876C' ) . ';';
	$style  = $width . $height . $color;

	$icon_width  = 'width:' . peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_icon_size', '35' ) . 'px;';
	$icon_height = 'height:' . peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_icon_size', '35' ) . 'px;';
	$icon_size   = $icon_width . $icon_height;
	$icon_url    = peachpay_button_icon_url();

	$left_gap       = 'left:' . peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_side_gap', '45' ) . 'px;';
	$right_gap      = 'right:' . peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_side_gap', '45' ) . 'px;';
	$bottom_gap     = peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_bottom_gap', '45' );
	$float_position = peachpay_get_settings_option( 'peachpay_button_options', 'floating_button_alignment' ) === 'left' ? $left_gap : $right_gap;
	$hide_button    = peachpay_is_test_mode() && ! ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ? 'hide' : '';

	$pp_button = '
	<div id="pp-button-container" class="pp-float-container ' . $hide_button . '" style="position: fixed; bottom: ' . $bottom_gap . 'px; z-index: 9999999999; ' . $float_position . '">
		<button id="pp-button" class="pp-button-float" type="button" style=' . $style . '>
			<div id="pp-button-content">
				<img id="button-icon-shop" style=' . $icon_size . ' class="pp-btn-symbol" src=' . $icon_url . '/>
			</div>
		</button>
		<div class="item-count">
			<span href="' . wc_get_cart_url() . '" id="pp-item-count">' . WC()->cart->cart_contents_count . '</span>
		</div>
		<div id="empty-cart-msg" class="hide">Cart is empty!</div>
	</div>';
	// phpcs:ignore
	echo $pp_button;
}
