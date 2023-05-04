<?php
/**
 * PeachPay Currency Switcher Core File.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/widget/class-peachpay-currency-widget.php';
require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/util/peachpay-currency-arrays.php';
require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/util/currency-uninstall.php';
require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/util/currency-geo.php';

add_action( 'woocommerce_loaded', 'peachpay_setup_currency_module' );

/**
 * Responsible for loading any files and setting up init functions.
 */
function peachpay_setup_currency_module() {

	if ( ! peachpay_supports_currency() || ! peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' ) ) {
		add_filter( 'update_option_peachpay_currency_options', 'peachpay_post_currency_changes', 1, 2 );
		add_filter( 'update_option_woocommerce_currency', 'peachpay_post_base_currency_change', 1, 2 );
		peachpay_unschedule_all_currency();
		return;
	}
	// Add paypal settings.
	// TODO: unused functionality, either update to new paypal or delete
	add_action( 'peachpay_paypal_extra_settings', 'peachpay_field_auto_convert_paypal' );

	// Add custom peachpay cron schedules.
	add_filter( 'cron_schedules', 'peachpay_add_cron_schedules', 1, 1 );

	add_action( 'init', 'peachpay_init_currency_module', 1 );

	add_filter( 'peachpay_register_feature', 'peachpay_currencies_to_modal', 10, 1 );
	add_action( 'peachpay_update_currency', 'peachpay_cron_update_currencies', 10, 1 );
	add_filter( 'peachpay_register_feature', 'peachpay_add_defaults', 10, 1 );
	/**
	 * Price filters
	 */
	add_filter( 'woocommerce_product_get_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_get_regular_price', 'peachpay_update_currency_per_product_item', 10000, 2 );
	add_filter( 'woocommerce_product_get_sale_price', 'peachpay_update_product_sale_price', 10000, 2 );

	add_filter( 'woocommerce_product_variation_get_price', 'peachpay_update_price_variable_product', 1, 2 );
	add_filter( 'woocommerce_product_variation_get_regular_price', 'peachpay_update_price_variable_product', 1000, 2 );
	add_filter( 'woocommerce_product_variation_get_sale_price', 'peachpay_update_sale_price_variable_product', 1000, 2 );

	add_filter( 'woocommerce_variation_prices_price', 'peachpay_update_price_variable_product', 10000, 2 );
	add_filter( 'woocommerce_variation_prices_regular_price', 'peachpay_update_price_variable_product', 10000, 2 );
	add_filter( 'woocommerce_variation_prices_sale_price', 'peachpay_update_price_variable_product', 10000, 2 );

	/**
	 * Hooks for changing decimals
	 */
	add_filter( 'wc_get_price_decimals', 'peachpay_change_decimals', 100, 1 );

	/**
	 * Shipping filter
	 */

	add_filter( 'woocommerce_package_rates', 'peachpay_cur_update_shipping_cost' );

	/**
	 * Currency filters
	 */
	add_filter( 'woocommerce_currency', 'peachpay_change_currency', 100 );

	// Deactivation hooks.
	register_deactivation_hook( __FILE__, 'peachpay_unschedule_all_currency' );
	register_deactivation_hook( __FILE__, 'peachpay_remove_currency_cookie' );

	// Add the currency conversion widget action.
	add_action( 'widgets_init', 'add_pp_currency_widget', 1 );

	// Post currency switch settings changes that must be fired off are fired off by this hook.
	add_filter( 'update_option_peachpay_currency_options', 'peachpay_post_currency_changes', 1, 2 );
	add_filter( 'update_option_woocommerce_currency', 'peachpay_post_base_currency_change', 1, 2 );
	add_filter( 'update_option_woocommerce_price_num_decimals', 'peachpay_post_base_currency_change', 1, 2 );

	// Handle billing address change if required.
	add_action( 'woocommerce_checkout_update_order_review', 'peachpay_update_billing_currency' );
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
 *
 * @param string $time the specicic time stamp we are updaitng.
 */
function peachpay_cron_update_currencies( $time = 'all' ) {
	$currencies_selected = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies' );
	$options             = get_option( 'peachpay_currency_options', array() );
	$updated_currencies  = peachpay_update_currencies( $currencies_selected );

	if ( ! empty( $options['every_currency'] ) ) {
		$options['every_currency_table'] = $currencies_selected;
		$options['flag']                 = 'update';
		return;
	}
	if ( 'all' === $time ) {
		$options['selected_currencies'] = $updated_currencies;
		$options['flag']                = 'update';
		update_option( 'peachpay_currency_options', $options );
		return;
	}
	foreach ( $currencies_selected as $key => $currency ) {
		if ( 'base' === $key ) {
			continue;
		}
		if ( $currency['custom_interval'] === $time ) {
			$currencies_selected[ $key ]['rate'] = $updated_currencies[ $key ]['rate'];
		}
	}
	$options['selected_currencies'] = $currencies_selected;
	$options['flag']                = 'update';
	update_option( 'peachpay_currency_options', $options );
}

/**
 * Update currencies in tables specified by $currencies.
 *
 * @param array $currencies the currencies we are updating.
 */
function peachpay_update_currencies( $currencies ) {
	$rates = peachpay_update_currency_rates();
	foreach ( $currencies as $key => $currency ) {
		if ( array_key_exists( 'auto_update', $currency ) && '1' === $currency['auto_update'] ) {
			if ( empty( $rates[ $currency['name'] ] ) || ! $rates[ $currency['name'] ] ) {
				$rate = ( 1000000 === $currencies[ $key ]['rate'] ) ? 1000000 : $currencies[ $key ]['rate'];
			} else {
				$rate = $rates[ $currency['name'] ];
			}
			$currencies[ $key ]['rate'] = $rate;
		}
	}
	return $currencies;
}

/**
 * Function used to update the rate of all currencies then let the other function ahndle the rest.
 */
function peachpay_update_currency_rates() {
	$base = get_option( 'woocommerce_currency' );
	$data = wp_remote_get( peachpay_api_url( peachpay_is_test_mode() ) . "api/v1/getAllCurrency?from={$base}" );

	if ( is_wp_error( $data ) || wp_remote_retrieve_response_code( $data ) === 400 ) {
		return array();
	}

	$data = json_decode( $data['body'], true );

	$rate = $data['rates'];

	return $rate;
}

/**
 * This function will setup what events need to be handled and removed if there are no currencies with these settings then we don't have to schedule the event.
 *
 * @param boolean $all if it is support every currency.
 */
function peachpay_currency_cron( $all = false ) {
	// Unschedule all prior events and then reschedule them so if a user won't get wasted resources when an event fires off for no reason.
	peachpay_unschedule_all_currency();
	if ( $all ) {
		wp_schedule_event( time(), '15minute', 'peachpay_update_currency' );
		return;
	}

	$update_time = peachpay_get_settings_option( 'peachpay_currency_options', 'update_frequency', '15_minute' );

	if ( ! peachpay_get_settings_option( 'peachpay_currency_options', 'custom_intervals' ) ) {
		wp_schedule_event( time(), $update_time, 'peachpay_update_currency' );
		return;
	}
	foreach ( peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() ) as $key => $currency ) {
		if ( 'base' === $key ) {
			continue;
		}
		if ( ! wp_next_scheduled( 'peachpay_update_currency', array( $currency['custom_interval'] ) ) && 'none' !== $currency['custom_interval'] ) {
			wp_schedule_event( time(), $currency['custom_interval'], 'peachpay_update_currency', array( $currency['custom_interval'] ) );
		}
	}
	wp_schedule_event( time(), $update_time, 'peachpay_update_currency', array( 'none' ) );

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

	$to_convert          = peachpay_get_cookie_value();
	$currencies_selected = peachpay_get_active_currencies( peachpay_get_client_country() );
	$rate                = 1;
	$decimals            = peachpay_currency_decimals();
	$round               = 'none';

	foreach ( $currencies_selected as $currency ) {
		if ( $currency && $currency['name'] === $to_convert ) {
			$rate     = floatval( $currency['rate'] );
			$decimals = $currency['decimals'];
			$round    = $currency['round'];
			break;
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
 * Takes in a price and a variable product and according to the set cookie will update the products price without changing actual product.
 * This version is specifically for variable products as they seem to cache their price so we have to clear that cached price before we display a new one.
 *
 * @param string|float $price the cost of the item in woocommerce.
 * @param object       $product the woocommerce object for the product.
 */
function peachpay_update_price_variable_product( $price, $product ) {
	wc_delete_product_transients( $product->get_id() );
	$new_price = peachpay_update_raw_price_per_product( $price );
	return $new_price;
}

/**
 * Takes in a sale price of a variable product and according to the set cookie will update the products sale price, if it exists.
 * This version is specifically for variable products as they seem to cache their price so we have to clear that cached price before we display a new one.
 *
 * @param string|float $price the cost of the item in woocommerce.
 * @param object       $product the woocommerce object for the product.
 */
function peachpay_update_sale_price_variable_product( $price, $product ) {
	wc_delete_product_transients( $product->get_id() );
	if ( $price ) {
		$new_price = peachpay_update_raw_price_per_product( $price );
		return $new_price;
	}
}

/**
 * Check if a product is on sale before updating the price with get_sale_price
 *
 * @param string|float $price the cost of the item in woocommerce.
 * @param object       $product the woocommerce product object.
 */
function peachpay_update_product_sale_price( $price, $product ) {
	if ( $price ) {
		return peachpay_update_raw_price_per_product( $price );
	}
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
 * @param object       $product the woocommerce object for the product.
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
	return peachpay_get_cookie_value();
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
	$currency_options = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() );
	if ( null === $currency_options ) {
		return $base;
	}
	$cookie = peachpay_get_cookie_value();
	foreach ( $currency_options as $currency ) {
		if ( $cookie === $currency['name'] ) {
			return $currency['decimals'];
		}
	}
}

/**
 * Generates modal currency data from a given country.
 *
 * @param string $country Country code.
 */
function peachpay_currencies_to_modal_from_country( $country ) {
	$currencies    = peachpay_get_active_currencies( $country );
	$metadata      = array();
	$currency_info = array();

	foreach ( $currencies as $key => $currency ) {
		$currency_info[ $currency['name'] ] ['name']                = PEACHPAY_SUPPORTED_CURRENCIES[ $currency['name'] ];
		$currency_info[ $currency['name'] ] ['code']                = $currency['name'];
		$currency_info[ $currency['name'] ] ['overridden_code']     = $currency['name'];
		$currency_info[ $currency['name'] ] ['symbol']              = get_woocommerce_currency_symbol( $currency['name'] );
		$currency_info[ $currency['name'] ] ['position']            = peachpay_currency_position();
		$currency_info[ $currency['name'] ] ['thousands_separator'] = peachpay_currency_thousands_separator();
		$currency_info[ $currency['name'] ] ['decimal_separator']   = peachpay_currency_decimal_separator();
		$currency_info[ $currency['name'] ] ['number_of_decimals']  = intval( $currency['decimals'] );
		$currency_info[ $currency['name'] ] ['rounding']            = $currency['round'];
	}

	$data['version']                   = 1;
	$data['enabled']                   = peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' );
	$metadata['set_cur']               = peachpay_best_currency( $country );
	$metadata['currency_info']         = $currency_info;
	$metadata['how_currency_defaults'] = peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults', 'geolocate' );
	$metadata['add_conversion_fees']   = peachpay_get_settings_option( 'peachpay_currency_options', 'add_conversion_fees' );
	$data['metadata']                  = $metadata;

	if ( count( $currencies ) === 1 ) {
		$data['metadata']['active_currency'] = reset( $currency_info );
	}

	return $data;
}

/**
 * Function to add a filter to send available currencies to modal.
 *
 * @param array $data Peachpay data array.
 */
function peachpay_currencies_to_modal( $data ) {
	$data['currency_switcher_input'] = peachpay_currencies_to_modal_from_country( peachpay_get_client_country() );
	$data['metadata']['set_cur']     = peachpay_get_cookie_value();
	return $data;
}


/**
 * Whether or not to hide a currency in the list or show it.
 *
 * @param string $currency_name the name of the currency in question.
 * @param  array  $allowed_currencies allowed in the modal.
 */
function peachpay_hide_currency( $currency_name, $allowed_currencies ) {
	if ( 1 > count( $allowed_currencies ) ) {
		return false;
	}
	foreach ( $allowed_currencies as $currency ) {
		if ( $currency === $currency_name ) {
			return false;
		}
	}
	return true;
}

/**
 * Get payment method default currencies.
 */
function peachpay_get_method_default_currencies() {
	$defaults = array();

	$paypal = peachpay_get_settings_option( 'peachpay_payment_options', 'paypal_auto_convert', 'none' );

	'none' === $paypal ? '' : $defaults['paypal'] = $paypal;

	return $defaults;
}

/**
 * Decouple the sending of currencies defaults from the currencies themselves with this function
 *
 * @param array $data the data being sent about currencies to the modal.
 */
function peachpay_add_defaults( $data ) {
	$paypal_default = peachpay_get_settings_option( 'peachpay_payment_options', 'paypal_auto_convert', 'none' );
	if ( array_key_exists( 'paypal_payment_method', $data ) ) {
		$data['paypal_payment_method']['metadata']['default_currency'] = $paypal_default;
	}

	return $data;
}

/**
 * Changes the model currency deprecated since the modal uses WC functions now.
 *
 * @param float  $amount the price of the items in the cart.
 * @param string $code the currency code to convert to.
 *
 * @deprecated
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
	if ( array_key_exists( 'flag', $new ) ) {
		switch ( $new['flag'] ) {
			case 'all':
				$new['selected_currencies'] = peachpay_rebuild_all_currencies( $new['selected_currencies']['base'] );
				unset( $new['flag'] );
				break;
			case 'remove':
				$new['selected_currencies'] = array(
					'base' => $new['selected_currencies']['base'],
				);
				unset( $new['flag'] );
				break;
			case 'update':
				unset( $new['flag'] );
				return $new;
			default:
				return;
		}
	}
	if ( ! empty( $new['new_flag'] ) ) {
		array_push( $new['selected_currencies'], $new['selected_currencies']['base'] );
	}

	$paypal_default        = peachpay_get_settings_option( 'peachpay_payment_settings', 'paypal_defuault', 'none' );
	$paypal_default_exists = true;
	foreach ( $new['selected_currencies'] as $currency ) {
		if ( array_key_exists( $paypal_default, $currency ) ) {
			$paypal_default_exists = false;
		}
	}
	$paypal_default_exists ? '' : update_option( 'peachpay_payment_options[paypal_default]', 'none' );

	peachpay_currency_cron();
	$new['selected_currencies'] = peachpay_update_currencies( $new['selected_currencies'] );
	$new['flag']                = 'update';
	update_option( 'peachpay_currency_options', $new );
	return $new;
}

/**
 * Since we are migrating over to one table this function rebuilds all the currencies.
 *
 * @param array $base the base currency from the array to append to the array.
 */
function peachpay_rebuild_all_currencies( $base ) {
	$base['countries'] = ',' . substr( $base['name'], 0, 2 );
	$currencies        = array( 'base' => $base );
	foreach ( PEACHPAY_SUPPORTED_CURRENCIES as $key => $name ) {
		if ( get_woocommerce_currency() === $key ) {
			continue;
		}
		$currency = array(
			'name'            => $key,
			'auto_update'     => '1',
			'rate'            => 1,
			'decimals'        => peachpay_is_zero_decimal_currency( $key ) ? 0 : 2,
			'custom_interval' => 'none',
			'round'           => 'up',
			'countries'       => peachpay_get_proper_countries( $key ),
		);
		array_push( $currencies, $currency );
	}
	return $currencies;
}

/**
 * Since some currencies are for mulitple countries take those into account.
 *
 * @param string $code the currency code.
 */
function peachpay_get_proper_countries( $code ) {
	switch ( $code ) {
		case 'XCD':
			return ',AG,DM,GD,KN,LC,VC,AI,MS';
		case 'XOF':
			return ',SN,CI,ML,BF,NE,GW';
		case 'XAF':
			return ',CM,GA,TD,CD,CF,GQ';
		case 'EUR':
			return ',AD,AL,AT,AX,BA,BE,BG,BY,CH,CZ,DE,DK,EE,ES,EU,FI,FO,FR,FX,GB,GG,GI,GR,HR,HU,IE,IM,IS,IT,JE,LI,LT,LU,LV,MC,MD,ME,MK,MT,NL,NO,PL,PT,RO,RS,RU,SE,SI,SJ,SK,SM,TR,UA,VA';
		case 'XPF':
			return ',FR,PF,WF,NC';
		default:
			return substr( $code, 0, 2 );
	}
}

/**
 * Initialize a cookie for the currency on visit from a customer.
 */
function peachpay_make_currency_cookie() {
	if ( empty( $_COOKIE ) ) {
		return;
	}

	$location           = peachpay_get_client_country();
	$allowed_currencies = peachpay_currencies_by_iso( $location );

	if ( isset( $_COOKIE['pp_active_currency'] ) ) {
		$prev = sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) );

		if ( 'billing_country' === peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults' ) && ! isset( WC()->customer ) ) {
			setcookie( 'pp_active_currency', $prev, time() + 60 * 60 * 24, '/' );
			return;
		}

		foreach ( $allowed_currencies as $currency ) {
			if ( $prev === $currency ) {
				setcookie( 'pp_active_currency', $prev, time() + 60 * 60 * 24, '/' );
				return;
			}
		}
	}

	$best = peachpay_best_currency( peachpay_get_client_country() );
	setcookie( 'pp_active_currency', $best, time() + 60 * 60 * 24, '/' );
	$_COOKIE['pp_active_currency'] = $best;
	add_action( 'woocommerce_before_shop_loop', 'peachpay_render_currency_notice' );

}

