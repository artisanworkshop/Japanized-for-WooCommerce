<?php
/**
 * Checks the body of POST /scan against the JSON contract.
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Artws_Sec_Scan_Request
 *
 * A hand-written copy of tests/fixtures/security-feed/schema/scan-request.schema.json
 * (the plugin does not load Composer packages at run time; the tests check
 * that what this class returns, the schema accepts). Keys the schema does not
 * define are dropped rather than rejected, at every level, as the contract
 * asks of readers: whatever else a client sends is never stored.
 */
class Artws_Sec_Scan_Request {

	/**
	 * Consent texts the server knows (consent_version).
	 *
	 * 2026-10: the terms of use in force from 2026-10-28 (sent by JP4WC 3.0.0).
	 * 2026-11: the revision of 2026-10-29, in force from 2026-11-05, which adds
	 * the staff's check of each registration (article 4.4.4) to the purposes.
	 */
	const CONSENT_VERSIONS = array( '2026-10', '2026-11' );

	/**
	 * Revenue band codes.
	 */
	const REVENUE_BANDS = array( 'B0', 'B1', 'B2', 'B3', 'B4', 'B5' );

	/**
	 * Order band codes.
	 */
	const ORDER_BANDS = array( 'O0', 'O1', 'O2', 'O3', 'O4' );

	/**
	 * Software types a site may list.
	 */
	const SOFTWARE_TYPES = array( 'plugin', 'theme' );

	/**
	 * Statuses of a local check.
	 */
	const STATUSES = array( 'pass', 'fail', 'unknown' );

	/**
	 * Most software items.
	 */
	const MAX_SOFTWARE = 1000;

	/**
	 * Most local findings.
	 */
	const MAX_FINDINGS = 100;

	/**
	 * Characters a version may not contain (C0 and C1 controls, DEL).
	 */
	const CONTROL_CHARACTERS = '\x{0000}-\x{001F}\x{007F}-\x{009F}';

