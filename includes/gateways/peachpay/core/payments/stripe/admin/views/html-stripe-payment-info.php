<?php
/**
 * PeachPay Stripe order payment info.
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

			<?php if ( null !== PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'id' ) ) { ?>

				<h3><?php esc_html_e( 'Payment Intent', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php echo esc_html( ucfirst( PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'mode' ) ) ); ?>
					</dd>

					<dt><?php esc_html_e( 'Status', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'status' ) ); ?></dd>

					<dt><?php esc_html_e( 'Payment Intent', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php
						PeachPay_Stripe::dashboard_url(
							PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'mode' ),
							null,
							'payments/' . PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'id' ),
							PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'id' )
						);
						?>
					</dd>

					<dt><?php esc_html_e( 'Customer', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php
						PeachPay_Stripe::dashboard_url(
							PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'mode' ),
							null,
							'customers/' . PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'customer' ),
							PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'customer' )
						);
						?>
					</dd>
				</dl>

				<hr>

			<?php } ?>


			<?php if ( null !== PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' ) ) { ?>
				<h3><?php esc_html_e( 'Payment Method', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( ucfirst( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'mode' ) ) ); ?></dd>

					<dt><?php esc_html_e( 'Type', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'type' ) ); ?></dd>

					<dt><?php esc_html_e( 'Payment Method', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'id' ) ); ?></dd>
					<?php
					if ( PeachPay_Stripe_Order_Data::get_payment_method( $order, 'customer' ) ) {
						?>
						<dt><?php esc_html_e( 'Customer', 'peachpay-for-woocommerce' ); ?></dt>
						<dd>
							<?php
							PeachPay_Stripe::dashboard_url(
								PeachPay_Stripe_Order_Data::get_payment_method( $order, 'mode' ),
								null,
								'customers/' . PeachPay_Stripe_Order_Data::get_payment_method( $order, 'customer' ),
								PeachPay_Stripe_Order_Data::get_payment_method( $order, 'customer' )
							);
							?>
						</dd>
						<?php
					}
					?>
				</dl>
			<?php } ?>

			<?php if ( null !== PeachPay_Stripe_Order_Data::get_charge( $order, 'id' ) ) { ?>
				<hr>
				<h3><?php esc_html_e( 'Charge', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Charge', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php
						PeachPay_Stripe::dashboard_url(
							PeachPay_Stripe_Order_Data::get_charge( $order, 'mode' ),
							null,
							'payments/' . PeachPay_Stripe_Order_Data::get_charge( $order, 'id' ),
							PeachPay_Stripe_Order_Data::get_charge( $order, 'id' )
						);
						?>
					</dd>
					<?php
					$balance_transaction = PeachPay_Stripe_Order_Data::get_charge( $order, 'balance_transaction' );
					if ( null !== $balance_transaction ) {
						?>
						<dt><?php esc_html_e( 'Amount', 'peachpay-for-woocommerce' ); ?></dt>
						<dd>
							<?php
							$amount = PeachPay_Stripe::display_amount( $balance_transaction['net'] + $balance_transaction['fee'], strtoupper( $balance_transaction['currency'] ) );
                            // PHPCS:ignore
                            echo wc_price( $amount, array( 'currency' =>  strtoupper( $balance_transaction['currency'] ) ) );
							?>
						</dd>
						<dt><?php esc_html_e( 'Refunds', 'peachpay-for-woocommerce' ); ?></dt>
						<dd>
							<?php
							$refunds = PeachPay_Stripe::display_amount( -PeachPay_Stripe_Order_Data::total_refunds( $order ), strtoupper( $balance_transaction['currency'] ) );
                            // PHPCS:ignore
                            echo wc_price( $refunds, array( 'currency' =>  strtoupper( $balance_transaction['currency'] ) ) );
							?>
						</dd>
						<dt><?php esc_html_e( 'Fee', 'peachpay-for-woocommerce' ); ?></dt>
						<dd>
							<?php
							$fee = PeachPay_Stripe::display_amount( PeachPay_Stripe_Order_Data::total_fees( $order ), strtoupper( $balance_transaction['currency'] ) );
                            // PHPCS:ignore
                            echo wc_price( $fee, array( 'currency' =>  strtoupper( $balance_transaction['currency'] ) ) );
							?>
						</dd>
						<hr>
						<dt><?php esc_html_e( 'Net', 'peachpay-for-woocommerce' ); ?></dt>
						<dd>
							<?php
							$net = PeachPay_Stripe::display_amount( PeachPay_Stripe_Order_Data::total_payout( $order ), strtoupper( $balance_transaction['currency'] ) );
                            // PHPCS:ignore
                            echo wc_price($net, array( 'currency' => strtoupper($balance_transaction['currency']) ) );
							?>
						</dd>
					<?php } ?>
				</dl>
			<?php } ?>
		</div>
	</div>
</div>
