<?php
/**
 * PeachPay payment settings.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Registers each payment option setting.
 */
function peachpay_settings_payment() {

	if ( peachpay_approved_wc_api_access() ) {
		update_option( 'peachpay_valid_key', true );
	}

	if ( ! get_option( 'peachpay_valid_key' ) && ! peachpay_is_test_mode() ) {
		add_settings_section(
			'peachpay_section_payment_cannot_continue',
			__( 'Payment methods', 'woocommerce-for-japan' ),
			'peachpay_section_payment_cannot_continue_html',
			'peachpay'
		);
		return;
	}

	add_settings_section(
		'peachpay_section_payment',
		'',
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_payment_general_setting',
		__( 'General', 'woocommerce-for-japan' ),
		'peachpay_general_setting_section',
		'peachpay',
		'peachpay_section_payment',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_paypal_setting',
		__( 'PayPal', 'woocommerce-for-japan' ),
		'peachpay_paypal_setting_section',
		'peachpay',
		'peachpay_section_payment',
		array( 'class' => 'pp-header' )
	);

	add_settings_field(
		'peachpay_stripe_setting',
		__( 'Stripe', 'woocommerce-for-japan' ),
		'peachpay_stripe_setting_section',
		'peachpay',
		'peachpay_section_payment',
		array( 'class' => 'pp-header' )
	);
}

/**
 * Renders the payment test mode setting section.
 */
