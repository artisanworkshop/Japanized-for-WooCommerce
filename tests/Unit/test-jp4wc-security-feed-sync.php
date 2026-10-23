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
	 * Local checks that threw, by ID: none is expected (#258).
	 *
	 * @var array<string, Throwable>
	 */
	private $errors = array();

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
		$this->errors = array();
		add_action( 'jp4wc_security_check_error', array( $this, 'record_error' ), 10, 2 );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_action( 'jp4wc_security_check_error', array( $this, 'record_error' ), 10 );
		$this->assertSame( array(), array_map( 'get_class', $this->errors ), 'No local check threw.' );
		remove_filter( 'pre_http_request', array( $this, 'answer' ), 10 );
		remove_filter( 'jp4wc_security_installed_software', array( $this, 'installed' ) );
		JP4WC_Security_Feed::purge();
		update_option( JP4WC_Security_Feed::SETTING, '1' );
		parent::tearDown();
	}

	/**
	 * Records a local check that threw (jp4wc_security_check_error).
	 *
	 * @param string    $check_id Check ID.
	 * @param Throwable $error    What it threw.
	 */
	public function record_error( $check_id, $error ) {
		$this->errors[ $check_id ] = $error;
	}

	/**
	 * Returns the keys of the requests to the site whose results are kept.
	 *
	 * @return string[]
	 */
	private function probe_results_left() {
		return array_values(
			array_filter(
				JP4WC_Security_Checks::probe_keys(),
				static function ( $key ) {
					return false !== get_option( JP4WC_Security_Probe::option_name( $key ) );
				}
			)
		);
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
	 * The local checks' requests to the site itself are not recorded: they
	 * fail, so the checks that need them are unknown.
	 *
	 * @param false|array|WP_Error $pre  Response so far.
	 * @param array                $args Request arguments.
	 * @param string               $url  URL.
	 * @return array|WP_Error
	 */
	public function answer( $pre, $args, $url ) {
		if ( wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return new WP_Error( 'http_request_failed', 'The site cannot connect to itself in this test.' );
		}
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
		foreach ( $this->requests as $request ) {
			$this->assertSame( 0, $request['args']['redirection'], 'Redirects are not followed (G1-3).' );
		}
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
	 * A stored file that is not the one the state records is downloaded again,
	 * as when two syncs ran at once (G1-2).
	 */
	public function test_stored_file_that_changed_is_downloaded_again() {
		$this->sync_successfully();
		copy( self::stored( 'core' ), self::stored( 'theme' ) );

		JP4WC_Security_Feed::sync();

		$this->assertSame( array( 'manifest.json', 'vuln-themes.json.gz' ), $this->requested_files() );
		$this->assertFileEquals( TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/vuln-themes.json', self::stored( 'theme' ) );
		$this->assertSame( hash_file( 'sha256', self::stored( 'theme' ) ), JP4WC_Security_Feed::get_state()['files']['theme']['json_sha256'] );
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
			'cut in half'            => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, intdiv( strlen( $gzip ), 2 ) );
				},
			),
			'trailer cut off'        => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, -4 );
				},
			),
			'wrong CRC'              => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, -8 ) . "\0\0\0\0" . substr( $gzip, -4 );
				},
			),
			'flipped byte'           => array(
				static function ( $gzip ) {
					return substr( $gzip, 0, 40 ) . chr( ord( $gzip[40] ) ^ 0xff ) . substr( $gzip, 41 );
				},
			),
			'not gzip'               => array(
				static function () {
					return '{"schema_version":1,"type":"plugin","vulnerabilities":{}}';
				},
			),
			'gzip of broken JSON'    => array(
				static function () {
					return gzencode( '{"schema_version":1,"type":"plugin","vulnerabilities":{"a":[' );
				},
			),
			// The reader finds where these end; only decoding them shows they are broken (G1-5).
			'records not valid JSON' => array(
				static function () {
					return gzencode( '{"schema_version":1,"type":"plugin","vulnerabilities":{"sample-plugin":[{"id":"x"},]}}' );
				},
			),
			'records missing'        => array(
				static function () {
					return gzencode( '{"schema_version":1,"type":"plugin","vulnerabilities":{"sample-plugin":}}' );
				},
			),
			'no records'             => array(
				static function () {
					return gzencode( '{"schema_version":1,"type":"plugin","vulnerabilities":{"sample-plugin":[]}}' );
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
	 * Failing syncs record when they began to fail, and a successful one clears it (G2-1).
	 */
	public function test_failing_since_is_kept_until_a_sync_succeeds() {
		$this->responses['manifest.json']['response']['code'] = 503;

		JP4WC_Security_Feed::sync();
		$first = JP4WC_Security_Feed::get_state()['failing_since'];
		$this->assertGreaterThan( 0, $first );

		$state                  = JP4WC_Security_Feed::get_state();
		$state['failing_since'] = $first - DAY_IN_SECONDS;
		update_option( JP4WC_Security_Feed::STATE_OPTION, $state, false );
		JP4WC_Security_Feed::sync();
		$this->assertSame( $first - DAY_IN_SECONDS, JP4WC_Security_Feed::get_state()['failing_since'], 'A later failure keeps the first one.' );

		$this->responses['manifest.json']['response']['code'] = 200;
		JP4WC_Security_Feed::sync();
		$this->assertSame( 0, JP4WC_Security_Feed::get_state()['failing_since'] );
	}

	/**
	 * Without data, the check counts as unavailable once the syncs have failed for longer than the stale age (G2-1).
	 */
	public function test_no_data_for_days_is_unavailable() {
		$this->assertFalse( JP4WC_Security_Feed::is_unavailable(), 'Nothing tried yet.' );

		update_option( JP4WC_Security_Feed::STATE_OPTION, array( 'failing_since' => time() - DAY_IN_SECONDS ), false );
		$this->assertFalse( JP4WC_Security_Feed::is_unavailable() );

		update_option( JP4WC_Security_Feed::STATE_OPTION, array( 'failing_since' => time() - 4 * DAY_IN_SECONDS ), false );
		$this->assertTrue( JP4WC_Security_Feed::is_unavailable() );

		$this->sync_successfully();
		$state                  = JP4WC_Security_Feed::get_state();
		$state['failing_since'] = time() - 4 * DAY_IN_SECONDS;
		update_option( JP4WC_Security_Feed::STATE_OPTION, $state, false );
		$this->assertFalse( JP4WC_Security_Feed::is_unavailable(), 'With data, its age tells instead (is_stale).' );
	}

	/**
	 * Deactivating the plugin removes its events; activation schedules them again (G2-6).
	 */
	public function test_deactivation_clears_the_events() {
		wp_schedule_single_event( time(), JP4WC_Security_Feed::RESCAN_HOOK );
		wp_schedule_single_event( time(), JP4WC_Security_Feed::RECHECK_HOOK );
		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );

		jp4wc_on_deactivation();

		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RESCAN_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
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
	 * V-03 passes on current oldest supported versions only: out-of-date data
	 * makes the pass unknown, as for V-01 (A2).
	 */
	public function test_out_of_date_data_makes_v03_unknown() {
		// The sample was generated on 2026-10-20: keep its age out of the test.
		$keep = static function () {
			return 100 * YEAR_IN_SECONDS;
		};
		add_filter( 'jp4wc_security_feed_stale_after', $keep );
		try {
			$this->sync_successfully();
			$v03 = wp_list_filter( JP4WC_Security_Feed::get_checks()['checks'], array( 'check_id' => 'V-03' ) );
			$this->assertSame( 'pass', reset( $v03 )['status'], 'PHP, WordPress and WooCommerce of the test are supported.' );

			$manifest          = self::manifest();
			$manifest['stale'] = true;
			$this->serve_manifest( $manifest );
			JP4WC_Security_Feed::sync();

			$v03 = wp_list_filter( JP4WC_Security_Feed::get_checks()['checks'], array( 'check_id' => 'V-03' ) );
			$this->assertSame( 'unknown', reset( $v03 )['status'] );
		} finally {
			remove_filter( 'jp4wc_security_feed_stale_after', $keep );
		}
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
		JP4WC_Security_Feed::request_recheck();
		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ), 'Turned on in setUp().' );

		update_option( JP4WC_Security_Feed::SETTING, '' );

		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RESCAN_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );

		JP4WC_Security_Feed::sync();
		JP4WC_Security_Feed::rescan();
		JP4WC_Security_Feed::recheck();
		JP4WC_Security_Feed::ensure_schedule();

		$this->assertSame( array(), $this->requests );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertNull( JP4WC_Security_Feed::get_findings() );
	}

	/**
	 * Turns the check off the way another request does while this one runs:
	 * the database changes and the other request purges, but the options
	 * this process loaded still say on.
	 */
	public function turn_off_from_another_request() {
		global $wpdb;
		JP4WC_Security_Feed::purge();
		$updated = $wpdb->update( $wpdb->options, array( 'option_value' => '' ), array( 'option_name' => JP4WC_Security_Feed::SETTING ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Leaves this process's option cache as it was.
		$this->assertSame( 1, $updated );
		$this->assertTrue( JP4WC_Security_Feed::is_enabled(), 'This process still sees the check on.' );
	}

	/**
	 * A check turned off during a sync stays off with nothing stored (R1-1).
	 */
	public function test_turning_off_during_a_sync_leaves_nothing() {
		// Turned off once the manifest is requested: the files are downloaded after it.
		$turn_off = function ( $pre, $args, $url ) {
			if ( str_ends_with( $url, 'manifest.json' ) ) {
				$this->turn_off_from_another_request();
			}
			return $pre;
		};
		add_filter( 'pre_http_request', $turn_off, 5, 3 );
		try {
			JP4WC_Security_Feed::sync();
		} finally {
			remove_filter( 'pre_http_request', $turn_off, 5 );
		}

		$this->assertCount( 4, $this->requests, 'The files were downloaded after the check was turned off.' );
		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
	}

	/**
	 * A failed sync of a check turned off meanwhile stores no state either (R1-1).
	 *
	 * With no files there is nothing to match, so only the sync itself can
	 * notice that the check is off.
	 */
	public function test_turning_off_during_a_failed_sync_leaves_nothing() {
		$this->responses['manifest.json']['response']['code'] = 503;
		$turn_off = function ( $pre ) {
			$this->turn_off_from_another_request();
			return $pre;
		};
		add_filter( 'pre_http_request', $turn_off, 5 );
		try {
			JP4WC_Security_Feed::sync();
		} finally {
			remove_filter( 'pre_http_request', $turn_off, 5 );
		}

		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
	}

	/**
	 * A check turned off during a match stores no findings (R1-1).
	 */
	public function test_turning_off_during_a_match_leaves_nothing() {
		$this->sync_successfully();
		delete_option( JP4WC_Security_Feed::FINDINGS_OPTION );
		$turn_off = function ( $installed ) {
			$this->turn_off_from_another_request();
			return $installed;
		};
		add_filter( 'jp4wc_security_installed_software', $turn_off, 5 );
		try {
			JP4WC_Security_Feed::rescan();
		} finally {
			remove_filter( 'jp4wc_security_installed_software', $turn_off, 5 );
		}

		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertSame( array(), $this->probe_results_left(), 'The results of the requests to the site itself go too.' );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );
	}

	/**
	 * Data written just as the check is turned off is removed (G1-6).
	 *
	 * The other request turns the check off between this run's last look at
	 * the setting and its write, which only a check after the write catches.
	 *
	 * @return array<string, array{string}>
	 */
	public function written_option_provider() {
		return array(
			'state'    => array( JP4WC_Security_Feed::STATE_OPTION ),
			'probes'   => array( JP4WC_Security_Probe::option_name( 'A-02' ) ),
			'findings' => array( JP4WC_Security_Feed::FINDINGS_OPTION ),
		);
	}

	/**
	 * Turning off while an option is written leaves nothing.
	 *
	 * @dataProvider written_option_provider
	 *
	 * @param string $option Option being written.
	 */
	public function test_turning_off_while_writing_leaves_nothing( $option ) {
		$turn_off = function ( $value ) {
			$this->turn_off_from_another_request();
			return $value;
		};
		add_filter( 'pre_update_option_' . $option, $turn_off );
		try {
			JP4WC_Security_Feed::sync();
		} finally {
			remove_filter( 'pre_update_option_' . $option, $turn_off );
		}

		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertSame( array(), $this->probe_results_left() );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );
	}

	/**
	 * Uninstall in another request during a sync leaves nothing behind (G1-1).
	 */
	public function test_uninstall_during_a_sync_leaves_nothing() {
		$uninstall = function ( $pre, $args, $url ) {
			global $wpdb;
			if ( str_ends_with( $url, 'manifest.json' ) ) {
				JP4WC_Security_Feed::purge();
				// As uninstall does, in another request: this process still has the setting cached.
				$wpdb->delete( $wpdb->options, array( 'option_name' => JP4WC_Security_Feed::SETTING ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Leaves this process's option cache as it was.
			}
			return $pre;
		};
		add_filter( 'pre_http_request', $uninstall, 5, 3 );
		try {
			JP4WC_Security_Feed::sync();
		} finally {
			remove_filter( 'pre_http_request', $uninstall, 5 );
		}

		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );
	}

	/**
	 * A site whose setting row is gone still has the check on, and a run stores the default (G1-1).
	 */
	public function test_missing_setting_row_keeps_the_check_on() {
		delete_option( JP4WC_Security_Feed::SETTING );

		JP4WC_Security_Feed::sync();

		$this->assertSame( '1', get_option( JP4WC_Security_Feed::SETTING ) );
		$this->assertSame( '', JP4WC_Security_Feed::get_state()['last_error'] );
		$this->assertNotNull( JP4WC_Security_Feed::get_findings() );
	}

	/**
	 * Uninstall removes the setting, the events, the options and the files (R1-3).
	 */
	public function test_uninstall_removes_everything() {
		$this->sync_successfully();
		wp_schedule_single_event( time(), JP4WC_Security_Feed::RESCAN_HOOK );
		JP4WC_Security_Feed::request_recheck();
		$this->assertDirectoryExists( JP4WC_Security_Feed::directory_path() );

		$this->assertNotSame( array(), $this->probe_results_left() );

		JP4WC_Security_Feed::uninstall();

		$this->assertFalse( get_option( JP4WC_Security_Feed::SETTING ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::STATE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertSame( array(), $this->probe_results_left() );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RESCAN_HOOK ) );
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_DONE_OPTION ) );
		$this->assertDirectoryDoesNotExist( JP4WC_Security_Feed::directory_path() );
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
	 * Every request puts back a missing daily event while the check is on,
	 * as after a reactivation that no admin page follows (G3-1).
	 */
	public function test_init_restores_the_schedule() {
		jp4wc_on_deactivation();
		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );

		// The init callback; firing init itself would register WooCommerce's blocks twice.
		JP4WC_Security_Feed::ensure_schedule();

		$this->assertNotFalse( wp_next_scheduled( JP4WC_Security_Feed::SYNC_HOOK ) );
		$this->assertNotFalse( has_action( 'init', array( 'JP4WC_Security_Feed', 'ensure_schedule' ) ) );
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
	 * A change of the installed software schedules one match, also before the
	 * first sync: the local checks need no vulnerability data (#258).
	 *
	 * The actions are not fired here: WordPress and WooCommerce act on them too.
	 */
	public function test_software_changes_schedule_one_match() {
		foreach ( array( 'upgrader_process_complete', 'activated_plugin', 'deactivated_plugin', 'deleted_plugin', 'switch_theme', 'deleted_theme' ) as $hook ) {
			$this->assertSame( 10, has_action( $hook, array( 'JP4WC_Security_Feed', 'schedule_rescan' ) ), $hook );
		}

		JP4WC_Security_Feed::schedule_rescan();
		JP4WC_Security_Feed::schedule_rescan();
		$this->assertSame( 1, self::count_events( JP4WC_Security_Feed::RESCAN_HOOK ), 'Many changes at once run one match, data or not.' );

		wp_clear_scheduled_hook( JP4WC_Security_Feed::RESCAN_HOOK );
		update_option( JP4WC_Security_Feed::SETTING, '' );
		JP4WC_Security_Feed::schedule_rescan();
		$this->assertSame( 0, self::count_events( JP4WC_Security_Feed::RESCAN_HOOK ), 'Nothing is matched while the check is off.' );
	}

	/**
	 * A stored file that went missing keeps the previous findings until a sync
	 * downloads it again, rather than dropping its type (G3-2).
	 */
	public function test_missing_file_keeps_the_previous_findings() {
		$this->sync_successfully();
		$before = JP4WC_Security_Feed::get_findings();
		$this->assertContains( 'sample-plugin', wp_list_pluck( $before['items'], 'slug' ) );
		wp_delete_file( self::stored( 'plugin' ) );

		JP4WC_Security_Feed::rescan();

		$vulnerability_keys = array_flip( array( 'version', 'generated_at', 'stale', 'copyrights', 'attribution', 'count', 'max_severity', 'items' ) );
		$this->assertSame( array_intersect_key( $before, $vulnerability_keys ), array_intersect_key( JP4WC_Security_Feed::get_findings(), $vulnerability_keys ) );
		$this->assertSame( 'fail', wp_list_filter( JP4WC_Security_Feed::get_checks()['checks'], array( 'check_id' => 'V-01' ) )[0]['status'], 'V-01 keeps counting the previous findings.' );

		JP4WC_Security_Feed::sync();

		$this->assertFileExists( self::stored( 'plugin' ) );
		$this->assertContains( 'sample-plugin', wp_list_pluck( JP4WC_Security_Feed::get_findings()['items'], 'slug' ) );
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

	/**
	 * A re-check is one WP-Cron event of its own, however often it is asked for (#259).
	 */
	public function test_recheck_is_scheduled_once() {
		$this->assertSame( 10, has_action( JP4WC_Security_Feed::RECHECK_HOOK, array( 'JP4WC_Security_Feed', 'recheck' ) ) );
		JP4WC_Security_Feed::schedule_rescan();

		$requested_at = JP4WC_Security_Feed::request_recheck();
		JP4WC_Security_Feed::request_recheck();

		$this->assertSame( 1, self::count_events( JP4WC_Security_Feed::RECHECK_HOOK ), 'Not dropped as a copy of the automatic rescan, nor scheduled twice.' );
		$this->assertSame( 1, self::count_events( JP4WC_Security_Feed::RESCAN_HOOK ) );
		$this->assertEqualsWithDelta( time(), $requested_at, 5 );
		$this->assertGreaterThanOrEqual( $requested_at, JP4WC_Security_Feed::recheck_requested_at() );
	}

	/**
	 * A re-check requests the site again instead of using the kept results,
	 * which the automatic rescan uses for 12 hours (#258 G1-5, #259).
	 */
	public function test_recheck_requests_the_site_again() {
		$this->sync_successfully();
		$kept = $this->probe_results_left();
		$this->assertNotSame( array(), $kept );
		foreach ( $kept as $key ) {
			$result           = get_option( JP4WC_Security_Probe::option_name( $key ) );
			$result['status'] = 'pass';
			update_option( JP4WC_Security_Probe::option_name( $key ), $result, false );
		}
		$statuses = static function () use ( $kept ) {
			return array_map(
				static function ( $key ) {
					return get_option( JP4WC_Security_Probe::option_name( $key ) )['status'];
				},
				$kept
			);
		};

		JP4WC_Security_Feed::rescan();
		$this->assertSame( array_fill( 0, count( $kept ), 'pass' ), $statuses(), 'The automatic rescan uses the kept results.' );

		do_action( JP4WC_Security_Feed::RECHECK_HOOK );

		$this->assertSame( array_fill( 0, count( $kept ), 'unknown' ), $statuses(), 'Requested again: the site cannot reach itself in this test.' );
		$this->assertSame( array(), $this->requested_files(), 'With stored data, the feed is not downloaded.' );
		$this->assertGreaterThanOrEqual( time() - 60, JP4WC_Security_Feed::get_checks()['checked_at'] );
	}

	/**
	 * A re-check records the request it ran for; the automatic rescan does not (R1-1).
	 */
	public function test_recheck_records_the_request_it_ran_for() {
		$this->sync_successfully();
		$requested_at = time() - 30;
		update_option( JP4WC_Security_Feed::RECHECK_OPTION, $requested_at, false );

		JP4WC_Security_Feed::rescan();
		$this->assertSame( 0, JP4WC_Security_Feed::recheck_done_at(), 'The automatic rescan is not the re-check.' );

		JP4WC_Security_Feed::recheck();
		$this->assertSame( $requested_at, JP4WC_Security_Feed::recheck_done_at() );
	}

	/**
	 * Asking for a re-check while another request turns the check off leaves
	 * nothing behind; a re-check event that still runs while the check is off
	 * removes what is left (R1-2).
	 */
	public function test_recheck_turned_off_meanwhile_leaves_nothing() {
		$this->sync_successfully();
		$this->turn_off_from_another_request();

		$this->assertSame( 0, JP4WC_Security_Feed::request_recheck() );

		$this->assertFalse( wp_next_scheduled( JP4WC_Security_Feed::RECHECK_HOOK ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_OPTION ) );

		// A later WP-Cron process sees the check off, with a re-check left behind from before.
		wp_cache_delete( 'alloptions', 'options' );
		$this->assertFalse( JP4WC_Security_Feed::is_enabled() );
		update_option( JP4WC_Security_Feed::RECHECK_OPTION, time(), false );
		update_option( JP4WC_Security_Feed::FINDINGS_OPTION, array( 'checked_at' => time() ), false );

		JP4WC_Security_Feed::recheck();

		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
		$this->assertSame( array(), $this->requests );
	}

	/**
	 * A check turned off while a re-check runs keeps nothing of it (R1-2).
	 */
	public function test_turning_off_during_a_recheck_leaves_nothing() {
		$this->sync_successfully();
		update_option( JP4WC_Security_Feed::RECHECK_OPTION, time(), false );
		$turn_off = function ( $installed ) {
			$this->turn_off_from_another_request();
			return $installed;
		};
		add_filter( 'jp4wc_security_installed_software', $turn_off, 5 );
		try {
			JP4WC_Security_Feed::recheck();
		} finally {
			remove_filter( 'jp4wc_security_installed_software', $turn_off, 5 );
		}

		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_DONE_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::RECHECK_OPTION ) );
		$this->assertFalse( get_option( JP4WC_Security_Feed::FINDINGS_OPTION ) );
	}

	/**
	 * Without stored vulnerability data, a re-check downloads the feed first (#259).
	 */
	public function test_recheck_without_data_downloads_the_feed() {
		JP4WC_Security_Feed::recheck();

		$this->assertContains( 'manifest.json', $this->requested_files() );
		$this->assertNotNull( JP4WC_Security_Feed::get_findings() );
		$this->assertNotNull( JP4WC_Security_Feed::get_checks() );
	}

	/**
	 * The local checks run and are scored without vulnerability data, and the
	 * notice's findings stay empty (#258).
	 */
	public function test_local_checks_run_without_vulnerability_data() {
		$this->responses['manifest.json']['response']['code'] = 404;

		JP4WC_Security_Feed::sync();

		$this->assertNull( JP4WC_Security_Feed::get_findings(), 'The notice still sees no vulnerability data.' );
		$result = JP4WC_Security_Feed::get_checks();
		$this->assertNotNull( $result );
		$this->assertSame( array_keys( JP4WC_Security_Checks::CHECKS ), wp_list_pluck( $result['checks'], 'check_id' ) );
		$this->assertSame( 'unknown', $result['checks'][0]['status'], 'V-01 without data.' );
		$this->assertSame( 'unknown', $result['checks'][2]['status'], 'V-03 without the oldest supported versions.' );
		$this->assertSame( JP4WC_Security_Checks::score( $result['checks'] ), $result['score'] );
		$this->assertGreaterThanOrEqual( time() - 60, $result['checked_at'] );
	}

	/**
	 * The findings keep only the ID, requirement, severity and status of each
	 * check: no path, setting value or URL (#258).
	 */
	public function test_findings_keep_only_the_status_of_each_check() {
		$this->sync_successfully();

		$findings = get_option( JP4WC_Security_Feed::FINDINGS_OPTION );
		$this->assertCount( count( JP4WC_Security_Checks::CHECKS ), $findings['checks'] );
		foreach ( $findings['checks'] as $check ) {
			$this->assertSame( array( 'check_id', 'requirement', 'severity', 'status' ), array_keys( $check ) );
			$this->assertContains( $check['status'], array( 'pass', 'fail', 'unknown' ) );
			$this->assertArrayHasKey( $check['severity'], JP4WC_Security_Checks::POINTS );
			$this->assertMatchesRegularExpression( '/^[VADM]-[0-9]{2}$/D', $check['check_id'] );
		}
		$this->assertSame( array( 'total', 'vuln', 'admin', 'data', 'malware' ), array_keys( $findings['score'] ) );

		$v01 = $findings['checks'][0];
		$this->assertSame( 'V-01', $v01['check_id'] );
		$this->assertSame( 'fail', $v01['status'] );
		$this->assertSame( 'high', $v01['severity'], 'The most severe record found is High.' );

		$this->assertNotSame( array(), $this->probe_results_left() );
		foreach ( $this->probe_results_left() as $key ) {
			$this->assertSame( array( 'status', 'checked_at', 'url_hash' ), array_keys( get_option( JP4WC_Security_Probe::option_name( $key ) ) ), $key );
		}
	}

	/**
	 * The oldest supported versions of the manifest are stored for V-03.
	 */
	public function test_oldest_supported_versions_are_stored() {
		$this->sync_successfully();

		$this->assertSame(
			array(
				'php'         => '8.2',
				'wordpress'   => '6.7',
				'woocommerce' => '10.2',
			),
			JP4WC_Security_Feed::get_state()['eol']
		);
	}

	/**
	 * A manifest whose eol is missing or malformed is still used; V-03 is then unknown.
	 *
	 * @return array<string, array{callable, array}>
	 */
	public function eol_change_provider() {
		return array(
			'no eol'                 => array(
				static function ( $manifest ) {
					unset( $manifest['eol'] );
					return $manifest;
				},
				array(),
			),
			'eol not an object'      => array(
				static function ( $manifest ) {
					$manifest['eol'] = '8.2';
					return $manifest;
				},
				array(),
			),
			'one malformed entry'    => array(
				static function ( $manifest ) {
					$manifest['eol']['php']['min_supported'] = '8.x';
					return $manifest;
				},
				array(
					'wordpress'   => '6.7',
					'woocommerce' => '10.2',
				),
			),
			'one entry not a string' => array(
				static function ( $manifest ) {
					$manifest['eol']['woocommerce']['min_supported'] = 10.2;
					return $manifest;
				},
				array(
					'php'       => '8.2',
					'wordpress' => '6.7',
				),
			),
		);
	}

	/**
	 * A manifest with a missing or malformed eol keeps its vulnerability data.
	 *
	 * @dataProvider eol_change_provider
	 *
	 * @param callable $change   Changes the sample manifest.
	 * @param array    $expected Versions stored.
	 */
	public function test_malformed_eol_keeps_the_manifest( $change, $expected ) {
		$this->serve_manifest( $change( self::manifest() ) );

		JP4WC_Security_Feed::sync();

		$state = JP4WC_Security_Feed::get_state();
		$this->assertSame( '', $state['last_error'] );
		$this->assertSame( $expected, $state['eol'] );
		$this->assertNotNull( JP4WC_Security_Feed::get_findings() );
		$this->assertSame( 'unknown', JP4WC_Security_Feed::get_checks()['checks'][2]['status'] );
	}
}
