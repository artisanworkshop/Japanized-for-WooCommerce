<?php
/**
 * Collects the feed records of one Wordfence download.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Feed_Builder
 *
 * Receives the V3 records one at a time, keeps the feed records that pass
 * Artws_Sec_Record_Validator under their type and slug, and counts what it
 * leaves out. A record that fails the check is dropped on its own; whether
 * too many were dropped is for Artws_Sec_Feed_Updater to decide.
 */
class Artws_Sec_Feed_Builder {

	/**
	 * How many excluded records are kept as examples for the settings page.
	 */
	const SAMPLE_LIMIT = 20;

	/**
	 * Type => slug => feed records, each kept as JSON text.
	 *
	 * A record held as a string takes about a fifth of the memory of the same
	 * record as nested PHP arrays, which matters with tens of thousands of them.
	 *
	 * @var array<string, array<string, array<int, string>>>
	 */
	private $vulnerabilities = array(
		'plugin' => array(),
		'theme'  => array(),
		'core'   => array(),
	);

	/**
	 * Copyright holders of the published records, first value seen per holder.
	 *
	 * @var array<string, array>
	 */
	private $copyrights = array();

	/**
	 * Counters shown on the settings page.
	 *
	 * @var array
	 */
	private $stats = array(
		'records'             => 0,
		'informational'       => 0,
		'candidates'          => 0,
		'excluded'            => 0,
		'excluded_reasons'    => array(),
		'excluded_samples'    => array(),
		'copyright_conflicts' => 0,
	);

	/**
	 * Adds one member of the V3 feed.
	 *
	 * @param string $key  Member name (the V3 record ID).
	 * @param string $json The record as raw JSON.
	 */
	public function add( $key, $json ) {
		++$this->stats['records'];

		$record = json_decode( $json, true );
		if ( ! is_array( $record ) ) {
			$this->exclude( $key, '', 'not a JSON object' );
			return;
		}

		try {
			$converted = Artws_Sec_Feed_Converter::convert( $record );
		} catch ( UnexpectedValueException $e ) {
			$this->exclude( $key, '', $e->getMessage() );
			return;
		}

		if ( $converted['informational'] ) {
			++$this->stats['informational'];
			return;
		}

		$copyright_error = Artws_Sec_Record_Validator::check_copyrights( $converted['copyrights'] );
		$published       = false;
		foreach ( $converted['records'] as $item ) {
			$type   = $item['software']['type'];
			$slug   = $item['software']['slug'];
			$reason = $copyright_error ?? Artws_Sec_Record_Validator::check_record( $item, $type );

			$encoded = null === $reason ? wp_json_encode( $item, Artws_Sec_Feed_Publisher::JSON_FLAGS ) : false;
			if ( false === $encoded ) {
				$this->exclude( $key, $slug, $reason ?? 'JSON encoding' );
				continue;
			}

			++$this->stats['candidates'];
			$this->vulnerabilities[ $type ][ $slug ][] = $encoded;
			$published                                 = true;
		}

		if ( $published ) {
			$this->add_copyrights( $converted['copyrights'] );
		}
	}

	/**
	 * Returns what was collected.
	 *
	 * @return array{vulnerabilities: array, counts: array<string, int>, copyrights: array, stats: array}
	 *               vulnerabilities holds each record as JSON text (see $vulnerabilities).
	 */
	public function result() {
		$counts = array();
		foreach ( $this->vulnerabilities as $type => $by_slug ) {
			$counts[ $type ] = array_sum( array_map( 'count', $by_slug ) );
		}

		return array(
			'vulnerabilities' => $this->vulnerabilities,
			'counts'          => $counts,
			'copyrights'      => $this->copyrights,
			'stats'           => $this->stats,
		);
	}

	/**
	 * Counts a record that is left out.
	 *
	 * Every left-out feed record (and every V3 record that could not be
	 * converted) is a candidate, so the excluded share is excluded / candidates.
	 *
	 * @param string $key    V3 record ID.
	 * @param mixed  $slug   Slug of the affected software, if known.
	 * @param string $reason The check that failed.
	 */
	private function exclude( $key, $slug, $reason ) {
		++$this->stats['candidates'];
		++$this->stats['excluded'];
		$this->stats['excluded_reasons'][ $reason ] = ( $this->stats['excluded_reasons'][ $reason ] ?? 0 ) + 1;

		if ( count( $this->stats['excluded_samples'] ) < self::SAMPLE_LIMIT ) {
			$this->stats['excluded_samples'][] = array(
				'id'     => self::sample_text( $key ),
				'slug'   => self::sample_text( $slug ),
				'reason' => $reason,
			);
		}
	}

	/**
	 * Adds a published record's copyright holders.
	 *
	 * The same holder normally has the same notice in every record. When two
	 * records disagree, the first one is kept and the conflict is counted.
	 *
	 * @param array $copyrights Holders that passed Artws_Sec_Record_Validator::check_copyrights().
	 */
	private function add_copyrights( array $copyrights ) {
		foreach ( $copyrights as $holder => $copyright ) {
			$holder = (string) $holder;
			if ( ! isset( $this->copyrights[ $holder ] ) ) {
				$this->copyrights[ $holder ] = $copyright;
			} elseif ( $this->copyrights[ $holder ] !== $copyright ) {
				++$this->stats['copyright_conflicts'];
			}
		}
	}

	/**
	 * Shortens a value from the feed for the examples on the settings page.
	 *
	 * @param mixed $value Record ID or slug.
	 * @return string
	 */
	private static function sample_text( $value ) {
		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return '';
		}
		$value = (string) $value;
		return mb_check_encoding( $value, 'UTF-8' ) ? mb_substr( $value, 0, 200, 'UTF-8' ) : '';
	}
}
