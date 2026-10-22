<?php
/**
 * Tests for Artws_Sec_Record_Validator against the contract's JSON Schema.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Tests read local fixtures directly.

/**
 * Artws_Sec_Record_Validator_Test
 *
 * The validator is a hand-written copy of vuln-file.schema.json. Each case
 * here checks it against the schema itself: a record it accepts must make a
 * valid vulnerability file, and a record the schema rejects must be rejected.
 */
class Artws_Sec_Record_Validator_Test extends Artws_Sec_Feed_Test_Case {

	/**
	 * A valid feed record of the given type.
	 *
	 * @param string $type plugin, theme or core.
	 * @return array
	 */
	private static function valid_record( $type = 'plugin' ) {
		return array(
			'id'                => '00000000-0000-4000-8000-000000000001',
			'title'             => 'Sample Plugin <= 1.2.3 - Cross-Site Scripting',
			'software'          => array(
				'type' => $type,
				'slug' => 'core' === $type ? 'wordpress' : 'sample-' . $type,
			),
			'affected_versions' => array(
				array(
					'from_version'   => '*',
					'from_inclusive' => true,
					'to_version'     => '1.2.3',
					'to_inclusive'   => true,
				),
			),
			'patched'           => true,
			'patched_versions'  => array( '1.2.4' ),
			'remediation'       => 'Update to version 1.2.4, or a newer patched version',
			'cvss'              => array(
				'score'  => 7.2,
				'rating' => 'High',
			),
			'published'         => '2026-09-30T00:00:00Z',
			'reference'         => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/00000000-0000-4000-8000-000000000001',
		);
	}

	/**
	 * Whether the schema accepts a vulnerability file holding only this record.
	 *
	 * @param array  $record Feed record.
	 * @param string $type   File type.
	 * @return bool
	 */
	private static function schema_accepts( array $record, $type ) {
		$slug = is_array( $record['software'] ?? null ) && is_string( $record['software']['slug'] ?? null ) && '' !== $record['software']['slug']
			? $record['software']['slug']
			: 'unknown';
		$file = array(
			'schema_version'  => 1,
			'type'            => $type,
			'generated_at'    => '2026-10-22T03:00:00Z',
			'copyrights'      => array(
				'defiant' => array(
					'notice'      => 'Copyright 2012-2026 Defiant Inc.',
					'license'     => 'Fixture license text.',
					'license_url' => 'https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/',
				),
			),
			'attribution'     => 'Vulnerability data: Wordfence Intelligence',
			'vulnerabilities' => (object) array( $slug => array( $record ) ),
		);

		return self::schema_validate( json_decode( wp_json_encode( $file ) ), 'vuln-file' )->isValid();
	}

