<?php
use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

add_action( 'rest_api_init', function () {
    register_rest_route( 'paidy/v1', '/order/', array(
        'methods' => 'POST',
        'callback' => 'paidy_check_webhook',
        'permission_callback' => '__return_true',
    ) );
} );

/**
 * Paidy Webhook response.
 * Version: 1.1.3
 *
 * @param object $data post data.
 * @return WP_REST_Response | WP_Error endpoint Paidy webhook response
 */
function paidy_check_webhook( $data ){
    $jp4wc_framework =new Framework\JP4WC_Plugin();
    $paidy = new WC_Gateway_Paidy();
    $debug = $paidy->debug;
    $body_data = (array)$data->get_body();
    $main_data = json_decode($body_data[0], true);
    if ( empty( $data ) ) {
        $message = 'no_data';
        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc');

        return new WP_Error( 'no_data', 'Invalid author', array( 'status' => 404 ) );
    }elseif( isset( $main_data['payment_id'] ) && isset( $main_data['order_ref'] )){
        if(is_numeric($main_data['order_ref'])){
            // Debug
	        if($main_data['payment_id'] == 'pay_0000000000000001'){
		        $message = 'This notification is a test request from Paidy.'. "\n" . $jp4wc_framework->jp4wc_array_to_message($main_data);
		        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc');
		        return new WP_REST_Response($main_data, 200);
	        }else{
		        $message = 'Exist [payment_id] and [order_ref]'. "\n" . $jp4wc_framework->jp4wc_array_to_message($main_data);
		        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc');
	        }

            $order = wc_get_order( $main_data['order_ref'] );
	        if($order === false){
		        $message = 'The order with this order number does not exist in the store.'. "\n" . 'Order# :' . $main_data['order_ref'];
		        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc');
		        return new WP_REST_Response($main_data, 200);
	        }
            $status = $order->get_status();

            if( $main_data["status"] == 'authorize_success' && $status == 'pending' || $status == 'cancelled' ){
                // Reduce stock levels
                wc_reduce_stock_levels($main_data['order_ref']);
                if(isset($main_data['payment_id'])){
                    $order->payment_complete($main_data['payment_id']);
                }else{
                    $order->payment_complete();
                }
                $order->add_order_note(sprintf(__('It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan'), __('authorization', 'woocommerce-for-japan')));
            }elseif( $main_data["status"] == 'authorize_success' && $status == 'processing' ){
                $order->add_order_note( __('This order status is processing, this site received authorize_success from the Paidy webhook.', 'woocommerce-for-japan') );
            }elseif( $main_data["status"] == 'capture_success' && $status == 'processing' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'completed', 'woocommerce-for-japan' ) ) );
            }elseif( $main_data["status"] == 'close_success' && $status == 'cancelled' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'cancelled', 'woocommerce-for-japan' ) ) );
            }elseif( $main_data["status"] == 'close_success' && $status == 'completed' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'close', 'woocommerce-for-japan' ) ) );
            }elseif( $main_data["status"] == 'refund_success' && $status == 'refunded' ){
                $order->add_order_note( sprintf( __( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), __( 'refunded', 'woocommerce-for-japan' ) ) );
            }else{
                $order->add_order_note( sprintf( __( 'It failed to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ), $main_data["status"] ) );
            }
            return new WP_REST_Response($main_data, 200);
        }else{
            // Debug
            $message = 'Payment_id exist but order_id. Payment_id : '.$main_data["payment_id"] . '; Status : ' . $main_data["status"];
            $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc');
            return new WP_Error( 'no_order_id', $message , array( 'status' => 404 ) );
        }
    }else{
        // Debug
        $message = '[no_payment_id]'. $jp4wc_framework->jp4wc_array_to_message($main_data);
        $jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc');
        return new WP_Error( 'no_payment_id', 'Invalid author', array( 'status' => 404 ) );
    }
}

