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
	 *
	 * 2: sites.site_key is no longer unique, so a site_url can be registered more than once.
	 */
	const VERSION = '2';

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

		// dbDelta() never drops an index, so it cannot turn the unique site_key of version 1 into a plain one.
		if ( true === self::has_unique_site_key() ) {
			$wpdb->query( "ALTER TABLE {$sites} DROP INDEX site_key" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Upgrades this plugin's own table.
		}

		dbDelta(
			array(
				// One row per registration: the hash of its normalized site_url and of its site_token.
				"CREATE TABLE {$sites} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  site_key char(64) NOT NULL,
  site_url varchar(2048) NOT NULL,
  token_hash char(64) NOT NULL,
  created_at bigint(20) unsigned NOT NULL,
  last_scan_at bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (id),
  KEY site_key (site_key),
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

		// Recorded only once every table is there and upgraded, so that a failed dbDelta() is tried again.
		foreach ( array( $sites, $scans, $limits, $vulns ) as $table ) {
			if ( ! self::table_exists( $table ) ) {
				return;
			}
		}
		if ( false !== self::has_unique_site_key() ) {
			return;
		}
		update_option( self::VERSION_OPTION, self::VERSION, true );
	}

	/**
	 * Whether a table exists.
	 *
	 * @param string $table Table name.
	 * @return bool
	 */
	private static function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks the schema.
	}

	/**
	 * Whether the sites table still has the unique site_key of version 1.
	 *
	 * A failed query returns null just as "no such index" does, so it is
	 * reported as unknown and the upgrade is not taken as done.
	 *
	 * @return bool|null Null when it cannot be told.
	 */
	private static function has_unique_site_key() {
		global $wpdb;

		$sites = self::sites_table();
		if ( ! self::table_exists( $sites ) ) {
			return '' === $wpdb->last_error ? false : null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Checks the schema of this plugin's own table.
		$index = $wpdb->get_row( "SHOW INDEX FROM {$sites} WHERE Key_name = 'site_key' AND Non_unique = 0" );
		return '' === $wpdb->last_error ? null !== $index : null;
	}

	/**
	 * Uninstall: drops the tables of the free security check and forgets their version.
	 *
	 * The scans hold site URLs, the data sites sent and the reports, which are
	 * kept only for the retention period: they must not outlive the plugin.
	 * Called from uninstall.php, which cannot be loaded in the tests.
	 */
	public static function uninstall() {
		global $wpdb;

		wp_unschedule_hook( Artws_Sec_Site_Store::CLEANUP_HOOK );
		foreach ( array( self::sites_table(), self::scans_table(), self::limits_table(), self::vulns_table() ) as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Drops this plugin's own tables.
		}
		delete_option( self::VERSION_OPTION );
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
