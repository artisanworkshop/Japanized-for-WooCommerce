<?php
/**
 * Rate limits of the free security check.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Rate_Limiter
 *
 * A fixed window per key: the first hit opens a window of $window seconds
 * that allows $max hits. Keys are HMACs of what they count (a site, a client
 * address), so the table holds no address in the clear.
 *
 * A hit is taken with one INSERT … ON DUPLICATE KEY UPDATE whose number of
 * affected rows tells whether it counted (1 inserted, 2 updated, 0 when the
 * window was full and the row stayed as it was), so two requests at once
 * cannot both take the last hit.
 */
class Artws_Sec_Rate_Limiter {

	/**
	 * Returns the key of something to count.
	 *
	 * @param string $scope What is counted ("site", "ip").
	 * @param string $value The site key or the client address.
	 * @return string
	 */
	public static function key( $scope, $value ) {
		return hash_hmac( 'sha256', $scope . '|' . $value, wp_salt( 'auth' ) );
	}

	/**
	 * Takes a hit from every limit, or none when one of them is full.
	 *
	 * Full limits are looked for first, so that a request refused by one
	 * limit does not use up another. Two requests at once can still pass that
	 * look and then race for the last hit: the loser is refused, and a hit it
	 * already took from an earlier limit stays used.
	 *
	 * @param array<int, array{key: string, max: int, window: int}> $limits Limits to take a hit from.
	 * @param int                                                   $now    Unix time.
	 * @return int 0 when the request may go ahead, otherwise the Unix time from which it may.
	 */
	public static function hit( array $limits, $now ) {
		$retry_at = self::retry_at( $limits, $now );
		if ( $retry_at > 0 ) {
			return $retry_at;
		}

		global $wpdb;
		$table = Artws_Sec_Db::limits_table();
		foreach ( $limits as $limit ) {
			$expired = $now - $limit['window'];
			// The hits are updated before window_start, so both read the row's old window_start.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Atomic counter in a custom table.
			$affected = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$table} ( limit_key, window_start, hits ) VALUES ( %s, %d, 1 )
					ON DUPLICATE KEY UPDATE
						hits = IF( window_start <= %d, 1, IF( hits < %d, hits + 1, hits ) ),
						window_start = IF( window_start <= %d, %d, window_start )",
					$limit['key'],
					$now,
					$expired,
					$limit['max'],
					$expired,
					$now
				)
			);
			// phpcs:enable
			if ( 0 === $affected ) {
				return max( $now + 1, self::retry_at( array( $limit ), $now ) );
			}
		}
		return 0;
	}

	/**
	 * Deletes the windows that have ended.
	 *
	 * @param int $now        Unix time.
	 * @param int $max_window Longest window in use, in seconds.
	 */
	public static function cleanup( $now, $max_window ) {
		global $wpdb;

		$table = Artws_Sec_Db::limits_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE window_start <= %d", $now - $max_window ) );
	}

	/**
	 * Returns the address the rate limit counts the request under.
	 *
	 * REMOTE_ADDR, or the $_SERVER key named by ARTWS_SEC_CLIENT_IP_HEADER
	 * when a proxy or CDN sits in front of the site (for example
	 * HTTP_CF_CONNECTING_IP). From a list, the last address is taken: the one
	 * the nearest proxy added, not one the client wrote. IPv6 addresses are
	 * counted per /64, which one host can usually choose from.
	 *
	 * @return string The address, or '' when there is none.
	 */
	public static function client_address() {
		$header = defined( 'ARTWS_SEC_CLIENT_IP_HEADER' ) && is_string( ARTWS_SEC_CLIENT_IP_HEADER ) && '' !== ARTWS_SEC_CLIENT_IP_HEADER ? ARTWS_SEC_CLIENT_IP_HEADER : 'REMOTE_ADDR';
		$value  = isset( $_SERVER[ $header ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) : '';
		$parts  = explode( ',', $value );
		$ip     = trim( (string) end( $parts ) );

		/**
		 * Filters the client address the rate limit counts.
		 *
		 * @param string $ip The address read from $_SERVER.
		 */
		$ip = apply_filters( 'artws_sec_client_ip', $ip );
		if ( ! is_string( $ip ) || false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '';
		}

		$packed = inet_pton( $ip );
		if ( false !== $packed && 16 === strlen( $packed ) ) {
			return inet_ntop( substr( $packed, 0, 8 ) . str_repeat( "\0", 8 ) ) . '/64';
		}
		return $ip;
	}

	/**
	 * Returns when the first full limit opens again, or 0 when none is full.
	 *
	 * @param array $limits Limits as for hit().
	 * @param int   $now    Unix time.
	 * @return int
	 */
	private static function retry_at( array $limits, $now ) {
		global $wpdb;

		$by_key = array();
		foreach ( $limits as $limit ) {
			$by_key[ $limit['key'] ] = $limit;
		}
		if ( array() === $by_key ) {
			return 0;
		}

		$table        = Artws_Sec_Db::limits_table();
		$placeholders = implode( ',', array_fill( 0, count( $by_key ), '%s' ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Custom table; one %s per key.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT limit_key, window_start, hits FROM {$table} WHERE limit_key IN ({$placeholders})", array_keys( $by_key ) ) );
		// phpcs:enable

		$retry_at = 0;
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$limit = $by_key[ $row->limit_key ] ?? null;
			if ( null === $limit ) {
				continue;
			}
			$opens_at = (int) $row->window_start + $limit['window'];
			if ( $opens_at > $now && (int) $row->hits >= $limit['max'] ) {
				$retry_at = max( $retry_at, $opens_at );
			}
		}
		return $retry_at;
	}
}
