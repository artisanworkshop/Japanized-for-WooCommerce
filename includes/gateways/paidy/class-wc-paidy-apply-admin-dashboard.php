<?php
/**
 * Paidy WooCommerce Admin Dashboard Class
 *
 * This file contains the WC_Paidy_Apply_Admin_Dashboard class
 * which handles the Paidy application status dashboard widget.
 *
 * @package Paidy_WC
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Paidy_Apply_Admin_Dashboard class file.
 *
 * @package Paidy_WC
 */
class WC_Paidy_Apply_Admin_Dashboard {

	/**
	 * Current step of the Paidy application process.
	 *
	 * @var int
	 */
	public $current_step;

	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Constructor.
	 *
	 * Sets up the admin dashboard for Paidy application status.
	 */
	public function __construct() {
		$woocommerce_paidy_on_boarding_settings = get_option( 'woocommerce_paidy_on_boarding_settings', array() );
		$this->current_step                     = isset( $woocommerce_paidy_on_boarding_settings['currentStep'] ) ? $woocommerce_paidy_on_boarding_settings['currentStep'] : 0;

		add_action( 'admin_notices', array( $this, 'paidy_apply_notice' ), 0 );
		add_action( 'network_admin_notices', array( $this, 'paidy_apply_notice' ), 0 );
		add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
	}

	/**
	 * Check if the Paidy Apply feature is enabled.
	 *
	 * @return bool
	 */
	public function wc_apply_paidy() {
		return apply_filters( 'wc_paidy_apply_enabled', true );
	}

	/**
	 * Paidy_apply_notice the dashboard widget content.
	 */
	public function paidy_apply_notice() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'dashboard' !== $screen->id ) {
			return;
		}

		if ( 2 === $this->current_step || 3 === $this->current_step || 99 === $this->current_step ) {
			$notice_key         = 'paidy_apply_notice_' . $this->current_step;
			$notice_dismissible = get_option( 'wc_' . $notice_key ) ? get_option( 'wc_' . $notice_key ) : 'yes';
			if ( 'no' !== $notice_dismissible ) {
				echo '<div class="notice notice-info">';
				echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'wc-paidy-apply-hide-notice', $notice_key ), 'wc_paidy_apply_hide_notices_nonce', '_wc_paidy_apply_notice_nonce' ) ) . '" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>';
				echo '<p>';
				// 2: Apply Completed ,3: Approved, 99: Error.
				$this->paidy_apply_approved();
				echo '</p></div>';
			}
		}
	}

	/**
	 * Display approved application content.
	 *
	 * Renders the HTML for the approved Paidy application status.
	 */
	public function paidy_apply_approved() {
		echo '<div id="paidy-admin-settings"></div>';
	}

	/**
	 * Hides any admin notices.
	 *
	 * @since 1.0.0
	 * @version 1.1.0
	 */
	public function hide_notices() {
		if ( isset( $_GET['wc-paidy-apply-hide-notice'] ) && isset( $_GET['_wc_paidy_apply_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wc_paidy_apply_notice_nonce'] ) ), 'wc_paidy_apply_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce-for-japan' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Cheatin&#8217; huh?', 'woocommerce-for-japan' ) );
			}

			$notice = isset( $_GET['wc-paidy-apply-hide-notice'] ) ? sanitize_text_field( wp_unslash( $_GET['wc-paidy-apply-hide-notice'] ) ) : '';

			switch ( $notice ) {
				case 'paidy_apply_notice_2':
					update_option( 'wc_paidy_apply_notice_2', 'no' );
					break;
				case 'paidy_apply_notice_3':
					update_option( 'wc_paidy_apply_notice_3', 'no' );
					break;
				case 'paidy_apply_notice_99':
					update_option( 'wc_paidy_apply_notice_99', 'no' );
					break;
			}
		}
	}
}
