<?php
/**
 * PeachPay uninstall script.
 *
 * @package PeachPay
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$payment_options = get_option( 'peachpay_payment_options' );

// When data_retention is true, that means they checked the box to remove plugin
// data upon uninstall.
if ( is_array( $payment_options ) && array_key_exists( 'data_retention', $payment_options ) && $payment_options['data_retention'] ) {
	// Old, deprecated options which were all in one group.
	delete_option( 'peachpay_options' );

	// Payment
	delete_option( 'peachpay_payment_options' );

	// Currency
	delete_option( 'peachpay_currency_options' );


	// Field editor
	delete_option( 'peachpay_field_editor' );
	delete_option( 'peachpay_field_editor_additional' );
	delete_option( 'peachpay_field_editor_billing' );
	delete_option( 'peachpay_field_editor_shipping' );

	// Recommended products
	delete_option( 'peachpay_related_products_options' );

	// Express Checkout
	delete_option( 'peachpay_express_checkout_branding' );
	delete_option( 'peachpay_express_checkout_window' );
	delete_option( 'peachpay_express_checkout_product_recommendations' );
	delete_option( 'peachpay_express_checkout_button' );
	delete_option( 'peachpay_express_checkout_advanced' );

	// Onboarding tour
	delete_option( 'peachpay_onboarding_tour' );

	// Floating options.
	delete_option( 'peachpay_merchant_id' );
	delete_option( 'peachpay_payment_settings_initialized' );
	delete_option( 'peachpay_set_default_button_settings' );
	delete_option( 'peachpay_migrate_button_position' );
	delete_option( 'peachpay_migrated_float_button_icon' );
	delete_option( 'peachpay_connected_stripe_account' );
	delete_option( 'peachpay_connected_square_config' );
	delete_option( 'peachpay_connected_square_account' );
	delete_option( 'peachpay_square_apple_pay_config_live' );
	delete_option( 'peachpay_square_apple_pay_config_test' );
	delete_option( 'peachpay_api_access_denied' );
	delete_option( 'peachpay_valid_key' );
	delete_option( 'peachpay_deny_add_to_cart_redirect' );
	delete_option( 'peachpay_migrated_to_enable_stripe_checkbox' );
	delete_option( 'peachpay_apple_pay_settings' );
	delete_option( 'peachpay_apple_pay_settings_v2' );
	delete_option( 'peachpay_migrated_settings_after_reorg' );

	// Analytics.
	drop_tables();
}

/**
 * Drops all PeachPay tables from the local database.
 */
function drop_tables() {
	global $wpdb;

	if ( ! $wpdb ) {
		return;
	}

	// Tables need to be dropped in reverse order to satisfy foreign key constraints.
	$tables_reverse = array(
		'peachpay_analytics_interval',
		'peachpay_analytics_meta',
		'peachpay_customer_cart_contents',
		'peachpay_customer_cart',
		'peachpay_customer_cart_meta',
	);

	foreach ( $tables_reverse as $table ) {
		$table_name = $wpdb->prefix . $table;
		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$response = $wpdb->query( "DROP TABLE IF EXISTS $table_name;" );
			if ( ! $response ) {
				return 'There was an error dropping tables. Check your foreign constraints and table add order? Failed on table: ' . $table_name;
			}
		} catch ( Exception $e ) {
			// Attempt to log if possible
			if ( defined( 'PEACHPAY_ABSPATH' ) ) {
				include_once PEACHPAY_ABSPATH . 'core/error-reporting.php';
				peachpay_notify_error( $e );
			}
		}
	}

	return $response;
}
