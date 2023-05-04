<?php
/**
 * Sets up and defines the PeachPay rest api endpoints.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Collects variation data and outputs the variation data in its expected format.
 */
function peachpay_get_selected_variation_data() {
	// phpcs:disabled
	if ( ! isset( $_POST['variation_data'] ) ) {
		return;
	}

	$data = array();
	$json = json_decode( sanitize_text_field( wp_unslash( $_POST['variation_data'] ) ) );
	foreach ( $json as $value ) {
		$tmp = array(
			$value[0] => $value[1],
		);
		$data = $data + $tmp;
	}
	// phpcs:enable
	return $data;
}

/**
 * Returns variation id
 *
 * @param int   $product_id Product ID.
 * @param array $attr Selected attribute.
 * @return int
 */
function peachpay_find_matching_product_variation_id( $product_id, $attr ) {
	return ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation( new \WC_Product( $product_id ), $attr );
}

/**
 * Add variable product to cart.
 */
function peachpay_wc_ajax_add_variable_product() {
	$product_id = peachpay_get_ocu_product_id();
	$data       = peachpay_get_selected_variation_data();
	$id         = peachpay_find_matching_product_variation_id( $product_id, $data );
	$name       = wc_get_product( $product_id )->get_name();

	if ( $id ) {
		WC()->cart->add_to_cart( $product_id, 1, $id, $data );
		// Send updated cart.
		$response = peachpay_cart_calculation();
		wc_add_notice( '&ldquo;' . $name . '&rdquo; has been added to your cart.', 'success' );
		wp_send_json( $response );

	} else {
		wp_send_json_error( 'Product not found', 404 );
	}
}
