<?php
/**
 * Tests for the bundled translation of strings a WordPress.org language pack lacks.
 *
 * A language pack wins over the bundled translation and has no translation for
 * new strings until they are translated on translate.wordpress.org, so the
 * security check's notice and the Security tab's error messages were shown in
 * English on Japanese sites. Each test loads a language pack that translates
 * one string only, with the locale set to Japanese.
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Bundled_Translation_Test
 */
class JP4WC_Bundled_Translation_Test extends WP_UnitTestCase {

	/**
	 * The one string the language pack translates, and its translation.
	 */
	const PACK_TEXT        = 'Open the Security tab';
	const PACK_TRANSLATION = 'Open the Security tab (language pack)';

	/**
	 * Path of the language pack.
	 *
	 * @var string
	 */
	private $pack = '';

	/**
	 * Locale of the translation controller before the test.
	 *
	 * @var string
	 */
	private $previous_locale = '';

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( function_exists( 'jp4wc_with_bundled_translation' ) );

		$this->pack = trailingslashit( get_temp_dir() ) . 'woocommerce-for-japan-ja-' . wp_generate_password( 12, false ) . '.mo';
		$mo         = new MO();
		$mo->add_entry(
			new Translation_Entry(
				array(
					'singular'     => self::PACK_TEXT,
					'translations' => array( self::PACK_TRANSLATION ),
				)
			)
		);
		$this->assertTrue( $mo->export_to_file( $this->pack ) );

		$this->previous_locale = WP_Translation_Controller::get_instance()->get_locale();
		add_filter( 'pre_determine_locale', array( $this, 'japanese' ) );
		unload_textdomain( 'woocommerce-for-japan', true );
		$this->assertTrue( load_textdomain( 'woocommerce-for-japan', $this->pack, 'ja' ) );

		update_option( JP4WC_Security_Feed::SETTING, '1' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unload_textdomain( 'woocommerce-for-japan', true );
		remove_filter( 'pre_determine_locale', array( $this, 'japanese' ) );
		WP_Translation_Controller::get_instance()->set_locale( $this->previous_locale );
		wp_delete_file( $this->pack );
		delete_option( JP4WC_Security_Feed::FINDINGS_OPTION );
		delete_option( JP4WC_Security_Feed::RECHECK_OPTION );
		update_option( JP4WC_Security_Feed::SETTING, '1' );
		parent::tearDown();
	}

	/**
	 * Sets the locale to Japanese (pre_determine_locale).
	 *
	 * @return string
	 */
	public function japanese() {
		return 'ja';
	}

	/**
	 * Logs in an editor, with or without the capability to manage WooCommerce.
	 *
	 * Granted by hand: WooCommerce's shop_manager role is not there in every test environment (CI).
	 *
	 * @param bool $manage_woocommerce Whether the user manages WooCommerce.
	 */
	private function log_in( $manage_woocommerce ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		if ( $manage_woocommerce ) {
			wp_get_current_user()->add_cap( 'manage_woocommerce' );
		}
	}

	/**
	 * Asserts that no bundled translation is used outside a callback.
	 */
	private function assert_filters_removed() {
		$this->assertFalse( has_filter( 'gettext_woocommerce-for-japan', 'jp4wc_bundled_translation' ) );
		$this->assertFalse( has_filter( 'gettext_with_context_woocommerce-for-japan', 'jp4wc_bundled_translation' ) );
		$this->assertFalse( has_filter( 'ngettext_woocommerce-for-japan', 'jp4wc_bundled_plural_translation' ) );
		$this->assertSame( 'License', __( 'License', 'woocommerce-for-japan' ), 'Other calls are left to the loaded translations.' );
	}

	/**
	 * __(), _x() and _n() take the strings the language pack lacks from the bundled file.
	 */
	public function test_strings_the_language_pack_lacks_come_from_the_bundled_file() {
		$strings = jp4wc_with_bundled_translation(
			static function ( $count ) {
				return array(
					'pack'    => __( 'Open the Security tab', 'woocommerce-for-japan' ),
					'escaped' => esc_html__( 'License', 'woocommerce-for-japan' ),
					'context' => _x( 'High', 'vulnerability severity', 'woocommerce-for-japan' ),
					/* translators: %d: number of vulnerabilities not listed */
					'one'     => _n( 'and %d more', 'and %d more', 1, 'woocommerce-for-japan' ),
					/* translators: %d: number of vulnerabilities */
					'plural'  => _n( '%d known vulnerability was found in the software installed on this site.', '%d known vulnerabilities were found in the software installed on this site.', $count, 'woocommerce-for-japan' ),
					'missing' => __( 'A string with no translation anywhere', 'woocommerce-for-japan' ),
				);
			},
			3
		);

		$this->assertSame( self::PACK_TRANSLATION, $strings['pack'], 'The language pack wins where it has a translation.' );
		$this->assertSame( 'ライセンス', $strings['escaped'] );
		$this->assertSame( '高', $strings['context'] );
		$this->assertSame( 'ほか %d 件', $strings['one'] );
		$this->assertSame( 'このサイトにインストールされているソフトウェアに、既知の脆弱性が %d 件見つかりました。', $strings['plural'] );
		$this->assertSame( 'A string with no translation anywhere', $strings['missing'] );
		$this->assert_filters_removed();
	}

