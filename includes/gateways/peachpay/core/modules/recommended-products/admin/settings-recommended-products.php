<?php
/**
 * PeachPay Recommended Products Settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Generates the recommended products field settings
 */
function peachpay_related_products() {
	add_settings_section(
		'peachpay_related_products',
		'',
		'__return_true',
		'peachpay'
	);

	add_settings_field(
		'peachpay_rp_product_page_section',
		__( 'Recommended products', 'peachpay-for-woocommerce' ),
		'peachpay_rp_product_page_cb',
		'peachpay',
		'peachpay_related_products',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Render settings field for product page.
 */
function peachpay_rp_product_page_cb() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<p style="margin: 0px 0px 8px 0px;"><?php esc_html_e( 'These related products appear near the bottom of the product page, not inside the PeachPay checkout window', 'peachpay-for-woocommerce' ); ?></p>
		</div>
		<?php
		peachpay_admin_input(
			'peachpay_related_products_toggle',
			'peachpay_related_products_options',
			'peachpay_related_enable',
			1,
			__( 'Display related products in the product page', 'peachpay-for-woocommerce' ),
			__( 'Display random related products in a slider based on product category, tag, or attribute on every product page.', 'peachpay-for-woocommerce' ),
			array( 'input_type' => 'checkbox' )
		);
		peachpay_admin_input(
			'peachpay_related_slider_toggle',
			'peachpay_related_products_options',
			'peachpay_related_slider',
			1,
			__( 'Enable auto-slider', 'peachpay-for-woocommerce' ),
			__( 'Enable auto-slider (only available for product page)', 'peachpay-for-woocommerce' ),
			array(
				'input_type' => 'checkbox',
				'disabled'   => ! peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_enable' ),
			)
		);
		peachpay_admin_input(
			'peachpay_display_nproducts',
			'peachpay_related_products_options',
			'peachpay_related_nproducts',
			'',
			__( 'Number of products to display for product detail page', 'peachpay-for-woocommerce' ),
			'',
			array(
				'input_type'  => 'number',
				'placeholder' => __( 'ie 99', 'peachpay-for-woocommerce' ),
			)
		);
		peachpay_admin_input(
			'peachpay_related_products_title',
			'peachpay_related_products_options',
			'peachpay_related_title',
			'',
			__( 'Heading text', 'peachpay-for-woocommerce' ),
			__( 'Customize the headline text. Leaving it blank defaults it to “Related products” in your chosen language.', 'peachpay-for-woocommerce' ),
			array(
				'input_type'  => 'text',
				'placeholder' => __( 'Related products', 'peachpay-for-woocommerce' ),
			)
		);
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}
