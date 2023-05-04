<?php
/**
 * PayPal WC gateway.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * This class allows us to submit orders with the PeachPay PayPal gateway.
 */
class PeachPay_PayPal_Wallet_Gateway extends PeachPay_PayPal_Payment_Gateway {
	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->id                = 'peachpay_paypal_wallet';
		$this->icon              = PeachPay::get_asset_url( 'img/marks/paypal.svg' );
		$this->settings_priority = 1;

		$this->title              = 'PayPal';
		$this->description        = '';
		$this->method_title       = 'PayPal (PeachPay)';
		$this->method_description = 'Accept PayPal payments';

		$this->payment_method_family = __( 'Digital Wallet', 'peachpay-for-woocommerce' );

		$global_fields = array();
		$global_fields = $this->paypal_button_header_settings( $global_fields );
		$global_fields = $this->paypal_button_color_settings( $global_fields );
		$global_fields = $this->paypal_button_shape_settings( $global_fields );
		$global_fields = $this->paypal_button_label_settings( $global_fields );
		$global_fields = $this->paypal_button_height_settings( $global_fields );

		$this->form_fields = array_merge(
			$global_fields,
			$this->form_fields
		);

		parent::__construct();
	}
}
