<?php
/**
 * Tests for guest-checkout handling in WC_Gateway_Paidy::paidy_make_order().
 *
 * This method computes the customer's completed-order history (total
 * amount, count, latest order) as risk-signal values sent to Paidy. For a
 * guest checkout, $user_id is 'guest-paidy' . order ID — a non-numeric
 * string, not a real customer identity. Passing that directly to
 * wc_get_orders()'s customer_id argument coerces it to 0, which matches
 * "no assigned customer" and pulls in unrelated guests' orders instead of
 * being treated as "this guest has no history" (PR #213 review finding).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Guest_Order_History_Test
 */
class WC_Paidy_Guest_Order_History_Test extends WP_UnitTestCase {

	/**
	 * Gateway instance under test.
	 *
	 * @var WC_Gateway_Paidy
	 */
	private $gateway;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-gateway-paidy.php';
		}
		$this->assertTrue( class_exists( 'WC_Gateway_Paidy' ), 'WC_Gateway_Paidy should be loadable.' );

		// Gateway left disabled/unconfigured: paidy_make_order() computes
		// the order-history risk signals unconditionally before checking
		// enabled/api_public_key/order status, so this is enough to
		// exercise that code path without configuring the gateway.
		$this->gateway = new WC_Gateway_Paidy();
	}

	/**
	 * A guest checkout (not logged in) must not query wc_get_orders() with
	 * the guest's pseudo-ID as customer_id — no transient should be cached
	 * under that bogus key, and the call must not fatal.
	 */
	public function test_guest_checkout_skips_order_history_query() {
		wp_set_current_user( 0 );
		$this->assertFalse( is_user_logged_in() );

		$order = wc_create_order();
		$order->set_status( 'pending' );
		$order->save();

		ob_start();
		$this->gateway->paidy_make_order( $order->get_id() );
		ob_end_clean();

		$bogus_cache_key = 'jp4wc_paidy_order_history_guest-paidy' . $order->get_id();
		$this->assertFalse( get_transient( $bogus_cache_key ), 'No order-history transient should be cached for a guest pseudo-ID.' );
	}

	/**
	 * A logged-in customer's order history is still looked up and cached
	 * (unchanged behavior for the non-guest path).
	 */
	public function test_logged_in_customer_order_history_is_cached() {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$order = wc_create_order();
		$order->set_status( 'pending' );
		$order->set_customer_id( $user_id );
		$order->save();

		ob_start();
		$this->gateway->paidy_make_order( $order->get_id() );
		ob_end_clean();

		$cache_key = 'jp4wc_paidy_order_history_' . $user_id;
		$this->assertNotFalse( get_transient( $cache_key ) );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}
}
