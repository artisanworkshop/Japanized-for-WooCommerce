<?php
/**
 * PeachPay Stripe payment integration admin settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}



/**
 * .
 */
final class PeachPay_Admin_Stripe_Integration {
	use PeachPay_Admin_Extension;

	/**
	 * .
	 */
	private function init() {
		peachpay_stripe_applepay_domain_register();

		add_action(
			'peachpay_admin_add_payment_setting_section',
			function( $current ) {
				$class = 'pp-header pp-sub-nav-stripe';
				if ( 'pp-sub-nav-stripe' !== $current ) {
					$class .= ' hide';
				}
				add_settings_field(
					'peachpay_stripe_setting',
					null,
					array( 'PeachPay_Admin_Stripe_Integration', 'do_admin_page' ),
					'peachpay',
					'peachpay_payment_settings_section',
					array( 'class' => $class )
				);
			}
		);
	}

	/**
	 * Stripe admin page HTML. This is embedded on the page ?page=peachpay&tab=payment
	 */
	public static function do_admin_page() {
		?>
		<div id="stripe" class="peachpay-setting-section">
			<div>
				<?php
					// Stripe connect option.
					require PeachPay::get_plugin_path() . '/core/payments/stripe/admin/views/html-stripe-connect.php';
				?>
			</div>
			<div>
				<?php
					$gateway_list = PeachPay_Stripe_Integration::get_payment_gateways();
					require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
				?>
			</div>
			<div class="gateway-provider-info">
				<p>
					<?php esc_html_e( 'Learn more about', 'peachpay-for-woocommerce' ); ?>
					<a href="https://stripe.com/payments/payment-methods-guide" target="_blank"><?php esc_html_e( 'payment methods', 'peachpay-for-woocommerce' ); ?></a>
					<?php esc_html_e( 'powered by Stripe and any associated', 'peachpay-for-woocommerce' ); ?>
					<a href="https://stripe.com/pricing/local-payment-methods" target="_blank">
						<?php esc_html_e( 'fees', 'peachpay-for-woocommerce' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
PeachPay_Admin_Stripe_Integration::instance();
