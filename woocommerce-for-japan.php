<?php
/**
 * Plugin Name: Japanized for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-for-japan/
 * Description: Woocommerce toolkit for Japanese use.
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * Version: 2.9.20
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * Requires at least: 6.7
 * Tested up to: 7.1
 * WC requires at least: 8.0
 * WC tested up to: 11.2.1
 *
 * Text Domain: woocommerce-for-japan
 * Domain Path: /i18n/
 *
 * @package woocommerce-for-japan
 * @category Core
 * @author Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'JP4WC_VERSION', '2.9.20' );

require_once __DIR__ . '/class-jp4wc.php';

// PHP security notice shared with other Artisan Workshop plugins. Shown without WooCommerce too.
// Optional: a request that runs while this plugin is updated goes on without it.
if ( is_readable( __DIR__ . '/includes/artws-php-security-notice/register.php' ) ) {
	include_once __DIR__ . '/includes/artws-php-security-notice/register.php';
}
if ( function_exists( 'artws_php_security_notice_register' ) ) {
	artws_php_security_notice_register( '1.0.0', __DIR__ . '/includes/artws-php-security-notice/class-notice.php', 'jp4wc_php_security_notice_strings' );
}

/**
 * Strings of the PHP security notice, translated with this plugin's text domain.
 *
 * The shared notice calls this when it displays, after init. The strings a
 * WordPress.org language pack lacks come from the bundled translation.
 *
 * @since 2.9.20
 * @return array
 */
function jp4wc_php_security_notice_strings() {
	return jp4wc_with_bundled_translation(
		static function () {
			return array(
				'title'          => __( 'Update PHP now: critical security vulnerabilities', 'woocommerce-for-japan' ),
				/* translators: 1: PHP version of this site, 2: first fixed PHP version, 3: vulnerability IDs */
				'vulnerable'     => __( 'The PHP version of this site (%1$s) has critical vulnerabilities (%3$s) that let an attacker run code on the server with a single request. Update PHP to %2$s or later right away.', 'woocommerce-for-japan' ),
				'support_ending' => __( 'Security support for PHP 8.2 ends at the end of 2026, so we recommend updating to PHP 8.3 or later.', 'woocommerce-for-japan' ),
				'vendor_patch'   => __( 'Your hosting company may have applied the fix without changing the PHP version number. Please check with your hosting company.', 'woocommerce-for-japan' ),
				/* translators: 1: PHP version of this site, 2: vulnerability IDs */
				'unsupported'    => __( 'The PHP version of this site (%1$s) has critical vulnerabilities (%2$s). Official support for this PHP version has ended, and no fixed version has been released for it. Some hosting companies apply their own security patches, but update to PHP 8.3 or later, which is supported longer, as soon as possible.', 'woocommerce-for-japan' ),
				/* translators: 1: PHP version of this site, 2: first fixed PHP version, 3: vulnerability IDs */
				'prerelease'     => __( 'This site runs a pre-release of PHP (%1$s) that has critical vulnerabilities (%3$s). Update it to %2$s or later.', 'woocommerce-for-japan' ),
				'learn_more'     => __( 'Learn more', 'woocommerce-for-japan' ),
				'dismiss'        => __( 'Don\'t show again', 'woocommerce-for-japan' ),
				'close'          => __( 'Close', 'woocommerce-for-japan' ),
			);
		}
	);
}

/**
 * Runs a callback with the strings the loaded translations lack taken from the bundled translation.
 *
 * A WordPress.org language pack wins over the bundled translation but has no
 * translation for new strings until they are translated there, so they would
 * be shown in English. Only the strings built while the callback runs are
 * looked up in the bundled file: __(), _x() and _n() and their esc_ variants
 * (_nx() is not covered).
 *
 * @since 3.0.0
 * @param callable $callback Callback that builds the strings.
 * @param mixed    ...$args  Arguments passed to the callback.
 * @return mixed What the callback returns.
 */
function jp4wc_with_bundled_translation( callable $callback, ...$args ) {
	// A nested call leaves the filters to the outer one, which removes them.
	$added = false === has_filter( 'gettext_woocommerce-for-japan', 'jp4wc_bundled_translation' );
	if ( $added ) {
		add_filter( 'gettext_woocommerce-for-japan', 'jp4wc_bundled_translation', 10, 2 );
		add_filter( 'gettext_with_context_woocommerce-for-japan', 'jp4wc_bundled_translation', 10, 3 );
		add_filter( 'ngettext_woocommerce-for-japan', 'jp4wc_bundled_plural_translation', 10, 4 );
	}
	try {
		return $callback( ...$args );
	} finally {
		if ( $added ) {
			remove_filter( 'gettext_woocommerce-for-japan', 'jp4wc_bundled_translation', 10 );
			remove_filter( 'gettext_with_context_woocommerce-for-japan', 'jp4wc_bundled_translation', 10 );
			remove_filter( 'ngettext_woocommerce-for-japan', 'jp4wc_bundled_plural_translation', 10 );
		}
	}
}

