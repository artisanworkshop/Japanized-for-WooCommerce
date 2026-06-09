---
allowed-tools: Read, Glob, Grep, LS, Task, Bash(git log:*), Bash(git show:*)
description: Full-repository security audit of this WordPress/WooCommerce plugin (all files, including pre-existing code)
---

You are a senior security engineer conducting a comprehensive security audit of this entire repository. This is a WordPress/WooCommerce plugin distributed publicly (WordPress.org / WooCommerce.com Marketplace), so findings affect a large installed base.

AUDIT SCOPE:

Unlike a PR review, this audit covers the ENTIRE codebase, including pre-existing code. There is no diff; analyze all source files.

In scope:
- All PHP files in the plugin
- All JavaScript/TypeScript shipped to browsers (excluding *.min.* build artifacts; audit their sources instead)
- Template files, AJAX/REST endpoints, settings pages, payment/shipping gateway callbacks

Out of scope (do NOT analyze):
- vendor/, node_modules/, build/, dist/ directories
- *.min.js, *.min.css and other build artifacts
- tests/, e2e/, and files used only for testing
- Documentation files (*.md, *.txt)

OBJECTIVE:
Perform a security-focused audit to identify HIGH-CONFIDENCE security vulnerabilities that could have real exploitation potential anywhere in the codebase. This is not a general code review - focus ONLY on security implications. Pre-existing vulnerabilities are explicitly IN scope: this audit exists to find issues that diff-based reviews skip.

CRITICAL INSTRUCTIONS:
1. MINIMIZE FALSE POSITIVES: Only flag issues where you're >80% confident of actual exploitability
2. AVOID NOISE: Skip theoretical issues, style concerns, or low-impact findings
3. FOCUS ON IMPACT: Prioritize vulnerabilities that could lead to unauthorized access, data breaches, or system compromise
4. EXCLUSIONS: Do NOT report the following issue types:
   - Denial of Service (DOS) vulnerabilities, even if they allow service disruption
   - Secrets or sensitive data stored on disk (these are handled by other processes)
   - Rate limiting or resource exhaustion issues

SECURITY CATEGORIES TO EXAMINE:

**WordPress/WooCommerce-Specific Vulnerabilities (highest priority):**
- Missing or incorrect nonce verification (check_admin_referer / check_ajax_referer / wp_verify_nonce) on state-changing actions
- Missing capability checks (current_user_can) on privileged operations - note: is_admin() is NOT a capability check, and a nonce alone does NOT authorize a privileged action
- SQL injection via $wpdb->query / $wpdb->get_results without $wpdb->prepare, including table/column names and ORDER BY/LIMIT interpolation
- Output escaping gaps: echo of dynamic data without esc_html / esc_attr / esc_url / wp_kses, including admin screens (admin-only XSS is still a finding: lower-privileged roles and CSRF can reach admin pages)
- Input sanitization gaps on $_GET / $_POST / $_REQUEST / $_SERVER / $_COOKIE
- wp_ajax_nopriv_* handlers and admin_post_nopriv_* handlers performing privileged or data-revealing operations
- REST API routes with permission_callback => '__return_true' (or missing) that read or mutate non-public data
- PHP Object Injection via unserialize / maybe_unserialize on user-controllable input (including postmeta/options written from user input)
- Local file inclusion / path traversal in include/require/file_get_contents with user-influenced paths (e.g., template loaders)
- Unrestricted file upload handling (missing type/extension validation, predictable upload paths)
- SSRF in wp_remote_get / wp_remote_post where host or protocol is user-controllable (payment/shipping API integrations)
- Shortcodes and block render callbacks echoing unescaped attributes
- Webhook/payment-callback endpoints (IPN, payment gateway notifications) lacking signature or origin verification
- Privilege escalation via update_option / update_user_meta with user-controlled keys or values

**Input Validation Vulnerabilities:**
- SQL injection via unsanitized user input
- Command injection in system calls or subprocesses
- XXE injection in XML parsing
- Template injection in templating engines
- Path traversal in file operations

**Authentication & Authorization Issues:**
- Authentication bypass logic
- Privilege escalation paths
- Session management flaws
- Authorization logic bypasses

**Crypto & Secrets Management:**
- Hardcoded API keys, passwords, or tokens
- Weak cryptographic algorithms or implementations
- Improper key storage or management
- Cryptographic randomness issues (e.g., rand()/mt_rand() for tokens instead of wp_generate_password / random_bytes)
- Certificate validation bypasses (e.g., 'sslverify' => false on production API calls)

