<?php
/**
 * Paidy Apply Receiver
 *
 * Handles the REST API endpoints for receiving and processing Paidy applications.
 *
 * @package WooCommerce
 * @category Payment Gateways
 * @author Paidy
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paidy Receiver Plugin Class.
 * REST API endpoint class for WordPress plugin.
 */
class WC_Paidy_Apply_Receiver {

	/**
	 * Prefix for the options that store active onboarding state tokens.
	 *
	 * Each token is stored as its own non-autoloaded option (prefix + token,
	 * value = issued UNIX time). One row per token means concurrent onboarding
	 * sessions insert separate rows and cannot overwrite each other — a single
	 * shared array option would lose tokens to read-modify-write races.
	 * Options survive object-cache evictions and have no TTL, unlike transients —
	 * the Paidy review takes days to weeks, far longer than any safe transient TTL.
	 */
	const STATE_OPTION_PREFIX = 'paidy_onboarding_state_';

	/**
	 * Request header carrying the HMAC-SHA256 signature of the callback body.
	 *
	 * The intermediary signs `<timestamp>.<raw JSON body>` with the site hash
	 * shared at application time, so a callback can be authenticated even when
	 * the one-time state token has expired or was never issued (applications
	 * submitted from plugin versions before 2.9.13 sent no state at all, and
	 * 2.9.13–2.9.14 kept it in a 2-day transient that never outlived the review).
	 *
	 * @since 2.9.16
	 */
	const SIGNATURE_HEADER = 'x-paidy-receiver-signature';

	/**
	 * Request header carrying the UNIX timestamp that was signed together with the body.
	 *
	 * @since 2.9.16
	 */
	const TIMESTAMP_HEADER = 'x-paidy-receiver-timestamp';

	/**
	 * Option-name prefix for signature claims (replay guard).
	 *
	 * A signed callback claims its signature atomically during authorization
	 * (add_option() on a unique option_name), so two concurrent deliveries of
	 * the same request cannot both pass. The claim is released if processing
	 * fails and kept (until pruned) if it succeeds.
	 *
	 * @since 2.9.16
	 */
	const SIGNATURE_USED_PREFIX = 'paidy_receiver_sig_';

	/**
	 * Transient that throttles the "signature present but invalid" warning.
	 *
	 * @since 2.9.16
	 */
	const SIGNATURE_WARNING_THROTTLE = 'paidy_receiver_sig_warned';

	/**
	 * Option-name prefix for business-event claims (idempotency guard).
	 *
	 * Unlike the per-signature claim (keyed by the HMAC, which changes on
	 * every retry because the timestamp it covers changes), this key is
	 * derived from stable, business-relevant fields — application_id,
	 * paidy_status, and the four (still-encrypted) key fields — so a retry or
	 * manual resend of the identical decision cannot re-run the
	 * credential/status update and the paidy_application_approved/rejected
	 * action a second time just because it carries a fresh timestamp and
	 * therefore a different signature.
	 *
	 * @since 2.9.16
	 */
	const EVENT_CLAIM_PREFIX = 'paidy_receiver_event_';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Get the lifetime of an onboarding state token in seconds.
	 *
	 * @return int TTL in seconds.
	 */
	public static function get_state_token_ttl() {
		/**
		 * Filters the lifetime of a Paidy onboarding state token.
		 *
		 * The token must outlive the Paidy merchant review, which can take
		 * several weeks between application and the key-delivery callback.
		 *
		 * @param int $ttl Lifetime in seconds. Default 90 days.
		 */
		return (int) apply_filters( 'wc4jp_paidy_onboarding_state_ttl', 90 * DAY_IN_SECONDS );
	}

	/**
	 * Store a one-time onboarding state token.
	 *
	 * Expired entries are pruned on every store so no cron cleanup is needed.
	 *
	 * @param string $token 32-char alphanumeric state token.
	 * @return bool True if the token was persisted, false otherwise.
	 */
	public static function store_state_token( $token ) {
		if ( ! is_string( $token ) || 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
			return false;
		}

		self::prune_expired_state_tokens();

		update_option( self::STATE_OPTION_PREFIX . $token, time(), false );

		// Re-read to verify the token actually persisted — update_option()
		// returns false on a no-change write, so its return value alone
		// cannot distinguish failure from an identical existing value.
		return is_numeric( get_option( self::STATE_OPTION_PREFIX . $token ) );
	}

