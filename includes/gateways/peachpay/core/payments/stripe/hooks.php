<?php
/**
 * PeachPay Stripe integration hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

// Gateway init
add_filter( 'woocommerce_payment_methods_list_item', 'peachpay_stripe_get_account_saved_payment_methods_list_item', 10, 2 );
add_filter( 'woocommerce_get_customer_payment_tokens', 'peachpay_get_customer_payment_tokens', 10, 3 );

// Public script data
add_filter( 'peachpay_register_feature', 'peachpay_stripe_register_feature' );
add_action( 'peachpay_settings_admin_action', 'peachpay_stripe_handle_admin_actions', 10, 1 );
add_action( 'peachpay_plugin_capabilities', 'peachpay_stripe_handle_plugin_capabilities', 10, 1 );

// Order Dashboard
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_stripe_display_order_transaction_capture_void_form' );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_stripe_display_order_transaction_details' );
add_action( 'woocommerce_admin_order_totals_after_total', 'peachpay_stripe_display_balance_transaction_fee_lines' );

// Order status change
add_action( 'woocommerce_order_status_changed', 'peachpay_stripe_handle_order_complete', 10, 4 );
add_action( 'woocommerce_order_status_cancelled', 'peachpay_stripe_handle_order_cancelled', 10, 1 );
add_action( 'woocommerce_order_status_changed', 'peachpay_stripe_handle_order_processing', 10, 4 );

// Public ajax routes
add_action( 'wc_ajax_pp-stripe-setup-intent', 'peachpay_stripe_handle_setup_intent' );

// Admin ajax routes
add_action( 'wp_ajax_pp-stripe-applepay-domain-register', 'peachpay_stripe_handle_applepay_domain_registration' );
add_action( 'wp_ajax_pp-stripe-capture-payment', 'peachpay_stripe_handle_capture_payment' );
add_action( 'wp_ajax_pp-stripe-void-payment', 'peachpay_stripe_handle_void_payment' );

// Backend admin GET actions
add_action( 'peachpay_settings_admin_action', 'peachpay_stripe_handle_admin_actions', 10, 1 );