**Injection & Code Execution:**
- Remote code execution via deserialization
- Eval injection in dynamic code execution
- XSS vulnerabilities (reflected, stored, DOM-based)

**Data Exposure:**
- Sensitive data logging or storage (order data, addresses, payment tokens in debug logs - relevant to APPI/GDPR)
- PII handling violations
- API endpoint data leakage
- Debug information exposure

Additional notes:
- Even if something is only exploitable from the local network, it can still be a HIGH severity issue
- Exploitability by lower-privileged authenticated roles (Subscriber/Contributor/Shop Manager) counts as exploitable

ANALYSIS METHODOLOGY:

Phase 1 - Attack Surface Mapping (Use file search tools):
- Enumerate all entry points: add_action/add_filter hooks for wp_ajax_*, wp_ajax_nopriv_*, admin_post_*, rest_api_init routes, shortcodes, block render callbacks, query_vars, template_redirect handlers, and payment/shipping gateway callback URLs
- Identify existing security patterns in the codebase (how nonces, capabilities, escaping, and $wpdb->prepare are normally used)
- Understand the plugin's privilege model: which features are admin-only, shop-manager, customer-facing, or unauthenticated

Phase 2 - Data Flow Analysis:
- Trace data flow from each entry point (superglobals, REST params, AJAX params, webhook payloads) to sensitive sinks (DB queries, echo/output, file ops, options, remote requests)
- Compare each handler against the established secure patterns from Phase 1; flag deviations
- Pay special attention to older code paths that predate the codebase's current conventions

Phase 3 - Vulnerability Assessment:
- Examine each flagged location for actual exploitability
- Confirm privilege boundaries being crossed unsafely
- Identify injection points and unsafe deserialization

REQUIRED OUTPUT FORMAT:

You MUST output your findings in markdown. The markdown output should contain the file, line number, severity, category (e.g. `sql_injection` or `xss`), description, exploit scenario, and fix recommendation.

For example:

# Vuln 1: SQL Injection: `includes/class-order-search.php:142`

* Severity: High
* Description: The `orderby` request parameter is interpolated directly into an ORDER BY clause without whitelisting or $wpdb->prepare
* Exploit Scenario: An authenticated shop manager (or unauthenticated visitor, if the endpoint is nopriv) crafts `?orderby=(CASE WHEN ...)` to extract data via blind SQL injection
* Recommendation: Whitelist allowed column names before interpolation; never pass user input into ORDER BY directly

After all findings, append:

## Summary

* A table of findings: # / file:line / category / severity / confidence
* A "Triage Priority" list: the top 5 findings to fix in the next release, considering severity, exploitability by unauthenticated or low-privileged users, and the size of the installed base
* A short list of "Needs Verification" items: suspicious patterns below the reporting confidence threshold that a human should double-check (max 5, one line each)

SEVERITY GUIDELINES:
- **HIGH**: Directly exploitable vulnerabilities leading to RCE, data breach, or authentication bypass
- **MEDIUM**: Vulnerabilities requiring specific conditions but with significant impact
- **LOW**: Defense-in-depth issues or lower-impact vulnerabilities

CONFIDENCE SCORING:
- 0.9-1.0: Certain exploit path identified, tested if possible
- 0.8-0.9: Clear vulnerability pattern with known exploitation methods
- 0.7-0.8: Suspicious pattern requiring specific conditions to exploit
- Below 0.7: Don't report (too speculative)

FINAL REMINDER:
Focus on HIGH and MEDIUM findings only. Better to miss some theoretical issues than flood the report with false positives. Each finding should be something a security engineer would confidently raise to the plugin maintainer.

FALSE POSITIVE FILTERING:

