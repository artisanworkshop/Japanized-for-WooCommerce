<?php
/**
 * PeachPay Admin settings sidebar navigation HTML view.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/admin/views/utilities.php';

?>
<div class="peachpay-side-nav">
	<nav class="nav-tab-wrapper peachpay-accordion <?php echo esc_attr( peachpay_nav_is_gateway_page() ? 'no-active-tab' : '' ); ?>">
		<a class="peachpay-logo" href="<?php Peachpay_Admin::admin_settings_url(); ?>"></a>
		<?php peachpay_generate_nav_tab( 'peachpay', 'home', null, 'Dashboard' ); ?>
		<p style="margin-bottom: 0px; text-transform: uppercase;"><?php echo esc_html_e( 'Settings', 'peachpay-for-woocommerce' ); ?></p>
		<?php peachpay_generate_nav_tab( 'peachpay', 'payment', null, 'Payments', $has_subtabs = true ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'bot_protection', 'settings', 'Bot protection' ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'express_checkout', 'branding', 'Express checkout', $has_subtabs = true ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'settings', 'address_autocomplete', 'Address autocomplete' ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'currency', null, 'Currency' ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'field', 'billing', 'Field editor', $has_subtabs = true ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'related_products', null, 'Related products' ); ?>
		<p style="margin-bottom: 0px; text-transform: uppercase;"><?php echo esc_html_e( 'Analytics', 'peachpay-for-woocommerce' ); ?></p>
		<?php peachpay_generate_nav_tab( 'peachpay', 'payment_methods', 'analytics', 'Payment methods' ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'device_breakdown', 'analytics', 'Device breakdown' ); ?>
		<?php peachpay_generate_nav_tab( 'peachpay', 'abandoned_carts', 'analytics', 'Abandoned carts' ); ?>
		<p style="margin-bottom: 0px; text-transform: uppercase;"><?php echo esc_html_e( 'Account', 'peachpay-for-woocommerce' ); ?></p>
		<?php peachpay_generate_nav_tab( 'peachpay', 'data', 'account', 'Data' ); ?>
	</nav>
	<div class="side-nav-bottom-group">
		<?php peachpay_generate_top_nav_link( null, 'https://help.peachpay.app', 'docs-icon', 'Docs' ); ?>
		<?php peachpay_generate_top_nav_link( null, '#', 'support-icon', 'Support' ); ?>
		<?php peachpay_generate_top_nav_link( null, 'https://twitter.com/peachpayhq/', 'twitter-icon', 'Twitter' ); ?>
		<?php peachpay_premium_misc_link(); ?>
	</div>
</div>
<?php
