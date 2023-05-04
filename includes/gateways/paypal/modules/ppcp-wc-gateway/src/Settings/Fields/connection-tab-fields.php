<?php
/**
 * The services of the Gateway module.
 *
 * @package WooCommerce\PayPalCommerce\WcGateway
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\WcGateway\Settings;

use WooCommerce\PayPalCommerce\Vendor\Psr\Container\ContainerInterface;
use WooCommerce\PayPalCommerce\ApiClient\Helper\DccApplies;
use WooCommerce\PayPalCommerce\Onboarding\Environment;
use WooCommerce\PayPalCommerce\Onboarding\Render\OnboardingOptionsRenderer;
use WooCommerce\PayPalCommerce\Onboarding\State;

return function ( ContainerInterface $container, array $fields ): array {

	$current_page_id = $container->get( 'wcgateway.current-ppcp-settings-page-id' );

	if ( $current_page_id !== Settings::CONNECTION_TAB_ID ) {
		return $fields;
	}

	$state = $container->get( 'onboarding.state' );
	assert( $state instanceof State );

	$dcc_applies = $container->get( 'api.helpers.dccapplies' );
	assert( $dcc_applies instanceof DccApplies );

	$is_shop_supports_dcc = $dcc_applies->for_country_currency() || $dcc_applies->for_wc_payments();

	$onboarding_options_renderer = $container->get( 'onboarding.render-options' );
	assert( $onboarding_options_renderer instanceof OnboardingOptionsRenderer );

	$module_url = $container->get( 'wcgateway.url' );

	$connection_fields = array(
		'ppcp_onboarading_header'                       => array(
			'type'         => 'ppcp-text',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'text'         => '
<div class="ppcp-onboarding-header">
	<div class="ppcp-onboarding-header-left">
		<img alt="PayPal" src="' . esc_url( $module_url ) . 'assets/images/paypal.png"/>
		<h2>The all-in-one checkout solution</h2>
	</div>
	<div class="ppcp-onboarding-header-right">
		<div class="ppcp-onboarding-header-paypal-logos">
			<img alt="PayPal" src="' . esc_url( $module_url ) . 'assets/images/paypal-button.svg"/>
			<img alt="Venmo" src="' . esc_url( $module_url ) . 'assets/images/venmo.svg"/>
			<img alt="Pay Later" src="' . esc_url( $module_url ) . 'assets/images/paylater.svg"/>
		</div>
		<div class="ppcp-onboarding-header-cards">
			<img alt="Visa" src="' . esc_url( $module_url ) . 'assets/images/visa-dark.svg"/>
			<img alt="Mastercard" src="' . esc_url( $module_url ) . 'assets/images/mastercard-dark.svg"/>
			<img alt="American Express" src="' . esc_url( $module_url ) . 'assets/images/amex.svg"/>
			<img alt="Discover" src="' . esc_url( $module_url ) . 'assets/images/discover.svg"/>
			<img alt="iDEAL" src="' . esc_url( $module_url ) . 'assets/images/ideal-dark.svg"/>
			<img alt="Sofort" src="' . esc_url( $module_url ) . 'assets/images/sofort.svg"/>
		</div>
	</div>
</div>',
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'credentials_production_heading'                => array(
			'heading'      => __( 'API Credentials', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'state_from'   => Environment::PRODUCTION,
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'credentials_sandbox_heading'                   => array(
			'heading'      => __( 'Sandbox API Credentials', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'state_from'   => Environment::SANDBOX,
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => __( 'Your account is connected to sandbox, no real charging takes place. To accept live payments, turn off sandbox mode and connect your live PayPal account.', 'woocommerce-for-japan' ),
		),

		'ppcp_onboarading_options'                      => array(
			'type'         => 'ppcp-text',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'text'         => $onboarding_options_renderer->render( $is_shop_supports_dcc ),
			'raw'          => true,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),

		// We need to have a button for each option (ppcp, express)
		// because currently the only documented way to use the PayPal onboarding JS library
		// is to have the buttons before loading the script.
		'ppcp_onboarding_production_ppcp'               => array(
			'type'         => 'ppcp_onboarding',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'screens'      => array(
				State::STATE_START,
			),
			'state_from'   => Environment::PRODUCTION,
			'env'          => Environment::PRODUCTION,
			'products'     => array( 'PPCP' ),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'ppcp_onboarding_production_express'            => array(
			'type'         => 'ppcp_onboarding',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'screens'      => array(
				State::STATE_START,
			),
			'state_from'   => Environment::PRODUCTION,
			'env'          => Environment::PRODUCTION,
			'products'     => array( 'EXPRESS_CHECKOUT' ),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'ppcp_onboarding_sandbox_ppcp'                  => array(
			'type'         => 'ppcp_onboarding',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'screens'      => array(
				State::STATE_START,
			),
			'state_from'   => Environment::SANDBOX,
			'env'          => Environment::SANDBOX,
			'products'     => array( 'PPCP' ),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => __( 'Prior to accepting live payments, you can test payments on your WooCommerce platform in a safe PayPal sandbox environment.', 'woocommerce-for-japan' ),
		),
		'ppcp_onboarding_sandbox_express'               => array(
			'type'         => 'ppcp_onboarding',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'screens'      => array(
				State::STATE_START,
			),
			'state_from'   => Environment::SANDBOX,
			'env'          => Environment::SANDBOX,
			'products'     => array( 'EXPRESS_CHECKOUT' ),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => __( 'Prior to accepting live payments, you can test payments on your WooCommerce platform in a safe PayPal sandbox environment.', 'woocommerce-for-japan' ),
		),

		'ppcp_disconnect_production'                    => array(
			'title'        => __( 'Disconnect from PayPal', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-text',
			'text'         => sprintf(
				'<p>%1$s <span class="dashicons dashicons-yes"></span></p><p><button type="button" class="button ppcp-disconnect production">%2$s</button></p>',
				esc_html__( 'Status: Connected', 'woocommerce-for-japan' ),
				esc_html__( 'Disconnect Account', 'woocommerce-for-japan' )
			),
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'state_from'   => Environment::PRODUCTION,
			'env'          => Environment::PRODUCTION,
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => __( 'Click to reset current credentials and use another account.', 'woocommerce-for-japan' ),
		),
		'ppcp_disconnect_sandbox'                       => array(
			'title'        => __( 'Disconnect from PayPal Sandbox', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-text',
			'text'         => sprintf(
				'<p>%1$s <span class="dashicons dashicons-yes"></span></p><p><button type="button" class="button ppcp-disconnect sandbox">%2$s</button></p>',
				esc_html__( 'Status: Connected', 'woocommerce-for-japan' ),
				esc_html__( 'Disconnect Account', 'woocommerce-for-japan' )
			),
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'state_from'   => Environment::SANDBOX,
			'env'          => Environment::SANDBOX,
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => __( 'Click to reset current credentials and use another account.', 'woocommerce-for-japan' ),
		),
		'toggle_manual_input'                           => array(
			'type'         => 'ppcp-text',
			'text'         => '<button type="button" id="ppcp[toggle_manual_input]">' . __( 'Toggle to manual credential input', 'woocommerce-for-japan' ) . '</button>',
			'classes'      => array( 'ppcp-onboarding-element' ),
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'error_label'                                   => array(
			'type'         => 'ppcp-text',
			'text'         => '<label class="error" id="ppcp-form-errors-label"></label>',
			'classes'      => array( 'hide', 'ppcp-always-shown-element' ),
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'sandbox_on'                                    => array(
			'title'        => __( 'Sandbox', 'woocommerce-for-japan' ),
			'classes'      => array( 'ppcp-onboarding-element', 'ppcp-always-shown-element' ),
			'type'         => 'checkbox',
			'label'        => __( 'To test your WooCommerce installation, you can use the sandbox mode.', 'woocommerce-for-japan' ),
			'default'      => 0,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'merchant_email_production'                     => array(
			'title'        => __( 'Live Email address', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->production_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'text',
			'required'     => true,
			'desc_tip'     => true,
			'description'  => __( 'The email address of your PayPal account.', 'woocommerce-for-japan' ),
			'default'      => '',
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'merchant_id_production'                        => array(
			'title'        => __( 'Live Merchant Id', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->production_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'ppcp-text-input',
			'desc_tip'     => true,
			'description'  => __( 'The merchant id of your account ', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'client_id_production'                          => array(
			'title'        => __( 'Live Client Id', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->production_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'ppcp-text-input',
			'desc_tip'     => true,
			'description'  => __( 'The client id of your api ', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'client_secret_production'                      => array(
			'title'        => __( 'Live Secret Key', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->production_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'ppcp-password',
			'desc_tip'     => true,
			'description'  => __( 'The secret key of your api', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),

		'merchant_email_sandbox'                        => array(
			'title'        => __( 'Sandbox Email address', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->sandbox_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'text',
			'required'     => true,
			'desc_tip'     => true,
			'description'  => __( 'The email address of your PayPal account.', 'woocommerce-for-japan' ),
			'default'      => '',
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'merchant_id_sandbox'                           => array(
			'title'        => __( 'Sandbox Merchant Id', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->sandbox_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'ppcp-text-input',
			'desc_tip'     => true,
			'description'  => __( 'The merchant id of your account ', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'client_id_sandbox'                             => array(
			'title'        => __( 'Sandbox Client Id', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->sandbox_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'ppcp-text-input',
			'desc_tip'     => true,
			'description'  => __( 'The client id of your api ', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'client_secret_sandbox'                         => array(
			'title'        => __( 'Sandbox Secret Key', 'woocommerce-for-japan' ),
			'classes'      => array( State::STATE_ONBOARDED === $state->sandbox_state() ? 'onboarded' : '', 'ppcp-always-shown-element' ),
			'type'         => 'ppcp-password',
			'desc_tip'     => true,
			'description'  => __( 'The secret key of your api', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),

		'credentials_feature_onboarding_heading'        => array(
			'heading'      => __( 'Advanced feature availability & sign-up', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => sprintf(
				// translators: %1$s and %2$s are the opening and closing of HTML <a> tag.
				__( 'Displays whether available advanced features are enabled for the connected PayPal account. More information about advanced features is available in the %1$sFeature sign-up documentation%2$s.', 'woocommerce-for-japan' ),
				'<a href="https://woocommerce.com/document/woocommerce-paypal-payments/#feature-signup" target="_blank">',
				'</a>'
			),
		),
		'ppcp_dcc_status'                               => array(
			'title'        => __( 'Advanced Credit and Debit Card Payments', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-text',
			'text'         => $container->get( 'wcgateway.settings.connection.dcc-status-text' ),
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'requirements' => array( 'dcc' ),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'ppcp_pui_status'                               => array(
			'title'        => __( 'Pay upon Invoice', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-text',
			'text'         => $container->get( 'wcgateway.settings.connection.pui-status-text' ),
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'requirements' => array( 'pui_ready' ),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
		'tracking_enabled'                              => array(
			'title'        => __( 'Shipment Tracking', 'woocommerce-for-japan' ),
			'type'         => 'checkbox',
			'desc_tip'     => true,
			'label'        => $container->get( 'wcgateway.settings.tracking-label' ),
			'description'  => __( 'Allows to send shipment tracking numbers to PayPal for PayPal transactions.', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'input_class'  => $container->get( 'wcgateway.settings.should-disable-tracking-checkbox' ) ? array( 'ppcp-disabled-checkbox' ) : array(),
		),
		'fraudnet_enabled'                              => array(
			'title'        => __( 'FraudNet', 'woocommerce-for-japan' ),
			'type'         => 'checkbox',
			'desc_tip'     => true,
			'label'        => $container->get( 'wcgateway.settings.fraudnet-label' ),
			'description'  => __( 'FraudNet is a JavaScript library developed by PayPal and embedded into a merchant’s web page to collect browser-based data to help reduce fraud.', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'input_class'  => $container->get( 'wcgateway.settings.should-disable-fraudnet-checkbox' ) ? array( 'ppcp-disabled-checkbox' ) : array(),
		),

		'credentials_integration_configuration_heading' => array(
			'heading'      => __( 'General integration configuration', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array(
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
			'description'  => sprintf(
			// translators: %1$s and %2$s are the opening and closing of HTML <a> tag.
				__( 'Configure integration specific settings such as a unique invoice prefix, or logging for potential %1$stroubleshooting%2$s.', 'woocommerce-for-japan' ),
				'<a href="https://woocommerce.com/document/woocommerce-paypal-payments/#troubleshooting" target="_blank">',
				'</a>'
			),
		),
		'prefix'                                        => array(
			'title'             => __( 'Invoice prefix', 'woocommerce-for-japan' ),
			'type'              => 'text',
			'desc_tip'          => true,
			'description'       => __( 'If you use your PayPal account with more than one installation, please use a distinct prefix to separate those installations. Please use only English letters and "-", "_" characters.', 'woocommerce-for-japan' ),
			'maxlength'         => 15,
			'custom_attributes' => array(
				'pattern' => '[a-zA-Z_-]+',
			),
			'default'           => ( static function (): string {
				$site_url = get_site_url( get_current_blog_id() );
				$hash = md5( $site_url );
				$letters = preg_replace( '~\d~', '', $hash ) ?? '';
				$prefix = substr( $letters, 0, 6 );
				return $prefix ? $prefix . '-' : '';
			} )(),
			'screens'           => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements'      => array(),
			'gateway'           => Settings::CONNECTION_TAB_ID,
		),
		'logging_enabled'                               => array(
			'title'        => __( 'Logging', 'woocommerce-for-japan' ),
			'type'         => 'checkbox',
			'desc_tip'     => true,
			'label'        => __( 'Enable logging. ', 'woocommerce-for-japan' ) .
				' <a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '">' . __( 'View logs', 'woocommerce-for-japan' ) . '</a>',
			'description'  => __( 'Enable logging of unexpected behavior. This can also log private data and should only be enabled in a development or stage environment.', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array(
				State::STATE_START,
				State::STATE_ONBOARDED,
			),
			'requirements' => array(),
			'gateway'      => Settings::CONNECTION_TAB_ID,
		),
	);

	return array_merge( $fields, $connection_fields );
};
