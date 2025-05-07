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
					<h4 class="inner"><?php esc_html_e( 'For those who are having trouble with WooCommerce', 'woocommerce-for-japan' ); ?></h4>
					<p class="inner"><?php esc_html_e( 'We are currently offering a 15-minute free Zoom consultation. A professional will check your current situation and propose the best course of action.', 'woocommerce-for-japan' ); ?><br/><br/>
					<!-- Google Calendar Appointment Scheduling begin -->
					<link href="https://calendar.google.com/calendar/scheduling-button-script.css" rel="stylesheet">
					<script src="https://calendar.google.com/calendar/scheduling-button-script.js" async></script>
					<script>
(function() {
	var target = document.currentScript;
	window.addEventListener('load', function() {
	calendar.schedulingButton.load({
		url: 'https://calendar.google.com/calendar/appointments/AcZssZ3mnRQcAL8LU9tPWptKCm05Zge58Oy2jffVIIQ=?gv=true',
		color: '#039BE5',
		label: "15\u5206\u7121\u6599 Zoom \u76F8\u8AC7\u306B\u7533\u3057\u8FBC\u3080",
		target,
	});
	});
})();
					</script>
					<!-- end Google Calendar Appointment Scheduling -->
					</p>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_pro_notice( 'https://wc4jp-pro.work/' ); ?>
					<hr />
					<h4 class="inner"><?php esc_html_e( 'Security measures for WooCommerce', 'woocommerce-for-japan' ); ?></h4>
					<p class="inner">
						<?php
						$product_link_url = 'https://wc4jp-pro.work/about-security-service/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=maintenance-support';
						/* translators: %s: URL */
						$explain_product = __( 'One the security, latest update is the most important thing. The credit card security guidelines that will be established from April 2025 are also important. If you need site maintenance support, please consider about <a href="%s" target="_blank" title="Security measures for WooCommerce">Security measures for WooCommerce</a>', 'woocommerce-for-japan' );
						printf( wp_kses_post( $explain_product ), esc_url( $product_link_url ) );
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
