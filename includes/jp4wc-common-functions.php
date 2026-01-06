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
		 * @since 2.6.0
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
		// Check if WooCommerce Blocks is active.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			return false;
		}

		// Block-based checkout only available on WooCommerce 6.9.0 and above.
		if ( version_compare( WC()->version, '6.9.0', '<' ) ) {
			return false;
		}

		// Check if we're in a REST API request (Checkout Block uses Store API).
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
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

		// Check if the checkout page contains the checkout block.
		if ( function_exists( 'has_block' ) ) {
			if ( has_block( 'woocommerce/checkout', $checkout_post ) ) {
				return true;
			}
		}

		// Fallback: Check for block comment in content.
		if ( strpos( $checkout_post->post_content, '<!-- wp:woocommerce/checkout' ) !== false ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'jp4wc_has_orders_in_last_5_days' ) ) {
	/**
	 * Check if there are any orders in the last 5 days.
	 *
	 * @since 2.7.15
	 * @return bool True if orders exist, false otherwise.
	 */
	function jp4wc_has_orders_in_last_5_days() {
		$args = array(
			'limit'        => 1,
			'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending', 'wc-refunded' ),
			'date_created' => '>' . ( time() - ( 5 * DAY_IN_SECONDS ) ),
		);

		$orders = wc_get_orders( $args );

		return ! empty( $orders );
	}
}
