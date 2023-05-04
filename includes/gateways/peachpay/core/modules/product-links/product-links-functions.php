<?php
/**
 * File for functions to make testing easier.
 *
 * @package PeachPay
 */

/**
 * Gets some basic product data we want to know about.
 *
 * @param WC_product $product the product to extract information from.
 */
function peachpay_get_required_product_info( $product ) {
	$product_data                              = array();
	$product_data['productIDS']['woocommerce'] = $product->get_id();
	$product_data['productName']               = $product->get_name();
	$product_data['productDescription']        = $product->get_description();
	$product_data['productShortDescription']   = $product->get_short_description();
	$product_data['productImages']             = peachpay_build_image_URLs( $product );
	if ( get_class( $product ) === 'WC_Product_Variable' ) {
		return peachpay_get_required_variable_product_info( $product, $product_data );
	}

	$product_data['purchaseOptions'] = array(
		'price'          => $product->get_price(),
		'currency'       => get_woocommerce_currency(),
		'currencySymbol' => html_entity_decode( peachpay_currency_symbol(), ENT_COMPAT ),
		'platforms'      => array(
			'woocommerce' => peachpay_build_checkout_url( $product->get_id() ),
		),
	);
	$product_data['product_options'] = null;
	$product_data['option_table']    = null;

	return $product_data;
}

/**
 * Helper function to build a checkout url for a product.
 *
 * @param string $id the id of the product to build the url for.
 */
function peachpay_build_checkout_url( $id ) {
	return home_url( '/checkout/?add-to-cart=' . $id );
}

/**
 * Variable products have children in WC so if it's variable get all it's children and extract a data from them.
 *
 * @param WC_product_variable $product the product to get data from.
 * @param array               $product_data all product data extracted from the parent product.
 */
function peachpay_get_required_variable_product_info( $product, $product_data ) {
	$children                       = $product->get_available_variations();
	$product_data['productOptions'] = peachpay_build_landing_product_options( $product->get_attributes() );
	$option_table                   = array();
	foreach ( $children as $child ) {
		$child = wc_get_product( $child['variation_id'] );
		$option_table[ peachpay_build_attribute_string( $child ) ] = array(
			'price'          => $child->get_price(),
			'currencySymbol' => html_entity_decode( peachpay_currency_symbol(), ENT_COMPAT ),
			'platforms'      => array(
				'woocommerce' => peachpay_build_checkout_url( $child->get_id() ),
			),
		);
	}

	$product_data['optionTable'] = $option_table;

	return $product_data;
}

/**
 * Get attributes and turn them into a concatanation of all child attributes to sell.
 *
 * @param WC_product $child the variation product.
 */
function peachpay_build_attribute_string( $child ) {
	$string = '';
	$child  = $child->get_attributes();
	foreach ( $child as $value ) {
		if ( null === $value ) {
			$string = $string . 'any.';
			continue;
		}
		$string = $string . $value . '.';
	}
	return $string;
}

/**
 * For products with options extract the options
 *
 * @param WC_product_attribute $attributes the attribute object to extract from.
 */
function peachpay_build_landing_product_options( $attributes ) {
	$options = array();
	foreach ( $attributes as $attribute ) {
		$option = array(
			'name'    => wc_attribute_label( $attribute->get_name() ),
			'options' => $attribute->get_slugs(),
		);
		array_push( $options, $option );
	}
	return $options;
}

/**
 * Get the urls of images to use in the store
 *
 * @param WC_Product $product the product to get images from.
 */
//phpcs:ignore
function peachpay_build_image_URLs( $product ) {
	$images     = array();
	$main_image = wp_get_attachment_image_src( $product->get_image_id(), 'medium' );
	$main_image ? array_push( $images, $main_image[0] ) : '';
	$image_ids = $product->get_gallery_image_ids();
	foreach ( $image_ids as $id ) {
		$src = wp_get_attachment_image_src( $id, 'medium' );
		$src ? array_push( $images, $src[0] ) : '';
	}
	return $images;
}
