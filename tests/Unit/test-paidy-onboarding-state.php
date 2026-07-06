<?php
/**
 * Tests for the Paidy onboarding state token storage.
 *
 * Covers the migration from transients (2-day TTL, evictable) to a
 * non-autoloaded option so the token survives the multi-week Paidy review
 * between wizard submission and the CSV-import key-delivery callback.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * WC_Paidy_Onboarding_State_Test
 */
class WC_Paidy_Onboarding_State_Test extends WP_UnitTestCase {

	/**
	 * A valid 32-char alphanumeric token used across tests.
	 *
	 * @var string
	 */
	const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEF';

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WC_Paidy_Apply_Receiver' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/gateways/paidy/class-wc-paidy-apply-receiver.php';
		}
	}

	/**
	 * Tear down test environment after each test.
	 */
	public function tearDown(): void {
		delete_option( WC_Paidy_Apply_Receiver::STATE_OPTION_NAME );
		delete_transient( 'paidy_onboarding_state_' . self::TOKEN );
		parent::tearDown();
	}

	/**
	 * A stored token verifies successfully.
	 */
	public function test_store_then_verify_returns_true() {
		$this->assertTrue( WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * A consumed token no longer verifies (one-time use).
	 */
	public function test_consume_then_verify_returns_false() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );
		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * A token older than the TTL fails verification.
	 */
	public function test_expired_token_fails_verification() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		// Rewind the issued timestamp to 91 days ago (TTL is 90 days).
		$states                 = get_option( WC_Paidy_Apply_Receiver::STATE_OPTION_NAME );
		$states[ self::TOKEN ]  = time() - ( 91 * DAY_IN_SECONDS );
		update_option( WC_Paidy_Apply_Receiver::STATE_OPTION_NAME, $states, false );

		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * The TTL is filterable via wc4jp_paidy_onboarding_state_ttl.
	 */
	public function test_ttl_is_filterable() {
		$this->assertSame( 90 * DAY_IN_SECONDS, WC_Paidy_Apply_Receiver::get_state_token_ttl() );

		$shorten = function () {
			return DAY_IN_SECONDS;
		};
		add_filter( 'wc4jp_paidy_onboarding_state_ttl', $shorten );
		$this->assertSame( DAY_IN_SECONDS, WC_Paidy_Apply_Receiver::get_state_token_ttl() );
		remove_filter( 'wc4jp_paidy_onboarding_state_ttl', $shorten );
	}

	/**
	 * A corrupted (non-array) option value does not fatal and verification fails.
	 */
	public function test_corrupted_option_does_not_fatal() {
		update_option( WC_Paidy_Apply_Receiver::STATE_OPTION_NAME, 'corrupted-string', false );

		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN ); // Must not fatal.

		// store_state_token() recovers by rebuilding the array.
		$this->assertTrue( WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * Storing a new token prunes expired entries.
	 */
	public function test_store_prunes_expired_entries() {
		$expired_token = 'ZYXWVUTSRQPONMLKJIHGFEDCBA654321';
		update_option(
			WC_Paidy_Apply_Receiver::STATE_OPTION_NAME,
			array( $expired_token => time() - ( 91 * DAY_IN_SECONDS ) ),
			false
		);

		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$states = get_option( WC_Paidy_Apply_Receiver::STATE_OPTION_NAME );
		$this->assertArrayNotHasKey( $expired_token, $states );
		$this->assertArrayHasKey( self::TOKEN, $states );
	}

	/**
	 * Multiple tokens coexist (parallel onboarding sessions).
	 */
	public function test_parallel_tokens_coexist() {
		$other_token = '0123456789abcdef0123456789abcdef';
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );
		WC_Paidy_Apply_Receiver::store_state_token( $other_token );

		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( $other_token ) );

		// Consuming one leaves the other intact.
		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( $other_token ) );
	}

	/**
	 * Legacy transient-only tokens (issued by older plugin versions) still verify.
	 */
	public function test_legacy_transient_token_verifies_and_is_consumed() {
		set_transient( 'paidy_onboarding_state_' . self::TOKEN, 1, 2 * DAY_IN_SECONDS );

		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );

		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		$this->assertFalse( get_transient( 'paidy_onboarding_state_' . self::TOKEN ) );
	}

	/**
	 * Malformed tokens are rejected by every helper without touching storage.
	 */
	public function test_malformed_tokens_rejected() {
		$malformed = array( '', 'short', str_repeat( 'a', 33 ), 'has-hyphens-not-allowed-in-token', array( 'x' ), null, 123 );

		foreach ( $malformed as $bad_token ) {
			$this->assertFalse( WC_Paidy_Apply_Receiver::store_state_token( $bad_token ) );
			$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( $bad_token ) );
			WC_Paidy_Apply_Receiver::consume_state_token( $bad_token ); // Must not fatal.
		}

		$this->assertFalse( get_option( WC_Paidy_Apply_Receiver::STATE_OPTION_NAME ) );
	}
}
