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

		// Execute the plugin's custom post type registration function
		// (Note: Replace with the actual function name in your plugin).

		// Refresh rewrite rules (required for custom post type tests).
		flush_rewrite_rules();
	}

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}
}
