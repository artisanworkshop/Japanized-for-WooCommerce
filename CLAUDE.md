# Japanized for WooCommerce — Claude Code Instructions

`AGENTS.md` holds the review rules Codex and Copilot apply to pull requests (Codex reads only that file). When a pitfall added here is something a reviewer should check, add a short rule there too. Keep each rule true to the code: a rule stricter than the code (public `artws-sec/v1` routes, `mb_chr()`) makes the bots flag correct code (#283).

## Plugin Overview

- **Plugin**: Japanized for WooCommerce (`woocommerce-for-japan`)
- **Version**: 2.9.20 | **PHP**: 8.3+ | **WP**: 6.7+ | **WC**: 8.0+
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
  artws-php-security-notice/  # PHP security notice shared with the payment plugins (copied as is)
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
`composer lint` fails on main as well (93 errors / 12 warnings in 21 files on 2026-10-27, mostly the generated `assets/js/build/**/*.asset.php`, which `.phpcs.xml.dist` does not exclude; backlog R1-X1), and CI does not run it: compare the counts with main instead of expecting zero (#280, #286).

JS/CSS build:
```bash
npx wp-scripts build   # bundle only — use this in feature PRs
npm run start          # watch mode
```
`npm run build` also runs `i18n:build`, which writes a stray `i18n/metaps-for-wc.pot` and rewrites the JSON translations — don't use it in feature PRs.

### Key Rules
- All globals must use `jp4wc_` / `JP4WC_` / `JP4WC` prefix — except `includes/artws-php-security-notice/`, which uses `artws_` / `ArtisanWorkshop\PhpSecurityNotice` (both listed in `.phpcs.xml.dist`)
- Escape all output: `esc_html__()`, `esc_attr()`, `wp_kses_post()`, etc.
- Sanitize all input: `sanitize_text_field()`, `absint()`, etc.
- Use nonces for all forms and AJAX requests
- Never use `extract()` or short PHP tags
- Settings stored as WP options with `wc4jp-` prefix (e.g. `wc4jp-yomigana`)

## Block Checkout Integration

### Boot Sequence
1. `plugins_loaded` (priority 10) → `JP4WC::instance()` → `JP4WC::init()`
2. `init()` hooks `jp4wc_blocks_support()` to `woocommerce_blocks_loaded` only, which WooCommerce fires from its own `plugins_loaded` 10 callback. There is no fallback: when WooCommerce's callback runs first (network activation, PHPUnit), it never runs (backlog R1-X1)
3. `jp4wc_blocks_support()` → registers the payment block callbacks and adds `woocommerce_init` at priority 5 (the globals `$jp4wc_{delivery,yomigana}_init_done` stop a second run)
4. `woocommerce_init` (priority 5) → instantiate integration → `register_checkout_fields()`; the same instance is registered on `woocommerce_blocks_checkout_block_registration`, where WooCommerce calls its `initialize()`

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
- A link can open a tab: `admin.php?page=jp4wc-settings&tab=<name>` (`TabPanel` `initialTabName`; the security notice links to `tab=security`, #279).

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

- `class_exists()` does not use PHP `use` aliases — always pass fully-qualified class name. A short name can seem to work when the file is loaded after its parent class, because PHP then declares the class before the guard runs (early binding); the payment block files relied on this until #268, and tests don't notice it, so `test-jp4wc-load-at-plugins-loaded.php` scans every guard
- Block checkout REST validation runs multiple times per page load (calc_totals, locale calls) — guard accordingly
- `wc4jp-` option prefix ≠ `jp4wc_` function prefix — they are different naming schemes intentionally
- CSS `:has()` selector is used for field ordering — supported in Chrome 105+, Firefox 121+, Safari 15.4+
- `is_order_received_page()` does NOT cover the My Account view-order page — order-display logic must also check `is_wc_endpoint_url( 'view-order' )` (both pages fire `woocommerce_order_details_after_customer_address`)
- Paired output-buffer hooks (ob_start at priority 9 / ob_get_clean at 11) must gate the close on a flag set by the open call — never re-evaluate the page condition, or a filter changing mid-request leaks/steals a buffer
- Transients are unsuitable for state that must outlive a slow external process (e.g. a third-party review taking days–weeks) — the external delay is unbounded, so no TTL is reliably long enough, and on sites with a persistent object cache (Redis/Memcached) transients can also be dropped before the TTL by eviction or a cache flush. Use a non-autoloaded option instead with an application-level TTL check.
- For concurrency-safe keyed storage, don't hold many independent values in a single option updated via read-modify-write (`get_option` → mutate → `update_option`) — concurrent requests can lose each other's writes. Store one option row per key (key embedded in the option name) instead.
- A job that decides from a stored state and writes it back (cron run, watchdog, settings-save hook) must take its lock first and read the option from the database: `wp_cache_delete( $name, 'options' )` and drop it from `notoptions`. A value read earlier in the process can predate another process's write, and saving it back rolls that write back (#275, `Artws_Sec_Feed_Updater::fresh_option()`). An autoloaded option lives in `alloptions`, so read it with `$wpdb->get_row()` — not `get_var()`, which returns null for `''` as well as for a missing row. Against a setting that is turned off and then purged, check the setting after the write, not before (`JP4WC_Security_Feed::keep_written_data()`, #276): the purge then either follows the write or the check sees the change. A writer that checks after its writes must store the default setting row first (`start_run()`): `keep_written_data()` counts a missing row as off and purges (#279 R2-1).
- A user-triggered WP-Cron job (`JP4WC_Security_Feed::request_recheck()`, #279) needs a hook of its own: WordPress drops an event identical to one within 10 minutes, so it would vanish into an automatic one. Schedule with `$wp_error = true` and treat `duplicate_event` as queued (two requests at once), other errors as failure. Mark completion with a value only that job writes (the request time it ran for), since automatic jobs in the same cron run write the same results first. Don't `spawn_cron()` under `ALTERNATE_WP_CRON` (it redirects the current request) or `DISABLE_WP_CRON`.
- WC core injects Additional Checkout Fields into the admin order edit screen (`CheckoutFieldsAdmin` on `woocommerce_admin_billing_fields` / `woocommerce_admin_shipping_fields`, priority 10) via `array_splice()`, which replaces the injected entries' string keys with numeric ones — detect them by `$field['id']` (e.g. `_wc_billing/jp4wc/...`), never by array key. `show => false` suppresses only the view-mode echo; the edit form ignores `show`, so the field stays editable.
- Docblock `@since` on new code, and any "since/before x.y.z" comment, must be the version of the first release tag that will contain it — the next version after the last pushed tag, which may be a minor or major step (the security check code says 3.0.0 after 2.9.20). Once a bump PR merges, readme `Stable tag` already names that unreleased version, so "Stable tag +1" is one too high. Whatever is on main when the tag is pushed ships: a PR meant for the next version that merges first needs a changelog line and its version mentions corrected (#245 → #246; `release-bump check` reports it as MISSING). Copilot review flags stale values.
- `wp_kses()` does not balance HTML tags — admin-entered HTML echoed into the classic checkout payment box must be `wp_kses( force_balance_tags( $html ), $allowed )` (balance inside kses so PHPCS EscapeOutput stays satisfied). A single stray `</div>` escapes the `.woocommerce-checkout-payment` AJAX fragment and leaves an orphaned place-order row behind on every `update_order_review` → duplicated order buttons. Also balance on save via a `validate_{key}_field()` override (PR #205).
- Use `wp_unslash()` instead of `stripslashes()` for request/setting values — Copilot review flags `stripslashes()`.
- In unit tests, never `markTestSkipped()` when a plugin class is missing — `require_once` the class file and assert `class_exists`, or a loading regression silently drops coverage (Copilot flags this too).
- `WP_REST_Request::get_body()` returns `null` (not `''`) when the request never had a body (e.g. any `GET`) — a `'' === $request->get_body()` guard silently fails to catch this; use `empty( $request->get_body() )` instead. This exact bug recurred 3 times across one PR (Paidy receiver signature work) before all instances were caught.
- `WP_REST_Request::get_params()`/`get_param()` merge the query string on top of the parsed body, and a query value *overrides* a same-named body value — for any HMAC/signature-verified endpoint, read only `get_json_params()`/`get_body_params()` for values the signature actually covers; the query string was never part of what was signed. The same goes for a route's URL parameter: `get_param( 'token' )` on `/scan/(?P<token>…)` returns a `?token=` from the query string instead — read `get_url_params()` (#281).
- The COD fee settings live in two places: the `wc4jp-extra_charge_*` options written by the React settings screen, and `woocommerce_cod_settings` (plus `jp4wc_tax_class_for_cod`) written by the COD gateway's own settings page. `JP4WC_COD_Fee::get_cod_fee_settings()` prefers a `wc4jp-` option whenever it *exists*, even when empty. Anything that reports these settings for editing must report the values in force (`JP4WC_Settings_API::fill_cod_fee_settings_in_force()`), because the settings screen saves back every value it was given — reporting them as empty wiped the fee on the first save (#218).
- Not every gateway module's options use the `wc4jp-` prefix — a payment gateway can have its own established naming scheme distinct from both `wc4jp-` and `jp4wc_` (e.g. Paidy's `paidy_*` options, present since v2.7.0). Check sibling options in the same file before flagging a missing `wc4jp-` prefix as a bug.
- Before running `msgmerge --update` on `i18n/*.po` inside a feature/fix PR, diff it against the current `.pot` first. A `.po` that hasn't been resynced in a while will also prune long-obsolete entries and reflow the whole file on merge, bloating an unrelated PR by 1000+ lines — add only the new/changed msgids by hand instead and leave a full resync for a dedicated POT-regen PR. `python3 ~/.claude/skills/wp-i18n/scripts/po-add-entry.py` does this for a new PHP string (finds the reference, matches each file's wrapping, and refuses unless the rebuilt `.mo` differs from the old one by exactly the new entry). To drop a string whose code was deleted, remove its entry from the `.pot` and each `.po` by hand and rebuild the `.mo` with `msgfmt`, after checking that `msgfmt` of the unchanged `.po` reproduces the committed `.mo` (`msgunfmt` both and diff), so the `.mo` diff is that entry alone (#280).
- A user-visible default that gets saved or used as a value (e.g. a select option's value) must not come from PHP `__()` at request time: a WordPress.org language pack beats the bundled `i18n/*.mo` and has no translation for a new string until it's translated on translate.wordpress.org, and block checkout fields are registered on `woocommerce_init` (init 0), before `load_plugin_textdomain()` (init 1). Script translations try the bundled JSON before the language pack, so save such defaults from the React settings app (`withMorningLabel()` in `Settings.js`, PR #222).
- For a PHP string that must show translated before the language pack has it, read the bundled `.mo` only while building that string: build it inside `jp4wc_with_bundled_translation( $callback )` (main file), which adds `gettext_` / `gettext_with_context_` / `ngettext_woocommerce-for-japan` filters that, when the text came back untranslated, look it up in `i18n/woocommerce-for-japan-<locale>.mo` through `WP_Translation_File::create()`, and removes them in `finally` (#272). `_nx()` is not covered. The PHP security notice strings, `JP4WC_Security_Notice::render()` and every `JP4WC_Security_API` route callback use it. A plugin that bundles the shared notice needs the same fallback.
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
- `.gitignore` does not keep a path out of the plugin package — the release ZIP and the WordPress.org deploy copy the tree with `rsync --exclude-from=.distignore`, so a local-only top-level directory (e.g. `dist/`) must also be listed in `.distignore`, plus `.phpcs.xml.dist` if it can hold PHP. Don't package from `git archive` either: `.gitattributes` export-ignore drops `src/`, `package.json` and `webpack.config.js`, so the export cannot be built. The tag deploy moves the release zip into `$GITHUB_WORKSPACE` before `10up/action-wordpress-plugin-deploy` rsyncs that directory, so anything the workflow writes there ships to SVN unless `.distignore` lists it — 2.9.14–2.9.17 each shipped their own zip until #265 added `/*.zip`.
- `plugins/` holds server-side plugins (`artws-security-hub` runs on wc.artws.info) that must never ship: `.distignore` lists `/plugins`, `.gitattributes` export-ignores it, and the tag deploy fails if the zip contains `plugins/`. Each one has its own `composer.json`, `.phpcs.xml.dist`, `phpunit.xml.dist` and workflow (locally `cd plugins/<name> && composer install && composer lint && composer test-local`; the workflow runs `composer lint` and `composer test`); `testing.yml` ignores `plugins/**`. `artws-security-hub`'s tests run on the WordPress test library: its `composer test-local` borrows the root `composer test-local` environment (run that once first) with a database of its own, and its workflow installs the library with the root `bin/install-wp-tests.sh`. The root `.phpcs.xml.dist` excludes it with a *relative* pattern (`^plugins/*`): a plain `<exclude-pattern>/plugins/</exclude-pattern>` matches the absolute path, so a checkout inside `wp-content/plugins/` lints no files at all. The repository is public: server secrets come from `wp-config.php` constants, never code. Deploying `artws-security-hub` follows `plugins/artws-security-hub/DEPLOY.md` (setup in working order, per-version update steps); a change that needs a deploy-time step adds it there (#282).
- wc.artws.info runs on Pressable, whose nginx can't be configured: `/feed/v1/` is answered by `plugins/artws-security-hub/hosting/pressable/custom-redirects.php`, uploaded by hand as `htdocs/custom-redirects.php` (`.distignore` keeps `/hosting` out of the ZIP). It runs before WordPress, so it repeats the four names of `Artws_Sec_Feed_Publisher::FILES` / `MANIFEST` and the default feed directory: renaming a feed file (backlog R1-L1) changes it too, and DEPLOY.md 10 re-uploads it. Cloudflare is DNS only there, so `ARTWS_SEC_CLIENT_IP_HEADER` stays unset (any direct request could forge the header), and Pressable runs WP-Cron itself with `DISABLE_WP_CRON` already true outside wp-config.php (don't define it). The root `.gitignore` ignores every `deploy/` directory (Grunt era): don't name a new one `deploy/` (#285).
- The JSON exchanged with wc.artws.info (feed manifest and `vuln-*.json.gz`, `POST /scan` request, the scan response with its report, REST errors) is defined only by the schemas and samples in `tests/fixtures/security-feed/` (its README holds the rules a schema cannot: sha256 is over the `.json.gz` bytes, version-range semantics, `/feed/v1/`, and the `artws-sec/v1` authentication, check order and status codes). Both test suites read those files, so a PR that changes them runs both workflows and must pass both. After editing a `vuln-*.json` sample, run `php tests/fixtures/security-feed/regenerate.php` to rebuild the `.json.gz` files and the manifest's sha256 / count.
- Paidy IDs (`pay_` / `cap_` / `ref_`) use the base64url alphabet — letters, digits, `_` **and `-`** — and Paidy documents only the prefix, not a charset or length. Any format guard tighter than `^pay_[A-Za-z0-9_-]+$` silently blocks real payments (the thank-you page and the webhook both leave the order pending until the stock hold cancels it); this regressed twice (`_` missed until 2.9.14, `-` until #223). Never pin a length; when a new character shows up, extend the class in `WC_Gateway_Paidy::paidy_payment_api_url()` and the providers in `tests/Unit/test-paidy-payment-id-format.php` together. Since #236 that helper holds the only copy of the guard and builds every `api.paidy.com/payments/…` URL (it returns `null` for a bad ID) — call it; never concatenate a transaction ID into a URL or add a second regex.
- PCRE `$` also matches just before a trailing `\n`, so `/^pay_[A-Za-z0-9_-]+$/` accepts `"pay_abc\n"`. A format guard that exists to keep input out of a URL/path must end with `$/D` (or use `\z`); `rawurlencode()` made the slip harmless here, but the comment claimed whitespace was rejected.
- Gateway code must check `is_wp_error()` before using a `wp_remote_*()` result, then read it with `wp_remote_retrieve_response_code()` / `wp_remote_retrieve_body()`. `$response['body']` on a WP_Error is a PHP `Error`, which escapes `wc_create_refund()`'s `catch ( Exception )` and leaves a WooCommerce refund behind with no gateway refund. `process_refund()` returns true only for a confirmed success (Paidy: 2xx, JSON, `status` `closed`), never as a fall-through, or a 502 HTML page gets recorded as refunded (#231).
- `phpunit.xml` converts warnings to exceptions, and WooCommerce code under test may catch them (`wc_refund_payment()` catches `Exception`). A test can then pass on broken code because a warning stood in for the failure it expects (the #236 `wc_create_refund()` test did). Wrap such a call in `set_error_handler( static function () { return true; }, E_WARNING )` with `restore_error_handler()` in `finally`, since production only logs warnings, and prove the test fails with the fix reverted. A `catch ( Throwable )` fallback hides them the same way: fire an action in the catch and fail the tests when it fires (`jp4wc_security_check_error`, #258).
- `.phpcs.xml.dist` excludes `/tests/`, so `composer lint` and CI never check test files. Run `vendor/bin/phpcs --standard=WordPress tests/Unit/<file>.php` (and `phpcbf`) by hand on a new or changed test — array `=>` alignment is the usual miss.
- phpcbf's `WordPress.WP.CapitalPDangit` fixer rewrites `wordpress` to `WordPress` in string literals and comments (array keys and values are left alone; docblock text is rewritten, #258), and the PostToolUse hook runs phpcbf after every edit. A slug compared as `'wordpress'` silently became `'WordPress'` and rejected every core record (#275). Keep such a string in a constant with `// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText`.
- The WordPress test library's bootstrap filters `automatic_updater_disabled` to true for every test, so `wp_is_auto_update_enabled_for_type()` is false there; a test of auto-update state removes that filter and re-adds it in `finally` (#258).
- WP-Cron may run from the command line with another php.ini than the web server's: `token_get_all()` follows this process's `short_open_tag`, and `display_errors` is this process's (`WP_DEBUG_DISPLAY` defined as null leaves it to php.ini; `wp_initial_constants()` defines it as true when wp-config.php does not, before plugins load — Codex claimed otherwise on #277). `get_home_url()` switches to https when the current request `is_ssl()`, and `home_url()` is filtered (CDN plugins). A check that infers what visitors get treats a possible difference as unknown, resets the scheme to the stored `home` (a `home_url` filter at `PHP_INT_MIN`) and sends requests for the site itself to `get_option( 'home' )` (`JP4WC_Security_Checks`, #258).
- A `?>` inside a `//` or `#` comment closes the PHP block, and the parse error makes the PostToolUse phpcbf re-indent the rest of the file: write "closing tag" in such comments (#258).
- WordPress does not require mbstring: `wp-includes/compat.php` defines `mb_strlen()` / `mb_substr()` (and `mb_chr()` / `mb_ord()`) but not `mb_check_encoding()`; both bots flag other `mb_` calls in new code. Check UTF-8 with `preg_match( '//u', $s )` (#275).
- Run tests locally with `composer test-local`, not `composer test`. `composer test` ends with `test-stop`, which removes the `jp4wc-mysql-test` container, and it uses the WP test lib in `$TMPDIR`, which other projects rewrite (repointing `DB_HOST` at their MySQL) and macOS purges (#237).
- Pin the WP test library to the same release as the WordPress core. `bin/setup-test-env.sh` used to pair the `latest` core with the trunk test library, which broke as soon as trunk needed a class the release lacks (`WP_Secrets_Store`, 2026-10); it now takes the version from the installed core (or resolves `latest` to a version number, as CI's `install-wp-tests.sh` does) and records the library's tag in `wordpress-tests-lib/.jp4wc-tests-tag`. When they disagree, or `composer test-local` finds a library without that record (e.g. one left by an earlier, failed install), it stops and asks you to delete the environment.
- WooCommerce Store API (`CheckoutSchema::sanitize_additional_fields()`) runs every submitted additional-field value through `wp_kses( $v, array() )` *before* `SelectFieldType` matches it against the option values, so a select option whose value contains `&` arrives as `&amp;` and WooCommerce itself rejects it with 400 `rest_not_in_enum` (`'` and `"` pass). The delivery time-zone options use the admin-entered label as the value, so a label with `&` cannot be chosen in the Checkout block (#224, backlog R1-X1) — warn in settings or use a separate option value if this ever needs fixing.
- `wp_kses_post()` turns a bare `&` into `&amp;` even in text with no markup, so never pass plain-text email output (the `$plain_text` branch) through it, and `wp_strip_all_tags()` is no substitute (its `trim()` eats the surrounding blank lines and `strip_tags()` cuts at a bare `<`). Echo the plain text with a reasoned `phpcs:ignore WordPress.Security.EscapeOutput`, as WooCommerce core's plain templates do. Delivery meta (`wc4jp-delivery-*`) is stored as entered since 2.9.17 and escaped only on output; don't add an unconditional "decode legacy entities on read" — it rewrites legitimate values that contain entity text, and both review bots rejected it (#224).
- Data printed into an inline `<script>` must be built as a PHP array and printed once with `wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )` — never concatenate JavaScript by hand (a skipped entry left a stray `},` and broke Paidy Checkout for every order with a free-shipping coupon, #232). Don't use `esc_js()` for values the script sends elsewhere: it HTML-encodes `&` `<` `"`, so Paidy showed `&amp;`. `paidy_make_order()` still prints the buyer and address fields with `esc_js()`.
- `wc_format_decimal()` trims trailing zeros only for a float input; a string such as `"0.00"` (e.g. a coupon discount saved through the REST API) is stored as is and is truthy in PHP. Test WC amounts numerically (`0.0 !== (float) $value`), not by truthiness.
- `add_option()` is not an atomic create-if-absent. Once a `get_option()` miss is cached (notoptions) it skips the existence check and runs `INSERT … ON DUPLICATE KEY UPDATE`, so it overwrites a concurrent writer, yet it fails silently on an existing row whose value is empty. It also fires the `add_option` action *before* that INSERT, so a handler that saves the same option inside it is overwritten by the outer insert. For a value that must exist and match what you send (e.g. `paidy_site_hash`, #238), store it with `update_option()` and send the value you stored. The per-option hooks `add_option_{$option}` and `update_option_{$option}` fire *after* the write (Copilot claimed otherwise on #275).
- Every PHPUnit run and CI job is a fresh install: the bootstrap's `init` runs `JP4WC_Install::check_version()` and fires `jp4wc_installed`, so anything hooked to `jp4wc_installed` / `jp4wc_updated` runs in tests. Never call out over HTTP from those hooks without a `pre_http_request` mock — the removed usage tracker POSTed the test site's profile to wc.artws.info on every run until #239.
- `register_activation_hook()` callbacks run after `plugins_loaded`, so the classes `JP4WC::includes()` loads are not there (nor in a deactivation callback while WooCommerce is inactive). Schedule WP-Cron events from an `init` check (`JP4WC_Security_Feed::ensure_schedule()`), not from activation, and clear them on deactivation by hook-name string (#276).
- A `.json.gz` checked by its sha256: WP_Http does not pass `decompress` to Requests, which decompresses any response with `Content-Encoding`, so the server must not send that header (contract README). Decompress in chunks with `inflate_init( ZLIB_ENCODING_GZIP )` + `inflate_add()` and require `ZLIB_STREAM_END`; `gzopen()` / `compress.zlib://` silently accept a cut-off trailer and non-gzip input (#276).
- Don't `do_action( 'init' )` in a unit test to reach a callback: WooCommerce registers its blocks again and the incorrect-usage notices fail the test. Assert `has_action()` and call the callback (#276).
- When removing a feature, clear its WP-Cron events and options under every name it ever used (`git log -S '<hook>'`): 2.6.10–2.6.19 scheduled the tracker as `wc4jp_tracker_send_event` and the 2.6.20 rename to `jp4wc_` never cleared it (#239). Do it in `JP4WC_Install::install()` (runs on install and every update) and `uninstall.php`, and don't reuse those names — the cleanup keeps running on every update. Put new cleanup in a static method of the feature class and call it from `uninstall.php` (which declares a global function, so tests can't load it), as `JP4WC_Security_Feed::uninstall()` does for every site of a network (#276).
- The tag deploy (`10up/action-wordpress-plugin-deploy`) syncs `.wordpress-org/` to the WordPress.org assets, so a settings-screen change must also update `.wordpress-org/screenshot-*.png` and the readme `== Screenshots ==` captions before the tag (#239). The deploy only commits to SVN: WordPress.org Release Confirmation holds the release until a committer confirms it on wordpress.org (2.9.16 sat unpublished for a month), so after the deploy check the plugins API `versions` list or `downloads.wordpress.org/plugin/woocommerce-for-japan.<ver>.zip`, not SVN.
- Never register order side effects (capture / cancel / refund on `woocommerce_order_status_*`) in a gateway constructor, and never `new WC_Gateway_Paidy()` outside WooCommerce's gateway list: every instance's constructor registers its hooks again, so a second instance re-sent the Paidy request (#241) and printed the order-pay script twice (#244). Take the gateway with `WC_Gateway_Paidy::get_registered_instance()` — it is null when Paidy is not in the list, so log the skip as a warning (the webhook returns 503) — and register side effects once with static callbacks (`register_order_status_hooks()`; `register_checkout_return_hooks()`, which also holds `woocommerce_thankyou_paidy`). WooCommerce loads the gateways only on the settings page, the order edit screen, checkout / order-pay / order-received and when sending deferred e-mails — not on REST, bulk-action or cron status changes, nor on a third-party thank-you page outside checkout. To read a gateway setting, read `woocommerce_paidy_settings` instead of instantiating the gateway; Paidy's mode is `environment` (`'live'`, otherwise sandbox) — there is no `testmode`, and checking it verified sandbox webhooks with the live key until #244. In tests, simulate a request without loaded gateways by setting `WC_Payment_Gateways::$_instance` to null through `ReflectionProperty` (without `setAccessible()`, which PHP 8.5 deprecates) and restore it in `tearDown()`, together with `WC_Gateway_Paypal`'s static `$instance`, which reloading the list replaces. In the test suite, creating the REST server loads the list, so call `rest_get_server()` before setting it to null. The same goes for front-end hooks: the constructor's `woocommerce_before_checkout_form` callback printed the Paidy "declined" notice twice (#248); `register_checkout_return_hooks()` now registers it once.
- Paidy treats any webhook response other than HTTP 200 — 403/404 included — or no response within 10 s as a failure and re-sends at growing intervals, the last attempt about 5 h after the first retry (https://paidy.com/docs/en/webhook.html "Latency & retries"; no retry count is documented). A 503 is right for a transient state such as the gateway missing from WooCommerce's list (`gateway_unavailable_error()`), but a 4xx meant as a permanent rejection is also retried for ~5 h (backlog R1-X1, #253).
- Fixing a translation in `i18n/*.po` / `.mo` only reaches sites without a WordPress.org language pack, which wins over the bundled file. Also fix it on translate.wordpress.org, and say "bundled translation" in the changelog (#241).
- Never guard `process_refund()` with "already refunded": WooCommerce calls it once per refund, so every later partial refund needs it (#243). A gateway's automatic refund (e.g. Paidy's completed → cancelled) goes through `wc_create_refund()` with `refund_payment` true for `get_remaining_refund_amount()` — calling `process_refund()` directly leaves no WooCommerce refund record. A full refund then switches the order to `refunded` and e-mails the customer; to keep the status the merchant just set, filter `woocommerce_order_fully_refunded_status` to false for that order only and remove the filter in `finally`. `get_meta( $key, false )` returns `WC_Meta_Data` objects, not values, and Paidy lists all of a payment's `refunds[]` in no documented order — store the union of the IDs, never `refunds[0]`.
- `JP4WC_Framework::jp4wc_debug_log()` writes only when `$flag` is the string `'yes'` — pass the gateway's `$this->debug`, never `true` (#247; `class-jp4wc-yahoo-api-endpoint.php` still passes `true`). To assert a log line in a test, return a recording `WC_Logger` object from `woocommerce_logging_class`, and in `tearDown()` return a fresh `WC_Logger` once: `wc_get_logger()` keeps any cached `WC_Logger` instance.
- `woocommerce_before_checkout_form` fires only on the shortcode checkout, and the Checkout block shows `wc_add_notice()` session notices only where the page has a Store Notices block. Block themes' `page-checkout` template has one; classic themes don't, so there the notice waits in the session and appears on the next classic page. For the Checkout block, dispatch the notice from an inline script with `wp.data.dispatch( 'core/notices' ).createErrorNotice( msg, { context: 'wc/checkout', id } )` (deps `wp-data`, `wp-notices`). Use exactly one of the two paths per request (`WC_Gateway_Paidy::handle_checkout_return()`, #248). A static flag that carries state from `template_redirect` to `wp_enqueue_scripts` must be reset at the start of each request and after use, because long-lived PHP workers (e.g. FrankenPHP) keep static properties between requests.
- Once a test defines `WOOCOMMERCE_CHECKOUT` (the Store API checkout tests do), `is_checkout()` stays true for the rest of the PHPUnit process, so a "not on the checkout page" case can't be tested in the shared suite. Don't move it to `@runInSeparateProcess`: the child runs the bootstrap again and fails in CI on a `_load_textdomain_just_in_time` notice (PHP 8.4+ / WC 10.6+). Test another negative case instead, such as the order-pay endpoint via `$GLOBALS['wp']->query_vars['order-pay']` (#248, backlog CI-1).
- A plugin update swaps the plugin directory while requests are running (WP 6.3+ `WP_Upgrader::install_package()` renames the old directory away, then moves the new one in), so a file `require`d after `plugins_loaded` can be missing, or already the other version's (#266). Since #268 every class file is loaded at `plugins_loaded` (`JP4WC::includes()`, `WC_Paidy::includes()`) and hooks only create instances; `tests/Unit/test-jp4wc-load-at-plugins-loaded.php` checks this, so add a new class file to its `class_files()`. One request can also mix versions — the root files, which the main file requires as it loads, from one version and everything loaded at `plugins_loaded` from the other — so a file under `includes/` requires its own dependencies at file level (an earlier version's `includes()` may load it), and a hook that creates such a class checks `class_exists( 'X', false )` first and skips when it is missing. Keep file names and paths, since a released version requires them lazily during its own update, and when deleting such a file, keep it until updates from that version are rare: `includes/jp4wc-framework/config-jp4wc-framework.php` is unused since 2.9.18 and kept on purpose for the 2.9.17 class (backlog G1-1). The `v2_0_14` framework is bundled in this plugin, Pro and paidy-wc behind `class_exists`, and the first copy loaded wins (paidy-wc at `plugins_loaded` 0, this plugin 10, Pro 20), so a framework fix needs all three copies.
- `includes/artws-php-security-notice/` is copied unchanged into the payment plugins (Pay.jp, Paidy, Paygent, Epsilon), some of which still reach PHP 7.x: keep it PHP 7.0 syntax (`vendor/bin/phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 7.0- includes/artws-php-security-notice/`; `composer lint` does not check this) and free of plugin code — each plugin passes its strings through its own callback. Of all bundled copies, the newest registered version is loaded at `plugins_loaded` 5 (unlike the framework's first-loaded rule), so bump `Notice::VERSION` together with the version the main file passes to `artws_php_security_notice_register()`. The main file includes `register.php` only when it is readable, so a request during an update goes on without the notice (#272). wc4jp-pro.work articles use dated permalinks (`/2026/10/22/php2shell-php-update/`), so take `INFO_URL` from the published article, not from the planned slug (#273).
- `Requires Plugins: woocommerce` does not stop WordPress from loading this plugin when WooCommerce is inactive (deactivated with WP-CLI, folder deleted): `wp_get_active_and_valid_plugins()` ignores dependencies, which only activation (`validate_plugin_requirements()`) and admin notices check. Code outside `jp4wc_plugin()` runs without WooCommerce. Copilot claimed the opposite on #272.
- `update_option()` returns false without touching the cache or firing the `update_option*` actions when the database already holds the value, so after changing a row with `$wpdb` to simulate another process it cannot sync the cache: use `wp_cache_delete( 'alloptions', 'options' )` (#279). The `pre_update_option_{$option}` filter still runs before that comparison, so a test can count calls through it (#282).
- Don't depend on WooCommerce's roles in unit tests: a `shop_manager` user had `manage_woocommerce` locally but not in CI (#272). Use an editor and `wp_get_current_user()->add_cap( 'manage_woocommerce' )` — adding the cap to another `WP_User` object for the same user leaves the current user without it.
- `$wpdb->get_results()` / `get_row()` return `[]` / `null` on a failed query just as for no rows, and `$wpdb->query()` returns `false` on error but `0` for an `INSERT … ON DUPLICATE KEY UPDATE` that changed nothing. Check `$wpdb->last_error` (or `false ===`) wherever "no rows" leads to letting a request through or answering "not found"/"unauthorized": the bots found three such fail-open reads in #281 (rate limit, index lookup, site lookup → 401).
- In unit tests the WordPress test library turns `CREATE TABLE` / `DROP TABLE` into `TEMPORARY` ones, and other DDL (`ALTER`) or `START TRANSACTION` commits the per-test transaction. Create plugin tables from an `init` version check (it runs during the bootstrap, so they are real tables), keep code under test free of explicit transactions (undo with compensating writes), and assert that a second `dbDelta()` sends no `ALTER`/`CREATE` (#281). A test that has to run DDL first sets every option it changed back to its committed value (the DDL commits whatever is there); when it asserts that no ALTER is sent, it also turns ALTERs into no-ops in a `query` filter, so a broken guard cannot commit either (#282).
- After `// phpcs:disable A` … `// phpcs:enable A`, a later `// phpcs:ignore A` in the same file is not honoured: use one-line `phpcs:ignore`s, or a bare `phpcs:enable`.
- `dbDelta()` never drops an index or changes one in place: turning a `UNIQUE KEY` into a `KEY` of the same name fails as a duplicate name and the unique index stays. A table version that removes or changes an index runs `ALTER TABLE … DROP INDEX` itself before `dbDelta()`, and records the version only once `SHOW INDEX` shows the change (a failed read, seen in `$wpdb->last_error`, counts as not done). Compare `SHOW TABLES LIKE` results with `strcasecmp()`: with `lower_case_table_names=1` MySQL answers in lower case (`Artws_Sec_Db::install()`, #282).
- The free security check has legal pages on wc.artws.info, edited by hand in WordPress: the terms of use https://wc.artws.info/security-assessment-term/ (職人工房 合同会社, in force 2026-10-28) and the privacy policy https://wc.artws.info/privacy-policy/. They state what the client sends, the 180-day retention and deletion (including the `uninstall.php` deletion #260 must add), the hashed IP and Pressable's 7-day access logs, and the terms count using the default-on check as consent because the Security tab links to them (`TERMS_URL`, #286). A change to any of these changes both pages and the readme External Services for the same release.
- `createInterpolateElement()` (WP 7.1) shows an unknown, renamed or unclosed tag as plain text, but a translation with a closing tag and no opener throws (it pops an empty stack), and the settings app has no error boundary, so the whole screen goes blank (backlog G1-1, #286; the Paidy wizard has the same exposure).
