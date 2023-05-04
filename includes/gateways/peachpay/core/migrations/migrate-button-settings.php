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
 * Migrate button icon setting for floating button icon setting.
 * Flip setting options for hiding PeachPay button across different pages on Woocommerce and hiding payment method icons.
 */
function peachpay_migrate_button_settings_option() {
	$button_options = get_option( 'peachpay_button_options', array() );

	// Migrate settings options if any existed.
	if ( isset( $button_options['button_hide_payment_method_icons'] ) ) {
		$button_options['button_display_payment_method_icons'] = ! $button_options['button_hide_payment_method_icons'];
		unset( $button_options['button_hide_payment_method_icons'] );
	}
	if ( isset( $button_options['checkout_outline_disabled'] ) ) {
		$button_options['display_checkout_outline'] = ! $button_options['checkout_outline_disabled'];
		unset( $button_options['checkout_outline_disabled'] );
	}
	if ( isset( $button_options['hide_on_product_page'] ) ) {
		$button_options['display_on_product_page'] = ! $button_options['hide_on_product_page'];
		unset( $button_options['hide_on_product_page'] );
	}
	if ( isset( $button_options['disabled_cart_page'] ) ) {
		$button_options['cart_page_enabled'] = ! $button_options['disabled_cart_page'];
		unset( $button_options['disabled_cart_page'] );
	}
	if ( isset( $button_options['disabled_checkout_page'] ) ) {
		$button_options['checkout_page_enabled'] = ! $button_options['disabled_checkout_page'];
		unset( $button_options['disabled_checkout_page'] );
	}
	if ( isset( $button_options['disabled_mini_cart'] ) ) {
		$button_options['mini_cart_enabled'] = ! $button_options['disabled_mini_cart'];
		unset( $button_options['disabled_mini_cart'] );
	}
	if ( isset( $button_options['disabled_floating_button'] ) ) {
		$button_options['floating_button_enabled'] = ! $button_options['disabled_floating_button'];
		unset( $button_options['disabled_floating_button'] );
	}

	update_option( 'peachpay_button_options', $button_options );

	if ( ! get_option( 'peachpay_migrated_float_button_icon' ) && peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ) ) {
		$icon = peachpay_get_settings_option( 'peachpay_button_options', 'button_icon', 'shopping_cart' );
		peachpay_set_settings_option( 'peachpay_button_options', 'floating_button_icon', $icon );
		update_option( 'peachpay_migrated_float_button_icon', 1 );
	}
}
