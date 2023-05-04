<?php
/**
 * PeachPay Stripe capture/void un-captured payments.
 *
 * @var number $amount_capturable The capturable amount.
 * @var WC_Order $order The order to display capture details about.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

$amount_capturable = PeachPay_Stripe::display_amount( $amount_capturable, $order->get_currency() );

?>
<div class="peachpay col">
	<h3><?php esc_html_e( 'Capture Payment', 'peachpay-for-woocommerce' ); ?></h3>
	<p style="margin: 0;">
		<?php
		esc_html_e( 'Authorized amount: ', 'peachpay-for-woocommerce' );
        // PHPCS:ignore
        echo wc_price( $amount_capturable );
		?>
	</p>
	<div id="pp-stripe-actions" class="row" style="margin-top: 0.2rem;">
		<div style="white-space: nowrap;">
			<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" >
			<?php echo esc_html( get_woocommerce_currency_symbol( $order->get_currency() ) ); ?>
			<input type="number" name="amount" value="<?php echo esc_attr( $amount_capturable ); ?>" max="<?php echo esc_attr( $amount_capturable ); ?>" min="1" step='0.01' style="width: 100px;">
		</div>

		<button class="button" type="button" name="capture" value="1" style="width: 75px;"><?php esc_html_e( 'Capture', 'peachpay-for-woocommerce' ); ?></button>
		<button class="button" type="button" name="void" value="1" style="width: 50px;" ><?php esc_html_e( 'Void', 'peachpay-for-woocommerce' ); ?></button>
	</div>
	<span class="error-message" style="font-size: smaller;"></span>
</div>
