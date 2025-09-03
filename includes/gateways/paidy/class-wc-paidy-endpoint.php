<?php
/**
 * Paidy endpoint.
 *
 * @package WooCommerce\Gateways
 */

use ArtisanWorkshop\PluginFramework\v2_0_13 as Framework;

/**
 * WC_Paidy_Endpoint class.
 */
class WC_Paidy_Endpoint {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Rest API to receive payment notifications from Paidy.
		add_action( 'rest_api_init', array( $this, 'paidy_register_routes' ) );
		// WebHook to get data from paidy.artws.info.
		add_action( 'rest_api_init', array( $this, 'paidy_check_regist_webhook' ) );
		$this->paidy           = new WC_Gateway_Paidy();
		$this->jp4wc_framework = new Framework\JP4WC_Framework();
	}

	/**
	 * Callback. Rest API to receive payment notifications from Paidy.
	 */
	public function paidy_register_routes() {
		// POST /wp-json/paidy/v1/order .
		register_rest_route(
			'paidy/v1',
			'/order',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'paidy_check_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Paidy Webhook response.
	 * Version: 1.4.6
	 *
	 * @param object $data post data.
	 * @return WP_REST_Response | WP_Error endpoint Paidy webhook response
	 */
	public function paidy_check_webhook( $data ) {
		$debug          = $this->paidy->debug;
		$body_data      = (array) $data->get_body();
		$main_data      = json_decode( $body_data[0], true );
		$notice_message = __( 'Paidy Webhook received. ', 'woocommerce-for-japan' );
		if ( empty( $data ) ) {
			$message = $notice_message . 'no_data';
			$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc' );

			return new WP_Error( 'no_data', 'Invalid author', array( 'status' => 404 ) );
		} elseif ( isset( $main_data['payment_id'] ) && isset( $main_data['order_ref'] ) ) {
			if ( is_numeric( $main_data['order_ref'] ) ) {
				// Debug.
				if ( 'pay_0000000000000001' === $main_data['payment_id'] ) {
					$message = $notice_message . __( 'This notification is a test request from Paidy.', 'woocommerce-for-japan' ) . "\n" . $this->jp4wc_framework->jp4wc_array_to_message( $main_data );
					$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc' );
					return new WP_REST_Response( $main_data, 200 );
				} else {
					$message = $notice_message . __( 'Exist [payment_id] and [order_ref]', 'woocommerce-for-japan' ) . "\n" . $this->jp4wc_framework->jp4wc_array_to_message( $main_data );
					$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc' );
				}

				$order = wc_get_order( $main_data['order_ref'] );
				if ( false === $order ) {
					$message = $notice_message . __( 'The order with this order number does not exist in the store.', 'woocommerce-for-japan' ) . "\n" . 'Order# :' . $main_data['order_ref'];
					$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc' );
					return new WP_REST_Response( $main_data, 200 );
				}
				$status = $order->get_status();

				$enable_authorize_success_statuses = apply_filters( 'paidy_endpoint_enable_authorize_statuses', array( 'pending', 'cancelled' ), $order );

				if ( 'authorize_success' === $main_data['status'] && in_array( $status, $enable_authorize_success_statuses, true ) ) {
					// Reduce stock levels.
					wc_reduce_stock_levels( $main_data['order_ref'] );
					if ( isset( $main_data['payment_id'] ) ) {
						$order->payment_complete( $main_data['payment_id'] );
					} else {
						$order->payment_complete();
					}
					$order->add_order_note(
						// Authorization status.
						sprintf(
							// translators: %s: status of the order.
							__( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ),
							__( 'authorization', 'woocommerce-for-japan' )
						)
					);
				} elseif ( 'authorize_success' === $main_data['status'] && 'processing' === $status ) {
					$order->add_order_note( __( 'This order status is processing, this site received authorize_success from the Paidy webhook.', 'woocommerce-for-japan' ) );
				} elseif ( 'capture_success' === $main_data['status'] && 'completed' === $status ) {
					$order->add_order_note(
						// Completed status.
						sprintf(
							// translators: %s: status of the order.
							__( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ),
							__( 'completed', 'woocommerce-for-japan' )
						)
					);
				} elseif ( 'close_success' === $main_data['status'] && 'cancelled' === $status ) {
					$order->add_order_note(
						// Cancelled status.
						sprintf(
							// translators: %s: status of the order.
							__( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ),
							__( 'cancelled', 'woocommerce-for-japan' )
						)
					);
				} elseif ( 'close_success' === $main_data['status'] && 'completed' === $status ) {
					$order->add_order_note(
						// Close status.
						sprintf(
							// translators: %s: status of the order.
							__( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ),
							__( 'close', 'woocommerce-for-japan' )
						)
					);
				} elseif ( 'refund_success' === $main_data['status'] && 'refunded' === $status ) {
					$order->add_order_note(
						// Refunded status.
						sprintf(
							// translators: %s: status of the order.
							__( 'It succeeded to check the %s of the order in Paidy Webhook.', 'woocommerce-for-japan' ),
							__( 'refunded', 'woocommerce-for-japan' )
						)
					);
				} else {
					// translators: %s: status of the order.
					$order->add_order_note( sprintf( __( 'The system received a notification for order %s via Paidy Webhook.', 'woocommerce-for-japan' ), $main_data['status'] ) );
				}
				return new WP_REST_Response( $main_data, 200 );
			} else {
				// Debug.
				$message = $notice_message . __( 'Payment_id exist but order_id. Payment_id : ', 'woocommerce-for-japan' ) . $main_data['payment_id'] . '; Status : ' . $main_data['status'];
				$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc' );
				return new WP_Error( 'no_order_id', $message, array( 'status' => 404 ) );
			}
		} else {
			// Debug.
			$message = '[no_payment_id]' . $this->jp4wc_framework->jp4wc_array_to_message( $main_data );
			$this->jp4wc_framework->jp4wc_debug_log( $message, $debug, 'paidy-wc' );
			return new WP_Error( 'no_payment_id', 'Invalid author', array( 'status' => 404 ) );
		}
	}

	/**
	 * Callback. Register WebHook route to get data from paidy.artws.info.
	 */
	public function paidy_check_regist_webhook() {
		// POST /wp-json/paidy/v1/check .
		register_rest_route(
			'paidy/v1',
			'/check',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'paidy_regist_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Paidy WebHook registration check.
	 *
	 * @param object $data Request data.
	 * @return WP_REST_Response | WP_Error Response for the webhook registration check.
	 */
	public function paidy_regist_webhook( $data ) {
		$debug     = $this->paidy->debug;
		$body_data = (array) $data->get_body();
		$main_data = json_decode( $body_data[0], true );
		if ( empty( $data ) ) {
			return new WP_Error( 'no_data', 'Invalid author', array( 'status' => 404 ) );
		} elseif ( isset( $main_data['webhook_url'] ) && isset( $main_data['status'] ) ) {
			if ( 'success' === $main_data['status'] ) {
				return new WP_REST_Response( $main_data, 200 );
			} else {
				return new WP_Error( 'no_webhook_url', 'Invalid author', array( 'status' => 404 ) );
			}
		} else {
			return new WP_Error( 'no_webhook_url', 'Invalid author', array( 'status' => 404 ) );
		}
	}
}
