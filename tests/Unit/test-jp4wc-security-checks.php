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
		JP4WC_Security_Checks::delete_probe_results();
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
		JP4WC_Security_Checks::delete_probe_results();
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
		JP4WC_Security_Checks::delete_probe_results();
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
		$checks = JP4WC_Security_Checks::run( array( 'uploads' => $this->uploads() ) );

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

		// Each request's result is kept in an option of its own, without the URL itself.
		$kept = 0;
		foreach ( JP4WC_Security_Checks::probe_keys() as $key ) {
			$stored = get_option( JP4WC_Security_Probe::option_name( $key ) );
			if ( false === $stored ) {
				continue;
			}
			++$kept;
			$this->assertSame( array( 'status', 'checked_at', 'url_hash' ), array_keys( $stored ), $key );
		}
		$this->assertGreaterThan( 0, $kept );
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
	 * V-03 does not pass on out-of-date oldest supported versions, as V-01 does
	 * not pass on out-of-date vulnerability data; a fail stands (A2).
	 */
	public function test_v03_out_of_date_data() {
		$versions = array(
			'php'         => '8.3.12',
			'wordpress'   => '6.9.1',
			'woocommerce' => '10.6.2',
		);
		$eol      = array(
			'php'         => '8.2',
			'wordpress'   => '6.7',
			'woocommerce' => '10.2',
		);
		$context  = array(
			'versions'  => $versions,
			'eol'       => $eol,
			'eol_stale' => true,
		);
		$this->assertSame( 'unknown', $this->status( 'V-03', $context ) );

		$context['versions']['php'] = '8.1.30';
		$this->assertSame( 'fail', $this->status( 'V-03', $context ) );

		$context['eol_stale'] = false;
		$context['versions']  = $versions;
		$this->assertSame( 'pass', $this->status( 'V-03', $context ) );
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
	 * The update_plugins transient of a site with two plugins WordPress can
	 * update: one with an update, one without.
	 *
	 * @param array $flags Extra properties of the entry with an update (autoupdate, disable_autoupdate).
	 * @return object
	 */
	private static function plugin_updates( array $flags = array() ) {
		$transient               = new stdClass();
		$transient->last_checked = time();
		$transient->response     = array(
			'shop-plugin/shop-plugin.php' => (object) array_merge(
				array(
					'slug'        => 'shop-plugin',
					'plugin'      => 'shop-plugin/shop-plugin.php',
					'new_version' => '2.0.0',
				),
				$flags
			),
		);
		$transient->no_update    = array(
			'forms/forms.php' => (object) array(
				'slug'        => 'forms',
				'plugin'      => 'forms/forms.php',
				'new_version' => '1.0.0',
			),
		);
		return $transient;
	}

	/**
	 * Runs V-04 as on a site: without the test library's automatic_updater_disabled filter.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private function v04( array $context ) {
		$this->assertSame( 10, has_filter( 'automatic_updater_disabled', '__return_true' ), 'The WordPress test library turns every automatic update off.' );
		remove_filter( 'automatic_updater_disabled', '__return_true' );
		try {
			return $this->status( 'V-04', $context );
		} finally {
			add_filter( 'automatic_updater_disabled', '__return_true' );
		}
	}

	/**
	 * V-04 passes only when every plugin WordPress can update is updated automatically.
	 */
	public function test_v04_every_plugin_updates_automatically() {
		$both = array( 'shop-plugin/shop-plugin.php', 'forms/forms.php' );

		$this->assertSame(
			'pass',
			$this->v04(
				array(
					'plugin_updates'      => self::plugin_updates(),
					'auto_update_plugins' => $both,
				)
			)
		);
		$this->assertSame(
			'fail',
			$this->v04(
				array(
					'plugin_updates'      => self::plugin_updates(),
					'auto_update_plugins' => array( 'shop-plugin/shop-plugin.php' ),
				)
			),
			'A plugin without an update counts too: its next update would wait.'
		);
		$this->assertSame(
			'fail',
			$this->v04(
				array(
					'plugin_updates'      => self::plugin_updates(),
					'auto_update_plugins' => array( 'forms/forms.php' ),
				)
			)
		);
		$this->assertSame(
			'pass',
			$this->v04(
				array(
					'plugin_updates'      => self::plugin_updates( array( 'autoupdate' => true ) ),
					'auto_update_plugins' => array( 'forms/forms.php' ),
				)
			),
			'WordPress.org asks for this update to be installed automatically.'
		);
		$this->assertSame(
			'fail',
			$this->v04(
				array(
					'plugin_updates'      => self::plugin_updates( array( 'disable_autoupdate' => true ) ),
					'auto_update_plugins' => $both,
				)
			),
			'The update says not to install it automatically.'
		);

		$none            = self::plugin_updates();
		$none->response  = array();
		$none->no_update = array();
		$this->assertSame( 'pass', $this->v04( array( 'plugin_updates' => $none ) ), 'No plugin WordPress can update.' );
		$this->assertSame( 'unknown', $this->v04( array( 'plugin_updates' => false ) ), 'WordPress has not looked for updates.' );
	}

	/**
	 * The auto_update_plugin filter decides, as it does for WordPress.
	 */
	public function test_v04_follows_the_auto_update_filter() {
		$items     = array();
		$all       = static function ( $update, $item ) use ( &$items ) {
			$items[] = $item;
			return true;
		};
		$transient = self::plugin_updates();
		add_filter( 'auto_update_plugin', $all, 10, 2 );
		try {
			$this->assertSame(
				'pass',
				$this->v04(
					array(
						'plugin_updates'      => $transient,
						'auto_update_plugins' => array(),
					)
				),
				'A host that updates every plugin.'
			);
		} finally {
			remove_filter( 'auto_update_plugin', $all, 10 );
		}
		$this->assertSame( array( 'shop-plugin', 'forms' ), wp_list_pluck( $items, 'slug' ) );
		$this->assertNotSame( $transient->response['shop-plugin/shop-plugin.php'], $items[0], 'The filter gets a copy of the entry.' );

		$one = static function ( $update, $item ) {
			return 'forms' === $item->slug ? false : $update;
		};
		add_filter( 'auto_update_plugin', $one, 10, 2 );
		try {
			$this->assertSame(
				'fail',
				$this->v04(
					array(
						'plugin_updates'      => self::plugin_updates(),
						'auto_update_plugins' => array( 'shop-plugin/shop-plugin.php', 'forms/forms.php' ),
					)
				)
			);
		} finally {
			remove_filter( 'auto_update_plugin', $one, 10 );
		}
	}

	/**
	 * A plugin that updates plugins on a schedule of its own, out of WordPress's
	 * automatic updates, counts them as updated automatically (#300, JP4WC Pro).
	 */
	public function test_v04_follows_the_scheduled_update_filter() {
		$context = array(
			'plugin_updates'      => self::plugin_updates(),
			'auto_update_plugins' => array( 'shop-plugin/shop-plugin.php' ),
		);
		$this->assertSame( 'fail', $this->v04( $context ), 'Without the filter, as before.' );

		$calls     = array();
		$scheduled = array( 'forms/forms.php' );
		$filter    = static function ( $update, $file, $item ) use ( &$calls, &$scheduled ) {
			$calls[ $file ] = array( $update, $item->slug );
			return $update || in_array( $file, $scheduled, true );
		};
		add_filter( 'jp4wc_security_check_updates_automatically', $filter, 10, 3 );
		try {
			$this->assertSame( 'pass', $this->v04( $context ), 'Forms is updated on a schedule.' );
			$this->assertSame(
				array(
					'shop-plugin/shop-plugin.php' => array( true, 'shop-plugin' ),
					'forms/forms.php'             => array( false, 'forms' ),
				),
				$calls,
				'The filter gets what WordPress decided, the plugin file and the entry.'
			);

			$scheduled = array();
			$this->assertSame( 'fail', $this->v04( $context ) );

			// Plugins as a whole may not be updated automatically: the filter does not change that.
			add_filter( 'plugins_auto_update_enabled', '__return_false' );
			$scheduled = array( 'forms/forms.php' );
			try {
				$this->assertSame( 'fail', $this->v04( $context ) );
			} finally {
				remove_filter( 'plugins_auto_update_enabled', '__return_false' );
			}
		} finally {
			remove_filter( 'jp4wc_security_check_updates_automatically', $filter, 10 );
		}

		// As JP4WC Pro does: auto_update_plugin takes forms out of WordPress's
		// automatic updates, and the filter, run after it, counts it as updated.
		$out_of_wordpress = static function ( $update, $item ) {
			return 'forms' === $item->slug ? false : $update;
		};
		$on_schedule      = static function ( $update, $file ) {
			return 'forms/forms.php' === $file ? true : $update;
		};
		$both             = array(
			'plugin_updates'      => self::plugin_updates(),
			'auto_update_plugins' => array( 'shop-plugin/shop-plugin.php', 'forms/forms.php' ),
		);
		add_filter( 'auto_update_plugin', $out_of_wordpress, 10, 2 );
		try {
			$this->assertSame( 'fail', $this->v04( $both ), 'Taken out of WordPress\'s automatic updates.' );
			add_filter( 'jp4wc_security_check_updates_automatically', $on_schedule, 10, 2 );
			$this->assertSame( 'pass', $this->v04( $both ), 'Updated on a schedule.' );
		} finally {
			remove_filter( 'jp4wc_security_check_updates_automatically', $on_schedule, 10 );
			remove_filter( 'auto_update_plugin', $out_of_wordpress, 10 );
		}

		// The filter has the last word, after auto_update_plugin.
		$deny = static function () {
			return false;
		};
		add_filter( 'auto_update_plugin', '__return_true' );
		add_filter( 'jp4wc_security_check_updates_automatically', $deny );
		try {
			$this->assertSame( 'fail', $this->v04( $both ) );
		} finally {
			remove_filter( 'jp4wc_security_check_updates_automatically', $deny );
			remove_filter( 'auto_update_plugin', '__return_true' );
		}
	}

	/**
	 * V-04 fails when automatic updates are off for plugins as a whole or for
	 * WordPress security releases, whatever the plugins' own settings.
	 */
	public function test_v04_automatic_updates_turned_off() {
		$this->assertSame( 'fail', $this->status( 'V-04' ), 'The WordPress test library turns every automatic update off.' );

		$context = array(
			'plugin_updates'      => self::plugin_updates(),
			'auto_update_plugins' => array( 'shop-plugin/shop-plugin.php', 'forms/forms.php' ),
		);
		$this->assertSame( 'pass', $this->v04( $context + array( 'constants' => array( 'WP_AUTO_UPDATE_CORE' => 'minor' ) ) ) );
		$this->assertSame( 'fail', $this->v04( $context + array( 'constants' => array( 'WP_AUTO_UPDATE_CORE' => false ) ) ) );
		foreach ( array( 'plugins_auto_update_enabled', 'allow_minor_auto_core_updates', 'auto_update_core' ) as $filter ) {
			add_filter( $filter, '__return_false' );
			try {
				$this->assertSame( 'fail', $this->v04( $context ), $filter );
			} finally {
				remove_filter( $filter, '__return_false' );
			}
		}

		// A filter that allows only minor core releases lets V-04 pass (B2).
		$minor_only = static function ( $update, $item ) {
			// Allows a release of the running branch only, as some update managers do.
			return isset( $item->current ) && get_bloginfo( 'version' ) === $item->current ? $update : false;
		};
		add_filter( 'auto_update_core', $minor_only, 10, 2 );
		try {
			$this->assertSame( 'pass', $this->v04( $context ) );
		} finally {
			remove_filter( 'auto_update_core', $minor_only, 10 );
		}

		// WordPress never looks for updates without wp_version_check(), as Site Health reports (B2).
		$this->assertSame( 10, has_action( 'wp_version_check', 'wp_version_check' ) );
		remove_action( 'wp_version_check', 'wp_version_check' );
		try {
			$this->assertSame( 'fail', $this->v04( $context ) );
		} finally {
			add_action( 'wp_version_check', 'wp_version_check' );
		}
	}

	/**
	 * V-04 reads the update_plugins transient and the auto_update_plugins option of the site.
	 */
	public function test_v04_reads_the_site_settings() {
		$transient = static function () {
			return self::plugin_updates();
		};
		$option    = static function () {
			return array( 'shop-plugin/shop-plugin.php', 'forms/forms.php' );
		};
		add_filter( 'pre_site_transient_update_plugins', $transient );
		add_filter( 'pre_site_option_auto_update_plugins', $option );
		try {
			$this->assertSame( 'pass', $this->v04( array() ) );
			remove_filter( 'pre_site_option_auto_update_plugins', $option );
			$this->assertSame( 'fail', $this->v04( array() ), 'No plugin has auto-updates on.' );
		} finally {
			remove_filter( 'pre_site_transient_update_plugins', $transient );
			remove_filter( 'pre_site_option_auto_update_plugins', $option );
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
	 * A-02 passes with this plugin's own login attempt limiter on, without a request (3.0.0).
	 */
	public function test_a02_own_login_limiter() {
		$context                            = array(
			'abspath'        => $this->dir,
			'active_plugins' => array( 'akismet' ),
		);
		$this->answers[ self::login_url() ] = array( 200, array() );
		update_option( 'wc4jp-login-limit', '1' );
		update_option( 'active_plugins', array() );
		try {
			$this->assertSame( 'pass', $this->status( 'A-02', $context ) );
			$this->assertSame( array(), $this->requested );

			add_filter( 'jp4wc_login_limit_enabled', '__return_false' );
			try {
				$this->assertSame( 'fail', $this->status( 'A-02', $context ), 'A limiter turned off by the filter does not protect the page.' );
			} finally {
				remove_filter( 'jp4wc_login_limit_enabled', '__return_false' );
			}
			update_option( 'wc4jp-login-limit', '' );
			$this->assertSame( 'fail', $this->status( 'A-02', $context ) );
		} finally {
			delete_option( 'wc4jp-login-limit' );
		}
	}

	/**
	 * A-02 passes with Jetpack's brute force protection, and login_protected_by_other_means() names what protects the page.
	 */
	public function test_a02_jetpack_and_what_protects_the_page() {
		$context                            = array(
			'abspath'           => $this->dir,
			'active_plugins'    => array( 'jetpack' ),
			'jetpack_protect'   => true,
			'jetpack_connected' => true,
		);
		$this->answers[ self::login_url() ] = array( 200, array() );
		$this->assertSame( 'pass', $this->status( 'A-02', $context ) );
		$this->assertSame( array(), $this->requested );

		$context['jetpack_connected'] = false;
		$this->assertSame( 'fail', $this->status( 'A-02', $context ), 'Jetpack runs the protection only while connected to WordPress.com (3.0.0).' );
		$this->assertNull( JP4WC_Security_Checks::login_protected_by_other_means( $context ) );
		$context['jetpack_connected'] = true;
		$this->assertSame(
			array(
				'type' => 'jetpack',
				'name' => 'Jetpack',
			),
			JP4WC_Security_Checks::login_protected_by_other_means( $context )
		);

		$context['jetpack_protect'] = false;
		$this->assertSame( 'fail', $this->status( 'A-02', $context ), 'Jetpack with the protection off does not protect the page.' );
		$this->assertNull( JP4WC_Security_Checks::login_protected_by_other_means( $context ) );

		$context['active_plugins'] = array( 'jetpack', 'siteguard' );
		$this->assertSame(
			array(
				'type' => 'plugin',
				'name' => 'siteguard',
			),
			JP4WC_Security_Checks::login_protected_by_other_means( $context )
		);

		$context['active_plugins'] = array();
		$this->write( '.htaccess', "<Files wp-login.php>\nRequire ip 192.0.2.1\n</Files>\n" );
		$this->assertSame(
			array(
				'type' => 'htaccess',
				'name' => '.htaccess',
			),
			JP4WC_Security_Checks::login_protected_by_other_means( $context )
		);
	}

	/**
	 * A-05 passes with this plugin's own setting that hides the users routes, without a request (3.0.0).
	 */
	public function test_a05_own_setting() {
		$url = get_rest_url( null, 'wp/v2/users' );
		update_option( 'wc4jp-hide-rest-users', '1' );
		try {
			$this->answers[ $url ] = array( 404, array() );
			$this->assertSame( 'pass', $this->status( 'A-05' ) );
			$this->assertContains( $url, $this->requested, 'The route is still requested (after the home page).' );

			unset( $this->answers[ $url ] );
			$this->assertSame( 'pass', $this->status( 'A-05' ), 'Unreachable: the setting is trusted.' );

			$this->answers[ $url ] = array( 200, array( 'x-wp-total' => '3' ) );
			$this->assertSame( 'fail', $this->status( 'A-05' ), 'Another plugin put the route back: the list is what counts.' );

			update_option( 'wc4jp-hide-rest-users', '' );
			unset( $this->answers[ $url ] );
			$this->assertSame( 'unknown', $this->status( 'A-05' ), 'Off: unreachable is unknown, as before.' );

			// With pretty permalinks the ?rest_route= form is requested too (#315).
			update_option( 'wc4jp-hide-rest-users', '1' );
			$this->set_permalink_structure( '/%postname%/' );
			$pretty = get_rest_url( null, 'wp/v2/users' );
			$query  = trailingslashit( home_url() ) . 'index.php?rest_route=/wp/v2/users';

			$this->answers[ $pretty ] = array( 404, array() );
			$this->answers[ $query ]  = array( 500, array() );
			$this->assertSame( 'pass', $this->status( 'A-05' ), 'The setting is trusted for what the probe cannot tell.' );

			$this->answers[ $query ] = array( 200, array( 'x-wp-total' => '3' ) );
			$this->assertSame( 'fail', $this->status( 'A-05' ), 'Only the ?rest_route= form lists users.' );

			$this->answers[ $pretty ] = array( 200, array( 'x-wp-total' => '3' ) );
			$this->requested          = array();
			$this->assertSame( 'fail', $this->status( 'A-05' ) );
			$this->assertNotContains( $query, $this->requested, 'A list at the first URL settles it.' );
		} finally {
			$this->set_permalink_structure( '' );
			delete_option( 'wc4jp-hide-rest-users' );
		}
	}

	/**
	 * Without Jetpack loaded, the Jetpack lookup says no (the test suite has no Jetpack).
	 */
	public function test_jetpack_lookup_without_jetpack() {
		$this->assertFalse( class_exists( 'Jetpack' ) );
		$this->assertNull(
			JP4WC_Security_Checks::login_protected_by_other_means(
				array(
					'abspath'        => $this->dir,
					'active_plugins' => array( 'jetpack' ),
				)
			)
		);
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
	 * A-04 with this plugin's setting that turns XML-RPC off (3.0.0): no request; allowed methods need a protected login page.
	 */
	public function test_a04_own_setting() {
		require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-xmlrpc-guard.php';
		$url                   = untrailingslashit( get_option( 'siteurl' ) ) . '/xmlrpc.php';
		$this->answers[ $url ] = array( 405, array() );
		$context               = array(
			'abspath'        => $this->dir,
			'active_plugins' => array( 'akismet' ),
		);
		update_option( 'active_plugins', array() );
		update_option( 'wc4jp-disable-xmlrpc', '1' );
		try {
			$this->assertSame( 'pass', $this->status( 'A-04', $context ), 'Nothing allowed.' );

			update_option( 'wc4jp-xmlrpc-allow', 'wp.getUsersBlogs' );
			$this->assertSame( 'fail', $this->status( 'A-04', $context ), 'An allowed method accepts passwords with nothing limiting them.' );

			update_option( 'wc4jp-login-limit', '1' );
			$this->assertSame( 'pass', $this->status( 'A-04', $context ), 'The login attempt limiter covers XML-RPC.' );

			update_option( 'wc4jp-login-limit', '' );
			$this->assertSame( 'pass', $this->status( 'A-04', array( 'active_plugins' => array( 'wordfence' ) ) + $context ), 'A security plugin limits XML-RPC logins too.' );
			$this->assertSame( 'pass', $this->status( 'A-04', array( 'active_plugins' => array( 'wps-hide-login', 'loginizer' ) ) + $context ), 'A limiting plugin next to a hiding one.' );
			$this->assertSame(
				'pass',
				$this->status(
					'A-04',
					array(
						'jetpack_protect'   => true,
						'jetpack_connected' => true,
					) + $context
				),
				'Jetpack\'s brute force protection.'
			);

			// What protects wp-login.php but not xmlrpc.php.
			$this->assertSame( 'fail', $this->status( 'A-04', array( 'active_plugins' => array( 'wps-hide-login' ) ) + $context ), 'Moving the login page does not limit XML-RPC.' );
			$this->assertSame( 'fail', $this->status( 'A-04', array( 'active_plugins' => array( 'rename-wp-login' ) ) + $context ) );
			$this->write( 'wordpress/.htaccess', "<Files wp-login.php>\nRequire ip 192.0.2.1\n</Files>\n" );
			$htaccess = array(
				'abspath'        => $this->dir . '/wordpress',
				'active_plugins' => array(),
			);
			$this->assertNotNull( JP4WC_Security_Checks::login_protected_by_other_means( $htaccess ), 'The login page is restricted.' );
			$this->assertSame( 'fail', $this->status( 'A-04', $htaccess ), 'A restriction of wp-login.php does not reach xmlrpc.php.' );

			// The limiter turned on but asleep behind a plugin that only moves the login page.
			update_option( 'wc4jp-login-limit', '1' );
			update_option( 'active_plugins', array( 'wps-hide-login/wps-hide-login.php' ) );
			$this->assertFalse( JP4WC_Login_Limiter::is_active() );
			$this->assertSame( 'fail', $this->status( 'A-04', array( 'active_plugins' => array( 'wps-hide-login' ) ) + $context ), 'An asleep limiter counts nothing.' );
			update_option( 'active_plugins', array() );
			update_option( 'wc4jp-login-limit', '' );
			$this->assertSame( array(), $this->requested, 'The setting is read, nothing is requested.' );

			update_option( 'wc4jp-disable-xmlrpc', '' );
			$this->assertSame( 'fail', $this->status( 'A-04', $context ), 'Off: XML-RPC is requested as before.' );
		} finally {
			delete_option( 'wc4jp-disable-xmlrpc' );
			delete_option( 'wc4jp-xmlrpc-allow' );
			delete_option( 'wc4jp-login-limit' );
		}
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
		$this->requested       = array();
		$this->assertSame( 'unknown', $this->status( 'A-05' ) );

		// Plain permalinks: the route's URL is already the ?rest_route= form, requested once (#315).
		$this->assertStringContainsString( '?rest_route=/wp/v2/users', $url );
		$this->assertSame(
			array( $url ),
			array_values(
				array_filter(
					$this->requested,
					static function ( $requested ) {
						return false !== strpos( $requested, 'wp/v2/users' );
					}
				)
			)
		);
	}

	/**
	 * With pretty permalinks A-05 also requests the ?rest_route= form, which a rule on /wp-json/ does not block (#315).
	 */
	public function test_a05_rest_route_form_with_pretty_permalinks() {
		$this->set_permalink_structure( '/%postname%/' );
		try {
			$pretty = get_rest_url( null, 'wp/v2/users' );
			$query  = trailingslashit( home_url() ) . 'index.php?rest_route=/wp/v2/users';
			$this->assertStringContainsString( '/wp-json/wp/v2/users', $pretty );
			$users = static function ( array $requested ) {
				return array_values(
					array_filter(
						$requested,
						static function ( $url ) {
							return false !== strpos( $url, 'wp/v2/users' );
						}
					)
				);
			};

			$this->answers[ $pretty ] = array( 403, array() );
			$this->answers[ $query ]  = array( 200, array( 'x-wp-total' => '3' ) );
			$this->requested          = array();
			$this->assertSame( 'fail', $this->status( 'A-05' ), 'Only /wp-json/ is blocked.' );
			$this->assertSame( array( $pretty, $query ), $users( $this->requested ), 'Each form is requested once.' );
			$this->assertNotFalse( get_option( JP4WC_Security_Probe::option_name( 'A-05-query' ) ), 'The result is kept under a key of its own.' );
			JP4WC_Security_Checks::delete_probe_results();
			$this->assertFalse( get_option( JP4WC_Security_Probe::option_name( 'A-05-query' ) ), 'And removed with the others.' );

			$this->answers[ $pretty ] = array( 200, array( 'x-wp-total' => '3' ) );
			$this->requested          = array();
			$this->assertSame( 'fail', $this->status( 'A-05' ) );
			$this->assertSame( array( $pretty ), $users( $this->requested ), 'A list at the first URL settles it.' );

			$this->answers[ $pretty ] = array( 404, array() );
			$this->answers[ $query ]  = array( 404, array() );
			$this->assertSame( 'pass', $this->status( 'A-05' ), 'Both closed.' );

			$this->answers[ $pretty ] = array( 403, array() );
			$this->answers[ $query ]  = array( 200, array( 'x-wp-total' => '0' ) );
			$this->assertSame( 'pass', $this->status( 'A-05' ), 'The open form lists nobody.' );

			$this->answers[ $query ] = array( 500, array() );
			$this->assertSame( 'unknown', $this->status( 'A-05' ), 'One form tells nothing.' );

			unset( $this->answers[ $query ] );
			$this->assertSame( 'unknown', $this->status( 'A-05' ), 'One form cannot be reached.' );

			$this->answers[ $pretty ] = array( 500, array() );
			$this->answers[ $query ]  = array( 500, array() );
			$this->assertSame( 'unknown', $this->status( 'A-05' ), 'Neither tells anything.' );

			// Permalinks through index.php (/index.php/wp-json/...) are pretty permalinks too.
			$this->set_permalink_structure( '/index.php/%postname%/' );
			$index = get_rest_url( null, 'wp/v2/users' );
			$this->assertStringContainsString( '/index.php/wp-json/wp/v2/users', $index );
			$this->answers[ $index ] = array( 403, array() );
			$this->answers[ $query ] = array( 200, array( 'x-wp-total' => '3' ) );
			$this->requested         = array();
			$this->assertSame( 'fail', $this->status( 'A-05' ) );
			$this->assertSame( array( $index, $query ), $users( $this->requested ) );
		} finally {
			$this->set_permalink_structure( '' );
		}
	}

	/**
	 * A-05 reads an X-WP-Total header sent twice without a warning (#315).
	 */
	public function test_a05_repeated_total_header() {
		$url = get_rest_url( null, 'wp/v2/users' );

		$this->answers[ $url ] = array( 200, array( 'x-wp-total' => array( '3', '3' ) ) );
		$this->assertSame( 'fail', $this->status( 'A-05' ) );

		$this->answers[ $url ] = array( 200, array( 'x-wp-total' => array( '0', '0' ) ) );
		$this->assertSame( 'pass', $this->status( 'A-05' ), 'Every total says nobody.' );

		$this->answers[ $url ] = array( 200, array( 'x-wp-total' => array( '0', '2' ) ) );
		$this->assertSame( 'fail', $this->status( 'A-05' ), 'The totals disagree.' );
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
			'tgz tarball'            => array( 'site.tgz', 'fail' ),
			'tar archive'            => array( 'site.tar', 'fail' ),
			'7z archive'             => array( 'site.7z', 'fail' ),
			'rar archive'            => array( 'site.rar', 'fail' ),
			'wp-config backup'       => array( 'wp-config.php.bak', 'fail' ),
			'wp-config editor copy'  => array( 'wp-config.php~', 'fail' ),
			'wp-config old'          => array( 'wp-config.old', 'fail' ),
			'wp-config orig'         => array( 'wp-config.php.orig', 'fail' ),
			'wp-config nano save'    => array( 'wp-config.php.save', 'fail' ),
			'wp-config vim swap'     => array( '.wp-config.php.swp', 'fail' ),
			'debug log'              => array( 'debug.log', 'fail' ),
			'PHP error log'          => array( 'error_log', 'fail' ),
			'upper case'             => array( 'DUMP.SQL', 'fail' ),
			'UpdraftPlus database'   => array( 'backup_2026-11-01-0930_Shop_0123456789ab-db.gz', 'fail' ),
			'UpdraftPlus other db'   => array( 'backup_2026-11-01-0930_Shop_0123456789ab-db1.gz', 'fail' ),
			'gzipped db'             => array( 'shop_db.gz', 'fail' ),
			'gzipped db with a dot'  => array( 'shop.db.gz', 'fail' ),
			'bzip2 dump'             => array( 'dump.sql.bz2', 'fail' ),
			'xz dump'                => array( 'dump.sql.xz', 'fail' ),
			'zstd dump'              => array( 'dump.sql.zst', 'fail' ),
			'bzip2 tarball'          => array( 'site.tar.bz2', 'fail' ),
			'xz tarball'             => array( 'site.tar.xz', 'fail' ),
			'zstd tarball'           => array( 'site.tar.zst', 'fail' ),
			'tbz2 tarball'           => array( 'site.tbz2', 'fail' ),
			'txz tarball'            => array( 'site.txz', 'fail' ),
			'wpress backup'          => array( 'example-com-20261101-093000-abc123.wpress', 'fail' ),
			'wp-config itself'       => array( 'wp-config.php', 'pass' ),
			'index'                  => array( 'index.php', 'pass' ),
			'readme'                 => array( 'readme.html', 'pass' ),
			'htaccess'               => array( '.htaccess', 'pass' ),
			'image'                  => array( 'logo.png', 'pass' ),
			'sql in the middle'      => array( 'sql-notes.txt', 'pass' ),
			'compressed sitemap'     => array( 'sitemap.xml.gz', 'pass' ),
			'compressed page cache'  => array( 'index.html.gz', 'pass' ),
			'xz text'                => array( 'readme.txt.xz', 'pass' ),
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

		$this->assertSame(
			$expected,
			$this->status(
				'D-02',
				array(
					'public_directories' => array(
						$this->dir . '/root'    => null,
						$this->dir . '/content' => null,
					),
				)
			)
		);
	}

	/**
	 * D-02 looks only at the files directly in each directory.
	 */
	public function test_d02_looks_only_at_the_top_level() {
		$this->write( 'root/backups/site.zip', 'data' );
		wp_mkdir_p( $this->dir . '/root/old.zip' );
		$context = array(
			'public_directories' => array(
				$this->dir . '/root'    => null,
				$this->dir . '/missing' => null,
			),
		);

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
			'public_directories' => array( $this->dir . '/big' => null ),
			'scan_limit'         => 3,
		);
		$this->assertSame( 'unknown', $this->status( 'D-02', $context ), 'Six names with . and .., three read.' );

		$context['scan_limit'] = 100;
		$this->assertSame( 'pass', $this->status( 'D-02', $context ) );
	}

	/**
	 * D-02 requests a file it finds: one the server refuses passes (B4).
	 */
	public function test_d02_requests_the_files_found() {
		$this->write( 'public/backup 1.sql', 'data' );
		$base    = untrailingslashit( home_url() ) . '/public';
		$url     = $base . '/backup%201.sql';
		$context = array( 'public_directories' => array( $this->dir . '/public' => $base ) );

		$this->answers[ $url ] = array( 403, array() );
		$this->assertSame( 'pass', $this->status( 'D-02', $context ), 'The server refuses it.' );
		$this->assertSame( $url, end( $this->requested ) );

		$this->answers[ $url ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'The server serves it.' );

		unset( $this->answers[ $url ] );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'The request tells nothing.' );

		$this->answers[ $url ]            = array( 403, array() );
		$this->answers[ home_url( '/' ) ] = array( 403, array() );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'The site refuses its own requests.' );

		$this->answers[ home_url( '/' ) ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'D-02', array( 'public_directories' => array( $this->dir . '/public' => null ) ) ), 'No URL to request.' );
	}

	/**
	 * D-02 requests one file of each kind, up to three per directory (B4).
	 */
	public function test_d02_requests_each_kind_of_file() {
		$base    = untrailingslashit( home_url() ) . '/public';
		$context = array( 'public_directories' => array( $this->dir . '/public' => $base ) );
		foreach ( array( 'a.sql', 'b.sql', 'site.zip' ) as $name ) {
			$this->write( 'public/' . $name, 'data' );
			$this->answers[ $base . '/' . $name ] = array( 403, array() );
		}

		$this->requested = array();
		$this->assertSame( 'pass', $this->status( 'D-02', $context ) );
		$this->assertCount(
			2,
			array_filter(
				$this->requested,
				static function ( $url ) use ( $base ) {
					return 0 === strpos( $url, $base );
				}
			),
			'One .sql and one .zip.'
		);

		$this->answers[ $base . '/site.zip' ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'Another kind is served.' );

		$this->answers[ $base . '/site.zip' ] = array( 403, array() );
		foreach ( array( 'debug.log', 'old.bak' ) as $name ) {
			$this->write( 'public/' . $name, 'data' );
			$this->answers[ $base . '/' . $name ] = array( 403, array() );
		}
		$this->assertSame( 'unknown', $this->status( 'D-02', $context ), 'Four kinds: one was not requested.' );
	}

	/**
	 * D-02 tells the kind of a file by the extension the pattern found (#315).
	 */
	public function test_d02_kinds_of_the_new_formats() {
		$base    = untrailingslashit( home_url() ) . '/public';
		$context = array( 'public_directories' => array( $this->dir . '/public' => $base ) );
		foreach ( array( 'backup_2026-11-01-0930_example.com_aaa-db.gz', 'backup_2026-11-02-0930_example.com_bbb-db.gz', 'dump.sql.xz' ) as $name ) {
			$this->write( 'public/' . $name, 'data' );
			$this->answers[ $base . '/' . $name ] = array( 403, array() );
		}

		$this->requested = array();
		$this->assertSame( 'pass', $this->status( 'D-02', $context ) );
		$this->assertCount(
			2,
			array_filter(
				$this->requested,
				static function ( $url ) use ( $base ) {
					return 0 === strpos( $url, $base );
				}
			),
			'One -db.gz, whatever the site name, and one .sql.xz.'
		);

		$this->write( 'public/site.tar.xz', 'data' );
		$this->answers[ $base . '/site.tar.xz' ] = array( 200, array() );
		$this->assertSame( 'fail', $this->status( 'D-02', $context ), 'A .tar.xz is not a .sql.xz.' );
	}

	/**
	 * D-02 is unknown when a directory cannot be read and nothing was found.
	 */
	public function test_d02_unreadable_directory() {
		wp_mkdir_p( $this->dir . '/locked' );
		chmod( $this->dir . '/locked', 0000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Test fixture.
		try {
			if ( is_readable( $this->dir . '/locked' ) ) {
				$this->markTestSkipped( 'Running as a user who reads any directory.' );
			}
			$this->assertSame( 'unknown', $this->status( 'D-02', array( 'public_directories' => array( $this->dir . '/locked' => null ) ) ) );
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
			array( true, null, 'unknown' ),
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
	 * D-05 reads the scheme the site stores, not the one of this request (B3).
	 */
	public function test_d05_ignores_the_scheme_of_this_request() {
		$https            = isset( $_SERVER['HTTPS'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) : null;
		$_SERVER['HTTPS'] = 'on';
		try {
			$this->assertTrue( is_ssl() );
			$this->assertSame( 'fail', $this->status( 'D-05' ), 'The test site stores http.' );
		} finally {
			if ( null === $https ) {
				unset( $_SERVER['HTTPS'] );
			} else {
				$_SERVER['HTTPS'] = $https;
			}
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

		$this->assertSame( 'unknown', $this->status( 'M-03', array( 'uploads' => $uploads ) ), 'Not followed, so not looked into.' );
	}

	/**
	 * M-03 is unknown when a directory under uploads cannot be read (B1).
	 */
	public function test_m03_unreadable_directory() {
		$this->write( 'uploads/locked/shell.php', '<?php system( "id" );' );
		$uploads = $this->uploads();
		chmod( $uploads['basedir'] . '/locked', 0000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Test fixture.
		try {
			if ( is_readable( $uploads['basedir'] . '/locked' ) ) {
				$this->markTestSkipped( 'Running as a user who reads any directory.' );
			}
			$this->assertSame( 'unknown', $this->status( 'M-03', array( 'uploads' => $uploads ) ) );
		} finally {
			chmod( $uploads['basedir'] . '/locked', 0755 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Test clean-up.
		}
	}
}
