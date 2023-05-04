<?php
/**
 * PeachPay Authorize.net util class.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Authnet API Client class.
 */
class PeachPay_Authnet {
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
	);

	/**
	 * Formats an amount within the constraints that Authnet expects.
	 *
	 * @param float  $amount The amount to format.
	 * @param string $currency_code The currency to format the amount for.
	 */
	public static function format_amount( $amount, $currency_code = 'USD' ) {
		if ( array_key_exists( $currency_code, self::ZERO_DECIMAL_CURRENCIES ) ) {
			return number_format( $amount, 0, '.', '' );
		}

		return number_format( $amount, 2, '.', '' );
	}

	/**
	 * Unlinks Authorize.net from PeachPay.
	 */
	public static function unlink() {
		$response = wp_remote_get(
			peachpay_api_url() . 'api/v1/authnet/unlink/oauth?merchant_id=' . peachpay_plugin_merchant_id(),
			array(
				'headers' => array(
					'PeachPay-Mode'           => PeachPay_Authnet_Integration::mode(),
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( true !== $json['success'] ) {
			return false;
		}

		delete_option( 'peachpay_connected_authnet_account' );

		return true;
	}

	/**
	 * Creates authnet transaction.
	 *
	 * @param WC_Order $order The order to create a payment for.
	 * @param array    $transaction_parameters Parameters on how to create the payment.
	 * @param array    $order_details Standardized order details for creating a payment.
	 * @param boolean  $mode The payment mode.
	 */
	public static function create_payment( $order, $transaction_parameters, $order_details, $mode ) {

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/authnet/payment',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'create_transaction_params' => $transaction_parameters,
						'order_details'             => $order_details,
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
	 * Captures a an Authorize.net transaction associated with an order.
	 *
	 * @param WC_Order $order The order with a Authorize.net transaction.
	 * @param float    $capture_amount The amount to capture from the authorized transaction.
	 */
	public static function capture_payment( $order, $capture_amount ) {
		$mode                   = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'mode' );
		$authnet_mode           = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'authnet_mode' );
		$authnet_transaction_id = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );
		$response               = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/authnet/payment/capture',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $authnet_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Session-Id'     => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Transaction-Id' => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'transactionType' => 'priorAuthCaptureTransaction',
						'amount'          => self::format_amount( $capture_amount, $order->get_currency() ),
						'refTransId'      => $authnet_transaction_id,
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $json['success'] ) {
			return array(
				'success' => false,
				'message' => $json['message'],
			);
		}

		self::calculate_payment_state( $order, $json['data'] );

		return array(
			'success' => true,
			'message' => 'Success.',
		);
	}

	/**
	 * Voids an Authorize.net transaction connected to a Order.
	 *
	 * @param WC_Order $order The order with a Authorize.net transaction.
	 */
	public static function void_payment( $order ) {
		$mode                   = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'mode' );
		$authnet_mode           = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'authnet_mode' );
		$authnet_transaction_id = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/authnet/payment/void',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $authnet_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Session-Id'     => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Transaction-Id' => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'transactionType' => 'voidTransaction',
						'refTransId'      => $authnet_transaction_id,
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $json['success'] ) {
			return array(
				'success' => false,
				'message' => $json['message'],
			);
		}

		self::calculate_payment_state( $order, $json['data'] );

		return array(
			'success' => true,
			'message' => 'Success.',
		);
	}

	/**
	 * Refunds an Authorize.net transaction connected to a Order.
	 *
	 * @param WC_Order $order The order with a Authorize.net transaction.
	 * @param array    $refund_params Refund parameters for creating a refund request.
	 */
	public static function refund_payment( $order, $refund_params ) {
		$mode         = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'mode' );
		$authnet_mode = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'authnet_mode' );
		$payment      = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'payment' );

		if ( null !== $payment['creditCard'] ) {
			$card_num                 = $payment['creditCard']['cardNumber'];
			$refund_params['payment'] = array(
				'creditCard' => array(
					'cardNumber'     => $card_num,
					'expirationDate' => 'XXXX',
				),
			);
		} elseif ( null !== $payment['bankAccount'] ) {
			$refund_params['payment'] = array(
				'bankAccount' => array(
					'accountType'   => 'checking',
					'routingNumber' => $payment['bankAccount']['routingNumber'],
					'accountNumber' => $payment['bankAccount']['accountNumber'],
					'nameOnAccount' => $payment['bankAccount']['nameOnAccount'],
					'echeckType'    => $payment['bankAccount']['echeckType'],
				),
			);
		}

		$ref_transaction_id = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'refTransId' );
		$transaction_id     = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );
		$id                 = $ref_transaction_id ? $ref_transaction_id : $transaction_id;
		if ( ! $id ) {
			return new \WP_Error( 'wc_' . $order->get_id() . '_refund_failed', __( 'Refund error: Invalid Transaction ID', 'peachpay-for-woocommerce' ) );
		}

		$refund_params['refTransId'] = $id;

		$refund_params['order'] = array(
			'invoiceNumber' => $order->get_order_number(),
			'description'   => PeachPay_Payment_Gateway::get_payment_description( $order ),
		);

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/authnet/payment/refund',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $authnet_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Session-Id'     => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Transaction-Id' => PeachPay_Authnet_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode( $refund_params ),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $json['success'] ) {
			return array(
				'success' => false,
				'message' => $json['message'],
			);
		}

		self::calculate_payment_state( $order, $json['data'] );

		$refunded      = $order->get_remaining_refund_amount();
		$refund_id     = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );
		$settle_amount = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'settleAmount' );
		$refund_amount = wc_price( $settle_amount, array( 'currency' => $order->get_currency() ) );

		if ( $refunded <= 0 ) {
			// translators: %1$s the payment method title, %3$s Refund amount, %4$s Refund Id.
			$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment refunded %2$s (Transaction Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $refund_amount, $refund_id ) );
		} else {
			// translators: %1$s the payment method title, %3$s Refund amount, %4$s Refund Id.
			$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment partially refunded %2$s (Transaction Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $refund_amount, $refund_id ) );
		}

		return array(
			'success' => true,
			'message' => 'Success.',
		);
	}

	/**
	 * Calculates the correct order status from a transaction.
	 *
	 * @param WC_Order $order The WC order.
	 * @param array    $data The Authnet transaction data.
	 */
	public static function calculate_payment_state( $order, $data ) {
		$old_transaction_status = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );

		if ( null !== $data && isset( $data['transaction_details'] ) ) {
			PeachPay_Authnet_Order_Data::set_transaction_details( $order, $data['transaction_details'] );
		}

		$authnet_transaction_id = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );
		$new_transaction_status = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );

		if ( $authnet_transaction_id ) {
			$order->set_transaction_id( $authnet_transaction_id );
		}

		if ( $new_transaction_status === $old_transaction_status && 'declined' !== $new_transaction_status ) {
			return;
		}

		if ( 'capturedPendingSettlement' === $new_transaction_status ) {
			if ( $authnet_transaction_id ) {
				$order->payment_complete( $authnet_transaction_id );
				$amount = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'settleAmount' );
				// translators: %1$s Payment method title, %2$s amount ,%3$s transaction id.
				$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment is complete for %2$s. (Transaction Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $amount, array( 'currency' => $order->get_currency() ) ), $authnet_transaction_id ) );
			}
		} elseif ( 'authorizedPendingCapture' === $new_transaction_status ) {
			if ( $order->get_status() === 'on-hold' ) {
				// translators: %1$s Payment method title, %2$s amount ,%3$s transaction id.
				$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment authorized for %2$s. (Transaction Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ), $authnet_transaction_id ) );
			} else {
				// translators: %1$s Payment method title, %2$s amount ,%3$s transaction id.
				$order->set_status( 'on-hold', sprintf( __( 'Authorize.net %1$s payment authorized for %2$s. (Transaction Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ), $authnet_transaction_id ) );
			}
		} elseif ( 'voided' === $new_transaction_status ) {
			// translators: %1$s Payment method title, %2$s transaction id.
			$order->set_status( 'cancelled', sprintf( __( 'Authorize.net %1$s payment was voided. (Transaction Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $authnet_transaction_id ) );
		} elseif ( 'FDSAuthorizedPendingReview' === $new_transaction_status ) {
			// translators: %1$s Payment method title, %2$s transaction id.
			$order->set_status( 'on-hold', sprintf( __( 'Authorize.net %1$s payment authorized but held pending review. Manage the payment in the Authorize.net MINT dashboard. (Transaction Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $authnet_transaction_id ) );
		} elseif ( 'FDSPendingReview' === $new_transaction_status ) {
			// translators: %1$s Payment method title, %2$s transaction id.
			$order->set_status( 'on-hold', sprintf( __( 'Authorize.net %1$s payment held pending review. Manage the payment in the Authorize.net MINT dashboard. (Transaction Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $authnet_transaction_id ) );
		} elseif ( 'declined' === $new_transaction_status ) {
			$order->set_status( 'failed' );
			$decline_reason = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'responseReasonDescription' );
			if ( $decline_reason ) {
				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( __( 'Payment error: ', 'peachpay-for-woocommerce' ) . $decline_reason, 'error' );
				}
				// translators: %1$s Payment method title, %2$s transaction id , %3$s reason
				$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment was declined (Transaction Id: %2$s). Reason: %3$s', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $authnet_transaction_id, $decline_reason ) );
			} else {
				// translators: %1$s Payment method title, %2$s transaction id
				$order->add_order_note( sprintf( __( 'Authorize.net %1$s payment was declined (Transaction Id: %2$s).', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $authnet_transaction_id ) );
			}
		}

		$order->save();
	}
}
