<?php
/**
 * Rest-API for Peachpay Variation Details.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Peachpay endpoint for gathering details about a dynamically chosen variation.
 *
 * Endpoint: wp-json/peachpay/v1/variation/details?id=:int:
 *
 * @param WP_REST_Request $request The rest api object.
 */
function peachpay_variation_details_rest( WP_REST_Request $request ) {
	// Cart is not needed but with some plugin compatibility the cart is a dependency.
	peachpay_wc_load_cart();

	$id      = $request['id'];
	$product = wc_get_product( $id );
	return array(
		'variation_title' => peachpay_product_variation_name( $id ),
		'stock_status'    => peachpay_product_stock_status( $id ),
		'display_price'   => peachpay_product_display_price( $product ),
		'price'           => peachpay_product_price( $product ),
		'attributes'      => peachpay_product_variation_attributes( $id ),
		'image'           => peachpay_product_image( $product ),
		'full_wc_product' => $product->get_data(),
	);
}
