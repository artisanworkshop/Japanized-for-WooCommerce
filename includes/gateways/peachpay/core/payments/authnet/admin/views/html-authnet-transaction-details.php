<?php
/**
 * PeachPay Authnet transaction details info template.
 *
 * @var WC_Order $order
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

			<?php if ( null !== PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' ) ) { ?>

				<h3><?php esc_html_e( 'Payment', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php echo esc_html( ucfirst( PeachPay_Authnet_Order_Data::get_peachpay( $order, 'authnet_mode' ) ) ); ?>
					</dd>

					<dt><?php esc_html_e( 'Transaction', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php echo esc_html( ucfirst( PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' ) ) ); ?>
					</dd>

					<dt><?php esc_html_e( 'Status', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' ) ); ?></dd>

					<dt><?php esc_html_e( 'Authorized amount', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php
						$auth_amount = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'authAmount' );
                        // PHPCS:ignore
                        echo wc_price( $auth_amount, array( 'currency' => $order->get_currency() ) );
						?>
					</dd>

					<dt><?php esc_html_e( 'Settlement amount', 'peachpay-for-woocommerce' ); ?></dt>
					<dd>
						<?php
						$settle_amount = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'settleAmount' );
                        // PHPCS:ignore
                        echo wc_price( $settle_amount, array( 'currency' => $order->get_currency() ) );
						?>
					</dd>
				</dl>

				<hr>
			<?php } ?>


			<?php if ( null !== PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' ) ) { ?>
				<h3><?php esc_html_e( 'Payment Method', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( ucfirst( PeachPay_Authnet_Order_Data::get_peachpay( $order, 'authnet_mode' ) ) ); ?></dd>


					<dt><?php esc_html_e( 'Type', 'peachpay-for-woocommerce' ); ?></dt>
					<?php
					$payment = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'payment' );
					if ( isset( $payment['creditCard'] ) && null !== $payment['creditCard'] ) {
						?>
						<dd><?php esc_html_e( 'card', 'peachpay-for-woocommerce' ); ?></dd>

						<dt><?php esc_html_e( 'Card Brand', 'peachpay-for-woocommerce' ); ?></dt>
						<dd><?php echo esc_html( $payment['creditCard']['cardType'] ); ?></dd>

						<dt><?php esc_html_e( 'Last 4 digits', 'peachpay-for-woocommerce' ); ?></dt>
						<dd><?php echo esc_html( $payment['creditCard']['cardNumber'] ); ?></dd>
						<?php
					} elseif ( isset( $payment['bankAccount'] ) && null !== $payment['bankAccount'] ) {
						?>
						<dd><?php esc_html_e( 'echeck', 'peachpay-for-woocommerce' ); ?></dd>

						<dt><?php esc_html_e( 'eCheck Type', 'peachpay-for-woocommerce' ); ?></dt>
						<dd><?php echo esc_html( $payment['bankAccount']['echeckType'] ); ?></dd>

						<dt><?php esc_html_e( 'Last 4 digits', 'peachpay-for-woocommerce' ); ?></dt>
						<dd><?php echo esc_html( $payment['bankAccount']['accountNumber'] ); ?></dd>
						<?php
					}
					?>
				</dl>
			<?php } ?>
		</div>
	</div>
</div>
