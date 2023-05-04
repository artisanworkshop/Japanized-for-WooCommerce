<?php
/**
 * Support for the Woocommerce-Subscriptions Plugin Actions
 * Plugin: https://woocommerce.com/products/woocommerce-subscriptions/
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Intilizes WC Subscription support.
 */
function peachpay_wcs_init() {
	add_action( 'woocommerce_checkout_create_order_line_item', 'peachpay_wcs_add_checkout_order_line_item_data', 10, 1 );
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcs_add_cart_item_meta', 10, 2 );
	add_filter( 'peachpay_calculate_carts', 'peachpay_wcs_calculate_recurring_carts', 10, 1 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_wcs_init' );

/**
 * Adds any needed meta data to a cart item if it is a subscription
 *
 * @since 1.44.0
 * @param array $pp_cart_item The item to add meta details related to subscriptions.
 * @param array $wc_line_item   The WC line item object to source details from.
 */
function peachpay_wcs_add_cart_item_meta( array $pp_cart_item, array $wc_line_item ) {
	$wc_product = $wc_line_item['data'];
	if ( $wc_product->get_type() === 'subscription' ) {
		$pp_cart_item['is_subscription']           = true;
		$pp_cart_item['subscription_price_string'] = WC_Subscriptions_Product::get_price_string( $wc_product );
	}

	return $pp_cart_item;
}

/**
 * Calculates and gathers totals for recurring carts.
 *
 * @param array $calculated_carts Carts calculated to be shown in the peachpay modal.
 */
function peachpay_wcs_calculate_recurring_carts( $calculated_carts ) {
	WC_Subscriptions_Cart::calculate_subscription_totals( WC()->cart->total, WC()->cart );
	$recurring_carts_packages = WC_Subscriptions_Cart::get_recurring_shipping_packages();

	if ( is_array( WC()->cart->recurring_carts ) || is_object( WC()->cart->recurring_carts ) ) {
		foreach ( WC()->cart->recurring_carts as $key => $cart ) {
			// Recurring shipping options are handled separately and needed to be added for renewing physical products.
			$calculated_carts[ $key ]                   = peachpay_build_cart_response( $key, $cart );
			$recurring_cart_package_record              = peachpay_cart_shipping_package_record( $key, WC()->shipping->calculate_shipping( $recurring_carts_packages[ $key ] ) );
			$calculated_carts[ $key ]['package_record'] = $recurring_cart_package_record;

			$subscription_product                                  = peachpay_wcs_get_subscription_in_cart( $cart );
			$calculated_carts[ $key ]['cart_meta']['subscription'] = array(
				'length'          => WC_Subscriptions_Product::get_length( $subscription_product ),
				'period'          => WC_Subscriptions_Product::get_period( $subscription_product ),
				'period_interval' => WC_Subscriptions_Product::get_interval( $subscription_product ),
				'first_renewal'   => WC_Subscriptions_Product::get_first_renewal_payment_date( $subscription_product ),
			);
		}
	}

	return $calculated_carts;
}

/**
 * Gets the first subscription product in a cart.
 *
 * @param \WC_Cart $cart A given cart.
 */
function peachpay_wcs_get_subscription_in_cart( $cart ) {

	$wc_cart = $cart->get_cart();

	foreach ( $wc_cart as $wc_line_item ) {
		if ( $wc_line_item['data']->get_type() === 'subscription' ) {
			return $wc_line_item['data'];
		}
	}
}

/**
 * Adds WCS subscription meta data to subscription product line items.
 * Primarily used to add useful subscription order meta when processing checkout.
 *
 * @param WC_Order_Item_Product $item line item object.
 */
function peachpay_wcs_add_checkout_order_line_item_data( $item ) {
	if ( $item->get_product()->get_type() === 'subscription' ) {
		$item->add_meta_data(
			'subscription',
			array(
				'length'          => WC_Subscriptions_Product::get_length( $item->get_product() ),
				'period'          => WC_Subscriptions_Product::get_period( $item->get_product() ),
				'period_interval' => WC_Subscriptions_Product::get_interval( $item->get_product() ),
				'first_renewal'   => WC_Subscriptions_Product::get_first_renewal_payment_date( $item->get_product() ),
			)
		);
		$item->add_meta_data(
			'trial',
			array(
				'trial_length'     => WC_Subscriptions_Product::get_trial_length( $item->get_product() ),
				'trial_period'     => WC_Subscriptions_Product::get_trial_period( $item->get_product() ),
				'trial_expiration' => WC_Subscriptions_Product::get_trial_expiration_date( $item->get_product() ),
			)
		);
	}
}

/**
 * Checks whether the order has a subscription
 *
 * @param WC_Order $order .
 */
function peachpay_wcs_order_has_subscription( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$items = $order->get_items();
	if ( $items ) {
		foreach ( $items as $item ) {
			$product_id = $item->get_data()['product_id'];

			if ( $product_id ) {
				$product = new WC_Product( $product_id );
				$length  = $product->get_meta( '_subscription_price' );
				if ( $length ) {
					return true;
				}
			}
		}
	}

	return false;
}
