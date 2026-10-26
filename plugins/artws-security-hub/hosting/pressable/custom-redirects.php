<?php
/**
 * Serves the vulnerability feed at /feed/v1/ on Pressable (DEPLOY.md 5-2).
 *
 * Pressable does not let a site change nginx, so the Alias of DEPLOY.md 5-1
 * cannot be made. Pressable runs htdocs/custom-redirects.php before WordPress
 * for every request that nginx does not answer with a file on disk, and
 * /feed/v1/ is not one: the files are in wp-content/artws-security-feed/v1/.
 * Upload this file as htdocs/custom-redirects.php, or, when the site already
 * has one, paste the code below the opening tag at the top of it.
 *
 * Only the four names the feed publishes (Artws_Sec_Feed_Publisher::FILES and
 * MANIFEST) are answered; every other request goes on to WordPress as before,
 * so /feed/ (RSS) is not affected. The file is sent as it is, not redirected:
 * JP4WC does not follow redirects for the feed.
 *
 * @package Artws_Security_Hub
 */

( static function () {
	// WP-CLI (the server cron jobs) may load this file too.
	if ( 'cli' === PHP_SAPI || ! isset( $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Only compared with fixed values; WordPress is not loaded yet.
	$method = (string) $_SERVER['REQUEST_METHOD'];
	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Only looked up in the list below.
	$path  = explode( '?', (string) $_SERVER['REQUEST_URI'], 2 )[0];
	$types = array(
		'/feed/v1/manifest.json'        => 'application/json',
		'/feed/v1/vuln-plugins.json.gz' => 'application/gzip',
		'/feed/v1/vuln-themes.json.gz'  => 'application/gzip',
		'/feed/v1/vuln-core.json.gz'    => 'application/gzip',
	);
	if ( ! isset( $types[ $path ] ) ) {
		return;
	}

	// The default ARTWS_SEC_FEED_DIR, written out: wp-config.php may not have been loaded yet.
	$file = __DIR__ . '/wp-content/artws-security-feed/v1/' . basename( $path );
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- WP_Filesystem is not loaded yet.
	$handle = is_file( $file ) ? fopen( $file, 'rb' ) : false;
	if ( false === $handle ) {
		return; // Not published yet: WordPress answers 404.
	}
	// The size of the file opened, not of the name: a feed run can rename a new file over it meanwhile.
	$stat = fstat( $handle );
	if ( false === $stat ) {
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- As fopen().
		return;
	}

	// Content-Encoding on the .json.gz would make the client inflate it, and the sha256 would not match.
	ini_set( 'zlib.output_compression', '0' ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- Turned off, before any output.
	header( 'Content-Type: ' . $types[ $path ] );
	header( 'Content-Length: ' . $stat['size'] );
	// Not cached, so that a CDN put in front later cannot pair a new manifest with an old .json.gz.
	header( 'Cache-Control: no-store' );
	header( 'X-Content-Type-Options: nosniff' );
	if ( 'HEAD' !== $method ) {
		fpassthru( $handle );
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- As fopen().
	exit;
} )();
