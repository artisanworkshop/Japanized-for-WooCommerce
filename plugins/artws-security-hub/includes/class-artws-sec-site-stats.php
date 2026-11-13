<?php
/**
 * Aggregates of the registered sites for the settings page.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Site_Stats
 *
 * Counts each site once, by its site_key, from the request of its latest
 * scan over all its registrations (a site that registered again after a 401
 * has several). Only sites with a scan left in the table count: the daily
 * cleanup deletes the scans past the retention period. The counting is kept
 * apart from the reading, so that the tests can feed it requests directly.
 */
class Artws_Sec_Site_Stats {

	/**
	 * Version bucket of a value that does not start with "major.minor".
	 */
	const UNKNOWN = 'unknown';

	/**
	 * Band cell of the sites that sent no bands (it is the same in both band tables).
	 */
	const NO_BAND = 'none';

	/**
	 * The request key of each version table.
	 */
	const VERSION_KEYS = array(
		'php'         => 'php_ver',
		'wordpress'   => 'wp_ver',
		'woocommerce' => 'wc_ver',
	);

	/**
	 * Band cells below this number show neither their number nor their share.
	 */
	const MIN_BAND_CELL = 5;

	/**
	 * Band cell shown as it is.
	 */
	const SHOWN = 'shown';

	/**
	 * Band cell below MIN_BAND_CELL.
	 */
	const UNDER_MIN = 'under_min';

	/**
	 * Band cell hidden because it could be worked out from the others and the total.
	 */
	const HIDDEN = 'hidden';

	/**
	 * Plugins listed on the Plugins tab.
	 */
	const TOP_PLUGINS = 30;

	/**
	 * Scans read at a time (a request may list up to 1,000 software items).
	 */
	const BATCH_SIZE = 200;

	/**
	 * Transient that keeps slow aggregates for a while.
	 */
	const CACHE_KEY = 'artws_sec_site_stats';

	/**
	 * Seconds the slow aggregates are kept.
	 */
	const CACHE_TTL = 600;

	/**
	 * Seconds of counting after which the result is cached (the tests change it).
	 *
	 * @var float
	 */
	private static $cache_after = 1.0;

	/**
	 * Sites counted.
	 *
	 * @var int
	 */
	private $sites = 0;

	/**
	 * Sites per version bucket, by table.
	 *
	 * @var array<string, array<string, int>>
	 */
	private $versions = array();

	/**
	 * Sites per revenue band.
	 *
	 * @var array<string, int>
	 */
	private $revenue = array();

	/**
	 * Sites per order band.
	 *
	 * @var array<string, int>
	 */
	private $order = array();

	/**
	 * Sites that sent no bands.
	 *
	 * @var int
	 */
	private $no_band = 0;

	/**
	 * Slug => array( sites with it active, sites with it installed ).
	 *
	 * @var array<string, int[]>
	 */
	private $plugins = array();

	/**
	 * Starts with every band at zero.
	 */
	public function __construct() {
		$this->versions = array_fill_keys( array_keys( self::VERSION_KEYS ), array() );
		$this->revenue  = array_fill_keys( Artws_Sec_Scan_Request::REVENUE_BANDS, 0 );
		$this->order    = array_fill_keys( Artws_Sec_Scan_Request::ORDER_BANDS, 0 );
	}

	/**
	 * Counts one site from the request of its latest scan.
	 *
	 * @param array $request Stored request (normalized by Artws_Sec_Scan_Request, but read defensively).
	 */
	public function add( array $request ) {
		++$this->sites;

		foreach ( self::VERSION_KEYS as $name => $key ) {
			$bucket                             = self::version_bucket( $request[ $key ] ?? null );
			$this->versions[ $name ][ $bucket ] = ( $this->versions[ $name ][ $bucket ] ?? 0 ) + 1;
		}

		$revenue = $request['revenue_band'] ?? null;
		$order   = $request['order_band'] ?? null;
		if ( is_string( $revenue ) && isset( $this->revenue[ $revenue ] ) && is_string( $order ) && isset( $this->order[ $order ] ) ) {
			++$this->revenue[ $revenue ];
			++$this->order[ $order ];
		} else {
			++$this->no_band;
		}

		// A slug listed twice counts once, as active when any of its entries is.
		$plugins = array();
		foreach ( (array) ( $request['software'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) || 'plugin' !== ( $item['type'] ?? null ) || ! is_string( $item['slug'] ?? null ) || '' === $item['slug'] ) {
				continue;
			}
			$plugins[ $item['slug'] ] = ! empty( $plugins[ $item['slug'] ] ) || true === ( $item['active'] ?? null );
		}
		foreach ( $plugins as $slug => $active ) {
			$counts                 = $this->plugins[ $slug ] ?? array( 0, 0 );
			$this->plugins[ $slug ] = array( $counts[0] + ( $active ? 1 : 0 ), $counts[1] + 1 );
		}
	}

