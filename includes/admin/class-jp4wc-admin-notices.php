<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that represents admin notices.
 *
 * @version 2.3.7
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
//		add_action( 'admin_notices', array( $this, 'admin_jp4wc_notices' ) );
		add_action( 'wp_ajax_jp4wc_pr_dismiss_prompt', array( $this, 'jp4wc_dismiss_review_prompt' ) );
	}

	public function jp4wc_dismiss_review_prompt() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'jp4wc_pr_dismiss_prompt' ) ) {
			die('Failed');
		}

		if ( ! empty( $_POST['type'] ) ) {
			if ( 'remove' === $_POST['type'] ) {
				update_option( 'jp4wc_pr_hide_notice', true );
				wp_send_json_success( array(
					'status' => 'removed'
				) );
			}
		}
	}

	/**
	 * Display any notices we've collected thus far.
	 *
	 * @since 2.3.4
     * @version 2.3.6
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

		// Delete notice when deadline expires
		$today = new DateTime('now');
		$end_day = new DateTime('2021-11-19');
		$diff = $end_day->diff($today);
		$diff_days = $diff->days;
		if ( $diff_days <= 0 ) {
			return;
		}

		// Notification display content
		$this->jp4wc_pr_display( $diff_days );
    }

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 *
	 * @since 2.3.4
     * @version 2.3.6
	 */
	public function jp4wc_pr_display( $diff_days ) {
		$pr_link = 'https://wooecfes.jp/';
		/* translators: 1) Japanixed for WooCommerce PR link */
		?>
        <div class="notice notice-info is-dismissible jp4wc-pr-notice" id="pr_jp4wc">
            <div id="pr_jp4wc_wooecfes2021">
                <p><?php echo sprintf( __('WooCommerce\'s online conference <b><a href="%s?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=wooecfses2021" target="_blank">[Woo EC Fes Japan 2021]</b></a> will be held in Japan from November 19th to 20th, 2021.', 'woocommerce-for-japan' ), $pr_link );?><br />
					<?php _e('You can get knowledge about online shops and learn the functions of WooCommerce from some Contributors.', 'woocommerce-for-japan' );?><br />
                    <strong style="color:orangered;font-size: large;"><?php echo sprintf( __('%s days until the event!', 'woocommerce-for-japan' ), $diff_days );?></strong>
                </p>
                <p> <?php echo sprintf( __( 'Please join us. <a href="%stickets/?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=wooecfses2021" target="_blank">Click here to apply.</a>', 'woocommerce-for-japan' ), $pr_link ); ?>🙂</p>
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
