<?php
/**
 * Plugin Name: PeachPay for WooCommerce | One-Click Checkout
 * Plugin URI: https://woocommerce.com/products/peachpay
 * Description: PeachPay is the fastest checkout for WooCommerce.
 * Version: 1.58.1
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
	exit; // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	exit;
}

peachpay_migrate_options();
peachpay_default_options();
peachpay_migrate_enable_stripe_checkbox();

const PEACHPAY_PAYMENT_META_KEY = '_peachpay_payment_meta';
define( 'PEACHPAY_VERSION', get_plugin_data( __FILE__ )['Version'] );
add_action( 'wp', 'peachpay_has_valid_key' );
add_action( 'activated_plugin', 'peachpay_ask_for_permission' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-peachpay-wc-gateway.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/analytics.php';

/**
 * Initializes plugin compatibility and loads plugin files.
 */
function peachpay_init() {
	load_plugin_textdomain( 'woocommerce-for-japan', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( is_admin() ) {
		add_action( 'admin_notices', 'peachpay_admin_notice_retry_permission' );

		require_once plugin_dir_path( __FILE__ ) . 'includes/admin/settings.php';
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'peachpay_add_settings_link' );
	}

	if ( peachpay_gateway_enabled() && ( ! is_admin() || peachpay_is_rest() ) ) {

		// Utilities.
		require_once plugin_dir_path( __FILE__ ) . 'includes/util/array.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/util/currency.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/util/product.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/util/cart.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/util/shipping.php';

		// Rest API.
		require_once plugin_dir_path( __FILE__ ) . 'includes/routes/rest-api.php';

		// Admin Rest API actions.
		add_action( 'wc_ajax_wc_peachpay_create_order', 'peachpay_ajax_create_order' );

		add_action( 'wp_ajax_peachpay_woocommerce_ajax_add_to_cart', 'peachpay_woocommerce_ajax_add_to_cart' );
		add_action( 'wp_ajax_nopriv_peachpay_woocommerce_ajax_add_to_cart', 'peachpay_woocommerce_ajax_add_to_cart' );

		add_action( 'wp_ajax_peachpay_wc_ajax_empty_cart', 'peachpay_wc_ajax_empty_cart' );
		add_action( 'wp_ajax_nopriv_peachpay_wc_ajax_empty_cart', 'peachpay_wc_ajax_empty_cart' );

		add_action( 'wp_ajax_peachpay_wc_ajax_order_payment_complete', 'peachpay_wc_ajax_order_payment_complete' );
		add_action( 'wp_ajax_nopriv_peachpay_wc_ajax_order_payment_complete', 'peachpay_wc_ajax_order_payment_complete' );

		add_action( 'wp_ajax_peachpay_wc_ajax_order_failed', 'peachpay_wc_ajax_order_failed' );
		add_action( 'wp_ajax_nopriv_peachpay_wc_ajax_order_failed', 'peachpay_wc_ajax_order_failed' );

		add_action( 'wp_ajax_peachpay_product_quantity_changer', 'peachpay_product_quantity_changer' );
		add_action( 'wp_ajax_nopriv_peachpay_product_quantity_changer', 'peachpay_product_quantity_changer' );

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
					'compatibility' => 'includes/compatibility/wc-subscriptions.php',
				),
				array(
					'plugin'        => 'woocommerce-product-addons/woocommerce-product-addons.php',
					'compatibility' => 'includes/compatibility/wc-product-addons.php',
				),
				array(
					'plugin'        => 'woo-product-country-base-restrictions/woocommerce-product-country-base-restrictions.php',
					'compatibility' => 'includes/compatibility/wc-country-based-restrictions.php',
				),
				array(
					'plugin'        => array( 'yaycurrency/yay-currency.php', 'yaycurrency-pro/yay-currency.php' ),
					'compatibility' => 'includes/compatibility/yaycurrency.php',
				),
				array(
					'plugin'        => 'woocommerce-product-addon/woocommerce-product-addon.php',
					'compatibility' => 'includes/compatibility/wc-ppom.php',
				),
				array(
					'plugin'        => 'dc-woocommerce-multi-vendor/dc_product_vendor.php',
					'compatibility' => 'includes/compatibility/wc-multi-vendor.php',
				),
				array(
					'plugin'        => 'shared-variation-inventory-for-woocommerce/woocommerce-shared-variation-inventory.php',
					'compatibility' => 'includes/compatibility/wc-shared-variation-inventory.php',
				),
				array(
					'plugin'        => 'booster-plus-for-woocommerce/booster-plus-for-woocommerce.php',
					'compatibility' => 'includes/compatibility/booster-for-wc/booster-for-wc.php',
				),
				array(
					'plugin'        => 'woo-discount-rules/woo-discount-rules.php',
					'compatibility' => 'includes/compatibility/woo-discount-rules.php',
				),
				array(
					'plugin'        => array( 'elementor/elementor.php', 'elementor-pro/elementor-pro.php' ),
					'compatibility' => 'includes/compatibility/class-peachpay-elementor-widget.php',
				),
				array(
					'plugin'        => 'woocommerce-product-bundles/woocommerce-product-bundles.php',
					'compatibility' => 'includes/compatibility/wc-product-bundles.php',
				),
				array(
					'plugin'        => array( 'pw-woocommerce-gift-cards/pw-gift-cards.php', 'pw-gift-cards/pw-gift-cards.php' ),
					'compatibility' => 'includes/compatibility/wc-pw-gift-cards.php',
				),
				array(
					'plugin'        => 'custom-product-boxes/custom-product-boxes.php',
					'compatibility' => 'includes/compatibility/custom-product-boxes.php',
				),
				array(
					'plugin'        => 'woocommerce-all-products-for-subscriptions/woocommerce-all-products-for-subscriptions.php',
					'compatibility' => 'includes/compatibility/wc-subscribe-all-things.php',
				),
				array(
					'plugin'        => 'flying-scripts/flying-scripts.php',
					'compatibility' => 'includes/compatibility/flying-scripts.php',
				),
				array(
					'plugin'        => 'woocommerce-tm-extra-product-options/tm-woo-extra-product-options.php',
					'compatibility' => 'includes/compatibility/wc-tm-extra-product-options.php',
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
					include_once plugin_dir_path( __FILE__ ) . $plugin_info['compatibility'];
				} catch ( Error $error ) {
					// phpcs:disable
					error_log( print_r( $error, true ) );
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
		'hide_product_images'
	);
	$general_options = peachpay_migrate_option_group( 'peachpay_options', $general_options_keys );
	update_option( 'peachpay_general_options', $general_options );

	// Payment section.
	$payment_options_keys = array('paypal');
	$payment_options = peachpay_migrate_option_group( 'peachpay_options', $payment_options_keys ) ;
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
		'button_width_product_page',
		'cart_button_position',
		'button_width_cart_page'
	);
	$button_options = peachpay_migrate_option_group( 'peachpay_options', $button_options_keys ) ;
	update_option( 'peachpay_button_options', $button_options );

	// Clear out the old settings so that we don't run this function anymore.
	update_option( 'peachpay_options', null );
}

