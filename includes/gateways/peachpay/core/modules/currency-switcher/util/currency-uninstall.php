<?php
/**
 * A file for cleaning up currency switcher this file holds the uninstall settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Unschedule all pp cron events on deactivation.
 */
function peachpay_unschedule_all_currency() {
	$times = array(
		'none',
		'15minute',
		'30minute',
		'hourly',
		'6hour',
		'12hour',
		'daily',
		'2day',
		'weekly',
		'biweekly',
		'monthly',
		'custom',
	);

	while ( wp_next_scheduled( 'peachpay_update_currency' ) ) {
		$timestamp = wp_next_scheduled( 'peachpay_update_currency' );
		wp_unschedule_event( $timestamp, 'peachpay_update_currency' );
	}
	foreach ( $times as $time ) {
		while ( wp_next_scheduled( 'peachpay_update_currency', array( $time ) ) ) {
			$timestamp = wp_next_scheduled( 'peachpay_update_currency', array( $time ) );
			wp_unschedule_event( $timestamp, 'peachpay_update_currency', array( $time ) );
		}
	}
}

/**
 * On plugin deactivation remove the currency cookie.
 */
function peachpay_remove_currency_cookie() {
	if ( $_COOKIE && ! empty( $_COOKIE['pp_active_currency'] ) ) {
		unset( $_COOKIE['pp_active_currency'] );
	}
}
