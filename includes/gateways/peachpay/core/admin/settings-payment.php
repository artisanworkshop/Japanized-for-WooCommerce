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
			__( 'Payment methods', 'peachpay-for-woocommerce' ),
			'peachpay_section_payment_cannot_continue_html',
			'peachpay'
		);
		return;
	}

	// Stripe settings.

	add_settings_section(
		'peachpay_section_payment',
		__( 'Stripe', 'peachpay-for-woocommerce' ),
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_enable_stripe',
		__( 'Enable Stripe', 'peachpay-for-woocommerce' ),
		'peachpay_field_enable_stripe_html',
		'peachpay',
		'peachpay_section_payment'
	);

	add_settings_field(
		'peachpay_field_stripe',
		__( 'Connect', 'peachpay-for-woocommerce' ),
		'peachpay_field_stripe_cb',
		'peachpay',
		'peachpay_section_payment'
	);

	add_settings_field(
		'peachpay_field_stripe_payment_request',
		__( 'Apple Pay  & Google Pay', 'peachpay-for-woocommerce' ),
		'peachpay_field_stripe_payment_request_cb',
		'peachpay',
		'peachpay_section_payment',
	);

	add_settings_field(
		'peachpay_field_stripe_more_payment_options',
		__( 'Additional methods', 'peachpay-for-woocommerce' ),
		'peachpay_field_stripe_more_payment_options_cb',
		'peachpay',
		'peachpay_section_payment'
	);

	// PayPal settings.

	add_settings_section(
		'peachpay_section_paypal_payment',
		__( 'PayPal', 'peachpay-for-woocommerce' ),
		'peachpay_feedback_cb',
		'peachpay'
	);

	add_settings_field(
		'peachpay_field_paypal_box',
		__( 'Enable PayPal', 'peachpay-for-woocommerce' ),
		'peachpay_field_paypal_box_cb',
		'peachpay',
		'peachpay_section_paypal_payment',
		array( 'label_for' => 'peachpay_paypal_box' )
	);

	add_settings_field(
		'peachpay_field_paypal',
		__( 'Connect', 'peachpay-for-woocommerce' ),
		'peachpay_field_paypal_cb',
		'peachpay',
		'peachpay_section_paypal_payment'
	);

}

/**
 * Renders the please give peachpay permission notice.
 */
function peachpay_section_payment_cannot_continue_html() {
	$retry_url = get_site_url() . '/wp-admin/admin.php?page=peachpay&retry_permission=1';
	?>
	<p><?php esc_html_e( 'To continue setting up PeachPay, please', 'peachpay-for-woocommerce' ); ?>&nbsp<a href="<?php echo esc_url( $retry_url ); ?>">&nbsp<?php esc_html_e( 'choose "Approve" on the permission screen', 'peachpay-for-woocommerce' ); ?></a>.</p>
		<?php
}

/**
 * Renders the stripe signup or stripe connect details.
 */
