<?php
/**
 * Sets up and defines the PeachPay rest api endpoints.
 *
 * @package PeachPay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'PEACHPAY_ROUTE_BASE', 'peachpay/v1' );

/**
 * Load external rest api files and register api endpoints.
 */
function peachpay_rest_api_init() {

	// Load any custom utilities we may need.
	require_once plugin_dir_path( __FILE__ ) . 'rest-api-utility.php';
	require_once plugin_dir_path( __FILE__ ) . '../util/reset-button.php';

	// Load endpoint files.
	require_once plugin_dir_path( __FILE__ ) . 'peachpay-coupon.php';
	require_once plugin_dir_path( __FILE__ ) . 'peachpay-variation-details.php';
	require_once plugin_dir_path( __FILE__ ) . 'peachpay-cart-calculation.php';

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/cart/calculation',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_cart_calculation',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/variation/details',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_variation_details_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/product/(?P<id>\d+)/price',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_product_price_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/coupon/(?P<code>\w+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_coupon_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/checkout/validate',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_validate_checkout_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/woo-discount-rules/discount/product',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_wdr_discount_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'compatibility/pw-wc-gift-cards/card/(?P<card_number>.+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_pw_wc_gift_cards_card_rest',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/plugin',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_check_plugin_status',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/cart/list',
		array(
			'methods'             => 'GET',
			'callback'            => 'peachpay_cart_list',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/button/settings',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_change_button_settings',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/check/email',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_check_email',
			'permission_callback' => '__return_true',
		)
	);
}

add_action( 'rest_api_init', 'peachpay_rest_api_init' );

function peachpay_product_price_rest( $data ) {
	$currency = $data->get_param( 'currency' );
	$price    = peachpay_product_price( wc_get_product( $data['id'] ) );
	return peachpay_convert_currency_rest_api( $price, $currency );
}

function peachpay_validate_checkout_rest( WP_REST_Request $request ) {
	// This is needed because there was a theme which had a filter that ran upon
	// the hook `woocommerce_checkout_fields`. This also helps in preventing
	// other errors where filters try to load the cart.
	peachpay_wc_load_cart();

	include_once plugin_dir_path( __FILE__ ) . '../class-peachpay-wc-checkout.php';
	$checkout_validator = new PeachPay_WC_Checkout();
	$errors             = new WP_Error();
	$checkout_validator->validate_posted_data( $request, $errors );
	if ( $errors->has_errors() ) {
		return $errors;
	}
}

/**
 * Rest API Endpoint for retrieving a gift card and its balance.
 *
 * @param WP_REST_Request $request The current HTTP rest request.
 */
function peachpay_pw_wc_gift_cards_card_rest( $request ) {
	return peachpay_cart_applied_gift_card( $request['card_number'] );
}

/**
 * Retrieves the peachpay version information.
 */
function peachpay_check_plugin_status() {
	return array(
		'merchantName'  => get_bloginfo( 'name' ),
		'hasValidKey'   => peachpay_has_valid_key(),
		'pluginVersion' => PEACHPAY_VERSION,
		'currentTime'   => current_time( 'Y-m-d H:i:s' ),
	);
}

/**
 * Gets details around the current customer cart.
 */
function peachpay_cart_list() {
	include_once WP_PLUGIN_DIR . '/peachpay-for-woocommerce/peachpay.php';
	peachpay_wc_load_cart();
	return array(
		'product_or_full_cart'    => peachpay_get_cart(),
		'full_cart'               => peachpay_make_cart_from_WC_cart( WC()->cart->get_cart() ),
		'coupons'                 => WC()->cart->get_coupon_discount_totals(),
		'cart_applied_gift_cards' => peachpay_cart_applied_gift_cards(),
	);
}

/**
 * A POST request API to change the peachpay button remotely.
 *
 * @param WP_REST_Request $request the values for changing the button.
 */
function peachpay_change_button_settings( WP_REST_Request $request ) {
	if ( isset( $request['reset_default'] ) && is_bool( $request['reset_default'] ) && $request['reset_default'] ) {
		peachpay_reset_button();
		return array(
			'success'             => true,
			'message'             => 'Button preferences were reset to defaults',
			'requestedChanges'    => json_decode( $request->get_body() ),
			'settingsAfterChange' => get_option( 'peachpay_button_options' ),
		);
	}
	$options = get_option( 'peachpay_button_options' );
	if ( isset( $request['color_value'] ) && is_string( $request['color_value'] ) ) {
		$options['button_color'] = $request['color_value'];
	}
	if ( isset( $request['icon_option'] ) && is_string( $request['icon_option'] ) ) {
		$options['button_icon'] = $request['icon_option'];
	}
	if ( isset( $request['border_radius'] ) && is_numeric( $request['border_radius'] ) ) {
		$options['button_border_radius'] = $request['border_radius'];
	}
	if ( isset( $request['button_text'] ) && is_string( $request['button_text'] ) ) {
		$options['peachpay_button_text'] = $request['button_text'];
	}
	if ( isset( $request['button_sheen_option'] ) ) {
		$options['button_sheen'] = $request['button_sheen_option'];
	}
	if ( isset( $request['hide_on_product_page_option'] ) ) {
		$options['hide_on_product_page'] = $request['hide_on_product_page_option'];
	}
	if ( isset( $request['button_hide_payment_method_icons_option'] ) ) {
		$options['button_hide_payment_method_icons'] = $request['button_hide_payment_method_icons_option'];
	}
	if ( isset( $request['product'] ) ) {
		if ( isset( $request['product']['position'] ) && is_string( $request['product']['position'] ) ) {
			$options['product_button_position'] = $request['product']['position'];
		}
		if ( isset( $request['product']['width'] ) && is_numeric( $request['product']['width'] ) ) {
			$options['button_width_product_page'] = $request['product']['width'];
		}
	}
	if ( isset( $request['cart'] ) ) {
		if ( isset( $request['cart']['position'] ) && is_string( $request['cart']['position'] ) ) {
			$options['cart_button_position'] = $request['cart']['position'];
		}
		if ( isset( $request['cart']['width'] ) && is_numeric( $request['product']['width'] ) ) {
			$options['button_width_cart_page'] = $request['cart']['width'];
		}
	}
	if ( isset( $request['checkout'] ) ) {
		if ( isset( $request['checkout']['position'] ) && is_string( $request['checkout']['position'] ) ) {
			$options['checkout_button_position'] = $request['checkout']['position'];
		}
		if ( isset( $request['checkout']['width'] ) && is_numeric( $request['checkout']['width'] ) ) {
			$options['button_width_checkout_page'] = $request['checkout']['width'];
		}
	}
	update_option( 'peachpay_button_options', $options );
	return array(
		'success'             => true,
		'message'             => 'Successfully updated the button settings; invalid keys were ignored',
		'requestedChanges'    => json_decode( $request->get_body() ),
		'settingsAfterChange' => get_option( 'peachpay_button_options' ),
	);
}

/** Returns a boolean to check wheather the given email exist .
 *
 * @param WP_REST_Request $request the email to be check .
 */
function peachpay_check_email( WP_REST_Request $request ) {
		return array( 'emailExists' => email_exists( $request['email'] ) );
}
