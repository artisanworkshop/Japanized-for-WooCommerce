<?php
/**
 * Shipping Utilities for PeachPay
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
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
function peachpay_shipping_package_chosen_option( $cart_key, $package_key, $package ) {
	$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

	if ( ! isset( $chosen_methods ) ) {
		return wc_get_default_shipping_method_for_package( $package_key, $package, '' );
	}

	if ( ! isset( $chosen_methods[ $package_key ] ) ) {
		return wc_get_default_shipping_method_for_package( $package_key, $package, '' );
	}

	return $chosen_methods[ $package_key ];
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
 * @param array $calculated_shipping_package The packages array with each package having a calculated "rate" key.
 */
function peachpay_package_shipping_options( $calculated_shipping_package ) {
	$shipping_options = array();
	foreach ( $calculated_shipping_package['rates'] as $full_method_id => $shipping_method ) {

		// The force all key is added by https://woocommerce.com/document/advanced-flat-rate-shipping-method-for-woocommerce/
		// They have a setting to combine all shipping methods into one but they add the key even when merchants aren't using that setting.
		// To fix it for Smart Lighting Industries (https://www.smart-light.co.uk) we just ignore it.
		// If other merchants use the above plugin and the force all setting this will need to be rethought.
		if ( 'forceall' === $full_method_id ) {
			continue;
		}

		// we use full_method_id and not $shipping_method->method_id because the former
		// includes a "sub" ID which is necessary if there is more than one flat_rate
		// shipping, for example.
		$shipping_options[ $full_method_id ] = array(
			'title'       => $shipping_method->get_label(),
			'total'       => floatval( $shipping_method->get_cost() ) + ( get_option( 'woocommerce_tax_display_cart' ) === 'incl' ? floatval( $shipping_method->get_shipping_tax() ) : 0 ),
			'description' => peachpay_shipping_method_description( $shipping_method ),
		);
	}

	return $shipping_options;
}

/**
 * Collects shipping options to choose from.
 *
 * @param array $calculated_shipping_package The packages array with each package having a calculated "rate" key.
 */
function peachpay_shipping_package_options( $calculated_shipping_package ) {
	$shipping_options = array();
	foreach ( $calculated_shipping_package['rates'] as $full_method_id => $shipping_method ) {
		$shipping_options[ $full_method_id ] = array(
			'id'     => $full_method_id,
			'label'  => $shipping_method->get_label(),
			'amount' => floatval( $shipping_method->get_cost() ) + ( get_option( 'woocommerce_tax_display_cart' ) === 'incl' ? floatval( $shipping_method->get_shipping_tax() ) : 0 ),
			'detail' => peachpay_shipping_method_description( $shipping_method ),
		);
	}

	return $shipping_options;
}

/**
 * Gets the shipping method description if one exists.
 *
 * @param array $shipping_method Type of shipping method.
 */
function peachpay_shipping_method_description( $shipping_method ) {
	return apply_filters( 'peachpay_shipping_method_description', '', $shipping_method );
}
