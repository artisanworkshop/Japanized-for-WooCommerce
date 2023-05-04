<?php
/**
 * Abstract PeachPay Authorize.net WC gateway.
 *
 * @PHPCS:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-payment-gateway.php';
require_once PEACHPAY_ABSPATH . 'core/payments/authnet/traits/trait-authnet-gateway-utilities.php';
require_once PEACHPAY_ABSPATH . 'core/payments/authnet/traits/trait-authnet-gateway-settings.php';

/**
 * The base Authorize.net gateway class.
 */
abstract class PeachPay_Authnet_Payment_Gateway extends PeachPay_Payment_Gateway {

	use PeachPay_Authnet_Gateway_Utilities;
	use PeachPay_Authnet_Gateway_Settings;

	public $payment_provider = 'authnet';
	public $min_max_currency = 'USD';

	/**
	 * Constructor for base Authorize.net gateway.
	 */
	public function __construct() {
		if ( ! $this->method_title ) {
			// translators: %s: gateway title
			$this->method_title = sprintf( __( '%s via Authorize.net (PeachPay)', 'peachpay-for-woocommerce' ), $this->title );
		}
		if ( ! $this->method_description ) {
			// translators: %s: gateway title
			$this->method_description = sprintf( __( 'Accept %s payments through Authorize.net', 'peachpay-for-woocommerce' ), $this->title );
		}

		$this->currencies = PeachPay_Authnet_Integration::supported_currencies();
		parent::__construct();
	}

