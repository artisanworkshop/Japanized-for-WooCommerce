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
		$this->id                                    = 'peachpay_stripe_afterpay';
		$this->stripe_payment_method_type            = 'afterpay_clearpay';
		$this->stripe_payment_method_capability_type = 'afterpay_clearpay';
		$this->settings_priority                     = 5;

		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
				$country = WC()->customer->get_billing_country();
		} elseif ( WC()->countries && method_exists( WC()->countries, 'get_base_country' ) ) {
				$country = WC()->countries->get_base_country();
		} else {
				$country = 'US';
		}
		switch ( $country ) {
			case 'GB':
			case 'ES':
			case 'FR':
			case 'IT':
				$this->title = __( 'Clearpay', 'peachpay-for-woocommerce' );
				$this->icon  = PeachPay::get_asset_url( 'img/marks/clearpay.svg' );
				break;
			default:
				$this->title = __( 'Afterpay', 'peachpay-for-woocommerce' );
				$this->icon  = PeachPay::get_asset_url( 'img/marks/afterpay.svg' );
		}

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

		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_afterpay_title' ), 100 );
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
		}

		// Availability for only the order pay page.
		if ( $is_available && is_wc_endpoint_url( 'order-pay' ) ) {
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
	 * Update afterpay name when the order is changed.
	 *
	 * @param array $fragments Script fragments.
	 */
	public function update_afterpay_title( $fragments ) {
		// Customer facing title and description.
		// The POST errors do not matter since we only do an equivalency check and the title
		//  will only be set according to that.
		// phpcs:ignore
		if ( ! empty( $_POST ) && array_key_exists( 'country', $_POST ) ) {
			// phpcs:ignore
			$active_country = sanitize_text_field( wp_unslash( $_POST['country'] ) );
		} elseif ( WC()->countries && method_exists( WC()->countries, 'get_base_country' ) ) {
			$active_country = WC()->countries->get_base_country();
		} else {
			$active_country = 'US';
		}
		switch ( $active_country ) {
			case 'GB':
			case 'ES':
			case 'FR':
			case 'IT':
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
