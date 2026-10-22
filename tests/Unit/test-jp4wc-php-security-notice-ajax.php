<?php
/**
 * Tests the "Don't show again" request of the PHP security notice (issue #271).
 *
 * @package Japanized_For_WooCommerce
 */

use ArtisanWorkshop\PhpSecurityNotice\Notice;

/**
 * JP4WC_PHP_Security_Notice_Ajax_Test
 */
class JP4WC_PHP_Security_Notice_Ajax_Test extends WP_Ajax_UnitTestCase {

	/**
	 * PHP version the notice judges in the current test.
	 *
	 * @var string
	 */
	private $php_version = '8.3.35';

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->assertInstanceOf( Notice::class, Notice::instance() );
		add_filter( 'artws_php_security_notice_php_version', array( $this, 'filter_php_version' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'artws_php_security_notice_php_version', array( $this, 'filter_php_version' ) );
		parent::tearDown();
	}

	/**
	 * Replaces the PHP version the notice judges.
	 *
	 * @return string
	 */
	public function filter_php_version() {
		return $this->php_version;
	}

	/**
	 * Sends the request and returns the exception wp_die() threw.
	 *
	 * Like _handleAjax(), but runs only the notice's admin_init callback: the
	 * other admin_init callbacks, such as WooCommerce's, print output of their own.
	 *
	 * @return WPDieException
	 */
	private function send_dismiss() {
		Notice::instance()->admin_init();
		$_POST['action'] = Notice::AJAX_ACTION;
		$_REQUEST        = array_merge( $_POST, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- Builds the request as _handleAjax() does; the handler checks the nonce.

		ob_start();
		try {
			do_action( 'wp_ajax_' . Notice::AJAX_ACTION );
		} catch ( WPDieException $e ) {
			// The die handler has closed the buffer.
			return $e;
		}
		ob_end_clean();
		$this->fail( 'The request did not end with wp_die().' );
	}

	/**
	 * An administrator's request stores the notice ID and the PHP version.
	 */
	public function test_dismiss_stores_the_notice_and_php_version() {
		$this->_setRole( 'administrator' );
		$user_id        = get_current_user_id();
		$_POST['nonce'] = wp_create_nonce( Notice::AJAX_ACTION );

		$this->assertInstanceOf( WPAjaxDieContinueException::class, $this->send_dismiss() );

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$dismissed = get_user_meta( $user_id, Notice::META_KEY, true );
		$this->assertSame( Notice::NOTICE_ID, $dismissed['notice_id'] );
		$this->assertSame( '8.3.35', $dismissed['php_version'] );
		$this->assertIsInt( $dismissed['time'] );

		set_current_screen( 'dashboard' );
		$this->assertNull( Notice::instance()->get_view(), 'Not shown again for the same PHP version.' );
		$this->php_version = '8.3.30';
		$this->assertNotNull( Notice::instance()->get_view(), 'Shown again after a change to another vulnerable version.' );
	}

	/**
	 * Nonces that reject the request.
	 *
	 * @return array Nonce, or null for none.
	 */
	public function bad_nonces() {
		return array(
			'no nonce'                  => array( null ),
			'nonce of another action'   => array( 'another-action' ),
			'nonce that is not a nonce' => array( 'abc' ),
		);
	}

	/**
	 * A request without a valid nonce is rejected and stores nothing.
	 *
	 * @dataProvider bad_nonces
	 *
	 * @param string|null $nonce_action Action to create the nonce for, 'abc' for a made-up value, or null.
	 */
	public function test_dismiss_requires_a_valid_nonce( $nonce_action ) {
		$this->_setRole( 'administrator' );
		if ( 'abc' === $nonce_action ) {
			$_POST['nonce'] = 'abc';
		} elseif ( null !== $nonce_action ) {
			$_POST['nonce'] = wp_create_nonce( $nonce_action );
		}

		$exception = $this->send_dismiss();

		$this->assertInstanceOf( WPAjaxDieStopException::class, $exception );
		$this->assertSame( '-1', $exception->getMessage() );
		$this->assertSame( '', get_user_meta( get_current_user_id(), Notice::META_KEY, true ) );
	}

	/**
	 * A user who cannot change the server's PHP is rejected and stores nothing.
	 */
	public function test_dismiss_requires_the_capability() {
		$this->_setRole( 'shop_manager' );
		$_POST['nonce'] = wp_create_nonce( Notice::AJAX_ACTION );

		$this->assertInstanceOf( WPAjaxDieContinueException::class, $this->send_dismiss() );

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( '', get_user_meta( get_current_user_id(), Notice::META_KEY, true ) );
	}
}
