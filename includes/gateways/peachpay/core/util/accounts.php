<?php
/**
 * Utilities for merchant accounts.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Logs in a user and/or creates an account first if an account already exist.
 */
function peachpay_login_user() {
    //phpcs:disable WordPress.Security.NonceVerification.Missing
	$account_type = isset( $_POST['account_type'] ) ? sanitize_text_field( wp_unslash( $_POST['account_type'] ) ) : '';
	$user_id      = isset( $_POST['account_user_id'] ) ? sanitize_text_field( wp_unslash( $_POST['account_user_id'] ) ) : '';
	$password     = isset( $_POST['account_password'] ) ? sanitize_text_field( wp_unslash( $_POST['account_password'] ) ) : '';
	//phpcs:enable

	// If password is not set then nothing left to do here.
	if ( ! $password || ! $user_id ) {
		return false;
	}

	if ( is_user_logged_in() ) {
		// Causes issues if already logged in and the password is present. Shouldn't happen but lets make sure.
		//phpcs:ignore
		unset( $_POST['account_type'] );
        //phpcs:ignore
		unset( $_POST['account_user_id'] );
		//phpcs:ignore
		unset( $_POST['account_password'] );
		return false;
	}

	if ( 'login' === $account_type ) {
		if ( ! email_exists( $user_id ) && ! username_exists( $user_id ) ) {
			return wp_send_json(
				array(
					'result'   => 'failure',
					'messages' => __( 'Login failed: Email or Username does not exist.', 'peachpay-for-woocommerce' ),
				)
			);
		}

		$info = array(
			'user_login'    => $user_id,
			'user_password' => $password,
			'remember'      => true,
		);

		$user = wp_signon( $info, is_ssl() );

		if ( ! is_wp_error( $user ) ) {
			$id = $user->ID;

			wc_set_customer_auth_cookie( $id );
			WC()->session->set( 'reload_checkout', true );

			do_action( 'wp_login', $user->user_login, $user );

			$_REQUEST['_wpnonce'] = wp_create_nonce( 'woocommerce-process_checkout' );
		} else {
			return wp_send_json(
				array(
					'result'   => 'failure',
					'messages' => __( 'Login failed: incorrect password.', 'peachpay-for-woocommerce' ),
				)
			);
		}

		//phpcs:ignore
		unset( $_POST['account_type'] );
        //phpcs:ignore
		unset( $_POST['account_user_id'] );
        //phpcs:ignore
		unset( $_POST['account_password'] );
		return true;
	} elseif ( 'register' === $account_type ) {
		// Modified code from process_customer of class-wc-checkout.php.
		$args = array(
			'first_name' => ! empty( $data['billing_first_name'] ) ? $data['billing_first_name'] : '',
			'last_name'  => ! empty( $data['billing_last_name'] ) ? $data['billing_last_name'] : '',
		);
		// Generate a username regardless of WC settings.
		// Remove later if username field is added to registration.
		$username = wc_create_new_customer_username( $user_id, $args );

		$customer_id = wc_create_new_customer(
			$user_id,
			$username,
			$password,
			$args
		);

		if ( is_wp_error( $customer_id ) ) {
			return wp_send_json(
				array(
					'result'   => 'failure',
					'messages' => $customer_id->get_error_message(),
				)
			);
		}

		wc_set_customer_auth_cookie( $customer_id );

		// As we are now logged in, checkout will need to refresh to show logged in data.
		WC()->session->set( 'reload_checkout', true );

		// Also, recalculate cart totals to reveal any role-based discounts that were unavailable before registering.
		WC()->cart->calculate_totals();

		//phpcs:ignore
		unset( $_POST['account_type'] );
        //phpcs:ignore
		unset( $_POST['account_user_id'] );
        //phpcs:ignore
		unset( $_POST['account_password'] );
		return true;
	}
}
