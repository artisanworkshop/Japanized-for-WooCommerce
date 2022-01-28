<?php
/**
 * Implements the PeachPay checkout window field editor.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'enqueue_field_editor_style' );
add_action( 'admin_enqueue_scripts', 'enqueue_field_editor_script' );

/**
 * Enqueues admin.css
 */
function enqueue_field_editor_style() {
	wp_enqueue_style(
		'field-editor.css',
		plugin_dir_url( __FILE__ ) . 'assets/field-editor.css',
		array(),
		true
	);
}

/**
 * Enqueues field-editor.js to the modal.
 */
function enqueue_field_editor_script() {
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
		'peachpay_section_field_editor',
		__( 'Checkout field editor', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_section_field_editor',
		__( 'Enable field editor', 'peachpay-for-woocommerce' ),
		'peachpay_field_editor_enable_cb',
		'peachpay',
		'peachpay_section_field_editor',
		array( 'label_for' => 'peachpay_enable_field_editor' )
	);

	add_settings_section(
		'peachpay_checkout_field_editor',
		__( 'Additional fields', 'peachpay-for-woocommerce' ),
		'peachpay_generate_table_cb',
		'peachpay',
		array( 'label_for' => 'peachpay_checkout_field_editor' )
	);
}

/**
 * Generates a checkbox for enable this module.
 */
function peachpay_field_editor_enable_cb() {
	?>
	<input
		id="peachpay_enable_show_additional_fields"
		name="peachpay_field_editor[enable_field_editor]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_field_editor', 'enable_field_editor' ), true ); ?>
	>
	<label for="peachpay_enable_show_additional_fields"><b><?php esc_attr_e( 'Show additional fields in the checkout window', 'peachpay-for-woocommerce' ); ?></b></label>
	<p class="description"><?php esc_attr_e( 'When enabled, the fields you add below will be displayed in the checkout window.', 'peachpay-for-woocommerce' ); ?></p>
	<?php
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
		// Keep for future implements.
		// 'field_display_email',
		// 'field_display_order_details',.
	);

	if ( empty( get_option( 'peachpay_field_editor' ) ) ) {
		update_option( 'peachpay_field_editor', array() );
	}

	//phpcs:disable
	if ( isset( $_POST['type_list'] ) && isset( $_POST['field_name'] ) ) {
		$temp_field = array();
		foreach ( $new_field_key as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$temp_field[ $key ] = $_POST[ $key ];
			}
		}
		if ( peachpay_field_name_exist( $_POST['field_name'] ) && ! empty( get_option( 'peachpay_field_editor' )['field'] ) ) {
			$index      = peachpay_field_name_exist( $_POST['field_name'] );
			$curent_row = isset( $_POST['edit-row'] ) ? $_POST['edit-row'] : null;
			peachpay_overlap_field( $temp_field, $index, $new_field_key, $curent_row );
		} elseif ( isset( $_POST['edit-row'] ) && ! peachpay_field_name_exist( $_POST['field_name'] ) ) {
			$field_option = get_option( 'peachpay_field_editor' );
			unset( $field_option['field'][ (int) $_POST['edit-row'] ] );
			unset( $field_option['order'][ peachpay_get_order_index( (int) $_POST['edit-row'] ) ] );
			update_option( 'peachpay_field_editor', $field_option );
			peachpay_add_new_field( $temp_field );
		} else {
			peachpay_add_new_field( $temp_field );
		}
	}
	//phpcs:enable

	?>
	<div class="table-form">
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
					peachpay_generate_body( $new_field_key );
				?>
			</tbody>
		</table>
	</div>
		<?php
}

/**
 * A helper function that generates the header and footer buttons.
 */
