<?php
/**
 * PeachPay One-Click-Upsell Settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require plugin_dir_path( __FILE__ ) . 'assets/class-peachpay-ajax-product-search.php';

add_action(
	'admin_enqueue_scripts',
	function() {
		wp_enqueue_style(
			'peachpay-one-click-upsell',
			plugin_dir_url( __FILE__ ) . 'assets/one-click-upsell.css',
			array(),
			true
		);
	}
);

/**
 * Generates the upsell page field settings
 */
function peachpay_one_click_upsell() {
	add_settings_section(
		'peachpay_one_click_upsell',
		__( 'One-Click-Upsell', 'woocommerce-for-japan' ),
		'',
		'peachpay'
	);

	add_settings_field(
		'peachpay_one_click_upsell_toggle',
		__( 'Enable', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_toggle_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label_for' => 'peachpay_one_click_upsell_toggle' )
	);

	add_settings_field(
		'peachpay_one_click_upsell_products',
		__( 'Product to upsell', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_products_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label-for' => 'peachpay_one_click_upsell_products' )
	);

	add_settings_field(
		'peachpay_display_one_click_upsell',
		__( 'Product(s) to display upsell on', 'woocommerce-for-japan' ),
		'peachpay_display_one_click_upsell_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label-for' => 'peachpay_display_one_click_upsell' )
	);

	add_settings_field(
		'peachpay_one_click_upsell_flow',
		__( 'When', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_flow_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label_for' => 'peachpay_one_click_upsell_flow' )
	);

	add_settings_field(
		'peachpay_one_click_upsell_primary_header',
		__( 'Primary header', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_primary_header_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label_for' => 'peachpay_one_click_upsell_primary_header' )
	);

	add_settings_field(
		'peachpay_one_click_upsell_secondary_header_text',
		__( 'Secondary header', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_secondary_header_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label_for' => 'peachpay_one_click_upsell_secondary_header_text' )
	);

	add_settings_field(
		'peachpay_one_click_upsell_accept_button_text',
		__( 'Accept button text', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_accept_button_text_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label-for' => 'peachpay_one_click_upsell_accept_button_text' )
	);

	add_settings_field(
		'peachpay_one_click_upsell_decline_button_text',
		__( 'Decline button text', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_decline_button_text_cb',
		'peachpay',
		'peachpay_one_click_upsell',
		array( 'label-for' => 'peachpay_one_click_upsell_decline_button_text' )
	);

	add_settings_section(
		'peachpay_one_click_upsell_preview',
		__( 'Preview', 'woocommerce-for-japan' ),
		'peachpay_one_click_upsell_preview_cb',
		'peachpay'
	);
}

/**
 * Callback for displaying upsell product for all products
 */
function peachpay_display_one_click_upsell_cb() {
	$options = get_option( 'peachpay_one_click_upsell_options' );
	?>
	<div style="display: flex; flex-direction: column; gap: 16px;">
		<div>
			<input
				id="peachpay_display_one_click_upsell_toggle_all"
				name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_display_all]"
				type="checkbox"
				value="1"
				<?php checked( 1, peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_display_all' ), true ); ?>
			>
			<label for="peachpay_display_one_click_upsell_toggle_all"><b><?php esc_attr_e( 'Display upsell for all products', 'woocommerce-for-japan' ); ?></b></label>
		</div>
		<div
		id="pp-display-container" 
		class="<?php print( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_display_all' ) ) ? 'pp-mute' : ''; ?>"
		>
			<select
				id="pp_display_one_click_upsell"
				data-security="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ); ?>" 
				class="pp-display-product-search"
				style="width: 300px;"
				multiple="multiple"
				name="peachpay_one_click_upsell_options[peachpay_display_one_click_upsell][]"
			>
			<?php
			foreach ( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_display_one_click_upsell' ) as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( is_object( $product ) ) {
					echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
				}
			}
			?>
			</select>
			<p class="description"><?php esc_html_e( 'Choose which product(s) to display the upsell on', 'woocommerce-for-japan' ); ?></p>
		</div>
	</div>
	<script>
		document.querySelector('#peachpay_display_one_click_upsell_toggle_all').addEventListener('change', (event) => {
			if (event.target.checked) {
				document.querySelector('#pp-display-container').classList.add('pp-mute');
			} else {
				document.querySelector('#pp-display-container').classList.remove('pp-mute');
			}
		});
	</script>
	<?php
}

/**
 * Callback for toggling one-click-upsell feature
 */
function peachpay_one_click_upsell_toggle_cb() {
	?>
	<input
		id="peachpay_one_click_upsell_toggle"
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_enable]"
		type="checkbox"
		value="1"
		<?php checked( 1, peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_enable' ), true ); ?>
	>
	<label for="peachpay_one_click_upsell_toggle"><b><?php esc_attr_e( 'Upsell a product during the checkout flow (in a separate pop-up)', 'woocommerce-for-japan' ); ?></b></label>
	<?php
}

/**
 * Callback for deciding which checkout flow to display the one-click-upsell page
 */
function peachpay_one_click_upsell_flow_cb() {
	$options = get_option( 'peachpay_one_click_upsell_options' );
	?>
	<select 
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_flow]"
		value='<?php echo esc_attr( $options['peachpay_one_click_upsell_flow'] ? $options['peachpay_one_click_upsell_flow'] : 'after_payment' ); ?>'
	>
		<option 
			value="after_payment" 
			<?php selected( $options['peachpay_one_click_upsell_flow'], 'after_payment', true ); ?>
		>
			<?php echo esc_html_e( 'After payment', 'woocommerce-for-japan' ); ?>
		</option>
		<option 
			value="pp_button" 
			<?php selected( $options['peachpay_one_click_upsell_flow'], 'pp_button', true ); ?>
		>
			<?php echo esc_html_e( 'Upon clicking the PeachPay button', 'woocommerce-for-japan' ); ?>
		</option>
		<option 
			value="before_payment" 
			<?php selected( $options['peachpay_one_click_upsell_flow'], 'before_payment', true ); ?>
		>
			<?php echo esc_html_e( 'After information page, before payment page', 'woocommerce-for-japan' ); ?>
		</option>
	</select>
	<?php
}

/**
 * Callback for change the primary header text
 */
function peachpay_one_click_upsell_primary_header_cb() {
	?>
	<input
		id="peachpay_one_click_upsell_primary_header"
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_primary_header]"
		type="text"
		style="width: 50%"
		placeholder="You may also like &#8230;"
		value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_primary_header' ) ); ?>"
	>
	<p class="description"><?php esc_html_e( 'Change the primary header text. Leaving it blank defaults it to "You may also like &#8230;" in your chosen language.', 'woocommerce-for-japan' ); ?></p>
	<?php
}

/**
 * Callback for changing the secondary header text
 */
function peachpay_one_click_upsell_secondary_header_cb() {
	?>
	<input
		id="peachpay_one_click_upsell_secondary_header"
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_secondary_header]"
		type="text"
		style="width: 50%"
		placeholder=""
		value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_secondary_header' ) ); ?>"
	>
	<p class="description"><?php esc_html_e( 'Add a secondary header.', 'woocommerce-for-japan' ); ?></p>
	<?php
}

/**
 * Callback for customizing the accept button text
 */
function peachpay_one_click_upsell_accept_button_text_cb() {
	?>
	<input
		id="peachpay_one_click_upsell_accept_button_text"
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_accept_button_text]"
		type="text"
		style="width: 50%"
		placeholder="Add to order"
		value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_accept_button_text' ) ); ?>"
	>
	<p class="description"><?php esc_html_e( 'Change the accept button text. Leaving it blank defaults it to "Add to order" in your chosen language.', 'woocommerce-for-japan' ); ?></p>
	<?php
}

/**
 * Callback for customizing the decline button text
 */
function peachpay_one_click_upsell_decline_button_text_cb() {
	?>
	<input
		id="peachpay_one_click_upsell_decline_button_text"
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_decline_button_text]"
		type="text"
		style="width: 50%"
		placeholder="No thanks"
		value="<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_decline_button_text' ) ); ?>"
	>
	<p class="description"><?php esc_html_e( 'Change the decline button text. Leaving it blank defaults it to "No thanks" in your chosen language.', 'woocommerce-for-japan' ); ?></p>
	<?php
}

