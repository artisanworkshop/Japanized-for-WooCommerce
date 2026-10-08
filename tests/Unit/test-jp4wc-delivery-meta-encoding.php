<?php
/**
 * Tests for the storage of the delivery date, time zone and ship date (#224).
 *
 * The classic checkout used to store the three values through
 * esc_attr( htmlspecialchars() ), while the Checkout block and the admin meta
 * box store them as entered. Since 2.9.17 every path stores the value as
 * entered and each output escapes for itself, and the values stored before are
 * decoded when read.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Delivery_Meta_Encoding_Test
 */
class JP4WC_Delivery_Meta_Encoding_Test extends WP_UnitTestCase {

	/**
	 * A time zone label with the characters the old storage encoded that
	 * survive sanitize_text_field(), which every label goes through.
	 */
	const LABEL = 'AM & PM\'s "x"';

	/**
	 * The same label as the classic checkout stored it up to 2.9.16.
	 */
	const LEGACY = 'AM &amp; PM&#039;s &quot;x&quot;';

	/**
	 * A label the Checkout block accepts: WooCommerce's Store API runs the
	 * submitted additional fields through wp_kses() before it matches them
	 * against the options, so a value with `&` becomes `&amp;` and is rejected
	 * by WooCommerce itself. `'` and `"` get through.
	 */
	const BLOCK_LABEL = 'PM\'s "late"';

	/**
	 * Options this test touches.
	 *
	 * @var string[]
	 */
	private $options = array(
		'wc4jp-date-format',
		'wc4jp-delivery-time-morning',
		'wc4jp-delivery-time-morning-label',
		'wc4jp-delivery-time-zone',
		'wc4jp-delivery-time-zone-required',
		'wc4jp-unspecified-time',
		'wc4jp_time_zone_details',
		'woocommerce_bacs_settings',
		'woocommerce_enable_guest_checkout',
	);

	/**
	 * Checkout block fields registered by a test, deregistered after it.
	 *
	 * @var string[]
	 */
	private $registered_fields = array();

