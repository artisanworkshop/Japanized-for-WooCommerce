<?php
/**
 * Rebuilds the gzip copies of the sample vulnerability files and the
 * sha256 / count of each file in the sample manifest.
 *
 * Run it after editing a vuln-*.json sample:
 *
 *     php tests/fixtures/security-feed/regenerate.php
 *
 * The .json.gz files are committed so that tests can use the exact bytes a
 * client downloads, and the manifest's sha256 values must match them.
 *
 * @package Japanized_For_WooCommerce
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Standalone CLI script; WordPress is not loaded.

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$fixture_dir   = __DIR__;
$manifest_path = $fixture_dir . '/manifest.json';
$manifest      = json_decode( file_get_contents( $manifest_path ), true, 512, JSON_THROW_ON_ERROR );
$sources       = array(
	'plugin' => 'vuln-plugins.json',
	'theme'  => 'vuln-themes.json',
	'core'   => 'vuln-core.json',
);

foreach ( $sources as $software_type => $source ) {
	$json = file_get_contents( $fixture_dir . '/' . $source );
	$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
	$gz   = gzencode( $json, 9 );

	file_put_contents( $fixture_dir . '/' . $source . '.gz', $gz );

	$manifest['files'][ $software_type ] = array(
		'file'   => $source . '.gz',
		'sha256' => hash( 'sha256', $gz ),
		'count'  => array_sum( array_map( 'count', $data['vulnerabilities'] ) ),
	);
}

file_put_contents(
	$manifest_path,
	json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) . "\n"
);
