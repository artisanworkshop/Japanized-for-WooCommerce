<?php
/**
 * Support for the Woo Discount Rules Plugin
 * Plugin:
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Intilizes support Woo Discount Rules.
 */
function peachpay_wdr_init() {
	add_filter( 'peachpay_product_price', 'peachpay_wdr_product_price', 10, 2 );
	add_filter( 'peachpay_product_display_price', 'peachpay_wdr_product_display_price', 10, 3 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_wdr_init' );

/**
 * Gets the discount amount for a given product.
 *
 * @param int $wc_product_id The product id to check a discount for.
 */
function peachpay_woo_discount_rules_get_discount( $wc_product_id ) {
	$manage_discount     = new Wdr\App\Controllers\ManageDiscount();
	$discount_calculator = new Wdr\App\Controllers\DiscountCalculator( $manage_discount->getDiscountRules() );
	$discount_info       = $discount_calculator->getProductPriceToDisplay( (int) $wc_product_id, 1 );

	if ( ! isset( $discount_info['discounted_price'] ) || ( isset( $discount_info['discounted_price'] ) && ! $discount_info['discounted_price'] ) ) {
		return false;
	}

	return $discount_info;
}

/**
 * Filter function for "peachpay_product_price". Applies a discount to the product price if one is needed.
 *
 * @param float      $price The current product price to conditionally adjust.
 * @param WC_Product $wc_product The specific product the price is from.
 */
function peachpay_wdr_product_price( $price, $wc_product ) {

	// Even if the Woo Discount Rules is active, this product may not have a discount.
	$maybe_discounted_price = peachpay_woo_discount_rules_get_discount( $wc_product->get_id() );
	if ( $maybe_discounted_price ) {
		$price = $maybe_discounted_price['discounted_price'];
	}

	return $price;
}

/**
 * Filter function for "peachpay_product_display_price". Applies a discount to the product display price if one is needed.
 *
 * @param float      $price The current product price to conditionally adjust.
 * @param WC_Product $wc_product The specific product the price is from.
 * @param bool       $tax_inclusive Whether tax is being displayed combined with price.
 */
function peachpay_wdr_product_display_price( $price, $wc_product, $tax_inclusive ) {
	// Even if the Woo Discount Rules is active, this product may not have a discount.
	$maybe_discounted_price = peachpay_woo_discount_rules_get_discount( $wc_product->get_id() );
	if ( $maybe_discounted_price ) {
		if ( $tax_inclusive ) {
			$price = $maybe_discounted_price['discounted_price_with_tax'];
		} else {
			$price = $maybe_discounted_price['discounted_price'];
		}
	}

	return $price;
}


/**
 * A Rest API Endpoint for getting the discount amount.
 *
 * @param WP_REST_Request $request The API request object.
 */
function peachpay_wdr_discount_rest( $request ) {
	$maybe_discounted_price = peachpay_woo_discount_rules_get_discount( $request['id'] );
	if ( $maybe_discounted_price ) {
		return $maybe_discounted_price['discounted_price'];
	}
	return false;
}
