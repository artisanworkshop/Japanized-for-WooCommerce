<?php
/**
 * Matches the installed software against the vulnerability feed.
 *
 * @package Japanized_For_WooCommerce
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-jp4wc-security-feed-reader.php';

/**
 * JP4WC_Security_Scanner
 *
 * Reads the vulnerability files that JP4WC_Security_Feed keeps and returns the
 * records that affect the installed plugins, themes and WordPress. The rules
 * are those of the feed contract (tests/fixtures/security-feed/README.md):
 * a version is affected when one of the record's ranges holds it, the plugin
 * slug is its directory name (or its file name without `.php`), the theme slug
 * is its directory name and the core slug is `wordpress`.
 */
class JP4WC_Security_Scanner {

	/**
	 * CVSS ratings of the contract, lowest first.
	 *
	 * A record with any other rating is skipped: the contract treats a new value
	 * as an incompatible change, and a reader skips what it does not know.
	 */
	const RATINGS = array( 'None', 'Low', 'Medium', 'High', 'Critical' );

	/**
	 * Returns the installed software, by type and slug.
	 *
	 * Software without a version is left out. version_compare() treats an
	 * empty version as older than any other, so it would match every range
	 * that has no lower bound.
	 *
	 * @return array<string, array<string, array<int, array{name: string, version: string, file: string}>>>
	 *         Type (plugin, theme, core) => slug => installed copies.
	 */
	public static function installed_software() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = array(
			'plugin' => array(),
			'theme'  => array(),
			'core'   => array(),
		);

