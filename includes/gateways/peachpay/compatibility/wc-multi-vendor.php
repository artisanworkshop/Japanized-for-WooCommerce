<?php
/**
 * Support for the Multivendor Marketplace Solution for WooCommerce
 * Plugin: https://wordpress.org/plugins/dc-woocommerce-multi-vendor/
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Adds meta data to the peachpay cart item for multi-vendors
 *
 * @param array $pp_cart_item The peachpay cart line item.
 * @param array $wc_line_item The Woocommerce cart line item.
 */
function peachpay_wcmv_add_cart_page_item_meta( array $pp_cart_item, array $wc_line_item ) {
	$product = $wc_line_item['data'];

	// TODO: I did not notice that the below returns an array, but sometimes an.
	// object ? Need to look into this more when we revisit the multi vendor plugins .
	$pp_cart_item['vendor_id']   = get_wcmp_product_vendors( $product->get_id() )->id;
	$pp_cart_item['vendor_name'] = get_wcmp_product_vendors( $product->get_id() )->user_data->data->display_name;

	return $pp_cart_item;
}
add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcmv_add_cart_page_item_meta', 10, 2 );
