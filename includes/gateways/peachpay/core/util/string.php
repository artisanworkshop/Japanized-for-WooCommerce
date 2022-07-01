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
