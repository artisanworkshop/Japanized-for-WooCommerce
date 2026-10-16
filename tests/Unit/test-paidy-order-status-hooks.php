<?php
/**
 * Tests that a Paidy order status change reaches Paidy once.
 *
 * WC_Gateway_Paidy registered its capture, cancel and refund callbacks in its
 * constructor, and the class is created several times in one request: by
 * WooCommerce's gateway list, by WC_Paidy_Endpoint and, in the admin, by
 * paidy_redirect_to_wizard(). Cancelling a processing order in the admin
 * therefore sent the close request three times; Paidy closed the payment on
 * the first and answered the others with 409, which left two "Cancelled
 * processing has not been completed" notes on an order that had been
 * cancelled. A failed capture or refund was likewise sent once per instance
 * (issue #241).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Order_Status_Hooks_Test
 */
class WC_Paidy_Order_Status_Hooks_Test extends WP_UnitTestCase {

	/**
	 * Payment ID saved on the test orders.
	 */
	const PAYMENT_ID = 'pay_WD1KIj4AALQAIMtZ';

	/**
	 * Paidy API URLs requested during a test.
	 *
	 * @var string[]
	 */
	private $requested_urls = array();

	/**
	 * Responses the mocked Paidy API returns, in order. The last one repeats.
	 *
	 * @var array[]
	 */
	private $responses = array();

	/**
	 * WooCommerce's gateway list, kept while a test unloads it. False when nothing is kept.
	 *
	 * @var WC_Payment_Gateways|null|false
	 */
	private $kept_gateway_list = false;

	/**
	 * PayPal's singleton, kept while a test unloads the gateway list. False when nothing is kept.
	 *
	 * Loading the list again replaces it (WC_Payment_Gateways::init() calls
	 * WC_Gateway_Paypal::set_instance()).
	 *
	 * @var WC_Gateway_Paypal|null|false
	 */
	private $kept_paypal_instance = false;

