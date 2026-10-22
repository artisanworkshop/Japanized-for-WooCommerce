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
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Probe' ), 'The plugin loads the probe at plugins_loaded.' );
		delete_option( JP4WC_Security_Probe::OPTION );
		$this->requests = array();
		add_filter( 'pre_http_request', array( $this, 'respond' ), 10, 3 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'respond' ), 10 );
		delete_option( JP4WC_Security_Probe::OPTION );
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
		if ( is_wp_error( $this->answer ) ) {
			return $this->answer;
		}
		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => $this->answer,
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
	 * One HEAD request, without following redirects, with a short timeout and
	 * a User-Agent that names the check.
	 */
	public function test_request_is_one_head_without_redirects() {
		$probe = new JP4WC_Security_Probe();
		$probe->status( 'A-04', self::own_url( '/xmlrpc.php' ), array( __CLASS__, 'open_on_200' ) );

		$this->assertCount( 1, $this->requests );
		$args = $this->requests[0]['args'];
		$this->assertSame( 'HEAD', $args['method'] );
		$this->assertSame( 0, $args['redirection'] );
		$this->assertSame( JP4WC_Security_Probe::TIMEOUT, $args['timeout'] );
		$this->assertFalse( $args['sslverify'], 'As WordPress requests itself.' );
		$this->assertSame( 'JP4WC/' . JP4WC_VERSION . ' (security check)', $args['user-agent'] );
	}

	/**
	 * A result is used for 12 hours, then requested again.
	 */
	public function test_result_is_kept_for_twelve_hours() {
		$probe = new JP4WC_Security_Probe();
		$this->assertSame( 'fail', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ) );
		$probe->save();

		$this->answer = 403;
		$probe        = new JP4WC_Security_Probe();
		$this->assertSame( 'fail', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ), 'The stored result is used.' );
		$this->assertCount( 1, $this->requests );

		$stored                       = get_option( JP4WC_Security_Probe::OPTION );
		$stored['D-01']['checked_at'] = time() - JP4WC_Security_Probe::TTL;
		update_option( JP4WC_Security_Probe::OPTION, $stored, false );

		$probe = new JP4WC_Security_Probe();
		$this->assertSame( 'pass', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ), 'An old result is requested again.' );
		$this->assertCount( 2, $this->requests );
	}

	/**
	 * A result from the future (a clock set back) is requested again.
	 */
	public function test_result_from_the_future_is_requested_again() {
		update_option(
			JP4WC_Security_Probe::OPTION,
			array(
				'D-01' => array(
					'status'     => 'pass',
					'checked_at' => time() + DAY_IN_SECONDS,
				),
			),
			false
		);

		$probe = new JP4WC_Security_Probe();
		$this->assertSame( 'fail', $probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) ) );
		$this->assertCount( 1, $this->requests );
	}

	/**
	 * Results are saved once, without autoload, and hold only the status and the time.
	 */
	public function test_results_are_saved_once_without_the_url() {
		global $wpdb;
		$writes = 0;
		$count  = static function ( $value ) use ( &$writes ) {
			++$writes;
			return $value;
		};
		add_filter( 'pre_update_option_' . JP4WC_Security_Probe::OPTION, $count );
		try {
			$probe = new JP4WC_Security_Probe();
			$probe->status( 'A-04', self::own_url( '/xmlrpc.php' ), array( __CLASS__, 'open_on_200' ) );
			$probe->status( 'D-01', self::own_url( '/wp-content/uploads/' ), array( __CLASS__, 'open_on_200' ) );
			$this->assertFalse( get_option( JP4WC_Security_Probe::OPTION ), 'Nothing is written before save().' );
			$probe->save();
			$probe->save();
		} finally {
			remove_filter( 'pre_update_option_' . JP4WC_Security_Probe::OPTION, $count );
		}

		$this->assertSame( 1, $writes );
		$stored = get_option( JP4WC_Security_Probe::OPTION );
		$this->assertSame( array( 'A-04', 'D-01' ), array_keys( $stored ) );
		foreach ( $stored as $result ) {
			$this->assertSame( array( 'status', 'checked_at' ), array_keys( $result ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the autoload flag.
		$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", JP4WC_Security_Probe::OPTION ) );
		$this->assertContains( $autoload, array( 'no', 'off' ) );
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
		$this->assertCount( 1, $this->requests );

		$probe->save();
		$stored = get_option( JP4WC_Security_Probe::OPTION );
		$this->assertSame( 'unknown', $stored['A-02']['status'] );
		$this->assertSame( 'unknown', $stored['A-04']['status'], 'Kept as unknown, so the next run within 12 hours sends nothing either.' );
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
