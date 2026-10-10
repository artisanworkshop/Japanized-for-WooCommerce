<?php
/**
 * Tests for the site hash sent with a Paidy onboarding application.
 *
 * The paidy_site_hash option outlives the onboarding settings: a canceled review
 * deletes only woocommerce_paidy_on_boarding_settings, and uninstall.php keeps the
 * hash too. Applying again then adds the settings option while a hash is
 * already stored, and add_paidy_on_boarding_settings() only assigned
 * $site_hash when it had to create one, so the application went out with an
 * undefined (null) site_hash. The intermediary could then neither encrypt the
 * API keys nor sign the callback for this site (issue #234).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Onboarding_Site_Hash_Test
 */
class WC_Paidy_Onboarding_Site_Hash_Test extends WP_UnitTestCase {

	/**
	 * Onboarding settings option saved by the wizard.
	 */
	const ONBOARDING_OPTION = 'woocommerce_paidy_on_boarding_settings';

	/**
	 * Intermediary endpoint that receives the application.
	 */
	const APPLICATIONS_URL = 'https://paidy.artws.info/api/applications/';

	/**
	 * A site hash in the format generate_random_string( 16 ) produces.
	 */
	const STORED_HASH = 'aB3!cD4@eF5#gH6$';

	/**
	 * Requests sent through the WP HTTP API during a test.
	 *
	 * @var array[]
	 */
	private $requests = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Paidy_Admin_Wizard' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-admin-wizard.php';
		}
		if ( ! class_exists( 'WC_Paidy_Apply_Receiver' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-apply-receiver.php';
		}
		$this->assertTrue( class_exists( 'WC_Paidy_Admin_Wizard' ) );
		$this->assertTrue( class_exists( 'WC_Paidy_Apply_Receiver' ) );

		$this->requests = array();
		add_filter( 'pre_http_request', array( $this, 'mock_intermediary' ), 10, 3 );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_intermediary' ), 10 );
		delete_option( self::ONBOARDING_OPTION );
		delete_option( 'paidy_site_hash' );
		delete_option( 'paidy_application_id' );
		delete_option( 'woocommerce_paidy_settings' );
		parent::tearDown();
	}

	/**
	 * Short-circuit every HTTP request, recording it and answering as the intermediary does.
	 *
	 * @param false|array|WP_Error $preempt     Whether to preempt the request.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 * @return array Mocked HTTP response.
	 */
	public function mock_intermediary( $preempt, $parsed_args, $url ) {
		$this->requests[] = array(
			'url'  => $url,
			'body' => $parsed_args['body'],
		);

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'application_id' => 'WC000000001' ) ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Start the wizard the way a site with Paidy settings does.
	 *
	 * The constructor hooks the onboarding handlers only when both API key
	 * settings exist, and the setting itself is registered on `init`.
	 */
	private function boot_wizard() {
		update_option(
			'woocommerce_paidy_settings',
			array(
				'api_public_key'      => '',
				'test_api_public_key' => '',
			)
		);
		$wizard = new WC_Paidy_Admin_Wizard();
		$wizard->paidy_on_boarding_settings();
	}

	/**
	 * Onboarding settings as the wizard saves them.
	 *
	 * @param int $step Wizard step.
	 * @return array
	 */
	private function application( $step ) {
		return array(
			'currentStep'                     => $step,
			'storeName'                       => 'Test Store',
			'siteName'                        => 'Test Site',
			'storeUrl'                        => 'https://example.com',
			'registEmail'                     => 'owner@example.com',
			'contactPhone'                    => '0312345678',
			'representativeLastName'          => 'Yamada',
			'representativeFirstName'         => 'Taro',
			'representativeLastNameKana'      => 'ヤマダ',
			'representativeFirstNameKana'     => 'タロウ',
			'representativeDateOfBirth'       => '1980-01-01',
			'annualGrossValue'                => 'less-than-10-million-yen',
			'averagePurchaseAmount'           => 'less-than-50000-yen',
			'securitySurvey01RadioControl'    => 'yes',
			'securitySurvey01TextControl'     => '',
			'securitySurvey11CheckControl'    => true,
			'securitySurvey12CheckControl'    => true,
			'securitySurvey13CheckControl'    => true,
			'securitySurvey14CheckControl'    => true,
			'securitySurvey10TextAreaControl' => '',
			'securitySurvey08RadioControl'    => 'yes',
			'securitySurvey09RadioControl'    => 'yes',
		);
	}

	/**
	 * Save the onboarding settings, collecting warnings instead of failing on them.
	 *
	 * PHPUnit turns a warning into an exception, which would stop the save
	 * before the application is sent. A live site only logs the warning and
	 * sends the application, so let warnings through and report them, and
	 * check what was sent.
	 *
	 * @param array $settings Onboarding settings to save.
	 * @return string[] Warning messages raised during the save.
	 */
	private function save_onboarding_settings( $settings ) {
		$warnings = array();
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Mimics production warning handling for this one call.
			static function ( $errno, $errstr ) use ( &$warnings ) {
				$warnings[] = $errstr;
				return true;
			},
			E_WARNING
		);
		try {
			update_option( self::ONBOARDING_OPTION, $settings );
		} finally {
			restore_error_handler();
		}

		return $warnings;
	}

	/**
	 * Applying again after a canceled review sends the hash the site kept.
	 */
	public function test_reapplication_sends_the_stored_site_hash() {
		update_option( 'paidy_site_hash', self::STORED_HASH );
		$this->boot_wizard();

		$warnings = $this->save_onboarding_settings( $this->application( 2 ) );

		$this->assertCount( 1, $this->requests, 'The application should have been sent once.' );
		$this->assertSame( self::APPLICATIONS_URL, $this->requests[0]['url'] );
		$this->assertSame( self::STORED_HASH, $this->requests[0]['body']['site_hash'] );
		$this->assertSame( self::STORED_HASH, get_option( 'paidy_site_hash' ), 'The stored hash must not be replaced.' );
		$this->assertSame( array(), $warnings );
	}

	/**
	 * The first application creates a hash, stores it and sends the same value.
	 */
	public function test_first_application_creates_and_sends_a_site_hash() {
		$this->boot_wizard();

		$warnings = $this->save_onboarding_settings( $this->application( 2 ) );

		$site_hash = get_option( 'paidy_site_hash' );
		$this->assertIsString( $site_hash );
		$this->assertSame( 16, strlen( $site_hash ) );
		$this->assertCount( 1, $this->requests, 'The application should have been sent once.' );
		$this->assertSame( $site_hash, $this->requests[0]['body']['site_hash'] );
		$this->assertSame( array(), $warnings );
	}

	/**
	 * Moving saved settings from step 1 to step 2 also sends the stored hash.
	 */
	public function test_update_from_step_one_sends_the_stored_site_hash() {
		update_option( 'paidy_site_hash', self::STORED_HASH );
		// Saved before the wizard hooks in, so only the update below is handled.
		update_option( self::ONBOARDING_OPTION, $this->application( 1 ) );
		$this->boot_wizard();

		$warnings = $this->save_onboarding_settings( $this->application( 2 ) );

		$this->assertCount( 1, $this->requests, 'The application should have been sent once.' );
		$this->assertSame( self::STORED_HASH, $this->requests[0]['body']['site_hash'] );
		$this->assertSame( self::STORED_HASH, get_option( 'paidy_site_hash' ) );
		$this->assertSame( array(), $warnings );
	}

	/**
	 * An empty stored hash is replaced, and the application carries the hash the site now keeps.
	 */
	public function test_empty_stored_site_hash_is_replaced_and_sent() {
		update_option( 'paidy_site_hash', '' );
		$this->boot_wizard();

		$warnings = $this->save_onboarding_settings( $this->application( 2 ) );

		$site_hash = get_option( 'paidy_site_hash' );
		$this->assertSame( 16, strlen( $site_hash ) );
		$this->assertCount( 1, $this->requests, 'The application should have been sent once.' );
		$this->assertSame( $site_hash, $this->requests[0]['body']['site_hash'] );
		$this->assertSame( array(), $warnings );
	}
}
