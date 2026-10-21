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
	 * Path to the framework class file.
	 *
	 * @var string
	 */
	private $class_file;

	/**
	 * The framework class must be loaded by the plugin.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->class_file = dirname( __DIR__, 2 ) . '/includes/jp4wc-framework/class-jp4wc-framework.php';
		require_once $this->class_file;
		$this->assertTrue( class_exists( 'ArtisanWorkshop\PluginFramework\v2_0_14\JP4WC_Framework' ) );
	}

	/**
	 * The strings load without reading any other file.
	 *
	 * Checked on the source rather than on the directory listing: whatever the
	 * class requires or includes after it is loaded can be missing while the
	 * plugin is updated, whatever that file is called.
	 */
	public function test_text_config_needs_no_separate_file() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
		$source     = file_get_contents( $this->class_file );
		$file_reads = array_filter(
			token_get_all( $source ),
			static function ( $token ) {
				return is_array( $token ) && in_array( $token[0], array( T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE ), true );
			}
		);
		$this->assertSame( array(), $file_reads );

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

	/**
	 * The old strings file stays for the framework class of 2.9.17 and earlier.
	 *
	 * That class requires the file at init. A request that loaded it before an
	 * update to this version still runs it after the update, so removing the
	 * file brings issue #266 back for that update. Delete this test together
	 * with the file.
	 */
	public function test_transitional_config_file_is_kept() {
		$config_file = dirname( $this->class_file ) . '/config-jp4wc-framework.php';
		$this->assertFileExists( $config_file );

		$config = require $config_file;

		$this->assertSame( 'Need support?', $config['support_notice_01'] );
		$this->assertCount( 19, $config );
	}
}
