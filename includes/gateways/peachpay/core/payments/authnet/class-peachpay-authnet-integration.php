<?php
/**
 * PeachPay Authorize.net Integration Class.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * PeachPay Authorize.net Integration class.
 */
final class PeachPay_Authnet_Integration {

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
	 * Called upon the integration being constructed and should_load returns true.
	 */
	private function includes() {
		require_once PEACHPAY_ABSPATH . 'core/payments/class-peachpay-payment.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/authnet/hooks.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/authnet/functions.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/authnet/routes/authnet-webhook.php';

		if ( is_admin() ) {
			add_action(
				'peachpay_admin_add_payment_setting_section',
				function( $current ) {
					$class = 'pp-header pp-sub-nav-authnet';
					if ( 'pp-sub-nav-authnet' !== $current ) {
						$class .= ' hide';
					}

					add_settings_field(
						'peachpay_authnet_setting',
						null,
						function() {
							require PEACHPAY_ABSPATH . '/core/payments/authnet/admin/views/html-authnet-payment-page.php';
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
	 * Called on the WordPress plugins loaded action.
	 */
	private function plugins_loaded() {
		include_once PEACHPAY_ABSPATH . '/core/payments/authnet/admin/tabs/class-peachpay-authnet-advanced.php';

		if ( is_admin() ) {
			PeachPay_Admin_Section::Create(
				'authnet',
				array(
					new PeachPay_Authnet_Advanced(),
				),
				array(
					array(
						'name' => __( 'Payments', 'peachpay-for-woocommerce' ),
						'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '#authnet', false ),
					),
				)
			);
		}
	}

	/**
	 * Called on the woocommerce_init action.
	 */
	private function woocommerce_init() {
		require_once PEACHPAY_ABSPATH . '/core/payments/authnet/abstract/class-peachpay-authnet-payment-gateway.php';

		require_once PEACHPAY_ABSPATH . '/core/payments/authnet/utils/class-peachpay-authnet.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/authnet/utils/class-peachpay-authnet-order-data.php';

		require_once PEACHPAY_ABSPATH . '/core/payments/authnet/gateways/class-peachpay-authnet-card-gateway.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/authnet/gateways/class-peachpay-authnet-echeck-gateway.php';

		$this->payment_gateways[] = 'PeachPay_Authnet_Card_Gateway';
		$this->payment_gateways[] = 'PeachPay_Authnet_ECheck_Gateway';
	}

	/**
	 * Determines whether Authorize.net is connected and returns connected data details.
	 */
	public static function connected() {
		return get_option( 'peachpay_connected_authnet_account', 0 );
	}

	/**
	 * Gets Authorize.net config.
	 */
	public static function config() {
		return get_option( 'peachpay_connected_authnet_config', 0 );
	}

	/**
	 * Gets Authorize.net public client key for Accept.js
	 */
	public static function public_client_key() {
		$account = self::connected();

		if ( ! $account ) {
			return '';
		}

		return $account['public_client_key'];
	}

	/**
	 * Gets Authorize.net login id for Accept.js
	 */
	public static function login_id() {
		$account = self::connected();

		if ( ! $account ) {
			return '';
		}

		return $account['login_id'];
	}


	/**
	 * Gets Authorize.net solution id.
	 */
	public static function solution_id() {
		$config = self::config();

		if ( ! $config ) {
			return '';
		}

		return $config['solution_id'];
	}


	/**
	 * Gets Authorize.net supported currencies.
	 */
	public static function supported_currencies() {
		$account = self::connected();

		if ( ! $account || ! isset( $account['currencies'] ) || ! is_array( $account['currencies'] ) ) {
			return array();
		}

		return $account['currencies'];
	}

	/**
	 * Gets the Authorize.net Merchant connect URL.
	 */
	public static function connect_url() {
		if ( ! isset( self::config()['connect_url'] ) ) {
			return '';
		}

		// PHPCS:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$state = base64_encode(
			wp_json_encode(
				array(
					'return_url'  => admin_url( 'admin.php?page=peachpay&tab=payment#authnet' ),
					'merchant_id' => peachpay_plugin_merchant_id(),
				)
			)
		);

		return self::config()['connect_url'] . "&state=$state";
	}

	/**
	 * Gets the Authorize.net Merchant signup URL.
	 */
	public static function signup_url() {
		if ( ! isset( self::config()['signup_url'] ) ) {
			return '';
		}

		// PHPCS:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$state = base64_encode(
			wp_json_encode(
				array(
					'return_url'  => admin_url( 'admin.php?page=peachpay&tab=payment#authnet' ),
					'merchant_id' => peachpay_plugin_merchant_id(),
				)
			)
		);

		return self::config()['signup_url'] . "&state=$state";
	}

	/**
	 * Gets the PeachPay Authorize.net test or live mode status
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
	 * Used to detect if a gateway is a PeachPay Authorize.net gateway.
	 *
	 * @param string $gateway_id Payment gateway id.
	 */
	public static function is_payment_gateway( $gateway_id ) {
		return peachpay_starts_with( $gateway_id, 'peachpay_authnet_' );
	}

	/**
	 * Authorize.net rest api endpoints.
	 */
	private function rest_api_init() {
		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/authnet/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => 'peachpay_rest_api_authnet_webhook',
				'permission_callback' => '__return_true',
			)
		);
	}
}
PeachPay_Authnet_Integration::instance();
