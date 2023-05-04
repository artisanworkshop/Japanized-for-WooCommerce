<?php
/**
 * PeachPay Hooks.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

// Public script data
add_filter( 'peachpay_register_feature', 'peachpay_paypal_register_feature' );
add_action( 'peachpay_plugin_capabilities', 'peachpay_paypal_handle_plugin_capabilities', 10, 1 );

// Backend GET request
add_action( 'peachpay_settings_admin_action', 'peachpay_paypal_handle_admin_actions', 10, 1 );

// Public ajax routes
add_action( 'wc_ajax_pp-paypal-update-order', 'peachpay_paypal_handle_update_order' );
add_action( 'wc_ajax_pp-paypal-approve-order', 'peachpay_paypal_handle_approve_order' );

// Order Dashboard
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_paypal_display_order_transaction_details' );
add_action( 'woocommerce_admin_order_totals_after_total', 'peachpay_paypal_display_fee_lines' );

// Order status change
add_action( 'woocommerce_order_status_cancelled', 'peachpay_paypal_handle_order_cancelled', 10, 1 );

// Checkout/Order page filters
add_filter( 'woocommerce_order_button_html', 'peachpay_paypal_custom_order_button_html' );