		foreach ( get_plugins() as $file => $data ) {
			$file    = (string) $file;
			$slug    = false === strpos( $file, '/' ) ? basename( $file, '.php' ) : strtok( $file, '/' );
			$version = isset( $data['Version'] ) && is_string( $data['Version'] ) ? trim( $data['Version'] ) : '';
			if ( '' === $slug || '' === $version ) {
				continue;
			}
			$name                           = isset( $data['Name'] ) && is_string( $data['Name'] ) && '' !== $data['Name'] ? $data['Name'] : $slug;
			$installed['plugin'][ $slug ][] = array(
				'name'    => $name,
				'version' => $version,
				'file'    => $file,
			);
		}

		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			$version = trim( (string) $theme->get( 'Version' ) );
			if ( '' === $version ) {
				continue;
			}
			$name = (string) $theme->get( 'Name' );
			$installed['theme'][ (string) $stylesheet ][] = array(
				'name'    => '' !== $name ? $name : (string) $stylesheet,
				'version' => $version,
				'file'    => '',
			);
		}

		$version = trim( (string) get_bloginfo( 'version' ) );
		if ( '' !== $version ) {
			$installed['core']['wordpress'][] = array(
				'name'    => 'WordPress',
				'version' => $version,
				'file'    => '',
			);
		}

		/**
		 * Filters the software the security check matches against the vulnerability feed.
		 *
		 * @since 3.0.0
		 * @param array $installed Type (plugin, theme, core) => slug => list of
		 *                         array( 'name', 'version', 'file' ). `file` is the
		 *                         plugin file relative to the plugins directory, or ''.
		 */
		return apply_filters( 'jp4wc_security_installed_software', $installed );
	}

	/**
	 * Returns the installed software that the vulnerability files list.
	 *
	 * A file that cannot be read is skipped, and a file that turns out to be
	 * damaged keeps what was found before the damage.
	 *
	 * @param array<string, string> $files     Type => path of its vulnerability file (decompressed JSON).
	 * @param array                 $installed Installed software, as installed_software() returns it.
	 * @return array<int, array> Each affected copy: type, slug, name, version, file and
	 *                           vulnerabilities (normalized records, most severe first).
	 */
	public static function scan( array $files, array $installed ) {
		$items = array();
		foreach ( $files as $type => $path ) {
			if ( empty( $installed[ $type ] ) || ! is_array( $installed[ $type ] ) || ! is_readable( $path ) ) {
				continue;
			}
			$wanted = $installed[ $type ];
			try {
				JP4WC_Security_Feed_Reader::read(
					$path,
					static function () {},
					static function ( $slug, $json ) use ( $type, $wanted, &$items ) {
						if ( ! isset( $wanted[ $slug ] ) || ! is_array( $wanted[ $slug ] ) ) {
							return;
						}
						$records = json_decode( $json, true );
						if ( ! is_array( $records ) ) {
							return;
						}
						foreach ( $wanted[ $slug ] as $software ) {
							$item = self::match_software( $type, $slug, $software, $records );
							if ( null !== $item ) {
								$items[] = $item;
							}
						}
					}
				);
			} catch ( UnexpectedValueException $e ) {
				// The file was checked when it was downloaded; keep what was found.
				continue;
			}
		}

		usort(
			$items,
			static function ( $a, $b ) {
				$by_severity = self::rating_rank( $b['vulnerabilities'][0]['rating'] ) <=> self::rating_rank( $a['vulnerabilities'][0]['rating'] );
				return 0 !== $by_severity ? $by_severity : strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $items;
	}

	/**
	 * Returns the records that affect one installed copy.
	 *
	 * @param string $type     Software type.
	 * @param string $slug     Slug.
	 * @param mixed  $software One entry of installed_software().
	 * @param array  $records  Records listed under the slug.
	 * @return array|null The affected copy, or null when no record affects it.
	 */
	private static function match_software( $type, $slug, $software, array $records ) {
		if ( ! is_array( $software ) || ! isset( $software['version'] ) || ! is_string( $software['version'] ) || '' === $software['version'] ) {
			return null;
		}

		$vulnerabilities = array();
		foreach ( $records as $record ) {
			$record = self::normalize_record( $record, $type, $slug );
			if ( null === $record || isset( $vulnerabilities[ $record['id'] ] ) ) {
				continue;
			}
			if ( self::is_affected( $software['version'], $record['affected_versions'] ) ) {
				unset( $record['affected_versions'] );
				$vulnerabilities[ $record['id'] ] = $record;
			}
		}
		if ( array() === $vulnerabilities ) {
			return null;
		}

		$vulnerabilities = array_values( $vulnerabilities );
		usort(
			$vulnerabilities,
			static function ( $a, $b ) {
				$by_rating = self::rating_rank( $b['rating'] ) <=> self::rating_rank( $a['rating'] );
				return 0 !== $by_rating ? $by_rating : ( (float) $b['score'] <=> (float) $a['score'] );
			}
		);

		return array(
			'type'            => $type,
			'slug'            => (string) $slug,
			'name'            => isset( $software['name'] ) && is_string( $software['name'] ) ? $software['name'] : (string) $slug,
			'version'         => $software['version'],
			'file'            => isset( $software['file'] ) && is_string( $software['file'] ) ? $software['file'] : '',
			'vulnerabilities' => $vulnerabilities,
		);
	}

	/**
	 * Checks one record and keeps the fields the security check uses.
	 *
	 * The feed is not checked against its JSON Schema here (contract README):
	 * a record is skipped when a value this method uses is missing or of the
	 * wrong kind, or is a value the contract does not define.
	 *
	 * @param mixed  $record Decoded record.
	 * @param string $type   Type of the file the record comes from.
	 * @param string $slug   Slug the record is listed under.
	 * @return array|null id, title, reference, rating ('' without CVSS), score
	 *                    (null without CVSS), patched, patched_versions and
	 *                    affected_versions; null to skip the record.
	 */
	public static function normalize_record( $record, $type, $slug ) {
		if ( ! is_array( $record ) || ! isset( $record['software'] ) || ! is_array( $record['software'] ) ) {
			return null;
		}
		$software = $record['software'];
		if ( ! isset( $software['type'], $software['slug'] ) || $type !== $software['type'] || (string) $slug !== $software['slug'] ) {
			return null;
		}
		if ( ! isset( $record['id'] ) || ! is_string( $record['id'] ) || 1 !== preg_match( '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D', $record['id'] ) ) {
			return null;
		}
		if ( ! isset( $record['title'] ) || ! is_string( $record['title'] ) || '' === $record['title'] ) {
			return null;
		}
		if ( ! isset( $record['reference'] ) || ! self::is_https_url( $record['reference'] ) ) {
			return null;
		}

		$ranges = self::normalize_ranges( isset( $record['affected_versions'] ) ? $record['affected_versions'] : null );
		if ( null === $ranges ) {
			return null;
		}

		$rating = '';
		$score  = null;
		if ( isset( $record['cvss'] ) ) {
			$cvss = $record['cvss'];
			if ( ! is_array( $cvss ) || ! isset( $cvss['rating'] ) || ! in_array( $cvss['rating'], self::RATINGS, true ) ) {
				return null;
			}
			$rating = $cvss['rating'];
			$score  = isset( $cvss['score'] ) && ( is_int( $cvss['score'] ) || is_float( $cvss['score'] ) ) ? (float) $cvss['score'] : null;
		}

		$patched_versions = array();
		if ( isset( $record['patched_versions'] ) && is_array( $record['patched_versions'] ) ) {
			foreach ( $record['patched_versions'] as $version ) {
				if ( is_string( $version ) && '' !== $version ) {
					$patched_versions[] = $version;
				}
			}
		}

		return array(
			'id'                => strtolower( $record['id'] ),
			'title'             => $record['title'],
			'reference'         => $record['reference'],
			'rating'            => $rating,
			'score'             => $score,
			'patched'           => isset( $record['patched'] ) && true === $record['patched'],
			'patched_versions'  => $patched_versions,
			'affected_versions' => $ranges,
		);
	}

	/**
	 * Checks the version ranges of a record.
	 *
	 * @param mixed $ranges `affected_versions` of a record.
	 * @return array|null The ranges, or null when one of them is malformed.
	 */
	private static function normalize_ranges( $ranges ) {
		if ( ! is_array( $ranges ) || array() === $ranges ) {
			return null;
		}
		$result = array();
		foreach ( $ranges as $range ) {
			if ( ! is_array( $range ) || ! isset( $range['from_version'], $range['to_version'], $range['from_inclusive'], $range['to_inclusive'] ) ) {
				return null;
			}
			foreach ( array( 'from_version', 'to_version' ) as $key ) {
				if ( ! is_string( $range[ $key ] ) || 1 !== preg_match( '/^\S+$/D', $range[ $key ] ) ) {
					return null;
				}
			}
			if ( ! is_bool( $range['from_inclusive'] ) || ! is_bool( $range['to_inclusive'] ) ) {
				return null;
			}
			$result[] = array(
				'from_version'   => $range['from_version'],
				'from_inclusive' => $range['from_inclusive'],
				'to_version'     => $range['to_version'],
				'to_inclusive'   => $range['to_inclusive'],
			);
		}
		return $result;
	}

	/**
	 * Whether a version lies in one of the ranges.
	 *
	 * The rule of the contract (and of Wordfence Intelligence V3): `*` is no
	 * bound, and each bound is compared with version_compare().
	 *
	 * @param string $version Installed version.
	 * @param array  $ranges  Ranges as normalize_record() returns them.
	 * @return bool
	 */
	public static function is_affected( $version, array $ranges ) {
		if ( ! is_string( $version ) || '' === $version ) {
			return false;
		}
		foreach ( $ranges as $range ) {
			$from = $range['from_version'];
			$to   = $range['to_version'];

			$above_from = '*' === $from
				|| version_compare( $from, $version, '<' )
				|| ( $range['from_inclusive'] && version_compare( $from, $version, '==' ) );
			$below_to   = '*' === $to
				|| version_compare( $version, $to, '<' )
				|| ( $range['to_inclusive'] && version_compare( $version, $to, '==' ) );

			if ( $above_from && $below_to ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the highest rating among the affected copies.
	 *
	 * @param array $items Affected copies, as scan() returns them.
	 * @return string A rating of RATINGS, or '' when no record has one.
	 */
	public static function max_rating( array $items ) {
		$max = '';
		foreach ( $items as $item ) {
			foreach ( $item['vulnerabilities'] as $vulnerability ) {
				if ( self::rating_rank( $vulnerability['rating'] ) > self::rating_rank( $max ) ) {
					$max = $vulnerability['rating'];
				}
			}
		}
		return $max;
	}

	/**
	 * Returns the rank of a rating: -1 for none, then 0 (None) to 4 (Critical).
	 *
	 * @param string $rating Rating.
	 * @return int
	 */
	public static function rating_rank( $rating ) {
		$rank = array_search( $rating, self::RATINGS, true );
		return false === $rank ? -1 : $rank;
	}

	/**
	 * Whether a value is an https URL, as the contract defines it.
	 *
	 * @param mixed $url Value.
	 * @return bool
	 */
	public static function is_https_url( $url ) {
		return is_string( $url ) && strlen( $url ) <= 2048 && 1 === preg_match( '~^https://[^\s/?#]+\S*$~D', $url );
	}
}
