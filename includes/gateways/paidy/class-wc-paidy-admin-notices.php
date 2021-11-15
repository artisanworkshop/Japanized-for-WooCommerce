<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that represents admin notices.
 *
 * @since 1.1.0
 */
class WC_Paidy_Admin_Notices {
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
		$this->paidy_check_environment();

		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

			if ( $notice['dismissible'] ) {
				?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-paidy-hide-notice', $notice_key ), 'wc_paidy_hide_notices_nonce', '_wc_paidy_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>
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
	public function paidy_check_environment() {
		$show_ssl_notice    = get_option( 'wc_paidy_show_ssl_notice' );
		$show_curl_notice   = get_option( 'wc_paidy_show_curl_notice' );
		$options            = get_option( 'woocommerce_paidy_settings' );
		$test_mode          = ( isset( $options['environment'] ) && 'sandbox' === $options['environment'] ) ? true : false;
		$test_pub_key       = isset( $options['test_api_public_key'] ) ? $options['test_api_public_key'] : '';
		$test_secret_key    = isset( $options['test_api_secret_key'] ) ? $options['test_api_secret_key'] : '';
		$api_public_key     = isset( $options['api_public_key'] ) ? $options['api_public_key'] : '';
		$api_secret_key     = isset( $options['api_secret_key'] ) ? $options['api_secret_key'] : '';
        $show_pr_notice     = get_option( 'wc_paidy_show_pr_notice' );

		if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
            if ( empty( $show_ssl_notice ) ) {
                // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected.
                if ( ! wc_checkout_is_https() ) {
                    /* translators: 1) Wikipedia link */
                    $this->add_admin_notice( 'ssl', 'notice notice-warning', sprintf( __( 'Paidy is enabled, but a SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid <a href="%1$s" target="_blank">SSL certificate</a>.', 'woocommerce-for-japan' ), 'https://ja.wikipedia.org/wiki/Transport_Layer_Security' ), true );
                }
            }
			if ( empty( $show_curl_notice ) ) {
				if ( ! function_exists( 'curl_init' ) ) {
					$this->add_admin_notice( 'curl', 'notice notice-warning', __( 'Paidy for WooCommerce - cURL is not installed.', 'woocommerce-for-japan' ), true );
				}
			}
            if ( $test_mode ) {
                if (
                    ! empty( $test_pub_key ) && ! preg_match( '/^pk_test_/', $test_pub_key )
                    || ( ! empty( $test_secret_key ) && ! preg_match( '/^sk_test_/', $test_secret_key ) ) ) {
                    $setting_link = $this->get_setting_link();
                    /* translators: 1) setting link */
                    $this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'Paidy is in test mode however your test keys may not be valid. Test keys start with pk_test and sk_test. Please go to your settings and, <a href="%s">set your Paidy account keys</a>.', 'woocommerce-for-japan' ), $setting_link ), true );
                }
            } else {
                if (
                    ! empty( $api_public_key ) && ! preg_match( '/^pk_live_/', $api_public_key )
                    || ( ! empty( $api_secret_key ) && ! preg_match( '/^sk_live_/', $api_secret_key ) ) ) {
                    $setting_link = $this->get_setting_link();
                    /* translators: 1) setting link */
                    $this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'Paidy is in live mode however your test keys may not be valid. Live keys start with pk_live and sk_live. Please go to your settings and, <a href="%s">set your Paidy account keys</a>.', 'woocommerce-for-japan' ), $setting_link ), true );
                }
            }
        }elseif( empty( $show_pr_notice ) && get_option('wc-paygent-paidy') == false){
            $paidy_link = 'https://campaign.paidy.com/woocommerce';
            /* translators: 1) Paidy PR link */
            $this->add_admin_notice( 'paidy_pr', 'notice notice-info', sprintf( __( 'Paidy payment is a monthly postpaid payment that is adopted by Amazon and Yodobashi Camera. <a href="%s">Click here for details.</a>.', 'woocommerce-for-japan' ), $paidy_link ), true );
        }
	}

	/**
	 * Hides any admin notices.
	 *
	 * @since 1.0.0
	 * @version 1.1.0
	 */
	public function hide_notices() {
		if ( isset( $_GET['wc-paidy-hide-notice'] ) && isset( $_GET['_wc_paidy_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_wc_paidy_notice_nonce'], 'wc_paidy_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-for-japan' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-for-japan' ) );
			}

			$notice = wc_clean( $_GET['wc-paidy-hide-notice'] );

			switch ( $notice ) {
				case 'curl':
					update_option( 'wc_paidy_show_curl_notice', 'no' );
					break;
				case 'ssl':
					update_option( 'wc_paidy_show_ssl_notice', 'no' );
					break;
                case 'paidy_pr':
                    update_option( 'wc_paidy_show_pr_notice', 'no' );
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
		$section_slug = 'paidy';

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}
}

new WC_Paidy_Admin_Notices();
