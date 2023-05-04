<?php
/**
 * PeachPay Payments extension.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-payment-integration.php';

/**
 * .
 */
final class PeachPay_Payments_Integration {
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
	protected function includes() {
		require_once PEACHPAY_ABSPATH . 'core/payments/class-peachpay-payment.php';

		if ( is_admin() ) {
			require_once PEACHPAY_ABSPATH . 'core/payments/peachpay/admin/class-peachpay-payments-admin-integration.php';
		}
	}

	/**
	 * .
	 */
	protected function woocommerce_init() {
		require_once PEACHPAY_ABSPATH . 'core/payments/peachpay/gateways/class-peachpay-purchase-order-gateway.php';

		$this->payment_gateways[] = 'PeachPay_Purchase_Order_Gateway';
	}

	/**
	 * Used to detect if a gateway is a PeachPay gateway.
	 *
	 * @param string $id Payment gateway id.
	 */
	public static function is_payment_gateway( $id ) {
		if ( 'peachpay_purchase_order' === $id ) {
			return true;
		}

		return false;
	}


}
PeachPay_Payments_Integration::instance();
