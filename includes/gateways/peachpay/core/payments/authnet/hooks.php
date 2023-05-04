<?php
/**
 * PeachPay Authnet Hooks.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

add_filter( 'peachpay_register_feature', 'peachpay_authnet_register_feature' );
add_action( 'peachpay_settings_admin_action', 'peachpay_authnet_handle_admin_actions', 10, 1 );
add_action( 'peachpay_plugin_capabilities', 'peachpay_authnet_handle_plugin_capabilities', 10, 1 );

// Order Dashboard
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_authnet_display_order_transaction_capture_void_form' );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'peachpay_authnet_display_order_transaction_details' );

// Admin Ajax
add_action( 'wp_ajax_pp-authnet-capture-payment', 'peachpay_authnet_handle_capture_payment' );
add_action( 'wp_ajax_pp-authnet-void-payment', 'peachpay_authnet_handle_void_payment' );

// Order status changed
add_action( 'woocommerce_order_status_completed', 'peachpay_authnet_handle_order_completed', 10, 1 );
add_action( 'woocommerce_order_status_cancelled', 'peachpay_authnet_handle_order_cancelled', 10, 1 );
