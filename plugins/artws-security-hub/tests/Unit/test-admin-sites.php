<?php
/**
 * Tests for Artws_Sec_Admin_Sites: the list of registrations, the CSV and the Sites / Plugins tabs.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- The CSV is written to a php://memory stream.

/**
 * Artws_Sec_Admin_Sites_Test
 */
class Artws_Sec_Admin_Sites_Test extends Artws_Sec_Scan_Test_Case {

	/**
	 * A fixed time for the scans.
	 */
	const NOW = 1792638000;

	/**
	 * Set up: an administrator.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		remove_all_filters( 'query' );
		unset( $_GET['s'], $_GET['paged'], $_GET['tab'], $_REQUEST['_wpnonce'] );
		delete_transient( Artws_Sec_Site_Stats::CACHE_KEY );
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
	 * @param array $overrides Request keys to replace (null removes the key).
	 * @param int   $score     Total score of the report.
	 * @throws RuntimeException When the scan is not saved.
	 */
	private static function scan( $site_id, $now, array $overrides = array(), $score = 80 ) {
		$request = self::sample_request( $overrides );
		$request = array_filter(
			$request,
			static function ( $value ) {
				return null !== $value;
			}
		);
		$site    = Artws_Sec_Site_Store::find_site( $site_id );
		$saved   = Artws_Sec_Site_Store::save_scan(
			$site_id,
			$site->site_url,
			Artws_Sec_Site_Store::new_token( Artws_Sec_Site_Store::SCAN_TOKEN_PREFIX ),
			$request,
			array( 'score' => array( 'total' => $score ) ),
			$now
		);
		if ( ! $saved ) {
			throw new RuntimeException( 'The scan was not saved.' );
		}
	}

	/**
	 * Writes the CSV of a search and returns it.
	 *
	 * @param string $search Search.
	 * @return string
	 */
	private static function csv( $search = '' ) {
		$handle = fopen( 'php://memory', 'w+' );
		// The way handle_download() writes it: the first batch is read before the file starts.
		Artws_Sec_Admin_Sites::write_csv( $handle, Artws_Sec_Admin_Sites::open_csv( $search ) );
		rewind( $handle );
		$csv = stream_get_contents( $handle );
		fclose( $handle );
		return $csv;
	}

	/**
	 * Parses the CSV without its BOM.
	 *
	 * @param string $csv CSV.
	 * @return array[]
	 */
	private static function parse_csv( $csv ) {
		$rows = array();
		foreach ( preg_split( '/\r?\n/', rtrim( substr( $csv, 3 ), "\n" ) ) as $line ) {
			$rows[] = str_getcsv( $line, ',', '"', '' );
		}
		return $rows;
	}

	/**
	 * Renders a tab of the settings page.
	 *
	 * @param string $tab Tab.
	 * @return string
	 */
	private static function render( $tab ) {
		$_GET['tab'] = $tab;
		ob_start();
		Artws_Sec_Admin::render_page();
		return ob_get_clean();
	}

	/**
	 * Breaks every read that the given SQL fragment is part of, for the rest of the test.
	 *
	 * @param string $fragment Part of the query.
	 */
	private static function break_queries( $fragment ) {
		add_filter(
			'query',
			static function ( $query ) use ( $fragment ) {
				return false !== strpos( $query, $fragment ) ? 'SELECT * FROM artws_sec_no_such_table' : $query;
			}
		);
	}

