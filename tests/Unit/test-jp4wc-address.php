<?php
/**
 * Class JP4WC_Address_Test
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Address_Test test case.
 */
class JP4WC_Address_Test extends WP_UnitTestCase {

	/**
	 * A preparation method that is always called before each test is run.
	 */
	public function setUp(): void {
		parent::setUp();

		// Refresh rewrite rules (required for custom post type tests).
		flush_rewrite_rules();
	}

	/**
	 * Test that the JP4WC_Address_Fields class exists.
	 */
	public function test_jp4wc_address_fields_class_exists() {
		// Debug: Check if JP4WC is loaded.
		$jp4wc_loaded = class_exists( 'JP4WC' );
		$this->assertTrue( $jp4wc_loaded, 'JP4WC main class should be loaded' );

		// Debug: Check if file exists.
		$address_file = dirname( __DIR__, 2 ) . '/includes/class-jp4wc-address-fields.php';
		$this->assertTrue( file_exists( $address_file ), "Address fields file should exist at: {$address_file}" );

		// Debug: Check if JP4WC_INCLUDES_PATH is defined.
		$this->assertTrue( defined( 'JP4WC_INCLUDES_PATH' ), 'JP4WC_INCLUDES_PATH should be defined' );

		if ( defined( 'JP4WC_INCLUDES_PATH' ) ) {
			error_log( 'JP4WC_INCLUDES_PATH: ' . JP4WC_INCLUDES_PATH );
			error_log( 'Expected file: ' . JP4WC_INCLUDES_PATH . 'class-jp4wc-address-fields.php' );
			error_log( 'File exists: ' . ( file_exists( JP4WC_INCLUDES_PATH . 'class-jp4wc-address-fields.php' ) ? 'YES' : 'NO' ) );
		}

		// Now check if class exists.
		$this->assertTrue( class_exists( 'JP4WC_Address_Fields' ), 'JP4WC_Address_Fields class should exist' );
	}

	/**
	 * Test that WooCommerce is active.
	 */
	public function test_woocommerce_is_active() {
		$this->assertTrue( class_exists( 'WooCommerce' ), 'WooCommerce should be loaded' );
		$this->assertInstanceOf( 'WooCommerce', WC(), 'WC() should return WooCommerce instance' );
	}

	/**
	 * Test that WordPress functions are available.
	 */
	public function test_wordpress_functions_available() {
		$this->assertTrue( function_exists( 'add_filter' ), 'WordPress add_filter function should be available' );
		$this->assertTrue( function_exists( 'do_action' ), 'WordPress do_action function should be available' );
	}

	/**
	 * Test that we can create a WooCommerce product.
	 */
	public function test_can_create_woocommerce_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( '1000' );
		$product_id = $product->save();

		$this->assertGreaterThan( 0, $product_id, 'Product should be created with a valid ID' );

		// Clean up.
		wp_delete_post( $product_id, true );
	}

	/**
	 * Test that we can create a WooCommerce order.
	 */
	public function test_can_create_woocommerce_order() {
		$order = new WC_Order();
		$order->set_billing_email( 'test@example.com' );
		$order_id = $order->save();

		$this->assertGreaterThan( 0, $order_id, 'Order should be created with a valid ID' );

		// Clean up.
		wp_delete_post( $order_id, true );
	}
}
