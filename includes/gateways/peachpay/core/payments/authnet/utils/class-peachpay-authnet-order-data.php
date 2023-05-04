<?php
/**
 * PeachPay Authorize.net order util class.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Utility class to handle checking authnet transactions
 */
class PeachPay_Authnet_Order_Data extends PeachPay_Order_Data {

	/**
	 * Adds metadata about an authnet transaction.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $transaction_details Details about an authnet transaction.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_transaction_details( $order, $transaction_details ) {
		return self::set_order_metadata( $order, '_pp_authnet_transaction_details', $transaction_details );
	}


	/**
	 * Gets metadata about Authnet transaction for an order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_transaction_details( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_authnet_transaction_details', $key );
	}

}
