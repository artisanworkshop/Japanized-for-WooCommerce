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
require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/admin/settings-currency-switcher.php';

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
 * Enqueues CSS style for the PeachPay settings.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_settings_styles( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'peachpay-settings',
		peachpay_url( 'core/admin/assets/css/admin.css' ),
		array(),
		peachpay_file_version( 'core/admin/assets/css/admin.css' )
	);
	wp_enqueue_style(
		'peachpay-settings-button-preview',
		peachpay_url( 'public/css/peachpay.css' ),
		array(),
		peachpay_file_version( 'public/css/peachpay.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_settings_styles' );

/**
 * Load the script for the floating feedback form from the third-party that
 * we use called Elfsight.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_floating_feedback( $hook ) {
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
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_floating_feedback' );

/**
 * Enqueues the JS for the peachpay settings.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_settings_scripts( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'peachpay-settings',
		peachpay_url( 'core/admin/assets/js/settings.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/settings.js' ),
		false
	);
}
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_settings_scripts' );

/**
 * Enqueues the menu JS for the peachpay settings.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_menu_scripts( $hook ) {
	if ( 'toplevel_page_peachpay' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'peachpay-menu',
		peachpay_url( 'core/admin/assets/js/menu.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/menu.js' ),
		false
	);
}
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_menu_scripts' );

/**
 * Enqueues the translation JS for the peachpay settings.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_translations_scripts( $hook ) {
	if ( 'plugins.php' !== $hook && 'toplevel_page_peachpay' !== $hook ) {
		return;
	}

	wp_enqueue_script(
		'peachpay-settings-translations',
		peachpay_url( 'public/js/translations.js' ),
		array(),
		peachpay_file_version( 'public/js/translations.js' ),
		false
	);

	// I think plugin_asset_url was added at some point to this data even though
	// it does not relate to the translations. For example, the button previews
	// depend on this piece of data, so where we load the button previews we
	// must also load this translation script. This could probably be decoupled.
	wp_localize_script(
		'peachpay-settings-translations',
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
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_translations_scripts' );

/**
 * Hide WordPress nags in our settings page. This is because it interferes with our styling so we supress them in Peachpay's settings.
 */
function peachpay_hide_nag() {
	if ( get_current_screen()->base === 'toplevel_page_peachpay' ) {
		remove_action( 'admin_notices', 'update_nag', 10 );
		remove_action( 'admin_notices', 'maintenance_nag', 10 );
	}
}
add_action( 'admin_head', 'peachpay_hide_nag', 10 );

/**
 * Registers each peachpay settings tab.
 */
function peachpay_settings_init() {
	register_setting( 'peachpay_button', 'peachpay_button_options' );
	register_setting( 'peachpay_general', 'peachpay_general_options' );
	register_setting( 'peachpay_payment', 'peachpay_payment_options' );
	register_setting( 'peachpay_field', 'peachpay_field_editor' );
	register_setting( 'peachpay_related_products', 'peachpay_related_products_options' );
	register_setting( 'peachpay_currency', 'peachpay_currency_options' );
	if ( ( ( isset( $_GET['tab'] ) && 'general' === $_GET['tab'] ) || ! isset( $_GET['tab'] ) ) && peachpay_user_role( 'administrator' ) ) {
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
	} if ( isset( $_GET['tab'] ) && 'currency' === $_GET['tab'] && peachpay_user_role( 'administrator' ) ) {
		peachpay_settings_currency_switch();
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
		__( 'Payment methods', 'peachpay-for-woocommerce' ),
		__( 'Payment methods', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_payment_methods',
		'peachpay_payment_methods_page'
	);

	add_submenu_page(
		'peachpay',
		__( 'Button preferences', 'peachpay-for-woocommerce' ),
		__( 'Button preferences', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_button_preferences',
		'peachpay_button_preferences_page'
	);

	add_submenu_page(
		'peachpay',
		__( 'Field editor', 'peachpay-for-woocommerce' ),
		__( 'Field editor', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_field_editor',
		'peachpay_field_editor_page'
	);

	add_submenu_page(
		'peachpay',
		__( 'Related products', 'peachpay-for-woocommerce' ),
		__( 'Related products', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_related_products',
		'peachpay_related_products_page',
	);

	add_submenu_page(
		'peachpay',
		__( 'Currency', 'peachpay-for-woocommerce' ),
		__( 'Currency', 'peachpay-for-woocommerce' ),
		'manage_options',
		'peachpay_currency_switch',
		'peachpay_currency_switcher_page'
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
 * Sets the location header for the currency switcher page.
 */
function peachpay_currency_switcher_page() {
	header( 'Location: /wp-admin/admin.php?page=peachpay&tab=currency' );
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
	<div class='peachpay-header'>
		<h1 class="peachpay-heading"><?php echo esc_html( get_peachpay_logo_primary_peach_svg() ); ?></h1>
			<?php
				peachpay_generate_nav_bar();
			?>
	</div>
	<form action="options.php" method="post">
			<div class="wrap">
<!-- WordPress targets first h2 to put alerts so leaving this empty one here insures the alert is always placed here :D -->
				<h2></h2>
				<div id='peachpay_settings_container' class = 
					<?php
					if ( 'currency' === $tab ) {
						echo esc_html( 'peachpay_settings_container_currency' );
					}
					?>
				>
					<?php
						// Output security fields for the registered setting "peachpay".
						settings_fields( 'peachpay_' . $tab );

						// Output setting sections and their fields
						// (sections are registered for "peachpay", each field is registered to a specific section).
						do_settings_sections( 'peachpay' );

						// Output save settings button.
						submit_button( __( 'Save settings', 'peachpay-for-woocommerce' ) );
					?>
				</div>
			</div>
		</form>
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
	$merchant_hostname = preg_replace( '(^https?://)', '', home_url() );
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
 * Calls our server to check if the store has given us their WooCommerce API
 * keys.
 *
 * @return bool True if the store has given us their API keys, false otherwise.
 */
function peachpay_approved_wc_api_access() {
	$args = array(
		'body'        => array( 'domain' => home_url() ),
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
		'body'        => array( 'domain' => home_url() ),
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
		<a
			class="nav-tab <?php echo 'currency' === $tab ? 'nav-tab-active' : ''; ?>"
			href="<?php echo esc_url( add_query_arg( 'tab', 'currency' ) ); ?>"
		><?php esc_html_e( 'Currency', 'peachpay-for-woocommerce' ); ?>
		</a>
	</nav>
	<?php
}

/**
 * Renders the need help button for all settings section.
 */
function peachpay_feedback_cb() {
	?>
	<div class="elfsight-app-8ffcad85-9a1d-4fdf-a2d2-1ce2ec48b81c"></div>
	<?php
}

/**
 * Returns svg of the primary logo in peach
 */
function get_peachpay_logo_primary_peach_svg() {
	echo '<svg class="peachpay-logo" alt="PeachPay" width="466" height="98" viewBox="0 0 466 98" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M26.1516 34.3148C23.1944 32.5723 20.9803 30.4383 19.6931 28.7776C19.5853 28.6384 19.4839 28.5027 19.3892 28.3707C19.7811 28.0714 20.1691 27.7867 20.5533 27.5161C23.2839 25.5936 25.824 24.3863 28.1934 23.7297C33.2517 22.3279 37.5319 23.4364 41.2263 25.4559C42.432 26.1151 43.5752 26.8712 44.6628 27.6689C45.8468 28.5372 46.9647 29.4546 48.0252 30.3495C48.1093 30.4205 48.193 30.4912 48.2763 30.5619C48.5434 30.7882 48.8069 31.0127 49.0669 31.2342C49.2086 31.355 49.3494 31.475 49.4891 31.5938C49.5225 31.471 49.5562 31.3474 49.5905 31.223L49.6187 31.1211C49.9312 29.9941 50.2884 28.8066 50.728 27.6118C51.4061 25.7692 52.2803 23.9091 53.4893 22.2263C55.913 18.8528 59.6822 16.1918 65.9136 15.8131C68.5362 15.6537 71.595 15.8986 75.1732 16.6648C75.4187 16.7174 75.6669 16.7725 75.9173 16.8299L75.8749 16.9405C75.0059 19.2029 74.0965 21.5702 72.7767 23.8386C72.3715 24.5351 71.9277 25.2221 71.4344 25.8939C71.0515 26.4155 70.6387 26.928 70.1911 27.4287C68.5931 29.2157 66.5517 30.8506 63.8387 32.2077C64.5966 32.2105 65.3481 32.2547 66.0896 32.3392C68.9395 32.6637 71.6428 33.5823 74.0025 35.0179L74.9159 35.7877C75.0054 35.8619 75.095 35.9368 75.1846 36.0123L75.1848 36.0124C79.5929 39.7276 81.6453 44.5712 81.6453 50.1965C81.6456 53.6891 80.8547 57.4832 79.3452 61.4958C77.4083 66.6441 74.4344 71.5358 71.1603 75.4244C68.7156 78.3279 66.4133 80.3095 64.6107 81.4811C45.5925 78.9088 35.6085 72.2143 30.9725 66.0301C28.29 62.4519 27.0979 58.6856 27.0283 55.1258C27.0257 54.9962 27.0247 54.8671 27.0252 54.7381C27.0334 52.1316 27.6414 49.6445 28.703 47.4338C28.7409 47.3551 28.7789 47.2766 28.8172 47.1983L29.6793 45.403C31.6535 42.1228 34.5454 39.3645 37.7488 37.5071C33.6541 37.5456 30.0535 36.4134 27.1038 34.8477C26.7785 34.675 26.4609 34.4971 26.1516 34.3148ZM20.4593 42.2538C20.4322 42.2368 20.4052 42.2197 20.3782 42.2026C16.2922 39.6126 13.2917 36.5114 11.5691 34.1112L6 26.3513L13.5655 20.5723C22.6884 13.6036 31.6286 11.9515 39.9078 14.3797C41.9121 14.9676 43.7888 15.7813 45.5181 16.7046C46.6679 15.0687 48.053 13.4786 49.7309 12.0422C56.6328 6.13359 66.1738 4.57055 78.0573 7.29992L88.9808 9.8088L84.9244 20.3688L84.882 20.4794L84.7535 20.8144L84.7531 20.8155C83.9448 22.9233 82.9391 25.5463 81.3966 28.2907C81.3667 28.344 81.3366 28.3972 81.3062 28.4505C81.3278 28.4686 81.3492 28.4867 81.3708 28.5048L81.3715 28.5053L81.3708 28.5067C88.2556 34.3092 91.0952 41.6621 91.28 49.2997C91.2897 49.7017 91.2921 50.1044 91.2874 50.5078C91.0761 68.2556 77.0221 87.1719 66.3933 91.5389C30.8756 87.6765 17.3957 70.3801 17.3628 54.8544L17.3627 54.7662C17.3678 50.6824 18.3027 46.7233 20.0112 43.1659L20.0107 43.1654L20.0112 43.164C20.1581 42.8583 20.3074 42.5549 20.4593 42.2538Z" fill="#FF876C"/>
<path d="M61.357 65.027C61.5359 58.3764 60.0134 48.3728 53.5521 42.3502C52.2398 41.1268 51.7413 39.2218 52.5443 37.7377C53.3836 36.1863 55.2432 35.6696 56.5124 36.6871C63.3447 42.1647 68.1712 53.3871 67.2594 66.005C67.1448 67.5905 65.682 68.7114 64.0746 68.4828C62.4668 68.2543 61.3111 66.7322 61.357 65.027Z" fill="#FF876C" stroke="#FF876C"/>
<path d="M109.963 75V22.6364H131.594C135.514 22.6364 138.898 23.4034 141.744 24.9375C144.608 26.4545 146.815 28.5767 148.366 31.304C149.918 34.0142 150.693 37.1676 150.693 40.7642C150.693 44.3778 149.901 47.5398 148.315 50.25C146.747 52.9432 144.506 55.0312 141.591 56.5142C138.676 57.9972 135.216 58.7386 131.21 58.7386H117.864V48.767H128.858C130.767 48.767 132.361 48.4347 133.639 47.7699C134.935 47.1051 135.915 46.1761 136.58 44.983C137.244 43.7727 137.577 42.3665 137.577 40.7642C137.577 39.1449 137.244 37.7472 136.58 36.571C135.915 35.3778 134.935 34.4574 133.639 33.8097C132.344 33.1619 130.75 32.8381 128.858 32.8381H122.619V75H109.963ZM174.516 75.7415C170.408 75.7415 166.863 74.9318 163.88 73.3125C160.914 71.6761 158.63 69.3494 157.028 66.3324C155.442 63.2983 154.65 59.6932 154.65 55.517C154.65 51.4602 155.451 47.9148 157.053 44.8807C158.656 41.8295 160.914 39.4602 163.829 37.7727C166.744 36.0682 170.178 35.2159 174.133 35.2159C176.928 35.2159 179.485 35.6506 181.803 36.5199C184.121 37.3892 186.124 38.6761 187.812 40.3807C189.499 42.0852 190.812 44.1903 191.749 46.696C192.687 49.1847 193.156 52.0398 193.156 55.2614V58.3807H159.022V51.1193H181.522C181.505 49.7898 181.19 48.6051 180.576 47.5653C179.962 46.5256 179.119 45.7159 178.045 45.1364C176.988 44.5398 175.769 44.2415 174.388 44.2415C172.991 44.2415 171.738 44.5568 170.63 45.1875C169.522 45.8011 168.644 46.6449 167.996 47.7188C167.349 48.7756 167.008 49.9773 166.974 51.3239V58.7131C166.974 60.3153 167.289 61.7216 167.92 62.9318C168.55 64.125 169.445 65.054 170.604 65.7188C171.763 66.3835 173.144 66.7159 174.746 66.7159C175.854 66.7159 176.86 66.5625 177.763 66.2557C178.667 65.9489 179.442 65.4972 180.09 64.9006C180.738 64.304 181.224 63.571 181.548 62.7017L193.028 63.0341C192.55 65.608 191.502 67.8494 189.883 69.7585C188.281 71.6506 186.175 73.125 183.567 74.1818C180.96 75.2216 177.942 75.7415 174.516 75.7415ZM213.885 75.5625C210.987 75.5625 208.354 74.8125 205.984 73.3125C203.615 71.8125 201.723 69.5625 200.308 66.5625C198.893 63.5625 198.186 59.8381 198.186 55.3892C198.186 50.7699 198.919 46.9687 200.385 43.9858C201.851 41.0028 203.768 38.7955 206.138 37.3636C208.524 35.9318 211.089 35.2159 213.834 35.2159C215.896 35.2159 217.661 35.5739 219.126 36.2898C220.592 36.9886 221.803 37.892 222.757 39C223.712 40.108 224.436 41.2585 224.93 42.4517H225.186V35.7273H237.689V75H225.314V68.6335H224.93C224.402 69.8438 223.652 70.9773 222.68 72.0341C221.709 73.0909 220.49 73.9432 219.024 74.5909C217.575 75.2386 215.862 75.5625 213.885 75.5625ZM218.232 65.821C219.749 65.821 221.044 65.3949 222.118 64.5426C223.192 63.6733 224.018 62.4545 224.598 60.8864C225.178 59.3182 225.467 57.4773 225.467 55.3636C225.467 53.2159 225.178 51.3665 224.598 49.8153C224.036 48.2642 223.209 47.071 222.118 46.2358C221.044 45.4006 219.749 44.983 218.232 44.983C216.68 44.983 215.368 45.4091 214.294 46.2614C213.22 47.1136 212.402 48.3153 211.839 49.8665C211.294 51.4176 211.021 53.25 211.021 55.3636C211.021 57.4773 211.303 59.3182 211.865 60.8864C212.428 62.4545 213.237 63.6733 214.294 64.5426C215.368 65.3949 216.68 65.821 218.232 65.821ZM263.928 75.7415C259.786 75.7415 256.232 74.8892 253.266 73.1847C250.317 71.4801 248.05 69.1108 246.465 66.0767C244.88 63.0256 244.087 59.4972 244.087 55.4915C244.087 51.4687 244.88 47.9403 246.465 44.9062C248.067 41.8551 250.343 39.4773 253.292 37.7727C256.258 36.0682 259.795 35.2159 263.903 35.2159C267.533 35.2159 270.695 35.8722 273.388 37.1847C276.099 38.4972 278.212 40.3551 279.729 42.7585C281.263 45.1449 282.073 47.9489 282.158 51.1705H270.474C270.235 49.1591 269.553 47.5824 268.428 46.4403C267.32 45.2983 265.871 44.7273 264.082 44.7273C262.633 44.7273 261.363 45.1364 260.272 45.9545C259.181 46.7557 258.329 47.9489 257.715 49.5341C257.119 51.1023 256.82 53.0455 256.82 55.3636C256.82 57.6818 257.119 59.642 257.715 61.2443C258.329 62.8295 259.181 64.0312 260.272 64.8494C261.363 65.6506 262.633 66.0511 264.082 66.0511C265.241 66.0511 266.263 65.804 267.15 65.3097C268.053 64.8153 268.795 64.0909 269.374 63.1364C269.954 62.1648 270.32 60.9886 270.474 59.608H282.158C282.039 62.8466 281.229 65.6761 279.729 68.0966C278.246 70.517 276.158 72.4006 273.465 73.7472C270.789 75.0767 267.61 75.7415 263.928 75.7415ZM301.06 52.6023V75H288.557V22.6364H300.651V42.9375H301.085C301.972 40.517 303.42 38.625 305.432 37.2614C307.46 35.8977 309.94 35.2159 312.872 35.2159C315.634 35.2159 318.037 35.8295 320.082 37.0568C322.128 38.267 323.713 39.9801 324.838 42.196C325.98 44.4119 326.543 47.0028 326.526 49.9688V75H314.023V52.4233C314.04 50.2415 313.494 48.5369 312.386 47.3097C311.278 46.0824 309.719 45.4688 307.707 45.4688C306.395 45.4688 305.236 45.7585 304.23 46.3381C303.241 46.9006 302.466 47.7102 301.903 48.767C301.358 49.8239 301.077 51.1023 301.06 52.6023ZM334.33 75V22.6364H355.961C359.881 22.6364 363.265 23.4034 366.112 24.9375C368.975 26.4545 371.183 28.5767 372.734 31.304C374.285 34.0142 375.06 37.1676 375.06 40.7642C375.06 44.3778 374.268 47.5398 372.683 50.25C371.114 52.9432 368.873 55.0312 365.958 56.5142C363.043 57.9972 359.583 58.7386 355.577 58.7386H342.231V48.767H353.225C355.134 48.767 356.728 48.4347 358.006 47.7699C359.302 47.1051 360.282 46.1761 360.947 44.983C361.612 43.7727 361.944 42.3665 361.944 40.7642C361.944 39.1449 361.612 37.7472 360.947 36.571C360.282 35.3778 359.302 34.4574 358.006 33.8097C356.711 33.1619 355.117 32.8381 353.225 32.8381H346.987V75H334.33ZM394.869 75.5625C391.972 75.5625 389.338 74.8125 386.969 73.3125C384.599 71.8125 382.707 69.5625 381.293 66.5625C379.878 63.5625 379.17 59.8381 379.17 55.3892C379.17 50.7699 379.903 46.9687 381.369 43.9858C382.835 41.0028 384.753 38.7955 387.122 37.3636C389.509 35.9318 392.074 35.2159 394.818 35.2159C396.881 35.2159 398.645 35.5739 400.111 36.2898C401.577 36.9886 402.787 37.892 403.741 39C404.696 40.108 405.42 41.2585 405.915 42.4517H406.17V35.7273H418.673V75H406.298V68.6335H405.915C405.386 69.8438 404.636 70.9773 403.665 72.0341C402.693 73.0909 401.474 73.9432 400.009 74.5909C398.56 75.2386 396.847 75.5625 394.869 75.5625ZM399.216 65.821C400.733 65.821 402.028 65.3949 403.102 64.5426C404.176 63.6733 405.003 62.4545 405.582 60.8864C406.162 59.3182 406.452 57.4773 406.452 55.3636C406.452 53.2159 406.162 51.3665 405.582 49.8153C405.02 48.2642 404.193 47.071 403.102 46.2358C402.028 45.4006 400.733 44.983 399.216 44.983C397.665 44.983 396.352 45.4091 395.278 46.2614C394.205 47.1136 393.386 48.3153 392.824 49.8665C392.278 51.4176 392.006 53.25 392.006 55.3636C392.006 57.4773 392.287 59.3182 392.849 60.8864C393.412 62.4545 394.222 63.6733 395.278 64.5426C396.352 65.3949 397.665 65.821 399.216 65.821ZM434.225 89.7273C432.725 89.7273 431.31 89.608 429.981 89.3693C428.651 89.1477 427.509 88.8494 426.555 88.4744L429.316 79.3977C430.543 79.8068 431.651 80.0455 432.64 80.1136C433.646 80.1818 434.506 80.0199 435.222 79.6278C435.955 79.2528 436.518 78.5795 436.91 77.608L437.396 76.4318L423.435 35.7273H436.526L443.762 63.75H444.171L451.509 35.7273H464.677L449.873 78.7585C449.157 80.9063 448.143 82.7983 446.83 84.4347C445.535 86.0881 443.856 87.3835 441.793 88.321C439.748 89.2585 437.225 89.7273 434.225 89.7273Z" fill="#FF876C"/>
</svg>';
}
