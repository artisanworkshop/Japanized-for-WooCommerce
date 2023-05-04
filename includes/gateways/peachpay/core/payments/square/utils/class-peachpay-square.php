<?php
/**
 * PeachPay Square util class.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_Square {
	/**
	 * Creates a square payment from a given WC_Order
	 *
	 * @param WC_Order $order The order to create a square payment for.
	 * @param mixed    $order_data order data processed by prepare_payment_result.
	 * @param mixed    $square_payment_details transaction details for square. Contains source_id, verification_token, prepare_reuse, and customer_id.
	 * @param string   $callback_url url callback for updating order status.
	 * @param string   $mode Square mode to make payment in.
	 */
	public static function create_payment( $order, $order_data, $square_payment_details, $callback_url, $mode ) {
		$body = array(
			'session'     => array(
				'id'             => PeachPay_Square_Order_Data::get_peachpay( $order, 'session_id' ),
				'merchant_id'    => peachpay_plugin_merchant_id(),
				'merchant_url'   => home_url(),
				'merchant_name'  => get_bloginfo( 'name' ),
				'plugin_version' => PEACHPAY_VERSION,
			),
			'transaction' => array(
				'id'                  => PeachPay_Square_Order_Data::get_peachpay( $order, 'transaction_id' ),
				'square'              => $square_payment_details,
				'status_callback_url' => $callback_url,
			),
			'order'       => array(
				'id'       => $order->get_id(),
				'amount'   => $order->get_total(),
				'currency' => peachpay_currency_code(),
				'data'     => $order_data,
			),
		);

		$response = wp_remote_post(
			peachpay_api_url() . 'api/v1/square/payment/create',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_Stripe_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_Stripe_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode( $body ),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		return $json;
	}

	/**
	 * Gets the square customer for the logged in user or returns null if one does not exist.
	 *
	 * @param int                    $user_id The id of the logged in user.
	 * @param "live"|"test"|"detect" $mode The mode to store customer under.
	 */
	public static function get_customer( $user_id, $mode = 'detect' ) {
		$square_mode = PeachPay_Square_Integration::mode( $mode );

		$customer_id = get_user_meta( $user_id, "_peachpay_square_{$square_mode}_customer", true );
		if ( ! $customer_id || ! isset( $customer_id['customer_id'] ) ) {
			return null;
		}

		return $customer_id['customer_id'];
	}

	/**
	 * Adds a square customer for a WC customer.
	 *
	 * @param int                    $user_id The id of the logged in user.
	 * @param string                 $customer_id The square customer ID.
	 * @param "live"|"test"|"detect" $mode The mode to store customer under.
	 */
	public static function set_customer( $user_id, $customer_id, $mode = 'detect' ) {
		$square_mode = PeachPay_Square_Integration::mode( $mode );

		if ( ! self::user_needs_square_customer( $user_id ) ) {
			return false;
		}

		return add_user_meta(
			$user_id,
			"_peachpay_square_{$square_mode}_customer",
			array(
				'customer_id' => $customer_id,
			),
			true
		);
	}

	/**
	 * Unsets the square customer ID associated with the WC_Customer and permanently removes
	 * all associated cards with this customer!
	 *
	 * @param int                    $user_id The id of the logged in user.
	 * @param "live"|"test"|"detect" $mode Current square mode.
	 */
	public static function unset_customer( $user_id, $mode = 'detect' ) {
		$square_mode = PeachPay_Square_Integration::mode( $mode );

		delete_user_meta( $user_id, "_peachpay_square_{$square_mode}_customer" );
		foreach ( WC_Payment_Tokens::get_customer_tokens( $user_id ) as $token ) {
			if ( peachpay_starts_with( $token->get_gateway_id(), 'peachpay_square_' ) ) {
				$token->delete();
			}
		}
	}

	/**
	 * Helper for checking whether a new square customer ID can be added to the WC Customer
	 *
	 * @param int $user_id The id of the logged in user.
	 */
	private static function user_needs_square_customer( $user_id ) {
		$user_id_valid       = ! 0 === $user_id;
		$has_square_customer = ! empty( self::get_customer( $user_id ) );

		return $user_id_valid || ! $has_square_customer;
	}

}
