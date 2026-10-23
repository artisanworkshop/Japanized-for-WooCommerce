<?php
/**
 * Builds the report of a free security check.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text messages; the REST API answers 503 without them.

/**
 * Artws_Sec_Report_Builder
 *
 * Turns a checked request into report_json (report.schema.json): the score of
 * each requirement, the findings most urgent first, and the next steps.
 *
 * The server decides two checks itself: V-01 (known vulnerabilities) from the
 * vulnerability index and V-03 (unsupported versions) from the oldest
 * supported versions set on the settings page. The other checks take the
 * status the site reported. The checks, their severities and the score are
 * those of JP4WC_Security_Checks, so the report scores the site as its
 * Security tab does.
 */
class Artws_Sec_Report_Builder {

	/**
	 * Points a failed check takes off its requirement's score, by severity (JP4WC_Security_Checks::POINTS).
	 */
	const POINTS = array(
		'critical' => 25,
		'high'     => 15,
		'medium'   => 8,
		'low'      => 3,
	);

	/**
	 * Requirements, in the order of the score.
	 */
	const REQUIREMENTS = array( 'vuln', 'admin', 'data', 'malware' );

	/**
	 * Severities, most severe first.
	 */
	const SEVERITIES = array( 'critical', 'high', 'medium', 'low' );

	/**
	 * Requirement and severity of each check, in the order they run (JP4WC_Security_Checks::CHECKS).
	 *
	 * V-01 takes its severity from the most severe vulnerability found.
	 */
	const CHECKS = array(
		'V-01' => array( 'vuln', 'high' ),
		'V-02' => array( 'vuln', 'medium' ),
		'V-03' => array( 'vuln', 'high' ),
		'V-04' => array( 'vuln', 'low' ),
		'A-01' => array( 'admin', 'medium' ),
		'A-02' => array( 'admin', 'medium' ),
		'A-03' => array( 'admin', 'high' ),
		'A-04' => array( 'admin', 'medium' ),
		'A-05' => array( 'admin', 'low' ),
		'D-01' => array( 'data', 'high' ),
		'D-02' => array( 'data', 'critical' ),
		'D-03' => array( 'data', 'medium' ),
		'D-04' => array( 'data', 'high' ),
		'D-05' => array( 'data', 'critical' ),
		'M-01' => array( 'malware', 'low' ),
		'M-02' => array( 'malware', 'medium' ),
		'M-03' => array( 'malware', 'high' ),
	);

	/**
	 * Checks the server decides itself; the statuses the site reports for them are not used.
	 */
	const SERVER_CHECKS = array( 'V-01', 'V-03' );

	/**
	 * CVSS ratings from the least severe (JP4WC_Security_Scanner::RATINGS); a record without one ranks below them all.
	 */
	const RATINGS = array( 'None', 'Low', 'Medium', 'High', 'Critical' );

	/**
	 * Most findings in a report (report.schema.json).
	 */
	const MAX_FINDINGS = 500;

	/**
	 * Bands of a store whose size makes the critical and high items more urgent.
	 */
	const LARGE_BANDS = array( 'B3', 'B4', 'B5', 'O3', 'O4' );

