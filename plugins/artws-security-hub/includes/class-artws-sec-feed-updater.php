<?php
/**
 * Daily import of the Wordfence Intelligence V3 production feed.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text messages, escaped where the settings page shows them.

/**
 * Artws_Sec_Feed_Updater
 *
 * Downloads the V3 production feed, builds the three vulnerability files and
 * publishes them with a new manifest. When anything fails, the files of the
 * last successful run stay in place and the manifest is rewritten with
 * "stale": true, so that clients do not mistake missing data for "no
 * vulnerabilities". A run is also treated as failed when the new data looks
 * wrong as a whole: too many records failing the contract, a type with no
 * records, or far fewer records than last time (a format change at
 * Wordfence, for example).
 */
class Artws_Sec_Feed_Updater {

	/**
	 * Daily cron event that runs update().
	 */
	const UPDATE_HOOK = 'artws_sec_feed_update';

	/**
	 * Hourly cron event that runs watchdog().
	 */
	const WATCHDOG_HOOK = 'artws_sec_feed_watchdog';

	/**
	 * Wordfence Intelligence V3 production feed.
	 */
	const FEED_URL = 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production';

	/**
	 * What the last successful run published (not autoloaded).
	 */
	const STATE_OPTION = 'artws_sec_feed_state';

	/**
	 * Result of the last run, for the settings page (not autoloaded).
	 */
	const STATUS_OPTION = 'artws_sec_feed_status';

	/**
	 * Oldest supported PHP / WordPress / WooCommerce versions (manifest "eol").
	 */
	const EOL_OPTION = 'artws_sec_feed_eol';

	/**
	 * Set from the settings page to let the next run publish fewer records than the last one.
	 */
	const ACCEPT_DROP_OPTION = 'artws_sec_feed_accept_drop';

	/**
	 * Row in the options table that only one run at a time can hold.
	 */
	const LOCK_OPTION = 'artws_sec_feed_lock';

	/**
	 * Seconds after which a lock is treated as left behind by a run that died (2 hours).
	 */
	const LOCK_TIMEOUT = 7200;

	/**
	 * Credit line written into the feed.
	 */
	const ATTRIBUTION = 'Vulnerability data: Wordfence Intelligence';

	/**
	 * Largest share of records that may fail the contract before the run fails.
	 */
	const DEFAULT_MAX_EXCLUDED_RATIO = 0.02;

	/**
	 * Smallest share of the last run's records, per type, that a run must keep.
	 */
	const DEFAULT_MIN_COUNT_RATIO = 0.9;

	/**
	 * Seconds without a successful run after which the feed is marked stale (48 hours).
	 */
	const DEFAULT_STALE_AFTER = 172800;

	/**
	 * Default oldest supported versions.
	 */
	const DEFAULT_EOL = array(
		'php'         => '8.2',
		'wordpress'   => '6.7',
		'woocommerce' => '10.2',
	);

	/**
	 * Registers the cron callbacks.
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'schedule_events' ) );
		add_action( self::UPDATE_HOOK, array( __CLASS__, 'update' ), 10, 0 );
		add_action( self::WATCHDOG_HOOK, array( __CLASS__, 'watchdog' ), 10, 0 );
		add_action( 'add_option_' . self::EOL_OPTION, array( __CLASS__, 'republish_manifest' ), 10, 0 );
		add_action( 'update_option_' . self::EOL_OPTION, array( __CLASS__, 'republish_manifest' ), 10, 0 );
	}

	/**
	 * Schedules the daily update and the hourly watchdog when they are missing.
	 *
	 * Also runs on init, because replacing the plugin with an uploaded ZIP
	 * does not run the activation hook.
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( self::UPDATE_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::UPDATE_HOOK );
		}
		if ( ! wp_next_scheduled( self::WATCHDOG_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::WATCHDOG_HOOK );
		}
	}

	/**
	 * Removes the cron events (deactivation).
	 */
	public static function unschedule_events() {
		wp_clear_scheduled_hook( self::UPDATE_HOOK );
		wp_clear_scheduled_hook( self::WATCHDOG_HOOK );
		wp_clear_scheduled_hook( self::UPDATE_HOOK, array( 'manual' ) );
	}