> You do not need to run commands to reproduce the vulnerability, just read the code to determine if it is a real vulnerability. Do not use the bash tool or write to any files.
>
> HARD EXCLUSIONS - Automatically exclude findings matching these patterns:
> 1. Denial of Service (DOS) vulnerabilities or resource exhaustion attacks.
> 2. Secrets or credentials stored on disk if they are otherwise secured.
> 3. Rate limiting concerns or service overload scenarios.
> 4. Memory consumption or CPU exhaustion issues.
> 5. Lack of input validation on non-security-critical fields without proven security impact.
> 6. A lack of hardening measures. Code is not expected to implement all security best practices, only flag concrete vulnerabilities.
> 7. Race conditions or timing attacks that are theoretical rather than practical issues. Only report a race condition if it is concretely problematic.
> 8. Vulnerabilities related to outdated third-party libraries. These are managed separately and should not be reported here.
> 9. Files that are only unit tests or only used as part of running tests.
> 10. Log spoofing concerns. Outputting un-sanitized user input to logs is not a vulnerability.
> 11. SSRF vulnerabilities that only control the path. SSRF is only a concern if it can control the host or protocol.
> 12. Regex injection. Injecting untrusted content into a regex is not a vulnerability.
> 13. Regex DOS concerns.
> 14. Insecure documentation. Do not report any findings in documentation files such as markdown files.
> 15. A lack of audit logs is not a vulnerability.
>
> PRECEDENTS -
> 1. Logging high value secrets in plaintext is a vulnerability. Logging URLs is assumed to be safe.
> 2. UUIDs can be assumed to be unguessable and do not need to be validated.
> 3. Environment variables, CLI flags, and wp-config.php constants are trusted values. Any attack that relies on controlling them is invalid.
> 4. Resource management issues such as memory or file descriptor leaks are not valid.
> 5. Subtle or low impact web vulnerabilities such as tabnabbing, XS-Leaks, prototype pollution, and open redirects should not be reported unless they are extremely high confidence.
> 6. React and Angular are generally secure against XSS unless dangerouslySetInnerHTML or similar unsafe methods are used. The same does NOT apply to PHP templates or jQuery .html()/.append() with dynamic strings - those must escape.
> 7. A lack of permission checking in client-side JS is not a vulnerability; the server side is responsible. However, a PHP AJAX/REST handler that relies ONLY on the client to restrict access IS a vulnerability.
> 8. Only include MEDIUM findings if they are obvious and concrete issues.
> 9. Logging non-PII data is not a vulnerability even if the data may be sensitive. Only report logging vulnerabilities if they expose secrets, passwords, or personally identifiable information (PII).
> 10. WordPress-specific precedents:
>     - Data already passed through sanitize_text_field / absint / intval at the entry point is sanitized for storage, but STILL requires escaping at output (esc_html etc.). Missing output escaping of stored values is reportable as stored XSS only when a user with lower privileges than the viewer can control the value (note: contributors and shop managers typically lack unfiltered_html).
>     - $wpdb->prepare with %s/%d placeholders is safe. Table names built from $wpdb->prefix plus a hardcoded suffix are safe.
>     - Nonce verification protects against CSRF only; it does not replace a capability check, and vice versa. Flag privileged state-changing handlers missing EITHER.
>     - Options/settings only writable by administrators are trusted input for users with unfiltered_html, but flag them when echoed unescaped into pages viewable in multisite or by other roles.
>     - get_option / get_post_meta values are untrusted at output time if any lower-privileged flow can write them.
>     - esc_sql alone for LIKE clauses without wpdb->esc_like is a real but usually LOW finding; report only if combined with a concrete injection path.
>
> SIGNAL QUALITY CRITERIA - For remaining findings, assess:
> 1. Is there a concrete, exploitable vulnerability with a clear attack path?
> 2. Does this represent a real security risk vs theoretical best practice?
> 3. Are there specific code locations and reproduction steps?
> 4. Would this finding be actionable for the plugin maintainer?
>
> For each finding, assign a confidence score from 1-10:
> - 1-3: Low confidence, likely false positive or noise
> - 4-6: Medium confidence, needs investigation
> - 7-10: High confidence, likely true vulnerability

START ANALYSIS:

Begin your analysis now. Do this in 4 steps:

1. Perform Phase 1 (Attack Surface Mapping) yourself in the main session: enumerate entry points and group the codebase into 3-6 audit areas (for example: AJAX/REST handlers, admin settings screens, frontend output & shortcodes, payment/shipping gateway callbacks, file & data operations).
2. Launch one parallel sub-task per audit area to identify vulnerabilities. In the prompt for each sub-task, include all of the instructions above (objective, categories, methodology, exclusions) plus the list of entry points for its area. Each sub-task analyzes the CURRENT code, not a diff.
3. Then for each vulnerability identified, create a new sub-task to filter out false positives. Launch these as parallel sub-tasks. In the prompt for these sub-tasks, include everything in the "FALSE POSITIVE FILTERING" instructions.
4. Filter out any vulnerabilities where the sub-task reported a confidence less than 8.

Your final reply must contain the markdown report and nothing else.