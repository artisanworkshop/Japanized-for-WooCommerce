<?php
/**
 * PeachPay Authorize.net Credit/Debit card gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Authorize.net Credit/Debit card gateway.
 */
class PeachPay_Authnet_Card_Gateway extends PeachPay_Authnet_Payment_Gateway {

	/**
	 * Authorize.net credit/debit card gateway constructor.
	 */
	public function __construct() {
		$this->id                = 'peachpay_authnet_card';
		$this->icons             = array(
			'full'  => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/cc-quad.svg' ),
			),
			'small' => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/card-small.svg' ),
			),
		);
		$this->settings_priority = 0;

		// Customer facing title and description.
		$this->title = 'Card';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s a prompt will appear to complete your payment.', 'peachpay-for-woocommerce' );

		$this->payment_method_family = __( 'Card', 'peachpay-for-woocommerce' );

		$this->form_fields = self::transaction_type_setting( $this->form_fields );

		$this->supports[] = 'refunds';

		parent::__construct();
	}
}
