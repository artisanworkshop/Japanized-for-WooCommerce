<?php
/**
 * Cash on Delivery Gateway for Subscriptions
 *
 * This file provides a Cash on Delivery payment gateway that supports WooCommerce Subscriptions.
 *
 * @package WooCommerce-For-Japan
 * @version 2.7.11
 * @category Payment Gateways
 * @author ArtsanWorkshop
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\OrderStatus;

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
 * @version     2.7.11
 * @package     WooCommerce/Classes/Payment
 * @author      ArtsanWorkshop
 */
class WC_Gateway_COD2 extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'cod2';

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
		$this->id                 = self::ID;
		$this->icon               = apply_filters( 'woocommerce_cod2_icon', JP4WC_URL_PATH . '/assets/images/jp4wc-cash-on-delivery.png' );
		$this->method_title       = __( 'Cash on Delivery for Subscriptions', 'woocommerce-for-japan' );
		$this->method_description = __( 'Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce-for-japan' );
		$this->has_fields         = false;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array(
			'products',
		);

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
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
				'options'           => $this->load_shipping_method_options(),
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
		$is_virtual       = true;
		$shipping_methods = array();

		// Get shipping methods from the cart or order.
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order            = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
			$shipping_methods = $order ? $order->get_shipping_methods() : array();
			$is_virtual       = ! count( $shipping_methods );
		} elseif ( WC()->cart && WC()->cart->needs_shipping() ) {
			$shipping_methods = WC()->cart->get_shipping_methods();
			$is_virtual       = false;
		}

		// If COD is not enabled for virtual orders and the order does not need shipping, return false.
		if ( ! $this->enable_for_virtual && $is_virtual ) {
			return false;
		}

		// Return early if:
		// - There are no shipping methods resrictions in place.
		// - The order is virtual so needs no shipping.
		// - Shipping methods are not set yet.
		if ( empty( $this->enable_for_methods ) || $is_virtual || ! $shipping_methods ) {
			return parent::is_available();
		}

		// Get the selected shipping method ids. This works on both WC_Shipping_Rate and WC_Order_Item_Shipping class instances.
		$canonical_rate_ids = array_unique(
			array_values(
				array_map(
					function ( $shipping_method ) {
						return $shipping_method && is_callable( array( $shipping_method, 'get_method_id' ) ) && is_callable( array( $shipping_method, 'get_instance_id' ) ) ? $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id() : null;
					},
					$shipping_methods
				)
			)
		);

		if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || self::ID !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		if ( Constants::is_true( 'REST_REQUEST' ) ) {
			global $wp;
			if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();
		$zones      = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return array
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
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
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) ) . PHP_EOL;
		}
	}

	/**
	 * Add the gateway to woocommerce
	 *
	 * @param array $methods Payment methods.
	 * @return array
	 */
	public static function add_gateway( $methods ) {
		if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
			$subscription_support_enabled = true;
		}
		if ( isset( $subscription_support_enabled ) ) {
			$methods[] = 'WC_Addons_Gateway_COD2';
		} else {
			$methods[] = 'WC_Gateway_COD2';
		}
		return $methods;
	}
}

// Add the gateway to WooCommerce.
if ( get_option( 'wc4jp-cod2' ) ) {
	add_filter( 'woocommerce_payment_gateways', array( 'WC_Gateway_COD2', 'add_gateway' ) );
}
