<?php
/**
 * Paidy for WooCommerce main class file.
 *
 * @package paidy-wc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Paidy' ) ) :

	/**
	 * Main class for Paidy for WooCommerce.
	 *
	 * @package paidy-wc
	 */
	class WC_Paidy {
		/**
		 * Paidy for WooCommerce Framework version.
		 *
		 * @var string
		 */
		public $framework_version = '2.0.14';

		/**
		 * The reference to the *Singleton* instance of this class.
		 *
		 * @var Singleton
		 */
		private static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Protected constructor to prevent creating a new instance of the
		 * *Singleton* via the `new` operator from outside of this class.
		 *
		 * Paidy for WooCommerce Constructor.
		 *
		 * @access public
		 */
		private function __construct() {
			// WC4JP Framework version.
			define( 'JP4WC_PAIDY_FRAMEWORK_VERSION', $this->framework_version );
			// Paidy for WooCommerce plugin url.
			define( 'WC_PAIDY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			define( 'WC_PAIDY_ASSETS_URL', WC_PAIDY_PLUGIN_URL . 'assets/' );
			define( 'WC_PAIDY_BLOCKS_URL', WC_PAIDY_PLUGIN_URL . 'assets/js/build/paidy/' ); // Paidy for WooCommerce blocks assets.
			define( 'WC_PAIDY_ABSPATH', __DIR__ . '/' );
			define( 'WC_PAIDY_ASSETS_ABSPATH', WC_PAIDY_ABSPATH . 'assets/js/build/paidy/' ); // Paidy for WooCommerce assets.
			// Paidy for WooCommerce plugin file.
			define( 'WC_PAIDY_PLUGIN_FILE', __FILE__ );
			$this->init();
			// Include required files.
			$this->includes();
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		private function includes() {
			// load framework.
			$version_text = 'v' . str_replace( '.', '_', JP4WC_PAIDY_FRAMEWORK_VERSION );
			if ( ! class_exists( '\\ArtisanWorkshop\\PluginFramework\\' . $version_text . '\\JP4WC_Framework' ) ) {
				require_once __DIR__ . '/includes/jp4wc-framework/class-jp4wc-framework.php';
			}
			require_once __DIR__ . '/includes/gateways/paidy/class-wc-gateway-paidy.php';

			// Endpoints.
			if ( ! class_exists( 'WC_Paidy_Endpoint' ) ) {
				require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-endpoint.php';
				new WC_Paidy_Endpoint();
			}

			// Admin dashboard.
			if ( ! class_exists( 'WC_Paidy_Apply_Admin_Dashboard' ) ) {
				require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-apply-admin-dashboard.php';
				new WC_Paidy_Apply_Admin_Dashboard();
			}
		}

		/**
		 * Init Paidy for WooCommerce when WordPress Initialises.
		 */
		public function init() {
			// WooCommerce Blocks support.
			add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'wc_paidy_blocks_support' ) );
		}

		/**
		 * Registers WooCommerce Blocks integration.
		 */
		public static function wc_paidy_blocks_support() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
						require_once 'includes/gateways/paidy/class-wc-payments-paidy-blocks-support.php';
						$payment_method_registry->register( new WC_Payments_Paidy_Blocks_Support() );
					}
				);
			}
		}
	}
endif;
