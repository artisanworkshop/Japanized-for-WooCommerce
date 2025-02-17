<?php
/**
 * Admin information screen view template.
 *
 * Displays support information in the WooCommerce Japan admin panel.
 *
 * @package Japanized-for-WooCommerce
 * @since 1.0.0
 * @version 1.0.0
 */

?>
	<div class="wrap">
	<h2><?php esc_html_e( 'Information of Our Support', 'woocommerce-for-japan' ); ?></h2>
	<p><b><?php esc_html_e( 'Sorry, Mainly Japanese and some English support Only', 'woocommerce-for-japan' ); ?></b></p>
	<div>
		<div class="wc4jp-informations metabox-holder">
	<?php
	// Display Setting Screen.
	settings_fields( 'jp4wc_informations' );
	$this->jp4wc_plugin->do_settings_sections( 'jp4wc_informations' );
	?>
		</div>
		</form>
		<div class="clear"></div>
	</div>
	<p>
	<?php
	/* translators: 1) Japanized for WooCommerce framework version */
	printf( esc_html__( 'The currently working framework version is %s.', 'woocommerce-for-japan' ), esc_html( JP4WC_FRAMEWORK_VERSION ) );
	?>
	<br />
	</p>
	<p>
	<?php esc_html_e( 'Nice to meet you on Facebook page!', 'woocommerce-for-japan' ); ?><br />
	<a href="https://www.facebook.com/wcjapan" target="_blank"><?php esc_html_e( 'Japanized for WooCommerce Facebook page!', 'woocommerce-for-japan' ); ?></a>
	</p>
</div>
