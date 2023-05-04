<?php
/**
 * Peachpay_Dependency_Service class
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Validates that PeachPay's dependency plugins are active and of a compatible version.
 * Displays an error on the admin page in the case of a missing or old plugin.
 * Plugin init is responsible for aborting plugin initialization in the case of missing dependencies.
 */
class PeachPay_Dependency_Service {

	/**
	 * Tracks the status of the woocommerce dependency.
	 *
	 * @var bool
	 */
	private $woocommerce_missing;

	/**
	 * Tracks whether the WP Permalink setting is set to plain, which is the only permalink setting that PeachPay won't work with.
	 *
	 * @var bool
	 */
	private $permalink_plain;

	/**
	 * Constructor method. This PHP magic method is called automatically as the class is instantiated.
	 */
	public function __construct() {
		// Populates the status of dependencies.
		self::evaluate_dependencies();
		// Filters the notice on the plugin page (i.e. if there are missing dependencies).
		add_filter( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Creates and displays admin notices on the admin/plugin page.
	 *
	 * @return null.
	 */
	public function display_admin_notices() {
		// Alerts not needed when plugins are installing.
		if ( self::is_plugin_install_page() ) {
			return;
		}

		if ( ! self::all_dependencies_valid() ) {
			self::display_admin_errors();
		}
	}

	/**
	 * Evaluates PeachPay's dependencies and updates this object's relevant status values.
	 *
	 * @return void.
	 */
	public function evaluate_dependencies() {
		// Modular dependency detection. Expand for more/more specific dependencies.
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$this->woocommerce_missing = true;
		} else {
			$this->woocommerce_missing = false;
		}
		if ( empty( get_option( 'permalink_structure' ) ) ) {
			$this->permalink_plain = true;
		} else {
			$this->permalink_plain = false;
		}
	}

	/**
	 * Returns the overall status of the app's dependencies.
	 *
	 * @return bool True if all dependencies are met, false otherwise.
	 */
	public function all_dependencies_valid() {
		// Add more dependencies here as they become relevant.
		if ( $this->woocommerce_missing ) {
			return false;
		} elseif ( $this->permalink_plain ) {
			return false;
		}
		return true;
	}

	/**
	 * Prints the given message in an "admin notice" wrapper with provided classes.
	 * CANNOT be abstracted as echo requires literal strings to conform with translation requirements.
	 *
	 * @return void.
	 */
	public function display_admin_errors() {
		// Modular dependency-related notices.
		if ( $this->woocommerce_missing ) {
			?>
			<div class="notice peachpay-notice <?php echo esc_attr( 'notice-error' ); ?>">
				<p><b><?php echo esc_html_e( 'PeachPay for WooCommerce', 'peachpay-for-woocommerce' ); ?></b></p>
				<p><?php echo esc_html_e( 'PeachPay requires the WooCommerce plugin to be installed and active.', 'peachpay-for-woocommerce' ); ?></p>
			</div>
			<?php
		}
		if ( $this->permalink_plain ) {
			?>
			<div class="notice peachpay-notice <?php echo esc_attr( 'notice-error' ); ?>">
				<p><b><?php echo esc_html_e( 'PeachPay for WooCommerce', 'peachpay-for-woocommerce' ); ?></b></p>
				<p>
				<?php
				echo esc_html_e( 'PeachPay requires the WordPress permalink structure to be set to anything other than "Plain". ', 'peachpay-for-woocommerce' );
					echo '<a href="' . esc_url_raw( site_url( '/wp-admin/options-permalink.php' ) ) . '">';
					echo esc_html_e( 'Change this setting here', 'peachpay-for-woocommerce' );
					echo '</a>';
				?>
					</p>
			</div>
			<?php
		}
		// Add more error messages here.
	}

	/**
	 * Checks if current page is plugin installation process page.
	 *
	 * @return bool True when installing plugin.
	 */
	private static function is_plugin_install_page() {
		$cur_screen = get_current_screen();
		return $cur_screen && 'update' === $cur_screen->id && 'plugins' === $cur_screen->parent_base;
	}

}
