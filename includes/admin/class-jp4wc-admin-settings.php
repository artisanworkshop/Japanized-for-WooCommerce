<?php
/**
 * JP4WC Admin Settings Class
 *
 * @package JP4WC
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * JP4WC Admin Settings Class by block.
 *
 * @class    JP4WC_Admin_Settings
 * @version  3.0.0
 * @package  JP4WC/Admin
 * @author   Shohei Tanaka
 */
class JP4WC_Admin_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Add settings page to WordPress admin.
	 */
	public function add_settings_page() {
		$menu_title = __( 'JP4WC Settings', 'woocommerce-for-japan' );
		add_submenu_page(
			'woocommerce',
			__( 'JP4WC Settings (Block)', 'woocommerce-for-japan' ),
			$menu_title,
			'manage_woocommerce',
			'jp4wc-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<div id="jp4wc-admin-settings-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our settings page.
		if ( 'woocommerce_page_jp4wc-settings' !== $hook ) {
			return;
		}

		$asset_file_path = JP4WC_ABSPATH . 'assets/js/build/admin/settings.asset.php';

		if ( ! file_exists( $asset_file_path ) ) {
			return;
		}

		$asset_file = include $asset_file_path;

		wp_enqueue_script(
			'jp4wc-admin-settings',
			JP4WC_URL_PATH . 'assets/js/build/admin/settings.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		// Set script translations.
		wp_set_script_translations(
			'jp4wc-admin-settings',
			'woocommerce-for-japan',
			JP4WC_ABSPATH . 'i18n'
		);

		wp_enqueue_style(
			'jp4wc-admin-settings',
			JP4WC_URL_PATH . 'assets/js/build/admin/settings.css',
			array( 'wp-components' ),
			$asset_file['version']
		);

		// Add inline settings.
		wp_localize_script(
			'jp4wc-admin-settings',
			'jp4wcSettings',
			array(
				'apiUrl'   => rest_url( 'jp4wc/v1/settings' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'siteUrl'  => get_site_url(),
				'adminUrl' => admin_url(),
			)
		);
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-settings-api.php';
		$controller = new JP4WC_Settings_API();
		$controller->register_routes();
	}
}

new JP4WC_Admin_Settings();
