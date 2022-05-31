<?php
/**
 * PeachPay Currency Switcher Core File.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

add_action( 'peachpay_setup_module', 'peachpay_setup_currency_module' );

/**
 * Responsible for loading any files and setting up init functions.
 */
function peachpay_setup_currency_module() {

	if ( ! peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' ) ) {
		add_filter( 'update_option_peachpay_currency_options', 'peachpay_post_currency_changes', 10, 2 );
		return;
	}
	add_action( 'init', 'peachpay_init_currency_module' );

	add_filter( 'peachpay_register_feature', 'peachpay_currencies_to_modal', 10, 1 );
	add_action( 'peachpay_update_currency', 'peachpay_update_currency_schedule', 10, 1 );

	/**
	 * Price filters
	 */
	add_filter( 'woocommerce_product_get_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_get_sale_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_get_regular_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_variation_get_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_variation_get_regular_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_variation_get_sale_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_variation_prices_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_variation_prices_regular_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_variation_prices_sale_price', 'peachpay_update_currency_per_product_item', 10000, 2 );

	/**
	 * Hooks for changing decimals
	 */
	add_filter( 'wc_get_price_decimals', 'peachpay_change_decimals', 10, 1 );

	/**
	 * Shipping filter
	 */

	add_filter( 'woocommerce_package_rates', 'peachpay_cur_update_shipping_cost' );

	/**
	 * Currency filters
	 */
	add_filter( 'woocommerce_currency', 'peachpay_change_currency' );

	register_deactivation_hook( __FILE__, 'peachpay_unschedule_all' );

	// Add the currency conversion widget action.
	// add_action( 'widgets_init' , 'add_pp_currency_widget',1 ).

	// Add custom peachpay cron schedules.
	add_filter( 'cron_schedules', 'peachpay_add_cron_schedules', 1, 1 );

	// Post currency switch settings changes that must be fired off are fired off by this hook.
	add_filter( 'update_option_peachpay_currency_options', 'peachpay_post_currency_changes', 10, 2 );
}

/**
 * Initializes code after all other plugins are loaded( Depends on woocommerce ).
 */
function peachpay_init_currency_module() {
	// Set cookie for user.
	peachpay_make_currency_cookie();
}

/**
 * This function takes all currencies that have a time interval conversion rate and updates them no matter what their interval is
 * Mostly for calling at the end of changing a currency so some random value isn't stored into the conversion rate
 */
function peachpay_force_update_currencies() {
	$currency_options = get_option( 'peachpay_currency_options' );
	if ( null !== ( $currency_options ) ) {
		$currencies_selected = $currency_options['selected_currencies'];
		foreach ( $currencies_selected as $key => $currency ) {
			if ( $currency && array_key_exists( 'type', $currency ) && 'custom' !== $currency['type'] ) {
				$currencies_selected[ $key ]['rate'] = peachpay_update_currency_rates( $currency['name'] );
			}
		}
		$currency_options['selected_currencies'] = $currencies_selected;
		update_option( 'peachpay_currency_options', $currency_options, true );
		peachpay_currency_cron();
	}
}

/**
 * Function used to update the rate of a currency, right now only one source of conversion available but hopefully more will be added soon.
 *
 * @param string $currency Currency code passed to the function.
 */
function peachpay_update_currency_rates( $currency ) {
	$base_currency = get_option( 'woocommerce_currency' );
	$convert_key   = 'fcb822afad46476295dd02da4cfd2aed';
	// Breaks the currency switcher if we use rawurlencode and wp_remote_get so these stay for now.
	$base         = rawurlencode( $base_currency );
	$convert_to   = rawurlencode( $currency );
	$query_string = "{$base}_{$convert_to}";

	$data = wp_remote_get( "https://api.currconv.com/api/v7/convert?q={$query_string}&compact=ultra&apiKey={$convert_key}" );

	if ( is_wp_error( $data ) ) {
		return 100000000000000;
	}

	$data = json_decode( $data['body'], true );

	$rate = $data[ "$query_string" ];

	return $rate;
}

