<?php
/**
 * PayPal Credit WC gateway.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * This class allows us to submit orders with the PeachPay PayPal Credit payment method.
 */
class PeachPay_PayPal_Credit_Gateway extends PeachPay_PayPal_Payment_Gateway {
	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->id                = 'peachpay_paypal_credit';
		$this->icon              = PeachPay::get_asset_url( 'img/marks/paypal.svg' );
		$this->settings_priority = 2;

		$this->title              = 'PayPal Credit';
		$this->description        = '';
		$this->method_title       = 'PayPal Credit (PeachPay)';
		$this->method_description = 'Accept PayPal Credit payments';

		$this->countries             = array( 'US', 'GB' );
		$this->min_amount            = 99;
		$this->min_max_currency      = 'USD';
		$this->payment_method_family = __( 'Revolving line of credit similar to a credit card', 'peachpay-for-woocommerce' );

		$global_fields = array();
		$global_fields = $this->paypal_button_header_settings( $global_fields );
		$global_fields = $this->paypal_button_color_settings( $global_fields );
		$global_fields = $this->paypal_button_shape_settings( $global_fields );
		$global_fields = $this->paypal_button_label_settings( $global_fields );
		$global_fields = $this->paypal_button_height_settings( $global_fields );

		$this->form_fields = array_merge(
			$this->form_fields,
			$global_fields
		);

		parent::__construct();
	}
}
