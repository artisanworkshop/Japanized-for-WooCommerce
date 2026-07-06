<?php
/**
 * Tests for the Paidy onboarding state token storage.
 *
 * Covers the migration from transients (2-day TTL, evictable) to per-token
 * non-autoloaded options so the token survives the multi-week Paidy review
 * between wizard submission and the CSV-import key-delivery callback, and so
 * concurrent onboarding sessions cannot overwrite each other's tokens.
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
	 * A second valid token for parallel-session tests.
	 *
	 * @var string
	 */
	const OTHER_TOKEN = '0123456789abcdef0123456789abcdef';

	/**
	 * A third valid token used as an expired entry.
	 *
	 * @var string
	 */
	const EXPIRED_TOKEN = 'ZYXWVUTSRQPONMLKJIHGFEDCBA654321';

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
		foreach ( array( self::TOKEN, self::OTHER_TOKEN, self::EXPIRED_TOKEN ) as $token ) {
			delete_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . $token );
			delete_transient( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . $token );
		}
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
		update_option(
			WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN,
			time() - ( 91 * DAY_IN_SECONDS ),
			false
		);

		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * A timestamp stored as a numeric string (DB round-trip) still verifies.
	 */
	public function test_numeric_string_timestamp_verifies() {
		update_option(
			WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN,
			(string) time(),
			false
		);

		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
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
	 * A corrupted (non-numeric) option value does not fatal and verification fails.
	 */
	public function test_corrupted_option_does_not_fatal() {
		update_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN, 'corrupted-string', false );

		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN ); // Must not fatal.

		// store_state_token() recovers by overwriting with a fresh timestamp.
		$this->assertTrue( WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * Storing a new token prunes expired and corrupted entries.
	 */
	public function test_store_prunes_expired_entries() {
		update_option(
			WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::EXPIRED_TOKEN,
			time() - ( 91 * DAY_IN_SECONDS ),
			false
		);
		update_option(
			WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::OTHER_TOKEN,
			'corrupted-string',
			false
		);

		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$this->assertFalse( get_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::EXPIRED_TOKEN ) );
		$this->assertFalse( get_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::OTHER_TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
	}

	/**
	 * Pruning does not delete legacy transient rows for other tokens.
	 */
	public function test_prune_leaves_legacy_transients_intact() {
		set_transient( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::OTHER_TOKEN, 1, 2 * DAY_IN_SECONDS );

		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );

		$this->assertNotFalse( get_transient( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::OTHER_TOKEN ) );
	}

	/**
	 * Multiple tokens coexist (parallel onboarding sessions).
	 */
	public function test_parallel_tokens_coexist() {
		WC_Paidy_Apply_Receiver::store_state_token( self::TOKEN );
		WC_Paidy_Apply_Receiver::store_state_token( self::OTHER_TOKEN );

		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::OTHER_TOKEN ) );

		// Consuming one leaves the other intact.
		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::OTHER_TOKEN ) );
	}

	/**
	 * Legacy transient-only tokens (issued by older plugin versions) still verify.
	 */
	public function test_legacy_transient_token_verifies_and_is_consumed() {
		set_transient( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN, 1, 2 * DAY_IN_SECONDS );

		$this->assertTrue( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );

		WC_Paidy_Apply_Receiver::consume_state_token( self::TOKEN );
		$this->assertFalse( WC_Paidy_Apply_Receiver::verify_state_token( self::TOKEN ) );
		$this->assertFalse( get_transient( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . self::TOKEN ) );
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

		$this->assertFalse( get_option( WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX . 'short' ) );
	}
}
