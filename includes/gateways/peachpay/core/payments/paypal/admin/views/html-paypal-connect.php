<?php
/**
 * PeachPay PayPal Connect
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="row">
	<div class="col-3 flex-col gap-8" style="text-align: center;">
		<div class="payment-logo paypal-primary-bg">
			<img src="<?php echo esc_attr( peachpay_url( 'core/payments/paypal/admin/assets/img/paypal-logo.svg' ) ); ?>" />
		</div>

		<div class="flex-col gap-8">
			<!-- PayPal Connect / Unlink buttons -->
			<?php if ( PeachPay_PayPal_Integration::connected() ) : ?>
				<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_PayPal_Advanced::get_url() ); ?>" >
					<?php esc_html_e( 'Advanced settings', 'peachpay-for-woocommerce' ); ?>
				</a>
				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_paypal#paypal' ) ); ?>" >
					<?php esc_html_e( 'Unlink PayPal', 'peachpay-for-woocommerce' ); ?>
				</a>
			<?php else : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( peachpay_paypal_signup_url() ); ?>">
					<span><?php esc_html_e( 'Connect PayPal', 'peachpay-for-woocommerce' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<div>
			<?php echo peachpay_build_video_help_section( 'https://youtu.be/3yHFi0A3Jw8', 'justify-content: center' ); // PHPCS:ignore ?>
		</div>
	</div>
	<div class="col-9 flex-col gap-4" style="padding-left: 1rem;">
		<!-- PayPal Status -->
		<?php if ( PeachPay_PayPal_Integration::connected() ) : ?>
			<p>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( "You've successfully connected your PayPal account", 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'Connect your PayPal business account.', 'peachpay-for-woocommerce' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'If you have a regular PayPal account, you’ll be asked to upgrade to a business account. Not using PayPal yet? PayPal is accepted in over 200 countries and allows shoppers from almost anywhere to buy from you.', 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php endif; ?>

		<!-- PayPal advanced details -->
		<?php if ( PeachPay_PayPal_Integration::connected() ) : ?>
		<details style="border: 1px solid #dcdcde; border-radius: 4px; padding: 4px 10px; width: content-width; margin-top: 1rem;">
			<summary>
				<b><?php esc_html_e( 'Advanced Details', 'peachpay-for-woocommerce' ); ?></b>
			</summary>
			<hr>
			<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Merchant Id:', 'peachpay-for-woocommerce' ); ?></b> <?php echo esc_html( PeachPay_PayPal_Integration::merchant_id() ); ?></p>
		</details>
		<?php endif; ?>
	</div>
</div>
