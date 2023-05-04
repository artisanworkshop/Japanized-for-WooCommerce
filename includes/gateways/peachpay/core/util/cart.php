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
 * Gets cart line items.
 *
 * @param WC_Cart $cart .
 */
function peachpay_cart_line_items( $cart ) {

	$pp_line_item = array();

	foreach ( $cart->get_cart() as $wc_line_item ) {

		$wc_product     = $wc_line_item['data'];
		$pp_line_item[] = array(
			'id'       => strval( $wc_line_item['key'] ),
			'label'    => peachpay_get_parent_name( $wc_product->get_id() ),
			'amount'   => strval( $wc_product->get_price( 'view' ) ),
			'quantity' => intval( $wc_line_item['quantity'] ),
		);
	}

	return $pp_line_item;
}

/**
 * Gets subtotal line
 *
 * @param WC_Cart $cart .
 */
function peachpay_cart_subtotal_line( $cart ) {
	return array(
		'label'  => __( 'Subtotal', 'peachpay-for-woocommerce' ),
		'amount' => strval( $cart->get_subtotal() ),
	);
}

/**
 * Gets total line
 *
 * @param WC_Cart $cart .
 */
function peachpay_cart_total_line( $cart ) {
	return array(
		'label'  => __( 'Total', 'peachpay-for-woocommerce' ),
		'amount' => strval( $cart->get_total( 'raw' ) ),
	);
}

/**
 * Gets Shipping line
 *
 * @param WC_Cart $cart .
 */
function peachpay_cart_shipping_lines( $cart ) {

	return array(
		array(
			'label'  => __( 'Shipping', 'peachpay-for-woocommerce' ),
			'amount' => strval( $cart->get_shipping_total() ),
		),
	);
}

/**
 * Gets discount line
 *
 * @param WC_Cart $cart .
 */
function peachpay_cart_discount_lines( $cart ) {
	if ( $cart->get_discount_total() === 0 ) {
		return array();
	}

	return array(
		array(
			'label'  => __( 'Discounts', 'peachpay-for-woocommerce' ),
			'amount' => strval( $cart->get_discount_total() ),
		),
	);
}

/**
 * Gets fee line
 *
 * @param WC_Cart $cart .
 */
function peachpay_cart_fee_lines( $cart ) {
	return array(
		array(
			'label'  => __( 'Fees', 'peachpay-for-woocommerce' ),
			'amount' => strval( $cart->get_fee_total() ),
		),
	);
}

/**
 * Gets fee line
 *
 * @param WC_Cart $cart .
 * @param string  $view What type of keys to use:
 *  - display: uses 'amount' as the key for value
 *  - order: uses 'total' as the key for values.
 */
function peachpay_cart_tax_lines( $cart, $view = 'display' ) {
	if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) && method_exists( $cart, 'get_tax_totals' ) ) {
		$cart_tax_totals      = $cart->get_tax_totals();
		$formatted_tax_totals = array();

		// Format tax totals based on $view.
		foreach ( $cart_tax_totals as $cart_tax_total ) {
			array_push(
				$formatted_tax_totals,
				array(
					'label' => $cart_tax_total->label,
					'display' === $view ? 'amount' : 'total' => strval( $cart_tax_total->amount ),
				)
			);
		}

		return $formatted_tax_totals;
	} else {
		return array(
			array(
				'label'                                  => __( 'Tax', 'peachpay-for-woocommerce' ),
				'display' === $view ? 'amount' : 'total' => method_exists( $cart, 'get_taxes_total' ) ? strval( $cart->get_taxes_total() ) : '0',
			),
		);
	}
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
			'selected_method' => peachpay_shipping_package_chosen_option( $cart_key, $package_index, $package ),
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
 * Get a WC cart subtotal.
 *
 * @param \WC_Cart $cart Instance of a WC cart to collect information for.
 */
function peachpay_cart_subtotal( $cart ) {
	if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) {
		return floatval( $cart->get_subtotal() ) + floatval( $cart->get_subtotal_tax() );
	} elseif ( 'excl' === get_option( 'woocommerce_tax_display_cart' ) ) {
		return floatval( $cart->get_subtotal() );
	}

}

/**
 * Gathers subtotal, coupons, fees, shipping + options, and the total for a given cart.
 *
 * @param string   $cart_key The given cart key.
 * @param \WC_Cart $cart A Woocommerce cart to gather information about for the peachpay modal.
 */
