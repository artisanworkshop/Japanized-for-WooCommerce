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