	/**
	 * Logger recording what is logged during a test, or null when the test does not record.
	 *
	 * @var WC_Logger|null
	 */
	private $logger = null;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-gateway-paidy.php';
		}
		$this->assertTrue( class_exists( 'WC_Gateway_Paidy' ), 'WC_Gateway_Paidy should be loadable.' );

		$this->requested_urls = array();
		$this->responses      = array( $this->http_response( 502, '<html><body><h1>502 Bad Gateway</h1></body></html>' ) );
		add_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10, 3 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_paidy_api' ), 10 );
		if ( false !== $this->kept_gateway_list ) {
			$this->gateway_list_property()->setValue( null, $this->kept_gateway_list );
			$this->kept_gateway_list = false;
		}
		if ( false !== $this->kept_paypal_instance ) {
			$this->paypal_instance_property()->setValue( null, $this->kept_paypal_instance );
			$this->kept_paypal_instance = false;
		}
		if ( null !== $this->logger ) {
			remove_filter( 'woocommerce_logging_class', array( $this, 'recording_logger' ) );
			// wc_get_logger() keeps the recording logger, which is a WC_Logger; put a plain one back.
			$fresh_logger = static function () {
				return new WC_Logger();
			};
			add_filter( 'woocommerce_logging_class', $fresh_logger );
			wc_get_logger();
			remove_filter( 'woocommerce_logging_class', $fresh_logger );
			$this->logger = null;
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
	 * Put WooCommerce back in the state of a request that has not loaded the payment gateways,
	 * as when an order status is changed through the REST API or a bulk action.
	 */
	private function unload_gateway_list() {
		$this->kept_gateway_list = $this->gateway_list_property()->getValue();
		$this->gateway_list_property()->setValue( null, null );
		if ( $this->paypal_instance_property() ) {
			$this->kept_paypal_instance = $this->paypal_instance_property()->getValue();
		}
	}

	/**
	 * Record what is logged through wc_get_logger() for the rest of the test.
	 */
	private function record_log() {
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
	 * Short-circuit every HTTP request, recording the Paidy API URLs.
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

		return count( $this->responses ) > 1 ? array_shift( $this->responses ) : $this->responses[0];
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
	 * Create an order with the given status.
	 *
	 * @param string $status         Order status.
	 * @param string $payment_method Payment method ID.
	 * @return WC_Order
	 */
	private function create_order( $status, $payment_method = 'paidy' ) {
		$order = wc_create_order();
		$order->set_payment_method( $payment_method );
		$order->set_total( 1000 );
		$order->set_transaction_id( self::PAYMENT_ID );
		// A capture ID keeps the completed status below from sending a capture.
		$order->update_meta_data( 'paidy_capture_id', 'cap_WD1KIj4AALQAIMtZ' );
		$order->set_status( $status );
		$order->save();

		return $order;
	}

	/**
	 * Load WooCommerce's gateway list and create the other gateway instances an admin request
	 * used to have (paidy_redirect_to_wizard() and WC_Paidy_Endpoint each created one).
	 */
	private function create_extra_gateway_instances() {
		WC()->payment_gateways()->payment_gateways();
		new WC_Gateway_Paidy();
		new WC_Gateway_Paidy();
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
	 * Number of callbacks attached to a hook.
	 *
	 * @param string $hook Hook name.
	 * @return int
	 */
	private function callback_count( $hook ) {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook ] ) ) {
			return 0;
		}
		$count = 0;
		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			$count += count( $callbacks );
		}

		return $count;
	}

	/**
	 * Make the mocked Paidy API close the payment once and refuse to close it again.
	 */
	private function mock_close_then_conflict() {
		$this->responses = array(
			$this->http_response(
				200,
				wp_json_encode(
					array(
						'id'     => self::PAYMENT_ID,
						'status' => 'closed',
					)
				)
			),
			// What Paidy answers to a second close of the same payment.
			$this->http_response(
				409,
				wp_json_encode(
					array(
						'status'      => 409,
						'code'        => 'service.conflict',
						'title'       => 'service.conflict',
						'description' => 'The request could not be completed because of a conflict.',
					)
				)
			),
		);
	}

	/**
	 * Cancelling a processing order closes the payment once and leaves no failure note.
	 */
	public function test_cancel_sends_close_once() {
		$order = $this->create_order( 'processing' );
		$this->create_extra_gateway_instances();
		$this->mock_close_then_conflict();

		$order->update_status( 'cancelled' );

		$this->assertSame( array( 'https://api.paidy.com/payments/' . self::PAYMENT_ID . '/close' ), $this->requested_urls );
		$this->assertStringNotContainsString( 'HTTP 409', $this->order_notes( $order ) );
	}

	/**
	 * The payment is closed once even in a request that has not loaded the payment gateways.
	 */
	public function test_cancel_sends_close_once_when_gateways_are_not_loaded() {
		$order = $this->create_order( 'processing' );
		$this->unload_gateway_list();
		$this->mock_close_then_conflict();

		$order->update_status( 'cancelled' );

		$this->assertSame( array( 'https://api.paidy.com/payments/' . self::PAYMENT_ID . '/close' ), $this->requested_urls );
		$this->assertStringNotContainsString( 'HTTP 409', $this->order_notes( $order ) );
	}

	/**
	 * A Paidy order whose status change cannot reach Paidy, because Paidy is not in
	 * WooCommerce's gateway list, leaves a warning in the log.
	 */
	public function test_status_change_without_paidy_in_gateway_list_is_logged() {
		$order = $this->create_order( 'processing' );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'remove_paidy_gateway' ), PHP_INT_MAX );
		$this->unload_gateway_list();
		$this->record_log();

		$order->update_status( 'cancelled' );

		$this->assertSame( array(), $this->requested_urls );
		$warnings = array_values(
			array_filter(
				$this->logger->entries,
				static function ( $entry ) {
					return 'warning' === $entry['level'] && isset( $entry['context']['source'] ) && 'paidy-wc' === $entry['context']['source'];
				}
			)
		);
		$this->assertCount( 1, $warnings );
		$this->assertStringContainsString( '#' . $order->get_id(), $warnings[0]['message'] );
	}

	/**
	 * A status change of an order paid by another method does not load the payment gateways.
	 */
	public function test_other_payment_method_does_not_load_gateways() {
		$order = $this->create_order( 'processing', 'bacs' );
		$this->unload_gateway_list();

		$order->update_status( 'cancelled' );

		$this->assertNull( $this->gateway_list_property()->getValue() );
	}

	/**
	 * A capture that Paidy refuses is sent once.
	 */
	public function test_failed_capture_is_sent_once() {
		$order = $this->create_order( 'processing' );
		$order->delete_meta_data( 'paidy_capture_id' );
		$order->save();
		$this->create_extra_gateway_instances();

		$order->update_status( 'completed' );

		$this->assertSame( array( 'https://api.paidy.com/payments/' . self::PAYMENT_ID . '/captures' ), $this->requested_urls );
	}

	/**
	 * A refund that Paidy refuses when a completed order is cancelled is sent once.
	 */
	public function test_failed_refund_is_sent_once() {
		$order = $this->create_order( 'completed' );
		$this->create_extra_gateway_instances();

		$order->update_status( 'cancelled' );

		$this->assertSame( array( 'https://api.paidy.com/payments/' . self::PAYMENT_ID . '/refunds' ), $this->requested_urls );
	}

	/**
	 * An order paid by another method is not sent to Paidy.
	 */
	public function test_other_payment_method_is_not_sent() {
		$order = $this->create_order( 'processing', 'bacs' );
		$this->create_extra_gateway_instances();

		$order->update_status( 'cancelled' );
		$order->update_status( 'completed' );

		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * Creating a gateway instance does not attach order status callbacks to it.
	 */
	public function test_gateway_instance_registers_no_order_status_callbacks() {
		$gateway = new WC_Gateway_Paidy();

		$this->assertFalse( has_action( 'woocommerce_order_status_completed', array( $gateway, 'jp4wc_order_paidy_status_completed' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_status_processing_to_cancelled', array( $gateway, 'paidy_order_paidy_status_processing_to_cancelled' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_status_completed_to_cancelled', array( $gateway, 'paidy_order_paidy_status_completed_to_cancelled' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_status_completed', array( 'WC_Gateway_Paidy', 'handle_order_status_completed' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_status_processing_to_cancelled', array( 'WC_Gateway_Paidy', 'handle_order_status_processing_to_cancelled' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_status_completed_to_cancelled', array( 'WC_Gateway_Paidy', 'handle_order_status_completed_to_cancelled' ) ) );
	}

	/**
	 * The wizard redirect check does not create a gateway instance.
	 */
	public function test_wizard_redirect_check_creates_no_gateway() {
		$before = $this->callback_count( 'woocommerce_receipt_paidy' );

		delete_option( 'paidy_do_activation_redirect' );
		paidy_redirect_to_wizard();
		$this->assertSame( $before, $this->callback_count( 'woocommerce_receipt_paidy' ) );

		// Paidy is enabled, so the redirect is not due and the flag stays.
		update_option( 'paidy_do_activation_redirect', true );
		update_option( 'woocommerce_paidy_settings', array( 'enabled' => 'yes' ) );
		paidy_redirect_to_wizard();
		$this->assertSame( $before, $this->callback_count( 'woocommerce_receipt_paidy' ) );
		$this->assertNotFalse( get_option( 'paidy_do_activation_redirect', false ) );
	}
}
