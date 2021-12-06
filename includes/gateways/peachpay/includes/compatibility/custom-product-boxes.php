<?php
/**
 * Support for the Custom Product Boxes WooCommercePlugin
 * Plugin: https://woocommerce.com/products/custom-product-boxes/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'peachpay_cart_page_line_item', 'peachpay_cpb_cart_page_line_item', 10, 2 );
add_filter( 'peachpay_rebuild_wc_line_item', 'peachpay_cpb_rebuild_cart', 10, 2 );
add_filter( 'peachpay_filter_rebuild_cart', 'peachpay_cpb_filter_cart', 10, 2 );

/**
 * Whether or not to keep a product in the cart for shipping.
 *
 * @param boolean $keep The default value if the custom product box data does not exist.
 * @param array   $pp_cart_item The PeachPay cart item.
 *
 * @return boolean Indicates if the item should be kept in the cart.
 */
function peachpay_cpb_filter_cart( $keep, $pp_cart_item ) {
	if ( array_key_exists( 'cpb_data', $pp_cart_item ) ) {
		if ( array_key_exists( 'cpb_custom_product_parent_key', $pp_cart_item['cpb_data'] ) ) {
			if ( 'yes' === get_post_meta( $pp_cart_item['cpb_data']['cpb_custom_parent_id'], 'cpb_product_boxes_shipping', true ) ) {
				$keep = false;
			}
		}
	}
	return $keep;
}

/**
 * Update line item for children and parents of custom product boxes.
 *
 * @param array $pp_cart_item The PeachPay cart item.
 * @param array $wc_line_item The WooCommerce line item.
 * @return array The updated PeachPay cart item.
 */
function peachpay_cpb_cart_page_line_item( array $pp_cart_item, $wc_line_item ) {
	if ( array_key_exists( 'cpb_custom_product_parent_key', $wc_line_item ) ) {
		// Child Product.
		$pp_cart_item['is_part_of_bundle'] = true;
		$pp_cart_item['price']             = $wc_line_item['line_subtotal'];
		$pp_cart_item['subtotal']          = $wc_line_item['line_subtotal'];
		$pp_cart_item['total']             = $wc_line_item['line_total'];

		$pp_cart_item['cpb_data'] = array(
			'cpb_custom_product_parent_key' => $wc_line_item['cpb_custom_product_parent_key'],
			'cpb_custom_parent_id'          => $wc_line_item['cpb_custom_parent_id'],
			'cpb_custom_product_qty'        => $wc_line_item['cpb_custom_product_qty'],
		);

	} elseif ( array_key_exists( 'cpb-box-add-to-cart', $wc_line_item ) ) {
		// Parent product.
		$pp_cart_item['price']    = $wc_line_item['line_subtotal'] / $wc_line_item['quantity'];
		$pp_cart_item['subtotal'] = $wc_line_item['line_subtotal'];
		$pp_cart_item['total']    = $wc_line_item['line_total'];

		$pp_cart_item['cpb_data'] = array(
			'cpb-box-add-to-cart'     => $wc_line_item['cpb-box-add-to-cart'],
			'cpb_custom_product_keys' => $wc_line_item['cpb_custom_product_keys'],
		);

	}

	return $pp_cart_item;
}

/**
 * Rebuild the cart for custom product boxe items.
 *
 * @param array $wc_line_item The WooCommerce line item.
 * @param array $pp_line_item The PeachPay line item.
 */
function peachpay_cpb_rebuild_cart( $wc_line_item, $pp_line_item ) {
	if ( array_key_exists( 'cpb_data', $pp_line_item ) ) {
		if ( array_key_exists( 'cpb-box-add-to-cart', $pp_line_item['cpb_data'] ) ) {
			// Parent item.
			$wc_line_item['cpb-box-add-to-cart']     = $pp_line_item['cpb_data']['cpb-box-add-to-cart'];
			$wc_line_item['cpb_custom_product_keys'] = $pp_line_item['cpb_data']['cpb_custom_product_keys'];
		} else {
			// Child item.
			$wc_line_item['cpb_custom_product_parent_key'] = $pp_line_item['cpb_data']['cpb_custom_product_parent_key'];
			$wc_line_item['cpb_custom_parent_id']          = $pp_line_item['cpb_data']['cpb_custom_parent_id'];
			$wc_line_item['cpb_custom_product_qty']        = $pp_line_item['cpb_data']['cpb_custom_product_qty'];
		}
	}
	return $wc_line_item;
}