	/**
	 * Builds the report.
	 *
	 * @param array  $request    Request from Artws_Sec_Scan_Request::normalize().
	 * @param array  $state      Published feed state (Artws_Sec_Feed_Updater::get_state()) with an index_version.
	 * @param array  $eol        Oldest supported versions (Artws_Sec_Feed_Updater::get_eol()).
	 * @param string $scan_token Token of the report.
	 * @param int    $now        Unix time.
	 * @return array
	 * @throws Artws_Sec_Feed_Exception When the vulnerability index cannot be read or is incomplete.
	 */
	public static function build( array $request, array $state, array $eol, $scan_token, $now ) {
		$language = Artws_Sec_Report_Templates::language( $request['locale'] );

		$statuses        = self::reported_statuses( $request['local_findings'] );
		$vulnerabilities = self::vulnerabilities( $request, (string) $state['index_version'] );
		$unsupported     = self::unsupported_versions( $request, $eol );

		if ( array() !== $vulnerabilities ) {
			$statuses['V-01'] = 'fail';
		} else {
			// From out-of-date data "nothing found" is not a pass, as on the Security tab.
			$statuses['V-01'] = $state['stale'] ? 'unknown' : 'pass';
		}
		$statuses['V-03'] = array() !== $unsupported ? 'fail' : 'pass';
		$statuses         = self::in_check_order( $statuses );

		$severities         = array_map( static fn( $check ) => $check[1], self::CHECKS );
		$severities['V-01'] = self::vulnerability_severity( array_column( $vulnerabilities, 'record' ) );

		// Each finding with the CVSS score that orders vulnerabilities of the same severity.
		$entries = array();
		foreach ( $vulnerabilities as $vulnerability ) {
			$entries[] = array( self::vulnerability_finding( $vulnerability, $language ), (float) ( $vulnerability['record']['cvss']['score'] ?? 0 ) );
		}
		if ( array() !== $unsupported ) {
			$entries[] = array( self::unsupported_finding( $unsupported, $language ), 0.0 );
		}
		foreach ( $statuses as $check_id => $status ) {
			if ( 'fail' !== $status || in_array( $check_id, self::SERVER_CHECKS, true ) ) {
				continue;
			}
			$text      = Artws_Sec_Report_Templates::check( $check_id, $language );
			$entries[] = array(
				array(
					'check_id'    => $check_id,
					'severity'    => $severities[ $check_id ],
					'requirement' => self::CHECKS[ $check_id ][0],
					'title'       => $text['title'],
					'detail'      => $text['detail'],
					'impact'      => $text['impact'],
					'fix'         => $text['fix'],
				),
				0.0,
			);
		}
		usort( $entries, array( __CLASS__, 'compare_findings' ) );
		$findings = array_column( array_slice( $entries, 0, self::MAX_FINDINGS ), 0 );

		return array(
			'schema_version' => 1,
			'token'          => $scan_token,
			'generated_at'   => gmdate( 'Y-m-d\TH:i:s\Z', $now ),
			// The version whose data the site was matched against.
			'feed_version'   => (string) $state['index_version'],
			'score'          => self::score( $statuses, $severities ),
			'findings'       => $findings,
			'next_steps'     => self::next_steps( $request, $statuses, $findings, $state, $language ),
			'attribution'    => (string) $state['attribution'],
		);
	}

	/**
	 * Returns the score of each requirement and the total, as JP4WC_Security_Checks::score() does.
	 *
	 * @param array<string, string> $statuses   Check ID => pass, fail or unknown.
	 * @param array<string, string> $severities Check ID => severity.
	 * @return array{total: int, vuln: int, admin: int, data: int, malware: int}
	 */
	public static function score( array $statuses, array $severities ) {
		$scores = array_fill_keys( self::REQUIREMENTS, 100 );
		foreach ( $statuses as $check_id => $status ) {
			if ( 'fail' !== $status || ! isset( self::CHECKS[ $check_id ], $severities[ $check_id ] ) ) {
				continue;
			}
			$requirement            = self::CHECKS[ $check_id ][0];
			$scores[ $requirement ] = max( 0, $scores[ $requirement ] - self::POINTS[ $severities[ $check_id ] ] );
		}
		return array_merge( array( 'total' => min( $scores ) ), $scores );
	}

	/**
	 * Returns the severity of a vulnerability record from its CVSS rating.
	 *
	 * As on the Security tab: a record without a rating counts as medium.
	 *
	 * @param array $record Feed record.
	 * @return string
	 */
	public static function rating_severity( array $record ) {
		switch ( $record['cvss']['rating'] ?? '' ) {
			case 'Critical':
				return 'critical';
			case 'High':
				return 'high';
			case 'Low':
			case 'None':
				return 'low';
			default:
				return 'medium';
		}
	}

