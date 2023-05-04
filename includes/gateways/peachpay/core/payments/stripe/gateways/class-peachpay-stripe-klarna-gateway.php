<?php
/**
 * PeachPay Stripe Klarna gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
/**
 * .
 */
class PeachPay_Stripe_Klarna_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_klarna';
		$this->stripe_payment_method_type            = 'klarna';
		$this->stripe_payment_method_capability_type = 'klarna';
		$this->icon                                  = PeachPay::get_asset_url( 'img/marks/klarna.svg' );
		$this->settings_priority                     = 4;

		// Customer facing title and description.
		$this->title = 'Klarna';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );

		if ( PeachPay_Stripe_Integration::connect_country() === 'US' ) {
			$this->currencies = array( 'USD' );
			$this->countries  = array( 'US' );
		} elseif ( PeachPay_Stripe_Integration::connect_country() !== '' ) {
			$this->currencies = array( 'EUR', 'GBP', 'DKK', 'SEK', 'NOK' );
			$this->countries  = array( 'AT', 'BE', 'DK', 'FI', 'FR', 'DE', 'IE', 'IT', 'NL', 'NO', 'ES', 'SE', 'GB' );
		}

		$this->payment_method_family = __( 'Buy Now, Pay Later', 'peachpay-for-woocommerce' );

		$this->form_fields = self::capture_method_setting( $this->form_fields );

		parent::__construct();
	}

	/**
	 * Setup future settings for payment intent.
	 */
	protected function setup_future_usage() {
		return null;
	}
}
