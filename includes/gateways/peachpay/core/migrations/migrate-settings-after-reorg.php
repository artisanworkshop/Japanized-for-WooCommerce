<?php
/**
 * Migrates settings options into the new set of settings options for express checkout.
 *
 * This migration can be deleted after all merchants have updated the plugin.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Runs all migration functions related to the settings reorganization in 1.83.0.
 */
function peachpay_migrate_express_checkout() {
	if ( ! get_option( 'peachpay_migrated_settings_after_reorg' ) ) {
		peachpay_migrate_branding();
		peachpay_migrate_checkout_window();
		peachpay_migrate_product_recommendations();
		peachpay_migrate_checkout_button();
		peachpay_migrate_advanced();

		peachpay_misc_migrations();

		peachpay_delete_deprecated_options();
		peachpay_delete_deprecated_payment_option_keys();

		update_option( 'peachpay_migrated_settings_after_reorg', 1 );
	}
}

/**
 * Migrates a set of options from peachpay_general_options and peachpay_button_options to peachpay_express_checkout_branding.
 */
function peachpay_migrate_branding() {
	// Get options from these arrays
	$general_options = get_option( 'peachpay_general_options', array() );
	$button_options  = get_option( 'peachpay_button_options', array() );

	$general_options_keys_to_migrate = array( 'language', 'merchant_logo' );
	$button_options_keys_to_migrate  = array( 'button_color', 'button_text_color' );

	// Migrate options to this array
	$branding_options = array_fill_keys( array_merge( $general_options_keys_to_migrate, $button_options_keys_to_migrate ), null );

	foreach ( $general_options_keys_to_migrate as $option_key ) {
		$value                           = array_key_exists( $option_key, $general_options ) ? $general_options[ $option_key ] : null;
		$branding_options[ $option_key ] = $value;
	}
	foreach ( $button_options_keys_to_migrate as $option_key ) {
		$value                           = array_key_exists( $option_key, $button_options ) ? $button_options[ $option_key ] : null;
		$branding_options[ $option_key ] = $value;
	}

	update_option( 'peachpay_express_checkout_branding', $branding_options );
}

/**
 * Migrates a set of options from peachpay_general_options to peachpay_express_checkout_window.
 */
function peachpay_migrate_checkout_window() {
	// Get options from these arrays
	$payment_options = get_option( 'peachpay_payment_options', array() );
	$general_options = get_option( 'peachpay_general_options', array() );
	$button_options  = get_option( 'peachpay_button_options', array() );

	$payment_options_keys_to_migrate = array( 'make_pp_the_only_checkout' );
	$general_options_keys_to_migrate = array(
		'display_product_images',
		'enable_quantity_changer',
		'enable_virtual_product_fields',
		'address_autocomplete',
		'use_wc_country_locale',
		'enable_store_support_message',
		'support_message_type',
		'support_message',
		'enable_order_notes',
	);
	$button_options_keys_to_migrate  = array( 'button_shadow_enabled' );

	// Migrate options to this array
	$checkout_window_options = array_fill_keys( array_merge( $payment_options_keys_to_migrate, $general_options_keys_to_migrate, $button_options_keys_to_migrate ), null );

	foreach ( $payment_options_keys_to_migrate as $option_key ) {
		$value                                  = array_key_exists( $option_key, $payment_options ) ? $payment_options[ $option_key ] : null;
		$checkout_window_options[ $option_key ] = $value;
	}
	foreach ( $general_options_keys_to_migrate as $option_key ) {
		$value                                  = array_key_exists( $option_key, $general_options ) ? $general_options[ $option_key ] : null;
		$checkout_window_options[ $option_key ] = $value;
	}
	foreach ( $button_options_keys_to_migrate as $option_key ) {
		$value                                  = array_key_exists( $option_key, $button_options ) ? $button_options[ $option_key ] : null;
		$checkout_window_options[ $option_key ] = $value;
	}

	update_option( 'peachpay_express_checkout_window', $checkout_window_options );
}

/**
 * Migrates a set of options from peachpay_related_products_options and peachpay_one_click_upsell_options to peachpay_express_checkout_product_recommendations.
 */
function peachpay_migrate_product_recommendations() {
	// Get options from these arrays
	$related_products_options = get_option( 'peachpay_related_products_options', array() );
	$ocu_options              = get_option( 'peachpay_one_click_upsell_options', array() );

	$related_products_options_keys_to_migrate = array(
		'display_woocommerce_linked_products',
		'peachpay_rp_nproducts',
		'peachpay_exclude_id',
		'peachpay_rp_mini_slider',
		'peachpay_rp_mini_slider_header',
		'peachpay_product_relation',
		'peachpay_recommended_products_manual',
	);
	$ocu_options_keys_to_migrate              = array(
		'peachpay_one_click_upsell_display_all',
		'peachpay_display_one_click_upsell',
		'peachpay_one_click_upsell_flow',
		'peachpay_one_click_upsell_products',
		'peachpay_one_click_upsell_primary_header',
		'peachpay_one_click_upsell_secondary_header',
		'peachpay_one_click_upsell_custom_description',
		'peachpay_one_click_upsell_accept_button_text',
		'peachpay_one_click_upsell_decline_button_text',
		'peachpay_one_click_upsell_enable',
	);

	// Migrate options to this array
	$product_recommendations_options = array_fill_keys( array_merge( $related_products_options_keys_to_migrate, $ocu_options_keys_to_migrate ), null );

	foreach ( $related_products_options_keys_to_migrate as $option_key ) {
		$value = array_key_exists( $option_key, $related_products_options ) ? $related_products_options[ $option_key ] : null;
		$product_recommendations_options[ $option_key ] = $value;
		unset( $related_products_options[ $option_key ] );
	}
	foreach ( $ocu_options_keys_to_migrate as $option_key ) {
		$value = array_key_exists( $option_key, $ocu_options ) ? $ocu_options[ $option_key ] : null;
		$product_recommendations_options[ $option_key ] = $value;
	}

	update_option( 'peachpay_express_checkout_product_recommendations', $product_recommendations_options );
	update_option( 'peachpay_related_products_options', $related_products_options );
}

