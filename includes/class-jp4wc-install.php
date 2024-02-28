<?php
/**
 * Installation related functions and actions.
 *
 * @package JP4WC\Classes
 * @version 2.6.9
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
		$wc_version      = get_option( 'jp4wc_version' );
		$requires_update = version_compare( $wc_version, JP4WC_VERSION, '<' );
        if( $requires_update ){
			self::install();
			/**
			 * Run after WooCommerce has been updated.
			 *
			 * @since 2.6.0
			 */
			do_action( 'jp4wc_updated' );

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

        self::create_cron_jobs();
		self::update_jp4wc_version();

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
	 * Create cron jobs (clear them first).
	 */
	private static function create_cron_jobs() {
        wp_clear_scheduled_hook( 'jp4wc_tracker_send_event' );
		/**
		 * How frequent to schedule the tracker send event.
		 *
		 * @since 2.6.0
		 */
		wp_schedule_event( time() + 10, apply_filters( 'wc4jp_tracker_event_recurrence', 'weekly' ), 'wc4jp_tracker_send_event' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'woocommerce_cleanup_rate_limits' );

    }

	/**
	 * Update WC version to current.
	 */
	private static function update_jp4wc_version() {
		update_option( 'jp4wc_version', JP4WC_VERSION );
		JP4WC_Tracker::jp4wc_send_tracking_data( true );
	}
}
JP4WC_Install::init();
