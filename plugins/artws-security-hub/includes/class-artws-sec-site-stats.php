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
	 * A cell below MIN_BAND_CELL (zero included) is UNDER_MIN. The total of a
	 * table is the number of sites, shown above it, so a table with a single
	 * cell that is not shown would give it away: the smallest cell still
	 * shown (the first of equal ones) is then HIDDEN as well, until no table
	 * has exactly one. The NO_BAND cell is in both tables with the same
	 * number, so it is hidden in both or in neither.
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

		do {
			$changed = false;
			foreach ( $tables as $table => $cells ) {
				$shown = array_keys( $states[ $table ], self::SHOWN, true );
				if ( count( $cells ) - count( $shown ) !== 1 ) {
					continue;
				}
				$smallest = null;
				foreach ( $cells as $cell => $count ) {
					if ( self::SHOWN === $states[ $table ][ $cell ] && ( null === $smallest || $count < $cells[ $smallest ] ) ) {
						$smallest = $cell;
					}
				}
				if ( null === $smallest ) {
					continue;
				}
				$states[ $table ][ $smallest ] = self::HIDDEN;
				if ( self::NO_BAND === $smallest ) {
					foreach ( array_keys( $tables ) as $other ) {
						$states[ $other ][ self::NO_BAND ] = self::HIDDEN;
					}
				}
				$changed = true;
			}
		} while ( $changed );

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
