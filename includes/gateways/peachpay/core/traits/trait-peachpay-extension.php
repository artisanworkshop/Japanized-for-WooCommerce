<?php
/**
 * PeachPay Extension Trait.
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-singleton.php';

trait PeachPay_Extension {

	use PeachPay_Singleton;

	/**
	 * Initializes the extension.
	 */
	private function __construct() {
		if ( self::should_load() ) {
			$this->internal_hooks();
			$this->includes();
		}
	}

	/**
	 * Initialize actions and filters. This should not be attempted to be overridden. Any custom hooks
	 * should be registered in hooks.php and defined in functions.php. These two files should be loaded
	 * in the includes method.
	 */
	private function internal_hooks() {
		$extension = $this;
		add_action(
			'init',
			function() use ( $extension ) {
				$extension->init();
			}
		);
		add_action(
			'woocommerce_init',
			function() use ( $extension ) {
				$extension->woocommerce_init();
			}
		);

		add_action(
			'plugins_loaded',
			function() use ( $extension ) {
				$extension->plugins_loaded();
			}
		);
		add_action(
			'wp_enqueue_scripts',
			function() use ( $extension ) {
				$extension->enqueue_public_scripts();
			}
		);
		add_action(
			'rest_api_init',
			function() use ( $extension ) {
				$extension->rest_api_init();
			}
		);
	}

	/**
	 * This is called immediately when the class is constructed. This is a good time to load files and utilities that do not depend on outside plugins.
	 */
	abstract protected function includes();

	/**
	 * This is called with the init hook.
	 */
	private function init() {}

	/**
	 * Called after all plugins are loaded.
	 */
	private function plugins_loaded() {}

	/**
	 * Initialize extension specific woocommerce dependencies.
	 */
	private function woocommerce_init() {}

	/**
	 * Load extension specific public scripts here.
	 */
	private function enqueue_public_scripts() {}

	/**
	 * Init any rest API endpoints.
	 */
	private function rest_api_init() {}

	/**
	 * This should be where you check for required dependencies. If this returns true the extension may still not load if it is disabled.
	 *
	 * @return boolean If it should load.
	 */
	abstract public static function should_load();
}
