<?php
/**
 * PeachPay Cart API
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Returns an array of products that are in the "cart".
 */
function peachpay_get_cart() {

	if ( is_null( WC()->cart ) ) {
		return array();
	}

	return peachpay_make_cart_from_wc_cart( WC()->cart->get_cart() );
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
 */
function peachpay_cart_applied_gift_card_record( $cart ) {
	$record = array();

	/**
	 * Builds a record of gift cards and how much each gift card was applied.
	 *
	 * @param array $record The object containing applied gift cards.
	 * @param WC_Cart $cart A given cart that may have gift cards applied toward it.
	 */
	$record = apply_filters( 'peachpay_cart_applied_gift_cards_record', $record, $cart );

	return $record;
}

/**
 * Returns a record of coupons and the applied amount on the given cart to send to the peachpay modal.
 *
 * @param WC_Cart $cart A cart to get applied coupons.
 */
function peachpay_cart_applied_coupon_record( $cart ) {
	$result = array();
	foreach ( $cart->get_applied_coupons() as $coupon_code ) {
		$result[ $coupon_code ] = floatval( $cart->get_coupon_discount_amount( $coupon_code ) );
	}
	return $result;
}

/**
 * Gets a cart fees record for sending to the peachpay modal.
 *
 * @param WC_Cart $cart A Woocommerce cart.
 */
function peachpay_cart_applied_fee_record( $cart ) {
	$result = array();

	foreach ( $cart->get_fees() as $_ => $fee ) {
		$result[ $fee->name ] = floatval( $fee->total );
	}

	return $result;
}

/**
 * Gets a record of available shipping options to display in the Peachpay Modal
 *
 * @param string $cart_key The given cart key.
 * @param array  $calculated_shipping_packages Shipping package to get shipping options from.
 */
function peachpay_cart_shipping_package_record( $cart_key, $calculated_shipping_packages ) {
	$result = array();
	foreach ( $calculated_shipping_packages as $package_index => $package ) {
		$result[ $package_index ] = array(
			'package_name'    => peachpay_shipping_package_name( $cart_key, $package_index, $package ),
			'selected_method' => peachpay_get_selected_shipping_method( $cart_key, $package_index, $package ),
			'methods'         => peachpay_package_shipping_options( $package ),
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
		return apply_filters( 'woocommerce_shipping_package_name', __( 'Shipping', 'peachpay-for-woocommerce' ), $package_index, $package );
	}

	return __( 'Recurring Shipment', 'peachpay-for-woocommerce' );
}
/**
 * Gathers subtotal, coupons, fees, shipping + options, and the total for a given cart.
 *
 * @param string   $cart_key The given cart key.
 * @param \WC_Cart $cart A Woocommerce cart to gather information about for the peachpay modal.
 */
function peachpay_build_cart_response( $cart_key, $cart ) {
	$result = array(
		'package_record' => peachpay_cart_shipping_package_record( $cart_key, WC()->shipping->calculate_shipping( $cart->get_shipping_packages() ) ),
		'cart'           => peachpay_get_cart(),
		'summary'        => array(
			'fees_record'      => peachpay_cart_applied_fee_record( $cart ),
			'coupons_record'   => peachpay_cart_applied_coupon_record( $cart ),
			'gift_card_record' => peachpay_cart_applied_gift_card_record( $cart ),
			'subtotal'         => floatval( $cart->get_subtotal() ),
			'total_shipping'   => floatval( $cart->get_shipping_total() ),
			'total_tax'        => floatval( $cart->get_total_tax() ),
			'total'            => floatval( $cart->get_total( 'display' ) ),
		),
		'cart_meta'      => array(
			'is_virtual' => ! $cart->needs_shipping(),
		),
	);

	return $result;
}

/**
 * Gets a cart calculation.
 *
 * @param array|null $order_info Information about a specific order to update.
 */
function peachpay_cart_calculation( $order_info = null ) {
	if ( is_array( $order_info ) ) {

		if ( '' !== $order_info['shipping_location']['country'] ) {
			WC()->customer->set_shipping_location(
				$order_info['shipping_location']['country'],
				$order_info['shipping_location']['state'],
				$order_info['shipping_location']['postcode'],
				$order_info['shipping_location']['city']
			);
		}

		if ( is_array( $order_info['selected_shipping'] ) && count( $order_info['selected_shipping'] ) > 0 ) {
			peachpay_set_selected_shipping_methods( $order_info['selected_shipping'] );
		}
	}

	WC()->cart->calculate_totals();

	/**
	* Builds an array of different cart calculations for a particular root cart. Allows for
	* subscription recurring carts to be calculated and loosely coupled.
	*
	* @param array The array of calculated cart.
	* @param WC_Cart The main Woocommerce cart.
	*/
	$cart_calculations = (array) apply_filters( 'peachpay_calculate_carts', array( '0' => peachpay_build_cart_response( '0', WC()->cart ) ) );

	$result = array(
		'success' => true,
		'notices' => wc_get_notices(),
		'data'    => array(
			'cart_calculation_record' => $cart_calculations,
			'shipping_location'       => array(
				'country'  => WC()->customer->get_shipping_country(),
				'state'    => WC()->customer->get_shipping_state(),
				'postcode' => WC()->customer->get_shipping_postcode(),
				'city'     => WC()->customer->get_shipping_city(),
			),
		),
	);

	// This is to prevent the page from spamming customers with add to carts notices and other notices created by cart calculation.
	wc_clear_notices();

	return $result;
}