/**
 * This function will setup what events need to be handled and removed if there are no currencies with these settings then we don't have to schedule the event.
 */
function peachpay_currency_cron() {
	$currency_options    = get_option( 'peachpay_currency_options' );
	$currencies_selected = $currency_options['selected_currencies'];
	// Unschedule all prior events and then reschedule them so if a user won't get wasted resources when an event fires off for no reason.
	Peachpay_unschedule_all_currency();
	if ( isset( $currencies_selected ) ) {
		foreach ( $currencies_selected as $currency ) {
			if ( ! wp_next_scheduled( 'peachpay_update_currency', array( $currency['type'] ) ) ) {
				wp_schedule_event( time(), $currency['type'], 'peachpay_update_currency', array( $currency['type'] ) );
			}
		}
	}
}

/**
 * Used by WordPress cron to update a currency conversion rate to base store currency.
 *
 * @param string $time The time period specified.
 */
function peachpay_update_currency_schedule( $time ) {
	$currency_options     = get_option( 'peachpay_currency_options' );
	$currencies_selected  = $currency_options['selected_currencies'];
	$number_of_currencies = $currency_options['num_currencies'];

	for ( $i = 0; $i < $number_of_currencies; $i++ ) {
		if ( isset( $currencies_selected[ $i ] ) && $currencies_selected [ $i ] ['type'] === $time ) {
			$currencies_selected [ $i ] ['rate'] = peachpay_update_currency_rates( $currencies_selected[ $i ]['name'] );
		}
	}
	$currency_options['selected_currencies'] = $currencies_selected;
	update_option( 'peachpay_currency_options', $currency_options, true );
	return $time;
}

/**
 * Updates the price of an item not using the raw price filter anymore just already defined function.
 *
 * @param string|float $price the price of an item.
 * @param string       $to_convert the currency code that will be converted to.
 */
function peachpay_update_raw_price_per_product( $price, $to_convert = null ) {
	if ( is_string( $price ) ) {
		$price = floatval( $price );
	}
	if ( isset( $_COOKIE ) && isset( $_COOKIE['pp_active_currency'] ) ) {
		$to_convert = sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) );
	}
	$currency_options    = get_option( 'peachpay_currency_options' );
	$currencies_selected = $currency_options['selected_currencies'];
	$rate                = 1;
	$decimals            = peachpay_currency_decimals();
	$round               = 'none';
	foreach ( $currencies_selected as $currency ) {
		if ( $currency && $currency['name'] === $to_convert ) {
			$rate     = floatval( $currency['rate'] );
			$decimals = $currency['decimals'];
			$round    = $currency['round'];

		}
	}
	if ( 'up' === $round ) {
		$round = PHP_ROUND_HALF_UP;
	} elseif ( 'down' === $round ) {
		$round = PHP_ROUND_HALF_DOWN;
	} else {
		$round = 0;
	}
	$decimals = intval( $decimals );
	$price    = round( $price * $rate, $decimals, $round );
	return $price;
}

/**
 * Updates cart price of an item.
 *
 * @param string|float $wctotal Total cost of the item in woocommerce.
 * @param array        $wcitem The item in in woocommerce.
 * @param string       $itemkey The items key.
 */
function peachpay_update_currency_per_product_cart( $wctotal, $wcitem, $itemkey ) {
	$original_price = $wcitem['data']->price;
	$new_price      = peachpay_update_raw_price_per_product( $original_price );
	$wctotal        = $new_price;
	return $wctotal;
}

/**
 * Takes in a price and a product and according to the set cookie will update the products price without changing actual product.
 *
 * @param string|float $price the cost of the item in woocommerce.
 * @param array        $product the woocommerce object for the product.
 */
function peachpay_update_currency_per_product_item( $price, $product ) {
	$new_price = peachpay_update_raw_price_per_product( $price );
	return $new_price;
}