function peachpay_general_setting_section() {
	?>
	<div class="peachpay-setting-section">
		<?php
		// PeachPay payment test mode setting.
		peachpay_field_test_mode_cb();

		// Hide native checkout option.
		peachpay_admin_input(
			'peachpay-only-checkout',
			'peachpay_payment_options',
			'make_pp_the_only_checkout',
			1,
			__( 'Make PeachPay the only checkout method', 'woocommerce-for-japan' ),
			__( 'Hide WooCommerce native checkout buttons (not available in test mode)', 'woocommerce-for-japan' ),
			array(
				'input_type' => 'checkbox',
				'disabled'   => peachpay_is_test_mode(),
			)
		);
		?>
		<div>
			<?php submit_button( 'Save changes', 'pp-save-button' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the paypal setting section.
 */
function peachpay_paypal_setting_section() {
	?>
	<div class="peachpay-setting-section">
		<?php
			peachpay_field_paypal_cb();

			peachpay_admin_input(
				'paypal-enable',
				'peachpay_payment_options',
				'paypal',
				1,
				__( 'Show PayPal in the checkout window', 'woocommerce-for-japan' ),
				'',
				array(
					'input_type' => 'checkbox',
					'disabled'   => ! peachpay_is_test_mode() && ! get_option( 'peachpay_paypal_signup' ),
				)
			);
		?>
		<div>
			<?php submit_button( 'Save changes', 'pp-save-button' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the stripe setting section.
 */
function peachpay_stripe_setting_section() {
	$peachpay_connected_stripe_account = get_option( 'peachpay_connected_stripe_account', array( 'id' => '' ) );

	$connect_id = is_array( $peachpay_connected_stripe_account ) ? $peachpay_connected_stripe_account['id'] : '';
	// Capabilities are fetched on the PHP side so the merchant does not see any changes briefly after page load.
	$capabilities = peachpay_fetch_stripe_capabilities( $connect_id );
	?>
	<div class="peachpay-setting-section">
		<?php
			// Stripe connect option.
			peachpay_field_stripe_cb();

			// Stripe PeachPay checkout enable.
			peachpay_admin_input(
				'stripe-enable',
				'peachpay_payment_options',
				'enable_stripe',
				1,
				__( 'Show Stripe in the checkout window', 'woocommerce-for-japan' ),
				'',
				array(
					'input_type' => 'checkbox',
					'disabled'   => ! peachpay_is_test_mode() && ! get_option( 'peachpay_connected_stripe_account' ),
				)
			);

			// Card Payment method.
			peachpay_stripe_payment_option(
				'card_payments',
				__( 'Credit card / Debit card', 'woocommerce-for-japan' ),
				array(
					peachpay_url( 'public/img/marks/mini-visa.svg' ),
					peachpay_url( 'public/img/marks/mini-master.svg' ),
					peachpay_url( 'public/img/marks/mini-amex.svg' ),
					peachpay_url( 'public/img/marks/mini-diners.svg' ),
				),
				__( 'Credit and debit cards (Visa, Mastercard, American Express, Discover and Diners, China UnionPay, JCB, Cartes Bancaires, Interac) are a dominant payment method globally, accounting for 41% of online payments.', 'woocommerce-for-japan' ),
				'https://stripe.com/pricing/local-payment-methods',
				array(
					'class'                       => 'pp-card-top',
					'no-input'                    => true,
					'data-stripe-capability-type' => 'card_payments',
					'data-stripe-capability'      => peachpay_stripe_capability( $capabilities, 'card_payments' ),
				)
			);

			// Apple Pay/ Google Pay payment method.
			peachpay_stripe_payment_option(
				'stripe_payment_request',
				'Apple Pay / Google Pay',
				peachpay_url( 'public/img/marks/apple-google.svg' ),
				__( 'Apple Pay is a wallet that allows customers to pay using payment details stored on their iPhone, iPad, or Apple Watch. Google Pay allows customers to pay with any payment method saved to their Google account.', 'woocommerce-for-japan' ),
				'https://stripe.com/pricing/local-payment-methods',
				array(
					'data-stripe-capability-type'     => 'card_payments',
					'data-stripe-capability'          => peachpay_stripe_capability( $capabilities, 'card_payments' ),
					'data-stripe-applepay-configured' => peachpay_get_settings_option( 'peachpay_apple_pay_settings_v2', 'apple_pay_domain_set', 'no' ) === 'yes',
				)
			);

			// Klarna payment method.
			peachpay_stripe_payment_option(
				'klarna_payments',
				'Klarna',
				peachpay_url( 'public/img/marks/klarna.svg' ),
				__( 'Klarna offers flexible payment options that give customers more freedom to choose when and how to pay for a purchase. Klarna provides payment solutions for 90 million consumers and over 200,000 businesses across 19 markets.', 'woocommerce-for-japan' ),
				'https://stripe.com/pricing/local-payment-methods',
				array(
					'data-stripe-capability-type' => 'klarna_payments',
					'data-stripe-capability'      => peachpay_stripe_capability( $capabilities, 'klarna_payments' ),
				)
			);

			// Afterpay / Clearpay payment method.
			peachpay_stripe_payment_option(
				'afterpay_clearpay_payments',
				'Afterpay / Clearpay',
				peachpay_url( 'public/img/marks/afterpay.svg' ),
				__( 'Afterpay (also known as Clearpay in the UK) offers customers more payment flexibility with no credit checks, no upfront fees, and no interest for on-time payments. Afterpay has more than 13M global customers and works with 75,000 brands and retailers.', 'woocommerce-for-japan' ),
				'https://stripe.com/pricing/local-payment-methods',
				array(
					'data-stripe-capability-type' => 'afterpay_clearpay_payments',
					'data-stripe-capability'      => peachpay_stripe_capability( $capabilities, 'afterpay_clearpay_payments' ),
				)
			);

			peachpay_field_stripe_more_info_cb();
		?>
		<div>
			<?php submit_button( 'Save changes', 'pp-save-button' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Renders the test mode option.
 */
function peachpay_field_test_mode_cb() {
	if ( peachpay_is_test_mode() && ! peachpay_get_settings_option( 'peachpay_payment_options', 'known_testmode' ) ) {
		peachpay_set_settings_option( 'peachpay_payment_options', 'known_testmode', 1 );
		peachpay_set_settings_option( 'peachpay_payment_options', 'stripe_payment_request', 1 );
		peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 1 );
		peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 1 );
	} elseif ( ! peachpay_is_test_mode() && peachpay_get_settings_option( 'peachpay_payment_options', 'known_testmode' ) ) {
		peachpay_set_settings_option( 'peachpay_payment_options', 'known_testmode', 0 );
		if ( get_option( 'peachpay_connected_stripe_account' ) ) {
			peachpay_set_settings_option( 'peachpay_payment_options', 'stripe_payment_request', 1 );
			peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 1 );
		} else {
			peachpay_set_settings_option( 'peachpay_payment_options', 'stripe_payment_request', 0 );
			peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 0 );
		}
		if ( get_option( 'peachpay_paypal_signup' ) ) {
			peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 1 );
		} else {
			peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 0 );
		}
	}

	?>
	<div class="pp-switch-section">
		<div>
			<label class="pp-switch">
				<input
					id="peachpay_test_mode"
					name="peachpay_payment_options[test_mode]"
					type="checkbox"
					value="1"
					<?php checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', 'test_mode' ), true ); ?>
				>
				<span class="pp-slider round"></span>
			</label>
		</div>
		<div>
			<label class="pp-setting-label" for="peachpay_test_mode"><?php esc_html_e( 'Enable test mode', 'woocommerce-for-japan' ); ?></label>
			<p class="description">
				<?php esc_html_e( 'Make test payments with or without a connected payment method.', 'woocommerce-for-japan' ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'For Stripe, use card number:', 'woocommerce-for-japan' ); ?>&nbsp<b>4242 4242 4242 4242</b>,&nbsp
				<?php esc_html_e( 'with expiration:', 'woocommerce-for-japan' ); ?>&nbsp<b>04/24</b> <?php esc_html_e( 'and CVC:', 'woocommerce-for-japan' ); ?>&nbsp<b>444</b>.&nbsp
				<?php esc_html_e( 'For PayPal, see', 'woocommerce-for-japan' ); ?>&nbsp<a target="_blank" href="https://docs.peachpay.app/paypal#test-mode"><?php esc_html_e( 'these instructions.', 'woocommerce-for-japan' ); ?></a>
			</p>
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
	<p><?php esc_html_e( 'To continue setting up PeachPay, please', 'woocommerce-for-japan' ); ?>&nbsp<a href="<?php echo esc_url( $retry_url ); ?>">&nbsp<?php esc_html_e( 'choose "Approve" on the permission screen', 'woocommerce-for-japan' ); ?></a>.</p>
		<?php
}

