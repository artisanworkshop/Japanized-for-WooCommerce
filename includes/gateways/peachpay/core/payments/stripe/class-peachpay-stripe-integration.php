<?php
/**
 * PeachPay Stripe Payment extension.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * .
 */
final class PeachPay_Stripe_Integration {
	use PeachPay_Payment_Integration;

	/**
	 * At some point in time we will allow the ability to disable and enable
	 * integrations completely to ensure they do not have performance impacts
	 * when not used. For now we always return true.
	 */
	public static function should_load() {
		return true;
	}

	/**
	 * .
	 */
	private function includes() {
		require_once PEACHPAY_ABSPATH . 'core/payments/class-peachpay-payment.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/hooks.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/functions.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/routes/stripe-webhook.php';

		if ( is_admin() ) {
			require_once PEACHPAY_ABSPATH . 'core/payments/stripe/admin/class-peachpay-admin-stripe-integration.php';
		}
	}

	/**
	 * .
	 */
	private function woocommerce_init() {
		// https://github.com/woocommerce/woocommerce/wiki/Payment-Token-API
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/tokens/class-wc-payment-token-peachpay-stripe-card.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/tokens/class-wc-payment-token-peachpay-stripe-achdebit.php';

		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/utils/class-peachpay-stripe.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/utils/class-peachpay-stripe-order-data.php';

		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/abstract/class-peachpay-stripe-payment-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-card-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-applepay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-googlepay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-affirm-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-klarna-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-afterpay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-achdebit-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-bancontact-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-giropay-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-ideal-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-sofort-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-p24-gateway.php';
		require_once PEACHPAY_ABSPATH . 'core/payments/stripe/gateways/class-peachpay-stripe-eps-gateway.php';

		$this->payment_gateways[] = 'PeachPay_Stripe_Card_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_ApplePay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_GooglePay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Affirm_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Klarna_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Afterpay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_AchDebit_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Bancontact_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Giropay_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Ideal_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Sofort_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_P24_Gateway';
		$this->payment_gateways[] = 'PeachPay_Stripe_Eps_Gateway';
	}

	/**
	 * Used to detect if a gateway is a PeachPay Stripe gateway.
	 *
	 * @param string $gateway_id Payment gateway id.
	 */
	public static function is_payment_gateway( $gateway_id ) {
		return peachpay_starts_with( $gateway_id, 'peachpay_stripe_' );
	}

	/**
	 * Determines whether Stripe is connected/ returns connect data.
	 */
	public static function connected() {
		return get_option( 'peachpay_connected_stripe_account', 0 );
	}

	/**
	 * Gets stripe config.
	 */
	public static function config() {
		return get_option( 'peachpay_connected_stripe_config', 0 );
	}

	/**
	 * Gets Stripe connect id.
	 */
	public static function connect_id() {
		$account = self::connected();

		if ( ! $account ) {
			return '';
		}

		// @deprecated property but checking its existence to ensure if the merchant has not visited the PeachPay settings things do not break.
		if ( isset( $account['id'] ) ) {
			return $account['id'];
		}

		return $account['connect_id'];
	}

	/**
	 * Gets Stripe public key.
	 */
	public static function public_key() {
		$config = self::config();

		if ( ! $config || ! is_array( $config ) || ! isset( $config['public_key'] ) ) {
			return '';
		}

		return $config['public_key'];
	}

	/**
	 * Gets Stripe connect account country.
	 */
	public static function connect_country() {
		$account = self::connected();

		if ( ! $account ) {
			return '';
		}

		return $account['country'];
	}

	/**
	 * Gets a stripe payment capability status.
	 *
	 * @param string $payment_key The payment capability to retrieve a status for.
	 */
	public static function is_capable( $payment_key ) {
		$account = self::connected();

		if ( ! $account ) {
			return 'inactive';
		}

		if ( ! array_key_exists( 'capabilities', $account ) ) {
			return 'inactive';
		}

		$capabilities = $account['capabilities'];

		if ( ! array_key_exists( $payment_key, $capabilities ) ) {
			return 'inactive';
		}

		return $capabilities[ $payment_key ];
	}

	/**
	 * Creates the Stripe connect signup link.
	 */
	public static function signup_url() {
		$home_url = get_home_url();
		$site_url = get_site_url();

		// phpcs:ignore
		$TEST_STRIPE_CLIENT_ID = 'ca_HHK0LPM3N7jbW1aV610tueC8zVOBtW2D';
		// phpcs:ignore
		$LIVE_STRIPE_CLIENT_ID = 'ca_HHK0N5DreIcJJAyqGbeOE77hAZD9gCFg';
		// phpcs:ignore
		$stripe_client_id = ( peachpay_is_local_development_site() || peachpay_is_staging_site() ) ? $TEST_STRIPE_CLIENT_ID : $LIVE_STRIPE_CLIENT_ID;

		$state               = new stdClass();
		$state->merchant_url = $home_url;
		$state->wp_admin_url = $site_url;

		// Using JSON to pass multiple parameters through state.
		$state_json = wp_json_encode( $state );
		// Base64 encode as JSON includes chars removed by esc_url().
		// phpcs:ignore
		$state_base64 = base64_encode( $state_json );

		$redirect_uri = peachpay_api_url( 'live' ) . 'connect/oauth';

		return "https://dashboard.stripe.com/oauth/v2/authorize?response_type=code&client_id=$stripe_client_id&scope=read_write&state=$state_base64&stripe_user[url]=$home_url&redirect_uri=$redirect_uri";
	}

	/**
	 * Gets the PeachPay stripe test or live mode status.
	 *
	 * @param boolean|null $mode If the mode should override the global test mode. If not null a truthy value will indicate live mode where a falsy value will indicate test mode.
	 */
	public static function mode( $mode = 'detect' ) {
		if ( 'detect' === $mode ) {
			return ( peachpay_is_test_mode() || peachpay_is_local_development_site() || peachpay_is_staging_site() ) ? 'test' : 'live';
		}

		if ( 'live' === $mode ) {
			return 'live';
		} else {
			return 'test';
		}
	}

	/**
	 * Stripe rest api endpoints.
	 */
	private function rest_api_init() {
		register_rest_route(
			PEACHPAY_ROUTE_BASE,
			'/stripe/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => 'peachpay_rest_api_stripe_webhook',
				'permission_callback' => '__return_true',
			)
		);
	}
}
PeachPay_Stripe_Integration::instance();
