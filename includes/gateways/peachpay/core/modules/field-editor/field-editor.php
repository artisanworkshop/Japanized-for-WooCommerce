<?php
/**
 * Handles all the events that happens in the field editor feature.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/modules/field-editor/admin/settings-field-editor.php';
require_once PEACHPAY_ABSPATH . 'core/modules/field-editor/pp-field-editor-functions.php';

peachpay_setup_field_editor();

/**
 * Sets up the field editor.
 */
function peachpay_setup_field_editor() {

	peachpay_initialize_fields( 'shipping' );
	peachpay_initialize_fields( 'billing' );

	add_filter( 'woocommerce_checkout_fields', 'peachpay_virtual_product_fields_preset', 9999 );
	add_filter( 'peachpay_register_feature', 'peachpay_filter_register_field_editor_support', 10, 1 );
	add_filter( 'peachpay_script_data', 'peachpay_filter_field_editor_script_data', 10, 1 );

	// Adding the billing fields according to the data.
	add_filter( 'woocommerce_checkout_fields', 'peachpay_billing_fields', 10, 1 );
	// Adding the shipping fields according to the data.

	// Need to remove this for future use.
	add_filter( 'woocommerce_checkout_fields', 'peachpay_shipping_fields', 10, 1 );

	// Adding new fields for the checkout page.
	add_action( 'woocommerce_before_order_notes', 'peachpay_additional_fields' );
	// save fields to order meta.
	add_action( 'woocommerce_checkout_update_order_meta', 'peachpay_save_new_fields_data' );
	// Making fields required with notices and custome validator.
	add_action( 'woocommerce_checkout_process', 'check_if_required' );
	// Render a table of custom fields in receipt page.
	add_action( 'woocommerce_order_details_after_order_table', 'peachpay_render_additional_fields_receipt' );
}

/**
 * Sets up all the fields automatically without opening the settings page.
 *
 * @param string $section the target section either billing or shipping.
 */
function peachpay_initialize_fields( $section ) {
	if ( empty( get_option( 'peachpay_field_editor_' . $section ) ) ) {
		peachpay_reset_region_presets_default_fields( $section );
	}
}

/**
 * Registers field editor support.
 *
 * @param array $base_features The existing registered features.
 */
