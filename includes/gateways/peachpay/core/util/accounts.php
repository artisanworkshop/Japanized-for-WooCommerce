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
	$password = isset( $_POST['account_password'] ) ? sanitize_text_field( wp_unslash( $_POST['account_password'] ) ) : '';
	$email    = isset( $_POST['billing_email'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_email'] ) ) : '';
	//phpcs:enable

	// If password is not set then nothing left to do here.
	if ( ! $password || ! $email ) {
		return false;
	}

	if ( is_user_logged_in() ) {
		// Causes issues if already logged in and the password is present. Shouldn't happen but lets make sure.
        //phpcs:ignore
		unset( $_POST['account_password'] );
		return false;
	}

	// If the username/email already exist then lets log them in.
	if ( email_exists( $email ) ) {

		$info = array(
			'user_login'    => $email,
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
			return wp_send_json_error( 'Login failed due to incorrect password' );
		}

        //phpcs:ignore
		unset( $_POST['account_password'] );
		return true;
	}

	// If it makes it here then that means a password is present and the email is
	// not an existing account. The account will be created by order.
}
