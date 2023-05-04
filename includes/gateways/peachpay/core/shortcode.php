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

	$button_text      = peachpay_get_settings_option( 'peachpay_express_checkout_button', 'peachpay_button_text', peachpay_get_translated_text( 'button_text' ) );
	$width            = 'width:' . peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_width_product_page', '220' ) . 'px;';
	$background_color = '--pp-button-background-color:' . peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_color', PEACHPAY_DEFAULT_BACKGROUND_COLOR ) . ';';
	$text_color       = '--pp-button-text-color:' . peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_text_color', PEACHPAY_DEFAULT_TEXT_COLOR ) . ';';
	$border_radius    = 'border-radius: ' . peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_border_radius', 5 ) . 'px;';
	$style            = $width . $background_color . $text_color . $border_radius;
	$default_fonts    = peachpay_get_settings_option( 'peachpay_express_checkout_button', 'disable_default_font_css', false );
	$button_effect    = peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_effect', 'fade' );
	$hide_button      = peachpay_is_test_mode() && ! ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ? 'hide' : '';
	$display_cards    = peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_display_payment_method_icons', false );
	$product_id       = (int) $attributes['product_id'];

	$output = '<div class="button-container pp-button-container ' . $hide_button . '">
		<div style="width: 100%; margin: 5px 0; display: none;">
		</div>
		<button data-product-id="' . $product_id . '" class="pp-button pp-product-page pp-button-shortcode ' . ( $default_fonts ? '' : 'pp-button-default-font' ) . ' ' . ( esc_html( $button_effect ) === 'fade' ? 'pp-effect-fade' : '' ) . '" type="button" style="display: block; ' . esc_html( $style ) . 'font-size: 16px;">
		<svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" viewBox="0 0 128 128" xml:space="preserve" class="pp-spinner hide"><g><circle cx="16" cy="64" r="16" fill-opacity="1"/><circle cx="16" cy="64" r="16" fill-opacity="0.67" transform="rotate(45,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.42" transform="rotate(90,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.2" transform="rotate(135,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(180,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(225,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(270,64,64)"/><circle cx="16" cy="64" r="16" fill-opacity="0.12" transform="rotate(315,64,64)"/><animateTransform attributeName="transform" type="rotate" values="0 64 64;315 64 64;270 64 64;225 64 64;180 64 64;135 64 64;90 64 64;45 64 64" calcMode="discrete" dur="800ms" repeatCount="indefinite"></animateTransform></g></svg>
			<div class="pp-button-content">
				<span>' . esc_html( $button_text ) . '</span>
				<svg class="button-icon-shortcode"></svg>
			</div>
		</button>
		<div id="payment-methods-container" class="cc-company-logos ' . ( $display_cards ? '' : 'hide' ) . '">
			' . PeachPay_Payment::available_gateway_icons() . '
		</div>
	</div>';

	return $output;
}