/**
 * Callback for selecting one or more upsell products
 * Referenced https://stackoverflow.com/questions/30973651/add-product-search-field-in-woo-commerce-product-page
 */
function peachpay_one_click_upsell_products_cb() {
	$options = get_option( 'peachpay_one_click_upsell_options' );
	?>
	<select
		id="pp_one_click_upsell_products"
		data-security="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ); ?>" 
		style="width: 300px;" 
		class="pp-product-search"
		name="peachpay_one_click_upsell_options[peachpay_one_click_upsell_products]"
	>
	<?php
	if ( $options['peachpay_one_click_upsell_products'] ) {
		$product_id = $options['peachpay_one_click_upsell_products'];
		$product    = wc_get_product( $product_id );
		?>
			<option value="<?php echo esc_attr( $options['peachpay_one_click_upsell_products'] ); ?>" selected="selected" >
			<?php
			echo esc_html( $product->get_formatted_name() );
			?>
			</option>
			<?php
	}
	?>
	</select>
	<p class="description"><?php esc_html_e( 'Please select only one simple, non-variable product', 'woocommerce-for-japan' ); ?></p>
	<?php
}

/**
 * For displaying the One-Click-Upsell page in the preview section
 */
function peachpay_one_click_upsell_preview_cb() {
	$options                 = get_option( 'peachpay_one_click_upsell_options' );
	$primary_header          = peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_primary_header' ) ? $options['peachpay_one_click_upsell_primary_header'] : 'You may also like &#8230;';
	$secondary_header        = peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_secondary_header' ) ? $options['peachpay_one_click_upsell_secondary_header'] : false;
	$ocu_product             = peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_products' ) ? wc_get_product( $options['peachpay_one_click_upsell_products'] ) : false;
	$ocu_product_name        = $ocu_product ? $ocu_product->get_name() : 'Product name';
	$ocu_product_description = $ocu_product ? preg_replace( '/<img[^>]+\>/i', '', $ocu_product->get_description() ) : 'Lorem ipsum dolor sit amet, consect adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo. Lorem ipsum dolor sit amet, consect adipiscing elit.';
	$ocu_product_price       = $ocu_product ? $ocu_product->get_price_html() : '$0.00';
	$ocu_product_img         = $ocu_product ? wp_get_attachment_image_url( $ocu_product->get_image_id(), 'full' ) : wc_placeholder_img_src();
	$accept_button_text      = peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_accept_button_text' ) ? $options['peachpay_one_click_upsell_accept_button_text'] : 'Add to order';
	$decline_button_text     = peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_decline_button_text' ) ? $options['peachpay_one_click_upsell_decline_button_text'] : 'No thanks';
	?>
		<div class="pp-one-click-upsell-container">
			<div class="pp-ocu-close">&times;</div>
			<span class="pp-one-click-upsell-headline"><?php echo esc_attr( $primary_header ); ?></span>
			<div class="pp-one-click-upsell-sub-headline <?php print( $secondary_header ? '' : 'hide' ); ?>">
				<span><?php echo esc_attr( $secondary_header ); ?></span>
			</div>
			<div class="pp-one-click-upsell-contents">
				<img class="pp-ocu-product-img" src="<?php echo esc_attr( $ocu_product_img ); ?>"/>
				<span class="pp-ocu-product-name"><?php echo esc_attr( $ocu_product_name ); ?></span>
				<span class="pp-ocu-product-description"><?php echo esc_attr( $ocu_product_description ); ?></span>
				<div class="pp-ocu-product-price"><?php echo wp_kses_post( $ocu_product_price ); ?></div>
			</div>
			<div
			style='--button-color:<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_button_options', 'button_color', PEACHPAY_DEFAULT_BACKGROUND_COLOR ) ); ?>'
			class="pp-ocu-accept-button"><?php echo esc_attr( $accept_button_text ); ?></div>
			<div class="pp-ocu-decline-button"><?php echo esc_attr( $decline_button_text ); ?></div>
		</div>
	<?php
}