/**
 * Get the base currency since get_woocommerce_currency returns the active currency.
 */
function peachpay_get_base_currency() {
	$currencies = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() );

	if ( empty( $currencies ) ) {
		return get_woocommerce_currency();
	}

	return $currencies['base']['name'];
}

/**
 * Add cron schedules on initialization otherwise it won't have them when we need to call them.
 *
 * @param array $schedules The schedules we are adding.
 */
function peachpay_add_cron_schedules( $schedules ) {
	$schedules['15minute'] = array(
		'interval' => 900,
		'display'  => esc_html__( 'Every 15 minutes', 'peachpay-for-woocommerce' ),
	);
	$schedules['30minute'] = array(
		'interval' => 1800,
		'display'  => esc_html__( 'Every 30 minutes', 'peachpay-for-woocommerce' ),
	);
	$schedules['hourly']   = array(
		'interval' => 3600,
		'display'  => esc_html__( 'Every hour', 'peachpay-for-woocommerce' ),
	);
	$schedules['6hour']    = array(
		'interval' => 21600,
		'display'  => esc_html__( 'Every 6 hours', 'peachpay-for-woocommerce' ),
	);
	$schedules['12hour']   = array(
		'interval' => 43200,
		'display'  => esc_html__( 'Every 12 hours', 'peachpay-for-woocommerce' ),
	);
	$schedules['2day']     = array(
		'interval' => 172800,
		'display'  => esc_html__( 'Every 2 days', 'peachpay-for-woocommerce' ),
	);
	$schedules['weekly']   = array(
		'interval' => 604800,
		'display'  => esc_html__( 'weekly', 'peachpay-for-woocommerce' ),
	);
	$schedules['biweekly'] = array(
		'interval' => 604800 * 2,
		'display'  => esc_html__( 'Biweekly', 'peachpay-for-woocommerce' ),
	);
	$schedules['monthly']  = array(
		'interval' => 86400 * 30,
		'display'  => esc_html__( 'Every month', 'peachpay-for-woocommerce' ),
	);

	return $schedules;
}

