<?php
/**
 * These functions send PHP errors to us so that we can debug issues
 * with PeachPay without having to ask for log files.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

register_shutdown_function( 'peachpay_notify_error' );

/**
 * Maps integer error code to an error code constant
 *
 *  @param int $err_code PHP Error Code.
 */
function peachpay_error_mapping( $err_code ) {
	switch ( $err_code ) {
		case 1:
			return 'E_ERROR';
		default:
			return 'UNK';
	}
}

/**
 * In case of any fatal runtime errors, this is the function that will be triggered at end capturing last error and sending it to lamda function.
 *
 * @param Exception $error If there is more info that needs to be passed in from a possible caught error, this can be
 *  done with this error variable.
 *
 * Some errors (i.e. from the analytics API) are a different type of error (an Exception directly from PHP). Line 41 is a
 *  conversion to match what this method expects.
 */
function peachpay_notify_error( $error = null ) {
	try {
		// To get list of php error constants; visit https://www.php.net/manual/en/errorfunc.constants.php.
		if ( $error ) {
			$error = array(
				'type'    => get_class( $error ),
				'message' => $error->getMessage(),
				'file'    => $error->getFile(),
				'line'    => $error->getLine(),
			);
		} else {
			$error = error_get_last();
		}

		if ( null !== $error && 1 === $error['type'] ) {
			$body = array(
				'host'                     => site_url(),
				'error_type'               => peachpay_error_mapping( $error['type'] ),
				'error_message'            => $error['message'],
				'error_file'               => $error['file'],
				'error_line'               => $error['line'],
				'error_uri'                => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '$_SERVER[\'REQUEST_URI\'] not set',
				'pp_version'               => PEACHPAY_VERSION,
				'test_mode'                => peachpay_get_settings_option( 'peachpay_payment_options', 'test_mode', false ),
				'php_version'              => phpversion(),
				'wp_version'               => get_bloginfo( 'version' ),
				'plugins_info'             => peachpay_collect_plugin_info(),
				'woocommerce_class_exists' => class_exists( 'WooCommerce' ),
			);

			$args = array(
				'body'        => wp_json_encode( $body ),
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'httpversion' => '2.0',
				'blocking'    => false,
			);

			wp_remote_post( 'https://619qwedgz3.execute-api.us-east-1.amazonaws.com/v1/post-error', $args );
		}
	// phpcs:ignore
	} catch ( Exception $ex ) {
		// Cause no further harm :D.
	}
}

/**
 * Collects nonsensitive plugin information for a store.
 */
function peachpay_collect_plugin_info() {
	$data = array(
		'plugins' => array(),
	);

	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();

		foreach ( $plugins as $plugin_key => $plugin_data ) {
			$data['plugins'][ $plugin_key ] = array(
				'name'      => $plugin_data['Name'],
				'version'   => $plugin_data['Version'],
				'pluginURI' => $plugin_data['PluginURI'],
				'active'    => is_plugin_active( $plugin_key ),
			);
		}

    //phpcs:ignore
	} catch ( Exception $ex ) {
		// Do no harm.
	}

	return $data;
}
