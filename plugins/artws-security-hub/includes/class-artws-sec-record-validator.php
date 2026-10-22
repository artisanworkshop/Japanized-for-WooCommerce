<?php
/**
 * Checks feed records against the JSON contract at run time.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Record_Validator
 *
 * A hand-written copy of the "vulnerability" definition in
 * tests/fixtures/security-feed/schema/vuln-file.schema.json, plus the
 * constraints the file puts on its records (software.type matches the file,
 * the core file has only the core slug, CORE_SLUG) and the copyright holders in
 * common.schema.json. The plugin does not load Composer packages at run time,
 * so it cannot use the JSON Schema itself; the tests check that whatever this
 * class accepts, the schema accepts too.
 *
 * String lengths count code points, as JSON Schema does. Where a schema
 * pattern uses "\s", the ECMA-262 whitespace characters are excluded as well,
 * so this class may be stricter than a validator that reads "\s" as ASCII.
 */
class Artws_Sec_Record_Validator {

	/**
	 * Software types and the file each one goes to.
	 */
	const TYPES = array( 'plugin', 'theme', 'core' );

	/**
	 * The only slug in the core file.
	 */
	const CORE_SLUG = 'wordpress'; // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- A slug, not the product name.

	/**
	 * CVSS ratings the contract allows.
	 */
	const RATINGS = array( 'None', 'Low', 'Medium', 'High', 'Critical' );

	/**
	 * Keys of a feed record, in the order the converter writes them.
	 */
	const RECORD_KEYS = array( 'id', 'title', 'software', 'affected_versions', 'patched', 'patched_versions', 'remediation', 'cvss', 'published', 'reference' );

	/**
	 * Whitespace as ECMA-262 "\s" matches it (plus U+0085), for patterns written as [^\s].
	 */
	const WHITESPACE = '\s\x{0085}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}';

	/**
	 * Returns why a feed record may not be published, or null when it may.
	 *
	 * @param mixed  $record Feed record built by Artws_Sec_Feed_Converter.
	 * @param string $type   Type of the file the record goes to.
	 * @return string|null
	 */
	public static function check_record( $record, $type ) {
		if ( ! is_array( $record ) || ! self::has_keys( $record, self::RECORD_KEYS ) ) {
			return 'record keys';
		}

		if ( ! is_string( $record['id'] ) || ! preg_match( '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D', $record['id'] ) ) {
			return 'id';
		}
		if ( ! self::is_text( $record['title'], 1, 1000 ) ) {
			return 'title';
		}

		$software = $record['software'];
		if ( ! is_array( $software ) || ! self::has_keys( $software, array( 'type', 'slug' ) ) ) {
			return 'software';
		}
		if ( ! in_array( $type, self::TYPES, true ) || $type !== $software['type'] ) {
			return 'software.type';
		}
		if ( ! self::is_slug( $software['slug'] ) || ( 'core' === $type && self::CORE_SLUG !== $software['slug'] ) ) {
			return 'software.slug';
		}

		$ranges = $record['affected_versions'];
		if ( ! is_array( $ranges ) || ! array_is_list( $ranges ) || array() === $ranges ) {
			return 'affected_versions';
		}
		foreach ( $ranges as $index => $range ) {
			if ( ! is_array( $range ) || ! self::has_keys( $range, array( 'from_version', 'from_inclusive', 'to_version', 'to_inclusive' ) ) ) {
				return "affected_versions[$index]";
			}
			foreach ( array( 'from_version', 'to_version' ) as $key ) {
				if ( ! self::is_version( $range[ $key ] ) ) {
					return "affected_versions[$index].$key";
				}
			}
			foreach ( array( 'from_inclusive', 'to_inclusive' ) as $key ) {
				if ( ! is_bool( $range[ $key ] ) ) {
					return "affected_versions[$index].$key";
				}
			}
		}

		if ( ! is_bool( $record['patched'] ) ) {
			return 'patched';
		}
		$patched_versions = $record['patched_versions'];
		if ( ! is_array( $patched_versions ) || ! array_is_list( $patched_versions ) ) {
			return 'patched_versions';
		}
		foreach ( $patched_versions as $index => $version ) {
			if ( ! self::is_version( $version ) ) {
				return "patched_versions[$index]";
			}
		}
		if ( ! self::is_text( $record['remediation'], 0, 2000 ) ) {
			return 'remediation';
		}

		$cvss = $record['cvss'];
		if ( null !== $cvss ) {
			if ( ! is_array( $cvss ) || ! self::has_keys( $cvss, array( 'score', 'rating' ) ) ) {
				return 'cvss';
			}
			if ( ! ( is_int( $cvss['score'] ) || is_float( $cvss['score'] ) ) || $cvss['score'] < 0 || $cvss['score'] > 10 ) {
				return 'cvss.score';
			}
			if ( ! in_array( $cvss['rating'], self::RATINGS, true ) ) {
				return 'cvss.rating';
			}
		}

		if ( null !== $record['published'] && ! self::is_timestamp( $record['published'] ) ) {
			return 'published';
		}
		if ( ! self::is_https_url( $record['reference'] ) ) {
			return 'reference';
		}

		return null;
	}