/**
 * Check if a currency is a zero decimal currency.
 *
 * @param string $currency the currency to check.
 */
function peachpay_is_zero_decimal_currency( $currency ) {
	$zero_decimals = array(
		'BIF',
		'CLP',
		'DJF',
		'GNF',
		'JPY',
		'KMF',
		'KRW',
		'MGA',
		'PYG',
		'RWF',
		'UGX',
		'VND',
		'VUV',
		'XAF',
		'XOF',
		'XPF',
		'TWD',
		'HUF',
	);
	return in_array( $currency, $zero_decimals, true );
}

/**
 * Returns selected_currencies from settings options formatted to correct data values.
 *
 * @param string $filter_country Filter only currencies supported by ISO.
 */
function peachpay_get_active_currencies( $filter_country = null ) {
	$active_currencies          = peachpay_get_settings_option( 'peachpay_currency_options', 'selected_currencies', array() );
	$supported_currencies       = peachpay_currencies_by_iso( $filter_country );
	$filtered_active_currencies = array();
	$defaults                   = peachpay_get_method_default_currencies();

	foreach ( $active_currencies as $currency => $data ) {
		if ( isset( $data['fees'] ) ) {
			$fee_data = array_values( $data['fees'] );

			foreach ( $fee_data as $i => $fee ) {
				if ( ! isset( $fee['value'] ) || '' === $fee['value'] || '.' === $fee['value'] ) {
					$fee_data[ $i ]['value'] = 0;
				}
				$fee_data[ $i ]['value'] = floatval( $fee['value'] );
			}

			$active_currencies[ $currency ]['fees'] = $fee_data;
		}

		if ( ( in_array( $data['name'], $supported_currencies, true ) && ( ! isset( $data['countries'] ) || in_array( $filter_country, explode( ',', $data['countries'] ), true ) || '' === $data['countries'] ) ) || ( in_array( $data['name'], $defaults, true ) ) ) {
			array_push( $filtered_active_currencies, $active_currencies[ $currency ] );
		}
	}

	if ( null !== $filter_country && '' !== $filter_country && ! empty( $filtered_active_currencies ) ) {
		return $filtered_active_currencies;
	}

	return $active_currencies;
}

