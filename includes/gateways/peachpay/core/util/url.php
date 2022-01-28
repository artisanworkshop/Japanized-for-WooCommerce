<?php
/**
 * PeachPay URL Utility Files.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Determines if the given site is active
 *
 * @param string $site The site domain to check.
 */
function peachpay_is_site( string $site ) {
	return strpos( get_site_url(), $site ) !== false;
}

/**
 * Gets the current checkout URL based on the current site context.
 */
function peachpay_checkout_url() {
	if ( peachpay_is_test_mode() ) {
		switch ( get_site_url() ) {
			case 'http://localhost':
			case 'http://127.0.0.1':
				return 'http://localhost:8080';
			case 'https://store.local':
			case 'https://woo.store.local':
				return 'https://dev-connect.peachpay.local'; // Local HTTPS.
			default:
				return 'https://dev-connect.peachpaycheckout.com';
		}
	}

	switch ( get_site_url() ) {
		case 'http://localhost':
		case 'http://127.0.0.1':
			return 'http://localhost:8080';
		case 'https://woo.peachpay.app':
		case 'https://theme1.peachpay.app':
		case 'https://theme2.peachpay.app':
		case 'https://theme3.peachpay.app':
		case 'https://theme4.peachpay.app':
		case 'https://theme5.peachpay.app':
		case 'https://qa.peachpay.app':
		case 'https://demo.peachpay.app':
			return 'https://dev-connect.peachpaycheckout.com';
		case 'https://store.local':
		case 'https://woo.store.local':
			return 'https://connect.peachpay.local'; // Local HTTPS.
		default:
			return 'https://connect.peachpaycheckout.com';
	}
}

/**
 * Determines which environment we are running in so we can call
 * the correct PeachPay API.
 */
function peachpay_api_url() {
	if ( peachpay_is_test_mode() ) {
		switch ( get_site_url() ) {
			case 'https://woo.store.local':
			case 'https://store.local':
				return 'https://dev.peachpay.local/';
			default:
				return 'https://dev.peachpay.app/';
		}
	} else {
		switch ( get_site_url() ) {
			case 'https://woo.peachpay.app':
			case 'https://theme1.peachpay.app':
			case 'https://theme2.peachpay.app':
			case 'https://theme3.peachpay.app':
			case 'https://theme4.peachpay.app':
			case 'https://theme5.peachpay.app':
			case 'https://qa.peachpay.app':
			case 'https://demo.peachpay.app':
			case 'http://localhost:8000':
				return 'https://dev.peachpay.app/';
			case 'https://woo.store.local':
			case 'https://store.local':
				return 'https://prod.peachpay.local/';
			default:
				return 'https://prod.peachpay.app/';
		}
	}
}

/**
 * Gets the left most subdomain out of a URL.
 *
 * @param string $url .
 */
function peachpay_subdomain( $url ) {
	$parsed_url = wp_parse_url( $url );
	$host       = explode( '.', $parsed_url['host'] );
	return $host[0];
}

/**
 * Gets a file version date for versioning URLs.
 *
 * @param string $file Relative path within the root plugin folder.
 */
function peachpay_file_version( $file ) {
	return gmdate( 'ymd-Gis', filemtime( PEACHPAY_ABSPATH . $file ) );
}

/**
 * Gets a files url inside the peachpay plugin. Used for css, js, imageages, and other assets.
 *
 * @example ```
 * peachpay_url( 'public/css/peachpay.css' ); // returns https://woo.store.local/wp-content/plugins/peachpay-for-woocommerce/public/css/peachpay.css
 * ```
 * @param string $file Relative path within the root plugin folder.
 */
function peachpay_url( $file ) {
	// "." is to include the current directory.
	return plugin_dir_url( PEACHPAY_ABSPATH . '.' ) . $file;
}
