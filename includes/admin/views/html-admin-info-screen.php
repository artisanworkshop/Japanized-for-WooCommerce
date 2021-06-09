<div class="wrap">
	<h2><?php echo __( 'Information of Our Support', 'woocommerce-for-japan' );?></h2>
	<p><b><?php echo __('Sorry, Mainly Japanese and some English support Only', 'woocommerce-for-japan');?></b></p>
	<div>
		<div class="wc4jp-informations metabox-holder">
	<?php
	//Display Setting Screen
	settings_fields( 'jp4wc_informations' );
	$this->jp4wc_plugin->do_settings_sections( 'jp4wc_informations' );
?>
		</div>
		</form>
		<div class="clear"></div>
	</div>
	<p><?php echo sprintf(__('The currently working framework version is %s.', 'woocommerce-for-japan'), JP4WC_FRAMEWORK_VERSION);?><br />
	</p>
	<p>
	<?php echo __('Nice to meet you on Facebook page!', 'woocommerce-for-japan');?><br />
	<a href="https://www.facebook.com/wcjapan" target="_blank"><?php echo __('Woo Japan Wave Facebook page!', 'woocommerce-for-japan');?></a>
	</p>
</div>
