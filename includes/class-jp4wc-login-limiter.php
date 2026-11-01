<?php
/**
 * Login attempt limiter.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Loaded here as well: an earlier version's JP4WC::includes() may load this file (#266).
require_once __DIR__ . '/class-jp4wc-security-checks.php';

/**
 * JP4WC_Login_Limiter
 *
 * Counts failed logins per client address (an IPv6 address per /64 network)
 * and per user name, and refuses logins for a while once either count
 * reaches its limit. Every login goes
 * through wp_authenticate(): wp-login.php, the WooCommerce My Account and
 * checkout login forms (wp_signon()) and XML-RPC.
 *
 * Each failure is a row of its own in the options table (not autoloaded,
 * written and read with $wpdb), holding the time it stops counting: an
 * INSERT per failure and a COUNT per check, so that parallel attempts are
 * all counted (a read-modify-write of one value would lose some). An
 * address or a name is locked while at least its limit of failures count,
 * and the lock lasts until enough of them expire.
 *
 * The limiter stays asleep while another measure protects the login page
 * (the security check's A-02 criteria: a known plugin, .htaccess, Jetpack's
 * brute force protection), so two limiters never count the same failure. It
 * is off until turned on in the Security tab, and the constant
 * JP4WC_LOGIN_LIMIT_DISABLE in wp-config.php turns it off from outside when
 * an administrator locked themselves out.
 *
 * @since 3.0.0
 */
class JP4WC_Login_Limiter {

	/**
	 * Setting that turns the limiter on ('1') or off ('').
	 */
	const SETTING = 'wc4jp-login-limit';

	/**
	 * Settings with the limits, and their defaults and ranges.
	 */
	const LIMITS = array(
		'wc4jp-login-limit-ip-attempts'     => array( 10, 1, 100 ),
		'wc4jp-login-limit-user-attempts'   => array( 5, 1, 100 ),
		'wc4jp-login-limit-lockout-minutes' => array( 15, 1, 1440 ),
	);

	/**
	 * Error code of a refused login. Not counted as a failure.
	 */
	const ERROR_CODE = 'jp4wc_login_locked';

	/**
	 * Prefix of the option rows that hold the failures:
	 * jp4wc_login_fail_<ip|user>_<hash of the address key or name>_<unique id>.
	 */
	const OPTION_PREFIX = 'jp4wc_login_fail_';

