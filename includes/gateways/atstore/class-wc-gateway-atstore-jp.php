<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * At Store Payment Gateway in Japanese
 *
 * Provides a At Store Payment Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class 			WC_Gateway_AtStore_JP
 * @extends		WC_Payment_Gateway
 * @version		2.1.14
 * @package		WooCommerce/Classes/Payment
 * @author 		Artisan Workshop
 */
class WC_Gateway_AtStore_JP extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
	public function __construct() {
		$this->id                 = 'atstore';
		$this->has_fields         = false;
		$this->method_title       = __( 'Pay At Store', 'woocommerce-for-japan' );
		$this->method_description = __( 'Allows At Store payments.', 'woocommerce-for-japan' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable At Store Payment', 'woocommerce-for-japan' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'At Store Payment', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Please pay the fee at Store.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-for-japan' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
    }

    /**
     * Output for the order received page.
     */
	public function thankyou_page() {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
        }
	}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	    $payment_method = version_compare( WC_VERSION, '2.7', '<' ) ? $order->payment_method : $order->get_payment_method();
        if ( $this->instructions && ! $sent_to_admin && 'atstore' === $payment_method && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( 'on-hold', __( 'Awaiting At Store payment', 'woocommerce-for-japan' ) );

		// Reduce stock levels
        wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}
}
/**
 * Add the gateway to woocommerce
 */
function add_wc4jp_commerce_atstore_gateway( $methods ) {
	$methods[] = 'WC_Gateway_AtStore_JP';
	return $methods;
}
if(get_option('wc4jp-atstore')) add_filter( 'woocommerce_payment_gateways', 'add_wc4jp_commerce_atstore_gateway' );