/**
 * Given the name of an old option and a set of keys for the new option,
 * migrates data from the given keys to a new array which is returned.
 *
 * @param string $from The name of the old option that we will call WP get_option on.
 * @param array $keys The array of option keys that should be moved from the old
 *              options to the new options.
 */
function peachpay_migrate_option_group( string $from, array $keys ) {
	$old_option = get_option( $from );
	$result = array();
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

	// Initilize default payment settings values.
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
 * @deprecated
 *
 * @param string $name The name of the option in the PeachPay settings.
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
 * @param string $setting_group The name of the option settings.
 * @param string $name The name of the option in the PeachPay settings.
 * @return mixed|false Returns false if the option does not exist; otherwise
 * returns the option.
 */
function peachpay_get_settings_option( string $setting_group, string $name) {
	$options = get_option( $setting_group );
	return isset( $options[ $name ] ) ? $options[ $name ] : false;
}

function peachpay_set_settings_option( string $setting_group, string $name, $value ) {
	$options = get_option( $setting_group );
	$options[ $name ] = $value;
	update_option( $setting_group, $options );
}

/**
 * Determines which environment we are running in so we can call
 * the correct PeachPay API.
 */
function peachpay_api_url() {
	if ( peachpay_is_test_mode() ) {
		switch ( get_site_url() ) {
			case 'https://woo.store.local':
			case 'https://store.local':
				return 'https://dev.peachpay.local/';
			default:
				return 'https://dev.peachpay.app/';
		}
	} else {
		switch ( get_site_url() ) {
			case 'https://woo.peachpay.app':
			case 'https://theme1.peachpay.app':
			case 'https://theme2.peachpay.app':
			case 'https://theme3.peachpay.app':
			case 'https://theme4.peachpay.app':
			case 'https://theme5.peachpay.app':
			case 'https://qa.peachpay.app':
			case 'https://demo.peachpay.app':
			case 'http://localhost:8000':
				return 'https://dev.peachpay.app/';
			case 'https://woo.store.local':
			case 'https://store.local':
				return 'https://prod.peachpay.local/';
			default:
				return 'https://prod.peachpay.app/';
		}
	}
}

/**
 * Checks if the current request is a WP REST API request.
 *
 * Case #1: After WP_REST_Request initialisation
 * Case #2: Support "plain" permalink settings and check if `rest_route` starts with `/`
 * Case #3: It can happen that WP_Rewrite is not yet initialized,
 *          so do this (wp-settings.php)
 * Case #4: URL Path begins with wp-json/ (your REST prefix)
 *          Also supports WP installations in subfolders
 *
 * @returns boolean
 * @author matzeeable
 * https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
 */
function peachpay_is_rest() {
	if (defined('REST_REQUEST') && REST_REQUEST // (#1)
			|| isset($_GET['rest_route']) // (#2)
					&& strpos( $_GET['rest_route'], '/', 0 ) === 0)
			return true;

	// (#3)
	global $wp_rewrite;
	if ($wp_rewrite === null) $wp_rewrite = new WP_Rewrite();

	//Admin ajax is a rest request that we use
	if(strpos( trailingslashit( $_SERVER['REQUEST_URI']) , "/wp-admin/admin-ajax.php") !== false){
		return true;
	}

	// (#4)
	$rest_url = wp_parse_url( trailingslashit( rest_url( ) ) );
	$current_url = wp_parse_url( add_query_arg( array( ) ) );
	return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
}

/**
 * Indicates if the "Test mode" box is checked in the plugin settings.
 */
function peachpay_is_test_mode() {
	return isset( get_option( 'peachpay_general_options' )['test_mode'] )
		&& get_option( 'peachpay_general_options' )['test_mode'];
}

/**
 * Gets the left most subdomain out of a URL.
 *
 * @param string $url .
 */
function peachpay_subdomain( $url ) {
	$parsed_url = parse_url( $url );
	$host       = explode( '.', $parsed_url['host'] );
	return $host[ 0 ];
}

/**
 * This lets us know when someone activates our plugin.
 */
function peachpay_email_us() {
	$body = array(
		'url'             => get_site_url(),
		'email'           => get_bloginfo( 'admin_email' ),
		'salesLastMonth'  => peachpay_sales_last_month(),
		'salesYTD'        => peachpay_sales_ytd(),
	);
	peachpay_email( $body, 'plugin/activate' );
}

function peachpay_email_merchant_welcome() {
	$body = array(
		'email' => get_bloginfo( 'admin_email' ),
	);
	peachpay_email( $body, 'mail/welcome' );
}

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

function peachpay_authorize_url() {
	$store_url    = get_site_url();
	$endpoint     = '/wc-auth/v1/authorize';
	$return_url   = peachpay_api_url() . "activation/verify?state=$store_url";

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

function peachpay_set_error_banner_flag() {
	if ( isset( $_GET['api_access'] ) && $_GET['api_access'] === '0' ) {
		update_option( 'peachpay_api_access_denied', true );
	}
}
add_action( 'admin_notices', 'peachpay_set_error_banner_flag' );

function peachpay_retry_permission() {
	update_option( 'peachpay_api_access_denied', false );
	$url = peachpay_authorize_url();
	wp_redirect( $url );
}

/**
 * Asks the merchant that just activated the plugin for permission to access
 * the store's WooCommerce API.
 */
function peachpay_ask_for_permission( $plugin ) {
	if ( plugin_basename( __FILE__ ) != $plugin ) {
		// Because we run peachpay_ask_for_permission on the activated_plugin hook, it fires
		// when any plugin is activated, not just ours. Exit if not ours.
		return;
	}
	if ( peachpay_has_valid_key() ) {
		// If the store has already given us their WooCommerce API keys, we
		// don't need to ask for them again.
		return;
	}
	peachpay_email_us();
	peachpay_email_merchant_welcome();
	update_option( 'peachpay_api_access_denied', false );
	$url = peachpay_authorize_url();
	wp_redirect( $url );
	exit();
}

function peachpay_admin_notice_retry_permission() {
	if ( isset( $_GET['retry_permission'] ) && $_GET['retry_permission'] === '1' ) {
		peachpay_retry_permission();
		exit();
	}
	if ( get_option( 'peachpay_api_access_denied' ) ) {
		$retry_url = get_site_url() . '/wp-admin/admin.php?page=peachpay&retry_permission=1';
		$message = "PeachPay will not work without access to WooCommerce. To continue setting up PeachPay, you will need to <a href=\"$retry_url\">choose \"Approve\" on the permission screen</a>. You can use PeachPay in test mode without giving permission.";
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( $message, 'peachpay' ),
			'error'
		);
	}
}

/**
 * Send a deacivation email to us so that we can follow up if needed.
 */
function peachpay_send_deactivation_email() {
	if ( peachpay_is_site( 'localhost' ) || peachpay_is_site( 'peachpay.app' ) ) {
		return;
	}
	$body = wp_json_encode( array(
		'merchant_url'         => get_site_url(),
		'merchant_admin_email' => get_bloginfo( 'admin_email' ),
		'stripe_connected'     => (bool) get_option( 'peachpay_connected_stripe_account' ),
		'paypal_connected'     => (bool) get_option( 'peachpay_paypal_signup' ),
		'salesLastMonth'       => peachpay_sales_last_month(),
		'salesYTD'             => peachpay_sales_ytd(),
	) );
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
	// returned explicitly
	return get_option( 'peachpay_valid_key' ) ? true : false;
}

function peachpay_load_styles() {
	wp_enqueue_style(
		'peachpay-css',
		plugin_dir_url( __FILE__ ) . 'css/peachpay.css',
		array(),
		peachpay_file_version( 'css/peachpay.css' )
	);
}

/**
 * Adds the JavaScript files to the page as it loads. These JavaScript
 * files insert the PeachPay button among other things.
 */
function peachpay_load_button_scripts() {
	if ( function_exists( 'is_product' ) && is_product() ) {
		if ( apply_filters( 'peachpay_hide_button_on_product_page', false ) ) {
			return;
		}

		// We don't yet support the fields required for buying a gift card product added by "PW WooCommerce Gift Cards".
		if ( wc_get_product()->get_type() === 'pw-gift-card' ) {
			return;
		}

		// This merchant does not want PeachPay appearing on free items because
		// we show the credit card field. Our choice here is intentional so
		// that customers get one click when returning to items that cost money.
		if (
			strpos( get_site_url(), 'mattsteady.com' ) !== false
			&& intval( wc_get_product()->get_price() ) === 0
		) {
			return;
		}
	}

	if ( is_cart() && apply_filters( 'peachpay_hide_button_on_cart_page', false ) ) {
		return;
	}

	if ( is_checkout() && apply_filters( 'peachpay_hide_button_on_checkout_page', false ) ) {
		return;
	}

	wp_enqueue_script(
		'pp-sentry-lib',
		'https://js.sentry-cdn.com/dd5e3292f8514baa872dcead0794f805.min.js',
		array(),
		1
	);
	wp_enqueue_script(
		'pp-sentry',
		plugin_dir_url( __FILE__ ) . 'js/sentry.js',
		array( 'pp-sentry-lib' ),
		peachpay_file_version( 'js/sentry.js' )
	);

	if ( peachpay_get_settings_option("peachpay_payment_options", "stripe_payment_request") ){
		wp_enqueue_script(
			'pp-stripe-lib',
			'https://js.stripe.com/v3/',
			array( ),
			1
		);

		wp_enqueue_script(
			'pp-stripe',
			plugin_dir_url( __FILE__ ) . 'js/peachpay.bundle.js',
			array('pp-stripe-lib' ),
			peachpay_file_version( 'js/peachpay.bundle.js' )
		);

	}

	wp_enqueue_script(
		'pp-translations',
		plugin_dir_url( __FILE__ ) . 'js/translations.js',
		array(),
		peachpay_file_version( 'js/translations.js' )
	);

	wp_enqueue_script(
		'pp-giftcards',
		plugin_dir_url( __FILE__ ) . 'js/giftcard.js',
		array(),
		peachpay_file_version( 'js/giftcard.js' )
	);

	wp_enqueue_script(
		'pp-coupons',
		plugin_dir_url( __FILE__ ) . 'js/coupon.js',
		array(),
		peachpay_file_version( 'js/coupon.js' )
	);

	wp_enqueue_script(
		'pp-button-product-page',
		plugin_dir_url( __FILE__ ) . 'js/product-page.js',
		array(),
		peachpay_file_version( 'js/product-page.js' )
	);

	wp_enqueue_script(
		'pp-button-cart-page',
		plugin_dir_url( __FILE__ ) . 'js/cart-page.js',
		array(),
		peachpay_file_version( 'js/cart-page.js' )
	);

	wp_enqueue_script(
		'pp-button-checkout-page',
		plugin_dir_url( __FILE__ ) . 'js/checkout-page.js',
		array(),
		peachpay_file_version( 'js/checkout-page.js' )
	);

	wp_enqueue_script(
		'pp-button-core',
		plugin_dir_url( __FILE__ ) . 'js/button.js',
		array( 'pp-translations' ),
		peachpay_file_version( 'js/button.js' )
	);

	wp_enqueue_script(
		'pp-button-shortcode',
		plugin_dir_url( __FILE__ ) . 'js/shortcode.js',
		array(),
		peachpay_file_version( 'js/shortcode.js' ),
		true
	);

	wp_enqueue_script(
		'pp-upsell',
		plugin_dir_url( __FILE__ ) . 'js/linked-products.js',
		array(),
		peachpay_file_version( 'js/linked-products.js' )
	);

	wp_enqueue_script(
		'pp-quantity-changer',
		plugin_dir_url( __FILE__ ) . 'js/quantity-changer.js',
		array(),
		peachpay_file_version( 'js/quantity-changer.js' )
	);

	add_shortcode( 'peachpay', 'peachpay_shortcode' );

	$general_options = get_option( 'peachpay_general_options' );
	$payment_options = get_option( 'peachpay_payment_options' );
	$button_options = get_option( 'peachpay_button_options');

	wp_localize_script(
		'pp-button-core',
		'php_data',
		// This filter is to allow plugin compatibility to allow plugins to add meta data dynamically so we can 1 reduce
		// what we have to send but also be loosely coupled with plugins we support. If the data will always be present
		// then it should be added directly here.
		apply_filters(
			'peachpay_script_data',
			array(
				'_wpnonce'                                 => wp_create_nonce( 'woocommerce-process_checkout' ),
				'apply_coupon_nonce'                       => wp_create_nonce( 'apply-coupon' ),
				'add_to_cart_nonce'                        => wp_create_nonce( 'peachpay_add_to_cart' ),
				'empty_cart_nonce'                         => wp_create_nonce( 'peachpay_empty_cart' ),
				'order_status_nonce'                       => wp_create_nonce( 'peachpay_set_order_status' ),
				// Use to define new feature support going forward
				'feature_support'                          => peachpay_feature_support_record(),
				'merchant_name'                            => get_bloginfo( 'name' ),
				'wp_site_url'                              => get_site_url(),
				'wp_admin_or_editor'                       => current_user_can( 'editor' ) || current_user_can( 'administrator' ),
				'wp_ajax_url'                              => admin_url( 'admin-ajax.php', 'relative' ),
				'version'                                  => PEACHPAY_VERSION,
				'num_shipping_zones'                       => count( WC_Shipping_Zones::get_zones() ),
				'cart'                                     => peachpay_get_cart(),
				'cart_fees'                                => is_cart() ? WC()->cart->get_fees() : null,
				'cart_coupons'                             => is_cart() ? WC()->cart->get_coupon_discount_totals() : array(),
				'cart_coupon_names'                        => is_cart() ? peachpay_cart_coupon_names() : array(),
				'enable_coupons'                           => isset( $general_options['enable_coupons'] ) ? $general_options['enable_coupons'] : null,
				'enable_order_notes'                       => isset( $general_options['enable_order_notes'] ) ? $general_options['enable_order_notes'] : false,
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
				// This one is different from `cart` because it is always the actual cart,
				// whereas the other one is only the product page item if on a product page.
				'wc_cart'                                  => ( isset( WC()->cart ) && '' !== WC()->cart ) ? peachpay_make_cart_from_wc_cart( WC()->cart->get_cart() ) : array(),
				'debug_cart'                               => WC()->cart,
				'wc_prices_include_tax'                    => wc_prices_include_tax(),
				'wc_tax_price_display'                     => ( isset( WC()->cart ) && '' !== WC()->cart ) ? WC()->cart->get_tax_price_display_mode() : '',
				'wc_order_received_url'                    => wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ),
				'wc_store_country_code'                    => WC()->countries->get_base_country(),
				'wc_customer_default_location'             => wc_get_customer_default_location(),
				'wc_location_info'                         => peachpay_location_details(),
				'test_mode'                                => isset( $general_options['test_mode'] ) ? $general_options['test_mode'] : null,
				'connected_stripe_account'                 => (bool) get_option( 'peachpay_connected_stripe_account' ),
				// This is the "Enable PayPal" checkbox in the Payment Methods tab of the plugin settings
				'paypal'                                   => isset( $payment_options['paypal'] ) ? $payment_options['paypal'] : null,
				'language'                                 => isset( $general_options['language'] ) ? $general_options['language'] : 'en-US',

				'button_color'                             => isset( $button_options['button_color'] ) ? $button_options['button_color'] : '#FF876C',
				'button_icon'                              => peachpay_get_settings_option( 'peachpay_button_options' ,'button_icon') ? peachpay_get_settings_option( 'peachpay_button_options' ,'button_icon') : 'lock',
				'button_border_radius'                     => peachpay_get_settings_option( 'peachpay_button_options' , 'button_border_radius' ),
				'button_text'                              => ( isset( $button_options['peachpay_button_text'] ) && '' !== $button_options['peachpay_button_text'] ) ? $button_options['peachpay_button_text'] : peachpay_get_button_text(),
				'button_position_product_page'             => isset( $button_options['product_button_position'] ) ? $button_options['product_button_position'] : null,
				'button_position_cart_page'                => isset( $button_options['cart_button_position'] ) ? $button_options['cart_button_position'] : null,
				'button_position_checkout_page'            => isset( $button_options['checkout_button_position'] ) ? $button_options['checkout_button_position'] : null,
				'button_width_product_page'                => isset( $button_options['button_width_product_page'] ) ? $button_options['button_width_product_page'] : null,
				'button_width_cart_page'                   => isset( $button_options['button_width_cart_page'] ) ? $button_options['button_width_cart_page'] : null,
				'button_width_checkout_page'               => isset( $button_options['button_width_checkout_page'] ) ? $button_options['button_width_checkout_page'] : null,
				'button_sheen'                             => peachpay_get_settings_option( 'peachpay_button_options' , 'button_sheen' ),
				'button_hide_on_product_page'              => peachpay_get_settings_option( 'peachpay_button_options' , 'hide_on_product_page' ),
				'button_hide_payment_method_icons'         => peachpay_get_settings_option( 'peachpay_button_options' , 'button_hide_payment_method_icons' ),

				'should_place_order_before_payment'              => should_place_order_before_payment(),
				'plugin_woocommerce_product_addon'               => is_plugin_active( 'woocommerce-product-addon/woocommerce-product-addon.php' ),
				'plugin_woocommerce_points_and_rewards_active'   => is_plugin_active( 'woocommerce-points-and-rewards/woocommerce-points-and-rewards.php' ),
				'plugin_woocommerce_order_delivery_options'      => woocommerce_order_delivery_options(),
				'plugin_woocommerce_order_delivery_active'       => is_plugin_active( 'woocommerce-order-delivery/woocommerce-order-delivery.php' ),
				'plugin_routeapp_active'                         => is_plugin_active( 'routeapp/routeapp.php' ),
				'plugin_woo_thank_you_page_nextmove_lite_active' => is_plugin_active( 'woo-thank-you-page-nextmove-lite/thank-you-page-for-woocommerce-nextmove-lite.php' ),
				'is_shortcode'                                   => false,
				'hide_peachpay_upsell'                           => peachpay_get_settings_option( 'peachpay_general_options', 'hide_woocommerce_products_upsell' ),
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
		'allow_guest_checkout'            => 'yes' === get_option( 'woocommerce_enable_guest_checkout'),
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
			"cart_calculation" => array(
				"enabled" => true,
				"version" => 1,
			),
			"coupon_input" => array(
				"enabled" => peachpay_get_settings_option("peachpay_general_options", "enable_coupons"),
				"version" => 1,
			),
			"order_notes_input" => array(
				"enabled" => peachpay_get_settings_option("peachpay_general_options", "enable_order_notes"),
				"version" => 1,
			),
			"stripe_payment_method" => array(
				"enabled" => peachpay_get_settings_option("peachpay_payment_options", "enable_stripe"),
				"version" => 1,
			),
			"stripe_payment_request" => array(
				"enabled" => peachpay_get_settings_option("peachpay_payment_options", "stripe_payment_request") && $show_payment_request_option,
				"version" => 1
			),
			"quantity_changer" => array(
				"enabled" => true,
				"version" => 1
			)
		);

	return (array) apply_filters("peachpay_register_feature", $base_features);
}

function peachpay_file_version( $file ) {
	return gmdate( 'ymd-Gis', filemtime( plugin_dir_path( __FILE__ ) . $file ) );
}

define(
	'BUTTON_TEXT_TRANSLATION',
	array(
		'ar' => 'الخروج السريع',
		'ca' => 'Pagament exprés',
		'cs-CZ' => 'Expresní pokladna',
		'da-DK' => 'Hurtig betaling',
		'de-DE' => 'Expresskauf',
		'el' => 'Γρήγορο ταμείο',
		'en-US' => 'Express checkout',
		'es-ES' => 'Chequeo rápido',
		'fr' => 'Acheter maintenant',
		'hi-IN' => 'स्पष्ट नियंत्रण',
		'it' => 'Cassa rapida',
		'ja' => 'エクスプレスチェックアウト',
		'ko-KR' => '익스프레스 체크아웃',
		'lb-LU' => 'Express Kees',
		'nl-NL' => 'Snel afrekenen',
		'pt-PT' => 'Checkout expresso',
		'ro-RO' => 'Cumpără cu 1-click',
		'ru-RU' => 'Экспресс-касса',
		'sl-SI' => 'Hitra odjava',
		'sv-SE' => 'snabbkassa',
		'th' => 'ชำระเงินด่วน',
		'uk' => 'Експрес -оплата',
		'zh-CN' => '快速结帐',
		'zh-TW' => '快速結帳',
	)
);

/**
 * Duplicate of peachpay_to_our_language_key in settings.php until we refactor
 * so that it can be used in both places.
 */
function peachpay_to_our_language_key_temp( $languageCodeOrLocale ) {
	switch ( $languageCodeOrLocale ) {
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
			return $languageCodeOrLocale;
	}
}

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
 * Returns an array of products that are in the "cart". If we are on a single
 * product page, the returned cart is only the single item regardless of the
 * actual contents of the cart.
 */
function peachpay_get_cart() {
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product();

		if ( get_site_url() === 'https://www.jogiachamasalo.com' || get_site_url() === 'https://www.jogiachamasalo.com/' ) {
			$product = wc_get_product( 160 );
		}

		if ( $product ) {
			return peachpay_product_page_cart( $product );
		}
	}

	if ( is_null( WC()->cart ) ) {
		return array();
	}

	return peachpay_make_cart_from_wc_cart( WC()->cart->get_cart() );
}

function peachpay_product_page_cart( WC_Product $product ) {
	if ( ! isset( $product ) || ! $product ) {
		return array();
	}

	// Grouped products.
	$child_ids = $product->get_children();

	if ( $product->get_type() === 'grouped' && $child_ids ) {
		$children = array();
		foreach ( $child_ids as $id ) {
			array_push( $children, wc_get_product( $id ) );
		}
		return peachpay_make_product_page_cart( $children, true );
	}

	// WooCommerce Product Bundles plugin.
	if ( $product->get_type() === 'bundle' ) {
		$cart = array();
		array_push( $cart, $product );
		foreach ( $product->get_bundled_items() as $item ) {
			array_push( $cart, wc_get_product( $item->get_product() ) );
		}

		return peachpay_make_product_page_cart( $cart, false, true );
	}

	return peachpay_make_product_page_cart( array( $product ) );
}

function peachpay_make_product_page_cart( $products, $is_grouped = false, $is_bundled = false ) {
	$pp_cart = array();

	foreach ( $products as $wc_product ) {
		$price = peachpay_product_price( $wc_product );
		// People might not buy one of the grouped items, so allow 0 quantity.
		$quantity = $is_grouped ? 0 : null;

		// The first check is for whether is group of products **contains** a parent bundle product,
		// and the second check is for whether the current product in the loop **is** the
		// parent bundle product.
		if ( $is_bundled && $wc_product->get_type() !== 'bundle' ) {
			// Only the parent in a bundle has a price.
			$price    = 0;
			$quantity = 1;
		}

		$pp_cart_item = array(
			'product_id'   => $wc_product->get_id(),
			'variation_id' => $wc_product->get_id(), // no variation support on grouped products for now.
			'name'         => $wc_product->get_name(),
			'price'        => $price,
			'display_price'=> peachpay_product_display_price( $wc_product ),
			'quantity'     => $quantity,
			'virtual'      => $wc_product->is_virtual(),
			'subtotal'     => strval( $price ), // subtotal and total are only relevant for what shows up in the order dashboard.
			'total'        => strval( $price ),
			'attributes'   => peachpay_product_variation_attributes( $wc_product->get_id()),
			'meta_data'    => array(),
			'image'        => peachpay_product_image( $wc_product ),
			'on_sale'             => $wc_product->is_on_sale(),
			'product_categories'  => $wc_product->get_category_ids(),
			'upsell_items'        => peachpay_upsell_items ( $wc_product ),
		);

		// Don't show the price on the order dashboard for items that are part of a bundle.
		if ( $is_bundled && $wc_product->get_type() !== 'bundle' ) {
			$pp_cart_item['subtotal']          = '0';
			$pp_cart_item['total']             = '0';
			$pp_cart_item['is_part_of_bundle'] = true;
		}

		// Apply meta data for compatibility. This filter can be hooked into anywhere to add needed meta data to cart items on the cart page.
		array_push( $pp_cart, apply_filters( 'peachpay_product_page_line_item', $pp_cart_item, $wc_product ) );
	}

	return $pp_cart;
}

/**
 * Checks if an item is a variation if so it will get the parent name so we can
 * use variations as subtitles if not returns the product's name.
 *
 * @param int $id the product ID.
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
		$wc_product = peachpay_product_from_line_item($wc_line_item);
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
			'variation'           => $wc_line_item[ 'variation' ], // This is the actual selected variation attributes
			'attributes'          => peachpay_product_variation_attributes( $wc_product->get_id() ),
			'image'               => peachpay_product_image( $wc_product ),
			'upsell_items'        => peachpay_upsell_items ( $wc_product ),
			'cross_sell_items'    => peachpay_cross_sell_items ( $wc_product ),
			'item_key'			  => $wc_line_item[ 'key' ],

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

function peachpay_woocommerce_ajax_add_to_cart() {
	if ( ! isset( $_POST['product_id'] ) || ! isset( $_POST['variation_id'] ) ) {
		wp_send_json_error( 'Missing required parameters', 400 );
	}

	$product_id   = absint( $_POST['product_id'] );
	$quantity     = empty( $_POST['quantity'] ) ? 0 : wc_stock_amount( sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) );
	$variation_id = absint( $_POST['variation_id'] );

	if ( peachpay_product_in_cart( $product_id, $variation_id ) ) {
		wp_send_json_success( 'Product already in cart; no need to add again' );
	}

	$cart_item_key = peachpay_non_redirect_add_to_cart($product_id, $quantity, $product_id === $variation_id ? 0 : $variation_id);

	if ( ! $cart_item_key ) {
		wp_send_json_error( 'Could not add product to cart', 400 );
	}

	if ( is_plugin_active( 'woocommerce-product-addon/woocommerce-product-addon.php' ) ) {
		ppom_woocommerce_add_cart_item_data( WC()->cart->get_cart_item( $cart_item_key ), $product_id );
	}

	wp_send_json_success(
		array(
			'message'            => "Successfully added product ID $product_id, variation ID $variation_id, quantity $quantity to cart (key $cart_item_key)",
			// Sometimes (cause not known), calling WC()->cart->add_to_cart() resets
			// either the session or checkout nonce, causing the order placement to
			// fail because the nonce provided on page load is invalid. To quickly
			// fix the issue we can generate a new nonce after the problematic
			// function call and use it in place of the original checkout nonce.
			'_wpnonce'           => wp_create_nonce( 'woocommerce-process_checkout' ),
		)
	);
}

/**
 * This is to override other people from adding a custom add_to_cart_redirect when
 * we are just trying to get some custom data for our plugin.
 */
function peachpay_do_not_redirect_checkout_add_cart_filter($url, $adding_to_cart) {
	if("yes" === get_option("peachpay_deny_add_to_cart_redirect")){
		return "";
	}

	return $url;
}

/**
 * Adds an item to the cart while disabling any add_to_cart_redirect functionality.
 */
function peachpay_non_redirect_add_to_cart( $product_id, $quantity, $variation_id, $variations = array(), $cart_item_data = array() ) {
	// Only need the filter with this call to ensure other custom redirects are not followed
	add_filter( 'woocommerce_add_to_cart_redirect', 'peachpay_do_not_redirect_checkout_add_cart_filter', PHP_INT_MAX, 2 ); // Want the highest priority

	$original_cart_redirect_after_add = get_option( 'woocommerce_cart_redirect_after_add' );
	$qlwcdc = get_option( 'qlwcdc_add_to_cart' ); // Get WC Direct Checkout

	update_option( 'qlwcdc_add_to_cart', 'no' ); // Disable WC Direct Checkout
	update_option( 'woocommerce_cart_redirect_after_add', 'no' ); // Not sure if the frowned upon changing settings like this but it works
	update_option( 'peachpay_deny_add_to_cart_redirect', 'yes' );

	$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variations, $cart_item_data );

	update_option( 'peachpay_deny_add_to_cart_redirect', 'no' );
	update_option( 'qlwcdc_add_to_cart', $qlwcdc ); // Restore WC Direct Checkout
	update_option( 'woocommerce_cart_redirect_after_add', $original_cart_redirect_after_add );

	return $cart_item_key;
}

