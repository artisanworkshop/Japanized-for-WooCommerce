<?php
/**
 * Support for the Woocommerce Product Addons Plugin
 * Plugin: https://woocommerce.com/products/product-add-ons/
 *
 * Supports product page and cart page without currency switchers. Only supports product page with Yaycurrency.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initializes Woocommerce Product Addon compatibility.
 */
function peachpay_wcpa_init() {
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcpa_add_cart_page_item_meta', 10, 2 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_wcpa_init' );

/**
 * Adds any needed meta data to cart item if has any product addons
 *
 * @since 1.47.0
 * @param array $pp_cart_item The item to add meta details related to product addons.
 * @param array $wc_line_item   Cart Line item data.
 */
function peachpay_wcpa_add_cart_page_item_meta( array $pp_cart_item, array $wc_line_item ) {
	if ( isset( $wc_line_item['addons'] ) && count( $wc_line_item['addons'] ) > 0 ) {
		$selected_addons = $wc_line_item['addons'];

		foreach ( $selected_addons as &$selected_addon ) {
			if ( isset( $selected_addon['price'] ) && '' !== $selected_addon['price'] ) {
				$selected_addon['price'] = floatval( $selected_addon['price'] );
			}
		}
		unset( $selected_addon );

		$pp_cart_item['wc_addons'] = $selected_addons;
	}

	return $pp_cart_item;
}
