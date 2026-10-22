<?php
/**
 * Tests the PHP security notice shared with other Artisan Workshop plugins.
 *
 * PHP2Shell (CVE-2026-109618, CVE-2026-109619) lets one unauthenticated request
 * run code on the server, before any application code runs. The notice asks the
 * administrators of a site whose PHP is older than the fixed version to update
 * it. The same directory is bundled in several plugins, and only the newest copy
 * registered is loaded (issue #271).
 *
 * @package Japanized_For_WooCommerce
 */

use ArtisanWorkshop\PhpSecurityNotice\Notice;

/**
 * JP4WC_PHP_Security_Notice_Test
 */
class JP4WC_PHP_Security_Notice_Test extends WP_UnitTestCase {

	/**
	 * The vulnerability IDs the notice shows.
	 */
	const CVE_IDS = 'CVE-2026-109618, CVE-2026-109619';

	/**
	 * PHP version the notice judges in the current test, or null for PHP_VERSION.
	 *
	 * @var string|null
	 */
	private $php_version = null;

	/**
	 * Temporary directories to remove after the test.
	 *
	 * @var string[]
	 */
	private $temp_dirs = array();

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->assertTrue( class_exists( 'ArtisanWorkshop\PhpSecurityNotice\Notice', false ), 'The plugin loads the notice at plugins_loaded.' );
		$this->assertInstanceOf( Notice::class, Notice::instance() );

		add_filter( 'artws_php_security_notice_php_version', array( $this, 'filter_php_version' ) );
		set_current_screen( 'dashboard' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'artws_php_security_notice_php_version', array( $this, 'filter_php_version' ) );
		remove_all_filters( 'artws_php_security_notice_fixed_versions' );
		remove_all_filters( 'artws_php_security_notice_info_url' );
		remove_action( 'admin_footer', array( Notice::instance(), 'render' ) );
		wp_dequeue_style( Notice::HANDLE );
		wp_deregister_style( Notice::HANDLE );
		wp_dequeue_script( Notice::HANDLE );
		wp_deregister_script( Notice::HANDLE );
		unset( $GLOBALS['current_screen'] );

		foreach ( $this->temp_dirs as $dir ) {
			foreach ( glob( $dir . '/*' ) as $file ) {
				wp_delete_file( $file );
			}
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Temporary test directory.
		}
		$this->temp_dirs = array();

