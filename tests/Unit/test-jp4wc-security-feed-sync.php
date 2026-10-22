<?php
/**
 * Tests for downloading the vulnerability feed and for turning the security check on and off (issue #257).
 *
 * Every request is answered by a `pre_http_request` filter from the contract
 * samples in tests/fixtures/security-feed/; nothing goes out.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Feed_Sync_Test
 */
class JP4WC_Security_Feed_Sync_Test extends WP_UnitTestCase {

	/**
	 * Responses by file name: an HTTP response array or a WP_Error.
	 *
	 * @var array<string, array|WP_Error>
	 */
	private $responses = array();

	/**
	 * Requests made: url and args.
	 *
	 * @var array<int, array{url: string, args: array}>
	 */
	private $requests = array();

	/**
	 * Software the check sees as installed.
	 *
	 * @var array
	 */
	private $installed = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Feed' ), 'The plugin loads the security check at plugins_loaded.' );

		update_option( JP4WC_Security_Feed::SETTING, '1' );
		JP4WC_Security_Feed::purge();
		JP4WC_Security_Feed::reconcile();

		$this->requests  = array();
		$this->responses = array( 'manifest.json' => $this->ok( self::fixture( 'manifest.json' ) ) );
		foreach ( array( 'plugins', 'themes', 'core' ) as $name ) {
			$this->responses[ 'vuln-' . $name . '.json.gz' ] = $this->ok( self::fixture( 'vuln-' . $name . '.json.gz' ) );
		}
		$this->installed = array(
			'plugin' => array(
				'sample-plugin' => array(
					array(
						'name'    => 'Sample Plugin',
						'version' => '1.2.3',
						'file'    => 'sample-plugin/sample-plugin.php',
					),
				),
			),
			'theme'  => array(
				'sample-theme' => array(
					array(
						'name'    => 'Sample Theme',
						'version' => '3.0.0',
						'file'    => '',
					),
				),
			),
			'core'   => array(
				'wordpress' => array(
					array(
						'name'    => 'WordPress',
						'version' => '6.4.2',
						'file'    => '',
					),
				),
			),
		);

