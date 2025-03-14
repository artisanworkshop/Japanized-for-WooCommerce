<?php
/**
 * Japanized for WooCommerce
 *
 * @package     Japanized for WooCommerce
 * @version     2.6.25
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
 * @version 2.6.25
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
		if ( function_exists( 'wc_is_checkout_block_page' ) && ! wc_is_checkout_block_page() ) {
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'address_formats' ), 20 );
		} else {
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'block_address_formats' ), 20 );
		}

		// Address Display for e-mail.
		add_filter( 'woocommerce_order_get_formatted_billing_address', array( $this, 'billing_jp4wc_get_address' ), 10, 3 );
		add_filter( 'woocommerce_order_get_formatted_shipping_address', array( $this, 'shipping_jp4wc_get_address' ), 20, 3 );
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
	 * @version 2.0.0
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function billing_address_fields( $fields ) {
		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $fields['billing_company'] );
		}
		return $fields;
	}

	/**
	 * Japan corresponding set of shipping address information
	 *
	 * @since  1.2
	 * @version 2.0.0
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
			$fields['JP'] = '〒{postcode}{state}{city}{address_1}{address_2} {country}';
		}
		if ( is_order_received_page() ) {
			$fields['JP'] = $fields['JP'] . "\n {phone}";
		}
		if ( $this->is_block_checkout_request() ) {
			$fields['JP'] = '〒{postcode}{state}{city}{address_1}{address_2} {country}';
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
	 * Modifies the address format fields for Japanese addresses by Checkout Block.
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields The default address format fields.
	 * @return array Modified address format fields for Japanese addresses.
	 */
	public function block_address_formats( $fields ) {
		$fields['JP'] = '〒{postcode}{state}{city}{address_1}{address_2} {country}';
		return $fields;
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
		if ( empty( $order ) ) {
			return;
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
			$order                         = wc_get_order( $args->get_id() );
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
	 * Get shipping address format for Japanese addresses
	 *
	 * @param string $address      The formatted shipping address.
	 * @param array  $raw_address  Raw address fields.
	 * @param object $order        The order object.
	 * @return string             Modified shipping address format for Japanese addresses
	 */
	public function jp4wc_get_address( $address, $raw_address, $order ) {
		if ( ( 'store-api' === $order->get_created_via() || 'checkout' === $order->get_created_via() ) && isset( $raw_address['last_name'] ) && isset( $raw_address['first_name'] ) ) {
			if ( preg_match( '/\p{Han}/u', $address, $matches, PREG_OFFSET_CAPTURE ) ) {
				$pos     = $matches[0][1];
				$address = substr_replace( $address, '<br />', $pos, 0 );
			}

			$address_name     = $raw_address['last_name'] . ' ' . $raw_address['first_name'];
			$honorific_suffix = '';
			if ( get_option( 'wc4jp-honorific-suffix' ) ) {
				$honorific_suffix = '様';
			}
			$address_name .= $honorific_suffix;
			if ( isset( $raw_address['country'] ) && 'JP' === $raw_address['country'] ) {
				$address .= ' <br/>' . $address_name;
			}
		}
		return $address;
	}

	/**
	 * Gets the billing address for Japanese orders
	 *
	 * @param string $address      Formatted address.
	 * @param array  $raw_address  Raw address in array format.
	 * @param object $order        WC_Order object.
	 * @return string Modified billing address for Japanese orders.
	 */
	public function billing_jp4wc_get_address( $address, $raw_address, $order ) {
		$address = $this->jp4wc_get_address( $address, $raw_address, $order );

		$billing_yomigana_first_name = $order->get_meta( '_billing_yomigana_first_name', true );
		$billing_yomigana_last_name  = $order->get_meta( '_billing_yomigana_last_name', true );
		$billing_company             = $order->get_billing_company();
		if ( $billing_yomigana_first_name && $billing_yomigana_last_name ) {
			$address .= '<br />(' . $billing_yomigana_last_name . ' ' . $billing_yomigana_first_name . ')';
		}
		if ( $billing_company ) {
			$address .= '<br />' . $billing_company;
		}

		return $address;
	}

	/**
	 * Gets the shipping address for Japanese orders
	 *
	 * @param string $address      Formatted address.
	 * @param array  $raw_address  Raw address in array format.
	 * @param object $order        WC_Order object.
	 * @return string Modified shipping address for Japanese orders.
	 */
	public function shipping_jp4wc_get_address( $address, $raw_address, $order ) {
		$address = $this->jp4wc_get_address( $address, $raw_address, $order );

		$shipping_yomigana_first_name = $order->get_meta( '_shipping_yomigana_first_name', true );
		$shipping_yomigana_last_name  = $order->get_meta( '_shipping_yomigana_last_name', true );
		$shipping_company             = $order->get_shipping_company();
		if ( $shipping_yomigana_first_name && $shipping_yomigana_last_name ) {
			$address .= '<br />(' . $shipping_yomigana_last_name . ' ' . $shipping_yomigana_first_name . ')';
		}
		if ( $shipping_company ) {
			$address .= '<br />' . $shipping_company;
		}

		return $address;
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
}
// Address Fields Class load.
if ( ! get_option( 'wc4jp-no-ja' ) ) {
	new JP4WC_Address_Fields();
}
