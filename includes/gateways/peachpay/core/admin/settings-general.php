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
}

/**
 * Registers general settings options.
 */
function peachpay_settings_general_main() {
	add_settings_section(
		'peachpay_section_general',
		__( 'General', 'peachpay-for-woocommerce' ),
		'peachpay_section_general_cb',
		'peachpay'
	);

	// WordPress has magic interaction with the following keys: label_for, class.
	// - the "label_for" key value is used for the "for" attribute of the <label>.
	// - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	// Note: you can add custom key value pairs to be used inside your callbacks.

	add_settings_field(
		'peachpay_field_language',
		__( 'Language', 'peachpay-for-woocommerce' ),
		'peachpay_field_language_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_language' )
	);

	add_settings_field(
		'peachpay_field_enable_coupons',
		__( 'Coupons', 'peachpay-for-woocommerce' ),
		'peachpay_field_enable_coupons_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_enable_coupons' )
	);

	add_settings_field(
		'peachpay_field_enable_order_notes',
		__( 'Order notes', 'peachpay-for-woocommerce' ),
		'peachpay_field_enable_order_notes_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_enable_order_notes' )
	);

	add_settings_field(
		'peachpay_field_test_mode',
		__( 'Test mode', 'peachpay-for-woocommerce' ),
		'peachpay_field_test_mode_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_test_mode' )
	);

	add_settings_field(
		'peachpay_field_data_retention',
		__( 'Data retention', 'peachpay-for-woocommerce' ),
		'peachpay_field_data_retention_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_test_mode' )
	);
}

/**
 * Renders the heading for the general settings section.
 */
function peachpay_section_general_cb() {
	?>
	<div class="elfsight-app-8ffcad85-9a1d-4fdf-a2d2-1ce2ec48b81c"></div>
	<?php
}

/**
 * Renders language selection options.
 */
function peachpay_field_language_cb() {
	$options = get_option( 'peachpay_general_options' );
	?>
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
	<p class="description"><?php esc_html_e( 'This will change the language on the button and in the checkout flow. Use the option', 'peachpay-for-woocommerce' ); ?>&nbsp<strong><?php esc_html_e( 'Detect from page', 'peachpay-for-woocommerce' ); ?></strong>&nbsp<?php esc_html_e( 'if you are using a language switcher plugin.', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Renders the enable coupon input option.
 */
function peachpay_field_enable_coupons_cb() {
	?>
	<input
		id="peachpay_enable_coupons"
		name="peachpay_general_options[enable_coupons]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'enable_coupons' ), true ); ?>
	>
	<label for="peachpay_enable_coupons"><?php esc_html_e( 'Enable coupons', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description"><?php esc_html_e( 'Allow customers to enter coupon codes inside the checkout window', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/** A plugin function for enableling order notes  */
function peachpay_field_enable_order_notes_cb() {
	?>
	<input
		id="peachpay_enable_order_notes"
		name="peachpay_general_options[enable_order_notes]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'enable_order_notes' ), true ); ?>
	>
	<label for="peachpay_enable_order_notes"><?php esc_html_e( 'Enable order notes', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description"><?php esc_html_e( 'Allow customers to enter order notes inside the checkout window', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Renders the test mode option.
 */
function peachpay_field_test_mode_cb() {
	?>
	<input
		id="peachpay_test_mode"
		name="peachpay_general_options[test_mode]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'test_mode' ), true ); ?>
	>
	<label for="peachpay_test_mode"><?php esc_html_e( 'Enable test mode', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description">
		<?php esc_html_e( 'Make test payments with or without a connected payment method.', 'peachpay-for-woocommerce' ); ?>
	</p>
	<p class="description">
		<?php esc_html_e( 'For Stripe, use card number:', 'peachpay-for-woocommerce' ); ?>&nbsp<b>4242 4242 4242 4242</b>,&nbsp
		<?php esc_html_e( 'with expiration:', 'peachpay-for-woocommerce' ); ?>&nbsp<b>04/24</b> <?php esc_html_e( 'and CVC:', 'peachpay-for-woocommerce' ); ?>&nbsp<b>444</b>.&nbsp
		<?php esc_html_e( 'For PayPal, see', 'peachpay-for-woocommerce' ); ?>&nbsp<a target="_blank" href="https://docs.peachpay.app/paypal#test-mode"><?php esc_html_e( 'these instructions.', 'peachpay-for-woocommerce' ); ?></a>
	</p>
	<?php
}

/**
 * Renders the data retention option.
 */
function peachpay_field_data_retention_cb() {
	?>
	<input
		id="peachpay_data_retention"
		name="peachpay_general_options[data_retention]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'data_retention' ), true ); ?>
	>
	<label for="peachpay_data_retention"><?php esc_html_e( 'Remove data on uninstall', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description"><?php esc_html_e( 'PeachPay settings and data will be removed if the plugin is uninstalled', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Adds the fields for Upsell and Cross-sell feature
 */
function peachpay_general_section_product() {
	add_settings_section(
		'peachpay_section_product',
		__( 'Products', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_hide_product_images',
		__( 'Product images', 'peachpay-for-woocommerce' ),
		'peachpay_hide_product_images_cb',
		'peachpay',
		'peachpay_section_product',
		array( 'label_for' => 'peachpay_product_images' )
	);

	add_settings_field(
		'peachpay_field_upsell',
		__( 'Upsell items', 'peachpay-for-woocommerce' ),
		'peachpay_hide_upsell_cb',
		'peachpay',
		'peachpay_section_product',
		array( 'label_for' => 'woocommerce_products_upsell' )
	);

	add_settings_field(
		'peachpay_field_cross_sell',
		__( 'Cross-sell items', 'peachpay-for-woocommerce' ),
		'peachpay_hide_cross_sell_cb',
		'peachpay',
		'peachpay_section_product',
		array( 'label_for' => 'woocommerce_products_cross_sell' )
	);
}

/**
 * Hide product images setting description.
 */
function peachpay_hide_product_images_cb() {
	?>
	<input
		id="peachpay_product_images"
		name="peachpay_general_options[hide_product_images]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'hide_product_images' ), true ); ?>
	>
	<label for="peachpay_product_images"><?php esc_html_e( 'Hide product images', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description"><?php esc_html_e( "Don't show product images in the checkout window", 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Callback for toggling upsell feature
 */
function peachpay_hide_upsell_cb() {
	?>
	<input
		id="woocommerce_products_upsell"
		name="peachpay_general_options[hide_woocommerce_products_upsell]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_upsell' ), true ); ?>
	>
	<label for='woocommerce_products_upsell'><?php esc_html_e( 'Hide upsell items', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description"><?php esc_html_e( 'Upsells on your products will not be displayed in the checkout window', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Callback for toggling cross-sell feature
 */
function peachpay_hide_cross_sell_cb() {
	?>
	<input
		id="woocommerce_products_cross_sell"
		name="peachpay_general_options[hide_woocommerce_products_cross_sell]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_cross_sell' ), true ); ?>
	>
	<label for='woocommerce_products_cross_sell'><?php esc_html_e( 'Hide cross-sell items', 'peachpay-for-woocommerce' ); ?></label>
	<p class="description"><?php esc_html_e( 'Cross-sells on your products will not be displayed in the checkout window', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}
