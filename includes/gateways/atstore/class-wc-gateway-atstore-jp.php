<?php
/**
 * At Store Payment Gateway
 *
 * Provides a At Store Payment Gateway for WooCommerce in Japan.
 *
 * @package Japanized_For_WooCommerce
 * @version 2.6.44
 */

use Automattic\WooCommerce\Enums\OrderStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * At Store Payment Gateway in Japanese
 *
 * Provides a At Store Payment Gateway in Japanese. Based on code by Shohei Tanaka.
 *
 * @class       WC_Gateway_AtStore_JP
 * @extends     WC_Payment_Gateway
 * @version     2.6.44
 * @package     WooCommerce/Classes/Payment
 * @author      Artisan Workshop
 */
class WC_Gateway_AtStore_JP extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'atstore';

	/**
	 * Settings parameter
	 *
	 * @var string
	 */
	public $instructions;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = apply_filters( 'jp4wc_atstore_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Pay At Store', 'woocommerce-for-japan' );
		$this->method_description = __( 'Allows At Store payments.', 'woocommerce-for-japan' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable At Store Payment', 'woocommerce-for-japan' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'At Store Payment', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description'  => array(
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
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Whether the email is being sent to the admin.
	 * @param bool     $plain_text Whether the email is in plain text format.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && self::ID === $order->get_payment_method() ) {
			/**
			 * Filter the email instructions order status.
			 *
			 * @since 7.4
			 *
			 * @param string $status The default status.
			 * @param object $order  The order object.
			 */
			$instructions_order_status = apply_filters( 'jp4wc_atstore_email_instructions_order_status', OrderStatus::ON_HOLD, $order );
			if ( $order->has_status( $instructions_order_status ) ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			/**
			 * Filter the order status for cheque payment.
			 *
			 * @since 3.6.0
			 *
			 * @param string $status The default status.
			 * @param object $order  The order object.
			 */
			$process_payment_status = apply_filters( 'jp4wc_atstore_process_payment_order_status', OrderStatus::ON_HOLD, $order );
			// Mark as on-hold (we're awaiting at store).
			$order->update_status( $process_payment_status, _x( 'At Store payment', 'At Store payment method', 'woocommerce-for-japan' ) );

			// Reduce stock levels.
			wc_reduce_stock_levels( $order_id );
		} else {
			$order->payment_complete();
		}

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods Payment methods.
	 * @return array
	 */
	public static function add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_AtStore_JP';
		return $methods;
	}
}

// Add the gateway to WooCommerce.
if ( get_option( 'wc4jp-atstore' ) ) {
	add_filter( 'woocommerce_payment_gateways', array( 'WC_Gateway_AtStore_JP', 'add_gateway' ) );
}