	/**
	 * A nested call leaves the bundled translation on until the outer call ends.
	 */
	public function test_nested_call_keeps_the_bundled_translation() {
		$after_inner = jp4wc_with_bundled_translation(
			static function () {
				jp4wc_with_bundled_translation( '__return_null' );
				return __( 'License', 'woocommerce-for-japan' );
			}
		);

		$this->assertSame( 'ライセンス', $after_inner );
		$this->assert_filters_removed();
	}

	/**
	 * The bundled translation is turned off when the callback throws.
	 */
	public function test_callback_that_throws_turns_it_off() {
		try {
			jp4wc_with_bundled_translation(
				static function () {
					throw new RuntimeException( 'Thrown by the test.' );
				}
			);
			$this->fail( 'The exception is passed on.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'Thrown by the test.', $e->getMessage() );
		}

		$this->assert_filters_removed();
	}

	/**
	 * A locale the plugin bundles no translation for keeps the English strings.
	 */
	public function test_locale_without_a_bundled_file_keeps_the_text() {
		remove_filter( 'pre_determine_locale', array( $this, 'japanese' ) );
		$fr = static function () {
			return 'fr_FR';
		};
		add_filter( 'pre_determine_locale', $fr );
		try {
			$this->assertSame( 'License', jp4wc_with_bundled_translation( '__', 'License', 'woocommerce-for-japan' ) );
		} finally {
			remove_filter( 'pre_determine_locale', $fr );
		}
	}

	/**
	 * The security check's notice is shown in Japanese.
	 */
	public function test_security_notice_uses_the_bundled_translation() {
		$this->log_in( true );
		$record = static function ( $suffix, $rating ) {
			$id = '00000000-0000-4000-8000-' . str_pad( $suffix, 12, '0', STR_PAD_LEFT );
			return array(
				'id'               => $id,
				'title'            => 'Sample - Cross-Site Scripting',
				'reference'        => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/' . $id,
				'rating'           => $rating,
				'score'            => 7.5,
				'patched'          => true,
				'patched_versions' => array( '1.2.4' ),
			);
		};
		$items  = array(
			array(
				'type'            => 'plugin',
				'slug'            => 'sample-plugin',
				'name'            => 'Sample Plugin',
				'version'         => '1.2.3',
				'file'            => 'sample-plugin/sample-plugin.php',
				'vulnerabilities' => array( $record( '1', 'High' ), $record( '2', 'Medium' ) ),
			),
		);
		update_option(
			JP4WC_Security_Feed::FINDINGS_OPTION,
			array(
				'checked_at'   => time(),
				'version'      => '2026-10-20.1',
				'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'stale'        => false,
				'copyrights'   => array(
					'defiant' => array(
						'notice'      => 'Copyright 2012-2026 Defiant Inc.',
						'license'     => 'Sample license text.',
						'license_url' => 'https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/',
					),
				),
				'attribution'  => 'Vulnerability data: Wordfence Intelligence',
				'count'        => 2,
				'max_severity' => JP4WC_Security_Scanner::max_rating( $items ),
				'items'        => $items,
			),
			false
		);

		ob_start();
		JP4WC_Security_Notice::render();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Japanized for WooCommerce セキュリティチェック', $html );
		$this->assertStringContainsString( '既知の脆弱性が 2 件見つかりました。', $html );
		$this->assertStringContainsString( '(深刻度: 高 / 修正版: 1.2.4)', $html );
		$this->assertStringContainsString( '(深刻度: 中 / 修正版: 1.2.4)', $html );
		$this->assertStringContainsString( '>' . esc_html( self::PACK_TRANSLATION ) . '</a>', $html, 'The language pack wins where it has a translation.' );
		$this->assertStringContainsString( '>ライセンス</a>', $html );
		$this->assert_filters_removed();
	}

	/**
	 * The Security tab's error messages come in Japanese.
	 */
	public function test_security_api_errors_use_the_bundled_translation() {
		$this->log_in( false );
		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/jp4wc/v1/security/findings' ) );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'セキュリティチェックを表示する権限がありません。', $response->get_data()['message'] );

		$this->log_in( true );
		$response = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/jp4wc/v1/security/rescan' ) );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'ページの有効期限が切れました。再読み込みしてからもう一度お試しください。', $response->get_data()['message'] );

		update_option( JP4WC_Security_Feed::RECHECK_OPTION, time(), false );
		$request = new WP_REST_Request( 'POST', '/jp4wc/v1/security/rescan' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 429, $response->get_status() );
		$this->assertMatchesRegularExpression( '/^少し前に再チェックを依頼しました。\d+ 秒後にもう一度お試しください。$/u', $response->get_data()['message'] );

		$this->assertSame( 200, rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/jp4wc/v1/security/findings' ) )->get_status() );
		$this->assert_filters_removed();
	}
}
