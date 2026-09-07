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
 * @since      2.6.0
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
	 * @version 2.7.15
	 * @since 2.6.0
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
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'jp4wc_reject_stale_gateway_fee' ), 10, 2 );
		}
		/**
		 * Register & Apply Gateway Fee for WooCommerce Blocks
		 *
		 * @since 2.6.0
		 */
		public static function jp4wc_register_wc_blocks() {
			if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
				return;
			}
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

			// This Store API extension endpoint is unauthenticated by design
			// (anonymous shoppers must be able to update their cart), so
			// $data['gateway_id'] is fully client-controlled. Only trust it
			// when it names a gateway actually available on this site;
			// otherwise fall back to WooCommerce's own chosen_payment_method
			// (see jp4wc_calculate_order_totals()) the same way an empty
			// value already does — a bogus value must never be able to
			// suppress the COD/COD2 surcharge for an order that ultimately
			// uses a real gateway.
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			if ( empty( $data['gateway_id'] ) || ! isset( $available_gateways[ $data['gateway_id'] ] ) ) {
				WC()->session->__unset( 'jp4wc_gateway_id' );
				return;
			}

			WC()->session->set( 'jp4wc_gateway_id', $data['gateway_id'] );
		}

		/**
		 * Reject a place-order request whose fees were calculated for a
		 * different gateway than the one actually being submitted.
		 *
		 * See jp4wc_calculate_order_totals() in class-jp4wc-cod-fee.php: it
		 * computes the cart's COD/COD2 surcharge from the jp4wc_gateway_id session value
		 * during Store API cart-total calculation — which, for the final
		 * place-order POST, always runs *before* WooCommerce sets the order's
		 * real payment method from this request (see
		 * WC_Store_API's CheckoutTrait::update_order_from_request(), which
		 * fires the hook this method is attached to only after doing so).
		 * A client can therefore set jp4wc_gateway_id to any other real,
		 * available gateway via the jp4wc-add-gateway-fee extension endpoint
		 * (only validated against the site's gateway list, not against what
		 * will actually be submitted — see add_gateway_fee_for_wc_blocks()),
		 * then place the order with a different payment_method, and the
		 * surcharge that should apply to the real gateway is silently
		 * dropped. Reject the request instead of risking an order placed
		 * with an incorrect total; a normal customer whose selection is in
		 * sync never hits this, since the jp4wc-add-gateway-fee endpoint is
		 * called again on every payment method change before submission.
		 *
		 * @since 2.9.16
		 *
		 * @param \WC_Order        $order   Order being placed.
		 * @param \WP_REST_Request $request Store API request.
		 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When the fee basis and the submitted gateway disagree.
		 */
		public static function jp4wc_reject_stale_gateway_fee( $order, $request ) {
			if ( 'POST' !== $request->get_method() ) {
				// Only the final place-order submission has already
				// calculated fees from a (potentially stale) session value
				// by the time this hook fires; the draft-update (PUT/PATCH)
				// flow sets the payment method *before* calculating fees,
				// so there is nothing to validate here yet.
				return;
			}

			$fee_basis_gateway_id = WC()->session->get( 'jp4wc_gateway_id' );
			if ( empty( $fee_basis_gateway_id ) ) {
				// No jp4wc-specific override was in play — fees were
				// calculated from chosen_payment_method, which this same
				// request just set to the real gateway. Nothing to check.
				return;
			}

			if ( $fee_basis_gateway_id === $order->get_payment_method() ) {
				return;
			}

			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'jp4wc_gateway_fee_mismatch',
				esc_html__( 'Your selected payment method changed. Please refresh and try again.', 'woocommerce-for-japan' ),
				409
			);
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
			if ( ! is_checkout() || ! jp4wc_is_using_checkout_blocks() ) {
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
