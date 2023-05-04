<?php
/**
 * PeachPay Admin settings primary navigation HTML view.
 *
 * @var array $bread_crumbs The array of breadcrumbs passed to the breadcrumb view.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/admin/views/utilities.php';

?>
<div id="peachpay-nav" class="col">
	<div class='peachpay-header'>
		<div class="peachpay-heading">
			<div class="left">
				<div class="hamburger-menu icon"></div>
				<a class="peachpay-logo" href="<?php Peachpay_Admin::admin_settings_url(); ?>"></a>
				<div class="flex-col gap-8 peachpay-accordion">
					<div class="top-nav-link-group accordion-tab">
						<?php peachpay_generate_top_nav_link( 'dashboard', PeachPay_Admin::admin_settings_url( 'peachpay', 'home', '', '', false ), 'dashboard-icon', 'Dashboard' ); ?>
						<?php peachpay_generate_top_nav_link( 'settings', PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '', false ), 'settings-icon', 'Settings' ); ?>
						<?php peachpay_generate_top_nav_link( 'analytics', PeachPay_Admin::admin_settings_url( 'peachpay', 'payment_methods', 'analytics', '', false ), 'analytics-icon', 'Analytics' ); ?>
					</div>
					<?php peachpay_top_nav_dropdown(); ?>
				</div>
			</div>
			<div class="right" style="<?php echo esc_attr( ! peachpay_premium_status() ? '' : 'margin-right: -6px;' ); ?>">
				<?php peachpay_generate_top_nav_link( null, 'https://help.peachpay.app', 'docs-icon', 'Docs' ); ?>
				<?php peachpay_generate_top_nav_link( null, '#', 'support-icon', 'Support' ); ?>
				<?php peachpay_generate_top_nav_link( null, 'https://twitter.com/peachpayhq/', 'twitter-icon', 'Twitter' ); ?>
				<?php peachpay_premium_misc_link(); ?>
			</div>
		</div>
		<?php require PeachPay::get_plugin_path() . '/core/admin/views/html-bread-crumbs.php'; ?>
	</div>
</div>
<?php
