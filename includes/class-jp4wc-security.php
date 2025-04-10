<?php
/**
 * Security features for WooCommerce Japan plugin
 *
 * @package WooCommerce-For-Japan
 * @since 3.0.0
 */

/**
 * Class JP4WC_Security
 *
 * Handles security-related functionality for the WooCommerce Japan plugin.
 */
class JP4WC_Security {
	/**
	 * Constructor. Sets up the security features.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'jp4wc_hide_author_id' ) );
		add_action( 'wp_login', array( $this, 'jp4wc_login_attempt_limiter_clear' ), 10, 2 );
		add_filter( 'authenticate', array( $this, 'jp4wc_login_attempt_limiter_authenticate' ), 30, 2 );
		add_action( 'wp_login_failed', array( $this, 'jp4wc_login_attempt_limiter_failed' ) );
	}

	/**
	 * Prevents user enumeration by redirecting requests with author query parameters.
	 *
	 * @return void
	 */
	public function jp4wc_hide_author_id() {
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			$query_string = sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
			if ( preg_match( '/author=\d+/', $query_string ) ) {
				wp_safe_redirect( home_url(), 301 );
				exit;
			}
		}
	}

	/**
	 * Authenticates a user login attempt by checking for too many failed attempts.
	 *
	 * @param WP_User|WP_Error|null $user     WP_User or WP_Error object from a previous callback. Default null.
	 * @param string                $username Username for authentication.
	 * @return WP_User|WP_Error WP_User on success, WP_Error on failure.
	 */
	public function jp4wc_login_attempt_limiter_authenticate( $user, $username ) {
		if ( empty( $username ) ) {
			return $user;
		}
		// Get the number of attempts using the user name as the key.
		$transient_key = 'failed_login_attempts_' . sanitize_user( $username );
		$attempts      = get_transient( $transient_key );
		if ( false !== $attempts && $attempts >= WP_LOGIN_USER_ATTEMPT_LIMIT ) {
			return new WP_Error( 'too_many_attempts', __( '<strong>ERROR</strong>: This user has reached the maximum number of login attempts. Please try again later.' ) );
		}
		return $user;
	}
}