	/**
	 * Returns the statuses the site reported for the checks the server knows.
	 *
	 * A check reported twice takes its worst status (fail, then unknown).
	 *
	 * @param array $local_findings Request's local_findings.
	 * @return array<string, string> Check ID => status.
	 */
	private static function reported_statuses( array $local_findings ) {
		$rank     = array(
			'pass'    => 0,
			'unknown' => 1,
			'fail'    => 2,
		);
		$reported = array();
		foreach ( $local_findings as $finding ) {
			$check_id = $finding['check_id'];
			if ( ! isset( self::CHECKS[ $check_id ] ) || in_array( $check_id, self::SERVER_CHECKS, true ) ) {
				continue;
			}
			if ( ! isset( $reported[ $check_id ] ) || $rank[ $finding['status'] ] > $rank[ $reported[ $check_id ] ] ) {
				$reported[ $check_id ] = $finding['status'];
			}
		}

		return $reported;
	}

	/**
	 * Puts statuses in the order of CHECKS.
	 *
	 * @param array<string, string> $statuses Check ID => status.
	 * @return array<string, string>
	 */
	private static function in_check_order( array $statuses ) {
		$ordered = array();
		foreach ( array_keys( self::CHECKS ) as $check_id ) {
			if ( isset( $statuses[ $check_id ] ) ) {
				$ordered[ $check_id ] = $statuses[ $check_id ];
			}
		}
		return $ordered;
	}

	/**
	 * Matches the site's software and WordPress version against the index.
	 *
	 * Every feed has records of WordPress core, so an index without them is
	 * missing or incomplete: reporting "nothing found" from it would tell a
	 * vulnerable site it is fine.
	 *
	 * @param array  $request Request.
	 * @param string $version Feed version of the index.
	 * @return array<int, array{type: string, slug: string, version: string, record: array}>
	 * @throws Artws_Sec_Feed_Exception When the index cannot be read or has no core records.
	 */
	private static function vulnerabilities( array $request, $version ) {
		$installed = array();
		foreach ( $request['software'] as $item ) {
			$key = Artws_Sec_Vuln_Index::lookup_key( $item['type'], $item['slug'] );
			// A plugin or theme without a Version header is not matched (contract README).
			if ( '' !== $item['version'] && ! isset( $installed[ $key ] ) ) {
				$installed[ $key ] = array(
					'type'    => $item['type'],
					'slug'    => $item['slug'],
					'version' => $item['version'],
				);
			}
		}
		$core_key               = Artws_Sec_Vuln_Index::lookup_key( 'core', Artws_Sec_Record_Validator::CORE_SLUG );
		$installed[ $core_key ] = array(
			'type'    => 'core',
			'slug'    => Artws_Sec_Record_Validator::CORE_SLUG,
			'version' => $request['wp_ver'],
		);

		$records_by_key = Artws_Sec_Vuln_Index::find( $version, array_keys( $installed ) );
		if ( ! isset( $records_by_key[ $core_key ] ) ) {
			throw new Artws_Sec_Feed_Exception( sprintf( 'The vulnerability index %s has no WordPress core records.', $version ) );
		}

		$found = array();
		foreach ( $records_by_key as $key => $records ) {
			foreach ( $records as $record ) {
				if ( is_array( $record ) && is_array( $record['affected_versions'] ?? null ) && Artws_Sec_Vuln_Index::is_affected( $installed[ $key ]['version'], $record['affected_versions'] ) ) {
					$found[] = $installed[ $key ] + array( 'record' => $record );
				}
			}
		}
		return $found;
	}

	/**
	 * Returns the PHP, WordPress and WooCommerce versions older than the oldest supported ones.
	 *
	 * @param array $request Request.
	 * @param array $eol     Name => oldest supported version.
	 * @return array<string, array{0: string, 1: string}> Name => running version, oldest supported version.
	 */
	private static function unsupported_versions( array $request, array $eol ) {
		$running     = array(
			'php'         => $request['php_ver'],
			'wordpress'   => $request['wp_ver'], // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Array key, not text.
			'woocommerce' => $request['wc_ver'],
		);
		$unsupported = array();
		foreach ( $running as $name => $version ) {
			if ( isset( $eol[ $name ] ) && is_string( $eol[ $name ] ) && '' !== $eol[ $name ] && version_compare( $version, $eol[ $name ], '<' ) ) {
				$unsupported[ $name ] = array( $version, $eol[ $name ] );
			}
		}
		return $unsupported;
	}

