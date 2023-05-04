<?php
/**
 * Token class for PeachPay Poynt card payment method.
 *
 * @package PeachPay
 */

( defined( 'PEACHPAY_ABSPATH' ) && defined( 'WC_ABSPATH' ) ) || exit;

require_once WC_ABSPATH . 'includes/payment-tokens/class-wc-payment-token-cc.php';

/**
 * .
 */
class WC_Payment_Token_PeachPay_Poynt_Card extends WC_Payment_Token_CC {

	/**
	 * Token Type String.
	 *
	 * @var string
	 */
	protected $type = 'peachpay_poynt_card';

	/**
	 * Stores Credit Card payment token data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'last4'        => '',
		'expiry_year'  => '',
		'expiry_month' => '',
		'card_type'    => '',
		'mode'         => '',
		'business_id'  => '',
	);

	/**
	 * Hook prefix
	 */
	protected function get_hook_prefix() {
		return 'peachpay_poynt_card_token_get_';
	}

	/**
	 * Validate Card payment tokens.
	 *
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( false === parent::validate() ) {
			return false;
		}

		$current_mode = PeachPay_Poynt_Integration::mode();
		if ( $current_mode !== $this->get_mode( 'edit' ) ) {
			return false;
		}

		$business_id = PeachPay_Poynt_Integration::business_id();
		if ( $business_id !== $this->get_business_id( 'edit' ) ) {
			return false;
		}

		return true;
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
	 * Returns the business id the payment method was created with.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string The business id.
	 */
	public function get_business_id( $context = 'view' ) {
		return $this->get_prop( 'business_id', $context );
	}

	/**
	 * Sets the business id the payment method was created with.
	 *
	 * @param string $business_id The business id the token belongs to.
	 */
	public function set_business_id( $business_id ) {
		$this->set_prop( 'business_id', $business_id );
	}
}
