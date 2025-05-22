<?php
/**
 * Plugin Name: Japanized for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-for-japan/
 * Description: Woocommerce toolkit for Japanese use.
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * Version: 2.6.43
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 6.8.1
 * WC requires at least: 6.0
 * WC tested up to: 9.8.5
 *
 * Text Domain: woocommerce-for-japan
 * Domain Path: /i18n/
 *
 * @package woocommerce-for-japan
 * @category Core
 * @author Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'JP4WC' ) ) :

	/**
	 * Main class for Japanized for WooCommerce
	 *
	 * @package Japanized for WooCommerce
	 * @since 1.0.0
	 */
	class JP4WC {

		/**
		 * Japanized for WooCommerce version.
		 *
		 * @var string
		 */
		public $version = '2.6.43';

		/**
		 * Japanized for WooCommerce Framework version.
		 *
		 * @var string
		 */
		public $framework_version = '2.0.12';

		/**
		 * The single instance of the class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Japanized for WooCommerce Constructor.
		 *
		 * @access public
		 */
		public function __construct() {
			// change paypal checkout for japan.
			add_filter( 'woocommerce_paypal_express_checkout_paypal_locale', array( &$this, 'jp4wc_paypal_locale' ) );
			add_filter( 'woocommerce_paypal_express_checkout_request_body', array( &$this, 'jp4wc_paypal_button_source' ) );
			// change amazon pay PlatformId for japan.
			add_filter( 'woocommerce_amazon_pa_api_request_args', array( &$this, 'jp4wc_amazon_pay' ) );
			// rated appeal.
			add_action( 'wp_ajax_wc4jp_rated', array( __CLASS__, 'jp4wc_rated' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
			// handle HPOS compatibility.
			add_action( 'before_woocommerce_init', array( $this, 'jp4wc_handle_hpos_compatibility' ) );
			// Add COD gateway for fee.
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_jp4wc_custom_cod_gateway' ) );
		}

		/**
		 * Get class instance.
		 *
		 * @return object Instance.
		 */
		public static function instance() {
			if ( null === static::$instance ) {
				static::$instance = new static();
			}
			return static::$instance;
		}

		/**
		 * Init the feature plugin, only if we can detect WooCommerce.
		 *
		 * @since 2.0.0
		 * @version 2.0.0
		 */
		public function init() {
			$this->define_constants();
			register_deactivation_hook( JP4WC_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 20 );
			add_action( 'woocommerce_blocks_loaded', array( $this, 'jp4wc_blocks_support' ) );
			if ( ! get_transient( 'jp4wc_first_installing' ) ) {
				// First time installing.
				set_transient( 'jp4wc_first_installing', 'yes', 180 * DAY_IN_SECONDS );
				update_option( 'wc4jp-tracking', true );
			}
		}

		/**
		 * Flush rewrite rules on deactivate.
		 *
		 * @return void
		 */
		public function on_deactivation() {
			flush_rewrite_rules();
			do_action( 'woocommerce_paypal_payments_gateway_deactivate' );
		}

		/**
		 * Setup plugin once all other plugins are loaded.
		 *
		 * @return void
		 */
		public function on_plugins_loaded() {
			$this->load_plugin_textdomain();
			$this->includes();
		}

		/**
		 * Define Constants.
		 */
		protected function define_constants() {
			define( 'JP4WC_ABSPATH', __DIR__ . '/' );
			define( 'JP4WC_INCLUDES_PATH', JP4WC_ABSPATH . 'includes/' );
			define( 'JP4WC_URL_PATH', plugins_url( '/', __FILE__ ) );
			define( 'JP4WC_PLUGIN_FILE', __FILE__ );
			define( 'JP4WC_VERSION', $this->version );
			define( 'JP4WC_FRAMEWORK_VERSION', $this->framework_version );
		}

		/**
		 * Load Localisation files.
		 */
		protected function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-for-japan', false, basename( __DIR__ ) . '/i18n' );
		}

		/**
		 * Include JP4WC classes.
		 */
		private function includes() {
			// load framework.
			$version_text = 'v' . str_replace( '.', '_', JP4WC_FRAMEWORK_VERSION );
			if ( ! class_exists( '\\ArtisanWorkshop\\WooCommerce\\PluginFramework\\' . $version_text . '\\JP4WC_Plugin' ) ) {
				require_once JP4WC_INCLUDES_PATH . 'jp4wc-framework/class-jp4wc-framework.php';
			}
			// common functions.
			require_once JP4WC_INCLUDES_PATH . 'jp4wc-common-functions.php';

			// Usage tracking.
			if ( 'yes' === get_transient( 'jp4wc_first_installing' ) ) {
				require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-usage-tracking.php';
				// Usage tracking.
				if ( ! class_exists( 'JP4WC_Usage_Tracking' ) ) {
					JP4WC_Usage_Tracking::init();
				}
			}
			// Install.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-install.php';
			// Admin Setting Screen.
			require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-admin.php';
			// Admin Security Screen.
			require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-check-security.php';
			require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-malware-check.php';
			new JP4WC_Check_Security();

			// Admin PR notice.
			require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-admin-notices.php';

			// Payment Gateway For Bank.
			require_once JP4WC_INCLUDES_PATH . 'gateways/bank-jp/class-wc-gateway-bank-jp.php';
			// Payment Gateway For Post Office Bank.
			require_once JP4WC_INCLUDES_PATH . 'gateways/postofficebank/class-wc-gateway-postofficebank-jp.php';
			// Payment Gateway at Real Store.
			require_once JP4WC_INCLUDES_PATH . 'gateways/atstore/class-wc-gateway-atstore-jp.php';
			// Payment Gateway For COD subscriptions.
			require_once JP4WC_INCLUDES_PATH . 'gateways/cod/class-wc-gateway-cod2.php';
			require_once JP4WC_INCLUDES_PATH . 'gateways/cod/class-wc-addons-gateway-cod.php';

			// Address Setting.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-address-fields.php';
			// Automatic address entry from zip code using Yahoo API.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-address-yahoo-auto-entry.php';
			// Delivery Setting.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-delivery.php';
			// ADD COD Fee.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-cod-fee.php';
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-cod-fee-handler.php';

			// ADD Shortcodes.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-shortcodes.php';
			// Add Free Shipping display.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-free-shipping.php';
			// Add Custom E-mail.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-custom-email.php';
			// Add Payments setting.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-payments.php';
			// Add PayPal Checkout(New from 2023/05 ).
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! is_plugin_active( 'woocommerce-paypal-payments/woocommerce-paypal-payments.php' ) &&
				! isset( $_GET['action'] ) &&
				! isset( $_REQUEST['plugin'] ) &&
				! ( isset( $_REQUEST['action'] ) &&
					isset( $_REQUEST['_wpnonce'] ) &&
					wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'activate-plugin_' . sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ?? '' ) ) ) &&
					'activate' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) &&
					isset( $_REQUEST['plugin'] ) &&
					false !== strpos( sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ), 'woocommerce-paypal-payments/woocommerce-paypal-payments.php' ) ) ) {
				require_once JP4WC_INCLUDES_PATH . 'gateways/paypal/woocommerce-paypal-payments.php';
			}

			// Include the main WooCommerce class.
			if ( ! class_exists( 'WC_Gateway_Paidy', false ) ) {
				// Add Paidy Checkout.
				require_once JP4WC_INCLUDES_PATH . 'gateways/paidy/class-wc-gateway-paidy.php';
				require_once JP4WC_INCLUDES_PATH . 'gateways/paidy/class-wc-paidy-endpoint.php';
				require_once JP4WC_INCLUDES_PATH . 'gateways/paidy/class-wc-paidy-admin-notices.php';
			}
			// Add affiliates setting.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-affiliate.php';
			// Add Subscriptions setting.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-subscriptions.php';
			// Add Virtual setting.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-virtual.php';
		}

		/**
		 * Set PayPal Checkout setting Japan for Artisan Workshop.
		 *
		 * @since  2.0.0
		 * @param  string $locale PayPal locale.
		 * @return string
		 */
		public function jp4wc_paypal_locale( $locale ) {
			$locale = 'ja_JP';

			return $locale;
		}

		/**
		 * Set PayPal Checkout for Artisan Workshop.
		 *
		 * @param array $body PayPal request arguments.
		 * @return array
		 */
		public function jp4wc_paypal_button_source( $body ) {
			if ( isset( $body['BUTTONSOURCE'] ) ) {
				$body['BUTTONSOURCE'] = 'ArtisanWorkshop_Cart_EC_JP';
			}
			return $body;
		}

		/**
		 * Set Amazon Pay PlatformId for Artisan Workshop.
		 *
		 * @param array $args Amazon Pay request arguments.
		 * @return array
		 */
		public function jp4wc_amazon_pay( $args ) {
			if ( isset( $args['OrderReferenceAttributes.PlatformId'] ) ) {
				$args['OrderReferenceAttributes.PlatformId'] = 'A2Q9IBPXOLHU7H';
			}
			return $args;
		}

		/**
		 * Change the admin footer text on WooCommerce for Japan admin pages.
		 *
		 * @since  1.2
		 * @version 2.0.0
		 * @param  string $footer_text footer text.
		 * @return string
		 */
		public function admin_footer_text( $footer_text ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $footer_text;
			}
			if ( function_exists( 'get_current_screen' ) ) :
				$current_screen = get_current_screen();
				$wc4jp_pages    = 'woocommerce_page_wc4jp-options';
				// Check to make sure we're on a WooCommerce admin page.
				if ( isset( $current_screen->id ) && $current_screen->id == $wc4jp_pages ) {
					if ( ! get_option( 'wc4jp_admin_footer_text_rated' ) ) {
						/* translators: %1$s and %2$s are HTML tags for a link that wraps around the five-star rating. %1$s opens the link and %2$s closes it. The &#9733; characters represent star symbols that will be displayed in the rating. */
						$footer_text = sprintf( __( 'If you like <strong>Japanized for WooCommerce</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. A huge thanks in advance!', 'woocommerce-for-japan' ), '<a href="https://wordpress.org/support/plugin/woocommerce-for-japan/reviews?rate=5#new-post" target="_blank" class="wc4jp-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'woocommerce-for-japan' ) . '">', '</a>' );
						wc_enqueue_js(
							"
					jQuery( 'a.wc4jp-rating-link' ).click( function() {
						jQuery.post( '" . WC()->ajax_url() . "', { action: 'wc4jp_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
				"
						);
					} else {
						$footer_text = __( 'Thank you for installing with Japanized for WooCommerce.', 'woocommerce-for-japan' );
					}
				}
			endif;
			return $footer_text;
		}

		/**
		 * Triggered when clicking the rating footer.
		 */
		public static function jp4wc_rated() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				die( -1 );
			}

			update_option( 'wc4jp_admin_footer_text_rated', 1 );
			die();
		}

		/**
		 * Registers WooCommerce Blocks integration.
		 */
		public static function jp4wc_blocks_support() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				if ( get_option( 'wc4jp-postofficebank' ) ) {
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							require_once 'includes/blocks/class-wc-payments-postofficebank-blocks-support.php';
							$payment_method_registry->register( new WC_Payments_PostOfficeBank_Blocks_Support() );
						}
					);
				}
				if ( get_option( 'wc4jp-bankjp' ) ) {
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							require_once 'includes/blocks/class-wc-payments-bank-jp-blocks-support.php';
							$payment_method_registry->register( new WC_Payments_BANK_JP_Blocks_Support() );
						}
					);
				}
				if ( get_option( 'wc4jp-atstore' ) ) {
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							require_once 'includes/blocks/class-wc-payments-atstore-blocks-support.php';
							$payment_method_registry->register( new WC_Payments_AtStore_Blocks_Support() );
						}
					);
				}

				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
						require_once 'includes/gateways/paidy/class-wc-payments-paidy-blocks-support.php';
						$payment_method_registry->register( new WC_Gateway_Paidy_Blocks_Support() );
					}
				);
			}
		}

		/**
		 * Declares HPOS compatibility if the plugin is compatible with HPOS.
		 *
		 * @internal
		 *
		 * @since 2.6.0
		 */
		public function jp4wc_handle_hpos_compatibility() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				$slug = dirname( plugin_basename( __FILE__ ) );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', trailingslashit( $slug ) . $slug . '.php', true );
			}
		}

		/**
		 * Add the gateway to WooCommerce
		 *
		 * @param array $methods Payment methods.
		 * @return array $methods Payment methods.
		 */
		public function add_jp4wc_custom_cod_gateway( $methods ) {
			// Add the COD gateway for Fee.
			$methods[] = 'JP4WC_COD_Fee';
			$key       = array_search( 'WC_Gateway_COD', $methods, true );
			if ( false !== $key ) {
				unset( $methods[ $key ] );
			}

			// Add the COD2 gateway.
			if ( get_option( 'wc4jp-cod2' ) ) {
				if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
					$subscription_support_enabled = true;
				}
				if ( isset( $subscription_support_enabled ) ) {
					$methods[] = 'WC_Addons_Gateway_COD2';
				} else {
					$methods[] = 'WC_Gateway_COD2';
				}
			}

			return $methods;
		}
	}

endif;

/**
 * Load plugin functions.
 */
add_action( 'plugins_loaded', 'JP4WC_plugin' );

function JP4WC_plugin() {
	if ( is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {
		JP4WC::instance()->init();
	} else {
		add_action( 'admin_notices', 'jp4wc_fallback_notice' );
	}
}

function jp4wc_fallback_notice() {
	?>
	<div class="error">
		<ul>
			<li><?php esc_html_e( 'Japanized for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'woocommerce-for-japan' ); ?></li>
		</ul>
	</div>
	<?php
}

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		if ( ! isset( $active_plugins ) ) {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}
		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}

// Garbled characters in e-mail.
add_filter( 'woocommerce_order_shipping_to_display', 'wc4jp_display_shipping', 10, 1 );
// Change from &nbsp; to space.
function wc4jp_display_shipping( $shipping ) {
	$shipping = str_replace( '&nbsp;', ' ', $shipping );
	return $shipping;
}