function peachpay_product_in_cart( $product_id, $variation_id ) {
	foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
		$_product = $values['data'];
		if ( $product_id === $_product->get_id() || $variation_id === $_product->get_id() ) {
			return true;
		}
	}
	return false;
}

function peachpay_wc_ajax_empty_cart() {
	WC()->cart->empty_cart();
}

function peachpay_wc_ajax_order_payment_complete() {
	if ( ! isset( $_POST['order_id'] ) || ! isset( $_POST['_peachpay_stripe_customer_id'] )
	|| ! isset( $_POST['payment_type'] ) || ! isset( $_POST['transaction_id'] ) ) {
		wp_send_json_error( 'Missing required parameters', 400 );
	}
	$_POST['order_status_nonce'] = wp_create_nonce( 'peachpay_set_order_status' );

	$order_id  = sanitize_text_field( wp_unslash( $_POST['order_id'] ) );
	$stripe_id = sanitize_text_field( wp_unslash( $_POST['_peachpay_stripe_customer_id'] ) );
	$order     = wc_get_order( $order_id );

	if ( '' !== $_POST['transaction_id'] ) {
		$order->set_transaction_id( sanitize_text_field( wp_unslash( $_POST['transaction_id'] ) ) );
	}

	$order->add_meta_data("peachpay_is_test_mode", peachpay_is_test_mode() ? "true" : "false");

	$order->payment_complete();

	if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) && wcs_order_contains_subscription( $order, 'parent' ) ) {
		peachpay_set_stripe_order_payment_meta( $order_id, $stripe_id );
	}
}

