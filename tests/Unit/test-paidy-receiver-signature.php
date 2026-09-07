<?php
/**
 * Tests for the signed-callback fallback on the Paidy onboarding receiver.
 *
 * The intermediary (paidy-app) signs `<timestamp>.<raw body>` with the site
 * hash shared at application time. The receiver accepts such a callback even
 * when the one-time state token has expired (2.9.13–2.9.14 kept it in a 2-day
 * transient), was never issued (pre-2.9.13 applications), or was lost.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Receiver_Signature_Test
 */
class WC_Paidy_Receiver_Signature_Test extends WP_UnitTestCase {

	/**
	 * Shared secret used by every test.
	 *
	 * @var string
	 */
	const SITE_HASH = 'aB3$dE6&gH9(kL2-';

	/**
	 * A valid 32-char alphanumeric state token.
	 *
	 * @var string
	 */
	const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEF';

	/**
	 * Raw JSON body used by every test.
	 *
	 * @var string
	 */
	const BODY = '{"application_id":"WC000000571","paidy_status":"approved","updated_at":"2026-08-28 10:00:00"}';

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Paidy_Apply_Receiver' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-apply-receiver.php';
		}
		$this->assertTrue( class_exists( 'WC_Paidy_Apply_Receiver' ) );

		update_option( 'paidy_site_hash', self::SITE_HASH );
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		delete_option( 'paidy_site_hash' );
		delete_option( 'paidy_received_data' );
		delete_option( 'woocommerce_paidy_settings' );
		delete_option( 'woocommerce_paidy_on_boarding_settings' );
		delete_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN );
		delete_transient( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN );
		delete_transient( WC_Paidy_Apply_Receiver::SIGNATURE_WARNING_THROTTLE );

		delete_option( 'paidy_application_id' );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%" . WC_Paidy_Apply_Receiver::SIGNATURE_USED_PREFIX . "%'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%" . WC_Paidy_Apply_Receiver::EVENT_CLAIM_PREFIX . "%'" );
		wp_cache_flush();

		parent::tearDown();
	}

	/**
	 * Compute a signature the way the intermediary does.
	 *
	 * @param string $timestamp UNIX timestamp as string.
	 * @param string $body      Raw body.
	 * @param string $secret    Site hash.
	 * @return string
	 */
	private function sign( $timestamp, $body = self::BODY, $secret = self::SITE_HASH ) {
		return hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );
	}

	/**
	 * Build a receiver request as paidy-app would send it.
	 *
	 * @param array $headers Extra headers.
	 * @param array $params  Extra params merged into the JSON body.
	 * @return WP_REST_Request
	 */
	private function build_request( array $headers = array(), array $params = array() ) {
		$body    = array_merge( json_decode( self::BODY, true ), $params );
		$json    = wp_json_encode( $body );
		$request = new WP_REST_Request( 'POST', '/paidy-receiver/v1/receive' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( $json );
		foreach ( $headers as $name => $value ) {
			$request->set_header( $name, $value );
		}
		return $request;
	}

	/**
	 * A correctly signed, fresh request verifies.
	 */
	public function test_valid_signature_verifies() {
		$ts = (string) time();
		$this->assertTrue(
			WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $this->sign( $ts ), self::BODY, self::SITE_HASH )
		);
	}

	/**
	 * A signature made with a different secret fails.
	 */
	public function test_wrong_secret_fails() {
		$ts = (string) time();
		$this->assertFalse(
			WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $this->sign( $ts, self::BODY, 'other-secret' ), self::BODY, self::SITE_HASH )
		);
	}

	/**
	 * Modifying the body after signing fails.
	 */
	public function test_tampered_body_fails() {
		$ts       = (string) time();
		$tampered = str_replace( 'approved', 'rejected', self::BODY );
		$this->assertFalse(
			WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $this->sign( $ts ), $tampered, self::SITE_HASH )
		);
	}

	/**
	 * A timestamp outside the tolerance window fails, even with a valid HMAC.
	 */
	public function test_stale_timestamp_fails() {
		$ts = (string) ( time() - WC_Paidy_Apply_Receiver::get_signature_tolerance() - 1 );
		$this->assertFalse(
			WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $this->sign( $ts ), self::BODY, self::SITE_HASH )
		);

		$ts = (string) ( time() + WC_Paidy_Apply_Receiver::get_signature_tolerance() + 1 );
		$this->assertFalse(
			WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $this->sign( $ts ), self::BODY, self::SITE_HASH )
		);
	}

	/**
	 * The tolerance is filterable via wc4jp_paidy_receiver_signature_tolerance.
	 */
	public function test_tolerance_is_filterable() {
		$this->assertSame( 10 * MINUTE_IN_SECONDS, WC_Paidy_Apply_Receiver::get_signature_tolerance() );

		$widen = function () {
			return HOUR_IN_SECONDS;
		};
		add_filter( 'wc4jp_paidy_receiver_signature_tolerance', $widen );
		$ts = (string) ( time() - 30 * MINUTE_IN_SECONDS );
		$this->assertTrue(
			WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $this->sign( $ts ), self::BODY, self::SITE_HASH )
		);
		remove_filter( 'wc4jp_paidy_receiver_signature_tolerance', $widen );
	}

	/**
	 * Malformed header values are rejected without computing an HMAC.
	 */
	public function test_malformed_inputs_rejected() {
		$ts  = (string) time();
		$sig = $this->sign( $ts );

		$bad_signatures = array( '', 'short', strtoupper( $sig ), $sig . 'a', array( $sig ), null, 123 );
		foreach ( $bad_signatures as $bad ) {
			$this->assertFalse( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $bad, self::BODY, self::SITE_HASH ) );
		}

		$bad_timestamps = array( '', 'now', '-1', '1.5', str_repeat( '9', 13 ), array( $ts ), null );
		foreach ( $bad_timestamps as $bad ) {
			$this->assertFalse( WC_Paidy_Apply_Receiver::verify_request_signature( $bad, $sig, self::BODY, self::SITE_HASH ) );
		}

		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $sig, self::BODY, '' ) );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $sig, null, self::SITE_HASH ) );
	}

	/**
	 * A claimed signature cannot be replayed; the claim is exclusive.
	 */
	public function test_claim_is_exclusive_and_blocks_replay() {
		$ts  = (string) time();
		$sig = $this->sign( $ts );

		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $sig, self::BODY, self::SITE_HASH ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_signature( $sig ) );

		// A concurrent delivery of the same request loses the claim race.
		$this->assertFalse( WC_Paidy_Apply_Receiver::claim_signature( $sig ) );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $sig, self::BODY, self::SITE_HASH ) );

		// A fresh signature (new timestamp) is still accepted.
		$ts2 = (string) ( time() + 1 );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_request_signature( $ts2, $this->sign( $ts2 ), self::BODY, self::SITE_HASH ) );
	}

	/**
	 * Releasing a claim after a processing failure allows a retry.
	 */
	public function test_release_allows_retry() {
		$ts  = (string) time();
		$sig = $this->sign( $ts );

		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_signature( $sig ) );
		WC_Paidy_Apply_Receiver::release_signature_claim( $sig );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $sig, self::BODY, self::SITE_HASH ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_signature( $sig ) );
	}

	/**
	 * Claims older than twice the tolerance are pruned on the next claim.
	 */
	public function test_stale_claims_are_pruned() {
		$old_key = WC_Paidy_Apply_Receiver::SIGNATURE_USED_PREFIX . str_repeat( 'a', 40 );
		update_option( $old_key, time() - ( 2 * WC_Paidy_Apply_Receiver::get_signature_tolerance() ) - 1, false );

		WC_Paidy_Apply_Receiver::claim_signature( $this->sign( (string) time() ) );

		$this->assertFalse( get_option( $old_key ) );
	}

	/**
	 * claim/release ignore malformed values without fatal.
	 */
	public function test_claim_and_release_ignore_malformed() {
		foreach ( array( null, 'not-hex', array( 'x' ), 123 ) as $bad ) {
			$this->assertFalse( WC_Paidy_Apply_Receiver::claim_signature( $bad ) );
			WC_Paidy_Apply_Receiver::release_signature_claim( $bad ); // Must not fatal.
		}
	}

	/**
	 * A processing failure releases the claim so the same signed request can be retried.
	 */
	public function test_failed_processing_releases_claim_for_retry() {
		$receiver = new WC_Paidy_Apply_Receiver();
		// "approved" without any key fields → paidy_missing_key after authorization.
		$request = $this->build_request();
		$ts      = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
		$result = $receiver->handle_receive_data( $request );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'paidy_missing_key', $result->get_error_code() );

		// The retry is authorized again because the claim was released.
		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * The "signature present but invalid" warning is throttled to one per window.
	 */
	public function test_invalid_signature_warning_is_throttled() {
		delete_transient( WC_Paidy_Apply_Receiver::SIGNATURE_WARNING_THROTTLE );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request();
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body(), 'wrong' ) );

		$this->assertInstanceOf( 'WP_Error', $receiver->check_permissions( $request ) );
		$this->assertNotFalse( get_transient( WC_Paidy_Apply_Receiver::SIGNATURE_WARNING_THROTTLE ) );
	}

	/**
	 * check_permissions() accepts a signed callback when no state token exists
	 * (the WC000000571 / WC000000531 scenario from issue #210).
	 */
	public function test_permission_accepts_signed_request_without_state() {
		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request();
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * check_permissions() accepts a signed callback carrying an expired state token.
	 */
	public function test_permission_accepts_signed_request_with_expired_state() {
		update_option(
			WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN,
			time() - ( 91 * DAY_IN_SECONDS ),
			false
		);

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request( array(), array( 'state' => self::TOKEN ) );
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * check_permissions() still rejects an unsigned request without a state token.
	 */
	public function test_permission_rejects_unsigned_request_without_state() {
		$receiver = new WC_Paidy_Apply_Receiver();
		$result   = $receiver->check_permissions( $this->build_request() );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'paidy_invalid_state', $result->get_error_code() );
	}

	/**
	 * check_permissions() rejects a badly signed request without a state token.
	 */
	public function test_permission_rejects_bad_signature_without_state() {
		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request();
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body(), 'wrong' ) );

		$result = $receiver->check_permissions( $request );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'paidy_invalid_state', $result->get_error_code() );
	}

	/**
	 * Encrypt a key the way paidy-app does (AES-256-CBC keyed by the site hash).
	 *
	 * @param string $plain Plain key.
	 * @return string Base64 ciphertext.
	 */
	private function encrypt_key( $plain ) {
		$aes_key = substr( hash( 'sha256', self::SITE_HASH ), 0, 32 );
		$aes_iv  = substr( hash( 'sha256', self::SITE_HASH . 'iv' ), 0, 16 );
		return base64_encode( openssl_encrypt( $plain, 'AES-256-CBC', $aes_key, OPENSSL_RAW_DATA, $aes_iv ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * End to end: a signed "approved" callback with no state token is
	 * processed once (keys stored, step advanced) and the same request is
	 * rejected as a replay afterwards.
	 */
	public function test_signed_approved_callback_processed_once_end_to_end() {
		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request(
			array(),
			array(
				'public_live_key' => $this->encrypt_key( 'pk_live_xxx' ),
				'secret_live_key' => $this->encrypt_key( 'sk_live_xxx' ),
				'public_test_key' => $this->encrypt_key( 'pk_test_xxx' ),
				'secret_test_key' => $this->encrypt_key( 'sk_test_xxx' ),
			)
		);
		$ts = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );

		$response = $receiver->handle_receive_data( $request );
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertSame( 200, $response->get_status() );

		$paidy_settings = get_option( 'woocommerce_paidy_settings' );
		$this->assertSame( 'pk_live_xxx', $paidy_settings['api_public_key'] );
		$this->assertSame( 'sk_live_xxx', $paidy_settings['api_secret_key'] );
		$this->assertSame( 'pk_test_xxx', $paidy_settings['test_api_public_key'] );
		$this->assertSame( 'sk_test_xxx', $paidy_settings['test_api_secret_key'] );
		$this->assertSame( 3, get_option( 'woocommerce_paidy_on_boarding_settings' )['currentStep'] );

		// Decrypted keys must not leak into the response body.
		$this->assertArrayNotHasKey( 'secret_live_key', $response->get_data()['received_data'] );

		// The decrypted secret keys must not be duplicated in plaintext in
		// the diagnostic paidy_received_data option either — they are
		// already stored (and actually used) in woocommerce_paidy_settings
		// above (PR review finding, second round).
		$received_data = get_option( 'paidy_received_data' );
		$this->assertSame( '[redacted]', $received_data['secret_live_key'] );
		$this->assertSame( '[redacted]', $received_data['secret_test_key'] );
		// Public keys are not sensitive and retain their diagnostic value.
		$this->assertSame( 'pk_live_xxx', $received_data['public_live_key'] );

		// Replaying the identical signed request is now rejected.
		$replay = $receiver->check_permissions( $request );
		$this->assertInstanceOf( 'WP_Error', $replay );
		$this->assertSame( 'paidy_invalid_state', $replay->get_error_code() );
	}

	/**
	 * A valid state token still authorizes without any signature (old paidy-app).
	 */
	public function test_permission_accepts_valid_state_without_signature() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request( array(), array( 'state' => self::TOKEN ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * check_permissions() also claims a valid signature that accompanies an
	 * otherwise state-authorized request, so the signature cannot separately
	 * re-authorize the identical request later.
	 */
	public function test_permission_claims_signature_alongside_valid_state() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request( array(), array( 'state' => self::TOKEN ) );
		$ts       = (string) time();
		$sig      = $this->sign( $ts, $request->get_body() );
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $sig );

		$this->assertTrue( $receiver->check_permissions( $request ) );

		// The signature was claimed as a side effect, even though state
		// authorized the request — a signature-only retry of the identical
		// request is now rejected.
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_request_signature( $ts, $sig, $request->get_body(), self::SITE_HASH ) );
	}

	/**
	 * A malformed or non-matching signature alongside a valid state token
	 * does not block the state-authorized request (backward compatible with
	 * intermediaries that send an unrelated or missing signature).
	 */
	public function test_permission_ignores_bad_signature_alongside_valid_state() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request( array(), array( 'state' => self::TOKEN ) );
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body(), 'wrong-secret' ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * End to end: consuming the state token on success does not let an
	 * identical sequential retry re-authorize via the signature path, because
	 * the accompanying signature was already claimed on the first delivery
	 * (the scenario reported in PR #211 review).
	 */
	public function test_sequential_retry_after_state_success_is_rejected_via_signature() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request(
			array(),
			array(
				'state'           => self::TOKEN,
				'public_live_key' => $this->encrypt_key( 'pk_live_xxx' ),
				'secret_live_key' => $this->encrypt_key( 'sk_live_xxx' ),
				'public_test_key' => $this->encrypt_key( 'pk_test_xxx' ),
				'secret_test_key' => $this->encrypt_key( 'sk_test_xxx' ),
			)
		);
		$ts = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		// First delivery: authorized via state, processed successfully, state consumed.
		$this->assertTrue( $receiver->check_permissions( $request ) );
		$first = $receiver->handle_receive_data( $request );
		$this->assertInstanceOf( 'WP_REST_Response', $first );
		$this->assertSame( 200, $first->get_status() );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );

		// Sequential retry of the identical request: state is now consumed,
		// and the accompanying signature was already claimed on delivery 1.
		$retry = $receiver->check_permissions( $request );
		$this->assertInstanceOf( 'WP_Error', $retry );
		$this->assertSame( 'paidy_invalid_state', $retry->get_error_code() );
	}

	/**
	 * event_claim_key() is stable across different timestamps for the same
	 * application_id + paidy_status + key fields (the P1 finding on PR #211:
	 * the signature-derived claim varies with the timestamp, so a retry with
	 * a fresh timestamp produced a different claim and bypassed the guard).
	 */
	public function test_event_claim_is_stable_across_timestamps() {
		$request1 = $this->build_request();
		$request2 = $this->build_request(); // Identical params, would sign differently at a later time.

		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_event( $request1 ) );
		$this->assertFalse( WC_Paidy_Apply_Receiver::claim_event( $request2 ) );
	}

	/**
	 * A different paidy_status (or key fields) produces a different event
	 * claim, so a genuinely new decision for the same application is not
	 * blocked by an earlier one.
	 */
	public function test_event_claim_differs_for_a_different_decision() {
		$approved = $this->build_request( array(), array( 'paidy_status' => 'approved' ) );
		$rejected = $this->build_request( array(), array( 'paidy_status' => 'rejected' ) );

		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_event( $approved ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_event( $rejected ) );
	}

	/**
	 * Releasing an event claim allows it to be claimed again (retry after failure).
	 */
	public function test_event_claim_release_allows_retry() {
		$request = $this->build_request();

		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_event( $request ) );
		WC_Paidy_Apply_Receiver::release_event_claim( $request );
		$this->assertTrue( WC_Paidy_Apply_Receiver::claim_event( $request ) );
	}

	/**
	 * End to end via the signature-only path (no state token): the same
	 * decision (application_id + paidy_status + key fields) delivered twice
	 * with two different timestamps — and therefore two different, both
	 * individually valid, signatures — is authorized only once. This is the
	 * exact scenario reported in the PR #211 review.
	 */
	public function test_same_decision_with_different_timestamps_is_authorized_once() {
		$receiver = new WC_Paidy_Apply_Receiver();
		$params   = array(
			'public_live_key' => $this->encrypt_key( 'pk_live_xxx' ),
			'secret_live_key' => $this->encrypt_key( 'sk_live_xxx' ),
			'public_test_key' => $this->encrypt_key( 'pk_test_xxx' ),
			'secret_test_key' => $this->encrypt_key( 'sk_test_xxx' ),
		);

		$first = $this->build_request( array(), $params );
		$ts1   = (string) time();
		$first->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts1 );
		$first->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts1, $first->get_body() ) );
		$this->assertTrue( $receiver->check_permissions( $first ) );
		$this->assertSame( 200, $receiver->handle_receive_data( $first )->get_status() );

		// A second, independently-signed delivery of the identical decision —
		// a fresh timestamp produces a fresh, individually valid signature,
		// so the per-signature claim alone would not catch this.
		$second = $this->build_request( array(), $params );
		$ts2    = (string) ( time() + 1 );
		$second->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts2 );
		$sig2 = $this->sign( $ts2, $second->get_body() );
		$second->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $sig2 );

		// The signature itself is still individually valid and unclaimed.
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_request_signature( $ts2, $sig2, $second->get_body(), self::SITE_HASH ) );

		// But check_permissions() rejects it because the underlying event was
		// already claimed by the first delivery.
		$result = $receiver->check_permissions( $second );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'paidy_invalid_state', $result->get_error_code() );
	}

	/**
	 * A GET request (or any request with an empty raw body) is never
	 * authorized via the signature — a signature over an empty body proves
	 * nothing about query-string-only parameters (PR #211 review finding).
	 *
	 * Also confirms the request never enters the signature-verification
	 * branch at all: WP_REST_Request::get_body() returns null (not '') for
	 * a request that never had a body, so a strict `'' !== $body` guard
	 * would fail to exclude it and — for this signature-only path — log a
	 * spurious "rejected" warning (PR #211 review, second round).
	 */
	public function test_empty_body_is_never_authorized_via_signature() {
		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = new WP_REST_Request( 'GET', '/paidy-receiver/v1/receive' );
		$request->set_query_params(
			array(
				'application_id' => 'WC000000571',
				'paidy_status'   => 'canceled',
			)
		);
		$ts = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		// A signature computed exactly the way the receiver would recompute
		// it for this (empty) body — i.e. genuinely valid for '', not forged.
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, '' ) );

		$result = $receiver->check_permissions( $request );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'paidy_invalid_state', $result->get_error_code() );

		// The "signature present but invalid" warning branch — and the
		// throttle transient it sets — must never be reached for a request
		// that never had a body to sign in the first place.
		$this->assertFalse( get_transient( WC_Paidy_Apply_Receiver::SIGNATURE_WARNING_THROTTLE ) );
	}

	/**
	 * check_permissions() rejects a callback whose application_id does not
	 * match the currently on-record application (PR #211 review finding): a
	 * delayed/resent callback for a superseded application must not be able
	 * to approve/reject/cancel a newer onboarding attempt.
	 */
	public function test_permission_rejects_callback_for_superseded_application() {
		update_option( 'paidy_application_id', 'WC000000999' );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request(); // BODY's application_id is WC000000571.
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$result = $receiver->check_permissions( $request );
		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'paidy_stale_application', $result->get_error_code() );
	}

	/**
	 * The application_id match check only applies once an application ID is
	 * on record — legacy sites where it was never stored keep working via
	 * the existing state/signature checks.
	 */
	public function test_permission_allows_callback_when_no_current_application_id_recorded() {
		delete_option( 'paidy_application_id' );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request();
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * A callback for the current application (application_id matches the
	 * recorded one) is unaffected by the mismatch check.
	 */
	public function test_permission_allows_callback_for_current_application() {
		update_option( 'paidy_application_id', 'WC000000571' ); // Matches BODY.

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = $this->build_request();
		$ts       = (string) time();
		$request->set_header( WC_Paidy_Apply_Receiver::TIMESTAMP_HEADER, $ts );
		$request->set_header( WC_Paidy_Apply_Receiver::SIGNATURE_HEADER, $this->sign( $ts, $request->get_body() ) );

		$this->assertTrue( $receiver->check_permissions( $request ) );
	}

	/**
	 * A GET request carrying a valid state token — the still-registered
	 * `GET` variant of the route, which has an empty body and therefore no
	 * signed payload — is processed end to end via its query parameters
	 * (PR #211 review: get_body_only_params() must not turn this into an
	 * unconditional `no_data` error).
	 */
	public function test_state_authorized_get_request_processed_via_query_params() {
		update_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN, time(), false );

		$receiver = new WC_Paidy_Apply_Receiver();
		$request  = new WP_REST_Request( 'GET', '/paidy-receiver/v1/receive' );
		$request->set_query_params(
			array(
				'application_id'   => 'WC000000571',
				'state'            => self::TOKEN,
				'paidy_status'     => 'approved',
				'public_live_key'  => $this->encrypt_key( 'pk_live_xxx' ),
				'secret_live_key'  => $this->encrypt_key( 'sk_live_xxx' ),
				'public_test_key'  => $this->encrypt_key( 'pk_test_xxx' ),
				'secret_test_key'  => $this->encrypt_key( 'sk_test_xxx' ),
			)
		);

		$this->assertTrue( $receiver->check_permissions( $request ) );
		$result = $receiver->handle_receive_data( $request );
		$this->assertInstanceOf( 'WP_REST_Response', $result );
		$this->assertSame( 200, $result->get_status() );
	}
}
