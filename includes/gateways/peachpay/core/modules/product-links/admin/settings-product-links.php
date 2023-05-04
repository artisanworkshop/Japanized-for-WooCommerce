<?php
/**
 * Settings Section for PeachPay product links page that allows products to be linked to the product links site.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * New settings section for prodcut links page.
 */
function peachpay_settings_product_links() {
	add_settings_section(
		'peachpay_product_links',
		'',
		'',
		'peachpay',
		'peachpay_section_product_links'
	);

	add_settings_section(
		'',
		'',
		'peachpay_feedback_cb',
		'peachpay',
		'peachpay_section_product_links'
	);

	if ( ! peachpay_get_settings_option( 'peachpay_product_links', 'key', null ) ) {
		add_settings_field(
			'peachpay_product_links_socials',
			__( 'Store details', 'peachpay-for-woocommerce' ),
			'peachpay_unregistered_product_links',
			'peachpay',
			'peachpay_product_links',
			array( 'class' => 'pp-header' )
		);
		return;
	}

	add_settings_field(
		'peachpay_product_links_socials',
		__( 'Store details', 'peachpay-for-woocommerce' ),
		'peachpay_product_links_details_section',
		'peachpay',
		'peachpay_product_links',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_product_links_active_product',
		__( 'Active products', 'peachpay-for-woocommerce' ),
		'peachpay_product_links_active_product_section',
		'peachpay',
		'peachpay_product_links',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_product_links_add_product',
		__( 'Add products', 'peachpay-for-woocommerce' ),
		'peachpay_product_links_add_product_section',
		'peachpay',
		'peachpay_product_links',
		array( 'class' => 'pp-header' )
	);
}

/**
 * The Product links section for editing social media links.
 */
function peachpay_product_links_details_section() {
	?>
	<div class="peachpay-setting-section product-links">
		<h3>
			<?php echo esc_html_e( 'Store identifier', 'peachpay-for-woocommerce' ); ?>
		</h3>
		<p>
			<?php echo esc_html( peachpay_get_settings_option( 'peachpay_product_links', 'store_name' ) ); ?>
		</p>
		<h3>
			<?php echo esc_html_e( 'Product links URL', 'peachpay-for-woocommerce' ); ?>
		</h3>
		<a 
			href=<?php echo esc_html( ( peachpay_is_local_development_site() ? 'https://fast.peachpay.local/merchant/' : 'https://fast.peachpay.app/merchant' ) . peachpay_get_settings_option( 'peachpay_product_links', 'store_name' ) ); ?>
			target="_blank"
		>
			<?php echo esc_html( ( peachpay_is_local_development_site() ? 'https://fast.peachpay.local/merchant/' : 'https://fast.peachpay.app/merchant' ) . peachpay_get_settings_option( 'peachpay_product_links', 'store_name' ) ); ?>
		</a>
		<h3>
			<?php echo esc_html_e( 'Store key', 'peachpay-for-woocommerce' ); ?>
		</h3>
		<input
			type='password'
			id="productLinksKey"
			name='peachpay_product_links[key]'
			value = <?php echo esc_html( peachpay_get_settings_option( 'peachpay_product_links', 'key', false ) ); ?>
			readonly
		>
		<input
			type="hidden"
			id="store_name"
			name="peachpay_product_links[store_name]"
			value=<?php echo esc_html( peachpay_get_settings_option( 'peachpay_product_links', 'store_name' ) ); ?>
		>
	</div>
	<?php
}

/**
 * If a store is not registered let them register or link.
 */
function peachpay_unregistered_product_links() {
	?>
	<p style="padding-bottom:5px">
		<?php echo esc_html_e( 'To build your Product links, please enter a short identifier that will be used in the URL of the Product links. Don’t use capital letters or spaces.', 'peachpay-for-woocommerce' ); ?>
	<p>
	<input
	id="store_slug"
	name="peachpay_product_links[store_name]"
	pattern="(^[^\sA-Z]*)([a-z0-9]+)$"
	>
	</input>
	<input
		type="button"
		id='peachpay-register-product-links'
		class='pp-button-primary'
		value=<?php echo esc_html_e( 'Register', 'peachpay-for-woocommerce' ); ?>
	>
	</input>
	<input
	type='hidden'
	id='product_links_key'
	name='peachpay_product_links[key]'
	value=<?php esc_html( peachpay_get_settings_option( 'peachpay_product_links', 'key', null ) ); ?>
	>
	<input
		type='hidden'
		value=<?php echo esc_html( get_home_url() ); ?>
		id='site_url'
	>
	<input
		type="submit"
		id="hidden_submit"
		class="hide"
	>
	<input
	type="hidden"
	id="store_name"
	value="<?php echo esc_html( get_bloginfo( 'name' ) ); ?>"
	>
	<?php
}

/**
 * Active product section we will leave pretty empty and fill with calls to peachpay landing api.
 */
function peachpay_product_links_active_product_section() {
	?>
	<div class="peachpay-setting-section pp-load">
		<div id="active_product_container">
		</div>
		<div class='ppModal' id='pp-product-links-modal'>

		</div>
	</div>
	<?php
}

/**
 * Allow users to specify new products to add to their store.
 */
function peachpay_product_links_add_product_section() {
	?>
	<div class="peachpay-setting-section product-links">
		<select
			id="pp_product_links_add"
			data-security="<?php echo esc_attr( wp_create_nonce( 'search-products' ) ); ?>" 
			style="width: 300px;" 
			class="pp-display-product-search"
			name="peachpay_product_links[new_products][]"
			multiple="multiple"
		>
		</select>
		<p class="description">
			<?php echo esc_html_e( 'Add products to your Product links (variable products must have variations configured for it to work properly)', 'peachpay-for-woocommerce' ); ?>
		</p>
		<?php submit_button( 'Add prodcuts', 'pp-button-primary' ); ?>
	</div>
	<?php
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_product_links_script' );

/**
 * Enqueues scripts for Product links admin.
 *
 * @param string $hook the current page name.
 */
function peachpay_enqueue_product_links_script( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'pp_product_links',
		peachpay_url( 'core/modules/product-links/admin/js/product-links.js' ),
		array(),
		peachpay_file_version( 'core/modules/product-links/admin/js/product-links.js' ),
		true
	);

	wp_localize_script(
		'pp_product_links',
		'pp_product_links_data',
		array(
			'URL' => peachpay_is_local_development_site() ? 'https://fast.peachpay.local' : 'https://fast.peachpay.app',
		)
	);
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_product_links_style' );

/**
 * Enque a stylesheet for Product links section.
 *
 * @param string $hook the current page name.
 */
function peachpay_enqueue_product_links_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'pp_product_links_style',
		plugin_dir_url( __FILE__ ) . 'style/product-links.css',
		array(),
		true
	);
}
