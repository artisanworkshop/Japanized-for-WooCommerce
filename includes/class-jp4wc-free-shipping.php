<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.0.0
 * @package 	Admin Screen
 * @author 		ArtisanWorkshop
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Free Shipping display for Japan, if free shipping situation, only display free shipping
 */
if(get_option('wc4jp-free-shipping')){
    add_filter( 'woocommerce_package_rates', 'hide_shipping_when_free_is_available', 100 );
}

/**
* Hide shipping rates when free shipping is available.
*
* @param array $rates Array of rates found for the package.
* @return array
*/
function hide_shipping_when_free_is_available ( $rates ) {
	$free = array();
	foreach ( $rates as $rate_id => $rate ) {
		if ( 'free_shipping' === $rate->method_id ) {
			$free[ $rate_id ] = $rate;
			break;
		}
	}
	return ! empty( $free ) ? $free : $rates;
}
