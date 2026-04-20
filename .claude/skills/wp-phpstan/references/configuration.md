# PHPStan configuration (WordPress)

This reference documents a minimal, WordPress-friendly PHPStan setup and baseline workflow.

## Minimal `phpstan.neon` template

Use the repo’s existing layout. The example below is intentionally conservative and should be adapted to the project’s actual directories.

```neon
# Include the baseline only if the file exists.
includes:
    - phpstan-baseline.neon

parameters:
    level: 5
    paths:
        - src/
        - includes/

    excludePaths:
        - vendor/
        - vendor-prefixed/
        - node_modules/
        - tests/

    ignoreErrors:
        # Add targeted exceptions only when necessary.
```

Guidelines:

- Prefer analyzing first-party code only.
- Exclude anything generated or vendored.
- Keep `ignoreErrors` patterns narrow and grouped by dependency.
- Only add `ignoreErrors` patterns for third-party symbols (e.g. WC classes) when those packages are **not** installed via Composer. If WooCommerce is available in `vendor/`, PHPStan resolves the symbols directly — unmatched ignore patterns then cause "ignored error pattern was not matched" errors.

## Plugin constants bootstrap

Plugin constants (e.g. `MY_PLUGIN_URL`) are often defined inside a conditional block such as `if ( is_woocommerce_active() )`. PHPStan cannot resolve constants declared only at runtime. Create a `phpstan-bootstrap.php` file at the project root and reference it in `bootstrapFiles`:

```php
<?php
// phpstan-bootstrap.php — defines plugin constants for static analysis only
define( 'MY_PLUGIN_PATH', __DIR__ );
define( 'MY_PLUGIN_URL', 'http://example.com/wp-content/plugins/my-plugin/' );
define( 'MY_PLUGIN_VERSION', '1.0.0' );
```

```neon
# phpstan.neon
parameters:
    bootstrapFiles:
        - vendor/autoload.php
        - phpstan-bootstrap.php
```

Do not commit actual values with production URLs — the values are only used during static analysis and are never executed in production.

## Baseline workflow

Baselines help you adopt PHPStan in legacy code without accepting new regressions.

```bash
# Generate a baseline (explicit filename)
vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon

# Update an existing baseline (defaults)
vendor/bin/phpstan analyse --generate-baseline
```

Best practices:

- Avoid adding new errors to the baseline; fix the new code instead.
- Treat baseline changes like code changes: review in PRs.
- Chip away at the baseline gradually (remove entries as you fix root causes).