	/**
	 * Returns the severity of V-01: that of the most severe vulnerability found.
	 *
	 * As JP4WC_Security_Checks does: the highest CVSS rating among the
	 * records, where a record without one ranks below None, so that it counts
	 * as medium only when no record has a rating.
	 *
	 * @param array $records Feed records of the vulnerabilities found.
	 * @return string
	 */
	public static function vulnerability_severity( array $records ) {
		if ( array() === $records ) {
			return self::CHECKS['V-01'][1];
		}
		$highest = -1;
		foreach ( $records as $record ) {
			$rank    = array_search( $record['cvss']['rating'] ?? null, self::RATINGS, true );
			$highest = max( $highest, false === $rank ? -1 : $rank );
		}
		return self::rating_severity( -1 === $highest ? array() : array( 'cvss' => array( 'rating' => self::RATINGS[ $highest ] ) ) );
	}

	/**
	 * Builds the finding of one vulnerability record.
	 *
	 * @param array  $vulnerability Match from vulnerabilities().
	 * @param string $language      ja or en.
	 * @return array
	 */
	private static function vulnerability_finding( array $vulnerability, $language ) {
		$record   = $vulnerability['record'];
		$type     = $vulnerability['type'];
		$severity = self::rating_severity( $record );
		$args     = array( $vulnerability['slug'], $vulnerability['version'] );

		$detail = (string) $record['title'];
		if ( isset( $record['cvss']['score'], $record['cvss']['rating'] ) ) {
			$detail = Artws_Sec_Report_Templates::text( 'vuln_detail_cvss', $language, array( $detail, (string) $record['cvss']['score'], $record['cvss']['rating'] ) );
		}

		$fixed = self::fixed_version( $vulnerability['version'], $record );
		if ( null !== $fixed ) {
			$fix = array(
				Artws_Sec_Report_Templates::text( 'vuln_fix_' . $type, $language, array( $vulnerability['slug'], $fixed ) ),
				Artws_Sec_Report_Templates::text( 'vuln_after_update', $language ),
			);
		} else {
			$fix = array( Artws_Sec_Report_Templates::text( 'vuln_nofix_' . $type, $language, $args ) );
		}

		return array(
			'check_id'    => 'V-01',
			'severity'    => $severity,
			'requirement' => 'vuln',
			'title'       => Artws_Sec_Report_Templates::text( 'vuln_title_' . $type, $language, $args ),
			'detail'      => $detail,
			'impact'      => Artws_Sec_Report_Templates::vulnerability_impact( $severity, $language ),
			'fix'         => $fix,
			'reference'   => (string) $record['reference'],
			'software'    => array(
				'type'    => $type,
				'slug'    => $vulnerability['slug'],
				'version' => $vulnerability['version'],
			),
		);
	}

	/**
	 * Returns the lowest patched version above the installed one that the record does not affect.
	 *
	 * @param string $installed Installed version.
	 * @param array  $record    Feed record.
	 * @return string|null Null when there is none.
	 */
	private static function fixed_version( $installed, array $record ) {
		if ( empty( $record['patched'] ) || ! is_array( $record['patched_versions'] ?? null ) ) {
			return null;
		}
		$candidates = array();
		foreach ( $record['patched_versions'] as $version ) {
			if ( is_string( $version ) && version_compare( $version, $installed, '>' ) && ! Artws_Sec_Vuln_Index::is_affected( $version, $record['affected_versions'] ) ) {
				$candidates[] = $version;
			}
		}
		if ( array() === $candidates ) {
			return null;
		}
		usort( $candidates, 'version_compare' );
		return $candidates[0];
	}

