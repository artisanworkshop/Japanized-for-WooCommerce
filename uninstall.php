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

// Left behind by the usage tracking feature, removed in 2.9.17.
wp_clear_scheduled_hook( 'jp4wc_tracker_send_event' );
wp_clear_scheduled_hook( 'wc4jp_tracker_send_event' );
delete_option( 'wc4jp-tracking' );
delete_option( 'jp4wc_tracker_last_send' );

// The security check: its WP-Cron events, options, downloaded files and closed notices.
require_once __DIR__ . '/includes/admin/class-jp4wc-security-notice.php';
JP4WC_Security_Feed::purge();
delete_option( JP4WC_Security_Feed::SETTING );
delete_metadata( 'user', 0, JP4WC_Security_Notice::DISMISSED_META, '', true );

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
	delete_option( 'paidy_application_id' );

	// Delete onboarding state token options, signature-claim options, and
	// event-claim options (one non-autoloaded row per token/claim, keyed by a
	// random or hashed suffix) — the suffixes cannot be enumerated via a core
	// API, so a direct LIKE query is required per prefix. Must match
	// WC_Paidy_Apply_Receiver::STATE_OPTION_PREFIX / SIGNATURE_USED_PREFIX /
	// EVENT_CLAIM_PREFIX (the class is not loaded during uninstall, so the
	// constants cannot be referenced directly). Without this, rows accumulate
	// indefinitely and an immediate reinstall could reject an otherwise-fresh
	// callback because its old claim is still on record.
	$prefixes_to_purge = array(
		'paidy_onboarding_state_', // STATE_OPTION_PREFIX.
		'paidy_receiver_sig_',     // SIGNATURE_USED_PREFIX.
		'paidy_receiver_event_',   // EVENT_CLAIM_PREFIX.
	);
	foreach ( $prefixes_to_purge as $prefix ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

wc_paidy_delete_plugin();
