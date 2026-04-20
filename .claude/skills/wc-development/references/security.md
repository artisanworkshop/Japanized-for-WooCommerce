# Security for WooCommerce Extensions

## Input Sanitization

```php
// Text fields
$value = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );

// Email
$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

// Integer
$count = absint( $_POST['count'] ?? 0 );

// URL
$url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

// Textarea (preserves line breaks)
$text = sanitize_textarea_field( wp_unslash( $_POST['textarea'] ?? '' ) );

// HTML (allow safe tags)
$html = wp_kses_post( wp_unslash( $_POST['html'] ?? '' ) );

// Array of values
$items = array_map( 'sanitize_text_field', wp_unslash( $_POST['items'] ?? [] ) );
```

## Output Escaping

```php
echo esc_html( $text );          // Plain text in HTML context
echo esc_attr( $value );         // HTML attribute values
echo esc_url( $url );            // URLs in href/src
echo esc_js( $js_string );      // Inline JavaScript strings
echo wp_kses_post( $html );      // Allow safe HTML tags (post content)

// In JavaScript data
wp_localize_script( 'my-script', 'myData', [
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'my_action' ),
] );
```

## Nonce Verification

```php
// Form nonce
wp_nonce_field( 'my_extension_save', 'my_extension_nonce' );

// Verify on submission
if ( ! isset( $_POST['my_extension_nonce'] )
    || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['my_extension_nonce'] ) ), 'my_extension_save' )
) {
    wp_die( esc_html__( 'Security check failed.', 'my-wc-extension' ) );
}

// AJAX nonce
check_ajax_referer( 'my_ajax_action', 'nonce' );

// REST API nonce (for logged-in users)
// Automatically handled by WordPress REST API cookie auth
```

## Capability Checks

```php
// Admin operations
if ( ! current_user_can( 'manage_woocommerce' ) ) {
    wp_die( esc_html__( 'Unauthorized.', 'my-wc-extension' ) );
}

// Order-specific operations
if ( ! current_user_can( 'edit_shop_orders' ) ) {
    return;
}

// Product editing
if ( ! current_user_can( 'edit_products' ) ) {
    return;
}

// Customer self-service (account pages)
if ( ! is_user_logged_in() || get_current_user_id() !== $order->get_customer_id() ) {
    wp_die( esc_html__( 'You do not have permission to view this order.', 'my-wc-extension' ) );
}
```

## Database Queries

```php
global $wpdb;

// Always use prepared statements
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}my_table WHERE status = %s AND created > %s ORDER BY id DESC LIMIT %d",
        $status,
        $date,
        $limit
    )
);

// Insert
$wpdb->insert(
    $wpdb->prefix . 'my_table',
    [ 'name' => $name, 'value' => $value, 'created' => current_time( 'mysql' ) ],
    [ '%s', '%s', '%s' ]
);

// Update
$wpdb->update(
    $wpdb->prefix . 'my_table',
    [ 'value' => $new_value ],
    [ 'id' => $id ],
    [ '%s' ],
    [ '%d' ]
);
```

## Payment Security (PCI DSS)

### Non-passthrough compliance (token-based)

For payment gateways, never handle raw card data on the server:

```php
// ❌ NEVER do this
$card_number = $_POST['card_number'];
$cvv = $_POST['cvv'];

// ✅ Use token from client-side tokenization
$payment_token = sanitize_text_field( wp_unslash( $_POST['payment_token'] ?? '' ) );

// ✅ Or redirect to payment provider's hosted page
return [
    'result'   => 'success',
    'redirect' => $provider->get_hosted_payment_url( $order ),
];
```

### Token-based flow
1. Client-side: JavaScript calls payment provider's SDK to tokenize card data
2. Token is submitted with checkout form (not card data)
3. Server-side: Use token to charge via provider's API
4. Store only the token reference, never raw card data

### Data storage rules
- Never log card numbers, CVV, or full track data
- Never store raw card data in order meta, logs, or database
- Store only: last 4 digits, card type, expiry, token reference
- API keys/secrets: use `wp_options` with encryption or environment variables

## Webhook Verification

```php
public function handle_webhook() {
    $payload   = file_get_contents( 'php://input' );
    $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

    // HMAC verification
    $expected = hash_hmac( 'sha256', $payload, $this->webhook_secret );

    if ( ! hash_equals( $expected, $signature ) ) {
        status_header( 403 );
        wp_die( 'Invalid signature' );
    }

    // Prevent replay attacks
    $timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? 0;
    if ( abs( time() - intval( $timestamp ) ) > 300 ) {
        status_header( 403 );
        wp_die( 'Request expired' );
    }

    $data = json_decode( $payload, true );
    // Validate event type and process...

    status_header( 200 );
    exit( 'OK' );
}
```

## Sensitive Data in Logs

```php
// Use WooCommerce logger
$logger = wc_get_logger();
$logger->info( 'Payment processed for order #' . $order_id, [ 'source' => 'my-payment' ] );

// ❌ Never log sensitive data
$logger->debug( 'API response: ' . wp_json_encode( $response ) ); // May contain tokens

// ✅ Sanitize before logging
$safe_response = $response;
unset( $safe_response['api_key'], $safe_response['token'] );
$logger->debug( 'API response: ' . wp_json_encode( $safe_response ), [ 'source' => 'my-payment' ] );
```

## CSRF in WooCommerce Checkout

WooCommerce provides its own checkout nonce:

```php
// In shortcode checkout: woocommerce-process-checkout-nonce
// In block checkout: handled by Store API authentication

// Verify in payment gateway:
if ( ! wp_verify_nonce(
    sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ?? '' ) ),
    'woocommerce-process_checkout'
) ) {
    // This is already handled by WooCommerce core
}
```

## File Upload Security

```php
if ( ! empty( $_FILES['my_file'] ) ) {
    // Verify file type
    $allowed = [ 'image/jpeg', 'image/png', 'application/pdf' ];
    $file_type = wp_check_filetype_and_ext(
        $_FILES['my_file']['tmp_name'],
        $_FILES['my_file']['name']
    );

    if ( ! in_array( $file_type['type'], $allowed, true ) ) {
        return new WP_Error( 'invalid_type', 'File type not allowed.' );
    }

    // Use WordPress upload handler
    $upload = wp_handle_upload( $_FILES['my_file'], [ 'test_form' => false ] );
}
```
