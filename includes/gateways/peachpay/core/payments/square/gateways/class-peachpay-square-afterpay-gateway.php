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

		$country = wc_get_base_location()['country'];
		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			$country = WC()->customer->get_billing_country();
		}

		$region_title = $this->afterpay_or_clearpay();
		$this->title  = 'clearpay' === $region_title ? __( 'Clearpay', 'peachpay-for-woocommerce' ) : __( 'Afterpay', 'peachpay-for-woocommerce' );

		// translators: %s Button text name.
		$this->description           = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );
		$this->currencies            = array( 'USD', 'CAD', 'GBP', 'AUD', 'NZD', 'EUR' );
		$this->countries             = array( 'US', 'CA', 'GB', 'AU', 'NZ', 'FR', 'ES', 'IT' );
		$this->payment_method_family = __( 'Buy now, Pay later', 'peachpay-for-woocommerce' );
		$this->min_amount            = 1;
		$this->max_amount            = 2000;

		parent::__construct();

		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_afterpay_title' ), 100 );
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
	 * Returns 'clearpay' if this payment method should be presented as Clearpay; returns 'afterpay' otherwise
	 */
	public function afterpay_or_clearpay() {
		$country = wc_get_base_location()['country'];

		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			$country = WC()->customer->get_billing_country();
		}

		switch ( $country ) {
			case 'GB':
			case 'ES':
			case 'FR':
			case 'IT':
				return 'clearpay';
			default:
				return 'afterpay';
		}
	}

	/**
	 * Override get_title method to return afterpay/clearpay depending on customer billing.
	 * If no customer, will default to store base country
	 */
	public function get_title() {
		if ( $this->get_option( 'title' ) && '' !== $this->get_option( 'title' ) ) {
			return $this->get_option( 'title' );
		}

		$afterpay_or_clearpay = $this->afterpay_or_clearpay();

		return 'clearpay' === $afterpay_or_clearpay ? __( 'Clearpay', 'peachpay-for-woocommerce' ) : __( 'Afterpay', 'peachpay-for-woocommerce' );
	}

	/**
	 * Override get_icon_url method to return afterpay/clearpay icon url depending on customer billing.
	 * If no customer, will default to store base currency
	 *
	 * @param string $size       of the icon.
	 * @param string $background of the icon.
	 */
	public function get_icon_url( $size = 'full', $background = 'color' ) {
		$afterpay_or_clearpay = $this->afterpay_or_clearpay();

		$this->icons = array(
			'full'  => array(
				'color' => PeachPay::get_asset_url( "img/marks/stripe/$afterpay_or_clearpay-full-color.svg" ),
			),
			'small' => array(
				'color' => PeachPay::get_asset_url( "img/marks/stripe/$afterpay_or_clearpay-small-color.svg" ),
				'white' => PeachPay::get_asset_url( "img/marks/stripe/$afterpay_or_clearpay-small-white.svg" ),
			),
		);

		return parent::get_icon_url( $size, $background );
	}

	/**
	 * Update afterpay name when the order is changed.
	 *
	 * @param array $fragments Script fragments.
	 */
	public function update_afterpay_title( $fragments ) {
		$region_title = $this->afterpay_or_clearpay();

		switch ( $region_title ) {
			case 'clearpay':
				$this->title = __( 'Clearpay', 'peachpay-for-woocommerce' );
				$this->icon  = PeachPay::get_asset_url( 'img/marks/clearpay.svg' );
				break;
			default:
				$this->title = __( 'Afterpay', 'peachpay-for-woocommerce' );
				$this->icon  = PeachPay::get_asset_url( 'img/marks/afterpay.svg' );
		}

		return $fragments;
	}
}
