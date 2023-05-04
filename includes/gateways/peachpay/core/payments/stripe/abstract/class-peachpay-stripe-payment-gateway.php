<?php
/**
 * Abstract PeachPay Stripe WC gateway.
 *
 * @PHPCS:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-payment-gateway.php';
require_once PEACHPAY_ABSPATH . 'core/payments/stripe/traits/trait-stripe-gateway-utilities.php';

/**
 * .
 */
abstract class PeachPay_Stripe_Payment_Gateway extends PeachPay_Payment_Gateway {

	use PeachPay_Stripe_Gateway_Utilities;

	public $payment_provider                         = 'Stripe';
	public $min_amount                               = 0.50;
	public $max_amount                               = 999999.99;
	public $min_max_currency                         = 'USD';
	public $currencies                               = PeachPay_Stripe::SUPPORTED_CURRENCIES;
	protected $stripe_payment_method_type            = '';
	protected $stripe_payment_method_capability_type = '';

	/**
	 * .
	 */
	public function __construct() {
		if ( ! $this->method_title ) {
			// translators: %s: gateway title
			$this->method_title = sprintf( __( '%s via Stripe (PeachPay)', 'peachpay-for-woocommerce' ), $this->title );
		}
		if ( ! $this->method_description ) {
			// translators: %s: gateway title
			$this->method_description = sprintf( __( 'Accept %s payments through Stripe', 'peachpay-for-woocommerce' ), $this->title );
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

		add_filter( 'woocommerce_gateway_title', array( $this, 'handle_payment_method_title_filter' ), 10, 2 );
	}

	/**
	 * Adds the capture method setting to the gateway settings.
	 *
	 * @param array $form_fields The existing gateway settings.
	 */
	protected static function capture_method_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'capture_method' => array(
					'title'       => __( 'Charge type', 'peachpay-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'This option determines if the customers funds are captured immediately or only authorized for capture at a later time. Authorized payments expire and cannot be captured after 7 days.', 'peachpay-for-woocommerce' ),
					'default'     => 'automatic',
					'options'     => array(
						'automatic' => __( 'Capture', 'peachpay-for-woocommerce' ),
						'manual'    => __( 'Authorize', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}

	/**
	 * Validates a PeachPay Stripe order
	 */
	public function validate_fields() {
		$result = parent::validate_fields();

		// PHPCS:disable WordPress.Security.NonceVerification.Missing
		$token_id          = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
		$payment_method_id = isset( $_POST['peachpay_stripe_payment_method_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_stripe_payment_method_id'] ) ) : null;
		// PHPCS:enable

		if ( $this->supports( 'tokenization' ) && null !== $token_id && get_current_user_id() !== 0 ) {
			if ( 'new' !== $token_id ) {
				$token = WC_Payment_Tokens::get( $token_id );
				if ( $token->get_user_id() !== get_current_user_id() ) {
					// translators: %s the name of the field.
					wc_add_notice( sprintf( __( 'Invalid field "%s". Token does not belong to the logged in user.', 'peachpay-for-woocommerce' ), "wc-$this->id-payment-token" ), 'error' );
					$result = false;
				}

				// If the gateway supports tokenization and a token is present we should skip validating the payment method id because it will not be used.
				return $result;
			}
		}

		if ( ! $payment_method_id ) {
			wc_add_notice( __( 'Missing required field "peachpay_stripe_payment_method_id"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		return $result;
	}

	/**
	 * Process the PeachPay Stripe Payment.
	 *
	 * @param int $order_id The id of the order.
	 */
	public function process_payment( $order_id ) {
		try {
			$stripe_mode = PeachPay_Stripe_Integration::mode();
			$order       = parent::process_payment( $order_id );

            // PHPCS:disable WordPress.Security.NonceVerification.Missing
			$session_id        = isset( $_POST['peachpay_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_session_id'] ) ) : null;
			$transaction_id    = isset( $_POST['peachpay_transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_transaction_id'] ) ) : null;
			$payment_method_id = isset( $_POST['peachpay_stripe_payment_method_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_stripe_payment_method_id'] ) ) : null;
			$token_id          = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
			$save_to_account   = isset( $_POST[ "wc-$this->id-new-payment-method" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-new-payment-method" ] ) ) : null;
            // PHPCS:enable

			PeachPay_Stripe_Order_Data::set_peachpay_details(
				$order,
				array(
					'session_id'     => $session_id,
					'transaction_id' => $transaction_id,
					'peachpay_mode'  => peachpay_is_test_mode() ? 'test' : 'live',
					'stripe_mode'    => $stripe_mode,
				)
			);

			if ( $this->supports( 'tokenization' ) && null !== $token_id && 'new' !== $token_id && get_current_user_id() !== 0 ) {
				$token = WC_Payment_Tokens::get( $token_id );
				if ( null !== $token ) {
					$payment_method_id = $token->get_token();
				}
			}

			// This will take care of payments that require a payment method initially but no actual payment. (Ex: A subscription free trial)
			if ( 0.0 === floatval( $order->get_total() ) ) {
				return $this->process_zero_total_payment( $order, $payment_method_id );
			}

			$payment_intent_params = array(
				'confirm'                     => $this->confirm_payment(),
				'amount'                      => PeachPay_Stripe::format_amount( $order->get_total(), $order->get_currency() ),
				'currency'                    => $order->get_currency(),
				'description'                 => $this->get_payment_intent_description( $order ),
				'customer'                    => $this->get_stripe_customer( get_current_user_id() ),
				'payment_method'              => $payment_method_id,
				'payment_method_types'        => array( $this->stripe_payment_method_type ),
				'setup_future_usage'          => $this->setup_future_usage(),
				'statement_descriptor'        => peachpay_truncate_str( wp_parse_url( get_site_url(), PHP_URL_HOST ), 22 ),
				'statement_descriptor_suffix' => is_string( $this->statement_descriptor_suffix ) ? peachpay_truncate_str( $this->statement_descriptor_suffix, 22 ) : null,
				'capture_method'              => $this->capture_method,
				'payment_method_options'      => $this->payment_method_options(),
				'mandate_data'                => $this->mandate_data( $order, 'online' ),
			);

			if ( $order->has_shipping_address() ) {
				$payment_intent_params['shipping'] = array(
					'address' => array(
						'city'        => $order->get_shipping_city(),
						'country'     => $order->get_shipping_country(),
						'line1'       => $order->get_shipping_address_1(),
						'line2'       => $order->get_shipping_address_2(),
						'postal_code' => $order->get_shipping_postcode(),
						'state'       => $order->get_shipping_state(),
					),
					'name'    => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
					'phone'   => $order->get_shipping_phone(),
				);
			}

			$result = PeachPay_Stripe::create_payment(
				$order,
				$payment_intent_params,
				$this->get_order_details( $order ),
				$stripe_mode
			);

			if ( ! $result ) {
				return null;
			}

			// Save payment method token to account if customer selected the save to account checkbox.
			if ( $this->supports( 'tokenization' ) && get_current_user_id() !== 0 ) {
				if ( 'true' === $save_to_account && ( 'new' === $token_id || null === $token_id ) ) {
					$this->create_payment_token( $order );
				}
			}

			self::set_payment_method_title( $order );
			PeachPay_Stripe::set_customer( get_current_user_id(), PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'customer' ) );

			return array(
				'result'   => 'success',
				'redirect' => $this->payment_intent_frontend_response( $order, $result['client_secret'] ),
			);
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
	 * This is called for every renewal that was initially paid for with the peachpay stripe integration.
	 *
	 * @param WC_Order $parent_order The parent order.
	 * @param WC_Order $renewal_order The renewal order to create a payment for.
	 * @param float    $renewal_total The amount to charge the renewal for.
	 */
	public function process_subscription_renewal( $parent_order, $renewal_order, $renewal_total ) {
		try {
			$stripe_mode       = PeachPay_Stripe_Order_Data::get_peachpay( $parent_order, 'stripe_mode' );
			$peachpay_mode     = PeachPay_Stripe_Order_Data::get_peachpay( $parent_order, 'peachpay_mode' );
			$payment_method_id = PeachPay_Stripe_Order_Data::get_payment_method( $parent_order, 'id' );
			$customer_id       = PeachPay_Stripe_Order_Data::get_payment_method( $parent_order, 'customer' );
			$session_id        = 'off_' . PeachPay_Stripe_Order_Data::get_peachpay( $parent_order, 'session_id' );

			$result = PeachPay_Payment::create_transaction( $renewal_order, $session_id, 'subscription-renewal', $peachpay_mode );
			if ( ! $result['success'] ) {
				$renewal_order->update_status( 'failed', $result['message'] );
				return null;
			}

			$payment_intent_params = array(
				'confirm'                     => true,
				'amount'                      => PeachPay_Stripe::format_amount( $renewal_total, $renewal_order->get_currency() ),
				'currency'                    => $renewal_order->get_currency(),
				'description'                 => $this->get_payment_intent_description( $renewal_order, true ),
				'customer'                    => $customer_id,
				'payment_method'              => $payment_method_id,
				'payment_method_types'        => array( $this->stripe_payment_method_type ),
				'statement_descriptor'        => peachpay_truncate_str( wp_parse_url( get_site_url(), PHP_URL_HOST ), 22 ),
				'setup_future_usage'          => null,
				'statement_descriptor_suffix' => empty( $this->statement_descriptor_suffix ) ? null : peachpay_truncate_str( $this->statement_descriptor_suffix, 22 ),
				'capture_method'              => $this->capture_method,
				'payment_method_options'      => $this->payment_method_options(),
				'mandate_data'                => $this->mandate_data( $renewal_order, 'offline' ),
			);

			if ( $renewal_order->has_shipping_address() ) {
				$payment_intent_params['shipping'] = array(
					'address' => array(
						'city'        => $renewal_order->get_shipping_city(),
						'country'     => $renewal_order->get_shipping_country(),
						'line1'       => $renewal_order->get_shipping_address_1(),
						'line2'       => $renewal_order->get_shipping_address_2(),
						'postal_code' => $renewal_order->get_shipping_postcode(),
						'state'       => $renewal_order->get_shipping_state(),
					),
					'name'    => $renewal_order->get_shipping_first_name() . ' ' . $renewal_order->get_shipping_last_name(),
					'phone'   => $renewal_order->get_shipping_phone(),
				);
			}

			$result = PeachPay_Stripe::create_payment(
				$renewal_order,
				$payment_intent_params,
				$this->get_order_details( $renewal_order ),
				$stripe_mode
			);
		} catch ( Exception $exception ) {
			$message = __( 'Error: ', 'peachpay-for-woocommerce' ) . $exception->getMessage();
			$renewal_order->add_order_note( $message );

			PeachPay_Payment::update_transaction(
				$renewal_order,
				array(
					'order_details' => $this->get_order_details( $renewal_order ),
					'note'          => $message,
				)
			);

			return null;
		}
	}

	/**
	 * Handles scenarios when an order does not initially charge for a payment.
	 *
	 * @param WC_Order $order The free order.
	 * @param string   $payment_method_id The payment method id for the order.
	 */
	public function process_zero_total_payment( $order, $payment_method_id ) {
		$payment_method_details = null;
		$setup_intent_details   = null;
		if ( WC()->session && WC()->session->get( 'peachpay_setup_intent_details' ) ) {
			$session_data = WC()->session->get( 'peachpay_setup_intent_details' );

			$setup_intent_details   = $session_data['setup_intent_details'];
			$payment_method_details = $session_data['payment_method_details'];
		} else {
			wc_add_notice( __( 'Stripe setup intent session data is missing or not defined.', 'peachpay-for-woocommerce' ), 'error' );
			return null;
		}

		if ( ! $payment_method_details ) {
			$payment_method_details = array(
				'id'       => $payment_method_id,
				'type'     => $this->stripe_payment_method_type,
				'mode'     => $setup_intent_details['mode'],
				'customer' => $setup_intent_details['customer'],
			);
		}

		PeachPay_Stripe_Order_Data::set_payment_method_details( $order, $payment_method_details );
		$this::set_payment_method_title( $order );

		$order->payment_complete();

		// translators: %1$s Payment method title, %2$s The payment method id
		$order->add_order_note( sprintf( __( 'Stripe %1$s payment setup for future use (Payment Method Id: %2$s)', 'peachpay-for-woocommerce' ), $order->get_payment_method_title(), $payment_method_id ) );
		$order->save();

		// Clear information stored on session because we do not need it anymore.
		unset( WC()->session->{'peachpay_setup_intent_details'} );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_order_received_url(),
		);
	}

	/**
	 * Process a Stripe refund.
	 *
	 * @param  int        $order_id Order ID.
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

			$amount = PeachPay_Stripe::format_amount( $amount, $order->get_currency() );
			$result = PeachPay_Stripe::refund_payment( $order, $amount );

			if ( ! $result['success'] ) {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', 'Refund error:' . $result['message'] );
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
	 * If Stripe is not connected we should prompt the merchant to connect while viewing any Stripe gateway.
	 */
	protected function action_needed_form() {
		if ( ! PeachPay_Stripe_Integration::connected() ) {
			?>
			<div class="settings-container action-needed">
				<h1><?php esc_html_e( 'Action needed', 'peachpay-for-woocommerce' ); ?></h1>
				<hr/>
				<br/>
				<?php
				require PeachPay::get_plugin_path() . '/core/payments/stripe/admin/views/html-stripe-connect.php';
				?>
			</div>
			<?php
		} elseif ( 'active' !== PeachPay_Stripe_Integration::is_capable( $this->stripe_payment_method_capability_type . '_payments' ) ) {
			?>
			<div class="settings-container action-needed">
				<h1><?php esc_html_e( 'Action needed', 'peachpay-for-woocommerce' ); ?></h1>
				<hr/>
				<br/>
				<?php
				//phpcs:ignore
				echo sprintf( __( "This payment method must be activated in your %1\$s Stripe dashboard %2\$s settings to be used in the PeachPay checkout", "peachpay-for-woocommerce" ), '<a target="_blank" href="https://dashboard.stripe.com/test/settings/payment_methods">', "</a>" )
				?>
			</div>
			<?php
		}
	}

	/**
	 * Stripe gateways require Stripe to be connected in order to use.
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( ! PeachPay_Stripe_Integration::connected() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Stripe gateways require setup if Stripe is not connected.
	 */
	public function needs_setup() {
		return ! PeachPay_Stripe_Integration::connected() || 'active' !== PeachPay_Stripe_Integration::is_capable( $this->stripe_payment_method_capability_type . '_payments' );
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
		if ( ! $order->get_transaction_id() ) {
			return '';
		}

		return PeachPay_Stripe::dashboard_url(
			PeachPay_Stripe_Order_Data::get_payment_intent( $order, 'mode' ),
			null,
			'payments/' . $order->get_transaction_id(),
			$order->get_transaction_id(),
			false
		);
	}

	/**
	 * Gets the endpoint to callback the store for any payment related status changes.
	 */
	protected function get_callback_url() {
		return get_rest_url( null, 'peachpay/v1/stripe/webhook' );
	}

	/**
	 * Adds a Stripe card payment method to the gateway.
	 *
	 * @param WC_Order $order The WC order.
	 */
	public function create_payment_token( $order ) {}

	/**
	 * Overrides the payment method title on the order dashboard page. Override in child classes.
	 *
	 * @param string $title The payment method title.
	 * @param string $id The id of the gateway.
	 */
	public function handle_payment_method_title_filter( $title, $id ) {
		global $post;

		if ( ! is_object( $post ) ) {
			return $title;
		}

		$order = wc_get_order( $post->ID );

		if ( ! $order ) {
			return $title;
		}

		if ( $id !== $order->get_payment_method() ) {
			return $title;
		}

		return $order->get_payment_method_title();
	}

	/**
	 * Gets the formatted payment method title for an order. Override in child classes
	 *
	 * @param WC_Order $order The order to get the payment method title for.
	 */
	public static function set_payment_method_title( $order ) {}

	/**
	 * Whether to confirm the payment immediately or not. By default the payment is never confirmed
	 *  immediately. Override in child classes to change this.
	 */
	protected function confirm_payment() {
		return null;
	}

	/**
	 * Returns the setup_future_usage value (i.e. off_session, on_session, null), defaults to 'off_session'
	 */
	protected function setup_future_usage() {
		return 'off_session';
	}

	/**
	 * Information about the Bank payment(ACH,...) mandate. By default no data is supplied.
	 *
	 * @param WC_Order $order The WC order to create the mandate data for.
	 * @param string   $type The type of mandate.
	 */
	protected function mandate_data( $order, $type = 'online' ) {
		return null;
	}

	/**
	 * Gets the current stripe customer during checkout.
	 *
	 * @param int $user_id The logged in user customer id.
	 */
	private function get_stripe_customer( $user_id ) {
		$stripe_customer = PeachPay_Stripe::get_customer( $user_id );
		if ( null !== $stripe_customer ) {
			return $stripe_customer;
		}

		if ( ! WC()->session ) {
			return null;
		}

		$data = WC()->session->get( 'peachpay_setup_intent_details' );
		if ( ! $data ) {
			return null;
		}

		return $data['setup_intent_details']['customer'];
	}
}
