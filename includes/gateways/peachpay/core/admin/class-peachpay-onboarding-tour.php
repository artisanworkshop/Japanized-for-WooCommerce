<?php
/**
 * PeachPay Admin Onboarding Tour.
 *
 * @package PeachPay/Admin
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

/**
 * Used to create a PeachPay admin section for managing multiple tabs.
 */
final class PeachPay_Onboarding_Tour {

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Sets a given section of the onboarding tour to complete.
	 *
	 * @param string $section - Given step/section to mark as complete.
	 * @param mixed  $value - The value to update with, defaults to true.
	 */
	public static function complete_section( $section, $value = true ) {
		peachpay_set_settings_option( 'peachpay_onboarding_tour', $section, $value );
	}

	/**
	 * Gets a given section of the onboarding tour.
	 *
	 * @param string $section - Given step/section to mark get from options.
	 */
	public static function get_section( $section ) {
		return peachpay_get_settings_option( 'peachpay_onboarding_tour', $section );
	}

	/**
	 * Returns the static to-do list.
	 *
	 * @return array $todo_item The entire item with the following content:
	 * - @var bool $checked If this to-do item has been completed.
	 * - @var bool $hidden If the given element should not be displayed.
	 * - @var string $id (OPTIONAL) The id of this element for the HTML.
	 * - @var string $title The title of this to-do item.
	 * - @var string $description The description of this to-do item.
	 * - @var string|null $href (OPTIONAL) Provide a link to the specific link.
	 */
	private static function get_todo_items() {
		return array(
			'meta'                 => array( // Extra content to be passed to rendered.
				'onboarding_tour_title'       => null,
				'onboarding_tour_description' => null,
			),
			'connect-payment'      => array(
				'checked'     => false,
				'id'          => 'pp-onboarding-tour-add-first-gateway',
				'title'       => __( 'Connect a payment method', 'peachpay-for-woocommerce' ),
				'description' => __( 'Choose from Stripe, PayPal, Square, Authorize.net, and Amazon Pay', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'payment', '', '', false ) ),
			),
			'viewed-first-gateway' => array(
				'checked'     => false,
				'hidden'      => true,
				'id'          => 'pp-onboarding-tour-manage-first-gateway',
				'title'       => __( 'Manage a payment method', 'peachpay-for-woocommerce' ),
				'description' => __( 'Set minimum/maximum charges and fees, restrict countries, and change the appearance', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'payment', '', '', false ) ),
			),
			'analytics'            => array(
				'checked'     => false,
				'title'       => __( 'View checkout analytics', 'peachpay-for-woocommerce' ),
				'description' => __( 'Understand payment method usage and track abandoned carts', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'payment_methods', 'analytics', '', false ) . '&onboarding=analytics' ),
			),
			'currency'             => array(
				'checked'     => false,
				'hidden'      => true,
				'premium'     => true,
				'title'       => __( 'Set up currencies', 'peachpay-for-woocommerce' ),
				'description' => __( 'Configure rates, fees, and restrictions', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'currency', '', '', false ) . '&onboarding=currency' ),
			),
			'field_editor'         => array(
				'checked'     => false,
				'hidden'      => true,
				'premium'     => true,
				'title'       => __( 'Edit checkout fields', 'peachpay-for-woocommerce' ),
				'description' => __( 'Edit billing and shipping fields or add new ones', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'field', 'billing', '', false ) . '&onboarding=field_editor' ),
			),
			'related_products'     => array(
				'checked'     => false,
				'hidden'      => true,
				'premium'     => true,
				'title'       => __( 'Add recommended products', 'peachpay-for-woocommerce' ),
				'description' => __( 'Show shoppers related products while shopping', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'related_products', '', '', false ) . '&onboarding=related_products' ),
			),
			'express_checkout'     => array(
				'checked'     => false,
				'hidden'      => true,
				'premium'     => true,
				'title'       => __( 'Enable express checkout', 'peachpay-for-woocommerce' ),
				'description' => __( 'Let shoppers buy from anywhere', 'peachpay-for-woocommerce' ),
				'href'        => esc_url_raw( Peachpay_Admin::admin_settings_url( 'peachpay', 'express_checkout', 'button', '', false ) . '&onboarding=express_checkout' ),
			),
		);
	}

	/**
	 * Builds the onboarding tour.
	 *
	 * @param bool $premium_locked .
	 */
	public static function display_onboarding_tour( $premium_locked ) {
		// Check if the merchant force_complete
		if ( self::get_section( 'force_complete' ) ) {
			return;
		}

		$first_gateway = peachpay_gateway_available() ? PeachPay_Payment::available_gateways() : null;
		$first_gateway = $first_gateway && count( $first_gateway ) ? $first_gateway[0]->payment_provider : null;

		// Setup tour title and description (as this changes based on the
		//  current status of the modal):
		$onboarding_tour_title       = __( 'Welcome to PeachPay', 'peachpay-for-woocommerce' );
		$onboarding_tour_description = __( 'Here are some tips to make the most out of PeachPay', 'peachpay-for-woocommerce' );

		// phpcs:ignore
		if ( isset( $_GET['onboarding'] ) ) {
			// Check for force complete - since this check does a comparison, ignore warnings.
			// phpcs:ignore
			if ( 0 === strcmp( 'force_complete', $_GET['onboarding'] ) ) {
				self::complete_section( 'force_complete' );
				// Update onboarding tour message
				$onboarding_tour_title       = __( 'You\'re good to go with PeachPay', 'peachpay-for-woocommerce' );
				$onboarding_tour_description = __( 'Next time you load a PeachPay page, we\'ll remove this', 'peachpay-for-woocommerce' );
			}

			// Check existence in expected categories:
			$onboarding_endpoints = array(
				'currency'         => 1,
				'field_editor'     => 1,
				'related_products' => 1,
				'express_checkout' => 1,
			);

			// This only touches the contents of $_GET iff the value is in $onboarding_endpoints,
			//  meaning that otherwise (if not in the above onboarding endpoints) the contents
			//  will not get touched.
			// phpcs:ignore
			if ( array_key_exists( $_GET['onboarding'], $onboarding_endpoints ) ) {
				// phpcs:ignore
				self::complete_section( $_GET['onboarding'] );
			}
		}

		// Prefill $todo_items with elements that are in both premium and not
		// with the following structure:
		$todo_items = self::get_todo_items();

		$items_checked = $first_gateway ? 1 : 0;

		$todo_items['connect-payment']['checked'] = $first_gateway ? true : false;

		$todo_items['viewed-first-gateway']['hidden'] = ! $first_gateway;

		$todo_items_keys  = array_keys( $todo_items );
		$todo_items_count = count( $todo_items );
		for ( $todo_item_index = 2; $todo_item_index < $todo_items_count; $todo_item_index++ ) {
			$is_premium_todo_item = array_key_exists( 'premium', $todo_items[ $todo_items_keys[ $todo_item_index ] ] )
				&& $todo_items[ $todo_items_keys[ $todo_item_index ] ]['premium'];
			if ( $premium_locked && $is_premium_todo_item ) {
				$items_checked++;
				continue;
			} elseif ( $is_premium_todo_item ) {
				$todo_items[ $todo_items_keys[ $todo_item_index ] ]['hidden'] = false;
			}

			$todo_items[ $todo_items_keys[ $todo_item_index ] ]['checked'] = self::get_section( $todo_items_keys[ $todo_item_index ] );
			$items_checked += $todo_items[ $todo_items_keys[ $todo_item_index ] ]['checked'] ? 1 : 0;
		}

		if ( $items_checked === $todo_items_count - 1 ) {
			// Check if this was already completed
			if ( self::get_section( 'completed' ) ) {
				return;
			}

			self::complete_section( 'completed' );
			$onboarding_tour_title       = __( 'You\'re all set with PeachPay', 'peachpay-for-woocommerce' );
			$onboarding_tour_description = __( 'Next time you load a PeachPay page, we\'ll remove this', 'peachpay-for-woocommerce' );
		} elseif ( self::get_section( 'completed' ) ) {
			self::complete_section( 'completed', false );
		}

		$todo_items['meta']['onboarding_tour_title']       = $onboarding_tour_title;
		$todo_items['meta']['onboarding_tour_description'] = $onboarding_tour_description;

		require PEACHPAY_ABSPATH . 'core/admin/views/html-onboarding-tour-modal.php';
	}
}