/**

 * Returns the value of the cookie if it exists. If it does not exist just return the best currency.
 */
function peachpay_get_cookie_value() {
	if ( isset( $_COOKIE['pp_active_currency'] ) ) {
		return sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) );
	}
	return peachpay_best_currency( peachpay_get_client_country() );
}

/**
 * If the woocommerce currency updates we want to also update our rates. Rare but good to have.
 *
 * @param string $old the old currency.
 * @param string $new the new currency.
 */
function peachpay_post_base_currency_change( $old, $new ) {
	$options  = get_option( 'peachpay_currency_options' );
	$name     = get_option( 'woocommerce_currency' );
	$decimals = get_option( 'woocommerce_price_num_decimals' );
	if ( ! array_key_exists( $name, PEACHPAY_SUPPORTED_CURRENCIES ) ) {
		add_action( 'admin_notices', 'peachpay_unsupported_currency_error' );
		$options['enabled'] = false;
		peachpay_unschedule_all_currency();
		peachpay_remove_currency_cookie();
		update_option( 'peachpay_currency_options', $options );
		return;
	}
	if ( ! empty( $options['selected_currencies']['base'] ) ) {
		$options['selected_currencies']['base']['name']     = $name;
		$options['selected_currencies']['base']['decimals'] = $decimals;
		unset( $options['flag'] );
		update_option( 'peachpay_currency_options', $options );
	}
}

