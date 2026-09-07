<?php
/**
 * Tests for the Paidy onboarding application ID handling.
 *
 * The intermediary returns an application ID (e.g. WC000000571) when the
 * wizard submission is accepted; it is stored in `paidy_application_id`,
 * exposed via /wp/v2/settings, and shown on the "under review" screen so a
 * merchant can quote it to support (issue #210).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Application_Id_Test
 */
class WC_Paidy_Application_Id_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Paidy_Admin_Wizard' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-admin-wizard.php';
		}
		$this->assertTrue( class_exists( 'WC_Paidy_Admin_Wizard' ) );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		delete_option( 'paidy_application_id' );
		parent::tearDown();
	}

	/**
	 * Well-formed IDs pass through unchanged.
	 */
	public function test_valid_ids_pass() {
		$this->assertSame( 'WC000000571', WC_Paidy_Admin_Wizard::sanitize_application_id( 'WC000000571' ) );
		$this->assertSame( 'abc-123_XYZ', WC_Paidy_Admin_Wizard::sanitize_application_id( 'abc-123_XYZ' ) );
	}

	/**
	 * Anything that is not a short alphanumeric/hyphen/underscore string is rejected.
	 */
	public function test_invalid_ids_rejected() {
		$invalid = array( '', ' ', 'WC 0001', '<b>WC1</b>', str_repeat( 'a', 33 ), 'WC/0001', null, 123, array( 'WC1' ) );
		foreach ( $invalid as $value ) {
			$this->assertSame( '', WC_Paidy_Admin_Wizard::sanitize_application_id( $value ) );
		}
	}

	/**
	 * Surrounding whitespace is trimmed; the remaining value must still match the allow-list.
	 */
	public function test_whitespace_trimmed() {
		$this->assertSame( 'WC000000571', WC_Paidy_Admin_Wizard::sanitize_application_id( " WC000000571\n" ) );
	}

	/**
	 * The intermediary response body is parsed and the ID persisted (private
	 * method exercised via reflection so the HTTP call is not needed).
	 */
	public function test_store_application_id_from_response_body() {
		$wizard = ( new ReflectionClass( 'WC_Paidy_Admin_Wizard' ) )->newInstanceWithoutConstructor();
		$method = new ReflectionMethod( 'WC_Paidy_Admin_Wizard', 'store_application_id' );

		$method->invoke( $wizard, wp_json_encode( array( 'id' => 571, 'application_id' => 'WC000000571' ) ) );
		$this->assertSame( 'WC000000571', get_option( 'paidy_application_id' ) );

		// Malformed or missing IDs leave the stored value untouched.
		$method->invoke( $wizard, wp_json_encode( array( 'application_id' => '<script>' ) ) );
		$method->invoke( $wizard, 'not json' );
		$method->invoke( $wizard, '' );
		$this->assertSame( 'WC000000571', get_option( 'paidy_application_id' ) );
	}

	/**
	 * The setting is registered for the REST settings endpoint with the sanitizer attached.
	 */
	public function test_setting_registered_with_sanitizer() {
		$wizard = ( new ReflectionClass( 'WC_Paidy_Admin_Wizard' ) )->newInstanceWithoutConstructor();
		$wizard->paidy_on_boarding_settings();

		$registered = get_registered_settings();
		$this->assertArrayHasKey( 'paidy_application_id', $registered );
		$this->assertTrue( $registered['paidy_application_id']['show_in_rest'] );

		update_option( 'paidy_application_id', '<b>WC1</b>' );
		$this->assertSame( '', get_option( 'paidy_application_id' ) );
		update_option( 'paidy_application_id', 'WC000000571' );
		$this->assertSame( 'WC000000571', get_option( 'paidy_application_id' ) );
	}
}
