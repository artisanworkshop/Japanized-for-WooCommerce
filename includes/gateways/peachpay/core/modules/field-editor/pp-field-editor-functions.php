<?php
/**
 * Handles all the events that happens in the field editor feature.
 *
 * @package PeachPay
 */

/**
 * Generates the preset fields for virtual products.
 *
 * @param object $fields The list of existing billing fields.
 */
function peachpay_virtual_product_fields_preset( $fields ) {
	if ( is_null( WC()->cart ) ) {
		return $fields;
	}
	if ( ! WC()->cart->needs_shipping_address() && peachpay_get_settings_option( 'peachpay_express_checkout_window', 'enable_virtual_product_fields' ) ) {
		unset( $fields['billing']['billing_company'] );
		unset( $fields['billing']['billing_phone'] );
		unset( $fields['billing']['billing_address_1'] );
		unset( $fields['billing']['billing_address_2'] );
		unset( $fields['billing']['billing_city'] );
		unset( $fields['billing']['billing_postcode'] );
		unset( $fields['billing']['billing_country'] );
		unset( $fields['billing']['billing_state'] );
	}
	return $fields;
}

/**
 * A helper method that check if the field is a default field or not.
 * return true if it is default and false if it is not.
 *
 * @param string $section the target section string.
 * @param string $target the string to check.
 */
function peachpay_is_default_field( $section, $target ) {
	if ( 'additional' === $section ) {
		return false;
	}

	$default_field_name_keys = array(
		$section . '_email',
		$section . '_phone',
		$section . '_first_name',
		$section . '_last_name',
		$section . '_company',
		$section . '_address_1',
		$section . '_address_2',
		$section . '_postcode',
		$section . '_city',
		$section . '_state',
		$section . '_country',
		$section . '_personal_header',
		$section . '_address_header',
	);
	return in_array( $target, $default_field_name_keys, true );
}
