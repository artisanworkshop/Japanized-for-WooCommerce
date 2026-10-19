<?php
/**
 * Tests for the security check JSON contract fixtures.
 *
 * The directory tests/fixtures/security-feed/ holds the JSON Schemas and samples that JP4WC
 * and the artws-security-hub server plugin (plugins/artws-security-hub/) both
 * test against. The server's tests validate the samples against the schemas;
 * these check what JP4WC relies on when it uses the samples as a downloaded
 * feed: the .json.gz files are exactly what the manifest describes (sha256 of
 * the gzip bytes, decompressed content, record count) and every record sits
 * under its own slug and type.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Feed_Fixtures_Test
 */
class JP4WC_Security_Feed_Fixtures_Test extends WP_UnitTestCase {

	/**
	 * Absolute path of the contract directory, with a trailing slash.
	 *
	 * @return string
	 */
	private static function fixture_dir() {
		return TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/';
	}

	/**
	 * Decodes a JSON file from the contract directory.
	 *
	 * @param string $file Path relative to the contract directory.
	 * @return array
	 */
	private static function read_json( $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.
		return json_decode( file_get_contents( self::fixture_dir() . $file ), true, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Every JSON file in the contract directory.
	 *
	 * @return array<string, array{string}>
	 */
	public function json_file_provider() {
		$files = array_merge(
			glob( self::fixture_dir() . '*.json' ),
			glob( self::fixture_dir() . 'schema/*.json' )
		);
		$cases = array();
		foreach ( $files as $file ) {
			$relative           = substr( $file, strlen( self::fixture_dir() ) );
			$cases[ $relative ] = array( $relative );
		}
		return $cases;
	}

	/**
	 * The contract has the files both test suites expect.
	 */
	public function test_contract_files_exist() {
		$this->assertEqualsCanonicalizing(
			array(
				'manifest.json',
				'report.json',
				'scan-request.json',
				'schema/common.schema.json',
				'schema/manifest.schema.json',
				'schema/report.schema.json',
				'schema/scan-request.schema.json',
				'schema/vuln-file.schema.json',
				'vuln-core.json',
				'vuln-plugins.json',
				'vuln-themes.json',
			),
			array_keys( $this->json_file_provider() )
		);
	}

	/**
	 * Every file is valid JSON and declares its format version.
	 *
	 * @dataProvider json_file_provider
	 *
	 * @param string $file Path relative to the contract directory.
	 */
	public function test_json_file_parses_and_declares_its_version( $file ) {
		$data = self::read_json( $file );

		if ( str_starts_with( $file, 'schema/' ) ) {
			$this->assertSame( 'https://json-schema.org/draft/2020-12/schema', $data['$schema'] );
			$this->assertSame( 'https://wc.artws.info/schema/security/v1/' . basename( $file ), $data['$id'] );
		} else {
			$this->assertSame( 1, $data['schema_version'] );
		}
	}

	/**
	 * The manifest's types and the vulnerability file each one points to.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function feed_file_provider() {
		return array(
			'plugin' => array( 'plugin', 'vuln-plugins.json' ),
			'theme'  => array( 'theme', 'vuln-themes.json' ),
			'core'   => array( 'core', 'vuln-core.json' ),
		);
	}

	/**
	 * Each .json.gz matches the manifest: sha256 of the gzip bytes, the
	 * decompressed sample, and the number of records.
	 *
	 * @dataProvider feed_file_provider
	 *
	 * @param string $type   Manifest files key.
	 * @param string $source Uncompressed sample of that file.
	 */
	public function test_manifest_describes_the_gzip_file( $type, $source ) {
		$entry = self::read_json( 'manifest.json' )['files'][ $type ];

		$this->assertSame( $source . '.gz', $entry['file'] );

		$gz_path = self::fixture_dir() . $entry['file'];
		$this->assertFileExists( $gz_path );
		$this->assertSame( $entry['sha256'], hash_file( 'sha256', $gz_path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.
		$this->assertSame( file_get_contents( self::fixture_dir() . $source ), gzdecode( file_get_contents( $gz_path ) ) );

		$records = self::read_json( $source )['vulnerabilities'];
		$this->assertSame( $entry['count'], array_sum( array_map( 'count', $records ) ) );
	}

	/**
	 * Each record sits under its own slug in the file of its own type.
	 *
	 * @dataProvider feed_file_provider
	 *
	 * @param string $type   Software type of the file.
	 * @param string $source Uncompressed sample of that file.
	 */
	public function test_records_sit_under_their_own_slug_and_type( $type, $source ) {
		$feed = self::read_json( $source );

		$this->assertSame( $type, $feed['type'] );
		foreach ( $feed['vulnerabilities'] as $slug => $records ) {
			foreach ( $records as $record ) {
				$this->assertSame( $type, $record['software']['type'], $record['id'] );
				$this->assertSame( (string) $slug, $record['software']['slug'], $record['id'] );
			}
		}
	}
}
