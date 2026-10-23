<?php
/**
 * Tests for the REST API of the free security check (artws-sec/v1).
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Rest_Scan_Test
 *
 * Sends real requests through rest_get_server()->dispatch(), as a client
 * would, and checks the responses against the contract.
 */
class Artws_Sec_Rest_Scan_Test extends Artws_Sec_Scan_Test_Case {

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		self::publish_contract_feed();
	}

	/**
	 * Registers the sample site and returns its site token and report token.
	 *
	 * @param array $overrides Top-level keys of the request to replace.
	 * @return array{0: string, 1: string}
	 */
	private function register_site( array $overrides = array() ) {
		$response = self::send( 'POST', '/scan', self::sample_request( $overrides ) );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		return array( $data['site_token'], $data['report']['token'] );
	}

	/**
	 * Ends every rate limit window, as if an hour had passed.
	 */
	private static function reset_rate_limits() {
		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . Artws_Sec_Db::limits_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test helper.
	}

	/**
	 * Returns every row of the sites and scans tables as text.
	 *
	 * @return string
	 */
	private static function dump_tables() {
		global $wpdb;
		$rows = array();
		foreach ( array( Artws_Sec_Db::sites_table(), Artws_Sec_Db::scans_table(), Artws_Sec_Db::limits_table() ) as $table ) {
			$rows[ $table ] = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test helper.
		}
		return wp_json_encode( $rows );
	}

	/**
	 * Asserts an error response.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param int              $status   Expected status.
	 * @param string           $code     Expected code.
	 */
	private function assert_error( WP_REST_Response $response, $status, $code ) {
		$this->assertSame( $status, $response->get_status() );
		$this->assertSame( $code, $response->get_data()['code'] );
		$this->assertSame( 'no-store', $response->get_headers()['Cache-Control'] );
		$this->assert_matches_schema( self::as_json( $response ), 'error' );
	}

	/**
	 * The routes are registered.
	 */
	public function test_routes_are_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/artws-sec/v1/scan', $routes );
		$this->assertArrayHasKey( '/artws-sec/v1/scan/(?P<token>[A-Za-z0-9_-]+)', $routes );
		$this->assertArrayHasKey( '/artws-sec/v1/site', $routes );
	}

	/**
	 * The first scan registers the site and returns its token once; the database keeps only its hash.
	 */
	public function test_first_scan_returns_the_site_token_once() {
		$response = self::send( 'POST', '/scan', self::sample_request() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'no-store', $response->get_headers()['Cache-Control'] );
		$this->assert_matches_schema( self::as_json( $response ), 'scan-response' );
		$data  = $response->get_data();
		$token = $data['site_token'];
		$this->assertMatchesRegularExpression( '/^sit_[A-Za-z0-9_-]{43}$/D', $token );

		$tables = self::dump_tables();
		$this->assertStringNotContainsString( $token, $tables );
		$this->assertStringContainsString( hash( 'sha256', $token ), $tables );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );

		self::reset_rate_limits();
		$again = self::send( 'POST', '/scan', self::sample_request(), $token );

		$this->assertSame( 200, $again->get_status() );
		$this->assertArrayNotHasKey( 'site_token', $again->get_data() );
		$this->assert_matches_schema( self::as_json( $again ), 'scan-response' );
		$this->assertNotSame( $data['report']['token'], $again->get_data()['report']['token'] );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );
		$this->assertSame( 2, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * A registered site needs its token for each route: none or a wrong one is 401.
	 */
	public function test_registered_site_needs_its_token() {
		list( $token, $scan ) = $this->register_site();
		$wrong                = Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SITE_TOKEN_PREFIX );
		self::reset_rate_limits();

		foreach ( array( null, $wrong, 'not-a-token', substr( $token, 0, -1 ) ) as $bad ) {
			$this->assert_error( self::send( 'POST', '/scan', self::sample_request(), $bad ), 401, 'artws_sec_unauthorized' );
			$this->assert_error( self::send( 'GET', '/scan/' . $scan, null, $bad ), 401, 'artws_sec_unauthorized' );
			$this->assert_error( self::send( 'DELETE', '/site', null, $bad ), 401, 'artws_sec_unauthorized' );
		}
		$this->assertSame( 'Bearer realm="artws-sec"', self::send( 'GET', '/scan/' . $scan )->get_headers()['WWW-Authenticate'] );

		// The refused requests used up nothing: the site can still scan this hour.
		$this->assertSame( 200, self::send( 'POST', '/scan', self::sample_request(), $token )->get_status() );
		$this->assertSame( 2, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * A non-bearer Authorization header counts as a wrong token.
	 */
	public function test_other_authorization_scheme_is_refused() {
		list( $token, $scan ) = $this->register_site();

		$request = new WP_REST_Request( 'GET', '/artws-sec/v1/scan/' . $scan );
		$request->set_header( 'Authorization', 'Token ' . $token );
		$this->assert_error( rest_get_server()->dispatch( $request ), 401, 'artws_sec_unauthorized' );

		$request->set_header( 'Authorization', 'bearer  ' . $token );
		$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );
	}

	/**
	 * GET /scan/{token} returns the saved report with the site's token, without the site token.
	 */
	public function test_get_scan() {
		$first                = self::send( 'POST', '/scan', self::sample_request() );
		list( $token, $scan ) = array( $first->get_data()['site_token'], $first->get_data()['report']['token'] );

		$response = self::send( 'GET', '/scan/' . $scan, null, $token );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'schema_version' => 1,
				'report'         => $first->get_data()['report'],
			),
			$response->get_data()
		);
		$this->assertSame( 'no-store', $response->get_headers()['Cache-Control'] );
		$this->assert_matches_schema( self::as_json( $response ), 'scan-response' );
	}

	/**
	 * Another site's token does not open a report.
	 */
	public function test_get_scan_with_another_sites_token() {
		list( , $scan )       = $this->register_site();
		list( $other_token, ) = $this->register_site( array( 'site_url' => 'https://other.example.jp' ) );

		$this->assert_error( self::send( 'GET', '/scan/' . $scan, null, $other_token ), 401, 'artws_sec_unauthorized' );
	}

	/**
	 * A scan token that does not exist or is malformed is 404.
	 */
	public function test_unknown_scan_is_not_found() {
		list( $token, ) = $this->register_site();

		$this->assert_error( self::send( 'GET', '/scan/' . Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SCAN_TOKEN_PREFIX ), null, $token ), 404, 'artws_sec_scan_not_found' );
		$this->assert_error( self::send( 'GET', '/scan/scn_short', null, $token ), 404, 'artws_sec_scan_not_found' );
		$this->assertSame( 404, self::send( 'GET', '/scan/scn_a.b', null, $token )->get_status() );
	}

	/**
	 * The scan token is read from the route, never from the query string.
	 */
	public function test_scan_token_comes_from_the_route() {
		list( $token, $scan ) = $this->register_site();

		$request = new WP_REST_Request( 'GET', '/artws-sec/v1/scan/' . Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SCAN_TOKEN_PREFIX ) );
		$request->set_header( 'Authorization', 'Bearer ' . $token );
		$request->set_query_params( array( 'token' => $scan ) );

		$this->assert_error( rest_get_server()->dispatch( $request ), 404, 'artws_sec_scan_not_found' );
	}

	/**
	 * DELETE /site removes the site and its scans; its reports are then 404 and its token 401.
	 */
	public function test_delete_site() {
		list( $token, $scan ) = $this->register_site();
		list( $other, )       = $this->register_site( array( 'site_url' => 'https://other.example.jp' ) );

		$response = self::send( 'DELETE', '/site', null, $token );

		$this->assertSame( 204, $response->get_status() );
		$this->assertNull( $response->get_data() );
		$this->assertSame( 'no-store', $response->get_headers()['Cache-Control'] );
		$this->assert_error( self::send( 'GET', '/scan/' . $scan, null, $token ), 404, 'artws_sec_scan_not_found' );
		$this->assert_error( self::send( 'DELETE', '/site', null, $token ), 401, 'artws_sec_unauthorized' );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );
		$this->assertNotNull( Artws_Sec_Site_Store::find_site_by_token( $other ) );
	}

	/**
	 * Deleting a site keeps its rate limit, so that registering again does not reset it.
	 */
	public function test_delete_site_keeps_the_rate_limit() {
		list( $token, ) = $this->register_site();
		$this->assertSame( 204, self::send( 'DELETE', '/site', null, $token )->get_status() );

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 429, 'artws_sec_rate_limited' );
	}

	/**
	 * Registering again deletes the site's scans and makes the old token useless.
	 */
	public function test_reregister() {
		list( $old_token, $old_scan ) = $this->register_site();
		self::reset_rate_limits();

		$response = self::send( 'POST', '/scan', self::sample_request( array( 'reregister' => true ) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assert_matches_schema( self::as_json( $response ), 'scan-response' );
		$new_token = $response->get_data()['site_token'];
		$new_scan  = $response->get_data()['report']['token'];
		$this->assertNotSame( $old_token, $new_token );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );

		$this->assert_error( self::send( 'GET', '/scan/' . $old_scan, null, $new_token ), 404, 'artws_sec_scan_not_found' );
		$this->assert_error( self::send( 'GET', '/scan/' . $new_scan, null, $old_token ), 401, 'artws_sec_unauthorized' );
		$this->assert_error( self::send( 'DELETE', '/site', null, $old_token ), 401, 'artws_sec_unauthorized' );
		self::reset_rate_limits();
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request(), $old_token ), 401, 'artws_sec_unauthorized' );
		$this->assertSame( 200, self::send( 'GET', '/scan/' . $new_scan, null, $new_token )->get_status() );
		$this->assertStringNotContainsString( $new_token, self::dump_tables() );
	}

	/**
	 * A token for a site that is not registered is 401; registering again registers it.
	 */
	public function test_unregistered_site_with_a_token() {
		list( $token, ) = $this->register_site();
		$moved          = self::sample_request( array( 'site_url' => 'https://moved.example.jp' ) );

		$this->assert_error( self::send( 'POST', '/scan', $moved, $token ), 401, 'artws_sec_unauthorized' );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );

		$moved['reregister'] = true;
		$response            = self::send( 'POST', '/scan', $moved, $token );
		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'site_token', $response->get_data() );
		$this->assertSame( 2, self::count_rows( Artws_Sec_Db::sites_table() ) );
		// The first site keeps its token.
		$this->assertNotNull( Artws_Sec_Site_Store::find_site_by_token( $token ) );
	}

	/**
	 * The scheme, the case of the host and a trailing slash do not make another site.
	 */
	public function test_same_site_over_http_and_https() {
		list( $token, ) = $this->register_site( array( 'site_url' => 'http://Example.jp/' ) );
		self::reset_rate_limits();

		$response = self::send( 'POST', '/scan', self::sample_request( array( 'site_url' => 'https://example.jp' ) ), $token );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );
		$this->assertSame( 'https://example.jp', Artws_Sec_Site_Store::find_site_by_token( $token )->site_url );

		$this->assertNotSame( Artws_Sec_Site_Store::site_key( 'https://example.jp' ), Artws_Sec_Site_Store::site_key( 'https://example.jp/shop' ) );
		$this->assertNotSame( Artws_Sec_Site_Store::site_key( 'https://example.jp' ), Artws_Sec_Site_Store::site_key( 'https://www.example.jp' ) );
		$this->assertNotSame( Artws_Sec_Site_Store::site_key( 'https://example.jp' ), Artws_Sec_Site_Store::site_key( 'https://example.jp:8443' ) );
	}

	/**
	 * Keys the contract does not define are neither refused nor stored.
	 */
	public function test_unknown_keys_are_not_stored() {
		$body                               = self::sample_request();
		$body['admin_email']                = 'owner@example.jp';
		$body['order_total']                = 98765432;
		$body['software'][0]['path']        = '/var/www/html/wp-content/plugins/woocommerce';
		$body['local_findings'][0]['value'] = 'DISALLOW_FILE_EDIT=false';

		$this->assertSame( 200, self::send( 'POST', '/scan', $body )->get_status() );

		$tables = self::dump_tables();
		foreach ( array( 'owner@example.jp', 'admin_email', '98765432', '/var/www/html', 'DISALLOW_FILE_EDIT=false' ) as $text ) {
			$this->assertStringNotContainsString( $text, $tables );
		}

		global $wpdb;
		$stored = $wpdb->get_var( 'SELECT request FROM ' . Artws_Sec_Db::scans_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test.
		$this->assertSame( self::sample_request(), json_decode( $stored, true ) );
	}

	/**
	 * Invalid requests are 400 and leave nothing behind, not even a rate limit hit.
	 *
	 * @return array<string, array{array, string}>
	 */
	public function invalid_provider() {
		return array(
			'revenue amount'          => array( array( 'revenue_band' => '12000000' ), 'artws_sec_invalid_request' ),
			'unknown order band'      => array( array( 'order_band' => 'O9' ), 'artws_sec_invalid_request' ),
			'unknown consent version' => array( array( 'consent_version' => '2030-01' ), 'artws_sec_unknown_consent_version' ),
			'missing site_url'        => array( array( 'site_url' => null ), 'artws_sec_invalid_request' ),
		);
	}

	/**
	 * Invalid requests are refused.
	 *
	 * @dataProvider invalid_provider
	 *
	 * @param array  $overrides Keys of the sample to replace.
	 * @param string $code      Expected error code.
	 */
	public function test_invalid_request( array $overrides, $code ) {
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request( $overrides ) ), 400, $code );

		$this->assertSame( '{"' . Artws_Sec_Db::sites_table() . '":[],"' . Artws_Sec_Db::scans_table() . '":[],"' . Artws_Sec_Db::limits_table() . '":[]}', self::dump_tables() );
	}

	/**
	 * A body that is not JSON is refused by WordPress itself.
	 */
	public function test_body_that_is_not_json() {
		$request = new WP_REST_Request( 'POST', '/artws-sec/v1/scan' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( '{"schema_version":1,' );

		$this->assertSame( 400, rest_get_server()->dispatch( $request )->get_status() );
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::sites_table() ) );
	}

	/**
	 * A body over 1 MB is 413.
	 */
	public function test_body_too_large() {
		$body            = self::sample_request();
		$body['padding'] = str_repeat( 'a', Artws_Sec_Rest_Controller::MAX_BODY_BYTES );

		$this->assert_error( self::send( 'POST', '/scan', $body ), 413, 'artws_sec_request_too_large' );
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::sites_table() ) );
	}

	/**
	 * A site can scan once an hour, registration included.
	 */
	public function test_rate_limit_per_site() {
		list( $token, ) = $this->register_site();
		$before         = time();

		$response = self::send( 'POST', '/scan', self::sample_request(), $token );

		$this->assert_error( $response, 429, 'artws_sec_rate_limited' );
		$retry_at = strtotime( $response->get_data()['data']['retry_at'] );
		$this->assertGreaterThanOrEqual( $before + HOUR_IN_SECONDS - 5, $retry_at );
		$this->assertLessThanOrEqual( time() + HOUR_IN_SECONDS, $retry_at );
		$retry_after = (int) $response->get_headers()['Retry-After'];
		$this->assertGreaterThan( HOUR_IN_SECONDS - 10, $retry_after );
		$this->assertLessThanOrEqual( HOUR_IN_SECONDS, $retry_after );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * Registering again is rate limited too.
	 */
	public function test_rate_limit_of_reregistering() {
		list( $token, ) = $this->register_site();

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request( array( 'reregister' => true ) ) ), 429, 'artws_sec_rate_limited' );
		$this->assertNotNull( Artws_Sec_Site_Store::find_site_by_token( $token ) );
	}

	/**
	 * One address can register 20 sites an hour; another address is counted apart.
	 */
	public function test_rate_limit_per_address() {
		for ( $i = 0; $i < 20; $i++ ) {
			$this->register_site( array( 'site_url' => "https://shop{$i}.example.jp" ) );
		}

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request( array( 'site_url' => 'https://shop20.example.jp' ) ) ), 429, 'artws_sec_rate_limited' );
		$this->assertSame( 20, self::count_rows( Artws_Sec_Db::sites_table() ) );

		$_SERVER['REMOTE_ADDR'] = '198.51.100.7';
		$this->register_site( array( 'site_url' => 'https://shop20.example.jp' ) );
	}

	/**
	 * A refusal by the address limit does not use up the site's hourly scan.
	 */
	public function test_address_limit_does_not_use_up_the_site_limit() {
		add_filter(
			'artws_sec_scans_per_address',
			static function () {
				return 1;
			}
		);
		$this->register_site( array( 'site_url' => 'https://first.example.jp' ) );

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 429, 'artws_sec_rate_limited' );

		$_SERVER['REMOTE_ADDR'] = '198.51.100.7';
		$this->register_site();
	}

	/**
	 * IPv6 addresses are counted per /64.
	 */
	public function test_rate_limit_counts_ipv6_per_64() {
		add_filter(
			'artws_sec_scans_per_address',
			static function () {
				return 1;
			}
		);
		$_SERVER['REMOTE_ADDR'] = '2001:db8:1:2::10';
		$this->register_site( array( 'site_url' => 'https://first.example.jp' ) );

		$_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:ffff::1';
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 429, 'artws_sec_rate_limited' );

		$_SERVER['REMOTE_ADDR'] = '2001:db8:1:3::10';
		$this->register_site();

		$this->assertSame( '2001:db8:1:3::/64', Artws_Sec_Rate_Limiter::client_address() );
		$this->assertStringNotContainsString( '2001:db8', self::dump_tables() );
	}

	/**
	 * The address the rate limit counts.
	 */
	public function test_client_address() {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.1';
		$this->assertSame( '203.0.113.1', Artws_Sec_Rate_Limiter::client_address() );

		// An IPv4-mapped address (a server listening on IPv6 for both) is its IPv4 address, not ::/64.
		$_SERVER['REMOTE_ADDR'] = '::ffff:192.0.2.1';
		$this->assertSame( '192.0.2.1', Artws_Sec_Rate_Limiter::client_address() );
		$_SERVER['REMOTE_ADDR'] = '::ffff:198.51.100.7';
		$this->assertSame( '198.51.100.7', Artws_Sec_Rate_Limiter::client_address() );

		// Behind a proxy: the header it sets, the last of a list; without it, REMOTE_ADDR.
		add_filter(
			'artws_sec_client_ip_header',
			static function () {
				return 'HTTP_X_ARTWS_TEST_CLIENT';
			}
		);
		$this->assertSame( '198.51.100.7', Artws_Sec_Rate_Limiter::client_address() );
		$_SERVER['HTTP_X_ARTWS_TEST_CLIENT'] = '192.0.2.99, 203.0.113.50';
		try {
			$this->assertSame( '203.0.113.50', Artws_Sec_Rate_Limiter::client_address() );
		} finally {
			unset( $_SERVER['HTTP_X_ARTWS_TEST_CLIENT'] );
		}

		add_filter(
			'artws_sec_client_ip',
			static function () {
				return 'not an address';
			}
		);
		$this->assertSame( '', Artws_Sec_Rate_Limiter::client_address() );
	}

	/**
	 * Without vulnerability data the API answers 503 and registers nothing.
	 */
	public function test_unavailable_without_the_index() {
		delete_option( Artws_Sec_Feed_Updater::STATE_OPTION );
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 503, 'artws_sec_unavailable' );

		// A feed published before 0.3.0 has no index yet.
		self::publish_contract_feed();
		$state = get_option( Artws_Sec_Feed_Updater::STATE_OPTION );
		unset( $state['index_version'] );
		update_option( Artws_Sec_Feed_Updater::STATE_OPTION, $state, false );
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 503, 'artws_sec_unavailable' );

		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::sites_table() ) );
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::limits_table() ) );
	}

	/**
	 * An index that is gone or cannot be read is 503, not a report that finds nothing.
	 */
	public function test_unavailable_when_the_index_is_missing_or_unreadable() {
		global $wpdb;
		list( $token, ) = $this->register_site();
		self::reset_rate_limits();

		Artws_Sec_Vuln_Index::delete_version( self::FEED_VERSION );
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request(), $token ), 503, 'artws_sec_unavailable' );

		self::publish_contract_feed();
		$table = Artws_Sec_Db::vulns_table();
		add_filter(
			'query',
			static function ( $query ) use ( $table ) {
				return 0 === strpos( $query, "SELECT lookup_key, records FROM {$table}" ) ? 'SELECT broken FROM artws_sec_no_such_table' : $query;
			}
		);
		$suppress = $wpdb->suppress_errors( true );
		try {
			$this->assert_error( self::send( 'POST', '/scan', self::sample_request(), $token ), 503, 'artws_sec_unavailable' );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		// Neither used up the site's hourly scan.
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::limits_table() ) );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * An index row that cannot be read is 503, since it could hide a vulnerability.
	 */
	public function test_unavailable_when_an_index_row_is_broken() {
		global $wpdb;
		$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test fixture.
			Artws_Sec_Db::vulns_table(),
			array(
				'feed_version' => self::FEED_VERSION,
				'lookup_key'   => Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'sample-plugin' ),
				'records'      => '{"not":"a list"}',
			)
		);

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 503, 'artws_sec_unavailable' );
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::sites_table() ) );
	}

	/**
	 * A token problem is 401 even while the vulnerability data is not ready.
	 */
	public function test_unauthorized_comes_before_unavailable() {
		$this->register_site();
		delete_option( Artws_Sec_Feed_Updater::STATE_OPTION );

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 401, 'artws_sec_unauthorized' );
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request( array( 'site_url' => 'https://moved.example.jp' ) ), 'sit_' . str_repeat( 'a', 43 ) ), 401, 'artws_sec_unauthorized' );
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request( array( 'site_url' => 'https://new.example.jp' ) ) ), 503, 'artws_sec_unavailable' );
	}

	/**
	 * Breaks the queries that start with a text, until the test ends.
	 *
	 * @param string $start Start of the queries to break.
	 */
	private static function break_queries( $start ) {
		add_filter(
			'query',
			static function ( $query ) use ( $start ) {
				return 0 === strpos( ltrim( $query ), $start ) ? 'SELECT broken FROM artws_sec_no_such_table' : $query;
			}
		);
	}

	/**
	 * Rate limits that cannot be read or counted refuse the scan instead of letting it through.
	 */
	public function test_rate_limit_fails_closed() {
		global $wpdb;
		$suppress = $wpdb->suppress_errors( true );
		try {
			self::break_queries( 'SELECT limit_key, window_start, hits FROM ' . Artws_Sec_Db::limits_table() );
			$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 500, 'artws_sec_server_error' );
			remove_all_filters( 'query' );

			self::break_queries( 'INSERT INTO ' . Artws_Sec_Db::limits_table() );
			$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 500, 'artws_sec_server_error' );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::sites_table() ) );
	}

	/**
	 * A registration whose scan cannot be saved is undone, so the site can register again.
	 */
	public function test_failed_save_undoes_the_registration() {
		global $wpdb;
		$suppress = $wpdb->suppress_errors( true );
		try {
			self::break_queries( 'INSERT INTO ' . Artws_Sec_Db::scans_table() . ' (' );
			$this->assert_error( self::send( 'POST', '/scan', self::sample_request() ), 500, 'artws_sec_server_error' );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::sites_table() ) );

		remove_all_filters( 'query' );
		self::reset_rate_limits();
		$this->register_site();
	}

	/**
	 * A re-registration whose scan cannot be saved leaves the site on its previous token.
	 */
	public function test_failed_save_keeps_the_previous_token() {
		global $wpdb;
		list( $token, ) = $this->register_site();
		self::reset_rate_limits();

		$suppress = $wpdb->suppress_errors( true );
		try {
			self::break_queries( 'INSERT INTO ' . Artws_Sec_Db::scans_table() . ' (' );
			$this->assert_error( self::send( 'POST', '/scan', self::sample_request( array( 'reregister' => true ) ) ), 500, 'artws_sec_server_error' );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertNotNull( Artws_Sec_Site_Store::find_site_by_token( $token ) );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::sites_table() ) );
	}

	/**
	 * Requests over http are refused unless the filter allows them.
	 */
	public function test_http_is_refused() {
		list( $token, $scan ) = $this->register_site();
		unset( $_SERVER['HTTPS'] );

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request(), $token ), 403, 'artws_sec_https_required' );
		$this->assert_error( self::send( 'GET', '/scan/' . $scan, null, $token ), 403, 'artws_sec_https_required' );
		$this->assert_error( self::send( 'DELETE', '/site', null, $token ), 403, 'artws_sec_https_required' );

		add_filter( 'artws_sec_require_https', '__return_false' );
		$this->assertSame( 200, self::send( 'GET', '/scan/' . $scan, null, $token )->get_status() );
	}

	/**
	 * The query string is not read: the request is the JSON body.
	 */
	public function test_query_string_is_ignored() {
		list( $token, ) = $this->register_site();
		self::reset_rate_limits();

		$request = new WP_REST_Request( 'POST', '/artws-sec/v1/scan' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'Authorization', 'Bearer ' . $token );
		$request->set_body( wp_json_encode( self::sample_request() ) );
		$request->set_query_params(
			array(
				'reregister'   => true,
				'revenue_band' => 'B9',
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'site_token', $response->get_data() );
		$this->assertNotNull( Artws_Sec_Site_Store::find_site_by_token( $token ) );
		$this->assertSame( 2, self::count_rows( Artws_Sec_Db::scans_table() ) );
		$stored = $GLOBALS['wpdb']->get_var( 'SELECT request FROM ' . Artws_Sec_Db::scans_table() . ' ORDER BY id DESC LIMIT 1' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test.
		$this->assertSame( 'B2', json_decode( $stored, true )['revenue_band'] );
	}

	/**
	 * A request over the rate limit is refused before the site's software is matched.
	 */
	public function test_rate_limited_request_is_not_matched() {
		list( $token, ) = $this->register_site();
		$table          = Artws_Sec_Db::vulns_table();
		$lookups        = 0;
		add_filter(
			'query',
			static function ( $query ) use ( $table, &$lookups ) {
				if ( false !== strpos( $query, "FROM {$table}" ) ) {
					++$lookups;
				}
				return $query;
			}
		);

		$this->assert_error( self::send( 'POST', '/scan', self::sample_request(), $token ), 429, 'artws_sec_rate_limited' );
		$this->assert_error( self::send( 'POST', '/scan', self::sample_request( array( 'reregister' => true ) ) ), 429, 'artws_sec_rate_limited' );
		$this->assertSame( 0, $lookups );
	}
}
