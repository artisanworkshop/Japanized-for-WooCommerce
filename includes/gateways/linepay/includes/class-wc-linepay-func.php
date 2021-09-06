<?php

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * LINE Pay Gateway
 *
 * LINE Pay use functions.
 *
 * @class 	WC_Gateway_LINEPay
 * @version	1.1.1
 * @author	Artisan Workshop
 */

/**
 * Generates requests to send to Epsilon
 */
class LINEPay_func {

	/**
	 * Pointer to gateway making the request
	 * @var WC_Gateway_LINEPay
	 */
	protected $gateway;

    /**
     * Framework.
     *
     * @var stdClass
     */
    public $jp4wc_framework;

    /**
     * LINE Pay API connect endpoint for Production.
     *
     * @var string
     */
    public $api_endpoint_production;

    /**
     * LINE Pay API connect channel id for Production.
     *
     * @var string
     */
    public $api_channel_id;

    /**
     * LINE Pay API connect channel secret key for Production.
     *
     * @var string
     */
    public $api_channel_secret_key;

    /**
	 * Constructor
	 * @param WC_Gateway_LINEPay $gateway
	 */
	public function __construct() {
        //Set LINE Pay endpoint
        $this->api_endpoint_production = 'https://api-pay.line.me';

        $this->jp4wc_framework = new Framework\JP4WC_Plugin();

        $setting_array = get_option('woocommerce_linepay_settings');
        //Set API chanel ids and keys
        if(isset($setting_array['environment']) && $setting_array['environment'] == 'production'){
            $this->api_channel_id = $setting_array['api_channel_id'];
            $this->api_channel_secret_key = $setting_array['api_channel_secret_key'];
        }else{
            if(isset($setting_array['test_api_channel_id']))$this->api_channel_id = $setting_array['test_api_channel_id'];
            if(isset($setting_array['test_api_channel_secret_key']))$this->api_channel_secret_key = $setting_array['test_api_channel_secret_key'];
        }

    }

