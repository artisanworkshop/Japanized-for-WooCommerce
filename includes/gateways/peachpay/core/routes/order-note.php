<?php
/**
 * PeachPay routes for setting order payment status
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Adds an order note for a customer order
 */
function peachpay_wc_ajax_order_note() {
    // phpcs:disable WordPress.Security.NonceVerification.Missing
	$order_id = '';
	if ( isset( $_POST['id'] ) ) {
		$order_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	}

	$note = '';
	if ( isset( $_POST['note'] ) ) {
		$note = sanitize_text_field( wp_unslash( $_POST['note'] ) );
	}
	//phpcs:enable

	if ( ! $note || ! $order_id ) {
		wp_send_json_error( 'Missing required parameters', 400 );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( 'Order not found', 404 );
	}

	$order->add_order_note( $note );
}
