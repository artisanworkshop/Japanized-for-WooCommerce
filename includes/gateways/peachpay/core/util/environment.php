<?php
/**
 * PeachPay environment utility
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gets the current URL.
 */
function peachpay_get_current_url() {
	// phpcs:ignore
	return home_url( $_SERVER['REQUEST_URI'] );
}

/**
 * Detects if a merchant store is running on localhost or not.
 *
 * @param string $url A url to check if it is a localhost URL. If not provided the current site URL is used.
 */
function peachpay_is_localhost_url( $url = null ) {

	if ( ! $url ) {
		$url = peachpay_get_current_url();
	}

	if ( false !== strpos( $url, '127.0.0.1' ) || false !== strpos( $url, 'localhost' ) ) {
		return true;
	}

	$url_parts = wp_parse_url( $url );

	$host = $url_parts['host'];

	return (
	// Ends with.
	// https://en.wikipedia.org/wiki/Special-use_domain_name.
	peachpay_ends_with( $host, '.dev' ) ||
	peachpay_ends_with( $host, '.test' ) ||
	peachpay_ends_with( $host, '.staging' ) ||
	peachpay_ends_with( $host, '.local' ) ||
	peachpay_ends_with( $host, '.localhost' ) ||
	peachpay_ends_with( $host, '.example' ) ||
	peachpay_ends_with( $host, '.internal' ) ||
	peachpay_ends_with( $host, '.invalid' )
	);
}

/**
 * Detects if a merchant is using HTTPS
 *
 * @param string $url The url to check if it is HTTPS. If not provided the current site URL is used.
 */
function peachpay_is_https_url( $url = null ) {
	if ( ! $url ) {
		$url = peachpay_get_current_url();
	}

	$url_parts = wp_parse_url( $url );

	return 'https' === $url_parts['scheme'];
}

/**
 * Indicates if the current website is for local development.
 */
function peachpay_is_local_development_site() {
	switch ( get_home_url() ) {
		case 'https://store.local':
		case 'https://woo.store.local':
			return true;
		default:
			return false;
	}
}

/**
 * Indicates if the current website is a staging site.
 */
function peachpay_is_staging_site() {
	switch ( get_home_url() ) {
		case 'https://woo.peachpay.app':
		case 'https://theme1.peachpay.app':
		case 'https://theme2.peachpay.app':
		case 'https://theme3.peachpay.app':
		case 'https://theme4.peachpay.app':
		case 'https://theme5.peachpay.app':
		case 'https://qa.peachpay.app':
		case 'https://qa-david.peachpay.app':
		case 'https://qa-vikrant.peachpay.app':
		case 'https://demo-plum.peachpay.app':
		case 'https://demo.peachpay.app':
		case 'https://ui-test.peachpay.app':
			return true;
		default:
			return false;
	}
}
