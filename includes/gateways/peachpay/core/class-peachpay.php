<?php
/**
 * Core PeachPay class.
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-singleton.php';

/**
 * .
 */
final class PeachPay {

	use PeachPay_Singleton;

	/**
	 * .
	 */
	private function __construct() {
		if ( ! file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_not_installed_error_notice' ) );
		} elseif ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_not_activated_error_notice' ) );
		} else {
			$this->hooks();
			$this->includes();
		}
	}

	/**
	 * PeachPay hooks.
	 */
	private function hooks() {
		add_filter( 'woocommerce_update_order_review_fragments', 'peachpay_native_checkout_data_fragment' );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Includes all dependencies. Dependencies should be responsible for self initialization.
	 */
	private function includes() {

		// Utilities
		require_once PEACHPAY_ABSPATH . 'core/util/util.php';
		include_once PEACHPAY_ABSPATH . 'core/functions.php';

		require_once PEACHPAY_ABSPATH . 'core/class-peachpay-lifecycle.php';
		require_once PEACHPAY_ABSPATH . 'core/class-peachpay-lifecycle-analytics.php';

		// Extensions / integrations
		require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-extension.php';
		require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-admin-extension.php';
		require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-payment-integration.php';

		include_once PEACHPAY_ABSPATH . '/core/payments/stripe/class-peachpay-stripe-integration.php';
		include_once PEACHPAY_ABSPATH . '/core/payments/square/class-peachpay-square-integration.php';
		include_once PEACHPAY_ABSPATH . '/core/payments/paypal/class-peachpay-paypal-integration.php';
		include_once PEACHPAY_ABSPATH . '/core/payments/poynt/class-peachpay-poynt-integration.php';
		include_once PEACHPAY_ABSPATH . '/core/payments/authnet/class-peachpay-authnet-integration.php';
		include_once PEACHPAY_ABSPATH . '/core/payments/peachpay/class-peachpay-payments-integration.php';

		if ( is_admin() ) {
			include_once PEACHPAY_ABSPATH . 'core/admin/class-peachpay-admin.php';
			include_once PEACHPAY_ABSPATH . 'core/admin/class-peachpay-admin-section.php';

			// Since this does not actually access the value within $_GET and just checks equivalency, ignore.
			// phpcs:ignore
			if ( array_key_exists( 'section', $_GET ) && 'analytics' === $_GET['section'] ) {
				include_once PEACHPAY_ABSPATH . 'core/modules/analytics/class-peachpay-analytics.php';
			}
		}
	}

	/**
	 * Enqueues only public scripts.
	 */
	public function enqueue_scripts() {
		// Native checkout scripts.
		if ( is_checkout() ) {
			self::enqueue_style( 'peachpay-native-checkout', 'public/dist/native-checkout.bundle.css' );
			self::enqueue_script( 'peachpay-native-checkout', 'public/dist/native-checkout.bundle.js' );
			self::enqueue_script_data( 'peachpay-native-checkout', 'peachpay_checkout_data', $this->native_checkout_data() );
		}
	}

	/**
	 * Gets the script data for the PeachPay native checkout experience.
	 */
	public function native_checkout_data() {
		$native_checkout_data = array(
			'merchant'     => array(
				'id'     => peachpay_plugin_merchant_id(),
				'name'   => get_bloginfo( 'name' ),
				'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
			),

			'page'         => array(
				'is_checkout'       => is_checkout(),
				'is_order_received' => is_wc_endpoint_url( 'order-received' ),
				'is_order_pay'      => is_wc_endpoint_url( 'order-pay' ),
			),

			'plugin'       => array(
				'version'         => self::get_plugin_version(),
				'api_url_base'    => peachpay_api_url(),
				'asset_url_base'  => self::get_asset_url( '' ),
				'feature_support' => apply_filters( 'peachpay_register_feature', array() ),
				'mode'            => peachpay_is_test_mode() ? 'test' : 'live',
			),

			// These cart details structure may still change once subscriptions are considered.
			'cart_details' => peachpay_cart_details(),
		);

		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			if ( $order instanceof WC_Order ) {
				$native_checkout_data['order_pay_details'] = peachpay_get_order_pay_details( $order );
			}
		}

		return apply_filters( 'peachpay_native_checkout_data', $native_checkout_data );
	}

	/**
	 * Gets the current version of the plugin.
	 */
	public static function get_plugin_version() {
		return PEACHPAY_VERSION;
	}

	/**
	 * Gets the plugin entry name.
	 * Likely always "peachpay-for-woocommerce/peachpay.php"
	 */
	public static function get_plugin_name() {
		return PEACHPAY_BASENAME;
	}

	/**
	 * Gets the current plugin path.
	 */
	public static function get_plugin_path() {
		return PEACHPAY_ABSPATH;
	}

	/**
	 * Gets a fully qualified URL of an asset file.
	 *
	 * @param string $asset_path The path to a asset file to create a URL for.
	 */
	public static function get_asset_url( $asset_path ) {
		return plugin_dir_url( PEACHPAY_ABSPATH . 'public/.' ) . $asset_path;
	}

	/**
	 * Enqueues a JS script to be loaded.
	 *
	 * @param string  $handle The handle to match the script with.
	 * @param string  $path The path to the script relative to the plugin root.
	 * @param array   $deps Any script dependencies to change the insertion order.
	 * @param boolean $in_footer If the script should be placed in the footer or not.
	 * @param boolean $path_is_url If the path is already a complete URL (Useful for external scripts).
	 */
	public static function enqueue_script( $handle, $path, $deps = array(), $in_footer = false, $path_is_url = false ) {
		$version = PEACHPAY_VERSION;
		if ( ! $path_is_url ) {
			$version = gmdate( 'ymd-Gis', filemtime( self::get_plugin_path() . $path ) );
			$path    = plugin_dir_url( self::get_plugin_path() . '/.' ) . $path;
		}

		wp_register_script(
			$handle,
			$path,
			$deps,
			$version,
			$in_footer
		);

		return wp_enqueue_script( $handle );
	}

	/**
	 * Enqueues a JS object to be loaded.
	 *
	 * @param string $handle The script handle to match the script with.
	 * @param string $object_name The name of the JS object.
	 * @param array  $data The data to set the object equal too.
	 */
	public static function enqueue_script_data( $handle, $object_name, $data ) {
		return wp_localize_script( $handle, $object_name, $data );
	}

	/**
	 * Enqueues a CSS stylesheet to be loaded.
	 *
	 * @param string  $handle The handle to match the style with.
	 * @param string  $path The path to the style relative to the plugin root.
	 * @param array   $deps Any style dependencies to change the insertion order.
	 * @param boolean $in_footer If the style should be placed in the footer or not.
	 */
	public static function enqueue_style( $handle, $path, $deps = array(), $in_footer = false ) {
		wp_register_style(
			$handle,
			plugin_dir_url( self::get_plugin_path() . '/.' ) . $path,
			$deps,
			gmdate( 'ymd-Gis', filemtime( self::get_plugin_path() . $path ) ),
			$in_footer
		);
		wp_enqueue_style( $handle );
	}

	/**
	 * Displays an error notice if woocommerce is not installed.
	 */
	public function woocommerce_not_installed_error_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
					echo sprintf(
						// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: opening and closing link tags, leads to plugins.php in admin.
						esc_html__( 'PeachPay is a WooCommerce extension. Please %3$sinstall and activate WooCommerce%4$s to use PeachPay.', 'peachpay-for-woocommerce' ),
						'<strong>',
						'</strong>',
						'<a href="' . esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'action' => 'install-plugin',
										'plugin' => 'woocommerce',
									),
									admin_url( 'update.php' )
								),
								'install-plugin_woocommerce'
							)
						) . '">',
						'</a>'
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays an error notice if woocommerce is not activated.
	 */
	public function woocommerce_not_activated_error_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
					echo sprintf(
						// translators: 1$-2$: opening and closing <strong> tags, 3$-4$: opening and closing link tags, leads to plugins.php in admin.
						esc_html__( 'PeachPay is a WooCommerce extension. Please %3$sactivate WooCommerce%4$s to use PeachPay.', 'peachpay-for-woocommerce' ),
						'<strong>',
						'</strong>',
						'<a href="' . esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'action' => 'activate',
										'plugin' => 'woocommerce/woocommerce.php',
									),
									admin_url( 'plugins.php' )
								),
								'activate-plugin_woocommerce/woocommerce.php'
							)
						) . '">',
						'</a>'
					);
				?>
			</p>
		</div>
		<?php
	}
}
