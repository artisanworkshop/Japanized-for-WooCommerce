<?php
/**
 * Tests for the XML-RPC usage record and guard (issue #293).
 *
 * The guard is exercised through a real wp_xmlrpc_server: its methods after
 * the xmlrpc_methods filter, call() and multiCall() as IXR dispatches them.
 *
 * @package Japanized_For_WooCommerce
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- A stand-in for Jetpack's XML-RPC server follows the test case.

/**
 * JP4WC_XMLRPC_Guard_Test
 */
class JP4WC_XMLRPC_Guard_Test extends WP_UnitTestCase {

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'JP4WC_XMLRPC_Guard' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-xmlrpc-guard.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_XMLRPC_Guard' ) );
		require_once ABSPATH . WPINC . '/class-IXR.php';
		require_once ABSPATH . WPINC . '/class-wp-xmlrpc-server.php';
		unset( $_GET['for'] );
		delete_option( 'wc4jp-disable-xmlrpc' );
		delete_option( 'wc4jp-xmlrpc-allow' );
		JP4WC_XMLRPC_Guard::delete_records();
	}

	/**
	 * Clean up.
	 */
	public function tearDown(): void {
		unset( $_GET['for'] );
		delete_option( 'wc4jp-disable-xmlrpc' );
		delete_option( 'wc4jp-xmlrpc-allow' );
		JP4WC_XMLRPC_Guard::delete_records();
		parent::tearDown();
	}

	/**
	 * Returns a server as xmlrpc.php makes it, with IXR's method table.
	 *
	 * @return wp_xmlrpc_server
	 */
	private function server() {
		$server            = new wp_xmlrpc_server();
		$server->callbacks = $server->methods;
		return $server;
	}

	/**
	 * Records a request to a server.
	 *
	 * @param wp_xmlrpc_server $server  Server.
	 * @param string           $method  Method of the request.
	 * @param array            $params  Parameters.
	 * @param array            $options jetpack, logins, user_agent, now.
	 */
	private function record( $server, $method, array $params = array(), array $options = array() ) {
		$server->message = (object) array(
			'methodName' => $method,
			'params'     => $params,
		);
		// The methods as other plugins left them, before the guard (what filter_methods() keeps).
		remove_filter( 'xmlrpc_methods', array( 'JP4WC_XMLRPC_Guard', 'filter_methods' ), PHP_INT_MAX );
		try {
			$originals = ( new wp_xmlrpc_server() )->methods;
		} finally {
			add_filter( 'xmlrpc_methods', array( 'JP4WC_XMLRPC_Guard', 'filter_methods' ), PHP_INT_MAX );
		}
		JP4WC_XMLRPC_Guard::record_request(
			$server,
			array(
				'jetpack'   => ! empty( $options['jetpack'] ),
				'originals' => $originals,
			),
			isset( $options['logins'] ) ? $options['logins'] : array(
				'ok'     => 0,
				'failed' => 0,
			),
			isset( $options['user_agent'] ) ? $options['user_agent'] : 'Test client',
			isset( $options['now'] ) ? $options['now'] : null
		);
	}

	/**
	 * Returns the recorded entry of a method.
	 *
	 * @param string   $method Method.
	 * @param int|null $now    Time.
	 * @return array|null
	 */
	private function entry( $method, $now = null ) {
		foreach ( (array) JP4WC_XMLRPC_Guard::usage( $now ) as $entry ) {
			if ( $method === $entry['method'] ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * The hooks are registered when the file is loaded.
	 */
	public function test_hooks_are_registered() {
		$this->assertSame( PHP_INT_MAX, has_filter( 'xmlrpc_methods', array( 'JP4WC_XMLRPC_Guard', 'filter_methods' ) ) );
		$this->assertSame( 100, has_filter( 'xmlrpc_enabled', array( 'JP4WC_XMLRPC_Guard', 'filter_enabled' ) ) );
		$this->assertSame( 10, has_filter( 'wp_headers', array( 'JP4WC_XMLRPC_Guard', 'filter_headers' ) ) );
		$this->assertSame( 0, has_action( 'wp_head', array( 'JP4WC_XMLRPC_Guard', 'remove_rsd_link' ) ) );
		$this->assertSame( PHP_INT_MAX, has_filter( 'authenticate', array( 'JP4WC_XMLRPC_Guard', 'note_login' ) ) );
		$this->assertSame( 10, has_action( 'wp_login_failed', array( 'JP4WC_XMLRPC_Guard', 'note_login_failure' ) ) );
		$this->assertSame( 10, has_action( 'shutdown', array( 'JP4WC_XMLRPC_Guard', 'record_current_request' ) ) );
		$this->assertSame( 10, has_action( 'delete_expired_transients', array( 'JP4WC_XMLRPC_Guard', 'delete_expired' ) ) );
	}

	/**
	 * Posted allow lists and how they are saved.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function allow_list_provider() {
		$many = array();
		for ( $i = 1; $i <= 60; $i++ ) {
			$many[] = 'plugin.method' . $i;
		}
		return array(
			'names and a prefix'   => array( "wp.getUsersBlogs\nmetaWeblog.*", "wp.getUsersBlogs\nmetaWeblog.*" ),
			'spaces and commas'    => array( "  wp.getUsersBlogs ,\r\n\twp.getPosts  ", "wp.getUsersBlogs\nwp.getPosts" ),
			'duplicates'           => array( "wp.getPosts\nwp.getPosts\nwp.getPost", "wp.getPosts\nwp.getPost" ),
			'invalid entries'      => array( "*\n**\nwp.*.x\na*b\nwp/getPosts\nx;y\n<b>\nwp.getPosts", 'wp.getPosts' ),
			'case is kept'         => array( 'WP.GetPosts', 'WP.GetPosts' ),
			'empty'                => array( '', '' ),
			'array'                => array( array( 'wp.getPosts' ), '' ),
			'number'               => array( 5, '' ),
			'null'                 => array( null, '' ),
			'more than the limit'  => array( implode( "\n", $many ), implode( "\n", array_slice( $many, 0, 50 ) ) ),
			'underscores and dots' => array( 'my_plugin.do_it', 'my_plugin.do_it' ),
		);
	}

	/**
	 * An allow list keeps its valid entries, one per line, without duplicates.
	 *
	 * @dataProvider allow_list_provider
	 *
	 * @param mixed  $posted Posted value.
	 * @param string $saved  Saved value.
	 */
	public function test_normalize_allow_list( $posted, $saved ) {
		$this->assertSame( $saved, JP4WC_XMLRPC_Guard::normalize_allow_list( $posted ) );
	}

	/**
	 * A method is allowed by its exact name or by a prefix ending in *.
	 */
	public function test_is_allowed() {
		$entries = array( 'wp.getUsersBlogs', 'metaWeblog.*' );
		$this->assertTrue( JP4WC_XMLRPC_Guard::is_allowed( 'wp.getUsersBlogs', $entries ) );
		$this->assertTrue( JP4WC_XMLRPC_Guard::is_allowed( 'metaWeblog.newPost', $entries ) );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_allowed( 'wp.getUsersBlogsX', $entries ), 'Exact name only.' );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_allowed( 'wp.getusersblogs', $entries ), 'Case-sensitive, as IXR is.' );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_allowed( 'metaweblog.newPost', $entries ) );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_allowed( 'wp.getPosts', $entries ) );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_allowed( 'wp.getPosts', array() ) );
		$this->assertSame( array(), JP4WC_XMLRPC_Guard::allow_list() );
		update_option( 'wc4jp-xmlrpc-allow', "wp.getPosts\n<bad>" );
		$this->assertSame( array( 'wp.getPosts' ), JP4WC_XMLRPC_Guard::allow_list(), 'Read through the same normalization.' );
	}

	/**
	 * Off (the default), XML-RPC is left as it is.
	 */
	public function test_off_changes_nothing() {
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_enabled() );
		$server = new wp_xmlrpc_server();
		$this->assertSame( 'this:wp_getUsersBlogs', $server->methods['wp.getUsersBlogs'] );
		$this->assertSame( 'this:pingback_ping', $server->methods['pingback.ping'] );
		$this->assertTrue( apply_filters( 'xmlrpc_enabled', true ) );
		$this->assertArrayHasKey( 'X-Pingback', apply_filters( 'wp_headers', array( 'X-Pingback' => 'https://example.com/xmlrpc.php' ) ) );
	}

	/**
	 * On with nothing allowed, every method is refused and the password login is off.
	 */
	public function test_on_refuses_every_method() {
		update_option( 'wc4jp-disable-xmlrpc', '1' );
		$server = $this->server();
		foreach ( $server->methods as $name => $callback ) {
			$this->assertSame( array( 'JP4WC_XMLRPC_Guard', 'refuse' ), $callback, $name );
		}

		$result = $server->call( 'wp.getUsersBlogs', array( 'admin', 'password' ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertSame( 405, $result->code );
		$result = $server->call( 'pingback.ping', array( 'https://example.com/', home_url( '/' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result, 'No pingback either.' );

		$this->assertFalse( apply_filters( 'xmlrpc_enabled', true ) );
		$this->assertArrayNotHasKey( 'X-Pingback', apply_filters( 'wp_headers', array( 'X-Pingback' => 'https://example.com/xmlrpc.php' ) ) );

		add_action( 'wp_head', 'rsd_link' );
		JP4WC_XMLRPC_Guard::remove_rsd_link();
		$this->assertFalse( has_action( 'wp_head', 'rsd_link' ), 'The discovery link is gone.' );
	}

	/**
	 * On with an allow list, the allowed methods log in as before and the others are refused.
	 */
	public function test_on_keeps_the_allowed_methods() {
		self::factory()->user->create(
			array(
				'user_login' => 'xmlrpc-editor',
				'user_pass'  => 'right-password',
				'role'       => 'editor',
			)
		);
		update_option( 'wc4jp-disable-xmlrpc', '1' );
		update_option( 'wc4jp-xmlrpc-allow', "wp.getUsersBlogs\ndemo.*" );
		$server = $this->server();
		$this->assertSame( 'this:wp_getUsersBlogs', $server->methods['wp.getUsersBlogs'] );
		$this->assertSame( 'this:sayHello', $server->methods['demo.sayHello'] );
		$this->assertSame( array( 'JP4WC_XMLRPC_Guard', 'refuse' ), $server->methods['wp.getPosts'] );
		$this->assertSame( array( 'JP4WC_XMLRPC_Guard', 'refuse' ), $server->methods['pingback.ping'] );

		$this->assertTrue( apply_filters( 'xmlrpc_enabled', true ), 'The allowed methods still log in.' );
		$blogs = $server->call( 'wp.getUsersBlogs', array( 'xmlrpc-editor', 'right-password' ) );
		$this->assertIsArray( $blogs );
		$this->assertCount( 1, $blogs );

		$result = $server->call( 'wp.getPosts', array( 1, 'xmlrpc-editor', 'right-password' ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertSame( 405, $result->code );

		// Each call of a multicall on its own.
		$results = $server->multiCall(
			array(
				array(
					'methodName' => 'wp.getPosts',
					'params'     => array( 1, 'xmlrpc-editor', 'right-password' ),
				),
				array(
					'methodName' => 'demo.sayHello',
					'params'     => array(),
				),
			)
		);
		$this->assertSame( 405, $results[0]['faultCode'] );
		$this->assertSame( array( 'Hello!' ), $results[1] );

		$this->assertArrayHasKey( 'X-Pingback', apply_filters( 'wp_headers', array( 'X-Pingback' => 'https://example.com/xmlrpc.php' ) ), 'Only with nothing allowed.' );
	}

	/**
	 * Only a request Jetpack took over is left alone.
	 */
	public function test_jetpack_requests() {
		$this->assertTrue( JP4WC_XMLRPC_Guard::is_jetpack_request( 'jetpack', false, true ) );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_jetpack_request( 'jetpack', true, true ), 'The password login is still there.' );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_jetpack_request( 'jetpack', false, false ), 'Jetpack does not choose the methods.' );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_jetpack_request( 'Jetpack', false, true ), 'Jetpack compares the value exactly.' );
		$this->assertFalse( JP4WC_XMLRPC_Guard::is_jetpack_request( '', false, true ) );

		update_option( 'wc4jp-disable-xmlrpc', '1' );
		$_GET['for'] = 'jetpack';

		// A request that only adds the parameter is guarded.
		$this->assertFalse( JP4WC_XMLRPC_Guard::jetpack_owns_request() );
		$server = new wp_xmlrpc_server();
		$this->assertSame( array( 'JP4WC_XMLRPC_Guard', 'refuse' ), $server->methods['wp.getUsersBlogs'] );
		$this->assertFalse( apply_filters( 'xmlrpc_enabled', true ) );

		// As Jetpack's connection sets it up: no password login, its server chooses the methods.
		$jetpack = $this->getMockBuilder( 'Jetpack_XMLRPC_Server' )->disableOriginalConstructor()->onlyMethods( array( 'bootstrap_xmlrpc_methods' ) )->getMock();
		$jetpack->method( 'bootstrap_xmlrpc_methods' )->willReturnArgument( 0 );
		$methods = array( $jetpack, 'bootstrap_xmlrpc_methods' );
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
		try {
			$this->assertFalse( JP4WC_XMLRPC_Guard::jetpack_owns_request(), 'Without Jetpack\'s server.' );
			add_filter( 'xmlrpc_methods', $methods );
			$this->assertTrue( JP4WC_XMLRPC_Guard::jetpack_owns_request() );
			$server = new wp_xmlrpc_server();
			$this->assertSame( 'this:wp_getUsersBlogs', $server->methods['wp.getUsersBlogs'], 'Jetpack chooses the methods.' );
			$this->assertTrue( apply_filters( 'xmlrpc_enabled', true ) );

			unset( $_GET['for'] );
			$this->assertFalse( JP4WC_XMLRPC_Guard::jetpack_owns_request(), 'Only for its own requests.' );
		} finally {
			remove_filter( 'xmlrpc_methods', $methods );
			add_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
		}

		add_filter( 'xmlrpc_methods', $methods );
		$_GET['for'] = 'jetpack';
		try {
			$this->assertFalse( JP4WC_XMLRPC_Guard::jetpack_owns_request(), 'Jetpack\'s server, but the password login is back.' );
		} finally {
			remove_filter( 'xmlrpc_methods', $methods );
		}
	}

	/**
	 * Calls are counted per method and result.
	 */
	public function test_record_counts_calls() {
		global $wpdb;
		$server = $this->server();
		$now    = time();
		$this->record( $server, 'wp.getUsersBlogs', array(), array( 'now' => $now ) );
		$this->record(
			$server,
			'wp.getUsersBlogs',
			array(),
			array(
				'now'    => $now,
				'logins' => array(
					'ok'     => 0,
					'failed' => 1,
				),
			)
		);
		$this->record(
			$server,
			'wp.getUsersBlogs',
			array(),
			array(
				'now'    => $now,
				'logins' => array(
					'ok'     => 1,
					'failed' => 0,
				),
			)
		);
		$this->record( $server, 'jetpack.method', array(), array( 'now' => $now ) );
		$this->record(
			$server,
			'wp.getPosts',
			array(),
			array(
				'now'     => $now,
				'jetpack' => true,
			)
		);

		$entry = $this->entry( 'wp.getUsersBlogs', $now );
		$this->assertNotNull( $entry );
		$this->assertSame( 3, $entry['calls'] );
		$this->assertSame(
			array(
				'login'   => 1,
				'failed'  => 1,
				'refused' => 0,
				'jetpack' => 0,
				'open'    => 1,
			),
			$entry['outcomes']
		);
		$this->assertSame( 'core', $entry['provider'] );
		$this->assertSame( 'Test client', $entry['user_agent'] );
		$this->assertSame( $now, $entry['first_at'] );
		$this->assertNull( $this->entry( 'jetpack.method', $now ), 'A method the server does not have is not recorded.' );
		$this->assertSame( 1, $this->entry( 'wp.getPosts', $now )['outcomes']['jetpack'] );

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, autoload FROM {$wpdb->options} WHERE option_name LIKE %s", 'jp4wc\_xmlrpc\_use\_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertCount( 6, $rows, 'An info row and a row per result: 1 + 3 and 1 + 1.' );
		foreach ( $rows as $row ) {
			$this->assertSame( 'off', $row->autoload );
		}
	}

	/**
	 * A multicall counts each call inside it, and a refused method is counted as refused.
	 */
	public function test_record_multicall_and_refused() {
		update_option( 'wc4jp-disable-xmlrpc', '1' );
		update_option( 'wc4jp-xmlrpc-allow', 'demo.*' );
		$server = $this->server();
		$this->record(
			$server,
			'system.multicall',
			array(
				array(
					array( 'methodName' => 'wp.getUsersBlogs' ),
					array( 'methodName' => 'wp.getUsersBlogs' ),
					array( 'methodName' => 'demo.sayHello' ),
					array( 'methodName' => 'system.multicall' ),
					array( 'methodName' => 'no.such.method' ),
					array( 'methodName' => array( 'not a string' ) ),
					'not a call',
				),
			)
		);
		$this->assertSame( 2, $this->entry( 'wp.getUsersBlogs' )['outcomes']['refused'] );
		$this->assertSame( 1, $this->entry( 'demo.sayHello' )['outcomes']['open'] );
		$this->assertNull( $this->entry( 'system.multicall' ), 'The multicall itself is not a method of its own.' );
		$this->assertCount( 2, JP4WC_XMLRPC_Guard::usage() );

		$this->assertSame( array( 'a.b' ), JP4WC_XMLRPC_Guard::requested_methods( 'a.b', array() ) );
		$this->assertSame( array(), JP4WC_XMLRPC_Guard::requested_methods( 'system.multicall', array() ) );
		$this->assertSame( array(), JP4WC_XMLRPC_Guard::requested_methods( 'system.multicall', array( 'x' ) ) );
	}

	/**
	 * A request without a parsed method records nothing.
	 */
	public function test_record_without_a_message() {
		$server = $this->server();
		JP4WC_XMLRPC_Guard::record_request(
			$server,
			array(
				'jetpack'   => false,
				'originals' => array(),
			),
			array(
				'ok'     => 0,
				'failed' => 0,
			),
			''
		);
		$server->message = (object) array( 'params' => array() );
		JP4WC_XMLRPC_Guard::record_request(
			$server,
			array(
				'jetpack'   => false,
				'originals' => array(),
			),
			array(
				'ok'     => 0,
				'failed' => 0,
			),
			''
		);
		$this->assertSame( array(), JP4WC_XMLRPC_Guard::usage() );
	}

	/**
	 * The last call and client are written at most once a minute; the counts every time.
	 */
	public function test_record_touches_the_info_row_once_a_minute() {
		$server = $this->server();
		$now    = time();
		$this->record( $server, 'demo.sayHello', array(), array( 'now' => $now ) );
		$this->record( $server, 'demo.sayHello', array(), array( 'now' => $now + 30 ) );
		$entry = $this->entry( 'demo.sayHello', $now + 30 );
		$this->assertSame( $now, $entry['last_at'] );
		$this->assertSame( 2, $entry['calls'] );

		$this->record(
			$server,
			'demo.sayHello',
			array(),
			array(
				'now'        => $now + 40,
				'user_agent' => 'Other client',
			)
		);
		$this->assertSame( 'Other client', $this->entry( 'demo.sayHello', $now + 40 )['user_agent'], 'Another client is written at once.' );

		$this->record( $server, 'demo.sayHello', array(), array( 'now' => $now + 120 ) );
		$entry = $this->entry( 'demo.sayHello', $now + 120 );
		$this->assertSame( $now + 120, $entry['last_at'] );
		$this->assertSame( $now, $entry['first_at'] );
		$this->assertSame( 4, $entry['calls'] );

		$this->record( $server, 'demo.sayHello', array(), array( 'user_agent' => str_repeat( 'あ', 300 ) ) );
		$this->assertSame( 200, mb_strlen( $this->entry( 'demo.sayHello' )['user_agent'] ), 'The client is cut to 200 characters.' );
	}

	/**
	 * No more than METHOD_MAX methods are recorded; the recorded ones keep counting.
	 */
	public function test_record_limits_the_number_of_methods() {
		global $wpdb;
		$server = $this->server();
		$this->record( $server, 'demo.sayHello' );
		for ( $i = 1; $i < JP4WC_XMLRPC_Guard::METHOD_MAX; $i++ ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->options,
				array(
					'option_name'  => 'jp4wc_xmlrpc_use_' . md5( 'filler' . $i ) . '_info',
					'option_value' => maybe_serialize(
						array(
							'method'  => 'filler' . $i,
							'last_at' => time(),
						)
					),
					'autoload'     => 'off',
				)
			);
		}
		$this->record( $server, 'demo.addTwoNumbers' );
		$this->assertNull( $this->entry( 'demo.addTwoNumbers' ) );
		$this->record( $server, 'demo.sayHello' );
		$this->assertSame( 2, $this->entry( 'demo.sayHello' )['calls'] );
	}

	/**
	 * Methods unused for 30 days are removed once a day and not shown before.
	 */
	public function test_old_methods_expire() {
		global $wpdb;
		$server = $this->server();
		$now    = time();
		$this->record( $server, 'demo.sayHello', array(), array( 'now' => $now - JP4WC_XMLRPC_Guard::RETENTION ) );
		$this->record( $server, 'demo.addTwoNumbers', array(), array( 'now' => $now - JP4WC_XMLRPC_Guard::RETENTION + 60 ) );
		$this->assertNull( $this->entry( 'demo.sayHello', $now ), 'Not shown once expired.' );
		$this->assertNotNull( $this->entry( 'demo.addTwoNumbers', $now ) );

		JP4WC_XMLRPC_Guard::delete_expired( null, $now );
		$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'jp4wc\_xmlrpc\_use\_%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertCount( 2, $names, 'The info row and the count of the recent method.' );
		foreach ( $names as $name ) {
			$this->assertStringContainsString( md5( 'demo.addTwoNumbers' ), $name );
		}
	}

	/**
	 * A record that cannot be read is reported as such, not as empty.
	 */
	public function test_unreadable_record() {
		global $wpdb;
		$break    = static function ( $query ) {
			return false !== strpos( $query, 'jp4wc\\\\_xmlrpc\\\\_use' ) ? 'SELECT * FROM jp4wc_no_such_table' : $query;
		};
		$suppress = $wpdb->suppress_errors( true );
		add_filter( 'query', $break );
		try {
			$this->assertNull( JP4WC_XMLRPC_Guard::usage() );
			$this->assertFalse( JP4WC_XMLRPC_Guard::view()['readable'] );
		} finally {
			remove_filter( 'query', $break );
			$wpdb->suppress_errors( $suppress );
		}
		$this->assertTrue( JP4WC_XMLRPC_Guard::view()['readable'] );
	}

	/**
	 * Who added a method is told from the file of its callback.
	 */
	public function test_provider() {
		$directories = array(
			'mu-plugin' => array( '/wp/wp-content/mu-plugins' ),
			'plugin'    => array( '/wp/wp-content/plugins/' ),
			'theme'     => array( '/wp/wp-content/themes' ),
			'core'      => array( '/wp/wp-includes', '/wp/wp-admin' ),
		);
		$this->assertSame( 'plugin:jetpack', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-content/plugins/jetpack/src/class-x.php', $directories ) );
		$this->assertSame( 'plugin:hello.php', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-content/plugins/hello.php', $directories ) );
		$this->assertSame( 'mu-plugin:loader.php', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-content/mu-plugins/loader.php', $directories ) );
		$this->assertSame( 'theme:twentytwentyfive', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-content/themes/twentytwentyfive/functions.php', $directories ) );
		$this->assertSame( 'core', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-includes/class-wp-xmlrpc-server.php', $directories ) );
		$this->assertSame( 'core', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-admin/includes/x.php', $directories ) );
		$this->assertSame( '', JP4WC_XMLRPC_Guard::provider_for_file( '/wp/wp-content/plugins-old/x.php', $directories ), 'A directory, not a prefix.' );
		$this->assertSame( '', JP4WC_XMLRPC_Guard::provider_for_file( '/elsewhere/x.php', $directories ) );

		$this->assertSame( 'core', JP4WC_XMLRPC_Guard::provider_of( 'this:wp_getUsersBlogs', 'wp_xmlrpc_server' ) );
		$this->assertSame( 'core', JP4WC_XMLRPC_Guard::provider_of( 'this:multiCall', 'wp_xmlrpc_server' ), 'Inherited from IXR.' );
		$this->assertSame( 'core', JP4WC_XMLRPC_Guard::provider_of( 'wp_authenticate', 'wp_xmlrpc_server' ) );
		$this->assertSame( '', JP4WC_XMLRPC_Guard::provider_of( 'this:no_such_method', 'wp_xmlrpc_server' ) );
		$this->assertSame( '', JP4WC_XMLRPC_Guard::provider_of( 'no_such_function', 'wp_xmlrpc_server' ) );
		$this->assertSame( '', JP4WC_XMLRPC_Guard::provider_of( array( new stdClass(), 'missing' ), 'wp_xmlrpc_server' ) );
		$this->assertSame( '', JP4WC_XMLRPC_Guard::provider_of( 42, 'wp_xmlrpc_server' ) );

		$this->assertSame(
			array(
				'type' => 'core',
				'name' => 'WordPress',
			),
			JP4WC_XMLRPC_Guard::describe_provider( 'core' )
		);
		$this->assertSame(
			array(
				'type' => 'plugin',
				'name' => 'no-such-plugin',
			),
			JP4WC_XMLRPC_Guard::describe_provider( 'plugin:no-such-plugin' ),
			'A plugin no longer installed keeps its directory name.'
		);
		$this->assertSame( 'theme', JP4WC_XMLRPC_Guard::describe_provider( 'theme:no-such-theme' )['type'] );
		$this->assertSame( 'unknown', JP4WC_XMLRPC_Guard::describe_provider( '' )['type'] );
		$this->assertSame( 'unknown', JP4WC_XMLRPC_Guard::describe_provider( 'bogus:x' )['type'] );
		$this->assertSame( 'unknown', JP4WC_XMLRPC_Guard::describe_provider( 'plugin:' )['type'] );
	}

	/**
	 * The view has the setting, the allow list and the record, newest first.
	 */
	public function test_view() {
		$view = JP4WC_XMLRPC_Guard::view();
		$this->assertFalse( $view['enabled'] );
		$this->assertSame( array(), $view['allow'] );
		$this->assertTrue( $view['readable'] );
		$this->assertSame( array(), $view['usage'] );

		update_option( 'wc4jp-disable-xmlrpc', '1' );
		update_option( 'wc4jp-xmlrpc-allow', 'demo.*' );
		$server = $this->server();
		// IXR adds its own methods after the filter.
		$server->callbacks['system.listMethods'] = 'this:listMethods';
		$this->record( $server, 'system.listMethods', array(), array( 'now' => time() - 200 ) );
		$this->record( $server, 'demo.sayHello', array(), array( 'now' => time() - 100 ) );
		$this->record( $server, 'wp.getUsersBlogs' );
		$view = JP4WC_XMLRPC_Guard::view();
		$this->assertTrue( $view['enabled'] );
		$this->assertSame( array( 'demo.*' ), $view['allow'] );
		$this->assertSame( array( 'wp.getUsersBlogs', 'demo.sayHello', 'system.listMethods' ), wp_list_pluck( $view['usage'], 'method' ) );
		$this->assertSame( array( false, true, false ), wp_list_pluck( $view['usage'], 'allowed' ) );
		$this->assertSame( array( true, true, false ), wp_list_pluck( $view['usage'], 'refusable' ), 'The guard cannot refuse what IXR adds.' );
		$this->assertSame( 'core', $view['usage'][2]['provider']['type'] );
		$this->assertSame(
			array(
				'type' => 'core',
				'name' => 'WordPress',
			),
			$view['usage'][0]['provider']
		);
	}

	/**
	 * Uninstalling removes the settings and the record.
	 */
	public function test_uninstall() {
		global $wpdb;
		update_option( 'wc4jp-disable-xmlrpc', '1' );
		update_option( 'wc4jp-xmlrpc-allow', 'demo.*' );
		$this->record( $this->server(), 'demo.sayHello' );
		JP4WC_XMLRPC_Guard::uninstall();
		$this->assertFalse( get_option( 'wc4jp-disable-xmlrpc' ) );
		$this->assertFalse( get_option( 'wc4jp-xmlrpc-allow' ) );
		$this->assertSame( '0', $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", 'jp4wc\_xmlrpc\_use\_%' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

if ( ! class_exists( 'Jetpack_XMLRPC_Server' ) ) {
	/**
	 * Stand-in for Jetpack's XML-RPC server where no plugin bundles Jetpack's connection.
	 */
	class Jetpack_XMLRPC_Server {

		/**
		 * Returns the methods (Jetpack offers its own here).
		 *
		 * @param array $methods Methods.
		 * @return array
		 */
		public function bootstrap_xmlrpc_methods( $methods ) {
			return $methods;
		}
	}
}
