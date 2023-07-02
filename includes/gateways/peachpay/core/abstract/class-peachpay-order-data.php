<?php
/**
 * PeachPay order data util class.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
class PeachPay_Order_Data {

	/**
	 * Stores an array of metadata on a order.
	 *
	 * @param WC_Order $order .
	 * @param string   $key .
	 * @param string   $data .
	 */
	protected static function set_order_metadata( $order, $key, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		$order->add_meta_data( $key, $data, true );

		$order->save();

		return true;
	}

	/**
	 * Gets an specific value of metadata.
	 *
	 * @param WC_Order $order .
	 * @param string   $metadata_key .
	 * @param string   $data_key .
	 */
	protected static function get_order_metadata( $order, $metadata_key, $data_key ) {
		if ( ! $order->meta_exists( $metadata_key ) ) {
			return null;
		}

		$data = $order->get_meta( $metadata_key, true );

		if ( ! is_array( $data ) || ! isset( $data[ $data_key ] ) ) {
			return null;
		}

		return $data[ $data_key ];
	}

	/**
	 * Adds metadata about peachpay details to a order.
	 *
	 * @param WC_Order $order A order.
	 * @param array    $peachpay_details Details about peachpay.
	 *
	 * @return boolean Indicating if successful.
	 */
	public static function set_peachpay_details( $order, $peachpay_details ) {
		return self::set_order_metadata( $order, '_pp_details', $peachpay_details );
	}

	/**
	 * Gets metadata about PeachPay for a order.
	 *
	 * @param WC_Order $order A order.
	 * @param string   $key Metadata key.
	 *
	 * @return mixed|null The details value or null.
	 */
	public static function get_peachpay( $order, $key ) {
		return self::get_order_metadata( $order, '_pp_details', $key );
	}

	/**
	 * Get if a order has a PeachPay service fee.
	 *
	 * @param WC_Order $order The order object.
	 * @return boolean If the order has a service fee.
	 */
	public static function has_service_fee( $order ) {
		$fees = $order->get_fees();

		foreach ( $fees as $fee ) {
			if ( $fee->get_name() === peachpay_get_service_fee_label() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets an order's PeachPay service fee.
	 *
	 * @param WC_Order $order The order object.
	 * @return float The service fee total.
	 */
	public static function get_service_fee_total( $order ) {
		$fees = $order->get_fees();

		foreach ( $fees as $fee ) {
			if ( $fee->get_name() === peachpay_get_service_fee_label() ) {
				return floatval( $fee->get_amount() );
			}
		}

		return 0;
	}


	/**
	 * Gets an order's PeachPay service fee percentage.
	 *
	 * @param WC_Order $order The order object.
	 * @return float The service fee percentage.
	 */
	public static function get_service_fee_percentage( $order ) {
		if ( self::get_order_metadata( $order, '_pp_details', 'service_fee_percentage' ) === null ) {
			return 0;
		}

		return self::get_order_metadata( $order, '_pp_details', 'service_fee_percentage' );
	}
}
