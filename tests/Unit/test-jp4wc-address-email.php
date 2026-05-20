<?php
/**
 * Regression tests for yomigana in order email address formatting.
 *
 * Covers the 2.9.10 regression where woocommerce_localisation_address_formats
 * filter was accidentally removed, causing yomigana to disappear from order emails.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Address_Email_Test
 *
 * Tests the three-layer chain required for yomigana to appear in order emails:
 *   1. woocommerce_localisation_address_formats  — injects {yomigana_*} placeholders into JP format
 *   2. woocommerce_order_formatted_billing_address — populates address array from order meta
 *   3. woocommerce_formatted_address_replacements  — maps {yomigana_*} → actual values
 */
class JP4WC_Address_Email_Test extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Address_Fields' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-address-fields.php';
		}
	}

	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'wc4jp-yomigana' );
		delete_option( 'wc4jp-yomigana-required' );
		delete_option( 'wc4jp-honorific-suffix' );
		delete_option( 'wc4jp-company-name' );
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
	 * JP format must include honorific suffix when enabled.
	 */
	public function test_jp_address_format_includes_honorific_suffix_when_enabled() {
		update_option( 'wc4jp-honorific-suffix', '1' );

		$formats = apply_filters( 'woocommerce_localisation_address_formats', array( 'JP' => '' ) );

		$this->assertArrayHasKey( 'JP', $formats );
		$this->assertStringContainsString(
			'{last_name} {first_name}様',
			$formats['JP'],
			'JP address format must append 様 to the name line when honorific suffix is enabled.'
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
	 * address_replacements() must not set {yomigana_*} when the option is disabled.
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
}
