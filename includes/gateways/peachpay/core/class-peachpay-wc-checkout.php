<?php
/**
 * Overridden checkout class.
 *
 * @phpcs:disable
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Overridden checkout class.
 */
class PeachPay_WC_Checkout extends WC_Checkout {
	public function __construct() {}

	public function validate_posted_data( &$data, &$errors ) {
		return parent::validate_posted_data( $data, $errors );
	}
}
