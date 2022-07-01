<?php
/**
 * Migrates old JS custom CSS to the new advanced CSS plugin setting.
 *
 * This migration can be deleted after all below merchants have updated the plugin.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Migrates existing advanced CSS to the new built in plugin setting.
 */
function peachpay_migrate_advanced_css() {
	$advanced_settings = get_option( 'peachpay_advanced_options', array() );

	if ( peachpay_is_site( 'katekos.com' ) && ! array_key_exists( 'custom_button_css', $advanced_settings ) ) {
		$advanced_settings['custom_button_css'] = '#pp-button { text-transform: uppercase !important; font-family: Raleway !important; font-size: 20px; font-weight: 400; padding: 15px; } #pp-button.pp-cart-page { padding: 10px 0; }';
		update_option( 'peachpay_advanced_options', $advanced_settings );
		return;
	}

	if ( peachpay_is_site( 'rahimsapphire.co.uk' ) && ! array_key_exists( 'custom_button_css', $advanced_settings ) ) {
		$advanced_settings['custom_button_css'] = '#pp-button { text-transform: none !important; } #pp-button-mini {  padding-top: 19px; padding-bottom: 16px; font-size: 100%; transition: all .2s linear; text-transform: none !important; }';

		$advanced_settings['custom_button_class'] = 'rahimsapphire';

		update_option( 'peachpay_advanced_options', $advanced_settings );
		return;
	}

	if ( peachpay_is_site( 'www.blazecandles.co ' ) && ! array_key_exists( 'custom_button_css', $advanced_settings ) ) {
		$advanced_settings['custom_button_css'] = '#pp-button.pp-cart-page { font-weight: normal; background-color: none !important; color: black !important; border: 1px solid #121212; border-radius: 100px; }';

		$advanced_settings['custom_button_class'] = 'blazecandle';

		update_option( 'peachpay_advanced_options', $advanced_settings );
		return;
	}
}
