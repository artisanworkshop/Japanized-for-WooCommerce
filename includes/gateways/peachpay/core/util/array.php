<?php
/**
 * Array Utilities for PeachPay
 *
 * @package PeachPay
 */

namespace PeachPay\Util\Arrays;

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}


/**
 * Copies a single key value entry from one array to another. Overwrites existing values already present on the target array.
 *
 * @param string $key The target key to copy.
 * @param array  $src_array The array to search for the target key.
 * @param array  $target_array The array to copy the value found on the source array if it exist.
 */
function copy_array_entry( $key, $src_array, $target_array ) {

	if ( array_key_exists( $key, $src_array ) ) {
		$target_array[ $key ] = $src_array[ $key ];
	}

	return $target_array;
}

/**
 * Copies multiple keys value entries that are found that match a sub string of any key on the source array.
 *
 * @param string|null $key_selector The string to match to any key.
 * @param array       $src_array The array to search for matching keys.
 * @param array       $target_array The array to copy to if the key selector matches.
 */
function copy_array_entries( $key_selector, $src_array, $target_array ) {

	foreach ( $src_array  as $key => $value ) {
		if ( null === $key_selector || strpos( $key, $key_selector ) !== false ) {
			$target_array[ $key ] = $value;
		}
	}

	return $target_array;
}

/**
 * Selects a key value pair of the closest key match on a given object or returns null if not found.
 *
 * @param string $key_selector The string to use to match the.
 * @param array  $src_array The array to search.
 */
function select_closest_entry( $key_selector, $src_array ) {
	foreach ( $src_array as $key => $value ) {
		if ( strpos( $key, $key_selector ) !== false ) {
			return array(
				'key'   => $key,
				'value' => $value,
			);
		}
	}

	return null;
}
