<?php
/**
 * Tests for the Paidy payment ID format check.
 *
 * WC_Gateway_Paidy::paidy_get_payment_data() validates the payment ID before
 * interpolating it into the Paidy API URL, because the ID can come from the
 * buyer-controllable thank-you URL. The check used to accept only letters,
 * digits and "_", but Paidy also issues IDs containing "-" (issue #223), and
 * those payments could never be verified: the thank-you page and the
 * authorize_success webhook both left the order pending until it was
 * auto-cancelled. Covers the relaxed check and the injection vectors it must
 * keep rejecting.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Payment_Id_Format_Test
 */
class WC_Paidy_Payment_Id_Format_Test extends WP_UnitTestCase {

	/**
	 * Gateway instance under test.
	 *
	 * @var WC_Gateway_Paidy
	 */
	private $gateway;

	/**
	 * URLs requested through the WP HTTP API during a test.
	 *
	 * @var string[]
	 */
	private $requested_urls = array();

	/**
	 * Payment object the mocked Paidy API returns, or null for an empty body.
	 *
	 * @var array|null
	 */
	private $mock_payment = null;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-gateway-paidy.php';
		}
		$this->assertTrue( class_exists( 'WC_Gateway_Paidy' ), 'WC_Gateway_Paidy should be loadable.' );
		$this->gateway = new WC_Gateway_Paidy();

		$this->requested_urls = array();
		$this->mock_payment   = null;
		add_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10, 3 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10 );
		parent::tearDown();
	}

	/**
	 * Short-circuit every HTTP request, recording the URL and answering as Paidy would.
	 *
	 * @param false|array|WP_Error $preempt     Whether to preempt the request.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 * @return array Mocked HTTP response.
	 */
	public function mock_paidy_api( $preempt, $parsed_args, $url ) {
		$this->requested_urls[] = $url;

		return array(
			'headers'  => array(),
			'body'     => null === $this->mock_payment ? '' : wp_json_encode( $this->mock_payment ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Payment IDs Paidy actually issues: letters, digits, "_" and "-".
	 *
	 * @return array[]
	 */
	public function valid_payment_id_provider() {
		return array(
			'alphanumeric'     => array( 'pay_WD1KIj4AALQAIMtZ' ),
			'underscore'       => array( 'pay_aii8_kYAAEYA2BDW' ),
			'one hyphen'       => array( 'pay_WD1K-j4AALQAIMtZ' ),
			'two hyphens'      => array( 'pay_W-1KIj4AALQAI-tZ' ),
			'hyphen and score' => array( 'pay_W-1K_j4AALQAIMtZ' ),
			'paidy test id'    => array( 'pay_0000000000000001' ),
		);
	}

	/**
	 * A well-formed payment ID must be looked up at the Paidy API as-is.
	 *
	 * @dataProvider valid_payment_id_provider
	 *
	 * @param string $payment_id Payment ID to query.
	 */
	public function test_valid_payment_id_is_queried( $payment_id ) {
		$this->mock_payment = array(
			'id'     => $payment_id,
			'status' => 'authorized',
		);

		$result = $this->gateway->paidy_get_payment_data( $payment_id );

		$this->assertSame( array( 'https://api.paidy.com/payments/' . $payment_id ), $this->requested_urls );
		$this->assertSame( $this->mock_payment, $result );
	}

	/**
	 * Strings that must never reach the API URL.
	 *
	 * @return array[]
	 */
	public function invalid_payment_id_provider() {
		return array(
			'path traversal'   => array( 'pay_abc/../x' ),
			'query string'     => array( 'pay_abc?x=1' ),
			'fragment'         => array( 'pay_abc#x' ),
			'percent encoding' => array( 'pay_abc%2Fx' ),
			'whitespace'       => array( 'pay_abc x' ),
			'newline'          => array( "pay_abc\nx" ),
			'trailing newline' => array( "pay_WD1KIj4AALQAIMtZ\n" ),
			'prefix only'      => array( 'pay_' ),
			'wrong prefix'     => array( 'cap_WD1KIj4AALQAIMtZ' ),
			'uppercase prefix' => array( 'PAY_WD1KIj4AALQAIMtZ' ),
			'leading space'    => array( ' pay_WD1KIj4AALQAIMtZ' ),
			'empty'            => array( '' ),
			'non-ascii hyphen' => array( 'pay_WD1K‐j4AALQAIMtZ' ),
		);
	}

	/**
	 * A malformed payment ID must be rejected before any HTTP request is made.
	 *
	 * @dataProvider invalid_payment_id_provider
	 *
	 * @param string $payment_id Payment ID to query.
	 */
	public function test_invalid_payment_id_is_rejected_without_request( $payment_id ) {
		$result = $this->gateway->paidy_get_payment_data( $payment_id );

		$this->assertNull( $result );
		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * Issue #223: a payment whose ID contains "-" must verify for its order.
	 */
	public function test_hyphenated_payment_id_verifies_for_order() {
		$order = wc_create_order();
		$order->set_total( 1000 );
		$order->save();

		$payment_id         = 'pay_WD1K-j4AALQAI-tZ';
		$this->mock_payment = array(
			'id'     => $payment_id,
			'status' => 'authorized',
			'amount' => 1000,
			'order'  => array(
				'order_ref' => (string) $order->get_id(),
			),
		);

		$this->assertTrue( $this->gateway->paidy_verify_payment_for_order( $order, $payment_id ) );
		$this->assertCount( 1, $this->requested_urls );
	}

	/**
	 * Relaxing the format check must not weaken the verification that follows it.
	 */
	public function test_hyphenated_payment_id_for_another_order_does_not_verify() {
		$order = wc_create_order();
		$order->set_total( 1000 );
		$order->save();

		$payment_id         = 'pay_WD1K-j4AALQAI-tZ';
		$this->mock_payment = array(
			'id'     => $payment_id,
			'status' => 'authorized',
			'amount' => 1000,
			'order'  => array(
				'order_ref' => (string) ( $order->get_id() + 1 ),
			),
		);

		$this->assertFalse( $this->gateway->paidy_verify_payment_for_order( $order, $payment_id ) );
	}
}
