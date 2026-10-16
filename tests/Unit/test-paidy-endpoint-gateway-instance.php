<?php
/**
 * Tests that WC_Paidy_Endpoint uses the Paidy gateway WooCommerce has registered.
 *
 * WC_Paidy_Endpoint created its own WC_Gateway_Paidy on every request, and the
 * gateway's constructor registers hooks. In a request that loaded WooCommerce's
 * gateway list, the order-pay page therefore printed the Paidy Checkout script
 * twice and the settings were saved twice (issue #244). The endpoint now takes
 * the gateway from WooCommerce's list when a webhook needs it.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Endpoint_Gateway_Instance_Test
 */
class WC_Paidy_Endpoint_Gateway_Instance_Test extends WP_UnitTestCase {

	/**
	 * Secret key used to sign the test webhooks.
	 */
	const SECRET_KEY = 'sk_test_jp4wc_webhook';

	/**
	 * Webhook body Paidy sends as a test request.
	 */
	const TEST_WEBHOOK_BODY = '{"payment_id":"pay_0000000000000001","order_ref":"1","status":"authorize_success"}';

	/**
	 * WooCommerce's gateway list, kept while the test runs. False when nothing is kept.
	 *
	 * @var WC_Payment_Gateways|null|false
	 */
	private $kept_gateway_list = false;

	/**
	 * PayPal's singleton, which loading the gateway list replaces. False when nothing is kept.
	 *
	 * @var WC_Gateway_Paypal|null|false
	 */
	private $kept_paypal_instance = false;

	/**
	 * REMOTE_ADDR before the test, or null when it was not set.
	 *
	 * @var string|null
	 */
	private $kept_remote_addr = null;

	/**
	 * Paidy API URLs requested during a test.
	 *
	 * @var string[]
	 */
	private $requested_urls = array();

