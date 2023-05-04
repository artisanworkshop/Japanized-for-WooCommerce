<?php
/**
 * Sets up and defines the PeachPay amazon pay rest api endpoints.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Init amazon pay rest routes
 */
function peachpay_amazonpay_rest_api_init() {
	$base = PEACHPAY_ROUTE_BASE . '/amazonpay';

	register_rest_route(
		$base,
		'/connect/oauth',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_amazonpay_oath_connect_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$base,
		'/signup',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_amazonpay_signup_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		$base,
		'/status',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_amazonpay_status_rest',
			'permission_callback' => '__return_true',
		)
	);
}

add_action( 'rest_api_init', 'peachpay_amazonpay_rest_api_init' );

/**
 * Handles key pair verification for API server If the public key is valid for this merchants site
 * it will decrypt the key and send the data back to our API server.
 */
function peachpay_amazonpay_oath_connect_rest() {
	$required = array( 'publicKeyId', 'merchantId', 'storeId' );
	foreach ( $required as $key ) {
		// phpcs:ignore
		if ( ! isset( $_POST[$key] ) ) {
			wp_send_json(
				array(
					'result'  => 'success',
					'message' => 'POST request missing required data.',
				),
				400
			);
		}
	}

	// phpcs:ignore
	$public_key              = $_POST['publicKeyId'];
	$public_key_id_encrypted = rawurldecode( $public_key );
	$keys                    = peachpay_validate_amazonpay_public_key_id( $public_key_id_encrypted );

	if ( ! $keys ) {
		update_option( 'peachpay_amazonpay_onboarding_status', 'failed' );
		wp_send_json(
			array(
				'result'  => 'error',
				'message' => 'Unable to validate POST request',
			),
			401
		);
		return;
	}

	update_option( 'peachpay_amazonpay_onboarding_status', 'connected' );
	peachpay_set_settings_option( 'peachpay_payment_options', 'amazonpay_enable', true );
	peachpay_set_settings_option( 'peachpay_payment_options', 'amazonpay_temp_keys', array() );

	wp_send_json(
		array(
			'result' => 'success',
			'body'   => $keys,
		),
		200
	);
}

/**
 * Routes incomming requests to the amazon pay sign-up URL with proper queries added.
 */
function peachpay_amazonpay_signup_rest() {
	$base_country = wc_get_base_location()['country'];

	$queries = array(
		'source'                   => 'SPPL',
		'spId'                     => peachpay_get_amazonpay_spid( $base_country ),
		'merchantCountry'          => $base_country,
		'locale'                   => get_locale(),
		'keyShareURL'              => peachpay_amazonpay_get_keyshare_url(),
		'onboardingVersion'        => '2',
		'merchantLoginDomains[]'   => home_url(),
		'merchantPrivacyNoticeURL' => home_url() . '/?page_id=3',
		'publicKey'                => peachpay_generate_amazonpay_public_key(),
	);

	update_option( 'peachpay_amazonpay_onboarding_status', 'waiting' );
	header( 'Location: ' . add_query_arg( $queries, peachpay_amazonpay_register_link( $base_country ) ) );
	exit();
}

/**
 * Returns the status of of amazonpay, either connected, awaiting, or failed.
 * Primarily used for amazon connect JS script to update UI accordingly.
 */
function peachpay_amazonpay_status_rest() {
	if ( peachpay_amazonpay_account_connected() ) {
		update_option( 'peachpay_amazonpay_onboarding_status', 'connected' );
	}

	$status = get_option( 'peachpay_amazonpay_onboarding_status', 'waiting' );

	wp_send_json(
		array(
			'status' => $status,
		)
	);
}
