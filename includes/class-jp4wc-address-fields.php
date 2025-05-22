<?php
/**
 * Japanized for WooCommerce
 *
 * @package     Japanized for WooCommerce
 * @version     2.6.37
 * @category    Address Setting for Japan
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class JP4WC_Address_Fields
 *
 * Handles the customization of WooCommerce address fields for Japan-specific requirements
 *
 * This class manages the modification and validation of address fields in WooCommerce
 * to better suit Japanese addressing conventions and postal formats.
 *
 * @package WooCommerce for Japan
 * @version 2.6.37
 * @category Address Management
 * @author Shohei Tanaka
 */
class JP4WC_Address_Fields {

	/**
	 * __construct function.
	 */
	public function __construct() {
		// WPML check.
		if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE !== 'ja' ) {
			return;
		}
		// Default address fields.
		add_filter( 'woocommerce_default_address_fields', array( $this, 'address_fields' ) );
		// Add yomigana fields.
		add_filter( 'woocommerce_default_address_fields', array( $this, 'add_yomigana_fields' ) );
		// MyPage Edit And Checkout fields.
		add_filter( 'woocommerce_billing_fields', array( $this, 'billing_address_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'shipping_address_fields' ), 20 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'address_replacements' ), 20, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_formats' ), 20 );
		// My Account Display for address.
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'formatted_address' ), 20, 3 );// template/myaccount/my-address.php
		// Checkout Display for address.
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'jp4wc_billing_address' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'jp4wc_shipping_address' ), 20, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin_order_data_after_shipping_address' ), 10 );

		// include get_order function.
		add_filter( 'woocommerce_get_order_address', array( $this, 'jp4wc_get_order_address' ), 20, 3 );// includes/abstract/abstract-wc-order.php
		// FrontEnd CSS file.
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_style' ), 99 );
		// Admin Edit Address.
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'admin_billing_address_fields' ) );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'admin_shipping_address_fields' ) );
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'admin_customer_meta_fields' ) );

		// Remove checkout fields for PayPal cart checkout.
		add_filter( 'woocommerce_default_address_fields', array( $this, 'remove_checkout_fields_for_paypal' ) );

		add_filter( 'woocommerce_email_preview_dummy_order', array( $this, 'jp4wc_email_preview_dummy_order' ), 10 );
		add_filter( 'woocommerce_email_preview_dummy_address', array( $this, 'jp4wc_email_preview_dummy_address' ), 10 );
		add_filter( 'woocommerce_email_preview_dummy_product', array( $this, 'jp4wc_email_preview_dummy_product' ), 10 );
		add_filter( 'woocommerce_email_preview_dummy_product_variation', array( $this, 'jp4wc_email_preview_dummy_product_variation' ), 10 );
	}

	/**
	 * Address correspondence in Japan
	 *
	 * @since  1.2
	 * @version 2.2.7
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function address_fields( $fields ) {
		$fields['last_name']['class']     = array( 'form-row-first' );
		$fields['last_name']['priority']  = 10;
		$fields['first_name']['class']    = array( 'form-row-last' );
		$fields['first_name']['priority'] = 20;
		$fields['postcode']['class']      = array( 'form-row-first' );
		$fields['postcode']['type']       = 'tel';
		$fields['state']['class']         = array( 'form-row-last' );

		return $fields;
	}

	/**
	 * Yomigana Setting
	 *
	 * @since 2.2.7
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function add_yomigana_fields( $fields ) {
		if ( get_option( 'wc4jp-yomigana' ) ) {
			$fields['yomigana_last_name']  = array(
				'label'    => __( 'Last Name ( Yomigana )', 'woocommerce-for-japan' ),
				'required' => false,
				'class'    => array( 'form-row-first' ),
				'priority' => 25,
			);
			$fields['yomigana_first_name'] = array(
				'label'    => __( 'First Name ( Yomigana )', 'woocommerce-for-japan' ),
				'required' => false,
				'class'    => array( 'form-row-last' ),
				'clear'    => true,
				'priority' => 28,
			);
		}
		if ( get_option( 'wc4jp-yomigana-required' ) ) {
			$fields['yomigana_last_name']['required']  = true;
			$fields['yomigana_first_name']['required'] = true;
		}
		return $fields;
	}
	/**
	 * Japan corresponding set of billing address information
	 *
	 * @since  1.2
	 * @version 2.6.37
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function billing_address_fields( $fields ) {
		if ( ! isset( $fields['billing_company'] ) ) {
			$fields['billing_company'] = array(
				'label'    => __( 'Company Name', 'woocommerce-for-japan' ),
				'required' => false,
				'class'    => array( 'form-row-wide' ),
				'clear'    => true,
				'priority' => 20,
			);
		}
		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $fields['billing_company'] );
		}

		return $fields;
	}

	/**
	 * Japan corresponding set of shipping address information
	 *
	 * @since  1.2
	 * @version 2.6.37
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function shipping_address_fields( $fields ) {
		$address_fields = $fields;

		$address_fields['shipping_phone'] = array(
			'label'    => __( 'Shipping Phone', 'woocommerce-for-japan' ),
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'clear'    => true,
			'validate' => array( 'phone' ),
			'priority' => 100,
		);
		if ( ! isset( $address_fields['shipping_company'] ) ) {
			$address_fields['shipping_company'] = array(
				'label'    => __( 'Company Name', 'woocommerce-for-japan' ),
				'required' => false,
				'class'    => array( 'form-row-wide' ),
				'clear'    => true,
				'priority' => 20,
			);
		}
		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $address_fields['shipping_company'] );
		}
		return $address_fields;
	}

	/**
	 * Substitute address parts into the string for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array $fields The formatted address fields.
	 * @param  array $args The order object.
	 * @return array
	 */
	public function address_replacements( $fields, $args ) {
		if ( get_option( 'wc4jp-yomigana' ) ) {
			if ( isset( $args['yomigana_last_name'] ) ) {
				$fields['{yomigana_last_name}'] = $args['yomigana_last_name'];
			}
			if ( isset( $args['yomigana_first_name'] ) ) {
				$fields['{yomigana_first_name}'] = $args['yomigana_first_name'];
			}
		}
		if ( is_order_received_page() && isset( $args['phone'] ) ) {
			$fields['{phone}'] = $args['phone'];
		}

		return $fields;
	}

	/**
	 * Setting address formats for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function address_formats( $fields ) {
		// honorific suffix.
		$honorific_suffix = '';
		if ( get_option( 'wc4jp-honorific-suffix' ) ) {
			$honorific_suffix = '様';
		}

		// PayPal Payment compatible.
		if ( isset( $_GET['woo-paypal-return'] ) && true === $_GET['woo-paypal-return'] && isset( $_GET['token'] ) ) {
			$set_yomigana = '';
		} else {
			$set_yomigana = "\n{yomigana_last_name} {yomigana_first_name}";
		}
		if ( get_option( 'wc4jp-company-name' ) && get_option( 'wc4jp-yomigana' ) ) {
			$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}" . $set_yomigana . "\n{last_name} {first_name}" . $honorific_suffix . "\n {country}";
		}
		if ( get_option( 'wc4jp-company-name' ) && ! get_option( 'wc4jp-yomigana' ) ) {
			$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}\n{last_name} {first_name}" . $honorific_suffix . "\n {country}";
		}
		if ( ! get_option( 'wc4jp-company-name' ) && get_option( 'wc4jp-yomigana' ) ) {
			$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}" . $set_yomigana . "\n{last_name} {first_name}" . $honorific_suffix . "\n {country}";
		}
		if ( ! get_option( 'wc4jp-company-name' ) && ! get_option( 'wc4jp-yomigana' ) ) {
			$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{last_name} {first_name}" . $honorific_suffix . "\n {country}";
		}
		if ( is_cart() ) {
			$fields['JP'] = '〒{postcode}{state}{city}';
		}
		if ( is_order_received_page() ) {
			$fields['JP'] = $fields['JP'] . "\n {phone}";
		}

		// Core page IDs.
		$cart_page_id     = wc_get_page_id( 'cart' );
		$checkout_page_id = wc_get_page_id( 'checkout' );

		// Checks a specific page (by ID) to see if it contains the named block.
		$has_block_cart     = $cart_page_id && has_block( 'woocommerce/cart', $cart_page_id );
		$has_block_checkout = $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );
		if ( $has_block_checkout && is_checkout() ) {
			$fields['JP'] = '〒{postcode} {state}{city}{address_1} {address_2} {country}';
		}
		return $fields;
	}

	/**
	 * Check if the current request is a block-based checkout request.
	 */
	public function is_block_checkout_request() {
		$rest_route = isset( $_REQUEST['rest_route'] ) ? wp_unslash( $_REQUEST['rest_route'] ) : '';// phpcs:ignore 

		// If it starts with /wc/store/, it is considered a block-based checkout.
		return ( 0 === strpos( $rest_route, '/wc/store/' ) );
	}

	/**
	 * Setting account formatted address for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array  $fields The formatted address fields.
	 * @param  string $customer_id The customer ID.
	 * @param  string $name The customer name.
	 * @return array
	 */
	public function formatted_address( $fields, $customer_id, $name ) {
		$fields['yomigana_first_name'] = get_user_meta( $customer_id, $name . '_yomigana_first_name', true );
		$fields['yomigana_last_name']  = get_user_meta( $customer_id, $name . '_yomigana_last_name', true );
		$fields['phone']               = get_user_meta( $customer_id, $name . '_phone', true );

		return $fields;
	}

	/**
	 * Setting account formatted address for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array  $fields The formatted address fields.
	 * @param  object $args  The order object.
	 * @return array
	 */
	public function jp4wc_billing_address( $fields, $args ) {
		$order = wc_get_order( $args->get_id() );
		if ( isset( $_GET['preview_woocommerce_mail'] ) || empty( $order ) ) {
			return $fields;
		}
		$fields['yomigana_first_name'] = $order->get_meta( '_billing_yomigana_first_name', true );
		$fields['yomigana_last_name']  = $order->get_meta( '_billing_yomigana_last_name', true );
		$fields['phone']               = $order->get_billing_phone();

		if ( '' === $fields['country'] ) {
			$fields['country'] = 'JP';
		}

		return $fields;
	}

	/**
	 * Setting a formatted shipping address for the order, in Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array  $fields The formatted address fields.
	 * @param  object $args The order object.
	 * @return array
	 */
	public function jp4wc_shipping_address( $fields, $args ) {
		if ( isset( $fields['first_name'] ) ) {
			$order = wc_get_order( $args->get_id() );
			if ( isset( $_GET['preview_woocommerce_mail'] ) || empty( $order ) ) {
				return $fields;
			}

			$fields['yomigana_first_name'] = $order->get_meta( '_shipping_yomigana_first_name', true );
			$fields['yomigana_last_name']  = $order->get_meta( '_shipping_yomigana_last_name', true );
			$fields['phone']               = $order->get_shipping_phone();
			if ( '' === $fields['country'] ) {
				$fields['country'] = 'JP';
			}
		}

		return $fields;
	}

	/**
	 * Display phone number of shipping address in admin screen
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  object WC_Order $order Order object.
	 */
	public function admin_order_data_after_shipping_address( $order ) {
		$field['label'] = __( 'Shipping Phone', 'woocommerce-for-japan' );
		$field_value    = $order->get_shipping_phone();
		$field_value    = wc_make_phone_clickable( $field_value );
		echo '<div style="display:block;clear:both;"><p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( $field_value ) . '</p></div>';
	}

	/**
	 * Setting address for the order, in Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array  $address The stored address.
	 * @param  string $type 'billing' or 'shipping' address.
	 * @param  object $args The order object.
	 * @return array The stored address after filter.
	 */
	public function jp4wc_get_order_address( $address, $type, $args ) {
		if ( empty( $args ) ) {
			return $address;
		}

		$order_id = $args->get_id();

		if ( 'billing' === $type ) {
			$address['yomigana_first_name'] = $args->get_meta( '_billing_yomigana_first_name', true );
			$address['yomigana_last_name']  = $args->get_meta( '_billing_yomigana_last_name', true );
		} else {
			$address['yomigana_first_name'] = $args->get_meta( '_shipping_yomigana_first_name', true );
			$address['yomigana_last_name']  = $args->get_meta( $order_id, '_shipping_yomigana_last_name', true );
			$address['phone']               = $args->get_meta( $order_id, '_shipping_phone', true );
		}
		return $address;
	}

	/**
	 * Checks if the current page is the order received (thank you) page.
	 *
	 * This conditional check is used to determine if the user is viewing
	 * the order confirmation page after completing a purchase.
	 *
	 * @return void
	 */
	public function frontend_enqueue_style() {
		if ( is_order_received_page() ) {
			wp_register_style( 'custom_order_received_jp4wc', JP4WC_URL_PATH . 'assets/css/order-received-jp4wc.css', false, JP4WC_VERSION );
			wp_enqueue_style( 'custom_order_received_jp4wc' );
		}
		if ( is_account_page() ) {
			wp_register_style( 'edit_account_jp4wc', JP4WC_URL_PATH . 'assets/css/edit-account-jp4wc.css', false, JP4WC_VERSION );
			wp_enqueue_style( 'edit_account_jp4wc' );
		}
	}

	/**
	 * Setting edit item in the billing address of the admin screen for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function admin_billing_address_fields( $fields ) {
		foreach ( $fields as $key => $value ) {
			$new_fields[ $key ] = $value;
		}
		$fields = array(
			'last_name'           => $new_fields['last_name'],
			'first_name'          => $new_fields['first_name'],
			'yomigana_last_name'  => array(
				'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			),
			'yomigana_first_name' => array(
				'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			),
			'country'             => $new_fields['country'],
			'postcode'            => $new_fields['postcode'],
			'state'               => $new_fields['state'],
			'city'                => $new_fields['city'],
			'company'             => $new_fields['company'],
			'address_1'           => $new_fields['address_1'],
			'address_2'           => $new_fields['address_2'],
			'email'               => $new_fields['email'],
			'phone'               => $new_fields['phone'],
		);
		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $fields['company'] );
		}
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			unset( $fields['yomigana_last_name'] );
			unset( $fields['yomigana_first_name'] );
		}

		return $fields;
	}

	/**
	 * Setting edit item in the shipping address of the admin screen for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function admin_shipping_address_fields( $fields ) {
		foreach ( $fields as $key => $value ) {
			$new_fields[ $key ] = $value;
		}
		$fields = array(
			'last_name'           => $new_fields['last_name'],
			'first_name'          => $new_fields['first_name'],
			'yomigana_last_name'  => array(
				'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			),
			'yomigana_first_name' => array(
				'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			),
			'country'             => $new_fields['country'],
			'postcode'            => $new_fields['postcode'],
			'state'               => $new_fields['state'],
			'city'                => $new_fields['city'],
			'company'             => $new_fields['company'],
			'address_1'           => $new_fields['address_1'],
			'address_2'           => $new_fields['address_2'],
			'phone'               => array(
				'label' => __( 'Phone', 'woocommerce-for-japan' ),
				'show'  => false,
			),
		);

		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $fields['company'] );
		}
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			unset( $fields['yomigana_last_name'], $fields['yomigana_first_name'] );
		}

		return $fields;
	}

	/**
	 * Setting Address Fields for the edit user pages for Japanese.
	 *
	 * @since  1.2
	 * @version 2.0.0
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function admin_customer_meta_fields( $fields ) {
		$customer_meta_fields = $fields;
		// Billing fields.
		$billing_fields                            = $fields['billing']['fields'];
		$customer_meta_fields['billing']['fields'] = array(
			'billing_last_name'           => $billing_fields['billing_last_name'],
			'billing_first_name'          => $billing_fields['billing_first_name'],
			'billing_yomigana_last_name'  => array(
				'label'       => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'billing_yomigana_first_name' => array(
				'label'       => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'billing_company'             => $billing_fields['billing_company'],
			'billing_country'             => $billing_fields['billing_country'],
			'billing_postcode'            => $billing_fields['billing_postcode'],
			'billing_state'               => $billing_fields['billing_state'],
			'billing_city'                => $billing_fields['billing_city'],
			'billing_address_1'           => $billing_fields['billing_address_1'],
			'billing_address_2'           => $billing_fields['billing_address_2'],
			'billing_phone'               => $billing_fields['billing_phone'],
			'billing_email'               => $billing_fields['billing_email'],
		);
		// Shipping fields.
		$shipping_fields                            = $fields['shipping']['fields'];
		$customer_meta_fields['shipping']['fields'] = array(
			'shipping_last_name'           => $shipping_fields['shipping_last_name'],
			'shipping_first_name'          => $shipping_fields['shipping_first_name'],
			'shipping_yomigana_last_name'  => array(
				'label'       => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'shipping_yomigana_first_name' => array(
				'label'       => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'description' => '',
			),
			'shipping_company'             => $shipping_fields['shipping_company'],
			'shipping_country'             => $shipping_fields['shipping_country'],
			'shipping_postcode'            => $shipping_fields['shipping_postcode'],
			'shipping_state'               => $shipping_fields['shipping_state'],
			'shipping_city'                => $shipping_fields['shipping_city'],
			'shipping_address_1'           => $shipping_fields['shipping_address_1'],
			'shipping_address_2'           => $shipping_fields['shipping_address_2'],
			'shipping_phone'               => array(
				'label'       => __( 'Phone', 'woocommerce-for-japan' ),
				'description' => '',
			),
		);
		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $customer_meta_fields['billing']['fields']['billing_company'], $customer_meta_fields['shipping']['fields']['shipping_company'] );
		}
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			unset( $customer_meta_fields['billing']['fields']['billing_yomigana_last_name'], $customer_meta_fields['billing']['fields']['billing_yomigana_first_name'], $customer_meta_fields['shipping']['fields']['shipping_yomigana_last_name'], $customer_meta_fields['shipping']['fields']['shipping_yomigana_first_name'] );
		}
		return $customer_meta_fields;
	}

	/**
	 * Address correspondence in Japan
	 *
	 * @since  2.2.7
	 * @param  array $fields The formatted address fields.
	 * @return array $fields
	 */
	public function remove_checkout_fields_for_paypal( $fields ) {
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array();
		foreach ( $gateways as $key => $value ) {
			if ( 'yes' === $value->enabled ) {
				$enabled_gateways[] = $key;
			}
		}
		$paypal_flag = in_array( 'ppec_paypal', $enabled_gateways );
		if ( get_option( 'wc4jp-yomigana' ) && 1 === $paypal_flag ) {
			$fields['yomigana_last_name']['required']  = false;
			$fields['yomigana_first_name']['required'] = false;
		}
		return $fields;
	}

	/**
	 * Modifies the dummy order for email previews to use JPY currency
	 *
	 * @since 2.6.43
	 * @param WC_Order $order The order object to modify.
	 * @return WC_Order The modified order object.
	 */
	public function jp4wc_email_preview_dummy_order( $order ) {
		$order->set_currency( 'JPY' );
		$order->set_discount_total( 1000 );
		$order->set_shipping_total( 500 );
		$order->set_total( 3000 );
		return $order;
	}

	/**
	 * Modifies the dummy address for email previews with Japanese sample data
	 *
	 * @since 2.6.43
	 * @param array $address    The address data to modify.
	 * @return array The modified address with Japanese sample data.
	 */
	public function jp4wc_email_preview_dummy_address( $address ) {
		$address['first_name'] = __( 'Taro', 'woocommerce-for-japan' );
		$address['last_name']  = __( 'Yamada', 'woocommerce-for-japan' );
		$address['company']    = __( 'Company', 'woocommerce-for-japan' );
		$address['phone']      = '090-1234-5678';
		$address['email']      = 'yamada.taro@company.com';
		$address['address_1']  = __( '2-1 Dougenzaka', 'woocommerce-for-japan' );
		$address['city']       = __( 'Shibuya Ku', 'woocommerce-for-japan' );
		$address['postcode']   = '150-0002';
		$address['country']    = 'JP';
		$address['state']      = 'JP13';
		return $address;
	}

	/**
	 * Modifies the dummy product for email previews with Japanese pricing
	 *
	 * @since 2.6.43
	 * @param WC_Product $product The product object to modify.
	 * @return WC_Product The modified product object.
	 */
	public function jp4wc_email_preview_dummy_product( $product ) {
		$product->set_price( 1000 );
		return $product;
	}

	/**
	 * Modifies the dummy product variation for email previews with Japanese pricing
	 *
	 * @since 2.6.43
	 * @param WC_Product_Variation $product The product variation object to modify.
	 * @return WC_Product_Variation The modified product variation object.
	 */
	public function jp4wc_email_preview_dummy_product_variation( $product ) {
		$product->set_price( 1500 );
		return $product;
	}
}
// Address Fields Class load.
if ( ! get_option( 'wc4jp-no-ja' ) ) {
	new JP4WC_Address_Fields();
}
