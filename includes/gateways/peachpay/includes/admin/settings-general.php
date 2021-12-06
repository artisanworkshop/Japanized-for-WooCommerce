<?php

/**
 * Calls the functions that implement the subsections under General preferences.
 */
function peachpay_settings_general() {
	peachpay_settings_general_main();
	peachpay_general_section_product();
}

function peachpay_settings_general_main() {
	add_settings_section(
		'peachpay_section_general',
		__( 'General', 'woocommerce-for-japan' ),
		'peachpay_section_general_cb',
		'peachpay'
	);

	// WordPress has magic interaction with the following keys: label_for, class.
	// - the "label_for" key value is used for the "for" attribute of the <label>.
	// - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	// Note: you can add custom key value pairs to be used inside your callbacks.

	add_settings_field(
		'peachpay_field_language',
		__( 'Language', 'woocommerce-for-japan' ),
		'peachpay_field_language_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_language' )
	);

	add_settings_field(
		'peachpay_field_enable_coupons',
		__( 'Coupons', 'woocommerce-for-japan' ),
		'peachpay_field_enable_coupons_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_enable_coupons' )
	);

	add_settings_field(
		'peachpay_field_enable_order_notes',
		__( 'Order notes', 'woocommerce-for-japan' ),
		'peachpay_field_enable_order_notes_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_enable_order_notes' )
	);

	add_settings_field(
		'peachpay_field_test_mode',
		__( 'Test mode', 'woocommerce-for-japan' ),
		'peachpay_field_test_mode_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_test_mode' )
	);

	add_settings_field(
		'peachpay_hide_product_images',
		__( 'Product images', 'woocommerce-for-japan' ),
		'peachpay_hide_product_images_cb',
		'peachpay',
		'peachpay_section_general',
		array( 'label_for' => 'peachpay_product_images' )
	);
}

function peachpay_hide_product_images_cb() {
	?>
	<input
		id="peachpay_product_images"
		name="peachpay_general_options[hide_product_images]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'hide_product_images' ), true ); ?>
	>
	<label for="peachpay_product_images"><?php _e( 'Hide product images', 'woocommerce-for-japan' ); ?></label>
	<p class="description"><?php _e( "Don't show product images in the checkout window", 'woocommerce-for-japan' ); ?></p>
	<?php
}

function peachpay_section_general_cb() {
	?>
	<div class="elfsight-app-8ffcad85-9a1d-4fdf-a2d2-1ce2ec48b81c"></div>
	<?php
}

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
	<p class="description"><?php _e( 'This will change the language on the button and in the checkout flow. Use the option', 'woocommerce-for-japan' ); ?>&nbsp<strong><?php _e( 'Detect from page', 'woocommerce-for-japan' ); ?></strong>&nbsp<?php _e( 'if you are using a language switcher plugin.', 'woocommerce-for-japan' ); ?></p>
	<?php
}

function peachpay_field_enable_coupons_cb() {
	?>
	<input
		id="peachpay_enable_coupons"
		name="peachpay_general_options[enable_coupons]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'enable_coupons' ), true ); ?>
	>
	<label for="peachpay_enable_coupons"><?php _e( 'Enable coupons', 'woocommerce-for-japan' ); ?></label>
	<p class="description"><?php _e( 'Allow customers to enter coupon codes inside the checkout window', 'woocommerce-for-japan' ); ?></p>
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
	<label for="peachpay_enable_order_notes"><?php _e( 'Enable order notes', 'woocommerce-for-japan' ); ?></label>
	<p class="description"><?php _e( 'Allow customers to enter order notes inside the checkout window', 'woocommerce-for-japan' ); ?></p>
	<?php
}

function peachpay_field_test_mode_cb() {
	?>
	<input
		id="peachpay_test_mode"
		name="peachpay_general_options[test_mode]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_general_options', 'test_mode' ), true ); ?>
	>
	<label for="peachpay_test_mode"><?php _e( 'Enable test mode', 'woocommerce-for-japan' ); ?></label>
	<p class="description">
		<?php _e( 'Make test payments with or without a connected payment method.', 'woocommerce-for-japan' ); ?>
	</p>
	<p class="description">
		<?php _e( 'For Stripe, use card number:', 'woocommerce-for-japan' ); ?>&nbsp<b>4242 4242 4242 4242</b>,&nbsp
		<?php _e( 'with expiration:', 'woocommerce-for-japan' ); ?>&nbsp<b>04/24</b> <?php _e( 'and CVC:', 'woocommerce-for-japan' ); ?>&nbsp<b>444</b>.&nbsp
		<?php _e( 'For PayPal, see', 'woocommerce-for-japan' ); ?>&nbsp<a target="_blank" href="https://docs.peachpay.app/paypal#test-mode"><?php _e( 'these instructions.', 'woocommerce-for-japan' ); ?></a>
	</p>
	<?php
}

/**
 * Adds the fields for Upsell and Cross-sell (soon) feature
 */
function peachpay_general_section_product() {
	add_settings_section(
		'peachpay_section_product',
		__( 'Products', 'woocommerce-for-japan' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_upsell',
		__( 'Upsell items', 'woocommerce-for-japan' ),
		'peachpay_hide_upsell_cb',
		'peachpay',
		'peachpay_section_product',
		array( 'label_for' => 'woocommerce_products_upsell' )
	);

	add_settings_field(
		'peachpay_field_cross_sell',
		__( 'Cross-sell items', 'woocommerce-for-japan' ),
		'peachpay_hide_cross_sell_cb',
		'peachpay',
		'peachpay_section_product',
		array( 'label_for' => 'woocommerce_products_cross_sell' )
	);
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
	<label for='woocommerce_products_upsell'><?php _e( 'Hide upsell items in checkout window in product page', 'woocommerce-for-japan' ); ?></label>
	<p class="description"><?php _e( 'Only simple upsell products will be displayed in checkout window', 'woocommerce-for-japan' ); ?></p>
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
	<label for='woocommerce_products_cross_sell'><?php _e( 'Hide cross-sell items in checkout window in cart page', 'woocommerce-for-japan');?></label>
	<p class="description"><?php _e( 'Only simple cross-sell products will be displayed in checkout window', 'woocommerce-for-japan');?></p>
	<?php
}
