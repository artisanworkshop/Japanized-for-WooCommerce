<?php
/**
 * PeachPay Square Connect
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="row">
	<div class="col-3 flex-col gap-8" style="text-align: center;">
		<div class="payment-logo square-primary-bg">
			<img class="square-logo" src="<?php echo esc_attr( peachpay_url( 'core/payments/square/admin/assets/square-logo.png' ) ); ?>" />
		</div>

		<div class="flex-col gap-8">
			<!-- Square Connect / Unlink buttons -->
			<?php if ( peachpay_square_connected() ) : ?>
				<?php if ( peachpay_square_merchant_permission_version() < peachpay_square_permission_version() ) : ?>
					<div class="tooltip pp-w-100">
						<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url( peachpay_square_signup_url() ); ?>">
							<span><?php esc_html_e( 'Update permissions', 'peachpay-for-woocommerce' ); ?></span>
						</a>
						<span class="tooltip-body right" style="max-width: 40rem;">
							<?php esc_html_e( 'PeachPay has added new features to its Square integration. These features require additional permissions from your Square account.', 'peachpay-for-woocommerce' ); ?>
						</span>
					</div>
				<?php endif; ?>

				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_square#square' ) ); ?>" >
					<?php esc_html_e( 'Unlink Square', 'peachpay-for-woocommerce' ); ?>
				</a>

			<?php else : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( peachpay_square_signup_url() ); ?>">
					<span><?php esc_html_e( 'Connect Square', 'peachpay-for-woocommerce' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

	</div>
	<div class="col-9 flex-col gap-4" style="padding-left: 1rem;">
		<!-- Square Status -->
		<?php if ( peachpay_square_connected() ) : ?>
			<p>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( "You've successfully connected your Square account", 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'Connect your Square account to PeachPay.', 'peachpay-for-woocommerce' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Not sure if Square is right for you? Square is good for merchants who sell online and in a physical store. It’s also good if you sell items that are usually risky for other payment processors.', 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php endif; ?>

		<!-- Square advanced details -->
		<?php if ( peachpay_square_connected() ) : ?>
			<details style="border: 1px solid #dcdcde; border-radius: 4px; padding: 4px 10px; width: content-width; margin-top: 1rem;">
				<summary>
					<b><?php esc_html_e( 'Advanced Details', 'peachpay-for-woocommerce' ); ?></b>
				</summary>
				<hr>
				<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Merchant Id:', 'peachpay-for-woocommerce' ); ?></b>
					<?php echo esc_html( peachpay_square_merchant_id() ); ?>
				</p>
				<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Location Id:', 'peachpay-for-woocommerce' ); ?></b>
					<?php echo esc_html( peachpay_square_location_id() ); ?>
				</p>
				<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Application Id:', 'peachpay-for-woocommerce' ); ?></b>
					<?php echo esc_html( peachpay_square_application_id() ); ?>
				</p>
			</details>
		<?php endif; ?>
	</div>
</div>
