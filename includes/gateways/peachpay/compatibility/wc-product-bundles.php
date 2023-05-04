<?php
/**
 * Support for the Woocommerce Product Bundles Plugin.
 * Plugin: https://woocommerce.com/products/product-bundles
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initialized support for the plugin Woocommerce Bundles.
 */
function peachpay_wcpb_init() {
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcpb_add_cart_page_cart_item_meta', 10, 2 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_wcpb_init' );

/**
 * Adds meta data to the peachpay cart item.
 *
 * @param array $pp_line_item The peachpay cart line item.
 * @param array $wc_line_item The woocommerce line item.
 */
function peachpay_wcpb_add_cart_page_cart_item_meta( $pp_line_item, $wc_line_item ) {
	if ( array_key_exists( 'bundled_items', $wc_line_item ) ) {
		$pp_line_item['is_bundle'] = true;
	} elseif ( wc_pb_is_bundled_cart_item( $wc_line_item ) ) {
		$pp_line_item['bundled_by']        = $wc_line_item['bundled_by'];
		$pp_line_item['is_part_of_bundle'] = true;
		$pp_line_item['price']             = 0;
		$pp_line_item['subtotal']          = '0';
		$pp_line_item['total']             = '0';
	}

	return $pp_line_item;
}
