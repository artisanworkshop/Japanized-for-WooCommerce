# Order Lifecycle and Status Management

## Order Status Flow

```
pending → processing → completed
pending → on-hold → processing → completed
pending → failed
processing → refunded
any → cancelled
```

## Status Transition Hooks

```php
// Specific transition (most precise)
add_action( 'woocommerce_order_status_pending_to_processing', function( $order_id, $order ) {
    // Fires when order moves from pending to processing
}, 10, 2 );

// Any status change (broad)
add_action( 'woocommerce_order_status_changed', function( $order_id, $old_status, $new_status, $order ) {
    // $old_status and $new_status are without 'wc-' prefix
}, 10, 4 );

// After payment is complete
add_action( 'woocommerce_payment_complete', function( $order_id ) {
    // Stock already reduced, emails sent, status updated
} );

// New order placed (any payment method)
add_action( 'woocommerce_new_order', function( $order_id, $order ) {
    // Order just created, before payment processing
}, 10, 2 );
```

## Working with Orders (HPOS-Safe)

```php
$order = wc_get_order( $order_id );
if ( ! $order ) {
    return; // Order not found or invalid
}

// Core data
$order->get_id();
$order->get_status();                 // without 'wc-' prefix
$order->get_total();
$order->get_currency();
$order->get_payment_method();
$order->get_payment_method_title();
$order->get_transaction_id();
$order->get_customer_id();
$order->get_date_created();           // WC_DateTime object
$order->get_date_paid();

// Billing
$order->get_billing_first_name();
$order->get_billing_last_name();
$order->get_billing_email();
$order->get_billing_phone();
$order->get_billing_address_1();
$order->get_billing_city();
$order->get_billing_state();
$order->get_billing_postcode();
$order->get_billing_country();
$order->get_formatted_billing_address();

// Shipping
$order->get_shipping_first_name();
$order->get_formatted_shipping_address();
$order->get_shipping_methods();

// Items
foreach ( $order->get_items() as $item_id => $item ) {
    $product    = $item->get_product();
    $product_id = $item->get_product_id();
    $var_id     = $item->get_variation_id();
    $quantity   = $item->get_quantity();
    $subtotal   = $item->get_subtotal();
    $total      = $item->get_total();
    $meta       = $item->get_meta( '_my_item_field' );
}

// Custom meta
$order->get_meta( '_my_custom_field', true );
$order->update_meta_data( '_my_custom_field', $value );
$order->save(); // Always call save()

// Order notes
$order->add_order_note( 'Note visible to admin only.' );
$order->add_order_note( 'Note visible to customer.', 1 ); // customer-facing

// Status changes
$order->update_status( 'processing', 'Payment received.' );
$order->payment_complete( $transaction_id ); // Best for payment confirmation
```

## Querying Orders (HPOS-Safe)

```php
// By meta
$orders = wc_get_orders([
    'meta_key'   => '_my_field',
    'meta_value' => 'target',
    'limit'      => 50,
]);

// By date range
$orders = wc_get_orders([
    'date_created' => '2025-01-01...2025-12-31',
    'status'       => 'completed',
    'limit'        => -1,
]);

// By customer
$orders = wc_get_orders([
    'customer_id' => $user_id,
    'status'      => [ 'processing', 'completed' ],
]);

// By billing email
$orders = wc_get_orders([
    'billing_email' => 'customer@example.com',
]);

// With pagination
$orders = wc_get_orders([
    'limit'  => 20,
    'page'   => 2,
    'return' => 'ids', // Return only IDs for performance
]);
```

## Custom Order Status

```php
// Register status
add_action( 'init', function() {
    register_post_status( 'wc-awaiting-shipment', [
        'label'                     => __( 'Awaiting Shipment', 'my-wc-extension' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Awaiting Shipment <span class="count">(%s)</span>',
            'Awaiting Shipment <span class="count">(%s)</span>',
            'my-wc-extension'
        ),
    ] );
} );

// Add to WooCommerce status list
add_filter( 'wc_order_statuses', function( $statuses ) {
    $new_statuses = [];
    foreach ( $statuses as $key => $label ) {
        $new_statuses[ $key ] = $label;
        if ( 'wc-processing' === $key ) {
            $new_statuses['wc-awaiting-shipment'] = __( 'Awaiting Shipment', 'my-wc-extension' );
        }
    }
    return $new_statuses;
} );

// Enable bulk action
add_filter( 'bulk_actions-edit-shop_order', function( $actions ) {
    $actions['mark_awaiting-shipment'] = __( 'Change status to Awaiting Shipment', 'my-wc-extension' );
    return $actions;
} );
```

## Order Item Meta

```php
// Add meta during checkout
add_action( 'woocommerce_checkout_create_order_line_item', function( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['my_custom_data'] ) ) {
        $item->add_meta_data( '_my_item_data', sanitize_text_field( $values['my_custom_data'] ) );
    }
}, 10, 4 );

// Display in order details
add_filter( 'woocommerce_order_item_get_formatted_meta_data', function( $formatted_meta, $item ) {
    foreach ( $formatted_meta as $key => $meta ) {
        if ( $meta->key === '_my_item_data' ) {
            $meta->display_key = __( 'Custom Data', 'my-wc-extension' );
        }
    }
    return $formatted_meta;
}, 10, 2 );
```
