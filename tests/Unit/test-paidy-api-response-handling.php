<?php
/**
 * Tests for how WC_Gateway_Paidy handles Paidy's answer to cancel, capture and refund.
 *
 * The three requests used to read `$response['body']` before checking
 * is_wp_error(), so a timeout or any other transport error stopped the request
 * with "Cannot use object of type WP_Error as array". For a refund that Error
 * slipped past wc_create_refund()'s catch ( Exception ), leaving a WooCommerce
 * refund that Paidy never made. process_refund() also returned true when the
 * response had no "status", so a 502 HTML page was recorded as a refund made
 * through the gateway (issue #231).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Api_Response_Handling_Test
 */
class WC_Paidy_Api_Response_Handling_Test extends WP_UnitTestCase {

	/**
	 * Payment ID saved on the test orders.
	 */
	const PAYMENT_ID = 'pay_WD1KIj4AALQAIMtZ';

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
	 * Response the mocked Paidy API returns.
	 *
	 * @var array|WP_Error
	 */
	private $response;

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
		$this->response       = new WP_Error( 'http_request_failed', 'No response was mocked.' );
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
	 * Short-circuit every HTTP request, recording the URL and returning the mocked response.
	 *
	 * @param false|array|WP_Error $preempt     Whether to preempt the request.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 * @return array|WP_Error Mocked HTTP response.
	 */
	public function mock_paidy_api( $preempt, $parsed_args, $url ) {
		$this->requested_urls[] = $url;

		return $this->response;
	}

