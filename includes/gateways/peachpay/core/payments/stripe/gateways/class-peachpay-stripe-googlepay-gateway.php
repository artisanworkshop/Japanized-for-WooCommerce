<?php
/**
 * PeachPay Stripe Google Pay gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
/**
 * .
 */
class PeachPay_Stripe_Googlepay_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_googlepay';
		$this->stripe_payment_method_type            = 'card';
		$this->stripe_payment_method_capability_type = 'card';
		$this->icon                                  = PeachPay::get_asset_url( 'img/marks/google-pay.svg' );
		$this->settings_priority                     = 1;

		// Customer facing title and description.
		$this->title = 'Google Pay';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s a prompt will appear to complete your payment.', 'peachpay-for-woocommerce' );

		$this->payment_method_family = __( 'Digital wallet', 'peachpay-for-woocommerce' );

		$this->form_fields = self::capture_method_setting( $this->form_fields );

		$this->supports = array(
			'products',
			'subscriptions',
			'multiple_subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
		);

		parent::__construct();
	}

	/**
	 * Confirm payment immediately
	 */
	protected function confirm_payment() {
		return true;
	}

	/**
	 * Gets the formatted payment method title for an order.
	 *
	 * @param WC_Order $order The order to get the payment method title for.
	 */
	public static function set_payment_method_title( $order ) {
		$payment_method_id   = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' );
		$payment_method_type = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'type' );
		if ( ! $payment_method_id || 'card' !== $payment_method_type ) {
			return;
		}

		$brand_full_name = array(
			'amex'       => 'American Express',
			'diners'     => 'Diners Club',
			'discover'   => 'Discover',
			'jcb'        => 'JCB',
			'mastercard' => 'Mastercard',
			'unionpay'   => 'UnionPay',
			'visa'       => 'Visa',
			'unknown'    => 'Card',
		);
		$brand           = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['brand'];
		$last4           = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['last4'];

		if ( ! $brand || ! $last4 || ! isset( $brand_full_name[ $brand ] ) ) {
			return;
		}

		$title = "$brand_full_name[$brand] ending with $last4 (Google Pay)";

		$order->set_payment_method_title( $title );
	}
}
