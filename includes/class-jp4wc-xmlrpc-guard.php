<?php
/**
 * XML-RPC usage report and guard.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JP4WC_XMLRPC_Guard
 *
 * Two parts, for check A-04 of the security check:
 *
 * - A record of the XML-RPC calls this site answers, kept whatever the
 *   setting: which methods were called, how often, with what result (a login
 *   that worked or failed, refused by this guard, a Jetpack request, no
 *   login) and by which client. The Security tab shows it, so that the
 *   integrations that still use XML-RPC can be told apart from attacks
 *   before XML-RPC is turned off.
 * - A setting, off until turned on, that turns XML-RPC off except for the
 *   methods on an allow list. Every other method answers a 405 fault without
 *   running (xmlrpc_methods), so neither its login nor a pingback happens.
 *   With an empty allow list xmlrpc_enabled is false as well, and the
 *   pingback header and the RSD link are gone.
 *
 * A request that Jetpack's connection took over (?for=jetpack, with
 * WordPress's password login removed and the methods chosen by its
 * Jetpack_XMLRPC_Server) is left alone: Jetpack checks its signature, so
 * Jetpack keeps working. WooCommerce bundles that connection, so on most
 * sites any request with ?for=jetpack is taken over, and then offers only
 * Jetpack's methods and no password login. Where nothing takes it over,
 * the parameter changes nothing and the request is guarded.
 *
 * WordPress.com's own login and the REST API are not XML-RPC and are not
 * affected.
 *
 * @since 3.0.0
 */
class JP4WC_XMLRPC_Guard {

	/**
	 * Setting that turns XML-RPC off ('1' on, '' off).
	 */
	const SETTING = 'wc4jp-disable-xmlrpc';

	/**
	 * Setting with the methods still answered: one per line, a name or a prefix ending in *.
	 */
	const ALLOW_SETTING = 'wc4jp-xmlrpc-allow';

	/**
	 * Entries kept on the allow list.
	 */
	const ALLOW_MAX = 50;

	/**
	 * An allow list entry: a method name (letters, digits, _ . : and /, as the XML-RPC specification allows) with an optional * at the end.
	 */
	const ALLOW_PATTERN = '/^[A-Za-z0-9_.:\/]+\*?$/D';

	/**
	 * Prefix of the option rows of the record: jp4wc_xmlrpc_use_<md5 of the method>_<info|outcome>.
	 */
	const OPTION_PREFIX = 'jp4wc_xmlrpc_use_';

	/**
	 * Results a call is counted under.
	 *
	 * A call counts as login when a password login worked, failed when one
	 * failed, refused when this guard refused the method, jetpack in a request
	 * Jetpack took over and open without a login.
	 */
	const OUTCOMES = array( 'login', 'failed', 'refused', 'jetpack', 'open' );

	/**
	 * Methods IXR adds after xmlrpc_methods, which the guard cannot refuse. None of them logs in.
	 */
	const ALWAYS_ANSWERED = array( 'system.getCapabilities', 'system.listMethods', 'system.multicall' );

	/**
	 * Methods recorded at most.
	 */
	const METHOD_MAX = 100;

	/**
	 * Methods recorded from one request at most.
	 */
	const METHODS_PER_REQUEST = 10;

	/**
	 * Seconds a method stays in the record after its last call (30 days).
	 */
	const RETENTION = 2592000;

	/**
	 * Seconds between two writes of a method's last call time and client.
	 */
	const TOUCH_INTERVAL = 60;

	/**
	 * Characters of the User-Agent header kept.
	 */
	const USER_AGENT_LENGTH = 200;

	/**
	 * Class of the XML-RPC server Jetpack's connection creates for a request it takes over.
	 *
	 * The connection package itself is bundled by WooCommerce and many other
	 * plugins, so its classes being loaded tells nothing.
	 */
	const JETPACK_SERVER = 'Jetpack_XMLRPC_Server';

	/**
	 * What the guard decided for the XML-RPC server of this request (xmlrpc_methods).
	 *
	 * @var array{jetpack: bool, originals: array}
	 */
	private static $request = array(
		'jetpack'   => false,
		'originals' => array(),
	);

