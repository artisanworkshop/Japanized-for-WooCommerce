<?php
/**
 * PeachPay's activator, deactivator, and updator all in one.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-singleton.php';

/**
 * Class for the lifecycle of the PeachPay plugin
 *   - Activate, Deactivate, Upgrade, or Downgrade
 */
final class PeachPay_Lifecycle {
	use PeachPay_Singleton;

	/**
	 * Setup the hooks
	 */
	public function __construct() {
		$lifecycle = $this;

		/**
		 * Listen for Plugin activation.
		 * 彡～彡 (whooshing noise) 彡～彡
		 */
		register_activation_hook( PEACHPAY_PLUGIN_FILE, array( $this, 'plugin_activated' ) );

		/**
		 * Listen for Plugin deactivation.
		 * 彡～彡 (whooshing noise) 彡～彡
		 */
		register_deactivation_hook( PEACHPAY_PLUGIN_FILE, array( $this, 'plugin_deactivated' ) );

		/**
		 * Listen for Plugin version change.
		 */
		try {
			$previous_plugin_version = get_option( 'peachpay_plugin_version', PEACHPAY_VERSION );
			update_option( 'peachpay_plugin_version', PEACHPAY_VERSION );

			if ( PEACHPAY_VERSION !== $previous_plugin_version ) {
				if ( version_compare( PEACHPAY_VERSION, $previous_plugin_version, '>' ) ) {
					add_action(
						'plugins_loaded',
						function() use ( $lifecycle, $previous_plugin_version ) {
							$lifecycle->plugin_upgraded( $previous_plugin_version );
						}
					);
				} else {
					add_action(
						'plugins_loaded',
						function() use ( $lifecycle, $previous_plugin_version ) {
							$lifecycle->plugin_downgraded( $previous_plugin_version );
						}
					);
				}
			}
		} catch ( Exception $ex ) {
			update_option( 'peachpay_plugin_version', PEACHPAY_VERSION );
		}
	}


	/**
	 * Handles plugin activation routines.
	 */
	public function plugin_activated() {
		do_action( 'peachpay_plugin_activated' );

		do_action( 'peachpay_plugin_capabilities', peachpay_fetch_plugin_capabilities() );
	}

	/**
	 * Handles plugin deactivation routines.
	 */
	public function plugin_deactivated() {
		do_action( 'peachpay_plugin_deactivated' );

		do_action( 'peachpay_plugin_capabilities', peachpay_fetch_plugin_capabilities() );
	}

	/**
	 * Handles plugin upgraded routines.
	 *
	 * @param string $old_version The old plugin version upgraded from.
	 */
	public function plugin_upgraded( $old_version ) {
		do_action( 'peachpay_plugin_upgraded', $old_version );

		do_action( 'peachpay_plugin_capabilities', peachpay_fetch_plugin_capabilities() );
	}

	/**
	 * Handles plugin downgraded routines.
	 *
	 * @param string $old_version The old plugin version downgraded from.
	 */
	public function plugin_downgraded( $old_version ) {
		do_action( 'peachpay_plugin_downgraded', $old_version );

		do_action( 'peachpay_plugin_capabilities', peachpay_fetch_plugin_capabilities() );
	}
}

return PeachPay_Lifecycle::instance();
