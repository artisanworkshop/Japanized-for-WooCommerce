# Paidy Security Vulnerability Fix

## Overview

Vulnerability Report from Wordfence:
- **Vulnerability**: Unauthorized modification of order status via unauthenticated access
- **Affected Versions**: All versions up to and including 2.7.17
- **Severity**: High - Unauthenticated attackers can mark WooCommerce orders as processed/completed

## Implemented Fixes

### 1. Webhook Signature Verification

Implemented HMAC-SHA256 signature verification to authenticate webhook notifications from Paidy.

**Implementation**: `paidy_webhook_permission_check()` method

```php
// Get signature from request header
$signature = $request->get_header( 'x-paidy-signature' );

// Calculate HMAC-SHA256 signature using Secret Key
$calculated_signature = hash_hmac( 'sha256', $body, $secret_key );

// Use hash_equals() to prevent timing attacks
if ( ! hash_equals( $calculated_signature, $signature ) ) {
    return new WP_Error(...);
}
```

### 2. IP Address Whitelist (Optional)

Support for IP whitelist as a fallback when signature header is not present.

```php
$allowed_ips = apply_filters( 'paidy_webhook_allowed_ips', array() );
```

**Usage**:
```php
// Add to functions.php or similar
add_filter( 'paidy_webhook_allowed_ips', function( $ips ) {
    return array(
        '203.0.113.0',  // Sample Paidy server IP
        '198.51.100.0',
    );
});
```

### 3. Payment Method Verification

Verify that the order actually uses Paidy payment method before modifying order status via webhook.

```php
if ( $order->get_payment_method() !== 'paidy' ) {
    return new WP_Error(
        'invalid_payment_method',
        __( 'Invalid payment method for this order.', 'woocommerce-for-japan' ),
        array( 'status' => 403 )
    );
}
```

### 4. Permission Callback Changes

Changed both endpoints from `__return_true` to proper verification method:

**Before**:
```php
'permission_callback' => '__return_true',
```

**After**:
```php
'permission_callback' => array( $this, 'paidy_webhook_permission_check' ),
```

## Security Measures

Implemented security measures:

1. ✅ **Cryptographic Signature Verification** - Verify communication authenticity using HMAC-SHA256
2. ✅ **Timing Attack Prevention** - Use `hash_equals()` for secure comparison
3. ✅ **IP Whitelist** - Optional additional defense layer
4. ✅ **Payment Method Verification** - Confirm order uses Paidy payment
5. ✅ **Detailed Logging** - Record unauthorized access attempts

## Required Paidy Configuration

Configure the following in your Paidy dashboard:

1. **Webhook URL**: `https://yoursite.com/wp-json/paidy/v1/order`
2. **Secret Key**: Use the same Secret Key configured in WooCommerce admin
3. **Signature Header**: Include HMAC-SHA256 signature in `x-paidy-signature` header

## Response to Wordfence Recommendations

| Recommendation | Implementation Status | Details |
|---------------|----------------------|---------|
| Capability Check | ✅ Alternative Implementation | Implemented webhook-specific signature verification (standard WordPress authentication is inappropriate) |
| Nonce Verification | ⚠️ Not Required | Signature verification used instead for external service webhooks |
| current_user_can() | ⚠️ Inappropriate | Signature verification used instead as webhooks are not authenticated users |

## Important Notes

### Why Nonce is Not Required

WordPress nonce functionality is **browser session-based** protection and is inappropriate for webhooks because:

1. Paidy server is server-to-server communication, not a browser
2. Does not have WordPress sessions or cookies
3. Cryptographic signature verification provides stronger security

### Why current_user_can() is Inappropriate

1. Webhooks are not authenticated WordPress users
2. Server-to-server communication from external service (Paidy)
3. Industry standard webhook verification method is cryptographic signatures

## Testing Methods

### 1. Testing Valid Webhook

```bash
# Secret Key = "test_secret_key"
# Body = '{"payment_id":"pay_test","order_ref":"123","status":"authorize_success"}'

BODY='{"payment_id":"pay_test","order_ref":"123","status":"authorize_success"}'
SIGNATURE=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "test_secret_key" | cut -d' ' -f2)

curl -X POST https://yoursite.com/wp-json/paidy/v1/order \
  -H "Content-Type: application/json" \
  -H "x-paidy-signature: $SIGNATURE" \
  -d "$BODY"
```

### 2. Testing Invalid Signature (Should Be Rejected)

```bash
curl -X POST https://yoursite.com/wp-json/paidy/v1/order \
  -H "Content-Type: application/json" \
  -H "x-paidy-signature: invalid_signature" \
  -d '{"payment_id":"pay_test","order_ref":"123","status":"authorize_success"}'
```

Expected response: `403 Forbidden - Invalid signature`

### 3. Testing Different Payment Method Order (Should Be Rejected)

If order #123 uses a payment method other than Paidy, the webhook will be rejected.

## Version Information

- **Fixed Version**: 2.7.18 and later
- **Fix Date**: December 31, 2025
- **Fixed By**: GitHub Copilot

## References

- [Paidy API Documentation](https://paidy.com/docs/api/)
- [WordPress REST API Security](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
- [OWASP Webhook Security](https://cheatsheetseries.owasp.org/cheatsheets/Webhook_Security_Cheat_Sheet.html)
