<?php
/**
 * Implements the PeachPay checkout window field editor.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// PHPCS:ignore
if ( isset( $_GET ) && array_key_exists( 'tab', $_GET ) && 'field' === $_GET['tab'] ) {
	add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_field_editor_style' );
	add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_field_editor_script' );
}

/**
 * Enqueues admin.css
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_field_editor_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'peachpay-field-editor',
		plugin_dir_url( __FILE__ ) . 'assets/field-editor.css',
		array(),
		true
	);
}

/**
 * Enqueues field-editor.js to the modal.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_field_editor_script( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	add_action( 'admin_footer', 'peachpay_add_new_field_modal' );
	wp_enqueue_script(
		'peachpay-field-editor',
		plugin_dir_url( __FILE__ ) . 'assets/field-editor.js',
		array(),
		true,
		false
	);
}

/**
 * Adds the div that will contain the deactivation form modal
 */
function peachpay_add_new_field_modal() {
	?>
		<div id = "ppModal" class = "ppModal"></div>
	<?php
}

/**
 * Adds the checkout window field editor table options.
 */
function peachpay_field_editor() {
	add_settings_section(
		'peachpay_checkout_field_editor_table',
		'',
		'peachpay_generate_table_cb',
		'peachpay'
	);
}

/**
 * A function that generates the additional field editor table options.
 */
function peachpay_generate_table_cb() {
	$new_field_key = array(
		'type_list',
		'field_name',
		'field_label',
		'field_default',
		'field_required',
		'field_enable',
		'width',
	);
	//phpcs:disable
	$section = isset( $_GET['section'] ) ? wp_unslash( $_GET['section'] ) : 'shipping';
	//phpcs:enable

	if ( empty( get_option( 'peachpay_field_editor' ) ) ) {
		update_option( 'peachpay_field_editor', array() );
	}

	if ( empty( get_option( 'peachpay_field_editor_' . $section ) ) ) {
		if ( 'billing' === $section || 'shipping' === $section ) {
			peachpay_reset_region_presets_default_fields( $section );
		} else {
			update_option( 'peachpay_field_editor_' . $section, array() );
		}
	}

	if ( ! empty( get_option( 'peachpay_field_editor' )['field'] ) && empty( get_option( 'peachpay_field_editor_additional' )[ $section ] ) ) {
		$field_current                           = get_option( 'peachpay_field_editor' );
		$field_additional                        = get_option( 'peachpay_field_editor_additional' );
		$field_additional[ $section ]            = $field_current['field'];
		$field_additional[ $section . '_order' ] = $field_current['order'];
		$field_additional['next_index']          = $field_current['next_index'];
		update_option( 'peachpay_field_editor_additional', $field_additional );

		foreach ( $field_additional[ $section . '_order' ] as $index ) {
			$field_additional[ $section ][ $index ]['width'] = 100;
		}
		update_option( 'peachpay_field_editor_additional', $field_additional );
		update_option( 'peachpay_field_editor', null );
	}

	//phpcs:disable
	if ( isset( $_POST['type_list'] ) && isset( $_POST['field_name'] ) ) {
		$temp_field = array();
		foreach ( $new_field_key as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$temp_field[ $key ] = wp_unslash($_POST[ $key ]);
			} else if ( 'width' === $key && ! isset( $_POST[ $key ] ) && 'header' === $_POST[ 'type_list' ]) {
				$temp_field[ $key ] = '-';
			}
		}

		if( isset( $_POST['option'] ) ) {
			$temp_option_name = array();
			foreach ( $_POST['option']['name'] as $name ) {
				$temp_option_name[] = $name;
			}
			$temp_option_value = array();
			foreach ( $_POST['option']['value'] as $value ) {
				$temp_option_value[] = $value;
			}

			for ($i = 0; $i <= sizeof( $temp_option_name ) - 1; $i++) {
				$temp_field['option'][$temp_option_value[$i]] = $temp_option_name[$i];
			}
		}

		if ( peachpay_field_name_exist( $_POST['field_name'], $section ) && ! empty( get_option( 'peachpay_field_editor_' . $section )[ $section ] ) ) {
			$index      = peachpay_field_name_exist( $_POST['field_name'], $section );
			$curent_row = isset( $_POST['edit-row'] ) ? $_POST['edit-row'] : null;
			peachpay_overlap_field( $temp_field, $index, $new_field_key, $curent_row, $section );
		} elseif ( isset( $_POST['edit-row'] ) && ! peachpay_field_name_exist( $_POST['field_name'], $section ) ) {
			$field_option = get_option( 'peachpay_field_editor_' . $section );
			unset( $field_option[ $section ][ (int) $_POST['edit-row'] ] );
			unset( $field_option[ $section . '_order' ][ peachpay_get_order_index( (int) $_POST['edit-row'], $section ) ] );
			update_option( 'peachpay_field_editor_' . $section, $field_option );
			peachpay_add_new_field( $temp_field, $section );
		} else {
			peachpay_add_new_field( $temp_field, $section );
		}
	}

	if ( ! isset( $_GET['section'] ) || empty( $_GET['section'] ) ) {
		$_GET['section'] = 'billing';
	}
	//phpcs:enable
	?>
	<div class="pp-section-nav-container">
	<a
		class="<?php echo 'billing' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-link-inactive'; ?>"
		href="<?php echo esc_url( add_query_arg( 'section', 'billing' ) ); ?>"
		style="text-decoration:none;"
	> <?php esc_html_e( 'Billing', 'peachpay-for-woocommerce' ); ?>
	</a>
	<a
		class="<?php echo 'shipping' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-link-inactive'; ?>"
		href="<?php echo esc_url( add_query_arg( 'section', 'shipping' ) ); ?>"
		style="text-decoration:none;"
	> <?php esc_html_e( 'Shipping', 'peachpay-for-woocommerce' ); ?>
	</a>
	<a
		class="<?php echo 'additional' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-link-inactive'; ?>"
		href="<?php echo esc_url( add_query_arg( 'section', 'additional' ) ); ?>"
		style="text-decoration:none;"
	> <?php esc_html_e( 'Additional', 'peachpay-for-woocommerce' ); ?>
	</a>
	</div>
	<?php
		peachpay_generate_additional_field_table( $new_field_key, $section );
	?>
	<?php
}

