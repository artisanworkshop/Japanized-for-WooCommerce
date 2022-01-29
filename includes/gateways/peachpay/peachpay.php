<?php
/**
 * Plugin Name: PeachPay for WooCommerce | One-Click Checkout
 * Plugin URI: https://woocommerce.com/products/peachpay
 * Description: PeachPay is the fastest checkout for WooCommerce.
 * Version: 1.62.0
 * Author: PeachPay, Inc.
 * Author URI: https://peachpay.app
 *
 * WC requires at least: 4.0
 * WC tested up to: 5.5
 *
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
/* remove there by Shohei Tanaka 2021-01-28
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	exit;
}

define( 'PEACHPAY_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'PEACHPAY_VERSION', get_plugin_data( __FILE__ )['Version'] );
define( 'PEACHPAY_BASENAME', plugin_basename( __FILE__ ) );
define( 'PEACHPAY_PLUGIN_FILE', __FILE__ );
define( 'PEACHPAY_PAYMENT_META_KEY', '_peachpay_payment_meta' );
*/
// add by Shohei Tanaka at 2021-01-29
define( 'PEACHPAY_ABSPATH', JP4WC_INCLUDES_PATH.'gateways/peachpay/' );
define( 'PEACHPAY_VERSION', '1.62.0' );
define( 'PEACHPAY_BASENAME', 'peachpay-for-woocommerce/peachpay.php' );
define( 'PEACHPAY_PLUGIN_FILE', JP4WC_INCLUDES_PATH.'gateways/peachpay/peachpay.php' );
define( 'PEACHPAY_PAYMENT_META_KEY', '_peachpay_payment_meta' );

peachpay_migrate_options();
peachpay_migrate_enable_stripe_checkbox();
peachpay_migrate_button_position();
peachpay_default_options();
add_action( 'wp', 'peachpay_has_valid_key' );
add_action( 'activated_plugin', 'peachpay_ask_for_permission' );

require_once PEACHPAY_ABSPATH . 'core/class-peachpay-wc-gateway.php';
require_once PEACHPAY_ABSPATH . 'core/analytics.php';
require_once PEACHPAY_ABSPATH . 'core/modules/module.php';
require_once PEACHPAY_ABSPATH . 'core/hide-peachpay.php';
require_once PEACHPAY_ABSPATH . 'core/peachpay-stripe-email.php';
require_once PEACHPAY_ABSPATH . 'core/product-page-button-locations.php';
require_once PEACHPAY_ABSPATH . 'core/util/button.php';
require_once PEACHPAY_ABSPATH . 'core/util/util.php';

// add by Shohei Tanaka at 2021-01-29
peachpay_init_gateway_class();

/**
 * Initializes plugin compatibility and loads plugin files.
 */
function peachpay_init() {
//	load_plugin_textdomain( 'peachpay-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );//remove there by Shohei Tanaka 2021-01-28

	if ( is_admin() ) {
		add_action( 'admin_notices', 'peachpay_admin_notice_retry_permission' );

		include_once PEACHPAY_ABSPATH . 'core/admin/settings.php';
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'peachpay_add_settings_link' );
	}

	if ( peachpay_gateway_enabled() && ( ! is_admin() || peachpay_is_rest() ) ) {
		// Shortcodes.
		include_once PEACHPAY_ABSPATH . 'core/shortcode.php';

		// Rest API.
		include_once PEACHPAY_ABSPATH . 'core/routes/rest-api.php';

		add_action( 'wc_ajax_wc_peachpay_create_order', 'peachpay_ajax_create_order' );

		// Admin Rest API actions.
		add_action( 'wp_ajax_peachpay_wc_ajax_order_payment_complete', 'peachpay_wc_ajax_order_payment_complete' );
		add_action( 'wp_ajax_nopriv_peachpay_wc_ajax_order_payment_complete', 'peachpay_wc_ajax_order_payment_complete' );

		add_action( 'wp_ajax_peachpay_wc_ajax_order_failed', 'peachpay_wc_ajax_order_failed' );
		add_action( 'wp_ajax_nopriv_peachpay_wc_ajax_order_failed', 'peachpay_wc_ajax_order_failed' );

		// Conditionally include frontend js and css.
		if ( ( peachpay_has_valid_key() || peachpay_is_test_mode() ) && ! peachpay_is_rest() ) {
			add_action( 'wp_enqueue_scripts', 'peachpay_load_styles' );
			add_action( 'wp_enqueue_scripts', 'peachpay_load_button_scripts' );

			// Extra Script data to opt in. Currently on by default.
			add_filter( 'peachpay_script_data', 'peachpay_collect_debug_info', 10, 1 );
		}

		// Load and initialize External plugin compatibility.
		load_plugin_compatibility(
			array(
				array(
					'plugin'        => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
					'compatibility' => 'compatibility/wc-subscriptions.php',
				),
				array(
					'plugin'        => 'woocommerce-product-addons/woocommerce-product-addons.php',
					'compatibility' => 'compatibility/wc-product-addons.php',
				),
				array(
					'plugin'        => 'woo-product-country-base-restrictions/woocommerce-product-country-base-restrictions.php',
					'compatibility' => 'compatibility/wc-country-based-restrictions.php',
				),
				array(
					'plugin'        => 'dc-woocommerce-multi-vendor/dc_product_vendor.php',
					'compatibility' => 'compatibility/wc-multi-vendor.php',
				),
				array(
					'plugin'        => 'booster-plus-for-woocommerce/booster-plus-for-woocommerce.php',
					'compatibility' => 'compatibility/booster-for-wc/booster-for-wc.php',
				),
				array(
					'plugin'        => 'woo-discount-rules/woo-discount-rules.php',
					'compatibility' => 'compatibility/woo-discount-rules.php',
				),
				array(
					'plugin'        => array( 'elementor/elementor.php', 'elementor-pro/elementor-pro.php' ),
					'compatibility' => 'compatibility/class-peachpay-elementor-widget.php',
				),
				array(
					'plugin'        => 'woocommerce-product-bundles/woocommerce-product-bundles.php',
					'compatibility' => 'compatibility/wc-product-bundles.php',
				),
				array(
					'plugin'        => array( 'pw-woocommerce-gift-cards/pw-gift-cards.php', 'pw-gift-cards/pw-gift-cards.php' ),
					'compatibility' => 'compatibility/wc-pw-gift-cards.php',
				),
				array(
					'plugin'        => 'custom-product-boxes/custom-product-boxes.php',
					'compatibility' => 'compatibility/custom-product-boxes.php',
				),
				array(
					'plugin'        => 'woocommerce-all-products-for-subscriptions/woocommerce-all-products-for-subscriptions.php',
					'compatibility' => 'compatibility/wc-subscribe-all-things.php',
				),
				array(
					'plugin'        => 'flying-scripts/flying-scripts.php',
					'compatibility' => 'compatibility/flying-scripts.php',
				),
			)
		);
	}

	do_action( 'peachpay_init_compatibility' );
}
add_action( 'init', 'peachpay_init' );