	/**
	 * Imports the feed and publishes the files.
	 *
	 * @param int|null $now Unix time of the run (for tests).
	 * @return string "success", "failure" or "locked" (another run is in progress).
	 */
	public static function update( $now = null ) {
		$now = null === $now ? time() : (int) $now;
		if ( ! self::acquire_lock( $now ) ) {
			return 'locked';
		}

		$download = '';
		$stats    = array();
		try {
			wp_raise_memory_limit( 'artws_sec_feed' );
			if ( function_exists( 'set_time_limit' ) ) {
				set_time_limit( 900 );
			}
			Artws_Sec_Feed_Publisher::delete_stale_temp_files( $now - HOUR_IN_SECONDS );

			$download = self::download();

			$builder = new Artws_Sec_Feed_Builder();
			Artws_Sec_Json_Object_Reader::read( self::readable_path( $download ), array( $builder, 'add' ) );
			$result = $builder->result();
			$stats  = $result['stats'] + array( 'counts' => $result['counts'] );

			self::check_result( $result );

			$generated_at = gmdate( 'Y-m-d\TH:i:s\Z', $now );
			$files        = Artws_Sec_Feed_Publisher::write_vulnerability_files( $result['vulnerabilities'], $result['copyrights'], self::ATTRIBUTION, $generated_at );

			$state = array(
				'version'      => gmdate( 'Ymd.His', $now ),
				'generated_at' => $generated_at,
				'stale'        => false,
				'files'        => $files,
				'copyrights'   => $result['copyrights'],
				'attribution'  => self::ATTRIBUTION,
				'last_success' => $now,
			);
			update_option( self::STATE_OPTION, $state, false );
			delete_option( self::ACCEPT_DROP_OPTION );
			Artws_Sec_Feed_Publisher::write_manifest( Artws_Sec_Feed_Publisher::render_manifest( $state, self::get_eol() ) );

			self::record_status( $now, 'success', '', $stats );
			return 'success';
		} catch ( Throwable $e ) {
			// Fatal errors such as running out of memory never get here; watchdog() covers them.
			$message = $e instanceof Artws_Sec_Feed_Exception ? $e->getMessage() : get_class( $e ) . ': ' . $e->getMessage();
			$message = self::mark_stale( $message );
			self::record_status( $now, 'failure', $message, $stats );
			return 'failure';
		} finally {
			if ( '' !== $download && file_exists( $download ) ) {
				wp_delete_file( $download );
			}
			self::release_lock();
		}
	}

	/**
	 * Marks the feed stale when no run has succeeded for a while.
	 *
	 * A run that dies on a fatal error (memory or time limit) cannot mark the
	 * feed stale itself, so this runs hourly on its own.
	 *
	 * @param int|null $now Unix time (for tests).
	 * @return bool Whether the feed was marked stale.
	 */
	public static function watchdog( $now = null ) {
		$now = null === $now ? time() : (int) $now;

		$state = self::get_state();
		if ( null === $state || $state['stale'] || $now - (int) $state['last_success'] <= self::stale_after() ) {
			return false;
		}
		if ( ! self::acquire_lock( $now ) ) {
			return false;
		}

		try {
			$message = self::mark_stale(
				sprintf( 'No successful update since %s.', gmdate( 'Y-m-d H:i:s \U\T\C', (int) $state['last_success'] ) )
			);
			self::record_status( $now, 'stale', $message, array() );
			return true;
		} finally {
			self::release_lock();
		}
	}

	/**
	 * Rewrites the manifest from the last published state (after the EOL settings change).
	 *
	 * Skipped while a run holds the lock: that run writes the manifest with
	 * the new settings when it ends.
	 */
	public static function republish_manifest() {
		$state = self::get_state();
		if ( null === $state || ! self::acquire_lock( time() ) ) {
			return;
		}

		try {
			Artws_Sec_Feed_Publisher::write_manifest( Artws_Sec_Feed_Publisher::render_manifest( $state, self::get_eol() ) );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			$status            = self::get_status();
			$status['message'] = 'The manifest could not be rewritten: ' . $e->getMessage();
			update_option( self::STATUS_OPTION, $status, false );
		} finally {
			self::release_lock();
		}
	}

	/**
	 * Returns what the last successful run published, or null before the first one.
	 *
	 * @return array|null
	 */
	public static function get_state() {
		$state = get_option( self::STATE_OPTION );
		if ( ! is_array( $state ) || ! isset( $state['version'], $state['generated_at'], $state['files'], $state['copyrights'], $state['attribution'], $state['last_success'] ) ) {
			return null;
		}
		$state['stale'] = ! empty( $state['stale'] );
		return $state;
	}

