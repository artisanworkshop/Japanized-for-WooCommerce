<?php
/**
 * PeachPay Square Afterpay/Clearpay Gateway
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * (Referred to as Clearpay in the EU)
 */
class PeachPay_Square_Afterpay_Gateway extends PeachPay_Square_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                = 'peachpay_square_afterpay';
		$this->settings_priority = 4;

		$this->title = $this->get_title();

		// translators: %s Button text name.
		$this->description           = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );
		$this->currencies            = array( 'USD', 'CAD', 'GBP', 'AUD', 'NZD', 'EUR' );
		$this->countries             = array( 'US', 'CA', 'GB', 'AU', 'NZ', 'FR', 'ES', 'IT' );
		$this->payment_method_family = __( 'Buy now, Pay later', 'peachpay-for-woocommerce' );
		$this->min_amount            = 1;
		$this->max_amount            = 2000;

		parent::__construct();
	}

	/**
	 * Renders payment method fields
	 */
	public function payment_method_form() {
		parent::payment_method_form();
		?>
		<div style="display:none" id="pp-square-afterpay-element"></div>
		<?php
	}

	/**
	 * Override get_title method to return afterpay/clearpay depending on customer billing.
	 * If no customer, will default to store base country
	 */
	public function get_title() {
		if ( $this->get_option( 'title' ) && '' !== $this->get_option( 'title' ) ) {
			return $this->get_option( 'title' );
		}

		$country = wc_get_base_location()['country'];

		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			$country = WC()->customer->get_billing_country();
		}

		switch ( $country ) {
			case 'GB':
			case 'ES':
			case 'FR':
			case 'IT':
				return 'Clearpay';
			default:
				return 'Afterpay';
		}
	}

	/**
	 * Override get_icon method to return afterpay/clearpay logo depending on customer billing.
	 * If no customer, will default to store base country
	 *
	 * @param bool $flex Whether to place the icon in a flex container or not.
	 */
	public function get_icon( $flex = false ) {
		$country = wc_get_base_location()['country'];

		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			$country = WC()->customer->get_billing_country();
		}

		switch ( $country ) {
			case 'GB':
			case 'ES':
			case 'FR':
			case 'IT':
				$this->icon = PeachPay::get_asset_url( 'img/marks/clearpay.svg' );
				break;
			default:
				$this->icon = PeachPay::get_asset_url( 'img/marks/afterpay.svg' );
		}

		return parent::get_icon( $flex );
	}
}