	/**
	 * The list shows the registrations, the latest scan first, with the values of their latest scans.
	 */
	public function test_list_reads_registrations_with_their_latest_scan() {
		$old    = self::register( 'https://old.example.jp', self::NOW - 500 );
		$recent = self::register( 'https://recent.example.jp', self::NOW - 400 );
		$empty  = self::register( 'https://empty.example.jp', self::NOW - 450 );
		self::scan( $old, self::NOW - 300, array( 'php_ver' => '8.2.1' ), 70 );
		self::scan( $recent, self::NOW - 200, array( 'php_ver' => '8.3.1' ), 75 );
		self::scan(
			$recent,
			self::NOW - 100,
			array(
				'php_ver' => '8.4.1',
				'locale'  => 'en_US',
			),
			90
		);

		$list = Artws_Sec_Site_Store::list_sites( '', 20, 1 );
		$rows = Artws_Sec_Admin_Sites::rows( $list['rows'], true );

		$this->assertSame( 3, $list['total'] );
		$this->assertSame( array( $recent, $old, $empty ), array_column( $rows, 'id' ) );
		$this->assertSame(
			array(
				'id'           => $recent,
				'site_url'     => 'https://recent.example.jp',
				'created_at'   => self::NOW - 400,
				'last_scan_at' => self::NOW - 100,
				'scans'        => 2,
				'jp4wc_ver'    => '2.9.19',
				'wp_ver'       => '6.9.1',
				'wc_ver'       => '10.6.2',
				'php_ver'      => '8.4.1',
				'locale'       => 'en_US',
				'score'        => 90,
				'revenue_band' => null,
				'order_band'   => null,
			),
			$rows[0]
		);
		$this->assertSame( 0, $rows[2]['scans'] );
		$this->assertSame( self::NOW - 450, $rows[2]['created_at'] );
		$this->assertNull( $rows[2]['last_scan_at'] );
		$this->assertNull( $rows[2]['php_ver'] );
		$this->assertNull( $rows[2]['score'] );
		$this->assertArrayNotHasKey( 'token_hash', (array) $list['rows'][0] );
	}

	/**
	 * The search matches part of the site URL, with % and _ taken literally.
	 */
	public function test_list_search_takes_wildcards_literally() {
		$underscore = self::register( 'https://a_b.example.jp' );
		self::register( 'https://axb.example.jp' );
		$percent = self::register( 'https://example.jp/50%off' );

		$this->assertSame( array( $underscore ), array_map( 'intval', array_column( Artws_Sec_Site_Store::list_sites( 'a_b', 20, 1 )['rows'], 'id' ) ) );
		$this->assertSame( array( $percent ), array_map( 'intval', array_column( Artws_Sec_Site_Store::list_sites( '%', 20, 1 )['rows'], 'id' ) ) );
		$this->assertSame( 2, Artws_Sec_Site_Store::list_sites( 'B.EXAMPLE', 20, 1 )['total'] );
		$this->assertSame( 0, Artws_Sec_Site_Store::list_sites( 'nothing', 20, 1 )['total'] );
	}

	/**
	 * The list is read a page at a time.
	 */
	public function test_list_pages() {
		for ( $i = 0; $i < 25; $i++ ) {
			self::register( 'https://site-' . $i . '.example.jp', self::NOW + $i );
		}

		$second = Artws_Sec_Site_Store::list_sites( '', 20, 2 );

		$this->assertSame( 25, $second['total'] );
		$this->assertCount( 5, $second['rows'] );
		$this->assertSame( 'https://site-4.example.jp', $second['rows'][0]->site_url );
		$this->assertCount( 20, Artws_Sec_Site_Store::list_sites( '', 20, 0 )['rows'] );

		$_GET['paged'] = '2';
		$html          = self::render( 'sites' );
		$this->assertStringContainsString( 'site-0.example.jp', $html );
		$this->assertStringNotContainsString( 'site-24.example.jp', $html );
		$this->assertStringContainsString( 'class="page-numbers"', $html );
	}

