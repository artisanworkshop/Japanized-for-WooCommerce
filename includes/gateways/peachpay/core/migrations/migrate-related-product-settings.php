<?php
/**
 * Migrate and flip old related product settings option.
 *
 * This migration can be deleted after all below merchants have updated the plugin.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrate slider setting option.
 * Flip setting options for hide upsell and cross-sell products setting options.
 */
function peachpay_migrate_related_products_settings_option() {
	if ( ! get_option( 'peachpay_migrated_related_slider_option' ) && 'Enabled' === esc_attr( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider', 1 );
		update_option( 'peachpay_migrated_related_slider_option', 1 );
	}

	if ( ! get_option( 'peachpay_flip_upsell_option' ) && ( ! isset( get_option( 'peachpay_related_products_options' )['hide_woocommerce_products_upsell'] ) || ! get_option( 'peachpay_related_products_options' )['hide_woocommerce_products_upsell'] ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'display_woocommerce_products_upsell', 1 );
		update_option( 'peachpay_flip_upsell_option', 1 );
	}

	if ( ! get_option( 'peachpay_flip_cross_sell_option' ) && ( ! isset( get_option( 'peachpay_related_products_options' )['hide_woocommerce_products_cross_sell'] ) || ! get_option( 'peachpay_related_products_options' )['hide_woocommerce_products_cross_sell'] ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'display_woocommerce_products_cross_sell', 1 );
		update_option( 'peachpay_flip_cross_sell_option', 1 );
	}
}

/**
 * Migrate linked products and related products setting option.
 */
function peachpay_migrate_linked_and_related_products_settings_option() {
	if ( ! get_option( 'peachpay_migrated_linked_products_option' ) && ( peachpay_get_settings_option( 'peachpay_related_products_options', 'display_woocommerce_products_upsell' ) || peachpay_get_settings_option( 'peachpay_related_products_options', 'display_woocommerce_products_cross_sell' ) ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'display_woocommerce_linked_products', 1 );
		peachpay_set_settings_option( 'peachpay_related_products_options', 'peachpay_rp_mini_slider', 1 );
		update_option( 'peachpay_migrated_linked_products_option', 1 );
	}

	if ( ! get_option( 'peachpay_migrated_related_products_option' ) && peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_products_checkout_window_enable' ) ) {
		peachpay_set_settings_option( 'peachpay_related_products_options', 'peachpay_rp_mini_slider', 1 );
		update_option( 'peachpay_migrated_related_products_option', 1 );
	}
}
