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
		 * Payment method named by the Store API checkout request being served.
		 *
		 * Null outside such a request, and when the request names none.
		 *
		 * @since 2.9.17
		 * @var string|null
		 */
		private static $request_payment_method = null;

		/**
		 * Gateway the gateway fee was last calculated for.
		 *
		 * A Store API checkout request starts with null, so inside one this
		 * is the gateway that request itself calculated the fee for, or null
		 * when it has not calculated it.
		 *
		 * @since 2.9.17
		 * @var string|null
		 */
		private static $fee_basis_gateway_id = null;

		/**
		 * Values of the two properties above as they were before each Store
		 * API checkout request that is currently being served.
		 *
		 * @since 2.9.17
		 * @var array<int, array{0: string|null, 1: string|null}>
		 */
		private static $request_state_stack = array();

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Add Gateway Fee in Cart/Checkout for block.
			if ( ! is_admin() ) {
				add_action( 'wp_enqueue_scripts', array( __CLASS__, 'jp4wc_block_external_js_files' ), 99 );
			}
			add_action( 'init', array( __CLASS__, 'jp4wc_register_wc_blocks' ), 10 );
			add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'jp4wc_capture_checkout_payment_method' ), 10, 3 );
			add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'jp4wc_release_checkout_payment_method' ), 10, 3 );
			// The draft-order guard runs first so that, should both guards ever
			// reject the same request, the answer says what to do rather than
			// to refresh and try again.
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'jp4wc_reject_cod_payment_of_draft_order' ), 10, 2 );
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( __CLASS__, 'jp4wc_reject_stale_gateway_fee' ), 10, 2 );
			add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'jp4wc_hide_cod_on_draft_order_pay_page' ) );
		}

		/**
		 * Gateways whose fee this plugin calculates at checkout.
		 *
		 * @since 2.9.17
		 *
		 * @return string[]
		 */
		private static function get_fee_gateway_ids() {
			return array( 'cod', 'cod2' );
		}

		/**
		 * Whether an order is a Checkout block draft (`checkout-draft`).
		 *
		 * WooCommerce lets such an order be paid like a pending one, but its
		 * fees are only calculated when it is placed from the checkout, from
		 * the cart. Paying it anywhere else with a gateway that carries a fee
		 * would place a COD/COD2 order without the surcharge.
		 *
		 * @since 2.9.17
		 *
		 * @param mixed $order Order, or anything else.
		 * @return bool
		 */
		private static function is_draft_order( $order ) {
			return $order instanceof \WC_Order && 'checkout-draft' === $order->get_status();
		}

		/**
		 * Reject paying a draft order with COD/COD2 through the Store API's
		 * pay-for-order route (`/checkout/<id>`).
		 *
		 * That route does not recalculate cart fees, so the surcharge the
		 * gateway carries would be missing from the order. The checkout route
		 * (`/checkout`) places the same draft with its fees calculated from the
		 * cart, which is where the Checkout block sends it.
		 *
		 * @since 2.9.17
		 *
		 * @param \WC_Order        $order   Order being paid.
		 * @param \WP_REST_Request $request Store API request.
		 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException When a draft order is paid with COD/COD2 outside the checkout.
		 */
		public static function jp4wc_reject_cod_payment_of_draft_order( $order, $request ) {
			if ( 'POST' !== $request->get_method() || 1 !== preg_match( '#^/wc/store(?:/v1)?/checkout/\d+#i', $request->get_route() ) ) {
				return;
			}

			if ( ! self::is_draft_order( $order ) || ! in_array( $order->get_payment_method(), self::get_fee_gateway_ids(), true ) ) {
				return;
			}

			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
				'jp4wc_cod_draft_order_payment',
				esc_html__( 'To pay by cash on delivery, please place this order from the checkout page.', 'woocommerce-for-japan' ),
				409
			);
		}

		/**
		 * Leave COD/COD2 out of the payment methods offered on the classic
		 * order-pay page for a draft order, for the same reason as above.
		 *
		 * The pay form is validated against the same list, so the gateways
		 * cannot be submitted either.
		 *
		 * @since 2.9.17
		 *
		 * @param array<string, \WC_Payment_Gateway> $gateways Available gateways.
		 * @return array<string, \WC_Payment_Gateway>
		 */
		public static function jp4wc_hide_cod_on_draft_order_pay_page( $gateways ) {
			if ( ! function_exists( 'is_checkout_pay_page' ) || ! is_checkout_pay_page() ) {
				return $gateways;
			}

			global $wp;
			$order_id = isset( $wp->query_vars['order-pay'] ) ? absint( $wp->query_vars['order-pay'] ) : 0;
			if ( ! $order_id || ! self::is_draft_order( wc_get_order( $order_id ) ) ) {
				return $gateways;
			}

			foreach ( self::get_fee_gateway_ids() as $gateway_id ) {
				unset( $gateways[ $gateway_id ] );
			}
			return $gateways;
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
		 * Records the payment method the shopper just selected so the cart
		 * returned by this same request already carries the right fee.
		 *
		 * The selection is written to WooCommerce's own `chosen_payment_method`
		 * session key — the key the Checkout block's own payment-method update
		 * (PUT /wc/store/v1/checkout, WooCommerce 9.8+) writes about a second
		 * later, and the only one jp4wc_calculate_order_totals() reads. Up to
		 * 2.9.16 this callback kept the selection in a separate
		 * `jp4wc_gateway_id` key that took priority over
		 * `chosen_payment_method`. The two requests overlap on a slow server,
		 * and WooCommerce saves the session as a single row, so each could
		 * calculate from — and then overwrite the session with — the other's
		 * stale value: the fee stayed on screen after switching away from COD,
		 * or disappeared after switching to it.
		 *
		 * @param array $data Data.
		 * @since 5.5.0
		 */
		public static function add_gateway_fee_for_wc_blocks( $data ) {

			if ( 'add-fee' !== $data['action'] ) {
				return;
			}

			// Drop the key earlier versions kept the selection in.
			WC()->session->__unset( 'jp4wc_gateway_id' );

			// This Store API extension endpoint is unauthenticated by design
			// (anonymous shoppers must be able to update their cart), so
			// $data['gateway_id'] is fully client-controlled. Only trust it
			// when it names a gateway actually available on this site;
			// otherwise leave WooCommerce's chosen_payment_method as it is —
			// a bogus value must never be able to suppress the COD/COD2
			// surcharge for an order that ultimately uses a real gateway.
			// is_string() must run before the array-offset lookup below: a
			// non-empty array/object for gateway_id (unrestricted client
			// input on this unauthenticated endpoint) is not caught by
			// empty(), and using it as an array offset is a TypeError, not
			// a false isset() — it would 500 instead of being ignored.
			if ( empty( $data['gateway_id'] ) || ! is_string( $data['gateway_id'] ) ) {
				return;
			}

			// WC()->payment_gateways can be null depending on initialization
			// order (guarded the same way in jp4wc_calculate_order_totals(),
			// see class-jp4wc-cod-fee.php) — this Store API callback can run
			// before it's set up.
			$available_gateways = WC()->payment_gateways ? WC()->payment_gateways->get_available_payment_gateways() : array();
			if ( ! isset( $available_gateways[ $data['gateway_id'] ] ) ) {
				return;
			}

			WC()->session->set( 'chosen_payment_method', $data['gateway_id'] );
		}

		/**
		 * Whether a REST request is a Store API checkout request.
		 *
		 * WooCommerce registers its Store API routes under both `wc/store` and
		 * `wc/store/v1`, and WordPress matches routes case-insensitively.
		 * Covers the cart checkout (`/checkout`) and paying for an existing
		 * order (`/checkout/<id>`).
		 *
		 * @since 2.9.17
		 *
		 * @param mixed $request Request being served.
		 * @return bool
		 */
		private static function is_store_api_checkout_request( $request ) {
			return $request instanceof \WP_REST_Request
				&& 1 === preg_match( '#^/wc/store(?:/v1)?/checkout(?:/|$)#i', $request->get_route() );
		}

		/**
		 * Remember the payment method a Store API checkout request names.
		 *
		 * WooCommerce applies a checkout request's `payment_method` to the
		 * session only after it has calculated the cart totals for a
		 * place-order POST, so during that calculation the session can still
		 * hold a different method than the one being submitted. Capturing the
		 * request's own value before the route runs lets
		 * get_fee_gateway_id() calculate the fee for the method the order
		 * will actually be placed with.
		 *
		 * Reads the parameter the same way WooCommerce does
		 * (`wc_clean( wp_unslash( $request['payment_method'] ) )`), so both
		 * resolve the same value from the same request.
		 *
		 * The captured value belongs to this one request:
		 * jp4wc_release_checkout_payment_method() puts the previous state back
		 * when the route has run. A batch request serves several requests in
		 * one process, and a route may dispatch another request while it runs,
		 * so the state is neither left behind for the next request nor wiped
		 * by a nested one.
		 *
		 * @since 2.9.17
		 *
		 * @param mixed            $response Result to send to the client.
		 * @param array            $handler  Route handler used for the request.
		 * @param \WP_REST_Request $request  Request used to generate the response.
		 * @return mixed The unchanged $response.
		 */
		public static function jp4wc_capture_checkout_payment_method( $response, $handler, $request ) {
			if ( ! self::is_store_api_checkout_request( $request ) ) {
				return $response;
			}

			self::$request_state_stack[] = array( self::$request_payment_method, self::$fee_basis_gateway_id );

			$payment_method = $request->get_param( 'payment_method' );
			$payment_method = is_string( $payment_method ) ? wc_clean( wp_unslash( $payment_method ) ) : '';

			self::$request_payment_method = '' !== $payment_method ? $payment_method : null;
			self::$fee_basis_gateway_id   = null;

			return $response;
		}

		/**
		 * Forget a Store API checkout request's payment method once the route
		 * has run.
		 *
		 * @since 2.9.17
		 *
		 * @param mixed            $response Result to send to the client.
		 * @param array            $handler  Route handler used for the request.
		 * @param \WP_REST_Request $request  Request used to generate the response.
		 * @return mixed The unchanged $response.
		 */
		public static function jp4wc_release_checkout_payment_method( $response, $handler, $request ) {
			if ( self::is_store_api_checkout_request( $request ) && ! empty( self::$request_state_stack ) ) {
				list( self::$request_payment_method, self::$fee_basis_gateway_id ) = array_pop( self::$request_state_stack );
			}

			return $response;
		}

		/**
		 * Get the payment method the gateway fee should be calculated for.
		 *
		 * Inside a Store API checkout request that names a payment method,
		 * that method (see jp4wc_capture_checkout_payment_method()); otherwise
		 * WooCommerce's `chosen_payment_method`, which the classic checkout,
		 * the Checkout block and add_gateway_fee_for_wc_blocks() all keep
		 * current.
		 *
		 * The request's value is not checked against the available gateways
		 * here. WooCommerce validates it for the same request and rejects the
		 * request when the gateway is not available, so nothing is placed with
		 * a fee calculated for a gateway the order cannot use — while a check
		 * made in the middle of the totals calculation could disagree with
		 * WooCommerce's own (the cart total is still 0 at that point, which
		 * availability filters may depend on) and silently fall back to a
		 * stale session value.
		 *
		 * The result is remembered so jp4wc_reject_stale_gateway_fee() can
		 * compare it with the method the order ends up with.
		 *
		 * @since 2.9.17
		 *
		 * @return string Gateway ID, or '' when no method has been chosen.
		 */
		public static function get_fee_gateway_id() {
			$gateway_id = '';

			if ( null !== self::$request_payment_method ) {
				$gateway_id = self::$request_payment_method;
			} elseif ( WC()->session ) {
				$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );
				$gateway_id            = is_string( $chosen_payment_method ) ? $chosen_payment_method : '';
			}

			self::$fee_basis_gateway_id = $gateway_id;

			return $gateway_id;
		}

		/**
		 * Reject a place-order request whose fees were calculated for a
		 * different gateway than the one actually being submitted.
		 *
		 * See jp4wc_calculate_order_totals() in class-jp4wc-cod-fee.php: it
		 * computes the cart's COD/COD2 surcharge during Store API cart-total
		 * calculation — which, for the final place-order POST, always runs
		 * *before* WooCommerce sets the order's real payment method from this
		 * request (see WC_Store_API's CheckoutTrait::update_order_from_request(),
		 * which fires the hook this method is attached to only after doing so).
		 * get_fee_gateway_id() therefore calculates from the payment method
		 * the request itself names, so the two normally agree. This check is
		 * the safety net behind that: should the fee ever have been calculated
		 * for another gateway than the order is placed with, and one of the
		 * two is COD or COD2 — the surcharge that applies to the real gateway
		 * silently dropped, or one charged that does not apply — reject the
		 * request instead of risking an order placed with an incorrect total.
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
				// calculated fees before the payment method was applied by
				// the time this hook fires; the draft-update (PUT/PATCH)
				// flow sets the payment method *before* calculating fees,
				// so there is nothing to validate here yet.
				return;
			}

			if ( ! $order->needs_payment() ) {
				// An order that stopped needing payment (e.g. fully covered
				// by a coupon after a gateway was previously selected) is
				// given payment_method '' by WooCommerce regardless of any
				// earlier selection — that's not a real mismatch, and
				// there's no gateway-specific surcharge to protect.
				return;
			}

			if ( null === self::$fee_basis_gateway_id ) {
				// The gateway fee was not calculated in this request (e.g.
				// paying for an existing order), so no fee was decided from
				// a gateway here. Nothing to check.
				return;
			}

			$order_gateway_id = $order->get_payment_method();
			if ( self::$fee_basis_gateway_id === $order_gateway_id ) {
				return;
			}

			// Only COD and COD2 carry a gateway fee. A difference between two
			// other values (say no method chosen yet and a bank transfer)
			// decides the same thing — no fee — so the total is right and
			// there is nothing to protect.
			$fee_gateway_ids = array( 'cod', 'cod2' );
			if ( ! in_array( self::$fee_basis_gateway_id, $fee_gateway_ids, true ) && ! in_array( $order_gateway_id, $fee_gateway_ids, true ) ) {
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
