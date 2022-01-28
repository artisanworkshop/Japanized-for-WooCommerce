<?php
/**
 * Support for the All Products for WooCommerce Subscriptions Plugin
 * Plugin: https://woocommerce.com/products/all-products-for-woocommerce-subscriptions/
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initialize peachpay support for All Products for WooCommerce Subscriptions Plugin.
 */
function peachpay_wcsatt_init() {
	// Depends on WC Subscriptions.
	if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
		add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcsatt_filter_add_cart_item_meta', 10, 2 );
		add_filter( 'peachpay_calculate_carts', 'peachpay_wcsatt_calculate_recurring_carts', 11, 1 );

	}
}
add_action( 'peachpay_init_compatibility', 'peachpay_wcsatt_init' );

/**
 * Filters cart item meta data for the peachpay endpoints.
 *
 * @param array $pp_cart_item Peachpay cart line item.
 * @param array $wc_line_item Woocommerce cart line item.
 */
function peachpay_wcsatt_filter_add_cart_item_meta( $pp_cart_item, $wc_line_item ) {
	$wc_product = $wc_line_item['data'];

	if ( WCS_ATT_Product::is_subscription( $wc_product ) ) {
		$pp_cart_item['is_subscription']           = true;
		$pp_cart_item['subscription_price_string'] = WC_Subscriptions_Product::get_price_string( $wc_product );
	}

	return $pp_cart_item;
}

/**
 * Calculates and gathers totals for recurring carts.
 *
 * @param array $calculated_carts Carts calculated to be shown in the peachpay modal.
 */
function peachpay_wcsatt_calculate_recurring_carts( $calculated_carts ) {
	WC_Subscriptions_Cart::calculate_subscription_totals( WC()->cart->get_total(), WC()->cart );

	if ( is_array( WC()->cart->recurring_carts ) || is_object( WC()->cart->recurring_carts ) ) {
		foreach ( WC()->cart->recurring_carts as $key => $cart ) {
			if ( ! peachpay_wcsatt_get_subscription_in_cart( $cart ) ) {
				continue;
			}

			$calculated_carts[ $key ] = peachpay_build_cart_response( $key, $cart );

			$subscription_product = peachpay_wcsatt_get_subscription_in_cart( $cart );
			$scheme               = WCS_ATT_Product_Schemes::get_subscription_scheme( $subscription_product, 'object' );

			$calculated_carts[ $key ]['cart_meta']['subscription'] = array(
				'length'          => $scheme->get_length(),
				'period'          => $scheme->get_period(),
				'period_interval' => $scheme->get_interval(),
				'first_renewal'   => '',
			);
		}
	}

	return $calculated_carts;
}

/**
 * Gets the first subscription product in a cart.
 *
 * @param \WC_Cart $cart A given cart.
 */
function peachpay_wcsatt_get_subscription_in_cart( $cart ) {

	$wc_cart = $cart->get_cart();

	foreach ( $wc_cart as $wc_line_item ) {
		if ( WCS_ATT_Product::is_subscription( $wc_line_item['data'] ) ) {
			return $wc_line_item['data'];
		}
	}
}
