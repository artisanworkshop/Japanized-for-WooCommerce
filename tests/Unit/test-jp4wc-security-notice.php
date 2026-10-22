<?php
/**
 * Tests for the admin notice of the security check (issue #257).
 *
 * @package Japanized_For_WooCommerce
 */

/**
 * JP4WC_Security_Notice_Test
 */
class JP4WC_Security_Notice_Test extends WP_UnitTestCase {

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->assertTrue( class_exists( 'JP4WC_Security_Notice' ), 'The plugin loads the security notice at plugins_loaded.' );

		update_option( JP4WC_Security_Feed::SETTING, '1' );
		// Granted by hand: WooCommerce's shop_manager role is not there in every test environment (CI).
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		wp_get_current_user()->add_cap( 'manage_woocommerce' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( JP4WC_Security_Feed::FINDINGS_OPTION );
		wp_dequeue_script( JP4WC_Security_Notice::HANDLE );
		wp_deregister_script( JP4WC_Security_Notice::HANDLE );
		wp_dequeue_style( JP4WC_Security_Notice::HANDLE );
		wp_deregister_style( JP4WC_Security_Notice::HANDLE );
		parent::tearDown();
	}

	/**
	 * One record, as the scanner stores it.
	 *
	 * @param string $suffix           Last digits of the ID.
	 * @param string $rating           Rating, or ''.
	 * @param bool   $patched          Whether a fix exists.
	 * @param array  $patched_versions Fixed versions.
	 * @param string $title            Title.
	 * @return array
	 */
	private static function record( $suffix, $rating = 'High', $patched = true, $patched_versions = array( '1.2.4' ), $title = 'Sample - Cross-Site Scripting' ) {
		$id = '00000000-0000-4000-8000-' . str_pad( $suffix, 12, '0', STR_PAD_LEFT );
		return array(
			'id'               => $id,
			'title'            => $title,
			'reference'        => 'https://www.wordfence.com/threat-intel/vulnerabilities/id/' . $id,
			'rating'           => $rating,
			'score'            => '' === $rating ? null : 7.5,
			'patched'          => $patched,
			'patched_versions' => $patched_versions,
		);
	}

	/**
	 * One affected copy.
	 *
	 * @param string $type    Type.
	 * @param string $slug    Slug.
	 * @param array  $records Records.
	 * @param string $file    Plugin file.
	 * @return array
	 */
	private static function item( $type, $slug, array $records, $file = '' ) {
		return array(
			'type'            => $type,
			'slug'            => $slug,
			'name'            => ucwords( str_replace( '-', ' ', $slug ) ),
			'version'         => '1.2.3',
			'file'            => $file,
			'vulnerabilities' => $records,
		);
	}

