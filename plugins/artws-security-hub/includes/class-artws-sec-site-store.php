<?php
/**
 * Registered sites and their scans.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Site_Store
 *
 * A site is registered by its first scan and gets a site_token, of which only
 * the SHA-256 hash is stored. Scans are kept for the retention period and
 * then deleted by a daily cron event, together with the sites that have not
 * run a scan in that time.
 */
class Artws_Sec_Site_Store {

	/**
	 * Prefix of a site token.
	 */
	const SITE_TOKEN_PREFIX = 'sit_';

	/**
	 * Prefix of a scan token (the report's "token").
	 */
	const SCAN_TOKEN_PREFIX = 'scn_';

	/**
	 * A site token: the prefix and 32 random bytes in base64url.
	 */
	const SITE_TOKEN_PATTERN = '/^sit_[A-Za-z0-9_-]{43}$/D';

	/**
	 * A scan token as the contract allows it.
	 */
	const SCAN_TOKEN_PATTERN = '/^scn_[A-Za-z0-9_-]{32,128}$/D';

	/**
	 * Daily cron event that runs cleanup().
	 */
	const CLEANUP_HOOK = 'artws_sec_scan_cleanup';

	/**
	 * Days a scan is kept, and a site without scans.
	 */
	const DEFAULT_RETENTION_DAYS = 180;

	/**
	 * Registers the cleanup event.
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'schedule_events' ) );
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup' ), 10, 0 );
	}

	/**
	 * Schedules the daily cleanup when it is missing.
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Activation: creates the tables and schedules the cleanup.
	 */
	public static function activate() {
		Artws_Sec_Db::install();
		self::schedule_events();
	}

	/**
	 * Deactivation: removes the cleanup event. The data stays.
	 */
	public static function deactivate() {
		wp_unschedule_hook( self::CLEANUP_HOOK );
	}

	/**
	 * Returns a new random token.
	 *
	 * @param string $prefix SITE_TOKEN_PREFIX or SCAN_TOKEN_PREFIX.
	 * @return string
	 */
	public static function new_token( $prefix ) {
		return $prefix . rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encodes random bytes as a token.
	}

	/**
	 * Returns the hash under which a token is stored.
	 *
	 * @param string $token Token.
	 * @return string
	 */
	public static function hash_token( $token ) {
		return hash( 'sha256', $token );
	}

	/**
	 * Returns the key that identifies a site by its URL.
	 *
	 * The scheme is left out, so a site that moves from http to https (as the
	 * report advises) stays the same site; so are the case of the host and a
	 * trailing slash.
	 *
	 * @param string $site_url home_url() of the site.
	 * @return string
	 */
	public static function site_key( $site_url ) {
		$parts = wp_parse_url( $site_url );
		if ( ! is_array( $parts ) || ! isset( $parts['host'] ) ) {
			$rest = strstr( $site_url, '://' );
			return hash( 'sha256', strtolower( untrailingslashit( false === $rest ? $site_url : substr( $rest, 3 ) ) ) );
		}

		$site = strtolower( $parts['host'] )
			. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' )
			. untrailingslashit( $parts['path'] ?? '' )
			. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
		return hash( 'sha256', $site );
	}

	/**
	 * Returns the site registered under a site key.
	 *
	 * @param string $site_key Key from site_key().
	 * @return object|null Row of the sites table.
	 */
	public static function find_site_by_key( $site_key ) {
		global $wpdb;

		$table = Artws_Sec_Db::sites_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE site_key = %s", $site_key ) );
		return is_object( $row ) ? $row : null;
	}

	/**
	 * Returns the site a site token belongs to.
	 *
	 * @param string $token Site token.
	 * @return object|null Row of the sites table.
	 */
	public static function find_site_by_token( $token ) {
		global $wpdb;

		if ( ! is_string( $token ) || ! preg_match( self::SITE_TOKEN_PATTERN, $token ) ) {
			return null;
		}
		$table = Artws_Sec_Db::sites_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token_hash = %s", self::hash_token( $token ) ) );
		return is_object( $row ) && self::token_matches( $row, $token ) ? $row : null;
	}

	/**
	 * Whether a token is the site's.
	 *
	 * @param object      $site  Row of the sites table.
	 * @param string|null $token Site token from the request.
	 * @return bool
	 */
	public static function token_matches( $site, $token ) {
		return is_string( $token ) && '' !== $token && hash_equals( (string) $site->token_hash, self::hash_token( $token ) );
	}

