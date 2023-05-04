<?php
/**
 * PeachPay Poynt order util class.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Utility class to handle saving/retrieving Poynt transactions order data
 */
class PeachPay_Poynt_Order_Data extends PeachPay_Order_Data {

	/**
	 * Adds metadata about an Poynt transaction.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $transaction_details Details about an Poynt transaction.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_transaction( $order, $transaction_details ) {
		return self::set_order_metadata( $order, '_pp_poynt_transaction_details', $transaction_details );
	}


	/**
	 * Gets metadata about Poynt transaction for an order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_transaction( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_poynt_transaction_details', $key );
	}

	/**
	 * Adds metadata about an Poynt refund.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $refund_details Details about an Poynt refund transaction.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function push_refund( $order, $refund_details ) {
		$existing_refunds = self::get_order_metadata( $order, '_pp_poynt_refund_details', 'data' );
		$total_refunded   = self::get_order_metadata( $order, '_pp_poynt_refund_details', 'total' );

		if ( ! $existing_refunds || ! $total_refunded ) {
			$existing_refunds = array();
			$total_refunded   = 0;
		}

		array_push( $existing_refunds, $refund_details );
		$total_refunded += $refund_details['amounts']['transactionAmount'];

		return self::set_order_metadata(
			$order,
			'_pp_poynt_refund_details',
			array(
				'data'  => $existing_refunds,
				'total' => $total_refunded,
			)
		);
	}

	/**
	 * Gets metadata about Poynt refund for an order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_refund( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_poynt_refund_details', $key );
	}

	/**
	 * Gets whether a Poynt transaction has been refunded partially or completely.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $refund_details Details about an Poynt refund transaction.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function refund_exists( $order, $refund_details ) {
		$existing_refunds = self::get_order_metadata( $order, '_pp_poynt_refund_details', 'data' );

		if ( ! is_array( $existing_refunds ) ) {
			$existing_refunds = array();
		}

		return 0 < count(
			array_filter(
				$existing_refunds,
				function( $refund ) use ( $refund_details ) {
					return $refund['id'] === $refund_details['id'];
				}
			)
		);
	}

	/**
	 * Adds metadata about an Poynt token.
	 *
	 * @param WC_Order $order         A order.
	 * @param array    $token_details Details about an Poynt token.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_token( $order, $token_details ) {
		return self::set_order_metadata( $order, '_pp_poynt_token_details', $token_details );
	}

	/**
	 * Gets metadata about Poynt token for an order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key   Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_token( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_poynt_token_details', $key );
	}
}
