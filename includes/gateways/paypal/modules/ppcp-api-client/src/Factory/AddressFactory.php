<?php
/**
 * The Address factory.
 *
 * @package WooCommerce\PayPalCommerce\ApiClient\Factory
 */

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\ApiClient\Factory;

use WooCommerce\PayPalCommerce\ApiClient\Entity\Address;
use WooCommerce\PayPalCommerce\ApiClient\Exception\RuntimeException;

/**
 * Class AddressFactory
 */
class AddressFactory {
	// Add 20-24 rows by Shohei Tanaka 2023/05/04
	public $jp_states;
	public function __construct() {
		global $states;
		$this->jp_states = $states['JP'];
	}

	/**
	 * Returns either the shipping or billing Address object of a customer.
	 *
	 * @param \WC_Customer $customer The WooCommerce customer.
	 * @param string       $type Either 'shipping' or 'billing'.
	 *
	 * @return Address
	 */
	public function from_wc_customer( \WC_Customer $customer, string $type = 'shipping' ): Address {
		if($customer->get_shipping_country() == 'JP' && !empty($customer->get_shipping_state())){// Add 35-39 rows by Shohei Tanaka 2023/05/04
			$shipping_state = $this->jp_states[$customer->get_shipping_state()];
		}else{
			$shipping_state = $customer->get_shipping_state();
		}
		return new Address(
			( 'shipping' === $type ) ?
				$customer->get_shipping_country() : $customer->get_billing_country(),
			( 'shipping' === $type ) ?
				$customer->get_shipping_address_1() : $customer->get_billing_address_1(),
			( 'shipping' === $type ) ?
				$customer->get_shipping_address_2() : $customer->get_billing_address_2(),
			( 'shipping' === $type ) ?
//				$customer->get_shipping_state() : $customer->get_billing_state(),
				$shipping_state : $customer->get_billing_state(),// Edit by Shohei Tanaka 2023/05/04
			( 'shipping' === $type ) ?
				$customer->get_shipping_city() : $customer->get_billing_city(),
			( 'shipping' === $type ) ?
				$customer->get_shipping_postcode() : $customer->get_billing_postcode()
		);
	}

	/**
	 * Returns an Address object based of a WooCommerce order.
	 *
	 * @param \WC_Order $order The order.
	 *
	 * @return Address
	 */
	public function from_wc_order( \WC_Order $order ): Address {
		return new Address(
			$order->get_shipping_country(),
			$order->get_shipping_address_1(),
			$order->get_shipping_address_2(),
			$order->get_shipping_state(),
			$order->get_shipping_city(),
			$order->get_shipping_postcode()
		);
	}

	/**
	 * Creates an Address object based off a PayPal Response.
	 *
	 * @param \stdClass $data The JSON object.
	 *
	 * @return Address
	 * @throws RuntimeException When JSON object is malformed.
	 */
	public function from_paypal_response( \stdClass $data ): Address {
		if ( isset( $data->country_code ) ) {// Add 84-93 rows by Shohei Tanaka 2023/05/04
			if($data->country_code == 'JP'){
				foreach ($this->jp_states as $key => $value){
					if($value == $data->admin_area_1){
						$jp_admin_area_1 = $key;
					}
				}
			}
		}
		if( !isset( $jp_admin_area_1 ) ) $jp_admin_area_1 = $data->admin_area_1;
		return new Address(
			( isset( $data->country_code ) ) ? $data->country_code : '',
			( isset( $data->address_line_1 ) ) ? $data->address_line_1 : '',
			( isset( $data->address_line_2 ) ) ? $data->address_line_2 : '',
			( isset( $jp_admin_area_1 ) ) ? $jp_admin_area_1 : '',// Edit by Shohei Tanaka 2023/05/04
//			( isset( $data->admin_area_1 ) ) ? $data->admin_area_1 : '',
			( isset( $data->admin_area_2 ) ) ? $data->admin_area_2 : '',
			( isset( $data->postal_code ) ) ? $data->postal_code : ''
		);
	}
}