/**
 * Gets any available payment information from an order.
 *
 * @param int $order_id The original order id that payment information was stored in.
 * @return Array
 */
function peachpay_get_order_payment_meta( int $order_id ) {
	return get_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, true );
}

/**
 * Sets peachpay order payment meta related to stripe
 *
 * @param int    $order_id The original order id that payment information was stored in.
 * @param string $stripe_customer_id The customer stripe id to store.
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
 * @param int $order_id The original order id that payment information was stored in.
 * @return string
 */
function peachpay_get_stripe_order_payment_meta( int $order_id ) {
	$data = get_post_meta( $order_id, PEACHPAY_PAYMENT_META_KEY, true );
	return ( 'stripe' === $data['payment_type'] ? $data['customer_id'] : '' );
}

function peachpay_wc_ajax_order_failed() {
	if ( ! isset( $_POST['order_id'] ) ) {
		wp_send_json_error( 'Missing required parameters', 400 );
	}
	$order = wc_get_order( $_POST['order_id'] );
	$order->set_status( 'failed' );
	$order->save();
	$order->add_order_note( 'Payment failed. Reason: ' . $_POST['payment_failure_reason'] );
	wp_send_json( array( 'success' => true ) );
}

function peachpay_cart_coupon_names() {
	if ( ! is_cart() ) {
		return;
	}
	$coupons = WC()->cart->get_coupon_discount_totals();
	$names   = array();
	foreach ( $coupons as $coupon_code => $amount ) {
		$match = array();
		preg_match( '/(?<=wc_)([^\d])*(?=_\d+)/', $coupon_code, $match );
		if($match && $match[ 0 ]){
			$names[ $coupon_code ] = ucfirst( str_replace( '_', ' ', $match[ 0 ] ) );
		}
	}
	return $names;
}



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
			if ( $wc_od_delivery_days_single['enabled'] === 'no' ) {
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

function peachpay_collect_debug_info( array $php_data ) {
	$php_data[ "debug" ] = array(
		"peachpay" => array(
			"version"    => PEACHPAY_VERSION,
			"test_mode"  => peachpay_is_test_mode(),
			"feature_support" => array("alerts")
		),
		"plugins" => array()
	);

	try{
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();

		foreach($plugins as $plugin_key => $plugin_data){
			$php_data[ "debug" ]["plugins"][$plugin_key] = array(
					"name" => $plugin_data['Name'],
					"version" => $plugin_data[ "Version" ],
					"pluginURI" => $plugin_data[ "PluginURI" ],
					"active" => is_plugin_active($plugin_key)
			);
		}
	} catch (Exception $ex) {
		// Don't break anything
	}

	if(peachpay_is_site("manicci.com") || peachpay_is_site("store.local")){
		$php_data[ "debug" ]["manicci"] = array(
			"actions" => $GLOBALS['wp_filter']["woocommerce_add_to_cart"],
			"filters" => $GLOBALS['wp_filter']["woocommerce_add_to_cart_redirect"]
		);
	}

	return $php_data;
}

/**
 * Determines if the given site is active
 *
 * @param string $site The site domain to check.
 */
function peachpay_is_site( string $site ) {
	return strpos( get_site_url(), $site ) !== false;
}

function peachpay_checkout_url() {
	if ( peachpay_is_test_mode() ) {
		switch ( get_site_url() ) {
			case 'http://localhost':
			case 'http://127.0.0.1':
				return 'http://localhost:8080';
			case 'https://store.local':
			case 'https://woo.store.local':
				return 'https://dev-connect.peachpay.local'; // Local HTTPS
			default:
				return 'https://dev-connect.peachpaycheckout.com';
		}
	}

	switch ( get_site_url() ) {
		case 'http://localhost':
		case 'http://127.0.0.1':
			return 'http://localhost:8080';
		case 'https://woo.peachpay.app':
		case 'https://theme1.peachpay.app':
		case 'https://theme2.peachpay.app':
		case 'https://theme3.peachpay.app':
		case 'https://theme4.peachpay.app':
		case 'https://theme5.peachpay.app':
		case 'https://qa.peachpay.app':
		case 'https://demo.peachpay.app':
			return 'https://dev-connect.peachpaycheckout.com';
		case 'https://store.local':
		case 'https://woo.store.local':
			return 'https://connect.peachpay.local'; // Local HTTPS
		default:
			return 'https://connect.peachpaycheckout.com';
	}
}

function peachpay_shortcode( $atts ) {
	$attributes = shortcode_atts([
		'product_id' => null,
	], $atts);

	if ( is_null( $attributes['product_id'] ) ) {
		return;
	}

	$product = wc_get_product( (int) $attributes['product_id'] );

	if ( is_null( $product ) || ! $product ) {
		return;
	}

	wp_localize_script(
		'pp-button-shortcode',
		'peachpayShortcodeData',
		array( 'cart' => peachpay_product_page_cart( $product ) )
	);

	$options = get_option( 'peachpay_button_options' );
	$button_text = ( isset( $options['peachpay_button_text'] ) && $options['peachpay_button_text'] !== '' ) ? $options['peachpay_button_text'] : peachpay_get_button_text();
	$dark = gethostname() === 'www.blazecandles.co' ? '-dark' : '';
	$spinnerURL= peachpay_checkout_url() . '/img/spinner' . $dark . '.svg';
	$width = 'width:'.$options['button_width_product_page'].'px;';
	$color = '--button-color:'.$options['button_color'].';';
	$style = $width.$color;

	$output = '
	<div id="pp-button-container" class="button-container pp-button-container">
		<button id="pp-button" class="pp-button" type="button" style='.$style.'>
		<img src='.$spinnerURL.' id="loading-spinner" class="pp-spinner hide">
		<div id="pp-button-content">
			<span id="pp-button-text">'.$button_text.'</span>
		</div>
		</button>
	</div>';

	return $output;
}

register_activation_hook( __FILE__, 'peachpay_record_activation' );
function peachpay_record_activation() {
	peachpay_record_analytics( true, PEACHPAY_VERSION );
}

register_deactivation_hook( __FILE__, 'record_record_deactivation' );
function record_record_deactivation() {
	peachpay_record_analytics( false, PEACHPAY_VERSION );
}

function peachpay_product_quantity_changer() {
	if ( ! isset( $_POST['key'] ) || ! isset( $_POST['value']) ) {
		wp_send_json_error( 'Missing required parameters: key and value', 400 );
	}

	$item_key = $_POST['key'];
	$value = $_POST['value'];

	foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        // Check for specific item key and change its quantity
		if($item_key === $cart_item_key && $cart_item['quantity'] >= 1 ){
			$oldQty = $cart_item['quantity'];
			$qty = $cart_item['quantity'] + ($value);
			$newQty = $qty;
			WC()->cart->set_quantity($cart_item_key, $qty);
        }
    }

	wp_send_json_success(
		array(
			'success' => true,
			'message' => "Changed quantity on $cart_item_key from $oldQty to $newQty",
		)
	);
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
