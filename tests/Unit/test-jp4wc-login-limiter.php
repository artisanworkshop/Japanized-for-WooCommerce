<?php
/**
 * Tests for the login attempt limiter (issue #289).
 *
 * Logins go through wp_authenticate() as every real login does (wp-login.php,
 * the WooCommerce login forms through wp_signon(), XML-RPC), so the filter
 * priorities and the wp_login_failed count are exercised, not called directly.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Login_Limiter_Test
 */
class JP4WC_Login_Limiter_Test extends WP_UnitTestCase {

	/**
	 * A user to log in as.
	 *
	 * @var WP_User
	 */
	private $user;

	/**
	 * Login names counted in this test, for the clean-up.
	 *
	 * @var string[]
	 */
	private $names = array();

	/**
	 * Set up: the limiter on with small limits, a user, a client address.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'JP4WC_Login_Limiter' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-login-limiter.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Login_Limiter' ) );
		$this->assertTrue( class_exists( 'JP4WC_Security_Checks' ) );

		$this->user = self::factory()->user->create_and_get(
			array(
				'user_login' => 'limited',
				'user_pass'  => 'right-password',
				'user_email' => 'limited@example.com',
			)
		);
		$this->names            = array( 'limited', 'limited@example.com', 'nobody' );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		update_option( 'wc4jp-login-limit', '1' );
		update_option( 'wc4jp-login-limit-ip-attempts', '4' );
		update_option( 'wc4jp-login-limit-user-attempts', '3' );
		update_option( 'wc4jp-login-limit-lockout-minutes', '15' );
		update_option( 'active_plugins', array( 'woocommerce/woocommerce.php' ) );
		JP4WC_Login_Limiter::delete_counts();
		wp_set_current_user( 0 );
	}

	/**
	 * Clean up.
	 */
	public function tearDown(): void {
		JP4WC_Login_Limiter::delete_counts();
		delete_option( 'wc4jp-login-limit' );
		delete_option( 'wc4jp-login-limit-ip-attempts' );
		delete_option( 'wc4jp-login-limit-user-attempts' );
		delete_option( 'wc4jp-login-limit-lockout-minutes' );
		unset( $_SERVER['REMOTE_ADDR'] );
		parent::tearDown();
	}

	/**
	 * Fails a login, as wp-login.php and the WooCommerce forms do.
	 *
	 * @param string $username User name or e-mail address.
	 * @param string $password Password.
	 * @return WP_User|WP_Error
	 */
	private function attempt( $username, $password = 'wrong-password' ) {
		return wp_authenticate( $username, $password );
	}

	/**
	 * The error code of a login result.
	 *
	 * @param WP_User|WP_Error $result Result.
	 * @return string
	 */
	private function code( $result ) {
		return is_wp_error( $result ) ? $result->get_error_code() : 'ok';
	}

	/**
	 * The hooks are registered when the file is loaded.
	 */
	public function test_hooks_are_registered() {
		$this->assertSame( 50, has_filter( 'authenticate', array( 'JP4WC_Login_Limiter', 'authenticate' ) ) );
		$this->assertSame( 10, has_action( 'wp_login_failed', array( 'JP4WC_Login_Limiter', 'record_failure' ) ) );
		$this->assertSame( 10, has_action( 'application_password_failed_authentication', array( 'JP4WC_Login_Limiter', 'record_app_password_failure' ) ) );
		$this->assertSame( 10, has_action( 'wp_login', array( 'JP4WC_Login_Limiter', 'clear' ) ) );
	}

