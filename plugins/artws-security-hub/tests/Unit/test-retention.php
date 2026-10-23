<?php
/**
 * Tests for the tables and the retention of the free security check.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Retention_Test
 */
class Artws_Sec_Retention_Test extends Artws_Sec_Scan_Test_Case {

	/**
	 * Unix time of the cleanup.
	 */
	const NOW = 1792465200;

	/**
	 * Inserts a site.
	 *
	 * @param string $url          Site URL.
	 * @param int    $last_scan_at Time of its last scan.
	 * @return int Site ID.
	 */
	private static function insert_site( $url, $last_scan_at ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture.
			Artws_Sec_Db::sites_table(),
			array(
				'site_key'     => Artws_Sec_Site_Store::site_key( $url ),
				'site_url'     => $url,
				'token_hash'   => hash( 'sha256', $url ),
				'created_at'   => $last_scan_at,
				'last_scan_at' => $last_scan_at,
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Inserts a scan.
	 *
	 * @param int $site_id    Site ID.
	 * @param int $created_at Time of the scan.
	 */
	private static function insert_scan( $site_id, $created_at ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture.
			Artws_Sec_Db::scans_table(),
			array(
				'site_id'    => $site_id,
				'scan_key'   => hash( 'sha256', $site_id . '/' . $created_at ),
				'created_at' => $created_at,
				'request'    => '{}',
				'report'     => '{}',
			)
		);
	}

	/**
	 * Returns the URLs of the remaining sites.
	 *
	 * @return string[]
	 */
	private static function site_urls() {
		global $wpdb;
		$urls = $wpdb->get_col( 'SELECT site_url FROM ' . Artws_Sec_Db::sites_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test helper.
		sort( $urls );
		return $urls;
	}

	/**
	 * Scans older than 180 days and the sites without a newer scan are deleted.
	 */
	public function test_cleanup_deletes_what_is_older_than_the_retention() {
		$limit  = self::NOW - 180 * DAY_IN_SECONDS;
		$active = self::insert_site( 'https://active.example.jp', self::NOW - DAY_IN_SECONDS );
		self::insert_scan( $active, $limit - 1 );
		self::insert_scan( $active, $limit );
		self::insert_scan( $active, self::NOW - DAY_IN_SECONDS );
		$gone = self::insert_site( 'https://gone.example.jp', $limit - 1 );
		self::insert_scan( $gone, $limit - 1 );
		self::insert_site( 'https://edge.example.jp', $limit );
		// A scan saved just before its site's last_scan_at was updated.
		$racing = self::insert_site( 'https://racing.example.jp', $limit - 10 );
		self::insert_scan( $racing, self::NOW );
		// A scan whose site was deleted while it was being saved.
		self::insert_scan( $racing + 1000, self::NOW );

		Artws_Sec_Site_Store::cleanup( self::NOW );

		$this->assertSame( array( 'https://active.example.jp', 'https://edge.example.jp', 'https://racing.example.jp' ), self::site_urls() );
		$this->assertSame( 3, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * A scan is saved only while its site exists, and taken back when the site cannot be updated.
	 */
	public function test_save_scan_needs_the_site() {
		global $wpdb;
		$site = self::insert_site( 'https://example.jp', self::NOW );

		$this->assertTrue( Artws_Sec_Site_Store::save_scan( $site, 'https://example.jp', 'scn_' . str_repeat( 'a', 43 ), array(), array(), self::NOW ) );
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );

		$sites = Artws_Sec_Db::sites_table();
		add_filter(
			'query',
			static function ( $query ) use ( $sites ) {
				return 0 === strpos( $query, "UPDATE `{$sites}`" ) ? 'UPDATE artws_sec_no_such_table SET a = 1' : $query;
			}
		);
		$suppress = $wpdb->suppress_errors( true );
		try {
			$this->assertFalse( Artws_Sec_Site_Store::save_scan( $site, 'https://example.jp', 'scn_' . str_repeat( 'b', 43 ), array(), array(), self::NOW + 1 ) );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::scans_table() ) );
		remove_all_filters( 'query' );

		Artws_Sec_Site_Store::delete_site( $site );
		$this->assertFalse( Artws_Sec_Site_Store::save_scan( $site, 'https://example.jp', 'scn_' . str_repeat( 'c', 43 ), array(), array(), self::NOW ) );
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * The retention period can be changed with a filter.
	 */
	public function test_retention_filter() {
		$site = self::insert_site( 'https://example.jp', self::NOW - 40 * DAY_IN_SECONDS );
		self::insert_scan( $site, self::NOW - 40 * DAY_IN_SECONDS );
		add_filter(
			'artws_sec_retention_days',
			static function () {
				return 30;
			}
		);

		Artws_Sec_Site_Store::cleanup( self::NOW );

		$this->assertSame( array(), self::site_urls() );
		$this->assertSame( 0, self::count_rows( Artws_Sec_Db::scans_table() ) );
	}

	/**
	 * Ended rate limit windows are deleted.
	 */
	public function test_cleanup_deletes_ended_rate_limit_windows() {
		$limits = array(
			array(
				'key'    => 'old',
				'max'    => 1,
				'window' => HOUR_IN_SECONDS,
			),
		);
		Artws_Sec_Rate_Limiter::hit( $limits, self::NOW - HOUR_IN_SECONDS );
		Artws_Sec_Rate_Limiter::hit( array( array( 'key' => 'new' ) + $limits[0] ), self::NOW - HOUR_IN_SECONDS + 1 );

		Artws_Sec_Site_Store::cleanup( self::NOW );

		$this->assertSame( 1, self::count_rows( Artws_Sec_Db::limits_table() ) );
		$this->assertSame( self::NOW + 1, Artws_Sec_Rate_Limiter::hit( array( array( 'key' => 'new' ) + $limits[0] ), self::NOW ) );
	}

	/**
	 * A window allows its hits, then opens again once it has ended.
	 */
	public function test_rate_limit_window() {
		$limit = array(
			array(
				'key'    => 'window',
				'max'    => 2,
				'window' => 100,
			),
		);

		$this->assertSame( 0, Artws_Sec_Rate_Limiter::hit( $limit, 1000 ) );
		$this->assertSame( 0, Artws_Sec_Rate_Limiter::hit( $limit, 1050 ) );
		$this->assertSame( 1100, Artws_Sec_Rate_Limiter::hit( $limit, 1099 ) );
		$this->assertSame( 0, Artws_Sec_Rate_Limiter::hit( $limit, 1100 ) );
		$this->assertSame( 0, Artws_Sec_Rate_Limiter::hit( $limit, 1101 ) );
		$this->assertSame( 1200, Artws_Sec_Rate_Limiter::hit( $limit, 1150 ) );
	}

	/**
	 * A request that passed the look for full limits but lost the last hit to another one is refused.
	 */
	public function test_rate_limit_race() {
		global $wpdb;
		$limits = array(
			array(
				'key'    => 'race',
				'max'    => 1,
				'window' => 100,
			),
		);
		$table  = Artws_Sec_Db::limits_table();
		$racing = false;
		add_filter(
			'query',
			static function ( $query ) use ( $table, &$racing, $wpdb ) {
				if ( ! $racing && false !== strpos( $query, "INSERT INTO {$table}" ) ) {
					// Another request takes the last hit just before this one.
					$racing = true;
					$wpdb->query( "INSERT INTO {$table} ( limit_key, window_start, hits ) VALUES ( 'race', 990, 1 )" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test.
				}
				return $query;
			}
		);

		$this->assertSame( 1090, Artws_Sec_Rate_Limiter::hit( $limits, 1000 ) );
		$this->assertTrue( $racing );
	}

	/**
	 * The cleanup runs daily and stops on deactivation.
	 */
	public function test_cleanup_event() {
		wp_unschedule_hook( Artws_Sec_Site_Store::CLEANUP_HOOK );

		Artws_Sec_Site_Store::schedule_events();
		$this->assertSame( 'daily', wp_get_schedule( Artws_Sec_Site_Store::CLEANUP_HOOK ) );
		$this->assertSame( 10, has_action( Artws_Sec_Site_Store::CLEANUP_HOOK, array( 'Artws_Sec_Site_Store', 'cleanup' ) ) );

		Artws_Sec_Site_Store::deactivate();
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Site_Store::CLEANUP_HOOK ) );
	}

	/**
	 * The tables are created on init when the stored version differs, and the REST routes are hooked.
	 */
	public function test_tables_are_installed() {
		global $wpdb;

		$this->assertSame( 5, has_action( 'init', array( 'Artws_Sec_Db', 'maybe_install' ) ) );
		$this->assertSame( 10, has_action( 'rest_api_init', array( 'Artws_Sec_Rest_Controller', 'register_routes' ) ) );
		$this->assertSame( Artws_Sec_Db::VERSION, get_option( Artws_Sec_Db::VERSION_OPTION ) );
		foreach ( array( Artws_Sec_Db::sites_table(), Artws_Sec_Db::scans_table(), Artws_Sec_Db::limits_table(), Artws_Sec_Db::vulns_table() ) as $table ) {
			$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Test.
		}

		// Running dbDelta() again on the installed tables changes nothing.
		$queries = array();
		add_filter(
			'query',
			static function ( $query ) use ( &$queries ) {
				$queries[] = $query;
				return $query;
			}
		);
		update_option( Artws_Sec_Db::VERSION_OPTION, '0' );
		Artws_Sec_Db::maybe_install();
		$this->assertSame( Artws_Sec_Db::VERSION, get_option( Artws_Sec_Db::VERSION_OPTION ) );
		$this->assertSame( array(), preg_grep( '/^\s*(ALTER|CREATE)\b/i', $queries ) );
	}

	/**
	 * The upgrade from version 1 drops the unique site_key, which dbDelta() cannot change, and is tried again until it does.
	 *
	 * ALTER TABLE commits the transaction the test runs in, so the version
	 * option is set back to the current version before each statement that
	 * really runs, and finally puts the table back as the current version has it.
	 */
	public function test_upgrade_from_version_1() {
		global $wpdb;
		$sites      = Artws_Sec_Db::sites_table();
		$non_unique = static function () use ( $wpdb, $sites ) {
			return $wpdb->get_row( "SHOW INDEX FROM {$sites} WHERE Key_name = 'site_key'" )->Non_unique; // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test.
		};

		try {
			// The sites table as version 1 made it.
			$wpdb->query( "ALTER TABLE {$sites} DROP INDEX site_key, ADD UNIQUE KEY site_key (site_key)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test.
			$this->assertSame( '0', (string) $non_unique() );

			// An upgrade that cannot change the table is not recorded, so the next request tries again.
			update_option( Artws_Sec_Db::VERSION_OPTION, '1' );
			add_filter(
				'query',
				static function ( $query ) {
					return 0 === stripos( ltrim( $query ), 'ALTER TABLE' ) ? 'SELECT broken FROM artws_sec_no_such_table' : $query;
				}
			);
			$suppress = $wpdb->suppress_errors( true );
			Artws_Sec_Db::maybe_install();
			$wpdb->suppress_errors( $suppress );
			$this->assertSame( '1', get_option( Artws_Sec_Db::VERSION_OPTION ) );
			$this->assertSame( '0', (string) $non_unique() );

			remove_all_filters( 'query' );
			update_option( Artws_Sec_Db::VERSION_OPTION, Artws_Sec_Db::VERSION );
			$recorded = 0;
			add_filter(
				'pre_update_option_' . Artws_Sec_Db::VERSION_OPTION,
				static function ( $value ) use ( &$recorded ) {
					++$recorded;
					return $value;
				}
			);
			Artws_Sec_Db::install();
			$this->assertSame( '1', (string) $non_unique() );
			$this->assertSame( 1, $recorded );
			$this->assertIsInt( Artws_Sec_Site_Store::register( 'https://example.jp', Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SITE_TOKEN_PREFIX ), self::NOW ) );
			$this->assertIsInt( Artws_Sec_Site_Store::register( 'https://example.jp', Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SITE_TOKEN_PREFIX ), self::NOW ) );
		} finally {
			remove_all_filters( 'query' );
			update_option( Artws_Sec_Db::VERSION_OPTION, Artws_Sec_Db::VERSION );
			Artws_Sec_Db::install();
		}
	}

	/**
	 * When the index cannot be read, the upgrade is not recorded as done: a failed query is not "no unique index".
	 */
	public function test_upgrade_is_not_recorded_when_the_index_cannot_be_read() {
		global $wpdb;
		$sites = Artws_Sec_Db::sites_table();
		update_option( Artws_Sec_Db::VERSION_OPTION, '1' );
		add_filter(
			'query',
			static function ( $query ) use ( $sites ) {
				return 0 === strpos( $query, "SHOW INDEX FROM {$sites} WHERE" ) ? 'SELECT broken FROM artws_sec_no_such_table' : $query;
			}
		);

		$suppress = $wpdb->suppress_errors( true );
		try {
			Artws_Sec_Db::maybe_install();
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
		$this->assertSame( '1', get_option( Artws_Sec_Db::VERSION_OPTION ) );

		remove_all_filters( 'query' );
		Artws_Sec_Db::maybe_install();
		$this->assertSame( Artws_Sec_Db::VERSION, get_option( Artws_Sec_Db::VERSION_OPTION ) );
	}

	/**
	 * Deleting the plugin drops its tables, its version option and the cleanup event.
	 *
	 * The test library turns DROP TABLE into DROP TEMPORARY TABLE, so the
	 * real tables stay; the test checks the statements uninstall.php sends.
	 */
	public function test_uninstall() {
		$queries = array();
		add_filter(
			'query',
			static function ( $query ) use ( &$queries ) {
				$queries[] = $query;
				return $query;
			},
			PHP_INT_MAX
		);
		Artws_Sec_Site_Store::schedule_events();
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'artws-security-hub/artws-security-hub.php' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Set by WordPress when it runs uninstall.php.
		}

		include dirname( __DIR__, 2 ) . '/uninstall.php';

		$dropped = array();
		foreach ( $queries as $query ) {
			if ( preg_match( '/^DROP (?:TEMPORARY )?TABLE IF EXISTS (\S+)$/', trim( $query ), $matches ) ) {
				$dropped[] = $matches[1];
			}
		}
		$this->assertEqualsCanonicalizing( array( Artws_Sec_Db::sites_table(), Artws_Sec_Db::scans_table(), Artws_Sec_Db::limits_table(), Artws_Sec_Db::vulns_table() ), $dropped );
		$this->assertFalse( get_option( Artws_Sec_Db::VERSION_OPTION ) );
		$this->assertFalse( wp_next_scheduled( Artws_Sec_Site_Store::CLEANUP_HOOK ) );
	}
}
