<?php
/**
 * Removes the data of the free security check when the plugin is deleted.
 *
 * The vulnerability feed (options and the files under ARTWS_SEC_FEED_DIR)
 * is left as it is: deactivation has already marked it stale.
 *
 * @package Artws_Security_Hub
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-artws-sec-db.php';
require_once __DIR__ . '/includes/class-artws-sec-site-store.php';
require_once __DIR__ . '/includes/class-artws-sec-site-stats.php';

Artws_Sec_Db::uninstall();
// The aggregates kept from a slow count on the settings page.
delete_transient( Artws_Sec_Site_Stats::CACHE_KEY );
