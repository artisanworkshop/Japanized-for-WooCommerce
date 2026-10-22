<?php
/**
 * Tests for Artws_Sec_Feed_Converter.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Feed_Converter_Test
 */
class Artws_Sec_Feed_Converter_Test extends Artws_Sec_Feed_Test_Case {

	/**
	 * A V3 record becomes a record with the contract's keys and the V3 names.
	 */
	public function test_converts_a_record_to_the_contract_shape() {
		$record = self::v3_record(
			1,
			array(
				self::v3_software(
					'plugin',
					'example-forms',
					array(
						'affected_versions' => array(
							'* - 1.4.9'     => array(
								'from_version'   => '*',
								'from_inclusive' => true,
								'to_version'     => '1.4.9',
								'to_inclusive'   => true,
							),
							'1.5.0 - 1.5.3' => array(
								'from_version'   => '1.5.0',
								'from_inclusive' => false,
								'to_version'     => '1.5.3',
								'to_inclusive'   => true,
							),
						),
						'patched_versions'  => array( '1.4.10', '1.5.4' ),
						'remediation'       => 'Update to version 1.5.4, or a newer patched version',
					)
				),
			),
			array(
				'title'     => 'Example Forms - SQL Injection',
				'cvss'      => array(
					'vector' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
					'score'  => 9.8,
					'rating' => 'Critical',
				),
				'published' => '2026-09-30 14:05:00',
			)
		);

		$converted = Artws_Sec_Feed_Converter::convert( $record );

		$this->assertFalse( $converted['informational'] );
		$this->assertSame(
			array(
				array(
					'id'                => '20000000-0000-4000-8000-000000000001',
					'title'             => 'Example Forms - SQL Injection',
					'software'          => array(
						'type' => 'plugin',
						'slug' => 'example-forms',
					),
					'affected_versions' => array(
						array(
							'from_version'   => '*',
							'from_inclusive' => true,
							'to_version'     => '1.4.9',
							'to_inclusive'   => true,
						),
						array(
							'from_version'   => '1.5.0',
							'from_inclusive' => false,
							'to_version'     => '1.5.3',
							'to_inclusive'   => true,
						),
					),
					'patched'           => true,
					'patched_versions'  => array( '1.4.10', '1.5.4' ),
					'remediation'       => 'Update to version 1.5.4, or a newer patched version',
					'cvss'              => array(
						'score'  => 9.8,
						'rating' => 'Critical',
					),
					'published'         => '2026-09-30T14:05:00Z',
					'reference'         => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/20000000-0000-4000-8000-000000000001',
				),
			),
			$converted['records']
		);
		$this->assertNull( Artws_Sec_Record_Validator::check_record( $converted['records'][0], 'plugin' ) );
	}

	/**
	 * A record that affects several software gives one record per software, each with its own versions.
	 */
	public function test_splits_a_record_per_software() {
		$record = self::v3_record(
			2,
			array(
				self::v3_software( 'plugin', 'example-forms', array( 'patched_versions' => array( '2.0.5' ) ) ),
				self::v3_software( 'plugin', 'example-forms-pro', array( 'patched_versions' => array( '2.0.0' ) ) ),
				self::v3_software(
					'theme',
					'example-theme',
					array(
						'patched'          => false,
						'patched_versions' => array(),
					)
				),
			)
		);

		$records = Artws_Sec_Feed_Converter::convert( $record )['records'];

		$this->assertSame(
			array(
				array( 'plugin', 'example-forms', array( '2.0.5' ), true ),
				array( 'plugin', 'example-forms-pro', array( '2.0.0' ), true ),
				array( 'theme', 'example-theme', array(), false ),
			),
			array_map(
				static function ( $item ) {
					return array( $item['software']['type'], $item['software']['slug'], $item['patched_versions'], $item['patched'] );
				},
				$records
			)
		);
		$this->assertSame( array( $record['id'] ), array_unique( array_column( $records, 'id' ) ) );
	}

	/**
	 * Informational records are left out entirely.
	 */
	public function test_informational_record_has_no_feed_records() {
		$converted = Artws_Sec_Feed_Converter::convert(
			self::v3_record( 3, array( self::v3_software( 'plugin', 'example-forms' ) ), array( 'informational' => true ) )
		);

		$this->assertTrue( $converted['informational'] );
		$this->assertSame( array(), $converted['records'] );
	}

	/**
	 * A record without the informational key is not informational.
	 */
	public function test_missing_informational_key_is_not_informational() {
		$record = self::v3_record( 4, array( self::v3_software( 'plugin', 'example-forms' ) ) );
		unset( $record['informational'] );

		$converted = Artws_Sec_Feed_Converter::convert( $record );

		$this->assertFalse( $converted['informational'] );
		$this->assertCount( 1, $converted['records'] );
	}

