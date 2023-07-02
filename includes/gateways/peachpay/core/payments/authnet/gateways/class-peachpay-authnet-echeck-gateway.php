<?php
/**
 * PeachPay Authorize.net Echeck gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Authorize.net ECheck gateway.
 */
class PeachPay_Authnet_ECheck_Gateway extends PeachPay_Authnet_Payment_Gateway {

	/**
	 * Authorize.net ECheck gateway constructor.
	 */
	public function __construct() {
		$this->id                = 'peachpay_authnet_echeck';
		$this->icons             = array(
			'full'  => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/us_banks-full.svg' ),
			),
			'small' => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/us_banks-small.svg' ),
			),
		);
		$this->settings_priority = 1;

		// Customer facing title and description.
		$this->title = 'US Bank Account';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s you will be prompted to complete your payment.', 'peachpay-for-woocommerce' );

		$this->payment_method_family = __( 'Bank debit', 'peachpay-for-woocommerce' );

		$this->form_fields = self::transaction_type_setting( $this->form_fields );

		parent::__construct();
	}
}
