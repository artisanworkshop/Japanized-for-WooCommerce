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
	$currencies              = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', null );
	foreach ( $currencies as $currency ) {
		$allowed = explode( ',', $currency['countries'] );
		if ( in_array( $iso_code, $allowed, true ) ) {
			array_push( $currency_restrictions, $currency );
		}
		// there will for some reason always be one empty string in the array so just account for that with 2 >.
		if ( 2 > count( $allowed ) ) {
			array_push( $unrestricted_currencies, $currency );
		}
	}

	return 1 > count( $currency_restrictions ) ? $unrestricted_currencies : $currency_restrictions;
}

/**
 * For geolocation purposes this will tell us the clients country returns countrys iso code.
 */
function peachpay_get_client_country() {
	$client    = new WC_Geolocation();
	$client_ip = $client->geolocate_ip( '', true, true );
	return $client_ip['country'];
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
	$best_fit   = ! empty( $currencies ) ? $currencies[0]['name'] : peachpay_get_base_currency();

	foreach ( $currencies as $currency ) {
		if ( substr( $currency['name'], 0, 2 ) === $country ) {
			return $currency['name'];
		}
		if ( 'EUR' === $currency['name'] && in_array( $country, $peachpay_eu_countries, true ) !== false ) {
			$best_fit = 'EUR';
		}
		if ( 'USD' === $currency['name'] && in_array( $country, $peachpay_na_countries, true ) !== false ) {
			$best_fit = 'USD';
		}
	}

	return $best_fit;
}
