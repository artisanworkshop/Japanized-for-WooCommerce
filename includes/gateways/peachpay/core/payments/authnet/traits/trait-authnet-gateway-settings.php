<?php
/**
 * PeachPay Authnet gateway utility trait.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


trait PeachPay_Authnet_Gateway_Settings {
	/**
	 * Gateway Authorize.net charge type setting.
	 *
	 * @param array $form_fields The current fields.
	 */
	protected function transaction_type_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'authnet_transaction_type' => array(
					'type'        => 'select',
					'title'       => __( 'Charge Type', 'peachpay-for-woocommerce' ),
					'description' => __( 'This option determines if the customers funds are captured immediately or only authorized for capture at a later time. Authorized payments expire and cannot be captured after 30 days.', 'peachpay-for-woocommerce' ),
					'default'     => 'authCaptureTransaction',
					'options'     => array(
						'authCaptureTransaction' => __( 'Capture', 'peachpay-for-woocommerce' ),
						'authOnlyTransaction'    => __( 'Authorize', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}
}
