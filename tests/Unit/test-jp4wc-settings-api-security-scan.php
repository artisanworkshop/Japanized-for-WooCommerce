<?php
/**
 * Tests for the security check setting of the settings endpoint (issue #257).
 *
 * The check is on until it is turned off. The settings screen saves back
 * everything the endpoint reports, so a site without the option yet must be
 * reported as on, or the first save of any tab turns the check off (#218).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Settings_API_Security_Scan_Test
 */
class JP4WC_Settings_API_Security_Scan_Test extends WP_UnitTestCase {

	/**
	 * Settings endpoint controller.
	 *
	 * @var JP4WC_Settings_API
	 */
	private $api;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'JP4WC_Settings_API' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/admin/class-jp4wc-settings-api.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Settings_API' ) );
		$this->api = new JP4WC_Settings_API();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		update_option( 'wc4jp-security-scan', '1' );
		parent::tearDown();
	}

	/**
	 * What the settings screen receives.
	 *
	 * @return array<string, mixed>
	 */
	private function reported_settings() {
		return $this->api->get_settings( new WP_REST_Request( 'GET', '/jp4wc/v1/settings' ) )->get_data();
	}

	/**
	 * Posts settings as the settings screen does.
	 *
	 * @param array $settings Settings.
	 */
	private function save( array $settings ) {
		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/settings' );
		$request->set_body_params( $settings );
		$this->api->update_settings( $request );
	}

	/**
	 * A site without the option is reported as on, and saving the screen keeps it on.
	 */
	public function test_missing_setting_is_reported_on_and_stays_on() {
		delete_option( 'wc4jp-security-scan' );

		$settings = $this->reported_settings();
		$this->assertSame( '1', $settings['security-scan'] );

		$this->save( $settings );
		$this->assertSame( '1', get_option( 'wc4jp-security-scan' ) );
		$this->assertTrue( JP4WC_Security_Feed::is_enabled() );
	}

	/**
	 * A check turned off is reported off.
	 */
	public function test_turned_off_setting_is_reported_off() {
		update_option( 'wc4jp-security-scan', '' );

		$this->assertSame( '', $this->reported_settings()['security-scan'] );
		$this->assertFalse( JP4WC_Security_Feed::is_enabled() );
	}

	/**
	 * Posted values and what is saved.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function posted_value_provider() {
		return array(
			'on'         => array( '1', '1' ),
			'true'       => array( true, '1' ),
			'off'        => array( '', '' ),
			'false'      => array( false, '' ),
			'other text' => array( 'yes', '' ),
			'array'      => array( array( '1' ), '' ),
		);
	}

	/**
	 * Only on is on.
	 *
	 * @dataProvider posted_value_provider
	 *
	 * @param mixed  $posted Posted value.
	 * @param string $saved  Saved value.
	 */
	public function test_posted_values_are_saved_as_on_or_off( $posted, $saved ) {
		$this->save( array( 'security-scan' => $posted ) );

		$this->assertSame( $saved, get_option( 'wc4jp-security-scan' ) );
	}

	/**
	 * The login attempt limiter is off until turned on, and its limits are reported with their defaults (3.0.0).
	 */
	public function test_login_limit_is_reported_off_with_default_limits() {
		delete_option( 'wc4jp-login-limit' );
		delete_option( 'wc4jp-login-limit-ip-attempts' );
		$settings = $this->reported_settings();

		$this->assertSame( '', $settings['login-limit'] );
		$this->assertSame( '10', $settings['login-limit-ip-attempts'] );
		$this->assertSame( '5', $settings['login-limit-user-attempts'] );
		$this->assertSame( '15', $settings['login-limit-lockout-minutes'] );
	}

	/**
	 * Only on is on, for the login attempt limiter as well.
	 *
	 * @dataProvider posted_value_provider
	 *
	 * @param mixed  $posted Posted value.
	 * @param string $saved  Saved value.
	 */
	public function test_login_limit_is_saved_as_on_or_off( $posted, $saved ) {
		try {
			$this->save( array( 'login-limit' => $posted ) );
			$this->assertSame( $saved, get_option( 'wc4jp-login-limit' ) );
		} finally {
			delete_option( 'wc4jp-login-limit' );
		}
	}

	/**
	 * Posted limits.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function posted_limit_provider() {
		return array(
			'in range'     => array( '20', '20' ),
			'number'       => array( 20, '20' ),
			'below range'  => array( '0', '1' ),
			'above range'  => array( '1000', '100' ),
			'not a number' => array( 'many', '10' ),
			'empty'        => array( '', '10' ),
			'array'        => array( array( '20' ), '10' ),
			'true'         => array( true, '10' ),
		);
	}

	/**
	 * A limit is saved within its range; anything that is not a number as the default.
	 *
	 * @dataProvider posted_limit_provider
	 *
	 * @param mixed  $posted Posted value.
	 * @param string $saved  Saved value.
	 */
	public function test_login_limits_are_saved_within_their_ranges( $posted, $saved ) {
		try {
			$this->save( array( 'login-limit-ip-attempts' => $posted ) );
			$this->assertSame( $saved, get_option( 'wc4jp-login-limit-ip-attempts' ) );
			$this->assertSame( $saved, $this->reported_settings()['login-limit-ip-attempts'] );
		} finally {
			delete_option( 'wc4jp-login-limit-ip-attempts' );
		}
	}

	/**
	 * The hardening options are off until turned on, and saved as on or off only (3.0.0).
	 *
	 * @dataProvider posted_value_provider
	 *
	 * @param mixed  $posted Posted value.
	 * @param string $saved  Saved value.
	 */
	public function test_hardening_options_are_saved_as_on_or_off( $posted, $saved ) {
		try {
			$this->assertSame( '', $this->reported_settings()['hide-rest-users'] );
			$this->assertSame( '', $this->reported_settings()['disallow-file-edit'] );
			$this->save(
				array(
					'hide-rest-users'    => $posted,
					'disallow-file-edit' => $posted,
				)
			);
			$this->assertSame( $saved, get_option( 'wc4jp-hide-rest-users' ) );
			$this->assertSame( $saved, get_option( 'wc4jp-disallow-file-edit' ) );
		} finally {
			delete_option( 'wc4jp-hide-rest-users' );
			delete_option( 'wc4jp-disallow-file-edit' );
		}
	}

	/**
	 * The lockout time has its own range (up to a day).
	 */
	public function test_lockout_minutes_range() {
		try {
			$this->save( array( 'login-limit-lockout-minutes' => '5000' ) );
			$this->assertSame( '1440', get_option( 'wc4jp-login-limit-lockout-minutes' ) );
		} finally {
			delete_option( 'wc4jp-login-limit-lockout-minutes' );
		}
	}
}
