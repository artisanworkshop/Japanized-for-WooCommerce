<?php
/**
 * PeachPay Plugin helpers.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gets the PeachPay plugin capabilities.
 */
function peachpay_fetch_plugin_capabilities() {

	$response = wp_remote_post(
		peachpay_api_url() . 'api/v1/plugin/capabilities',
		array(
			'body'     => array(
				'domain'      => home_url(),
				'merchant_id' => peachpay_plugin_merchant_id(),
			),
			'blocking' => true,
		)
	);

	if ( ! peachpay_response_ok( $response ) ) {
		$code = wp_remote_retrieve_response_code( $response );
		if ( 404 === $code ) {
			delete_option( 'peachpay_merchant_id' );
		}
		return array();
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true )['data'];

	update_option( 'peachpay_merchant_id', $data['merchant_id'] );

	return $data;
}

/**
 * Determines if a peachpay capability is connected.
 *
 * @param string $capability_key The key of the capability to check for.
 * @param array  $plugin_capabilities The array of capabilities.
 */
function peachpay_plugin_has_capability( $capability_key, $plugin_capabilities ) {

	if ( ! is_array( $plugin_capabilities ) ) {
		return false;
	}

	if ( ! array_key_exists( $capability_key, $plugin_capabilities ) || ! is_array( $plugin_capabilities[ $capability_key ] ) ) {
		return false;
	}

	$capability = $plugin_capabilities[ $capability_key ];

	if ( ! array_key_exists( 'connected', $capability ) ) {
		return false;
	}

	return (bool) $capability['connected'];
}

/**
 * Gets whether a given capability has a configuration object.
 *
 * @param string $capability_key The key of the capability to check for.
 * @param array  $plugin_capabilities The array of capabilities.
 */
function peachpay_plugin_has_capability_config( $capability_key, $plugin_capabilities ) {

	if ( ! is_array( $plugin_capabilities ) ) {
		return false;
	}

	if ( ! array_key_exists( $capability_key, $plugin_capabilities ) || ! is_array( $plugin_capabilities[ $capability_key ] ) ) {
		return false;
	}

	$capability = $plugin_capabilities[ $capability_key ];

	if ( ! array_key_exists( 'config', $capability ) ) {
		return false;
	}

	return $capability['config'];
}

/**
 * Gets a specific capability data.
 *
 * @param string $capability_key The key of the capability to check for.
 * @param array  $plugin_capabilities The array of capabilities.
 */
function peachpay_plugin_get_capability( $capability_key, $plugin_capabilities ) {
	if ( ! peachpay_plugin_has_capability( $capability_key, $plugin_capabilities ) ) {
		return null;
	}

	return $plugin_capabilities[ $capability_key ];
}

/**
 * Gets a specific capability config array.
 *
 * @param string $capability_key The key of the capability to check for.
 * @param array  $plugin_capabilities The array of capabilities.
 */
function peachpay_plugin_get_capability_config( $capability_key, $plugin_capabilities ) {
	if ( ! peachpay_plugin_has_capability_config( $capability_key, $plugin_capabilities ) ) {
		return null;
	}

	return $plugin_capabilities[ $capability_key ]['config'];
}

/**
 * Gets the PeachPay merchant id.
 */
function peachpay_plugin_merchant_id() {
	return get_option( 'peachpay_merchant_id', '' );
}
