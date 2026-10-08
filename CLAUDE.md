# Japanized for WooCommerce — Claude Code Instructions

## Plugin Overview

- **Plugin**: Japanized for WooCommerce (`woocommerce-for-japan`)
- **Version**: 2.9.17 | **PHP**: 8.3+ | **WP**: 6.7+ | **WC**: 8.0+
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
dist/                         # Local install ZIPs (gitignored, never shipped)
```

## 開発環境

- ローカル環境: `npx wp-env start` で起動（Docker必須）
- 開発サイト: http://localhost:8890
- テストサイト: http://localhost:8891
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
npx wp-scripts build   # bundle only — use this in feature PRs
npm run start          # watch mode
```
`npm run build` also runs `i18n:build`, which writes a stray `i18n/metaps-for-wc.pot` and rewrites the JSON translations — don't use it in feature PRs.

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
- Every tab posts the whole settings object through the shared `saveSettings()` in `Settings.js` — put save-time normalization there, not in one tab's handler. `updateSetting()` is a functional state update, so two calls in one handler both apply.

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

Admin React strings load from `i18n/woocommerce-for-japan-ja-<md5>.json` (`<md5>` = md5 of the built script's path relative to the plugin, e.g. `assets/js/build/admin/settings.js` → `6d3c6b06…`). When adding strings by hand, add them to this JSON as well as the `.po`/`.mo`.

## WooCommerce HPOS Compatibility

This plugin declares HPOS (High-Performance Order Storage) compatibility. When adding order-related code, use WC order CRUD methods (`$order->get_meta()`, `$order->update_meta_data()`) instead of direct `get_post_meta()` / `update_post_meta()`.

## Testing

```bash
composer test-install   # sets up WP test DB (first time)
composer test           # runs PHPUnit
```

Test files live in `tests/Unit/`. Follow existing patterns; tests use `WP_UnitTestCase`.

CI (`.github/workflows/testing.yml`) is two-tier: a PR runs a light 3-job diagonal (oldest PHP/WP/WC → newest); the full WooCommerce-supported matrix runs on tag push (gating the wp.org deploy), weekly, and on demand:

```bash
gh workflow run testing.yml --repo artisanworkshop/Japanized-for-WooCommerce --ref <branch> -f scope=full
```

Pinned WC versions = latest patch of the minor current 6 and 12 months ago — refresh them about every 6 months, together with the `exclude` list derived from each WC's `Requires at least` (WooCommerce supports WordPress L-1, so older WP × newer WC pairs are impossible installs).

## Payment Gateways

Gateway classes in `includes/gateways/`. Each extends `WC_Payment_Gateway`. Block support classes in `includes/blocks/class-wc-payments-*-blocks-support.php`.

## Common Pitfalls

- `class_exists()` does not use PHP `use` aliases — always pass fully-qualified class name
- Block checkout REST validation runs multiple times per page load (calc_totals, locale calls) — guard accordingly
- `wc4jp-` option prefix ≠ `jp4wc_` function prefix — they are different naming schemes intentionally
- CSS `:has()` selector is used for field ordering — supported in Chrome 105+, Firefox 121+, Safari 15.4+
- `is_order_received_page()` does NOT cover the My Account view-order page — order-display logic must also check `is_wc_endpoint_url( 'view-order' )` (both pages fire `woocommerce_order_details_after_customer_address`)
- Paired output-buffer hooks (ob_start at priority 9 / ob_get_clean at 11) must gate the close on a flag set by the open call — never re-evaluate the page condition, or a filter changing mid-request leaks/steals a buffer
- Transients are unsuitable for state that must outlive a slow external process (e.g. a third-party review taking days–weeks) — the external delay is unbounded, so no TTL is reliably long enough, and on sites with a persistent object cache (Redis/Memcached) transients can also be dropped before the TTL by eviction or a cache flush. Use a non-autoloaded option instead with an application-level TTL check.
- For concurrency-safe keyed storage, don't hold many independent values in a single option updated via read-modify-write (`get_option` → mutate → `update_option`) — concurrent requests can lose each other's writes. Store one option row per key (key embedded in the option name) instead.
- WC core injects Additional Checkout Fields into the admin order edit screen (`CheckoutFieldsAdmin` on `woocommerce_admin_billing_fields` / `woocommerce_admin_shipping_fields`, priority 10) via `array_splice()`, which replaces the injected entries' string keys with numeric ones — detect them by `$field['id']` (e.g. `_wc_billing/jp4wc/...`), never by array key. `show => false` suppresses only the view-mode echo; the edit form ignores `show`, so the field stays editable.
- Docblock `@since` on new code must be the next release version (current Stable tag +1), not the version when work started — Copilot review flags stale values.
- `wp_kses()` does not balance HTML tags — admin-entered HTML echoed into the classic checkout payment box must be `wp_kses( force_balance_tags( $html ), $allowed )` (balance inside kses so PHPCS EscapeOutput stays satisfied). A single stray `</div>` escapes the `.woocommerce-checkout-payment` AJAX fragment and leaves an orphaned place-order row behind on every `update_order_review` → duplicated order buttons. Also balance on save via a `validate_{key}_field()` override (PR #205).
- Use `wp_unslash()` instead of `stripslashes()` for request/setting values — Copilot review flags `stripslashes()`.
- In unit tests, never `markTestSkipped()` when a plugin class is missing — `require_once` the class file and assert `class_exists`, or a loading regression silently drops coverage (Copilot flags this too).
- `WP_REST_Request::get_body()` returns `null` (not `''`) when the request never had a body (e.g. any `GET`) — a `'' === $request->get_body()` guard silently fails to catch this; use `empty( $request->get_body() )` instead. This exact bug recurred 3 times across one PR (Paidy receiver signature work) before all instances were caught.
- `WP_REST_Request::get_params()`/`get_param()` merge the query string on top of the parsed body, and a query value *overrides* a same-named body value — for any HMAC/signature-verified endpoint, read only `get_json_params()`/`get_body_params()` for values the signature actually covers; the query string was never part of what was signed.
- The COD fee settings live in two places: the `wc4jp-extra_charge_*` options written by the React settings screen, and `woocommerce_cod_settings` (plus `jp4wc_tax_class_for_cod`) written by the COD gateway's own settings page. `JP4WC_COD_Fee::get_cod_fee_settings()` prefers a `wc4jp-` option whenever it *exists*, even when empty. Anything that reports these settings for editing must report the values in force (`JP4WC_Settings_API::fill_cod_fee_settings_in_force()`), because the settings screen saves back every value it was given — reporting them as empty wiped the fee on the first save (#218).
- Not every gateway module's options use the `wc4jp-` prefix — a payment gateway can have its own established naming scheme distinct from both `wc4jp-` and `jp4wc_` (e.g. Paidy's `paidy_*` options, present since v2.7.0). Check sibling options in the same file before flagging a missing `wc4jp-` prefix as a bug.
- Before running `msgmerge --update` on `i18n/*.po` inside a feature/fix PR, diff it against the current `.pot` first. A `.po` that hasn't been resynced in a while will also prune long-obsolete entries and reflow the whole file on merge, bloating an unrelated PR by 1000+ lines — add only the new/changed msgids by hand instead and leave a full resync for a dedicated POT-regen PR.
- A user-visible default that gets saved or used as a value (e.g. a select option's value) must not come from PHP `__()` at request time: a WordPress.org language pack beats the bundled `i18n/*.mo` and has no translation for a new string until it's translated on translate.wordpress.org, and block checkout fields are registered on `woocommerce_init` (init 0), before `load_plugin_textdomain()` (init 1). Script translations try the bundled JSON before the language pack, so save such defaults from the React settings app (`withMorningLabel()` in `Settings.js`, PR #222).
- WooCommerce Store API's checkout POST calculates cart fees (`woocommerce_cart_calculate_fees`, via `calculate_totals()`) *before* it sets the order's final payment method from the request (`woocommerce_store_api_checkout_update_order_from_request` fires after) — a fee decided from session state during that calculation can be for the wrong method. Calculate from the request's own `payment_method` (see below) and keep a post-hoc check in that later action only as a safety net.
- Never keep the shopper's selected payment method in a session key of our own. Since WooCommerce 9.8 the Checkout block pushes a payment-method change itself (`PUT /wc/store/v1/checkout?__experimental_calc_totals=true`, debounced ~1.5 s), writes `chosen_payment_method` and returns recalculated totals. A parallel key updated through `extensionCartUpdate` races with that request on any server slower than the debounce: each calculates from the other's unsaved value, and because the WooCommerce session is saved as one row, the later save overwrites the earlier one (#215). Write `chosen_payment_method` and read only that.
- To calculate a fee for the payment method a Store API checkout request is submitting, capture the request's `payment_method` in `rest_request_before_callbacks` (see `JP4WC_COD_Fee_Handler::jp4wc_capture_checkout_payment_method()`); it is the only place to see it before the totals are calculated. Scope what you capture to that one request — save the previous state there and restore it in `rest_request_after_callbacks` — because a batch request serves several requests in one process and a route may dispatch a nested request while it runs. Do not re-validate the value against `get_available_payment_gateways()` in the middle of the totals calculation: WooCommerce validates it for the same request and rejects the request, while a mid-calculation check sees a cart total of 0 and can disagree.
- WooCommerce registers every Store API route under both `wc/store` and `wc/store/v1`, and WordPress matches REST routes case-insensitively. Code that recognises a Store API route by its path must accept both namespaces and any case (`#^/wc/store(?:/v1)?/checkout(?:/|$)#i`); matching only `/wc/store/v1/...` silently skips the unversioned alias.
- Unit tests that call a hook callback directly do not prove the hook is registered. For Store API behaviour, also dispatch real requests through `rest_get_server()->dispatch()` (see `tests/Unit/test-jp4wc-cod-fee-store-api.php`): with `woocommerce_store_api_disable_nonce_check` and `woocommerce_is_checkout` filtered to true, guest checkout enabled and a virtual product, a full place-order POST works in the unit environment. CI runs WooCommerce 10.2.2 / 10.6.2 / latest — on 10.5 and 10.6 (and older) the pay-for-order route (`/checkout/<id>`) throws a TypeError unless the request carries `shipping_address`.
- WooCommerce lets a Checkout block draft order (`checkout-draft`) be paid like a pending one — through the Store API pay-for-order route (`/wc/store/v1/checkout/<id>`) and the classic order-pay page — and neither recalculates cart fees. A surcharge decided in `woocommerce_cart_calculate_fees` therefore never reaches an order paid that way; the draft must be placed from `/checkout` for the fee to exist. `JP4WC_COD_Fee_Handler` rejects COD/COD2 for drafts on both paths. The same hook (`woocommerce_store_api_checkout_update_order_from_request`) fires for both checkout routes, so tell them apart by `$request->get_route()`.
- When rebasing a PR onto another merged PR that changed the same hooks, check past the conflict markers and past green tests. A line both sides kept can land inside one side of the conflict *and* again right after the markers (rebasing #217 onto #216 registered `jp4wc_reject_stale_gateway_fee` twice that way), and a test can keep passing for the wrong reason when it sets up state the other PR removed (#217's guard-order test still set the dropped `jp4wc_gateway_id` key). Re-run the rebased PR's key tests with its change reverted to prove they still fail.
- A custom action fired synchronously from an early `init`-priority callback (e.g. version-upgrade detection at priority 5) will never reach a listener only registered when a class is lazily instantiated at a later `init` priority (e.g. via its constructor, hooked at the default priority 10) — register such listeners at file-load time instead.
- `wc_get_orders()`'s `customer_id` must be a real numeric ID — a non-numeric string (e.g. a synthetic guest identifier) coerces to `0`, meaning "no assigned customer," silently pulling in unrelated orders instead of matching none.
- Client-controlled input used as an array offset (`$array[$input]`) needs an explicit `is_string()`/`is_int()` check, not just `empty()` — a non-empty array/object bypasses `empty()` and crashes with a TypeError instead of being safely rejected.
- `WC()->payment_gateways` can be `null` depending on init order — guard with `WC()->payment_gateways ? WC()->payment_gateways->get_available_payment_gateways() : array()` (established pattern in `class-jp4wc-cod-fee.php`).
- `WC_Order::needs_payment()` is `false` for a 0-total order (e.g. fully covered by a coupon), and WC core sets `payment_method` to `''` for such orders regardless of any gateway selected earlier in the session — code comparing against an order's payment method must exempt orders where `needs_payment()` is false.
- Don't take a readme "External Services" disclosure's stated activation condition at face value — verify against the actual code. This plugin shipped inaccurate claims 3 times in one release cycle: a bundled fallback API key that fires regardless of merchant configuration, a data-collecting intermediary domain distinct from the payment API domain, and a tracking override flag that bypasses the opt-in setting entirely.
- `.gitignore` does not keep a path out of the plugin package — the release ZIP and the WordPress.org deploy copy the tree with `rsync --exclude-from=.distignore`, so a local-only top-level directory (e.g. `dist/`) must also be listed in `.distignore`, plus `.phpcs.xml.dist` if it can hold PHP. Don't package from `git archive` either: `.gitattributes` export-ignore drops `src/`, `package.json` and `webpack.config.js`, so the export cannot be built.
- Paidy IDs (`pay_` / `cap_` / `ref_`) use the base64url alphabet — letters, digits, `_` **and `-`** — and Paidy documents only the prefix, not a charset or length. Any format guard tighter than `^pay_[A-Za-z0-9_-]+$` silently blocks real payments (the thank-you page and the webhook both leave the order pending until the stock hold cancels it); this regressed twice (`_` missed until 2.9.14, `-` until #223). Never pin a length; when a new character shows up, extend the class and the providers in `tests/Unit/test-paidy-payment-id-format.php` together.
- PCRE `$` also matches just before a trailing `\n`, so `/^pay_[A-Za-z0-9_-]+$/` accepts `"pay_abc\n"`. A format guard that exists to keep input out of a URL/path must end with `$/D` (or use `\z`); `rawurlencode()` made the slip harmless here, but the comment claimed whitespace was rejected.
- `.phpcs.xml.dist` excludes `/tests/`, so `composer lint` and CI never check test files. Run `vendor/bin/phpcs --standard=WordPress tests/Unit/<file>.php` (and `phpcbf`) by hand on a new or changed test — array `=>` alignment is the usual miss.
- `composer test` ends with `test-stop`, which removes the `jp4wc-mysql-test` container, so a second `composer test` fails to connect. Between runs call `vendor/bin/phpunit` directly (the WP test lib under `sys_get_temp_dir()` and the container stay up), and run `composer test-install` before each `composer test`; macOS also purges the temp dir, so expect to reinstall after a while.
