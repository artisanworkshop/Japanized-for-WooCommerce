# AGENTS.md

Guidance for AI agents working on Japanized for WooCommerce, and the rules Codex and Copilot apply when they review pull requests.

- **[CLAUDE.md](CLAUDE.md) is the full project guide**: plugin overview, directory layout, commands, architecture and the complete list of known pitfalls, each with the PR or issue it came from. Read it as well; this file repeats only what a reviewer must check.
- The server-side plugin `plugins/artws-security-hub/` runs on wc.artws.info and never ships with this plugin. See its `README.md` (design) and `DEPLOY.md` (deployment).
- `tests/fixtures/security-feed/` (README and JSON Schemas) is the only definition of the JSON exchanged with wc.artws.info.
- Findings already triaged are in `docs/review-backlog.md` (held or deferred), `docs/review-baseline.md` (accepted) and `docs/reviews/<branch>/` (earlier rounds of the same branch). Do not report them again unless the change makes them worse.

## Commands

```bash
composer lint                      # phpcs (WordPress Coding Standards); tests/ is excluded from the ruleset
composer test-local                # PHPUnit, own MySQL container and WP test library
cd plugins/artws-security-hub && composer lint && composer test-local   # the server plugin
npx wp-scripts build               # JS bundle (not `npm run build` in feature PRs)
```

## Code Review Rules

Report defects that change behaviour: security holes, data loss, wrong results, broken compatibility, contract violations, and tests that cannot fail. Leave formatting and lint out (`composer lint` covers them, though CI does not run it).

### Security

- Escape on output and sanitize on input; forms and AJAX need a nonce and a capability check, REST routes a `permission_callback` that really checks. A route that is public on purpose (`'__return_true'`, as every `artws-sec/v1` route is) authenticates inside its callback instead. Safe path: `esc_html()` / `esc_attr()` / `wp_kses_post()`, `sanitize_text_field()`, `absint()`, `wp_unslash()` (never `stripslashes()`).
- A value covered by a signature or a token must come from the part that was verified. `WP_REST_Request::get_param()` lets the query string override the body and the route. Safe path: `get_json_params()` / `get_body_params()` / `get_url_params()`.
- `$wpdb->get_results()` / `get_row()` / `get_var()` return the same value for "no rows" and for a failed query. Where "no rows" lets a request through or answers "not found" / "unauthorized", a read error must not be taken for it. Safe path: check `$wpdb->last_error`, and treat `false === $wpdb->query()` as a failure; fail closed.
- Client input used as an array offset needs `is_string()` / `is_int()`. A format guard that keeps input out of a URL or path ends with `$/D` or `\z`.
- Check `is_wp_error()` before using a `wp_remote_*()` result. A gateway's `process_refund()` returns true only for a confirmed success.
- Data printed into an inline `<script>` is one `wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )`, never JavaScript concatenated by hand.
- The repository is public: no keys, secrets or credentials in code, tests or fixtures (server secrets come from `wp-config.php` constants).

### WordPress and WooCommerce

