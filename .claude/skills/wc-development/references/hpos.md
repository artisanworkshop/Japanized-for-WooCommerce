# HPOS (High-Performance Order Storage)

HPOS moves order data from `wp_posts`/`wp_postmeta` to dedicated tables (`wp_wc_orders`,
`wp_wc_orders_meta`, `wp_wc_order_addresses`, etc.) for better scalability.
All new extensions MUST be HPOS-compatible.

## Migration Patterns

### Reading Order Data

```php
// ❌ WRONG: Direct post meta
$email = get_post_meta( $order_id, '_billing_email', true );
$total = get_post_meta( $order_id, '_order_total', true );
$custom = get_post_meta( $order_id, '_my_custom_field', true );

// ✅ CORRECT: WooCommerce Order API
$order = wc_get_order( $order_id );
$email = $order->get_billing_email();
$total = $order->get_total();
$custom = $order->get_meta( '_my_custom_field', true );
```

### Writing Order Data

```php
// ❌ WRONG
update_post_meta( $order_id, '_my_field', $value );

// ✅ CORRECT — always call save() after update_meta_data()
$order = wc_get_order( $order_id );
$order->update_meta_data( '_my_field', $value );
$order->save();
```

### Querying Orders

```php
// ❌ WRONG: WP_Query for orders
$query = new WP_Query([
    'post_type'  => 'shop_order',
    'meta_key'   => '_my_field',
    'meta_value' => 'target',
]);

// ✅ CORRECT: wc_get_orders()
$orders = wc_get_orders([
    'meta_key'     => '_my_field',
    'meta_value'   => 'target',
    'limit'        => 50,
    'orderby'      => 'date',
    'order'        => 'DESC',
    'status'       => ['wc-processing', 'wc-completed'],
    'date_created' => '>' . strtotime( '-30 days' ),
]);
```

### Order Type Detection

```php
// ❌ WRONG
$type = get_post_type( $order_id );

// ✅ CORRECT
use Automattic\WooCommerce\Utilities\OrderUtil;
$type = OrderUtil::get_order_type( $order_id );
```

### Storage Type Detection

```php
use Automatic\WooCommerce\Utilities\OrderUtil;

if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
    // HPOS is active
} else {
    // Legacy post-based storage
}
```

### Admin Screen Detection (Meta Boxes)

```php
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

$screen = class_exists( CustomOrdersTableController::class )
    && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
    ? wc_get_page_screen_id( 'shop-order' )
    : 'shop_order';

add_meta_box( 'my-meta-box', __( 'My Extension', 'my-wc-extension' ), 'render_callback', $screen, 'side' );
```

### Admin Columns

```php
// For HPOS, hook into the orders list table
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'add_custom_column' );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'render_custom_column', 10, 2 );

// For legacy post-type orders (backward compat)
add_filter( 'manage_edit-shop_order_columns', 'add_custom_column' );
add_action( 'manage_shop_order_posts_custom_column', 'render_custom_column', 10, 2 );
```

### Direct Database Queries (avoid, but if necessary)

```php
if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
    $table = OrderUtil::get_table_for_orders();
    $result = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $order_id )
    );
} else {
    $result = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'shop_order'", $order_id )
    );
}
```

## Common HPOS Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Order meta not saving | Missing `$order->save()` after `update_meta_data()` | Add `$order->save()` |
| Order not found | Using `get_post()` instead of `wc_get_order()` | Switch to `wc_get_order()` |
| Admin column empty | Meta box registered for `shop_order` post type only | Use `wc_get_page_screen_id()` |
| Compatibility warning | Missing `declare_compatibility()` | Add in `before_woocommerce_init` |
| `$order->id` deprecation | Accessing property directly | Use `$order->get_id()` |
| Bulk action not working | Hooked to `post_row_actions` only | Also hook to HPOS list table |

## Checking HPOS Sync Status

```php
// Is data sync (compatibility mode) enabled?
$sync = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );

// Are tables in sync?
$in_sync = OrderUtil::is_custom_order_tables_in_sync();
```
