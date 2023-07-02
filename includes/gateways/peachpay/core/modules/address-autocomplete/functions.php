<?php
/**
 * PeachPay address autocomplete functions.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Adds address autocomplete status to feature flag set.
 *
 * @param array $data PeachPay data array.
 */
function peachpay_address_autocomplete_feature_flag( $data ) {
	$data['address_autocomplete'] = array(
		'enabled'  => PeachPay_Address_Autocomplete_Settings::get_setting( 'enabled' ) === 'yes',
		'version'  => 1,
		'metadata' => array(
			'active_locations' => PeachPay_Address_Autocomplete_Settings::get_setting( 'active_locations' ),
		),
	);

	return $data;
}