	/**
	 * The list shows the bands only for scans sent under a consent text that allows it.
	 */
	public function test_list_shows_bands_only_for_listed_consent_versions() {
		$listed   = self::register( 'https://listed.example.jp' );
		$earlier  = self::register( 'https://earlier.example.jp' );
		$no_bands = self::register( 'https://no-bands.example.jp' );
		self::scan(
			$listed,
			self::NOW,
			array(
				'consent_version' => '2026-11',
				'revenue_band'    => 'B4',
				'order_band'      => 'O3',
			)
		);
		self::scan( $earlier, self::NOW - 1, array( 'consent_version' => '2026-10' ) );
		self::scan(
			$no_bands,
			self::NOW - 2,
			array(
				'consent_version' => '2026-11',
				'revenue_band'    => null,
				'order_band'      => null,
			)
		);

		$rows = Artws_Sec_Admin_Sites::rows( Artws_Sec_Site_Store::list_sites( '', 20, 1 )['rows'], true );
		$this->assertSame( array( 'B4', 'O3' ), array( $rows[0]['revenue_band'], $rows[0]['order_band'] ) );
		$this->assertSame( array( null, null ), array( $rows[1]['revenue_band'], $rows[1]['order_band'] ) );
		$this->assertSame( array( null, null ), array( $rows[2]['revenue_band'], $rows[2]['order_band'] ) );

		$without = Artws_Sec_Admin_Sites::rows( Artws_Sec_Site_Store::list_sites( '', 20, 1 )['rows'], false );
		$this->assertArrayNotHasKey( 'revenue_band', $without[0] );
		$this->assertArrayNotHasKey( 'order_band', $without[0] );

		$html = self::render( 'sites' );
		$this->assertMatchesRegularExpression( '/<td>B4<\/td>\s*<td>O3<\/td>/', $html );
		$this->assertDoesNotMatchRegularExpression( '/<td>B2<\/td>/', $html );
	}

