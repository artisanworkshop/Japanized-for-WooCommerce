<?php
/**
 * Tests for the COD fee through real Store API requests.
 *
 * test-jp4wc-cod-fee-gateway-validation.php calls the handler's methods
 * directly. Here every request is dispatched through the REST server, so the
 * hooks registered in JP4WC_COD_Fee_Handler::init() and WooCommerce's own
 * order of operations are part of what is tested: a regression that only
 * unhooks the handler — and reopens a way to place a cash-on-delivery order
 * without its fee — fails these tests, where the direct-call tests would
 * still pass.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_COD_Fee_Store_API_Test
 */
class JP4WC_COD_Fee_Store_API_Test extends WP_UnitTestCase {

	/**
	 * ID of the product in the cart.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( array( 'cod', 'bacs' ) as $gateway_id ) {
			update_option( 'woocommerce_' . $gateway_id . '_settings', array( 'enabled' => 'yes' ) );
		}
		WC()->payment_gateways()->init();

		update_option( 'wc4jp-extra_charge_name', 'COD fee' );
		update_option( 'wc4jp-extra_charge_amount', '330' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );

		// A dispatched request is not a "real" REST request: there is no nonce, and
		// REST_REQUEST — which the fee calculation accepts in place of is_checkout()
		// — is not defined.
		add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$product = new WC_Product_Simple();
		$product->set_name( 'Product' );
		$product->set_regular_price( '1000' );
		$product->set_virtual( true );
		$this->product_id = $product->save();

		$this->reset_handler_state();
		WC()->session->set( 'chosen_payment_method', null );
		WC()->session->set( 'jp4wc_gateway_id', null );
		$this->fresh_cart();

		// Start every test from a server that has just registered its routes.
		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		remove_filter( 'woocommerce_is_checkout', '__return_true' );

		WC()->cart->empty_cart();
		WC()->session->__unset( 'chosen_payment_method' );
		WC()->session->__unset( 'jp4wc_gateway_id' );
		WC()->session->__unset( 'store_api_draft_order' );
		$this->reset_handler_state();

		delete_option( 'woocommerce_cod_settings' );
		delete_option( 'woocommerce_bacs_settings' );
		delete_option( 'wc4jp-extra_charge_name' );
		delete_option( 'wc4jp-extra_charge_amount' );
		delete_option( 'woocommerce_enable_guest_checkout' );
		WC()->payment_gateways()->init();

		parent::tearDown();
	}

	/**
	 * Forget what the handler remembered about earlier requests: it keeps
	 * that in static properties, which outlive a test.
	 */
	private function reset_handler_state() {
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
	 * Start a new checkout: one product in the cart and no draft order.
	 */
	private function fresh_cart() {
		WC()->cart->empty_cart();
		WC()->session->set( 'store_api_draft_order', null );
		WC()->cart->add_to_cart( $this->product_id );
	}

	/**
	 * Dispatch a JSON request through the REST server.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  REST route.
	 * @param array<string, mixed> $body   JSON body.
	 * @param array<string, mixed> $query  Query string parameters.
	 * @return array{status: int, data: array<string, mixed>}
	 */
	private function request( $method, $route, array $body = array(), array $query = array() ) {
		$request = new WP_REST_Request( $method, $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		if ( $query ) {
			$request->set_query_params( $query );
		}

		$response = rest_get_server()->dispatch( $request );
		$data     = json_decode( wp_json_encode( $response->get_data() ), true );

		return array(
			'status' => $response->get_status(),
			'data'   => is_array( $data ) ? $data : array(),
		);
	}

	/**
	 * Tell the plugin's extension endpoint which payment method was selected,
	 * as assets/js/jp4wc-cod-wc-blocks.js does.
	 *
	 * @param mixed $gateway_id Value sent as gateway_id.
	 * @return array{status: int, data: array<string, mixed>}
	 */
	private function tell_extension_endpoint( $gateway_id ) {
		return $this->request(
			'POST',
			'/wc/store/v1/cart/extensions',
			array(
				'namespace' => 'jp4wc-add-gateway-fee',
				'data'      => array(
					'action'     => 'add-fee',
					'gateway_id' => $gateway_id,
				),
			)
		);
	}

	/**
	 * Place the order.
	 *
	 * @param string $payment_method Payment method submitted with the order.
	 * @param string $route          Checkout route.
	 * @return array{status: int, data: array<string, mixed>}
	 */
	private function place_order( $payment_method, $route = '/wc/store/v1/checkout' ) {
		return $this->request(
			'POST',
			$route,
			array(
				'billing_address' => $this->address(),
				'payment_method'  => $payment_method,
			)
		);
	}

	/**
	 * A complete Japanese billing address.
	 *
	 * @return array<string, string>
	 */
	private function address() {
		return array(
			'first_name' => 'Taro',
			'last_name'  => 'Yamada',
			'company'    => '',
			'address_1'  => '1-1 Chiyoda',
			'address_2'  => '',
			'city'       => 'Chiyoda',
			'state'      => 'JP13',
			'postcode'   => '100-0001',
			'country'    => 'JP',
			'email'      => 'taro@example.com',
			'phone'      => '0312345678',
		);
	}

	/**
	 * Assert that a place-order response created an order with the expected
	 * payment method and gateway fee.
	 *
	 * @param array{status: int, data: array<string, mixed>} $response       Response of place_order().
	 * @param string                                         $payment_method Expected payment method.
	 * @param float|null                                     $fee            Expected gateway fee, or null for none.
	 */
	private function assert_order( array $response, $payment_method, $fee ) {
		$this->assertSame( 200, $response['status'], 'The order must be accepted: ' . wp_json_encode( $response['data'] ) );
		$this->assertNotEmpty( $response['data']['order_id'] );

		$order = wc_get_order( $response['data']['order_id'] );
		$this->assertSame( $payment_method, $order->get_payment_method() );

		$fees = array();
		foreach ( $order->get_fees() as $item ) {
			$fees[] = (float) $item->get_total();
		}
		$this->assertSame( null === $fee ? array() : array( $fee ), $fees );
		$this->assertSame( 1000.0 + (float) $fee, (float) $order->get_total() );
	}

	/**
	 * Gateway fee lines of the cart a Store API response carries.
	 *
	 * @param array<string, mixed> $data Response data.
	 * @return array<int, string> Fee names.
	 */
	private function cart_fee_names( array $data ) {
		$cart = isset( $data['__experimentalCart'] ) ? $data['__experimentalCart'] : $data;
		$this->assertArrayHasKey( 'fees', $cart, 'The response must carry a cart: ' . wp_json_encode( $data ) );
		return wp_list_pluck( $cart['fees'], 'name' );
	}

	/**
	 * The extension endpoint's own response already carries the fee for the
	 * method it was told about.
	 */
	public function test_extension_endpoint_response_follows_the_selected_method() {
		$this->assertSame( array( 'COD fee' ), $this->cart_fee_names( $this->tell_extension_endpoint( 'cod' )['data'] ) );
		$this->assertSame( array(), $this->cart_fee_names( $this->tell_extension_endpoint( 'bacs' )['data'] ) );
	}

	/**
	 * Telling the extension endpoint about another real gateway and then
	 * placing the order with COD must still charge the COD fee (the bypass
	 * closed in 2.9.16).
	 */
	public function test_cod_order_carries_the_fee_whatever_the_extension_endpoint_was_told() {
		$this->tell_extension_endpoint( 'bacs' );

		$this->assert_order( $this->place_order( 'cod' ), 'cod', 330.0 );
	}

	/**
	 * A bogus or malformed gateway_id is ignored, and the COD order that
	 * follows carries its fee.
	 */
	public function test_cod_order_carries_the_fee_after_a_bogus_gateway_id() {
		$this->tell_extension_endpoint( 'this-gateway-does-not-exist' );
		$this->tell_extension_endpoint( array( 'bacs' ) );

		$this->assert_order( $this->place_order( 'cod' ), 'cod', 330.0 );
	}

	/**
	 * The other direction: an order placed by bank transfer is not charged
	 * the COD fee the cart showed a moment earlier.
	 */
	public function test_bank_transfer_order_has_no_fee_after_cod_was_selected() {
		$this->tell_extension_endpoint( 'cod' );

		$this->assert_order( $this->place_order( 'bacs' ), 'bacs', null );
	}

	/**
	 * The Checkout block's own payment-method update (WooCommerce 9.8+)
	 * returns totals for the method it names, even when the session still
	 * holds the previous one — the request that used to put the fee back on
	 * screen, or take it away again, on a slow server.
	 */
	public function test_block_payment_method_update_returns_totals_for_the_method_it_names() {
		WC()->session->set( 'chosen_payment_method', 'cod' );
		WC()->session->set( 'jp4wc_gateway_id', 'cod' );
		$to_bank_transfer = $this->request( 'PUT', '/wc/store/v1/checkout', array( 'payment_method' => 'bacs' ), array( '__experimental_calc_totals' => 'true' ) );
		$this->assertSame( 200, $to_bank_transfer['status'], wp_json_encode( $to_bank_transfer['data'] ) );
		$this->assertSame( array(), $this->cart_fee_names( $to_bank_transfer['data'] ) );

		WC()->session->set( 'chosen_payment_method', 'bacs' );
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );
		$to_cod = $this->request( 'PUT', '/wc/store/v1/checkout', array( 'payment_method' => 'cod' ), array( '__experimental_calc_totals' => 'true' ) );
		$this->assertSame( 200, $to_cod['status'], wp_json_encode( $to_cod['data'] ) );
		$this->assertSame( array( 'COD fee' ), $this->cart_fee_names( $to_cod['data'] ) );
	}

	/**
	 * WooCommerce serves the same checkout under `wc/store` without a
	 * version, and WordPress matches routes case-insensitively. An order
	 * that has nothing to do with COD must not be rejected there, and a COD
	 * order must carry its fee.
	 *
	 * @dataProvider alias_routes
	 *
	 * @param string $route Checkout route.
	 */
	public function test_checkout_route_aliases_behave_like_the_versioned_route( $route ) {
		// Nothing selected beforehand, order placed by bank transfer.
		$this->assert_order( $this->place_order( 'bacs', $route ), 'bacs', null );

		// Bank transfer in the session, order placed with COD.
		$this->fresh_cart();
		$this->assert_order( $this->place_order( 'cod', $route ), 'cod', 330.0 );
	}

	/**
	 * Other paths that reach the checkout route.
	 *
	 * @return array<string, array{string}>
	 */
	public function alias_routes() {
		return array(
			'unversioned' => array( '/wc/store/checkout' ),
			'mixed case'  => array( '/WC/Store/V1/Checkout' ),
		);
	}

	/**
	 * The place-order guard is the safety net behind all of the above: when
	 * an order ends up with another payment method than the gateway its fee
	 * was calculated for — here other code switching the method after the
	 * totals were calculated — it is rejected rather than placed with a COD
	 * fee that does not belong to it.
	 */
	public function test_order_is_rejected_when_its_payment_method_no_longer_matches_the_fee() {
		$switch_to_bank_transfer = static function ( $order ) {
			$order->set_payment_method( 'bacs' );
		};
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', $switch_to_bank_transfer, 5 );

		$response = $this->place_order( 'cod' );

		remove_action( 'woocommerce_store_api_checkout_update_order_from_request', $switch_to_bank_transfer, 5 );

		$this->assertSame( 409, $response['status'], wp_json_encode( $response['data'] ) );
		$this->assertSame( 'jp4wc_gateway_fee_mismatch', $response['data']['code'] );
	}

	/**
	 * What a checkout request named does not outlive the request: a later
	 * request in the same process (a batch) is calculated from the session
	 * again.
	 */
	public function test_checkout_payment_method_does_not_outlive_its_request() {
		$this->request( 'PUT', '/wc/store/v1/checkout', array( 'payment_method' => 'bacs' ), array( '__experimental_calc_totals' => 'true' ) );

		// The request stored bank transfer in the session as well; select COD
		// again so the session and the finished request disagree.
		WC()->session->set( 'chosen_payment_method', 'cod' );

		// A cart request that recalculates the totals without selecting anything
		// itself (a plain GET returns the totals as last calculated): the
		// extension endpoint ignores a gateway ID it does not know.
		$cart = $this->tell_extension_endpoint( 'this-gateway-does-not-exist' );
		$this->assertSame( 200, $cart['status'] );
		$this->assertSame( array( 'COD fee' ), $this->cart_fee_names( $cart['data'] ) );
	}

	/**
	 * Paying for an existing order does not recalculate cart fees, so a fee
	 * calculated by an earlier request in the same process (a batch) must
	 * not be held against it.
	 */
	public function test_pay_for_order_is_not_checked_against_an_earlier_requests_fee() {
		$order = wc_create_order();
		$order->add_product( wc_get_product( $this->product_id ), 1 );
		$order->set_address( $this->address(), 'billing' );
		$order->calculate_totals();
		$order->set_status( 'pending' );
		$order->save();

		// Fees calculated for bank transfer, by a cart request and by a checkout request.
		$this->tell_extension_endpoint( 'bacs' );
		$this->request( 'PUT', '/wc/store/v1/checkout', array( 'payment_method' => 'bacs' ), array( '__experimental_calc_totals' => 'true' ) );
		$this->tell_extension_endpoint( 'bacs' );

		$response = $this->request(
			'POST',
			'/wc/store/v1/checkout/' . $order->get_id(),
			array(
				'key'              => $order->get_order_key(),
				'billing_email'    => 'taro@example.com',
				'billing_address'  => $this->address(),
				// WooCommerce 10.5 / 10.6 pass this to WC_Order::set_shipping_address() unchecked.
				'shipping_address' => $this->address(),
				'payment_method'   => 'cod',
			)
		);

		$code = isset( $response['data']['code'] ) ? $response['data']['code'] : '';
		$this->assertNotSame( 'jp4wc_gateway_fee_mismatch', $code, wp_json_encode( $response['data'] ) );
	}
}
