<?php
/**
 * Tests for the security check's requests to the site itself (issue #258).
 *
 * Every request is answered by a `pre_http_request` filter; nothing goes out.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Probe_Test
 */
class JP4WC_Security_Probe_Test extends WP_UnitTestCase {

	/**
	 * Requests made: url and args.
	 *
	 * @var array<int, array{url: string, args: array}>
	 */
	private $requests = array();

	/**
	 * Answer to the next requests: an HTTP code, or a WP_Error.
	 *
	 * @var int|WP_Error
	 */
	private $answer = 200;

	/**
	 * Answer to the request for the home page that starts a run: an HTTP code, or a WP_Error.
	 *
	 * @var int|WP_Error
	 */
	private $home = 200;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Probe' ), 'The plugin loads the probe at plugins_loaded.' );
		JP4WC_Security_Checks::delete_probe_results();
		$this->requests = array();
		$this->answer   = 200;
		$this->home     = 200;
		add_filter( 'pre_http_request', array( $this, 'respond' ), 10, 3 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'respond' ), 10 );
		JP4WC_Security_Checks::delete_probe_results();
		parent::tearDown();
	}

	/**
	 * Answers a request with $answer (pre_http_request).
	 *
	 * @param false|array|WP_Error $pre  Response so far.
	 * @param array                $args Request arguments.
	 * @param string               $url  URL.
	 * @return array|WP_Error
	 */
	public function respond( $pre, $args, $url ) {
		$this->requests[] = array(
			'url'  => $url,
			'args' => $args,
		);
		$answer           = home_url( '/' ) === $url ? $this->home : $this->answer;
		if ( is_wp_error( $answer ) ) {
			return $answer;
		}
		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => $answer,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Classifies a response: 200 fails.
	 *
	 * @param array $response Response.
	 * @return string
	 */
	public static function open_on_200( $response ) {
		return JP4WC_Security_Probe::status_for_code( $response, array( 200 ) );
	}

	/**
	 * A URL on the test site.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private static function own_url( $path ) {
		return untrailingslashit( home_url() ) . $path;
	}

	/**
	 * HTTP codes and the status they give.
	 *
	 * @return array<string, array{int, string}>
	 */
	public function code_provider() {
		return array(
			'open'         => array( 200, 'fail' ),
			'unauthorized' => array( 401, 'pass' ),
			'forbidden'    => array( 403, 'pass' ),
			'not found'    => array( 404, 'pass' ),
			'gone'         => array( 410, 'pass' ),
			'redirect'     => array( 301, 'unknown' ),
			'server error' => array( 500, 'unknown' ),
			'unavailable'  => array( 503, 'unknown' ),
		);
	}

	/**
	 * Each HTTP code gives its status.
	 *
	 * @dataProvider code_provider
	 *
	 * @param int    $code     HTTP code.
	 * @param string $expected Status.
	 */
	public function test_code_gives_a_status( $code, $expected ) {
		$this->answer = $code;
		$probe        = new JP4WC_Security_Probe();

		$this->assertSame( $expected, $probe->status( 'A-04', self::own_url( '/xmlrpc.php' ), array( __CLASS__, 'open_on_200' ) ) );
	}

	/**
	 * A run asks for the home page first, then sends one HEAD request per
	 * check, without following redirects, with a short timeout and a
	 * User-Agent that names the check.
	 */
	public function test_request_is_one_head_without_redirects() {
		$probe = new JP4WC_Security_Probe();
		$probe->status( 'A-04', self::own_url( '/xmlrpc.php' ), array( __CLASS__, 'open_on_200' ) );
		$probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) );

		$this->assertSame( array( home_url( '/' ), self::own_url( '/xmlrpc.php' ), self::own_url( '/wp-content/uploads/' ) ), wp_list_pluck( $this->requests, 'url' ), 'The home page once per run.' );
		foreach ( $this->requests as $request ) {
			$args = $request['args'];
			$this->assertSame( 'HEAD', $args['method'] );
			$this->assertSame( 0, $args['redirection'] );
			$this->assertSame( JP4WC_Security_Probe::TIMEOUT, $args['timeout'] );
			$this->assertFalse( $args['sslverify'], 'As WordPress requests itself.' );
			$this->assertSame( 'JP4WC/' . JP4WC_VERSION . ' (security check)', $args['user-agent'] );
		}
	}

	/**
	 * Home page answers that show the site's own requests are refused.
	 *
	 * @return array<string, array{int|WP_Error}>
	 */
	public function refused_home_provider() {
		return array(
			'authentication' => array( 401 ),
			'challenge'      => array( 403 ),
			'HEAD refused'   => array( 405 ),
			'rate limited'   => array( 429 ),
			'server error'   => array( 500 ),
			'unavailable'    => array( 503 ),
			'no connection'  => array( new WP_Error( 'http_request_failed', 'cURL error 7: Failed to connect' ) ),
		);
	}

	/**
	 * When the home page refuses the site's own request, no check is requested
	 * and every result is unknown: a 403 from a firewall says nothing about the URL (R1-1).
	 *
	 * @dataProvider refused_home_provider
	 *
	 * @param int|WP_Error $home Answer of the home page.
	 */
	public function test_refused_home_page_makes_every_result_unknown( $home ) {
		$this->home   = $home;
		$this->answer = 403;
		$probe        = new JP4WC_Security_Probe();

		$this->assertSame( 'unknown', $probe->status( 'A-02', self::own_url( '/wp-login.php' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertSame( 'unknown', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertSame( array( home_url( '/' ) ), wp_list_pluck( $this->requests, 'url' ) );
	}

	/**
	 * A redirect or a 404 of the home page is a normal answer.
	 */
	public function test_redirected_home_page_is_a_normal_answer() {
		foreach ( array( 301, 302, 404 ) as $home ) {
			JP4WC_Security_Checks::delete_probe_results();
			$this->home   = $home;
			$this->answer = 403;
			$probe        = new JP4WC_Security_Probe();
			$this->assertSame( 'pass', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ), (string) $home );
		}
	}

	/**
	 * A pre_http_request filter that answers with something else gives unknown, not a TypeError (R1-5).
	 */
	public function test_malformed_response_is_unknown() {
		$xmlrpc = self::own_url( '/xmlrpc.php' );
		$junk   = static function ( $response, $args, $url ) use ( $xmlrpc ) {
			return $xmlrpc === $url ? true : $response;
		};
		// After respond(), which answers every request; the home page answers as usual.
		add_filter( 'pre_http_request', $junk, 20, 3 );
		try {
			$probe = new JP4WC_Security_Probe();
			$this->assertSame( 'unknown', $probe->status( 'A-04', $xmlrpc, array( __CLASS__, 'open_on_200' ) ) );
			$this->assertSame( 'unknown', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ), 'Counted as a failed connection.' );
		} finally {
			remove_filter( 'pre_http_request', $junk, 20 );
		}
	}

	/**
	 * A result is used for 12 hours, then requested again.
	 */
	public function test_result_is_kept_for_twelve_hours() {
		$probe = new JP4WC_Security_Probe();
		$this->assertSame( 'fail', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ) );

		$this->answer = 403;
		$probe        = new JP4WC_Security_Probe();
		$this->assertSame( 'fail', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ), 'The stored result is used.' );
		$this->assertCount( 2, $this->requests, 'The home page and the uploads directory, once.' );

		$stored               = get_option( JP4WC_Security_Probe::option_name( 'D-01' ) );
		$stored['checked_at'] = time() - JP4WC_Security_Probe::TTL;
		update_option( JP4WC_Security_Probe::option_name( 'D-01' ), $stored, false );

		$probe = new JP4WC_Security_Probe();
		$this->assertSame( 'pass', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ), 'An old result is requested again.' );
		$this->assertCount( 4, $this->requests );
	}

	/**
	 * A result from the future (a clock set back) is requested again.
	 */
	public function test_result_from_the_future_is_requested_again() {
		update_option(
			JP4WC_Security_Probe::option_name( 'D-01' ),
			array(
				'status'     => 'pass',
				'checked_at' => time() + DAY_IN_SECONDS,
				'url_hash'   => md5( self::own_url( '/wp-content/uploads/' ) ),
			),
			false
		);

		$probe = new JP4WC_Security_Probe();
		$this->assertSame( 'fail', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertCount( 2, $this->requests );
	}

	/**
	 * Each result is kept at once in an option of its own, without autoload,
	 * with a hash of the URL and not the URL itself (A4, B5).
	 */
	public function test_each_result_is_kept_in_its_own_option() {
		global $wpdb;
		$first  = new JP4WC_Security_Probe();
		$second = new JP4WC_Security_Probe();
		$first->status( 'A-04', self::own_url( '/xmlrpc.php' ), array( __CLASS__, 'open_on_200' ) );
		$second->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) );

		foreach ( array(
			'A-04' => '/xmlrpc.php',
			'D-01' => '/wp-content/uploads/',
		) as $check_id => $path ) {
			$option = JP4WC_Security_Probe::option_name( $check_id );
			$stored = get_option( $option );
			$this->assertSame( array( 'status', 'checked_at', 'url_hash' ), array_keys( $stored ), $check_id );
			$this->assertSame( 'fail', $stored['status'] );
			$this->assertSame( md5( self::own_url( $path ) ), $stored['url_hash'] );
			$this->assertStringNotContainsString( 'example.org', wp_json_encode( $stored ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the autoload flag.
			$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $option ) );
			$this->assertContains( $autoload, array( 'no', 'off' ) );
		}
		$this->assertSame( 'jp4wc_security_probe_d_02_4', JP4WC_Security_Probe::option_name( 'D-02-4' ) );
	}

	/**
	 * A stored result for another URL is not used (A4).
	 */
	public function test_changed_url_is_requested_again() {
		$probe = new JP4WC_Security_Probe();
		$probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) );
		$probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) );
		$this->assertCount( 2, $this->requests, 'The home page and the uploads directory, once.' );

		$this->answer = 403;
		$this->assertSame( 'pass', $probe->status( 'D-01', self::own_url( '/files/' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertSame( self::own_url( '/files/' ), end( $this->requests )['url'] );
	}

	/**
	 * Once a request cannot connect, the others of the run are not sent, and
	 * every one of them is unknown.
	 */
	public function test_connection_error_stops_the_other_requests() {
		$this->answer = new WP_Error( 'http_request_failed', 'cURL error 28: Connection timed out' );
		$probe        = new JP4WC_Security_Probe();

		$this->assertSame( 'unknown', $probe->status( 'A-02', self::own_url( '/wp-login.php' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertSame( 'unknown', $probe->status( 'A-04', self::own_url( '/xmlrpc.php' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertSame( array( home_url( '/' ), self::own_url( '/wp-login.php' ) ), wp_list_pluck( $this->requests, 'url' ) );

		$this->assertSame( 'unknown', get_option( JP4WC_Security_Probe::option_name( 'A-02' ) )['status'] );
		$this->assertSame( 'unknown', get_option( JP4WC_Security_Probe::option_name( 'A-04' ) )['status'], 'Kept as unknown, so the next run within 12 hours sends nothing either.' );
	}

	/**
	 * Only this site's host is requested.
	 *
	 * @return array<string, array{string}>
	 */
	public function foreign_url_provider() {
		return array(
			'another host'  => array( 'https://cdn.example.net/wp-content/uploads/' ),
			'not http'      => array( 'ftp://example.org/wp-content/uploads/' ),
			'not a URL'     => array( 'wp-content/uploads/' ),
			'host as path'  => array( 'https://cdn.example.net/example.org/' ),
			'userinfo trap' => array( 'https://example.org@cdn.example.net/' ),
		);
	}

	/**
	 * A URL on another host is not requested and is unknown.
	 *
	 * @dataProvider foreign_url_provider
	 *
	 * @param string $url URL.
	 */
	public function test_other_hosts_are_not_requested( $url ) {
		$probe = new JP4WC_Security_Probe();

		$this->assertSame( 'unknown', $probe->status( 'D-01', $url, array( __CLASS__, 'open_on_200' ) ) );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * A classifier that returns something else gives unknown.
	 */
	public function test_unexpected_classification_is_unknown() {
		$probe = new JP4WC_Security_Probe();

		$this->assertSame(
			'unknown',
			$probe->status(
				'A-05',
				self::own_url( '/wp-json/wp/v2/users' ),
				static function () {
					return 'open';
				}
			)
		);
	}
}
