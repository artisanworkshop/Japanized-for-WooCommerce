<?php
/**
 * PeachPay Square Cashapp Gateway
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Cashapp payment gateway for Square.
 */
class PeachPay_Square_Cashapp_Gateway extends PeachPay_Square_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                = 'peachpay_square_cashapp';
		$this->icons             = array(
			'full'  => array(
				'color' => PeachPay::get_asset_url( 'img/marks/cashapp-green.svg' ),
			),
			'small' => array(
				'white' => PeachPay::get_asset_url( 'img/marks/square/cashapp-small-white.svg' ),
				'color' => PeachPay::get_asset_url( 'img/marks/square/cashapp-small-color.svg' ),
			),
		);
		$this->settings_priority = 5;

		// Customer facing title and description.
		$this->title = __( 'Cash App', 'peachpay-for-woocommerce' );
		// translators: %s Button text name.
		$this->description           = __( 'Press the Cash App Pay button above to link your account.', 'peachpay-for-woocommerce' );
		$this->currencies            = array( 'USD' );
		$this->countries             = array( 'US' );
		$this->payment_method_family = __( 'Digital wallet', 'peachpay-for-woocommerce' );

		parent::__construct();
	}

	/**
	 * Renders the Payment method form.
	 */
	public function payment_method_form() {
		?>
			<style>
				.peachpay-cashapp-styling {
					width: fit-content;
					padding: 1rem 0 1rem 0;
					animation: scale-up 2s ease-in-out;
					animation-iteration-count: infinite;
				}
			</style>
			<div>
				<?php $this->display_fallback_currency_option_message(); ?>
				<div class="peachpay-cashapp-styling" id="pp-square-cashapp-element"></div>
				<?php if ( $this->description ) : ?>
					<p style="text-align: left; margin: 0; font-size: smaller;" class="muted">
						<?php
						if ( ! isset( $this->order_button_text ) ) {
							$this->order_button_text = __( 'Place order', 'peachpay-for-woocommerce' );
						}
                        // PHPCS:ignore
                        echo sprintf( $this->description, "<b>$this->order_button_text</b>" );
						?>
					<p>
				<?php endif; ?>
			</div>
		<?php
	}
}
