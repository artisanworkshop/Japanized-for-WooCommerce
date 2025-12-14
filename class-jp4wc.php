<?php
/**
 * Main class file for Japanized for WooCommerce
 *
 * @package Japanized for WooCommerce
 * @since 2.7.15
 */

if ( ! class_exists( 'JP4WC' ) ) :

	/**
	 * Main class for Japanized for WooCommerce
	 *
	 * @package Japanized for WooCommerce
	 * @since 1.0.0
	 */
	class JP4WC {

		/**
		 * Japanized for WooCommerce Framework version.
		 *
		 * @var string
		 */
		public $framework_version = '2.0.14';

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
			$this->init();
			// change paypal checkout for japan.
			add_filter( 'woocommerce_paypal_express_checkout_paypal_locale', array( &$this, 'jp4wc_paypal_locale' ) );
			add_filter( 'woocommerce_paypal_express_checkout_request_body', array( &$this, 'jp4wc_paypal_button_source' ) );
			// change amazon pay PlatformId for japan.
			add_filter( 'woocommerce_amazon_pa_api_request_args', array( &$this, 'jp4wc_amazon_pay' ) );
			// rated appeal.
			add_action( 'wp_ajax_wc4jp_rated', array( __CLASS__, 'jp4wc_rated' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
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
			$this->includes();
			add_action( 'init', array( $this, 'on_plugins_loaded' ), 20 );
			add_action( 'woocommerce_blocks_loaded', array( $this, 'jp4wc_blocks_support' ) );
			if ( ! get_transient( 'jp4wc_first_installing' ) ) {
				// First time installing.
				set_transient( 'jp4wc_first_installing', 'yes', 180 * DAY_IN_SECONDS );
			}
		}

		/**
		 * Setup plugin once all other plugins are loaded.
		 *
		 * @return void
		 */
		public function on_plugins_loaded() {
			$this->load_plugin_textdomain();
		}

		/**
		 * Define Constants.
		 */
		protected function define_constants() {
			define( 'JP4WC_URL_PATH', plugins_url( '/', __FILE__ ) );
			define( 'JP4WC_ABSPATH', __DIR__ . '/' );
			define( 'JP4WC_INCLUDES_PATH', JP4WC_ABSPATH . 'includes/' );
			define( 'JP4WC_PLUGIN_FILE', __FILE__ );
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
			if ( ! class_exists( '\\ArtisanWorkshop\\PluginFramework\\' . $version_text . '\\JP4WC_Framework' ) ) {
				require_once JP4WC_INCLUDES_PATH . 'jp4wc-framework/class-jp4wc-framework.php';
			}
			// common functions.
			require_once JP4WC_INCLUDES_PATH . 'jp4wc-common-functions.php';

			// Usage tracking.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-usage-tracking.php';
			if ( class_exists( 'JP4WC_Usage_Tracking' ) ) {
				JP4WC_Usage_Tracking::init();
			}

			// Install.
			require_once JP4WC_INCLUDES_PATH . 'class-jp4wc-install.php';
			// Admin Setting Screen.
			require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-admin-settings.php';
			require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-admin-product-meta.php';

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
			require_once JP4WC_INCLUDES_PATH . 'gateways/cod/class-wc-addons-gateway-cod2.php';

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
				if ( isset( $current_screen->id ) && $current_screen->id === $wc4jp_pages ) {
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
				if ( get_option( 'wc4jp-cod2' ) ) {
					add_action(
						'woocommerce_blocks_payment_method_type_registration',
						function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
							require_once 'includes/blocks/class-wc-payments-cod2-blocks-support.php';
							$payment_method_registry->register( new WC_Payments_Cod2_Blocks_Support() );
						}
					);
				}
			}

			// CRITICAL: Register delivery fields EARLY on woocommerce_init.
			// Additional Checkout Fields API requires registration before blocks are initialized.
			add_action(
				'woocommerce_init',
				function () {
					require_once 'includes/blocks/class-jp4wc-delivery-blocks-integration.php';
					$integration = new JP4WC_Delivery_Blocks_Integration();
					// Register fields immediately on woocommerce_init.
					$integration->register_checkout_fields();
				},
				5 // Early priority.
			);

			// Register block integration for UI rendering.
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function ( $integration_registry ) {
					require_once 'includes/blocks/class-jp4wc-delivery-blocks-integration.php';
					$integration_registry->register( new JP4WC_Delivery_Blocks_Integration() );
				}
			);

			// CRITICAL: Register yomigana fields EARLY on woocommerce_init.
			// Additional Checkout Fields API requires registration before blocks are initialized.
			add_action(
				'woocommerce_init',
				function () {
					require_once 'includes/blocks/class-jp4wc-yomigana-blocks-integration.php';
					$integration = new JP4WC_Yomigana_Blocks_Integration();
					// Register fields immediately on woocommerce_init.
					$integration->register_checkout_fields();
				},
				5 // Early priority.
			);

			// Register yomigana block integration for UI rendering.
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function ( $integration_registry ) {
					require_once 'includes/blocks/class-jp4wc-yomigana-blocks-integration.php';
					$integration_registry->register( new JP4WC_Yomigana_Blocks_Integration() );
				}
			);
		}       /**
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

			return $methods;
		}
	}

endif;
