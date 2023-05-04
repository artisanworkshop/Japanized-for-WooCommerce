<?php
/**
 * Abstract PeachPay Square WC gateway.
 *
 * @PHPCS:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-payment-gateway.php';

/**
 * .
 */
abstract class PeachPay_Square_Payment_Gateway extends PeachPay_Payment_Gateway {

	public $payment_provider            = 'Square';
	protected $requires_verification_id = false;

	/**
	 * .
	 */
	public function __construct() {
		if ( ! $this->method_title ) {
			// translators: %s: gateway title
			$this->method_title = sprintf( __( '%s via Square (PeachPay)', 'peachpay-for-woocommerce' ), $this->title );
		}
		if ( ! $this->method_description ) {
			// translators: %s: gateway title
			$this->method_description = sprintf( __( 'Accept %s payments through Square', 'peachpay-for-woocommerce' ), $this->title );
		}

		$this->max_amount = 50000;
		$this->currencies = array( peachpay_square_currency() );
		$this->supports   = array(
			'products',
			'refunds',
		);

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
	 * Validates a PeachPay Square order
	 */
	public function validate_fields() {
		$result = parent::validate_fields();

		// PHPCS:disable WordPress.Security.NonceVerification.Missing
		$token_id        = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
		$source_id       = isset( $_POST['peachpay_square_source_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_square_source_id'] ) ) : null;
		$verification_id = isset( $_POST['peachpay_square_verification_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_square_verification_id'] ) ) : null;
		// PHPCS:enable

		if ( $this->supports( 'tokenization' ) && null !== $token_id && get_current_user_id() !== 0 ) {
			if ( 'new' !== $token_id ) {
				$token = WC_Payment_Tokens::get( $token_id );
				if ( $token->get_user_id() !== get_current_user_id() ) {
					// translators: %s the name of the field.
					wc_add_notice( sprintf( __( 'Invalid field "%s". Token does not belong to the logged in user.', 'peachpay-for-woocommerce' ), "wc-$this->id-payment-token" ), 'error' );
					$result = false;
				}

				return $result;
			}
		}

