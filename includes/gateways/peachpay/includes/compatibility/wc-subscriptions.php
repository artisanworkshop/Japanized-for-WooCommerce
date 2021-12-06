<?php
/**
 * Support for the Woocommerce-Subscriptions Plugin Actions
 * Plugin: https://woocommerce.com/products/woocommerce-subscriptions/
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intilizes WC Subscription support.
 */
function peachpay_wcs_init() {
	add_action( 'woocommerce_scheduled_subscription_payment_peachpay', 'peachpay_wcs_scheduled_payment_peachpay', 10, 2 );
	add_filter( 'peachpay_cart_page_line_item', 'peachpay_wcs_add_cart_item_meta', 10, 2 );
	add_filter( 'peachpay_product_page_line_item', 'peachpay_wcs_add_product_page_item_meta', 10, 2 );
	add_filter( 'peachpay_calculate_carts', 'peachpay_wcs_calculate_recurring_carts', 10, 1 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_wcs_init' );


/**
 * This is fired for every renewal that was initially paid for with peachpay.
 *
 * @since 1.44.0
 * @param float    $renewal_total The order total.
 * @param WC_Order $renewal_order The order to pay for.
 * @return void
 */
function peachpay_wcs_scheduled_payment_peachpay( float $renewal_total, WC_Order $renewal_order ) {
	$subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
	$subscription  = array_pop( $subscriptions );

	$endpoint = peachpay_api_url() . 'api/v1/subscription-renewal';

	$body    = array(
		'merchant-url'  => wp_parse_url( get_site_url() )['host'],
		'merchant-name' => get_bloginfo( 'name' ),
		'amount'        => $renewal_total,
		'payment'       => array(
			'method' => 'stripe',
			'id'     => peachpay_get_stripe_order_payment_meta( $subscription->get_parent_id() ),
		),
		'wc-order'      => $renewal_order->get_data(),
	);
	$options = array(
		'headers' => array(
			'Content-Type' => 'application/json',
		),
		'body'    => wp_json_encode( $body ),
	);

	$response = wp_remote_post( $endpoint, $options );

	if ( is_wp_error( $response ) ) {
		$renewal_order->update_status( 'failed', 'Peachpay scheduled renewal payment: ' . $response->get_error_message() );
	} else {

		if ( wp_remote_retrieve_body( $response ) === 'Success' ) {
			$renewal_order->payment_complete();
		} else {
			$renewal_order->update_status( 'failed', 'Peachpay scheduled renewal payment: ' . wp_remote_retrieve_body( $response ) );
		}
	}
}


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
 * Adds any needed meta data to a peachpay line item if it is a subscription on the product page
 *
 * @param array      $pp_cart_item The item to add meta details related to subscriptions.
 * @param WC_Product $product   The WC Product object to source details from.
 */
function peachpay_wcs_add_product_page_item_meta( array $pp_cart_item, WC_Product $product ) {
	return peachpay_wcs_add_cart_item_meta( $pp_cart_item, array( 'data' => $product ) );
}

/**
 * Calculates and gathers totals for recurring carts.
 *
 * @param array $calculated_carts Carts calculated to be shown in the peachpay modal.
 */
function peachpay_wcs_calculate_recurring_carts( $calculated_carts ) {
	WC_Subscriptions_Cart::calculate_subscription_totals( WC()->cart->get_total(), WC()->cart );

	foreach ( WC()->cart->recurring_carts as $key => $cart ) {
		$calculated_carts[ $key ] = peachpay_build_cart_response( $key, $cart );

		$subscription_product                                  = peachpay_wcs_get_subscription_in_cart( $cart );
		$calculated_carts[ $key ]['cart_meta']['subscription'] = array(
			'length'          => WC_Subscriptions_Product::get_length( $subscription_product ),
			'period'          => WC_Subscriptions_Product::get_period( $subscription_product ),
			'period_interval' => WC_Subscriptions_Product::get_interval( $subscription_product ),
			'first_renewal'   => WC_Subscriptions_Product::get_first_renewal_payment_date( $subscription_product ),
		);
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
