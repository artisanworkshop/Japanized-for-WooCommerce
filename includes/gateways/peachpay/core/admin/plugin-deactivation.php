<?php
/**
 * Enqueues the scripts and styles for the deactivation popup and adds the placeholder for the modal
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues CSS styles for the plugin deactivation feedback pop up.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_deactivation_style( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'peachpay-deactivation-feedback',
		peachpay_url( 'core/admin/assets/css/deactivation-feedback.css' ),
		array(),
		peachpay_file_version( 'core/admin/assets/css/deactivation-feedback.css' )
	);
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_deactivation_style' );

/**
 * Enqueues scripts for the plugin deactivation feedback pop up modal as well
 * as root element for the pop up modal HTML.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_deactivation_script( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}

	add_action( 'admin_footer', 'peachpay_add_feedback_modal' );
}
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_deactivation_script' );

/**
 * Adds the div that will contain the deactivation form modal
 */
function peachpay_add_feedback_modal() {
	?>
	<div id="ppModal" class="ppModal">
		<div id="modal-content" class="modal-content">
			<div id="modal-header" class="modal-header">
				<span id="deactivation-header" class="deactivation-header">
					<?php esc_html_e( 'Feedback', 'peachpay-for-woocommerce' ); ?>
				</span>
			</div>
			<div id="modal-message" class="modal-message">
				<form id="peachpay-deactivate-feedback-form">
					<div id="peachpay-deactivate-feedback-form-caption" class="peachpay-deactivate-feedback-form-caption">
						<?php esc_html_e( "Please help us understand why PeachPay didn't work for you", 'peachpay-for-woocommerce' ); ?>
					</div>
					<div id="peachpay-deactivate-feedback-form-wrapper" class="peachpay-deactivate-feedback-form-wrapper">
						<div id="peachpay-deactivate-feedback-input-wrapper" class="feedback-input">
							<input id="peachpay-deactivate-feedback-no-longer-needed" class="peachpay-deactivate-feedback-input" type="radio" name="deactivation_reason" value="no-longer-needed">
							<label for="peachpay-deactivate-feedback-no-longer-needed" class="peachpay-deactivate-feedback-label">
								<?php esc_html_e( 'I no longer need the plugin', 'peachpay-for-woocommerce' ); ?>
							</label>
						</div>
						<div id="peachpay-deactivate-feedback-input-wrapper" class="feedback-input">
							<input id="peachpay-deactivate-feedback-better-plugin" class="peachpay-deactivate-feedback-input" type="radio" name="deactivation_reason" value="better-plugin">
							<label for="peachpay-deactivate-feedback-better-plugin" class="peachpay-deactivate-feedback-label">
								<?php esc_html_e( 'I found a better plugin', 'peachpay-for-woocommerce' ); ?>
							</label>
							<!-- Show if found a better plugin -->
							<input  id="better_plugin" class="reason_dropdown_input" type="text" name="deactivation_explanation" placeholder="<?php esc_html_e( 'Please share which plugin', 'peachpay-for-woocommerce' ); ?>" data-show-if="peachpay-deactivate-feedback-better-plugin" disabled required>
						</div>
						<div id="peachpay-deactivate-feedback-input-wrapper" class="feedback-input">
							<input id="peachpay-deactivate-feedback-couldnt-get-to-work" class="peachpay-deactivate-feedback-input" type="radio" name="deactivation_reason" value="couldnt-get-to-work">
							<label for="peachpay-deactivate-feedback-couldnt-get-to-work" class="peachpay-deactivate-feedback-label">
								<?php esc_html_e( "I couldn't get the plugin to work", 'peachpay-for-woocommerce' ); ?>
							</label>
							<!-- Show if could not get to work.-->
							<div id="was_support_contacted" class="reason_dropdown_input" data-show-if="peachpay-deactivate-feedback-couldnt-get-to-work">
								<img src="https://cdn3.iconfinder.com/data/icons/google-material-design-icons/48/ic_info_outline_48px-512.png" height="20" width="20">
								<span class="customer-support-ask">
									<?php esc_html_e( 'You can contact support@peachpay.app or use the', 'peachpay-for-woocommerce' ); ?>
								</span>
								<a href="<?php PeachPay_Admin::admin_settings_url( 'peachpay' ); ?>" class="customer-support-ask">
									<?php esc_html_e( 'support chat', 'peachpay-for-woocommerce' ); ?>
								</a>
							</div>
						</div>
						<div id="peachpay-deactivate-feedback-input-wrapper" class="feedback-input">
							<input id="peachpay-deactivate-feedback-temporary-deactivation" class="peachpay-deactivate-feedback-input" type="radio" name="deactivation_reason" value="temporary-deactivation">
							<label for="peachpay-deactivate-feedback-temporary-deactivation" class="peachpay-deactivate-feedback-label">
								<?php esc_html_e( 'Temporary deactivation', 'peachpay-for-woocommerce' ); ?>
							</label>
						</div>
						<div id="peachpay-deactivate-feedback-input-wrapper" class="feedback-input">
							<input id="peachpay-deactivate-feedback-other" class="peachpay-deactivate-feedback-input" type="radio" name="deactivation_reason" value="other">
							<label for="peachpay-deactivate-feedback-other" class="peachpay-deactivate-feedback-label">
								<?php esc_html_e( 'Other', 'peachpay-for-woocommerce' ); ?>
							</label>
							<!-- Show if other reason -->
							<input id="other_reason" class="reason_dropdown_input" type="text" name="deactivation_explanation" placeholder="<?php esc_html_e( 'Please share your reason', 'peachpay-for-woocommerce' ); ?>" data-show-if="peachpay-deactivate-feedback-other" disabled required>
						</div>
					</div>
					<div class="modal-buttons-wrapper">
						<button id="pp-deactivate-button" class="feedback-button-submit" name="form_submit" type="submit">
							<object id="loading-spinner" type="image/svg+xml" data="<?php echo esc_attr( PeachPay::get_asset_url( 'img/spinner.svg' ) ); ?>" height="20" width="20" class="pp-spinner hide">
							</object>
							<div id="pp-deactivate-content">
								<?php esc_html_e( 'Submit & deactivate', 'peachpay-for-woocommerce' ); ?>
							</div>
						</button>
						<a href="#" class="feedback-button-skip" id="peachpay-skip-feedback">
							<?php esc_html_e( 'Skip & deactivate', 'peachpay-for-woocommerce' ); ?>
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php
}
