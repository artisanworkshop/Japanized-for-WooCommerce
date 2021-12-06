<?php
/**
 * Implements the PeachPay plugin settings Button preferences sections.
 *
 * @package PeachPay
 */

require_once plugin_dir_path( __FILE__ ) . '../util/reset-button.php';

/**
 * Calls the functions that implement the subsections under Button preferences.
 */
function peachpay_settings_button() {
	peachpay_button_section_overall();
	peachpay_button_section_product_page();
	peachpay_button_section_cart_page();
	peachpay_button_section_checkout_page();
	peachpay_button_section_reset();
}

/**
 * Adds the general settings fields.
 */
function peachpay_button_section_overall() {
	add_settings_section(
		'peachpay_section_button',
		__( 'Button preferences', 'woocommerce-for-japan' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_button_color',
		__( 'Color', 'woocommerce-for-japan' ),
		'peachpay_field_button_color_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_color' )
	);

	add_settings_field(
		'peachpay_field_button_icon',
		__( 'Show icon', 'woocommerce-for-japan' ),
		'peachpay_field_button_icon_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_icon' )
	);

	add_settings_field(
		'peachpay_field_button_border_radius',
		__( 'Rounded corners', 'woocommerce-for-japan' ),
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
		__( 'Text', 'woocommerce-for-japan' ),
		'peachpay_field_button_text_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_text' )
	);

	add_settings_field(
		'peachpay_field_button_sheen',
		__( 'Shine', 'woocommerce-for-japan' ),
		'peachpay_field_button_sheen_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_button_sheen' )
	);

	add_settings_field(
		'peachpay_field_hide_on_product_page',
		__( 'Hide on product page', 'woocommerce-for-japan' ),
		'peachpay_field_hide_on_product_page_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_hide_on_product_page' )
	);

	add_settings_field(
		'peachpay_field_payment_methods',
		__( 'Hide payment methods', 'woocommerce-for-japan' ),
		'peachpay_field_payment_method_icons_cb',
		'peachpay',
		'peachpay_section_button',
		array( 'label_for' => 'peachpay_payment_method_icons' )
	);
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
		<option value='lock' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'lock', true ); ?>>Lock</option>
		<option value='baseball' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'baseball', true ); ?>>Baseball</option>
		<option value='arrow' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'arrow', true ); ?>>Arrow</option>
		<option value='mountain' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'mountain', true ); ?>>Mountain</option>
		<option value='bag' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'bag', true ); ?>>Bag</option>
		<option value='none' <?php selected( peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ), 'none', true ); ?>>None</option>
	</select>
	<?php
}

/**
 * Callback for button color field.
 */
function peachpay_field_button_color_cb() {
	$options = get_option( 'peachpay_button_options' );
	?>
	<input
		id='peachpay_button_color'
		name='peachpay_button_options[button_color]'
		type='color'
		value='<?php echo esc_attr( $options ? $options['button_color'] : '#FF876C' ); ?>'
		style='width: 75px'
		list='presets'
	/>
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
 * @param array $args {
 *      @type string $key
 *      @type string $label_for
 * }
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
	<p class="description"><?php _e( 'Customize the text of the PeachPay button. Leaving it blank defaults it to "Express checkout" in your chosen language.', 'woocommerce-for-japan');?></p>
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
	<label for='peachpay_button_sheen'><?php _e( 'Turn off button shine', 'woocommerce-for-japan');?></label>
	<?php
}

/**
 * Callback for the hide on product page field.
 */
function peachpay_field_hide_on_product_page_cb() {
	?>
	<input
		id="peachpay_hide_on_product_page"
		name="peachpay_button_options[hide_on_product_page]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_button_options', 'hide_on_product_page' ), true ); ?>
	>
	<label for='peachpay_hide_on_product_page'><?php _e( 'Don’t show the PeachPay button on individual product pages', 'woocommerce-for-japan');?></label>
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
	<label for='peachpay_payment_method_icons'><?php _e( 'Hide the payment method icons below the PeachPay button', 'woocommerce-for-japan');?></label>
	<?php
}

