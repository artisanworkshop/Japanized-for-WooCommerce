<?php
/**
 * Bot protection functions.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Determines whether the order should go through depending on captcha.
 */
function peachpay_captcha_validation() {
    // PHPCS:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$bot_protection_is_enabled = peachpay_bot_protection_enabled();

	if ( $bot_protection_is_enabled ) {
		$captcha_token = isset( $_POST['peachpay_captcha_token'] ) ? wp_unslash( $_POST['peachpay_captcha_token'] ) : null;

		if ( ! $captcha_token ) {
			wc_add_notice( __( 'Failed to generate captcha token. Please try again or contact the store for assistance.', 'peachpay-for-woocommerce' ), 'error' );
			return;
		}

		$secret_key = PeachPay_Bot_Protection_Settings::get_setting( 'secret_key' );

		$data = array(
			'secret'   => $secret_key,
			'response' => $captcha_token,
		);

		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'headers' => $headers,
				'body'    => $data,
			)
		);

		if ( is_wp_error( $response ) ) {
			wc_add_notice( __( 'Failed to connect to the captcha verification service. Please try again or contact the store for assistance.', 'peachpay-for-woocommerce' ), 'error' );
			return;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $response_body, true );

		if ( empty( $response_data ) ) {
			wc_add_notice( __( 'Invalid response from the captcha verification service. Please try again or contact the store for assistance.', 'peachpay-for-woocommerce' ), 'error' );
			return;
		}

		// condition for successful captcha token
		if ( $response_data['success'] && $response_data['score'] >= 0.5 ) {
			return;
		}

		wc_add_notice( __( 'Captcha verification failed. Please try again or contact the store for assistance.', 'peachpay-for-woocommerce' ), 'error' );
	}

    // PHPCS:enable
}

/**
 * Function to determine whether bot protection is enabled.
 */
function peachpay_bot_protection_enabled() {
	$enabled = 'yes' === PeachPay_Bot_Protection_Settings::get_setting( 'enabled' ) &&
		! empty( PeachPay_Bot_Protection_Settings::get_setting( 'site_key' ) ) &&
		! empty( PeachPay_Bot_Protection_Settings::get_setting( 'secret_key' ) );

	return $enabled;
}

/**
 * Function to add bot protection data to feature set.
 *
 * @param array $data Peachpay data array.
 */
function peachpay_bot_protection_feature_flag( $data ) {
	$enabled = peachpay_bot_protection_enabled();

	$data['bot_protection'] = array(
		'enabled' => $enabled,
		'version' => 1,
	);

	$data['bot_protection']['metadata'] = array(
		'site_key' => $enabled ? PeachPay_Bot_Protection_Settings::get_setting( 'site_key' ) : '',
	);

	return $data;
}