		parent::tearDown();
	}

	/**
	 * Replaces the PHP version the notice judges.
	 *
	 * @param string $version PHP_VERSION.
	 * @return string
	 */
	public function filter_php_version( $version ) {
		return null === $this->php_version ? $version : $this->php_version;
	}

	/**
	 * Logs in a new user with a role.
	 *
	 * @param string $role Role.
	 * @return int User ID.
	 */
	private function log_in_as( $role ) {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/**
	 * PHP versions and how the notice judges them.
	 *
	 * @return array Version, type (null: not shown), fixed version, support ending.
	 */
	public function status_cases() {
		return array(
			'8.2 below the fix'         => array( '8.2.34', 'vulnerable', '8.2.35', true ),
			'8.2 fixed'                 => array( '8.2.35', null, '', false ),
			'8.2 after the fix'         => array( '8.2.40', null, '', false ),
			'8.3 below the fix'         => array( '8.3.35', 'vulnerable', '8.3.36', false ),
			'8.3 fixed'                 => array( '8.3.36', null, '', false ),
			'8.4 below the fix'         => array( '8.4.26', 'vulnerable', '8.4.27', false ),
			'8.4 fixed'                 => array( '8.4.27', null, '', false ),
			'8.5 below the fix'         => array( '8.5.11', 'vulnerable', '8.5.12', false ),
			'8.5 fixed'                 => array( '8.5.12', null, '', false ),
			'8.5.0'                     => array( '8.5.0', 'vulnerable', '8.5.12', false ),
			'8.1'                       => array( '8.1.33', 'unsupported', '', false ),
			'8.0'                       => array( '8.0.30', 'unsupported', '', false ),
			'7.4'                       => array( '7.4.33', 'unsupported', '', false ),
			'5.6'                       => array( '5.6.40', 'unsupported', '', false ),
			'8.6 RC3'                   => array( '8.6.0RC3', 'prerelease', '8.6.0RC4', false ),
			'8.6 beta'                  => array( '8.6.0beta1', 'prerelease', '8.6.0RC4', false ),
			'8.6 dev build'             => array( '8.6.0-dev', 'prerelease', '8.6.0RC4', false ),
			'8.6 RC4'                   => array( '8.6.0RC4', null, '', false ),
			'8.6 release'               => array( '8.6.0', null, '', false ),
			'8.7'                       => array( '8.7.0', null, '', false ),
			'9.0'                       => array( '9.0.0', null, '', false ),
			'distribution, below fix'   => array( '8.3.6-1ubuntu1', 'vulnerable', '8.3.36', false ),
			'distribution, fixed'       => array( '8.3.36-1+ubuntu24.04.1+deb.sury.org+1', null, '', false ),
			'version without the patch' => array( '8.4', 'vulnerable', '8.4.27', false ),
			'not a version'             => array( 'unknown', null, '', false ),
		);
	}

	/**
	 * Each PHP version is judged as in the table of issue #271.
	 *
	 * @dataProvider status_cases
	 *
	 * @param string      $version        PHP version.
	 * @param string|null $type           Expected type, or null when the notice is not shown.
	 * @param string      $fixed_version  Expected fixed version.
	 * @param bool        $support_ending Whether the branch's support ends soon.
	 */
	public function test_status_of_php_version( $version, $type, $fixed_version, $support_ending ) {
		$status = Notice::instance()->get_status( $version );

		if ( null === $type ) {
			$this->assertNull( $status );
			return;
		}
		$this->assertSame( $type, $status['type'] );
		$this->assertSame( $version, $status['php_version'] );
		$this->assertSame( $fixed_version, $status['fixed_version'] );
		$this->assertSame( $support_ending, $status['support_ending'] );
	}

	/**
	 * The notice judges PHP_VERSION unless the filter replaces it.
	 */
	public function test_judges_php_version_by_default() {
		$this->assertSame( PHP_VERSION, Notice::instance()->php_version() );

		$this->php_version = '8.3.35';
		$this->assertSame( '8.3.35', Notice::instance()->php_version() );
		$this->assertSame( 'vulnerable', Notice::instance()->get_status()['type'] );
	}

	/**
	 * Messages for each kind of PHP version.
	 *
	 * @return array Version, expected messages.
	 */
	public function message_cases() {
		$strings = jp4wc_php_security_notice_strings();
		return array(
			'8.2 below the fix' => array(
				'8.2.34',
				array(
					'The PHP version of this site (8.2.34) has critical vulnerabilities (' . self::CVE_IDS . ') that let an attacker run code on the server with a single request. Update PHP to 8.2.35 or later right away.',
					$strings['support_ending'],
					$strings['vendor_patch'],
				),
			),
			'8.3 below the fix' => array(
				'8.3.35',
				array(
					'The PHP version of this site (8.3.35) has critical vulnerabilities (' . self::CVE_IDS . ') that let an attacker run code on the server with a single request. Update PHP to 8.3.36 or later right away.',
					$strings['vendor_patch'],
				),
			),
			'8.1'               => array(
				'8.1.33',
				array(
					'The PHP version of this site (8.1.33) has critical vulnerabilities (' . self::CVE_IDS . '). Official support for this PHP version has ended, and no fixed version has been released for it. Some hosting companies apply their own security patches, but update to PHP 8.3 or later, which is supported longer, as soon as possible.',
				),
			),
			'8.6 RC3'           => array(
				'8.6.0RC3',
				array(
					'This site runs a pre-release of PHP (8.6.0RC3) that has critical vulnerabilities (' . self::CVE_IDS . '). Update it to 8.6.0RC4 or later.',
				),
			),
		);
	}

	/**
	 * An administrator sees the message for the site's PHP version.
	 *
	 * @dataProvider message_cases
	 *
	 * @param string   $version  PHP version.
	 * @param string[] $messages Expected paragraphs.
	 */
	public function test_view_shows_the_message_for_the_php_version( $version, $messages ) {
		$this->log_in_as( 'administrator' );
		$this->php_version = $version;

		$view = Notice::instance()->get_view();

		$this->assertNotNull( $view );
		$this->assertSame( $messages, $view['messages'] );
		$this->assertSame( 'Update PHP now: critical security vulnerabilities', $view['title'] );
		$this->assertSame( 'https://wc4jp-pro.work/2026/10/22/php2shell-php-update/', $view['url'] );
	}

	/**
	 * A fixed PHP version shows nothing.
	 */
	public function test_view_is_empty_for_a_fixed_php_version() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.36';

		$this->assertNull( Notice::instance()->get_view() );
	}

	/**
	 * The list of fixed versions can be replaced.
	 */
	public function test_fixed_versions_filter() {
		add_filter(
			'artws_php_security_notice_fixed_versions',
			function () {
				return array( '8.3' => '8.3.40' );
			}
		);

		$this->assertSame( '8.3.40', Notice::instance()->get_status( '8.3.36' )['fixed_version'] );
		$this->assertNull( Notice::instance()->get_status( '8.3.40' ) );
		// Older than every branch in the list.
		$this->assertSame( 'unsupported', Notice::instance()->get_status( '8.2.35' )['type'] );
		// Newer than every branch in the list.
		$this->assertNull( Notice::instance()->get_status( '8.4.0' ) );

		remove_all_filters( 'artws_php_security_notice_fixed_versions' );
		add_filter( 'artws_php_security_notice_fixed_versions', '__return_false' );
		$this->assertSame( '8.3.36', Notice::instance()->get_status( '8.3.35' )['fixed_version'], 'A broken filter falls back to the built-in list.' );
	}

	/**
	 * The article URL can be replaced.
	 */
	public function test_info_url_filter() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.35';
		add_filter(
			'artws_php_security_notice_info_url',
			function () {
				return 'https://example.com/php/';
			}
		);

		$this->assertSame( 'https://example.com/php/', Notice::instance()->get_view()['url'] );
	}

	/**
	 * Of the registered copies, the newest one wins, whatever the order.
	 */
	public function test_newest_registration_wins() {
		$old   = array(
			'version'          => '1.0.0',
			'class_file'       => '/old/class-notice.php',
			'strings_callback' => 'old_strings',
		);
		$new   = array(
			'version'          => '1.1.0',
			'class_file'       => '/new/class-notice.php',
			'strings_callback' => 'new_strings',
		);
		$tenth = array_merge( $new, array( 'version' => '1.10.0' ) );
		$ninth = array_merge( $new, array( 'version' => '1.9.0' ) );
		$twin  = array_merge( $old, array( 'class_file' => '/twin/class-notice.php' ) );

		$this->assertSame( $new, artws_php_security_notice_newest( array( $old, $new ) ) );
		$this->assertSame( $new, artws_php_security_notice_newest( array( $new, $old ) ) );
		$this->assertSame( $tenth, artws_php_security_notice_newest( array( $tenth, $ninth ) ), 'Versions are compared as versions, not as strings.' );
		$this->assertSame( $old, artws_php_security_notice_newest( array( $old, $twin ) ), 'Of the same version, the first one wins.' );
		$this->assertSame( $old, artws_php_security_notice_newest( array( 'broken', array( 'version' => '' ), array_merge( $new, array( 'version' => 2 ) ), $old ) ) );
		$this->assertNull( artws_php_security_notice_newest( array() ) );
		$this->assertNull( artws_php_security_notice_newest( 'broken' ) );
	}

	/**
	 * Registration orders of an older and a newer copy.
	 *
	 * @return array Whether the newer copy is registered first.
	 */
	public function registration_orders() {
		return array(
			'older copy first' => array( false ),
			'newer copy first' => array( true ),
		);
	}

	/**
	 * Only the newest copy is loaded, and one notice is created with its strings.
	 *
	 * Runs register.php and the loader of two copies in a PHP process of its own,
	 * because the class can be declared only once per process.
	 *
	 * @dataProvider registration_orders
	 *
	 * @param bool $newer_first Whether the newer copy is registered first.
	 */
	public function test_loader_loads_only_the_newest_copy( $newer_first ) {
		$source = dirname( __DIR__, 2 ) . '/includes/artws-php-security-notice';
		$older  = $this->copy_notice( $source );
		$newer  = $this->copy_notice( $source );

		$result = $this->load_copies( $newer_first ? array( $newer, '1.1.0', $older, '1.0.0' ) : array( $older, '1.0.0', $newer, '1.1.0' ) );

		$newer_file = realpath( $newer . '/class-notice.php' );
		$this->assertSame( array( $newer_file ), $result['loaded_files'] );
		$this->assertSame( $newer_file, $result['class_file'] );
		$this->assertTrue( $result['instance_is_registered'] );
		$this->assertSame( 1, $result['plugins_loaded_hooks'], 'Only the first register.php hooks the loader.' );
		$this->assertSame( 1, $result['admin_init_hooks'], 'One notice is created.' );
		$this->assertSame( $newer_first ? 'strings_of_a' : 'strings_of_b', $result['strings_callback'], 'The notice uses the strings of the plugin that registered the newer copy.' );
	}

	/**
	 * A newest copy whose directory is missing, as during its plugin's update, stops nothing.
	 */
	public function test_loader_skips_a_missing_copy() {
		$older   = $this->copy_notice( dirname( __DIR__, 2 ) . '/includes/artws-php-security-notice' );
		$missing = trailingslashit( get_temp_dir() ) . 'jp4wc-php-security-notice-missing-' . wp_generate_password( 12, false );

		$result = $this->load_copies( array( $older, '1.0.0', $missing, '1.1.0' ) );

		$this->assertSame( array(), $result['loaded_files'] );
		$this->assertFalse( $result['instance_is_registered'] );
	}

	/**
	 * Runs tests/fixtures/php-security-notice/load-copies.php in a PHP process of its own.
	 *
	 * @param string[] $args Directory and version of copy A, then of copy B.
	 * @return array The fixture's report.
	 */
	private function load_copies( $args ) {
		$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/fixtures/php-security-notice/load-copies.php' );
		foreach ( $args as $arg ) {
			$command .= ' ' . escapeshellarg( $arg );
		}
		exec( $command . ' 2>&1', $output, $exit_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- The class can be declared only once per process.
		$output = implode( "\n", $output );

		$this->assertSame( 0, $exit_code, $output );
		$result = json_decode( $output, true );
		$this->assertIsArray( $result, $output );
		return $result;
	}

	/**
	 * Copies the notice's files to a new temporary directory.
	 *
	 * @param string $source Directory of the notice.
	 * @return string Real path of the copy.
	 */
	private function copy_notice( $source ) {
		$dir = trailingslashit( get_temp_dir() ) . 'jp4wc-php-security-notice-' . wp_generate_password( 12, false );
		$this->assertTrue( wp_mkdir_p( $dir ) );
		$this->temp_dirs[] = $dir;
		foreach ( array( 'register.php', 'class-notice.php' ) as $file ) {
			$this->assertTrue( copy( $source . '/' . $file, $dir . '/' . $file ) );
		}
		return realpath( $dir );
	}

	/**
	 * This plugin registers its own copy with the copy's version, and that copy is loaded.
	 */
	public function test_plugin_registers_its_copy() {
		$class_file = dirname( __DIR__, 2 ) . '/includes/artws-php-security-notice/class-notice.php';
		$registered = array();
		foreach ( $GLOBALS['artws_php_security_notice_registry'] as $entry ) {
			if ( realpath( $entry['class_file'] ) === realpath( $class_file ) ) {
				$registered[] = $entry;
			}
		}

		$this->assertCount( 1, $registered );
		$this->assertSame( Notice::VERSION, $registered[0]['version'], 'Register the version of the bundled copy.' );
		$this->assertSame( 'jp4wc_php_security_notice_strings', $registered[0]['strings_callback'] );
		$this->assertSame( realpath( $class_file ), realpath( ( new ReflectionClass( Notice::class ) )->getFileName() ) );
		$this->assertSame( 10, has_action( 'admin_init', array( Notice::instance(), 'admin_init' ) ) );
	}

	/**
	 * This plugin passes a string for every key the notice uses.
	 */
	public function test_plugin_passes_every_string() {
		$strings = jp4wc_php_security_notice_strings();

		foreach ( Notice::STRING_KEYS as $key ) {
			$this->assertArrayHasKey( $key, $strings );
			$this->assertIsString( $strings[ $key ] );
			$this->assertNotSame( '', $strings[ $key ], $key );
		}
	}

	/**
	 * Strings a WordPress.org language pack lacks come from the bundled translation.
	 *
	 * A language pack wins over the bundled file and has no translation for new
	 * strings until they are translated on translate.wordpress.org, so the notice
	 * was shown in English on Japanese sites.
	 */
	public function test_strings_missing_from_the_language_pack_use_the_bundled_translation() {
		$pack = trailingslashit( get_temp_dir() ) . 'woocommerce-for-japan-ja-' . wp_generate_password( 12, false ) . '.mo';
		$mo   = new MO();
		$mo->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Close',
					'translations' => array( 'Close (language pack)' ),
				)
			)
		);
		$this->assertTrue( $mo->export_to_file( $pack ) );

		$controller      = WP_Translation_Controller::get_instance();
		$previous_locale = $controller->get_locale();
		$japanese        = static function () {
			return 'ja';
		};
		add_filter( 'pre_determine_locale', $japanese );
		unload_textdomain( 'woocommerce-for-japan', true );
		try {
			$this->assertTrue( load_textdomain( 'woocommerce-for-japan', $pack, 'ja' ) );

			$strings = jp4wc_php_security_notice_strings();

			$this->assertSame( 'Close (language pack)', $strings['close'], 'The language pack wins where it has a translation.' );
			$this->assertSame( 'PHP の重大な脆弱性: 今すぐ更新してください', $strings['title'] );
			$this->assertSame( '今後表示しない', $strings['dismiss'] );
			$this->assertSame( 'Update PHP now: critical security vulnerabilities', __( 'Update PHP now: critical security vulnerabilities', 'woocommerce-for-japan' ), 'Other calls are left to the loaded translations.' );
		} finally {
			unload_textdomain( 'woocommerce-for-japan', true );
			remove_filter( 'pre_determine_locale', $japanese );
			$controller->set_locale( $previous_locale );
			wp_delete_file( $pack );
		}
	}

	/**
	 * "Don't show again" hides the notice for the same PHP version and notice ID only.
	 */
	public function test_dismissed_notice_comes_back_when_php_or_notice_changes() {
		$user_id           = $this->log_in_as( 'administrator' );
		$this->php_version = '8.3.35';
		$this->assertNotNull( Notice::instance()->get_view() );

		update_user_meta(
			$user_id,
			Notice::META_KEY,
			array(
				'notice_id'   => Notice::NOTICE_ID,
				'php_version' => '8.3.35',
				'time'        => time(),
			)
		);
		$this->assertNull( Notice::instance()->get_view(), 'Dismissed for this PHP version.' );

		$this->php_version = '8.3.30';
		$this->assertNotNull( Notice::instance()->get_view(), 'The PHP version changed and is still vulnerable.' );

		$this->php_version = '8.3.35';
		update_user_meta(
			$user_id,
			Notice::META_KEY,
			array(
				'notice_id'   => 'an-earlier-notice',
				'php_version' => '8.3.35',
				'time'        => time(),
			)
		);
		$this->assertNotNull( Notice::instance()->get_view(), 'A new notice ID shows the notice again.' );
	}

	/**
	 * Only users who can change the server's PHP see the notice.
	 */
	public function test_only_administrators_see_the_notice() {
		$this->php_version = '8.3.35';

		// A shop manager. Granted by hand: WooCommerce's shop_manager role is not
		// there in every test environment (CI).
		$this->log_in_as( 'editor' );
		wp_get_current_user()->add_cap( 'manage_woocommerce' );
		$this->assertTrue( current_user_can( 'manage_woocommerce' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's capability.
		$this->assertNull( Notice::instance()->get_view(), 'A shop manager does not see it.' );

		$this->log_in_as( 'editor' );
		$this->assertNull( Notice::instance()->get_view() );

		wp_set_current_user( 0 );
		$this->assertNull( Notice::instance()->get_view() );

		$this->log_in_as( 'administrator' );
		$this->assertNotNull( Notice::instance()->get_view() );
	}

	/**
	 * On multisite only super administrators see the notice.
	 *
	 * The test suite runs as a single site, where a site administrator does not
	 * have manage_network_options.
	 */
	public function test_multisite_requires_network_capability() {
		$this->assertSame( 'manage_options', Notice::required_capability( false ) );
		$this->assertSame( 'manage_network_options', Notice::required_capability( true ) );

		$user = get_user_by( 'id', $this->log_in_as( 'administrator' ) );
		$this->assertFalse( user_can( $user, Notice::required_capability( true ) ), 'A site administrator is not a super administrator.' );
		$user->add_cap( 'manage_network_options' );
		$this->assertTrue( user_can( $user, Notice::required_capability( true ) ) );

		// is_multisite() cannot change in a single-site run, so check on the source
		// that the notice and the dismiss request ask for the capability of the
		// current install, through the notice's only capability check.
		$file = ( new ReflectionClass( Notice::class ) )->getFileName();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
		$this->assertSame( 1, substr_count( file_get_contents( $file ), 'current_user_can(' ), 'The notice has one capability check.' );
		$this->assertStringContainsString( 'current_user_can( self::required_capability( is_multisite() ) )', $this->method_source( 'current_user_can_update_php' ) );
		foreach ( array( 'get_view', 'ajax_dismiss' ) as $method ) {
			$this->assertStringContainsString( '$this->current_user_can_update_php()', $this->method_source( $method ), $method );
		}
	}

	/**
	 * Source of a method of the notice.
	 *
	 * @param string $method Method name.
	 * @return string
	 */
	private function method_source( $method ) {
		$reflection = new ReflectionMethod( Notice::class, $method );
		$lines      = file( $reflection->getFileName() );
		return implode( '', array_slice( $lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1 ) );
	}

	/**
	 * The block editor and the Customizer show nothing.
	 */
	public function test_excluded_screens() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.35';

		set_current_screen( 'post' );
		$screen          = get_current_screen();
		$is_block_editor = $screen->is_block_editor();
		try {
			$screen->is_block_editor( false );
			$this->assertNotNull( Notice::instance()->get_view(), 'The classic editor shows the notice.' );
			$screen->is_block_editor( true );
			$this->assertNull( Notice::instance()->get_view(), 'The block editor does not.' );
		} finally {
			$screen->is_block_editor( $is_block_editor );
		}

		set_current_screen( 'customize' );
		$this->assertNull( Notice::instance()->get_view() );
	}

	/**
	 * When the notice is shown, its style and script are added and the dialog is printed.
	 */
	public function test_dialog_is_printed_with_a_valid_nonce() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.35';

		Notice::instance()->enqueue();

		$this->assertTrue( wp_style_is( Notice::HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_script_is( Notice::HANDLE, 'enqueued' ) );
		$this->assertSame( 10, has_action( 'admin_footer', array( Notice::instance(), 'render' ) ) );

		ob_start();
		Notice::instance()->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<dialog id="artws-php-security-notice"', $html );
		$this->assertStringContainsString( 'Update PHP now: critical security vulnerabilities', $html );
		$this->assertStringContainsString( 'Update PHP to 8.3.36 or later right away.', $html );
		$this->assertStringContainsString( 'href="https://wc4jp-pro.work/2026/10/22/php2shell-php-update/"', $html );
		$this->assertStringContainsString( 'data-action="artws_php_security_notice_dismiss"', $html );
		// Relative, like core's ajaxurl: the request must go to the host the admin is open on.
		$this->assertStringContainsString( 'data-ajax-url="/wp-admin/admin-ajax.php"', $html );
		$this->assertSame( 1, preg_match( '/data-nonce="([^"]+)"/', $html, $matches ) );
		$this->assertSame( 1, wp_verify_nonce( $matches[1], Notice::AJAX_ACTION ) );
	}

	/**
	 * The PHP version and the URL are escaped in the dialog.
	 */
	public function test_dialog_escapes_its_values() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.1<script>alert(1)</script>';
		add_filter(
			'artws_php_security_notice_info_url',
			function () {
				return 'https://example.com/?a=1&b="x"';
			}
		);

		Notice::instance()->enqueue();
		ob_start();
		Notice::instance()->render();
		$html = ob_get_clean();

		$this->assertStringContainsString( '(8.3.1&lt;script&gt;alert(1)&lt;/script&gt;)', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '"x"', $html );
	}

	/**
	 * When the notice is not shown, nothing is added or printed.
	 */
	public function test_nothing_is_printed_for_a_fixed_php_version() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.36';

		Notice::instance()->enqueue();
		ob_start();
		Notice::instance()->render();
		$html = ob_get_clean();

		$this->assertFalse( wp_style_is( Notice::HANDLE, 'enqueued' ) );
		$this->assertFalse( wp_script_is( Notice::HANDLE, 'enqueued' ) );
		$this->assertFalse( has_action( 'admin_footer', array( Notice::instance(), 'render' ) ) );
		$this->assertSame( '', $html );
	}

	/**
	 * Missing strings hide the notice, and a broken placeholder does not stop the screen.
	 */
	public function test_strings_from_the_callback_are_checked() {
		$this->log_in_as( 'administrator' );
		$this->php_version = '8.3.35';
		$property          = new ReflectionProperty( Notice::class, 'strings_callback' );
		$original          = $property->getValue( Notice::instance() );

		try {
			$property->setValue(
				Notice::instance(),
				function () {
					return array( 'title' => 'Title' );
				}
			);
			$this->assertNull( Notice::instance()->get_view(), 'Without the message and the button, nothing is shown.' );

			$property->setValue(
				Notice::instance(),
				function () {
					return array(
						'title'      => 'Title',
						'vulnerable' => 'Broken %5$s',
						'dismiss'    => 'Dismiss',
					);
				}
			);
			$this->assertSame( array( 'Broken %5$s' ), Notice::instance()->get_view()['messages'] );
		} finally {
			$property->setValue( Notice::instance(), $original );
		}
	}
}