function peachpay_generate_buttons_headers_footer() {
	?>
		<tr id="table-buttons-header-footer">
			<td colspan="8" style="text-align: left;">
				<button class="button button-primary field-button" type="button" id="add-new-field">+ <?php esc_html_e( 'Add new field', 'peachpay-for-woocommerce' ); ?></button>
				<button class="button button-secondary remove-button" type="button" id="remove-field"><?php esc_html_e( 'Remove', 'peachpay-for-woocommerce' ); ?></button>
				<button class="button button-secondary enable-button" type="button" id="enable-field"><?php esc_html_e( 'Enable', 'peachpay-for-woocommerce' ); ?></button>
				<button class="button button-secondary disable-button" type="button" id="disable-field"><?php esc_html_e( 'Disable', 'peachpay-for-woocommerce' ); ?></button>
			</td>
			<td colspan="1">
				<a
				class="button button-secondary"
				onclick="return confirm('Are you sure would you like to reset all your changes made to the PeachPay cart fields?')"
				type="button" id="reset-fields" style="float: right;"
				href="
			<?php
			//phpcs:ignore
			echo add_query_arg( 'reset_field', 'reset' );
			peachpay_reset_default_fields();
			?>
				"
				><?php esc_html_e( 'Reset fields', 'peachpay-for-woocommerce' ); ?></a>
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
			<th class="sort"></th>
			<th class="select-all-collum">
				<input type="checkbox" class="select-all">
			</th>
			<th><?php esc_html_e( 'Name', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Type', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Label', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Default value', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Required', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Enable', 'peachpay-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Edit', 'peachpay-for-woocommerce' ); ?></th>
		</tr>
	<?php
}

/**
 * A helper function that generates the table body.
 *
 * @param array $field_keys the field keys array for array indexing as well as accessing the field.
 */
function peachpay_generate_body( array $field_keys ) {
	if ( ! empty( get_option( 'peachpay_field_editor' )['field'] ) ) {

		$field_option = get_option( 'peachpay_field_editor' );
		?>

		<?php
		foreach ( $field_option['order'] as $order_number ) {
			?>
			<tr class="row_<?php echo esc_html( $order_number ); ?> <?php echo ( ! isset( $field_option['field'][ $order_number ]['field_enable'] ) || '' === $field_option['field'][ $order_number ]['field_enable'] ) ? 'row-disabled' : ''; ?>" draggable="true" >
				<td class="sort">
				<i class="dragable-icon" aria-hidden="true"></i>
				<?php
				foreach ( $field_keys as $key ) {
					?>
					<input type="hidden"
						name="peachpay_field_editor[field][<?php echo esc_html( $order_number ); ?>][<?php echo esc_html( $key ); ?>]"
						class="field_<?php echo esc_html( $order_number ); ?>"
						value="<?php echo isset( $field_option['field'][ $order_number ][ $key ] ) ? esc_html( $field_option['field'][ $order_number ][ $key ] ) : ''; ?>"
						id ="<?php echo esc_html( $key . $order_number ); ?>"
					/>
					<?php
				}
				?>
					<input type="hidden" class="field_<?php echo esc_html( $order_number ); ?>" name="peachpay_field_editor[order][]" value="<?php echo esc_html( $order_number ); ?>" id =" order<?php echo esc_html( $order_number ); ?>" >
					<input type="hidden" class="field_<?php echo esc_html( $order_number ); ?>" name="peachpay_field_editor[next_index]" value="<?php echo esc_html( $field_option['next_index'] ); ?>" id ="next_index<?php echo esc_html( $order_number ); ?>" />
					<input type="hidden" class="field-data" id="field-data_<?php echo esc_html( $order_number ); ?>" value="<?php echo esc_html( htmlspecialchars( peachpay_generate_field_data_json( $field_keys, $order_number ) ) ); ?>" />
				</td>
				<td>
					<input class="checkbox" type="checkbox" name="select_field" value="<?php echo esc_html( $order_number ); ?>" id="<?php echo esc_html( $order_number ); ?>">
				</td>
				<td> <?php echo esc_html( $field_option['field'][ $order_number ]['field_name'] ); ?> </td>
				<td> <?php echo esc_html( $field_option['field'][ $order_number ]['type_list'] ); ?> </td>
				<td> <?php echo esc_html( $field_option['field'][ $order_number ]['field_label'] ); ?></td>
				<td> <?php echo esc_html( $field_option['field'][ $order_number ]['field_default'] ); ?> </td>
				<td> <?php echo ( isset( $field_option['field'][ $order_number ]['field_required'] ) && 'yes' === $field_option['field'][ $order_number ]['field_required'] ) ? '&#10003;' : '-'; ?> </td>
				<td class="th_field_enable" id ="field_<?php echo esc_html( $order_number ); ?>"> <?php echo ( isset( $field_option['field'][ $order_number ]['field_enable'] ) && 'yes' === $field_option['field'][ $order_number ]['field_enable'] ) ? '&#10003;' : '-'; ?> </td>
				<td>
					<button class="button button-secondary edit-field" type="button" id="edit-field<?php echo esc_html( $order_number ); ?>" value="field-data_<?php echo esc_html( $order_number ); ?>" ><?php esc_html_e( 'Edit', 'peachpay-for-woocommerce' ); ?></button>
				</td>
			</tr>
				<?php
		}
	}
}

/**
 * This method opens a model and adds a new field to the form and table.
 *
 * @param array $field This is the new field data that is to be added to the array data.
 */
function peachpay_add_new_field( array $field ) {
	if ( empty( get_option( 'peachpay_field_editor' )['field'] ) ) {
		$field_option               = get_option( 'peachpay_field_editor' );
		$field_option['field']      = array();
		$field_option['order']      = array();
		$field_option['next_index'] = 1;
		update_option( 'peachpay_field_editor', $field_option );
	}
	$field_option                         = get_option( 'peachpay_field_editor' );
	$next_index                           = $field_option['next_index'];
	$field_option['field'][ $next_index ] = $field;
	$field_option['order'][]              = $next_index;
	$field_option['next_index']++;
	update_option( 'peachpay_field_editor', $field_option );
}

	/**
	 * This method updates just the current field data when field name does not exist.
	 *
	 * @param array $field the field data that is to be edited.
	 * @param array $keys the field data keys.
	 * @param int   $current_row the field row.
	 */
function peachpay_update_field_data( array $field, array $keys, int $current_row ) {
	$field_option = get_option( 'peachpay_field_editor' );
	foreach ( $keys as $key ) {
		$field_option['field'][ $current_row ][ $key ] = $field[ $key ];
	}
	update_option( 'peachpay_field_editor', $field_option );
}

/**
 * This method is use to edit the current field.
 *
 * @param array $field the field data that is to be edited.
 * @param int   $index the field that is to be edited.
 * @param array $keys the field data keys.
 * @param int   $current_row the field row.
 */
function peachpay_overlap_field( array $field, int $index, array $keys, $current_row ) {
	$field_option = get_option( 'peachpay_field_editor' );
	foreach ( $keys as $key ) {
		if ( ! isset( $field[ $key ] ) ) {
			unset( $field_option['field'][ $index ][ $key ] );
		} else {
			$field_option['field'][ $index ][ $key ] = $field[ $key ];
		}
	}

	if ( peachpay_field_name_exist( $field['field_name'] ) && $index !== (int) $current_row && null !== $current_row ) {
		unset( $field_option['field'][ $current_row ] );
		unset( $field_option['order'][ peachpay_get_order_index( $current_row ) ] );
	}
	update_option( 'peachpay_field_editor', $field_option );
}

/**
 * This method resets the additional fields data as well as the table content.
 */
function peachpay_reset_default_fields() {
	//phpcs:ignore
	if ( isset( $_GET['reset_field'] ) && 'reset' === $_GET['reset_field'] ) {
		$temp_option = get_option( 'peachpay_field_editor' );
		unset( $temp_option['field'] );
		unset( $temp_option['order'] );
		unset( $temp_option['next_index'] );
		update_option( 'peachpay_field_editor', $temp_option );
		wp_safe_redirect( remove_query_arg( 'reset_field' ) );
		exit();
	}
}

/**
 * A helper method that generate the field data in a JSON string format.
 *
 * @param array $keys the field keys to loop over.
 * @param int   $current_index This is the current targeted row index.
 */
function peachpay_generate_field_data_json( array $keys, int $current_index ) {
	if ( empty( get_option( 'peachpay_field_editor' )['field'] ) ) {
		return;
	}
	$result  = '{';
	$result .= '"row":"' . $current_index . '",';
	$field   = get_option( 'peachpay_field_editor' );
	foreach ( $keys as $key ) {
		if ( isset( $field['field'][ $current_index ][ $key ] ) ) {
			$temp = '"' . $key . '":"' . $field['field'][ $current_index ][ $key ] . '",';
		}
		$result .= $temp;
	}
	$result  = rtrim( $result, ', ' );
	$result .= '}';
	return $result;
}

	/**
	 * A method to check if the field name already exists in the field options array.
	 * Return the field name index if found else returns null.
	 *
	 * @param string $name the field name that is to be checked.
	 */
function peachpay_field_name_exist( string $name ) {
	$field = get_option( 'peachpay_field_editor' );
	if ( ! empty( get_option( 'peachpay_field_editor' )['field'] ) ) {
		foreach ( $field['order'] as $order_num ) {
			if ( $field['field'][ $order_num ]['field_name'] === $name ) {
				return $order_num;
			}
		}
	}
	return null;
}

	/**
	 * A helper method that returns the order number index.
	 *
	 * @param int $target the target number to find in the order list.
	 */
function peachpay_get_order_index( $target ) {
	$index = 0;
	$order = get_option( 'peachpay_field_editor' );
	foreach ( $order['order'] as $current_key ) {
		if ( (int) $current_key === (int) $target ) {
			return $index;
		}
		$index ++;
	}
	return $index;
}
