<?php
/**
 * Tests for the COD/COD2 fee Store API gateway-ID validation.
 *
 * The `jp4wc-add-gateway-fee` Store API extension endpoint is unauthenticated
 * by design (anonymous shoppers must be able to update their cart), so any
 * value it accepts for `gateway_id` is fully client-controlled. Before the
 * fix under test, a non-empty but bogus gateway_id was stored verbatim and
 * could suppress the merchant's configured COD/COD2 surcharge for an order
 * that ultimately completes with a real gateway (security review finding).
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

		WC()->session->set( 'jp4wc_gateway_id', null );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		WC()->session->__unset( 'jp4wc_gateway_id' );
		delete_option( 'woocommerce_cod_settings' );
		WC()->payment_gateways()->init();
		parent::tearDown();
	}

	/**
	 * A gateway_id that does not match any currently available payment
	 * gateway must not be stored — it must be treated the same as an empty
	 * value (session key cleared).
	 */
	public function test_bogus_gateway_id_is_not_stored() {
		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'this-gateway-does-not-exist',
			)
		);

		$this->assertNull( WC()->session->get( 'jp4wc_gateway_id' ) );
	}

	/**
	 * A non-string gateway_id (unrestricted client input on this
	 * unauthenticated endpoint) must be rejected without a TypeError — a
	 * non-empty array/object bypasses empty() and, used directly as an
	 * array offset, would otherwise crash the request (PR review, third
	 * round).
	 */
	public function test_non_string_gateway_id_does_not_throw() {
		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => array( 'cod' ),
			)
		);

		$this->assertNull( WC()->session->get( 'jp4wc_gateway_id' ) );
	}

	/**
	 * A gateway_id matching a real, currently available gateway is stored
	 * as before. Enable WooCommerce core's built-in "cod" gateway so at
	 * least one gateway is actually available regardless of test-env
	 * defaults.
	 */
	public function test_valid_gateway_id_is_stored() {
		$settings            = get_option( 'woocommerce_cod_settings', array() );
		$settings['enabled'] = 'yes';
		update_option( 'woocommerce_cod_settings', $settings );
		WC()->payment_gateways()->init();

		$available = WC()->payment_gateways->get_available_payment_gateways();
		$this->assertArrayHasKey( 'cod', $available, 'Test fixture expects WooCommerce core "cod" gateway to be available once enabled.' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => 'cod',
			)
		);

		$this->assertSame( 'cod', WC()->session->get( 'jp4wc_gateway_id' ) );
	}

	/**
	 * An empty gateway_id still clears the session key (unchanged behavior).
	 */
	public function test_empty_gateway_id_clears_session() {
		WC()->session->set( 'jp4wc_gateway_id', 'cod' );

		JP4WC_COD_Fee_Handler::add_gateway_fee_for_wc_blocks(
			array(
				'action'     => 'add-fee',
				'gateway_id' => '',
			)
		);

		$this->assertNull( WC()->session->get( 'jp4wc_gateway_id' ) );
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

	/**
	 * A place-order POST whose fee-basis gateway (a real, available, but
	 * different gateway sent to the extension endpoint earlier) disagrees
	 * with the payment method actually being submitted must be rejected —
	 * even though that gateway ID individually passed the availability
	 * check above (second-round security review finding: a valid-but-
	 * different gateway ID still let the surcharge be dropped).
	 */
	public function test_reject_stale_gateway_fee_throws_on_mismatch() {
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		$this->expectException( \Automattic\WooCommerce\StoreApi\Exceptions\RouteException::class );
		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
	}

	/**
	 * A place-order POST whose fee-basis gateway matches the submitted
	 * payment method is allowed through.
	 */
	public function test_reject_stale_gateway_fee_allows_match() {
		WC()->session->set( 'jp4wc_gateway_id', 'cod' );

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	/**
	 * When no jp4wc-specific override is in session, fees were calculated
	 * from chosen_payment_method (which this same request already set to
	 * the real gateway) — nothing to validate.
	 */
	public function test_reject_stale_gateway_fee_allows_empty_session() {
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
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );

		$order = $this->create_order_needing_payment();
		$order->set_payment_method( 'cod' );

		$request = new WP_REST_Request( 'PUT', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}

	/**
	 * An order that no longer needs payment (e.g. fully covered by a
	 * coupon after a gateway was previously selected in the UI, leaving a
	 * stale jp4wc_gateway_id in session) must not be rejected — there is
	 * no gateway-specific surcharge to protect, and WooCommerce itself
	 * sets payment_method to '' for such orders regardless of any earlier
	 * selection (third-round review finding).
	 */
	public function test_reject_stale_gateway_fee_allows_order_not_needing_payment() {
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );

		$order = new WC_Order();
		$order->set_status( 'pending' );
		$order->set_total( 0 );
		$order->set_payment_method( '' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		JP4WC_COD_Fee_Handler::jp4wc_reject_stale_gateway_fee( $order, $request );
		$this->addToAssertionCount( 1 ); // No exception thrown.
	}
}
