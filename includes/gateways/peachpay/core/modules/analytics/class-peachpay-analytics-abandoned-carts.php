<?php
/**
 * Handles abandoned carts section of PeachPay's analytics admin panel
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for loading the data for and rendering the Abandoned Carts analytics page.
 */
class PeachPay_Analytics_Abandoned_Carts extends PeachPay_Admin_Tab {
	/**
	 * ID for this specific class.
	 *
	 * @var string $id.
	 */
	public $id = 'abandoned_carts';

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
		return 'abandoned_carts';
	}

	/**
	 * The title for this tab.
	 *
	 * @var string
	 */
	public function get_title() {
		return 'Abandoned carts';
	}

	/**
	 * The description for this tab.
	 */
	public function get_description() {
		return 'Welcome to the abandoned carts analytics tab. Here you will find all information and statistics associated with abandoned carts.';
	}

	/**
	 * Include dependencies here.
	 */
	protected function includes() {}


	/**
	 * Register admin view here.
	 *
	 * For each of the following, grab then ensure there is data (while otherwise making an empty version of the expected structure)
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

		$currency_options = PeachPay_Analytics_Database::query_analytics(
			array(
				'tab'      => 'abandoned_carts',
				'section'  => 'volume_count',
				'title'    => array(
					'Recoverable',
					'Unrecoverable',
				),
				'currency' => '*',
				'group_by' => 'currency',
			)
		);

		$pull_currency_options = array();
		foreach ( $currency_options['value'] as $currency_option ) {
			if ( property_exists( $currency_option, 'currency' ) && $currency_option->currency ) {
				array_push( $pull_currency_options, $currency_option->currency );
			}
		}
		$currency_options = $pull_currency_options;

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

		$recoverable_volume_interval_query   = array(
			'tab'       => 'abandoned_carts',
			'section'   => 'volume_interval',
			'currency'  => $currency,
			'convert'   => 1,
			'title'     => 'Recoverable',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$recoverable_volume_interval         = PeachPay_Analytics_Database::query_analytics( $recoverable_volume_interval_query );
		$recoverable_cart_interval_query     = array(
			'tab'       => 'abandoned_carts',
			'section'   => 'cart_interval',
			'title'     => 'Recoverable',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$recoverable_cart_interval           = PeachPay_Analytics_Database::query_analytics( $recoverable_cart_interval_query );
		$unrecoverable_cart_interval_query   = array(
			'tab'       => 'abandoned_carts',
			'section'   => 'cart_interval',
			'title'     => 'Unrecoverable',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$unrecoverable_cart_interval         = PeachPay_Analytics_Database::query_analytics( $unrecoverable_cart_interval_query );
		$total_cart_count_interval_query     = array(
			'tab'       => 'abandoned_carts',
			'section'   => 'cart_interval',
			'title'     => array(
				'Completed',
				'Recoverable',
				'Unrecoverable',
			),
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$total_cart_count_interval           = PeachPay_Analytics_Database::query_analytics( $total_cart_count_interval_query );
		$unrecoverable_volume_count_query    = array(
			'tab'      => 'abandoned_carts',
			'section'  => 'volume_count',
			'currency' => $currency,
			'convert'  => 1,
			'title'    => 'Unrecoverable',
			'sum'      => 1,
		);
		$unrecoverable_volume_count          = PeachPay_Analytics_Database::query_analytics( $unrecoverable_volume_count_query );
		$unrecoverable_volume_interval_query = array(
			'tab'       => 'abandoned_carts',
			'section'   => 'volume_interval',
			'currency'  => $currency,
			'convert'   => 1,
			'title'     => 'Unrecoverable',
			'interval'  => $interval,
			'time_span' => $time_span,
			'format'    => $active_format,
		);
		$unrecoverable_volume_interval       = PeachPay_Analytics_Database::query_analytics( $unrecoverable_volume_interval_query );

		$cart_count_total_query = array(
			'tab'     => 'abandoned_carts',
			'section' => 'cart_count',
			'sum'     => 1,
		);
		$cart_count_total       = PeachPay_Analytics_Database::query_analytics( $cart_count_total_query );

		$cart_count_metric_query = array(
			'tab'     => 'abandoned_carts',
			'section' => 'cart_count',
			'title'   => array(
				'Completed',
				'Recoverable',
				'Unrecoverable',
			),
		);
		$cart_count_metric       = PeachPay_Analytics_Database::query_analytics( $cart_count_metric_query );

		$recoverable_cart_count_query = array(
			'tab'     => 'abandoned_carts',
			'section' => 'cart_count',
			'title'   => 'Recoverable',
			'sum'     => 1,
		);
		$recoverable_cart_count       = PeachPay_Analytics_Database::query_analytics( $recoverable_cart_count_query );

		$unrecoverable_cart_count_query = array(
			'tab'     => 'abandoned_carts',
			'section' => 'cart_count',
			'title'   => 'Unrecoverable',
			'sum'     => 1,
		);
		$unrecoverable_cart_count       = PeachPay_Analytics_Database::query_analytics( $unrecoverable_cart_count_query );

		$percent_cart_abandoned = $cart_count_total ? ( ( $recoverable_cart_count + $unrecoverable_cart_count ) / $cart_count_total * 100 ) : 0;

		require PEACHPAY_ABSPATH . 'core/modules/analytics/pages/html-peachpay-abandoned-carts.php';
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
