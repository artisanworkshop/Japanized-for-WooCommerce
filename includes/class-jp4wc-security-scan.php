<?php
/**
 * Free security check of wc.artws.info.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-jp4wc-security-feed.php';
require_once __DIR__ . '/class-jp4wc-security-scanner.php';

/**
 * JP4WC_Security_Scan
 *
 * Sends the site's information to wc.artws.info (artws-sec/v1) only when a
 * store manager agrees on the Security tab and clicks the button, and keeps
 * the report that comes back. Nothing is sent from WP-Cron. What is sent is
 * defined by tests/fixtures/security-feed/schema/scan-request.schema.json,
 * and payload() is the only place that builds it: the consent screen shows
 * its result, and the request sends the same.
 *
 * wc.artws.info issues a token for each registration (site_token) in the
 * response of the first check. It is kept with the site URL it was issued
 * for, sent as Authorization: Bearer, and never shown, logged or returned by
 * this site's REST API. A copy of the database on another URL (a staging
 * site) does not use it: it would read or delete the production site's data.
 */
class JP4WC_Security_Scan {

	/**
	 * Version of the consent text, which wc.artws.info must know.
	 */
	const CONSENT_VERSION = '2026-10';

	/**
	 * Base URL of the API, with a trailing slash.
	 */
	const DEFAULT_API_URL = 'https://wc.artws.info/wp-json/artws-sec/v1/';

	/**
	 * Terms of use of the check.
	 */
	const TERMS_URL = 'https://wc.artws.info/security-assessment-term/';

	/**
	 * Privacy policy of wc.artws.info.
	 */
	const PRIVACY_URL = 'https://wc.artws.info/privacy-policy/';

	/**
	 * Option: the site token and the site URL it was issued for.
	 */
	const TOKEN_OPTION = 'jp4wc_security_site_token';

	/**
	 * Option: the consent (version, time, whether the bands may be sent).
	 */
	const CONSENT_OPTION = 'jp4wc_security_scan_consent';

	/**
	 * Option: the last report, the site URL it is for and when it came.
	 */
	const REPORT_OPTION = 'jp4wc_security_scan_report';

	/**
	 * Option: held while a check or a deletion talks to wc.artws.info.
	 */
	const LOCK_OPTION = 'jp4wc_security_scan_lock';

	/**
	 * Seconds after which a lock is taken as left by a request that died:
	 * longer than two requests of REQUEST_TIMEOUT.
	 */
	const LOCK_TTL = 120;

	/**
	 * Seconds a request to wc.artws.info may take.
	 */
	const REQUEST_TIMEOUT = 30;

	/**
	 * Seconds the deletion sent on uninstall may take.
	 */
	const UNINSTALL_TIMEOUT = 5;

	/**
	 * Format of a site token.
	 */
	const TOKEN_PATTERN = '/^sit_[A-Za-z0-9_-]{43,128}$/D';

	/**
	 * Format of a report token.
	 */
	const REPORT_TOKEN_PATTERN = '/^scn_[A-Za-z0-9_-]{32,128}$/D';

	/**
	 * Revenue bands of the last 12 months in yen: the highest amount of each band.
	 * B0 is no revenue; more than the last one is B5.
	 */
	const REVENUE_BANDS = array(
		'B1' => 1000000,
		'B2' => 10000000,
		'B3' => 50000000,
		'B4' => 100000000,
	);

	/**
	 * Order bands of the last 12 months: the highest count of each band.
	 * O0 is no order; more than the last one is O4.
	 */
	const ORDER_BANDS = array(
		'O1' => 100,
		'O2' => 1000,
		'O3' => 10000,
	);

	/**
	 * Most entries of software and local_findings the contract accepts.
	 */
	const MAX_SOFTWARE = 1000;
	const MAX_FINDINGS = 100;

	/**
	 * Severities and requirements a report may use.
	 */
	const SEVERITIES   = array( 'critical', 'high', 'medium', 'low' );
	const REQUIREMENTS = array( 'vuln', 'admin', 'data', 'malware' );

	/**
	 * Returns the base URL of the API, with a trailing slash.
	 *
	 * @return string
	 */
	public static function api_url() {
		/**
		 * Filters the URL of the free security check API.
		 *
		 * @since 3.0.0
		 * @param string $url Base URL. Default https://wc.artws.info/wp-json/artws-sec/v1/.
		 */
		$url = apply_filters( 'jp4wc_security_scan_api_url', self::DEFAULT_API_URL );
		return trailingslashit( is_string( $url ) && '' !== $url ? $url : self::DEFAULT_API_URL );
	}

	/**
	 * Whether the check can be run: it sends the results of the local checks,
	 * so the security check must be on and have run once.
	 *
	 * @return bool
	 */
	public static function can_scan() {
		return JP4WC_Security_Feed::is_enabled() && null !== JP4WC_Security_Feed::get_checks();
	}

