<?php
/**
 * Stripe WC gateway.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * This class allows us to submit orders with the PeachPay Amazon Pay gateway.
 */
// phpcs:ignore
class PeachPay_AmazonPay_Gateway extends PeachPay_Abstract_WC_Gateway {
	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->id    = 'peachpay_amazonpay';
		$this->title = 'PeachPay (Amazon Pay)';
		// This needs to be here even though it's blank. Some plugins assume
		// gateways have a description and crash if they do not.
		$this->description  = '';
		$this->has_fields   = false;
		$this->method_title = 'PeachPay (Amazon Pay)';
		$this->supports     = array(
			'products',
		);
	}

	/**
	 * Processes payment for Purchase Order orders.
	 *
	 * @param int $order_id order id.
	 * @return array result.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		return $this->prepare_payment_result( $order );
	}
}
