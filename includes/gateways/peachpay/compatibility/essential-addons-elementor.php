<?php
/**
 * Support for the essential addons for elementor plugin.
 * Plugin: https://wordpress.org/plugins/essential-addons-for-elementor-lite/
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

add_action( 'elementor/widgets/register', 'peachpay_essential_addons_compatibility', 1000 );

/**
 * Essential addons compatibility function.
 */
function peachpay_essential_addons_compatibility() {
	if ( peachpay_get_settings_option( 'peachpay_express_checkout_window', 'make_pp_the_only_checkout' ) && ! peachpay_is_test_mode() ) {
		do_hard_unregister_object_callback( 'woocommerce_proceed_to_checkout', 20, 'eael_cart_button_proceed_to_checkout' );
	}
}

/**
 * Do a hard unregister of an object's callback for the specified event name
 * and priority level.
 *
 * In WordPress, the callback key (or unique ID) is generated using the hash ID of
 * the object concatenated with the method name.  In the event that you do not have
 * the object itself, then we use this hard approach to first find the callback
 * function and then do the remove.
 *
 * @param string  $event_name The name of the filter or action event.
 * @param integer $priority Priority level.
 * @param string  $method_name Callback's method name.
 */
function do_hard_unregister_object_callback( $event_name, $priority, $method_name ) {
	$callback_function = get_object_callback_unique_id_from_registry( $event_name, $priority, $method_name );
	if ( ! $callback_function ) {
		return false;
	}

	remove_filter( $event_name, $callback_function, $priority );
}

/**
 * Get the object's event registry unique ID for the given event name, priority
 * level, and method name.
 *
 * @param string  $event_name The name of the filter or action event.
 * @param integer $priority Priority level.
 * @param string  $method_name Callback's method name.
 *
 * @return string|boolean
 */
function get_object_callback_unique_id_from_registry( $event_name, $priority, $method_name ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $event_name ][ $priority ] ) ) {
		return false;
	}

	foreach ( $wp_filter[ $event_name ][ $priority ] as $callback_function => $registration ) {
		if ( strpos( $callback_function, $method_name, 32 ) !== false ) {
			return $callback_function;
		}
	}

	return false;
}
