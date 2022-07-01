<?php
/**
 * PeachPay General Settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Calls the functions that implement the subsections under General preferences.
 */
function peachpay_settings_general() {
	peachpay_settings_general_main();
	peachpay_general_section_product();
	peachpay_general_section_field_editor();
}

/**
 * Registers general settings options.
 */
function peachpay_settings_general_main() {
	add_settings_section(
		'peachpay_section_general',
		'',
		'peachpay_feedback_cb',
		'peachpay'
	);

	// WordPress has magic interaction with the following keys: label_for, class.
	// - the "label_for" key value is used for the "for" attribute of the <label>.
	// - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	// Note: you can add custom key value pairs to be used inside your callbacks.

	add_settings_field(
		'peachpay_general_appearance_field',
		__( 'General', 'woocommerce-for-japan' ),
		'peachpay_general_appearance',
		'peachpay',
		'peachpay_section_general',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Render all general setting fields which includes:
 * language selection options, enableling order notes, support message setting HTML, data retention option.
 */
function peachpay_general_appearance() {
	$options = get_option( 'peachpay_general_options' );

	?>
	<div class="peachpay-setting-section">
		<div>
			<h3><?php esc_html_e( 'Language', 'woocommerce-for-japan' ); ?></h3>
			<select
				id="peachpay_language"
				name="peachpay_general_options[language]">
				<?php foreach ( LANGUAGE_TO_LOCALE as $language => $value ) { ?>
					<option
						value="<?php echo esc_attr( $value ); ?>"
						<?php echo isset( $options['language'] ) ? ( selected( $options['language'], $value, false ) ) : ( '' ); ?>
					>
						<?php echo esc_html( $language ); ?>
					</option>
				<?php } ?>
			</select>
			<p class="description"><?php esc_html_e( 'This will change the language on the button and in the checkout flow. Use the option', 'woocommerce-for-japan' ); ?>&nbsp<strong><?php esc_html_e( 'Detect from page', 'woocommerce-for-japan' ); ?></strong>&nbsp<?php esc_html_e( 'if you are using a language switcher plugin.', 'woocommerce-for-japan' ); ?></p>
		</div>
		<div>
			<h3><?php esc_html_e( 'Support message', 'woocommerce-for-japan' ); ?></h3>
			<?php peachpay_field_support_message_cb(); ?>
		</div>
		<?php
		peachpay_admin_input(
			'peachpay_enable_order_notes',
			'peachpay_general_options',
			'enable_order_notes',
			1,
			__( 'Enable order notes', 'woocommerce-for-japan' ),
			__( 'Allow customers to enter order notes inside the checkout window', 'woocommerce-for-japan' ),
			array( 'input_type' => 'checkbox' )
		);

		peachpay_admin_input(
			'peachpay_data_retention',
			'peachpay_general_options',
			'data_retention',
			1,
			__( 'Remove data on uninstall', 'woocommerce-for-japan' ),
			__( 'PeachPay settings and data will be removed if the plugin is uninstalled', 'woocommerce-for-japan' ),
			array( 'input_type' => 'checkbox' )
		);
		?>
		<div>
			<?php submit_button( '', 'pp-save-button' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the Support message setting HTML.
 */
function peachpay_field_support_message_cb() {
	?>
	<textarea id="peachpay-support-message" name="peachpay_general_options[support_message]" style="width: 400px; min-height: 200px;"><?php echo esc_html( peachpay_get_settings_option( 'peachpay_general_options', 'support_message' ) ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Display a support message within the PeachPay checkout window for customers', 'woocommerce-for-japan' ); ?>
	</p>
	<?php
}

/**
 * Adds the fields for toggling product images and quantiy changer
 */
function peachpay_general_section_product() {
	add_settings_section(
		'peachpay_section_product',
		'',
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_order_summary_section',
		__( 'Order summary', 'woocommerce-for-japan' ),
		'peachpay_order_summary',
		'peachpay',
		'peachpay_section_product',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Render order summary settings fields which includes:
 * Hiding product images, disabling in modal quantity changer.
 */
function peachpay_order_summary() {
	?>
	<div class="peachpay-setting-section">
		<?php
		peachpay_admin_input(
			'peachpay_product_images',
			'peachpay_general_options',
			'display_product_images',
			1,
			__( 'Display product images', 'woocommerce-for-japan' ),
			__( 'Display product images in the checkout window', 'woocommerce-for-japan' ),
			array( 'input_type' => 'checkbox' )
		);

		peachpay_admin_input(
			'peachpay_quantity_toggle',
			'peachpay_general_options',
			'enable_quantity_changer',
			1,
			__( 'Enable quantity changer', 'woocommerce-for-japan' ),
			__( 'Display the quantity changer next to items in the checkout window order summary', 'woocommerce-for-japan' ),
			array( 'input_type' => 'checkbox' )
		);
		?>
		<div>
			<?php submit_button( 'Save changes', 'pp-save-button' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Adds the fields for general checkout field settings.
 */
function peachpay_general_section_field_editor() {
	add_settings_section(
		'peachpay_section_field',
		'',
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_editor_section',
		__( 'Checkout fields', 'woocommerce-for-japan' ),
		'peachpay_field_editor_general',
		'peachpay',
		'peachpay_section_field',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Render checkout field settings.
 */
function peachpay_field_editor_general() {
	?>
	<div class="peachpay-setting-section">
		<?php
		peachpay_admin_input(
			'peachpay_enable_preset_virtual_product_fields',
			'peachpay_general_options',
			'enable_virtual_product_fields',
			1,
			__( 'Hide the shipping/billing fields for virtual products', 'woocommerce-for-japan' ),
			__( 'If the cart only consists of virtual products, don\'t show the shipping/billing address fields.', 'woocommerce-for-japan' ),
			array( 'input_type' => 'checkbox' )
		);

		peachpay_admin_input(
			'peachpay_address_autocomplete',
			'peachpay_general_options',
			'address_autocomplete',
			1,
			__( 'Google Maps address autocomplete', 'woocommerce-for-japan' ),
			__( 'When a shopper starts typing the street address, they will get autocomplete suggestions', 'woocommerce-for-japan' ),
			array( 'input_type' => 'checkbox' )
		);
		?>
		<div>
			<?php submit_button( 'Save changes', 'pp-save-button' ); ?>
		</div>
	</div>
	<?php
}
