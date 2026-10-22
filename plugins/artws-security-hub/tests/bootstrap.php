<?php
/**
 * PHPUnit bootstrap for artws-security-hub.
 *
 * Loads the WordPress test library (WP_TESTS_DIR) and the plugin. Locally,
 * `composer test-local` points it at the environment that the repository
 * root's `composer test-local` installs; CI installs it with the root's
 * bin/install-wp-tests.sh.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI messages before WordPress is loaded.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP_TESTS_* constants are read by the WordPress test library.

/*
 * The JSON contract shared with Japanized for WooCommerce lives at the
 * repository root, so both test suites read the same files.
 */
define( 'ARTWS_SEC_CONTRACT_DIR', dirname( __DIR__, 3 ) . '/tests/fixtures/security-feed' );
define( 'ARTWS_SEC_SCHEMA_BASE_URI', 'https://wc.artws.info/schema/security/v1/' );

if ( ! is_dir( ARTWS_SEC_CONTRACT_DIR . '/schema' ) ) {
	fwrite( STDERR, 'JSON contract not found in ' . ARTWS_SEC_CONTRACT_DIR . PHP_EOL );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$artws_sec_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $artws_sec_tests_dir ) {
	$artws_sec_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}
if ( ! file_exists( $artws_sec_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "The WordPress test library was not found in $artws_sec_tests_dir. Run `composer test-local`, or set WP_TESTS_DIR." . PHP_EOL );
	exit( 1 );
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

// bin/test-local.sh uses a configuration of its own (another database than JP4WC's tests).
$artws_sec_tests_config = getenv( 'ARTWS_SEC_TESTS_CONFIG' );
if ( $artws_sec_tests_config ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', $artws_sec_tests_config );
}

// The feed is written to a throwaway directory, which each test empties.
define( 'ARTWS_SEC_FEED_DIR', rtrim( sys_get_temp_dir(), '/\\' ) . '/artws-sec-feed-tests-' . getmypid() );

require_once $artws_sec_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/artws-security-hub.php';
	}
);

require $artws_sec_tests_dir . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/class-artws-sec-feed-test-case.php';