add_filter( 'peachpay_register_feature', 'peachpay_ocu_feature_flag', 10, 1 );

/**
 * Adds a filter to send PeachPay's One-Click-Checkout products to checkout modal to be rendered.
 *
 * @param array $data PeachPay data array.
 */
function peachpay_ocu_feature_flag( $data ) {
	$options                         = get_option( 'peachpay_one_click_upsell_options' );
	$data['peachpay_ocu']['enabled'] = peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_enable' ) && peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_products' ) && peachpay_display_ocu_product() ? true : false;
	$data['peachpay_ocu']['version'] = 1;

	$metadata = array(
		'pp_ocu_flow'         => peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_flow' ),
		'headline_text'       => peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_primary_header' ) ? $options['peachpay_one_click_upsell_primary_header'] : 'You may also like ...',
		'sub_headline_text'   => peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_secondary_header' ),
		'accept_button_text'  => peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_accept_button_text' ) ? $options['peachpay_one_click_upsell_accept_button_text'] : 'Add to order',
		'decline_button_text' => peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_decline_button_text' ) ? $options['peachpay_one_click_upsell_decline_button_text'] : 'No thanks',
		'pp_ocu_products'     => peachpay_ocu_product_data(),
	);

	$data['peachpay_ocu']['metadata'] = $metadata;

	return $data;
}

