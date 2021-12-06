<?php
/**
 * Support for the Country Based Restrictions for WooCommerce
 * Plugin: https://wordpress.org/plugins/woo-product-country-base-restrictions/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize filters for peachpay compatibility.
 */
function peachpay_cbr_init() {
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_cbr_add_cart_page_item_meta', 10, 2 );
	add_filter( 'peachpay_product_page_line_item', 'peachpay_cbr_add_product_page_item_meta', 10, 2 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_cbr_init' );

/**
 * Gets any needed meta data for the peachpay modal rendering of the cart item.
 *
 * @param array $pp_cart_item Peachpay cart line item.
 * @param array $wc_line_item Woocommerce Cart line item.
 */
function peachpay_cbr_add_cart_page_item_meta( array $pp_cart_item, array $wc_line_item ) {
	$product = $wc_line_item['data'];

	if ( PeachPay_CBR_Product::product_has_restrictions( $product ) ) {
		$pp_cart_item['wc_country_base_restrictions'] = PeachPay_CBR_Product::product_restrictions_meta( $product );
	}
	return $pp_cart_item;
}

/**
 * Gets any needed meta data for the peachpay modal rendering of the cart item.
 *
 * @param array      $pp_cart_item Peachpay cart line item.
 * @param WC_Product $product Woocommerce product.
 */
function peachpay_cbr_add_product_page_item_meta( array $pp_cart_item, WC_Product $product ) {
	return peachpay_cbr_add_cart_page_item_meta( $pp_cart_item, array( 'data' => $product ) );
}



/**
 * No API is provided for accessing what we needed so this now exist.
 * If the plugin is public a merge request to add this interface would
 * be a good idea to prevent breaking our code if the plugin changes
 * in the future.
 */
class PeachPay_CBR_Product {

	/**
	 * Gets the restricted countries for a given wc product id.
	 *
	 * @param int $product_id The WC Product id.
	 */
	private static function product_restricted_countries( int $product_id ) {
		$countries = get_post_meta( $product_id, '_restricted_countries', true );
		if ( empty( $countries ) || ! is_array( $countries ) ) {
			$countries = array();
		}

		if ( ! $countries[0] ) {
			// Sometimes you get a list of product restrictions but the product id shows up in the list so we can just grab it.
			$countries = $countries[ $product_id ];
		}

		return $countries;
	}

	/**
	 * Gets the country restriction type for a given wc product id.
	 *
	 * @param int $product_id The WC Product id.
	 */
	private static function product_restriction_type( int $product_id ) {
		return get_post_meta( $product_id, '_fz_country_restriction_type', true );
	}

	/**
	 * Indicates if a product has any country restrictions.
	 *
	 * @param WC_Product $product The WC Product.
	 */
	public static function product_has_restrictions( WC_Product $product ) {
		$product_id = $product->get_id();

		// If the variation has specific restrictions this will occur here.
		if ( self::product_id_has_restrictions( $product_id ) ) {
			return self::product_id_has_restrictions( $product_id );
		}

		if ( $product->get_parent_id() !== 0 ) {
			$product_id = $product->get_parent_id();
		}

		// If the specific variation does not have a restriction then test the parent product.
		return self::product_id_has_restrictions( $product_id );
	}

	/**
	 * Indicates if a product id has any country restrictions.
	 *
	 * @param int $product_id The WC Product id.
	 */
	public static function product_id_has_restrictions( int $product_id ) {
		return self::product_restriction_type( $product_id ) !== 'all' && self::product_restriction_type( $product_id );
	}

	/**
	 * Gets the product restriction meta data using a product.
	 *
	 * @param WC_Product $product The WC Product.
	 */
	public static function product_restrictions_meta( WC_Product $product ) {
		$product_id = $product->get_id();

		// If the variation has specific restrictions this will occur here.
		if ( self::product_id_has_restrictions( $product_id ) ) {
			return self::product_id_restrictions_meta( $product_id );
		}

		// In case this is a variation get the parent product id.
		if ( $product->get_parent_id() !== 0 ) {
			$product_id = $product->get_parent_id();
		}

		if ( self::product_id_has_restrictions( $product_id ) ) {
			return self::product_id_restrictions_meta( $product_id );
		} else {
			return array();
		}
	}

	/**
	 * Gets the product restriction meta data using a product id
	 *
	 * @param int $product_id The WC Product id.
	 */
	public static function product_id_restrictions_meta( int $product_id ) {

		return array(
			// Values are always 'specific', 'excluded', or 'all'.
			'type'      => self::product_restriction_type( $product_id ),
			// Country codes that are restricted using above restriction type.
			'countries' => self::product_restricted_countries( $product_id ),
		);
	}
}
