<?php
/**
 * Plugin Name: PeachPay Checkout and Payments for WooCommerce: Stripe, PayPal, Square
 * Plugin URI: https://woocommerce.com/products/peachpay
 * Description: PeachPay is supercharging WooCommerce checkout and payments. Connect and manage all your payment methods, offer shoppers a beautiful Express Checkout, and track cart abandonment analytics, all from one place.
 * Version: 1.91.5
 * Author: PeachPay, Inc.
 * Author URI: https://peachpay.app
 *
 * WC requires at least: 5.0
 * WC tested up to: 7.6
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

define( 'PEACHPAY_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'PEACHPAY_VERSION', get_plugin_data( __FILE__ )['Version'] );
define( 'PEACHPAY_BASENAME', plugin_basename( __FILE__ ) );
define( 'PEACHPAY_PLUGIN_FILE', __FILE__ );
define( 'PEACHPAY_ROUTE_BASE', 'peachpay/v1' );


define( 'PEACHPAY_DEFAULT_BACKGROUND_COLOR', '#21105d' );
define( 'PEACHPAY_DEFAULT_TEXT_COLOR', '#FFFFFF' );

require_once PEACHPAY_ABSPATH . 'core/error-reporting.php';
require_once PEACHPAY_ABSPATH . 'core/util/util.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migration.php';

require_once PEACHPAY_ABSPATH . 'core/class-peachpay.php';
require_once PEACHPAY_ABSPATH . 'core/peachpay-default-options.php';

/**
 * Returns an instance of PeachPay global instance.
 */
function peachpay() {
	return PeachPay::instance();
}

$GLOBALS['peachpay'] = peachpay();

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

//
// Followup(refactor): All following code needs put in proper locations
//

require_once PEACHPAY_ABSPATH . 'core/class-peachpay-initializer.php';
$initializer = new PeachPay_Initializer();
if ( ! $initializer::init() ) {
	// Peachpay should stop setup if init fails for any reason.
	return;
}

// Set default options.
peachpay_set_default_options();

// Load utilities.

// Load independent execution paths or hook initializations(Aka these have side effects of being loaded).
require_once PEACHPAY_ABSPATH . 'core/payments/payment.php';
require_once PEACHPAY_ABSPATH . 'core/modules/module.php';
require_once PEACHPAY_ABSPATH . 'core/hide-peachpay.php';
require_once PEACHPAY_ABSPATH . 'core/product-page-button-locations.php';
require_once PEACHPAY_ABSPATH . 'core/apple-pay.php';

add_action( 'wp', 'peachpay_has_valid_key' );
add_action( 'activated_plugin', 'peachpay_ask_for_wc_permission' );

/**
 * Notifies merchant if there store does not have an HTTPS connection.
 */
function unsecure_connection_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>Your site does not support secure connections (HTTPS). Without a secure connection, PeachPay payment methods may not work because payment providers require this security.</p>
	</div>
	<?php
}

/**
 * Initializes plugin compatibility and loads plugin files.
 */
