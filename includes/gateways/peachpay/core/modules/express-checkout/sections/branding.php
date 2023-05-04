<?php
/**
 * PeachPay Express checkout / Branding settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Calls functions that add the settings fields to the express checkout / branding section.
 */
function peachpay_express_checkout_branding_render() {
	peachpay_general_section_merchant_logo();
	peachpay_branding_section_colors();
	peachpay_general_section_language();
}

/**
 * Adds the field for selecting the language
 */
function peachpay_general_section_language() {
	add_settings_field(
		'peachpay_language_section',
		peachpay_build_section_header( __( 'Language', 'peachpay-for-woocommerce' ) ),
		'peachpay_render_language_field',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Renders the language field.
 */
function peachpay_render_language_field() {
	$options = get_option( 'peachpay_express_checkout_branding' );
	?>
	<div class="peachpay-setting-section">
		<div>
			<select id="peachpay_language" name="peachpay_express_checkout_branding[language]">
				<?php foreach ( LANGUAGE_TO_LOCALE as $language => $value ) { ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php echo isset( $options['language'] ) ? ( selected( $options['language'], $value, false ) ) : ( '' ); ?>>
						<?php echo esc_html( $language ); ?>
					</option>
				<?php } ?>
			</select>
			<p class="description"><?php esc_html_e( 'This will change the language on the button and in the checkout flow. Use the option', 'peachpay-for-woocommerce' ); ?>&nbsp<strong><?php esc_html_e( 'Detect from page', 'peachpay-for-woocommerce' ); ?></strong>&nbsp<?php esc_html_e( 'if you are using a language switcher plugin.', 'peachpay-for-woocommerce' ); ?></p>
			<div class="pp-save-button-section">
				<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Adds the settings field for selecting the text/bg colors.
 */
function peachpay_branding_section_colors() {
	add_settings_field(
		'peachpay_brand_color_section',
		peachpay_build_section_header( __( 'Colors', 'peachpay-for-woocommerce' ) ),
		'peachpay_render_colors_field',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Renders the brand color and brand text color fields.
 */
function peachpay_render_colors_field() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<h4><?php esc_html_e( 'Button color', 'peachpay-for-woocommerce' ); ?></h4>
			<div id="peachpay_button_background_color" class="pp-merged-inputs">
				<div class="pp-color-input-container">
					<input name='peachpay_express_checkout_branding[button_color]' type='color' value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_color', PEACHPAY_DEFAULT_BACKGROUND_COLOR ) ); ?>' />
				</div>
				<input name='button_color_text' type='text' value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_color', PEACHPAY_DEFAULT_BACKGROUND_COLOR ) ); ?>' />
			</div>
			<p class="description"><?php esc_html_e( 'This will set the colors in the checkout window and for the checkout buttons.', 'peachpay-for-woocommerce' ); ?></p>
		</div>
		<div>
			<h4><?php esc_html_e( 'Button text color', 'peachpay-for-woocommerce' ); ?></h4>
			<div id="peachpay_button_text_color" class="pp-merged-inputs">
				<div class="pp-color-input-container">
					<input name='peachpay_express_checkout_branding[button_text_color]' type='color' value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_text_color', PEACHPAY_DEFAULT_TEXT_COLOR ) ); ?>' />
				</div>
				<input name='button_text_color_text' type='text' value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_text_color', PEACHPAY_DEFAULT_TEXT_COLOR ) ); ?>' />
			</div>
			<p class="description"><?php esc_html_e( 'This will set the text color on buttons for the checkout window.', 'peachpay-for-woocommerce' ); ?></p>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Adds the field for uploading a merhant logo
 */
function peachpay_general_section_merchant_logo() {
	add_settings_field(
		'peachpay_merchant_logo_section',
		peachpay_build_section_header( __( 'Logo', 'peachpay-for-woocommerce' ) ),
		'peachpay_render_merchant_logo_field',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Renders the merchant logo field input.
 */
function peachpay_render_merchant_logo_field() {
	$merchant_logo_id  = peachpay_get_merchant_logo_id();
	$merchant_logo_src = peachpay_get_merchant_logo_src();

	$img_field_value = null === $merchant_logo_id ? '' : $merchant_logo_id;

	$img_src = null === $merchant_logo_src ? '' : $merchant_logo_src;

	$remove_btn_class = null === $merchant_logo_src ? 'hide' : '';

	?>
	<div class="peachpay-setting-section">
		<div>
			<div class="pp-flex-row pp-gap-24 pp-max-w-50 pp-ai-start">
				<div>
					<input id="pp-merchant-logo-img-field" type="hidden" name="peachpay_express_checkout_branding[merchant_logo]" value="<?php echo esc_attr( $img_field_value ); ?>">
					<div class="pp-flex-col pp-gap-12 pp-ai-center">
						<div class="pp-merchant-logo-border"><img id="pp-merchant-logo-img" <?php echo esc_html( $img_src ? 'src=' . esc_url( $img_src ) . '' : '' ); ?> /></div>
						<span id="pp-merchant-logo-remove" class="<?php echo esc_attr( $remove_btn_class ); ?> pp-delete-field">REMOVE</span>
					</div>
				</div>
				<div class="pp-flex-col pp-gap-12">
					<p class="description"><?php esc_html_e( 'Add your logo at the top of the checkout window. We recommend an image of at least 512⨉512.', 'peachpay-for-woocommerce' ); ?></p>
					<button id="pp-merchant-logo-select" type="button" class="button pp-button-secondary">
						<?php esc_html_e( 'Choose image', 'peachpay-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
			<div class="pp-save-button-section">
				<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
			</div>
		</div>
	</div>
	<?php
}