	/**
	 * Returns what is sent: the body of POST /scan.
	 *
	 * The consent screen shows this, and scan() sends it.
	 *
	 * @param bool $include_bands Whether the revenue and order bands are added
	 *                            (only when the store currency is yen, and
	 *                            only when they can be counted).
	 * @return array
	 */
	public static function payload( $include_bands = false ) {
		$payload = array(
			'schema_version'  => 1,
			'site_url'        => home_url(),
			'wp_ver'          => (string) get_bloginfo( 'version' ),
			'wc_ver'          => defined( 'WC_VERSION' ) ? (string) WC_VERSION : '',
			'php_ver'         => PHP_VERSION,
			'jp4wc_ver'       => defined( 'JP4WC_VERSION' ) ? (string) JP4WC_VERSION : '',
			'locale'          => self::locale(),
			'software'        => self::software(),
			'local_findings'  => self::local_findings(),
			'consent_version' => self::CONSENT_VERSION,
		);
		if ( $include_bands ) {
			$bands = self::bands();
			if ( null !== $bands ) {
				// Both or neither: the contract refuses one without the other.
				$payload['revenue_band'] = $bands['revenue_band'];
				$payload['order_band']   = $bands['order_band'];
			}
		}
		return $payload;
	}

	/**
	 * Returns the locale the report is written for: that of the store manager
	 * who runs the check.
	 *
	 * @return string
	 */
	private static function locale() {
		$locale = (string) get_user_locale();
		return strlen( $locale ) <= 20 && 1 === preg_match( '/^[A-Za-z]{2,3}(_[A-Za-z0-9]+)*$/D', $locale ) ? $locale : 'en_US';
	}

	/**
	 * Returns every installed plugin and theme, active or not.
	 *
	 * @return array<int, array{type: string, slug: string, version: string, active: bool}>
	 */
	private static function software() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$software = array();
		foreach ( get_plugins() as $file => $data ) {
			$file = (string) $file;
			$slug = false === strpos( $file, '/' ) ? basename( $file, '.php' ) : (string) strtok( $file, '/' );
			if ( ! self::is_slug( $slug ) ) {
				continue;
			}
			$software[] = array(
				'type'    => 'plugin',
				'slug'    => $slug,
				'version' => self::software_version( isset( $data['Version'] ) ? $data['Version'] : '' ),
				'active'  => is_plugin_active( $file ),
			);
		}