function peachpay_init() {

	load_plugin_textdomain( 'peachpay-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( is_admin() ) {
		if ( ! isset( $_SERVER['HTTPS'] ) ) {
			add_action( 'admin_notices', 'unsecure_connection_notice' );
		}

		add_action( 'admin_notices', 'peachpay_admin_notice_retry_permission' );

		add_filter( 'bulk_actions-edit-shop_order', 'peachpay_add_bulk_export_csv' );
		add_filter( 'handle_bulk_actions-edit-shop_order', 'peachpay_handle_bulk_export_csv', 10, 3 );
		add_action( 'admin_notices', 'peachpay_bulk_export_csv_notice' );
	}

	if ( peachpay_gateway_available() && ( ! is_admin() || peachpay_is_rest() ) ) {
		// Shortcodes.
		include_once PEACHPAY_ABSPATH . 'core/shortcode.php';

		// Conditionally include frontend js and css.
		if ( ( peachpay_has_valid_key() || peachpay_is_test_mode() ) && ! peachpay_is_rest() ) {
			add_action( 'wp_enqueue_scripts', 'peachpay_load_styles' );
			add_action( 'wp_enqueue_scripts', 'peachpay_load_button_scripts' );

			// Extra Script data to opt in. Currently on by default.
			add_filter( 'peachpay_script_data', 'peachpay_collect_debug_info', 10, 1 );
		}
	}

	// Hides "proceed to checkout" WooCommerce checkout button on the cart page and mini cart.
	if ( peachpay_get_settings_option( 'peachpay_express_checkout_window', 'make_pp_the_only_checkout' ) && ! peachpay_is_test_mode() ) {
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_proceed_to_checkout', 20 );
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
				'plugin'        => 'booster-plus-for-woocommerce/booster-plus-for-woocommerce.php',
				'compatibility' => 'compatibility/booster-for-wc/booster-for-wc.php',
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
			array(
				// Note: Not a typo. Plugin file is spelled "adons".
				'plugin'        => 'essential-addons-for-elementor-lite/essential_adons_elementor.php',
				'compatibility' => 'compatibility/essential-addons-elementor.php',
			),
		)
	);

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
 * Loads Peachpay Elementor support.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function peachpay_load_elementor_widget( $widgets_manager ) {
	try {
		require_once PEACHPAY_ABSPATH . 'compatibility/class-peachpay-elementor-widget.php';

		$widgets_manager->register( new \Elementor\PeachPay_Elementor_Widget() );
		// phpcs:ignore
	} catch ( \Exception $exception ) {
		// Prevent a fatal error if Elementor class could not be loaded for whatever reason.
	}
}
add_action( 'elementor/widgets/register', 'peachpay_load_elementor_widget' );

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
 * We have to use this instead of the null coalescing operator (??) due to
 * compatibility requirements for WooCommerce Marketplace.
 *
 * @param  string     $setting_group The name of the option settings.
 * @param  string     $name          The name of the option in the PeachPay settings.
 * @param  mixed|bool $default       The default value to return if the option is not set.
 * @return mixed|false Returns false if the option does not exist or is empty; otherwise
 * returns the option.
 */
function peachpay_get_settings_option( $setting_group, $name, $default = false ) {
	$options = get_option( $setting_group );

	if ( isset( $options[ $name ] ) && '' !== $options[ $name ] ) {
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
 * Indicates if a response is 2xx.
 *
 * @param array | WP_Error $response The response to check.
 */
function peachpay_response_ok( $response ) {
	$code = wp_remote_retrieve_response_code( $response );

	if ( ! is_int( $code ) ) {
		return false;
	}

	if ( $code < 200 || $code > 299 ) {
		return false;
	}

	return true;
}

/**
 * Gets the merchant logo id or null if not set.
 *
 * @returns int | null
 */
function peachpay_get_merchant_logo_id() {
	$image_id = peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'merchant_logo', null );

	if ( null === $image_id || ! $image_id ) {
		return null;
	}

	return $image_id;
}

/**
 * Gets the merchant logo src url or null if not set.
 *
 * @returns string | null
 */
function peachpay_get_merchant_logo_src() {
	$image_id = peachpay_get_merchant_logo_id();

	if ( null === $image_id ) {
		return null;
	}

	$image_src = wp_get_attachment_image_src( $image_id, 'full' );
	if ( is_array( $image_src ) && array_key_exists( 0, $image_src ) ) {
		return $image_src[0];
	} else {
		return null;
	}
}

/**
 * Indicates if the "Test mode" box is checked in the plugin settings.
 */
function peachpay_is_test_mode() {
	return isset( get_option( 'peachpay_payment_options' )['test_mode'] ) && get_option( 'peachpay_payment_options' )['test_mode'];
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
	$site_url   = admin_url( 'admin.php?page=peachpay' );
	$home_url   = home_url();
	$endpoint   = '/wc-auth/v1/authorize';
	$return_url = peachpay_api_url( ( peachpay_is_staging_site() || peachpay_is_local_development_site() ) ? 'test' : 'prod' )
								. "activation/verify?home_url=$home_url&site_url=$site_url";

	$callback_url_params = array(
		'domain' => $home_url,
	);
	if ( peachpay_plugin_merchant_id() ) {
		$callback_url_params['merchantId'] = peachpay_plugin_merchant_id();
	}

	$callback_url = peachpay_api_url( ( peachpay_is_staging_site() || peachpay_is_local_development_site() ) ? 'test' : 'prod' ) . 'store-token?' . http_build_query( $callback_url_params );

	$params       = array(
		'app_name'     => 'PeachPay',
		'scope'        => 'read_write',
		'user_id'      => 1,
		'return_url'   => $return_url,
		'callback_url' => $callback_url,
	);
	$query_string = http_build_query( $params );
	$url          = $home_url . $endpoint . '?' . $query_string;
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
function peachpay_ask_for_wc_permission( $plugin ) {
	if ( PEACHPAY_BASENAME !== $plugin ) {
		// Because we run this on the activated_plugin hook, it fires
		// when any plugin is activated, not just ours. Exit if not ours.
		return;
	}

	if ( peachpay_has_valid_key() ) {
		// If the store has already given us their WooCommerce API keys, we
		// don't need to ask for them again.
		update_option( 'peachpay_api_access_denied', false );
		return;
	}

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
		$message   = "<span>PeachPay will not work without access to WooCommerce. To continue setting up PeachPay, you will need to <a href=\"$retry_url\">choose \"Approve\" on the permission screen</a>. You can use PeachPay in test mode without giving permission.</span>";
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			$message,
			'error'
		);
	}
}

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
	if ( ! peachpay_gateway_available() ) {
		return;
	}
	wp_enqueue_style(
		'pp-button-css',
		peachpay_url( 'public/dist/express-checkout-button.bundle.css' ),
		array(),
		peachpay_file_version( 'public/dist/express-checkout-button.bundle.css' )
	);
}