/**
 * Changes what woocommerce recognizes as the default currency.
 *
 * @param string $currency_base The currency that is set as default in woocommerce.
 */
function peachpay_change_currency( $currency_base ) {
	foreach ( get_option( 'peachpay_currency_options' )['selected_currencies'] as $currency ) {
		if ( isset( $_COOKIE ) && ( $currency ) && isset( $_COOKIE['pp_active_currency'] ) && $_COOKIE['pp_active_currency'] === $currency['name'] ) {
			return $currency['name'];
		}
	}
	return $currency_base;
}

/**
 * Goes through all shipping options updating thenm to reflect new currency changes.
 *
 * @param array $data The shipping options for the object.
 */
function peachpay_cur_update_shipping_cost( $data ) {
	$new_shipping_options = $data;
	foreach ( $new_shipping_options as $shipping_option ) {
		$cost     = $shipping_option->__get( 'cost' );
		$new_cost = peachpay_update_raw_price_per_product( $cost );
		$shipping_option->__set( 'cost', $new_cost );
		$tax = $shipping_option->__get( 'taxes' );
		foreach ( $tax as $ship_tax => $value ) {
			$tax[ $ship_tax ] = peachpay_update_raw_price_per_product( $value );
		}
		$shipping_option->__set( 'taxes', $tax );
	}
	return $new_shipping_options;
}

/**
 * Change the default decimal amount.
 *
 * @param int $base the amount of decmials the currency supports.
 */
function peachpay_change_decimals( $base ) {
	$currency_options = get_option( 'peachpay_currency_options' );
	if ( null === $currency_options ) {
		return $base;
	}
	foreach ( $currency_options['selected_currencies'] as $currency ) {
		if ( $_COOKIE && isset( $_COOKIE['pp_active_currency'] ) && $_COOKIE['pp_active_currency'] === $currency['name'] ) {
			return $currency['decimals'];
		}
	}
}

/**
 * Function to add a filter to send available currencies to modal.
 *
 * @param array $data Peachpay data array.
 */
function peachpay_currencies_to_modal( $data ) {
	$currencies = get_option( 'peachpay_currency_options' )['selected_currencies'];

	if ( count( $currencies ) < 2 ) {
		$data['currency_switcher_input']['enabled'] = false;
		return $data;
	}

	$data['currency_switcher_input']['enabled'] = true;
	$data['currency_switcher_input']['version'] = 1;
	$metadata                                   = array();
	$currency_names                             = array();
	$currency_info                              = array();
	foreach ( $currencies as $currency ) {
		if ( $currency && array_key_exists( 'name', $currency ) ) {
			$currency_names[ $currency['name'] ] = $currency['name'];

			$currency_info[ $currency['name'] ] ['code']                = $currency['name'];
			$currency_info[ $currency['name'] ] ['overridden_code']     = $currency['name'];
			$currency_info[ $currency['name'] ] ['symbol']              = get_woocommerce_currency_symbol( $currency['name'] );
			$currency_info[ $currency['name'] ] ['position']            = peachpay_currency_position();
			$currency_info[ $currency['name'] ] ['thousands_separator'] = peachpay_currency_thousands_separator();
			$currency_info[ $currency['name'] ] ['decimal_separator']   = peachpay_currency_decimal_separator();
			$currency_info[ $currency['name'] ] ['number_of_decimals']  = $currency['decimals'];
			$currency_info[ $currency['name'] ] ['rounding']            = $currency['round'];
		}
	}
	$metadata['set_cur']                         = isset( $_COOKIE['pp_active_currency'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) ) : '';
	$metadata['currencies']                      = $currency_names;
	$metadata['currency_info']                   = $currency_info;
	$data['currency_switcher_input']['metadata'] = $metadata;
	return $data;
}

/**
 * Changes the model currency.
 *
 * @param float  $amount the price of the items in the cart.
 * @param string $code the currency code to convert to.
 */
