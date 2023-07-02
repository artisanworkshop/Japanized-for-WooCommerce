<?php
/**
 * PeachPay Square Connect
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div class="flex-col payment-provider-header <?php echo esc_attr( peachpay_square_connected() ? 'is-connected' : '' ); ?>">
	<div>
		<div class="flex-col gap-24 w-100">
			<div class="flex-row gap-12 ai-center provider-title">
				<img src="<?php echo esc_attr( peachpay_url( 'public/img/marks/square/short.svg' ) ); ?>" />
				<?php echo esc_html_e( 'Square Account', 'peachpay-for-woocommerce' ); ?>
			</div>
			<!-- Square Status -->
			<?php if ( peachpay_square_connected() ) : ?>
				<div class="flex-col gap-12 connected-info">
					<div class="flex-row gap-8 ai-center connected-success">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php echo esc_html_e( 'Your Square sandbox account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php else : ?>
							<?php echo esc_html_e( 'Your Square account is connected!', 'peachpay-for-woocommerce' ); ?>
						<?php endif; ?>
						<img src="<?php echo esc_attr( peachpay_url( 'public/img/checkmark-green.svg' ) ); ?>"/>
					</div>
					<span class="account-info">
						<?php if ( peachpay_is_test_mode() ) : ?>
							<?php esc_html_e( 'Make test payments following', 'peachpay-for-woocommerce' ); ?> <a href="https://developer.squareup.com/docs/devtools/sandbox/payments" target="_blank"><?php esc_html_e( 'these instructions', 'peachpay-for-woocommerce' ); ?></a>.
						<?php endif; ?>
						<div class="flex-col">
							<span class="account-info">
							<?php echo esc_html_e( 'Merchant Id: ', 'peachpay-for-woocommerce' ); ?>
								<span>
									<?php echo esc_html( peachpay_square_merchant_id() ); ?>
								</span>
							</span>
							<span class="account-info">
							<?php echo esc_html_e( 'Location Id: ', 'peachpay-for-woocommerce' ); ?>
								<span>
									<?php echo esc_html( peachpay_square_location_id() ); ?>
								</span>
							</span>
							<span class="account-info">
							<?php echo esc_html_e( 'Application Id: ', 'peachpay-for-woocommerce' ); ?>
								<span>
									<?php echo esc_html( peachpay_square_application_id() ); ?>
								</span>
							</span>
						</div>
					</span>
				</div>
			<?php else : ?>
				<div class="flex-col provider-description">
					<p>
						<span><?php esc_html_e( 'Not sure if Square is right for you?', 'peachpay-for-woocommerce' ); ?></span>
						<span><?php esc_html_e( 'Square is good for merchants who sell online and in a physical store. It’s also good if you sell items that are usually risky for other payment processors.', 'peachpay-for-woocommerce' ); ?></span>
						<span><?php esc_html_e( 'With Square you will be able to connect the following payment methods:', 'peachpay-for-woocommerce' ); ?></span>
					</p>
					<div class="method-icons">
						<?php
						foreach ( PeachPay_Square_Integration::get_payment_gateways() as $gateway ) {
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
					<?php
					if ( peachpay_is_test_mode() ) {
						esc_html_e( 'Unlink Square (sandbox)', 'peachpay-for-woocommerce' );
					} else {
						esc_html_e( 'Unlink Square', 'peachpay-for-woocommerce' );
					}
					?>
				</a>
			<?php else : ?>
				<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( peachpay_square_signup_url() ); ?>">
				<?php
				if ( peachpay_is_test_mode() ) {
					esc_html_e( 'Connect Square (sandbox)', 'peachpay-for-woocommerce' );
				} else {
					esc_html_e( 'Connect Square', 'peachpay-for-woocommerce' );
				}
				?>
				</a>
				<?php if ( peachpay_is_test_mode() ) : ?>
					<div id="square-signup" class="modal-window">
						<a href="#square" title="Cancel" class="outside-close"> </a>
						<div>
							<h4><?php esc_html_e( 'Connect Square Sandbox Test Account', 'peachpay-for-woocommerce' ); ?></h4>
							<hr>
							<a href="#square" title="Cancel" class="modal-close"><?php esc_html_e( 'Cancel', 'peachpay-for-woocommerce' ); ?></a>
							<span style="display: inline-block; height: 6px;"></span>
							<p style="text-align:left;">
								<?php esc_html_e( "Before you click connect make sure to open a sandbox test account's dashboard in another browser tab.", 'peachpay-for-woocommerce' ); ?>
								<br>
								<a href="https://developer.squareup.com/docs/devtools/sandbox/overview" target="_blank"><?php esc_html_e( 'Square Sandbox Documentation', 'peachpay-for-woocommerce' ); ?></a>
							</p>
							<span style="display: inline-block; height: 16px;"></span>
							<div style="display: flex; flex-direction: row;">
								<a class="connect-payment-button button-primary-filled-medium" href="<?php echo esc_url( peachpay_square_signup_url() ); ?>">
									<?php esc_html_e( 'Connect', 'peachpay-for-woocommerce' ); ?>
								</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<div>
	<div class="provider-info">
		<div class="info-icon icon-18"></div>
		<span>
			<span><?php esc_html_e( 'Learn more about', 'peachpay-for-woocommerce' ); ?> </span>
			<a href="https://squareup.com/us/en/payments" target="_blank"><?php esc_html_e( 'payment methods', 'peachpay-for-woocommerce' ); ?></a>
			<span> <?php esc_html_e( 'powered by Square and any associated', 'peachpay-for-woocommerce' ); ?> </span>
			<a href="https://squareup.com/us/en/payments/our-fees" target="_blank">
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