	/**
	 * Whether this request is an XML-RPC request, when set (tests); null reads XMLRPC_REQUEST.
	 *
	 * A test cannot define the constant: it would stay for the rest of the run.
	 *
	 * @var bool|null
	 */
	private static $xmlrpc_request = null;

	/**
	 * Password logins of this request: worked, failed.
	 *
	 * @var array{ok: int, failed: int}
	 */
	private static $logins = array(
		'ok'     => 0,
		'failed' => 0,
	);

	/**
	 * Registers the hooks.
	 */
	public static function init() {
		// Last, after Jetpack (1000) and other plugins have put their methods in.
		add_filter( 'xmlrpc_methods', array( __CLASS__, 'filter_methods' ), PHP_INT_MAX );
		add_filter( 'xmlrpc_enabled', array( __CLASS__, 'filter_enabled' ), 100 );
		add_filter( 'wp_headers', array( __CLASS__, 'filter_headers' ) );
		add_action( 'wp_head', array( __CLASS__, 'remove_rsd_link' ), 0 );
		add_filter( 'authenticate', array( __CLASS__, 'note_login' ), PHP_INT_MAX );
		add_action( 'wp_login_failed', array( __CLASS__, 'note_login_failure' ) );
		add_action( 'shutdown', array( __CLASS__, 'record_current_request' ) );
		// WordPress fires this once a day: the methods unused for RETENTION are removed then.
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
	 * Returns the allow list.
	 *
	 * @return string[]
	 */
	public static function allow_list() {
		$value = self::normalize_allow_list( get_option( self::ALLOW_SETTING, '' ) );
		return '' === $value ? array() : explode( "\n", $value );
	}

	/**
	 * Whether XML-RPC is off with nothing allowed.
	 *
	 * @return bool
	 */
	public static function disables_all() {
		return self::is_enabled() && array() === self::allow_list();
	}

	/**
	 * Returns an allow list as it is saved: valid entries, one per line, without duplicates.
	 *
	 * Entries may be separated by new lines, commas or spaces. An entry that
	 * is not a method name with an optional * at the end is dropped, and so is
	 * anything but a string.
	 *
	 * @param mixed $value Posted or saved value.
	 * @return string
	 */
	public static function normalize_allow_list( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$entries = array();
		foreach ( (array) preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY ) as $entry ) {
			if ( 1 === preg_match( self::ALLOW_PATTERN, $entry ) && ! in_array( $entry, $entries, true ) ) {
				$entries[] = $entry;
			}
		}
		return implode( "\n", array_slice( $entries, 0, self::ALLOW_MAX ) );
	}

