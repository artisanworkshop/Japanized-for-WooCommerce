<?php
use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

add_action( 'rest_api_init', function () {
    register_rest_route( 'yahoo/v1', '/postcode/', array(
        'methods' => 'POST',
        'callback' => 'yahoo_api_postcode',
        'permission_callback' => '__return_true',
    ) );
} );

/**
 * Yahoo API Postal Code Webhook response.
 * Version: 2.6.4
 *
 * @param object $request post data.
 * @return WP_REST_Response | WP_Error endpoint Paidy webhook response
 */
function yahoo_api_postcode( $request ){
    $jp4wc_framework =new Framework\JP4WC_Plugin();
	$debug = true;
    if ( empty( $request ) ) {
        $message = 'no_data';
        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'jp4wc');

        return new WP_Error( 'no_data', 'Invalid author', array( 'status' => 404 ) );
    }elseif( isset( $request['post_code'] ) ){
	    $yahoo_app_id      = $request['yahoo_app_id'] ?? 'dj0zaiZpPWZ3VWp4elJ2MXRYUSZzPWNvbnN1bWVyc2VjcmV0Jng9MmY-';
	    $yahoo_api_zip_url = 'https://map.yahooapis.jp/search/zip/V1/zipCodeSearch';
	    $param = array(
		    'query' => $request['post_code'],
		    'appid' => $yahoo_app_id,
		    'output' => 'json',
	    );
	    $url = $yahoo_api_zip_url.'?'.http_build_query($param);

	    // Open a connection
	    $conn = curl_init();

		// It does not verify the server certificate.
	    curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, false);

		// Set so that the execution result of curl_exec can be obtained as a character string
	    curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);

		// Specify the contact url
	    curl_setopt($conn, CURLOPT_URL,  $url);

		// Make an inquiry, get the result and disconnect
	    $result = curl_exec($conn);
		curl_close($conn);

		// Convert from json to associative array
	    $result_array = json_decode($result, true);
		if(isset($result_array['Feature'][0]['Property']['Address'])){
			$postcode_address = $result_array['Feature'][0]['Property']['Address'];
			$jp4wc_countries = new WC_Countries;
			$states = $jp4wc_countries->get_states();
			$set_prefecture_code = 0;
			$set_prefecture_name = 0;
			foreach($states['JP'] as $key => $value){
				if(mb_substr($value, 0, 3) === mb_substr( $postcode_address, 0, 3)){
					$set_prefecture_code = $key;
					$set_prefecture_name = $value;
				}
			}
			if($set_prefecture_code === 0){
				return new WP_Error( 'no_address', 'No match address', array( 'status' => 404 ));
			}else{
				$postcode_result = array(
					'state_code' => $set_prefecture_code,
					'state' => $set_prefecture_name,
					'city' => str_replace($states['JP'][$set_prefecture_code], '', $postcode_address),
				);
				return new WP_REST_Response($postcode_result, 200);
			}
		}else{
			return new WP_Error( 'no_address', 'No match address', array( 'status' => 404 ));
		}
    }else{
        // Debug
        $message = '[no_postcode]'. $jp4wc_framework->jp4wc_array_to_message($request);
        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'jp4wc');
        return new WP_Error( 'no_postcode', 'No post code', array( 'status' => 404 ) );
    }
}

