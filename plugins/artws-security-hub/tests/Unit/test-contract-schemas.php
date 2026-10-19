<?php
/**
 * Tests for the JSON contract shared with Japanized for WooCommerce.
 *
 * The feed, the free security check request and the report are defined by the
 * JSON Schemas in tests/fixtures/security-feed/schema/ at the repository root.
 * Each sample there must match its schema, and each schema must reject the
 * mistakes it exists to catch: otherwise a schema that accepts everything
 * would still let every sample pass.
 *
 * @package Artws_Security_Hub
 */

use Opis\JsonSchema\CompliantValidator;
use Opis\JsonSchema\Errors\ErrorFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Artws_Sec_Contract_Schemas_Test
 */
class Artws_Sec_Contract_Schemas_Test extends TestCase {

	/**
	 * Validates a decoded JSON value against one of the contract schemas.
	 *
	 * @param mixed  $data   Value decoded with json_decode() (objects, not arrays).
	 * @param string $schema Schema name without the .schema.json suffix.
	 * @return \Opis\JsonSchema\ValidationResult
	 */
	private function validate( $data, $schema ) {
		// CompliantValidator keeps to standard JSON Schema, so the schemas stay usable with other validators.
		$validator = new CompliantValidator();
		$validator->resolver()->registerPrefix( ARTWS_SEC_SCHEMA_BASE_URI, ARTWS_SEC_CONTRACT_DIR . '/schema/' );

		return $validator->validate( $data, ARTWS_SEC_SCHEMA_BASE_URI . $schema . '.schema.json' );
	}

