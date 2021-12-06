<?php
/**
 * Sets up and defines the PeachPay rest api for calculating the cart details.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const PP_CURRENCY_CODE     = 'currency_code';
const PP_CART              = 'cart';
const PP_COUPONS           = 'coupons';
const PP_GIFT_CARDS        = 'gift_cards';
const PP_SELECTED_SHIPPING = 'selected_shipping';
const PP_SHIPPING_LOCATION = 'shipping_location';

/**
 * Returns the tax information for a given location and set of products.
 *
 * @param WP_REST_Request $request RestAPI object.
 */
function peachpay_cart_calculation( $request ) {
	try {
		ob_start();

		// Process data in any way in the future.
		$request_data = array(
			'currency_code'     => $request[ PP_CURRENCY_CODE ],
			'cart'              => $request[ PP_CART ],
			'coupons'           => $request[ PP_COUPONS ],
			'gift_cards'        => $request[ PP_GIFT_CARDS ],
			'selected_shipping' => $request[ PP_SELECTED_SHIPPING ],
			'shipping_location' => array(
				'country'  => $request[ PP_SHIPPING_LOCATION ]['country'],
				'state'    => $request[ PP_SHIPPING_LOCATION ]['state'],
				'postcode' => $request[ PP_SHIPPING_LOCATION ]['postcode'],
				'city'     => $request[ PP_SHIPPING_LOCATION ]['city'],
			),
		);

		peachpay_rebuild_wc_cart( $request_data[ PP_CART ], $request_data[ PP_SHIPPING_LOCATION ] );
		peachpay_set_selected_shipping_methods( $request_data[ PP_SELECTED_SHIPPING ] );

		if ( isset( $request_data[ PP_COUPONS ] ) && ! empty( $request_data[ PP_COUPONS ] ) ) {
			foreach ( $request_data[ PP_COUPONS ]   as $coupon_code ) {
				WC()->cart->apply_coupon( $coupon_code );
			}
		}

		/**
		 * Allows for special actions to be performed before calculating the carts. Perhaps like applying present gift cards.
		 *
		 * @param array $data The information being used to calculate the cart.
		 */
		do_action( 'peachpay_calculate_carts_init', $request_data );

		WC()->cart->calculate_totals();

		/**
		* Builds an array of different cart calculations for a particular root cart. Allows for
		* subscription recurring carts to be calculated and loosely coupled.
		*
		* @param array The array of calculated cart.
		* @param WC_Cart The main Woocommerce cart.
		*/
		$cart_calculations = (array) apply_filters( 'peachpay_calculate_carts', array( '0' => peachpay_build_cart_response( '0', WC()->cart, $request_data[ PP_CURRENCY_CODE ] ) ) );

		/**
		 * Allows for cleanup after calculating carts. Currently needed for resetting PW Gift Card session between request.
		 *
		 * Things that may be a good thing to cleanup also may be cart itself and any notices
		 */
		do_action( 'peachpay_calculate_carts_cleanup' );

		$result = array(
			'success' => true,
			'notices' => wc_get_notices(),
			'data'    => array(
				'cart_calculation_record' => $cart_calculations,
			),
		);

		ob_get_clean();
		return $result;

	} catch ( Exception $error ) {
		$result = array(
			'success'       => false,
			'error_message' => $error->getMessage(), // Only reported to peachpay sentry??
			'notices'       => wc_get_notices(),
		);

		ob_get_clean();
		return $result;
	}
}


