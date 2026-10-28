<?php
/**
 * Tests for the hardening options of the Security tab (issue #291).
 *
 * The users REST routes are requested through rest_get_server()->dispatch(),
 * as a visitor and as a logged-in user, so the rest_endpoints filter is
 * exercised where WordPress applies it.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Hardening_Test
 */
class JP4WC_Security_Hardening_Test extends WP_UnitTestCase {

	/**
	 * An administrator, who may list users.
	 *
	 * @var int
	 */
	private $admin;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		if ( ! class_exists( 'JP4WC_Security_Hardening' ) ) {
			require_once dirname( __DIR__, 2 ) . '/includes/class-jp4wc-security-hardening.php';
		}
		$this->assertTrue( class_exists( 'JP4WC_Security_Hardening' ) );
		$this->admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( 0 );
		delete_option( 'wc4jp-hide-rest-users' );
		delete_option( 'wc4jp-disallow-file-edit' );
	}

	/**
	 * Clean up.
	 */
	public function tearDown(): void {
		delete_option( 'wc4jp-hide-rest-users' );
		delete_option( 'wc4jp-disallow-file-edit' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Dispatches a GET request through the REST server.
	 *
	 * @param string $route Route.
	 * @return WP_REST_Response
	 */
	private function get( $route ) {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', $route ) );
	}

	/**
	 * The hooks are registered when the file is loaded.
	 */
	public function test_hooks_are_registered() {
		$this->assertSame( 100, has_filter( 'rest_endpoints', array( 'JP4WC_Security_Hardening', 'hide_users_routes' ) ) );
	}

	/**
	 * Off (the default), the users routes answer as WordPress does.
	 */
	public function test_off_leaves_the_users_routes() {
		$this->assertFalse( JP4WC_Security_Hardening::hides_rest_users() );
		$response = $this->get( '/wp/v2/users' );
		$this->assertSame( 200, $response->get_status(), 'WordPress lists the users with published content to visitors.' );
		$this->assertArrayHasKey( '/wp/v2/users', rest_get_server()->get_routes() );
	}

	/**
	 * On, a visitor gets 404 for the users routes, and the index does not list them.
	 */
	public function test_on_hides_the_users_routes_from_visitors() {
		update_option( 'wc4jp-hide-rest-users', '1' );

		$response = $this->get( '/wp/v2/users' );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'rest_no_route', $response->get_data()['code'] );

		$response = $this->get( '/wp/v2/users/' . $this->admin );
		$this->assertSame( 404, $response->get_status() );

		$routes = rest_get_server()->get_routes();
		$this->assertArrayNotHasKey( '/wp/v2/users', $routes );
		$this->assertArrayNotHasKey( '/wp/v2/users/me', $routes );
		$this->assertSame( array(), preg_grep( '#^/wp/v2/users#', array_keys( $routes ) ) );
		$this->assertArrayHasKey( '/wp/v2/posts', $routes, 'Other routes stay.' );

		$index = $this->get( '/' );
		$this->assertSame( 200, $index->get_status() );
		$this->assertArrayNotHasKey( '/wp/v2/users', $index->get_data()['routes'] );
	}

	/**
	 * On, a logged-in user keeps the routes and WordPress's own permissions.
	 */
	public function test_on_keeps_the_users_routes_for_logged_in_users() {
		update_option( 'wc4jp-hide-rest-users', '1' );
		wp_set_current_user( $this->admin );

		$this->assertSame( 200, $this->get( '/wp/v2/users' )->get_status() );
		$this->assertSame( 200, $this->get( '/wp/v2/users/me' )->get_status() );
		$this->assertSame( 200, $this->get( '/wp/v2/users/' . $this->admin )->get_status() );

		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );
		$this->assertSame( 200, $this->get( '/wp/v2/users/me' )->get_status(), 'The route is there; WordPress decides what a subscriber may see.' );
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$this->assertContains( rest_get_server()->dispatch( $request )->get_status(), array( 401, 403 ), 'WordPress refuses the edit context as before.' );
	}

	/**
	 * The filter leaves anything but a routes array alone.
	 */
	public function test_filter_passes_other_values_through() {
		update_option( 'wc4jp-hide-rest-users', '1' );
		$this->assertSame( 'x', JP4WC_Security_Hardening::hide_users_routes( 'x' ) );
		$this->assertSame( array( 5 => array() ), JP4WC_Security_Hardening::hide_users_routes( array( 5 => array() ) ) );
	}

	/**
	 * The file-editing setting and what else defines the constants.
	 *
	 * Nothing is defined here: a constant lives for the rest of the process.
	 */
	public function test_file_edit_setting_and_constants() {
		$this->assertFalse( JP4WC_Security_Hardening::disallows_file_edit() );
		update_option( 'wc4jp-disallow-file-edit', '1' );
		$this->assertTrue( JP4WC_Security_Hardening::disallows_file_edit() );

		$this->assertFalse( JP4WC_Security_Hardening::file_edit_defined_elsewhere( array() ) );
		$this->assertTrue( JP4WC_Security_Hardening::file_edit_defined_elsewhere( array( 'DISALLOW_FILE_EDIT' => true ) ) );
		$this->assertTrue( JP4WC_Security_Hardening::file_edit_defined_elsewhere( array( 'DISALLOW_FILE_MODS' => true ) ) );
		$this->assertFalse( JP4WC_Security_Hardening::file_edit_defined_elsewhere( array( 'DISALLOW_FILE_EDIT' => false ) ), 'Defined as false does not forbid.' );

		if ( ! defined( 'DISALLOW_FILE_EDIT' ) && ! defined( 'DISALLOW_FILE_MODS' ) ) {
			$this->assertFalse( JP4WC_Security_Hardening::file_edit_defined_elsewhere(), 'The test environment defines neither.' );
			update_option( 'wc4jp-disallow-file-edit', '' );
			$this->assertFalse( JP4WC_Security_Hardening::define_file_edit(), 'Off: nothing is defined.' );
			$this->assertFalse( defined( 'DISALLOW_FILE_EDIT' ) );
		}
	}

	/**
	 * Uninstalling removes both settings.
	 */
	public function test_uninstall_removes_the_settings() {
		update_option( 'wc4jp-hide-rest-users', '1' );
		update_option( 'wc4jp-disallow-file-edit', '1' );
		JP4WC_Security_Hardening::uninstall();
		$this->assertFalse( get_option( 'wc4jp-hide-rest-users' ) );
		$this->assertFalse( get_option( 'wc4jp-disallow-file-edit' ) );
	}
}