function peachpay_update_cur_from_modal( $amount, $code ) {
	return peachpay_update_raw_price_per_product( $amount, $code );
}

/**
 * After a update to what currencies are enabled will do some checks and changes so our currencies have proper values.
 *
 * @param array $old previous settings being used.
 * @param array $new new settings being used.
 */
function peachpay_post_currency_changes( $old, $new ) {
	if ( $old['num_currencies'] < $new['num_currencies'] ) {
		for ( $i = 0; $i < ( $new['num_currencies'] - $old['num_currencies'] ); $i++ ) {
			array_push( $new['selected_currencies'], $old['selected_currencies']['base'] );
		}
	}
	update_option( 'peachpay_currency_options', $new );
	peachpay_force_update_currencies();
}

/**
 * Add cron schedules on initialization otherwise it won't have them when we need to call them.
 *
 * @param array $schedules The schedules we are adding.
 */
function peachpay_add_cron_schedules( $schedules ) {
	$schedules['15minute'] = array(
		'interval' => 900,
		'display'  => esc_html__( 'Every 15 minutes' ),
	);
	$schedules['30minute'] = array(
		'interval' => 1800,
		'display'  => esc_html__( 'Every 30 minutes' ),
	);
	$schedules['hourly']   = array(
		'interval' => 3600,
		'display'  => esc_html__( 'Every hour' ),
	);
	$schedules['6hour']    = array(
		'interval' => 21600,
		'display'  => esc_html__( 'Every 6 hours' ),
	);
	$schedules['12hour']   = array(
		'interval' => 43200,
		'display'  => esc_html__( 'Every 12 hours' ),
	);
	$schedules['2day']     = array(
		'interval' => 172800,
		'display'  => esc_html__( 'Every 2 days' ),
	);
	$schedules['weekly']   = array(
		'interval' => 604800,
		'display'  => esc_html__( 'weekly' ),
	);
	$schedules['biweekly'] = array(
		'interval' => 604800 * 2,
		'display'  => esc_html__( 'Biweekly' ),
	);
	$schedules['monthly']  = array(
		'interval' => 86400 * 30,
		'display'  => esc_html__( 'Every month' ),
	);

	return $schedules;
}

/**
 * Initialize a cookie for the currency on visit from a customer.
 */
function peachpay_make_currency_cookie() {
	if ( empty( $_COOKIE ) || isset( $_COOKIE['pp_active_currency'] ) && ! peachpay_get_settings_option( 'peachpay_currency_options', 'geo_locate' ) ) {
		return;
	}
	setcookie( 'pp_active_currency', get_woocommerce_currency(), time() + ( 60 * 60 * 24 * 30 ), '/' );
}

register_deactivation_hook( __FILE__, 'peachpay_unschedule_all' );
/**
 * Unschedule all pp cron events on deactivation.
 */
function peachpay_unschedule_all_currency() {
	$times = array(
		'15minute',
		'30minute',
		'hourly',
		'2hour',
		'6hour',
		'12hour',
		'day',
		'2day',
		'weekly',
		'biweekly',
		'monthly',
	);

	foreach ( $times as $time ) {
		if ( wp_next_scheduled( 'peachpay_update_currency', array( $time ) ) ) {
			$timestamp = wp_next_scheduled( 'peachpay_update_currency', array( $time ) );
			wp_unschedule_event( $timestamp, 'peachpay_active_currency', array( $time ) );
		}
	}
}

// Hook for removing the cookie on plugin deactivation.
register_deactivation_hook( __FILE__, 'peachpay_remove_currency_cookie' );

/**
 * On plugin deactivation remove the currency cookie.
 */
function peachpay_remove_currency_cookie() {
	if ( $_COOKIE && ! empty( $_COOKIE['pp_active_currency'] ) ) {
		unset( $_COOKIE['pp_active_currency'] );
	}
}

