<?php
/**
 * PeachPay button settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/util/button.php';

/**
 * Calls the functions that implement the subsections under Button preferences.
 */
function peachpay_settings_button() {
	peachpay_button_section_overall();
	peachpay_button_section_shop_page();
	peachpay_button_section_product_page();
	peachpay_button_section_cart_page();
	peachpay_button_section_checkout_page();
	peachpay_button_section_locations();
	peachpay_button_section_reset();
}

/**
 * Adds the general settings fields.
 */
function peachpay_button_section_overall() {
	add_settings_section(
		'peachpay_section_button',
		__( 'Button preferences', 'peachpay-for-woocommerce' ),
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_button_color',
		__( 'Color', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_color_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_color' )
	);

	add_settings_field(
		'peachpay_field_button_icon',
		__( 'Show icon', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_icon_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_icon' )
	);

	add_settings_field(
		'peachpay_field_button_border_radius',
		__( 'Rounded corners', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_border_radius_cb',
		'peachpay',
		'peachpay_section_button',
		array(
			'label_for' => 'button_border_radius',
			'key'       => 'button_border_radius',
		)
	);

	add_settings_field(
		'peachpay_button_text',
		__( 'Text', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_text_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_text' )
	);

	add_settings_field(
		'peachpay_field_button_sheen',
		__( 'Shine', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_sheen_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_sheen' )
	);

	add_settings_field(
		'peachpay_field_button_fade',
		__( 'Fade', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_fade_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_fade' )
	);

	add_settings_field(
		'peachpay_field_disable_default_font_css',
		__( 'Font style', 'peachpay-for-woocommerce' ),
		'peachpay_field_disable_default_font_css_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_disable_default_font_css' )
	);

	add_settings_field(
		'peachpay_field_payment_methods',
		__( 'Hide payment methods', 'peachpay-for-woocommerce' ),
		'peachpay_field_payment_method_icons_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_payment_method_icons' )
	);
}

/**
 * Give a user a choice of where to put the button on the product page
 */
function peachpay_button_product_page_position_cb() {
	?>
	<select
		id='peachpay_button_before_after_cart'
		name='peachpay_button_options[product_button_position]'
		value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_button_options', 'product_button_position' ) ? peachpay_get_settings_option( 'peachpay_button_options', 'product_button_position' ) : 'before' ); ?>'
		style='width: 96px'
	>
		<option value='beforebegin' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'product_button_position' ), 'beforebegin', true ); ?>><?php esc_html_e( 'Before', 'peachpay-for-woocommerce' ); ?></option>
		<option value='afterend' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'product_button_position' ), 'afterend', true ); ?>><?php esc_html_e( 'After', 'peachpay-for-woocommerce' ); ?></option>
	</select>

	<p
	for='peachpay_button_before_after_cart'
	class="description">
	<?php
	esc_html_e( 'Choose whether the PeachPay button appears before or after the "add to cart" button on the product page.', 'peachpay-for-woocommerce' );
	?>
	</p>
	<?php
}

/**
 * This function creates the field for toggling PeachPay button icon.
 */
function peachpay_field_button_icon_cb() {
	?>
	<select
		id='peachpay_button_icon'
		name='peachpay_button_options[button_icon]'
		value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ) ? peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ) : 'lock' ); ?>'
		style='width: 96px'
	>
		<option value='lock' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'lock', true ); ?>><?php esc_html_e( 'Lock', 'peachpay-for-woocommerce' ); ?></option>
		<option value='baseball' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'baseball', true ); ?>><?php esc_html_e( 'Baseball', 'peachpay-for-woocommerce' ); ?></option>
		<option value='arrow' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'arrow', true ); ?>><?php esc_html_e( 'Arrow', 'peachpay-for-woocommerce' ); ?></option>
		<option value='mountain' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'mountain', true ); ?>><?php esc_html_e( 'Mountain', 'peachpay-for-woocommerce' ); ?></option>
		<option value='bag' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'bag', true ); ?>><?php esc_html_e( 'Bag', 'peachpay-for-woocommerce' ); ?></option>
		<option value='none' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'none', true ); ?>><?php esc_html_e( 'None', 'peachpay-for-woocommerce' ); ?></option>
	</select>
	<?php
}

/**
 * Callback for button color field.
 */