/**
 * Renders the stripe signup or stripe connect details.
 */
function peachpay_field_stripe_cb() {
	?>
	<div>
	<?php
	if ( peachpay_is_test_mode() ) {
		?>
			<p><?php esc_html_e( 'Stripe is in test mode. You can use credit card number 4242 4242 4242 4242 with expiration 04/24 and CVC 444.', 'woocommerce-for-japan' ); ?></p>
		<?php
	} else {

		if ( get_option( 'peachpay_connected_stripe_account' ) ) {
			?>
			<p>
				<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( "You've successfully connected the Stripe account", 'woocommerce-for-japan' ); ?>&nbsp<strong><?php echo esc_html( get_option( 'peachpay_connected_stripe_account' )['id'] ); ?></strong>
			</p>
			<br>
			<a class="button button-unlink-stripe" href="?page=peachpay&tab=payment&unlink_stripe&merchant_store=<?php echo esc_url( get_site_url() ); ?>" ><?php esc_html_e( 'Unlink Stripe', 'woocommerce-for-japan' ); ?></a>
			<?php
		} else {
			?>
			<a
				href="<?php echo esc_url( peachpay_stripe_signup_url( get_site_url(), get_home_url() ) ); ?>"
				class="stripe-connect"
				target="_blank"
			>
				<span><?php esc_html_e( 'Connect with', 'woocommerce-for-japan' ); ?></span>
			</a>
			<style>
				.stripe-connect {
					background: #635bff;
					display: inline-block;
					height: 38px;
					text-decoration: none;
					width: 180px;

					border-radius: 4px;
					-moz-border-radius: 4px;
					-webkit-border-radius: 4px;

					user-select: none;
					-moz-user-select: none;
					-webkit-user-select: none;
					-ms-user-select: none;

					-webkit-font-smoothing: antialiased;
				}

				.stripe-connect span {
					color: #ffffff;
					display: block;
					font-family: sohne-var, "Helvetica Neue", Arial, sans-serif;
					font-size: 15px;
					font-weight: 400;
					line-height: 14px;
					padding: 11px 0px 0px 24px;
					position: relative;
					text-align: left;
				}

				.stripe-connect:hover {
					background: #7a73ff;
				}

				.stripe-connect.slate {
					background: #0a2540;
				}

				.stripe-connect.slate:hover {
					background: #425466;
				}

				.stripe-connect.white {
					background: #ffffff;
				}

				.stripe-connect.white span {
					color: #0a2540;
				}

				.stripe-connect.white:hover {
					background: #f6f9fc;
				}

				.stripe-connect span::after {
					background-repeat: no-repeat;
					background-size: 49.58px;
					content: "";
					height: 20px;
					left: 62%;
					position: absolute;
					top: 28.95%;
					width: 49.58px;
				}

				/* Logos */
				.stripe-connect span::after {
					background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='utf-8'%3F%3E%3C!-- Generator: Adobe Illustrator 23.0.4, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3Csvg version='1.1' id='Layer_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' viewBox='0 0 468 222.5' style='enable-background:new 0 0 468 222.5;' xml:space='preserve'%3E%3Cstyle type='text/css'%3E .st0%7Bfill-rule:evenodd;clip-rule:evenodd;fill:%23FFFFFF;%7D%0A%3C/style%3E%3Cg%3E%3Cpath class='st0' d='M414,113.4c0-25.6-12.4-45.8-36.1-45.8c-23.8,0-38.2,20.2-38.2,45.6c0,30.1,17,45.3,41.4,45.3 c11.9,0,20.9-2.7,27.7-6.5v-20c-6.8,3.4-14.6,5.5-24.5,5.5c-9.7,0-18.3-3.4-19.4-15.2h48.9C413.8,121,414,115.8,414,113.4z M364.6,103.9c0-11.3,6.9-16,13.2-16c6.1,0,12.6,4.7,12.6,16H364.6z'/%3E%3Cpath class='st0' d='M301.1,67.6c-9.8,0-16.1,4.6-19.6,7.8l-1.3-6.2h-22v116.6l25-5.3l0.1-28.3c3.6,2.6,8.9,6.3,17.7,6.3 c17.9,0,34.2-14.4,34.2-46.1C335.1,83.4,318.6,67.6,301.1,67.6z M295.1,136.5c-5.9,0-9.4-2.1-11.8-4.7l-0.1-37.1 c2.6-2.9,6.2-4.9,11.9-4.9c9.1,0,15.4,10.2,15.4,23.3C310.5,126.5,304.3,136.5,295.1,136.5z'/%3E%3Cpolygon class='st0' points='223.8,61.7 248.9,56.3 248.9,36 223.8,41.3 '/%3E%3Crect x='223.8' y='69.3' class='st0' width='25.1' height='87.5'/%3E%3Cpath class='st0' d='M196.9,76.7l-1.6-7.4h-21.6v87.5h25V97.5c5.9-7.7,15.9-6.3,19-5.2v-23C214.5,68.1,202.8,65.9,196.9,76.7z'/%3E%3Cpath class='st0' d='M146.9,47.6l-24.4,5.2l-0.1,80.1c0,14.8,11.1,25.7,25.9,25.7c8.2,0,14.2-1.5,17.5-3.3V135 c-3.2,1.3-19,5.9-19-8.9V90.6h19V69.3h-19L146.9,47.6z'/%3E%3Cpath class='st0' d='M79.3,94.7c0-3.9,3.2-5.4,8.5-5.4c7.6,0,17.2,2.3,24.8,6.4V72.2c-8.3-3.3-16.5-4.6-24.8-4.6 C67.5,67.6,54,78.2,54,95.9c0,27.6,38,23.2,38,35.1c0,4.6-4,6.1-9.6,6.1c-8.3,0-18.9-3.4-27.3-8v23.8c9.3,4,18.7,5.7,27.3,5.7 c20.8,0,35.1-10.3,35.1-28.2C117.4,100.6,79.3,105.9,79.3,94.7z'/%3E%3C/g%3E%3C/svg%3E");
				}

				.stripe-connect.white span::after {
					background-image: url("data:image/svg+xml,%3C%3Fxml version='1.0' encoding='utf-8'%3F%3E%3C!-- Generator: Adobe Illustrator 24.0.3, SVG Export Plug-In . SVG Version: 6.00 Build 0) --%3E%3Csvg version='1.1' id='Layer_1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' viewBox='0 0 468 222.5' style='enable-background:new 0 0 468 222.5;' xml:space='preserve'%3E%3Cstyle type='text/css'%3E .st0%7Bfill-rule:evenodd;clip-rule:evenodd;fill:%230A2540;%7D%0A%3C/style%3E%3Cg%3E%3Cpath class='st0' d='M414,113.4c0-25.6-12.4-45.8-36.1-45.8c-23.8,0-38.2,20.2-38.2,45.6c0,30.1,17,45.3,41.4,45.3 c11.9,0,20.9-2.7,27.7-6.5v-20c-6.8,3.4-14.6,5.5-24.5,5.5c-9.7,0-18.3-3.4-19.4-15.2h48.9C413.8,121,414,115.8,414,113.4z M364.6,103.9c0-11.3,6.9-16,13.2-16c6.1,0,12.6,4.7,12.6,16H364.6z'/%3E%3Cpath class='st0' d='M301.1,67.6c-9.8,0-16.1,4.6-19.6,7.8l-1.3-6.2h-22v116.6l25-5.3l0.1-28.3c3.6,2.6,8.9,6.3,17.7,6.3 c17.9,0,34.2-14.4,34.2-46.1C335.1,83.4,318.6,67.6,301.1,67.6z M295.1,136.5c-5.9,0-9.4-2.1-11.8-4.7l-0.1-37.1 c2.6-2.9,6.2-4.9,11.9-4.9c9.1,0,15.4,10.2,15.4,23.3C310.5,126.5,304.3,136.5,295.1,136.5z'/%3E%3Cpolygon class='st0' points='223.8,61.7 248.9,56.3 248.9,36 223.8,41.3 '/%3E%3Crect x='223.8' y='69.3' class='st0' width='25.1' height='87.5'/%3E%3Cpath class='st0' d='M196.9,76.7l-1.6-7.4h-21.6v87.5h25V97.5c5.9-7.7,15.9-6.3,19-5.2v-23C214.5,68.1,202.8,65.9,196.9,76.7z'/%3E%3Cpath class='st0' d='M146.9,47.6l-24.4,5.2l-0.1,80.1c0,14.8,11.1,25.7,25.9,25.7c8.2,0,14.2-1.5,17.5-3.3V135 c-3.2,1.3-19,5.9-19-8.9V90.6h19V69.3h-19L146.9,47.6z'/%3E%3Cpath class='st0' d='M79.3,94.7c0-3.9,3.2-5.4,8.5-5.4c7.6,0,17.2,2.3,24.8,6.4V72.2c-8.3-3.3-16.5-4.6-24.8-4.6 C67.5,67.6,54,78.2,54,95.9c0,27.6,38,23.2,38,35.1c0,4.6-4,6.1-9.6,6.1c-8.3,0-18.9-3.4-27.3-8v23.8c9.3,4,18.7,5.7,27.3,5.7 c20.8,0,35.1-10.3,35.1-28.2C117.4,100.6,79.3,105.9,79.3,94.7z'/%3E%3C/g%3E%3C/svg%3E");
				}
			</style>
			<?php
		}
	}
	?>
	</div>
	<?php
}

