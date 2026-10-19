<?php
/**
 * PHPUnit bootstrap for artws-security-hub.
 *
 * The contract tests run without WordPress. Tests that need WordPress (S1 and
 * later) add the WordPress test library here.
 *
 * @package Artws_Security_Hub
 */

/*
 * The JSON contract shared with Japanized for WooCommerce lives at the
 * repository root, so both test suites read the same files.
 */
define( 'ARTWS_SEC_CONTRACT_DIR', dirname( __DIR__, 3 ) . '/tests/fixtures/security-feed' );
define( 'ARTWS_SEC_SCHEMA_BASE_URI', 'https://wc.artws.info/schema/security/v1/' );

if ( ! is_dir( ARTWS_SEC_CONTRACT_DIR . '/schema' ) ) {
	fwrite( STDERR, 'JSON contract not found in ' . ARTWS_SEC_CONTRACT_DIR . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI message, WordPress is not loaded.
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
