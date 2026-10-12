<?php
/**
 * Tests for Paidy refunds after the first one and for the refund when a completed order is cancelled.
 *
 * The gateway's process_refund() returned early for any order that had a
 * paidy_refund_id. The guard was added while the completed-to-cancelled callback
 * ran once per gateway instance, but WooCommerce calls process_refund() once per
 * refund, so every refund after the first one failed, and cancelling a completed
 * order after a partial refund refunded nothing. The refund on cancellation also
 * sent the order total and left no refund in WooCommerce (issue #243).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Refunds_Test
 */
class WC_Paidy_Refunds_Test extends WP_UnitTestCase {

	/**
	 * Payment ID saved on the test orders.
	 */
	const PAYMENT_ID = 'pay_WD1KIj4AALQAIMtZ';

	/**
	 * Capture ID saved on the test orders.
	 */
	const CAPTURE_ID = 'cap_WD1KIj4AALQAIMtZ';

	/**
	 * Refund ID of a refund made before the test.
	 */
	const EARLIER_REFUND_ID = 'ref_WD1N-j4AALQAI_vB';

	/**
	 * Gateway instance for calling process_refund() directly.
	 *
	 * @var WC_Gateway_Paidy
	 */
	private $gateway;

	/**
	 * Paidy API requests made during a test, each with its URL and the amount it sent.
	 *
	 * @var array[]
	 */
	private $requests = array();

	/**
	 * Refunds the mocked Paidy payment holds, oldest first.
	 *
	 * @var array[]
	 */
	private $paidy_refunds = array();

	/**
	 * Whether the mocked Paidy API lists the newest refund first.
	 *
	 * @var bool
	 */
	private $newest_refund_first = false;

	/**
	 * Whether the mocked Paidy API answers with a 502 error page.
	 *
	 * @var bool
	 */
	private $paidy_fails = false;

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

		$this->requests            = array();
		$this->paidy_refunds       = array();
		$this->newest_refund_first = false;
		$this->paidy_fails         = false;
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
	 * Short-circuit every HTTP request and answer refund requests like Paidy.
	 *
	 * A successful refund adds a refund to the payment, and Paidy answers with the
	 * closed payment and all of its refunds.
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
		$body             = isset( $parsed_args['body'] ) && is_string( $parsed_args['body'] ) ? json_decode( $parsed_args['body'], true ) : null;
		$this->requests[] = array(
			'url'    => $url,
			'amount' => is_array( $body ) && isset( $body['amount'] ) ? (float) $body['amount'] : null,
		);

		if ( $this->paidy_fails ) {
			return $this->http_response( 502, '<html><body><h1>502 Bad Gateway</h1></body></html>' );
		}
		$this->paidy_refunds[] = array(
			'id'     => 'ref_WD1P-j4AALQAI_w' . count( $this->paidy_refunds ),
			'amount' => is_array( $body ) && isset( $body['amount'] ) ? (float) $body['amount'] : 0,
		);
		$refunds               = $this->newest_refund_first ? array_reverse( $this->paidy_refunds ) : $this->paidy_refunds;

		return $this->http_response(
			200,
			wp_json_encode(
				array(
					'id'      => self::PAYMENT_ID,
					'status'  => 'closed',
					'refunds' => $refunds,
				)
			)
		);
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
	 * Create a captured Paidy order of 1,000.
	 *
	 * @param string $status Order status.
	 * @return WC_Order
	 */
	private function create_order( $status = 'completed' ) {
		$order = wc_create_order();
		$order->set_payment_method( 'paidy' );
		$order->set_total( 1000 );
		$order->set_transaction_id( self::PAYMENT_ID );
		// A capture ID keeps the completed status from sending a capture.
		$order->update_meta_data( 'paidy_capture_id', self::CAPTURE_ID );
		$order->set_status( $status );
		$order->save();

		return $order;
	}

	/**
	 * Mark a refund as already made at Paidy before the test.
	 *
	 * @param WC_Order $order Order.
	 */
	private function add_earlier_paidy_refund( $order ) {
		$this->paidy_refunds[] = array(
			'id'     => self::EARLIER_REFUND_ID,
			'amount' => 300,
		);
		$order->update_meta_data( 'paidy_refund_id', array( self::EARLIER_REFUND_ID ) );
		$order->save();
	}

