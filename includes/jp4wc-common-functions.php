<?php
/**
 * Common Functions for WooCommerce for Japan
 *
 * This file contains common utility functions used throughout
 * the WooCommerce for Japan plugin.
 *
 * @package    Woocommerce_For_Japan
 * @subpackage Woocommerce_For_Japan/includes
 * @author     Artisan Workshop
 * @license    GPL-2.0+
 * @link       https://wc4jp-pro.work/
 * @since      2.6.0
 */

if ( ! function_exists( 'jp4wc_get_fee_tax_classes' ) ) {

	/**
	 * Get Tax class options.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	function jp4wc_get_fee_tax_classes() {
		$tax_class = array(
			'not-required' => __( 'Not Required', 'woocommerce-for-japan' ),
			'standard'     => __( 'Standard', 'woocommerce-for-japan' ),
		);

		$tax_class_options = WC_Tax::get_tax_classes();
		foreach ( $tax_class_options as $key => $options ) {
			$tax_class[ sanitize_title( $options ) ] = $options;
		}

		/**
		 * This hook is used to alter the tax classes.
		 *
		 * @since 5.3.0
		 * @param array $tax_class Tax classes.
		 */
		return apply_filters( 'jp4wc_tax_classes', $tax_class );
	}
}

if ( ! function_exists( 'jp4wc_is_using_checkout_blocks' ) ) {

	/**
	 * A function to determine if a WooCommerce Checkout Block is being used.
	 *
	 * @return bool true if you are using Checkout Block, false if not.
	 */
	function jp4wc_is_using_checkout_blocks() {
		// Block-based checkout only available on WooCommerce 6.9.0 and above.
		if ( version_compare( WC()->version, '6.9.0', '<' ) ) {
			return false;
		}

		// Get the checkout page ID from your WooCommerce settings.
		$checkout_page_id = wc_get_page_id( 'checkout' );

		if ( $checkout_page_id <= 0 ) {
			return false;
		}

		// Get the checkout page content.
		$checkout_post = get_post( $checkout_page_id );

		if ( ! $checkout_post || empty( $checkout_post->post_content ) ) {
			return false;
		}

		// Check if you are using a checkout block.
		// Check the block checkout identifier.
		$has_checkout_block = false;

		// Check if woocommerce/checkout block exists.
		if ( has_block( 'woocommerce/checkout', $checkout_post->post_content ) ) {
			$has_checkout_block = true;
		}

		if ( strpos( $checkout_post->post_content, '<!-- wp:woocommerce/checkout' ) !== false ) {
			$has_checkout_block = true;
		}

		return $has_checkout_block;
	}
}
