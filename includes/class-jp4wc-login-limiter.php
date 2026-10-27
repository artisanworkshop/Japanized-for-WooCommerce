<?php
/**
 * Login attempt limiter.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Loaded here as well: an earlier version's JP4WC::includes() may load this file (#266).
require_once __DIR__ . '/class-jp4wc-security-checks.php';

/**
 * JP4WC_Login_Limiter
 *
 * Counts failed logins per client address and per user name, and refuses
 * logins for a while once either count reaches its limit. Every login goes
 * through wp_authenticate(): wp-login.php, the WooCommerce My Account and
 * checkout login forms (wp_signon()) and XML-RPC. The REST API's application
 * password failures count against the address only.
 *
 * The limiter stays asleep while another measure protects the login page
 * (the security check's A-02 criteria: a known plugin, .htaccess, Jetpack's
 * brute force protection), so two limiters never count the same failure. It
 * is off until turned on in the Security tab, and the constant
 * JP4WC_LOGIN_LIMIT_DISABLE in wp-config.php turns it off from outside when
 * an administrator locked themselves out.
 *
 * @since 3.1.0
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
	 * Prefix of the transients that hold the counts: jp4wc_login_fail_ip_<hash> and _user_<hash>.
	 */
	const TRANSIENT_PREFIX = 'jp4wc_login_fail_';

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
		add_action( 'application_password_failed_authentication', array( __CLASS__, 'record_app_password_failure' ) );
		add_action( 'wp_login', array( __CLASS__, 'clear' ), 10, 2 );
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
	 * On when the setting is on, the constant is not defined, the filter
	 * agrees and nothing else protects the login page.
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( ! self::is_enabled() || ( defined( 'JP4WC_LOGIN_LIMIT_DISABLE' ) && JP4WC_LOGIN_LIMIT_DISABLE ) ) {
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
		 * @since 3.1.0
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
		list( $default, $min, $max ) = self::LIMITS[ $setting ];
		return self::clamp( get_option( $setting, $default ), $default, $min, $max );
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
		$locked_until = max( self::locked_until( 'ip', self::client_ip() ), self::locked_until( 'user', self::user_key( $username ) ) );
		if ( $locked_until <= time() ) {
			return $user;
		}
		$minutes = max( 1, (int) ceil( ( $locked_until - time() ) / 60 ) );
		return new WP_Error(
			self::ERROR_CODE,
			sprintf(
				/* translators: %d: minutes until logins are accepted again */
				_n(
					'<strong>Error:</strong> Too many login attempts. Please try again in %d minute.',
					'<strong>Error:</strong> Too many login attempts. Please try again in %d minutes.',
					$minutes,
					'woocommerce-for-japan'
				),
				$minutes
			)
		);
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
		self::increment( 'ip', self::client_ip(), self::limit( 'wc4jp-login-limit-ip-attempts' ) );
		if ( '' !== (string) $username ) {
			self::increment( 'user', self::user_key( $username ), self::limit( 'wc4jp-login-limit-user-attempts' ) );
		}
	}

	/**
	 * Counts a failed application password against the address (REST API basic auth).
	 */
	public static function record_app_password_failure() {
		if ( ! self::is_active() ) {
			return;
		}
		self::increment( 'ip', self::client_ip(), self::limit( 'wc4jp-login-limit-ip-attempts' ) );
	}

	/**
	 * Forgets the counts of the address and the user who just logged in (wp_login).
	 *
	 * @param string  $user_login User name.
	 * @param WP_User $user       User.
	 */
	public static function clear( $user_login, $user = null ) {
		delete_transient( self::transient_name( 'ip', self::client_ip() ) );
		delete_transient( self::transient_name( 'user', self::user_key( $user_login ) ) );
		if ( $user instanceof WP_User && $user->user_email ) {
			delete_transient( self::transient_name( 'user', self::user_key( $user->user_email ) ) );
		}
	}

	/**
	 * Returns the client address the counts are kept by.
	 *
	 * REMOTE_ADDR only: a forwarded-for header can be forged by the client.
	 * Behind a proxy that does not pass the client address, every visitor
	 * shares one address; the address limit is the looser one for that.
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
		 * @since 3.1.0
		 * @param string $ip The address (REMOTE_ADDR).
		 */
		return (string) apply_filters( 'jp4wc_login_limit_client_ip', $ip );
	}

	/**
	 * Removes the counts of every site (uninstall).
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
	 * Removes every count of the current site.
	 */
	public static function delete_counts() {
		global $wpdb;
		$names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- The transients are found by their name prefix (uninstall).
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%'
			)
		);
		foreach ( (array) $names as $name ) {
			delete_transient( substr( (string) $name, strlen( '_transient_' ) ) );
		}
		// With a persistent object cache the counts are not in the table; they expire within a day by themselves.
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
	 * @param string $username User name or e-mail address.
	 * @return string
	 */
	private static function user_key( $username ) {
		return strtolower( trim( (string) $username ) );
	}

	/**
	 * Returns the transient of an address or user name.
	 *
	 * @param string $type ip or user.
	 * @param string $key  Address or user name.
	 * @return string
	 */
	private static function transient_name( $type, $key ) {
		return self::TRANSIENT_PREFIX . $type . '_' . md5( $key );
	}

	/**
	 * Returns until when an address or user name is locked (0 when not).
	 *
	 * @param string $type ip or user.
	 * @param string $key  Address or user name.
	 * @return int
	 */
	private static function locked_until( $type, $key ) {
		if ( '' === $key ) {
			return 0;
		}
		$state = get_transient( self::transient_name( $type, $key ) );
		return is_array( $state ) && isset( $state['locked_until'] ) ? (int) $state['locked_until'] : 0;
	}

	/**
	 * Adds a failure to an address or user name, and locks it at the limit.
	 *
	 * The count is kept for the lockout time: failures further apart than
	 * that start over.
	 *
	 * @param string $type  ip or user.
	 * @param string $key   Address or user name.
	 * @param int    $limit Failures allowed.
	 */
	private static function increment( $type, $key, $limit ) {
		if ( '' === $key ) {
			return;
		}
		$seconds = self::limit( 'wc4jp-login-limit-lockout-minutes' ) * MINUTE_IN_SECONDS;
		$name    = self::transient_name( $type, $key );
		$state   = get_transient( $name );
		$count   = is_array( $state ) && isset( $state['count'] ) ? (int) $state['count'] : 0;
		++$count;
		$state = array(
			'count'        => $count,
			'locked_until' => 0,
		);
		if ( $count >= $limit ) {
			$state['locked_until'] = time() + $seconds;
			self::log( $type, $count, $seconds );
		}
		set_transient( $name, $state, $seconds );
	}

	/**
	 * Logs a lockout with WooCommerce's logger (source jp4wc-login-limit), without the address or name.
	 *
	 * @param string $type    ip or user.
	 * @param int    $count   Failures counted.
	 * @param int    $seconds Lockout length.
	 */
	private static function log( $type, $count, $seconds ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->warning(
			sprintf( 'Login attempts locked by %s after %d failures for %d seconds.', 'ip' === $type ? 'client address' : 'user name', $count, $seconds ),
			array( 'source' => 'jp4wc-login-limit' )
		);
	}
}

JP4WC_Login_Limiter::init();
