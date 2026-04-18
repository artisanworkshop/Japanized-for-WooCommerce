---
name: wc-development
description: >
  WooCommerce extension development skill covering HPOS, payment gateways, Blocks/StoreAPI integration,
  order management, product data, shipping methods, REST API, and security. Use this skill whenever
  building, modifying, debugging, or reviewing a WooCommerce extension plugin. Trigger on keywords like
  "WooCommerce plugin", "WC extension", "payment gateway", "HPOS", "order meta", "wc_get_orders",
  "checkout block", "Store API", "WC_Payment_Gateway", "shipping method", "product type",
  "WooCommerce REST API", or any WooCommerce-specific development task. Also trigger when the user
  references WooCommerce hooks, filters, or classes. This skill targets WooCommerce 10.x (current stable: 10.6)
  on WordPress 6.6+ / 7.0+.
compatibility: >
  WooCommerce 9.0–10.6+ on WordPress 6.7–7.0+. PHP 8.2+. HPOS required for all new extensions.
  Some workflows use WP-CLI with WooCommerce commands.
---

# WooCommerce Extension Development

## When to use

Use this skill for any WooCommerce extension work:

- Building or modifying **payment gateways** (`WC_Payment_Gateway` subclass)
- Building or modifying **shipping methods** (`WC_Shipping_Method` subclass)
- **HPOS** compatibility (mandatory for all new extensions)
- **Block-based checkout** integration (payment methods, cart extensions, Store API)
- **Order management** (status transitions, custom statuses, meta, queries)
- **Product data** (custom fields, product types, variations, attributes)
- **REST API** extensions (custom endpoints, schema extensions)
- **Security** (nonce, sanitization, PCI DSS, webhook verification)
- Debugging WooCommerce-specific issues

## Inputs required

- Plugin main file location (with `WC requires at least:` header)
- WooCommerce version target (10.x recommended; 9.0 minimum for HPOS)
- Whether the store uses **block-based checkout** or **shortcode checkout**
- Target market/locale (affects tax, payment, compliance)

## Procedure

### 0) Triage: detect WooCommerce context

Run the detection script to scan the project:

```bash
node scripts/detect_wc_extension.mjs /path/to/plugin
```

This returns JSON with: HPOS declaration status, incompatible patterns, gateway classes,
Blocks integration, and actionable issues. Alternatively, manually check:

```bash
grep -r "WC requires at least" *.php
grep -r "custom_order_tables" *.php
grep -r "Automattic\\\\WooCommerce\\\\Blocks" *.php
```

### 1) Plugin bootstrap and WooCommerce dependency

- Declare WooCommerce as a required dependency in plugin headers
- Gate all WC code behind WooCommerce active check
- Declare HPOS compatibility via `before_woocommerce_init` hook
- Register Blocks integration via `woocommerce_blocks_loaded` hook
- Initialize on `plugins_loaded` priority 10

See: `references/bootstrap.md`

### 2) HPOS compatibility (mandatory)

All new extensions MUST support HPOS:

- Use `$order->get_meta()` / `$order->update_meta_data()` + `$order->save()`
- Use `wc_get_orders()` instead of `WP_Query` for orders
- Use `wc_get_page_screen_id( 'shop-order' )` for meta box screen detection
- Declare compatibility via `FeaturesUtil::declare_compatibility( 'custom_order_tables' )`
- Never use `$order->id` (deprecated); use `$order->get_id()`
- Never query `wp_posts` / `wp_postmeta` for order data

See: `references/hpos.md`

### 3) Payment gateway implementation

- Extend `WC_Payment_Gateway` (or `WC_Payment_Gateway_CC`)
- Implement: `process_payment()`, `init_form_fields()`, `init_settings()`
- Handle both shortcode and **block-based checkout** via Blocks integration
- Implement refund support via `process_refund()` if applicable
- For tokenization, use `WC_Payment_Token` API
- Never store raw card data; use token-based processing

See: `references/payment-gateway.md`

