<?php
/**
 * Tests for the free security check of wc.artws.info (issue #260).
 *
 * Requests over HTTP are recorded and answered by a `pre_http_request`
 * filter with the samples of the JSON contract (tests/fixtures/security-feed/);
 * nothing goes out. The REST routes are dispatched through the REST server,
 * so their permission callbacks are tested as registered.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Scan_Test
 */
class JP4WC_Security_Scan_Test extends WP_UnitTestCase {

	/**
	 * A site token in the contract's format.
	 */
	const TOKEN = 'sit_c3RvcmVkLXNpdGUtdG9rZW4tZm9yLXRoZS10ZXN0cy0wMQ';

	/**
	 * The keys the free check sends: top level, each software entry and each
	 * local finding. The readme's External Services entry lists the same.
	 */
	const SENT_KEYS     = array(
		'schema_version',
		'site_url',
		'wp_ver',
		'wc_ver',
		'php_ver',
		'jp4wc_ver',
		'locale',
		'software',
		'local_findings',
		'consent_version',
		'revenue_band',
		'order_band',
	);
	const SOFTWARE_KEYS = array( 'type', 'slug', 'version', 'active' );
	const FINDING_KEYS  = array( 'check_id', 'status' );

	/**
	 * Requests sent over HTTP: method, url, headers and body.
	 *
	 * @var array<int, array{method: string, url: string, headers: array, body: mixed}>
	 */
	private $requests = array();

	/**
	 * Responses to give, in order; a request beyond them fails.
	 *
	 * @var array<int, array|WP_Error>
	 */
	private $responses = array();

	/**
	 * Plugins get_plugins() returns in this test, or null for the installed ones.
	 *
	 * @var array|null
	 */
	private $plugins = null;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Scan' ), 'The plugin loads the free check at plugins_loaded.' );

		update_option( JP4WC_Security_Feed::SETTING, '1' );
		update_option( 'woocommerce_currency', 'JPY' );
		self::forget();
		self::store_checks( array( 'A-04' => 'fail' ) );

