<?php
/**
 * Vulnerability records by software, for matching a scan's software list.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text messages, escaped where the settings page shows them.

/**
 * Artws_Sec_Vuln_Index
 *
 * The published .json.gz files hold tens of thousands of records; decoding
 * them for every scan would take hundreds of megabytes. So the feed update
 * also writes each software's records as one row, keyed by the feed version
 * and a hash of the type and slug, and a scan reads only the rows of the
 * software it lists.
 *
 * The feed update writes a generation after it has published the feed, and
 * only then records it as the state's index_version, so a scan always finds
 * the rows of the version it read. It keeps the generation it replaced (a
 * scan that read the previous state a moment before still finds its rows)
 * and deletes older ones.
 */
class Artws_Sec_Vuln_Index {

	/**
	 * Largest INSERT, in bytes of escaped row values; a larger row is sent on its own.
	 *
	 * Well below the 4 MB max_allowed_packet of MySQL 5.7.
	 */
	const BATCH_BYTES = 1048576;

	/**
	 * Keys per SELECT.
	 */
	const LOOKUP_CHUNK = 200;

	/**
	 * Returns the key of a software's row.
	 *
	 * @param string $type plugin, theme or core.
	 * @param string $slug Slug.
	 * @return string
	 */
	public static function lookup_key( $type, $slug ) {
		return hash( 'sha256', $type . '/' . $slug );
	}

	/**
	 * Writes one generation of the index.
	 *
	 * @param array  $vulnerabilities Type => slug => feed records as JSON text (Artws_Sec_Feed_Builder).
	 * @param string $version         Feed version the generation belongs to.
	 * @throws Artws_Sec_Feed_Exception When a row cannot be written.
	 */
	public static function write( array $vulnerabilities, $version ) {
		global $wpdb;

		// A run that failed earlier with the same version may have left part of it.
		self::delete_version( $version );

		$rows  = array();
		$bytes = 0;
		foreach ( $vulnerabilities as $type => $by_slug ) {
			foreach ( $by_slug as $slug => $records ) {
				$row = $wpdb->prepare( '(%s, %s, %s)', $version, self::lookup_key( (string) $type, (string) $slug ), '[' . implode( ',', $records ) . ']' );
				if ( array() !== $rows && $bytes + strlen( $row ) > self::BATCH_BYTES ) {
					self::insert( $rows );
					$rows  = array();
					$bytes = 0;
				}
				$rows[] = $row;
				$bytes += strlen( $row );
			}
		}
		if ( array() !== $rows ) {
			self::insert( $rows );
		}
	}

	/**
	 * Deletes every generation except the ones given.
	 *
	 * @param string[] $keep Feed versions to keep (none to delete them all).
	 */
	public static function delete_other_versions( array $keep ) {
		global $wpdb;

		$table = Artws_Sec_Db::vulns_table();
		if ( array() === $keep ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table; the name is not user input.
			$wpdb->query( "DELETE FROM {$table}" );
			return;
		}
		$placeholders = implode( ',', array_fill( 0, count( $keep ), '%s' ) );
		$sql          = "DELETE FROM {$table} WHERE feed_version NOT IN ({$placeholders})";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom table; $sql has one %s per version.
		$wpdb->query( $wpdb->prepare( $sql, array_values( $keep ) ) );
	}

	/**
	 * Returns the records of the listed software in one generation.
	 *
	 * @param string   $version Feed version.
	 * @param string[] $keys    Keys from lookup_key().
	 * @return array<string, array> Key => feed records (decoded); software without records is left out.
	 * @throws Artws_Sec_Feed_Exception When the index cannot be read.
	 */
	public static function find( $version, array $keys ) {
		global $wpdb;

		$table = Artws_Sec_Db::vulns_table();
		$found = array();
		foreach ( array_chunk( array_values( array_unique( $keys ) ), self::LOOKUP_CHUNK ) as $chunk ) {
			$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%s' ) );
			$sql          = "SELECT lookup_key, records FROM {$table} WHERE feed_version = %s AND lookup_key IN ({$placeholders})";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Custom table; $sql has one %s per key.
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( $version ), $chunk ) ) );
			// A failed query returns no rows, which must not read as "no vulnerabilities".
			if ( ! is_array( $rows ) || '' !== $wpdb->last_error ) {
				throw new Artws_Sec_Feed_Exception( 'The vulnerability index could not be read.' );
			}
			foreach ( $rows as $row ) {
				$records = json_decode( $row->records, true );
				if ( is_array( $records ) ) {
					$found[ $row->lookup_key ] = $records;
				}
			}
		}
		return $found;
	}

	/**
	 * Whether a version falls in one of a record's affected ranges.
	 *
	 * The contract's rule (tests/fixtures/security-feed/README.md), the same
	 * as the client's JP4WC_Security_Scanner::is_affected(). An empty version
	 * is never affected: version_compare() would put it below every bound.
	 *
	 * @param string $version Installed version.
	 * @param array  $ranges  The record's affected_versions.
	 * @return bool
	 */
	public static function is_affected( $version, array $ranges ) {
		if ( ! is_string( $version ) || '' === $version ) {
			return false;
		}
		foreach ( $ranges as $range ) {
			if ( ! is_array( $range ) || ! isset( $range['from_version'], $range['to_version'] ) ) {
				continue;
			}
			$from = (string) $range['from_version'];
			$to   = (string) $range['to_version'];

			$above_from = '*' === $from
				|| version_compare( $from, $version, '<' )
				|| ( ! empty( $range['from_inclusive'] ) && version_compare( $from, $version, '==' ) );
			$below_to   = '*' === $to
				|| version_compare( $version, $to, '<' )
				|| ( ! empty( $range['to_inclusive'] ) && version_compare( $version, $to, '==' ) );

			if ( $above_from && $below_to ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deletes one generation.
	 *
	 * @param string $version Feed version.
	 */
	public static function delete_version( $version ) {
		global $wpdb;

		$wpdb->delete( Artws_Sec_Db::vulns_table(), array( 'feed_version' => $version ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table.
	}

	/**
	 * Inserts prepared rows.
	 *
	 * @param string[] $rows Row values prepared by write().
	 * @throws Artws_Sec_Feed_Exception When the insert fails.
	 */
	private static function insert( array $rows ) {
		global $wpdb;

		$table = Artws_Sec_Db::vulns_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Each row was prepared in write().
		if ( false === $wpdb->query( "INSERT INTO {$table} (feed_version, lookup_key, records) VALUES " . implode( ',', $rows ) ) ) {
			throw new Artws_Sec_Feed_Exception( 'The vulnerability index could not be written: ' . $wpdb->last_error );
		}
	}
}
