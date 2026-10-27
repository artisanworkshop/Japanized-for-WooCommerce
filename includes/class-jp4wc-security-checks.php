<?php
/**
 * Local checks of the security check.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.AlternativeFunctions -- Reads the first bytes of files in the uploads directory and of .htaccess; WP_Filesystem may not be set up during cron.

require_once __DIR__ . '/class-jp4wc-security-probe.php';
require_once __DIR__ . '/class-jp4wc-security-scanner.php';

/**
 * JP4WC_Security_Checks
 *
 * The local checks of the four requirements of the Japanese credit card
 * security guidelines (vulnerabilities, admin access, data exposure and
 * malware) and the score of each requirement.
 *
 * A check reports only pass, fail or unknown: the values, paths and file
 * names behind it are never kept, as the free check sends only the check ID
 * and the status (tests/fixtures/security-feed/). Unknown, a check that could
 * not be run, costs no points. The checks scan the uploads directory and send
 * requests to the site itself, so they run in WP-Cron, never in an admin request.
 */
class JP4WC_Security_Checks {

	/**
	 * Points a failed check takes off its requirement's score, by severity.
	 */
	const POINTS = array(
		'critical' => 25,
		'high'     => 15,
		'medium'   => 8,
		'low'      => 3,
	);

	/**
	 * Requirements, in the order of the score.
	 */
	const REQUIREMENTS = array( 'vuln', 'admin', 'data', 'malware' );

	/**
	 * The checks in the order they run: requirement, severity and the method that runs it.
	 *
	 * The IDs are those of the contract. V-01 takes its severity from the most
	 * severe vulnerability found.
	 */
	const CHECKS = array(
		'V-01' => array(
			'requirement' => 'vuln',
			'severity'    => 'high',
			'method'      => 'known_vulnerabilities',
		),
		'V-02' => array(
			'requirement' => 'vuln',
			'severity'    => 'medium',
			'method'      => 'neglected_updates',
		),
		'V-03' => array(
			'requirement' => 'vuln',
			'severity'    => 'high',
			'method'      => 'end_of_life',
		),
		'V-04' => array(
			'requirement' => 'vuln',
			'severity'    => 'low',
			'method'      => 'automatic_updates',
		),
		'A-01' => array(
			'requirement' => 'admin',
			'severity'    => 'medium',
			'method'      => 'admin_username',
		),
		'A-02' => array(
			'requirement' => 'admin',
			'severity'    => 'medium',
			'method'      => 'login_protection',
		),
		'A-03' => array(
			'requirement' => 'admin',
			'severity'    => 'high',
			'method'      => 'two_factor',
		),
		'A-04' => array(
			'requirement' => 'admin',
			'severity'    => 'medium',
			'method'      => 'xmlrpc',
		),
		'A-05' => array(
			'requirement' => 'admin',
			'severity'    => 'low',
			'method'      => 'rest_users',
		),
		'D-01' => array(
			'requirement' => 'data',
			'severity'    => 'high',
			'method'      => 'directory_listing',
		),
		'D-02' => array(
			'requirement' => 'data',
			'severity'    => 'critical',
			'method'      => 'exposed_files',
		),
		'D-03' => array(
			'requirement' => 'data',
			'severity'    => 'medium',
			'method'      => 'debug_display',
		),
		'D-04' => array(
			'requirement' => 'data',
			'severity'    => 'high',
			'method'      => 'log_files',
		),
		'D-05' => array(
			'requirement' => 'data',
			'severity'    => 'critical',
			'method'      => 'https_pages',
		),
		'M-01' => array(
			'requirement' => 'malware',
			'severity'    => 'low',
			'method'      => 'file_editing',
		),
		'M-02' => array(
			'requirement' => 'malware',
			'severity'    => 'medium',
			'method'      => 'security_plugin',
		),
		'M-03' => array(
			'requirement' => 'malware',
			'severity'    => 'high',
			'method'      => 'php_in_uploads',
		),
	);

	/**
	 * Plugins the checks recognise, by category (directory names on WordPress.org).
	 *
	 * The `jp4wc_security_known_plugins` filter adds others.
	 */
	const KNOWN_PLUGINS = array(
		// A-02: limit login attempts, hide or restrict the login page.
		'login'    => array(
			'all-in-one-wp-security-and-firewall',
			'better-wp-security',
			'defender-security',
			'limit-login-attempts',
			'limit-login-attempts-reloaded',
			'login-lockdown',
			'loginizer',
			'ninjafirewall',
			'rename-wp-login',
			'siteguard',
			'wordfence',
			'wp-cerber',
			'wp-limit-login-attempts',
			'wp-simple-firewall',
			'wps-hide-login',
			'xo-security',
		),
		// A-03: two-factor authentication.
		'2fa'      => array(
			'all-in-one-wp-security-and-firewall',
			'better-wp-security',
			'duo-wordpress',
			'google-authenticator',
			'miniorange-2-factor-authentication',
			'two-factor',
			'two-factor-authentication',
			'wordfence',
			'wordfence-login-security',
			'wp-2fa',
			'wp-cerber',
			'wp-simple-firewall',
		),
		// M-02: malware scanning or a web application firewall.
		'security' => array(
			'all-in-one-wp-security-and-firewall',
			'better-wp-security',
			'bulletproof-security',
			'defender-security',
			'gotmls',
			'jetpack-protect',
			'malcare-security',
			'ninjafirewall',
			'quttera-web-malware-scanner',
			'security-malware-firewall',
			'sucuri-scanner',
			'wordfence',
			'wp-cerber',
			'wp-simple-firewall',
		),
	);

	/**
	 * A plugin with an update that has not been updated for this long fails V-02 (180 days).
	 */
	const NEGLECTED_AFTER = 15552000;

	/**
	 * Entries of the uploads directory M-03 looks at before it gives up.
	 */
	const SCAN_LIMIT = 50000;

	/**
	 * Directory levels under the uploads directory M-03 looks into.
	 */
	const SCAN_DEPTH = 8;

	/**
	 * Bytes of a PHP file read to tell whether it can run code. A larger file is
	 * judged by what this much of it shows, and is unknown when that is not enough.
	 */
	const PHP_HEAD_BYTES = 65536;

	/**
	 * Directories D-02 sends requests for (WordPress, wp-content, uploads), and
	 * kinds of file per directory it requests.
	 */
	const EXPOSED_DIRECTORIES = 3;
	const EXPOSED_PROBES      = 3;

	/**
	 * Bytes of an .htaccess file read.
	 */
	const HTACCESS_BYTES = 65536;

	/**
	 * File names M-03 treats as PHP that a web server may run.
	 */
	const PHP_FILE_PATTERN = '/\.(?:php[0-9]?|phtml|phar)$/iD';

	/**
	 * Tokens that start a declaration, which runs nothing by itself.
	 */
	const DECLARATIONS = array( T_NAMESPACE, T_USE, T_DECLARE, T_CLASS, T_ABSTRACT, T_FINAL, T_READONLY, T_INTERFACE, T_TRAIT, T_ENUM, T_FUNCTION, T_CONST );

	/**
	 * File names D-02 reports in a public directory: database dumps, archives,
	 * backups, logs and copies of wp-config.php. Other .gz files, such as
	 * sitemap.xml.gz, are meant to be public.
	 */
	const EXPOSED_FILE_PATTERN = '/^(?:.+\.(?:sql|sql\.gz|tar|tar\.gz|tgz|zip|7z|rar|bak|old|orig|save|swp|log)|wp-config\.php.+|error_log)$/iD';

