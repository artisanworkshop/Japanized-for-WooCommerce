<?php
/**
 * Settings page tabs of the free security check: the registered sites and the plugins they use.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Admin_Sites
 *
 * The Sites tab shows the aggregates, the list of registrations and the CSV
 * download; the Plugins tab the plugins active on the most sites. The list
 * and the CSV are built from the same rows. The revenue and order bands are
 * shown in the list only for scans sent under a consent text that allows the
 * staff to check each registration, and never written to the CSV: the terms
 * of use (article 6.6) allow the CSV only for aggregation, without the bands,
 * and promise to delete it right after use.
 */
class Artws_Sec_Admin_Sites {

	/**
	 * Consent texts whose bands the list may show.
	 *
	 * 2026-11: the terms of use in force from 2026-11-05, which add the staff's
	 * check of each registration (article 4.4.4) to the purposes the bands may
	 * be used for (article 4.5). The bands of a 2026-10 consent are not used for
	 * it until the store agrees again (supplementary provision 2).
	 */
	const BAND_LISTING_CONSENT_VERSIONS = array( '2026-11' );

	/**
	 * Registrations per page of the list.
	 */
	const PER_PAGE = 20;

	/**
	 * Registrations read at a time for the CSV.
	 */
	const BATCH_SIZE = 200;

	/**
	 * The admin-post action (and nonce action) of the CSV download.
	 */
	const DOWNLOAD_ACTION = 'artws_sec_download_sites';

	/**
	 * Characters that make a spreadsheet read a cell as a formula.
	 */
	const FORMULA_STARTS = "=+-@\t\r";