/**
 * Check if this currency is supported by any of our repitoire of payment methods.
 */
function peachpay_supports_currency() {
	$name = get_option( 'woocommerce_currency' );
	if ( ! array_key_exists( $name, PEACHPAY_SUPPORTED_CURRENCIES ) ) {
		add_action( 'admin_notices', 'peachpay_unsupported_currency_error' );

		return false;
	}
	return true;
}

/**
 * If we do not support any methods for checking out with a currency let the merchant know
 */
function peachpay_unsupported_currency_error() {
	?>
	<div class="notice notice-error">
		<p><?php echo esc_html_e( 'PeachPay does not support the current currency set by woocommerce and all orders will fail', 'peachpay-for-woocommerce' ); ?></p>
	</div>
	<?php
}
/**
 * On first currency switch with autodetect it will alert a customer that the currency has switched.
 */
function peachpay_render_currency_notice() {
	$align = peachpay_get_settings_option( 'peachpay_express_checkout_button', 'floating_button_alignment', 'right' ) === 'left' ? 'right: 20px' : 'left:20px';
	?>
	<div class='pp-currency-set-notice hide' style="<?php echo esc_html( $align ); ?>"> 
		<div class='pp-currency-notice-inner'>
		<?php
		esc_html_e( 'We detected the best currency for you would be ', 'peachpay-for-woocommerce' );
		echo isset( $_COOKIE['pp_active_currency'] ) ? esc_html( PEACHPAY_SUPPORTED_CURRENCIES[ sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) ) ] ) : esc_html( PEACHPAY_SUPPORTED_CURRENCIES[ peachpay_best_currency( peachpay_get_client_country() ) ] );
		esc_html_e( ' and automatically converted the prices!', 'peachpay-for-woocommerce' );
		?>
		</div>
	</div>
	<?php
}

