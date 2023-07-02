<?php
/**
 * PeachPay Poynt Connect template.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="flex-col payment-provider-header <?php echo esc_attr( PeachPay_Poynt_Integration::connected() ? 'is-connected' : '' ); ?>">
	<div>
		<div class="flex-col gap-24 w-100">
			<div class="flex-row gap-12 ai-center provider-title">
				<img src="<?php echo esc_attr( peachpay_url( 'public/img/marks/poynt/short.svg' ) ); ?>" />
				<?php echo esc_html_e( 'GoDaddy Poynt Account', 'peachpay-for-woocommerce' ); ?>
			</div>
			<!-- Poynt Status -->
			<?php if ( PeachPay_Poynt_Integration::connected() ) : ?>
				<div class="flex-col gap-12 connected-info">
					<div class="flex-row gap-8 ai-center connected-success">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php echo esc_html_e( 'GoDaddy Poynt sandbox account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php else : ?>
							<?php echo esc_html_e( 'Your GoDaddy Poynt account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php endif; ?>
						<img src="<?php echo esc_attr( peachpay_url( 'public/img/checkmark-green.svg' ) ); ?>"/>
					</div>
					<div class="flex-col gap-4">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<span class="account-info">
								<?php esc_html_e( 'Make test payments using the card number ', 'peachpay-for-woocommerce' ); ?> <b>4242 4242 4242 4242</b> <?php esc_html_e( 'with expiration ', 'peachpay-for-woocommerce' ); ?> <b>04/28</b> <?php esc_html_e( 'and CVC ', 'peachpay-for-woocommerce' ); ?> <b>444</b>
							</span>
						<?php else : ?>
							<span class="account-info">
								<?php esc_html_e( 'Business Id:', 'peachpay-for-woocommerce' ); ?> <?php echo esc_html( PeachPay_Poynt_Integration::business_id() ); ?>
							</span>
							<span class="account-info">
								<?php esc_html_e( 'Application Id:', 'peachpay-for-woocommerce' ); ?> <?php echo esc_html( PeachPay_Poynt_Integration::application_id() ); ?>
							</span>
						<?php endif; ?>
						<span class="account-info">
							<?php esc_html_e( 'Webhook Status:', 'peachpay-for-woocommerce' ); ?> <b><?php PeachPay_Poynt_Integration::webhook_status() ? esc_html_e( 'Active', 'peachpay-for-woocommerce' ) : esc_html_e( 'Inactive', 'peachpay-for-woocommerce' ); ?></b>
						</span>
						<span id="poynt-webhook-register" class="peachpay row">
							<button class="button-primary-outlined-small default-outlined" type="button">
								<?php echo esc_html( sprintf( '%s webhooks', PeachPay_Poynt_Integration::webhook_status() ? __( 'Reset', 'peachpay-for-woocommerce' ) : __( 'Register', 'peachpay-for-woocommerce' ) ) ); ?>
							</button>
							<img src="<?php echo esc_attr( PeachPay::get_asset_url( 'img/spinner-dark.svg' ) ); ?>" class="hide" style="height: 1.6rem;">
							<span style="align-items: center;display: flex;"></span>
						</span>
					</div>
				</div>
			<?php else : ?>
				<div class="flex-col provider-description">
					<p><?php esc_html_e( 'Poynt supports card and in-person payments if you have the Poynt POS (Point of Sale) system.', 'peachpay-for-woocommerce' ); ?></p>
					<div class="method-icons">
						<?php
						foreach ( PeachPay_Poynt_Integration::get_payment_gateways() as $gateway ) {
							$icon = $gateway->get_icon_url( 'small', 'color' );
							?>
							<span class="flex-row">
								<img src="<?php echo esc_attr( $icon ); ?>" alt="<?php echo esc_attr( $gateway->title ); ?>"/>
								<span class="gateway-title">
									<span><?php echo esc_attr( $gateway->title ); ?></span>
									<span>,</span>
								</span>
							</span>
							<?php
						}
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<div class="flex-col gap-12 ai-center buttons-container">
			<!-- Poynt Connect / Unlink buttons -->
			<?php if ( PeachPay_Poynt_Integration::connected() ) : ?>
				<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url_raw( PeachPay_Poynt_Advanced::get_url() ); ?>" >
					<?php esc_html_e( 'Advanced settings', 'peachpay-for-woocommerce' ); ?>
				</a>
				<?php if ( ! peachpay_is_test_mode() ) : ?>
				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url_raw( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_poynt#poynt' ) ); ?>">
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
							<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url_raw( PeachPay_Poynt_Integration::login_url() ); ?>">
								<?php esc_html_e( 'Log in', 'peachpay-for-woocommerce' ); ?>
							</a>
							<span style="display: inline-block; width: 12px;"></span>
							<a class="connect-payment-button button-primary-outlined-medium" href="<?php echo esc_url_raw( PeachPay_Poynt_Integration::signup_url() ); ?>">
								<?php esc_html_e( 'Sign up', 'peachpay-for-woocommerce' ); ?>
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="provider-info">
		<div class="info-icon icon-18"></div>
		<span>
			<span><?php esc_html_e( 'Learn more about', 'peachpay-for-woocommerce' ); ?> <span>
			<a href="https://www.godaddy.com/help/which-payment-methods-work-with-godaddy-payments-40690" target="_blank"><?php esc_html_e( 'payment methods', 'peachpay-for-woocommerce' ); ?></a>
			<span> <?php esc_html_e( 'powered by GoDaddy and any associated', 'peachpay-for-woocommerce' ); ?> </span>
			<a href="https://www.godaddy.com/help/what-are-the-charges-and-fees-for-godaddy-payments-40617" target="_blank">
				<?php esc_html_e( 'fees', 'peachpay-for-woocommerce' ); ?>
			</a>
		</span>
	</div>
</div>
