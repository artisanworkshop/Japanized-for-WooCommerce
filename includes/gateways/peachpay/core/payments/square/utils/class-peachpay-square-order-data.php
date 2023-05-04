<?php
/**
 * PeachPay Square order util class.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . '/core/abstract/class-peachpay-order-data.php';

/**
 * .
 */
final class PeachPay_Square_Order_Data extends PeachPay_Order_Data {

	/**
	 * Sets order payment details to be reused. Automatically flags order as containing
	 * a square reusable payment method.
	 *
	 * @param WC_Order $order woocommerce order to retrieve data from.
	 * @param string   $source_id reusable payment source ID.
	 * @param string   $customer_id square customer ID the source is attached to.
	 */
	public static function set_reusable_payment_details( $order, $source_id, $customer_id ) {
		$payment_method_details = array(
			'source_id'   => $source_id,
			'customer_id' => $customer_id,
			'reusable'    => true,
		);

		self::set_order_metadata( $order, '_pp_square_payment_method_details', $payment_method_details );
	}

	/**
	 * Gets the _pp_square_payment_method_details order meta.
	 *
	 * @param WC_Order $order woocommerce order to retrieve data from.
	 */
	public static function get_reusable_payment_id( $order ) {
		return self::get_order_metadata( $order, '_pp_square_payment_method_details', 'source_id' );
	}

	/**
	 * Gets the _pp_square_payment_method_details order meta.
	 *
	 * @param WC_Order $order woocommerce order to retrieve data from.
	 */
	public static function get_payment_customer_id( $order ) {
		return self::get_order_metadata( $order, '_pp_square_payment_method_details', 'customer_id' );
	}

	/**
	 * Gets the _pp_square_payment_method_details['reusable'] metadata which tells whether the payment source id
	 * attached to the order has been configured for reuse.
	 *
	 * @param WC_Order $order woocommerce order to retrieve data from.
	 */
	public static function is_payment_reusable( $order ) {
		if ( null === self::get_order_metadata( $order, '_pp_square_payment_method_details', 'reusable' ) ) {
			return false;
		}
		return self::get_order_metadata( $order, '_pp_square_payment_method_details', 'reusable' );
	}

}
