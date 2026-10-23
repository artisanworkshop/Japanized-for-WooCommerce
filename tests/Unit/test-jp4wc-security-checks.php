<?php
/**
 * Tests for the local checks of the security check (issue #258).
 *
 * Requests to the site itself are answered by a `pre_http_request` filter,
 * and the files are written to a temporary directory that the checks are
 * pointed at through their context.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Checks_Test
 */
class JP4WC_Security_Checks_Test extends WP_UnitTestCase {

	/**
	 * Temporary directory of the test.
	 *
	 * @var string
	 */
	private $dir = '';

	/**
	 * Answers by URL: HTTP code and headers. Other URLs get a connection error.
	 *
	 * @var array<string, array{int, array}>
	 */
	private $answers = array();

	/**
	 * URLs requested.
	 *
	 * @var string[]
	 */
	private $requested = array();

	/**
	 * Checks that threw, by ID: a test that does not expect one fails (R2-4).
	 *
	 * @var array<string, Throwable>
	 */
	private $errors = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Checks' ), 'The plugin loads the local checks at plugins_loaded.' );

		$this->dir = untrailingslashit( get_temp_dir() ) . '/jp4wc-security-checks-' . wp_generate_password( 8, false );
		$this->assertTrue( wp_mkdir_p( $this->dir ) );
		// The home page answers as usual, so the requests of the checks are sent (R1-1).
		$this->answers   = array( home_url( '/' ) => array( 200, array() ) );
		$this->requested = array();
		delete_option( JP4WC_Security_Probe::OPTION );
		add_filter( 'pre_http_request', array( $this, 'respond' ), 10, 3 );
		// phpunit.xml turns warnings into exceptions, which the checks report as unknown.
		$this->errors = array();
		add_action( 'jp4wc_security_check_error', array( $this, 'record_error' ), 10, 2 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_action( 'jp4wc_security_check_error', array( $this, 'record_error' ), 10 );
		$this->assertSame( array(), array_map( 'get_class', $this->errors ), 'No check threw.' );
		remove_filter( 'pre_http_request', array( $this, 'respond' ), 10 );
		delete_option( JP4WC_Security_Probe::OPTION );
		self::remove( $this->dir );
		parent::tearDown();
	}

