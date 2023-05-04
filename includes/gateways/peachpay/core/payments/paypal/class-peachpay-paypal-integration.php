<?php
/**
 * PeachPay PayPal payment integration.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_PayPal_Integration {
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
		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/hooks.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/functions.php';

		if ( is_admin() ) {
			add_action(
				'peachpay_admin_add_payment_setting_section',
				function( $current ) {
					$class = 'pp-header pp-sub-nav-paypal';
					if ( 'pp-sub-nav-paypal' !== $current ) {
						$class .= ' hide';
					}

					add_settings_field(
						'peachpay_paypal_setting',
						null,
						function() {
							require PEACHPAY_ABSPATH . '/core/payments/paypal/admin/views/html-paypal-payment-page.php';
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

		include_once PEACHPAY_ABSPATH . '/core/payments/paypal/admin/tabs/class-peachpay-paypal-advanced.php';

		if ( is_admin() ) {
			PeachPay_Admin_Section::Create(
				'paypal',
				array(
					new PeachPay_PayPal_Advanced(),
				),
				array(
					array(
						'name' => __( 'Payments', 'peachpay-for-woocommerce' ),
						'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '#paypal', false ),
					),
				)
			);
		}
	}

	/**
	 * .
	 */
	protected function woocommerce_init() {

		require_once PEACHPAY_ABSPATH . 'core/payments/paypal/utils/class-peachpay-paypal.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/paypal/utils/class-peachpay-paypal-order-data.php';

		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/abstract/class-peachpay-paypal-payment-gateway.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/gateways/class-peachpay-paypal-wallet-gateway.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/gateways/class-peachpay-paypal-venmo-gateway.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/gateways/class-peachpay-paypal-paylater-gateway.php';
		require_once PEACHPAY_ABSPATH . '/core/payments/paypal/gateways/class-peachpay-paypal-credit-gateway.php';

		$this->payment_gateways[] = 'PeachPay_PayPal_Wallet_Gateway';
		$this->payment_gateways[] = 'PeachPay_PayPal_Venmo_Gateway';
		$this->payment_gateways[] = 'PeachPay_PayPal_PayLater_Gateway';
		$this->payment_gateways[] = 'PeachPay_PayPal_Credit_Gateway';
	}

	/**
	 * Gets the PeachPay paypal test or live mode status.
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
	 * Used to detect if a gateway is a PeachPay Square gateway.
	 *
	 * @param string $id Payment gateway id.
	 */
	public static function is_payment_gateway( $id ) {
		return peachpay_starts_with( $id, 'peachpay_paypal_' );
	}

	/**
	 * Determines whether PayPal is connected/returns connect data.
	 */
	public static function connected() {
		return get_option( 'peachpay_connected_paypal_account', 0 );
	}

	/**
	 * Determines whether PayPal is connected/returns connect data.
	 */
	public static function config() {
		return get_option( 'peachpay_connected_paypal_config', 0 );
	}

	/**
	 * Gets PayPal client id.
	 */
	public static function client_id() {
		$account = self::config();

		if ( ! $account ) {
			return '';
		}

		return $account['client_id'];
	}

	/**
	 * Gets PayPal merchant id.
	 */
	public static function merchant_id() {
		$account = self::connected();

		if ( ! $account ) {
			return '';
		}

		return $account['merchant_id'];
	}

	/**
	 * Gets PayPal merchant id.
	 */
	public static function partner_attribution_id() {
		$account = self::config();

		if ( ! $account ) {
			return '';
		}

		return $account['partner_attribution_id'];
	}
}

PeachPay_PayPal_Integration::instance();
