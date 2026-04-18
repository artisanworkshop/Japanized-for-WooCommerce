---
name: woo-marketplace-qit
description: >
  Skill for quality testing of plugins for the WooCommerce.com Marketplace. Covers all QIT
  (Quality Insights Toolkit) test suites (Activation, Security, PHPStan, PHP Compatibility,
  Malware, Woo E2E, Woo API), how to run them locally, GitHub Actions CI integration, writing
  custom E2E tests, and coding standards compliance via PHPCS/ESLint. Use when keywords like
  "QIT", "marketplace testing", "quality testing", "security scan", "PHPStan", "Woo E2E",
  "plugin testing", "pre-submission", "pre-submission check", "PHPCS", or "coding standards"
  appear. Also reference proactively when designing CI/CD pipelines or test strategies for
  WooCommerce plugins.
---

# WooCommerce Marketplace QIT & Quality Assurance

QIT (Quality Insights Toolkit) is a testing platform developed by WooCommerce that acts as a
quality gate for both initial marketplace submissions and ongoing version updates. Since failing
any test blocks the submission from progressing, it is essential to integrate QIT from the
start of development.

---

## QIT Test Suite Overview

Managed tests required by the marketplace:

| Test | Description | Required |
|------|-------------|----------|
| **Activation Test** | Verifies the plugin activates without errors or warnings in a clean WP+WC environment | ✅ |
| **Security Test** | Semgrep-based security scan and coding best practices check | ✅ |
| **PHPStan Test** | Static analysis to detect bugs and type errors | ✅ |
| **PHP Compatibility Test** | Compatibility across different PHP versions (8.2–8.3+) | ✅ |
| **Malware Test** | Detection of malicious code | ✅ |
| **Woo E2E Test** | Runs WooCommerce Core E2E tests (Playwright) with the extension active | ✅ |
| **Woo API Test** | Runs WooCommerce Core API tests with the extension active | ✅ |
| **Custom E2E Test** | Playwright E2E tests written by the developer | Recommended |

Failing the Activation, Security, or Malware tests also blocks version update deployments.

---

## QIT CLI Setup

### Installation

```bash
composer require --dev woocommerce/qit-cli
```

### Authentication

Authenticate with your WooCommerce.com Partner Developer account:

```bash
./vendor/bin/qit
# A browser window opens and begins the authentication flow on WooCommerce.com
```

Once logged in with your Vendor account, you can run QIT against plugins that have been
submitted to or are listed on the marketplace.

### Basic Test Execution

```bash
# Activation test
./vendor/bin/qit run:activation my-extension --zip=./my-extension.zip

# Security test
./vendor/bin/qit run:security my-extension --zip=./my-extension.zip

# PHPStan test
./vendor/bin/qit run:phpstan my-extension --zip=./my-extension.zip

# PHP Compatibility test
./vendor/bin/qit run:phpcompatibility my-extension --zip=./my-extension.zip

# Malware test
./vendor/bin/qit run:malware my-extension --zip=./my-extension.zip

# Woo E2E test (runs WooCommerce Core E2E with the plugin active)
./vendor/bin/qit run:e2e my-extension --zip=./my-extension.zip

# Woo API test
./vendor/bin/qit run:api my-extension --zip=./my-extension.zip
```

### Environment Customization

```bash
# Specify PHP / WordPress / WooCommerce versions
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --php_version=8.3 \
  --wordpress_version=6.7 \
  --woocommerce_version=9.6

# Activate other extensions simultaneously for compatibility testing
./vendor/bin/qit run:activation my-extension \
  --zip=./my-extension.zip \
  --with-extension=woocommerce-subscriptions \
  --with-extension=woocommerce-payments
```

### Compatibility Testing with Extension Sets

QIT provides sets of top marketplace extensions to simplify compatibility testing:

```bash
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --test-package=woocommerce/checkout-tests
```

Use this feature to fulfill the pre-submission checklist requirement of verifying compatibility
with top marketplace extensions.

---

## Local Test Environment (No Authentication Required)

QIT's local test environment can be used without a WooCommerce.com connection:

```bash
# Run tests in the local environment
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --local
```

Useful for rapid feedback loops during development. Note that installing premium marketplace
plugins into the test environment does require authentication.

---

## Custom E2E Tests (Playwright)

QIT supports Playwright-based custom tests. By following the standardized format for test
packages, you can also participate in cross-plugin compatibility testing.

### Test Structure

```
tests/
├── e2e/
│   ├── playwright.config.ts
│   ├── example.spec.ts
│   └── utils/
│       └── helpers.ts
```

### Basic playwright.config.ts

```typescript
import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  timeout: 60000,
  retries: 1,
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8889',
    storageState: process.env.STORAGE_STATE || undefined,
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium' },
    },
  ],
});
```

### Example: Settings Page Test