	/**
	 * Build an HTTP response.
	 *
	 * @param int    $code HTTP status code.
	 * @param string $body Response body.
	 * @return array
	 */
	private function http_response( $code, $body ) {
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => get_status_header_desc( $code ),
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Responses that mean Paidy did not carry out the request.
	 *
	 * @return array[]
	 */
	public function failed_response_provider() {
		return array(
			'timeout'                => array( 'timeout' ),
			'502 html page'          => array( 'bad_gateway' ),
			'200 without status'     => array( 'no_status' ),
			'200 with non-json body' => array( 'not_json' ),
		);
	}

	/**
	 * Set the mocked response for a failed_response_provider() case.
	 *
	 * Built here rather than in the provider, which runs before WordPress is set up.
	 *
	 * @param string $failure Case name.
	 */
	private function mock_failure( $failure ) {
		switch ( $failure ) {
			case 'timeout':
				$this->response = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
				break;
			case 'bad_gateway':
				$this->response = $this->http_response( 502, '<html><body><h1>502 Bad Gateway</h1></body></html>' );
				break;
			case 'no_status':
				$this->response = $this->http_response( 200, '{}' );
				break;
			case 'not_json':
				$this->response = $this->http_response( 200, 'OK' );
				break;
		}
	}

	/**
	 * Create a Paidy order.
	 *
	 * @param string|null $capture_id Paidy capture ID saved on the order, or null for none.
	 * @return WC_Order
	 */
	private function create_paidy_order( $capture_id = null ) {
		$order = wc_create_order();
		$order->set_payment_method( 'paidy' );
		$order->set_total( 1000 );
		$order->set_transaction_id( self::PAYMENT_ID );
		if ( null !== $capture_id ) {
			$order->update_meta_data( 'paidy_capture_id', $capture_id );
		}
		$order->save();

		return $order;
	}

	/**
	 * Notes added to an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string All note contents, one per line.
	 */
	private function order_notes( $order ) {
		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		return implode( "\n", wp_list_pluck( $notes, 'content' ) );
	}

	/**
	 * A cancellation that Paidy did not carry out must fail with a note, not a fatal error.
	 *
	 * @dataProvider failed_response_provider
	 *
	 * @param string $failure Case name.
	 */
	public function test_cancel_fails_when_paidy_does_not_close( $failure ) {
		$order = $this->create_paidy_order();
		$this->mock_failure( $failure );

		$this->assertFalse( $this->gateway->paidy_order_paidy_status_processing_to_cancelled( $order->get_id() ) );
		$this->assertCount( 1, $this->requested_urls );
		$this->assertNotSame( '', $this->order_notes( $order ) );
	}

	/**
	 * A transport error is reported in the order note.
	 */
	public function test_cancel_notes_transport_error() {
		$order = $this->create_paidy_order();
		$this->mock_failure( 'timeout' );

		$this->gateway->paidy_order_paidy_status_processing_to_cancelled( $order->get_id() );

		$this->assertStringContainsString( 'cURL error 28', $this->order_notes( $order ) );
	}

	/**
	 * A closed payment is a successful cancellation.
	 */
	public function test_cancel_succeeds_when_paidy_closes() {
		$order          = $this->create_paidy_order();
		$this->response = $this->http_response(
			200,
			wp_json_encode(
				array(
					'id'     => self::PAYMENT_ID,
					'status' => 'closed',
				)
			)
		);

		$this->assertTrue( $this->gateway->paidy_order_paidy_status_processing_to_cancelled( $order->get_id() ) );
		$this->assertSame( '', $this->order_notes( $order ) );
	}

	/**
	 * A capture that Paidy did not carry out must fail without saving a capture ID.
	 *
	 * @dataProvider failed_response_provider
	 *
	 * @param string $failure Case name.
	 */
	public function test_capture_fails_when_paidy_does_not_capture( $failure ) {
		$order = $this->create_paidy_order();
		$this->mock_failure( $failure );

		$this->assertFalse( $this->gateway->jp4wc_order_paidy_status_completed( $order->get_id() ) );
		$this->assertCount( 1, $this->requested_urls );
		$this->assertSame( '', wc_get_order( $order->get_id() )->get_meta( 'paidy_capture_id' ) );
		$this->assertNotSame( '', $this->order_notes( $order ) );
	}

	/**
	 * A 502 page is reported with its HTTP status.
	 */
	public function test_capture_notes_http_status_of_server_error() {
		$order = $this->create_paidy_order();
		$this->mock_failure( 'bad_gateway' );

		$this->gateway->jp4wc_order_paidy_status_completed( $order->get_id() );

		$this->assertStringContainsString( 'HTTP 502', $this->order_notes( $order ) );
	}

	/**
	 * A closed payment with the order's amount is a successful capture.
	 */
	public function test_capture_succeeds_when_paidy_captures() {
		$order          = $this->create_paidy_order();
		$this->response = $this->http_response(
			200,
			wp_json_encode(
				array(
					'id'       => self::PAYMENT_ID,
					'status'   => 'closed',
					'amount'   => 1000,
					'captures' => array( array( 'id' => 'cap_WD1KIj4AALQAIMtZ' ) ),
				)
			)
		);

		$this->assertTrue( $this->gateway->jp4wc_order_paidy_status_completed( $order->get_id() ) );
		$this->assertSame( 'cap_WD1KIj4AALQAIMtZ', wc_get_order( $order->get_id() )->get_meta( 'paidy_capture_id' ) );
	}

	/**
	 * A refund that Paidy did not carry out must fail, not report success.
	 *
	 * @dataProvider failed_response_provider
	 *
	 * @param string $failure Case name.
	 */
	public function test_refund_fails_when_paidy_does_not_refund( $failure ) {
		$order = $this->create_paidy_order( 'cap_WD1KIj4AALQAIMtZ' );
		$this->mock_failure( $failure );

		$this->assertFalse( $this->gateway->process_refund( $order->get_id(), 1000 ) );
		$this->assertCount( 1, $this->requested_urls );
		$this->assertSame( '', wc_get_order( $order->get_id() )->get_meta( 'paidy_refund_id' ) );
		$this->assertNotSame( '', $this->order_notes( $order ) );
	}

	/**
	 * An error object from Paidy fails the refund without claiming the response had no status.
	 */
	public function test_refund_fails_on_paidy_error_response() {
		$order          = $this->create_paidy_order( 'cap_WD1KIj4AALQAIMtZ' );
		$this->response = $this->http_response(
			400,
			wp_json_encode(
				array(
					'status' => 400,
					'code'   => 'request_content.malformed',
					'title'  => 'Malformed request content',
				)
			)
		);

		$this->assertFalse( $this->gateway->process_refund( $order->get_id(), 1000 ) );
		$this->assertStringNotContainsString( 'There was no status', $this->order_notes( $order ) );
	}

	/**
	 * A closed payment is a successful refund, and its refund ID is saved.
	 */
	public function test_refund_succeeds_when_paidy_refunds() {
		$order          = $this->create_paidy_order( 'cap_WD1KIj4AALQAIMtZ' );
		$this->response = $this->http_response(
			200,
			wp_json_encode(
				array(
					'id'      => self::PAYMENT_ID,
					'status'  => 'closed',
					'refunds' => array( array( 'id' => 'ref_WD1KIj4AALQAIMtZ' ) ),
				)
			)
		);

		$this->assertTrue( $this->gateway->process_refund( $order->get_id(), 1000 ) );
		$this->assertSame( array( 'ref_WD1KIj4AALQAIMtZ' ), wc_get_order( $order->get_id() )->get_meta( 'paidy_refund_id' ) );
	}

	/**
	 * A refund from the order screen that Paidy did not carry out must leave no WooCommerce refund behind.
	 *
	 * @dataProvider failed_response_provider
	 *
	 * @param string $failure Case name.
	 */
	public function test_wc_create_refund_keeps_no_refund_when_paidy_fails( $failure ) {
		$order = $this->create_paidy_order( 'cap_WD1KIj4AALQAIMtZ' );
		$this->mock_failure( $failure );

		// PHPUnit turns a warning into an exception, which wc_refund_payment() catches
		// and reports as a failed refund. A live site only logs the warning and carries
		// on, so let warnings through here; the tests above call process_refund()
		// directly and still fail on any warning.
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Mimics production warning handling for this one call.
			static function () {
				return true;
			},
			E_WARNING
		);
		try {
			$refund = wc_create_refund(
				array(
					'order_id'       => $order->get_id(),
					'amount'         => 1000,
					'refund_payment' => true,
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertWPError( $refund );
		$this->assertCount( 1, $this->requested_urls, 'The refund should have been sent to Paidy.' );
		$this->assertSame( array(), wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * A refund from the order screen that Paidy carried out is recorded as refunded through the gateway.
	 */
	public function test_wc_create_refund_records_refund_when_paidy_refunds() {
		$order          = $this->create_paidy_order( 'cap_WD1KIj4AALQAIMtZ' );
		$this->response = $this->http_response(
			200,
			wp_json_encode(
				array(
					'id'      => self::PAYMENT_ID,
					'status'  => 'closed',
					'refunds' => array( array( 'id' => 'ref_WD1KIj4AALQAIMtZ' ) ),
				)
			)
		);

		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 1000,
				'refund_payment' => true,
			)
		);

		$this->assertInstanceOf( 'WC_Order_Refund', $refund );
		$this->assertTrue( $refund->get_refunded_payment() );
		$this->assertSame( array( 'https://api.paidy.com/payments/' . self::PAYMENT_ID . '/refunds' ), $this->requested_urls );
	}
}