	/**
	 * Builds the V-03 finding.
	 *
	 * @param array  $unsupported From unsupported_versions().
	 * @param string $language    ja or en.
	 * @return array
	 */
	private static function unsupported_finding( array $unsupported, $language ) {
		$items = array();
		$fix   = array();
		foreach ( $unsupported as $name => $versions ) {
			$items[] = Artws_Sec_Report_Templates::text( 'eol_item', $language, array( Artws_Sec_Report_Templates::EOL_NAMES[ $name ], $versions[0], $versions[1] ) );
			$fix[]   = Artws_Sec_Report_Templates::text( 'eol_fix_' . $name, $language );
		}

		return array(
			'check_id'    => 'V-03',
			'severity'    => self::CHECKS['V-03'][1],
			'requirement' => 'vuln',
			'title'       => Artws_Sec_Report_Templates::text( 'eol_title', $language ),
			'detail'      => Artws_Sec_Report_Templates::text( 'eol_detail', $language, array( implode( Artws_Sec_Report_Templates::text( 'eol_list_separator', $language ), $items ) ) ),
			'impact'      => Artws_Sec_Report_Templates::text( 'eol_impact', $language ),
			'fix'         => $fix,
		);
	}

	/**
	 * Orders findings: severity, then the check (requirements in score order),
	 * then the CVSS score of vulnerabilities, highest first.
	 *
	 * @param array{0: array, 1: float} $a Finding and CVSS score.
	 * @param array{0: array, 1: float} $b Finding and CVSS score.
	 * @return int
	 */
	private static function compare_findings( array $a, array $b ) {
		$check_ids = array_keys( self::CHECKS );
		$order     = array(
			array_search( $a[0]['severity'], self::SEVERITIES, true ) <=> array_search( $b[0]['severity'], self::SEVERITIES, true ),
			array_search( $a[0]['check_id'], $check_ids, true ) <=> array_search( $b[0]['check_id'], $check_ids, true ),
			$b[1] <=> $a[1],
			strcmp( $a[0]['title'], $b[0]['title'] ),
			strcmp( $a[0]['reference'] ?? '', $b[0]['reference'] ?? '' ),
		);
		foreach ( $order as $result ) {
			if ( 0 !== $result ) {
				return $result;
			}
		}
		return 0;
	}

	/**
	 * Builds the next steps.
	 *
	 * @param array  $request  Request.
	 * @param array  $statuses Check ID => status.
	 * @param array  $findings Findings in order.
	 * @param array  $state    Feed state.
	 * @param string $language ja or en.
	 * @return string[]
	 */
	private static function next_steps( array $request, array $statuses, array $findings, array $state, $language ) {
		$steps = array();
		if ( array() === $findings ) {
			$steps[] = Artws_Sec_Report_Templates::text( 'step_none', $language );
		} else {
			$steps[] = Artws_Sec_Report_Templates::text( 'step_order', $language );

			$urgent = array_filter( $findings, static fn( $finding ) => in_array( $finding['severity'], array( 'critical', 'high' ), true ) );
			$large  = in_array( $request['revenue_band'] ?? '', self::LARGE_BANDS, true ) || in_array( $request['order_band'] ?? '', self::LARGE_BANDS, true );
			if ( $large && array() !== $urgent ) {
				$steps[] = Artws_Sec_Report_Templates::text( 'step_large_store', $language );
			}
		}

		$unknown = array_keys( array_filter( $statuses, static fn( $status ) => 'unknown' === $status ) );
		if ( array() !== $unknown ) {
			$steps[] = Artws_Sec_Report_Templates::text( 'step_unknown', $language, array( count( $unknown ), implode( ', ', $unknown ) ) );
		}
		if ( $state['stale'] ) {
			$steps[] = Artws_Sec_Report_Templates::text( 'step_stale', $language );
		}
		$steps[] = Artws_Sec_Report_Templates::text( 'step_rescan', $language );

		return $steps;
	}
}