	/**
	 * Changes to a valid record, in both directions.
	 *
	 * @return array<string, array{callable, string}>
	 */
	public function mutation_provider() {
		$set   = static function ( array $path, $value ) {
			return static function ( array &$record ) use ( $path, $value ) {
				$node = &$record;
				foreach ( $path as $key ) {
					$node = &$node[ $key ];
				}
				$node = $value;
			};
		};
		$unset = static function ( array $path ) {
			return static function ( array &$record ) use ( $path ) {
				$last = array_pop( $path );
				$node = &$record;
				foreach ( $path as $key ) {
					$node = &$node[ $key ];
				}
				unset( $node[ $last ] );
			};
		};

		return array(
			// Accepted by both.
			'unchanged'                      => array( $set( array( 'title' ), 'Sample' ), 'plugin' ),
			'theme record'                   => array( $set( array( 'software', 'slug' ), 'sample-theme' ), 'theme' ),
			'cvss null'                      => array( $set( array( 'cvss' ), null ), 'plugin' ),
			'published null'                 => array( $set( array( 'published' ), null ), 'plugin' ),
			'no patched versions'            => array( $set( array( 'patched_versions' ), array() ), 'plugin' ),
			'empty remediation'              => array( $set( array( 'remediation' ), '' ), 'plugin' ),
			'integer score 10'               => array( $set( array( 'cvss', 'score' ), 10 ), 'plugin' ),
			'score 0 rating None'            => array(
				$set(
					array( 'cvss' ),
					array(
						'score'  => 0,
						'rating' => 'None',
					)
				),
				'plugin',
			),
			'upper-case UUID'                => array( $set( array( 'id' ), 'ABCDEF00-0000-4000-8000-000000000001' ), 'plugin' ),
			'unicode slug'                   => array( $set( array( 'software', 'slug' ), 'プラグイン' ), 'plugin' ),
			'200-character slug'             => array( $set( array( 'software', 'slug' ), str_repeat( 'a', 200 ) ), 'plugin' ),
			'64-character version'           => array( $set( array( 'patched_versions' ), array( str_repeat( '9', 64 ) ) ), 'plugin' ),
			'64 multibyte characters'        => array( $set( array( 'patched_versions' ), array( str_repeat( 'é', 64 ) ) ), 'plugin' ),
			'1000-character title'           => array( $set( array( 'title' ), str_repeat( 'あ', 1000 ) ), 'plugin' ),
			'2000-character remediation'     => array( $set( array( 'remediation' ), str_repeat( 'r', 2000 ) ), 'plugin' ),
			// Rejected by both.
			'missing key'                    => array( $unset( array( 'remediation' ) ), 'plugin' ),
			'extra key'                      => array( $set( array( 'description' ), 'x' ), 'plugin' ),
			'id not a UUID'                  => array( $set( array( 'id' ), 'wf-1' ), 'plugin' ),
			'UUID with a trailing newline'   => array( $set( array( 'id' ), "00000000-0000-4000-8000-000000000001\n" ), 'plugin' ),
			'empty title'                    => array( $set( array( 'title' ), '' ), 'plugin' ),
			'1001-character title'           => array( $set( array( 'title' ), str_repeat( 'あ', 1001 ) ), 'plugin' ),
			'title not a string'             => array( $set( array( 'title' ), 12 ), 'plugin' ),
			'theme record in a plugin file'  => array( $set( array( 'software', 'type' ), 'theme' ), 'plugin' ),
			'unknown type'                   => array( $set( array( 'software', 'type' ), 'mu-plugin' ), 'plugin' ),
			'extra software key'             => array( $set( array( 'software', 'name' ), 'Sample' ), 'plugin' ),
			'slug with a slash'              => array( $set( array( 'software', 'slug' ), 'a/b' ), 'plugin' ),
			'slug with a backslash'          => array( $set( array( 'software', 'slug' ), 'a\\b' ), 'plugin' ),
			'slug with a control character'  => array( $set( array( 'software', 'slug' ), "a\tb" ), 'plugin' ),
			'201-character slug'             => array( $set( array( 'software', 'slug' ), str_repeat( 'a', 201 ) ), 'plugin' ),
			'empty slug'                     => array( $set( array( 'software', 'slug' ), '' ), 'plugin' ),
			'no affected range'              => array( $set( array( 'affected_versions' ), array() ), 'plugin' ),
			'affected ranges as an object'   => array(
				$set(
					array( 'affected_versions' ),
					array(
						'* - 1.2.3' => array(
							'from_version'   => '*',
							'from_inclusive' => true,
							'to_version'     => '1.2.3',
							'to_inclusive'   => true,
						),
					)
				),
				'plugin',
			),
			'range without to_inclusive'     => array( $unset( array( 'affected_versions', 0, 'to_inclusive' ) ), 'plugin' ),
			'range with an extra key'        => array( $set( array( 'affected_versions', 0, 'note' ), 'x' ), 'plugin' ),
			'inclusive as a string'          => array( $set( array( 'affected_versions', 0, 'from_inclusive' ), 'true' ), 'plugin' ),
			'version with a space'           => array( $set( array( 'affected_versions', 0, 'to_version' ), '1.0 beta' ), 'plugin' ),
			'version with a tab'             => array( $set( array( 'affected_versions', 0, 'from_version' ), "1.0\t" ), 'plugin' ),
			'empty version'                  => array( $set( array( 'affected_versions', 0, 'to_version' ), '' ), 'plugin' ),
			'65-character version'           => array( $set( array( 'patched_versions' ), array( str_repeat( '9', 65 ) ) ), 'plugin' ),
			'version not a string'           => array( $set( array( 'patched_versions' ), array( 1.2 ) ), 'plugin' ),
			'patched as a string'            => array( $set( array( 'patched' ), 'yes' ), 'plugin' ),
			'patched versions as an object'  => array( $set( array( 'patched_versions' ), array( 'a' => '1.0' ) ), 'plugin' ),
			'2001-character remediation'     => array( $set( array( 'remediation' ), str_repeat( 'r', 2001 ) ), 'plugin' ),
			'remediation null'               => array( $set( array( 'remediation' ), null ), 'plugin' ),
			'cvss without rating'            => array( $unset( array( 'cvss', 'rating' ) ), 'plugin' ),
			'cvss with the vector'           => array( $set( array( 'cvss', 'vector' ), 'CVSS:3.1/AV:N' ), 'plugin' ),
			'score above 10'                 => array( $set( array( 'cvss', 'score' ), 10.5 ), 'plugin' ),
			'negative score'                 => array( $set( array( 'cvss', 'score' ), -1 ), 'plugin' ),
			'score as a string'              => array( $set( array( 'cvss', 'score' ), '7.2' ), 'plugin' ),
			'unknown rating'                 => array( $set( array( 'cvss', 'rating' ), 'Severe' ), 'plugin' ),
			'lower-case rating'              => array( $set( array( 'cvss', 'rating' ), 'high' ), 'plugin' ),
			'cvss as a string'               => array( $set( array( 'cvss' ), 'High' ), 'plugin' ),
			'published with an offset'       => array( $set( array( 'published' ), '2026-09-30T00:00:00+09:00' ), 'plugin' ),
			'published with a space'         => array( $set( array( 'published' ), '2026-09-30 00:00:00' ), 'plugin' ),
			'published with a newline'       => array( $set( array( 'published' ), "2026-09-30T00:00:00Z\n" ), 'plugin' ),
			'reference over http'            => array( $set( array( 'reference' ), 'http://www.wordfence.com/' ), 'plugin' ),
			'reference with a space'         => array( $set( array( 'reference' ), 'https://www.wordfence.com/a b' ), 'plugin' ),
			'reference without a host'       => array( $set( array( 'reference' ), 'https:///path' ), 'plugin' ),
			'reference over 2048 characters' => array( $set( array( 'reference' ), 'https://www.wordfence.com/' . str_repeat( 'a', 2030 ) ), 'plugin' ),
		);
	}

