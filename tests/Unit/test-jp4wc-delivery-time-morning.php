<?php
/**
 * Tests for the "Morning" delivery time zone (#221).
 *
 * The configured delivery time zones are start/end time pairs, so a time zone
 * that is only a word, such as "Morning" (午前中), could not be offered. The
 * option adds it ahead of the configured time zones, at the classic checkout
 * and at the Checkout block.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Delivery_Time_Morning_Test
 */
class JP4WC_Delivery_Time_Morning_Test extends WP_UnitTestCase {

	/**
	 * Options this test touches.
	 *
	 * @var string[]
	 */
	private $options = array(
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
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Delivery_Blocks_Integration' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/blocks/class-jp4wc-delivery-blocks-integration.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Delivery_Blocks_Integration' ) );

		if ( ! class_exists( 'JP4WC_Settings_API' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/admin/class-jp4wc-settings-api.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Settings_API' ) );

		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
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

	/**
	 * Configure two time zones and turn the time zone field on.
	 */
	private function configure_time_zones() {
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
	}

	/**
	 * Turn the "Morning" option on.
	 *
	 * @param string $label Label saved for it.
	 */
	private function enable_morning( $label = '午前中' ) {
		update_option( 'wc4jp-delivery-time-morning', '1' );
		update_option( 'wc4jp-delivery-time-morning-label', $label );
	}

	/**
	 * Time zone options the Checkout block registers.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	private function block_options() {
		$method = new ReflectionMethod( 'JP4WC_Delivery_Blocks_Integration', 'get_time_zone_options' );
		return $method->invoke( new JP4WC_Delivery_Blocks_Integration() );
	}

	/**
	 * Option values and texts of the classic checkout's time zone select, in order.
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	private function classic_options() {
		ob_start();
		( new JP4WC_Delivery() )->delivery_time_display( array( 'unspecified-time' => get_option( 'wc4jp-unspecified-time' ) ) );
		$html = ob_get_clean();

		preg_match_all( '#<option value="([^"]*)">([^<]*)</option>#', $html, $matches, PREG_SET_ORDER );
		$options = array();
		foreach ( $matches as $match ) {
			$options[] = array( $match[1], $match[2] );
		}
		return $options;
	}

	// ------------------------------------------------------------------
	// jp4wc_get_delivery_time_morning_label()
	// ------------------------------------------------------------------

	public function test_morning_is_off_by_default() {
		update_option( 'wc4jp-delivery-time-morning-label', '午前中' );

		$this->assertSame( '', jp4wc_get_delivery_time_morning_label() );
	}

	public function test_morning_label_is_the_saved_label() {
		$this->enable_morning( '午前中（8〜12時）' );

		$this->assertSame( '午前中（8〜12時）', jp4wc_get_delivery_time_morning_label() );
	}

	public function test_morning_label_falls_back_to_the_default_when_empty() {
		$this->enable_morning( '' );
		$this->assertSame( 'Morning', jp4wc_get_delivery_time_morning_label() );

		delete_option( 'wc4jp-delivery-time-morning-label' );
		$this->assertSame( 'Morning', jp4wc_get_delivery_time_morning_label() );
	}

	/**
	 * '0' is the value of the classic checkout's "Not specified" option, so a
	 * "Morning" option with that value would be saved as "not specified".
	 */
	public function test_morning_label_never_collides_with_the_not_specified_value() {
		$this->enable_morning( '0' );

		$this->assertSame( 'Morning', jp4wc_get_delivery_time_morning_label() );
	}

	public function test_morning_label_is_sanitized() {
		$this->enable_morning( ' <b>午前中</b> ' );

		$this->assertSame( '午前中', jp4wc_get_delivery_time_morning_label() );
	}

	// ------------------------------------------------------------------
	// Checkout block options
	// ------------------------------------------------------------------

	public function test_block_options_start_with_morning() {
		$this->configure_time_zones();
		$this->enable_morning();

		$this->assertSame(
			array(
				array(
					'value' => '午前中',
					'label' => '午前中',
				),
				array(
					'value' => '14:00-16:00',
					'label' => '14:00-16:00',
				),
				array(
					'value' => '16:00-18:00',
					'label' => '16:00-18:00',
				),
			),
			$this->block_options()
		);
	}

	public function test_block_options_are_unchanged_when_morning_is_off() {
		$this->configure_time_zones();

		$this->assertSame( array( '14:00-16:00', '16:00-18:00' ), wp_list_pluck( $this->block_options(), 'value' ) );
	}

	public function test_block_options_offer_morning_without_configured_time_zones() {
		$this->enable_morning();

		$this->assertSame( array( '午前中' ), wp_list_pluck( $this->block_options(), 'value' ) );
	}

	// ------------------------------------------------------------------
	// Classic checkout select
	// ------------------------------------------------------------------

	public function test_classic_select_puts_morning_right_after_not_specified() {
		$this->configure_time_zones();
		$this->enable_morning();

		$this->assertSame(
			array(
				array( '0', 'Not specified' ),
				array( '午前中', '午前中' ),
				array( '14:00-16:00', '14:00-16:00' ),
				array( '16:00-18:00', '16:00-18:00' ),
			),
			$this->classic_options()
		);
	}

	public function test_classic_select_starts_with_morning_when_the_time_zone_is_required() {
		$this->configure_time_zones();
		update_option( 'wc4jp-delivery-time-zone-required', '1' );
		$this->enable_morning();

		$this->assertSame( array( '午前中', '14:00-16:00', '16:00-18:00' ), array_column( $this->classic_options(), 0 ) );
	}

	public function test_classic_select_is_unchanged_when_morning_is_off() {
		$this->configure_time_zones();

		$this->assertSame( array( '0', '14:00-16:00', '16:00-18:00' ), array_column( $this->classic_options(), 0 ) );
	}

	/**
	 * With no time zones saved the option does not exist; count() on it used
	 * to be a TypeError.
	 */
	public function test_classic_select_offers_morning_without_configured_time_zones() {
		update_option( 'wc4jp-delivery-time-zone', '1' );
		update_option( 'wc4jp-unspecified-time', 'Not specified' );
		$this->enable_morning();

		$this->assertSame( array( '0', '午前中' ), array_column( $this->classic_options(), 0 ) );
	}

	public function test_classic_checkout_saves_morning_to_the_order() {
		$order = wc_create_order();

		( new JP4WC_Delivery() )->save_delivery_data_to_order( $order, array( 'wc4jp_delivery_time_zone' => '午前中' ) );
		$order->save();

		$this->assertSame( '午前中', wc_get_order( $order->get_id() )->get_meta( 'wc4jp-delivery-time-zone', true ) );
		$order->delete( true );
	}

	// ------------------------------------------------------------------
	// Settings endpoint
	// ------------------------------------------------------------------

	public function test_settings_endpoint_saves_and_reports_the_morning_settings() {
		$api = new JP4WC_Settings_API();

		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/settings' );
		$request->set_body_params(
			array(
				'delivery-time-morning'       => '1',
				'delivery-time-morning-label' => '<script>alert(1)</script>午前中 ',
			)
		);
		$settings = $api->update_settings( $request )->get_data();

		$this->assertSame( '1', get_option( 'wc4jp-delivery-time-morning' ) );
		$this->assertSame( '午前中', get_option( 'wc4jp-delivery-time-morning-label' ) );
		$this->assertSame( '1', $settings['delivery-time-morning'] );
		$this->assertSame( '午前中', $settings['delivery-time-morning-label'] );
	}

	public function test_settings_endpoint_does_not_save_a_non_string_label() {
		$api = new JP4WC_Settings_API();

		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/settings' );
		$request->set_body_params( array( 'delivery-time-morning-label' => array( '午前中' ) ) );
		$api->update_settings( $request );

		$this->assertSame( '', get_option( 'wc4jp-delivery-time-morning-label' ) );
	}

	// ------------------------------------------------------------------
	// Checkout block, through the Store API
	// ------------------------------------------------------------------

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
	 * Place the order with a delivery time zone.
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

	public function test_block_checkout_accepts_morning_and_saves_it_to_the_order() {
		$this->configure_time_zones();
		$this->enable_morning();
		$this->prepare_block_checkout();

		$response = $this->place_block_order( '午前中' );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status(), 'The order must be accepted: ' . wp_json_encode( $data ) );
		$order = wc_get_order( $data['order_id'] );
		$this->assertSame( '午前中', $order->get_meta( '_wc_other/jp4wc/delivery-time', true ) );
	}

	/**
	 * WooCommerce only accepts a value among the field's options, so the
	 * accepted order above is down to the "Morning" option being offered.
	 */
	public function test_block_checkout_rejects_morning_when_it_is_off() {
		$this->configure_time_zones();
		$this->prepare_block_checkout();

		$response = $this->place_block_order( '午前中' );

		$this->assertSame( 400, $response->get_status() );
	}
}
