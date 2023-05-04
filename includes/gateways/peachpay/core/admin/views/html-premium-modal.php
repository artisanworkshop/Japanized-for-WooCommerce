<?php
/**
 * PeachPay Admin settings premium modal.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Generate the full premium upgrade modal with all functionality. Only the button needs to be
 * added just before calling this.
 */
$peachpay_premium_modal = function () {
	$peachpay_premium_config = peachpay_plugin_get_capability_config( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) );
	?>
		<div id="pp-premium-modal">
			<div class="premium-modal-content">
				<span id="premium-modal-close" class="premium-modal-close">&times;</span>

				<p class="premium-modal-header">
				<?php echo isset( $peachpay_premium_config['canceled'] ) ? esc_html_e( 'Start PeachPay', 'peachpay-for-woocommerce' ) : esc_html_e( 'Start your trial of PeachPay', 'peachpay-for-woocommerce' ); ?>
				<?php require PeachPay::get_plugin_path() . '/public/img/crown-icon.svg'; ?>
				<span style="color: #FF876C">Premium!</span>
				</p>

				<div class="feature-list">
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						All payment methods on the checkout page
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Field editor
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Currency switcher
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Express checkout
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Advanced product recommendations
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Advanced analytics
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Priority support
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Custom branding
					</p>
					<p class="feature-element">
						<?php require PeachPay::get_plugin_path() . '/public/img/checkmark-green.svg'; ?>
						Early access to new features
					</p>
				</div>

				<div class="premium-modal-actions">
					<form id="premium-monthly-form" action="<?php echo esc_url_raw( peachpay_api_url( 'prod' ) . 'api/v1/premium/checkoutPage?type=monthly' ); ?>" method="post">
						<input type="text" name="merchant_id" value="<?php echo esc_html( peachpay_plugin_merchant_id() ); ?>" style="visibility: hidden; position: absolute; top: -1000px; left: -1000px;" />
						<input type="text" name="return_url" value="<?php echo esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'payment' ) ); ?>" style="visibility: hidden; position: absolute; top: -1000px; left: -1000px;" />
						<div>
							<?php echo isset( $peachpay_premium_config['canceled'] ) ? '' : esc_html_e( '14-day trial then', 'peachpay-for-woocommerce' ); ?>
						</div>
						<button type="submit" class="button pp-button-secondary">
							<p class="premium-modal-actions-text">
								<?php echo esc_html_e( '$9.99 monthly', 'peachpay-for-woocommerce' ); ?>
							</p>
						</button>
					</form>

					<form id="premium-yearly-form" action="<?php echo esc_url_raw( peachpay_api_url( 'prod' ) . 'api/v1/premium/checkoutPage?type=yearly' ); ?>" method="post">
						<input type="text" name="merchant_id" value="<?php echo esc_html( peachpay_plugin_merchant_id() ); ?>" style="visibility: hidden; position: absolute; top: -1000px; left: -1000px;" />
						<input type="text" name="return_url" value="<?php echo esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'payment' ) ); ?>" style="visibility: hidden; position: absolute; top: -1000px; left: -1000px;" />
						<div>
							<?php echo isset( $peachpay_premium_config['canceled'] ) ? '' : esc_html_e( '14-day trial then', 'peachpay-for-woocommerce' ); ?>
						</div>
						<button type="submit" class="button pp-button-primary">
							<p class="premium-modal-actions-text">
								<?php echo esc_html_e( '$99 annually', 'peachpay-for-woocommerce' ); ?>
							</p>
						</button>
					</form>
				</div>

				<p class="premium-modal-footer">
					Get 2 months free if you pay annually! Prices are in USD.
				</p>

			</div>
		</div>
	<?php
}
?>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		document.querySelector('body').insertAdjacentHTML('beforeend', `
			<?php echo esc_html( $peachpay_premium_modal() ); ?>
		`);
		const $premiumModal = document.querySelector('#pp-premium-modal');

		document.querySelectorAll('.pp-button-continue-premium').forEach((element) => {
			element.addEventListener('click', (event) => {
				event.preventDefault();
				$premiumModal.style.display = 'block';
			});
		});

		document.querySelector('#premium-modal-close').addEventListener('click', () => {
			$premiumModal.style.display = 'none';
		});

		window.addEventListener('click', (event) => {
			if(event.target === $premiumModal) {
				$premiumModal.style.display = 'none';
			}
		});
	});
</script>
