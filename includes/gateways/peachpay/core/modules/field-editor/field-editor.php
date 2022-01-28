<?php
/**
 * Handles all the events that happens in the field editor feature.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

peachpay_setup_field_editor();

/**
 * Sets up the field editor.
 */
function peachpay_setup_field_editor() {

	if ( ! peachpay_get_settings_option( 'peachpay_field_editor', 'enable_field_editor' ) ) {
		return;
	}

	add_filter( 'peachpay_register_feature', 'peachpay_filter_register_field_editor_support', 10, 1 );
	add_filter( 'peachpay_script_data', 'peachpay_filter_field_editor_script_data', 10, 1 );

	// Adding new fields for the checkout page.
	add_action( 'woocommerce_before_order_notes', 'peachpay_additional_fields' );
	// save fields to order meta.
	add_action( 'woocommerce_checkout_update_order_meta', 'save_what_we_added' );
	// Making fields required with notices and custome validator.
	add_action( 'woocommerce_checkout_process', 'check_if_required' );

}

/**
 * Registers field editor support.
 *
 * @param array $base_features The existing registered features.
 */
function peachpay_filter_register_field_editor_support( $base_features ) {

	$base_features['additional_fields'] = array(
		'enabled' => peachpay_get_settings_option( 'peachpay_field_editor', 'enable_field_editor' ),
		'version' => 1,
	);

	return $base_features;
}

/**
 * Registers field editor meta data.
 *
 * @param array $script_data The existing php script data.
 */
function peachpay_filter_field_editor_script_data( $script_data ) {

	$script_data['additional_fields']       = peachpay_enabled_additional_field_list();
	$script_data['additional_fields_order'] = peachpay_enabled_additional_field_list_order();

	return $script_data;
}

/**
 * Render all additional fields to the checkout page.
 *
 * @param object $checkout The checkout form data that will be used to render new fields.
 */
function peachpay_additional_fields( $checkout ) {
	$field_option = get_option( 'peachpay_field_editor' );

	if ( ! isset( $field_option['order'] ) || empty( $field_option['order'] ) ) {
		return;
	}
	foreach ( $field_option['order'] as $order_number ) {
		if ( ! isset( $field_option['field'][ $order_number ]['field_enable'] ) || '' === $field_option['field'][ $order_number ]['field_enable'] ) {
			continue;
		}
		if ( 'text' === $field_option['field'][ $order_number ]['type_list'] ) {
			woocommerce_form_field(
				$field_option['field'][ $order_number ]['field_name'],
				array(
					'type'     => $field_option['field'][ $order_number ]['type_list'],
					'required' => isset( $field_option['field'][ $order_number ]['field_required'] ) && 'yes' === $field_option['field'][ $order_number ]['field_required'],
					'label'    => $field_option['field'][ $order_number ]['field_label'],
					'default'  => $field_option['field'][ $order_number ]['field_default'],
				),
				$checkout->get_value( $field_option['field'][ $order_number ]['field_name'] )
			);
		} else {
			woocommerce_form_field(
				$field_option['field'][ $order_number ]['field_name'],
				array(
					'type'     => $field_option['field'][ $order_number ]['type_list'],
					'required' => isset( $field_option['field'][ $order_number ]['field_required'] ),
					'label'    => $field_option['field'][ $order_number ]['field_label'],
				),
				$checkout->get_value( $field_option['field'][ $order_number ]['field_name'] )
			);
		}
	}

}

/**
 * Update the metadata when a new field input is added.
 *
 * @param object $order_id takes in the order id.
 */
function save_what_we_added( $order_id ) {
	$field_option = get_option( 'peachpay_field_editor' );
	if ( ! isset( $field_option['enable_field_editor'] ) ) {
		return;
	}
	if ( ! isset( $field_option['order'] ) || empty( $field_option['order'] ) ) {
		return;
	}
	foreach ( $field_option['order'] as $order_number ) {
		if ( ! isset( $field_option['field'][ $order_number ]['field_enable'] ) || 'yes' !== $field_option['field'][ $order_number ]['field_enable'] ) {
			continue;
		}
		// phpcs:disable
		if ( ! empty( $_POST[ $field_option['field'][ $order_number ]['field_name'] ] ) ) {
			update_post_meta(
				$order_id,
				( isset( $field_option['field'][ $order_number ]['field_label'] ) && '' !== $field_option['field'][ $order_number ]['field_label'] ) ?
				$field_option['field'][ $order_number ]['field_label'] : $field_option['field'][ $order_number ]['field_name'],
				$_POST[ $field_option['field'][ $order_number ]['field_name'] ]
			);
		}
		// phpcs:enable
	}
}

/**
 * A custom method to test if the field in the native checkout is require must be filled in else it post a error message banner.
 */
function check_if_required() {
	$field_option = get_option( 'peachpay_field_editor' );
	if ( ! isset( $field_option['enable_field_editor'] ) ) {
		return;
	}
	if ( ! isset( $field_option['order'] ) || empty( $field_option['order'] ) ) {
		return;
	}
	foreach ( $field_option['order'] as $order_number ) {
		//phpcs:disable
		if ( isset( $field_option['field'][ $order_number ]['field_enable'] ) && 'yes' === $field_option['field'][ $order_number ]['field_enable']
		&& isset( $field_option['field'][ $order_number ]['field_required'] ) && 'yes' === $field_option['field'][ $order_number ]['field_required'] ) {
			if ( empty( $_POST[ $field_option['field'][ $order_number ]['field_name'] ] ) ) {
				wc_add_notice( 'Please fill in all required fields', 'error' );
			}
		}
		//phpcs:enable
	}
}

/**
 * Returns a list of all the enabled field data for rendering in the modal.
 */
function peachpay_enabled_additional_field_list() {
	$field_option = get_option( 'peachpay_field_editor' );
	if ( ! isset( $field_option['enable_field_editor'] ) ) {
		return;
	}
	if ( ! isset( $field_option['order'] ) ) {
		return;
	}
	$result = array();
	foreach ( $field_option['order'] as $order_number ) {
		if ( isset( $field_option['field'][ $order_number ]['field_enable'] ) && 'yes' === $field_option['field'][ $order_number ]['field_enable'] ) {
			$result[ $order_number ] = $field_option['field'][ $order_number ];
		}
	}
	return $result;
}

/**
 * Returns a list of all the enabled field order arrangements for rendering in the modal.
 */
function peachpay_enabled_additional_field_list_order() {
	$field_option = get_option( 'peachpay_field_editor' );
	if ( ! isset( $field_option['enable_field_editor'] ) ) {
		return;
	}
	$result = array();
	if ( ! isset( $field_option['order'] ) ) {
		return;
	}
	foreach ( $field_option['order'] as $order_number ) {
		if ( isset( $field_option['field'][ $order_number ]['field_enable'] ) && 'yes' === $field_option['field'][ $order_number ]['field_enable'] ) {
			$result[] = $order_number;
		}
	}
	return $result;
}