	/**
	 * The validator and the schema agree on each change.
	 *
	 * @dataProvider mutation_provider
	 *
	 * @param callable $mutate Changes the valid record in place.
	 * @param string   $type   File type.
	 */
	public function test_agrees_with_the_schema( $mutate, $type ) {
		$record = self::valid_record( 'theme' === $type ? 'theme' : 'plugin' );
		$mutate( $record );

		$this->assertSame(
			self::schema_accepts( $record, $type ),
			null === Artws_Sec_Record_Validator::check_record( $record, $type )
		);
	}

	/**
	 * Core records: only the "WordPress" slug, as the schema requires for the core file.
	 */
	public function test_core_record_needs_the_wordpress_slug() {
		$record = self::valid_record( 'core' );
		$this->assertNull( Artws_Sec_Record_Validator::check_record( $record, 'core' ) );
		$this->assertTrue( self::schema_accepts( $record, 'core' ) );

		$record['software']['slug'] = 'sample-plugin';
		$this->assertSame( 'software.slug', Artws_Sec_Record_Validator::check_record( $record, 'core' ) );
		$this->assertFalse( self::schema_accepts( $record, 'core' ) );
	}

	/**
	 * Unicode whitespace in a version is rejected, as ECMA-262 "\s" in the schema means.
	 */
	public function test_rejects_unicode_whitespace_in_versions() {
		foreach ( array( "\u{00A0}", "\u{3000}", "\u{2028}", "\u{FEFF}" ) as $space ) {
			$record                     = self::valid_record();
			$record['patched_versions'] = array( '1.2' . $space . '4' );

			$this->assertSame( 'patched_versions[0]', Artws_Sec_Record_Validator::check_record( $record, 'plugin' ) );
		}
	}

	/**
	 * Every record in the contract's sample files passes.
	 */
	public function test_accepts_every_contract_sample_record() {
		$checked = 0;
		foreach ( array( 'vuln-plugins.json', 'vuln-themes.json', 'vuln-core.json' ) as $sample ) {
			$file = json_decode( file_get_contents( ARTWS_SEC_CONTRACT_DIR . '/' . $sample ), true, 512, JSON_THROW_ON_ERROR );
			foreach ( $file['vulnerabilities'] as $records ) {
				foreach ( $records as $record ) {
					$this->assertNull( Artws_Sec_Record_Validator::check_record( $record, $file['type'] ), $record['id'] );
					++$checked;
				}
			}
			$this->assertNull( Artws_Sec_Record_Validator::check_copyrights( $file['copyrights'] ) );
		}
		$this->assertSame( 7, $checked );
	}

	/**
	 * Copyright holders that cannot be carried into the feed.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function invalid_copyrights_provider() {
		$holder = array(
			'notice'      => 'Copyright 2012-2026 Defiant Inc.',
			'license'     => 'Fixture license text.',
			'license_url' => 'https://www.wordfence.com/',
		);
		return array(
			'not an object'           => array( 'Defiant' ),
			'a list of holders'       => array( array( $holder ) ),
			'upper-case holder name'  => array( array( 'Defiant' => $holder ) ),
			'empty notice'            => array( array( 'defiant' => array_merge( $holder, array( 'notice' => '' ) ) ) ),
			'license missing'         => array( array( 'defiant' => array_diff_key( $holder, array( 'license' => true ) ) ) ),
			'license_url over http'   => array( array( 'defiant' => array_merge( $holder, array( 'license_url' => 'http://www.wordfence.com/' ) ) ) ),
			'license_url null'        => array( array( 'defiant' => array_merge( $holder, array( 'license_url' => null ) ) ) ),
			'holder that is a string' => array( array( 'defiant' => 'Copyright Defiant' ) ),
		);
	}

	/**
	 * A record whose copyright holders do not fit the contract is not published.
	 *
	 * @dataProvider invalid_copyrights_provider
	 *
	 * @param mixed $copyrights Holders.
	 */
	public function test_rejects_copyrights_the_contract_cannot_carry( $copyrights ) {
		$this->assertNotNull( Artws_Sec_Record_Validator::check_copyrights( $copyrights ) );
	}

	/**
	 * A record without copyright holders is fine on its own.
	 */
	public function test_accepts_no_copyright_holders() {
		$this->assertNull( Artws_Sec_Record_Validator::check_copyrights( array() ) );
	}
}