	/**
	 * Registers a site.
	 *
	 * @param string $site_url Site URL from the request.
	 * @param int    $now      Unix time.
	 * @return array{0: int, 1: string}|null The new site ID and its token, or null when it could not be saved.
	 */
	public static function register( $site_url, $now ) {
		global $wpdb;

		$token    = self::new_token( self::SITE_TOKEN_PREFIX );
		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table.
			Artws_Sec_Db::sites_table(),
			array(
				'site_key'     => self::site_key( $site_url ),
				'site_url'     => $site_url,
				'token_hash'   => self::hash_token( $token ),
				'created_at'   => $now,
				'last_scan_at' => $now,
			),
			array( '%s', '%s', '%s', '%d', '%d' )
		);
		return 1 === $inserted ? array( (int) $wpdb->insert_id, $token ) : null;
	}

	/**
	 * Registers a site again: deletes its scans, then replaces its token.
	 *
	 * The scans go first, so that a failure in between never leaves them
	 * readable with the new token.
	 *
	 * @param object $site     Row of the sites table.
	 * @param string $site_url Site URL from the request.
	 * @param int    $now      Unix time.
	 * @return string|null The new token, or null when it could not be saved.
	 */
	public static function reregister( $site, $site_url, $now ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom tables.
		if ( false === $wpdb->delete( Artws_Sec_Db::scans_table(), array( 'site_id' => (int) $site->id ), array( '%d' ) ) ) {
			return null;
		}
		$token   = self::new_token( self::SITE_TOKEN_PREFIX );
		$updated = $wpdb->update(
			Artws_Sec_Db::sites_table(),
			array(
				'site_url'     => $site_url,
				'token_hash'   => self::hash_token( $token ),
				'created_at'   => $now,
				'last_scan_at' => $now,
			),
			array( 'id' => (int) $site->id ),
			array( '%s', '%s', '%d', '%d' ),
			array( '%d' )
		);
		// phpcs:enable
		return 1 === $updated ? $token : null;
	}

	/**
	 * Saves a scan and records it as the site's latest.
	 *
	 * @param int    $site_id    Site ID.
	 * @param string $site_url   Site URL from the request (the latest one is kept).
	 * @param string $scan_token Scan token.
	 * @param array  $request    Normalized request.
	 * @param array  $report     Report.
	 * @param int    $now        Unix time.
	 * @return bool
	 */
	public static function save_scan( $site_id, $site_url, $scan_token, array $request, array $report, $now ) {
		global $wpdb;

		$request_json = wp_json_encode( $request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$report_json  = wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $request_json || false === $report_json ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom tables.
		$inserted = $wpdb->insert(
			Artws_Sec_Db::scans_table(),
			array(
				'site_id'    => (int) $site_id,
				'scan_key'   => self::hash_token( $scan_token ),
				'created_at' => $now,
				'request'    => $request_json,
				'report'     => $report_json,
			),
			array( '%d', '%s', '%d', '%s', '%s' )
		);
		if ( 1 !== $inserted ) {
			return false;
		}
		$wpdb->update(
			Artws_Sec_Db::sites_table(),
			array(
				'site_url'     => $site_url,
				'last_scan_at' => $now,
			),
			array( 'id' => (int) $site_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);
		// phpcs:enable
		return true;
	}

	/**
	 * Returns a scan.
	 *
	 * @param string $scan_token Scan token.
	 * @return object|null Row of the scans table.
	 */
	public static function find_scan( $scan_token ) {
		global $wpdb;

		if ( ! is_string( $scan_token ) || ! preg_match( self::SCAN_TOKEN_PATTERN, $scan_token ) ) {
			return null;
		}
		$table = Artws_Sec_Db::scans_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE scan_key = %s", self::hash_token( $scan_token ) ) );
		return is_object( $row ) ? $row : null;
	}

	/**
	 * Returns a site by ID.
	 *
	 * @param int $site_id Site ID.
	 * @return object|null Row of the sites table.
	 */
	public static function find_site( $site_id ) {
		global $wpdb;

		$table = Artws_Sec_Db::sites_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $site_id ) );
		return is_object( $row ) ? $row : null;
	}

	/**
	 * Deletes a site and its scans.
	 *
	 * @param int $site_id Site ID.
	 * @return bool
	 */
	public static function delete_site( $site_id ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom tables.
		$scans = $wpdb->delete( Artws_Sec_Db::scans_table(), array( 'site_id' => (int) $site_id ), array( '%d' ) );
		$sites = $wpdb->delete( Artws_Sec_Db::sites_table(), array( 'id' => (int) $site_id ), array( '%d' ) );
		// phpcs:enable
		return false !== $scans && false !== $sites;
	}

	/**
	 * Deletes the scans and sites older than the retention period, and the ended rate limit windows.
	 *
	 * @param int|null $now Unix time (for tests).
	 */
	public static function cleanup( $now = null ) {
		global $wpdb;

		$now    = null === $now ? time() : (int) $now;
		$cutoff = $now - self::retention_days() * DAY_IN_SECONDS;
		$scans  = Artws_Sec_Db::scans_table();
		$sites  = Artws_Sec_Db::sites_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom tables.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$scans} WHERE created_at < %d", $cutoff ) );
		// A scan is saved before last_scan_at is updated, so a site that has a scan left is kept.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$sites} WHERE last_scan_at < %d AND NOT EXISTS ( SELECT 1 FROM {$scans} c WHERE c.site_id = {$sites}.id )", $cutoff ) );
		// phpcs:enable

		Artws_Sec_Rate_Limiter::cleanup( $now, Artws_Sec_Rest_Controller::window() );
	}

	/**
	 * Returns the retention period in days.
	 *
	 * @return int
	 */
	public static function retention_days() {
		/**
		 * Filters how many days scans, and sites without scans, are kept.
		 *
		 * @param int $days Default 180. Also stated in the privacy policy of wc.artws.info.
		 */
		return max( 1, (int) apply_filters( 'artws_sec_retention_days', self::DEFAULT_RETENTION_DAYS ) );
	}
}
