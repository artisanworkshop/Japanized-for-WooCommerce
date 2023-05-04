<?php
/**
 * PeachPay PayPal gateway settings trait.`
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

trait PeachPay_Poynt_Gateway_Settings {

	/**
	 * Adds the capture method setting to the gateway settings.
	 *
	 * @param array $form_fields The existing gateway settings.
	 */
	public static function transaction_action_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'transaction_action' => array(
					'title'       => __( 'Charge type', 'peachpay-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'This option determines if the customers funds are captured immediately or only authorized for capture at a later time.', 'peachpay-for-woocommerce' ),
					'default'     => 'automatic',
					'options'     => array(
						'SALE'      => __( 'Capture', 'peachpay-for-woocommerce' ),
						'AUTHORIZE' => __( 'Authorize', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}

}
