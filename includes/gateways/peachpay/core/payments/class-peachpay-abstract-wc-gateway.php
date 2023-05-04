<?php
/**
 * Peachpay WC Gateway setup.
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Missing
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Base class from which specific payment processors gateways can inherit from.
 *
 * @deprecated
 */
abstract class PeachPay_Abstract_WC_Gateway extends WC_Payment_Gateway {
	/**
	 * Not used, but there in case WooCommerce code tries to call it.
	 * See https://docs.woocommerce.com/document/payment-gateway-api/
	 */
	public function init_form_fields() {}

	/**
	 * Same as above.
	 */
	public function payment_scripts() {}

	/**
	 * Same as above.
	 */
	public function webhook() {}

	/**
	 * We don't actually process the payment here, but this is a critical
	 * part of our checkout that returns information about the order that
	 * has just been placed so that we can continue working with it on the
	 * client side.
	 *
	 * @param int $order_id The order that was just placed.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		return $this->prepare_payment_result( $order );
	}

	/**
	 * Collects the information required about the order for use on the
	 * frontend. The main piece was originally the redirect url, but it has
	 * since expanded to include all data about the order for which we just
	 * confirmed that payment.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 * @return array The associative array of order data that is turned
	 * into JSON when it's returned to the frontend.
	 */
	protected function prepare_payment_result( WC_Order $order ) {
		$result = array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
			'number'   => $order->get_order_number(),
			'orderID'  => $order->get_id(),
			'details'  => $order->get_data(),
		);

		// If we don't do the below, the end result will be something like
		// "line_items": {"972": {}}, which is not useful because we can't
		// see the line item details. This is because json_encode which runs
		// behind the scenes ignores protected data. We can forcefully
		// unprotect these.
		$result['details']['line_items']     = $this->get_protected( $order->get_items() );
		$result['details']['shipping_lines'] = $this->get_protected( $order->get_shipping_methods() );
		$result['details']['fee_lines']      = $this->get_protected( $order->get_fees() );
		$result['details']['coupon_lines']   = $this->get_protected( $order->get_coupons() );

		// This is not usually part of the WooCommerce order object, but
		// we want to avoid doing math on money whenever possible and so
		// would rather set it here.
		$result['details']['fee_total'] = number_format( $order->get_total_fees() ?? '0', 2 );

		return $result;
	}

	/**
	 * Helper "hack" to get expose protected array items.
	 *
	 * @param array $protected_items The items to expose.
	 */
	private function get_protected( $protected_items ) {
		return array_map(
			function ( WC_Data $item ) {
				return $item->get_data();
			},
			$protected_items
		);
	}

}
