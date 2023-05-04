<?php
/**
 * PeachPay Square ACHBank Gateway
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
class PeachPay_Square_ACH_Gateway extends PeachPay_Square_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                    = 'peachpay_square_ach';
		$this->icon                  = PeachPay::get_asset_url( 'img/marks/bank.svg' );
		$this->settings_priority     = 3;
		$this->payment_method_family = __( 'Bank debit', 'peachpay-for-woocommerce' );

		// Customer facing title and description.
		$this->title       = 'US Bank Account';
		$this->description = '';
		$this->countries   = array( 'US' );
		$this->currencies  = array( 'USD' );

		parent::__construct();
	}
}