	/**
	 * Verify an onboarding state token exists and has not expired.
	 *
	 * Accepts raw request input: anything but a 32-char alphanumeric string is
	 * rejected before any storage key is built.
	 *
	 * @param mixed $token 32-char alphanumeric state token.
	 * @return bool True if the token is valid.
	 */
	public static function verify_state_token( $token ) {
		if ( ! is_string( $token ) || 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
			return false;
		}

		// is_numeric (not is_int): scalar options round-trip through the DB as
		// numeric strings on requests other than the one that stored them.
		$issued_at = get_option( self::STATE_OPTION_PREFIX . $token );
		if ( is_numeric( $issued_at ) && ( time() - (int) $issued_at ) <= self::get_state_token_ttl() ) {
			return true;
		}

		// Legacy fallback: tokens issued by older plugin versions were stored as
		// transients under the same key (transients live in separate, prefixed
		// option rows, so there is no collision). Keep accepting them so an
		// in-flight application submitted before the update still succeeds.
		// TODO: remove after 2-3 releases.
		if ( false !== get_transient( self::STATE_OPTION_PREFIX . $token ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Consume (delete) an onboarding state token so it cannot be reused.
	 *
	 * Accepts raw request input: anything but a 32-char alphanumeric string is
	 * rejected before any storage key is built.
	 *
	 * @param mixed $token 32-char alphanumeric state token.
	 * @return void
	 */
	public static function consume_state_token( $token ) {
		if ( ! is_string( $token ) || 1 !== preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ) {
			return;
		}

		delete_option( self::STATE_OPTION_PREFIX . $token );

		// Also clear the legacy transient variant. Remove together with the
		// legacy fallback in verify_state_token().
		delete_transient( self::STATE_OPTION_PREFIX . $token );
	}

	/**
	 * Get the maximum allowed clock drift between the signed timestamp and now.
	 *
	 * @since 2.9.16
	 *
	 * @return int Tolerance in seconds.
	 */
	public static function get_signature_tolerance() {
		/**
		 * Filters how far the signed callback timestamp may deviate from the
		 * receiving site's clock before the signature is rejected.
		 *
		 * @since 2.9.16
		 *
		 * @param int $tolerance Tolerance in seconds. Default 10 minutes.
		 */
		return (int) apply_filters( 'wc4jp_paidy_receiver_signature_tolerance', 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Verify an HMAC-SHA256 signature over `<timestamp>.<body>` keyed by the site hash.
	 *
	 * Accepts raw header values: anything but a numeric timestamp within the
	 * tolerance window and a 64-char lowercase hex digest is rejected before
	 * any HMAC is computed. Signatures already claimed by an earlier delivery
	 * are rejected as well (see claim_signature()).
	 *
	 * @since 2.9.16
	 *
	 * @param mixed  $timestamp UNIX timestamp from the request header.
	 * @param mixed  $signature Hex HMAC digest from the request header.
	 * @param string $body      Raw request body exactly as received.
	 * @param string $site_hash Shared secret established at application time.
	 * @return bool True if the signature is valid, fresh, and unused.
	 */
	public static function verify_request_signature( $timestamp, $signature, $body, $site_hash ) {
		return self::signature_matches( $timestamp, $signature, $body, $site_hash )
			&& ! self::is_signature_claimed( $signature );
	}

	/**
	 * Cryptographic half of verify_request_signature(), without the claim check.
	 *
	 * Used where a signature must be evaluated on its own merit independent of
	 * claim state — e.g. deciding whether a signature accompanying an
	 * already-state-authorized request is genuine before claiming it (see
	 * check_permissions()). A malformed/incorrect signature here must not
	 * reject a request that state-token verification already authorized.
	 *
	 * @since 2.9.16
	 *
	 * @param mixed  $timestamp UNIX timestamp from the request header.
	 * @param mixed  $signature Hex HMAC digest from the request header.
	 * @param string $body      Raw request body exactly as received.
	 * @param string $site_hash Shared secret established at application time.
	 * @return bool True if the signature is well-formed, fresh, and matches the body.
	 */
	private static function signature_matches( $timestamp, $signature, $body, $site_hash ) {
		if ( ! is_string( $site_hash ) || '' === $site_hash || ! is_string( $body ) ) {
			return false;
		}
		if ( ! is_string( $signature ) || 1 !== preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return false;
		}
		if ( ! is_string( $timestamp ) && ! is_int( $timestamp ) ) {
			return false;
		}
		if ( 1 !== preg_match( '/^[0-9]{1,12}$/', (string) $timestamp ) ) {
			return false;
		}
		if ( abs( time() - (int) $timestamp ) > self::get_signature_tolerance() ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $body, $site_hash );
		return hash_equals( $expected, $signature );
	}

	/**
	 * Build the claim option name for a signature.
	 *
	 * @since 2.9.16
	 *
	 * @param string $signature Hex HMAC digest.
	 * @return string
	 */
	private static function signature_claim_key( $signature ) {
		return self::SIGNATURE_USED_PREFIX . substr( $signature, 0, 40 );
	}

	/**
	 * Whether a signature has already been claimed by another delivery.
	 *
	 * @since 2.9.16
	 *
	 * @param string $signature Hex HMAC digest.
	 * @return bool
	 */
	private static function is_signature_claimed( $signature ) {
		return false !== get_option( self::signature_claim_key( $signature ) );
	}

	/**
	 * Atomically claim a signature for the current delivery.
	 *
	 * The claim uses add_option(), which inserts against the unique
	 * option_name index, so when the
	 * intermediary retries the same signed request concurrently only one of
	 * the overlapping requests obtains the claim; the others are rejected
	 * before any credential or status update runs. Stale claims are pruned
	 * on every call so no cron cleanup is needed.
	 *
	 * @since 2.9.16
	 *
	 * @param mixed $signature Hex HMAC digest from the request header.
	 * @return bool True if this request now owns the claim.
	 */
	public static function claim_signature( $signature ) {
		if ( ! is_string( $signature ) || 1 !== preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return false;
		}

		self::prune_stale_signature_claims();

		return false !== add_option( self::signature_claim_key( $signature ), time(), '', false );
	}

	/**
	 * Release a signature claim so the intermediary can retry after a
	 * processing failure (DB write, decryption, missing key field, ...).
	 *
	 * @since 2.9.16
	 *
	 * @param mixed $signature Hex HMAC digest from the request header.
	 * @return void
	 */
	public static function release_signature_claim( $signature ) {
		if ( ! is_string( $signature ) || 1 !== preg_match( '/^[a-f0-9]{64}$/', $signature ) ) {
			return;
		}
		delete_option( self::signature_claim_key( $signature ) );
	}

	/**
	 * Delete signature claims older than twice the timestamp tolerance.
	 *
	 * A signature can only verify while its timestamp is within the tolerance
	 * window, so a claim older than 2x the tolerance can never be replayed and
	 * is safe to drop. Same direct LIKE query rationale as
	 * prune_expired_state_tokens().
	 *
	 * @since 2.9.16
	 *
	 * @return void
	 */
	private static function prune_stale_signature_claims() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( self::SIGNATURE_USED_PREFIX ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $rows ) ) {
			return;
		}

		$max_age = 2 * self::get_signature_tolerance();
		$now     = time();
		foreach ( $rows as $row ) {
			if ( ! is_numeric( $row->option_value ) || ( $now - (int) $row->option_value ) > $max_age ) {
				delete_option( $row->option_name );
			}
		}
	}

	/**
	 * Extract the parameters this request's data should be read from.
	 *
	 * The HMAC signature verified in check_permissions() covers only the
	 * raw body, but WP_REST_Request::get_params()/get_param() merge every
	 * parameter source — and the query string wins over the body for a
	 * matching key. Trusting that merged view for a request that carries a
	 * body would let an unsigned query parameter (e.g.
	 * `?paidy_status=canceled`) override a value that was actually signed,
	 * so such a request is restricted to its JSON/form-encoded body.
	 *
	 * A request with an empty body — the still-registered `GET` variant, or
	 * any state-token-authorized callback that never carried one — has no
	 * signed payload for a query parameter to override, so it falls back to
	 * the full parameter merge (query string included) exactly as before
	 * the body-only restriction was introduced.
	 *
	 * @since 2.9.16
	 *
	 * @param WP_REST_Request $request request object.
	 * @return array
	 */
	private static function get_body_only_params( $request ) {
		// WP_REST_Request::get_body() defaults to null (never '') when
		// set_body() was never called — e.g. every GET request — so this
		// must use empty(), not a strict '' === comparison.
		if ( empty( $request->get_body() ) ) {
			return $request->get_params();
		}

		$json_params = $request->get_json_params();

		return array_merge( $request->get_body_params(), is_array( $json_params ) ? $json_params : array() );
	}

	/**
	 * Build the business-event claim key for a request.
	 *
	 * Derived only from application_id, paidy_status, and the four
	 * (still-encrypted) key fields as delivered — never from the header
	 * timestamp or signature — so a retry or resend carrying the identical
	 * decision produces the identical key regardless of when it is sent.
	 * Missing fields are treated as empty strings so the key stays
	 * deterministic even for requests that omit paidy_status or the key
	 * fields (e.g. a bare ping). Read from the body only (see
	 * get_body_only_params()) so an unsigned query parameter cannot change
	 * which event this request is claiming.
	 *
	 * @since 2.9.16
	 *
	 * @param WP_REST_Request $request request object.
	 * @return string
	 */
	private static function event_claim_key( $request ) {
		$body_params = self::get_body_only_params( $request );
		$parts       = array(
			isset( $body_params['application_id'] ) ? (string) $body_params['application_id'] : '',
			isset( $body_params['paidy_status'] ) ? (string) $body_params['paidy_status'] : '',
		);
		foreach ( array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' ) as $field ) {
			$value   = isset( $body_params[ $field ] ) ? $body_params[ $field ] : null;
			$parts[] = is_string( $value ) ? $value : '';
		}

		return self::EVENT_CLAIM_PREFIX . hash( 'sha256', implode( '|', $parts ) );
	}

	/**
	 * Atomically claim the business event carried by a request.
	 *
	 * Same add_option()-based exclusivity as claim_signature(), but keyed by
	 * event_claim_key() instead of the signature, so a retry with a fresh
	 * timestamp (and therefore a different signature) for the identical
	 * decision is still recognized as a duplicate and rejected.
	 *
	 * @since 2.9.16
	 *
	 * @param WP_REST_Request $request request object.
	 * @return bool True if this request now owns the claim.
	 */
	public static function claim_event( $request ) {
		self::prune_stale_event_claims();

		return false !== add_option( self::event_claim_key( $request ), time(), '', false );
	}

	/**
	 * Release a business-event claim so a failed delivery can be retried.
	 *
	 * @since 2.9.16
	 *
	 * @param WP_REST_Request $request request object.
	 * @return void
	 */
	public static function release_event_claim( $request ) {
		delete_option( self::event_claim_key( $request ) );
	}

	/**
	 * Delete event claims older than twice the signature timestamp tolerance.
	 *
	 * A short-lived window is intentional: it closes the near-term automatic-
	 * retry gap this guard exists for, while still letting an operator's
	 * deliberate later resend (see WC_Paidy_Apply_Receiver via the paidy-app
	 * "resend" action) go through once the window has passed. Same direct
	 * LIKE query rationale as prune_expired_state_tokens().
	 *
	 * @since 2.9.16
	 *
	 * @return void
	 */
	private static function prune_stale_event_claims() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( self::EVENT_CLAIM_PREFIX ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $rows ) ) {
			return;
		}

		$max_age = 2 * self::get_signature_tolerance();
		$now     = time();
		foreach ( $rows as $row ) {
			if ( ! is_numeric( $row->option_value ) || ( $now - (int) $row->option_value ) > $max_age ) {
				delete_option( $row->option_name );
			}
		}
	}

	/**
	 * Delete state token options that are past their TTL or hold invalid values.
	 *
	 * Runs on every store_state_token() call so no cron cleanup is needed.
	 *
	 * @return void
	 */
	private static function prune_expired_state_tokens() {
		global $wpdb;

		// Token options are non-autoloaded and keyed by a random token value,
		// so there is no core API to enumerate them — a direct LIKE query is
		// required. esc_like() makes the underscores match literally instead
		// of acting as single-character LIKE wildcards, so the pattern cannot
		// accidentally match other, similarly named option rows.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( self::STATE_OPTION_PREFIX ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $rows ) ) {
			return;
		}

		$ttl = self::get_state_token_ttl();
		$now = time();
		foreach ( $rows as $row ) {
			if ( ! is_numeric( $row->option_value ) || ( $now - (int) $row->option_value ) > $ttl ) {
				delete_option( $row->option_name );
			}
		}
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'paidy-receiver/v1',
			'/receive',
			array(
				'methods'             => 'GET, POST',
				'callback'            => array( $this, 'handle_receive_data' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Check permissions for the Paidy receiver endpoint.
	 * Verifies site hash is configured and application_id format is valid.
	 *
	 * @param WP_REST_Request $request request object.
	 * @return bool|WP_Error
	 */
	public function check_permissions( $request ) {
		// Require paidy_site_hash to be configured before accepting any data.
		$site_hash = get_option( 'paidy_site_hash' );
		if ( empty( $site_hash ) ) {
			return new WP_Error(
				'paidy_not_configured',
				__( 'Paidy onboarding is not configured.', 'woocommerce-for-japan' ),
				array( 'status' => 403 )
			);
		}

		// Validate application_id format (alphanumeric, hyphens, underscores only).
		$application_id = $request->get_param( 'application_id' );
		if ( empty( $application_id ) || ! preg_match( '/^[a-zA-Z0-9_\-]+$/', $application_id ) ) {
			return new WP_Error(
				'paidy_invalid_request',
				__( 'Invalid application ID format.', 'woocommerce-for-japan' ),
				array( 'status' => 400 )
			);
		}

		// Reject callbacks for a superseded application. paidy_site_hash is
		// site-wide and intentionally survives reinstalls (see uninstall.php),
		// so without this check a delayed callback or a manual resend for an
		// older application would still authenticate via a still-valid state
		// token or a correctly signed body, and could approve/reject/cancel a
		// newer onboarding attempt — including overwriting or clearing its
		// credentials. Only enforced when an application ID is on record
		// (set by the wizard on submission, see
		// WC_Paidy_Admin_Wizard::store_application_id()); legacy sites and
		// applications submitted before this was tracked fall through
		// unchanged to the state/signature checks below.
		$current_application_id = get_option( 'paidy_application_id' );
		if ( ! empty( $current_application_id ) && $current_application_id !== $application_id ) {
			return new WP_Error(
				'paidy_stale_application',
				__( 'This callback is for an application that is no longer the current one.', 'woocommerce-for-japan' ),
				array( 'status' => 403 )
			);
		}

		// Verify the one-time state token generated when the onboarding form was submitted.
		// Tokens are stored keyed by their own value (set in the admin wizard) so
		// parallel onboarding sessions cannot clobber each other's tokens. Verifying
		// existence of the scoped key is sufficient — no separate value comparison needed.
		//
		// verify_state_token() validates the format internally (32-char alphanumeric,
		// matching wp_generate_password(32, false)) before building any storage key,
		// so non-string values or oversized inputs are rejected there.
		//
		// Do NOT consume the token here — consume it only after the handler
		// completes successfully so a transient DB/decryption failure does not
		// permanently prevent retrying the onboarding callback.
		if ( self::verify_state_token( $request->get_param( 'state' ) ) ) {
			// A well-formed, correctly-signed signature accompanying this
			// state-authorized request is claimed here as well. Without this,
			// consuming the state token on success leaves the signature
			// unclaimed; a sequential retry of the identical request would
			// then fail the (now-consumed) state check but pass the signature
			// check and re-run the handler a second time. A signature that is
			// missing, malformed, or does not match the body is ignored here
			// — the state token alone already authorizes the request, and a
			// bad signature header must not turn a legitimate request away.
			$signature = $request->get_header( self::SIGNATURE_HEADER );
			$timestamp = $request->get_header( self::TIMESTAMP_HEADER );
			$body      = $request->get_body();
			// A signature over an empty body (a GET request has none) covers
			// nothing meaningful — application_id/paidy_status/keys read from
			// query params would then be entirely unauthenticated by it — so
			// only trust the header when there is a body for it to protect.
			$has_signature = ( null !== $signature && '' !== $body && self::signature_matches( $timestamp, $signature, $body, $site_hash ) );

			if ( $has_signature && ! self::claim_signature( $signature ) ) {
				// The signature was already claimed by another delivery —
				// reject the replay even though the state token still verifies.
				return new WP_Error(
					'paidy_invalid_state',
					__( 'Invalid or missing state token or signature for Paidy onboarding.', 'woocommerce-for-japan' ),
					array( 'status' => 403 )
				);
			}

			// Claim the underlying business event unconditionally, even when
			// no (or no valid) signature accompanies this request. The state
			// token is only consumed after the handler completes (see above),
			// so without this, two concurrent state-only deliveries of the
			// same callback would both pass this check and both run the
			// credential/status updates.
			if ( ! self::claim_event( $request ) ) {
				if ( $has_signature ) {
					self::release_signature_claim( $signature );
				}
				return new WP_Error(
					'paidy_invalid_state',
					__( 'Invalid or missing state token or signature for Paidy onboarding.', 'woocommerce-for-japan' ),
					array( 'status' => 403 )
				);
			}
			return true;
		}

		// No usable state token. Fall back to the signed-callback path: the
		// intermediary signs the raw body with the site hash it received at
		// application time, so the callback can still be authenticated when the
		// token expired (2.9.13–2.9.14 stored it in a 2-day transient), was
		// never issued (pre-2.9.13 applications), or was lost to a reinstall.
		$signature = $request->get_header( self::SIGNATURE_HEADER );
		$timestamp = $request->get_header( self::TIMESTAMP_HEADER );
		$body      = $request->get_body();
		// See the same-named guard above: a signature over an empty body
		// authenticates nothing about the (query-string-only) parameters a
		// GET request would carry.
		if ( null !== $signature && '' !== $body ) {
			// Verify, then claim both the signature and the underlying
			// business event atomically so concurrent deliveries of the same
			// request — or a retry that carries a fresh timestamp and
			// therefore a different signature for the same application_id +
			// paidy_status + key fields — cannot both run the handler. Both
			// claims are released in handle_receive_data() if processing
			// fails.
			if ( self::verify_request_signature( $timestamp, $signature, $body, $site_hash )
				&& self::claim_signature( $signature ) ) {
				if ( self::claim_event( $request ) ) {
					wc_get_logger()->info(
						'Paidy onboarding callback accepted via body signature (state token missing or expired).',
						array( 'source' => 'paidy-wc' )
					);
					return true;
				}
				// Signature was fresh (unclaimed) but the same business event
				// was already claimed by an earlier delivery — release the
				// signature claim we just took and fall through to the 403.
				self::release_signature_claim( $signature );
			}

			// A signature was sent but did not verify (or was already claimed).
			// The most common field cause is server clock drift beyond the
			// tolerance, so record the drift to make the 403 diagnosable from
			// the site's own log. The endpoint is public, so throttle the
			// warning to one entry per window to keep a flood of forged
			// requests from growing the log file.
			if ( false === get_transient( self::SIGNATURE_WARNING_THROTTLE ) ) {
				set_transient( self::SIGNATURE_WARNING_THROTTLE, 1, self::get_signature_tolerance() );
				$drift = is_numeric( $timestamp ) ? (string) ( time() - (int) $timestamp ) : 'n/a';
				wc_get_logger()->warning(
					sprintf(
						'Paidy onboarding callback rejected: signature header present but invalid or already used (timestamp drift: %s s, tolerance: %d s). Check the server clock and that paidy_site_hash matches the value sent at application time. Further occurrences are suppressed for %d s.',
						$drift,
						self::get_signature_tolerance(),
						self::get_signature_tolerance()
					),
					array( 'source' => 'paidy-wc' )
				);
			}
		}

		return new WP_Error(
			'paidy_invalid_state',
			__( 'Invalid or missing state token or signature for Paidy onboarding.', 'woocommerce-for-japan' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Handle received POST data.
	 *
	 * Wraps process_receive_data() so that a failed delivery releases the
	 * signature claim taken in check_permissions() and the intermediary can
	 * retry with the same signed request.
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_receive_data( $request ) {
		$result = $this->process_receive_data( $request );

		if ( is_wp_error( $result ) ) {
			self::release_signature_claim( $request->get_header( self::SIGNATURE_HEADER ) );
			// Also release the business-event claim (may have been taken on
			// either the state-authorized or the signature-only path) so a
			// transient failure does not permanently block a legitimate retry.
			self::release_event_claim( $request );
		}

		return $result;
	}

	/**
	 * Decrypt, validate and store the delivered onboarding result.
	 *
	 * @since 2.9.16 Split out of handle_receive_data().
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	private function process_receive_data( $request ) {
		try {
			// Only the request body is covered by the HMAC signature verified
			// in check_permissions() — see get_body_only_params(). Using
			// WP_REST_Request::get_params() here would also pull in (and let
			// override) query-string values that were never actually signed.
			$post_params = self::get_body_only_params( $request );

			// Remove WordPress internal parameters if they exist.
			$filtered_params = array();
			$internal_params = array( '_wpnonce', '_wp_http_referer', 'rest_route' );

			foreach ( $post_params as $key => $value ) {
				if ( ! in_array( $key, $internal_params, true ) ) {
					$filtered_params[ $key ] = $value;
				}
			}

			// Check if data exists.
			if ( empty( $filtered_params ) ) {
				return new WP_Error(
					'no_data',
					'No POST data found.',
					array( 'status' => 400 )
				);
			}

			if ( ! isset( $filtered_params['application_id'] ) || empty( $filtered_params['application_id'] ) ) {
				return new WP_Error(
					'missing_application_id',
					'Missing or empty application ID.',
					array( 'status' => 400 )
				);
			}

			// Check if the site hash is set.
			$site_hash = get_option( 'paidy_site_hash' );
			if ( empty( $site_hash ) ) {
				return new WP_Error(
					'missing_site_hash',
					'Site hash is not set.',
					array( 'status' => 400 )
				);
			}

			// Decrypt AES-256-CBC-encoded API keys sent by the Paidy intermediary server.
			$method     = 'AES-256-CBC';
			$aes_key    = substr( hash( 'sha256', $site_hash ), 0, 32 );
			$aes_iv     = substr( hash( 'sha256', $site_hash . 'iv' ), 0, 16 );
			$key_fields = array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' );
			$decrypted  = array();

			foreach ( $key_fields as $field ) {
				if ( ! isset( $filtered_params[ $field ] ) ) {
					// Field absent — store empty string so all four key fields are
					// always present in $decrypted and merged into $filtered_params.
					$decrypted[ $field ] = '';
					continue;
				}

				// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- legitimate AES decryption of Paidy-supplied key data.
				$decoded = base64_decode( (string) $filtered_params[ $field ], true );
				// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				if ( false === $decoded ) {
					return new WP_Error(
						'paidy_invalid_encoding',
						/* translators: %s: API key field name */
						sprintf( __( 'Invalid base64 encoding for field: %s', 'woocommerce-for-japan' ), esc_html( $field ) ),
						array( 'status' => 400 )
					);
				}

				// OPENSSL_RAW_DATA is required because $decoded is already raw binary
				// (we base64-decoded it above). Without this flag openssl_decrypt()
				// would attempt a second base64 decode and fail.
				$result = openssl_decrypt( $decoded, $method, $aes_key, OPENSSL_RAW_DATA, $aes_iv );
				if ( false === $result ) {
					return new WP_Error(
						'paidy_decryption_failed',
						/* translators: %s: API key field name */
						sprintf( __( 'Decryption failed for field: %s', 'woocommerce-for-japan' ), esc_html( $field ) ),
						array( 'status' => 400 )
					);
				}

				$decrypted[ $field ] = $result;
			}

			// Merge all four key fields (present or absent) into $filtered_params so
			// downstream code can access them unconditionally without undefined-index notices.
			$filtered_params = array_merge( $filtered_params, $decrypted );

			if ( isset( $filtered_params['paidy_status'] ) ) {
				$paidy_status         = $filtered_params['paidy_status'];
				$allowed_status_array = array( 'approved', 'rejected', 'canceled' );
				// Validate the paidy_status.
				if ( ! in_array( $paidy_status, $allowed_status_array, true ) ) {
					return new WP_Error(
						'invalid_paidy_status',
						'Invalid paidy_status value. Allowed values are: ' . implode( ', ', $allowed_status_array ),
						array( 'status' => 400 )
					);
				}
				// Additional processing for approved status can be added here if needed.
				$woocommerce_paidy_on_boarding_settings = get_option( 'woocommerce_paidy_on_boarding_settings', array() );
				$current_step                           = isset( $woocommerce_paidy_on_boarding_settings['currentStep'] ) ? $woocommerce_paidy_on_boarding_settings['currentStep'] : 0;
				if ( 'approved' === $paidy_status ) {
					// Require all four key fields to be non-empty after decryption.
					// An approved callback from the intermediary always contains all four
					// keys; an empty string here means the field was absent or the
					// intermediary sent an incomplete payload. Accepting empty keys would
					// silently overwrite existing credentials with blank values, breaking
					// payment processing without any obvious error.
					$required_key_fields = array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' );
					foreach ( $required_key_fields as $key_field ) {
						if ( empty( $filtered_params[ $key_field ] ) ) {
							return new WP_Error(
								'paidy_missing_key',
								/* translators: %s: API key field name */
								sprintf( __( 'Approved response is missing a required API key field: %s', 'woocommerce-for-japan' ), esc_html( $key_field ) ),
								array( 'status' => 400 )
							);
						}
					}

					// Process approved status.
					$woocommerce_paidy_on_boarding_settings['currentStep'] = 3;
					update_option( 'woocommerce_paidy_on_boarding_settings', $woocommerce_paidy_on_boarding_settings );

					$woocommerce_paidy_settings                        = get_option( 'woocommerce_paidy_settings', array() );
					$woocommerce_paidy_settings['api_public_key']      = $filtered_params['public_live_key'];
					$woocommerce_paidy_settings['api_secret_key']      = $filtered_params['secret_live_key'];
					$woocommerce_paidy_settings['test_api_public_key'] = $filtered_params['public_test_key'];
					$woocommerce_paidy_settings['test_api_secret_key'] = $filtered_params['secret_test_key'];
					$woocommerce_paidy_settings['environment']         = '';
					update_option( 'woocommerce_paidy_settings', $woocommerce_paidy_settings );

					do_action( 'paidy_application_approved', $filtered_params );
				} elseif ( 'rejected' === $paidy_status || 'canceled' === $paidy_status ) {
					if ( 'canceled' === $paidy_status ) {
						// Process canceled status.
						delete_option( 'woocommerce_paidy_on_boarding_settings' );
						delete_option( 'paidy_application_id' );
					} else {
						// Process rejected status.
						$woocommerce_paidy_on_boarding_settings['currentStep'] = 99;
						update_option( 'woocommerce_paidy_on_boarding_settings', $woocommerce_paidy_on_boarding_settings );
					}

					$woocommerce_paidy_settings                        = get_option( 'woocommerce_paidy_settings', array() );
					$woocommerce_paidy_settings['api_public_key']      = '';
					$woocommerce_paidy_settings['api_secret_key']      = '';
					$woocommerce_paidy_settings['test_api_public_key'] = '';
					$woocommerce_paidy_settings['test_api_secret_key'] = '';
					$woocommerce_paidy_settings['environment']         = '';
					update_option( 'woocommerce_paidy_settings', $woocommerce_paidy_settings );

					do_action( 'paidy_application_rejected', $filtered_params );
				}
			}

			// Save data to wp_option.
			// update_option() returns false both when the save fails AND when the stored
			// value is already identical to $filtered_params (no-change). Treat the
			// no-change case as success so retries with an identical payload do not
			// incorrectly return a 500 and skip consuming the one-time state token.
			$saved = update_option( 'paidy_received_data', $filtered_params, false );
			if ( false === $saved ) {
				if ( get_option( 'paidy_received_data' ) === $filtered_params ) {
					$saved = true; // Value already identical — treat as success.
				} else {
					// Option does not exist yet — create it.
					$saved = add_option( 'paidy_received_data', $filtered_params, '', 'no' );
				}
			}
			// Check if the data was saved successfully.
			if ( $saved ) {
				// Consume the one-time state token now that the handler has fully
				// succeeded — consuming it here (not in check_permissions) means a
				// transient DB or decryption failure during processing does not
				// permanently prevent the merchant from retrying the callback.
				// consume_state_token() validates the format internally (same rule
				// as verify_state_token) so it never builds a storage key from an
				// unsanitized param.
				self::consume_state_token( $request->get_param( 'state' ) );
				// A signature claim taken in check_permissions() is intentionally
				// kept here: it is the replay marker until it is pruned.

				// Success response — omit decrypted API key fields to avoid
				// exposing secrets via response bodies, proxy logs, or intermediaries.
				$sensitive_fields = array( 'public_live_key', 'secret_live_key', 'public_test_key', 'secret_test_key' );
				$safe_received    = array_diff_key( $filtered_params, array_flip( $sensitive_fields ) );

				return new WP_REST_Response(
					array(
						'success'       => true,
						'message'       => 'Data saved successfully.',
						'received_data' => $safe_received,
						'timestamp'     => current_time( 'mysql' ),
					),
					200
				);
			} else {
				// Save failed.
				return new WP_Error(
					'save_failed',
					'Failed to save data.',
					array( 'status' => 500 )
				);
			}
		} catch ( Exception $e ) {
			// Error handling.
			return new WP_Error(
				'server_error',
				'Server error occurred: ' . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Helper method to get saved data.
	 *
	 * @return mixed
	 */
	public function get_received_data() {
		return get_option( 'received_data', array() );
	}

	/**
	 * Helper method to delete saved data.
	 *
	 * @return bool True if the option was deleted, false otherwise.
	 */
	public function delete_received_data() {
		return delete_option( 'received_data' );
	}
}
