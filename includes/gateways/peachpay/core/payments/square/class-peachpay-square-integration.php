<?php
/**
 * PeachPay Square payment integration.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_Square_Integration {
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
		require_once PEACHPAY_ABSPATH . 'core/payments/square/hooks.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/functions.php';

		if ( is_admin() ) {
			require_once PEACHPAY_ABSPATH . 'core/payments/square/admin/class-peachpay-admin-square-integration.php';
		}
	}

	/**
	 * Runs code after all plugins are loaded. Before WC init.
	 */
	private function plugins_loaded() {
		// https://github.com/woocommerce/woocommerce/wiki/Payment-Token-API
		require_once PEACHPAY_ABSPATH . 'core/payments/square/tokens/class-wc-payment-token-peachpay-square-card.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/utils/class-peachpay-square.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/utils/class-peachpay-square-order-data.php';
	}

	/**
	 * .
	 */
	protected function woocommerce_init() {
		require_once PEACHPAY_ABSPATH . 'core/payments/square/abstract/class-peachpay-square-payment-gateway.php';

		require_once PEACHPAY_ABSPATH . 'core/payments/square/gateways/class-peachpay-square-card-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/gateways/class-peachpay-square-applepay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/gateways/class-peachpay-square-googlepay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/gateways/class-peachpay-square-ach-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/gateways/class-peachpay-square-afterpay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/square/gateways/class-peachpay-square-cashapp-gateway.php';

		$this->payment_gateways[] = 'PeachPay_Square_Card_Gateway';
		$this->payment_gateways[] = 'PeachPay_Square_ApplePay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Square_GooglePay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Square_ACH_Gateway';
		$this->payment_gateways[] = 'PeachPay_Square_Afterpay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Square_Cashapp_Gateway';
	}

	/**
	 * Used to detect if a gateway is a PeachPay Square gateway.
	 *
	 * @param string $id Payment gateway id.
	 */
	public static function is_payment_gateway( $id ) {
		return peachpay_starts_with( $id, 'peachpay_square_' );
	}

	/**
	 * Gets the PeachPay square test or live mode status.
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

}
PeachPay_Square_Integration::instance();