		$this->requests  = array();
		$this->responses = array();
		add_filter( 'pre_http_request', array( $this, 'answer' ), 10, 3 );
		$this->log_in( true );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'answer' ), 10 );
		wp_cache_delete( 'plugins', 'plugins' );
		self::forget();
		JP4WC_Security_Feed::purge();
		update_option( JP4WC_Security_Feed::SETTING, '1' );
		parent::tearDown();
	}

	/**
	 * Removes what the free check stores.
	 */
	private static function forget() {
		delete_option( JP4WC_Security_Scan::TOKEN_OPTION );
		delete_option( JP4WC_Security_Scan::CONSENT_OPTION );
		delete_option( JP4WC_Security_Scan::REPORT_OPTION );
		delete_option( JP4WC_Security_Scan::LOCK_OPTION );
	}

	/**
	 * Records a request and gives the next response (pre_http_request).
	 *
	 * @param false|array|WP_Error $pre  Response so far.
	 * @param array                $args Request arguments.
	 * @param string               $url  URL.
	 * @return array|WP_Error
	 */
	public function answer( $pre, $args, $url ) {
		$this->requests[] = array(
			'method'  => $args['method'],
			'url'     => $url,
			'headers' => $args['headers'],
			'body'    => $args['body'],
			'args'    => $args,
		);
		if ( array() === $this->responses ) {
			return new WP_Error( 'http_request_failed', 'No request goes out in this test.' );
		}
		return array_shift( $this->responses );
	}

	/**
	 * Returns an HTTP response.
	 *
	 * @param int          $status  Status.
	 * @param array|string $body    Body; an array is sent as JSON.
	 * @param array        $headers Headers, with lowercase names.
	 * @return array
	 */
	private static function response( $status, $body = '', array $headers = array() ) {
		return array(
			'headers'  => $headers,
			'body'     => is_array( $body ) ? wp_json_encode( $body ) : $body,
			'response' => array(
				'code'    => $status,
				'message' => get_status_header_desc( $status ),
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Decodes a JSON file of the contract.
	 *
	 * @param string $file Path relative to the contract directory.
	 * @return array
	 */
	private static function fixture( $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.
		return json_decode( file_get_contents( TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/' . $file ), true, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * The 200 of a POST /scan that registered the site (with site_token).
	 *
	 * @return array
	 */
	private static function registered() {
		return self::response( 200, self::fixture( 'scan-response.json' ) );
	}

	/**
	 * The 200 of a POST /scan with a token (no site_token).
	 *
	 * @return array
	 */
	private static function scanned() {
		$body = self::fixture( 'scan-response.json' );
		unset( $body['site_token'] );
		return self::response( 200, $body );
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
	 * Stores the result of the local checks.
	 *
	 * @param array<string, string> $statuses Check ID => status; the others pass.
	 */
	private static function store_checks( array $statuses ) {
		$checks = array();
		foreach ( JP4WC_Security_Checks::CHECKS as $check_id => $check ) {
			$checks[] = array(
				'check_id'    => $check_id,
				'requirement' => $check['requirement'],
				'severity'    => $check['severity'],
				'status'      => isset( $statuses[ $check_id ] ) ? $statuses[ $check_id ] : 'pass',
			);
		}
		update_option(
			JP4WC_Security_Feed::FINDINGS_OPTION,
			array(
				'checked_at' => time(),
				'checks'     => $checks,
				'score'      => JP4WC_Security_Checks::score( $checks ),
			),
			false
		);
	}

	/**
	 * Stores a token as a check before this test left it.
	 *
	 * @param string $site_url The site URL it was issued for.
	 */
	private static function store_token( $site_url ) {
		update_option(
			JP4WC_Security_Scan::TOKEN_OPTION,
			array(
				'token'    => self::TOKEN,
				'site_url' => $site_url,
			),
			false
		);
	}

	/**
	 * Stores a consent to the current text.
	 */
	private static function consent() {
		JP4WC_Security_Scan::record_consent( false );
	}

	/**
	 * Dispatches a request to the free check's route.
	 *
	 * @param string      $method HTTP method.
	 * @param array       $params Parameters.
	 * @param string|null $nonce  Value of the X-WP-Nonce header; null to send a valid one.
	 * @return WP_REST_Response
	 */
	private function dispatch( $method, array $params = array(), $nonce = null ) {
		$request = new WP_REST_Request( $method, '/jp4wc/v1/security/scan' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$request->set_header( 'X-WP-Nonce', null === $nonce ? wp_create_nonce( 'wp_rest' ) : $nonce );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Returns the Authorization header of a recorded request, or null.
	 *
	 * @param int $index Request.
	 * @return string|null
	 */
	private function authorization( $index ) {
		$headers = $this->requests[ $index ]['headers'];
		return isset( $headers['Authorization'] ) ? $headers['Authorization'] : null;
	}

	/**
	 * Lets get_plugins() return these plugins.
	 *
	 * @param array $plugins Plugin file => header data.
	 */
	private static function use_plugins( array $plugins ) {
		wp_cache_set( 'plugins', array( '' => $plugins ), 'plugins' );
	}

	/**
	 * Creates an order.
	 *
	 * @param float  $total   Total.
	 * @param string $status  Status.
	 * @param int    $created Unix time it was created.
	 * @return WC_Order
	 */
	private static function order( $total, $status, $created = 0 ) {
		$order = wc_create_order();
		$order->set_total( $total );
		$order->set_status( $status );
		if ( $created > 0 ) {
			$order->set_date_created( $created );
		}
		$order->save();
		return $order;
	}

	/**
	 * The route answers GET, POST and DELETE.
	 */
	public function test_route_is_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/jp4wc/v1/security/scan', $routes );
		$methods = array();
		foreach ( $routes['/jp4wc/v1/security/scan'] as $handler ) {
			$methods = array_merge( $methods, array_keys( $handler['methods'] ) );
		}
		$this->assertEqualsCanonicalizing( array( 'GET', 'POST', 'DELETE' ), $methods );
	}

	/**
	 * Showing what would be sent sends nothing, bands included.
	 */
	public function test_view_sends_nothing() {
		self::order( 1000, 'processing' );

		$response = $this->dispatch( 'GET', array( 'bands' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), $this->requests );
		$data = $response->get_data();
		$this->assertSame( JP4WC_Security_Scan::payload( true ), $data['payload'], 'The preview is what is sent.' );
		$this->assertSame( 'B1', $data['payload']['revenue_band'] );
		$this->assertSame( 'O1', $data['payload']['order_band'] );
		$this->assertTrue( $data['bands_available'] );
		$this->assertFalse( $data['bands_error'] );
		$this->assertNull( $data['consent'] );
		$this->assertFalse( $data['registered'] );
		$this->assertTrue( $data['ready'] );
		$this->assertSame( '2026-10', $data['consent_version'] );
		$this->assertSame( 'https://wc.artws.info/security-assessment-term/', $data['terms_url'] );
		$this->assertSame( 'https://wc.artws.info/privacy-policy/', $data['privacy_url'] );

		$without = $this->dispatch( 'GET' )->get_data();
		$this->assertArrayNotHasKey( 'revenue_band', $without['payload'] );
		$this->assertArrayNotHasKey( 'order_band', $without['payload'] );
	}

	/**
	 * What is sent has the contract's keys and nothing else, and the readme
	 * lists the same keys.
	 */
	public function test_sent_keys_match_the_contract_and_the_readme() {
		self::order( 1000, 'processing' );
		$payload = JP4WC_Security_Scan::payload( true );
		$schema  = self::fixture( 'schema/scan-request.schema.json' );

		$this->assertSame( self::SENT_KEYS, array_keys( $payload ) );
		$this->assertEqualsCanonicalizing( array_keys( $schema['properties'] ), self::SENT_KEYS );
		$this->assertSame( array(), array_diff( $schema['required'], array_keys( JP4WC_Security_Scan::payload( false ) ) ) );
		$this->assertEqualsCanonicalizing( array_keys( $schema['properties']['software']['items']['properties'] ), self::SOFTWARE_KEYS );
		$this->assertEqualsCanonicalizing( array_keys( $schema['properties']['local_findings']['items']['properties'] ), self::FINDING_KEYS );
		$this->assertNotEmpty( $payload['software'] );
		foreach ( $payload['software'] as $software ) {
			$this->assertSame( self::SOFTWARE_KEYS, array_keys( $software ) );
		}
		foreach ( $payload['local_findings'] as $finding ) {
			$this->assertSame( self::FINDING_KEYS, array_keys( $finding ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$readme = file_get_contents( TESTS_PLUGIN_DIR . '/readme.txt' );
		$this->assertSame( 1, preg_match( '/^\* \*\*Artisan Workshop free security check\*\*.*$/m', $readme, $entry ), 'The readme has an External Services entry for the free check.' );
		preg_match_all( '/`([a-z0-9_]+)`/', $entry[0], $listed );
		$this->assertEqualsCanonicalizing( array_merge( self::SENT_KEYS, self::SOFTWARE_KEYS, self::FINDING_KEYS ), array_unique( $listed[1] ) );
	}

	/**
	 * The administrator's e-mail, the server paths and order amounts are not sent.
	 */
	public function test_private_values_are_not_sent() {
		update_option( 'admin_email', 'owner-260@example.jp' );
		self::order( 123457, 'processing' );

		// Without escaped slashes, so that a path inside any value is found.
		$json = wp_json_encode( JP4WC_Security_Scan::payload( true ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$this->assertStringNotContainsString( 'owner-260@example.jp', $json );
		$this->assertStringNotContainsString( '123457', $json );
		$this->assertStringNotContainsString( untrailingslashit( ABSPATH ), $json );
		$this->assertStringNotContainsString( WP_CONTENT_DIR, $json );
	}

	/**
	 * Every plugin is sent, active or not, with its Version header as written,
	 * or empty when it is too long or holds a control character.
	 */
	public function test_software_versions_are_sent_as_the_contract_says() {
		self::use_plugins(
			array(
				'sample/sample.php'    => array( 'Version' => '1.2.3' ),
				'single.php'           => array( 'Version' => '2.0-beta' ),
				'multibyte/plugin.php' => array( 'Version' => str_repeat( 'あ', 64 ) ),
				'long/plugin.php'      => array( 'Version' => str_repeat( '1', 65 ) ),
				'control/plugin.php'   => array( 'Version' => "1.0\x07" ),
				'c1/plugin.php'        => array( 'Version' => "1.0\u{0085}" ),
				'none/plugin.php'      => array(),
				"bad\x01/plugin.php"   => array( 'Version' => '1.0' ),
			)
		);
		$active = static function ( $plugins ) {
			return array_merge( (array) $plugins, array( 'sample/sample.php' ) );
		};
		add_filter( 'option_active_plugins', $active );
		try {
			$software = JP4WC_Security_Scan::payload()['software'];
		} finally {
			remove_filter( 'option_active_plugins', $active );
		}

		$plugins = array();
		foreach ( $software as $entry ) {
			if ( 'plugin' === $entry['type'] ) {
				$plugins[ $entry['slug'] ] = array( $entry['version'], $entry['active'] );
			}
		}
		$this->assertSame(
			array(
				'sample'    => array( '1.2.3', true ),
				'single'    => array( '2.0-beta', false ),
				'multibyte' => array( str_repeat( 'あ', 64 ), false ),
				'long'      => array( '', false ),
				'control'   => array( '', false ),
				'c1'        => array( '', false ),
				'none'      => array( '', false ),
			),
			$plugins,
			'A slug with a control character is left out.'
		);

		// The test library's theme directories hold themes in subdirectories
		// (subdir/theme2): a slug with a slash is not one the contract takes.
		$expected = array_values(
			array_filter(
				array_map( 'strval', array_keys( wp_get_themes() ) ),
				static function ( $stylesheet ) {
					return false === strpos( $stylesheet, '/' );
				}
			)
		);
		$themes   = wp_list_filter( $software, array( 'type' => 'theme' ) );
		$this->assertEqualsCanonicalizing( $expected, wp_list_pluck( $themes, 'slug' ), 'Every theme is sent.' );
		$this->assertCount( 1, wp_list_filter( $themes, array( 'slug' => get_stylesheet() ) ) );
		$this->assertTrue( current( wp_list_filter( $themes, array( 'slug' => get_stylesheet() ) ) )['active'] );
	}

	/**
	 * Only the ID and status of each local check are sent.
	 */
	public function test_local_findings_are_statuses_only() {
		$findings = JP4WC_Security_Scan::payload()['local_findings'];

		$this->assertCount( count( JP4WC_Security_Checks::CHECKS ), $findings );
		$this->assertContains(
			array(
				'check_id' => 'A-04',
				'status'   => 'fail',
			),
			$findings
		);
	}

	/**
	 * Revenue bands of the decided boundaries.
	 *
	 * @return array
	 */
	public function revenue_provider() {
		return array(
			'refunded beyond' => array( -1, 'B0' ),
			'none'            => array( 0, 'B0' ),
			'one yen'         => array( 1, 'B1' ),
			'1M'              => array( 1000000, 'B1' ),
			'1M + 1'          => array( 1000001, 'B2' ),
			'10M'             => array( 10000000, 'B2' ),
			'10M + 1'         => array( 10000001, 'B3' ),
			'50M'             => array( 50000000, 'B3' ),
			'50M + 1'         => array( 50000001, 'B4' ),
			'100M'            => array( 100000000, 'B4' ),
			'100M + 0.5'      => array( 100000000.5, 'B5' ),
		);
	}

	/**
	 * Revenue is banded at the decided boundaries.
	 *
	 * @dataProvider revenue_provider
	 *
	 * @param float  $amount Revenue.
	 * @param string $band   Band.
	 */
	public function test_revenue_band( $amount, $band ) {
		$this->assertSame( $band, JP4WC_Security_Scan::revenue_band( $amount ) );
	}

	/**
	 * Order bands of the decided boundaries.
	 *
	 * @return array
	 */
	public function order_provider() {
		return array(
			'none'     => array( 0, 'O0' ),
			'one'      => array( 1, 'O1' ),
			'100'      => array( 100, 'O1' ),
			'101'      => array( 101, 'O2' ),
			'1,000'    => array( 1000, 'O2' ),
			'1,001'    => array( 1001, 'O3' ),
			'10,000'   => array( 10000, 'O3' ),
			'10,001'   => array( 10001, 'O4' ),
			'a string' => array( '5', 'O1' ),
		);
	}

	/**
	 * Orders are banded at the decided boundaries.
	 *
	 * @dataProvider order_provider
	 *
	 * @param int|string $count Orders.
	 * @param string     $band  Band.
	 */
	public function test_order_band( $count, $band ) {
		$this->assertSame( $band, JP4WC_Security_Scan::order_band( $count ) );
	}

	/**
	 * Order storages: HPOS and the posts table.
	 *
	 * @return array
	 */
	public function storage_provider() {
		return array(
			'HPOS'  => array( 'yes' ),
			'posts' => array( 'no' ),
		);
	}

	/**
	 * The bands count the paid orders of the last 365 days, less refunds, in
	 * either order storage.
	 *
	 * @dataProvider storage_provider
	 *
	 * @param string $hpos Value of woocommerce_custom_orders_table_enabled.
	 */
	public function test_bands_count_paid_orders_of_the_year_less_refunds( $hpos ) {
		// The bootstrap creates the HPOS tables; orders are stored as posts until switched.
		update_option( 'woocommerce_custom_orders_table_enabled', $hpos );
		update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
		$this->assertSame( 'yes' === $hpos, Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() );

		$refunded = self::order( 600000, 'processing' );
		self::order( 500000, 'completed' );
		self::order( 5000000, 'pending' );
		self::order( 7000000, 'cancelled' );
		self::order( 9000000, 'completed', time() - 400 * DAY_IN_SECONDS );

		$this->assertSame(
			array(
				'orders'  => 2,
				'revenue' => 1100000.0,
			),
			JP4WC_Security_Scan::order_totals()
		);
		$this->assertSame(
			array(
				'revenue_band' => 'B2',
				'order_band'   => 'O1',
			),
			JP4WC_Security_Scan::bands()
		);

		$refund = wc_create_refund(
			array(
				'order_id' => $refunded->get_id(),
				'amount'   => 200000,
			)
		);
		$this->assertNotWPError( $refund );

		$this->assertSame(
			array(
				'orders'  => 2,
				'revenue' => 900000.0,
			),
			JP4WC_Security_Scan::order_totals()
		);
		$this->assertSame( 'B1', JP4WC_Security_Scan::bands()['revenue_band'] );
	}

	/**
	 * Without orders the bands are B0 / O0.
	 */
	public function test_no_orders_are_b0_o0() {
		$this->assertSame(
			array(
				'revenue_band' => 'B0',
				'order_band'   => 'O0',
			),
			JP4WC_Security_Scan::bands()
		);
	}

	/**
	 * The bands are offered only when the store currency is yen, and never
	 * sent otherwise.
	 */
	public function test_bands_need_yen() {
		update_option( 'woocommerce_currency', 'USD' );
		$this->responses = array( self::registered() );

		$this->assertFalse( JP4WC_Security_Scan::bands_available() );
		$this->assertNull( JP4WC_Security_Scan::bands() );
		$this->assertArrayNotHasKey( 'revenue_band', JP4WC_Security_Scan::payload( true ) );
		$view = $this->dispatch( 'GET', array( 'bands' => true ) )->get_data();
		$this->assertFalse( $view['bands_available'] );
		$this->assertArrayNotHasKey( 'revenue_band', $view['payload'] );

		$this->assertSame(
			200,
			$this->dispatch(
				'POST',
				array(
					'consent' => true,
					'bands'   => true,
				)
			)->get_status()
		);
		$sent = json_decode( $this->requests[0]['body'], true );
		$this->assertArrayNotHasKey( 'revenue_band', $sent );
		$this->assertArrayNotHasKey( 'order_band', $sent );
		$this->assertFalse( JP4WC_Security_Scan::consent()['bands'] );
	}

	/**
	 * Without the separate tick, neither band is sent.
	 */
	public function test_bands_are_sent_only_when_ticked() {
		self::order( 1000, 'processing' );
		$this->responses = array( self::registered(), self::scanned() );

		$this->dispatch( 'POST', array( 'consent' => true ) );
		$this->dispatch( 'POST', array( 'bands' => true ) );

		$first  = json_decode( $this->requests[0]['body'], true );
		$second = json_decode( $this->requests[1]['body'], true );
		$this->assertArrayNotHasKey( 'revenue_band', $first );
		$this->assertArrayNotHasKey( 'order_band', $first );
		$this->assertSame( 'B1', $second['revenue_band'] );
		$this->assertSame( 'O1', $second['order_band'] );
		$this->assertTrue( JP4WC_Security_Scan::consent()['bands'] );
	}

	/**
	 * Nothing is sent before the consent.
	 */
	public function test_nothing_is_sent_without_consent() {
		$response = $this->dispatch( 'POST' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'jp4wc_security_scan_consent_required', $response->get_data()['code'] );
		$this->assertSame( array(), $this->requests );
		$this->assertNull( JP4WC_Security_Scan::consent() );

		// A consent to another version of the text does not count.
		update_option(
			JP4WC_Security_Scan::CONSENT_OPTION,
			array(
				'version'      => '2026-09',
				'consented_at' => time(),
				'bands'        => false,
			)
		);
		$this->assertSame( 400, $this->dispatch( 'POST' )->get_status() );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * The check needs the security check on and its results; nothing is sent
	 * or stored before.
	 */
	public function test_check_needs_local_results() {
		delete_option( JP4WC_Security_Feed::FINDINGS_OPTION );

		$response = $this->dispatch( 'POST', array( 'consent' => true ) );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'jp4wc_security_scan_not_ready', $response->get_data()['code'] );
		$this->assertSame( array(), $this->requests );
		$this->assertNull( JP4WC_Security_Scan::consent() );
		$this->assertFalse( $this->dispatch( 'GET' )->get_data()['ready'] );

		self::store_checks( array() );
		update_option( JP4WC_Security_Feed::SETTING, '' );
		$this->assertSame( 409, $this->dispatch( 'POST', array( 'consent' => true ) )->get_status() );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * The first check registers the site: no Authorization, and the token
	 * comes back to be stored with the site URL, never shown.
	 */
	public function test_first_check_registers_the_site() {
		$this->responses = array( self::registered() );

		$response = $this->dispatch( 'POST', array( 'consent' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->requests );
		$request = $this->requests[0];
		$this->assertSame( 'POST', $request['method'] );
		$this->assertSame( 'https://wc.artws.info/wp-json/artws-sec/v1/scan', $request['url'] );
		$this->assertNull( $this->authorization( 0 ) );
		$this->assertSame( 'application/json', $request['headers']['Content-Type'] );
		$this->assertSame( 0, $request['args']['redirection'] );
		$this->assertStringNotContainsString( home_url(), $request['args']['user-agent'] );
		$this->assertSame( JP4WC_Security_Scan::payload(), json_decode( $request['body'], true ) );

		$fixture = self::fixture( 'scan-response.json' );
		$this->assertSame(
			array(
				'token'    => $fixture['site_token'],
				'site_url' => home_url(),
			),
			get_option( JP4WC_Security_Scan::TOKEN_OPTION )
		);
		$this->assertArrayNotHasKey( JP4WC_Security_Scan::TOKEN_OPTION, wp_load_alloptions(), 'The token is not autoloaded.' );

		$data = $response->get_data();
		$this->assertTrue( $data['registered'] );
		$this->assertFalse( $data['reregistered'] );
		$this->assertSame( $fixture['report']['token'], $data['report']['token'] );
		$this->assertSame( $fixture['report']['score'], $data['report']['score'] );
		$this->assertCount( count( $fixture['report']['findings'] ), $data['report']['findings'] );
		$this->assertTrue( $data['consent']['current'] );
		$this->assertGreaterThan( 0, $data['consent']['consented_at'] );
		$this->assertStringNotContainsString( 'sit_', wp_json_encode( $data ), 'The token never leaves the server.' );
		$this->assertStringNotContainsString( 'sit_', wp_json_encode( $this->dispatch( 'GET' )->get_data() ) );
	}

	/**
	 * A later check sends the stored token and keeps it.
	 */
	public function test_later_check_sends_the_token() {
		self::store_token( home_url() );
		self::consent();
		$this->responses = array( self::scanned() );

		$response = $this->dispatch( 'POST' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->requests );
		$this->assertSame( 'Bearer ' . self::TOKEN, $this->authorization( 0 ) );
		$this->assertSame( self::TOKEN, get_option( JP4WC_Security_Scan::TOKEN_OPTION )['token'] );
		$this->assertFalse( $response->get_data()['reregistered'] );
	}

	/**
	 * The scheme, the case of the host and a trailing slash leave the site the same.
	 */
	public function test_same_site() {
		$this->assertTrue( JP4WC_Security_Scan::same_site( 'http://Example.org/', 'https://example.org' ) );
		$this->assertTrue( JP4WC_Security_Scan::same_site( 'https://example.org/shop/', 'http://EXAMPLE.org/shop' ) );
		$this->assertFalse( JP4WC_Security_Scan::same_site( 'https://example.org/shop', 'https://example.org/Shop' ) );
		$this->assertFalse( JP4WC_Security_Scan::same_site( 'https://example.org:8443', 'https://example.org' ) );
		$this->assertFalse( JP4WC_Security_Scan::same_site( 'https://staging.example.org', 'https://example.org' ) );
	}

	/**
	 * A token issued to another URL (a staging copy of the database) is not
	 * sent: the site registers again.
	 */
	public function test_token_of_another_site_is_not_sent() {
		self::store_token( 'https://production.example.jp' );
		self::consent();
		$this->responses = array( self::registered() );

		$response = $this->dispatch( 'POST' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->requests );
		$this->assertNull( $this->authorization( 0 ) );
		$this->assertTrue( $response->get_data()['reregistered'] );
		$this->assertSame( home_url(), get_option( JP4WC_Security_Scan::TOKEN_OPTION )['site_url'] );
		$this->assertNotSame( self::TOKEN, get_option( JP4WC_Security_Scan::TOKEN_OPTION )['token'] );
	}

	/**
	 * A refused token is thrown away and the check is sent once more without
	 * it; the old registration is not deleted.
	 */
	public function test_refused_token_registers_again_once() {
		self::store_token( home_url() );
		self::consent();
		update_option(
			JP4WC_Security_Scan::REPORT_OPTION,
			array(
				'report'     => array( 'token' => 'old' ),
				'site_url'   => home_url(),
				'scanned_at' => 1,
			)
		);
		$this->responses = array( self::response( 401, '{"code":"artws_sec_unauthorized"}' ), self::registered() );

		$response = $this->dispatch( 'POST' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $this->requests );
		$this->assertSame( array( 'POST', 'POST' ), wp_list_pluck( $this->requests, 'method' ) );
		$this->assertSame( 'Bearer ' . self::TOKEN, $this->authorization( 0 ) );
		$this->assertNull( $this->authorization( 1 ) );
		$this->assertSame( $this->requests[0]['body'], $this->requests[1]['body'] );
		$this->assertTrue( $response->get_data()['reregistered'] );
		$this->assertSame( self::fixture( 'scan-response.json' )['site_token'], get_option( JP4WC_Security_Scan::TOKEN_OPTION )['token'] );
		$this->assertNotSame( 'old', $response->get_data()['report']['token'] );
	}

	/**
	 * A refused token whose new registration fails: the check fails without
	 * retrying again, the old token and report are gone, and the error says
	 * that the earlier results can no longer be shown.
	 *
	 * @dataProvider failed_reregistration_provider
	 *
	 * @param array|WP_Error $second Answer to the request without the token.
	 * @param int            $status Status of this site's REST response.
	 * @param string         $code   Error code.
	 */
	public function test_failed_reregistration_is_not_retried( $second, $status, $code ) {
		self::store_token( home_url() );
		self::consent();
		update_option( JP4WC_Security_Scan::REPORT_OPTION, array( 'report' => array( 'token' => 'old' ) ) );
		$this->responses = array( self::response( 401 ), $second );

		$response = $this->dispatch( 'POST' );

		$this->assertSame( $status, $response->get_status() );
		$this->assertSame( $code, $response->get_data()['code'] );
		$this->assertTrue( $response->get_data()['data']['reregistered'] );
		$this->assertCount( 2, $this->requests );
		$this->assertNotContains( 'DELETE', wp_list_pluck( $this->requests, 'method' ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::REPORT_OPTION ) );
	}

	/**
	 * Failures of the request that registers again.
	 *
	 * @return array
	 */
	public function failed_reregistration_provider() {
		return array(
			'refused again' => array( self::response( 401 ), 502, 'jp4wc_security_scan_failed' ),
			'rate limited'  => array( self::response( 429 ), 429, 'jp4wc_security_scan_rate_limited' ),
			'unreachable'   => array( new WP_Error( 'http_request_failed', 'timeout' ), 502, 'jp4wc_security_scan_unreachable' ),
		);
	}

	/**
	 * An error that did not throw a registration away does not say so.
	 */
	public function test_error_without_reregistration_does_not_say_so() {
		self::store_token( home_url() );
		self::consent();
		$this->responses = array( self::response( 503 ) );

		$response = $this->dispatch( 'POST' );

		$this->assertSame( 503, $response->get_status() );
		$this->assertArrayNotHasKey( 'reregistered', $response->get_data()['data'] );
		$this->assertSame( self::TOKEN, get_option( JP4WC_Security_Scan::TOKEN_OPTION )['token'] );
	}

	/**
	 * Responses that are not a report: errors, nothing stored.
	 *
	 * @return array
	 */
	public function failure_provider() {
		return array(
			'rate limited'      => array( 'rate', 429, 'jp4wc_security_scan_rate_limited' ),
			'bad request'       => array( 400, 502, 'jp4wc_security_scan_rejected' ),
			'too large'         => array( 413, 502, 'jp4wc_security_scan_rejected' ),
			'not ready'         => array( 503, 503, 'jp4wc_security_scan_unavailable' ),
			'server error'      => array( 500, 502, 'jp4wc_security_scan_failed' ),
			'not JSON'          => array( 'html', 502, 'jp4wc_security_scan_invalid_response' ),
			'no site token'     => array( 'no-token', 502, 'jp4wc_security_scan_invalid_response' ),
			'not a report'      => array( 'not-report', 502, 'jp4wc_security_scan_invalid_response' ),
			'connection failed' => array( 'error', 502, 'jp4wc_security_scan_unreachable' ),
		);
	}

	/**
	 * Responses that are not a report end in an error and store nothing.
	 *
	 * @dataProvider failure_provider
	 *
	 * @param int|string $answer What wc.artws.info answers.
	 * @param int        $status Status of this site's REST response.
	 * @param string     $code   Error code.
	 */
	public function test_failures_store_nothing( $answer, $status, $code ) {
		switch ( $answer ) {
			case 'rate':
				$response = self::response( 429, self::fixture( 'error-rate-limited.json' ), array( 'retry-after' => '3600' ) );
				break;
			case 'html':
				$response = self::response( 200, '<html>Maintenance</html>' );
				break;
			case 'no-token':
				$response = self::scanned();
				break;
			case 'not-report':
				$response = self::response(
					200,
					array(
						'schema_version' => 1,
						'site_token'     => self::TOKEN,
						'report'         => array( 'token' => 'scn_short' ),
					)
				);
				break;
			case 'error':
				$response = new WP_Error( 'http_request_failed', 'cURL error 28' );
				break;
			default:
				$response = self::response( $answer, '{"code":"x"}' );
		}
		$this->responses = array( $response );

		$result = $this->dispatch( 'POST', array( 'consent' => true ) );

		$this->assertSame( $status, $result->get_status() );
		$this->assertSame( $code, $result->get_data()['code'] );
		$this->assertCount( 1, $this->requests );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::REPORT_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::LOCK_OPTION ), 'The lock is released.' );
		if ( 'rate' === $answer ) {
			$this->assertSame( strtotime( '2026-10-20T04:15:00Z' ), $result->get_data()['data']['retry_at'] );
		}
	}

	/**
	 * Another request to wc.artws.info holds the lock: nothing is sent. A
	 * lock left by a request that died is taken over.
	 */
	public function test_lock() {
		global $wpdb;
		self::consent();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Sets the lock as another request would.
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => JP4WC_Security_Scan::LOCK_OPTION,
				'option_value' => (string) time(),
				'autoload'     => 'off',
			)
		);

		$response = $this->dispatch( 'POST' );
		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'jp4wc_security_scan_busy', $response->get_data()['code'] );
		$this->assertSame( 409, $this->dispatch( 'DELETE' )->get_status() );
		$this->assertSame( array(), $this->requests );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Ages the lock.
		$wpdb->update( $wpdb->options, array( 'option_value' => (string) ( time() - JP4WC_Security_Scan::LOCK_TTL - 1 ) ), array( 'option_name' => JP4WC_Security_Scan::LOCK_OPTION ) );
		$this->responses = array( self::registered() );
		$this->assertSame( 200, $this->dispatch( 'POST' )->get_status() );
		$this->assertCount( 1, $this->requests );
	}

	/**
	 * Deleting sends DELETE /site with the token, then removes the consent,
	 * the token and the report; a 401 counts as deleted.
	 *
	 * @dataProvider deleted_provider
	 *
	 * @param int $status Status of DELETE /site.
	 */
	public function test_delete_removes_everything( $status ) {
		self::store_token( home_url() );
		self::consent();
		update_option( JP4WC_Security_Scan::REPORT_OPTION, array( 'report' => array() ) );
		$this->responses = array( self::response( $status ) );

		$response = $this->dispatch( 'DELETE' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->requests );
		$this->assertSame( 'DELETE', $this->requests[0]['method'] );
		$this->assertSame( 'https://wc.artws.info/wp-json/artws-sec/v1/site', $this->requests[0]['url'] );
		$this->assertSame( 'Bearer ' . self::TOKEN, $this->authorization( 0 ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::CONSENT_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::REPORT_OPTION ) );
		$this->assertNull( $response->get_data()['consent'] );
	}

	/**
	 * Statuses that mean the data is gone.
	 *
	 * @return array
	 */
	public function deleted_provider() {
		return array(
			'deleted'         => array( 204 ),
			'already deleted' => array( 401 ),
		);
	}

	/**
	 * A deletion that fails keeps everything, so that it can be tried again.
	 */
	public function test_failed_delete_keeps_everything() {
		self::store_token( home_url() );
		self::consent();
		$this->responses = array( self::response( 500 ), new WP_Error( 'http_request_failed', 'timeout' ) );

		$this->assertSame( 502, $this->dispatch( 'DELETE' )->get_status() );
		$this->assertSame( 502, $this->dispatch( 'DELETE' )->get_status() );

		$this->assertSame( self::TOKEN, get_option( JP4WC_Security_Scan::TOKEN_OPTION )['token'] );
		$this->assertNotNull( JP4WC_Security_Scan::consent() );
	}

	/**
	 * A token of another URL is never used to delete: only what this site keeps goes.
	 */
	public function test_delete_with_a_token_of_another_site_sends_nothing() {
		self::store_token( 'https://production.example.jp' );
		self::consent();

		$this->assertSame( 200, $this->dispatch( 'DELETE' )->get_status() );

		$this->assertSame( array(), $this->requests );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );
		$this->assertNull( JP4WC_Security_Scan::consent() );
	}

	/**
	 * Uninstalling asks wc.artws.info to delete the site's data and removes
	 * the options.
	 */
	public function test_uninstall_sends_the_deletion() {
		self::store_token( home_url() );
		self::consent();
		$this->responses = array( self::response( 204 ) );

		JP4WC_Security_Scan::uninstall();

		$this->assertCount( 1, $this->requests );
		$this->assertSame( 'DELETE', $this->requests[0]['method'] );
		$this->assertSame( 'Bearer ' . self::TOKEN, $this->authorization( 0 ) );
		$this->assertSame( JP4WC_Security_Scan::UNINSTALL_TIMEOUT, $this->requests[0]['args']['timeout'] );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Scan::CONSENT_OPTION ) );
	}

	/**
	 * Uninstalling a staging copy does not delete the production site's data,
	 * and a deletion that fails still removes the token.
	 */
	public function test_uninstall_without_a_usable_token() {
		self::store_token( 'https://production.example.jp' );
		JP4WC_Security_Scan::uninstall();
		$this->assertSame( array(), $this->requests );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );

		self::store_token( home_url() );
		JP4WC_Security_Scan::uninstall();
		$this->assertCount( 1, $this->requests, 'The failed request is not retried.' );
		$this->assertFalse( get_option( JP4WC_Security_Scan::TOKEN_OPTION ) );
	}

	/**
	 * Only users who manage WooCommerce use the route, and sending or
	 * deleting needs the REST nonce.
	 */
	public function test_permissions() {
		self::consent();
		$this->assertSame( 403, $this->dispatch( 'POST', array(), 'bad' )->get_status() );
		$this->assertSame( 403, $this->dispatch( 'DELETE', array(), 'bad' )->get_status() );

		$this->log_in( false );
		$this->assertSame( 403, $this->dispatch( 'GET' )->get_status() );
		$this->assertSame( 403, $this->dispatch( 'POST' )->get_status() );
		$this->assertSame( 403, $this->dispatch( 'DELETE' )->get_status() );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * The settings REST response does not carry the token.
	 */
	public function test_settings_do_not_carry_the_token() {
		self::store_token( home_url() );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/jp4wc/v1/settings' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringNotContainsString( self::TOKEN, wp_json_encode( $response->get_data() ) );
		$this->assertStringNotContainsString( self::TOKEN, wp_json_encode( $this->dispatch( 'GET' )->get_data() ) );
	}

	/**
	 * A report keeps what this plugin shows: unknown fields, findings with an
	 * unknown severity and references that are not https are left out.
	 */
	public function test_report_is_normalized() {
		$report                             = self::fixture( 'report.json' );
		$report['extra']                    = 'ignored';
		$report['findings'][0]['extra']     = 'ignored';
		$report['findings'][0]['reference'] = 'http://example.com/record';
		$report['findings'][1]['severity']  = 'urgent';

		$normalized = JP4WC_Security_Scan::normalize_report( $report );

		$this->assertArrayNotHasKey( 'extra', $normalized );
		$this->assertCount( count( $report['findings'] ) - 1, $normalized['findings'] );
		$this->assertArrayNotHasKey( 'extra', $normalized['findings'][0] );
		$this->assertSame( '', $normalized['findings'][0]['reference'] );
		$this->assertSame( $report['next_steps'], $normalized['next_steps'] );
		$this->assertSame( $report['attribution'], $normalized['attribution'] );
		$this->assertNull( JP4WC_Security_Scan::normalize_report( array_merge( $report, array( 'score' => array( 'total' => 50 ) ) ) ) );
	}

	/**
	 * A report stored for another URL is not shown.
	 */
	public function test_report_of_another_site_is_not_shown() {
		update_option(
			JP4WC_Security_Scan::REPORT_OPTION,
			array(
				'report'     => array( 'token' => 'x' ),
				'site_url'   => 'https://production.example.jp',
				'scanned_at' => time(),
			)
		);

		$this->assertNull( $this->dispatch( 'GET' )->get_data()['report'] );
	}
}
