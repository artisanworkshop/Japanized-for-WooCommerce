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

peachpay_setup_field_editor();

/**
 * Sets up the field editor.
 */
function peachpay_setup_field_editor() {

	peachpay_initialize_fields( 'shipping' );

	add_filter( 'woocommerce_checkout_fields', 'peachpay_virtual_product_fields_preset', 9999 );
	add_filter( 'peachpay_register_feature', 'peachpay_filter_register_field_editor_support', 10, 1 );
	add_filter( 'peachpay_script_data', 'peachpay_filter_field_editor_script_data', 10, 1 );

	// Adding the billing fields accoring to the data.
	add_filter( 'woocommerce_checkout_fields', 'peachpay_billing_fields', 10, 1 );
	// Adding the shipping fields accoring to the data.

	// Need to remove this for future use.
	// Need this add_filter( 'woocommerce_checkout_fields', 'peachpay_shipping_fields', 10, 1 );.

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
 * Generates the preset fields for virtual products.
 *
 * @param object $fields The list of existing billing fields.
 */
function peachpay_virtual_product_fields_preset( $fields ) {
	if ( ! WC()->cart->needs_shipping_address() && peachpay_get_settings_option( 'peachpay_general_options', 'enable_virtual_product_fields' ) ) {
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
 * Registers field editor support.
 *
 * @param array $base_features The existing registered features.
 */
function peachpay_filter_register_field_editor_support( $base_features ) {

	$base_features['enable_virtual_product_fields'] = array(
		'enabled' => peachpay_get_settings_option( 'peachpay_general_options', 'enable_virtual_product_fields' ),
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
	// temp_section is only for our end since we swtich the fields shipping and billing the other way around.
	// So we need to target the shipping option section for woocommerce billing section.
	$temp_section = 'billing' === $section ? 'shipping' : 'billing';
	$field_option = get_option( 'peachpay_field_editor_' . $temp_section );
	$priority     = 10;

	$field_name_keys = array();
	foreach ( $field_option[ $temp_section . '_order' ] as $order ) {
		if ( 'header' === $field_option[ $temp_section ][ $order ]['type_list'] ) {
			continue;
		}
		if ( ! isset( $field_option[ $temp_section ][ $order ]['field_enable'] ) || 'yes' !== $field_option[ $temp_section ][ $order ]['field_enable'] ) {
			continue;
		}
		// If it is the default field we need to replace the shipping name to match it with woocomerce field name as they are keys in woocommerce.
		if ( peachpay_is_default_field( $temp_section, $field_option[ $temp_section ][ $order ]['field_name'] ) ) {
			if ( 'billing' === $section ) {
				$temp_name = str_ireplace( 'shipping_', 'billing_', $field_option['shipping'][ $order ]['field_name'] );
				array_push( $field_name_keys, $temp_name );
				continue;
			} else {
				$temp_name = str_ireplace( 'billing_', 'shipping_', $field_option['billing'][ $order ]['field_name'] );
				array_push( $field_name_keys, $temp_name );
				continue;
			}
		}
		array_push( $field_name_keys, $field_option[ $temp_section ][ $order ]['field_name'] );
	}

	foreach ( $fields[ $section ] as $key => $field ) {
		if ( ! in_array( $key, $field_name_keys, true ) ) {
			unset( $fields[ $section ][ $key ] );
		}
	}

	foreach ( $field_option[ $temp_section ] as $key => $value ) {
		if ( 'header' === $value['type_list'] ) {
			continue;
		}
		if ( ! isset( $value['field_enable'] ) || 'yes' !== $value['field_enable'] ) {
			continue;
		}
		$temp_name = $value['field_name'];
		// If it is the default field we need to replace the shipping name to match it with woocomerce field name as they are keys in woocommerce.
		if ( peachpay_is_default_field( $temp_section, $temp_name ) ) {
			if ( 'shipping' === $temp_section ) {
				$temp_name = str_ireplace( 'shipping_', 'billing_', $temp_name );
			} else {
				$temp_name = str_ireplace( 'billing_', 'shipping_', $temp_name );
			}
		}
		if ( array_key_exists( $temp_name, $fields[ $section ] ) ) {
			$fields[ $section ][ $temp_name ]['priority'] = $priority;
			$fields[ $section ][ $temp_name ]['required'] = isset( $value['field_required'] ) && 'yes' === $value['field_required'] ? 1 : '';
			$priority                                    += 10;
		} else {
			$fields[ $section ][ $temp_name ] = array(
				'type'     => $value['type_list'],
				'label'    => $value['field_label'],
				'required' => isset( $value['field_required'] ) && 'yes' === $value['field_required'] ? 1 : '',
				'priority' => $priority,
			);
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
			woocommerce_form_field(
				$field_option['additional'][ $order_number ]['field_name'],
				array(
					'type'     => $field_option['additional'][ $order_number ]['type_list'],
					'required' => isset( $field_option['additional'][ $order_number ]['field_required'] ) && 'yes' === $field_option['additional'][ $order_number ]['field_required'],
					'label'    => $field_option['additional'][ $order_number ]['field_label'],
					'options'  => peachpay_set_options_list( $field_option['additional'][ $order_number ]['option'] ),
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
}

/**
 * Update the metadata when a new field input is added.
 *
 * @param object $order_id takes in the order id.
 * @param string $section the section that is targeted.
 */
function save_what_we_added( $order_id, $section ) {
	// temp_section is only for our end since we swtich the fields shipping and billing the other way around.
	// So we need to target the shipping option section for woocommerce billing section.
	$temp_section = 'billing' === $section ? 'shipping' : 'billing';
	if ( 'additional' === $section ) {
		$temp_section = $section;
	}
	$field_option = get_option( 'peachpay_field_editor_' . $temp_section );
	if ( ! isset( $field_option[ $temp_section . '_order' ] ) || empty( $field_option[ $temp_section . '_order' ] ) ) {
		return;
	}
	foreach ( $field_option[ $temp_section . '_order' ] as $order_number ) {
		if ( peachpay_is_default_field( $temp_section, $field_option[ $temp_section ][ $order_number ]['field_name'] ) || 'header' === $field_option[ $temp_section ][ $order_number ]['type_list'] ) {
			continue;
		}
		if ( ! isset( $field_option[ $temp_section ][ $order_number ]['field_enable'] ) || 'yes' !== $field_option[ $temp_section ][ $order_number ]['field_enable'] ) {
			continue;
		}
		// phpcs:disable
		if ( ! empty( $_POST[ $field_option[ $temp_section ][ $order_number ]['field_name'] ] ) ) {
			add_post_meta(
				$order_id,
				$field_option[ $temp_section ][ $order_number ]['field_name'],
				$_POST[ $field_option[ $temp_section ][ $order_number ]['field_name'] ]
			);
		}
		// phpcs:enable
	}
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
	$field_option = get_option( 'peachpay_field_editor_' . $section );

	if ( ! isset( $field_option[ $section . '_order' ] ) ) {
		return;
	}
	$result = array();
	foreach ( $field_option[ $section . '_order' ] as $order_number ) {
		$field_name = $field_option[ $section ][ $order_number ]['field_name'];
		$add_field  = ! $ignore_defaults || ! ( peachpay_is_default_field( $section, $field_name ) || peachpay_is_default_header( $section, $field_name ) );

		if ( $add_field && isset( $field_option[ $section ][ $order_number ]['field_enable'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_enable'] ) {
			$result[ $order_number ] = $field_option[ $section ][ $order_number ];
			if ( isset( $field_option[ $section ][ $order_number ]['option'] ) ) {
				$result[ $order_number ]['option_order'] = array();
				foreach ( $field_option[ $section ][ $order_number ]['option'] as $value => $name ) {
					$result[ $order_number ]['option_order'][] = array( $value, $name );
				}
			}
		}
	}

	return $result;
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
	);
	return in_array( $target, $default_field_name_keys, true );
}

/**
 * A helper method that check if the header is a default header or not.
 * return true if it is default and false if it is not.
 *
 * @param string $section the target section string.
 * @param string $target the string to check.
 */
function peachpay_is_default_header( $section, $target ) {
	if ( 'additional' === $section ) {
		return false;
	}

	$default_field_name_keys = array(
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
	if ( ! isset( $field_option[ $section . '_order' ] ) ) {
		return;
	}
	foreach ( $field_option[ $section . '_order' ] as $order_number ) {
		if ( isset( $field_option[ $section ][ $order_number ]['field_enable'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_enable'] ) {
			$result[] = $order_number;
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
	$all_additional_fields = peachpay_all_additional_enabled_field_list();  // get list of enabled peaypach additional fields.
	$order_meta            = get_array_intersection( $order->get_meta_data(), $all_additional_fields );  // get order meta data. Contains the additional field key<->value pairs for this order.

	if ( empty( $order_meta ) || empty( $all_additional_fields ) ) {
		return;
	}

	?>
	<div class="address">
		<h3><?php esc_html_e( 'Additional Fields' ); ?></h3>
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
		<th> <?php esc_html_e( 'Additional Fields', 'woocommerce-for-japan' ); ?> </th>
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
