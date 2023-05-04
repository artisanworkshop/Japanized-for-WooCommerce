<?php
/**
 * Token class for PeachPay square card payment method.
 *
 * @PHPCS:disable Squiz.Commenting.VariableComment.Missing
 *
 * @package PeachPay
 */

( defined( 'PEACHPAY_ABSPATH' ) && defined( 'WC_ABSPATH' ) ) || exit;

require_once WC_ABSPATH . 'includes/payment-tokens/class-wc-payment-token-cc.php';

/**
 * .
 */
class WC_Payment_Token_PeachPay_Square_Card extends WC_Payment_Token_CC {
	/**
	 * Token Type String.
	 *
	 * @var string
	 */
	protected $type = 'peachpay_square_card';

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
	);

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
	 * Validate Card payment tokens.
	 *
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( false === parent::validate() ) {
			return false;
		}

		$current_mode = PeachPay_Square_Integration::mode();
		if ( $current_mode !== $this->get_mode( 'edit' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Hook prefix
	 */
	protected function get_hook_prefix() {
		return 'peachpay_square_card_token_get_';
	}

}