		$active = array( get_stylesheet(), get_template() );
		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			$slug = (string) $stylesheet;
			if ( ! self::is_slug( $slug ) ) {
				continue;
			}
			$software[] = array(
				'type'    => 'theme',
				'slug'    => $slug,
				'version' => self::software_version( $theme->get( 'Version' ) ),
				'active'  => in_array( $slug, $active, true ),
			);
		}

		return array_slice( $software, 0, self::MAX_SOFTWARE );
	}

	/**
	 * Whether a value is a slug as the contract defines it.
	 *
	 * @param string $slug Value.
	 * @return bool
	 */
	private static function is_slug( $slug ) {
		return self::is_text( $slug, 1, 200 ) && 1 === preg_match( '#^[^/\\\\\x00-\x1f]+$#D', $slug );
	}

	/**
	 * Returns a Version header as the contract sends it: as written, or empty
	 * when it is longer than 64 characters or holds a control character.
	 *
	 * @param mixed $version Version header.
	 * @return string
	 */
	private static function software_version( $version ) {
		$version = is_string( $version ) ? $version : '';
		return self::is_text( $version, 0, 64 ) ? $version : '';
	}

	/**
	 * Whether a value is UTF-8 text of a length (in code points) within the
	 * bounds and without control characters (U+0000 to U+001F, U+007F to U+009F).
	 *
	 * @param mixed $value Value.
	 * @param int   $min   Shortest length.
	 * @param int   $max   Longest length.
	 * @return bool
	 */
	private static function is_text( $value, $min, $max ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '//u', $value ) || 1 === preg_match( '/[\x{00}-\x{1f}\x{7f}-\x{9f}]/u', $value ) ) {
			return false;
		}
		$length = mb_strlen( $value, 'UTF-8' );
		return $length >= $min && $length <= $max;
	}

	/**
	 * Returns the status of each local check: never the values behind it.
	 *
	 * @return array<int, array{check_id: string, status: string}>
	 */
	private static function local_findings() {
		$result = JP4WC_Security_Feed::get_checks();
		if ( null === $result ) {
			return array();
		}
		$findings = array();
		foreach ( $result['checks'] as $check ) {
			if ( ! is_array( $check ) || ! isset( $check['check_id'], $check['status'] ) || ! is_string( $check['check_id'] ) ) {
				continue;
			}
			if ( 1 !== preg_match( '/^[VADM]-[0-9]{2}$/D', $check['check_id'] ) || ! in_array( $check['status'], array( 'pass', 'fail', 'unknown' ), true ) ) {
				continue;
			}
			$findings[] = array(
				'check_id' => $check['check_id'],
				'status'   => $check['status'],
			);
		}
		return array_slice( $findings, 0, self::MAX_FINDINGS );
	}

	/**
	 * Whether the bands can be offered: only when the store currency is yen,
	 * as the bands are in yen and no rate is applied.
	 *
	 * @return bool
	 */
	public static function bands_available() {
		return function_exists( 'get_woocommerce_currency' ) && 'JPY' === get_woocommerce_currency();
	}

	/**
	 * Returns the revenue and order bands of the last 12 months.
	 *
	 * @return array{revenue_band: string, order_band: string}|null Null when
	 *         the store currency is not yen or the orders cannot be counted.
	 */
	public static function bands() {
		if ( ! self::bands_available() ) {
			return null;
		}
		$totals = self::order_totals();
		if ( null === $totals ) {
			return null;
		}
		return array(
			'revenue_band' => self::revenue_band( $totals['revenue'] ),
			'order_band'   => self::order_band( $totals['orders'] ),
		);
	}

	/**
	 * Returns the band of a revenue in yen.
	 *
	 * @param float|int|string $amount Revenue.
	 * @return string B0 to B5.
	 */
	public static function revenue_band( $amount ) {
		$amount = (float) $amount;
		if ( $amount <= 0 ) {
			return 'B0';
		}
		foreach ( self::REVENUE_BANDS as $band => $highest ) {
			if ( $amount <= $highest ) {
				return $band;
			}
		}
		return 'B5';
	}

	/**
	 * Returns the band of a number of orders.
	 *
	 * @param int|string $count Number of orders.
	 * @return string O0 to O4.
	 */
	public static function order_band( $count ) {
		$count = (int) $count;
		if ( $count <= 0 ) {
			return 'O0';
		}
		foreach ( self::ORDER_BANDS as $band => $highest ) {
			if ( $count <= $highest ) {
				return $band;
			}
		}
		return 'O4';
	}

	/**
	 * Counts the orders of the last 365 days in a paid status and their total,
	 * less what was refunded on them, with two queries: no order is loaded.
	 *
	 * The totals are summed as recorded. A store whose currency is yen records
	 * its orders in yen; an order in another currency (a multi-currency
	 * plugin) counts at face value, which can only lower the band.
	 *
	 * @return array{orders: int, revenue: float}|null Null when a query failed.
	 */
	public static function order_totals() {
		global $wpdb;

		$statuses = array();
		foreach ( (array) wc_get_is_paid_statuses() as $status ) {
			if ( is_string( $status ) && '' !== $status ) {
				$statuses[] = 'wc-' . $status;
			}
		}
		if ( array() === $statuses ) {
			return array(
				'orders'  => 0,
				'revenue' => 0.0,
			);
		}
		$in    = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		$args  = array_merge( $statuses, array( gmdate( 'Y-m-d H:i:s', time() - 365 * DAY_IN_SECONDS ) ) );
		$money = 'DECIMAL(30,8)';

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$table = Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Sums without loading the orders; the table name comes from WooCommerce and the IN list is placeholders.
			$orders = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS revenue FROM {$table} WHERE type = 'shop_order' AND status IN ({$in}) AND date_created_gmt >= %s",
					$args
				)
			);
			$failed = '' !== $wpdb->last_error;
			// A refund's total is the refunded amount as a negative number.
			$refunds = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(r.total_amount), 0) FROM {$table} r INNER JOIN {$table} o ON o.id = r.parent_order_id WHERE r.type = 'shop_order_refund' AND o.type = 'shop_order' AND o.status IN ({$in}) AND o.date_created_gmt >= %s",
					$args
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		} else {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Sums without loading the orders; the IN list is placeholders.
			$orders  = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT p.ID) AS orders, COALESCE(SUM(CAST(m.meta_value AS {$money})), 0) AS revenue FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_order_total' WHERE p.post_type = 'shop_order' AND p.post_status IN ({$in}) AND p.post_date_gmt >= %s",
					$args
				)
			);
			$failed  = '' !== $wpdb->last_error;
			$refunds = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(CAST(m.meta_value AS {$money})), 0) FROM {$wpdb->posts} r INNER JOIN {$wpdb->posts} o ON o.ID = r.post_parent INNER JOIN {$wpdb->postmeta} m ON m.post_id = r.ID AND m.meta_key = '_order_total' WHERE r.post_type = 'shop_order_refund' AND o.post_type = 'shop_order' AND o.post_status IN ({$in}) AND o.post_date_gmt >= %s",
					$args
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		}

		// A failed query reads as no orders: that would send B0 / O0.
		if ( $failed || '' !== $wpdb->last_error || null === $orders || null === $refunds ) {
			return null;
		}
		return array(
			'orders'  => (int) $orders->orders,
			'revenue' => (float) $orders->revenue + (float) $refunds,
		);
	}

	/**
	 * Whether two site URLs are the same site, as wc.artws.info decides it
	 * (Artws_Sec_Site_Store::site_key()): the scheme, the case of the host
	 * and a trailing slash make no difference.
	 *
	 * @param string $a Site URL.
	 * @param string $b Site URL.
	 * @return bool
	 */
	public static function same_site( $a, $b ) {
		return self::site_key( (string) $a ) === self::site_key( (string) $b );
	}

	/**
	 * Returns a site URL without what same_site() ignores.
	 *
	 * @param string $site_url Site URL.
	 * @return string
	 */
	private static function site_key( $site_url ) {
		$parts = wp_parse_url( $site_url );
		if ( ! is_array( $parts ) || ! isset( $parts['host'] ) ) {
			$rest = strstr( $site_url, '://' );
			return strtolower( untrailingslashit( false === $rest ? $site_url : substr( $rest, 3 ) ) );
		}
		return strtolower( $parts['host'] )
			. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' )
			. untrailingslashit( isset( $parts['path'] ) ? $parts['path'] : '' )
			. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
	}

	/**
	 * Returns the stored site token and the site URL it was issued for.
	 *
	 * @return array{token: string, site_url: string}|null
	 */
	private static function stored_token() {
		$stored = get_option( self::TOKEN_OPTION, null );
		if ( ! is_array( $stored ) || ! isset( $stored['token'], $stored['site_url'] ) || ! is_string( $stored['token'] ) || ! is_string( $stored['site_url'] ) ) {
			return null;
		}
		if ( 1 !== preg_match( self::TOKEN_PATTERN, $stored['token'] ) ) {
			return null;
		}
		return array(
			'token'    => $stored['token'],
			'site_url' => $stored['site_url'],
		);
	}

	/**
	 * Returns the site token when it was issued to this site's URL.
	 *
	 * @return string|null
	 */
	private static function usable_token() {
		$stored = self::stored_token();
		return null !== $stored && self::same_site( $stored['site_url'], home_url() ) ? $stored['token'] : null;
	}

	/**
	 * Returns the stored consent.
	 *
	 * @return array{version: string, consented_at: int, bands: bool}|null
	 */
	public static function consent() {
		$consent = get_option( self::CONSENT_OPTION, null );
		if ( ! is_array( $consent ) || ! isset( $consent['version'] ) || ! is_string( $consent['version'] ) ) {
			return null;
		}
		return array(
			'version'      => $consent['version'],
			'consented_at' => isset( $consent['consented_at'] ) ? (int) $consent['consented_at'] : 0,
			'bands'        => ! empty( $consent['bands'] ),
		);
	}

	/**
	 * Whether the consent row is in the database, read past the options this
	 * process cached: uninstall in another request may have removed it.
	 *
	 * @return bool True also when the row cannot be read, so that a failed
	 *              read deletes nothing.
	 */
	private static function consent_in_database() {
		global $wpdb;
		// get_row(), not get_var(): get_var() returns null for '' as well as for a missing row.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Must not read the options this process cached.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", self::CONSENT_OPTION ) );
		return null !== $row || '' !== $wpdb->last_error;
	}

	/**
	 * Whether the store manager agreed to the current consent text.
	 *
	 * @return bool
	 */
	public static function has_consent() {
		$consent = self::consent();
		return null !== $consent && self::CONSENT_VERSION === $consent['version'];
	}

	/**
	 * Stores the consent to the current text, and whether the bands may be sent.
	 *
	 * The time is that of the first consent to this version.
	 *
	 * @param bool $bands Whether the bands may be sent.
	 */
	public static function record_consent( $bands ) {
		$consent = self::consent();
		update_option(
			self::CONSENT_OPTION,
			array(
				'version'      => self::CONSENT_VERSION,
				'consented_at' => null !== $consent && self::CONSENT_VERSION === $consent['version'] && $consent['consented_at'] > 0 ? $consent['consented_at'] : time(),
				'bands'        => (bool) $bands,
			),
			false
		);
	}

	/**
	 * Returns the last report, when it is for this site's URL.
	 *
	 * @return array{report: array, scanned_at: int}|null
	 */
	public static function stored_report() {
		$stored = get_option( self::REPORT_OPTION, null );
		if ( ! is_array( $stored ) || ! isset( $stored['report'], $stored['site_url'] ) || ! is_array( $stored['report'] ) || ! is_string( $stored['site_url'] ) ) {
			return null;
		}
		if ( ! self::same_site( $stored['site_url'], home_url() ) ) {
			return null;
		}
		return array(
			'report'     => $stored['report'],
			'scanned_at' => isset( $stored['scanned_at'] ) ? (int) $stored['scanned_at'] : 0,
		);
	}

	/**
	 * Returns what the Security tab shows of the free check. Sends nothing.
	 *
	 * @param bool $with_bands Whether the preview includes the bands.
	 * @return array
	 */
	public static function view( $with_bands = false ) {
		$consent         = self::consent();
		$stored          = self::stored_token();
		$token           = self::usable_token();
		$report          = self::stored_report();
		$bands_available = self::bands_available();
		$payload         = self::payload( $with_bands && $bands_available );

		return array(
			'consent_version' => self::CONSENT_VERSION,
			'consent'         => null === $consent ? null : array(
				'current'      => self::CONSENT_VERSION === $consent['version'],
				'consented_at' => $consent['consented_at'],
				'bands'        => $consent['bands'],
			),
			// The token itself never leaves this class.
			'registered'      => null !== $token,
			'other_site'      => null !== $stored && null === $token,
			'report'          => null === $report ? null : $report['report'],
			'scanned_at'      => null === $report ? 0 : $report['scanned_at'],
			'ready'           => self::can_scan(),
			'enabled'         => JP4WC_Security_Feed::is_enabled(),
			'bands_available' => $bands_available,
			'bands_error'     => $with_bands && $bands_available && ! isset( $payload['revenue_band'] ),
			'payload'         => $payload,
			'host'            => (string) wp_parse_url( self::api_url(), PHP_URL_HOST ),
			'terms_url'       => self::TERMS_URL,
			'privacy_url'     => self::PRIVACY_URL,
		);
	}

	/**
	 * Runs the check the store manager asked for on the Security tab.
	 *
	 * The consent is stored before anything is sent, and only when the check
	 * can run. A store manager who agreed to the current text runs it again
	 * without ticking the box; whether the bands are sent is asked each time.
	 *
	 * @param bool $consent       Whether the consent box was ticked.
	 * @param bool $include_bands Whether the bands may be sent.
	 * @return array{reregistered: bool}|WP_Error
	 */
	public static function run( $consent, $include_bands ) {
		if ( ! $consent && ! self::has_consent() ) {
			return new WP_Error(
				'jp4wc_security_scan_consent_required',
				__( 'Agree to the terms of use and the privacy policy to run the check.', 'woocommerce-for-japan' ),
				array( 'status' => 400 )
			);
		}
		if ( ! self::can_scan() ) {
			return self::not_ready_error();
		}
		$include_bands = $include_bands && self::bands_available();
		self::record_consent( $include_bands );
		return self::scan( $include_bands );
	}

	/**
	 * Runs the check: sends payload() to wc.artws.info and stores the report.
	 *
	 * The stored token is sent when it was issued to this site's URL. When
	 * wc.artws.info refuses it (401: deleted, expired, or the site moved),
	 * it is thrown away with the report and the same request is sent once
	 * more without it, which registers the site again. The old token is never
	 * used to delete the old registration: a staging copy holds the production
	 * site's token. The old registration's data expires on the server.
	 *
	 * @param bool $include_bands Whether the bands are sent.
	 * @return array{reregistered: bool}|WP_Error reregistered is true when an
	 *         earlier registration can no longer be used. When a refused token
	 *         was thrown away and the request that registers again fails, the
	 *         error's data has registration_reset: the earlier report is gone,
	 *         and the next check registers the site.
	 */
	private static function scan( $include_bands ) {
		if ( ! self::can_scan() ) {
			return self::not_ready_error();
		}
		if ( ! self::lock() ) {
			return self::busy_error();
		}
		try {
			$payload = self::payload( $include_bands );
			$invalid = self::payload_error( $payload );
			if ( null !== $invalid ) {
				return $invalid;
			}

			$token = self::usable_token();
			// A token of another URL stays until this site registers: it belongs to that site.
			$reregistered = null !== self::stored_token() && null === $token;
			$reset        = false;
			$response     = self::request( 'POST', 'scan', $token, $payload );
			if ( null !== $token && 401 === self::status( $response ) ) {
				self::forget_registration();
				$token        = null;
				$reregistered = true;
				$reset        = true;
				$response     = self::request( 'POST', 'scan', null, $payload );
			}

			$result = self::parse_scan_response( $response, null === $token );
			if ( is_wp_error( $result ) ) {
				if ( $reset ) {
					$result->add_data( array_merge( (array) $result->get_error_data(), array( 'registration_reset' => true ) ) );
				}
				return $result;
			}

			if ( null === $token ) {
				update_option(
					self::TOKEN_OPTION,
					array(
						'token'    => $result['site_token'],
						'site_url' => $payload['site_url'],
					),
					false
				);
			}
			update_option(
				self::REPORT_OPTION,
				array(
					'report'     => $result['report'],
					'site_url'   => $payload['site_url'],
					'scanned_at' => time(),
				),
				false
			);

			// Uninstall does not wait for the lock: when it removed the consent while
			// this request ran, what was just written goes too, and the registration is
			// deleted, as uninstall promises.
			if ( ! self::consent_in_database() ) {
				self::forget_registration();
				self::request( 'DELETE', 'site', null === $token ? $result['site_token'] : $token, null, self::UNINSTALL_TIMEOUT );
				return new WP_Error(
					'jp4wc_security_scan_cancelled',
					__( 'The check data was deleted while the check ran, so its result was not kept.', 'woocommerce-for-japan' ),
					array( 'status' => 409 )
				);
			}
			return array( 'reregistered' => $reregistered );
		} finally {
			self::unlock();
		}
	}

	/**
	 * Deletes this site's data on wc.artws.info (DELETE /site), then the
	 * consent, the token and the report kept here.
	 *
	 * A 401 means the registration is gone already. A token issued to another
	 * URL is not sent (it belongs to the site this database was copied from):
	 * only what is kept here is deleted.
	 *
	 * @return true|WP_Error
	 */
	public static function delete_site() {
		if ( ! self::lock() ) {
			return self::busy_error();
		}
		try {
			$token = self::usable_token();
			if ( null !== $token ) {
				$response = self::request( 'DELETE', 'site', $token );
				if ( is_wp_error( $response ) ) {
					return self::unreachable_error();
				}
				$status = self::status( $response );
				if ( 401 !== $status && ( $status < 200 || $status >= 300 ) ) {
					return self::status_error( $status, $response );
				}
			}
			self::forget_all();
			return true;
		} finally {
			self::unlock();
		}
	}

	/**
	 * Asks wc.artws.info to delete this site's data and removes what the free
	 * check stored, on every site of a network (uninstall.php).
	 *
	 * The terms of use promise the deletion request on uninstall. A request
	 * that does not arrive leaves the data to expire on the server.
	 */
	public static function uninstall() {
		if ( ! is_multisite() ) {
			self::uninstall_site();
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
				self::uninstall_site();
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Sends the deletion request of the current site, when it has a token for
	 * its URL, and removes the options.
	 */
	private static function uninstall_site() {
		$token = self::usable_token();
		if ( null !== $token ) {
			self::request( 'DELETE', 'site', $token, null, self::UNINSTALL_TIMEOUT );
		}
		self::forget_all();
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * Removes the token and the report of a registration that cannot be used.
	 */
	private static function forget_registration() {
		delete_option( self::TOKEN_OPTION );
		delete_option( self::REPORT_OPTION );
	}

	/**
	 * Removes the consent, the token and the report.
	 */
	private static function forget_all() {
		self::forget_registration();
		delete_option( self::CONSENT_OPTION );
	}

	/**
	 * Sends a request to the API.
	 *
	 * @param string      $method  HTTP method.
	 * @param string      $path    Path under the base URL.
	 * @param string|null $token   Site token, or null to send none.
	 * @param array|null  $body    JSON body.
	 * @param int         $timeout Seconds.
	 * @return array|WP_Error
	 */
	private static function request( $method, $path, $token, $body = null, $timeout = self::REQUEST_TIMEOUT ) {
		$args = array(
			'method'      => $method,
			'timeout'     => $timeout,
			// The API answers itself: a redirect would only send the request elsewhere.
			'redirection' => 0,
			// WordPress's default User-Agent holds the site URL.
			'user-agent'  => JP4WC_Security_Feed::user_agent(),
			'headers'     => array( 'Accept' => 'application/json' ),
		);
		if ( null !== $token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}
		if ( null !== $body ) {
			$json = wp_json_encode( $body );
			if ( false === $json ) {
				return new WP_Error( 'jp4wc_security_scan_encode', 'The request could not be encoded.' );
			}
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = $json;
		}
		return wp_remote_request( self::api_url() . $path, $args );
	}

	/**
	 * Returns the HTTP status of a response, or 0 for a request that failed.
	 *
	 * @param array|WP_Error $response Response.
	 * @return int
	 */
	private static function status( $response ) {
		return is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Returns an error when a value the contract requires cannot be sent.
	 *
	 * @param array $payload What would be sent.
	 * @return WP_Error|null
	 */
	private static function payload_error( array $payload ) {
		$site_url = $payload['site_url'];
		$valid    = is_string( $site_url ) && strlen( $site_url ) <= 2048 && 1 === preg_match( '~^https?://[^\s/?#]+\S*$~D', $site_url );
		foreach ( array( 'wp_ver', 'wc_ver', 'php_ver', 'jp4wc_ver' ) as $key ) {
			$valid = $valid && self::is_text( $payload[ $key ], 1, 64 );
		}
		if ( $valid ) {
			return null;
		}
		return new WP_Error(
			'jp4wc_security_scan_unsupported',
			__( 'This site cannot be checked: its address or a version of WordPress, WooCommerce, PHP or this plugin cannot be sent as the check requires.', 'woocommerce-for-japan' ),
			array( 'status' => 409 )
		);
	}

	/**
	 * Reads the response of POST /scan.
	 *
	 * @param array|WP_Error $response     Response.
	 * @param bool           $expect_token Whether the request registered the site.
	 * @return array{report: array, site_token: string}|WP_Error
	 */
	private static function parse_scan_response( $response, $expect_token ) {
		if ( is_wp_error( $response ) ) {
			return self::unreachable_error();
		}
		$status = self::status( $response );
		if ( 200 !== $status ) {
			return self::status_error( $status, $response );
		}

		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		$report = is_array( $data ) && 1 === ( $data['schema_version'] ?? null ) ? self::normalize_report( $data['report'] ?? null ) : null;
		$token  = is_array( $data ) && isset( $data['site_token'] ) && is_string( $data['site_token'] ) && 1 === preg_match( self::TOKEN_PATTERN, $data['site_token'] ) ? $data['site_token'] : '';
		if ( null === $report || ( $expect_token && '' === $token ) ) {
			return new WP_Error(
				'jp4wc_security_scan_invalid_response',
				__( 'wc.artws.info sent a response that could not be read. Try again later.', 'woocommerce-for-japan' ),
				array( 'status' => 502 )
			);
		}
		return array(
			'report'     => $report,
			'site_token' => $token,
		);
	}

	/**
	 * Returns the parts of a report this plugin shows, or null when it is not one.
	 *
	 * Fields it does not know are left out, and so are findings with a
	 * severity or requirement it does not know (the contract asks readers to
	 * skip them). Every string stays plain text; a reference that is not an
	 * https URL is dropped.
	 *
	 * @param mixed $report Report.
	 * @return array|null
	 */
	public static function normalize_report( $report ) {
		if ( ! is_array( $report ) || 1 !== ( $report['schema_version'] ?? null ) || ! isset( $report['token'], $report['score'], $report['findings'] ) ) {
			return null;
		}
		if ( ! is_string( $report['token'] ) || 1 !== preg_match( self::REPORT_TOKEN_PATTERN, $report['token'] ) || ! is_array( $report['score'] ) || ! is_array( $report['findings'] ) ) {
			return null;
		}

		$score = array();
		foreach ( array_merge( array( 'total' ), self::REQUIREMENTS ) as $key ) {
			if ( ! isset( $report['score'][ $key ] ) || ! is_int( $report['score'][ $key ] ) ) {
				return null;
			}
			$score[ $key ] = max( 0, min( 100, $report['score'][ $key ] ) );
		}

		$findings = array();
		foreach ( $report['findings'] as $finding ) {
			$finding = self::normalize_finding( $finding );
			if ( null !== $finding ) {
				$findings[] = $finding;
			}
		}

		return array(
			'token'        => $report['token'],
			'generated_at' => isset( $report['generated_at'] ) && is_string( $report['generated_at'] ) ? $report['generated_at'] : '',
			'feed_version' => isset( $report['feed_version'] ) && is_string( $report['feed_version'] ) ? $report['feed_version'] : '',
			'score'        => $score,
			'findings'     => $findings,
			'next_steps'   => self::strings( $report['next_steps'] ?? array() ),
			'attribution'  => isset( $report['attribution'] ) && is_string( $report['attribution'] ) ? $report['attribution'] : '',
		);
	}

	/**
	 * Returns a finding of a report as normalize_report() keeps it.
	 *
	 * @param mixed $finding Finding.
	 * @return array|null Null when it is not one this plugin can show.
	 */
	private static function normalize_finding( $finding ) {
		if ( ! is_array( $finding ) || ! isset( $finding['check_id'], $finding['severity'], $finding['requirement'], $finding['title'] ) ) {
			return null;
		}
		if ( ! is_string( $finding['check_id'] ) || ! is_string( $finding['title'] ) || ! in_array( $finding['severity'], self::SEVERITIES, true ) || ! in_array( $finding['requirement'], self::REQUIREMENTS, true ) ) {
			return null;
		}
		$normalized = array(
			'check_id'    => $finding['check_id'],
			'severity'    => $finding['severity'],
			'requirement' => $finding['requirement'],
			'title'       => $finding['title'],
			'detail'      => isset( $finding['detail'] ) && is_string( $finding['detail'] ) ? $finding['detail'] : '',
			'impact'      => isset( $finding['impact'] ) && is_string( $finding['impact'] ) ? $finding['impact'] : '',
			'fix'         => self::strings( $finding['fix'] ?? array() ),
			'reference'   => isset( $finding['reference'] ) && JP4WC_Security_Scanner::is_https_url( $finding['reference'] ) ? $finding['reference'] : '',
			'software'    => null,
		);
		if ( isset( $finding['software'] ) && is_array( $finding['software'] ) ) {
			$software = $finding['software'];
			if ( isset( $software['type'], $software['slug'] ) && in_array( $software['type'], array( 'plugin', 'theme', 'core' ), true ) && is_string( $software['slug'] ) ) {
				$normalized['software'] = array(
					'type'    => $software['type'],
					'slug'    => $software['slug'],
					'version' => isset( $software['version'] ) && is_string( $software['version'] ) ? $software['version'] : '',
				);
			}
		}
		return $normalized;
	}

	/**
	 * Returns the strings of a list.
	 *
	 * @param mixed $values List.
	 * @return string[]
	 */
	private static function strings( $values ) {
		return is_array( $values ) ? array_values( array_filter( $values, 'is_string' ) ) : array();
	}

	/**
	 * Returns the error for a status other than success.
	 *
	 * The server's message is for its log: what the store manager reads is
	 * chosen by the status.
	 *
	 * @param int   $status   HTTP status.
	 * @param array $response Response.
	 * @return WP_Error
	 */
	private static function status_error( $status, $response ) {
		switch ( $status ) {
			case 429:
				$retry_at = self::retry_at( $response );
				return new WP_Error(
					'jp4wc_security_scan_rate_limited',
					$retry_at > 0
						? sprintf(
							/* translators: %s: date and time */
							__( 'The check can run once an hour. Try again after %s.', 'woocommerce-for-japan' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $retry_at )
						)
						: __( 'The check can run once an hour. Try again later.', 'woocommerce-for-japan' ),
					array(
						'status'   => 429,
						'retry_at' => $retry_at,
					)
				);
			case 400:
			case 413:
				return new WP_Error(
					'jp4wc_security_scan_rejected',
					__( 'wc.artws.info did not accept the information sent. Update this plugin to the latest version and try again.', 'woocommerce-for-japan' ),
					array( 'status' => 502 )
				);
			case 503:
				return new WP_Error(
					'jp4wc_security_scan_unavailable',
					__( 'The check of wc.artws.info is not ready yet. Try again later.', 'woocommerce-for-japan' ),
					array( 'status' => 503 )
				);
			default:
				return new WP_Error(
					'jp4wc_security_scan_failed',
					sprintf(
						/* translators: %d: HTTP status code */
						__( 'wc.artws.info answered with an error (HTTP %d). Try again later.', 'woocommerce-for-japan' ),
						$status
					),
					array( 'status' => 502 )
				);
		}
	}

	/**
	 * Returns when a rate-limited check can run again.
	 *
	 * @param array $response Response with status 429.
	 * @return int Unix time, or 0 when the response does not say.
	 */
	private static function retry_at( $response ) {
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_array( $data ) && isset( $data['data']['retry_at'] ) && is_string( $data['data']['retry_at'] ) && 1 === preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $data['data']['retry_at'] ) ) {
			$time = strtotime( $data['data']['retry_at'] );
			if ( false !== $time ) {
				return $time;
			}
		}
		$after = wp_remote_retrieve_header( $response, 'retry-after' );
		return is_string( $after ) && ctype_digit( $after ) ? time() + (int) $after : 0;
	}

	/**
	 * Returns the error for a request that did not reach wc.artws.info.
	 *
	 * @return WP_Error
	 */
	private static function unreachable_error() {
		return new WP_Error(
			'jp4wc_security_scan_unreachable',
			__( 'Could not connect to wc.artws.info. Make sure this server can connect to it, and try again later.', 'woocommerce-for-japan' ),
			array( 'status' => 502 )
		);
	}

	/**
	 * Returns the error for a check the site is not ready to run.
	 *
	 * @return WP_Error
	 */
	private static function not_ready_error() {
		return new WP_Error(
			'jp4wc_security_scan_not_ready',
			__( 'The free check sends the results of the security check: turn the security check on and wait for its first results.', 'woocommerce-for-japan' ),
			array( 'status' => 409 )
		);
	}

	/**
	 * Returns the error for a check or deletion already running.
	 *
	 * @return WP_Error
	 */
	private static function busy_error() {
		return new WP_Error(
			'jp4wc_security_scan_busy',
			__( 'Another request to wc.artws.info is running. Try again in a few minutes.', 'woocommerce-for-japan' ),
			array( 'status' => 409 )
		);
	}

	/**
	 * Takes the lock that keeps two requests to wc.artws.info from running at
	 * once: two first checks would register the site twice, and a check
	 * running across a deletion would store a token that was just deleted.
	 *
	 * One row inserted with INSERT IGNORE; a row older than LOCK_TTL is taken
	 * over with a compare-and-swap of its time. A failed query fails closed.
	 *
	 * @return bool
	 */
	private static function lock() {
		global $wpdb;
		$now = time();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- An atomic lock: the options API cannot create a row only when it is missing.
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')",
				self::LOCK_OPTION,
				(string) $now
			)
		);
		if ( 1 === $inserted ) {
			return true;
		}
		if ( false === $inserted ) {
			return false;
		}
		$held_since = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", self::LOCK_OPTION ) );
		if ( '' !== $wpdb->last_error || null === $held_since || (int) $held_since > $now - self::LOCK_TTL ) {
			return false;
		}
		$taken = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				(string) $now,
				self::LOCK_OPTION,
				$held_since
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return 1 === $taken;
	}

	/**
	 * Releases the lock.
	 */
	private static function unlock() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The row lock() inserted.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", self::LOCK_OPTION ) );
	}
}
