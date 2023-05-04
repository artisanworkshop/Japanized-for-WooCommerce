<?php
/**
 * PeachPay express checkout settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}
require_once PEACHPAY_ABSPATH . 'core/modules/express-checkout/sections/branding.php';
require_once PEACHPAY_ABSPATH . 'core/modules/express-checkout/sections/checkout-window.php';
require_once PEACHPAY_ABSPATH . 'core/modules/express-checkout/sections/product-recommendations.php';
require_once PEACHPAY_ABSPATH . 'core/modules/express-checkout/sections/checkout-button.php';
require_once PEACHPAY_ABSPATH . 'core/modules/express-checkout/sections/advanced.php';

/**
 * Adds the express checkout settings section.
 */
function peachpay_express_checkout() {
	add_settings_section(
		'peachpay_express_checkout_render',
		'',
		'peachpay_express_checkout_render_cb',
		'peachpay'
	);
}

function peachpay_express_checkout_render_cb() { 	//phpcs:disable
	$section = isset($_GET['section']) ? wp_unslash($_GET['section']) : 'branding';
	//phpcs:enable

	?>
	<div class="pp-section-nav-container">
		<a class="<?php echo 'branding' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-inactive'; ?>" href="<?php echo esc_url( remove_query_arg( 'settings-updated', add_query_arg( 'section', 'branding' ) ) ); ?>" style="text-decoration:none;"> <?php esc_html_e( 'Branding', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a class="<?php echo 'button' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-inactive'; ?>" href="<?php echo esc_url( remove_query_arg( 'settings-updated', add_query_arg( 'section', 'button' ) ) ); ?>" style="text-decoration:none;"> <?php esc_html_e( 'Checkout button', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a class="<?php echo 'window' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-inactive'; ?>" href="<?php echo esc_url( remove_query_arg( 'settings-updated', add_query_arg( 'section', 'window' ) ) ); ?>" style="text-decoration:none;"> <?php esc_html_e( 'Checkout window', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a class="<?php echo 'product_recommendations' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-inactive'; ?>" href="<?php echo esc_url( remove_query_arg( 'settings-updated', add_query_arg( 'section', 'product_recommendations' ) ) ); ?>" style="text-decoration:none;"> <?php esc_html_e( 'Product recommendations', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a class="<?php echo 'advanced' === $section ? 'pp-sub-nav-link-active' : 'pp-sub-nav-inactive'; ?>" href="<?php echo esc_url( remove_query_arg( 'settings-updated', add_query_arg( 'section', 'advanced' ) ) ); ?>" style="text-decoration:none;"> <?php esc_html_e( 'Advanced', 'peachpay-for-woocommerce' ); ?>
		</a>
	</div>
	<?php
	peachpay_paginate_express_checkout_section( $section );
}

/**
 * Handles pagination within a settings page.
 *
 * @param string $section The current section in the Express checkout settings.
 */
function peachpay_paginate_express_checkout_section( $section ) {
	if ( 'branding' === $section ) {
		peachpay_express_checkout_branding_render();
	}
	if ( 'window' === $section ) {
		peachpay_express_checkout_window_render();
	}
	if ( 'product_recommendations' === $section ) {
		peachpay_express_checkout_product_recommendations_render();
	}
	if ( 'button' === $section ) {
		peachpay_express_checkout_button_render();
	}
	if ( 'advanced' === $section ) {
		peachpay_express_checkout_advanced_render();
	}
}

/**
 * Renders save button.
 */
function peachpay_save_button() {
	?>
<div class="pp-save-button-section">
	<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
</div>
	<?php
}
