# Japanized for WooCommerce — Claude Code Instructions

## Plugin Overview

- **Plugin**: Japanized for WooCommerce (`woocommerce-for-japan`)
- **Version**: 2.9.8 | **PHP**: 8.3+ | **WP**: 6.7+ | **WC**: 8.0+
- **Text Domain**: `woocommerce-for-japan`
- **Prefix**: `jp4wc_` (functions), `JP4WC_` (constants), `JP4WC` (classes)
- **Main files**: `woocommerce-for-japan.php`, `class-jp4wc.php`

## Directory Structure

```
woocommerce-for-japan.php     # Plugin entry point
class-jp4wc.php               # Main JP4WC class (singleton)
includes/
  admin/                      # Admin settings pages
  blocks/                     # Block checkout integrations (IntegrationInterface)
  gateways/                   # Payment gateways
  jp4wc-framework/            # Shared framework utilities
  class-jp4wc-*.php           # Feature classes
src/js/                       # React source (admin settings UI)
assets/js/                    # Built JS
i18n/                         # POT/PO/JSON translation files
tests/                        # PHPUnit tests
```

## 開発環境

- ローカル環境: `npx wp-env start` で起動（Docker必須）
- 開発サイト: http://localhost:8888
- テストサイト: http://localhost:8889
- WP-CLI: `npx wp-env run cli wp <command>`

## Coding Standards

Follow **WordPress Coding Standards** (WPCS). Run before committing:

```bash
composer lint          # phpcs check
composer format        # phpcbf auto-fix
composer test          # phpunit
```

JS/CSS build:
```bash
npm run build          # production build
npm run start          # watch mode
```

### Key Rules
- All globals must use `jp4wc_` / `JP4WC_` / `JP4WC` prefix
- Escape all output: `esc_html__()`, `esc_attr()`, `wp_kses_post()`, etc.
- Sanitize all input: `sanitize_text_field()`, `absint()`, etc.
- Use nonces for all forms and AJAX requests
- Never use `extract()` or short PHP tags
- Settings stored as WP options with `wc4jp-` prefix (e.g. `wc4jp-yomigana`)

## Block Checkout Integration

### Boot Sequence
1. `plugins_loaded` (priority 10) → `JP4WC::instance()` → `JP4WC::init()`
2. `init()` registers `woocommerce_blocks_loaded` + `init` (priority 1) fallback
3. First hook to fire → `jp4wc_blocks_support()` (static `$done` guard prevents double-run)
4. `jp4wc_blocks_support()` → adds `woocommerce_init` at priority 5
5. `woocommerce_init` (priority 5) → instantiate integration → `register_checkout_fields()` → `initialize()`

### IntegrationInterface
When implementing `Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface`:
- **Always use the fully-qualified class name** in `class_exists()` checks — PHP's `class_exists()` does NOT resolve `use` aliases:
  ```php
  // CORRECT
  if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) { return; }

  // WRONG — always returns false, file exits early
  use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
  if ( ! class_exists( 'IntegrationInterface' ) ) { return; }
  ```

### Additional Checkout Fields (WC 9.3+)
- Yomigana fields: `location: 'address'` → billing + shipping address forms
- Delivery fields: `location: 'order'` → order information section
- Key prefixes in saved meta: `_wc_billing/` and `_wc_shipping/`
- In `validate_additional_field`, skip validation during `calc_totals` REST calls:
  ```php
  $is_calc_totals = isset( $_GET['__experimental_calc_totals'] ); // phpcs:ignore
  $is_locale_rest = defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['_locale'] ); // phpcs:ignore
  if ( $is_calc_totals || $is_locale_rest ) { return $is_valid; }
  ```

## Critical: Never Manipulate React-Managed DOM

WooCommerce blocks checkout renders via React. **Never** use:
- `insertAdjacentElement`, `appendChild`, `removeChild` on block elements
- `MutationObserver` to move block elements

This causes an infinite re-render loop. Use **CSS `order` property** for visual reordering:
```php
// In enqueue_block_styles() — use wp_add_inline_style()
wp_add_inline_style( 'handle', '.element { order: 3; }' );
```

`assets/js/checkout-blocks-jp4wc.js` is intentionally a comment-only stub — do not add DOM manipulation there.

## Settings Architecture

- Options stored in WordPress options table, prefix `wc4jp-`
- Checkboxes: `'1'` (enabled) or `''` (disabled)
- REST API: `GET/POST /jp4wc/v1/settings`
- Admin UI: React app at `src/js/jp4wc/admin/settings/`

## i18n

All user-facing strings must be wrapped with translation functions using domain `woocommerce-for-japan`:
```php
__( 'String', 'woocommerce-for-japan' )
esc_html__( 'String', 'woocommerce-for-japan' )
```

Regenerate translation files after adding strings:
```bash
npm run make-pot    # requires wp-env
npm run make-json
```

## WooCommerce HPOS Compatibility

This plugin declares HPOS (High-Performance Order Storage) compatibility. When adding order-related code, use WC order CRUD methods (`$order->get_meta()`, `$order->update_meta_data()`) instead of direct `get_post_meta()` / `update_post_meta()`.

## Testing

```bash
composer test-install   # sets up WP test DB (first time)
composer test           # runs PHPUnit
```

Test files live in `tests/Unit/`. Follow existing patterns; tests use `WP_UnitTestCase`.

## Payment Gateways

Gateway classes in `includes/gateways/`. Each extends `WC_Payment_Gateway`. Block support classes in `includes/blocks/class-wc-payments-*-blocks-support.php`.

## Common Pitfalls

- `class_exists()` does not use PHP `use` aliases — always pass fully-qualified class name
- Block checkout REST validation runs multiple times per page load (calc_totals, locale calls) — guard accordingly
- `wc4jp-` option prefix ≠ `jp4wc_` function prefix — they are different naming schemes intentionally
- CSS `:has()` selector is used for field ordering — supported in Chrome 105+, Firefox 121+, Safari 15.4+
