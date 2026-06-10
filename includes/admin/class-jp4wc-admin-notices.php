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

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1, 403 );
		}

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'jp4wc_pr_dismiss_prompt' ) ) {
			die( 'Failed' );
		}

		if ( ! empty( $_POST['type'] ) ) {
			if ( 'remove' === sanitize_text_field( wp_unslash( $_POST['type'] ) ) ) {
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
	 * Hides the security checklist notice when the user opts to dismiss it.
	 *
	 * This function checks for a specific GET parameter and nonce to securely
	 * update the option that controls the visibility of the security checklist notice.
	 *
	 * @since 2.7.1
	 * @return void
	 */
	public function jp4wc_hide_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
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
