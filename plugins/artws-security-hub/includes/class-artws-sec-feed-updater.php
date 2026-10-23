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
 *
 * Everything that reads or writes the published state does so while holding
 * the lock, and reads the state from the database rather than from the
 * options cache, because another process may have published in between.
 */
class Artws_Sec_Feed_Updater {

	/**
	 * Daily cron event that runs update(). Also scheduled once with the "manual" argument from the settings page.
	 */
	const UPDATE_HOOK = 'artws_sec_feed_update';

	/**
	 * One-off cron event that retries a failed run; its argument is the attempt number.
	 */
	const RETRY_HOOK = 'artws_sec_feed_retry';

	/**
	 * Hourly cron event that runs watchdog().
	 */
	const WATCHDOG_HOOK = 'artws_sec_feed_watchdog';

	/**
	 * One-off cron event that rewrites the manifest when the EOL settings were saved during a run.
	 */
	const REPUBLISH_HOOK = 'artws_sec_feed_republish';

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
	 * Set on deactivation (not autoloaded): a run still in progress then publishes its files as stale.
	 */
	const DEACTIVATED_OPTION = 'artws_sec_feed_deactivated';

	/**
	 * Row in the options table that only one run at a time can hold.
	 */
	const LOCK_OPTION = 'artws_sec_feed_lock';

	/**
	 * Seconds after which a lock is treated as left behind by a run that died (2 hours).
	 */
	const LOCK_TIMEOUT = 7200;

	/**
	 * Retries after a failed download, read or write (each daily or manual run starts again at 0).
	 */
	const MAX_RETRIES = 3;

	/**
	 * Seconds between a failed run and its retry.
	 */
	const RETRY_DELAY = 3600;

