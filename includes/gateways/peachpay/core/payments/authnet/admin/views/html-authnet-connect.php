<?php
/**
 * PeachPay Authnet Connect template.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="row">
	<div class="col-3 flex-col gap-8" style="text-align: center;">
		<div style="background-color: transparent; height: unset;" class="payment-logo">
			<img src="<?php echo esc_attr( peachpay_url( 'core/payments/authnet/admin/assets/img/authnet-logo.svg' ) ); ?>" />
		</div>

		<div class="flex-col gap-8">
			<!-- Authnet Connect / Unlink buttons -->
			<?php if ( PeachPay_Authnet_Integration::connected() ) : ?>
				<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Authnet_Advanced::get_url() ); ?>" >
					<?php esc_html_e( 'Advanced settings', 'peachpay-for-woocommerce' ); ?>
				</a>
				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_authnet#authnet' ) ); ?>" >
					<?php
					if ( peachpay_is_test_mode() ) {
						esc_html_e( 'Unlink Authorize.net (sandbox)', 'peachpay-for-woocommerce' );
					} else {
						esc_html_e( 'Unlink Authorize.net', 'peachpay-for-woocommerce' );
					}
					?>
				</a>
			<?php else : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="#authnet_signup">
					<?php
					peachpay_is_test_mode() ? esc_html_e( 'Connect Authorize.net (sandbox)', 'peachpay-for-woocommerce' ) : esc_html_e( 'Connect Authorize.net', 'peachpay-for-woocommerce' );
					?>
				</a>
				<div id="authnet_signup" class="modal-window">
					<a href="#" class="outside-close"></a>
					<div>
						<h4><?php peachpay_is_test_mode() ? esc_html_e( 'Connect Authorize.net (sandbox)', 'peachpay-for-woocommerce' ) : esc_html_e( 'Connect Authorize.net', 'peachpay-for-woocommerce' ); ?></h4>
						<hr>
						<a href="#" title="Cancel" class="modal-close"><?php esc_html_e( 'Cancel', 'peachpay-for-woocommerce' ); ?></a>
						<span style="display: inline-block; height: 6px;"></span>
						<p style="text-align:left;">
						<?php
						$sandbox = peachpay_is_test_mode() ? 'Sandbox' : '';
						// translators: %1$s Account type title.
						echo sprintf( __( 'Connect an existing Authorize.net %1$s account or create a new one. You\'ll be redirected to the Authorize.net website to complete the onboarding.' ), $sandbox ); //phpcs:ignore
						?>
						</p>
						<span style="display: inline-block; height: 16px"></span>
						<div class="flex-row gap-12">
							<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Authnet_Integration::connect_url() ); ?>">
							<?php
							if ( peachpay_is_test_mode() ) {
								esc_html_e( 'Log in (sandbox)', 'peachpay-for-woocommerce' );
							} else {
								esc_html_e( 'Log in', 'peachpay-for-woocommerce' );
							}
							?>
							</a>
							<?php if ( peachpay_is_test_mode() ) : ?>
							<a class="connect-payment-button button-primary-outlined-medium" href="<?php echo esc_url( 'https://developer.authorize.net/hello_world/sandbox.html' ); ?>" target="_blank">
								<?php esc_html_e( 'Sign up (sandbox)', 'peachpay-for-woocommerce' ); ?>
							</a>
							<?php else : ?>
							<a class="connect-payment-button button-primary-outlined-medium" href="<?php echo esc_url( PeachPay_Authnet_Integration::signup_url() ); ?>" target="_blank">
								<?php esc_html_e( 'Sign up', 'peachpay-for-woocommerce' ); ?>
							</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="col-9" style="padding-left: 1rem;">
		<!-- Authnet Status -->
		<?php if ( PeachPay_Authnet_Integration::connected() ) : ?>
			<p>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php if ( peachpay_is_test_mode() ) : ?>
					<?php esc_html_e( "You've successfully connected your Authorize.net sandbox account", 'peachpay-for-woocommerce' ); ?>
					<br/>
					<br/>
					<p>
						<?php esc_html_e( 'Make sandbox payments following', 'peachpay-for-woocommerce' ); ?>
						<a href="https://developer.authorize.net/hello_world/testing_guide.html"><?php esc_html_e( 'these instructions', 'peachpay-for-woocommerce' ); ?></a>.
					</p>
				<?php else : ?>
					<?php esc_html_e( "You've successfully connected your Authorize.net account", 'peachpay-for-woocommerce' ); ?>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p>
				<?php
				if ( peachpay_is_test_mode() ) {
					esc_html_e( 'Connect your Authorize.net sandbox account.', 'peachpay-for-woocommerce' );
				} else {
					esc_html_e( 'Connect your Authorize.net account.', 'peachpay-for-woocommerce' );
				}
				?>
			</p>
			<br>
			<p>
				<?php esc_html_e( 'Authorize.net is a credit card processor. Not sure if this one is right for you, or looking to switch? Authorize.net offers competitive transaction rates for stores selling more than $500K USD per year. For more information, visit the Authorize.net website.', 'peachpay-for-woocommerce' ); ?>
			</p>
		<?php endif; ?>

		<!-- Authnet advanced details -->
		<?php if ( PeachPay_Authnet_Integration::connected() ) : ?>
		<details style="border: 1px solid #dcdcde; border-radius: 4px; padding: 4px 10px; width: content-width; margin-top: 1rem;">
			<summary>
				<b><?php esc_html_e( 'Advanced Details', 'peachpay-for-woocommerce' ); ?></b>
			</summary>
			<hr>
			<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Login Id:', 'peachpay-for-woocommerce' ); ?></b> <?php echo PeachPay_Authnet_Integration::login_id(); //phpcs:ignore ?></p>
			<p style="padding: 0 1rem 0; margin: 0;"><b><?php esc_html_e( 'Public Client Key:', 'peachpay-for-woocommerce' ); ?></b> <?php echo PeachPay_Authnet_Integration::public_client_key(); //phpcs:ignore ?></p>
		</details>
		<?php endif; ?>
	</div>
</div>
