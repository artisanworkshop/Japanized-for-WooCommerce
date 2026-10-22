<?php
/**
 * Vulnerability feed of the security check.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.AlternativeFunctions -- Streams large files and renames them in place; WP_Filesystem reads whole files only and may not be set up during cron.

require_once __DIR__ . '/class-jp4wc-security-feed-reader.php';
require_once __DIR__ . '/class-jp4wc-security-scanner.php';

/**
 * JP4WC_Security_Feed
 *
 * Downloads the vulnerability feed of wc.artws.info once a day, keeps it in
 * wp-content/uploads/jp4wc-security/ and matches the installed software
 * against it (JP4WC_Security_Scanner). Nothing about the site is sent: the
 * requests carry only a User-Agent with the versions of JP4WC, WordPress,
 * WooCommerce and PHP. The format is defined in tests/fixtures/security-feed/.
 *
 * The sync and the rescan both run as WP-Cron events, which WP-Cron's own lock
 * runs one at a time. Should two syncs still overlap, each writes its own
 * temporary files and renames them into place, so the stored files can come
 * from both. The state keeps the sha256 of each stored file, and the next sync
 * downloads again any file that no longer matches it, so there is no lock of
 * its own.
 */
class JP4WC_Security_Feed {

	/**
	 * Setting that turns the security check on ('1', the default) or off ('').
	 */
	const SETTING = 'wc4jp-security-scan';

	/**
	 * Option with what the last sync stored (not autoloaded).
	 */
	const STATE_OPTION = 'jp4wc_security_feed_state';

	/**
	 * Option with the result of the last match (not autoloaded).
	 */
	const FINDINGS_OPTION = 'jp4wc_security_findings';

	/**
	 * Daily WP-Cron event that downloads the feed.
	 */
	const SYNC_HOOK = 'jp4wc_security_feed_sync';

	/**
	 * Single WP-Cron event that matches the installed software again.
	 */
	const RESCAN_HOOK = 'jp4wc_security_rescan';

	/**
	 * Feed location; the `jp4wc_security_feed_base_url` filter changes it.
	 */
	const DEFAULT_BASE_URL = 'https://wc.artws.info/feed/v1/';

	/**
	 * Vulnerability file types of the manifest.
	 */
	const TYPES = array( 'plugin', 'theme', 'core' );

	/**
	 * Directory under the uploads directory where the feed is kept.
	 */
	const DIRECTORY = 'jp4wc-security';

	/**
	 * Largest manifest accepted, in bytes.
	 */
	const MAX_MANIFEST_BYTES = 1048576;

	/**
	 * Largest .json.gz accepted, in bytes. The plugin file was a few megabytes in 2026.
	 */
	const MAX_GZ_BYTES = 33554432;

	/**
	 * Largest decompressed vulnerability file accepted, in bytes.
	 */
	const MAX_JSON_BYTES = 268435456;

	/**
	 * Compressed bytes inflated per inflate_add() call. A deflate block expands
	 * at most about a thousand times, so one call never holds more than a few megabytes.
	 */
	const INFLATE_CHUNK = 8192;

	/**
	 * Data generated longer ago than this is shown as out of date (3 days).
	 */
	const STALE_AFTER = 259200;

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_action( self::SYNC_HOOK, array( __CLASS__, 'sync' ) );
		add_action( self::RESCAN_HOOK, array( __CLASS__, 'rescan' ) );
		add_action( 'admin_init', array( __CLASS__, 'ensure_schedule' ) );
		add_action( 'add_option_' . self::SETTING, array( __CLASS__, 'reconcile' ), 10, 0 );
		add_action( 'update_option_' . self::SETTING, array( __CLASS__, 'reconcile' ), 10, 0 );

