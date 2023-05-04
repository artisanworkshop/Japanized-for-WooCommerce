<?php
/**
 * PeachPay Poynt Connect template.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="row">
	<div class="col-3 flex-col gap-8" style="text-align: center;">
		<div class="payment-logo poynt-primary-bg">
			<img style="padding: 0px 5px; width: 96%;" src="<?php echo esc_attr( peachpay_url( 'core/payments/poynt/admin/assets/img/poynt-logo.svg' ) ); ?>" />
		</div>

		<div class="flex-col gap-8">
			<!-- Poynt Connect / Unlink buttons -->
			<?php if ( PeachPay_Poynt_Integration::connected() ) : ?>
				<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Poynt_Advanced::get_url() ); ?>" >
					<?php esc_html_e( 'Advanced settings', 'peachpay-for-woocommerce' ); ?>
				</a>
				<?php if ( ! peachpay_is_test_mode() ) : ?>
				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_poynt#poynt' ) ); ?>">
					<?php esc_html_e( 'Unlink GoDaddy Poynt', 'peachpay-for-woocommerce' ); ?>
				</a>
				<?php endif; ?>
			<?php elseif ( ! peachpay_is_test_mode() ) : ?>

				<a class="connect-payment-button button-primary-filled-medium" href="#poynt-signup">
					<?php esc_html_e( 'Connect GoDaddy Poynt', 'peachpay-for-woocommerce' ); ?>
				</a>

				<div id="poynt-signup" class="modal-window">
					<a href="#" title="Cancel" class="outside-close"> </a>
					<div>
						<h4><?php esc_html_e( 'Connect GoDaddy Poynt', 'peachpay-for-woocommerce' ); ?></h4>
						<hr>
						<a href="#" title="Cancel" class="modal-close"><?php esc_html_e( 'Cancel', 'peachpay-for-woocommerce' ); ?></a>
						<span style="display: inline-block; height: 6px;"></span>
						<p style="text-align:left;">
							<?php esc_html_e( "Connect an existing Poynt account or create a new one. You'll be redirected to the GoDaddy Poynt website to complete the onboarding.", 'peachpay-for-woocommerce' ); ?>
						</p>
						<span style="display: inline-block; height: 16px;"></span>
						<div style="display: flex; flex-direction: row;">
							<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Poynt_Integration::login_url() ); ?>">
								<?php esc_html_e( 'Log in', 'peachpay-for-woocommerce' ); ?>
							</a>
							<span style="display: inline-block; width: 12px;"></span>
							<a class="connect-payment-button button-primary-outlined-medium" href="<?php echo esc_url( PeachPay_Poynt_Integration::signup_url() ); ?>">
								<?php esc_html_e( 'Sign up', 'peachpay-for-woocommerce' ); ?>
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="col-9" style="padding-left: 1rem;">
		<!-- Poynt Status -->
		<?php if ( PeachPay_Poynt_Integration::connected() ) : ?>
			<p>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php if ( peachpay_is_test_mode() ) : ?>
					<?php esc_html_e( 'GoDaddy Poynt sandbox account connected', 'peachpay-for-woocommerce' ); ?>
				<?php else : ?>
					<?php esc_html_e( "You've successfully connected your GoDaddy Poynt account", 'peachpay-for-woocommerce' ); ?>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p>
				<?php if ( ! peachpay_is_test_mode() ) : ?>
					<?php esc_html_e( 'Connect your GoDaddy Poynt account.', 'peachpay-for-woocommerce' ); ?>
				<?php endif; ?>
			</p>
			<br>
			<p>
				<?php esc_html_e( 'Poynt supports card and in-person payments if you have the Poynt POS (Point of Sale) system.', 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php endif; ?>

		<!-- Poynt advanced details -->
		<?php if ( PeachPay_Poynt_Integration::connected() ) : ?>
		<details style="border: 1px solid #dcdcde; border-radius: 4px; padding: 4px 10px; width: content-width; margin-top: 1rem;">
			<summary>
				<b><?php esc_html_e( 'Advanced Details', 'peachpay-for-woocommerce' ); ?></b>
			</summary>
			<hr>
			<?php if ( ! peachpay_is_test_mode() ) : ?>
				<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Business Id:', 'peachpay-for-woocommerce' ); ?></b> <?php echo esc_html( PeachPay_Poynt_Integration::business_id() ); ?></p>
				<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Application Id:', 'peachpay-for-woocommerce' ); ?></b> <?php echo esc_html( PeachPay_Poynt_Integration::application_id() ); ?></p>
			<?php endif; ?>
			<p style="padding: 0 1rem 0; margin: 0;">
				<b><?php esc_html_e( 'Webhook Status:', 'peachpay-for-woocommerce' ); ?></b>
				<?php PeachPay_Poynt_Integration::webhook_status() ? esc_html_e( 'Active', 'peachpay-for-woocommerce' ) : esc_html_e( 'Inactive', 'peachpay-for-woocommerce' ); ?>
			</p>
			<br/>
			<span id="poynt-webhook-register" class="peachpay row" style="padding: 0 1rem 0; margin: 0;">
				<button class="button-primary-outlined-small default-outlined" type="button">
					<?php echo esc_html( sprintf( '%s webhooks', PeachPay_Poynt_Integration::webhook_status() ? __( 'Reset', 'peachpay-for-woocommerce' ) : __( 'Register', 'peachpay-for-woocommerce' ) ) ); ?>
				</button>
				<img src="<?php echo esc_attr( PeachPay::get_asset_url( 'img/spinner-dark.svg' ) ); ?>" class="hide" style="height: 1.6rem;">
				<span style="align-items: center;display: flex;"></span>
			</span>
		</details>
		<?php endif; ?>
	</div>
</div>