### 4) WooCommerce Blocks integration

- Register payment method via `PaymentMethodRegistry` in `woocommerce_blocks_loaded`
- Create JS implementing `PaymentMethodInterface`
- Handle `payment_data` from Store API in server-side `process_payment()`
- Extend Store API schemas via `ExtendSchema`
- Use `woocommerce_store_api_register_update_callback` for frontend-to-server data flow
- Declare cart_checkout_blocks compatibility via `FeaturesUtil`

See: `references/blocks-integration.md`

### 5) Order lifecycle and status management

- Use `woocommerce_order_status_{from}_to_{to}` hooks for transitions
- Use `$order->payment_complete()` to mark paid (triggers stock, emails, status)
- Add order notes via `$order->add_order_note()`
- Custom statuses: `wc_register_order_status()` + `wc_order_statuses` filter
- Batch queries: `wc_get_orders()` with meta queries

See: `references/order-lifecycle.md`

### 6) Product handling

- Use `wc_get_product()` (returns correct subclass: Simple, Variable, etc.)
- Variable products: `WC_Product_Variable` → `WC_Product_Variation`
- Custom product types: extend `WC_Product` and register via `woocommerce_product_class`
- Custom fields: `woocommerce_product_options_general_product_data` and related hooks
- Product meta: `$product->get_meta()` / `$product->update_meta_data()` + `$product->save()`

See: `references/products.md`

### 7) Shipping method development

- Extend `WC_Shipping_Method`
- Implement `calculate_shipping()` to add rates via `$this->add_rate()`
- Register via `woocommerce_shipping_methods` filter
- Handle shipping zones, instances, and method settings
- New in 10.6: `woocommerce_shipping_prices_include_tax` filter for EU compliance

See: `references/shipping.md`

### 8) REST API extensions

- Extend existing endpoints via `woocommerce_rest_pre_insert_{object}` filters
- Register custom endpoints using `WC_REST_Controller` base class
- Respect WooCommerce authentication (API keys, OAuth 1.0a)
- New in 10.5: experimental REST API caching feature

See: `references/rest-api.md`

### 9) Security

- Never log or store raw card data (PCI DSS)
- Use WooCommerce's nonce for checkout: `woocommerce-process-checkout-nonce`
- Validate `$order->get_payment_method()` matches gateway ID before processing
- Sanitize all webhook/callback payloads from external providers
- All user input: `sanitize_*()` on input, `esc_*()` on output

See: `references/security.md`

## Verification

- Plugin activates with WooCommerce active; admin notice if WC missing
- HPOS compatibility declared; no direct `wp_posts`/`wp_postmeta` queries for orders
- Payment gateway appears in WooCommerce > Settings > Payments
- Gateway works in both shortcode and block-based checkout
- Order meta read/write works with HPOS enabled and disabled
- WooCommerce status page shows no compatibility warnings
- WordPress 7.0 compatibility (admin CSS changes, new block editor)

## Failure modes

| Symptom | Likely Cause |
|---------|-------------|
| Gateway not in settings | `woocommerce_payment_gateways` filter not registered |
| HPOS warning on status page | Missing `FeaturesUtil::declare_compatibility()` |
| Block checkout missing payment fields | Missing Blocks integration class or JS |
| Order meta not saving | Using `update_post_meta()` instead of `$order->update_meta_data()` + `$order->save()` |
| Admin styling broken on WP 7.0 | CSS not scoped; relying on deprecated WP admin styles |

See: `references/debugging.md`

## Escalation

- [WooCommerce Developer Docs](https://developer.woocommerce.com/docs/)
- [WooCommerce REST API](https://developer.woocommerce.com/docs/rest-api/)
- [HPOS Documentation](https://developer.woocommerce.com/docs/hpos/)
- [Blocks Payment Methods](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce-blocks/docs/third-party-developers/extensibility/checkout-payment-methods/)
- [WooCommerce Developer Blog](https://developer.woocommerce.com/)
