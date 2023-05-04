<?php
/**
 * PeachPay Extension Admin Trait.
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-singleton.php';

/**
 * Admin class for extensions.
 *
 * @deprecated in favor of the the PeachPay_Admin_Section and PeachPay_Admin_Tab API
 */
trait PeachPay_Admin_Extension {

	use PeachPay_Singleton;


	/**
	 * Initializes the extension.
	 */
	private function __construct() {
		$this->internal_hooks();
		$this->init();
	}

	/**
	 * Initialize actions and filters. This should not be attempted to be overridden. Any custom hooks
	 * should be defined in a hooks.php file and loaded in in $this->includes() of the parent integration file.
	 */
	private function internal_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Initialize classes and functions. This is probably the best place to
	 * load utility functions and admin settings related code.
	 */
	abstract protected function init();

	/**
	 * Write your admin page HTML here or load a HTML view here. This function must be triggered by your own filter. This does not run by default.
	 */
	abstract public static function do_admin_page();


	/**
	 * Load extension specific public scripts here.
	 */
	public function enqueue_admin_scripts() { }
}
