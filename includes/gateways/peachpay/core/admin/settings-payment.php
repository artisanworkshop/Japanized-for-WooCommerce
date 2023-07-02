<?php
/**
 * PeachPay payment settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/payments/payment-threshold.php';

/**
 * Registers each payment option setting.
 */
function peachpay_settings_payment() {
	if ( ! peachpay_has_valid_key() && ! peachpay_is_test_mode() ) {
		add_settings_section(
			'peachpay_section_payment_cannot_continue',
			'',
			'peachpay_section_payment_cannot_continue_html',
			'peachpay'
		);
		return;
	}

	add_settings_section(
		'peachpay_section_payment',
		'',
		'__return_true',
		'peachpay'
	);

	add_settings_section(
		'peachpay_payment_settings_section',
		'',
		'peachpay_payment_settings_section_cb',
		'peachpay'
	);
}

/**
 * Renders all parts of the pament settings.
 */
function peachpay_payment_settings_section_cb() {
	?>
	<div class='pp-static-header'>
		<?php
		peachpay_field_test_mode_cb();
		?>
		<div class="pp-save-button-section">
			<?php submit_button( 'Save changes', 'pp-button-primary' ); ?>
		</div>
	</div>
	<?php

	peachpay_payment_sub_nav();

	do_action( 'peachpay_admin_add_payment_setting_section' );

	add_settings_field(
		'peachpay_cod_check_bacs_setting',
		__( 'WooCommerce methods', 'peachpay-for-woocommerce' ),
		'peachpay_cod_check_bacs_setting_section',
		'peachpay',
		'peachpay_payment_settings_section',
		array( 'class' => 'pp-header pp-sub-nav-peachpay no-border-bottom' )
	);
}

/**
 * Renders the payment settings horizontal sub-navigation tabs.
 */
function peachpay_payment_sub_nav() {
	?>
	<div class='pp-flex-row pp-section-nav-container pp-sub-nav-controller'>
		<?php
		$buttons = array(
			array(
				'href'  => 'stripe',
				'title' => 'Stripe',
			),
			array(
				'href'  => 'square',
				'title' => 'Square',
			),
			array(
				'href'  => 'paypal',
				'title' => 'PayPal',
			),
			array(
				'href'  => 'poynt',
				'title' => 'GoDaddy Poynt',
			),
			array(
				'href'  => 'authnet',
				'title' => 'Authorize.net',
			),
			array(
				'href'  => 'amazonpay',
				'title' => 'Amazon Pay',
			),
			array(
				'href'  => 'peachpay',
				'title' => 'Other',
			),
		);
		foreach ( $buttons as $button ) {
			?>
				<a class='pp-sub-nav-button' href='#<?php echo esc_attr( $button['href'] ); ?>'><?php echo esc_html( $button['title'] ); ?></a>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Renders the Cod, Check, and Bacs settings section.
 *
 * @return void
 */
function peachpay_cod_check_bacs_setting_section() {
	?>
	<div class="peachpay-setting-section">
		<p><?php esc_html_e( 'Cash on Delivery, Check, and Bank Transfer payment options will be available through PeachPay if they are turned on in', 'peachpay-for-woocommerce' ); ?>
			<a href="<?php /* phpcs:ignore */ echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>"><?php esc_html_e('WooCommerce', 'peachpay-for-woocommerce'); ?></a>.
		</p>
	</div>
	<?php
}

/**
 * Renders the test mode option.
 */
function peachpay_field_test_mode_cb() {
	?>
	<div>
		<div class="pp-switch-section">
			<div>
				<label class="pp-switch">
					<input id="peachpay_test_mode" name="peachpay_payment_options[test_mode]" type="checkbox" value="1" <?php checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', 'test_mode' ), true ); ?>>
					<span class="pp-slider round"></span>
				</label>
			</div>
			<div>
				<label class="pp-setting-label" for="peachpay_test_mode"><?php esc_html_e( 'Enable test mode', 'peachpay-for-woocommerce' ); ?></label>
				<p class="description">
					<?php esc_html_e( 'In test mode, you can make test payments for each payment method. Find instructions on how to make a test payment in each tab below.', 'peachpay-for-woocommerce' ); ?>
				</p>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Renders the please give peachpay permission notice.
 */
function peachpay_section_payment_cannot_continue_html() {
	$retry_url = get_site_url() . '/wp-admin/admin.php?page=peachpay&retry_permission=1';
	?>
	<div class='pp-payment-uninitialized'>
		<img src='<?php echo esc_url( peachpay_url( '/public/img/pp-short-purple.svg' ) ); ?>'/>
		<div>
			<p><?php esc_html_e( 'To continue setting up PeachPay, please', 'peachpay-for-woocommerce' ); ?>&nbsp<a href="<?php echo esc_url( $retry_url ); ?>">&nbsp<?php esc_html_e( 'choose "Approve" on the permission screen', 'peachpay-for-woocommerce' ); ?></a>.</p>
		</div>
	</div>
	<?php
}

/**
 * Echos HTML escaped information if the array key exist.
 *
 * @param string $key The key to check if exists.
 * @param array  $array The array to check if the key exists in.
 */
function peachpay_echo_exist( $key, $array ) {
	if ( array_key_exists( $key, $array ) ) {
		echo esc_html( $array[ $key ] );
	}
}

/**
 * Display an alert if the merchant has connected at least one payment
 * method but has none selected to show in the checkout window.
 */
function peachpay_connected_payments_check() {
	if ( peachpay_is_test_mode() ) {
		return;
	}

	if ( peachpay_gateway_available() ) {
		// At least one of the connected payment methods is enabled.
		return;
	}

	// At this point, there must be at least one payment method connected but none of them are enabled.
	add_filter( 'admin_notices', 'peachpay_display_payment_method_notice' );
}

/**
 * Filter function for displaying admin notices.
 */
function peachpay_display_payment_method_notice() {
	?>
	<div class="error notice">
		<p>
			<?php
			esc_html_e(
				'You have disabled all PeachPay payment methods. The PeachPay checkout window will appear, but customers will have no way to pay. Please ',
				'peachpay-for-woocommerce'
			);
			$payment_settings = admin_url() . 'admin.php?page=peachpay&tab=payment';
			?>

			<a href="<?php echo esc_url_raw( $payment_settings ); ?>">

				<?php
				esc_html_e(
					'enable at least one payment method',
					'peachpay-for-woocommerce'
				);

				echo '</a>.'
				?>
		</p>
	</div>
	<?php
}
