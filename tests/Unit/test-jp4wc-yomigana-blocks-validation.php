<?php
/**
 * Tests for JP4WC_Yomigana_Blocks_Integration
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * Unit tests for JP4WC_Yomigana_Blocks_Integration.
 *
 * Covers:
 *  - validate_additional_field: required/optional, billing keys, unrelated keys
 *  - hide_additional_fields_from_order_meta: all four yomigana WC meta keys
 *  - save_to_order_meta: billing and shipping yomigana saved from REST request
 *  - filter_order_confirmation_address_block: strips dt/dd from rendered HTML
 */
class JP4WC_Yomigana_Blocks_Validation_Test extends WP_UnitTestCase {

	/**
	 * @var JP4WC_Yomigana_Blocks_Integration
	 */
	private $integration;

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Yomigana_Blocks_Integration' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/blocks/class-jp4wc-yomigana-blocks-integration.php';
		}

		if ( ! class_exists( 'JP4WC_Yomigana_Blocks_Integration' ) ) {
			$this->markTestSkipped( 'JP4WC_Yomigana_Blocks_Integration not available (WC Blocks interface missing)' );
		}

		$this->integration = new JP4WC_Yomigana_Blocks_Integration();
	}

	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'wc4jp-yomigana' );
		delete_option( 'wc4jp-yomigana-required' );
	}

	// ------------------------------------------------------------------
	// validate_additional_field
	// ------------------------------------------------------------------

	/**
	 * Yomigana required, billing last name empty → WP_Error.
	 */
	public function test_returns_error_when_required_billing_last_name_empty() {
		update_option( 'wc4jp-yomigana-required', '1' );

		$result = $this->integration->validate_additional_field(
			true,
			'_wc_billing/jp4wc/yomigana_last_name',
			''
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'invalid_yomigana', $result->get_error_code() );
	}

	/**
	 * Yomigana required, billing first name empty → WP_Error.
	 */
	public function test_returns_error_when_required_billing_first_name_empty() {
		update_option( 'wc4jp-yomigana-required', '1' );

		$result = $this->integration->validate_additional_field(
			true,
			'_wc_billing/jp4wc/yomigana_first_name',
			''
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'invalid_yomigana', $result->get_error_code() );
	}

	/**
	 * Yomigana required, value present → $is_valid returned unchanged.
	 */
	public function test_passes_when_required_yomigana_is_present() {
		update_option( 'wc4jp-yomigana-required', '1' );

		$result = $this->integration->validate_additional_field(
			true,
			'_wc_billing/jp4wc/yomigana_last_name',
			'タナカ'
		);

		$this->assertTrue( $result );
	}

	/**
	 * Yomigana not required, value empty → passes.
	 */
	public function test_passes_when_yomigana_not_required_and_empty() {
		update_option( 'wc4jp-yomigana-required', '0' );

		$result = $this->integration->validate_additional_field(
			true,
			'_wc_billing/jp4wc/yomigana_last_name',
			''
		);

		$this->assertTrue( $result );
		$this->assertNotInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Unrelated field key → $is_valid returned unchanged without any check.
	 */
	public function test_passes_unrelated_field_key() {
		update_option( 'wc4jp-yomigana-required', '1' );

		$result = $this->integration->validate_additional_field(
			true,
			'_wc_billing/some/other-field',
			''
		);

		$this->assertTrue( $result );
	}

	/**
	 * Shipping yomigana keys are NOT in the validation list → pass through.
	 * Shipping fields use _wc_shipping/ prefix, which is intentionally outside
	 * the billing-only validation scope.
	 */
	public function test_shipping_keys_are_not_validated() {
		update_option( 'wc4jp-yomigana-required', '1' );

		$result = $this->integration->validate_additional_field(
			true,
			'_wc_shipping/jp4wc/yomigana_last_name',
			''
		);

		$this->assertTrue( $result );
		$this->assertNotInstanceOf( 'WP_Error', $result );
	}

	// ------------------------------------------------------------------
	// hide_additional_fields_from_order_meta
	// ------------------------------------------------------------------

	/**
	 * All four yomigana WC meta keys are removed from formatted order meta.
	 */
	public function test_hides_all_four_yomigana_meta_keys() {
		$order = wc_create_order();

		$keys_to_hide = array(
			'_wc_billing/jp4wc/yomigana_last_name',
			'_wc_billing/jp4wc/yomigana_first_name',
			'_wc_shipping/jp4wc/yomigana_last_name',
			'_wc_shipping/jp4wc/yomigana_first_name',
		);

		$formatted = array();
		foreach ( $keys_to_hide as $i => $key ) {
			$meta        = new stdClass();
			$meta->key   = $key;
			$meta->value = 'テスト';
			$formatted[ $i ] = $meta;
		}

		// Add an unrelated meta entry.
		$other        = new stdClass();
		$other->key   = '_billing_first_name';
		$other->value = 'Shohei';
		$formatted[99] = $other;

		$result = $this->integration->hide_additional_fields_from_order_meta( $formatted, $order );

		foreach ( array( 0, 1, 2, 3 ) as $idx ) {
			$this->assertArrayNotHasKey( $idx, $result, "Key {$keys_to_hide[$idx]} should be hidden" );
		}
		$this->assertArrayHasKey( 99, $result, 'Unrelated meta should remain' );

		$order->delete( true );
	}

	/**
	 * No yomigana keys → array returned unchanged.
	 */
	public function test_leaves_non_yomigana_meta_unchanged() {
		$order = wc_create_order();

		$meta        = new stdClass();
		$meta->key   = '_billing_address_1';
		$meta->value = '1-1-1 Shibuya';
		$formatted   = array( 1 => $meta );

		$result = $this->integration->hide_additional_fields_from_order_meta( $formatted, $order );

		$this->assertCount( 1, $result );

		$order->delete( true );
	}

	// ------------------------------------------------------------------
	// save_to_order_meta
	// ------------------------------------------------------------------

	/**
	 * Billing yomigana fields from REST request are saved to order meta.
	 */
	public function test_saves_billing_yomigana_from_request() {
		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_param(
			'billing_address',
			array(
				'additional_fields' => array(
					'jp4wc/yomigana_last_name'  => 'タナカ',
					'jp4wc/yomigana_first_name' => 'ショウヘイ',
				),
			)
		);

		$this->integration->save_to_order_meta( $order, $request );

		$refreshed = wc_get_order( $order->get_id() );
		$this->assertSame( 'タナカ', $refreshed->get_meta( '_billing_yomigana_last_name', true ) );
		$this->assertSame( 'ショウヘイ', $refreshed->get_meta( '_billing_yomigana_first_name', true ) );

		$order->delete( true );
	}

	/**
	 * Shipping yomigana fields from REST request are saved to order meta.
	 */
	public function test_saves_shipping_yomigana_from_request() {
		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_param(
			'shipping_address',
			array(
				'additional_fields' => array(
					'jp4wc/yomigana_last_name'  => 'ヤマダ',
					'jp4wc/yomigana_first_name' => 'タロウ',
				),
			)
		);

		$this->integration->save_to_order_meta( $order, $request );

		$refreshed = wc_get_order( $order->get_id() );
		$this->assertSame( 'ヤマダ', $refreshed->get_meta( '_shipping_yomigana_last_name', true ) );
		$this->assertSame( 'タロウ', $refreshed->get_meta( '_shipping_yomigana_first_name', true ) );

		$order->delete( true );
	}

	/**
	 * Request with no billing/shipping params → no meta saved, no error.
	 */
	public function test_save_does_nothing_when_request_has_no_address_params() {
		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );

		$this->integration->save_to_order_meta( $order, $request );

		$refreshed = wc_get_order( $order->get_id() );
		$this->assertEmpty( $refreshed->get_meta( '_billing_yomigana_last_name', true ) );
		$this->assertEmpty( $refreshed->get_meta( '_shipping_yomigana_last_name', true ) );

		$order->delete( true );
	}

	/**
	 * Input is sanitized: HTML tags in yomigana value are stripped.
	 */
	public function test_sanitizes_yomigana_value_on_save() {
		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/store/v1/checkout' );
		$request->set_param(
			'billing_address',
			array(
				'additional_fields' => array(
					'jp4wc/yomigana_last_name' => '<script>alert(1)</script>タナカ',
				),
			)
		);

		$this->integration->save_to_order_meta( $order, $request );

		$refreshed = wc_get_order( $order->get_id() );
		$saved     = $refreshed->get_meta( '_billing_yomigana_last_name', true );

		$this->assertStringNotContainsString( '<script>', $saved );
		$this->assertStringContainsString( 'タナカ', $saved );

		$order->delete( true );
	}

	// ------------------------------------------------------------------
	// filter_order_confirmation_address_block
	// ------------------------------------------------------------------

	/**
	 * Yomigana dt/dd pairs are stripped from rendered block HTML.
	 */
	public function test_strips_yomigana_entries_from_block_html() {
		$label_last  = esc_html( __( 'Last Name ( Yomigana )', 'woocommerce-for-japan' ) );
		$label_first = esc_html( __( 'First Name ( Yomigana )', 'woocommerce-for-japan' ) );

		$html = '<dl class="wc-block-order-confirmation-totals__table">'
			. "<dt>{$label_last}</dt><dd>タナカ</dd>"
			. "<dt>{$label_first}</dt><dd>ショウヘイ</dd>"
			. '<dt>City</dt><dd>Tokyo</dd>'
			. '</dl>';

		$result = $this->integration->filter_order_confirmation_address_block( $html );

		$this->assertStringNotContainsString( $label_last, $result );
		$this->assertStringNotContainsString( $label_first, $result );
		$this->assertStringContainsString( 'Tokyo', $result );
	}

	/**
	 * Block HTML with no yomigana entries → returned unchanged.
	 */
	public function test_returns_block_html_unchanged_when_no_yomigana() {
		$html   = '<dl><dt>City</dt><dd>Osaka</dd></dl>';
		$result = $this->integration->filter_order_confirmation_address_block( $html );

		$this->assertSame( $html, $result );
	}

	/**
	 * Empty <dl> wrapper is removed after all yomigana entries are stripped.
	 */
	public function test_removes_empty_dl_wrapper_after_stripping() {
		$label = esc_html( __( 'Last Name ( Yomigana )', 'woocommerce-for-japan' ) );
		$html  = "<dl class=\"extra\"><dt>{$label}</dt><dd>タナカ</dd></dl>";

		$result = $this->integration->filter_order_confirmation_address_block( $html );

		$this->assertStringNotContainsString( '<dl', $result );
	}

	// ------------------------------------------------------------------
	// start_order_address_fields_buffer / filter_order_address_fields_buffer
	// ------------------------------------------------------------------

	/**
	 * Output buffer is started when jp4wc_is_order_display_page returns true
	 * (covers both order-received and My Account view-order pages).
	 */
	public function test_buffer_starts_on_order_display_page() {
		add_filter( 'jp4wc_is_order_display_page', '__return_true' );
		$level_before = ob_get_level();
		$level_after  = $level_before;

		try {
			$this->integration->start_order_address_fields_buffer( 'billing', null );
			$level_after = ob_get_level();

			$this->assertGreaterThan( $level_before, $level_after, 'ob_start() should be called on order display pages' );
		} finally {
			while ( ob_get_level() > $level_before ) {
				ob_end_clean();
			}
			remove_filter( 'jp4wc_is_order_display_page', '__return_true' );
		}
	}

	/**
	 * Output buffer is NOT started on pages other than order display pages.
	 */
	public function test_buffer_does_not_start_outside_order_display_page() {
		add_filter( 'jp4wc_is_order_display_page', '__return_false' );
		$level_before = ob_get_level();

		try {
			$this->integration->start_order_address_fields_buffer( 'billing', null );

			$this->assertSame( $level_before, ob_get_level(), 'ob_start() should not be called outside order display pages' );
		} finally {
			while ( ob_get_level() > $level_before ) {
				ob_end_clean();
			}
			remove_filter( 'jp4wc_is_order_display_page', '__return_false' );
		}
	}

	/**
	 * Yomigana dt/dd pairs are stripped from buffered output on order display pages.
	 *
	 * Simulates the priority-9/10/11 sequence:
	 *   9  → start_order_address_fields_buffer() opens a buffer
	 *   10 → WooCommerce renders additional fields (yomigana) into that buffer
	 *   11 → filter_order_address_fields_buffer() consumes the buffer and echoes filtered HTML
	 */
	public function test_filter_strips_yomigana_from_buffer_on_order_display_page() {
		add_filter( 'jp4wc_is_order_display_page', '__return_true' );
		$level_before = ob_get_level();
		$output       = '';

		try {
			$label_last  = esc_html( __( 'Last Name ( Yomigana )', 'woocommerce-for-japan' ) );
			$label_first = esc_html( __( 'First Name ( Yomigana )', 'woocommerce-for-japan' ) );

			// Outer buffer captures whatever filter_order_address_fields_buffer echoes.
			ob_start();

			// Priority 9: open inner buffer (via start_order_address_fields_buffer).
			$this->integration->start_order_address_fields_buffer( 'billing', null );

			// Priority 10: WooCommerce renders yomigana + other fields into the inner buffer.
			echo "<dl><dt>{$label_last}</dt><dd>タナカ</dd><dt>{$label_first}</dt><dd>ショウヘイ</dd><dt>City</dt><dd>Tokyo</dd></dl>";

			// Priority 11: consume inner buffer, strip yomigana, echo result to outer buffer.
			$this->integration->filter_order_address_fields_buffer( 'billing', null );

			$output = ob_get_clean();

			$this->assertStringNotContainsString( $label_last, $output, 'Last name yomigana dt/dd should be stripped' );
			$this->assertStringNotContainsString( $label_first, $output, 'First name yomigana dt/dd should be stripped' );
			$this->assertStringContainsString( 'Tokyo', $output, 'Non-yomigana entries should remain' );
		} finally {
			while ( ob_get_level() > $level_before ) {
				ob_end_clean();
			}
			remove_filter( 'jp4wc_is_order_display_page', '__return_true' );
		}
	}
}
