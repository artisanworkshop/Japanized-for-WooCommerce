<?php
/**
 * PeachPay Authnet gateway utility trait.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


trait PeachPay_Authnet_Gateway_Utilities {

	/**
	 * Gets the Authorize.net order line items.
	 *
	 * @param WC_Order $order The order to retrieve line items from.
	 */
	protected static function get_authnet_order_line_items( $order ) {
		$line_items = array();

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product                  = $item->get_product();
			$line_items['lineItem'][] = array(
				'itemId'      => strval( $product->get_id() ),
				'name'        => str_truncate( $product->get_name(), 31 ),
				'description' => str_truncate( $product->get_slug(), 255 ),
				'quantity'    => $item->get_quantity(),
				'unitPrice'   => strval( $product->get_price() ),
				'taxable'     => 'taxable' === $product->get_tax_status(),
			);
		}

		// maximum of 30 line items per order
		if ( isset( $line_items['lineItem'] ) && count( $line_items['lineItem'] ) > 30 ) {
			$line_items['lineItem'] = array_slice( $line_items['lineItem'], 0, 30 );
		}

		return $line_items;
	}
}
