<?php
/**
 * PeachPay shortcode implementation.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay Shortcode.
 *
 * @param array $atts Shortcode Attributes.
 */
function peachpay_shortcode( $atts ) {
	$attributes = shortcode_atts(
		array(
			'product_id' => null,
		),
		$atts
	);

	if ( is_null( $attributes['product_id'] ) ) {
		return;
	}

	$product = wc_get_product( (int) $attributes['product_id'] );

	if ( is_null( $product ) || ! $product ) {
		return;
	}

	wp_localize_script(
		'pp-button-shortcode',
		'peachpayShortcodeData',
		array( 'product_id' => (int) $attributes['product_id'] )
	);

	$button_text = peachpay_get_settings_option( 'peachpay_button_options', 'peachpay_button_text', peachpay_get_translated_text( 'button_text' ) );
	$dark        = gethostname() === 'www.blazecandles.co' ? '-dark' : '';
	$spinner_url = peachpay_url( 'public/img/spinner' . $dark . '.svg' );
	$width       = 'width:' . peachpay_get_settings_option( 'peachpay_button_options', 'button_width_product_page', '220' ) . 'px;';
	$color       = '--button-color:' . peachpay_get_settings_option( 'peachpay_button_options', 'button_color', '#FF876C' ) . ';';
	$style       = $width . $color;
	$hide_button = peachpay_is_test_mode() && ! ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ? 'hide' : '';

	$output = '
	<div id="pp-button-container-shortcode" class="button-container pp-button-container ' . $hide_button . '">
		<button id="pp-button-shortcode" class="pp-button" type="button" style=' . $style . '>
		<img src=' . $spinner_url . ' id="loading-spinner" class="pp-spinner hide">
		<div id="pp-button-content">
			<span id="pp-button-text">' . $button_text . '</span>
		</div>
		</button>
	</div>';

	return $output;
}
