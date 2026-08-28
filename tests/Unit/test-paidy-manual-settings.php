<?php
/**
 * Tests for the Paidy "enter settings manually" (wizard=false) bypass.
 *
 * The settings page wraps the gateway fields in #paidy-payment-settings
 * (hidden by CSS) whenever no API keys are stored, and renders the onboarding
 * UI instead. Following wizard=false must let the merchant see the plain
 * fields even while an application is under review (issue #210 recovery path).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Manual_Settings_Test
 */
class WC_Paidy_Manual_Settings_Test extends WP_UnitTestCase {

	/**
	 * Wizard instance built without running the constructor (no hooks).
	 *
	 * @var WC_Paidy_Admin_Wizard
	 */
	private $wizard;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Paidy_Admin_Wizard' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-admin-wizard.php';
		}
		$this->assertTrue( class_exists( 'WC_Paidy_Admin_Wizard' ) );

		$this->wizard                 = ( new ReflectionClass( 'WC_Paidy_Admin_Wizard' ) )->newInstanceWithoutConstructor();
		$this->wizard->paidy_settings = array(
			'api_public_key'      => '',
			'test_api_public_key' => '',
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		delete_transient( WC_Paidy_Admin_Wizard::manual_settings_transient_key() );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Without the flag, empty keys produce the onboarding wrappers.
	 */
	public function test_wrappers_emitted_without_flag() {
		$gateway     = (object) array( 'id' => 'paidy' );
		$description = $this->wizard->paidy_method_description( 'desc', $gateway );

		$this->assertStringContainsString( 'id="paidy-admin-settings"', $description );
		$this->assertStringContainsString( 'id="paidy-payment-settings"', $description );
	}

	/**
	 * With the flag set (wizard=false was followed), the plain description is returned
	 * so the gateway fields are not wrapped in the hidden container.
	 */
	public function test_flag_bypasses_wrappers() {
		set_transient( WC_Paidy_Admin_Wizard::manual_settings_transient_key(), 1, MINUTE_IN_SECONDS );

		$this->assertTrue( $this->wizard->is_manual_settings_requested() );

		$gateway = (object) array( 'id' => 'paidy' );
		$this->assertSame( 'desc', $this->wizard->paidy_method_description( 'desc', $gateway ) );

		$_GET['section'] = 'paidy';
		ob_start();
		$this->wizard->paidy_after_settings_checkout();
		$closing = ob_get_clean();
		unset( $_GET['section'] );

		$this->assertSame( '', $closing );
	}

	/**
	 * The flag is scoped to the current user.
	 */
	public function test_flag_is_per_user() {
		set_transient( WC_Paidy_Admin_Wizard::manual_settings_transient_key(), 1, MINUTE_IN_SECONDS );
		$this->assertTrue( $this->wizard->is_manual_settings_requested() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertFalse( $this->wizard->is_manual_settings_requested() );
	}
}
