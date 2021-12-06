<?php
/**
 * Support for the All Products for WooCommerce Subscriptions Plugin
 * Plugin: https://woocommerce.com/products/all-products-for-woocommerce-subscriptions/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WCSATT_DATA_KEY = 'wcsatt_data';

/**
 * Initialize peachpay support for All Products for WooCommerce Subscriptions Plugin.
 */
function peachpay_wcsatt_init() {
	// Depends on WC Subscriptions.
	if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
		add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcsatt_filter_add_cart_item_meta', 10, 2 );

		add_filter( 'peachpay_rebuild_wc_line_item', 'peachpay_wcsatt_filter_rebuild_wc_line_item', 10, 2 );
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
		$pp_cart_item[ WCSATT_DATA_KEY ]           = $wc_line_item[ WCSATT_DATA_KEY ];
	}

	return $pp_cart_item;
}

/**
 * Rebuilds woocommerce line item for use with peachpay endpoints.
 *
 * @param array $wc_line_item The woocommerce line item to rebuild.
 * @param array $pp_line_item The peachpay line item to source information from.
 */
function peachpay_wcsatt_filter_rebuild_wc_line_item( $wc_line_item, $pp_line_item ) {
	if ( array_key_exists( WCSATT_DATA_KEY, $pp_line_item ) ) {
		$wc_line_item[ WCSATT_DATA_KEY ] = $pp_line_item[ WCSATT_DATA_KEY ];
		WCS_ATT_Cart::apply_subscription_scheme( $wc_line_item );
	}

	return $wc_line_item;
}

/**
 * Calculates and gathers totals for recurring carts.
 *
 * @param array $calculated_carts Carts calculated to be shown in the peachpay modal.
 */
function peachpay_wcsatt_calculate_recurring_carts( $calculated_carts ) {
	WC_Subscriptions_Cart::calculate_subscription_totals( WC()->cart->get_total(), WC()->cart );

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