```typescript
import { test, expect } from '@playwright/test';

test.describe( 'My Extension Settings', () => {
  test.beforeEach( async ( { page } ) => {
    // Log in to WP Admin
    await page.goto( '/wp-login.php' );
    await page.fill( '#user_login', 'admin' );
    await page.fill( '#user_pass', 'password' );
    await page.click( '#wp-submit' );
    await page.waitForURL( '**/wp-admin/**' );
  });

  test( 'settings page loads without errors', async ( { page } ) => {
    await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=my_extension' );
    await expect( page.locator( '.woocommerce' ) ).toBeVisible();
    // Verify no console errors
    const errors: string[] = [];
    page.on( 'console', msg => {
      if ( msg.type() === 'error' ) errors.push( msg.text() );
    });
    await page.waitForTimeout( 2000 );
    expect( errors ).toHaveLength( 0 );
  });

  test( 'can save settings', async ( { page } ) => {
    await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=my_extension' );
    await page.fill( '#my_extension_api_key', 'test-key-123' );
    await page.click( '.woocommerce-save-button' );
    await expect( page.locator( '.updated' ) ).toBeVisible();
    // Verify the value is retained
    const value = await page.inputValue( '#my_extension_api_key' );
    expect( value ).toBe( 'test-key-123' );
  });
});
```

### Example: Checkout Flow Test

```typescript
test.describe( 'Checkout Integration', () => {
  test( 'extension feature works on block checkout', async ( { page } ) => {
    // Add a product to the cart
    await page.goto( '/shop/' );
    await page.click( '.add_to_cart_button' );
    await page.waitForTimeout( 1000 );

    // Navigate to block checkout
    await page.goto( '/checkout/' );
    await expect( page.locator( '.wc-block-checkout' ) ).toBeVisible();

    // Verify extension element is displayed
    await expect(
      page.locator( '[data-block-name="my-extension/checkout-field"]' )
    ).toBeVisible();
  });
});
```

### Running Custom Tests with QIT

```bash
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --test-package=./tests/e2e
```

---

## Coding Standards Configuration

### PHPCS (PHP CodeSniffer)

```json
// composer.json
{
  "require-dev": {
    "squizlabs/php_codesniffer": "^3.7",
    "wp-coding-standards/wpcs": "^3.0",
    "phpcompatibility/phpcompatibility-wp": "^2.1",
    "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
  },
  "scripts": {
    "phpcs": "phpcs",
    "phpcbf": "phpcbf"
  }
}
```

```xml
<!-- phpcs.xml -->
<?xml version="1.0"?>
<ruleset name="My Extension">
  <description>PHPCS ruleset for WooCommerce Marketplace extension</description>

  <file>.</file>

  <exclude-pattern>vendor/*</exclude-pattern>
  <exclude-pattern>node_modules/*</exclude-pattern>
  <exclude-pattern>build/*</exclude-pattern>
  <exclude-pattern>tests/*</exclude-pattern>

  <arg name="extensions" value="php"/>
  <arg name="colors"/>
  <arg value="sp"/>

  <!-- WordPress Coding Standards -->
  <rule ref="WordPress-Extra">
    <exclude name="WordPress.Files.FileName.InvalidClassFileName"/>
    <exclude name="WordPress.Files.FileName.NotHyphenatedLowercase"/>
  </rule>

  <!-- WooCommerce-specific -->
  <rule ref="WordPress.WP.I18n">
    <properties>
      <property name="text_domain" type="array">
        <element value="my-extension"/>
      </property>
    </properties>
  </rule>

  <!-- PHP Compatibility -->
  <config name="testVersion" value="8.2-"/>
  <rule ref="PHPCompatibilityWP"/>

  <!-- Minimum WP version -->
  <config name="minimum_supported_wp_version" value="6.7"/>
</ruleset>
```

### PHPStan

```neon
# phpstan.neon
includes:
  - vendor/phpstan/phpstan/conf/bleedingEdge.neon

parameters:
  level: 5
  paths:
    - my-extension.php
    - includes/
  excludePaths:
    - vendor/
    - node_modules/
    - build/
  scanDirectories:
    - vendor/woocommerce/
  bootstrapFiles:
    - vendor/autoload.php
  ignoreErrors:
    # WooCommerce dynamic methods
    - '#Call to an undefined method WC_Order::#'
```

```json
// Add to composer.json
{
  "require-dev": {
    "phpstan/phpstan": "^1.10",
    "phpstan/extension-installer": "^1.3",
    "szepeviktor/phpstan-wordpress": "^1.3"
  },
  "scripts": {
    "phpstan": "phpstan analyse --memory-limit=512M"
  }
}
```

### ESLint (JavaScript / TypeScript)

```json
// .eslintrc.json
{
  "extends": [
    "plugin:@woocommerce/eslint-plugin/recommended"
  ],
  "env": {
    "browser": true,
    "es2021": true
  },
  "parserOptions": {
    "ecmaVersion": "latest",
    "sourceType": "module",
    "ecmaFeatures": {
      "jsx": true
    }
  },
  "settings": {
    "react": {
      "version": "detect"
    }
  }
}
```

