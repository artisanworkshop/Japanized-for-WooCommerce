<?php
/**
 * Adds a hook for post update if a stripe email is added will call the function peachpay_email_stripe_welcome.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

add_filter( 'update_option_peachpay_connected_stripe_account', 'peachpay_post_connect_stripe', 10, 2 );

/**
 * Checks if a stripe account email is not equal to the old stripe email if it is.
 *
 * @param array $old the old settings that were saved.
 * @param array $new the new settings that have changed.
 */
function peachpay_post_connect_stripe( $old, $new ) {
	if ( false === $new ) {
		return $new;
	}
	if ( is_array( $old ) && is_array( $new ) && ( array_key_exists( 'email', $old ) && array_key_exists( 'email', $new ) ) && $old['email'] === $new['email'] ) {
		return $new;
	}
	if ( is_array( $new ) && array_key_exists( 'email', $new ) ) {
		peachpay_email_stripe_welcome( $new['email'] );
	}

	return $new;
}

/**
 * Sends a welcome email to stripe when stripe account added.
 *
 * @param string $email the stripe account email specified in the new settings.
 */
function peachpay_email_stripe_welcome( $email ) {
	$body = array(
		'email'          => $email,
		'merchantDomain' => explode( 'https://', get_site_url() )[1],
	);
	peachpay_email( $body, 'mail/welcome' );
}
