<?php
/**
 * PeachPay PayPal Connect
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="flex-col payment-provider-header <?php echo esc_attr( PeachPay_PayPal_Integration::connected() ? 'is-connected' : '' ); ?>">
	<div>
		<div class="flex-col gap-24 w-100">
			<div class="flex-row gap-12 ai-center provider-title">
				<img src="<?php echo esc_attr( peachpay_url( 'public/img/marks/paypal/small-white.svg' ) ); ?>" />
				<?php echo esc_html_e( 'PayPal Account', 'peachpay-for-woocommerce' ); ?>
			</div>
			<?php if ( PeachPay_PayPal_Integration::connected() ) : ?>
				<div class="flex-col gap-12 connected-info">
					<div class="flex-row gap-8 ai-center connected-success">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php echo esc_html_e( 'PayPal sandbox account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php else : ?>
							<?php echo esc_html_e( 'Your PayPal account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php endif; ?>
						<img src="<?php echo esc_attr( peachpay_url( 'public/img/checkmark-green.svg' ) ); ?>"/>
					</div>
					<span class="account-info">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php esc_html_e( 'Make test payments following', 'peachpay-for-woocommerce' ); ?> <a href="https://developer.paypal.com/tools/sandbox/card-testing/" target="_blank"><?php esc_html_e( 'these instructions', 'peachpay-for-woocommerce' ); ?></a>.
						<?php else : ?>
							<?php echo esc_html_e( 'Merchant Id: ', 'peachpay-for-woocommerce' ); ?>
							<span>
								<?php echo esc_html( PeachPay_PayPal_Integration::merchant_id() ); ?>
							</span>
						<?php endif; ?>
					</span>
				</div>
			<?php else : ?>
				<div class="flex-col provider-description">
					<p>
						<span><?php esc_html_e( 'If you have a regular PayPal account, you’ll be asked to upgrade to a business account. Not using PayPal yet? PayPal is accepted in over 200 countries and allows shoppers from almost anywhere to buy from you.', 'peachpay-for-woocommerce' ); ?></span>
						<span><?php esc_html_e( 'With PayPal you will be able to connect the following payment methods:', 'peachpay-for-woocommerce' ); ?></span>
					</p>
					<div class="method-icons">
						<?php
						foreach ( PeachPay_PayPal_Integration::get_payment_gateways() as $gateway ) {
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
			<!-- PayPal Connect / Unlink buttons -->
			<?php if ( PeachPay_PayPal_Integration::connected() ) : ?>
				<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_PayPal_Advanced::get_url() ); ?>" >
					<?php esc_html_e( 'Advanced settings', 'peachpay-for-woocommerce' ); ?>
				</a>
				<?php if ( ! peachpay_is_test_mode() ) : ?>
					<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_paypal#paypal' ) ); ?>" >
						<?php esc_html_e( 'Unlink PayPal', 'peachpay-for-woocommerce' ); ?>
					</a>
				<?php endif; ?>
			<?php elseif ( ! peachpay_is_test_mode() ) : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( peachpay_paypal_signup_url() ); ?>">
					<span><?php esc_html_e( 'Connect PayPal', 'peachpay-for-woocommerce' ); ?></span>
				</a>
			<?php endif; ?>
			<?php echo peachpay_build_video_help_section( 'https://youtu.be/3yHFi0A3Jw8', 'justify-content: center' ); // PHPCS:ignore ?>
		</div>
	</div>
	<div>
	<div class="provider-info">
		<div class="info-icon icon-18"></div>
		<span>
			<span><?php esc_html_e( 'Learn more about', 'peachpay-for-woocommerce' ); ?> <span>
			<a href="https://www.paypal.com/us/business/accept-payments/checkout" target="_blank"><?php esc_html_e( 'payment methods', 'peachpay-for-woocommerce' ); ?></a>
			<span> <?php esc_html_e( 'powered by PayPal and any associated', 'peachpay-for-woocommerce' ); ?> </span>
			<a href="https://www.paypal.com/us/webapps/mpp/merchant-fees" target="_blank">
				<?php esc_html_e( 'fees', 'peachpay-for-woocommerce' ); ?>
			</a>
		</span>
	</div>
		<?php if ( PeachPay::service_fee_enabled() ) : ?>
		<div class="provider-info">
			<div class="info-icon icon-18"></div>
			<span>
				<span><?php esc_html_e( 'PeachPay charges a', 'peachpay-for-woocommerce' ); ?></span>
				<a href="https://help.peachpay.app/en/articles/7932806-about-our-service-fee"><?php echo esc_html( PeachPay::service_fee_percentage() * 100 ); ?><?php esc_html_e( '% service fee', 'peachpay-for-woocommerce' ); ?></a>
				<?php esc_html_e( 'to the customer. As a merchant, you don’t pay anything extra.', 'peachpay-for-woocommerce' ); ?>
			</span>
		</div>
		<?php endif; ?>
	</div>
</div>