	/**
	 * Returns why a record's copyright holders cannot be carried into the feed, or null.
	 *
	 * The data license asks for each holder's notice and license to be passed
	 * on, so a record whose holders do not fit the contract is not published.
	 *
	 * @param mixed $copyrights Holders returned by Artws_Sec_Feed_Converter::convert().
	 * @return string|null
	 */
	public static function check_copyrights( $copyrights ) {
		if ( ! is_array( $copyrights ) || ( array() !== $copyrights && array_is_list( $copyrights ) ) ) {
			return 'copyrights';
		}
		foreach ( $copyrights as $holder => $copyright ) {
			if ( ! preg_match( '/^[a-z0-9_-]+$/D', (string) $holder ) ) {
				return 'copyrights holder';
			}
			if ( ! is_array( $copyright ) || ! self::has_keys( $copyright, array( 'notice', 'license', 'license_url' ) ) ) {
				return "copyrights.$holder";
			}
			if ( ! self::is_text( $copyright['notice'], 1, null ) || ! self::is_text( $copyright['license'], 1, null ) ) {
				return "copyrights.$holder";
			}
			if ( ! self::is_https_url( $copyright['license_url'] ) ) {
				return "copyrights.$holder.license_url";
			}
		}
		return null;
	}

	/**
	 * Whether a value is a slug the contract allows.
	 *
	 * @param mixed $slug Value to check.
	 * @return bool
	 */
	public static function is_slug( $slug ) {
		return self::is_text( $slug, 1, 200 ) && 1 === preg_match( '/^[^\/\\\\\x00-\x1f]+$/D', $slug );
	}

	/**
	 * Whether a value is a version as the feed carries it ("*" for no bound).
	 *
	 * @param mixed $version Value to check.
	 * @return bool
	 */
	private static function is_version( $version ) {
		return self::is_text( $version, 1, 64 ) && 1 === preg_match( '/^[^' . self::WHITESPACE . ']+$/uD', $version );
	}

	/**
	 * Whether a value is a UTC timestamp in the contract's format.
	 *
	 * @param mixed $value Value to check.
	 * @return bool
	 */
	private static function is_timestamp( $value ) {
		return is_string( $value ) && 1 === preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value );
	}

	/**
	 * Whether a value is an https URL the contract allows.
	 *
	 * @param mixed $url Value to check.
	 * @return bool
	 */
	private static function is_https_url( $url ) {
		return self::is_text( $url, 0, 2048 ) && 1 === preg_match( '/^https:\/\/[^' . self::WHITESPACE . '\/?#]+[^' . self::WHITESPACE . ']*$/uD', $url );
	}

	/**
	 * Whether a string is valid UTF-8.
	 *
	 * PCRE checks the encoding of the subject in UTF mode, so this works
	 * without the mbstring extension, which WordPress does not require.
	 * (WordPress itself provides mb_strlen() and mb_substr() when it is missing.)
	 *
	 * @param string $value String to check.
	 * @return bool
	 */
	public static function is_utf8( $value ) {
		return 1 === preg_match( '//u', $value );
	}

	/**
	 * Whether a value is a UTF-8 string whose length in code points is within bounds.
	 *
	 * @param mixed    $value Value to check.
	 * @param int      $min   Minimum length.
	 * @param int|null $max   Maximum length, or null for none.
	 * @return bool
	 */
	private static function is_text( $value, $min, $max ) {
		if ( ! is_string( $value ) || ! self::is_utf8( $value ) ) {
			return false;
		}
		$length = mb_strlen( $value, 'UTF-8' );
		return $length >= $min && ( null === $max || $length <= $max );
	}

	/**
	 * Whether an array has exactly these keys.
	 *
	 * @param array    $value Array to check.
	 * @param string[] $keys  Expected keys.
	 * @return bool
	 */
	private static function has_keys( array $value, array $keys ) {
		$actual = array_map( 'strval', array_keys( $value ) );
		sort( $actual );
		sort( $keys );
		return $actual === $keys;
	}
}
