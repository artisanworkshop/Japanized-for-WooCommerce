<?php 
	global $woocommerce;
	if(isset($_GET['tab'])){
		$section = 'jp4wc_'.$_GET['tab'];
	}else{
		$section = 'jp4wc_setting';
		$_GET['tab'] = 'setting';
	}
	$title = array(
		'setting' => __( 'General Setting', 'woocommerce-for-japan' ),
		'shipment' => __( 'Shipment Setting', 'woocommerce-for-japan' ),
		'payment' => __( 'Payment Setting', 'woocommerce-for-japan' ),
        'law' => __( 'Notation based on Specified Commercial Transaction Law', 'woocommerce-for-japan' ),
		'affiliate' => __( 'Affiliate Setting', 'woocommerce-for-japan' ),
	);
    $title = apply_filters( 'wc4jp_admin_setting_title', $title );
	?>
<div class="wrap">
	<h2><?php echo $title[$_GET['tab']];?></h2>
	<div class="jp4wc-settings metabox-holder">
		<div class="jp4wc-sidebar">
			<div class="jp4wc-credits">
				<h3 class="hndle"><?php echo __( 'Japanized for WooCommerce', 'woocommerce-for-japan' ) . ' ' . JP4WC_VERSION;?></h3>
				<div class="inside">
					<?php $this->jp4wc_plugin->jp4wc_pro_notice('https://wc4jp-pro.work/');?>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_update_notice();?>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_community_info();?>
					<?php if ( ! get_option( 'wc4jp_admin_footer_text_rated' ) ) :?>
					<hr />
					<h4 class="inner"><?php echo __( 'Do you like this plugin?', 'woocommerce-for-japan' );?></h4>
					<p class="inner"><a href="https://wordpress.org/support/plugin/woocommerce-for-japan/reviews/#postform" target="_blank" title="' . __( 'Rate it 5', 'woocommerce-for-japan' ) . '"><?php echo __( 'Rate it 5', 'woocommerce-for-japan' )?> </a><?php echo __( 'on WordPress.org', 'woocommerce-for-japan' ); ?><br />
					</p>
					<?php endif;?>
					<hr />
					<?php $this->jp4wc_plugin->jp4wc_author_info(JP4WC_URL_PATH);?>
				</div>
			</div>
		</div>
		<form id="jp4wc-setting-form" method="post" action="">
			<div id="main-sortables" class="meta-box-sortables ui-sortable">
<?php
	//Display Setting Screen
	settings_fields( $section );
	$this->jp4wc_plugin->do_settings_sections( $section );
?>
			<p class="submit">
<?php
	submit_button( '', 'primary', 'save_'.$section, false );
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
		postboxes.add_postbox_toggles('<?php echo $section; ?>');
	});
	//]]>
	</script>
</div>