/**
 * Loads plugin compatibility
 *
 * @param array $plugin_compatibility The plugins and compatibility location.
 */
function load_plugin_compatibility( array $plugin_compatibility ) {
	foreach ( $plugin_compatibility as $plugin_info ) {

		// Convert plugin name to an array to make simpler.
		if ( ! is_array( $plugin_info['plugin'] ) ) {
			$plugin_info['plugin'] = array( $plugin_info['plugin'] );
		}

		foreach ( $plugin_info['plugin'] as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				try {
					include_once PEACHPAY_ABSPATH . $plugin_info['compatibility'];
                // phpcs:ignore
				} catch ( Error $error ) {
					// Do no harm.
				}
			}
		}
	}
}

/**
 * Migrate data from the old peachpay_options (all on one page) to the new one
 * options (split into several tabs).
 *
 * This should only run once when someone upgrades from plugin version <1.55.0
 * to plugin version >= 1.55.0
 *
 * @since 1.55.0
 */
function peachpay_migrate_options() {
	if ( empty( get_option( 'peachpay_options' ) ) ) {
		return;
	}

	// General section.
	$general_options_keys = array(
		'language',
		'test_mode',
		'enable_coupons',
		'enable_order_notes',
		'hide_product_images',
	);
	$general_options      = peachpay_migrate_option_group( 'peachpay_options', $general_options_keys );
	update_option( 'peachpay_general_options', $general_options );

	// Payment section.
	$payment_options_keys = array( 'paypal' );
	$payment_options      = peachpay_migrate_option_group( 'peachpay_options', $payment_options_keys );
	update_option( 'peachpay_payment_options', $payment_options );

	// Button section.
	$button_options_keys = array(
		'button_color',
		'button_icon',
		'button_border_radius',
		'peachpay_button_text',
		'button_sheen',
		'hide_on_product_page',
		'button_hide_payment_method_icons',
		'product_button_position',
		'product_button_alignment',
		'button_width_product_page',
		'cart_button_alignment',
		'button_width_cart_page',
	);
	$button_options      = peachpay_migrate_option_group( 'peachpay_options', $button_options_keys );
	update_option( 'peachpay_button_options', $button_options );

	// Clear out the old settings so that we don't run this function anymore.
	update_option( 'peachpay_options', null );
}

/**
 * Migrates the options after `<page>_button_position` was moved to `<page>_button_alignment`. `product_button_position` is
 * now used for placing the product page button above or below the add to cart button. All other pages do not use `_button_position` now.
 */
function peachpay_migrate_button_position() {
	if ( get_option( 'peachpay_migrate_button_position' ) ) {
		return;
	}

	$button_options = get_option( 'peachpay_button_options' );
	if ( ! isset( $button_options ) || ! is_array( $button_options ) ) {
		return;
	}

	if ( isset( $button_options['product_button_position'] ) ) {
		$position = $button_options['product_button_position'];
		if ( 'left' === $position
		|| 'right' === $position
		|| 'full' === $position
		|| 'center' === $position ) {

			$button_options['product_button_alignment'] = $button_options['product_button_position'];
			$button_options['product_button_position']  = 'beforebegin';
		}
	}

	if ( isset( $button_options['checkout_button_position'] ) ) {
		$position = $button_options['checkout_button_position'];
		if ( 'center' === $position ) {
			$button_options['checkout_button_alignment'] = $button_options['checkout_button_position'];
		}
		unset( $button_options['checkout_button_position'] );
	}

	if ( isset( $button_options['cart_button_position'] ) ) {
		$position = $button_options['cart_button_position'];

		if ( 'left' === $position
		|| 'right' === $position
		|| 'full' === $position
		|| 'center' === $position ) {

			$button_options['cart_button_alignment'] = $button_options['cart_button_position'];
		}

		unset( $button_options['cart_button_position'] );
	}

	update_option( 'peachpay_button_options', $button_options );

	update_option( 'peachpay_migrate_button_position', 1 );
}

/**
 * Given the name of an old option and a set of keys for the new option,
 * migrates data from the given keys to a new array which is returned.
 *
 * @param string $from The name of the old option that we will call WP get_option on.
 * @param array  $keys The array of option keys that should be moved from the old
 *                     options to the new options.
 */
function peachpay_migrate_option_group( string $from, array $keys ) {
	$old_option = get_option( $from );
	$result     = array();
	foreach ( $keys as $key ) {
		if ( isset( $old_option[ $key ] ) ) {
			$result[ $key ] = $old_option[ $key ];
		}
	}
	return $result;
}

/**
 * Initializes default peachpay settings options.
 */
function peachpay_default_options() {
	// Initializes default payment settings values.
	$initialized = get_option( 'peachpay_payment_settings_initialized' );
	if ( ! $initialized ) {
		update_option( 'peachpay_payment_settings_initialized', 1 );

		if ( ! is_array( get_option( 'peachpay_payment_options' ) ) ) {
			update_option( 'peachpay_payment_options', array() );
		}

		/**
		 * Set any default payment settings options below.
		 */

		peachpay_set_settings_option( 'peachpay_payment_options', 'stripe_payment_request', true );
	}
}

/**
 * We have to use this instead of the null coalescing operator (??) due to
 * compatibility requirements for WooCommerce Marketplace.
 *
 * @deprecated
 *
 * @param  string $name The name of the option in the PeachPay settings.
 * @return mixed|false Returns false if the option does not exist; otherwise
 * returns the option.
 */