		if ( ! $source_id ) {
			wc_add_notice( __( 'Missing required field "peachpay_square_source_id"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		if ( ! $this->requires_verification_id ) {
			return $result;
		}

		if ( ! $verification_id ) {
			wc_add_notice( __( 'Missing required field "peachpay_square_verification_id"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		return $result;
	}

	/**
	 * Process the PeachPay Square Payment.
	 *
	 * @param int $order_id The id of the order.
	 */
	public function process_payment( $order_id ) {
		try {
			$square_mode = PeachPay_Square_Integration::mode();
			$order       = parent::process_payment( $order_id );

		// PHPCS:disable WordPress.Security.NonceVerification.Missing
			$session_id         = isset( $_POST['peachpay_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_session_id'] ) ) : null;
			$transaction_id     = isset( $_POST['peachpay_transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_transaction_id'] ) ) : null;
			$source_id          = isset( $_POST['peachpay_square_source_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_square_source_id'] ) ) : null;
			$verification_token = isset( $_POST['peachpay_square_verification_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_square_verification_id'] ) ) : null;
			$token_id           = isset( $_POST[ "wc-$this->id-payment-token" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-payment-token" ] ) ) : null;
			$save_to_account    = isset( $_POST[ "wc-$this->id-new-payment-method" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "wc-$this->id-new-payment-method" ] ) ) : null;
			$customer_id        = PeachPay_Square::get_customer( get_current_user_id() );
			$prepare_for_reuse  = false;
			$token              = null;

		// PHPCS:enable

			PeachPay_Square_Order_Data::set_peachpay_details(
				$order,
				array(
					'session_id'     => $session_id,
					'transaction_id' => $transaction_id,
					'peachpay_mode'  => peachpay_is_test_mode() ? 'test' : 'live',
					'square_mode'    => $square_mode,
				)
			);

			if ( $this->supports( 'tokenization' ) && get_current_user_id() !== 0 ) {
				if ( 'new' !== $token_id && null !== $token_id ) {
					$token = WC_Payment_Tokens::get( $token_id );
					if ( null !== $token ) {
						$source_id = $token->get_token();
						PeachPay_Square_Order_Data::set_reusable_payment_details( $order, $source_id, $customer_id );
						WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );
					}
				}
			}

			if ( get_current_user_id() !== 0 && $this->supports( 'subscriptions' ) ) {
				if ( $save_to_account || ( function_exists( 'peachpay_wcs_order_has_subscription' ) && peachpay_wcs_order_has_subscription( $order ) && is_null( $token ) ) ) {
					$prepare_for_reuse = true;
				}
			}

			$order_data      = $this->prepare_payment_result( $order );
			$payment_details = $this->prepare_payment_details( $source_id, $prepare_for_reuse, $verification_token, $customer_id );

			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				unset( $order_data['details']['billing']['email'] );
			}

			$json = PeachPay_Square::create_payment( $order, $order_data, $payment_details, $this->get_callback_url(), $square_mode );

			if ( ! $json['success'] ) {
				if ( ! is_null( $token_id ) && 'new' !== $token_id ) {
					// Token failed to make payment and needs to be removed.
					$token = WC_Payment_Tokens::get( $token_id );
					if ( ! is_null( $token ) ) {
						$token->delete();
					}
				}
				wc_add_notice( __( 'Payment error: ', 'peachpay-for-woocommerce' ) . $json['message'], 'error' );
				return null;
			}

			if ( isset( $json['data']['payment_id'] ) && 'no-payment-required' !== $json['data']['payment_id'] ) {
				$order->set_transaction_id( $json['data']['payment_id'] );
			}

			if ( 'COMPLETED' === $json['data']['status'] ) {
				$order->payment_complete();
			} elseif ( 'PENDING' === $json['data']['status'] ) {
				$order->set_status( 'on-hold', __( "Pending transfer from customer's bank account. This typically takes 3-5 business days.", 'peachpay-for-woocommerce' ) );
				$order->save();
			} else {
				wc_add_notice( __( 'Payment error: Square failed to process the transaction.', 'peachpay-for-woocommerce' ) );
				$order->set_status( 'failed' );
				$order->save();
				return null;
			}

			if ( isset( $json['data']['reuse'] ) ) {
				$reuse_json = $json['data']['reuse'];

				if ( $reuse_json['success'] && isset( $reuse_json['data'] ) ) {
					$reuse_data = $reuse_json['data'];
					PeachPay_Square_Order_Data::set_reusable_payment_details( $order, $reuse_data['source_id'], $reuse_data['customer_id'] );

					if ( $save_to_account ) {
						if ( $reuse_data['customer_id'] !== $customer_id ) {
							PeachPay_Square::unset_customer( get_current_user_id() );
							PeachPay_Square::set_customer( get_current_user_id(), $reuse_data['customer_id'] );
						}

						$this->create_payment_token( $order, $reuse_data );
					}
				} elseif ( isset( $reuse_json['reason'] ) ) {
					$reason = $reuse_json['reason'];
					switch ( $reason ) {
						case 'CUSTOMER_NOT_FOUND':
							PeachPay_Square::unset_customer( get_current_user_id() );
					}
				}
			}

			$result                    = $this->prepare_payment_result( $order );
			$result['payment_details'] = array(
				'type'       => $this->id,
				'status'     => 'success',
				'payment_id' => $json['data']['payment_id'],
			);

			return $result;
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
	 * Square gateways require setup if Square is not connected.
	 */
	public function needs_setup() {
		return ! peachpay_square_connected();
	}

	/**
	 * If Square is not connected we should prompt the merchant to connect while viewing any square gateway.
	 */
	protected function action_needed_form() {
		if ( ! peachpay_square_connected() ) {
			?>
			<div class="settings-container action-needed">
				<h1><?php esc_html_e( 'Action needed', 'peachpay-for-woocommerce' ); ?></h1>
				<hr/>
				<br/>
				<?php require PeachPay::get_plugin_path() . '/core/payments/square/admin/views/html-square-connect.php'; ?>
			</div>
			<?php
		}
	}

	/**
	 * Square gateways require square to be connected in order to use.x
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( ! peachpay_square_connected() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Enqueue native checkout scripts. Enqueueing data here will result in duplication so only enqueue static scripts here.
	 */
	public function enqueue_checkout_scripts() {
		if ( $this->get_option( 'enabled' ) === 'yes' ) {
			PeachPay::enqueue_script( 'peachpay-native-checkout-square-api-js', $this->square_script_src(), array(), false, true );
		}
	}

	/**
	 * .
	 */
	public function enqueue_admin_scripts() { }

	/**
	 * Gets the Square script src URL.
	 */
	public function square_script_src() {
		if ( peachpay_is_test_mode() || peachpay_is_local_development_site() || peachpay_is_staging_site() ) {
			return 'https://sandbox.web.squarecdn.com/v1/square.js';
		} else {
			return 'https://web.squarecdn.com/v1/square.js';
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
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			// translators: %s the order id.
			return new \WP_Error( 'wc_' . $order_id . '_refund_failed', sprintf( __( 'Refund error: The order_id %s did not match any orders.', 'peachpay-for-woocommerce' ), strval( $order_id ) ) );
		}

		try {
			if ( ! is_numeric( $amount ) || floatval( $amount ) <= 0 ) {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', __( 'Refund amount must be greater then 0', 'peachpay-for-woocommerce' ) );
			}

			$is_test_mode = 'true' === wc_get_order( $order_id )->get_meta( 'peachpay_is_test_mode' );
			$url          = peachpay_api_url( $is_test_mode ? 'test' : 'prod' ) . 'api/v1/square/refund';

			$data = array(
				'order_id'    => $order_id,
				'amount'      => floatval( $amount ),
				'reason'      => $reason,
				'merchant_id' => peachpay_plugin_merchant_id(),
				'currency'    => $order->get_currency(),
				'payment_id'  => $order->get_transaction_id(),
			);

			$params = array(
				'body'    => $data,
				'timeout' => 60,
			);

			$status = wp_remote_post( $url, $params );

			if ( is_wp_error( $status ) ) {
				return false;
			}

			$raw_response = wp_remote_retrieve_body( $status );
			$response     = json_decode( $raw_response, true );
			$order->add_order_note( $response['message'] );
			if ( $response['success'] ) {
				return true;
			} else {
				return new \WP_Error( 'wc_' . $order_id . '_refund_failed', $response['message'] );
			}
		} catch ( Exception $exception ) {
			$message = __( 'Error: ', 'peachpay-for-woocommerce' ) . $exception->getMessage();
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
	 * This is called for every renewal that was initially paid for with the peachpay square integration.
	 *
	 * @param WC_Order $parent_order The parent order.
	 * @param WC_Order $renewal_order The renewal order to create a payment for.
	 * @param float    $renewal_total The amount to charge the renewal for.
	 */
	public function process_subscription_renewal( $parent_order, $renewal_order, $renewal_total ) {
		try {
			if ( ! PeachPay_Square_Order_data::is_payment_reusable( $parent_order ) ) {
				$renewal_order->update_status( 'failed', __( 'Square reusable payment method details missing from subscription order.', 'peachpay-for-woocommerce' ) );
			}

			$peachpay_mode = PeachPay_Square_Order_Data::get_peachpay( $parent_order, 'peachpay_mode' );
			$square_mode   = PeachPay_Square_Order_Data::get_peachpay( $parent_order, 'square_mode' );
			$source_id     = PeachPay_Square_Order_Data::get_reusable_payment_id( $parent_order );
			$customer_id   = PeachPay_Square_Order_Data::get_payment_customer_id( $parent_order );
			$session_id    = 'off_' . PeachPay_Square_Order_Data::get_peachpay( $parent_order, 'session_id' );

			if ( is_null( $source_id ) || is_null( $customer_id ) ) {
				$renewal_order->update_status( 'failed', __( 'Square reusable payment method details missing from subscription order.', 'peachpay-for-woocommerce' ) );
				return;
			}

			$result = PeachPay_Payment::create_transaction( $renewal_order, $session_id, 'subscription-renewal', $peachpay_mode );
			if ( ! $result['success'] ) {
				$renewal_order->update_status( 'failed', $result['message'] );
				return null;
			}

			$order_data      = $this->prepare_payment_result( $renewal_order );
			$payment_details = $this->prepare_payment_details( $source_id, false, null, $customer_id, $square_mode );

			$result = PeachPay_Square::create_payment( $renewal_order, $order_data, $payment_details, $this->get_callback_url(), $square_mode );

			if ( ! $result['success'] ) {
				// translators: The payment method title, The failure reason
				$renewal_order->update_status( 'failed', sprintf( __( 'Square %1$s payment failed: %2$s', 'peachpay-for-woocommerce' ), $renewal_order->get_payment_method_title(), $result['message'] ) );
				return;
			}

			$renewal_order->set_transaction_id( $result['data']['payment_id'] );
			$renewal_order->payment_complete();
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
	 * Handles fetching the Square transaction URL
	 *
	 * The woocommerce plugin fetches the url from calling this function on the payment gateway.
	 *
	 * @param order $order Order object related to transaction.
	 * @return string URL linking the transaction ID with the Square merchant dashboard.
	 */
	public function get_transaction_url( $order ) {
		return peachpay_square_transaction_url( $order );
	}

	/**
	 * For tokenizable methods, tokenizes that method for furthur reuse.
	 * Upt to the individual payment method gateways to implement, all tokenizable
	 * methods should have an implementation.
	 *
	 * @param WC_Order $order The WC order.
	 * @param mixed    $reuse_data Reuse data object from payment create response.
	 */
	public function create_payment_token( $order, $reuse_data ) {
		$user_id      = $order->get_user_id();
		$card_details = $reuse_data['details'];
		$source_id    = $reuse_data['source_id'];
		if ( null === $source_id || null === $card_details ) {
			return;
		}

		$token = new WC_Payment_Token_PeachPay_Square_Card();
		$token->set_mode( PeachPay_Square_Order_Data::get_peachpay( $order, 'square_mode' ) );
		$token->set_gateway_id( $this->id );
		$token->set_user_id( $user_id );
		$token->set_card_type( $card_details['brand'] );
		$token->set_last4( $card_details['last_4'] );
		$token->set_expiry_month( $card_details['exp_month'] );
		$token->set_expiry_year( $card_details['exp_year'] );
		$token->set_token( $source_id );

		$token->save();

		WC_Payment_Tokens::set_users_default( get_current_user_id(), $token->get_id() );
	}

	/**
	 * Overrides default get_token behavior to only show tokens supported under the current square mode.
	 * This is because the users customer id is associated with square mode, and tokens are tied to a specific square customer id.
	 */
	public function get_tokens() {
		$tokens       = parent::get_tokens();
		$square_mode  = PeachPay_Square_Integration::mode();
		$valid_tokens = array();

		foreach ( $tokens as $token ) {
			$token_square_mode = method_exists( $token, 'get_mode' ) ? $token->get_mode( 'edit' ) : null;
			if ( null === $token_square_mode || $square_mode === $token_square_mode ) {
				$valid_tokens[] = $token;
			}
		}

		return $valid_tokens;
	}

	/**
	 * Creates the payment_details array used for create_payment()
	 *
	 * @param string $source_id payment source_id.
	 * @param bool   $prepare_reuse whether payment should be prepared for reuse.
	 * @param string $verification_token Optional token from verifyBuyer step.
	 * @param string $customer_id Payment method customer ID if attached to a square customer.
	 * @param string $mode Square mode for payment.
	 */
	public function prepare_payment_details( $source_id, $prepare_reuse = false, $verification_token = null, $customer_id = null, $mode = 'detect' ) {
		$payment_details = array(
			'source_id'     => $source_id,
			'prepare_reuse' => $prepare_reuse,
			'mode'          => PeachPay_Square_Integration::mode( $mode ),
		);

		if ( ! is_null( $customer_id ) ) {
			$payment_details['customer_id'] = $customer_id;
		}

		if ( ! is_null( $verification_token ) ) {
			$payment_details['verification_token'] = $verification_token;
		}

		return $payment_details;
	}

}