		add_filter( 'pre_http_request', array( $this, 'answer' ), 10, 3 );
		add_filter( 'jp4wc_security_installed_software', array( $this, 'installed' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'answer' ), 10 );
		remove_filter( 'jp4wc_security_installed_software', array( $this, 'installed' ) );
		JP4WC_Security_Feed::purge();
		update_option( JP4WC_Security_Feed::SETTING, '1' );
		parent::tearDown();
	}

	/**
	 * Reads a file of the contract directory.
	 *
	 * @param string $name File name.
	 * @return string
	 */
	private static function fixture( $name ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.
		return file_get_contents( TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/' . $name );
	}

	/**
	 * A 200 response.
	 *
	 * @param string $body Body.
	 * @return array
	 */
	private function ok( $body ) {
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Answers a request from $responses (pre_http_request).
	 *
	 * @param false|array|WP_Error $pre  Response so far.
	 * @param array                $args Request arguments.
	 * @param string               $url  URL.
	 * @return array|WP_Error
	 */
	public function answer( $pre, $args, $url ) {
		$this->requests[] = array(
			'url'  => $url,
			'args' => $args,
		);
		$name             = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		if ( isset( $this->responses[ $name ] ) ) {
			return $this->responses[ $name ];
		}
		$response                     = $this->ok( '' );
		$response['response']['code'] = 404;
		return $response;
	}

	/**
	 * Returns the installed software of the test (jp4wc_security_installed_software).
	 *
	 * @return array
	 */
	public function installed() {
		return $this->installed;
	}

	/**
	 * The sample manifest, decoded.
	 *
	 * @return array
	 */
	private static function manifest() {
		return json_decode( self::fixture( 'manifest.json' ), true );
	}

	/**
	 * Serves a manifest.
	 *
	 * @param array $manifest Manifest.
	 */
	private function serve_manifest( array $manifest ) {
		$this->responses['manifest.json'] = $this->ok( wp_json_encode( $manifest ) );
	}

	/**
	 * Serves gzip bytes as a type's vulnerability file and lists them in a new manifest version.
	 *
	 * @param array  $manifest Manifest to change.
	 * @param string $type     File type.
	 * @param string $bytes    Bytes served.
	 * @return array The changed manifest.
	 */
	private function serve_file( array $manifest, $type, $bytes ) {
		$name                                 = 'vuln-' . $type . '-' . substr( hash( 'sha256', $bytes ), 0, 8 ) . '.json.gz';
		$this->responses[ $name ]             = $this->ok( $bytes );
		$manifest['version']                  = 'test-' . wp_generate_password( 6, false );
		$manifest['files'][ $type ]['file']   = $name;
		$manifest['files'][ $type ]['sha256'] = hash( 'sha256', $bytes );
		return $manifest;
	}

	/**
	 * The sample plugin file with one change, compressed.
	 *
	 * @param callable $change Changes the decoded file.
	 * @return string
	 */
	private static function changed_plugin_file( callable $change ) {
		$data = json_decode( self::fixture( 'vuln-plugins.json' ), true );
		return gzencode( wp_json_encode( $change( $data ) ) );
	}

	/**
	 * Path of a stored vulnerability file.
	 *
	 * @param string $type File type.
	 * @return string
	 */
	private static function stored( $type ) {
		return JP4WC_Security_Feed::directory_path() . '/' . $type . '.json';
	}

	/**
	 * URLs requested, by file name.
	 *
	 * @return string[]
	 */
	private function requested_files() {
		return array_map(
			static function ( $request ) {
				return basename( (string) wp_parse_url( $request['url'], PHP_URL_PATH ) );
			},
			$this->requests
		);
	}

	/**
	 * Runs a first sync that succeeds.
	 */
	private function sync_successfully() {
		JP4WC_Security_Feed::sync();
		$state = JP4WC_Security_Feed::get_state();
		$this->assertSame( '', $state['last_error'] );
		$this->assertSame( '2026-10-20.1', $state['version'] );
		$this->requests = array();
	}

	/**
	 * Asserts the files and state of the sample feed are still in use.
	 *
	 * @param string $error Part of the error the failed sync recorded.
	 */
	private function assert_previous_data_kept( $error ) {
		$state = JP4WC_Security_Feed::get_state();
		$this->assertSame( '2026-10-20.1', $state['version'] );
		$this->assertSame( self::manifest()['files']['plugin']['sha256'], $state['files']['plugin']['sha256'] );
		$this->assertStringContainsString( $error, $state['last_error'] );
		foreach ( array(
			'plugin' => 'plugins',
			'theme'  => 'themes',
			'core'   => 'core',
		) as $type => $name ) {
			$this->assertFileEquals( TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/vuln-' . $name . '.json', self::stored( $type ) );
		}
		$this->assertSame( array(), glob( JP4WC_Security_Feed::directory_path() . '/*.tmp' ), 'No temporary file is left behind.' );
	}

	/**
	 * The first sync downloads the manifest and the three files, stores them
	 * decompressed and matches the installed software.
	 */
	public function test_sync_stores_the_feed_and_finds_vulnerabilities() {
		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'manifest.json', 'vuln-plugins.json.gz', 'vuln-themes.json.gz', 'vuln-core.json.gz' ), $this->requested_files() );
		$this->assertSame( 'https://wc.artws.info/feed/v1/manifest.json', $this->requests[0]['url'] );

		foreach ( array(
			'plugin' => 'plugins',
			'theme'  => 'themes',
			'core'   => 'core',
		) as $type => $name ) {
			$this->assertFileEquals( TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/vuln-' . $name . '.json', self::stored( $type ) );
		}
		$this->assertFileExists( JP4WC_Security_Feed::directory_path() . '/index.php' );
		$this->assertFileExists( JP4WC_Security_Feed::directory_path() . '/.htaccess' );

		$state    = JP4WC_Security_Feed::get_state();
		$manifest = self::manifest();
		$this->assertSame( '', $state['last_error'] );
		$this->assertSame( '2026-10-20T03:00:00Z', $state['generated_at'] );
		$this->assertFalse( $state['stale'] );
		$this->assertSame( $manifest['attribution'], $state['attribution'] );
		$this->assertSame( $manifest['copyrights'], $state['copyrights'] );
		$this->assertSame( $manifest['files']['core']['sha256'], $state['files']['core']['sha256'] );
		$this->assertSame( 2, $state['files']['core']['count'] );

		$findings = JP4WC_Security_Feed::get_findings();
		$this->assertSame( 3, $findings['count'] );
		$this->assertSame( 'High', $findings['max_severity'] );
		$this->assertSame( array( 'sample-plugin', 'wordpress', 'sample-theme' ), wp_list_pluck( $findings['items'], 'slug' ) );
		$this->assertSame( $manifest['attribution'], $findings['attribution'] );
	}

	/**
	 * The requests send only software versions: no site URL in the User-Agent.
	 */
	public function test_requests_send_only_versions() {
		JP4WC_Security_Feed::sync();

		$this->assertNotEmpty( $this->requests );
		foreach ( $this->requests as $request ) {
			$this->assertSame(
				sprintf( 'JP4WC/%s; WP/%s; WC/%s; PHP/%s', JP4WC_VERSION, get_bloginfo( 'version' ), WC_VERSION, PHP_VERSION ),
				$request['args']['user-agent']
			);
			$this->assertStringNotContainsString( wp_parse_url( home_url(), PHP_URL_HOST ), $request['args']['user-agent'] );
			$this->assertArrayNotHasKey( 'body', array_filter( $request['args'] ), 'Nothing is posted.' );
		}
		$this->assertFalse( $this->requests[1]['args']['decompress'], 'The .json.gz is downloaded as served.' );
	}

	/**
	 * The feed URL can be changed with a filter.
	 */
	public function test_base_url_filter() {
		$filter = static function () {
			return 'https://feed.example.test/v1';
		};
		add_filter( 'jp4wc_security_feed_base_url', $filter );
		try {
			JP4WC_Security_Feed::sync();
		} finally {
			remove_filter( 'jp4wc_security_feed_base_url', $filter );
		}

		$this->assertSame( 'https://feed.example.test/v1/manifest.json', $this->requests[0]['url'] );
		$this->assertSame( 'https://feed.example.test/v1/vuln-plugins.json.gz', $this->requests[1]['url'] );
	}

	/**
	 * When the checksums did not change, only the manifest is downloaded.
	 */
	public function test_unchanged_files_are_not_downloaded_again() {
		$this->sync_successfully();

		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'manifest.json' ), $this->requested_files() );
		$this->assertSame( '', JP4WC_Security_Feed::get_state()['last_error'] );
	}

	/**
	 * A stored file that went missing is downloaded again.
	 */
	public function test_missing_file_is_downloaded_again() {
		$this->sync_successfully();
		wp_delete_file( self::stored( 'theme' ) );

		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'manifest.json', 'vuln-themes.json.gz' ), $this->requested_files() );
		$this->assertFileExists( self::stored( 'theme' ) );
	}

	/**
	 * A new version of a file replaces the stored one.
	 */
	public function test_changed_file_replaces_the_stored_one() {
		$this->sync_successfully();
		$bytes    = self::changed_plugin_file(
			static function ( $data ) {
				unset( $data['vulnerabilities']['sample-plugin'] );
				return $data;
			}
		);
		$manifest = $this->serve_file( self::manifest(), 'plugin', $bytes );
		$this->serve_manifest( $manifest );

		JP4WC_Security_Feed::sync();

		$state = JP4WC_Security_Feed::get_state();
		$this->assertSame( '', $state['last_error'] );
		$this->assertSame( $manifest['version'], $state['version'] );
		$this->assertSame( gzdecode( $bytes ), file_get_contents( self::stored( 'plugin' ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$this->assertSame( array( 'wordpress', 'sample-theme' ), wp_list_pluck( JP4WC_Security_Feed::get_findings()['items'], 'slug' ) );
	}

	/**
	 * A file whose bytes do not match the manifest's sha256 keeps the previous data,
	 * which is also what a cache mixing up the manifest and the files leads to.
	 */
	public function test_checksum_mismatch_keeps_the_previous_data() {
		$this->sync_successfully();
		$manifest                              = self::manifest();
		$manifest['version']                   = '2026-10-21.1';
		$manifest['files']['plugin']['sha256'] = str_repeat( 'a', 64 );
		$this->serve_manifest( $manifest );

		JP4WC_Security_Feed::sync();

		$this->assert_previous_data_kept( 'sha256' );
	}

	/**
	 * Failed requests.
	 *
	 * @return array<string, array{string, int|string, string}>
	 */
	public function http_error_provider() {
		return array(
			'manifest 500'           => array( 'manifest.json', 500, 'HTTP 500' ),
			'manifest network error' => array( 'manifest.json', 'error', 'timed out' ),
			'file 404'               => array( 'vuln-themes.json.gz', 404, 'HTTP 404' ),
			'file network error'     => array( 'vuln-core.json.gz', 'error', 'timed out' ),
		);
	}

	/**
	 * A failed request keeps the previous data.
	 *
	 * @dataProvider http_error_provider
	 *
	 * @param string     $name  File that fails.
	 * @param int|string $code  HTTP status, or 'error' for a WP_Error.
	 * @param string     $error Part of the recorded error.
	 */
	public function test_http_errors_keep_the_previous_data( $name, $code, $error ) {
		$this->sync_successfully();
		// A new version of every file, so that each one is downloaded.
		$manifest = self::manifest();
		foreach ( array(
			'plugin' => 'plugins',
			'theme'  => 'themes',
			'core'   => 'core',
		) as $type => $file ) {
			$manifest = $this->serve_file( $manifest, $type, gzencode( self::fixture( 'vuln-' . $file . '.json' ), 1 ) );
			if ( 'vuln-' . $file . '.json.gz' === $name ) {
				$manifest['files'][ $type ]['file'] = $name;
			}
		}
		$this->serve_manifest( $manifest );
		if ( 'error' === $code ) {
			$this->responses[ $name ] = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		} else {
			$this->responses[ $name ]['response']['code'] = $code;
		}

		JP4WC_Security_Feed::sync();

		$this->assert_previous_data_kept( $error );
	}

	/**
	 * Damaged gzip data.
	 *
	 * @return array<string, array{callable}>
	 */
	public function damaged_gzip_provider() {
		return array(
			'cut in half'         => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, intdiv( strlen( $gzip ), 2 ) );
				},
			),
			'trailer cut off'     => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, -4 );
				},
			),
			'wrong CRC'           => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, -8 ) . "\0\0\0\0" . substr( $gzip, -4 );
				},
			),
			'flipped byte'        => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, 40 ) . chr( ord( $gzip[40] ) ^ 0xff ) . substr( $gzip, 41 );
				},
			),
			'not gzip'            => array(
				static function () {
					return '{"schema_version":1,"type":"plugin","vulnerabilities":{}}';
				},
			),
			'gzip of broken JSON' => array(
				static function () {
					return gzencode( '{"schema_version":1,"type":"plugin","vulnerabilities":{"a":[' );
				},
			),
		);
	}

	/**
	 * A damaged file whose sha256 matches the manifest keeps the previous data.
	 *
	 * @dataProvider damaged_gzip_provider
	 *
	 * @param callable $damage Damages the sample gzip bytes.
	 */
	public function test_damaged_gzip_keeps_the_previous_data( $damage ) {
		$this->sync_successfully();
		$this->serve_manifest( $this->serve_file( self::manifest(), 'plugin', $damage( self::fixture( 'vuln-plugins.json.gz' ) ) ) );

		JP4WC_Security_Feed::sync();

		$this->assert_previous_data_kept( 'plugin vulnerability file' );
	}

	/**
	 * Files the client does not accept, although their bytes are as listed.
	 *
	 * @return array<string, array{callable}>
	 */
	public function rejected_file_provider() {
		return array(
			'schema_version 2'          => array(
				static function ( $data ) {
					$data['schema_version'] = 2;
					return $data;
				},
			),
			'another type'              => array(
				static function ( $data ) {
					$data['type'] = 'theme';
					return $data;
				},
			),
			'records not a list'        => array(
				static function ( $data ) {
					$data['vulnerabilities']['sample-plugin'] = array( 'id' => 'x' );
					return $data;
				},
			),
			'no vulnerabilities object' => array(
				static function ( $data ) {
					unset( $data['vulnerabilities'] );
					return $data;
				},
			),
		);
	}

	/**
	 * Such a file keeps the previous data.
	 *
	 * @dataProvider rejected_file_provider
	 *
	 * @param callable $change Changes the decoded sample.
	 */
	public function test_rejected_file_keeps_the_previous_data( $change ) {
		$this->sync_successfully();
		$this->serve_manifest( $this->serve_file( self::manifest(), 'plugin', self::changed_plugin_file( $change ) ) );

		JP4WC_Security_Feed::sync();

		$this->assert_previous_data_kept( 'plugin vulnerability file is not valid' );
	}

	/**
	 * When one changed file fails, the other changed files are not replaced either.
	 */
	public function test_one_failing_file_keeps_every_file() {
		$this->sync_successfully();
		$manifest = $this->serve_file(
			self::manifest(),
			'plugin',
			self::changed_plugin_file(
				static function ( $data ) {
					unset( $data['vulnerabilities']['sample-plugin'] );
					return $data;
				}
			)
		);
		$manifest = $this->serve_file( $manifest, 'core', 'not gzip' );
		$this->serve_manifest( $manifest );

		JP4WC_Security_Feed::sync();

		$this->assert_previous_data_kept( 'core vulnerability file' );
	}

	/**
	 * Manifests the client does not accept.
	 *
	 * @return array<string, array{callable, string}>
	 */
	public function rejected_manifest_provider() {
		return array(
			'schema_version 2'       => array(
				static function ( $manifest ) {
					$manifest['schema_version'] = 2;
					return $manifest;
				},
				'schema_version',
			),
			'schema_version as text' => array(
				static function ( $manifest ) {
					$manifest['schema_version'] = '1';
					return $manifest;
				},
				'schema_version',
			),
			'file with a directory'  => array(
				static function ( $manifest ) {
					$manifest['files']['plugin']['file'] = '../vuln-plugins.json.gz';
					return $manifest;
				},
				'files.plugin.file',
			),
			'file with a newline'    => array(
				static function ( $manifest ) {
					$manifest['files']['plugin']['file'] = "vuln-plugins.json.gz\n";
					return $manifest;
				},
				'files.plugin.file',
			),
			'uppercase sha256'       => array(
				static function ( $manifest ) {
					$manifest['files']['core']['sha256'] = strtoupper( $manifest['files']['core']['sha256'] );
					return $manifest;
				},
				'files.core.sha256',
			),
			'missing type'           => array(
				static function ( $manifest ) {
					unset( $manifest['files']['theme'] );
					return $manifest;
				},
				'files.theme',
			),
			'license over http'      => array(
				static function ( $manifest ) {
					$manifest['copyrights']['defiant']['license_url'] = 'http://www.wordfence.com/';
					return $manifest;
				},
				'copyrights.defiant',
			),
			'no copyrights'          => array(
				static function ( $manifest ) {
					$manifest['copyrights'] = array();
					return $manifest;
				},
				'copyrights',
			),
			'no attribution'         => array(
				static function ( $manifest ) {
					unset( $manifest['attribution'] );
					return $manifest;
				},
				'attribution',
			),
			'not an object'          => array(
				static function () {
					return 'manifest';
				},
				'not a JSON object',
			),
		);
	}

	/**
	 * Such a manifest keeps the previous data, and no vulnerability file is requested.
	 *
	 * @dataProvider rejected_manifest_provider
	 *
	 * @param callable $change Changes the decoded sample manifest.
	 * @param string   $error  Part of the recorded error.
	 */
	public function test_rejected_manifest_keeps_the_previous_data( $change, $error ) {
		$this->sync_successfully();
		$changed                          = $change( self::manifest() );
		$this->responses['manifest.json'] = $this->ok( is_array( $changed ) ? wp_json_encode( $changed ) : $changed );

		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'manifest.json' ), $this->requested_files() );
		$this->assert_previous_data_kept( $error );
	}

	/**
	 * Members and file types the client does not know are ignored.
	 */
	public function test_unknown_manifest_members_are_ignored() {
		$manifest                               = self::manifest();
		$manifest['new_member']                 = array( 'a' => 1 );
		$manifest['files']['mu-plugin']         = array( 'file' => 'vuln-mu.json.gz' );
		$manifest['copyrights']['defiant']['x'] = 'y';
		$this->serve_manifest( $manifest );

		JP4WC_Security_Feed::sync();

		$state = JP4WC_Security_Feed::get_state();
		$this->assertSame( '', $state['last_error'] );
		$this->assertSame( array( 'plugin', 'theme', 'core' ), array_keys( $state['files'] ) );
		$this->assertSame( self::manifest()['copyrights'], $state['copyrights'] );
		$this->assertNotContains( 'vuln-mu.json.gz', $this->requested_files() );
	}

	/**
	 * A first sync that fails stores no data and no findings.
	 */
	public function test_failed_first_sync_stores_no_data() {
		$this->responses['manifest.json']['response']['code'] = 404;

		JP4WC_Security_Feed::sync();

		$state = JP4WC_Security_Feed::get_state();
		$this->assertSame( array(), $state['files'] );
		$this->assertStringContainsString( 'HTTP 404', $state['last_error'] );
		$this->assertNull( JP4WC_Security_Feed::get_findings() );
	}

	/**
	 * A failed sync still matches the stored data against the software installed now.
	 */
	public function test_failed_sync_still_matches_again() {
		$this->sync_successfully();
		$this->installed['plugin']['sample-plugin'][0]['version'] = '1.2.4';
		$this->responses['manifest.json']['response']['code']     = 503;

		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'wordpress', 'sample-theme' ), wp_list_pluck( JP4WC_Security_Feed::get_findings()['items'], 'slug' ) );
	}

	/**
	 * The manifest's stale flag reaches the findings without downloading the files again.
	 */
	public function test_stale_manifest_is_recorded() {
		$this->sync_successfully();
		$manifest          = self::manifest();
		$manifest['stale'] = true;
		$this->serve_manifest( $manifest );

		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'manifest.json' ), $this->requested_files() );
		$this->assertTrue( JP4WC_Security_Feed::get_state()['stale'] );
		$this->assertTrue( JP4WC_Security_Feed::is_stale( JP4WC_Security_Feed::get_findings() ) );
	}

	/**
	 * Data generated more than three days ago is out of date, even when the manifest says otherwise.
	 */
	public function test_old_data_is_stale() {
		$data = array(
			'stale'        => false,
			'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - DAY_IN_SECONDS ),
		);
		$this->assertFalse( JP4WC_Security_Feed::is_stale( $data ) );

		$data['generated_at'] = gmdate( 'Y-m-d\TH:i:s\Z', time() - 4 * DAY_IN_SECONDS );
		$this->assertTrue( JP4WC_Security_Feed::is_stale( $data ) );

		$filter = static function () {
			return 7 * DAY_IN_SECONDS;
		};
		add_filter( 'jp4wc_security_feed_stale_after', $filter );
		try {
			$this->assertFalse( JP4WC_Security_Feed::is_stale( $data ) );
		} finally {
			remove_filter( 'jp4wc_security_feed_stale_after', $filter );
		}

		$this->assertTrue( JP4WC_Security_Feed::is_stale( array( 'generated_at' => '' ) ), 'Data without a date counts as out of date.' );
	}

	/**
	 * Old temporary files are removed by the next sync; recent ones may belong to a sync running now.
	 */
	public function test_old_temporary_files_are_removed() {
		$this->sync_successfully();
		$old    = JP4WC_Security_Feed::directory_path() . '/plugin.json.old.tmp';
		$recent = JP4WC_Security_Feed::directory_path() . '/plugin.json.recent.tmp';
		touch( $old, time() - 2 * HOUR_IN_SECONDS ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture.
		touch( $recent ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test fixture.

		JP4WC_Security_Feed::sync();

		$this->assertFileDoesNotExist( $old );
		$this->assertFileExists( $recent );
		wp_delete_file( $recent );
	}

	/**
	 * Turning the check off removes the events and the data, and a sync that
	 * still runs makes no request.
	 */
	public function test_turning_off_removes_everything_and_stops_requests() {
		$this->sync_successfully();
		wp_schedule_single_event( time(), JP4WC_Security_Feed::RESCAN_HOOK );
		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ), 'Turned on in setUp().' );

		update_option( JP4WC_Security_Feed::SETTING, '' );

		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RESCAN_HOOK ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );

		JP4WC_Security_Feed::sync();
		JP4WC_Security_Feed::rescan();
		JP4WC_Security_Feed::ensure_schedule();

		$this->assertSame( array(), $this->requests );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertNull( JP4WC_Security_Feed::get_findings() );
	}

	/**
	 * Turning the check on schedules the daily sync.
	 */
	public function test_turning_on_schedules_the_daily_sync() {
		update_option( JP4WC_Security_Feed::SETTING, '' );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );

		update_option( JP4WC_Security_Feed::SETTING, '1' );

		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertSame( 'daily', wp_get_schedule( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertSame( array(), $this->requests, 'Scheduling makes no request.' );
	}

	/**
	 * The check is on by default: install adds the setting and schedules the sync.
	 */
	public function test_install_turns_the_check_on_by_default() {
		delete_option( JP4WC_Security_Feed::SETTING );
		wp_clear_scheduled_hook( JP4WC_Security_Feed::SYNC_HOOK );
		$this->assertTrue( JP4WC_Security_Feed::is_enabled(), 'No setting means on.' );

		delete_transient( 'jp4wc_installing' );
		JP4WC_Install::install();

		$this->assertSame( '1', get_option( JP4WC_Security_Feed::SETTING ) );
		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
	}

	/**
	 * Install (and every update) keeps a check that was turned off off, and clears what is left.
	 */
	public function test_install_keeps_the_check_off() {
		$this->sync_successfully();
		update_option( JP4WC_Security_Feed::SETTING, '' );
		// Left over, for example by a request that ran while the check was turned off.
		wp_schedule_event( time(), 'daily', JP4WC_Security_Feed::SYNC_HOOK );
		update_option( JP4WC_Security_Feed::STATE_OPTION, array( 'version' => 'left over' ), false );

		delete_transient( 'jp4wc_installing' );
		JP4WC_Install::install();

		$this->assertSame( '', get_option( JP4WC_Security_Feed::SETTING ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
	}

	/**
	 * Admin requests put back a missing daily event while the check is on.
	 */
	public function test_admin_init_restores_the_schedule() {
		wp_clear_scheduled_hook( JP4WC_Security_Feed::SYNC_HOOK );

		JP4WC_Security_Feed::ensure_schedule();

		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertNotFalse( has_action( 'admin_init', array( 'JP4WC_Security_Feed', 'ensure_schedule' ) ) );
		$this->assertNotFalse( has_action( JP4WC_Security_Feed::SYNC_HOOK, array( 'JP4WC_Security_Feed', 'sync' ) ) );
		$this->assertNotFalse( has_action( JP4WC_Security_Feed::RESCAN_HOOK, array( 'JP4WC_Security_Feed', 'rescan' ) ) );
	}

	/**
	 * Number of scheduled events of a hook.
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

	/**
	 * A change of the installed software schedules one match, once there is data.
	 *
	 * The actions are not fired here: WordPress and WooCommerce act on them too.
	 */
	public function test_software_changes_schedule_one_match() {
		foreach ( array( 'upgrader_process_complete', 'activated_plugin', 'deleted_plugin', 'switch_theme', 'deleted_theme' ) as $hook ) {
			$this->assertSame( 10, has_action( $hook, array( 'JP4WC_Security_Feed', 'schedule_rescan' ) ), $hook );
		}

		JP4WC_Security_Feed::schedule_rescan();
		$this->assertSame( 0, self::count_events( JP4WC_Security_Feed::RESCAN_HOOK ), 'Nothing to match before the first sync.' );

		$this->sync_successfully();
		JP4WC_Security_Feed::schedule_rescan();
		JP4WC_Security_Feed::schedule_rescan();
		$this->assertSame( 1, self::count_events( JP4WC_Security_Feed::RESCAN_HOOK ), 'Many changes at once run one match.' );

		wp_clear_scheduled_hook( JP4WC_Security_Feed::RESCAN_HOOK );
		update_option( JP4WC_Security_Feed::SETTING, '' );
		JP4WC_Security_Feed::schedule_rescan();
		$this->assertSame( 0, self::count_events( JP4WC_Security_Feed::RESCAN_HOOK ), 'Nothing is matched while the check is off.' );
	}

	/**
	 * The scheduled match records the software installed now.
	 */
	public function test_rescan_records_the_current_versions() {
		$this->sync_successfully();
		$this->installed['core']['wordpress'][0]['version'] = '6.5.4';

		do_action( JP4WC_Security_Feed::RESCAN_HOOK );

		$findings = JP4WC_Security_Feed::get_findings();
		$core     = wp_list_filter( $findings['items'], array( 'slug' => 'wordpress' ) );
		$this->assertSame( '00000000-0000-4000-8000-000000000202', reset( $core )['vulnerabilities'][0]['id'] );
		$this->assertSame( array(), $this->requests, 'Matching makes no request.' );
	}
}
