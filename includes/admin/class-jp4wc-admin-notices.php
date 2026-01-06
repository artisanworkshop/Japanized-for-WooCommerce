<?php
/**
 * Admin Notices Class for WooCommerce for Japan.
 *
 * Handles the display of various admin notices specific to the Japanese market settings.
 *
 * @package woocommerce-for-japan
 * @category Admin
 * @author Shohei Tanaka
 * @since 2.3.4
 * @license GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that represents admin notices.
 *
 * @version 2.8.0
 * @since 2.3.4
 */
class JP4WC_Admin_Notices {
	/**
	 * The single instance of the class
	 *
	 * @var JP4WC_Admin_Notices
	 */
	protected static $instance = null;

	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Get the singleton instance
	 *
	 * @return JP4WC_Admin_Notices
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 2.3.4
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_jp4wc_security_checklist' ) );
		add_action( 'admin_notices', array( $this, 'admin_jp4wc_promotion' ) );
		add_action( 'admin_notices', array( $this, 'admin_jp4wc_paypal_deprecation' ) );
		add_action( 'wp_loaded', array( $this, 'jp4wc_hide_notices' ) );

		add_action( 'wp_ajax_jp4wc_pr_dismiss_prompt', array( $this, 'jp4wc_dismiss_review_prompt' ) );
	}

	/**
	 * Prevent cloning of the instance
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the instance
	 *
	 * @throws Exception When trying to unserialize the singleton instance.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
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

		// Notification display content.
		if ( get_option( 'jp4wc_hide_security_check_notice', 0 ) ) {
			return;
		}

		// Check if the user has placed orders in the last 5 days.
		if ( ! jp4wc_has_orders_in_last_5_days() ) {
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
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'jp4wc-hide-notice', 'security' ), 'jp4wc_hide_notices_nonce', '_jp4wc_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>
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
	 * Display promotion notice for WooCommerce admins.
	 *
	 * Shows a notice to promotion from Artisan Workshop
	 * and the admin hasn't dismissed the notice.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	public function admin_jp4wc_promotion() {
		// Only show to WooCommerce admins.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Check if the user has placed orders in the last 5 days.
		if ( ! jp4wc_has_orders_in_last_5_days() ) {
			return;
		}

		self::jp4wc_promotion_display();
	}

	/**
	 * Display the promotion notice.
	 *
	 * @since 2.8.0
	 */
	public static function jp4wc_promotion_display() {
		$set_promotion = array();
		$set_promotion = self::get_promotion_content();

		// No promotion content available.
		if ( empty( $set_promotion ) ) {
			return;
		}

		$notice_css = ! empty( $set_promotion['css'] ) ? $set_promotion['css'] : '';
		$notice_key = ! empty( $set_promotion['key'] ) ? $set_promotion['key'] : '';

		// Notification display content.
		if ( get_option( 'jp4wc_hide_' . $notice_key . '_notice', 0 ) ) {
			return;
		}

		$catch_copy     = ! empty( $set_promotion['catch_copy'] ) ? $set_promotion['catch_copy'] : '';
		$catch_copy_css = ! empty( $set_promotion['catch_copy_css'] ) ? $set_promotion['catch_copy_css'] : '';

		$promotion_text1 = ! empty( $set_promotion['text1'] ) ? $set_promotion['text1'] : '';
		$promotion_text2 = ! empty( $set_promotion['text2'] ) ? $set_promotion['text2'] : '';

		$promotion_button_css  = ! empty( $set_promotion['button_css'] ) ? $set_promotion['button_css'] : '';
		$promotion_button_text = ! empty( $set_promotion['button_text'] ) ? $set_promotion['button_text'] : '';
		$promotion_link        = ! empty( $set_promotion['URL'] ) ? $set_promotion['URL'] : '';
		?>
		<div class="notice notice-info jp4wc-promotion-notice" id="pr_jp4wc_promotion" style="<?php echo esc_attr( $notice_css ); ?>">
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'jp4wc-hide-notice', $notice_key ), 'jp4wc_hide_notices_nonce', '_jp4wc_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>
		<div id="jp4wc-promotion-notice-content">
			<h2 style="<?php echo esc_attr( $catch_copy_css ); ?>"><?php echo esc_html( $catch_copy ); ?></h2>
			<p>
				<?php echo esc_html( $promotion_text1 ); ?><br />
				<?php echo esc_html( $promotion_text2 ); ?><br />
			</p>
			<a href="<?php echo esc_url( $promotion_link ); ?>" target="_blank" rel="noopener noreferrer" style="<?php echo esc_attr( $promotion_button_css ); ?>">
				<?php echo esc_html( $promotion_button_text ); ?>
			</a>
		</div>
		</div>
		<?php
	}

	/**
	 * Display PayPal deprecation notice for WooCommerce admins.
	 *
	 * Shows a notice to inform that PayPal will be removed from the plugin
	 * in updates after February 2026.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	public function admin_jp4wc_paypal_deprecation() {
		// Only show to WooCommerce admins.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Check if already dismissed.
		if ( get_option( 'jp4wc_hide_paypal_deprecation_notice', 0 ) ) {
			return;
		}

		// Only show if current date is before February 2026.
		$current_date = current_time( 'Y-m-d' );
		if ( $current_date >= '2026-02-28' ) {
			// Automatically dismiss if we're past February 2026.
			update_option( 'jp4wc_hide_paypal_deprecation_notice', 1 );
			return;
		}

		// Check if PayPal gateway is enabled.
		if ( ! $this->is_paypal_gateway_enabled() ) {
			return;
		}

		self::jp4wc_paypal_deprecation_display();
	}

	/**
	 * Display the PayPal deprecation notice.
	 *
	 * @since 2.8.0
	 */
	public static function jp4wc_paypal_deprecation_display() {
		?>
		<div class="notice notice-warning jp4wc-paypal-deprecation-notice" id="jp4wc_paypal_deprecation" style="background-color: #fff3cd; color: #856404; border-left: 4px solid #ffc107;">
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'jp4wc-hide-notice', 'paypal_deprecation' ), 'jp4wc_hide_notices_nonce', '_jp4wc_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"></a>
		<div id="jp4wc-paypal-deprecation-content">
			<h2 style="color:#856404;"><?php esc_html_e( '【Important Notice】PayPal Integration Removal', 'woocommerce-for-japan' ); ?></h2>
			<p>
				<?php esc_html_e( 'Starting with updates from February 2026, PayPal payment gateway will be removed from the Japanized for WooCommerce plugin.', 'woocommerce-for-japan' ); ?><br />
				<?php esc_html_e( 'If you are currently using PayPal, please consider installing the official PayPal plugin or using an alternative payment method.', 'woocommerce-for-japan' ); ?><br />
				<strong><?php esc_html_e( 'Please prepare for this change before the update.', 'woocommerce-for-japan' ); ?></strong>
			</p>
		</div>
		</div>
		<?php
	}

	/**
	 * Checks if PayPal payment gateway is enabled.
	 *
	 * @since 2.8.0
	 * @return bool True if PayPal gateway is enabled, false otherwise.
	 */
	private function is_paypal_gateway_enabled() {
		// Check if WooCommerce is active.
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		// Get available payment gateways.
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		// Check if PayPal gateway exists and is enabled.
		if ( isset( $payment_gateways['paypal'] ) && 'yes' === $payment_gateways['paypal']->enabled && get_option( 'wc4jp-paypal' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Fetches promotion content from remote JSON endpoint and converts it to an array.
	 *
	 * Retrieves promotion data from https://wc.artws.info/jp4wc-promotion-notices.json endpoint and decodes
	 * the JSON response into a PHP array. Filters promotions by current locale and randomly selects one to display.
	 * Handles errors gracefully by returning an empty array on failure.
	 *
	 * Expected JSON format:
	 * [
	 *   {
	 *     "locale": "ja",
	 *     "key": "2026_new_year_campaign",
	 *     "css": "background-color: #002F6C; color: #D1C1FF;",
	 *     "catch_copy": "新年キャンペーン2026！",
	 *     "catch_copy_css": "color:#fff;",
	 *     "text1": "WooCommerce Japanユーザー特別割引！",
	 *     "text2": "1月31日まで全てのプレミアム拡張機能が30%オフ。",
	 *     "button_css": "display: inline-block; padding: 10px 20px; background-color: #3498db; color: #fff; border-radius: 5px; text-decoration: none; font-weight: bold;",
	 *     "button_text": "キャンペーン詳細を見る",
	 *     "URL": "https://example.com/new-year-campaign"
	 *   },
	 *   {
	 *     "locale": "en",
	 *     "key": "spring_sale_2026",
	 *     "css": "background-color: #f0f8ff; color: #333;",
	 *     "catch_copy": "Spring Sale Now On!",
	 *     "catch_copy_css": "color:#2c3e50;",
	 *     "text1": "Refresh your store with our spring updates.",
	 *     "text2": "Limited time offer - save up to 50% on selected products.",
	 *     "button_css": "display: inline-block; padding: 10px 20px; background-color: #27ae60; color: #fff; border-radius: 5px; text-decoration: none; font-weight: bold;",
	 *     "button_text": "Shop Now",
	 *     "URL": "https://example.com/spring-sale"
	 *   }
	 * ]
	 *
	 * @since 2.7.15
	 * @return array Array of promotion data, or empty array on failure.
	 */
	private static function get_promotion_content() {
		$promotion_url = 'https://wc.artws.info/jp4wc-promotion-notices.json';

		// Make remote request to fetch JSON data.
		$response = wp_remote_get(
			$promotion_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for errors in the response.
		if ( is_wp_error( $response ) ) {
			return array();
		}

		// Get the response body.
		$body = wp_remote_retrieve_body( $response );

		// Decode JSON to array.
		$promotions = json_decode( $body, true );

		// Return empty array if JSON decode fails or result is not an array.
		if ( ! is_array( $promotions ) || empty( $promotions ) ) {
			return array();
		}

		// Get current locale (e.g., "ja", "en_US").
		$current_locale = get_locale();

		// Extract language code from locale (e.g., "ja" from "ja" or "ja_JP").
		$current_language = substr( $current_locale, 0, 2 );

		// Filter promotions by current language.
		$filtered_promotions = array_filter(
			$promotions,
			function ( $promotion ) use ( $current_language ) {
				// If locale field doesn't exist, include it for backward compatibility.
				if ( ! isset( $promotion['locale'] ) ) {
					return true;
				}
				// Match the language code.
				return $current_language === $promotion['locale'];
			}
		);

		// If no promotions match current language, try fallback to English or all promotions.
		if ( empty( $filtered_promotions ) ) {
			// Try to get English promotions as fallback.
			$filtered_promotions = array_filter(
				$promotions,
				function ( $promotion ) {
					return isset( $promotion['locale'] ) && 'en' === $promotion['locale'];
				}
			);

			// If still empty, use all promotions.
			if ( empty( $filtered_promotions ) ) {
				$filtered_promotions = $promotions;
			}
		}

		// Return empty array if no promotions available after filtering.
		if ( empty( $filtered_promotions ) ) {
			return array();
		}

		// Randomly select one promotion to display.
		$random_key = array_rand( $filtered_promotions );

		return $filtered_promotions[ $random_key ];
	}

	/**
	 * Checks if the current PHP version is considered safe (8.1.0 or higher).
	 *
	 * @since 2.6.37
	 * @return bool True if PHP version is safe, false otherwise.
	 */
	public function is_safe_php_version() {
		$php_ver = phpversion();
		if ( version_compare( $php_ver, '8.2.0', '>=' ) ) {
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

		if ( isset( $latest_parts[0] ) && isset( $current_parts[0] ) && $current_parts[0] !== $latest_parts[0] ) {
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
			return ( 2 <= $latest_parts[1] - $current_parts[1] ) ? false : true;
		}

		// If major versions are the same, check if minor version is at least 2 versions behind.
		if ( $latest_parts[0] === $current_parts[0] && ( 2 <= $latest_parts[1] - $current_parts[1] ) ) {
			return false;
		}

		// Otherwise, the version is current enough.
		return true;
	}

	/**
	 * Hides the security checklist notice when the user opts to dismiss it.
	 *
	 * This function checks for a specific GET parameter and nonce to securely
	 * update the option that controls the visibility of the security checklist notice.
	 *
	 * @since 2.7.1
	 * @return void
	 */
	public function jp4wc_hide_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['jp4wc-hide-notice'] ) && 'security' === sanitize_text_field( wp_unslash( $_GET['jp4wc-hide-notice'] ) ) ) {
			if ( ! isset( $_GET['_jp4wc_notice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_jp4wc_notice_nonce'] ) ), 'jp4wc_hide_notices_nonce' ) ) {
				return;
			}
			update_option( 'jp4wc_hide_security_check_notice', 1 );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['jp4wc-hide-notice'] ) && 'ecbuddy' === sanitize_text_field( wp_unslash( $_GET['jp4wc-hide-notice'] ) ) ) {
			if ( ! isset( $_GET['_jp4wc_notice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_jp4wc_notice_nonce'] ) ), 'jp4wc_hide_notices_nonce' ) ) {
				return;
			}
			update_option( 'jp4wc_hide_ecbuddy_notice', 1 );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['jp4wc-hide-notice'] ) && 'paypal_deprecation' === sanitize_text_field( wp_unslash( $_GET['jp4wc-hide-notice'] ) ) ) {
			if ( ! isset( $_GET['_jp4wc_notice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_jp4wc_notice_nonce'] ) ), 'jp4wc_hide_notices_nonce' ) ) {
				return;
			}
			update_option( 'jp4wc_hide_paypal_deprecation_notice', 1 );
		}
		if ( get_option( 'jp4wc_hide_security_check_notice', 0 ) ) {
			return;
		}
		if ( get_option( 'jp4wc_hide_ecbuddy_notice', 0 ) ) {
			return;
		}
		if ( get_option( 'jp4wc_hide_paypal_deprecation_notice', 0 ) ) {
			return;
		}
	}
}

// Initialize the singleton instance.
JP4WC_Admin_Notices::get_instance();
