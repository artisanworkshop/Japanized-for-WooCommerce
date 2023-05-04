<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.5.4
 * @package 	Virtual Order
 * @author 		ArtisanWorkshop
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Virtual_Order{
	
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		// Show delivery date and time at checkout page
		add_filter( 'woocommerce_checkout_fields', array( $this, 'jp4wc_virtual_order_checkout_fields'), 10 );
	}

	/**
	 * Hide Checkout Billing Fields if Virtual Product at Cart
	 *
	 */
	public function jp4wc_virtual_order_checkout_fields($fields){
		$only_virtual = true;
		if ( ! is_null( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				// Check if there are non-virtual products
				if ( ! $cart_item['data']->is_virtual() ) {
					$only_virtual = false;
				}
			}
		}
		if( $only_virtual ){
			if( get_option( 'wc4jp-billing_postcode' ) )unset( $fields['billing']['billing_postcode'] );
			if( get_option( 'wc4jp-billing_state' ) )unset( $fields['billing']['billing_state'] );
			if( get_option( 'wc4jp-billing_city' ) )unset( $fields['billing']['billing_city'] );
			if( get_option( 'wc4jp-billing_address_1' ) )unset( $fields['billing']['billing_address_1'] );
			if( get_option( 'wc4jp-billing_address_2' ) )unset( $fields['billing']['billing_address_2'] );
			if( get_option( 'wc4jp-billing_phone' ) )unset( $fields['billing']['billing_phone'] );
		}
		return $fields;
	}
}

new JP4WC_Virtual_Order();
