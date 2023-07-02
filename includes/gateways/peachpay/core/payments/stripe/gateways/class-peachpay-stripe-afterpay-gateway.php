<?php
/**
 * PeachPay Stripe Afterpay / Clearpay gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
class PeachPay_Stripe_Afterpay_Gateway extends PeachPay_Stripe_Payment_Gateway {
	const COUNTRIES_CURRENCIES_DICT = array(
		'AU' => 'AUD',
		'CA' => 'CAD',
		'NZ' => 'NZD',
		'GB' => 'GBP',
		'US' => 'USD',
		'FR' => 'EUR',
		'ES' => 'EUR',
	);

	/**
	 * .
	 */
	public function __construct() {
		$this->id    = 'peachpay_stripe_afterpay';
		$this->title = $this->get_title();

		$this->stripe_payment_method_type            = 'afterpay_clearpay';
		$this->stripe_payment_method_capability_type = 'afterpay_clearpay';
		$this->settings_priority                     = 5;

		// translators: %s Button text name.
		$this->description = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );

		$this->currencies = array();
		$this->countries  = array();
		$connect_country  = PeachPay_Stripe_Integration::connect_country();
		if ( isset( self::COUNTRIES_CURRENCIES_DICT[ $connect_country ] ) ) {
			$this->countries  = array( $connect_country );
			$this->currencies = array( self::COUNTRIES_CURRENCIES_DICT[ $connect_country ] );
		}

		$this->payment_method_family = __( 'Buy now, Pay later', 'peachpay-for-woocommerce' );
		$this->min_amount            = 1;
		$this->max_amount            = 2000;

		$this->form_fields = self::capture_method_setting( $this->form_fields );

		parent::__construct();
	}

	/**
	 * Setup future settings for payment intent.
	 */
	protected function setup_future_usage() {
		return null;
	}

	/**
	 * AfterPay does not support virtual product purchases.
	 */
	public function is_available() {
		$is_available = parent::is_available();

		// Availability for cart/checkout page
		if ( WC()->cart ) {
			if ( ! WC()->cart->needs_shipping() ) {
				$is_available = false;
			}
		} elseif ( $is_available && is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order || ! $order->has_shipping_address() ) {
				$is_available = false;
			}
		}

		return $is_available;
	}

	/**
	 * Indicates if the checkout should refresh when the payment method is selected.
	 * ALWAYS returns true for AfterPay so that it can switch to Clearpay branding if shopper country updated.
	 */
	protected function should_refresh_checkout() {
		return 'true';
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
}