	/**
	 * The user name is locked at its limit, and stays locked for the right password too.
	 */
	public function test_user_name_is_locked_at_its_limit() {
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ) );
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ) );
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ), 'The failure that reaches the limit still shows the real error.' );

		$result = $this->attempt( 'limited', 'right-password' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $result ), 'A right password is refused while locked (the filter runs after the password check).' );
		$this->assertStringContainsString( '15 minutes', $result->get_error_message() );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited' ) ) );
	}

	/**
	 * The address is locked at its limit, across user names.
	 */
	public function test_address_is_locked_at_its_limit() {
		// Different names: a name reaches its own limit of 3 first otherwise.
		$this->assertSame( 'invalid_username', $this->code( $this->attempt( 'nobody1' ) ) );
		$this->attempt( 'nobody2' );
		$this->attempt( 'nobody3' );
		$this->attempt( 'nobody4' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited', 'right-password' ) ), 'Another user name from the same address is locked.' );

		$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
		$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ), 'Another address is not.' );
	}

	/**
	 * A refused login is not counted as another failure.
	 */
	public function test_a_refused_login_is_not_counted() {
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited' ) ) );
		// 3 real failures for the name and the address: the refusals did not bring the address to its limit of 4.
		$this->assertSame( 'invalid_username', $this->code( $this->attempt( 'nobody' ) ), 'The refused attempts did not count towards the address.' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'nobody2' ) ), 'That one did: the address is at its limit now.' );
	}

	/**
	 * Logging in forgets the counts of the address and the name.
	 */
	public function test_a_login_clears_the_counts() {
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->attempt( 'nobody' );
		$this->assertInstanceOf( 'WP_User', wp_signon(
			array(
				'user_login'    => 'limited',
				'user_password' => 'right-password',
			)
		) );

		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ), 'The count started over.' );
	}

	/**
	 * An e-mail address counts against the same name as the login, in any case.
	 */
	public function test_email_and_login_are_counted_together() {
		$this->attempt( 'Limited@Example.com' );
		$this->attempt( 'limited@example.com' );
		$this->attempt( 'limited@example.com' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited@example.com', 'right-password' ) ) );
	}

	/**
	 * The WooCommerce login form (wp_signon) is covered.
	 */
	public function test_woocommerce_login_form_is_covered() {
		$creds = array(
			'user_login'    => 'limited',
			'user_password' => 'wrong-password',
			'remember'      => false,
		);
		$this->assertSame( 'incorrect_password', $this->code( wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), false ) ) );
		wp_signon( $creds, false );
		wp_signon( $creds, false );
		$creds['user_password'] = 'right-password';
		$this->assertSame( 'jp4wc_login_locked', $this->code( wp_signon( $creds, false ) ) );
	}

	/**
	 * A failed application password counts against the address only.
	 */
	public function test_application_password_failure_counts_against_the_address() {
		do_action( 'application_password_failed_authentication', new WP_Error( 'invalid_password', 'x' ) );
		do_action( 'application_password_failed_authentication', new WP_Error( 'invalid_password', 'x' ) );
		do_action( 'application_password_failed_authentication', new WP_Error( 'invalid_password', 'x' ) );
		do_action( 'application_password_failed_authentication', new WP_Error( 'invalid_password', 'x' ) );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited', 'right-password' ) ) );

		$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
		$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ), 'No user name was counted.' );
	}

	/**
	 * Off, the limiter neither counts nor refuses.
	 */
	public function test_off_does_nothing() {
		update_option( 'wc4jp-login-limit', '' );
		for ( $i = 0; $i < 6; $i++ ) {
			$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ) );
		}
		$this->assertFalse( JP4WC_Login_Limiter::is_active() );

		update_option( 'wc4jp-login-limit', '1' );
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ), 'Nothing was counted while off.' );
	}

	/**
	 * With a known login protection plugin active, the limiter sleeps.
	 */
	public function test_sleeps_with_a_known_plugin() {
		update_option( 'active_plugins', array( 'woocommerce/woocommerce.php', 'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php' ) );
		$this->assertSame(
			array(
				'type' => 'plugin',
				'name' => 'limit-login-attempts-reloaded',
			),
			JP4WC_Login_Limiter::protected_by()
		);
		$this->assertFalse( JP4WC_Login_Limiter::is_active() );
		$this->assertTrue( JP4WC_Login_Limiter::is_enabled(), 'The setting itself stays on.' );
		for ( $i = 0; $i < 6; $i++ ) {
			$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ) );
		}
	}

	/**
	 * The filter turns the limiter off for a plugin the check does not know.
	 */
	public function test_filter_turns_it_off() {
		add_filter( 'jp4wc_login_limit_enabled', '__return_false' );
		try {
			for ( $i = 0; $i < 6; $i++ ) {
				$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ) );
			}
		} finally {
			remove_filter( 'jp4wc_login_limit_enabled', '__return_false' );
		}
	}

	/**
	 * While not locked, a refusal by an earlier filter is returned as it is; while locked, the lock answers for every password.
	 */
	public function test_locked_answer_is_the_same_for_every_password() {
		$other = static function () {
			return new WP_Error( 'other_plugin', 'Blocked elsewhere.' );
		};
		add_filter( 'authenticate', $other, 40 );
		try {
			$this->assertSame( 'other_plugin', $this->code( $this->attempt( 'limited', 'right-password' ) ), 'Not locked: the other refusal is kept.' );
		} finally {
			remove_filter( 'authenticate', $other, 40 );
		}

		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited' ) ), 'The other refusal counted; now locked.' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited', 'right-password' ) ) );
		add_filter( 'authenticate', $other, 40 );
		try {
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited', 'right-password' ) ), 'Locked: the lock answers, not the other refusal.' );
		} finally {
			remove_filter( 'authenticate', $other, 40 );
		}
	}

	/**
	 * An empty user name is neither counted nor refused.
	 */
	public function test_empty_user_name_is_ignored() {
		for ( $i = 0; $i < 6; $i++ ) {
			$this->assertSame( 'empty_username', $this->code( $this->attempt( '' ) ) );
		}
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ), 'The address was not counted.' );
	}

	/**
	 * The client address can be replaced, and an empty one turns the address count off.
	 */
	public function test_client_ip_filter() {
		$proxy = static function () {
			return '192.0.2.1';
		};
		add_filter( 'jp4wc_login_limit_client_ip', $proxy );
		try {
			$this->assertSame( '192.0.2.1', JP4WC_Login_Limiter::client_ip() );
		} finally {
			remove_filter( 'jp4wc_login_limit_client_ip', $proxy );
		}
		$this->assertSame( '203.0.113.7', JP4WC_Login_Limiter::client_ip() );

		add_filter( 'jp4wc_login_limit_client_ip', '__return_empty_string' );
		try {
			for ( $i = 0; $i < 5; $i++ ) {
				$this->attempt( 'nobody' );
			}
			$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ), 'Only the names were counted.' );
		} finally {
			remove_filter( 'jp4wc_login_limit_client_ip', '__return_empty_string' );
		}
	}

	/**
	 * The limits come from the settings, within their ranges.
	 */
	public function test_limits_are_read_within_their_ranges() {
		$this->assertSame( 4, JP4WC_Login_Limiter::limit( 'wc4jp-login-limit-ip-attempts' ) );
		update_option( 'wc4jp-login-limit-ip-attempts', '0' );
		$this->assertSame( 1, JP4WC_Login_Limiter::limit( 'wc4jp-login-limit-ip-attempts' ) );
		update_option( 'wc4jp-login-limit-ip-attempts', '500' );
		$this->assertSame( 100, JP4WC_Login_Limiter::limit( 'wc4jp-login-limit-ip-attempts' ) );
		update_option( 'wc4jp-login-limit-ip-attempts', 'many' );
		$this->assertSame( 10, JP4WC_Login_Limiter::limit( 'wc4jp-login-limit-ip-attempts' ), 'Not a number: the default.' );
		delete_option( 'wc4jc-login-limit-lockout-minutes' );
		$this->assertSame( 15, JP4WC_Login_Limiter::limit( 'wc4jp-login-limit-lockout-minutes' ) );
		$this->assertSame( 0, JP4WC_Login_Limiter::limit( 'wc4jp-no-such-limit' ) );
	}

	/**
	 * The lockout message names the minutes left, the counts expire with the lockout.
	 */
	public function test_lockout_expires() {
		update_option( 'wc4jp-login-limit-lockout-minutes', '1' );
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$result = $this->attempt( 'limited', 'right-password' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $result ) );
		$this->assertStringContainsString( '1 minute.', $result->get_error_message() );

		// The transients expire: WordPress deletes an expired transient when it is read.
		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->options} SET option_value = '1' WHERE option_name LIKE '_transient_timeout_jp4wc_login_fail_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_flush();
		$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ) );
	}

	/**
	 * A lockout is logged without the address or the name.
	 */
	public function test_lockout_is_logged() {
		$logger = new class() extends WC_Logger {
			/**
			 * Recorded entries.
			 *
			 * @var array
			 */
			public $entries = array();

			/**
			 * Records instead of writing.
			 *
			 * @param string $level   Level.
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function log( $level, $message, $context = array() ) {
				$this->entries[] = array( $level, $message, $context );
			}
		};
		$use = static function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'woocommerce_logging_class', $use );
		try {
			$this->attempt( 'limited' );
			$this->attempt( 'limited' );
			$this->assertSame( array(), $logger->entries );
			$this->attempt( 'limited' );
		} finally {
			remove_filter( 'woocommerce_logging_class', $use );
			// wc_get_logger() keeps the instance: hand it a fresh WC_Logger once.
			$fresh = static function () {
				return new WC_Logger();
			};
			add_filter( 'woocommerce_logging_class', $fresh );
			wc_get_logger();
			remove_filter( 'woocommerce_logging_class', $fresh );
		}
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( 'warning', $logger->entries[0][0] );
		$this->assertSame( 'jp4wc-login-limit', $logger->entries[0][2]['source'] );
		$this->assertStringNotContainsString( 'limited', $logger->entries[0][1] );
		$this->assertStringNotContainsString( '203.0.113.7', $logger->entries[0][1] );
	}

	/**
	 * delete_counts() removes every count, uninstall() the settings too.
	 */
	public function test_uninstall_removes_counts_and_settings() {
		global $wpdb;
		$this->attempt( 'limited' );
		$this->attempt( 'nobody' );
		// The address, and the two names.
		$this->assertSame( 3, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_jp4wc_login_fail_%'" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		JP4WC_Login_Limiter::uninstall();
		$this->assertSame( 0, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%jp4wc_login_fail_%'" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertFalse( get_option( 'wc4jp-login-limit' ) );
		$this->assertFalse( get_option( 'wc4jp-login-limit-ip-attempts' ) );
		$this->assertFalse( get_option( 'wc4jp-login-limit-lockout-minutes' ) );
	}
}
