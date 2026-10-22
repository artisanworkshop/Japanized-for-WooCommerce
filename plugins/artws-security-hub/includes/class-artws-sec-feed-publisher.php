<?php
/**
 * Writes the feed files that the web server serves under /feed/v1/.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.AlternativeFunctions -- Writes and renames files in place; WP_Filesystem has no atomic rename and may not be set up during cron.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text messages, escaped where the settings page shows them.

/**
 * Artws_Sec_Feed_Publisher
 *
 * Every file is first written under a temporary name in the same directory
 * and then renamed over the served one, so a client never reads a half
 * written file. The vulnerability files are replaced before the manifest
 * that lists their sha256, as the contract asks.
 */
class Artws_Sec_Feed_Publisher {

	/**
	 * Served file name of each software type.
	 */
	const FILES = array(
		'plugin' => 'vuln-plugins.json.gz',
		'theme'  => 'vuln-themes.json.gz',
		'core'   => 'vuln-core.json.gz',
	);

	/**
	 * Served name of the manifest.
	 */
	const MANIFEST = 'manifest.json';

	/**
	 * Format version written into every file.
	 */
	const SCHEMA_VERSION = 1;

	/**
	 * Flags passed to json_encode() for the feed files.
	 */
	const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/**
	 * Suffix of temporary files.
	 */
	const TEMP_SUFFIX = '.tmp';

	/**
	 * Directory the files are written to.
	 *
	 * Not the document root's feed/ directory: that would stop WordPress from
	 * serving its RSS at /feed/. The web server maps /feed/v1/ to this
	 * directory with an alias (see README.md).
	 *
	 * @return string Absolute path without a trailing slash.
	 */
	public static function directory() {
		if ( defined( 'ARTWS_SEC_FEED_DIR' ) && is_string( ARTWS_SEC_FEED_DIR ) && '' !== ARTWS_SEC_FEED_DIR ) {
			return untrailingslashit( ARTWS_SEC_FEED_DIR );
		}
		return WP_CONTENT_DIR . '/artws-security-feed/v1';
	}

	/**
	 * Writes the three vulnerability files.
	 *
	 * @param array  $vulnerabilities Type => slug => feed records as JSON text (Artws_Sec_Feed_Builder).
	 * @param array  $copyrights      Copyright holders.
	 * @param string $attribution     Credit line.
	 * @param string $generated_at    Generation time (YYYY-MM-DDTHH:MM:SSZ).
	 * @return array<string, array{file: string, sha256: string, count: int}> Manifest entry of each type.
	 * @throws Artws_Sec_Feed_Exception When a file cannot be written.
	 */
	public static function write_vulnerability_files( array $vulnerabilities, array $copyrights, $attribution, $generated_at ) {
		self::prepare_directory();

		$prepared = array();
		try {
			foreach ( self::FILES as $type => $file ) {
				$by_slug = $vulnerabilities[ $type ] ?? array();
				$json    = self::encode_vulnerability_file( $type, $by_slug, $copyrights, $attribution, $generated_at );

				$gz = gzencode( $json, 9 );
				if ( false === $gz ) {
					throw new Artws_Sec_Feed_Exception( sprintf( 'The %s file could not be compressed.', $type ) );
				}

				$prepared[ $type ] = array(
					'temp'  => self::write_temp_file( $file, $gz ),
					'entry' => array(
						'file'   => $file,
						'sha256' => hash( 'sha256', $gz ),
						'count'  => array_sum( array_map( 'count', $by_slug ) ),
					),
				);
			}

			$entries = array();
			foreach ( $prepared as $type => $item ) {
				self::move_into_place( $item['temp'], self::FILES[ $type ] );
				$entries[ $type ] = $item['entry'];
			}
			return $entries;
		} finally {
			foreach ( $prepared as $item ) {
				if ( file_exists( $item['temp'] ) ) {
					wp_delete_file( $item['temp'] );
				}
			}
		}
	}

