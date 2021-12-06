<?php
/**
 * Support for the YayCurrency Plugin
 * Plugin: https://wordpress.org/plugins/yaycurrency/
 *
 * @package PeachPay
 */

use Yay_Currency\WooCommerceCurrency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates a price based on a given currency.
 *
 * @param float  $price .
 * @param string $currency_code .
 */
function yc_calculate_price( float $price, string $currency_code ) {
	$obj            = WooCommerceCurrency::getInstance();
	$apply_currency = $obj->get_currency_by_id( yc_get_currency_id( $currency_code ) );

	if ( 'percentage' === $apply_currency['fee']['type'] ) {
		$rate_after_fee = (float) $apply_currency['rate'] + ( (float) $apply_currency['rate'] * ( (float) $apply_currency['fee']['value'] / 100 ) );
	} else {
		$rate_after_fee = (float) $apply_currency['rate'] + (float) $apply_currency['fee']['value'];
	}
	$price = ( (float) $price * $rate_after_fee );

	if ( 'disabled' !== $apply_currency['roundingType'] ) {

		$rounding_type   = $apply_currency['roundingType'];
		$rounding_value  = $apply_currency['roundingValue'];
		$subtract_amount = $apply_currency['subtractAmount'];

		switch ( $rounding_type ) {
			case 'up':
				$price = ceil( $price / $rounding_value ) * $rounding_value - $subtract_amount;
				return $price;
			case 'down':
				$price = floor( $price / $rounding_value ) * $rounding_value - $subtract_amount;
				return $price;
			case 'nearest':
				$price = round( $price / $rounding_value ) * $rounding_value - $subtract_amount;
				return $price;
			default:
				return;
		}
	}
	return $price;
}

/**
 * Gets the Yaycurrency currency Id for a given currency.
 *
 * @param string $currency_code .
 */
function yc_get_currency_id( string $currency_code ) {
	$obj        = WooCommerceCurrency::getInstance();
	$currencies = $obj->get_currencies_post_type();

	if ( $currencies ) {
		foreach ( $currencies as $currency ) {
			if ( $currency->post_title === $currency_code ) {
				return $currency->ID;
			}
		}
	}

	return 0;
}

/**
 * Transforms values from default site currency to a given currency.
 *
 * @param float  $amount .
 * @param string $to_currency .
 * */
function peachpay_currency_switcher_yaycurrency_convert( float $amount, string $to_currency ) {
	return yc_calculate_price( $amount, $to_currency );
}
add_filter( 'peachpay_convert_from_default_currency', 'peachpay_currency_switcher_yaycurrency_convert', 10, 2 );

/**
 * Gets the current rounding type setting for the given currency.
 *
 * @param string $default_rounding_type The default rounding type.
 * @param string $currency_code The currency code to check.
 */
function peachpay_currency_switcher_yaycurrency_rounding_type( string $default_rounding_type, string $currency_code ) {
	$obj = WooCommerceCurrency::getInstance();
	return strtolower( $obj->get_currency_by_id( yc_get_currency_id( $currency_code ) )['roundingType'] );
}
add_filter( 'peachpay_currency_rounding_type', 'peachpay_currency_switcher_yaycurrency_rounding_type', 10, 2 );

/**
 * Indicates to peachpay what the overridden currency is currently.
 *
 * @param string $currency_code The current overridden currency code to fall back on.
 */
function peachpay_currency_switcher_yaycurrency_overridden_code( string $currency_code ) {
	$obj = WooCommerceCurrency::getInstance();
	if ( $obj->get_current_and_default_currency()['current_currency'] && $obj->get_current_and_default_currency()['current_currency']['currency'] ) {
		$currency_code = $obj->get_current_and_default_currency()['current_currency']['currency'];
	}
	return $currency_code;
}
add_filter( 'peachpay_currency_overridden_code', 'peachpay_currency_switcher_yaycurrency_overridden_code', 10, 1 );