	/**
	 * Returns what the settings page shows.
	 *
	 * @param int $computed_at Unix time of the count.
	 * @return array{computed_at: int, sites: int, versions: array<string, array<string, int>>, bands: array, plugins: array}
	 */
	public function result( $computed_at ) {
		$versions = array();
		foreach ( $this->versions as $name => $counts ) {
			$versions[ $name ] = self::sort_versions( $counts );
		}
		return array(
			'computed_at' => (int) $computed_at,
			'sites'       => $this->sites,
			'versions'    => $versions,
			'bands'       => self::suppress_bands( $this->revenue, $this->order, $this->no_band ),
			'plugins'     => $this->top_plugins( self::TOP_PLUGINS ),
		);
	}

	/**
	 * Returns the plugins active on the most sites.
	 *
	 * Ties are ordered by slug and share a rank (1, 2, 2, 4).
	 *
	 * @param int $limit Most rows.
	 * @return array<int, array{rank: int, slug: string, active: int, installed: int}>
	 */
	public function top_plugins( $limit ) {
		$rows = array();
		foreach ( $this->plugins as $slug => $counts ) {
			$rows[] = array(
				'rank'      => 0,
				'slug'      => (string) $slug,
				'active'    => $counts[0],
				'installed' => $counts[1],
			);
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				$by_active = $b['active'] <=> $a['active'];
				return 0 !== $by_active ? $by_active : strcmp( $a['slug'], $b['slug'] );
			}
		);
		$rows = array_slice( $rows, 0, max( 0, (int) $limit ) );

