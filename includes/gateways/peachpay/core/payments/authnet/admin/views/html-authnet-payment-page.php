<?php
/**
 * PeachPay Authnet Payment page view
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div id="authnet" class="peachpay peachpay-setting-section">
	<div>
		<?php
			// Authnet connect view.
			require PeachPay::get_plugin_path() . '/core/payments/authnet/admin/views/html-authnet-connect.php';
		?>
	</div>
	<div>
		<?php
			$gateway_list = PeachPay_Authnet_Integration::get_payment_gateways();
			require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
		?>
	</div>
</div>
