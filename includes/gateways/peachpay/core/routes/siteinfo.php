<?php
/**
 * Route handler for site info
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves the site health debug tab information.
 */
function peachpay_get_site_info() {
	if ( ! function_exists( 'get_core_updates' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}
	if ( ! function_exists( 'got_url_rewrite' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	if ( ! class_exists( 'WP_Debug_Data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
	}

	return WP_Debug_Data::debug_data();
}