/**
 * When currency defaults to billing country set in merchant config, address changes should
 * try to update currency when possible.
 *
 * @param string $order_meta Order meta data.
 */
function peachpay_update_billing_currency( $order_meta ) {
	$default_to = peachpay_get_settings_option( 'peachpay_currency_options', 'how_currency_defaults' );
	$args       = wp_parse_args( $order_meta );

	if ( $default_to && 'billing_country' === $default_to && array_key_exists( 'billing_country', $args ) && $args['billing_country'] ) {
		$new_billing_country = $args['billing_country'];
		$allowed_currencies  = peachpay_currencies_by_iso( $args['billing_country'] );
		$new_curr            = peachpay_best_currency( $new_billing_country );

		if ( isset( $_COOKIE['pp_active_currency'] ) && in_array( $_COOKIE['pp_active_currency'], $allowed_currencies, true ) ) {
			$new_curr = sanitize_text_field( wp_unslash( $_COOKIE['pp_active_currency'] ) );
		}

		setcookie( 'pp_active_currency', $new_curr, time() + 60 * 60 * 24, '/' );
		$_COOKIE['pp_active_currency'] = $new_curr;
	}
}

add_filter( 'woocommerce_cart_calculate_fees', 'peachpay_add_currency_fees', 10, 1 );

