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
function peachpay_settings_advanced() {
	peachpay_settings_advanced_main();
}

/**
 * Registers the advanced settings options.
 */
function peachpay_settings_advanced_main() {

	add_settings_section(
		'peachpay_section_advanced_general',
		__( 'General', 'woocommerce-for-japan' ),
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_advanced_disclaimer',
		'',
		'peachpay_field_advanced_disclaimer_cb',
		'peachpay',
		'peachpay_section_advanced_general',
		array( 'label_for' => 'peachpay_custom_button_class' )
	);

	add_settings_section(
		'peachpay_section_custom_css',
		__( 'Custom CSS', 'woocommerce-for-japan' ),
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_custom_button_class',
		__( 'Button classes', 'woocommerce-for-japan' ),
		'peachpay_field_button_class_cb',
		'peachpay',
		'peachpay_section_custom_css',
		array( 'label_for' => 'peachpay_custom_button_class' )
	);

	add_settings_field(
		'peachpay_custom_button_css',
		__( 'Button CSS', 'woocommerce-for-japan' ),
		'peachpay_field_button_css_cb',
		'peachpay',
		'peachpay_section_custom_css',
		array( 'label_for' => 'peachpay_custom_button_css' )
	);

	add_settings_section(
		'peachpay_section_custom_js',
		__( 'Custom JS', 'woocommerce-for-japan' ),
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_custom_checkout_js',
		__( 'Checkout JS', 'woocommerce-for-japan' ),
		'peachpay_field_checkout_js_cb',
		'peachpay',
		'peachpay_section_custom_js',
		array( 'label_for' => 'peachpay_custom_checkout_js' )
	);
}

/**
 * Renders the advanced settings disclaimer.
 */
function peachpay_field_advanced_disclaimer_cb() {
	?>
	<p>
		<?php esc_html_e( 'These settings are provided for advanced customization by developers or merchants. Support is not guaranteed and it is expected that you know what your are doing by editing the below settings.', 'woocommerce-for-japan' ); ?>
	</p>
	<?php
}

/**
 * Renders custom button classes setting.
 */
function peachpay_field_button_class_cb() {
	?>
	<label>
		<input
			id="peachpay_custom_button_class"
			type="text"
			name="peachpay_advanced_options[custom_button_class]"
			style="width: 300px"
			spellcheck="false"
			value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_advanced_options', 'custom_button_class', '' ) ); ?>"
		>
		<p class="description">
			<?php esc_html_e( 'This setting will add additional CSS classes to the PeachPay button.', 'woocommerce-for-japan' ); ?>
		</p>
	</label>
	<?php
}

/**
 * Renders custom button CSS text area.
 */
function peachpay_field_button_css_cb() {
	?>
	<label>
		<textarea
			id="peachpay_custom_button_css"
			name="peachpay_advanced_options[custom_button_css]"
			style="width: 400px; min-height: 200px;"
			spellcheck="false"><?php echo esc_attr( peachpay_get_settings_option( 'peachpay_advanced_options', 'custom_button_css', '' ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'This setting allows styling of the PeachPay button through additional CSS.', 'woocommerce-for-japan' ); ?></p>
	</label>
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
		name="peachpay_advanced_options[custom_checkout_js]"
		style="width: 400px; min-height: 200px;"
		placeholder="<script>
// Custom script element here
</script>"><?php echo esc_attr( peachpay_get_settings_option( 'peachpay_advanced_options', 'custom_checkout_js' ) ); ?></textarea>
	<p class="description"><?php esc_html_e( 'This setting will append any provided elements to the PeachPay checkout window.', 'woocommerce-for-japan' ); ?></p>
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
