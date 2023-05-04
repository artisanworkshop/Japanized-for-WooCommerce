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
	<?php
	if ( peachpay_nav_is_analytics_page() ) {
		?>
		<nav class="nav-tab-wrapper peachpay-accordion">
			<a class="peachpay-logo" href="<?php Peachpay_Admin::admin_settings_url(); ?>"></a>
			<?php
			foreach ( $admin_tab_views as $admin_tab ) {
				peachpay_generate_nav_tab( 'peachpay', $admin_tab->get_tab(), 'analytics', $admin_tab->get_title() );
			}
			?>
		</nav>
		<div class="side-nav-bottom-group">
			<?php peachpay_generate_top_nav_link( null, 'https://help.peachpay.app', 'docs-icon', 'Docs' ); ?>
			<?php peachpay_generate_top_nav_link( null, '#', 'support-icon', 'Support' ); ?>
			<?php peachpay_generate_top_nav_link( null, 'https://twitter.com/peachpayhq/', 'twitter-icon', 'Twitter' ); ?>
			<?php peachpay_premium_misc_link(); ?>
		</div>
	<?php } else { ?>
		<nav class="nav-tab-wrapper peachpay-accordion <?php echo esc_attr( peachpay_nav_is_gateway_page() ? 'no-active-tab' : '' ); ?>">
			<a class="peachpay-logo" href="<?php Peachpay_Admin::admin_settings_url(); ?>"></a>
			<?php peachpay_generate_nav_tab( 'peachpay', 'payment', null, 'Payments', $has_subtabs = true ); ?>
			<?php peachpay_generate_nav_tab( 'peachpay', 'currency', null, 'Currency' ); ?>
			<?php peachpay_generate_nav_tab( 'peachpay', 'field', 'billing', 'Field editor', $has_subtabs = true ); ?>
			<?php peachpay_generate_nav_tab( 'peachpay', 'related_products', null, 'Related products' ); ?>
			<?php peachpay_generate_nav_tab( 'peachpay', 'express_checkout', 'branding', 'Express checkout', $has_subtabs = true ); ?>
		</nav>
		<div class="side-nav-bottom-group">
			<?php peachpay_generate_top_nav_link( null, 'https://help.peachpay.app', 'docs-icon', 'Docs' ); ?>
			<?php peachpay_generate_top_nav_link( null, '#', 'support-icon', 'Support' ); ?>
			<?php peachpay_generate_top_nav_link( null, 'https://twitter.com/peachpayhq/', 'twitter-icon', 'Twitter' ); ?>
			<?php peachpay_premium_misc_link(); ?>
		</div>
		<?php
	}
	?>
</div>
<?php
