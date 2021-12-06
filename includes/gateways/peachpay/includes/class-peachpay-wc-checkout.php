<?php

class PeachPay_WC_Checkout extends WC_Checkout {
	public function __construct() {}

	public function validate_posted_data( &$data, &$errors ) {
		return parent::validate_posted_data( $data, $errors );
	}
}
