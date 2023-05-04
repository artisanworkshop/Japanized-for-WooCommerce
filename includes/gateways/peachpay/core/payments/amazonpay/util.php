<?php
/**
 * PeachPay Square utility functions
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Determines whether Amazon Pay is enabled
 */
function peachpay_amazonpay_enabled() {
	if ( peachpay_is_test_mode() ) {
		return peachpay_get_settings_option( 'peachpay_payment_options', 'amazonpay_enable', 0 );
	}
	return peachpay_amazonpay_account_connected() && peachpay_get_settings_option( 'peachpay_payment_options', 'amazonpay_enable', 0 );
}

/**
 * Returns the correct spId given the country. (currently taken from WC Amazon Pay)
 *
 * @param string $country the country the store is based in.
 */
function peachpay_get_amazonpay_spid( $country ) {
	switch ( $country ) {
		case 'US':
		case 'CA':
			return 'A1BVJDFFHQ7US4';
		case 'GB':
			return 'A3AO8502KEOZS3';
		case 'JP':
			return 'A2EBW2CGZKMGE4';
		case 'DK':
		case 'FR':
		case 'DE':
		case 'HU':
		case 'IT':
		case 'LU':
		case 'AW':
		case 'PT':
		case 'ES':
		case 'SE':
			return 'A3V6YX13IG1QFQ';
		default:
			return false;
	}
}

/**
 * Determines if square is connected.
 */
function peachpay_amazonpay_account_connected() {
	return get_option( 'peachpay_connected_amazonpay_account', 0 );
}

/**
 * Returns the amazon pay register link for linking amazon pay account.
 *
 * @param String $country amazon register link for a given country.
 */
function peachpay_amazonpay_register_link( $country ) {
	switch ( $country ) {
		case 'US':
		case 'CA':
			return 'https://payments.amazon.com/register';
		case 'GB':
		case 'DK':
		case 'FR':
		case 'DE':
		case 'HU':
		case 'IT':
		case 'LU':
		case 'AW':
		case 'PT':
		case 'ES':
		case 'SE':
			return 'https://payments-eu.amazon.com/register';
		default:
			return false;
	}
}

/**
 * Generates the public key we use to verify whether the callback to this stores
 * rest API from Amazon is valid.
 *
 * @throws Exception When openssl not present in environment.
 */
function peachpay_generate_amazonpay_public_key() {
	if ( ! function_exists( 'openssl_pkey_new' ) || ! function_exists( 'openssl_verify' ) ) {
		throw new Exception( esc_html__( 'OpenSSL extension is not available in your server.', 'peachpay-for-woocommerce' ) );
	}

	$keys = openssl_pkey_new(
		array(
			'digest_alg'       => 'sha1',
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		)
	);

	$public_key_pem = openssl_pkey_get_details( $keys )['key'];
	openssl_pkey_export( $keys, $private_key );
	$public_key = str_replace( array( '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n" ), array( '', '', '' ), $public_key_pem );
	openssl_pkey_export( $keys, $private_key );

	$temp_keys   = peachpay_get_amazonpay_temp_keys();
	$temp_keys[] = $private_key;

	peachpay_set_settings_option( 'peachpay_payment_options', 'amazonpay_temp_keys', $temp_keys );

	return $public_key;
}

/**
 * Returns the keyshare GET url admazon will transfer keys too.
 */
function peachpay_amazonpay_get_keyshare_url() {
	$base = peachpay_api_url() . 'api/v1/amazonpay/connect/oauth';

	$state = array(
		'merchant_domain' => home_url(),
		'merchant_id'     => peachpay_plugin_merchant_id(),
		'merchant_rest'   => get_rest_url(),
	);

	$json_encode = wp_json_encode( $state );
	// PHPCS:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	$encoded = base64_encode( $json_encode );

	return $base . '?state=' . $encoded;
}

/**
 * Returns the temp keys that have been generated, oldest to newest.
 */
function peachpay_get_amazonpay_temp_keys() {
	return peachpay_get_settings_option( 'peachpay_payment_options', 'amazonpay_temp_keys', array() );
}

/**
 * Clears the stored amazon pay temp keys
 */
function peachpay_clear_amazonpay_temp_keys() {
	peachpay_set_settings_option( 'peachpay_payment_options', 'amazonpay_temp_keys', array() );
}

/**
 * Given the public_key_id provided in the amazon account transferKey POST, verify
 * it is a valid response.
 *
 * @param String $public_key_id encrypted public key.
 */
function peachpay_validate_amazonpay_public_key_id( $public_key_id ) {
	$private_keys  = array_reverse( peachpay_get_amazonpay_temp_keys() );
	$decrypted_key = null;
	$found         = false;

	foreach ( $private_keys as $private_key ) {
		$res = openssl_private_decrypt(
			base64_decode( $public_key_id ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode, WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$decrypted_key,
			$private_key
		);

		if ( $res ) {
			$found = $private_key;
			break;
		}
	}

	if ( ! $found ) {
		return false;
	}

	return array(
		'publicKeyId'  => $decrypted_key,
		'privateKeyId' => $found,
	);
}

/**
 * Creates the amazon pay connect account link
 */
function peachpay_amazonpay_signup_link() {
	return get_rest_url( null, 'peachpay/v1/amazonpay/signup' );
}

/**
 * Generates the URL users are sent back to after the user signs in with their amazon pay account
 */
function peachpay_amazonpay_return_url() {
	return get_admin_url() . '?page=peachpay&tab=payment';
}

/**
 * Check because getallheaders is only available for apache, we need a fallback in case of nginx or others,
 * http://php.net/manual/es/function.getallheaders.php
 *
 * @return array
 */
function peachpay_get_all_headers() {
	if ( ! function_exists( 'getallheaders' ) ) {
		$headers = array();
		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) === 'HTTP_' ) {
				$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}
		return $headers;

	} else {
		return getallheaders();
	}
}

/**
 * Apache uses capital, nginx uses not capitalised.
 *
 * @return string
 */
function peachpay_get_origin_header() {
	$headers = peachpay_get_all_headers();
	return ( $headers['Origin'] ) ? $headers['Origin'] : $headers['origin'];
}

/**
 * Returns the supported AmazonPay currency
 * amazon-pay only supports the currency in which the merchant has configured
 * funds to be transferred as.
 */
function peachpay_amazonpay_currency() {
	return get_woocommerce_currency();
}

/**
 * Returns AmazonPay supported countries
 */
function peachpay_amazonpay_supported_countries() {
	return array( 'US', 'GB', 'DK', 'FR', 'DE', 'HU', 'IT', 'JP', 'LU', 'AW', 'PT', 'ES', 'SE' );
}
