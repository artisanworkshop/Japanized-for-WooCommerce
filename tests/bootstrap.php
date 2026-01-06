<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Japanized_For_WooCommerce
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'UNIT_TESTS_DATA_PLUGIN_DIR', TESTS_PLUGIN_DIR . '/tests/Data/' );

// Explicitly define WP_CORE_DIR (the location of the WordPress core) to prevent errors.
if ( ! defined( 'WP_CORE_DIR' ) ) {
	$_wp_core_dir = getenv( 'WP_CORE_DIR' );
	if ( ! $_wp_core_dir ) {
		$_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
	}
	define( 'WP_CORE_DIR', $_wp_core_dir );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load WooCommerce first.
	$woocommerce_plugin = getenv( 'WC_PLUGIN_DIR' );
	if ( ! $woocommerce_plugin ) {
		// Try to load from WordPress plugins directory.
		$_wp_core_dir = getenv( 'WP_CORE_DIR' );
		if ( ! $_wp_core_dir ) {
			$_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
		}
		$woocommerce_plugin = $_wp_core_dir . '/wp-content/plugins/woocommerce/woocommerce.php';
	}

	if ( file_exists( $woocommerce_plugin ) ) {
		require_once $woocommerce_plugin;
		echo "WooCommerce loaded from: {$woocommerce_plugin}" . PHP_EOL;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Mark WooCommerce as active for plugin detection.
		$active_plugins = get_option( 'active_plugins', array() );
		if ( ! in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
			$active_plugins[] = 'woocommerce/woocommerce.php';
			update_option( 'active_plugins', $active_plugins );
		}
	} else {
		echo "Warning: WooCommerce not found at {$woocommerce_plugin}" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo 'WooCommerce-dependent tests may fail.' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// Load the plugin being tested.
	require dirname( __DIR__ ) . '/woocommerce-for-japan.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Install WooCommerce after loading.
 */
function _install_woocommerce() {
	// Define WooCommerce constants.
	define( 'WC_REMOVE_ALL_DATA', true );

	// Install WooCommerce.
	if ( class_exists( 'WC_Install' ) ) {
		WC_Install::install();

		// Reload capabilities and create tables.
		if ( class_exists( 'WC_Install' ) && method_exists( 'WC_Install', 'create_tables' ) ) {
			WC_Install::create_tables();
		}

		// Set WooCommerce as installed.
		update_option( 'woocommerce_db_version', WC()->version );

		echo 'WooCommerce tables created successfully' . PHP_EOL;
	}
}

tests_add_filter( 'setup_theme', '_install_woocommerce' );

/**
 * Initialize JP4WC plugin after WooCommerce is loaded.
 */
function _init_jp4wc_plugin() {
	// Initialize JP4WC plugin.
	if ( function_exists( 'jp4wc_plugin' ) ) {
		jp4wc_plugin();
		echo 'JP4WC plugin initialized successfully' . PHP_EOL;
	}
}

tests_add_filter( 'plugins_loaded', '_init_jp4wc_plugin', 100 );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
