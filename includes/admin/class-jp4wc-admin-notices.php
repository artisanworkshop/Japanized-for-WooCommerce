<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that represents admin notices.
 *
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
		add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
     * @param string $slug
     * @param string $class
     * @param string $message
     * @param boolean $dismissible
	 * @since 2.3.4
     * @version 1.0.0
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->notices[ $slug ] = array(
			'class'       => $class,
			'message'     => $message,
			'dismissible' => $dismissible,
		);
	}

	/**
	 * Display any notices we've collected thus far.
	 *
	 * @since 2.3.4
     * @version 1.0.0
	 */
	public function admin_jp4wc_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Main Paidy payment method check.
		$this->jp4wc_pr_check();

		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

			if ( $notice['dismissible'] ) {
				?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'jp4wc-hide-notice', $notice_key ), 'jp4wc_hide_notices_nonce', '_jp4wc_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0px 9px 9px;text-decoration:none;"></a>
				<?php
			}

			echo '<p>';
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array(), 'target' => array() ) ) );
			echo '</p></div>';
		}
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 *
	 * @since 2.3.4
     * @version 1.1.0
	 */
	public function jp4wc_pr_check() {
        $show_pr_notice     = get_option( 'jp4wc_show_pr_notice' );
		$today = new DateTime('now');
		$end_day = new DateTime('2021-11-19');
		$diff = $end_day->diff($today);
		$diff_days = $diff->days;
        if( empty( $show_pr_notice ) & $diff_days >= 0 ){
            $pr_link = 'https://wooecfes.jp/';
            /* translators: 1) Japanixed for WooCommerce PR link */
            ?>
            <div class="notice notice-info is-dismissible prli-review-notice" id="prli_review_notice">
                <div id="prli_review_intro">
                    <p><?php echo sprintf( __('WooCommerce\'s online conference <b><a href="%s?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=wooecfses2021" target="_blank">[Woo EC Fes Japan 2021]</b></a> will be held in Japan from November 19th to 20th, 2021.', 'woocommerce-for-japan' ), $pr_link );?><br />
	                    <?php _e('You can get knowledge about online shops and learn the functions of WooCommerce from some Contributors.', 'woocommerce-for-japan' );?><br />
	                    <strong style="color:orangered;font-size: large;"><?php echo sprintf( __('%s days until the event!', 'woocommerce-for-japan' ), $diff_days );?></strong>
                        </p>
                    <p> <?php echo sprintf( __( 'Please join us. <a href="%stickets/?utm_source=jp4wc_plugin&utm_medium=site&utm_campaign=wooecfses2021" target="_blank">Click here to apply.</a>', 'woocommerce-for-japan' ), $pr_link ); ?>🙂</p>
                </div>
            </div>
<?php
        }
	}

	/**
	 * Hides any admin notices.
	 *
	 * @since 2.3.4
	 * @version 1.1.0
	 */
	public function hide_notices() {
		if ( isset( $_GET['jp4wc-hide-notice'] ) && isset( $_GET['_jp4wc_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_jp4wc_notice_nonce'], 'jp4wc_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-for-japan' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-for-japan' ) );
			}

			$notice = wc_clean( $_GET['jp4wc-hide-notice'] );

			switch ( $notice ) {
                case 'jp4wc_pr':
                    update_option( 'jp4wc_show_pr_notice', 'no' );
                    break;
			}
		}
	}

	/**
	 * Get setting link.
	 *
	 * @since 2.3.4
	 *
	 * @return string Setting link
	 */
	public function get_setting_link() {
		$section_slug = 'linepay';

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}
}

new JP4WC_Admin_Notices();