	/**
	 * Returns the result of the last run.
	 *
	 * @return array{last_attempt?: int, result?: string, message?: string, stats?: array}
	 */
	public static function get_status() {
		$status = get_option( self::STATUS_OPTION );
		return is_array( $status ) ? $status : array();
	}

	/**
	 * Returns the oldest supported versions, with defaults for missing or invalid values.
	 *
	 * @return array{php: string, WordPress: string, woocommerce: string}
	 */
	public static function get_eol() {
		$saved = get_option( self::EOL_OPTION );
		$eol   = self::DEFAULT_EOL;
		foreach ( array_keys( $eol ) as $name ) {
			if ( is_array( $saved ) && isset( $saved[ $name ] ) && self::is_eol_version( $saved[ $name ] ) ) {
				$eol[ $name ] = $saved[ $name ];
			}
		}
		return $eol;
	}

	/**
	 * Whether a value fits the manifest's eol.*.min_supported ("8.2", "6.7.1").
	 *
	 * @param mixed $version Value to check.
	 * @return bool
	 */
	public static function is_eol_version( $version ) {
		return is_string( $version ) && strlen( $version ) <= 32 && 1 === preg_match( '/^[0-9]+(\.[0-9]+)*$/D', $version );
	}

	/**
	 * Downloads the V3 production feed to a temporary file.
	 *
	 * @return string Path of the downloaded file.
	 * @throws Artws_Sec_Feed_Exception When the download fails.
	 */
	private static function download() {
		/**
		 * Filters the Wordfence Intelligence API key.
		 *
		 * @param string $key Value of ARTWS_SEC_WORDFENCE_API_KEY in wp-config.php, or ''.
		 */
		$key = apply_filters( 'artws_sec_wordfence_api_key', defined( 'ARTWS_SEC_WORDFENCE_API_KEY' ) ? (string) ARTWS_SEC_WORDFENCE_API_KEY : '' );
		if ( ! is_string( $key ) || '' === trim( $key ) ) {
			throw new Artws_Sec_Feed_Exception( 'ARTWS_SEC_WORDFENCE_API_KEY is not set in wp-config.php.' );
		}

		$file = tempnam( get_temp_dir(), 'artws-sec-wordfence-' );
		if ( false === $file ) {
			throw new Artws_Sec_Feed_Exception( 'No temporary file could be created for the download.' );
		}

		$response = wp_remote_get(
			self::FEED_URL,
			array(
				'timeout'  => 300,
				'stream'   => true,
				'filename' => $file,
				'headers'  => array(
					'Authorization' => 'Bearer ' . trim( $key ),
					'Accept'        => 'application/json',
				),
			)
		);

		try {
			if ( is_wp_error( $response ) ) {
				throw new Artws_Sec_Feed_Exception( 'The Wordfence request failed: ' . $response->get_error_message() );
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				throw new Artws_Sec_Feed_Exception( sprintf( 'Wordfence answered HTTP %d.', $code ) );
			}
			clearstatcache( true, $file );
			if ( ! is_file( $file ) || 0 === filesize( $file ) ) {
				throw new Artws_Sec_Feed_Exception( 'Wordfence answered with an empty body.' );
			}
		} catch ( Artws_Sec_Feed_Exception $e ) {
			wp_delete_file( $file );
			throw $e;
		}

		return $file;
	}

	/**
	 * Returns a path that reads the download decompressed.
	 *
	 * The cURL transport decompresses a gzip response while streaming it to
	 * the file, but the PHP streams transport writes the bytes as received.
	 *
	 * @param string $file Downloaded file.
	 * @return string
	 */
	private static function readable_path( $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads two bytes of a local file.
		return "\x1f\x8b" === file_get_contents( $file, false, null, 0, 2 ) ? 'compress.zlib://' . $file : $file;
	}

