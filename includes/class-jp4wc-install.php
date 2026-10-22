<?php
/**
 * Installation related functions and actions.
 *
 * @package JP4WC\Classes
 * @version 2.7.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * JP4WC_Install Class.
 */
class JP4WC_Install {
	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Check Japanized for WooCommerce version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		$previous_version = get_option( 'jp4wc_version' );
		$is_new_install   = empty( $previous_version );
		$requires_update  = ! $is_new_install && version_compare( $previous_version, JP4WC_VERSION, '<' );

		if ( $is_new_install || $requires_update ) {
			self::install();

			if ( $is_new_install ) {
				/**
				 * Run after Japanized for WooCommerce has been installed for the first time.
				 *
				 * @since 2.6.0
				 * @param string $previous_version Previous version of the plugin (empty for new installs).
				 */
				do_action( 'jp4wc_installed', $previous_version );
			} elseif ( $requires_update ) {
				/**
				 * Run after Japanized for WooCommerce has been updated.
				 *
				 * @since 2.6.0
				 * @param string $previous_version Previous version of the plugin.
				 */
				do_action( 'jp4wc_updated', $previous_version );
			}
		}
	}

	/**
	 * Install WC.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( self::is_installing() ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'jp4wc_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		self::remove_usage_tracking();
		self::update_jp4wc_version();
		// Set up the security check, or remove its events and data when it is off.
		if ( class_exists( 'JP4WC_Security_Feed', false ) ) {
			JP4WC_Security_Feed::install();
		}

		delete_transient( 'jp4wc_installing' );
	}

	/**
	 * Returns true if we're installing.
	 *
	 * @return bool
	 */
	private static function is_installing() {
		return 'yes' === get_transient( 'jp4wc_installing' );
	}

	/**
	 * Remove what the usage tracking feature left behind.
	 *
	 * Usage tracking was removed in 2.9.17 to make way for a new feature that
	 * will replace it. Sites that ran an earlier version still have its weekly
	 * WP-Cron event, its setting and the time of the last send. Versions
	 * 2.6.10 to 2.6.19 scheduled the event as `wc4jp_tracker_send_event`, and
	 * nothing has cleared that name since.
	 *
	 * @since 2.9.17
	 */
	private static function remove_usage_tracking() {
		wp_clear_scheduled_hook( 'jp4wc_tracker_send_event' );
		wp_clear_scheduled_hook( 'wc4jp_tracker_send_event' );
		delete_option( 'wc4jp-tracking' );
		delete_option( 'jp4wc_tracker_last_send' );
	}

	/**
	 * Update WC version to current.
	 */
	private static function update_jp4wc_version() {
		$current_version = get_option( 'jp4wc_version', '' );
		if ( ! $current_version ) {
			add_option( 'jp4wc-first-installing', 'yes' );
		} else {
			delete_option( 'jp4wc-first-installing' );
		}
		update_option( 'jp4wc_version', JP4WC_VERSION );
		$paidy_do_activation_redirect = get_option( 'paidy_do_activation_redirect', false );
		if ( ! $paidy_do_activation_redirect ) {
			update_option( 'paidy_do_activation_redirect', true );
		}
	}
}
JP4WC_Install::init();
