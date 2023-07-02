<?php
/**
 * Handles the routing for the Bot protection settings of the PeachPay admin panel
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/admin/class-peachpay-admin-section.php';
require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-extension.php';

/**
 * Initializer for the PeachPay checkout page settings.
 */
class PeachPay_Bot_Protection {
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
		require_once PEACHPAY_ABSPATH . 'core/modules/bot-protection/hooks.php';
		require_once PEACHPAY_ABSPATH . 'core/modules/bot-protection/functions.php';
	}

	/**
	 * On plugins load.
	 */
	public function plugins_loaded() {
		// Specific analytics tabs to include.
		require_once PEACHPAY_ABSPATH . 'core/modules/bot-protection/class-peachpay-bot-protection-settings.php';

		$section = PeachPay_Admin_Section::create(
			'settings',
			array(
				new PeachPay_Bot_Protection_Settings(),
			),
			array(),
			false
		);

	}
}
PeachPay_Bot_Protection::instance();
