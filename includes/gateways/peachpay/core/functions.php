<?php
/**
 * PeachPay utility functions
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets updated script fragments.
 *
 * @param array $fragments .
 */
function peachpay_native_checkout_data_fragment( $fragments ) {
	$fragments['script#peachpay-native-checkout-js-extra'] = '<script id="peachpay-native-checkout-js-extra">var peachpay_checkout_data = ' . wp_json_encode( peachpay()->native_checkout_data() ) . ';</script>';
	return $fragments;
}

/**
 * Stores deactivation feedback results.
 */
function peachpay_handle_deactivation_feedback() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-deactivation-feedback' ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'Invalid nonce. Please refresh the page and try again.',
			)
		);
	}

	$deactivation_reason      = isset( $_POST['deactivation_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['deactivation_reason'] ) ) : null;
	$deactivation_explanation = isset( $_POST['deactivation_explanation'] ) ? sanitize_text_field( wp_unslash( $_POST['deactivation_explanation'] ) ) : null;

	$feedback = array(
		'deactivation_reason' => $deactivation_reason,
	);

	if ( $deactivation_explanation ) {
		$feedback['deactivation_explanation'] = $deactivation_explanation;
	}

	update_option( 'peachpay_deactivation_feedback', $feedback );

	wp_send_json_success();
}
