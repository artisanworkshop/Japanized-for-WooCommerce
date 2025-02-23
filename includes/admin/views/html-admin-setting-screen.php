<?php
/**
 * Admin View: Settings
 *
 * @package WooCommerce
 */

global $woocommerce;
if ( isset( $_GET['tab'] ) ) {
	$current_tab = wc_clean( wp_unslash( $_GET['tab'] ) );// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$section     = 'jp4wc_' . $current_tab;
} else {
	$section     = 'jp4wc_setting';
	$current_tab = 'setting';
}
$current_title = array(
	'setting'   => __( 'General Setting', 'woocommerce-for-japan' ),
	'shipment'  => __( 'Shipment Setting', 'woocommerce-for-japan' ),
	'payment'   => __( 'Payment Setting', 'woocommerce-for-japan' ),
	'law'       => __( 'Notation based on Specified Commercial Transaction Law', 'woocommerce-for-japan' ),
	'affiliate' => __( 'Affiliate Setting', 'woocommerce-for-japan' ),
);
$current_title = apply_filters( 'wc4jp_admin_setting_title', $current_title );
if ( ! isset( $current_title[ $current_tab ] ) ) {
	$current_title[ $current_tab ] = __( 'The URL for this page is incorrect.', 'woocommerce-for-japan' );
}
?>
<div class="wrap">
	<h2><?php echo esc_html( $current_title[ $current_tab ] ); ?></h2>
	<div class="jp4wc-settings metabox-holder">
		<div class="jp4wc-sidebar">
			<div class="jp4wc-credits">
				<h3 class="hndle">
				<?php
				esc_html_e( 'Japanized for WooCommerce', 'woocommerce-for-japan' );
				echo ' ' . esc_html( JP4WC_VERSION );
				?>
				</h3>
				<div class="inside">
					<?php $this->jp4wc_plugin->jp4wc_pro_notice( 'https://wc4jp-pro.work/' ); ?>
					<hr />
					<h4 class="inner"><?php esc_html_e( 'Security measures for WooCommerce', 'woocommerce-for-japan' ); ?></h4>
					<p class="inner">
						<?php
						$product_link_url = 'https://wc4jp-pro.work/about-security-service/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=maintenance-support';

						/* translators: %s: URL */
						printf( esc_html__( 'One the security, latest update is the most important thing. The credit card security guidelines that will be established from April 2025 are also important. If you need site maintenance support, please consider about <a href="%s" target="_blank" title="Security measures for WooCommerce">Security measures for WooCommerce</a>', 'woocommerce-for-japan' ), esc_url( $product_link_url ) );
						?>
					</p>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_community_info(); ?>
					<?php if ( ! get_option( 'wc4jp_admin_footer_text_rated' ) ) : ?>
						<hr />
						<h4 class="inner"><?php esc_html_e( 'Do you like this plugin?', 'woocommerce-for-japan' ); ?></h4>
						<p class="inner"><a href="https://wordpress.org/support/plugin/woocommerce-for-japan/reviews/#postform" target="_blank" title="<?php esc_attr_e( 'Rate it 5', 'woocommerce-for-japan' ); ?>"><?php esc_html_e( 'Rate it 5', 'woocommerce-for-japan' ); ?> </a><?php esc_html_e( 'on WordPress.org', 'woocommerce-for-japan' ); ?><br />
						</p>
					<?php endif; ?>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_author_info( JP4WC_URL_PATH ); ?>
				</div>
			</div>
		</div>
		<form id="jp4wc-setting-form" method="post" action="">
			<div id="main-sortables" class="meta-box-sortables ui-sortable">
				<?php
				// Display Setting Screen.
				settings_fields( $section );
				$this->jp4wc_plugin->do_settings_sections( $section );
				?>
				<p class="submit">
					<?php
					submit_button( '', 'primary', 'save_' . $section, false );
					?>
				</p>
			</div>
		</form>
		<div class="clear"></div>
	</div>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function ($) {
			// close postboxes that should be closed
			$('if-js-closed').removeClass('if-js-closed').addClass('closed');
			// postboxes setup
			postboxes.add_postbox_toggles('<?php echo esc_js( $section ); ?>');
		});
		//]]>
	</script>
</div>
