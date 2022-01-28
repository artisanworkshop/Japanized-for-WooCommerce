<?php
/**
 * Peachpay Settings.
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/util/util.php';
require_once PEACHPAY_ABSPATH . 'core/admin/settings-general.php';
require_once PEACHPAY_ABSPATH . 'core/admin/settings-payment.php';
require_once PEACHPAY_ABSPATH . 'core/admin/settings-button.php';
require_once PEACHPAY_ABSPATH . 'core/admin/plugin-deactivation.php';
require_once PEACHPAY_ABSPATH . 'core/modules/field-editor/admin/settings-field-editor.php';
require_once PEACHPAY_ABSPATH . 'core/modules/related-products/pp-related-products.php';

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
		'Detect from page'               => 'detect-from-page',
		'Use WordPress setting - ' . peachpay_supported_language_lookup( peachpay_default_language() ) => peachpay_supported_locale_lookup( peachpay_default_language() ),
		'العربية (Arabic)'               => 'ar',
		'Català (Catalan)'               => 'ca',
		'Čeština (Czech)'                => 'cs-CZ',
		'Dansk (Danish)'                 => 'da-DK',
		'Deutsch (German)'               => 'de-DE',
		'Ελληνικά (Greek)'               => 'el',
		'English'                        => 'en-US',
		'Español (Spanish)'              => 'es-ES',
		'Français (French)'              => 'fr',
		'हिन्दी (Hindi)'                 => 'hi-IN',
		'Italiano (Italian)'             => 'it',
		'日本語 (Japanese)'                 => 'ja',
		'한국어 (Korean)'                   => 'ko-KR',
		'Lëtzebuergesch (Luxembourgish)' => 'lb-LU',
		'Nederlands (Dutch)'             => 'nl-NL',
		'Português (Portuguese)'         => 'pt-PT',
		'Română (Romanian)'              => 'ro-RO',
		'Русский (Russian)'              => 'ru-RU',
		'Slovenščina (Slovenian)'        => 'sl-SI',
		'Svenska (Swedish)'              => 'sv-SE',
		'ไทย (Thai)'                     => 'th',
		'Українська (Ukrainian)'         => 'uk',
		'简体中文 (Simplified Chinese)'      => 'zh-CN',
		'繁體中文 (Traditional Chines)'      => 'zh-TW',
	)
);

/**
 * Gets a peachpay supported language.
 *
 * @param string $locale The locale to check.
 */
function peachpay_supported_language_lookup( $locale ) {
	if ( ! isset( LOCALE_TO_LANGUAGE[ $locale ] ) ) {
		return 'English (US)';
	}
	return LOCALE_TO_LANGUAGE[ $locale ];
}

/**
 * Gets a peachpay supported language locale.
 *
 * @param string $locale The locale to check.
 */
function peachpay_supported_locale_lookup( $locale ) {
	if ( ! isset( LOCALE_TO_LANGUAGE[ $locale ] ) ) {
		return 'en-US';
	}
	return $locale;
}

/**
 * Enqueues CSS style for the peachpay settings.
 *
 * @param string $hook Page level hook.
 */
function enqueue_admin_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'admin.css',
		peachpay_url( 'core/admin/assets/css/admin.css' ),
		array(),
		peachpay_file_version( 'core/admin/assets/css/admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_admin_style' );

/**
 * Loads the CSS for the button preview
 *
 * @param string $hook Page level hook.
 */
