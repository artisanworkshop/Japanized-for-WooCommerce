<?php
/**
 * Tests for matching the installed software against the vulnerability feed (issue #257).
 *
 * The records come from the contract samples in tests/fixtures/security-feed/.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Scanner_Test
 */
class JP4WC_Security_Scanner_Test extends WP_UnitTestCase {

	/**
	 * Temporary files written by a test.
	 *
	 * @var string[]
	 */
	private $temporary = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'JP4WC_Security_Scanner' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-security-scanner.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Security_Scanner' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->temporary as $path ) {
			wp_delete_file( $path );
		}
		wp_cache_delete( 'plugins', 'plugins' );
		parent::tearDown();
	}

	/**
	 * The decompressed sample files, by type.
	 *
	 * @return array<string, string>
	 */
	private static function sample_files() {
		$dir = TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/';
		return array(
			'plugin' => $dir . 'vuln-plugins.json',
			'theme'  => $dir . 'vuln-themes.json',
			'core'   => $dir . 'vuln-core.json',
		);
	}

	/**
	 * Returns one record of a sample file, by the last digits of its ID.
	 *
	 * @param string $type   File type.
	 * @param string $slug   Slug.
	 * @param string $suffix Last digits of the record ID.
	 * @return array
	 */
	private static function sample_record( $type, $slug, $suffix ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.
		$data = json_decode( file_get_contents( self::sample_files()[ $type ] ), true );
		foreach ( $data['vulnerabilities'][ $slug ] as $record ) {
			if ( str_ends_with( $record['id'], $suffix ) ) {
				return $record;
			}
		}
		return array();
	}

	/**
	 * One installed copy.
	 *
	 * @param string $version Version.
	 * @param string $name    Name.
	 * @param string $file    Plugin file.
	 * @return array
	 */
	private static function copy_of( $version, $name = 'Software', $file = '' ) {
		return array(
			'name'    => $name,
			'version' => $version,
			'file'    => $file,
		);
	}

	/**
	 * IDs of the records found for each affected copy, as "type:slug@version".
	 *
	 * @param array $items What scan() returned.
	 * @return array<string, string[]>
	 */
	private static function found_ids( array $items ) {
		$found = array();
		foreach ( $items as $item ) {
			$found[ $item['type'] . ':' . $item['slug'] . '@' . $item['version'] ] = array_map(
				static function ( $vulnerability ) {
					return substr( $vulnerability['id'], -3 );
				},
				$item['vulnerabilities']
			);
		}
		ksort( $found );
		return $found;
	}

	/**
	 * Versions against the sample ranges.
	 *
	 * @return array<string, array{string, string, string, string, bool}>
	 */
	public function range_provider() {
		return array(
			// 001: * (incl) to 1.2.3 (incl).
			'no lower bound'          => array( 'plugin', 'sample-plugin', '001', '0.1', true ),
			'inclusive upper bound'   => array( 'plugin', 'sample-plugin', '001', '1.2.3', true ),
			'above the upper bound'   => array( 'plugin', 'sample-plugin', '001', '1.2.4', false ),
			// 002: 2.0.0 (incl) to 2.0.5 (excl).
			'inclusive lower bound'   => array( 'plugin', 'sample-plugin', '002', '2.0.0', true ),
			'below the lower bound'   => array( 'plugin', 'sample-plugin', '002', '1.9.9', false ),
			'inside'                  => array( 'plugin', 'sample-plugin', '002', '2.0.4', true ),
			'exclusive upper bound'   => array( 'plugin', 'sample-plugin', '002', '2.0.5', false ),
			// 003: 1.0 (incl) to 1.4.9 (incl), and 1.5.0 (excl) to 1.5.3 (incl).
			'first range'             => array( 'plugin', 'sample-multi-range', '003', '1.4.9', true ),
			'between the ranges'      => array( 'plugin', 'sample-multi-range', '003', '1.4.10', false ),
			'exclusive lower bound'   => array( 'plugin', 'sample-multi-range', '003', '1.5.0', false ),
			'second range'            => array( 'plugin', 'sample-multi-range', '003', '1.5.1', true ),
			'end of the second range' => array( 'plugin', 'sample-multi-range', '003', '1.5.3', true ),
			'after both ranges'       => array( 'plugin', 'sample-multi-range', '003', '1.5.4', false ),
			// 004: * to *.
			'no bounds at all'        => array( 'plugin', 'sample-plugin-unpatched', '004', '99.0', true ),
			// 101: * to 3.1.0 (excl).
			'theme below the fix'     => array( 'theme', 'sample-theme', '101', '3.0.9', true ),
			'theme fixed'             => array( 'theme', 'sample-theme', '101', '3.1.0', false ),
			// 202: 6.5 (incl) to 6.5.4 (incl).
			'core inside'             => array( 'core', 'wordpress', '202', '6.5.2', true ),
			'core after'              => array( 'core', 'wordpress', '202', '6.6', false ),
		);
	}

	/**
	 * A version is affected when one of the ranges holds it.
	 *
	 * @dataProvider range_provider
	 *
	 * @param string $type     File type.
	 * @param string $slug     Slug.
	 * @param string $suffix   Last digits of the record ID.
	 * @param string $version  Installed version.
	 * @param bool   $affected Expected result.
	 */
	public function test_is_affected_follows_the_contract( $type, $slug, $suffix, $version, $affected ) {
		$record = JP4WC_Security_Scanner::normalize_record( self::sample_record( $type, $slug, $suffix ), $type, $slug );
		$this->assertIsArray( $record );

		$this->assertSame( $affected, JP4WC_Security_Scanner::is_affected( $version, $record['affected_versions'] ) );
	}

	/**
	 * An empty version is never affected, although "*" has no lower bound.
	 */
	public function test_empty_version_is_not_affected() {
		$ranges = array(
			array(
				'from_version'   => '*',
				'from_inclusive' => true,
				'to_version'     => '*',
				'to_inclusive'   => true,
			),
		);
		$this->assertFalse( JP4WC_Security_Scanner::is_affected( '', $ranges ) );
		$this->assertTrue( JP4WC_Security_Scanner::is_affected( '1.0', $ranges ) );
	}

	/**
	 * Matching the samples: affected copies only, the right records, unknown slugs ignored.
	 */
	public function test_scan_finds_the_records_of_the_installed_versions() {
		$installed = array(
			'plugin' => array(
				'sample-plugin'           => array( self::copy_of( '1.2.3', 'Sample Plugin', 'sample-plugin/sample-plugin.php' ) ),
				'sample-multi-range'      => array( self::copy_of( '1.5.0' ) ),
				'sample-plugin-unpatched' => array( self::copy_of( '0.0.1' ) ),
				'not-in-the-feed'         => array( self::copy_of( '1.0' ) ),
			),
			'theme'  => array(
				'sample-theme' => array( self::copy_of( '3.0.0' ) ),
			),
			'core'   => array(
				'wordpress' => array( self::copy_of( '6.4.2', 'WordPress' ) ),
			),
		);

		$items = JP4WC_Security_Scanner::scan( self::sample_files(), $installed );

		$this->assertSame(
			array(
				'core:wordpress@6.4.2'                 => array( '201' ),
				'plugin:sample-plugin-unpatched@0.0.1' => array( '004' ),
				'plugin:sample-plugin@1.2.3'           => array( '001' ),
				'theme:sample-theme@3.0.0'             => array( '101' ),
			),
			self::found_ids( $items )
		);

		$plugin = wp_list_filter( $items, array( 'slug' => 'sample-plugin' ) );
		$plugin = reset( $plugin );
		$this->assertSame( 'Sample Plugin', $plugin['name'] );
		$this->assertSame( 'sample-plugin/sample-plugin.php', $plugin['file'] );
		$this->assertSame(
			array(
				'id'               => '00000000-0000-4000-8000-000000000001',
				'title'            => 'Sample Plugin <= 1.2.3 - Cross-Site Scripting (fixture)',
				'reference'        => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/00000000-0000-4000-8000-000000000001',
				'rating'           => 'High',
				'score'            => 7.2,
				'patched'          => true,
				'patched_versions' => array( '1.2.4' ),
			),
			$plugin['vulnerabilities'][0]
		);

		$theme = wp_list_filter( $items, array( 'slug' => 'sample-theme' ) );
		$theme = reset( $theme );
		$this->assertSame( '', $theme['vulnerabilities'][0]['rating'], 'A record without CVSS has no rating.' );
		$this->assertNull( $theme['vulnerabilities'][0]['score'] );

		$this->assertSame( 'High', JP4WC_Security_Scanner::max_rating( $items ) );
	}

	/**
	 * Copies of software with no affected version give nothing.
	 */
	public function test_scan_returns_nothing_for_fixed_versions() {
		$installed = array(
			'plugin' => array( 'sample-plugin' => array( self::copy_of( '1.2.4' ) ) ),
			'core'   => array( 'wordpress' => array( self::copy_of( '6.6' ) ) ),
		);

		$this->assertSame( array(), JP4WC_Security_Scanner::scan( self::sample_files(), $installed ) );
		$this->assertSame( '', JP4WC_Security_Scanner::max_rating( array() ) );
	}

	/**
	 * Copies without a version are not matched.
	 */
	public function test_scan_skips_copies_without_a_version() {
		$installed = array(
			'plugin' => array( 'sample-plugin-unpatched' => array( self::copy_of( '' ) ) ),
		);

		$this->assertSame( array(), JP4WC_Security_Scanner::scan( self::sample_files(), $installed ) );
	}

	/**
	 * A file that is missing is skipped.
	 */
	public function test_scan_skips_missing_files() {
		$installed = array(
			'plugin' => array( 'sample-plugin' => array( self::copy_of( '1.0' ) ) ),
		);
		$files     = array( 'plugin' => get_temp_dir() . 'jp4wc-missing-' . wp_generate_password( 8, false ) . '.json' );

		$this->assertSame( array(), JP4WC_Security_Scanner::scan( $files, $installed ) );
	}

	/**
	 * Writes a plugin vulnerability file with the given records.
	 *
	 * @param array $vulnerabilities Slug => records.
	 * @return string Path.
	 */
	private function write_plugin_file( array $vulnerabilities ) {
		$path = tempnam( get_temp_dir(), 'jp4wc-scanner' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
		file_put_contents(
			$path,
			wp_json_encode(
				array(
					'schema_version'  => 1,
					'type'            => 'plugin',
					'vulnerabilities' => $vulnerabilities,
				)
			)
		);
		$this->temporary[] = $path;
		return $path;
	}

	/**
	 * A numeric slug, which becomes an integer key in PHP arrays, still matches.
	 */
	public function test_numeric_slug_matches() {
		$record                     = self::sample_record( 'plugin', 'sample-plugin-unpatched', '004' );
		$record['software']['slug'] = '404';
		$installed                  = array( 'plugin' => array( '404' => array( self::copy_of( '1.0' ) ) ) );

		$items = JP4WC_Security_Scanner::scan( array( 'plugin' => $this->write_plugin_file( array( '404' => array( $record ) ) ) ), $installed );

		$this->assertSame( array( 'plugin:404@1.0' => array( '004' ) ), self::found_ids( $items ) );
	}

	/**
	 * Records that are invalid or have values the contract does not define.
	 *
	 * @return array<string, array{callable}>
	 */
	public function skipped_record_provider() {
		return array(
			'unknown software type'     => array(
				static function ( $record ) {
					$record['software']['type'] = 'mu-plugin';
					return $record;
				},
			),
			'unknown rating'            => array(
				static function ( $record ) {
					$record['cvss']['rating'] = 'Severe';
					return $record;
				},
			),
			'listed under another slug' => array(
				static function ( $record ) {
					$record['software']['slug'] = 'other';
					return $record;
				},
			),
			'reference over http'       => array(
				static function ( $record ) {
					$record['reference'] = 'http://www.wordfence.com/threat-intel/vulnerabilities/id/' . $record['id'];
					return $record;
				},
			),
			'version with a space'      => array(
				static function ( $record ) {
					$record['affected_versions'][0]['to_version'] = '1.2 beta';
					return $record;
				},
			),
			'inclusive flag not a bool' => array(
				static function ( $record ) {
					$record['affected_versions'][0]['to_inclusive'] = 'true';
					return $record;
				},
			),
			'no ranges'                 => array(
				static function ( $record ) {
					$record['affected_versions'] = array();
					return $record;
				},
			),
			'malformed ID'              => array(
				static function ( $record ) {
					$record['id'] = "00000000-0000-4000-8000-000000000001\n";
					return $record;
				},
			),
			'patched as text'           => array(
				static function ( $record ) {
					$record['patched'] = 'true';
					return $record;
				},
			),
			'patched missing'           => array(
				static function ( $record ) {
					unset( $record['patched'] );
					return $record;
				},
			),
			'fixed version not text'    => array(
				static function ( $record ) {
					$record['patched_versions'] = array( 1.2 );
					return $record;
				},
			),
			'fixed versions not a list' => array(
				static function ( $record ) {
					$record['patched_versions'] = array( 'fix' => '1.2.4' );
					return $record;
				},
			),
			'not an object'             => array(
				static function () {
					return 'record';
				},
			),
		);
	}

	/**
	 * Such a record is skipped, and the other records of the slug still match.
	 *
	 * @dataProvider skipped_record_provider
	 *
	 * @param callable $change Changes the sample record.
	 */
	public function test_invalid_records_are_skipped( $change ) {
		$record = self::sample_record( 'plugin', 'sample-plugin', '001' );
		$this->assertNull( JP4WC_Security_Scanner::normalize_record( $change( $record ), 'plugin', 'sample-plugin' ) );

		$other                     = self::sample_record( 'plugin', 'sample-plugin-unpatched', '004' );
		$other['software']['slug'] = 'sample-plugin';
		$path                      = $this->write_plugin_file( array( 'sample-plugin' => array( $change( $record ), $other ) ) );
		$installed                 = array( 'plugin' => array( 'sample-plugin' => array( self::copy_of( '1.0' ) ) ) );

		$this->assertSame(
			array( 'plugin:sample-plugin@1.0' => array( '004' ) ),
			self::found_ids( JP4WC_Security_Scanner::scan( array( 'plugin' => $path ), $installed ) )
		);
	}

	/**
	 * A record listed twice under a slug is reported once.
	 */
	public function test_duplicate_records_are_reported_once() {
		$record    = self::sample_record( 'plugin', 'sample-plugin', '001' );
		$path      = $this->write_plugin_file( array( 'sample-plugin' => array( $record, $record ) ) );
		$installed = array( 'plugin' => array( 'sample-plugin' => array( self::copy_of( '1.0' ) ) ) );

		$this->assertSame(
			array( 'plugin:sample-plugin@1.0' => array( '001' ) ),
			self::found_ids( JP4WC_Security_Scanner::scan( array( 'plugin' => $path ), $installed ) )
		);
	}

	/**
	 * Slugs of the installed plugins: the directory, or the file name of a single-file plugin.
	 */
	public function test_installed_plugins_are_keyed_by_slug() {
		wp_cache_set(
			'plugins',
			array(
				'' => array(
					'sample-plugin/sample-plugin.php' => array(
						'Name'    => 'Sample Plugin',
						'Version' => '1.2.3',
					),
					'single-file.php'                 => array(
						'Name'    => 'Single File',
						'Version' => '0.9',
					),
					'no-version/no-version.php'       => array(
						'Name'    => 'No Version',
						'Version' => '',
					),
				),
			),
			'plugins'
		);

		$installed = JP4WC_Security_Scanner::installed_software();

		$this->assertSame(
			array(
				'sample-plugin' => array( self::copy_of( '1.2.3', 'Sample Plugin', 'sample-plugin/sample-plugin.php' ) ),
				'single-file'   => array( self::copy_of( '0.9', 'Single File', 'single-file.php' ) ),
			),
			$installed['plugin']
		);
		$this->assertSame( array( 'wordpress' => array( self::copy_of( get_bloginfo( 'version' ), 'WordPress' ) ) ), $installed['core'] );
		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			if ( '' !== $theme->get( 'Version' ) ) {
				$this->assertArrayHasKey( $stylesheet, $installed['theme'] );
			}
		}
	}
}
