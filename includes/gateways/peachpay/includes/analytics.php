<?php
/**
 * Functions for recording activation and deactivation analytics.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'upgrader_process_complete', 'peachpay_upgrader_process_complete', 10, 2 );

function peachpay_upgrader_process_complete( $upgrader_object, $options ) {
	$peachpay_updated = false;

	if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
		foreach ( $options['plugins'] as $index => $plugin ) {
			if ( 'peachpay-for-woocommerce/peachpay.php' === $plugin ) {
				$peachpay_updated = true;
				break;
			}
		}
	}

	if ( ! $peachpay_updated ) {
		return;
	}

	peachpay_record_analytics(
		is_plugin_active( 'peachpay-for-woocommerce/peachpay.php' ),
		// We have to do this because the WordPress upgrader runs on the
		// previous version of the plugin.
		peachpay_get_published_version()
	);
}

/**
 * @param boolean $active Records whether the plugin is active or not.
 * @param string $version The current version of the plugin should be passed in
 * here.
 */
function peachpay_record_analytics( $active, string $version ) {
	$body = wp_json_encode( array(
		'site_url'       => get_site_url(),
		'site_title'     => get_bloginfo( 'name' ),
		'plugin_slug'    => 'woocommerce-for-japan',
		'plugin_version' => $version,
		'plugin_active'  => $active,
		'has_stripe'     => peachpay_is_using_stripe_plugin()
	) );

	$args = array(
		'body'        => $body,
		'headers'     => array( 'Content-Type' => 'application/json' ),
		'httpversion' => '2.0',
		'blocking'    => false,
	);

	wp_remote_post(
		'https://itb2aqqh8g.execute-api.us-east-1.amazonaws.com/default/pluginAnalytics',
		$args
	);
}

function peachpay_get_published_version() {
	$response = wp_remote_get('https://plugins.svn.wordpress.org/peachpay-for-woocommerce/trunk/readme.txt');
	$body     = wp_remote_retrieve_body( $response );
	$result   = array();
	preg_match('/Stable tag: (\d+\.\d+\.\d+)/', $body, $result);
	return $result[1];
}

function peachpay_is_using_stripe_plugin() {
	return is_plugin_active( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php' )
		|| is_plugin_active( 'woo-stripe-payment/stripe-payments.php' )
		|| is_plugin_active( 'stripe-payments/accept-stripe-payments.php' )
		|| is_plugin_active( 'payment-gateway-stripe-and-woocommerce-integration/payment-gateway-stripe-and-woocommerce-integration.php' )
		|| is_plugin_active( 'stripe/stripe-checkout.php' );
}

/**
 * Get the total sales for last 30 days for this store. The function being
 * prefixed with peachpay_ should not be confused to imply that the total is for
 * only PeachPay orders. It is for all orders.
 */
function peachpay_sales_last_month() {
	return peachpay_sales('last_month');
}

function peachpay_sales_ytd() {
	return peachpay_sales('ytd');
}

/**
 * Get the store's total sales for the given period.
 *
 * @param string period Supported values are "30days" and "ytd"
 * @return string The total sales with the currency symbol.
 */
function peachpay_sales( string $period ) {
	global $wpdb;
	include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );
	$wc_report = new WC_Admin_Report();

	switch ( $period ) {
		case 'last_month':
			$start_date = strtotime( 'first day of last month' );
			$end_date = strtotime( 'last day of last month' );
			break;
		case 'ytd':
			$start_date = strtotime( 'first day of january' );
			$end_date = strtotime( 'now' );
			break;
	}

	$wc_report->start_date = $start_date;
	$wc_report->end_date = $end_date;

	// Avoid max join size error
	$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

	$report = (array) $wc_report->get_order_report_data(
		array(
			'data'         => array(
				'_order_total'        => array(
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

	return html_entity_decode( get_woocommerce_currency_symbol() ) . number_format( $report[0]->total_sales, 2 );
}
