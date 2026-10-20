<?php
/**
 * Tests that the framework's translated strings come from the class file itself.
 *
 * The strings used to live in config-jp4wc-framework.php and were required at
 * init, after the class had been loaded at plugins_loaded. A plugin update that
 * replaced the directory in between left the class without that file, and the
 * require stopped the request with a fatal error (issue #266).
 *
 * @package Japanized_For_WooCommerce
 */

use ArtisanWorkshop\PluginFramework\v2_0_14 as Framework;

/**
 * JP4WC_Framework_Text_Config_Test
 */
class JP4WC_Framework_Text_Config_Test extends WP_UnitTestCase {

	/**
	 * The framework class must be loaded by the plugin.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once dirname( __DIR__, 2 ) . '/includes/jp4wc-framework/class-jp4wc-framework.php';
		$this->assertTrue( class_exists( 'ArtisanWorkshop\PluginFramework\v2_0_14\JP4WC_Framework' ) );
	}

	/**
	 * The strings load with no other file beside the class.
	 */
	public function test_text_config_needs_no_separate_file() {
		$this->assertSame(
			array( 'class-jp4wc-framework.php' ),
			array_map( 'basename', glob( dirname( __DIR__, 2 ) . '/includes/jp4wc-framework/*.php' ) )
		);

		$framework = new Framework\JP4WC_Framework();

		$this->assertSame( 'Need support?', $framework->text_array['support_notice_01'] );
		$this->assertSame( '2.0.14', $framework->text_array['framework_version'] );
		$this->assertCount( 19, $framework->text_array );
	}

	/**
	 * The jp4wc_framework_config filter still applies, and the result is still passed through wp_kses().
	 */
	public function test_text_config_filter_applies() {
		$filter = static function ( $config ) {
			$config['support_notice_01'] = 'Custom <script>x</script><strong>help</strong>';
			return $config;
		};
		add_filter( 'jp4wc_framework_config', $filter );
		try {
			$framework = new Framework\JP4WC_Framework();
		} finally {
			remove_filter( 'jp4wc_framework_config', $filter );
		}

		$this->assertSame( 'Custom x<strong>help</strong>', $framework->text_array['support_notice_01'] );
	}
}
