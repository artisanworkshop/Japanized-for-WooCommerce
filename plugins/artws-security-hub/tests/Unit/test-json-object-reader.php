<?php
/**
 * Tests for Artws_Sec_Json_Object_Reader.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Tests write local files directly.

/**
 * Artws_Sec_Json_Object_Reader_Test
 */
class Artws_Sec_Json_Object_Reader_Test extends WP_UnitTestCase {

	/**
	 * Files created by a test.
	 *
	 * @var string[]
	 */
	private $files = array();

	/**
	 * Tear down.
	 */
	public function tear_down() {
		foreach ( $this->files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		parent::tear_down();
	}

	/**
	 * Writes content to a temporary file.
	 *
	 * @param string $content File content.
	 * @return string Path.
	 */
	private function file_with( $content ) {
		$file = tempnam( sys_get_temp_dir(), 'artws-sec-reader-' );
		file_put_contents( $file, $content );
		$this->files[] = $file;
		return $file;
	}

	/**
	 * Reads a file and returns key => decoded value.
	 *
	 * @param string $path       File path or stream URL.
	 * @param int    $chunk_size Bytes per read.
	 * @return array
	 */
	private static function read_all( $path, $chunk_size = 1048576 ) {
		$members = array();
		Artws_Sec_Json_Object_Reader::read(
			$path,
			static function ( $key, $json ) use ( &$members ) {
				$members[ $key ] = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
			},
			$chunk_size
		);
		return $members;
	}

	/**
	 * Chunk sizes that put the chunk boundaries everywhere.
	 *
	 * @return array<string, array{int}>
	 */
	public function chunk_size_provider() {
		$cases = array();
		foreach ( array( 1, 2, 3, 5, 7, 64, 1048576 ) as $size ) {
			$cases[ "$size bytes" ] = array( $size );
		}
		return $cases;
	}

	/**
	 * Every member comes back as json_decode() of the whole file would give it.
	 *
	 * @dataProvider chunk_size_provider
	 *
	 * @param int $chunk_size Bytes per read.
	 */
	public function test_reads_the_same_members_as_json_decode( $chunk_size ) {
		$json = wp_json_encode(
			array(
				'a'           => array(
					'title'  => 'Braces { } [ ] and "quotes" \\ in strings',
					'nested' => array( array( 'x' => array( 1, 2, array( 'y' => null ) ) ) ),
				),
				'ünicode-キー'  => array( 'text' => "改行\nとタブ\t、絵文字 🐞" ),
				'escaped"key' => array( 'slash' => '\\"' ),
				'list'        => array( 1, '2', array( 3 ) ),
				'string'      => 'a "string" value',
				'number'      => -12.5e3,
				'true'        => true,
				'null'        => null,
				'empty'       => array(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
		);

		$this->assertSame( json_decode( $json, true ), self::read_all( $this->file_with( $json ), $chunk_size ) );
	}

	/**
	 * The V3 fixture reads the same in small chunks.
	 */
	public function test_reads_the_v3_fixture() {
		$json = file_get_contents( dirname( __DIR__ ) . '/fixtures/wordfence-v3-production.json' );
		$file = $this->file_with( $json );

		$this->assertSame( json_decode( $json, true ), self::read_all( $file, 13 ) );
	}

	/**
	 * Compact JSON with a byte order mark and no whitespace.
	 */
	public function test_reads_compact_json_after_a_byte_order_mark() {
		$file = $this->file_with( "\xEF\xBB\xBF" . '{"a":{"b":[1,2]},"c":"d","e":12}' );

		$this->assertSame(
			array(
				'a' => array( 'b' => array( 1, 2 ) ),
				'c' => 'd',
				'e' => 12,
			),
			self::read_all( $file, 2 )
		);
	}

	/**
	 * An empty object has no members.
	 */
	public function test_empty_object_has_no_members() {
		$count = Artws_Sec_Json_Object_Reader::read(
			$this->file_with( " { \n } \n" ),
			static function () {
				throw new LogicException( 'No member expected.' );
			}
		);

		$this->assertSame( 0, $count );
	}

	/**
	 * A gzip file reads through the compress.zlib wrapper.
	 */
	public function test_reads_through_compress_zlib() {
		$json = '{"one":{"x":1},"two":{"x":2}}';
		$file = $this->file_with( gzencode( $json ) );

		$this->assertSame( json_decode( $json, true ), self::read_all( 'compress.zlib://' . $file, 4 ) );
	}

	/**
	 * Input that is not one complete JSON object.
	 *
	 * @return array<string, array{string}>
	 */
	public function malformed_provider() {
		return array(
			'empty file'               => array( '' ),
			'a list'                   => array( '[{"a":1}]' ),
			'truncated in a record'    => array( '{"a":{"b":[1,2' ),
			'truncated in a string'    => array( '{"a":{"b":"unterminated' ),
			'truncated after a member' => array( '{"a":{"b":1}' ),
			'missing colon'            => array( '{"a" {"b":1}}' ),
			'unquoted name'            => array( '{a:{"b":1}}' ),
			'missing comma'            => array( '{"a":{"b":1} "c":{}}' ),
			'data after the object'    => array( '{"a":{}} {"b":{}}' ),
			'trailing comma'           => array( '{"a":{},}' ),
		);
	}

	/**
	 * Malformed input stops the run instead of returning part of the feed.
	 *
	 * @dataProvider malformed_provider
	 *
	 * @param string $json File content.
	 */
	public function test_malformed_input_throws( $json ) {
		$this->expectException( Artws_Sec_Feed_Exception::class );

		self::read_all( $this->file_with( $json ), 3 );
	}

	/**
	 * A missing file throws.
	 */
	public function test_missing_file_throws() {
		$this->expectException( Artws_Sec_Feed_Exception::class );

		// fopen() warns before returning false; production only logs the warning.
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Lets fopen() fail as it does in production.
			static function () {
				return true;
			},
			E_WARNING
		);
		try {
			self::read_all( sys_get_temp_dir() . '/artws-sec-reader-missing-' . uniqid() . '.json' );
		} finally {
			restore_error_handler();
		}
	}
}
