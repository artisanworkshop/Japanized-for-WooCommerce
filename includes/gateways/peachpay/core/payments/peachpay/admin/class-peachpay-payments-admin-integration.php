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
final class PeachPay_Payments_Admin_Integration {
	use PeachPay_Admin_Extension;

	/**
	 * .
	 */
	private function init() {
		add_action(
			'peachpay_admin_add_payment_setting_section',
			function( $current ) {
				$class = 'pp-header pp-sub-nav-peachpay';
				if ( 'pp-sub-nav-peachpay' !== $current ) {
					$class .= ' hide';
				}
				add_settings_field(
					'peachpay_purchase_order_setting',
					__( 'Other payment methods', 'peachpay-for-woocommerce' ),
					array( 'PeachPay_Payments_Admin_Integration', 'do_admin_page' ),
					'peachpay',
					'peachpay_payment_settings_section',
					array( 'class' => $class )
				);
			},
			11
		);
	}

	/**
	 * Stripe admin page HTML. This is embedded on the page ?page=peachpay&tab=payment
	 */
	public static function do_admin_page() {
		?>
		<div id="peachpay" class="peachpay-setting-section">
			<div>
				<?php
					$gateway_list = PeachPay_Payments_Integration::get_payment_gateways();
					require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
				?>
			</div>
		</div>
		<?php
	}
}
PeachPay_Payments_Admin_Integration::instance();
