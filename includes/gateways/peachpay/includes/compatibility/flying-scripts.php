<?php
/**
 * Support for the Flying Scripts by Gijo Varghese
 * Plugin: https://wordpress.org/plugins/flying-scripts/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Priority is 11 to make sure it gets executed after peachpay_collect_debug_info
add_filter( 'peachpay_script_data', 'peachpay_remove_debug_info_plugins', 11, 1 );

/**
 * Flying Scripts is often used to optimize scripts by keyword, and those
 * keywords are often things like "facebook", which show up in the list of
 * plugins and trigger Flying Scripts to mess with the php_data global JS script
 * that we add that defers its loading, which causes PeachPay to break. We can
 * avoid it touching our script by not including the keyword heavy plugin list.
 */
function peachpay_remove_debug_info_plugins( array $php_data ) {
	unset( $php_data['debug']['plugins'] );
	return $php_data;
}
