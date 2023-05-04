<?php
/**
 * PeachPay Poynt Hooks.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

// Public script data
add_filter( 'peachpay_register_feature', 'peachpay_poynt_register_feature' );

// Order status changed
add_action( 'woocommerce_order_status_completed', 'peachpay_poynt_handle_order_completed', 10, 1 );
add_action( 'woocommerce_order_status_cancelled', 'peachpay_poynt_handle_order_cancelled', 10, 1 );

// Register admin hooks
add_action( 'peachpay_settings_admin_action', 'peachpay_poynt_handle_admin_actions', 10, 1 );

// Order Dashboard
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_poynt_display_order_transaction_capture_void_form' );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_poynt_display_order_transaction_details' );

// Admin Ajax
add_action( 'wp_ajax_pp-poynt-capture-payment', 'peachpay_poynt_handle_capture_payment' );
add_action( 'wp_ajax_pp-poynt-void-payment', 'peachpay_poynt_handle_void_payment' );
add_action( 'wp_ajax_pp-poynt-register-webhooks', 'peachpay_poynt_handle_register_webhooks' );

// Gateway init
add_filter( 'woocommerce_payment_methods_list_item', 'peachpay_poynt_get_account_saved_payment_methods_list_item', 10, 2 );
add_filter( 'woocommerce_get_customer_payment_tokens', 'peachpay_poynt_get_customer_payment_tokens', 10, 3 );
