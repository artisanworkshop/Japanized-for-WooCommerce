<?php
/**
 * Handles the routing for the autocomplete page section of the PeachPay admin panel
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/admin/class-peachpay-admin-section.php';
require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-extension.php';
require_once PEACHPAY_ABSPATH . 'core/admin/class-peachpay-onboarding-tour.php';

/**
 * Initializer for the PeachPay address autocomplete settings.
 */
class PeachPay_Address_Autocomplete {
	use PeachPay_Extension;

	/**
	 * At some point in time we will allow the ability to disable and enable
	 * integrations completely to ensure they do not have performance impacts
	 * when not used. For now we always return true.
	 */
	public static function should_load() {
		return true;
	}

	/**
	 * .
	 */
	private function includes() {
		require_once PEACHPAY_ABSPATH . 'core/modules/address-autocomplete/hooks.php';
		require_once PEACHPAY_ABSPATH . 'core/modules/address-autocomplete/functions.php';
	}

	/**
	 * On plugins load.
	 */
	public function plugins_loaded() {
		require_once PEACHPAY_ABSPATH . 'core/modules/address-autocomplete/class-peachpay-address-autocomplete-settings.php';

		$section = PeachPay_Admin_Section::create(
			'address_autocomplete',
			array(
				new PeachPay_Address_Autocomplete_Settings(),
			),
			array(),
			false,
			true
		);

		// migrate address autocommplete setting
		$need_to_migrate_setting = peachpay_get_settings_option( 'peachpay_express_checkout_window', 'address_autocomplete' );
		if ( $need_to_migrate_setting ) {
			PeachPay_Address_Autocomplete_Settings::update_setting( 'active_locations', 'default' );
			PeachPay_Address_Autocomplete_Settings::update_setting( 'enabled', 'yes' );

			peachpay_set_settings_option( 'peachpay_express_checkout_window', 'address_autocomplete', false );
		}

		if ( ! peachpay_plugin_has_capability( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) ) ) {
			PeachPay_Address_Autocomplete_Settings::update_setting( 'enabled', 'no' );
		}

		PeachPay_Onboarding_Tour::complete_section( 'address_autocomplete' );
	}
}
PeachPay_Address_Autocomplete::instance();
