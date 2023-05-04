<?php
/**
 * PeachPay Square hooks
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'peachpay_register_feature', 'peachpay_square_register_feature' );
add_action( 'peachpay_settings_admin_action', 'peachpay_square_handle_admin_actions', 10, 1 );
add_action( 'peachpay_plugin_capabilities', 'peachpay_square_handle_plugin_capabilities', 10, 1 );

add_action( 'wp_ajax_pp-square-applepay-domain-register', 'peachpay_square_handle_applepay_domain_registration' );
