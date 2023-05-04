<?php
/**
 * PeachPay Amazon Pay payment settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/payments/payment-threshold.php';

/**
 * PeachPay Amazon Pay admin settings.
 *
 * @param string $current The key of the current payment section tab.
 */
function peachpay_amazonpay_admin_settings_section( $current ) {
	$class = 'pp-header pp-sub-nav-amazonpay';
	if ( 'pp-sub-nav-amazonpay' !== $current ) {
		$class .= ' hide';
	}
	add_settings_field(
		'peachpay_amazonpay_setting',
		__( 'Amazon Pay', 'peachpay-for-woocommerce' ),
		'peachpay_amazonpay_setting_section',
		'peachpay',
		'peachpay_payment_settings_section',
		array( 'class' => $class )
	);

}

add_action( 'peachpay_admin_add_payment_setting_section', 'peachpay_amazonpay_admin_settings_section' );
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_amazonpay_admin_scripts' );

/**
 * Adds required amazonpay admin scripts
 */
function peachpay_enqueue_amazonpay_admin_scripts() {
	if ( ! peachpay_amazonpay_account_connected() ) {
		wp_enqueue_script(
			'pp_amazonpay_connect_account',
			peachpay_url( 'core/payments/amazonpay/admin/js/connectAccount.js' ),
			array(),
			peachpay_file_version( 'core/payments/amazonpay/admin/js/connectAccount.js' ),
			true
		);

		wp_localize_script(
			'pp_amazonpay_connect_account',
			'pp_amazonpay_post_data',
			array(
				'status_callback' => get_rest_url( null, 'peachpay/v1/amazonpay/status' ),
			)
		);
	}
}

/**
 * Renders the Amazon Pay setting section.
 */
