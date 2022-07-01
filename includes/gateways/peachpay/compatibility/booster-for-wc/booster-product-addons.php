<?php
/**
 * Support for the Booster for Woocommerce Product Addons Module
 * Plugin:
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initlizes the Booster Product Addon Support for peachpay.
 */
function peachpay_booster_pa_module_init() {
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_booster_pa_cart_page_line_item', 10, 2 );
}
add_action( 'peachpay_booster_module_init', 'peachpay_booster_pa_module_init' );

/**
 * Adds meta data to a peachpay line item on the cart page.
 *
 * @param array $pp_line_item The PeachPay Line item.
 * @param array $wc_line_item The WC cart line item.
 */
function peachpay_booster_pa_cart_page_line_item( $pp_line_item, $wc_line_item ) {

	/**
	 * Booster Product Addons Support.
	 * Keys: wcj_pa_extra_price, wcj_pa_total_extra_price, wcj_product_all_products_addons_label_*,wcj_product_all_products_addons_price_*
	 */
	if ( array_key_exists( 'wcj_pa_extra_price', $wc_line_item ) || array_key_exists( 'wcj_pa_total_extra_price', $wc_line_item ) ) {
		$wc_product = $wc_line_item['data'];

		$pp_line_item['display_price'] = peachpay_product_display_price( $wc_product );
		$pp_line_item['price']         = peachpay_product_price( $wc_product );

		$addons = WCJ()->modules['product_addons']->get_product_addons( peachpay_product_parent_id( $wc_product->get_id() ) );
		foreach ( $addons as $addon ) {
			if ( $wc_line_item[ $addon['price_key'] ] ) {
				array_push(
					$pp_line_item['meta_data'],
					array(
						'key'   => $addon['label_value'],
						'value' => $addon['price_value'],
					)
				);
			}
		}
	}

	return $pp_line_item;
}
