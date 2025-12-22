<?php
/**
 * JP4WC Yomigana Blocks Integration
 *
 * @package JP4WC\Blocks
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class for integrating yomigana (name reading) fields with WooCommerce Blocks.
 * Uses WooCommerce Additional Checkout Fields API (no custom React components needed).
 */
class JP4WC_Yomigana_Blocks_Integration implements IntegrationInterface {

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'jp4wc-yomigana';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 * NOTE: Field registration happens earlier in woocommerce_init hook (see class-jp4wc.php).
	 */
	public function initialize() {
		// Check if Additional Checkout Fields API is available (WooCommerce 9.3+).
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			$this->log_info( 'Additional Checkout Fields API not available - skipping Block integration' );
			return;
		}

		// DO NOT register fields here - they are registered earlier in woocommerce_init hook.
		// This ensures fields are available to the frontend before blocks are initialized.

		add_filter( 'woocommerce_validate_additional_field', array( $this, 'validate_additional_field' ), 10, 3 );
		// Save additional fields to order meta in classic format for compatibility.
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'save_to_order_meta' ), 100, 2 );
		// Hide WooCommerce's default display of additional fields (we use our own).
		add_filter( 'woocommerce_order_get_formatted_meta_data', array( $this, 'hide_additional_fields_from_order_meta' ), 10, 2 );
		// Enqueue CSS for field ordering.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_styles' ) );
	}

	/**
	 * Enqueue CSS and JS for block checkout field ordering.
	 */
	public function enqueue_block_styles() {
		if ( is_checkout() && get_option( 'wc4jp-yomigana' ) ) {
			wp_enqueue_script(
				'jp4wc-checkout-blocks-js',
				plugins_url( 'assets/js/checkout-blocks-jp4wc.js', dirname( __DIR__ ) ),
				array(),
				'1.0.10',
				true
			);
		}
	}

	/**  * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array();
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}

	/**
	 * Register yomigana checkout fields using WooCommerce Additional Checkout Fields API.
	 * This automatically creates the UI in the checkout block.
	 */
	public function register_checkout_fields() {
		// Prevent duplicate registration using static flag.
		static $registered = false;
		if ( $registered ) {
			return;
		}

		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			$this->log_info( 'ERROR: woocommerce_register_additional_checkout_field function not found' );
			return;
		}

		// CRITICAL: Check if woocommerce_blocks_loaded has run.
		// If not, the CheckoutFields class won't be available yet.
		$blocks_loaded = did_action( 'woocommerce_blocks_loaded' );
		if ( ! $blocks_loaded ) {
			add_action(
				'woocommerce_blocks_loaded',
				array( $this, 'register_checkout_fields' ),
				10
			);
			return;
		}

		// Verify CheckoutFields class is available.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) ) {
			$this->log_info( 'ERROR: CheckoutFields class not found even after woocommerce_blocks_loaded' );
			return;
		}

		// Verify Package container is available.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			$this->log_info( 'ERROR: Package class not found' );
			return;
		}

		// Check if yomigana fields are enabled.
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			$this->log_info( 'Yomigana fields NOT enabled - skipping registration' );
			return;
		}

		$is_required = get_option( 'wc4jp-yomigana-required' ) === '1';

		// Register yomigana last name field (applies to both billing and shipping).
		// Note: Field order is controlled via JavaScript (see checkout-blocks-jp4wc.js).
		$field_config = array(
			'id'            => 'jp4wc/yomigana_last_name',
			'label'         => __( 'Last Name ( Yomigana )', 'woocommerce-for-japan' ),
			'location'      => 'address',
			'type'          => 'text',
			'required'      => $is_required,
			'show_in_order' => true,
			'attributes'    => array(
				'autocomplete' => 'family-name',
			),
		);

		try {
			$result = woocommerce_register_additional_checkout_field( $field_config );
			if ( is_wp_error( $result ) ) {
				$this->log_info( 'ERROR registering yomigana last name: ' . $result->get_error_message() );
			}
		} catch ( \Exception $e ) {
			$this->log_info( 'EXCEPTION registering yomigana last name: ' . $e->getMessage() );
		}

		// Register yomigana first name field (applies to both billing and shipping).
		// Note: Field order is controlled via JavaScript (see checkout-blocks-jp4wc.js).
		$field_config = array(
			'id'            => 'jp4wc/yomigana_first_name',
			'label'         => __( 'First Name ( Yomigana )', 'woocommerce-for-japan' ),
			'location'      => 'address',
			'type'          => 'text',
			'required'      => $is_required,
			'show_in_order' => true,
			'attributes'    => array(
				'autocomplete' => 'given-name',
			),
		);

		try {
			$result = woocommerce_register_additional_checkout_field( $field_config );
			if ( is_wp_error( $result ) ) {
				$this->log_info( 'ERROR registering yomigana first name: ' . $result->get_error_message() );
			}
		} catch ( \Exception $e ) {
			$this->log_info( 'EXCEPTION registering yomigana first name: ' . $e->getMessage() );
		}

		// Mark as registered to prevent duplicate calls.
		$registered = true;
	}

	/**
	 * Save yomigana fields to order meta in classic format for compatibility.
	 * This ensures that block checkout data is compatible with classic checkout.
	 *
	 * @param WC_Order        $order   Order object.
	 * @param WP_REST_Request $request Request object.
	 */
	public function save_to_order_meta( $order, $request ) {
		// Get the billing and shipping addresses from the request.
		$billing_address  = $request->get_param( 'billing_address' );
		$shipping_address = $request->get_param( 'shipping_address' );

		// Debug: Log the current order data.
		$this->log_info( 'Order ID: ' . $order->get_id() );
		$this->log_info( 'Billing first_name: ' . $order->get_billing_first_name() );
		$this->log_info( 'Billing last_name: ' . $order->get_billing_last_name() );
		$this->log_info( 'Billing address data: ' . wp_json_encode( $billing_address ) );
		$this->log_info( 'Shipping address data: ' . wp_json_encode( $shipping_address ) );

		// Map of field IDs to meta keys for billing.
		$billing_field_mapping = array(
			'jp4wc/yomigana_last_name'  => '_billing_yomigana_last_name',
			'jp4wc/yomigana_first_name' => '_billing_yomigana_first_name',
		);

		// Map of field IDs to meta keys for shipping.
		$shipping_field_mapping = array(
			'jp4wc/yomigana_last_name'  => '_shipping_yomigana_last_name',
			'jp4wc/yomigana_first_name' => '_shipping_yomigana_first_name',
		);

		// Save billing yomigana fields.
		if ( isset( $billing_address['additional_fields'] ) ) {
			foreach ( $billing_address['additional_fields'] as $field_key => $field_value ) {
				if ( isset( $billing_field_mapping[ $field_key ] ) ) {
					$meta_key = $billing_field_mapping[ $field_key ];
					$order->update_meta_data( $meta_key, sanitize_text_field( $field_value ) );
					$this->log_info( "Saved {$meta_key}: {$field_value}" );
				}
			}
		}

		// Save shipping yomigana fields.
		if ( isset( $shipping_address['additional_fields'] ) ) {
			foreach ( $shipping_address['additional_fields'] as $field_key => $field_value ) {
				if ( isset( $shipping_field_mapping[ $field_key ] ) ) {
					$meta_key = $shipping_field_mapping[ $field_key ];
					$order->update_meta_data( $meta_key, sanitize_text_field( $field_value ) );
					$this->log_info( "Saved {$meta_key}: {$field_value}" );
				}
			}
		}

		$order->save();
	}

	/**
	 * Validate additional field value.
	 *
	 * @param bool   $is_valid Whether the field is valid.
	 * @param string $key      The field key.
	 * @param mixed  $value    The field value.
	 * @return bool|WP_Error True if valid, WP_Error if not.
	 */
	public function validate_additional_field( $is_valid, $key, $value ) {
		// Validate yomigana fields if required.
		$yomigana_fields = array(
			'_wc_billing/jp4wc/yomigana_first_name',
			'_wc_billing/jp4wc/yomigana_last_name',
		);

		if ( in_array( $key, $yomigana_fields, true ) ) {
			if ( get_option( 'wc4jp-yomigana-required' ) === '1' ) {
				if ( empty( $value ) ) {
					return new WP_Error(
						'invalid_yomigana',
						__( 'Please enter the name reading.', 'woocommerce-for-japan' )
					);
				}
			}
			$this->log_info( 'Validated ' . $key . ': ' . $value );
		}

		return $is_valid;
	}

	/**
	 * Hide additional checkout fields from WooCommerce's default order meta display.
	 * We use our own display logic instead.
	 *
	 * @param array    $formatted_meta Formatted meta data.
	 * @param WC_Order $order Order object.
	 * @return array Modified formatted meta data.
	 */
	public function hide_additional_fields_from_order_meta( $formatted_meta, $order ) {
		$fields_to_hide = array(
			'_wc_other/jp4wc/yomigana_last_name',
			'_wc_other/jp4wc/yomigana_first_name',
		);

		foreach ( $formatted_meta as $key => $meta ) {
			if ( in_array( $meta->key, $fields_to_hide, true ) ) {
				unset( $formatted_meta[ $key ] );
			}
		}

		return $formatted_meta;
	}

	/**
	 * Log informational message.
	 *
	 * @param string $message Message to log.
	 */
	private function log_info( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->info(
				'[JP4WC Yomigana Blocks] ' . $message,
				array( 'source' => 'jp4wc_yomigana_block' )
			);
		}
	}
}
