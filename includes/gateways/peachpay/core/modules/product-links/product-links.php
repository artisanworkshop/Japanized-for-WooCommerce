<?php
/**
 * File for handling uploading new products to peachpay product links.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require PEACHPAY_ABSPATH . 'core/modules/product-links/product-links-functions.php';

add_action( 'peachpay_setup_module', 'peachpay_init_product_links' );

/**
 * If there is no key there is no reason to listen for changes in the settings.
 */
function peachpay_init_product_links() {
	if ( ! peachpay_get_settings_option( 'peachpay_product_links', 'key', false ) ) {
		return;
	}
	add_filter( 'update_option_peachpay_product_links', 'peachpay_post_product_links_changes', 1, 2 );
}

/**
 * Listener for changes to product links.
 *
 * @param array $old old settings.
 * @param array $new new settings.
 */
function peachpay_post_product_links_changes( $old, $new ) {
	if ( ! empty( $new ) && ! empty( $new['new_products'] ) ) {
		$data = array();
		foreach ( $new['new_products'] as $id ) {
			$product = wc_get_product( $id );
			array_push( $data, peachpay_get_required_product_info( $product ) );
		}

		$post_data = array(
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'merchantID' => peachpay_get_settings_option( 'peachpay_product_links', 'key', '' ),
					'products'   => $data,
				)
			),
		);

		$url = peachpay_is_local_development_site() ? 'https://headless.peachpay.local' : 'https://fast.peachpay.app';

		$response = wp_remote_post( $url . '/api/addProducts', $post_data );

		$new['new_products'] = null;

		if ( is_wp_error( $response ) ) {
			$new['error'] = 'failed to communicate with the peachpay server please try again later';
		}
		update_option( 'peachpay_product_links', $new );
	}
}
