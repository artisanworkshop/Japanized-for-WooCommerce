<?php
/**
 * Migrates old general settings option.
 *
 * This migration can be deleted after all below merchants have updated the plugin.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrate hide upsell and cross-sell settings option to related product settings page.
 * Flip setting options for product image and quantity changer setting options.
 */
function peachpay_migrate_general_settings_option() {
	if ( ! get_option( 'peachpay_migrated_pp_only_checkout_option' ) && peachpay_get_settings_option( 'peachpay_general_options', 'make_pp_the_only_checkout' ) ) {
		peachpay_set_settings_option( 'peachpay_payment_options', 'make_pp_the_only_checkout', 1 );
		update_option( 'peachpay_migrated_pp_only_checkout_option', 1 );
	}

	if ( ! get_option( 'peachpay_migrated_product_image_option' ) && ( ! isset( get_option( 'peachpay_general_options' )['hide_product_images'] ) || ! get_option( 'peachpay_general_options' )['hide_product_images'] ) ) {
		peachpay_set_settings_option( 'peachpay_general_options', 'display_product_images', 1 );
		update_option( 'peachpay_migrated_product_image_option', 1 );
	}

	if ( ! get_option( 'peachpay_migrated_quantity_changer_option' ) && ( ! isset( get_option( 'peachpay_general_options' )['hide_quantity_changer'] ) || ! get_option( 'peachpay_general_options' )['hide_quantity_changer'] ) ) {
		peachpay_set_settings_option( 'peachpay_general_options', 'enable_quantity_changer', 1 );
		update_option( 'peachpay_migrated_quantity_changer_option', 1 );
	}

	if ( ! get_option( 'peachpay_migrated_upsell_option' ) && peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_upsell' ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'hide_woocommerce_products_upsell', 1 );
		update_option( 'peachpay_migrated_upsell_option', 1 );
	}

	if ( ! get_option( 'peachpay_migrated_cross_sell_option' ) && peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_cross_sell' ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'hide_woocommerce_products_cross_sell', 1 );
		update_option( 'peachpay_migrated_cross_sell_option', 1 );
	}

	// This one did not migrate from anywhere, so this block is just being used
	// to default the option to on. If we do more of these, we could consider
	// moving these new defaults to a dedicated file.
	if ( ! get_option( 'peachpay_migrated_address_autocomplete' ) ) {
		peachpay_set_settings_option( 'peachpay_general_options', 'address_autocomplete', 1 );
		update_option( 'peachpay_migrated_address_autocomplete', 1 );
	}

	if ( ! get_option( 'peachpay_migrated_wc_country_locale' ) ) {
		peachpay_set_settings_option( 'peachpay_general_options', 'use_wc_country_locale', 1 );
		update_option( 'peachpay_migrated_wc_country_locale', 1 );
	}
}
