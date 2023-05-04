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
final class PeachPay_Analytics_Payment_Methods extends PeachPay_Admin_Tab {
	/**
	 * ID for this specific class.
	 *
	 * @var string $id.
	 */
	public $id = 'payment_methods';

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
		return 'payment_methods';
	}

	/**
	 * The title for this tab.
	 *
	 * @var string
	 */
	public function get_title() {
		return 'Payment methods';
	}

	/**
	 * The description for this tab.
	 */
	public function get_description() {
		return 'Welcome to the payment methods analytics tab. Here you will find all information and statistics associated with payment methods.';
	}

	/**
	 * Include dependencies here.
	 */
	protected function includes() {}

	/**
	 * Register admin view here.
	 */
	public function do_admin_view() {
		$currency = array_key_exists( 'currency', $_REQUEST ) ?
			sanitize_text_field( wp_unslash( $_REQUEST['currency'] ) ) : get_option( 'woocommerce_currency' );

		$currency = str_replace( '.', ',', $currency );

		$time_span = array_key_exists( 'time_span', $_GET ) ?
			sanitize_text_field( wp_unslash( $_GET['time_span'] ) ) : 'year';
		$interval  = array_key_exists( 'interval', $_GET ) ?
			sanitize_text_field( wp_unslash( $_GET['interval'] ) ) : 'monthly';

		$format_map    = PeachPay_Analytics::$date_format;
		$active_format = PeachPay_Analytics::$date_format[ $time_span . '.' . $interval ];

		$currency_breakdown_query = array(
			'tab'      => 'payment_methods',
			'section'  => 'currency_count',
			'order_by' => 'value DESC',
		);
		$currency_breakdown       = PeachPay_Analytics_Database::query_analytics( $currency_breakdown_query );
		$currency_options         = $currency_breakdown['graph']['labels'];

		$inputted_currencies          = explode( ',', $currency );
		$associative_currency_options = array();
		foreach ( $currency_options as $currency_option ) {
			$associative_currency_options[ $currency_option ] = 1;
		}

		foreach ( $inputted_currencies as $inputted_currency ) {
			if ( ! array_key_exists( $inputted_currency, $associative_currency_options ) ) {
				$associative_currency_options[ $inputted_currency ] = 1;
				array_push( $currency_options, $inputted_currency );
			}
		}
		if ( ! array_key_exists( get_option( 'woocommerce_currency' ), $associative_currency_options ) ) {
			array_push( $currency_options, get_option( 'woocommerce_currency' ) );
		}

		$inputted_currencies_count = count( $inputted_currencies );
		if ( count( $currency_options ) === $inputted_currencies_count && $inputted_currencies_count > 1 ) {
			$currency = '*';
		}

		$order_count_query     = array(
			'tab'      => 'payment_methods',
			'section'  => 'order_count',
			'currency' => $currency,
			'sum'      => 1,
		);
		$total_orders          = PeachPay_Analytics_Database::query_analytics( $order_count_query );
		$order_interval_query  = array(
			'tab'       => 'payment_methods',
			'section'   => 'order_interval',
			'currency'  => $currency,
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$order_interval        = PeachPay_Analytics_Database::query_analytics( $order_interval_query );
		$volume_count_query    = array(
			'tab'      => 'payment_methods',
			'section'  => 'volume_count',
			'currency' => $currency,
			'convert'  => 1,
			'order_by' => 'value DESC',
		);
		$volume_count          = PeachPay_Analytics_Database::query_analytics( $volume_count_query );
		$total_volume_query    = array(
			'tab'      => 'payment_methods',
			'section'  => 'volume_count',
			'currency' => $currency,
			'sum'      => 1,
			'convert'  => 1,
		);
		$total_volume          = PeachPay_Analytics_Database::query_analytics( $total_volume_query );
		$volume_interval_query = array(
			'tab'       => 'payment_methods',
			'section'   => 'volume_interval',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
			'convert'   => 1,
			'currency'  => $currency,
			'order_by'  => 'value DESC',
		);
		$volume_interval       = PeachPay_Analytics_Database::query_analytics( $volume_interval_query );

		require PEACHPAY_ABSPATH . 'core/modules/analytics/pages/html-peachpay-payment-methods.php';
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
