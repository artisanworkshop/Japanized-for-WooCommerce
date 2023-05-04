<?php
/**
 * Abstract PeachPay payment gateway
 *
 * Handles PeachPay's generic payment gateway functionality
 * which is extended by individual payment gateways.
 *
 * @class PeachPay_Payment_Gateway
 * @package PeachPay/Payments
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

require_once PEACHPAY_ABSPATH . 'core/payments/peachpay/class-peachpay-payments-integration.php';

/**
 * PeachPay Payment Gateway class.
 *
 * Extended by PeachPay's gateways
 *
 * @class       PeachPay_Payment_Gateway
 * @extends     WC_Payment_Gateway
 * @package     PeachPay
 */
abstract class PeachPay_Payment_Gateway extends WC_Payment_Gateway {
	/**
	 * Absolute maximum limit, set by the gateway.
	 *
	 * @var float
	 */
	public $max_amount = INF;

	/**
	 * Absolute minimum limit, set by the gateway.
	 *
	 * @var float
	 */
	public $min_amount = 0;

	/**
	 * Custom maximum, set by the merchant. Zero means unrestricted.
	 *
	 * @var float
	 */
	public $custom_max_amount = 0;

	/**
	 * Custom minimum, set by the merchant.
	 *
	 * @var float
	 */
	public $custom_min_amount = 0;

	/**
	 * The currency used when rendering the min/max.
	 *
	 * @var string
	 */
	public $min_max_currency = 'USD';

	/**
	 * Country list, set by the gateway.
	 *
	 * @var null|array
	 */
	public $countries = null;

	/**
	 * Whitelist or blacklist the countries list, set by the gateway.
	 *
	 * @var string "allow" or "block"
	 */
	public $country_availability = 'allow';

	/**
	 * Custom country list, set by the merchant.
	 *
	 * @var null|array
	 */
	public $custom_countries = null;

	/**
	 * Whitelist or blacklist the custom countries list, set by the merchant.
	 *
	 * @var string "allow" or "block"
	 */
	public $custom_country_availability = 'allow';

	/**
	 * Currency list, set by the gateway.
	 *
	 * @var null|array
	 */
	public $currencies = null;

	/**
	 * Whitelist or blacklist the currencies list, set by the merchant.
	 *
	 * @var string "allow" or "block"
	 */
	public $currency_availability = 'allow';

	/**
	 * Custom currency list, set by the merchant.
	 *
	 * @var null|array
	 */
	public $custom_currencies = null;

	/**
	 * Whitelist or blacklist the custom currencies list, set by the merchant.
	 *
	 * @var string "allow" or "block"
	 */
	public $custom_currency_availability = 'allow';

	/**
	 * To be used if a payment provider needs it.
	 *
	 * @var int
	 */
	public $settings_priority = 0;

	/**
	 * List of the form fields to display on the gateway settings page.
	 *
	 * A gateway should array merge the parent form fields to the top
	 * of its form fields.
	 *
	 * @var array
	 */
	public $form_fields = array();

	/**
	 * Payment provider title used for breadcrumbs
	 *
	 * @var string
	 */
	public $payment_provider;

	/**
	 * Specify what variety of gateway this is.
	 *
	 * Ex: "Cards", "Digital wallet"
	 *
	 * @var void|string
	 */
	public $payment_method_family;

	/**
	 * Base PeachPay gateway.
	 */
	public function __construct() {
		$this->plugin_id  = 'peachpay_';
		$this->has_fields = true;
		$this->title      = ( $this->title ) ? $this->title : $this->method_title;

		$global_fields = $this->enabled_setting( array() );
		$global_fields = $this->active_locations_setting( $global_fields );
		$global_fields = $this->gateway_title_setting( $global_fields );
		$global_fields = $this->custom_min_amount_settings( $global_fields );
		$global_fields = $this->custom_max_amount_settings( $global_fields );
		$global_fields = $this->currency_filter_settings( $global_fields );
		$global_fields = $this->country_filter_setting( $global_fields );
		$global_fields = $this->fee_settings( $global_fields, $this->title );

		$this->form_fields = array_merge(
			$global_fields,
			$this->form_fields
		);

		$this->init_settings();
		$this->hooks();
	}