	/**
	 * $_POST as it was before the test.
	 *
	 * @var array
	 */
	private $saved_post;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Delivery_Blocks_Integration' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/blocks/class-jp4wc-delivery-blocks-integration.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Delivery_Blocks_Integration' ) );
		$this->assertTrue( class_exists( 'JP4WC_Delivery' ) );

		$this->saved_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- kept only to restore it after the test.

		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		$_POST = $this->saved_post;

		foreach ( $this->registered_fields as $field_id ) {
			__internal_woocommerce_blocks_deregister_checkout_field( $field_id );
		}
		$this->registered_fields = array();

		remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		remove_filter( 'woocommerce_is_checkout', '__return_true' );
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
		if ( WC()->session ) {
			WC()->session->__unset( 'store_api_draft_order' );
		}

		global $wp_rest_server;
		$wp_rest_server = null;

		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
		WC()->payment_gateways()->init();

		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Create a saved order.
	 *
	 * @return WC_Order
	 */
	private function create_order() {
		$order = wc_create_order();
		$order->set_billing_email( 'test@example.com' );
		$order->save();
		return $order;
	}

	/**
	 * Create a saved order that already holds a time zone in order meta.
	 *
	 * @param string $stored Value stored for wc4jp-delivery-time-zone.
	 * @return WC_Order
	 */
	private function create_order_with_time_zone( $stored ) {
		$order = $this->create_order();
		$order->update_meta_data( 'wc4jp-delivery-time-zone', $stored );
		$order->save();
		return wc_get_order( $order->get_id() );
	}

	/**
	 * What display_date_and_time_zone() prints for an order.
	 *
	 * @param WC_Order $order      The order.
	 * @param bool     $plain_text Print as plain text.
	 * @return string
	 */
	private function render( $order, $plain_text ) {
		ob_start();
		( new JP4WC_Delivery() )->display_date_and_time_zone( $order, true, $plain_text );
		return ob_get_clean();
	}

	/**
	 * The time zone as printed inside the HTML output.
	 *
	 * @param string $html Output of display_date_and_time_zone().
	 * @return string
	 */
	private function html_time_zone( $html ) {
		$this->assertSame( 1, preg_match( '#<p class="jp4wc_time"><strong>[^<]*</strong> <br>([^<]*)</p>#', $html, $matches ), 'The time zone paragraph must be printed: ' . $html );
		return $matches[1];
	}

	/**
	 * Offer a label as the "Morning" time zone of the Checkout block and the
	 * classic checkout, with two regular time zones after it.
	 *
	 * @param string $label The label.
	 */
	private function offer_label_as_time_zone( $label ) {
		update_option( 'wc4jp-delivery-time-zone', '1' );
		update_option( 'wc4jp-unspecified-time', 'Not specified' );
		update_option(
			'wc4jp_time_zone_details',
			array(
				array(
					'start_time' => '14:00',
					'end_time'   => '16:00',
				),
				array(
					'start_time' => '16:00',
					'end_time'   => '18:00',
				),
			)
		);
		update_option( 'wc4jp-delivery-time-morning', '1' );
		update_option( 'wc4jp-delivery-time-morning-label', $label );
	}

	/**
	 * Register the Checkout block's delivery fields from the current options
	 * and put a product in the cart.
	 */
	private function prepare_block_checkout() {
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'yes' ) );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		WC()->payment_gateways()->init();

		// A dispatched request has no nonce and is not a "real" checkout page request.
		add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		( new JP4WC_Delivery_Blocks_Integration() )->register_checkout_fields();
		$this->registered_fields[] = 'jp4wc/delivery-time';

		$product = new WC_Product_Simple();
		$product->set_name( 'Product' );
		$product->set_regular_price( '1000' );
		$product->set_virtual( true );
		$product->save();

		WC()->cart->empty_cart();
		WC()->session->set( 'store_api_draft_order', null );
		WC()->cart->add_to_cart( $product->get_id() );

		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
	}

	/**
	 * Place a Checkout block order with a delivery time zone.
	 *
	 * @param string $time_zone Value sent for the delivery time zone field.
	 * @return WP_REST_Response
	 */
	private function place_block_order( $time_zone ) {
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'billing_address'   => array(
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
					),
					'payment_method'    => 'bacs',
					'additional_fields' => array( 'jp4wc/delivery-time' => $time_zone ),
				)
			)
		);

		return rest_get_server()->dispatch( $request );
	}

	// ------------------------------------------------------------------
	// Storage: classic checkout
	// ------------------------------------------------------------------

	/**
	 * The woocommerce_checkout_create_order path stores the time zone as entered.
	 */
	public function test_classic_checkout_stores_the_time_zone_as_entered() {
		$order = $this->create_order();

		( new JP4WC_Delivery() )->save_delivery_data_to_order( $order, array( 'wc4jp_delivery_time_zone' => self::LABEL ) );
		$order->save();

		$this->assertSame( self::LABEL, wc_get_order( $order->get_id() )->get_meta( 'wc4jp-delivery-time-zone', true ) );
	}

	/**
	 * The date is stored as formatted, without HTML entities.
	 */
	public function test_classic_checkout_stores_the_formatted_date_as_is() {
		update_option( 'wc4jp-date-format', 'Y/m/d' );
		$order = $this->create_order();

		( new JP4WC_Delivery() )->save_delivery_data_to_order( $order, array( 'wc4jp_delivery_date' => '2026-05-10' ) );
		$order->save();

		$this->assertSame( '2026/05/10', wc_get_order( $order->get_id() )->get_meta( 'wc4jp-delivery-date', true ) );
	}

	/**
	 * The woocommerce_checkout_update_order_meta path, which saves again from
	 * $_POST, stores the three values as entered too.
	 */
	public function test_classic_checkout_update_order_meta_stores_the_values_as_entered() {
		$order = $this->create_order();

		$_POST['woocommerce-process-checkout-nonce'] = wp_create_nonce( 'woocommerce-process_checkout' );
		$_POST['wc4jp_delivery_date']                = '2026-05-10';
		$_POST['wc4jp_delivery_time_zone']           = self::LABEL;
		$_POST['wc4jp-tracking-ship-date']           = '2026-05-08';

		( new JP4WC_Delivery() )->update_order_meta( $order->get_id() );

		$saved = wc_get_order( $order->get_id() );
		$this->assertSame( '2026-05-10', $saved->get_meta( 'wc4jp-delivery-date', true ) );
		$this->assertSame( self::LABEL, $saved->get_meta( 'wc4jp-delivery-time-zone', true ) );
		$this->assertSame( '2026-05-08', $saved->get_meta( 'wc4jp-tracking-ship-date', true ) );
	}

	// ------------------------------------------------------------------
	// Storage: the same label through both checkouts
	// ------------------------------------------------------------------

	/**
	 * The classic checkout and the Checkout block store the same value for
	 * the same selected time zone.
	 */
	public function test_both_checkouts_store_the_same_value_for_the_same_label() {
		$this->offer_label_as_time_zone( self::BLOCK_LABEL );

		// Classic first: the block checkout below marks the request as a block request.
		$classic = $this->create_order();
		( new JP4WC_Delivery() )->save_delivery_data_to_order( $classic, array( 'wc4jp_delivery_time_zone' => self::BLOCK_LABEL ) );
		$classic->save();
		$classic_value = wc_get_order( $classic->get_id() )->get_meta( 'wc4jp-delivery-time-zone', true );

		$this->prepare_block_checkout();
		$response = $this->place_block_order( self::BLOCK_LABEL );
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status(), 'The order must be accepted: ' . wp_json_encode( $data ) );
		$block_value = wc_get_order( $data['order_id'] )->get_meta( '_wc_other/jp4wc/delivery-time', true );

		$this->assertSame( self::BLOCK_LABEL, $block_value );
		$this->assertSame( $block_value, $classic_value );
	}

	// ------------------------------------------------------------------
	// Output
	// ------------------------------------------------------------------

	/**
	 * The plain-text email prints the label itself.
	 */
	public function test_plain_text_output_prints_the_label_itself() {
		$text = $this->render( $this->create_order_with_time_zone( self::LABEL ), true );

		$this->assertStringContainsString( ': ' . self::LABEL, $text );
		$this->assertStringNotContainsString( '&amp;', $text );
	}

	/**
	 * The HTML output escapes the label once.
	 */
	public function test_html_output_escapes_the_label_once() {
		$printed = $this->html_time_zone( $this->render( $this->create_order_with_time_zone( self::LABEL ), false ) );

		$this->assertStringNotContainsString( ' & ', $printed );
		$this->assertStringNotContainsString( '&amp;amp;', $printed );
		$this->assertSame( self::LABEL, html_entity_decode( $printed, ENT_QUOTES ) );
	}

	/**
	 * An order stored before 2.9.17 holds the encoded label; the plain-text
	 * email prints the label itself.
	 */
	public function test_plain_text_output_decodes_a_value_stored_before() {
		$text = $this->render( $this->create_order_with_time_zone( self::LEGACY ), true );

		$this->assertStringContainsString( ': ' . self::LABEL, $text );
		$this->assertStringNotContainsString( '&amp;', $text );
	}

	/**
	 * An order stored before 2.9.17 is not escaped twice in HTML.
	 */
	public function test_html_output_does_not_escape_a_value_stored_before_twice() {
		$printed = $this->html_time_zone( $this->render( $this->create_order_with_time_zone( self::LEGACY ), false ) );

		$this->assertStringNotContainsString( '&amp;amp;', $printed );
		$this->assertSame( self::LABEL, html_entity_decode( $printed, ENT_QUOTES ) );
	}

	/**
	 * The jp4wc_display_date_and_time_zone filter receives the label itself,
	 * whichever way the order stored it.
	 */
	public function test_display_filter_receives_the_label_itself() {
		$received = array();
		$capture  = function ( $html, $date_time ) use ( &$received ) {
			$received[] = $date_time['time'];
			return $html;
		};
		add_filter( 'jp4wc_display_date_and_time_zone', $capture, 10, 2 );

		$this->render( $this->create_order_with_time_zone( self::LABEL ), true );
		$this->render( $this->create_order_with_time_zone( self::LEGACY ), true );

		remove_filter( 'jp4wc_display_date_and_time_zone', $capture, 10 );

		$this->assertSame( array( self::LABEL, self::LABEL ), $received );
	}

	/**
	 * The admin meta box is given the label itself, whichever way the order
	 * stored it, so saving the box again stores it as entered.
	 */
	public function test_meta_box_fields_hold_the_label_itself() {
		$delivery = new JP4WC_Delivery();

		$fields = $delivery->shipping_fields( $this->create_order_with_time_zone( self::LABEL ) );
		$this->assertSame( self::LABEL, $fields['wc4jp-delivery-time-zone']['value'] );

		$fields = $delivery->shipping_fields( $this->create_order_with_time_zone( self::LEGACY ) );
		$this->assertSame( self::LABEL, $fields['wc4jp-delivery-time-zone']['value'] );
	}
}
