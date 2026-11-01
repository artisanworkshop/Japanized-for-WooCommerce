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
	 * REMOTE_ADDR before the test (the test library sets 127.0.0.1).
	 *
	 * @var string|null
	 */
	private $remote_addr;

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

		$this->user             = self::factory()->user->create_and_get(
			array(
				'user_login' => 'limited',
				'user_pass'  => 'right-password',
				'user_email' => 'limited@example.com',
			)
		);
		$this->names            = array( 'limited', 'limited@example.com', 'nobody' );
		$this->remote_addr      = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Kept as it is, to put back.
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
		if ( null === $this->remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->remote_addr;
		}
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
	 * Logs in from a client address.
	 *
	 * @param string $address  REMOTE_ADDR.
	 * @param string $username User name or e-mail address.
	 * @param string $password Password.
	 * @return WP_User|WP_Error
	 */
	private function attempt_from( $address, $username, $password = 'wrong-password' ) {
		$_SERVER['REMOTE_ADDR'] = $address;
		return $this->attempt( $username, $password );
	}

	/**
	 * Fails a login once from each address, each time with another name (nobody1, nobody2, ...).
	 *
	 * Another name each time: a name reaches its own limit of 3 first otherwise.
	 *
	 * @param string[] $addresses REMOTE_ADDR values.
	 */
	private function fail_from_each( array $addresses ) {
		foreach ( array_values( $addresses ) as $i => $address ) {
			$this->attempt_from( $address, 'nobody' . ( $i + 1 ) );
		}
	}

	/**
	 * Logs in as wp-login.php and the WooCommerce forms do (fires wp_login on success).
	 *
	 * @param string $username User name or e-mail address.
	 * @param string $password Password.
	 * @return WP_User|WP_Error
	 */
	private function sign_on( $username, $password ) {
		return wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => false,
			),
			false
		);
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
		$this->assertSame( 10, has_action( 'wp_login', array( 'JP4WC_Login_Limiter', 'clear' ) ) );
		$this->assertFalse( has_action( 'application_password_failed_authentication', array( 'JP4WC_Login_Limiter', 'record_app_password_failure' ) ), 'Fires for every REST request with Basic auth, not only logins (R1-1).' );
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
	 * Logging in forgets the failures of that name, but not of the address.
	 */
	public function test_a_login_clears_the_name_but_not_the_address() {
		update_option( 'wc4jp-login-limit-ip-attempts', '6' );
		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->attempt( 'nobody' );
		$this->assertInstanceOf( 'WP_User', $this->sign_on( 'limited', 'right-password' ) );

		$this->attempt( 'limited' );
		$this->attempt( 'limited' );
		$this->assertSame( 'incorrect_password', $this->code( $this->attempt( 'limited' ) ), 'The name count started over: the third failure after the login is not refused.' );
		// The address has 3 + 3 = 6 failures, its limit: logging in did not reset it (R1-3).
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'other-name', 'x' ) ) );
	}

	/**
	 * An e-mail address counts against the same name as the login, in any case.
	 */
	public function test_email_and_login_are_counted_together() {
		$this->attempt( 'Limited@Example.com' );
		$this->attempt( 'LIMITED' );
		$this->attempt( ' limited@example.com ' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited', 'right-password' ) ), 'Two e-mail failures and one login failure lock the one name (R1-4).' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited@example.com', 'right-password' ) ) );

		$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
		$this->attempt( 'unknown@example.com' );
		$this->attempt( 'unknown@example.com' );
		$this->attempt( 'unknown@example.com' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'unknown@example.com' ) ), 'An address that is no user is counted as itself.' );
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
	 * A REST request with Basic auth that is no login is not counted (R1-1).
	 */
	public function test_basic_auth_rest_requests_are_not_counted() {
		for ( $i = 0; $i < 6; $i++ ) {
			do_action( 'application_password_failed_authentication', new WP_Error( 'invalid_username', 'x' ) );
		}
		$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ) );
	}

	/**
	 * Parallel failures are all counted: each one is a row of its own (R1-5).
	 */
	public function test_each_failure_is_a_row() {
		global $wpdb;
		$this->attempt( 'limited' );
		$this->attempt( 'nobody' );
		$rows = $wpdb->get_results( "SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE 'jp4wc_login_fail_%' ORDER BY option_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertCount( 4, $rows, 'Two for the address, one per name.' );
		foreach ( $rows as $row ) {
			$this->assertSame( 'off', $row->autoload );
			$this->assertGreaterThan( time() + 14 * MINUTE_IN_SECONDS, (int) $row->option_value );
			$this->assertStringNotContainsString( 'limited', $row->option_name, 'The name is hashed.' );
		}
		$this->assertArrayNotHasKey( $rows[0]->option_name, (array) wp_load_alloptions(), 'Not autoloaded.' );
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
	 * The addresses of one IPv6 /64 network count together; the next /64 counts on its own (#314).
	 */
	public function test_ipv6_is_counted_per_64() {
		$this->fail_from_each( array( '2001:db8:1:2::1', '2001:db8:1:2::2', '2001:DB8:1:2:0:0:0:3', '2001:db8:1:2:ffff:ffff:ffff:ffff' ) );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '2001:db8:1:2:abcd::9', 'limited', 'right-password' ) ), 'Four addresses of one /64, written in any form, reach the address limit of 4.' );
		$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db8:1:3::1', 'limited', 'right-password' ), 'The next /64 is another client.' );
	}

	/**
	 * An IPv4-mapped address counts as its IPv4 address, not together with every IPv4 client in ::/64 (#314).
	 */
	public function test_ipv4_mapped_is_counted_as_ipv4() {
		$this->fail_from_each( array( '::ffff:203.0.113.7', '::ffff:203.0.113.7', '203.0.113.7', '203.0.113.7' ) );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '203.0.113.7', 'limited', 'right-password' ) ) );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '::ffff:203.0.113.7', 'limited', 'right-password' ) ) );
		$this->assertInstanceOf( 'WP_User', $this->attempt_from( '::ffff:198.51.100.9', 'limited', 'right-password' ), 'Another IPv4 client is not in the same count.' );
	}

	/**
	 * IPv4 addresses are not grouped, and keep the key they had before 3.0.2 (#314).
	 */
	public function test_ipv4_is_counted_per_address() {
		global $wpdb;
		$this->fail_from_each( array_fill( 0, 4, '203.0.113.7' ) );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '203.0.113.7', 'limited', 'right-password' ) ) );
		$this->assertInstanceOf( 'WP_User', $this->attempt_from( '203.0.113.8', 'limited', 'right-password' ), 'The next address is another client.' );
		$rows = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'jp4wc_login_fail_ip_' . md5( '203.0.113.7' ) . '_' ) . '%'
			)
		);
		$this->assertSame( 4, $rows, 'Counted by the address itself, as before: the counts carry over the update.' );
	}

	/**
	 * A value from the filter that is no IP address counts as it is (#314).
	 */
	public function test_non_ip_value_is_counted_as_is() {
		$value  = 'proxy-a';
		$filter = static function () use ( &$value ) {
			return $value;
		};
		add_filter( 'jp4wc_login_limit_client_ip', $filter );
		try {
			$this->fail_from_each( array_fill( 0, 4, 'ignored' ) );
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited', 'right-password' ) ) );
			$value = 'proxy-b';
			$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ), 'Another value is another client.' );
			$value = 'fe80::1%eth0';
			$this->fail_from_each( array_fill( 0, 4, 'ignored' ) );
			$value = 'fe80::2%eth0';
			$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ), 'An address with a zone is no IP address to filter_var(): not grouped.' );
		} finally {
			remove_filter( 'jp4wc_login_limit_client_ip', $filter );
		}
	}

	/**
	 * The filter sets the length of the IPv6 network that counts as one (#314).
	 */
	public function test_ipv6_prefix_filter() {
		$bits   = 128;
		$length = static function () use ( &$bits ) {
			return $bits;
		};
		add_filter( 'jp4wc_login_limit_ipv6_prefix', $length );
		try {
			$this->fail_from_each( array_fill( 0, 4, '2001:db8:1:2::1' ) );
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '2001:db8:1:2::1', 'limited', 'right-password' ) ) );
			$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db8:1:2::2', 'limited', 'right-password' ), '128: each address on its own.' );

			JP4WC_Login_Limiter::delete_counts();
			$bits = 60;
			$this->fail_from_each( array( '2001:db8:1:20::1', '2001:db8:1:2a::1', '2001:db8:1:2f:ffff::1', '2001:db8:1:21::1' ) );
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '2001:db8:1:25::9', 'limited', 'right-password' ) ), '60: 2001:db8:1:20:: to 2001:db8:1:2f:ffff:... count as one.' );
			$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db8:1:30::1', 'limited', 'right-password' ) );
			$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db8:1:1f::1', 'limited', 'right-password' ) );
		} finally {
			remove_filter( 'jp4wc_login_limit_ipv6_prefix', $length );
		}
	}

	/**
	 * The IPv6 network length is kept within 32 to 128, and is 64 for a value that is not a number (#314).
	 */
	public function test_ipv6_prefix_is_kept_within_its_range() {
		$bits   = 16;
		$length = static function () use ( &$bits ) {
			return $bits;
		};
		add_filter( 'jp4wc_login_limit_ipv6_prefix', $length );
		try {
			$this->fail_from_each( array( '2001:db8:1::1', '2001:db8:2::1', '2001:db8:ffff::1', '2001:db8:abcd::1' ) );
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '2001:db8:9::9', 'limited', 'right-password' ) ), '16 is taken as 32.' );
			$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db9::1', 'limited', 'right-password' ), 'Not as 16.' );

			JP4WC_Login_Limiter::delete_counts();
			$bits = 200;
			$this->fail_from_each( array_fill( 0, 4, '2001:db8:1:2::1' ) );
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '2001:db8:1:2::1', 'limited', 'right-password' ) ) );
			$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db8:1:2::2', 'limited', 'right-password' ), '200 is taken as 128.' );

			JP4WC_Login_Limiter::delete_counts();
			$bits = 'wide';
			$this->fail_from_each( array( '2001:db8:1:2::1', '2001:db8:1:2::2', '2001:db8:1:2::3', '2001:db8:1:2::4' ) );
			$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt_from( '2001:db8:1:2::5', 'limited', 'right-password' ) ), 'Not a number: 64.' );
			$this->assertInstanceOf( 'WP_User', $this->attempt_from( '2001:db8:1:3::1', 'limited', 'right-password' ) );
		} finally {
			remove_filter( 'jp4wc_login_limit_ipv6_prefix', $length );
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
		delete_option( 'wc4jp-login-limit-lockout-minutes' );
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

		// One failure expires: the count drops below the limit of 3 and the lock ends.
		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->options} SET option_value = '0000000001' WHERE option_name LIKE 'jp4wc_login_fail_user_%' ORDER BY option_name LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertInstanceOf( 'WP_User', $this->sign_on( 'limited', 'right-password' ) );
		$this->assertSame( 0, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'jp4wc_login_fail_user_%'" ), 'The user logged in: that name has no rows left, expired or not.' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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
		$use    = static function () use ( $logger ) {
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
	 * Expired failures of the same key are removed on the next failure, of every key once a day (R2-1, G1-2).
	 */
	public function test_expired_failures_are_removed() {
		global $wpdb;
		$this->attempt( 'nobody' );
		$wpdb->query( "UPDATE {$wpdb->options} SET option_value = '0000000001' WHERE option_name LIKE 'jp4wc_login_fail_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$_SERVER['REMOTE_ADDR'] = '198.51.100.9';
		$this->attempt( 'limited' );
		$this->assertSame( 2, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'jp4wc_login_fail_%' AND option_value = '0000000001'" ), 'Other keys are not swept on a failure (the login path stays cheap).' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->attempt( 'nobody' );
		$this->assertSame( 1, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'jp4wc_login_fail_%' AND option_value = '0000000001'" ), 'The expired failure of the name that failed again is gone; the other address keeps its expired row.' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$wpdb->query( "UPDATE {$wpdb->options} SET option_value = '0000000001' WHERE option_name LIKE 'jp4wc_login_fail_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertSame( 10, has_action( 'delete_expired_transients', array( 'JP4WC_Login_Limiter', 'delete_expired' ) ) );
		JP4WC_Login_Limiter::delete_expired();
		$this->assertSame( 0, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'jp4wc_login_fail_%'" ), 'The daily sweep removes them without a failure.' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * A login name in the form of an e-mail address is counted as itself, not as the user whose e-mail it is (R2-3).
	 */
	public function test_login_name_shaped_like_an_email_is_itself() {
		self::factory()->user->create(
			array(
				'user_login' => 'limited@example.com',
				'user_pass'  => 'other-password',
				'user_email' => 'other@example.com',
			)
		);
		$this->attempt( 'limited@example.com' );
		$this->attempt( 'limited@example.com' );
		$this->attempt( 'limited@example.com' );
		$this->assertSame( 'jp4wc_login_locked', $this->code( $this->attempt( 'limited@example.com', 'other-password' ) ) );
		$this->assertInstanceOf( 'WP_User', $this->attempt( 'limited', 'right-password' ), 'The user whose e-mail that is was not counted.' );
	}

	/**
	 * Removing the counts (delete_counts) and the settings too (uninstall).
	 */
	public function test_uninstall_removes_counts_and_settings() {
		global $wpdb;
		$this->attempt( 'limited' );
		$this->attempt( 'nobody' );
		// Two for the address, one per name.
		$this->assertSame( 4, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'jp4wc_login_fail_%'" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		JP4WC_Login_Limiter::uninstall();
		$this->assertSame( 0, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '%jp4wc_login_fail_%'" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->assertFalse( get_option( 'wc4jp-login-limit' ) );
		$this->assertFalse( get_option( 'wc4jp-login-limit-ip-attempts' ) );
		$this->assertFalse( get_option( 'wc4jp-login-limit-lockout-minutes' ) );
	}
}
