<?php
/**
 * Tests for Artws_Sec_Scan_Request.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Scan_Request_Test
 *
 * What the class accepts, the contract's scan-request schema accepts too, and
 * it drops the keys the schema does not define instead of storing them.
 */
class Artws_Sec_Scan_Request_Test extends Artws_Sec_Feed_Test_Case {

	/**
	 * Returns the contract's sample request.
	 *
	 * @return array
	 */
	private static function sample() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture.
		return json_decode( file_get_contents( ARTWS_SEC_CONTRACT_DIR . '/scan-request.json' ), true );
	}

	/**
	 * Asserts that a normalized request matches the contract's schema.
	 *
	 * @param array $request Normalized request.
	 */
	private function assert_request_schema( array $request ) {
		$this->assert_matches_schema( json_decode( wp_json_encode( $request ) ), 'scan-request' );
	}

	/**
	 * The sample comes back unchanged and matches the schema.
	 */
	public function test_sample_is_accepted_as_is() {
		$request = Artws_Sec_Scan_Request::normalize( self::sample() );

		$this->assertIsArray( $request );
		$this->assertSame( self::sample(), $request );
		$this->assert_request_schema( $request );
	}

	/**
	 * Requests a client may send besides the sample.
	 *
	 * @return array<string, array{callable}>
	 */
	public function valid_provider() {
		return array(
			'without the bands'                => array(
				static function ( &$d ) {
					unset( $d['revenue_band'], $d['order_band'] );
				},
			),
			'plugin without a Version header'  => array(
				static function ( &$d ) {
					$d['software'][2]['version'] = '';
				},
			),
			'Version header with a space'      => array(
				static function ( &$d ) {
					$d['software'][2]['version'] = '1.0 beta';
				},
			),
			'PHP version with a suffix'        => array(
				static function ( &$d ) {
					$d['php_ver'] = '8.3.6-1+ubuntu24.04.1+deb.sury.org+1';
				},
			),
			'locale with a variant'            => array(
				static function ( &$d ) {
					$d['locale'] = 'de_DE_formal';
				},
			),
			'no software and no findings'      => array(
				static function ( &$d ) {
					$d['software']       = array();
					$d['local_findings'] = array();
				},
			),
			'reregister of 0.3.0, now unknown' => array(
				static function ( &$d ) {
					$d['reregister'] = 'yes';
				},
			),
			'1000 software items'              => array(
				static function ( &$d ) {
					$d['software'] = array_fill( 0, 1000, $d['software'][0] );
				},
			),
			'64-character Japanese version'    => array(
				static function ( &$d ) {
					$d['software'][0]['version'] = str_repeat( 'あ', 64 );
				},
			),
		);
	}

	/**
	 * Valid requests are accepted, and the result matches the schema.
	 *
	 * @dataProvider valid_provider
	 *
	 * @param callable $mutate Changes the sample in place.
	 */
	public function test_valid_request_is_accepted( $mutate ) {
		$data = self::sample();
		$mutate( $data );

		$request = Artws_Sec_Scan_Request::normalize( $data );

		$this->assertIsArray( $request );
		$this->assertArrayNotHasKey( 'reregister', $request );
		$this->assert_request_schema( $request );
	}

	/**
	 * Keys the contract does not define are dropped at every level.
	 */
	public function test_unknown_keys_are_dropped() {
		$data                               = self::sample();
		$data['admin_email']                = 'owner@example.jp';
		$data['order_total']                = 1234567;
		$data['software'][0]['path']        = '/var/www/html/wp-content/plugins/woocommerce';
		$data['local_findings'][0]['value'] = 'DISALLOW_FILE_EDIT=false';

		$request = Artws_Sec_Scan_Request::normalize( $data );

		$this->assertSame( self::sample(), $request );
		$this->assertStringNotContainsString( 'owner@example.jp', wp_json_encode( $request ) );
		$this->assert_request_schema( $request );
	}

	/**
	 * Requests that must be refused, and the field the error names.
	 *
	 * @return array<string, array{callable, string}>
	 */
	public function invalid_provider() {
		return array(
			'body that is a list'                 => array(
				static function ( &$d ) {
					$d = array( $d );
				},
				'body',
			),
			'newer schema_version'                => array(
				static function ( &$d ) {
					$d['schema_version'] = 2;
				},
				'schema_version',
			),
			'schema_version as a string'          => array(
				static function ( &$d ) {
					$d['schema_version'] = '1';
				},
				'schema_version',
			),
			'site_url that is not http(s)'        => array(
				static function ( &$d ) {
					$d['site_url'] = 'javascript:alert(1)';
				},
				'site_url',
			),
			'site_url with a space'               => array(
				static function ( &$d ) {
					$d['site_url'] = 'https://example.jp/my shop';
				},
				'site_url',
			),
			'site_url with an ideographic space'  => array(
				static function ( &$d ) {
					$d['site_url'] = "https://example.jp/\u{3000}";
				},
				'site_url',
			),
			'site_url over 2048 characters'       => array(
				static function ( &$d ) {
					$d['site_url'] = 'https://example.jp/' . str_repeat( 'a', 2030 );
				},
				'site_url',
			),
			'empty WordPress version'             => array(
				static function ( &$d ) {
					$d['wp_ver'] = '';
				},
				'wp_ver',
			),
			'missing jp4wc_ver'                   => array(
				static function ( &$d ) {
					unset( $d['jp4wc_ver'] );
				},
				'jp4wc_ver',
			),
			'C1 control character in php_ver'     => array(
				static function ( &$d ) {
					$d['php_ver'] = "8.3.12\u{009f}";
				},
				'php_ver',
			),
			'locale with a path'                  => array(
				static function ( &$d ) {
					$d['locale'] = 'ja/../../etc';
				},
				'locale',
			),
			'core in the software list'           => array(
				static function ( &$d ) {
					$d['software'][0]['type'] = 'core';
				},
				'software[0]',
			),
			'slug with a slash'                   => array(
				static function ( &$d ) {
					$d['software'][1]['slug'] = 'a/b';
				},
				'software[1]',
			),
			'control character in a version'      => array(
				static function ( &$d ) {
					$d['software'][0]['version'] = "10.6.2\n";
				},
				'software[0]',
			),
			'Version header over 64 characters'   => array(
				static function ( &$d ) {
					$d['software'][0]['version'] = str_repeat( '1', 65 );
				},
				'software[0]',
			),
			'active as a string'                  => array(
				static function ( &$d ) {
					$d['software'][0]['active'] = 'true';
				},
				'software[0]',
			),
			'software as an object'               => array(
				static function ( &$d ) {
					$d['software'] = array( 'woocommerce' => $d['software'][0] );
				},
				'software',
			),
			'too many software items'             => array(
				static function ( &$d ) {
					$d['software'] = array_fill( 0, 1001, $d['software'][0] );
				},
				'software',
			),
			'unknown check ID'                    => array(
				static function ( &$d ) {
					$d['local_findings'][0]['check_id'] = 'X-01';
				},
				'local_findings[0]',
			),
			'check ID with a trailing newline'    => array(
				static function ( &$d ) {
					$d['local_findings'][0]['check_id'] = "V-01\n";
				},
				'local_findings[0]',
			),
			'unknown finding status'              => array(
				static function ( &$d ) {
					$d['local_findings'][0]['status'] = 'ok';
				},
				'local_findings[0]',
			),
			'too many findings'                   => array(
				static function ( &$d ) {
					$d['local_findings'] = array_fill( 0, 101, $d['local_findings'][0] );
				},
				'local_findings',
			),
			'revenue amount, not a band'          => array(
				static function ( &$d ) {
					$d['revenue_band'] = '12000000';
				},
				'revenue_band',
			),
			'out-of-range order band'             => array(
				static function ( &$d ) {
					$d['order_band'] = 'O5';
				},
				'order_band',
			),
			'revenue band without the order band' => array(
				static function ( &$d ) {
					unset( $d['order_band'] );
				},
				'order_band',
			),
			'order band without the revenue band' => array(
				static function ( &$d ) {
					unset( $d['revenue_band'] );
				},
				'revenue_band',
			),
			'missing consent_version'             => array(
				static function ( &$d ) {
					unset( $d['consent_version'] );
				},
				'consent_version',
			),
			'consent_version month 13'            => array(
				static function ( &$d ) {
					$d['consent_version'] = '2026-13';
				},
				'consent_version',
			),
		);
	}

	/**
	 * Invalid requests are refused with a 400 naming the field.
	 *
	 * @dataProvider invalid_provider
	 *
	 * @param callable $mutate Changes the sample in place.
	 * @param string   $field  Field the error names.
	 */
	public function test_invalid_request_is_refused( $mutate, $field ) {
		$data = self::sample();
		$mutate( $data );

		$error = Artws_Sec_Scan_Request::normalize( $data );

		$this->assertWPError( $error );
		$this->assertSame( 'artws_sec_invalid_request', $error->get_error_code() );
		$this->assertSame( array( 'status' => 400 ), $error->get_error_data() );
		$this->assertSame( "The request has an invalid $field.", $error->get_error_message() );
	}

	/**
	 * Consent texts the server accepts: the first terms (JP4WC 3.0.0) and the revision in force from 2026-11-05.
	 *
	 * @return array<string, array{string}>
	 */
	public function known_consent_version_provider() {
		return array(
			'2026-10' => array( '2026-10' ),
			'2026-11' => array( '2026-11' ),
		);
	}

	/**
	 * Every known consent text is accepted and kept as sent.
	 *
	 * @dataProvider known_consent_version_provider
	 *
	 * @param string $version Consent text.
	 */
	public function test_known_consent_versions( $version ) {
		$data                    = self::sample();
		$data['consent_version'] = $version;

		$request = Artws_Sec_Scan_Request::normalize( $data );

		$this->assertIsArray( $request );
		$this->assertSame( $version, $request['consent_version'] );
		$this->assert_request_schema( $request );
	}

	/**
	 * A consent text the server does not know is refused with its own code; the filter adds texts.
	 */
	public function test_unknown_consent_version() {
		$data = self::sample();

		$data['consent_version'] = '2027-04';
		$error                   = Artws_Sec_Scan_Request::normalize( $data );
		$this->assertWPError( $error );
		$this->assertSame( 'artws_sec_unknown_consent_version', $error->get_error_code() );
		$this->assertSame( array( 'status' => 400 ), $error->get_error_data() );

		add_filter(
			'artws_sec_consent_versions',
			static function ( $versions ) {
				$versions[] = '2027-04';
				return $versions;
			}
		);
		$this->assertIsArray( Artws_Sec_Scan_Request::normalize( $data ) );
	}
}