function peachpay_filter_register_field_editor_support( $base_features ) {

	$base_features['enable_virtual_product_fields'] = array(
		'enabled' => peachpay_get_settings_option( 'peachpay_express_checkout_window', 'enable_virtual_product_fields' ),
		'version' => 1,
	);

	$base_features['enable_field_editor'] = array(
		'enabled' => true,
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

	$script_data['additional_fields']       = peachpay_enabled_field_list( 'additional' );
	$script_data['additional_fields_order'] = peachpay_enabled_field_list_order( 'additional' );
	$script_data['billing_fields']          = peachpay_enabled_field_list( 'billing' );
	$script_data['billing_fields_order']    = peachpay_enabled_field_list_order( 'billing' );
	$script_data['shipping_fields']         = peachpay_enabled_field_list( 'shipping' );
	$script_data['shipping_fields_order']   = peachpay_enabled_field_list_order( 'shipping' );

	return $script_data;
}

/**
 * Init the billing fields.
 *
 * @param array $fields the billing fields that are in the checkout form that is to be adjusted accordingly.
 */
function peachpay_billing_fields( $fields ) {
	return field_adjustments( $fields, 'billing' );
}

/**
 * Init the shipping fields.
 *
 * @param array $fields the shipping fields that are in the checkout form that is to be adjusted accordingly.
 */
function peachpay_shipping_fields( $fields ) {
	return field_adjustments( $fields, 'shipping' );
}

/**
 * Checks the fields and make sure they are following the field data table accordingly and rendering it.
 *
 * @param array $fields the shipping fields that are in the checkout form that is to be adjusted accordingly.
 * @param array $section the target field section that is to be adjust.
 */
function field_adjustments( $fields, $section ) {
	$field_option = get_option( 'peachpay_field_editor_' . $section );
	$priority     = 10;

	$field_name_keys = array();
	foreach ( $field_option[ $section . '_order' ] as $order ) {
		if ( 'header' === $field_option[ $section ][ $order ]['type_list'] ) {
			continue;
		}
		if ( ! isset( $field_option[ $section ][ $order ]['field_enable'] ) || 'yes' !== $field_option[ $section ][ $order ]['field_enable'] ) {
			continue;
		}
		array_push( $field_name_keys, $field_option[ $section ][ $order ]['field_name'] );
	}

	foreach ( $fields[ $section ] as $key => $field ) {
		if ( ! in_array( $key, $field_name_keys, true ) ) {
			unset( $fields[ $section ][ $key ] );
		}
	}

	foreach ( $field_option[ $section ] as $key => $value ) {
		if ( 'header' === $value['type_list'] ) {
			continue;
		}
		if ( ! isset( $value['field_enable'] ) || 'yes' !== $value['field_enable'] ) {
			continue;
		}
		if ( is_array( $fields[ $section ] ) && array_key_exists( $value['field_name'], $fields[ $section ] ) ) {
			$fields[ $section ][ $value['field_name'] ]['priority'] = $priority;
			if ( ! isset( $value['field_required'] ) || '' === $value['field_required'] ) {
				$fields[ $section ][ $value['field_name'] ]['required'] = '';
			}
			$priority += 10;
		} else {
			$fields[ $section ][ $value['field_name'] ] = array(
				'type'        => $value['type_list'],
				'label'       => $value['field_label'],
				'label_class' => 'peachpay-radio-label',
				'required'    => isset( $value['field_required'] ) && 'yes' === $value['field_required'] ? 1 : '',
				'priority'    => $priority,
				'options'     => isset( $value['option'] ) && ! empty( $value['option'] ) ? $value['option'] : '',
				'default'     => $value['field_default'],
			);
			$priority                                  += 10;
		}
	}
	return $fields;
}

/**
 * Render all additional fields to the checkout page.
 *
 * @param object $checkout The checkout form data that will be used to render new fields.
 */
function peachpay_additional_fields( $checkout ) {
	$field_option = get_option( 'peachpay_field_editor_additional' );

	if ( ! isset( $field_option['additional_order'] ) || empty( $field_option['additional_order'] ) ) {
		return;
	}
	foreach ( $field_option['additional_order'] as $order_number ) {
		if ( ! isset( $field_option['additional'][ $order_number ]['field_enable'] ) || '' === $field_option['additional'][ $order_number ]['field_enable'] ) {
			continue;
		}
		if ( 'text' === $field_option['additional'][ $order_number ]['type_list'] ) {
			woocommerce_form_field(
				$field_option['additional'][ $order_number ]['field_name'],
				array(
					'type'     => $field_option['additional'][ $order_number ]['type_list'],
					'required' => isset( $field_option['additional'][ $order_number ]['field_required'] ) && 'yes' === $field_option['additional'][ $order_number ]['field_required'],
					'label'    => $field_option['additional'][ $order_number ]['field_label'],
					'default'  => $field_option['additional'][ $order_number ]['field_default'],
				),
				$checkout->get_value( $field_option['additional'][ $order_number ]['field_name'] )
			);
		} elseif ( 'select' === $field_option['additional'][ $order_number ]['type_list'] || 'radio' === $field_option['additional'][ $order_number ]['type_list'] ) {
			$input_type = $field_option['additional'][ $order_number ]['type_list'];
			woocommerce_form_field(
				$field_option['additional'][ $order_number ]['field_name'],
				array(
					'type'        => $field_option['additional'][ $order_number ]['type_list'],
					'required'    => isset( $field_option['additional'][ $order_number ]['field_required'] ) && 'yes' === $field_option['additional'][ $order_number ]['field_required'],
					'label'       => $field_option['additional'][ $order_number ]['field_label'],
					'label_class' => 'peachpay-radio-label',
					'default'     => $field_option['additional'][ $order_number ]['field_default'],
					'options'     => 'select' === $input_type ? peachpay_set_options_list( $field_option['additional'][ $order_number ]['option'] ) : $field_option['additional'][ $order_number ]['option'],
				),
				$checkout->get_value( $field_option['additional'][ $order_number ]['field_name'] )
			);
		} else {
			woocommerce_form_field(
				$field_option['additional'][ $order_number ]['field_name'],
				array(
					'type'     => $field_option['additional'][ $order_number ]['type_list'],
					'required' => isset( $field_option['additional'][ $order_number ]['field_required'] ) && 'yes' === $field_option['additional'][ $order_number ]['field_required'],
					'label'    => $field_option['additional'][ $order_number ]['field_label'],
					'default'  => $field_option['additional'][ $order_number ]['field_default'],
				),
				$checkout->get_value( $field_option['additional'][ $order_number ]['field_name'] )
			);
		}
	}
}

/**
 * Prepares the options array with a default value.
 *
 * @param array  $options the option array from the php data.
 * @param string $default_option the default value for the select box.
 */
function peachpay_set_options_list( $options, $default_option = 'Please select' ) {
	return array_replace( array( '' => $default_option ), $options );
}

/**
 * Update the metadata to all three section of the form section.
 *
 * @param object $order_id takes in the order id.
 */
function peachpay_save_new_fields_data( $order_id ) {
	save_what_we_added( $order_id, 'billing' );
	save_what_we_added( $order_id, 'shipping' );
	save_what_we_added( $order_id, 'additional' );

	save_klaviyo_options();
}

/**
 * Update the metadata when a new field input is added.
 *
 * @param object $order_id takes in the order id.
 * @param string $section the section that is targeted.
 */
function save_what_we_added( $order_id, $section ) {
	// phpcs:disable
	if ( 'shipping' === $section && ( empty( $_POST['ship_to_different_address'] ) || ! isset( $_POST['ship_to_different_address'] ) ) ) {
		return;
	}
	// phpcs:enable
	$field_option = get_option( 'peachpay_field_editor_' . $section );
	if ( ! isset( $field_option[ $section . '_order' ] ) || empty( $field_option[ $section . '_order' ] ) ) {
		return;
	}
	foreach ( $field_option[ $section . '_order' ] as $order_number ) {
		if ( peachpay_is_default_field( $section, $field_option[ $section ][ $order_number ]['field_name'] ) || 'header' === $field_option[ $section ][ $order_number ]['type_list'] ) {
			continue;
		}
		if ( ! isset( $field_option[ $section ][ $order_number ]['field_enable'] ) || 'yes' !== $field_option[ $section ][ $order_number ]['field_enable'] ) {
			continue;
		}
		// phpcs:disable
		if ( ! empty( $_POST[ $field_option[ $section ][ $order_number ]['field_name'] ] ) ) {
			add_post_meta(
				$order_id,
				$field_option[ $section ][ $order_number ]['field_name'],
				$_POST[ $field_option[ $section ][ $order_number ]['field_name'] ]
			);
		}
		// phpcs:enable
	}
}

/**
 * Klaviyo integration-- due to a different submission structure, we need to
 * submit these here rather than integrated into our form save.
 */
function save_klaviyo_options() {
	if ( ! is_plugin_active( 'klaviyo/klaviyo.php' ) ) {
		return;
	}
	$klaviyo_settings = get_option( 'klaviyo_settings' );
	// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	$email   = isset( $_POST['billing_email'] ) ? esc_html( wp_unslash( $_POST['billing_email'] ) ) : null;
	$phone   = isset( $_POST['billing_phone'] ) ? esc_html( wp_unslash( $_POST['billing_phone'] ) ) : null;
	$country = isset( $_POST['billing_country'] ) ? esc_html( wp_unslash( $_POST['billing_country'] ) ) : null;
	$url     = 'https://a.klaviyo.com/api/webhook/integration/woocommerce?c=' . $klaviyo_settings['klaviyo_public_api_key'];
	$body    = array(
		'data' => array(),
	);

	if ( isset( $_POST['kl_sms_consent_checkbox'] ) && $_POST['kl_sms_consent_checkbox'] ) {
		array_push(
			$body['data'],
			array(
				'customer'     => array(
					'email'   => $email,
					'country' => $country,
					'phone'   => $phone,
				),
				'consent'      => true,
				'updated_at'   => gmdate( DATE_ATOM, date_timestamp_get( date_create() ) ),
				'consent_type' => 'sms',
				'group_id'     => $klaviyo_settings['klaviyo_sms_list_id'],
			)
		);
	}

	if ( isset( $_POST['kl_newsletter_checkbox'] ) && esc_html( wp_unslash( $_POST['kl_newsletter_checkbox'] ) ) ) {
		array_push(
			$body['data'],
			array(
				'customer'     => array(
					'email' => $email,
					'phone' => $phone,
				),
				'consent'      => true,
				'updated_at'   => gmdate( DATE_ATOM, date_timestamp_get( date_create() ) ),
				'consent_type' => 'email',
				'group_id'     => $klaviyo_settings['klaviyo_newsletter_list_id'],
			)
		);
	}
	// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

	wp_remote_post(
		$url,
		array(
			'method'      => 'POST',
			'httpversion' => '1.0',
			'blocking'    => false,
			'headers'     => array(
				'X-WC-Webhook-Topic' => 'custom/consent',
				'Content-Type'       => 'application/json',
			),
			'body'        => wp_json_encode( $body ),
			'data_format' => 'body',
		)
	);
}

/**
 * A custom method to test if the field in the native checkout is require must be filled in else it post a error message banner.
 */
function peachpay_add_form_require_validation() {
	check_if_required( 'additional' );
}

/**
 * A custom method to test if the field in the native checkout is require must be filled in else it post a error message banner.
 *
 * @param string $section the section that is targeted.
 */
function check_if_required( $section ) {
	$field_option = get_option( 'peachpay_field_editor_' . $section );
	if ( ! isset( $field_option[ $section . '_order' ] ) || empty( $field_option[ $section . '_order' ] ) ) {
		return;
	}
	foreach ( $field_option[ $section . '_order' ] as $order_number ) {
		//phpcs:disable
		if ( isset( $field_option[ $section ][ $order_number ]['field_enable'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_enable']
		&& isset( $field_option[ $section ][ $order_number ]['field_required'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_required'] ) {
			if ( empty( $_POST[ $field_option[ $section ][ $order_number ]['field_name'] ] ) ) {
				wc_add_notice( 'Please fill in ' . $field_option[ $section ][ $order_number ]['field_label'], 'error' );
			}
		}
		//phpcs:enable
	}
}

/**
 * Returns a list of all the enabled field data for rendering in the modal.
 *
 * @param string $section the section that is targeted.
 * @param bool   $ignore_defaults when true, default values will not be included in result.
 */
function peachpay_enabled_field_list( $section, $ignore_defaults = false ) {
	$field_option      = get_option( 'peachpay_field_editor_' . $section );
	$result            = array();
	$next_order_number = 0;

	if ( isset( $field_option[ $section . '_order' ] ) ) {
		foreach ( $field_option[ $section . '_order' ] as $order_number ) {
			$field_name = $field_option[ $section ][ $order_number ]['field_name'];
			$add_field  = ! $ignore_defaults || ! peachpay_is_default_field( $section, $field_name );

			if ( $add_field && isset( $field_option[ $section ][ $order_number ]['field_enable'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_enable'] ) {
				$result[ $order_number ] = $field_option[ $section ][ $order_number ];
				if ( isset( $field_option[ $section ][ $order_number ]['option'] ) ) {
					$result[ $order_number ]['option_order'] = array();
					foreach ( $field_option[ $section ][ $order_number ]['option'] as $value => $name ) {
						$result[ $order_number ]['option_order'][] = array( $value, $name );
					}
				}
			}
			$next_order_number = $order_number;
		}
		$next_order_number++;
	}

	// Support for third party field editors
	$extra_results = array();

	// Flexible Checkout Fields for WooCommerce
	$fcfw_fields = peachpay_import_fcfw_fields( $section );
	if ( $fcfw_fields ) {
		$extra_results = array_merge( $extra_results, $fcfw_fields );
	}

	// Checkout Field Editor for WooCommerce
	$cfew_fields = peachpay_import_cfew_fields( $section );
	if ( $cfew_fields ) {
		$extra_results = array_merge( $extra_results, $cfew_fields );
	}

	// Checkout Field Manager for WooCommerce
	$cfm_fields = peachpay_import_cfm_fields( $section );
	if ( $cfm_fields ) {
		$extra_results = array_merge( $extra_results, $cfm_fields );
	}

	// Klaviyo integration
	$klaviyo_fields = peachpay_import_klaviyo_checkbox( $section );
	if ( $klaviyo_fields ) {
		$extra_results = array_merge( $extra_results, $klaviyo_fields );
	}

	if ( 0 === count( $result ) ) {
		$next_order_number = 1;
	}
	if ( $extra_results ) {
		foreach ( $extra_results as $extra ) {
			$result[ $next_order_number ] = $extra;
			$next_order_number++;
		}
	}

	return $result;
}

/**
 * Returns a list of enabled field data from the Flexible Checkout Fields for WooCommerce formatted for use in the PeachPay modal.
 *
 * @param string $section the section that is targeted.
 */
function peachpay_import_fcfw_fields( $section ) {
	if ( ! is_plugin_active( 'flexible-checkout-fields/flexible-checkout-fields.php' ) ) {
		return array();
	}

	$fcfw_option = get_option( 'inspire_checkout_fields_settings' );
	if ( 'shipping' === $section ) {
		$fcfw_option = $fcfw_option['shipping'];
	} elseif ( 'billing' === $section ) {
		$fcfw_option = $fcfw_option['billing'];
	} else {
		$fcfw_option = $fcfw_option['order'];
	}

	if ( ! $fcfw_option ) {
		return array();
	}

	$result        = array();
	$result_number = 1;
	foreach ( $fcfw_option as $option ) {
		// Strip out woocommerce defaults
		if ( isset( $option['external_field'] ) || ( isset( $option['type'] ) && peachpay_starts_with( $option['type'], 'inspire' ) ) ) {
			// Convert to PeachPay standard
			if ( '1' === $option['visible'] ) {
				// This feels backwards, but yes, '0' is on and '1' is off.
				continue;
			}
			// Only enable fields our field editor is currently able to support.
			if ( peachpay_is_valid_import_field( 'fcfw', $option['type'] ) ) {
				$result[ $result_number ]['field_enable'] = 'yes';
			} else {
				continue;
			}
			$result[ $result_number ]['field_default']  = isset( $option['default'] ) ? $option['default'] : '';
			$result[ $result_number ]['field_label']    = $option['label'];
			$result[ $result_number ]['field_name']     = $option['name'];
			$result[ $result_number ]['field_required'] = '1' === $option['required'] ? 'yes' : 'no';
			// Some type_list types need to be converted.
			switch ( $option['type'] ) {
				case 'textarea':
				case 'number':
				case 'url':
					$result[ $result_number ]['type_list'] = 'text';
					break;
				case 'phone':
					$result[ $result_number ]['type_list'] = 'tel';
					break;
				case 'inspirecheckbox':
					$result[ $result_number ]['type_list'] = 'checkbox';
					break;
				default: // text, email
					$result[ $result_number ]['type_list'] = $option['type'];
			}
			$result[ $result_number ]['width'] = '100'; // FCFW has no setting for this; default to 100% width

			$result_number++;
		}
	}

	return $result;
}

/**
 * Returns a list of enabled field data from the Checkout Field Editor for WooCommerce formatted for use in the PeachPay modal.
 *
 * @param string $section the section that is targeted.
 */
function peachpay_import_cfew_fields( $section ) {
	// Support for Flexible Checkout Fields for WooCommerce
	if ( ! is_plugin_active( 'woo-checkout-field-editor-pro/checkout-form-designer.php' ) ) {
		return array();
	}

	$fce_option = null;
	if ( 'shipping' === $section ) {
		$fce_option = get_option( 'wc_fields_shipping' );
	} elseif ( 'billing' === $section ) {
		$fce_option = get_option( 'wc_fields_billing' );
	} else {
		$fce_option = get_option( 'wc_fields_additional' );
	}

	if ( ! $fce_option ) {
		return array();
	}

	$result        = array();
	$result_number = 1;
	foreach ( $fce_option as $option ) {
		// Strip out WooCommerce default fields
		if ( isset( $option['type'] ) && isset( $option['name'] ) ) {
			// Convert to PeachPay standard
			if ( ! $option['enabled'] ) {
				continue;
			}
			// Only enable fields our field editor is currently able to support.
			if ( peachpay_is_valid_import_field( 'cfew', $option['type'] ) ) {
				$result[ $result_number ]['field_enable'] = 'yes';
			} else {
				continue;
			}
			$result[ $result_number ]['field_default']  = isset( $option['default'] ) ? $option['default'] : '';
			$result[ $result_number ]['field_label']    = $option['label'];
			$result[ $result_number ]['field_name']     = $option['name'];
			$result[ $result_number ]['field_required'] = '1' === $option['required'] ? 'yes' : 'no';
			// Some type_list types need to be converted.
			switch ( $option['type'] ) {
				case 'textarea':
				case 'number':
				case 'url':
					$result[ $result_number ]['type_list'] = 'text';
					break;
				case 'phone':
					$result[ $result_number ]['type_list'] = 'tel';
					break;
				case 'radio':
					$result[ $result_number ]['type_list'] = 'radio';
					$numoptions                            = count( $option['options'] );
					for ( $i = 0; $i < $numoptions; $i++ ) {
						$n                                        = $i + 1;
						$result[ $result_number ]['option'][ $n ] = $option['options'][ $n ];
						$result[ $result_number ]['option_order'][ $i ] = array( $n, strval( $n ) );
					}
					break;
				default: // text, email
					$result[ $result_number ]['type_list'] = $option['type'];
			}
			$result[ $result_number ]['width'] = '100'; // CFEW has no setting for this; default to 100% width

			$result_number++;
		}
	}

	return $result;
}

/**
 * Returns a list of enabled field data from Klaviyo formatted for use in the PeachPay modal.
 *
 * @param string $section the section that is targeted.
 */
function peachpay_import_klaviyo_checkbox( $section ) {
	// Support for Klaviyo
	if ( ! is_plugin_active( 'klaviyo/klaviyo.php' ) || 'billing' !== $section ) {
		return array();
	}
	$klaviyo_settings = get_option( 'klaviyo_settings' );
	if ( ! $klaviyo_settings || ! $klaviyo_settings['klaviyo_subscribe_checkbox'] ) {
		return array();
	}
	// At least 1 of the newsletter and sms checkbox are active!
	$result = array();
	$i      = 0;

	// Newsletter.
	if ( $klaviyo_settings['klaviyo_subscribe_checkbox'] ) {
		$result[ $i ]['field_default']  = 1;
		$result[ $i ]['field_enable']   = 'yes';
		$result[ $i ]['field_label']    = $klaviyo_settings['klaviyo_newsletter_text'];
		$result[ $i ]['field_name']     = 'kl_newsletter_checkbox';
		$result[ $i ]['field_required'] = 'no';
		$result[ $i ]['type_list']      = 'checkbox';
		$result[ $i ]['width']          = '100'; // default to 100% width
		$i++;
	}

	// SMS.
	if ( $klaviyo_settings['klaviyo_sms_subscribe_checkbox'] ) {
		$result[ $i ]['field_default']  = 1;
		$result[ $i ]['field_enable']   = 'yes';
		$result[ $i ]['field_label']    = $klaviyo_settings['klaviyo_sms_consent_text'];
		$result[ $i ]['field_name']     = 'kl_sms_consent_checkbox';
		$result[ $i ]['field_required'] = 'no';
		$result[ $i ]['type_list']      = 'checkbox';
		$result[ $i ]['width']          = '100'; // default to 100% width
		$i++;
	}

	return $result;
}

/**
 * Returns a list of enabled field data from the Checkout Field Manager for WooCommerce formatted for use in the PeachPay modal.
 *
 * @param string $section the section that is targeted.
 */
function peachpay_import_cfm_fields( $section ) {
	// Support for Checkout Field Manager for WooCommerce
	if ( ! is_plugin_active( 'woocommerce-checkout-manager/woocommerce-checkout-manager.php' ) ) {
		return array();
	}

	$cfm_option = null;
	if ( 'shipping' === $section ) {
		$cfm_option = get_option( 'wooccm_shipping' );
	} elseif ( 'billing' === $section ) {
		$cfm_option = get_option( 'wooccm_billing' );
	} else {
		$cfm_option = get_option( 'wooccm_additional' );
	}

	if ( ! $cfm_option ) {
		return array();
	}

	$result        = array();
	$result_number = 1;
	foreach ( $cfm_option as $option ) {
		// Strip out WooCommerce default fields
		if ( str_contains( $option['key'], 'wooccm' ) ) {
			// Convert to PeachPay standard
			if ( $option['disabled'] ) {
				continue;
			}
			// Only enable fields our field editor is currently able to support.
			if ( peachpay_is_valid_import_field( 'cfm', $option['type'] ) ) {
				$result[ $result_number ]['field_enable'] = 'yes';
			} else {
				continue;
			}
			$result[ $result_number ]['field_default']  = isset( $option['default'] ) ? $option['default'] : '';
			$result[ $result_number ]['field_label']    = $option['label'];
			$result[ $result_number ]['field_name']     = $option['key'];
			$result[ $result_number ]['field_required'] = true === $option['required'] ? 'yes' : 'no';
			// Some type_list types need to be converted.
			switch ( $option['type'] ) {
				case 'textarea':
				case 'number':
					$result[ $result_number ]['type_list'] = 'text';
					break;
				case 'heading':
					$result[ $result_number ]['type_list'] = 'header';
					break;
				case 'radio':
					$result[ $result_number ]['type_list'] = 'radio';
					$numoptions                            = count( $option['options'] );
					for ( $i = 0; $i < $numoptions; $i++ ) {
						$n                                        = $i + 1;
						$label                                    = $option['options'][ $i ]['label'];
						$result[ $result_number ]['option'][ $n ] = $label;
						$result[ $result_number ]['option_order'][ $i ] = array( $label, $label );
					}
					break;
				case 'select':
					$result[ $result_number ]['type_list'] = 'select';
					$numoptions                            = count( $option['options'] );
					for ( $i = 0; $i < $numoptions; $i++ ) {
						$n                                        = $i + 1;
						$label                                    = $option['options'][ $i ]['label'];
						$result[ $result_number ]['option'][ $n ] = $label;
						$result[ $result_number ]['option_order'][ $i ] = array( $label, $label );
					}
					break;
				default: // text, email, tel, checkbox
					$result[ $result_number ]['type_list'] = $option['type'];
			}
			$result[ $result_number ]['width'] = '100'; // default to 100% width

			$result_number++;
		}
	}

	return $result;
}

/**
 * A helper method to determine whether a checkout field in a supported integration is usable by the PeachPay modal or not.
 *
 * @param string $plugin the plugin the field being checked is from.
 * @param string $type the field type being checked.
 */
function peachpay_is_valid_import_field( $plugin, $type ) {
	if ( 'fcfw' === $plugin ) {
		switch ( $type ) {
			case 'header':
			case 'textarea':
			case 'number':
			case 'url':
			case 'phone':
			case 'inspirecheckbox':
			case 'text':
			case 'email':
				return true;
			default:
				return false;
		}
	} elseif ( 'cfew' === $plugin ) {
		switch ( $type ) {
			case 'header':
			case 'textarea':
			case 'number':
			case 'url':
			case 'phone':
			case 'radio':
			case 'text':
			case 'email':
				return true;
			default:
				return false;
		}
	} elseif ( 'cfm' === $plugin ) {
		switch ( $type ) {
			case 'heading':
			case 'textarea':
			case 'number':
			case 'tel':
			case 'radio':
			case 'checkbox':
			case 'text':
			case 'email':
			case 'select':
				return true;
			default:
				return false;
		}
	}
	return false;
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

/**
 * Returns a list of all the enabled field order arrangements for rendering in the modal.
 *
 * @param object $section This is to determin which section it is to pull data from.
 */
function peachpay_enabled_field_list_order( $section ) {
	$field_option = get_option( 'peachpay_field_editor_' . $section );
	$result       = array();
	if ( isset( $field_option[ $section . '_order' ] ) ) {
		foreach ( $field_option[ $section . '_order' ] as $order_number ) {
			if ( isset( $field_option[ $section ][ $order_number ]['field_enable'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_enable'] ) {
				$result[] = $order_number;
			}
		}
	}

	// Support for third party field editors
	$extra_results = array();

	// Flexible Checkout Fields for WooCommerce
	$fcfw_fields = peachpay_import_fcfw_fields( $section );
	if ( $fcfw_fields ) {
		$extra_results = array_merge( $extra_results, $fcfw_fields );
	}

	// Checkout Field Editor for WooCommerce
	$cfew_fields = peachpay_import_cfew_fields( $section );
	if ( $cfew_fields ) {
		$extra_results = array_merge( $extra_results, $cfew_fields );
	}

	// Checkout Field Manager for WooCommerce
	$cfm_fields = peachpay_import_cfm_fields( $section );
	if ( $cfm_fields ) {
		$extra_results = array_merge( $extra_results, $cfm_fields );
	}

	// Klaviyo
	$klaviyo_fields = peachpay_import_klaviyo_checkbox( $section );
	if ( $klaviyo_fields ) {
		$extra_results = array_merge( $extra_results, $klaviyo_fields );
	}

	if ( $extra_results ) {
		$num_fields        = count( $result );
		$num_new_fields    = count( $extra_results );
		$next_order_number = 0 === $num_fields ? 0 : $result[ $num_fields - 1 ];
		$next_order_number++;
		for ( $i = 0; $i < $num_new_fields; $i++ ) {
			$result[ $num_fields ] = $next_order_number;
			$num_fields++;
			$next_order_number++;
		}
	}

	return $result;
}

/**
 * Returns a list of all peachpay additional fields (enabled or not).
 */
function peachpay_additional_field_list() {
	$field_option = get_option( 'peachpay_field_editor_additional' );
	if ( ! isset( $field_option['additional_order'] ) ) {
		return;
	}
	return $field_option['additional'];
}

/**
 * An additional field in this function's scope is considered a field either in the additional field section
 * or a non-default field within the any of the other sections (such as billing or shipping). All fields
 * that made this criteria and are enabled will be returned as a list.
 */
function peachpay_all_additional_enabled_field_list() {
	$fields         = array();
	$check_sections = array(
		'billing'    => true,
		'shipping'   => true,
		'additional' => false,
	);

	foreach ( $check_sections as $section => $drop_defaults ) {
		$result = peachpay_enabled_field_list( $section, $drop_defaults );
		if ( isset( $result ) ) {
			$fields = array_merge( $fields, $result );
		}
	}

	return $fields;
}

// Display peachpay additional fields in the order admin panel.
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'peachpay_display_additional_fields_in_admin' );

/**
 * Displays peachpay additional fields in the order admin panel.
 *
 * @param WC_Order $order Order object.
 */
function peachpay_display_additional_fields_in_admin( $order ) {
	$all_additional_fields = peachpay_all_additional_enabled_field_list();  // get list of enabled peachpay additional fields.
	$order_meta            = get_array_intersection( $order->get_meta_data(), $all_additional_fields );  // get order meta data. Contains the additional field key<->value pairs for this order.

	if ( empty( $order_meta ) || empty( $all_additional_fields ) ) {
		return;
	}

	?>
	<div class="address">
		<h3><?php esc_html_e( 'Additional information', 'peachpay-for-woocommerce' ); ?></h3>
		<p>
			<?php
			foreach ( $all_additional_fields as $array ) {
				$field_label = $array['field_label'];
				?>
				<strong> <?php echo esc_html( $field_label . ':' ); ?> </strong>
				<?php

				if ( isset( $order_meta[ $array['field_name'] ] ) ) {
					$field_value = $order_meta[ $array['field_name'] ]['value'];

					// Converts option value to option text (more readable for customer).
					if ( isset( $array['option'] ) && isset( $array['option'][ $field_value ] ) ) {
						$field_value = $array['option'][ $field_value ];
					}
					echo esc_html( $field_value );

				} else {
					echo esc_html( 'empty' );
				}
			}
			?>
		</p>
	</div>
	<?php
}

/**
 * Returns the "intersetion" of two arrays as follows: each input is an array of keyed arrays;
 * find the matching keys and return a keyed array with the matching keys and corresponding values.
 *
 * This function is different than PHP's array_intersect in that it matches on keys. This one does
 * some pre-processing on the input arrays, then calls array_intersect_key.
 *
 * @param Array $meta_data An array whose keys to compare to the other array's keys.
 * @param Array $fields_list An array whose keys to compare to the other array's keys.
 * @return Array An array with the matching keys and corresponding values.
 */
function get_array_intersection( $meta_data, $fields_list ) {
	$meta_extracted = array_map(
		function ( $v ) {
            return [ $v->get_data()['key'] => $v->get_data() ];  // phpcs:ignore
		},
		$meta_data
	);

	$meta_extracted_keyed = array_merge( ...$meta_extracted );
	$fields_extracted     = array_map(
		function ( $v ) {
            return [ $v['field_name'] => $v ];  // phpcs:ignore
		},
		$fields_list
	);

	if ( empty( $fields_extracted ) ) {
		return array();
	}

	$fields_extracted_keyed = array_merge( ...$fields_extracted );
	return array_intersect_key( $meta_extracted_keyed, $fields_extracted_keyed );
}

/**
 * Renders a table of custom fields in the receipt page under Order Details.
 * If there are no custom fields. Nothing will be echoed.
 *
 * @param Object $param an object passed in by a hook that contains useful data such as order number.
 */
function peachpay_render_additional_fields_receipt( $param ) {
	$fields       = peachpay_all_additional_enabled_field_list();
	$order_number = $param->get_id();
	$order_data   = get_post_meta( $order_number );

	if ( empty( $fields ) ) {
		return;
	}

	?>
	<table id='pp-receipt_additional_fields' style='table-layout: fixed;'>
	<tr>
		<th> <?php esc_html_e( 'Additional information', 'peachpay-for-woocommerce' ); ?> </th>
		<th></th>
	</tr>
	<?php

	foreach ( $fields as $array ) {
		if ( isset( $order_data[ $array['field_name'] ] ) ) {
			$value = $order_data[ $array['field_name'] ][0];
			// Converts option value to option text (more readable for customer).
			if ( isset( $array['option'] ) && isset( $array['option'][ $value ] ) ) {
				$value = $array['option'][ $value ];
			}
			?>
			<tr style='word-wrap: break-word'>
				<td> <?php echo esc_html( $array['field_label'] ); ?> </td>
				<td> <?php echo esc_html( $value ); ?> </td>
			</tr>
			<?php
		}
	}
	?>
	</table>
	<?php
}

/**
 * Adds enabled field data to the order emails.
 *
 * @param Array    $fields to be added to.
 * @param Boolean  $sent_to_admin flag.
 * @param WC_Order $order in question.
 */
function peachpay_add_fields_to_emails( $fields, $sent_to_admin, $order ) {
	$custom_field_sections = array();

	$billing_fields = get_option( 'peachpay_field_editor_billing', array( 'billing' => array() ) );
	if ( is_array( $billing_fields ) && isset( $billing_fields['billing'] ) ) {
		$custom_field_sections['billing'] = $billing_fields['billing'];
	}

	$shipping_fields = get_option( 'peachpay_field_editor_shipping', array( 'shipping' => array() ) );
	if ( is_array( $shipping_fields ) && isset( $shipping_fields['shipping'] ) ) {
		$custom_field_sections['shipping'] = $shipping_fields['shipping'];
	}

	$additional_fields = get_option( 'peachpay_field_editor_additional', array( 'additional' => array() ) );
	if ( is_array( $additional_fields ) && isset( $additional_fields['additional'] ) ) {
		$custom_field_sections['additional'] = $additional_fields['additional'];
	}

	foreach ( $custom_field_sections as $section => $custom_fields ) {
		foreach ( $custom_fields as $custom_field ) {
			if ( ! peachpay_is_default_field( $section, $custom_field['field_name'] ) && 'yes' === $custom_field['field_enable'] ) {
				$field_value = get_post_meta( $order->get_id(), $custom_field['field_name'], true );

				$fields[ $custom_field['field_name'] ] = array(
					'label' => $custom_field['field_label'],
					'value' => $field_value,
				);
			}
		}
	}

	return $fields;
}
add_filter( 'woocommerce_email_order_meta_fields', 'peachpay_add_fields_to_emails', 10, 3 );
