<?php
/**
 * Class PeachPay_Initializer
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main class for the PeachPay plugin. Its responsibility is to initialize the extension.
 */
class PeachPay_Initializer {

	/**
	 * Dependency Checking Service for PeachPay.
	 *
	 * @var PeachPay_Dependency_Service
	 */
	private static $dependency_service;

	/**
	 * Entry point to the initialization logic.
	 */
	public static function init() {

		// Check dependencies and update the PeachPay admin error notice.
		self::$dependency_service = new PeachPay_Dependency_Service();

		if ( ! self::$dependency_service->all_dependencies_valid() ) {
			// If Woocommerce isn't active, PeachPay will not run properly. Return without initializing.
			return false;
		}

		return true;
	}
}
