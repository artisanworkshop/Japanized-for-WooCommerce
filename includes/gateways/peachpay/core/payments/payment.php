<?php
/**
 * Peachpay Payments setup.
 *
 * @deprecated This file will be removed in favor of initializing gateways in class-peachpay.php
 * @package PeachPay
 */

/**
 * Adds the peachpay gateway class to wc.
 *
 * @param array $gateways The gateway array.
 * @return array
 */

require_once PEACHPAY_ABSPATH . 'core/payments/amazonpay/amazonpay.php';

/**
 * This function is called via the add_action below it to initialize the
 * PeachPay_WC_Gateway class.
 *
 * @deprecated Remove once all payment methods use WC API.
 */
function peachpay_init_payments() {
	require_once PEACHPAY_ABSPATH . 'core/payments/class-peachpay-abstract-wc-gateway.php';

	$registered_gateways = apply_filters( 'peachpay_register_supported_gateways', array() );

	// Registers gateways with WC.
	add_filter(
		'woocommerce_payment_gateways',
		function ( $gateways ) use ( $registered_gateways ) {
			foreach ( $registered_gateways as $value ) {
				$gateways[] = $value['gateway_class'];
			}
			return $gateways;
		}
	);

	add_filter(
		'peachpay_register_feature',
		function ( $feature_list ) use ( $registered_gateways ) {

			foreach ( $registered_gateways as $gateway_config ) {
				if ( ! array_key_exists( 'features', $gateway_config ) ) {
					continue;
				}

				$feature_list = array_merge( $feature_list, $gateway_config['features'] );
			}

			return $feature_list;
		}
	);
}
add_action( 'plugins_loaded', 'peachpay_init_payments', 11 );

/**
 * Filters out the available gateways for on the checkout page.
 *
 * @deprecated Remove once all gateways support the native checkout.
 * @param array $available_gateways The available gateways to filter.
 */
function hide_peachpay_gateways( $available_gateways ) {
	if ( defined( 'WOOCOMMERCE_CHECKOUT' ) && ! defined( 'PEACHPAY_CHECKOUT' ) ) {
		$supported_gateways_ids = peachpay_supported_gateways();

		foreach ( $available_gateways as $key => $gateway ) {
			if ( in_array( $gateway->id, $supported_gateways_ids, true ) && ! peachpay_starts_with( $gateway->id, 'peachpay_square_' ) && ! peachpay_starts_with( $gateway->id, 'peachpay_stripe_' ) && ! peachpay_starts_with( $gateway->id, 'peachpay_purchase_' ) && ! peachpay_starts_with( $gateway->id, 'peachpay_paypal_' ) && ! peachpay_starts_with( $gateway->id, 'peachpay_poynt_' ) && ! peachpay_starts_with( $gateway->id, 'peachpay_authnet_' ) ) {
				unset( $available_gateways[ $key ] );
			}
		}
	}

	return $available_gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'hide_peachpay_gateways', 10, 2 );

/**
 * Gets a list of PeachPay gateways.
 *
 * @param array $gateways A list of gateways.
 */
function peachpay_supported_gateway_list( $gateways ) {

	foreach ( WC()->payment_gateways->payment_gateways() as $gateway ) {
		if ( peachpay_starts_with( $gateway->id, 'peachpay_' ) ) {
			$gateways[] = $gateway->id;
		}
	}

	return $gateways;
}
add_filter( 'peachpay_supported_gateway_list', 'peachpay_supported_gateway_list' );
