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
 * Collects request info for changing product quantity
 */
function peachpay_collect_quantity_request_info() {
	// phpcs:disable
	if ( ! isset( $_POST['cart_item_key'] )
	|| ! isset( $_POST['quantity'] )
	|| ! isset( $_POST['absolute'] )
	) {

		$response                  = peachpay_cart_calculation();
		$response['error_message'] = '400 - Bad Request. Valid `key`,`value`, and `absolute` is required.';

		wp_send_json( $response );
		wp_die();
	}

	return array(
		'cart_item_key'    => strval( $_POST['cart_item_key'] ),
		'quantity'         => intval( $_POST['quantity'] ),
		'absolute'         => $_POST['absolute'],
	);

	// phpcs:enable
}

/**
 * Changes the cart quantity.
 */
function peachpay_wc_ajax_product_quantity_changer() {
	try {

		$request = peachpay_collect_quantity_request_info();

		$cart_contents = WC()->cart->get_cart();

		if ( isset( $cart_contents[ $request['cart_item_key'] ] ) ) {
			$cart_item    = $cart_contents[ $request['cart_item_key'] ];
			$new_quantity = intval( $cart_item['quantity'] ) + $request['quantity'];
			if ( 'true' === $request['absolute'] ) {
				$new_quantity = $request['quantity'];
			}

			if ( 0 === $new_quantity ) {
				WC()->cart->remove_cart_item( $request['cart_item_key'] );
			} else {
				WC()->cart->set_quantity( $request['cart_item_key'], $new_quantity, true );
			}
		}

		// Send updated cart.
		$response = peachpay_cart_calculation();
		wp_send_json( $response );

	} catch ( Exception $error ) {

		wp_send_json(
			array(
				'success'       => false,
				'error_message' => $error->getMessage(),
				'notices'       => wc_get_notices(),
			)
		);
	}

}
