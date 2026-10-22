<?php
/**
 * Tests for the streaming reader of the vulnerability files (issue #257).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Feed_Reader_Test
 */
class JP4WC_Security_Feed_Reader_Test extends WP_UnitTestCase {

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
		if ( ! class_exists( 'JP4WC_Security_Feed_Reader' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-security-feed-reader.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Security_Feed_Reader' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->temporary as $path ) {
			wp_delete_file( $path );
		}
		parent::tearDown();
	}

	/**
	 * Writes JSON text to a temporary file.
	 *
	 * @param string $json JSON text.
	 * @return string Path.
	 */
	private function write( $json ) {
		$path = tempnam( get_temp_dir(), 'jp4wc-reader' );
		file_put_contents( $path, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
		$this->temporary[] = $path;
		return $path;
	}

	/**
	 * Reads a file and returns what the callbacks received.
	 *
	 * @param string $path       File.
	 * @param int    $chunk_size Bytes read at a time.
	 * @return array{found: bool, members: array, slugs: array}
	 */
	private function read( $path, $chunk_size = 1048576 ) {
		$members = array();
		$slugs   = array();
		$found   = JP4WC_Security_Feed_Reader::read(
			$path,
			static function ( $key, $json ) use ( &$members ) {
				$members[ $key ] = json_decode( $json, true );
			},
			static function ( $slug, $json ) use ( &$slugs ) {
				$slugs[] = array( $slug, json_decode( $json, true ) );
			},
			$chunk_size
		);
		return compact( 'found', 'members', 'slugs' );
	}

	/**
	 * Chunk sizes, including ones that split every token.
	 *
	 * @return array<string, array{int}>
	 */
	public function chunk_size_provider() {
		return array(
			'one byte'    => array( 1 ),
			'seven bytes' => array( 7 ),
			'one MB'      => array( 1048576 ),
		);
	}

	/**
	 * The plugin sample reads as json_decode() reads it, whatever the chunk size.
	 *
	 * @dataProvider chunk_size_provider
	 *
	 * @param int $chunk_size Bytes read at a time.
	 */
	public function test_reads_the_sample_like_json_decode( $chunk_size ) {
		$path     = TESTS_PLUGIN_DIR . '/tests/fixtures/security-feed/vuln-plugins.json';
		$expected = json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture file.

		$result = $this->read( $path, $chunk_size );

		$this->assertTrue( $result['found'] );
		$vulnerabilities = $expected['vulnerabilities'];
		unset( $expected['vulnerabilities'] );
		$this->assertSame( $expected, $result['members'] );

		$slugs = array();
		foreach ( $result['slugs'] as $pair ) {
			$slugs[ $pair[0] ] = $pair[1];
		}
		$this->assertSame( $vulnerabilities, $slugs );
	}

	/**
	 * A numeric slug stays a string, as it is written in the file.
	 */
	public function test_numeric_slug_stays_a_string() {
		$result = $this->read( $this->write( '{"vulnerabilities":{"123":[],"aé":[1]}}' ) );

		$this->assertSame( array( array( '123', array() ), array( "a\u{e9}", array( 1 ) ) ), $result['slugs'] );
	}

	/**
	 * A byte order mark and whitespace around the object are accepted.
	 */
	public function test_accepts_bom_and_whitespace() {
		$result = $this->read( $this->write( "\xEF\xBB\xBF \n{ \"type\" : \"core\" , \"vulnerabilities\" : { } }\n" ) );

		$this->assertTrue( $result['found'] );
		$this->assertSame( array( 'type' => 'core' ), $result['members'] );
		$this->assertSame( array(), $result['slugs'] );
	}

	/**
	 * A `vulnerabilities` member that is not an object goes to the member callback.
	 */
	public function test_vulnerabilities_that_is_not_an_object_is_a_member() {
		$result = $this->read( $this->write( '{"vulnerabilities":[1,2]}' ) );

		$this->assertFalse( $result['found'] );
		$this->assertSame( array( 'vulnerabilities' => array( 1, 2 ) ), $result['members'] );
	}

	/**
	 * Damaged or non-object input.
	 *
	 * @return array<string, array{string}>
	 */
	public function damaged_provider() {
		return array(
			'cut short'           => array( '{"type":"plugin","vulnerabilities":{"a":[{"id":"x"' ),
			'cut inside a string' => array( '{"type":"plu' ),
			'not an object'       => array( '[1,2,3]' ),
			'empty'               => array( '' ),
			'data after the end'  => array( '{"type":"plugin"}{}' ),
			'missing colon'       => array( '{"type" "plugin"}' ),
			'missing comma'       => array( '{"a":1 "b":2}' ),
		);
	}

	/**
	 * Damaged input throws.
	 *
	 * @dataProvider damaged_provider
	 *
	 * @param string $json Input.
	 */
	public function test_damaged_input_throws( $json ) {
		$this->expectException( UnexpectedValueException::class );
		$this->read( $this->write( $json ) );
	}

	/**
	 * A missing file throws instead of warning.
	 */
	public function test_missing_file_throws() {
		$this->expectException( UnexpectedValueException::class );
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- fopen() warns before the reader throws.
			static function () {
				return true;
			},
			E_WARNING
		);
		try {
			$this->read( get_temp_dir() . 'jp4wc-reader-missing-' . wp_generate_password( 8, false ) . '.json' );
		} finally {
			restore_error_handler();
		}
	}
}
