<?php
/**
 * PeachPay AmazonPay Hooks.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

add_action( 'peachpay_settings_admin_action', 'peachpay_amazonpay_handle_admin_actions', 10, 1 );
add_action( 'peachpay_plugin_capabilities', 'peachpay_amazonpay_handle_plugin_capabilities', 10, 1 );
