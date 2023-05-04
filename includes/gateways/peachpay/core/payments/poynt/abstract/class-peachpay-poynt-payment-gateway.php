<?php
/**
 * Abstract PeachPay Poynt WC gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-payment-gateway.php';
require_once PEACHPAY_ABSPATH . 'core/payments/poynt/traits/trait-poynt-gateway-settings.php';
require_once PEACHPAY_ABSPATH . 'core/payments/poynt/traits/trait-poynt-gateway-utilities.php';

/**
 * .
 */
abstract class PeachPay_Poynt_Payment_Gateway extends PeachPay_Payment_Gateway {

	use PeachPay_Poynt_Gateway_Settings;
	use PeachPay_Poynt_Gateway_Utilities;

    //PHPCS:disable Squiz.Commenting.VariableComment.Missing
	public $payment_provider             = 'Poynt';
	public $min_max_currency             = 'USD';
	public $currencies                   = array( 'USD' );
	public $countries                    = array( 'US' );
	protected $poynt_payment_method_type = '';
    // PHPCS:enable

	/**
	 * .
	 */
	public function __construct() {
		if ( ! $this->method_title ) {
			// translators: %s: gateway title
			$this->method_title = sprintf( __( '%s via GoDaddy Poynt (PeachPay)', 'peachpay-for-woocommerce' ), $this->title );
		}
		if ( ! $this->method_description ) {
			// translators: %s: gateway title
			$this->method_description = sprintf( __( 'Accept %s payments through GoDaddy Poynt', 'peachpay-for-woocommerce' ), $this->title );
		}

		$this->supports[] = 'refunds';
		parent::__construct();

		// Subscription support.
		$gateway = $this;
		add_action(
			'woocommerce_scheduled_subscription_payment_' . $this->id,
			function( $renewal_total, $renewal_order ) use ( $gateway ) {
				$subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
				$subscription  = array_pop( $subscriptions );
				$parent_order  = wc_get_order( $subscription->get_parent_id() );

				$gateway->process_subscription_renewal( $parent_order, $renewal_order, $renewal_total );
			},
			10,
			2
		);
	}

	/**
	 * Validates the submitted POST fields on order submission.
	 */
	public function validate_fields() {
		$result = parent::validate_fields();

		// PHPCS:disable WordPress.Security.NonceVerification.Missing
		$wc_token_id = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
		$poynt_nonce = isset( $_POST['peachpay_poynt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_poynt_nonce'] ) ) : null;
		// PHPCS:enable

		if ( $this->supports( 'tokenization' ) && null !== $wc_token_id && get_current_user_id() !== 0 ) {
			if ( 'new' !== $wc_token_id ) {
				$wc_token = WC_Payment_Tokens::get( $wc_token_id );
				if ( $wc_token->get_user_id() !== get_current_user_id() ) {
					// translators: %s the name of the field.
					wc_add_notice( sprintf( __( 'Invalid field "%s". Token does not belong to the logged in user.', 'peachpay-for-woocommerce' ), "wc-$this->id-payment-token" ), 'error' );
					$result = false;
				}

				// If the gateway supports tokenization and a token is present we should skip validating the payment nonce because it will not be used.
				return $result;
			}
		}

		if ( ! $poynt_nonce ) {
			wc_add_notice( __( 'Missing required field "peachpay_poynt_nonce"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		return $result;
	}

	/**
	 * Process the PeachPay Poynt Payment.
	 *
	 * @param int $order_id The id of the order.
	 */
	public function process_payment( $order_id ) {
		$poynt_mode = PeachPay_Poynt_Integration::mode();
		$order      = parent::process_payment( $order_id );

		// PHPCS:disable WordPress.Security.NonceVerification.Missing
		$session_id     = isset( $_POST['peachpay_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_session_id'] ) ) : null;
		$transaction_id = isset( $_POST['peachpay_transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_transaction_id'] ) ) : null;

		$poynt_nonce = isset( $_POST['peachpay_poynt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_poynt_nonce'] ) ) : null;
		$poynt_token = null;

		$wc_token    = null;
		$wc_token_id = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
		// PHPCS:enable

