<?php
/**
 * PeachPay Poynt Integration Class.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * PeachPay Poynt Integration class.
 */
final class PeachPay_Poynt_Integration {
	use PeachPay_Payment_Integration;

	/**
	 * At some point in time we will allow the ability to disable and enable
	 * integrations completely to ensure they do not have performance impacts
	 * when not used. For now we always return true.
	 */
	public static function should_load() {
		return true;
	}

	/**
	 * .
	 */
	private function includes() {
		require_once PEACHPAY_ABSPATH . 'core/payments/class-peachpay-payment.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/hooks.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/functions.php';

		if ( is_admin() ) {
			add_action(
				'peachpay_admin_add_payment_setting_section',
				function( $current ) {
					$class = 'pp-header pp-sub-nav-poynt';
					if ( 'pp-sub-nav-poynt' !== $current ) {
						$class .= ' hide';
					}

					add_settings_field(
						'peachpay_poynt_setting',
						null,
						function() {
							require PEACHPAY_ABSPATH . '/core/payments/poynt/admin/views/html-poynt-payment-page.php';
						},
						'peachpay',
						'peachpay_payment_settings_section',
						array( 'class' => $class )
					);
				}
			);
		}
	}
	/**
	 * .
	 */
	private function woocommerce_init() {
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/tokens/class-wc-payment-token-peachpay-poynt-card.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/utils/class-peachpay-poynt.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/utils/class-peachpay-poynt-order-data.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/abstract/class-peachpay-poynt-payment-gateway.php';

		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/gateways/class-peachpay-poynt-card-gateway.php';

		$this->payment_gateways[] = 'PeachPay_Poynt_Card_Gateway';
	}

	/**
	 * Determines whether Poynt is connected and returns connected data details.
	 */
	public static function connected() {
		return get_option( 'peachpay_connected_poynt_account', 0 );
	}

	/**
	 * Gets Poynt config.
	 */
	public static function config() {
		return get_option( 'peachpay_connected_poynt_config', 0 );
	}

	/**
	 * Gets the Poynt Merchant signup URL.
	 */
	public static function signup_url() {
		if ( ! isset( self::config()['signup_url'] ) ) {
			return '';
		}

		// PHPCS:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$state = base64_encode(
			wp_json_encode(
				array(
					'return_url'   => admin_url( 'admin.php?page=peachpay&tab=payment#poynt' ),
					'merchant_id'  => peachpay_plugin_merchant_id(),
					'merchant_url' => get_site_url(),
				)
			)
		);

		return self::config()['signup_url'] . "&context=$state";
	}

		/**
		 * Gets the Poynt Merchant login URL.
		 */
	public static function login_url() {
		if ( ! isset( self::config()['login_url'] ) ) {
			return '';
		}

		// PHPCS:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$state = base64_encode(
			wp_json_encode(
				array(
					'return_url'   => admin_url( 'admin.php?page=peachpay&tab=payment#poynt' ),
					'merchant_id'  => peachpay_plugin_merchant_id(),
					'merchant_url' => get_site_url(),
				)
			)
		);

		return self::config()['login_url'] . "&context=$state";
	}

	/**
	 * Gets the PeachPay Poynt test or live mode status
	 *
	 * @param boolean|null $mode If the mode should override the global test mode. If not null a truthy value will indicate live mode where a falsy value will indicate test mode.
	 */
	public static function mode( $mode = 'detect' ) {
		if ( 'detect' === $mode ) {
			return ( peachpay_is_test_mode() || peachpay_is_local_development_site() || peachpay_is_staging_site() ) ? 'test' : 'live';
		}

		if ( 'live' === $mode ) {
			return 'live';
		} else {
			return 'test';
		}
	}

	/**
	 * Used to detect if a gateway is a PeachPay Poynt gateway.
	 *
	 * @param string $gateway_id Payment gateway id.
	 */
	public static function is_payment_gateway( $gateway_id ) {
		return peachpay_starts_with( $gateway_id, 'peachpay_poynt_' );
	}

	/**
	 * Called on the WordPress plugins loaded action.
	 */
	private function plugins_loaded() {

		include_once PEACHPAY_ABSPATH . '/core/payments/poynt/admin/tabs/class-peachpay-poynt-advanced.php';

		if ( is_admin() ) {
			PeachPay_Admin_Section::Create(
				'poynt',
				array(
					new PeachPay_Poynt_Advanced(),
				),
				array(
					array(
						'name' => __( 'Payments', 'peachpay-for-woocommerce' ),
						'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '#poynt', false ),
					),
				)
			);
		}
	}

	/**
	 * Gets Poynt business id.
	 */
	public static function business_id() {
		$account = self::connected();

		if ( ! $account || ! isset( $account['business_id'] ) ) {
			return '';
		}

		return $account['business_id'];
	}

	/**
	 * Gets Poynt application id.
	 */
	public static function application_id() {
		$config = self::config();

		if ( ! $config || ! isset( $config['application_id'] ) ) {
			return '';
		}

		return $config['application_id'];
	}


	/**
	 * Gets Poynt webhook status.
	 */
	public static function webhook_status() {
		$account = self::connected();

		if ( ! $account | ! isset( $account['webhook_status'] ) ) {
			return false;
		}

		return $account['webhook_status'] ? true : false;
	}

	/**
	 * Gets the Poynt Collect script src URL.
	 */
	public static function poynt_script_src() {
		return 'https://cdn.poynt.net/collect.js';
	}

	/**
	 * Poynt rest api endpoints.
	 */
	private function rest_api_init() {
		require_once PEACHPAY_ABSPATH . 'core/payments/poynt/routes/poynt-webhook.php';

		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/poynt/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => 'peachpay_rest_api_poynt_webhook',
				'permission_callback' => '__return_true',
			)
		);
	}
}

PeachPay_Poynt_Integration::instance();