	/**
	 * Renders the Sites tab.
	 */
	public static function render_sites_tab() {
		$search = self::search();
		$page   = self::page();

		echo '<h2>' . esc_html__( 'Registered sites', 'artws-security-hub' ) . '</h2>';
		try {
			$stats = Artws_Sec_Site_Stats::collect();
		} catch ( Artws_Sec_Feed_Exception $e ) {
			$stats = null;
			self::render_error( __( 'The aggregates could not be read. Reload the page to try again.', 'artws-security-hub' ) );
		}
		if ( null !== $stats ) {
			self::render_basis( $stats );
			if ( 0 < $stats['sites'] ) {
				self::render_version_tables( $stats );
				self::render_band_tables( $stats );
			}
		}

		echo '<h2>' . esc_html__( 'Registrations', 'artws-security-hub' ) . '</h2>';
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of days. */
				__( 'Scans are kept for %d days and then deleted by the daily cleanup, together with the registrations that have no scan left. They are not listed here.', 'artws-security-hub' ),
				Artws_Sec_Site_Store::retention_days()
			)
		) . '</p>';
		self::render_search_form( $search );
		self::render_download( $search );

		try {
			$list = Artws_Sec_Site_Store::list_sites( $search, self::PER_PAGE, $page );
			$rows = self::rows( $list['rows'], true );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			self::render_error( __( 'The registrations could not be read. Reload the page to try again.', 'artws-security-hub' ) );
			return;
		}
		self::render_list( $list['total'], $rows, $search, $page );
	}

	/**
	 * Renders the Plugins tab.
	 */
	public static function render_plugins_tab() {
		echo '<h2>' . esc_html__( 'Plugins on the most sites', 'artws-security-hub' ) . '</h2>';
		try {
			$stats = Artws_Sec_Site_Stats::collect();
		} catch ( Artws_Sec_Feed_Exception $e ) {
			self::render_error( __( 'The aggregates could not be read. Reload the page to try again.', 'artws-security-hub' ) );
			return;
		}
		self::render_basis( $stats );
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %d: number of plugins. */
				__( 'The %d plugins active on the most sites. Sites send only the slug, so plugins are listed by slug.', 'artws-security-hub' ),
				Artws_Sec_Site_Stats::TOP_PLUGINS
			)
		) . '</p>';
		?>
		<table class="widefat striped" style="max-width: 60em;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Rank', 'artws-security-hub' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Slug', 'artws-security-hub' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Active sites', 'artws-security-hub' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Installed sites', 'artws-security-hub' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Active share', 'artws-security-hub' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( array() === $stats['plugins'] ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No plugins yet.', 'artws-security-hub' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $stats['plugins'] as $plugin ) : ?>
					<tr>
						<td><?php echo esc_html( number_format_i18n( $plugin['rank'] ) ); ?></td>
						<td><code><?php echo esc_html( $plugin['slug'] ); ?></code></td>
						<td><?php echo esc_html( number_format_i18n( $plugin['active'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $plugin['installed'] ) ); ?></td>
						<td><?php echo esc_html( self::share( $plugin['active'], $stats['sites'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Sends the registrations as a CSV file.
	 */
	public static function handle_download() {
		Artws_Sec_Admin::check_request( self::DOWNLOAD_ACTION );

		try {
			$batches = self::open_csv( self::search() );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			wp_die( esc_html__( 'The registrations could not be read. Try again later.', 'artws-security-hub' ), '', array( 'response' => 500 ) );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="artws-security-hub-sites-' . wp_date( 'Ymd-His' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );
		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Writes the response body, not a file.
		self::write_csv( $output, $batches );
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- See fopen() above.
		exit;
	}

	/**
	 * Writes the CSV: a UTF-8 BOM (for Excel), the header row and the registrations.
	 *
	 * The columns are those of the list without the bands. A read that fails
	 * once the file has started ends it with a row that says it is not
	 * complete, since the response can no longer turn into an error.
	 *
	 * @param resource $handle  Stream to write to.
	 * @param iterable $batches Rows from csv_batches().
	 * @return bool Whether every registration was written.
	 */
	public static function write_csv( $handle, $batches ) {
		fwrite( $handle, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writes to the given stream.
		$columns = self::columns( false );
		self::put_csv( $handle, array_values( $columns ) );

		try {
			foreach ( $batches as $rows ) {
				foreach ( $rows as $row ) {
					$cells = array();
					foreach ( array_keys( $columns ) as $key ) {
						$cells[] = self::csv_value( $key, $row[ $key ] );
					}
					self::put_csv( $handle, $cells );
				}
			}
		} catch ( Artws_Sec_Feed_Exception $e ) {
			self::put_csv( $handle, array( __( 'The registrations could not all be read: this file is not complete.', 'artws-security-hub' ) ) );
			return false;
		}
		return true;
	}

	/**
	 * Starts reading the registrations for the CSV and reads the first batch.
	 *
	 * The first batch is read before anything is sent, so that a failed read
	 * can still answer with an error page.
	 *
	 * @param string $search Part of the site URL to look for, or ''.
	 * @return Generator<int, array[]> From csv_batches(), at its first batch.
	 * @throws Artws_Sec_Feed_Exception When the data cannot be read.
	 */
	public static function open_csv( $search ) {
		$batches = self::csv_batches( $search );
		$batches->current();
		return $batches;
	}

	/**
	 * Reads the registrations for the CSV, BATCH_SIZE at a time in the order of their IDs.
	 *
	 * The first batch is yielded even when it is empty: a generator that ends
	 * before its first yield is closed, and open_csv() has already started it,
	 * so the foreach of write_csv() would throw.
	 *
	 * @param string $search Part of the site URL to look for, or ''.
	 * @return Generator<int, array[]> Rows from build_row() without the bands.
	 * @throws Artws_Sec_Feed_Exception When the data cannot be read.
	 */
	public static function csv_batches( $search ) {
		$after = 0;
		do {
			$sites = Artws_Sec_Site_Store::sites_after( $after, $search, self::BATCH_SIZE );
			yield self::rows( $sites, false );
			if ( array() === $sites ) {
				return;
			}
			$after = (int) end( $sites )->id;
			$read  = count( $sites );
		} while ( self::BATCH_SIZE === $read );
	}

	/**
	 * Builds the rows of registrations with their latest scans.
	 *
	 * @param object[] $sites      Rows of the sites table.
	 * @param bool     $with_bands Whether the rows may carry the bands (the list) or not (the CSV).
	 * @return array[] Rows from build_row().
	 * @throws Artws_Sec_Feed_Exception When the data cannot be read.
	 */
	public static function rows( array $sites, $with_bands ) {
		$ids    = array_map(
			static function ( $site ) {
				return (int) $site->id;
			},
			$sites
		);
		$latest = Artws_Sec_Site_Store::latest_scans( $ids );
		$counts = Artws_Sec_Site_Store::scan_counts( $ids );

		$rows = array();
		foreach ( $sites as $site ) {
			$id     = (int) $site->id;
			$rows[] = self::build_row( $site, $latest[ $id ] ?? null, $counts[ $id ] ?? 0, $with_bands );
		}
		return $rows;
	}

	/**
	 * Builds one row of the list and the CSV.
	 *
	 * @param object      $site       Row of the sites table.
	 * @param object|null $latest     Latest scan of the site, or null.
	 * @param int         $scans      Number of scans of the site.
	 * @param bool        $with_bands Whether the row may carry the bands. Without, it has no band keys at all.
	 * @return array<string, int|string|null> Keyed like columns(); null where there is no value (the last scan and
	 *                                         the values of the latest scan, for a registration without a scan).
	 */
	public static function build_row( $site, $latest, $scans, $with_bands ) {
		$request = null === $latest ? null : json_decode( (string) $latest->request, true );
		$report  = null === $latest ? null : json_decode( (string) $latest->report, true );
		$request = is_array( $request ) ? $request : array();
		$report  = is_array( $report ) ? $report : array();
		$score   = is_array( $report['score'] ?? null ) ? ( $report['score']['total'] ?? null ) : null;

		$row = array(
			'id'           => (int) $site->id,
			'site_url'     => (string) $site->site_url,
			'created_at'   => (int) $site->created_at,
			// A registration starts with last_scan_at at its own time; without a scan left, there is no last scan to show.
			'last_scan_at' => null === $latest ? null : (int) $site->last_scan_at,
			'scans'        => (int) $scans,
			'jp4wc_ver'    => self::text( $request, 'jp4wc_ver' ),
			'wp_ver'       => self::text( $request, 'wp_ver' ),
			'wc_ver'       => self::text( $request, 'wc_ver' ),
			'php_ver'      => self::text( $request, 'php_ver' ),
			'locale'       => self::text( $request, 'locale' ),
			'score'        => is_int( $score ) ? $score : null,
		);
		if ( $with_bands ) {
			$listed              = in_array( $request['consent_version'] ?? null, self::BAND_LISTING_CONSENT_VERSIONS, true );
			$row['revenue_band'] = $listed ? self::text( $request, 'revenue_band' ) : null;
			$row['order_band']   = $listed ? self::text( $request, 'order_band' ) : null;
		}
		return $row;
	}

	/**
	 * Column headings of the list and the CSV.
	 *
	 * @param bool $with_bands Whether to add the band columns (the list only).
	 * @return array<string, string> Row key => heading.
	 */
	public static function columns( $with_bands ) {
		$columns = array(
			'id'           => __( 'ID', 'artws-security-hub' ),
			'site_url'     => __( 'Site URL', 'artws-security-hub' ),
			'created_at'   => __( 'Registered', 'artws-security-hub' ),
			'last_scan_at' => __( 'Last scan', 'artws-security-hub' ),
			'scans'        => __( 'Scans', 'artws-security-hub' ),
			'jp4wc_ver'    => __( 'JP4WC', 'artws-security-hub' ),
			'wp_ver'       => __( 'WordPress', 'artws-security-hub' ),
			'wc_ver'       => __( 'WooCommerce', 'artws-security-hub' ),
			'php_ver'      => __( 'PHP', 'artws-security-hub' ),
			'locale'       => __( 'Locale', 'artws-security-hub' ),
			'score'        => __( 'Score', 'artws-security-hub' ),
		);
		if ( $with_bands ) {
			$columns['revenue_band'] = __( 'Revenue band', 'artws-security-hub' );
			$columns['order_band']   = __( 'Order band', 'artws-security-hub' );
		}
		return $columns;
	}

	/**
	 * Returns a value as a CSV cell, defused against formulas.
	 *
	 * A value that starts like a formula gets a leading apostrophe. The
	 * versions can: POST /scan takes any text without control characters
	 * there (site_url and locale cannot, but every column is defused).
	 *
	 * @param string          $key   Row key.
	 * @param int|string|null $value Row value.
	 * @return string
	 */
	public static function csv_value( $key, $value ) {
		if ( null === $value ) {
			return '';
		}
		if ( in_array( $key, array( 'created_at', 'last_scan_at' ), true ) ) {
			return (string) wp_date( 'c', (int) $value );
		}
		$value = (string) $value;
		return ( '' !== $value && false !== strpos( self::FORMULA_STARTS, $value[0] ) ) ? "'" . $value : $value;
	}

	/**
	 * Writes one CSV line.
	 *
	 * @param resource $handle Stream.
	 * @param string[] $cells  Cells.
	 */
	private static function put_csv( $handle, array $cells ) {
		// The escape character is given explicitly: PHP 8.4 deprecates leaving it out, and "" keeps the output RFC 4180.
		fputcsv( $handle, $cells, ',', '"', '' );
	}

	/**
	 * Renders the line that says what the aggregates count.
	 *
	 * @param array $stats From Artws_Sec_Site_Stats::collect().
	 */
	private static function render_basis( array $stats ) {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: 1: number of sites, 2: date and time. */
				_n(
					'%1$s site with a scan, counted once per site (site URL) from its latest scan. Counted at %2$s.',
					'%1$s sites with a scan, counted once per site (site URL) from its latest scan. Counted at %2$s.',
					$stats['sites'],
					'artws-security-hub'
				),
				number_format_i18n( $stats['sites'] ),
				self::date( $stats['computed_at'] )
			)
		) . '</p>';
	}

	/**
	 * Renders the PHP, WordPress and WooCommerce version tables side by side.
	 *
	 * @param array $stats From Artws_Sec_Site_Stats::collect().
	 */
	private static function render_version_tables( array $stats ) {
		$labels = array(
			'php'         => __( 'PHP', 'artws-security-hub' ),
			'wordpress'   => __( 'WordPress', 'artws-security-hub' ),
			'woocommerce' => __( 'WooCommerce', 'artws-security-hub' ),
		);
		echo '<div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start;">';
		foreach ( $labels as $name => $label ) {
			$cells = array();
			foreach ( $stats['versions'][ $name ] as $bucket => $count ) {
				$cells[] = array(
					Artws_Sec_Site_Stats::UNKNOWN === $bucket ? __( 'Unknown', 'artws-security-hub' ) : (string) $bucket,
					number_format_i18n( $count ),
					self::share( $count, $stats['sites'] ),
				);
			}
			self::render_small_table( $label, __( 'Version', 'artws-security-hub' ), $cells );
		}
		echo '</div>';
	}

	/**
	 * Renders the revenue and order band tables, with small cells withheld.
	 *
	 * @param array $stats From Artws_Sec_Site_Stats::collect().
	 */
	private static function render_band_tables( array $stats ) {
		echo '<h3>' . esc_html__( 'Revenue and order bands', 'artws-security-hub' ) . '</h3>';
		echo '<p>' . esc_html__( 'Counted whatever the consent text. A band with fewer than 5 sites shows "Under 5"; "—" withholds a band that could otherwise be worked out from the others.', 'artws-security-hub' ) . '</p>';
		echo '<div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start;">';
		$titles = array(
			'revenue' => __( 'Revenue (12 months)', 'artws-security-hub' ),
			'order'   => __( 'Orders (12 months)', 'artws-security-hub' ),
		);
		foreach ( $titles as $table => $title ) {
			$cells = array();
			foreach ( $stats['bands'][ $table ] as $band => $cell ) {
				$label = Artws_Sec_Site_Stats::NO_BAND === $band ? __( 'Not sent', 'artws-security-hub' ) : (string) $band;
				if ( Artws_Sec_Site_Stats::SHOWN === $cell['state'] ) {
					$cells[] = array( $label, number_format_i18n( $cell['count'] ), self::share( $cell['count'], $stats['sites'] ) );
				} elseif ( Artws_Sec_Site_Stats::UNDER_MIN === $cell['state'] ) {
					$cells[] = array( $label, __( 'Under 5', 'artws-security-hub' ), '' );
				} else {
					$cells[] = array( $label, '—', '' );
				}
			}
			self::render_small_table( $title, __( 'Band', 'artws-security-hub' ), $cells );
		}
		echo '</div>';
	}

	/**
	 * Renders a three-column table of the aggregates.
	 *
	 * @param string     $caption Caption.
	 * @param string     $first   Heading of the first column.
	 * @param string[][] $cells   Rows of three cells (plain text).
	 */
	private static function render_small_table( $caption, $first, array $cells ) {
		?>
		<table class="widefat striped" style="width: auto; min-width: 16em;">
			<caption style="text-align: left; font-weight: 600; padding: 4px 0;"><?php echo esc_html( $caption ); ?></caption>
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html( $first ); ?></th>
					<th scope="col"><?php esc_html_e( 'Sites', 'artws-security-hub' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Share', 'artws-security-hub' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $cells as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row[0] ); ?></th>
						<td><?php echo esc_html( $row[1] ); ?></td>
						<td><?php echo esc_html( $row[2] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders the site URL search.
	 *
	 * @param string $search Current search.
	 */
	private static function render_search_form( $search ) {
		?>
		<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( Artws_Sec_Admin::PAGE ); ?>">
			<input type="hidden" name="tab" value="sites">
			<p class="search-box" style="float: none; margin: 8px 0;">
				<label for="artws-sec-site-search"><?php esc_html_e( 'Site URL contains', 'artws-security-hub' ); ?></label>
				<input type="search" id="artws-sec-site-search" name="s" value="<?php echo esc_attr( $search ); ?>">
				<?php submit_button( __( 'Search', 'artws-security-hub' ), 'secondary', '', false ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Renders the CSV download link and what the CSV may be used for.
	 *
	 * @param string $search Current search, passed on to the download.
	 */
	private static function render_download( $search ) {
		$args = array( 'action' => self::DOWNLOAD_ACTION );
		if ( '' !== $search ) {
			$args['s'] = rawurlencode( $search );
		}
		$url = wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), self::DOWNLOAD_ACTION );
		?>
		<p>
			<a class="button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Download CSV', 'artws-security-hub' ); ?></a>
			<span class="description"><?php esc_html_e( 'Use this CSV only for aggregation and delete it right after use (terms of use, article 6, paragraph 6). It has no revenue or order bands, and only the rows of the current search.', 'artws-security-hub' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Renders the list of registrations and its pages.
	 *
	 * @param int     $total  Matching registrations.
	 * @param array[] $rows   Rows of this page, from build_row().
	 * @param string  $search Current search.
	 * @param int     $page   Current page.
	 */
	private static function render_list( $total, array $rows, $search, $page ) {
		$columns = self::columns( true );
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %s: number of registrations. */
				_n( '%s registration. Versions, locale, score and bands are from its latest scan.', '%s registrations. Versions, locale, score and bands are from the latest scan of each.', $total, 'artws-security-hub' ),
				number_format_i18n( $total )
			)
		) . '</p>';
		?>
		<div style="overflow-x: auto;">
			<table class="widefat striped">
				<thead>
					<tr>
						<?php foreach ( $columns as $heading ) : ?>
							<th scope="col"><?php echo esc_html( $heading ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php if ( array() === $rows ) : ?>
						<tr><td colspan="<?php echo esc_attr( (string) count( $columns ) ); ?>"><?php esc_html_e( 'No registrations found.', 'artws-security-hub' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<?php foreach ( array_keys( $columns ) as $key ) : ?>
								<td><?php echo esc_html( self::list_value( $key, $row[ $key ] ) ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( 1 < $pages ) {
			$base = add_query_arg( 'paged', '%#%', self::sites_url( $search ) );
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => $base,
						'format'  => '',
						'current' => $page,
						'total'   => $pages,
					)
				)
			);
			echo '</div></div>';
		}
	}

	/**
	 * Returns a value as the list shows it.
	 *
	 * @param string          $key   Row key.
	 * @param int|string|null $value Row value.
	 * @return string
	 */
	private static function list_value( $key, $value ) {
		if ( null === $value ) {
			return '—';
		}
		if ( in_array( $key, array( 'created_at', 'last_scan_at' ), true ) ) {
			return self::date( (int) $value );
		}
		if ( in_array( $key, array( 'scans', 'score' ), true ) ) {
			return number_format_i18n( (int) $value );
		}
		return (string) $value;
	}

	/**
	 * Returns the URL of the Sites tab with a search.
	 *
	 * @param string $search Search, or ''.
	 * @return string
	 */
	private static function sites_url( $search ) {
		$url = Artws_Sec_Admin::tab_url( 'sites' );
		return '' === $search ? $url : add_query_arg( 's', rawurlencode( $search ), $url );
	}

	/**
	 * Renders an error notice.
	 *
	 * @param string $message Message.
	 */
	private static function render_error( $message ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Returns a share of the sites as a percentage.
	 *
	 * @param int $count Sites.
	 * @param int $total All sites.
	 * @return string
	 */
	private static function share( $count, $total ) {
		return 0 < $total ? number_format_i18n( $count / $total * 100, 1 ) . '%' : '';
	}

	/**
	 * Returns a date and time in the site's time zone.
	 *
	 * @param int $timestamp Unix time.
	 * @return string
	 */
	private static function date( $timestamp ) {
		return (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $timestamp );
	}

	/**
	 * Returns a text value of a decoded request.
	 *
	 * @param array  $request Decoded request.
	 * @param string $key     Key.
	 * @return string|null
	 */
	private static function text( array $request, $key ) {
		return isset( $request[ $key ] ) && is_string( $request[ $key ] ) && '' !== $request[ $key ] ? $request[ $key ] : null;
	}

	/**
	 * Returns the site URL search of the request.
	 *
	 * @return string
	 */
	private static function search() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A read-only filter; the CSV download checks its nonce before reading it.
		return isset( $_GET['s'] ) && is_string( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	}

	/**
	 * Returns the page number of the request.
	 *
	 * @return int
	 */
	private static function page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A read-only page number.
		return isset( $_GET['paged'] ) && is_string( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	}
}