function peachpay_field_stripe_cb() {
	if ( peachpay_is_test_mode() ) {
		?>
		<p><?php esc_html_e( 'Stripe is in test mode. You can use credit card number 4242 4242 4242 4242 with expiration 04/24 and CVC 444.', 'peachpay-for-woocommerce' ); ?></p>
			<?php
			return;
	}

	if ( get_option( 'peachpay_connected_stripe_account' ) ) {
		?>
		<p>
			<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( "You've successfully connected the Stripe account", 'peachpay-for-woocommerce' ); ?>&nbsp<strong><?php echo esc_html( get_option( 'peachpay_connected_stripe_account' )['id'] ); ?></strong>
		</p>
		<br>
		<a class="button button-unlink-stripe" href="?page=peachpay&tab=payment&unlink_stripe&merchant_store=<?php echo esc_url( get_site_url() ); ?>" ><?php esc_html_e( 'Unlink Stripe', 'peachpay-for-woocommerce' ); ?></a>
		<?php
	} else {
		?>
		<a
			href="<?php echo esc_url( peachpay_generate_stripe_url( get_site_url(), get_home_url() ) ); ?>"
			class="stripe-connect"
			target="_blank"
		>
			<span><?php esc_html_e( 'Connect with', 'peachpay-for-woocommerce' ); ?></span>
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

/**
 * Creates the Stripe connect signup link.
 *
 * @param string $site_url The admin URL of the current site.
 * @param string $home_url The home URL of the current site.
 */
function peachpay_generate_stripe_url( $site_url, $home_url ) {
	// phpcs:ignore
	$TEST_STRIPE_CLIENT_ID = 'ca_HHK0LPM3N7jbW1aV610tueC8zVOBtW2D';
	// phpcs:ignore
	$LIVE_STRIPE_CLIENT_ID = 'ca_HHK0N5DreIcJJAyqGbeOE77hAZD9gCFg';
	// phpcs:ignore
	$stripe_client_id = ( peachpay_is_local_development_site() || peachpay_is_staging_site() ) ? $TEST_STRIPE_CLIENT_ID : $LIVE_STRIPE_CLIENT_ID;

	$state               = new stdClass();
	$state->merchant_url = $home_url;
	$state->wp_admin_url = $site_url;

	// Using JSON to pass multiple parameters through state.
	$state_json = wp_json_encode( $state );
	// Base64 encode as JSON includes chars removed by esc_url().
	// phpcs:ignore
	$state_base64 = base64_encode( $state_json );

	$redirect_uri = peachpay_determine_stripe_redirect_uri();

	return "https://dashboard.stripe.com/oauth/v2/authorize?response_type=code&client_id=$stripe_client_id&scope=read_write&state=$state_base64&stripe_user[url]=$home_url&redirect_uri=$redirect_uri";
}

/**
 * Indicates if the current website is for local development.
 */
function peachpay_is_local_development_site() {
	switch ( get_home_url() ) {
		case 'https://store.local':
		case 'https://woo.store.local':
			return true;
		default:
			return false;
	}
}

/**
 * Indicates if the current website is a staging site.
 */
function peachpay_is_staging_site() {
	switch ( get_home_url() ) {
		case 'https://woo.peachpay.app':
		case 'https://theme1.peachpay.app':
		case 'https://theme2.peachpay.app':
		case 'https://theme3.peachpay.app':
		case 'https://theme4.peachpay.app':
		case 'https://theme5.peachpay.app':
		case 'https://qa.peachpay.app':
		case 'https://demo.peachpay.app':
			return true;
		default:
			return false;
	}
}

/**
 * Get the correct redirect URI that will be given to Stripe based on the
 * current environment.
 */
function peachpay_determine_stripe_redirect_uri() {
	if ( peachpay_is_local_development_site() ) {
		return 'https://dev.peachpay.local/connect/oauth';
	}

	if ( peachpay_is_staging_site() ) {
		return 'https://dev.peachpay.app/connect/oauth';
	}

	return 'https://prod.peachpay.app/connect/oauth';
}

/**
 * Renders the more stripe payment method options.
 */
function peachpay_field_stripe_more_payment_options_cb() {
	$stripe_account = get_option( 'peachpay_connected_stripe_account' );
	?>
		<p>
			<?php
			if ( $stripe_account ) {
				esc_html_e( 'On your ', 'peachpay-for-woocommerce' );
				// PHPCS:ignore ?>
				<a href="https://dashboard.stripe.com/<?php echo $stripe_account['id']; ?>/settings/connect/payment_methods">Stripe <?php esc_html_e( 'connect dashboard', 'peachpay-for-woocommerce' ); ?></a> 
				<?php
				esc_html_e( 'you can configure additional payment methods that will show up in the PeachPay checkout window.', 'peachpay-for-woocommerce' );
			} else {
				esc_html_e( 'On your ', 'peachpay-for-woocommerce' );
				?>
			<a href="https://dashboard.stripe.com/settings">Stripe <?php esc_html_e( 'connect dashboard', 'peachpay-for-woocommerce' ); ?></a> 
				<?php
				esc_html_e( 'you can configure additional payment methods that will show up in the PeachPay checkout window.', 'peachpay-for-woocommerce' );
			}
			?>
		</p>
		<h4><?php esc_html_e( 'Supported methods', 'peachpay-for-woocommerce' ); ?></h4>
		<ul style="list-style-type: disc; margin-left: 2rem">
			<li>
				Klarna 
			</li>
			<li>
				AfterPay
			</li>
		</ul>
    <p><?php esc_html_e( 'Learn more about additional <a href="https://stripe.com/payments/payment-methods-guide" target="_blank">payment methods</a> powered by Stripe and any associated <a href="https://stripe.com/pricing/local-payment-methods" target="_blank">fees</a>', 'peachpay-for-woocommerce' ); ?></p>
    <?php
}

/**
 * Renders the Stripe Google Pay and Apple Pay button for customers.
 */
function peachpay_field_stripe_payment_request_cb() {
	?>
	<input
		id="peachpay_stripe_payment_request"
		name="peachpay_payment_options[stripe_payment_request]"
		type="checkbox"
		value="1"
	<?php
		checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', 'stripe_payment_request' ), true );
		disabled( false, peachpay_is_test_mode() || get_option( 'peachpay_connected_stripe_account' ), true );
	?>
	>
	<label for="peachpay_stripe_payment_request">
		<?php esc_html_e( 'Show Apple Pay and Google Pay buttons above the PeachPay button', 'peachpay-for-woocommerce' ); ?>
	</label>
	<?php
}

/**
 * Renders the enable Stripe button option.
 */
function peachpay_field_enable_stripe_html() {
	?>
	<input
		id="peachpay_enable_stripe"
		name="peachpay_payment_options[enable_stripe]"
		type="checkbox"
		value="1"
	<?php
		checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', 'enable_stripe' ), true );
		disabled( false, peachpay_is_test_mode() || get_option( 'peachpay_connected_stripe_account' ), true );
	?>
	>
	<label for="peachpay_enable_stripe">
		<?php esc_html_e( 'Show Stripe in the checkout window', 'peachpay-for-woocommerce' ); ?>
	</label>
	<?php
}

/**
 * Renders the PayPal signup link or connected PayPal account info.
 */
function peachpay_field_paypal_cb() {
	$paypal_merchant_id = '';
	if ( get_option( 'peachpay_paypal_signup' ) ) {
		$paypal_merchant_id = peachpay_get_paypal_merchant_id();
	}

	if ( peachpay_is_test_mode() ) {
		?>
		<p>
			<?php esc_html_e( 'PayPal is in', 'peachpay-for-woocommerce' ); ?> <a target="_blank" href="https://docs.peachpay.app/paypal#test-mode"><?php esc_html_e( 'test mode', 'peachpay-for-woocommerce' ); ?></a>. <?php esc_html_e( 'You can use the guest checkout option to pay with PayPal in test mode.', 'peachpay-for-woocommerce' ); ?>
		</p>
		<?php
	} elseif ( get_option( 'peachpay_paypal_signup' ) ) {
		?>
		<p>
			<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( "You've successfully connected your PayPal account with Merchant ID", 'peachpay-for-woocommerce' ); ?>&nbsp<b><?php echo esc_html( $paypal_merchant_id ); ?></b>
		</p>
		<br>
		<a class="button button-unlink-paypal" href="?page=peachpay&tab=payment&unlink_paypal" ><?php esc_html_e( 'Unlink PayPal', 'peachpay-for-woocommerce' ); ?></a>
			<?php
	} else {
		?>
		<a href="<?php echo esc_url( peachpay_get_paypal_link() ); ?>" title="PayPal" target="_blank">
			<img
				src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-150px.png"
				alt="PayPal Logo"
				width="150"
				height="38">
			<div style="margin-top: 0.5rem;">
			<?php esc_html_e( 'Connect your PayPal business account to start accepting payments through PayPal', 'peachpay-for-woocommerce' ); ?>
			</div>
		</a>
			<?php
	}
}

/**
 * Gets the current PayPal merchant id.
 */
function peachpay_get_paypal_merchant_id() {
	$merchant_hostname = preg_replace( '(^https?://)', '', home_url() );
	$response          = wp_remote_get( peachpay_api_url() . 'api/v1/paypal/merchantAndClient?merchantHostname=' . $merchant_hostname );

	if ( ! peachpay_response_ok( $response ) ) {
		return __( 'An error occurred while looking up your PayPal account.', 'peachpay-for-woocommerce' );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( is_wp_error( $data ) || '' === $data['paypalMerchantID'] ) {
		delete_option( 'peachpay_paypal_signup' );
		return 'not found';
	}
	return $data['paypalMerchantID'];
}

/**
 * Creates a PayPal signup link.
 */
function peachpay_get_paypal_link() {
	$response = wp_remote_get( peachpay_api_url() . 'api/v1/paypal/signup?merchant_url=' . get_home_url() . '&wp_admin_url=' . get_site_url() );

	if ( ! peachpay_response_ok( $response ) ) {
		return;
	}

	$data = wp_remote_retrieve_body( $response );

	if ( is_wp_error( $data ) ) {
		return;
	}
	return $data;
}

/**
 * Renders the enable PayPal button option.
 */
function peachpay_field_paypal_box_cb() {
	?>
	<input
		id="peachpay_paypal_box"
		name="peachpay_payment_options[paypal]"
		type="checkbox"
		value="1"
	<?php
		checked( 1, peachpay_get_settings_option( 'peachpay_payment_options', 'paypal' ), true );
		disabled( false, peachpay_is_test_mode() || get_option( 'peachpay_paypal_signup' ), true );
	?>
	>
	<label for="peachpay_paypal_box">
		<?php esc_html_e( 'Show PayPal in the checkout window', 'peachpay-for-woocommerce' ); ?>
	</label>
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
