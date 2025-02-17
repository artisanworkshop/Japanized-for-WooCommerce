<?php
/**
 * Admin Dashboard - PHP notice
 *
 * @package     JP4WC\Admin
 * @version     2.6.12
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'JP4WC_Admin_Dashboard_PHP_Notice', false ) ) :

	/**
	 * WC_Admin_Dashboard_Setup Class.
	 */
	class JP4WC_Admin_Dashboard_PHP_Notice {

		/**
		 * # of completed tasks.
		 *
		 * @var int
		 */
		private $completed_tasks_count = 0;

		/**
		 * JP4WC_Admin_Dashboard_PHP_Notice constructor.
		 */
		public function __construct() {
			if ( $this->should_display_php_notice() ) {
				add_meta_box(
					'jp4wc_admin_dashboard_php_notice',
					__( '[Immediate response recommended]PHP Version notice!', 'woocommerce-for-japan' ).'('.PHP_VERSION.')',
					array( $this, 'render' ),
					'dashboard',
					'normal',
					'high'
				);
			}
		}

		/**
		 * Render meta box output.
		 */
		public function render() {
			wp_enqueue_style( 'jp4wc_admin_dashboard_php_notice', JP4WC_URL_PATH . 'assets/css/dashboard-php-notice.css', array(), JP4WC_VERSION );

			$php_notice_link    = 'https://wc4jp-pro.work/php-updates-unavoidable-obligation/';

			include __DIR__ . '/views/html-admin-dashboard-php-notice.php';
		}

        /**
		 * Check to see if we should display the notice
		 *
		 * @return bool
		 */
		public function should_display_php_notice() {
			if ( ! WC()->is_wc_admin_active() ) {
				return false;
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return false;
			}

			$php_ver = phpversion();
            if ( version_compare( $php_ver, '8.1.0', '>=' ) ) {
				return false;
			}
			if( ! is_multisite() ){
				$month_totals = JP4WC_Tracker::get_last_month_order_totals();
				$month_total = $month_totals['monthly_gross'] + $month_totals['monthly_processing_gross'];
				if ( $month_total <= 0 ){
					return false;
				}
			}

			return true;
		}
    }

endif;

return new JP4WC_Admin_Dashboard_PHP_Notice();