function peachpay_build_cart_response( $cart_key, $cart ) {
	$result = array(
		'package_record'   => peachpay_cart_shipping_package_record( $cart_key, WC()->shipping->calculate_shipping( $cart->get_shipping_packages() ) ),
		'cart'             => peachpay_get_cart(),
		'summary'          => array(
			'fees_record'      => peachpay_cart_applied_fee_record( $cart ),
			'coupons_record'   => peachpay_cart_applied_coupon_record( $cart ),
			'gift_card_record' => peachpay_cart_applied_gift_card_record( $cart ),
			'subtotal'         => peachpay_cart_subtotal( $cart ),
			'total_shipping'   => floatval( $cart->get_shipping_total() ) + ( get_option( 'woocommerce_tax_display_cart' ) === 'incl' ? floatval( $cart->get_shipping_tax() ) : 0 ),
			'tax_lines'        => peachpay_cart_tax_lines( $cart ),
			'total_tax'        => floatval( $cart->get_total_tax() ),
			'total'            => floatval( $cart->get_total( 'display' ) ),
		),
		'cart_meta'        => array(
			'is_virtual' => peachpay_is_virtual_cart( $cart ),
		),
		'feature_metadata' => apply_filters( 'peachpay_dynamic_feature_metadata', array(), $cart_key, $cart ),
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

		if ( '' !== $order_info['billing_location']['country'] ) {
			WC()->customer->set_billing_location(
				$order_info['billing_location']['country'],
				$order_info['billing_location']['state'],
				$order_info['billing_location']['postcode'],
				$order_info['billing_location']['city']
			);
		}

		if ( '' !== $order_info['shipping_location']['country'] ) {
			WC()->customer->set_shipping_location(
				$order_info['shipping_location']['country'],
				$order_info['shipping_location']['state'],
				$order_info['shipping_location']['postcode'],
				$order_info['shipping_location']['city']
			);
			// Update analytics to match.
			if ( class_exists( 'PeachPay_Analytics_Database' ) && PeachPay_Analytics_Database::should_load() ) {
				PeachPay_Analytics_Database::update_billing( 'country', $order_info['shipping_location']['country'] );
				PeachPay_Analytics_Database::update_billing( 'state', $order_info['shipping_location']['state'] );
				PeachPay_Analytics_Database::update_billing( 'postcode', $order_info['shipping_location']['postcode'] );
				PeachPay_Analytics_Database::update_billing( 'city', $order_info['shipping_location']['city'] );
			}
		}

		if ( is_array( $order_info['selected_shipping'] ) && count( $order_info['selected_shipping'] ) > 0 ) {
			peachpay_set_selected_shipping_methods( $order_info['selected_shipping'] );
		}

		if ( '' !== $order_info['payment_method'] ) {
			WC()->session->set( 'chosen_payment_method', $order_info['payment_method'] );
			WC()->session->set( 'peachpay_payment_method_variation', $order_info['payment_method_variation'] );
			// Update analytics to match.
			if ( class_exists( 'PeachPay_Analytics_Database' ) && PeachPay_Analytics_Database::should_load() ) {
				PeachPay_Analytics_Database::update_payment_method(
					$order_info['payment_method']
				);
			}
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

	$gateways    = WC()->payment_gateways->get_available_payment_gateways();
	$gateway_ids = array_keys( $gateways );

	$result = array(
		'success' => true,
		'notices' => wc_get_notices(),
		'data'    => array(
			'cart_calculation_record' => $cart_calculations,
			'available_gateway_ids'   => $gateway_ids,
		),
	);

	// This is to prevent the page from spamming customers with add to carts notices and other notices created by cart calculation.
	wc_clear_notices();

	return $result;
}

/**
 * Gets cart details formatted for the native checkout. New simplified format!
 */
function peachpay_cart_details() {

	if ( is_null( WC()->cart ) ) {
		return null;
	}

	$result = array(
		'currency'           => peachpay_currency_code(),

		'is_only_free_trial' => floatVal( 0 ) === floatVal( WC()->cart->get_total( '' ) ),
		'needs_shipping'     => WC()->cart->needs_shipping(),
		'shipping_packages'  => array(),

		'item_lines'         => peachpay_cart_line_items( WC()->cart ),

		'subtotal_lines'     => peachpay_cart_subtotal_line( WC()->cart ),
		'shipping_lines'     => peachpay_cart_shipping_lines( WC()->cart ),
		'discount_lines'     => peachpay_cart_discount_lines( WC()->cart ),
		'fee_lines'          => peachpay_cart_fee_lines( WC()->cart ),
		'tax_lines'          => peachpay_cart_tax_lines( WC()->cart ),
		'total_line'         => peachpay_cart_total_line( WC()->cart ),
	);

	if ( WC()->cart->needs_shipping() ) {
		$result['shipping_packages'] = peachpay_cart_shipping_packages( '0', WC()->cart );
	}

	return $result;
}

/**
 * Gets the packages for a cart.
 *
 * @param string  $cart_key A given cart key. Standard cart is '0'.
 * @param WC_Cart $cart The cart to get packages for.
 */
function peachpay_cart_shipping_packages( $cart_key, $cart ) {
	$packages                     = array();
	$calculated_shipping_packages = WC()->shipping->calculate_shipping( $cart->get_shipping_packages() );

	foreach ( $calculated_shipping_packages as $package_index => $package ) {
		$packages[ $package_index ] = array(
			'selected_method' => peachpay_shipping_package_chosen_option( $cart_key, $package_index, $package ),
			'methods'         => peachpay_shipping_package_options( $package ),
		);
	}

	return $packages;
}

/**
 * Checks if the cart contains only virutal products.
 *
 * @param WC_Cart $cart The WC cart to check for non-virtual products.
 */
function peachpay_is_virtual_cart( $cart ) {
	foreach ( $cart->get_cart() as $item ) {
		if ( ! $item['data']->is_virtual() ) {
			return false;
		}
	}
	return true;
}
