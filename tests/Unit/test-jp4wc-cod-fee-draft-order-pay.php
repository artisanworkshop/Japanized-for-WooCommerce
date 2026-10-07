<?php
/**
 * Tests for paying a Checkout block draft order with COD/COD2 outside the
 * checkout.
 *
 * WooCommerce lets a `checkout-draft` order be paid like a pending one, through
 * the Store API's pay-for-order route and the classic order-pay page. Neither
 * recalculates cart fees, so a draft started with another payment method could
 * be completed as a cash-on-delivery order without the surcharge the gateway
 * carries. Both ways are closed for COD/COD2; everything else stays as it was.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_COD_Fee_Draft_Order_Pay_Test
 */
class JP4WC_COD_Fee_Draft_Order_Pay_Test extends WP_UnitTestCase {

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

		if ( ! class_exists( 'JP4WC_COD_Fee_Handler' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-cod-fee-handler.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_COD_Fee_Handler' ) );

		// COD2 (Cash on Delivery for Subscriptions) only registers itself when the
		// wc4jp-cod2 option is set while the plugin loads, which is before setUp().
		add_filter( 'woocommerce_payment_gateways', array( 'WC_Gateway_COD2', 'add_gateway' ) );
		foreach ( array( 'cod', 'cod2', 'bacs' ) as $gateway_id ) {
			update_option( 'woocommerce_' . $gateway_id . '_settings', array( 'enabled' => 'yes' ) );
		}
		WC()->payment_gateways()->init();
		foreach ( array( 'cod', 'cod2', 'bacs' ) as $gateway_id ) {
			$this->assertArrayHasKey( $gateway_id, WC()->payment_gateways->get_available_payment_gateways(), "Test fixture expects the {$gateway_id} gateway to be available." );
		}

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

		WC()->session->set( 'chosen_payment_method', null );
		WC()->session->set( 'jp4wc_gateway_id', null );
		WC()->session->set( 'store_api_draft_order', null );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product_id );

		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server, $wp;
		$wp_rest_server = null;
		unset( $wp->query_vars['order-pay'] );

		remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		remove_filter( 'woocommerce_is_checkout', '__return_true' );

		WC()->cart->empty_cart();
		WC()->session->__unset( 'chosen_payment_method' );
		WC()->session->__unset( 'jp4wc_gateway_id' );
		WC()->session->__unset( 'store_api_draft_order' );

		remove_filter( 'woocommerce_payment_gateways', array( 'WC_Gateway_COD2', 'add_gateway' ) );
		delete_option( 'woocommerce_cod_settings' );
		delete_option( 'woocommerce_cod2_settings' );
		delete_option( 'woocommerce_bacs_settings' );
		delete_option( 'wc4jp-extra_charge_name' );
		delete_option( 'wc4jp-extra_charge_amount' );
		delete_option( 'woocommerce_enable_guest_checkout' );
		WC()->payment_gateways()->init();

		parent::tearDown();
	}

