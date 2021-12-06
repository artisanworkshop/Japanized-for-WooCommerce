<?php
/**
 * Support for the Woocommerce Product Addons Plugin
 * Plugin: https://woocommerce.com/products/product-add-ons/
 *
 * Supports product page and cart page without currency switchers. Only supports product page with Yaycurrency.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initializes Woocommerce Product Addon compatibility.
 */
function peachpay_wcpa_init() {
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcpa_add_cart_page_item_meta', 10, 2 );
	add_filter( 'peachpay_product_page_line_item', 'peachpay_wcpa_add_product_page_cart_item_meta', 10, 2 );
	add_filter( 'peachpay_rebuild_wc_line_item', 'peachpay_wcpa_rebuild_line_item', 10, 2 );

	// Some how Manicci seems to convert the the values themselves. Likely custom code on there side. We added custom code ourselves so we test and compare the same way.
	if ( peachpay_is_site( 'store.local' ) ) {
		add_filter( 'woocommerce_product_addons_option_price_raw', 'peachpay_wcpa_raw_price_support_currency_switchers', 10, 2 );
		add_filter( 'wc_price_args', 'peachpay_wcpa_formatted_price_support_currency_switchers', 10, 2 );
	}

}
add_action( 'peachpay_init_compatibility', 'peachpay_wcpa_init' );

/**
 * Adds any needed meta data to cart item if has any product addons
 *
 * @since 1.47.0
 * @param array $pp_cart_item The item to add meta details related to product addons.
 * @param array $wc_line_item   Cart Line item data.
 */
function peachpay_wcpa_add_cart_page_item_meta( array $pp_cart_item, array $wc_line_item ) {
	if ( isset( $wc_line_item['addons'] ) && count( $wc_line_item['addons'] ) > 0 ) {
		$selected_addons = $wc_line_item['addons'];

		foreach ( $selected_addons as &$selected_addon ) {
			if ( isset( $selected_addon['price'] ) && '' !== $selected_addon['price'] ) {
				$selected_addon['price'] = apply_filters( 'peachpay_convert_from_default_currency', floatval( $selected_addon['price'] ), peachpay_currency_overridden_code() );
			}
		}
		unset( $selected_addon );

		$pp_cart_item['wc_addons'] = $selected_addons;
	}

	return $pp_cart_item;
}

/**
 * Rebuilds the meta data for a given addon product.
 *
 * @param array $wc_line_item Woocommerce line item extra data object.
 * @param array $pp_line_item Peachpay line item.
 * @return array The WooCommerce line item with the meta data that was on the
 * PeachPay line item.
 */
function peachpay_wcpa_rebuild_line_item( $wc_line_item, $pp_line_item ) {
	if ( isset( $pp_line_item['wc_addons'] ) ) {
		$wc_line_item['addons'] = $pp_line_item['wc_addons'];
	}

	return $wc_line_item;
}

/**
 * Adds any needed meta data to cart item if it has any product addons
 *
 * @since 1.47.0
 * @param array      $pp_cart_item The peachpay cart line item.
 * @param WC_Product $product The woocommerce product object.
 */
function peachpay_wcpa_add_product_page_cart_item_meta( array $pp_cart_item, WC_Product $product ) {
	// Gets available options for product.
	if ( count( WC_Product_Addons_Helper::get_product_addons( $product->get_id() ) ) > 0 ) {
		$addons = WC_Product_Addons_Helper::get_product_addons( $product->get_id() );

		foreach ( $addons as &$addon ) {
			if ( isset( $addon['price'] ) && '' !== $addon['price'] ) {
				$addon['price'] = apply_filters( 'peachpay_convert_from_default_currency', floatval( $addon['price'] ), peachpay_currency_overridden_code() );
			}

			if ( isset( $addon['options'] ) ) {
				foreach ( $addon['options'] as &$option ) {

					if ( isset( $option['price'] ) && '' !== $option['price'] ) {
						$option['price'] = apply_filters( 'peachpay_convert_from_default_currency', floatval( $option['price'] ), peachpay_currency_overridden_code() );
					}
				}
				unset( $option );
			}
		}
		unset( $addon );

		$pp_cart_item['wc_addons_options'] = $addons;
	}

	return $pp_cart_item;
}

/**
 * Adds support for Currency switchers with Woocommerce Product Addons for
 * converting the raw price.
 *
 * @param string $price The raw price.
 * @param array  $option .
 */
function peachpay_wcpa_raw_price_support_currency_switchers( string $price, array $option ) {
	if ( isset( $price ) ) {
		return apply_filters( 'peachpay_convert_from_default_currency', floatval( $price ), peachpay_currency_overridden_code() );
	}
	return $price;
}

/**
 * Adds support for Currency switchers with Woocommerce Product Addons for converting the price formatting
 *
 * @param array $args The default currency formatting args.
 */
function peachpay_wcpa_formatted_price_support_currency_switchers( array $args ) {
	return array(
		'ex_tax_label'       => $args['ex_tax_label'],
		'currency'           => peachpay_currency_overridden_code(),
		'decimal_separator'  => peachpay_currency_decimal_separator(),
		'thousand_separator' => peachpay_currency_thousands_separator(),
		'decimals'           => peachpay_currency_decimals(),
		'price_format'       => peachpay_currency_price_format(),
	);
}