function peachpay_get_option( $name ) {
	$options = get_option( 'peachpay_options' );
	return isset( $options[ $name ] ) ? $options[ $name ] : false;
}

/**
 * We have to use this instead of the null coalescing operator (??) due to
 * compatibility requirements for WooCommerce Marketplace.
 *
 * @param  string     $setting_group The name of the option settings.
 * @param  string     $name          The name of the option in the PeachPay settings.
 * @param  mixed|bool $default       The default value to return if the option is not set.
 * @return mixed|false Returns false if the option does not exist; otherwise
 * returns the option.
 */
function peachpay_get_settings_option( $setting_group, $name, $default = false ) {
	$options = get_option( $setting_group );

	if ( isset( $options[ $name ] ) ) {
		return $options[ $name ];
	}

	return $default;
}

/**
 * Easily set peachpay option group property values.
 *
 * @param string $setting_group The option group to set.
 * @param string $name          The name of the option in the group.
 * @param mixed  $value         The value to set the targeted option.
 */
function peachpay_set_settings_option( $setting_group, $name, $value ) {
	$options = get_option( $setting_group );

	if ( ! is_array( $options ) ) {
		$options = array();
	}

	$options[ $name ] = $value;

	update_option( $setting_group, $options );
}

/**
 * Checks if the current request is a WP REST API request.
 *
 * Case #1: After WP_REST_Request initialization
 * Case #2: Support "plain" permalink settings and check if `rest_route` starts with `/`
 * Case #3: It can happen that WP_Rewrite is not yet initialized,
 *          so do this (wp-settings.php)
 * Case #4: URL Path begins with wp-json/ (your REST prefix)
 *          Also supports WP installations in subfolder
 *
 * @returns boolean
 * https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
 */
function peachpay_is_rest() {
    // phpcs:disable
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
		|| isset( $_GET['rest_route'] ) // (#2)
		&& strpos( $_GET['rest_route'], '/', 0 ) === 0
	) {
		return true;
	}

	// (#3)
	global $wp_rewrite;
	if ( $wp_rewrite === null ) {
		$wp_rewrite = new WP_Rewrite();
	}

	// Admin ajax is a rest request that we use.
	if ( strpos( trailingslashit( $_SERVER['REQUEST_URI'] ), '/wp-admin/admin-ajax.php' ) !== false ) {
		return true;
	}

	// (#4)
	$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
	$current_url = wp_parse_url( add_query_arg( array() ) );

	if ( ! isset( $current_url['path'] ) || ! isset( $rest_url['path'] ) ) {
		return false;
	}

	return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
    // phpcs:enable
}

/**
 * Indicates if the "Test mode" box is checked in the plugin settings.
 */
function peachpay_is_test_mode() {
	return isset( get_option( 'peachpay_general_options' )['test_mode'] )
	&& get_option( 'peachpay_general_options' )['test_mode'];
}

/**
 * This lets us know when someone activates our plugin.
 */
function peachpay_email_us() {
	$body = array(
		'url'            => get_site_url(),
		'email'          => get_bloginfo( 'admin_email' ),
		'salesLastMonth' => peachpay_sales_last_month(),
		'salesYTD'       => peachpay_sales_ytd(),
	);
	peachpay_email( $body, 'plugin/activate' );
}

/**
 * Sends a peachpay welcome email.
 */
function peachpay_email_merchant_welcome() {
	$body = array(
		'email'          => get_bloginfo( 'admin_email' ),
		'merchantDomain' => explode( 'https://', get_site_url() )[1],
	);
	peachpay_email( $body, 'mail/welcome' );
}

/**
 * Sends a peachpay email.
 *
 * @param array  $body The body of the email.
 * @param string $endpoint The email endpoint to use.
 */
function peachpay_email( $body, $endpoint ) {
	$post_body = wp_json_encode( $body );
	$args      = array(
		'body'        => $post_body,
		'headers'     => array( 'Content-Type' => 'application/json' ),
		'httpversion' => '2.0',
		'blocking'    => false,
	);
	wp_remote_post( peachpay_api_url() . $endpoint, $args );
}

/**
 * Creates a peachpay permissions authorization URL.
 */
function peachpay_authorize_url() {
	$store_url  = get_site_url();
	$endpoint   = '/wc-auth/v1/authorize';
	$return_url = peachpay_api_url() . "activation/verify?state=$store_url";

	$params       = array(
		'app_name'     => 'PeachPay',
		'scope'        => 'read_write',
		'user_id'      => 1,
		'return_url'   => $return_url,
		'callback_url' => peachpay_api_url() . 'store-token',
	);
	$query_string = http_build_query( $params );
	$url          = $store_url . $endpoint . '?' . $query_string;
	return $url;
}

/**
 * Sets a option to indicate permissions was denied.
 */
function peachpay_set_error_banner_flag() {
	// phpcs:ignore
	if ( isset( $_GET['api_access'] ) && '0' === $_GET['api_access'] ) {
		update_option( 'peachpay_api_access_denied', true );
	}
}
add_action( 'admin_notices', 'peachpay_set_error_banner_flag' );

/**
 * Reattempt peachpay api permissions request.
 */
function peachpay_retry_permission() {
	update_option( 'peachpay_api_access_denied', false );
	$url = peachpay_authorize_url();
	// phpcs:ignore
	wp_redirect( $url );
}

/**
 * Asks the merchant that just activated the plugin for permission to access
 * the store's WooCommerce API.
 *
 * @param string $plugin The plugin key.
 */
function peachpay_ask_for_permission( $plugin ) {
/* remove by Shohei Tanaka at 2022-01-29
	if ( PEACHPAY_BASENAME !== $plugin ) {
		// Because we run peachpay_ask_for_permission on the activated_plugin hook, it fires
		// when any plugin is activated, not just ours. Exit if not ours.
		return;
	}
*/
	if ( peachpay_has_valid_key() ) {
		// If the store has already given us their WooCommerce API keys, we
		// don't need to ask for them again.
		update_option( 'peachpay_api_access_denied', false );
		return;
	}
	peachpay_email_us();
	peachpay_email_merchant_welcome();
	update_option( 'peachpay_api_access_denied', false );
	$url = peachpay_authorize_url();
    // phpcs:ignore
	wp_redirect( $url );
	exit();
}

