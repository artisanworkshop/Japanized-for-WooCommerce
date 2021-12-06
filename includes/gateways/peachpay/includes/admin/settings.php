<?php

require_once plugin_dir_path( __FILE__ ) . 'settings-general.php';
require_once plugin_dir_path( __FILE__ ) . 'settings-payment.php';
require_once plugin_dir_path( __FILE__ ) . 'settings-button.php';
require_once plugin_dir_path( __FILE__ ) . 'plugin-deactivation.php';

define(
	'LOCALE_TO_LANGUAGE',
	array(
		'ar'    => 'العربية',
		'ca'    => 'Català',
		'cs-CZ' => 'Čeština',
		'da-DK' => 'Dansk',
		'de-DE' => 'Deutsch',
		'el'    => 'Ελληνικά',
		'en-US' => 'English (US)',
		'es-ES' => 'Español',
		'fr'    => 'Français',
		'hi-IN' => 'हिन्दी',
		'it'    => 'Italiano',
		'ja'    => '日本語',
		'ko-KR' => '한국어',
		'lb-LU' => 'Lëtzebuergesch',
		'nl-NL' => 'Nederlands',
		'pt-PT' => 'Português',
		'ro-RO' => 'Română',
		'ru-RU' => 'Русский',
		'sl-SI' => 'Slovenščina',
		'sv-SE' => 'Svenska',
		'th'    => 'ไทย',
		'uk'    => 'Українська',
		'zh-CN' => '简体中文',
		'zh-TW' => '繁體中文',
	)
);

define(
	'LANGUAGE_TO_LOCALE',
	array(
		'Detect from page' => 'detect-from-page',
		'Use WordPress setting - ' . peachpay_supported_language_lookup( peachpay_default_language() ) => peachpay_supported_locale_lookup( peachpay_default_language() ),
		'العربية'          => 'ar',
		'Català'           => 'ca',
		'Čeština'          => 'cs-CZ',
		'Dansk'            => 'da-DK',
		'Deutsch'          => 'de-DE',
		'Ελληνικά'         => 'el',
		'English (US)'     => 'en-US',
		'Español'          => 'es-ES',
		'Français'         => 'fr',
		'हिन्दी'           => 'hi-IN',
		'Italiano'         => 'it',
		'Română'           => 'ro-RO',
		'日本語'              => 'ja',
		'한국어'              => 'ko-KR',
		'Lëtzebuergesch'   => 'lb-LU',
		'Nederlands'       => 'nl-NL',
		'Português'        => 'pt-PT',
		'Română'           => 'ro-RO',
		'Русский'          => 'ru-RU',
		'Slovenščina'      => 'sl-SI',
		'Svenska'          => 'sv-SE',
		'ไทย'              => 'th',
		'Українська'       => 'uk',
		'简体中文'             => 'zh-CN',
		'繁體中文'             => 'zh-TW',
	)
);

function peachpay_supported_language_lookup( $locale ) {
	if ( ! isset( LOCALE_TO_LANGUAGE[ $locale ] ) ) {
		return 'English (US)';
	}
	return LOCALE_TO_LANGUAGE[ $locale ];
}

function peachpay_supported_locale_lookup( $locale ) {
	if ( ! isset( LOCALE_TO_LANGUAGE[ $locale ] ) ) {
		return 'en-US';
	}
	return $locale;
}

function enqueue_admin_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'admin.css',
		plugin_dir_url( __DIR__ ) . '../css/admin.css',
		array(),
		peachpay_file_version( 'css/admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_admin_style' );

/**
 * Loads the CSS for the button preview
 */
function enqueue_button_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'peachpay.css',
		plugin_dir_url( __DIR__ ) . '../css/peachpay.css',
		array(),
		peachpay_file_version( 'css/peachpay.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_button_style' );

/**
 * Load the script for the floating feedback form from the third-party that
 * we use called Elfsight.
 */
