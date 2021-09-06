<?php
/**
 * Class WC_Gateway_LINEPay file.
 *
 * @package WooCommerce\Gateways
 */

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * LINE Pay Gateway in Japanese
 *
 * Provides a LINE Pay Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class 		WC_Gateway_LINEPay
 * @extends		WC_Payment_Gateway
 * @version		1.1.1
 * @package		WooCommerce/Classes/Payment
 * @author 		Artisan Workshop
 */
class WC_Gateway_LINEPay extends WC_Payment_Gateway {

    /**
     * Framework.
     *
     * @var stdClass
     */
    public $jp4wc_framework;

    /**
     * LINE Pay function.
     *
     * @var
     */
    public $linepay_func;

    /**
     * Environment mode
     *
     * @var string
     */
    public $environment;

    /**
     * Order Prefix text
     *
     * @var string
     */
    public $order_prefix;

    /**
     * Payment Action mode
     *
     * @var string
     */
    public $payment_action;

    /**
     * Debug mode
     *
     * @var string
     */
    public $debug;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'linepay';
		$this->icon               = apply_filters('woocommerce_linepay_icon', JP4WC_URL_PATH . '/assets/images/linepay_logo.png');
		$this->has_fields         = false;
        $this->order_button_text = sprintf(__( 'Proceed to %s', 'woocommerce-for-japan' ), __('LINE Pay', 'woocommerce-for-japan' ));

		// Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();

        $this->method_title       = __( 'LINE Pay', 'woocommerce-for-japan' );
        $this->method_description = __( 'Pay using LINE Pay.', 'woocommerce-for-japan' );

        $this->supports = array(
            'products',
            'refunds',
        );
        //load LINEPay_func class
        include_once( 'includes/class-wc-linepay-func.php' );

        $this->jp4wc_framework = new Framework\JP4WC_Plugin();
        $this->linepay_func = new LINEPay_func;

        // Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Actions Hook
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'sales_complete' ) );
//        add_action( 'woocommerce_before_cart', array($this, 'cart_cancel' ) );
        add_filter( 'gettext', array( $this, 'change_hiragana_validation'), 20, 3 );
        add_filter( 'woocommerce_thankyou_order_id', array( $this, 'linepay_set_order_data'), 20, 1);
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
	public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable LINE Pay', 'woocommerce-for-japan' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'LINE Pay', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
                'default'     => __( '', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
            'order_button_text' => array(
                'title'       => __( 'Order Button Text', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-for-japan' ),
                'default'     => sprintf(__( 'Proceed to %s', 'woocommerce-for-japan' ), __('LINE Pay', 'woocommerce-for-japan' )),
            ),
            'environment_details'           => array(
                'title'       => __( 'API Environment setting', 'woocommerce-for-japan' ),
                'type'        => 'title',
                /* translators: %s: URL */
                'description' => sprintf( __( 'Please refer to <a href="%s" target="_blank">the LINE Pay setting flow page</a> for setting up the LINE Pay API. Examination is necessary for the use.', 'woocommerce-for-japan' ), 'https://docs.artws.info/knowledge-base/linepay-environment-setting-flow/' ),
            ),
            'environment' => array(
                'title'       => __( 'Environment', 'woocommerce-for-japan' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'This setting specifies whether you will process production transactions, or whether you will process simulated transactions using the LINEPay Test.', 'woocommerce-for-japan' ),
                'default'     => 'test',
                'desc_tip'    => true,
                'options'     => array(
                    'production'    => __( 'Production', 'woocommerce-for-japan' ),
                    'test' => __( 'Test merchant', 'woocommerce-for-japan' ),
                ),
            ),
            'api_channel_id' => array(
                'title'       => __( 'Channel Id', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'Channel Id', 'woocommerce-for-japan' )).' '.__( 'This field is numeric only.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_channel_secret_key' => array(
                'title'       => __( 'Channel Secret Key', 'woocommerce-for-japan' ),
                'type'        => 'password',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'Channel Secret Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_api_channel_id' => array(
                'title'       => __( 'Test Channel Id', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => sprintf(__( 'Please enter %s from LINE pay Admin site.', 'woocommerce-for-japan' ),__( 'Test Channel Id', 'woocommerce-for-japan' )).' '.__( 'This field is numeric only.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_api_channel_secret_key' => array(
                'title'       => __( 'Test Channel Secret Key', 'woocommerce-for-japan' ),
                'type'        => 'password',
                'description' => sprintf(__( 'Please enter %s from LINE pay Admin site.', 'woocommerce-for-japan' ),__( 'Test Channel Secret Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'shop_name' => array(
                'title'       => __( 'Shop Name', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'Please enter shop name for LINE pay.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'order_prefix' => array(
                'title'       => __( 'Order prefix', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'Please enter Order prefix for LINE pay.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'payment_action' => array(
                'title'       => __( 'Payment Action', 'woocommerce-for-japan' ),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce-for-japan' ),
                'default'     => 'sale',
                'desc_tip'    => true,
                'options'     => array(
                    'sale'          => __( 'Capture', 'woocommerce-for-japan' ),
                    'authorization' => __( 'Authorize', 'woocommerce-for-japan' )
                )
            ),
            'cart_checkout_enabled' => array(
                'title'       => __( 'Checkout on cart page', 'woocommerce-for-japan' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable LINEPay Checkout on the cart page', 'woocommerce-for-japan' ),
                'description' => __( 'This shows or hides the LINEPay Checkout button on the cart page.', 'woocommerce-for-japan' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            'contracted_name' => array(
                'title'       => __( 'Contracting company name', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'Please enter the name of the company or sole proprietor contracted with LINE Pay.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'debug' => array(
                'title'   => __( 'Debug Mode', 'woocommerce-for-japan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Debug Mode', 'woocommerce-for-japan' ),
                'default' => 'no',
                'description' => __( 'Save debug data using WooCommerce logging.', 'woocommerce-for-japan' ),
            ),
		);
    }

    /**
     * UI - Payment page Description fields for LINE Pay Payment.
     */
    function payment_fields() {
        // Description of payment method from settings
        ?>
        <p><?php echo $this->description; ?></p>
        <?php
        echo get_option( 'woocommerce_shipping_tax_class' );
    }

    /**
     * Process the payment and return the result
     *
     * @param int Order ID
     * @throws
     * @return mixed array | mixed
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        $requestUri = '/v3/payments/request';

        $post_data = $this->set_api_order( $order );

        //Set Redirect URLs
        $post_data['redirectUrls']['confirmUrl'] = $this->get_return_url( $order );
        $post_data['redirectUrls']['cancelUrl'] = wc_get_cart_url().'?linepay=cancel&order_id='.$order_id;

        //Set States
        if(version_compare( WC_VERSION, '3.6', '>=' )){
            $jp4wc_countries = new WC_Countries;
            $states = $jp4wc_countries->get_states();
        }else{
            global $states;
        }

        //Set Shipping information
        $shipping_total = preg_replace('/[^0-9]/', '', $order->get_shipping_total());
        $shipping_total = $this->jp4wc_framework->jp4wc_price_round_cal($shipping_total) + $this->jp4wc_framework->jp4wc_price_round_cal($order->get_shipping_tax());
        if( isset($shipping_total) === false ){
            $post_data['options']['shipping']['type'] = 'NO_SHIPPING';
        }elseif(!$order->get_shipping_postcode()){ //for Virtual Products
            $post_data['options']['shipping']['type'] = 'NO_SHIPPING';
        }else{
            $post_data['options']['shipping']['type'] = 'FIXED_ADDRESS';
            $post_data['options']['shipping']['feeInquiryType'] = 'FIXED';
            $post_data['options']['shipping']['feeAmount'] = $shipping_total;
            $post_data['options']['shipping']['address']['country'] = $order->get_shipping_country();
            $post_data['options']['shipping']['address']['postalCode'] = $order->get_shipping_postcode();
            $post_data['options']['shipping']['address']['state'] = $states[$order->get_shipping_country()][$order->get_shipping_state()];
            $post_data['options']['shipping']['address']['city'] = $order->get_shipping_city();
            $post_data['options']['shipping']['address']['detail'] = $order->get_shipping_address_1();
            if($order->get_shipping_address_2()){
	            $post_data['options']['shipping']['address']['optional'] = $order->get_shipping_address_2();
            }
            $post_data['options']['shipping']['address']['recipient']['firstName'] = $order->get_shipping_first_name();
            $post_data['options']['shipping']['address']['recipient']['lastName'] = $order->get_shipping_last_name();
            if(get_option( 'wc4jp-yomigana')){
                $post_data['options']['shipping']['address']['recipient']['firstNameOptional'] = get_post_meta( $order_id, '_shipping_yomigana_first_name', true );
                $post_data['options']['shipping']['address']['recipient']['lastNameOptional'] = get_post_meta( $order_id, '_shipping_yomigana_last_name', true );
            }
            $shipping_phone = get_post_meta( $order_id, '_shipping_phone', true );
            if(!empty($shipping_phone)){
                $post_data['options']['shipping']['address']['recipient']['phoneNo'] = $shipping_phone;
            }else{
                $post_data['options']['shipping']['address']['recipient']['phoneNo'] = $order->get_billing_phone();
            }
            $post_data['options']['shipping']['address']['recipient']['email'] = $order->get_billing_email();
        }

        $json_content = json_encode( $post_data );

        $response = $this->linepay_func->send_api_linepay( $requestUri, $json_content, $this->debug, 'POST', $order_id );
        $response_message = $this->response_request_message($response);
        $this->jp4wc_framework->jp4wc_debug_log($response_message, $this->debug, 'linepay-wc');
        if($response->returnCode == '0000'){
            $order->set_transaction_id($response->info->transactionId);
            $order->save();
            // Return thankyou redirect
            if($this->jp4wc_framework->isSmartPhone()){
                return array(
                    'result' 	=> 'success',
                    'redirect'	=> $response->info->paymentUrl->app
                );
            }else{
                return array(
                    'result' 	=> 'success',
                    'redirect'	=> $response->info->paymentUrl->web
                );
            }
        }else{
            return false;
        }
    }

    /**
     * Validate frontend fields.
     *
     * Validate payment fields on the frontend.
     *
     * @return bool
     */
    public function validate_fields(){
        if(get_option( 'wc4jp-yomigana')){
            $billing_yomigana_last_name = $this->get_post('billing_yomigana_last_name');
            $billing_yomigana_first_name = $this->get_post('billing_yomigana_first_name');
            $shipping_yomigana_last_name = $this->get_post('shipping_yomigana_last_name');
            $shipping_yomigana_first_name = $this->get_post('shipping_yomigana_first_name');
            if( $this->jp4wc_framework->isKatakana($billing_yomigana_last_name) === false ||
                $this->jp4wc_framework->isKatakana($billing_yomigana_first_name) === false
            ){
                wc_add_notice( __('All Yomigana must be katakana.', 'woocommerce-for-japan'), 'error' );
            }
            if($this->get_post('ship_to_different_address') == 1){
                if( $this->jp4wc_framework->isKatakana($shipping_yomigana_last_name) === false ||
                    $this->jp4wc_framework->isKatakana($shipping_yomigana_first_name) === false
                ){
                    wc_add_notice( __('All Yomigana must be katakana.', 'woocommerce-for-japan'), 'error' );
                }
            }
        }
        return true;
    }

    /**
     * Make array for LinePay Request API order data
     *
     * @param object $order
     * @return array $post_data
     */
    public function set_api_order( $order ){
        $post_data['amount'] = $order->get_total();
        $post_data['currency'] = get_woocommerce_currency();
        $post_data['orderId'] = $this->order_prefix.$order->get_id();
        //Set package data
        $packages['id'] = 1;
        $packages['name'] = $this->shop_name;
        $packages['amount'] = $order->get_total() - $order->get_shipping_total() - $order->get_shipping_tax();
        $packages['products'] = $this->linepay_func->array_products();
        $post_data['packages'] = array( $packages );

        //Set Payment capture
        if($this->payment_action == 'sale'){
            $payment_capture = 'true';
        }else{
            $payment_capture = 'false';
        }
        $post_data['options']['payment']['capture'] = $payment_capture;

        //Set Display language
        $allowed_langs =array('ja','th','zh_TW','zh_CN');
        $wp_current_lang = get_locale();
        if(strpos($wp_current_lang, 'en') !== false){
            $current_lang = 'en';
        }elseif(strpos($wp_current_lang, 'ko') !== false){
            $current_lang = 'ko';
        }elseif(in_array($wp_current_lang, $allowed_langs)){
            $current_lang = $wp_current_lang;
        }else{
            $current_lang = 'en';
        }
        $post_data['options']['display']['locale'] = $current_lang;
        return $post_data;
    }

	/**
	 * Make array for LinePay Request API order data
	 *
	 * @param object $order
	 * @return array $post_data
	 */
	public function set_api_cart_order( $order ){
		$post_data['amount'] = $this->linepay_func->get_cart_subtotal();
		$post_data['currency'] = get_woocommerce_currency();
		$post_data['orderId'] = $this->order_prefix.$order->get_id();
		//Set package data
		$packages['id'] = 1;
		$packages['name'] = $this->shop_name;
		$packages['amount'] = $this->linepay_func->get_cart_subtotal();
		$packages['products'] = $this->linepay_func->array_products();
		$post_data['packages'] = array( $packages );

		//Set Payment capture
		if($this->payment_action == 'sale'){
			$payment_capture = 'true';
		}else{
			$payment_capture = 'false';
		}
		$post_data['options']['payment']['capture'] = $payment_capture;

		//Set Display language
		$allowed_langs =array('ja','th','zh_TW','zh_CN');
		$wp_current_lang = get_locale();
		if(strpos($wp_current_lang, 'en') !== false){
			$current_lang = 'en';
		}elseif(strpos($wp_current_lang, 'ko') !== false){
			$current_lang = 'ko';
		}elseif(in_array($wp_current_lang, $allowed_langs)){
			$current_lang = $wp_current_lang;
		}else{
			$current_lang = 'en';
		}
		$post_data['options']['display']['locale'] = $current_lang;
		return $post_data;
	}

	/**
     * Make message for LinePay Request API response
     *
     * @param object $response
     * @return string $response_message
     */
    public function response_request_message($response){
        $response_array['returnCode'] = $response->returnCode;
        $response_array['returnMessage'] = $response->returnMessage;
        if(isset($response->info)){
            $response_array['info.transactionId'] = $response->info->transactionId;
            $response_array['info.paymentAccessToken'] = $response->info->paymentAccessToken;
            $response_array['info.paymentUrl.app'] = $response->info->paymentUrl->app;
            $response_array['info.paymentUrl.web'] = $response->info->paymentUrl->web;
        }
        $response_message = $this->jp4wc_framework->jp4wc_array_to_message( $response_array );
        return $response_message;
    }

    /**
     * Make message for LinePay Confirm API response
     *
     * @param object $response
     * @return string $response_message
     */
    public function response_confirm_message($response){
        $response_array['returnCode'] = $response->returnCode;
        $response_array['returnMessage'] = $response->returnMessage;
        if(isset($response->info)){
            $response_array['info.transactionId'] = $response->info->transactionId;
            $response_array['info.orderId'] = $response->info->orderId;
        }
        $response_message = $this->jp4wc_framework->jp4wc_array_to_message( $response_array );
        return $response_message;
    }

	/**
	 *
	 */
    public function linepay_set_order_data( $order_id ){
	    $order = wc_get_order($order_id);

	    if(isset($_GET['orderId']) && $order->get_payment_method() == $this->id && $_GET['orderId'] == $this->order_prefix.$order_id && $order->get_status() != 'processing') {
		    $order->calculate_taxes();
		    $order->calculate_totals();
	        $post_data = array();

		    if(isset( $_GET['shippingFeeAmount'], $_GET['transactionId'] )){
			    $order_amount = $this->jp4wc_framework->jp4wc_price_round_cal( $order->get_total() ) + $this->jp4wc_framework->jp4wc_price_round_cal( $_GET['shippingFeeAmount'] );
		    }else{
			    $order_amount = $order->get_total();
		    }
		    $requestUri = '/v3/payments/' . sanitize_text_field( $_GET['transactionId'] ) . '/confirm';
		    $post_data['amount'] = $order_amount;
		    $post_data['currency'] = get_woocommerce_currency();
		    $json_content = json_encode( $post_data );

		    $response = $this->linepay_func->send_api_linepay( $requestUri, $json_content, $this->debug, 'POST', $order_id );
		    $response_message = $this->response_confirm_message($response);
		    $this->jp4wc_framework->jp4wc_debug_log($response_message, $this->debug, 'linepay-wc');
		    if($response->returnCode == '0000'){
			    if(isset($response->info->authorizationExpireDate)){
				    $order->set_meta_data( array( '_linepay_authorization_expire_date' => $response->info->authorizationExpireDate ) );
				    $order->save();
				    $order->add_order_note( sprintf( __('Authorization Expire Date is %s.', 'woocommerce-for-japan' ),$response->info->authorizationExpireDate ) );
			    }
			    // Reduce stock levels
			    wc_reduce_stock_levels( $order_id );
			    // Set order status of processing
			    $order->payment_complete($_GET['transactionId']);
			    $order->add_order_note( __( 'Confirm API processing was successful.', 'woocommerce-for-japan' ) );
			    if(isset( $_GET['shippingFeeAmount'], $_GET['transactionId'] )) {
				    $this->set_shipping_address( $order );
				    $this->set_shipping_fee( $order );
				    $order->set_total( $order_amount );
				    $order->calculate_totals( false );
				    $order->save();
			    }

                if($order->get_total() != $order_amount){
                    $order->add_order_note( sprintf( __('The payment amount differs between the system and LINE PAY. The system is %1$s and LINE PAY is %2$s.', 'woocommerce-for-japan' ), $order->get_total(), $order_amount));
                }
		    }else{
			    $order->add_order_note( __( 'Confirm API processing failed.', 'woocommerce-for-japan' ).$response->returnCode.' : '.$response->returnMessage);
		    }
	    }
        return $order_id;
    }

	/**
	 * Set shipping address from LINE Pay data
     *
     * @param object WC_Order
	 */
	public function set_shipping_address( $order ){
		if( isset( $_GET['transactionId']) && isset( $_GET['shippingMethodId'])){
			$order_get['orderId'] = (string)$_GET['orderId'];
			$order_get['fields'] = 'ORDER';
			$order_id = $order->get_id();
			$linepay_order = $this->linepay_func->send_api_linepay('/v3/payments', http_build_query($order_get), $this->debug, 'GET', $order_id);
			// Set log data get payment
			$message = 'Check order data at LinePay. '.$linepay_order->returnCode.':'.$linepay_order->returnMessage;
			$this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'linepay-wc');

			if($linepay_order->returnCode == '0000') {
			    $jp4wc_countries = new WC_Countries;
			    $states = $jp4wc_countries->get_states();
				$country_code = $linepay_order->info[0]->shipping->address->country;
				if ( $country_code == 'JP' ) {
					$post_code = substr( $linepay_order->info[0]->shipping->address->postalCode, 0, 3 ) . '-' . substr( $linepay_order->info[0]->shipping->address->postalCode, - 4 );
				}else{
					$post_code = $linepay_order->info[0]->shipping->address->postalCode;
				}
				$state_code = array_search( $linepay_order->info[0]->shipping->address->state, $states[ $country_code ] );
				//Set address to Order
                // Billing address
                if(is_user_logged_in()) {
                    $user = wp_get_current_user();
                    $user_id = $user->ID;
	                $order->set_billing_first_name(get_user_meta($user_id, 'billing_first_name', true));
	                $order->set_billing_last_name(get_user_meta($user_id, 'billing_last_name', true));
	                $order->set_billing_phone(get_user_meta($user_id, 'billing_phone', true));
	                $order->set_billing_email(get_user_meta($user_id, 'billing_email', true));
	                $order->set_billing_country(get_user_meta($user_id, 'billing_country', true));
	                $order->set_billing_state(get_user_meta($user_id, 'billing_state', true));
	                $order->set_billing_postcode(get_user_meta($user_id, 'billing_postcode', true));
	                $order->set_billing_city(get_user_meta($user_id, 'billing_city', true));
	                $order->set_billing_address_1(get_user_meta($user_id, 'billing_address_1', true));
	                $order->set_billing_address_2(get_user_meta($user_id, 'billing_address_2', true));
                }else{
	                $order->set_billing_first_name($linepay_order->info[0]->shipping->address->recipient->firstName);
	                $order->set_billing_last_name($linepay_order->info[0]->shipping->address->recipient->lastName);
	                $order->set_billing_phone($linepay_order->info[0]->shipping->address->recipient->phoneNo);
	                $order->set_billing_email($linepay_order->info[0]->shipping->address->recipient->email);
	                $order->set_billing_country($country_code);
	                $order->set_billing_state($state_code);
	                $order->set_billing_postcode($post_code);
	                $order->set_billing_city($linepay_order->info[0]->shipping->address->city);
	                $order->set_billing_address_1($linepay_order->info[0]->shipping->address->detail);
	                if(isset($linepay_order->info[0]->shipping->address->optional)){
		                $order->set_billing_address_2($linepay_order->info[0]->shipping->address->optional);
	                }
                }
				// Shipping address
				$order->set_shipping_first_name($linepay_order->info[0]->shipping->address->recipient->firstName);
				$order->set_shipping_last_name($linepay_order->info[0]->shipping->address->recipient->lastName);
				$order->update_meta_data('_shipping_phone',$linepay_order->info[0]->shipping->address->recipient->phoneNo);
				$order->update_meta_data('_shipping_email',$linepay_order->info[0]->shipping->address->recipient->email);
				$order->set_shipping_country($country_code);
				$order->set_shipping_state($state_code);
				$order->set_shipping_postcode($post_code);
				$order->set_shipping_city($linepay_order->info[0]->shipping->address->city);
				$order->set_shipping_address_1($linepay_order->info[0]->shipping->address->detail);
				if(isset($linepay_order->info[0]->shipping->address->optional)){
					$order->set_shipping_address_2($linepay_order->info[0]->shipping->address->optional);
				}
				$order->save();
			}
		}
	}

	/**
	 * Set shipping Fee
	 *
	 * @param object WC_Order
	 */
	public function set_shipping_fee( $order ){
		$package['destination']['country'] = $order->get_shipping_country();
		$package['destination']['state'] = $order->get_shipping_state();
		$package['destination']['postcode'] = $order->get_shipping_postcode();
		$package['destination']['city'] = $order->get_shipping_city();
		$package['destination']['tax_class'] = $order->get_shipping_city();

		$shipping_zone = WC_Shipping_Zones::get_zone_matching_package( $package );
		$shipping_methods = $shipping_zone->get_shipping_methods( true );
		$shipping_tax_flag = false;
		$shipping_total = 0;
		$shipping_tax = 0;
		foreach($shipping_methods as $shipping_method) {
			if ( $shipping_method->id == 'free_shipping' ) {
				if($shipping_method->instance_settings['requires'] == 'min_amount'){
					if((float)$shipping_method->instance_settings['min_amount'] <= $order->get_total()){
						$rate = new WC_Shipping_Rate( 'free_shipping', __( 'Free Shipping', 'woocommerce-for-japan' ), 0, array(), 'free_shipping' );
						$order->add_shipping($rate);
						return;
					}
				}
            }
			if ( $shipping_method->id == 'flat_rate' ) {
				$shipping_total = $this->jp4wc_framework->jp4wc_price_round_cal( (float) $shipping_method->instance_settings['cost'] );
				if($shipping_method->instance_settings['tax_status'] == 'taxable'){
				    $shipping_tax_flag = true;
					$tax_rates = WC_Tax::get_rates();
					foreach ($tax_rates as $tax_rate){
						if($tax_rate['shipping'] == 'yes'){
						    $shipping_tax = $this->jp4wc_framework->jp4wc_price_round_cal( (float) $shipping_total*$tax_rate['rate']/100 );
						}
					}
				}
			}
		}
		$rate = new WC_Shipping_Rate( $_GET['shippingMethodId'], __( 'Shipping Fee', 'woocommerce-for-japan' ), $shipping_total ?? 0, array(), $_GET['shippingMethodId'] );
		$order->add_shipping($rate);
		$order->set_shipping_tax($shipping_tax);
		if($shipping_tax_flag){
			$order->calculate_taxes($package);
		}
    }

		/**
     * Process a sales payment after complete order to ship.
     *
     * @param  int Order ID
     */
    public function sales_complete( $order_id ) {
        $order = wc_get_order( $order_id );
        if( $order->get_payment_method() == $this->id and $order->get_status() == 'completed'){
            $order_get['orderId'] = $this->order_prefix.$order_id;
            $order_get['fields'] = 'TRANSACTION';
            $linepay_order = $this->linepay_func->send_api_linepay('/v3/payments', http_build_query($order_get), $this->debug, 'GET', $order_id);
            if($linepay_order->info[0]->payStatus == 'AUTHORIZATION' && $order->get_total() == $linepay_order->info[0]->payInfo[0]->amount){
                $transaction_id = $order->get_transaction_id();
                $requestUri = '/v3/payments/authorizations/'.$transaction_id.'/capture';
                $post_data['amount'] = $order->get_total();
                $post_data['currency'] = get_woocommerce_currency();
                $json_content = json_encode( $post_data );

                $response = $this->linepay_func->send_api_linepay( $requestUri, $json_content, $this->debug, 'POST', $order_id );
                if($response->returnCode == '0000'){
                    $info = $response->info->payInfo;
                    $payInfo = $info[0];
                    if(isset($payInfo->method)){
                        $order->set_meta_data( array( '_linepay_payinfo_method' => $payInfo->method ) );
                        $order->set_meta_data( array('_linepay_authorization_expire_date'=>$response->info->authorizationExpireDate) );
                        $order->save();
                    }
                    $order->add_order_note( __( 'Capture API processing was successful.', 'woocommerce-for-japan' ) );
                }else{
                    $order->add_order_note(__( 'Capture API processing failed.', 'woocommerce-for-japan' ).$response->returnCode.' : '.$response->returnMessage);
                }
            }elseif($linepay_order->info[0]->payStatus != 'CAPTURE'){
                $order->add_order_note(__( 'Capture API processing failed.', 'woocommerce-for-japan' ).__( ' Not Authorize or not match amount.', 'woocommerce-for-japan' ));
            }
        }elseif( $order->get_payment_method() == $this->id ){
            $order->add_order_note(__( 'Capture API processing failed.', 'woocommerce-for-japan' ).$order->get_payment_method().$order->get_status());
        }
    }

    /**
     * Cancelled at Cart page by LINE Pay
     */
    public function cart_cancel(){
        if(isset($_GET['linepay']) && isset($_GET['order_id']) && $_GET['linepay'] == 'cancel'){
            $order = wc_get_order($_GET['order_id']);
            $order->update_status('cancelled', __( 'This order is cancelled by LINE Pay.', 'woocommerce-for-japan' ));
            wp_trash_post($_GET['order_id']);
        }
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  bool True or false based on success, or a WP_Error object
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order($order_id);
        if( $order->get_payment_method() == $this->id ){
	        $transaction_id = $order->get_transaction_id();
	        $refundUri      = '/v3/payments/' . $transaction_id . '/refund';
	        $voidUri        = '/v3/payments/authorizations/' . $transaction_id . '/void';
	        $auth           = $order->get_meta( '_linepay_authorization_expire_date', true );

	        $order_get['orderId'] = $this->order_prefix . $order_id;
	        $order_get['fields']  = 'TRANSACTION';
	        $linepay_order        = $this->linepay_func->send_api_linepay( '/v3/payments', http_build_query( $order_get ), $this->debug, 'GET', $order_id );
	        $last_total_amount    = 0;
	        foreach ( $linepay_order->info as $info ) {
		        if ( $info === reset( $linepay_order->info ) ) {
			        $last_total_amount += $info->payInfo[0]->amount;
		        }
	        }

	        if ( isset( $auth ) && $order->get_status() === 'processing' && $order->get_total() == $amount && $linepay_order->info[0]->payStatus == 'AUTHORIZATION' ) {
		        $void_response = $this->linepay_func->send_api_linepay( $voidUri, '', $this->debug, 'POST', $order_id );
		        if ( $void_response->returnCode == '0000' ) {
			        $order->add_order_note( __( 'The Void was successful.', 'woocommerce-for-japan' ) );

			        return true;
		        } else {
			        $order->add_order_note( __( 'The Void was failed.', 'woocommerce-for-japan' ) . $void_response->returnCode . ' : ' . $void_response->returnMessage );

			        return false;
		        }
	        } elseif ( isset( $auth ) && $order->get_status() === 'completed' && $order->get_total() >= $amount ) {
		        $post_data['refundAmount'] = $amount;
		        $json_content              = json_encode( $post_data );

		        $response = $this->linepay_func->send_api_linepay( $refundUri, $json_content, $this->debug, 'POST', $order_id );
		        if ( $response->returnCode == '0000' ) {
			        $order->add_order_note( __( 'The refund was successful.', 'woocommerce-for-japan' ) );

			        return true;
		        } else {
			        $order->add_order_note( __( 'The refund was failed.', 'woocommerce-for-japan' ) . $response->returnCode . ' : ' . $response->returnMessage );

			        return false;
		        }
	        } else {
		        $order->add_order_note( __( 'The refund was failed. not match some conditions', 'woocommerce-for-japan' ) );
		        $this->jp4wc_framework->jp4wc_debug_log( $auth . ' : ' . $order->get_status() . ' : ' . $amount . ' : ' . $linepay_order->info[0]->payStatus, $this->debug, 'linepay-wc' );

		        return false;
	        }
        }
	    return true;
    }

    /**
     * Get post data if set
     *
     * @param  string $name
     * @return  string
     */
    private function get_post( $name ) {
        if ( isset( $_POST[ $name ] ) ) {
            return sanitize_text_field( $_POST[ $name ] );
        }
        return null;
    }

    /**
     * Add the gateway to woocommerce
     *
     * @param array $translated_text
     * @param string $untranslated_text
     * @param string $domain
     * @return array $translated_text
     */
    public function change_hiragana_validation($translated_text, $untranslated_text, $domain){
        switch ($untranslated_text) {
            case 'First Name (Yomigana)'://Original text of the word you want to change
                $translated_text = '名（ヨミガナ）';//Text after change break;
            case 'Last Name (Yomigana)'://Original text of the word you want to change
                $translated_text = '姓（ヨミガナ）';//Text after change
                break;
        }
        return $translated_text;
    }
}

if( function_exists( 'add_wc4jp_linepay_gateway' ) === false ){
    /**
     * Add the gateway to woocommerce
     *
     * @param array $methods
     * @return array $methods
     */
    function add_wc4jp_linepay_gateway( $methods ) {
        $methods[] = 'WC_Gateway_LINEPay';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_linepay_gateway' );
}

if( function_exists( 'wc4jp_linepay_available_gateways' ) === false ) {
    /**
     * The available gateway to woocommerce only Japanese currency
     */
    function wc4jp_linepay_available_gateways($methods)
    {
        $currency = get_woocommerce_currency();
        if ($currency != 'JPY') {
            unset($methods['linepay']);
        }
        return $methods;
    }

    add_filter('woocommerce_available_payment_gateways', 'wc4jp_linepay_available_gateways');
}