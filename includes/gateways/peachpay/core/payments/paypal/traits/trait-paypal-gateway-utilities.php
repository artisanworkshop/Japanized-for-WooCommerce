<?php
/**
 * PeachPay PayPal gateway utility trait.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


trait PeachPay_PayPal_Gateway_Utilities {

	/**
	 * Gets the URL to redirect the frontend to for confirming the payment.
	 *
	 * @param WC_Order $order The order the payment intent was created for.
	 * @param string   $order_id The PayPal order id.
	 */
	protected function paypal_order_frontend_response( $order, $order_id ) {
		$cancel_url = is_wc_endpoint_url( 'order-pay' ) ? $order->get_checkout_payment_url() : $order->get_cancel_order_url();

		$data = rawurlencode(
			// PHPCS:ignore
			base64_encode(
				wp_json_encode(
					array(
						'type'           => 'paypal',
						'transaction_id' => PeachPay_PayPal_Order_data::get_peachpay( $order, 'transaction_id' ),
						'order_id'       => $order->get_id(),
						'gateway'        => $order->get_payment_method(),
						'success_url'    => $order->get_checkout_order_received_url(),
						'cancel_url'     => str_replace( '&amp;', '&', $cancel_url ),
						'data'           => array(
							'id' => $order_id,
						),
					)
				)
			)
		);

		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			if ( $order->get_status() !== 'pending' ) {
				return $order->get_checkout_order_received_url();
			}

			return $order->get_checkout_payment_url() . '&payment_data=' . $data;
		}

		return wc_get_checkout_url() . '#payment_data=' . $data;
	}

	/**
	 * Gets the PayPal order line items.
	 *
	 * @param WC_Order $order The order to retrieve line items from.
	 */
	public static function get_paypal_order_line_items( $order ) {
		$line_items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product      = $item->get_product();
			$line_items[] = array(
				'name'        => $product->get_title(),
				'quantity'    => strval( $item->get_quantity() ),
				'unit_amount' => array(
					'currency_code' => $order->get_currency(),
					'value'         => PeachPay_PayPal::format_amount( $product->get_price(), $order->get_currency() ),
				),
				'category'    => $product->is_virtual() ? 'DIGITAL_GOODS' : 'PHYSICAL_GOODS',
				'sku'         => $product->get_sku() ? $product->get_sku() : '',
			);
		}

		return $line_items;
	}

	/**
	 * Gets a shipping address in the format PayPal needs it.
	 *
	 * @param WC_Order $order The WC order to retrieve shipping details from.
	 */
	private function get_paypal_shipping_address( $order ) {
		$shipping_address = array();

		if ( $order->get_shipping_country() ) {
			$shipping_address['country_code'] = $order->get_shipping_country();
		}

		if ( $order->get_shipping_address_1() ) {
			$shipping_address['address_line_1'] = $order->get_shipping_address_1();
		}

		if ( $order->get_shipping_address_2() ) {
			$shipping_address['address_line_2'] = $order->get_shipping_address_2();
		}

		if ( $order->get_shipping_state() ) {
			$shipping_address['admin_area_1'] = $order->get_shipping_state();
		}

		if ( $order->get_shipping_city() ) {
			$shipping_address['admin_area_2'] = $order->get_shipping_city();
		}

		if ( $order->get_shipping_postcode() ) {
			$shipping_address['postal_code'] = $order->get_shipping_postcode();
		}

		return array(
			'type'    => 'SHIPPING',
			'address' => $shipping_address,
			'name'    => array(
				'full_name' => $order->get_formatted_shipping_full_name(),
			),
		);
	}
}