function peachpay_field_button_color_cb() {
	$options              = get_option( 'peachpay_button_options' );
	$default_color_hex    = '#FF876C';
	$current_button_color = ($options)?$options['button_color']:'';
	?>
	<div class="pp-merged-inputs">
		<div class="pp-color-input-container">
			<input
				id='peachpay_button_color'
				name='peachpay_button_options[button_color]'
				type='color'
				value='<?php echo esc_attr( $options ? $options['button_color'] : $default_color_hex ); ?>'
				list='presets'
			/>
		</div>
		<input
			id='peachpay_button_color_text'
			name='button_color_text'
			type='text'
			value='<?php echo esc_attr( $current_button_color ); ?>'
		/>
	</div>
	<style>
		.pp-merged-inputs{
			padding: 0;
			margin: 0;
			background-color: white;
			border-radius: 0.2rem;
			border: 1px solid #8c8f94;
			width: fit-content;
			display: flex;
			flex-direction: row;
		}
		.pp-color-input-container {
			border-right: 1px solid #8c8f94;
		}
		.pp-merged-inputs input{
			padding: 0;
			margin: 0;
			border: none;
			border-radius: 0;
		}
		.pp-merged-inputs input[type="color"]{
			height:100%;
			width: 65px;
			border-top-left-radius: 0.2rem;
			border-bottom-left-radius: 0.2rem;
		}
		.pp-merged-inputs input[type="text"]{
			display: inline-block;
			padding: 0 5px;
			width: 100px;
			border-top-right-radius: 0.2rem;
			border-bottom-right-radius: 0.2rem;
		}
	</style>
	<script>
		function copyColorFromText(){
			const hex_color = document.getElementById('peachpay_button_color_text').value;
			if(document.getElementById('peachpay_button_color') != null)
			document.getElementById('peachpay_button_color').value = hex_color;
		}
		if(document.getElementById('peachpay_button_color_text')!= null){
			const hex_input = document.getElementById('peachpay_button_color_text');
			if(document.getElementById('peachpay_button_color_text').value != null)
			hex_input.addEventListener('change', copyColorFromText);
			hex_input.addEventListener('input', copyColorFromText);
		}
		function copyColorFromDropBox(){
			if(document.getElementById('peachpay_button_color_text')!= null){
				const hex_color = document.getElementById('peachpay_button_color').value;
				document.getElementById('peachpay_button_color_text').value = hex_color;
			}
		}
		if(document.getElementById('peachpay_button_color')!= null){
			const hex_dropBox = document.getElementById('peachpay_button_color');
			if(document.getElementById('peachpay_button_color').value != null)
			hex_dropBox.addEventListener('change', copyColorFromDropBox);
		}
	</script>

	<datalist id="presets">
		<option>#FF876C</option>
		<option>#ff8ba8</option>
		<option>#ff4d39</option>
		<option>#5cab5e</option>
		<option>#0286e7</option>
		<option>#af57ec</option>
		<option>#111111</option>
	</datalist>

	<?php
}

/**
 * Callback for peachpay_field_button_border_radius that renders the button
 * radius (rounded corners) setting field.
 *
 * @param array $args Button arguments.
 */
function peachpay_field_button_border_radius_cb( $args ) {
	$options = get_option( 'peachpay_button_options' );
	$key     = $args['key'];
	?>
	<input
		id="<?php echo esc_attr( $key ); ?>"
		name="peachpay_button_options[<?php echo esc_attr( $key ); ?>]"
		type="number"
		value="<?php echo esc_attr( ( $options && array_key_exists( $key, $options ) ) ? $options[ $key ] : 5 ); ?>"
		style="width: 75px"
	> px
	<?php
}

/**
 * Callback for button text field.
 */