/**
 * Returns the bundled translation of a string the loaded translations lack.
 *
 * @since 3.0.0
 * @param string $translation Translation from the loaded files.
 * @param string $text        Text to translate.
 * @param string $context     Context of _x(), passed by gettext_with_context only.
 * @return string
 */
function jp4wc_bundled_translation( $translation, $text, $context = '' ) {
	if ( $translation !== $text ) {
		return $translation;
	}
	$forms = jp4wc_bundled_translation_forms( '' === $context ? $text : $context . "\4" . $text );
	return null !== $forms ? $forms[0] : $translation;
}

/**
 * Returns the bundled translation of a plural string the loaded translations lack.
 *
 * @since 3.0.0
 * @param string $translation Translation from the loaded files.
 * @param string $single      Singular text.
 * @param string $plural      Plural text.
 * @param int    $number      Number the form is chosen for.
 * @return string
 */
function jp4wc_bundled_plural_translation( $translation, $single, $plural, $number ) {
	// Without a translation, _n() returns one of the texts.
	if ( $translation !== $single && $translation !== $plural ) {
		return $translation;
	}
	$forms = jp4wc_bundled_translation_forms( $single );
	if ( null === $forms ) {
		return $translation;
	}
	$form = jp4wc_bundled_translation_file()->get_plural_form( (int) $number );
	return isset( $forms[ $form ] ) && '' !== $forms[ $form ] ? $forms[ $form ] : $forms[0];
}

/**
 * Returns the forms of an entry of the bundled translation for the current locale.
 *
 * @since 3.0.0
 * @param string $key Text, context and text joined by "\4", or the singular of a plural string.
 * @return string[]|null Forms (one for a string without plurals), or null without a translation.
 */
function jp4wc_bundled_translation_forms( $key ) {
	$file    = jp4wc_bundled_translation_file();
	$bundled = $file ? $file->translate( $key ) : false;
	if ( ! is_string( $bundled ) || '' === $bundled ) {
		return null;
	}
	return explode( "\0", $bundled );
}

/**
 * Returns the bundled translation file for the current locale.
 *
 * @since 3.0.0
 * @return WP_Translation_File|false False when the plugin bundles none for the locale.
 */
function jp4wc_bundled_translation_file() {
	static $files = array();
	$locale       = determine_locale();
	if ( ! array_key_exists( $locale, $files ) ) {
		$mofile           = __DIR__ . '/i18n/woocommerce-for-japan-' . $locale . '.mo';
		$files[ $locale ] = is_readable( $mofile ) ? WP_Translation_File::create( $mofile ) : false;
	}
	return $files[ $locale ];
}

// Register activation hook.
register_activation_hook( __FILE__, 'jp4wc_activation_redirect' );
// deactivation.
register_deactivation_hook( __FILE__, 'jp4wc_on_deactivation' );

/**
 * Add option for activation redirect.
 *
 * @return void
 */
function jp4wc_activation_redirect() {
	add_option( 'paidy_do_activation_redirect', true );
}

/**
 * Flush rewrite rules on deactivate.
 *
 * @return void
 */
function jp4wc_on_deactivation() {
	add_option( 'paidy_do_activation_redirect', true );
	flush_rewrite_rules();
	// Events of the security check (JP4WC_Security_Feed, not loaded without WooCommerce).
	// After activation, JP4WC_Security_Feed::ensure_schedule() schedules the sync again on the next request.
	wp_clear_scheduled_hook( 'jp4wc_security_feed_sync' );
	wp_clear_scheduled_hook( 'jp4wc_security_rescan' );
	wp_clear_scheduled_hook( 'jp4wc_security_recheck' );
}

/**
 * Load the plugin textdomain for translations.
 * Loaded at init priority 1 to comply with WordPress 6.7+ requirements.
 * WooCommerce payment gateway classes are initialized during woocommerce_init (init priority 10+),
 * so loading at init priority 1 ensures translations are available in time.
 *
 * @return void
 */
function jp4wc_load_textdomain() {
	load_plugin_textdomain( 'woocommerce-for-japan', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );
}
add_action( 'init', 'jp4wc_load_textdomain', 1 );

/**
 * Load plugin functions.
 */
add_action( 'plugins_loaded', 'jp4wc_plugin', 10 );

/**
 * Initialize JP4WC plugin when plugins are loaded.
 *
 * @return void
 */
function jp4wc_plugin() {
	if ( class_exists( 'WooCommerce' ) ) {
		JP4WC::instance();
	} else {
		add_action( 'admin_notices', 'jp4wc_fallback_notice' );
	}
}

/**
 * Display fallback notice when WooCommerce is not active.
 *
 * @return void
 */
