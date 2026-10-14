<?php
/**
 * Tests that a Paidy capture records its request URL in debug mode.
 *
 * WC_Gateway_Paidy::jp4wc_order_paidy_status_completed() passed the boolean true as the
 * debug flag when it logged the capture URL, and JP4WC_Framework::jp4wc_debug_log() writes
 * only when the flag is 'yes'. The "Send URL is following." line was therefore never written, even
 * with debug mode on, so a capture that failed with a WP_Error left no trace of the URL
 * it was sent to (issue #247).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Capture_Debug_Log_Test
 */
class WC_Paidy_Capture_Debug_Log_Test extends WP_UnitTestCase {

	/**
	 * Payment ID saved on the test order.
	 */
	const PAYMENT_ID = 'pay_WD1KIj4AALQAIMtZ';

	/**
	 * Gateway under test.
	 *
	 * @var WC_Gateway_Paidy
	 */
	private $gateway;

	/**
	 * Logger that records the entries instead of writing them.
	 *
	 * @var WC_Logger
	 */
	private $logger;

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

		$this->logger = new class() extends WC_Logger {
			/**
			 * Recorded entries, each with level, message and context.
			 *
			 * @var array[]
			 */
			public $entries = array();

			/**
			 * Record a log entry.
			 *
			 * @param string $level   Log level.
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function log( $level, $message, $context = array() ) {
				$this->entries[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
		};
		add_filter( 'woocommerce_logging_class', array( $this, 'recording_logger' ) );

		add_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10, 3 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10 );
		remove_filter( 'woocommerce_logging_class', array( $this, 'recording_logger' ) );
		// wc_get_logger() keeps the recording logger, which is a WC_Logger; put a plain one back.
		$fresh_logger = static function () {
			return new WC_Logger();
		};
		add_filter( 'woocommerce_logging_class', $fresh_logger );
		wc_get_logger();
		remove_filter( 'woocommerce_logging_class', $fresh_logger );
		parent::tearDown();
	}

	/**
	 * Give wc_get_logger() the recording logger.
	 *
	 * @return WC_Logger
	 */
	public function recording_logger() {
		return $this->logger;
	}

	/**
	 * Short-circuit every HTTP request with the answer of a Paidy API that is down.
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

		return array(
			'headers'  => array(),
			'body'     => '<html><body><h1>502 Bad Gateway</h1></body></html>',
			'response' => array(
				'code'    => 502,
				'message' => get_status_header_desc( 502 ),
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Create a processing Paidy order that has not been captured.
	 *
	 * @return WC_Order
	 */
	private function create_order() {
		$order = wc_create_order();
		$order->set_payment_method( 'paidy' );
		$order->set_total( 1000 );
		$order->set_transaction_id( self::PAYMENT_ID );
		$order->set_status( 'processing' );
		$order->save();

		return $order;
	}

	/**
	 * Recorded log entries that name the capture URL.
	 *
	 * @return array[]
	 */
	private function capture_url_entries() {
		$line = 'Send URL is following. : https://api.paidy.com/payments/' . self::PAYMENT_ID . '/captures';

		return array_values(
			array_filter(
				$this->logger->entries,
				static function ( $entry ) use ( $line ) {
					return false !== strpos( $entry['message'], $line );
				}
			)
		);
	}

	/**
	 * In debug mode, the capture URL is logged under the paidy-wc source.
	 */
	public function test_capture_url_is_logged_in_debug_mode() {
		$order                = $this->create_order();
		$this->gateway->debug = 'yes';

		$this->gateway->jp4wc_order_paidy_status_completed( $order->get_id() );

		$entries = $this->capture_url_entries();
		$this->assertCount( 1, $entries );
		$this->assertSame( WC_Log_Levels::DEBUG, $entries[0]['level'] );
		$this->assertSame( 'paidy-wc', $entries[0]['context']['source'] );
	}

	/**
	 * With debug mode off, the capture URL is not logged.
	 */
	public function test_capture_url_is_not_logged_when_debug_is_off() {
		$order                = $this->create_order();
		$this->gateway->debug = 'no';

		$this->gateway->jp4wc_order_paidy_status_completed( $order->get_id() );

		$this->assertSame( array(), $this->capture_url_entries() );
	}
}
