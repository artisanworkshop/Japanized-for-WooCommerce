<?php
/**
 * Handles Apple Pay domain registration
 *
 * @package peachpay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Handles serving the domain association file.
 */
function peachpay_parse_domain_request() {
	$request_uri = '/.well-known/apple-developer-merchantid-domain-association';
	if ( ! isset( $_SERVER['REQUEST_URI'] ) || $request_uri !== $_SERVER['REQUEST_URI'] ) {
		return;
	}

	$gateway   = get_option( 'peachpay_attempt_applepay', 'stripe' );
	$file_path = PeachPay::get_plugin_path() . '/core/payments/' . $gateway . '/apple-developer-merchantid-domain-association';
	header( 'Content-Type: text/plain;charset=utf-8' );
	echo esc_html( file_get_contents( $file_path ) ); // phpcs:ignore
	exit;
}
add_action( 'parse_request', 'peachpay_parse_domain_request', 10, 1 );
