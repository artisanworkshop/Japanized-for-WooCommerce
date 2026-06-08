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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_order_enqueue_style' ), 20 );
		add_filter( 'woocommerce_customer_meta_fields', array( $this, 'admin_customer_meta_fields' ) );

		add_filter( 'woocommerce_email_preview_dummy_order', array( $this, 'jp4wc_email_preview_dummy_order' ), 10 );
		add_filter( 'woocommerce_email_preview_dummy_address', array( $this, 'jp4wc_email_preview_dummy_address' ), 10 );
		add_filter( 'woocommerce_email_preview_dummy_product', array( $this, 'jp4wc_email_preview_dummy_product' ), 10 );
		add_filter( 'woocommerce_email_preview_dummy_product_variation', array( $this, 'jp4wc_email_preview_dummy_product_variation' ), 10 );
		// Remove WC Additional Checkout Fields API duplicates from My Account address edit form.
		add_filter( 'woocommerce_address_to_edit', array( $this, 'remove_duplicate_yomigana_from_address_edit' ), 20, 2 );
		// Suppress WC Additional Fields API rendering on My Account address view page.
		add_action( 'woocommerce_my_account_after_my_address', array( $this, 'suppress_wc_additional_fields_on_account_view' ), 9 );
	}

	/**
	 * Yomigana Setting
	 *
	 * @since 2.2.7
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function add_yomigana_fields( $fields ) {
		// Check if block checkout is being used.
		$checkout_page_id   = wc_get_page_id( 'checkout' );
		$has_block_checkout = $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );

		// If block checkout is being used, skip adding classic checkout fields.
		// The block checkout fields are handled by JP4WC_Yomigana_Blocks_Integration.
		if ( $has_block_checkout ) {
			return $fields;
		}

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
	 * @param  array $args The address data array.
	 * @return array
	 */
	public function address_replacements( $fields, $args ) {
		// Ensure standard name fields are always set.
		if ( ! isset( $fields['{first_name}'] ) && isset( $args['first_name'] ) ) {
			$fields['{first_name}'] = $args['first_name'];
		}
		if ( ! isset( $fields['{last_name}'] ) && isset( $args['last_name'] ) ) {
			$fields['{last_name}'] = $args['last_name'];
		}

		// Apply honorific suffix here (PHP-only): WooCommerce Blocks JS replaces format
		// placeholders client-side and never calls this filter, so 様 is kept out of the
		// format string itself (see address_formats()).
		if ( get_option( 'wc4jp-honorific-suffix' ) && isset( $fields['{first_name}'] ) && isset( $args['country'] ) && 'JP' === $args['country'] ) {
			$fields['{first_name}'] .= '様';
		}

		if ( get_option( 'wc4jp-yomigana' ) ) {
			$fields['{yomigana_last_name}']  = isset( $args['yomigana_last_name'] ) ? $args['yomigana_last_name'] : '';
			$fields['{yomigana_first_name}'] = isset( $args['yomigana_first_name'] ) ? $args['yomigana_first_name'] : '';
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
		$include_yomigana = get_option( 'wc4jp-yomigana' );
		$include_company  = get_option( 'wc4jp-company-name' );
		$country_inner    = $this->show_country_in_address() ? '{country}' : '';

		// For the WooCommerce Blocks checkout (JS rendering), the name placeholder must appear
		// first in the format string so checkout-frontend.js can identify and strip it before
		// replacing address-only placeholders. Custom placeholders like {yomigana_*} are not
		// in the JS replacement table and would appear verbatim if included here.
		// Honorific suffix (様) is applied in address_replacements() for PHP-only contexts.
		if ( $this->is_blocks_checkout_context() ) {
			$parts = array( '{last_name} {first_name}', '〒{postcode}', '{state}{city}{address_1}', '{address_2}' );
			if ( $include_company ) {
				$parts[] = '{company}';
			}
			if ( $country_inner ) {
				$parts[] = $country_inner;
			}
			$fields['JP'] = implode( "\n", $parts );
			return $fields;
		}

		// PHP rendering contexts (order emails, admin, My Account, order confirmation).
		$set_yomigana = $include_yomigana ? "\n{yomigana_last_name} {yomigana_first_name}" : '';

		if ( $include_company ) {
			$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}\n{company}" . $set_yomigana . "\n{last_name} {first_name}\n" . $country_inner;
		} else {
			$fields['JP'] = "〒{postcode}\n{state}{city}{address_1}\n{address_2}" . $set_yomigana . "\n{last_name} {first_name}\n" . $country_inner;
		}

		if ( is_cart() ) {
			$fields['JP'] = '〒{postcode}{state}{city}';
		}
		if ( is_order_received_page() ) {
			$fields['JP'] = $fields['JP'] . "\n {phone}";
		}

		return $fields;
	}

	/**
	 * Detect whether the current request is for the WooCommerce Blocks checkout.
	 *
	 * WC Blocks JS (checkout-frontend.js) renders the address summary client-side using
	 * countryData[country]['format'], which is embedded by Checkout::enqueue_data() on the
	 * checkout page load. Store API REST calls also need the JS-compatible format so that
	 * server-side validation receives the same structure.
	 *
	 * @return bool
	 */
	private function is_blocks_checkout_context() {
		// Admin (non-AJAX) is never a JS-rendered blocks context.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}
		// Order-received (thank-you) page uses PHP rendering even after a blocks checkout.
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return false;
		}
		// Email sending always requires PHP rendering with {yomigana_*} in the format string.
		// Blocks checkout triggers order emails synchronously during the Store API request,
		// so this guard must come before the REST_REQUEST check below.
		if ( $this->is_email_context() ) {
			return false;
		}
		// WooCommerce Store API REST calls (checkout block submits here).
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( false !== strpos( $uri, '/wc/store/' ) ) {
				return true;
			}
		}
		// Checkout page: enqueue_data() embeds countryData here for the blocks JS.
		// Classic checkout does not display a formatted address string during checkout,
		// so returning the blocks-compatible format for is_checkout() is safe for both
		// classic and blocks setups. FSE themes are also covered since is_checkout() works
		// regardless of whether the block appears in post content or a site template.
		return function_exists( 'is_checkout' ) && is_checkout();
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
		// Determine which meta key set to prefer based on the configured checkout type.
		// Block checkout saves to _wc_{type}/jp4wc/* keys; classic checkout saves to {type}_yomigana_* keys.
		// When both are present (e.g. after switching checkout types), use the key that matches the
		// current checkout configuration so the most recently entered value is shown.
		// When the checkout page is not configured (no page ID), we cannot detect the type and
		// default to block (WC Additional Fields API) keys, matching the block-preferred fallback.
		$checkout_page_id     = wc_get_page_id( 'checkout' );
		$has_classic_checkout = $checkout_page_id && ! has_block( 'woocommerce/checkout', $checkout_page_id );

		$block_last    = get_user_meta( $customer_id, '_wc_' . $name . '/jp4wc/yomigana_last_name', true );
		$block_first   = get_user_meta( $customer_id, '_wc_' . $name . '/jp4wc/yomigana_first_name', true );
		$classic_last  = get_user_meta( $customer_id, $name . '_yomigana_last_name', true );
		$classic_first = get_user_meta( $customer_id, $name . '_yomigana_first_name', true );

		if ( $has_classic_checkout ) {
			// Classic checkout: prefer classic keys, fall back to block.
			$fields['yomigana_last_name']  = ! empty( $classic_last ) ? $classic_last : $block_last;
			$fields['yomigana_first_name'] = ! empty( $classic_first ) ? $classic_first : $block_first;
		} else {
			// Block checkout or no checkout page configured: prefer block keys, fall back to classic.
			$fields['yomigana_last_name']  = ! empty( $block_last ) ? $block_last : $classic_last;
			$fields['yomigana_first_name'] = ! empty( $block_first ) ? $block_first : $classic_first;
		}

		$fields['phone'] = get_user_meta( $customer_id, $name . '_phone', true );

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
		if ( isset( $_GET['preview_woocommerce_mail'] ) || empty( $order ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $fields;
		}
		// Try classic checkout format first, then fall back to block checkout format.
		$fields['yomigana_first_name'] = $order->get_meta( '_billing_yomigana_first_name', true );
		if ( empty( $fields['yomigana_first_name'] ) ) {
			$fields['yomigana_first_name'] = $order->get_meta( '_wc_billing/jp4wc/yomigana_first_name', true );
		}
		$fields['yomigana_last_name'] = $order->get_meta( '_billing_yomigana_last_name', true );
		if ( empty( $fields['yomigana_last_name'] ) ) {
			$fields['yomigana_last_name'] = $order->get_meta( '_wc_billing/jp4wc/yomigana_last_name', true );
		}
		$fields['phone'] = $order->get_billing_phone();

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
			if ( isset( $_GET['preview_woocommerce_mail'] ) || empty( $order ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return $fields;
			}

			// Try classic checkout format first, then fall back to block checkout format.
			$fields['yomigana_first_name'] = $order->get_meta( '_shipping_yomigana_first_name', true );
			if ( empty( $fields['yomigana_first_name'] ) ) {
				$fields['yomigana_first_name'] = $order->get_meta( '_wc_shipping/jp4wc/yomigana_first_name', true );
			}
			$fields['yomigana_last_name'] = $order->get_meta( '_shipping_yomigana_last_name', true );
			if ( empty( $fields['yomigana_last_name'] ) ) {
				$fields['yomigana_last_name'] = $order->get_meta( '_wc_shipping/jp4wc/yomigana_last_name', true );
			}
			$fields['phone'] = $order->get_shipping_phone();
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
			if ( empty( $address['yomigana_first_name'] ) ) {
				$address['yomigana_first_name'] = $args->get_meta( '_wc_billing/jp4wc/yomigana_first_name', true );
			}
			$address['yomigana_last_name'] = $args->get_meta( '_billing_yomigana_last_name', true );
			if ( empty( $address['yomigana_last_name'] ) ) {
				$address['yomigana_last_name'] = $args->get_meta( '_wc_billing/jp4wc/yomigana_last_name', true );
			}
		} else {
			$address['yomigana_first_name'] = $args->get_meta( '_shipping_yomigana_first_name', true );
			if ( empty( $address['yomigana_first_name'] ) ) {
				$address['yomigana_first_name'] = $args->get_meta( '_wc_shipping/jp4wc/yomigana_first_name', true );
			}
			$address['yomigana_last_name'] = $args->get_meta( '_shipping_yomigana_last_name', true );
			if ( empty( $address['yomigana_last_name'] ) ) {
				$address['yomigana_last_name'] = $args->get_meta( '_wc_shipping/jp4wc/yomigana_last_name', true );
			}
			$address['phone'] = $args->get_shipping_phone();
		}
		return $address;
	}

	/**
	 * Check whether the country should be shown in formatted addresses.
	 *
	 * Returns false when the store is configured to sell to only one country,
	 * since showing the country adds no information for the customer or merchant.
	 *
	 * @return bool
	 */
	private function show_country_in_address() {
		if ( ! function_exists( 'WC' ) || ! isset( WC()->countries ) ) {
			return true;
		}
		return count( WC()->countries->get_allowed_countries() ) > 1;
	}

	/**
	 * Check if we are in an email sending context.
	 *
	 * @return bool True if in email context, false otherwise.
	 */
	private function is_email_context() {
		// Primary check: WC_Email::get_content() sets $sending = true before rendering.
		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			foreach ( WC()->mailer()->get_emails() as $email ) {
				if ( $email->sending ) {
					return true;
				}
			}
		}

		// Fallback: check if WC_Email is anywhere in the call stack.
		if ( function_exists( 'debug_backtrace' ) ) {
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			foreach ( $backtrace as $trace ) {
				if ( isset( $trace['class'] ) && false !== strpos( $trace['class'], 'WC_Email' ) ) {
					return true;
				}
			}
		}
		return false;
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
	 * Enqueues inline CSS for the admin order edit screen to override WooCommerce default
	 * field float directions for Japanese name/address ordering.
	 *
	 * WooCommerce CSS hardcodes ._billing_last_name_field as float:right and first_name as
	 * float:left. In Japanese order (last_name first in DOM), we swap these so that the
	 * visual display shows 姓(last) on the left and 名(first) on the right.
	 *
	 * @since  2.9.9
	 * @return void
	 */
	public function admin_order_enqueue_style() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Match HPOS edit screen (woocommerce_page_wc-orders or admin_page_wc-orders for restricted users)
		// and classic post-based shop_order screen.
		$hpos_page     = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_order_page = in_array( $screen->id, array( 'woocommerce_page_wc-orders', 'admin_page_wc-orders', 'shop_order' ), true )
			|| 'wc-orders' === $hpos_page;

		if ( ! $is_order_page ) {
			return;
		}

		$css = '
