<?php
/**
 * Tests for the REST API of the Security tab (issue #259).
 *
 * Every request is dispatched through the REST server, so the routes and
 * their permission callbacks are tested as registered. Requests over HTTP
 * (WP-Cron started for a re-check) are answered by a `pre_http_request`
 * filter; nothing goes out.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_API_Test
 */
class JP4WC_Security_API_Test extends WP_UnitTestCase {

	/**
	 * URLs requested over HTTP.
	 *
	 * @var string[]
	 */
	private $requests = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_API' ), 'The plugin loads the security REST API at plugins_loaded.' );

		update_option( JP4WC_Security_Feed::SETTING, '1' );
		JP4WC_Security_Feed::purge();
		$this->requests = array();
		add_filter( 'pre_http_request', array( $this, 'answer' ), 10, 3 );
		$this->log_in( true );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'answer' ), 10 );
		JP4WC_Security_Feed::purge();
		update_option( JP4WC_Security_Feed::SETTING, '1' );
		parent::tearDown();
	}

	/**
	 * Records a request and refuses it (pre_http_request).
	 *
	 * @param false|array|WP_Error $pre  Response so far.
	 * @param array                $args Request arguments.
	 * @param string               $url  URL.
	 * @return WP_Error
	 */
	public function answer( $pre, $args, $url ) {
		$this->requests[] = $url;
		return new WP_Error( 'http_request_failed', 'No request goes out in this test.' );
	}

	/**
	 * Logs in an editor, with or without the capability to manage WooCommerce.
	 *
	 * Granted by hand: WooCommerce's shop_manager role is not there in every test environment (CI).
	 *
	 * @param bool $manage_woocommerce Whether the user manages WooCommerce.
	 */
	private function log_in( $manage_woocommerce ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		if ( $manage_woocommerce ) {
			wp_get_current_user()->add_cap( 'manage_woocommerce' );
		}
	}

	/**
	 * Gets the findings.
	 *
	 * @return WP_REST_Response
	 */
	private function get_findings() {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/jp4wc/v1/security/findings' ) );
	}

	/**
	 * Asks for a re-check.
	 *
	 * @param string|null $nonce Value of the X-WP-Nonce header; null to send none.
	 * @return WP_REST_Response
	 */
	private function post_rescan( $nonce ) {
		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/security/rescan' );
		if ( null !== $nonce ) {
			$request->set_header( 'X-WP-Nonce', $nonce );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Stores the result of the local checks and, unless null, vulnerability findings.
	 *
	 * @param array<string, string> $statuses Check ID => status; the others pass.
	 * @param array|null            $items    Affected copies, or null for no vulnerability data.
	 * @param array                 $extra    Values to change in the findings.
	 */
	private static function store( array $statuses, $items = null, array $extra = array() ) {
		$checks = array();
		foreach ( JP4WC_Security_Checks::CHECKS as $check_id => $check ) {
			$checks[] = array(
				'check_id'    => $check_id,
				'requirement' => $check['requirement'],
				'severity'    => $check['severity'],
				'status'      => isset( $statuses[ $check_id ] ) ? $statuses[ $check_id ] : 'pass',
			);
		}
		$findings = array(
			'checked_at' => time(),
			'checks'     => $checks,
			'score'      => JP4WC_Security_Checks::score( $checks ),
		);
		if ( null !== $items ) {
			$count = 0;
			foreach ( $items as $item ) {
				$count += count( $item['vulnerabilities'] );
			}
			$findings = array_merge(
				$findings,
				array(
					'version'      => '2026-10-20.1',
					'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'stale'        => false,
					'copyrights'   => array(
						'defiant' => array(
							'notice'      => 'Copyright 2012-2026 Defiant Inc.',
							'license'     => 'Sample license text.',
							'license_url' => 'https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/',
						),
					),
					'attribution'  => 'Vulnerability data: Wordfence Intelligence',
					'count'        => $count,
					'max_severity' => JP4WC_Security_Scanner::max_rating( $items ),
					'items'        => $items,
				)
			);
		}
		update_option( JP4WC_Security_Feed::FINDINGS_OPTION, array_merge( $findings, $extra ), false );
	}

	/**
	 * One affected copy with one record, as the scanner stores it.
	 *
	 * @param string $type Type.
	 * @param string $slug Slug.
	 * @param string $file Plugin file.
	 * @return array
	 */
	private static function item( $type, $slug, $file = '' ) {
		$id = '00000000-0000-4000-8000-000000000001';
		return array(
			'type'            => $type,
			'slug'            => $slug,
			'name'            => 'Sample',
			'version'         => '1.2.3',
			'file'            => $file,
			'vulnerabilities' => array(
				array(
					'id'                => $id,
					'title'             => 'Sample <= 1.2.3 - Cross-Site Scripting',
					'reference'         => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/' . $id,
					'rating'            => 'High',
					'score'             => 7.2,
					'patched'           => true,
					'patched_versions'  => array( '1.2.4' ),
					'affected_versions' => array(),
				),
			),
		);
	}

	/**
	 * Both routes are registered.
	 */
	public function test_routes_are_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/jp4wc/v1/security/findings', $routes );
		$this->assertArrayHasKey( '/jp4wc/v1/security/rescan', $routes );
		$this->assertSame( 404, rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/jp4wc/v1/security/findings' ) )->get_status(), 'The findings are read only.' );
		$this->assertSame( 404, rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/jp4wc/v1/security/rescan' ) )->get_status(), 'A re-check is not asked for with GET.' );
	}

	/**
	 * Only users who manage WooCommerce read the findings.
	 */
	public function test_findings_need_manage_woocommerce() {
		$this->assertSame( 200, $this->get_findings()->get_status() );

		$this->log_in( false );
		$response = $this->get_findings();
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );

		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->get_findings()->get_status() );
	}

	/**
	 * Only users who manage WooCommerce ask for a re-check.
	 */
	public function test_rescan_needs_manage_woocommerce() {
		$this->log_in( false );
		$response = $this->post_rescan( wp_create_nonce( 'wp_rest' ) );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );

		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->post_rescan( wp_create_nonce( 'wp_rest' ) )->get_status() );

		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
		$this->assertSame( 0, JP4WC_Security_Feed::recheck_requested_at() );
	}

	/**
	 * Nonces a re-check is refused with.
	 *
	 * @return array<string, array{callable}>
	 */
	public function bad_nonce_provider() {
		return array(
			'none'                    => array(
				static function () {
					return null;
				},
			),
			'empty'                   => array(
				static function () {
					return '';
				},
			),
			'not a nonce'             => array(
				static function () {
					return 'abcdef0123';
				},
			),
			'nonce of another action' => array(
				static function () {
					return wp_create_nonce( 'jp4wc_security_notice_dismiss' );
				},
			),
		);
	}

	/**
	 * A re-check needs the REST nonce of the settings screen.
	 *
	 * @dataProvider bad_nonce_provider
	 *
	 * @param callable $nonce Returns the nonce sent.
	 */
	public function test_rescan_needs_the_rest_nonce( $nonce ) {
		$response = $this->post_rescan( $nonce() );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'jp4wc_invalid_nonce', $response->get_data()['code'] );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
		$this->assertSame( 0, JP4WC_Security_Feed::recheck_requested_at() );
	}

	/**
	 * A re-check is scheduled in WP-Cron, which is started at once, and the
	 * findings say it is running until a check that started after it is saved.
	 */
	public function test_rescan_schedules_a_recheck() {
		self::store( array(), array(), array( 'checked_at' => time() - HOUR_IN_SECONDS ) );

		$response = $this->post_rescan( wp_create_nonce( 'wp_rest' ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RESCAN_HOOK ), 'Not the automatic rescan, which keeps the requests to the site.' );
		$requested_at = JP4WC_Security_Feed::recheck_requested_at();
		$this->assertEqualsWithDelta( time(), $requested_at, 5 );
		$cron_requests = array_filter(
			$this->requests,
			static function ( $url ) {
				return false !== strpos( $url, 'wp-cron.php' );
			}
		);
		$this->assertCount( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 0 : 1, $cron_requests, 'WP-Cron is started, unless it is disabled.' );

		$data = $response->get_data();
		$this->assertSame( $requested_at, $data['recheck']['requested_at'] );
		$this->assertTrue( $data['recheck']['pending'] );
		$this->assertGreaterThan( 0, $data['recheck']['retry_after'] );
		$this->assertTrue( $this->get_findings()->get_data()['recheck']['pending'] );

		self::store( array(), array(), array( 'checked_at' => $requested_at ) );
		$this->assertFalse( $this->get_findings()->get_data()['recheck']['pending'], 'Done once the findings come from a check that started after the request.' );
	}

	/**
	 * A second re-check within a minute is refused, with the seconds to wait.
	 */
	public function test_rescan_cannot_be_repeated_at_once() {
		$this->assertSame( 202, $this->post_rescan( wp_create_nonce( 'wp_rest' ) )->get_status() );

		$response = $this->post_rescan( wp_create_nonce( 'wp_rest' ) );

		$this->assertSame( 429, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'jp4wc_security_rescan_too_soon', $data['code'] );
		$this->assertGreaterThan( 0, $data['data']['retry_after'] );
		$this->assertLessThanOrEqual( JP4WC_Security_API::RECHECK_INTERVAL, $data['data']['retry_after'] );

		update_option( JP4WC_Security_Feed::RECHECK_OPTION, time() - JP4WC_Security_API::RECHECK_INTERVAL - 1, false );
		$this->assertSame( 202, $this->post_rescan( wp_create_nonce( 'wp_rest' ) )->get_status() );
		$this->assertSame( 1, self::count_events( JP4WC_Security_Feed::RECHECK_HOOK ), 'A re-check still waiting is not scheduled twice.' );
	}

	/**
	 * A request time in the future (the clock went back) waits one interval at most.
	 */
	public function test_request_time_in_the_future_waits_one_interval() {
		update_option( JP4WC_Security_Feed::RECHECK_OPTION, time() + DAY_IN_SECONDS, false );

		$this->assertLessThanOrEqual( JP4WC_Security_API::RECHECK_INTERVAL, $this->get_findings()->get_data()['recheck']['retry_after'] );
	}

	/**
	 * A check turned off is not re-checked.
	 */
	public function test_rescan_while_off_is_refused() {
		update_option( JP4WC_Security_Feed::SETTING, '' );

		$response = $this->post_rescan( wp_create_nonce( 'wp_rest' ) );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'jp4wc_security_disabled', $response->get_data()['code'] );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
	}

	/**
	 * Before the first check, nothing is reported.
	 */
	public function test_not_checked_yet() {
		$data = $this->get_findings()->get_data();

		$this->assertTrue( $data['enabled'] );
		$this->assertSame( 0, $data['checked_at'] );
		$this->assertNull( $data['score'] );
		$this->assertNull( $data['requirements'] );
		$this->assertSame( array(), $data['checks'] );
		$this->assertNull( $data['vulnerabilities'] );
		$this->assertFalse( $data['feed']['has_data'] );
		$this->assertFalse( $data['feed']['unavailable'] );
		$this->assertSame( 'wc.artws.info', $data['feed']['host'] );
		$this->assertFalse( $data['recheck']['pending'] );
		$this->assertSame( 0, $data['recheck']['retry_after'] );
	}

	/**
	 * Everything passed and nothing was found.
	 */
	public function test_nothing_found() {
		self::store( array(), array() );

		$data = $this->get_findings()->get_data();

		$this->assertSame( 100, $data['score']['total'] );
		$this->assertSame( array( 'vuln', 'admin', 'data', 'malware' ), array_keys( $data['requirements'] ) );
		foreach ( $data['requirements'] as $requirement ) {
			$this->assertSame( 'pass', $requirement['status'] );
			$this->assertSame( 100, $requirement['score'] );
			$this->assertSame( 0, $requirement['fail'] );
			$this->assertSame( 0, $requirement['unknown'] );
		}
		$this->assertCount( count( JP4WC_Security_Checks::CHECKS ), $data['checks'] );
		$this->assertSame( array(), $data['vulnerabilities']['items'] );
		$this->assertSame( 0, $data['vulnerabilities']['count'] );
		$this->assertFalse( $data['vulnerabilities']['stale'] );
		$this->assertSame( 'Vulnerability data: Wordfence Intelligence', $data['vulnerabilities']['attribution'] );
		$this->assertSame(
			array(
				array(
					'notice'      => 'Copyright 2012-2026 Defiant Inc.',
					'license'     => 'Sample license text.',
					'license_url' => 'https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/',
				),
			),
			$data['vulnerabilities']['copyrights']
		);
	}

	/**
	 * Checks that could not be run leave a requirement unconfirmed, at full score.
	 */
	public function test_unknown_checks_leave_requirements_unconfirmed() {
		$statuses         = array_fill_keys( array_keys( JP4WC_Security_Checks::CHECKS ), 'unknown' );
		$statuses['M-01'] = 'pass';
		self::store( $statuses );

		$data = $this->get_findings()->get_data();

		$this->assertSame( 100, $data['score']['total'] );
		foreach ( $data['requirements'] as $requirement ) {
			$this->assertSame( 'unknown', $requirement['status'] );
		}
		$this->assertSame( 1, $data['requirements']['malware']['pass'] );
		$this->assertSame( 2, $data['requirements']['malware']['unknown'] );
		$this->assertNull( $data['vulnerabilities'], 'No vulnerability data.' );
	}

	/**
	 * A failed check makes its requirement need action, whatever else is unknown.
	 */
	public function test_failed_check_needs_action() {
		self::store(
			array(
				'A-01' => 'fail',
				'A-02' => 'unknown',
			)
		);

		$data = $this->get_findings()->get_data();

		$this->assertSame( 'action', $data['requirements']['admin']['status'] );
		$this->assertSame( 100 - JP4WC_Security_Checks::POINTS['medium'], $data['requirements']['admin']['score'] );
		$this->assertSame( 1, $data['requirements']['admin']['fail'] );
		$this->assertSame( 1, $data['requirements']['admin']['unknown'] );
		$this->assertSame( 3, $data['requirements']['admin']['pass'] );
		$this->assertSame( 'pass', $data['requirements']['data']['status'] );
		$this->assertSame( $data['requirements']['admin']['score'], $data['score']['total'] );
	}

	/**
	 * Vulnerability findings carry what the table shows, and where each copy is updated.
	 */
	public function test_vulnerabilities_link_to_where_they_are_updated() {
		self::store(
			array( 'V-01' => 'fail' ),
			array(
				self::item( 'plugin', 'sample-plugin', 'sample-plugin/sample-plugin.php' ),
				self::item( 'theme', 'sample-theme' ),
				self::item( 'core', 'wordpress' ),
			)
		);

		$items = $this->get_findings()->get_data()['vulnerabilities']['items'];

		$this->assertSame( admin_url( 'plugins.php' ) . '#checkbox_' . md5( 'sample-plugin/sample-plugin.php' ), $items[0]['manage_url'] );
		$this->assertSame( admin_url( 'themes.php?theme=sample-theme' ), $items[1]['manage_url'] );
		$this->assertSame( network_admin_url( 'update-core.php' ), $items[2]['manage_url'] );
		$this->assertSame( array( 'type', 'slug', 'name', 'version', 'manage_url', 'vulnerabilities' ), array_keys( $items[0] ) );
		$this->assertSame(
			array(
				'id'               => '00000000-0000-4000-8000-000000000001',
				'title'            => 'Sample <= 1.2.3 - Cross-Site Scripting',
				'reference'        => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/00000000-0000-4000-8000-000000000001',
				'rating'           => 'High',
				'score'            => 7.2,
				'patched'          => true,
				'patched_versions' => array( '1.2.4' ),
			),
			$items[0]['vulnerabilities'][0]
		);
	}

	/**
	 * Data generated days ago is reported as out of date.
	 */
	public function test_old_data_is_stale() {
		self::store( array(), array(), array( 'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 4 * DAY_IN_SECONDS ) ) );

		$this->assertTrue( $this->get_findings()->get_data()['vulnerabilities']['stale'] );
	}

	/**
	 * Syncs that failed for days without data are reported.
	 */
	public function test_feed_unavailable() {
		self::store( array( 'V-01' => 'unknown' ) );
		update_option( JP4WC_Security_Feed::STATE_OPTION, array( 'failing_since' => time() - 4 * DAY_IN_SECONDS ), false );

		$data = $this->get_findings()->get_data();

		$this->assertTrue( $data['feed']['unavailable'] );
		$this->assertTrue( $data['feed']['failing'] );
		$this->assertFalse( $data['feed']['has_data'] );
		$this->assertNull( $data['vulnerabilities'] );
		$this->assertNotNull( $data['requirements'], 'The local checks still show.' );
	}

	/**
	 * A check turned off reports nothing but that.
	 */
	public function test_off_reports_nothing() {
		update_option( JP4WC_Security_Feed::SETTING, '' );

		$data = $this->get_findings()->get_data();

		$this->assertFalse( $data['enabled'] );
		$this->assertNull( $data['requirements'] );
		$this->assertNull( $data['vulnerabilities'] );
		$this->assertSame( array(), $data['checks'] );
	}

	/**
	 * Malformed stored checks are left out.
	 */
	public function test_malformed_checks_are_left_out() {
		self::store( array() );
		$findings             = get_option( JP4WC_Security_Feed::FINDINGS_OPTION );
		$findings['checks'][] = 'not a check';
		$findings['checks'][] = array(
			'check_id'    => 'X-01',
			'requirement' => 'admin',
			'severity'    => 'low',
			'status'      => 'broken',
		);
		update_option( JP4WC_Security_Feed::FINDINGS_OPTION, $findings, false );

		$data = $this->get_findings()->get_data();

		$this->assertCount( count( JP4WC_Security_Checks::CHECKS ), $data['checks'] );
		$this->assertSame( 'pass', $data['requirements']['admin']['status'] );
	}

	/**
	 * Counts the scheduled events of a hook.
	 *
	 * @param string $hook Hook.
	 * @return int
	 */
	private static function count_events( $hook ) {
		$count = 0;
		foreach ( _get_cron_array() as $events ) {
			if ( isset( $events[ $hook ] ) ) {
				$count += count( $events[ $hook ] );
			}
		}
		return $count;
	}
}
