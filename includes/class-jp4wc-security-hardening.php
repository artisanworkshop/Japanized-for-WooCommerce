<?php
/**
 * Security hardening options.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JP4WC_Security_Hardening
 *
 * Three settings of the Security tab, off until turned on, that close what
 * checks A-05, A-06 and M-01 of the security check find open:
 *
 * - The users REST routes (/wp/v2/users and below) are not registered for a
 *   visitor who is not logged in, so the route answers 404 and is not in the
 *   index either. Logged-in users keep WordPress's own permissions.
 * - User names are kept from visitors who are not logged in (3.1.0): author
 *   archives, ?author=N included, answer 404, author links point to the home
 *   page, and the users sitemap, the comment classes and the oEmbed author
 *   leave the names out. For everyone, a failed login, a password reset and
 *   a REST API request with a wrong application password answer the same
 *   whether the user exists or not.
 * - DISALLOW_FILE_EDIT is defined when this file loads (plugins_loaded), unless
 *   wp-config.php or another plugin defined it already, so the theme and
 *   plugin file editors are gone from the admin screens.
 *
 * @since 3.0.0
 */
class JP4WC_Security_Hardening {

	/**
	 * Setting that hides the users REST routes from visitors ('1' on, '' off).
	 */
	const REST_USERS_SETTING = 'wc4jp-hide-rest-users';

	/**
	 * Setting that forbids file editing in the admin screens ('1' on, '' off).
	 */
	const FILE_EDIT_SETTING = 'wc4jp-disallow-file-edit';

	/**
	 * Setting that keeps user names from visitors ('1' on, '' off).
	 *
	 * @since 3.1.0
	 */
	const USER_NAMES_SETTING = 'wc4jp-hide-user-names';

	/**
	 * Pattern of the REST routes hidden from visitors.
	 */
	const USERS_ROUTE_PATTERN = '#^/wp/v2/users(?:/|$)#';

	/**
	 * Login errors that tell whether the user exists, answered as one.
	 *
	 * @since 3.1.0
	 */
	const LOGIN_ERROR_CODES = array( 'invalid_username', 'invalid_email', 'incorrect_password' );

	/**
	 * Errors of the REST API's application password check that tell whether the user exists.
	 *
	 * @since 3.1.0
	 */
	const REST_ERROR_CODES = array( 'invalid_username', 'invalid_email', 'incorrect_password', 'application_passwords_disabled_for_user' );

	/**
	 * Priority on authenticate: after WordPress's own checks (20, 30), the login attempt limiter (50) and the spam check (99).
	 *
	 * @since 3.1.0
	 */
	const AUTHENTICATE_PRIORITY = 100;

	/**
	 * Priority on rest_authentication_errors: after WordPress's application password check (90).
	 *
	 * @since 3.1.0
	 */
	const REST_ERRORS_PRIORITY = 100;

	/**
	 * Whether this class defined DISALLOW_FILE_EDIT in this request.
	 *
	 * @var bool
	 */
	private static $defined_file_edit = false;

