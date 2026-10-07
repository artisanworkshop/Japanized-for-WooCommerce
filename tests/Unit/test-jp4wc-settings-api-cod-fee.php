<?php
/**
 * Tests for the COD fee settings the settings endpoint reports.
 *
 * The COD fee reads each setting from the `wc4jp-` option when it exists and
 * from the COD gateway's own settings page otherwise. The settings screen
 * saves back everything the endpoint reports, so the endpoint must report the
 * values in force: reporting a store's gateway-page settings as empty let one
 * click on "Save" wipe its COD fee (#218).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Settings_API_COD_Fee_Test
 */
class JP4WC_Settings_API_COD_Fee_Test extends WP_UnitTestCase {

	/**
	 * Settings endpoint controller.
	 *
	 * @var JP4WC_Settings_API
	 */
	private $api;

	/**
	 * Options this test touches.
	 *
	 * @var string[]
	 */
	private $options = array(
		'woocommerce_cod_settings',
		'jp4wc_tax_class_for_cod',
		'wc4jp-extra_charge_name',
		'wc4jp-extra_charge_amount',
		'wc4jp-extra_charge_max_cart_value',
		'wc4jp-extra_charge_calc_taxes',
		'wc4jp-extra_charge_tax_class',
	);

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Settings_API' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/admin/class-jp4wc-settings-api.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Settings_API' ) );
		$this->assertTrue( class_exists( 'JP4WC_COD_Fee' ), 'The plugin must be loaded so the fee settings can be resolved.' );

		$this->api = new JP4WC_Settings_API();
		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->options as $option ) {
			delete_option( $option );
		}
		parent::tearDown();
	}

	/**
	 * A store configured on the COD gateway's settings page.
	 */
	private function configure_on_gateway_page() {
		update_option(
			'woocommerce_cod_settings',
			array(
				'enabled'                     => 'yes',
				'extra_charge_name'           => 'COD fee',
				'extra_charge_amount'         => '550',
				'extra_charge_max_cart_value' => '50000',
				'extra_charge_calc_taxes'     => 'tax-incl',
			)
		);
		update_option( 'jp4wc_tax_class_for_cod', 'reduced-rate' );
	}

	/**
	 * What the settings screen receives.
	 *
	 * @return array<string, mixed>
	 */
	private function reported_settings() {
		$response = $this->api->get_settings( new WP_REST_Request( 'GET', '/jp4wc/v1/settings' ) );
		return $response->get_data();
	}

	/**
	 * What the COD fee calculates with.
	 *
	 * @return array<string, mixed>
	 */
	private function settings_in_force() {
		$method = new ReflectionMethod( 'JP4WC_COD_Fee', 'get_cod_fee_settings' );
		return $method->invoke( null );
	}

	public function test_gateway_page_settings_are_reported_when_the_screen_has_none() {
		$this->configure_on_gateway_page();

		$settings = $this->reported_settings();

		$this->assertSame( 'COD fee', $settings['extra_charge_name'] );
		$this->assertSame( '550', $settings['extra_charge_amount'] );
		$this->assertSame( '50000', $settings['extra_charge_max_cart_value'] );
		$this->assertSame( 'tax-incl', $settings['extra_charge_calc_taxes'] );
		$this->assertSame( 'reduced-rate', $settings['extra_charge_tax_class'] );
	}

	/**
	 * The settings screen posts back everything it received. That must keep
	 * the fee as it was, where it used to wipe it.
	 */
	public function test_saving_the_screen_keeps_the_fee_configured_on_the_gateway_page() {
		$this->configure_on_gateway_page();
		$before = $this->settings_in_force();
		$this->assertSame( '550', $before['extra_charge_amount'] );

		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/settings' );
		$request->set_body_params( $this->reported_settings() );
		$this->api->update_settings( $request );

		$after = $this->settings_in_force();
		$this->assertSame( 'COD fee', $after['extra_charge_name'] );
		$this->assertSame( '550', $after['extra_charge_amount'] );
		$this->assertSame( '50000', $after['extra_charge_max_cart_value'] );
		$this->assertSame( 'tax-incl', $after['extra_charge_calc_taxes'] );
		$this->assertSame( '550', get_option( 'wc4jp-extra_charge_amount' ), 'The value in force is now saved on the screen as well.' );
	}

	/**
	 * A value saved on the screen wins over the gateway page even when it is
	 * empty — clearing the free-above amount on the screen is a choice.
	 */
	public function test_screen_values_win_over_the_gateway_page_even_when_empty() {
		$this->configure_on_gateway_page();
		update_option( 'wc4jp-extra_charge_amount', '330' );
		update_option( 'wc4jp-extra_charge_max_cart_value', '' );

		$settings = $this->reported_settings();

		$this->assertSame( '330', $settings['extra_charge_amount'] );
		$this->assertSame( '', $settings['extra_charge_max_cart_value'] );
		$this->assertSame( 'COD fee', $settings['extra_charge_name'], 'Keys the screen never saved still come from the gateway page.' );
	}

	public function test_nothing_is_reported_when_neither_place_has_a_value() {
		$settings = $this->reported_settings();

		foreach ( array( 'extra_charge_name', 'extra_charge_amount', 'extra_charge_max_cart_value', 'extra_charge_calc_taxes', 'extra_charge_tax_class' ) as $key ) {
			$this->assertSame( '', $settings[ $key ], $key );
		}
	}

	public function test_malformed_gateway_settings_are_ignored() {
		update_option( 'woocommerce_cod_settings', 'not-an-array' );
		$this->assertSame( '', $this->reported_settings()['extra_charge_amount'] );

		update_option( 'woocommerce_cod_settings', array( 'extra_charge_amount' => array( '550' ) ) );
		$this->assertSame( '', $this->reported_settings()['extra_charge_amount'] );
	}
}
