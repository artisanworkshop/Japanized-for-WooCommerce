<?php
/**
 * Plugin Name: Artws Security Hub
 * Plugin URI: https://github.com/artisanworkshop/Japanized-for-WooCommerce/tree/main/plugins/artws-security-hub
 * Description: Server side of the Japanized for WooCommerce security check on wc.artws.info: serves the vulnerability feed and answers the free security check.
 * Version: 0.1.0
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

define( 'ARTWS_SEC_VERSION', '0.1.0' );
define( 'ARTWS_SEC_PLUGIN_FILE', __FILE__ );
define( 'ARTWS_SEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
