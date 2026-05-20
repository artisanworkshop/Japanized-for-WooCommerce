<?php
/**
 * Regression tests for yomigana in order email address formatting.
 *
 * Covers the 2.9.10 regression where woocommerce_localisation_address_formats
 * filter was accidentally removed, causing yomigana to disappear from order emails.
 *
 * @package Japanized_For_WooCommerce
 */

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed,Generic.Files.OneObjectStructurePerFile.MultipleFound
// Test files mix function stubs with multiple class declarations; these rules do not apply here.

// Stub WooCommerce functions that are absent in the minimal WP test environment.
if ( ! function_exists( 'wc_get_page_id' ) ) {
	/**
	 * Stub: returns 0 (no page configured), simulating classic/non-block checkout.
	 *
	 * @param string $page Page slug.
	 * @return int
	 */
	function wc_get_page_id( $page ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return 0;
	}
}

/**
 * JP4WC_Address_Email_Test
 *
 * Tests the three-layer chain required for yomigana to appear in order emails:
 *   1. woocommerce_localisation_address_formats  — injects {yomigana_*} placeholders into JP format
 *   2. woocommerce_order_formatted_billing_address — populates address array from order meta
 *   3. woocommerce_formatted_address_replacements  — maps {yomigana_*} → actual values
 */
