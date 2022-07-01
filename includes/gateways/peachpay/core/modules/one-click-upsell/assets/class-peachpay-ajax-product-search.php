<?php
/**
 * PeachPay One-Click-Upsell Product Search.
 *
 * @package PeachPay
 */

//phpcs:disable
/**
 * Referenced https://stackoverflow.com/questions/30973651/add-product-search-field-in-woo-commerce-product-page
 */
class PeachPay_AJAX_Product_Search {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}
	public function enqueue_admin() {
		wp_register_style( 'peachpay-select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css' );
		wp_enqueue_style( 'peachpay-select2-style' );
		wp_register_script( 'peachpay-select2-script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js', array( 'jquery' ) );
		wp_register_script( 'peachpay-script', plugins_url( 'peachpay_product_search_script.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_script( 'peachpay-select2-script' );
		wp_enqueue_script( 'peachpay-script' );
	}
} new PeachPay_AJAX_Product_Search();
