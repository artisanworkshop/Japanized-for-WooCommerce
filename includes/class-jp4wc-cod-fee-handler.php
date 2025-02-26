<?php
/**
 * COD Fee Handler for Japanese Woocommerce
 *
 * This file contains the class that handles Cash on Delivery (COD) fees
 * specifically for the Japanese market in WooCommerce.
 *
 * @package    Woocommerce_For_Japan
 * @subpackage Woocommerce_For_Japan/includes
 * @author     Artisan Workshop
 * @since      1.0.0
 * @license    GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'JP4WC_COD_Fee_Handler' ) ) {
	/**
	 * Handles Cash on Delivery (COD) fees for WooCommerce Japan.
	 *
	 * This class manages the calculation and application of COD fees
	 * for orders using Cash on Delivery payment method in the Japanese market.
	 *
	 * @package WooCommerce for Japan
	 * @since 1.0.0
	 */
	class JP4WC_COD_Fee_Handler {
		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Add Gateway Fee in Cart/Checkout for block.
			if ( ! is_admin() ) {
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'jp4wc_block_external_js_files' ), 99 );
			}
			add_action( 'init', array( __CLASS__, 'jp4wc_register_wc_blocks' ), 10 );
		}
		/**
		 * Register & Apply Gateway Fee for WooCommerce Blocks
		 *
		 * @since 2.6.0
		 */
		public static function jp4wc_register_wc_blocks() {
			woocommerce_store_api_register_update_callback(
				array(
					'namespace' => 'jp4wc-add-gateway-fee',
					'callback'  => function ( $data ) {
						self::add_gateway_fee_for_wc_blocks( $data );
					},
				)
			);
		}

		/**
		 * Add Gateway Fee for WooCommerce Blocks
		 *
		 * @param array $data Data.
		 * @since 5.5.0
		 */
		public static function add_gateway_fee_for_wc_blocks( $data ) {

			if ( 'add-fee' !== $data['action'] ) {
				return;
			}

			if ( empty( $data['gateway_id'] ) ) {
				WC()->session->__unset( 'jp4wc_gateway_id' );
				return;
			}

			WC()->session->set( 'jp4wc_gateway_id', $data['gateway_id'] );
		}

		/**
		 * Enqueues external JavaScript files required for COD fee functionality for Checkout Block.
		 *
		 * This function is responsible for loading any JavaScript files needed
		 * for the Cash on Delivery fee calculation and display features.
		 *
		 * @access public
		 * @return void
		 */
		public static function jp4wc_block_external_js_files() {
			if ( ! is_checkout() ) {
				return;
			}
			$enqueue_array = array(
				'jp4wc-wc-blocks' => array(
					'callable' => array( 'JP4WC_COD_Fee_Handler', 'jp4wc_blocks_script' ),
					'restrict' => true,
				),
			);
			$enqueue_array = apply_filters( 'jp4wc_cod_enqueue_scripts', $enqueue_array );

			if ( ! is_array( $enqueue_array ) || empty( $enqueue_array ) ) {
				return;
			}
			foreach ( $enqueue_array as $key => $enqueue ) {
				if ( ! is_array( $enqueue ) || empty( $enqueue ) ) {
					continue;
				}

				if ( $enqueue['restrict'] ) {
					call_user_func_array( $enqueue['callable'], array() );
				}
			}
		}

		/**
		 * Registers and enqueues scripts for WooCommerce blocks functionality.
		 *
		 * This function is responsible for handling the JavaScript scripts needed
		 * for WooCommerce blocks integration, specifically for COD fee features.
		 *
		 * @access public
		 * @return void
		 */
		public static function jp4wc_blocks_script() {

			wp_register_script(
				'jquery-modal',
				JP4WC_URL_PATH . 'assets/js/jquery.modal.min.js',
				array( 'jquery' ),
				JP4WC_VERSION
			);
			wp_enqueue_script(
				'jp4wc-cod-wc-blocks',
				JP4WC_URL_PATH . 'assets/js/jp4wc-cod-wc-blocks.js',
				array( 'jquery', 'jquery-modal', 'wc-blocks-checkout' ),
				JP4WC_VERSION,
				true
			);

			$gatewayfee_enabled = 'yes';
			wp_localize_script(
				'jp4wc-cod-wc-blocks',
				'jp4wc_cod_blocks_param',
				array(
					'is_gateway_fee_enabled' => $gatewayfee_enabled,
					'is_checkout'            => is_checkout(),
					'ajaxurl'                => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}

	JP4WC_COD_Fee_Handler::init();
}
