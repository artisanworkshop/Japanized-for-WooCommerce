<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

	public function jp4wc_dismiss_review_prompt() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'jp4wc_pr_dismiss_prompt' ) ) {
			die('Failed');
		}

		if ( ! empty( $_POST['type'] ) ) {
			if ( 'remove' === $_POST['type'] ) {
				update_option( 'jp4wc_pr_hide_notice', date_i18n( 'Y-m-d H:i:s' ) );
				wp_send_json_success( 
					array(
						'status' => 'removed'
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
		// Only show to WooCommerce admins
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Notice has been removed
		if ( get_option( 'jp4wc_pr_hide_notice' ) ) {
			return;
		}

		// Notice removed by page
		$allow_pages = array( 'wc-admin', 'wc-orders', 'wc-settings', 'wc-status', 'wc-reports', 'wc4jp-options' );
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allow_pages) ) {
			// Notification display content
			$this->jp4wc_pr_display();
		}

		// Delete notice when deadline expires
/*		$today = new DateTime('now');
		$end_day = new DateTime('2021-11-19');
		$diff = $end_day->diff($today);
		$diff_days = $diff->days;
		if ( $diff_days <= 0 ) {
			return;
		}*/

    }

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 *
	 * @since 2.3.4
     * @version 2.6.8
	 */
	public function jp4wc_pr_display() {
		$pr_link = 'https://wc4jp-pro.work/product/site-security-for-woo/';
		/* translators: 1) Japanized for WooCommerce PR link */
		?>
        <div class="notice notice-info is-dismissible jp4wc-pr-notice" id="pr_jp4wc">
            <div id="pr_jp4wc_">
				<p><?php 
				/* translators: 1) Japanized for WooCommerce PR link */
				echo sprintf( __('<a href="%s?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=woo_security" target="_blank">Run WooCommerce safely with regular updates, security monitoring, and a quick alert system.</a>', 'woocommerce-for-japan' ), $pr_link );
				?><br />
					<?php _e( 'If you have not taken measures yet, please take measures for WooCommerce dedicated site monitoring starting from 2,200 yen per month.', 'woocommerce-for-japan' );?>
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
                            nonce: "<?php echo wp_create_nonce( 'jp4wc_pr_dismiss_prompt' ) ?>",
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
