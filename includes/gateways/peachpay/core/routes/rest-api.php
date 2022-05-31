<?php
/**
 * Sets up and defines the PeachPay rest api endpoints.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

define( 'PEACHPAY_ROUTE_BASE', 'peachpay/v1' );

// Load any custom utilities we may need.
require_once PEACHPAY_ABSPATH . 'core/util/button.php';
require_once PEACHPAY_ABSPATH . 'core/routes/rest-api-utility.php';

// Load endpoint files.
require_once PEACHPAY_ABSPATH . 'core/routes/cart-coupon.php';
require_once PEACHPAY_ABSPATH . 'core/routes/cart-item-quantity.php';
require_once PEACHPAY_ABSPATH . 'core/routes/cart-calculation.php';
require_once PEACHPAY_ABSPATH . 'core/routes/order-create.php';
require_once PEACHPAY_ABSPATH . 'core/routes/payment-intent-create.php';
require_once PEACHPAY_ABSPATH . 'core/routes/order-payment-status.php';
require_once PEACHPAY_ABSPATH . 'core/routes/order-note.php';

// wc-ajax enpoints need intilized right away.
add_action( 'wc_ajax_pp-cart', 'peachpay_wc_ajax_cart_calculation' );
add_action( 'wc_ajax_pp-cart-item-quantity', 'peachpay_wc_ajax_product_quantity_changer' );
add_action( 'wc_ajax_pp-order-create', 'peachpay_wc_ajax_create_order' );
add_action( 'wc_ajax_pp-order-status', 'peachpay_wc_ajax_order_payment_status' );
add_action( 'wc_ajax_pp-order-note', 'peachpay_wc_ajax_order_note' );
add_action( 'wc_ajax_pp-create-stripe-payment-intent', 'peachpay_wc_ajax_create_stripe_payment_intent' );

/**
 * Load external rest api files and register api endpoints.
 */
function peachpay_rest_api_init() {

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/order/status',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_rest_api_order_payment_status',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		PEACHPAY_ROUTE_BASE,
		'/coupon/(?P<code>[-\w]+.)',
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
		'/payment/settings',
		array(
			'methods'             => 'POST',
			'callback'            => 'peachpay_change_payment_settings',
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

/**
 * RestAPI Endpoint for validating checkout address.
 *
 * @param WP_REST_Request $request The request object.
 */
function peachpay_validate_checkout_rest( $request ) {
	// This is needed because there was a theme which had a filter that ran upon
	// the hook `woocommerce_checkout_fields`. This also helps in preventing
	// other errors where filters try to load the cart.
	peachpay_wc_load_cart();

	include_once PEACHPAY_ABSPATH . 'core/class-peachpay-wc-checkout.php';
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
 * Allows our customer support to change certain payment settings on the store.
 *
 * @param WP_REST_Request $request The incoming request.
 */
function peachpay_change_payment_settings( WP_REST_Request $request ) {
	$options = get_option( 'peachpay_payment_options' );

	if ( isset( $request['stripeGoogleApplePayEnabled'] ) ) {
		$options['stripe_payment_request'] = $request['stripeGoogleApplePayEnabled'] ? '1' : '';
	}

	if ( isset( $request['stripeEnabled'] ) ) {
		$options['enable_stripe'] = $request['stripeEnabled'] ? '1' : '';
	}

	if ( isset( $request['paypalEnabled'] ) ) {
		$options['paypal'] = $request['paypalEnabled'] ? '1' : '';
	}

	update_option( 'peachpay_payment_options', $options );
	return array(
		'success'             => true,
		'message'             => 'Successfully updated the payment settings. Invalid keys were ignored.',
		'incomingRequestBody' => json_decode( $request->get_body() ),
		'settingsAfterChange' => get_option( 'peachpay_payment_options' ),
	);
}

/**
 * A POST request API to change the peachpay button remotely.
 *
 * @param WP_REST_Request $request the values for changing the button.
 */
function peachpay_change_button_settings( WP_REST_Request $request ) {
	if ( isset( $request['reset_button_preferences'] ) && is_bool( $request['reset_button_preferences'] ) && $request['reset_button_preferences'] ) {
		peachpay_reset_button();
		return array(
			'success'             => true,
			'message'             => 'Button preferences were reset to defaults',
			'requestedChanges'    => json_decode( $request->get_body() ),
			'settingsAfterChange' => get_option( 'peachpay_button_options' ),
		);
	}
	$options = get_option( 'peachpay_button_options' );
	if ( isset( $request['button_color'] ) && is_string( $request['button_color'] ) ) {
		$options['button_color'] = $request['button_color'];
	}
	if ( isset( $request['button_icon'] ) && is_string( $request['button_icon'] ) ) {
		$options['button_icon'] = $request['button_icon'];
	}
	if ( isset( $request['button_border_radius'] ) && is_numeric( $request['button_border_radius'] ) ) {
		$options['button_border_radius'] = $request['button_border_radius'];
	}
	if ( isset( $request['peachpay_button_text'] ) && is_string( $request['peachpay_button_text'] ) ) {
		$options['peachpay_button_text'] = $request['peachpay_button_text'];
	}
	if ( isset( $request['button_sheen'] ) ) {
		$options['button_sheen'] = $request['button_sheen'];
	}
	if ( isset( $request['button_fade'] ) ) {
		$options['button_fade'] = $request['button_fade'];
	}
	if ( isset( $request['hide_on_product_page'] ) ) {
		$options['hide_on_product_page'] = $request['hide_on_product_page'];
	}
	if ( isset( $request['button_hide_payment_method_icons'] ) ) {
		$options['button_hide_payment_method_icons'] = $request['button_hide_payment_method_icons'];
	}
	if ( isset( $request['product'] ) ) {
		if ( isset( $request['product']['alignment'] ) && is_string( $request['product']['alignment'] ) ) {
			$options['product_button_alignment'] = $request['product']['alignment'];
		}
		if ( isset( $request['product']['width'] ) && is_numeric( $request['product']['width'] ) ) {
			$options['button_width_product_page'] = $request['product']['width'];
		}
		if ( isset( $request['product']['position'] ) && is_string( $request['product']['position'] ) ) {
			$options['product_button_position'] = $request['product']['position'];
		}
	}
	if ( isset( $request['cart'] ) ) {
		if ( isset( $request['cart']['alignment'] ) && is_string( $request['cart']['alignment'] ) ) {
			$options['cart_button_alignment'] = $request['cart']['alignment'];
		}
		if ( isset( $request['cart']['width'] ) && is_numeric( $request['cart']['width'] ) ) {
			$options['button_width_cart_page'] = $request['cart']['width'];
		}
	}
	if ( isset( $request['checkout'] ) ) {
		if ( isset( $request['checkout']['alignment'] ) && is_string( $request['checkout']['alignment'] ) ) {
			$options['checkout_button_alignment'] = $request['checkout']['alignment'];
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
