<?php
/**
 * Venmo WC gateway.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * This class allows us to submit orders with the PeachPay PayPal Venmo payment method.
 */
class PeachPay_PayPal_Venmo_Gateway extends PeachPay_PayPal_Payment_Gateway {
	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->id                = 'peachpay_paypal_venmo';
		$this->icon              = PeachPay::get_asset_url( 'img/marks/venmo.svg' );
		$this->settings_priority = 3;

		$this->title              = 'Venmo';
		$this->description        = '';
		$this->method_title       = 'Venmo via PayPal (PeachPay)';
		$this->method_description = 'Accept Venmo payments through PayPal';

		$this->currencies            = array( 'USD' );
		$this->countries             = array( 'US' );
		$this->max_amount            = 10000;
		$this->min_max_currency      = 'USD';
		$this->payment_method_family = __( 'Digital Wallet', 'peachpay-for-woocommerce' );

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
	 * Gateway cpaypal button color settings.
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
					'default' => 'blue',
					'options' => array(
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
