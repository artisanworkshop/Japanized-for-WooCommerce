<?php
/**
 * PeachPay Payment util class.
 *
 * @package PeachPay/Payments
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_Payment {

	/**
	 * For creating a PeachPay transaction, allowing us to create payment intents for subscription renewals.
	 *
	 * @param WC_Order $order Text.
	 * @param string   $session_id The.
	 * @param string   $transaction_location text.
	 * @param string   $peachpay_mode The mode to create the transaction.
	 */
	public static function create_transaction( $order, $session_id, $transaction_location, $peachpay_mode = 'detect' ) {
		$response = wp_remote_post(
			peachpay_api_url( $peachpay_mode ) . 'api/v1/transaction/create',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $peachpay_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Session-Id'     => $session_id,
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode(
					array(
						'session'     => array(
							'id'             => $session_id,
							'merchant_id'    => peachpay_plugin_merchant_id(),
							'merchant_url'   => home_url(),
							'merchant_name'  => get_bloginfo( 'name' ),
							'plugin_version' => PEACHPAY_VERSION,
							'platform'       => 'woocommerce',
						),
						'transaction' => array(
							'transaction_location'     => $transaction_location,
							'payment_method'           => $order->get_payment_method(),
							'payment_method_variation' => '',
						),
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

		$transaction_id = $json['data']['transaction_id'];

		PeachPay_Stripe_Order_Data::set_peachpay_details(
			$order,
			array(
				'session_id'     => $session_id,
				'transaction_id' => $transaction_id,
			)
		);

		return array(
			'success' => true,
		);
	}

	/**
	 * Updates a PeachPay transaction.
	 *
	 * @param WC_Order $order The order to update for.
	 * @param array    $options The order details to update the transaction.
	 */
	public static function update_transaction( $order, $options = array() ) {
		$peachpay_mode  = PeachPay_Order_Data::get_peachpay( $order, 'peachpay_mode' );
		$session_id     = PeachPay_Order_Data::get_peachpay( $order, 'session_id' );
		$transaction_id = PeachPay_Order_Data::get_peachpay( $order, 'transaction_id' );

		$body = array(
			'session'     => array(
				'id'             => $session_id,
				'merchant_id'    => peachpay_plugin_merchant_id(),
				'merchant_url'   => home_url(),
				'merchant_name'  => get_bloginfo( 'name' ),
				'plugin_version' => PEACHPAY_VERSION,
			),
			'transaction' => array(
				'id' => $transaction_id,
			),
			'order'       => array(
				'order_status' => $order->get_status(),
			),
		);

		if ( isset( $options['payment_status'] ) ) {
			$body['order']['payment_status'] = $options['payment_status'];
		}

		if ( isset( $options['order_details'] ) ) {
			$body['order']['order_details'] = $options['order_details'];
		}

		if ( isset( $options['note'] ) ) {
			$body['transaction']['note'] = $options['note'];
		}

		$response = wp_remote_post(
			peachpay_api_url( $peachpay_mode ) . 'api/v1/transaction/update',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type'            => 'application/json; charset=utf-8',
					'PeachPay-Mode'           => $peachpay_mode,
					'PeachPay-Merchant-Id'    => peachpay_plugin_merchant_id(),
					'PeachPay-Transaction-Id' => PeachPay_Order_Data::get_peachpay( $order, 'transaction_id' ),
					'PeachPay-Session-Id'     => PeachPay_Order_Data::get_peachpay( $order, 'session_id' ),
					'PeachPay-Plugin-Version' => PEACHPAY_VERSION,
				),
				'body'        => wp_json_encode( $body ),
			)
		);

		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $json['success'] ) {
			return array(
				'success' => false,
				'message' => $json['message'],
			);
		}

		return array(
			'success' => true,
		);
	}

	/**
	 * Updates a PeachPay transaction.
	 *
	 * TODO followup(refactor/cleanup) Move method into Purchase order folder
	 *
	 * @param WC_Order $order The order to update for.
	 * @param string   $session_id The session id for the order.
	 * @param string   $transaction_id The transaction id for the order.
	 * @param array    $purchase_order_number the information to update the transaction with.
	 */
	public static function update_transaction_purchase_order( $order, $session_id, $transaction_id, $purchase_order_number ) {
		$response = wp_remote_post(
			peachpay_api_url() . 'api/v1/transaction/update',
			array(
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'        => wp_json_encode(
					array(
						'session'     => array(
							'id'             => $session_id,
							'merchant_id'    => peachpay_plugin_merchant_id(),
							'merchant_url'   => home_url(),
							'merchant_name'  => get_bloginfo( 'name' ),
							'plugin_version' => PEACHPAY_VERSION,
						),
						'transaction' => array(
							'id'             => $transaction_id,
							'purchase_order' => array(
								'purchase_order_number' => $purchase_order_number,
							),
						),
						'order'       => array(
							'payment_status' => $order->get_status(),
							'order_status'   => $order->get_status(),
							'data'           => array(
								'id'      => $order->get_id(),
								'result'  => 'success',
								'details' => $order->get_data(),
							),
						),
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

		return array(
			'success' => true,
		);
	}

	/**
	 * Gets the available PeachPay gateways instances.
	 */
	public static function available_gateways() {
		$gateways = array();

		foreach ( WC()->payment_gateways->payment_gateways() as $gateway ) {
			if ( $gateway instanceof PeachPay_Payment_Gateway ) {
				$gateways[] = $gateway;
			}
		}

		return $gateways;
	}

	/**
	 * Gets the available PeachPay gateway icons HTML.
	 *
	 * @param int $maximum_icons The amount of icons to display before summing up remaining available gateways and appending a "+5" to list.
	 */
	public static function available_gateway_icons( $maximum_icons = 4 ) {
		$icons              = '';
		$total_icons        = 0;
		$remaining_icons    = 0;
		$available_gateways = self::available_gateways();
		foreach ( $available_gateways as $gateway ) {
			if ( $gateway->is_available() ) {
				$current_icon = $gateway->get_icon();

				// If the gateway does not have an icon then just include it in the remaining.
				if ( ! $current_icon ) {
					$remaining_icons++;
					continue;
				}

				// Remove duplicates
				if ( strpos( $icons, $current_icon ) ) {
					continue;
				}

				// Only show maximum requested icons and then record remaining.
				if ( $total_icons >= $maximum_icons ) {
					$remaining_icons++;
					continue;
				}

				$total_icons++;
				$icons .= $gateway->get_icon();
			}
		}

		if ( $remaining_icons > 0 ) {
			$icons .= '<span class="peachpay-gateway-icons" style="gap:0.2rem;margin-left:0.4rem;text-align: center;justify-content: center;align-items: center;height: calc(1.4rem - 2px);width: calc(1.4rem - 2px);font-size: 13px;">+' . $remaining_icons . '</span>';
		}

		return $icons;
	}
}