	/**
	 * Registers the hooks and defines the constant when the setting is on.
	 */
	public static function init() {
		add_filter( 'rest_endpoints', array( __CLASS__, 'hide_users_routes' ), 100 );
		add_filter( 'request', array( __CLASS__, 'close_author_archives' ) );
		add_filter( 'author_link', array( __CLASS__, 'author_link' ) );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'sitemap_provider' ), 10, 2 );
		add_filter( 'comment_class', array( __CLASS__, 'comment_class' ) );
		add_filter( 'oembed_response_data', array( __CLASS__, 'oembed_author' ) );
		add_filter( 'authenticate', array( __CLASS__, 'same_login_error' ), self::AUTHENTICATE_PRIORITY );
		// Last, so that the errors of other plugins (a CAPTCHA) are in by then. WooCommerce's form fires only lostpassword_post.
		add_action( 'lostpassword_post', array( __CLASS__, 'unknown_user_reset' ), PHP_INT_MAX, 2 );
		// wp-login.php: retrieve_password() applies lostpassword_errors after lostpassword_post.
		add_filter( 'lostpassword_errors', array( __CLASS__, 'unknown_user_reset_errors' ), PHP_INT_MAX, 2 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'same_rest_auth_error' ), self::REST_ERRORS_PRIORITY );
		self::define_file_edit();
	}

	/**
	 * Whether the users REST routes are hidden from visitors.
	 *
	 * @return bool
	 */
	public static function hides_rest_users() {
		return '1' === get_option( self::REST_USERS_SETTING, '' );
	}

	/**
	 * Whether user names are kept from visitors.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	public static function hides_user_names() {
		return '1' === get_option( self::USER_NAMES_SETTING, '' );
	}

	/**
	 * Whether the setting is on and the current request is a visitor's, who is not logged in.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	private static function hides_user_names_from_visitor() {
		return self::hides_user_names() && ! is_user_logged_in();
	}

	/**
	 * Whether the setting that forbids file editing is on.
	 *
	 * @return bool
	 */
	public static function disallows_file_edit() {
		return '1' === get_option( self::FILE_EDIT_SETTING, '' );
	}

	/**
	 * Whether file editing is forbidden by something other than this setting:
	 * DISALLOW_FILE_EDIT or DISALLOW_FILE_MODS defined before this plugin
	 * loaded (wp-config.php, a must-use plugin).
	 *
	 * @param array|null $constants Constants to look at instead of the defined ones (tests): name => value.
	 * @return bool
	 */
	public static function file_edit_defined_elsewhere( $constants = null ) {
		foreach ( array( 'DISALLOW_FILE_EDIT', 'DISALLOW_FILE_MODS' ) as $name ) {
			if ( is_array( $constants ) ) {
				if ( array_key_exists( $name, $constants ) && $constants[ $name ] ) {
					return true;
				}
				continue;
			}
			if ( 'DISALLOW_FILE_EDIT' === $name && self::$defined_file_edit ) {
				continue;
			}
			if ( defined( $name ) && constant( $name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether file editing is forbidden on this request, whoever defined the constant.
	 *
	 * @return bool
	 */
	public static function file_edit_forbidden() {
		return ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) || ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS );
	}

	/**
	 * Whether DISALLOW_FILE_EDIT is defined as false (wp-config.php), which the setting cannot change.
	 *
	 * @return bool
	 */
	public static function file_edit_allowed_by_constant() {
		return defined( 'DISALLOW_FILE_EDIT' ) && ! DISALLOW_FILE_EDIT && ! self::$defined_file_edit;
	}

	/**
	 * Removes the users REST routes for a visitor who is not logged in (rest_endpoints).
	 *
	 * The filter runs in WP_REST_Server::get_routes(), after the request was
	 * authenticated, so a cookie, an application password or a plugin's
	 * token has set the user by then. A removed route answers 404.
	 *
	 * @param array $endpoints Routes.
	 * @return array
	 */
	public static function hide_users_routes( $endpoints ) {
		if ( ! is_array( $endpoints ) || is_user_logged_in() || ! self::hides_rest_users() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( is_string( $route ) && 1 === preg_match( self::USERS_ROUTE_PATTERN, $route ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Answers author archives with 404 for a visitor (request).
	 *
	 * ?author=N is redirected to /author/<user_nicename>/ by redirect_canonical()
	 * and shows the name in the body class even without a redirect, and the
	 * archive by name tells which names exist. Both query variables lead to
	 * the 404 page before the query runs: redirect_canonical() redirects only
	 * an author archive, and WP::send_headers() sends the 404 status. REST
	 * requests keep their parameters; an empty rest_route is not one, as for
	 * rest_api_loaded(), and is shown as a page.
	 *
	 * @since 3.1.0
	 * @param array $query_vars Query variables of the request.
	 * @return array
	 */
	public static function close_author_archives( $query_vars ) {
		if ( ! is_array( $query_vars ) || ! empty( $query_vars['rest_route'] ) ) {
			return $query_vars;
		}
		if ( empty( $query_vars['author'] ) && empty( $query_vars['author_name'] ) ) {
			return $query_vars;
		}
		return self::hides_user_names_from_visitor() ? array( 'error' => '404' ) : $query_vars;
	}

	/**
	 * Points author links at the home page for a visitor (author_link), as the archives are closed.
	 *
	 * @since 3.1.0
	 * @param string $link Author archive URL, with the user_nicename.
	 * @return string
	 */
	public static function author_link( $link ) {
		return self::hides_user_names_from_visitor() ? home_url( '/' ) : $link;
	}

	/**
	 * Leaves the users sitemap out for a visitor (wp_sitemaps_add_provider).
	 *
	 * @since 3.1.0
	 * @param WP_Sitemaps_Provider|false $provider Provider.
	 * @param string                     $name     Provider name.
	 * @return WP_Sitemaps_Provider|false
	 */
	public static function sitemap_provider( $provider, $name ) {
		return 'users' === $name && self::hides_user_names_from_visitor() ? false : $provider;
	}

	/**
	 * Removes the comment-author-<user_nicename> class for a visitor (comment_class).
	 *
	 * WooCommerce's product reviews use the same classes.
	 *
	 * @since 3.1.0
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public static function comment_class( $classes ) {
		if ( ! is_array( $classes ) || ! self::hides_user_names_from_visitor() ) {
			return $classes;
		}
		return array_values(
			array_filter(
				$classes,
				static function ( $name ) {
					return ! is_string( $name ) || 0 !== strpos( $name, 'comment-author-' );
				}
			)
		);
	}

	/**
	 * Gives the oEmbed response of a post the site as its author for a visitor (oembed_response_data).
	 *
	 * The site name and home URL are what WordPress gives a post without an author.
	 *
	 * @since 3.1.0
	 * @param array $data oEmbed response data.
	 * @return array
	 */
	public static function oembed_author( $data ) {
		if ( ! is_array( $data ) || ! self::hides_user_names_from_visitor() ) {
			return $data;
		}
		if ( array_key_exists( 'author_name', $data ) ) {
			$data['author_name'] = get_bloginfo( 'name' );
		}
		if ( array_key_exists( 'author_url', $data ) ) {
			$data['author_url'] = get_home_url();
		}
		return $data;
	}

	/**
	 * Answers a wrong user name, e-mail address or password with the same error (authenticate, 100).
	 *
	 * The code is the same too: wp-login.php keeps the name in the field only
	 * for incorrect_password and empties it for invalid_username. Logins of
	 * the WooCommerce forms go through wp_signon() and get the same error.
	 * Other errors, the lockout of the login attempt limiter among them, are
	 * left as they are; other codes added to the same error are kept.
	 *
	 * @since 3.1.0
	 * @param WP_User|WP_Error|null $user Result so far.
	 * @return WP_User|WP_Error|null
	 */
	public static function same_login_error( $user ) {
		if ( ! is_wp_error( $user ) || ! in_array( $user->get_error_code(), self::LOGIN_ERROR_CODES, true ) || ! self::hides_user_names() ) {
			return $user;
		}
		$build = static function () {
			return sprintf(
				/* translators: %s: URL of the lost password page */
				__( '<strong>Error:</strong> The username, email address or password is incorrect. <a href="%s">Lost your password?</a>', 'woocommerce-for-japan' ),
				esc_url( wp_lostpassword_url() )
			);
		};
		// Shown to shoppers: the bundled translation stands in until the language pack has the string.
		$error = new WP_Error( 'incorrect_password', function_exists( 'jp4wc_with_bundled_translation' ) ? jp4wc_with_bundled_translation( $build ) : $build() );
		foreach ( $user->get_error_codes() as $code ) {
			if ( in_array( $code, self::LOGIN_ERROR_CODES, true ) ) {
				continue;
			}
			foreach ( $user->get_error_messages( $code ) as $message ) {
				$error->add( $code, $message );
			}
			$data = $user->get_error_data( $code );
			if ( null !== $data ) {
				$error->add_data( $data, $code );
			}
		}
		return $error;
	}

	/**
	 * Sends a request of WooCommerce's lost password form for an unknown user to the page of a sent one (lostpassword_post, last).
	 *
	 * WooCommerce does not apply lostpassword_errors, so its form is decided here.
	 *
	 * @since 3.1.0
	 * @param WP_Error            $errors    Errors so far.
	 * @param WP_User|false|mixed $user_data The user, false when no user matched.
	 */
	public static function unknown_user_reset( $errors, $user_data = false ) {
		if ( ! self::is_woocommerce_reset() ) {
			return;
		}
		$url = self::reset_sent_url( $errors, $user_data );
		if ( null === $url ) {
			return;
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Answers a wp-login.php request for an unknown user as a known one (lostpassword_errors, last).
	 *
	 * Runs after every lostpassword_post callback and every other filter, so
	 * that a request another plugin refused (a CAPTCHA) is refused the same
	 * way for both: its error stays, and WordPress's invalid_email, which it
	 * adds for an unknown address before the action, is removed. A request no
	 * one refused goes to the page of a sent one.
	 *
	 * @since 3.1.0
	 * @param WP_Error            $errors    Errors so far.
	 * @param WP_User|false|mixed $user_data The user, false when no user matched.
	 * @return WP_Error
	 */
	public static function unknown_user_reset_errors( $errors, $user_data = false ) {
		if ( $user_data || ! $errors instanceof WP_Error || ! self::hides_user_names() ) {
			return $errors;
		}
		if ( array() !== array_diff( $errors->get_error_codes(), array( 'invalid_email' ) ) ) {
			$errors->remove( 'invalid_email' );
			return $errors;
		}
		if ( ! self::is_login_page_reset() ) {
			return $errors;
		}
		$url = self::reset_sent_url( $errors, $user_data );
		if ( null === $url ) {
			return $errors;
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Whether WooCommerce's lost password form is being handled.
	 *
	 * WC_Form_Handler::process_lost_password() (wp_loaded) checked the nonce
	 * before it called WC_Shortcode_My_Account::retrieve_password(). A request
	 * of the login page is not one, even with WooCommerce's field added to it
	 * when a plugin loads the login page from wp_loaded.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	private static function is_woocommerce_reset() {
		return ! self::is_login_page_reset() && doing_action( 'wp_loaded' ) && isset( $_POST['wc_reset_password'] ) && function_exists( 'wc_get_account_endpoint_url' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only tells WooCommerce's form apart; its nonce is checked by WooCommerce.
	}

	/**
	 * Whether wp-login.php's lost password form is being handled.
	 *
	 * The login page (wp-login.php) fires login_form_lostpassword, or
	 * login_form_retrievepassword, before it calls retrieve_password(), also
	 * when a plugin that moves the login page loads it from a hook (WPS Hide
	 * Login does from wp_loaded).
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	private static function is_login_page_reset() {
		global $pagenow;
		return 'wp-login.php' === $pagenow && ( did_action( 'login_form_lostpassword' ) || did_action( 'login_form_retrievepassword' ) );
	}

	/**
	 * Returns where a password reset request for an unknown user goes, as if it had been sent.
	 *
	 * The address is the one the form goes to after sending: wp-login.php's
	 * redirect_to or ?checkemail=confirm, and WooCommerce's lost-password
	 * endpoint with reset-link-sent. Other callers of retrieve_password(),
	 * and a request another plugin refused (an error other than WordPress's
	 * own invalid_email for an unknown address), get null.
	 *
	 * @since 3.1.0
	 * @param WP_Error            $errors    Errors so far.
	 * @param WP_User|false|mixed $user_data The user, false when no user matched.
	 * @return string|null Null to let the request go on.
	 */
	public static function reset_sent_url( $errors, $user_data ) {
		if ( $user_data || ! self::hides_user_names() ) {
			return null;
		}
		if ( $errors instanceof WP_Error && array() !== array_diff( $errors->get_error_codes(), array( 'invalid_email' ) ) ) {
			return null;
		}
		if ( self::is_woocommerce_reset() ) {
			return add_query_arg( 'reset-link-sent', 'true', wc_get_account_endpoint_url( 'lost-password' ) );
		}
		if ( self::is_login_page_reset() ) {
			// The address wp-login.php sends a request it sent to.
			return ! empty( $_REQUEST['redirect_to'] ) ? wp_sanitize_redirect( wp_unslash( $_REQUEST['redirect_to'] ) ) : 'wp-login.php?checkemail=confirm'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- wp-login.php reads it the same way; wp_safe_redirect() validates it.
		}
		return null;
	}

	/**
	 * Answers a wrong user name, e-mail address or application password in the REST API with the same error (rest_authentication_errors, 100).
	 *
	 * WordPress returns the error of the Basic authentication with an
	 * application password as is (rest_application_password_check_errors(),
	 * 90), whose code tells whether the user exists.
	 *
	 * @since 3.1.0
	 * @param WP_Error|null|true $result Result so far.
	 * @return WP_Error|null|true
	 */
	public static function same_rest_auth_error( $result ) {
		if ( ! is_wp_error( $result ) || ! in_array( $result->get_error_code(), self::REST_ERROR_CODES, true ) || ! self::hides_user_names() ) {
			return $result;
		}
		$build = static function () {
			return __( 'The username, email address or password is incorrect.', 'woocommerce-for-japan' );
		};
		return new WP_Error(
			'incorrect_password',
			function_exists( 'jp4wc_with_bundled_translation' ) ? jp4wc_with_bundled_translation( $build ) : $build(),
			array( 'status' => 401 )
		);
	}

	/**
	 * Defines DISALLOW_FILE_EDIT when the setting is on and nothing defined it yet.
	 *
	 * Runs when the file loads (plugins_loaded): wp-admin/menu.php and
	 * map_meta_cap() read the constant later in the request. Code that reads
	 * it before plugins load (a must-use plugin) does not see it. A constant
	 * lasts for the process: on a long-lived PHP worker, turning the setting
	 * off takes effect when the worker restarts (the safe direction).
	 *
	 * @return bool Whether the constant was defined by this call.
	 */
	public static function define_file_edit() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) || ! self::disallows_file_edit() ) {
			return false;
		}
		define( 'DISALLOW_FILE_EDIT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core's own constant, on purpose.
		self::$defined_file_edit = true;
		return true;
	}

	/**
	 * Removes the settings of every site (uninstall).
	 */
	public static function uninstall() {
		if ( ! is_multisite() ) {
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
				self::delete_settings();
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Removes the settings of the current site.
	 */
	private static function delete_settings() {
		delete_option( self::REST_USERS_SETTING );
		delete_option( self::FILE_EDIT_SETTING );
		delete_option( self::USER_NAMES_SETTING );
	}
}

JP4WC_Security_Hardening::init();