	/**
	 * Answers a request from $answers (pre_http_request).
	 *
	 * @param false|array|WP_Error $pre  Response so far.
	 * @param array                $args Request arguments.
	 * @param string               $url  URL.
	 * @return array|WP_Error
	 */
	public function respond( $pre, $args, $url ) {
		$this->requested[] = $url;
		if ( ! isset( $this->answers[ $url ] ) ) {
			return new WP_Error( 'http_request_failed', 'No answer in this test.' );
		}
		return array(
			'headers'  => $this->answers[ $url ][1],
			'body'     => '',
			'response' => array(
				'code'    => $this->answers[ $url ][0],
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Records a check that threw (jp4wc_security_check_error).
	 *
	 * @param string    $check_id Check ID.
	 * @param Throwable $error    What it threw.
	 */
	public function record_error( $check_id, $error ) {
		$this->errors[ $check_id ] = $error;
	}

	/**
	 * Runs one check with a fresh probe, and returns its status.
	 *
	 * @param string $check_id Check ID.
	 * @param array  $context  Context.
	 * @return string
	 */
	private function status( $check_id, array $context = array() ) {
		delete_option( JP4WC_Security_Probe::OPTION );
		$result = JP4WC_Security_Checks::run_check( $check_id, $context );
		$this->assertSame( $check_id, $result['check_id'] );
		return $result['status'];
	}

	/**
	 * Writes a file under the temporary directory.
	 *
	 * @param string $path     Path relative to the temporary directory.
	 * @param string $contents Contents.
	 * @return string Absolute path.
	 */
	private function write( $path, $contents = '' ) {
		$file = $this->dir . '/' . $path;
		wp_mkdir_p( dirname( $file ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture in a temporary directory.
		file_put_contents( $file, $contents );
		return $file;
	}

	/**
	 * Removes a directory and everything in it.
	 *
	 * @param string $path Directory.
	 */
	private static function remove( $path ) {
		if ( '' === $path || ( ! file_exists( $path ) && ! is_link( $path ) ) ) {
			return;
		}
		if ( is_link( $path ) || is_file( $path ) ) {
			unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test clean-up; wp_delete_file() does not remove links to directories.
			return;
		}
		foreach ( array_diff( scandir( $path ), array( '.', '..' ) ) as $name ) {
			self::remove( $path . '/' . $name );
		}
		rmdir( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test clean-up.
	}

	/**
	 * The uploads directory of the test.
	 *
	 * @return array{basedir: string, baseurl: string}
	 */
	private function uploads() {
		wp_mkdir_p( $this->dir . '/uploads' );
		return array(
			'basedir' => $this->dir . '/uploads',
			'baseurl' => untrailingslashit( home_url() ) . '/wp-content/uploads',
		);
	}

	/**
	 * Every check runs in order, with its requirement, and the probe is saved once.
	 */
	public function test_run_returns_every_check_in_order() {
		$writes = 0;
		$count  = static function ( $value ) use ( &$writes ) {
			++$writes;
			return $value;
		};
		add_filter( 'pre_update_option_' . JP4WC_Security_Probe::OPTION, $count );
		try {
			$checks = JP4WC_Security_Checks::run( array( 'uploads' => $this->uploads() ) );
		} finally {
			remove_filter( 'pre_update_option_' . JP4WC_Security_Probe::OPTION, $count );
		}

		$this->assertSame( array( 'V-01', 'V-02', 'V-03', 'V-04', 'A-01', 'A-02', 'A-03', 'A-04', 'A-05', 'D-01', 'D-02', 'D-03', 'D-04', 'D-05', 'M-01', 'M-02', 'M-03' ), wp_list_pluck( $checks, 'check_id' ) );
		$prefixes = array(
			'V' => 'vuln',
			'A' => 'admin',
			'D' => 'data',
			'M' => 'malware',
		);
		foreach ( $checks as $check ) {
			$this->assertSame( $prefixes[ $check['check_id'][0] ], $check['requirement'], $check['check_id'] );
			$this->assertContains( $check['status'], array( 'pass', 'fail', 'unknown' ), $check['check_id'] );
		}
		$this->assertSame( 1, $writes );
	}

	/**
	 * A check that throws is unknown, and the other checks still run (R1-5).
	 */
	public function test_failing_check_is_unknown() {
		$throw = static function () {
			throw new RuntimeException( 'A broken filter.' );
		};
		add_filter( 'jp4wc_security_known_plugins', $throw );
		try {
			$checks = JP4WC_Security_Checks::run(
				array(
					'uploads'   => $this->uploads(),
					'constants' => array(
						'DISALLOW_FILE_EDIT' => true,
						'DISALLOW_FILE_MODS' => null,
					),
				)
			);
		} finally {
			remove_filter( 'jp4wc_security_known_plugins', $throw );
		}

		$this->assertSame( array( 'A-02', 'A-03', 'M-02' ), array_keys( $this->errors ), 'Each check that threw is reported.' );
		$this->assertInstanceOf( RuntimeException::class, $this->errors['A-03'] );
		$this->errors = array();

		$statuses = wp_list_pluck( $checks, 'status', 'check_id' );
		$this->assertCount( count( JP4WC_Security_Checks::CHECKS ), $statuses );
		$this->assertSame( 'unknown', $statuses['A-03'] );
		$this->assertSame( 'unknown', $statuses['M-02'] );
		$this->assertSame( 'pass', $statuses['M-01'], 'The checks after it still run.' );
	}

	/**
	 * An unknown check ID gives null.
	 */
	public function test_unknown_check_id_is_null() {
		$this->assertNull( JP4WC_Security_Checks::run_check( 'X-01' ) );
		$this->assertNull( JP4WC_Security_Checks::run_check( array( 'V-01' ) ) );
	}

	/**
	 * A matched item with records of the given ratings.
	 *
	 * @param string[] $ratings Ratings.
	 * @return array
	 */
	private static function item( array $ratings ) {
		$vulnerabilities = array();
		foreach ( $ratings as $rating ) {
			$vulnerabilities[] = array( 'rating' => $rating );
		}
		return array( 'vulnerabilities' => $vulnerabilities );
	}

	/**
	 * V-01 status and severity.
	 *
	 * @return array<string, array{array|null, string, string}>
	 */
	public function vulnerability_provider() {
		return array(
			'no data'             => array( null, 'unknown', 'high' ),
			'nothing found'       => array(
				array(
					'items' => array(),
					'stale' => false,
				),
				'pass',
				'high',
			),
			'nothing in old data' => array(
				array(
					'items' => array(),
					'stale' => true,
				),
				'unknown',
				'high',
			),
			'critical'            => array(
				array(
					'items' => array( self::item( array( 'Medium' ) ), self::item( array( 'Critical', 'Low' ) ) ),
					'stale' => true,
				),
				'fail',
				'critical',
			),
			'high'                => array(
				array(
					'items' => array( self::item( array( 'High' ) ) ),
					'stale' => false,
				),
				'fail',
				'high',
			),
			'medium'              => array(
				array(
					'items' => array( self::item( array( 'Medium', 'Low' ) ) ),
					'stale' => false,
				),
				'fail',
				'medium',
			),
			'no CVSS rating'      => array(
				array(
					'items' => array( self::item( array( '' ) ) ),
					'stale' => false,
				),
				'fail',
				'medium',
			),
			'low'                 => array(
				array(
					'items' => array( self::item( array( 'Low' ) ) ),
					'stale' => false,
				),
				'fail',
				'low',
			),
			'none'                => array(
				array(
					'items' => array( self::item( array( 'None' ) ) ),
					'stale' => false,
				),
				'fail',
				'low',
			),
		);
	}

	/**
	 * V-01 fails on any match and takes the severity of the most severe record.
	 *
	 * @dataProvider vulnerability_provider
	 *
	 * @param array|null $vulnerabilities Context value.
	 * @param string     $status          Expected status.
	 * @param string     $severity        Expected severity.
	 */
	public function test_v01_known_vulnerabilities( $vulnerabilities, $status, $severity ) {
		$result = JP4WC_Security_Checks::run_check( 'V-01', array( 'vulnerabilities' => $vulnerabilities ) );

		$this->assertSame( $status, $result['status'] );
		$this->assertSame( $severity, $result['severity'] );
		$this->assertSame( 'vuln', $result['requirement'] );
	}

	/**
	 * V-02 fails for a plugin with an update whose file is older than 180 days.
	 */
	public function test_v02_neglected_updates() {
		$old    = $this->write( 'plugins/old-plugin/old-plugin.php', '<?php' );
		$recent = $this->write( 'plugins/recent-plugin/recent-plugin.php', '<?php' );
		touch( $old, time() - JP4WC_Security_Checks::NEGLECTED_AFTER - DAY_IN_SECONDS ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture.
		touch( $recent, time() - 30 * DAY_IN_SECONDS ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture.

		$updates = static function ( array $files ) {
			$transient               = new stdClass();
			$transient->last_checked = time();
			$transient->response     = array_fill_keys( $files, (object) array( 'new_version' => '9.9' ) );
			return $transient;
		};
		$context = array( 'plugin_dir' => $this->dir . '/plugins' );

		$this->assertSame( 'fail', $this->status( 'V-02', $context + array( 'plugin_updates' => $updates( array( 'recent-plugin/recent-plugin.php', 'old-plugin/old-plugin.php' ) ) ) ) );
		$this->assertSame( 'pass', $this->status( 'V-02', $context + array( 'plugin_updates' => $updates( array( 'recent-plugin/recent-plugin.php' ) ) ) ), 'Updated within 180 days.' );
		$this->assertSame( 'pass', $this->status( 'V-02', $context + array( 'plugin_updates' => $updates( array() ) ) ), 'No update.' );
		$this->assertSame( 'pass', $this->status( 'V-02', $context + array( 'plugin_updates' => $updates( array( 'gone/gone.php' ) ) ) ), 'A plugin that is no longer there.' );
		$this->assertSame( 'pass', $this->status( 'V-02', $context + array( 'plugin_updates' => $updates( array( '../plugins/old-plugin/old-plugin.php' ) ) ) ), 'A path out of the plugins directory is ignored.' );
		$this->assertSame( 'unknown', $this->status( 'V-02', $context + array( 'plugin_updates' => false ) ), 'WordPress has not looked for updates.' );
	}

	/**
	 * V-02 reads the update_plugins transient of the site.
	 */
	public function test_v02_reads_the_update_transient() {
		$filter = static function () {
			return false;
		};
		add_filter( 'pre_site_transient_update_plugins', $filter );
		try {
			$this->assertSame( 'unknown', $this->status( 'V-02' ) );
		} finally {
			remove_filter( 'pre_site_transient_update_plugins', $filter );
		}
	}

	/**
	 * V-03 status by running and oldest supported versions.
	 *
	 * @return array<string, array{array, array, string}>
	 */
	public function end_of_life_provider() {
		$eol = array(
			'php'         => '8.2',
			'wordpress'   => '6.7',
			'woocommerce' => '10.2',
		);
		return array(
			'all supported'              => array(
				array(
					'php'         => '8.3.12',
					'wordpress'   => '6.9.1',
					'woocommerce' => '10.2.0',
				),
				$eol,
				'pass',
			),
			'old PHP'                    => array(
				array(
					'php'         => '8.1.30',
					'wordpress'   => '6.9.1',
					'woocommerce' => '10.6.2',
				),
				$eol,
				'fail',
			),
			'old WordPress'              => array(
				array(
					'php'         => '8.3.12',
					'wordpress'   => '6.6.2',
					'woocommerce' => '10.6.2',
				),
				$eol,
				'fail',
			),
			'old WooCommerce'            => array(
				array(
					'php'         => '8.3.12',
					'wordpress'   => '6.9.1',
					'woocommerce' => '10.1.9',
				),
				$eol,
				'fail',
			),
			'WooCommerce not active'     => array(
				array(
					'php'         => '8.3.12',
					'wordpress'   => '6.9.1',
					'woocommerce' => null,
				),
				array(
					'php'       => '8.2',
					'wordpress' => '6.7',
				),
				'pass',
			),
			'no data'                    => array(
				array(
					'php'         => '8.3.12',
					'wordpress'   => '6.9.1',
					'woocommerce' => '10.6.2',
				),
				array(),
				'unknown',
			),
			'one version missing'        => array(
				array(
					'php'         => '8.3.12',
					'wordpress'   => '6.9.1',
					'woocommerce' => '10.6.2',
				),
				array(
					'php'       => '8.2',
					'wordpress' => '6.7',
				),
				'unknown',
			),
			'an old version beats a gap' => array(
				array(
					'php'         => '8.1.30',
					'wordpress'   => '6.9.1',
					'woocommerce' => '10.6.2',
				),
				array( 'php' => '8.2' ),
				'fail',
			),
		);
	}

	/**
	 * V-03 compares each version with the oldest supported one of the feed.
	 *
	 * @dataProvider end_of_life_provider
	 *
	 * @param array  $versions Running versions.
	 * @param array  $eol      Oldest supported versions.
	 * @param string $expected Status.
	 */
	public function test_v03_end_of_life( $versions, $eol, $expected ) {
		$this->assertSame(
			$expected,
			$this->status(
				'V-03',
				array(
					'versions' => $versions,
					'eol'      => $eol,
				)
			)
		);
	}

	/**
	 * V-03 reads the versions of the site.
	 */
	public function test_v03_reads_the_running_versions() {
		$eol = array(
			'php'         => '1.0',
			'wordpress'   => '1.0',
			'woocommerce' => '1.0',
		);
		$this->assertSame( 'pass', $this->status( 'V-03', array( 'eol' => $eol ) ) );

		$eol['wordpress'] = '999.0';
		$this->assertSame( 'fail', $this->status( 'V-03', array( 'eol' => $eol ) ) );
	}

	/**
	 * V-04 fails when plugin or minor core automatic updates are off.
	 */
	public function test_v04_automatic_updates() {
		$this->assertSame( 'fail', $this->status( 'V-04' ), 'The WordPress test library turns every automatic update off.' );

		// As on a site: the test library's automatic_updater_disabled filter removed.
		$this->assertSame( 10, has_filter( 'automatic_updater_disabled', '__return_true' ) );
		remove_filter( 'automatic_updater_disabled', '__return_true' );
		try {
			$this->assertSame( 'pass', $this->status( 'V-04' ) );
			$this->assertSame( 'pass', $this->status( 'V-04', array( 'constants' => array( 'WP_AUTO_UPDATE_CORE' => 'minor' ) ) ) );
			$this->assertSame( 'fail', $this->status( 'V-04', array( 'constants' => array( 'WP_AUTO_UPDATE_CORE' => false ) ) ) );

			foreach ( array( 'plugins_auto_update_enabled', 'allow_minor_auto_core_updates' ) as $filter ) {
				add_filter( $filter, '__return_false' );
				try {
					$this->assertSame( 'fail', $this->status( 'V-04' ), $filter );
				} finally {
					remove_filter( $filter, '__return_false' );
				}
			}
		} finally {
			add_filter( 'automatic_updater_disabled', '__return_true' );
		}
	}

	/**
	 * A-01 fails while a user is named admin.
	 */
	public function test_a01_admin_username() {
		global $wpdb;
		$this->assertNotFalse( username_exists( 'admin' ), 'The test site is installed with a user named admin.' );
		$this->assertSame( 'fail', $this->status( 'A-01' ) );

		$user_id = username_exists( 'admin' );
		$wpdb->update( $wpdb->users, array( 'user_login' => 'shop-owner' ), array( 'ID' => $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A user name cannot be changed through the API.
		wp_cache_delete( 'admin', 'userlogins' );
		clean_user_cache( $user_id );
		try {
			$this->assertSame( 'pass', $this->status( 'A-01' ) );
		} finally {
			$wpdb->update( $wpdb->users, array( 'user_login' => 'admin' ), array( 'ID' => $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Restores the user name.
			wp_cache_delete( 'shop-owner', 'userlogins' );
			clean_user_cache( $user_id );
		}
	}

	/**
	 * The login page URL the checks request.
	 *
	 * @return string
	 */
	private static function login_url() {
		return untrailingslashit( get_option( 'siteurl' ) ) . '/wp-login.php';
	}

	/**
	 * A-02 passes with a login protection plugin, without a request.
	 */
	public function test_a02_login_protection_plugin() {
		$context = array(
			'abspath'        => $this->dir,
			'active_plugins' => array( 'akismet', 'wps-hide-login' ),
		);
		$this->assertSame( 'pass', $this->status( 'A-02', $context ) );
		$this->assertSame( array(), $this->requested );

		$context['active_plugins']          = array( 'my-login-guard' );
		$this->answers[ self::login_url() ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'A-02', $context ) );

		$add = static function ( $slugs, $category ) {
			if ( 'login' === $category ) {
				$slugs[] = 'my-login-guard';
			}
			return $slugs;
		};
		add_filter( 'jp4wc_security_known_plugins', $add, 10, 2 );
		try {
			$this->assertSame( 'pass', $this->status( 'A-02', $context ), 'The filter adds a plugin.' );
		} finally {
			remove_filter( 'jp4wc_security_known_plugins', $add, 10 );
		}
	}

	/**
	 * .htaccess rules that restrict the login page.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function htaccess_provider() {
		return array(
			'Files with Require ip'           => array( '.htaccess', "# BEGIN WordPress\n<Files wp-login.php>\n\tRequire ip 192.0.2.1\n</Files>\n", 'pass' ),
			'FilesMatch with Deny from'       => array( '.htaccess', "<FilesMatch \"^(wp-login|xmlrpc)\\.php$\">\nOrder Deny,Allow\nDeny from all\nAllow from 192.0.2.1\n</FilesMatch>\n", 'pass' ),
			'Files with basic authentication' => array( '.htaccess', "<Files wp-login.php>\nAuthType Basic\nAuthName \"Login\"\nAuthUserFile /home/example/.htpasswd\nRequire valid-user\n</Files>\n", 'pass' ),
			'rewrite on the client address'   => array( '.htaccess', "RewriteEngine On\nRewriteCond %{REMOTE_ADDR} !^192\\.0\\.2\\.1$\nRewriteRule ^wp-login\\.php$ - [F,L]\n", 'pass' ),
			'rewrite without the address'     => array( '.htaccess', "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^wp-login\\.php$ /index.php [L]\n", 'fail' ),
			'commented out'                   => array( '.htaccess', "#<Files wp-login.php>\n#\tRequire ip 192.0.2.1\n#</Files>\n", 'fail' ),
			'another file restricted'         => array( '.htaccess', "<Files wp-config.php>\nRequire all denied\n</Files>\n<Files wp-login.php>\n</Files>\n", 'fail' ),
			'wp-admin with authentication'    => array( 'wp-admin/.htaccess', "AuthType Basic\nAuthName \"Admin\"\nRequire valid-user\n", 'pass' ),
			'wp-admin without a rule'         => array( 'wp-admin/.htaccess', "Options -Indexes\n", 'fail' ),
			'wp-admin rule for one file'      => array( 'wp-admin/.htaccess', "<Files install.php>\nDeny from all\n</Files>\n<FilesMatch \"\\.(log|txt)$\">\nRequire all denied\n</FilesMatch>\n", 'fail' ),
			'wp-admin rule after a file rule' => array( 'wp-admin/.htaccess', "<Files install.php>\nDeny from all\n</Files>\nAuthType Basic\nRequire valid-user\n", 'pass' ),
			'Files that allows everyone'      => array( '.htaccess', "<Files wp-login.php>\nOrder Allow,Deny\nAllow from all\n</Files>\n", 'fail' ),
		);
	}

	/**
	 * A-02 reads .htaccess before it requests the login page.
	 *
	 * @dataProvider htaccess_provider
	 *
	 * @param string $file     File relative to the WordPress directory.
	 * @param string $contents Contents.
	 * @param string $expected Status when the login page answers 200.
	 */
	public function test_a02_htaccess( $file, $contents, $expected ) {
		$this->write( 'wordpress/' . $file, $contents );
		$this->answers[ self::login_url() ] = array( 200, array() );

		$this->assertSame(
			$expected,
			$this->status(
				'A-02',
				array(
					'abspath'        => $this->dir . '/wordpress',
					'active_plugins' => array(),
				)
			)
		);
	}

	/**
	 * A-02 requests the login page at its stored address, whatever the site_url filter makes of it.
	 */
	public function test_a02_requests_the_login_page() {
		$context = array(
			'abspath'        => $this->dir,
			'active_plugins' => array(),
		);
		$hide    = static function ( $url ) {
			return str_replace( 'wp-login.php', 'secret-login', $url );
		};
		add_filter( 'site_url', $hide );
		try {
			foreach ( array(
				200 => 'fail',
				403 => 'pass',
				404 => 'pass',
				302 => 'unknown',
			) as $code => $expected ) {
				$this->answers[ self::login_url() ] = array( $code, array() );
				$this->assertSame( $expected, $this->status( 'A-02', $context ), (string) $code );
			}
		} finally {
			remove_filter( 'site_url', $hide );
		}
		$this->assertSame( self::login_url(), end( $this->requested ) );

		unset( $this->answers[ self::login_url() ] );
		$this->assertSame( 'unknown', $this->status( 'A-02', $context ), 'The site cannot reach itself.' );
	}

	/**
	 * A-03 passes with a two-factor plugin, network-activated ones included.
	 */
	public function test_a03_two_factor() {
		$this->assertSame( 'pass', $this->status( 'A-03', array( 'active_plugins' => array( 'two-factor' ) ) ) );
		$this->assertSame( 'fail', $this->status( 'A-03', array( 'active_plugins' => array( 'wps-hide-login' ) ) ) );

		$active = static function () {
			return array( 'woocommerce/woocommerce.php', 'wp-2fa/wp-2fa.php' );
		};
		add_filter( 'pre_option_active_plugins', $active );
		try {
			$this->assertSame( 'pass', $this->status( 'A-03' ), 'Reads the active plugins of the site.' );
		} finally {
			remove_filter( 'pre_option_active_plugins', $active );
		}

		$single_file = static function () {
			return array( 'two-factor.php' );
		};
		add_filter( 'pre_option_active_plugins', $single_file );
		try {
			$this->assertSame( 'pass', $this->status( 'A-03' ), 'A single-file plugin is known by its file name.' );
		} finally {
			remove_filter( 'pre_option_active_plugins', $single_file );
		}
	}

	/**
	 * A-04 fails while XML-RPC answers.
	 */
	public function test_a04_xmlrpc() {
		$url = untrailingslashit( get_option( 'siteurl' ) ) . '/xmlrpc.php';
		foreach ( array(
			405 => 'fail',
			200 => 'fail',
			403 => 'pass',
			404 => 'pass',
			301 => 'unknown',
		) as $code => $expected ) {
			$this->answers[ $url ] = array( $code, array() );
			$this->assertSame( $expected, $this->status( 'A-04' ), (string) $code );
		}

		$this->requested = array();
		add_filter( 'xmlrpc_enabled', '__return_false' );
		try {
			$this->assertSame( 'pass', $this->status( 'A-04' ) );
		} finally {
			remove_filter( 'xmlrpc_enabled', '__return_false' );
		}
		$this->assertSame( array(), $this->requested, 'Turned off: no request.' );
	}

	/**
	 * A-05 fails while the users route lists users to anyone.
	 */
	public function test_a05_rest_users() {
		$url = get_rest_url( null, 'wp/v2/users' );

		$this->answers[ $url ] = array( 200, array( 'x-wp-total' => '3' ) );
		$this->assertSame( 'fail', $this->status( 'A-05' ) );

		$this->answers[ $url ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'A-05' ), 'Without the total.' );

		$this->answers[ $url ] = array( 200, array( 'x-wp-total' => '0' ) );
		$this->assertSame( 'pass', $this->status( 'A-05' ), 'Lists nobody.' );

		$this->answers[ $url ] = array( 401, array() );
		$this->assertSame( 'pass', $this->status( 'A-05' ) );

		$this->answers[ $url ] = array( 500, array() );
		$this->assertSame( 'unknown', $this->status( 'A-05' ) );
	}

	/**
	 * D-01 fails when the uploads directory answers 200 without an index file.
	 */
	public function test_d01_directory_listing() {
		$uploads = $this->uploads();
		$url     = $uploads['baseurl'] . '/';
		$context = array( 'uploads' => $uploads );

		$this->answers[ $url ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'D-01', $context ) );

		$this->answers[ $url ] = array( 403, array() );
		$this->assertSame( 'pass', $this->status( 'D-01', $context ) );

		$this->requested = array();
		$this->write( 'uploads/index.html', '' );
		$this->assertSame( 'pass', $this->status( 'D-01', $context ), 'The index file is served instead.' );
		$this->assertSame( array(), $this->requested );
	}

	/**
	 * D-01 is unknown without an uploads directory, or with uploads on another host.
	 */
	public function test_d01_unknown() {
		$this->assertSame( 'unknown', $this->status( 'D-01', array( 'uploads' => array( 'error' => 'Unable to create directory.' ) ) ) );

		$uploads            = $this->uploads();
		$uploads['baseurl'] = 'https://cdn.example.net/uploads';
		$this->assertSame( 'unknown', $this->status( 'D-01', array( 'uploads' => $uploads ) ) );
		$this->assertSame( array(), $this->requested, 'Another host is not requested.' );
	}

	/**
	 * File names directly in a public directory and the status they give.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function exposed_file_provider() {
		return array(
			'database dump'          => array( 'backup-2026-10-01.sql', 'fail' ),
			'compressed dump'        => array( 'db.sql.gz', 'fail' ),
			'site archive'           => array( 'site.zip', 'fail' ),
			'tarball'                => array( 'site.tar.gz', 'fail' ),
			'7z archive'             => array( 'site.7z', 'fail' ),
			'wp-config backup'       => array( 'wp-config.php.bak', 'fail' ),
			'wp-config editor copy'  => array( 'wp-config.php~', 'fail' ),
			'wp-config old'          => array( 'wp-config.old', 'fail' ),
			'debug log'              => array( 'debug.log', 'fail' ),
			'PHP error log'          => array( 'error_log', 'fail' ),
			'upper case'             => array( 'DUMP.SQL', 'fail' ),
			'wp-config itself'       => array( 'wp-config.php', 'pass' ),
			'index'                  => array( 'index.php', 'pass' ),
			'readme'                 => array( 'readme.html', 'pass' ),
			'htaccess'               => array( '.htaccess', 'pass' ),
			'image'                  => array( 'logo.png', 'pass' ),
			'sql in the middle'      => array( 'sql-notes.txt', 'pass' ),
			'compressed sitemap'     => array( 'sitemap.xml.gz', 'pass' ),
			'compressed page cache'  => array( 'index.html.gz', 'pass' ),
			'name ending like a log' => array( 'catalog', 'pass' ),
		);
	}

	/**
	 * D-02 reports a dump, archive, backup or log directly in a public directory.
	 *
	 * @dataProvider exposed_file_provider
	 *
	 * @param string $name     File name.
	 * @param string $expected Status.
	 */
	public function test_d02_exposed_files( $name, $expected ) {
		wp_mkdir_p( $this->dir . '/root' );
		$this->write( 'content/' . $name, 'data' );

		$this->assertSame( $expected, $this->status( 'D-02', array( 'public_directories' => array( $this->dir . '/root', $this->dir . '/content' ) ) ) );
	}

	/**
	 * D-02 looks only at the files directly in each directory.
	 */
	public function test_d02_looks_only_at_the_top_level() {
		$this->write( 'root/backups/site.zip', 'data' );
		wp_mkdir_p( $this->dir . '/root/old.zip' );
		$context = array( 'public_directories' => array( $this->dir . '/root', $this->dir . '/missing' ) );

		$this->assertSame( 'pass', $this->status( 'D-02', $context ) );
	}

	/**
	 * D-02 checks the WordPress, wp-content and uploads directories by default.
	 */
	public function test_d02_default_directories() {
		$this->write( 'wordpress/index.php', '<?php' );
		$this->write( 'content/index.php', '<?php' );
		$context = array(
			'abspath'     => $this->dir . '/wordpress',
			'content_dir' => $this->dir . '/content',
			'uploads'     => $this->uploads(),
		);
		$this->assertSame( 'pass', $this->status( 'D-02', $context ) );

		$this->write( 'uploads/export.sql', 'data' );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'The uploads directory.' );

		wp_delete_file( $this->dir . '/uploads/export.sql' );
		$this->write( 'wordpress/wp-config.php.save', 'data' );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'The WordPress directory.' );

		wp_delete_file( $this->dir . '/wordpress/wp-config.php.save' );
		$this->write( 'content/debug.log', 'PHP Notice' );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'The wp-content directory.' );
	}

	/**
	 * D-02 reads a directory one name at a time and gives up at the limit (R1-4).
	 */
	public function test_d02_stops_at_the_limit() {
		foreach ( array( 'a.jpg', 'b.jpg', 'c.jpg', 'd.jpg' ) as $name ) {
			$this->write( 'big/' . $name, 'x' );
		}
		$context = array(
			'public_directories' => array( $this->dir . '/big' ),
			'scan_limit'         => 3,
		);
		$this->assertSame( 'unknown', $this->status( 'D-02', $context ), 'Six names with . and .., three read.' );

		$context['scan_limit'] = 100;
		$this->assertSame( 'pass', $this->status( 'D-02', $context ) );
	}

	/**
	 * D-02 is unknown when a directory cannot be read and nothing was found.
	 */
	public function test_d02_unreadable_directory() {
		wp_mkdir_p( $this->dir . '/locked' );
		chmod( $this->dir . '/locked', 0000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Test fixture.
		try {
			if ( is_readable( $this->dir . '/locked' ) ) {
				$this->assertTrue( true, 'Running as a user who reads any directory.' );
				return;
			}
			$this->assertSame( 'unknown', $this->status( 'D-02', array( 'public_directories' => array( $this->dir . '/locked' ) ) ) );
		} finally {
			chmod( $this->dir . '/locked', 0755 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Test clean-up.
		}
	}

	/**
	 * D-03 fails while errors are shown on the pages.
	 */
	public function test_d03_debug_display() {
		$cases = array(
			array( true, true, 'fail' ),
			array( true, 1, 'fail' ),
			array( true, false, 'pass' ),
			array( true, null, 'pass' ),
			array( false, true, 'pass' ),
		);
		foreach ( $cases as $case ) {
			$constants = array(
				'WP_DEBUG'         => $case[0],
				'WP_DEBUG_DISPLAY' => $case[1],
			);
			$this->assertSame( $case[2], $this->status( 'D-03', array( 'constants' => $constants ) ), wp_json_encode( $case ) );
		}
	}

	/**
	 * D-04 requests one WooCommerce log file.
	 */
	public function test_d04_log_files() {
		$uploads = $this->uploads();
		$this->write( 'uploads/wc-logs/index.html', '' );
		$this->write( 'uploads/wc-logs/fatal errors-2026-10-23-abc123.log', 'log' );
		$url     = $uploads['baseurl'] . '/wc-logs/fatal%20errors-2026-10-23-abc123.log';
		$context = array(
			'uploads'   => $uploads,
			'constants' => array( 'WC_LOG_DIR' => $uploads['basedir'] . '/wc-logs/' ),
		);

		$this->answers[ $url ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'D-04', $context ) );
		$this->assertSame( array( home_url( '/' ), $url ), $this->requested );

		$this->answers[ $url ] = array( 403, array() );
		$this->assertSame( 'pass', $this->status( 'D-04', $context ) );
	}

	/**
	 * D-04 is unknown without WooCommerce, without logs, or with logs outside the uploads directory.
	 */
	public function test_d04_unknown() {
		$uploads = $this->uploads();
		$this->assertSame(
			'unknown',
			$this->status(
				'D-04',
				array(
					'uploads'   => $uploads,
					'constants' => array( 'WC_LOG_DIR' => null ),
				)
			),
			'WooCommerce is not active.'
		);

		wp_mkdir_p( $uploads['basedir'] . '/wc-logs' );
		$this->assertSame(
			'unknown',
			$this->status(
				'D-04',
				array(
					'uploads'   => $uploads,
					'constants' => array( 'WC_LOG_DIR' => $uploads['basedir'] . '/wc-logs/' ),
				)
			),
			'No log file.'
		);

		$this->write( 'private/wc-logs/debug.log', 'log' );
		$this->assertSame(
			'unknown',
			$this->status(
				'D-04',
				array(
					'uploads'   => $uploads,
					'constants' => array( 'WC_LOG_DIR' => $this->dir . '/private/wc-logs/' ),
				)
			),
			'Outside the uploads directory.'
		);
		$this->write( 'uploads-old/wc-logs/debug.log', 'log' );
		$this->assertSame(
			'unknown',
			$this->status(
				'D-04',
				array(
					'uploads'   => $uploads,
					'constants' => array( 'WC_LOG_DIR' => $this->dir . '/uploads-old/wc-logs/' ),
				)
			),
			'A sibling directory whose name starts like the uploads directory.'
		);
		$this->assertSame( array(), $this->requested );
	}

	/**
	 * D-05 fails when the checkout or My account page is not on HTTPS.
	 */
	public function test_d05_https_pages() {
		$this->assertSame( 'pass', $this->status( 'D-05', array( 'shop_urls' => array( 'https://example.org/checkout/', 'https://example.org/my-account/' ) ) ) );
		$this->assertSame( 'fail', $this->status( 'D-05', array( 'shop_urls' => array( 'https://example.org/checkout/', 'http://example.org/my-account/' ) ) ) );
		$this->assertSame( 'fail', $this->status( 'D-05', array( 'shop_urls' => array( false, 'https://example.org/my-account/' ) ) ) );

		$https = static function ( $url ) {
			return set_url_scheme( $url, 'https' );
		};
		add_filter( 'woocommerce_get_checkout_page_permalink', $https );
		add_filter( 'woocommerce_get_myaccount_page_permalink', $https );
		try {
			$this->assertSame( 'pass', $this->status( 'D-05' ), 'Reads the WooCommerce pages.' );
			remove_filter( 'woocommerce_get_myaccount_page_permalink', $https );
			$this->assertSame( 'fail', $this->status( 'D-05' ), 'The test site is on http.' );
		} finally {
			remove_filter( 'woocommerce_get_checkout_page_permalink', $https );
			remove_filter( 'woocommerce_get_myaccount_page_permalink', $https );
		}
	}

	/**
	 * M-01 fails while files can be edited from the admin screens.
	 */
	public function test_m01_file_editing() {
		$this->assertSame(
			'pass',
			$this->status(
				'M-01',
				array(
					'constants' => array(
						'DISALLOW_FILE_EDIT' => true,
						'DISALLOW_FILE_MODS' => null,
					),
				)
			)
		);
		$this->assertSame(
			'pass',
			$this->status(
				'M-01',
				array(
					'constants' => array(
						'DISALLOW_FILE_EDIT' => null,
						'DISALLOW_FILE_MODS' => true,
					),
				)
			)
		);
		$this->assertSame(
			'fail',
			$this->status(
				'M-01',
				array(
					'constants' => array(
						'DISALLOW_FILE_EDIT' => false,
						'DISALLOW_FILE_MODS' => null,
					),
				)
			)
		);
	}

	/**
	 * M-02 passes with a security plugin.
	 */
	public function test_m02_security_plugin() {
		$this->assertSame( 'pass', $this->status( 'M-02', array( 'active_plugins' => array( 'wordfence' ) ) ) );
		$this->assertSame( 'pass', $this->status( 'M-02', array( 'active_plugins' => array( 'sucuri-scanner' ) ) ) );
		$this->assertSame( 'fail', $this->status( 'M-02', array( 'active_plugins' => array( 'wps-hide-login', 'two-factor' ) ) ) );
		$this->assertSame( 'fail', $this->status( 'M-02', array( 'active_plugins' => array() ) ) );
	}

	/**
	 * PHP files in the uploads directory and the status they give.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function php_file_provider() {
		return array(
			'web shell'                  => array( '2026/10/image.php', '<?php system( $_GET["c"] );', 'fail' ),
			'short echo tag'             => array( '2026/10/x.php', '<?= `id` ?>', 'fail' ),
			'phtml'                      => array( '2026/10/x.phtml', '<?php eval( $_POST["x"] );', 'fail' ),
			'upper case extension'       => array( '2026/10/X.PHP', '<?php eval( $_POST["x"] );', 'fail' ),
			'php5'                       => array( 'x.php5', '<?php eval( $_POST["x"] );', 'fail' ),
			'guarded code'               => array( 'plugin/cache.php', "<?php\ndefined( 'ABSPATH' ) || exit;\nsystem( \$_GET['c'] );\n", 'pass' ),
			'guard with or die'          => array( 'plugin/cache.php', "<?php defined( 'ABSPATH' ) or die( 'No direct access.' ); system( 'id' );", 'pass' ),
			'guard in an if block'       => array( 'plugin/cache.php', "<?php if ( ! defined( 'ABSPATH' ) ) { exit; } system( 'id' );", 'pass' ),
			'guard on WPINC'             => array( 'plugin/cache.php', "<?php if ( ! \\defined( \"WPINC\" ) ) exit(); system( 'id' );", 'pass' ),
			'guard on a PHP constant'    => array( 'plugin/cache.php', "<?php defined( 'PHP_VERSION' ) || exit; system( 'id' );", 'fail' ),
			'guard whose exit runs code' => array( 'plugin/cache.php', "<?php defined( 'ABSPATH' ) || exit( system( 'id' ) );", 'fail' ),
			'guard with a cast for !'    => array( 'plugin/cache.php', "<?php if ( (int) defined( 'ABSPATH' ) ) { exit; } system( 'id' );", 'fail' ),
			'guard the wrong way round'  => array( 'plugin/cache.php', "<?php if ( defined( 'ABSPATH' ) ) { exit; } system( 'id' );", 'fail' ),
			'guard cut by the read'      => array( 'plugin/cache.php', "<?php defined( 'ABSPATH' ) || exit" . str_repeat( ' ', 70000 ) . "(system( 'id' ));", 'unknown' ),
			'exit with code'             => array( 'x.php', '<?php exit( system( "id" ) );', 'fail' ),
			'silence is golden'          => array( 'jp4wc-security/index.php', "<?php\n// Silence is golden.\n", 'pass' ),
			'empty'                      => array( 'index.php', '', 'pass' ),
			'html only'                  => array( 'index.php', '<html></html>', 'pass' ),
			'doc comment only'           => array( 'index.php', "<?php\n/**\n * Nothing here.\n */\n?>\n", 'pass' ),
			'exit then data'             => array( 'sucuri/sucuri-auditqueue.php', '<?php exit(0); ?>{"a":"<?php system(1); ?>"}', 'pass' ),
			'die with a message'         => array( 'data.php', "<?php die( 'Access denied' );\nsystem( 'id' );", 'pass' ),
			'bare exit'                  => array( 'data.php', "<?php exit;\nsystem( 'id' );", 'pass' ),
			'exit without arguments'     => array( 'data.php', '<?php exit(); system( "id" );', 'pass' ),
			'halt compiler'              => array( 'data.php', "<?php __halt_compiler();\x00binary", 'pass' ),
			'long comment then code'     => array( 'index.php', "<?php\n/*" . str_repeat( 'x', 2000 ) . "*/\nsystem( 'id' );", 'fail' ),
			'comment past the read'      => array( 'index.php', "<?php\n/*" . str_repeat( 'x', 70000 ) . "*/\nsystem( 'id' );", 'unknown' ),
			'not PHP'                    => array( '2026/10/photo.jpg', '<?php system( "id" );', 'pass' ),
			'guard answering 404'        => array( 'backwpup-logs/index.php', "<?php\nheader( \$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );\nheader( 'Status: 404 Not Found' );\n?>\n", 'pass' ),
			'response code then exit'    => array( 'cache/index.php', "<?php http_response_code( 403 ); exit;\nsystem( 'id' );", 'pass' ),
			'header calling a function'  => array( 'cache/index.php', "<?php header( system( 'id' ) );", 'fail' ),
			'header then code'           => array( 'cache/index.php', "<?php header( 'X-A: 1' ); system( 'id' );", 'fail' ),
			'data file'                  => array( 'plugin/settings.php', "<?php\nreturn array( 'a' => 1, 'b' => array( 'c', -2, 3.5, true, null ), 'd' => [ 'e' ] );\n", 'pass' ),
			'data file without ;'        => array( 'plugin/settings.php', "<?php return array( 'a' => 1 ) ?>", 'pass' ),
			'return of a call'           => array( 'plugin/settings.php', "<?php return 'system'( 'id' );", 'fail' ),
			'return with a call inside'  => array( 'plugin/settings.php', "<?php return array( 'a' => system( 'id' ) );", 'fail' ),
			'long data file'             => array( 'plugin/settings.php', "<?php return array(\n" . str_repeat( "\t'key' => 'value',\n", 100 ) . ');', 'pass' ),
			'data past the read'         => array( 'plugin/settings.php', "<?php return array(\n" . str_repeat( "\t'key' => 'value',\n", 4000 ) . ');', 'unknown' ),
			'string cut by the read'     => array( 'plugin/settings.php', "<?php return array( '" . str_repeat( 'x', 70000 ) . "' );", 'unknown' ),
			'double quotes cut'          => array( 'plugin/settings.php', '<?php return array( "' . str_repeat( 'x', 70000 ) . '" );', 'unknown' ),
			'compiled template'          => array( 'cache/twig/ab/abc.php', "<?php\n\nuse Twig\\Environment;\n\n/* index.twig */\nclass __TwigTemplate_abc extends Twig\\Template\n{\n    public function display( \$context ) { echo 'x'; }\n}\n", 'pass' ),
			'declarations only'          => array( 'cache/x.php', "<?php\ndeclare(strict_types=1);\nnamespace X;\nuse A\\{B, C};\n#[Attr( 1 )]\nfinal class Y { const A = 1; }\nfunction z( \$a = array( 1 ) ): void { system( 'id' ); }\nconst B = 2;\n", 'pass' ),
			'namespace then code'        => array( 'cache/x.php', "<?php namespace X; system( 'id' );", 'fail' ),
			'function then code'         => array( 'cache/x.php', "<?php function a() {} system( \$_GET['c'] );", 'fail' ),
			'constant then code'         => array( 'cache/x.php', "<?php const A = 1; system( 'id' );", 'fail' ),
			'attribute then code'        => array( 'cache/x.php', "<?php #[A] function a() {} system( 'id' );", 'fail' ),
			'class then instance'        => array( 'cache/x.php', "<?php class X { function __destruct() { system( 'id' ); } } new X();", 'fail' ),
			'namespace block'            => array( 'cache/x.php', "<?php namespace X { system( 'id' ); }", 'fail' ),
			'exit then a short open tag' => array( 'x.php', "<?php exit; ?>\n<? system( 'id' ); ?>", 'unknown' ),
			'XML declaration'            => array( 'x.php', "<?php exit; ?>\n<?xml version=\"1.0\"?><a/>", 'pass' ),
			'declare before a statement' => array( 'cache/x.php', "<?php declare(ticks=1) system( 'id' );", 'fail' ),
			'declare alternative syntax' => array( 'cache/x.php', "<?php declare(ticks=1): system( 'id' ); enddeclare;", 'fail' ),
			'declare then a class'       => array( 'cache/x.php', "<?php declare(strict_types=1);\nclass X {}\n", 'pass' ),
			'declare block'              => array( 'cache/x.php', "<?php declare(ticks=1) { system( 'id' ); }", 'fail' ),
			'declarations past the read' => array( 'cache/x.php', "<?php class X {\n" . str_repeat( "\tconst A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';\n", 2000 ) . "}\nsystem( 'id' );", 'unknown' ),
			'exit then a padded call'    => array( 'x.php', '<?php exit' . str_repeat( ' ', 1100 ) . "(system( \$_GET['c'] ));", 'fail' ),
			'exit cut by the read'       => array( 'x.php', '<?php exit' . str_repeat( ' ', 70000 ) . "(system( \$_GET['c'] ));", 'unknown' ),
			'die after header cut'       => array( 'x.php', "<?php header( 'X-A: 1' ); die" . str_repeat( ' ', 70000 ) . "(system( \$_GET['c'] ));", 'unknown' ),
		);
	}

	/**
	 * M-03 fails on a PHP file in the uploads directory that runs code.
	 *
	 * @dataProvider php_file_provider
	 *
	 * @param string $path     Path under the uploads directory.
	 * @param string $contents Contents.
	 * @param string $expected Status.
	 */
	public function test_m03_php_in_uploads( $path, $contents, $expected ) {
		$this->write( 'uploads/' . $path, $contents );

		$this->assertSame( $expected, $this->status( 'M-03', array( 'uploads' => $this->uploads() ) ) );
	}

	/**
	 * M-03 does not call short open tag code harmless, whatever this process's
	 * short_open_tag (G3-1): with it on the code is read and fails, with it off
	 * the code reads as HTML and is unknown.
	 */
	public function test_m03_short_open_tag() {
		$this->write( 'uploads/2026/10/x.php', "<? system( \$_GET['c'] ); ?>" );

		$expected = filter_var( ini_get( 'short_open_tag' ), FILTER_VALIDATE_BOOLEAN ) ? 'fail' : 'unknown';
		$this->assertSame( $expected, $this->status( 'M-03', array( 'uploads' => $this->uploads() ) ) );
	}

	/**
	 * M-03 passes for an empty or missing uploads directory and is unknown without one.
	 */
	public function test_m03_without_files() {
		$this->assertSame( 'pass', $this->status( 'M-03', array( 'uploads' => $this->uploads() ) ) );

		$uploads            = $this->uploads();
		$uploads['basedir'] = $this->dir . '/missing';
		$this->assertSame( 'pass', $this->status( 'M-03', array( 'uploads' => $uploads ) ) );

		$this->assertSame( 'unknown', $this->status( 'M-03', array( 'uploads' => array( 'error' => 'Unable to create directory.' ) ) ) );
	}

	/**
	 * M-03 stops at its limits: unknown unless it found a file first.
	 */
	public function test_m03_limits() {
		$this->write( 'uploads/a/one.jpg', 'x' );
		$this->write( 'uploads/b/two.jpg', 'x' );
		$this->write( 'uploads/c/three.jpg', 'x' );
		$context = array(
			'uploads'    => $this->uploads(),
			'scan_limit' => 3,
		);
		$this->assertSame( 'unknown', $this->status( 'M-03', $context ), 'Six entries, three looked at.' );

		$context['scan_limit'] = 100;
		$this->assertSame( 'pass', $this->status( 'M-03', $context ) );

		$limit = static function () {
			return 2;
		};
		add_filter( 'jp4wc_security_file_scan_limit', $limit );
		try {
			$this->assertSame( 'unknown', $this->status( 'M-03', $context ), 'The filter sets the limit.' );
		} finally {
			remove_filter( 'jp4wc_security_file_scan_limit', $limit );
		}

		$deep = 'uploads/' . implode( '/', array_fill( 0, JP4WC_Security_Checks::SCAN_DEPTH + 2, 'd' ) ) . '/shell.php';
		$this->write( $deep, '<?php system( "id" );' );
		$this->assertSame( 'unknown', $this->status( 'M-03', $context ), 'Below the last level looked at.' );
	}

	/**
	 * M-03 does not follow a link to a directory outside the uploads directory.
	 */
	public function test_m03_does_not_follow_links() {
		$this->write( 'elsewhere/shell.php', '<?php system( "id" );' );
		$uploads = $this->uploads();
		if ( ! function_exists( 'symlink' ) || ! @symlink( $this->dir . '/elsewhere', $uploads['basedir'] . '/link' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Symbolic links may not be allowed.
			$this->assertTrue( true, 'Symbolic links are not available.' );
			return;
		}

		$this->assertSame( 'pass', $this->status( 'M-03', array( 'uploads' => $uploads ) ) );
	}
}
