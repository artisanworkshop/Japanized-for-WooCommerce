<?php
/**
 * PeachPay Poynt Payment page view
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div id="poynt" class="peachpay peachpay-setting-section">
	<div>
		<?php
			// Poynt connect view.
			require PeachPay::get_plugin_path() . '/core/payments/poynt/admin/views/html-poynt-connect.php';
		?>
	</div>
	<div>
		<?php
			$gateway_list = PeachPay_Poynt_Integration::get_payment_gateways();
			require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
		?>
	</div>
</div>
