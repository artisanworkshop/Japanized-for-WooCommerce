<?php
/**
 * Tests for Artws_Sec_Site_Stats and the reads behind it.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Site_Stats_Test
 */
class Artws_Sec_Site_Stats_Test extends Artws_Sec_Scan_Test_Case {

	/**
	 * A fixed time for the scans.
	 */
	const NOW = 1792638000;

	/**
	 * Tear down: no cached aggregates and the default cache threshold.
	 */
	public function tear_down() {
		remove_all_filters( 'query' );
		delete_transient( Artws_Sec_Site_Stats::CACHE_KEY );
		( new ReflectionProperty( 'Artws_Sec_Site_Stats', 'cache_after' ) )->setValue( null, 1.0 );
		parent::tear_down();
	}

	/**
	 * Registers a site and returns its ID.
	 *
	 * @param string $url Site URL.
	 * @param int    $now Time of the registration.
	 * @return int
	 */
	private static function register( $url, $now = self::NOW ) {
		return (int) Artws_Sec_Site_Store::register( $url, Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SITE_TOKEN_PREFIX ), $now );
	}

	/**
	 * Saves a scan of a site.
	 *
	 * @param int   $site_id   Site ID.
	 * @param int   $now       Time of the scan.
	 * @param array $overrides Request keys to replace.
	 * @throws RuntimeException When the scan is not saved.
	 */
	private static function scan( $site_id, $now, array $overrides = array() ) {
		$saved = Artws_Sec_Site_Store::save_scan(
			$site_id,
			'https://example.jp',
			Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SCAN_TOKEN_PREFIX ),
			self::sample_request( $overrides ),
			array( 'score' => array( 'total' => 80 ) ),
			$now
		);
		if ( ! $saved ) {
			throw new RuntimeException( 'The scan was not saved.' );
		}
	}

	/**
	 * Versions are bucketed by their first two numbers.
	 *
	 * @dataProvider data_version_bucket
	 *
	 * @param mixed  $version  Version as sent.
	 * @param string $expected Bucket.
	 */
	public function test_version_bucket( $version, $expected ) {
		$this->assertSame( $expected, Artws_Sec_Site_Stats::version_bucket( $version ) );
	}

	/**
	 * Data for test_version_bucket().
	 *
	 * @return array
	 */
	public function data_version_bucket() {
		return array(
			'patch'          => array( '8.3.12', '8.3' ),
			'minor'          => array( '6.9', '6.9' ),
			'two digits'     => array( '10.2.2', '10.2' ),
			'release suffix' => array( '7.0-RC1', '7.0' ),
			'leading zeros'  => array( '08.03', '8.3' ),
			'major only'     => array( '10', Artws_Sec_Site_Stats::UNKNOWN ),
			'empty'          => array( '', Artws_Sec_Site_Stats::UNKNOWN ),
			'text first'     => array( 'v8.3', Artws_Sec_Site_Stats::UNKNOWN ),
			'not a string'   => array( 8.3, Artws_Sec_Site_Stats::UNKNOWN ),
			'missing'        => array( null, Artws_Sec_Site_Stats::UNKNOWN ),
		);
	}

	/**
	 * Buckets are ordered from the newest, with Unknown last.
	 */
	public function test_sort_versions() {
		$this->assertSame(
			array(
				'10.2'                        => 1,
				'9.9'                         => 2,
				'8.10'                        => 3,
				'8.3'                         => 4,
				Artws_Sec_Site_Stats::UNKNOWN => 5,
			),
			Artws_Sec_Site_Stats::sort_versions(
				array(
					'8.3'                         => 4,
					Artws_Sec_Site_Stats::UNKNOWN => 5,
					'10.2'                        => 1,
					'8.10'                        => 3,
					'9.9'                         => 2,
				)
			)
		);
	}

	/**
	 * Returns the state of each cell of suppress_bands().
	 *
	 * @param array $result From suppress_bands().
	 * @return array<string, array<string, string>>
	 */
	private static function states( array $result ) {
		$states = array();
		foreach ( $result as $table => $cells ) {
			$states[ $table ] = array_map(
				static function ( $cell ) {
					return $cell['state'];
				},
				$cells
			);
		}
		return $states;
	}

	/**
	 * Band cells of 5 or more sites are shown with their numbers.
	 */
	public function test_bands_of_five_or_more_are_shown() {
		$result = Artws_Sec_Site_Stats::suppress_bands(
			array_combine( Artws_Sec_Scan_Request::REVENUE_BANDS, array( 5, 6, 7, 8, 9, 10 ) ),
			array_combine( Artws_Sec_Scan_Request::ORDER_BANDS, array( 9, 9, 9, 9, 9 ) ),
			5
		);

		$this->assertSame(
			array(
				'count' => 7,
				'state' => Artws_Sec_Site_Stats::SHOWN,
			),
			$result['revenue']['B2']
		);
		$this->assertSame(
			array(
				'count' => 5,
				'state' => Artws_Sec_Site_Stats::SHOWN,
			),
			$result['order'][ Artws_Sec_Site_Stats::NO_BAND ]
		);
		$this->assertSame( array( 'B0', 'B1', 'B2', 'B3', 'B4', 'B5', Artws_Sec_Site_Stats::NO_BAND ), array_keys( $result['revenue'] ) );
		foreach ( self::states( $result ) as $cells ) {
			$this->assertSame( array( Artws_Sec_Site_Stats::SHOWN ), array_values( array_unique( $cells ) ) );
		}
	}

	/**
	 * A lone cell under 5 takes the next smallest cell (the first of equal ones) with it.
	 */
	public function test_a_lone_small_band_hides_the_next_smallest() {
		$states = self::states(
			Artws_Sec_Site_Stats::suppress_bands(
				array_combine( Artws_Sec_Scan_Request::REVENUE_BANDS, array( 3, 6, 6, 10, 10, 10 ) ),
				array_combine( Artws_Sec_Scan_Request::ORDER_BANDS, array( 9, 9, 9, 9, 9 ) ),
				10
			)
		);

		$this->assertSame(
			array(
				'B0'                          => Artws_Sec_Site_Stats::UNDER_MIN,
				'B1'                          => Artws_Sec_Site_Stats::HIDDEN,
				'B2'                          => Artws_Sec_Site_Stats::SHOWN,
				'B3'                          => Artws_Sec_Site_Stats::SHOWN,
				'B4'                          => Artws_Sec_Site_Stats::SHOWN,
				'B5'                          => Artws_Sec_Site_Stats::SHOWN,
				Artws_Sec_Site_Stats::NO_BAND => Artws_Sec_Site_Stats::SHOWN,
			),
			$states['revenue']
		);
		$this->assertNotContains( Artws_Sec_Site_Stats::HIDDEN, $states['order'] );
	}

	/**
	 * Zero is under 5 as well, and two small cells need no other cell hidden.
	 */
	public function test_zero_is_under_five() {
		$states = self::states(
			Artws_Sec_Site_Stats::suppress_bands(
				array_combine( Artws_Sec_Scan_Request::REVENUE_BANDS, array( 0, 6, 6, 10, 10, 10 ) ),
				array_combine( Artws_Sec_Scan_Request::ORDER_BANDS, array( 2, 10, 10, 10, 10 ) ),
				12
			)
		);

		$this->assertSame( Artws_Sec_Site_Stats::UNDER_MIN, $states['revenue']['B0'] );
		$this->assertSame( Artws_Sec_Site_Stats::HIDDEN, $states['revenue']['B1'] );
		$this->assertSame( Artws_Sec_Site_Stats::UNDER_MIN, $states['order']['O0'] );
		$this->assertSame( Artws_Sec_Site_Stats::HIDDEN, $states['order']['O1'] );
	}

	/**
	 * The "not sent" cell is the same in both tables, so hiding it in one hides it in the other, which may need one more cell there.
	 */
	public function test_the_no_band_cell_is_hidden_in_both_tables() {
		$states = self::states(
			Artws_Sec_Site_Stats::suppress_bands(
				array_combine( Artws_Sec_Scan_Request::REVENUE_BANDS, array( 20, 2, 20, 20, 20, 20 ) ),
				array_combine( Artws_Sec_Scan_Request::ORDER_BANDS, array( 24, 24, 24, 24, 6 ) ),
				5
			)
		);

		$this->assertSame( Artws_Sec_Site_Stats::UNDER_MIN, $states['revenue']['B1'] );
		$this->assertSame( Artws_Sec_Site_Stats::HIDDEN, $states['revenue'][ Artws_Sec_Site_Stats::NO_BAND ] );
		$this->assertSame( Artws_Sec_Site_Stats::HIDDEN, $states['order'][ Artws_Sec_Site_Stats::NO_BAND ] );
		$this->assertSame( Artws_Sec_Site_Stats::HIDDEN, $states['order']['O4'] );
		$this->assertCount( 4, array_keys( $states['order'], Artws_Sec_Site_Stats::SHOWN, true ) );
		foreach ( $states as $cells ) {
			$this->assertNotSame( 1, count( $cells ) - count( array_keys( $cells, Artws_Sec_Site_Stats::SHOWN, true ) ) );
		}
	}

	/**
	 * A few sites leave every cell under 5.
	 */
	public function test_few_sites_hide_every_band() {
		$result = Artws_Sec_Site_Stats::suppress_bands(
			array(
				'B0' => 0,
				'B1' => 0,
				'B2' => 1,
				'B3' => 0,
				'B4' => 0,
				'B5' => 0,
			),
			array(
				'O0' => 0,
				'O1' => 1,
				'O2' => 0,
				'O3' => 0,
				'O4' => 0,
			),
			2
		);

		foreach ( self::states( $result ) as $cells ) {
			$this->assertSame( array( Artws_Sec_Site_Stats::UNDER_MIN ), array_values( array_unique( $cells ) ) );
		}
	}

	/**
	 * Plugins are ranked by active sites, then by slug; installed sites count inactive ones; a slug listed twice counts once; themes do not count.
	 */
	public function test_top_plugins() {
		$plugin = static function ( $slug, $active, $type = 'plugin' ) {
			return array(
				'type'    => $type,
				'slug'    => $slug,
				'version' => '1.0',
				'active'  => $active,
			);
		};
		$stats  = new Artws_Sec_Site_Stats();
		$stats->add( array( 'software' => array( $plugin( 'alpha', true ), $plugin( 'beta', true ), $plugin( 'gamma', false ), $plugin( 'alpha', false ), $plugin( 'theme-a', true, 'theme' ) ) ) );
		$stats->add( array( 'software' => array( $plugin( 'alpha', false ), $plugin( 'beta', true ), $plugin( 'delta', true ), $plugin( 'delta', true ) ) ) );
		$stats->add( array( 'software' => array( $plugin( 'beta', true ), $plugin( '123', false ) ) ) );
		$stats->add( array( 'software' => 'not a list' ) );

		$this->assertSame(
			array(
				array(
					'rank'      => 1,
					'slug'      => 'beta',
					'active'    => 3,
					'installed' => 3,
				),
				array(
					'rank'      => 2,
					'slug'      => 'alpha',
					'active'    => 1,
					'installed' => 2,
				),
				array(
					'rank'      => 2,
					'slug'      => 'delta',
					'active'    => 1,
					'installed' => 1,
				),
				array(
					'rank'      => 4,
					'slug'      => '123',
					'active'    => 0,
					'installed' => 1,
				),
				array(
					'rank'      => 4,
					'slug'      => 'gamma',
					'active'    => 0,
					'installed' => 1,
				),
			),
			$stats->top_plugins( 30 )
		);
	}

	/**
	 * The Plugins tab lists 30 plugins at most.
	 */
	public function test_top_plugins_stop_at_thirty() {
		$software = array();
		for ( $i = 1; $i <= 35; $i++ ) {
			$software[] = array(
				'type'    => 'plugin',
				'slug'    => sprintf( 'plugin-%02d', $i ),
				'version' => '1.0',
				'active'  => true,
			);
		}
		$stats = new Artws_Sec_Site_Stats();
		$stats->add( array( 'software' => $software ) );

		$result = $stats->result( self::NOW );
		$this->assertCount( Artws_Sec_Site_Stats::TOP_PLUGINS, $result['plugins'] );
		$this->assertSame( 'plugin-01', $result['plugins'][0]['slug'] );
		$this->assertSame( 'plugin-30', $result['plugins'][29]['slug'] );
		$this->assertSame( array( 1 ), array_values( array_unique( array_column( $result['plugins'], 'rank' ) ) ) );
	}

	/**
	 * Every registration of a site_key counts as one site, from the latest scan over all of them.
	 */
	public function test_collect_counts_each_site_once_from_its_latest_scan() {
		// The same site three times: the scheme, the case of the host and a trailing slash do not matter.
		$first  = self::register( 'https://shop.example.jp' );
		$second = self::register( 'http://SHOP.example.jp/' );
		$third  = self::register( 'https://shop.example.jp' );
		self::scan( $first, self::NOW, array( 'php_ver' => '8.1.2' ) );
		self::scan( $first, self::NOW + 100, array( 'php_ver' => '8.2.3' ) );
		self::scan( $second, self::NOW + 50, array( 'php_ver' => '8.3.4' ) );
		self::scan( $third, self::NOW + 99, array( 'php_ver' => '8.4.0' ) );

		// Two scans in the same second: the one saved last is the latest.
		$other = self::register( 'https://other.example.jp' );
		self::scan( $other, self::NOW + 10, array( 'php_ver' => '7.4.33' ) );
		self::scan( $other, self::NOW + 10, array( 'php_ver' => '8.0.30' ) );

		// A registration whose scans are gone (past the retention period) does not count.
		self::register( 'https://expired.example.jp' );

		$result = Artws_Sec_Site_Stats::collect();

		$this->assertSame( 2, $result['sites'] );
		$this->assertSame(
			array(
				'8.2' => 1,
				'8.0' => 1,
			),
			$result['versions']['php']
		);
		$this->assertSame( array( '6.9' => 2 ), $result['versions']['wordpress'] );
		$this->assertSame( array( '10.6' => 2 ), $result['versions']['woocommerce'] );
		foreach ( $result['versions'] as $counts ) {
			$this->assertSame( $result['sites'], array_sum( $counts ) );
		}
		$this->assertSame( 2, $result['bands']['revenue']['B2']['count'] );
		$this->assertSame( 2, $result['plugins'][0]['active'] );
		$this->assertFalse( get_transient( Artws_Sec_Site_Stats::CACHE_KEY ) );
	}

	/**
	 * The aggregates read the scans in batches and count sites without bands.
	 */
	public function test_collect_reads_past_one_batch() {
		$count = Artws_Sec_Site_Stats::BATCH_SIZE + 3;
		for ( $i = 0; $i < $count; $i++ ) {
			$site = self::register( 'https://site-' . $i . '.example.jp' );
			$sent = self::sample_request();
			unset( $sent['revenue_band'], $sent['order_band'] );
			Artws_Sec_Site_Store::save_scan( $site, 'https://site-' . $i . '.example.jp', Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SCAN_TOKEN_PREFIX ), $sent, array(), self::NOW );
		}

		$result = Artws_Sec_Site_Stats::collect();

		$this->assertSame( $count, $result['sites'] );
		$this->assertSame( $count, $result['bands']['revenue'][ Artws_Sec_Site_Stats::NO_BAND ]['count'] );
		$this->assertSame( 0, $result['bands']['order']['O2']['count'] );
	}

	/**
	 * Only the scans saved before the count started are read.
	 */
	public function test_latest_requests_read_up_to_the_snapshot() {
		$site = self::register( 'https://shop.example.jp' );
		self::scan( $site, self::NOW, array( 'php_ver' => '8.2.0' ) );
		$max = Artws_Sec_Site_Store::max_scan_id();
		self::scan( $site, self::NOW + 1, array( 'php_ver' => '8.3.0' ) );

		$rows = Artws_Sec_Site_Store::latest_requests_by_site_key( 0, $max, 10 );

		$this->assertCount( 1, $rows );
		$this->assertSame( '8.2.0', json_decode( $rows[0]->request, true )['php_ver'] );
		$this->assertSame( array(), Artws_Sec_Site_Store::latest_requests_by_site_key( (int) $rows[0]->id, $max, 10 ) );
	}

	/**
	 * A slow count is kept for a while.
	 */
	public function test_collect_caches_a_slow_count() {
		( new ReflectionProperty( 'Artws_Sec_Site_Stats', 'cache_after' ) )->setValue( null, -1.0 );
		self::scan( self::register( 'https://one.example.jp' ), self::NOW );

		$first = Artws_Sec_Site_Stats::collect();
		self::scan( self::register( 'https://two.example.jp' ), self::NOW );

		$this->assertSame( $first, Artws_Sec_Site_Stats::collect() );
		$this->assertSame( 1, $first['sites'] );

		delete_transient( Artws_Sec_Site_Stats::CACHE_KEY );
		$this->assertSame( 2, Artws_Sec_Site_Stats::collect()['sites'] );
	}

	/**
	 * A failed read is not taken for "no sites".
	 */
	public function test_collect_throws_when_the_scans_cannot_be_read() {
		global $wpdb;
		self::scan( self::register( 'https://one.example.jp' ), self::NOW );
		add_filter(
			'query',
			static function ( $query ) {
				return false !== strpos( $query, 'NOT EXISTS' ) ? 'SELECT * FROM artws_sec_no_such_table' : $query;
			}
		);

		$suppress = $wpdb->suppress_errors( true );
		try {
			Artws_Sec_Site_Stats::collect();
			$this->fail( 'Expected an exception.' );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			$this->assertFalse( get_transient( Artws_Sec_Site_Stats::CACHE_KEY ) );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}
}
