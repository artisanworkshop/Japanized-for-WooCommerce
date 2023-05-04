<?php
/**
 * PeachPay premium portal form.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

$peachpay_premium_portal = function () {
	?>
		<form
			id="peachpay-premium-subscription-portal-form"
			action="<?php echo esc_url_raw( peachpay_api_url( 'prod' ) . 'api/v1/premium/subscriptionPortal' ); ?>"
			method='post'
		>
			<input
				type='text'
				name='merchant_id'
				value='<?php echo esc_html( peachpay_plugin_merchant_id() ); ?>'
				style='visibility: hidden; position: absolute; top: -1000px; left: -1000px;'
			/>
			<input
				type='text'
				name='return_url'
				value='<?php echo esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'payment' ) ); ?>'
				style='visibility: hidden; position: absolute; top: -1000px; left: -1000px;'
			/>
		</form>
	<?php
}

?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelector('body').insertAdjacentHTML('beforeend', `
			<?php echo esc_html( $peachpay_premium_portal() ); ?>
		`);
	});
</script>