	/**
	 * The reference is built from the ID, not taken from the V3 references.
	 */
	public function test_reference_is_built_from_the_id() {
		$record = self::v3_record(
			5,
			array( self::v3_software( 'plugin', 'example-forms' ) ),
			array( 'references' => array( 'https://example.com/advisory' ) )
		);

		$this->assertSame(
			'https://www.wordfence.com/threat-intel/vulnerabilities/id/20000000-0000-4000-8000-000000000005',
			Artws_Sec_Feed_Converter::convert( $record )['records'][0]['reference']
		);
	}

	/**
	 * A record without a CVSS score keeps cvss null.
	 */
	public function test_cvss_null_stays_null() {
		$record = self::v3_record( 6, array( self::v3_software( 'plugin', 'example-forms' ) ), array( 'cvss' => null ) );

		$this->assertNull( Artws_Sec_Feed_Converter::convert( $record )['records'][0]['cvss'] );
	}

	/**
	 * Published times and what they become.
	 *
	 * @return array<string, array{mixed, string|null}>
	 */
	public function published_provider() {
		return array(
			'space separated (V3)'   => array( '2026-09-30 14:05:00', '2026-09-30T14:05:00Z' ),
			'T separated'            => array( '2026-09-30T14:05:00', '2026-09-30T14:05:00Z' ),
			'fractional seconds'     => array( '2026-08-15 09:00:00.000000', '2026-08-15T09:00:00Z' ),
			'Z suffix'               => array( '2026-09-30T14:05:00Z', '2026-09-30T14:05:00Z' ),
			'zero offset'            => array( '2026-10-01T03:00:00+00:00', '2026-10-01T03:00:00Z' ),
			'other offset'           => array( '2026-10-01T09:00:00+09:00', '2026-10-01T00:00:00Z' ),
			'surrounding whitespace' => array( ' 2026-09-30 14:05:00 ', '2026-09-30T14:05:00Z' ),
			'null'                   => array( null, null ),
			'empty'                  => array( '', null ),
			'date only'              => array( '2026-09-30', null ),
			'words'                  => array( 'sometime in 2026', null ),
			'relative time'          => array( 'tomorrow', null ),
			'impossible date'        => array( '2026-02-30 00:00:00', null ),
			'impossible time'        => array( '2026-09-30 24:00:00', null ),
			'trailing newline only'  => array( "2026-09-30 14:05:00\nX", null ),
			'number'                 => array( 1759240000, null ),
		);
	}

	/**
	 * Published is normalized to YYYY-MM-DDTHH:MM:SSZ, or null when it cannot be read.
	 *
	 * @dataProvider published_provider
	 *
	 * @param mixed       $published V3 value.
	 * @param string|null $expected  Feed value.
	 */
	public function test_published_is_normalized( $published, $expected ) {
		$this->assertSame( $expected, Artws_Sec_Feed_Converter::published( $published ) );
	}

	/**
	 * The V3 "message" key is not a copyright holder.
	 */
	public function test_copyrights_drop_the_message_key() {
		$record = self::v3_record( 7, array( self::v3_software( 'plugin', 'example-forms' ) ) );

		$copyrights = Artws_Sec_Feed_Converter::convert( $record )['copyrights'];

		$this->assertSame( array( 'defiant' ), array_keys( $copyrights ) );
		$this->assertSame( $record['copyrights']['defiant'], $copyrights['defiant'] );
		$this->assertNull( Artws_Sec_Record_Validator::check_copyrights( $copyrights ) );
	}

	/**
	 * Keys the contract does not have are dropped from ranges, cvss and copyrights.
	 */
	public function test_drops_keys_the_contract_does_not_have() {
		$record = self::v3_record( 8, array( self::v3_software( 'plugin', 'example-forms' ) ) );
		$record['software'][0]['affected_versions']['* - 1.0']['note'] = 'extra';
		$record['copyrights']['defiant']['extra']                      = 'extra';

		$converted = Artws_Sec_Feed_Converter::convert( $record );

		$this->assertSame( array( 'from_version', 'from_inclusive', 'to_version', 'to_inclusive' ), array_keys( $converted['records'][0]['affected_versions'][0] ) );
		$this->assertSame( array( 'score', 'rating' ), array_keys( $converted['records'][0]['cvss'] ) );
		$this->assertSame( array( 'notice', 'license', 'license_url' ), array_keys( $converted['copyrights']['defiant'] ) );
	}

	/**
	 * A vulnerability with an empty software list cannot be converted; an informational one is simply skipped.
	 */
	public function test_empty_software_list() {
		$this->assertSame( array(), Artws_Sec_Feed_Converter::convert( self::v3_record( 10, array(), array( 'informational' => true ) ) )['records'] );

		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'software is empty' );
		Artws_Sec_Feed_Converter::convert( self::v3_record( 11, array() ) );
	}

	/**
	 * A record without a software list cannot be converted.
	 */
	public function test_record_without_software_list_throws() {
		$this->expectException( UnexpectedValueException::class );

		Artws_Sec_Feed_Converter::convert( array( 'id' => '20000000-0000-4000-8000-000000000009' ) );
	}
}