#order_data .order_data_column ._billing_first_name_field,
#order_data .order_data_column ._shipping_first_name_field,
#order_data .order_data_column ._billing_city_field,
#order_data .order_data_column ._shipping_city_field {
	float: right;
	clear: right;
}
#order_data .order_data_column ._billing_last_name_field,
#order_data .order_data_column ._shipping_last_name_field,
#order_data .order_data_column ._billing_state_field,
#order_data .order_data_column ._shipping_state_field {
	float: left;
	clear: left;
}
';
		// Attach inline CSS directly after woocommerce_admin_styles so our rules
		// appear later in the document and override WC's admin.css with equal specificity.
		// Running at priority 20 guarantees WC's admin_styles() (priority 10) has already
		// registered and enqueued woocommerce_admin_styles before this callback fires.
		wp_add_inline_style( 'woocommerce_admin_styles', $css );
	}

	/**
	 * Setting edit item in the billing address of the admin screen for Japanese.
	 *
	 * @since  1.2
	 * @version 2.9.9
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function admin_billing_address_fields( $fields ) {
		// Japanese address field order.
		$jp_order = array(
			'last_name',
			'first_name',
			'yomigana_last_name',
			'yomigana_first_name',
			'country',
			'postcode',
			'state',
			'city',
			'address_1',
			'address_2',
			'company',
			'email',
			'phone',
		);

		// Add yomigana fields if not already present.
		if ( ! isset( $fields['yomigana_last_name'] ) ) {
			$fields['yomigana_last_name'] = array(
				'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			);
		}
		if ( ! isset( $fields['yomigana_first_name'] ) ) {
			$fields['yomigana_first_name'] = array(
				'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			);
		}

		// Reorder: first by jp_order, then any remaining unknown fields (e.g. added by WooCommerce extensions).
		$ordered = array();
		foreach ( $jp_order as $key ) {
			if ( isset( $fields[ $key ] ) ) {
				$ordered[ $key ] = $fields[ $key ];
			}
		}
		foreach ( $fields as $key => $value ) {
			if ( ! isset( $ordered[ $key ] ) ) {
				$ordered[ $key ] = $value;
			}
		}

		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $ordered['company'] );
		}
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			unset( $ordered['yomigana_last_name'], $ordered['yomigana_first_name'] );
		} else {
			// Ensure yomigana fields display side-by-side using WC's column convention.
			if ( isset( $ordered['yomigana_last_name'] ) ) {
				$ordered['yomigana_last_name']['wrapper_class'] = '';
			}
			if ( isset( $ordered['yomigana_first_name'] ) ) {
				$ordered['yomigana_first_name']['wrapper_class'] = 'last';
			}
		}

		return $ordered;
	}

	/**
	 * Setting edit item in the shipping address of the admin screen for Japanese.
	 *
	 * @since  1.2
	 * @version 2.9.9
	 * @param  array $fields The formatted address fields.
	 * @return array
	 */
	public function admin_shipping_address_fields( $fields ) {
		// Japanese address field order.
		$jp_order = array(
			'last_name',
			'first_name',
			'yomigana_last_name',
			'yomigana_first_name',
			'country',
			'postcode',
			'state',
			'city',
			'company',
			'address_1',
			'address_2',
			'phone',
		);

		// Add yomigana fields if not already present.
		if ( ! isset( $fields['yomigana_last_name'] ) ) {
			$fields['yomigana_last_name'] = array(
				'label' => __( 'Last Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			);
		}
		if ( ! isset( $fields['yomigana_first_name'] ) ) {
			$fields['yomigana_first_name'] = array(
				'label' => __( 'First Name Yomigana', 'woocommerce-for-japan' ),
				'show'  => false,
			);
		}

		// Ensure shipping phone field is shown with correct label.
		if ( ! isset( $fields['phone'] ) ) {
			$fields['phone'] = array(
				'label' => __( 'Phone', 'woocommerce-for-japan' ),
				'show'  => false,
			);
		} else {
			$fields['phone']['show'] = false;
		}

		// Reorder: first by jp_order, then any remaining unknown fields (e.g. added by WooCommerce extensions).
		$ordered = array();
		foreach ( $jp_order as $key ) {
			if ( isset( $fields[ $key ] ) ) {
				$ordered[ $key ] = $fields[ $key ];
			}
		}
		foreach ( $fields as $key => $value ) {
			if ( ! isset( $ordered[ $key ] ) ) {
				$ordered[ $key ] = $value;
			}
		}

		if ( ! get_option( 'wc4jp-company-name' ) ) {
			unset( $ordered['company'] );
		}
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			unset( $ordered['yomigana_last_name'], $ordered['yomigana_first_name'] );
		} else {
			// Ensure yomigana fields display side-by-side using WC's column convention.
			if ( isset( $ordered['yomigana_last_name'] ) ) {
				$ordered['yomigana_last_name']['wrapper_class'] = '';
			}
			if ( isset( $ordered['yomigana_first_name'] ) ) {
				$ordered['yomigana_first_name']['wrapper_class'] = 'last';
			}
		}

		return $ordered;
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

	/**
	 * Remove WC Additional Checkout Fields API yomigana duplicates from My Account address edit.
	 *
	 * WC's CheckoutFieldsFrontend::edit_address_fields() (priority 10) always injects
	 * _wc_{type}/jp4wc/* fields into the My Account address edit form. On classic-checkout
	 * sites (and FSE block-checkout sites where has_block() returns false) JP4WC's own
	 * {type}_yomigana_* fields are also present, causing a second yomigana row below the
	 * email field with an incorrect name attribute.
	 *
	 * When the traditional {type}_yomigana_last_name field is present we remove the
	 * WC-added duplicates so only the traditional fields (positioned near the name section)
	 * remain visible.
	 *
	 * On non-FSE block-checkout sites add_yomigana_fields() returns early, so the
	 * traditional field is absent. In that case this guard is false and the WC-added
	 * fields are left intact (single display via block API).
	 *
	 * @since 2.9.12
	 * @param array  $address      Address fields array for the edit form.
	 * @param string $address_type 'billing' or 'shipping'.
	 * @return array
	 */
	public function remove_duplicate_yomigana_from_address_edit( $address, $address_type ) {
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			return $address;
		}
		if ( ! isset( $address[ $address_type . '_yomigana_last_name' ] ) ) {
			return $address;
		}
		$namespace_prefix = '_wc_' . $address_type . '/jp4wc/';
		foreach ( array_keys( $address ) as $key ) {
			if ( 0 === strpos( $key, $namespace_prefix ) ) {
				unset( $address[ $key ] );
			}
		}
		return $address;
	}

	/**
	 * Suppress WC Additional Checkout Fields rendering on the My Account address view page.
	 *
	 * On the My Account address view page, is_blocks_checkout_context() always returns false,
	 * so address_formats() always takes the PHP rendering path and includes {yomigana_*} in
	 * the JP format string (both classic and block checkout sites). WC's CheckoutFieldsFrontend::
	 * render_address_fields() (priority 10) would then render yomigana again as a bare
	 * <br><strong> line, causing a duplicate entry below the formatted address block.
	 *
	 * This callback (priority 9) removes WC's render_address_fields hook whenever yomigana is
	 * enabled, preventing the duplicate. It runs for both classic and block checkout setups.
	 *
	 * @since 2.9.12
	 * @param string $address_type 'billing' or 'shipping'.
	 * @return void
	 */
	public function suppress_wc_additional_fields_on_account_view( $address_type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! get_option( 'wc4jp-yomigana' ) ) {
			return;
		}
		$hook     = 'woocommerce_my_account_after_my_address';
		$priority = 10;
		// Guard: skip if the hook has no callbacks at all.
		if ( ! has_action( $hook ) ) {
			return;
		}
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook ] ) || ! ( $wp_filter[ $hook ] instanceof WP_Hook ) ) {
			return;
		}
		// Use remove_action() rather than directly mutating $wp_filter internals.
		// Iterate over a snapshot so removal does not affect the current loop.
		foreach ( $wp_filter[ $hook ]->callbacks[ $priority ] ?? array() as $callback_data ) {
			$fn = $callback_data['function'];
			if (
				is_array( $fn )
				&& is_object( $fn[0] )
				&& false !== strpos( get_class( $fn[0] ), 'CheckoutFields' )
				&& method_exists( $fn[0], 'render_address_fields' )
			) {
				remove_action( $hook, $fn, $priority );
			}
		}
	}
}
// Address Fields Class load.
if ( ! get_option( 'wc4jp-no-ja' ) ) {
	new JP4WC_Address_Fields();
}
