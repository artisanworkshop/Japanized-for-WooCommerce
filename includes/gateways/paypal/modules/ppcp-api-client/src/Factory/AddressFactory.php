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

	public $jp_states;
	public function __construct() {
		$this->jp_states = array(
			'JP01' => __( 'Hokkaido', 'woocommerce' ),
			'JP02' => __( 'Aomori', 'woocommerce' ),
			'JP03' => __( 'Iwate', 'woocommerce' ),
			'JP04' => __( 'Miyagi', 'woocommerce' ),
			'JP05' => __( 'Akita', 'woocommerce' ),
			'JP06' => __( 'Yamagata', 'woocommerce' ),
			'JP07' => __( 'Fukushima', 'woocommerce' ),
			'JP08' => __( 'Ibaraki', 'woocommerce' ),
			'JP09' => __( 'Tochigi', 'woocommerce' ),
			'JP10' => __( 'Gunma', 'woocommerce' ),
			'JP11' => __( 'Saitama', 'woocommerce' ),
			'JP12' => __( 'Chiba', 'woocommerce' ),
			'JP13' => __( 'Tokyo', 'woocommerce' ),
			'JP14' => __( 'Kanagawa', 'woocommerce' ),
			'JP15' => __( 'Niigata', 'woocommerce' ),
			'JP16' => __( 'Toyama', 'woocommerce' ),
			'JP17' => __( 'Ishikawa', 'woocommerce' ),
			'JP18' => __( 'Fukui', 'woocommerce' ),
			'JP19' => __( 'Yamanashi', 'woocommerce' ),
			'JP20' => __( 'Nagano', 'woocommerce' ),
			'JP21' => __( 'Gifu', 'woocommerce' ),
			'JP22' => __( 'Shizuoka', 'woocommerce' ),
			'JP23' => __( 'Aichi', 'woocommerce' ),
			'JP24' => __( 'Mie', 'woocommerce' ),
			'JP25' => __( 'Shiga', 'woocommerce' ),
			'JP26' => __( 'Kyoto', 'woocommerce' ),
			'JP27' => __( 'Osaka', 'woocommerce' ),
			'JP28' => __( 'Hyogo', 'woocommerce' ),
			'JP29' => __( 'Nara', 'woocommerce' ),
			'JP30' => __( 'Wakayama', 'woocommerce' ),
			'JP31' => __( 'Tottori', 'woocommerce' ),
			'JP32' => __( 'Shimane', 'woocommerce' ),
			'JP33' => __( 'Okayama', 'woocommerce' ),
			'JP34' => __( 'Hiroshima', 'woocommerce' ),
			'JP35' => __( 'Yamaguchi', 'woocommerce' ),
			'JP36' => __( 'Tokushima', 'woocommerce' ),
			'JP37' => __( 'Kagawa', 'woocommerce' ),
			'JP38' => __( 'Ehime', 'woocommerce' ),
			'JP39' => __( 'Kochi', 'woocommerce' ),
			'JP40' => __( 'Fukuoka', 'woocommerce' ),
			'JP41' => __( 'Saga', 'woocommerce' ),
			'JP42' => __( 'Nagasaki', 'woocommerce' ),
			'JP43' => __( 'Kumamoto', 'woocommerce' ),
			'JP44' => __( 'Oita', 'woocommerce' ),
			'JP45' => __( 'Miyazaki', 'woocommerce' ),
			'JP46' => __( 'Kagoshima', 'woocommerce' ),
			'JP47' => __( 'Okinawa', 'woocommerce' ),		);
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
		if(!is_object($customer)) return new Address();
		if($customer->get_shipping_country() == 'JP' && !empty($customer->get_shipping_state())){// Edit by Shohei Tanaka 2022/05/30
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
				$shipping_state : $customer->get_billing_state(),
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
		if ( ! isset( $data->country_code ) ) {
			throw new RuntimeException(
				__( 'No country given for address.', 'woocommerce-for-japan' )
			);
		}else{// Edit by Shohei Tanaka 2022/05/30
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
			$data->country_code,
			( isset( $data->address_line_1 ) ) ? $data->address_line_1 : '',
			( isset( $data->address_line_2 ) ) ? $data->address_line_2 : '',
			( isset( $jp_admin_area_1 ) ) ? $jp_admin_area_1 : '',
//			( isset( $data->admin_area_1 ) ) ? $data->admin_area_1 : '',
			( isset( $data->admin_area_2 ) ) ? $data->admin_area_2 : '',
			( isset( $data->postal_code ) ) ? $data->postal_code : ''
		);
	}
}