/**
 * Sets a admin notice if permissions were denied.
 */
function peachpay_admin_notice_retry_permission() {
    // phpcs:ignore
	if ( isset( $_GET['retry_permission'] ) && '1' === $_GET['retry_permission'] ) {
		peachpay_retry_permission();
		exit();
	}
	if ( get_option( 'peachpay_api_access_denied' ) ) {
		$retry_url = get_site_url() . '/wp-admin/admin.php?page=peachpay&retry_permission=1';
		$message   = "PeachPay will not work without access to WooCommerce. To continue setting up PeachPay, you will need to <a href=\"$retry_url\">choose \"Approve\" on the permission screen</a>. You can use PeachPay in test mode without giving permission.";
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			$message,
			'error'
		);
	}
}

/**
 * Send a deactivation email to us so that we can follow up if needed.
 */
function peachpay_send_deactivation_email() {
	if ( peachpay_is_site( 'localhost' ) || peachpay_is_site( 'peachpay.app' ) ) {
		return;
	}
	$body = wp_json_encode(
		array(
			'merchant_url'         => get_site_url(),
			'merchant_admin_email' => get_bloginfo( 'admin_email' ),
			'stripe_connected'     => (bool) get_option( 'peachpay_connected_stripe_account' ),
			'paypal_connected'     => (bool) get_option( 'peachpay_paypal_signup' ),
			'salesLastMonth'       => peachpay_sales_last_month(),
			'salesYTD'             => peachpay_sales_ytd(),
		)
	);
	$args = array(
		'body'        => $body,
		'headers'     => array( 'Content-Type' => 'application/json' ),
		'httpversion' => '2.0',
		'blocking'    => false,
	);
	wp_remote_post( peachpay_api_url() . 'api/v1/plugin/deactivation_email', $args );
}
register_deactivation_hook( __FILE__, 'peachpay_send_deactivation_email' );

/**
 * Checks if a valid API key has been set.
 */
function peachpay_has_valid_key() {
	// The option is serialized as "1" or "0", so that's why true/false is
	// returned explicitly.
	return get_option( 'peachpay_valid_key' ) ? true : false;
}

/**
 * Enqueues CSS styles for peachpay.
 */
function peachpay_load_styles() {
	if ( ! peachpay_gateway_enabled() ) {
		return;
	}
	wp_enqueue_style(
		'peachpay-css',
		peachpay_url( 'public/css/peachpay.css' ),
		array(),
		peachpay_file_version( 'public/css/peachpay.css' )
	);
}

/**
 * Adds the JavaScript files to the page as it loads. These JavaScript
 * files insert the PeachPay button among other things.
 */