	/**
	 * Common gateway hooks.
	 */
	public function hooks() {
		add_filter( 'peachpay_register_feature', array( $this, 'register_feature' ) );
		add_action( 'woocommerce_settings_checkout', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action(
			'woocommerce_cart_calculate_fees',
			function( $cart ) {
				return $this->calculate_payment_method_fee( $cart );
			}
		);

		add_filter( 'woocommerce_gateway_title', array( $this, 'get_title_filter' ), 1000, 2 );

		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );
		}
	}

	/**
	 * Indicates if the checkout should refresh when the payment method is selected
	 */
	protected function should_refresh_checkout() {
		if ( $this->get_option( 'fees_enabled' ) === 'yes' ) {
			return 'true';
		} else {
			return 'false';
		}
	}

	/**
	 * Registers the gateway as a feature for the PeachPay SDK. Override in child
	 * classes to add special metadata if needed.
	 *
	 * @param array $feature_list The list of features.
	 */
	public function register_feature( $feature_list ) {
		$feature_list[ $this->id . '_gateway' ] = array(
			'enabled'  => 'yes' === $this->enabled,
			'version'  => 1,
			'metadata' => array(
				'minimum_charge'       => floatval( $this->get_minimum_charge() ),
				// Checkout modal treats 0 as unrestricted
				'maximum_charge'       => INF === $this->get_maximum_charge() ? 0 : floatval( $this->get_maximum_charge() ),
				'supported_currencies' => $this->get_supported_currencies(),
				'supported_countries'  => $this->get_supported_countries(),
				'title'                => $this->get_title(),
			),
		);

		return $feature_list;
	}

	/**
	 * Registers fee to the cart based on selected payment method.
	 *
	 * @param WC_Cart $cart Current selected cart.
	 */
	private function calculate_payment_method_fee( $cart ) {
		if ( $this->get_option( 'fees_enabled' ) !== 'yes' ) {
			return;
		}
		// Check existence of session
		// Check correct payment method to only add fees for that method
		if ( ! WC() || ! isset( WC()->session ) || WC()->session->get( 'chosen_payment_method' ) !== $this->id ) {
			return;
		}

		$payment_method_fee = array(
			'name'              => $this->get_option( 'fee_reason' ),
			'fee_type'          => $this->get_option( 'fee_type' ),
			'fee_amount'        => floatval( $this->get_option( 'fee_amount' ) ),
			'fee_threshold_min' => $this->get_option( 'fee_threshold_min' ),
			'fee_threshold_max' => $this->get_option( 'fee_threshold_max' ),
		);

		// Only compute rate for fixed amounts.
		if ( 0 === strcmp( 'fixed', $payment_method_fee['fee_type'] ) ) {
			$payment_method_fee['fee_amount']        = peachpay_update_raw_price_per_product( $payment_method_fee['fee_amount'] );
			$payment_method_fee['fee_threshold_min'] = peachpay_update_raw_price_per_product( $payment_method_fee['fee_threshold_min'] );
			$payment_method_fee['fee_threshold_max'] = peachpay_update_raw_price_per_product( $payment_method_fee['fee_threshold_max'] );
		}

		if ( $payment_method_fee['fee_amount'] ) {
			$cart_total = WC()->cart->cart_contents_total;

			// Check cart total is within threshold
			$cart_total_above_min = ! array_key_exists( 'fee_threshold_min', $payment_method_fee ) ||
				$cart_total >= $payment_method_fee['fee_threshold_min'];
			$cart_total_below_max = ! array_key_exists( 'fee_threshold_max', $payment_method_fee ) ||
				! $payment_method_fee['fee_threshold_max'] ||
				$cart_total <= $payment_method_fee['fee_threshold_max'];

			if ( $cart_total_above_min && $cart_total_below_max ) {
				$fee_amount = 0 === strcmp( 'percentage', $payment_method_fee['fee_type'] ) ?
					$cart_total * ( $payment_method_fee['fee_amount'] / 100 ) :
					$payment_method_fee['fee_amount'];

				$cart->add_fee( $payment_method_fee['name'], $fee_amount, true, '' );
			}
		}
	}

	/**
	 * Handles determining if the gateway should be shown or not.
	 */
	public function is_available() {
		if ( $this->needs_setup() ) {
			return false;
		}

		if ( ! $this->enabled || 'no' === $this->enabled ) {
			return false;
		}

		if ( defined( 'PEACHPAY_CHECKOUT' ) && 'checkout_page_only' === $this->get_option( 'active_locations' ) ) {
			return false;
		}

		if ( ! defined( 'PEACHPAY_CHECKOUT' ) && 'express_checkout_only' === $this->get_option( 'active_locations' ) ) {
			return false;
		}

		// Ensure gateway is not seen when PeachPay is in test mode.
		if ( peachpay_is_test_mode() && ! ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
			return false;
		}

		if ( WC()->cart ) {
			$order_total = $this->get_order_total();

			// Minimum charge
			$minimum_charge = $this->get_minimum_charge();
			if ( 0 < $order_total && 0 < $minimum_charge && $minimum_charge > $order_total ) {
				return false;
			}

			// Maximum charge
			$maximum_charge = $this->get_maximum_charge();
			if ( 0 < $order_total && 0 < $maximum_charge && $maximum_charge < $order_total ) {
				return false;
			}
		}

		$currencies = $this->get_supported_currencies();
		if ( is_array( $currencies ) && ! in_array( get_woocommerce_currency(), $currencies, true ) ) {
			return false;
		}

		if ( is_object( WC()->customer ) && method_exists( WC()->customer, 'get_billing_country' ) ) {

			$countries       = $this->get_supported_countries();
			$billing_country = WC()->customer->get_billing_country();
			if ( is_array( $countries ) && ! in_array( $billing_country, $this->get_supported_countries(), true ) && '' !== $billing_country && null !== $billing_country ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate gateway specific order fields.
	 */
	public function validate_fields() {
		// PHPCS:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$result = true;

		$transaction_id = isset( $_POST['peachpay_session_id'] ) ? wp_unslash( $_POST['peachpay_session_id'] ) : null;
		if ( ! $transaction_id ) {
			wc_add_notice( __( 'Missing required field "peachpay_session_id"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		$transaction_id = isset( $_POST['peachpay_transaction_id'] ) ? wp_unslash( $_POST['peachpay_transaction_id'] ) : null;
		if ( ! $transaction_id ) {
			wc_add_notice( __( 'Missing required field "peachpay_transaction_id"', 'peachpay-for-woocommerce' ), 'error' );
			$result = false;
		}

		// PHPCS:enable

		return $result;
	}

	/**
	 * This should be used by the child classes to start the process_payment cycle.
	 *
	 * @param int $order_id The order id to process.
	 * @return WC_Order
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Make sure every attempt the order status is in the default ("pending") status.
		$default_status = apply_filters( 'woocommerce_default_order_status', 'pending' );
		if ( $order->get_status() !== $default_status ) {
			$order->update_status( $default_status, __( 'Customer attempting payment again.', 'peachpay-for-woocommerce' ) );
			$order->save();
		}

		$this->add_payment_meta( $order );

		return $order;
	}

	/**
	 * Messaging to indicate if the payment method is in test mode or not.
	 */
	protected function payment_field_test_mode_notice() {
		if ( peachpay_is_test_mode() ) {
			?>
			<div class="peachpay-testmode">
			<?php esc_html_e( 'Test mode enabled.', 'peachpay-for-woocommerce' ); ?>
				<br>
			<?php esc_html_e( 'Customers cannot see this payment method.', 'peachpay-for-woocommerce' ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Template for displaying tokenize errors to customers.
	 */
	protected function payment_field_tokenize_error_notice() {
		?>
		<div class="peachpay-tokenize-error hide"></div>
		<?php
	}

	/**
	 * Template for displaying 'powered by' messaging
	 */
	protected function payment_field_powered_by_notice() {
		if ( ! peachpay_plugin_has_capability( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) ) ) {
			?>
			<div class='peachpay-native-powered-by-notice'>
				Powered by
				<a href="https://peachpay.app">
					<?php require PeachPay::get_plugin_path() . '/public/img/pp-full-purple.svg'; ?>
				</a>
			</div>
			<?php
		}
	}

	/**
	 * Renders the Payment method form.
	 */
	public function payment_method_form() {
		?>
			<div>
				<?php echo $this->get_icon( true );//PHPCS:ignore ?>
				<p style="text-align: left; margin: 0.5rem 0 0;">
					<?php
					// translators: %s: gateway title
					echo esc_html( sprintf( __( '%s selected for checkout.', 'peachpay-for-woocommerce' ), $this->title ) );
					?>
				</p>
				<?php if ( $this->description ) : ?>
					<hr style="margin: 0.5rem 0;"/>
					<p style="text-align: left; margin: 0; font-size: smaller;" class="muted">
						<?php
						if ( ! isset( $this->order_button_text ) ) {
							$this->order_button_text = __( 'Place order', 'peachpay-for-woocommerce' );
						}
                        // PHPCS:ignore
                        echo sprintf( $this->description, "<b>$this->order_button_text</b>" );
						?>
					<p>
				<?php endif; ?>
			</div>
		<?php
	}

	/**
	 * Renders the default payment fields.
	 */
	public function payment_fields() {
		?>
		<div style="display:none" data-should-refresh-checkout="<?php echo esc_attr( $this->should_refresh_checkout() ); ?>"></div>
		<?php
		$this->payment_field_test_mode_notice();
		$this->payment_field_tokenize_error_notice();

		if ( $this->supports( 'tokenization' ) && is_checkout() && get_current_user_id() !== 0 ) {
				$this->tokenization_script();
				$this->saved_payment_methods();
			?>
				<div class="form-row woocommerce-SavedPaymentMethods-saveNew woocommerce-validated">
				<?php
				$this->payment_method_form();
				?>
				</div>
				<?php
				$this->save_payment_method_checkbox();
		} else {
			$this->payment_method_form();
		}

		$this->payment_field_powered_by_notice();
	}

	/**
	 * Gets the breadcrumb links for the gateway settings page.
	 */
	protected function get_settings_breadcrumbs() {
		return array(
			array(
				'name' => __( 'Payments', 'peachpay-for-woocommerce' ),
				'url'  => PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', null, '#' . strtolower( $this->payment_provider ), false ),
			),
			array(
				'name' => $this->title,
			),
		);
	}

	/**
	 * Outputs the admin settings html.
	 */
	public function admin_options() {
		$bread_crumbs = $this->get_settings_breadcrumbs();
		$gateway      = $this;

		PeachPay_Onboarding_Tour::complete_section( 'viewed-first-gateway' );
		$plugin_capabilities = peachpay_fetch_plugin_capabilities();
		PeachPay_Onboarding_Tour::display_onboarding_tour( ! peachpay_plugin_has_capability( 'woocommerce_premium', $plugin_capabilities ) );

		?>
			<div class="peachpay peachpay-container">
				<?php require PeachPay::get_plugin_path() . '/core/admin/views/html-primary-navigation.php'; ?>
				<div class="pp-admin-content-wrapper">
					<?php require PeachPay::get_plugin_path() . '/core/admin/views/html-side-navigation.php'; ?>
					<div class="pp-admin-content">
						<?php require PeachPay::get_plugin_path() . '/core/admin/views/html-gateway-details.php'; ?>
						<?php $this->action_needed_form(); ?>

						<div id="peachpay-gateway-settings" class="settings-container">
							<h1><?php esc_html_e( 'Settings', 'peachpay-for-woocommerce' ); ?></h1>
							<hr>
							<table class="form-table">
								<?php echo $this->generate_settings_html( $this->get_form_fields(), false ); // PHPCS:ignore ?>
							</table>
							<div class="peachpay-notices-container"></div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Adds scripts and styles for Gateway Admin settings.
	 */
	public function enqueue_admin_scripts() {}

	/**
	 * Enqueue frontend scripts for gateway payments.
	 */
	public function enqueue_checkout_scripts() {}

	/**
	 * Render a needs action message for the gateway.
	 */
	protected function action_needed_form(){}

	/**
	 * Gets the minimum charge taking into account the custom and absolute minimum.
	 */
	public function get_minimum_charge() {
		$min        = $this->min_amount;
		$custom_min = $this->custom_min_amount;

		// Custom cannot be lower then absolute min.
		if ( $custom_min < $min ) {
			return $min;
		}

		if ( $custom_min > $min ) {
			return $custom_min;
		}

		return $min;
	}

	/**
	 * Gets the maximum charge taking into account the custom and absolute maximum. If zero then no maximum is defined.
	 */
	public function get_maximum_charge() {
		$max        = $this->max_amount;
		$custom_max = $this->custom_max_amount;

		// INF is unrestricted
		if ( INF === $max ) {
			return $custom_max;
		}

		// Custom cannot be higher then absolute max.
		if ( $custom_max > $max ) {
			return $max;
		}

		if ( 0 !== $custom_max && $custom_max < $max ) {
			return $custom_max;
		}

		return $max;
	}

	/**
	 * Gets an array of supported currencies taking into account the custom and absolute supported
	 * currencies.
	 *
	 * @return array|null Empty array indicates no supported currencies. null represents not restricted.
	 */
	public function get_supported_currencies() {
		$result = null;

		if ( is_array( $this->currencies ) ) {
			if ( 'allow' === $this->currency_availability ) {
				$result = $this->currencies;
			} elseif ( 'block' === $this->currency_availability ) {
				$result = array_diff( array_keys( get_woocommerce_currencies() ), $this->currencies );
			}
		}
		// If merchant configure the custom_currencies, then custom_currencies should return an array of keys.
		// Otherwise, it would be the same as $this->currencies.
		if ( is_array( $this->custom_currencies ) && array_diff( $this->custom_currencies, $this->currencies ) && 'all' !== $this->custom_currency_availability ) {
			$currencies = null;
			foreach ( $this->custom_currencies as $key ) {
				if ( $this->currencies[ $key ] ) {
					$currencies[] = $this->currencies[ $key ];
				}
			}
			if ( 'allow' === $this->custom_currency_availability ) {
				$result = $currencies;
			} elseif ( 'block' === $this->custom_currency_availability ) {
				$result = array_diff( $this->currencies, $currencies );
			}
		}
		// When "Select none" button is clicked.
		if ( is_array( $this->custom_currencies ) && ! array_diff( $this->custom_currencies, $this->currencies ) && 'allow' === $this->custom_currency_availability ) {
			$result = array();
		}

		return $result;
	}

	/**
	 * Gets an array of supported countries taking into account the custom and absolute supported
	 * countries.
	 *
	 * @return array|null Empty array indicates no supported countries. null represents not restricted.
	 */
	public function get_supported_countries() {
		$result = null;

		if ( is_array( $this->countries ) ) {
			if ( 'allow' === $this->country_availability ) {
				$result = $this->countries;
			} elseif ( 'block' === $this->country_availability ) {
				$result = array_diff( array_keys( WC()->countries->get_countries() ), $this->countries );
			}
		}

		if ( is_array( $this->custom_countries ) && array_diff( $this->custom_countries, $this->countries ) && 'all' !== $this->custom_country_availability ) {
			$countries = null;
			foreach ( $this->custom_countries as $key ) {
				if ( $this->countries[ $key ] ) {
					$countries[] = $this->countries[ $key ];
				}
			}
			if ( 'allow' === $this->custom_country_availability ) {
				$result = $countries;
			} elseif ( 'block' === $this->custom_country_availability ) {
				$result = array_diff( $this->countries, $countries );
			}
		}
		// When "Select none" button is clicked.
		if ( is_array( $this->custom_countries ) && ! array_diff( $this->custom_countries, $this->countries ) && 'allow' === $this->custom_country_availability ) {
			$result = array();
		}

		return $result;
	}

	/**
	 * Return the gateway's icon.
	 *
	 * @param boolean $flex Whether to place the icon in a flex container or not.
	 *
	 * @return string
	 */
	public function get_icon( $flex = false ) {
		ob_start();
		if ( $this->icon ) {
			?>
			<span style="<?php echo $flex ? 'display:flex;' : ''; ?>gap: 0.2rem;margin-left: 0.4rem;align-items:center">
				<img style="max-height:1.4rem;" gateway="<?php echo $this->id; ?>" src="<?php echo WC_HTTPS::force_https_url( $this->icon );//PHPCS:ignore ?>" alt="<?php esc_attr( $this->get_title() ); ?>" />
			</span>
			<?php
		}
		$icon = ob_get_clean();

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Gets a link to this gateways settings.
	 */
	public function get_settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id );
	}

	/**
	 * Helper "hack" to get expose protected array items.
	 *
	 * @param array $protected_items The items to expose.
	 */
	private function get_protected( $protected_items ) {
		return array_map(
			function ( WC_Data $item ) {
				return $item->get_data();
			},
			$protected_items
		);
	}

	/**
	 * Gets the expected order data result for a successful order.
	 * In the future this should be rethought how its structured and what to actually include.
	 *
	 * @deprecated Use get_order_details instead for new code.
	 *
	 * @param WC_Order $order .
	 */
	protected function prepare_payment_result( $order ) {
		$result = array(
			'id'       => $order->get_id(),
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
			'number'   => $order->get_order_number(),
			'details'  => $order->get_data(),
		);

		// If we don't do the below, the end result will be something like
		// "line_items": {"972": {}}, which is not useful because we can't
		// see the line item details. This is because json_encode which runs
		// behind the scenes ignores protected data. We can forcefully
		// un-protect these.
		$result['details']['line_items']     = $this->get_protected( $order->get_items() );
		$result['details']['shipping_lines'] = $this->get_protected( $order->get_shipping_methods() );
		$result['details']['fee_lines']      = $this->get_protected( $order->get_fees() );
		$result['details']['coupon_lines']   = $this->get_protected( $order->get_coupons() );

		// This is not usually part of the WooCommerce order object, but
		// we want to avoid doing math on money whenever possible and so
		// would rather set it here.
		$result['details']['fee_total'] = number_format( $order->get_total_fees() ?? '0', 2 );

		return $result;
	}

	/**
	 * Adds the peachpay order meta elements such as payment variation, test mode, and has subscription.
	 *
	 * @param WC_Order $order The order to add meta to.
	 */
	protected function add_payment_meta( $order ) {
		if ( function_exists( 'peachpay_wcs_order_has_subscription' ) && peachpay_wcs_order_has_subscription( $order ) ) {
			$order->add_meta_data( 'has_subscription', true );
		}

		if ( peachpay_is_test_mode() ) {
			$order->add_meta_data( 'peachpay_is_test_mode', true );
		}
	}

	/**
	 * Gets order details needed to create a payment.
	 *
	 * @param WC_Order $order The order to collect details for.
	 */
	protected function get_order_details( $order ) {
		return array(
			'id'                    => strval( $order->get_id() ),
			'parent_id'             => $order->get_parent_id() === 0 ? null : strval( $order->get_parent_id() ),
			'number'                => strval( $order->get_order_number() ),

			'merchant_name'         => get_bloginfo( 'name' ),
			'merchant_url'          => get_site_url(),
			'merchant_callback_url' => $this->get_callback_url(),

			'customer_id'           => strval( $order->get_customer_id() ),
			'customer_ip'           => $this->get_customer_ip( $order ),
			'customer_useragent'    => $this->get_customer_user_agent( $order ),

			'payment_method'        => $order->get_payment_method(),
			'payment_method_title'  => $order->get_payment_method_title(),
			'currency'              => $order->get_currency(),
			'status'                => $order->get_status(),

			'billing'               => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
				'line_1'     => $order->get_billing_address_1(),
				'line_2'     => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'region'     => $order->get_billing_state(),
				'postal'     => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
			),
			'shipping'              => array(
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'email'      => '',
				'phone'      => $order->get_shipping_phone(),
				'line_1'     => $order->get_shipping_address_1(),
				'line_2'     => $order->get_shipping_address_2(),
				'city'       => $order->get_shipping_city(),
				'region'     => $order->get_shipping_state(),
				'postal'     => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			),
			'item_lines'            => $this->get_order_line_items( $order ),
			'subtotal_line'         => array(
				'label' => __( 'Subtotal', 'peachpay-for-woocommerce' ),
				'total' => strval( $order->get_subtotal() ),
			),
			'shipping_lines'        => array(
				array(
					'label' => __( 'Shipping', 'peachpay-for-woocommerce' ),
					'total' => strval( $order->get_shipping_total() ),
				),
			),
			'fee_lines'             => array(
				array(
					'label' => __( 'Fee', 'peachpay-for-woocommerce' ),
					'total' => strval( $order->get_total_fees() ? $order->get_total_fees() : 0 ),
				),
			),
			'discount_lines'        => array(
				array(
					'label' => __( 'Discount', 'peachpay-for-woocommerce' ),
					'total' => strval( $order->get_total_discount() ),
				),
			),
			'tax_lines'             => array(
				array(
					'label' => __( 'Tax', 'peachpay-for-woocommerce' ),
					'total' => strval( $order->get_total_tax() ),
				),
			),
			'total_line'            => array(
				'label' => __( 'Total', 'peachpay-for-woocommerce' ),
				'total' => strval( $order->get_total() ),
			),
		);
	}

	/**
	 * Get the user IP address from the provided order with fallbacks.
	 *
	 * @param WC_Order $order to get ip from.
	 * @return null|string
	 */
	protected function get_customer_ip( $order ) {
		$customer_ip = $order->get_customer_ip_address();
		if ( ! empty( $customer_ip ) ) {
			return $customer_ip;
		}

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return wp_kses_post( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return null;
	}

	/**
	 * Get the user agent from the provided order with fallbacks.
	 *
	 * @param WC_Order $order to get user agent from.
	 * @return null|string
	 */
	protected function get_customer_user_agent( $order ) {
		$customer_user_agent = $order->get_customer_user_agent();
		if ( ! empty( $customer_user_agent ) ) {
			return $customer_user_agent;
		}

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return wp_kses_post( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return null;
	}

	/**
	 * Gets the order line items.
	 *
	 * @param WC_Order $order The order to retrieve line items from.
	 */
	private function get_order_line_items( $order ) {
		$line_items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product      = $item->get_product();
			$line_items[] = array(
				'id'        => strval( $product->get_id() ),
				'label'     => $product->get_title(),
				'amount'    => strval( $product->get_price() ),
				'quantity'  => $item->get_quantity(),
				'total'     => strval( $item->get_total() ),
				'image_url' => ( is_array( peachpay_product_image( $product ) ) && isset( peachpay_product_image( $product )[0] ) ) ? peachpay_product_image( $product )[0] : null,
			);
		}

		return $line_items;
	}

	/**
	 * Gets the endpoint to callback the store for any payment related status changes.
	 */
	protected function get_callback_url() {
		return get_rest_url( null, 'peachpay/v1/order/status' );
	}

	/**
	 * Gets the payment description.
	 *
	 * @param WC_Order $order Order details.
	 * @param boolean  $is_subscription If the description should be for a subscription.
	 */
	public static function get_payment_description( $order, $is_subscription = false ) {
		if ( $is_subscription ) {
			return get_bloginfo( 'name' ) . ' - Subscription Order ' . $order->get_order_number() . ' (PeachPay)';
		} else {
			return get_bloginfo( 'name' ) . ' - Order ' . $order->get_order_number() . ' (PeachPay)';
		}
	}

	/**
	 * Currency settings for a gateway.
	 *
	 * @param array $form_fields The current fields.
	 */
	private function currency_filter_settings( $form_fields ) {
		if ( null === $this->currencies ) {
			$this->currencies = array_keys( get_woocommerce_currencies() );
		}

		$this->custom_currencies            = (array) $this->get_option( 'custom_currencies', $this->currencies );
		$this->custom_currency_availability = $this->get_option( 'custom_currency_availability', 'all' );

		$description = __( 'When the currency matches one of these values, the payment method will be shown on the checkout page.', 'peachpay-for-woocommerce' );
		if ( 'block' === $this->custom_currency_availability ) {
			$description = __( 'When the currency matches one of these values, the payment method will not be shown on the checkout page.', 'peachpay-for-woocommerce' );
		}
		return array_merge(
			$form_fields,
			array(
				'custom_currency_availability' => array(
					'type'    => 'select',
					'title'   => __( 'Currency availability', 'peachpay-for-woocommerce' ),
					'class'   => 'pp-custom-currency-availability',
					'options' => array(
						'all'   => __( 'All supported currencies', 'peachpay-for-woocommerce' ),
						'allow' => __( 'Allow specific currencies', 'peachpay-for-woocommerce' ),
						'block' => __( 'Block specific currencies', 'peachpay-for-woocommerce' ),
					),
					'default' => 'all',
				),
				'custom_currencies'            => array(
					'type'        => 'multiselect',
					'title'       => '',
					'description' => $description,
					'class'       => 'wc-enhanced-select n pp-custom-currencies',
					'options'     => $this->currencies,
					'default'     => array_map( 'strval', array_keys( $this->custom_currencies ? $this->custom_currencies : array() ) ),
				),
			)
		);
	}

	/**
	 * Country settings for a gateway.
	 *
	 * @param array $form_fields The current fields.
	 */
	private function country_filter_setting( $form_fields ) {
		if ( null === $this->countries ) {
			$this->countries = array_keys( WC()->countries->get_countries() );
		}

		$this->custom_countries            = (array) $this->get_option( 'custom_countries', $this->countries );
		$this->custom_country_availability = $this->get_option( 'custom_country_availability', 'all' );

		$description = __( 'When the billing country matches one of these values, the payment method will be shown on the checkout page.', 'peachpay-for-woocommerce' );
		if ( 'block' === $this->custom_country_availability ) {
			$description = __( 'When the billing country matches one of these values, the payment method will not be shown on the checkout page.', 'peachpay-for-woocommerce' );
		}
		return array_merge(
			$form_fields,
			array(
				'custom_country_availability' => array(
					'type'    => 'select',
					'title'   => __( 'Country availability', 'peachpay-for-woocommerce' ),
					'class'   => 'pp-custom-country-availability',
					'options' => array(
						'all'   => __( 'All supported billing countries', 'peachpay-for-woocommerce' ),
						'allow' => __( 'Allow specific billing countries', 'peachpay-for-woocommerce' ),
						'block' => __( 'Block specific billing countries', 'peachpay-for-woocommerce' ),
					),
					'default' => 'all',
				),
				'custom_countries'            => array(
					'type'        => 'multiselect',
					'title'       => '',
					'description' => $description,
					'class'       => 'wc-enhanced-select pp-custom-countries',
					'options'     => $this->countries,
					'default'     => array_map( 'strval', array_keys( $this->custom_countries ? $this->custom_countries : array() ) ),
				),
			)
		);
	}

	/**
	 * Gateway custom min settings
	 *
	 * @param array $form_fields The current fields.
	 */
	private function custom_min_amount_settings( $form_fields ) {
		$this->custom_min_amount = floatval( $this->get_option( 'custom_min_amount', $this->min_amount ) );

		return array_merge(
			$form_fields,
			array(
				'custom_min_amount' => array(
					'type'              => 'number',
					'title'             => __( 'Minimum charge', 'peachpay-for-woocommerce' ),
					// translators: %s gateway minimum.
					'description'       => sprintf( __( 'If the cart total is less than this amount, this payment method will not show. The minimum cannot be less than %s', 'peachpay-for-woocommerce' ), wc_price( $this->min_amount, array( 'currency' => $this->min_max_currency ) ) ),
					'default'           => $this->get_minimum_charge(),
					'class'             => '',
					'custom_attributes' => array(
						'min'  => $this->min_amount,
						'max'  => INF === $this->max_amount ? '' : $this->max_amount,
						'step' => '0.01',
					),
				),
			)
		);
	}

	/**
	 * Gateway custom max settings
	 *
	 * @param array $form_fields the current fields.
	 */
	private function custom_max_amount_settings( $form_fields ) {

		// translators: %s gateway maximum.
		$description = sprintf( __( 'If the cart total is more than this amount, this payment method will not show. The maximum cannot be more than %s', 'peachpay-for-woocommerce' ), wc_price( $this->max_amount, array( 'currency' => $this->min_max_currency ) ) );
		if ( INF === $this->max_amount ) {
			$description = __( 'If the cart total is more than this amount, this payment method will not show.', 'peachpay-for-woocommerce' );
		}

		$this->custom_max_amount = floatval( $this->get_option( 'custom_max_amount', $this->max_amount ) );
		return array_merge(
			$form_fields,
			array(
				'custom_max_amount' => array(
					'type'              => 'number',
					'title'             => __( 'Maximum charge', 'peachpay-for-woocommerce' ),
					// translators: %s gateway maximum.
					'description'       => $description,
					'default'           => $this->get_maximum_charge(),
					'class'             => '',
					'placeholder'       => __( 'Not restricted', 'peachpay-for-woocommerce' ),
					'custom_attributes' => array(
						'min'  => $this->min_amount,
						'max'  => INF === $this->max_amount ? '' : $this->max_amount,
						'step' => '0.01',
					),
				),
			)
		);
	}

	/**
	 * Inserts the fee settings inputs onto the given peachpay payment gateway.
	 *
	 * @param array  $form_fields The currence form fields associated with this gateway. This will append
	 *  all the needed fee settings onto the end of the form field.
	 * @param string $payment_title The specified external (merchant, customer) payment method value.
	 */
	public function fee_settings( $form_fields, $payment_title ) {
		return array_merge(
			$form_fields,
			array(
				'fees_enabled'      => array(
					'id'          => 'fee_id',
					'type'        => 'checkbox',
					'title'       => 'Fees',
					'label'       => __( 'Enable extra fees', 'peachpay-for-woocommerce' ),
					// translators: %s gateway title.
					'description' => sprintf( __( 'Shoppers paying with %s will be charged an extra fee.', 'peachpay-for-woocommerce' ), $this->title ),
					'default'     => 'no',
					'class'       => 'toggle',
				),
				'fee_type'          => array(
					'title'   => __( 'Fee type', 'peachpay-for-woocommerce' ),
					'type'    => 'select',
					'default' => 'fixed',
					'options' => array(
						'fixed'      => __( 'Fixed', 'peachpay-for-woocommerce' ),
						'percentage' => __( 'Percentage', 'peachpay-for-woocommerce' ),
					),
				),
				'fee_amount'        => array(
					'title'       => __( 'Fee amount', 'peachpay-for-woocommerce' ),
					'description' => __( 'The amount you would like to charge', 'peachpay-for-woocommerce' ),
					'type'        => 'number',
					'default'     => 0,
				),
				'fee_reason'        => array(
					'title'       => __( 'Fee label', 'peachpay-for-woocommerce' ),
					'description' => __( 'Title or reason for the extra fee', 'peachpay-for-woocommerce' ),
					'type'        => 'text',
					'default'     => 'Payment gateway fee',
				),
				'fee_threshold_min' => array(
					'title'       => __( 'Fee minimum threshold', 'peachpay-for-woocommerce' ),
					'description' => __( 'The extra fee will only be applied if the cart total is above this amount.', 'peachpay-for-woocommerce' ),
					'type'        => 'number',
					'default'     => 0,
				),
				'fee_threshold_max' => array(
					'title'       => __( 'Fee maximum threshold', 'peachpay-for-woocommerce' ),
					'description' => __( 'The extra fee will only be applied if the cart total is below this amount. This must be less than or equal to this payment gateway\'s maximum charge.', 'peachpay-for-woocommerce' ),
					'type'        => 'number',
					'placeholder' => __( 'Not restricted', 'peachpay-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * If this gateway is enabled.
	 *
	 * @param array $form_fields The current fields.
	 */
	private function enabled_setting( $form_fields ) {
		return array_merge(
			array(
				'enabled' => array(
					'type'    => 'checkbox',
					'title'   => __( 'Enable/Disable', 'peachpay-for-woocommerce' ),
					// translators: %s gateway title.
					'label'   => sprintf( __( 'Enable %s', 'peachpay-for-woocommerce' ), $this->title ),
					'default' => 'no',
					'class'   => 'toggle',
				),
			),
			$form_fields
		);
	}

	/**
	 * Settings for enabling/disabling a gateway in the native checkout / express checkout.
	 *
	 * @param array $form_fields The current fields.
	 */
	private function active_locations_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'active_locations' => array(
					'type'    => 'select',
					'title'   => __( 'Display', 'peachpay-for-woocommerce' ),
					'class'   => 'pp-location-select',
					'options' => array(
						'default'               => __( 'Both checkout page and Express Checkout', 'peachpay-for-woocommerce' ),
						'checkout_page_only'    => __( 'Checkout page only', 'peachpay-for-woocommerce' ),
						'express_checkout_only' => __( 'Express Checkout only', 'peachpay-for-woocommerce' ),
					),
				),
			)
		);
	}

	/**
	 * Settings for customizing the gateway title.
	 *
	 * @param array $form_fields The current fields.
	 */
	private function gateway_title_setting( $form_fields ) {
		return array_merge(
			$form_fields,
			array(
				'title' => array(
					'type'        => 'text',
					'title'       => __( 'Title', 'peachpay-for-woocommerce' ),
					'default'     => '',
					'placeholder' => $this->title,
				),
			)
		);
	}

	/**
	 * Gets the gateway title.
	 *
	 * @param string $title .
	 * @param string $id    .
	 */
	public function get_title_filter( $title, $id ) {
		if ( $id === $this->id ) {
			return $this->get_option( 'title', $title );
		}

		return $title;
	}
}
