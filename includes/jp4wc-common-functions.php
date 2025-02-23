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
