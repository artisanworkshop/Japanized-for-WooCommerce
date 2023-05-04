<?php
/**
 * Functions for recording activation and deactivation analytics.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/traits/trait-peachpay-singleton.php';

/**
 * Collects Plugin analytical data.
 */
final class PeachPay_Lifecycle_Analytics {
	use PeachPay_Singleton;

	/**
	 * Sets up the analytic hooks.
	 */
	public function __construct() {
		add_action( 'peachpay_plugin_activated', array( $this, 'plugin_activated' ) );
		add_action( 'peachpay_plugin_deactivated', array( $this, 'plugin_deactivated' ) );
		add_action( 'peachpay_plugin_upgraded', array( $this, 'plugin_upgraded' ) );
		add_action( 'peachpay_plugin_downgraded', array( $this, 'plugin_downgraded' ) );
	}

	/**
	 * Triggered when the plugin has been activated. Plugin modules can hook in via the
	 * filter `peachpay_plugin_activated_analytics` to add information to the analytics data.
	 */
	public function plugin_activated() {
		$this->send_analytics(
			'plugin_activated',
			apply_filters(
				'peachpay_plugin_activated_analytics',
				array(
					'sales_currency'       => get_woocommerce_currency(),
					'sales_ytd'            => $this->sales( 'ytd' ),
					'sales_last_month'     => $this->sales( 'last_month' ),
					'sales_last_12_months' => $this->sales( 'last_12_months' ),
				)
			)
		);
	}

	/**
	 * Triggered when the plugin has been deactivated. Plugin modules can hook in via the
	 * filter `peachpay_plugin_deactivated_analytics` to add information to the analytics data
	 */
	public function plugin_deactivated() {
		$data = array(
			'sales_currency'       => get_woocommerce_currency(),
			'sales_ytd'            => $this->sales( 'ytd' ),
			'sales_last_month'     => $this->sales( 'last_month' ),
			'sales_last_12_months' => $this->sales( 'last_12_months' ),
		);

		$feedback = get_option( 'peachpay_deactivation_feedback', null );
		if ( $feedback && is_array( $feedback ) ) {
			$data = array_merge( $data, $feedback );
			delete_option( 'peachpay_deactivation_feedback' );
		}

		$this->send_analytics(
			'plugin_deactivated',
			apply_filters(
				'peachpay_plugin_deactivated_analytics',
				$data
			)
		);
	}

	/**
	 * Triggered when the plugin has been upgraded. Plugin modules can hook in via the
	 * filter `peachpay_plugin_upgraded_analytics` to add information to the analytics data.
	 *
	 * @param string $old_version The old version of the plugin.
	 */
	public function plugin_upgraded( $old_version ) {
		$this->send_analytics(
			'plugin_upgraded',
			apply_filters(
				'peachpay_plugin_upgraded_analytics',
				array(
					'old_version' => $old_version,
				)
			)
		);
	}

	/**
	 * Triggered when the plugin has been downgraded. Plugin modules can hook in via the
	 * filter `peachpay_plugin_downgraded_analytics` to add information to the analytics data.
	 *
	 * @param string $old_version The old version of the plugin.
	 */
	public function plugin_downgraded( $old_version ) {
		$this->send_analytics(
			'plugin_downgraded',
			apply_filters(
				'peachpay_plugin_downgraded_analytics',
				array(
					'old_version' => $old_version,
				)
			)
		);
	}

	/**
	 * Sends an analytic request.
	 *
	 * @param string $type The analytic type.
	 * @param array  $body The analytic data.
	 */
	private function send_analytics( $type, $body = array() ) {
		wp_remote_post(
			peachpay_api_url( 'prod' ) . 'api/v1/analytics',
			array(
				'blocking'    => true,
				'data_format' => 'body',
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
				'body'        => wp_json_encode(
					array(
						'type'         => $type,
						'merchant_url' => get_home_url(),
						'merchant_id'  => peachpay_plugin_merchant_id(),
						'metadata'     => array_merge(
							array(
								'merchant_email' => get_bloginfo( 'admin_email' ),
								'plugin_slug'    => 'peachpay-for-woocommerce',
								'plugin_mode'    => peachpay_is_test_mode() ? 'test' : 'live',
								'plugin_version' => PEACHPAY_VERSION,
							),
							$body
						),
					)
				),
			)
		);
	}

	/**
	 * Get the store's total sales for the given period.
	 *
	 * @param string $period Supported values are "30days" and "ytd".
	 * @return string The total sales with the currency symbol.
	 */
	private function sales( $period ) {
		global $wpdb;
		include_once WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php';
		$wc_report = new WC_Admin_Report();

		switch ( $period ) {
			case 'last_month':
				$start_date = strtotime( 'first day of last month' );
				$end_date   = strtotime( 'last day of last month' );
				break;
			case 'ytd':
				$start_date = strtotime( 'first day of january' );
				$end_date   = strtotime( 'now' );
				break;
			case 'last_12_months':
				$start_date = strtotime( '- 12 months' );
				$end_date   = strtotime( 'now' );
				break;
		}

		$wc_report->start_date = $start_date;
		$wc_report->end_date   = $end_date;

		// Avoid max join size error.
		// phpcs:ignore
		$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

		$report = (array) $wc_report->get_order_report_data(
			array(
				'data'         => array(
					'_order_total' => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_sales',
					),
				),
				'group_by'     => $wc_report->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => 'month',
				'order_types'  => wc_get_order_types( 'sales-reports' ),
				'order_status' => array( 'completed', 'processing', 'on-hold', 'refunded' ),
			)
		);

		return html_entity_decode( get_woocommerce_currency_symbol(), ENT_COMPAT ) . number_format( $report[0]->total_sales, 2 );
	}
}

return PeachPay_Lifecycle_Analytics::instance();
