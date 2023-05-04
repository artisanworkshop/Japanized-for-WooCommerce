<?php
/**
 * PeachPay Poynt order payment info.
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

			<?php if ( null !== PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' ) ) { ?>
				<h3><?php esc_html_e( 'Transaction', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( ucfirst( PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' ) ) ); ?></dd>

					<dt><?php esc_html_e( 'Id', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' ) ); ?></dd>

					<dt><?php esc_html_e( 'Status', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' ) ); ?></dd>

					<?php
					$currency      = PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['currency'];
					$amount        = PeachPay_Poynt::display_amount( PeachPay_Poynt_Order_Data::get_transaction( $order, 'amounts' )['transactionAmount'], $currency );
					$refund_amount = PeachPay_Poynt::display_amount( PeachPay_Poynt_Order_Data::get_refund( $order, 'total' ), $currency );
					if ( 'VOIDED' === PeachPay_Poynt_Order_Data::get_transaction( $order, 'status' ) ) {
						$refund_amount = $amount;
					}
					?>
					<dt><?php esc_html_e( 'Currency', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $currency ); ?></dd>

					<dt><?php esc_html_e( 'Amount', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo wc_price( $amount, array( 'currency' => $currency ) ); //PHPCS:ignore ?></dd>

					<dt><?php esc_html_e( 'Refunds', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo wc_price( $refund_amount, array( 'currency' => $currency ) ); //PHPCS:ignore ?></dd>

				</dl>
			<?php } ?>

			<?php
			if ( null !== PeachPay_Poynt_Order_Data::get_transaction( $order, 'id' ) ) {
				$funding_source = PeachPay_Poynt_Order_Data::get_transaction( $order, 'fundingSource' );
				?>
				<hr>
				<h3><?php esc_html_e( 'Payment Method', 'peachpay-for-woocommerce' ); ?></h3>
				<dl class="inline-dl">
					<dt><?php esc_html_e( 'Mode', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( ucfirst( PeachPay_Poynt_Order_Data::get_peachpay( $order, 'poynt_mode' ) ) ); ?></dd>

					<dt><?php esc_html_e( 'Type', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $funding_source['type'] ); ?></dd>

				<?php if ( null !== $funding_source['card'] ) { ?>  
					<dt><?php esc_html_e( 'Card Brand', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $funding_source['card']['type'] ); ?></dd>

					<dt><?php esc_html_e( 'Last 4 digits', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $funding_source['card']['numberLast4'] ); ?></dd>

					<dt><?php esc_html_e( 'Expiry date', 'peachpay-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $funding_source['card']['expirationMonth'] . '/' . $funding_source['card']['expirationYear'] ); ?></dd>
				<?php } ?>
				</dl>
			<?php } ?>
		</div>
	</div>
</div>
