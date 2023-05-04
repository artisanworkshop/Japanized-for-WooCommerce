<?php
/**
 * PeachPay Stripe order util class.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-order-data.php';

/**
 * .
 */
final class PeachPay_Stripe_Order_Data extends PeachPay_Order_Data {

	/**
	 * Adds metadata about a stripe payment intent details to a order.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $payment_intent_details Details about a payment intent.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_payment_intent_details( $order, $payment_intent_details ) {
		// Client secret should NOT be stored!.
		unset( $payment_intent_details['client_secret'] );
		return self::set_order_metadata( $order, '_pp_stripe_payment_intent_details', $payment_intent_details );
	}

	/**
	 * Gets metadata about a stripe payment intent for a order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_payment_intent( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_stripe_payment_intent_details', $key );
	}



	/**
	 * Adds metadata about a stripe payment method details to a order.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $payment_method_details Details about a payment intent.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_payment_method_details( $order, $payment_method_details ) {
		return self::set_order_metadata( $order, '_pp_stripe_payment_method_details', $payment_method_details );
	}

	/**
	 * Gets metadata about a stripe payment method for a order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_payment_method( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_stripe_payment_method_details', $key );
	}

	/**
	 * Adds metadata about a stripe charge details to a order.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $charge_details Details about a payment intent.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_charge_details( $order, $charge_details ) {
		return self::set_order_metadata( $order, '_pp_stripe_charge_details', $charge_details );
	}

	/**
	 * Gets metadata about a stripe charge for a order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_charge( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_stripe_charge_details', $key );
	}

	/**
	 * Gets the total payout for a PeachPay stripe order.
	 *
	 * @param WC_Order $order A order.
	 */
	public static function total_payout( $order ) {
		$balance_transaction = self::get_charge( $order, 'balance_transaction' );
		if ( null === $balance_transaction ) {
			return null;
		}

		$net = $balance_transaction['net'];

		$refunds = self::get_charge( $order, 'refunds' );
		if ( null === $refunds ) {
			return $net;
		}

		foreach ( $refunds as $refund ) {
			if ( ! isset( $refund['balance_transaction'] ) ) {
				continue;
			}
			$net += $refund['balance_transaction']['net'];
		}

		return $net;
	}

	/**
	 * Gets the total fees for a PeachPay stripe order.
	 *
	 * @param WC_Order $order A order.
	 */
	public static function total_fees( $order ) {
		$balance_transaction = self::get_charge( $order, 'balance_transaction' );
		if ( null === $balance_transaction ) {
			return null;
		}

		$fees = $balance_transaction['fee'];

		$refunds = self::get_charge( $order, 'refunds' );
		if ( null === $refunds ) {
			return $fees;
		}

		foreach ( $refunds as $refund ) {
			if ( ! isset( $refund['balance_transaction'] ) ) {
				continue;
			}
			$fees += $refund['balance_transaction']['fee'];
		}

		return $fees;
	}

	/**
	 * Gets the total refunds for a PeachPay stripe order.
	 *
	 * @param WC_Order $order A order.
	 */
	public static function total_refunds( $order ) {
		$refund_total = 0;

		$refunds = self::get_charge( $order, 'refunds' );
		if ( null === $refunds ) {

			return $refund_total;
		}

		foreach ( $refunds as $refund ) {
			if ( ! isset( $refund['balance_transaction'] ) ) {
				continue;
			}
			$refund_total += $refund['balance_transaction']['net'];
		}

		return $refund_total;
	}
}
