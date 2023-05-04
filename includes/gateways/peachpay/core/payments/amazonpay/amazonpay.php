<?php
/**
 * PeachPay paypal payment method.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/util.php';

/**
 * Sets up the PeachPay paypal payment methods/gateway.
 *
 * @param array $supported_gateways An array of supported gateways and there configuration.
 */
function peachpay_action_register_amazonpay_gateway( $supported_gateways ) {

	require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/class-peachpay-amazonpay-gateway.php';
	require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/routes/rest-api.php';
	require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/routes/order-payment-status-hooks.php';
	require_once PEACHPAY_ABSPATH . 'core/payments/payment-threshold.php';
	require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/functions.php';
	require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/hooks.php';

	if ( is_admin() ) {
		require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/admin/settings.php';
	}

	$account = peachpay_amazonpay_account_connected();
	if ( ! $account ) {
		$account = array(
			'merchant_id'   => '',
			'store_id'      => '',
			'public_key_id' => '',
		);
	}

	$supported_gateways[] = array(
		'gateway_id'    => 'peachpay_amazonpay',
		'gateway_class' => 'PeachPay_AmazonPay_Gateway',
		'features'      => array(
			'amazonpay_payment_method' => array(
				'enabled'  => peachpay_amazonpay_enabled(),
				'version'  => 1,
				'metadata' => array(
					'merchant_id'          => $account['merchant_id'],
					'store_id'             => $account['store_id'],
					'public_key_id'        => $account['public_key_id'],
					'supported_currencies' => array( peachpay_amazonpay_currency() ),
					'supported_countries'  => peachpay_amazonpay_supported_countries(),
					'limits'               => peachpay_get_transaction_thresholds( 'amazonpay' ),
				),
			),
		),
	);

	return $supported_gateways;
}
add_filter( 'peachpay_register_supported_gateways', 'peachpay_action_register_amazonpay_gateway', 10, 1 );
