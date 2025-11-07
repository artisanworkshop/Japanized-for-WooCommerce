<?php
/**
 * Plugin Name: Japanized for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-for-japan/
 * Description: Woocommerce toolkit for Japanese use.
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * Version: 2.7.16
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Requires at least: 6.7
 * Tested up to: 6.8.3
 * WC requires at least: 8.0
 * WC tested up to: 10.3.4
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

define( 'JP4WC_VERSION', '2.7.16' );

require_once __DIR__ . '/class-jp4wc.php';

// Register activation hook.
register_activation_hook( __FILE__, 'jp4wc_activation_redirect' );
// deactivation.
register_deactivation_hook( __FILE__, 'jp4wc_on_deactivation' );

/**
 * Add option for activation redirect.
 *
 * @return void
 */
function jp4wc_activation_redirect() {
	add_option( 'paidy_do_activation_redirect', true );
}

/**
 * Flush rewrite rules on deactivate.
 *
 * @return void
 */
function jp4wc_on_deactivation() {
	add_option( 'paidy_do_activation_redirect', true );
	flush_rewrite_rules();
	do_action( 'woocommerce_paypal_payments_gateway_deactivate' );
}


/**
 * Load plugin functions.
 */
add_action( 'plugins_loaded', 'jp4wc_plugin' );

/**
 * Initialize JP4WC plugin when plugins are loaded.
 *
 * @return void
 */
function jp4wc_plugin() {
	if ( is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {
		JP4WC::instance();
	} else {
		add_action( 'admin_notices', 'jp4wc_fallback_notice' );
	}
}

/**
 * Display fallback notice when WooCommerce is not active.
 *
 * @return void
 */
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
	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	function is_woocommerce_active() {
		if ( ! isset( $active_plugins ) ) {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}
		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}

// Garbled characters in e-mail.
add_filter( 'woocommerce_order_shipping_to_display', 'wc4jp_display_shipping', 10, 1 );

/**
 * Replace non-breaking spaces in shipping display to prevent garbled characters.
 *
 * @param string $shipping The shipping display string.
 * @return string The modified shipping display string.
 */
function wc4jp_display_shipping( $shipping ) {
	$shipping = str_replace( '&nbsp;', ' ', $shipping );
	return $shipping;
}

if ( ! class_exists( 'WC_Paidy' ) ) :

	// Paidy Payment Gateways version.
	define( 'WC_PAIDY_VERSION', '1.5.1' );
	require_once __DIR__ . '/class-wc-paidy.php';

	/**
	 * Load plugin functions.
	 */
	add_action( 'init', 'wc_paidy_plugin', 0 );

	/**
	 * Initialize the Paidy plugin.
	 */
	function wc_paidy_plugin() {
		if ( is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {
			WC_Paidy::get_instance();
		}
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

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods Methods.
	 * @return array $methods Methods.
	 */
	function add_wc4jp_paidy_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Paidy';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_paidy_gateway' );

	// Admin wizard.
	require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-admin-wizard.php';
	new WC_Paidy_Admin_Wizard();

	if ( is_admin() ) {
		// Load admin settings controller.
		require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-settings-controller.php';
		new WC_Paidy_Settings_Controller();

		// Load admin notices controller.
		require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-admin-notices.php';
		new WC_Paidy_Admin_Notices();
	}

	// Redirect when the plugin is activated or updated.
	add_action( 'admin_init', 'paidy_redirect_to_wizard' );

	/**
	 * Redirects to the Paidy wizard after plugin activation.
	 */
	function paidy_redirect_to_wizard() {
		$paidy_payment_method = new WC_Gateway_Paidy();
		if ( get_option( 'paidy_do_activation_redirect', false ) ) {
			$first_installing = get_option( 'jp4wc-first-installing', 'no' );
			if ( 'yes' !== $paidy_payment_method->enabled && ( jp4wc_has_orders_in_last_5_days() || 'yes' === $first_installing ) ) {
				$paidy_payment_method->update_option( 'enabled', 'yes' );
				// Check if the user is an admin and has the capability to manage options.
				delete_option( 'paidy_do_activation_redirect' );
				wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=%2Fpaidy-on-boarding' ) );
				exit;
			}
		}
	}

	// API Receiver.
	require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-apply-receiver.php';

	/**
	 * Initialize the Paidy receiver.
	 *
	 * @return void
	 */
	function init_paidy_receiver() {
		new WC_Paidy_Apply_Receiver();
	}

	// Load the receiver.
	add_action( 'init', 'init_paidy_receiver' );

	/**
	 * The available gateway to woocommerce only Japanese currency
	 */
	if ( function_exists( 'wc4jp_paidy_available_gateways' ) === false ) {
		/**
		 * Filter available payment gateways to include only Japanese currency.
		 *
		 * @param array $methods Available payment methods.
		 * @return array Filtered payment methods.
		 */
		function wc4jp_paidy_available_gateways( $methods ) {
			// Check if the Paidy payment method is available.
			if ( ! isset( $methods['paidy'] ) ) {
				return $methods;
			}

			// Check if the currency is JPY.
			// If the currency is not JPY, remove the Paidy payment method.
			if ( get_woocommerce_currency() !== 'JPY' ) {
				unset( $methods['paidy'] );
				return $methods;
			}

			$settings = get_option( 'woocommerce_paidy_settings', array() );

			// Check if the API keys are set.
			$has_test_key = ! empty( $settings['test_api_public_key'] );
			$has_live_key = ! empty( $settings['api_public_key'] );

			if ( ! $has_test_key && ! $has_live_key ) {
				unset( $methods['paidy'] );
			}
			return $methods;
		}
		add_filter( 'woocommerce_available_payment_gateways', 'wc4jp_paidy_available_gateways' );
	}
endif;

/**
 * Declare plugin compatibility with WooCommerce HPOS.
 *
 * @since 2.6.0
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
