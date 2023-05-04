<?php
/**
 * PeachPay PayPal gateway settings trait.`
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

trait PeachPay_PayPal_Gateway_Settings {

	/**
	 * Gateway paypal button color settings.
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

	/**
	 * Gateway paypal button shape settings.
	 *
	 * @param array $form_fields The current fields.
	 */
	protected function paypal_button_shape_settings( $form_fields ) {

		return array_merge(
			$form_fields,
			array(
				'paypal_button_shape' => array(
					'type'    => 'select',
					'title'   => __( 'Button Shape', 'peachpay-for-woocommerce' ),
					'default' => 'pill',
					'options' => array(
						'pill' => __( 'Pill', 'peachpay-for-woocommerce' ),
						'rect' => __( 'Rectangle', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}

	/**
	 * Gateway paypal button label settings.
	 *
	 * @param array $form_fields The current fields.
	 */
	protected function paypal_button_label_settings( $form_fields ) {

		return array_merge(
			$form_fields,
			array(
				'paypal_button_label' => array(
					'type'    => 'select',
					'title'   => __( 'Button Label', 'peachpay-for-woocommerce' ),
					'default' => 'paypal',
					'options' => array(
						'paypal'   => __( 'Standard', 'peachpay-for-woocommerce' ),
						'checkout' => __( 'Checkout', 'peachpay-for-woocommerce' ),
						'buynow'   => __( 'Buy Now', 'peachpay-for-woocommerce' ),
						'pay'      => __( 'Pay', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}

	/**
	 * Gateway paypal button height settings.
	 *
	 * @param array $form_fields The current fields.
	 */
	protected function paypal_button_height_settings( $form_fields ) {

		return array_merge(
			$form_fields,
			array(
				'paypal_button_height' => array(
					'type'              => 'number',
					'title'             => __( 'Button Height', 'peachpay-for-woocommerce' ),
					'default'           => 40,
					'custom_attributes' => array(
						'min' => 25,
						'max' => 55,
					),
				),
			)
		);
	}

	/**
	 * Gateway paypal button header settings.
	 *
	 * @param array $form_fields The current fields.
	 */
	protected function paypal_button_header_settings( $form_fields ) {

		return array_merge(
			$form_fields,
			array(
				'paypal_button_header' => array(
					'type'  => 'title',
					'title' => __( 'Button settings', 'peachpay-for-woocommerce' ),
				),
			)
		);
	}
}
