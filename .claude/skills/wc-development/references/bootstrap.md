# Bootstrap: WooCommerce Plugin Setup

## Plugin Headers

```php
<?php
/**
 * Plugin Name:          My WooCommerce Extension
 * Plugin URI:           https://example.com/my-extension
 * Description:          Extends WooCommerce with custom functionality.
 * Version:              1.0.0
 * Author:               Developer Name
 * Author URI:           https://example.com
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          my-wc-extension
 * Domain Path:          /languages
 * Requires at least:    6.6
 * Tested up to:         7.0
 * Requires PHP:         8.0
 * Requires Plugins:     woocommerce
 *
 * WC requires at least: 9.0
 * WC tested up to:      10.6
 * Woo: 12345:abcdef1234567890abcdef1234567890
 */
```

Key fields:
- `Requires Plugins: woocommerce` — WordPress 6.5+ dependency declaration
- `WC requires at least` / `WC tested up to` — WooCommerce version range
- `Woo:` — WooCommerce.com Marketplace product ID (only for marketplace products)
- Text domain must match plugin directory name (hyphens, no underscores)

## WooCommerce Active Check

```php
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'my_extension_init', 10 );

function my_extension_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>';
            esc_html_e( 'My Extension requires WooCommerce to be installed and active.', 'my-wc-extension' );
            echo '</p></div>';
        });
        return;
    }

    define( 'MY_EXTENSION_VERSION', '1.0.0' );
    define( 'MY_EXTENSION_PLUGIN_FILE', __FILE__ );
    define( 'MY_EXTENSION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'MY_EXTENSION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

    require_once MY_EXTENSION_PLUGIN_DIR . 'includes/class-main.php';
    \My_Extension\Main::instance();
}
```

## HPOS Compatibility Declaration (mandatory)

```php
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});
```

## Block Checkout Compatibility Declaration

```php
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
});
```

Both declarations can be in the same `before_woocommerce_init` callback.

## Activation / Deactivation / Uninstall

```php
register_activation_hook( __FILE__, function() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'This plugin requires WooCommerce.', 'my-wc-extension' ) );
    }
    // Create custom tables, set default options, add capabilities
});

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'my_extension_cron_event' );
    // Clear transients, remove temporary data
    // Do NOT delete user data here — use uninstall.php
});
```

uninstall.php for full cleanup:
```php
<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete options
delete_option( 'my_extension_settings' );

// Delete custom tables
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_extension_table" );

// Clear scheduled actions
if ( function_exists( 'as_unschedule_all_actions' ) ) {
    as_unschedule_all_actions( 'my_extension_cron_event' );
}
```

## Hook Priority Reference

| Hook | Priority | Purpose |
|------|----------|---------|
| `before_woocommerce_init` | 10 | Feature compatibility declarations |
| `plugins_loaded` | 10 | Main plugin initialization |
| `woocommerce_loaded` | 10 | WC-dependent initialization |
| `woocommerce_blocks_loaded` | 10 | Blocks integration registration |
| `woocommerce_init` | 10 | WC session/cart available |
| `init` | 10 | Register post types, taxonomies |

## Autoloading

```php
// composer.json
{
    "autoload": {
        "psr-4": {
            "My_Extension\\": "includes/"
        }
    }
}
```

Run `composer dump-autoload --optimize` and require in main plugin file:
```php
require_once __DIR__ . '/vendor/autoload.php';
```