	/**
	 * Validates the submitted POST fields on order submission.
	 */
	public function validate_fields() {
		$result = parent::validate_fields();

        // PHPCS:disable WordPress.Security.NonceVerification.Missing
		$data_descriptor = isset( $_POST['peachpay_authnet_data_descriptor'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_authnet_data_descriptor'] ) ) : null;
		$data_value      = isset( $_POST['peachpay_authnet_data_value'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_authnet_data_value'] ) ) : null;
        // PHPCS:enable

		if ( ! $data_descriptor ) {
			wc_add_notice( __( 'Missing required field "peachpay_authnet_data_descriptor"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		if ( ! $data_value ) {
			wc_add_notice( __( 'Missing required field "peachpay_authnet_data_value"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		return $result;
	}

	/**
	 * Process the PeachPay Authorize.net Payment.
	 *
	 * @param int $order_id The id of the order.
	 */
	public function process_payment( $order_id ) {
		try {
			$authnet_mode = PeachPay_Authnet_Integration::mode();
			$order        = parent::process_payment( $order_id );

        // PHPCS:disable WordPress.Security.NonceVerification.Missing
			$session_id      = isset( $_POST['peachpay_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_session_id'] ) ) : null;
			$transaction_id  = isset( $_POST['peachpay_transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_transaction_id'] ) ) : null;
			$data_descriptor = isset( $_POST['peachpay_authnet_data_descriptor'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_authnet_data_descriptor'] ) ) : null;
			$data_value      = isset( $_POST['peachpay_authnet_data_value'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_authnet_data_value'] ) ) : null;
        // PHPCS:enable

			PeachPay_Authnet_Order_Data::set_peachpay_details(
				$order,
				array(
					'session_id'     => $session_id,
					'transaction_id' => $transaction_id,
					'peachpay_mode'  => peachpay_is_test_mode() ? 'test' : 'live',
					'authnet_mode'   => $authnet_mode,
				)
			);

			// WARNING: The transaction parameters are ordered this way because the API endpoint rejects out of order elements. Order is according to Authorize.net documentation.
			$transaction_params = array(
				'transactionType' => $this->get_option( 'authnet_transaction_type' ),
				'amount'          => $order->get_total(),
				'payment'         => array(
					'opaqueData' => array(
						'dataDescriptor' => $data_descriptor,
						'dataValue'      => $data_value,
					),
				),
				'solution'        => array(
					'id' => PeachPay_Authnet_Integration::solution_id(),
				),
				'order'           => array(
					'invoiceNumber' => $order->get_order_number(),
					'description'   => $this->get_payment_description( $order ),
				),
			);

			if ( PeachPay_Authnet_Advanced::get_setting( 'itemized_order_details' ) === 'yes' ) {
				$transaction_params['lineItems'] = $this->get_authnet_order_line_items( $order );
			}

			$transaction_params['tax'] = array(
				'amount' => $order->get_total_tax(),
			);

			if ( $order->has_shipping_address() ) {
				$transaction_params['shipping'] = array(
					'amount' => $order->get_shipping_total(),
				);
			}

			$transaction_params['billTo'] = array(
				'firstName'   => $order->get_billing_first_name(),
				'lastName'    => $order->get_billing_last_name(),
				'address'     => $order->get_billing_address_1() . $order->get_billing_address_2(),
				'city'        => $order->get_billing_city(),
				'state'       => $order->get_billing_state(),
				'zip'         => $order->get_billing_postcode(),
				'country'     => $order->get_billing_country(),
				'phoneNumber' => $order->get_billing_phone(),
			);

			if ( $order->get_billing_company() ) {
				$company = array(
					'company' => $order->get_billing_company(),
				);
				array_merge( $transaction_params['billTo'], $company );
			}

			if ( $order->has_shipping_address() ) {
				$transaction_params['shipTo'] = array(
					'firstName' => $order->get_shipping_first_name(),
					'lastName'  => $order->get_shipping_last_name(),
					'address'   => $order->get_shipping_address_1() . $order->get_shipping_address_2(),
					'city'      => $order->get_shipping_city(),
					'state'     => $order->get_shipping_state(),
					'zip'       => $order->get_shipping_postcode(),
					'country'   => $order->get_shipping_country(),
				);

				if ( $order->get_shipping_company() ) {
					$company = array(
						'company' => $order->get_shipping_company(),
					);
					array_merge( $transaction_params['shipTo'], $company );
				}
			}

			$transaction_params['customerIP'] = $order->get_customer_ip_address();

			$result = PeachPay_Authnet::create_payment(
				$order,
				$transaction_params,
				$this->get_order_details( $order ),
				$authnet_mode
			);

			if ( ! $result ) {
				return null;
			}

			switch ( PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' ) ) {
				case 'authorizedPendingCapture':
				case 'capturedPendingSettlement':
				case 'settledSuccessfully':
				case 'FDSAuthorizedPendingReview':
				case 'FDSPendingReview':
					return array(
						'result'   => 'success',
						'redirect' => $order->get_checkout_order_received_url(),
					);
				default:
					return null;
			}
		} catch ( Exception $exception ) {
			$message = __( 'Error: ', 'peachpay-for-woocommerce' ) . $exception->getMessage();
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $message, 'error' );
			}

			$order->add_order_note( $message );

			PeachPay_Payment::update_transaction(
				$order,
				array(
					'order_details' => $this->get_order_details( $order ),
					'note'          => $message,
				)
			);

			return null;
		}
	}

	/**
	 * Process a Authorize.net refund.
	 *
	 * @param  int        $order_id Order Id.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			// translators: %s the order id.
			return new \WP_Error( 'wc_' . $order_id . '_refund_failed', sprintf( __( 'Refund error: The order_id %s did not match any orders.', 'peachpay-for-woocommerce' ), strval( $order_id ) ) );
		}

		try {

			if ( ! is_numeric( $amount ) || floatval( $amount ) <= 0 ) {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', __( 'Refund error: Amount must be greater then 0', 'peachpay-for-woocommerce' ) );
			}

			$refund_params = array(
				'transactionType' => 'refundTransaction',
				'amount'          => $amount,
			);

			$order_status = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transactionStatus' );

			if ( 'capturedPendingSettlement' === $order_status && $amount !== $order->get_total() ) {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', __( 'This transaction cannot be partially refunded until it has settled. You can either wait for settlement to process or use the full order total to void the transaction.', 'peachpay-for-woocommerce' ) );
			} elseif ( 'authorizedPendingCapture' === $order_status ) {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', __( 'This transaction cannot be partially refunded until it has been captured and settled. Please capture the order or use the full order total to void the transaction.', 'peachpay-for-woocommerce' ) );
			}

			$result = '';
			if ( $amount === $order->get_total() && 'capturedPendingSettlement' === $order_status ) {
				$result = PeachPay_Authnet::void_payment( $order );
			} else {
				$result = PeachPay_Authnet::refund_payment( $order, $refund_params );
			}

			if ( ! $result['success'] ) {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', ' Refund error: ' . $result['message'] );
			}

			return ( filter_var( $result['success'], FILTER_VALIDATE_BOOLEAN ) );
		} catch ( Exception $exception ) {
			$message = __( 'Refund error: ', 'peachpay-for-woocommerce' ) . $exception->getMessage();
			$order->add_order_note( $message );

			PeachPay_Payment::update_transaction(
				$order,
				array(
					'order_details' => $this->get_order_details( $order ),
					'note'          => $message,
				)
			);

			return new \WP_Error( 'wc_' . $order_id . '_refund_failed', $message );
		}
	}

	/**
	 * If Authorize.net is not connected we should prompt the merchant to connect while viewing any Authorize.net gateway.
	 */
	protected function action_needed_form() {
		if ( ! PeachPay_Authnet_integration::connected() ) {
			?>
			<div class="settings-container action-needed">
				<h1><?php esc_html_e( 'Action needed', 'peachpay-for-woocommerce' ); ?></h1>
				<hr />
				<br />
				<?php require PeachPay::get_plugin_path() . '/core/payments/authnet/admin/views/html-authnet-connect.php'; ?>
			</div>
			<?php
		}
	}

	/**
	 * Authorize.net gateways require Authorize.net to be connected in order to use.
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( self::needs_setup() ) {
			$is_available = false;
		}

		if ( ! PeachPay_Authnet_integration::connected() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Authorize.net gateways require setup if Authorize.net is not connected.
	 */
	public function needs_setup() {
		return ! PeachPay_Authnet_integration::connected();
	}

	/**
	 * Handles fetching the Authorize.net transaction URL
	 *
	 * The woocommerce plugin fetches the url from calling this function on the payment gateway.
	 *
	 * @param order $order Order object related to transaction.
	 * @return string URL linking the transaction Id with the Authorize.net merchant dashboard.
	 */
	public function get_transaction_url( $order ) {
		if ( ! $order->get_transaction_id() ) {
			return '';
		}

		$mode     = PeachPay_Authnet_Order_Data::get_peachpay( $order, 'authnet_mode' );
		$trans_id = PeachPay_Authnet_Order_Data::get_transaction_details( $order, 'transId' );
		$base_url = 'live' === $mode ? 'https://account.authorize.net' : 'https://sandbox.authorize.net';
		$path     = 'live' === $mode ? 'anet' : 'sandbox';
		$url      = $base_url . '/ui/themes/' . $path . '/transaction/transactiondetail.aspx?transID=' . $trans_id;

		return $url;
	}

	/**
	 * Gets the endpoint to callback the store for any payment related status changes.
	 */
	protected function get_callback_url() {
		return get_rest_url( null, 'peachpay/v1/authnet/webhook' );
	}

	/**
	 * Gets the bread crumbs to display on the Authorize.net gateway settings page.
	 */
	protected function get_settings_breadcrumbs() {
		return array(
			array(
				'name' => __( 'Payments', 'peachpay-for-woocommerce' ),
				'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '', false ),
			),
			array(
				'name' => __( 'Authorize.net', 'peachpay-for-woocommerce' ),
				'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '#' . strtolower( $this->payment_provider ), false ),
			),
			array(
				'name' => $this->title,
			),
		);
	}
}
