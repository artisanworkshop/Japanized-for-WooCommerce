<?php
/**
 * PeachPay PayPal util class.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_PayPal {

	const ZERO_DECIMAL_CURRENCIES = array(
		'BIF' => 1,
		'CLP' => 1,
		'DJF' => 1,
		'GNF' => 1,
		'JPY' => 1,
		'KMF' => 1,
		'KRW' => 1,
		'MGA' => 1,
		'PYG' => 1,
		'RWF' => 1,
		'UGX' => 1,
		'VND' => 1,
		'VUV' => 1,
		'XAF' => 1,
		'XOF' => 1,
		'XPF' => 1,
		'TWD' => 1,
		'HUF' => 1,
	);

	/**
	 * Formats an amount according to the associated currency.
	 *
	 * @param float|string $amount The amount to format.
	 * @param string       $currency_code The currency code.
	 */
	public static function format_amount( $amount, $currency_code ) {
		if ( array_key_exists( $currency_code, self::ZERO_DECIMAL_CURRENCIES ) ) {
			return number_format( $amount, 0, '.', '' );
		}

		return number_format( $amount, 2, '.', '' );
	}
	/**
	 * Builds a href for linking to a merchants PayPal dashboard.
	 *
	 * @param string  $mode A live or test mode url.
	 * @param string  $path The path of the URL.
	 * @param string  $id The specific resource we are looking for.
	 * @param boolean $echo To echo a href of the URL.
	 */
	public static function dashboard_url( $mode, $path, $id, $echo = false ) {

		$base_url = 'live' === $mode ? 'https://www.paypal.com' : 'https://www.sandbox.paypal.com';
		$url      = $base_url . '/' . $path . '/' . $id;

		if ( $echo ) {
            // PHPCS:ignore
            echo "<a href='$url' target='_blank'>$id</a>";
		}

		return $url;
	}

	/**
	 * Creates a payment intent in stripe.
	 *
	 * @param WC_Order $order The woocommerce order to create a payment intent for.
	 * @param array    $paypal_order_param The parameters for the payment intent.
	 * @param array    $order_details The order details needed to create the payment intent.
	 * @param string   $mode The mode to place the payment in.
	 */
	public static function create_order( $order, $paypal_order_param, $order_details, $mode ) {

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v2/paypal/order',
			array(
				'data_format' => 'body',
				'headers'     => array(
                    // PHPCS:ignore
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'create_order_params' => $paypal_order_param,
						'order_details'       => $order_details,
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $json['success'] ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Payment error: ', 'peachpay-for-woocommerce' ) . $json['message'], 'error' );
			}

			// translators: The payment method title, The failure reason
			$order->update_status( 'failed', sprintf( __( '%1$s payment failed: %2$s', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $json['message'] ) );
			return null;
		}

		$data = $json['data'];
		self::calculate_payment_state( $order, $data );

		return $data;
	}

	/**
	 * Updates the PayPal order.
	 *
	 * @param WC_Order $order The WC order to update a PayPal order with.
	 */
	public static function update_order( $order ) {
		$peachpay_mode   = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'peachpay_mode' );
		$paypal_mode     = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'paypal_mode' );
		$paypal_order_id = PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'id' );

		$value =
		array(
			'reference_id' => $order->get_order_number(),
			'description'  => PeachPay_PayPal_Payment_Gateway::get_payment_description( $order ),
			'payee'        => array(
				'merchant_id' => PeachPay_PayPal_Integration::merchant_id(),
			),
			'amount'       => array(
				'currency_code' => $order->get_currency(),
				'value'         => self::format_amount( $order->get_total(), $order->get_currency() ),
				'breakdown'     => array(
					'discount'   => array(
						'currency_code' => $order->get_currency(),
						'value'         => self::format_amount( $order->get_discount_total(), $order->get_currency() ),
					),
					'item_total' => array(
						'currency_code' => $order->get_currency(),
						'value'         => self::format_amount( $order->get_subtotal() + $order->get_total_fees(), $order->get_currency() ),
					),
					'shipping'   => array(
						'currency_code' => $order->get_currency(),
						'value'         => self::format_amount( $order->get_shipping_total(), $order->get_currency() ),
					),
					'tax_total'  => array(
						'currency_code' => $order->get_currency(),
						'value'         => self::format_amount( $order->get_total_tax(), $order->get_currency() ),
					),
				),
			),
		);

		if ( PeachPay_PayPal_Advanced::get_setting( 'itemized_order_details' ) === 'yes' ) {
			$value['items'] = PeachPay_PayPal_Payment_Gateway::get_paypal_order_line_items( $order );
		}

		$response = wp_remote_post(
			peachpay_api_url( $peachpay_mode ) . 'api/v2/paypal/order/' . $paypal_order_id,
			array(
				'data_format' => 'body',
				'headers'     => array(
					// PHPCS:ignore
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $paypal_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						array(
							'op'    => 'replace',
							'path'  => "/purchase_units/@reference_id=='" . $order->get_order_number() . "'",
							'value' => $value,
						),
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		return $json;
	}

	/**
	 * Captures the PayPal order.
	 *
	 * @param WC_Order $order The WC order to capture a PayPal order for.
	 */
	public static function capture_order( $order ) {
		$peachpay_mode   = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'peachpay_mode' );
		$paypal_mode     = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'paypal_mode' );
		$paypal_order_id = PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'id' );

		$response = wp_remote_post(
			peachpay_api_url( $peachpay_mode ) . 'api/v2/paypal/order/' . $paypal_order_id . '/capture',
			array(
				'data_format' => 'body',
				'headers'     => array(
					// PHPCS:ignore
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $paypal_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		$data = $json['data'];
		self::calculate_payment_state( $order, $data );

		return $json;
	}

	/**
	 * Refunds a PayPal order.
	 *
	 * @param WC_Order $order The WC order to refund a PayPal payment for.
	 * @param array    $refund_params The parameters to refund the PayPal payment with.
	 */
	public static function refund_payment( $order, $refund_params ) {
		$peachpay_mode = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'peachpay_mode' );
		$paypal_mode   = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'paypal_mode' );

		$paypal_order_id   = PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'id' );
		$paypal_capture_id = $order->get_transaction_id();

		$response = wp_remote_post(
			peachpay_api_url( $peachpay_mode ) . 'api/v2/paypal/order/' . $paypal_order_id . '/capture/' . $paypal_capture_id . '/refund',
			array(
				'data_format' => 'body',
				'headers'     => array(
					// PHPCS:ignore
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $paypal_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_PayPal_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode( $refund_params ),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $json['success'] ) {
			return $json;
		}

		self::calculate_payment_state( $order, $json['data'] );

		$refunds = PeachPay_PayPal_Order_Data::get_refunded_payments( $order );

		if ( is_array( $refunds ) && count( $refunds ) > 0 ) {
			$refund = array_pop( $refunds );

			$refund_id     = $refund['id'];
			$refund_amount = wc_price( $refund['amount']['value'], array( 'currency' => $refund['amount']['currency_code'] ) );

			// translators: % 1$s the payment method title, % 3$s Refund amount, % 4$s Refund Id .
			$order->add_order_note( sprintf( __( '%1$s payment refunded %2$s (Refund Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $refund_amount, $refund_id ) );
		}

		return $json;
	}

	/**
	 * Calculates the WC order state for based on the current state of the PayPal transaction.
	 *
	 * @param WC_Order $order The WC order to update.
	 * @param array    $payment_details The PayPal payment details to use to update the order.
	 */
	public static function calculate_payment_state( $order, $payment_details = null ) {
		if ( null !== $payment_details ) {
			PeachPay_PayPal_Order_Data::set_order_transaction_details( $order, $payment_details );
		}

		$paypal_order_status = PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'status' );
		$paypal_order_id     = PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'id' );

		$paypal_transaction_id = null;
		$purchase_units        = PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'purchase_units' );
		if ( is_array( $purchase_units ) && 0 < count( $purchase_units ) && isset( $purchase_units[0]['payments'] ) ) {
			$payments = $purchase_units[0]['payments'];

			if ( isset( $payments['captures'] ) && is_array( $payments['captures'] ) && 0 < count( $payments['captures'] ) ) {
				$capture = $payments['captures'][0];

				$paypal_transaction_id = $capture['id'];

			}
		}

		if ( 'CREATED' === $paypal_order_status || 'SAVED' === $paypal_order_status ) {
			// translators: %1$s Payment method, %2$s transaction id.
			$order->add_order_note( sprintf( __( '%1$s payment was created. Awaiting customer confirmation. (PayPal Order Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $paypal_order_id ) );
		} elseif ( 'APPROVED' === $paypal_order_status ) {
			// translators: %1$s Payment method, %2$s transaction id.
			$order->set_status( 'on-hold', sprintf( __( '%1$s payment was approved. Awaiting capture. (PayPal Order Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $paypal_order_id ) );
		} elseif ( 'VOIDED' === $paypal_order_status ) {
			// translators: %1$s Payment method, %2$s transaction id.
			$order->set_status( 'cancelled', sprintf( __( '%1$s payment was voided. (PayPal Order Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $paypal_order_id ) );
		} elseif ( 'COMPLETED' === $paypal_order_status && ! $order->is_paid() && ! $order->get_transaction_id() ) {

			if ( $paypal_transaction_id ) {
				$order->payment_complete( $paypal_transaction_id );
				// translators: %1$s Payment method, %2$s transaction id.
				$order->add_order_note( sprintf( __( '%1$s payment is complete. (Capture Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $paypal_transaction_id ) );
			}
		}

		$order->save();
	}
}
