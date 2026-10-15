<?php
/**
 * Tests the notice shown when a buyer returns from Paidy Checkout.
 *
 * When Paidy declines a payment, Paidy Checkout sends the buyer back to
 * `/checkout/?status=rejected&order_id=…`. The "declined" notice was added on
 * woocommerce_before_checkout_form, which only the classic (shortcode) checkout
 * fires, so a Checkout block page showed nothing and the `?status=closed` debug
 * log was never written either. The callback was also registered in the gateway
 * constructor, and the class is created more than once in a request, so the
 * classic checkout showed the notice twice (issue #248).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Checkout_Return_Notice_Test
 */
class WC_Paidy_Checkout_Return_Notice_Test extends WP_UnitTestCase {

	/**
	 * Script handle that shows the notice in the Checkout block.
	 */
	const HANDLE = 'jp4wc-paidy-checkout-reject-notice';

	/**
	 * Notice shown when Paidy declines the payment.
	 */
	const REJECT_MESSAGE = 'This Paidy payment has been declined. Please select another payment method.';

	/**
	 * Logger that records the entries instead of writing them.
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * Whether the checkout page uses the Checkout block in the current test.
	 *
	 * @var bool
	 */
	private $is_block_checkout = false;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-gateway-paidy.php';
		}
		$this->assertTrue( class_exists( 'WC_Gateway_Paidy' ), 'WC_Gateway_Paidy should be loadable.' );

		if ( null === WC()->session ) {
			WC()->initialize_session();
		}
		wc_clear_notices();

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

		add_filter( 'woocommerce_is_checkout', '__return_true' );
		add_filter( 'jp4wc_paidy_is_block_checkout', array( $this, 'is_block_checkout' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['status'], $_GET['order_id'] );
		remove_filter( 'woocommerce_is_checkout', '__return_true' );
		remove_filter( 'jp4wc_paidy_is_block_checkout', array( $this, 'is_block_checkout' ) );
		wc_clear_notices();
		wp_dequeue_script( self::HANDLE );
		wp_deregister_script( self::HANDLE );
		$flag = new ReflectionProperty( 'WC_Gateway_Paidy', 'show_block_checkout_reject_notice' );
		$flag->setValue( null, false );
		delete_option( 'woocommerce_paidy_settings' );

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
	 * Whether the checkout page uses the Checkout block in the current test.
	 *
	 * @return bool
	 */
	public function is_block_checkout() {
		return $this->is_block_checkout;
	}

	/**
	 * Simulate the return from Paidy Checkout and run the template_redirect and wp_enqueue_scripts callbacks.
	 *
	 * @param string|null $status   Value of the status query parameter, or null to leave it out.
	 * @param string|null $order_id Value of the order_id query parameter, or null to leave it out.
	 */
	private function return_from_paidy( $status, $order_id ) {
		if ( null !== $status ) {
			$_GET['status'] = $status;
		}
		if ( null !== $order_id ) {
			$_GET['order_id'] = $order_id;
		}
		WC_Gateway_Paidy::handle_checkout_return();
		WC_Gateway_Paidy::enqueue_block_checkout_reject_notice();
	}

	/**
	 * The inline script printed after the notice script.
	 *
	 * @return string
	 */
	private function inline_script() {
		$after = wp_scripts()->get_data( self::HANDLE, 'after' );

		return is_array( $after ) ? implode( "\n", $after ) : '';
	}

	/**
	 * The classic checkout gets the notice in the session, once.
	 */
	public function test_rejected_on_classic_checkout_adds_one_session_notice() {
		$this->return_from_paidy( 'rejected', '42' );

		$this->assertSame( 1, wc_notice_count( 'error' ) );
		$notices = wc_get_notices( 'error' );
		$this->assertSame( self::REJECT_MESSAGE, $notices[0]['notice'] );
		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * The Checkout block gets the notice in its notice store, not in the session.
	 */
	public function test_rejected_on_block_checkout_dispatches_checkout_notice() {
		$this->is_block_checkout = true;

		$this->return_from_paidy( 'rejected', '42' );

		$this->assertSame( 0, wc_notice_count(), 'A session notice would stay until another page prints it.' );
		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertSame( array( 'wp-data', 'wp-notices' ), wp_scripts()->registered[ self::HANDLE ]->deps );
		$script = $this->inline_script();
		$this->assertStringContainsString( 'wp.data.dispatch( "core/notices" ).createErrorNotice(', $script );
		$this->assertStringContainsString( '"context":"wc\/checkout"', $script );
		$this->assertStringContainsString( wp_json_encode( self::REJECT_MESSAGE ), $script );
	}

	/**
	 * The Checkout block notice is added once, even if wp_enqueue_scripts runs again.
	 */
	public function test_block_checkout_notice_is_added_once() {
		$this->is_block_checkout = true;

		$this->return_from_paidy( 'rejected', '42' );
		WC_Gateway_Paidy::enqueue_block_checkout_reject_notice();

		$this->assertSame( 1, substr_count( $this->inline_script(), 'createErrorNotice(' ) );
	}

	/**
	 * A later request in the same PHP worker does not show the notice again.
	 *
	 * Long-lived workers keep static properties between requests, and a rejected
	 * return that never reached wp_enqueue_scripts (e.g. a redirect) left the flag set.
	 */
	public function test_block_checkout_notice_does_not_reach_the_next_request() {
		$this->is_block_checkout = true;
		$_GET['status']          = 'rejected';
		$_GET['order_id']        = '42';
		WC_Gateway_Paidy::handle_checkout_return();

		unset( $_GET['status'], $_GET['order_id'] );
		$this->return_from_paidy( null, null );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * The notice data is JSON-encoded so that a translation cannot break out of the script.
	 */
	public function test_block_checkout_notice_escapes_the_message() {
		$this->is_block_checkout = true;
		$translate               = static function ( $translation, $text ) {
			return self::REJECT_MESSAGE === $text ? '</script><b>"Paidy" & \'x\'</b>' : $translation;
		};
		add_filter( 'gettext', $translate, 10, 2 );

		try {
			$this->return_from_paidy( 'rejected', '42' );
		} finally {
			remove_filter( 'gettext', $translate, 10 );
		}

		$script = $this->inline_script();
		$this->assertStringNotContainsString( '</script>', $script );
		$this->assertStringNotContainsString( '<b>', $script );
		$this->assertStringContainsString( '\u003C\/script\u003E', $script );
	}

	/**
	 * A buyer who closes Paidy Checkout is logged in debug mode, without a notice.
	 */
	public function test_closed_writes_debug_log_in_debug_mode() {
		update_option( 'woocommerce_paidy_settings', array( 'debug' => 'yes' ) );

		$this->return_from_paidy( 'closed', '42abc' );

		$this->assertCount( 1, $this->logger->entries );
		$this->assertStringContainsString( 'Order ID:42', $this->logger->entries[0]['message'] );
		$this->assertStringNotContainsString( '42abc', $this->logger->entries[0]['message'] );
		$this->assertSame( 0, wc_notice_count() );
		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * Without debug mode, closing Paidy Checkout is not logged.
	 */
	public function test_closed_writes_nothing_without_debug_mode() {
		update_option( 'woocommerce_paidy_settings', array( 'debug' => 'no' ) );

		$this->return_from_paidy( 'closed', '42' );

		$this->assertCount( 0, $this->logger->entries );
		$this->assertSame( 0, wc_notice_count() );
	}

	/**
	 * Requests that are not a return from Paidy Checkout do nothing.
	 *
	 * The is_checkout() guard is not covered here: a Store API checkout test earlier
	 * in the suite defines WOOCOMMERCE_CHECKOUT, which keeps is_checkout() true for the
	 * rest of the process, and a test in a separate process fails on WooCommerce's
	 * notices during its bootstrap.
	 *
	 * @dataProvider provide_unrelated_requests
	 *
	 * @param string|null $status   Value of the status query parameter.
	 * @param string|null $order_id Value of the order_id query parameter.
	 * @param string|null $endpoint WooCommerce endpoint the request is for, or null for the checkout page.
	 */
	public function test_unrelated_requests_do_nothing( $status, $order_id, $endpoint ) {
		update_option( 'woocommerce_paidy_settings', array( 'debug' => 'yes' ) );
		if ( null !== $endpoint ) {
			$GLOBALS['wp']->query_vars[ $endpoint ] = '42';
		}

		try {
			foreach ( array( false, true ) as $is_block_checkout ) {
				$this->is_block_checkout = $is_block_checkout;
				$this->return_from_paidy( $status, $order_id );
			}
		} finally {
			if ( null !== $endpoint ) {
				unset( $GLOBALS['wp']->query_vars[ $endpoint ] );
			}
		}

		$this->assertSame( 0, wc_notice_count() );
		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertCount( 0, $this->logger->entries );
	}

	/**
	 * Requests that are not a return from Paidy Checkout.
	 *
	 * @return array[]
	 */
	public function provide_unrelated_requests() {
		return array(
			'rejected without order_id' => array( 'rejected', null, null ),
			'closed without order_id'   => array( 'closed', null, null ),
			'order_id without status'   => array( null, '42', null ),
			'other status'              => array( 'authorized', '42', null ),
			'rejected on the order-pay' => array( 'rejected', '42', 'order-pay' ),
			'closed on the order-pay'   => array( 'closed', '42', 'order-pay' ),
		);
	}

	/**
	 * A checkout page that holds the Checkout block gets the notice in the block's notice store.
	 *
	 * Unlike the other tests, this one lets the gateway look at the checkout page itself.
	 */
	public function test_checkout_page_with_checkout_block_is_detected() {
		remove_filter( 'jp4wc_paidy_is_block_checkout', array( $this, 'is_block_checkout' ) );
		$this->use_checkout_page( '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->' );

		$this->return_from_paidy( 'rejected', '42' );

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertSame( 0, wc_notice_count() );
	}

	/**
	 * A checkout page that holds the checkout shortcode gets the session notice.
	 *
	 * @dataProvider provide_shortcode_checkout_pages
	 *
	 * @param string $content Content of the checkout page.
	 */
	public function test_checkout_page_with_shortcode_is_detected( $content ) {
		remove_filter( 'jp4wc_paidy_is_block_checkout', array( $this, 'is_block_checkout' ) );
		$this->use_checkout_page( $content );

		$this->return_from_paidy( 'rejected', '42' );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertSame( 1, wc_notice_count( 'error' ) );
	}

	/**
	 * Checkout page contents that use the checkout shortcode.
	 *
	 * @return array[]
	 */
	public function provide_shortcode_checkout_pages() {
		return array(
			'shortcode'               => array( '[woocommerce_checkout]' ),
			'classic shortcode block' => array( '<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"} /-->' ),
		);
	}

	/**
	 * Make a new page with the given content the WooCommerce checkout page.
	 *
	 * @param string $content Page content.
	 */
	private function use_checkout_page( $content ) {
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);
		update_option( 'woocommerce_checkout_page_id', $page_id );
	}

	/**
	 * The callbacks are registered once, by the loader, and never by a gateway instance.
	 */
	public function test_callbacks_are_registered_once_and_not_by_instances() {
		$gateway = new WC_Gateway_Paidy();
		new WC_Gateway_Paidy();

		$this->assertNotFalse( has_action( 'template_redirect', array( 'WC_Gateway_Paidy', 'handle_checkout_return' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( 'WC_Gateway_Paidy', 'enqueue_block_checkout_reject_notice' ) ) );
		$this->assertFalse( method_exists( $gateway, 'checkout_reject_to_cancel' ) );

		$registered = 0;
		foreach ( $GLOBALS['wp_filter']['template_redirect']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( array( 'WC_Gateway_Paidy', 'handle_checkout_return' ) === $callback['function'] ) {
					++$registered;
				}
			}
		}
		$this->assertSame( 1, $registered );
	}
}