	/**
	 * Dispatch a JSON request through the REST server.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $route  REST route.
	 * @param array<string, mixed> $body   JSON body.
	 * @return array{status: int, data: array<string, mixed>}
	 */
	private function request( $method, $route, array $body = array() ) {
		$request = new WP_REST_Request( $method, $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = json_decode( wp_json_encode( $response->get_data() ), true );

		return array(
			'status' => $response->get_status(),
			'data'   => is_array( $data ) ? $data : array(),
		);
	}

	/**
	 * A complete Japanese address.
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
	 * Leave a Checkout block draft order behind for the current cart, the way
	 * a shopper's session does, and return it.
	 *
	 * An order attempt with a gateway that is registered but not enabled
	 * fails after the draft has been created (or, on older WooCommerce,
	 * updated) with the shopper's billing address, which paying for the
	 * order later checks against.
	 *
	 * @return WC_Order
	 */
	private function create_draft_order() {
		$this->request(
			'POST',
			'/wc/store/v1/checkout',
			array(
				'billing_address' => $this->address(),
				'payment_method'  => 'cheque',
			)
		);
		$checkout = $this->request( 'GET', '/wc/store/v1/checkout' );
		$this->assertNotEmpty( $checkout['data']['order_id'], 'Test fixture expects a draft order: ' . wp_json_encode( $checkout['data'] ) );

		$order = wc_get_order( $checkout['data']['order_id'] );
		$this->assertSame( 'checkout-draft', $order->get_status() );
		return $order;
	}

	/**
	 * A pending order that can be paid for, as the My Account "Pay" link does.
	 *
	 * @return WC_Order
	 */
	private function create_pending_order() {
		$order = wc_create_order();
		$order->add_product( wc_get_product( $this->product_id ), 1 );
		$order->set_address( $this->address(), 'billing' );
		$order->calculate_totals();
		$order->set_status( 'pending' );
		$order->save();
		return $order;
	}

	/**
	 * Pay for an existing order through the Store API.
	 *
	 * @param WC_Order $order          Order to pay for.
	 * @param string   $payment_method Payment method.
	 * @param string   $route_prefix   Checkout route prefix.
	 * @return array{status: int, data: array<string, mixed>}
	 */
	private function pay_for_order( WC_Order $order, $payment_method, $route_prefix = '/wc/store/v1/checkout' ) {
		return $this->request(
			'POST',
			$route_prefix . '/' . $order->get_id(),
			array(
				'key'              => $order->get_order_key(),
				'billing_email'    => 'taro@example.com',
				'billing_address'  => $this->address(),
				// WooCommerce 10.5 / 10.6 pass this to WC_Order::set_shipping_address() unchecked.
				'shipping_address' => $this->address(),
				'payment_method'   => $payment_method,
			)
		);
	}

	// ---------------------------------------------------------------------
	// Store API pay-for-order route.
	// ---------------------------------------------------------------------

	/**
	 * @dataProvider fee_gateways_and_pay_for_order_routes
	 *
	 * @param string $gateway_id   Gateway that carries a fee.
	 * @param string $route_prefix Checkout route prefix.
	 */
	public function test_draft_order_cannot_be_paid_with_a_fee_gateway_outside_the_checkout( $gateway_id, $route_prefix ) {
		$order = $this->create_draft_order();

		$response = $this->pay_for_order( $order, $gateway_id, $route_prefix );

		$this->assertSame( 409, $response['status'], wp_json_encode( $response['data'] ) );
		$this->assertSame( 'jp4wc_cod_draft_order_payment', $response['data']['code'] );
		$this->assertSame( 'checkout-draft', wc_get_order( $order->get_id() )->get_status(), 'The draft must be left as it was.' );
	}

	/**
	 * Both fee gateways on every route that reaches the pay-for-order handler.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function fee_gateways_and_pay_for_order_routes() {
		$cases = array();
		foreach ( array( 'cod', 'cod2' ) as $gateway_id ) {
			$cases[ $gateway_id . ', versioned' ]   = array( $gateway_id, '/wc/store/v1/checkout' );
			$cases[ $gateway_id . ', unversioned' ] = array( $gateway_id, '/wc/store/checkout' );
			$cases[ $gateway_id . ', mixed case' ]  = array( $gateway_id, '/WC/Store/V1/Checkout' );
		}
		return $cases;
	}

	public function test_draft_order_can_still_be_paid_with_another_method() {
		$order = $this->create_draft_order();

		$response = $this->pay_for_order( $order, 'bacs' );

		$this->assertSame( 200, $response['status'], wp_json_encode( $response['data'] ) );
		$this->assertSame( 'bacs', wc_get_order( $order->get_id() )->get_payment_method() );
	}

	/**
	 * Paying a pending order with COD is what the pay-for-order route is for;
	 * it is not touched.
	 */
	/**
	 * The shopper selected another gateway in the Checkout block before the
	 * draft was paid for with COD. The stale-fee guard sees a mismatch too;
	 * the answer must still be the one that says what to do.
	 */
	public function test_draft_order_rejection_says_to_use_the_checkout_even_when_another_gateway_was_selected() {
		$order = $this->create_draft_order();
		WC()->session->set( 'jp4wc_gateway_id', 'bacs' );

		$response = $this->pay_for_order( $order, 'cod' );

		$this->assertSame( 409, $response['status'], wp_json_encode( $response['data'] ) );
		$this->assertSame( 'jp4wc_cod_draft_order_payment', $response['data']['code'] );
	}

	/**
	 * @dataProvider fee_gateways
	 *
	 * @param string $gateway_id Gateway that carries a fee.
	 */
	public function test_pending_order_can_still_be_paid_with_a_fee_gateway( $gateway_id ) {
		$order = $this->create_pending_order();

		$response = $this->pay_for_order( $order, $gateway_id );

		$this->assertSame( 200, $response['status'], wp_json_encode( $response['data'] ) );
		$this->assertSame( $gateway_id, wc_get_order( $order->get_id() )->get_payment_method() );
	}

	/**
	 * Gateways that carry a fee.
	 *
	 * @return array<string, array{string}>
	 */
	public function fee_gateways() {
		return array(
			'cod'  => array( 'cod' ),
			'cod2' => array( 'cod2' ),
		);
	}

	/**
	 * The checkout route places the same draft with its fee calculated from
	 * the cart — that is the way to pay a draft by COD, and it must not be
	 * mistaken for the pay-for-order route.
	 */
	public function test_draft_order_is_placed_with_cod_and_its_fee_from_the_checkout() {
		$this->create_draft_order();

		// The Checkout block tells the extension endpoint about the selection first.
		$this->request(
			'POST',
			'/wc/store/v1/cart/extensions',
			array(
				'namespace' => 'jp4wc-add-gateway-fee',
				'data'      => array(
					'action'     => 'add-fee',
					'gateway_id' => 'cod',
				),
			)
		);

		$response = $this->request(
			'POST',
			'/wc/store/v1/checkout',
			array(
				'billing_address' => $this->address(),
				'payment_method'  => 'cod',
			)
		);

		$this->assertSame( 200, $response['status'], wp_json_encode( $response['data'] ) );
		$order = wc_get_order( $response['data']['order_id'] );
		$this->assertSame( 'cod', $order->get_payment_method() );
		$this->assertSame( 1330.0, (float) $order->get_total() );
	}

	// ---------------------------------------------------------------------
	// Classic order-pay page.
	// ---------------------------------------------------------------------

	/**
	 * Make WooCommerce believe the current request is the order-pay page of
	 * the given order (is_checkout_pay_page() reads the query var and
	 * is_checkout(), which the test filters to true).
	 *
	 * @param int $order_id Order ID.
	 */
	private function on_order_pay_page( $order_id ) {
		global $wp;
		$wp->query_vars['order-pay'] = $order_id;
	}

	public function test_fee_gateways_are_not_offered_on_the_order_pay_page_of_a_draft_order() {
		$order = $this->create_draft_order();
		$this->on_order_pay_page( $order->get_id() );

		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$this->assertArrayNotHasKey( 'cod', $gateways );
		$this->assertArrayNotHasKey( 'cod2', $gateways );
		$this->assertArrayHasKey( 'bacs', $gateways );
	}

	public function test_fee_gateways_are_offered_on_the_order_pay_page_of_a_pending_order() {
		$order = $this->create_pending_order();
		$this->on_order_pay_page( $order->get_id() );

		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$this->assertArrayHasKey( 'cod', $gateways );
		$this->assertArrayHasKey( 'cod2', $gateways );
	}

	public function test_fee_gateways_are_offered_outside_the_order_pay_page() {
		$this->create_draft_order();

		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$this->assertArrayHasKey( 'cod', $gateways );
		$this->assertArrayHasKey( 'cod2', $gateways );
	}
}
