<?php
/**
 * PeachPay Stripe helpers
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gets the stripe account payment capabilities
 *
 * @param string $connect_id The stripe connect account id.
 */
function peachpay_fetch_stripe_capabilities( $connect_id ) {
	$endpoint = peachpay_api_url() . 'api/v1/stripe/capabilities';

		$data = array(
			'session' => array(
				'merchant_url' => get_site_url(),
				'stripe'       => array(
					'connect_id' => $connect_id,
				),
			),
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'body'    => $data,
				'timeout' => 60,
			)
		);

	if ( ! peachpay_response_ok( $response ) ) {
		return array();
	}

	$body = wp_remote_retrieve_body( $response );
	if ( is_wp_error( $body ) ) {
		return array();
	}

	$json = json_decode( $body, true );
	if ( ! $json['success'] ) {
		return array();
	}

	return $json['data']['capabilities'];
}

/**
 * Gets a stripe payment capability status.
 *
 * @param array  $capabilities The array of stripe payment capability.
 * @param string $payment_key The payment capability to retrieve a status for.
 */
function peachpay_stripe_capability( $capabilities, $payment_key ) {
	if ( ! array_key_exists( $payment_key, $capabilities ) ) {
		return 'inactive';
	}

	return $capabilities[ $payment_key ];
}

/**
 * Creates the Stripe connect signup link.
 *
 * @param string $site_url The admin URL of the current site.
 * @param string $home_url The home URL of the current site.
 */
function peachpay_stripe_signup_url( $site_url, $home_url ) {
	// phpcs:ignore
	$TEST_STRIPE_CLIENT_ID = 'ca_HHK0LPM3N7jbW1aV610tueC8zVOBtW2D';
	// phpcs:ignore
	$LIVE_STRIPE_CLIENT_ID = 'ca_HHK0N5DreIcJJAyqGbeOE77hAZD9gCFg';
	// phpcs:ignore
	$stripe_client_id = ( peachpay_is_local_development_site() || peachpay_is_staging_site() ) ? $TEST_STRIPE_CLIENT_ID : $LIVE_STRIPE_CLIENT_ID;

	$state               = new stdClass();
	$state->merchant_url = $home_url;
	$state->wp_admin_url = $site_url;

	// Using JSON to pass multiple parameters through state.
	$state_json = wp_json_encode( $state );
	// Base64 encode as JSON includes chars removed by esc_url().
	// phpcs:ignore
	$state_base64 = base64_encode( $state_json );

	$redirect_uri = peachpay_determine_stripe_redirect_uri();

	return "https://dashboard.stripe.com/oauth/v2/authorize?response_type=code&client_id=$stripe_client_id&scope=read_write&state=$state_base64&stripe_user[url]=$home_url&redirect_uri=$redirect_uri";
}

/**
 * Get the correct redirect URI that will be given to Stripe based on the
 * current environment.
 */
function peachpay_determine_stripe_redirect_uri() {
	if ( peachpay_is_local_development_site() ) {
		return 'https://dev.peachpay.local/connect/oauth';
	}

	if ( peachpay_is_staging_site() ) {
		return 'https://dev.peachpay.app/connect/oauth';
	}

	return 'https://prod.peachpay.app/connect/oauth';
}

/**
 * Determines if the given URL is supported for Apple Pay though stripe.
 */
function peachpay_stripe_supported_applepay_url() {
	return (
		! peachpay_is_localhost_url() &&
		peachpay_is_https_url()
	);
}