function peachpay_load_button_scripts() {
	if ( ! peachpay_gateway_enabled() ) {
		return;
	}

	if ( ! peachpay_get_settings_option( 'peachpay_button_options', 'disabled_floating_button' ) ) {
		add_action( 'woocommerce_before_shop_loop', 'peachpay_render_floating_button' );
	}

	if ( function_exists( 'is_product' ) && is_product() ) {
		if ( apply_filters( 'peachpay_hide_button_on_product_page', false ) ) {
			return;
		}

		// These merchants do not want PeachPay appearing on free items because
		// we show the credit card field. Our choice here is intentional so
		// that customers get one click when returning to items that cost money.
		if ( intval( wc_get_product()->get_price() ) === 0 ) {
			return;
		}
	}

	if ( ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) && floatval( WC()->cart->get_cart_contents_total() ) === 0.00 ) {
		return;
	}

	if ( function_exists( 'is_cart' ) && is_cart() && apply_filters( 'peachpay_hide_button_on_cart_page', false ) ) {
		return;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() && apply_filters( 'peachpay_hide_button_on_checkout_page', false ) ) {
		return;
	}

	wp_enqueue_script(
		'pp-sentry-lib',
		'https://js.sentry-cdn.com/dd5e3292f8514baa872dcead0794f805.min.js',
		array(),
		1,
		false
	);
	wp_enqueue_script(
		'pp-sentry',
		peachpay_url( 'public/js/sentry.js' ),
		array( 'pp-sentry-lib' ),
		peachpay_file_version( 'public/js/sentry.js' ),
		false
	);

	if ( peachpay_get_settings_option( 'peachpay_payment_options', 'stripe_payment_request' ) ) {
		wp_enqueue_script(
			'pp-stripe-lib',
			'https://js.stripe.com/v3/',
			array(),
			1,
			false
		);

		wp_enqueue_script(
			'pp-stripe',
			peachpay_url( 'public/dist/' . PEACHPAY_VERSION . '/stripe-payment-request/bundle.js' ),
			array( 'pp-stripe-lib' ),
			peachpay_file_version( 'public/dist/' . PEACHPAY_VERSION . '/stripe-payment-request/bundle.js' ),
			false
		);

	}

	wp_enqueue_script(
		'pp-translations',
		peachpay_url( 'public/js/translations.js' ),
		array(),
		peachpay_file_version( 'public/js/translations.js' ),
		false
	);

	wp_enqueue_script(
		'pp-giftcards',
		peachpay_url( 'public/js/giftcard.js' ),
		array(),
		peachpay_file_version( 'public/js/giftcard.js' ),
		false
	);

	wp_enqueue_script(
		'pp-coupons',
		peachpay_url( 'public/js/coupon.js' ),
		array(),
		peachpay_file_version( 'public/js/coupon.js' ),
		false
	);

	wp_enqueue_script(
		'pp-button-product-page',
		peachpay_url( 'public/js/product-page.js' ),
		array(),
		peachpay_file_version( 'public/js/product-page.js' ),
		false
	);

	wp_enqueue_script(
		'pp-button-cart-page',
		peachpay_url( 'public/js/cart-page.js' ),
		array(),
		peachpay_file_version( 'public/js/cart-page.js' ),
		false
	);

	wp_enqueue_script(
		'pp-button-checkout-page',
		peachpay_url( 'public/js/checkout-page.js' ),
		array(),
		peachpay_file_version( 'public/js/checkout-page.js' ),
		false
	);

	wp_enqueue_script(
		'pp-button-core',
		peachpay_url( 'public/js/button.js' ),
		array( 'pp-translations' ),
		peachpay_file_version( 'public/js/button.js' ),
		false
	);

	wp_enqueue_script(
		'pp-button-shortcode',
		peachpay_url( 'public/js/shortcode.js' ),
		array(),
		peachpay_file_version( 'public/js/shortcode.js' ),
		false
	);

	wp_enqueue_script(
		'pp-upsell',
		peachpay_url( 'public/js/linked-products.js' ),
		array(),
		peachpay_file_version( 'public/js/linked-products.js' ),
		false
	);

	wp_enqueue_script(
		'pp-quantity-changer',
		peachpay_url( 'public/js/quantity-changer.js' ),
		array(),
		peachpay_file_version( 'public/js/quantity-changer.js' ),
		false
	);

	add_shortcode( 'peachpay', 'peachpay_shortcode' );

	$general_options = get_option( 'peachpay_general_options' );
	$payment_options = get_option( 'peachpay_payment_options' );
	$button_options  = get_option( 'peachpay_button_options' );

	wp_localize_script(
		'pp-button-core',
		'peachpay_data',
		// This filter is to allow plugin compatibility to allow plugins to add meta data dynamically so we can 1 reduce
		// what we have to send but also be loosely coupled with plugins we support. If the data will always be present
		// then it should be added directly here.
		apply_filters(
			'peachpay_script_data',
			array(
				'checkout_nonce'                           => wp_create_nonce( 'peachpay_process_checkout' ),
				'apply_coupon_nonce'                       => wp_create_nonce( 'apply-coupon' ),
				// Use to define new feature support going forward.
				'feature_support'                          => peachpay_feature_support_record(),
				'merchant_name'                            => get_bloginfo( 'name' ),
				'wp_site_url'                              => get_site_url(),
				'wp_admin_or_editor'                       => current_user_can( 'editor' ) || current_user_can( 'administrator' ),
				'wp_ajax_url'                              => admin_url( 'admin-ajax.php', 'relative' ),
				'plugin_asset_url'                         => peachpay_url( '' ),
				'version'                                  => PEACHPAY_VERSION,
				'num_shipping_zones'                       => count( WC_Shipping_Zones::get_zones() ),
				'merchant_customer_account'                => peachpay_get_merchant_customer_account(),
				// cart_total_tax is only used to fix a very specific bug on tires.pneupress.com where the
				// usual server request must be called twice with cookies in order for it to send back
				// the correct tax amount. Instead of doing something weird like that, we just get the tax
				// right here since for this store it is the same no matter what address.
				'cart_total_tax'                           => is_cart() ? WC()->cart->get_total_tax() : null,
				'currency_info'                            => peachpay_get_currency_info(),
				'is_category_page'                         => is_product_category(),
				'is_cart_page'                             => is_cart(),
				'is_checkout_page'                         => is_checkout(),
				'wc_cart_url'                              => wc_get_cart_url(),
				'has_valid_key'                            => peachpay_has_valid_key(),
				'authorize_url'                            => ! peachpay_has_valid_key() ? peachpay_authorize_url() : '',
				'cart'                                     => peachpay_get_cart(),
				'debug_cart'                               => WC()->cart,
				'wc_prices_include_tax'                    => wc_prices_include_tax(),
				'wc_tax_price_display'                     => ( isset( WC()->cart ) && '' !== WC()->cart ) ? WC()->cart->get_tax_price_display_mode() : '',
				'wc_order_received_url'                    => wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ),
				'wc_store_country_code'                    => WC()->countries->get_base_country(),
				'wc_customer_default_location'             => wc_get_customer_default_location(),
				'wc_location_info'                         => peachpay_location_details(),
				'test_mode'                                => isset( $general_options['test_mode'] ) ? $general_options['test_mode'] : null,
				'connected_stripe_account'                 => (bool) get_option( 'peachpay_connected_stripe_account' ),
				// This is the "Enable PayPal" checkbox in the Payment Methods tab of the plugin settings.
				'paypal'                                   => isset( $payment_options['paypal'] ) ? $payment_options['paypal'] : null,
				'language'                                 => isset( $general_options['language'] ) ? $general_options['language'] : 'en-US',

				'button_color'                             => isset( $button_options['button_color'] ) ? $button_options['button_color'] : '#FF876C',
				'button_icon'                              => peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ) ? peachpay_get_settings_option( 'peachpay_button_options', 'button_icon' ) : 'lock',
				'button_border_radius'                     => peachpay_get_settings_option( 'peachpay_button_options', 'button_border_radius' ),
				'button_text'                              => ( isset( $button_options['peachpay_button_text'] ) && '' !== $button_options['peachpay_button_text'] ) ? $button_options['peachpay_button_text'] : peachpay_get_button_text(),
				'button_alignment_product_page'            => isset( $button_options['product_button_alignment'] ) ? $button_options['product_button_alignment'] : null,
				'button_alignment_cart_page'               => isset( $button_options['cart_button_alignment'] ) ? $button_options['cart_button_alignment'] : null,
				'button_alignment_checkout_page'           => isset( $button_options['checkout_button_alignment'] ) ? $button_options['checkout_button_alignment'] : null,
				'button_width_product_page'                => isset( $button_options['button_width_product_page'] ) ? $button_options['button_width_product_page'] : null,
				'button_width_cart_page'                   => isset( $button_options['button_width_cart_page'] ) ? $button_options['button_width_cart_page'] : null,
				'button_width_checkout_page'               => isset( $button_options['button_width_checkout_page'] ) ? $button_options['button_width_checkout_page'] : null,
				'button_sheen'                             => peachpay_get_settings_option( 'peachpay_button_options', 'button_sheen' ),
				'button_hide_on_product_page'              => peachpay_get_settings_option( 'peachpay_button_options', 'hide_on_product_page' ),
				'button_hide_payment_method_icons'         => peachpay_get_settings_option( 'peachpay_button_options', 'button_hide_payment_method_icons' ),

				'should_place_order_before_payment'        => should_place_order_before_payment(),
				'plugin_woocommerce_product_addon'         => is_plugin_active( 'woocommerce-product-addon/woocommerce-product-addon.php' ),
				'plugin_woocommerce_points_and_rewards_active' => is_plugin_active( 'woocommerce-points-and-rewards/woocommerce-points-and-rewards.php' ),
				'plugin_woocommerce_order_delivery_options' => woocommerce_order_delivery_options(),
				'plugin_woocommerce_order_delivery_active' => is_plugin_active( 'woocommerce-order-delivery/woocommerce-order-delivery.php' ),
				'plugin_routeapp_active'                   => is_plugin_active( 'routeapp/routeapp.php' ),
				'plugin_woo_thank_you_page_nextmove_lite_active' => is_plugin_active( 'woo-thank-you-page-nextmove-lite/thank-you-page-for-woocommerce-nextmove-lite.php' ),
				'is_shortcode'                             => false,
				'hide_peachpay_upsell'                     => peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_upsell' ),
			)
		)
	);
}