	/**
	 * Body the mocked Paidy API returns.
	 *
	 * @var string
	 */
	private $paidy_api_body = '';

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-gateway-paidy.php';
		}
		$this->assertTrue( class_exists( 'WC_Gateway_Paidy' ), 'WC_Gateway_Paidy should be loadable.' );
		if ( ! class_exists( 'WC_Paidy_Endpoint' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-endpoint.php';
		}
		$this->assertTrue( class_exists( 'WC_Paidy_Endpoint' ), 'WC_Paidy_Endpoint should be loadable.' );

		// Start from a request that has not loaded the payment gateways, so the list
		// loaded by the test reads the settings the test saves.
		$this->kept_gateway_list = $this->gateway_list_property()->getValue();
		$this->gateway_list_property()->setValue( null, null );
		if ( $this->paypal_instance_property() ) {
			$this->kept_paypal_instance = $this->paypal_instance_property()->getValue();
		}

		$this->kept_remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;

		$this->requested_urls = array();
		add_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10, 3 );

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		remove_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10 );
		unset( $_GET['transaction_id'] );

		if ( null === $this->kept_remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->kept_remote_addr;
		}

		remove_filter( 'woocommerce_payment_gateways', array( $this, 'remove_paidy_gateway' ), PHP_INT_MAX );
		$this->gateway_list_property()->setValue( null, $this->kept_gateway_list );
		$this->kept_gateway_list = false;
		if ( false !== $this->kept_paypal_instance ) {
			$this->paypal_instance_property()->setValue( null, $this->kept_paypal_instance );
			$this->kept_paypal_instance = false;
		}
		parent::tearDown();
	}

	/**
	 * The property holding WooCommerce's gateway list once it has been loaded.
	 *
	 * @return ReflectionProperty
	 */
	private function gateway_list_property() {
		return new ReflectionProperty( 'WC_Payment_Gateways', '_instance' );
	}

	/**
	 * The property holding PayPal's singleton, which loading the gateway list replaces.
	 *
	 * @return ReflectionProperty|null Null on a WooCommerce version without it.
	 */
	private function paypal_instance_property() {
		return property_exists( 'WC_Gateway_Paypal', 'instance' ) ? new ReflectionProperty( 'WC_Gateway_Paypal', 'instance' ) : null;
	}

	/**
	 * Short-circuit every HTTP request, answering Paidy API requests with $paidy_api_body.
	 *
	 * @param false|array|WP_Error $preempt     Whether to preempt the request.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 * @return array|WP_Error Mocked HTTP response.
	 */
	public function mock_paidy_api( $preempt, $parsed_args, $url ) {
		if ( 0 !== strpos( $url, 'https://api.paidy.com/' ) ) {
			return new WP_Error( 'http_request_failed', 'Only the Paidy API is mocked.' );
		}
		$this->requested_urls[] = $url;

		return array(
			'headers'  => array(),
			'body'     => $this->paidy_api_body,
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Leave Paidy out of WooCommerce's gateway list, as a third party removing it would.
	 *
	 * @param array $methods Gateway classes or instances.
	 * @return array
	 */
	public function remove_paidy_gateway( $methods ) {
		return array_values(
			array_filter(
				$methods,
				static function ( $method ) {
					return 'WC_Gateway_Paidy' !== $method && ! $method instanceof WC_Gateway_Paidy;
				}
			)
		);
	}

	/**
	 * Number of callbacks on a hook that belong to a WC_Gateway_Paidy instance.
	 *
	 * @param string $hook Hook name.
	 * @return int
	 */
	private function paidy_callback_count( $hook ) {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof WC_Gateway_Paidy ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Save the Paidy settings the gateway list reads when it is loaded.
	 *
	 * @param array $settings Settings to save.
	 */
	private function save_settings( $settings ) {
		update_option( 'woocommerce_paidy_settings', $settings );
	}

	/**
	 * Send a webhook to the Paidy endpoint through the REST server.
	 *
	 * @param string      $body      Request body.
	 * @param string|null $signature The x-paidy-signature header, or null for none.
	 * @return WP_REST_Response
	 */
	private function send_webhook( $body, $signature = null ) {
		$this->assertArrayHasKey( '/paidy/v1/order', rest_get_server()->get_routes(), 'The Paidy webhook route should be registered.' );

		$request = new WP_REST_Request( 'POST', '/paidy/v1/order' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( $body );
		if ( null !== $signature ) {
			$request->set_header( 'x-paidy-signature', $signature );
		}

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * The error code of a response, or null when it is not an error.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return string|null
	 */
	private function error_code( $response ) {
		$data = $response->get_data();

		return is_array( $data ) && isset( $data['code'] ) ? $data['code'] : null;
	}

	/**
	 * The endpoint adds no gateway hooks: after WooCommerce loads its list, each gateway
	 * hook has one Paidy callback. Fails if anything creates another WC_Gateway_Paidy.
	 */
	public function test_gateway_hooks_are_registered_once() {
		$hooks  = array(
			'woocommerce_receipt_paidy',
			'woocommerce_update_options_payment_gateways_paidy',
			'admin_print_footer_scripts',
		);
		$before = array();
		foreach ( $hooks as $hook ) {
			$before[ $hook ] = $this->paidy_callback_count( $hook );
		}

		new WC_Paidy_Endpoint();
		WC()->payment_gateways()->payment_gateways();

		foreach ( $hooks as $hook ) {
			$this->assertSame( 1, $this->paidy_callback_count( $hook ) - $before[ $hook ], $hook );
		}
		// The thank-you callback is registered once, on the class, not on each instance.
		$this->assertSame( 0, $this->paidy_callback_count( 'woocommerce_thankyou_paidy' ) );
		$this->assertNotFalse( has_action( 'woocommerce_thankyou_paidy', array( 'WC_Gateway_Paidy', 'handle_thankyou' ) ) );
	}

	/**
	 * A thank-you page that has not loaded the payment gateways (e.g. one made by a funnel
	 * plugin outside the checkout) still completes the Paidy order once.
	 */
	public function test_thankyou_page_without_loaded_gateways_completes_order() {
		$this->save_settings(
			array(
				'environment'         => 'sandbox',
				'test_api_secret_key' => self::SECRET_KEY,
			)
		);
		$product = new WC_Product_Simple();
		$product->set_name( 'Product' );
		$product->set_regular_price( '1000' );
		$product->save();
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_payment_method( 'paidy' );
		$order->calculate_totals( false );
		$order->set_status( 'pending' );
		$order->save();
		$payment_id             = 'pay_WD1KIj4AALQAIMtZ';
		$this->paidy_api_body   = wp_json_encode(
			array(
				'id'     => $payment_id,
				'status' => 'authorized',
				'amount' => (float) $order->get_total(),
				'order'  => array( 'order_ref' => (string) $order->get_id() ),
			)
		);
		$_GET['transaction_id'] = $payment_id;
		$this->assertNull( $this->gateway_list_property()->getValue() );

		do_action( 'woocommerce_thankyou_paidy', $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'processing', $order->get_status() );
		$this->assertSame( $payment_id, $order->get_transaction_id() );
		$this->assertSame( array( 'https://api.paidy.com/payments/' . $payment_id ), $this->requested_urls );
	}

	/**
	 * The order-pay page prints the Paidy Checkout script once.
	 */
	public function test_order_pay_page_prints_paidy_script_once() {
		$this->save_settings(
			array(
				'enabled'             => 'yes',
				'environment'         => 'sandbox',
				'test_api_public_key' => 'pk_test_dummy',
			)
		);
		$order = wc_create_order();
		$order->set_payment_method( 'paidy' );
		$order->set_total( 1000 );
		$order->set_status( 'pending' );
		$order->save();
		remove_all_actions( 'woocommerce_receipt_paidy' );

		new WC_Paidy_Endpoint();
		WC()->payment_gateways()->payment_gateways();
		ob_start();
		do_action( 'woocommerce_receipt_paidy', $order->get_id() );
		$output = (string) ob_get_clean();

		$this->assertSame( 1, substr_count( $output, 'Paidy.configure(' ) );
	}

	/**
	 * A webhook signed with the configured secret key is accepted.
	 */
	public function test_signed_webhook_is_accepted() {
		// The same key in both environments: this test is about the gateway the
		// endpoint uses, not which key it picks.
		$this->save_settings(
			array(
				'api_secret_key'      => self::SECRET_KEY,
				'test_api_secret_key' => self::SECRET_KEY,
			)
		);

		$response = $this->send_webhook( self::TEST_WEBHOOK_BODY, hash_hmac( 'sha256', self::TEST_WEBHOOK_BODY, self::SECRET_KEY ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( json_decode( self::TEST_WEBHOOK_BODY, true ), $response->get_data() );
	}

	/**
	 * A webhook with a wrong signature is refused.
	 */
	public function test_wrongly_signed_webhook_is_refused() {
		$this->save_settings(
			array(
				'api_secret_key'      => self::SECRET_KEY,
				'test_api_secret_key' => self::SECRET_KEY,
			)
		);

		$response = $this->send_webhook( self::TEST_WEBHOOK_BODY, hash_hmac( 'sha256', self::TEST_WEBHOOK_BODY, 'sk_test_other' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'paidy_invalid_signature', $this->error_code( $response ) );
	}

	/**
	 * An unsigned webhook is accepted from a Paidy address and refused from any other.
	 */
	public function test_unsigned_webhook_is_checked_against_paidy_addresses() {
		$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
		$response               = $this->send_webhook( self::TEST_WEBHOOK_BODY );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'paidy_unauthorized', $this->error_code( $response ) );

		$_SERVER['REMOTE_ADDR'] = WC_Paidy_Endpoint::PAIDY_WEBHOOK_IPS[0];
		$response               = $this->send_webhook( self::TEST_WEBHOOK_BODY );
		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * A signed webhook is refused with a configuration error when no secret key is set.
	 */
	public function test_signed_webhook_without_secret_key_is_refused() {
		$this->save_settings( array() );

		$response = $this->send_webhook( self::TEST_WEBHOOK_BODY, hash_hmac( 'sha256', self::TEST_WEBHOOK_BODY, self::SECRET_KEY ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'paidy_config_error', $this->error_code( $response ) );
	}

	/**
	 * When Paidy is not in WooCommerce's gateway list, a webhook gets 503 so that Paidy sends it again.
	 */
	public function test_webhook_without_paidy_in_gateway_list_is_unavailable() {
		$this->save_settings(
			array(
				'api_secret_key'      => self::SECRET_KEY,
				'test_api_secret_key' => self::SECRET_KEY,
			)
		);
		add_filter( 'woocommerce_payment_gateways', array( $this, 'remove_paidy_gateway' ), PHP_INT_MAX );

		$response = $this->send_webhook( self::TEST_WEBHOOK_BODY, hash_hmac( 'sha256', self::TEST_WEBHOOK_BODY, self::SECRET_KEY ) );
		$this->assertSame( 503, $response->get_status() );
		$this->assertSame( 'paidy_gateway_unavailable', $this->error_code( $response ) );

		$_SERVER['REMOTE_ADDR'] = WC_Paidy_Endpoint::PAIDY_WEBHOOK_IPS[0];
		$response               = $this->send_webhook( self::TEST_WEBHOOK_BODY );
		$this->assertSame( 503, $response->get_status() );
		$this->assertSame( 'paidy_gateway_unavailable', $this->error_code( $response ) );
	}
}