/**
 * Adds the JavaScript files to the page as it loads. These JavaScript
 * files insert the PeachPay button among other things.
 *
 * @param array $available_gateways A predetermined (there's a race condition) list of available gateways.
 */
function peachpay_load_button_scripts( $available_gateways ) {
	if ( ! peachpay_gateway_available() ) {
		return;
	}

	add_shortcode( 'peachpay', 'peachpay_shortcode' );

	if ( peachpay_get_settings_option( 'peachpay_express_checkout_button', 'floating_button_enabled' ) ) {
		add_action( 'loop_end', 'peachpay_render_floating_button' );
	}

	wp_enqueue_script(
		'pp-sentry-lib',
		'https://browser.sentry-cdn.com/7.28.1/bundle.min.js',
		array(),
		1,
		false
	);

	wp_enqueue_script(
		'pp-button-js',
		peachpay_url( 'public/dist/express-checkout-button.bundle.js' ),
		array(),
		peachpay_file_version( 'public/dist/express-checkout-button.bundle.js' ),
		false
	);

	wp_localize_script(
		'pp-button-js',
		'peachpay_data',
		// This filter is to allow plugin compatibility to allow plugins to add meta data dynamically so we can 1 reduce
		// what we have to send but also be loosely coupled with plugins we support. If the data will always be present
		// then it should be added directly here.
		apply_filters(
			'peachpay_script_data',
			array(
				'checkout_nonce'                           => wp_create_nonce( 'peachpay_process_checkout' ),
				'apply_coupon_nonce'                       => wp_create_nonce( 'apply-coupon' ),
				'remove_coupon_nonce'                      => wp_create_nonce( 'remove-coupon' ),
				'version'                                  => PEACHPAY_VERSION,
				'test_mode'                                => peachpay_is_test_mode(),
				'feature_support'                          => peachpay_feature_support_record( $available_gateways ),
				'plugin_asset_url'                         => peachpay_url( '' ),

				'merchant_name'                            => get_bloginfo( 'name' ),
				'wp_site_url'                              => site_url(),
				'wp_home_url'                              => home_url(),
				'wp_hostname'                              => preg_replace( '(^https?://)', '', home_url() ),
				'wp_admin_or_editor'                       => current_user_can( 'editor' ) || current_user_can( 'administrator' ),

				'wp_ajax_url'                              => admin_url( 'admin-ajax.php', 'relative' ),
				'num_shipping_zones'                       => count( WC_Shipping_Zones::get_zones() ),
				'merchant_customer_account'                => peachpay_get_merchant_customer_account(),
				'currency_info'                            => peachpay_get_currency_info(),
				'is_category_page'                         => is_product_category(),
				'is_cart_page'                             => is_cart(),
				'cart_page_hide'                           => apply_filters( 'peachpay_hide_button_on_cart_page', false ),
				'is_checkout_page'                         => is_checkout(),
				'checkout_page_hide'                       => apply_filters( 'peachpay_hide_button_on_checkout_page', false ),
				'is_shop_page'                             => is_shop(),
				'is_product'                               => is_product(),
				'product_type'                             => peachpay_product_type(),
				'wc_cart_url'                              => wc_get_cart_url(),
				'has_valid_key'                            => peachpay_has_valid_key(),
				'wc_prices_include_tax'                    => wc_prices_include_tax(),
				'wc_tax_price_display'                     => ( isset( WC()->cart ) && '' !== WC()->cart ) ? WC()->cart->get_tax_price_display_mode() : '',
				'wc_location_info'                         => peachpay_location_details(),
				'language'                                 => peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language', 'en-US' ),
				'support_message'                          => peachpay_get_settings_option( 'peachpay_express_checkout_window', 'support_message', '' ),
				'support_message_type'                     => peachpay_get_settings_option( 'peachpay_express_checkout_window', 'support_message_type', 'inline' ),
				'wc_terms_conditions'                      => peachpay_wc_terms_condition(),
				'custom_checkout_css'                      => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_checkout_css', '' ),

				'merchant_id'                              => peachpay_plugin_merchant_id(),
				'order_status_endpoint'                    => get_rest_url( null, 'peachpay/v1/order/status' ),

				'button_color'                             => peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_color', PEACHPAY_DEFAULT_BACKGROUND_COLOR ),
				'button_text_color'                        => peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'button_text_color', PEACHPAY_DEFAULT_TEXT_COLOR ),
				'floating_button_icon'                     => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'floating_button_icon', 'shopping_cart' ),
				'button_icon'                              => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_icon', 'none' ),
				'button_border_radius'                     => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_border_radius', 5 ),
				'button_text'                              => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'peachpay_button_text', peachpay_get_translated_text( 'button_text' ) ),
				'button_alignment_product_page'            => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'product_button_alignment', null ),
				'button_mobile_product_page'               => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'product_button_mobile_position', 'default' ),
				'button_alignment_cart_page'               => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'cart_button_alignment', null ),
				'button_alignment_checkout_page'           => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'checkout_button_alignment', null ),
				'button_width_product_page'                => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_width_product_page', null ),
				'button_width_cart_page'                   => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_width_cart_page', null ),
				'button_width_checkout_page'               => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_width_checkout_page', null ),
				'button_effect'                            => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_effect', 'fade' ),
				'disable_default_font_css'                 => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'disable_default_font_css' ),
				'button_available_icons'                   => PeachPay_Payment::available_gateway_icons(),
				'button_display_on_product_page'           => apply_filters( 'peachpay_hide_button_on_product_page', false ),
				'button_display_payment_method_icons'      => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_display_payment_method_icons' ),
				'button_custom_css'                        => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_button_css', '' ),
				'button_custom_classes'                    => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_button_class', '' ),
				'custom_checkout_js'                       => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_checkout_js', '' ),
				'custom_button_placement'                  => peachpay_get_custom_button_placement(),

				'header_text_checkout_page'                => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'checkout_header_text', peachpay_get_translated_text( 'header_text_checkout_page' ) ),
				'subtext_text_checkout_page'               => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'checkout_subtext_text', peachpay_get_translated_text( 'subtext_text_checkout_page' ) ),
				'display_checkout_outline'                 => peachpay_get_settings_option( 'peachpay_express_checkout_button', 'display_checkout_outline' ),

				'is_shortcode'                             => false,
				// @deprecated Use feature flags going forward.
				'plugin_woocommerce_order_delivery_options' => woocommerce_order_delivery_options(),
				// @deprecated Use feature flags going forward.
				'plugin_woocommerce_order_delivery_active' => is_plugin_active( 'woocommerce-order-delivery/woocommerce-order-delivery.php' ),
				// @deprecated Use feature flags going forward.
				'plugin_routeapp_active'                   => is_plugin_active( 'routeapp/routeapp.php' ),
				// @deprecated Use feature flags going forward.
				'plugin_woo_thank_you_page_nextmove_lite_active' => is_plugin_active( 'woo-thank-you-page-nextmove-lite/thank-you-page-for-woocommerce-nextmove-lite.php' ),
				// @deprecated Use feature flags going forward.
				'hide_peachpay_upsell'                     => peachpay_get_settings_option( 'peachpay_related_products_options', 'hide_woocommerce_products_upsell' ),
			)
		)
	);

}