/**
 * Gathers location information for peachpay so peachpay can show only the countries/state/provinces that the store supports or defaults to.
 * https://woocommerce.github.io/code-reference/classes/WC-Countries.html#method_get_allowed_countries
 */
function peachpay_location_details() {
	$countries         = WC()->countries;
	$store_country     = $countries->get_base_country();
	$customer_location = wc_get_customer_default_location();
	return array(
		'store_country'                      => $store_country,
		'customer_default_country'           => $customer_location ? $customer_location['country'] : $store_country, // Default to store company if we do not know the customer location.
		'customer_default_state_or_province' => $customer_location ? $customer_location['state'] : '',
		'allowed_countries'                  => $countries->get_allowed_countries(),
		'allowed_states_or_provinces'        => $countries->get_allowed_country_states(),
	);
}

/**
 * Gets useful information about the merchant customer login so peachpay can adapt where needed.
 */
function peachpay_get_merchant_customer_account() {
	return array(
		'logged_in'                       => is_user_logged_in(),
		'email'                           => is_user_logged_in() ? wp_get_current_user()->user_email : '',
		'logins_and_registration_enabled' => 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
		'auto_generate_username'          => 'yes' === get_option( 'woocommerce_registration_generate_username' ),
		'auto_generate_password'          => 'yes' === get_option( 'woocommerce_registration_generate_password' ),
		'allow_guest_checkout'            => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ),
	);
}

/**
 * Creates a record of what features are enabled and what api version they are so the modal can easily handle different plugins.
 * "version": should only be incremented if a change is breaking. Starts at 1 because the modal uses 0 for backwards compatibility
 * versions before plugins that supply a feature support record.
 * "meta_data": should only be static information. It is also optional.
 */
function peachpay_feature_support_record() {
	$payment_request_option_test_sites = array(
		'https://store.local',
		'https://woo.store.local',
		'https://shop.peachpay.app',
		'https://woo.peachpay.app',
		'https://theme1.peachpay.app',
		'https://qa.peachpay.app',
		'https://holistichuman.design',
		'https://midfox.com',
		'https://123duionline.com',
		'https://arespta.org',
		'https://legoudalier.com',
	);

	$show_payment_request_option = false;
	if ( in_array( get_site_url(), $payment_request_option_test_sites, true ) ) {
		$show_payment_request_option = true;
	}

	$base_features = array(
		'cart_calculation'       => array(
			'enabled' => true,
			'version' => 2,
		),
		'coupon_input'           => array(
			'enabled' => peachpay_get_settings_option( 'peachpay_general_options', 'enable_coupons' ),
			'version' => 2,
		),
		'order_notes_input'      => array(
			'enabled' => peachpay_get_settings_option( 'peachpay_general_options', 'enable_order_notes' ),
			'version' => 1,
		),
		'stripe_payment_method'  => array(
			'enabled' => peachpay_get_settings_option( 'peachpay_payment_options', 'enable_stripe' ),
			'version' => 1,
		),
		'stripe_payment_request' => array(
			'enabled' => ( peachpay_get_settings_option( 'peachpay_payment_options', 'stripe_payment_request' ) && peachpay_has_valid_key() ) && $show_payment_request_option,
			'version' => 1,
		),
		'quantity_changer'       => array(
			'enabled' => true,
			'version' => 3,
		),
	);

	return (array) apply_filters( 'peachpay_register_feature', $base_features );
}

define(
	'BUTTON_TEXT_TRANSLATION',
	array(
		'ar'    => 'الخروج السريع',
		'ca'    => 'Pagament exprés',
		'cs-CZ' => 'Expresní pokladna',
		'da-DK' => 'Hurtig betaling',
		'de-DE' => 'Expresskauf',
		'el'    => 'Γρήγορο ταμείο',
		'en-US' => 'Express checkout',
		'es-ES' => 'Chequeo rápido',
		'fr'    => 'Acheter maintenant',
		'hi-IN' => 'स्पष्ट नियंत्रण',
		'it'    => 'Cassa rapida',
		'ja'    => 'エクスプレスチェックアウト',
		'ko-KR' => '익스프레스 체크아웃',
		'lb-LU' => 'Express Kees',
		'nl-NL' => 'Snel afrekenen',
		'pt-PT' => 'Checkout expresso',
		'ro-RO' => 'Cumpără cu 1-click',
		'ru-RU' => 'Экспресс-касса',
		'sl-SI' => 'Hitra odjava',
		'sv-SE' => 'snabbkassa',
		'th'    => 'ชำระเงินด่วน',
		'uk'    => 'Експрес -оплата',
		'zh-CN' => '快速结帐',
		'zh-TW' => '快速結帳',
	)
);

/**
 * Duplicate of peachpay_to_our_language_key in settings.php until we refactor
 * so that it can be used in both places.
 *
 * @param string $language_code_or_locale A given language code.
 */