```json
// package.json
{
  "devDependencies": {
    "@woocommerce/eslint-plugin": "^2.2.0",
    "@wordpress/scripts": "^28.0.0"
  },
  "scripts": {
    "lint:js": "wp-scripts lint-js src/",
    "lint:css": "wp-scripts lint-style src/**/*.scss",
    "build": "wp-scripts build",
    "start": "wp-scripts start"
  }
}
```

---

## GitHub Actions CI Integration

### Basic CI Workflow

```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  phpcs:
    name: PHPCS
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer, cs2pr
      - run: composer install --no-progress
      - run: composer phpcs -- --report=checkstyle | cs2pr

  phpstan:
    name: PHPStan
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - run: composer phpstan

  eslint:
    name: ESLint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      - run: npm ci
      - run: npm run lint:js

  php-compatibility:
    name: PHP Compatibility
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install --no-progress
      - run: |
          ./vendor/bin/phpcs \
            --standard=PHPCompatibilityWP \
            --runtime-set testVersion ${{ matrix.php }} \
            --extensions=php \
            --ignore=vendor/,node_modules/,build/ \
            .

  activation-test:
    name: Activation Test
    runs-on: ubuntu-latest
    strategy:
      matrix:
        wc: ['9.0', 'latest']
        php: ['8.2', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci && npm run build
      - name: Install wp-env
        run: npm -g install @wordpress/env
      - name: Configure wp-env
        run: |
          cat > .wp-env.override.json << 'EOF'
          {
            "plugins": ["."],
            "env": {
              "tests": {
                "phpVersion": "${{ matrix.php }}"
              }
            }
          }
          EOF
      - name: Start wp-env
        run: wp-env start
      - name: Verify activation
        # Use ${{ github.event.repository.name }} instead of a hardcoded slug because
        # wp-env mounts the plugin using the repository directory name.
        run: |
          wp-env run tests-cli wp plugin activate ${{ github.event.repository.name }}
          wp-env run tests-cli wp plugin list --status=active --format=csv | grep ${{ github.event.repository.name }}
          # Verify no PHP errors
          wp-env run tests-cli wp eval "error_reporting(E_ALL); do_action('admin_init');" 2>&1 | grep -v "^$" && echo "OK" || exit 1
```

### Integrating QIT with GitHub Actions

```yaml
  qit-tests:
    name: QIT Tests
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - name: Build ZIP
        run: |
          npm ci && npm run build
          mkdir -p dist
          zip -r dist/my-extension.zip . \
            -x ".git/*" "node_modules/*" ".github/*" "tests/*" ".wp-env*"
      - name: Run QIT Activation
        env:
          QIT_TOKEN: ${{ secrets.QIT_TOKEN }}
        run: ./vendor/bin/qit run:activation my-extension --zip=dist/my-extension.zip
      - name: Run QIT Security
        env:
          QIT_TOKEN: ${{ secrets.QIT_TOKEN }}
        run: ./vendor/bin/qit run:security my-extension --zip=dist/my-extension.zip
```

---

## Pre-Submission Quality Checklist

Verify all of the following before submitting:

### Automated Tests
- [ ] PHPCS (WordPress-Extra) zero errors
- [ ] PHPStan level 5 or higher with zero errors
- [ ] ESLint zero errors
- [ ] PHP compatibility test passes for PHP 8.2 and 8.3
- [ ] Zero errors, warnings, or notices with WP_DEBUG enabled
- [ ] QIT Activation Test passes (clean WP+WC environment)
- [ ] QIT Security Test passes
- [ ] QIT Malware Test passes
- [ ] QIT Woo E2E Test passes (Core Critical Flows not broken)
- [ ] QIT Woo API Test passes

### Compatibility Tests
- [ ] Works with the latest stable WooCommerce release
- [ ] Works with the previous minor WooCommerce release
- [ ] Works with the latest stable WordPress release
- [ ] Co-existence tested with top marketplace extensions (Extension Sets)
- [ ] Verified with block-based Cart/Checkout
- [ ] Verified with classic Cart/Checkout (where applicable)

### Code Quality
- [ ] HPOS compatibility declared
- [ ] Blocks compatibility declared
- [ ] All inputs are sanitized
- [ ] All outputs are escaped
- [ ] Nonce verification implemented for all forms/AJAX
- [ ] Capability checks implemented for all admin operations
- [ ] Text domain matches directory name
- [ ] `Automattic\WooCommerce\Internal` namespace not used
- [ ] `changelog.txt` exists with the correct format
- [ ] Version numbers match in header and changelog

### UX
- [ ] No top-level menu items created
- [ ] Existing WordPress/WooCommerce UI components used
- [ ] Mobile responsive
- [ ] Setup flow is intuitive
- [ ] No ads, banners, or excessive branding
