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
 * @version 2.6.10
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
		add_action( 'admin_notices', array( $this, 'admin_jp4wc_notices' ) );
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
				update_option( 'jp4wc_202502pr_hide_notice', date_i18n( 'Y-m-d H:i:s' ) );
				wp_send_json_success(
					array(
						'status' => 'removed',
					)
				);
			}
		}
	}

	/**
	 * Display any notices we've collected thus far.
	 *
	 * @since 2.3.4
	 * @version 2.6.8
	 */
	public function admin_jp4wc_notices() {
		// Only show to WooCommerce admins.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Notice has been removed.
		if ( get_option( 'jp4wc_202502pr_hide_notice' ) ) {
			return;
		}

		// Delete notice when deadline expires.
		$today   = strtotime( date_i18n( 'Y-m-d' ) );
		$end_day = strtotime( '2025-04-01' );
		if ( $today > $end_day ) {
			return;
		}

		// Notification display content.
		$this->jp4wc_pr2025_display();
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 *
	 * @since 2.3.4
	 * @version 2.6.8
	 */
	public function jp4wc_pr2025_display() {
		$pr_link = 'https://wc4jp-pro.work/about-security-service/2025lp/';
		$today   = new DateTime();
		$end_day = new DateTime( '2025-04-01' );
		$diff    = $today->diff( $end_day );
		$days    = $diff->format( '%a' );
		?>
		<div class="notice notice-error is-dismissible jp4wc-pr-notice" id="pr_jp4wc" style="background-color: #D1C1FF; color: #2C045D;">
			<div id="pr_jp4wc_2025">
				<p>	
				<?php
				$second     = date_i18n( 's' );
				$second_int = (int) $second;
				if ( 0 === $second_int % 2 ) {
					/* translators: %s: number of days until April 2025 */
					$catch_copy = sprintf( esc_html__( 'Only %s days left. New credit card payment standards are coming into force in April 2025!', 'woocommerce-for-japan' ), $days );
					echo '<h2 style="color: #2C045D;">' . esc_html( $catch_copy ) . '</h2>';
					esc_html_e( 'Coming into force in April 2025! Is your website ready for the new credit card payment standards?', 'woocommerce-for-japan' );
					echo '<br />';
					esc_html_e( 'We recommend that you take the following measures to ensure that your store is ready for the new credit card payment standards.', 'woocommerce-for-japan' );
					echo '<br />';
					/* translators: 1: Opening anchor tag with URL, 2: Closing anchor tag */
					$product_link = sprintf( __( '%1$s [Security measures for WooCommerce] in line with the "Credit Card Security Guidelines" %2$s', 'woocommerce-for-japan' ), '<a href="' . esc_url( $pr_link ) . '?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=woo_security" target="_blank">', '</a>' );
					echo wp_kses(
						$product_link,
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
				} else {
					$catch_copy = __( '🔒 Are you ready for the new standard in credit card payments?', 'woocommerce-for-japan' );
					echo '<h2 style="color: #2C045D;">' . esc_html( $catch_copy ) . '</h2>';
					esc_html_e( 'Are you prepared for the new security guidelines that will come into effect in April 2025?', 'woocommerce-for-japan' );
					echo '<br />';
					echo '<b>' . esc_html( 'Don\'t worry if you\'re not ready yet!', 'woocommerce-for-japan' ) . '</b> ' . esc_html( 'Let our experts help you establish a safe payment environment.', 'woocommerce-for-japan' );
					echo '<br />';
					/* translators: 1: Opening anchor tag with URL, 2: Closing anchor tag */
					$product_link = sprintf( __( '%1$s 📢 Check it out now and be fully prepared! %2$s', 'woocommerce-for-japan' ), '<a href="' . esc_url( $pr_link ) . '?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=woo_security_safe" target="_blank">', '</a>' );
					echo wp_kses(
						$product_link,
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
				}
				?>
				</p>
			</div>
		</div>
		<script>
			jQuery(document).ready(function($) {
				$('body').on('click', '#pr_jp4wc .notice-dismiss', function(event) {
					event.preventDefault();
					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'jp4wc_pr_dismiss_prompt',
							nonce: "<?php echo esc_js( wp_create_nonce( 'jp4wc_pr_dismiss_prompt' ) ); ?>",
							type: 'remove'
						},
					})
				});
			});
		</script>
		<?php
	}
}

new JP4WC_Admin_Notices();
