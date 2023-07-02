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
class PeachPay_Stripe_SepaDebit_Gateway extends PeachPay_Stripe_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_sepadebit';
		$this->stripe_payment_method_type            = 'sepa_debit';
		$this->stripe_payment_method_capability_type = 'sepa_debit';
		$this->icons                                 = array(
			'full'  => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/us_banks-full.svg' ),
			),
			'small' => array(
				'clear' => PeachPay::get_asset_url( 'img/marks/us_banks-small.svg' ),
			),
		);
		$this->settings_priority                     = 12;

		// Customer facing title and description.
		$this->title = 'SEPA Direct Debit';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s, fill out the IBAN input, accept the conditions and pay.', 'peachpay-for-woocommerce' );

		$this->currencies = array( 'EUR' );
		$this->countries  = array(
			'FI',
			'LV',
			'PT',
			'AT',
			'FR',
			'LI',
			'RO',
			'BE',
			'DE',
			'LT',
			'BG',
			'GI',
			'LU',
			'SK',
			'GR',
			'MT',
			'SI',
			'HR',
			'ES',
			'CY',
			'HU',
			'NL',
			'SE',
			'CZ',
			'IE',
			'CH',
			'DK',
			'IT',
			'NO',
			'GB',
			'EE',
			'PL',
			'US',
			'CA',
			'NZ',
			'SG',
			'HK',
			'JP',
			'AU',
			'MX',
		);

		$this->payment_method_family = __( 'Bank debit', 'peachpay-for-woocommerce' );

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
	 * Information about the SEPA Direct Debit mandate.
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
	 * Hook into peachpay's native checkout data to add required order data for the order-pay page
	 *
	 * @param Array $native_checkout_data array to add to.
	 */
	public function add_order_pay_details( $native_checkout_data ) {
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			if ( $order instanceof WC_Order ) {
				$native_checkout_data['order_pay_details']['billing_email']      = $order->get_billing_email();
				$native_checkout_data['order_pay_details']['billing_phone']      = $order->get_billing_phone();
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

			if ( ! $order instanceof WC_Order || ! $order->get_billing_first_name() || ! $order->get_billing_last_name() || ! $order->get_billing_email() ) {
				$available = false;
			}
		}

		return $available;
	}

	/**
	 * Renders payment fields.
	 */
	public function payment_method_form() {
		// translators: %s The merchant's store name.
		$description = __(
			'By providing your IBAN and confirming this payment, you are authorizing %s and Stripe,
			our payment service provider, to send instructions to your bank to debit your account in accordance with those instructions.
			You are entitled to a refund from your bank under the terms and conditions of your agreement with your bank.
			A refund must be claimed within 8 weeks starting from the date on which your account was debited.',
			'peachpay-for-woocommerce'
		);

		?>
			<div>
			<?php $this->display_fallback_currency_option_message(); ?>
			<div id="pp-stripe-sepadebit-element">
				<hr>
				<p style="font-size: smaller; text-align: justify;">
					<?php echo sprintf( esc_html( $description ), '<b>' . esc_html( get_bloginfo( 'name' ) ) . '</b>' ); ?>
				</p>
			</div></div>
		<?php
	}
}
