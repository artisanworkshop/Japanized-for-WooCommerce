<?php
/**
 * COD Gateway for WooCommerce Subscriptions
 *
 * This file contains the class that extends the COD payment gateway to work with WooCommerce Subscriptions.
 *
 * @package     Japanized-for-WooCommerce
 * @version     2.2.17
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * COD Gateway for subscriptions.
 *
 * @class       WC_Addons_Gateway_COD2
 * @extends     WC_Gateway_COD2
 * @since       2.6.0
 * @version     2.2.17
 * @package     WooCommerce/Classes/Payment
 * @author      Artisan Workshop
 */
class WC_Addons_Gateway_COD2 extends WC_Gateway_COD2 {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_cod2', array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		}
	}

	/**
	 * Process the subscription.
	 *
	 * @param  WC_Order $order Order object.
	 * @return array
	 * @throws Exception When there is an error processing the subscription payment.
	 */
	protected function process_subscription( $order ) {
		$payment_response = $this->process_subscription_payment( $order, $order->get_total() );

		if ( is_wp_error( $payment_response ) ) {
			throw new Exception( esc_html( $payment_response->get_error_message() ) );
		} else {
			// Remove cart.
			WC()->cart->empty_cart();

			// Return thank you page redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}

	/**
	 * Process the payment.
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Processing subscription.
		$order_id = $order->get_id();
		if ( 'standard' === $this->mode && ( $this->order_contains_subscription( $order_id ) || ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order_id ) ) ) ) {
			return $this->process_subscription( $order );

			// Processing regular product.
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id Order ID.
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}

	/**
	 * Add process_subscription_payment function.
	 *
	 * @param WC_order $order Order object.
	 * @param int      $amount (default: 0) Amount to charge.
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order, $amount = 0 ) {
		if ( 0 === $amount ) {
			// Payment complete.
			$order->payment_complete();

			return true;
		}

		// Mark as processing or on-hold (payment won't be taken until delivery).
		$order->update_status( 'processing', __( 'Payment to be made upon delivery.', 'woocommerce-for-japan' ) );

		// Reduce stock levels.
		wc_reduce_stock_levels( $order->get_id() );

		return true;
	}

	/**
	 * Add scheduled_subscription_payment function.
	 *
	 * @param float    $amount_to_charge The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
		$result = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			/* translators: %s: Error message from payment processing */
			$renewal_order->update_status( 'failed', sprintf( __( 'COD Failed (%s)', 'woocommerce-for-japan' ), $result->get_error_message() ) );
		}
	}
}
