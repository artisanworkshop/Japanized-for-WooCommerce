<?php
/**
 * PeachPay Square payment integration admin settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_Admin_Square_Integration {
	use PeachPay_Admin_Extension;

	/**
	 * .
	 */
	private function init() {
		peachpay_square_register_apple_pay_domain();

		add_action(
			'peachpay_admin_add_payment_setting_section',
			function( $current ) {
				$class = 'pp-header pp-sub-nav-square';
				if ( 'pp-sub-nav-square' !== $current ) {
					$class .= ' hide';
				}
				add_settings_field(
					'peachpay_square_setting',
					null,
					array( 'PeachPay_Admin_Square_Integration', 'do_admin_page' ),
					'peachpay',
					'peachpay_payment_settings_section',
					array( 'class' => $class )
				);
			}
		);
	}

	/**
	 * Square admin page HTML. This is embedded on the page ?page=peachpay&section=payments
	 */
	public static function do_admin_page() {
		?>
		<div id="square" class="peachpay-setting-section">
			<div>
				<?php
				require PeachPay::get_plugin_path() . '/core/payments/square/admin/views/html-square-connect.php';
				?>
			</div>
			<div>
				<?php
				$gateway_list = PeachPay_Square_Integration::get_payment_gateways();
				require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
				?>
			</div>
		</div>
		<?php
	}
}
PeachPay_Admin_Square_Integration::instance();
