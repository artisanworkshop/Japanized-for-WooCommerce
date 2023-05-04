<?php
/**
 * PeachPay Stripe ACH debit(US bank account) gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


/**
 * .
 */
class PeachPay_Stripe_AchDebit_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_achdebit';
		$this->stripe_payment_method_type            = 'us_bank_account';
		$this->stripe_payment_method_capability_type = 'us_bank_account_ach';
		$this->icon                                  = PeachPay::get_asset_url( 'img/marks/bank.svg' );
		$this->settings_priority                     = 6;

		// Customer facing title and description.
		$this->title = 'US bank account';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s a prompt will appear to complete your payment.', 'peachpay-for-woocommerce' );

		$this->currencies            = array( 'USD' );
		$this->countries             = array( 'US' );
		$this->payment_method_family = __( 'Bank debit', 'peachpay-for-woocommerce' );

		$this->supports = array(
			'products',
			'tokenization',
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
	 * Information about the ACH mandate.
	 *
	 * @param WC_Order $order The WC order to create the mandate data for.
	 * @param string   $type The type of mandate.
	 */
	protected function mandate_data( $order, $type = 'online' ) {
		if ( 'online' === $type ) {
			return array(
				'customer_acceptance' => array(
					'type'   => 'online',
					'online' => array(
						'ip_address' => $this->get_customer_ip( $order ),
						'user_agent' => $this->get_customer_user_agent( $order ),
					),
				),
			);
		} else {
			return array(
				'customer_acceptance' => array(
					'type'    => 'offline',
					'offline' => new stdClass(),
				),
			);
		}
	}

	/**
	 * Adds a Stripe ACH payment method to the gateway.
	 *
	 * @param WC_Order $order The WC order.
	 */
	public function create_payment_token( $order ) {
		if ( null === PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' )
			|| 'us_bank_account' !== PeachPay_Stripe_Order_Data::get_payment_method( $order, 'type' )
			|| null === PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' ) ) {
			return;
		}
		$token = new WC_Payment_Token_PeachPay_Stripe_Achdebit();

		$token->set_gateway_id( $this->id );
		$token->set_user_id( get_current_user_id() );

		$token->set_token( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' ) );
		$token->set_bank( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['bank_name'] );
		$token->set_last4( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['last4'] );
		$token->set_mode( PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'mode' ) );
		$token->set_connect_id( PeachPay_Stripe_Integration::connect_id() );

		$token->save();

		WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );
	}

	/**
	 * Gets the formated payment method title for an order.
	 *
	 * @param WC_Order $order The order to get the payment method title for.
	 */
	public static function set_payment_method_title( $order ) {
		if ( null === PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' )
		|| 'us_bank_account' !== PeachPay_Stripe_Order_Data::get_payment_method( $order, 'type' )
		|| null === PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )
		) {
			return;
		}

		$bank  = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['bank_name'];
		$last4 = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['last4'];

		if ( ! $bank || ! $last4 ) {
			return;
		}

		$title = "ACH: $bank ending in $last4";

		$order->set_payment_method_title( $title );
		$order->save();
	}
}