	/**
	 * Encodes one vulnerability file.
	 *
	 * The records arrive already encoded, so the file is assembled from
	 * encoded parts: the header object without its closing brace, then each
	 * slug (encoded as a string, so a numeric slug stays an object key) with
	 * its list of records.
	 *
	 * @param string $type         plugin, theme or core.
	 * @param array  $by_slug      Slug => records as JSON text.
	 * @param array  $copyrights   Copyright holders.
	 * @param string $attribution  Credit line.
	 * @param string $generated_at Generation time.
	 * @return string
	 * @throws Artws_Sec_Feed_Exception When a part cannot be encoded.
	 */
	private static function encode_vulnerability_file( $type, array $by_slug, array $copyrights, $attribution, $generated_at ) {
		$header = wp_json_encode(
			array(
				'schema_version' => self::SCHEMA_VERSION,
				'type'           => $type,
				'generated_at'   => $generated_at,
				'copyrights'     => (object) $copyrights,
				'attribution'    => $attribution,
			),
			self::JSON_FLAGS
		);
		if ( false === $header ) {
			throw new Artws_Sec_Feed_Exception( sprintf( 'The %s file could not be encoded as JSON.', $type ) );
		}

		$slugs = array();
		foreach ( $by_slug as $slug => $records ) {
			$name = wp_json_encode( (string) $slug, self::JSON_FLAGS );
			if ( false === $name ) {
				throw new Artws_Sec_Feed_Exception( sprintf( 'A %s slug could not be encoded as JSON.', $type ) );
			}
			$slugs[] = $name . ':[' . implode( ',', $records ) . ']';
		}

		return substr( $header, 0, -1 ) . ',"vulnerabilities":{' . implode( ',', $slugs ) . '}}';
	}

	/**
	 * Builds the manifest from the last published state.
	 *
	 * @param array $state Option Artws_Sec_Feed_Updater::STATE_OPTION.
	 * @param array $eol   Oldest supported versions, keyed like Artws_Sec_Feed_Updater::DEFAULT_EOL.
	 * @return array
	 */
	public static function render_manifest( array $state, array $eol ) {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'version'        => $state['version'],
			'generated_at'   => $state['generated_at'],
			'stale'          => (bool) $state['stale'],
			'files'          => $state['files'],
			'copyrights'     => (object) $state['copyrights'],
			'attribution'    => $state['attribution'],
			'eol'            => array(
				'php'         => array( 'min_supported' => $eol['php'] ),
				'wordpress'   => array( 'min_supported' => $eol['wordpress'] ),
				'woocommerce' => array( 'min_supported' => $eol['woocommerce'] ),
			),
		);
	}

	/**
	 * Writes the manifest.
	 *
	 * @param array $manifest Manifest from render_manifest().
	 * @throws Artws_Sec_Feed_Exception When it cannot be written.
	 */
	public static function write_manifest( array $manifest ) {
		$json = wp_json_encode( $manifest, JSON_PRETTY_PRINT | self::JSON_FLAGS );
		if ( false === $json ) {
			throw new Artws_Sec_Feed_Exception( 'The manifest could not be encoded as JSON.' );
		}

		self::prepare_directory();
		$temp = self::write_temp_file( self::MANIFEST, $json . "\n" );
		try {
			self::move_into_place( $temp, self::MANIFEST );
		} finally {
			if ( file_exists( $temp ) ) {
				wp_delete_file( $temp );
			}
		}
	}

	/**
	 * Deletes temporary files that a run killed by a fatal error left behind.
	 *
	 * @param int $older_than Unix time; files modified before it are deleted.
	 */
	public static function delete_stale_temp_files( $older_than ) {
		$files = glob( self::directory() . '/.*' . self::TEMP_SUFFIX );
		foreach ( is_array( $files ) ? $files : array() as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $older_than ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * Creates the directory when it is missing.
	 *
	 * @throws Artws_Sec_Feed_Exception When it cannot be created or written to.
	 */
	private static function prepare_directory() {
		$directory = self::directory();
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			throw new Artws_Sec_Feed_Exception( 'The feed directory could not be created.' );
		}
		if ( ! wp_is_writable( $directory ) ) {
			throw new Artws_Sec_Feed_Exception( 'The feed directory is not writable.' );
		}
	}

	/**
	 * Writes bytes to a new temporary file next to the served file.
	 *
	 * @param string $file  Served file name.
	 * @param string $bytes Content.
	 * @return string Path of the temporary file.
	 * @throws Artws_Sec_Feed_Exception When it cannot be written.
	 */
	private static function write_temp_file( $file, $bytes ) {
		$temp = self::directory() . '/.' . $file . '.' . bin2hex( random_bytes( 6 ) ) . self::TEMP_SUFFIX;

		if ( strlen( $bytes ) !== file_put_contents( $temp, $bytes ) ) {
			if ( file_exists( $temp ) ) {
				wp_delete_file( $temp );
			}
			throw new Artws_Sec_Feed_Exception( sprintf( '%s could not be written.', $file ) );
		}
		// The web server may run as another user than PHP.
		chmod( $temp, 0644 );

		return $temp;
	}

	/**
	 * Renames a temporary file over the served file.
	 *
	 * @param string $temp Temporary file.
	 * @param string $file Served file name.
	 * @throws Artws_Sec_Feed_Exception When the rename fails.
	 */
	private static function move_into_place( $temp, $file ) {
		if ( ! rename( $temp, self::directory() . '/' . $file ) ) {
			throw new Artws_Sec_Feed_Exception( sprintf( '%s could not be replaced.', $file ) );
		}
	}
}
