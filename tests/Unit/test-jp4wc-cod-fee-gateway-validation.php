<?php
/**
 * Tests for which payment method the COD/COD2 fee is calculated for.
 *
 * The `jp4wc-add-gateway-fee` Store API extension endpoint is unauthenticated
 * by design (anonymous shoppers must be able to update their cart), so any
 * value it accepts for `gateway_id` is fully client-controlled. A bogus
 * gateway_id must not be stored, and a valid-but-different one must not let
 * an order be placed with a fee calculated for another gateway (security
 * review findings).
 *
 * Since 2.9.17 the selection lives in WooCommerce's own
 * `chosen_payment_method` session key instead of a separate
 * `jp4wc_gateway_id`, and a Store API checkout request's own `payment_method`
 * takes priority over the session — so the fee no longer depends on which of
 * two overlapping requests saved the session last.
 *
 * These tests call the handler's methods directly. The same behaviour through
 * real Store API requests, hooks included, is covered by
 * test-jp4wc-cod-fee-store-api.php.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_COD_Fee_Handler_Gateway_Validation_Test
 */
class JP4WC_COD_Fee_Handler_Gateway_Validation_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_COD_Fee_Handler' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-cod-fee-handler.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_COD_Fee_Handler' ) );

		foreach ( array( 'cod', 'bacs' ) as $gateway_id ) {
			$settings            = get_option( 'woocommerce_' . $gateway_id . '_settings', array() );
			$settings            = is_array( $settings ) ? $settings : array();
			$settings['enabled'] = 'yes';
			update_option( 'woocommerce_' . $gateway_id . '_settings', $settings );
		}
		WC()->payment_gateways()->init();

		$available = WC()->payment_gateways->get_available_payment_gateways();
		$this->assertArrayHasKey( 'cod', $available, 'Test fixture expects the "cod" gateway to be available once enabled.' );
		$this->assertArrayHasKey( 'bacs', $available, 'Test fixture expects the "bacs" gateway to be available once enabled.' );

		WC()->session->set( 'chosen_payment_method', null );
		WC()->session->set( 'jp4wc_gateway_id', null );
		WC()->cart->empty_cart();
		$this->reset_request_state();
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		WC()->session->__unset( 'chosen_payment_method' );
		WC()->session->__unset( 'jp4wc_gateway_id' );
		WC()->cart->empty_cart();
		$this->reset_request_state();
		remove_filter( 'woocommerce_is_checkout', '__return_true' );
		delete_option( 'woocommerce_cod_settings' );
		delete_option( 'woocommerce_bacs_settings' );
		delete_option( 'wc4jp-extra_charge_name' );
		delete_option( 'wc4jp-extra_charge_amount' );
		WC()->payment_gateways()->init();
		parent::tearDown();
	}

	/**
	 * Forget what the handler remembered about the "current request": the
	 * handler keeps it in static properties, which outlive a test.
	 */
	private function reset_request_state() {
		foreach (
			array(
				'request_payment_method' => null,
				'fee_basis_gateway_id'   => null,
				'request_state_stack'    => array(),
			) as $name => $value
		) {
			$property = new ReflectionProperty( 'JP4WC_COD_Fee_Handler', $name );
			$property->setValue( null, $value );
		}
	}

	/**
	 * Pass a request through the hook WordPress fires before a route runs.
	 *
	 * @param string      $method         HTTP method.
	 * @param string      $route          REST route.
	 * @param string|null $payment_method Value of the request's payment_method parameter, if any.
	 * @return WP_REST_Request The request, to hand to finish_request() later.
	 */
	private function serve_request( $method, $route, $payment_method = null ) {
		$request = new WP_REST_Request( $method, $route );
		if ( null !== $payment_method ) {
			$request->set_param( 'payment_method', $payment_method );
		}
		$response = new WP_REST_Response();

		$this->assertSame(
			$response,
			JP4WC_COD_Fee_Handler::jp4wc_capture_checkout_payment_method( $response, array(), $request ),
			'The filter must pass the response through untouched.'
		);

		return $request;
	}

	/**
	 * Pass a request through the hook WordPress fires after its route has run.
	 *
	 * @param WP_REST_Request $request Request returned by serve_request().
	 */
	private function finish_request( WP_REST_Request $request ) {
		$response = new WP_REST_Response();

		$this->assertSame(
			$response,
			JP4WC_COD_Fee_Handler::jp4wc_release_checkout_payment_method( $response, array(), $request ),
			'The filter must pass the response through untouched.'
		);
	}

	/**
	 * Build an order that WC_Order::needs_payment() considers payable
	 * (pending status, non-zero total) — the scenario the mismatch guard
	 * in jp4wc_reject_stale_gateway_fee() is meant to protect.
	 *
	 * @return WC_Order
	 */
	private function create_order_needing_payment() {
		$order = new WC_Order();
		$order->set_status( 'pending' );
		$order->set_total( 1000 );
		return $order;
	}

	// ---------------------------------------------------------------------
	// Extension endpoint callback.
	// ---------------------------------------------------------------------

	/**
	 * A gateway_id that does not match any currently available payment
	 * gateway must not be stored — the shopper's real selection stays.
	 */
	public function test_bogus_gateway_id_is_not_stored() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'this-gateway-does-not-exist',
			)
		);

		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * A non-string gateway_id (unrestricted client input on this
	 * unauthenticated endpoint) must be rejected without a TypeError — a
	 * non-empty array/object bypasses empty() and, used directly as an
	 * array offset, would otherwise crash the request (PR review, third
	 * round).
	 */
	public function test_non_string_gateway_id_does_not_throw() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => array( 'bacs' ),
			)
		);

		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * A gateway_id matching a real, currently available gateway becomes
	 * WooCommerce's chosen payment method.
	 */
	public function test_valid_gateway_id_becomes_the_chosen_payment_method() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'cod',
			)
		);

		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * An empty gateway_id does not clear the method WooCommerce itself
	 * recorded.
	 */
	public function test_empty_gateway_id_leaves_the_chosen_payment_method_alone() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => '',
			)
		);

		$this->assertSame( 'cod', WC()->session->get( 'chosen_payment_method' ) );
	}

	/**
	 * The session key earlier versions kept the selection in is removed, so
	 * a session started before the update does not carry it around.
	 */
	public function test_legacy_session_key_is_removed() {
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'cod',
			)
		);

		$this->assertNull( WC()->session->get( 'jp4wc_gateway_id' ) );
	}

	// ---------------------------------------------------------------------
	// Which gateway the fee is calculated for.
	// ---------------------------------------------------------------------

	/**
	 * Without a checkout request, the fee follows WooCommerce's chosen
	 * payment method.
	 */
	public function test_fee_gateway_is_the_chosen_payment_method() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * A leftover `jp4wc_gateway_id` no longer overrides WooCommerce's chosen
	 * payment method: that override is what let the Checkout block's own
	 * payment-method update calculate the fee from a stale value.
	 */
	public function test_legacy_session_key_does_not_override_the_chosen_payment_method() {
		WC()->session->set( 'jp4wc_gateway_id', 'cod' );
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		$this->assertSame( 'bacs', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * No selection at all resolves to an empty string, not null.
	 */
	public function test_fee_gateway_is_empty_without_a_selection() {
		$this->assertSame( '', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * A place-order POST calculates totals before WooCommerce applies the
	 * request's payment method to the session, so the request's own value
	 * must win over a session that still holds another method.
	 */
	public function test_checkout_post_payment_method_wins_over_the_session() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		$this->serve_request( 'POST', '/wc/store/v1/checkout', 'cod' );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * The same holds for the Checkout block's payment-method update.
	 */
	public function test_checkout_put_payment_method_wins_over_the_session() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		$this->serve_request( 'PUT', '/wc/store/v1/checkout', 'bacs' );

		$this->assertSame( 'bacs', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * The request's payment method is taken as it is. WooCommerce validates
	 * it for the same request and rejects the request when the gateway is
	 * not available; second-guessing it here, in the middle of the totals
	 * calculation, could only fall back to a stale session value.
	 */
	public function test_request_payment_method_is_not_second_guessed() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		$this->serve_request( 'POST', '/wc/store/v1/checkout', 'this-gateway-does-not-exist' );

		$this->assertSame( 'this-gateway-does-not-exist', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * WooCommerce reads the parameter with wc_clean(); the handler must
	 * resolve the same value.
	 */
	public function test_request_payment_method_is_cleaned_like_woocommerce_does() {
		$this->serve_request( 'POST', '/wc/store/v1/checkout', ' cod ' );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * WooCommerce registers the Store API under `wc/store` as well as
	 * `wc/store/v1`, WordPress matches routes case-insensitively, and paying
	 * for an existing order is a checkout request too.
	 *
	 * @dataProvider checkout_routes
	 *
	 * @param string $route REST route.
	 */
	public function test_every_form_of_the_checkout_route_is_recognised( $route ) {
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		$this->serve_request( 'POST', $route, 'cod' );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * Routes that reach WooCommerce's checkout handlers.
	 *
	 * @return array<string, array{string}>
	 */
	public function checkout_routes() {
		return array(
			'versioned'     => array( '/wc/store/v1/checkout' ),
			'unversioned'   => array( '/wc/store/checkout' ),
			'mixed case'    => array( '/WC/Store/V1/Checkout' ),
			'pay for order' => array( '/wc/store/v1/checkout/123' ),
		);
	}

	/**
	 * Only checkout requests carry a payment method that will be applied to
	 * the order; a same-named parameter on any other route means nothing.
	 *
	 * @dataProvider other_routes
	 *
	 * @param string $route REST route.
	 */
	public function test_payment_method_on_other_routes_is_ignored( $route ) {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		$this->serve_request( 'POST', $route, 'bacs' );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * Routes that are not the checkout.
	 *
	 * @return array<string, array{string}>
	 */
	public function other_routes() {
		return array(
			'cart extensions'   => array( '/wc/store/v1/cart/extensions' ),
			'similar name'      => array( '/wc/store/v1/checkout-fields' ),
			'another namespace' => array( '/my-plugin/v1/wc/store/v1/checkout' ),
		);
	}

	/**
	 * A batch request serves several requests in one process: a later
	 * request must not inherit an earlier one's payment method.
	 */
	public function test_request_payment_method_does_not_leak_into_the_next_request() {
		WC()->session->set( 'chosen_payment_method', 'cod' );

		$request = $this->serve_request( 'PUT', '/wc/store/v1/checkout', 'bacs' );
		$this->assertSame( 'bacs', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
		$this->finish_request( $request );

		$this->serve_request( 'POST', '/wc/store/v1/cart/update-item' );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * A route may dispatch another REST request while it runs. That inner
	 * request must not wipe what the checkout request named.
	 */
	public function test_nested_request_does_not_wipe_the_checkout_payment_method() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		$checkout = $this->serve_request( 'POST', '/wc/store/v1/checkout', 'cod' );

		$inner = $this->serve_request( 'GET', '/wc/store/v1/cart' );
		$this->finish_request( $inner );

		$this->assertSame( 'cod', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );

		$this->finish_request( $checkout );
		$this->assertSame( 'bacs', JP4WC_COD_Fee_Handler::get_fee_gateway_id() );
	}

	/**
	 * Each checkout request starts without a fee basis, so the place-order
	 * guard never compares an order against a fee that an earlier request in
	 * the same process calculated.
	 */
	public function test_fee_basis_of_an_earlier_request_is_not_checked_against_a_later_order() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		$first = $this->serve_request( 'PUT', '/wc/store/v1/checkout', 'bacs' );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();
		$this->finish_request( $first );

		// Paying for an existing order: its fees are not recalculated.
		$second = $this->serve_request( 'POST', '/wc/store/v1/checkout/123', 'cod' );

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );
		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $second );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	// ---------------------------------------------------------------------
	// The fee on the cart.
	// ---------------------------------------------------------------------

	/**
	 * Put a product in the cart and configure a 330 COD fee.
	 */
	private function prepare_cart_with_cod_fee() {
		update_option( 'wc4jp-extra_charge_name', 'COD fee' );
		update_option( 'wc4jp-extra_charge_amount', '330' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Product' );
		$product->set_regular_price( '1000' );
		$product->set_virtual( true );
		WC()->cart->add_to_cart( $product->save() );

		// The fee is only calculated on the checkout page, its AJAX calls and REST requests.
		add_filter( 'woocommerce_is_checkout', '__return_true' );
	}

	/**
	 * Amount of the gateway fee currently on the cart, or null without one.
	 *
	 * @return float|null
	 */
	private function calculated_gateway_fee() {
		WC()->cart->calculate_totals();
		foreach ( WC()->cart->get_fees() as $fee ) {
			if ( 'jp4wc_gateway_fee' === $fee->id ) {
				return (float) $fee->amount;
			}
		}
		return null;
	}

	/**
	 * The fee is on the cart while COD is the chosen payment method and gone
	 * once another one is.
	 */
	public function test_cart_fee_follows_the_chosen_payment_method() {
		$this->prepare_cart_with_cod_fee();

		WC()->session->set( 'chosen_payment_method', 'cod' );
		$this->assertSame( 330.0, $this->calculated_gateway_fee() );

		WC()->session->set( 'chosen_payment_method', 'bacs' );
		$this->assertNull( $this->calculated_gateway_fee() );
	}

	/**
	 * The scenario behind the Checkout block bug: the block's payment-method
	 * update runs while the session still holds the value another request
	 * has not saved yet. The request's own payment method decides the fee.
	 */
	public function test_cart_fee_follows_the_checkout_request_over_a_stale_session() {
		$this->prepare_cart_with_cod_fee();

		// Shopper switched from COD to bank transfer; the session is stale.
		WC()->session->set( 'chosen_payment_method', 'cod' );
		WC()->session->set( 'jp4wc_gateway_id', 'cod' );
		$this->serve_request( 'PUT', '/wc/store/v1/checkout', 'bacs' );
		$this->assertNull( $this->calculated_gateway_fee(), 'No COD fee may be calculated for a bank-transfer request.' );

		// Shopper switched from bank transfer to COD; the session is stale.
		WC()->session->set( 'chosen_payment_method', 'bacs' );
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );
		$this->serve_request( 'PUT', '/wc/store/v1/checkout', 'cod' );
		$this->assertSame( 330.0, $this->calculated_gateway_fee(), 'The COD fee must be calculated for a COD request.' );
	}

	/**
	 * Sending another real gateway to the extension endpoint first and then
	 * placing the order with COD must still charge the COD fee (second-round
	 * security review finding: a valid-but-different gateway ID let the
	 * surcharge be dropped).
	 */
	public function test_cod_order_is_charged_the_fee_whatever_the_extension_endpoint_was_told() {
		$this->prepare_cart_with_cod_fee();

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'bacs',
			)
		);
		$this->serve_request( 'POST', '/wc/store/v1/checkout', 'cod' );

		$this->assertSame( 330.0, $this->calculated_gateway_fee() );

		// And the guard agrees with the order that results.
		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );
		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, new WP_REST_Request( 'POST', '/wc/store/v1/checkout' ) );
	}

	// ---------------------------------------------------------------------
	// Place-order guard.
	// ---------------------------------------------------------------------

	/**
	 * A place-order POST whose fee was calculated for another gateway than
	 * the payment method actually being submitted must be rejected.
	 */
	public function test_reject_stale_gateway_fee_throws_on_mismatch() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		$this->expectException( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException::class );
		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
	}

	/**
	 * The reverse mismatch — a COD fee calculated for an order placed with
	 * another method — is rejected as well.
	 */
	public function test_reject_stale_gateway_fee_throws_when_fee_was_calculated_for_cod() {
		WC()->session->set( 'chosen_payment_method', 'cod' );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'bacs' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		$this->expectException( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException::class );
		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
	}

	/**
	 * A difference that does not involve COD or COD2 decides the same thing
	 * — no gateway fee — so there is no wrong total to protect and the order
	 * must go through. (No method chosen yet and an order placed by bank
	 * transfer is the everyday case.)
	 *
	 * @dataProvider mismatches_without_a_fee_gateway
	 *
	 * @param string|null $chosen       Chosen payment method in the session.
	 * @param string      $order_method Payment method the order is placed with.
	 */
	public function test_reject_stale_gateway_fee_allows_mismatch_without_a_fee_gateway( $chosen, $order_method ) {
		WC()->session->set( 'chosen_payment_method', $chosen );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( $order_method );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	/**
	 * Fee basis / order method pairs in which neither is COD or COD2.
	 *
	 * @return array<string, array{string|null, string}>
	 */
	public function mismatches_without_a_fee_gateway() {
		return array(
			'nothing chosen, bank transfer' => array( null, 'bacs' ),
			'bank transfer, cheque'         => array( 'bacs', 'cheque' ),
		);
	}

	/**
	 * A place-order POST whose fee-basis gateway matches the submitted
	 * payment method is allowed through.
	 */
	public function test_reject_stale_gateway_fee_allows_match() {
		WC()->session->set( 'chosen_payment_method', 'cod' );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	/**
	 * When the gateway fee was never calculated in the request (e.g. paying
	 * for an existing order), no fee was decided from a gateway — nothing
	 * to validate, whatever the session holds.
	 */
	public function test_reject_stale_gateway_fee_allows_request_without_fee_calculation() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	/**
	 * The draft-update (PUT/PATCH) flow sets the payment method before
	 * calculating fees, so a mismatch there does not indicate a stale
	 * fee — must not be rejected.
	 */
	public function test_reject_stale_gateway_fee_skips_non_post_requests() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	/**
	 * An order that no longer needs payment (e.g. fully covered by a
	 * coupon after a gateway was previously selected in the UI) must not
	 * be rejected — there is no gateway-specific surcharge to protect, and
	 * WooCommerce itself sets payment_method to '' for such orders
	 * regardless of any earlier selection (third-round review finding).
	 */
	public function test_reject_stale_gateway_fee_allows_order_not_needing_payment() {
		WC()->session->set( 'chosen_payment_method', 'bacs' );
		JP4WC_COD_Fee_Handler::get_fee_gateway_id();

		$order = new WC_Order();
		$order->set_status( 'pending' );
		$order->set_total( 0 );
		$order->set_payment_method( '' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}
}