function peachpay_amazonpay_setting_section() {

	/**
	 * Here we need to figure out how to implement an Amazon Pay login auth system similar to Bolts
	 */
	?>
	<div class="peachpay-setting-section">
		<?php
			peachpay_field_amazonpay_connect_setting();

			peachpay_admin_input(
				'amazon-pay-enable',
				'peachpay_payment_options',
				'amazonpay_enable',
				1,
				__( 'Show Amazon Pay in the checkout window', 'peachpay-for-woocommerce' ),
				'',
				array(
					'input_type' => 'checkbox',
					'disabled'   => ! peachpay_amazonpay_account_connected(),
				)
			);

			peachpay_amazonpay_payment_option(
				'amazonpay',
				'Amazon Pay',
				peachpay_url( 'public/img/marks/amazon-pay-card.svg' ),
				__( 'If you have an Amazon account, you are ready to start using Amazon Pay wherever you see the Amazon Pay button. Simply click the button when checking out to use the information already stored in your Amazon account.', 'peachpay-for-woocommerce' ),
				'https://pay.amazon.com/help/201212280',
				array(
					'class'    => 'pp-card-top',
					'no-input' => true,
				)
			);
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the Amazon Pay connect signup/deactivate setting.
 */
function peachpay_field_amazonpay_connect_setting() {
	?>
	<div> 
	<?php
	$base_country = wc_get_base_location()['country'];
	if ( ! peachpay_get_amazonpay_spid( $base_country ) || ! peachpay_amazonpay_register_link( $base_country ) ) {
		?>
		<p><i><?php echo( esc_html_e( 'Amazon Pay is not currently supported in your region', 'peachpay-for-woocommerce' ) ); ?></i></p>
		<?php
	} else {
		if ( peachpay_amazonpay_account_connected() ) {
			$amazon_merchant_id = peachpay_amazonpay_account_connected()['merchant_id'];
			?>
			<p>
				<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( "You've successfully connected your Amazon Pay account with Merchant ID", 'peachpay-for-woocommerce' ); ?>&nbsp<b><?php echo esc_html( $amazon_merchant_id ); ?></b>
			</p>
			<a class="button pp-button-unlink" href="?page=peachpay&tab=payment&unlink_amazonpay" >
				<?php esc_html_e( 'Unlink Amazon Pay', 'peachpay-for-woocommerce' ); ?>
			</a>
			<?php
		} else {
			?>
			<a type="button" id="amazon-pay-connect-button" type="submit" class="pp-connect-payment" style="background-color: #231f20;" href=<?php echo esc_html( peachpay_amazonpay_signup_link() ); ?> target="_blank">
				<span><?php esc_html_e( 'Connect with', 'peachpay-for-woocommerce' ); ?></span>
				<img style="height: 100%; vertical-align: middle; width: 4rem; translate: 0rem 0.28rem;" src="<?php echo esc_attr( peachpay_url( 'public/img/marks/amazon-logo-white.svg' ) ); ?>"/>
			</a>
			<button type="button" id="amazon-pay-connect-loading" type="submit" class="pp-connect-payment pp-connect-payment-loading" style="display: none; background-color: white; border: 0.1rem solid black;" title="<?php esc_html_e( 'Awaiting response from Amazon', 'peachpay-for-woocommerce' ); ?>">
				<img style="color: black; vertical-align: middle; height: 1.2rem;" src="<?php echo esc_attr( peachpay_url( 'public/img/spinner-dark.svg' ) ); ?>"/>
			</button>
			<div id="amazon-pay-section-refresh" style="display: none">
				<button type="button" id="amazon-pay-refresh-button" type="submit" class="pp-connect-payment" style="background-color: #ff856c;">
					<span style="color: white"><?php esc_html_e( 'refresh', 'peachpay-for-woocommerce' ); ?> page</span>
				</button>
				<p id="success-message"><?php esc_html_e( 'Your Amazon account has successfully been linked with PeachPay! Please refresh page or save changes.', 'peachpay-for-woocommerce' ); ?></p>
				<p id="failed-message"><?php esc_html_e( 'An error was encountered while trying to link your Amazon account. If this error persists reach out to PeachPay support!', 'peachpay-for-woocommerce' ); ?></p>
			</div>
			<?php
		}
	}
	?>
	</div> 
	<?php
}

/**
 * Template function for rendering amazon pay payment methods.
 *
 * @param string       $key The payment type key.
 * @param string       $name The name of the payment method.
 * @param string|array $image The URL of the image for the payment method.
 * @param string       $description The description for the payment method.
 * @param string       $fees The fees short string details.
 * @param array        $options Any extra information needed for rendering.
 */
function peachpay_amazonpay_payment_option( $key, $name, $image, $description, $fees, $options = array() ) {
	?>
	<div class="pp-pm-container <?php peachpay_echo_exist( 'class', $options ); ?>"
	>

		<?php if ( ! ( array_key_exists( 'no-input', $options ) && $options['no-input'] ) ) { ?>
		<label>
		<?php } ?>

		<div class="pp-pm-main">
			<div>
				<?php if ( ! ( array_key_exists( 'no-input', $options ) && $options['no-input'] ) ) { ?>
					<input
						id="peachpay_amazonpay_<?php echo esc_html( $key ); ?>"
						name="peachpay_payment_options[<?php echo esc_html( $key ); ?>]"
						type="checkbox"
						value="1"
						<?php
						checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', $key, false ) ? 1 : 0, true );
						?>
					>
					<?php
				}
				?>
			</div>
			<?php if ( ! is_array( $image ) ) { ?>
			<div class="pp-pm-badge">
				<img src="<?php echo esc_url( $image ); ?>">
			</div>
			<?php } else { ?>
			<div class="pp-pm-mini-badge">
				<?php
				$length = count( $image ) - 1;
				for ( $index = 0; $index < $length; $index += 2 ) {
					?>
					<div>
						<img src="<?php echo esc_url( $image[ $index ] ); ?>">
						<img src="<?php echo esc_url( $image[ $index + 1 ] ); ?>">
					</div>
				<?php } ?>
			</div>
			<?php } ?>
			<div class="pp-mobile-vertical pp-fill">
				<div class="pp-pm-body">
					<div>
						<h3>
						<?php echo esc_html( $name ); ?>
						</h3>
					</div>
					<div>
						<p>
						<?php echo esc_html( $description ); ?>
						</p>
					</div>
				</div>
				<div class="pp-pm-right-col">
					<div class="pp-pm-fees">
						<a href="<?php echo esc_url( $fees ); ?>" target="_blank">
							<?php echo esc_html_e( 'View Amazon fees', 'peachpay-for-woocommerce' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>

		<?php do_action( 'peachpay_action_amazonpay_' . $key . '_extra_settings' ); ?>

		<?php if ( ! ( array_key_exists( 'no-input', $options ) && $options['no-input'] ) ) { ?>
		</label>
		<?php } ?>
	</div>
	<?php
}
