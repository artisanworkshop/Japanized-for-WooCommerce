<?php
/**
 * PeachPay Stripe ApplePay gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
/**
 * .
 */
class PeachPay_Stripe_Applepay_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_applepay';
		$this->stripe_payment_method_type            = 'card';
		$this->stripe_payment_method_capability_type = 'card';
		$this->icons                                 = array(
			'full'  => array(
				'white' => PeachPay::get_asset_url( 'img/marks/applepay-full.svg' ),
			),
			'small' => array(
				'white' => PeachPay::get_asset_url( 'img/marks/applepay-small-white.svg' ),
				'color' => PeachPay::get_asset_url( 'img/marks/applepay-small-color.svg' ),
			),
		);
		$this->settings_priority                     = 1;

		// Customer facing title and description.
		$this->title = 'Apple Pay';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s a prompt will appear to complete your payment.', 'peachpay-for-woocommerce' );

		$this->payment_method_family = __( 'Digital wallet', 'peachpay-for-woocommerce' );

		$this->form_fields = self::capture_method_setting( $this->form_fields );
		$this->form_fields = $this->reset_domain_registration_setting( $this->form_fields );

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
	 * Checks whether Stripe ApplePay requires setup.
	 */
	public function needs_setup() {
		return parent::needs_setup() || ! peachpay_stripe_apple_pay_domain_registered();
	}

	/**
	 * Stripe ApplePay needs the domain registered. This renders the template needed to perform that
	 * action if automatic registration fails.
	 */
	protected function action_needed_form() {
		parent::action_needed_form();

		$gateway = $this;

		if ( PeachPay_Stripe_Integration::connected() ) {
			if ( ! peachpay_stripe_apple_pay_domain_registered() && PeachPay_Stripe_Integration::is_capable( 'card_payments' ) ) {
				require PeachPay::get_plugin_path() . '/core/admin/views/html-applepay-register-domain.php';
			}
		}
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

		$title = "$brand_full_name[$brand] ending with $last4 (Apple Pay)";

		$order->set_payment_method_title( $title );
	}

	/**
	 * Adds a setting to reset the apple pay domain registration.
	 *
	 * @param array $form_fields The existing gateway settings.
	 */
	protected function reset_domain_registration_setting( $form_fields ) {

		if ( ! peachpay_stripe_apple_pay_domain_registered() && PeachPay_Stripe_Integration::is_capable( 'card_payments' ) ) {
			return $form_fields;
		}

		return array_merge(
			$form_fields,
			array(
				'reset_apple_domain' => array(
					'type'              => 'hidden',
					'title'             => __( 'Reset domain registration', 'peachpay-for-woocommerce' ),
					'description'       => __( 'Apple Pay requires your domain to be registered with Apple. If you are encountering issues with Apple Pay or recently changed your domain, resetting the domain registration might fix the problem.', 'peachpay-for-woocommerce' ),
					'class'             => 'peachpay-applepay-reset',
					'custom_attributes' => array(
						'data-gateway' => $this->id,
					),
				),
			)
		);
	}
}