/**
 * Renders the more stripe info.
 */
function peachpay_field_stripe_more_info_cb() {
	?>
	<div class="pp-stripe-container pp-card-bottom" style="display: block; text-align: center; min-height: 35px;">
		<p style="width: 100%;">
			<?php esc_html_e( 'Learn more about', 'woocommerce-for-japan' ); ?>
			<a href="https://stripe.com/payments/payment-methods-guide" target="_blank"><?php esc_html_e( 'payment methods', 'woocommerce-for-japan' ); ?></a>
			<?php esc_html_e( 'powered by Stripe and any associated', 'woocommerce-for-japan' ); ?>
			<a href="https://stripe.com/pricing/local-payment-methods" target="_blank">
				<?php esc_html_e( 'fees', 'woocommerce-for-japan' ); ?>
			</a>
		</p>
	</div>
	<style>
			.pp-stripe-container{
				background-color: white;
				border-top: 1pt solid rgba(0,0,0,0.1);
				min-height: 100px;
				padding: 1rem 0;
				margin: 0;
			}
			.pp-stripe-main{
				display: flex;
				flex-direction: row;
				align-items: center;
			}
			.pp-stripe-badge img{
				width: 4.5rem;
				padding: 0 1rem;
				display: inline-block;
			}
			.pp-stripe-mini-badge{
				display: flex;
				flex-direction: column;
				align-content: center;
				padding: 0 1rem 0 0;
			}
			.pp-stripe-mini-badge div {
				display: flex;
				flex-direction: row;
				align-content: center;
			}
			.pp-stripe-mini-badge img{
				display: inline-block;
				width: 3rem;
				padding: 0.2rem 0.2rem;
			}

			.pp-stripe-body{
				display: flex;
				flex-direction: column;
				margin: 0;
				padding: 0;
				flex-grow: 1;
			}

			.pp-stripe-body h3{
				margin: 0 0 5px;
				padding: 0;
				font-size: 1rem;
			}

			.pp-stripe-fees{
				white-space: nowrap;
				border-radius: 0.8rem;
				border: solid rgba(0,0,0) 1px;
				padding: 5px 0.5rem;
				margin: 0 0 0 1rem;
				font-size: smaller;
				opacity: 0.6;
				transition: opacity 0.2s;
			}

			.pp-stripe-fees a{
				display: inline-block;
				width: 125px;
				text-align: center;
				color: black;
				text-decoration: none;
			}
			.pp-stripe-fees:hover{
				opacity: 1;
			}

			.pp-mobile-vertical{
				display: flex;
				flex-direction: row;
				align-items: center;
			}

			.pp-needs-action{
				display: inline-block;
				background-color: rgba(0,0,0,0.1);
				padding: 5px 1rem;
				margin: 1rem 0 0;
				border-radius: 0.5rem;
				width: calc(100% - 2rem);
			}

			.pp-fill{
				flex-grow: 100;
			}
			tr.pp-condense-row, tr.pp-condense-row td{
				margin: 0;
				padding: 0;
			}

			.pp-card-top{
				overflow: hidden;
			}

			.pp-card-bottom{
				overflow: hidden;
			}

			@media screen and (max-width: 782px) {
				.pp-mobile-vertical{
					flex-direction: column;
				}

				.pp-stripe-fees{
					margin: 1rem 0 0;
				}
			}
		</style>
		<script>
			function peachpay_markActionNeeded(stripeMethod){
				const $input = document.querySelector(`input[name="peachpay_payment_options[${stripeMethod}]"]`);
				if(!$input){
					return;
				}

				const $container = $input.closest(".pp-stripe-container");
				if(!$container){
					return;
				}

				$container.insertAdjacentHTML( "beforeend", `
<span class="pp-needs-action">
<b>
	<?php esc_html_e( 'Action required:', 'woocommerce-for-japan' ); ?>
</b>
</br>
	<?php esc_html_e( 'This payment method must be activated in your', 'woocommerce-for-japan' ); ?>
	<a target="_blank" href="https://dashboard.stripe.com/test/settings/payment_methods">Stripe <?php esc_html_e( 'dashboard', 'woocommerce-for-japan' ); ?></a>
	<?php esc_html_e( 'settings to be used in the PeachPay checkout.', 'woocommerce-for-japan' ); ?>
</span>`);
			}

			function peachpay_markApplePayConfigError(){
				const $container = document.querySelector("[data-stripe-applepay-configured]");
				if(!$container){
					return;
				}

				const $input = $container.querySelector('input[type="checkbox"]');

				if(!$input || !$input.checked){
					return;
				}

				const status = $container.dataset.stripeApplepayConfigured;
				if(status){
					return
				}


				$container.insertAdjacentHTML( "beforeend", `
<span class="pp-needs-action">
<b>
	<?php esc_html_e( 'Setup failure:', 'woocommerce-for-japan' ); ?>
</b>
</br>
	<?php
	if ( peachpay_stripe_supported_applepay_url() ) {
		esc_html_e( 'Apple Pay domain registration failed. Apple Pay will not be shown to customers but Google Pay will continue to work.', 'woocommerce-for-japan' );
	} else {
		esc_html_e( 'Apple Pay is not supported on localhost sites. Google Pay will continue to work normally.', 'woocommerce-for-japan' );
	}
	?>
</span>`);
			}

			const $targets = document.querySelectorAll("[data-stripe-payment]");
			$targets.forEach(($el) => {
				const type = $el.dataset.stripePayment;
				const capabilityType = $el.dataset.stripeCapabilityType;
				const capability = $el.dataset.stripeCapability;

				if(capability !== "active"){
					peachpay_markActionNeeded(type);
				}
			});

			peachpay_markApplePayConfigError();
		</script>
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
 * Template function for rendering stripe payment methods.
 *
 * @param string       $key The payment type key.
 * @param string       $name The name of the payment method.
 * @param string|array $image The URL of the image for the payment method.
 * @param string       $description The description for the payment method.
 * @param string       $fees The fees short string details.
 * @param array        $options Any extra information needed for rendering.
 */
function peachpay_stripe_payment_option( $key, $name, $image, $description, $fees, $options = array() ) {
	$has_capability = get_option( 'peachpay_connected_stripe_account' ) && 'active' === $options['data-stripe-capability'];

	?>
	<div class="pp-stripe-container <?php peachpay_echo_exist( 'class', $options ); ?>"
		data-stripe-payment="<?php echo esc_html( $key ); ?>"
		data-stripe-capability-type="<?php echo esc_html( $options['data-stripe-capability-type'] ); ?>"
		data-stripe-capability="<?php echo esc_html( $options['data-stripe-capability'] ); ?>"
		<?php if ( array_key_exists( 'data-stripe-applepay-configured', $options ) ) { ?>
			data-stripe-applepay-configured="<?php echo esc_html( $options['data-stripe-applepay-configured'] ); ?>"
		<?php } ?>
	>


		<?php if ( ! ( array_key_exists( 'no-input', $options ) && $options['no-input'] ) ) { ?>
		<label>
		<?php } ?>


			<div class="pp-stripe-main">
				<div>
					<?php if ( ! ( array_key_exists( 'no-input', $options ) && $options['no-input'] ) ) { ?>
						<input
							id="peachpay_stripe_<?php echo esc_html( $key ); ?>"
							name="peachpay_payment_options[<?php echo esc_html( $key ); ?>]"
							type="checkbox"
							value="1"
							<?php
							checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', $key, false ) ? 1 : 0, true );
							disabled( false, $has_capability, true );
							?>
						>
						<?php
					}
					?>
				</div>
				<?php if ( ! is_array( $image ) ) { ?>
				<div class="pp-stripe-badge">
					<img src="<?php echo esc_url( $image ); ?>">
				</div>
				<?php } else { ?>
				<div class="pp-stripe-mini-badge">
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
					<div class="pp-stripe-body">
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
					<div class="pp-stripe-fees">
						<a href="<?php echo esc_url( $fees ); ?>" target="_blank">
							<?php echo esc_html_e( 'View Stripe fees', 'woocommerce-for-japan' ); ?>
						</a>
					</div>
				</div>
			</div>

		<?php if ( ! ( array_key_exists( 'no-input', $options ) && $options['no-input'] ) ) { ?>
		</label>
		<?php } ?>
	</div>
	<?php
}

/**
 * Renders the PayPal signup link or connected PayPal account info.
 */
function peachpay_field_paypal_cb() {
	?>
	<div> 
	<?php
	$paypal_merchant_id = '';
	if ( get_option( 'peachpay_paypal_signup' ) ) {
		$paypal_merchant_id = peachpay_get_paypal_merchant_id();
	}

	if ( peachpay_is_test_mode() ) {
		?>
		<p>
		<?php esc_html_e( 'PayPal is in', 'woocommerce-for-japan' ); ?> <a target="_blank" href="https://docs.peachpay.app/paypal#test-mode"><?php esc_html_e( 'test mode', 'woocommerce-for-japan' ); ?></a>. <?php esc_html_e( 'You can use the guest checkout option to pay with PayPal in test mode.', 'woocommerce-for-japan' ); ?>
		</p>
		<?php
	} elseif ( get_option( 'peachpay_paypal_signup' ) ) {
		?>
		<p>
			<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( "You've successfully connected your PayPal account with Merchant ID", 'woocommerce-for-japan' ); ?>&nbsp<b><?php echo esc_html( $paypal_merchant_id ); ?></b>
		</p>
		<br>
		<a class="button button-unlink-paypal" href="?page=peachpay&tab=payment&unlink_paypal" ><?php esc_html_e( 'Unlink PayPal', 'woocommerce-for-japan' ); ?></a>
		<?php
	} else {
		?>
		<a href="<?php echo esc_url( peachpay_paypal_signup_url() ); ?>" title="PayPal" target="_blank">
			<img
				src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-150px.png"
				alt="PayPal Logo"
				width="150"
				height="38">
			<div style="margin-top: 0.5rem;">
		<?php esc_html_e( 'Connect your PayPal business account to start accepting payments through PayPal', 'woocommerce-for-japan' ); ?>
			</div>
		</a>
		<?php
	}

	?>
	</div> 
	<?php
}

/**
 * Display an alert if the merchant has connected at least one payment
 * method but has none selected to show in the checkout window.
 */
function peachpay_connected_payments_check() {
	if ( peachpay_is_test_mode() ) {
		return;
	}

	if ( ! get_option( 'peachpay_connected_stripe_account' ) && ! get_option( 'peachpay_paypal_signup' ) ) {
		// No payment methods have been connected, so we don't need to show the alert.
		return;
	}

	if ( peachpay_get_settings_option( 'peachpay_payment_options', 'enable_stripe' ) || peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' ) ) {
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
