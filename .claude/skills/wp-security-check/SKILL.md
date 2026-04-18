---
name: wp-security-check
description: "Use for security audits of WordPress plugins: nonce validation, capability checks, input sanitization, output escaping, SQL injection prevention, CSRF protection, PCI DSS compliance for payment plugins, and WooCommerce-specific security patterns."
compatibility: "Targets WordPress 6.7+ / WooCommerce 9.0+ (PHP 8.2+). Requires PHPCS with WordPress standards."
---

# WP Security Check

## When to use

Run this skill to audit plugin security:

- Missing nonce validation in form submissions and AJAX handlers
- Insufficient capability checks (`current_user_can`)
- Missing input sanitization
- Missing output escaping (XSS)
- SQL injection (`$wpdb->prepare()` not used)
- CSRF protection
- PCI DSS requirements for payment plugins (no logging or storing card numbers)
- WooCommerce HPOS compliance (no direct `get_post_meta` on orders)
- Missing `permission_callback` on REST API endpoints

## Inputs required

- Plugin root path
- Audit scope (full plugin or specific files)
- Whether payment processing is involved (PCI DSS scope)

## Procedure

### 0) Run static analysis for an overview

```bash
# PHPCS security-related warnings
composer phpcs 2>&1 | grep -E "(nonce|sanitize|escape|wpdb|direct)" | head -50

# PHPStan type safety
composer phpstan 2>&1 | head -50
```

### 1) Check input sanitization

Scan all `$_POST`, `$_GET`, `$_REQUEST`, `$_COOKIE` access:

```bash
grep -r '\$_POST\|\$_GET\|\$_REQUEST\|\$_COOKIE' includes/ src/ --include="*.php" -n
```

**Required patterns:**
- `sanitize_text_field( wp_unslash( $_POST['key'] ) )`
- `absint( $_POST['id'] )`
- `wc_clean( wp_unslash( $_POST['field'] ) )` (WooCommerce)
- `sanitize_email()`, `esc_url_raw()`, `wp_kses_post()`

See: `references/sanitization.md`

### 2) Check output escaping

Scan all HTML output locations:

```bash
grep -r 'echo \|<\?=' includes/ src/ templates/ --include="*.php" -n | grep -v 'esc_\|wp_kses'
```

**Required patterns:**
- `esc_html()` — plain text output
- `esc_attr()` — HTML attribute values
- `esc_url()` — URL output
- `esc_js()` — inside JS strings
- `wp_kses_post()` — when HTML must be allowed

See: `references/escaping.md`

### 3) Check nonce validation

Scan all form submissions and AJAX handlers:

```bash
grep -r 'wp_ajax_\|admin_post_\|woocommerce_checkout_process' includes/ --include="*.php" -n
```

Each handler must include:

```php
// Form submission
check_admin_referer( 'action_name', 'nonce_field' );

// AJAX
check_ajax_referer( 'action_name', 'nonce_field' );

// REST API uses cookie auth automatically, but permission_callback is still required
```

See: `references/nonces-and-csrf.md`

### 4) Check capability checks

Verify `current_user_can()` is called before all admin operations and data mutations:

```bash
grep -r 'update_option\|delete_option\|$wpdb->insert\|$wpdb->update\|$wpdb->delete' includes/ --include="*.php" -n
```

- Admin operations: `current_user_can( 'manage_options' )` or `manage_woocommerce`
- REST API: always verify in `permission_callback` (`__return_true` only for public GET endpoints)
- Order data: `current_user_can( 'edit_shop_orders' )`

### 5) Check SQL injection prevention

```bash
grep -r '$wpdb->' includes/ --include="*.php" -n | grep -v 'prepare\|get_charset_collate\|prefix'
```

Every query containing variables must use `$wpdb->prepare()`:

```php
// BAD
$wpdb->get_results( "SELECT * FROM {$table} WHERE id = {$id}" );

// GOOD
$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
```

See: `references/sql-safety.md`

### 6) Check REST API security

```bash
grep -r 'register_rest_route' includes/ src/ --include="*.php" -n
```

Every route must have a `permission_callback`:

```php
// BAD — security hole for write operations
'permission_callback' => '__return_true',

// GOOD
'permission_callback' => function() {
    return current_user_can( 'manage_woocommerce' );
},
```

See: `references/rest-api-security.md`

### 7) Payment / PCI DSS check (payment plugins only)

```bash
grep -r 'card\|credit\|cvv\|cvc\|expiry\|pan\|log\|error_log\|WC_Logger' includes/ --include="*.php" -n
```

- Never log or store card numbers or CVV in any form
- Tokenize: send card data directly to the payment gateway JS, receive a token
- Always verify webhook signatures
- Protect webhooks with HMAC signatures or IP allowlisting

See: `references/payment-security.md`

### 8) WooCommerce HPOS check

```bash
grep -r 'get_post_meta\|update_post_meta\|delete_post_meta\|get_post(' includes/ --include="*.php" -n
```

No order-related code should use `get_post_meta()`. Use instead:

```php
$order = wc_get_order( $order_id );
$value = $order->get_meta( '_meta_key' );
$order->update_meta_data( '_meta_key', $value );
$order->save();
```

### 9) File upload security (if applicable)

```bash
grep -r 'wp_handle_upload\|move_uploaded_file\|$_FILES' includes/ --include="*.php" -n
```

- Validate MIME type with `wp_check_filetype_and_ext()`
- Use `wp_upload_dir()` for destination path
- Sanitize filenames with `sanitize_file_name()`

## Verification

Security audit pass criteria:

- [ ] PHPCS runs with zero security warnings
- [ ] All form submissions have nonce + capability check
- [ ] All AJAX handlers have nonce + capability check
- [ ] All output uses appropriate escaping
- [ ] All input uses appropriate sanitization
- [ ] All `$wpdb` queries with variables use `prepare()`
- [ ] All REST routes have `permission_callback`
- [ ] HPOS: no `get_post_meta()` on order data
- [ ] (Payment only) No card data logged or stored

## References

- `references/sanitization.md` — input sanitization patterns
- `references/escaping.md` — output escaping patterns
- `references/nonces-and-csrf.md` — nonces and CSRF protection
- `references/sql-safety.md` — SQL injection prevention
- `references/rest-api-security.md` — REST API permission control
- `references/payment-security.md` — PCI DSS requirements for payment plugins
