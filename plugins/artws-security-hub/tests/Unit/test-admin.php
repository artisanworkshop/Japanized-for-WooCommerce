<?php
/**
 * Tests for Artws_Sec_Admin.
 *
 * @package Artws_Security_Hub
 */

/**
 * Artws_Sec_Admin_Test
 */
class Artws_Sec_Admin_Test extends Artws_Sec_Feed_Test_Case {

	/**
	 * Set up: an administrator, and redirects stop the request instead of exiting.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter(
			'wp_redirect',
			static function ( $location ) {
				throw new RuntimeException( 'redirect:' . $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Read by the test only.
			}
		);
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		unset( $_REQUEST['_wpnonce'] );
		wp_clear_scheduled_hook( Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) );
		parent::tear_down();
	}

	/**
	 * Runs an admin-post handler and returns where it redirected.
	 *
	 * @param callable $handler Handler.
	 * @return string
	 */
	private static function run_handler( callable $handler ) {
		try {
			$handler();
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}
		return '';
	}

	/**
	 * Valid versions are saved.
	 */
	public function test_sanitize_eol_keeps_valid_versions() {
		$this->assertSame(
			array(
				'php'         => '8.3',
				'wordpress'   => '6.8.1',
				'woocommerce' => '10',
			),
			Artws_Sec_Admin::sanitize_eol(
				array(
					'php'         => ' 8.3 ',
					'wordpress'   => '6.8.1',
					'woocommerce' => '10',
				)
			)
		);
		$this->assertSame( array(), get_settings_errors( Artws_Sec_Feed_Updater::EOL_OPTION ) );
	}

	/**
	 * An invalid version keeps the saved one and reports an error.
	 */
	public function test_sanitize_eol_rejects_invalid_versions() {
		update_option(
			Artws_Sec_Feed_Updater::EOL_OPTION,
			array(
				'php'         => '8.3',
				'wordpress'   => '6.8',
				'woocommerce' => '10.3',
			)
		);

		$saved = Artws_Sec_Admin::sanitize_eol(
			array(
				'php'         => '8.4-dev',
				'wordpress'   => '<b>7</b>',
				'woocommerce' => '10.4',
			)
		);

		$this->assertSame(
			array(
				'php'         => '8.3',
				'wordpress'   => '6.8',
				'woocommerce' => '10.4',
			),
			$saved
		);
		$this->assertCount( 2, get_settings_errors( Artws_Sec_Feed_Updater::EOL_OPTION ) );
		$this->assertSame( Artws_Sec_Feed_Updater::get_eol(), Artws_Sec_Admin::sanitize_eol( 'not an array' ) );
	}

	/**
	 * "Update now" schedules a one-off run that does not clash with the daily event.
	 */
	public function test_update_now_schedules_a_run() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'artws_sec_run_update' );

		$redirect = self::run_handler( array( 'Artws_Sec_Admin', 'handle_run_update' ) );

		$this->assertStringContainsString( 'artws_sec_notice=scheduled', $redirect );
		$this->assertNotFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) ) );
	}

	/**
	 * "Allow fewer records" sets the one-time permission.
	 */
	public function test_allow_drop_sets_the_option() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'artws_sec_accept_drop' );

		$redirect = self::run_handler( array( 'Artws_Sec_Admin', 'handle_accept_drop' ) );

		$this->assertStringContainsString( 'artws_sec_notice=drop_accepted', $redirect );
		$this->assertSame( '1', (string) get_option( Artws_Sec_Feed_Updater::ACCEPT_DROP_OPTION ) );
	}

	/**
	 * The handlers need the nonce.
	 */
	public function test_handlers_need_a_nonce() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'another_action' );

		$this->expectException( WPDieException::class );
		self::run_handler( array( 'Artws_Sec_Admin', 'handle_accept_drop' ) );
	}

	/**
	 * The handlers need manage_options.
	 */
	public function test_handlers_need_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'artws_sec_run_update' );

		try {
			self::run_handler( array( 'Artws_Sec_Admin', 'handle_run_update' ) );
			$this->fail( 'Expected wp_die().' );
		} catch ( WPDieException $e ) {
			$this->assertFalse( wp_next_scheduled( Artws_Sec_Feed_Updater::UPDATE_HOOK, array( 'manual' ) ) );
		}
	}

	/**
	 * The page escapes what came from the feed.
	 */
	public function test_page_escapes_feed_values() {
		update_option(
			Artws_Sec_Feed_Updater::STATUS_OPTION,
			array(
				'last_attempt' => 1792638000,
				'result'       => 'failure',
				'message'      => 'Wordfence answered <b>HTTP 500</b>.',
				'stats'        => array(
					'records'          => 2,
					'informational'    => 0,
					'candidates'       => 2,
					'excluded'         => 1,
					'excluded_reasons' => array( 'software.slug' => 1 ),
					'excluded_samples' => array(
						array(
							'id'     => '<script>alert(1)</script>',
							'slug'   => '"><img src=x>',
							'reason' => 'software.slug',
						),
					),
				),
			),
			false
		);

		ob_start();
		Artws_Sec_Admin::render_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( '2026-10-22 03:00:00 UTC: failure', $html );
		$this->assertStringContainsString( 'Wordfence answered &lt;b&gt;HTTP 500&lt;/b&gt;.', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
		$this->assertStringNotContainsString( '<script>alert(1)', $html );
		$this->assertStringNotContainsString( '<img src=x>', $html );
		$this->assertStringContainsString( 'name="artws_sec_feed_eol[php]"', $html );
	}
}
