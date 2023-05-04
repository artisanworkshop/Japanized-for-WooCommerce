<?php
/**
 * Peachpay Admin
 *
 * @package Peachpay\Admin
 */

defined( 'ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-singleton.php';

/**
 * .
 */
class PeachPay_Admin {

	use PeachPay_Singleton;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->hooks();
		$this->includes();
	}

	/**
	 * Init any hooks we need within admin here.
	 */
	private function hooks() {
		add_filter( 'plugin_action_links_' . PeachPay::get_plugin_name(), array( $this, 'add_plugin_list_links' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'admin_menu', array( $this, 'add_admin_menus' ) );
		add_action( 'wp_ajax_pp-deactivation-feedback', 'peachpay_handle_deactivation_feedback' );
	}

	/**
	 * Include any php files we need within the admin area here.
	 */
	private function includes() {
		require_once PEACHPAY_ABSPATH . 'core/admin/settings.php';
	}

	/**
	 * Enqueue JS and CSS that we need on every PeachPay admin page. If it is gateway specific put it in the gateway. If it is related to settings section put it in that section.
	 *
	 * @param string $page The current page in the WP admin dashboard.
	 */
	public function enqueue_scripts( $page ) {
		PeachPay::enqueue_style( 'peachpay-admin-core', 'public/dist/admin.bundle.css' );
		PeachPay::enqueue_script( 'peachpay-admin-core', 'public/dist/admin.bundle.js' );
		PeachPay::enqueue_script_data(
			'peachpay-admin-core',
			'peachpay_admin',
			array(
				'merchant_id'  => peachpay_plugin_merchant_id(),
				'domain'       => wp_parse_url( get_site_url(), PHP_URL_HOST ),

				'asset_url'    => PeachPay::get_asset_url( '' ),
				'admin_url'    => admin_url( 'admin.php' ),
				'admin_ajax'   => admin_url( 'admin-ajax.php' ),

				'nonces'       => array(
					'gateway_toggle'           => wp_create_nonce( 'woocommerce-toggle-payment-gateway-enabled' ),
					'applepay_domain_register' => wp_create_nonce( 'peachpay-applepay-domain-register' ),

					'stripe_capture_payment'   => wp_create_nonce( 'peachpay-stripe-capture-payment' ),
					'stripe_void_payment'      => wp_create_nonce( 'peachpay-stripe-void-payment' ),

					'poynt_capture_payment'    => wp_create_nonce( 'peachpay-poynt-capture-payment' ),
					'poynt_void_payment'       => wp_create_nonce( 'peachpay-poynt-void-payment' ),
					'poynt_register_webhooks'  => wp_create_nonce( 'peachpay-poynt-register-webhooks' ),

					'authnet_capture_payment'  => wp_create_nonce( 'peachpay-authnet-capture-payment' ),
					'authnet_void_payment'     => wp_create_nonce( 'peachpay-authnet-void-payment' ),

					'deactivation_feedback'    => wp_create_nonce( 'peachpay-deactivation-feedback' ),
				),

				'translations' => array(
					'country_description_block'  => __( 'When the billing country matches one of these values, the payment method will not be shown on the checkout page.', 'peachpay-for-woocommerce' ),
					'country_description_allow'  => __( 'When the billing country matches one of these values, the payment method will be shown on the checkout page.', 'peachpay-for-woocommerce' ),
					'currency_description_block' => __( 'When the currency matches one of these values, the payment method will not be shown on the checkout page.', 'peachpay-for-woocommerce' ),
					'currency_description_allow' => __( 'When the currency matches one of these values, the payment method will be shown on the checkout page.', 'peachpay-for-woocommerce' ),
					'select_all'                 => __( 'Select all', 'peachpay-for-woocommerce' ),
					'select_none'                => __( 'Select none', 'peachpay-for-woocommerce' ),
				),
			)
		);

		if ( $this->is_peachpay_admin_page( $page ) ) {
			PeachPay::enqueue_script( 'peachpay-heap-analytics', 'core/admin/assets/js/heap-analytics.js' );
			PeachPay::enqueue_script_data(
				'peachpay-heap-analytics',
				'peachpay_heap',
				array(
					'environment_id' => peachpay_is_local_development_site() || peachpay_is_staging_site() ? '248465022' : '3719363403',
				)
			);
		}
	}

	/**
	 * Tells us if we are on a WP admin page related to PeachPay.
	 *
	 * @param string $page The current page in the WP admin dashboard.
	 */
	private function is_peachpay_admin_page( $page ) {
		return 'toplevel_page_peachpay' === $page
		|| 'peachpay_page_peachpay_analytics' === $page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		|| ( 'woocommerce_page_wc-settings' === $page && isset( $_GET['section'] ) && str_contains( sanitize_text_field( wp_unslash( $_GET['section'] ) ), 'peachpay' ) );
	}

	/**
	 * Adds links to the WordPress plugin listing.
	 *
	 * @param array $links An array of links to include on the WordPress plugin list.
	 */
	public function add_plugin_list_links( $links ) {
		array_unshift( $links, '<a href="https://help.peachpay.app/" target="_blank" rel="noopener noreferrer">' . __( 'Docs', 'peachpay-for-woocommerce' ) . '</a>' );
		array_unshift( $links, '<a href="admin.php?page=peachpay">' . __( 'Settings', 'peachpay-for-woocommerce' ) . '</a>' );
		return $links;
	}

	/**
	 * Adds the plugin menus to the plugin listing
	 */
	public function add_admin_menus() {
		add_submenu_page(
			'woocommerce',
			__( 'PeachPay', 'peachpay-for-woocommerce' ),
			__( 'PeachPay', 'peachpay-for-woocommerce' ),
			'manage_woocommerce',
			'peachpay',
			array( __CLASS__, 'do_admin_page' )
		);
		add_menu_page(
			__( 'PeachPay', 'peachpay-for-woocommerce' ),
			__( 'PeachPay', 'peachpay-for-woocommerce' ),
			'manage_options',
			'peachpay',
			array( __CLASS__, 'do_admin_page' ),
			'dashicons-cart',
			58
		);
		add_submenu_page(
			'peachpay',
			__( 'Dashboard', 'peachpay-for-woocommerce' ),
			__( 'Dashboard', 'peachpay-for-woocommerce' ),
			'manage_woocommerce',
			'peachpay&tab=home',
			array( __CLASS__, 'do_admin_page' )
		);
		add_submenu_page(
			'peachpay',
			__( 'Settings', 'peachpay-for-woocommerce' ),
			__( 'Settings', 'peachpay-for-woocommerce' ),
			'manage_woocommerce',
			'peachpay&tab=payment',
			array( __CLASS__, 'do_admin_page' )
		);
		add_submenu_page(
			'peachpay',
			__( 'Analytics', 'peachpay-for-woocommerce' ),
			__( 'Analytics', 'peachpay-for-woocommerce' ),
			'manage_options',
			'peachpay&tab=payment_methods&section=analytics',
			array( __CLASS__, 'do_admin_page' )
		);
	}

	/**
	 * Makes an admin page render.
	 */
	public static function do_admin_page() {
        // PHPCS:disable WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		// PHPCS:enable

		// Hack to get legacy pages to work. This should be removed once new admin API is in use.
		if (
		( 'field' === $tab && 'billing' === $section ) ||
		( 'field' === $tab && 'shipping' === $section ) ||
		( 'field' === $tab && 'additional' === $section ) ||
		( 'express_checkout' === $tab && 'branding' === $section ) ||
		( 'express_checkout' === $tab && 'window' === $section ) ||
		( 'express_checkout' === $tab && 'product_recommendations' === $section ) ||
		( 'express_checkout' === $tab && 'button' === $section ) ||
		( 'express_checkout' === $tab && 'advanced' === $section ) ) {
			peachpay_options_page_html();
			return;
		}

        // PHPCS:ignore
		if ( $section) {
			// PHPCS:ignore
			$section = sanitize_text_field( $_GET['section'] );

			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				do_action( 'peachpay_update_options_admin_settings_' . $section );
			}

			do_action( 'peachpay_admin_section_' . $section );

		} else {
			peachpay_options_page_html();
		}
	}

	/**
	 * Gets an admin settings url for links.
	 *
	 * @param string  $page     The page GET parameter.
	 * @param string  $tab      The tab GET parameter.
	 * @param string  $section  The section GET parameter.
	 * @param string  $hash     The page hash parameter.
	 * @param boolean $echo     Whether to echo the output or not.
	 */
	public static function admin_settings_url( $page = 'peachpay', $tab = '', $section = '', $hash = '', $echo = true ) {
		$url = '';

		if ( is_string( $tab ) && is_string( $section ) && strlen( $tab ) > 0 && strlen( $section ) > 0 ) {
			$url = admin_url( 'admin.php?page=' . $page . '&tab=' . $tab . '&section=' . $section . $hash );
		} elseif ( is_string( $tab ) && strlen( $tab ) > 0 ) {
			$url = admin_url( 'admin.php?page=' . $page . '&tab=' . $tab . $hash );
		} else {
			$url = admin_url( 'admin.php?page=' . $page . $hash );
		}

		if ( $echo ) {
			// PHPCS:ignore
			echo $url;
		}

		return $url;
	}
}

PeachPay_Admin::instance();
