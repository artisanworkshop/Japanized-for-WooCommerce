<?php
/**
 * PeachPay WooCommerce Gateway that is a single gateway for all payment
 * methods, at least for now. It's likely that we'll need to make separate
 * gateways for each payment method later on.
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Missing
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Adds the peachpay gateway class to wc.
 *
 * @param Array $gateways The gateway array.
 * @return Array
 */
function peachpay_add_gateway_class( $gateways ) {
	$gateways[] = 'PeachPay_Old_Stripe_Gateway';
	$gateways[] = 'PeachPay_Stripe_Gateway';
	$gateways[] = 'PeachPay_PayPal_Gateway';

	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'peachpay_add_gateway_class' );

/**
 * This function is called via the add_action below it to initialize the
 * PeachPay_WC_Gateway class.
 */
function peachpay_init_gateway_class() {
	/**
	 * Base class from which specific payment processors gateways can inherit from.
	 */
	abstract class PeachPay_WC_Gateway extends WC_Payment_Gateway {
		/**
		 * Not used, but there in case WooCommerce code tries to call it.
		 * See https://docs.woocommerce.com/document/payment-gateway-api/
		 */
		public function init_form_fields() {}

		/**
		 * Same as above.
		 */
		public function payment_scripts() {}

		/**
		 * Same as above.
		 */
		public function webhook() {}

		/**
		 * We don't actually process the payment here, but this is a critical
		 * part of our checkout that returns information about the order that
		 * has just been placed so that we can continue working with it on the
		 * client side.
		 *
		 * @param int $order_id The order that was just placed.
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			return $this->prepare_payment_result( $order );
		}

		/**
		 * Collects the information required about the order for use on the
		 * frontend. The main piece was originally the redirect url, but it has
		 * since expanded to include all data about the order for which we just
		 * confirmed that payment.
		 *
		 * @param WC_Order $order The WooCommerce order.
		 * @return array The associative array of order data that is turned
		 * into JSON when it's returned to the frontend.
		 */
		private function prepare_payment_result( WC_Order $order ) {
			$result = array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
				'number'   => $order->get_order_number(),
				'orderID'  => $order->get_id(),
				'details'  => $order->get_data(),
			);

			// If we don't do the below, the end result will be something like
			// "line_items": {"972": {}}, which is not useful because we can't
			// see the line item details. This is because json_encode which runs
			// behind the scenes ignores protected data. We can forcefully
			// unprotect these.
			$result['details']['line_items']     = $this->get_protected( $order->get_items() );
			$result['details']['shipping_lines'] = $this->get_protected( $order->get_shipping_methods() );
			$result['details']['fee_lines']      = $this->get_protected( $order->get_fees() );
			$result['details']['coupon_lines']   = $this->get_protected( $order->get_coupons() );

			// This is not usually part of the WooCommerce order object, but
			// we want to avoid doing math on money whenever possible and so
			// would rather set it here.
			$result['details']['fee_total'] = number_format( $order->get_total_fees() ?? '0', 2 );

			return $result;
		}

		/**
		 * Helper "hack" to get expose protected array items.
		 *
		 * @param array $protected_items The items to expose.
		 */
		private function get_protected( $protected_items ) {
			return array_map(
				function ( WC_Data $item ) {
					return $item->get_data();
				},
				$protected_items
			);
		}
	}

	/**
	 * This class allows us to submit orders with the PeachPay Stripe gateway.
	 */
	// phpcs:ignore
	class PeachPay_Stripe_Gateway extends PeachPay_WC_Gateway {
		/**
		 * Default constructor.
		 */
		public function __construct() {
			$this->id    = 'peachpay_stripe';
			$this->title = 'PeachPay (Stripe)';
			// This needs to be here even though it's blank. Some plugins assume
			// gateways have a description and crash if they do not.
			$this->description  = '';
			$this->has_fields   = false;
			$this->method_title = 'PeachPay (Stripe)';
			$this->supports     = array(
				'products',
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'multiple_subscriptions',
				'subscription_date_changes',
				'refunds',
			);
		}

		/**
		 * Handles fetching the Stripe transaction URL
		 *
		 * The woocommerce plugin fetches the url from calling this function on the payment gateway.
		 *
		 * @param order $order Order object related to transaction.
		 * @return string URL linking the transaction ID with the Stripe merchant dashboard.
		 */
		public function get_transaction_url( $order ) {
			if ( $order->get_meta( 'peachpay_is_test_mode' ) === 'true' ) {
				return sprintf( 'https://dashboard.stripe.com/test/payments/%s', $order->get_transaction_id() );
			} else {
				return sprintf( 'https://dashboard.stripe.com/payments/%s', $order->get_transaction_id() );
			}
		}

		/**
		 * Process refund.
		 *
		 * If the gateway declares 'refunds' support, this will allow it to refund.
		 * a passed in amount.
		 *
		 * @param  int        $order_id Order ID.
		 * @param  float|null $amount Refund amount.
		 * @param  string     $reason Refund reason.
		 * @return boolean True or false based on success, or a WP_Error object.
		 */
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			$url = peachpay_api_url() . 'api/v1/refund';

			$data = array(
				'order_id'     => $order_id,
				'amount'       => $amount,
				'reason'       => $reason,
				'merchant_url' => get_home_url(),
			);

			$params = array(
				'body'    => $data,
				'timeout' => 60,
			);

			$status = wp_remote_post( $url, $params );

			if ( is_wp_error( $status ) ) {
				return false;
			}

			$response = wp_remote_retrieve_body( $status );

			return ( filter_var( $response, FILTER_VALIDATE_BOOLEAN ) );
		}
	}

	/**
	 * This class allows us to submit orders with the PeachPay PayPal gateway.
	 */
	// phpcs:ignore
	class PeachPay_PayPal_Gateway extends PeachPay_WC_Gateway {
		/**
		 * Default constructor.
		 */
		public function __construct() {
			$this->id    = 'peachpay_paypal';
			$this->title = 'PeachPay (PayPal)';
			// This needs to be here even though it's blank. Some plugins assume
			// gateways have a description and crash if they do not.
			$this->description  = '';
			$this->has_fields   = false;
			$this->method_title = 'PeachPay (PayPal)';
			$this->supports     = array(
				'products',
			);
		}
	}

	/**
	 * This is here for backwards compatibility with orders placed before we
	 * split peachpay into peachpay_stripe and peachpay_paypal. If we don't have
	 * this, merchants won't be able to refund orders made prior to the switch
	 * since the payment gateway id is no longer valid. This class makes it a
	 * valid one.
	 *
	 * When viewed from the WooCommerce payment settings, this gateway can be
	 * seen as the "parent" gateway and is the only one to show a description,
	 * which prevents clutter on all of our payment gateways.
	 *
	 * @deprecated
	 */
	// phpcs:ignore
	class PeachPay_Old_Stripe_Gateway extends PeachPay_Stripe_Gateway {
		/**
		 * Default constructor.
		 */
		public function __construct() {
			parent::__construct();
			$this->id                 = 'peachpay';
			$this->title              = 'PeachPay';
			$this->method_title       = 'PeachPay';
			$this->method_description = 'All-in-one checkout. <a href="/wp-admin/admin.php?page=peachpay">Manage PeachPay settings</a>';
		}
	}
}
add_action( 'plugins_loaded', 'peachpay_init_gateway_class', 11 );

/**
 * Gets whether the PeachPay gateway is enabled.
 */
function peachpay_gateway_enabled() {
	$gateways          = WC()->payment_gateways->get_available_payment_gateways();
	$peachpay_gateways = array( 'peachpay', 'peachpay_stripe', 'peachpay_paypal' );

	foreach ( $peachpay_gateways as $gateway ) {
		if ( isset( $gateways[ $gateway ] ) ) {
			return 'yes' === $gateways[ $gateway ]->enabled;
		}

		return false;
	}
}