	/**
	 * Refund from the order screen, as WooCommerce does when "Refund via Paidy" is clicked.
	 *
	 * @param WC_Order $order  Order.
	 * @param int      $amount Amount to refund.
	 * @return WC_Order_Refund|WP_Error
	 */
	private function refund_from_order_screen( $order, $amount ) {
		return wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => $amount,
				'refund_payment' => true,
			)
		);
	}

	/**
	 * Amounts sent to Paidy during the test.
	 *
	 * @return float[]
	 */
	private function sent_amounts() {
		return wp_list_pluck( $this->requests, 'amount' );
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
	 * A second refund is sent to Paidy, and both refund IDs are kept.
	 */
	public function test_second_refund_is_sent_to_paidy() {
		$order = $this->create_order();
		$this->add_earlier_paidy_refund( $order );

		$this->assertTrue( $this->gateway->process_refund( $order->get_id(), 100, 'Second partial refund' ) );

		$this->assertSame( array( 'https://api.paidy.com/payments/' . self::PAYMENT_ID . '/refunds' ), wp_list_pluck( $this->requests, 'url' ) );
		$this->assertSame( array( 100.0 ), $this->sent_amounts() );
		$this->assertSame(
			array( self::EARLIER_REFUND_ID, 'ref_WD1P-j4AALQAI_w1' ),
			wc_get_order( $order->get_id() )->get_meta( 'paidy_refund_id' )
		);
	}

	/**
	 * The new refund ID is kept whichever order Paidy lists the refunds in.
	 */
	public function test_new_refund_id_is_kept_when_paidy_lists_it_first() {
		$order = $this->create_order();
		$this->add_earlier_paidy_refund( $order );
		$this->newest_refund_first = true;

		$this->assertTrue( $this->gateway->process_refund( $order->get_id(), 100 ) );

		$this->assertSame(
			array( self::EARLIER_REFUND_ID, 'ref_WD1P-j4AALQAI_w1' ),
			wc_get_order( $order->get_id() )->get_meta( 'paidy_refund_id' )
		);
	}

	/**
	 * Two partial refunds from the order screen both reach Paidy and are both recorded.
	 */
	public function test_partial_refunds_from_order_screen_are_all_sent() {
		$order = $this->create_order();

		$this->assertInstanceOf( 'WC_Order_Refund', $this->refund_from_order_screen( $order, 300 ) );
		$this->assertInstanceOf( 'WC_Order_Refund', $this->refund_from_order_screen( $order, 200 ) );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( array( 300.0, 200.0 ), $this->sent_amounts() );
		$this->assertCount( 2, $order->get_refunds() );
		$this->assertSame( array( 'ref_WD1P-j4AALQAI_w0', 'ref_WD1P-j4AALQAI_w1' ), $order->get_meta( 'paidy_refund_id' ) );
	}

	/**
	 * Cancelling a completed order refunds it through WooCommerce and keeps it cancelled.
	 */
	public function test_cancel_refunds_the_order_through_woocommerce() {
		$order         = $this->create_order();
		$status_filter = has_filter( 'woocommerce_order_fully_refunded_status' );

		$order->update_status( 'cancelled' );

		$order   = wc_get_order( $order->get_id() );
		$refunds = $order->get_refunds();
		$this->assertSame( array( 1000.0 ), $this->sent_amounts() );
		$this->assertCount( 1, $refunds );
		$this->assertTrue( $refunds[0]->get_refunded_payment() );
		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertSame( array( 'ref_WD1P-j4AALQAI_w0' ), $order->get_meta( 'paidy_refund_id' ) );
		$this->assertSame( $status_filter, has_filter( 'woocommerce_order_fully_refunded_status' ), 'The status filter should be removed after the refund.' );
	}

	/**
	 * Cancelling a completed order after a partial refund refunds only the rest.
	 */
	public function test_cancel_after_partial_refund_refunds_the_rest() {
		$order = $this->create_order();
		$this->assertInstanceOf( 'WC_Order_Refund', $this->refund_from_order_screen( $order, 300 ) );

		wc_get_order( $order->get_id() )->update_status( 'cancelled' );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( array( 300.0, 700.0 ), $this->sent_amounts() );
		$this->assertCount( 2, $order->get_refunds() );
		$this->assertEquals( 1000, $order->get_total_refunded() );
		$this->assertSame( 'cancelled', $order->get_status() );
	}

	/**
	 * Cancelling an order that is already refunded in full sends nothing.
	 */
	public function test_cancel_of_fully_refunded_order_sends_nothing() {
		$order = $this->create_order();
		$this->assertInstanceOf( 'WC_Order_Refund', $this->refund_from_order_screen( $order, 1000 ) );
		// A full refund marks the order refunded; the shop owner then sets it back to completed.
		wc_get_order( $order->get_id() )->update_status( 'completed' );
		$this->requests = array();

		wc_get_order( $order->get_id() )->update_status( 'cancelled' );

		$this->assertSame( array(), $this->requests );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * Cancelling an order that an earlier version refunded on cancellation sends nothing.
	 *
	 * Earlier versions refunded the total at Paidy without a WooCommerce refund, so the
	 * order still looks unrefunded in WooCommerce; it reaches completed to cancelled again
	 * when the shop owner completes and cancels it once more.
	 */
	public function test_cancel_of_order_refunded_by_earlier_version_sends_nothing() {
		$order = $this->create_order();
		$order->update_meta_data( 'paidy_refund_id', array( self::EARLIER_REFUND_ID ) );
		$order->save();

		$order->update_status( 'cancelled' );

		$this->assertSame( array(), $this->requests );
		$this->assertSame( array(), wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * A manual refund in WooCommerce does not make an earlier version's Paidy refund look new.
	 */
	public function test_cancel_of_order_refunded_by_earlier_version_after_manual_refund_sends_nothing() {
		$order = $this->create_order();
		// Recorded in WooCommerce only ("Refund manually"), not sent to Paidy.
		$this->assertInstanceOf(
			'WC_Order_Refund',
			wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => 200,
				)
			)
		);
		$order = wc_get_order( $order->get_id() );
		$order->update_meta_data( 'paidy_refund_id', array( self::EARLIER_REFUND_ID ) );
		$order->save();

		$order->update_status( 'cancelled' );

		$this->assertSame( array(), $this->requests );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * A refund on cancellation that Paidy does not carry out leaves no WooCommerce refund and says so.
	 */
	public function test_failed_refund_on_cancel_keeps_no_refund_and_leaves_a_note() {
		$order             = $this->create_order();
		$this->paidy_fails = true;

		// PHPUnit turns a warning into an exception, which WooCommerce catches and reports
		// as a failed refund. A live site only logs the warning and carries on.
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Mimics production warning handling for this one call.
			static function () {
				return true;
			},
			E_WARNING
		);
		try {
			$order->update_status( 'cancelled' );
		} finally {
			restore_error_handler();
		}

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $this->requests );
		$this->assertSame( array(), $order->get_refunds() );
		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertStringContainsString( 'was not refunded at Paidy', $this->order_notes( $order ) );
	}
}