	/**
	 * Runs every check.
	 *
	 * The context replaces what a check reads from the site, which the tests
	 * use; the security check passes only the data of the vulnerability feed.
	 *
	 * @param array $context Optional values:
	 *                       - vulnerabilities: array( 'items' => matched software, 'stale' => bool ),
	 *                         or null when no vulnerability data was matched (V-01).
	 *                       - eol: name (php, WordPress, woocommerce) => oldest supported version, and
	 *                         eol_stale: whether they come from out-of-date data (V-03).
	 *                       - versions: name => running version, null when not installed (V-03).
	 *                       - constants: constant name => value (V-04, D-03, D-04, M-01).
	 *                       - plugin_updates: the update_plugins transient (V-02, V-04); plugin_dir (V-02);
	 *                         auto_update_plugins: plugin files with auto-updates on (V-04).
	 *                       - active_plugins: active plugin slugs (A-02, A-03, M-02).
	 *                       - abspath, content_dir, public_directories (directory => its URL, or
	 *                         null) (A-02, D-02); uploads: basedir and baseurl (D-01, D-02, D-04,
	 *                         M-03); scan_limit (D-02, D-04, M-03); shop_urls (D-05).
	 *                       - probe: the JP4WC_Security_Probe of the run.
	 * @return array<int, array{check_id: string, requirement: string, severity: string, status: string}>
	 */
	public static function run( array $context = array() ) {
		if ( ! isset( $context['probe'] ) || ! $context['probe'] instanceof JP4WC_Security_Probe ) {
			$context['probe'] = new JP4WC_Security_Probe();
		}
		$checks = array();
		foreach ( array_keys( self::CHECKS ) as $check_id ) {
			$checks[] = self::run_check( $check_id, $context );
		}
		return $checks;
	}

	/**
	 * Runs one check.
	 *
	 * @param string $check_id Check ID.
	 * @param array  $context  As for run().
	 * @return array{check_id: string, requirement: string, severity: string, status: string}|null Null for an unknown ID.
	 */
	public static function run_check( $check_id, array $context = array() ) {
		if ( ! is_string( $check_id ) || ! array_key_exists( $check_id, self::CHECKS ) ) {
			return null;
		}
		if ( ! isset( $context['probe'] ) || ! $context['probe'] instanceof JP4WC_Security_Probe ) {
			$context['probe'] = new JP4WC_Security_Probe();
		}

		$check  = self::CHECKS[ $check_id ];
		$method = $check['method'];
		try {
			$status = self::$method( $context );
		} catch ( Throwable $e ) {
			// A check that breaks on an unexpected site must not stop the others, nor the
			// vulnerability findings saved with them.
			$status = 'unknown';

			/**
			 * Fires when a local check of the security check throws; the check is reported as unknown.
			 *
			 * @since 3.0.0
			 * @param string    $check_id Check ID.
			 * @param Throwable $error    What the check threw.
			 */
			do_action( 'jp4wc_security_check_error', $check_id, $e );
		}

		return array(
			'check_id'    => $check_id,
			'requirement' => $check['requirement'],
			'severity'    => 'V-01' === $check_id ? self::vulnerability_severity( $context ) : $check['severity'],
			'status'      => $status,
		);
	}

