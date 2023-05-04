<?php
/**
 * PeachPay Square Credit / Debit card gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * This class allows us to submit orders with the PeachPay PayPal gateway.
 */
class PeachPay_Square_Card_Gateway extends PeachPay_Square_Payment_Gateway {

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->id                       = 'peachpay_square_card';
		$this->icon                     = PeachPay::get_asset_url( 'img/marks/cc-quad.svg' );
		$this->settings_priority        = 0;
		$this->requires_verification_id = true;
		$this->payment_method_family    = __( 'Cards', 'peachpay-for-woocommerce' );

		// Customer facing title and description.
		$this->title       = __( 'Credit/Debit card', 'peachpay-for-woocommerce' );
		$this->description = __( 'Pay securely using your credit or debit card.', 'peachpay-for-woocommerce' );

		parent::__construct();
		$this->supports = array_merge(
			$this->supports,
			array(
				'tokenization',
				'subscriptions',
				'multiple_subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
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
				<div id="pp-square-card-element"></div>
			</div>
		<?php
	}

}
