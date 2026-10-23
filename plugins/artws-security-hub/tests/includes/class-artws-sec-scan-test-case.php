<?php
/**
 * Shared helpers for the free security check tests.
 *
 * @package Artws_Security_Hub
 */

// phpcs:disable WordPress.WP.AlternativeFunctions -- Test helpers read local fixture files directly.

/**
 * Artws_Sec_Scan_Test_Case
 *
 * Empties the tables of the free security check around every test (the
 * deletes are rolled back with the test), publishes the vulnerability
 * samples of the contract, and sends REST requests as a client over HTTPS.
 */
abstract class Artws_Sec_Scan_Test_Case extends Artws_Sec_Feed_Test_Case {

	/**
	 * Feed version of the published samples.
	 */
	const FEED_VERSION = '20261020.031500';

	/**
	 * Address the requests come from.
	 */
	const CLIENT_IP = '192.0.2.10';

	/**
	 * $_SERVER values replaced by set_up().
	 *
	 * @var array<string, mixed>
	 */
	private $saved_server = array();

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;
		foreach ( array( Artws_Sec_Db::sites_table(), Artws_Sec_Db::scans_table(), Artws_Sec_Db::limits_table(), Artws_Sec_Db::vulns_table() ) as $table ) {
			$wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test cleanup of custom tables.
		}

		foreach ( array( 'HTTPS', 'REMOTE_ADDR' ) as $key ) {
			$this->saved_server[ $key ] = $_SERVER[ $key ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Saved as is, to be put back in tear_down().
		}
		$_SERVER['HTTPS']       = 'on';
		$_SERVER['REMOTE_ADDR'] = self::CLIENT_IP;
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		foreach ( $this->saved_server as $key => $value ) {
			if ( null === $value ) {
				unset( $_SERVER[ $key ] );
			} else {
				$_SERVER[ $key ] = $value;
			}
		}
		parent::tear_down();
	}

	/**
	 * Publishes the contract's vulnerability samples as the current feed.
	 *
	 * @param bool $stale Whether the feed is stale.
	 */
	protected static function publish_contract_feed( $stale = false ) {
		$vulnerabilities = array();
		foreach ( array(
			'plugin' => 'vuln-plugins.json',
			'theme'  => 'vuln-themes.json',
			'core'   => 'vuln-core.json',
		) as $type => $file ) {
			$data = json_decode( file_get_contents( ARTWS_SEC_CONTRACT_DIR . '/' . $file ), true );
			foreach ( $data['vulnerabilities'] as $slug => $records ) {
				$vulnerabilities[ $type ][ $slug ] = array_map( 'wp_json_encode', $records );
			}
		}
		Artws_Sec_Vuln_Index::write( $vulnerabilities, self::FEED_VERSION );

		update_option(
			Artws_Sec_Feed_Updater::STATE_OPTION,
			array(
				'version'      => self::FEED_VERSION,
				'generated_at' => '2026-10-20T03:15:00Z',
				'stale'        => $stale,
				'files'        => array(),
				'copyrights'   => array(),
				'attribution'  => Artws_Sec_Feed_Updater::ATTRIBUTION,
				'last_success' => time(),
				'indexed'      => true,
			),
			false
		);
	}

	/**
	 * Returns the contract's sample request.
	 *
	 * @param array $overrides Top-level keys to replace.
	 * @return array
	 */
	protected static function sample_request( array $overrides = array() ) {
		$request = json_decode( file_get_contents( ARTWS_SEC_CONTRACT_DIR . '/scan-request.json' ), true );
		return array_merge( $request, $overrides );
	}

	/**
	 * Sends a REST request to artws-sec/v1.
	 *
	 * @param string      $method HTTP method.
	 * @param string      $route  Route below the namespace, such as /scan.
	 * @param array|null  $body   Body to send as JSON.
	 * @param string|null $token  Site token for the Authorization header.
	 * @return WP_REST_Response
	 */
	protected static function send( $method, $route, $body = null, $token = null ) {
		$request = new WP_REST_Request( $method, '/' . Artws_Sec_Rest_Controller::REST_NAMESPACE . $route );
		if ( null !== $body ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}
		if ( null !== $token ) {
			$request->set_header( 'Authorization', 'Bearer ' . $token );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Returns a response's data as the client decodes it (objects), for the schema checks.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return mixed
	 */
	protected static function as_json( WP_REST_Response $response ) {
		return json_decode( wp_json_encode( $response->get_data() ) );
	}

	/**
	 * Counts the rows of a table.
	 *
	 * @param string $table Table name.
	 * @return int
	 */
	protected static function count_rows( $table ) {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test helper.
	}
}
