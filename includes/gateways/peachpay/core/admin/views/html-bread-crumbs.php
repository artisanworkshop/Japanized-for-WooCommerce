<?php
/**
 * PeachPay breadcrumbs HTML view.
 *
 * @var array $bread_crumbs The array of breadcrumbs to render.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

// PHPCS:ignore
$hide = isset( $_GET['tab'] ) && 'peachpay' === $_GET['tab'];

if ( ! $hide && isset( $bread_crumbs ) && is_array( $bread_crumbs ) ) : ?>
	<div id="peachpay-bread-crumbs">
		<div class="crumb root">
			<a href="<?php PeachPay_Admin::admin_settings_url(); ?>">
				<?php esc_html_e( 'PeachPay', 'peachpay-for-woocommerce' ); ?>
			</a>
		</div>

		<?php foreach ( $bread_crumbs as $crumb ) : ?>
			<?php if ( isset( $crumb['url'] ) ) : ?>
				<div class="crumb">
					<a href="<?php echo esc_url( $crumb['url'] ); ?>">
						<?php echo esc_html( $crumb['name'] ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="crumb active">
					<?php echo esc_html( $crumb['name'] ); ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
endif;
