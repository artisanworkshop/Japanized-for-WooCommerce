<?php
/**
 * PeachPay gateway utilities
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Gets whether a PeachPay gateway is enabled.
 */
function peachpay_gateway_available() {
	$gateways          = WC()->payment_gateways->get_available_payment_gateways();
	$peachpay_gateways = peachpay_supported_gateways();

	foreach ( $peachpay_gateways as $gateway ) {
		if ( isset( $gateways[ $gateway ] ) && 'yes' === $gateways[ $gateway ]->enabled ) {
			return true;
		}
	}

	return false;
}

/**
 * Gets a list of supported gateways for PeachPay.
 *
 * @param array $extras An optional list of gateways to list as supported in the final result. (For gateways not made by PeachPay).
 */
function peachpay_supported_gateways( $extras = array() ) {
	return apply_filters( 'peachpay_supported_gateway_list', $extras );
}