/**
 * Returns the product type of a wc product.
 */
function peachpay_product_type() {
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product();
		if ( $product ) {
			return $product->get_type();
		}
	}

	return null;
}

/**
 * Gathers location information for peachpay so peachpay can show only the countries/state/provinces that the store supports or defaults to.
 * https://woocommerce.github.io/code-reference/classes/WC-Countries.html#method_get_allowed_countries
 */
function peachpay_location_details() {
	$countries         = WC()->countries;
	$store_country     = $countries->get_base_country();
	$customer_location = wc_get_customer_default_location();
	$locale            = $countries->get_country_locale();
	return array(
		'store_country'                      => $store_country,
		'customer_default_country'           => $customer_location ? $customer_location['country'] : $store_country, // Default to store company if we do not know the customer location.
		'customer_default_state_or_province' => $customer_location ? $customer_location['state'] : '',
		'allowed_countries'                  => $countries->get_allowed_countries(),
		'allowed_states_or_provinces'        => $countries->get_allowed_country_states(),
		'country_locale_data'                => $locale,
	);
}

/**
 * Gets useful information about the merchant customer login so peachpay can adapt where needed.
 */
function peachpay_get_merchant_customer_account() {
	return array(
		'logged_in'                     => is_user_logged_in(),
		'email'                         => is_user_logged_in() ? wp_get_current_user()->user_email : '',
		'checkout_registration_enabled' => 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
		'checkout_login_enabled'        => 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ),
		'auto_generate_username'        => 'yes' === get_option( 'woocommerce_registration_generate_username' ),
		'auto_generate_password'        => 'yes' === get_option( 'woocommerce_registration_generate_password' ),
		'allow_guest_checkout'          => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ),
	);
}