	/**
	 * Stores findings as the scanner does.
	 *
	 * @param array $items Affected copies.
	 * @param array $extra Values to change.
	 */
	private static function store_findings( array $items, array $extra = array() ) {
		$count = 0;
		foreach ( $items as $item ) {
			$count += count( $item['vulnerabilities'] );
		}
		update_option(
			JP4WC_Security_Feed::FINDINGS_OPTION,
			array_merge(
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
					'count'        => $count,
					'max_severity' => JP4WC_Security_Scanner::max_rating( $items ),
					'items'        => $items,
				),
				$extra
			),
			false
		);
	}

	/**
	 * Prints the notice.
	 *
	 * @return string
	 */
	private static function render() {
		ob_start();
		JP4WC_Security_Notice::render();
		return ob_get_clean();
	}

	/**
	 * The notice is hooked to the admin screens.
	 */
	public function test_hooks_are_registered() {
		$this->assertNotFalse( has_action( 'admin_notices', array( 'JP4WC_Security_Notice', 'render' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( 'JP4WC_Security_Notice', 'enqueue' ) ) );
		$this->assertNotFalse( has_action( 'wp_ajax_' . JP4WC_Security_Notice::AJAX_ACTION, array( 'JP4WC_Security_Notice', 'ajax_dismiss' ) ) );
	}

	/**
	 * The affected software, its records with links, and the credits of the data.
	 */
	public function test_lists_the_records_with_their_links_and_the_credits() {
		self::store_findings(
			array(
				self::item( 'plugin', 'sample-plugin', array( self::record( '1', 'High', true, array( '1.2.4' ), 'Sample <script>alert(1)</script> XSS' ) ), 'sample-plugin/sample-plugin.php' ),
				self::item( 'theme', 'sample-theme', array( self::record( '101', '', false, array() ) ) ),
				self::item( 'core', 'wordpress', array( self::record( '201', 'Medium', true, array( '6.4.3', '6.5.1' ) ) ) ),
			)
		);

		$html = self::render();

		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'is-dismissible', $html );
		$this->assertStringContainsString( '3 known vulnerabilities were found', $html );
		$this->assertStringContainsString( 'Sample &lt;script&gt;alert(1)&lt;/script&gt; XSS', $html );
		$this->assertStringNotContainsString( '<script>alert(1)', $html );
		$this->assertStringContainsString( '<a href="https://www.wordfence.com/threat-intel/vulnerabilities/id/00000000-0000-4000-8000-000000000001" target="_blank" rel="noopener noreferrer">', $html );
		$this->assertStringContainsString( 'Severity: High / Fixed in 1.2.4', $html );
		$this->assertStringContainsString( 'No fixed version yet', $html );
		$this->assertStringContainsString( 'Severity: Medium / Fixed in 6.4.3, 6.5.1', $html );
		$this->assertStringContainsString( esc_url( admin_url( 'plugins.php' ) . '#checkbox_' . md5( 'sample-plugin/sample-plugin.php' ) ), $html );
		$this->assertStringContainsString( esc_url( admin_url( 'themes.php?theme=sample-theme' ) ), $html );
		$this->assertStringContainsString( esc_url( network_admin_url( 'update-core.php' ) ), $html );
		$this->assertStringContainsString( 'Vulnerability data: Wordfence Intelligence', $html );
		$this->assertStringContainsString( '<summary>Copyright 2012-2026 Defiant Inc.</summary>', $html );
		$this->assertStringContainsString( 'Sample license text.', $html );
		$this->assertStringContainsString( 'href="https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/"', $html );
		$this->assertStringNotContainsString( 'has not been updated', $html );
	}

	/**
	 * Without a High or Critical record, the notice is a warning.
	 */
	public function test_medium_findings_are_a_warning() {
		self::store_findings( array( self::item( 'plugin', 'sample-plugin', array( self::record( '1', 'Medium' ), self::record( '2', '' ) ) ) ) );

		$html = self::render();

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringNotContainsString( 'notice-error', $html );
	}

	/**
	 * Out-of-date data is shown even when nothing was found.
	 */
	public function test_stale_data_is_shown_without_findings() {
		self::store_findings( array(), array( 'stale' => true ) );

		$html = self::render();

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( 'The vulnerability data has not been updated for a while', $html );
		$this->assertStringNotContainsString( 'known vulnerabilit', $html );
		$this->assertStringContainsString( 'Vulnerability data: Wordfence Intelligence', $html );
	}

	/**
	 * Data generated more than three days ago counts as out of date.
	 */
	public function test_old_data_is_shown_as_stale() {
		self::store_findings( array(), array( 'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 4 * DAY_IN_SECONDS ) ) );

		$this->assertStringContainsString( 'has not been updated for a while', self::render() );
	}

	/**
	 * Cases with no notice.
	 *
	 * @return array<string, array{callable}>
	 */
	public function hidden_provider() {
		return array(
			'nothing found, fresh data' => array(
				static function () {
					self::store_findings( array() );
				},
			),
			'no match yet'              => array(
				static function () {
					delete_option( JP4WC_Security_Feed::FINDINGS_OPTION );
				},
			),
			'check turned off'          => array(
				static function () {
					self::store_findings( array( self::item( 'plugin', 'sample-plugin', array( self::record( '1' ) ) ) ) );
					add_filter( 'pre_option_' . JP4WC_Security_Feed::SETTING, '__return_empty_string' );
				},
			),
			'user cannot manage'        => array(
				static function () {
					self::store_findings( array( self::item( 'plugin', 'sample-plugin', array( self::record( '1' ) ) ) ) );
					wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
				},
			),
		);
	}

	/**
	 * No notice and no script in these cases.
	 *
	 * @dataProvider hidden_provider
	 *
	 * @param callable $arrange Sets up the case.
	 */
	public function test_no_notice( $arrange ) {
		$arrange();
		try {
			$this->assertSame( '', self::render() );
			JP4WC_Security_Notice::enqueue();
			$this->assertFalse( wp_script_is( JP4WC_Security_Notice::HANDLE, 'enqueued' ) );
		} finally {
			remove_filter( 'pre_option_' . JP4WC_Security_Feed::SETTING, '__return_empty_string' );
		}
	}

	/**
	 * The script that remembers a closed notice is added with the notice.
	 */
	public function test_enqueue_adds_the_script_with_the_notice() {
		self::store_findings( array( self::item( 'plugin', 'sample-plugin', array( self::record( '1' ) ) ) ) );

		JP4WC_Security_Notice::enqueue();

		$this->assertTrue( wp_script_is( JP4WC_Security_Notice::HANDLE, 'enqueued' ) );
		$script = implode( "\n", wp_scripts()->get_data( JP4WC_Security_Notice::HANDLE, 'after' ) );
		$this->assertStringContainsString( '.jp4wc-security-notice .notice-dismiss', $script );
		$this->assertStringContainsString( 'data-fingerprint', $script );
	}

	/**
	 * A long list is cut, and the rest is counted.
	 */
	public function test_long_list_is_cut() {
		$records = array();
		for ( $i = 1; $i <= JP4WC_Security_Notice::MAX_RECORDS + 5; $i++ ) {
			$records[] = self::record( (string) $i, 'Low' );
		}
		self::store_findings( array( self::item( 'plugin', 'sample-plugin', $records ) ) );

		$html = self::render();

		$this->assertSame( JP4WC_Security_Notice::MAX_RECORDS, substr_count( $html, 'target="_blank" rel="noopener noreferrer">Sample' ) );
		$this->assertStringContainsString( 'and 5 more', $html );
	}

	/**
	 * A closed notice stays closed until the findings change.
	 */
	public function test_closed_notice_comes_back_when_the_findings_change() {
		$items = array( self::item( 'plugin', 'sample-plugin', array( self::record( '1' ) ) ) );
		self::store_findings( $items );
		$view = JP4WC_Security_Notice::get_view();
		$this->assertNotNull( $view );
		$this->assertStringContainsString( 'data-fingerprint="' . $view['fingerprint'] . '"', self::render() );

		update_user_meta( get_current_user_id(), JP4WC_Security_Notice::DISMISSED_META, $view['fingerprint'] );
		$this->assertSame( '', self::render() );

		// The same findings, matched again later.
		self::store_findings( $items, array( 'checked_at' => time() + 60 ) );
		$this->assertSame( '', self::render() );

		$items[0]['vulnerabilities'][] = self::record( '2' );
		self::store_findings( $items );
		$this->assertStringContainsString( 'notice-error', self::render(), 'A new record shows the notice again.' );

		self::store_findings( array( self::item( 'plugin', 'sample-plugin', array( self::record( '1' ) ) ) ), array( 'stale' => true ) );
		$this->assertStringContainsString( 'has not been updated', self::render(), 'Data going out of date shows the notice again.' );
	}
}
