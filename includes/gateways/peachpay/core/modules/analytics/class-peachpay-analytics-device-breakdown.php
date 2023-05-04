<?php
/**
 * Handles payment methods section of PeachPay's analytics admin panel
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-admin-tab.php';

/**
 * Sets up needed information for the Payment Methods Tab.
 */
final class PeachPay_Analytics_Device_Breakdown extends PeachPay_Admin_Tab {
	/**
	 * ID for this specific class.
	 *
	 * @var string $id.
	 */
	public $id = 'device_breakdown';

	/**
	 * The value of the $_GET["section"] parameter when this page is loaded.
	 */
	public function get_section() {
		return 'analytics';
	}

	/**
	 * The value of the $_GET["tab"] parameter when this page is loaded.
	 */
	public function get_tab() {
		return 'device_breakdown';
	}

	/**
	 * The title for this tab.
	 *
	 * @var string
	 */
	public function get_title() {
		return 'Device breakdown';
	}

	/**
	 * The description for this tab.
	 */
	public function get_description() {
		return 'Welcome to the breakdown analytics tab. Here you will find all information and statistics associated with device breakdowns.';
	}

	/**
	 * Include dependencies here.
	 */
	protected function includes() {}

	/**
	 * Register admin view here.
	 */
	public function do_admin_view() {
		$time_span = array_key_exists( 'time_span', $_GET ) ?
			sanitize_text_field( wp_unslash( $_GET['time_span'] ) ) : 'year';
		$interval  = array_key_exists( 'interval', $_GET ) ?
			sanitize_text_field( wp_unslash( $_GET['interval'] ) ) : 'monthly';

		$format_map    = PeachPay_Analytics::$date_format;
		$active_format = $format_map[ $time_span . '.' . $interval ];

		$browser_count_query             = array(
			'tab'      => 'device_breakdown',
			'section'  => 'browser_count',
			'order_by' => 'value DESC',
		);
		$browser_count                   = PeachPay_Analytics_Database::query_analytics( $browser_count_query );
		$browser_total_query             = array(
			'tab'      => 'device_breakdown',
			'section'  => 'browser_count',
			'group_by' => 'title',
			'count'    => 1,
		);
		$browser_total                   = PeachPay_Analytics_Database::query_analytics( $browser_total_query );
		$browser_interval_query          = array(
			'tab'       => 'device_breakdown',
			'section'   => 'browser_interval',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$browser_interval                = PeachPay_Analytics_Database::query_analytics( $browser_interval_query );
		$operating_system_count_query    = array(
			'tab'      => 'device_breakdown',
			'section'  => 'operating_system_count',
			'order_by' => 'value DESC',
		);
		$operating_system_count          = PeachPay_Analytics_Database::query_analytics( $operating_system_count_query );
		$operating_system_total_query    = array(
			'tab'      => 'device_breakdown',
			'section'  => 'operating_system_count',
			'group_by' => 'title',
			'count'    => 1,
		);
		$operating_system_total          = PeachPay_Analytics_Database::query_analytics( $operating_system_total_query );
		$operating_system_interval_query = array(
			'tab'       => 'device_breakdown',
			'section'   => 'operating_system_interval',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$operating_system_interval       = PeachPay_Analytics_Database::query_analytics( $operating_system_interval_query );

		require PEACHPAY_ABSPATH . 'core/modules/analytics/pages/html-peachpay-device-breakdown.php';
	}

	/**
	 * Attach to enqueue scripts hook.
	 */
	public function enqueue_admin_scripts() {
		PeachPay::enqueue_script(
			'peachpay-analytics-charts',
			'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.8.0/chart.min.js',
			array(),
			false,
			true
		);
		PeachPay::enqueue_script(
			'peachpay-analytics-uaparser',
			'https://cdnjs.cloudflare.com/ajax/libs/UAParser.js/0.7.20/ua-parser.min.js',
			array(),
			false,
			true
		);
		PeachPay::enqueue_script(
			'peachpay_analytics_chart_builder',
			peachpay_url( 'core/modules/analytics/assets/js/util.js' ),
			array(),
			false,
			true
		);

		PeachPay::enqueue_style(
			'peachpay-settings',
			'public/dist/admin.bundle.css',
			array()
		);
		PeachPay::enqueue_style(
			'peachpay-analytics-styles',
			'core/modules/analytics/assets/css/analytics.css',
			array()
		);
	}
}
