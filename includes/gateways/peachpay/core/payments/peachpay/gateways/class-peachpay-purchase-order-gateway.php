<?php
/**
 * PeachPay purchase order gateway.
 *
 * @phpcs:disable WordPress.Security.NonceVerification.Missing
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-payment-gateway.php';

/**
 * This class allows us to submit orders with the PeachPay Purchase Order gateway.
 */
class PeachPay_Purchase_Order_Gateway extends PeachPay_Payment_Gateway {
	/**
	 * Default constructor.
	 */
	public function __construct() {

		$this->id                    = 'peachpay_purchase_order';
		$this->title                 = __( 'Purchase Order', 'peachpay-for-woocommerce' );
		$this->description           = '';
		$this->payment_provider      = 'PeachPay';
		$this->payment_method_family = __( 'Purchase Order', 'peachpay-for-woocommerce' );

		$this->method_title       = __( 'Purchase Order (PeachPay)', 'peachpay-for-woocommerce' );
		$this->method_description = __( 'Accept purchase order payments through PeachPay', 'peachpay-for-woocommerce' );

		// Reason for center placement rather than the bottom (should eventually be at the top):
		// - Form fields needs to add to the default fields from the parent
		parent::__construct();

		$this->placeholder = $this->get_option( 'field_name', __( 'Purchase order', 'peachpay-for-woocommerce' ) );

		$this->form_fields = array_merge(
			$this->form_fields,
			array(
				'field_name'  => array(
					'title'       => __( 'Field name', 'peachpay-for-woocommerce' ),
					'description' => __( 'Customize the field name for Purchase Order. Leaving it blank defaults it to "Purchase Order" in your chosen language.', 'peachpay-for-woocommerce' ),
					'type'        => 'text',
					'default'     => __( 'Purchase order', 'peachpay-for-woocommerce' ),
					'placeholder' => __( 'Purchase order', 'peachpay-for-woocommerce' ),
				),
				'description' => array(
					'title'       => __( 'Description', 'peachpay-for-woocommerce' ),
					'description' => __( 'Customize a description to be displayed under the Purchase Order payment option. This will be left blank by default.', 'peachpay-for-woocommerce' ),
					'type'        => 'textarea',
					'default'     => '',
				),
			)
		);
	}

	/**
	 * Registers the gateway as a feature for the PeachPay SDK.
	 *
	 * @param array $feature_list The list of features.
	 * @return array updated feature list
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
				'field_name'           => $this->get_option( 'field_name', $this->title ),
				'description'          => $this->get_option( 'description', '' ),
			),
		);

		return $feature_list;
	}

	/**
	 * Renders payment fields.
	 */
	public function payment_fields() {
		$this->payment_field_test_mode_notice();
		$this->payment_field_tokenize_error_notice();
		?>
			<div class='payment_method_peachpay_purchase_order'>
				<label><?php echo esc_html( $this->get_option( 'description', '' ) ); ?></label>
				<input type='text' name='purchase_order_number' placeholder='<?php echo esc_html( $this->placeholder ); ?>' required>
			</div>
		<?php
		$this->payment_field_powered_by_notice();
	}

	/**
	 * Validation
	 *
	 * @return bool form valid flag
	 */
	public function validate_fields() {
		$result = parent::validate_fields();

		$purchase_order_number = null;
		if ( isset( $_POST['purchase_order_number'] ) ) {
			$purchase_order_number = sanitize_text_field( wp_unslash( $_POST['purchase_order_number'] ) );
		}

		if ( ! $purchase_order_number ) {
			wc_add_notice( __( 'Missing required field "purchase_order_number"', 'peachpay-for-woocommerce' ), 'error' );
			return false;
		}

		return $result;
	}

	/**
	 * Processes payment for Purchase Order orders.
	 *
	 * @param int $order_id order id.
	 * @return array result.
	 */
	public function process_payment( $order_id ) {
		try {
			$order = parent::process_payment( $order_id );

			if ( isset( $_POST['purchase_order_number'] ) && isset( $_POST['peachpay_session_id'] ) && isset( $_POST['peachpay_transaction_id'] ) ) {
				$purchase_order_number   = sanitize_key( wp_unslash( $_POST['purchase_order_number'] ) );
				$peachpay_session_id     = sanitize_key( wp_unslash( $_POST['peachpay_session_id'] ) );
				$peachpay_transaction_id = sanitize_key( wp_unslash( $_POST['peachpay_transaction_id'] ) );

				$order->set_transaction_id( $purchase_order_number );
				add_post_meta( $order_id, 'Purchase Order #', $purchase_order_number );
			} else {
				$order->delete( true );
				return array( 'result' => 'failure' );
			}

			// Mark on-hold for the store to resolve.
			// translators: %s: purchase order number
			$order->update_status( 'on-hold', sprintf( __( 'Awaiting Purchase Order (%s) completion.', 'peachpay-for-woocommerce' ), $purchase_order_number ) );

			$order->save();

			PeachPay_Payment::update_transaction_purchase_order( $order, $peachpay_session_id, $peachpay_transaction_id, $purchase_order_number );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
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
	 * There is no icon.
	 *
	 * @param $boolean $flex This is unused.
	 *
	 * @return string empty string
	 */
	public function get_icon( $flex = false ) {
		return '';
	}
}
