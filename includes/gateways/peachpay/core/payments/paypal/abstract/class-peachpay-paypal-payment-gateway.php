<?php
/**
 * Abstract PeachPay PayPal WC gateway.
 *
 * @PHPCS:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-payment-gateway.php';
require_once PEACHPAY_ABSPATH . 'core/payments/paypal/traits/trait-paypal-gateway-utilities.php';
require_once PEACHPAY_ABSPATH . 'core/payments/paypal/traits/trait-paypal-gateway-settings.php';

/**
 * .
 */
abstract class PeachPay_PayPal_Payment_Gateway extends PeachPay_Payment_Gateway {

	use PeachPay_PayPal_Gateway_Utilities;
	use PeachPay_PayPal_Gateway_Settings;

	public $payment_provider            = 'PayPal';
	protected $requires_verification_id = false;
	public $max_amount                  = 999999.99;
	public $currencies                  = array( 'USD', 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB' );

	/**
	 * .
	 */
	public function __construct() {
		$this->supports = array(
			'products',
			'refunds',
		);

		parent::__construct();
	}

	/**
	 * Registers the gateway as a feature for the PeachPay SDK. Override in child
	 * classes to add special metadata if needed.
	 *
	 * @param array $feature_list The list of features.
	 */
	public function register_feature( $feature_list ) {
		$feature_list[ $this->id . '_gateway' ] = array(
			'enabled'  => 'yes' === $this->enabled,
			'version'  => 1,
			'metadata' => array(
				'minimum_charge'       => floatval( $this->get_minimum_charge() ),
				// Checkout modal treats 0 as unrestricted
				'maximum_charge'       => INF === $this->get_maximum_charge() ? 0 : floatval( $this->get_maximum_charge() ),
				'supported_currencies' => $this->get_supported_currencies(),
				'supported_countries'  => $this->get_supported_countries(),
				'button_style'         => array(
					'layout' => wc_string_to_bool( $this->get_option( 'paypal_card_tagline' ) ) ? 'vertical' : 'horizontal',
					'color'  => $this->get_option( 'paypal_button_color' ),
					'shape'  => $this->get_option( 'paypal_button_shape' ),
					'label'  => $this->get_option( 'paypal_button_label' ),
					'height' => intval( $this->get_option( 'paypal_button_height' ) ),
				),
				'title'                => $this->get_title(),
			),
		);

		return $feature_list;
	}

	/**
	 * Handles fetching the PayPal transaction URL
	 *
	 * The woocommerce plugin fetches the url from calling this function on the payment gateway.
	 *
	 * @param WC_Order $order Order object related to transaction.
	 * @return string URL linking the transaction ID with the PayPal merchant dashboard.
	 */
	public function get_transaction_url( $order ) {
		if ( ! $order->get_transaction_id() ) {
			return '';
		}

		$mode = PeachPay_PayPal_Order_Data::get_peachpay( $order, 'paypal_mode' );
		return PeachPay_PayPal::dashboard_url( $mode, 'activity/payment', $order->get_transaction_id() );
	}

	/**
	 * If PayPal is not connected we should prompt the merchant to connect while viewing PayPal gateway.
	 */
	protected function action_needed_form() {
		if ( ! PeachPay_PayPal_Integration::connected() ) {
			?>
			<div class="settings-container action-needed">
				<h1><?php esc_html_e( 'Action needed', 'peachpay-for-woocommerce' ); ?></h1>
				<hr/>
				<br/>
				<?php
				require PeachPay::get_plugin_path() . '/core/payments/paypal/admin/views/html-paypal-connect.php';
				?>
			</div>
			<?php
		}
	}

	/**
	 * PayPal gateways require PayPal to be connected in order to use.
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( ! PeachPay_PayPal_Integration::connected() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Gets the bread crumbs to display on the PayPal gateway settings page.
	 */
	protected function get_settings_breadcrumbs() {
		return array(
			array(
				'name' => __( 'Payments', 'peachpay-for-woocommerce' ),
				'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '#' . strtolower( $this->payment_provider ), false ),
			),
			array(
				'name' => __( 'PayPal', 'peachpay-for-woocommerce' ),
				'url'  => PeachPay_PayPal_Advanced::get_url(),
			),
			array(
				'name' => $this->title,
			),
		);
	}

	/**
	 * Process the PeachPay PayPal Payment.
	 *
	 * @param int $order_id The id of the order.
	 */
	public function process_payment( $order_id ) {
		try {
			$paypal_mode = PeachPay_PayPal_Integration::mode();
			$order       = parent::process_payment( $order_id );

		// PHPCS:disable WordPress.Security.NonceVerification.Missing
			$session_id     = isset( $_POST['peachpay_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_session_id'] ) ) : null;
			$transaction_id = isset( $_POST['peachpay_transaction_id'] ) ? sanitize_text_field( wp_unslash( $_POST['peachpay_transaction_id'] ) ) : null;
		// PHPCS:enable

			PeachPay_PayPal_Order_Data::set_peachpay_details(
				$order,
				array(
					'session_id'     => $session_id,
					'transaction_id' => $transaction_id,
					'peachpay_mode'  => peachpay_is_test_mode() ? 'test' : 'live',
					'paypal_mode'    => $paypal_mode,
				)
			);

			$paypal_order_params =
			array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'reference_id' => $order->get_order_number(),
						'description'  => self::get_payment_description( $order ),
						'payee'        => array(
							'merchant_id' => PeachPay_PayPal_Integration::merchant_id(),
						),
						'amount'       => array(
							'currency_code' => $order->get_currency(),
							'value'         => PeachPay_PayPal::format_amount( $order->get_total(), $order->get_currency() ),
						),
					),
				),
				'payment_source' => array(
					'paypal' => array(
						'experience_context' => array(
							'brand_name'                => PeachPay_PayPal_Advanced::get_setting( 'store_name' ),
							'shipping_preference'       => $order->has_shipping_address() ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING',
							'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
							'user_action'               => 'PAY_NOW',
						),
					),
				),
			);

			if ( $order->has_shipping_address() ) {
				$paypal_order_params['purchase_units']['0']['shipping'] = self::get_paypal_shipping_address( $order );
			}

			if ( PeachPay_PayPal_Advanced::get_setting( 'itemized_order_details' ) === 'yes' ) {
				$paypal_order_params['purchase_units']['0']['items']               = self::get_paypal_order_line_items( $order );
				$paypal_order_params['purchase_units']['0']['amount']['breakdown'] = array(
					'discount'   => array(
						'currency_code' => $order->get_currency(),
						'value'         => PeachPay_PayPal::format_amount( $order->get_discount_total(), $order->get_currency() ),
					),
					'item_total' => array(
						'currency_code' => $order->get_currency(),
						'value'         => PeachPay_PayPal::format_amount( $order->get_subtotal() + $order->get_total_fees(), $order->get_currency() ),
					),
					'shipping'   => array(
						'currency_code' => $order->get_currency(),
						'value'         => PeachPay_PayPal::format_amount( $order->get_shipping_total(), $order->get_currency() ),
					),
					'tax_total'  => array(
						'currency_code' => $order->get_currency(),
						'value'         => PeachPay_PayPal::format_amount( $order->get_total_tax(), $order->get_currency() ),
					),
				);
			}

			$result = PeachPay_PayPal::create_order(
				$order,
				$paypal_order_params,
				$this->get_order_details( $order ),
				$paypal_mode
			);

			if ( ! $result ) {
				return null;
			}

			return array(
				'result'   => 'success',
				'redirect' => $this->paypal_order_frontend_response( $order, $result['id'] ),
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
	 * Process a PayPal refund.
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

			$result = PeachPay_PayPal::refund_payment(
				$order,
				array(
					'amount'        => array(
						'currency_code' => $order->get_currency(),
						'value'         => $amount,
					),
					'note_to_payer' => $reason ? $reason : null,
				)
			);

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
	 * PayPal gateways require setup if PayPal is not connected.
	 */
	public function needs_setup() {
		return ! PeachPay_PayPal_Integration::connected();
	}
}
