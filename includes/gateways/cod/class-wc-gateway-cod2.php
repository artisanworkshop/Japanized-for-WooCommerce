<?php
/**
 * Cash on Delivery Gateway for Subscriptions
 *
 * This file provides a Cash on Delivery payment gateway that supports WooCommerce Subscriptions.
 *
 * @package WooCommerce-For-Japan
 * @version 2.2.19
 * @category Payment Gateways
 * @author ArtsanWorkshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cash on Delivery Gateway for Subscriptions.
 *
 * Provides a Cash on Delivery Payment Gateway for Subscriptions.
 *
 * @class       WC_Gateway_COD2
 * @extends     WC_Payment_Gateway
 * @version     2.2.19
 * @package     WooCommerce/Classes/Payment
 * @author      ArtsanWorkshop
 */
class WC_Gateway_COD2 extends WC_Payment_Gateway {
	/**
	 * Flag to indicate whether or not we need to load code for / support subscriptions.
	 *
	 * @var bool
	 */
	private $subscription_support_enabled = false;

	/**
	 * Instructions to be displayed on the thank you page and in emails.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $instructions;

	/**
	 * Array of shipping method IDs this COD gateway is allowed for
	 *
	 * @var array
	 */
	public $enable_for_methods;

	/**
	 * Enable COD for virtual products option
	 *
	 * @var string
	 */
	public $enable_for_virtual;


	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'cod2';
		$this->icon               = apply_filters( 'woocommerce_cod_icon', JP4WC_URL_PATH . '/assets/images/jp4wc-cash-on-delivery.png' );
		$this->method_title       = __( 'Cash on Delivery for Subscriptions', 'woocommerce-for-japan' );
		$this->method_description = __( 'Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce-for-japan' );
		$this->has_fields         = false;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array(
			'subscriptions',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_date_changes',
		);

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_cod', array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$shipping_methods = array();

		if ( is_admin() ) {
			foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
				$shipping_methods[ $method->id ] = $method->get_method_title();
			}
		}

		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable COD', 'woocommerce-for-japan' ),
				'label'       => __( 'Enable Cash on Delivery', 'woocommerce-for-japan' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'woocommerce-for-japan' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-for-japan' ),
				'default'     => __( 'Cash on Delivery', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Description', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce-for-japan' ),
				'default'     => __( 'Pay with cash upon delivery.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'woocommerce-for-japan' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce-for-japan' ),
				'default'     => __( 'Pay with cash upon delivery.', 'woocommerce-for-japan' ),
				'desc_tip'    => true,
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'woocommerce-for-japan' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'description'       => __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-for-japan' ),
				'options'           => $shipping_methods,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'woocommerce-for-japan' ),
				),
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Accept for virtual orders', 'woocommerce-for-japan' ),
				'label'   => __( 'Accept COD if the order is virtual', 'woocommerce-for-japan' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		// Test if shipping is needed first.
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $order->get_product_from_item( $item );
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled.
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Check methods.
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {

			// Only apply if all packages are being shipped via chosen methods, or order is virtual.
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( isset( $chosen_shipping_methods_session ) ) {
				$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
			} else {
				$chosen_shipping_methods = array();
			}

			$check_method = false;

			if ( is_object( $order ) ) {
				if ( $order->shipping_method ) {
					$check_method = $order->shipping_method;
				}
			} elseif ( empty( $chosen_shipping_methods ) || count( $chosen_shipping_methods ) > 1 ) {
				$check_method = false;
			} elseif ( count( $chosen_shipping_methods ) == 1 ) {
				$check_method = $chosen_shipping_methods[0];
			}

			if ( ! $check_method ) {
				return false;
			}

			$found = false;

			foreach ( $this->enable_for_methods as $method_id ) {
				if ( strpos( $check_method, $method_id ) === 0 ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		}

		return parent::is_available();
	}


	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Mark as processing or on-hold (payment won't be taken until delivery).
		$order->update_status( 'processing', __( 'Payment to be made upon delivery.', 'woocommerce-for-japan' ) );

		// Reduce stock levels.
		wc_reduce_stock_levels( $order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
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
	 * @param object $order WC_Order Order object.
	 * @param bool   $sent_to_admin Whether the email is being sent to the admin.
	 * @param bool   $plain_text Whether the email is plain text.
	 * @return void
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		$payment_method = version_compare( WC_VERSION, '2.7', '<' ) ? $order->payment_method : $order->get_payment_method();
		if ( $this->instructions && ! $sent_to_admin && 'cod2' === $payment_method ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
		}
	}
}