- Orders go through the WC order CRUD (`$order->get_meta()`, `update_meta_data()`), never post meta (HPOS).
- `class_exists()` takes the fully-qualified class name; `use` aliases are not resolved.
- Block checkout DOM belongs to React: no `appendChild`, `insertAdjacentElement`, `removeChild` or `MutationObserver` moves there. Safe path: CSS `order`.
- Store API routes are matched under both `wc/store` and `wc/store/v1`, case-insensitively. The payment method of a checkout comes from the request or `chosen_payment_method`, never from a session key of our own.
- Every class file is loaded at `plugins_loaded` (a plugin update swaps the directory while requests run). A new class file is added to `tests/Unit/test-jp4wc-load-at-plugins-loaded.php`. A file under `includes/` may be loaded by the previous version's `includes()` during an update, so it checks `function_exists()` before calling a main-file function added in this version.
- State written back by a job (cron, settings save) is read from the database under a lock, not from an earlier read. Do not keep independent values in one option updated by read-modify-write.
- WP-Cron events are scheduled from an `init` check, not from the activation hook. A user-triggered job uses a hook of its own.
- Never instantiate `WC_Gateway_Paidy` outside WooCommerce's gateway list, and register order side effects once. Paidy IDs use base64url including `-`; the only guard is `WC_Gateway_Paidy::paidy_payment_api_url()`.
- Do not use `mb_*` functions other than `mb_strlen()` / `mb_substr()` / `mb_chr()` / `mb_ord()`, which `wp-includes/compat.php` defines (WordPress does not require mbstring). Safe path: `preg_match( '//u', $s )`.
- New user-facing strings use the text domain `woocommerce-for-japan`. A `.po` is not resynced wholesale in a feature PR. The settings app's JavaScript strings are translated from `i18n/woocommerce-for-japan-ja-<md5>.json`; check that file before reporting a missing JS translation.
- `wp_login_failed` is fired by `wp_authenticate()` itself, so XML-RPC and the WooCommerce login forms reach it as well as wp-login.php.
- WooCommerce bundles Jetpack's connection package, so `class_exists()` on `Automattic\Jetpack\Connection\*` does not mean Jetpack is active. A `.htaccess` rule on wp-login.php or a plugin that only moves the login page does not protect xmlrpc.php.
- `jp4wc_security_check_updates_automatically` and `jp4wc_security_findings_updated` are used by JP4WC Pro: their names, arguments and when they fire do not change (CLAUDE.md, #300).
- `@since` and "since x.y.z" name the first release tag that will contain the change. That is the next version after the last pushed tag, which may be a minor or major step (the security check code says 3.0.0 after 2.9.20). Once a bump PR merges, the readme `Stable tag` already names it.

### Packaging and disclosure

- Anything local-only at the top level is listed in `.distignore` (the release ZIP and the WordPress.org deploy copy the tree with `rsync --exclude-from=.distignore`). `plugins/` never ships.
- The readme `== External Services ==` section must match what the code actually sends, when and where.
- What the free security check sends is built only by `JP4WC_Security_Scan::payload()`, which the consent screen also previews. A key sent from anywhere else, or one missing from the readme External Services list, is a defect.
- A new `consent_version` sent by JP4WC must already be in `Artws_Sec_Scan_Request::CONSENT_VERSIONS`, and a version a released JP4WC sends stays there.
- The terms of use and the privacy policy on wc.artws.info describe what the client sends, how long it is kept and how it is deleted, and the terms rely on the Security tab's link to them. A PR that changes any of these says in its description that both pages need the same change.

### Tests

- A fix comes with a test that fails without it. Calling a hook callback directly does not prove the hook is registered; Store API behaviour is tested with `rest_get_server()->dispatch()`.
- Never `markTestSkipped()` because a plugin class is missing: `require_once` it and assert `class_exists()`.
- A test passes for the wrong reason when WooCommerce catches the warning-turned-exception it expects, or when it sets up state the code no longer reads.

### plugins/artws-security-hub (wc.artws.info)

- Fail closed: a rate limit, token or index lookup that cannot be read answers 500 / 503, never "allowed", 404 or 401.
- Registrations are per site token. No request may read, change or use up the rate limit of a registration whose token it did not send, and a token is served only with a `site_url` of the same site.
- A change to the JSON exchanged with the client changes the Schemas and samples in `tests/fixtures/security-feed/` in the same PR, and both test suites pass.
- `dbDelta()` never drops or changes an index: a table version that does runs `DROP INDEX` itself and records the version only once the change is seen.
- Code under test uses no explicit transactions (the WordPress test library wraps each test in one); undo with compensating writes.
- A change that needs a step on the server adds it to `DEPLOY.md`.
- `hosting/pressable/custom-redirects.php` repeats the feed file names and the default feed directory: a change to `Artws_Sec_Feed_Publisher::FILES` / `MANIFEST` or to that directory changes it in the same PR. It never redirects (the client sends `redirection => 0`) and never sends `Content-Encoding` with a `.json.gz`.