function enqueue_feedback( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'feedback',
		'https://apps.elfsight.com/p/platform.js',
		array(),
		1
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_feedback' );


function enqueue_button_js( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'admin-settings.js',
		plugin_dir_url( __DIR__ ) . '../js/admin-settings.js',
		array(),
		peachpay_file_version( 'js/admin-settings.js' )
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_button_js' );

function peachpay_settings_init() {
	register_setting( 'peachpay_button', 'peachpay_button_options' );
	register_setting( 'peachpay_general', 'peachpay_general_options' );
	register_setting( 'peachpay_payment', 'peachpay_payment_options' );
	if ( isset( $_GET['tab'] ) && 'general' === $_GET['tab'] && peachpay_user_role( 'administrator' ) || ! isset( $_GET['tab'] ) ) {
		peachpay_settings_general();
	}
	if ( isset( $_GET['tab'] ) && 'payment' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_settings_payment();
	}
	if ( isset( $_GET['tab'] ) && 'button' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_settings_button();
	}
}
add_action( 'admin_init', 'peachpay_settings_init' );

function peachpay_options_page() {
	add_menu_page(
		'PeachPay settings',
		'PeachPay',
		'read',
		'peachpay',
		'peachpay_options_page_html',
		'dashicons-cart',
		58,
	);
}
add_action( 'admin_menu', 'peachpay_options_page' );

function peachpay_options_page_html() {
	// Don't show the PeachPay settings to users who are not allowed to view
	// administration screens: https://wordpress.org/support/article/roles-and-capabilities/#read
	if ( ! current_user_can( 'read' ) ) {
		return;
	}

	// Check if the merchant has "Approved" our request to user their
	// store's WooCommerce API. The ask for permission appears on the screen
	// shows after activating the PeachPay plugin.
	if ( ! peachpay_get_settings_option( 'peachpay_general_options', 'test_mode' ) ) {
		update_option( 'peachpay_valid_key', peachpay_approved_wc_api_access() );

		if ( $account = peachpay_fetch_connected_stripe_account() ) {
			update_option( 'peachpay_connected_stripe_account', $account );
		} else {
			delete_option( 'peachpay_connected_stripe_account' );
			if ( is_array( get_option( 'peachpay_payment_options' ) ) ) {
				peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 0 );
			}
		}
	}

	peachpay_check_options_page_get_params();

	// Show error/success messages
	settings_errors( 'peachpay_messages' );
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			peachpay_generate_nav_bar();
			// Output security fields for the registered setting "peachpay"
			settings_fields( 'peachpay_' . $tab );

			// Output setting sections and their fields
			// (sections are registered for "peachpay", each field is registered to a specific section)
			do_settings_sections( 'peachpay' );

			// Output save settings button
			submit_button( 'Save settings' );
			?>
		</form>
	</div>
	<?php
}

/**
 * Check the get parameters on the URL to see if any actions need to be
 * performed.
 */
function peachpay_check_options_page_get_params() {
	if ( isset( $_GET['settings-updated'] ) ) {
		// If the merchant has not yet connected a payment method but enables
		// the payment method while in test mode, clear the setting as they are
		// leaving test mode.
		if ( ! peachpay_is_test_mode() ) {
			if ( ! is_array( get_option( 'peachpay_payment_options' ) ) ) {
				update_option( 'peachpay_payment_options', array() );
			}

			if ( ! get_option( 'peachpay_connected_stripe_account' ) ) {
				peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 0 );
			}

			if ( ! get_option( 'peachpay_paypal_signup' ) ) {
				peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 0 );
			}
		}

		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'Success! Your settings have been saved.', 'woocommerce-for-japan' ),
			'success'
		);
	}

	if ( isset( $_GET['connected_stripe'] ) && 'true' === $_GET['connected_stripe'] ) {
		// See PayPal version of this below for commentary.
		if ( ! is_array( get_option( 'peachpay_payment_options' ) ) ) {
			update_option( 'peachpay_payment_options', array() );
		}
		peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 1 );

		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have successfully connected your Stripe account. You may set up other payment methods in the "Payment methods" tab.', 'woocommerce-for-japan' ),
			'success'
		);
	}

	if ( isset( $_GET['connected_paypal'] ) && 'true' === $_GET['connected_paypal'] ) {
		// If no checkboxes under "peachpay_payment_options" are set, then when
		// you save the settings, the value is saved as an empty string instead
		// of empty array like one might assume, so we have to set it up in some
		// cases. Sometimes it saves as a 1 or 0 (the value of the checkbox?),
		// but either way, if it's not an array it's wrong.
		if ( ! is_array( get_option( 'peachpay_payment_options' ) ) ) {
			update_option( 'peachpay_payment_options', array() );
		}

		// Enable PayPal by default right after connecting a PayPal account.
		peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 1 );

		// Mark that the merchant has connected their PayPal account.
		update_option( 'peachpay_paypal_signup', true );

		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You have successfully connected your PayPal account. You may set up other payment methods in the "Payment methods" tab.', 'woocommerce-for-japan'),
			'success'
		);
	}

	if ( isset( $_GET['unlink_paypal'] ) && get_option( 'peachpay_paypal_signup' ) ) {
		peachpay_unlink_paypal();
	}

	if ( isset( $_GET['connect_payment_method_later'] ) ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You can enable test mode below and can finish setting up payment methods for PeachPay from the "Payment methods" tab.', 'woocommerce-for-japan' ),
			'info'
		);
	}
}

/**
 * Unlink merchant PayPal Account
 */
function peachpay_unlink_paypal() {
	if ( ! peachpay_unlink_paypal_request() ) {
		add_settings_error( 'peachpay_messages', 'peachpay_message', __( 'Unable to unlink PayPal account. Please try again or contact us if you need help.', 'woocommerce-for-japan' ), 'error' );
		return;
	}

	update_option( 'peachpay_paypal_signup', false );
	peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 0 );

	add_settings_error(
		'peachpay_messages',
		'peachpay_message',
		__( 'You have successfully unlinked your PayPal account. Please revoke the API permissions in your PayPal account settings as well.', 'woocommerce-for-japan' ),
		'success'
	);
}

/**
 * Get unlink merchant PayPal status
 */
