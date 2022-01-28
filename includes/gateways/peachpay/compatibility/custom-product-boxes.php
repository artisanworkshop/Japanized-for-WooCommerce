<?php
/**
 * Support for the Custom Product Boxes WooCommercePlugin
 * Plugin: https://woocommerce.com/products/custom-product-boxes/
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

add_filter( 'peachpay_cart_page_line_item', 'peachpay_cpb_cart_page_line_item', 10, 2 );

/**
 * Update line item for children and parents of custom product boxes.
 *
 * @param array $pp_cart_item The PeachPay cart item.
 * @param array $wc_line_item The WooCommerce line item.
 * @return array The updated PeachPay cart item.
 */
function peachpay_cpb_cart_page_line_item( array $pp_cart_item, $wc_line_item ) {
	if ( array_key_exists( 'cpb_custom_product_parent_key', $wc_line_item ) ) {
		// Child Product.
		$pp_cart_item['is_part_of_bundle'] = true;
		$pp_cart_item['price']             = $wc_line_item['line_subtotal'];
		$pp_cart_item['subtotal']          = $wc_line_item['line_subtotal'];
		$pp_cart_item['total']             = $wc_line_item['line_total'];

	} elseif ( array_key_exists( 'cpb-box-add-to-cart', $wc_line_item ) ) {
		// Parent product.
		$pp_cart_item['price']    = $wc_line_item['line_subtotal'] / $wc_line_item['quantity'];
		$pp_cart_item['subtotal'] = $wc_line_item['line_subtotal'];
		$pp_cart_item['total']    = $wc_line_item['line_total'];
	}

	return $pp_cart_item;
}
