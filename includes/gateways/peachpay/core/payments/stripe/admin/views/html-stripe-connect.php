<?php
/**
 * PeachPay Stripe Connect
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="flex-col payment-provider-header <?php echo esc_attr( PeachPay_Stripe_Integration::connected() ? 'is-connected' : '' ); ?>">
	<div>
		<div class="flex-col gap-24 w-100">
			<div class="flex-row gap-12 ai-center provider-title">
				<img src="<?php echo esc_attr( peachpay_url( 'public/img/marks/stripe/short-color.svg' ) ); ?>" />
				Stripe Account
			</div>
			<?php if ( PeachPay_Stripe_Integration::connected() ) : ?>
				<div class="flex-col gap-12 connected-info">
					<div class="flex-row gap-8 ai-center connected-success">
						<?php echo esc_html_e( 'Your Stripe account is connected!', 'peachpay-for-woocommerce' ); ?>
						<img src="<?php echo esc_attr( peachpay_url( 'public/img/checkmark-green.svg' ) ); ?>"/>
					</div>
					<span class="account-info">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php esc_html_e( 'Make test payments following', 'peachpay-for-woocommerce' ); ?> <a href="https://stripe.com/docs/testing" target="_blank"><?php esc_html_e( 'these instructions', 'peachpay-for-woocommerce' ); ?></a>.
							<br/>
						<?php endif; ?>
						<?php echo esc_html_e( 'Account Id: ', 'peachpay-for-woocommerce' ); ?>
						<span>
							<?php
							PeachPay_Stripe::dashboard_url(
								PeachPay_Stripe_Integration::mode(),
								PeachPay_Stripe_Integration::connect_id(),
								'activity',
								PeachPay_Stripe_Integration::connect_id()
							);
							?>
						</span>
					</span>
				</div>
			<?php else : ?>
				<div class="flex-col provider-description">
					<p>
						<span><?php esc_html_e( 'Stripe will give you the largest selection of global payment methods along with Apple Pay and Google Pay.', 'peachpay-for-woocommerce' ); ?></span>
						<span><?php esc_html_e( 'With Stripe you will be able to connect the following payment methods:', 'peachpay-for-woocommerce' ); ?></span>
					</p>
					<div class="method-icons">
						<?php
						foreach ( PeachPay_Stripe_Integration::get_payment_gateways() as $gateway ) {
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
			<?php if ( PeachPay_Stripe_Integration::connected() ) : ?>
				<a class="update-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Stripe_Advanced::get_url() ); ?>" >
					<?php esc_html_e( 'Advanced settings', 'peachpay-for-woocommerce' ); ?>
				</a>
				<a class="unlink-payment-button button-error-outlined-medium" href="<?php echo esc_url( admin_url( 'admin.php?page=peachpay&tab=payment&unlink_stripe#stripe' ) ); ?>">
					<?php esc_html_e( 'Unlink Stripe', 'peachpay-for-woocommerce' ); ?>
				</a>
			<?php else : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( PeachPay_Stripe_Integration::signup_url() ); ?>">
					<span><?php esc_html_e( 'Connect Stripe', 'peachpay-for-woocommerce' ); ?></span>
				</a>
			<?php endif; ?>
			<?php
			//phpcs:ignore
			echo peachpay_build_video_help_section('https://youtu.be/SrTykTIzwHo', "justify-content: center");
			?>
		</div>
	</div>
	<div class="flex-col gap-4">
		<div class="provider-info">
			<div class="info-icon icon-18"></div>
			<span>
				<span><?php esc_html_e( 'Learn more about', 'peachpay-for-woocommerce' ); ?> <span>
				<a href="https://stripe.com/payments/payment-methods-guide" target="_blank"><?php esc_html_e( 'payment methods', 'peachpay-for-woocommerce' ); ?></a>
				<span> <?php esc_html_e( 'powered by Stripe and any associated', 'peachpay-for-woocommerce' ); ?> </span>
				<a href="https://stripe.com/pricing/local-payment-methods" target="_blank">
					<?php esc_html_e( 'fees', 'peachpay-for-woocommerce' ); ?>
				</a>
			</span>
		</div>
		<?php if ( PeachPay::service_fee_enabled() ) : ?>
			<div class="provider-info">
				<div class="info-icon icon-18"></div>
				<span>
					<span><?php esc_html_e( 'PeachPay charges a', 'peachpay-for-woocommerce' ); ?> <span>
					<a href="https://help.peachpay.app/en/articles/7932806-about-our-service-fee" target="_blank"><?php echo esc_html( PeachPay::service_fee_percentage() * 100 ); ?><?php esc_html_e( '% service fee', 'peachpay-for-woocommerce' ); ?></a>
					<?php esc_html_e( 'to the customer. As a merchant, you don’t pay anything extra.', 'peachpay-for-woocommerce' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>
</div>
