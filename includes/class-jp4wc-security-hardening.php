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
 * Two settings of the Security tab, off until turned on, that close what
 * checks A-05 and M-01 of the security check find open:
 *
 * - The users REST routes (/wp/v2/users and below) are not registered for a
 *   visitor who is not logged in, so the route answers 404 and is not in the
 *   index either. Logged-in users keep WordPress's own permissions.
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
	 * Pattern of the REST routes hidden from visitors.
	 */
	const USERS_ROUTE_PATTERN = '#^/wp/v2/users(?:/|$)#';

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
	 * Defines DISALLOW_FILE_EDIT when the setting is on and nothing defined it yet.
	 *
	 * Runs when the file loads (plugins_loaded): wp-admin/menu.php and
	 * map_meta_cap() read the constant later in the request. Code that reads
	 * it before plugins load (a must-use plugin) does not see it.
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
	}
}

JP4WC_Security_Hardening::init();
