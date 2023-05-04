<?php
/**
 * PeachPay Analytics Database API
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Main class for computing date components for the analytics.
 *
 * This is used for building both queries and date arrays for the query_analytics function.
 */
class PeachPay_Analytics_Time {
	use PeachPay_Singleton;

	/**
	 * .
	 */
	public function __construct() {}

	/**
	 * Pattern for $interval on query_analytics.
	 *
	 * @var array $interval_component_connect.
	 * These numbers match up with how the SQL clause gets built (see @var $interval_pattern).
	 */
	public static $interval_component_connect = array(
		'daily'   => 'GROUP BY interval_order',
		'weekly'  => 'GROUP BY YEARWEEK(interval_order)',
		'monthly' => 'GROUP BY YEAR(interval_order), MONTH(interval_order)',
		'yearly'  => 'GROUP BY YEAR(interval_order)',
	);

	/**
	 * Basic query clause builder for time span.
	 *
	 * @var array $time_span_component_connect
	 */
	public static $time_span_component_connect = array(
		'week'  => ' WHERE interval_order >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
		'month' => ' WHERE interval_order >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
		'year'  => ' WHERE interval_order >= DATE_SUB(NOW(), INTERVAL 1 YEAR)',
		'5year' => ' WHERE interval_order >= DATE_SUB(NOW(), INTERVAL 5 YEAR)',
		'all'   => '',
	);

	/**
	 * Basic query clause builder for time span.
	 *
	 * @param string $begin_date Simple date object that represents the data to include.
	 */
	public static function time_span_component_connect( $begin_date ) {
		global $wpdb;
		return $wpdb->prepare( ' AND interval_order >= %s', $begin_date );
	}

	/**
	 * String representation of each of the different intervals.
	 *
	 * @var array $interval_to_date
	 */
	public static $interval_to_date = array(
		'daily'   => 'P1D',

		'week'    => 'P1W',
		'weekly'  => 'P7D',

		'month'   => 'P1M',
		'monthly' => 'P1M',

		'year'    => 'P1Y',
		'yearly'  => 'P1Y',
		'5year'   => 'P5Y',

		'all'     => 1,
	);

	/**
	 * Formats the date to ensure the correct date component is checked (based on interval)
	 *
	 * @var array $format_check.
	 */
	private static $format_check = array(
		'daily'   => 'Ymd',
		'weekly'  => 'Ymd',
		'monthly' => 'Ym',
		'yearly'  => 'Y',
	);

	/**
	 * Builds an array of the correct interval labels for the given query input.
	 *
	 * @param string $interval The given interval this function should cover:
	 *  - daily   = 1
	 *  - weekly  = 7
	 *  - monthly = x
	 *  - yearly  = 365
	 * .
	 * @param string $time_span The given time span this function should cover:
	 *  - week -> (7, 1, 0, 0)
	 *  - month -> (x (changes), x (changes), 1, 0)
	 *  - year -> (365, 52, 12, 1)
	 *  - 5year -> (365 * 5, 52 * 5, 60, 5)
	 *  - all
	 * .
	 * @param string $end_date If $time_span is set to "all", $end_date will be the date that
	 *  this function should run until. Otherwise, does not mater.
	 * @param bool   $is_all_time All time bool since this should not have certain interval adjustments.
	 */
	public static function compute_intervals( $interval, $time_span, $end_date, $is_all_time = 0 ) {
		$dates         = array();
		$date_interval = new DateInterval( self::$interval_to_date[ $interval ] );
		$current_date  = new DateTime();

		$until_date = new DateTime( $end_date );

		// Get number of days til start of next $interval (as this won't match the following ones)
		if ( 0 === strcmp( 'daily', $interval ) || 0 === strcmp( 'yearly', $interval ) ) {
			array_push( $dates, $until_date->format( 'Y-m-d H:i:s' ) );
			$until_date = date_add( $until_date, $date_interval );
		} elseif ( 0 === strcmp( 'weekly', $interval ) ) {
			$week_delta = 7 - intval( $until_date->format( 'w' ) );
			$current    = intval( $until_date->format( 'j' ) );
			$week_start = $current - ( 7 - $week_delta ) <= 0 ? 1 : $current - ( 7 - $week_delta );
			$new_day    = $current + $week_delta;
			$new_month  = intval( $until_date->format( 'm' ) );
			$new_year   = intval( $until_date->format( 'Y' ) );

			$build_new_date = null;
			$days_over_week = $new_day - intval( $until_date->format( 't' ) );
			if ( $days_over_week > 0 ) {
				$new_month++;
				if ( $new_month > 12 ) {
					$new_year++;
					$new_month = 1;
				}

				$build_new_date = new DateTime(
					$until_date->format( $new_year . '-' . $new_month . '-' . $days_over_week . ' H:i:s' )
				);
			} else {
				$build_new_date = new DateTime( $until_date->format( $new_year . '-' . $new_month . '-' . $new_day . ' H:i:s' ) );
			}
			array_push( $dates, $until_date->format( $is_all_time ? 'Y-m-d H:i:s' : 'Y-m-' . $week_start . ' H:i:s' ) );
			$until_date = $build_new_date;
		} elseif ( 0 === strcmp( 'monthly', $interval ) ) {
			$new_month = intval( $until_date->format( 'm' ) ) + 1;
			$new_year  = intval( $until_date->format( 'Y' ) );
			if ( $new_month > 12 ) {
				$new_year++;
				$new_month = 1;
			}
			array_push( $dates, $until_date->format( $is_all_time ? 'Y-m-d H:i:s' : 'Y-m-01 H:i:s' ) );
			$until_date = new DateTime( $until_date->format( $new_year . '-' . $new_month . '-01 H:i:s' ) );
		}

		// Continue running function until
		while ( $until_date->format( self::$format_check[ $interval ] ) <= $current_date->format( self::$format_check[ $interval ] ) ) {
			array_push( $dates, $until_date->format( 'Y-m-d 23:59:59' ) );
			$until_date = date_add( $until_date, $date_interval );
		};

		return $dates;
	}
}
PeachPay_Analytics_Time::instance();
