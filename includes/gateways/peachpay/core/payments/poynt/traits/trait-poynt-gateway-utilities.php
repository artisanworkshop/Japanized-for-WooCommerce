<?php
/**
 * PeachPay Poynt utility trait.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


trait PeachPay_Poynt_Gateway_Utilities {

	/**
	 * Sets the billing charge details on the charge options array
	 *
	 * @param array    $charge_options The charge options.
	 * @param WC_Order $order The given order.
	 */
	protected function set_charge_billing( &$charge_options, $order ) {
		if ( $order->has_billing_address() ) {
			$address = array();

			peachpay_add_if_not_empty( $address, 'line1', $order->get_billing_address_1() );
			peachpay_add_if_not_empty( $address, 'line2', $order->get_billing_address_2() );
			peachpay_add_if_not_empty( $address, 'city', $order->get_billing_city() );
			peachpay_add_if_not_empty( $address, 'territory', $order->get_billing_state() );
			peachpay_add_if_not_empty( $address, 'countryCode', $order->get_billing_country() );
			peachpay_add_if_not_empty( $address, 'postalCode', $order->get_billing_postcode() );

			$charge_options['address'] = $address;
		}
	}

	/**
	 * Sets the shipping charge details on the charge options array
	 *
	 * @param array    $charge_options The charge options.
	 * @param WC_Order $order The given order.
	 */
	protected function set_charge_shipping( &$charge_options, $order ) {
		if ( $order->has_shipping_address() ) {
			$shipping_address = array();

			peachpay_add_if_not_empty( $shipping_address, 'line1', $order->get_shipping_address_1() );
			peachpay_add_if_not_empty( $shipping_address, 'line2', $order->get_shipping_address_2() );
			peachpay_add_if_not_empty( $shipping_address, 'city', $order->get_shipping_city() );
			peachpay_add_if_not_empty( $shipping_address, 'territory', $order->get_shipping_state() );
			peachpay_add_if_not_empty( $shipping_address, 'countryCode', $order->get_shipping_country() );
			peachpay_add_if_not_empty( $shipping_address, 'postalCode', $order->get_shipping_postcode() );

			$charge_options['shippingAddress'] = $shipping_address;
		}
	}

	/**
	 * Sets the Email receipt charge details on the charge options array
	 *
	 * @param array    $charge_options The charge options.
	 * @param WC_Order $order The given order.
	 */
	protected function set_charge_email_receipt( &$charge_options, $order ) {
		if ( PeachPay_Poynt_Advanced::get_setting( 'email_receipts' ) === 'yes' && $order->get_billing_email() ) {
			$charge_options['emailReceipt']        = true;
			$charge_options['receiptEmailAddress'] = $order->get_billing_email();
		}
	}

	/**
	 * Creates a WC payment token if a customer opted to have the payment method saved for later.
	 *
	 * @param WC_Order $order The order to attach the token.
	 */
	protected function maybe_create_payment_token( $order ) {
		// PHPCS:disable WordPress.Security.NonceVerification.Missing
		$wc_token_id     = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
		$save_to_account = isset( $_POST[ "wc-$this->id-new-payment-method" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-new-payment-method" ] ) ) : null;
		// PHPCS:enable

		if ( $this->supports( 'tokenization' ) && get_current_user_id() !== 0 ) {
			if ( 'true' === $save_to_account && ( 'new' === $wc_token_id || null === $wc_token_id ) ) {
				$this->create_payment_token( $order );
			}
		}
	}

}
