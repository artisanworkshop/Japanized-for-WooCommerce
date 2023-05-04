<?php
/**
 * PeachPay PayPal order util class.
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
final class PeachPay_PayPal_Order_Data extends PeachPay_Order_Data {

	/**
	 * Adds metadata about a PayPal transaction to a order.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $order_transaction_details Details about a PayPal order transaction.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_order_transaction_details( $order, $order_transaction_details ) {
		return self::set_order_metadata( $order, '_pp_paypal_order_transaction_details', $order_transaction_details );
	}

	/**
	 * Gets metadata about a PayPal transaction for an order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed The details requested.
	 */
	public static function get_order_transaction_details( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_paypal_order_transaction_details', $key );
	}

	/**
	 * Gets the captured payments for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return array|null An array of captured payments, or null if no captured payments are found.
	 */
	public static function get_captured_payments( $order ) {
		$captured_payments = array();

		$purchase_units = self::get_order_transaction_details( $order, 'purchase_units' );
		if ( $purchase_units ) {
			foreach ( $purchase_units as $purchase_unit ) {
				if ( ! isset( $purchase_unit['payments'] ) ) {
					continue;
				}
				$payments = $purchase_unit['payments'];
				if ( ! isset( $payments['captures'] ) ) {
					continue;
				}
				$captures = $payments['captures'];
				foreach ( $captures as $capture ) {
					if ( 'COMPLETED' === $capture['status'] || 'PARTIALLY_REFUNDED' === $capture['status'] || 'REFUNDED' === $capture['status'] ) {
						$captured_payments[] = $capture;
					}
				}
			}

			return $captured_payments;
		}
	}

	/**
	 * Gets the refunded payments for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return array|null An array of refunded payments, or null if no refunded payments are found.
	 */
	public static function get_refunded_payments( $order ) {
		$refunded_payments = array();

		$purchase_units = self::get_order_transaction_details( $order, 'purchase_units' );
		if ( $purchase_units ) {
			foreach ( $purchase_units as $purchase_unit ) {
				if ( ! isset( $purchase_unit['payments'] ) ) {
					continue;
				}

				$payments = $purchase_unit['payments'];
				if ( ! isset( $payments['refunds'] ) ) {
					continue;
				}

				$refunds = $payments['refunds'];
				foreach ( $refunds as $refund ) {
					if ( 'COMPLETED' === $refund['status'] ) {
						$refunded_payments[] = $refund;
					}
				}
			}

			return $refunded_payments;
		}
	}

	/**
	 * Gets the sum of the net payments for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return float|null The sum of the net payments, or null if no captured payments are found.
	 */
	public static function get_net_sum( $order ) {
		$captured_payments = self::get_captured_payments( $order );
		$refunded_payments = self::get_refunded_payments( $order );
		if ( ! is_array( $captured_payments ) || ! is_array( $refunded_payments ) ) {
			return null;
		}

		$net_payments_sum = 0;
		foreach ( $captured_payments as $captured_payment ) {
			if ( ! isset( $captured_payment['seller_receivable_breakdown']['net_amount']['value'] ) ) {
				continue;
			}
			$net_amount        = $captured_payment['seller_receivable_breakdown']['net_amount']['value'];
			$net_payments_sum += $net_amount;
		}

		foreach ( $refunded_payments as $refunded_payment ) {
			if ( ! isset( $refunded_payment['seller_payable_breakdown']['net_amount']['value'] ) ) {
				continue;
			}

			$net_amount        = -$refunded_payment['seller_payable_breakdown']['net_amount']['value'];
			$net_payments_sum += $net_amount;
		}

		return $net_payments_sum;
	}

	/**
	 * Gets the sum of the gross payments for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return float|null The sum of the gross payments, or null if no captured payments are found.
	 */
	public static function get_gross_sum( $order ) {
		$captured_payments = self::get_captured_payments( $order );
		if ( ! is_array( $captured_payments ) ) {
			return null;
		}

		$gross_payments_sum = 0;
		foreach ( $captured_payments as $captured_payment ) {
			if ( ! isset( $captured_payment['seller_receivable_breakdown']['gross_amount']['value'] ) ) {
				continue;
			}
			$gross_amount        = $captured_payment['seller_receivable_breakdown']['gross_amount']['value'];
			$gross_payments_sum += $gross_amount;
		}

		return $gross_payments_sum;
	}

	/**
	 * Gets the sum of the PayPal fees for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return float|null The sum of the PayPal fees, or null if no captured payments are found.
	 */
	public static function get_fees_sum( $order ) {
		$captured_payments = self::get_captured_payments( $order );
		$refunded_payments = self::get_refunded_payments( $order );
		if ( ! is_array( $captured_payments ) ) {
			return null;
		}

		$paypal_fees_sum = 0;
		foreach ( $captured_payments as $captured_payment ) {
			if ( ! isset( $captured_payment['seller_receivable_breakdown']['paypal_fee']['value'] ) ) {
				continue;
			}
			$paypal_fee       = $captured_payment['seller_receivable_breakdown']['paypal_fee']['value'];
			$paypal_fees_sum += $paypal_fee;
		}

		foreach ( $refunded_payments as $refunded_payment ) {
			if ( ! isset( $refunded_payment['seller_payable_breakdown']['paypal_fee']['value'] ) ) {
				continue;
			}

			$net_amount       = -$refunded_payment['seller_payable_breakdown']['paypal_fee']['value'];
			$paypal_fees_sum += $net_amount;
		}

		return $paypal_fees_sum;
	}

	/**
	 * Gets the sum of refunded payments for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return float|null The sum of refunded payments, or null if no refunded payments are found.
	 */
	public static function get_refunded_sum( $order ) {
		$refunded_payments = self::get_refunded_payments( $order );
		if ( ! is_array( $refunded_payments ) ) {
			return null;
		}

		$sum_of_refunded_payments = 0.0;
		foreach ( $refunded_payments as $refunded_payment ) {
			$sum_of_refunded_payments += $refunded_payment['amount']['value'];
		}

		return $sum_of_refunded_payments;
	}

	/**
	 * Gets the capture amount currency for an order.
	 *
	 * @param WC_Order $order A order.
	 *
	 * @return string|null The capture amount currency, or null if no captured payments are found.
	 */
	public static function get_capture_amount_currency( $order ) {
		$captured_payments = self::get_captured_payments( $order );
		if ( ! is_array( $captured_payments ) ) {
			return null;
		}

		if ( count( $captured_payments ) <= 0 ) {
			return null;

		}

		// Assuming that all captured payments have the same currency code.
		return $captured_payments[0]['amount']['currency_code'];
	}
}
