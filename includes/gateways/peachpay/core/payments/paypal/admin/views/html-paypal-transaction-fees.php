<?php
/**
 * PeachPay PayPal order payment info.
 *
 * @var WC_Order $order
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<tr>
	<td class="label">
		<?php esc_html_e( 'PayPal Fee:', 'peachpay-for-woocommerce' ); ?>
	</td>
	<td width="1%"></td>
	<td class="total">
		<?php
		$fee = -PeachPay_PayPal_Order_Data::get_fees_sum( $order );
        // PHPCS:ignore
		echo wc_price( $fee, array( 'currency' => PeachPay_PayPal_Order_Data::get_capture_amount_currency( $order )) );
		?>
	</td>
</tr>
<tr>
	<td class="label">
		<?php esc_html_e( 'PayPal Net Payout:', 'peachpay-for-woocommerce' ); ?>
	</td>
	<td width="1%"></td>
	<td class="total">
		<?php
		$net = PeachPay_PayPal_Order_Data::get_net_sum( $order );

        // PHPCS:ignore
		echo wc_price( $net, array( 'currency' => PeachPay_PayPal_Order_Data::get_capture_amount_currency( $order ) ) );
		?>
	</td>
</tr>
