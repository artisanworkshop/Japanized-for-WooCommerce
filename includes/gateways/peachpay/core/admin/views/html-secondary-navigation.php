<?php
/**
 * PeachPay Admin settings secondary navigation HTML view.
 *
 * @var PeachPay_Admin_Section $admin_section The section this navigation belongs to.
 * @var PeachPay_Admin_Tab[] $admin_tab_views The array of tabs to render links for.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="peachpay-secondary-navigation">
	<div class="pp-flex-row pp-gap-12 pp-section-nav-container">
		<?php foreach ( $admin_tab_views as $admin_tab ) { ?>
			<div class="pp-sub-nav-button <?php echo ( $admin_tab->is_active() ? 'pp-sub-nav-button-active' : '' ); ?>">
				<a href="<?php echo $admin_section->get_url( $admin_tab->get_tab() ); // PHPCS:ignore ?>">
					<?php echo esc_html( $admin_tab->get_title() ); ?>
				</a>
			</div>
		<?php } ?>
	</div>
	<hr>
</div>
