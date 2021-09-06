<?php
/**
 * Cart handler.
 *
 * @version		1.1.1
 * @author 		Artisan Workshop
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

/**
 * WC_Gateway_LINEPay_Cart_Handler handles button display in the cart.
 */
class WC_Gateway_LINEPay_Cart_Handler {
    /**
     * Framework.
     *
     * @var stdClass LINEPay_func
     */
    public $linepay_func;

    /**
     * Checkout class
     *
     * @var stdClass LINEPay_func
     */
    public $gateway_linepay;

    /**
     * Framework.
     *
     * @var stdClass JP4WC_Plugin
     */
    public $jp4wc_framework;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_before_cart_totals', array( $this, 'before_cart_totals' ) );
        add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_linepay_button' ), 20 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        //Set class LINEPay_func
        require_once ('includes/class-wc-linepay-func.php');
        $this->linepay_func = new LINEPay_func;
        //Set class LINEPay_func
        require_once ('class-wc-gateway-linepay.php');
        $this->gateway_linepay = new WC_Gateway_LINEPay();
        $this->jp4wc_framework = new Framework\JP4WC_Plugin();
    }

    /**
     * Start checkout handler when cart is loaded.
     */
    public function before_cart_totals(){
        // If there then call start_checkout() else do nothing so page loads as normal.
        if (!empty($_GET['startcheckout']) && 'true' === $_GET['startcheckout']) {
            // Trying to prevent auto running checkout when back button is pressed from PayPal page.
            $_GET['startcheckout'] = 'false';

            //Create new order
            $order_id = wc_create_order();
            $order = wc_get_order($order_id);
	        $order->set_payment_method( 'linepay' );
            $order->set_created_via('checkout');
	        $cart_hash = WC()->cart->get_cart_hash();
            $order->set_cart_hash($cart_hash);
            $order->set_customer_id(apply_filters('woocommerce_checkout_customer_id', get_current_user_id()));
            $order_vat_exempt = WC()->cart->get_customer()->get_is_vat_exempt() ? 'yes' : 'no';
            $order->add_meta_data('is_vat_exempt', $order_vat_exempt);
            $order->set_currency(get_woocommerce_currency());
            $order->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
            $order->set_customer_ip_address(WC_Geolocation::get_ip_address());
            $order->set_customer_user_agent(wc_get_user_agent());
            $order->set_discount_total(WC()->cart->get_discount_total());
            $order->set_discount_tax(WC()->cart->get_discount_tax());
            $order->set_cart_tax(WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax());
            $order->set_total(WC()->cart->get_total('edit') - WC()->cart->get_shipping_total());
            $checkout = WC()->checkout();
            $checkout->create_order_line_items($order, WC()->cart);
            $checkout->create_order_fee_lines($order, WC()->cart);
            $checkout->create_order_tax_lines($order, WC()->cart);
            $checkout->create_order_coupon_lines($order, WC()->cart);
            $order->save();

            $requestUri = '/v3/payments/request';
            $post_data = $this->gateway_linepay->set_api_cart_order($order);
            //Shipping setting with Virtual order
            $post_data['options']['shipping']['type'] = 'NO_SHIPPING';
            $items = $order->get_items();
            // go through each item
            foreach ($items as $item) {
                // if it is a variation
                if ('0' != $item['variation_id']) {
                    // make a product based upon variation
                    $product = new WC_Product($item['variation_id']);
                } else {
                    // else make a product off of the product id
                    $product = new WC_Product($item['product_id']);
                }
                // if the product isn't virtual, exit
                if (!$product->is_virtual()) {
                    $post_data['options']['shipping']['type'] = 'SHIPPING';
                    $post_data['options']['shipping']['feeInquiryUrl'] = site_url() . '/wp-json/linepay/v1/shippings/';
                    $post_data['options']['shipping']['feeInquiryType'] = 'CONDITION';
                }
            }
            //Set Redirect URLs
            $post_data['redirectUrls']['confirmUrl'] = $this->gateway_linepay->get_return_url( $order );
            $post_data['redirectUrls']['cancelUrl'] = wc_get_cart_url() . '?linepay=cancel&order_id='.$order->get_id();
            $json_content = json_encode($post_data);

            $response = $this->gateway_linepay->linepay_func->send_api_linepay($requestUri, $json_content, $this->gateway_linepay->debug, 'POST', $order->get_id() );
            $response_message = $this->gateway_linepay->response_request_message($response);
            $this->jp4wc_framework->jp4wc_debug_log($response_message, $this->gateway_linepay->debug, 'linepay-wc');
            if ($response->returnCode == '0000') {
                $order->set_transaction_id($response->info->transactionId);
                $order->save();
                // Return thankyou redirect
                if ($this->jp4wc_framework->isSmartPhone()) {
                    echo '<script type="text/javascript"> window.location.href = "' . $response->info->paymentUrl->app . '"; </script>';
                } else {
                    echo '<script type="text/javascript"> window.location.href = "' . $response->info->paymentUrl->web . '"; </script>';
                }
            }
        }
    }

    /**
     * Display paypal button on the cart page.
     */
    public function display_linepay_button() {

        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $cart_checkout_enabled = $this->gateway_linepay->cart_checkout_enabled;

        // billing details on checkout page to calculate shipping costs
        if ( ! isset( $gateways['linepay'] ) || 'no' === $cart_checkout_enabled ) {
            return;
        }
    ?>
    		<div class="wc-checkout-buttons linepay_wc_cart_buttons_div">

			<?php if ( has_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout' ) ) : ?>
				<div class="wc-checkout-buttons__separator">
					<?php _e( '&mdash; or &mdash;', 'woocommerce-for-japan' ); ?>
				</div>
			<?php endif;
            wp_enqueue_style( 'wc-gateway-linepay-smart-payment-buttons' );
            ?>

                <div id="woo_linepay_ec_button_cart" class="woo_linepay_ec_button_cart">
                    <a href="<?php echo esc_url( add_query_arg( array( 'startcheckout' => 'true' ), wc_get_page_permalink( 'cart' ) ) ); ?>"><img src="<?php echo JP4WC_URL_PATH;?>assets/images/linepay_white.png" alt="LINE Pay logo"/></a>
                </div>
            </div>
<?php
    }

    /**
     * Frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('wc-gateway-linepay-smart-payment-buttons', JP4WC_URL_PATH.'assets/css/linepay_button.css');
    }
}

new WC_Gateway_LINEPay_Cart_Handler();