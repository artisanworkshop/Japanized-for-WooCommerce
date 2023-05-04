<?php
/**
 * Token class for PeachPay stripe Achdebit payment method.
 *
 * @PHPCS:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package PeachPay
 */

( defined( 'PEACHPAY_ABSPATH' ) && defined( 'WC_ABSPATH' ) ) || exit;

require_once WC_ABSPATH . 'includes/payment-tokens/class-wc-payment-token-echeck.php';

/**
 * .
 */
class WC_Payment_Token_PeachPay_Stripe_Achdebit extends WC_Payment_Token_ECheck {

	/**
	 * Token Type String.
	 *
	 * @var string
	 */
	protected $type = 'peachpay_stripe_achdebit';

	/**
	 * Stores eCheck payment token data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'last4'      => '',
		'bank'       => '',
		'mode'       => '',
		'connect_id' => '',
	);

		/**
		 * Hook prefix
		 *
		 * @since 3.0.0
		 */
	protected function get_hook_prefix() {
		return 'woocommerce_payment_token_peachpay_stripe_achdebit_get_';
	}

	/**
	 * Get type to display to user.
	 *
	 * @since  2.6.0
	 * @param  string $deprecated Deprecated since WooCommerce 3.0.
	 * @return string
	 */
	public function get_display_name( $deprecated = '' ) {
		$display = sprintf(
			/* translators: 1: The name of the bank 2: last 4 digits */
			__( 'ACH: %1$s ending in %2$s', 'peachpay-for-woocommerce' ),
			$this->get_bank(),
			$this->get_last4()
		);
		return $display;
	}

	/**
	 * Validate ACHDebit payment tokens.
	 *
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( false === parent::validate() ) {
			return false;
		}

		if ( ! $this->get_bank( 'edit' ) ) {
			return false;
		}

		$current_mode = PeachPay_Stripe_Integration::mode();
		if ( $current_mode !== $this->get_mode( 'edit' ) ) {
			return false;
		}

		$connect_id = PeachPay_Stripe_Integration::connect_id();
		if ( $connect_id !== $this->get_connect_id( 'edit' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the Name of the bank.
	 *
	 * @since  2.6.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string Name of the bank
	 */
	public function get_bank( $context = 'view' ) {
		return $this->get_prop( 'bank', $context );
	}

	/**
	 * Set the name of the bank.
	 *
	 * @since 2.6.0
	 * @param string $bank The name of the bank.
	 */
	public function set_bank( $bank ) {
		$this->set_prop( 'bank', $bank );
	}

	/**
	 * Returns the mode the payment method was created in.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string The mode.
	 */
	public function get_mode( $context = 'view' ) {
		return $this->get_prop( 'mode', $context );
	}

	/**
	 * Sets the mode the payment method was created in.
	 *
	 * @param "live"|"test" $mode The mode the token belongs to.
	 */
	public function set_mode( $mode ) {
		$this->set_prop( 'mode', $mode );
	}


	/**
	 * Returns the connect id the payment method was created with.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string The connect id.
	 */
	public function get_connect_id( $context = 'view' ) {
		return $this->get_prop( 'connect_id', $context );
	}

	/**
	 * Sets the connect id the payment method was created with.
	 *
	 * @param string $connect_id The connect id the token belongs to.
	 */
	public function set_connect_id( $connect_id ) {
		$this->set_prop( 'connect_id', $connect_id );
	}
}
