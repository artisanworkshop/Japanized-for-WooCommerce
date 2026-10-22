<?php
/**
 * Shared helpers for the feed tests.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Test helpers read and write local files directly.

use Opis\JsonSchema\CompliantValidator;
use Opis\JsonSchema\Errors\ErrorFormatter;

/**
 * Artws_Sec_Feed_Test_Case
 *
 * Empties the feed directory around every test, sets a fake API key, and
 * answers the Wordfence request from pre_http_request so that no test
 * reaches the network.
 */
abstract class Artws_Sec_Feed_Test_Case extends WP_UnitTestCase {

	/**
	 * Fake key; only the mock sees it.
	 */
	const API_KEY = 'fixture-key';

	/**
	 * Arguments of each Wordfence request the mock answered.
	 *
	 * @var array<int, array>
	 */
	protected $wordfence_requests = array();

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		self::empty_feed_directory();
		$this->wordfence_requests = array();

		add_filter(
			'artws_sec_wordfence_api_key',
			static function () {
				return self::API_KEY;
			}
		);
		// Anything that is not answered by mock_wordfence() fails instead of going out.
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
	 * Tear down.
	 */
	public function tear_down() {
		self::empty_feed_directory();
		parent::tear_down();
	}

	/**
	 * Answers the Wordfence feed request.
	 *
	 * Public so that data provider closures can call it.
	 *
	 * @param string|WP_Error $body Body written to the stream file, or the error to return.
	 * @param int             $code HTTP status.
	 */
	public function mock_wordfence( $body, $code = 200 ) {
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( $body, $code ) {
				if ( Artws_Sec_Feed_Updater::FEED_URL !== $url ) {
					return $response;
				}
				$this->wordfence_requests[] = $args;
				if ( $body instanceof WP_Error ) {
					return $body;
				}
				file_put_contents( $args['filename'], $body );
				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => $code,
						'message' => '',
					),
					'cookies'  => array(),
					'filename' => $args['filename'],
				);
			},
			10,
			3
		);
	}

	/**
	 * Returns the V3 fixture feed.
	 *
	 * @return string
	 */
	protected static function fixture_feed() {
		return file_get_contents( dirname( __DIR__ ) . '/fixtures/wordfence-v3-production.json' );
	}

	/**
	 * Builds a V3 production record.
	 *
	 * @param int   $number    Makes the ID unique.
	 * @param array $software  Software entries from v3_software().
	 * @param array $overrides Top-level keys to replace.
	 * @return array
	 */
	protected static function v3_record( $number, array $software, array $overrides = array() ) {
		$id = sprintf( '20000000-0000-4000-8000-%012d', $number );
		return array_merge(
			array(
				'id'            => $id,
				'title'         => "Generated record $number",
				'software'      => $software,
				'informational' => false,
				'description'   => 'Generated for a test.',
				'references'    => array(),
				'cwe'           => null,
				'cvss'          => array(
					'vector' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N',
					'score'  => 5.3,
					'rating' => 'Medium',
				),
				'cve'           => null,
				'cve_link'      => null,
				'researchers'   => array(),
				'published'     => '2026-10-01 00:00:00',
				'updated'       => null,
				'copyrights'    => array(
					'message' => 'This record contains material that is subject to copyright',
					'defiant' => array(
						'notice'      => 'Copyright 2012-2026 Defiant Inc.',
						'license'     => 'Fixture license text.',
						'license_url' => 'https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/',
					),
				),
			),
			$overrides
		);
	}

	/**
	 * Builds a V3 software entry.
	 *
	 * @param string $type      plugin, theme or core.
	 * @param string $slug      Slug.
	 * @param array  $overrides Keys to replace.
	 * @return array
	 */
	protected static function v3_software( $type, $slug, array $overrides = array() ) {
		return array_merge(
			array(
				'type'              => $type,
				'name'              => $slug,
				'slug'              => $slug,
				'affected_versions' => array(
					'* - 1.0' => array(
						'from_version'   => '*',
						'from_inclusive' => true,
						'to_version'     => '1.0',
						'to_inclusive'   => true,
					),
				),
				'patched'           => true,
				'patched_versions'  => array( '1.0.1' ),
				'remediation'       => 'Update to version 1.0.1, or a newer patched version',
			),
			$overrides
		);
	}

	/**
	 * Encodes records as a V3 feed (ID => record).
	 *
	 * @param array $records V3 records.
	 * @return string
	 */
	protected static function v3_feed( array $records ) {
		$feed = array();
		foreach ( $records as $record ) {
			$feed[ $record['id'] ] = $record;
		}
		return wp_json_encode( $feed );
	}

	/**
	 * A valid V3 feed: $plugins plugin records, $themes theme records and $core core records.
	 *
	 * @param int   $plugins Plugin records.
	 * @param int   $themes  Theme records.
	 * @param int   $core    Core records.
	 * @param array $extra   More V3 records appended.
	 * @return string
	 */
	protected static function generated_feed( $plugins, $themes = 1, $core = 1, array $extra = array() ) {
		$records = array();
		$number  = 0;
		for ( $i = 0; $i < $plugins; $i++ ) {
			$records[] = self::v3_record( ++$number, array( self::v3_software( 'plugin', 'generated-plugin-' . ( $i % 7 ) ) ) );
		}
		for ( $i = 0; $i < $themes; $i++ ) {
			$records[] = self::v3_record( ++$number, array( self::v3_software( 'theme', 'generated-theme' ) ) );
		}
		for ( $i = 0; $i < $core; $i++ ) {
			$records[] = self::v3_record( ++$number, array( self::v3_software( 'core', 'wordpress' ) ) );
		}
		return self::v3_feed( array_merge( $records, $extra ) );
	}

	/**
	 * Path of a file in the feed directory.
	 *
	 * @param string $name File name.
	 * @return string
	 */
	protected static function feed_path( $name ) {
		return Artws_Sec_Feed_Publisher::directory() . '/' . $name;
	}

	/**
	 * Reads the served manifest.
	 *
	 * @return object
	 */
	protected static function read_manifest() {
		return json_decode( file_get_contents( self::feed_path( 'manifest.json' ) ), false, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Reads a served vulnerability file.
	 *
	 * @param string $name File name, e.g. vuln-plugins.json.gz.
	 * @return object
	 */
	protected static function read_vulnerability_file( $name ) {
		return json_decode( gzdecode( file_get_contents( self::feed_path( $name ) ) ), false, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Asserts that a decoded value matches one of the contract schemas.
	 *
	 * @param mixed  $data   Value decoded with json_decode() (objects, not arrays).
	 * @param string $schema Schema name without the .schema.json suffix.
	 */
	protected function assert_matches_schema( $data, $schema ) {
		$result = self::schema_validate( $data, $schema );
		$this->assertTrue(
			$result->isValid(),
			$result->isValid() ? '' : wp_json_encode( ( new ErrorFormatter() )->format( $result->error() ), JSON_PRETTY_PRINT )
		);
	}

	/**
	 * Validates a decoded value against one of the contract schemas.
	 *
	 * @param mixed  $data   Value decoded with json_decode().
	 * @param string $schema Schema name without the .schema.json suffix.
	 * @return \Opis\JsonSchema\ValidationResult
	 */
	protected static function schema_validate( $data, $schema ) {
		$validator = new CompliantValidator();
		$validator->resolver()->registerPrefix( ARTWS_SEC_SCHEMA_BASE_URI, ARTWS_SEC_CONTRACT_DIR . '/schema/' );
		return $validator->validate( $data, ARTWS_SEC_SCHEMA_BASE_URI . $schema . '.schema.json' );
	}

	/**
	 * Lists the files in the feed directory, temporary files included.
	 *
	 * @return string[]
	 */
	protected static function feed_directory_files() {
		$directory = Artws_Sec_Feed_Publisher::directory();
		$entries   = is_dir( $directory ) ? scandir( $directory ) : array();
		$names     = array();
		foreach ( is_array( $entries ) ? $entries : array() as $name ) {
			if ( is_file( $directory . '/' . $name ) ) {
				$names[] = $name;
			}
		}
		sort( $names );
		return $names;
	}

	/**
	 * Deletes everything in the feed directory.
	 */
	private static function empty_feed_directory() {
		$directory = Artws_Sec_Feed_Publisher::directory();
		if ( ! is_dir( $directory ) ) {
			return;
		}
		foreach ( self::feed_directory_files() as $name ) {
			unlink( $directory . '/' . $name );
		}
	}
}