	/**
	 * Fails the run when the new data looks wrong as a whole.
	 *
	 * @param array $result Artws_Sec_Feed_Builder::result().
	 * @throws Artws_Sec_Feed_Exception When the data must not be published.
	 */
	private static function check_result( array $result ) {
		$stats = $result['stats'];

		/**
		 * Filters the largest share of records that may fail the contract before the run fails.
		 *
		 * @param float $ratio Default 0.02 (2%).
		 */
		$max_excluded = (float) apply_filters( 'artws_sec_feed_max_excluded_ratio', self::DEFAULT_MAX_EXCLUDED_RATIO );
		if ( $stats['candidates'] > 0 && $stats['excluded'] / $stats['candidates'] > $max_excluded ) {
			throw new Artws_Sec_Feed_Exception(
				sprintf( '%d of %d records failed the contract check (more than %s%%).', $stats['excluded'], $stats['candidates'], (string) ( $max_excluded * 100 ) )
			);
		}

		foreach ( $result['counts'] as $type => $count ) {
			if ( 0 === $count ) {
				throw new Artws_Sec_Feed_Exception( sprintf( 'The %s file would have no records.', $type ) );
			}
		}

		if ( array() === $result['copyrights'] ) {
			throw new Artws_Sec_Feed_Exception( 'No copyright holders were found in the records.' );
		}

		$previous = self::get_state();
		if ( null === $previous || get_option( self::ACCEPT_DROP_OPTION ) ) {
			return;
		}

		/**
		 * Filters the smallest share of the last successful run's records, per type, that a run must keep.
		 *
		 * @param float $ratio Default 0.9 (90%).
		 */
		$min_kept = (float) apply_filters( 'artws_sec_feed_min_count_ratio', self::DEFAULT_MIN_COUNT_RATIO );
		foreach ( $result['counts'] as $type => $count ) {
			$before = (int) ( $previous['files'][ $type ]['count'] ?? 0 );
			if ( $before > 0 && $count < $before * $min_kept ) {
				throw new Artws_Sec_Feed_Exception(
					sprintf( 'The %1$s records dropped from %2$d to %3$d. If Wordfence removed them on purpose, allow the drop on the settings page.', $type, $before, $count )
				);
			}
		}
	}

	/**
	 * Marks the published feed stale and rewrites its manifest.
	 *
	 * Before the first successful run there is nothing to mark: no manifest is served.
	 *
	 * @param string $message Why the feed is stale.
	 * @return string The message, with the manifest error appended when the manifest could not be written.
	 */
	private static function mark_stale( $message ) {
		$state = self::get_state();
		if ( null === $state ) {
			return $message;
		}

		$state['stale'] = true;
		update_option( self::STATE_OPTION, $state, false );

		try {
			Artws_Sec_Feed_Publisher::write_manifest( Artws_Sec_Feed_Publisher::render_manifest( $state, self::get_eol() ) );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			$message .= ' The stale manifest could not be written either: ' . $e->getMessage();
		}
		return $message;
	}

	/**
	 * Saves the result of a run for the settings page.
	 *
	 * @param int    $now     Time of the run.
	 * @param string $result  "success", "failure" or "stale".
	 * @param string $message Error message.
	 * @param array  $stats   Counters from Artws_Sec_Feed_Builder (empty when the download failed).
	 */
	private static function record_status( $now, $result, $message, array $stats ) {
		update_option(
			self::STATUS_OPTION,
			array(
				'last_attempt' => $now,
				'result'       => $result,
				'message'      => $message,
				'stats'        => $stats,
			),
			false
		);
	}

	/**
	 * Seconds without a successful run after which the feed is marked stale.
	 *
	 * @return int
	 */
	private static function stale_after() {
		/**
		 * Filters the seconds without a successful run after which the feed is marked stale.
		 *
		 * @param int $seconds Default 48 hours.
		 */
		return (int) apply_filters( 'artws_sec_feed_stale_after', self::DEFAULT_STALE_AFTER );
	}

	/**
	 * Takes the lock that allows one run at a time.
	 *
	 * INSERT IGNORE either creates the row or does nothing, so two runs
	 * cannot both get it (add_option() is not atomic). A lock older than
	 * LOCK_TIMEOUT was left by a run that died and is taken over, again only
	 * by one run.
	 *
	 * @param int $now Unix time.
	 * @return bool
	 */
	private static function acquire_lock( $now ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic lock row; must not go through the options cache.
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'off' )",
				self::LOCK_OPTION,
				(string) $now
			)
		);
		if ( 1 === $inserted ) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the lock row written above.
		$locked_at = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::LOCK_OPTION ) );
		if ( null === $locked_at || (int) $locked_at > $now - self::LOCK_TIMEOUT ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Takes over an expired lock only if no other run did.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				(string) $now,
				self::LOCK_OPTION,
				$locked_at
			)
		);
		return 1 === $updated;
	}

	/**
	 * Releases the lock.
	 */
	private static function release_lock() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deletes the lock row written by acquire_lock().
		$wpdb->delete( $wpdb->options, array( 'option_name' => self::LOCK_OPTION ) );
	}
}
