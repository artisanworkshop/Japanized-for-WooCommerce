<?php
/**
 * PeachPay Authnet Connect template.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="flex-col payment-provider-header <?php echo esc_attr( PeachPay_Authnet_Integration::connected() ? 'is-connected' : '' ); ?>">
	<div>
		<div class="flex-col gap-24 w-100">
			<div class="flex-row gap-12 ai-center provider-title">
				<img src="<?php echo esc_attr( peachpay_url( 'public/img/marks/authnet/short.svg' ) ); ?>" />
				<?php echo esc_html_e( 'Authorize.net Account', 'peachpay-for-woocommerce' ); ?>
			</div>
			<!-- Authnet Status -->
			<?php if ( PeachPay_Authnet_Integration::connected() ) : ?>
				<div class="flex-col gap-12 connected-info">
					<div class="flex-row gap-8 ai-center connected-success">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php echo esc_html_e( 'Your Authorize.net sandbox account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php else : ?>
							<?php echo esc_html_e( 'Your Authorize.net account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php endif; ?>
						<img src="<?php echo esc_attr( peachpay_url( 'public/img/checkmark-green.svg' ) ); ?>"/>
					</div>
					<div class="flex-col gap-4">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<span class="account-info">
								<?php esc_html_e( 'Make test payments following', 'peachpay-for-woocommerce' ); ?> 
								<a href="https://developer.authorize.net/hello_world/testing_guide.html" target="_blank"><?php esc_html_e( 'these instructions', 'peachpay-for-woocommerce' ); ?></a>.
							</span>
						<?php endif; ?>
						<span class="account-info">
							<?php esc_html_e( 'Login Id:', 'peachpay-for-woocommerce' ); ?> <?php echo esc_html( PeachPay_Authnet_Integration::login_id() ); ?>
						</span>
						<span class="account-info">
							<?php esc_html_e( 'Public Client Key:', 'peachpay-for-woocommerce' ); ?> <?php echo esc_html( PeachPay_Authnet_Integration::public_client_key() ); ?>
						</span>
					</div>
				</div>
			<?php else : ?>
				<div class="flex-col provider-description">
					<p><?php esc_html_e( 'Authorize.net is a credit card processor. Not sure if this one is right for you, or looking to switch? Authorize.net offers competitive transaction rates for stores selling more than $500K USD per year.', 'peachpay-for-woocommerce' ); ?></p>
					<div class="method-icons">
						<?php
						foreach ( PeachPay_Authnet_Integration::get_payment_gateways() as $gateway ) {
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
	<div class="provider-info">
		<div class="info-icon icon-18"></div>
		<span>
			<span><?php esc_html_e( 'For more information about Authorize.net\'s services and pricing, visit', 'peachpay-for-woocommerce' ); ?> <span>
			<a href="https://www.authorize.net/sign-up/pricing.html" target="_blank"><?php esc_html_e( 'Authorize.net', 'peachpay-for-woocommerce' ); ?></a>
		</span>
	</div>
</div>
