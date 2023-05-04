<?php
/**
 * PeachPay Poynt util class.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
final class PeachPay_Poynt {

	const ZERO_DECIMAL_CURRENCIES = array(
		'BIF',
		'CLP',
		'DJF',
		'GNF',
		'JPY',
		'KMF',
		'KRW',
		'MGA',
		'PYG',
		'RWF',
		'UGX',
		'VND',
		'VUV',
		'XAF',
		'XOF',
		'XPF',
	);

	/**
	 * Formats a Poynt amount for displaying to merchants/customers.
	 *
	 * @param string $amount Poynt amount.
	 * @param string $currency_code Currency code returned from Poynt API.
	 */
	public static function display_amount( $amount, $currency_code ) {
		$amount = floatval( $amount );

		if ( ! in_array( $currency_code, self::ZERO_DECIMAL_CURRENCIES, true ) ) {
			$amount = $amount / 100;
		}

		return $amount;
	}

	/**
	 * Formats an amount to use with Poynts API's
	 *
	 * @param string $amount The amount to format.
	 * @param string $currency_code Currency code returned from Poynts API.
	 */
	public static function format_amount( $amount, $currency_code ) {
		$amount = floatval( $amount );

		if ( in_array( $currency_code, self::ZERO_DECIMAL_CURRENCIES, true ) ) {
			return round( $amount );
		}

		return round( $amount * 100 );
	}

	/**
	 * Unlink Poynt account.
	 */
	public static function unlink() {
		$response = wp_remote_get(
			peachpay_api_url() . 'api/v1/poynt/unlink/oauth?merchant_id=' . peachpay_plugin_merchant_id(),
			array(
				'headers' => array(
					'PeachPay-Mode'           => PeachPay_Poynt_Integration::mode(),
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( true !== $json['success'] ) {
			add_settings_error( 'peachpay_messages', 'peachpay_message', __( 'Unable to unlink Poynt account. Please try again or contact us if you need help.', 'peachpay-for-woocommerce' ), 'error' );
			return false;
		}

		delete_option( 'peachpay_connected_poynt_account' );

		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have successfully unlinked your GoDaddy Poynt account.', 'peachpay-for-woocommerce' ),
			'success'
		);
	}

	/**
	 * Creates a transaction in Poynt.
	 *
	 * @param WC_Order $order The woocommerce order to create a charge for.
	 * @param string   $nonce The charge nonce.
	 */
	public static function create_token( $order, $nonce ) {
		$mode       = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'mode' );
		$poynt_mode = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' );

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/poynt/tokenize',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $poynt_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'businessId' => PeachPay_Poynt_Integration::business_id(),
						'nonce'      => $nonce,
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $json['success'] ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Payment error: ', 'peachpay-for-woocommerce' ) . $json['message'], 'error' );
			}
			return null;
		}

		$data = $json['data'];
		self::calculate_payment_state( $order, $data );

		return $data;
	}

	/**
	 * Creates a transaction in Poynt.
	 *
	 * @param WC_Order $order The woocommerce order to create a charge for.
	 * @param array    $charge_options The charge options.
	 * @param array    $order_details The order details needed to create the charge.
	 */
	public static function create_payment( $order, $charge_options, $order_details ) {
		$mode       = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'mode' );
		$poynt_mode = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' );

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/poynt/transaction',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $poynt_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'charge_options' => $charge_options,
						'order_details'  => $order_details,
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
			$order->update_status( 'failed', sprintf( __( 'GoDaddy Poynt %1$s payment failed: %2$s', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $json['message'] ) );
			return null;
		}

		if ( isset( $json['data'] ) ) {
			self::calculate_payment_state( $order, $json['data'] );
		}

		return $json;
	}

	/**
	 * Captures a Poynt transaction connected to a Order.
	 *
	 * @param WC_Order $order The order with a Poynt transaction.
	 * @param float    $capture_amount The amount to capture from the authorized transaction.
	 */
	public static function capture_payment( $order, $capture_amount ) {
		$mode       = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'mode' );
		$poynt_mode = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' );

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/poynt/transaction/capture',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $poynt_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Session-Id'     => PeachPay_Poynt_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Transaction-Id' => PeachPay_Poynt_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'requestId'     => wp_generate_uuid4(),
						'businessId'    => PeachPay_Poynt_Integration::business_id(),
						'transactionId' => PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' ),
						'currency'      => $order->get_currency(),
						'amount'        => self::format_amount( $capture_amount, $order->get_currency() ),
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $json['data'] ) ) {
			self::calculate_payment_state( $order, $json['data'] );
		}

		return $json;
	}

	/**
	 * Voids a Poynt transaction connected to a Order.
	 *
	 * @param WC_Order $order The order with a Poynt transaction.
	 */
	public static function void_payment( $order ) {
		$mode       = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'mode' );
		$poynt_mode = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' );

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/poynt/transaction/void',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $poynt_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Session-Id'     => PeachPay_Poynt_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Transaction-Id' => PeachPay_Poynt_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'requestId'     => wp_generate_uuid4(),
						'businessId'    => PeachPay_Poynt_Integration::business_id(),
						'transactionId' => PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' ),
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $json['data'] ) ) {
			self::calculate_payment_state( $order, $json['data'] );
		}

		return $json;
	}

	/**
	 * Refunds Poynt transaction connected to an order
	 *
	 * @param WC_Order $order The order with a Poynt transaction.
	 * @param float    $amount The refund amount.
	 */
	public static function refund_payment( $order, $amount ) {
		$mode       = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'mode' );
		$poynt_mode = PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' );

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/poynt/transaction/refund',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $poynt_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'requestId'     => wp_generate_uuid4(),
						'businessId'    => PeachPay_Poynt_Integration::business_id(),
						'transactionId' => PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' ),
						'amount'        => self::format_amount( $amount, $order->get_currency() ),
					)
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $json['data'] ) ) {
			self::calculate_payment_state( $order, $json['data'] );
		}

		return $json;
	}

	/**
	 * Registers or resets a plugins Poynt webhooks.
	 */
	public static function register_webhooks() {
		$mode       = peachpay_is_test_mode() ? 'test' : 'live';
		$poynt_mode = PeachPay_Poynt_Integration::mode();

		$response = wp_remote_post(
			peachpay_api_url( $mode ) . 'api/v1/poynt/webhook/register',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $poynt_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		return $json;
	}

	/**
	 * Calculates the payment state to change a orders status and notes.
	 *
	 * @param WC_Order $order The WC Poynt order to calculate its order status.
	 * @param array    $data The latest Poynt transaction details.
	 */
	public static function calculate_payment_state( $order, $data = null ) {
		$old_transaction_status = PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' );
		if ( null !== $data && isset( $data['transaction_details'] ) ) {
			PeachPay_Poynt_Order_Data::set_transaction( $order, $data['transaction_details'] );
		}

		if ( null !== $data && isset( $data['token_details'] ) ) {
			PeachPay_Poynt_Order_Data::set_token( $order, $data['token_details'] );
		}

		if ( null !== $data && isset( $data['refund_details'] ) ) {
			if ( PeachPay_Poynt_Order_Data::refund_exists( $order, $data['refund_details'] ) ) {
				return;
			}

			PeachPay_Poynt_Order_Data::push_refund( $order, $data['refund_details'] );
		}

		$poynt_transaction_id   = PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' );
		$new_transaction_status = PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' );

		if ( $poynt_transaction_id ) {
			$order->set_transaction_id( $poynt_transaction_id );
		}

		if ( $new_transaction_status === $old_transaction_status && 'PARTIALLY_REFUNDED' !== $new_transaction_status ) {
			return;
		}

		if ( 'CAPTURED' === $new_transaction_status || 'PARTIALLY_CAPTURED' === $new_transaction_status ) {
			$currency = PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['currency'];
			$amount   = self::display_amount( PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['transactionAmount'], $currency );
			// translators: %1$s Payment method title, %2$s amount ,%3$s transaction id.
			$order->add_order_note( sprintf( __( 'GoDaddy Poynt %1$s payment captured for %2$s. (Capture Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $amount, array( 'currency' => $currency ) ), $poynt_transaction_id ) );
			$order->payment_complete();
		} elseif ( 'AUTHORIZED' === $new_transaction_status ) {
			$currency = PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['currency'];
			$amount   = self::display_amount( PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['transactionAmount'], $currency );
			// translators: %1$s Payment method title, %2$s amount ,%3$s transaction id.
			$order->add_order_note( sprintf( __( 'GoDaddy Poynt %1$s payment is authorized for %2$s. (Authorization Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $amount, array( 'currency' => $currency ) ), $poynt_transaction_id ) );
			$order->set_status( 'on-hold' );
		} elseif ( 'VOIDED' === $new_transaction_status ) {
			// translators: %1$s Payment method title, %3$s transaction id.
			$order->add_order_note( sprintf( __( 'GoDaddy Poynt %1$s payment was voided. (Authorization Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $poynt_transaction_id ) );
			$order->set_status( 'cancelled' );
		}

		if ( null !== $data && isset( $data['refund_details'] ) ) {
			$refund_details = $data['refund_details'];
			$refunded       = self::display_amount( $refund_details['amounts']['transactionAmount'], $order->get_currency() );
			$refund_id      = $refund_details['id'];

			if ( $order->get_remaining_refund_amount() <= 0 ) {
				// translators: %1$s the payment method title, %3$s Refund amount, %4$s Refund Id.
				$order->add_order_note( sprintf( __( 'GoDaddy Poynt %1$s payment refunded %2$s (Refund Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $refunded, array( 'currency' => $order->get_currency() ) ), $refund_id ) );
			} else {
				// translators: %1$s the payment method title, %3$s Refund amount, %4$s Refund Id.
				$order->add_order_note( sprintf( __( 'GoDaddy Poynt %1$s payment partially refunded %2$s (Refund Id: %3$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), wc_price( $refunded, array( 'currency' => $order->get_currency() ) ), $refund_id ) );
			}
		}

		$order->save();
	}
}
