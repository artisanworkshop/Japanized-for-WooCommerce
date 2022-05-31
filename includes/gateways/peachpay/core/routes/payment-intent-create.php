<?php
/**
 * Updates a payment intent for the current cart.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Creates a stripe payment intent for the given cart.
 */
function peachpay_wc_ajax_create_stripe_payment_intent() {

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$session_id = '';
	if ( isset( $_POST['session']['id'] ) ) {
		$session_id = sanitize_text_field( wp_unslash( $_POST['session']['id'] ) );
	}

	$customer_id = '';
	if ( isset( $_POST['session']['stripe'] ) && isset( $_POST['session']['stripe']['customer_id'] ) ) {
		$customer_id = sanitize_text_field( wp_unslash( $_POST['session']['stripe']['customer_id'] ) );
	}

	$connect_id = '';
	if ( isset( $_POST['session']['stripe'] ) && isset( $_POST['session']['stripe']['connect_id'] ) ) {
		$connect_id = sanitize_text_field( wp_unslash( $_POST['session']['stripe']['connect_id'] ) );
	}

	$payment_method_id = '';
	if ( isset( $_POST['session']['stripe'] ) && isset( $_POST['session']['stripe']['payment_method_id'] ) ) {
		$payment_method_id = sanitize_text_field( wp_unslash( $_POST['session']['stripe']['payment_method_id'] ) );
	}

	$payment_method_type = '';
	if ( isset( $_POST['session']['stripe'] ) && isset( $_POST['session']['stripe']['payment_method_type'] ) ) {
		$payment_method_type = sanitize_text_field( wp_unslash( $_POST['session']['stripe']['payment_method_type'] ) );
	}

	$order_id = 0;
	if ( isset( $_POST['order']['id'] ) ) {
		$order_id = sanitize_text_field( wp_unslash( $_POST['order']['id'] ) );
	}

	$order_data = null;
	if ( isset( $_POST['order']['data'] ) ) {
		$order_data = json_decode( sanitize_text_field( wp_unslash( $_POST['order']['data'] ) ), true );
	}
	//phpcs:enable

	$cart_amount = WC()->cart->get_total( 'raw' );

	$response = wp_remote_post(
		peachpay_api_url() . 'api/v1/stripe/payment-intent/create',
		array(
			'body' => array(
				'session' => array(
					'id'             => $session_id,
					'plugin_version' => PEACHPAY_VERSION,
					'merchant_url'   => get_site_url(),
					'merchant_name'  => get_bloginfo( 'name' ),
					'stripe'         => array(
						'customer_id'         => $customer_id,
						'connect_id'          => $connect_id,
						'payment_method_id'   => $payment_method_id,
						'payment_method_type' => $payment_method_type,
					),
				),
				'order'   => array(
					'id'       => $order_id,
					'amount'   => $cart_amount,
					'currency' => peachpay_currency_code(),
					'data'     => $order_data,
				),
			),
		)
	);

	if ( ! peachpay_response_ok( $response ) ) {
		wp_send_json(
			array(
				'success'        => false,
				'error_message'  => 'Non 200 response from PeachPay API server.',
				'error_response' => wp_remote_retrieve_body( $response ),
			)
		);

		return;
	}

	$data = wp_remote_retrieve_body( $response );

	if ( is_wp_error( $data ) ) {
		wp_send_json(
			array(
				'success' => false,
			)
		);
	}

	// Optimal would be not to decode -> encode but I know this works for now.
	wp_send_json( json_decode( $data ) );
	wp_die();
}
