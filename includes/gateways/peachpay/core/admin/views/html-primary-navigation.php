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
