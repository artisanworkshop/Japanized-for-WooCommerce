<?php
/**
 * PeachPay Stripe Klarna gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
/**
 * .
 */
class PeachPay_Stripe_Klarna_Gateway extends PeachPay_Stripe_Payment_Gateway {
	const CUSTOMER_COUNTRIES_CURRENCIES_DICT = array(
		'AU' => 'AUD',
		'CA' => 'CAD',
		'CZ' => 'CZK',
		'DK' => 'DKK',
		'NZ' => 'NZD',
		'NW' => 'NOK',
		'PL' => 'PLN',
		'SE' => 'SEK',
		'CH' => 'CHF',
		'GB' => 'GBP',
		'US' => 'USD',
		'AT' => 'EUR',
		'BE' => 'EUR',
		'FI' => 'EUR',
		'FR' => 'EUR',
		'DE' => 'EUR',
		'GR' => 'EUR',
		'IE' => 'EUR',
		'IT' => 'EUR',
		'NL' => 'EUR',
		'PT' => 'EUR',
		'ES' => 'EUR',
	);

	/**
	 * .
	 */
	public function __construct() {
		$this->id                                    = 'peachpay_stripe_klarna';
		$this->stripe_payment_method_type            = 'klarna';
		$this->stripe_payment_method_capability_type = 'klarna';
		$this->icons                                 = array(
			'full'  => array(
				'color' => PeachPay::get_asset_url( 'img/marks/stripe/klarna-full.svg' ),
			),
			'small' => array(
				'white' => PeachPay::get_asset_url( 'img/marks/stripe/klarna-small-white.svg' ),
				'color' => PeachPay::get_asset_url( 'img/marks/stripe/klarna-small-color.svg' ),
			),
		);
		$this->settings_priority                     = 4;

		// Customer facing title and description.
		$this->title = 'Klarna';
		// translators: %s Button text name.
		$this->description = __( 'After selecting %s you will be redirected to complete your payment.', 'peachpay-for-woocommerce' );

		if ( is_admin() ) {
			$connect_country = PeachPay_Stripe_Integration::connect_country();

			if ( 'US' === $connect_country ) {
				$this->countries  = array( 'US' );
				$this->currencies = array( 'USD' );
			} elseif ( 'AU' === $connect_country ) {
				$this->countries  = array( 'AU' );
				$this->currencies = array( 'AUD' );
			} elseif ( 'NZ' === $connect_country ) {
				$this->countries  = array( 'NZ' );
				$this->currencies = array( 'NZD' );
			} elseif ( 'CA' === $connect_country ) {
				$this->countries  = array( 'CA' );
				$this->currencies = array( 'CAD' );
			} else {
				$this->countries  = array( 'AT', 'BE', 'CZ', 'DK', 'FI', 'FR', 'DE', 'GR', 'IE', 'IT', 'NL', 'NO', 'PL', 'PT', 'ES', 'SE', 'CH', 'GB' );
				$this->currencies = array( 'EUR', 'CZK', 'DKK', 'NOK', 'PLN', 'SEK', 'CHF', 'GBP' );
			}
		} else {
			$current_billing_country = 'US';
			if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
				$current_billing_country = WC()->customer->get_billing_country();
			} elseif ( WC()->countries && method_exists( WC()->countries, 'get_base_country' ) ) {
				$current_billing_country = WC()->countries->get_base_country();
			}

			if ( isset( self::CUSTOMER_COUNTRIES_CURRENCIES_DICT[ $current_billing_country ] ) ) {
				$this->countries  = array( $current_billing_country );
				$this->currencies = array( self::CUSTOMER_COUNTRIES_CURRENCIES_DICT[ $current_billing_country ] );
			} else {
				$this->countries  = array( 'US' );
				$this->currencies = array( 'USD' );
			}
		}

		$this->payment_method_family = __( 'Buy Now, Pay Later', 'peachpay-for-woocommerce' );

		$this->form_fields = self::capture_method_setting( $this->form_fields );

		parent::__construct();
	}

	/**
	 * Setup future settings for payment intent.
	 */
	protected function setup_future_usage() {
		return null;
	}
}
