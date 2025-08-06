<?php
/**
 * Paidy Payment Gateway Settings Controller
 *
 * This file contains the settings controller class for the Paidy payment gateway.
 * It handles admin settings scripts and functionality.
 *
 * @package WooCommerce
 * @subpackage Paidy_Payment_Gateway
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Paidy_Settings_Controller
 *
 * Handles the admin settings scripts and functionality for the Paidy payment gateway.
 *
 * @since 1.0.0
 */
class WC_Paidy_Settings_Controller {

	/**
	 * Constructor for WC_Paidy_Settings_Controller class.
	 *
	 * Hooks into WordPress admin scripts action to load admin settings scripts.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'paidy_admin_settings_scripts' ) );
	}

	/**
	 * Enqueues admin wizard scripts for the Paidy payment gateway settings page.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function paidy_admin_settings_scripts( $hook_suffix ) {
		$screen = get_current_screen();
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix && ( is_object( $screen ) && 'dashboard' !== $screen->id ) ) {
			return;
		}

		$display_flag = false;
		if ( is_admin() && isset( $_GET['section'] ) && 'paidy' === $_GET['section'] ) {// phpcs:ignore
			$display_flag = true;
		}
		if ( is_object( $screen ) && 'dashboard' === $screen->id ) {
			$display_flag = true;
		}
		if ( $display_flag ) {
			$handle = 'paidy-admin-setting-script';

			$script_path       = 'admin/paidy.js';
			$script_asset_path = WC_PAIDY_ASSETS_ABSPATH . 'admin/paidy.asset.php';
			$script_asset      = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(),
					'version'      => '1.2.1',
				);

			$script_url = WC_PAIDY_BLOCKS_URL . $script_path;
			wp_register_script(
				$handle,
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true // Load in footer.
			);

			// Enqueue CSS.
			wp_enqueue_style(
				$handle,
				WC_PAIDY_BLOCKS_URL . 'admin/paidy.css',
				array_filter(
					$script_asset['dependencies'],
					function ( $style ) {
						return wp_style_is( $style, 'registered' );
					}
				),
				$script_asset['version'],
			);

			wp_enqueue_script( $handle );
			add_action(
				'wp_print_scripts',
				function () use ( $handle ) {
					$result = wp_set_script_translations(
						$handle,
						'woocommerce-for-japan', // Load translations from the plugin's i18n directory.
						WC_PAIDY_ABSPATH . 'i18n'
					);
				},
				999
			);

			// Setting data.
			$rest_url = get_rest_url();
			wp_localize_script(
				$handle,
				'paidyForWcSettings',
				array(
					'restUrl' => $rest_url,
				)
			);
		}
	}
}