/**
 * Adds the fields for the Product Page subsection.
 */
function peachpay_button_section_product_page() {
	add_settings_section(
		'peachpay_product_page_button',
		__( 'Product page', 'woocommerce-for-japan' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_product_button_position',
		__( 'Position', 'woocommerce-for-japan' ),
		'peachpay_field_button_position_cb',
		'peachpay',
		'peachpay_product_page_button',
		array(
			'label_for' => 'product_button_position',
			'key'       => 'product_button_position',
		)
	);

	add_settings_field(
		'peachpay_field_button_width_product_page',
		__( 'Width', 'woocommerce-for-japan' ),
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
		__( 'Preview', 'woocommerce-for-japan' ),
		'peachpay_field_product_button_preview_cb',
		'peachpay',
		'peachpay_product_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);
}

/**
 * Callback for peachpay_field_product_button_position that renders the product position selector.
 *
 * @param array $args Contains which page.
 */
function peachpay_field_button_position_cb( $args ) {
	$options                 = get_option( 'peachpay_button_options' );
	$key                     = $args['key'];
	$positions_product_page  = array(
		'Left'   => 'left',
		'Right'  => 'right',
		'Full'   => 'full',
		'Center' => 'center',
	);
	$positions_cart_page     = array(
		'Full'   => 'full',
		'Left'   => 'left',
		'Right'  => 'right',
		'Center' => 'center',
	);
	$positions_checkout_page = array(
		'Center' => 'center',
	);
	$positions = array();
	switch ( $key ) {
		case ( 'product_button_position' ):
			$positions = $positions_product_page;
			break;
		case ( 'cart_button_position' ):
			$positions = $positions_cart_page;
			break;
		case ( 'checkout_button_position' ):
			$positions = $positions_checkout_page;
			break;
	}
	?>
	<select
		id="<?php echo esc_attr( $key ); ?>"
		name="peachpay_button_options[<?php echo esc_attr( $key ); ?>]">
		<?php foreach ( $positions as $position => $value ) { ?>
			<option
				value="<?php echo esc_attr( $value ); ?>"
				<?php echo isset( $options[ $key ] ) ? ( selected( $options[ $key ], $value, false ) ) : ( '' ); ?>
			>
				<?php echo esc_html( $position ); ?>
			</option>
		<?php } ?>
	</select>
	<?php
}

/**
 * Callback for peachpay_field_button_width that renders the button width input.
 *
 * @param array $args {
 * @type string $key page.
 * @type string $label_for page.
 * }
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
 * Callback for peachpay_field_product_button_preview that renders the product page button preview.
 */
function peachpay_field_product_button_preview_cb() {
	$options     = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && '' !== $options['peachpay_button_text'] ) ? $options['peachpay_button_text'] : peachpay_get_button_text();
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
				<img class="<?php print(peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' )) ? "cc-logo" : "hide"; ?>"
				src="/wp-content/plugins/peachpay-for-woocommerce/img/paypal.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/visa.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/amex.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/discover.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/mastercard.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/cc-stripe-brands.svg"/>
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
		__( 'Cart page', 'woocommerce-for-japan' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_cart_button_position',
		__( 'Position', 'woocommerce-for-japan' ),
		'peachpay_field_button_position_cb',
		'peachpay',
		'peachpay_cart_page_button',
		array(
			'label_for' => 'cart_button_position',
			'key'       => 'cart_button_position',
		)
	);

	add_settings_field(
		'peachpay_field_button_width_cart_page',
		__( 'Width', 'woocommerce-for-japan' ),
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
		__( 'Preview', 'woocommerce-for-japan' ),
		'peachpay_field_cart_button_preview_cb',
		'peachpay',
		'peachpay_cart_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);
}

/**
 * Callback for peachpay_field_cart_button_preview that renders the cart page button preview.
 *
 * @param array $args {
 *      @type string $key page.
 *      @type string $label_for page.
 * }
 */
function peachpay_field_cart_button_preview_cb() {
	$options     = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && '' !== $options['peachpay_button_text'] ) ? $options['peachpay_button_text'] : peachpay_get_button_text();
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
				<img class="<?php print(peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' )) ? "cc-logo" : "hide"; ?>"
				src="/wp-content/plugins/peachpay-for-woocommerce/img/paypal.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/visa.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/amex.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/discover.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/mastercard.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/cc-stripe-brands.svg"/>
			</div>
		</div>
	<?php
}

function peachpay_button_section_checkout_page() {
	add_settings_section(
		'peachpay_checkout_page_button',
		__( 'Checkout page', 'woocommerce-for-japan' ),
		null,
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_checkout_button_position',
		__( 'Position', 'woocommerce-for-japan' ),
		'peachpay_field_button_position_cb',
		'peachpay',
		'peachpay_checkout_page_button',
		array(
			'label_for' => 'checkout_button_position',
			'key'       => 'checkout_button_position',
		)
	);

	add_settings_field(
		'peachpay_field_button_width_checkout_page',
		__( 'Width', 'woocommerce-for-japan' ),
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
		__( 'Preview', 'woocommerce-for-japan' ),
		'peachpay_field_checkout_button_preview_cb',
		'peachpay',
		'peachpay_checkout_page_button',
		array( 'label_for' => 'peachpay_field_button_preview' )
	);
}

function peachpay_field_checkout_button_preview_cb() {
	$options     = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && $options['peachpay_button_text'] !== '' ) ? $options['peachpay_button_text'] : peachpay_get_button_text();
	?>
		<div id="pp-button-container" class="button-container-preview pp-button-container margin-0">
			<button
				id="pp-button-checkout" class="pp-button" type="button"
				style='--button-color:<?php echo esc_attr( $options ? $options['button_color'] : '#ff876c' ); ?>'>
				<div id="pp-button-content">
					<span id="pp-button-text"> <?php echo $button_text; ?> </span>
					<img id="button-icon-checkout" class=""/>
				</div>
			</button>
			<div id="payment-methods-container-checkout" class='cc-company-logos'>
				<img class="<?php print(peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' )) ? "cc-logo" : "hide"; ?>"
				src="/wp-content/plugins/peachpay-for-woocommerce/img/paypal.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/visa.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/amex.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/discover.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/mastercard.svg"/>
				<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/cc-stripe-brands.svg"/>
			</div>
		</div>
	<?php
}

