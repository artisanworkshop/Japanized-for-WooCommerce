<?php
/**
 * PeachPay Recommended Products Settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require PEACHPAY_ABSPATH . 'core/modules/one-click-upsell/assets/class-peachpay-ajax-product-search.php';

/**
 * Calls all functions that add the settings fields to the product recommendations section
 */
function peachpay_express_checkout_product_recommendations_render() {
	// phpcs:disable
	$current = 'pp-sub-nav-pr';
	if ( isset( $_COOKIE['pp_sub_nav_express_checkout_product_recommendations'] ) ) {
		$current = $_COOKIE['pp_sub_nav_express_checkout_product_recommendations'];
	}
	peachpay_pr_ocu_sub_nav( $current );
	peachpay_product_recommendations_section( $current );
	peachpay_one_click_upsell( $current );
}

/**
 * Adds the product recommendations / ocu navigation tabs.
 */
function peachpay_pr_ocu_sub_nav( $current ) {
	?>
	<div class='pp-flex-row pp-gap-12 pp-sub-nav-container'>
		<?php
		$buttons = array(
			array(
				'id'    => 'pp-sub-nav-pr',
				'title' => 'Product recommendations'
			),
			array(
				'id'    => 'pp-sub-nav-ocu',
				'title' => 'One-click upsell'
			),
		);
		foreach( $buttons as $button ) {
			?>
				<div class='pp-sub-nav-button<?php echo 0 === strcmp( $current, $button['id'] ) ? ' pp-sub-nav-button-active' : '' ?>' id='<?php echo esc_attr( $button['id'] ); ?>'><?php echo esc_html( $button['title'] ); ?></div>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Adds product recommendation fields.
 */
function peachpay_product_recommendations_section( $current ) {
	$class = 'pp-header pp-sub-nav-pr';
	if ( $current != 'pp-sub-nav-pr' ) {
		$class .= ' hide';
	}
	add_settings_field(
		'peachpay_rp_checkout_modal_section',
		peachpay_build_section_header( __( 'Display', 'peachpay-for-woocommerce' ) ),
		'peachpay_rp_checkout_modal_cb',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
	add_settings_field(
		'peachpay_upsell_cross-sell',
		peachpay_build_section_header( __( 'WooCommerce upsell and cross-sell', 'peachpay-for-woocommerce' ) ),
		'peachpay_upsell_cross_sell_cb',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
	add_settings_field(
		'peachpay_automatic_recommendations',
		peachpay_build_section_header( __( 'Automatic recommendations', 'peachpay-for-woocommerce' ) ),
		'peachpay_automatic_recommendations_cb',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
	add_settings_field(
		'peachpay_manual_recommendations',
		peachpay_build_section_header( __( 'Manual recommendations', 'peachpay-for-woocommerce' ) ),
		'peachpay_manual_recommended_products_cb',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
}

/**
 * Renders the product recommendation settings that determine what recommendations to show
 */
function peachpay_upsell_cross_sell_cb() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<?php
			peachpay_admin_input(
				'woocommerce_linked_products',
				'peachpay_express_checkout_product_recommendations',
				'display_woocommerce_linked_products',
				1,
				__( 'Include upsell and cross-sell items that you have already configured', 'peachpay-for-woocommerce' ),
				'',
				array( 'input_type' => 'checkbox' )
			);
			?>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>	
	<?php
}

/**
 * Renders the automatic recommendations settings.
 */
function peachpay_automatic_recommendations_cb() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<h4><?php esc_html_e( 'Related by', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_product_relation_cb(); ?>
		</div>
		<?php
		peachpay_admin_input(
			'peachpay_display_rp_nproducts',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_rp_nproducts',
			'',
			__( 'Number of products to display for mini slider and full page', 'peachpay-for-woocommerce' ),
			'',
			array(
				'input_type'  => 'number',
				'placeholder' => __( 'ie 99', 'peachpay-for-woocommerce' ),
			)
		);

		peachpay_admin_input(
			'peachpay_related_products_exclude_id',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_exclude_id',
			'',
			__( 'Taxonomy IDs to exclude (comma separated)', 'peachpay-for-woocommerce' ),
			'',
			array(
				'input_type'  => 'text',
				'placeholder' => __( 'ie 12,45,32', 'peachpay-for-woocommerce' ),
			)
		);
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Callback for selecting one or more recommended products
 * Referenced https://stackoverflow.com/questions/30973651/add-product-search-field-in-woo-commerce-product-page
 */
function peachpay_manual_recommended_products_cb() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<h4>Add product</h4>
			<select
				id="pp_recommended_products_manual"
				data-security="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ); ?>" 
				style="width: 300px;" 
				class="pp-pr-search pp-select2"
				name="peachpay_express_checkout_product_recommendations[peachpay_recommended_products_manual][]"
				multiple="multiple"
			>
			<?php
			if ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_recommended_products_manual' ) ) {
				foreach ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_recommended_products_manual' ) as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
					}
				}
			}
			?>
			</select>
		</div>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the product recommendation settings that determine how it appears in the checkout window
 */
function peachpay_rp_checkout_modal_cb() {
	?>
	<div class="peachpay-setting-section">
		<div>
			<?php
			peachpay_admin_input(
				'peachpay_rp_mini_slider',
				'peachpay_express_checkout_product_recommendations',
				'peachpay_rp_mini_slider',
				1,
				__( 'Display recommended products as a mini slider', 'peachpay-for-woocommerce' ),
				'',
				array( 'input_type' => 'checkbox' )
			);
			?>
			<div class="pp-flex-row pp-gap-12 flex-wrap">
				<div class="flex col center">
					<img src='<?php echo esc_url( peachpay_url( '/public/img/recommended-products/new-user.svg' ) ); ?>'/>
					<p class="description"><?php esc_html_e( 'New user', 'peachpay-for-woocommerce' ); ?></p>
				</div>
				<div class="flex col center">
					<img src='<?php echo esc_url( peachpay_url( '/public/img/recommended-products/returning-user.svg' ) ); ?>'/>
					<p class="description"><?php esc_html_e( 'Returning user', 'peachpay-for-woocommerce' ); ?></p>
				</div>
			</div>
		</div>
		<?php
		peachpay_admin_input(
			'peachpay_rp_header_mini_slider',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_rp_mini_slider_header',
			'',
			__( 'Header text', 'peachpay-for-woocommerce' ),
			__( 'Customize the header text for the mini slider. Leaving it blank defaults it to “Recommended for you” in your chosen language.', 'peachpay-for-woocommerce' ),
			array(
				'input_type'  => 'text',
				'placeholder' => __( 'Recommended for you', 'peachpay-for-woocommerce' ),
			)
		);
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Generates the upsell page field settings
 */
function peachpay_one_click_upsell( $current ) {
	$class = 'pp-header pp-sub-nav-ocu';
	if ( $current != 'pp-sub-nav-ocu' ) {
		$class .= ' hide';
	}
	add_settings_field(
		'peachpay_one_click_upsell_display',
		peachpay_build_section_header( __( 'Display', 'peachpay-for-woocommerce' ), 'https://youtu.be/SUd1y03iGzY' ),
		'peachpay_ocu_display_settings',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
	add_settings_field(
		'peachpay_one_click_upsell_appearance',
		peachpay_build_section_header( __( 'Appearance', 'peachpay-for-woocommerce' ) ),
		'peachpay_ocu_appearance_settings',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
	add_settings_field(
		'peachpay_one_click_upsell_preview',
		peachpay_build_section_header( __( 'Preview', 'peachpay-for-woocommerce' ) ),
		'peachpay_ocu_preview_cb',
		'peachpay',
		'peachpay_express_checkout_render',
		array( 'class' => $class )
	);
}

/**
 * Renders the one-click upsell display settings
 */
function peachpay_ocu_display_settings() {
	$options = get_option( 'peachpay_express_checkout_product_recommendations', array() );
	?>
	<div class="peachpay-setting-section">
		<div>
			<?php
				peachpay_admin_input(
					'peachpay_one_click_upsell_enable',
					'peachpay_express_checkout_product_recommendations',
					'peachpay_one_click_upsell_enable',
					1,
					__( 'Enable one-click upsells', 'peachpay-for-woocommerce' ),
					__( 'Upsell a product during the checkout flow (in a separate pop-up)', 'peachpay-for-woocommerce' ),
					array( 'input_type' => 'checkbox' )
				);
			?>
			<h4><?php esc_html_e( 'When', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_one_click_upsell_flow_cb( $options ); ?>
		</div>
		<div>
			<h4><?php esc_html_e( 'Product to upsell', 'peachpay-for-woocommerce' ); ?></h4>
			<?php peachpay_one_click_upsell_products_cb( $options ); ?>
		</div>
		<?php
		peachpay_display_one_click_upsell_cb();
		peachpay_admin_input(
			'peachpay_display_one_click_upsell_toggle_all',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_one_click_upsell_display_all',
			1,
			__( 'Display upsell for all products', 'peachpay-for-woocommerce' ),
			'',
			array( 'input_type' => 'checkbox' )
		);
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
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
 * Render the one-click upsell appearance settings
 */
function peachpay_ocu_appearance_settings() {
	?>
	<div class="peachpay-setting-section">
		<?php
		peachpay_admin_input(
			'peachpay_one_click_upsell_primary_header',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_one_click_upsell_primary_header',
			'',
			__( 'Primary header', 'peachpay-for-woocommerce' ),
			__( 'Change the primary header text. Leaving it blank defaults it to "Recommended for you" in your chosen language.', 'peachpay-for-woocommerce' ),
			array(
				'input_type'  => 'text',
				'placeholder' => __( 'Recommended for you', 'peachpay-for-woocommerce' ),
			)
		);
		?>
		<?php
		peachpay_admin_input(
			'peachpay_one_click_upsell_secondary_header',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_one_click_upsell_secondary_header',
			'',
			__( 'Secondary header', 'peachpay-for-woocommerce' ),
			__( 'Add a secondary header.', 'peachpay-for-woocommerce' ),
			array(
				'input_type' => 'text',
			)
		);

		peachpay_admin_input(
			'peachpay_one_click_upsell_custom_description',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_one_click_upsell_custom_description',
			'',
			__( 'Custom product description', 'peachpay-for-woocommerce' ),
			__( 'Add a custom product description.', 'peachpay-for-woocommerce' ),
			array(
				'input_type' => 'textarea',
			)
		);

		peachpay_admin_input(
			'peachpay_one_click_upsell_accept_button_text',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_one_click_upsell_accept_button_text',
			'',
			__( 'Accept button text', 'peachpay-for-woocommerce' ),
			__( 'Change the accept button text. Leaving it blank defaults it to "Add to order" in your chosen language.', 'peachpay-for-woocommerce' ),
			array(
				'input_type'  => 'text',
				'placeholder' => __( 'Add to order', 'peachpay-for-woocommerce' ),
			)
		);

		peachpay_admin_input(
			'peachpay_one_click_upsell_decline_button_text',
			'peachpay_express_checkout_product_recommendations',
			'peachpay_one_click_upsell_decline_button_text',
			'',
			__( 'Decline button text', 'peachpay-for-woocommerce' ),
			__( 'Change the decline button text. Leaving it blank defaults it to "No thanks" in your chosen language.', 'peachpay-for-woocommerce' ),
			array(
				'input_type'  => 'text',
				'placeholder' => __( 'No thanks', 'peachpay-for-woocommerce' ),
			)
		);
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

function peachpay_ocu_preview_cb() {
	$options = get_option( 'peachpay_express_checkout_product_recommendations', array() );
	?>
	<div class="peachpay-setting-section">
		<div>
			<?php peachpay_one_click_upsell_preview_cb( $options ); ?>
		</div>
	</div>
	<?php
}

/**
 * Callback for displaying upsell product for all products
 */
function peachpay_display_one_click_upsell_cb() {
	?>
	<div
	id="pp-display-container"
	class="<?php print( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_display_all' ) ) ? 'pp-mute' : ''; ?>"
	>
		<h4>Products that show upsells</h4>
		<select
			id="pp_display_one_click_upsell"
			data-security="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ); ?>"
			class="pp-display-product-search pp-select2"
			style="width: 300px;"
			multiple="multiple"
			name="peachpay_express_checkout_product_recommendations[peachpay_display_one_click_upsell][]"
		>
		<?php
		if ( ! empty( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_display_one_click_upsell' ) ) ) {
			foreach ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_display_one_click_upsell' ) as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( is_object( $product ) ) {
					echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
				}
			}
		}
		?>
		</select>
		<p class="description"><?php esc_html_e( 'Choose which product(s) to display the upsell on', 'peachpay-for-woocommerce' ); ?></p>
	</div>
	<?php
}

/**
 * Callback for deciding which checkout flow to display the one-click-upsell page
 *
 * @param mixed $options The list of WordPress settings options.
 */
function peachpay_one_click_upsell_flow_cb( $options ) {
	$pp_ocu_flow = array_key_exists( 'peachpay_one_click_upsell_flow', $options ) ? $options['peachpay_one_click_upsell_flow'] : 'after_payment';
	?>
	<select
		name="peachpay_express_checkout_product_recommendations[peachpay_one_click_upsell_flow]"
		value='<?php echo esc_attr( $pp_ocu_flow ); ?>'
	>
		<option
			value="after_payment"
			<?php selected( $pp_ocu_flow, 'after_payment', true ); ?>
		>
			<?php echo esc_html_e( 'After payment', 'peachpay-for-woocommerce' ); ?>
		</option>
		<option
			value="pp_button"
			<?php selected( $pp_ocu_flow, 'pp_button', true ); ?>
		>
			<?php echo esc_html_e( 'Upon clicking the PeachPay button', 'peachpay-for-woocommerce' ); ?>
		</option>
		<option
			value="before_payment"
			<?php selected( $pp_ocu_flow, 'before_payment', true ); ?>
		>
			<?php echo esc_html_e( 'After information page, before payment page', 'peachpay-for-woocommerce' ); ?>
		</option>
	</select>
	<?php
}

/**
 * Callback for selecting one or more upsell products
 * Referenced https://stackoverflow.com/questions/30973651/add-product-search-field-in-woo-commerce-product-page
 *
 * @param mixed $options The list of WordPress settings options.
 */
function peachpay_one_click_upsell_products_cb( $options ) {
	?>
	<select
		id="pp_one_click_upsell_products"
		data-security="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ); ?>"
		style="width: 300px;"
		class="pp-product-search pp-select2"
		name="peachpay_express_checkout_product_recommendations[peachpay_one_click_upsell_products]"
	>
	<?php
	if ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) ) {
		$product_id = $options['peachpay_one_click_upsell_products'];
		$product    = wc_get_product( $product_id );
		if ( isset( $product ) && $product ) {
			?>
			<option value="<?php echo esc_attr( $options['peachpay_one_click_upsell_products'] ); ?>" selected="selected" >
			<?php
			echo esc_html( $product->get_formatted_name() );
			?>
			</option>
			<?php
		}
	}
	?>
	</select>
	<p class="description"><?php esc_html_e( 'Please select only one simple, non-variable product', 'peachpay-for-woocommerce' ); ?></p>
	<?php
}

/**
 * For displaying the One-Click-Upsell page in the preview section
 *
 * @param mixed $options The list of WordPress settings options.
 */
function peachpay_one_click_upsell_preview_cb( $options ) {
	$primary_header          = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_primary_header' ) ? $options['peachpay_one_click_upsell_primary_header'] : 'Recommended for you';
	$secondary_header        = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_secondary_header' ) ? $options['peachpay_one_click_upsell_secondary_header'] : false;
	$ocu_product             = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) ? wc_get_product( $options['peachpay_one_click_upsell_products'] ) : false;
	$ocu_product_name        = $ocu_product ? $ocu_product->get_name() : 'Product name';
	$ocu_product_description = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_custom_description' ) ? $options['peachpay_one_click_upsell_custom_description'] : false;
	$ocu_product_price       = $ocu_product ? $ocu_product->get_price_html() : '$0.00';
	$ocu_product_img         = $ocu_product ? wp_get_attachment_image_url( $ocu_product->get_image_id(), 'full' ) : wc_placeholder_img_src();
	$accept_button_text      = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_accept_button_text' ) ? $options['peachpay_one_click_upsell_accept_button_text'] : 'Add to order';
	$decline_button_text     = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_decline_button_text' ) ? $options['peachpay_one_click_upsell_decline_button_text'] : 'No thanks';
	?>
	<div class="pp-button-preview-container">
		<div class="pp-one-click-upsell-container">
			<div class="pp-ocu-close">&times;</div>
			<span class="pp-one-click-upsell-headline"><?php echo esc_attr( $primary_header ); ?></span>
			<div class="pp-one-click-upsell-sub-headline <?php print( $secondary_header ? '' : 'hide' ); ?>">
				<span><?php echo esc_attr( $secondary_header ); ?></span>
			</div>
			<div class="pp-one-click-upsell-contents">
				<img class="pp-ocu-product-img" src="<?php echo esc_attr( $ocu_product_img ); ?>"/>
				<span class="pp-ocu-product-name"><?php echo esc_attr( $ocu_product_name ); ?></span>
				<span class="pp-ocu-product-description <?php print( $ocu_product_description ? '' : 'hide' ); ?>"><?php echo wp_kses_post( $ocu_product_description ); ?></span>
				<div class="pp-ocu-product-price"><?php echo wp_kses_post( $ocu_product_price ); ?></div>
			</div>
			<div
			style='--button-color:<?php echo esc_attr( peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_color', PEACHPAY_DEFAULT_BACKGROUND_COLOR ) ); ?>'
			class="pp-ocu-accept-button"><?php echo esc_attr( $accept_button_text ); ?></div>
			<div class="pp-ocu-decline-button"><?php echo esc_attr( $decline_button_text ); ?></div>
		</div>
	</div>
	<?php
}

/**
 * Adds a filter to send PeachPay's One-Click-Checkout products to checkout modal to be rendered.
 *
 * @param array $data PeachPay data array.
 */
function peachpay_ocu_feature_flag( $data ) {
	$options                         = get_option( 'peachpay_express_checkout_product_recommendations' );
	$data['peachpay_ocu']['enabled'] = peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_enable' ) && peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) && peachpay_display_ocu_product() ? true : false;
	$data['peachpay_ocu']['version'] = 1;

	$metadata = array(
		'pp_ocu_flow'         => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_flow' ),
		'headline_text'       => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_primary_header' ) ? $options['peachpay_one_click_upsell_primary_header'] : 'Recommended for you',
		'sub_headline_text'   => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_secondary_header' ),
		'accept_button_text'  => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_accept_button_text' ) ? $options['peachpay_one_click_upsell_accept_button_text'] : 'Add to order',
		'decline_button_text' => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_decline_button_text' ) ? $options['peachpay_one_click_upsell_decline_button_text'] : 'No thanks',
		'pp_ocu_products'     => peachpay_ocu_product_data(),
		'custom_description'  => peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_custom_description' ),
	);

	$data['peachpay_ocu']['metadata'] = $metadata;

	return $data;
}

add_filter( 'peachpay_register_feature', 'peachpay_ocu_feature_flag', 10, 1 );

/**
 * Gathers PeachPay One-Click-Upsell product data
 */
function peachpay_ocu_product_data() {
	$options = get_option( 'peachpay_express_checkout_product_recommendations' );
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_enable' ) || ! peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) ) {
		return null;
	}
	$product      = wc_get_product( $options['peachpay_one_click_upsell_products'] );
	$product_data = null;
	if ( isset( $product ) && $product ) {
		$product_data = array(
			'id'    => $product->get_id(),
			'name'  => $product->get_name(),
			'price' => $product->get_price_html(),
			'image' => is_array( peachpay_product_image( $product ) ) ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : wc_placeholder_img_src(),
		);
	}

	return $product_data;
}
add_filter( 'peachpay_dynamic_feature_metadata', 'peachpay_update_ocu_product_data', 10, 2 );

/**
 * Gathers PeachPay One-Click-Upsell product data
 *
 * @param array  $feature_metadata Peachpay feature metadata.
 * @param string $cart_key The given cart key.
 */
function peachpay_update_ocu_product_data( $feature_metadata, $cart_key ) {
	$options = get_option( 'peachpay_express_checkout_product_recommendations' );
	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_enable' ) || ! peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) || '0' !== $cart_key ) {
		return $feature_metadata;
	}

	$product = wc_get_product( $options['peachpay_one_click_upsell_products'] );
	if ( isset( $product ) && $product ) {
		$product_data = array(
			'id'    => $product->get_id(),
			'name'  => $product->get_name(),
			'price' => $product->get_price_html(),
			'image' => is_array( peachpay_product_image( $product ) ) ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : wc_placeholder_img_src(),
		);
	}

	$feature_metadata['peachpay_ocu'] = array( 'pp_ocu_products' => $product_data );
	return $feature_metadata;
}

/**
 * Allows display of OCU product on selected products or on all products.
 */
function peachpay_display_ocu_product() {
	// If upsell item is already in cart, then do not display the upsell page.
	if ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		$product    = wc_get_product( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_products' ) );
		$product_id = isset( $product ) && $product ? $product->get_id() : array();
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

	if ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_one_click_upsell_display_all' ) ) {
		return true;
	}

	if ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_display_one_click_upsell' ) ) {
		foreach ( peachpay_get_settings_option( 'peachpay_express_checkout_product_recommendations', 'peachpay_display_one_click_upsell' ) as $product_id ) {
			if ( is_product() ) {
				$cur_product_id = wc_get_product() ? wc_get_product()->get_id() : false;
				$product_id     = wc_get_product( $product_id ) ? wc_get_product( $product_id )->get_id() : false;
				if ( $cur_product_id && $product_id && $product_id === $cur_product_id ) {
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
