<?php
/**
 * Plugin Name: Artws Security Hub
 * Plugin URI: https://github.com/artisanworkshop/Japanized-for-WooCommerce/tree/main/plugins/artws-security-hub
 * Description: Server side of the Japanized for WooCommerce security check on wc.artws.info: serves the vulnerability feed and answers the free security check.
 * Version: 0.4.1
 * Requires at least: 6.7
 * Requires PHP: 8.3
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: artws-security-hub
 * Update URI: false
 *
 * @package Artws_Security_Hub
 */

defined( 'ABSPATH' ) || exit;

define( 'ARTWS_SEC_VERSION', '0.4.1' );
define( 'ARTWS_SEC_PLUGIN_FILE', __FILE__ );
define( 'ARTWS_SEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-feed-exception.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-db.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-vuln-index.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-json-object-reader.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-feed-converter.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-record-validator.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-feed-builder.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-feed-publisher.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-feed-updater.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-admin.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-scan-request.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-rate-limiter.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-site-store.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-report-templates.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-report-builder.php';
require_once ARTWS_SEC_PLUGIN_DIR . 'includes/class-artws-sec-rest-controller.php';

register_activation_hook( __FILE__, array( 'Artws_Sec_Site_Store', 'activate' ) );
register_activation_hook( __FILE__, array( 'Artws_Sec_Feed_Updater', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Artws_Sec_Feed_Updater', 'deactivate' ) );
register_deactivation_hook( __FILE__, array( 'Artws_Sec_Site_Store', 'deactivate' ) );

Artws_Sec_Db::register_hooks();
Artws_Sec_Feed_Updater::register_hooks();
Artws_Sec_Admin::register_hooks();
Artws_Sec_Site_Store::register_hooks();
Artws_Sec_Rest_Controller::register_hooks();
