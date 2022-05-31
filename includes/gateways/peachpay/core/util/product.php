<?php
/**
 * PeachPay Product API
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gets the parent id of a product. If the product does not have a parent then it returns its own Id.
 *
 * @param int $id The variation or child product id.
 * @return int The id of the parent or the id of the product you are checking.
 */
function peachpay_product_parent_id( $id ) {
	$product = wc_get_product( $id );

	if ( ! $product ) {
		return $id;
	}

	if ( $product instanceof WC_Product_Variation ) {
		return $product->get_parent_id();
	}

	return $id;
}

/**
 * Gets the product price.
 *
 * @param WC_Product $wc_product The product to get the price for.
 */
function peachpay_product_price( WC_Product $wc_product ) {
	$price = wc_get_price_excluding_tax( $wc_product );

	return (float) apply_filters( 'peachpay_product_price', $price, $wc_product );
}

/**
 * Allows retrieval of the WC_Product out of a WC Line item array with strong typing
 *
 * @param array $wc_line_item The Woocommerce Line item.
 * @return WC_Product
 */
function peachpay_product_from_line_item( $wc_line_item ) {
	return $wc_line_item['data'];
}

/**
 * Get the price product price to render in the modal.
 *
 * @param WC_Product $wc_product The product to get the display price for.
 */
function peachpay_product_display_price( WC_Product $wc_product ) {
	$price = 0;
	if ( 'incl' === WC()->cart->get_tax_price_display_mode() ) {
		$price = wc_get_price_including_tax( $wc_product );
	} else {
		$price = wc_get_price_excluding_tax( $wc_product );
	}

	return (float) apply_filters( 'peachpay_product_display_price', $price, $wc_product, ( 'incl' === WC()->cart->get_tax_price_display_mode() ) );
}

/**
 * Gets the products sin stock status.
 *
 * @param int $product_id The id of the product or variation.
 */
function peachpay_product_stock_status( int $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return 'outofstock';
	}

	/**
	 * Filters stock status for non wc inventory plugin support.
	 *
	 * @param string $stock_status The status of the stock
	 * @param int    $id           The id of the given product or variation
	 */
	return apply_filters( 'peachpay_product_stock_status', $product->get_stock_status(), $product_id );
}

/**
 * Takes in a product ID and if that product has variations will return an array
 * of all the product's variations. If no variations exist it returns an empty
 * string.
 *
 * @param int $id the product Id.
 * @return array of variation attributes including both the label and value.
 */
function peachpay_product_variation_attributes( $id ) {
	$variations = '';
	$product    = wc_get_product( $id );
	if ( ! $product ) {
		return '';
	}
	if ( $product instanceof WC_Product_Variation ) {
		$variations = $product->get_variation_attributes( true );
	}
	return $variations;
}

/**
 * Gets the formatted product variation name.
 *
 * @param int $id The product variation id.
 */
function peachpay_product_variation_name( $id ) {
	$product = wc_get_product( $id );
	if ( ! $product ) {
		return '';
	}
	return wc_get_formatted_variation( $product, true, false );
}

/**
 * Retrieve the given product's image for use in the checkout window.
 *
 * @param WC_Product $product The product to get image data for.
 * @return array|false Image data for the product, or false it it doesn't exist
 * or the option is turned off.
 */
function peachpay_product_image( WC_Product $product ) {
	if ( peachpay_get_settings_option( 'peachpay_general_options', 'hide_product_images' ) ) {
		return false;
	}
	return wp_get_attachment_image_src( $product->get_image_id() );
}

/**
 * Retrieve the given product's upsell item data for use in the checkout window.
 *
 * @param WC_Product $product The product to get upsell item's data for.
 * @return array|false Upsell item data for the product, or false it it doesn't
 * exist or the option is turned off.
 */
function peachpay_upsell_items( WC_Product $product ) {
	if ( peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_upsell' ) ) {
		return false;
	}

	$upsell_ids = $product->get_upsell_ids();
	if ( ! $upsell_ids ) {
		return;
	}
	$upsell_items = array();
	foreach ( $upsell_ids as $upsell_id ) {
		$upsell = wc_get_product( $upsell_id );

		if ( ! $upsell ) {
			break;
		}
		$item = array(
			'id'        => $upsell->get_id(),
			'name'      => $upsell->get_name(),
			'price'     => $upsell->get_price(),
			'variable'  => $upsell->is_type( 'variable' ),
			'bundle'    => $upsell->is_type( 'bundle' ),
			'img_src'   => is_array( peachpay_product_image( $upsell ) ) ? peachpay_product_image( $upsell )[0] : '',
			'has_stock' => $upsell->get_stock_status() === 'instock',
		);
		array_push( $upsell_items, $item );
	}
	return $upsell_items;
}

/**
 * Retrieve the given product's cross-sell item data for use in the checkout window.
 *
 * @param WC_Product $product The product to get cross-sell item's data for.
 * @return array|false Cross-sell item data for the product, or false it it doesn't
 * exist or the option is turned off.
 */
function peachpay_cross_sell_items( WC_Product $product ) {
	if ( peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_cross_sell' ) ) {
		return false;
	}
	$cross_sell_ids = $product->get_cross_sell_ids();
	if ( ! $cross_sell_ids ) {
		return;
	}
	$cross_sell_items = array();
	foreach ( $cross_sell_ids as $cross_sell_id ) {
		$cross_sell = wc_get_product( $cross_sell_id );
		if ( ! $cross_sell ) {
			break;
		}
		$item = array(
			'id'        => $cross_sell->get_id(),
			'name'      => $cross_sell->get_name(),
			'price'     => $cross_sell->get_price(),
			'variable'  => $cross_sell->is_type( 'variable' ),
			'bundle'    => $cross_sell->is_type( 'bundle' ),
			'img_src'   => is_array( peachpay_product_image( $cross_sell ) ) ? peachpay_product_image( $cross_sell )[0] : '',
			'has_stock' => $cross_sell->get_stock_status() === 'instock',
		);
		array_push( $cross_sell_items, $item );
	}
	return $cross_sell_items;
}
