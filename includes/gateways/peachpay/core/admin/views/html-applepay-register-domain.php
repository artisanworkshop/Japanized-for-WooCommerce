<?php
/**
 * PeachPay ApplePay domain registration for Square and Stripe.
 *
 * @var $gateway The gateway instance to differentiate between Square and Stripe.
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


$verification_url = 'https://' . wp_parse_url( get_site_url(), PHP_URL_HOST ) . '/.well-known/apple-developer-merchantid-domain-association';

?>
	<div class="settings-container action-needed">
		<h1><?php esc_html_e( 'Action required', 'peachpay-for-woocommerce' ); ?></h1>
		<hr>
		<span id="<?php echo esc_html( lcfirst( $gateway->payment_provider ) ); ?>-verify-apay-action-needed" class="pp-needs-action">
			<p>
				<?php esc_html_e( 'Apple Pay requires your domain to be registered in order to work. Automatic registration has failed. Click "Register domain" to attempt registration again.', 'peachpay-for-woocommerce' ); ?>
			</p>
			<div style="margin: 1rem 0; display: flex; align-items: center;">
				<button id="<?php echo esc_html( lcfirst( $gateway->payment_provider ) ); ?>-verify-apay-button"class="button pp-button-secondary" type="button"><?php esc_html_e( 'Register domain', 'peachpay-for-woocommerce' ); ?></button>
				<span id="<?php echo esc_html( lcfirst( $gateway->payment_provider ) ); ?>-verify-apay-spinner" class="hide" style="text-align: center; width: 110px;">
					<img src="<?php echo esc_attr( PeachPay::get_asset_url( 'img/spinner-dark.svg' ) ); ?>" style="height: 1.6rem;">
				</span>
				<span id="<?php echo esc_html( lcfirst( $gateway->payment_provider ) ); ?>-verify-apay-error" style="margin-left: 1rem;"></span>
			</div>
			<h4>
				<?php esc_html_e( 'If registration still fails:', 'peachpay-for-woocommerce' ); ?>
			</h4>
			<p>
				<?php esc_html_e( 'Ensure the following link is reachable: ', 'peachpay-for-woocommerce' ); ?><a target="_blank" href="<?php echo esc_attr( $verification_url ); ?>"><?php echo esc_url( $verification_url ); ?></a>. <?php esc_html_e( 'Some services such as Cloudflare will block access to this route and an exception will need to be made manually on those services.', 'peachpay-for-woocommerce' ); ?>
			</p>
		</span>
	</div>
	<?php
