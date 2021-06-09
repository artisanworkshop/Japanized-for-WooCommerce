<?php
/**
 * Japanized for WooCommerce Uninstall
 *
 * @package woocommerce-for-japan
 * @category Core
 * @author Artisan Workshop
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

//delete option settings
delete_option('wc4jp-bankjp');
delete_option('woocommerce_bankjp_settings');
delete_option('wc4jp-postofficebank');
delete_option('woocommerce_postofficebankjp_settings');
delete_option('wc4jp-atstore');
delete_option('wc4jp-company-name');
delete_option('wc4jp-yomigana');
delete_option('woocommerce_cod_extra_charge_name');
delete_option('woocommerce_cod_extra_charge_amount');
delete_option('woocommerce_cod_extra_charge_max_cart_value');
delete_option('woocommerce_cod_extra_charge_calc_taxes');
delete_option('woocommerce_cod_settings');
