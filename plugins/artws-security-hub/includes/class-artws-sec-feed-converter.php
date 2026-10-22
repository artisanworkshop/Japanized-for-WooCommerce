<?php
/**
 * Reduces Wordfence Intelligence V3 production records to feed records.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Feed_Converter
 *
 * Follows "V3 からの変換" in tests/fixtures/security-feed/README.md: one feed
 * record per element of the V3 software list, with the V3 field names. It
 * copies values as they are; Artws_Sec_Record_Validator decides whether each
 * result may be published.
 */
class Artws_Sec_Feed_Converter {

	/**
	 * Page of a record on wordfence.com, followed by its ID.
	 */
	const REFERENCE_BASE = 'https://www.wordfence.com/threat-intel/vulnerabilities/id/';

	/**
	 * Converts one V3 record.
	 *
	 * @param array $record V3 record decoded as an associative array.
	 * @return array{informational: bool, records: array<int, array>, copyrights: mixed}
	 *               The feed records (one per affected software) and the record's
	 *               copyright holders without the V3 "message" key.
	 * @throws UnexpectedValueException When the record has no software list.
	 */
	public static function convert( array $record ) {
		if ( ! isset( $record['software'] ) || ! is_array( $record['software'] ) || ! array_is_list( $record['software'] ) ) {
			throw new UnexpectedValueException( 'software is not a list' );
		}

		$informational = isset( $record['informational'] ) && true === $record['informational'];
		$records       = array();

		if ( ! $informational ) {
			$id        = $record['id'] ?? null;
			$cvss      = self::cvss( $record['cvss'] ?? null );
			$published = self::published( $record['published'] ?? null );

			foreach ( $record['software'] as $software ) {
				$software  = is_array( $software ) ? $software : array();
				$records[] = array(
					'id'                => $id,
					'title'             => $record['title'] ?? null,
					'software'          => array(
						'type' => $software['type'] ?? null,
						'slug' => $software['slug'] ?? null,
					),
					'affected_versions' => self::affected_versions( $software['affected_versions'] ?? null ),
					'patched'           => $software['patched'] ?? null,
					'patched_versions'  => $software['patched_versions'] ?? null,
					'remediation'       => $software['remediation'] ?? null,
					'cvss'              => $cvss,
					'published'         => $published,
					'reference'         => is_string( $id ) ? self::REFERENCE_BASE . $id : null,
				);
			}
		}

		return array(
			'informational' => $informational,
			'records'       => $records,
			'copyrights'    => self::copyrights( $record['copyrights'] ?? array() ),
		);
	}

	/**
	 * Turns the V3 affected_versions object into a list of its ranges.
	 *
	 * The V3 keys only repeat the range as text ("* - 1.2.3"), so they are dropped.
	 *
	 * @param mixed $ranges V3 affected_versions.
	 * @return mixed A list of ranges, or the value unchanged when it is not an object.
	 */
	private static function affected_versions( $ranges ) {
		if ( ! is_array( $ranges ) ) {
			return $ranges;
		}

		$list = array();
		foreach ( $ranges as $range ) {
			$list[] = is_array( $range ) ? array(
				'from_version'   => $range['from_version'] ?? null,
				'from_inclusive' => $range['from_inclusive'] ?? null,
				'to_version'     => $range['to_version'] ?? null,
				'to_inclusive'   => $range['to_inclusive'] ?? null,
			) : $range;
		}
		return $list;
	}

	/**
	 * Keeps the score and rating of the V3 cvss object (drops the vector).
	 *
	 * @param mixed $cvss V3 cvss.
	 * @return mixed Null, the reduced object, or false when it is neither null nor an object.
	 */
	private static function cvss( $cvss ) {
		if ( null === $cvss ) {
			return null;
		}
		if ( ! is_array( $cvss ) ) {
			return false;
		}
		return array(
			'score'  => $cvss['score'] ?? null,
			'rating' => $cvss['rating'] ?? null,
		);
	}

	/**
	 * Formats the V3 published time (UTC) as YYYY-MM-DDTHH:MM:SSZ.
	 *
	 * Accepts "YYYY-MM-DD HH:MM:SS" (or with a "T"), optionally with fractional
	 * seconds and a "Z" or numeric offset. Anything else, and impossible dates,
	 * become null rather than a guess.
	 *
	 * @param mixed $published V3 published.
	 * @return string|null
	 */
	public static function published( $published ) {
		if ( ! is_string( $published ) ) {
			return null;
		}
		if ( ! preg_match( '/^([0-9]{4}-[0-9]{2}-[0-9]{2})[ T]([0-9]{2}:[0-9]{2}:[0-9]{2})(?:\.[0-9]+)?(Z|[+-][0-9]{2}:?[0-9]{2})?$/D', trim( $published ), $parts ) ) {
			return null;
		}

		$offset = isset( $parts[3] ) && '' !== $parts[3] ? $parts[3] : 'Z';
		$time   = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:sP', $parts[1] . 'T' . $parts[2] . $offset );
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $time || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
			return null;
		}

		return $time->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Returns the copyright holders of a record without the V3 "message" key.
	 *
	 * @param mixed $copyrights V3 copyrights.
	 * @return mixed Holder => {notice, license, license_url}, or the value unchanged when it is not an object.
	 */
	private static function copyrights( $copyrights ) {
		if ( ! is_array( $copyrights ) ) {
			return $copyrights;
		}

		$holders = array();
		foreach ( $copyrights as $holder => $copyright ) {
			if ( 'message' === $holder ) {
				continue;
			}
			$holders[ $holder ] = is_array( $copyright ) ? array(
				'notice'      => $copyright['notice'] ?? null,
				'license'     => $copyright['license'] ?? null,
				'license_url' => $copyright['license_url'] ?? null,
			) : $copyright;
		}
		return $holders;
	}
}
