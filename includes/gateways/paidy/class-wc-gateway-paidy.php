<?php
/**
 * Class WC_Gateway_Paidy file.
 *
 * @package WooCommerce\Gateways
 */

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Paidy Payment Gateway in Japanese
 *
 * Provides a Paidy Payment Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class 		WC_Gateway_Paidy
 * @extends		WC_Payment_Gateway
 * @version		1.4.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Artisan Workshop
 */
class WC_Gateway_Paidy extends WC_Payment_Gateway {

    /**
     * Framework.
     *
     * @var stdClass
     */
    public $jp4wc_framework;

    /**
     * Settings parameter
     *
     * @var string
     */
    public $paidy_description;
    public $order_button_text;
    public $environment;
    public $api_public_key;
    public $api_secret_key;
    public $test_api_public_key;
    public $test_api_secret_key;
    public $store_name;
    public $logo_image_url;
    public $debug;
    public $webhook;
    public $notice_email;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		$this->id                 = 'paidy';
//		$this->icon               = apply_filters('woocommerce_paidy_icon', 'assets/images/paidy_logo_100_2023.png');
		$this->has_fields         = false;
        $this->order_button_text = sprintf(__( 'Proceed to %s', 'woocommerce-for-japan' ), __('Paidy', 'woocommerce-for-japan' ));

		// Create plugin fields and settings
		$this->init_form_fields();
		$this->init_settings();

        $this->method_title       = __( 'Paidy Payment', 'woocommerce-for-japan' );
        $this->method_description = __( '"Paidy next month payment" reduces the opportunity loss due to the payment method and contributes to sales increase.', 'woocommerce-for-japan' );

        $this->supports = array(
            'products',
            'refunds',
        );

        $this->jp4wc_framework = new Framework\JP4WC_Plugin();

		// Get setting values
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

        // Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );

		// Actions Hook
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'paidy_make_order') );

        add_action( 'wp_enqueue_scripts', array( $this, 'paidy_token_scripts_method' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        add_action( 'woocommerce_before_checkout_form', array( $this, 'checkout_reject_to_cancel' ));
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_completed' ) );

        add_action( 'woocommerce_order_status_completed', array( $this, 'jp4wc_order_paidy_status_completed' ) );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'jp4wc_order_paidy_status_cancelled' ) );
	}

	/**
     * Initialise Gateway Settings Form Fields
     */
	public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Paidy', 'woocommerce-for-japan' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Paidy Payment', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
                'default'     => __( 'No matter how many times you shop a month, you pay once in the following month. <br /> The following payment methods are available.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
            'paidy_description' => array(
                'title'       => __( 'Paidy description', 'woocommerce-for-japan' ),
                'type'        => 'textarea',
                'custom_attributes' => array( 'rows' => 6 ),
                'description' => __( 'Payment method description for paidy explanation that the customer will see on your checkout.', 'woocommerce-for-japan' ),
                'default'     => $this->paidy_explanation(),
                'desc_tip'    => true,
            ),
            'order_button_text' => array(
                'title'       => __( 'Order Button Text', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-for-japan' ),
                'default'     => sprintf(__( 'Proceed to %s', 'woocommerce-for-japan' ), __('Paidy', 'woocommerce-for-japan' )),
            ),
            'environment' => array(
                'title'       => __( 'Environment', 'woocommerce-for-japan' ),
                'type'        => 'select',
                'description' => __( 'This setting specifies whether you will process live transactions, or whether you will process simulated transactions using the Paidy Sandbox.', 'woocommerce-for-japan' ),
                'default'     => 'live',
                'desc_tip'    => true,
                'options'     => array(
                    'live'    => __( 'Live', 'woocommerce-for-japan' ),
                    'sandbox' => __( 'Sandbox', 'woocommerce-for-japan' ),
                ),
            ),
            'api_public_key' => array(
                'title'       => __( 'API Public Key', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'API Public Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_secret_key' => array(
                'title'       => __( 'API Secret Key', 'woocommerce-for-japan' ),
                'type'        => 'password',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'API Secret Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_api_public_key' => array(
                'title'       => __( 'Test API Public Key', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'Test API Public Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_api_secret_key' => array(
                'title'       => __( 'Test API Secret Key', 'woocommerce-for-japan' ),
                'type'        => 'password',
                'description' => sprintf(__( 'Please enter %s from Paidy Admin site.', 'woocommerce-for-japan' ),__( 'Test API Secret Key', 'woocommerce-for-japan' )),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'store_name'       => array(
                'title'       => __( 'Store Name', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'This controls the store name which the user sees during paidy checkout.', 'woocommerce-for-japan' ),
                'default'     => get_bloginfo( 'name' )
            ),
            'logo_image_url' => array(
                'title'       => __( 'Logo Image (168×168 recommend)', 'woocommerce-for-japan' ),
                'type'        => 'image',
                'description' => __( 'URL of a custom logo that can be displayed in the checkout application header. If no value is specified, the Paidy logo will be displayed.', 'woocommerce-for-japan' ),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => __( 'Optional', 'woocommerce-for-japan' ),
            ),
            'debug' => array(
                'title'   => __( 'Debug Mode', 'woocommerce-for-japan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Debug Mode', 'woocommerce-for-japan' ),
                'default' => 'no',
                'description' => __( 'Save debug data using WooCommerce logging.', 'woocommerce-for-japan' ),
            ),
            'webhook' => array(
                'title'   => __( 'About Webhook', 'woocommerce-for-japan' ),
                'type'    => 'title',
                'description' => __( 'The webhooks set in the Paidy management screen are as follows. <br />', 'woocommerce-for-japan' ) . '<strong>' . site_url() . '/wp-json/paidy/v1/order/' . '</strong>',
            ),
            'notice_email'       => array(
                'title'       => __( 'Notice e-mail', 'woocommerce-for-japan' ),
                'type'        => 'text',
                'description' => __( 'Set an e-mail address to send an e-mail when an error occurs during connection with paidy.', 'woocommerce-for-japan' ),
                'default'     => get_option( 'admin_email' )
            ),
		);
    }

    /**
     * UI - Payment page Description fields for Paidy Payment.
     */
    function payment_fields() {
        // Description of payment method from settings
        ?>
        <br />
        <a href="https://paidy.com/consumer" target="_blank" class="jp4wc-paidy-icon">
            <img src="<?php echo JP4WC_URL_PATH;?>assets/images/paidy_checkout_2023_320x100.png" alt="Paidy 翌月まとめてお支払い" style="max-height: none; float: none;">
        </a>
        <br />
        <p class="jp4wc-paidy-description"><?php echo $this->description; ?></p>
        <br />
        <?php
        if(empty($this->paidy_description)){
            $paidy_explanation = $this->paidy_explanation();
        }else{
            $paidy_explanation = $this->paidy_description;
        }
        $allowed_html = array(
            'a' => array( 'href' => array (), 'target' => array(), ),
            'br' => array(),
            'strong' => array(),
            'b' => array(),
            'div' => array(),
            'ul' => array(),
            'li' => array(),
        );
        echo wp_kses( $paidy_explanation, $allowed_html );
    }

    /**
     *
     */
    function paidy_explanation(){
        $explain_html = '
        <div class="jp4wc-paidy-explanation">
        <ul>
            <li style="list-style: disc !important;">クレジットカード、事前登録不要。</li>
            <li style="list-style: disc !important;">メールアドレスと携帯番号だけで、今すぐお買い物。</li>
            <li style="list-style: disc !important;">1か月に何度お買い物しても、お支払いは翌月まとめて1回でOK。</li>
            <li style="list-style: disc !important;">お支払いは翌月10日までに、コンビニ払い・銀行振込・口座振替で。</li>
        </ul>
        さらにペイディアプリから本人確認をすると、分割手数料無料*の３回あと払い**や、使い過ぎを防止する予算設定など、便利な機能をご利用いただけます。<br />
*銀行振込・口座振替のみ無料<br />
**1回のご利用金額が3,000円以上の場合のみ利用可能<br />
        Paidyについて詳しくは<a href="https://paidy.com/payments/" target="_blank">こちら</a>。
        </div>
        ';
        return apply_filters( 'jp4wc_paidy_explanation', $explain_html );
    }
    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $order->get_checkout_payment_url(true)
		);
    }

    /**
     * Make Paidy JavaScript for payment process
     *
     * @param string $order_id
     */
    public function paidy_make_order( $order_id ){
        //Set Order
        $order = wc_get_order( $order_id );
        //Set public key by environment.
        if( $this->environment == 'live' ){
            $api_public_key = $this->api_public_key;
        }else{
            $api_public_key = $this->test_api_public_key;
        }
        //Set logo image url
        if(isset($this->logo_image_url)){
            $logo_image_url = wp_get_attachment_url($this->logo_image_url);
        }else{
            $logo_image_url = 'http://www.paidy.com/images/logo.png';
        }
        $date = new DateTime();
        $paidy_order_ref = $order_id;
        //Set user id
        if(is_user_logged_in()){
            $user_id = get_current_user_id();
        }else{
            $user_id = 'guest-paidy'.$paidy_order_ref;
        }

        $jp4wc_countries = new WC_Countries;
        $states = $jp4wc_countries->get_states();

        //Get products and coupons information from order
        $order_items = apply_filters( 'jp4wc_paidy_order_items', $order->get_items( 'line_item' ) );
        $items_count = 0;
        $cart_total = 0;
        $fees = $order->get_fees();
        $items = '';
        $paidy_amount = 0;
        foreach( $order_items as $key => $item){
            if( $item->get_product_id() ) {
                $item_name = str_replace( '"','\"',$item->get_name() );
                $unit_price = round($item->get_subtotal() / $item->get_quantity(), 0);
                $items .= '{
                    "id":"' . $item->get_product_id() . '",
                    "quantity":' . $item->get_quantity() . ',
                    "title":"' . $item_name . '",
                    "unit_price":' . $unit_price;
                $paidy_amount += $item->get_quantity()*$unit_price;
            }
            if ($item === end($order_items) and (!isset($fees))) {
                $items .= '}
';
            }else{
                $items .= '},
                    ';
            }
		}
        $order_coupons = apply_filters( 'jp4wc_paidy_order_coupons', $order->get_items( 'coupon' ) );
        foreach( $order_coupons as $key => $coupon){
			if( $coupon->get_discount() ){
                $items .= '{
                    "id":"'.$coupon->get_code().'",
                    "quantity":1,
                    "title":"'.$coupon->get_name().'",
                    "unit_price":-'.$coupon->get_discount();
                $paidy_amount -= $coupon->get_discount();
            }
            if ($coupon === end($order_items) and (!isset($fees))) {
                $items .= '}
';
            }else{
                $items .= '},
                    ';
            }
            $items_count += $coupon->get_quantity();
            $cart_total += $coupon->get_subtotal();
        }

		if(isset( $fees )){
            $i = 1;
            foreach ( $fees as $fee ){
                $items .= '{
                    "id":"fee'.$i.'",
                    "quantity":1,
                    "title":"'.esc_html($fee->get_name()).'",
                    "unit_price":'.esc_html($fee->get_amount());
                $paidy_amount += intval($fee->get_amount());
                if ($fee === end($fees)) {
                    $items .= '}
';
                }else{
                    $items .= '},
                    ';
                }
                $i++;
            }
        }

	    //Check the order only for virtual products
        $not_virtual = false;

        foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            // Check if there are non-virtual products
            if ( ! $cart_item['data']->is_virtual() ) $not_virtual = true;
        }
	    //Set shipping address
        if($not_virtual){
	        if($order->get_shipping_postcode()){
		        $shipping_address['line1'] = $order->get_shipping_address_2();
		        $shipping_address['line2'] = $order->get_shipping_address_1();
		        $shipping_address['city'] = $order->get_shipping_city();
		        $shipping_address['state'] = $states['JP'][$order->get_shipping_state()];
		        $shipping_address['zip'] = $order->get_shipping_postcode();
	        }else{
		        $shipping_address['line1'] = $order->get_billing_address_2();
		        $shipping_address['line2'] = $order->get_billing_address_1();
		        $shipping_address['city'] = $order->get_billing_city();
		        $shipping_address['state'] = $states['JP'][$order->get_billing_state()];
		        $shipping_address['zip'] = $order->get_billing_postcode();
	        }
        }

	    // Get the latest order
        $args = array(
            'customer_id' => $user_id,
            'status' => 'completed',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        $orders = wc_get_orders($args);
        $total_order_amount = 0;
        $order_count = 0;
        foreach($orders as $each_order){
            if( $each_order->get_payment_method() != $this->id ){
                $selected_orders[] = $each_order;
                $total_order_amount += $each_order->get_total();
                $order_count += 1;
            }
        }
        if(isset($selected_orders[1])) {
            foreach ($selected_orders as $each_order) {
                if ($each_order === end($selected_orders)) {
                    $latest_order = $each_order;
                }
            }
        }elseif(isset($selected_orders)){
            $latest_order = $selected_orders[0];
        }else{
            $latest_order = null;
        }
        if(isset($latest_order)){
            $last_order_amount = $latest_order->get_total();
            $day1 = strtotime($latest_order->get_date_created());
            $day2 = strtotime(date_i18n('Y-m-d H:i:s'));
            $diff_day = floor(($day2 - $day1) / (60 * 60 * 24));
            if($diff_day <=0 ){
                $diff_day = 0;
            }
        }else{
            $last_order_amount = 0;
            $diff_day = 0;
        }
        $order_amount = $order->get_total();
        $tax = $order_amount - $paidy_amount - $order->get_shipping_total();

        if( $this->enabled =='yes' and isset($api_public_key) and $api_public_key != '' and $order->get_status() == 'pending'):
            ?>
            <script type="text/javascript">
                // Paidy Payment apply
                jQuery(window).on('load', function(){
                    paidyPay();
                })
                var config = {
                    "api_key": "<?php echo $api_public_key;?>",
                    "logo_url": "<?php echo $logo_image_url;?>",
                    "closed": function(callbackData) {
                        /*
                        Data returned in the callback:
                        callbackData.id,
                        callbackData.amount,
                        callbackData.currency,
                        callbackData.created_at,
                        callbackData.status
                        */
                        if(callbackData.status === "rejected"){
                            window.location.href = "<?php echo wc_get_checkout_url().'?status='; ?>" + callbackData.status + "&order_id=<?php echo $order_id;?>";
                        }else if(callbackData.status === "authorized"){
                            window.location.href = "<?php echo $this->get_return_url( $order ).'&transaction_id='; ?>" + callbackData.id;
                        }else{
                            window.location.href = "<?php echo wc_get_checkout_url().'?status='; ?>" + callbackData.status + "&order_id=<?php echo $order_id;?>";
                        }
                    }
                };

                var paidyHandler = Paidy.configure(config);
                function paidyPay() {
                    var payload = {
                        "amount": <?php echo $order_amount;?>,
                        "currency": "JPY",
                        "store_name": "<?php echo wc_clean($this->store_name);?>",
                        "buyer": {
                            "email": "<?php echo $order->get_billing_email(); ?>",
                            "name1": "<?php echo $order->get_billing_last_name().' '.$order->get_billing_first_name();?>",
<?php $billing_yomigana_last_name = $order->get_meta('_billing_yomigana_last_name');
                            if(isset($billing_yomigana_last_name)):?>
                            "name2": "<?php echo $order->get_meta('_billing_yomigana_last_name').' '.$order->get_meta('_billing_yomigana_first_name');?>",
<?php endif; ?>
                            "phone": "<?php echo $order->get_billing_phone(); ?>"
                        },
                        "buyer_data": {
                            "user_id": "<?php echo $user_id; ?>",
                            "order_count": <?php echo $order_count; ?>,
                            "ltv": <?php echo $total_order_amount; ?>,
                            "last_order_amount": <?php echo $last_order_amount; ?>,
                            "last_order_at": <?php echo $diff_day?>
                        },
                        "order": {
                            "items": [
                                <?php echo $items;?>

						],
                            "order_ref": "<?php echo $paidy_order_ref; ?>",
                            <?php if($not_virtual)echo '"shipping": '.$order->get_shipping_total().','; ?>
                            "tax": <?php echo $tax;?>
                        },
                        <?php if($not_virtual){ ?>
                        "shipping_address": {
                            "line1": "<?php echo $shipping_address['line1'];?>",
                            "line2": "<?php echo $shipping_address['line2'];?>",
                            "city": "<?php echo $shipping_address['city'];?>",
                            "state": "<?php echo $shipping_address['state'];?>",
                            "zip": "<?php echo $shipping_address['zip'];?>"
                        },
                        <?php } ?>
                        "description": "<?php echo wc_clean($this->store_name);?>",
                        "metadata" : {"Platform" : "WooCommerce"}
                    };
                    paidyHandler.launch(payload);
                }
            </script>
        <?php elseif($this->enabled =='yes' and isset($api_public_key) and $api_public_key == ''): ?>
            <h2><?php echo __('This order has already been settled.', 'woocommerce-for-japan'); ?></h2>
        <?php else: ?>
            <h2><?php echo __('API Public key is not set. Please set an API public key in the admin page.', 'woocommerce-for-japan'); ?></h2>
        <?php endif;
    }

    /**
     * Set API key
     *
     * @return string $api_secret_key
     */
    private function set_api_secret_key() {
        if( $this->environment == 'live' ){
            $api_secret_key = $this->api_secret_key;
        }else{
            $api_secret_key = $this->test_api_secret_key;
        }
        return $api_secret_key;
    }

    /**
     * Load Paidy Token javascript
     */
    public function paidy_token_scripts_method() {
        if( is_checkout_pay_page() ){
            // Image upload.
            wp_enqueue_media();

	        wp_enqueue_script(
		        'paidy-redirect',
		        JP4WC_URL_PATH.'assets/js/jp4wc-paidy.js',
		        array(),
		        JP4WC_VERSION,
		        true
	        );
            $paygent_token_js_link = 'https://apps.paidy.com/';
            if(is_checkout()){
                wp_enqueue_script(
                    'paidy-token',
                    $paygent_token_js_link,
                    array(),
	                JP4WC_VERSION,
                    false
                );
                // Paidy Payment for Checkout page
                wp_register_style(
                    'jp4wc-paidy',
                    JP4WC_URL_PATH . 'assets/css/jp4wc-paidy.css',
                    false,
                    JP4WC_VERSION
                );
                wp_enqueue_style( 'jp4wc-paidy' );
            }
        }
    }

    /**
     * Load Paidy javascript for Admin
     */
    public function admin_enqueue_scripts(){
        // Image upload.
        wp_enqueue_media();
        if ( is_admin() && wp_script_is('wc-gateway-ppec-settings') == false && isset($_GET['section']) && $_GET['section'] =='paidy') {
            wp_enqueue_script(
                'wc-gateway-paidy-settings',
                JP4WC_URL_PATH . 'assets/js/wc-gateway-paidy-settings.js',
                array('jquery'),
                JP4WC_VERSION,
                true
            );
        }
        if( is_admin() && isset($_GET['section']) && isset($_GET['tab']) && $_GET['section'] == 'paidy' && $_GET['tab'] == 'checkout' ){
            wp_register_style(
                'jp4wc_paidy_admin',
                JP4WC_URL_PATH . 'assets/css/admin-jp4wc-paidy.css',
                false,
                JP4WC_VERSION
            );
            wp_enqueue_style( 'jp4wc_paidy_admin' );
        }
    }

    /**
     * Update Cancel from Auth to Paidy System
     *
     * @param object $checkout
     */
    public function checkout_reject_to_cancel( $checkout ){
        if( isset($_GET['status']) ){
            if($_GET['status'] == 'closed'){
                $message = __('Once the customer interrupted the payment.. Order ID:', 'woocommerce-for-japan').$_GET['order_id'];
                $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'paidy-wc');
            }elseif($_GET['status'] == 'rejected' or isset($_GET['order_id'])){
                $reject_message = __('This Paidy payment has been declined. Please select another payment method.', 'woocommerce-for-japan');
                wc_add_notice( $reject_message, 'error');
            }
        }
    }

    /**
     * Update Complete at thank you page for Paidy Payment
     *
     * @param string $order_id
     */
    public function thankyou_completed($order_id){
        $order = wc_get_order($order_id);
        $current_status = $order->get_status();
        if($current_status == 'pending' || $current_status == 'cancelled'){
            // Reduce stock levels
            wc_reduce_stock_levels( $order_id );
            $order->payment_complete($_GET['transaction_id']);
            $message = __('Paidy Payment succeeds to authorize and move to thank you page. Get data is following.', 'woocommerce-for-japan')."\n";
            $message .= $this->jp4wc_framework->jp4wc_array_to_message($_GET);
            $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'paidy-wc');
        }
    }

    /**
     * Validate api_public_key input and add error
     *
     * @param string $key not use
     * @param string $value
     * @return mixed
     */
    public function validate_api_public_key_field( $key, $value ) {
        return $this->setting_required_field( __( 'API Public Key', 'woocommerce-for-japan' ), __( 'Production environment', 'woocommerce-for-japan' ), $value );
    }

    /**
     * Validate api_secret_key input and add error
     *
     * @param string $key not use
     * @param string $value
     * @return mixed
     */
    public function validate_api_secret_key_field( $key, $value ) {
        return $this->setting_required_field( __( 'API Secret Key', 'woocommerce-for-japan' ), __( 'Production environment', 'woocommerce-for-japan' ), $value );
    }

    /**
     * Validate api_public_key input and add error
     *
     * @param string $title
     * @param string $environment
     * @param string $value
     * @return mixed
     */
    public function setting_required_field( $title, $environment, $value ) {
        if ( isset( $value )  && 1 > strlen( $value ) ) {
            $paidy_link = 'https://campaign.paidy.com/woocommerce';
            /* translators: 1) Field title 2) Environment 3) Paidy PR link */
            WC_Admin_Settings::add_error( sprintf(__( 'If you do not set %1$s, it will not work in the %2$s. Application is necessary for acquisition.', 'woocommerce-for-japan' ), $title, $environment, $paidy_link) );
        }

        return $value;
    }

    /**
     * Update Cancel from Auth to Paidy System
     *
     * @param string $order_id
     */
    public function jp4wc_order_paidy_status_cancelled( $order_id ){
        $secret_key = $this->set_api_secret_key();
        $order = wc_get_order( $order_id );
        $order_payment_method = $order->get_payment_method();
        $transaction_id = $order->get_transaction_id();
        if( $order_payment_method == $this->id && !empty($transaction_id)) {
            $send_url = 'https://api.paidy.com/payments/' . $transaction_id . '/close';
            $args = array(
                'method' => 'POST',
                'body' => '{}',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Paidy-Version' => '2018-04-10',
                    'Authorization' => 'Bearer ' . $secret_key
                )
            );

            $message = 'Send URL is following. : ' . $send_url;
            $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'paidy-wc');

            $close = wp_remote_post($send_url, $args);
            $close_array = json_decode($close['body'], true);
            if (is_wp_error($close)) {
                $order->add_order_note($close->get_error_message());
            } elseif ($close_array['status'] == 'closed') {
                $message = $this->jp4wc_framework->jp4wc_array_to_message($close_array) . 'This is success cancellation data.';
                $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'paidy-wc');
            } else {
                $message = $this->jp4wc_framework->jp4wc_array_to_message($close_array) . 'This is close data.';
                $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'paidy-wc');

                $order->add_order_note(__('Cancelled processing has not been completed due to a Paidy error. Please check Paidy admin.', 'woocommerce-for-japan'));
            }
        }
    }

    /**
     * Update Sale from Auth to Paidy System
     *
     * @param string $order_id
     * @return boolean true or false
     */
    public function jp4wc_order_paidy_status_completed( $order_id ){
        $secret_key = $this->set_api_secret_key();
        $order = wc_get_order( $order_id );
        $order_payment_method = $order->get_payment_method();
        if( $order_payment_method == $this->id ){
            $transaction_id = $order->get_transaction_id();
            $send_url = 'https://api.paidy.com/payments/'.$transaction_id.'/captures';
            $args = array(
                'method' => 'POST',
                'body' => '{"metadata": {"Platform": "WooCommerce"}}',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Paidy-Version' => '2018-04-10',
                    'Authorization' => 'Bearer '.$secret_key
                )
            );
            if($this->debug != 'no'){
                $debug_message = 'Send URL is following. : '.$send_url;
                $this->jp4wc_framework->jp4wc_debug_log( $debug_message, true, 'paidy-wc');
            }
            $capture = wp_remote_post( $send_url, $args );
            $order->add_order_note(__('In the payment completion process, the amount and ID match were confirmed.', 'woocommerce-for-japan'));
            $capture_array = json_decode( $capture['body'], true );

            if( $capture_array['status'] == 'closed' ) {
                $message = $this->jp4wc_framework->jp4wc_array_to_message($capture_array) . 'This is capture data.';
                $this->jp4wc_framework->jp4wc_debug_log($message, $this->debug, 'paidy-wc');

                $order->set_meta_data( array( 'paidy_capture_id' => $capture_array['captures'][0]['id'] ) );
                $order->save_meta_data();
                if ($capture_array['amount'] == $order->get_total() and $transaction_id == $capture_array['id']) {
                    $order->add_order_note(__('In the payment completion process, the amount and ID match were confirmed.', 'woocommerce-for-japan'));
                    return true;
                } else {
                    $order->add_order_note(__('In the payment completion process, the amount and ID did not match. Check on the Paidy admin.', 'woocommerce-for-japan'));
                }
            }elseif( is_wp_error( $capture )){
                $message = $capture->get_error_message();
                $order->add_order_note( $message );
            }else{
                $message = $this->jp4wc_framework->jp4wc_array_to_message($capture_array).'This is capture data.';
                $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'paidy-wc');

                $order->add_order_note( __('Completion processing has not been completed due to a Paidy error.', 'woocommerce-for-japan') );
            }
            $situation = __('Status Change from Processing to completed.', 'woocommerce-for-japan');
            $email_message = $this->notice_message( $order_id, $transaction_id, $situation, $message );
            $this->send_notice_email( $email_message );
            return false;
        }
        return true;
    }

    /**
     * Process a refund if supported
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     * @return  boolean True or false based on success, or a WP_Error object
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $secret_key = $this->set_api_secret_key();
        $order = wc_get_order( $order_id );
        $order_payment_method = $order->get_payment_method();
        $capture_id = $order->get_meta( 'paidy_capture_id', true );
        if( $order_payment_method == $this->id ) {
            $transaction_id = $order->get_transaction_id();
            $post_data = '{"capture_id":"' . $capture_id . '","amount":"' . $amount . '","metadata" : {"Platform" : "WooCommerce"}}';
            $send_url = 'https://api.paidy.com/payments/' . $transaction_id . '/refunds';
            $args = array(
                'method' => 'POST',
                'body' => $post_data,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Paidy-Version' => '2018-04-10',
                    'Authorization' => 'Bearer ' . $secret_key
                )
            );
            // Debug message
            $message = 'Send URL is following. : '.$send_url."\n";
            $message .= 'Post data is following. : '.$post_data;
            $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'paidy-wc');
            if($capture_id != ''){
                $refund = wp_remote_post($send_url, $args);
            }else{
                $order->add_order_note( __('Refund is not possible because Paidy has not completed processing.', 'woocommerce-for-japan') );
                return false;
            }
            $refund_array = json_decode( $refund['body'], true );
            if( is_wp_error( $refund )){
                $order->add_order_note( $refund->get_error_message() );
                return false;
            }elseif( $refund_array['status'] == 'closed' ){
                $refunds_array = $order->get_meta( 'paidy_refund_id', false );
                if( empty($refunds_array) ){
                    $refunds_array = array( $refund_array['refunds'][0]['id'] );
                }else{
                    $refunds_array = array_merge( $refunds_array, array( $refund_array['refunds'][0]['id'] ));
                }
                $order->set_meta_data( array( 'paidy_refund_id' => $refunds_array ) );
                $order->save_meta_data();
                $order->add_order_note( __('Completion refunding has been completed at Paidy.', 'woocommerce-for-japan') );
                return true;
            }else{
                $message = $this->jp4wc_framework->jp4wc_array_to_message($refund_array).'This is refund data.';
                $this->jp4wc_framework->jp4wc_debug_log( $message, $this->debug, 'paidy-wc');

                $order->add_order_note( __('Completion processing has not been completed due to a Paidy error.', 'woocommerce-for-japan') );
                return false;
            }
        }
        return true;
    }

	/**
     * Check Paidy payment details by payment_id
     *
     * @param string $payment_id
     * @return void
     */
    public function paidy_get_payment_data( $payment_id ){
        $send_url = 'https://api.paidy.com/payments/' . $payment_id ;
        $args = array(
            'method' => 'GET',
            'body' => '',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Paidy-Version' => '2018-04-10',
                'Authorization' => 'Bearer ' . $this->set_api_secret_key()
            )
        );
		$payment_order = wp_remote_post( $send_url, $args );
		if( isset($payment_order['response']['code']) || $payment_order['response']['code'] != 200 ){
			return false;
		}
		return json_decode($payment_order['body']);
    }

    /**
     * Send notice e-mail to shop owner
     *
     * @param  string $message
     */
    public function send_notice_email( $message ) {
        $to = $this->notice_email;
        if(is_email($to)){
            $subject = __('[WooCommerce] Notice of error occurrence in Paidy payment linkage', 'woocommerce-for-japan');
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            wp_mail( $to, $subject, $message, $headers);
        }
    }

    /**
     * Send notice e-mail to shop owner
     *
     * @param  string $order_id
     * @param  string $transaction_id
     * @param  string $situation
     * @param  string $error_message
     * @return string
     */
    public function notice_message( $order_id, $transaction_id, $situation, $error_message = null ){
        $message = '';
        $message .= __('An error has occurred in the Paidy payment linkage when changing the status of WooCommerce.', 'woocommerce-for-japan'). "\n";
        $message .= __('Please check the status of Paidy payment and respond accordingly.', 'woocommerce-for-japan'). "\n". "\n";
        $message .= __('Order number:', 'woocommerce-for-japan'). $order_id ."\n";
        $message .= __('Order details URL:', 'woocommerce-for-japan'). site_url().'/wp-admin/post.php?post='.$order_id.'&action=edit'."\n";
        $message .= __('Paidy payment ID:', 'woocommerce-for-japan'). $transaction_id ."\n";
        $message .= __('Error occurrence time:', 'woocommerce-for-japan').date("Y/m/d H:i:s"). "\n";
        $message .= __('Error situation:', 'woocommerce-for-japan').$situation. "\n";
        if(isset($error_message)){
            $message .= __('Error situation:', 'woocommerce-for-japan').$error_message. "\n";
        }
        $message .= "\n".__('* This e-mail is for sending only, so you cannot reply directly to this e-mail.', 'woocommerce-for-japan');
        return $message;
    }

    /**
     * Generate Image HTML.
     *
     * @param  mixed $key
     * @param  mixed $data
     * @since  1.5.0
     * @return string
     */
    public function generate_image_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data  = wp_parse_args( $data, $defaults );
        $value = $this->get_option( $key );

        // Hide show add remove buttons.
        $maybe_hide_add_style    = '';
        $maybe_hide_remove_style = '';

        // For backwards compatibility (customers that already have set a url)
        $value_is_url            = filter_var( $value, FILTER_VALIDATE_URL ) !== false;

        if ( empty( $value ) || $value_is_url ) {
            $maybe_hide_remove_style = 'display: none;';
        } else {
            $maybe_hide_add_style = 'display: none;';
        }

        ob_start();
        ?>
        <tr>
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
            </th>

            <td class="image-component-wrapper">
                <div class="image-preview-wrapper">
                    <?php
                    if ( ! $value_is_url ) {
                        echo wp_get_attachment_image( $value, 'thumbnail' );
                    } else {
                        echo sprintf( __( 'Already using URL as image: %s', 'woocommerce-for-japan' ), $value );
                    }
                    ?>
                </div>

                <button
                        class="button image_upload"
                        data-field-id="<?php echo esc_attr( $field_key ); ?>"
                        data-media-frame-title="<?php echo esc_attr( __( 'Select a image to upload', 'woocommerce-for-japan' ) ); ?>"
                        data-media-frame-button="<?php echo esc_attr( __( 'Use this image', 'woocommerce-for-japan' ) ); ?>"
                        data-add-image-text="<?php echo esc_attr( __( 'Add image', 'woocommerce-for-japan' ) ); ?>"
                        style="<?php echo esc_attr( $maybe_hide_add_style ); ?>"
                >
                    <?php echo esc_html__( 'Add image', 'woocommerce-for-japan' ); ?>
                </button>

                <button
                        class="button image_remove"
                        data-field-id="<?php echo esc_attr( $field_key ); ?>"
                        style="<?php echo esc_attr( $maybe_hide_remove_style ); ?>"
                >
                    <?php echo esc_html__( 'Remove image', 'woocommerce-for-japan' ); ?>
                </button>

                <input type="hidden"
                       name="<?php echo esc_attr( $field_key ); ?>"
                       id="<?php echo esc_attr( $field_key ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                />
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function wc_paidy_blocks_support(){
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry  $payment_method_registry ) {
					require_once 'class-wc-payments-paidy-blocks-support.php';
					$payment_method_registry->register( new WC_Gateway_Paidy_Blocks_Support() );
				}
			);
		}
	}
}

if( function_exists( 'add_wc4jp_paidy_gateway' ) === false ){
    /**
     * Add the gateway to woocommerce
     *
     * @param array Methods
     * @return array Methods
     */
    function add_wc4jp_paidy_gateway( $methods ) {
        $methods[] = 'WC_Gateway_Paidy';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_paidy_gateway' );
}

/**
 * The available gateway to woocommerce only Japanese currency
 */
if( function_exists( 'wc4jp_paidy_available_gateways' ) === false ) {
    function wc4jp_paidy_available_gateways($methods)
    {
        $currency = get_woocommerce_currency();
        if ($currency != 'JPY') {
            unset($methods['paidy']);
        }
        return $methods;
    }

    add_filter('woocommerce_available_payment_gateways', 'wc4jp_paidy_available_gateways');
}
add_shortcode( 'test_display', 'test_display' );
function test_display(){
	$payment_id = 'pay_ZMr_CFMAAFMAc_Bj';
	$disp = new WC_Gateway_Paidy();
	$order = $disp->paidy_get_payment_data( $payment_id );
	echo $order->id."TEST</br>";
	print_r($order);
}
