<?php
/**
 * PeachPay Square GooglePay gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
class PeachPay_Square_GooglePay_Gateway extends PeachPay_Square_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                    = 'peachpay_square_googlepay';
		$this->icon                  = peachpay_url( 'public/img/marks/google-pay.svg' );
		$this->settings_priority     = 2;
		$this->payment_method_family = __( 'Digital wallet', 'peachpay-for-woocommerce' );

		// Customer facing title and description.
		$this->title       = 'Google Pay';
		$this->description = '';

		parent::__construct();
	}
}
