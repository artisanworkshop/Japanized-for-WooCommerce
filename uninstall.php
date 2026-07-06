<?php
/**
 * Japanized for WooCommerce Uninstall
 *
 * @package woocommerce-for-japan
 * @category Core
 * @author Artisan Workshop
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// delete option settings.
delete_option( 'wc4jp-bankjp' );
delete_option( 'woocommerce_bankjp_settings' );
delete_option( 'wc4jp-postofficebank' );
delete_option( 'woocommerce_postofficebankjp_settings' );
delete_option( 'wc4jp-atstore' );
delete_option( 'woocommerce_atstorejp_settings' );
delete_option( 'wc4jp-cod2' );
delete_option( 'woocommerce_cod2_settings' );
delete_option( 'wc4jp-company-name' );
delete_option( 'wc4jp-yomigana' );
delete_option( 'woocommerce_cod_extra_charge_name' );
delete_option( 'woocommerce_cod_extra_charge_amount' );
delete_option( 'woocommerce_cod_extra_charge_max_cart_value' );
delete_option( 'woocommerce_cod_extra_charge_calc_taxes' );
delete_option( 'woocommerce_cod_settings' );

/**
 * Deletes the Paidy plugin options from the database.
 */
function wc_paidy_delete_plugin() {
	global $wpdb;

	// delete option settings.
	$options = array_merge(
		wp_load_alloptions(),
		wp_cache_get( 'alloptions', 'options' )
	);
	foreach ( $options as $option_name => $option_value ) {
		if ( strpos( $option_name, 'woocommerce_paidy_' ) === 0 || strpos( $option_name, 'wc-paidy-' ) === 0 ) {
			delete_option( $option_name );
		}
	}
	delete_option( 'wc_paidy_show_pr_notice' );

	// Delete onboarding state token options (one non-autoloaded row per token,
	// named paidy_onboarding_state_<token>) — the random suffixes cannot be
	// enumerated via a core API, so a direct LIKE query is required.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( 'paidy_onboarding_state_' ) . '%'
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

wc_paidy_delete_plugin();
