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
			'response'     => array( 'scan-response.json', 'scan-response' ),
			'report'       => array( 'report.json', 'report' ),
			'error'        => array( 'error-rate-limited.json', 'error' ),
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
	 * Requests the client may send besides the sample.
	 *
	 * @return array<string, array{callable}>
	 */
	public function valid_request_provider() {
		return array(
			'without the revenue and order bands'    => array(
				static function ( $d ) {
					unset( $d->revenue_band, $d->order_band );
				},
			),
			'plugin without a Version header'        => array(
				static function ( $d ) {
					$d->software[2]->version = '';
				},
			),
			'Version header with a space'            => array(
				static function ( $d ) {
					$d->software[2]->version = '1.0 beta';
				},
			),
			'PHP version with a distribution suffix' => array(
				static function ( $d ) {
					$d->php_ver = '8.3.6-1+ubuntu24.04.1+deb.sury.org+1';
				},
			),
			'locale with a variant'                  => array(
				static function ( $d ) {
					$d->locale = 'de_DE_formal';
				},
			),
		);
	}

	/**
	 * The request schema accepts what real sites report.
	 *
	 * @dataProvider valid_request_provider
	 *
	 * @param callable $mutate Changes the decoded sample in place.
	 */
	public function test_scan_request_variant_is_valid( $mutate ) {
		$request = $this->load_sample( 'scan-request.json' );
		$mutate( $request );

		$this->assertTrue( $this->validate( $request, 'scan-request' )->isValid() );
	}

	/**
	 * Every schema file.
	 *
	 * @return array<string, array{string}>
	 */
	public function schema_file_provider() {
		$cases = array();
		foreach ( glob( ARTWS_SEC_CONTRACT_DIR . '/schema/*.schema.json' ) as $file ) {
			$cases[ basename( $file ) ] = array( $file );
		}
		return $cases;
	}

	/**
	 * Every object a schema describes is closed, as the README promises:
	 * a schema with "properties" also has "additionalProperties": false,
	 * and any other object schema is either closed or a map whose values
	 * "additionalProperties" describes (vulnerabilities, copyrights).
	 * Conditional subschemas (if / then / else) only add constraints to
	 * an object defined elsewhere, so they are skipped.
	 *
	 * @dataProvider schema_file_provider
	 *
	 * @param string $file Absolute path of the schema file.
	 */
	public function test_every_object_schema_is_closed( $file ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture; WordPress is not loaded.
		$schema = json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
		$open   = array();
		$closed = 0;

		$walk = static function ( $node, $path ) use ( &$walk, &$open, &$closed ) {
			if ( ! is_array( $node ) ) {
				return;
			}
			$additional = array_key_exists( 'additionalProperties', $node ) ? $node['additionalProperties'] : null;
			if ( isset( $node['properties'] ) && is_array( $node['properties'] ) ) {
				if ( false === $additional ) {
					++$closed;
				} else {
					$open[] = $path;
				}
			} elseif ( isset( $node['type'] ) && 'object' === $node['type'] ) {
				if ( false === $additional || is_array( $additional ) ) {
					++$closed;
				} else {
					$open[] = $path;
				}
			}
			foreach ( $node as $key => $child ) {
				if ( in_array( $key, array( 'if', 'then', 'else' ), true ) ) {
					continue;
				}
				$walk( $child, $path . '/' . $key );
			}
		};
		$walk( $schema, '' );

		$this->assertSame( array(), $open, 'Objects without "additionalProperties": false' );
		$this->assertGreaterThan( 0, $closed );
	}

	/**
	 * Changes to a sample that its schema must reject.
	 *
	 * @return array<string, array{string, string, callable}>
	 */
	public function invalid_provider() {
		return array(
			// Manifest.
			'manifest: newer schema_version'               => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->schema_version = 2;
				},
			),
			'manifest: missing stale'                      => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					unset( $d->stale );
				},
			),
			'manifest: unknown top-level key'              => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->site_count = 10;
				},
			),
			'manifest: missing core file'                  => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					unset( $d->files->core );
				},
			),
			'manifest: upper-case sha256'                  => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->files->plugin->sha256 = strtoupper( $d->files->plugin->sha256 );
				},
			),
			'manifest: file with a directory'              => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->files->plugin->file = '../vuln-plugins.json.gz';
				},
			),
			'manifest: file that is not .json.gz'          => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->files->theme->file = 'vuln-themes.json';
				},
			),
			'manifest: generated_at not in UTC'            => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->generated_at = '2026-10-20T12:00:00+09:00';
				},
			),
			'manifest: no copyright holder'                => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->copyrights = new stdClass();
				},
			),
			'manifest: license_url over http'              => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->copyrights->defiant->license_url = 'http://www.wordfence.com/';
				},
			),
			'manifest: eol version with a suffix'          => array(
				'manifest.json',
				'manifest',
				static function ( $d ) {
					$d->eol->php->min_supported = '8.2-dev';
				},
			),
			// Vulnerability files.
			'feed: core file with a plugin slug'           => array(
				'vuln-core.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'} = $d->vulnerabilities->wordpress;
				},
			),
			'feed: slug with a slash'                      => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample/plugin'} = $d->vulnerabilities->{'sample-plugin'};
				},
			),
			'feed: slug with no records'                   => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'} = array();
				},
			),
			'feed: record without reference'               => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					unset( $d->vulnerabilities->{'sample-plugin'}[0]->reference );
				},
			),
			'feed: record with an extra key'               => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->description = 'Not part of the reduced record.';
				},
			),
			'feed: theme record in the plugin file'        => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->software->type = 'theme';
				},
			),
			'feed: plugin record in the theme file'        => array(
				'vuln-themes.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-theme'}[0]->software->type = 'plugin';
				},
			),
			'feed: plugin record in the core file'         => array(
				'vuln-core.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->wordpress[0]->software->type = 'plugin';
				},
			),
			'feed: id that is not a UUID'                  => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->id = 'wf-1';
				},
			),
			'feed: no affected range'                      => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->affected_versions = array();
				},
			),
			'feed: range without to_inclusive'             => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					unset( $d->vulnerabilities->{'sample-plugin'}[0]->affected_versions[0]->to_inclusive );
				},
			),
			'feed: unknown cvss rating'                    => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->cvss->rating = 'Severe';
				},
			),
			'feed: cvss score above 10'                    => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->cvss->score = 10.5;
				},
			),
			'feed: reference over http'                    => array(
				'vuln-plugins.json',
				'vuln-file',
				static function ( $d ) {
					$d->vulnerabilities->{'sample-plugin'}[0]->reference = 'http://www.wordfence.com/threat-intel/';
				},
			),
			// Free security check request.
			'request: administrator e-mail'                => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->admin_email = 'owner@example.jp';
				},
			),
			'request: extra key on a software item'        => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->path = '/var/www/html/wp-content/plugins/woocommerce';
				},
			),
			'request: core in the software list'           => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->type = 'core';
				},
			),
			'request: setting value in a finding'          => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings[0]->value = 'DISALLOW_FILE_EDIT=false';
				},
			),
			'request: unknown finding status'              => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings[0]->status = 'ok';
				},
			),
			'request: unknown check ID'                    => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings[0]->check_id = 'X-01';
				},
			),
			'request: revenue amount, not a band'          => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->revenue_band = '12000000';
				},
			),
			'request: out-of-range order band'             => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->order_band = 'O5';
				},
			),
			'request: missing consent_version'             => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					unset( $d->consent_version );
				},
			),
			'request: consent_version month 13'            => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->consent_version = '2026-13';
				},
			),
			'request: site_url that is not http(s)'        => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->site_url = 'javascript:alert(1)';
				},
			),
			'request: control character in a version'      => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->version = "10.6.2\n";
				},
			),
			'request: Version header over 64 characters'   => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->version = str_repeat( '1', 65 );
				},
			),
			'request: empty WordPress version'             => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->wp_ver = '';
				},
			),
			'request: locale with a path'                  => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->locale = 'ja/../../etc';
				},
			),
			'request: too many findings'                   => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->local_findings = array_fill( 0, 101, $d->local_findings[0] );
				},
			),
			'request: revenue band without the order band' => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					unset( $d->order_band );
				},
			),
			'request: order band without the revenue band' => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					unset( $d->revenue_band );
				},
			),
			'request: C1 control character in a version'   => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software[0]->version = "10.6.2\u{0085}";
				},
			),
			'request: C1 control character in php_ver'     => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->php_ver = "8.3.12\u{009f}";
				},
			),
			'request: too many software items'             => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->software = array_fill( 0, 1001, $d->software[0] );
				},
			),
			'request: reregister (removed in 0.4.0)'       => array(
				'scan-request.json',
				'scan-request',
				static function ( $d ) {
					$d->reregister = true;
				},
			),
			// Scan response.
			'response: without the report'                 => array(
				'scan-response.json',
				'scan-response',
				static function ( $d ) {
					unset( $d->report );
				},
			),
			'response: site token without the prefix'      => array(
				'scan-response.json',
				'scan-response',
				static function ( $d ) {
					$d->site_token = substr( $d->site_token, 4 );
				},
			),
			'response: short site token'                   => array(
				'scan-response.json',
				'scan-response',
				static function ( $d ) {
					$d->site_token = 'sit_' . str_repeat( 'a', 42 );
				},
			),
			'response: site token with a trailing newline' => array(
				'scan-response.json',
				'scan-response',
				static function ( $d ) {
					$d->site_token .= "\n";
				},
			),
			'response: invalid report'                     => array(
				'scan-response.json',
				'scan-response',
				static function ( $d ) {
					$d->report->score->total = 101;
				},
			),
			'response: unknown top-level key'              => array(
				'scan-response.json',
				'scan-response',
				static function ( $d ) {
					$d->scan_token = $d->report->token;
				},
			),
			// Error.
			'error: 429 without retry_at'                  => array(
				'error-rate-limited.json',
				'error',
				static function ( $d ) {
					unset( $d->data->retry_at );
				},
			),
			'error: retry_at on another status'            => array(
				'error-rate-limited.json',
				'error',
				static function ( $d ) {
					$d->data->status = 401;
				},
			),
			'error: code of another plugin'                => array(
				'error-rate-limited.json',
				'error',
				static function ( $d ) {
					$d->code = 'rest_forbidden';
				},
			),
			'error: success status'                        => array(
				'error-rate-limited.json',
				'error',
				static function ( $d ) {
					$d->data = (object) array( 'status' => 200 );
				},
			),
			// Report.
			'report: V-01 without the Wordfence link'      => array(
				'report.json',
				'report',
				static function ( $d ) {
					unset( $d->findings[0]->reference );
				},
			),
			'report: V-01 without the software'            => array(
				'report.json',
				'report',
				static function ( $d ) {
					unset( $d->findings[0]->software );
				},
			),
			'report: vulnerability check under admin'      => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[0]->requirement = 'admin';
				},
			),
			'report: admin check under vuln'               => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[1]->requirement = 'vuln';
				},
			),
			'report: data check under admin'               => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[1]->check_id = 'D-04';
				},
			),
			'report: malware check under data'             => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[2]->requirement = 'data';
				},
			),
			'report: unknown severity'                     => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[1]->severity = 'info';
				},
			),
			'report: fix as one string'                    => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[2]->fix = $d->findings[2]->fix[0];
				},
			),
			'report: HTML field on a finding'              => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->findings[2]->html = '<p>Fix it</p>';
				},
			),
			'report: score above 100'                      => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->score->data = 101;
				},
			),
			'report: negative score'                       => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->score->admin = -1;
				},
			),
			'report: token without the prefix'             => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->token = substr( $d->token, 4 );
				},
			),
			'report: short token'                          => array(
				'report.json',
				'report',
				static function ( $d ) {
					$d->token = 'scn_' . str_repeat( 'a', 31 );
				},
			),
			'report: token with a trailing newline'        => array(
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