function enqueue_button_style( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'peachpay.css',
		peachpay_url( 'public/css/peachpay.css' ),
		array(),
		peachpay_file_version( 'public/css/peachpay.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_button_style' );

/**
 * Load the script for the floating feedback form from the third-party that
 * we use called Elfsight.
 *
 * @param string $hook Page level hook.
 */
function enqueue_feedback( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'feedback',
		'https://apps.elfsight.com/p/platform.js',
		array(),
		1,
		false
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_feedback' );

/**
 * Enqueues the JS for the peachpay settings.
 *
 * @param string $hook Page level hook.
 */
function enqueue_button_js( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'admin-settings.js',
		peachpay_url( 'core/admin/assets/js/settings.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/settings.js' ),
		false
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_button_js' );

/**
 * Enqueues the menu JS for the peachpay settings.
 *
 * @param string $hook Page level hook.
 */
function enqueue_menu_js( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'menu.js',
		peachpay_url( 'core/admin/assets/js/menu.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/menu.js' ),
		false
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_menu_js' );

/**
 * Enqueues the translation JS for the peachpay settings.
 */
function enqueue_translations_js() {
	wp_enqueue_script(
		'translations.js',
		peachpay_url( 'public/js/translations.js' ),
		array(),
		peachpay_file_version( 'public/js/translations.js' ),
		false
	);

	wp_localize_script(
		'translations.js',
		'peachpay_wordpress_settings',
		apply_filters(
			'peachpay_admin_script_data',
			array(
				'locale'           => get_locale(),
				'plugin_asset_url' => peachpay_url( '' ),
			)
		)
	);
}
add_action( 'admin_enqueue_scripts', 'enqueue_translations_js' );

/**
 * Registers each peachpay settings tab.
 */
function peachpay_settings_init() {
	register_setting( 'peachpay_button', 'peachpay_button_options' );
	register_setting( 'peachpay_general', 'peachpay_general_options' );
	register_setting( 'peachpay_payment', 'peachpay_payment_options' );
	register_setting( 'peachpay_field', 'peachpay_field_editor' );
	register_setting( 'peachpay_related_products', 'peachpay_related_products_options' );
	if ( ! isset( $_GET['tab'] ) || empty( $_GET['tab'] ) ) {
		$_GET['tab'] = 'general';
	}
	if ( isset( $_GET['tab'] ) && 'general' === $_GET['tab'] && peachpay_user_role( 'administrator' ) || ! isset( $_GET['tab'] ) ) {
		peachpay_settings_general();
	}
	if ( isset( $_GET['tab'] ) && 'payment' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_settings_payment();
	}
	if ( isset( $_GET['tab'] ) && 'button' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_settings_button();
	}
	if ( isset( $_GET['tab'] ) && 'field' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_field_editor();
	}
	if ( isset( $_GET['tab'] ) && 'related_products' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_related_products();
	}
	peachpay_connected_payments_check();
}
add_action( 'admin_init', 'peachpay_settings_init' );

/**
 * Registers peachpay sidebar link.
 */
function peachpay_options_page() {
	add_menu_page(
		__( 'PeachPay', 'peachpay-for-woocommerce' ),
		__( 'PeachPay', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay',
		'peachpay_options_page_html',
		'dashicons-cart',
		58,
	);

	add_submenu_page(
		'peachpay',
		__( 'Payment Methods', 'peachpay-for-woocommerce' ),
		__( 'Payment Methods', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_payment_methods',
		'peachpay_payment_methods_page'
	);

	add_submenu_page(
		'peachpay',
		__( 'Button Preferences', 'peachpay-for-woocommerce' ),
		__( 'Button Preferences', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_button_preferences',
		'peachpay_button_preferences_page'
	);

	add_submenu_page(
		'peachpay',
		__( 'Field Editor', 'peachpay-for-woocommerce' ),
		__( 'Field Editor', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_field_editor',
		'peachpay_field_editor_page'
	);

	add_submenu_page(
		'peachpay',
		__( 'Related Products', 'peachpay-for-woocommerce' ),
		__( 'Related Products', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_related_products',
		'peachpay_related_products_page'
	);

	// manually update text of first item
	// https://wordpress.stackexchange.com/a/98233.
	global $submenu;
	//phpcs:ignore
	$submenu['peachpay'][0][0] = __( 'General', 'peachpay-for-woocommerce' );
}
add_action( 'admin_menu', 'peachpay_options_page' );

/**
 * Sets the location header for the payment settings.
 */
function peachpay_payment_methods_page() {
	header( 'Location: /wp-admin/admin.php?page=peachpay&tab=payment' );
}

/**
 * Sets the location header for the button preferences.
 */
function peachpay_button_preferences_page() {
	header( 'Location: /wp-admin/admin.php?page=peachpay&tab=button' );
}

/**
 * Sets the location header for the field editor page.
 */
function peachpay_field_editor_page() {
	header( 'Location: /wp-admin/admin.php?page=peachpay&tab=field' );
}

/**
 * Sets the location header for the related product page.
 */
function peachpay_related_products_page() {
	header( 'Location: /wp-admin/admin.php?page=peachpay&tab=related_products' );
}

/**
 * Renders the settings page.
 */
function peachpay_options_page_html() {
	// Don't show the PeachPay settings to users who are not allowed to view
	// administration screens: https://wordpress.org/support/article/roles-and-capabilities/#read.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if the merchant has "Approved" our request to user their
	// store's WooCommerce API. The ask for permission appears on the screen
	// shows after activating the PeachPay plugin.
	update_option( 'peachpay_valid_key', peachpay_approved_wc_api_access() );

	if ( get_option( 'peachpay_valid_key' ) ) {
		delete_option( 'peachpay_api_access_denied' );
	}

	if ( ! peachpay_get_settings_option( 'peachpay_general_options', 'test_mode' ) ) {

		$account = peachpay_fetch_connected_stripe_account();
		if ( $account ) {
			update_option( 'peachpay_connected_stripe_account', $account );
		} else {
			delete_option( 'peachpay_connected_stripe_account' );

			if ( is_array( get_option( 'peachpay_payment_options' ) ) ) {
				peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 0 );
			}
		}
	}

	peachpay_check_options_page_get_params();

	// Show error/success messages.
	settings_errors( 'peachpay_messages' );
	//phpcs:ignore
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			peachpay_generate_nav_bar();
			// Output security fields for the registered setting "peachpay".
			settings_fields( 'peachpay_' . $tab );

			// Output setting sections and their fields
			// (sections are registered for "peachpay", each field is registered to a specific section).
			do_settings_sections( 'peachpay' );

			// Output save settings button.
			submit_button( __( 'Save settings', 'peachpay-for-woocommerce' ) );
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
			__( 'Success! Your settings have been saved.', 'peachpay-for-woocommerce' ),
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
			__( 'You have successfully connected your Stripe account. You may set up other payment methods in the "Payment methods" tab.', 'peachpay-for-woocommerce' ),
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
			__( 'You have successfully connected your PayPal account. You may set up other payment methods in the "Payment methods" tab.', 'peachpay-for-woocommerce' ),
			'success'
		);
	}

	if ( isset( $_GET['unlink_paypal'] ) && get_option( 'peachpay_paypal_signup' ) ) {
		peachpay_unlink_paypal();
	}

	if ( isset( $_GET['unlink_stripe'] ) && get_option( 'peachpay_connected_stripe_account' ) && isset( $_GET['merchant_store'] ) ) {
		peachpay_unlink_stripe( esc_url_raw( wp_unslash( $_GET['merchant_store'] ) ) );
	}

	if ( isset( $_GET['connect_payment_method_later'] ) ) {
		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'You can enable test mode below and can finish setting up payment methods for PeachPay from the "Payment methods" tab.', 'peachpay-for-woocommerce' ),
			'info'
		);
	}
}

/**
 * Unlink merchant PayPal Account
 */
function peachpay_unlink_paypal() {
	if ( ! peachpay_unlink_paypal_request() ) {
		add_settings_error( 'peachpay_messages', 'peachpay_message', __( 'Unable to unlink PayPal account. Please try again or contact us if you need help.', 'peachpay-for-woocommerce' ), 'error' );
		return;
	}

	update_option( 'peachpay_paypal_signup', false );
	peachpay_set_settings_option( 'peachpay_payment_options', 'paypal', 0 );

	add_settings_error(
		'peachpay_messages',
		'peachpay_message',
		__( 'You have successfully unlinked your PayPal account. Please revoke the API permissions in your PayPal account settings as well.', 'peachpay-for-woocommerce' ),
		'success'
	);
}

/**
 * Unlink merchant Stripe Account
 *
 * @param string $merchant_store stores the URL for MongoDB to filter by.
 */
function peachpay_unlink_stripe( $merchant_store ) {
	if ( ! peachpay_unlink_stripe_request( $merchant_store ) ) {
		add_settings_error( 'peachpay_messages', 'peachpay_message', __( 'Unable to unlink Stripe account. Please try again or contact us if you need help.', 'peachpay-for-woocommerce' ), 'error' );
		return;
	}

	update_option( 'peachpay_connected_stripe_account', false );
	peachpay_set_settings_option( 'peachpay_payment_options', 'enable_stripe', 0 );

	add_settings_error(
		'peachpay_messages',
		'peachpay_message',
		__( 'You have successfully unlinked your Stripe account.', 'peachpay-for-woocommerce' ),
		'success'
	);
}


/**
 * Get unlink merchant PayPal status
 */
function peachpay_unlink_paypal_request() {
	$merchant_hostname = wp_parse_url( get_site_url(), PHP_URL_HOST );
	$response          = wp_remote_get( peachpay_api_url() . 'api/v1/paypal/merchant/unlink?merchantHostname=' . $merchant_hostname );

	if ( ! peachpay_response_ok( $response ) ) {
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
 * Get unlink merchant Stripe status
 *
 * @param string $merchant_store stores the URL for MongoDB to filter by.
 */
function peachpay_unlink_stripe_request( $merchant_store ) {
	$stripe_id = get_option( 'peachpay_connected_stripe_account' )['id'];
	$response  = wp_remote_get( peachpay_api_url() . 'api/v1/stripe/merchant/unlink?stripeAccountId=' . $stripe_id . '&merchantStore=' . $merchant_store );

	if ( ! peachpay_response_ok( $response ) ) {
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
 * Indicates if a response is 2xx.
 *
 * @param array | WP_Error $response The response to check.
 */
function peachpay_response_ok( $response ) {
	$code = wp_remote_retrieve_response_code( $response );

	if ( ! is_int( $code ) ) {
		return false;
	}

	if ( $code < 200 || $code > 299 ) {
		return false;
	}

	return true;
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

	if ( ! peachpay_response_ok( $response ) ) {

		add_settings_error(
			'peachpay_messages',
			'peachpay_message',
			__( 'Something went wrong while trying to validate your plugin activation status.', 'peachpay-for-woocommerce' ),
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

/**
 * Gets the merchants connected stripe account.
 */
function peachpay_fetch_connected_stripe_account() {
	$args = array(
		'body'        => array( 'domain' => get_site_url() ),
		'httpversion' => '2.0',
		'blocking'    => true,
	);

	$response = wp_remote_post( peachpay_api_url() . 'api/v1/plugin/auth', $args );

	if ( is_wp_error( $response ) ) {
		add_settings_error( 'peachpay_messages', 'peachpay_message', __( 'Something went wrong while trying to validate your plugin activation status.', 'peachpay-for-woocommerce' ), 'error' );
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
 * @param array $links Settings link array.
 */
function peachpay_add_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=peachpay">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Gets the default store language.
 */
function peachpay_default_language() {
	return peachpay_to_our_language_key( get_bloginfo( 'language' ) );
}

/**
 * Always returns what we use as the key in our translation files.
 *
 * There is a duplicate of this in peachpay.php
 *
 * @param string $language_code_or_locale Raw language locale.
 */
function peachpay_to_our_language_key( $language_code_or_locale ) {
	// This is mostly for places like Germany, for example. Although they may
	// choose three different versions of German in WordPress, we only support
	// one. It can also be used generally.
	switch ( $language_code_or_locale ) {
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
			return $language_code_or_locale;
	}
}

/**
 * A function that generates the nav bar.
 * For now this is a very simple way of generating the nav bar.
 * ! In the future we might want to change this into something that uses a foreach loop to generate all the nav options
 */
function peachpay_generate_nav_bar() {
	//phpcs:ignore
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

	?>
	<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
		<a
			href="<?php echo esc_url( add_query_arg( 'tab', 'general' ) ); ?>"
			class="nav-tab <?php echo ( 'general' === $tab || ! isset( $tab ) ) ? 'nav-tab-active' : ''; ?>"
		> <?php esc_html_e( 'General', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a
			class="nav-tab <?php echo 'payment' === $tab ? 'nav-tab-active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( 'tab', 'payment' ) ); ?>"
		> <?php esc_html_e( 'Payment methods', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a
			class="nav-tab <?php echo 'button' === $tab ? 'nav-tab-active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( 'tab', 'button' ) ); ?>"
		> <?php esc_html_e( 'Button preferences', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a
			class="nav-tab <?php echo 'field' === $tab ? 'nav-tab-active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( 'tab', 'field' ) ); ?>"
		> <?php esc_html_e( 'Field editor', 'peachpay-for-woocommerce' ); ?>
		</a>
		<a
			class="nav-tab <?php echo 'related_products' === $tab ? 'nav-tab-active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( 'tab', 'related_products' ) ); ?>"
		> <?php esc_html_e( 'Related products', 'peachpay-for-woocommerce' ); ?>
		</a>
	</nav>
	<?php
}