	/**
	 * The CSV has a BOM, the list's columns without the bands, ISO 8601 times and every registration in ID order.
	 */
	public function test_csv_has_the_list_columns_without_bands() {
		$first  = self::register( 'https://first.example.jp', self::NOW - 100 );
		$second = self::register( 'https://second.example.jp', self::NOW - 50 );
		self::scan(
			$first,
			self::NOW,
			array(
				'consent_version' => '2026-11',
				'revenue_band'    => 'B4',
				'order_band'      => 'O3',
			)
		);

		$csv = self::csv();

		$this->assertSame( "\xEF\xBB\xBF", substr( $csv, 0, 3 ) );
		$rows = self::parse_csv( $csv );
		$this->assertSame( array_values( Artws_Sec_Admin_Sites::columns( false ) ), $rows[0] );
		$this->assertNotContains( 'Revenue band', $rows[0] );
		$this->assertCount( 3, $rows );
		$this->assertSame( array( (string) $first, 'https://first.example.jp' ), array_slice( $rows[1], 0, 2 ) );
		$this->assertSame( (string) $second, $rows[2][0] );
		$this->assertSame( wp_date( 'c', self::NOW - 100 ), $rows[1][2] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/D', $rows[1][3] );
		$this->assertSame( array( '1', '2.9.19', '6.9.1', '10.6.2', '8.3.12', 'ja', '80' ), array_slice( $rows[1], 4 ) );
		$this->assertSame( wp_date( 'c', self::NOW - 50 ), $rows[2][2] );
		$this->assertSame( array( '', '0', '', '', '', '', '', '' ), array_slice( $rows[2], 3 ) );
		$this->assertStringNotContainsString( 'B4', $csv );
		$this->assertStringNotContainsString( 'O3', $csv );
	}

	/**
	 * Values that a spreadsheet would read as a formula get a leading apostrophe.
	 *
	 * The versions are where a sender can put one: POST /scan takes any text
	 * without control characters there, while site_url must start with
	 * http(s):// and locale with a letter. Every column is defused all the same.
	 */
	public function test_csv_defuses_formulas() {
		self::publish_contract_feed();
		$response = self::send(
			'POST',
			'/scan',
			self::sample_request(
				array(
					'jp4wc_ver' => '@SUM(1)',
					'wp_ver'    => '=1+1',
					'wc_ver'    => '+1',
					'php_ver'   => '-1',
				)
			)
		);
		$this->assertSame( 200, $response->get_status() );

		$rows = self::parse_csv( self::csv() );

		$this->assertSame( array( "'@SUM(1)", "'=1+1", "'+1", "'-1" ), array_slice( $rows[1], 5, 4 ) );
		$this->assertSame( 'https://example.jp', $rows[1][1] );
		$this->assertSame( 'ja', $rows[1][9] );
		foreach ( array( '=A1', '+A1', '-A1', '@A1', "\tA1", "\rA1" ) as $value ) {
			$this->assertSame( "'" . $value, Artws_Sec_Admin_Sites::csv_value( 'site_url', $value ) );
		}
		$this->assertSame( 'https://example.jp', Artws_Sec_Admin_Sites::csv_value( 'site_url', 'https://example.jp' ) );
		$this->assertSame( '8.3.12', Artws_Sec_Admin_Sites::csv_value( 'php_ver', '8.3.12' ) );
		$this->assertSame( '', Artws_Sec_Admin_Sites::csv_value( 'php_ver', null ) );
	}

	/**
	 * The CSV keeps to the search and reads past one batch.
	 */
	public function test_csv_follows_the_search_across_batches() {
		$count = Artws_Sec_Admin_Sites::BATCH_SIZE + 1;
		for ( $i = 0; $i < $count; $i++ ) {
			self::register( 'https://match-' . $i . '.example.jp' );
		}
		self::register( 'https://other.example.jp' );

		$rows = self::parse_csv( self::csv( 'match-' ) );

		$this->assertCount( $count + 1, $rows );
		$ids = array_map( 'intval', array_column( array_slice( $rows, 1 ), 0 ) );
		$this->assertSame( $ids, array_values( array_unique( $ids ) ) );
		$sorted = $ids;
		sort( $sorted );
		$this->assertSame( $sorted, $ids );
		$this->assertStringNotContainsString( 'other.example.jp', self::csv( 'match-' ) );
	}

	/**
	 * A CSV without registrations has the header row only.
	 */
	public function test_csv_without_registrations() {
		self::register( 'https://example.jp' );

		foreach ( array( 'no-such-site', '' ) as $search ) {
			if ( '' === $search ) {
				global $wpdb;
				$wpdb->query( 'DELETE FROM ' . Artws_Sec_Db::sites_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Test cleanup of a custom table.
			}
			$rows = self::parse_csv( self::csv( $search ) );
			$this->assertSame( array( array_values( Artws_Sec_Admin_Sites::columns( false ) ) ), $rows, $search );
		}
	}

	/**
	 * A read that fails after the file has started ends it with a note.
	 */
	public function test_csv_says_when_it_is_not_complete() {
		$batches = ( static function () {
			yield array(
				Artws_Sec_Admin_Sites::build_row(
					(object) array(
						'id'           => 7,
						'site_url'     => 'https://example.jp',
						'created_at'   => self::NOW,
						'last_scan_at' => self::NOW,
					),
					null,
					0,
					false
				),
			);
			throw new Artws_Sec_Feed_Exception( 'The stored scans could not be read.' );
		} )();

		$handle = fopen( 'php://memory', 'w+' );
		$this->assertFalse( Artws_Sec_Admin_Sites::write_csv( $handle, $batches ) );
		rewind( $handle );
		$rows = self::parse_csv( stream_get_contents( $handle ) );
		fclose( $handle );

		$this->assertCount( 3, $rows );
		$this->assertSame( '7', $rows[1][0] );
		$this->assertStringContainsString( 'not complete', $rows[2][0] );
	}

	/**
	 * The download needs manage_options.
	 */
	public function test_download_needs_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$_REQUEST['_wpnonce'] = wp_create_nonce( Artws_Sec_Admin_Sites::DOWNLOAD_ACTION );

		try {
			Artws_Sec_Admin_Sites::handle_download();
			$this->fail( 'Expected wp_die().' );
		} catch ( WPDieException $e ) {
			$this->assertSame( 403, $e->getCode() );
		}
	}

	/**
	 * The download needs its nonce.
	 */
	public function test_download_needs_a_nonce() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'artws_sec_run_update' );

