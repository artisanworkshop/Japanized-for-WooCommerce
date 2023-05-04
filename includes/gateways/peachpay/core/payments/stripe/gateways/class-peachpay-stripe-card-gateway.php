<?php
/**
 * PeachPay Stripe Credit / Debit card gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
/**
 * .
 */
class PeachPay_Stripe_Card_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_card';
		$this->stripe_payment_method_type            = 'card';
		$this->stripe_payment_method_capability_type = 'card';
		$this->icon                                  = PeachPay::get_asset_url( 'img/marks/cc-quad.svg' );
		$this->settings_priority                     = 0;

		$this->title                 = 'Credit / Debit card';
		$this->description           = 'Pay securely using your credit or debit card.';
		$this->payment_method_family = __( 'Card', 'peachpay-for-woocommerce' );

		$this->form_fields = self::capture_method_setting( $this->form_fields );
		$this->form_fields = self::statement_descriptor_suffix_setting( $this->form_fields );
		$this->form_fields = self::force_3d_secure_setting( $this->form_fields );

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
	 * Adds the statement descriptor suffix setting to the gateway settings.
	 *
	 * @param array $form_fields The existing gateway settings.
	 */
	private static function statement_descriptor_suffix_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'statement_descriptor_suffix' => array(
					'type'              => 'text',
					'title'             => __( 'Statement descriptor suffix', 'peachpay-for-woocommerce' ),
					'description'       => __( 'Adds a dynamic suffix to the transaction statement descriptor. The complete statement descriptor cannot exceed 22 characters and will be truncated if exceeded.', 'peachpay-for-woocommerce' ),
					'default'           => '',
					'custom_attributes' => array( 'max' => 22 ),
				),
			)
		);
	}

	/**
	 * Adds the force 3d secure setting to the gateway settings.
	 *
	 * @param array $form_fields The existing gateway settings.
	 */
	private static function force_3d_secure_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'payment_method_options__card__request_three_d_secure' => array(
					'type'  => 'checkbox',
					'title' => __( 'Force 3D Secure', 'peachpay-for-woocommerce' ),
					'label' => __( 'Stripe normally determines when 3D Secure should be shown automatically. If "Force 3D Secure" is enabled, 3D Secure will be shown for all card transactions. In test mode, 3D Secure is only shown for 3DS test cards regardless of the setting.', 'peachpay-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Renders payment fields.
	 */
	public function payment_method_form() {
		?>
			<div>
				<p style="text-align: left; margin: 0;">
					<?php echo esc_html( $this->description ); ?>
				<p>
				<div id="pp-stripe-card-element" >
					<div class="card-line">
						<div class="card-line-brand">
							<div id="pp-stripe-card-brand">
								<img src="<?php echo esc_url( PeachPay::get_asset_url( 'img/marks/credit-card-regular.svg' ) ); ?>" alt="">
							</div>
						</div>
						<div class="card-line-number">
							<div id="pp-stripe-card-number">
								<div class="loading-blur"></div>
							</div>
						</div>
					</div>
					<div class="card-line">
						<div class="card-line-half">
							<div id="pp-stripe-card-expiry">
								<div class="loading-blur"></div>
							</div>
						</div>
						<div class="card-line-half">
							<div id="pp-stripe-card-cvc">
								<div class="loading-blur"></div>
							</div>
						</div>
					</div>
				</div>
				<div id="pp-stripe-card-status">
					<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path d="M11 17H13V11H11V17ZM12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM11 9H13V7H11V9Z" />
						<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20Z"/>
					</svg>
					<span></span>
				</div>
			</div>
		<?php
	}

	/**
	 * Adds a Stripe card payment method to the gateway.
	 *
	 * @param WC_Order $order The WC order.
	 *
	 * @return WC_Payment_Token_PeachPay_Stripe_Card|null
	 */
	public function create_payment_token( $order ) {
		$payment_method_id   = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' );
		$payment_method_type = PeachPay_Stripe_Order_Data::get_payment_method( $order, 'type' );
		if ( ! $payment_method_id || 'card' !== $payment_method_type ) {
			return null;
		}

		$token = new WC_Payment_Token_PeachPay_Stripe_Card();

		$token->set_gateway_id( $this->id );
		$token->set_user_id( get_current_user_id() );

		$token->set_token( $payment_method_id );
		$token->set_card_type( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['brand'] );
		$token->set_last4( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['last4'] );
		$token->set_expiry_month( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['exp_month'] );
		$token->set_expiry_year( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'data' )['exp_year'] );
		$token->set_mode( PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'mode' ) );
		$token->set_connect_id( PeachPay_Stripe_Integration::connect_id() );

		$token->save();

		WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );

		return $token;
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

		$title = "$brand_full_name[$brand] ending with $last4";

		$order->set_payment_method_title( $title );
	}
}
