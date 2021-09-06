<?php
/**
 * Line Pay endpoint for Shipping
 *
 * @version		1.1.1
 * @author 		Artisan Workshop
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

add_action( 'rest_api_init', function () {
    register_rest_route( 'linepay/v1', '/shippings/', array(
        'methods' => 'POST',
        'callback' => 'linepay_shipping_webhook',
        'permission_callback' => '__return_true',
    ) );
} );

/**
 * LinePay Shipping Webhook response.
 *
 * @param object $data post data.
 * @return WP_REST_Response | WP_Error endpoint LinePay webhook response
 */
function linepay_shipping_webhook( $data ){
    $jp4wc_framework =new Framework\JP4WC_Plugin();
    $linepay = new WC_Gateway_LINEPay();
    $debug = $linepay->debug;
    $order_prefix = $linepay->order_prefix;
    if ( empty( $data ) ) {
        $message = "no_data";
        $jp4wc_framework->jp4wc_debug_log($message, $debug, 'linepay-wc');

        $response_data['returnCode'] = '5001';
        $response_data['returnMessage'] = '内部サーバーエラー(不明なエラー)';

        return new WP_REST_Response( $response_data, 200 );
    }
    $body_data = (array)$data->get_body();
    $shippingMethods = json_decode($body_data[0]);

    // debug Received data at webhook
	$message = 'Received data related to shipping at webhook.' . "\n" . $body_data[0];
    $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'linepay-wc');

    $currency = $shippingMethods->requiredCurrency;
    $orderId = $shippingMethods->orderId;
    $transactionId = $shippingMethods->transactionId;
    $postalCode = $shippingMethods->shippingAddress->postalCode;
    $state = $shippingMethods->shippingAddress->state;
    $city = $shippingMethods->shippingAddress->city;
    $country = $shippingMethods->shippingAddress->country;
    //Set Order detail
    $order_id = ltrim($orderId, $order_prefix);
    $order = wc_get_order($order_id);
    //Set States
    if(version_compare( WC_VERSION, '3.6', '>=' )){
        $jp4wc_countries = new WC_Countries;
        $default_states = $jp4wc_countries->get_states( $country );
    }else{
        global $states;
        $default_states = $states;
    }
    $current_states = array();
    foreach ($default_states as $key => $value){
        $current_states[$value] = $key;
    }
    $state_code = $current_states[$state];

    $package['destination']['country'] = $country;
    $package['destination']['state'] = $state_code;
    $package['destination']['postcode'] = $postalCode;

    $shipping_zone = WC_Shipping_Zones::get_zone_matching_package( $package );
    $shipping_methods = $shipping_zone->get_shipping_methods( true );
    $returnCode = '0000';
    $returnMessage = 'OK';
	foreach($shipping_methods as $shipping_method){
        if($shipping_method->id == 'flat_rate') {
            $response_id = $shipping_method->id;
            $response_name = $shipping_method->title;
	        $response_amount = $jp4wc_framework->jp4wc_price_round_cal((float)$shipping_method->instance_settings['cost']);
	        if($shipping_method->instance_settings['tax_status'] == 'taxable'){
	        	$tax_rates = WC_Tax::get_rates();
	        	foreach ($tax_rates as $tax_rate){
	        		if($tax_rate['shipping'] == 'yes'){
	        			$rate = $tax_rate['rate'];
			        }
		        }
		        if(isset($rate))$response_amount = $jp4wc_framework->jp4wc_price_round_cal(($response_amount * (1 + $rate/100)));
	        }
        }elseif($shipping_method->id == 'free_shipping'){
            if($shipping_method->instance_settings['requires'] == 'min_amount'){
                if((float)$shipping_method->instance_settings['min_amount'] <= $order->get_total()){
                    $response_id = $shipping_method->id;
                    $response_name = $shipping_method->title;
                    $response_amount = 0;
                }
            }
        }else{
            $returnCode = '4001';
            $returnMessage = 'Mistake shipment.';
            $response_id = $shipping_method->id;
            $response_name = $shipping_method->title;
            $response_amount = $shipping_method->instance_settings['cost'];
        }
    }
    $log_message = 'Return '.$returnCode.':'.$returnMessage."\n";
    if(isset($response_id)){
        $log_message .= $response_id.':'.$response_name.':'.$response_amount;
    }
    $jp4wc_framework->jp4wc_debug_log( $log_message, $debug, 'linepay-wc');

    $response_data['returnCode'] = $returnCode;
    $response_data['returnMessage'] = $returnMessage;
    if(isset($response_id) && isset($response_name) && isset($response_amount)){
        $methods['id'] = $response_id;
        $methods['name'] = $response_name;
        $methods['amount'] = $response_amount;
//        $methods['toDeliveryYmd'] = date('Ymd',strtotime('+3 day'));
        $response_data['info']['shippingMethods'] = array($methods);
    }

    return new WP_REST_Response( $response_data, 200 );
}

