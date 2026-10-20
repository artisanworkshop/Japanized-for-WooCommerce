# Japanized for WooCommerce — Claude Code Instructions

## Plugin Overview

- **Plugin**: Japanized for WooCommerce (`woocommerce-for-japan`)
- **Version**: 2.9.18 | **PHP**: 8.3+ | **WP**: 6.7+ | **WC**: 8.0+
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
  fixtures/security-feed/     # JSON contract (schemas + samples) shared with plugins/artws-security-hub
plugins/                      # Server-side plugins, never shipped (own composer/lint/tests/CI)
  artws-security-hub/         # wc.artws.info: vulnerability feed + free security check API
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
composer test-local    # phpunit (see Testing)
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
composer test-local                     # sets up on first run, then runs PHPUnit
composer test-local -- --filter Paidy   # arguments after -- go to phpunit
```

`composer test-local` (`bin/test-local.sh`) keeps its own environment, so other projects' `install-wp-tests.sh` runs can't repoint it at their database:

- WordPress, the WP test library and WooCommerce live in `~/.cache/jp4wc-tests/` (`JP4WC_TEST_ENV_DIR`); `rm -rf` it to reinstall the latest versions.
- MySQL runs in the `jp4wc-phpunit-db` container on `127.0.0.1:10104` (`JP4WC_TEST_DB_PORT`; 10104 is the spare +4 of this repo's dev-env slot 10). It is left running between runs. If the port is taken, the script names the owner and stops before connecting; it also checks that the server answering is that container.
- `wp-tests-config.php` is rewritten on every run.

`composer test-install` + `composer test` remain for the shared `$TMPDIR` layout on port 3306; CI uses neither (it runs `bin/install-wp-tests.sh`).

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
- Docblock `@since` on new code, and any "since/before x.y.z" comment, must be the version of the first release tag that will contain it — the last pushed tag +1. Once a bump PR merges, readme `Stable tag` already names that unreleased version, so "Stable tag +1" is one too high. Whatever is on main when the tag is pushed ships: a PR meant for the next version that merges first needs a changelog line and its version mentions corrected (#245 → #246; `release-bump check` reports it as MISSING). Copilot review flags stale values.
- `wp_kses()` does not balance HTML tags — admin-entered HTML echoed into the classic checkout payment box must be `wp_kses( force_balance_tags( $html ), $allowed )` (balance inside kses so PHPCS EscapeOutput stays satisfied). A single stray `</div>` escapes the `.woocommerce-checkout-payment` AJAX fragment and leaves an orphaned place-order row behind on every `update_order_review` → duplicated order buttons. Also balance on save via a `validate_{key}_field()` override (PR #205).
- Use `wp_unslash()` instead of `stripslashes()` for request/setting values — Copilot review flags `stripslashes()`.
- In unit tests, never `markTestSkipped()` when a plugin class is missing — `require_once` the class file and assert `class_exists`, or a loading regression silently drops coverage (Copilot flags this too).
- `WP_REST_Request::get_body()` returns `null` (not `''`) when the request never had a body (e.g. any `GET`) — a `'' === $request->get_body()` guard silently fails to catch this; use `empty( $request->get_body() )` instead. This exact bug recurred 3 times across one PR (Paidy receiver signature work) before all instances were caught.
- `WP_REST_Request::get_params()`/`get_param()` merge the query string on top of the parsed body, and a query value *overrides* a same-named body value — for any HMAC/signature-verified endpoint, read only `get_json_params()`/`get_body_params()` for values the signature actually covers; the query string was never part of what was signed.
- The COD fee settings live in two places: the `wc4jp-extra_charge_*` options written by the React settings screen, and `woocommerce_cod_settings` (plus `jp4wc_tax_class_for_cod`) written by the COD gateway's own settings page. `JP4WC_COD_Fee::get_cod_fee_settings()` prefers a `wc4jp-` option whenever it *exists*, even when empty. Anything that reports these settings for editing must report the values in force (`JP4WC_Settings_API::fill_cod_fee_settings_in_force()`), because the settings screen saves back every value it was given — reporting them as empty wiped the fee on the first save (#218).
- Not every gateway module's options use the `wc4jp-` prefix — a payment gateway can have its own established naming scheme distinct from both `wc4jp-` and `jp4wc_` (e.g. Paidy's `paidy_*` options, present since v2.7.0). Check sibling options in the same file before flagging a missing `wc4jp-` prefix as a bug.
- Before running `msgmerge --update` on `i18n/*.po` inside a feature/fix PR, diff it against the current `.pot` first. A `.po` that hasn't been resynced in a while will also prune long-obsolete entries and reflow the whole file on merge, bloating an unrelated PR by 1000+ lines — add only the new/changed msgids by hand instead and leave a full resync for a dedicated POT-regen PR. `python3 ~/.claude/skills/wp-i18n/scripts/po-add-entry.py` does this for a new PHP string (finds the reference, matches each file's wrapping, and refuses unless the rebuilt `.mo` differs from the old one by exactly the new entry).
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
- `WC()->payment_gateways` can be `null` depending on init order — guard with `WC()->payment_gateways ? WC()->payment_gateways->get_available_payment_gateways() : array()` (established pattern in `class-jp4wc-cod-fee.php`). The *property* can be null; the method `WC()->payment_gateways()` always returns the instance, loading the gateways if needed.
- `WC_Order::needs_payment()` is `false` for a 0-total order (e.g. fully covered by a coupon), and WC core sets `payment_method` to `''` for such orders regardless of any gateway selected earlier in the session — code comparing against an order's payment method must exempt orders where `needs_payment()` is false.
- Don't take a readme "External Services" disclosure's stated activation condition at face value — verify against the actual code. This plugin shipped inaccurate claims 3 times in one release cycle: a bundled fallback API key that fires regardless of merchant configuration, a data-collecting intermediary domain distinct from the payment API domain, and an install/update send that bypassed the usage-tracking opt-in (the tracker was removed in #239).
- `.gitignore` does not keep a path out of the plugin package — the release ZIP and the WordPress.org deploy copy the tree with `rsync --exclude-from=.distignore`, so a local-only top-level directory (e.g. `dist/`) must also be listed in `.distignore`, plus `.phpcs.xml.dist` if it can hold PHP. Don't package from `git archive` either: `.gitattributes` export-ignore drops `src/`, `package.json` and `webpack.config.js`, so the export cannot be built. The tag deploy moves the release zip into `$GITHUB_WORKSPACE` before `10up/action-wordpress-plugin-deploy` rsyncs that directory, so anything the workflow writes there ships to SVN unless `.distignore` lists it — every tag since 2.9.14 put its zip into SVN (backlog R1-X1).
- `plugins/` holds server-side plugins (`artws-security-hub` runs on wc.artws.info) that must never ship: `.distignore` lists `/plugins`, `.gitattributes` export-ignores it, and the tag deploy fails if the zip contains `plugins/`. Each one has its own `composer.json`, `.phpcs.xml.dist`, `phpunit.xml.dist` and workflow (`cd plugins/<name> && composer install && composer lint && composer test`); `testing.yml` ignores `plugins/**`. The root `.phpcs.xml.dist` excludes it with a *relative* pattern (`^plugins/*`): a plain `<exclude-pattern>/plugins/</exclude-pattern>` matches the absolute path, so a checkout inside `wp-content/plugins/` lints no files at all. The repository is public: server secrets come from `wp-config.php` constants, never code.
- The JSON exchanged with wc.artws.info (feed manifest and `vuln-*.json.gz`, `POST /scan` request, report) is defined only by the schemas and samples in `tests/fixtures/security-feed/` (its README holds the rules a schema cannot: sha256 is over the `.json.gz` bytes, version-range semantics, `/feed/v1/`). Both test suites read those files, so a PR that changes them runs both workflows and must pass both. After editing a `vuln-*.json` sample, run `php tests/fixtures/security-feed/regenerate.php` to rebuild the `.json.gz` files and the manifest's sha256 / count.
- Paidy IDs (`pay_` / `cap_` / `ref_`) use the base64url alphabet — letters, digits, `_` **and `-`** — and Paidy documents only the prefix, not a charset or length. Any format guard tighter than `^pay_[A-Za-z0-9_-]+$` silently blocks real payments (the thank-you page and the webhook both leave the order pending until the stock hold cancels it); this regressed twice (`_` missed until 2.9.14, `-` until #223). Never pin a length; when a new character shows up, extend the class in `WC_Gateway_Paidy::paidy_payment_api_url()` and the providers in `tests/Unit/test-paidy-payment-id-format.php` together. Since #236 that helper holds the only copy of the guard and builds every `api.paidy.com/payments/…` URL (it returns `null` for a bad ID) — call it; never concatenate a transaction ID into a URL or add a second regex.
- PCRE `$` also matches just before a trailing `\n`, so `/^pay_[A-Za-z0-9_-]+$/` accepts `"pay_abc\n"`. A format guard that exists to keep input out of a URL/path must end with `$/D` (or use `\z`); `rawurlencode()` made the slip harmless here, but the comment claimed whitespace was rejected.
- Gateway code must check `is_wp_error()` before using a `wp_remote_*()` result, then read it with `wp_remote_retrieve_response_code()` / `wp_remote_retrieve_body()`. `$response['body']` on a WP_Error is a PHP `Error`, which escapes `wc_create_refund()`'s `catch ( Exception )` and leaves a WooCommerce refund behind with no gateway refund. `process_refund()` returns true only for a confirmed success (Paidy: 2xx, JSON, `status` `closed`), never as a fall-through, or a 502 HTML page gets recorded as refunded (#231).
- `phpunit.xml` converts warnings to exceptions, and WooCommerce code under test may catch them (`wc_refund_payment()` catches `Exception`). A test can then pass on broken code because a warning stood in for the failure it expects (the #236 `wc_create_refund()` test did). Wrap such a call in `set_error_handler( static function () { return true; }, E_WARNING )` with `restore_error_handler()` in `finally`, since production only logs warnings, and prove the test fails with the fix reverted.
- `.phpcs.xml.dist` excludes `/tests/`, so `composer lint` and CI never check test files. Run `vendor/bin/phpcs --standard=WordPress tests/Unit/<file>.php` (and `phpcbf`) by hand on a new or changed test — array `=>` alignment is the usual miss.
- Run tests locally with `composer test-local`, not `composer test`. `composer test` ends with `test-stop`, which removes the `jp4wc-mysql-test` container, and it uses the WP test lib in `$TMPDIR`, which other projects rewrite (repointing `DB_HOST` at their MySQL) and macOS purges (#237).
- Pin the WP test library to the same release as the WordPress core. `bin/setup-test-env.sh` used to pair the `latest` core with the trunk test library, which broke as soon as trunk needed a class the release lacks (`WP_Secrets_Store`, 2026-10); it now takes the version from the installed core (or resolves `latest` to a version number, as CI's `install-wp-tests.sh` does) and records the library's tag in `wordpress-tests-lib/.jp4wc-tests-tag`. When they disagree, or `composer test-local` finds a library without that record (e.g. one left by an earlier, failed install), it stops and asks you to delete the environment.
- WooCommerce Store API (`CheckoutSchema::sanitize_additional_fields()`) runs every submitted additional-field value through `wp_kses( $v, array() )` *before* `SelectFieldType` matches it against the option values, so a select option whose value contains `&` arrives as `&amp;` and WooCommerce itself rejects it with 400 `rest_not_in_enum` (`'` and `"` pass). The delivery time-zone options use the admin-entered label as the value, so a label with `&` cannot be chosen in the Checkout block (#224, backlog R1-X1) — warn in settings or use a separate option value if this ever needs fixing.
- `wp_kses_post()` turns a bare `&` into `&amp;` even in text with no markup, so never pass plain-text email output (the `$plain_text` branch) through it, and `wp_strip_all_tags()` is no substitute (its `trim()` eats the surrounding blank lines and `strip_tags()` cuts at a bare `<`). Echo the plain text with a reasoned `phpcs:ignore WordPress.Security.EscapeOutput`, as WooCommerce core's plain templates do. Delivery meta (`wc4jp-delivery-*`) is stored as entered since 2.9.17 and escaped only on output; don't add an unconditional "decode legacy entities on read" — it rewrites legitimate values that contain entity text, and both review bots rejected it (#224).
- Data printed into an inline `<script>` must be built as a PHP array and printed once with `wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )` — never concatenate JavaScript by hand (a skipped entry left a stray `},` and broke Paidy Checkout for every order with a free-shipping coupon, #232). Don't use `esc_js()` for values the script sends elsewhere: it HTML-encodes `&` `<` `"`, so Paidy showed `&amp;`. `paidy_make_order()` still prints the buyer and address fields with `esc_js()`.
- `wc_format_decimal()` trims trailing zeros only for a float input; a string such as `"0.00"` (e.g. a coupon discount saved through the REST API) is stored as is and is truthy in PHP. Test WC amounts numerically (`0.0 !== (float) $value`), not by truthiness.
- `add_option()` is not an atomic create-if-absent. Once a `get_option()` miss is cached (notoptions) it skips the existence check and runs `INSERT … ON DUPLICATE KEY UPDATE`, so it overwrites a concurrent writer, yet it fails silently on an existing row whose value is empty. It also fires the `add_option` action *before* that INSERT, so a handler that saves the same option inside it is overwritten by the outer insert. For a value that must exist and match what you send (e.g. `paidy_site_hash`, #238), store it with `update_option()` and send the value you stored.
- Every PHPUnit run and CI job is a fresh install: the bootstrap's `init` runs `JP4WC_Install::check_version()` and fires `jp4wc_installed`, so anything hooked to `jp4wc_installed` / `jp4wc_updated` runs in tests. Never call out over HTTP from those hooks without a `pre_http_request` mock — the removed usage tracker POSTed the test site's profile to wc.artws.info on every run until #239.
- When removing a feature, clear its WP-Cron events and options under every name it ever used (`git log -S '<hook>'`): 2.6.10–2.6.19 scheduled the tracker as `wc4jp_tracker_send_event` and the 2.6.20 rename to `jp4wc_` never cleared it (#239). Do it in `JP4WC_Install::install()` (runs on install and every update) and `uninstall.php`, and don't reuse those names — the cleanup keeps running on every update.
- The tag deploy (`10up/action-wordpress-plugin-deploy`) syncs `.wordpress-org/` to the WordPress.org assets, so a settings-screen change must also update `.wordpress-org/screenshot-*.png` and the readme `== Screenshots ==` captions before the tag (#239). The deploy only commits to SVN: WordPress.org Release Confirmation holds the release until a committer confirms it on wordpress.org (2.9.16 sat unpublished for a month), so after the deploy check the plugins API `versions` list or `downloads.wordpress.org/plugin/woocommerce-for-japan.<ver>.zip`, not SVN.
- Never register order side effects (capture / cancel / refund on `woocommerce_order_status_*`) in a gateway constructor, and never `new WC_Gateway_Paidy()` outside WooCommerce's gateway list: every instance's constructor registers its hooks again, so a second instance re-sent the Paidy request (#241) and printed the order-pay script twice (#244). Take the gateway with `WC_Gateway_Paidy::get_registered_instance()` — it is null when Paidy is not in the list, so log the skip as a warning (the webhook returns 503) — and register side effects once with static callbacks (`register_order_status_hooks()`; `register_checkout_return_hooks()`, which also holds `woocommerce_thankyou_paidy`). WooCommerce loads the gateways only on the settings page, the order edit screen, checkout / order-pay / order-received and when sending deferred e-mails — not on REST, bulk-action or cron status changes, nor on a third-party thank-you page outside checkout. To read a gateway setting, read `woocommerce_paidy_settings` instead of instantiating the gateway; Paidy's mode is `environment` (`'live'`, otherwise sandbox) — there is no `testmode`, and checking it verified sandbox webhooks with the live key until #244. In tests, simulate a request without loaded gateways by setting `WC_Payment_Gateways::$_instance` to null through `ReflectionProperty` (without `setAccessible()`, which PHP 8.5 deprecates) and restore it in `tearDown()`, together with `WC_Gateway_Paypal`'s static `$instance`, which reloading the list replaces. In the test suite, creating the REST server loads the list, so call `rest_get_server()` before setting it to null. The same goes for front-end hooks: the constructor's `woocommerce_before_checkout_form` callback printed the Paidy "declined" notice twice (#248); `register_checkout_return_hooks()` now registers it once.
- Paidy treats any webhook response other than HTTP 200 — 403/404 included — or no response within 10 s as a failure and re-sends at growing intervals, the last attempt about 5 h after the first retry (https://paidy.com/docs/en/webhook.html "Latency & retries"; no retry count is documented). A 503 is right for a transient state such as the gateway missing from WooCommerce's list (`gateway_unavailable_error()`), but a 4xx meant as a permanent rejection is also retried for ~5 h (backlog R1-X1, #253).
- Fixing a translation in `i18n/*.po` / `.mo` only reaches sites without a WordPress.org language pack, which wins over the bundled file. Also fix it on translate.wordpress.org, and say "bundled translation" in the changelog (#241).
- Never guard `process_refund()` with "already refunded": WooCommerce calls it once per refund, so every later partial refund needs it (#243). A gateway's automatic refund (e.g. Paidy's completed → cancelled) goes through `wc_create_refund()` with `refund_payment` true for `get_remaining_refund_amount()` — calling `process_refund()` directly leaves no WooCommerce refund record. A full refund then switches the order to `refunded` and e-mails the customer; to keep the status the merchant just set, filter `woocommerce_order_fully_refunded_status` to false for that order only and remove the filter in `finally`. `get_meta( $key, false )` returns `WC_Meta_Data` objects, not values, and Paidy lists all of a payment's `refunds[]` in no documented order — store the union of the IDs, never `refunds[0]`.
- `JP4WC_Framework::jp4wc_debug_log()` writes only when `$flag` is the string `'yes'` — pass the gateway's `$this->debug`, never `true` (#247; `class-jp4wc-yahoo-api-endpoint.php` still passes `true`). To assert a log line in a test, return a recording `WC_Logger` object from `woocommerce_logging_class`, and in `tearDown()` return a fresh `WC_Logger` once: `wc_get_logger()` keeps any cached `WC_Logger` instance.
- `woocommerce_before_checkout_form` fires only on the shortcode checkout, and the Checkout block shows `wc_add_notice()` session notices only where the page has a Store Notices block. Block themes' `page-checkout` template has one; classic themes don't, so there the notice waits in the session and appears on the next classic page. For the Checkout block, dispatch the notice from an inline script with `wp.data.dispatch( 'core/notices' ).createErrorNotice( msg, { context: 'wc/checkout', id } )` (deps `wp-data`, `wp-notices`). Use exactly one of the two paths per request (`WC_Gateway_Paidy::handle_checkout_return()`, #248). A static flag that carries state from `template_redirect` to `wp_enqueue_scripts` must be reset at the start of each request and after use, because long-lived PHP workers (e.g. FrankenPHP) keep static properties between requests.
- Once a test defines `WOOCOMMERCE_CHECKOUT` (the Store API checkout tests do), `is_checkout()` stays true for the rest of the PHPUnit process, so a "not on the checkout page" case can't be tested in the shared suite. Don't move it to `@runInSeparateProcess`: the child runs the bootstrap again and fails in CI on a `_load_textdomain_just_in_time` notice (PHP 8.4+ / WC 10.6+). Test another negative case instead, such as the order-pay endpoint via `$GLOBALS['wp']->query_vars['order-pay']` (#248, backlog CI-1).