		try {
			Artws_Sec_Admin_Sites::handle_download();
			$this->fail( 'Expected wp_die().' );
		} catch ( WPDieException $e ) {
			$this->assertSame( 403, $e->getCode() );
		}
	}

	/**
	 * A first read that fails answers with an error page instead of an empty file.
	 */
	public function test_download_answers_500_when_the_list_cannot_be_read() {
		global $wpdb;
		self::register( 'https://example.jp' );
		$_REQUEST['_wpnonce'] = wp_create_nonce( Artws_Sec_Admin_Sites::DOWNLOAD_ACTION );
		self::break_queries( 'SELECT id, site_url' );

		$suppress = $wpdb->suppress_errors( true );
		try {
			Artws_Sec_Admin_Sites::handle_download();
			$this->fail( 'Expected wp_die().' );
		} catch ( WPDieException $e ) {
			$this->assertSame( 500, $e->getCode() );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}

	/**
	 * The Sites tab shows the aggregates, the download with the search and the escaped list.
	 */
	public function test_sites_tab() {
		// 7 sites in B2 / O2 (with the one below) and 6 in B3 / O3: every other band is empty.
		for ( $i = 0; $i < 12; $i++ ) {
			$bands = $i < 6 ? array() : array(
				'revenue_band' => 'B3',
				'order_band'   => 'O3',
			);
			self::scan( self::register( 'https://shop-' . $i . '.example.jp' ), self::NOW + $i, $bands );
		}
		$evil = self::register( 'https://example.jp/"><script>alert(1)</script>' );
		self::scan( $evil, self::NOW + 10, array( 'php_ver' => '<b>8.4</b>' ) );
		$_GET['s'] = 'example.jp/"';

		$html = self::render( 'sites' );

		$this->assertStringContainsString( '13 sites with a scan', $html );
		$this->assertMatchesRegularExpression( '/<th scope="row">8\.3<\/th>\s*<td>12<\/td>\s*<td>92\.3%<\/td>/', $html );
		$this->assertMatchesRegularExpression( '/<th scope="row">Unknown<\/th>\s*<td>1<\/td>/', $html );
		// The empty bands are known to be 0 unless one of the two others is withheld too.
		$this->assertMatchesRegularExpression( '/<th scope="row">B2<\/th>\s*<td>7<\/td>\s*<td>53\.8%<\/td>/', $html );
		$this->assertMatchesRegularExpression( '/<th scope="row">B3<\/th>\s*<td>—<\/td>\s*<td><\/td>/', $html );
		$this->assertMatchesRegularExpression( '/<th scope="row">B0<\/th>\s*<td>Under 5<\/td>\s*<td><\/td>/', $html );
		$this->assertStringContainsString( 'article 6, paragraph 6', $html );
		$this->assertMatchesRegularExpression( '/admin-post\.php\?action=artws_sec_download_sites&#038;s=example\.jp%2F%22&#038;_wpnonce=[0-9a-f]+/', $html );
		$this->assertStringContainsString( '1 registration.', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
		$this->assertStringContainsString( '&lt;b&gt;8.4&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<script>alert(1)', $html );
		$this->assertStringNotContainsString( '<b>8.4</b>', $html );
	}

	/**
	 * A failed read shows an error instead of an empty list.
	 */
	public function test_sites_tab_shows_failed_reads() {
		global $wpdb;
		self::scan( self::register( 'https://example.jp' ), self::NOW );
		self::break_queries( Artws_Sec_Db::sites_table() );
		self::break_queries( Artws_Sec_Db::scans_table() );

		$suppress = $wpdb->suppress_errors( true );
		try {
			$html = self::render( 'sites' );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertStringContainsString( 'The aggregates could not be read.', $html );
		$this->assertStringContainsString( 'The registrations could not be read.', $html );
		$this->assertStringNotContainsString( 'No registrations found.', $html );
		$this->assertStringNotContainsString( '0 sites with a scan', $html );
	}

	/**
	 * The Plugins tab lists the plugins of the latest scans.
	 */
	public function test_plugins_tab() {
		self::scan( self::register( 'https://one.example.jp' ), self::NOW );
		self::scan( self::register( 'https://two.example.jp' ), self::NOW );

		$html = self::render( 'plugins' );

		$this->assertStringContainsString( '2 sites with a scan', $html );
		$this->assertMatchesRegularExpression( '/<td>1<\/td>\s*<td><code>woocommerce<\/code><\/td>\s*<td>2<\/td>\s*<td>2<\/td>\s*<td>100\.0%<\/td>/', $html );
		$this->assertMatchesRegularExpression( '/<td>3<\/td>\s*<td><code>sample-plugin<\/code><\/td>\s*<td>0<\/td>\s*<td>2<\/td>/', $html );
		$this->assertStringNotContainsString( 'twentytwentyfive', $html );
	}
}
