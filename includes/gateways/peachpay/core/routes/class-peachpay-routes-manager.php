<?php
/**
 * Sets up and defines the PeachPay rest api endpoints.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * The PeachPay routes API for JS-PHP interaction.
 */
class PeachPay_Routes_Manager {
	/**
	 * Magic constructor method is called on instantiation. Automagically registers all of our endpoints.
	 */
	public function __construct() {
		// Load any custom utilities we may need.
		require_once PEACHPAY_ABSPATH . 'core/util/button.php';

		// Load endpoint files.
		require_once PEACHPAY_ABSPATH . 'core/routes/cart-coupon.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/cart-item-quantity.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/cart-calculation.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/order-create.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/order-payment-status.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/order-note.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/ocu-product-data.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/add-variable-product.php';
		require_once PEACHPAY_ABSPATH . 'core/routes/siteinfo.php';

		// wc-ajax endpoints need initialized right away.
		add_action( 'wc_ajax_pp-cart', 'peachpay_wc_ajax_cart_calculation' );
		add_action( 'wc_ajax_pp-cart-item-quantity', 'peachpay_wc_ajax_product_quantity_changer' );
		add_action( 'wc_ajax_pp-order-create', 'peachpay_wc_ajax_create_order' );
		add_action( 'wc_ajax_pp-order-status', 'peachpay_wc_ajax_order_payment_status' );
		add_action( 'wc_ajax_pp-order-note', 'peachpay_wc_ajax_order_note' );
		add_action( 'wc_ajax_pp-ocu-product', 'peachpay_wc_ajax_ocu_product_data' );
		add_action( 'wc_ajax_pp-get-modal-currency-data', array( $this, 'peachpay_wc_ajax_modal_currency_of_country' ) );
		add_action( 'wc_ajax_pp-set-wc-billing-country', array( $this, 'peachpay_wc_ajax_set_wc_billing_country' ) );
		add_action( 'wc_ajax_pp-validate-checkout', array( $this, 'peachpay_wc_ajax_validate_checkout' ) );
		add_action( 'wc_ajax_pp-add-variation-product', 'peachpay_wc_ajax_add_variable_product' );

		add_action( 'wc_ajax_pp-update-email', array( $this, 'peachpay_wc_ajax_update_email' ) );

		add_action( 'rest_api_init', array( $this, 'peachpay_rest_api_init' ) );
	}

