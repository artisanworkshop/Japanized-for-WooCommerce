<?php
/**
 * Tests for Artws_Sec_Vuln_Index and its place in the feed update.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Vuln_Index_Test
 */
class Artws_Sec_Vuln_Index_Test extends Artws_Sec_Scan_Test_Case {

	/**
	 * Unix time of the first run (2026-10-20 03:00:00 UTC).
	 */
	const NOW = 1792465200;

	/**
	 * Returns the feed versions in the index.
	 *
	 * @return string[]
	 */
	private static function index_versions() {
		global $wpdb;
		$versions = $wpdb->get_col( 'SELECT DISTINCT feed_version FROM ' . Artws_Sec_Db::vulns_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test helper.
		sort( $versions );
		return $versions;
	}

	/**
	 * A successful run writes the index of the version it publishes.
	 */
	public function test_update_writes_the_index() {
		$this->mock_wordfence( self::fixture_feed() );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW ) );

		$state = Artws_Sec_Feed_Updater::get_state();
		$this->assertSame( $state['version'], $state['index_version'] );
		$this->assertSame( array( $state['version'] ), self::index_versions() );

		// The index holds what the published files hold.
		foreach ( Artws_Sec_Feed_Publisher::FILES as $type => $file ) {
			$published = json_decode( wp_json_encode( self::read_vulnerability_file( $file ) ), true );
			$keys      = array();
			foreach ( array_keys( $published['vulnerabilities'] ) as $slug ) {
				$keys[ Artws_Sec_Vuln_Index::lookup_key( $type, (string) $slug ) ] = $slug;
			}
			$found = Artws_Sec_Vuln_Index::find( $state['version'], array_keys( $keys ) );
			$this->assertCount( count( $keys ), $found, $type );
			foreach ( $found as $key => $records ) {
				$this->assertSame( $published['vulnerabilities'][ $keys[ $key ] ], $records );
			}
		}
		$this->assertObjectNotHasProperty( 'index_version', self::read_manifest() );
	}

	/**
	 * Each run keeps the generation it replaced (a scan may still be reading it) and deletes older ones.
	 */
	public function test_update_keeps_one_previous_generation() {
		$this->mock_wordfence( self::fixture_feed() );

		Artws_Sec_Feed_Updater::update( self::NOW );
		$first = Artws_Sec_Feed_Updater::get_state()['version'];
		Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS );
		$second = Artws_Sec_Feed_Updater::get_state()['version'];
		$this->assertSame( array( $first, $second ), self::index_versions() );

		Artws_Sec_Feed_Updater::update( self::NOW + 2 * DAY_IN_SECONDS );
		$third = Artws_Sec_Feed_Updater::get_state()['version'];
		$this->assertSame( array( $second, $third ), self::index_versions() );
	}

	/**
	 * The next run deletes what a run that died after writing the index left behind.
	 */
	public function test_next_run_clears_a_dead_runs_generation() {
		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW );
		$published = Artws_Sec_Feed_Updater::get_state()['version'];
		Artws_Sec_Vuln_Index::write( array( 'plugin' => array( 'left-behind' => array( '{}' ) ) ), 'left-behind' );

		$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS ) );

		$this->assertSame( array( $published, Artws_Sec_Feed_Updater::get_state()['version'] ), self::index_versions() );
	}

	/**
	 * A run that cannot write the index still publishes the feed; scans keep the previous index.
	 */
	public function test_index_write_failure_keeps_the_feed() {
		global $wpdb;
		$this->mock_wordfence( self::fixture_feed() );
		Artws_Sec_Feed_Updater::update( self::NOW );
		$first = Artws_Sec_Feed_Updater::get_state()['version'];

		$table = Artws_Sec_Db::vulns_table();
		add_filter(
			'query',
			static function ( $query ) use ( $table ) {
				return 0 === strpos( $query, "INSERT INTO {$table}" ) ? 'INSERT INTO artws_sec_no_such_table VALUES (1)' : $query;
			}
		);
		$suppress = $wpdb->suppress_errors( true );
		try {
			$this->assertSame( 'success', Artws_Sec_Feed_Updater::update( self::NOW + DAY_IN_SECONDS ) );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		$state = Artws_Sec_Feed_Updater::get_state();
		$this->assertNotSame( $first, $state['version'] );
		$this->assertFalse( $state['stale'] );
		$this->assertSame( $first, $state['index_version'] );
		$this->assertSame( $state['version'], self::read_manifest()->version );
		$this->assertSame( array( $first ), self::index_versions() );
		$status = Artws_Sec_Feed_Updater::get_status();
		$this->assertSame( 'success', $status['result'] );
		$this->assertStringStartsWith( 'The feed was published, but the index of the free security check was not updated', $status['message'] );
		$this->assertSame( 0, $status['retry_at'] );
	}

	/**
	 * A failed read is an error, not "no records".
	 */
	public function test_find_throws_when_the_index_cannot_be_read() {
		global $wpdb;
		$table = Artws_Sec_Db::vulns_table();
		add_filter(
			'query',
			static function ( $query ) use ( $table ) {
				return 0 === strpos( $query, "SELECT lookup_key, records FROM {$table}" ) ? 'SELECT broken FROM artws_sec_no_such_table' : $query;
			}
		);
		$suppress = $wpdb->suppress_errors( true );
		try {
			$this->expectException( Artws_Sec_Feed_Exception::class );
			Artws_Sec_Vuln_Index::find( 'any', array( Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'a' ) ) );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}

	/**
	 * A row larger than a batch is sent on its own, and every row arrives.
	 */
	public function test_large_rows_are_written() {
		$big = array( wp_json_encode( array( 'text' => str_repeat( 'x', Artws_Sec_Vuln_Index::BATCH_BYTES ) ) ) );
		Artws_Sec_Vuln_Index::write(
			array(
				'plugin' => array(
					'small' => array( '{"n":1}' ),
					'big'   => $big,
					'after' => array( '{"n":2}' ),
				),
			),
			'large'
		);

		$found = Artws_Sec_Vuln_Index::find( 'large', array( Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'small' ), Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'big' ), Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'after' ) ) );
		$this->assertCount( 3, $found );
	}

	/**
	 * Any number of keys can be looked up; software without records is left out.
	 */
	public function test_find_in_chunks() {
		$records = array();
		$keys    = array();
		for ( $i = 0; $i < 450; $i++ ) {
			$records[ "plugin-$i" ] = array( wp_json_encode( array( 'n' => $i ) ) );
			$keys[]                 = Artws_Sec_Vuln_Index::lookup_key( 'plugin', "plugin-$i" );
		}
		Artws_Sec_Vuln_Index::write( array( 'plugin' => $records ), 'chunks' );
		$keys[] = Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'not-in-the-feed' );
		$keys[] = Artws_Sec_Vuln_Index::lookup_key( 'theme', 'plugin-0' );

		$found = Artws_Sec_Vuln_Index::find( 'chunks', $keys );

		$this->assertCount( 450, $found );
		$this->assertSame( array( array( 'n' => 449 ) ), $found[ Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'plugin-449' ) ] );
		$this->assertSame( array(), Artws_Sec_Vuln_Index::find( 'other-version', $keys ) );
	}

	/**
	 * Slugs that differ only in case or a trailing space are different software.
	 */
	public function test_slugs_are_matched_exactly() {
		Artws_Sec_Vuln_Index::write(
			array(
				'plugin' => array(
					'Sample'  => array( '{"n":1}' ),
					'sample'  => array( '{"n":2}' ),
					'sample ' => array( '{"n":3}' ),
				),
			),
			'exact'
		);

		$found = Artws_Sec_Vuln_Index::find( 'exact', array( Artws_Sec_Vuln_Index::lookup_key( 'plugin', 'sample' ) ) );

		$this->assertSame( array( array( 'n' => 2 ) ), array_values( $found )[0] );
	}

	/**
	 * Version ranges as the contract defines them.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public function range_provider() {
		return array(
			'empty version'       => array( '', false ),
			'below the range'     => array( '0.9', false ),
			'inclusive start'     => array( '1.0', true ),
			'inside'              => array( '1.5', true ),
			'exclusive end'       => array( '2.0', false ),
			'exclusive start'     => array( '3.0', false ),
			'just after start'    => array( '3.0.1', true ),
			'no upper bound'      => array( '99.0', true ),
			'pre-release of end'  => array( '2.0-beta', true ),
			'not a version'       => array( 'abc', false ),
			'longer equal string' => array( '1.0.0', true ),
		);
	}

	/**
	 * The range check follows the contract's rule.
	 *
	 * @dataProvider range_provider
	 *
	 * @param string $version  Installed version.
	 * @param bool   $expected Whether it is affected.
	 */
	public function test_is_affected( $version, $expected ) {
		$ranges = array(
			array(
				'from_version'   => '1.0',
				'from_inclusive' => true,
				'to_version'     => '2.0',
				'to_inclusive'   => false,
			),
			array(
				'from_version'   => '3.0',
				'from_inclusive' => false,
				'to_version'     => '*',
				'to_inclusive'   => true,
			),
		);

		$this->assertSame( $expected, Artws_Sec_Vuln_Index::is_affected( $version, $ranges ) );
	}
}
