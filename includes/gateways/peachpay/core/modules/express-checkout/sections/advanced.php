<?php
/**
 * PeachPay Advanced Settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Calls the functions that implement the subsections under Advanced Settings.
 */
function peachpay_express_checkout_advanced_render() {
	peachpay_settings_advanced_main();
}

/**
 * Registers the advanced settings options.
 */
function peachpay_settings_advanced_main() {

	add_settings_section(
		'peachpay_advanced_disclaimer',
		'',
		'peachpay_field_advanced_disclaimer_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_custom_button_class',
		__( 'Custom CSS', 'peachpay-for-woocommerce' ),
		'peachpay_custom_css_section',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_custom_checkout_js',
		__( 'Custom JS', 'peachpay-for-woocommerce' ),
		'peachpay_custom_js_section',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_custom_button_placement',
		__( 'Button location', 'peachpay-for-woocommerce' ),
		'peachpay_custom_button_placement',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Renders the advanced settings disclaimer.
 */
function peachpay_field_advanced_disclaimer_cb() {
	?>
	<div>
		<div style="display:flex; justify-content:space-between;">
			<h1>General</h1>
			<?php echo wp_kses_post( peachpay_build_video_help_section( 'https://youtu.be/1VTk9Vln1do' ) ); ?>
		</div>
		<p>
			<?php esc_html_e( 'These settings are provided for advanced customization by developers or merchants. Support is not guaranteed and it is expected that you know what you are doing by editing the below settings.', 'peachpay-for-woocommerce' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Renders custom button classes setting.
 */
function peachpay_field_button_class_cb() {
	?>
	<input
		id="peachpay_custom_button_class"
		type="text"
		name="peachpay_express_checkout_advanced[custom_button_class]"
		style="width: 300px"
		spellcheck="false"
		value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_button_class', '' ) ); ?>"
	>
	<p class="description">
		<?php esc_html_e( 'This setting will add additional CSS classes to the PeachPay button.', 'peachpay-for-woocommerce' ); ?>
	</p>
	<?php
}

/**
 * Renders custom button CSS text area.
 */
function peachpay_field_button_css_cb() {
	?>
	<textarea
		id="peachpay_custom_button_css"
		name="peachpay_express_checkout_advanced[custom_button_css]"
		style="width: 400px; min-height: 200px;"
		spellcheck="false"><?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_button_css', '' ) ); ?></textarea>
	<p class="description"><?php esc_html_e( 'This setting allows styling of the PeachPay button through additional CSS.', 'peachpay-for-woocommerce' ); ?></p>
	<script>
		// Allow tabing within the text area.
		document.getElementById('peachpay_custom_button_css').addEventListener('keydown', function(e) {
		if (e.key == 'Tab') {
			e.preventDefault();
			var start = this.selectionStart;
			var end = this.selectionEnd;
			this.value = this.value.substring(0, start) +
			"\t" + this.value.substring(end);
			this.selectionStart = this.selectionEnd = start + 1;
		}
		});
	</script>
	<?php
}

/**
 * Renders custom checkout JS text area.
 */
function peachpay_field_checkout_js_cb() {
	?>
	<textarea
		id="peachpay_custom_checkout_js"
		name="peachpay_express_checkout_advanced[custom_checkout_js]"
		style="width: 400px; min-height: 200px;"
		placeholder="<script>
// Custom script element here
</script>"><?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_checkout_js' ) ); ?></textarea>
	<p class="description"><?php esc_html_e( 'This setting will append any provided elements to the PeachPay checkout window.', 'peachpay-for-woocommerce' ); ?></p>
	<script>
		document.getElementById('peachpay_custom_checkout_js').addEventListener('keydown', function(e) {
		if (e.key == 'Tab') {
			e.preventDefault();
			var start = this.selectionStart;
			var end = this.selectionEnd;
			this.value = this.value.substring(0, start) +
			"\t" + this.value.substring(end);
			this.selectionStart = this.selectionEnd = start + 1;
		}
		});
	</script>
	<?php
}

/**
 * Render the settings field for custom css section.
 */
function peachpay_custom_css_section() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<h4><?php esc_html_e( 'Button classes', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_field_button_class_cb(); ?>
		</div>
		<div>
			<h4><?php esc_html_e( 'Button CSS', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_field_button_css_cb(); ?>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Render the settings field custom js section.
 */
function peachpay_custom_js_section() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<h4><?php esc_html_e( 'Checkout JS', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_field_checkout_js_cb(); ?>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Render the custom button location section
 */
function peachpay_custom_button_placement() {
	?>
		<div class="peachpay-setting-section">
			<div>
				<p class="description"><?php echo esc_html_e( 'For each page, you can enter a CSS selector where the button should appear. If empty, it will appear in a default location.', 'peachpay-for-woocommerce' ); ?></p>
			</div>
			<?php
			peachpay_admin_input(
				'productPageTarget',
				'peachpay_express_checkout_advanced',
				'custom_target_product_page',
				peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_target_product_page', null ),
				'Product page',
				'',
				array(
					'placeholder' => esc_html( '#sampleTarget' ),
					'input_type'  => 'text',
				)
			);
			peachpay_admin_input(
				'cartPageTarget',
				'peachpay_express_checkout_advanced',
				'custom_target_cart_page',
				peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_target_cart_page', null ),
				'Cart page',
				'',
				array(
					'placeholder' => esc_html( '#sampleTarget' ),
					'input_type'  => 'text',
				)
			);
			peachpay_admin_input(
				'checkoutPageTarget',
				'peachpay_express_checkout_advanced',
				'custom_target_checkout_page',
				peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_target_checkout_page', null ),
				'Checkout page',
				'',
				array(
					'placeholder' => esc_html( '#sampleTarget' ),
					'input_type'  => 'text',
				)
			);
			?>
			<div class="pp-save-button-section">
				<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
			</div>
		</div>
	<?php
}
