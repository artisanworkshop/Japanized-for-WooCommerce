# Payment Security (PCI DSS)

## Cardinal rules — never violate

1. **NEVER log raw card numbers, CVV, or expiry dates** — even in debug mode
2. **NEVER store card data** in database or transients
3. **NEVER transmit card data through your server** — use tokenization
4. **NEVER disable SSL** for payment pages

## Token-based payment flow

```
Browser → Gateway JS SDK (collects card data) → Gateway Server → Token
Browser → Your plugin (sends token only) → Your Server → Gateway API (uses token)
```

```javascript
// Example: Collect card via gateway JS, receive token
gatewaySDK.tokenize(cardElement).then((token) => {
    document.getElementById('payment-token').value = token.id;
    form.submit();
});
```

```php
// PHP side: never see raw card data
$token = sanitize_text_field( wp_unslash( $_POST['payment_token'] ?? '' ) );
// Send $token to gateway API — never card numbers
```

## Webhook security

```php
// Always verify webhook signatures
add_action( 'woocommerce_api_plugin_name', function() {
    $raw_body  = file_get_contents( 'php://input' );
    $signature = sanitize_text_field( $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '' );
    $secret    = get_option( 'plugin_name_webhook_secret' );

    $expected = hash_hmac( 'sha256', $raw_body, $secret );

    if ( ! hash_equals( $expected, $signature ) ) {
        status_header( 401 );
        exit;
    }

    // Process webhook...
} );
```

## Logging (WC_Logger, not error_log)

```php
$logger  = wc_get_logger();
$context = [ 'source' => 'plugin-name' ];

// OK — log transaction ID, order ID
$logger->info( "Payment completed for order #{$order_id}, txn: {$transaction_id}", $context );

// NG — never log card-related data
// $logger->debug( "Card: {$card_number}" );  // NEVER
// $logger->debug( json_encode( $payment_data ) );  // Risk if $payment_data has card info
```

## HTTPS enforcement

```php
// Ensure checkout is always HTTPS
add_filter( 'woocommerce_force_ssl_checkout', '__return_true' );

// Enqueue payment scripts only on secure pages
add_action( 'wp_enqueue_scripts', function() {
    if ( is_checkout() && is_ssl() ) {
        wp_enqueue_script( 'plugin-name-payment', ... );
    }
} );
```

## Sensitive option storage

```php
// Store API keys encrypted or at minimum not in plain text in logs
// Use get_option with a capability check when retrieving
function plugin_name_get_api_key(): string {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return '';
    }
    return (string) get_option( 'plugin_name_api_key', '' );
}
```
