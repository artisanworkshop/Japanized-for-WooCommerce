<?php
/**
 * PeachPay Square ApplePay gateway.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * .
 */
class PeachPay_Square_ApplePay_Gateway extends PeachPay_Square_Payment_Gateway {

	/**
	 * .
	 */
	public function __construct() {
		$this->id                = 'peachpay_square_applepay';
		$this->icon              = peachpay_url( 'public/img/marks/apple-pay.svg' );
		$this->settings_priority = 1;

		// Customer facing title and description.
		$this->title       = 'Apple Pay';
		$this->description = '';

		$this->payment_method_family = __( 'Digital wallet', 'peachpay-for-woocommerce' );

		$this->form_fields = $this->reset_domain_registration_setting( $this->form_fields );

		parent::__construct();
	}

	/**
	 * ApplePay is only available if the domain is registered.
	 */
	public function is_available() {
		$is_available = parent::is_available();

		if ( ! peachpay_square_apple_pay_domain_registered() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * ApplePay requires setup if the domain is not verified.
	 */
	public function needs_setup() {
		return parent::needs_setup() || ! peachpay_square_apple_pay_domain_registered();
	}

	/**
	 * ApplePay needs the domain registered. This renders the template needed to perform that
	 * action if automatic registration fails.
	 */
	protected function action_needed_form() {
		parent::action_needed_form();

		$gateway = $this;

		if ( peachpay_square_connected() ) {
			if ( ! peachpay_square_apple_pay_domain_registered() && peachpay_square_connected() && peachpay_square_capability( 'square_apple_pay_payments' ) ) {
				require PeachPay::get_plugin_path() . '/core/admin/views/html-applepay-register-domain.php';
			}
		}
	}

	/**
	 * Adds a setting to reset the apple pay domain registration.
	 *
	 * @param array $form_fields The existing gateway settings.
	 */
	protected function reset_domain_registration_setting( $form_fields ) {

		if ( ! peachpay_square_apple_pay_domain_registered() ) {
			return $form_fields;
		}

		return array_merge(
			$form_fields,
			array(
				'reset_apple_domain' => array(
					'type'              => 'hidden',
					'title'             => __( 'Reset domain registration', 'peachpay-for-woocommerce' ),
					'description'       => __( 'Apple Pay requires your domain to be registered with Apple. If you are encountering issues with Apple Pay or recently changed your domain, resetting the domain registration might fix the problem.', 'peachpay-for-woocommerce' ),
					'class'             => 'peachpay-applepay-reset',
					'custom_attributes' => array(
						'data-gateway' => $this->id,
					),
				),
			)
		);
	}
}