	/**
	 * Returns the request with only the contract's keys, or why it is invalid.
	 *
	 * @param mixed $data Body decoded with json_decode( …, true ).
	 * @return array|WP_Error The request, or a WP_Error with status 400.
	 */
	public static function normalize( $data ) {
		if ( ! is_array( $data ) || ( array() !== $data && array_is_list( $data ) ) ) {
			return self::invalid( 'body' );
		}

		if ( ! isset( $data['schema_version'] ) || 1 !== $data['schema_version'] ) {
			return self::invalid( 'schema_version' );
		}
		if ( ! self::is_site_url( $data['site_url'] ?? null ) ) {
			return self::invalid( 'site_url' );
		}
		foreach ( array( 'wp_ver', 'wc_ver', 'php_ver', 'jp4wc_ver' ) as $key ) {
			if ( ! self::is_version( $data[ $key ] ?? null, 1 ) ) {
				return self::invalid( $key );
			}
		}
		$locale = $data['locale'] ?? null;
		if ( ! Artws_Sec_Record_Validator::is_text( $locale, 1, 20 ) || ! preg_match( '/^[A-Za-z]{2,3}(_[A-Za-z0-9]+)*$/D', $locale ) ) {
			return self::invalid( 'locale' );
		}

		$software = self::software( $data['software'] ?? null );
		if ( is_wp_error( $software ) ) {
			return $software;
		}
		$findings = self::local_findings( $data['local_findings'] ?? null );
		if ( is_wp_error( $findings ) ) {
			return $findings;
		}

		$has_revenue = array_key_exists( 'revenue_band', $data );
		$has_order   = array_key_exists( 'order_band', $data );
		if ( $has_revenue && ! in_array( $data['revenue_band'], self::REVENUE_BANDS, true ) ) {
			return self::invalid( 'revenue_band' );
		}
		if ( $has_order && ! in_array( $data['order_band'], self::ORDER_BANDS, true ) ) {
			return self::invalid( 'order_band' );
		}
		if ( $has_revenue !== $has_order ) {
			// One consent covers both bands, so they come together or not at all.
			return self::invalid( $has_revenue ? 'order_band' : 'revenue_band' );
		}

		$consent = $data['consent_version'] ?? null;
		if ( ! is_string( $consent ) || ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])$/D', $consent ) ) {
			return self::invalid( 'consent_version' );
		}
		if ( ! in_array( $consent, self::consent_versions(), true ) ) {
			return new WP_Error( 'artws_sec_unknown_consent_version', sprintf( 'The consent text %s is not known to the server.', $consent ), array( 'status' => 400 ) );
		}

		$request = array(
			'schema_version' => 1,
			'site_url'       => $data['site_url'],
			'wp_ver'         => $data['wp_ver'],
			'wc_ver'         => $data['wc_ver'],
			'php_ver'        => $data['php_ver'],
			'jp4wc_ver'      => $data['jp4wc_ver'],
			'locale'         => $locale,
			'software'       => $software,
			'local_findings' => $findings,
		);
		if ( $has_revenue ) {
			$request['revenue_band'] = $data['revenue_band'];
			$request['order_band']   = $data['order_band'];
		}
		$request['consent_version'] = $consent;

		return $request;
	}

	/**
	 * Returns the consent texts the server accepts.
	 *
	 * @return string[]
	 */
	public static function consent_versions() {
		/**
		 * Filters the consent texts (YYYY-MM) the server accepts.
		 *
		 * @param string[] $versions Default Artws_Sec_Scan_Request::CONSENT_VERSIONS.
		 */
		$versions = apply_filters( 'artws_sec_consent_versions', self::CONSENT_VERSIONS );
		return is_array( $versions ) ? array_values( array_filter( $versions, 'is_string' ) ) : array();
	}

	/**
	 * Checks and copies the software list.
	 *
	 * @param mixed $items Decoded "software".
	 * @return array|WP_Error
	 */
	private static function software( $items ) {
		if ( ! is_array( $items ) || ! array_is_list( $items ) || count( $items ) > self::MAX_SOFTWARE ) {
			return self::invalid( 'software' );
		}
		$software = array();
		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item )
				|| ! in_array( $item['type'] ?? null, self::SOFTWARE_TYPES, true )
				|| ! Artws_Sec_Record_Validator::is_slug( $item['slug'] ?? null )
				|| ! self::is_version( $item['version'] ?? null, 0 )
				|| ! is_bool( $item['active'] ?? null )
			) {
				return self::invalid( "software[$index]" );
			}
			$software[] = array(
				'type'    => $item['type'],
				'slug'    => $item['slug'],
				'version' => $item['version'],
				'active'  => $item['active'],
			);
		}
		return $software;
	}

	/**
	 * Checks and copies the local findings.
	 *
	 * @param mixed $items Decoded "local_findings".
	 * @return array|WP_Error
	 */
	private static function local_findings( $items ) {
		if ( ! is_array( $items ) || ! array_is_list( $items ) || count( $items ) > self::MAX_FINDINGS ) {
			return self::invalid( 'local_findings' );
		}
		$findings = array();
		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item )
				|| ! is_string( $item['check_id'] ?? null )
				|| ! preg_match( '/^[VADM]-[0-9]{2}$/D', $item['check_id'] )
				|| ! in_array( $item['status'] ?? null, self::STATUSES, true )
			) {
				return self::invalid( "local_findings[$index]" );
			}
			$findings[] = array(
				'check_id' => $item['check_id'],
				'status'   => $item['status'],
			);
		}
		return $findings;
	}

	/**
	 * Whether a value is an http(s) URL the contract allows for site_url.
	 *
	 * @param mixed $url Value to check.
	 * @return bool
	 */
	private static function is_site_url( $url ) {
		$space = Artws_Sec_Record_Validator::WHITESPACE;
		return Artws_Sec_Record_Validator::is_text( $url, 1, 2048 ) && 1 === preg_match( '/^https?:\/\/[^' . $space . '\/?#]+[^' . $space . ']*$/uD', $url );
	}

	/**
	 * Whether a value is a version as the request carries it.
	 *
	 * @param mixed $version Value to check.
	 * @param int   $min     Smallest length: 1 for wp_ver and the like, 0 for a Version header.
	 * @return bool
	 */
	private static function is_version( $version, $min ) {
		return Artws_Sec_Record_Validator::is_text( $version, $min, 64 ) && 1 === preg_match( '/^[^' . self::CONTROL_CHARACTERS . ']*$/uD', $version );
	}

	/**
	 * Returns the error for an invalid field.
	 *
	 * @param string $field Field name.
	 * @return WP_Error
	 */
	private static function invalid( $field ) {
		return new WP_Error( 'artws_sec_invalid_request', sprintf( 'The request has an invalid %s.', $field ), array( 'status' => 400 ) );
	}
}
