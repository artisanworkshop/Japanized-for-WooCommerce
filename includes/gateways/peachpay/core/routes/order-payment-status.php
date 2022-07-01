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
 * Updates the order status for purchases with PeachPay.
 *
 * @param WP_REST_Request|Array $request The request object.
 */
function peachpay_rest_api_order_payment_status( $request ) {
	try {
		$status   = $request['status'];
		$order_id = $request['order_id'];

		if ( ! $status || ! $order_id ) {
			wp_send_json_error( 'Missing required parameters', 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'Order not found', 404 );
		}

		peachpay_add_partner_meta( $order );

		if ( 'success' === $status ) {

			$transaction_id     = $request['transaction_id'];
			$stripe_customer_id = $request['stripe_customer_id'];
			$stripe_details     = isset( $request['stripe'] ) && is_array( $request['stripe'] ) ? $request['stripe'] : null;

			if ( ! $transaction_id ) {
				wp_send_json_error( 'Missing required parameters for order success', 400 );
			}

			if ( null !== $stripe_details ) {
				$charge_fee = $stripe_details['charge_fee'];
				$charge_net = $stripe_details['charge_net'];

				if ( ! $charge_fee || ! $charge_net ) {
					wp_send_json_error( 'Missing required parameters for order success', 400 );
				}

				$charge_fee /= 100;
				$charge_net /= 100;

				$order->update_meta_data( '_stripe_fee', $charge_fee );
				$order->update_meta_data( '_stripe_net', $charge_net );
			}

			$order->set_transaction_id( $transaction_id );
			$order->add_meta_data( 'peachpay_is_test_mode', peachpay_is_test_mode() ? 'true' : 'false' );
			if ( $stripe_customer_id ) {
				$order->add_meta_data( PEACHPAY_PAYMENT_META_KEY, peachpay_build_stripe_order_payment_meta( $stripe_customer_id ) );
			}
			$order->payment_complete();

		} elseif ( 'failed' === $status ) {

			$order_failure_message = $request['status_message'];

			if ( ! $order_failure_message ) {
				wp_send_json_error( 'Missing required parameters for order failure', 400 );
			}

			$order->set_status( 'failed' );
			$order->save();

			$order->add_order_note( 'Payment failed. Reason: "' . $order_failure_message . '"' );
		} elseif ( 'cancelled' === $status ) {
			$order_failure_message = $request['status_message'];

			if ( ! $order_failure_message ) {
				wp_send_json_error( 'Missing required parameters for order cancellation', 400 );
			}
			$order->set_status( 'cancelled' );
			$order->save();

			$order->add_order_note( 'Payment cancelled. Reason: "' . $order_failure_message . '"' );
		}
	} catch ( Exception $error ) {
		wp_send_json_error( $error->getMessage(), 500 );
	}
}

/**
 * Updates the order status for payments with PeachPay.
 */
function peachpay_wc_ajax_order_payment_status() {
// phpcs:disable WordPress.Security.NonceVerification.Missing
	$status = '';
	if ( isset( $_POST['status'] ) ) {
		$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
	}

	$order_id = '';
	if ( isset( $_POST['order_id'] ) ) {
		$order_id = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
	}

	$transaction_id = '';
	if ( isset( $_POST['transaction_id'] ) ) {
		$transaction_id = sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) );
	}

	$stripe_customer_id = '';
	if ( isset( $_POST['stripe_customer_id'] ) ) {
		$stripe_customer_id = sanitize_text_field( wp_unslash( $_POST['stripe_customer_id'] ) );
	}

	$status_message = '';
	if ( isset( $_POST['status_message'] ) ) {
		$status_message = sanitize_text_field( wp_unslash( $_POST['status_message'] ) );
	}
	//phpcs:enable

	$request_data = array(
		'status'             => $status,
		'order_id'           => $order_id,
		'transaction_id'     => $transaction_id,
		'stripe_customer_id' => $stripe_customer_id,
		'status_message'     => $status_message,
	);
	peachpay_rest_api_order_payment_status( $request_data );
}

/**
 * Right now we have a partnership with Japanized for WooCommerce where our
 * plugin code is literally inside their plugin. To know which orders come from
 * our plugin that is within their plugin, we need metadata on the order.
 *
 * This function can later be expanded if we have similar partnerships.
 *
 * @param WC_Order $order The order for which to add metadata.
 * @return void
 */
function peachpay_add_partner_meta( $order ) {
	if ( get_option( 'wc4jp_peachpay' ) ) {
		$order->add_meta_data( 'peachpay_partner', 'wc4jp' );
	}
}
