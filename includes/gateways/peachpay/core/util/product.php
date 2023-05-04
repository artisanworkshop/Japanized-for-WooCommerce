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
	return wp_get_attachment_image_src( $product->get_image_id() );
}

/**
 * Check if bundle product contains variation product
 *
 * @param WC_Product_Bundle $bundle The bundle product to get product data from.
 */
function peachpay_is_variation_bundle( WC_Product_Bundle $bundle ) {
	$bundle_products = $bundle->get_bundled_items();
	foreach ( $bundle_products as $bundle_product ) {
		$product = $bundle_product->get_product();
		if ( $product->is_type( 'variable' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Get variable product's attribute data.
 *
 * @param WP_Product $product Variation product.
 */
function peachpay_get_attribute_data( $product ) {
	$attributes = $product->get_attributes();
	$attr_data  = array();
	foreach ( $attributes as $attr ) {
		$data = array(
			'label'   => wc_attribute_label( $attr['name'] ),
			'name'    => sanitize_title( $attr['name'] ),
			'options' => $attr->get_slugs(),
		);

		array_push( $attr_data, $data );
	}
	return $attr_data;
}

/**
 * Get Woocommerce product's default price html.
 *
 * @param WP_Product $product Woocommerce product.
 */
function peachpay_get_product_price_html( $product ) {
	$price = '';
	if ( '' === $product->get_price() ) {
		$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
	} elseif ( $product->is_on_sale() ) {
		$price = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
	} else {
		$price = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
	}

	if ( $product->is_type( 'variable' ) ) {
		$min_price = $product->get_variation_price( 'min', true );
		$max_price = $product->get_variation_price( 'max', true );
		$min_price !== $max_price ?
		$price     = wc_format_price_range( $min_price, $max_price ) : '';
	}

	return $price;
}