	/**
	 * Returns the score of each requirement and the total.
	 *
	 * Each requirement starts at 100 and loses the points of each failed check,
	 * down to 0. Passed and unknown checks cost nothing. The total is the
	 * lowest requirement score: the weakest requirement decides.
	 *
	 * @param array $checks Checks as run() returns them.
	 * @return array{total: int, vuln: int, admin: int, data: int, malware: int}
	 */
	public static function score( array $checks ) {
		$scores = array_fill_keys( self::REQUIREMENTS, 100 );
		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) || ! isset( $check['status'], $check['requirement'], $check['severity'] ) || 'fail' !== $check['status'] ) {
				continue;
			}
			if ( ! is_string( $check['requirement'] ) || ! array_key_exists( $check['requirement'], $scores ) || ! is_string( $check['severity'] ) || ! array_key_exists( $check['severity'], self::POINTS ) ) {
				continue;
			}
			$scores[ $check['requirement'] ] = max( 0, $scores[ $check['requirement'] ] - self::POINTS[ $check['severity'] ] );
		}
		return array_merge( array( 'total' => min( $scores ) ), $scores );
	}

	/**
	 * V-01: installed software with a known vulnerability.
	 *
	 * Unknown without vulnerability data, and when out-of-date data found nothing.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function known_vulnerabilities( array $context ) {
		$items = self::vulnerability_items( $context );
		if ( null === $items ) {
			return 'unknown';
		}
		if ( array() !== $items ) {
			return 'fail';
		}
		return empty( $context['vulnerabilities']['stale'] ) ? 'pass' : 'unknown';
	}

	/**
	 * Returns the severity of V-01: that of the most severe vulnerability found.
	 *
	 * A record without a CVSS rating counts as medium.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function vulnerability_severity( array $context ) {
		$items = self::vulnerability_items( $context );
		if ( null === $items || array() === $items ) {
			return self::CHECKS['V-01']['severity'];
		}
		switch ( JP4WC_Security_Scanner::max_rating( $items ) ) {
			case 'Critical':
				return 'critical';
			case 'High':
				return 'high';
			case 'Low':
			case 'None':
				return 'low';
			default:
				return 'medium';
		}
	}

	/**
	 * Returns the matched software of the context.
	 *
	 * @param array $context Context.
	 * @return array|null Null without vulnerability data.
	 */
	private static function vulnerability_items( array $context ) {
		if ( ! isset( $context['vulnerabilities']['items'] ) || ! is_array( $context['vulnerabilities']['items'] ) ) {
			return null;
		}
		return $context['vulnerabilities']['items'];
	}

	/**
	 * V-02: a plugin with an update available that this site has not updated for 180 days.
	 *
	 * WordPress does not record when an update was released or installed, so
	 * the age is that of the plugin's main file, which an update replaces.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function neglected_updates( array $context ) {
		$updates = array_key_exists( 'plugin_updates', $context ) ? $context['plugin_updates'] : get_site_transient( 'update_plugins' );
		if ( ! is_object( $updates ) || ! isset( $updates->last_checked ) ) {
			// WordPress has not looked for updates (or cannot).
			return 'unknown';
		}
		$response   = isset( $updates->response ) && is_array( $updates->response ) ? $updates->response : array();
		$plugin_dir = isset( $context['plugin_dir'] ) ? (string) $context['plugin_dir'] : WP_PLUGIN_DIR;
		$undecided  = false;

		foreach ( array_keys( $response ) as $file ) {
			$file = (string) $file;
			if ( '' === $file || false !== strpos( $file, '..' ) ) {
				continue;
			}
			$path = untrailingslashit( $plugin_dir ) . '/' . $file;
			if ( ! is_file( $path ) ) {
				continue;
			}
			$modified = filemtime( $path );
			if ( false === $modified ) {
				// The age of this plugin cannot be told (the file is being replaced, for example).
				$undecided = true;
			} elseif ( time() - $modified >= self::NEGLECTED_AFTER ) {
				return 'fail';
			}
		}
		return $undecided ? 'unknown' : 'pass';
	}

	/**
	 * V-03: PHP, WordPress or WooCommerce older than the oldest supported version.
	 *
	 * The oldest supported versions come with the vulnerability feed; unknown
	 * when one of them is missing. WooCommerce counts only when it is active.
	 * From out-of-date data a pass is unknown, as for V-01: a version may have
	 * reached its end of life since. A fail stands, since that never reverses.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function end_of_life( array $context ) {
		$eol      = isset( $context['eol'] ) && is_array( $context['eol'] ) ? $context['eol'] : array();
		$versions = isset( $context['versions'] ) && is_array( $context['versions'] ) ? $context['versions'] : array(
			'php'         => PHP_VERSION,
			'wordpress'   => (string) get_bloginfo( 'version' ),
			'woocommerce' => defined( 'WC_VERSION' ) ? WC_VERSION : null,
		);

		$unknown = false;
		foreach ( array( 'php', 'wordpress', 'woocommerce' ) as $name ) {
			$version = isset( $versions[ $name ] ) && is_string( $versions[ $name ] ) ? $versions[ $name ] : '';
			if ( '' === $version ) {
				// WooCommerce is not active; PHP and WordPress always have a version.
				$unknown = $unknown || 'woocommerce' !== $name;
				continue;
			}
			if ( ! isset( $eol[ $name ] ) || ! is_string( $eol[ $name ] ) || '' === $eol[ $name ] ) {
				$unknown = true;
				continue;
			}
			if ( version_compare( $version, $eol[ $name ], '<' ) ) {
				return 'fail';
			}
		}
		return $unknown || ! empty( $context['eol_stale'] ) ? 'unknown' : 'pass';
	}

	/**
	 * V-04: automatic updates that are not on: for WordPress security releases
	 * (WP_AUTO_UPDATE_CORE, allow_minor_auto_core_updates, auto_update_core,
	 * wp_version_check removed), for plugins as a whole, or for any plugin
	 * WordPress can update.
	 *
	 * A plugin WordPress can update is one the update_plugins transient lists,
	 * with or without an update; whether it is updated automatically is decided
	 * as WP_Automatic_Updater::should_update() decides it. Unknown when
	 * WordPress has not looked for updates.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function automatic_updates( array $context ) {
		if ( ! function_exists( 'wp_is_auto_update_enabled_for_type' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		// False also when every automatic update is off (AUTOMATIC_UPDATER_DISABLED, DISALLOW_FILE_MODS).
		if ( ! wp_is_auto_update_enabled_for_type( 'plugin' ) ) {
			return 'fail';
		}

		// As Core_Upgrader::should_update_to_version() decides for minor (security) releases.
		$minor = false !== self::constant( $context, 'WP_AUTO_UPDATE_CORE' );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
		if ( ! apply_filters( 'allow_minor_auto_core_updates', $minor ) ) {
			return 'fail';
		}
		// auto_update_core turns every core update off (add_filter( 'auto_update_core', '__return_false' )).
		// It is asked about a release of the running version, so that a filter that allows
		// only minor releases lets it through.
		$version = (string) get_bloginfo( 'version' );
		$offer   = (object) array(
			'response' => 'autoupdate',
			'current'  => $version,
			'version'  => $version,
			'locale'   => get_locale(),
		);
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
		if ( ! apply_filters( 'auto_update_core', true, $offer ) ) {
			return 'fail';
		}
		// As Site Health: without wp_version_check() WordPress never looks for an update.
		if ( ( ! is_multisite() || is_main_site() ) && ! has_action( 'wp_version_check', 'wp_version_check' ) ) {
			return 'fail';
		}

		$updates = array_key_exists( 'plugin_updates', $context ) ? $context['plugin_updates'] : get_site_transient( 'update_plugins' );
		if ( ! is_object( $updates ) || ! isset( $updates->last_checked ) ) {
			return 'unknown';
		}
		// The plugins the site admin turned auto-updates on for, on the Plugins screen.
		$enabled = array_key_exists( 'auto_update_plugins', $context ) ? $context['auto_update_plugins'] : get_site_option( 'auto_update_plugins', array() );
		$enabled = is_array( $enabled ) ? $enabled : array();

		foreach ( array( 'response', 'no_update' ) as $list ) {
			if ( ! isset( $updates->$list ) || ! is_array( $updates->$list ) ) {
				continue;
			}
			foreach ( $updates->$list as $file => $item ) {
				if ( ! self::updates_automatically( (string) $file, $item, $enabled ) ) {
					return 'fail';
				}
			}
		}
		return 'pass';
	}

	/**
	 * Whether WordPress updates a plugin automatically.
	 *
	 * As WP_Automatic_Updater::should_update(): on when WordPress.org asks for it
	 * (autoupdate) or the site admin turned it on, off when the update says so
	 * (disable_autoupdate), and the auto_update_plugin filter has the last word.
	 *
	 * @param string $file    Plugin file, relative to the plugins directory.
	 * @param mixed  $item    The plugin's entry in the update_plugins transient.
	 * @param array  $enabled Plugin files with auto-updates turned on.
	 * @return bool
	 */
	private static function updates_automatically( $file, $item, array $enabled ) {
		// A copy: the filter's callbacks get the entry, which must not change the transient.
		$item = is_object( $item ) ? clone $item : new stdClass();
		if ( ! isset( $item->plugin ) ) {
			$item->plugin = $file;
		}

		$update = ! empty( $item->autoupdate ) || in_array( $file, $enabled, true );
		if ( ! empty( $item->disable_autoupdate ) ) {
			$update = false;
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
		return (bool) apply_filters( 'auto_update_plugin', $update, $item );
	}

	/**
	 * A-01: a user named admin, the name attacks try first.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function admin_username( array $context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Every check takes the context.
		return username_exists( 'admin' ) ? 'fail' : 'pass';
	}

	/**
	 * A-02: the login page open to everyone.
	 *
	 * Passes with a plugin that limits login attempts or hides the page, with
	 * Jetpack's brute force protection, with an access rule in .htaccess, with
	 * this plugin's own login attempt limiter, or when the page does not answer
	 * the site's own request (an IP restriction or password in the server
	 * configuration, which .htaccess does not show on nginx).
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function login_protection( array $context ) {
		if ( null !== self::login_protected_by_other_means( $context ) ) {
			return 'pass';
		}
		// This plugin's own limiter (3.0.0). class_exists: an earlier version's includes() may have loaded this file.
		if ( class_exists( 'JP4WC_Login_Limiter', false ) && JP4WC_Login_Limiter::is_active() ) {
			return 'pass';
		}
		// The address as stored: the site_url filter is how some plugins hide the page.
		$url = untrailingslashit( (string) get_option( 'siteurl' ) ) . '/wp-login.php';
		return $context['probe']->status(
			'A-02',
			$url,
			static function ( $response ) {
				return JP4WC_Security_Probe::status_for_code( $response, array( 200 ) );
			}
		);
	}

	/**
	 * Whether .htaccess restricts the login page or the admin directory.
	 *
	 * @param string $abspath WordPress directory, with a trailing slash.
	 * @return bool
	 */
	private static function htaccess_restricts_login( $abspath ) {
		$root = self::without_comments( self::read_head( $abspath . '.htaccess', self::HTACCESS_BYTES ) );
		if ( '' !== $root ) {
			// A <Files wp-login.php> block with an access rule (Allow from alone may allow everyone).
			if ( 1 === preg_match( '~<Files(?:Match)?\s[^>]*wp-login[^>]*>(?:(?!</Files).)*?(?:Require\s+(?:ip|host|all\s+denied|valid-user)|Deny\s+from|AuthType)~is', $root ) ) {
				return true;
			}
			// A rewrite rule whose conditions and pattern name the login page and the client address.
			preg_match_all( '/(?:^[ \t]*RewriteCond\b.*\R)+^[ \t]*RewriteRule\b.*$/im', $root, $rules );
			foreach ( $rules[0] as $rule ) {
				if ( 1 === preg_match( '/wp-login/i', $rule ) && 1 === preg_match( '/%\{(?:REMOTE_ADDR|REMOTE_HOST|HTTP:X-Forwarded-For)\}/i', $rule ) ) {
					return true;
				}
			}
		}

		$admin = self::without_comments( self::read_head( $abspath . 'wp-admin/.htaccess', self::HTACCESS_BYTES ) );
		// Rules inside <Files> blocks apply to those files only, such as install.php.
		$admin = (string) preg_replace( '~<(Files(?:Match)?)\b[^>]*>.*?</\1\s*>~is', '', $admin );
		return 1 === preg_match( '/^\s*(?:AuthType|Require\s+(?:ip|host|all\s+denied|valid-user)|Deny\s+from)\b/im', $admin );
	}

	/**
	 * A-03: no two-factor authentication plugin.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function two_factor( array $context ) {
		return self::has_active_plugin( $context, '2fa' ) ? 'pass' : 'fail';
	}

	/**
	 * A-04: XML-RPC turned on and answering.
	 *
	 * WordPress answers 405 to a request that is not a POST.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function xmlrpc( array $context ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
		if ( ! apply_filters( 'xmlrpc_enabled', true ) ) {
			return 'pass';
		}
		return $context['probe']->status(
			'A-04',
			untrailingslashit( (string) get_option( 'siteurl' ) ) . '/xmlrpc.php',
			static function ( $response ) {
				return JP4WC_Security_Probe::status_for_code( $response, array( 200, 405 ) );
			}
		);
	}

	/**
	 * A-05: the users REST route lists users to anyone.
	 *
	 * A route that lists nobody (X-WP-Total: 0) passes. With this plugin's own
	 * setting that hides the route from visitors, the route is still requested
	 * (another plugin could put it back): a list of users fails, anything
	 * else passes.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function rest_users( array $context ) {
		// This plugin's own setting (3.0.0). class_exists: an earlier version's includes() may have loaded this file.
		$hidden = class_exists( 'JP4WC_Security_Hardening', false ) && JP4WC_Security_Hardening::hides_rest_users();
		$status = $context['probe']->status(
			'A-05',
			get_rest_url( null, 'wp/v2/users' ),
			static function ( $response ) {
				if ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
					return '0' === trim( (string) wp_remote_retrieve_header( $response, 'x-wp-total' ) ) ? 'pass' : 'fail';
				}
				return JP4WC_Security_Probe::status_for_code( $response, array() );
			}
		);
		// With the setting on, the route is gone for visitors: only a list of users seen by the probe says otherwise.
		return $hidden && 'fail' !== $status ? 'pass' : $status;
	}

	/**
	 * D-01: the uploads directory lists its files.
	 *
	 * An index file in the directory is served instead of a listing.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function directory_listing( array $context ) {
		$uploads = self::uploads( $context );
		if ( null === $uploads ) {
			return 'unknown';
		}
		foreach ( array( 'index.php', 'index.html', 'index.htm' ) as $index ) {
			if ( is_file( $uploads['basedir'] . '/' . $index ) ) {
				return 'pass';
			}
		}
		return $context['probe']->status(
			'D-01',
			trailingslashit( $uploads['baseurl'] ),
			static function ( $response ) {
				return JP4WC_Security_Probe::status_for_code( $response, array( 200 ) );
			}
		);
	}

	/**
	 * D-02: a database dump, archive, backup or log file a public directory serves.
	 *
	 * Looks only at the files directly in the WordPress, wp-content and uploads
	 * directories, and requests each kind of file found (up to EXPOSED_PROBES
	 * per directory) from the site itself. A file the server refuses (401, 403,
	 * 404, 410) passes. One it serves fails, and so does one whose request tells
	 * nothing (the site cannot reach itself, a directory without a known URL):
	 * the file lies in a public directory and nothing shows that it is blocked.
	 * Unknown when a directory cannot be read in full, or holds more kinds of
	 * such files than are requested.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function exposed_files( array $context ) {
		if ( isset( $context['public_directories'] ) && is_array( $context['public_directories'] ) ) {
			$directories = $context['public_directories'];
		} else {
			$directories = array(
				self::abspath( $context ) => (string) get_option( 'siteurl' ),
				( isset( $context['content_dir'] ) ? (string) $context['content_dir'] : WP_CONTENT_DIR ) => content_url(),
			);
			$uploads     = self::uploads( $context );
			if ( null !== $uploads ) {
				$directories[ $uploads['basedir'] ] = $uploads['baseurl'];
			}
		}
		$urls = array();
		foreach ( $directories as $directory => $url ) {
			$directory = untrailingslashit( wp_normalize_path( (string) $directory ) );
			if ( ! array_key_exists( $directory, $urls ) ) {
				$urls[ $directory ] = is_string( $url ) && '' !== $url ? untrailingslashit( $url ) : null;
			}
		}

		$limit      = self::scan_limit( $context );
		$incomplete = false;
		$index      = -1;
		foreach ( $urls as $directory => $url ) {
			++$index;
			if ( ! is_dir( $directory ) ) {
				continue;
			}
			$kinds = array();
			$names = self::find_entries(
				$directory,
				static function ( $name ) use ( $directory, &$kinds ) {
					if ( 1 !== preg_match( self::EXPOSED_FILE_PATTERN, $name ) || ! is_file( $directory . '/' . $name ) ) {
						return false;
					}
					// One file per kind: .sql, .sql.gz, .log, error_log...
					$kind = strtolower( (string) preg_replace( '/^.*?(\.[^.]+(?:\.gz)?)$/', '$1', $name ) );
					if ( isset( $kinds[ $kind ] ) ) {
						return false;
					}
					$kinds[ $kind ] = true;
					return true;
				},
				$limit,
				self::EXPOSED_PROBES + 1,
				$complete
			);
			foreach ( array_slice( $names, 0, self::EXPOSED_PROBES ) as $position => $name ) {
				if ( 'pass' !== self::exposed_file_status( $context, $index, $position, $url, $name ) ) {
					return 'fail';
				}
			}
			$incomplete = $incomplete || ! $complete || count( $names ) > self::EXPOSED_PROBES;
		}
		return $incomplete ? 'unknown' : 'pass';
	}

	/**
	 * Returns whether the server refuses one file D-02 found (pass) or not (fail).
	 *
	 * @param array       $context   Context.
	 * @param int         $directory Position of the directory.
	 * @param int         $position  Position of the file among those found in it.
	 * @param string|null $url       URL of the directory, or null when it is not known.
	 * @param string      $name      File name.
	 * @return string
	 */
	private static function exposed_file_status( array $context, $directory, $position, $url, $name ) {
		if ( null === $url || $directory >= self::EXPOSED_DIRECTORIES ) {
			return 'fail';
		}
		$status = $context['probe']->status(
			'D-02-' . ( $directory * self::EXPOSED_PROBES + $position ),
			$url . '/' . rawurlencode( $name ),
			static function ( $response ) {
				return JP4WC_Security_Probe::status_for_code( $response, array( 200 ) );
			}
		);
		return 'pass' === $status ? 'pass' : 'fail';
	}

	/**
	 * D-03: errors shown on the site's pages (WP_DEBUG with WP_DEBUG_DISPLAY).
	 *
	 * WP_DEBUG_DISPLAY defaults to true (wp_initial_constants()). Defined as
	 * null it leaves display_errors to php.ini, which this process (WP-Cron
	 * may run on the command line) need not share with the web server, so
	 * the check is then unknown.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function debug_display( array $context ) {
		if ( ! self::constant( $context, 'WP_DEBUG' ) ) {
			return 'pass';
		}
		$display = self::has_constant( $context, 'WP_DEBUG_DISPLAY' ) ? self::constant( $context, 'WP_DEBUG_DISPLAY' ) : true;
		if ( null === $display ) {
			return 'unknown';
		}
		return $display ? 'fail' : 'pass';
	}

	/**
	 * D-04: WooCommerce log files that can be downloaded.
	 *
	 * Requests one log file in WooCommerce's log directory. Unknown when
	 * WooCommerce is not active, has no log file, or keeps its logs outside
	 * the uploads directory (where the URL of a file is not known).
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function log_files( array $context ) {
		$directory = self::constant( $context, 'WC_LOG_DIR' );
		$uploads   = self::uploads( $context );
		if ( ! is_string( $directory ) || '' === $directory || null === $uploads ) {
			return 'unknown';
		}
		$directory = untrailingslashit( wp_normalize_path( $directory ) );
		$basedir   = untrailingslashit( wp_normalize_path( $uploads['basedir'] ) );
		if ( 0 !== strpos( $directory . '/', $basedir . '/' ) || ! is_dir( $directory ) || ! is_readable( $directory ) ) {
			return 'unknown';
		}

		$log = self::find_entry(
			$directory,
			static function ( $name ) use ( $directory ) {
				return 1 === preg_match( '/\.log$/iD', $name ) && is_file( $directory . '/' . $name );
			},
			self::scan_limit( $context )
		);
		if ( ! is_string( $log ) ) {
			return 'unknown';
		}

		$path = substr( $directory, strlen( $basedir ) ) . '/' . $log;
		$url  = untrailingslashit( $uploads['baseurl'] ) . implode( '/', array_map( 'rawurlencode', explode( '/', $path ) ) );
		return $context['probe']->status(
			'D-04',
			$url,
			static function ( $response ) {
				return JP4WC_Security_Probe::status_for_code( $response, array( 200 ) );
			}
		);
	}

	/**
	 * D-05: the checkout or My account page not served over HTTPS.
	 *
	 * Reads the pages' addresses with the scheme the site stores: is_ssl()
	 * describes only the current request.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function https_pages( array $context ) {
		if ( isset( $context['shop_urls'] ) && is_array( $context['shop_urls'] ) ) {
			$urls = $context['shop_urls'];
		} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
			// get_home_url() switches to https when this request is over HTTPS (is_ssl()), as a
			// cron request may be while visitors are not: keep the scheme the site stores.
			$stored = static function ( $url, $path, $scheme ) {
				$home = wp_parse_url( (string) get_option( 'home' ), PHP_URL_SCHEME );
				return null === $scheme && is_string( $home ) ? set_url_scheme( $url, $home ) : $url;
			};
			add_filter( 'home_url', $stored, PHP_INT_MIN, 3 );
			try {
				$urls = array( wc_get_page_permalink( 'checkout' ), wc_get_page_permalink( 'myaccount' ) );
			} finally {
				remove_filter( 'home_url', $stored, PHP_INT_MIN );
			}
		} else {
			return 'unknown';
		}
		foreach ( $urls as $url ) {
			$scheme = is_string( $url ) ? wp_parse_url( $url, PHP_URL_SCHEME ) : null;
			if ( ! is_string( $scheme ) || 'https' !== strtolower( $scheme ) ) {
				return 'fail';
			}
		}
		return 'pass';
	}

	/**
	 * M-01: theme and plugin files can be edited from the admin screens.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function file_editing( array $context ) {
		return self::constant( $context, 'DISALLOW_FILE_EDIT' ) || self::constant( $context, 'DISALLOW_FILE_MODS' ) ? 'pass' : 'fail';
	}

	/**
	 * M-02: no plugin that scans for malware or filters attacks.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function security_plugin( array $context ) {
		return self::has_active_plugin( $context, 'security' ) ? 'pass' : 'fail';
	}

	/**
	 * M-03: a PHP file in the uploads directory that can run code.
	 *
	 * Files that plugins leave there on purpose are left out (is_harmless_php()).
	 * Unknown when the scan stops at its limits before it finds one, or a file
	 * cannot be told apart.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function php_in_uploads( array $context ) {
		$uploads = self::uploads( $context );
		if ( null === $uploads ) {
			return 'unknown';
		}
		if ( ! is_dir( $uploads['basedir'] ) ) {
			return 'pass';
		}

		$limit = self::scan_limit( $context );

		$undecided = false;
		$seen      = 0;
		try {
			$iterator = new RecursiveIteratorIterator(
				// Symbolic links to directories are not followed (the result is then unknown).
				new RecursiveDirectoryIterator( $uploads['basedir'], FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST,
				RecursiveIteratorIterator::CATCH_GET_CHILD
			);
			$iterator->setMaxDepth( self::SCAN_DEPTH );
			foreach ( $iterator as $entry ) {
				if ( ++$seen > $limit ) {
					return 'unknown';
				}
				if ( $entry->isDir() ) {
					// What lies below the last level, in a directory that cannot be read, or
					// behind a link is not looked at, though a web server may serve it.
					$undecided = $undecided || $iterator->getDepth() >= self::SCAN_DEPTH || $entry->isLink() || ! $entry->isReadable();
					continue;
				}
				if ( ! $entry->isFile() || 1 !== preg_match( self::PHP_FILE_PATTERN, $entry->getFilename() ) ) {
					continue;
				}
				$harmless = self::is_harmless_php( $entry->getPathname() );
				if ( false === $harmless ) {
					return 'fail';
				}
				$undecided = $undecided || null === $harmless;
			}
		} catch ( UnexpectedValueException $e ) {
			// The uploads directory cannot be read.
			return 'unknown';
		}
		return $undecided ? 'unknown' : 'pass';
	}

	/**
	 * Whether a PHP file runs no code when requested.
	 *
	 * Harmless: comments and HTML only; a first statement that stops (exit,
	 * die, __halt_compiler, as security plugins guard their data files); the
	 * usual guard against direct requests, `defined( 'ABSPATH' ) || exit;` or
	 * `if ( ! defined( 'ABSPATH' ) ) { exit; }`, since a request for the file
	 * itself does not load WordPress; header() or http_response_code() calls
	 * with plain values, as guard files
	 * answer 404; a data file that returns plain values; and declarations only
	 * (namespace, use, class, function), as compiled template caches hold.
	 * A file larger than PHP_HEAD_BYTES that its start does not decide is unknown.
	 *
	 * A harmless file that holds a short open tag (`<?` other than `<?php`,
	 * `<?=` and `<?xml`) is unknown: the tokenizer follows this process's
	 * short_open_tag, and WP-Cron run from the command line may not share the
	 * web server's, which would run that code.
	 *
	 * @param string $path File.
	 * @return bool|null True when harmless, false when it runs code, null when that cannot be told.
	 */
	public static function is_harmless_php( $path ) {
		$head = self::read_head( $path, self::PHP_HEAD_BYTES + 1 );
		if ( null === $head || ! function_exists( 'token_get_all' ) ) {
			return null;
		}
		$harmless = self::judge_php( $head, strlen( $head ) <= self::PHP_HEAD_BYTES );
		if ( true === $harmless && 1 === preg_match( '/<\?(?!php\b|=|xml\b)/i', $head ) ) {
			return null;
		}
		return $harmless;
	}

	/**
	 * Judges PHP source as is_harmless_php() describes.
	 *
	 * @param string $head  Source read.
	 * @param bool   $whole Whether it is the whole file.
	 * @return bool|null
	 */
	private static function judge_php( $head, $whole ) {
		$tokens = token_get_all( $head );
		if ( ! $whole ) {
			// The last token may be cut in two, and a cut string leaves its opening quote behind.
			array_pop( $tokens );
			while ( array() !== $tokens && in_array( self::token_type( end( $tokens ) ), array( '"', '`', T_START_HEREDOC, T_ENCAPSED_AND_WHITESPACE ), true ) ) {
				array_pop( $tokens );
			}
		}
		$skip   = array( T_OPEN_TAG, T_CLOSE_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML );
		$tokens = array_values(
			array_filter(
				$tokens,
				static function ( $token ) use ( $skip ) {
					return ! in_array( self::token_type( $token ), $skip, true );
				}
			)
		);

		$i = 0;
		while ( self::skip_header_call( $tokens, $i ) ) {
			continue;
		}
		if ( ! isset( $tokens[ $i ] ) ) {
			// Nothing else in what was read: harmless if that was the whole file.
			return $whole ? true : null;
		}

		$type = self::token_type( $tokens[ $i ] );
		if ( T_HALT_COMPILER === $type ) {
			return true;
		}
		if ( T_EXIT === $type ) {
			return self::is_plain_exit( array_slice( $tokens, $i + 1, 4 ), $whole );
		}
		if ( 0 === $i ) {
			$guard = self::direct_request_guard( $tokens, $whole );
			if ( false !== $guard ) {
				return $guard;
			}
		}
		if ( 0 === $i && T_RETURN === $type ) {
			return self::is_literal_return( array_slice( $tokens, 1 ), $whole );
		}
		if ( 0 === $i && ( T_ATTRIBUTE === $type || in_array( $type, self::DECLARATIONS, true ) ) ) {
			// What follows the declarations is not known without the whole file.
			return $whole ? self::declares_only( $tokens ) : null;
		}
		return false;
	}

	/**
	 * Whether every top-level statement is a declaration.
	 *
	 * Class and function bodies are skipped: they run only when called. A
	 * namespace block, and a declare that takes a statement or a block, are
	 * not declarations, since their statements run.
	 *
	 * @param array $tokens Tokens without whitespace and comments.
	 * @return bool
	 */
	private static function declares_only( array $tokens ) {
		$count = count( $tokens );
		$i     = 0;
		while ( $i < $count ) {
			$type = self::token_type( $tokens[ $i ] );
			if ( ';' === $type ) {
				++$i;
				continue;
			}
			if ( T_ATTRIBUTE === $type ) {
				// #[Attribute( ... )] before a declaration.
				for ( $brackets = 0; $i < $count; $i++ ) {
					$token     = self::token_type( $tokens[ $i ] );
					$brackets += ( T_ATTRIBUTE === $token || '[' === $token ) ? 1 : ( ']' === $token ? -1 : 0 );
					if ( 0 === $brackets ) {
						break;
					}
				}
				++$i;
				continue;
			}
			if ( ! in_array( $type, self::DECLARATIONS, true ) ) {
				return false;
			}
			if ( T_DECLARE === $type ) {
				// Only declare( ... ); — a declare followed by a statement or a block runs it.
				$end = self::closing_parenthesis( $tokens, $i + 1 );
				if ( null === $end || ';' !== self::type_at( $tokens, $end + 1 ) ) {
					return false;
				}
				$i = $end + 2;
				continue;
			}

			// The statement ends at a semicolon outside braces and parentheses, or
			// where the braces of its body close.
			$block       = T_NAMESPACE === $type;
			$braces      = 0;
			$parentheses = 0;
			for ( ; $i < $count; $i++ ) {
				$token = self::token_type( $tokens[ $i ] );
				if ( '(' === $token ) {
					++$parentheses;
				} elseif ( ')' === $token ) {
					--$parentheses;
				} elseif ( in_array( $token, array( '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) {
					if ( $block && 0 === $braces ) {
						return false;
					}
					++$braces;
				} elseif ( '}' === $token ) {
					--$braces;
					if ( 0 === $braces ) {
						break;
					}
				} elseif ( ';' === $token && 0 === $braces && 0 === $parentheses ) {
					break;
				}
			}
			++$i;
		}
		return true;
	}

	/**
	 * Whether the file starts by stopping when WordPress is not loaded.
	 *
	 * Only ABSPATH and WPINC count: WordPress defines them, so a request for
	 * the file itself stops, whereas a constant PHP always defines would not.
	 *
	 * @param array $tokens Tokens without whitespace and comments.
	 * @param bool  $whole  Whether the tokens reach the end of the file.
	 * @return bool|null True for a guard that stops at once, null when its exit
	 *                   goes on past what was read, false otherwise (no such guard,
	 *                   or an exit whose argument runs code).
	 */
	private static function direct_request_guard( array $tokens, $whole ) {
		$k     = 0;
		$is_if = T_IF === self::type_at( $tokens, 0 );
		if ( $is_if ) {
			// The if form: the condition is the negated defined() call.
			if ( '(' !== self::type_at( $tokens, 1 ) || '!' !== self::type_at( $tokens, 2 ) ) {
				return false;
			}
			$k = 3;
		}

		// The defined() call with ABSPATH or WPINC.
		$function = isset( $tokens[ $k ] ) && is_array( $tokens[ $k ] ) && in_array( $tokens[ $k ][0], array( T_STRING, T_NAME_FULLY_QUALIFIED ), true ) ? strtolower( ltrim( $tokens[ $k ][1], '\\' ) ) : '';
		$constant = T_CONSTANT_ENCAPSED_STRING === self::type_at( $tokens, $k + 2 ) ? trim( $tokens[ $k + 2 ][1], '\'"' ) : '';
		if ( 'defined' !== $function || '(' !== self::type_at( $tokens, $k + 1 ) || ! in_array( $constant, array( 'ABSPATH', 'WPINC' ), true ) || ')' !== self::type_at( $tokens, $k + 3 ) ) {
			return false;
		}
		$k += 4;

		if ( $is_if ) {
			if ( ')' !== self::type_at( $tokens, $k ) ) {
				return false;
			}
			++$k;
			if ( '{' === self::type_at( $tokens, $k ) ) {
				++$k;
			}
		} elseif ( in_array( self::type_at( $tokens, $k ), array( T_BOOLEAN_OR, T_LOGICAL_OR ), true ) ) {
			// The short form: the defined() call, || or or, then exit or die.
			++$k;
		} else {
			return false;
		}

		if ( T_EXIT !== self::type_at( $tokens, $k ) ) {
			return false;
		}
		// The argument of exit( ... ) runs first: only a plain one keeps the guard harmless,
		// and any other is code (false).
		return self::is_plain_exit( array_slice( $tokens, $k + 1, 4 ), $whole );
	}

	/**
	 * Returns the position of the parenthesis that closes the one at a position.
	 *
	 * @param array $tokens Tokens.
	 * @param int   $open   Position of the opening parenthesis.
	 * @return int|null Null when there is no opening parenthesis there, or it is not closed.
	 */
	private static function closing_parenthesis( array $tokens, $open ) {
		if ( '(' !== self::type_at( $tokens, $open ) ) {
			return null;
		}
		$depth = 0;
		for ( $k = $open, $count = count( $tokens ); $k < $count; $k++ ) {
			$type = self::token_type( $tokens[ $k ] );
			if ( '(' === $type ) {
				++$depth;
			} elseif ( ')' === $type && 0 === --$depth ) {
				return $k;
			}
		}
		return null;
	}

	/**
	 * Returns the type of the token at a position.
	 *
	 * @param array $tokens Tokens.
	 * @param int   $k      Position.
	 * @return int|string|null Null past the end.
	 */
	private static function type_at( array $tokens, $k ) {
		return isset( $tokens[ $k ] ) ? self::token_type( $tokens[ $k ] ) : null;
	}

	/**
	 * Returns the type of a token: its token constant, or the character itself.
	 *
	 * @param array|string $token Token of token_get_all().
	 * @return int|string
	 */
	private static function token_type( $token ) {
		return is_array( $token ) ? $token[0] : $token;
	}

	/**
	 * Moves past a header() or http_response_code() call with plain values.
	 *
	 * The arguments may hold only strings, numbers, booleans, concatenation and
	 * $_SERVER items, so they cannot call anything.
	 *
	 * @param array $tokens Tokens without whitespace and comments.
	 * @param int   $i      Position; moved past the call when there is one.
	 * @return bool Whether a call was skipped.
	 */
	private static function skip_header_call( array $tokens, &$i ) {
		$name = isset( $tokens[ $i ] ) && is_array( $tokens[ $i ] ) && T_STRING === $tokens[ $i ][0] ? strtolower( $tokens[ $i ][1] ) : '';
		if ( ! in_array( $name, array( 'header', 'http_response_code' ), true ) || ! isset( $tokens[ $i + 1 ] ) || '(' !== $tokens[ $i + 1 ] ) {
			return false;
		}
		for ( $j = $i + 2; isset( $tokens[ $j ] ); $j++ ) {
			$token = $tokens[ $j ];
			if ( ')' === $token ) {
				// The statement ends with ; or with the end of what was read.
				if ( isset( $tokens[ $j + 1 ] ) && ';' !== $tokens[ $j + 1 ] ) {
					return false;
				}
				$i = $j + 2;
				return true;
			}
			$server = is_array( $token ) && T_VARIABLE === $token[0] && '$_SERVER' === $token[1];
			if ( ! $server && ! self::is_plain_value( $token, array( '.', ',', '[', ']' ) ) ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Whether the tokens after exit or die stop at once: exit; exit(); exit( 0 ); exit( 'Silence' );
	 *
	 * The argument of exit( ... ) runs first, so any other argument is code.
	 *
	 * @param array $rest  Up to four tokens after exit.
	 * @param bool  $whole Whether the tokens reach the end of the file.
	 * @return bool|null Null when what was read ends before the statement does.
	 */
	private static function is_plain_exit( array $rest, $whole ) {
		$next = isset( $rest[0] ) ? $rest[0] : null;
		if ( ';' === $next ) {
			return true;
		}
		if ( null === $next ) {
			// exit at the end of the file; an argument may follow past what was read.
			return $whole ? true : null;
		}
		if ( '(' !== $next ) {
			return false;
		}
		$argument = isset( $rest[1] ) ? $rest[1] : null;
		if ( ')' === $argument ) {
			return true;
		}
		if ( null === $argument ) {
			return $whole ? false : null;
		}
		if ( ! is_array( $argument ) || ! in_array( $argument[0], array( T_LNUMBER, T_CONSTANT_ENCAPSED_STRING ), true ) ) {
			return false;
		}
		$close = isset( $rest[2] ) ? $rest[2] : null;
		if ( ')' === $close ) {
			return true;
		}
		return null === $close && ! $whole ? null : false;
	}

	/**
	 * Whether a return statement returns plain values only: return array( 'a' => 1 );
	 *
	 * A parenthesis is allowed only after array, so nothing can be called.
	 *
	 * @param array $tokens Tokens after return.
	 * @param bool  $whole  Whether they are the whole file.
	 * @return bool|null Null when the statement goes on past what was read.
	 */
	private static function is_literal_return( array $tokens, $whole ) {
		$depth = 0;
		for ( $k = 0; isset( $tokens[ $k ] ); $k++ ) {
			$token = $tokens[ $k ];
			if ( ';' === $token ) {
				// Nothing after return runs.
				return 0 === $depth;
			}
			if ( is_array( $token ) && T_ARRAY === $token[0] ) {
				if ( ! isset( $tokens[ $k + 1 ] ) ) {
					return $whole ? false : null;
				}
				if ( '(' !== $tokens[ $k + 1 ] ) {
					return false;
				}
				++$depth;
				++$k;
				continue;
			}
			if ( ')' === $token ) {
				if ( 0 === $depth ) {
					return false;
				}
				--$depth;
				continue;
			}
			$arrow = is_array( $token ) && T_DOUBLE_ARROW === $token[0];
			if ( ! $arrow && ! self::is_plain_value( $token, array( ',', '[', ']', '-' ) ) ) {
				return false;
			}
		}
		// The statement ends with the file (or a closing tag), or goes on past what was read.
		return $whole ? 0 === $depth : null;
	}

	/**
	 * Whether a token is a plain value or one of the given characters.
	 *
	 * @param mixed    $token Token.
	 * @param string[] $chars Characters allowed.
	 * @return bool
	 */
	private static function is_plain_value( $token, array $chars ) {
		if ( ! is_array( $token ) ) {
			return in_array( $token, $chars, true );
		}
		if ( in_array( $token[0], array( T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER ), true ) ) {
			return true;
		}
		return T_STRING === $token[0] && in_array( strtolower( $token[1] ), array( 'true', 'false', 'null' ), true );
	}

	/**
	 * Whether an active plugin of a category is installed.
	 *
	 * @param array  $context  Context.
	 * @param string $category login, 2fa or security.
	 * @return bool
	 */
	private static function has_active_plugin( array $context, $category ) {
		return null !== self::active_known_plugin( $context, $category );
	}

	/**
	 * Returns the first active plugin of a category, or null.
	 *
	 * @param array  $context  Context.
	 * @param string $category login, 2fa or security.
	 * @return string|null Plugin slug.
	 */
	private static function active_known_plugin( array $context, $category ) {
		$active = isset( $context['active_plugins'] ) && is_array( $context['active_plugins'] ) ? $context['active_plugins'] : self::active_plugin_slugs();
		$found  = array_values( array_intersect( $active, self::known_plugins( $category ) ) );
		return array() === $found ? null : (string) $found[0];
	}

	/**
	 * Returns what protects the login page apart from this plugin's own limiter.
	 *
	 * The A-02 criteria: a known plugin that limits or hides logins, a
	 * restriction in .htaccess, or Jetpack's brute force protection. The login
	 * attempt limiter (JP4WC_Login_Limiter) sleeps while one of these is in
	 * place, so that two limiters never count the same failure.
	 *
	 * @since 3.0.0
	 * @param array $context Context: active_plugins and abspath (A-02).
	 * @return array{type: string, name: string}|null type: plugin, htaccess or
	 *                                                jetpack; name: the plugin
	 *                                                slug, '.htaccess' or 'Jetpack'.
	 */
	public static function login_protected_by_other_means( array $context = array() ) {
		$plugin = self::active_known_plugin( $context, 'login' );
		if ( null !== $plugin ) {
			return array(
				'type' => 'plugin',
				'name' => $plugin,
			);
		}
		if ( self::jetpack_protects_login( $context ) ) {
			return array(
				'type' => 'jetpack',
				'name' => 'Jetpack',
			);
		}
		if ( self::htaccess_restricts_login( self::abspath( $context ) ) ) {
			return array(
				'type' => 'htaccess',
				'name' => '.htaccess',
			);
		}
		return null;
	}

	/**
	 * Whether Jetpack's brute force protection is on and running.
	 *
	 * Jetpack and the Jetpack Protect plugin both bundle the jetpack-waf
	 * package, whose Brute_Force_Protection class knows whether it is on
	 * (Jetpack 12.6+, Jetpack Protect 3.0+); older Jetpack versions have it
	 * as the "protect" module. The package runs the protection only while
	 * the site is connected to WordPress.com (its initialize() checks
	 * Connection\Manager::is_connected()), so the connection is required too.
	 * Neither plugin is in KNOWN_PLUGINS: active with the protection off or
	 * unconnected, they do not protect the login page.
	 *
	 * @param array $context Context; jetpack_protect and jetpack_connected (bool) override the lookups (tests).
	 * @return bool
	 */
	private static function jetpack_protects_login( array $context ) {
		$enabled = isset( $context['jetpack_protect'] ) ? (bool) $context['jetpack_protect'] : self::jetpack_protection_enabled();
		if ( ! $enabled ) {
			return false;
		}
		if ( isset( $context['jetpack_connected'] ) ) {
			return (bool) $context['jetpack_connected'];
		}
		return self::jetpack_connected();
	}

	/**
	 * Whether the site is connected to WordPress.com, as Jetpack sees it.
	 *
	 * @return bool
	 */
	private static function jetpack_connected() {
		$manager = 'Automattic\Jetpack\Connection\Manager';
		if ( class_exists( $manager ) && method_exists( $manager, 'is_connected' ) ) {
			try {
				return (bool) ( new $manager() )->is_connected();
			} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Unknown Jetpack version: fall through to the plugin class.
				// Fall through.
			}
		}
		if ( class_exists( 'Jetpack' ) ) {
			if ( method_exists( 'Jetpack', 'is_connection_ready' ) ) {
				return (bool) Jetpack::is_connection_ready();
			}
			if ( method_exists( 'Jetpack', 'is_active' ) ) {
				return (bool) Jetpack::is_active();
			}
		}
		return false;
	}

	/**
	 * Whether Jetpack's brute force protection module is on.
	 *
	 * @return bool
	 */
	private static function jetpack_protection_enabled() {
		$class = 'Automattic\Jetpack\Waf\Brute_Force_Protection\Brute_Force_Protection';
		if ( class_exists( $class ) && method_exists( $class, 'is_enabled' ) ) {
			try {
				$method = new ReflectionMethod( $class, 'is_enabled' );
				if ( $method->isStatic() ) {
					return (bool) $class::is_enabled();
				}
				if ( method_exists( $class, 'instance' ) ) {
					return (bool) $class::instance()->is_enabled();
				}
			} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Unknown Jetpack version: fall through to the module check.
				// Fall through.
			}
		}
		return class_exists( 'Jetpack' ) && method_exists( 'Jetpack', 'is_module_active' ) && Jetpack::is_module_active( 'protect' );
	}

	/**
	 * Returns the slugs of the active plugins, network-activated ones included.
	 *
	 * @return string[]
	 */
	private static function active_plugin_slugs() {
		$files = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$files = array_merge( $files, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		$slugs = array();
		foreach ( $files as $file ) {
			if ( is_string( $file ) && '' !== $file ) {
				// The slug of JP4WC_Security_Scanner::installed_software(): the directory, or the file without .php.
				$slugs[] = false === strpos( $file, '/' ) ? basename( $file, '.php' ) : strtok( $file, '/' );
			}
		}
		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Returns the plugins of a category.
	 *
	 * @param string $category login, 2fa or security.
	 * @return string[]
	 */
	public static function known_plugins( $category ) {
		$slugs = isset( self::KNOWN_PLUGINS[ $category ] ) ? self::KNOWN_PLUGINS[ $category ] : array();

		/**
		 * Filters the plugins the security check recognises.
		 *
		 * @since 3.0.0
		 * @param string[] $slugs    Plugin slugs (directory names).
		 * @param string   $category login (limits or hides the login page, check A-02),
		 *                           2fa (two-factor authentication, A-03) or
		 *                           security (malware scan or firewall, M-02).
		 */
		$slugs = apply_filters( 'jp4wc_security_known_plugins', $slugs, $category );
		return is_array( $slugs ) ? array_values( array_filter( $slugs, 'is_string' ) ) : array();
	}

	/**
	 * Returns how many entries of a directory the checks read.
	 *
	 * @param array $context Context.
	 * @return int
	 */
	private static function scan_limit( array $context ) {
		/**
		 * Filters how many entries the security check reads in the uploads directory
		 * and below when it looks for PHP files, and in each directory it looks into
		 * for backups and logs.
		 *
		 * @since 3.0.0
		 * @param int $limit Entries (files and directories). Default 50000.
		 */
		return (int) apply_filters( 'jp4wc_security_file_scan_limit', isset( $context['scan_limit'] ) ? (int) $context['scan_limit'] : self::SCAN_LIMIT );
	}

	/**
	 * Looks for an entry of a directory, reading one name at a time.
	 *
	 * @param string   $directory Directory.
	 * @param callable $is_match  Gets a name and returns whether it is the one looked for.
	 * @param int      $limit     Names read before giving up.
	 * @return string|false|null The name found; false when there is none; null when
	 *                           the directory cannot be read or holds more names than the limit.
	 */
	private static function find_entry( $directory, callable $is_match, $limit ) {
		$names = self::find_entries( $directory, $is_match, $limit, 1, $complete );
		if ( array() !== $names ) {
			return $names[0];
		}
		return $complete ? false : null;
	}

	/**
	 * Looks for entries of a directory, reading one name at a time.
	 *
	 * @param string   $directory Directory.
	 * @param callable $is_match  Gets a name and returns whether to keep it.
	 * @param int      $limit     Names read before giving up.
	 * @param int      $max       Names kept before stopping.
	 * @param bool     $complete  Set to false when the directory cannot be read,
	 *                            or holds more names than the limit.
	 * @return string[] Names kept.
	 */
	private static function find_entries( $directory, callable $is_match, $limit, $max, &$complete ) {
		$complete = true;
		$found    = array();
		$handle   = is_readable( $directory ) ? opendir( $directory ) : false;
		if ( false === $handle ) {
			$complete = false;
			return $found;
		}
		try {
			$seen = 0;
			for ( $name = readdir( $handle ); false !== $name; $name = readdir( $handle ) ) {
				if ( ++$seen > $limit ) {
					$complete = false;
					break;
				}
				if ( $is_match( $name ) ) {
					$found[] = $name;
					if ( count( $found ) >= $max ) {
						break;
					}
				}
			}
		} finally {
			closedir( $handle );
		}
		return $found;
	}

	/**
	 * Returns the keys under which the checks keep the results of their requests to the site.
	 *
	 * @return string[]
	 */
	public static function probe_keys() {
		$keys = array( 'A-02', 'A-04', 'A-05', 'D-01', 'D-04' );
		for ( $key = 0; $key < self::EXPOSED_DIRECTORIES * self::EXPOSED_PROBES; $key++ ) {
			$keys[] = 'D-02-' . $key;
		}
		return $keys;
	}

	/**
	 * Removes every kept result of the requests to the site.
	 */
	public static function delete_probe_results() {
		foreach ( self::probe_keys() as $key ) {
			delete_option( JP4WC_Security_Probe::option_name( $key ) );
		}
	}

	/**
	 * Returns a constant of the context or of the site.
	 *
	 * @param array  $context Context.
	 * @param string $name    Constant.
	 * @return mixed Null when it is not defined.
	 */
	private static function constant( array $context, $name ) {
		if ( isset( $context['constants'] ) && is_array( $context['constants'] ) && array_key_exists( $name, $context['constants'] ) ) {
			return $context['constants'][ $name ];
		}
		return defined( $name ) ? constant( $name ) : null;
	}

	/**
	 * Whether a constant is set in the context or defined on the site.
	 *
	 * @param array  $context Context.
	 * @param string $name    Constant.
	 * @return bool
	 */
	private static function has_constant( array $context, $name ) {
		return ( isset( $context['constants'] ) && is_array( $context['constants'] ) && array_key_exists( $name, $context['constants'] ) ) || defined( $name );
	}

	/**
	 * Returns the WordPress directory, with a trailing slash.
	 *
	 * @param array $context Context.
	 * @return string
	 */
	private static function abspath( array $context ) {
		return trailingslashit( isset( $context['abspath'] ) ? (string) $context['abspath'] : ABSPATH );
	}

	/**
	 * Returns the uploads directory and its URL, without creating it.
	 *
	 * @param array $context Context.
	 * @return array{basedir: string, baseurl: string}|null Null when it is not available.
	 */
	private static function uploads( array $context ) {
		$uploads = isset( $context['uploads'] ) && is_array( $context['uploads'] ) ? $context['uploads'] : wp_upload_dir( null, false );
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return null;
		}
		return array(
			'basedir' => untrailingslashit( (string) $uploads['basedir'] ),
			'baseurl' => untrailingslashit( (string) $uploads['baseurl'] ),
		);
	}

	/**
	 * Reads the start of a file.
	 *
	 * @param string $path  File.
	 * @param int    $bytes Bytes to read.
	 * @return string|null Null when the file cannot be read.
	 */
	private static function read_head( $path, $bytes ) {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return null;
		}
		$head = file_get_contents( $path, false, null, 0, $bytes );
		return false === $head ? null : $head;
	}

	/**
	 * Removes the comment lines of an .htaccess file.
	 *
	 * @param string|null $htaccess Content.
	 * @return string
	 */
	private static function without_comments( $htaccess ) {
		return null === $htaccess ? '' : (string) preg_replace( '/^\s*#.*$/m', '', $htaccess );
	}
}
