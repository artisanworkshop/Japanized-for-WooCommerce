<?php
/**
 * PeachPay Stripe utility trait.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


trait PeachPay_Stripe_Gateway_Utilities {

	/**
	 * PHP get magic method to retrieve gateway specific settings. Any stripe settings
	 * should be added to the list inside this method.
	 *
	 * @param string $key The name of the key.
	 */
	public function __get( $key ) {
		if ( in_array(
			$key,
			array(
				'capture_method',
				'statement_descriptor_suffix',
				'payment_method_options__card__request_three_d_secure',
			),
			true
		) ) {
			$value = $this->get_option( $key, null );

			if ( empty( $value ) || '' === $value ) {
				$value = null;
			}

			return $value;
		}
	}

	/**
	 * Gets the payment intent description.
	 *
	 * @param WC_Order $order Order details.
	 * @param boolean  $is_subscription If the description should be for a subscription.
	 */
	protected function get_payment_intent_description( $order, $is_subscription = false ) {
		if ( $is_subscription ) {
			return get_bloginfo( 'name' ) . ' - Subscription Order ' . $order->get_order_number() . ' (PeachPay)';
		} else {
			return get_bloginfo( 'name' ) . ' - Order ' . $order->get_order_number() . ' (PeachPay)';
		}
	}

	/**
	 * Gets the stripe payment intents `payment_method_options` object.
	 */
	protected function payment_method_options() {
		return array(
			'card' => array(
				'request_three_d_secure' => 1 === $this->payment_method_options__card__request_three_d_secure ? 'any' : 'automatic',
			),
		);
	}

	/**
	 * Gets the URL to redirect the frontend to for confirming the payment.
	 *
	 * @param WC_Order $order The order the payment intent was created for.
	 * @param string   $client_secret The payment intent client secret.
	 */
	protected function payment_intent_frontend_response( $order, $client_secret ) {
		$data = rawurlencode(
            // PHPCS:ignore
			base64_encode(
				wp_json_encode(
					array(
						'type'             => 'stripe',
						'order_id'         => $order->get_id(),
						'transaction_id'   => PeachPay_Stripe_Order_data::get_peachpay( $order, 'transaction_id' ),
						'gateway'          => $order->get_payment_method(),
						'intermediate_url' => $this->payment_intent_intermediate_url( $order ),
						'success_url'      => $order->get_checkout_order_received_url(),
						'cancel_url'       => str_replace( '&amp;', '&', $order->get_cancel_order_url() ),
						'data'             => array(
							'id'            => PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'id' ),
							'client_secret' => $client_secret,
						),
					)
				)
			)
		);

		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			if ( $order->get_status() !== 'pending' ) {
				return $order->get_checkout_order_received_url();
			}

			return $order->get_checkout_payment_url() . '#payment_data=' . $data;
		}

		return wc_get_checkout_url() . '#payment_data=' . $data;
	}

	/**
	 * Gets the intermediate redirect URL for redirect based stripe payments.
	 *
	 * @param WC_Order $order The order to create the URL for.
	 */
	private function payment_intent_intermediate_url( $order ) {
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$cancel_order_url = $order->get_checkout_payment_url();
		} else {
			$cancel_order_url = str_replace( '&amp;', '&', $order->get_cancel_order_url() );
		}
		$state_param_data = rawurlencode(
            // PHPCS:ignore
			base64_encode(
				wp_json_encode(
					array(
						'public_key'  => PeachPay_Stripe_Integration::public_key(),
						'connect_id'  => PeachPay_Stripe_Integration::connect_id(),
						'success_url' => $order->get_checkout_order_received_url(),
						'failure_url' => $cancel_order_url,
						'color'       => PEACHPAY_DEFAULT_BACKGROUND_COLOR,
					)
				)
			)
		);

		return PeachPay::get_asset_url( 'stripe-redirect.html' ) . "?state=$state_param_data";
	}
}