		$count = count( $rows );
		for ( $i = 0; $i < $count; $i++ ) {
			$rows[ $i ]['rank'] = ( 0 < $i && $rows[ $i ]['active'] === $rows[ $i - 1 ]['active'] ) ? $rows[ $i - 1 ]['rank'] : $i + 1;
		}
		return $rows;
	}

	/**
	 * Returns the "major.minor" a version starts with.
	 *
	 * @param mixed $version Version as sent, such as 8.3.12 or 7.0-RC1.
	 * @return string Such as 8.3, or UNKNOWN.
	 */
	public static function version_bucket( $version ) {
		if ( ! is_string( $version ) || ! preg_match( '/^([0-9]+)\.([0-9]+)/', $version, $matches ) ) {
			return self::UNKNOWN;
		}
		return (int) $matches[1] . '.' . (int) $matches[2];
	}

	/**
	 * Orders version buckets from the newest, with UNKNOWN last.
	 *
	 * @param array<string, int> $counts Bucket => sites.
	 * @return array<string, int>
	 */
	public static function sort_versions( array $counts ) {
		$unknown = $counts[ self::UNKNOWN ] ?? null;
		unset( $counts[ self::UNKNOWN ] );
		uksort(
			$counts,
			static function ( $a, $b ) {
				return version_compare( (string) $b, (string) $a );
			}
		);
		if ( null !== $unknown ) {
			$counts[ self::UNKNOWN ] = $unknown;
		}
		return $counts;
	}

	/**
	 * Decides which band cells may be shown.
	 *
	 * A cell below MIN_BAND_CELL (zero included) is UNDER_MIN. The page shows
	 * the number of sites, which is the total of each table, so the cells not
	 * shown add up to a known number. Each of them has a known range as well:
	 * 0 to MIN_BAND_CELL - 1 for UNDER_MIN, and for HIDDEN from MIN_BAND_CELL
	 * up to the cells still shown in its table: at most those after it and
	 * less than those before it (this method hides the smallest cell, the
	 * first of equal ones, and the code is public). While a cell's value
	 * follows from the total and the ranges of the others, the smallest cell
	 * still shown in its table is HIDDEN as well. The NO_BAND cell is in both
	 * tables with the same number, so it is hidden in both or in neither, and
	 * what one table tells about it narrows the other.
	 *
	 * This keeps a withheld cell from being worked out with the table's
	 * numbers and these ranges. It does not stop someone who runs this method
	 * on every possible set of counts and keeps those that give the same page:
	 * that a cell was hidden at all says that something could be worked out
	 * before, which can narrow the values further. Only users who can read
	 * the database see the page (decided on issue #296, review R1-2).
	 *
	 * @param array<string, int> $revenue Revenue band => sites.
	 * @param array<string, int> $order   Order band => sites.
	 * @param int                $none    Sites that sent no bands.
	 * @return array{revenue: array, order: array} Table => cell => array{count: int, state: string}, NO_BAND last.
	 */
	public static function suppress_bands( array $revenue, array $order, $none ) {
		$tables = array(
			'revenue' => $revenue + array( self::NO_BAND => (int) $none ),
			'order'   => $order + array( self::NO_BAND => (int) $none ),
		);

		$states = array();
		foreach ( $tables as $table => $cells ) {
			foreach ( $cells as $cell => $count ) {
				$states[ $table ][ $cell ] = $count < self::MIN_BAND_CELL ? self::UNDER_MIN : self::SHOWN;
			}
		}

		// The table whose smallest shown cell the NO_BAND cell was, when it was hidden as one.
		$none_hidden_in = null;
		do {
			$table = self::table_to_widen( $tables, $states, $none_hidden_in );
			$cell  = null === $table ? null : self::smallest_shown( $tables[ $table ], $states[ $table ] );
			if ( null !== $cell ) {
				$states[ $table ][ $cell ] = self::HIDDEN;
				if ( self::NO_BAND === $cell ) {
					foreach ( array_keys( $tables ) as $other ) {
						$states[ $other ][ self::NO_BAND ] = self::HIDDEN;
					}
					$none_hidden_in = $table;
				}
			}
		} while ( null !== $cell );

		$result = array();
		foreach ( $tables as $table => $cells ) {
			foreach ( $cells as $cell => $count ) {
				$result[ $table ][ $cell ] = array(
					'count' => (int) $count,
					'state' => $states[ $table ][ $cell ],
				);
			}
		}
		return $result;
	}

	/**
	 * Returns a table in which the value of a cell not shown can still be worked out, if one more cell there can be hidden.
	 *
	 * Each table is checked on its own first. Then, as the NO_BAND cell is the
	 * same in both, its range is narrowed by both tables and the cells are
	 * checked again; the table that narrows it most is the one to widen.
	 *
	 * @param array<string, array<string, int>>    $tables         Table => cell => sites.
	 * @param array<string, array<string, string>> $states         Table => cell => state.
	 * @param string|null                          $none_hidden_in See suppress_bands().
	 * @return string|null
	 */
	private static function table_to_widen( array $tables, array $states, $none_hidden_in ) {
		$ranges = array();
		$totals = array();
		foreach ( $tables as $table => $cells ) {
			$totals[ $table ] = 0;
			foreach ( $cells as $cell => $count ) {
				if ( self::SHOWN === $states[ $table ][ $cell ] ) {
					continue;
				}
				$totals[ $table ] += $count;
				if ( self::UNDER_MIN === $states[ $table ][ $cell ] ) {
					$ranges[ $table ][ $cell ] = array( 0, self::MIN_BAND_CELL - 1 );
				} else {
					// Bounded by the cells still shown in the table it was hidden in.
					$from                      = ( self::NO_BAND === $cell && null !== $none_hidden_in ) ? $none_hidden_in : $table;
					$ranges[ $table ][ $cell ] = array( self::MIN_BAND_CELL, self::hidden_cap( $tables[ $from ], $states[ $from ], $cell ) );
				}
			}
		}

		$can_widen = static function ( $table ) use ( $tables, $states ) {
			return null !== self::smallest_shown( $tables[ $table ], $states[ $table ] );
		};

		foreach ( $ranges as $table => $cells ) {
			if ( self::has_known_cell( $cells, $totals[ $table ] ) && $can_widen( $table ) ) {
				return $table;
			}
		}

		if ( ! isset( $ranges['revenue'][ self::NO_BAND ], $ranges['order'][ self::NO_BAND ] ) ) {
			return null;
		}
		$shared = $ranges['revenue'][ self::NO_BAND ];
		$widths = array();
		foreach ( $ranges as $table => $cells ) {
			$range            = self::cell_range( $cells, $totals[ $table ], self::NO_BAND );
			$shared           = array( max( $shared[0], $range[0] ), min( $shared[1], $range[1] ) );
			$widths[ $table ] = $range[1] - $range[0];
		}
		$known = false;
		foreach ( $ranges as $table => $cells ) {
			$cells[ self::NO_BAND ] = $shared;
			$known                  = $known || self::has_known_cell( $cells, $totals[ $table ] );
		}
		if ( ! $known ) {
			return null;
		}
		asort( $widths );
		foreach ( array_keys( $widths ) as $table ) {
			if ( $can_widen( $table ) ) {
				return $table;
			}
		}
		return null;
	}

	/**
	 * Whether the value of one of the cells follows from their total and ranges.
	 *
	 * @param array<string, int[]> $ranges Cell => array( lowest, highest ).
	 * @param int                  $total  Total of the cells.
	 * @return bool
	 */
	private static function has_known_cell( array $ranges, $total ) {
		foreach ( array_keys( $ranges ) as $cell ) {
			$range = self::cell_range( $ranges, $total, $cell );
			if ( $range[0] >= $range[1] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the values a cell can take, given the total of the cells and the ranges of the others.
	 *
	 * @param array<string, int[]> $ranges Cell => array( lowest, highest ).
	 * @param int                  $total  Total of the cells.
	 * @param string               $cell   Cell.
	 * @return int[] array( lowest, highest ).
	 */
	private static function cell_range( array $ranges, $total, $cell ) {
		$others_low  = 0;
		$others_high = 0;
		foreach ( $ranges as $other => $range ) {
			if ( $other !== $cell ) {
				$others_low  += $range[0];
				$others_high += $range[1];
			}
		}
		return array( max( $ranges[ $cell ][0], $total - $others_high ), min( $ranges[ $cell ][1], $total - $others_low ) );
	}

	/**
	 * Returns the smallest cell still shown (the first of equal ones).
	 *
	 * @param array<string, int>    $cells  Cell => sites.
	 * @param array<string, string> $states Cell => state.
	 * @return string|null Null when every cell is withheld.
	 */
	private static function smallest_shown( array $cells, array $states ) {
		$smallest = null;
		foreach ( $cells as $cell => $count ) {
			if ( self::SHOWN === $states[ $cell ] && ( null === $smallest || $count < $cells[ $smallest ] ) ) {
				$smallest = $cell;
			}
		}
		return $smallest;
	}

	/**
	 * Returns the largest value a hidden cell can have, from the cells still shown in the table it was hidden in.
	 *
	 * It was the smallest cell shown when it was hidden, and the first of
	 * equal ones: so it is less than a cell shown before it, and at most one
	 * shown after it. NO_BAND is the last cell, so it is less than all of them.
	 *
	 * @param array<string, int>    $cells  Cell => sites.
	 * @param array<string, string> $states Cell => state.
	 * @param string                $hidden The hidden cell.
	 * @return int The total of the table when no cell is shown.
	 */
	private static function hidden_cap( array $cells, array $states, $hidden ) {
		$cap    = (int) array_sum( $cells );
		$before = true;
		foreach ( $cells as $cell => $count ) {
			if ( $cell === $hidden ) {
				$before = false;
			} elseif ( self::SHOWN === $states[ $cell ] ) {
				$cap = min( $cap, $before ? $count - 1 : $count );
			}
		}
		return $cap;
	}

	/**
	 * Counts the sites, or returns the aggregates kept from a slow count.
	 *
	 * @return array See result().
	 * @throws Artws_Sec_Feed_Exception When the scans cannot be read.
	 */
	public static function collect() {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && isset( $cached['computed_at'], $cached['sites'], $cached['versions'], $cached['bands'], $cached['plugins'] ) ) {
			return $cached;
		}

		$started = microtime( true );
		$stats   = new self();
		$max_id  = Artws_Sec_Site_Store::max_scan_id();
		$after   = 0;
		do {
			$rows = Artws_Sec_Site_Store::latest_requests_by_site_key( $after, $max_id, self::BATCH_SIZE );
			foreach ( $rows as $row ) {
				$after   = (int) $row->id;
				$request = json_decode( (string) $row->request, true );
				$stats->add( is_array( $request ) ? $request : array() );
			}
			$read = count( $rows );
		} while ( self::BATCH_SIZE === $read );

		$result = $stats->result( time() );
		if ( microtime( true ) - $started > self::$cache_after ) {
			set_transient( self::CACHE_KEY, $result, self::CACHE_TTL );
		}
		return $result;
	}
}
