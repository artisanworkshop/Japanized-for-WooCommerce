<?php
/**
 * Registers a bundled copy of the Artisan Workshop PHP security notice.
 *
 * Several Artisan Workshop plugins bundle this directory. Each one requires this
 * file from its main plugin file and registers its own copy there:
 *
 *     require_once __DIR__ . '/includes/artws-php-security-notice/register.php';
 *     artws_php_security_notice_register(
 *         '1.0.0', // Notice::VERSION of this copy.
 *         __DIR__ . '/includes/artws-php-security-notice/class-notice.php',
 *         'myplugin_php_security_notice_strings'
 *     );
 *
 * At plugins_loaded priority 5 only the copy with the newest version is loaded,
 * and its notice uses the strings of the plugin that registered that copy (see
 * the Notice class for the keys). Plugins are loaded in the order of their
 * folder names, so using whichever copy came first could use one whose list of
 * fixed PHP versions is out of date.
 *
 * Keep this directory identical in every plugin that bundles it (check with
 * `diff -r`), and raise Notice::VERSION and the registered version together.
 * The files must run on PHP 7.0 and later, because the notice is meant for
 * sites on old PHP versions.
 *
 * @package ArtisanWorkshop\PhpSecurityNotice
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'artws_php_security_notice_register' ) ) {
	/**
	 * Registers a bundled copy of the notice.
	 *
	 * @param string   $version          Notice::VERSION of the copy.
	 * @param string   $class_file       Absolute path of the copy's class-notice.php.
	 * @param callable $strings_callback Returns the notice's strings, translated with the plugin's text domain.
	 * @return void
	 */
	function artws_php_security_notice_register( $version, $class_file, $strings_callback ) {
		if ( ! isset( $GLOBALS['artws_php_security_notice_registry'] ) || ! is_array( $GLOBALS['artws_php_security_notice_registry'] ) ) {
			$GLOBALS['artws_php_security_notice_registry'] = array();
		}
		$GLOBALS['artws_php_security_notice_registry'][] = array(
			'version'          => (string) $version,
			'class_file'       => (string) $class_file,
			'strings_callback' => $strings_callback,
		);
	}
}

if ( ! function_exists( 'artws_php_security_notice_newest' ) ) {
	/**
	 * Returns the registration with the newest version.
	 *
	 * Of registrations with the same version, the first one wins.
	 *
	 * @param array $registry Registrations made by artws_php_security_notice_register().
	 * @return array|null The registration, or null when there is none.
	 */
	function artws_php_security_notice_newest( $registry ) {
		if ( ! is_array( $registry ) ) {
			return null;
		}
		$newest = null;
		foreach ( $registry as $entry ) {
			if ( ! is_array( $entry ) || ! isset( $entry['version'], $entry['class_file'], $entry['strings_callback'] ) || ! is_string( $entry['version'] ) || '' === $entry['version'] ) {
				continue;
			}
			if ( null === $newest || version_compare( $entry['version'], $newest['version'], '>' ) ) {
				$newest = $entry;
			}
		}
		return $newest;
	}
}

if ( ! function_exists( 'artws_php_security_notice_load' ) ) {
	/**
	 * Loads the newest registered copy and creates the notice.
	 *
	 * @return void
	 */
	function artws_php_security_notice_load() {
		$registry = isset( $GLOBALS['artws_php_security_notice_registry'] ) ? $GLOBALS['artws_php_security_notice_registry'] : array();
		$newest   = artws_php_security_notice_newest( $registry );
		if ( null === $newest ) {
			return;
		}
		if ( ! class_exists( 'ArtisanWorkshop\PhpSecurityNotice\Notice', false ) ) {
			// The plugin's directory can be missing for a moment while the plugin is updated.
			if ( ! is_readable( $newest['class_file'] ) ) {
				return;
			}
			require_once $newest['class_file'];
		}
		if ( class_exists( 'ArtisanWorkshop\PhpSecurityNotice\Notice', false ) ) {
			\ArtisanWorkshop\PhpSecurityNotice\Notice::boot( $newest['strings_callback'] );
		}
	}

	add_action( 'plugins_loaded', 'artws_php_security_notice_load', 5 );
}
