<?php
/**
 * PeachPay Stripe Affirm gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
class PeachPay_Stripe_Affirm_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_affirm';
		$this->stripe_payment_method_type            = 'affirm';
		$this->stripe_payment_method_capability_type = 'affirm';
		$this->icon                                  = PeachPay::get_asset_url( 'img/marks/affirm.svg' );
		$this->settings_priority                     = 3;

		// Customer facing title and description.
		$this->title = 'Affirm';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );

		$this->currencies            = array( 'USD' );
		$this->countries             = array( 'US' );
		$this->payment_method_family = __( 'Buy Now, Pay Later', 'peachpay-for-woocommerce' );
		$this->min_amount            = 50;
		$this->max_amount            = 30000;
		$this->form_fields           = self::capture_method_setting( $this->form_fields );

		parent::__construct();
	}

	/**
	 * Setup future settings for payment intent.
	 */
	protected function setup_future_usage() {
		return null;
	}
}
