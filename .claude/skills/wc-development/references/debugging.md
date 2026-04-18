# Debugging WooCommerce Extensions

## Common Issues and Solutions

### HPOS Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Order meta not saving | Missing `$order->save()` | Add `$order->save()` after `update_meta_data()` |
| Order not found | Using `get_post()` | Use `wc_get_order()` |
| Admin column empty | Wrong screen ID for meta box | Use `wc_get_page_screen_id('shop-order')` |
| HPOS warning on status | Missing declaration | Add `FeaturesUtil::declare_compatibility()` |
| `$order->id` notice | Direct property access | Use `$order->get_id()` |
| Query returns no orders | Using `WP_Query` for orders | Use `wc_get_orders()` |

### Block Checkout Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Payment method not showing | Missing Blocks registration | Register via `woocommerce_blocks_payment_method_type_registration` |
| JS not loading | Wrong script handle | Check `get_payment_method_script_handles()` return value |
| Data not passing to server | Missing Store API callback | Register `woocommerce_store_api_register_update_callback` |
| Fields not rendering | React component error | Check browser console; verify JSX build |

### Gateway Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Not in settings | Filter not registered | Check `woocommerce_payment_gateways` filter |
| Always failing | Wrong nonce or auth | Check `process_payment()` return format |
| Webhook not received | Wrong callback URL | Verify `woocommerce_api_{class_name}` |
| Refund button missing | Missing support declaration | Add `'refunds'` to `$this->supports` |

## WordPress 7.0 Compatibility (2026)

WooCommerce 10.6.2 includes compatibility fixes for WP 7.0:

- **Admin CSS changes**: Analytics tables, dashboard cards may have extra padding.
  Avoid relying on specific WP admin CSS class names for styling.
- **Action buttons**: May wrap prematurely on smaller viewports.
  Test responsive behavior in WP 7.0.
- **Block editor updates**: New block editor capabilities. Test any
  custom editor integrations.

```php
// Check WordPress version for conditional compatibility
global $wp_version;
if ( version_compare( $wp_version, '7.0', '>=' ) ) {
    // WP 7.0+ specific code
}
```

## Debug Logging

```php
// WooCommerce logger (recommended)
$logger = wc_get_logger();
$context = [ 'source' => 'my-wc-extension' ];

$logger->debug( 'Debug message', $context );
$logger->info( 'Info message', $context );
$logger->warning( 'Warning message', $context );
$logger->error( 'Error message', $context );

// Logs stored in: wp-content/uploads/wc-logs/my-wc-extension-*.log
// View in: WooCommerce > Status > Logs
```

```php
// Enable WP_DEBUG for development
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

// WooCommerce-specific debug
define( 'WC_LOG_HANDLER', 'WC_Log_Handler_File' );
```

## WP-CLI Commands

```bash
# WooCommerce status
wp wc tool list --user=1

# Clear transients
wp wc tool run clear_transients --user=1

# HPOS verification
wp wc cot verify_cot_data

# Product operations
wp wc product list --user=1
wp wc product get 123 --user=1

# Order operations
wp wc shop_order list --user=1
wp wc shop_order get 456 --user=1

# Check plugin status
wp plugin list --status=active --format=csv | grep woocommerce
```

## System Status Check

```php
// Programmatic system status
$system_status = WC()->api->get_endpoint_data( '/wc/v3/system_status' );

// Check HPOS status
$hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

// Check WC version
$wc_version = WC()->version;
if ( version_compare( $wc_version, '10.0', '<' ) ) {
    // Legacy code path
}
```

## ActionScheduler Debugging

WooCommerce uses ActionScheduler for background processing:

```bash
# List pending actions
wp action-scheduler run --hooks=my_extension_action

# Check failed actions
wp action-scheduler list --status=failed --group=my-extension

# Clear stuck actions
wp action-scheduler clean --status=complete --before="2025-01-01 00:00:00"
```

```php
// Schedule action
as_schedule_single_action( time() + HOUR_IN_SECONDS, 'my_extension_process', [ $order_id ] );

// Recurring action
as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'my_extension_daily_task' );

// Handle action
add_action( 'my_extension_process', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    // Process...
} );
```

## Performance Profiling

```php
// Query Monitor plugin integration
do_action( 'qm/debug', 'My extension: processing order ' . $order_id );

// Measure execution time
$start = microtime( true );
// ... expensive operation ...
$elapsed = microtime( true ) - $start;
wc_get_logger()->debug(
    sprintf( 'Operation took %.3f seconds', $elapsed ),
    [ 'source' => 'my-wc-extension' ]
);
```

## WC 10.5/10.6 Specific Notes

- **Analytics batch imports**: WC 10.5 changed analytics imports to batch processing
  (100 orders every 12 hours). If your extension depends on real-time analytics data,
  you may need to manually trigger imports.
- **Product object caching**: WC 10.5 introduced experimental request-level product caching.
  If your extension modifies products during a request, the cache may return stale data.
  Use `wc_get_product()` with the `$force` parameter if needed.
- **Store API per_page minimum**: WC 10.6 enforces minimum `per_page=1` for product
  Store API endpoints. Extensions using `per_page=0` must implement pagination.
- **Product image lazy-loading**: WC 10.6 lazy-loads product images by default.
  Use `woocommerce_product_image_loading_attr` filter to override.
