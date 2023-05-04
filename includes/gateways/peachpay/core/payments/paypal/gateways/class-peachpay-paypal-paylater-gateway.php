<?php
/**
 * PayPal Pay Later WC gateway.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * This class allows us to submit orders with the PeachPay PayPal Pay Later payment method.
 */
class PeachPay_PayPal_PayLater_Gateway extends PeachPay_PayPal_Payment_Gateway {
	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->id                = 'peachpay_paypal_paylater';
		$this->icon              = PeachPay::get_asset_url( 'img/marks/paypal-paylater.svg' );
		$this->settings_priority = 2;

		$this->title              = 'PayPal Pay Later';
		$this->description        = '';
		$this->method_title       = 'PayPal Pay Later (PeachPay)';
		$this->method_description = 'Accept Pay Later payments through PayPal';

		$this->countries             = array( 'AU', 'FR', 'DE', 'IT', 'ES', 'GB', 'US' );
		$this->min_amount            = 99;
		$this->min_max_currency      = 'USD';
		$this->payment_method_family = __( 'Loan', 'peachpay-for-woocommerce' );

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

	/**
	 * Gateway PayPal Pay Later button color settings.
	 *
	 * @param array $form_fields The current fields.
	 */
	protected function paypal_button_color_settings( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'paypal_button_color' => array(
					'type'    => 'select',
					'title'   => __( 'Button Color', 'peachpay-for-woocommerce' ),
					'default' => 'gold',
					'options' => array(
						'gold'   => __( 'Gold', 'peachpay-for-woocommerce' ),
						'blue'   => __( 'Blue', 'peachpay-for-woocommerce' ),
						'silver' => __( 'Silver', 'peachpay-for-woocommerce' ),
						'black'  => __( 'Black', 'peachpay-for-woocommerce' ),
						'white'  => __( 'White', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}
}