		PeachPay_Poynt_Order_Data::set_peachpay_details(
			$order,
			array(
				'session_id'     => $session_id,
				'transaction_id' => $transaction_id,
				'peachpay_mode'  => peachpay_is_test_mode() ? 'test' : 'live',
				'poynt_mode'     => $poynt_mode,
			)
		);

		if ( $this->supports( 'tokenization' ) && null !== $wc_token_id && 'new' !== $wc_token_id && get_current_user_id() !== 0 ) {
			$wc_token = WC_Payment_Tokens::get( $wc_token_id );
			if ( null !== $wc_token ) {
				$poynt_token = $wc_token->get_token();
			}
		}

		// This will take care of payments that require a payment method initially but no actual payment. (Ex: A subscription free trial)
		if ( 0.0 === floatval( $order->get_total() ) ) {
			return $this->process_zero_total_payment( $order, $poynt_nonce, $wc_token );
		}

		$charge_options = array(
			'requestId'  => wp_generate_uuid4(),
			'businessId' => PeachPay_Poynt_Integration::business_id(),
			'action'     => $this->get_option( 'transaction_action', 'SALE' ),
			'authOnly'   => 'AUTHORIZE' === $this->get_option( 'transaction_action' ),
			'amounts'    => array(
				'currency'          => $order->get_currency(),
				'transactionAmount' => PeachPay_Poynt::format_amount( $order->get_total(), $order->get_currency() ),
				'orderAmount'       => PeachPay_Poynt::format_amount( $order->get_total(), $order->get_currency() ),
			),
			'notes'      => $this->get_payment_description( $order ),
			'sourceApp'  => 'PeachPay for WooCommerce/' . PEACHPAY_VERSION,
		);

		if ( $poynt_nonce ) {
			$charge_options['nonce'] = $poynt_nonce;
		} elseif ( $poynt_token ) {
			$charge_options['token'] = $poynt_token;
		} else {
			return null;
		}

		$this->set_charge_billing( $charge_options, $order );
		$this->set_charge_shipping( $charge_options, $order );
		$this->set_charge_email_receipt( $charge_options, $order );

		$result = PeachPay_Poynt::create_payment( $order, $charge_options, $this->get_order_details( $order ) );
		if ( ! $result ) {
			return null;
		}

