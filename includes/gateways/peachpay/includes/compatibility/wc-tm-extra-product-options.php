<?php
/**
 * Compatibility for plugin: https://codecanyon.net/item/woocommerce-extra-product-options/7908619
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TM_EPO_DATA_KEY = 'tm_epo_data';

/**
 * Initialize PeachPay support for the TM Extra Product Options plugin.
 */
function peachpay_tmepo_init() {
	if ( is_plugin_active( 'woocommerce-tm-extra-product-options/tm-woo-extra-product-options.php' ) ) {
		add_filter( 'peachpay_cart_page_line_item', 'peachpay_tmepo_filter_add_cart_item_meta', 10, 2 );
		add_filter( 'peachpay_rebuild_wc_line_item', 'peachpay_tmepo_filter_rebuild_wc_line_item', 10, 2 );
	}
}
add_action( 'peachpay_init_compatibility', 'peachpay_tmepo_init' );

/**
 * Adds custom meta data properties that are usually added by the plugin to the
 * WooCommerce line item, to the PeachPay line item.
 *
 * @param array $pp_cart_item PeachPay cart line item.
 * @param array $wc_line_item WooCommerce cart line item.
 */
function peachpay_tmepo_filter_add_cart_item_meta( $pp_cart_item, $wc_line_item ) {
	if ( ! isset( $wc_line_item['tmhasepo'] ) || $wc_line_item['tmhasepo'] === 0 ) {
		return $pp_cart_item;
	}

	$pp_cart_item[ TM_EPO_DATA_KEY ] = array(
		'tmhasepo'                           => $wc_line_item['tmhasepo'],
		'tmcartepo'                          => $wc_line_item['tmcartepo'],
		'tmcartfee'                          => $wc_line_item['tmcartfee'],
		'tmpost_data'                        => $wc_line_item['tmpost_data'],
		'tmsubscriptionfee'                  => $wc_line_item['tmsubscriptionfee'],
		'tmdata'                             => $wc_line_item['tmdata'],
		'tm_epo_product_original_price'      => $wc_line_item['tm_epo_product_original_price'],
		'tm_epo_options_prices'              => $wc_line_item['tm_epo_options_prices'],
		'tm_epo_product_price_with_options'  => $wc_line_item['tm_epo_product_price_with_options'],
		'tm_epo_options_static_prices'       => $wc_line_item['tm_epo_options_static_prices'],
		'tm_epo_options_static_prices_first' => $wc_line_item['tm_epo_options_static_prices_first'],
		'addons'                             => $wc_line_item['addons'],
		'data'                               => $wc_line_item['data'],
	);

	return $pp_cart_item;
}

/**
 * Rebuilds WooCommerce line item for use with PeachPay endpoints.
 *
 * @param array $wc_line_item The WooCommerce line item to rebuild.
 * @param array $pp_line_item The PeachPay line item to source information from.
 */
function peachpay_tmepo_filter_rebuild_wc_line_item( $wc_line_item, $pp_line_item ) {
	if ( array_key_exists( TM_EPO_DATA_KEY, $pp_line_item ) ) {
		$wc_line_item = array_merge( $wc_line_item, $pp_line_item[ TM_EPO_DATA_KEY ] );
	}

	return $wc_line_item;
}