	/**
	 * Whether a method is on an allow list.
	 *
	 * Method names are matched as IXR matches them, case-sensitively; an entry
	 * ending in * matches every method that starts with the rest.
	 *
	 * @param string   $method  Method name.
	 * @param string[] $entries Allow list.
	 * @return bool
	 */
	public static function is_allowed( $method, array $entries ) {
		foreach ( $entries as $entry ) {
			if ( '*' === substr( $entry, -1 ) ? 0 === strpos( $method, substr( $entry, 0, -1 ) ) : $method === $entry ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Refuses the methods not on the allow list while the setting is on (xmlrpc_methods).
	 *
	 * Runs once for each XML-RPC server, before the request is read: the
	 * methods as other plugins left them are kept for the record.
	 *
	 * @param array $methods Method name => callback.
	 * @return array
	 */
	public static function filter_methods( $methods ) {
		// A long-lived worker keeps static values between requests: start over for each server.
		self::$logins  = array(
			'ok'     => 0,
			'failed' => 0,
		);
		self::$request = array(
			'jetpack'   => self::jetpack_owns_request(),
			'originals' => is_array( $methods ) ? $methods : array(),
		);
		if ( ! is_array( $methods ) || self::$request['jetpack'] || ! self::is_enabled() ) {
			return $methods;
		}
		$allow = self::allow_list();
		foreach ( array_keys( $methods ) as $name ) {
			if ( ! is_string( $name ) || ! self::is_allowed( $name, $allow ) ) {
				// An array callback: IXR calls it with is_callable(); a closure would fail its function_exists().
				$methods[ $name ] = array( __CLASS__, 'refuse' );
			}
		}
		return $methods;
	}

	/**
	 * Answers a refused method.
	 *
	 * @param mixed $args Arguments of the call (not used).
	 * @return IXR_Error
	 */
	public static function refuse( $args = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- IXR passes the arguments.
		return new IXR_Error( 405, __( 'XML-RPC services are disabled on this site.', 'woocommerce-for-japan' ) );
	}

	/**
	 * Turns off the password login of XML-RPC when nothing is allowed (xmlrpc_enabled).
	 *
	 * With an allow list the value stays: the allowed methods log in as before.
	 *
	 * @param bool $enabled Whether XML-RPC methods that need a login are enabled.
	 * @return bool
	 */
	public static function filter_enabled( $enabled ) {
		if ( self::disables_all() && ! self::jetpack_owns_request() ) {
			return false;
		}
		return $enabled;
	}

	/**
	 * Removes the pingback header when nothing is allowed (wp_headers).
	 *
	 * @param array $headers Headers.
	 * @return array
	 */
	public static function filter_headers( $headers ) {
		if ( is_array( $headers ) && isset( $headers['X-Pingback'] ) && self::disables_all() ) {
			unset( $headers['X-Pingback'] );
		}
		return $headers;
	}

	/**
	 * Removes the link to the XML-RPC discovery document when nothing is allowed (wp_head, 0).
	 *
	 * Decided on each page; on a long-lived worker, turning the setting off
	 * brings the link back when the worker restarts.
	 */
	public static function remove_rsd_link() {
		if ( self::disables_all() ) {
			remove_action( 'wp_head', 'rsd_link' );
		}
	}

	/**
	 * Whether Jetpack took this request over.
	 *
	 * @return bool
	 */
	public static function jetpack_owns_request() {
		$for_param = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only to tell Jetpack's requests apart, as Jetpack itself does; nothing is changed.
		if ( isset( $_GET['for'] ) && is_string( $_GET['for'] ) ) {
			$for_param = sanitize_text_field( wp_unslash( $_GET['for'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- As above.
		}
		$password_login = false !== has_filter( 'authenticate', 'wp_authenticate_username_password' );
		return self::is_jetpack_request( $for_param, $password_login, self::jetpack_filters_methods() );
	}

	/**
	 * Whether Jetpack's XML-RPC server chooses the methods of this request.
	 *
	 * @return bool
	 */
	private static function jetpack_filters_methods() {
		global $wp_filter;
		if ( ! isset( $wp_filter['xmlrpc_methods'] ) || ! $wp_filter['xmlrpc_methods'] instanceof WP_Hook ) {
			return false;
		}
		foreach ( $wp_filter['xmlrpc_methods']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = isset( $callback['function'] ) ? $callback['function'] : null;
				// is_a() on an object never loads a class.
				if ( is_array( $function ) && isset( $function[0] ) && is_object( $function[0] ) && is_a( $function[0], self::JETPACK_SERVER ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether a request is one Jetpack took over.
	 *
	 * Jetpack's connection (Connection\Manager::setup_xmlrpc_handlers())
	 * handles a request with ?for=jetpack exactly: it removes every
	 * authenticate callback, WordPress's password login with them, checks
	 * the request's signature, and lets its Jetpack_XMLRPC_Server choose the
	 * methods. A request that only adds the parameter still has the password
	 * login and no such server.
	 *
	 * @param string $for_param       Value of the for parameter.
	 * @param bool   $password_login  Whether WordPress's password login is still hooked.
	 * @param bool   $jetpack_methods Whether Jetpack's XML-RPC server filters the methods.
	 * @return bool
	 */
	public static function is_jetpack_request( $for_param, $password_login, $jetpack_methods ) {
		return 'jetpack' === $for_param && ! $password_login && $jetpack_methods;
	}

	/**
	 * Counts a password login that worked during an XML-RPC request (authenticate, last).
	 *
	 * @param WP_User|WP_Error|null $user Result.
	 * @return WP_User|WP_Error|null
	 */
	public static function note_login( $user ) {
		if ( $user instanceof WP_User && self::is_xmlrpc_request() ) {
			++self::$logins['ok'];
		}
		return $user;
	}

	/**
	 * Counts a failed login during an XML-RPC request (wp_login_failed).
	 */
	public static function note_login_failure() {
		if ( self::is_xmlrpc_request() ) {
			++self::$logins['failed'];
		}
	}

	/**
	 * Records the calls of this XML-RPC request (shutdown).
	 */
	public static function record_current_request() {
		global $wp_xmlrpc_server;
		if ( ! self::is_xmlrpc_request() ) {
			return;
		}
		$logins        = self::$logins;
		$request       = self::$request;
		self::$logins  = array(
			'ok'     => 0,
			'failed' => 0,
		);
		self::$request = array(
			'jetpack'   => false,
			'originals' => array(),
		);
		if ( ! is_object( $wp_xmlrpc_server ) ) {
			return;
		}
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		// The server is a global only on xmlrpc.php; Jetpack's alternate endpoint keeps its own.
		self::record_request( $wp_xmlrpc_server, $request, $logins, $user_agent );
	}

	/**
	 * Records the calls of a served XML-RPC request.
	 *
	 * Only methods the server has are recorded, so a client cannot add
	 * names of its own, and no more than METHODS_PER_REQUEST of them, so a
	 * multicall listing every method costs a few queries. A system.multicall
	 * counts each call inside it. The login result is the request's: in a
	 * multicall that mixes methods, each method gets the same one.
	 *
	 * @param object                                 $server     The XML-RPC server, after serving (message, callbacks, error).
	 * @param array{jetpack: bool, originals: array} $request    What the guard decided for the server.
	 * @param array{ok: int, failed: int}            $logins     Password logins of the request.
	 * @param string                                 $user_agent Client.
	 * @param int|null                               $now        Time (tests).
	 */
	public static function record_request( $server, array $request, array $logins, $user_agent, $now = null ) {
		$now       = null === $now ? time() : (int) $now;
		$message   = isset( $server->message ) ? $server->message : null;
		$callbacks = isset( $server->callbacks ) && is_array( $server->callbacks ) ? $server->callbacks : array();
		if ( ! is_object( $message ) || ! isset( $message->methodName ) || ! is_string( $message->methodName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- IXR's property.
			return;
		}
		$params = isset( $message->params ) && is_array( $message->params ) ? $message->params : array();

		$calls = array();
		foreach ( self::requested_methods( $message->methodName, $params ) as $name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- IXR's property.
			if ( ! isset( $callbacks[ $name ] ) ) {
				continue;
			}
			if ( ! isset( $calls[ $name ] ) && count( $calls ) >= self::METHODS_PER_REQUEST ) {
				continue;
			}
			$calls[ $name ] = isset( $calls[ $name ] ) ? $calls[ $name ] + 1 : 1;
		}
		if ( array() === $calls ) {
			return;
		}

		// WordPress refused the login itself: XML-RPC turned off by xmlrpc_enabled (this guard or another plugin).
		$error   = isset( $server->error ) ? $server->error : null;
		$refused = is_object( $error ) && isset( $error->code ) && 405 === (int) $error->code;

		$entries   = array();
		$originals = isset( $request['originals'] ) && is_array( $request['originals'] ) ? $request['originals'] : array();
		foreach ( $calls as $name => $count ) {
			if ( ! empty( $request['jetpack'] ) ) {
				$outcome = 'jetpack';
			} elseif ( array( __CLASS__, 'refuse' ) === $callbacks[ $name ] ) {
				$outcome = 'refused';
			} elseif ( ! empty( $logins['failed'] ) ) {
				$outcome = 'failed';
			} elseif ( ! empty( $logins['ok'] ) ) {
				$outcome = 'login';
			} elseif ( $refused ) {
				$outcome = 'refused';
			} else {
				$outcome = 'open';
			}
			$entries[ $name ] = array(
				'outcome'  => $outcome,
				'count'    => $count,
				'callback' => array_key_exists( $name, $originals ) ? $originals[ $name ] : $callbacks[ $name ],
			);
		}
		self::store( $entries, get_class( $server ), self::clean_user_agent( $user_agent ), $now );
	}

	/**
	 * Returns a client as it is kept: printable ASCII only, at most USER_AGENT_LENGTH characters.
	 *
	 * A User-Agent header is ASCII; anything else (or a 4-byte character a
	 * utf8 table refuses) is dropped rather than failing the write.
	 *
	 * @param string $user_agent User-Agent header.
	 * @return string
	 */
	public static function clean_user_agent( $user_agent ) {
		return substr( (string) preg_replace( '/[^\x20-\x7E]/', '', (string) $user_agent ), 0, self::USER_AGENT_LENGTH );
	}

	/**
	 * Returns the methods a request calls: the one method, or each call in a system.multicall.
	 *
	 * @param string $method Method of the request.
	 * @param array  $params Parameters of the request.
	 * @return string[]
	 */
	public static function requested_methods( $method, array $params ) {
		if ( 'system.multicall' !== $method ) {
			return array( $method );
		}
		$names = array();
		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			foreach ( $params[0] as $call ) {
				// IXR refuses a multicall inside a multicall.
				if ( is_array( $call ) && isset( $call['methodName'] ) && is_string( $call['methodName'] ) && 'system.multicall' !== $call['methodName'] ) {
					$names[] = $call['methodName'];
				}
			}
		}
		return $names;
	}

	/**
	 * Adds the calls of a request to the record.
	 *
	 * The methods' rows are read in one query and the counts written in one
	 * statement, added to in the database (parallel requests are all
	 * counted). A new method is recorded while fewer than METHOD_MAX are (the
	 * methods unused for RETENTION are removed first when the limit is
	 * reached). A method's row is written when it is new or its last call is
	 * older than TOUCH_INTERVAL, so a client cannot make every call rewrite
	 * it; the client of the last login that worked is kept apart from the
	 * last client, so failed attempts cannot hide it. Parallel writes of a
	 * row keep one of them, which is all it holds.
	 *
	 * @param array<string, array{outcome: string, count: int, callback: mixed}> $entries      Method => calls.
	 * @param string                                                             $server_class Class of the XML-RPC server.
	 * @param string                                                             $user_agent   Client (clean_user_agent()).
	 * @param int                                                                $now          Time.
	 */
	private static function store( array $entries, $server_class, $user_agent, $now ) {
		global $wpdb;
		$names = array();
		foreach ( array_keys( $entries ) as $method ) {
			$names[ self::OPTION_PREFIX . md5( $method ) . '_info' ] = $method;
		}
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Read from the database: a cached copy may predate another request's write.
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name IN (" . implode( ', ', array_fill( 0, count( $names ), '%s' ) ) . ')', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One placeholder per name.
				array_keys( $names )
			),
			ARRAY_A
		);
		if ( '' !== $wpdb->last_error || ! is_array( $rows ) ) {
			return;
		}
		$infos = array();
		foreach ( $rows as $row ) {
			$info = maybe_unserialize( $row['option_value'] );
			if ( isset( $names[ $row['option_name'] ] ) && is_array( $info ) ) {
				$infos[ $names[ $row['option_name'] ] ] = $info;
			}
		}

		$new = array_diff( array_keys( $entries ), array_keys( $infos ) );
		if ( array() !== $new ) {
			$room = self::METHOD_MAX - self::count_methods();
			if ( $room < count( $new ) ) {
				self::delete_expired( null, $now );
				$room = self::METHOD_MAX - self::count_methods();
			}
			foreach ( array_slice( $new, max( 0, $room ) ) as $method ) {
				unset( $entries[ $method ] );
			}
			if ( array() === $entries ) {
				return;
			}
		}

		$args = array();
		foreach ( $entries as $method => $entry ) {
			$args[] = self::OPTION_PREFIX . md5( $method ) . '_' . $entry['outcome'];
			$args[] = (string) (int) $entry['count'];
		}
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Counts added to in the database, so that parallel requests are all counted.
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Two values per placeholder group, one group per count.
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES " . implode( ', ', array_fill( 0, count( $entries ), "(%s, %s, 'off')" ) ) . ' ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + CAST(VALUES(option_value) AS UNSIGNED)', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One placeholder group per count.
				$args
			)
		);
		if ( '' !== $wpdb->last_error ) {
			return;
		}

		foreach ( $entries as $method => $entry ) {
			$info  = isset( $infos[ $method ] ) ? $infos[ $method ] : null;
			$login = 'login' === $entry['outcome'];
			$due   = null === $info || ! isset( $info['last_at'] ) || $now - (int) $info['last_at'] >= self::TOUCH_INTERVAL;
			if ( ! $due && ! ( $login && ( ! isset( $info['login_user_agent'] ) || $user_agent !== $info['login_user_agent'] ) ) ) {
				continue;
			}
			$value = array(
				'method'           => $method,
				'provider'         => self::provider_of( $entry['callback'], $server_class ),
				'first_at'         => isset( $info['first_at'] ) ? (int) $info['first_at'] : $now,
				'last_at'          => $due ? $now : (int) $info['last_at'],
				'user_agent'       => $due ? $user_agent : ( isset( $info['user_agent'] ) ? (string) $info['user_agent'] : '' ),
				'login_at'         => $login ? $now : ( isset( $info['login_at'] ) ? (int) $info['login_at'] : 0 ),
				'login_user_agent' => $login ? $user_agent : ( isset( $info['login_user_agent'] ) ? (string) $info['login_user_agent'] : '' ),
			);
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One row per method, written as a whole; never cached.
				$wpdb->prepare(
					"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
					self::OPTION_PREFIX . md5( $method ) . '_info',
					maybe_serialize( $value )
				)
			);
		}
	}

	/**
	 * Returns how many methods are recorded (PHP_INT_MAX when the rows cannot be counted).
	 *
	 * @return int
	 */
	private static function count_methods() {
		global $wpdb;
		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Rows found by their name; never cached.
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( self::OPTION_PREFIX ) . '%' . $wpdb->esc_like( '_info' ) )
		);
		return '' === $wpdb->last_error ? (int) $count : PHP_INT_MAX;
	}

	/**
	 * Returns who added a method: core, plugin:<directory>, mu-plugin:<file>, theme:<directory> or ''.
	 *
	 * @param mixed  $callback     The method's callback, as plugins left it.
	 * @param string $server_class Class of the XML-RPC server ('this:' callbacks are its methods).
	 * @return string
	 */
	public static function provider_of( $callback, $server_class ) {
		try {
			if ( is_string( $callback ) && 0 === strpos( $callback, 'this:' ) ) {
				$reflection = new ReflectionMethod( $server_class, substr( $callback, 5 ) );
			} elseif ( is_array( $callback ) && isset( $callback[0], $callback[1] ) && is_string( $callback[1] ) && ( is_object( $callback[0] ) || is_string( $callback[0] ) ) ) {
				$reflection = new ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( $callback instanceof Closure || ( is_string( $callback ) && function_exists( $callback ) ) ) {
				$reflection = new ReflectionFunction( $callback );
			} else {
				return '';
			}
		} catch ( ReflectionException $e ) {
			return '';
		}
		$file = $reflection->getFileName();
		if ( ! is_string( $file ) ) {
			return '';
		}
		$file = wp_normalize_path( $file );
		// A plugin linked into the plugins directory: WordPress keeps where each link points.
		foreach ( isset( $GLOBALS['wp_plugin_paths'] ) && is_array( $GLOBALS['wp_plugin_paths'] ) ? $GLOBALS['wp_plugin_paths'] : array() as $dir => $real ) {
			$real = trailingslashit( wp_normalize_path( (string) $real ) );
			if ( 0 === strpos( $file, $real ) ) {
				$file = trailingslashit( wp_normalize_path( (string) $dir ) ) . substr( $file, strlen( $real ) );
				break;
			}
		}
		return self::provider_for_file(
			$file,
			array(
				'mu-plugin' => array( WPMU_PLUGIN_DIR ),
				'plugin'    => array( WP_PLUGIN_DIR ),
				'theme'     => array( get_theme_root() ),
				'core'      => array( ABSPATH . WPINC, ABSPATH . 'wp-admin' ),
			)
		);
	}

	/**
	 * Returns who a file belongs to.
	 *
	 * @param string                  $file        File (normalized path).
	 * @param array<string, string[]> $directories Type => directories, in the order to try.
	 * @return string core, <type>:<first path segment under the directory>, or ''.
	 */
	public static function provider_for_file( $file, array $directories ) {
		foreach ( $directories as $type => $paths ) {
			foreach ( (array) $paths as $path ) {
				$path = trailingslashit( wp_normalize_path( (string) $path ) );
				if ( '/' === $path || 0 !== strpos( $file, $path ) ) {
					continue;
				}
				if ( 'core' === $type ) {
					return 'core';
				}
				$segment = strtok( substr( $file, strlen( $path ) ), '/' );
				return false === $segment || '' === $segment ? '' : $type . ':' . $segment;
			}
		}
		return '';
	}

	/**
	 * Returns the record, newest call first.
	 *
	 * @param int|null $now Time (tests).
	 * @return array<int, array{method: string, provider: string, first_at: int, last_at: int, user_agent: string, login_at: int, login_user_agent: string, calls: int, outcomes: array<string, int>}>|null
	 *         Null when the record could not be read.
	 */
	public static function usage( $now = null ) {
		global $wpdb;
		$now  = null === $now ? time() : (int) $now;
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Rows found by their name prefix; never cached.
			$wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( self::OPTION_PREFIX ) . '%' ),
			ARRAY_A
		);
		if ( '' !== $wpdb->last_error || ! is_array( $rows ) ) {
			return null;
		}

		$infos  = array();
		$counts = array();
		$length = strlen( self::OPTION_PREFIX );
		foreach ( $rows as $row ) {
			$suffix = substr( (string) $row['option_name'], $length );
			if ( 1 !== preg_match( '/^([0-9a-f]{32})_([a-z]+)$/D', $suffix, $parts ) ) {
				continue;
			}
			if ( 'info' === $parts[2] ) {
				$info = maybe_unserialize( $row['option_value'] );
				if ( is_array( $info ) && isset( $info['method'], $info['last_at'] ) && is_string( $info['method'] ) && md5( $info['method'] ) === $parts[1] ) {
					$infos[ $parts[1] ] = $info;
				}
			} elseif ( in_array( $parts[2], self::OUTCOMES, true ) ) {
				$counts[ $parts[1] ][ $parts[2] ] = max( 0, (int) $row['option_value'] );
			}
		}

		$usage = array();
		foreach ( $infos as $hash => $info ) {
			if ( $now - (int) $info['last_at'] >= self::RETENTION ) {
				continue;
			}
			$outcomes = array_fill_keys( self::OUTCOMES, 0 );
			foreach ( isset( $counts[ $hash ] ) ? $counts[ $hash ] : array() as $outcome => $count ) {
				$outcomes[ $outcome ] = $count;
			}
			$usage[] = array(
				'method'           => $info['method'],
				'provider'         => isset( $info['provider'] ) && is_string( $info['provider'] ) ? $info['provider'] : '',
				'first_at'         => isset( $info['first_at'] ) ? (int) $info['first_at'] : (int) $info['last_at'],
				'last_at'          => (int) $info['last_at'],
				'user_agent'       => isset( $info['user_agent'] ) && is_string( $info['user_agent'] ) ? $info['user_agent'] : '',
				'login_at'         => isset( $info['login_at'] ) ? (int) $info['login_at'] : 0,
				'login_user_agent' => isset( $info['login_user_agent'] ) && is_string( $info['login_user_agent'] ) ? $info['login_user_agent'] : '',
				'calls'            => array_sum( $outcomes ),
				'outcomes'         => $outcomes,
			);
		}
		usort(
			$usage,
			static function ( $a, $b ) {
				$order = $b['last_at'] <=> $a['last_at'];
				return 0 !== $order ? $order : strcmp( $a['method'], $b['method'] );
			}
		);
		return $usage;
	}

	/**
	 * Returns what the Security tab shows.
	 *
	 * @return array{enabled: bool, allow: string[], readable: bool, usage: array}
	 *         usage: usage() with the provider as {type, name}, whether the
	 *         method is allowed, and whether the guard can refuse it.
	 */
	public static function view() {
		$allow = self::allow_list();
		$usage = self::usage();
		$view  = array(
			'enabled'  => self::is_enabled(),
			'allow'    => $allow,
			'readable' => null !== $usage,
			'usage'    => array(),
		);
		foreach ( null === $usage ? array() : $usage as $entry ) {
			$entry['provider']  = self::describe_provider( $entry['provider'] );
			$entry['allowed']   = self::is_allowed( $entry['method'], $allow );
			$entry['refusable'] = ! in_array( $entry['method'], self::ALWAYS_ANSWERED, true );
			$view['usage'][]    = $entry;
		}
		return $view;
	}

	/**
	 * Returns a provider as the Security tab shows it.
	 *
	 * @param string $provider core, plugin:<directory>, mu-plugin:<file>, theme:<directory> or ''.
	 * @return array{type: string, name: string} type: core, plugin, mu-plugin, theme or unknown.
	 */
	public static function describe_provider( $provider ) {
		$parts = explode( ':', (string) $provider, 2 );
		$type  = $parts[0];
		$slug  = isset( $parts[1] ) ? $parts[1] : '';
		if ( 'core' === $type ) {
			return array(
				'type' => 'core',
				'name' => 'WordPress',
			);
		}
		if ( ! in_array( $type, array( 'plugin', 'mu-plugin', 'theme' ), true ) || '' === $slug ) {
			return array(
				'type' => 'unknown',
				'name' => '',
			);
		}
		$name = $slug;
		if ( 'theme' === $type ) {
			$theme = wp_get_theme( $slug );
			if ( $theme->exists() ) {
				$name = $theme->get( 'Name' );
			}
		} else {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = 'plugin' === $type ? get_plugins() : get_mu_plugins();
			foreach ( $plugins as $file => $data ) {
				if ( ( $file === $slug || 0 === strpos( $file, $slug . '/' ) ) && ! empty( $data['Name'] ) ) {
					$name = $data['Name'];
					break;
				}
			}
		}
		return array(
			'type' => $type,
			'name' => (string) $name,
		);
	}

	/**
	 * Removes the methods not called for RETENTION (delete_expired_transients).
	 *
	 * @param mixed    $force_db Passed by the action; not used.
	 * @param int|null $now      Time (tests).
	 */
	public static function delete_expired( $force_db = null, $now = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- The action's argument.
		global $wpdb;
		$now  = null === $now ? time() : (int) $now;
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Rows found by their name; never cached.
			$wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( self::OPTION_PREFIX ) . '%' . $wpdb->esc_like( '_info' ) ),
			ARRAY_A
		);
		if ( '' !== $wpdb->last_error || ! is_array( $rows ) ) {
			return;
		}
		$length = strlen( self::OPTION_PREFIX );
		foreach ( $rows as $row ) {
			$hash = substr( (string) $row['option_name'], $length, 32 );
			$info = maybe_unserialize( $row['option_value'] );
			if ( 1 === preg_match( '/^[0-9a-f]{32}$/D', $hash ) && ( ! is_array( $info ) || ! isset( $info['last_at'] ) || $now - (int) $info['last_at'] >= self::RETENTION ) ) {
				self::delete_rows( self::OPTION_PREFIX . $hash . '_' );
			}
		}
	}

	/**
	 * Removes the whole record of the current site.
	 */
	public static function delete_records() {
		self::delete_rows( self::OPTION_PREFIX );
	}

	/**
	 * Removes the settings and the record of every site (uninstall).
	 */
	public static function uninstall() {
		if ( ! is_multisite() ) {
			self::delete_site_data();
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
				self::delete_site_data();
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Removes the settings and the record of the current site.
	 */
	private static function delete_site_data() {
		delete_option( self::SETTING );
		delete_option( self::ALLOW_SETTING );
		self::delete_records();
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
	 * Whether this request is an XML-RPC request.
	 *
	 * @return bool
	 */
	private static function is_xmlrpc_request() {
		if ( null !== self::$xmlrpc_request ) {
			return self::$xmlrpc_request;
		}
		return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
	}
}

JP4WC_XMLRPC_Guard::init();
