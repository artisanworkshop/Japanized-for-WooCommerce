<?php
/**
 * Registers two copies of the shared PHP security notice outside WordPress and
 * reports which one was loaded.
 *
 * Run by tests/Unit/test-jp4wc-php-security-notice.php in a PHP process of its
 * own, because the class can be declared only once per process:
 *
 *     php load-copies.php <dir A> <version A> <dir B> <version B>
 *
 * Each directory holds a copy of register.php and class-notice.php. Prints one
 * line of JSON.
 *
 * @package Japanized_For_WooCommerce
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Stands in for WordPress functions outside WordPress.

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['load_copies_hooks'] = array();

/**
 * Records a hook callback, like WordPress's add_action().
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 */
function add_action( $hook, $callback, $priority = 10 ) {
	$GLOBALS['load_copies_hooks'][] = array( $hook, $callback, $priority );
}

/**
 * Runs the callbacks recorded for a hook.
 *
 * @param string $hook Hook name.
 */
function load_copies_run( $hook ) {
	foreach ( $GLOBALS['load_copies_hooks'] as $entry ) {
		if ( $hook === $entry[0] ) {
			call_user_func( $entry[1] );
		}
	}
}

/**
 * Counts the callbacks recorded for a hook.
 *
 * @param string $hook Hook name.
 * @return int
 */
function load_copies_count( $hook ) {
	$count = 0;
	foreach ( $GLOBALS['load_copies_hooks'] as $entry ) {
		if ( $hook === $entry[0] ) {
			++$count;
		}
	}
	return $count;
}

// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Command-line arguments of a test helper.
list( , $dir_a, $version_a, $dir_b, $version_b ) = $argv;

require $dir_a . '/register.php';
artws_php_security_notice_register( $version_a, $dir_a . '/class-notice.php', 'strings_of_a' );
require $dir_b . '/register.php';
artws_php_security_notice_register( $version_b, $dir_b . '/class-notice.php', 'strings_of_b' );

// plugins_loaded runs once per request; running it twice checks that boot() creates one instance.
load_copies_run( 'plugins_loaded' );
load_copies_run( 'plugins_loaded' );

$notice_class = 'ArtisanWorkshop\PhpSecurityNotice\Notice';
$loaded_files = array();
foreach ( get_included_files() as $file ) {
	if ( 'class-notice.php' === basename( $file ) ) {
		$loaded_files[] = realpath( $file );
	}
}
$instance = class_exists( $notice_class, false ) ? $notice_class::instance() : null;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.json_encode_json_encode -- JSON for the test, outside WordPress.
echo json_encode(
	array(
		'loaded_files'           => $loaded_files,
		'class_file'             => class_exists( $notice_class, false ) ? realpath( ( new ReflectionClass( $notice_class ) )->getFileName() ) : null,
		'plugins_loaded_hooks'   => load_copies_count( 'plugins_loaded' ),
		'admin_init_hooks'       => load_copies_count( 'admin_init' ),
		'strings_callback'       => $instance ? ( new ReflectionProperty( $notice_class, 'strings_callback' ) )->getValue( $instance ) : null,
		'instance_is_registered' => null !== $instance,
	)
);