	/**
	 * Load external rest api files and register api endpoints.
	 */
	public function peachpay_rest_api_init() {
		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_health_request' ),
				'permission_callback' => '__return_true',
			)
		);

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
			'/coupon/(?P<code>.*)',
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
				'callback'            => array( $this, 'peachpay_pw_wc_gift_cards_card_rest' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/payment/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'peachpay_change_payment_settings' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/plugin/settings',
			array(
				'methods'             => 'POST,GET',
				'callback'            => array( $this, 'peachpay_change_plugin_settings' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/siteinfo',
			array(
				'methods'             => 'GET',
				'callback'            => 'peachpay_get_site_info',
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Health endpoint.
	 */
	public function handle_health_request() {
		do_action( 'peachpay_plugin_capabilities', peachpay_fetch_plugin_capabilities() );

		return array(
			'plugin_version' => PEACHPAY_VERSION,
			'plugin_mode'    => peachpay_is_test_mode() ? 'test' : 'live',
		);
	}

	/**
	 * Ajax hook for validating checkout field addresses (shipping & billing).
	 */
	public function peachpay_wc_ajax_validate_checkout() {
		// phpcs:ignore
		if ( ! isset( $_POST['ship_to_different_address'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => 'Missing required field "ship_to_different_address"',
					'notices' => wc_get_notices(),
				)
			);
			return;
		}

		// phpcs:ignore
		$request = $_POST;
		$request['ship_to_different_address'] = 'true' === $request['ship_to_different_address'];
		apply_filters( 'peachpay_validation_checks', $request );

		include_once PEACHPAY_ABSPATH . 'core/class-peachpay-wc-checkout.php';
		$checkout_validator = new PeachPay_WC_Checkout();
		$errors             = new WP_Error();
		$checkout_validator->validate_posted_data( $request, $errors );
		if ( $errors->has_errors() ) {
			wp_send_json(
				array(
					'success'        => false,
					'error_messages' => $errors->get_error_messages(),
					'notices'        => wc_get_notices(),
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'notices' => wc_get_notices(),
			)
		);
	}

	/**
	 * Rest API Endpoint for retrieving a gift card and its balance.
	 *
	 * @param WP_REST_Request $request The current HTTP rest request.
	 */
	public function peachpay_pw_wc_gift_cards_card_rest( $request ) {
		return peachpay_cart_applied_gift_card( $request['card_number'] );
	}

	/**
	 * Allows our customer support to change certain payment settings on the store.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 */
	public function peachpay_change_payment_settings( WP_REST_Request $request ) {
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

		if ( isset( $request['test_mode'] ) ) {
			$options['test_mode'] = $request['test_mode'] ? '1' : '';
		}

		if ( isset( $request['general']['data_retention'] ) ) {
			$checkout_window_options['data_retention'] = $request['general']['data_retention'];
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
	public function peachpay_change_plugin_settings( WP_REST_Request $request ) {
		if ( isset( $request['reset_button_preferences'] ) && is_bool( $request['reset_button_preferences'] ) && $request['reset_button_preferences'] ) {
			peachpay_reset_button();
			return array(
				'success'               => true,
				'message'               => 'Button preferences were reset to defaults',
				'requested_changes'     => json_decode( $request->get_body() ),
				'settings_after_change' => array(
					'button' => get_option( 'peachpay_express_checkout_button' ),
				),
			);
		}

		// General Settings.
		$checkout_window_options = get_option( 'peachpay_express_checkout_window', array() );
		$payment_options         = get_option( 'peachpay_payment_options', array() );

		if ( isset( $request['checkout_window'] ) ) {
			if ( isset( $request['checkout_window']['support_message'] ) && is_string( $request['checkout_window']['support_message'] ) ) {
				$checkout_window_options['support_message'] = $request['checkout_window']['support_message'];
			}
			if ( isset( $request['checkout_window']['enable_order_notes'] ) ) {
				$checkout_window_options['enable_order_notes'] = $request['checkout_window']['enable_order_notes'];
			}
			if ( isset( $request['checkout_window']['display_product_images'] ) ) {
				$checkout_window_options['display_product_images'] = $request['checkout_window']['display_product_images'];
			}
			if ( isset( $request['checkout_window']['enable_quantity_changer'] ) ) {
				$checkout_window_options['enable_quantity_changer'] = $request['checkout_window']['enable_quantity_changer'];
			}
		}

		update_option( 'peachpay_express_checkout_window', $checkout_window_options );
		update_option( 'peachpay_payment_options', $payment_options );

		// Button settings.
		$button_options   = get_option( 'peachpay_express_checkout_button', array() );
		$branding_options = get_option( 'peachpay_express_checkout_branding', array() );

		if ( isset( $request['checkout_button'] ) ) {
			// full button settings
			if ( isset( $request['checkout_button']['button_color'] ) && is_string( $request['checkout_button']['button_color'] ) ) {
				$branding_options['button_color'] = $request['checkout_button']['button_color'];
			}
			if ( isset( $request['checkout_button']['button_text_color'] ) && is_string( $request['checkout_button']['button_text_color'] ) ) {
				$branding_options['button_text_color'] = $request['checkout_button']['button_text_color'];
			}
			if ( isset( $request['checkout_button']['button_icon'] ) && is_string( $request['checkout_button']['button_icon'] ) ) {
				$button_options['button_icon'] = $request['checkout_button']['button_icon'];
			}
			if ( isset( $request['checkout_button']['button_border_radius'] ) && is_numeric( $request['checkout_button']['button_border_radius'] ) ) {
				$button_options['button_border_radius'] = $request['checkout_button']['button_border_radius'];
			}
			if ( isset( $request['checkout_button']['peachpay_button_text'] ) && is_string( $request['checkout_button']['peachpay_button_text'] ) ) {
				$button_options['peachpay_button_text'] = $request['checkout_button']['peachpay_button_text'];
			}
			if ( isset( $request['checkout_button']['button_effect'] ) ) {
				$button_options['button_effect'] = $request['checkout_button']['button_effect'];
			}
			if ( isset( $request['checkout_button']['disable_default_font_css'] ) ) {
				$button_options['disable_default_font_css'] = $request['checkout_button']['disable_default_font_css'];
			}
			if ( isset( $request['checkout_button']['button_display_payment_method_icons'] ) ) {
				$button_options['button_display_payment_method_icons'] = $request['checkout_button']['button_display_payment_method_icons'];
			}

			// floating button settings
			if ( isset( $request['checkout_button']['floating_button_enabled'] ) ) {
				$button_options['floating_button_enabled'] = $request['checkout_button']['floating_button_enabled'];
			}
			if ( isset( $request['checkout_button']['floating_button_alignment'] ) && is_string( $request['checkout_button']['floating_button_alignment'] ) ) {
				$button_options['floating_button_alignment'] = $request['checkout_button']['floating_button_alignment'];
			}
			if ( isset( $request['checkout_button']['floating_button_bottom_gap'] ) && is_numeric( $request['checkout_button']['floating_button_bottom_gap'] ) ) {
				$button_options['floating_button_bottom_gap'] = $request['checkout_button']['floating_button_bottom_gap'];
			}
			if ( isset( $request['checkout_button']['floating_button_side_gap'] ) && is_numeric( $request['checkout_button']['floating_button_side_gap'] ) ) {
				$button_options['floating_button_side_gap'] = $request['checkout_button']['floating_button_side_gap'];
			}
			if ( isset( $request['checkout_button']['floating_button_size'] ) && is_numeric( $request['checkout_button']['floating_button_size'] ) ) {
				$button_options['floating_button_size'] = $request['checkout_button']['floating_button_size'];
			}
			if ( isset( $request['checkout_button']['floating_button_icon_size'] ) && is_numeric( $request['checkout_button']['floating_button_icon_size'] ) ) {
				$button_options['floating_button_icon_size'] = $request['checkout_button']['floating_button_icon_size'];
			}

			// Product button settings.
			if ( isset( $request['checkout_button']['product_button_alignment'] ) && is_string( $request['checkout_button']['product_button_alignment'] ) ) {
				$button_options['product_button_alignment'] = $request['checkout_button']['product_button_alignment'];
			}
			if ( isset( $request['checkout_button']['button_width_product_page'] ) && is_numeric( $request['checkout_button']['button_width_product_page'] ) ) {
				$button_options['button_width_product_page'] = $request['checkout_button']['button_width_product_page'];
			}
			if ( isset( $request['checkout_button']['product_button_position'] ) && is_string( $request['checkout_button']['product_button_position'] ) ) {
				$button_options['product_button_position'] = $request['checkout_button']['product_button_position'];
			}
			if ( isset( $request['checkout_button']['display_on_product_page'] ) ) {
				$button_options['display_on_product_page'] = $request['checkout_button']['display_on_product_page'];
			}

			// Cart button settings.
			if ( isset( $request['checkout_button']['cart_button_alignment'] ) && is_string( $request['checkout_button']['cart_button_alignment'] ) ) {
				$button_options['cart_button_alignment'] = $request['checkout_button']['cart_button_alignment'];
			}
			if ( isset( $request['checkout_button']['button_width_cart_page'] ) && is_numeric( $request['checkout_button']['button_width_cart_page'] ) ) {
				$button_options['button_width_cart_page'] = $request['checkout_button']['button_width_cart_page'];
			}
			if ( isset( $request['checkout_button']['cart_page_enabled'] ) ) {
				$button_options['cart_page_enabled'] = $request['checkout_button']['cart_page_enabled'];
			}

			// Checkout button settings.
			if ( isset( $request['checkout_button']['checkout_button_alignment'] ) && is_string( $request['checkout_button']['checkout_button_alignment'] ) ) {
				$button_options['checkout_button_alignment'] = $request['checkout_button']['checkout_button_alignment'];
			}
			if ( isset( $request['checkout_button']['button_width_checkout_page'] ) && is_numeric( $request['checkout_button']['button_width_checkout_page'] ) ) {
				$button_options['button_width_checkout_page'] = $request['checkout_button']['button_width_checkout_page'];
			}
			if ( isset( $request['checkout_button']['outline_enabled'] ) ) {
				$button_options['display_checkout_outline'] = $request['checkout_button']['outline_enabled'];
			}
			if ( isset( $request['checkout_button']['checkout_header_text'] ) && is_string( $request['checkout_button']['checkout_header_text'] ) ) {
				$button_options['checkout_header_text'] = $request['checkout_button']['checkout_header_text'];
			}
			if ( isset( $request['checkout_button']['checkout_subtext_text'] ) && is_string( $request['checkout_button']['checkout_subtext_text'] ) ) {
				$button_options['checkout_subtext_text'] = $request['checkout_button']['checkout_subtext_text'];
			}
			if ( isset( $request['checkout_button']['checkout_page_enabled'] ) ) {
				$button_options['checkout_page_enabled'] = $request['checkout_button']['checkout_page_enabled'];
			}

			// minicart button settings.
			if ( isset( $request['checkout_button']['mini_cart_enabled'] ) ) {
				$button_options['mini_cart_enabled'] = $request['checkout_button']['mini_cart_enabled'];
			}
		}

		update_option( 'peachpay_express_checkout_button', $button_options );

		// Advanced settings.
		$advanced_options = get_option( 'peachpay_express_checkout_advanced', array() );

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

		update_option( 'peachpay_express_checkout_advanced', $advanced_options );

		return array(
			'success'               => true,
			'message'               => 'Successfully updated the button settings; invalid keys were ignored',
			'requested_changes'     => json_decode( $request->get_body() ),
			'settings_after_change' => array(
				'express_checkout/window'   => get_option( 'peachpay_express_checkout_window' ),
				'express_checkout/button'   => get_option( 'peachpay_express_checkout_button' ),
				'express_checkout/advanced' => get_option( 'peachpay_express_checkout_advanced' ),
			),
		);
	}

	/**
	 * Handles a get request, provided a country, responds with the currency code.
	 */
	public function peachpay_wc_ajax_modal_currency_of_country() {
		try {
			$headers = getallheaders();
			$data    = isset( $headers['Currency-Country'] ) ? peachpay_currencies_to_modal_from_country( $headers['Currency-Country'] ) : peachpay_currencies_to_modal_from_country( peachpay_get_client_country() );

			wp_send_json(
				array(
					'success' => true,
					'data'    => $data,
				)
			);
		} catch ( Exception $error ) {
			wp_send_json(
				array(
					'success'       => false,
					'error_message' => $error->getMessage(),
					'notices'       => wc_get_notices(),
				)
			);
		}

		wp_die();
	}

	/**
	 * Called by frontend modal to update wc billing country if the modal has a different
	 * country set for customer.
	 */
	public function peachpay_wc_ajax_set_wc_billing_country() {
		try {
			$response = array(
				'success' => false,
			);

			// phpcs:disable
			if ( isset( $_POST ) && isset( $_POST['country'] ) ) {
				if ( isset( WC()->customer ) ) {
					WC()->customer->set_billing_country( $_POST['country'] );
					$response['success'] = true;

					if ( isset( $_COOKIE ) && isset( $_COOKIE['pp_active_currency'] ) ) {
						$_COOKIE['pp_active_currency'] = $_POST['country'];
					}
				} else {
					$response['error_message'] = 'No associated customer for client';
				}
			} else {
				$response['error_message'] = 'No country provided in body';
			}

			wp_send_json( $response );
		} catch ( Exception $error ) {
			wp_send_json(
				array(
					'success'       => false,
					'error_message' => $error->getMessage(),
					'notices'       => wc_get_notices(),
				)
			);
		}

		wp_die();
	}

	/**
	 * Updates the locally stored email address for an in-progress order, used for analytics.
	 */
	public function peachpay_wc_ajax_update_email() {
		try {
			$response = array(
				'ok' => false,
			);

			// phpcs:disable
			if ( isset( $_POST ) && isset( $_POST['email'] ) ) {
				// Update email
				$response['ok'] = PeachPay_Analytics_Database::update_email( $_POST['email'] );
			} else {
				$response['error_message'] = 'No email provided in body';
			}

			wp_send_json( $response );
		} catch ( Exception $error ) {
			wp_send_json(
				array(
					'ok' => false
				)
			);
		}
		// phpcs:enable
		wp_die();
	}
}
