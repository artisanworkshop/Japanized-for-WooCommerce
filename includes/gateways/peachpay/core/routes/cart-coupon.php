<?php
/**
 * Sets up and defines the PeachPay rest api endpoints.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gets information about a given coupon or returns a WP_Error if the coupon does not exist.
 *
 * @param WP_REST_Request $request The current http request.
 */
function peachpay_coupon_rest( WP_REST_Request $request ) {
	$code    = urldecode( $request['code'] );
	$args    = array(
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'desc',
		'post_type'      => 'shop_coupon',
		'post_status'    => 'publish',
	);
	$coupons = get_posts( $args );
	foreach ( $coupons as $coupon ) {
		if ( strcasecmp( $coupon->post_title, $code ) === 0 ) {
			return ( new WC_Coupon( $coupon->ID ) )->get_data();
		}
	}
	return new WP_Error( 'no_coupon', 'This coupon does not exist.', array( 'status' => 404 ) );
}