/**
 * Generates the table for additional field data.
 *
 * @param array  $new_field_key the field keys array for array indexing as well as accessing the field.
 * @param string $section The current field section key.
 */
function peachpay_generate_additional_field_table( $new_field_key, $section ) {
	?>
	<div class="peachpay-setting-section" style="padding: 0px 16px;">
		<div class="table-form" style="overflow-x: scroll;">
			<table id="field-table">
				<thead>
					<?php
						peachpay_generate_buttons_headers_footer();
						peachpay_generate_table_headers_footer();
					?>
				</thead>
				<tfoot>
					<?php
						peachpay_generate_table_headers_footer();
						peachpay_generate_buttons_headers_footer();
					?>
				</tfoot>
				<tbody>
					<?php
						peachpay_generate_body( $new_field_key, $section );
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

/**
 * A helper function that generates the header and footer buttons.
 */
function peachpay_generate_buttons_headers_footer() {
	?>
		<tr id="table-buttons-header-footer">
			<td colspan="6" style="text-align: left; padding: 16px 0px;">
				<button class="button pp-button-primary field-button" type="button" id="add-new-field">+ <?php esc_html_e( 'Add new field', 'peachpay-for-woocommerce' ); ?></button>
				<button class="button pp-button-secondary remove-button" type="submit" id="remove-field"><?php esc_html_e( 'Remove', 'peachpay-for-woocommerce' ); ?></button>
				<button class="button pp-button-secondary enable-button" type="submit" id="enable-field"><?php esc_html_e( 'Enable', 'peachpay-for-woocommerce' ); ?></button>
				<button class="button pp-button-secondary disable-button" type="submit" id="disable-field"><?php esc_html_e( 'Disable', 'peachpay-for-woocommerce' ); ?></button>
			</td>
			<td colspan="6" style="padding: 16px 0px; text-align: right;">
				<a
				class="button pp-button-secondary"
				onclick="return confirm('Are you sure would you like to reset all your changes made to the PeachPay cart fields?')"
				type="button" id="reset-fields"
				href="
		<?php
		//phpcs:ignore
		echo add_query_arg( 'reset_field', 'reset' );
		peachpay_reset_default_fields();
		?>
				"
				><?php esc_html_e( 'Reset fields', 'peachpay-for-woocommerce' ); ?></a>
				<div class="pp-field-save-button" style="display: inline-block;">
					<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
				</div>
			</td>
		</tr>
			<?php
}

/**
 * A helper function that generates the table header and footer labels.
 */
function peachpay_generate_table_headers_footer() {
	?>
		<tr class="table-header-footer">
			<th style="border-bottom-left-radius: 5px; border-top-left-radius: 5px;" class="sort"></th>
			<th class="select-all-collum">
				<input type="checkbox" class="select-all">
			</th>
			<th><?php esc_html_e( 'Name', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Type', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Label', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Default value', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Width', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Required', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Enabled', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Delete', 'peachpay-for-woocommerce' ); ?></th>
			<th style="border-bottom-right-radius: 5px; border-top-right-radius: 5px;"><?php esc_html_e( 'Edit', 'peachpay-for-woocommerce' ); ?></th>
		</tr>
		<?php
}

/**
 * A helper function that generates the table body.
 *
 * @param array  $field_keys the field keys array for array indexing as well as accessing the field.
 * @param string $section The current field section key.
 */
function peachpay_generate_body( array $field_keys, $section ) {
	if ( ! empty( get_option( 'peachpay_field_editor_' . $section )[ $section ] ) ) {

		$field_option = get_option( 'peachpay_field_editor_' . $section );
		?>

		<?php
		foreach ( $field_option[ $section . '_order' ] as $order_number ) {
			?>
			<tr class="field-data-row row_<?php echo esc_html( $order_number ); ?> <?php echo ( ! isset( $field_option[ $section ][ $order_number ]['field_enable'] ) || '' === $field_option[ $section ][ $order_number ]['field_enable'] ) ? 'row-disabled' : ''; ?>" draggable="false" >
				<td class="sort">
				<i class="dragable-icon" aria-hidden="true"></i>
				<?php
				foreach ( $field_keys as $key ) {
					?>
					<input type="hidden"
						name="peachpay_field_editor_<?php echo esc_html( $section ); ?>[<?php echo esc_html( $section ); ?>][<?php echo esc_html( $order_number ); ?>][<?php echo esc_html( $key ); ?>]"
						class="field_<?php echo esc_html( $order_number ); ?>"
						value="<?php echo isset( $field_option[ $section ][ $order_number ][ $key ] ) ? esc_html( $field_option[ $section ][ $order_number ][ $key ] ) : ''; ?>"
						id ="<?php echo esc_html( $key . $order_number ); ?>"
					/>
					<?php
				}
				if ( isset( $field_option[ $section ][ $order_number ]['option'] ) && ! empty( isset( $field_option[ $section ][ $order_number ]['option'] ) ) ) {
					foreach ( $field_option[ $section ][ $order_number ]['option'] as $value => $name ) {
						?>
						<input
							type="hidden"
							name="peachpay_field_editor_<?php echo esc_html( $section ); ?>[<?php echo esc_html( $section ); ?>][<?php echo esc_html( $order_number ); ?>][option][<?php echo esc_html( $value ); ?>]"
							class="field_<?php echo esc_html( $order_number ); ?>"
							value="<?php echo esc_html( $name ); ?>"
						/>
						<?php
					}
				}
				?>
					<input type="hidden" class="field_<?php echo esc_html( $order_number ); ?>" name="peachpay_field_editor_<?php echo esc_html( $section ); ?>[<?php echo esc_html( $section ); ?>_order][]" value="<?php echo esc_html( $order_number ); ?>" id ="order<?php echo esc_html( $order_number ); ?>" >
					<input type="hidden" class="field_<?php echo esc_html( $order_number ); ?>" name="peachpay_field_editor_<?php echo esc_html( $section ); ?>[next_index]" value="<?php echo esc_html( $field_option['next_index'] ); ?>" id ="next_index<?php echo esc_html( $order_number ); ?>" />
					<input type="hidden" class="field-data" id="field-data_<?php echo esc_html( $order_number ); ?>" value="<?php echo esc_html( htmlspecialchars( peachpay_generate_field_data_json( $field_keys, $order_number, $section ), ENT_COMPAT ) ); ?>" />
				</td>
				<td>
					<input class="checkbox" type="checkbox" name="select_field" value="<?php echo esc_html( $order_number ); ?>" id="<?php echo esc_html( $order_number ); ?>">
				</td>
				<td> <?php echo esc_html( $field_option[ $section ][ $order_number ]['field_name'] ); ?> </td>
				<td> <?php echo esc_html( $field_option[ $section ][ $order_number ]['type_list'] ); ?> </td>
				<td> <?php echo esc_html( $field_option[ $section ][ $order_number ]['field_label'] ); ?></td>
				<td> <?php echo esc_html( $field_option[ $section ][ $order_number ]['field_default'] ); ?> </td>
				<td> <?php echo esc_html( ( isset( $field_option[ $section ][ $order_number ]['width'] ) && '-' !== $field_option[ $section ][ $order_number ]['width'] ) ? $field_option[ $section ][ $order_number ]['width'] . '%' : '-' ); ?> </td>
				<td> <?php echo ( isset( $field_option[ $section ][ $order_number ]['field_required'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_required'] ) ? '&#10003;' : '-'; ?> </td>
				<td class="th_field_enable" id ="field_<?php echo esc_html( $order_number ); ?>"> <?php echo ( isset( $field_option[ $section ][ $order_number ]['field_enable'] ) && 'yes' === $field_option[ $section ][ $order_number ]['field_enable'] ) ? '&#10003;' : '-'; ?> </td>
				<td>
					<button class="pp-delete-field" type="submit" name="select_field" value="<?php echo esc_html( $order_number ); ?>" id="<?php echo esc_html( $order_number ); ?>">&times;</button>
				</td>
				<td>
					<button class="button pp-edit-field" type="button" id="edit-field<?php echo esc_html( $order_number ); ?>" value="field-data_<?php echo esc_html( $order_number ); ?>" >
						<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="pencil-alt" class="pp-edit-field-icon svg-inline--fa fa-pencil-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
							<path d="M497.9 142.1l-46.1 46.1c-4.7 4.7-12.3 4.7-17 0l-111-111c-4.7-4.7-4.7-12.3 0-17l46.1-46.1c18.7-18.7 49.1-18.7 67.9 0l60.1 60.1c18.8 18.7 18.8 49.1 0 67.9zM284.2 99.8L21.6 362.4.4 483.9c-2.9 16.4 11.4 30.6 27.8 27.8l121.5-21.3 262.6-262.6c4.7-4.7 4.7-12.3 0-17l-111-111c-4.8-4.7-12.4-4.7-17.1 0zM124.1 339.9c-5.5-5.5-5.5-14.3 0-19.8l154-154c5.5-5.5 14.3-5.5 19.8 0s5.5 14.3 0 19.8l-154 154c-5.5 5.5-14.3 5.5-19.8 0zM88 424h48v36.3l-64.5 11.3-31.1-31.1L51.7 376H88v48z"></path>
						</svg>
					</button>
				</td>
			</tr>
				<?php
		}
	}
}

/**
 * This method opens a model and adds a new field to the form and table.
 *
 * @param array  $field This is the new field data that is to be added to the array data.
 * @param string $section The current field section key.
 */
function peachpay_add_new_field( array $field, $section ) {
	if ( empty( get_option( 'peachpay_field_editor_' . $section )[ $section ] ) ) {
		$field_option               = get_option( 'peachpay_field_editor_' . $section );
		$field_option[ $section ]   = array();
		$field_option['order']      = array();
		$field_option['next_index'] = 1;
		update_option( 'peachpay_field_editor_' . $section, $field_option );
	}
	$field_option                            = get_option( 'peachpay_field_editor_' . $section );
	$next_index                              = $field_option['next_index'];
	$field_option[ $section ][ $next_index ] = $field;
	$field_option[ $section . '_order' ][]   = $next_index;
	$field_option['next_index']++;
	update_option( 'peachpay_field_editor_' . $section, $field_option );
}

/**
 * This method updates just the current field data when field name does not exist.
 *
 * @param array  $field the field data that is to be edited.
 * @param array  $keys the field data keys.
 * @param int    $current_row the field row.
 * @param string $section The current field section key.
 */
function peachpay_update_field_data( array $field, array $keys, int $current_row, $section ) {
	$field_option = get_option( 'peachpay_field_editor_' . $section );
	foreach ( $keys as $key ) {
		$field_option[ $section ][ $current_row ][ $key ] = $field[ $key ];
	}
	if ( isset( $field['option'] ) ) {
		$temp_option_name = array();
		foreach ( $field['option']['name'] as $name ) {
			$temp_option_name[] = $name;
		}
		$temp_option_value = array();
		foreach ( $field['option']['value'] as $value ) {
			$temp_option_value[] = $value;
		}
		$temp_array_size = count( $temp_option_name );
		for ( $i = 0; $i <= $temp_array_size - 1; $i++ ) {
			$field_option[ $section ][ $current_row ]['option'][ $temp_option_name[ $i ] ] = $temp_option_value[ $i ];
		}
	}
	update_option( 'peachpay_field_editor_' . $section, $field_option );
}

/**
 * This method is use to edit the current field.
 *
 * @param array  $field the field data that is to be edited.
 * @param int    $index the field that is to be edited.
 * @param array  $keys the field data keys.
 * @param int    $current_row the field row.
 * @param string $section The current field section key.
 */
function peachpay_overlap_field( array $field, int $index, array $keys, $current_row, $section ) {
	$field_option = get_option( 'peachpay_field_editor_' . $section );
	foreach ( $keys as $key ) {
		if ( ! isset( $field[ $key ] ) ) {
			unset( $field_option[ $section ][ $index ][ $key ] );
		} else {
			$field_option[ $section ][ $index ][ $key ] = $field[ $key ];
		}
	}

	if ( isset( $field['option'] ) ) {
		unset( $field_option[ $section ][ $index ]['option'] );
		foreach ( $field['option'] as $value => $name ) {
			$field_option[ $section ][ $index ]['option'][ $value ] = $name;
		}
	} else {
		unset( $field_option[ $section ][ $index ]['option'] );
	}

	if ( peachpay_field_name_exist( $field['field_name'], $section ) && $index !== (int) $current_row && null !== $current_row ) {
		unset( $field_option[ $section ][ $current_row ] );
		unset( $field_option[ $section . '_order' ][ peachpay_get_order_index( $current_row, $section ) ] );
	}
	update_option( 'peachpay_field_editor_' . $section, $field_option );
}

/**
 * This method resets the additional fields data as well as the table content.
 */
function peachpay_reset_default_fields() {
	//phpcs:ignore
	$section = isset( $_GET['section'] ) ? $_GET['section'] : 'additional';
	//phpcs:ignore
	if ( isset( $_GET['reset_field'] ) && 'reset' === $_GET['reset_field'] ) {
		update_option( 'peachpay_field_editor_' . $section, null );

		if ( 'billing' === $section || 'shipping' === $section ) {
			update_option( 'peachpay_field_editor_' . $section, null );
			peachpay_reset_region_presets_default_fields( $section );
		}
		wp_safe_redirect( remove_query_arg( 'reset_field' ) );
		exit();
	}
}

/**
 * This method sets the billing and shipping presets fields according to the region.
 *
 * @param string $section The current field section key.
 */
function peachpay_reset_region_presets_default_fields( $section ) {

	if ( 'additional' === $section ) {
		return;
	}

	$default_field_name_keys = array(
		'email',
		'phone',
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'postcode',
		'city',
		'state',
		'country',
	);

	$default_field_label = array(
		'Email',
		'Phone number',
		'First name',
		'Last name',
		'Company name',
		'Street address',
		'Apartment',
		'Postal code',
		'City',
		'State/Province',
		'Country',
	);

	$japanese_field_name_keys = array(
		'email',
		'phone',
		'last_name',
		'first_name',
		'company',
		'country',
		'postal',
		'state',
		'city',
		'address_1',
		'address_2',
	);

	$japanese_field_label = array(
		'Email',
		'Phone number',
		'Last name',
		'First name',
		'Company name',
		'Country',
		'Postal code',
		'State/Province',
		'City',
		'Street address',
		'Apartment',
	);

	$region = peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language' );
	if ( 'ja' !== $region ) {
		peachpay_reset_presets_default_fields( $section, $default_field_name_keys, $default_field_label );
	} else {
		peachpay_reset_presets_default_fields( $section, $japanese_field_name_keys, $japanese_field_label );
	}
}

/**
 * This method sets the billing and shipping presets fields according
 *
 * @param string $section The target section for the settings.
 * @param array  $keys the key names.
 * @param array  $label the label for the fields.
 */
function peachpay_reset_presets_default_fields( $section, array $keys, array $label ) {
	$min = min( count( $keys ), count( $label ) );
	if ( empty( get_option( 'peachpay_field_editor_' . $section ) ) ) {
		$field_preset                        = get_option( 'peachpay_field_editor_' . $section );
		$field_preset                        = array();
		$field_preset[ $section ]            = array();
		$field_preset[ $section . '_order' ] = array();
		$field_preset['next_index']          = 1;
		update_option( 'peachpay_field_editor_' . $section, $field_preset );
	}
	for ( $i = 0; $i < $min; $i++ ) {
		$next_index = $field_preset['next_index'];
		$field      = array();
		if ( 'state' !== $keys[ $i ] && 'country' !== $keys[ $i ]
		&& 'email' !== $keys[ $i ] && 'phone' !== $keys[ $i ] ) {
			$field['type_list'] = 'text';
		} else {
			if ( 'state' === $keys[ $i ] ) {
				$field['type_list'] = 'state';
			}
			if ( 'country' === $keys[ $i ] ) {
				$field['type_list'] = 'country';
			}
			if ( 'email' === $keys[ $i ] ) {
				$field['type_list'] = 'email';
			}
			if ( 'phone' === $keys[ $i ] ) {
				$field['type_list'] = 'tel';
			}
		}
		$field['field_name']    = $section . '_' . $keys[ $i ];
		$field['field_label']   = $label[ $i ];
		$field['field_default'] = '';
		if ( 'company' === $keys[ $i ]
		|| 'address_2' === $keys[ $i ] ) {
			$field['field_required'] = '';
		} else {
			$field['field_required'] = 'yes';
		}
		if ( 'company' === $keys[ $i ] ) {
			$field['field_enable'] = '';
		} else {
			$field['field_enable'] = 'yes';
		}
		if ( 'address_1' !== $keys[ $i ] || 'address_2' !== $keys[ $i ] && 'company' !== $keys[ $i ] ) {
			$field['width'] = 50;
		}
		if ( 'company' === $keys[ $i ] ) {
			$field['width'] = 100;
		}
		if ( 'address_1' === $keys[ $i ] ) {
			$field['width'] = 70;
		}
		if ( 'address_2' === $keys[ $i ] ) {
			$field['width'] = 30;
		}
		$field_preset[ $section ][ $next_index ] = $field;
		$field_preset[ $section . '_order' ][]   = $next_index;
		$field_preset['next_index']++;
		update_option( 'peachpay_field_editor_' . $section, $field_preset );
	}
}

/**
 * A helper method that generate the field data in a JSON string format.
 *
 * @param array  $keys the field keys to loop over.
 * @param int    $current_index This is the current targeted row index.
 * @param string $section The current field section key.
 */
function peachpay_generate_field_data_json( array $keys, int $current_index, $section ) {
	if ( empty( get_option( 'peachpay_field_editor_' . $section )[ $section ] ) ) {
		return;
	}
	$result  = '{';
	$result .= '"row":"' . $current_index . '",';
	$field   = get_option( 'peachpay_field_editor_' . $section );
	foreach ( $keys as $key ) {
		if ( isset( $field[ $section ][ $current_index ][ $key ] ) ) {
			$temp_value = str_ireplace( '"', '\"', $field[ $section ][ $current_index ][ $key ] );
			$temp_value = str_ireplace( "\'", "\\\'", $temp_value );
			$temp       = '"' . $key . '":"' . $temp_value . '",';
		}
		$result .= $temp;
	}
	if ( isset( $field[ $section ][ $current_index ]['option'] ) && ! empty( $field[ $section ][ $current_index ]['option'] ) ) {
		$result .= '"option":[';

		foreach ( $field[ $section ][ $current_index ]['option'] as $value => $name ) {
			$result .= '["' . $value . '","' . str_ireplace( "\\'", "\\\'", $name ) . '"],';
		}
		$result  = rtrim( $result, ', ' );
		$result .= ']';
	}
	$result  = rtrim( $result, ', ' );
	$result .= '}';

	// wp_json_encode() cannot be used because the option list has to be manipulated to work for an in order list items.
	return $result;
}

/**
 * A method to check if the field name already exists in the field options array.
 * Return the field name index if found else returns null.
 *
 * @param string $name the field name that is to be checked.
 * @param string $section The current field section key.
 */
function peachpay_field_name_exist( string $name, $section ) {
	$field = get_option( 'peachpay_field_editor_' . $section );
	if ( ! empty( get_option( 'peachpay_field_editor_' . $section )[ $section ] ) ) {
		foreach ( $field[ $section . '_order' ] as $order_num ) {
			if ( $field[ $section ][ $order_num ]['field_name'] === $name ) {
				return $order_num;
			}
		}
	}
	return null;
}

/**
 * A helper method that returns the order number index.
 *
 * @param int    $target the target number to find in the order list.
 * @param string $section The current field section key.
 */
function peachpay_get_order_index( $target, $section ) {
	$index = 0;
	$order = get_option( 'peachpay_field_editor_' . $section );
	foreach ( $order[ $section . '_order' ] as $current_key ) {
		if ( (int) $current_key === (int) $target ) {
			return $index;
		}
		$index ++;
	}
	return $index;
}