		// Installed versions change: match again.
		foreach ( array( 'upgrader_process_complete', 'activated_plugin', 'deleted_plugin', 'switch_theme', 'deleted_theme' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'schedule_rescan' ), 10, 0 );
		}
	}

	/**
	 * Whether the security check is on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return '1' === get_option( self::SETTING, '1' );
	}

	/**
	 * Returns the setting as stored in the database.
	 *
	 * A sync or a match takes seconds, and the check can be turned off, or the
	 * plugin uninstalled, in the meantime. The options this process loaded
	 * when it started do not show that.
	 *
	 * @return string|null Null when there is no setting row.
	 */
	private static function setting_in_database() {
		global $wpdb;
		// get_row(), not get_var(): get_var() returns null for '' as well as for a missing row.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Must not read the options this process cached.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", self::SETTING ) );
		return null === $row ? null : (string) $row->option_value;
	}

	/**
	 * Whether the stored data may stay, judged after writing it.
	 *
	 * Turning the check off saves the setting first and then purges, and
	 * uninstall purges and then deletes the setting. Checking the database
	 * after each write therefore either sees the change and purges here, or
	 * the change's own purge runs after the write. A missing setting row
	 * counts as off: start_run() stores the default before a run begins.
	 *
	 * @return bool False when the data was purged.
	 */
	private static function keep_written_data() {
		if ( '1' === self::setting_in_database() ) {
			return true;
		}
		self::purge();
		return false;
	}

	/**
	 * Stores the default setting for a run when the site has no setting row,
	 * for example after it was deleted by hand, so that keep_written_data()
	 * can tell it from a check turned off or uninstalled meanwhile.
	 */
	private static function start_run() {
		if ( null === self::setting_in_database() ) {
			add_option( self::SETTING, '1' );
		}
	}

	/**
	 * Sets up the setting on install and update, and matches the WP-Cron
	 * event and the stored data to it.
	 */
	public static function install() {
		add_option( self::SETTING, '1' );
		self::reconcile();
	}

	/**
	 * Schedules the daily sync when the check is on; removes the events and
	 * the data when it is off.
	 */
	public static function reconcile() {
		if ( self::is_enabled() ) {
			if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
				wp_schedule_event( time(), 'daily', self::SYNC_HOOK );
			}
			return;
		}
		self::purge();
	}

	/**
	 * Keeps the daily sync scheduled while the check is on (admin_init).
	 *
	 * Lighter than reconcile(): when the check is off, it removes the data only
	 * while the event is still scheduled, so it does not query the options on
	 * every admin request.
	 */
	public static function ensure_schedule() {
		$scheduled = wp_next_scheduled( self::SYNC_HOOK );
		if ( self::is_enabled() ) {
			if ( ! $scheduled ) {
				wp_schedule_event( time(), 'daily', self::SYNC_HOOK );
			}
		} elseif ( $scheduled ) {
			self::purge();
		}
	}

	/**
	 * Removes the WP-Cron events, the stored state, the findings and the downloaded files.
	 */
	public static function purge() {
		wp_clear_scheduled_hook( self::SYNC_HOOK );
		wp_clear_scheduled_hook( self::RESCAN_HOOK );
		delete_option( self::STATE_OPTION );
		delete_option( self::FINDINGS_OPTION );

		$directory = self::directory_path();
		if ( '' === $directory || ! is_dir( $directory ) ) {
			return;
		}
		$names = scandir( $directory );
		if ( false === $names ) {
			return;
		}
		$left = 0;
		foreach ( array_diff( $names, array( '.', '..' ) ) as $name ) {
			$path = $directory . '/' . $name;
			if ( is_file( $path ) ) {
				wp_delete_file( $path );
			}
			if ( file_exists( $path ) ) {
				++$left;
			}
		}
		if ( 0 === $left ) {
			rmdir( $directory );
		}
	}

	/**
	 * Removes everything the security check stored, on every site of a network (uninstall.php).
	 *
	 * Each site of a network has its own setting, events and directory under
	 * its uploads directory.
	 */
	public static function uninstall() {
		if ( ! is_multisite() ) {
			self::purge();
			delete_option( self::SETTING );
			return;
		}
		foreach ( get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		) as $site_id ) {
			switch_to_blog( (int) $site_id );
			try {
				self::purge();
				delete_option( self::SETTING );
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Matches the installed software again soon, in a WP-Cron event of its own.
	 *
	 * WordPress drops an identical event scheduled within ten minutes of
	 * another, so activating many plugins at once runs one match.
	 */
	public static function schedule_rescan() {
		if ( ! self::is_enabled() || ! self::has_data() ) {
			return;
		}
		wp_schedule_single_event( time(), self::RESCAN_HOOK );
	}

	/**
	 * Returns what the last sync stored.
	 *
	 * @return array version, generated_at, stale, files (type => sha256, count,
	 *               json_sha256), copyrights, attribution, synced_at, last_attempt,
	 *               last_error and failing_since (when the syncs began to fail, or 0).
	 */
	public static function get_state() {
		$state = get_option( self::STATE_OPTION, array() );
		return array_merge(
			array(
				'version'       => '',
				'generated_at'  => '',
				'stale'         => false,
				'files'         => array(),
				'copyrights'    => array(),
				'attribution'   => '',
				'synced_at'     => 0,
				'last_attempt'  => 0,
				'last_error'    => '',
				'failing_since' => 0,
			),
			is_array( $state ) ? $state : array()
		);
	}

	/**
	 * Whether a sync has stored vulnerability files.
	 *
	 * @return bool
	 */
	private static function has_data() {
		$state = self::get_state();
		return is_array( $state['files'] ) && array() !== $state['files'];
	}

	/**
	 * Whether stored data should be shown as out of date.
	 *
	 * The server marks the manifest stale when it cannot refresh the data from
	 * Wordfence. A manifest the server stopped rewriting, or one this site has
	 * not been able to download for days, cannot say so, so data generated
	 * longer ago than STALE_AFTER counts as stale too.
	 *
	 * @param array $data A state or findings array with `stale` and `generated_at`.
	 * @return bool
	 */
	public static function is_stale( array $data ) {
		if ( ! empty( $data['stale'] ) ) {
			return true;
		}
		$generated = isset( $data['generated_at'] ) && is_string( $data['generated_at'] ) ? strtotime( $data['generated_at'] ) : false;
		if ( false === $generated ) {
			return true;
		}
		return time() - $generated > self::stale_after();
	}

	/**
	 * Whether the check has had no data for longer than STALE_AFTER because
	 * every sync failed (outbound requests blocked, the feed unreachable).
	 *
	 * Once data was stored, its age shows the same through is_stale().
	 *
	 * @return bool
	 */
	public static function is_unavailable() {
		$state = self::get_state();
		if ( is_array( $state['files'] ) && array() !== $state['files'] ) {
			return false;
		}
		$failing_since = (int) $state['failing_since'];
		return $failing_since > 0 && time() - $failing_since > self::stale_after();
	}

	/**
	 * Returns how old data may be before it is shown as out of date.
	 *
	 * @return int Seconds.
	 */
	private static function stale_after() {
		/**
		 * Filters how old the vulnerability data may be before it is shown as out of date.
		 *
		 * @since 3.0.0
		 * @param int $seconds Age in seconds. Default 3 days.
		 */
		return (int) apply_filters( 'jp4wc_security_feed_stale_after', self::STALE_AFTER );
	}

	/**
	 * Downloads the feed when it changed and matches the installed software (WP-Cron).
	 *
	 * The vulnerability files are replaced only when every changed file
	 * downloaded and checked out; otherwise the previous files stay in use,
	 * which also covers a manifest and files that a cache mixed up.
	 */
	public static function sync() {
		if ( ! self::is_enabled() ) {
			self::purge();
			return;
		}

		self::start_run();
		$state                 = self::get_state();
		$state['last_attempt'] = time();

		$result = self::update_files( $state );
		if ( is_wp_error( $result ) ) {
			$state['last_error'] = $result->get_error_message();
			if ( empty( $state['failing_since'] ) ) {
				$state['failing_since'] = $state['last_attempt'];
			}
		} else {
			$state = $result;
		}

		update_option( self::STATE_OPTION, $state, false );
		// The check may have been turned off while the files were downloading.
		if ( ! self::keep_written_data() ) {
			return;
		}

		self::rescan();
	}

	/**
	 * Matches the installed software against the stored files and saves the result.
	 */
	public static function rescan() {
		if ( ! self::is_enabled() ) {
			return;
		}
		self::start_run();
		$state = self::get_state();
		if ( ! is_array( $state['files'] ) || array() === $state['files'] ) {
			return;
		}

		$directory = self::directory_path();
		if ( '' === $directory ) {
			return;
		}
		$files = array();
		foreach ( array_keys( $state['files'] ) as $type ) {
			$files[ $type ] = $directory . '/' . $type . '.json';
		}

		$items = JP4WC_Security_Scanner::scan( $files, JP4WC_Security_Scanner::installed_software() );
		$count = 0;
		foreach ( $items as $item ) {
			$count += count( $item['vulnerabilities'] );
		}

		update_option(
			self::FINDINGS_OPTION,
			array(
				'checked_at'   => time(),
				'version'      => $state['version'],
				'generated_at' => $state['generated_at'],
				'stale'        => (bool) $state['stale'],
				'copyrights'   => $state['copyrights'],
				'attribution'  => $state['attribution'],
				'count'        => $count,
				'max_severity' => JP4WC_Security_Scanner::max_rating( $items ),
				'items'        => $items,
			),
			false
		);
		// The check may have been turned off while the files were read.
		self::keep_written_data();
	}

	/**
	 * Returns the findings of the last match.
	 *
	 * @return array|null Null before the first match.
	 */
	public static function get_findings() {
		$findings = get_option( self::FINDINGS_OPTION, null );
		if ( ! is_array( $findings ) || ! isset( $findings['items'] ) || ! is_array( $findings['items'] ) ) {
			return null;
		}
		return array_merge(
			array(
				'checked_at'   => 0,
				'version'      => '',
				'generated_at' => '',
				'stale'        => false,
				'copyrights'   => array(),
				'attribution'  => '',
				'count'        => 0,
				'max_severity' => '',
			),
			$findings
		);
	}

	/**
	 * Downloads the manifest and every vulnerability file that changed.
	 *
	 * @param array $state Stored state.
	 * @return array|WP_Error The new state, or the error that kept the previous files.
	 */
	private static function update_files( array $state ) {
		$manifest = self::fetch_manifest();
		if ( is_wp_error( $manifest ) ) {
			return $manifest;
		}

		$directory = self::prepare_directory();
		if ( is_wp_error( $directory ) ) {
			return $directory;
		}
		self::remove_old_temporary_files( $directory );

		$temporary = array();
		$stored    = array();
		try {
			foreach ( self::TYPES as $type ) {
				$entry = $manifest['files'][ $type ];
				if ( self::is_stored( $state, $type, $entry['sha256'], $directory . '/' . $type . '.json' ) ) {
					$stored[ $type ] = $state['files'][ $type ]['json_sha256'];
					continue;
				}
				$temporary[ $type ] = $directory . '/' . $type . '.json.' . wp_generate_password( 12, false ) . '.tmp';

				$downloaded = self::download( $type, $entry, $temporary[ $type ] );
				if ( is_wp_error( $downloaded ) ) {
					return $downloaded;
				}
				$stored[ $type ] = $downloaded;
			}

			foreach ( $temporary as $type => $path ) {
				if ( ! rename( $path, $directory . '/' . $type . '.json' ) ) {
					return new WP_Error( 'jp4wc_security_feed_write', sprintf( 'The %s vulnerability file could not be saved.', $type ) );
				}
				unset( $temporary[ $type ] );
			}
		} finally {
			foreach ( $temporary as $path ) {
				if ( is_file( $path ) ) {
					wp_delete_file( $path );
				}
			}
		}

		$files = array();
		foreach ( self::TYPES as $type ) {
			$files[ $type ] = array(
				'sha256'      => $manifest['files'][ $type ]['sha256'],
				'count'       => $manifest['files'][ $type ]['count'],
				'json_sha256' => $stored[ $type ],
			);
		}

		return array_merge(
			$state,
			array(
				'version'       => $manifest['version'],
				'generated_at'  => $manifest['generated_at'],
				'stale'         => $manifest['stale'],
				'files'         => $files,
				'copyrights'    => $manifest['copyrights'],
				'attribution'   => $manifest['attribution'],
				'synced_at'     => time(),
				'last_error'    => '',
				'failing_since' => 0,
			)
		);
	}

	/**
	 * Whether the stored file of a type is the one the manifest lists.
	 *
	 * Compares the manifest's checksum with the stored state, and the stored
	 * file with the checksum it had when it was written: two syncs that ran
	 * at once may have left another run's file under a state that is not its own.
	 *
	 * @param array  $state  Stored state.
	 * @param string $type   File type.
	 * @param string $sha256 Checksum of the .json.gz in the manifest.
	 * @param string $path   Stored file.
	 * @return bool
	 */
	private static function is_stored( array $state, $type, $sha256, $path ) {
		$file = isset( $state['files'][ $type ] ) && is_array( $state['files'][ $type ] ) ? $state['files'][ $type ] : array();
		if ( ! isset( $file['sha256'], $file['json_sha256'] ) || ! is_string( $file['sha256'] ) || ! is_string( $file['json_sha256'] ) ) {
			return false;
		}
		if ( ! hash_equals( $file['sha256'], $sha256 ) || ! is_file( $path ) ) {
			return false;
		}
		$actual = hash_file( 'sha256', $path );
		return is_string( $actual ) && hash_equals( $file['json_sha256'], $actual );
	}

	/**
	 * Downloads and checks the manifest.
	 *
	 * @return array|WP_Error The values the client uses, checked as the contract
	 *                        README asks (the JSON Schema is not used for this).
	 */
	private static function fetch_manifest() {
		$response = wp_remote_get(
			self::base_url() . 'manifest.json',
			self::request_args(
				array(
					'timeout'             => 15,
					'limit_response_size' => self::MAX_MANIFEST_BYTES,
				)
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'jp4wc_security_feed_http', 'The manifest could not be downloaded: ' . $response->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'jp4wc_security_feed_http', sprintf( 'The manifest could not be downloaded: HTTP %d.', $code ) );
		}

		return self::parse_manifest( json_decode( wp_remote_retrieve_body( $response ), true ) );
	}

	/**
	 * Checks a decoded manifest and keeps the values the client uses.
	 *
	 * Members the client does not know are ignored, and so are file types other
	 * than plugin, theme and core.
	 *
	 * @param mixed $manifest Decoded manifest.json.
	 * @return array|WP_Error version, generated_at, stale, files, copyrights and attribution.
	 */
	public static function parse_manifest( $manifest ) {
		$invalid = static function ( $what ) {
			return new WP_Error( 'jp4wc_security_feed_manifest', sprintf( 'The manifest is not valid: %s.', $what ) );
		};

		if ( ! is_array( $manifest ) ) {
			return $invalid( 'not a JSON object' );
		}
		if ( ! isset( $manifest['schema_version'] ) || 1 !== $manifest['schema_version'] ) {
			return $invalid( 'schema_version is not 1' );
		}
		if ( ! isset( $manifest['version'] ) || ! is_string( $manifest['version'] ) || strlen( $manifest['version'] ) > 64 || 1 !== preg_match( '/^[0-9A-Za-z._-]+$/D', $manifest['version'] ) ) {
			return $invalid( 'version' );
		}
		if ( ! isset( $manifest['generated_at'] ) || ! is_string( $manifest['generated_at'] ) || 1 !== preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $manifest['generated_at'] ) ) {
			return $invalid( 'generated_at' );
		}
		if ( ! isset( $manifest['stale'] ) || ! is_bool( $manifest['stale'] ) ) {
			return $invalid( 'stale' );
		}
		if ( ! isset( $manifest['attribution'] ) || ! is_string( $manifest['attribution'] ) || '' === $manifest['attribution'] || strlen( $manifest['attribution'] ) > 1000 ) {
			return $invalid( 'attribution' );
		}

		$files = array();
		foreach ( self::TYPES as $type ) {
			$entry = isset( $manifest['files'][ $type ] ) ? $manifest['files'][ $type ] : null;
			if ( ! is_array( $entry ) ) {
				return $invalid( 'files.' . $type );
			}
			// A bare file name: it is appended to the feed URL and never names another directory.
			if ( ! isset( $entry['file'] ) || ! is_string( $entry['file'] ) || strlen( $entry['file'] ) > 128 || 1 !== preg_match( '/^[a-z0-9][a-z0-9._-]*\.json\.gz$/D', $entry['file'] ) ) {
				return $invalid( 'files.' . $type . '.file' );
			}
			if ( ! isset( $entry['sha256'] ) || ! is_string( $entry['sha256'] ) || 1 !== preg_match( '/^[0-9a-f]{64}$/D', $entry['sha256'] ) ) {
				return $invalid( 'files.' . $type . '.sha256' );
			}
			if ( ! isset( $entry['count'] ) || ! is_int( $entry['count'] ) || $entry['count'] < 0 ) {
				return $invalid( 'files.' . $type . '.count' );
			}
			$files[ $type ] = array(
				'file'   => $entry['file'],
				'sha256' => $entry['sha256'],
				'count'  => $entry['count'],
			);
		}

		// The data license asks for every holder's notice and license wherever the data is shown.
		$copyrights = array();
		if ( isset( $manifest['copyrights'] ) && is_array( $manifest['copyrights'] ) ) {
			foreach ( $manifest['copyrights'] as $holder => $copyright ) {
				if ( ! is_array( $copyright ) || ! isset( $copyright['notice'], $copyright['license'], $copyright['license_url'] )
					|| ! is_string( $copyright['notice'] ) || '' === $copyright['notice']
					|| ! is_string( $copyright['license'] ) || '' === $copyright['license']
					|| ! JP4WC_Security_Scanner::is_https_url( $copyright['license_url'] ) ) {
					return $invalid( 'copyrights.' . $holder );
				}
				$copyrights[ (string) $holder ] = array(
					'notice'      => $copyright['notice'],
					'license'     => $copyright['license'],
					'license_url' => $copyright['license_url'],
				);
			}
		}
		if ( array() === $copyrights ) {
			return $invalid( 'copyrights' );
		}

		return array(
			'version'      => $manifest['version'],
			'generated_at' => $manifest['generated_at'],
			'stale'        => $manifest['stale'],
			'files'        => $files,
			'copyrights'   => $copyrights,
			'attribution'  => $manifest['attribution'],
		);
	}

	/**
	 * Downloads one vulnerability file, checks it and writes it decompressed to $path.
	 *
	 * The order is the contract's: download, check the sha256 of the .json.gz
	 * bytes, decompress. The file is decompressed in pieces and its JSON is
	 * read as a stream, so a file of tens of megabytes is never held in memory.
	 *
	 * @param string $type  File type.
	 * @param array  $entry The type's entry of the manifest.
	 * @param string $path  Temporary file to write.
	 * @return string|WP_Error SHA-256 of the decompressed file written.
	 */
	private static function download( $type, array $entry, $path ) {
		$response = wp_remote_get(
			self::base_url() . $entry['file'],
			self::request_args(
				array(
					'timeout'             => 60,
					'limit_response_size' => self::MAX_GZ_BYTES,
					// Ask for the bytes as served: an HTTP client that decompressed them would break the sha256.
					'decompress'          => false,
				)
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'jp4wc_security_feed_http', sprintf( 'The %s vulnerability file could not be downloaded: %s', $type, $response->get_error_message() ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'jp4wc_security_feed_http', sprintf( 'The %s vulnerability file could not be downloaded: HTTP %d.', $type, $code ) );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! hash_equals( $entry['sha256'], hash( 'sha256', $body ) ) ) {
			return new WP_Error( 'jp4wc_security_feed_checksum', sprintf( 'The %s vulnerability file does not match the sha256 of the manifest.', $type ) );
		}

		$inflated = self::inflate_to_file( $body, $path );
		if ( is_wp_error( $inflated ) ) {
			return new WP_Error( $inflated->get_error_code(), sprintf( 'The %s vulnerability file %s', $type, $inflated->get_error_message() ) );
		}

		$checked = self::check_file( $type, $path );
		return is_wp_error( $checked ) ? $checked : $inflated;
	}

	/**
	 * Decompresses gzip data into a file, a piece at a time.
	 *
	 * Unlike reading through gzopen(), inflate reports a damaged stream, a
	 * wrong checksum and a stream cut short (it never reaches its end).
	 *
	 * @param string $gzip Gzip data.
	 * @param string $path File to write.
	 * @return string|WP_Error SHA-256 of the decompressed data.
	 */
	private static function inflate_to_file( $gzip, $path ) {
		if ( ! function_exists( 'inflate_init' ) ) {
			return new WP_Error( 'jp4wc_security_feed_zlib', 'cannot be decompressed: the PHP zlib extension is not available.' );
		}
		$context = inflate_init( ZLIB_ENCODING_GZIP );
		if ( false === $context ) {
			return new WP_Error( 'jp4wc_security_feed_zlib', 'cannot be decompressed.' );
		}
		$handle = fopen( $path, 'wb' );
		if ( false === $handle ) {
			return new WP_Error( 'jp4wc_security_feed_write', 'could not be written.' );
		}

		$written = 0;
		$length  = strlen( $gzip );
		$hash    = hash_init( 'sha256' );
		// inflate_add() also raises a warning for damaged data; the return value reports it.
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Turns the warning of damaged data into an error result.
			static function () {
				return true;
			},
			E_WARNING
		);
		try {
			for ( $offset = 0; $offset < $length; $offset += self::INFLATE_CHUNK ) {
				$piece = inflate_add( $context, substr( $gzip, $offset, self::INFLATE_CHUNK ), ZLIB_SYNC_FLUSH );
				if ( false === $piece ) {
					return new WP_Error( 'jp4wc_security_feed_gzip', 'is not valid gzip data.' );
				}
				$written += strlen( $piece );
				if ( $written > self::MAX_JSON_BYTES ) {
					return new WP_Error( 'jp4wc_security_feed_size', 'is too large when decompressed.' );
				}
				if ( '' !== $piece && strlen( $piece ) !== fwrite( $handle, $piece ) ) {
					return new WP_Error( 'jp4wc_security_feed_write', 'could not be written.' );
				}
				hash_update( $hash, $piece );
				if ( ZLIB_STREAM_END === inflate_get_status( $context ) ) {
					break;
				}
			}
			if ( ZLIB_STREAM_END !== inflate_get_status( $context ) ) {
				return new WP_Error( 'jp4wc_security_feed_gzip', 'is not valid gzip data: it ends early.' );
			}
		} finally {
			restore_error_handler();
			fclose( $handle );
		}

		return hash_final( $hash );
	}

	/**
	 * Checks a decompressed vulnerability file without decoding it as a whole.
	 *
	 * @param string $type Expected type.
	 * @param string $path File.
	 * @return true|WP_Error
	 */
	private static function check_file( $type, $path ) {
		$header = array();
		try {
			$found = JP4WC_Security_Feed_Reader::read(
				$path,
				static function ( $key, $json ) use ( &$header ) {
					if ( 'schema_version' === $key || 'type' === $key ) {
						$header[ $key ] = json_decode( $json );
					}
				},
				static function ( $slug, $json ) {
					// The reader finds where a value ends but does not parse it.
					$records = json_decode( $json, true );
					if ( ! is_array( $records ) || array() === $records || ! array_is_list( $records ) ) {
						throw new UnexpectedValueException( 'The records of a slug are not a list of records.' );
					}
				}
			);
		} catch ( UnexpectedValueException $e ) {
			return new WP_Error( 'jp4wc_security_feed_json', sprintf( 'The %s vulnerability file is not valid: %s', $type, $e->getMessage() ) );
		}

		if ( ! isset( $header['schema_version'] ) || 1 !== $header['schema_version'] ) {
			return new WP_Error( 'jp4wc_security_feed_json', sprintf( 'The %s vulnerability file is not valid: schema_version is not 1.', $type ) );
		}
		if ( ! isset( $header['type'] ) || $type !== $header['type'] ) {
			return new WP_Error( 'jp4wc_security_feed_json', sprintf( 'The %s vulnerability file is not valid: its type does not match.', $type ) );
		}
		if ( ! $found ) {
			return new WP_Error( 'jp4wc_security_feed_json', sprintf( 'The %s vulnerability file is not valid: it has no vulnerabilities object.', $type ) );
		}
		return true;
	}

	/**
	 * Returns the arguments of a feed request.
	 *
	 * The User-Agent replaces WordPress's default one, which holds the site URL.
	 * Redirects are not followed, so the requests go nowhere but the feed URL.
	 *
	 * @param array $args Arguments of this request.
	 * @return array
	 */
	private static function request_args( array $args ) {
		return array_merge(
			array(
				// The feed is served as static files: a redirect would only send the request elsewhere.
				'redirection' => 0,
				'user-agent'  => self::user_agent(),
			),
			$args
		);
	}

	/**
	 * Returns the User-Agent of the feed requests: only software versions.
	 *
	 * @return string
	 */
	public static function user_agent() {
		return sprintf(
			'JP4WC/%s; WP/%s; WC/%s; PHP/%s',
			defined( 'JP4WC_VERSION' ) ? JP4WC_VERSION : '',
			get_bloginfo( 'version' ),
			defined( 'WC_VERSION' ) ? WC_VERSION : '',
			PHP_VERSION
		);
	}

	/**
	 * Returns the feed URL, with a trailing slash.
	 *
	 * @return string
	 */
	public static function base_url() {
		/**
		 * Filters the URL the vulnerability feed is downloaded from.
		 *
		 * @since 3.0.0
		 * @param string $url Directory URL of manifest.json. Default https://wc.artws.info/feed/v1/.
		 */
		$url = apply_filters( 'jp4wc_security_feed_base_url', self::DEFAULT_BASE_URL );
		return trailingslashit( is_string( $url ) && '' !== $url ? $url : self::DEFAULT_BASE_URL );
	}

	/**
	 * Returns the directory the feed is kept in, without creating it.
	 *
	 * @return string Empty when the uploads directory is not available.
	 */
	public static function directory_path() {
		$uploads = wp_upload_dir( null, false );
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return '';
		}
		return untrailingslashit( $uploads['basedir'] ) . '/' . self::DIRECTORY;
	}

	/**
	 * Creates the directory the feed is kept in, closed to direct requests.
	 *
	 * @return string|WP_Error The directory.
	 */
	private static function prepare_directory() {
		$directory = self::directory_path();
		if ( '' === $directory || ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) ) {
			return new WP_Error( 'jp4wc_security_feed_write', 'The directory for the vulnerability files could not be created.' );
		}

		$guards = array(
			'index.php' => "<?php\n// Silence is golden.\n",
			'.htaccess' => "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
		);
		foreach ( $guards as $name => $content ) {
			if ( ! is_file( $directory . '/' . $name ) && false === file_put_contents( $directory . '/' . $name, $content ) ) {
				return new WP_Error( 'jp4wc_security_feed_write', 'The directory for the vulnerability files could not be written.' );
			}
		}
		return $directory;
	}

	/**
	 * Removes temporary files that a sync stopped by a fatal error left behind.
	 *
	 * Only files older than an hour, so that a sync running at the same time keeps its own.
	 *
	 * @param string $directory Feed directory.
	 */
	private static function remove_old_temporary_files( $directory ) {
		$paths = glob( $directory . '/*.tmp' );
		if ( ! is_array( $paths ) ) {
			return;
		}
		foreach ( $paths as $path ) {
			$modified = filemtime( $path );
			if ( false !== $modified && $modified < time() - HOUR_IN_SECONDS ) {
				wp_delete_file( $path );
			}
		}
	}
}

JP4WC_Security_Feed::init();
