<?php
/**
 * Tests for the feed import: Artws_Sec_Feed_Updater with the builder and publisher.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Tests read and write local files directly.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- The lock row is read and written directly, as the plugin does.

/**
 * Artws_Sec_Feed_Updater_Test
 */
class Artws_Sec_Feed_Updater_Test extends Artws_Sec_Feed_Test_Case {

	/**
	 * Time of the first run in these tests (2026-10-22 03:00:00 UTC).
	 */
	const NOW = 1792638000;

	/**
	 * Served file names.
	 */
	const SERVED = array( 'manifest.json', 'vuln-core.json.gz', 'vuln-plugins.json.gz', 'vuln-themes.json.gz' );

	/**
	 * Runs one successful import of the V3 fixture.
	 */
	private function publish_fixture() {
		$this->mock_wordfence( self::fixture_feed() );
		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );
		remove_all_filters( 'pre_http_request' );
		$this->wordfence_requests = array();
		$this->set_up_http_blocker();
	}

	/**
	 * Restores the filter that blocks unexpected HTTP requests.
	 */
	private function set_up_http_blocker() {
		add_filter(
			'pre_http_request',
			static function ( $response, $args, $url ) {
				return false === $response ? new WP_Error( 'http_request_blocked', 'Unexpected HTTP request in a test: ' . $url ) : $response;
			},
			PHP_INT_MAX,
			3
		);
	}

	/**
	 * Bytes of every served file.
	 *
	 * @return array<string, string>
	 */
	private static function served_bytes() {
		$bytes = array();
		foreach ( self::SERVED as $name ) {
			$bytes[ $name ] = file_get_contents( self::feed_path( $name ) );
		}
		return $bytes;
	}

	/**
	 * The generated manifest and vulnerability files match the contract's schemas.
	 */
	public function test_generated_files_match_the_contract() {
		$this->mock_wordfence( self::fixture_feed() );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );

		$this->assertSame( self::SERVED, self::feed_directory_files() );
		$this->assert_matches_schema( self::read_manifest(), 'manifest' );
		foreach ( array( 'vuln-plugins.json.gz', 'vuln-themes.json.gz', 'vuln-core.json.gz' ) as $name ) {
			$this->assert_matches_schema( self::read_vulnerability_file( $name ), 'vuln-file' );
		}
	}

	/**
	 * The request goes to the production feed with the key as a bearer token, streamed to a file.
	 */
	public function test_requests_the_production_feed_with_the_api_key() {
		$this->mock_wordfence( self::fixture_feed() );

		Artws_Sec_Feed_Updater::update( self::NOW );

		$this->assertCount( 1, $this->wordfence_requests );
		$args = $this->wordfence_requests[0];
		$this->assertTrue( $args['stream'] );
		$this->assertSame( 'Bearer ' . self::API_KEY, $args['headers']['Authorization'] );
		$this->assertFileDoesNotExist( $args['filename'], 'The download is deleted after the run.' );
	}

	/**
	 * The fixture is split, filtered and normalized as the contract README describes.
	 */
	public function test_fixture_records_are_placed_by_type_and_slug() {
		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW );

		$plugins = self::read_vulnerability_file( 'vuln-plugins.json.gz' );
		$themes  = self::read_vulnerability_file( 'vuln-themes.json.gz' );
		$core    = self::read_vulnerability_file( 'vuln-core.json.gz' );

		$ids = static function ( $file ) {
			$ids = array();
			foreach ( (array) $file->vulnerabilities as $slug => $records ) {
				foreach ( $records as $record ) {
					$ids[] = $slug . ' ' . substr( $record->id, -2 );
				}
			}
			return $ids;
		};

		// 03 is informational; 02 affects two plugins and a theme.
		$this->assertSame( array( 'example-forms 01', 'example-forms 02', 'example-forms-pro 02', 'example-uploader 06' ), $ids( $plugins ) );
		$this->assertSame( array( 'example-theme 02', 'example-theme 04' ), $ids( $themes ) );
		$this->assertSame( array( 'wordpress 05' ), $ids( $core ) );

		$forms = $plugins->vulnerabilities->{'example-forms'};
		$this->assertSame( '2026-09-30T14:05:00Z', $forms[0]->published );
		$this->assertSame( '2026-10-01T03:00:00Z', $forms[1]->published );
		$this->assertNull( $plugins->vulnerabilities->{'example-uploader'}[0]->published, 'An unreadable time becomes null.' );
		$this->assertSame( '2026-08-15T09:00:00Z', $core->vulnerabilities->wordpress[0]->published );
		$this->assertNull( $themes->vulnerabilities->{'example-theme'}[1]->cvss );
		$this->assertSame(
			'https://www.wordfence.com/threat-intel/vulnerabilities/id/10000000-0000-4000-8000-000000000002',
			$plugins->vulnerabilities->{'example-forms-pro'}[0]->reference
		);
		$this->assertSame( 'Example Uploader (all versions) - Unauthenticated Arbitrary File Upload "{fixture}" [\\]', $plugins->vulnerabilities->{'example-uploader'}[0]->title );
	}

	/**
	 * The copyright holders of published records go into every file; the message key and
	 * holders of left-out records do not.
	 */
	public function test_copyrights_and_attribution() {
		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW );

		$manifest = self::read_manifest();
		$this->assertSame( array( 'defiant', 'mitre' ), array_keys( (array) $manifest->copyrights ) );
		$this->assertSame( 'Copyright 1999-2026 The MITRE Corporation', $manifest->copyrights->mitre->notice );
		$this->assertSame( Artws_Sec_Feed_Updater::ATTRIBUTION, $manifest->attribution );
		foreach ( array( 'vuln-plugins.json.gz', 'vuln-themes.json.gz', 'vuln-core.json.gz' ) as $name ) {
			$file = self::read_vulnerability_file( $name );
			$this->assertEquals( $manifest->copyrights, $file->copyrights, $name );
			$this->assertSame( $manifest->attribution, $file->attribution, $name );
		}
	}

	/**
	 * The manifest's sha256 is that of the served .json.gz bytes and count is the number of records in the file.
	 */
	public function test_manifest_sha256_and_count_match_the_files() {
		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW );

		$manifest = self::read_manifest();
		$expected = array(
			'plugin' => 4,
			'theme'  => 2,
			'core'   => 1,
		);
		foreach ( $expected as $type => $count ) {
			$entry = $manifest->files->{$type};
			$bytes = file_get_contents( self::feed_path( $entry->file ) );
			$file  = self::read_vulnerability_file( $entry->file );

			$this->assertSame( hash( 'sha256', $bytes ), $entry->sha256, $type );
			$this->assertNotSame( hash( 'sha256', gzdecode( $bytes ) ), $entry->sha256, 'Not the hash of the decompressed JSON.' );
			$this->assertSame( $count, $entry->count, $type );
			$this->assertSame( $count, array_sum( array_map( 'count', (array) $file->vulnerabilities ) ), $type );
			$this->assertSame( $type, $file->type );
		}
	}

	/**
	 * Version, times and EOL in the manifest; files readable by the web server.
	 */
	public function test_manifest_header_and_permissions() {
		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW );

		$manifest = self::read_manifest();
		$this->assertSame( 1, $manifest->schema_version );
		$this->assertSame( '20261022.030000', $manifest->version );
		$this->assertSame( '2026-10-22T03:00:00Z', $manifest->generated_at );
		$this->assertFalse( $manifest->stale );
		$this->assertSame( '8.2', $manifest->eol->php->min_supported );
		$this->assertSame( '2026-10-22T03:00:00Z', self::read_vulnerability_file( 'vuln-core.json.gz' )->generated_at );
		foreach ( self::SERVED as $name ) {
			$this->assertSame( '0644', substr( sprintf( '%o', fileperms( self::feed_path( $name ) ) ), -4 ), $name );
		}
	}

	/**
	 * A gzip-compressed download (PHP streams transport) is read too.
	 */
	public function test_reads_a_gzip_compressed_download() {
		$this->mock_wordfence( gzencode( self::fixture_feed() ) );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );
		$this->assertSame( 4, self::read_manifest()->files->plugin->count );
	}

	/**
	 * Slugs that are numbers stay object keys, even when PHP would see the map as a list.
	 *
	 * Checked on the JSON text: opis/json-schema receives numeric property
	 * names of a decoded object as integers and rejects them.
	 */
	public function test_numeric_slugs_stay_object_keys() {
		$this->mock_wordfence(
			self::v3_feed(
				array(
					self::v3_record( 1, array( self::v3_software( 'plugin', '0' ) ) ),
					self::v3_record( 2, array( self::v3_software( 'plugin', '1' ) ) ),
					self::v3_record( 3, array( self::v3_software( 'theme', 'generated-theme' ) ) ),
					self::v3_record( 4, array( self::v3_software( 'core', 'wordpress' ) ) ),
				)
			)
		);

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );
		$json = gzdecode( file_get_contents( self::feed_path( 'vuln-plugins.json.gz' ) ) );
		$this->assertStringContainsString( '"vulnerabilities":{"0":[{', $json );
		$this->assertStringContainsString( '"slug":"1"', $json );
		$this->assertSame( 2, self::read_manifest()->files->plugin->count );
	}

	/**
	 * Failures that must leave the published files alone.
	 *
	 * @return array<string, array{callable}>
	 */
	public function failure_provider() {
		return array(
			'request error'        => array(
				static function ( $test ) {
					$test->mock_wordfence( new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );
				},
				'The Wordfence request failed',
			),
			'HTTP 500'             => array(
				static function ( $test ) {
					$test->mock_wordfence( 'Internal Server Error', 500 );
				},
				'HTTP 500',
			),
			'HTTP 401'             => array(
				static function ( $test ) {
					$test->mock_wordfence( '{"message":"Unauthorized"}', 401 );
				},
				'HTTP 401',
			),
			'empty body'           => array(
				static function ( $test ) {
					$test->mock_wordfence( '' );
				},
				'empty body',
			),
			'truncated JSON'       => array(
				static function ( $test ) {
					$test->mock_wordfence( substr( self::fixture_feed(), 0, 2000 ) );
				},
				'The feed ends',
			),
			'HTML instead of JSON' => array(
				static function ( $test ) {
					$test->mock_wordfence( '<html><body>Maintenance</body></html>' );
				},
				'not a JSON object',
			),
			'empty feed'           => array(
				static function ( $test ) {
					$test->mock_wordfence( '{}' );
				},
				'would have no records',
			),
			'no API key'           => array(
				static function ( $test ) {
					remove_all_filters( 'artws_sec_wordfence_api_key' );
					$test->mock_wordfence( self::fixture_feed() );
				},
				'ARTWS_SEC_WORDFENCE_API_KEY',
			),
			'no theme records'     => array(
				static function ( $test ) {
					$test->mock_wordfence( self::generated_feed( 50, 0, 1 ) );
				},
				'The theme file would have no records.',
			),
			'no copyright holders' => array(
				static function ( $test ) {
					$records = array();
					foreach ( array(
						'plugin' => 'a',
						'theme'  => 'b',
						'core'   => 'wordpress',
					) as $type => $slug ) {
						$records[] = self::v3_record( count( $records ) + 1, array( self::v3_software( $type, $slug ) ), array( 'copyrights' => array() ) );
					}
					$test->mock_wordfence( self::v3_feed( $records ) );
				},
				'No copyright holders',
			),
		);
	}

	/**
	 * After a failure the previous files stay byte for byte, and the manifest says stale.
	 *
	 * @dataProvider failure_provider
	 *
	 * @param callable $arrange Sets up the failing download.
	 * @param string   $message Part of the recorded error.
	 */
	public function test_failure_keeps_the_previous_files_and_marks_them_stale( $arrange, $message ) {
		$this->publish_fixture();
		$before   = self::served_bytes();
		$manifest = self::read_manifest();

		$arrange( $this );
		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS ) );

		$after = self::served_bytes();
		unset( $before['manifest.json'], $after['manifest.json'] );
		$this->assertSame( $before, $after, 'The vulnerability files are unchanged.' );
		$this->assertSame( self::SERVED, self::feed_directory_files(), 'No temporary files are left.' );

		$stale = self::read_manifest();
		$this->assert_matches_schema( $stale, 'manifest' );
		$this->assertTrue( $stale->stale );
		$this->assertSame( $manifest->version, $stale->version );
		$this->assertSame( $manifest->generated_at, $stale->generated_at, 'generated_at stays the time of the data.' );
		$this->assertEquals( $manifest->files, $stale->files );

		$status = Artws_Sec_Feed_Updater::get_status();
		$this->assertSame( 'failure', $status['result'] );
		$this->assertStringContainsString( $message, $status['message'] );
		$this->assertStringNotContainsString( self::API_KEY, $status['message'] );
	}

	/**
	 * A failure before anything was published leaves nothing to serve.
	 */
	public function test_first_failure_writes_no_manifest() {
		$this->mock_wordfence( 'Bad Gateway', 502 );

		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW ) );

		$this->assertSame( array(), self::feed_directory_files() );
		$this->assertNull( Artws_Sec_Feed_Updater::get_state() );
		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::get_status()['result'] );
	}

	/**
	 * The next successful run clears stale and publishes new files.
	 */
	public function test_success_after_failure_clears_stale() {
		$this->publish_fixture();
		$this->mock_wordfence( 'Internal Server Error', 500 );
		Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS );
		remove_all_filters( 'pre_http_request' );
		$this->set_up_http_blocker();

		$this->mock_wordfence( self::fixture_feed() );
		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW + 2 * DAY_IN_SECONDS ) );

		$manifest = self::read_manifest();
		$this->assertFalse( $manifest->stale );
		$this->assertSame( '20261024.030000', $manifest->version );
	}

	/**
	 * Records that fail the contract are left out one by one and counted; the rest is published.
	 */
	public function test_invalid_records_are_left_out_and_counted() {
		$bad = array(
			self::v3_record( 901, array( self::v3_software( 'plugin', 'bad-version', array( 'patched_versions' => array( '1.0 beta' ) ) ) ) ),
			self::v3_record(
				902,
				array(
					self::v3_software( 'plugin', 'good-half' ),
					self::v3_software( 'plugin', 'bad/slug' ),
				)
			),
		);
		// 100 valid plugin records + 1 theme + 1 core, then 2 bad ones and 1 good: 2 of 105 = 1.9%.
		$this->mock_wordfence( self::generated_feed( 100, 1, 1, $bad ) );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );

		$manifest = self::read_manifest();
		$plugins  = self::read_vulnerability_file( 'vuln-plugins.json.gz' );
		$this->assertSame( 101, $manifest->files->plugin->count );
		$this->assertObjectHasProperty( 'good-half', $plugins->vulnerabilities );
		$this->assertObjectNotHasProperty( 'bad-version', $plugins->vulnerabilities );
		$this->assertObjectNotHasProperty( 'bad/slug', $plugins->vulnerabilities );
		$this->assert_matches_schema( $plugins, 'vuln-file' );

		$stats = Artws_Sec_Feed_Updater::get_status()['stats'];
		$this->assertSame( 105, $stats['candidates'] );
		$this->assertSame( 2, $stats['excluded'] );
		$this->assertSame(
			array(
				'patched_versions[0]' => 1,
				'software.slug'       => 1,
			),
			$stats['excluded_reasons']
		);
		$this->assertSame(
			array(
				array(
					'id'     => '20000000-0000-4000-8000-000000000901',
					'slug'   => 'bad-version',
					'reason' => 'patched_versions[0]',
				),
				array(
					'id'     => '20000000-0000-4000-8000-000000000902',
					'slug'   => 'bad/slug',
					'reason' => 'software.slug',
				),
			),
			$stats['excluded_samples']
		);
	}

	/**
	 * Undecodable records and records with uncarriable copyrights are left out too.
	 */
	public function test_undecodable_and_uncarriable_records_are_left_out() {
		$feed = self::generated_feed(
			200,
			1,
			1,
			array(
				self::v3_record(
					903,
					array( self::v3_software( 'plugin', 'http-license' ) ),
					array(
						'copyrights' => array(
							'defiant' => array(
								'notice'      => 'Copyright Defiant',
								'license'     => 'License',
								'license_url' => 'http://www.wordfence.com/',
							),
						),
					)
				),
			)
		);
		// A member whose value is not an object.
		$feed = substr( $feed, 0, -1 ) . ',"20000000-0000-4000-8000-000000000904":"not a record"}';
		$this->mock_wordfence( $feed );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );

		$stats = Artws_Sec_Feed_Updater::get_status()['stats'];
		$this->assertSame( 2, $stats['excluded'] );
		$this->assertSame(
			array(
				'copyrights.defiant.license_url' => 1,
				'not a JSON object'              => 1,
			),
			$stats['excluded_reasons']
		);
		$this->assertSame( array( 'defiant' ), array_keys( (array) self::read_manifest()->copyrights ), 'Holders of left-out records are not added.' );
	}

	/**
	 * More than 2% of records failing the contract fails the run.
	 */
	public function test_too_many_invalid_records_fail_the_run() {
		$bad = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$bad[] = self::v3_record( 910 + $i, array( self::v3_software( 'plugin', 'bad', array( 'remediation' => null ) ) ) );
		}
		// 3 of 100 = 3%.
		$this->mock_wordfence( self::generated_feed( 95, 1, 1, $bad ) );

		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW ) );
		$this->assertStringContainsString( '3 of 100 records failed the contract check', Artws_Sec_Feed_Updater::get_status()['message'] );
		$this->assertSame( array(), self::feed_directory_files() );
	}

	/**
	 * The limit can be changed with a filter.
	 */
	public function test_excluded_ratio_filter() {
		$bad = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$bad[] = self::v3_record( 910 + $i, array( self::v3_software( 'plugin', 'bad', array( 'remediation' => null ) ) ) );
		}
		$this->mock_wordfence( self::generated_feed( 95, 1, 1, $bad ) );
		add_filter(
			'artws_sec_feed_max_excluded_ratio',
			static function () {
				return 0.05;
			}
		);

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );
	}

	/**
	 * Fewer than 90% of the last run's records of a type fails the run, unless allowed once.
	 */
	public function test_count_drop_fails_unless_allowed_once() {
		$this->mock_wordfence( self::generated_feed( 100 ) );
		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );
		$before = self::served_bytes();
		remove_all_filters( 'pre_http_request' );
		$this->set_up_http_blocker();

		// 89 of 100 plugin records.
		$this->mock_wordfence( self::generated_feed( 89 ) );
		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS ) );
		$this->assertStringContainsString( 'The plugin records dropped from 100 to 89.', Artws_Sec_Feed_Updater::get_status()['message'] );
		$this->assertTrue( self::read_manifest()->stale );
		$after = self::served_bytes();
		unset( $before['manifest.json'], $after['manifest.json'] );
		$this->assertSame( $before, $after );

		update_option( Artws_Sec_Feed_Updater::ACCEPT_DROP_OPTION, 1, false );
		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW + 2 * DAY_IN_SECONDS ) );
		$this->assertSame( 89, self::read_manifest()->files->plugin->count );
		$this->assertFalse( get_option( Artws_Sec_Feed_Updater::ACCEPT_DROP_OPTION ), 'The permission is used once.' );
	}

	/**
	 * 90% is still enough.
	 */
	public function test_count_drop_to_ninety_percent_passes() {
		$this->mock_wordfence( self::generated_feed( 100 ) );
		Artws_Sec_Feed_Updater::update( self::NOW );
		remove_all_filters( 'pre_http_request' );
		$this->set_up_http_blocker();

		$this->mock_wordfence( self::generated_feed( 90 ) );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS ) );
	}

	/**
	 * The watchdog marks the feed stale when no run has succeeded for 48 hours.
	 */
	public function test_watchdog_marks_an_old_feed_stale() {
		$this->publish_fixture();

		$this->assertFalse( Artws_Sec_Feed_Updater::watchdog( self::NOW + 2 * DAY_IN_SECONDS ) );
		$this->assertFalse( self::read_manifest()->stale );

		$this->assertTrue( Artws_Sec_Feed_Updater::watchdog( self::NOW + 2 * DAY_IN_SECONDS + 1 ) );
		$manifest = self::read_manifest();
		$this->assertTrue( $manifest->stale );
		$this->assertSame( '2026-10-22T03:00:00Z', $manifest->generated_at );
		$this->assert_matches_schema( $manifest, 'manifest' );
		$this->assertSame( 'stale', Artws_Sec_Feed_Updater::get_status()['result'] );
		$this->assertStringContainsString( '2026-10-22 03:00:00 UTC', Artws_Sec_Feed_Updater::get_status()['message'] );
	}

	/**
	 * The watchdog does nothing before the first run.
	 */
	public function test_watchdog_without_a_feed() {
		$this->assertFalse( Artws_Sec_Feed_Updater::watchdog( self::NOW ) );
		$this->assertSame( array(), self::feed_directory_files() );
	}

	/**
	 * A run in progress keeps a second run (and the watchdog) out; a lock left by a dead run expires.
	 */
	public function test_lock_allows_one_run_at_a_time() {
		global $wpdb;
		$this->publish_fixture();
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => Artws_Sec_Feed_Updater::LOCK_OPTION,
				'option_value' => (string) ( self::NOW + 3 * DAY_IN_SECONDS - 60 ),
				'autoload'     => 'off',
			)
		);

		$this->mock_wordfence( self::fixture_feed() );
		$this->assertSame( 'locked', Artws_Sec_Feed_Updater::update( self::NOW + 3 * DAY_IN_SECONDS ) );
		$this->assertFalse( Artws_Sec_Feed_Updater::watchdog( self::NOW + 3 * DAY_IN_SECONDS ) );
		$this->assertSame( array(), $this->wordfence_requests );
		$this->assertFalse( self::read_manifest()->stale );

		// Two hours later the lock counts as left behind.
		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW + 3 * DAY_IN_SECONDS + 2 * HOUR_IN_SECONDS ) );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", Artws_Sec_Feed_Updater::LOCK_OPTION ) ), 'The lock is released.' );
	}

	/**
	 * Temporary files left by a run that died are cleaned up by the next run.
	 */
	public function test_deletes_temporary_files_left_by_a_dead_run() {
		$this->publish_fixture();
		$old = self::feed_path( '.manifest.json.abc123.tmp' );
		$new = self::feed_path( '.manifest.json.def456.tmp' );
		file_put_contents( $old, '{' );
		file_put_contents( $new, '{' );
		touch( $old, self::NOW - 2 * HOUR_IN_SECONDS );
		touch( $new, self::NOW + DAY_IN_SECONDS );

		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS );

		$this->assertFileDoesNotExist( $old );
		$this->assertFileExists( $new, 'A file that a running run may still use is kept.' );
	}

	/**
	 * The cron events are scheduled once and all removed on deactivation.
	 */
	public function test_schedules_and_unschedules_the_events() {
		Artws_Sec_Feed_Updater::deactivate();
		Artws_Sec_Feed_Updater::schedule_events();
		Artws_Sec_Feed_Updater::schedule_events();

		$this->assertSame( 'daily', wp_get_schedule( Artws_Sec_Feed_Updater::UPDATE_HOOK ) );
		$this->assertSame( 'hourly', wp_get_schedule( Artws_Sec_Feed_Updater::WATCHDOG_HOOK ) );

		wp_schedule_single_event( time(), Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) );
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, Artws_Sec_Feed_Updater::RETRY_HOOK, array( 2 ) );
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, Artws_Sec_Feed_Updater::REPUBLISH_HOOK );
		Artws_Sec_Feed_Updater::deactivate();

		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::UPDATE_HOOK ) );
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) ) );
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 2 ) ) );
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::REPUBLISH_HOOK ) );
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::WATCHDOG_HOOK ) );
	}

	/**
	 * Deactivation marks the feed stale: nothing will update the served files any more.
	 */
	public function test_deactivation_marks_the_feed_stale() {
		$this->publish_fixture();

		Artws_Sec_Feed_Updater::deactivate();

		$this->assertTrue( self::read_manifest()->stale );
		$this->assertTrue( Artws_Sec_Feed_Updater::get_state()['stale'] );
		$this->assertStringContainsString( 'deactivated', Artws_Sec_Feed_Updater::get_status()['message'] );
	}

	/**
	 * The cron event of "Update now" runs with the current time, not its "manual" argument.
	 */
	public function test_manual_event_runs_with_the_current_time() {
		$this->mock_wordfence( self::fixture_feed() );

		do_action_ref_array( Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) );

		$manifest = self::read_manifest();
		$this->assertLessThan( 120, abs( strtotime( $manifest->generated_at ) - time() ), $manifest->generated_at );
	}

	/**
	 * A failed download is retried an hour later, up to three times per run.
	 */
	public function test_failed_download_is_retried() {
		$this->mock_wordfence( 'Bad Gateway', 502 );

		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW ) );
		$this->assertSame( self::NOW + HOUR_IN_SECONDS, wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 1 ) ) );
		$this->assertSame( self::NOW + HOUR_IN_SECONDS, Artws_Sec_Feed_Updater::get_status()['retry_at'] );

		// The cron event passes the attempt number.
		do_action( Artws_Sec_Feed_Updater::RETRY_HOOK, 1 );
		$this->assertNotFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 2 ) ) );

		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::retry( 3, self::NOW + 3 * HOUR_IN_SECONDS ) );
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 4 ) ), 'No fourth retry.' );
		$this->assertSame( 0, Artws_Sec_Feed_Updater::get_status()['retry_at'] );
	}

	/**
	 * Rejected data is not retried: the same data would be rejected again.
	 */
	public function test_rejected_data_is_not_retried() {
		$this->mock_wordfence( '{}' );

		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW ) );

		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 1 ) ) );
		$this->assertSame( 0, Artws_Sec_Feed_Updater::get_status()['retry_at'] );
	}

	/**
	 * A successful run cancels the pending retries.
	 */
	public function test_success_cancels_pending_retries() {
		wp_schedule_single_event( self::NOW + HOUR_IN_SECONDS, Artws_Sec_Feed_Updater::RETRY_HOOK, array( 2 ) );
		$this->mock_wordfence( self::fixture_feed() );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::retry( 1, self::NOW ) );

		$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 2 ) ) );
	}

	/**
	 * A failure while writing the files keeps the previous files.
	 */
	public function test_unwritable_directory_keeps_the_previous_files() {
		$this->publish_fixture();
		$before = self::served_bytes();

		// The same data, so that only the write can fail.
		$this->mock_wordfence( self::fixture_feed() );
		$result = $this->with_read_only_feed_directory(
			static function () {
				return Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS );
			}
		);

		$this->assertSame( 'failure', $result );
		$this->assertSame( $before, self::served_bytes(), 'Nothing in the directory changed, the manifest included.' );
		$status = Artws_Sec_Feed_Updater::get_status();
		$this->assertStringStartsWith( 'The feed directory is not writable.', $status['message'] );
		$this->assertStringContainsString( 'The stale manifest could not be written either', $status['message'] );
		$this->assertFalse( Artws_Sec_Feed_Updater::get_state()['stale'], 'Stale is saved only together with the manifest.' );
		$this->assertNotFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::RETRY_HOOK, array( 1 ) ) );
	}

	/**
	 * When the watchdog cannot write the manifest, it tries again on its next run.
	 */
	public function test_watchdog_tries_again_after_a_write_failure() {
		$this->publish_fixture();
		$later = self::NOW + 3 * DAY_IN_SECONDS;

		$this->with_read_only_feed_directory(
			static function () use ( $later ) {
				Artws_Sec_Feed_Updater::watchdog( $later );
			}
		);
		$this->assertFalse( self::read_manifest()->stale );

		$this->assertTrue( Artws_Sec_Feed_Updater::watchdog( $later + HOUR_IN_SECONDS ) );
		$this->assertTrue( self::read_manifest()->stale );
	}

	/**
	 * The watchdog decides on the state in the database, not on an older copy in the options cache.
	 */
	public function test_watchdog_reads_the_state_from_the_database() {
		$this->publish_fixture();
		$later = self::NOW + 3 * DAY_IN_SECONDS;

		// This process has the old state cached; another process then publishes.
		Artws_Sec_Feed_Updater::get_state();
		$published = self::store_state_behind_the_cache( $later - HOUR_IN_SECONDS );

		$this->assertFalse( Artws_Sec_Feed_Updater::watchdog( $later ) );

		$this->assertFalse( self::read_manifest()->stale );
		$this->assertSame( $published['version'], Artws_Sec_Feed_Updater::get_state()['version'], 'The newer state is not rolled back.' );
	}

	/**
	 * The manifest written after an EOL change comes from the state in the database.
	 */
	public function test_republish_reads_the_state_from_the_database() {
		$this->publish_fixture();

		Artws_Sec_Feed_Updater::get_state();
		$published = self::store_state_behind_the_cache( self::NOW + DAY_IN_SECONDS );

		Artws_Sec_Feed_Updater::republish_manifest();

		$this->assertSame( $published['version'], self::read_manifest()->version );
	}

	/**
	 * EOL saved while a run holds the lock: the manifest is rewritten by a one-off event a minute later.
	 */
	public function test_eol_saved_during_a_run_is_published_by_a_later_event() {
		global $wpdb;
		$this->publish_fixture();
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => Artws_Sec_Feed_Updater::LOCK_OPTION,
				'option_value' => (string) time(),
				'autoload'     => 'off',
			)
		);

		update_option( Artws_Sec_Feed_Updater::EOL_OPTION, array_merge( Artws_Sec_Feed_Updater::DEFAULT_EOL, array( 'php' => '8.4' ) ) );

		$this->assertSame( '8.2', self::read_manifest()->eol->php->min_supported, 'The run holds the lock.' );
		$this->assertNotFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::REPUBLISH_HOOK ) );

		$wpdb->delete( $wpdb->options, array( 'option_name' => Artws_Sec_Feed_Updater::LOCK_OPTION ) );
		do_action( Artws_Sec_Feed_Updater::REPUBLISH_HOOK );

		$this->assertSame( '8.4', self::read_manifest()->eol->php->min_supported );
	}

	/**
	 * The permission to publish fewer records survives a failed run and is used by the next success.
	 */
	public function test_allowed_drop_survives_a_failed_run() {
		$this->publish_fixture();
		update_option( Artws_Sec_Feed_Updater::ACCEPT_DROP_OPTION, 1, false );

		$this->mock_wordfence( 'Internal Server Error', 500 );
		$this->assertSame( 'failure', Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS ) );

		$this->assertSame( '1', (string) get_option( Artws_Sec_Feed_Updater::ACCEPT_DROP_OPTION ) );
	}

	/**
	 * Downloads left in the temp directory by a run that died are deleted by the next run.
	 */
	public function test_deletes_downloads_left_by_a_dead_run() {
		$old = trailingslashit( get_temp_dir() ) . Artws_Sec_Feed_Updater::DOWNLOAD_PREFIX . 'dead-run';
		$new = trailingslashit( get_temp_dir() ) . Artws_Sec_Feed_Updater::DOWNLOAD_PREFIX . 'running';
		file_put_contents( $old, '{' );
		file_put_contents( $new, '{' );
		touch( $old, self::NOW - 2 * HOUR_IN_SECONDS );
		touch( $new, self::NOW );

		try {
			$this->mock_wordfence( self::fixture_feed() );
			Artws_Sec_Feed_Updater::update( self::NOW );

			$this->assertFileDoesNotExist( $old );
			$this->assertFileExists( $new );
		} finally {
			foreach ( array( $old, $new ) as $file ) {
				if ( file_exists( $file ) ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Saves a newer state straight to the database, as another process would, leaving this process's cache alone.
	 *
	 * @param int $time Time of that run.
	 * @return array The saved state.
	 */
	private static function store_state_behind_the_cache( $time ) {
		global $wpdb;
		$state                 = Artws_Sec_Feed_Updater::get_state();
		$state['version']      = gmdate( 'Ymd.His', $time );
		$state['generated_at'] = gmdate( 'Y-m-d\TH:i:s\Z', $time );
		$state['last_success'] = $time;
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => maybe_serialize( $state ) ),
			array( 'option_name' => Artws_Sec_Feed_Updater::STATE_OPTION )
		);
		return $state;
	}

	/**
	 * Runs a callback while the feed directory cannot be written to.
	 *
	 * @param callable $callback Callback.
	 * @return mixed What the callback returns.
	 */
	private function with_read_only_feed_directory( callable $callback ) {
		$directory = Artws_Sec_Feed_Publisher::directory();
		chmod( $directory, 0555 );
		try {
			clearstatcache();
			if ( is_writable( $directory ) ) {
				$this->markTestSkipped( 'Running as a user that can write to read-only directories (root).' );
			}
			return $callback();
		} finally {
			chmod( $directory, 0755 );
			clearstatcache();
		}
	}

	/**
	 * Saving the EOL versions rewrites the manifest, without touching the data.
	 */
	public function test_saving_eol_rewrites_the_manifest() {
		$this->publish_fixture();
		$before = self::read_manifest();

		update_option(
			Artws_Sec_Feed_Updater::EOL_OPTION,
			array(
				'php'         => '8.3',
				'wordpress'   => '6.8',
				'woocommerce' => '10.3.1',
			)
		);

		$after = self::read_manifest();
		$this->assertSame( '8.3', $after->eol->php->min_supported );
		$this->assertSame( '6.8', $after->eol->wordpress->min_supported );
		$this->assertSame( '10.3.1', $after->eol->woocommerce->min_supported );
		$this->assertSame( $before->version, $after->version );
		$this->assertSame( $before->generated_at, $after->generated_at );
		$this->assertEquals( $before->files, $after->files );
		$this->assert_matches_schema( $after, 'manifest' );
	}
}
