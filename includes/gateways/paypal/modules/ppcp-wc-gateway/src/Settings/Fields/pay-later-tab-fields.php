<?php
/**
 * The services of the Gateway module.
 *
 * @package WooCommerce\PayPalCommerce\WcGateway
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\WcGateway\Settings;

use WooCommerce\PayPalCommerce\Onboarding\State;
use WooCommerce\PayPalCommerce\Vendor\Psr\Container\ContainerInterface;

return function ( ContainerInterface $container, array $fields ): array {

	$current_page_id = $container->get( 'wcgateway.current-ppcp-settings-page-id' );

	if ( $current_page_id !== Settings::PAY_LATER_TAB_ID ) {
		return $fields;
	}

	$settings = $container->get( 'wcgateway.settings' );
	assert( $settings instanceof Settings );

	$vault_enabled = $settings->has( 'vault_enabled' ) && $settings->get( 'vault_enabled' );

	$pay_later_messaging_enabled_label = $vault_enabled
		? __( "You have PayPal vaulting enabled, that's why Pay Later options are unavailable now. You cannot use both features at the same time.", 'woocommerce-paypal-payments' )
		: __( 'Enabled', 'woocommerce-for-japan' );

	$selected_country             = $container->get( 'api.shop.country' );
	$default_messaging_flex_color = $selected_country === 'US' ? 'white-no-border' : 'white';

	$render_preview_element = function ( string $id, string $type ): string {
		return '
<div class="ppcp-preview ppcp-' . $type . '-preview pay-later">
	<h4>' . __( 'Preview', 'woocommerce-for-japan' ) . '</h4>
	<div id="' . $id . '" class="ppcp-' . $type . '-preview-inner"></div>
</div>';
	};

	$pay_later_fields = array(
		'pay_later_button_heading'                        => array(
			'heading'      => __( 'Pay Later Button', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
			'description'  => sprintf(
			// translators: %1$s and %2$s are the opening and closing of HTML <a> tag.
				__( 'When enabled, a %1$sPay Later button%2$s is displayed for eligible customers.%3$sPayPal buttons must be enabled to display the Pay Later button.', 'woocommerce-for-japan' ),
				'<a href="https://woocommerce.com/document/woocommerce-paypal-payments/#pay-later-buttons" target="_blank">',
				'</a>',
				'</ br>'
			),
		),
		'pay_later_button_enabled'                        => array(
			'title'        => __( 'Enable/Disable', 'woocommerce-for-japan' ),
			'type'         => 'checkbox',
			'label'        => esc_html( $pay_later_messaging_enabled_label ),
			'default'      => true,
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
			'input_class'  => $vault_enabled ? array( 'ppcp-disabled-checkbox' ) : array(),
		),
		'pay_later_button_locations'                      => array(
			'title'        => __( 'Pay Later Button Locations', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-multiselect',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => $container->get( 'wcgateway.button.default-locations' ),
			'desc_tip'     => false,
			'description'  => __( 'Select where the Pay Later button should be displayed.', 'woocommerce-for-japan' ),
			'options'      => $container->get( 'wcgateway.settings.pay-later.button-locations' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_button_preview'                        => array(
			'type'         => 'ppcp-text',
			'text'         => $render_preview_element( 'ppcpPayLaterButtonPreview', 'button' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),

		// Messaging.
		'pay_later_messaging_heading'                     => array(
			'heading'      => __( 'Pay Later Messaging', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
			'description'  => sprintf(
			// translators: %1$s and %2$s are the opening and closing of HTML <a> tag.
				__( 'When enabled, %1$sPayPal Pay Later messaging%2$s is displayed for eligible customers.%3$sCustomers automatically see the most relevant Pay Later offering.', 'woocommerce-for-japan' ),
				'<a href="https://woocommerce.com/document/woocommerce-paypal-payments/#pay-later-messaging" target="_blank">',
				'</a>',
				'</ br>'
			),
		),
		'pay_later_messaging_enabled'                     => array(
			'title'        => __( 'Enable/Disable', 'woocommerce-for-japan' ),
			'type'         => 'checkbox',
			'label'        => esc_html( $pay_later_messaging_enabled_label ),
			'default'      => true,
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
			'input_class'  => $vault_enabled ? array( 'ppcp-disabled-checkbox' ) : array(),
		),
		'pay_later_messaging_locations'                   => array(
			'title'        => __( 'Pay Later Messaging Locations', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-multiselect',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => $container->get( 'wcgateway.button.default-locations' ),
			'desc_tip'     => false,
			'description'  => __( 'Select where the Pay Later messaging should be displayed.', 'woocommerce-for-japan' ),
			'options'      => $container->get( 'wcgateway.settings.pay-later.messaging-locations' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_enable_styling_per_messaging_location' => array(
			'title'        => __( 'Customize Messaging Per Location', 'woocommerce-for-japan' ),
			'type'         => 'checkbox',
			'label'        => __( 'Customize Pay Later messaging style per location', 'woocommerce-for-japan' ),
			'default'      => false,
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_layout'                => array(
			'title'        => __( 'Messaging Layout', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'text',
			'desc_tip'     => true,
			'description'  => __( 'The layout of the message.', 'woocommerce-for-japan' ),
			'options'      => array(
				'text' => __( 'Text', 'woocommerce-for-japan' ),
				'flex' => __( 'Banner', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_logo'                  => array(
			'title'        => __( 'Messaging Logo', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'inline',
			'desc_tip'     => true,
			'description'  => __( 'What logo the text message contains. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'primary'     => __( 'Primary', 'woocommerce-for-japan' ),
				'alternative' => __( 'Alternative', 'woocommerce-for-japan' ),
				'inline'      => __( 'Inline', 'woocommerce-for-japan' ),
				'none'        => __( 'None', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_position'              => array(
			'title'        => __( 'Messaging Logo Position', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'left',
			'desc_tip'     => true,
			'description'  => __( 'The position of the logo. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'left'  => __( 'Left', 'woocommerce-for-japan' ),
				'right' => __( 'Right', 'woocommerce-for-japan' ),
				'top'   => __( 'Top', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_color'                 => array(
			'title'        => __( 'Messaging Text Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'black',
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'black'      => __( 'Black', 'woocommerce-for-japan' ),
				'white'      => __( 'White', 'woocommerce-for-japan' ),
				'monochrome' => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'  => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_flex_color'            => array(
			'title'        => __( 'Messaging Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => $default_messaging_flex_color,
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'blue'            => __( 'Blue', 'woocommerce-for-japan' ),
				'black'           => __( 'Black', 'woocommerce-for-japan' ),
				'white'           => __( 'White', 'woocommerce-for-japan' ),
				'white-no-border' => __( 'White no border', 'woocommerce-for-japan' ),
				'gray'            => __( 'Gray', 'woocommerce-for-japan' ),
				'monochrome'      => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'       => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_flex_ratio'            => array(
			'title'        => __( 'Messaging Ratio', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => '8x1',
			'desc_tip'     => true,
			'description'  => __( 'The width/height ratio of the banner. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'1x1'  => __( '1x1', 'woocommerce-for-japan' ),
				'1x4'  => __( '1x4', 'woocommerce-for-japan' ),
				'8x1'  => __( '8x1', 'woocommerce-for-japan' ),
				'20x1' => __( '20x1', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_general_message_preview'               => array(
			'type'         => 'ppcp-text',
			'text'         => $render_preview_element( 'ppcpGeneralMessagePreview', 'message' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),

		// Single product.
		'pay_later_product_messaging_heading'             => array(
			'heading'      => __( 'Pay Later Messaging on Single Product', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array(),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_layout'                => array(
			'title'        => __( 'Single Product Messaging Layout', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'text',
			'desc_tip'     => true,
			'description'  => __( 'The layout of the message.', 'woocommerce-for-japan' ),
			'options'      => array(
				'text' => __( 'Text', 'woocommerce-for-japan' ),
				'flex' => __( 'Banner', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_logo'                  => array(
			'title'        => __( 'Single Product Messaging Logo', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'inline',
			'desc_tip'     => true,
			'description'  => __( 'What logo the text message contains. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'primary'     => __( 'Primary', 'woocommerce-for-japan' ),
				'alternative' => __( 'Alternative', 'woocommerce-for-japan' ),
				'inline'      => __( 'Inline', 'woocommerce-for-japan' ),
				'none'        => __( 'None', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_position'              => array(
			'title'        => __( 'Single Product Messaging Logo Position', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'left',
			'desc_tip'     => true,
			'description'  => __( 'The position of the logo. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'left'  => __( 'Left', 'woocommerce-for-japan' ),
				'right' => __( 'Right', 'woocommerce-for-japan' ),
				'top'   => __( 'Top', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_color'                 => array(
			'title'        => __( 'Single Product Messaging Text Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'black',
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'black'      => __( 'Black', 'woocommerce-for-japan' ),
				'white'      => __( 'White', 'woocommerce-for-japan' ),
				'monochrome' => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'  => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_flex_color'            => array(
			'title'        => __( 'Single Product Messaging Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => $default_messaging_flex_color,
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'blue'            => __( 'Blue', 'woocommerce-for-japan' ),
				'black'           => __( 'Black', 'woocommerce-for-japan' ),
				'white'           => __( 'White', 'woocommerce-for-japan' ),
				'white-no-border' => __( 'White no border', 'woocommerce-for-japan' ),
				'gray'            => __( 'Gray', 'woocommerce-for-japan' ),
				'monochrome'      => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'       => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_flex_ratio'            => array(
			'title'        => __( 'Single Product Messaging Ratio', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => '8x1',
			'desc_tip'     => true,
			'description'  => __( 'The width/height ratio of the banner. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'1x1'  => __( '1x1', 'woocommerce-for-japan' ),
				'1x4'  => __( '1x4', 'woocommerce-for-japan' ),
				'8x1'  => __( '8x1', 'woocommerce-for-japan' ),
				'20x1' => __( '20x1', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_product_message_preview'               => array(
			'type'         => 'ppcp-text',
			'text'         => $render_preview_element( 'ppcpProductMessagePreview', 'message' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),

		// Cart.
		'pay_later_cart_messaging_heading'                => array(
			'heading'      => __( 'Pay Later Messaging on Cart', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array(),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_layout'                   => array(
			'title'        => __( 'Cart Messaging Layout', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'text',
			'desc_tip'     => true,
			'description'  => __( 'The layout of the message.', 'woocommerce-for-japan' ),
			'options'      => array(
				'text' => __( 'Text', 'woocommerce-for-japan' ),
				'flex' => __( 'Banner', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_logo'                     => array(
			'title'        => __( 'Cart Messaging Logo', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'inline',
			'desc_tip'     => true,
			'description'  => __( 'What logo the text message contains. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'primary'     => __( 'Primary', 'woocommerce-for-japan' ),
				'alternative' => __( 'Alternative', 'woocommerce-for-japan' ),
				'inline'      => __( 'Inline', 'woocommerce-for-japan' ),
				'none'        => __( 'None', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_position'                 => array(
			'title'        => __( 'Cart Messaging Logo Position', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'left',
			'desc_tip'     => true,
			'description'  => __( 'The position of the logo. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'left'  => __( 'Left', 'woocommerce-for-japan' ),
				'right' => __( 'Right', 'woocommerce-for-japan' ),
				'top'   => __( 'Top', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_color'                    => array(
			'title'        => __( 'Cart Messaging Text Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'black',
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'black'      => __( 'Black', 'woocommerce-for-japan' ),
				'white'      => __( 'White', 'woocommerce-for-japan' ),
				'monochrome' => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'  => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_flex_color'               => array(
			'title'        => __( 'Cart Messaging Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => $default_messaging_flex_color,
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'blue'            => __( 'Blue', 'woocommerce-for-japan' ),
				'black'           => __( 'Black', 'woocommerce-for-japan' ),
				'white'           => __( 'White', 'woocommerce-for-japan' ),
				'white-no-border' => __( 'White no border', 'woocommerce-for-japan' ),
				'gray'            => __( 'Gray', 'woocommerce-for-japan' ),
				'monochrome'      => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'       => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_flex_ratio'               => array(
			'title'        => __( 'Cart Messaging Ratio', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => '8x1',
			'desc_tip'     => true,
			'description'  => __( 'The width/height ratio of the banner. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'1x1'  => __( '1x1', 'woocommerce-for-japan' ),
				'1x4'  => __( '1x4', 'woocommerce-for-japan' ),
				'8x1'  => __( '8x1', 'woocommerce-for-japan' ),
				'20x1' => __( '20x1', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_cart_message_preview'                  => array(
			'type'         => 'ppcp-text',
			'text'         => $render_preview_element( 'ppcpCartMessagePreview', 'message' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),

		// Checkout.
		'pay_later_checkout_messaging_heading'            => array(
			'heading'      => __( 'Pay Later Messaging on Checkout', 'woocommerce-for-japan' ),
			'type'         => 'ppcp-heading',
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array(),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_layout'               => array(
			'title'        => __( 'Checkout Messaging Layout', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'text',
			'desc_tip'     => true,
			'description'  => __( 'The layout of the message.', 'woocommerce-for-japan' ),
			'options'      => array(
				'text' => __( 'Text', 'woocommerce-for-japan' ),
				'flex' => __( 'Banner', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_logo'                 => array(
			'title'        => __( 'Checkout Messaging Logo', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'inline',
			'desc_tip'     => true,
			'description'  => __( 'What logo the text message contains. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'primary'     => __( 'Primary', 'woocommerce-for-japan' ),
				'alternative' => __( 'Alternative', 'woocommerce-for-japan' ),
				'inline'      => __( 'Inline', 'woocommerce-for-japan' ),
				'none'        => __( 'None', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_position'             => array(
			'title'        => __( 'Checkout Messaging Logo Position', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'left',
			'desc_tip'     => true,
			'description'  => __( 'The position of the logo. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'left'  => __( 'Left', 'woocommerce-for-japan' ),
				'right' => __( 'Right', 'woocommerce-for-japan' ),
				'top'   => __( 'Top', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_color'                => array(
			'title'        => __( 'Checkout Messaging Text Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => 'black',
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Text is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'black'      => __( 'Black', 'woocommerce-for-japan' ),
				'white'      => __( 'White', 'woocommerce-for-japan' ),
				'monochrome' => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'  => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_flex_color'           => array(
			'title'        => __( 'Checkout Messaging Color', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => $default_messaging_flex_color,
			'desc_tip'     => true,
			'description'  => __( 'The color of the text. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'blue'            => __( 'Blue', 'woocommerce-for-japan' ),
				'black'           => __( 'Black', 'woocommerce-for-japan' ),
				'white'           => __( 'White', 'woocommerce-for-japan' ),
				'white-no-border' => __( 'White no border', 'woocommerce-for-japan' ),
				'gray'            => __( 'Gray', 'woocommerce-for-japan' ),
				'monochrome'      => __( 'Monochrome', 'woocommerce-for-japan' ),
				'grayscale'       => __( 'Grayscale', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_flex_ratio'           => array(
			'title'        => __( 'Checkout Messaging Ratio', 'woocommerce-for-japan' ),
			'type'         => 'select',
			'class'        => array(),
			'input_class'  => array( 'wc-enhanced-select' ),
			'default'      => '8x1',
			'desc_tip'     => true,
			'description'  => __( 'The width/height ratio of the banner. Only applicable, when the layout style Banner is used.', 'woocommerce-for-japan' ),
			'options'      => array(
				'1x1'  => __( '1x1', 'woocommerce-for-japan' ),
				'1x4'  => __( '1x4', 'woocommerce-for-japan' ),
				'8x1'  => __( '8x1', 'woocommerce-for-japan' ),
				'20x1' => __( '20x1', 'woocommerce-for-japan' ),
			),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
		'pay_later_checkout_message_preview'              => array(
			'type'         => 'ppcp-text',
			'text'         => $render_preview_element( 'ppcpCheckoutMessagePreview', 'message' ),
			'screens'      => array( State::STATE_ONBOARDED ),
			'requirements' => array( 'messages' ),
			'gateway'      => Settings::PAY_LATER_TAB_ID,
		),
	);

	return array_merge( $fields, $pay_later_fields );
};