/**
 * Gathers PeachPay One-Click-Upsell product data
 */
function peachpay_ocu_product_data() {
	$options = get_option( 'peachpay_one_click_upsell_options' );
	if ( ! peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_enable' ) || ! peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_products' ) ) {
		return null;
	}
	$product      = wc_get_product( $options['peachpay_one_click_upsell_products'] );
	$product_data = array(
		'id'    => $product->get_id(),
		'name'  => $product->get_name(),
		'desc'  => preg_replace( '/<img[^>]+\>/i', '', $product->get_description() ),
		'price' => $product->get_price_html(),
		'image' => is_array( peachpay_product_image( $product ) ) ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : wc_placeholder_img_src(),
	);

	return $product_data;
}

/**
 * Allows display of OCU product on selected products or on all products.
 */
function peachpay_display_ocu_product() {
	// If upsell item is already in cart, then do not display the upsell page.
	if ( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_products' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		$product_id = wc_get_product( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_products' ) )->get_id();
		// Loop though cart items.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			// Handling also variable products and their products variations.
			$cart_item_ids = array( $cart_item['product_id'], $cart_item['variation_id'] );

			// Handle a simple ocu product id or an array of ocu product ids.
			if ( ( is_array( $product_id ) && array_intersect( $product_id, $cart_item_ids ) ) || ( ! is_array( $product_id ) && in_array( $product_id, $cart_item_ids, true ) ) ) {
				return false;
			}
		}
	}

	if ( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_one_click_upsell_display_all' ) ) {
		return true;
	}

	if ( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_display_one_click_upsell' ) ) {
		foreach ( peachpay_get_settings_option( 'peachpay_one_click_upsell_options', 'peachpay_display_one_click_upsell' ) as $product_id ) {
			if ( is_product() ) {
				$cur_product_id = wc_get_product()->get_id();
				$product_id     = wc_get_product( $product_id )->get_id();
				if ( $product_id === $cur_product_id ) {
					return true;
				}
			} elseif ( is_cart() ) {
				$product_cart_id = WC()->cart->generate_cart_id( $product_id );
				$in_cart         = WC()->cart->find_product_in_cart( $product_cart_id );
				if ( $in_cart ) {
					return true;
				}
			}
		}
	}

	return false;
}
