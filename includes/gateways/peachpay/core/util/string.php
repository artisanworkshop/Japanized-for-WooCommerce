<?php
/**
 * PeachPay String utilities
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * String contains starting string.
 *
 * @param string $haystack The string to search.
 * @param string $needle What to look for in the string.
 */
function peachpay_starts_with( $haystack, $needle ) {
	$length = strlen( $needle );

	if ( 0 === $length ) {
		return false;
	}

	return ( substr( $haystack, 0, $length ) === $needle );
}

/**
 * String contains ending string.
 *
 * @param string $haystack The string to search.
 * @param string $needle What to look for in the string.
 */
function peachpay_ends_with( $haystack, $needle ) {
	$length = strlen( $needle );
	$start  = $length * - 1;

	return ( substr( $haystack, $start ) === $needle );
}

/**
 * Truncates a string to a given length. If the length is already less then the given length then the original string is returned.
 *
 * @param string $string The string to truncate.
 * @param int    $length The length to truncate the string to.
 */
function peachpay_truncate_str( $string, $length ) {
	return ( strlen( $string ) > $length ) ? substr( $string, 0, $length ) : $string;
}