function peachpay_unlink_paypal_request() {
	$merchant_hostname = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$response          = wp_remote_get( peachpay_api_url() . 'api/v1/paypal/merchant/unlink?merchantHostname=' . $merchant_hostname );

	if ( is_wp_error( $response ) ) {
		return 0;
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( is_wp_error( $data ) ) {
		return 0;
	}
	return $data['unlink_success'];
}

/**
 * Calls our server to check if the store has given us their WooCommerce API
 * keys.
 *
 * @return bool True if the store has given us their API keys, false otherwise.
 */
function peachpay_approved_wc_api_access() {
	$args = array(
		'body'        => array( 'domain' => get_site_url() ),
		'httpversion' => '2.0',
		'blocking'    => true,
	);

	$response = wp_remote_post(
		peachpay_api_url() . 'api/v1/plugin/woocommerce-api-keys',
		$args
	);

	if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'Something went wrong while trying to validate your plugin activation status.', 'woocommerce-for-japan' ),
			'error'
		);
		return false;
	}

	$data = json_decode(
		wp_remote_retrieve_body( $response ),
		true
	);

	return (bool) $data['hasWooCommerceAPIKeys'];
}

function peachpay_fetch_connected_stripe_account() {
	$args = array(
		'body'        => array( 'domain' => get_site_url() ),
		'httpversion' => '2.0',
		'blocking'    => true,
	);

	$response = wp_remote_post( peachpay_api_url() . 'api/v1/plugin/auth', $args );

	if ( is_wp_error( $response ) ) {
		add_settings_error( 'peachpay_messages', 'peachpay_message', __( 'Something went wrong while trying to validate your plugin activation status.', 'woocommerce-for-japan' ), 'error' );
		return false;
	}

	if ( 200 !== $response['response']['code'] ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	return json_decode( $body, true );
}

/**
 * Adds the "Settings" link for PeachPay in the list of installed plugins.
 *
 * @param array $links
 * @return array
 */
function peachpay_add_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=peachpay">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

function peachpay_default_language() {
	return peachpay_to_our_language_key( get_bloginfo( 'language' ) );
}

/**
 * Always returns what we use as the key in our translation files.
 *
 * There is a duplicate of this in peachpay.php
 */
function peachpay_to_our_language_key( $languageCodeOrLocale ) {
	// This is mostly for places like Germany, for example. Although they may
	// choose three different versions of German in WordPress, we only support
	// one. It can also be used generally.
	switch ( $languageCodeOrLocale ) {
		case 'cs':
			return 'cs-CZ';
		case 'da':
			return 'da-DK';
		case 'de':
		case 'de-AT':
		case 'de-DE':
		case 'de-CH':
			return 'de-DE';
		case 'en':
			return 'en-US';
		case 'es':
		case 'es-MX':
		case 'es-AR':
		case 'es-CL':
		case 'es-PE':
		case 'es-PR':
		case 'es-GT':
		case 'es-CO':
		case 'es-EC':
		case 'es-VE':
		case 'es-UY':
		case 'es-CR':
			return 'es-ES';
		case 'fr-BE':
		case 'fr-CA':
		case 'fr-FR':
			return 'fr';
		case 'hi':
			return 'hi-IN';
		case 'it-IT':
			return 'it';
		case 'ko':
			return 'ko-KR';
		case 'lb':
			return 'lb-LU';
		case 'nl':
		case 'nl-BE':
		case 'nl-NL':
			return 'nl-NL';
		case 'pt':
		case 'pt-AO':
		case 'pt-BR':
		case 'pt-PT-ao90':
		case 'pt-PT':
			return 'pt-PT';
		case 'ro':
			return 'ro-RO';
		case 'ru':
			return 'ru-RU';
		case 'sl':
			return 'sl-SI';
		case 'sv':
			return 'sv-SE';
		default:
			return $languageCodeOrLocale;
	}
}

/**
 * A function that generates the nav bar.
 * For now this is a very simple way of generating the nav bar.
 * ! In the future we might want to change this into something that uses a foreach loop to generate all the nav options
 */
function peachpay_generate_nav_bar() {
	?>
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<a
			href="<?php echo add_query_arg( 'tab', 'general' ); ?>"
			class="nav-tab <?php echo ( 'general' === $_GET['tab'] || ! isset( $_GET['tab'] ) ) ? 'nav-tab-active' : ''; ?>"
		> <?php _e( "General", 'woocommerce-for-japan');?>
		</a>
		<a
			class="nav-tab <?php echo 'payment' === $_GET['tab'] ? 'nav-tab-active' : ''; ?>"
			href="<?php echo add_query_arg( 'tab', 'payment' ); ?>"
		> <?php _e( "Payment methods", 'woocommerce-for-japan');?>
		</a>
		<a
			class="nav-tab <?php echo 'button' === $_GET['tab'] ? 'nav-tab-active' : ''; ?>"
			href="<?php echo add_query_arg( 'tab', 'button' ); ?>"
		> <?php _e( "Button preferences", 'woocommerce-for-japan');?>
		</a>
	</nav>
	<?php
}