	/**
	 * Prefix of the temporary download in the system temp directory.
	 */
	const DOWNLOAD_PREFIX = 'artws-sec-wordfence-';

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
	 *
	 * The update callback takes no arguments, so the "manual" argument of a
	 * run scheduled from the settings page is not read as the run's time.
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'schedule_events' ) );
		add_action( self::UPDATE_HOOK, array( __CLASS__, 'update' ), 10, 0 );
		add_action( self::RETRY_HOOK, array( __CLASS__, 'retry' ), 10, 1 );
		add_action( self::WATCHDOG_HOOK, array( __CLASS__, 'watchdog' ), 10, 0 );
		add_action( self::REPUBLISH_HOOK, array( __CLASS__, 'republish_manifest' ), 10, 0 );
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
	 * Activation: schedules the events and lets runs publish fresh files again.
	 */
	public static function activate() {
		delete_option( self::DEACTIVATED_OPTION );
		self::schedule_events();
	}

	/**
	 * Deactivation: removes the cron events and marks the feed stale.
	 *
	 * The files stay where the web server serves them, but nothing will
	 * update them any more, not even the watchdog. The feed is marked stale
	 * even when the lock is held: by a run still in progress, which sees the
	 * flag and publishes stale files too, or by a run that died, which will
	 * never write again.
	 */
	public static function deactivate() {
		foreach ( array( self::UPDATE_HOOK, self::RETRY_HOOK, self::WATCHDOG_HOOK, self::REPUBLISH_HOOK ) as $hook ) {
			wp_unschedule_hook( $hook );
		}

		$now = time();
		update_option( self::DEACTIVATED_OPTION, $now, false );

		$locked = self::acquire_lock( $now );
		try {
			self::record_status( $now, 'stale', self::mark_stale( 'The plugin was deactivated, so the feed is no longer updated.' ), array() );
		} finally {
			if ( $locked ) {
				self::release_lock();
			}
		}
	}

	/**
	 * Imports the feed and publishes the files (daily or from the settings page).
	 *
	 * @param int|null $now Unix time of the run (for tests).
	 * @return string "success", "failure" or "locked" (another run is in progress).
	 */
	public static function update( $now = null ) {
		return self::run( null === $now ? time() : (int) $now, 0 );
	}

	/**
	 * Retries a failed run (RETRY_HOOK).
	 *
	 * @param int      $attempt Retry number, from 1.
	 * @param int|null $now     Unix time of the run (for tests).
	 * @return string See update().
	 */
	public static function retry( $attempt, $now = null ) {
		return self::run( null === $now ? time() : (int) $now, max( 1, (int) $attempt ) );
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

		// A cheap check first, so that the hourly event rarely takes the lock.
		if ( ! self::is_overdue( self::get_state(), $now ) || ! self::acquire_lock( $now ) ) {
			return false;
		}

		try {
			$state = self::fresh_state();
			if ( ! self::is_overdue( $state, $now ) ) {
				return false;
			}
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
	 * While a run holds the lock, a one-off event tries again a minute later:
	 * the run may already have written the manifest with the previous settings.
	 */
	public static function republish_manifest() {
		if ( ! self::acquire_lock( time() ) ) {
			if ( ! wp_next_scheduled( self::REPUBLISH_HOOK ) ) {
				wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::REPUBLISH_HOOK );
			}
			return;
		}

		try {
			$state = self::fresh_state();
			if ( null !== $state ) {
				Artws_Sec_Feed_Publisher::write_manifest( Artws_Sec_Feed_Publisher::render_manifest( $state, self::get_eol() ) );
			}
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
		// Feed version of the vulnerability index the free security check uses, '' before there is one (0.3.0).
		$state['index_version'] = isset( $state['index_version'] ) && is_string( $state['index_version'] ) ? $state['index_version'] : '';
		return $state;
	}

	/**
	 * Returns the seconds after which data that has not been updated counts as stale.
	 *
	 * @return int
	 */
	public static function stale_after() {
		/**
		 * Filters the seconds without a successful run after which the feed is marked stale.
		 *
		 * The free security check also treats its vulnerability index as stale
		 * when the index has not caught up with the feed for this long.
		 *
		 * @param int $seconds Default 48 hours.
		 */
		return (int) apply_filters( 'artws_sec_feed_stale_after', self::DEFAULT_STALE_AFTER );
	}

	/**
	 * Returns the result of the last run.
	 *
	 * @return array{last_attempt?: int, result?: string, message?: string, stats?: array, retry_at?: int}
	 */
	public static function get_status() {
		$status = get_option( self::STATUS_OPTION );
		return is_array( $status ) ? $status : array();
	}

	/**
	 * Returns the oldest supported versions, with defaults for missing or invalid values.
	 *
	 * @return array<string, string> Keyed like Artws_Sec_Feed_Updater::DEFAULT_EOL.
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
	 * Imports the feed and publishes the files.
	 *
	 * @param int $now     Unix time of the run.
	 * @param int $attempt 0 for a daily or manual run, then the retry number.
	 * @return string "success", "failure" or "locked".
	 */
	private static function run( $now, $attempt ) {
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
			// What a run killed by a fatal error left behind.
			Artws_Sec_Feed_Publisher::delete_stale_temp_files( $now - HOUR_IN_SECONDS );
			self::delete_stale_downloads( $now - HOUR_IN_SECONDS );
			// The published index stays; generations a run failed to finish go.
			$previous = self::fresh_state();
			Artws_Sec_Vuln_Index::delete_other_versions( null !== $previous && '' !== $previous['index_version'] ? array( $previous['index_version'] ) : array() );

			$download = self::download();

			$builder = new Artws_Sec_Feed_Builder();
			Artws_Sec_Json_Object_Reader::read( self::readable_path( $download ), array( $builder, 'add' ) );
			$result = $builder->result();
			$stats  = $result['stats'] + array( 'counts' => $result['counts'] );

			self::check_result( $result, self::fresh_state() );

			$version      = gmdate( 'Ymd.His', $now );
			$generated_at = gmdate( 'Y-m-d\TH:i:s\Z', $now );
			$files        = Artws_Sec_Feed_Publisher::write_vulnerability_files( $result['vulnerabilities'], $result['copyrights'], self::ATTRIBUTION, $generated_at );

			$state = array(
				'version'       => $version,
				'generated_at'  => $generated_at,
				'stale'         => false,
				'files'         => $files,
				'copyrights'    => $result['copyrights'],
				'attribution'   => self::ATTRIBUTION,
				'last_success'  => $now,
				// Updated by update_index() once the new generation is written.
				'index_version' => null === $previous ? '' : $previous['index_version'],
			);
			update_option( self::STATE_OPTION, $state, false );
			delete_option( self::ACCEPT_DROP_OPTION );
			wp_unschedule_hook( self::RETRY_HOOK );
			Artws_Sec_Feed_Publisher::write_manifest( Artws_Sec_Feed_Publisher::render_manifest( $state, self::get_eol() ) );
			$index_error = self::update_index( $result['vulnerabilities'], $version );

			// Deactivated while this run was in progress: nothing will update these files. Checked
			// after the manifest is written, so a deactivation at any point of the run is caught
			// either here or by deactivate(), which sets the flag before writing its stale manifest.
			$notice = '';
			if ( false !== self::fresh_option( self::DEACTIVATED_OPTION ) ) {
				$notice = self::mark_stale( 'The plugin was deactivated during this update, so the feed is marked stale.' );
			}

			self::record_status( $now, 'success', trim( $notice . ' ' . $index_error ), $stats );
			return 'success';
		} catch ( Throwable $e ) {
			// Fatal errors such as running out of memory never get here; watchdog() covers them.
			$message = $e instanceof Artws_Sec_Feed_Exception ? $e->getMessage() : get_class( $e ) . ': ' . $e->getMessage();
			$message = self::mark_stale( $message );

			// Rejected data would be rejected again; a failed download, read or write may not fail again.
			$retry_at = 0;
			$rejected = $e instanceof Artws_Sec_Feed_Exception && Artws_Sec_Feed_Exception::REJECTED === $e->getCode();
			if ( ! $rejected && $attempt < self::MAX_RETRIES ) {
				$retry_at = $now + self::RETRY_DELAY;
				if ( ! wp_next_scheduled( self::RETRY_HOOK, array( $attempt + 1 ) ) ) {
					wp_schedule_single_event( $retry_at, self::RETRY_HOOK, array( $attempt + 1 ) );
				}
			}

			self::record_status( $now, 'failure', $message, $stats, $retry_at );
			return 'failure';
		} finally {
			if ( '' !== $download && file_exists( $download ) ) {
				wp_delete_file( $download );
			}
			self::release_lock();
		}
	}

	/**
	 * Writes the vulnerability index of a published version and makes it the one scans use.
	 *
	 * The feed is already published: a failure here leaves the scans on the
	 * previous generation instead of failing the run, so that the feed every
	 * JP4WC site reads never goes stale because of the free security check.
	 * The next run tries again. Call it while holding the lock.
	 *
	 * @param array  $vulnerabilities Type => slug => feed records as JSON text.
	 * @param string $version         The published version.
	 * @return string Why the index was not updated, or ''.
	 */
	private static function update_index( array $vulnerabilities, $version ) {
		try {
			Artws_Sec_Vuln_Index::write( $vulnerabilities, $version );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			Artws_Sec_Vuln_Index::delete_version( $version );
			return 'The feed was published, but the index of the free security check was not updated, so scans use the previous one: ' . $e->getMessage();
		}

		$state = self::fresh_state();
		if ( null === $state ) {
			return '';
		}
		$replaced               = $state['index_version'];
		$state['index_version'] = $version;
		update_option( self::STATE_OPTION, $state, false );
		Artws_Sec_Vuln_Index::delete_other_versions( '' === $replaced ? array( $version ) : array( $version, $replaced ) );
		return '';
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

		$file = tempnam( get_temp_dir(), self::DOWNLOAD_PREFIX );
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
	 * Deletes downloads that a run killed by a fatal error left in the system temp directory.
	 *
	 * @param int $older_than Unix time; files modified before it are deleted.
	 */
	private static function delete_stale_downloads( $older_than ) {
		$files = glob( trailingslashit( get_temp_dir() ) . self::DOWNLOAD_PREFIX . '*' );
		foreach ( is_array( $files ) ? $files : array() as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $older_than ) {
				wp_delete_file( $file );
			}
		}
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
	 * @param array      $result   Artws_Sec_Feed_Builder::result().
	 * @param array|null $previous State of the last successful run.
	 * @throws Artws_Sec_Feed_Exception When the data must not be published (code REJECTED).
	 */
	private static function check_result( array $result, $previous ) {
		$stats = $result['stats'];

		/**
		 * Filters the largest share of records that may fail the contract before the run fails.
		 *
		 * @param float $ratio Default 0.02 (2%).
		 */
		$max_excluded = (float) apply_filters( 'artws_sec_feed_max_excluded_ratio', self::DEFAULT_MAX_EXCLUDED_RATIO );
		if ( $stats['candidates'] > 0 && $stats['excluded'] / $stats['candidates'] > $max_excluded ) {
			throw new Artws_Sec_Feed_Exception(
				sprintf( '%d of %d records failed the contract check (more than %s%%).', $stats['excluded'], $stats['candidates'], (string) ( $max_excluded * 100 ) ),
				Artws_Sec_Feed_Exception::REJECTED
			);
		}

		foreach ( $result['counts'] as $type => $count ) {
			if ( 0 === $count ) {
				throw new Artws_Sec_Feed_Exception( sprintf( 'The %s file would have no records.', $type ), Artws_Sec_Feed_Exception::REJECTED );
			}
		}

		if ( array() === $result['copyrights'] ) {
			throw new Artws_Sec_Feed_Exception( 'No copyright holders were found in the records.', Artws_Sec_Feed_Exception::REJECTED );
		}

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
					sprintf( 'The %1$s records dropped from %2$d to %3$d. If Wordfence removed them on purpose, allow the drop on the settings page.', $type, $before, $count ),
					Artws_Sec_Feed_Exception::REJECTED
				);
			}
		}
	}

	/**
	 * Marks the published feed stale and rewrites its manifest.
	 *
	 * Call it while holding the lock; deactivate() is the only caller that
	 * may not have it.
	 *
	 * The state is saved as stale only once the manifest says so, so that the
	 * watchdog tries again when the manifest could not be written. Before the
	 * first successful run there is nothing to mark: no manifest is served.
	 *
	 * @param string $message Why the feed is stale.
	 * @return string The message, with the manifest error appended when the manifest could not be written.
	 */
	private static function mark_stale( $message ) {
		$state = self::fresh_state();
		if ( null === $state ) {
			return $message;
		}

		$state['stale'] = true;
		try {
			Artws_Sec_Feed_Publisher::write_manifest( Artws_Sec_Feed_Publisher::render_manifest( $state, self::get_eol() ) );
		} catch ( Artws_Sec_Feed_Exception $e ) {
			return $message . ' The stale manifest could not be written either: ' . $e->getMessage();
		}
		update_option( self::STATE_OPTION, $state, false );
		return $message;
	}

	/**
	 * Reads the published state from the database, not from the options cache.
	 *
	 * @return array|null
	 */
	private static function fresh_state() {
		self::fresh_option( self::STATE_OPTION );
		return self::get_state();
	}

	/**
	 * Reads a non-autoloaded option from the database, not from the options cache.
	 *
	 * Another process may have changed it since this one cached it.
	 *
	 * @param string $name Option name.
	 * @return mixed The value, or false when the option does not exist.
	 */
	private static function fresh_option( $name ) {
		wp_cache_delete( $name, 'options' );
		$notoptions = wp_cache_get( 'notoptions', 'options' );
		if ( is_array( $notoptions ) && isset( $notoptions[ $name ] ) ) {
			unset( $notoptions[ $name ] );
			wp_cache_set( 'notoptions', $notoptions, 'options' );
		}
		return get_option( $name );
	}

	/**
	 * Whether a published, not yet stale feed has gone without a successful run for too long.
	 *
	 * @param array|null $state Published state.
	 * @param int        $now   Unix time.
	 * @return bool
	 */
	private static function is_overdue( $state, $now ) {
		return null !== $state && ! $state['stale'] && $now - (int) $state['last_success'] > self::stale_after();
	}

	/**
	 * Saves the result of a run for the settings page.
	 *
	 * @param int    $now      Time of the run.
	 * @param string $result   "success", "failure" or "stale".
	 * @param string $message  Error message.
	 * @param array  $stats    Counters from Artws_Sec_Feed_Builder (empty when the download failed).
	 * @param int    $retry_at When the retry is scheduled, or 0.
	 */
	private static function record_status( $now, $result, $message, array $stats, $retry_at = 0 ) {
		update_option(
			self::STATUS_OPTION,
			array(
				'last_attempt' => $now,
				'result'       => $result,
				'message'      => $message,
				'stats'        => $stats,
				'retry_at'     => $retry_at,
			),
			false
		);
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
