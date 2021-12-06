<?php
/**
 * PeachPay Currency API
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gathers all information about the current currency that is selected needed for the frontend.
 */
function peachpay_get_currency_info() {
	return array(
		'code'                => peachpay_currency_default_code(),
		'overridden_code'     => peachpay_currency_overridden_code(),
		'symbol'              => peachpay_currency_symbol(),
		'position'            => peachpay_currency_position(),
		'thousands_separator' => peachpay_currency_thousands_separator(),
		'decimal_separator'   => peachpay_currency_decimal_separator(),
		'number_of_decimals'  => peachpay_currency_decimals(),
		'rounding'            => peachpay_currency_rounding_type(),
		// 'to'                  => '1', // Round to nearest 1.00,0.10,0.05... etc. Some currency switchers use this but for now we will not worry about it. (Modal does not support it yet)
	);
}

/**
 * Gets the PeachPay default currency code for the site.
 */
function peachpay_currency_default_code() {
	return apply_filters( 'peachpay_currency_default_code', get_woocommerce_currency() );
}

/**
 * Gets the PeachPay overridden currency code for the site or defaults to the default sites currency.
 */
function peachpay_currency_overridden_code() {
	return apply_filters( 'peachpay_currency_overridden_code', get_woocommerce_currency() );
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

/**
 * Converts a given amount(In base currency) to a target currency using any available currency switchers.
 *
 * @param float  $amount The amount to convert.
 * @param string $to_currency The currency to convert too.
 */
function peachpay_convert_currency_rest_api( $amount, $to_currency ) {
	if ( ! isset( $to_currency ) || '' === $to_currency ) {
		return $amount;
	}

	if ( is_plugin_active( 'currency-switcher-woocommerce/currency-switcher-woocommerce.php' ) ) {
		return peachpay_currency_switcher_woocommerce_convert( $amount, $to_currency );
	}

	if (
		is_plugin_active( 'woocommerce-jetpack/woocommerce-jetpack.php' ) ||
		is_plugin_active( 'booster-plus-for-woocommerce/booster-plus-for-woocommerce.php' ) ||
		is_plugin_active( 'booster-plus-for-woocommerce-1/booster-plus-for-woocommerce.php' )
	) {
		return peachpay_booster_for_woocommerce_currency_convert( $amount, $to_currency );
	}

	// Convert currencies using this filter going forward.
	return apply_filters( 'peachpay_convert_from_default_currency', $amount, $to_currency );
}

/**
 * Plugin support for a currency switcher(I am not sure which one)
 *
 * @param float  $amount Amount to convert.
 * @param string $to_currency The currency code to target.
 */
function peachpay_currency_switcher_woocommerce_convert( $amount, $to_currency ) {
	if ( 'yes' !== get_option( 'alg_wc_currency_switcher_enabled', 'yes' ) ) {
		return $amount;
	}

	$result                    = $amount;
	$currency_based_on_address = alg_get_current_currency_code();

	// The plugin has a switcher widget where the user can change the currency.
	// The REST API endpoint has no knowledge of their session, so we must receive
	// that data explicitly.
	if ( $to_currency !== $currency_based_on_address && '' !== $to_currency ) {
		// Undo the applied exchange rate that was incorrectly assumed.
		$result /= alg_wc_cs_get_currency_exchange_rate( $currency_based_on_address );
		// Apply the exchange rate that the customer selected on the screen.
		$result *= alg_wc_cs_get_currency_exchange_rate( $to_currency );
	}

	return round( $result, 2 );
}

/**
 * Plugin support for booster currency switching.
 *
 * @param float  $amount Amount to convert.
 * @param string $to_currency Target currency code.
 */
function peachpay_booster_for_woocommerce_currency_convert( $amount, $to_currency ) {
	$result = $amount;
	if ( '' !== $to_currency ) {
		$result *= wcj_get_currency_exchange_rate( 'multicurrency', $to_currency );
	}
	return round( $result, 2 );
}
