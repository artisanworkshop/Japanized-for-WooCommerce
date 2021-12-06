<?php
/**
 * PeachPay Cart API
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the applied gift cards on the cart.
 */
function peachpay_cart_applied_gift_cards() {
	/**
	 * Gets the applied gift cards applied to a cart.
	 *
	 * @param array $applied_gift_cards The array of applied gift cards.`
	 */
	return (array) apply_filters( 'peachpay_cart_applied_gift_cards', array() );
}

/**
 * Gets a specific gift card with a gift card number.
 *
 * @param string $card_number The gift card number to find.
 */
function peachpay_cart_applied_gift_card( $card_number ) {
	/**
	 * Filters out a specific gift card.
	 *
	 * @param array $gift_card The selected gift card.
	 * @param string $card_number The selected gift card card number.
	 */
	return (array) apply_filters( 'peachpay_cart_applied_gift_card', array(), $card_number );
}

/**
 * Gets applied gift cards and how much the gift cards were applied.
 *
 * @param WC_Cart $cart The woocommerce cart.
 * @param string  $currency_code Currency to use while converting.
 */
function peachpay_cart_applied_gift_card_record( $cart, $currency_code ) {
	$record = array();

	/**
	 * Builds a record of gift cards and how much each gift card was applied.
	 *
	 * @param array $record The object containing applied gift cards.
	 * @param WC_Cart $cart A given cart that may have gift cards applied toward it.
	 */
	$record = apply_filters( 'peachpay_cart_applied_gift_cards_record', $record, $cart, $currency_code );

	return $record;
}

/**
 * Returns a record of coupons and the applied amount on the given cart to send to the peachpay modal.
 *
 * @param WC_Cart $cart A cart to get applied coupons.
 * @param string  $currency_code Currency to use while converting.
 */
function peachpay_cart_applied_coupon_record( $cart, $currency_code ) {
	$result = array();
	foreach ( $cart->get_applied_coupons() as $coupon_code ) {
		$result[ $coupon_code ] = peachpay_convert_currency_rest_api( floatval( $cart->get_coupon_discount_amount( $coupon_code ) ), $currency_code );
	}
	return $result;
}

/**
 * Gets a cart fees record for sending to the peachpay modal.
 *
 * @param WC_Cart $cart A Woocommerce cart.
 * @param string  $currency_code Currency to use while converting.
 */
function peachpay_cart_applied_fee_record( $cart, $currency_code ) {
	$result = array();

	foreach ( $cart->get_fees() as $_ => $fee ) {
		$result[ $fee->name ] = peachpay_convert_currency_rest_api( floatval( $fee->total ), $currency_code );
	}

	return $result;
}

/**
 * Gets a record of available shipping options to display in the Peachpay Modal
 *
 * @param string $cart_key The given cart key.
 * @param array  $calculated_shipping_packages Shipping package to get shipping options from.
 * @param string $currency_code Currency to use while converting.
 */
function peachpay_cart_shipping_package_record( $cart_key, $calculated_shipping_packages, $currency_code ) {
	$result = array();
	foreach ( $calculated_shipping_packages as $package_index => $package ) {
		$result[ $package_index ] = array(
			'package_name'    => peachpay_shipping_package_name( $cart_key, $package_index, $package ),
			'selected_method' => peachpay_get_selected_shipping_method( $cart_key, $package_index, $package ),
			'methods'         => peachpay_package_shipping_options( $package, $currency_code ),
		);
	}
	return $result;
}

/**
 * Gets the title of the package.
 *
 * @param string $cart_key A given cart key.
 * @param int    $package_index A given package index.
 * @param array  $package A calculated package array.
 */
function peachpay_shipping_package_name( $cart_key, $package_index, $package ) {

	if ( '0' === $cart_key ) {
		return apply_filters( 'woocommerce_shipping_package_name', 'Shipping', $package_index, $package );
	}

	return 'Recurring Shipment';
}
/**
 * Gathers subtotal, coupons, fees, shipping + options, and the total for a given cart.
 *
 * @param string   $cart_key The given cart key.
 * @param \WC_Cart $cart A Woocommerce cart to gather information about for the peachpay modal.
 * @param string   $currency_code Currency to use while converting.
 */
function peachpay_build_cart_response( $cart_key, $cart, $currency_code = 'USD' ) {
	$result = array(
		'package_record' => peachpay_cart_shipping_package_record( $cart_key, WC()->shipping->calculate_shipping( $cart->get_shipping_packages() ), $currency_code ),
		'cart'           => array(),
		'summary'        => array(
			'fees_record'      => peachpay_cart_applied_fee_record( $cart, $currency_code ),
			'coupons_record'   => peachpay_cart_applied_coupon_record( $cart, $currency_code ),
			'gift_card_record' => peachpay_cart_applied_gift_card_record( $cart, $currency_code ),
			'subtotal'         => peachpay_convert_currency_rest_api( floatval( $cart->get_subtotal() ), $currency_code ),
			'total_shipping'   => peachpay_convert_currency_rest_api( floatval( $cart->get_shipping_total() ), $currency_code ),
			'total_tax'        => peachpay_convert_currency_rest_api( floatval( $cart->get_total_tax() ), $currency_code ),
			'total'            => peachpay_convert_currency_rest_api( floatval( $cart->get_total( 'edit' ) ), $currency_code ),
		),
		'cart_meta'      => array(
			'is_virtual' => ! $cart->needs_shipping(),
		),
	);

	return $result;
}
