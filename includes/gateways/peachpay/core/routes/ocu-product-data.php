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
 * Collects request info for collecting OCU product data.
 */
function peachpay_get_ocu_product_id() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$ocu_product_id = '';
	if ( isset( $_POST['product_id'] ) ) {
		$ocu_product_id = sanitize_text_field( wp_unslash( $_POST['product_id'] ) );
	}
	// phpcs:enable
	return $ocu_product_id;
}

/**
 * Collects the OCU product data.
 */
function peachpay_wc_ajax_ocu_product_data() {
	try {

		$ocu_product_id = peachpay_get_ocu_product_id();
		$ocu_product    = wc_get_product( $ocu_product_id );

		$response = array(
			'success' => true,
			'data'    => array(
				'ocu_product_img'   => wp_get_attachment_image_url( $ocu_product->get_image_id(), 'full' ),
				'ocu_product_name'  => $ocu_product->get_name(),
				'ocu_product_price' => $ocu_product->get_price_html(),
			),
		);

		wp_send_json( $response );

	} catch ( Exception $error ) {

		wp_send_json_error(
			array(
				'success'       => false,
				'error_message' => $error->getMessage(),
				'notices'       => wc_get_notices(),
			)
		);
	}

}
