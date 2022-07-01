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
		'15minute',
		'30minute',
		'hourly',
		'2hour',
		'6hour',
		'12hour',
		'day',
		'2day',
		'weekly',
		'biweekly',
		'monthly',
	);

	foreach ( $times as $time ) {
		if ( wp_next_scheduled( 'peachpay_update_currency', array( $time ) ) ) {
			$timestamp = wp_next_scheduled( 'peachpay_update_currency', array( $time ) );
			wp_unschedule_event( $timestamp, 'peachpay_active_currency', array( $time ) );
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
