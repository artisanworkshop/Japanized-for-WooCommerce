<?php
/**
 * PeachPay Stripe order payment info.
 *
 * @var array $balance_transaction
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<tr>
	<td class="label">
		<?php esc_html_e( 'Stripe Fee:', 'peachpay-for-woocommerce' ); ?>
	</td>
	<td width="1%"></td>
	<td class="total">
		<?php
		$fee = -PeachPay_Stripe::display_amount( PeachPay_Stripe_Order_Data::total_fees( $order ), strtoupper( $balance_transaction['currency'] ) );
        // PHPCS:ignore
		echo wc_price( $fee, array( 'currency' => strtoupper( $balance_transaction['currency'] ) ) );
		?>
	</td>
</tr>
<tr>
	<td class="label">
		<?php esc_html_e( 'Stripe Net Payout:', 'peachpay-for-woocommerce' ); ?>
	</td>
	<td width="1%"></td>
	<td class="total">
		<?php
		$net = PeachPay_Stripe::display_amount( PeachPay_Stripe_Order_Data::total_payout( $order ), strtoupper( $balance_transaction['currency'] ) );
        // PHPCS:ignore
		echo wc_price( $net, array( 'currency' => strtoupper( $balance_transaction['currency'] ) ) );
		?>
	</td>
</tr>
