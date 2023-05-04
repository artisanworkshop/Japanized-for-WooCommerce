<?php
/**
 * PeachPay Stripe Connect
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="row">
	<div class="col-3 flex-col gap-8" style="text-align: center;">
		<div class="payment-logo stripe-primary-bg">
			<img src="<?php echo esc_attr( peachpay_url( 'core/payments/stripe/admin/assets/img/stripe-logo.svg' ) ); ?>" />
		</div>

		<div class="flex-col gap-8">
			<!-- Stripe Connect / Unlink buttons -->
			<?php if ( PeachPay_Stripe_Integration::connected() ) : ?>
				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_stripe#stripe' ) ); ?>">
					<?php esc_html_e( 'Unlink Stripe', 'peachpay-for-woocommerce' ); ?>
				</a>
			<?php else : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Stripe_Integration::signup_url() ); ?>">
					<span><?php esc_html_e( 'Connect Stripe', 'peachpay-for-woocommerce' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<div>
			<?php
			//phpcs:ignore
			echo peachpay_build_video_help_section('https://youtu.be/SrTykTIzwHo', "justify-content: center");
			?>
		</div>
	</div>
	<div class="col-9 flex-col gap-4" style="padding-left: 1rem;">
		<!-- Stripe Status -->
		<?php if ( PeachPay_Stripe_Integration::connected() ) : ?>
			<div class="pp-flex-row pp-gap-4">
				<span class="dashicons dashicons-yes-alt"></span>
				<div class="pp-flex-col pp-gap-4">
					<?php esc_html_e( "You've successfully connected your Stripe account", 'peachpay-for-woocommerce' ); ?>
				</div>
			</div>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'Connect your Stripe account to PeachPay.', 'peachpay-for-woocommerce' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Stripe is a good choice if you want the biggest selection of global payment methods and “buy now pay later” options.', 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php endif; ?>

		<!-- Stripe advanced details -->
		<?php if ( PeachPay_Stripe_Integration::connected() ) : ?>
		<details style="border: 1px solid #dcdcde; border-radius: 4px; padding: 4px 10px; width: content-width; margin-top: 1rem;">
			<summary>
				<b><?php esc_html_e( 'Advanced Details', 'peachpay-for-woocommerce' ); ?></b>
			</summary>
			<hr>
			<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Connect Id:', 'peachpay-for-woocommerce' ); ?></b>
			<?php
			PeachPay_Stripe::dashboard_url(
				PeachPay_Stripe_Integration::mode(),
				PeachPay_Stripe_Integration::connect_id(),
				'activity',
				PeachPay_Stripe_Integration::connect_id()
			);
			?>
			</p>
		</details>
		<?php endif; ?>
	</div>
</div>