	/**
	 * Reads a sample file from the contract directory.
	 *
	 * @param string $sample File name in tests/fixtures/security-feed/.
	 * @return mixed
	 */
	private function load_sample( $sample ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture; WordPress is not loaded.
		return json_decode( file_get_contents( ARTWS_SEC_CONTRACT_DIR . '/' . $sample ), false, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * Each sample and the schema it follows.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function sample_provider() {
		return array(
			'manifest'     => array( 'manifest.json', 'manifest' ),
			'plugin feed'  => array( 'vuln-plugins.json', 'vuln-file' ),
			'theme feed'   => array( 'vuln-themes.json', 'vuln-file' ),
			'core feed'    => array( 'vuln-core.json', 'vuln-file' ),
			'scan request' => array( 'scan-request.json', 'scan-request' ),
			'report'       => array( 'report.json', 'report' ),
		);
	}

	/**
	 * Every sample matches its schema.
	 *
	 * @dataProvider sample_provider
	 *
	 * @param string $sample Sample file name.
	 * @param string $schema Schema name.
	 */
	public function test_sample_matches_schema( $sample, $schema ) {
		$result = $this->validate( $this->load_sample( $sample ), $schema );

		$this->assertTrue(
			$result->isValid(),
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- WordPress is not loaded.
			$result->isValid() ? '' : json_encode( ( new ErrorFormatter() )->format( $result->error() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * The optional revenue and order bands may be left out of a request.
	 */
	public function test_scan_request_without_bands_is_valid() {
		$request = $this->load_sample( 'scan-request.json' );
		unset( $request->revenue_band, $request->order_band );

		$this->assertTrue( $this->validate( $request, 'scan-request' )->isValid() );
	}

	/**
	 * Changes to a sample that its schema must reject.
	 *
	 * @return array<string, array{string, string, callable}>
	 */
	public function invalid_provider() {
		return array(
			// Manifest.
			'manifest: newer schema_version'          => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->schema_version = 2;
				},
			),
			'manifest: missing stale'                 => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					unset( $d->stale );
				},
			),
			'manifest: unknown top-level key'         => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->site_count = 10;
				},
			),
			'manifest: missing core file'             => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					unset( $d->files->core );
				},
			),
			'manifest: upper-case sha256'             => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->files->plugin->sha256 = strtoupper( $d->files->plugin->sha256 );
				},
			),
			'manifest: file with a directory'         => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->files->plugin->file = '../vuln-plugins.json.gz';
				},
			),
			'manifest: file that is not .json.gz'     => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->files->theme->file = 'vuln-themes.json';
				},
			),
			'manifest: generated_at not in UTC'       => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->generated_at = '2026-10-20T12:00:00+09:00';
				},
			),
			'manifest: no copyright holder'           => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->copyrights = new stdClass();
				},
			),
			'manifest: license_url over http'         => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->copyrights->defiant->license_url = 'http://www.wordfence.com/';
				},
			),
			'manifest: eol version with a suffix'     => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->eol->php->min_supported = '8.2-dev';
				},
			),
			// Vulnerability files.
			'feed: core file with a plugin slug'      => array(
				'vuln-core.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'} = $d->vulnerabilities->wordpress;
				},
			),
			'feed: slug with a slash'                 => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample/plugin'} = $d->vulnerabilities->{'sample-plugin'};
				},
			),
			'feed: slug with no records'              => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'} = array();
				},
			),
			'feed: record without reference'          => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					unset( $d->vulnerabilities->{'sample-plugin'}[0]->reference );
				},
			),
			'feed: record with an extra key'          => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->description = 'Not part of the reduced record.';
				},
			),
			'feed: id that is not a UUID'             => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->id = 'wf-1';
				},
			),
			'feed: no affected range'                 => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->affected_versions = array();
				},
			),
			'feed: range without to_inclusive'        => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					unset( $d->vulnerabilities->{'sample-plugin'}[0]->affected_versions[0]->to_inclusive );
				},
			),
			'feed: unknown cvss rating'               => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->cvss->rating = 'Severe';
				},
			),
			'feed: cvss score above 10'               => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->cvss->score = 10.5;
				},
			),
			'feed: reference over http'               => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->reference = 'http://www.wordfence.com/threat-intel/';
				},
			),
			// Free security check request.
			'request: administrator e-mail'           => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->admin_email = 'owner@example.jp';
				},
			),
			'request: extra key on a software item'   => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->path = '/var/www/html/wp-content/plugins/woocommerce';
				},
			),
			'request: core in the software list'      => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->type = 'core';
				},
			),
			'request: setting value in a finding'     => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings[0]->value = 'DISALLOW_FILE_EDIT=false';
				},
			),
			'request: unknown finding status'         => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings[0]->status = 'ok';
				},
			),
			'request: unknown check ID'               => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings[0]->check_id = 'X-01';
				},
			),
			'request: revenue amount, not a band'     => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->revenue_band = '12000000';
				},
			),
			'request: out-of-range order band'        => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->order_band = 'O5';
				},
			),
			'request: missing consent_version'        => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					unset( $d->consent_version );
				},
			),
			'request: consent_version month 13'       => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->consent_version = '2026-13';
				},
			),
			'request: site_url that is not http(s)'   => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->site_url = 'javascript:alert(1)';
				},
			),
			'request: too many software items'        => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software = array_fill( 0, 1001, $d->software[0] );
				},
			),
			// Report.
			'report: V-01 without the Wordfence link' => array(
				'report.json',
				'report',
				static function ( $d ) {
					unset( $d->findings[0]->reference );
				},
			),
			'report: V-01 without the software'       => array(
				'report.json',
				'report',
				static function ( $d ) {
					unset( $d->findings[0]->software );
				},
			),
			'report: admin check under vuln'          => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[1]->requirement = 'vuln';
				},
			),
			'report: unknown severity'                => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[1]->severity = 'info';
				},
			),
			'report: fix as one string'               => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[2]->fix = $d->findings[2]->fix[0];
				},
			),
			'report: HTML field on a finding'         => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[2]->html = '<p>Fix it</p>';
				},
			),
			'report: score above 100'                 => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->score->data = 101;
				},
			),
			'report: token without the prefix'        => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->token = substr( $d->token, 4 );
				},
			),
			'report: short token'                     => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->token = 'scn_' . str_repeat( 'a', 31 );
				},
			),
			'report: token with a trailing newline'   => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->token .= "\n";
				},
			),
		);
	}

	/**
	 * Each schema rejects the changes it is meant to catch.
	 *
	 * @dataProvider invalid_provider
	 *
	 * @param string   $sample Sample file name.
	 * @param string   $schema Schema name.
	 * @param callable $mutate Changes the decoded sample in place.
	 */
	public function test_schema_rejects( $sample, $schema, $mutate ) {
		$data = $this->load_sample( $sample );
		$mutate( $data );

		$this->assertFalse( $this->validate( $data, $schema )->isValid() );
	}
}
