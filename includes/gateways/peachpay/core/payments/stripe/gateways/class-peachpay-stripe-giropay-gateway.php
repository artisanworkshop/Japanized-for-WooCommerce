<?php
/**
 * PeachPay Stripe Giropay gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
/**
 * .
 */
class PeachPay_Stripe_Giropay_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_giropay';
		$this->stripe_payment_method_type            = 'giropay';
		$this->stripe_payment_method_capability_type = 'giropay';
		$this->icon                                  = PeachPay::get_asset_url( 'img/marks/giropay.svg' );
		$this->settings_priority                     = 8;

		// Customer facing title and description.
		$this->title = 'giropay';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );

		$this->countries             = array( 'DE' );
		$this->currencies            = array( 'EUR' );
		$this->min_max_currency      = 'EUR';
		$this->payment_method_family = __( 'Authenticated bank debit', 'peachpay-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Setup future settings for payment intent.
	 */
	protected function setup_future_usage() {
		return null;
	}

	/**
	 * .
	 */
	public function hooks() {
		add_filter( 'peachpay_native_checkout_data', array( $this, 'add_order_pay_details' ), 10, 1 );
		parent::hooks();
	}

	/**
	 * Hook into peachpay's native checkout data to add required order data for the order-pay page
	 *
	 * @param Array $native_checkout_data array to add to.
	 */
	public function add_order_pay_details( $native_checkout_data ) {
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			if ( $order instanceof WC_Order ) {
				$native_checkout_data['order_pay_details']['billing_first_name'] = $order->get_billing_first_name();
				$native_checkout_data['order_pay_details']['billing_last_name']  = $order->get_billing_last_name();
			}
		}
		return $native_checkout_data;
	}

	/**
	 * .
	 */
	public function is_available() {
		$available = parent::is_available();

		if ( $available && is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order || ! $order->get_billing_first_name() || ! $order->get_billing_last_name() ) {
				$available = false;
			}
		}

		return $available;
	}
}
