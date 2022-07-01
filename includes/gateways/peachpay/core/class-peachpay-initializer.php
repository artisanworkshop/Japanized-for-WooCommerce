<?php
/**
 * Class PeachPay_Initializer
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once PEACHPAY_ABSPATH . 'core/class-peachpay-dependency-service.php';
require_once PEACHPAY_ABSPATH . 'core/class-peachpay-test-mode-service.php';

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
	 * Test Mode Checking Service for PeachPay.
	 *
	 * @var PeachPay_Test_Mode_Service
	 */
	private static $test_mode_service;

	/**
	 * Entry point to the initialization logic.
	 */
	public static function init() {

		// Check dependencies and update the PeachPay admin error notice.
		self::$dependency_service = new PeachPay_Dependency_Service();

		if ( ! self::$dependency_service->all_dependencies_valid() ) {
			// If any dependencies are invalid, PeachPay will not run properly. Return without further initialization.
			return false;
		}

		// Initialize all other services after dependencies are checked.
		self::$test_mode_service = new PeachPay_Test_Mode_Service();

		return true;
	}
}