/**
 * Puts together settings for a WC gateway we're passing through.
 *
 * @param WC_Gateway $gateway Gateway.
 * @return array
 */
function peachpay_get_passthrough_gateway_settings( $gateway ) {
	if ( ! $gateway ) {
		return array(
			'enabled' => false,
			'version' => 1,
		);
	}

	return array(
		'enabled'  => 'yes' === $gateway->enabled,
		'version'  => 1,
		'metadata' => array(
			'title'                       => $gateway->get_option( 'title', '' ),
			'description'                 => $gateway->get_option( 'description', '' ),
			'instructions'                => $gateway->get_option( 'instructions', '' ),
			'enabled_for_virtual'         => 'yes' === $gateway->get_option( 'enable_for_virtual', 'yes' ),
			'enable_for_shipping_methods' => $gateway->get_option( 'enable_for_methods', array() ),
		),
	);
}

/**
 * Creates a record of what features are enabled and what api version they are so the modal can easily handle different plugins.
 * "version": should only be incremented if a change is breaking. Starts at 1 because the modal uses 0 for backwards compatibility
 * versions before plugins that supply a feature support record.
 * "meta_data": should only be static information. It is also optional.
 */
function peachpay_feature_support_record() {

	$gateways = WC()->payment_gateways()->payment_gateways();

	$cod_gateway    = isset( $gateways['cod'] ) ? $gateways['cod'] : null;
	$cheque_gateway = isset( $gateways['cheque'] ) ? $gateways['cheque'] : null;
	$bacs_gateway   = isset( $gateways['bacs'] ) ? $gateways['bacs'] : null;

	$base_features = array(
		'cod_payment_method'       => peachpay_get_passthrough_gateway_settings( $cod_gateway ),
		'cheque_payment_method'    => peachpay_get_passthrough_gateway_settings( $cheque_gateway ),
		'bacs_payment_method'      => peachpay_get_passthrough_gateway_settings( $bacs_gateway ),
		'translated_modal_terms'   => array(
			'enabled'  => true,
			'version'  => 2,
			'metadata' => array(
				'selected_language' => peachpay_get_translated_modal_terms( peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language', 'en-US' ) ),
				'all_languages'     => peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language', 'en-US' ) === 'detect-from-page' ? peachpay_get_translated_modal_terms_all_languages() : null,
			),
		),
		'coupon_input'             => array(
			'enabled' => wc_coupons_enabled(),
			'version' => 2,
		),
		'order_notes_input'        => array(
			'enabled' => peachpay_get_settings_option( 'peachpay_express_checkout_window', 'enable_order_notes' ),
			'version' => 1,
		),
		'display_quantity_changer' => array(
			'enabled' => (bool) peachpay_get_settings_option( 'peachpay_express_checkout_window', 'enable_quantity_changer', false ),
			'version' => 1,
		),
		'display_product_images'   => array(
			'enabled' => (bool) peachpay_get_settings_option( 'peachpay_express_checkout_window', 'display_product_images', false ),
			'version' => 1,
		),
		'store_support_message'    => array(
			'enabled'  => (bool) peachpay_get_settings_option( 'peachpay_express_checkout_window', 'enable_store_support_message', false ),
			'version'  => 1,
			'metadata' => array(
				'text' => peachpay_get_settings_option( 'peachpay_express_checkout_window', 'support_message', '' ),
				'type' => peachpay_get_settings_option( 'peachpay_express_checkout_window', 'support_message_type', 'inline' ),
			),
		),
		'merchant_logo'            => array(
			'enabled'  => (bool) peachpay_get_merchant_logo_id(),
			'metadata' => array(
				'logo_src' => peachpay_get_merchant_logo_src(),
			),
		),
		'use_wc_country_locale'    => array(
			'enabled' => (bool) peachpay_get_settings_option( 'peachpay_express_checkout_window', 'use_wc_country_locale', false ),
			'version' => 1,
		),
		'address_autocomplete'     => array(
			'enabled' => (bool) peachpay_get_settings_option( 'peachpay_express_checkout_window', 'address_autocomplete', false ),
			'version' => 1,
		),
		'button_shadow'            => array(
			'enabled' => (bool) peachpay_get_settings_option( 'peachpay_express_checkout_button', 'button_shadow_enabled' ),
			'version' => 1,
		),
	);

	return (array) apply_filters( 'peachpay_register_feature', $base_features );
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
			'version'   => PEACHPAY_VERSION,
			'test_mode' => peachpay_is_test_mode(),
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
 * Returns the terms and condition page of the merchant's store
 */
function peachpay_wc_terms_condition() {
	if ( ! function_exists( 'wc_terms_and_conditions_page_id' ) ) {
		return '';
	}

	$id   = wc_terms_and_conditions_page_id();
	$page = $id ? get_permalink( $id ) : null;

	return $page;
}

/**
 * Returns custom button placements for a store.
 */
function peachpay_get_custom_button_placement() {
	return array(
		'product_page'  => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_target_product_page', null ),
		'cart_page'     => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_target_cart_page', null ),
		'checkout_page' => peachpay_get_settings_option( 'peachpay_express_checkout_advanced', 'custom_target_checkout_page', null ),
	);
}
