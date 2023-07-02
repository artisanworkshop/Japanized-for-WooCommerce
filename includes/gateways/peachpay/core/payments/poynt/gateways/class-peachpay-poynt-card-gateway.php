<?php
/**
 * PeachPay Poynt Credit/Debit card gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
class PeachPay_Poynt_Card_Gateway extends PeachPay_Poynt_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                = 'peachpay_poynt_card';
		$this->icons             = array(
			'full'  => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/cc-quad.svg' ),
			),
			'small' => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/card-small.svg' ),
			),
		);
		$this->settings_priority = 0;

		$this->title = 'Card';
		// translators: %s Button text name.
		$this->description = __( 'Pay securely using your credit or debit card.', 'peachpay-for-woocommerce' );

		$this->payment_method_family = __( 'Card', 'peachpay-for-woocommerce' );

		$this->form_fields = self::transaction_action_setting( $this->form_fields );

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
	 * Renders payment fields.
	 */
	public function payment_method_form() {
		?>
			<div>
				<?php $this->display_fallback_currency_option_message(); ?>
				<p style="text-align: left; margin: 0;">
					<?php echo esc_html( $this->description ); ?>
				<p>
				<div id="pp-poynt-card-element"></div>
			</div>
		<?php
	}

	/**
	 * Adds a Poynt card payment method to the gateway.
	 *
	 * @param WC_Order $order The WC order.
	 *
	 * @return WC_Payment_Token_PeachPay_Poynt_Card|null
	 */
	public function create_payment_token( $order ) {
		$payment_method_token = PeachPay_Poynt_Order_Data::get_token( $order, 'token' );
		$payment_method_type  = PeachPay_Poynt_Order_Data::get_transaction( $order, 'fundingSource' )['type'];
		if ( ! $payment_method_token || 'CREDIT_DEBIT' !== $payment_method_type ) {
			return null;
		}

		$token = new WC_Payment_Token_PeachPay_Poynt_Card();

		$token->set_gateway_id( $this->id );
		$token->set_user_id( get_current_user_id() );

		$card_data = PeachPay_Poynt_Order_Data::get_transaction( $order, 'fundingSource' )['card'];

		$token->set_token( $payment_method_token );
		$token->set_card_type( $card_data['type'] );
		$token->set_last4( $card_data['numberLast4'] );
		$token->set_expiry_month( $card_data['expirationMonth'] );
		$token->set_expiry_year( $card_data['expirationYear'] );
		$token->set_mode( PeachPay_Poynt_Integration::mode() );
		$token->set_business_id( PeachPay_Poynt_Integration::business_id() );

		$token->save();

		WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );

		$order->add_payment_token( $token );
		$order->save();

		return $token;
	}
}