/**
 * Migrates a set of options from peachpay_button_options to peachpay_express_checkout_button.
 */
function peachpay_migrate_checkout_button() {
	// Get options from this array
	$button_options = get_option( 'peachpay_button_options', array() );

	$button_options_keys_to_migrate = array(
		'product_button_position',
		'product_button_mobile_position',
		'button_icon',
		'floating_button_icon',
		'button_border_radius',
		'peachpay_button_text',
		'button_effect',
		'product_button_alignment',
		'cart_button_alignment',
		'floating_button_alignment',
		'floating_button_bottom_gap',
		'floating_button_side_gap',
		'floating_button_size',
		'floating_button_icon_size',
		'cart_page_enabled',
		'checkout_page_enabled',
		'mini_cart_enabled',
		'floating_button_enabled',
		'button_display_payment_method_icons',
		'disable_default_font_css',
		'display_on_product_page',
		'display_checkout_outline',
		'checkout_header_text',
		'checkout_subtext_text',
		'button_width_product_page',
		'button_width_cart_page',
		'button_width_checkout_page',
	);

	// Migrate options to this array
	$checkout_button_options = array_fill_keys( $button_options_keys_to_migrate, null );

	foreach ( $button_options_keys_to_migrate as $option_key ) {
		$value                                  = array_key_exists( $option_key, $button_options ) ? $button_options[ $option_key ] : null;
		$checkout_button_options[ $option_key ] = $value;
	}

	update_option( 'peachpay_express_checkout_button', $checkout_button_options );
}

/**
 * Migrates options from peachpay_advanced_options to peachpay_express_checkout_advanced.
 */
function peachpay_migrate_advanced() {
	// Get options from this array
	$advanced_options = get_option( 'peachpay_advanced_options', array() );

	$advanced_options_keys_to_migrate = array(
		'custom_button_class',
		'custom_button_css',
		'custom_checkout_js',
		'custom_target_product_page',
		'custom_target_cart_page',
		'custom_target_checkout_page',
	);

	// Migrate options to this array
	$ec_advanced_options = array_fill_keys( $advanced_options_keys_to_migrate, null );

	foreach ( $advanced_options_keys_to_migrate as $option_key ) {
		$value                              = array_key_exists( $option_key, $advanced_options ) ? $advanced_options[ $option_key ] : null;
		$ec_advanced_options[ $option_key ] = $value;
	}

	update_option( 'peachpay_express_checkout_advanced', $ec_advanced_options );
}

/**
 * Runs remaining migrations.
 */
function peachpay_misc_migrations() {
	$general_options = get_option( 'peachpay_general_options', array() );
	$payment_options = get_option( 'peachpay_payment_options', array() );
	$value           = array_key_exists( 'data_retention', $general_options ) ? $general_options['data_retention'] : 1;

	$payment_options['data_retention'] = $value;
	update_option( 'peachpay_payment_options', $payment_options );
}

/**
 * Removes peachpay options that won't be used after this migration.
 */
function peachpay_delete_deprecated_options() {
	$option_groups_to_delete = array(
		'peachpay_general_options',
		'peachpay_one_click_upsell_options',
		'peachpay_button_options',
		'peachpay_advanced_options',
	);
	foreach ( $option_groups_to_delete as $option_group ) {
		delete_option( $option_group );
	}
}

/**
 * Removes unused payment options for the minimum/maximum settings.
 */
function peachpay_delete_deprecated_payment_option_keys() {
	$peachpay_payment_options = get_option( 'peachpay_payment_options' );
	if ( ! is_array( $peachpay_payment_options ) ) {
		return;
	}
	$payment_methods = array(
		'stripe_card_payments',
		'stripe_affirm_payments',
		'stripe_klarna_payments',
		'stripe_afterpay_clearpay_payments',
		'stripe_us_bank_account_ach_payments',
		'stripe_bancontact_payments',
		'stripe_giropay_payments',
		'stripe_ideal_payments',
		'stripe_sofort_payments',
		'stripe_p24_payments',
		'stripe_eps_payments',
		'square_card_payments',
		'peachpay_purchase_order',
	);
	$suffixes        = array(
		'_pm_min',
		'_pm_max',
		'_merchant_min',
		'_merchant_max',
		'_default_currency',
	);
	foreach ( $payment_methods as $payment_method ) {
		foreach ( $suffixes as $suffix ) {
			unset( $peachpay_payment_options[ $payment_method . $suffix ] );
			delete_option( $payment_method . $suffix );
		}
	}
	update_option( 'peachpay_payment_options', $peachpay_payment_options );
}