function peachpay_to_our_language_key_temp( $language_code_or_locale ) {
	switch ( $language_code_or_locale ) {
		case 'cs':
			return 'cs-CZ';
		case 'da':
			return 'da-DK';
		case 'de':
		case 'de-AT':
		case 'de-DE':
		case 'de-CH':
			return 'de-DE';
		case 'en':
			return 'en-US';
		case 'es':
		case 'es-MX':
		case 'es-AR':
		case 'es-CL':
		case 'es-PE':
		case 'es-PR':
		case 'es-GT':
		case 'es-CO':
		case 'es-EC':
		case 'es-VE':
		case 'es-UY':
		case 'es-CR':
			return 'es-ES';
		case 'fr-BE':
		case 'fr-CA':
		case 'fr-FR':
			return 'fr';
		case 'hi':
			return 'hi-IN';
		case 'it-IT':
			return 'it';
		case 'ko':
			return 'ko-KR';
		case 'lb':
			return 'lb-LU';
		case 'nl':
		case 'nl-BE':
		case 'nl-NL':
			return 'nl-NL';
		case 'pt':
		case 'pt-AO':
		case 'pt-BR':
		case 'pt-PT-ao90':
		case 'pt-PT':
			return 'pt-PT';
		case 'ro':
			return 'ro-RO';
		case 'ru':
			return 'ru-RU';
		case 'sl':
			return 'sl-SI';
		case 'sv':
			return 'sv-SE';
		default:
			return $language_code_or_locale;
	}
}

/**
 * Gets the text to display on the peachpay button.
 */
function peachpay_get_button_text() {
	$page_language = peachpay_to_our_language_key_temp( substr( get_locale(), 0, 2 ) );

	if ( ! peachpay_get_settings_option( 'peachpay_general_options', 'language' ) ) {
		return BUTTON_TEXT_TRANSLATION['en-US'];
	}

	if ( 'detect-from-page' === peachpay_get_settings_option( 'peachpay_general_options', 'language' ) ) {
		if ( ! isset( BUTTON_TEXT_TRANSLATION[ $page_language ] ) ) {
			return BUTTON_TEXT_TRANSLATION['en-US'];
		}

		return BUTTON_TEXT_TRANSLATION[ $page_language ];
	}

	return BUTTON_TEXT_TRANSLATION[ peachpay_get_settings_option( 'peachpay_general_options', 'language' ) ];
}

/**
 * Checks if an item is a variation if so it will get the parent name so we can
 * use variations as subtitles if not returns the product's name.
 *
 * @param  int $id the product ID.
 * @return string the parent product name if exists, otherwise the product name.
 */
function peachpay_get_parent_name( $id ) {
	$product = wc_get_product( $id );

	if ( ! $product ) {
		return '';
	}

	if ( $product instanceof WC_Product_Variation ) {
		$id = $product->get_parent_id();
	}

	$product = wc_get_product( $id );

	return $product->get_name();
}

/**
 * Builds the array of cart product data for the peachpay checkout modal.
 *
 * @param array $wc_line_items List of cart wc product line items.
 */
function peachpay_make_cart_from_wc_cart( $wc_line_items ) {
	$pp_cart = array();

	foreach ( $wc_line_items as $wc_line_item ) {
		$wc_product   = peachpay_product_from_line_item( $wc_line_item );
		$pp_cart_item = array(
			'product_id'          => $wc_product->get_id(),
			'variation_id'        => $wc_product->get_id(), // Why? WC_Product::get_variation_id is deprecated since version 3.0. Use WC_Product::get_id(). It will always be the variation ID if this is a variation.
			'name'                => peachpay_get_parent_name( $wc_product->get_id() ),
			'price'               => peachpay_product_price( $wc_product ),
			'display_price'       => peachpay_product_display_price( $wc_product ),
			'quantity'            => $wc_line_item['quantity'],
			'stock_qty'           => $wc_product->get_stock_quantity(),
			'virtual'             => $wc_product->is_virtual(),
			'subtotal'            => strval( peachpay_product_price( $wc_product ) ), // subtotal and total are only relevant for what shows up in the order dashboard.
			'total'               => strval( peachpay_product_price( $wc_product ) ),
			'variation'           => $wc_line_item['variation'], // This is the actual selected variation attributes.
			'attributes'          => peachpay_product_variation_attributes( $wc_product->get_id() ),
			'image'               => peachpay_product_image( $wc_product ),
			'upsell_items'        => peachpay_upsell_items( $wc_product ),
			'cross_sell_items'    => peachpay_cross_sell_items( $wc_product ),
			'item_key'            => $wc_line_item['key'],

			// On the cart page only this replaces both including the variation
			// in the name (not in the above code anymore) and using the
			// attributes above because it takes care of variation value
			// formatting as well as plugins which add their own extra
			// variations, like Extra Product Options. This is not available on
			// the product page since the customer hasn't yet selected the options.
			'formatted_item_data' => wc_get_formatted_cart_item_data( $wc_line_item ),
			// If Extra Product Options is not configured to have the variation
			// inside it, then formatted_item_data won't include the variation,
			// so we need to include it in the product name.
			'name_with_variation' => peachpay_product_name_always_with_variation( $wc_product->get_id() ),
			'meta_data'           => array(),
			'on_sale'             => $wc_product->is_on_sale(),
			'product_categories'  => $wc_product->get_category_ids(),

		);

		// Apply meta data for compatibility. This filter can be hooked into anywhere to add needed meta data to cart items on the cart page.
		array_push( $pp_cart, apply_filters( 'peachpay_cart_page_line_item', $pp_cart_item, $wc_line_item ) );
	}

	return $pp_cart;
}

/**
 * Gets the full product name even if the filter
 * woocommerce_product_variation_title_include_attributes has been set to not
 * include the variation in the title.
 *
 * Example usage: add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );
 *
 * This is used for the cart page checkout window to display the variation as part of the title.
 *
 * This pretty much takes the code from the internal function generate_product_title
 * from woocommerce/includes/data-stores/class-wc-product-variation-data-store-cpt.php
 * and removes the filter part.
 *
 * If this is a simple product with no variations, it returns the base name.
 *
 * @param int $id The product id of a given product.
 */
function peachpay_product_name_always_with_variation( $id ) {
	$product = wc_get_product( $id );
	if ( ! $product ) {
		return '';
	}
	if ( $product instanceof WC_Product_Variation ) {
		$separator = apply_filters( 'woocommerce_product_variation_title_attributes_separator', ' - ', $product );
		return get_post_field( 'post_title', $product->get_parent_id() ) . $separator . wc_get_formatted_variation( $product, true, false );
	}
	return $product->get_name();
}