    /**
     * Make json content from array
     *
     * @return array $items
     */
	public function array_products(){
        $items = array();
        $cart_data = WC()->cart->get_cart();
        $fees = WC()->cart->get_fees();
        $coupons = WC()->cart->get_coupons();
        foreach ( $cart_data as $cart_item_key => $cart_item ) {
            $_product = $cart_item['data'];
            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                $price = round( ( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] ) / $cart_item['quantity'] );
                $item['id'] = $cart_item['product_id'];
                $item['name'] = $_product->get_name();
                $item['imageUrl'] = wp_get_attachment_image_url( $_product->get_image_id(), 'thumbnail' );
                $item['quantity'] = $cart_item['quantity'];
                $item['price'] = $this->jp4wc_framework->jp4wc_price_round_cal( $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'] )/$cart_item['quantity'];
	            $item['originalPrice'] = $price;
                array_push($items, $item);
            }
        }
        if(isset( $fees )) {
            $i = 1;
            foreach ( $fees as $fee ){
                $item['id'] = 'fee'.$i;
                $item['name'] = $fee->get_name();
                $item['quantity'] = 1;
                $item['price'] = $fee->get_amount();
                array_push($items, $item);
                $i++;
            }
        }
        if(isset( $coupons )) {
            $i = 1;
            foreach ( $coupons as $coupon ){
                $item['id'] = 'coupon'.$i;
                $item['name'] = $coupon->get_code();
                $item['quantity'] = 1;
                $item['price'] = '-'. $coupon->get_amount();
                array_push($items, $item);
                $i++;
            }
        }
        return $items;
	}
	/**
	 * Make json content from array
	 *
	 * @return int $amount
	 */
	public function get_cart_subtotal(){
		$cart_data = WC()->cart->get_cart();
		$fees      = WC()->cart->get_fees();
		$coupons   = WC()->cart->get_coupons();
		$amount    = 0;
		foreach ( $cart_data as $cart_item_key => $cart_item ) {
			$_product = $cart_item['data'];
			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$amount += $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
			}
		}
		if(isset( $coupons )) {
			foreach ( $coupons as $coupon ){
				$amount -= $coupon->get_amount();
			}
		}
		return round($amount);
	}
    /**
     * Send data by Post data for LINE Pay API
     *
     * @param string $uri
     * @param string $json_content
     * @param string $debug
     * @param string $send_method
     * @param string $order_id
     * @throws
     * @return array | mixed
     */
	public function send_api_linepay( $uri, $json_content, $debug, $send_method ,$order_id = null ){
	    $url = $this->api_endpoint_production;
	    // Set the Log of post data.
	    $message = 'Send api data. requestUri is ' . $uri . '.' . "\n" . 'Post data is following' . "\n" . $json_content;
        $this->jp4wc_framework->jp4wc_debug_log($message , $debug, 'linepay-wc');

        $headers = $this->get_linepay_headers($uri, $json_content);
        $args = array(
            'method' => $send_method,
            'httpversion'	=> '1.1',
            'timeout'		=> 20,
            'headers' => $headers,
        );
        $args['body'] = '';
        if($send_method == 'POST'){
            $args['body'] = $json_content;
        }elseif($send_method == 'GET'){
            $uri .= '?'.$json_content;
        }
	    $response = wp_remote_post($url.$uri, $args);

        $response_body = static::json_custom_decode( $response['body'] );

        if ( is_wp_error( $response ) ){
            $error_message = $this->make_debug_message($response->get_error_message(), $order_id);
            $this->jp4wc_framework->jp4wc_debug_log($error_message, $debug, 'linepay-wc');
            return false;
        }elseif ( $response['response']['code'] != 200 ) {
            $res_error_message = $this->make_debug_message($response['response']['code'].' - '.$response['response']['message'], $order_id);
            $this->jp4wc_framework->jp4wc_debug_log($res_error_message, $debug, 'linepay-wc');
            return false;
        } else {
            $response_message = $this->make_debug_message(var_export($response_body, true), $order_id);
            $this->jp4wc_framework->jp4wc_debug_log($response_message, $debug, 'linepay-wc');
            return $response_body;
        }
    }

    /**
     * Get payments detail by LINE Pay API
     *
     * @param array $send_get
     * @param string $debug
     * @throws
     * @return array | mixed
     */
    public function get_payments_detail($send_get, $debug){
        $url = $this->api_endpoint_production;
        $query_string = http_build_query($send_get);
        $requestUri = '/v3/payments';
        $message = 'Send api data. requestUri is ' . $requestUri . '.' .  "\n" . 'Get data is following' . "\n" . $send_get;
        $this->jp4wc_framework->jp4wc_debug_log( $message , $debug, 'linepay-wc' );
        $headers = $this->get_linepay_headers($requestUri, $query_string);
        $args = array(
            'method' => 'GET',
            'httpversion'	=> '1.1',
            'timeout'		=> 20,
            'headers' => $headers,
            'body' => $send_get
        );
        $response = wp_remote_post($url.$requestUri, $args);

        $response_body = static::json_custom_decode( $response['body'] );

        if ( is_wp_error( $response ) ){
            $error_message = $response->get_error_message();
            $this->jp4wc_framework->jp4wc_debug_log($error_message, $debug, 'linepay-wc');
            return false;
        }elseif ( $response['response']['code'] != 200 ) {
            $this->jp4wc_framework->jp4wc_debug_log($response['response']['code'].' - '.$response['response']['message'], $debug, 'linepay-wc');
            return false;
        } else {
            $response_message = var_export($response_body, true);
            $this->jp4wc_framework->jp4wc_debug_log($response_message, $debug, 'linepay-wc');
            return $response_body;
        }
    }

    /**
     * set send header for LINE Pay API
     *
     * @param string $requestUri
     * @param string $content
     * @throws
     * @return array
     */
    public function get_linepay_headers($requestUri, $content){
        $none = uniqid();
        $body = $this->api_channel_secret_key.$requestUri.$content.$none;
        $signature = base64_encode(hash_hmac( 'sha256', $body, $this->api_channel_secret_key, true ));
        return array(
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-LINE-ChannelId' => $this->api_channel_id,
            'X-LINE-Authorization-Nonce' => $none,
            'X-LINE-Authorization' => $signature
        );
    }

    /**
     * Make debug message with order id.
     *
     * @param string $order_id
     * @param string $message
     * @throws
     * @return string
     */
    public function make_debug_message($message, $order_id = null){
        $return_message = $message;
        if(isset($order_id)){
            $return_message = 'Order ID :'.$order_id.';' . "\n" . $message;
        }
        return $return_message;
    }

    // change large integer to json's string format.
    private static function json_custom_decode($json) {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_decode($json, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            return json_decode( preg_replace ('/:\s?(\d{14,})/', ': "${1}"', $json) );
        }
    }
}