		$this->maybe_create_payment_token( $order );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_order_received_url(),
		);
	}

	/**
	 * This is called for every renewal that was initially paid for with the peachpay poynt integration.
	 *
	 * @param WC_Order $parent_order The parent order.
	 * @param WC_Order $renewal_order The renewal order to create a payment for.
	 * @param float    $renewal_total The amount to charge the renewal for.
	 */
	public function process_subscription_renewal( $parent_order, $renewal_order, $renewal_total ) {
		$peachpay_mode = PeachPay_Poynt_Order_Data::get_peachpay( $parent_order, 'peachpay_mode' );
		$session_id    = PeachPay_Poynt_Order_Data::get_peachpay( $parent_order, 'session_id' );

		$result = PeachPay_Payment::create_transaction( $renewal_order, $session_id, 'subscription-renewal', $peachpay_mode );
		if ( ! $result['success'] ) {
			$renewal_order->update_status( 'failed', $result['message'] );
			return null;
		}

		PeachPay_Poynt_Order_Data::set_peachpay_details(
			$renewal_order,
			array(
				'session_id'     => PeachPay_Poynt_Order_Data::get_peachpay( $renewal_order, 'session_id' ),
				'transaction_id' => PeachPay_Poynt_Order_Data::get_peachpay( $renewal_order, 'transaction_id' ),
				'peachpay_mode'  => PeachPay_Poynt_Order_Data::get_peachpay( $parent_order, 'peachpay_mode' ),
				'poynt_mode'     => PeachPay_Poynt_Order_Data::get_peachpay( $parent_order, 'poynt_mode' ),
			)
		);

		$charge_options = array(
			'requestId'  => wp_generate_uuid4(),
			'businessId' => PeachPay_Poynt_Integration::business_id(),
			'token'      => PeachPay_Poynt_Order_Data::get_token( $parent_order, 'token' ),
			'action'     => $this->get_option( 'transaction_action', 'SALE' ),
			'authOnly'   => 'AUTHORIZE' === $this->get_option( 'transaction_action' ),
			'amounts'    => array(
				'currency'          => $renewal_order->get_currency(),
				'transactionAmount' => PeachPay_Poynt::format_amount( $renewal_total, $renewal_order->get_currency() ),
				'orderAmount'       => PeachPay_Poynt::format_amount( $renewal_total, $renewal_order->get_currency() ),
			),
			'notes'      => $this->get_payment_description( $renewal_order ),
			'sourceApp'  => 'PeachPay for WooCommerce/' . PEACHPAY_VERSION,

		);

		$this->set_charge_billing( $charge_options, $renewal_order );
		$this->set_charge_shipping( $charge_options, $renewal_order );
		$this->set_charge_email_receipt( $charge_options, $renewal_order );

		$result = PeachPay_Poynt::create_payment(
			$renewal_order,
			$charge_options,
			$this->get_order_details( $renewal_order )
		);
	}

	/**
	 * Handles scenarios when an order does not initially charge for a payment.
	 *
	 * @param WC_Order         $order The free order.
	 * @param string           $poynt_nonce The payment nonce if present.
	 * @param WC_Payment_Token $wc_token The payment token if present.
	 */
	public function process_zero_total_payment( $order, $poynt_nonce, $wc_token ) {
		if ( $poynt_nonce ) {
			$result = PeachPay_Poynt::create_token( $order, $poynt_nonce );
			if ( ! $result || ! $result['token_details'] ) {
				return null;
			}

			PeachPay_Poynt_Order_data::set_token( $order, array( 'token' => $result['token_details'] ) );
		} elseif ( $wc_token ) {
			$order->add_payment_token( $wc_token );
			$order->save();

			PeachPay_Poynt_Order_data::set_token( $order, array( 'token' => $wc_token->get_token() ) );
		}

		$order->payment_complete();

		// translators: %1$s Payment method title, %2$s The payment method id
		$order->add_order_note( sprintf( __( 'GoDaddy Poynt %1$s payment setup for future use.', 'peachpay-for-woocommerce' ), $order->get_payment_method_title() ) );
		$order->save();

		PeachPay_Payment::update_transaction( $order, $this->get_order_details( $order ), 'SETUP' );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_order_received_url(),
		);
	}

	/**
	 * Process a Poynt refund.
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

		if ( ! is_numeric( $amount ) || floatval( $amount ) <= 0 ) {
			return new \WP_Error( 'wc_' . $order_id . '_refund_failed', __( 'Refund error: Amount must be greater then 0', 'peachpay-for-woocommerce' ) );
		}

		$result = PeachPay_Poynt::refund_payment( $order, $amount );

		if ( ! $result['success'] ) {
			return new \WP_Error( 'wc_' . $order_id . '_refund_failed', 'Refund error:' . $result['message'] );
		}

		return ( filter_var( $result['success'], FILTER_VALIDATE_BOOLEAN ) );
	}

	/**
	 * If Poynt is not connected we should prompt the merchant to connect while viewing any Poynt gateway.
	 */
	protected function action_needed_form() {
		if ( ! PeachPay_Poynt_Integration::connected() ) {
			?>
			<div class="settings-container action-needed">
				<h1><?php esc_html_e( 'Action needed', 'peachpay-for-woocommerce' ); ?></h1>
				<hr/>
				<br/>
				<?php
				require PeachPay::get_plugin_path() . '/core/payments/poynt/admin/views/html-poynt-connect.php';
				?>
			</div>
			<?php
		}
	}

	/**
	 * Poynt gateways require Poynt to be connected in order to use.
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( ! PeachPay_Poynt_integration::connected() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Poynt gateways require setup if Poynt is not connected.
	 */
	public function needs_setup() {
		return ! PeachPay_Poynt_integration::connected();
	}

	/**
	 * Gets the endpoint to callback the store for any payment related status changes.
	 */
	protected function get_callback_url() {
		return get_rest_url( null, 'peachpay/v1/poynt/webhook' );
	}

	/**
	 * Enqueue Poynt Collect scripts into native checkout.
	 */
	public function enqueue_checkout_scripts() {
		PeachPay::enqueue_script( 'poynt-core', PeachPay_Poynt_Integration::poynt_script_src(), array(), false, true );
	}

	/**
	 * Creates a tokenized payment method for the gateway. Override
	 * in child classes.
	 *
	 * @param WC_Order $order The WC order.
	 */
	public function create_payment_token( $order ) {}
}
