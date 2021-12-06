<?php
/**
 * Rest-API utilities for PeachPay.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Loads class files needed for using the Woocommerce cart.
 */
function peachpay_wc_load_cart() {
	include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
	include_once WC_ABSPATH . 'includes/class-wc-cart.php';
	if ( is_null( WC()->cart ) ) {
		wc_load_cart();
	}
}

/**
 * Forces the cart class to load in the REST endpoints because it normally does
 * not. It also clears out the cart in case it has already been loaded.
 */
function peachpay_wc_cart_for_rest() {
	peachpay_wc_load_cart();
	WC()->cart->empty_cart();
}

/**
 * Rebuilds the woocommerce cart from the peachpay cart in a consistent manner. Logs errors.
 *
 * @param array $pp_cart The Peachpay cart.
 * @param array $shipping_location The location the cart will be shipped.
 */
function peachpay_rebuild_wc_cart( $pp_cart, $shipping_location = null ) {
	peachpay_wc_cart_for_rest();

	if ( isset( $shipping_location ) ) {
		WC()->customer->set_shipping_location(
			$shipping_location['country'],
			$shipping_location['state'],
			$shipping_location['postcode'],
			$shipping_location['city']
		);
	}

	foreach ( $pp_cart as $pp_line_item ) {

		if ( ! apply_filters( 'peachpay_filter_rebuild_cart', true, $pp_line_item ) ) {
			continue;
		}

		$product_id = $pp_line_item['variation_id'] ? intval( $pp_line_item['variation_id'] ) : intval( $pp_line_item['product_id'] );
		$quantity   = intval( $pp_line_item['quantity'] );
		$variation  = isset( $pp_line_item['variation'] ) ? $pp_line_item['variation'] : array();

		/**
		 * Builds an object of meta data to be added to woocommerce line item.
		 *
		 * @since 4.5.0
		 * @param array     $wc_line_item The wc product line item object.
		 * @param array     $pp_line_item The PeachPay line item data to be used to recreate the wc_line_item.
		 */
		$extra_line_item_data = (array) apply_filters( 'peachpay_rebuild_wc_line_item', array( 'data' => wc_get_product( $product_id ) ), $pp_line_item );
		// The validation added here for WC 5.2.0 doesn't allow passing in the
		// product_id as the variation_id like we have been doing
		// https://github.com/woocommerce/woocommerce/commit/918750fffbbb36974190efeeb4ae0de280756741
		// Set variation_id to the default value in this case.
		peachpay_non_redirect_add_to_cart( $product_id, $quantity, 0, $variation, $extra_line_item_data );
	}

	if ( wc_notice_count( 'error' ) > 0 ) {
		//phpcs:ignore
		error_log( 'Peachpay Cart Rebuild Failure: ' . print_r( wc_get_notices(), true ) );
	}
}