function peachpay_field_button_text_cb() {
	?>
	<input
		id="peachpay_button_text"
		name="peachpay_button_options[peachpay_button_text]"
		type="text"
		value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_button_options', 'peachpay_button_text' ) ); ?>'
		style='width: 300px'
	>
	<p class="description"><?php esc_html_e( 'Customize the text of the PeachPay button. Leaving it blank defaults it to "Express checkout" in your chosen language.', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Callback for button shine field.
 */
function peachpay_field_button_sheen_cb() {
	?>
	<input
		id="peachpay_button_sheen"
		name="peachpay_button_options[button_sheen]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'button_sheen' ), true ); ?>
	>
	<label for='peachpay_button_sheen'><?php esc_html_e( 'Turn off button shine', 'peachpay-for-woocommerce' ); ?></label>
	<?php
}

/**
 * Callback for button shine field.
 */
function peachpay_field_button_fade_cb() {
	?>
	<input
		id="peachpay_button_fade"
		name="peachpay_button_options[button_fade]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'button_fade' ), true ); ?>
	>
	<label for='peachpay_button_fade'><?php esc_html_e( 'Turn on button fade on hover', 'peachpay-for-woocommerce' ); ?></label>
	<p
	for='peachpay_button_fade'
	class="description">
	<?php
	esc_html_e( 'It\'s recommended to have \'Show icon\' set to \'None\' if button fade is enabled.', 'peachpay-for-woocommerce' );
	?>
	</p>
	<?php
}

/**
 * Callback for disable default CSS for the button field.
 */
function peachpay_field_disable_default_font_css_cb() {
	?>
	<input
		id="peachpay_disable_default_font_css"
		name="peachpay_button_options[disable_default_font_css]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'disable_default_font_css' ), true ); ?>
	>
	<label for='peachpay_disable_default_font_css'><?php esc_html_e( 'Make the PeachPay button font style match the theme font', 'peachpay-for-woocommerce' ); ?></label>
	<p
	for='peachpay_disable_default_font_css'
	class="description">
	<?php esc_html_e( 'This will disable the PeachPay button font style rules (font-family, font-size, font-weight, and transform-text) and use the styles from the website theme.', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Callback for the show payment method icons field.
 */
function peachpay_field_payment_method_icons_cb() {
	?>
	<input
		id="peachpay_payment_method_icons"
		name="peachpay_button_options[button_hide_payment_method_icons]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'button_hide_payment_method_icons' ), true ); ?>
	>
	<label for='peachpay_payment_method_icons'><?php esc_html_e( 'Hide the payment method icons below the PeachPay button', 'peachpay-for-woocommerce' ); ?></label>
	<?php
}

/**
 * Adds the fields for the Product Page subsection.
 */
function peachpay_button_section_product_page() {
	add_settings_section(
		'peachpay_product_page_button',
		__( 'Product page', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_product_button_alignment',
		__( 'Alignment', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_alignment_cb',
		'peachpay',
		'peachpay_product_page_button',
		array(
			'label_for' => 'product_button_alignment',
			'key'       => 'product_button_alignment',
		)
	);

	add_settings_field(
		'peachpay_field_button_before_after_add_to_cart',
		__( 'Position', 'peachpay-for-woocommerce' ),
		'peachpay_button_product_page_position_cb',
		'peachpay',
		'peachpay_product_page_button',
	);

	add_settings_field(
		'peachpay_field_button_width_product_page',
		__( 'Width', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_width_cb',
		'peachpay',
		'peachpay_product_page_button',
		array(
			'label_for' => 'button_width_product_page',
			'key'       => 'button_width_product_page',
		)
	);

	add_settings_field(
		'peachpay_field_button_preview',
		__( 'Preview', 'peachpay-for-woocommerce' ),
		'peachpay_field_product_button_preview_cb',
		'peachpay',
		'peachpay_product_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);

}

/**
 * Callback for peachpay_field_product_button_alignment that renders the product alignment selector.
 *
 * @param array $args Contains which page.
 */
function peachpay_field_button_alignment_cb( $args ) {
	$options = get_option( 'peachpay_button_options' );
	$key     = $args['key'];

	$alignment_product_page = array(
		__( 'Left', 'peachpay-for-woocommerce' )   => 'left',
		__( 'Right', 'peachpay-for-woocommerce' )  => 'right',
		__( 'Full', 'peachpay-for-woocommerce' )   => 'full',
		__( 'Center', 'peachpay-for-woocommerce' ) => 'center',
	);

	// Keep order the same so the default is "Full".
	$alignment_cart_page = array(
		__( 'Full', 'peachpay-for-woocommerce' )   => 'full',
		__( 'Left', 'peachpay-for-woocommerce' )   => 'left',
		__( 'Right', 'peachpay-for-woocommerce' )  => 'right',
		__( 'Center', 'peachpay-for-woocommerce' ) => 'center',
	);

	$alignment_checkout_page = array(
		__( 'Center', 'peachpay-for-woocommerce' ) => 'center',
	);

	$alignment_floating_button = array(
		__( 'Bottom right', 'peachpay-for-woocommerce' ) => 'right',
		__( 'Bottom left', 'peachpay-for-woocommerce' )  => 'left',
	);

	$alignment = array();

	switch ( $key ) {
		case ( 'product_button_alignment' ):
			$alignment = $alignment_product_page;
			break;
		case ( 'cart_button_alignment' ):
			$alignment = $alignment_cart_page;
			break;
		case ( 'checkout_button_alignment' ):
			$alignment = $alignment_checkout_page;
			break;
		case ( 'floating_button_alignment' ):
			$alignment = $alignment_floating_button;
			break;
	}
	?>
	<select
		id="<?php echo esc_attr( $key ); ?>"
		name="peachpay_button_options[<?php echo esc_attr( $key ); ?>]">
		<?php foreach ( $alignment as $alignments => $value ) { ?>
			<option
				value="<?php echo esc_attr( $value ); ?>"
				<?php echo isset( $options[ $key ] ) ? ( selected( $options[ $key ], $value, false ) ) : ( '' ); ?>
			>
				<?php echo esc_html( $alignments ); ?>
			</option>
		<?php } ?>
	</select>
	<?php
}

/**
 * Callback for peachpay_field_button_width that renders the button width input.
 *
 * @param array $args Page arguments.
 */
function peachpay_field_button_width_cb( $args ) {
	$options = get_option( 'peachpay_button_options' );
	$key     = $args['key'];
	$page    = '';
	switch ( $key ) {
		case 'button_width_cart_page':
			$page = 'cart';
			break;
		case 'button_width_product_page':
			$page = 'product';
			break;
		case 'button_width_checkout_page':
			$page = 'checkout';
			break;
	}
	$disabled = peachpay_get_settings_option( 'peachpay_button_options', $page . '_button_position' ) !== 'full' ? '' : 'disabled';

	// If the hidden field is not here then the second time the form is saved
	// while the field is set to "full" the value is lost because disabled
	// inputs are not submitted.
	if ( $disabled ) {
		?>
		<input
			id="<?php echo esc_attr( $key ); ?>"
			name="peachpay_button_options[<?php echo esc_attr( $key ); ?>]"
			type="hidden"
			value="<?php echo esc_attr( ( $options && array_key_exists( $key, $options ) ) ? $options[ $key ] : 220 ); ?>"
		>
		<?php
	}

	?>
		<input
			id="<?php echo esc_attr( $key ); ?>"
			name="peachpay_button_options[<?php echo esc_attr( $key ); ?>]"
			type="number"
			value="<?php echo esc_attr( ( $options && array_key_exists( $key, $options ) ) ? $options[ $key ] : 220 ); ?>"
			style="width: 75px" <?php echo esc_attr( $disabled ); ?>
		> px
	<?php
}

/**
 * Callback for peachpay_field_checkout_outline that renders the checkout outline display.
 */
function peachpay_field_checkout_outline_cb() {
	?>
	<input
		id = "checkout_outline_disabled"
		name = "peachpay_button_options[checkout_outline_disabled]"
		type = "checkbox"
		value = "1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'checkout_outline_disabled' ), true ); ?>
	>
	<label for='checkout_outline_disabled'>
	<?php
		esc_html_e( 'Don\'t show the outline around the PeachPay button on the checkout page', 'peachpay-for-woocommerce' );
	?>
	</label>
	<?php
}

/**
 * Callback for peachpay_field_checkout_text that renders the checkout text display.
 */
function peachpay_field_checkout_text_cb() {
	?>
	<input
		id="checkout_header_text"
		name="peachpay_button_options[checkout_header_text]"
		type="text"
		value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_button_options', 'checkout_header_text' ) ); ?>'
	>
	<p class="description"><?php esc_html_e( 'Customize the text above the PeachPay button on the checkout page. Leaving it blank defaults it to "Check out with PeachPay" in your chosen language.', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Callback for peachpay_field_checkout_subtext that renders the checkout subtext display.
 */
function peachpay_field_checkout_subtext_cb() {
	?>
	<input
		id="checkout_subtext_text"
		name="peachpay_button_options[checkout_subtext_text]"
		type="text"
		value='<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_button_options', 'checkout_subtext_text' ) ); ?>'
	>
	<p class="description"><?php esc_html_e( 'Customize the text below the PeachPay button on the checkout page. Leaving it blank defaults it to "The next time you come back, you’ll have one-click checkout and won’t have to waste time filling out the fields below." in your chosen language.', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * Callback for peachpay_field_product_button_preview that renders the product page button preview.
 */
function peachpay_field_product_button_preview_cb() {
	$options     = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && '' !== $options['peachpay_button_text'] ) ? $options['peachpay_button_text'] : peachpay_get_translated_text( 'button_text' );
	?>
		<div id="pp-button-container" class="button-container-preview pp-button-container margin-0">
			<button
				id="pp-button-product" class="pp-button" type="button"
				style='--button-color:<?php echo esc_attr( $options ? $options['button_color'] : '#ff876c' ); ?>'>
				<div id="pp-button-content">
					<span id="pp-button-text"> <?php echo esc_attr( $button_text ); ?> </span>
					<img id="button-icon-product" class=""/>
				</div>
			</button>
			<div id="payment-methods-container-product" class='cc-company-logos'>
				<img class="<?php print( peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' ) ) ? 'cc-logo' : 'hide'; ?>"
					src="<?php echo esc_url( peachpay_url( 'public/img/marks/paypal.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/visa.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/amex.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/discover.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/mastercard.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/cc-stripe-brands.svg' ) ); ?>"/>
			</div>
		</div>
	<?php
}

/**
 * Creates the cart page subsection.
 */
function peachpay_button_section_cart_page() {
	add_settings_section(
		'peachpay_cart_page_button',
		__( 'Cart page', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_cart_button_alignment',
		__( 'Alignment', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_alignment_cb',
		'peachpay',
		'peachpay_cart_page_button',
		array(
			'label_for' => 'cart_button_alignment',
			'key'       => 'cart_button_alignment',
		)
	);

	add_settings_field(
		'peachpay_field_button_width_cart_page',
		__( 'Width', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_width_cb',
		'peachpay',
		'peachpay_cart_page_button',
		array(
			'label_for' => 'button_width_cart_page',
			'key'       => 'button_width_cart_page',
		)
	);

	add_settings_field(
		'peachpay_field_button_preview',
		__( 'Preview', 'peachpay-for-woocommerce' ),
		'peachpay_field_cart_button_preview_cb',
		'peachpay',
		'peachpay_cart_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);
}

/**
 * Callback for peachpay_field_cart_button_preview that renders the cart page button preview.
 */
function peachpay_field_cart_button_preview_cb() {
	$options     = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && '' !== $options['peachpay_button_text'] ) ? $options['peachpay_button_text'] : peachpay_get_translated_text( 'button_text' );
	?>
		<div id="pp-button-container" class="button-container-preview pp-button-container margin-0">
			<button
				id="pp-button-cart" class="pp-button" type="button"
				style='--button-color:<?php echo esc_attr( $options ? $options['button_color'] : '#ff876c' ); ?>'>
				<div id="pp-button-content">
					<span id="pp-button-text"> <?php echo esc_attr( $button_text ); ?></span>
					<img id="button-icon-cart" class=""/>
				</div>
			</button>
			<div id="payment-methods-container-cart" class='cc-company-logos'>
				<img class="<?php print( peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' ) ) ? 'cc-logo' : 'hide'; ?>"
				src="<?php echo esc_url( peachpay_url( 'public/img/marks/paypal.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/visa.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/amex.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/discover.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/mastercard.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/cc-stripe-brands.svg' ) ); ?>"/>
			</div>
		</div>
	<?php
}

/**
 * Renders the checkout button options.
 */
function peachpay_button_section_checkout_page() {
	add_settings_section(
		'peachpay_checkout_page_button',
		__( 'Checkout page', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_checkout_button_alignment',
		__( 'Alignment', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_alignment_cb',
		'peachpay',
		'peachpay_checkout_page_button',
		array(
			'label_for' => 'checkout_button_alignment',
			'key'       => 'checkout_button_alignment',
		)
	);

	add_settings_field(
		'peachpay_field_button_width_checkout_page',
		__( 'Width', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_width_cb',
		'peachpay',
		'peachpay_checkout_page_button',
		array(
			'label_for' => 'button_width_checkout_page',
			'key'       => 'button_width_checkout_page',
		)
	);

	add_settings_field(
		'peachpay_field_button_preview',
		__( 'Preview', 'peachpay-for-woocommerce' ),
		'peachpay_field_checkout_button_preview_cb',
		'peachpay',
		'peachpay_checkout_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);

	add_settings_field(
		'peachpay_field_button_outline_checkout_page',
		__( 'Display outline', 'peachpay-for-woocommerce' ),
		'peachpay_field_checkout_outline_cb',
		'peachpay',
		'peachpay_checkout_page_button'
	);

	add_settings_field(
		'peachpay_field_text_checkout_page',
		__( 'Header text', 'peachpay-for-woocommerce' ),
		'peachpay_field_checkout_text_cb',
		'peachpay',
		'peachpay_checkout_page_button'
	);

	add_settings_field(
		'peachpay_field_subtext_checkout_page',
		__( 'Additional text', 'peachpay-for-woocommerce' ),
		'peachpay_field_checkout_subtext_cb',
		'peachpay',
		'peachpay_checkout_page_button'
	);
}

/**
 * Renders the checkout button preview.
 */
function peachpay_field_checkout_button_preview_cb() {
	$options     = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && '' !== $options['peachpay_button_text'] ) ? $options['peachpay_button_text'] : peachpay_get_translated_text( 'button_text' );
	?>
		<div id="pp-button-container" class="button-container-preview pp-button-container margin-0">
			<button
				id="pp-button-checkout" class="pp-button" type="button"
				style='--button-color:<?php echo esc_attr( $options ? $options['button_color'] : '#ff876c' ); ?>'>
				<div id="pp-button-content">
					<span id="pp-button-text"> <?php echo esc_html( $button_text ); ?> </span>
					<img id="button-icon-checkout" class=""/>
				</div>
			</button>
			<div id="payment-methods-container-checkout" class='cc-company-logos'>
				<img class="<?php print( peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' ) ) ? 'cc-logo' : 'hide'; ?>"
				src="<?php echo esc_url( peachpay_url( 'public/img/marks/paypal.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/visa.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/amex.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/discover.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/mastercard.svg' ) ); ?>"/>
				<img class="cc-logo" src="<?php echo esc_url( peachpay_url( 'public/img/marks/cc-stripe-brands.svg' ) ); ?>"/>
			</div>
		</div>
	<?php
}

/**
 * Renders the shop page button options.
 */
function peachpay_button_section_shop_page() {
	add_settings_section(
		'peachpay_shop_page_button',
		__( 'Shop page floating button', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_floating_button_alignment',
		__( 'Position', 'peachpay-for-woocommerce' ),
		'peachpay_field_button_alignment_cb',
		'peachpay',
		'peachpay_shop_page_button',
		array(
			'label_for' => 'floating_button_alignment',
			'key'       => 'floating_button_alignment',
		)
	);

	add_settings_field(
		'peachpay_field_floating_button_bottom_gap',
		__( 'Bottom gap', 'peachpay-for-woocommerce' ),
		'peachpay_field_floating_button_position_cb',
		'peachpay',
		'peachpay_shop_page_button',
		array(
			'label_for' => 'floating_button_bottom_gap',
			'key'       => 'floating_button_bottom_gap',
		)
	);

	add_settings_field(
		'peachpay_field_floating_button_side_gap',
		__( 'Left/right gap', 'peachpay-for-woocommerce' ),
		'peachpay_field_floating_button_position_cb',
		'peachpay',
		'peachpay_shop_page_button',
		array(
			'label_for' => 'floating_button_side_gap',
			'key'       => 'floating_button_side_gap',
		)
	);

	add_settings_field(
		'peachpay_field_floating_button_size',
		__( 'Button size', 'peachpay-for-woocommerce' ),
		'peachpay_field_floating_button_size_cb',
		'peachpay',
		'peachpay_shop_page_button',
	);

	add_settings_field(
		'peachpay_field_floating_button_icon_size',
		__( 'Icon size', 'peachpay-for-woocommerce' ),
		'peachpay_field_floating_button_icon_size_cb',
		'peachpay',
		'peachpay_shop_page_button',
	);

	add_settings_field(
		'peachpay_field_button_preview',
		__( 'Preview', 'peachpay-for-woocommerce' ),
		'peachpay_field_shop_button_preview_cb',
		'peachpay',
		'peachpay_shop_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);
}

/**
 * Callback for configuring the position of the floating peachpay button
 *
 * @param array $args Position arguments.
 */
function peachpay_field_floating_button_position_cb( $args ) {
	$options = get_option( 'peachpay_button_options' );
	$key     = $args['key'];

	?>
		<input
			id="<?php echo esc_attr( $key ); ?>"
			name="peachpay_button_options[<?php echo esc_attr( $key ); ?>]"
			type="number"
			value="<?php echo esc_attr( isset( $options[ $key ] ) ? $options[ $key ] : 45 ); ?>"
			style="width: 75px"
		> px
	<?php
}

/**
 * Callback for peachpay_field_button_size that renders the button size input for shop page.
 */
function peachpay_field_floating_button_size_cb() {
	$options = get_option( 'peachpay_button_options' );
	?>
		<input
			id="floating_button_size"
			name="peachpay_button_options[floating_button_size]"
			type="number"
			value="<?php echo esc_attr( isset( $options['floating_button_size'] ) ? $options['floating_button_size'] : 70 ); ?>"
			style="width: 75px"
		> px
	<?php
}

/**
 * Callback for peachpay_field_button_icon_size that renders the button's icon size input for shop page.
 */
function peachpay_field_floating_button_icon_size_cb() {
	$options = get_option( 'peachpay_button_options' );
	?>
		<input
			id="floating_button_icon_size"
			name="peachpay_button_options[floating_button_icon_size]"
			type="number"
			value="<?php echo esc_attr( isset( $options['floating_button_icon_size'] ) ? $options['floating_button_icon_size'] : 35 ); ?>"
			style="width: 75px"
		> px
	<?php
}

/**
 * Renders the shop button preview.
 */
function peachpay_field_shop_button_preview_cb() {
	$options = get_option( 'peachpay_button_options' );
	?>
		<div id="pp-button-container" class="button-container-preview pp-button-container margin-0" style="position: relative;">
			<button
				id="pp-button-shop" class="pp-button-float" type="button"
				style='--button-color:<?php echo esc_attr( $options ? $options['button_color'] : '#ff876c' ); ?>'>
				<div id="pp-button-content">
					<img id="button-icon-shop" class=""/>
				</div>
			</button>
			<div class="item-count">0</div>
		</div>
	<?php
}

/**
 * Adds the settings section that allows merchants to hide the PeachPay button
 * from certain locations on their store.
 */
function peachpay_button_section_locations() {
	add_settings_section(
		'peachpay_section_locations',
		__( 'Hide the PeachPay button', 'peachpay-for-woocommerce' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_button_hide_on_shop_page',
		__( 'Shop page', 'peachpay-for-woocommerce' ),
		'peachpay_button_hide_html',
		'peachpay',
		'peachpay_section_locations',
		array( 'floating_button' )
	);

	add_settings_field(
		'peachpay_field_hide_on_product_page',
		__( 'Product page', 'peachpay-for-woocommerce' ),
		'peachpay_field_hide_on_product_page_html',
		'peachpay',
		'peachpay_section_locations',
		array( 'label_for' => 'peachpay_hide_on_product_page' )
	);

	add_settings_field(
		'peachpay_button_hide_on_cart_page',
		__( 'Cart page', 'peachpay-for-woocommerce' ),
		'peachpay_button_hide_html',
		'peachpay',
		'peachpay_section_locations',
		array( 'cart_page' )
	);

	add_settings_field(
		'peachpay_button_hide_on_checkout_page',
		__( 'Checkout page', 'peachpay-for-woocommerce' ),
		'peachpay_button_hide_html',
		'peachpay',
		'peachpay_section_locations',
		array( 'checkout_page' )
	);

	add_settings_field(
		'peachpay_button_hide_in_mini_cart',
		__( 'Mini/sidebar cart', 'peachpay-for-woocommerce' ),
		'peachpay_button_hide_html',
		'peachpay',
		'peachpay_section_locations',
		array( 'mini_cart' )
	);
}

/**
 * Callback for the hide on product page field.
 */
function peachpay_field_hide_on_product_page_html() {
	?>
	<input
		id="peachpay_hide_on_product_page"
		name="peachpay_button_options[hide_on_product_page]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'hide_on_product_page' ), true ); ?>
	>
	<label for='peachpay_hide_on_product_page'><?php esc_html_e( 'Don\'t show PeachPay on product pages', 'peachpay-for-woocommerce' ); ?></label>
	<?php
}

/**
 * Use to render PeachPay button exclusion settings.
 *
 * @param string $args Arguments passed to this callback from where we add the
 * fields.
 */
function peachpay_button_hide_html( $args ) {
	?>
	<input
		id = "peachpay_disabled_on_<?php echo esc_html( $args[0] ); ?>"
		name = "peachpay_button_options[disabled_<?php echo esc_html( $args[0] ); ?>]"
		type = "checkbox"
		value = 1
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'disabled_' . $args[0] ), true ); ?>
	>
	<label for='peachpay_disabled_on_<?php echo esc_html( $args[0] ); ?>'>
	<?php
	if ( 'cart_page' === $args[0] ) {
		esc_html_e( 'Don\'t show PeachPay on the cart page', 'peachpay-for-woocommerce' );
	} elseif ( 'mini_cart' === $args[0] ) {
		esc_html_e( 'Don\'t show PeachPay in the mini and/or sidebar cart', 'peachpay-for-woocommerce' );
	} elseif ( 'floating_button' === $args[0] ) {
		esc_html_e( 'Don\'t show the floating PeachPay button on the shop page', 'peachpay-for-woocommerce' );
	} else {
		esc_html_e( 'Don\'t show PeachPay on the checkout page', 'peachpay-for-woocommerce' );
	}
	?>
	</label>
	<?php
}

/**
 * Creates a subsection for the reset button.
 */
function peachpay_button_section_reset() {
	add_settings_section(
		'peachpay_reset_button',
		__( 'Reset settings', 'peachpay-for-woocommerce' ),
		null,
		'peachpay',
	);

	add_settings_field(
		'peachpay_reset_to_default_button',
		__( 'Reset button to default settings', 'peachpay-for-woocommerce' ),
		'peachpay_reset_button_cb',
		'peachpay',
		'peachpay_reset_button',
		array(
			'label_for' => 'reset_to_default_button',
			'key'       => 'reset_to_default_button',
		)
	);
}

/**
 * Display reset button.
 */
function peachpay_reset_button_cb() {
	?>
		<a onclick="return confirm('Are you sure would you like to reset all your changes made to the PeachPay button preferences?')" href="
		<?php
		echo esc_url( add_query_arg( 'reset_button', 'peachpay' ) );
		peachpay_reset_settings();
		?>
		" class="button-secondary">
		<?php esc_html_e( 'Reset button preferences', 'peachpay-for-woocommerce' ); ?>
		</a>
	<?php
}

/**
 * Reset the button settings to original values.
 */
function peachpay_reset_settings() {
	// phpcs:disable
	if ( isset( $_GET['reset_button'] ) && 'peachpay' === $_GET['reset_button'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_reset_button();
		wp_safe_redirect( remove_query_arg( 'reset_button' ) );
		exit();
	}
	//phpcs:enable
}

/**
 * A method to the check the user's role. It returns a boolean value indicating whether the user is an admin or guest.
 *
 * @param array $user_role .
 * @param array $user_id .
 */
function peachpay_user_role( $user_role, $user_id = 0 ) {
	if ( ! function_exists( 'wp_get_current_user' ) ) {
		include ABSPATH . 'wp-includes/pluggable.php';
	}
	$_user = ( 0 === $user_id ? wp_get_current_user() : get_user_by( 'id', $user_id ) );
	if ( ! isset( $_user->roles ) || empty( $_user->roles ) ) {
		$_user->roles = array( 'guest' );
	}
	if ( ! is_array( $_user->roles ) ) {
		return false;
	}
	if ( is_array( $user_role ) ) {
		if ( in_array( 'administrator', $user_role, true ) ) {
			$user_role[] = 'super_admin';
		}
		$_intersect = array_intersect( $user_role, $_user->roles );
		return ( ! empty( $_intersect ) );
	} else {
		if ( 'administrator' === $user_role ) {
			return ( in_array( 'administrator', $_user->roles, true ) || in_array( 'super_admin', $_user->roles, true ) );
		} else {
			return ( in_array( $user_role, $_user->roles, true ) );
		}
	}
}
