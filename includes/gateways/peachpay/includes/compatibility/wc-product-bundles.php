<?php
/**
 * Support for the Woocommerce Product Bundles Plugin.
 * Plugin: https://woocommerce.com/products/product-bundles
 *
 * @package PeachPay
 */


const WCPB_DATA_KEY = 'wcpb_data';

/**
 * Initialized support for the plugin Woocommerce Bundles.
 */
function peachpay_wcpb_init() {
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcpb_add_cart_page_cart_item_meta', 10, 2 );
	add_filter( 'peachpay_rebuild_wc_line_item', 'peachpay_wcpb_rebuild_wc_line_item', 10, 2 );
	add_filter( 'peachpay_filter_rebuild_cart', 'peachpay_wcpb_filter_rebuild_cart', 10, 2 );
	add_filter( 'peachpay_script_data', 'peachpay_wcpb_add_script_data', 10, 1 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_wcpb_init' );

/**
 * Adds meta information to the peachpay php data
 *
 * @param array $script_data The default script data.
 */
function peachpay_wcpb_add_script_data( $script_data ) {
	// If this function is called it means that bundles are active so it is ok to assume true.
	$script_data['plugin_woocommerce_product_bundles_active'] = true;
	return $script_data;
}

/**
 * Adds meta data to the peachpay cart item.
 *
 * @param array $pp_line_item The peachpay cart line item.
 * @param array $wc_line_item The woocommerce line item.
 */
function peachpay_wcpb_add_cart_page_cart_item_meta( $pp_line_item, $wc_line_item ) {
	if ( wc_pb_is_bundled_cart_item( $wc_line_item ) ) {

		$pp_line_item['is_part_of_bundle'] = true;
		$pp_line_item['quantity']          = 1;
		$pp_line_item['price']             = 0;
		$pp_line_item['subtotal']          = '0';
		$pp_line_item['total']             = '0';

		// Data needed for rebuilding the wc cart.
		$pp_line_item[ WCPB_DATA_KEY ] = array(
			'bundled_item_id' => $wc_line_item['bundled_item_id'],
			'bundled_by'      => $wc_line_item['bundled_by'],
			'stamp'           => $wc_line_item['stamp'],
		);
	} elseif ( array_key_exists( 'bundled_items', $wc_line_item ) ) {
		$pp_line_item[ WCPB_DATA_KEY ] = array(
			'bundled_items' => $wc_line_item['bundled_items'],
			'stamp'         => $wc_line_item['stamp'],
		);
	}

	return $pp_line_item;
}

/**
 * Rebuild the WC cart item for Pridcut bundles
 *
 * @param array $wc_line_item The woocommerce line item to rebuild.
 * @param array $pp_line_item The peachpay line item to source data from.
 */
function peachpay_wcpb_rebuild_wc_line_item( $wc_line_item, $pp_line_item ) {

	if ( isset( $pp_line_item[ WCPB_DATA_KEY ] ) ) {
		if ( array_key_exists( 'bundled_items', $pp_line_item[ WCPB_DATA_KEY ] ) ) {
			$wc_line_item['bundled_items'] = $pp_line_item[ WCPB_DATA_KEY ]['bundled_items'];
			$wc_line_item['stamp']         = $pp_line_item[ WCPB_DATA_KEY ]['stamp'];
		} else {
			$wc_line_item['bundled_item_id'] = $pp_line_item[ WCPB_DATA_KEY ]['bundled_item_id'];
			$wc_line_item['bundled_by']      = $pp_line_item[ WCPB_DATA_KEY ]['bundled_by'];
		}
	}

	return $wc_line_item;
}

/**
 * Used to remove bundled children from being added to the rebuilt cart.
 *
 * @param boolean $keep The current keep value.
 * @param array   $pp_line_item The peachpay cart line item.
 */
function peachpay_wcpb_filter_rebuild_cart( $keep, $pp_line_item ) {
	if ( array_key_exists( WCPB_DATA_KEY, $pp_line_item ) && array_key_exists( 'bundled_item_id', $pp_line_item[ WCPB_DATA_KEY ] ) ) {
		return false;
	}

	return $keep;
}
