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
	if ( $request instanceof WP_REST_Request ) {
		$request = $request->get_json_params();
	}

	// These are function names mapped to gateways and status
	// changes. Only the default scenarios are defined in this file.
	$gateway_callbacks = array(
		'peachpay_paypal'           => array(
			'success'    => 'peachpay_handle_paypal_success_status',
			'processing' => 'peachpay_handle_paypal_processing_status',
			'on-hold'    => 'peachpay_handle_paypal_pending_status',
			'failed'     => 'peachpay_handle_default_failed_status',
		),
		'peachpay_square_card'      => array(
			'success'   => 'peachpay_handle_square_success_status',
			'failed'    => 'peachpay_handle_default_failed_status',
			'cancelled' => 'peachpay_handle_default_cancelled_status',
		),
		'peachpay_square_ach'       => array(
			'success'   => 'peachpay_handle_square_success_status',
			'failed'    => 'peachpay_handle_default_failed_status',
			'cancelled' => 'peachpay_handle_default_cancelled_status',
		),
		'peachpay_square_cashapp'   => array(
			'success'   => 'peachpay_handle_square_success_status',
			'failed'    => 'peachpay_handle_default_failed_status',
			'cancelled' => 'peachpay_handle_default_cancelled_status',
		),
		'peachpay_square_afterpay'  => array(
			'success'   => 'peachpay_handle_square_success_status',
			'failed'    => 'peachpay_handle_default_failed_status',
			'cancelled' => 'peachpay_handle_default_cancelled_status',
		),
		'peachpay_square_applepay'  => array(
			'success'   => 'peachpay_handle_square_success_status',
			'failed'    => 'peachpay_handle_default_failed_status',
			'cancelled' => 'peachpay_handle_default_cancelled_status',
		),
		'peachpay_square_googlepay' => array(
			'success'   => 'peachpay_handle_square_success_status',
			'failed'    => 'peachpay_handle_default_failed_status',
			'cancelled' => 'peachpay_handle_default_cancelled_status',
		),
		'peachpay_amazonpay'        => array(
			'success' => 'peachpay_handle_amazonpay_success_status',
			'failed'  => 'peachpay_handle_default_failed_status',
		),
	);

	try {

		$order = wc_get_order( $request['order_id'] );
		if ( ! $order ) {
			wp_send_json_error( 'Required field "order_id" was invalid or missing', 400 );
			return;
		}

		$order_status = $request['status'];
		if ( ! $order_status ) {
			wp_send_json_error( 'Required field "status" was missing or invalid', 400 );
			return;
		}

		if ( $order instanceof WC_Subscription ) {
			// This means the order status update was triggered from a payment intent confirmed by /api/v1/stripe/payment/renew.
			// Because of this, we want to update both the subscription order and the last payment order (they are two separate objects).
			if ( 'success' === $order_status ) {
				$order->update_status( 'active' );
			} else {
				$order->set_status( $order_status );
			}
			$order->save();
			$order = wc_get_order( $order->get_last_order( 'ids' ) );
		}

		if ( 'success' === $order_status ) {
			if ( 'completed' === $order->get_status() || 'processing' === $order->get_status() ) {
				wp_send_json_success( 'Status already set', 200 );
				return;
			} elseif ( $order->get_status() === $order_status ) {
				wp_send_json_success( 'Status already set', 200 );
				return;
			}
		}

		peachpay_order_add_partner_meta( $order );
		peachpay_order_add_test_mode_meta( $order );

		$order_note     = '';
		$payment_method = $order->get_payment_method();

		if ( array_key_exists( $payment_method, $gateway_callbacks ) && array_key_exists( $order_status, $gateway_callbacks[ $payment_method ] ) ) {
			$order_note = call_user_func( $gateway_callbacks[ $payment_method ][ $order_status ], $order, $request );
		} else {
			wp_send_json_error( 'Unknown payment gateway or order status.', 400 );
			return;
		}

		if ( 'success' === $order_status ) {
			$order->payment_complete();
		} else {
			$order->set_status( $order_status );
		}
		$order->save();

		if ( $order_note ) {
			$order->add_order_note( $order_note );
		}

		$order->save();
		wp_send_json_success( 'Order status updated to "' . $order_status . '"' );
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
		$order_id = intval( wp_unslash( $_POST['order_id'] ) );
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
		'order_id'       => $order_id,
		'status'         => $status,
		'status_message' => $status_message,
		'stripe'         => array(
			'stripe_customer_id' => $stripe_customer_id,
			// I assign the transaction_id to both the stripe section and the paypal one. This is ok because the correct gateway will handle the information.
			'charge_id'          => $transaction_id,
		),
		'paypal'         => array(
			// I assign the transaction_id to both the stripe section and the paypal one. This is ok because the correct gateway will handle the information.
			'transaction_id' => $transaction_id,
		),
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
function peachpay_order_add_partner_meta( $order ) {
	if ( get_option( 'wc4jp_peachpay' ) ) {
		$order->add_meta_data( 'peachpay_partner', 'wc4jp', true );
	}
}

/**
 * Adds meta data information about the current test mode status of peachpay.
 *
 * @param WC_Order $order The order object to operate on.
 */
function peachpay_order_add_test_mode_meta( $order ) {

	// We do not want to accidentally update the test mode status in the case of a
	// webhook coming in later and the test mode status is different.
	if ( $order->meta_exists( 'peachpay_is_test_mode' ) ) {
		return;
	}

	if ( peachpay_is_test_mode() ) {
		if ( '1' === $order->get_meta( 'has_subscription' ) ) {
			$subscriptions              = wcs_get_subscriptions_for_renewal_order( $order );
			$subscription               = array_pop( $subscriptions );
			$initial_subscription_order = wc_get_order( $subscription->get_parent_id() );

			if ( 'true' !== $initial_subscription_order->get_meta( 'peachpay_is_test_mode' ) && '1' !== $initial_subscription_order->get_meta( 'peachpay_is_test_mode' ) ) {
				/**
				 * Subscription renewal orders should not have peachpay_is_test_mode
				 * added to meta data if their initial subscription purchase was not
				 * placed in test mode.
				 */
				return;
			}
		}
		$order->add_meta_data( 'peachpay_is_test_mode', 'true', true );
	}
}

/**
 * Default hook for failed order-status.
 *
 * @param WC_Order $order The order to operate on.
 * @param array    $request The request data.
 */
function peachpay_handle_default_failed_status( $order, $request ) {
	$message = peachpay_array_value( $request, 'status_message' );

	if ( ! $message ) {
		wp_send_json_error( 'Required field "status_message" is missing or invalid', 400 );
	}

	return 'Payment failed. Reason: "' . $message . '"';
}

/**
 * Default hook for cancelled order-status.
 *
 * @param WC_Order $order The order to operate on.
 * @param array    $request The request data.
 */
function peachpay_handle_default_cancelled_status( $order, $request ) {
	$message = peachpay_array_value( $request, 'status_message' );

	if ( ! $message ) {
		wp_send_json_error( 'Required field "status_message" is missing or invalid', 400 );
	}

	return 'Payment cancelled. Reason: "' . $message . '"';
}
