<?php
/**
 * Custom tables of the free security check.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Db
 *
 * Creates and upgrades the tables with dbDelta(). Replacing the plugin with
 * an uploaded ZIP does not run the activation hook, so the stored version is
 * also checked on init.
 *
 * Times are Unix timestamps. Every key that is looked up is a lower-case
 * SHA-256 (hex) of the value, so the lookups do not depend on the collation
 * of the table (case-insensitive comparisons, trailing spaces).
 */
class Artws_Sec_Db {

	/**
	 * Version of the table definitions; bump it when they change.
	 */
	const VERSION = '1';

	/**
	 * Option holding the installed version (autoloaded: read on every request).
	 */
	const VERSION_OPTION = 'artws_sec_db_version';

	/**
	 * Registers the version check.
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
	}

	/**
	 * Creates or upgrades the tables when the stored version differs.
	 */
	public static function maybe_install() {
		if ( self::VERSION !== get_option( self::VERSION_OPTION ) ) {
			self::install();
		}
	}

	/**
	 * Creates or upgrades the tables.
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = $wpdb->get_charset_collate();
		$sites   = self::sites_table();
		$scans   = self::scans_table();
		$limits  = self::limits_table();
		$vulns   = self::vulns_table();

		dbDelta(
			array(
				// One row per site: the hash of its normalized site_url and of its site_token.
				"CREATE TABLE {$sites} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  site_key char(64) NOT NULL,
  site_url varchar(2048) NOT NULL,
  token_hash char(64) NOT NULL,
  created_at bigint(20) unsigned NOT NULL,
  last_scan_at bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY site_key (site_key),
  UNIQUE KEY token_hash (token_hash),
  KEY last_scan_at (last_scan_at)
) {$collate};",
				// One row per scan: the accepted request and the report, found by the hash of its scan token.
				"CREATE TABLE {$scans} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  site_id bigint(20) unsigned NOT NULL,
  scan_key char(64) NOT NULL,
  created_at bigint(20) unsigned NOT NULL,
  request longtext NOT NULL,
  report longtext NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY scan_key (scan_key),
  KEY site_id (site_id),
  KEY created_at (created_at)
) {$collate};",
				// Rate limit windows, keyed by an HMAC of the site or the client address.
				"CREATE TABLE {$limits} (
  limit_key char(64) NOT NULL,
  window_start bigint(20) unsigned NOT NULL,
  hits int(10) unsigned NOT NULL,
  PRIMARY KEY  (limit_key),
  KEY window_start (window_start)
) {$collate};",
				// The published vulnerability records by software, one generation per feed version.
				"CREATE TABLE {$vulns} (
  feed_version varchar(64) NOT NULL,
  lookup_key char(64) NOT NULL,
  records longtext NOT NULL,
  PRIMARY KEY  (feed_version,lookup_key)
) {$collate};",
			)
		);

		// Recorded only once every table is there, so that a failed dbDelta() is tried again.
		foreach ( array( $sites, $scans, $limits, $vulns ) as $table ) {
			if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks the schema.
				return;
			}
		}
		update_option( self::VERSION_OPTION, self::VERSION, true );
	}

	/**
	 * Registered sites.
	 *
	 * @return string
	 */
	public static function sites_table() {
		global $wpdb;
		return $wpdb->prefix . 'artws_sec_sites';
	}

	/**
	 * Scans and their reports.
	 *
	 * @return string
	 */
	public static function scans_table() {
		global $wpdb;
		return $wpdb->prefix . 'artws_sec_scans';
	}

	/**
	 * Rate limit windows.
	 *
	 * @return string
	 */
	public static function limits_table() {
		global $wpdb;
		return $wpdb->prefix . 'artws_sec_limits';
	}

	/**
	 * Vulnerability index.
	 *
	 * @return string
	 */
	public static function vulns_table() {
		global $wpdb;
		return $wpdb->prefix . 'artws_sec_vulns';
	}
}
