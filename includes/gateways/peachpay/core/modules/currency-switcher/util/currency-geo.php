<?php
/**
 * A file for cleaning up currency switcher this file holds the geolocation features.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

$peachpay_eu_countries = array(
	'AD',
	'AL',
	'AT',
	'AX',
	'BA',
	'BE',
	'BG',
	'BY',
	'CH',
	'CZ',
	'DE',
	'DK',
	'EE',
	'ES',
	'EU',
	'FI',
	'FO',
	'FR',
	'FX',
	'GB',
	'GG',
	'GI',
	'GR',
	'HR',
	'HU',
	'IE',
	'IM',
	'IS',
	'IT',
	'JE',
	'LI',
	'LT',
	'LU',
	'LV',
	'MC',
	'MD',
	'ME',
	'MK',
	'MT',
	'NL',
	'NO',
	'PL',
	'PT',
	'RO',
	'RS',
	'RU',
	'SE',
	'SI',
	'SJ',
	'SK',
	'SM',
	'TR',
	'UA',
	'VA',
);

$peachpay_na_countries = array(
	'AG',
	'AI',
	'AN',
	'AW',
	'BB',
	'BL',
	'BM',
	'BS',
	'BZ',
	'CA',
	'CR',
	'CU',
	'DM',
	'DO',
	'GD',
	'GL',
	'GP',
	'GT',
	'HN',
	'HT',
	'JM',
	'KN',
	'KY',
	'LC',
	'MF',
	'MQ',
	'MS',
	'MX',
	'NI',
	'PA',
	'PM',
	'PR',
	'SV',
	'TC',
	'TT',
	'US',
	'VC',
	'VG',
	'VI',
);

/**
 * Get all the currencies a country is allowed to use on the site.
 *
 * @param string $iso_code the countries iso code.
 */
function peachpay_currencies_by_iso( $iso_code ) {
	$currency_restrictions   = array();
	$unrestricted_currencies = array();
	$currencies              = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() );

	foreach ( $currencies as $currency ) {
		if ( ! isset( $currency['countries'] ) ) {
			array_push( $unrestricted_currencies, $currency['name'] );
			continue;
		}
		$allowed = explode( ',', $currency['countries'] );
		if ( in_array( $iso_code, $allowed, true ) ) {
			array_push( $currency_restrictions, $currency['name'] );
			continue;
		}
		// there will for some reason always be one empty string in the array so just account for that with 2 >.
		if ( ! isset( $currency['countries'] ) || 2 > count( $allowed ) ) {
			array_push( $unrestricted_currencies, $currency['name'] );
		}
	}

	return 1 <= count( $currency_restrictions ) ? $currency_restrictions : $unrestricted_currencies;
}

/**
 * Get's clients country code based on billing country if configured and available, otherwise geolocate.
 */
function peachpay_get_client_country() {
	if ( 'billing_country' === peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults' ) ) {
		return isset( WC()->customer ) ? WC()->customer->get_billing_country() : '';
	}

	return peachpay_get_client_geolocation();
}

/**
 * Get's client geolocation
 */
function peachpay_get_client_geolocation() {
	if ( ! class_exists( 'WC_Geolocation' ) ) {
		add_action( 'admin_notices', 'pp_failed_wc_geo' );
		return '';
	}
	$client = new WC_Geolocation();
	// If you ever need to test this feature just change the string in geolocate_ip to a real ip of a country.
	// some test IP's : Japan->89.187.160.155, Canada->91.245.254.78, UK->185.108.105.114, Mexico->194.41.112.20 .
	$client_ip = $client->geolocate_ip( '', true, true );
	return $client_ip['country'];
}

/**
 * Make an alert if there is no WC_geolocation detected.
 */
function pp_failed_wc_geo() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p><?php esc_html_e( 'Could not detect WC_geolocation class, all geolocation functions for the currency switcher will not function.', 'peachpay-for-woocommerce' ); ?></p>
	</div>
	<?php
}

/**
 * This will select the best currency by country for a user from the table.
 *
 * @param string $country the country of a user.
 */
function peachpay_best_currency( $country ) {
	global $peachpay_eu_countries;
	global $peachpay_na_countries;
	$currencies = peachpay_currencies_by_iso( $country );
	$best_fit   = ! empty( $currencies ) ? array_pop( $currencies ) : peachpay_get_base_currency();

	foreach ( $currencies as $currency ) {
		if ( substr( $currency, 0, 2 ) === $country ) {
			return $currency;
		}
		if ( 'EUR' === $currency && in_array( $country, $peachpay_eu_countries, true ) !== false ) {
			$best_fit = 'EUR';
		}
		if ( 'USD' === $currency && in_array( $country, $peachpay_na_countries, true ) !== false ) {
			$best_fit = 'USD';
		}
	}

	return $best_fit;
}
