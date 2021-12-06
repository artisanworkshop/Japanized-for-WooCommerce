<?php
/**
 * Support for the PPOM for WooCommerce Plugin
 * Plugin: https://wordpress.org/plugins/woocommerce-product-addon/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds meta data to the peachpay cart item for PPOM
 *
 * @param array $pp_cart_item The peachpay cart line item.
 * @param array $wc_line_item The Woocommerce cart line item.
 */
function peachpay_ppom_add_cart_page_cart_item_meta( array $pp_cart_item, array $wc_line_item ) {
	if ( isset( $wc_line_item['ppom'] ) ) {
		// meta_data are key value pairs that are seen on each product.
		// in the order dashboard for a particular order.
		foreach ( $wc_line_item['ppom']['fields'] as $key => $value ) {
			// id is produced by the plugin, but we don't need it.
			if ( 'id' === $key ) {
				continue;
			}
			array_push(
				$pp_cart_item['meta_data'],
				array(
					'key'   => $key,
					'value' => $value,
				)
			);
		}
	}

	return $pp_cart_item;
}
add_filter( 'peachpay_cart_page_line_item', 'peachpay_ppom_add_cart_page_cart_item_meta', 10, 2 );

