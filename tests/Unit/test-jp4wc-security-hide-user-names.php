<?php
/**
 * Tests for the setting that keeps user names from visitors (issue #318).
 *
 * Each place a user name shows is tested off, on for a visitor and on for a
 * logged-in user. Author archives go through WP::main() (go_to()), logins
 * through wp_signon(), password resets through retrieve_password() and
 * WooCommerce's own form handler, so the hooks are exercised where
 * WordPress and WooCommerce apply them.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Hide_User_Names_Test
 */
class JP4WC_Security_Hide_User_Names_Test extends WP_UnitTestCase {

	/**
	 * An administrator with published content.
	 *
	 * @var WP_User
	 */
	private $admin;

	/**
	 * A post of the administrator.
	 *
	 * @var int
	 */
	private $post;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'JP4WC_Security_Hardening' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-security-hardening.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Security_Hardening' ) );
		$this->admin = self::factory()->user->create_and_get(
			array(
				'role'          => 'administrator',
				'user_login'    => 'shopowner',
				'user_nicename' => 'shop-owner',
				'user_email'    => 'owner@example.org',
				'user_pass'     => 'right-password',
				'display_name'  => 'shopowner',
			)
		);
		$this->post  = self::factory()->post->create(
			array(
				'post_author' => $this->admin->ID,
				'post_status' => 'publish',
			)
		);
		$this->set_permalink_structure( '/%postname%/' );
		wp_set_current_user( 0 );
		delete_option( 'wc4jp-hide-user-names' );
	}

	/**
	 * Clean up.
	 */
	public function tearDown(): void {
		delete_option( 'wc4jp-hide-user-names' );
		$this->set_permalink_structure( '' );
		wp_set_current_user( 0 );
		unset( $GLOBALS['pagenow'], $_POST['wc_reset_password'], $_POST['user_login'], $_REQUEST['redirect_to'], $_REQUEST['woocommerce-lost-password-nonce'] );
		parent::tearDown();
	}

	/**
	 * Turns the setting on.
	 */
	private function turn_on() {
		update_option( 'wc4jp-hide-user-names', '1' );
	}

	/**
	 * Logs a subscriber in.
	 */
	private function log_in() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	/**
	 * Calls a function and returns where it redirected, or null when it returned.
	 *
	 * The wp_redirect filter throws, so exit is never reached. The hooks the
	 * exception left are taken off the stack of running hooks again, as the
	 * next request would start without them.
	 *
	 * @param callable $callback Function.
	 * @return string|null
	 * @throws RuntimeException An exception the function threw for another reason.
	 */
	private function redirect_of( callable $callback ) {
		global $wp_current_filter;
		$running = $wp_current_filter;
		$throw   = static function ( $location ) {
			throw new RuntimeException( 'redirect:' . $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The test's own marker, never printed.
		};
		add_filter( 'wp_redirect', $throw );
		try {
			$callback();
			return null;
		} catch ( RuntimeException $e ) {
			if ( 0 !== strpos( $e->getMessage(), 'redirect:' ) ) {
				throw $e;
			}
			return substr( $e->getMessage(), strlen( 'redirect:' ) );
		} finally {
			remove_filter( 'wp_redirect', $throw );
			$wp_current_filter = $running; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Undoes what the exception left.
		}
	}

	/**
	 * The hooks are registered when the file is loaded.
	 */
	public function test_hooks_are_registered() {
		$this->assertSame( 10, has_filter( 'request', array( 'JP4WC_Security_Hardening', 'close_author_archives' ) ) );
		$this->assertSame( 10, has_filter( 'author_link', array( 'JP4WC_Security_Hardening', 'author_link' ) ) );
		$this->assertSame( 10, has_filter( 'wp_sitemaps_add_provider', array( 'JP4WC_Security_Hardening', 'sitemap_provider' ) ) );
		$this->assertSame( 10, has_filter( 'comment_class', array( 'JP4WC_Security_Hardening', 'comment_class' ) ) );
		$this->assertSame( 10, has_filter( 'oembed_response_data', array( 'JP4WC_Security_Hardening', 'oembed_author' ) ) );
		$this->assertSame( 100, has_filter( 'authenticate', array( 'JP4WC_Security_Hardening', 'same_login_error' ) ) );
		$this->assertSame( PHP_INT_MAX, has_action( 'lostpassword_post', array( 'JP4WC_Security_Hardening', 'unknown_user_reset' ) ) );
		$this->assertSame( 100, has_filter( 'rest_authentication_errors', array( 'JP4WC_Security_Hardening', 'same_rest_auth_error' ) ) );
	}

	/**
	 * Off (the default), ?author=N redirects to the archive with the name, which answers.
	 */
	public function test_off_leaves_author_archives() {
		$this->assertFalse( JP4WC_Security_Hardening::hides_user_names() );
		$url = home_url( '/?author=' . $this->admin->ID );
		$this->go_to( $url );
		$this->assertTrue( is_author() );
		$this->assertStringContainsString( '/author/shop-owner/', (string) redirect_canonical( $url, false ) );

		$this->go_to( home_url( '/author/shop-owner/' ) );
		$this->assertTrue( is_author() );
		$this->assertStringContainsString( '/author/shop-owner/', get_author_posts_url( $this->admin->ID ) );
	}

	/**
	 * On, a visitor gets the 404 page for ?author=N, the archive by name and its feed, without a redirect.
	 */
	public function test_on_closes_author_archives_for_visitors() {
		$this->turn_on();
		$url = home_url( '/?author=' . $this->admin->ID );
		$this->go_to( $url );
		$this->assertTrue( is_404() );
		$this->assertFalse( is_author() );
		$this->assertStringNotContainsString( 'shop-owner', (string) redirect_canonical( $url, false ), 'No redirect to the name.' );

		foreach ( array( '/author/shop-owner/', '/author/shop-owner/feed/', '/?author_name=shop-owner', '/?author=' . $this->admin->ID . '&feed=rss2' ) as $path ) {
			$this->go_to( home_url( $path ) );
			$this->assertTrue( is_404(), $path );
			$this->assertFalse( is_author(), $path );
			$this->assertFalse( is_feed(), $path );
		}

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->go_to( home_url( '/?author=' . $editor ) );
		$this->assertTrue( is_404(), 'A user without posts, whose archive WordPress answers with 200, is closed too.' );

		$this->go_to( get_permalink( $this->post ) );
		$this->assertTrue( is_single(), 'Other pages are left alone.' );
	}

	/**
	 * On, a logged-in user keeps the archives and the redirect.
	 */
	public function test_on_keeps_author_archives_for_logged_in_users() {
		$this->turn_on();
		$this->log_in();
		$url = home_url( '/?author=' . $this->admin->ID );
		$this->go_to( $url );
		$this->assertTrue( is_author() );
		$this->assertStringContainsString( '/author/shop-owner/', (string) redirect_canonical( $url, false ) );
		$this->assertStringContainsString( '/author/shop-owner/', get_author_posts_url( $this->admin->ID ) );
	}

	/**
	 * The request filter leaves REST requests and other queries alone.
	 */
	public function test_request_filter_leaves_other_queries() {
		$this->turn_on();
		$rest = array(
			'rest_route' => '/wp/v2/posts',
			'author'     => '1',
		);
		$this->assertSame( $rest, JP4WC_Security_Hardening::close_author_archives( $rest ) );
		$this->assertSame( array( 'p' => '5' ), JP4WC_Security_Hardening::close_author_archives( array( 'p' => '5' ) ) );
		$this->assertSame( array( 'author' => '0' ), JP4WC_Security_Hardening::close_author_archives( array( 'author' => '0' ) ), 'Not an author.' );
		$this->assertSame( 'x', JP4WC_Security_Hardening::close_author_archives( 'x' ) );
		$this->assertSame( array( 'error' => '404' ), JP4WC_Security_Hardening::close_author_archives( array( 'author' => array( '1' ) ) ) );
	}

	/**
	 * On, author links lead a visitor to the home page.
	 */
	public function test_author_links() {
		$this->turn_on();
		$this->assertSame( home_url( '/' ), get_author_posts_url( $this->admin->ID ) );

		$GLOBALS['post'] = get_post( $this->post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The loop of a theme.
		setup_postdata( $GLOBALS['post'] );
		try {
			$link = get_the_author_posts_link();
		} finally {
			wp_reset_postdata();
		}
		$this->assertStringContainsString( 'href="' . home_url( '/' ) . '"', $link );
		$this->assertStringNotContainsString( 'shop-owner', $link );
	}

	/**
	 * On, the users sitemap is left out for a visitor only.
	 */
	public function test_users_sitemap() {
		$registry = new WP_Sitemaps_Registry();
		$this->assertTrue( $registry->add_provider( 'users', new WP_Sitemaps_Users() ), 'Off: added.' );

		$this->turn_on();
		$registry = new WP_Sitemaps_Registry();
		$this->assertFalse( $registry->add_provider( 'users', new WP_Sitemaps_Users() ) );
		$this->assertTrue( $registry->add_provider( 'posts', new WP_Sitemaps_Posts() ), 'Other sitemaps stay.' );

		$this->log_in();
		$registry = new WP_Sitemaps_Registry();
		$this->assertTrue( $registry->add_provider( 'users', new WP_Sitemaps_Users() ) );
	}

	/**
	 * On, the comment-author-<nicename> class is left out for a visitor.
	 */
	public function test_comment_class() {
		$comment = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->post,
				'user_id'         => $this->admin->ID,
			)
		);
		$this->assertContains( 'comment-author-shop-owner', get_comment_class( '', $comment ) );

		$this->turn_on();
		$classes = get_comment_class( '', $comment );
		$this->assertSame( array(), preg_grep( '/^comment-author-/', $classes ) );
		$this->assertContains( 'byuser', $classes, 'Other classes stay.' );

		$this->log_in();
		$this->assertContains( 'comment-author-shop-owner', get_comment_class( '', $comment ) );
	}

	/**
	 * On, the oEmbed response gives the site as the author to a visitor.
	 */
	public function test_oembed_author() {
		$data = get_oembed_response_data( $this->post, 600 );
		$this->assertSame( 'shopowner', $data['author_name'] );
		$this->assertStringContainsString( 'shop-owner', $data['author_url'] );

		$this->turn_on();
		$data = get_oembed_response_data( $this->post, 600 );
		$this->assertSame( get_bloginfo( 'name' ), $data['author_name'] );
		$this->assertSame( get_home_url(), $data['author_url'] );

		$this->log_in();
		$this->assertSame( 'shopowner', get_oembed_response_data( $this->post, 600 )['author_name'] );
	}

	/**
	 * Off, the login errors tell an unknown user from a wrong password.
	 */
	public function test_off_login_errors_differ() {
		$this->assertSame( 'invalid_username', $this->login( 'nobody', 'x' )->get_error_code() );
		$this->assertSame( 'invalid_email', $this->login( 'nobody@example.org', 'x' )->get_error_code() );
		$this->assertSame( 'incorrect_password', $this->login( 'shopowner', 'x' )->get_error_code() );
	}

	/**
	 * On, an unknown user name, an unknown e-mail address and a wrong password get the same error.
	 */
	public function test_on_login_errors_are_the_same() {
		$this->turn_on();
		$errors = array(
			$this->login( 'nobody', 'x' ),
			$this->login( 'nobody@example.org', 'x' ),
			$this->login( 'shopowner', 'x' ),
			$this->login( 'owner@example.org', 'x' ),
		);
		foreach ( $errors as $error ) {
			$this->assertWPError( $error );
			$this->assertSame( array( 'incorrect_password' ), $error->get_error_codes(), 'wp-login.php keeps the typed name for this code only.' );
			$this->assertSame( $errors[0]->get_error_message(), $error->get_error_message() );
		}
		$this->assertStringContainsString( 'href="' . esc_url( wp_lostpassword_url() ) . '"', $errors[0]->get_error_message() );
		$this->assertStringNotContainsString( 'nobody', $errors[0]->get_error_message() );
		$this->assertStringNotContainsString( 'shopowner', $errors[0]->get_error_message() );

		$this->assertInstanceOf( 'WP_User', $this->login( 'shopowner', 'right-password' ) );
		$this->assertSame( 'empty_password', $this->login( 'shopowner', '' )->get_error_code(), 'Other errors are left alone.' );
	}

	/**
	 * The login error leaves other errors alone and keeps the codes added next to it.
	 */
	public function test_login_error_keeps_other_errors() {
		$this->turn_on();
		$locked = new WP_Error( 'jp4wc_login_locked', 'Locked.' );
		$this->assertSame( $locked, JP4WC_Security_Hardening::same_login_error( $locked ) );

		$error = new WP_Error( 'invalid_username', 'Unknown.' );
		$error->add( 'captcha_failed', 'Wrong answer.', array( 'status' => 403 ) );
		$result = JP4WC_Security_Hardening::same_login_error( $error );
		$this->assertSame( array( 'incorrect_password', 'captcha_failed' ), $result->get_error_codes() );
		$this->assertSame( 'Wrong answer.', $result->get_error_message( 'captcha_failed' ) );
		$this->assertSame( array( 'status' => 403 ), $result->get_error_data( 'captcha_failed' ) );

		$this->assertNull( JP4WC_Security_Hardening::same_login_error( null ) );
		$this->assertSame( $this->admin, JP4WC_Security_Hardening::same_login_error( $this->admin ) );
	}

	/**
	 * Signs in as WooCommerce's login forms do.
	 *
	 * @param string $login    User name or e-mail address.
	 * @param string $password Password.
	 * @return WP_User|WP_Error
	 */
	private function login( $login, $password ) {
		return wp_signon(
			array(
				'user_login'    => $login,
				'user_password' => $password,
			)
		);
	}

	/**
	 * On, wp-login.php sends a reset request for an unknown user where it sends a sent one.
	 */
	public function test_on_core_reset_of_unknown_user_looks_sent() {
		$GLOBALS['pagenow'] = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- wp-login.php.
		$this->assertWPError( retrieve_password( 'nobody' ), 'Off: WordPress says there is no such account.' );

		$this->turn_on();
		foreach ( array( 'nobody', 'nobody@example.org' ) as $login ) {
			$location = $this->redirect_of(
				static function () use ( $login ) {
					retrieve_password( $login );
				}
			);
			$this->assertNotNull( $location, $login );
			$this->assertStringEndsWith( 'wp-login.php?checkemail=confirm', $location, $login );
		}

		$_REQUEST['redirect_to'] = home_url( '/sent/' );
		$this->assertSame(
			home_url( '/sent/' ),
			$this->redirect_of(
				static function () {
					retrieve_password( 'nobody' );
				}
			)
		);
		unset( $_REQUEST['redirect_to'] );

		$sent = static function () {
			return true;
		};
		add_filter( 'pre_wp_mail', $sent );
		try {
			$result = null;
			$this->assertNull(
				$this->redirect_of(
					function () use ( &$result ) {
						$result = retrieve_password( 'shopowner' );
					}
				),
				'A known user is left to WordPress, which sends the e-mail.'
			);
			$this->assertTrue( $result );
		} finally {
			remove_filter( 'pre_wp_mail', $sent );
		}
	}

	/**
	 * A reset refused for another reason, or requested from elsewhere, is left alone.
	 */
	public function test_on_other_resets_are_left_alone() {
		$this->turn_on();
		$GLOBALS['pagenow'] = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- wp-login.php.

		$this->assertNull(
			$this->redirect_of(
				static function () {
					retrieve_password( ' ' );
				}
			),
			'An empty name is an error of its own.'
		);

		$captcha = static function ( $errors ) {
			$errors->add( 'captcha_failed', 'Wrong answer.' );
		};
		add_action( 'lostpassword_post', $captcha );
		try {
			$this->assertNull(
				$this->redirect_of(
					static function () {
						retrieve_password( 'nobody' );
					}
				),
				'Another plugin refused the request.'
			);
		} finally {
			remove_action( 'lostpassword_post', $captcha );
		}

		$inside = static function () {
			retrieve_password( 'nobody' );
		};
		add_action( 'jp4wc_test_reset_inside_a_hook', $inside );
		try {
			$this->assertNull(
				$this->redirect_of(
					static function () {
						do_action( 'jp4wc_test_reset_inside_a_hook' );
					}
				),
				'Another plugin called retrieve_password() from a hook.'
			);
		} finally {
			remove_action( 'jp4wc_test_reset_inside_a_hook', $inside );
		}

		$GLOBALS['pagenow'] = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Another page.
		$this->assertNull(
			$this->redirect_of(
				static function () {
					retrieve_password( 'nobody' );
				}
			)
		);
	}

	/**
	 * On, WooCommerce's lost password form sends an unknown user where it sends a known one.
	 */
	public function test_on_woocommerce_reset_of_unknown_user_looks_sent() {
		if ( ! class_exists( 'WC_Shortcode_My_Account' ) ) {
			require_once WC_ABSPATH . 'includes/shortcodes/class-wc-shortcode-my-account.php';
		}
		$this->assertTrue( class_exists( 'WC_Shortcode_My_Account' ) );
		$this->turn_on();
		$sent = static function () {
			return true;
		};
		add_filter( 'pre_wp_mail', $sent );

		// WC_Form_Handler::process_lost_password() runs on wp_loaded.
		$GLOBALS['wp_current_filter'][] = 'wp_loaded';
		try {
			$locations = array();
			foreach ( array( 'nobody', 'nobody@example.org', 'shopowner', 'owner@example.org' ) as $login ) {
				$_POST['wc_reset_password']                  = 'true';
				$_POST['user_login']                         = $login;
				$_REQUEST['woocommerce-lost-password-nonce'] = wp_create_nonce( 'lost_password' );
				$locations[ $login ]                         = $this->redirect_of( array( 'WC_Form_Handler', 'process_lost_password' ) );
			}
		} finally {
			array_pop( $GLOBALS['wp_current_filter'] );
			remove_filter( 'pre_wp_mail', $sent );
		}

		$expected = add_query_arg( 'reset-link-sent', 'true', wc_get_account_endpoint_url( 'lost-password' ) );
		foreach ( $locations as $login => $location ) {
			$this->assertSame( $expected, $location, $login );
		}
	}

	/**
	 * On, the REST API answers every wrong application password login with the same error.
	 */
	public function test_rest_basic_auth_errors() {
		global $wp_rest_application_password_status;
		$codes = array( 'invalid_username', 'invalid_email', 'incorrect_password', 'application_passwords_disabled_for_user' );
		try {
			$wp_rest_application_password_status = new WP_Error( 'invalid_username', 'Unknown username.' );
			$this->assertSame( 'invalid_username', apply_filters( 'rest_authentication_errors', null )->get_error_code(), 'Off: WordPress answers as is.' );

			$this->turn_on();
			foreach ( $codes as $code ) {
				$wp_rest_application_password_status = new WP_Error( $code, 'Detailed.' );
				$result                              = apply_filters( 'rest_authentication_errors', null );
				$this->assertSame( 'incorrect_password', $result->get_error_code(), $code );
				$this->assertSame( 'The username, email address or password is incorrect.', $result->get_error_message(), $code );
				$this->assertSame( array( 'status' => 401 ), $result->get_error_data(), $code );
			}

			$wp_rest_application_password_status = new WP_Error( 'application_passwords_disabled', 'Off for the site.' );
			$this->assertSame( 'application_passwords_disabled', apply_filters( 'rest_authentication_errors', null )->get_error_code(), 'An error that does not depend on the user stays.' );

			$wp_rest_application_password_status = $this->admin;
			$this->assertTrue( apply_filters( 'rest_authentication_errors', null ) );
		} finally {
			$wp_rest_application_password_status = null;
		}
	}

	/**
	 * Uninstalling removes the setting.
	 */
	public function test_uninstall_removes_the_setting() {
		$this->turn_on();
		JP4WC_Security_Hardening::uninstall();
		$this->assertFalse( get_option( 'wc4jp-hide-user-names' ) );
	}
}