/**
 * Endpoint for indicating a successful payment.
 */
function peachpay_wc_ajax_order_payment_complete() {
    // phpcs:disable
	if ( ! isset( $_POST['order_id'] ) || ! isset( $_POST['_peachpay_stripe_customer_id'] ) || ! isset( $_POST['payment_type'] ) || ! isset( $_POST['transaction_id'] )
	) {
		wp_send_json_error( 'Missing required parameters', 400 );
	}
	$_POST['order_status_nonce'] = wp_create_nonce( 'peachpay_set_order_status' );

	$order_id  = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
	$stripe_id = sanitize_text_field( wp_unslash( $_POST['_peachpay_stripe_customer_id'] ) );
	$order     = wc_get_order( $order_id );

	if ( '' !== $_POST['transaction_id'] ) {
		$order->set_transaction_id( sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) );
	}

    //phpcs:enable

	$order->add_meta_data( 'peachpay_is_test_mode', peachpay_is_test_mode() ? 'true' : 'false' );

	$order->payment_complete();

	if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) && wcs_order_contains_subscription( $order, 'parent' ) ) {
		peachpay_set_stripe_order_payment_meta( $order_id, $stripe_id );
	}
}

/**
 * Gets any available payment information from an order.
 *
 * @param  int $order_id The original order id that payment information was stored in.
 * @return Array
 */
function peachpay_get_order_payment_meta( int $order_id ) {
	return get_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, true );
}

/**
 * Sets peachpay order payment meta related to stripe
 *
 * @param  int    $order_id           The original order id that payment information was stored in.
 * @param  string $stripe_customer_id The customer stripe id to store.
 * @return void
 */
function peachpay_set_stripe_order_payment_meta( int $order_id, string $stripe_customer_id ) {
	$data = array(
		'payment_type' => 'stripe',
		'customer_id'  => $stripe_customer_id,
	);

	update_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, $data );
}

/**
 * Gets all peachpay order payment meta related to stripe. If the payment
 * was not stripe it then returns a empty string.
 *
 * @param  int $order_id The original order id that payment information was stored in.
 * @return string
 */
function peachpay_get_stripe_order_payment_meta( int $order_id ) {
	$data = get_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, true );
	return ( 'stripe' === $data['payment_type'] ? $data['customer_id'] : '' );
}

/**
 * Ajax endpoint for indicating that a order payment failed.
 */
function peachpay_wc_ajax_order_failed() {
    // phpcs:ignore
	if ( ! isset( $_POST['order_id'] ) ) {
		wp_send_json_error( 'Missing required parameters', 400 );
	}
     // phpcs:ignore
	$order = wc_get_order( $_POST['order_id'] );
	$order->set_status( 'failed' );
	$order->save();
     // phpcs:ignore
	$order->add_order_note( 'Payment failed. Reason: ' . $_POST['payment_failure_reason'] );
	wp_send_json( array( 'success' => true ) );
}

/**
 * Gets order delivery options got a Woocommerce Order Delivery plugin.
 */
function woocommerce_order_delivery_options() {
	if ( ! is_plugin_active( 'woocommerce-order-delivery/woocommerce-order-delivery.php' ) ) {
		return array();
	}
	$wc_od_delivery_days    = get_option( 'wc_od_delivery_days' );
	$delivery_unchecked_day = array();

	// default order delivery setting for delivery days.
	if ( ! get_option( 'wc_od_delivery_days' ) ) {
		array_push( $delivery_unchecked_day, 0 );
	} else {
		$days = array( 0, 1, 2, 3, 4, 5, 6 );
		foreach ( $days as $day ) {
			$wc_od_delivery_days_single = $wc_od_delivery_days[ $day ];
			if ( 'no' === $wc_od_delivery_days_single['enabled'] ) {
				array_push( $delivery_unchecked_day, $day );
			}
		}
	}

	$order_delivery_options = array(
		'wc_od_max_delivery_days' => ! get_option( 'wc_od_max_delivery_days' ) ? 9 : (int) get_option( 'wc_od_max_delivery_days' ),
		'delivery_unchecked_day'  => $delivery_unchecked_day,
	);
	return $order_delivery_options;
}

/**
 * Collects nonsensitive debug information.
 *
 * @param array $peachpay_data The starting data to be sent to the frontend.
 */
function peachpay_collect_debug_info( $peachpay_data ) {
	$peachpay_data['debug'] = array(
		'peachpay' => array(
			'version'         => PEACHPAY_VERSION,
			'test_mode'       => peachpay_is_test_mode(),
			'feature_support' => array( 'alerts' ),
		),
		'plugins'  => array(),
	);

	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();

		foreach ( $plugins as $plugin_key => $plugin_data ) {
			$peachpay_data['debug']['plugins'][ $plugin_key ] = array(
				'name'      => $plugin_data['Name'],
				'version'   => $plugin_data['Version'],
				'pluginURI' => $plugin_data['PluginURI'],
				'active'    => is_plugin_active( $plugin_key ),
			);
		}

    //phpcs:ignore
	} catch ( Exception $ex ) {
		// Do no harm.
	}

	return $peachpay_data;
}

/**
 * After introducing the "Enable Stripe" checkbox, we will need to automatically
 * check this box for any merchant who is upgrading from an older version of the
 * plugin.
 */
function peachpay_migrate_enable_stripe_checkbox() {
	if ( get_option( 'peachpay_migrated_to_enable_stripe_checkbox' ) ) {
		return;
	}

	if ( ! get_option( 'peachpay_connected_stripe_account' ) ) {
		update_option( 'peachpay_migrated_to_enable_stripe_checkbox', 1 );
		return;
	}

	if ( ! is_array( get_option( 'peachpay_payment_options' ) ) ) {
		update_option( 'peachpay_payment_options', array() );
	}
	peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 1 );

	update_option( 'peachpay_migrated_to_enable_stripe_checkbox', 1 );
}
/** debug code from here */

add_action('woocommerce_before_checkout_form','checkout_form');
function checkout_form(){
	echo do_shortcode('peachpay');
	echo PEACHPAY_ABSPATH;
}