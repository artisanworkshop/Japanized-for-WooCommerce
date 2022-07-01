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
require_once PEACHPAY_ABSPATH . 'core/routes/ocu-product-data.php';

// wc-ajax enpoints need intilized right away.
add_action( 'wc_ajax_pp-cart', 'peachpay_wc_ajax_cart_calculation' );
add_action( 'wc_ajax_pp-cart-item-quantity', 'peachpay_wc_ajax_product_quantity_changer' );
add_action( 'wc_ajax_pp-order-create', 'peachpay_wc_ajax_create_order' );
add_action( 'wc_ajax_pp-order-status', 'peachpay_wc_ajax_order_payment_status' );
add_action( 'wc_ajax_pp-order-note', 'peachpay_wc_ajax_order_note' );
add_action( 'wc_ajax_pp-create-stripe-payment-intent', 'peachpay_wc_ajax_create_stripe_payment_intent' );
add_action( 'wc_ajax_pp-ocu-product', 'peachpay_wc_ajax_ocu_product_data' );

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
		'/plugin/settings',
		array(
			'methods'             => 'POST,GET',
			'callback'            => 'peachpay_change_plugin_settings',
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
	apply_filters( 'peachpay_validation_checks', $request );
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
 * A GET/POST request API endpoint to make requested setting changes remotely and return the settings.
 *
 * @param WP_REST_Request $request the values for changing the button.
 */
function peachpay_change_plugin_settings( WP_REST_Request $request ) {
	if ( isset( $request['reset_button_preferences'] ) && is_bool( $request['reset_button_preferences'] ) && $request['reset_button_preferences'] ) {
		peachpay_reset_button();
		return array(
			'success'               => true,
			'message'               => 'Button preferences were reset to defaults',
			'requested_changes'     => json_decode( $request->get_body() ),
			'settings_after_change' => array(
				'button' => get_option( 'peachpay_button_options' ),
			),
		);
	}

	// General Settings.
	$general_options = get_option( 'peachpay_general_options', array() );

	if ( isset( $request['general'] ) ) {
		if ( isset( $request['general']['support_message'] ) && is_string( $request['general']['support_message'] ) ) {
			$general_options['support_message'] = $request['general']['support_message'];
		}
		if ( isset( $request['general']['enable_order_notes'] ) ) {
			$general_options['enable_order_notes'] = $request['general']['enable_order_notes'];
		}
		if ( isset( $request['general']['data_retention'] ) ) {
			$general_options['data_retention'] = $request['general']['data_retention'];
		}
		if ( isset( $request['general']['display_product_images'] ) ) {
			$general_options['display_product_images'] = $request['general']['display_product_images'];
		}
		if ( isset( $request['general']['enable_quantity_changer'] ) ) {
			$general_options['enable_quantity_changer'] = $request['general']['enable_quantity_changer'];
		}
	}
	update_option( 'peachpay_general_options', $general_options );

	// Button settings.
	$button_options = get_option( 'peachpay_button_options', array() );

	if ( isset( $request['button_color'] ) && is_string( $request['button_color'] ) ) {
		$button_options['button_color'] = $request['button_color'];
	}
	if ( isset( $request['button_text_color'] ) && is_string( $request['button_text_color'] ) ) {
		$options['button_text_color'] = $request['button_text_color'];
	}
	if ( isset( $request['button_icon'] ) && is_string( $request['button_icon'] ) ) {
		$button_options['button_icon'] = $request['button_icon'];
	}
	if ( isset( $request['button_border_radius'] ) && is_numeric( $request['button_border_radius'] ) ) {
		$button_options['button_border_radius'] = $request['button_border_radius'];
	}
	if ( isset( $request['peachpay_button_text'] ) && is_string( $request['peachpay_button_text'] ) ) {
		$button_options['peachpay_button_text'] = $request['peachpay_button_text'];
	}
	if ( isset( $request['button_effect'] ) ) {
		$button_options['button_effect'] = $request['button_effect'];
	}
	if ( isset( $request['disable_default_font_css'] ) ) {
		$button_options['disable_default_font_css'] = $request['disable_default_font_css'];
	}
	if ( isset( $request['floating_button_enabled'] ) ) {
		$button_options['floating_button_enabled'] = $request['floating_button_enabled'];
	}
	if ( isset( $request['display_on_product_page'] ) ) {
		$button_options['display_on_product_page'] = $request['display_on_product_page'];
	}
	if ( isset( $request['button_display_payment_method_icons'] ) ) {
		$button_options['button_display_payment_method_icons'] = $request['button_display_payment_method_icons'];
	}
	if ( isset( $request['cart_page_enabled'] ) ) {
		$button_options['cart_page_enabled'] = $request['cart_page_enabled'];
	}
	if ( isset( $request['checkout_page_enabled'] ) ) {
		$button_options['checkout_page_enabled'] = $request['checkout_page_enabled'];
	}
	if ( isset( $request['mini_cart_enabled'] ) ) {
		$button_options['mini_cart_enabled'] = $request['mini_cart_enabled'];
	}
	if ( isset( $request['shop'] ) ) {
		if ( isset( $request['shop']['position'] ) && is_string( $request['shop']['position'] ) ) {
			$button_options['floating_button_alignment'] = $request['shop']['position'];
		}
		if ( isset( $request['button']['button_text_color'] ) && is_string( $request['button']['button_text_color'] ) ) {
			$button_options['button_text_color'] = $request['button']['button_text_color'];
		}
		if ( isset( $request['button']['button_icon'] ) && is_string( $request['button']['button_icon'] ) ) {
			$button_options['button_icon'] = $request['button']['button_icon'];
		}
		if ( isset( $request['button']['button_border_radius'] ) && is_numeric( $request['button']['button_border_radius'] ) ) {
			$button_options['button_border_radius'] = $request['button']['button_border_radius'];
		}
		if ( isset( $request['button']['peachpay_button_text'] ) && is_string( $request['button']['peachpay_button_text'] ) ) {
			$button_options['peachpay_button_text'] = $request['button']['peachpay_button_text'];
		}
		if ( isset( $request['button']['button_effect'] ) ) {
			$button_options['button_effect'] = $request['button']['button_effect'];
		}
		if ( isset( $request['button']['disable_default_font_css'] ) ) {
			$button_options['disable_default_font_css'] = $request['button']['disable_default_font_css'];
		}
		if ( isset( $request['button']['disabled_floating_button'] ) ) {
			$button_options['disabled_floating_button'] = $request['button']['disabled_floating_button'];
		}
		if ( isset( $request['button']['hide_on_product_page'] ) ) {
			$button_options['hide_on_product_page'] = $request['button']['hide_on_product_page'];
		}
		if ( isset( $request['button']['disabled_cart_page'] ) ) {
			$button_options['disabled_cart_page'] = $request['button']['disabled_cart_page'];
		}
		if ( isset( $request['button']['disabled_checkout_page'] ) ) {
			$button_options['disabled_checkout_page'] = $request['button']['disabled_checkout_page'];
		}
		if ( isset( $request['button']['disabled_mini_cart'] ) ) {
			$button_options['disabled_mini_cart'] = $request['button']['disabled_mini_cart'];
		}
		if ( isset( $request['button']['button_hide_payment_method_icons'] ) ) {
			$button_options['button_hide_payment_method_icons'] = $request['button']['button_hide_payment_method_icons'];
		}

		// Shop button settings.
		if ( isset( $request['button']['floating_button_alignment'] ) && is_string( $request['button']['floating_button_alignment'] ) ) {
			$button_options['floating_button_alignment'] = $request['button']['floating_button_alignment'];
		}
		if ( isset( $request['button']['floating_button_bottom_gap'] ) && is_numeric( $request['button']['floating_button_bottom_gap'] ) ) {
			$button_options['floating_button_bottom_gap'] = $request['button']['floating_button_bottom_gap'];
		}
		if ( isset( $request['button']['floating_button_side_gap'] ) && is_numeric( $request['button']['floating_button_side_gap'] ) ) {
			$button_options['floating_button_side_gap'] = $request['button']['floating_button_side_gap'];
		}
		if ( isset( $request['button']['floating_button_size'] ) && is_numeric( $request['button']['floating_button_size'] ) ) {
			$button_options['floating_button_size'] = $request['button']['floating_button_size'];
		}
		if ( isset( $request['button']['floating_button_icon_size'] ) && is_numeric( $request['button']['floating_button_icon_size'] ) ) {
			$button_options['floating_button_icon_size'] = $request['button']['floating_button_icon_size'];
		}

		// Product button settings.
		if ( isset( $request['button']['product_button_alignment'] ) && is_string( $request['button']['product_button_alignment'] ) ) {
			$button_options['product_button_alignment'] = $request['button']['product_button_alignment'];
		}
		if ( isset( $request['button']['button_width_product_page'] ) && is_numeric( $request['button']['button_width_product_page'] ) ) {
			$button_options['button_width_product_page'] = $request['button']['button_width_product_page'];
		}
		if ( isset( $request['button']['product_button_position'] ) && is_string( $request['button']['product_button_position'] ) ) {
			$button_options['product_button_position'] = $request['button']['product_button_position'];
		}

		// Cart buttong settings.
		if ( isset( $request['button']['cart_button_alignment'] ) && is_string( $request['button']['cart_button_alignment'] ) ) {
			$button_options['cart_button_alignment'] = $request['button']['cart_button_alignment'];
		}
		if ( isset( $request['button']['button_width_cart_page'] ) && is_numeric( $request['button']['button_width_cart_page'] ) ) {
			$button_options['button_width_cart_page'] = $request['button']['button_width_cart_page'];
		}

		// Checkout button settings.
		if ( isset( $request['button']['checkout_button_alignment'] ) && is_string( $request['button']['checkout_button_alignment'] ) ) {
			$button_options['checkout_button_alignment'] = $request['button']['checkout_button_alignment'];
		}
		if ( isset( $request['button']['button_width_checkout_page'] ) && is_numeric( $request['button']['button_width_checkout_page'] ) ) {
			$button_options['button_width_checkout_page'] = $request['button']['button_width_checkout_page'];
		}
		if ( isset( $request['checkout']['outline_enabled'] ) ) {
			$button_options['display_checkout_outline'] = $request['checkout']['outline_enabled'];
		}
		if ( isset( $request['button']['checkout_header_text'] ) && is_string( $request['button']['checkout_header_text'] ) ) {
			$button_options['checkout_header_text'] = $request['button']['checkout_header_text'];
		}
		if ( isset( $request['button']['checkout_subtext_text'] ) && is_string( $request['button']['checkout_subtext_text'] ) ) {
			$button_options['checkout_subtext_text'] = $request['button']['checkout_subtext_text'];
		}
	}
	update_option( 'peachpay_button_options', $button_options );

	// Advanced settings.
	$advanced_options = get_option( 'peachpay_advanced_options', array() );

	if ( isset( $request['advanced'] ) ) {
		if ( isset( $request['advanced']['custom_button_css'] ) && is_string( $request['advanced']['custom_button_css'] ) ) {
			$advanced_options['custom_button_css'] = $request['advanced']['custom_button_css'];
		}
		if ( isset( $request['advanced']['custom_button_class'] ) && is_string( $request['advanced']['custom_button_class'] ) ) {
			$advanced_options['custom_button_class'] = $request['advanced']['custom_button_class'];
		}
		if ( isset( $request['advanced']['custom_checkout_css'] ) && is_string( $request['advanced']['custom_checkout_css'] ) ) {
			$advanced_options['custom_checkout_css'] = $request['advanced']['custom_checkout_css'];
		}
	}
	update_option( 'peachpay_advanced_options', $advanced_options );

	return array(
		'success'               => true,
		'message'               => 'Successfully updated the button settings; invalid keys were ignored',
		'requested_changes'     => json_decode( $request->get_body() ),
		'settings_after_change' => array(
			'general'  => get_option( 'peachpay_general_options' ),
			'button'   => get_option( 'peachpay_button_options' ),
			'advanced' => get_option( 'peachpay_advanced_options' ),
		),
	);
}

/** Returns a boolean to check wheather the given email exist .
 *
 * @param WP_REST_Request $request the email to be check .
 */
function peachpay_check_email( WP_REST_Request $request ) {
		return array( 'emailExists' => email_exists( $request['email'] ) );
}
