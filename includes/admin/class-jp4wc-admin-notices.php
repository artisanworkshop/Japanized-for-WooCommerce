<?php
/**
 * Admin Notices Class for WooCommerce for Japan.
 *
 * Handles the display of various admin notices specific to the Japanese market settings.
 *
 * @package woocommerce-for-japan
 * @category Admin
 * @author Shohei Tanaka
 * @since 1.0.0
 * @license GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that represents admin notices.
 *
 * @version 2.6.36
 * @since 2.3.4
 */
class JP4WC_Admin_Notices {
	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Constructor
	 *
	 * @since 2.3.4
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_jp4wc_security_checklist' ) );
		add_action( 'wp_ajax_jp4wc_pr_dismiss_prompt', array( $this, 'jp4wc_dismiss_review_prompt' ) );
	}

	/**
	 * Dismisses the review prompt notice
	 *
	 * Handles the ajax request to dismiss the review prompt notice
	 * by storing the dismiss status in user meta.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function jp4wc_dismiss_review_prompt() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'jp4wc_pr_dismiss_prompt' ) ) {// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			die( 'Failed' );
		}

		if ( ! empty( $_POST['type'] ) ) {
			if ( 'remove' === $_POST['type'] ) {
				update_option( 'jp4wc_2025031pr_hide_notice', date_i18n( 'Y-m-d H:i:s' ) );
				wp_send_json_success(
					array(
						'status' => 'removed',
					)
				);
			}
		}
	}

	/**
	 * Display security checklist notice for WooCommerce admins.
	 *
	 * @since 2.6.8
	 * @return void
	 */
	public function admin_jp4wc_security_checklist() {
		// Only show to WooCommerce admins.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! $this->has_orders_in_last_5_days() ) {
			return;
		}

		$security_check = get_option( 'jp4wc_security_settings' );

		if ( isset( $security_check['checkAdminLogin'] )
		&& isset( $security_check['checkSeucirytPluigns'] )
		&& $security_check['checkAdminLogin']
		&& $security_check['checkSeucirytPluigns'] ) {
			return;
		}

		// Notification display content.
		$this->jp4wc_security_checklist_display();
	}

	/**
	 * Display the security checklist notice.
	 *
	 * @since 2.6.8
	 */
	public function jp4wc_security_checklist_display() {
		$check_link = '/wp-admin/admin.php?page=wc-admin&path=%2Fjp4wc-security-check';
		?>
		<div class="notice notice-warning jp4wc-security-check" id="pr_jp4wc" style="background-color: #002F6C; color: #D1C1FF;">
		<div id="jp4wc-security-check">
			<p>
			<?php
				$catch_copy = __( 'Credit Card Security Guidelines Quick Check', 'woocommerce-for-japan' );
				echo '<h2 style="color:#fff;">' . esc_html( $catch_copy ) . '</h2>';
				esc_html_e( 'We recommend you do a quick check on the following page.', 'woocommerce-for-japan' );
				echo '<br />';
				esc_html_e( 'Once you have checked and saved the problem, this message will disappear.', 'woocommerce-for-japan' );
				echo '<br />';
				echo '<a href="' . esc_url( $check_link ) . '" style="color:#fff;">' . esc_html__( 'Check the security checklist', 'woocommerce-for-japan' ) . '</a>';
			?>
			</p>
		</div>
		</div>
		<?php
	}

	/**
	 * Check if there are any orders in the last 48 hours.
	 *
	 * @since 2.6.8
	 * @return bool True if orders exist, false otherwise.
	 */
	public function has_orders_in_last_5_days() {
		$args = array(
			'limit'        => 1,
			'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending', 'wc-refunded' ),
			'date_created' => '>' . ( time() - ( 5 * DAY_IN_SECONDS ) ),
		);

		$orders = wc_get_orders( $args );

		return ! empty( $orders );
	}
}

new JP4WC_Admin_Notices();
