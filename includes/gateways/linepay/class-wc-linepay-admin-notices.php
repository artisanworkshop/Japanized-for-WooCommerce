<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that represents admin notices.
 *
 * @since 1.0.0
 */
class WC_LINEPay_Admin_Notices {
	/**
	 * Notices (array)
	 * @var array
	 */
	public $notices = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
     * @version 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
     * @param string $slug
     * @param string $class
     * @param string $message
     * @param boolean $dismissible
	 * @since 1.0.0
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
	 * @since 1.0.0
     * @version 1.0.0
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Main Paidy payment method check.
		$this->linepay_check_environment();

		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

			if ( $notice['dismissible'] ) {
				?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-linepay-hide-notice', $notice_key ), 'wc_linepay_hide_notices_nonce', '_wc_linepay_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>
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
	 * @since 1.0.0
     * @version 1.1.0
	 */
	public function linepay_check_environment() {
		$show_ssl_notice    = get_option( 'wc_linepay_show_ssl_notice' );
		$show_curl_notice   = get_option( 'wc_linepay_show_curl_notice' );
		$options            = get_option( 'woocommerce_linepay_settings' );
        $show_pr_notice     = get_option( 'wc_linepay_show_pr_notice' );

		if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
            if ( empty( $show_ssl_notice ) ) {
                // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected.
                if ( ! wc_checkout_is_https() ) {
                    /* translators: 1) Wikipedia link */
                    $this->add_admin_notice( 'ssl', 'notice notice-warning', sprintf( __( 'LINE Pay is enabled, but a SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid <a href="%1$s" target="_blank">SSL certificate</a>.', 'woocommerce-for-japan' ), 'https://ja.wikipedia.org/wiki/Transport_Layer_Security' ), true );
                }
            }
			if ( empty( $show_curl_notice ) ) {
				if ( ! function_exists( 'curl_init' ) ) {
					$this->add_admin_notice( 'curl', 'notice notice-warning', __( 'LINE Pay for WooCommerce - cURL is not installed.', 'woocommerce-for-japan' ), true );
				}
			}
        }elseif( empty( $show_pr_notice ) ){
            $linepay_link = 'https://wc.artws.info/about-line-pay/';
            /* translators: 1) LINE Pay PR link */
            $this->add_admin_notice( 'linepay_pr', 'notice notice-info', sprintf( __( 'LINE Pay can reach many LINE users from the payment method without fixed monthly fee. <a href="%s">Click here for details.</a>.', 'woocommerce-for-japan' ), $linepay_link ), true );
        }
	}

	/**
	 * Hides any admin notices.
	 *
	 * @since 1.0.0
	 * @version 1.1.0
	 */
	public function hide_notices() {
		if ( isset( $_GET['wc-linepay-hide-notice'] ) && isset( $_GET['_wc_linepay_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_wc_linepay_notice_nonce'], 'wc_linepay_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-for-japan' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-for-japan' ) );
			}

			$notice = wc_clean( $_GET['wc-linepay-hide-notice'] );

			switch ( $notice ) {
				case 'curl':
					update_option( 'wc_linepay_show_curl_notice', 'no' );
					break;
				case 'ssl':
					update_option( 'wc_linepay_show_ssl_notice', 'no' );
					break;
                case 'linepay_pr':
                    update_option( 'wc_linepay_show_pr_notice', 'no' );
                    break;
			}
		}
	}

	/**
	 * Get setting link.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting link
	 */
	public function get_setting_link() {
		$section_slug = 'linepay';

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}
}

new WC_LINEPay_Admin_Notices();
