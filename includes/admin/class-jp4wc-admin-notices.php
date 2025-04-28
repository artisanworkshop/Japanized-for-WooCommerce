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
 * @version 2.6.37
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
		add_action( 'jp4wc_save_methods_tracking', array( $this, 'jp4wc_save_methods_tracking' ) );
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

		$self_check_flag = false;
		$sacurity_flag   = false;
		// Check if the security check is enabled.
		if ( isset( $security_check['checkAdminLogin'] )
		&& isset( $security_check['checkSeucirytPluigns'] )
		&& $security_check['checkAdminLogin']
		&& $security_check['checkSeucirytPluigns'] ) {
			$self_check_flag = true;
		}
		// Check if the PHP version is safe.
		if ( $this->is_safe_php_version() ) {
			$sacurity_flag = true;
		}
		// Check if the WordPress version is up to date.
		if ( $this->is_later_wordpress_version() ) {
			$sacurity_flag = true;
		}
		// Check if the WooCommerce version is up to date.
		if ( $this->is_latest_woocommerce_version() ) {
			$sacurity_flag = true;
		}

		if ( $self_check_flag && $sacurity_flag ) {
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
			$message_flag = false;
			$catch_copy   = __( 'Credit Card Security Guidelines Quick Check', 'woocommerce-for-japan' );
			echo '<h2 style="color:#fff;">' . esc_html( $catch_copy ) . '</h2>';
			echo '<span style="color:#f00; font-weight:bold;">';
		if ( ! $this->is_safe_php_version() ) {
			esc_html_e( 'Please check the PHP version. The PHP currently used on this site is not supported for security reasons.', 'woocommerce-for-japan' );
			echo '<br />';
			$message_flag = true;
		}
		if ( ! $this->is_later_wordpress_version() ) {
			esc_html_e( 'Please check the WordPress version. The version of WordPress is outdated.', 'woocommerce-for-japan' );
			echo '<br />';
			$message_flag = true;
		}
		if ( ! $this->is_latest_woocommerce_version() ) {
			esc_html_e( 'Please check the WooCommerce version. The version of WooCommerce is outdated.', 'woocommerce-for-japan' );
			echo '<br />';
			$message_flag = true;
		}
			echo '</span>';
		if ( $message_flag ) {
			esc_html_e( 'This site is currently in violation of the security guidelines due to the above content. Immediate action is required.', 'woocommerce-for-japan' );
			echo '<br />';
			echo '<br />';
		}
		$messages = array(
			array(
				'text' => __( 'Get 2,200 yen/month and cashback now! Click here for your chance to switch to a secure server.', 'woocommerce-for-japan' ),
				'link' => '001',
			),
			array(
				'text' => __( '30-day money back guarantee! Click here for details on the security-enabled server migration campaign.', 'woocommerce-for-japan' ),
				'link' => '002',
			),
			array(
				'text' => __( 'Get a cashback on all your current server costs! Click here for a safe and economical migration.', 'woocommerce-for-japan' ),
				'link' => '003',
			),
			array(
				'text' => __( 'Fully secure! Migration + cashback for just 2,200 yen per month [Click here for details].', 'woocommerce-for-japan' ),
				'link' => '004',
			),
			array(
				'text' => __( 'Migration is also safe. 30-day money back guarantee! Click here for security-enabled servers.', 'woocommerce-for-japan' ),
				'link' => '005',
			),
			array(
				'text' => __( 'If you\'re worried about server migration, check out SoftStepsEC for Pressable!', 'woocommerce-for-japan' ),
				'link' => '006',
			),
			array(
				'text' => __( 'Now is your chance to switch! Check out the ¥2,200/month + cashback campaign.', 'woocommerce-for-japan' ),
				'link' => '007',
			),
			array(
				'text' => __( '[Hurry] Check out the details of this safe and affordable server migration campaign now!', 'woocommerce-for-japan' ),
				'link' => '008',
			),
		);
		// Get a random key from the array.
		$random_key = array_rand( $messages );
		esc_html_e( 'We recommend you do a quick check on the following page.', 'woocommerce-for-japan' );
		echo '<br />';
		esc_html_e( 'The above warning will no longer be displayed, and once you have checked the page problems, addressed them, checked and saved, this message will disappear.', 'woocommerce-for-japan' );
		echo '<br />';
		echo '<a href="' . esc_url( $check_link ) . '" style="color:#fff;">' . esc_html__( 'Check the security checklist', 'woocommerce-for-japan' ) . '</a>';
		echo '<br />';
		echo '<strong style="color:#fff;margin-top: 15px;display: inline-block;">';
		esc_html_e( '[Introducing servers that comply with credit card guidelines]', 'woocommerce-for-japan' );
		echo '</strong>';
		echo '<br />';
		echo '<a href="https://wc4jp-pro.work/product/softstepec-for-pressable/?utm_source=jp4wc&utm_medium=plugin&utm_campaign=' . esc_html( $messages[ $random_key ]['link'] ) . '" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; border: 2px solid #3498db; border-radius: 12px; text-decoration: none;color:#fff; font-weight:bold;margin: 15px 0 5px;">';
		echo esc_html( $messages[ $random_key ]['text'] );
		echo '</a>';
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

	/**
	 * Checks if the current PHP version is considered safe (8.1.0 or higher).
	 *
	 * @since 2.6.37
	 * @return bool True if PHP version is safe, false otherwise.
	 */
	public function is_safe_php_version() {
		$php_ver = phpversion();
		if ( version_compare( $php_ver, '8.1.0', '>=' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if the site is running a recent version of WordPress.
	 *
	 * Compares the current WordPress version with the latest available version
	 * to determine if the site needs an update.
	 *
	 * @since 2.6.37
	 * @return bool True if running a recent WordPress version, false if update needed.
	 */
	public function is_later_wordpress_version() {
		$api_response = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );

		if ( is_wp_error( $api_response ) ) {
			return true;
		}

		$api_data = json_decode( wp_remote_retrieve_body( $api_response ), true );

		if ( empty( $api_data ) || ! isset( $api_data['offers'] ) || empty( $api_data['offers'] ) ) {
			return true;
		}

		$latest_version  = $api_data['offers'][0]['version'];
		$current_version = get_bloginfo( 'version' );

		$latest_parts  = explode( '.', $latest_version );
		$current_parts = explode( '.', $current_version );

		if ( isset( $latest_parts[0] ) && isset( $current_parts[0] ) && $latest_parts[0] !== $current_parts[0] ) {
			return false;
		}

		if ( isset( $latest_parts[1] ) && isset( $current_parts[1] ) ) {
			$minor_diff = intval( $latest_parts[1] ) - intval( $current_parts[1] );

			if ( $minor_diff >= 1 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the site is running a recent version of WooCommerce.
	 *
	 * Compares the current WooCommerce version with the latest available version
	 * to determine if an update is needed. Considers a version outdated if it's
	 * at least 2 minor versions behind.
	 *
	 * @since 2.6.37
	 * @return bool True if running an acceptable WooCommerce version, false if update needed.
	 */
	public function is_latest_woocommerce_version() {
		// Get the currently installed WooCommerce version.
		if ( ! function_exists( 'WC' ) ) {
			// WooCommerce is not active.
			return true;
		}

		$current_version = WC()->version;

		// Get the latest WooCommerce version from the WordPress.org API.
		$response = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/woocommerce.json' );

		// Check if request was successful.
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			// If we can't determine the latest version, assume current version is OK.
			return true;
		}

		$plugin_info = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $plugin_info ) || ! isset( $plugin_info->version ) ) {
			// If we can't determine the latest version, assume current version is OK.
			return true;
		}

		$latest_version = $plugin_info->version;

		// Parse version numbers.
		$current_parts = explode( '.', $current_version );
		$latest_parts  = explode( '.', $latest_version );

		// Ensure we have at least major.minor.patch format.
		$current_parts_count = count( $current_parts );
		while ( $current_parts_count < 3 ) {
			$current_parts[] = '0';
			++$current_parts_count;
		}
		$latest_parts_count = count( $latest_parts );
		while ( $latest_parts_count < 3 ) {
			$latest_parts[] = '0';
			++$latest_parts_count;
		}

		// Compare major versions.
		if ( $current_parts[0] < $latest_parts[0] ) {
			// If major version is behind, check if minor version is at least 2 versions behind.
			return ( $latest_parts[1] - $current_parts[1] >= 2 ) ? false : true;
		}

		// If major versions are the same, check if minor version is at least 2 versions behind.
		if ( $current_parts[0] === $latest_parts[0] && ( $latest_parts[1] - $current_parts[1] >= 2 ) ) {
			return false;
		}

		// Otherwise, the version is current enough.
		return true;
	}

	/**
	 * Handles tracking method settings.
	 *
	 * Saves the tracking preferences and schedules or clears the tracking event
	 * based on user selection.
	 *
	 * @since 2.6.0
	 * @param string $post The tracking preference value ('0' to disable tracking).
	 * @return void
	 */
	public function jp4wc_save_methods_tracking( $post ) {
		if ( empty( $post ) ) {
			wp_clear_scheduled_hook( 'jp4wc_tracker_send_event' );
		} elseif ( ! wp_next_scheduled( 'jp4wc_tracker_send_event' ) ) {
				/**
				 * How frequent to schedule the tracker send event.
				 *
				 * @since 2.6.0
				 */
				wp_schedule_event( time() + 10, apply_filters( 'jp4wc_tracker_event_recurrence', 'weekly' ), 'jp4wc_tracker_send_event' );
		}
		$tracking = get_option( 'wc4jp-tracking' );
	}
}

new JP4WC_Admin_Notices();
