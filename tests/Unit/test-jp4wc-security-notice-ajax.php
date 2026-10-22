<?php
/**
 * Tests the request that remembers a closed security notice (issue #257).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Notice_Ajax_Test
 */
class JP4WC_Security_Notice_Ajax_Test extends WP_Ajax_UnitTestCase {

	/**
	 * A fingerprint as the notice sends it.
	 *
	 * @var string
	 */
	private $fingerprint;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Notice' ) );
		$this->fingerprint = sha1( 'findings' );
	}

	/**
	 * Sends the request and returns the exception wp_die() threw.
	 *
	 * Like _handleAjax(), but runs only the notice's callback: the admin_init
	 * callbacks of other plugins, such as WooCommerce's, print output of their own.
	 *
	 * @return WPDieException
	 */
	private function send_dismiss() {
		$_POST['action'] = JP4WC_Security_Notice::AJAX_ACTION;
		$_REQUEST        = array_merge( $_POST, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- Builds the request as _handleAjax() does; the handler checks the nonce.

		ob_start();
		try {
			do_action( 'wp_ajax_' . JP4WC_Security_Notice::AJAX_ACTION );
		} catch ( WPDieException $e ) {
			// The die handler has closed the buffer.
			return $e;
		}
		ob_end_clean();
		$this->fail( 'The request did not end with wp_die().' );
	}

	/**
	 * Logs in a user who manages WooCommerce.
	 */
	private function log_in_shop_manager() {
		// Granted by hand: WooCommerce's shop_manager role is not there in every test environment (CI).
		$this->_setRole( 'editor' );
		wp_get_current_user()->add_cap( 'manage_woocommerce' );
	}

	/**
	 * The fingerprint of the closed notice is stored for the user.
	 */
	public function test_dismiss_stores_the_fingerprint() {
		$this->log_in_shop_manager();
		$_POST['nonce']       = wp_create_nonce( JP4WC_Security_Notice::AJAX_ACTION );
		$_POST['fingerprint'] = $this->fingerprint;

		$this->assertInstanceOf( WPAjaxDieContinueException::class, $this->send_dismiss() );

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
		$this->assertSame( $this->fingerprint, get_user_meta( get_current_user_id(), JP4WC_Security_Notice::DISMISSED_META, true ) );
	}

	/**
	 * Values that are not a fingerprint.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function bad_fingerprints() {
		return array(
			'missing'   => array( null ),
			'too short' => array( 'abc' ),
			'uppercase' => array( strtoupper( sha1( 'x' ) ) ),
			'array'     => array( array( sha1( 'x' ) ) ),
		);
	}

	/**
	 * Such a value is rejected and nothing is stored.
	 *
	 * @dataProvider bad_fingerprints
	 *
	 * @param mixed $fingerprint Posted value, or null for none.
	 */
	public function test_dismiss_rejects_bad_fingerprints( $fingerprint ) {
		$this->log_in_shop_manager();
		$_POST['nonce'] = wp_create_nonce( JP4WC_Security_Notice::AJAX_ACTION );
		if ( null !== $fingerprint ) {
			$_POST['fingerprint'] = $fingerprint;
		}

		$this->assertInstanceOf( WPAjaxDieContinueException::class, $this->send_dismiss() );

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( '', get_user_meta( get_current_user_id(), JP4WC_Security_Notice::DISMISSED_META, true ) );
	}

	/**
	 * A request without a valid nonce is rejected.
	 */
	public function test_dismiss_requires_a_valid_nonce() {
		$this->log_in_shop_manager();
		$_POST['nonce']       = wp_create_nonce( 'another-action' );
		$_POST['fingerprint'] = $this->fingerprint;

		$exception = $this->send_dismiss();

		$this->assertInstanceOf( WPAjaxDieStopException::class, $exception );
		$this->assertSame( '-1', $exception->getMessage() );
		$this->assertSame( '', get_user_meta( get_current_user_id(), JP4WC_Security_Notice::DISMISSED_META, true ) );
	}

	/**
	 * A user who does not manage WooCommerce is rejected.
	 */
	public function test_dismiss_requires_the_capability() {
		$this->_setRole( 'editor' );
		$_POST['nonce']       = wp_create_nonce( JP4WC_Security_Notice::AJAX_ACTION );
		$_POST['fingerprint'] = $this->fingerprint;

		$this->assertInstanceOf( WPAjaxDieContinueException::class, $this->send_dismiss() );

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertSame( '', get_user_meta( get_current_user_id(), JP4WC_Security_Notice::DISMISSED_META, true ) );
	}
}
