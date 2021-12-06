<?php
/**
 * Shipping Utilities for PeachPay
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the selected package shipping method. A WC method exist to do this but
 * it does not take into account renewing carts.
 *
 * @param string $cart_key A given cart key. Standard cart is '0'.
 * @param int    $package_key A given package key.
 * @param array  $package A calculated shipping package array.
 */
function peachpay_get_selected_shipping_method( $cart_key, $package_key, $package ) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

	if ( ! isset( $chosen_methods ) ) {
		return wc_get_default_shipping_method_for_package( $package_key, $package, '' );
	}

	$selected_key = ( '0' === $cart_key ) ? $package_key : $cart_key . '_' . $package_key;
	if ( ! isset( $chosen_methods[ $selected_key ] ) ) {
		return wc_get_default_shipping_method_for_package( $package_key, $package, '' );
	}

	return $chosen_methods[ $selected_key ];
}

/**
 * Sets the selected shipping methods for the peachpay modal cart calculation.
 *
 * @param array $selected_shipping_methods_record The currently selected shipping methods record from the modal.
 */
function peachpay_set_selected_shipping_methods( $selected_shipping_methods_record ) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
	foreach ( $selected_shipping_methods_record as $package_key => $selected_method ) {
		$chosen_methods[ $package_key ] = $selected_method;
	}
	WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
}

/**
 * Collects shipping options to choose from.
 *
 * @param array  $calculated_shipping_package The packages array with each package having a calculated "rate" key.
 * @param string $currency_code The currency code to target.
 */
function peachpay_package_shipping_options( $calculated_shipping_package, $currency_code ) {
	$shipping_options = array();
	foreach ( $calculated_shipping_package['rates'] as $full_method_id => $shipping_rate ) {

		// we use full_method_id and not $shipping_method->method_id because the former
		// includes a "sub" ID which is necessary if there is more than one flat_rate
		// shipping, for example.
		$shipping_options[ $full_method_id ] = array(
			'title' => $shipping_rate->get_label(),
			'total' => peachpay_convert_currency_rest_api( floatval( $shipping_rate->get_cost() ), $currency_code ),
		);
	}

	return $shipping_options;
}
