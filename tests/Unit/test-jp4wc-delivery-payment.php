<?php
/**
 * Tests for jp4wc_delivery_check_data (woocommerce_payment_successful_result filter)
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * Regression and unit tests for JP4WC_Delivery::jp4wc_delivery_check_data().
 *
 * Covers the bug where $result['order_id'] was undefined for Square/Amazon Pay,
 * causing a PHP Fatal Error after a successful payment and preventing the browser
 * from redirecting to the Thank You page.
 */
class JP4WC_Delivery_Payment_Test extends WP_UnitTestCase {

	/**
	 * @var JP4WC_Delivery
	 */
	private $delivery;

	public function setUp(): void {
		parent::setUp();
		$this->delivery = new JP4WC_Delivery();
	}

	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'wc4jp-delivery-date' );
		delete_option( 'wc4jp-delivery-date-required' );
		delete_option( 'wc4jp-delivery-time-zone' );
		delete_option( 'wc4jp-delivery-time-zone-required' );
	}

	/**
	 * Create a saved WC_Order for use in tests.
	 */
	private function create_order(): WC_Order {
		$order = wc_create_order();
		$order->set_billing_email( 'test@example.com' );
		$order->save();
		return $order;
	}

	/**
	 * Build a minimal payment result array (as returned by process_payment).
	 * Intentionally does NOT include 'order_id' — this matches Square/Amazon Pay.
	 */
	private function make_result( string $status = 'success' ): array {
		return array(
			'result'   => $status,
			'redirect' => 'https://example.com/order-received/',
		);
	}

	/**
	 * Confirm the filter is registered with 2 accepted arguments.
	 * Regression: was registered with 1, so $order_id was never received.
	 */
	public function test_filter_registered_with_two_args() {
		global $wp_filter;
		$hooks = $wp_filter['woocommerce_payment_successful_result'] ?? null;
		$this->assertNotNull( $hooks, 'Filter woocommerce_payment_successful_result should be registered' );

		$found = false;
		foreach ( $hooks->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $cb ) {
				if (
					is_array( $cb['function'] ) &&
					$cb['function'][0] instanceof JP4WC_Delivery &&
					$cb['function'][1] === 'jp4wc_delivery_check_data'
				) {
					$this->assertSame( 2, $cb['accepted_args'], 'Filter must accept 2 arguments to receive $order_id' );
					$found = true;
				}
			}
		}
		$this->assertTrue( $found, 'jp4wc_delivery_check_data callback should be registered' );
	}

	/**
	 * Delivery options disabled → result is returned unchanged.
	 */
	public function test_returns_result_unchanged_when_delivery_disabled() {
		$order  = $this->create_order();
		$result = $this->make_result();

		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'success', $returned['result'] );

		$order->delete( true );
	}

	/**
	 * Regression: Square/Amazon Pay style result (no 'order_id' key) must not cause a
	 * Fatal Error. The $order_id arrives as the second filter argument, not in $result.
	 */
	public function test_square_style_result_without_order_id_key_causes_no_error() {
		$order = $this->create_order();

		// Intentionally no 'order_id' in the result array — matches real gateway output.
		$result   = array( 'result' => 'success', 'redirect' => 'https://example.com/' );
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'success', $returned['result'] );

		$order->delete( true );
	}

	/**
	 * Non-existent order_id → early return without Fatal Error.
	 */
	public function test_returns_result_when_order_not_found() {
		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, 999999999 );

		$this->assertSame( 'success', $returned['result'] );
	}

	/**
	 * order_id = 0 (another edge case from undefined key) → early return without Fatal Error.
	 */
	public function test_returns_result_when_order_id_is_zero() {
		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, 0 );

		$this->assertSame( 'success', $returned['result'] );
	}

	/**
	 * Delivery date required, date present → passes through as success.
	 */
	public function test_passes_when_required_date_is_present() {
		$order = $this->create_order();
		$order->update_meta_data( 'wc4jp-delivery-date', '2026-05-10' );
		$order->save();

		update_option( 'wc4jp-delivery-date', '1' );
		update_option( 'wc4jp-delivery-date-required', '1' );

		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'success', $returned['result'] );

		$order->delete( true );
	}

	/**
	 * Delivery date required, date missing → result becomes 'failure' and order is marked failed.
	 */
	public function test_fails_when_required_date_missing() {
		$order = $this->create_order();

		update_option( 'wc4jp-delivery-date', '1' );
		update_option( 'wc4jp-delivery-date-required', '1' );

		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'failure', $returned['result'] );
		$this->assertArrayHasKey( 'messages', $returned );

		$refreshed = wc_get_order( $order->get_id() );
		$this->assertSame( 'failed', $refreshed->get_status() );

		$order->delete( true );
	}

	/**
	 * Delivery time required, time missing → result becomes 'failure'.
	 */
	public function test_fails_when_required_time_missing() {
		$order = $this->create_order();

		update_option( 'wc4jp-delivery-time-zone', '1' );
		update_option( 'wc4jp-delivery-time-zone-required', '1' );

		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'failure', $returned['result'] );

		$order->delete( true );
	}

	/**
	 * Delivery date option disabled (empty) even if required flag is set → passes.
	 * This matches the reported site where Option: '' but Dates: 8.
	 */
	public function test_passes_when_delivery_date_option_is_disabled() {
		$order = $this->create_order();

		update_option( 'wc4jp-delivery-date', '' );
		update_option( 'wc4jp-delivery-date-required', '1' );

		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'success', $returned['result'] );

		$order->delete( true );
	}

	/**
	 * Both date and time required, both missing → single 'failure' with order marked failed once.
	 */
	public function test_fails_once_when_both_date_and_time_required_and_missing() {
		$order = $this->create_order();

		update_option( 'wc4jp-delivery-date', '1' );
		update_option( 'wc4jp-delivery-date-required', '1' );
		update_option( 'wc4jp-delivery-time-zone', '1' );
		update_option( 'wc4jp-delivery-time-zone-required', '1' );

		$result   = $this->make_result();
		$returned = $this->delivery->jp4wc_delivery_check_data( $result, $order->get_id() );

		$this->assertSame( 'failure', $returned['result'] );

		$refreshed = wc_get_order( $order->get_id() );
		$this->assertSame( 'failed', $refreshed->get_status() );

		$order->delete( true );
	}

	/**
	 * The notice about a missing required delivery date goes only to the shop's notification address.
	 */
	public function test_missing_date_notice_goes_only_to_the_shop() {
		$mails   = array();
		$capture = static function ( $short_circuit, $atts ) use ( &$mails ) {
			$mails[] = $atts;
			return true;
		};
		add_filter( 'pre_wp_mail', $capture, 10, 2 );

		$order = $this->create_order();

		update_option( 'wc4jp-delivery-date', '1' );
		update_option( 'wc4jp-delivery-date-required', '1' );
		update_option( 'wc4jp-delivery-notification-email', 'shop@example.com' );

		try {
			$this->delivery->jp4wc_delivery_check_data( $this->make_result(), $order->get_id() );
		} finally {
			remove_filter( 'pre_wp_mail', $capture, 10 );
			delete_option( 'wc4jp-delivery-notification-email' );
		}

		// Marking the order failed also sends WooCommerce's own e-mails; keep the notice only.
		$notices = array_values(
			array_filter(
				$mails,
				static function ( $mail ) {
					return 'shop@example.com' === $mail['to'];
				}
			)
		);
		$this->assertCount( 1, $notices );
		$headers = implode( "\n", (array) $notices[0]['headers'] );
		$this->assertStringNotContainsStringIgnoringCase( 'bcc:', $headers );
		$this->assertStringNotContainsString( 'artws.info', $headers );

		$order->delete( true );
	}
}
