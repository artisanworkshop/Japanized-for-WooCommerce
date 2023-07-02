<?php
/**
 * PeachPay PayPal Payment page view
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div id="paypal" class="peachpay-setting-section">
	<div>
		<?php
			// PayPal connect option.
			require PeachPay::get_plugin_path() . '/core/payments/paypal/admin/views/html-paypal-connect.php';
		?>
	</div>
	<?php if ( PeachPay_PayPal_Integration::connected() ) : ?>
		<div>
			<?php
				$gateway_list = PeachPay_PayPal_Integration::get_payment_gateways();
				require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
			?>
		</div>
	<?php endif; ?>
</div>
