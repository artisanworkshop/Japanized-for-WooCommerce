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

	add_filter( 'woocommerce_checkout_fields', 'peachpay_virtual_product_fields_preset', 9999 );
	add_filter( 'peachpay_register_feature', 'peachpay_filter_register_field_editor_support', 10, 1 );
	add_filter( 'peachpay_script_data', 'peachpay_filter_field_editor_script_data', 10, 1 );

	if ( ! peachpay_get_settings_option( 'peachpay_field_editor', 'enable_field_editor' ) ) {
		return;
	}
	// Adding new fields for the checkout page.
	add_action( 'woocommerce_before_order_notes', 'peachpay_additional_fields' );
	// save fields to order meta.
	add_action( 'woocommerce_checkout_update_order_meta', 'save_what_we_added' );
	// Making fields required with notices and custome validator.
	add_action( 'woocommerce_checkout_process', 'check_if_required' );

}

/**
 * Generates the preset fields for virtual products.
 *
 * @param object $fields The list of existing billing fields.
 */
function peachpay_virtual_product_fields_preset( $fields ) {
	if ( ! WC()->cart->needs_shipping_address() && peachpay_get_settings_option( 'peachpay_field_editor', 'enable_virtual_product_fields' ) ) {
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

	$base_features['additional_fields'] = array(
		'enabled' => peachpay_get_settings_option( 'peachpay_field_editor', 'enable_field_editor' ),
		'version' => 1,
	);

	$base_features['enable_virtual_product_fields'] = array(
		'enabled' => peachpay_get_settings_option( 'peachpay_field_editor', 'enable_virtual_product_fields' ),
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
		} elseif ( 'select' === $field_option['field'][ $order_number ]['type_list'] || 'radio' === $field_option['field'][ $order_number ]['type_list'] ) {
			woocommerce_form_field(
				$field_option['field'][ $order_number ]['field_name'],
				array(
					'type'     => $field_option['field'][ $order_number ]['type_list'],
					'required' => isset( $field_option['field'][ $order_number ]['field_required'] ) && 'yes' === $field_option['field'][ $order_number ]['field_required'],
					'label'    => $field_option['field'][ $order_number ]['field_label'],
					'options'  => peachpay_set_options_list( $field_option['field'][ $order_number ]['option'] ),
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
 * Prepares the options array with a default value.
 *
 * @param array  $options the option array from the php data.
 * @param string $default_option the default value for the select box.
 */
function peachpay_set_options_list( $options, $default_option = 'Please select' ) {
	return array_replace( array( '' => $default_option ), $options );
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
			if ( isset( $field_option['field'][ $order_number ]['option'] ) ) {
				$result[ $order_number ]['option_order'] = array();
				foreach ( $field_option['field'][ $order_number ]['option'] as $value => $name ) {
					$result[ $order_number ]['option_order'][] = array( $value, $name );
				}
			}
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

/**
 * Returns a list of all peachpay additional fields (enabled or not).
 */
function peachpay_additional_field_list() {
	$field_option = get_option( 'peachpay_field_editor' );
	if ( ! isset( $field_option['enable_field_editor'] ) ) {
		return;
	}
	if ( ! isset( $field_option['order'] ) ) {
		return;
	}
	return $field_option['field'];
}

// Display peachpay additional fields in the order admin panel.
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'peachpay_display_additional_fields_in_admin' );

/**
 * Displays peachpay additional fields in the order admin panel.
 *
 * @param WC_Order $order Order object.
 */
function peachpay_display_additional_fields_in_admin( $order ) {
	$order_additional_fields = $order->get_meta_data();  // get order meta data. Contains the additional field key<->value pairs for this order.
	$all_additional_fields   = peachpay_additional_field_list();  // get list of enabled peaypach additional fields.
	if ( ! empty( $order_additional_fields ) && ! empty( $all_additional_fields ) ) {
		$fields_to_display = get_array_intersection( $order_additional_fields, $all_additional_fields );
		if ( ! empty( $fields_to_display ) ) {
			?>
			<div class="address">
				<h3><?php esc_html_e( 'Additional Fields' ); ?></h3>
				<p>
					<?php
					foreach ( $fields_to_display as $field ) {
						?>
						<strong> <?php echo esc_html( $field['key'] . ':' ); ?> </strong>
						<?php echo esc_html( $field['value'] ); ?>
						<?php
					}
					?>
				</p>
			</div>
			<?php
		}
	}
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
	$meta_extracted         = array_map(
		function ( $v ) {
            return [ $v->get_data()['key'] => $v->get_data() ];  // phpcs:ignore
		},
		$meta_data
	);
	$meta_extracted_keyed   = array_merge( ...$meta_extracted );
	$fields_extracted       = array_map(
		function ( $v ) {
            return [ $v['field_label'] => $v ];  // phpcs:ignore
		},
		$fields_list
	);
	$fields_extracted_keyed = array_merge( ...$fields_extracted );
	return array_intersect_key( $meta_extracted_keyed, $fields_extracted_keyed );
}
