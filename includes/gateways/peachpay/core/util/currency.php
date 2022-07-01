<?php
/**
 * PeachPay Currency API
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gathers all information about the current currency that is selected needed for the frontend.
 */
function peachpay_get_currency_info() {
	return array(
		'code'                => peachpay_currency_code(),
		'symbol'              => peachpay_currency_symbol(),
		'position'            => peachpay_currency_position(),
		'thousands_separator' => peachpay_currency_thousands_separator(),
		'decimal_separator'   => peachpay_currency_decimal_separator(),
		'number_of_decimals'  => peachpay_currency_decimals(),
		'rounding'            => peachpay_currency_rounding_type(),
	);
}

/**
 * Gets the PeachPay default currency code for the site.
 */
function peachpay_currency_code() {
	return apply_filters( 'peachpay_currency_code', get_woocommerce_currency() );
}

/**
 * Gets the PeachPay currency symbol
 */
function peachpay_currency_symbol() {
	return apply_filters( 'peachpay_currency_symbol', get_woocommerce_currency_symbol() );
}

/**
 * Gets the PeachPay currency position.
 */
function peachpay_currency_position() {
	// Values are able to be ["left", "right", "left_space", "right_space"].
	return apply_filters( 'peachpay_currency_position', get_option( 'woocommerce_currency_pos' ) );
}

/**
 * Gets the PeachPay currency thousands separator.
 */
function peachpay_currency_thousands_separator() {
	return apply_filters( 'peachpay_currency_thousands_separator', wc_get_price_thousand_separator() );
}

/**
 * Gets the PeachPay currency decimal separator.
 */
function peachpay_currency_decimal_separator() {
	return apply_filters( 'peachpay_currency_decimal_separator', wc_get_price_decimal_separator() );
}

/**
 * Gets the PeachPay currency decimal length
 */
function peachpay_currency_decimals() {
	return apply_filters( 'peachpay_currency_decimals', wc_get_price_decimals() );
}

/**
 * Because there is no dedicated woocommerce setting for the rounding type, this
 * function exist to support the currency switchers that do have that kind of setting.
 */
function peachpay_currency_rounding_type() {
	// Just fallback to "disabled" because woocommerce does not round anything by default but some currency switchers do.
	return apply_filters( 'peachpay_currency_rounding_type', 'disabled', get_woocommerce_currency() );
}

/**
 * Gets the peachpay currency price formatter string.
 */
function peachpay_currency_price_format() {
	return apply_filters( 'peachpay_currency_price_format', get_woocommerce_price_format() );
}