/**
 * Creates a subsection for the reset button.
 */
function peachpay_button_section_reset() {
	add_settings_section(
		'peachpay_reset_button',
		__( 'Reset settings', 'woocommerce-for-japan' ),
		null,
		'peachpay',
	);

	add_settings_field(
		'peachpay_reset_to_default_button',
		__( 'Reset button to default settings', 'woocommerce-for-japan' ),
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
		echo add_query_arg( 'reset_button', 'peachpay' ); peachpay_reset_settings()
		?>
		" class="button-secondary">
		Reset button preferences
		</a>
	<?php
}

/**
 * Reset the button settings to original values.
 */
function peachpay_reset_settings() {
	if ( isset( $_GET['reset_button'] ) && 'peachpay' === $_GET['reset_button'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_reset_button();
		wp_safe_redirect( remove_query_arg( 'reset_button' ) );
		exit();
	}
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
	$_user = ( 0 == $user_id ? wp_get_current_user() : get_user_by( 'id', $user_id ) );
	if ( ! isset( $_user->roles ) || empty( $_user->roles ) ) {
		$_user->roles = array( 'guest' );
	}
	if ( ! is_array( $_user->roles ) ) {
		return false;
	}
	if ( is_array( $user_role ) ) {
		if ( in_array( 'administrator', $user_role ) ) {
			$user_role[] = 'super_admin';
		}
		$_intersect = array_intersect( $user_role, $_user->roles );
		return ( ! empty( $_intersect ) );
	} else {
		if ( 'administrator' == $user_role ) {
			return ( in_array( 'administrator', $_user->roles ) || in_array( 'super_admin', $_user->roles ) );
		} else {
			return ( in_array( $user_role, $_user->roles ) );
		}
	}
}
