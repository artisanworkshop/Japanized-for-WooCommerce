<?php
/**
 * Tests for the removal of the usage tracking feature.
 *
 * Usage tracking is being rebuilt as a new feature, so the old implementation
 * is removed in 2.9.17 (issue #239). It sent a store profile to wc.artws.info
 * on every first install and on every update to a new version, and weekly
 * when enabled. The update send ran from the new version's own code, through
 * the jp4wc_updated action fired by JP4WC_Install::check_version(), so
 * removing the code from 2.9.17 also stops the send on the update to 2.9.17.
 * The update also removes the leftovers: the weekly WP-Cron event, the
 * setting and the last-send time.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Usage_Tracking_Removal_Test
 */
class JP4WC_Usage_Tracking_Removal_Test extends WP_UnitTestCase {

	/**
	 * Host of the usage tracking service.
	 */
	const TRACKING_HOST = 'wc.artws.info';

	/**
	 * WP-Cron hook of the weekly send.
	 */
	const CRON_HOOK = 'jp4wc_tracker_send_event';

	/**
	 * WP-Cron hook of the weekly send in versions 2.6.10 to 2.6.19.
	 */
	const LEGACY_CRON_HOOK = 'wc4jp_tracker_send_event';

	/**
	 * Options the usage tracking feature stored.
	 *
	 * @var string[]
	 */
	private $tracking_options = array( 'wc4jp-tracking', 'jp4wc_tracker_last_send' );

	/**
	 * URLs requested through the WP HTTP API during a test.
	 *
	 * @var string[]
	 */
	private $requested_urls = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'JP4WC_Install' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-install.php';
		}
		if ( ! class_exists( 'JP4WC_Settings_API' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/admin/class-jp4wc-settings-api.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Install' ) );
		$this->assertTrue( class_exists( 'JP4WC_Settings_API' ) );

		// An update runs on an admin request, where get_plugins() and get_dropins() are loaded.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->requested_urls = array();
		add_filter( 'pre_http_request', array( $this, 'record_request' ), 10, 3 );

		delete_transient( 'jp4wc_installing' );
		foreach ( $this->tracking_options as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'record_request' ), 10 );
		wp_clear_scheduled_hook( self::CRON_HOOK );
		wp_clear_scheduled_hook( self::LEGACY_CRON_HOOK );
		foreach ( $this->tracking_options as $option ) {
			delete_option( $option );
		}
		update_option( 'jp4wc_version', JP4WC_VERSION );
		parent::tearDown();
	}

	/**
	 * Short-circuit every HTTP request and record its URL.
	 *
	 * @param false|array|WP_Error $preempt     Whether to preempt the request.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 * @return array Mocked HTTP response.
	 */
	public function record_request( $preempt, $parsed_args, $url ) {
		$this->requested_urls[] = $url;

		return array(
			'headers'  => array(),
			'body'     => '{}',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Requests sent to the usage tracking service.
	 *
	 * @return string[]
	 */
	private function tracking_requests() {
		return array_values(
			array_filter(
				$this->requested_urls,
				static function ( $url ) {
					return self::TRACKING_HOST === wp_parse_url( $url, PHP_URL_HOST );
				}
			)
		);
	}

	/**
	 * Updating from 2.9.16 sends nothing to the usage tracking service.
	 */
	public function test_update_sends_nothing_to_the_tracking_service() {
		update_option( 'jp4wc_version', '2.9.16' );

		JP4WC_Install::check_version();

		$this->assertSame( JP4WC_VERSION, get_option( 'jp4wc_version' ), 'The update must have run.' );
		$this->assertSame( array(), $this->tracking_requests() );
	}

	/**
	 * A first install sends nothing to the usage tracking service.
	 */
	public function test_new_install_sends_nothing_to_the_tracking_service() {
		delete_option( 'jp4wc_version' );

		JP4WC_Install::check_version();

		$this->assertSame( JP4WC_VERSION, get_option( 'jp4wc_version' ), 'The install must have run.' );
		$this->assertSame( array(), $this->tracking_requests() );
	}

	/**
	 * The update removes the weekly event (under both names), the setting and the last-send time.
	 */
	public function test_update_removes_the_tracking_schedule_and_options() {
		update_option( 'jp4wc_version', '2.9.16' );
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::CRON_HOOK );
		// 2.6.10 to 2.6.19 scheduled the event again on every update, without a duplicate check.
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::LEGACY_CRON_HOOK );
		wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', self::LEGACY_CRON_HOOK );
		update_option( 'wc4jp-tracking', '1' );
		update_option( 'jp4wc_tracker_last_send', time() );
		$this->assertNotFalse( wp_next_scheduled( self::CRON_HOOK ), 'The event must be scheduled before the update.' );
		$this->assertNotFalse( wp_next_scheduled( self::LEGACY_CRON_HOOK ), 'The legacy event must be scheduled before the update.' );

		JP4WC_Install::check_version();

		$this->assertFalse( wp_next_scheduled( self::CRON_HOOK ) );
		$this->assertFalse( wp_next_scheduled( self::LEGACY_CRON_HOOK ) );
		$this->assertFalse( get_option( 'wc4jp-tracking' ) );
		$this->assertFalse( get_option( 'jp4wc_tracker_last_send' ) );
	}

	/**
	 * The settings screen neither reports nor saves the tracking setting.
	 */
	public function test_settings_api_neither_reports_nor_saves_tracking() {
		$api = new JP4WC_Settings_API();

		$reported = $api->get_settings( new WP_REST_Request( 'GET', '/jp4wc/v1/settings' ) )->get_data();
		$this->assertArrayNotHasKey( 'tracking', $reported );

		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/settings' );
		$request->set_body_params( array( 'tracking' => '1' ) );
		$api->update_settings( $request );
		$this->assertFalse( get_option( 'wc4jp-tracking' ) );
	}
}
