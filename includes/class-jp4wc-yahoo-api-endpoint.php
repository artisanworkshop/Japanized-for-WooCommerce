<?php
/**
 * Yahoo API endpoint for postal code lookup.
 *
 * @package Japanized_For_WooCommerce
 */

use ArtisanWorkshop\PluginFramework\v2_0_14 as Framework;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'yahoo/v1',
			'/postcode/',
			array(
				'methods'             => 'POST',
				'callback'            => 'jp4wc_yahoo_api_postcode',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/**
 * Rate limit check for the Yahoo postal code endpoint.
 *
 * Allows up to JP4WC_POSTCODE_RATE_LIMIT requests per JP4WC_POSTCODE_RATE_WINDOW
 * seconds per remote IP. Uses WordPress transients for storage.
 *
 * @return WP_Error|true WP_Error with status 429 when limit exceeded, true otherwise.
 */
function jp4wc_postcode_check_rate_limit() {
	$limit  = defined( 'JP4WC_POSTCODE_RATE_LIMIT' ) ? JP4WC_POSTCODE_RATE_LIMIT : 10;
	$window = defined( 'JP4WC_POSTCODE_RATE_WINDOW' ) ? JP4WC_POSTCODE_RATE_WINDOW : 60;

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- REMOTE_ADDR is server-set
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'jp4wc_rl_' . md5( $ip );

	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return new WP_Error(
			'rate_limit_exceeded',
			__( 'Too many requests. Please try again later.', 'woocommerce-for-japan' ),
			array( 'status' => 429 )
		);
	}

	if ( 0 === $count ) {
		set_transient( $key, 1, $window );
	} else {
		// Preserve remaining TTL by incrementing without resetting the window.
		set_transient( $key, $count + 1, $window );
	}

	return true;
}

/**
 * Yahoo API Postal Code Webhook response.
 * Version: 2.7.17
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function jp4wc_yahoo_api_postcode( $request ) {
	$rate_limit_result = jp4wc_postcode_check_rate_limit();
	if ( is_wp_error( $rate_limit_result ) ) {
		return $rate_limit_result;
	}

	$jp4wc_framework = new Framework\JP4WC_Framework();
	$debug           = true;
	if ( empty( $request ) ) {
		$message = 'no_data';
		$jp4wc_framework->jp4wc_debug_log( $message, $debug, 'jp4wc' );

		return new WP_Error( 'no_data', 'Invalid author', array( 'status' => 404 ) );
	} elseif ( isset( $request['post_code'] ) ) {
		// Use server-side stored App ID only; never accept from client request params.
		$yahoo_app_id = get_option( 'wc4jp-yahoo-app-id' );
		if ( empty( $yahoo_app_id ) ) {
			$yahoo_app_id = 'dj0zaiZpPWZ3VWp4elJ2MXRYUSZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-';
		}
		$post_code         = sanitize_text_field( $request['post_code'] );
		$yahoo_api_zip_url = 'https://map.yahooapis.jp/search/zip/V1/zipCodeSearch';
		$param             = array(
			'query'  => $post_code,
			'appid'  => $yahoo_app_id,
			'output' => 'json',
		);

		$url = $yahoo_api_zip_url . '?' . http_build_query( $param );

		// Use WordPress HTTP API instead of direct curl calls.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 10,
				'sslverify' => apply_filters( 'jp4wc_yahoo_api_sslverify', true ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$result = wp_remote_retrieve_body( $response );

		// Convert from json to associative array.
		$result_array = json_decode( $result, true );
		if ( isset( $result_array['Feature'][0]['Property']['Address'] ) ) {
			$postcode_address = $result_array['Feature'][0]['Property']['Address'];

			// Fixed Japanese prefecture name → WooCommerce state code map.
			// Yahoo API always returns Japanese addresses regardless of site locale,
			// so we match against the Japanese names directly rather than relying
			// on WC_Countries::get_states() which returns translated names.
			$jp_prefecture_map = array(
				'北海道'  => 'JP01',
				'青森県'  => 'JP02',
				'岩手県'  => 'JP03',
				'宮城県'  => 'JP04',
				'秋田県'  => 'JP05',
				'山形県'  => 'JP06',
				'福島県'  => 'JP07',
				'茨城県'  => 'JP08',
				'栃木県'  => 'JP09',
				'群馬県'  => 'JP10',
				'埼玉県'  => 'JP11',
				'千葉県'  => 'JP12',
				'東京都'  => 'JP13',
				'神奈川県' => 'JP14',
				'新潟県'  => 'JP15',
				'富山県'  => 'JP16',
				'石川県'  => 'JP17',
				'福井県'  => 'JP18',
				'山梨県'  => 'JP19',
				'長野県'  => 'JP20',
				'岐阜県'  => 'JP21',
				'静岡県'  => 'JP22',
				'愛知県'  => 'JP23',
				'三重県'  => 'JP24',
				'滋賀県'  => 'JP25',
				'京都府'  => 'JP26',
				'大阪府'  => 'JP27',
				'兵庫県'  => 'JP28',
				'奈良県'  => 'JP29',
				'和歌山県' => 'JP30',
				'鳥取県'  => 'JP31',
				'島根県'  => 'JP32',
				'岡山県'  => 'JP33',
				'広島県'  => 'JP34',
				'山口県'  => 'JP35',
				'徳島県'  => 'JP36',
				'香川県'  => 'JP37',
				'愛媛県'  => 'JP38',
				'高知県'  => 'JP39',
				'福岡県'  => 'JP40',
				'佐賀県'  => 'JP41',
				'長崎県'  => 'JP42',
				'熊本県'  => 'JP43',
				'大分県'  => 'JP44',
				'宮崎県'  => 'JP45',
				'鹿児島県' => 'JP46',
				'沖縄県'  => 'JP47',
			);

			$set_prefecture_code = '';
			$set_prefecture_jp   = '';

			foreach ( $jp_prefecture_map as $jp_name => $code ) {
				if ( 0 === mb_strpos( $postcode_address, $jp_name ) ) {
					$set_prefecture_code = $code;
					$set_prefecture_jp   = $jp_name;
					break;
				}
			}

			if ( '' === $set_prefecture_code ) {
				return new WP_Error( 'no_address', 'No match address', array( 'status' => 404 ) );
			} else {
				// Use localized state name from WC_Countries for the response.
				$jp4wc_countries     = new WC_Countries();
				$states              = $jp4wc_countries->get_states();
				$set_prefecture_name = isset( $states['JP'][ $set_prefecture_code ] )
					? $states['JP'][ $set_prefecture_code ]
					: $set_prefecture_jp;

				$postcode_result = array(
					'state_code' => $set_prefecture_code,
					'state'      => $set_prefecture_name,
					'city'       => mb_substr( $postcode_address, mb_strlen( $set_prefecture_jp ) ),
				);
				return new WP_REST_Response( $postcode_result, 200 );
			}
		} else {
			return new WP_Error( 'no_address', 'No match address', array( 'status' => 404 ) );
		}
	} else {
		// Debug.
		$message = '[no_postcode]' . $jp4wc_framework->jp4wc_array_to_message( $request );
		$jp4wc_framework->jp4wc_debug_log( $message, $debug, 'jp4wc' );
		return new WP_Error( 'no_postcode', 'No post code', array( 'status' => 404 ) );
	}
}