function jp4wc_fallback_notice() {
	?>
	<div class="error">
		<ul>
			<li><?php esc_html_e( 'Japanized for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'woocommerce-for-japan' ); ?></li>
		</ul>
	</div>
	<?php
}

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	function is_woocommerce_active() {
		if ( ! isset( $active_plugins ) ) {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
		}
		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}

// Garbled characters in e-mail.
add_filter( 'woocommerce_order_shipping_to_display', 'wc4jp_display_shipping', 10, 1 );

/**
 * Replace non-breaking spaces in shipping display to prevent garbled characters.
 *
 * @param string $shipping The shipping display string.
 * @return string The modified shipping display string.
 */
function wc4jp_display_shipping( $shipping ) {
	$shipping = str_replace( '&nbsp;', ' ', $shipping );
	return $shipping;
}

if ( ! class_exists( 'WC_Paidy' ) ) :

	// Paidy Payment Gateways version.
	define( 'WC_PAIDY_VERSION', '1.5.1' );
	require_once __DIR__ . '/class-wc-paidy.php';

	/**
	 * Load plugin functions.
	 */
	add_action( 'plugins_loaded', 'wc_paidy_plugin', 20 );

	/**
	 * Initialize the Paidy plugin.
	 */
	function wc_paidy_plugin() {
		if ( class_exists( 'WooCommerce' ) ) {
			WC_Paidy::get_instance();
		}
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					if ( class_exists( 'WC_Payments_Paidy_Blocks_Support', false ) ) {
						$payment_method_registry->register( new WC_Payments_Paidy_Blocks_Support() );
					}
				}
			);
		}
	}

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods Methods.
	 * @return array $methods Methods.
	 */
	function add_wc4jp_paidy_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Paidy';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_paidy_gateway' );

	// Admin wizard.
	require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-admin-wizard.php';
	new WC_Paidy_Admin_Wizard();

	if ( is_admin() ) {
		// Load admin settings controller.
		require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-settings-controller.php';
		new WC_Paidy_Settings_Controller();

		// Load admin notices controller.
		require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-admin-notices.php';
		new WC_Paidy_Admin_Notices();
	}

	// Redirect when the plugin is activated or updated.
	add_action( 'admin_init', 'paidy_redirect_to_wizard' );

	/**
	 * Redirects to the Paidy wizard after plugin activation.
	 */
	function paidy_redirect_to_wizard() {
		if ( ! class_exists( 'WC_Gateway_Paidy' ) ) {
			return;
		}
		if ( get_option( 'paidy_do_activation_redirect', false ) ) {
			// Read the setting rather than create a gateway, whose constructor adds its hooks again.
			$settings = get_option( 'woocommerce_paidy_settings', array() );
			$enabled  = is_array( $settings ) && isset( $settings['enabled'] ) ? $settings['enabled'] : 'no';
			if ( 'yes' !== $enabled && jp4wc_has_orders_in_last_5_days() ) {
				delete_option( 'paidy_do_activation_redirect' );
				wp_safe_redirect( admin_url( 'admin.php?page=wc-admin&path=%2Fpaidy-on-boarding' ) );
				exit;
			}
		}
	}

	// API Receiver.
	require_once __DIR__ . '/includes/gateways/paidy/class-wc-paidy-apply-receiver.php';

	/**
	 * Initialize the Paidy receiver.
	 *
	 * @return void
	 */
	function init_paidy_receiver() {
		new WC_Paidy_Apply_Receiver();
	}

	// Load the receiver.
	add_action( 'init', 'init_paidy_receiver' );

	/**
	 * The available gateway to woocommerce only Japanese currency
	 */
	if ( function_exists( 'wc4jp_paidy_available_gateways' ) === false ) {
		/**
		 * Filter available payment gateways to include only Japanese currency.
		 *
		 * @param array $methods Available payment methods.
		 * @return array Filtered payment methods.
		 */
		function wc4jp_paidy_available_gateways( $methods ) {
			// Check if the Paidy payment method is available.
			if ( ! isset( $methods['paidy'] ) ) {
				return $methods;
			}

			// Check if the currency is JPY.
			// If the currency is not JPY, remove the Paidy payment method.
			if ( get_woocommerce_currency() !== 'JPY' ) {
				unset( $methods['paidy'] );
				return $methods;
			}

			$settings = get_option( 'woocommerce_paidy_settings', array() );

			// Check if the API keys are set.
			$has_test_key = ! empty( $settings['test_api_public_key'] );
			$has_live_key = ! empty( $settings['api_public_key'] );

			if ( ! $has_test_key && ! $has_live_key ) {
				unset( $methods['paidy'] );
			}
			return $methods;
		}
		add_filter( 'woocommerce_available_payment_gateways', 'wc4jp_paidy_available_gateways' );
	}
endif;

/**
 * Declare plugin compatibility with WooCommerce HPOS.
 *
 * @since 2.6.0
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
