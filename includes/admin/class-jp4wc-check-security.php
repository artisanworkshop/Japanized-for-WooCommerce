<?php
/**
 * Security Check functionality for WooCommerce Japan
 *
 * @package WooCommerce-For-Japan
 * @version 2.6.37
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that represents security checks.
 *
 * @version 2.6.37
 * @since 2.6.27
 */
class JP4WC_Check_Security {
	/**
	 * Constructor
	 *
	 * @since 2.6.27
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'jp4wc_check_security_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'jp4wc_check_security_menu_scripts' ) );
		add_action( 'init', array( $this, 'jp4wc_security_settings' ) );
		// Add the rest api endpoint for the security check.
		add_action( 'rest_api_init', array( $this, 'jp4wc_ms_start_scan' ) );
		add_action( 'rest_api_init', array( $this, 'jp4wc_ms_process_scan_batch' ) );
	}

	/**
	 * Register the security check menu page in WooCommerce admin.
	 *
	 * @since 2.6.27
	 * @return void
	 */
	public function jp4wc_check_security_menu() {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}
		wc_admin_register_page(
			array(
				'id'         => 'jp4wc-security-check',
				'title'      => __( 'Security Check List', 'woocommerce-for-japan' ),
				'parent'     => 'woocommerce',
				'path'       => '/jp4wc-security-check',
				'position'   => 200,
				'capability' => 'manage_woocommerce',
			)
		);
	}

	/**
	 * Get screen id.
	 *
	 * @since 1.0.0
	 */
	public function get_screen_id() {
		return '/jp4wc-security-check';
	}

	/**
	 * Enqueue scripts and styles for the security check menu page.
	 *
	 * @since 2.6.27
	 * @return void
	 */
	public function jp4wc_check_security_menu_scripts() {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		if ( ! isset( $_GET['path'] ) || ( isset( $_GET['path'] ) && $this->get_screen_id() !== $_GET['path'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$asset_file = JP4WC_ABSPATH . 'assets/js/admin/security.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset  = include $asset_file;
		$handle = 'jp4wc-security-check-script';

		wp_enqueue_script(
			$handle,
			JP4WC_URL_PATH . 'assets/js/admin/security.js',
			$asset['dependencies'],
			$asset['version'],
			array(
				'in_footer' => true,
			)
		);

		wp_enqueue_style(
			'jp4wc-security-check-style',
			JP4WC_URL_PATH . 'assets/js/admin/security.css',
			array_filter(
				$asset['dependencies'],
				function ( $style ) {
					return wp_style_is( $style, 'registered' );
				}
			),
			$asset['version'],
		);

		$login_url        = wp_login_url();
		$check_basic      = $this::jp4wc_security_check_admin_login_basic( $login_url );
		$check_ip         = $this::jp4wc_security_check_admin_login_ip( $login_url, '117.103.160.46' );
		$check_two_factor = $this::jp4wc_is_two_factor_plugin_active();
		$security_plugins = array(
			'siteguard/siteguard.php',
			'wordfence/wordfence.php',
			'all-in-one-wp-security-and-firewall/wp-security.php',
			'security-malware-firewall/security-malware-firewall.php',
			'bulletproof-security/bulletproof-security.php',
			'sucuri-scanner/sucuri.php',
			'wpremote/plugin.php',
		);

		$check_plugins      = $this::jp4wc_check_plugins_status( $security_plugins );
		$check_plugins_flag = false;
		if ( ! empty( $check_plugins ) ) {
			foreach ( $check_plugins as $plugin => $status ) {
				if ( 'installed (active)' === $status ) {
					$check_plugins_flag = true;
					break;
				}
			}
		}

		// Check if Jetpack Protect module is active.
		if ( $this->jp4wc_is_jetpack_protect_active() && $this->jp4wc_check_jetpack_protect_status() ) {
			$check_plugins_flag = true;
		}

		$check_php         = $this::jp4wc_check_php_version();
		$check_php_flag    = $check_php['result'];
		$check_php_message = $check_php['message'];

		$check_update                = $this::jp4wc_check_wordpress_updates();
		$check_core_update_message   = '';
		$check_plugin_update_message = '';
		$check_theme_update_message  = '';
		$check_update_flag           = false;
		if ( true === $check_update ) {
			$check_update_flag    = true;
			$check_update_message = '';
		} elseif ( is_array( $check_update ) ) {
			if ( 'Update required' === $check_update['core'] ) {
				$check_core_update_message = __( 'WordPress core update required', 'woocommerce-for-japan' );
			} elseif ( 'Update required' === $check_update['plugins'] ) {
				$check_plugin_update_message = __( 'Not all plugins are up to date.', 'woocommerce-for-japan' );
			} elseif ( 'Update required' === $check_update['themes'] ) {
				$check_theme_update_message = __( 'Not all themes are up to date.', 'woocommerce-for-japan' );
			}
		}

		$sales_last_30_days = $this::jp4wc_get_woocommerce_sales_last_30_days();

		// Set translations.
		wp_set_script_translations(
			$handle,
			'woocommerce-for-japan',
			JP4WC_ABSPATH . 'i18n'
		);

		wp_localize_script(
			$handle,
			'jp4wcSecurityCheckResult',
			array(
				'checkBasic'               => $check_basic,
				'checkIp'                  => $check_ip,
				'checkTwoFactor'           => $check_two_factor,
				'checkPlugins'             => $check_plugins_flag,
				'checkPHPFlag'             => $check_php_flag,
				'checkPHPMessage'          => $check_php_message,
				'checkUpdateFlag'          => $check_update_flag,
				'checkUpdateCoreMessage'   => $check_core_update_message,
				'checkUpdatePluginMessage' => $check_plugin_update_message,
				'checkUpdateThemeMessage'  => $check_theme_update_message,
				'salesLast30Days'          => $sales_last_30_days,
			)
		);
	}

	/**
	 * Register security settings options.
	 *
	 * @since 2.6.27
	 * @return void
	 */
	public function jp4wc_security_settings() {
		$default = array(
			'checkAdminLogin'      => false,
			'checkSeucirytPluigns' => false,
		);
		$schema  = array(
			'type'       => 'object',
			'properties' => array(
				'checkAdminLogin'      => array(
					'type' => 'boolean',
				),
				'checkSeucirytPluigns' => array(
					'type' => 'boolean',
				),
			),
		);

		register_setting(
			'options',
			'jp4wc_security_settings',
			array(
				'type'              => 'object',
				'default'           => $default,
				'sanitize_callback' => array( $this, 'jp4wc_security_sanitize_settings' ),
				'show_in_rest'      => array(
					'schema' => $schema,
				),
			)
		);
	}

	/**
	 * Sanitize the security settings input.
	 *
	 * @since 2.6.27
	 * @param mixed $input The input to sanitize.
	 * @return array The sanitized input.
	 */
	public function jp4wc_security_sanitize_settings( $input ) {
		$input = (object) $input;
		$input = array_map( 'sanitize_text_field', (array) $input );
		return $input;
	}

	/**
	 * Check if basic authentication is enabled for the WordPress admin login.
	 *
	 * @since 2.6.27
	 * @param string $url The URL to check.
	 * @return bool True if basic authentication is enabled, false otherwise.
	 */
	private function jp4wc_security_check_admin_login_basic( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 0,
			)
		);

		$message = '';

		$basic_authentication_flag = false;
		if ( is_wp_error( $response ) ) {
			$message = __( 'Error: ', 'woocommerce-for-japan' ) . esc_html( $response->get_error_message() );
		} else {
			// Get response headers.
			$headers = wp_remote_retrieve_headers( $response );

			// Checks if the www-authenticate header is present and contains "Basic".
			if ( isset( $headers['www-authenticate'] ) && stripos( $headers['www-authenticate'], 'Basic' ) !== false ) {
				$basic_authentication_flag = true;
			}
		}
		return $basic_authentication_flag;
	}


	/**
	 * Check if admin login is accessible from a specific IP address.
	 *
	 * @since 2.6.27
	 * @param string $url The URL to check.
	 * @param string $ip The IP address to check from.
	 * @return int|false HTTP response code or false on failure.
	 */
	private function jp4wc_security_check_admin_login_ip( $url, $ip ) {
		$args = array(
			'timeout'        => 10,
			'redirection'    => 0,
			'stream_context' => stream_context_create(
				array(
					'socket' => array(
						// Send a request from the specified IP address (port is automatically assigned at 0).
						'bindto' => "{$ip}:0",
					),
				)
			),
		);

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check if the Two Factor Authentication plugin is active.
	 *
	 * @since 2.6.27
	 * @return bool True if the plugin is active, false otherwise.
	 */
	private function jp4wc_is_two_factor_plugin_active() {
		$plugin_file = 'two-factor/two-factor.php';
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( $plugin_file ) ) {
			return true;
		}
		if ( is_multisite() ) {
			$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( isset( $network_plugins[ $plugin_file ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check the installation and activation status of specified plugins.
	 *
	 * @since 2.6.27
	 * @param array $plugin_files List of plugin files to check.
	 * @return array Status of each plugin (installed/active, installed/inactive, or not installed).
	 */
	private function jp4wc_check_plugins_status( $plugin_files ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugins = get_plugins();

		$result = array();

		foreach ( $plugin_files as $plugin_file ) {
			if ( array_key_exists( $plugin_file, $installed_plugins ) ) {
				if ( is_plugin_active( $plugin_file ) ) {
					$result[ $plugin_file ] = 'installed (active)';
				} else {
					$result[ $plugin_file ] = 'installed (inactive)';
				}
			} else {
				$result[ $plugin_file ] = 'not installed';
			}
		}

		return $result;
	}

	/**
	 * Check if Jetpack Protect module is active.
	 *
	 * @since 2.6.34
	 * @return bool True if Jetpack Protect module is active, false otherwise.
	 */
	private function jp4wc_is_jetpack_protect_active() {
		if ( class_exists( 'Jetpack' ) ) {
			$active_modules = Jetpack::get_active_modules();

			// Check if the Protect module is enabled.
			if ( in_array( 'protect', $active_modules ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if Jetpack Protect is active and functioning.
	 *
	 * @since 2.6.34
	 * @return bool True if Jetpack Protect is active and functioning, false otherwise.
	 */
	private function jp4wc_check_jetpack_protect_status() {
		if ( ! class_exists( 'Jetpack' ) ) {
			return false;
		}

		$site_url        = get_home_url();
		$jetpack_options = get_option( 'jetpack_options' );

		if ( isset( $jetpack_options['id'] ) ) {
			$jetpack_site_id = $jetpack_options['id'];

			$response = wp_remote_get( "https://public-api.wordpress.com/rest/v1.1/sites/{$jetpack_site_id}/protect" );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			return isset( $body['active'] ) && $body['active'];
		}

		return false;
	}

	/**
	 * Check the current PHP version and its support status.
	 *
	 * @since 2.6.27
	 * @return string Message indicating PHP version support status.
	 */
	private function jp4wc_check_php_version() {
		$php_versions = array(
			'8.4' => array(
				'active_support'   => '2026-12-31',
				'security_support' => '2028-12-31',
			),
			'8.3' => array(
				'active_support'   => '2025-12-31',
				'security_support' => '2027-12-31',
			),
			'8.2' => array(
				'active_support'   => '2024-12-31',
				'security_support' => '2026-12-31',
			),
			'8.1' => array(
				'active_support'   => '2023-11-25',
				'security_support' => '2025-12-31',
			),
			'8.0' => array(
				'active_support'   => '2022-11-26',
				'security_support' => '2023-11-26',
			), // EOL.
			'7.4' => array(
				'active_support'   => '2021-11-28',
				'security_support' => '2022-11-28',
			), // EOL.
		);

		// Get the current PHP version.
		$current_version = phpversion();
		$major_minor     = implode( '.', array_slice( explode( '.', $current_version ), 0, 2 ) );

		// Check if your PHP version is in the list.
		if ( ! isset( $php_versions[ $major_minor ] ) ) {
			/* translators: %s: PHP version number */
			return sprintf( __( 'Support information for PHP %s is unknown.', 'woocommerce-for-japan' ), $current_version );
		}

		$today                = date_i18n( 'Y-m-d' );
		$security_support_end = $php_versions[ $major_minor ]['security_support'];

		if ( $today <= $php_versions[ $major_minor ]['active_support'] ) {
			return array(
				'result'  => true,
				/* translators: %s: PHP version number */
				'message' => sprintf( __( 'PHP %s is **Active Support** (new features and bug fixes available)', 'woocommerce-for-japan' ), $current_version ),
			);
		} elseif ( $today <= $security_support_end ) {
			return array(
				'result'  => true,
				/* translators: %s: PHP version number */
				'message' => sprintf( __( 'PHP %s is in **Security Support** (security fixes only)', 'woocommerce-for-japan' ), $current_version ),
			);
		} else {
			return array(
				'result'  => false,
				/* translators: 1: PHP version number, 2: Last supported date */
				'message' => sprintf( __( 'PHP %1$s has **End of Life (EOL)**!\nLast supported date: %2$s \nWe recommend you upgrade immediately.', 'woocommerce-for-japan' ), $current_version, $security_support_end ),
			);
		}
	}

	/**
	 * Check for available updates in WordPress core, plugins, and themes.
	 *
	 * @since 2.6.27
	 * @return array|bool Returns true if everything is up to date, or an array of update statuses.
	 */
	private function jp4wc_check_wordpress_updates() {
		// Check for WordPress core updates.
		$core_updates         = get_core_updates();
		$core_update_required = ! empty( $core_updates ) && 'latest' !== $core_updates[0]->response;

		// Check for plugin updates.
		$plugin_updates         = get_plugin_updates();
		$plugin_update_required = ! empty( $plugin_updates );

		// Check for theme updates.
		$theme_updates         = get_theme_updates();
		$theme_update_required = ! empty( $theme_updates );

		$update_status = array(
			'core'    => $core_update_required ? 'Update required' : 'Latest',
			'plugins' => $plugin_update_required ? 'Update required' : 'Latest',
			'themes'  => $theme_update_required ? 'Update required' : 'Latest',
		);

		// Returns true if all are up to date.
		if ( ! $core_update_required && ! $plugin_update_required && ! $theme_update_required ) {
			return true;
		}

		return $update_status;
	}

	/**
	 * Get the total sales amount for WooCommerce orders in the last 30 days.
	 *
	 * @since 2.6.27
	 * @return float|bool Total sales amount or false if WooCommerce is not active.
	 */
	private function jp4wc_get_woocommerce_sales_last_30_days() {
		if ( ! class_exists( 'WC_Order_Query' ) ) {
			return false;
		}

		$args = array(
			'limit'        => -1,
			'status'       => array( 'completed', 'processing' ),
			'date_created' => '>' . date_i18n( 'Y-m-d', strtotime( '-30 days' ) ),
			'return'       => 'ids',
		);

		$orders      = wc_get_orders( $args );
		$total_sales = 0;

		foreach ( $orders as $order_id ) {
			$order        = wc_get_order( $order_id );
			$total_sales += $order->get_total();
		}

		return $total_sales;
	}

	/**
	 * Register REST API endpoint for initiating security scan.
	 *
	 * @since 2.6.27
	 * @return void
	 */
	public function jp4wc_ms_start_scan() {
		register_rest_route(
			'jp4wc/v1',
			'/security-start-scan',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'jp4wc_ms_start_scan_func' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			)
		);
	}

	/**
	 * Initiates the security scan process.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response Response object containing scan initialization status.
	 */
	public function jp4wc_ms_start_scan_func( WP_REST_Request $request ) {
		$directory = ABSPATH . 'wp-content/';
		$scanner   = new JP4WC_Malware_Check( $directory );
		$files     = $scanner->get_file_list();
		// Save the initial state as transient (expiration time 1 hour).
		set_transient(
			MS_SCAN_TRANSIENT_KEY,
			array(
				'fileList'          => $files,
				'currentIndex'      => 0,
				'currentSuspicious' => array(),
			),
			3600
		);
		return rest_ensure_response(
			array(
				'message'    => 'Scan started',
				'totalFiles' => count( $files ),
			)
		);
	}

	/**
	 * Register REST API endpoint for processing security scan batches.
	 *
	 * @since 2.6.27
	 * @return void
	 */
	public function jp4wc_ms_process_scan_batch() {
		register_rest_route(
			'jp4wc/v1',
			'/security-process-scan-batch',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'jp4wc_ms_process_scan_batch_func' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			)
		);
	}

	/**
	 * Process a batch of files for malware scanning.
	 *
	 * @since 2.6.27
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response Response object containing scan batch results.
	 */
	public function jp4wc_ms_process_scan_batch_func( WP_REST_Request $request ) {
		$session = get_transient( MS_SCAN_TRANSIENT_KEY );
		if ( ! $session ) {
			return rest_ensure_response(
				array(
					'error' => __( 'No scan session found. Please start a new scan.', 'woocommerce-for-japan' ),
				)
			);
		}
		$directory                   = ABSPATH . 'wp-content/';
		$scanner                     = new JP4WC_Malware_Check( $directory );
		$scanner->file_list          = $session['fileList'];
		$scanner->current_index      = $session['currentIndex'];
		$scanner->current_suspicious = isset( $session['currentSuspicious'] ) ? $session['currentSuspicious'] : array();

		$result            = $scanner->process_batch();
		$merged_suspicious = array_merge(
			isset( $session['currentSuspicious'] ) ? $session['currentSuspicious'] : array(),
			$scanner->current_suspicious
		);
		// Save the updated state.
		$session['currentIndex']      = $result['current'];
		$session['currentSuspicious'] = $scanner->current_suspicious;

		$complete = false;
		if ( $scanner->is_complete() ) {
			$scanner->save_last_suspicious();
			$complete = true;
			// If there is a suspicious file, send it to us by email along with the site URL.
			if ( ! empty( $result['suspicious'] ) ) {
				$site_url = site_url();
				/* translators: %s: Site URL */
				$subject = sprintf( __( 'Malware Alert on %s', 'woocommerce-for-japan' ), $site_url );
				/* translators: %s: Site URL */
				$message  = sprintf( __( 'The following suspicious files were detected on your site (%s):', 'woocommerce-for-japan' ), $site_url ) . "\n\n";
				$message .= wp_json_encode( $result['suspicious'] );
				wp_mail( JP4WC_MALWARE_ALERT_EMAIL, $subject, $message );
			}
		}
		set_transient( MS_SCAN_TRANSIENT_KEY, $session, 3600 );
		return rest_ensure_response(
			array(
				'current'         => $result['current'],
				'total'           => $result['total'],
				'suspiciousFiles' => $this->get_filtered_suspicious_from_array( $merged_suspicious ),
				'complete'        => $complete,
			)
		);
	}
	/**
	 * Filter suspicious files from array by checking whitelist status.
	 *
	 * @since 2.6.27
	 * @param array $suspicious Array of suspicious files with their details.
	 * @return array Filtered array of suspicious files excluding whitelisted ones.
	 */
	private function get_filtered_suspicious_from_array( $suspicious ) {
		$directory = ABSPATH . 'wp-content/';
		$scanner   = new JP4WC_Malware_Check( $directory );
		$filtered  = array();
		foreach ( $suspicious as $file => $info ) {
			if ( $scanner->is_manual_whitelisted( $file ) || $scanner->is_auto_whitelisted( $file ) ) {
				continue;
			}
			$filtered[] = array(
				'file'      => $file,
				'mod_time'  => $info['mod_time'],
				'pattern'   => $info['pattern'],
				'line'      => $info['line'],
				'line_code' => $info['line_code'],
			);
		}
		return $filtered;
	}
}