class JP4WC_Address_Email_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Address_Fields' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-address-fields.php';
		}
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		delete_option( 'wc4jp-yomigana' );
		delete_option( 'wc4jp-yomigana-required' );
		delete_option( 'wc4jp-honorific-suffix' );
		delete_option( 'wc4jp-company-name' );
		parent::tearDown();
	}


	// ------------------------------------------------------------------
	// Layer 1: woocommerce_localisation_address_formats filter registration
	// ------------------------------------------------------------------

	/**
	 * Regression: woocommerce_localisation_address_formats filter must be registered.
	 *
	 * In 2.9.10 this filter registration was accidentally removed, causing yomigana
	 * to vanish from order emails because the JP format template never received
	 * the {yomigana_last_name} / {yomigana_first_name} placeholders.
	 */
	public function test_localisation_address_formats_filter_is_registered() {
		$this->assertNotFalse(
			has_filter( 'woocommerce_localisation_address_formats' ),
			'woocommerce_localisation_address_formats must have at least one callback registered. '
			. 'Regression from 2.9.10: filter removal caused yomigana to disappear from order emails.'
		);
	}

	/**
	 * JP format must contain yomigana placeholders when the option is enabled.
	 */
	public function test_jp_address_format_includes_yomigana_placeholders_when_enabled() {
		update_option( 'wc4jp-yomigana', '1' );

		$formats = apply_filters( 'woocommerce_localisation_address_formats', array( 'JP' => '' ) );

		$this->assertArrayHasKey( 'JP', $formats );
		$this->assertStringContainsString(
			'{yomigana_last_name}',
			$formats['JP'],
			'JP address format must contain {yomigana_last_name} when yomigana option is enabled.'
		);
		$this->assertStringContainsString(
			'{yomigana_first_name}',
			$formats['JP'],
			'JP address format must contain {yomigana_first_name} when yomigana option is enabled.'
		);
	}

	/**
	 * JP format must not contain yomigana placeholders when the option is disabled.
	 */
	public function test_jp_address_format_excludes_yomigana_when_disabled() {
		delete_option( 'wc4jp-yomigana' );

		$formats = apply_filters( 'woocommerce_localisation_address_formats', array( 'JP' => '' ) );

		$this->assertArrayHasKey( 'JP', $formats );
		$this->assertStringNotContainsString(
			'{yomigana_last_name}',
			$formats['JP'],
			'JP address format must not contain {yomigana_last_name} when yomigana option is disabled.'
		);
	}

	/**
	 * Honorific suffix (様) must NOT appear in the format string.
	 * It is applied via address_replacements() so PHP rendering (emails, admin) gets 様
	 * while WooCommerce Blocks JS — which renders the format client-side and cannot
	 * substitute custom text — never encounters a literal 様 placeholder.
	 */
	public function test_honorific_suffix_applied_via_replacements_not_format_string() {
		update_option( 'wc4jp-honorific-suffix', '1' );

		// Format string must be free of 様 so Blocks JS does not render it literally.
		$formats = apply_filters( 'woocommerce_localisation_address_formats', array( 'JP' => '' ) );
		$this->assertArrayHasKey( 'JP', $formats );
		$this->assertStringNotContainsString(
			'様',
			$formats['JP'],
			'JP address format must not contain 様 — Blocks JS renders this client-side and cannot substitute it.'
		);

		// address_replacements() must append 様 to {first_name} for JP (PHP rendering path).
		$args         = array(
			'first_name' => 'Taro',
			'last_name'  => 'Yamada',
			'country'    => 'JP',
		);
		$replacements = apply_filters( 'woocommerce_formatted_address_replacements', array(), $args );
		$this->assertArrayHasKey( '{first_name}', $replacements );
		$this->assertStringContainsString(
			'様',
			$replacements['{first_name}'],
			'address_replacements() must append 様 to {first_name} for JP country when honorific suffix is enabled.'
		);
	}

	/**
	 * JP format must keep a trailing newline after the name placeholders.
	 */
	public function test_jp_address_format_has_newline_after_name_placeholders() {
		delete_option( 'wc4jp-honorific-suffix' );
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'JP', 'US' ) );

		$formats = apply_filters( 'woocommerce_localisation_address_formats', array( 'JP' => '' ) );

		$this->assertArrayHasKey( 'JP', $formats );
		$this->assertMatchesRegularExpression(
			'/\{last_name\} \{first_name\}\n/',
			$formats['JP'],
			'JP address format must keep a trailing newline after the name placeholders.'
		);
	}

	// ------------------------------------------------------------------
	// Layer 3: woocommerce_formatted_address_replacements
	// ------------------------------------------------------------------

	/**
	 * address_replacements() must populate {yomigana_*} from the args array.
	 */
	public function test_address_replacements_sets_yomigana_values() {
		update_option( 'wc4jp-yomigana', '1' );

		$args = array(
			'first_name'          => 'Taro',
			'last_name'           => 'Yamada',
			'yomigana_last_name'  => 'ヤマダ',
			'yomigana_first_name' => 'タロウ',
		);

		$replacements = apply_filters( 'woocommerce_formatted_address_replacements', array(), $args );

		$this->assertArrayHasKey( '{yomigana_last_name}', $replacements );
		$this->assertSame( 'ヤマダ', $replacements['{yomigana_last_name}'] );
		$this->assertArrayHasKey( '{yomigana_first_name}', $replacements );
		$this->assertSame( 'タロウ', $replacements['{yomigana_first_name}'] );
	}

	/**
	 * Address_replacements() must not set {yomigana_*} when the option is disabled.
	 */
	public function test_address_replacements_skips_yomigana_when_disabled() {
		delete_option( 'wc4jp-yomigana' );

		$args         = array(
			'yomigana_last_name'  => 'ヤマダ',
			'yomigana_first_name' => 'タロウ',
		);
		$replacements = apply_filters( 'woocommerce_formatted_address_replacements', array(), $args );

		$this->assertArrayNotHasKey( '{yomigana_last_name}', $replacements );
		$this->assertArrayNotHasKey( '{yomigana_first_name}', $replacements );
	}

	/**
	 * address_replacements() must not add honorific suffix for non-JP addresses.
	 */
	public function test_address_replacements_does_not_add_honorific_suffix_for_non_jp() {
		update_option( 'wc4jp-honorific-suffix', '1' );

		$args = array(
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'country'    => 'US',
		);

		$replacements = apply_filters( 'woocommerce_formatted_address_replacements', array(), $args );

		$this->assertArrayHasKey( '{first_name}', $replacements );
		$this->assertSame( 'John', $replacements['{first_name}'] );
		$this->assertStringNotContainsString( '様', $replacements['{first_name}'] );
	}

	// ------------------------------------------------------------------
	// End-to-end: formatted billing address (simulates email rendering)
	// ------------------------------------------------------------------

	/**
	 * Classic checkout: yomigana stored with _billing_ prefix appears in formatted address.
	 */
	public function test_classic_checkout_yomigana_in_formatted_billing_address() {
		update_option( 'wc4jp-yomigana', '1' );

		$order = wc_create_order();
		$order->set_billing_first_name( 'Taro' );
		$order->set_billing_last_name( 'Yamada' );
		$order->set_billing_country( 'JP' );
		$order->set_billing_postcode( '150-0002' );
		$order->set_billing_state( 'JP13' );
		$order->set_billing_city( 'Shibuya' );
		$order->set_billing_address_1( '2-1 Dougenzaka' );
		$order->update_meta_data( '_billing_yomigana_last_name', 'ヤマダ' );
		$order->update_meta_data( '_billing_yomigana_first_name', 'タロウ' );
		$order->save();

		$formatted = $order->get_formatted_billing_address();

		$this->assertStringContainsString(
			'ヤマダ',
			$formatted,
			'Formatted billing address must contain yomigana last name (classic checkout).'
		);
		$this->assertStringContainsString(
			'タロウ',
			$formatted,
			'Formatted billing address must contain yomigana first name (classic checkout).'
		);

		$order->delete( true );
	}

	/**
	 * Block checkout: yomigana stored with _wc_billing/ prefix appears in formatted address.
	 */
	public function test_block_checkout_yomigana_in_formatted_billing_address() {
		update_option( 'wc4jp-yomigana', '1' );

		$order = wc_create_order();
		$order->set_billing_first_name( 'Hanako' );
		$order->set_billing_last_name( 'Suzuki' );
		$order->set_billing_country( 'JP' );
		$order->set_billing_postcode( '100-0001' );
		$order->set_billing_state( 'JP13' );
		$order->set_billing_city( 'Chiyoda' );
		$order->set_billing_address_1( '1-1 Chiyoda' );
		$order->update_meta_data( '_wc_billing/jp4wc/yomigana_last_name', 'スズキ' );
		$order->update_meta_data( '_wc_billing/jp4wc/yomigana_first_name', 'ハナコ' );
		$order->save();

		$formatted = $order->get_formatted_billing_address();

		$this->assertStringContainsString(
			'スズキ',
			$formatted,
			'Formatted billing address must contain yomigana last name (block checkout).'
		);
		$this->assertStringContainsString(
			'ハナコ',
			$formatted,
			'Formatted billing address must contain yomigana first name (block checkout).'
		);

		$order->delete( true );
	}

	/**
	 * Block checkout: yomigana stored with _wc_shipping/ prefix appears in formatted shipping address.
	 */
	public function test_block_checkout_yomigana_in_formatted_shipping_address() {
		update_option( 'wc4jp-yomigana', '1' );

		$order = wc_create_order();
		$order->set_shipping_first_name( 'Jiro' );
		$order->set_shipping_last_name( 'Tanaka' );
		$order->set_shipping_country( 'JP' );
		$order->set_shipping_postcode( '530-0001' );
		$order->set_shipping_state( 'JP27' );
		$order->set_shipping_city( 'Kita' );
		$order->set_shipping_address_1( '1-1 Umeda' );
		$order->update_meta_data( '_wc_shipping/jp4wc/yomigana_last_name', 'タナカ' );
		$order->update_meta_data( '_wc_shipping/jp4wc/yomigana_first_name', 'ジロウ' );
		$order->save();

		$formatted = $order->get_formatted_shipping_address();

		$this->assertStringContainsString(
			'タナカ',
			$formatted,
			'Formatted shipping address must contain yomigana last name (block checkout).'
		);
		$this->assertStringContainsString(
			'ジロウ',
			$formatted,
			'Formatted shipping address must contain yomigana first name (block checkout).'
		);

		$order->delete( true );
	}

	// ------------------------------------------------------------------
	// My Account address edit: remove_duplicate_yomigana_from_address_edit
	// ------------------------------------------------------------------

	/**
	 * When traditional yomigana fields are present, WC-added _wc_billing/jp4wc/* duplicates
	 * must be removed so only one yomigana row appears in My Account billing address edit.
	 */
	public function test_duplicate_wc_added_yomigana_removed_from_billing_address_edit() {
		update_option( 'wc4jp-yomigana', '1' );

		// Call the method directly to avoid WP_UnitTestCase's _restore_hooks() resetting
		// $wp_filter between tests — the hook state is unreliable across test methods.
		$af = new JP4WC_Address_Fields();

		$address = array(
			'billing_last_name'                     => array(
				'label' => 'Last Name',
				'value' => 'Yamada',
			),
			'billing_first_name'                    => array(
				'label' => 'First Name',
				'value' => 'Taro',
			),
			'billing_yomigana_last_name'            => array(
				'label' => 'Yomigana Last',
				'value' => 'ヤマダ',
			),
			'billing_yomigana_first_name'           => array(
				'label' => 'Yomigana First',
				'value' => 'タロウ',
			),
			'billing_email'                         => array(
				'label' => 'Email',
				'value' => 'test@example.com',
			),
			'_wc_billing/jp4wc/yomigana_last_name'  => array(
				'label' => 'Yomigana Last',
				'value' => 'ヤマダ',
			),
			'_wc_billing/jp4wc/yomigana_first_name' => array(
				'label' => 'Yomigana First',
				'value' => 'タロウ',
			),
		);

		$result = $af->remove_duplicate_yomigana_from_address_edit( $address, 'billing' );

		$this->assertArrayHasKey( 'billing_yomigana_last_name', $result, 'Traditional yomigana_last_name must remain.' );
		$this->assertArrayHasKey( 'billing_yomigana_first_name', $result, 'Traditional yomigana_first_name must remain.' );
		$this->assertArrayNotHasKey( '_wc_billing/jp4wc/yomigana_last_name', $result, 'WC-added _wc_billing/jp4wc/yomigana_last_name must be removed.' );
		$this->assertArrayNotHasKey( '_wc_billing/jp4wc/yomigana_first_name', $result, 'WC-added _wc_billing/jp4wc/yomigana_first_name must be removed.' );
	}

	/**
	 * When traditional yomigana fields are present, WC-added _wc_shipping/jp4wc/* duplicates
	 * must be removed from the shipping address edit form.
	 */
	public function test_duplicate_wc_added_yomigana_removed_from_shipping_address_edit() {
		update_option( 'wc4jp-yomigana', '1' );

		$af = new JP4WC_Address_Fields();

		$address = array(
			'shipping_last_name'                     => array(
				'label' => 'Last Name',
				'value' => 'Suzuki',
			),
			'shipping_yomigana_last_name'            => array(
				'label' => 'Yomigana Last',
				'value' => 'スズキ',
			),
			'shipping_yomigana_first_name'           => array(
				'label' => 'Yomigana First',
				'value' => 'ハナコ',
			),
			'_wc_shipping/jp4wc/yomigana_last_name'  => array(
				'label' => 'Yomigana Last',
				'value' => 'スズキ',
			),
			'_wc_shipping/jp4wc/yomigana_first_name' => array(
				'label' => 'Yomigana First',
				'value' => 'ハナコ',
			),
		);

		$result = $af->remove_duplicate_yomigana_from_address_edit( $address, 'shipping' );

		$this->assertArrayHasKey( 'shipping_yomigana_last_name', $result, 'Traditional shipping_yomigana_last_name must remain.' );
		$this->assertArrayNotHasKey( '_wc_shipping/jp4wc/yomigana_last_name', $result, 'WC-added _wc_shipping/jp4wc/yomigana_last_name must be removed.' );
		$this->assertArrayNotHasKey( '_wc_shipping/jp4wc/yomigana_first_name', $result, 'WC-added _wc_shipping/jp4wc/yomigana_first_name must be removed.' );
	}

	/**
	 * When traditional yomigana fields are absent (block checkout, non-FSE theme),
	 * the WC-added _wc_billing/jp4wc/* fields must be kept intact (single display).
	 */
	public function test_wc_added_yomigana_kept_when_traditional_fields_absent() {
		update_option( 'wc4jp-yomigana', '1' );

		$af = new JP4WC_Address_Fields();

		$address = array(
			'billing_last_name'                     => array(
				'label' => 'Last Name',
				'value' => 'Yamada',
			),
			'billing_email'                         => array(
				'label' => 'Email',
				'value' => 'test@example.com',
			),
			'_wc_billing/jp4wc/yomigana_last_name'  => array(
				'label' => 'Yomigana Last',
				'value' => 'ヤマダ',
			),
			'_wc_billing/jp4wc/yomigana_first_name' => array(
				'label' => 'Yomigana First',
				'value' => 'タロウ',
			),
		);

		$result = $af->remove_duplicate_yomigana_from_address_edit( $address, 'billing' );

		$this->assertArrayHasKey( '_wc_billing/jp4wc/yomigana_last_name', $result, 'WC-added fields must remain when traditional fields are absent (block checkout).' );
		$this->assertArrayHasKey( '_wc_billing/jp4wc/yomigana_first_name', $result, 'WC-added fields must remain when traditional fields are absent (block checkout).' );
	}

	/**
	 * When yomigana option is disabled, no fields are removed.
	 */
	public function test_address_edit_deduplication_skipped_when_yomigana_disabled() {
		delete_option( 'wc4jp-yomigana' );

		$af = new JP4WC_Address_Fields();

		$address = array(
			'billing_yomigana_last_name'           => array(
				'label' => 'Yomigana Last',
				'value' => 'ヤマダ',
			),
			'_wc_billing/jp4wc/yomigana_last_name' => array(
				'label' => 'Yomigana Last',
				'value' => 'ヤマダ',
			),
		);

		$result = $af->remove_duplicate_yomigana_from_address_edit( $address, 'billing' );

		$this->assertArrayHasKey( '_wc_billing/jp4wc/yomigana_last_name', $result, 'WC-added fields must remain when yomigana option is disabled.' );
	}

	// ------------------------------------------------------------------
	// My Account address view: suppress_wc_additional_fields_on_account_view
	// ------------------------------------------------------------------

	/**
	 * On classic checkout with yomigana enabled, a mock render_address_fields callback
	 * at priority 10 must be removed by suppress_wc_additional_fields_on_account_view.
	 *
	 * The test environment has no WooCommerce/checkout block on the checkout page
	 * (wc_get_page_id('checkout') returns 0 or a page without that block), so
	 * has_block() returns false — the classic-checkout suppression path is taken.
	 */
	public function test_suppress_removes_wc_render_address_fields_on_classic_checkout() {
		update_option( 'wc4jp-yomigana', '1' );

		// Simulate WC CheckoutFieldsFrontend having render_address_fields hooked at priority 10.
		$mock = new JP4WC_Test_CheckoutFields_Mock();
		add_action( 'woocommerce_my_account_after_my_address', array( $mock, 'render_address_fields' ), 10 );

		$af = new JP4WC_Address_Fields();
		$af->suppress_wc_additional_fields_on_account_view( 'billing' );

		// The mock callback must have been removed.
		global $wp_filter;
		$callbacks_at_10 = $wp_filter['woocommerce_my_account_after_my_address']->callbacks[10] ?? array();
		foreach ( $callbacks_at_10 as $callback_data ) {
			$fn = $callback_data['function'];
			if ( is_array( $fn ) && $fn[0] === $mock ) {
				$this->fail( 'render_address_fields callback should have been removed on classic checkout.' );
			}
		}
		$this->assertTrue( true, 'render_address_fields callback was correctly removed.' );
	}

	/**
	 * When yomigana option is disabled, suppress_wc_additional_fields_on_account_view
	 * must leave WC's render_address_fields callback intact.
	 */
	public function test_suppress_skips_removal_when_yomigana_disabled() {
		delete_option( 'wc4jp-yomigana' );

		$mock = new JP4WC_Test_CheckoutFields_Mock();
		add_action( 'woocommerce_my_account_after_my_address', array( $mock, 'render_address_fields' ), 10 );

		$af = new JP4WC_Address_Fields();
		$af->suppress_wc_additional_fields_on_account_view( 'billing' );

		// Callback must still be present.
		global $wp_filter;
		$found           = false;
		$callbacks_at_10 = $wp_filter['woocommerce_my_account_after_my_address']->callbacks[10] ?? array();
		foreach ( $callbacks_at_10 as $callback_data ) {
			$fn = $callback_data['function'];
			if ( is_array( $fn ) && $fn[0] === $mock ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'render_address_fields callback must remain when yomigana option is disabled.' );
	}
}

/**
 * Minimal stand-in for WC CheckoutFieldsFrontend — provides render_address_fields()
 * so method_exists() detection inside suppress_wc_additional_fields_on_account_view works.
 */
class JP4WC_Test_CheckoutFields_Mock {
	/**
	 * Stub render_address_fields — intentionally empty for detection testing only.
	 *
	 * @param string $address_type Address type (billing or shipping).
	 * @return void
	 */
	public function render_address_fields( $address_type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	}
}
