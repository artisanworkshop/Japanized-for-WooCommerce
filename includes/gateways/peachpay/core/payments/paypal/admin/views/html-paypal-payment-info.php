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

<div class="peachpay">
	<div>
		<h3><?php esc_html_e( 'Transaction details', 'peachpay-for-woocommerce' ); ?></h3>
		<a href="#transaction-info"><?php esc_html_e( 'View', 'peachpay-for-woocommerce' ); ?></a>
	</div>

	<div id="transaction-info" class="modal-window">
		<a href="#" title="Close" class="outside-close"> </a>

		<div>
			<h2><?php esc_html_e( 'Transaction details', 'peachpay-for-woocommerce' ); ?></h2>
			<hr>
			<a href="#" title="Close" class="modal-close"><?php esc_html_e( 'Close', 'peachpay-for-woocommerce' ); ?></a>

			<?php if ( null !== PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'id' ) ) { ?>
				<h3><?php esc_html_e( 'PayPal Order', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( ucfirst( PeachPay_PayPal_Order_Data::get_peachpay( $order, 'paypal_mode' ) ) ); ?></dd>

					<dt><?php esc_html_e( 'Status', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( ucfirst( strtolower( PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'status' ) ) ) ); ?></dd>

					<dt><?php esc_html_e( 'PayPal Order', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_PayPal_Order_Data::get_order_transaction_details( $order, 'id' ) ); ?></dd>
				</dl>
			<?php } ?>

			<?php
			$capture_currency  = PeachPay_PayPal_Order_Data::get_capture_amount_currency( $order );
			$captured_payments = PeachPay_PayPal_Order_Data::get_captured_payments( $order );

			if ( is_array( $captured_payments ) && 0 < count( $captured_payments ) ) {
				?>
				<hr>
				<h3><?php esc_html_e( 'Capture Summary', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Capture', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
					<?php
					$captured_payment_ids = array_map(
						function( $payment ) use ( $order ) {
							ob_start();
							PeachPay_PayPal::dashboard_url(
								PeachPay_PayPal_Order_Data::get_peachpay( $order, 'paypal_mode' ),
								'activity/payment',
								$payment['id'],
								true
							);

							return ob_get_clean();
						},
						$captured_payments
					);

                    // PHPCS:ignore
					echo implode( ',', $captured_payment_ids );
					?>
					</dd>

					<dt><?php esc_html_e( 'Amount', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
					<?php
						$amount = PeachPay_PayPal_Order_Data::get_gross_sum( $order );
                        //PHPCS:ignore
                        echo wc_price( $amount, array( 'currency' => $capture_currency) );
					?>
					</dd>

					<dt><?php esc_html_e( 'Refunds', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
					<?php
						$refunds = PeachPay_PayPal_Order_Data::get_refunded_sum( $order );
                        //PHPCS:ignore
                        echo wc_price( $refunds, array( 'currency' => $capture_currency ) );
					?>
					</dd>

					<dt><?php esc_html_e( 'Fee', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
					<?php
						$fees = PeachPay_PayPal_Order_Data::get_fees_sum( $order );
                        //PHPCS:ignore
                        echo wc_price( $fees, array( 'currency' => $capture_currency) );
					?>
					</dd>

					<dt><?php esc_html_e( 'Net', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
					<?php
						$net = PeachPay_PayPal_Order_Data::get_net_sum( $order );
                        //PHPCS:ignore
                        echo wc_price( $net, array( 'currency' => $capture_currency ) );
					?>
					</dd>
				</dl>
			<?php } ?>
		</div>
	</div>
</div>