/**
 * Add currency fees to cart
 *
 * @param WC_Cart $cart cart to add fees to.
 */
function peachpay_add_currency_fees( $cart ) {
	$active_currencies = peachpay_get_active_currencies( peachpay_get_client_country() );
	$fee_data          = null;
	$currency_cookie   = get_woocommerce_currency();

	foreach ( $active_currencies as $currency => $data ) {
		if ( isset( $data['fees'] ) && $data['name'] === $currency_cookie ) {
			$fee_data = $data['fees'];
			break;
		}
	}

	if ( null !== $fee_data ) {
		$cart_total  = $cart->get_shipping_total() + $cart->get_subtotal();
		$region_fees = 0;

		foreach ( $fee_data as $fee ) {
			$amount = $fee['value'];
			if ( isset( $fee['is_percent'] ) ) {
				$amount = $cart_total * ( $amount / 100 );
			}

			if ( '' === $fee['reason'] ) {
				$region_fees += $amount;
			} else {
				$cart->add_fee( $fee['reason'], $amount );
			}
		}

		if ( $region_fees > 0 ) {
			$cart->add_fee( 'Region Fees', $region_fees );
		}
	}

	if ( peachpay_get_settings_option( 'peachpay_currency_options', 'add_conversion_fees' ) && get_option( 'woocommerce_currency' ) !== $currency_cookie ) {
		if ( ! WC() || ! isset( WC()->session ) ) {
			return;
		}

		$pm_name = WC()->session->get( 'chosen_payment_method' );

		if ( ! $pm_name ) {
			return;
		}

		$pm_name = explode( '_', $pm_name )[1] ?? '';

		$cart_total    = $cart->get_shipping_total() + $cart->get_subtotal();
		$base_currency = get_option( 'woocommerce_currency' );

		if ( 'stripe' === $pm_name ) {
			$cart->add_fee( __( 'Currency Conversion', 'peachpay-for-woocommerce' ), $cart_total * 0.01 );
		} elseif ( 'paypal' === $pm_name ) {
			if ( 'USD' === $base_currency || 'CAD' === $base_currency ) {
				$cart->add_fee( __( 'Currency Conversion', 'peachpay-for-woocommerce' ), $cart_total * 0.04 );
			} else {
				$cart->add_fee( __( 'Currency Conversion', 'peachpay-for-woocommerce' ), $cart_total * 0.045 );
			}
		}
	}

	return $cart;
}