	/**
	 * Priority on the authenticate filter: after the password checks (20) and
	 * the cookie (30), which replace an earlier WP_Error with the user when the
	 * password is right, and before the multisite spam check (99).
	 */
	const AUTHENTICATE_PRIORITY = 50;

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'authenticate' ), self::AUTHENTICATE_PRIORITY, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'record_failure' ), 10, 2 );
		add_action( 'wp_login', array( __CLASS__, 'clear' ) );
		// WordPress fires this once a day: the expired failures of every key are removed then.
		add_action( 'delete_expired_transients', array( __CLASS__, 'delete_expired' ) );
	}

	/**
	 * Whether the setting is on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return '1' === get_option( self::SETTING, '' );
	}

	/**
	 * Whether the limiter counts and refuses logins on this request.
	 *
	 * On when the setting is on, the constant is not defined, nothing else
	 * protects the login page and the filter agrees.
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( ! self::is_enabled() || ( defined( 'JP4WC_LOGIN_LIMIT_DISABLE' ) && JP4WC_LOGIN_LIMIT_DISABLE ) ) {
			return false;
		}
		// An earlier version's security checks class, loaded while this plugin is updated, cannot tell: stay asleep.
		if ( ! method_exists( 'JP4WC_Security_Checks', 'login_protected_by_other_means' ) ) {
			return false;
		}
		if ( null !== self::protected_by() ) {
			return false;
		}

		/**
		 * Filters whether the login attempt limiter runs.
		 *
		 * Return false when another plugin or the server already limits login
		 * attempts and the security check does not recognise it (the
		 * `jp4wc_security_known_plugins` filter adds a plugin to that list, which
		 * also makes check A-02 pass).
		 *
		 * @since 3.0.0
		 * @param bool $active Whether the limiter runs.
		 */
		return (bool) apply_filters( 'jp4wc_login_limit_enabled', true );
	}

	/**
	 * Returns what else protects the login page, if anything.
	 *
	 * @return array{type: string, name: string}|null type: plugin, htaccess or
	 *                                                jetpack; name: what to show.
	 */
	public static function protected_by() {
		if ( ! method_exists( 'JP4WC_Security_Checks', 'login_protected_by_other_means' ) ) {
			return null;
		}
		return JP4WC_Security_Checks::login_protected_by_other_means();
	}

	/**
	 * Returns a limit, within its range.
	 *
	 * @param string $setting One of the LIMITS keys.
	 * @return int
	 */
	public static function limit( $setting ) {
		if ( ! isset( self::LIMITS[ $setting ] ) ) {
			return 0;
		}
		list( $fallback, $min, $max ) = self::LIMITS[ $setting ];
		return self::clamp( get_option( $setting, $fallback ), $fallback, $min, $max );
	}

	/**
	 * Returns a limit value within its range: the default for anything that is not a number.
	 *
	 * @param mixed $value    Saved or posted value.
	 * @param int   $fallback Default.
	 * @param int   $min      Lowest allowed.
	 * @param int   $max      Highest allowed.
	 * @return int
	 */
	public static function clamp( $value, $fallback, $min, $max ) {
		if ( is_bool( $value ) || ! is_numeric( $value ) ) {
			return $fallback;
		}
		return (int) min( $max, max( $min, (int) $value ) );
	}

	/**
	 * Refuses a login while the address or the user name is locked (authenticate, 50).
	 *
	 * While locked, the answer is the same whether the password was right or
	 * wrong, or another filter refused the login already: the lock would tell
	 * the right password apart otherwise. The lockout message is the only one
	 * shown then.
	 *
	 * @param WP_User|WP_Error|null $user     Result so far.
	 * @param string                $username User name or e-mail address.
	 * @return WP_User|WP_Error|null
	 */
	public static function authenticate( $user, $username ) {
		if ( '' === (string) $username || ! self::is_active() ) {
			return $user;
		}
		$locked_until = max(
			self::locked_until( 'ip', self::ip_key(), self::limit( 'wc4jp-login-limit-ip-attempts' ) ),
			self::locked_until( 'user', self::user_key( $username ), self::limit( 'wc4jp-login-limit-user-attempts' ) )
		);
		if ( $locked_until <= time() ) {
			return $user;
		}
		$minutes = max( 1, (int) ceil( ( $locked_until - time() ) / 60 ) );
		$build   = static function () use ( $minutes ) {
			return sprintf(
				/* translators: %d: minutes until logins are accepted again */
				_n(
					'<strong>Error:</strong> Too many login attempts. Please try again in %d minute.',
					'<strong>Error:</strong> Too many login attempts. Please try again in %d minutes.',
					$minutes,
					'woocommerce-for-japan'
				),
				$minutes
			);
		};
		// Shown to shoppers: the bundled translation stands in until the language pack has the string (#287).
		$message = function_exists( 'jp4wc_with_bundled_translation' ) ? jp4wc_with_bundled_translation( $build ) : $build();
		return new WP_Error( self::ERROR_CODE, $message );
	}

	/**
	 * Counts a failed login against the address and the user name (wp_login_failed).
	 *
	 * @param string        $username User name or e-mail address.
	 * @param WP_Error|null $error    Why the login failed.
	 */
	public static function record_failure( $username, $error = null ) {
		if ( $error instanceof WP_Error && self::ERROR_CODE === $error->get_error_code() ) {
			return;
		}
		if ( ! self::is_active() ) {
			return;
		}
		$seconds = self::limit( 'wc4jp-login-limit-lockout-minutes' ) * MINUTE_IN_SECONDS;
		self::add_failure( 'ip', self::ip_key(), self::limit( 'wc4jp-login-limit-ip-attempts' ), $seconds );
		if ( '' !== (string) $username ) {
			self::add_failure( 'user', self::user_key( $username ), self::limit( 'wc4jp-login-limit-user-attempts' ), $seconds );
		}
	}

	/**
	 * Forgets the failures of the user who just logged in (wp_login).
	 *
	 * The address keeps its failures: wp-login.php fires wp_login for a
	 * visitor with a valid cookie on every GET, so a visitor with any account
	 * could reset the address count between attempts on other names.
	 *
	 * @param string $user_login User name.
	 */
	public static function clear( $user_login ) {
		self::delete_rows( self::row_prefix( 'user', self::user_key( $user_login ) ) );
	}

	/**
	 * Returns the client address the counts are kept by.
	 *
	 * REMOTE_ADDR only: a forwarded-for header can be forged by the client.
	 * Behind a proxy that does not pass the client address, every visitor
	 * shares one address; the address limit is the looser one for that.
	 * This method and its filter decide which address it is; ip_key() decides
	 * which addresses count as one.
	 *
	 * @return string
	 */
	public static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the client address the login attempt limiter counts by.
		 *
		 * A site behind a proxy it trusts can take the address from the header
		 * that proxy sets. Return an empty string to count by address no more.
		 *
		 * The limiter then groups the value returned: an IPv6 address counts
		 * with the rest of its /64 network (the `jp4wc_login_limit_ipv6_prefix`
		 * filter changes the length), an IPv4-mapped IPv6 address
		 * (::ffff:192.0.2.1) as its IPv4 address, and a value that is no IP
		 * address as it is.
		 *
		 * @since 3.0.0
		 * @since 3.0.2 An IPv6 address counts per /64 network.
		 * @param string $ip The address (REMOTE_ADDR).
		 */
		return (string) apply_filters( 'jp4wc_login_limit_client_ip', $ip );
	}

	/**
	 * Returns the key the client address is counted by.
	 *
	 * An IPv6 address counts with the rest of its network, a /64 by default:
	 * a home line or a server usually gets a /64 or more, so it could take a
	 * new address for every attempt. A network many people share (an office
	 * LAN on IPv6) counts as one, as a NAT does on IPv4. An IPv4-mapped
	 * address (::ffff:192.0.2.1, from a server that listens on IPv6 for both)
	 * counts as its IPv4 address, not together with every IPv4 client in
	 * ::/64. An IPv4 address, and a value that is no IP address (from the
	 * jp4wc_login_limit_client_ip filter), counts as it is; an empty one is
	 * not counted.
	 *
	 * @since 3.0.2
	 * @return string
	 */
	private static function ip_key() {
		$ip = self::client_ip();
		// Only a valid address reaches inet_pton().
		if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}
		$packed = inet_pton( $ip );
		if ( false === $packed || 16 !== strlen( $packed ) ) {
			return $ip;
		}
		if ( str_repeat( "\0", 10 ) . "\xff\xff" === substr( $packed, 0, 12 ) ) {
			return (string) inet_ntop( substr( $packed, 12 ) );
		}

		/**
		 * Filters the length of the IPv6 network the login attempt limiter counts as one client.
		 *
		 * 64 by default, kept within 32 to 128: 128 counts each IPv6 address on
		 * its own, 56 or 48 a larger network as one.
		 *
		 * @since 3.0.2
		 * @param int $prefix_length Leading bits of the address that count.
		 */
		$bits   = self::clamp( apply_filters( 'jp4wc_login_limit_ipv6_prefix', 64 ), 64, 32, 128 );
		$whole  = intdiv( $bits, 8 );
		$masked = substr( $packed, 0, $whole );
		if ( 0 !== $bits % 8 ) {
			$masked .= chr( ord( $packed[ $whole ] ) & ( 0xff << ( 8 - $bits % 8 ) ) & 0xff );
		}
		return (string) inet_ntop( str_pad( $masked, 16, "\0" ) ) . '/' . $bits;
	}

	/**
	 * Removes the failures and the settings of every site (uninstall).
	 */
	public static function uninstall() {
		if ( ! is_multisite() ) {
			self::delete_counts();
			self::delete_settings();
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
				self::delete_counts();
				self::delete_settings();
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Removes every failure of the current site.
	 */
	public static function delete_counts() {
		self::delete_rows( self::OPTION_PREFIX );
	}

	/**
	 * Removes the failures that no longer count: of one key on each failure of
	 * that key, of every key once a day (delete_expired_transients).
	 *
	 * @param string $prefix Option name prefix; the whole set by default.
	 */
	public static function delete_expired( $prefix = self::OPTION_PREFIX ) {
		global $wpdb;
		if ( ! is_string( $prefix ) || 0 !== strpos( $prefix, self::OPTION_PREFIX ) ) {
			// The action passes nothing of use.
			$prefix = self::OPTION_PREFIX;
		}
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Expired failures, found by their name prefix; never cached.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value <= %s",
				$wpdb->esc_like( $prefix ) . '%',
				self::stamp( time() )
			)
		);
	}

	/**
	 * Removes the settings of the current site.
	 */
	private static function delete_settings() {
		delete_option( self::SETTING );
		foreach ( array_keys( self::LIMITS ) as $setting ) {
			delete_option( $setting );
		}
	}

	/**
	 * Returns the key a user name or e-mail address is counted by.
	 *
	 * An existing user is counted by the login name as stored, whichever way
	 * it was typed: the login name in any case (the lookup is case-insensitive,
	 * as the password check is), or the e-mail address, which
	 * wp_authenticate_email_password() resolves the same way. A login name in
	 * the form of an e-mail address is found first, as wp_authenticate_username_password()
	 * finds it. A name that is no user is counted as typed, in lower case.
	 *
	 * @param string $username User name or e-mail address.
	 * @return string
	 */
	private static function user_key( $username ) {
		$name = trim( (string) $username );
		$user = get_user_by( 'login', $name );
		if ( ! $user && is_email( $name ) ) {
			$user = get_user_by( 'email', $name );
		}
		if ( $user instanceof WP_User ) {
			$name = $user->user_login;
		}
		// ASCII only on purpose: the same key on every host, with or without mbstring.
		return strtolower( $name );
	}

	/**
	 * Returns the prefix of the option rows of an address or user name.
	 *
	 * @param string $type ip or user.
	 * @param string $key  Address or user name.
	 * @return string
	 */
	private static function row_prefix( $type, $key ) {
		return self::OPTION_PREFIX . $type . '_' . md5( $key ) . '_';
	}

	/**
	 * Returns when the failures of an address or user name that still count expire, oldest first.
	 *
	 * @param string $type ip or user.
	 * @param string $key  Address or user name.
	 * @return int[]|null Null when the rows could not be read.
	 */
	private static function expiries( $type, $key ) {
		global $wpdb;
		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One row per failure, found by its name prefix; never cached.
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value > %s ORDER BY option_value ASC",
				$wpdb->esc_like( self::row_prefix( $type, $key ) ) . '%',
				self::stamp( time() )
			)
		);
		if ( '' !== $wpdb->last_error ) {
			self::log( sprintf( 'Could not read the login failures: %s', $wpdb->last_error ) );
			return null;
		}
		return array_map( 'intval', (array) $rows );
	}

	/**
	 * Returns until when an address or user name is locked (0 when not).
	 *
	 * Locked while at least the limit of failures count; the lock ends when
	 * enough of them have expired for the count to drop below the limit.
	 * When the failures cannot be read, the login goes through (an outage
	 * must not lock everyone out) and the error is logged.
	 *
	 * @param string $type  ip or user.
	 * @param string $key   Address or user name.
	 * @param int    $limit Failures allowed.
	 * @return int
	 */
	private static function locked_until( $type, $key, $limit ) {
		if ( '' === $key ) {
			return 0;
		}
		$expiries = self::expiries( $type, $key );
		if ( null === $expiries || count( $expiries ) < $limit ) {
			return 0;
		}
		return $expiries[ count( $expiries ) - $limit ];
	}

	/**
	 * Adds a failure to an address or user name, and logs the lock at the limit.
	 *
	 * A failure counts for the lockout time, so failures further apart than
	 * that do not add up. The expired failures of the same key are removed on
	 * the way (those of other keys once a day: a sweep of every row on each
	 * failure would grow with the number of addresses and names failing).
	 *
	 * @param string $type    ip or user.
	 * @param string $key     Address or user name.
	 * @param int    $limit   Failures allowed.
	 * @param int    $seconds How long the failure counts.
	 */
	private static function add_failure( $type, $key, $limit, $seconds ) {
		global $wpdb;
		if ( '' === $key ) {
			return;
		}
		$prefix = self::row_prefix( $type, $key );
		self::delete_expired( $prefix );
		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One row per failure: an atomic count, unlike a value read and written back.
			$wpdb->options,
			array(
				'option_name'  => $prefix . str_replace( '.', '', uniqid( '', true ) ) . wp_rand( 1000, 9999 ),
				'option_value' => self::stamp( time() + $seconds ),
				'autoload'     => 'off',
			),
			array( '%s', '%s', '%s' )
		);
		if ( false === $inserted ) {
			self::log( sprintf( 'Could not record a login failure: %s', $wpdb->last_error ) );
			return;
		}
		$expiries = self::expiries( $type, $key );
		if ( null !== $expiries && count( $expiries ) >= $limit ) {
			self::log( sprintf( 'Login attempts locked by %s after %d failures for %d seconds.', 'ip' === $type ? 'client address' : 'user name', count( $expiries ), $seconds ) );
		}
	}

	/**
	 * Removes the option rows whose name starts with a prefix.
	 *
	 * @param string $prefix Option name prefix.
	 */
	private static function delete_rows( $prefix ) {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Rows found by their name prefix; never cached.
			$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $prefix ) . '%' )
		);
	}

	/**
	 * Returns a time as the fixed-width string the rows hold, so that strings compare as numbers.
	 *
	 * @param int $time Unix time.
	 * @return string
	 */
	private static function stamp( $time ) {
		return sprintf( '%010d', $time );
	}

	/**
	 * Logs with WooCommerce's logger (source jp4wc-login-limit), without the address or name.
	 *
	 * @param string $message Message.
	 */
	private static function log( $message ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->warning( $message, array( 'source' => 'jp4wc-login-limit' ) );
	}
}

JP4WC_Login_Limiter::init();
