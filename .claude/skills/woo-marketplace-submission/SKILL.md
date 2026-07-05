---
name: woo-marketplace-submission
description: >
  Skill covering the full process from plugin submission to the WooCommerce.com Marketplace through
  review approval and post-release ongoing operations. Covers all fields of the Vendor Dashboard's
  Submit Product form (Name, Short description, Best features, Benefits, Rationale, Monthly sales,
  Competitive comparison, Testing instructions, Notes for reviewers, etc.) with appropriate English
  text generation, submission package preparation (ZIP naming, changelog format, version consistency),
  response strategies for the 4-stage review process (Business/Code/UX/Launch preparations), pricing rules
  (70/30 revenue split, SaaS Billing API), Freemium model configuration, post-release update
  obligations (minimum every 6 months, quarterly major recommended), and documentation requirements.
  Use when keywords like "marketplace submission", "Woo submission", "review handling", "Vendor Dashboard",
  "submission form", "Submit Product", "pricing", "revenue sharing", "Freemium", "update obligations",
  "trademark guidelines", "SaaS Billing", "submission form English", or "application form" appear.
  Also reference proactively when consulted about WooCommerce plugin sales strategy, business model
  design, or filling out submission forms.
---

# WooCommerce Marketplace Submission & Operations

After completing Vendor registration, this covers the full process from product submission through
review approval and post-release operations.

---

## Generating Submission Form Text

For all fields of the Submit Product form in the Vendor Dashboard,
gather plugin information and generate appropriate English text optimized for passing review.

Covers all sections: Product Details (Name, Category, Short description), Business Details (Best features,
Benefits, Rationale, Monthly sales, Competitive comparison), Pricing, Languages,
Integrations, Testing (Instructions, Video, Demo URL, Credentials),
Product Upload (Slug), and Notes for reviewers.

Usage: Say "create the submission form content" or "write the Submit Product English text".

See: `references/submission-form.md`

---

## Preparing the Submission Package

### ZIP File Requirements

WooCommerce.com validates the name and folder structure of uploaded ZIP files.

```
my-extension.zip
└── my-extension/           # Directory name = plugin slug
    ├── my-extension.php    # Main file name = directory name
    ├── changelog.txt       # Required
    ├── readme.txt
    ├── includes/
    ├── build/              # Built JS/CSS (exclude source maps)
    ├── assets/
    ├── languages/
    └── ...
```

When uploading via Vendor Dashboard, the expected ZIP file name is displayed.
If the name does not match, an upload error will occur.

### Automating ZIP Creation

```bash
#!/bin/bash
# build-zip.sh

PLUGIN_SLUG="my-extension"
VERSION=$(grep "Version:" "${PLUGIN_SLUG}.php" | awk '{print $NF}')

# Build
npm ci
npm run build
composer install --no-dev --optimize-autoloader

# Create ZIP (excluding unnecessary files)
zip -r "${PLUGIN_SLUG}.zip" . \
  -x ".git/*" \
  -x ".github/*" \
  -x "node_modules/*" \
  -x "tests/*" \
  -x "src/*" \
  -x ".wp-env*" \
  -x "phpcs.xml" \
  -x "phpstan.neon" \
  -x ".eslintrc*" \
  -x "*.config.ts" \
  -x "*.config.js" \
  -x "tsconfig.json" \
  -x "build-zip.sh" \
  -x ".editorconfig" \
  -x "*.map"

echo "Created ${PLUGIN_SLUG}.zip (v${VERSION})"
```

### changelog.txt Format

```
*** My Extension Changelog ***

= 1.1.0 - 2025-06-01 =
* Feature - Added new payment method support
* Tweak - Improved order processing performance
* Fix - Resolved checkout validation issue with block editor

= 1.0.1 - 2025-05-15 =
* Fix - Fixed compatibility issue with WooCommerce 10.9
* Fix - Corrected translation string for Japanese locale

= 1.0.0 - 2025-04-01 =
* Feature - Initial release
```

Entry types: `Feature`, `Tweak`, `Fix`, `Dev`, `Update`

The version number must match in all three places:
1. The `Version:` field in the plugin header
2. The latest entry in `changelog.txt`
3. The version number entered when uploading

A mismatch will cause an upload error.

---

## Review Process

After submission, you must pass through 4 stages of review:

### 1. Automated Testing (Immediate)

Upon submission, QIT automatically runs the following tests:
- Activation Test
- Security Test
- Malware Test

You cannot enter the review queue without passing these.
Results appear in the Submission progress tab of the Vendor Dashboard.

QIT test result links are temporary signed URLs and may expire.
Re-run the tests if fresh results are needed.

### 2. Business Review (Up to 30 days for a formal decision)

The review team evaluates:

**Revenue sharing model**: The marketplace uses a 70/30 revenue split (vendor 70%, Woo 30%).
They verify that the product is structured to share revenue.

**Pricing**: The marketplace price must be no higher than prices on other sales channels
(your own site, etc.). If it's $99 on your own site, it must be $99 or less on the marketplace.

**Trademark guidelines**: Whether the plugin name violates Woo's trademark guidelines.
Pay attention to the rules about including "WooCommerce" in the plugin name.

**Product uniqueness**: Whether it overlaps too much with existing marketplace products.
Whether differentiating factors are clear.

**Prohibited items**:
- Upsell links to sites outside the marketplace
- Affiliate links
- Spam links
- Links directing to other marketplaces

### 3. Code Review

Checks the submission code for originality, security, and compliance with WordPress/WooCommerce quality standards:

- Code is original
- Complies with security best practices
- WordPress / WooCommerce coding standards compliance
- HPOS compatibility
- Appropriate data validation/sanitization

### 4. UX Review

Since 2024, UX review has been added to the process, extending the review period.

Items checked:
- Whether the product's critical flows work correctly
- Whether UX guidelines are followed (menu placement, UI components, responsive, etc.)
- Whether the setup flow is intuitive
- Accessibility support
- Whether it aligns with WooCommerce's look and feel
- **Documentation completeness**: If documentation is not published or prepared on the WooCommerce official documentation portal, the UX review will be blocked. Creating and publishing documentation before the UX review begins is a mandatory requirement after passing code review.

Tips for passing the UX review quickly:
- **Publish documentation first**: If documentation is not on the WooCommerce official documentation portal before the UX review, the review stops there (this has happened)
- Thoroughly test critical flows in-house beforehand
- Refer to WooCommerce Core's critical flow definitions
- Maximize the use of existing WordPress/WooCommerce UI components

### Handling Feedback

Feedback may be returned during review:
- Check feedback in the Submission progress tab of the Vendor Dashboard
- After making corrections, resubmit (ZIP replacement is only available when status is "Changes required")
- For other statuses, request a status change via comments
- Incorporate all previous feedback before resubmitting

---

## Pricing and Business Model

### Revenue Sharing

- Vendor: 70%
- WooCommerce: 30%

### Pricing Strategy

**Annual subscription (standard model)**:
The marketplace's standard pricing model. Provides support and updates with an annual license.

**Monthly subscription**:
For monthly pricing, implementing the SaaS Billing API is required. API keys and sandbox access
are granted manually after product submission.

```
For monthly subscriptions:
→ SaaS Billing API implementation is required
→ API keys/sandbox are granted after submission
→ Design so that monthly total payments per user are higher than annual
```

**Tiered pricing (Standard, Pro, Enterprise, etc.)**:
For differentiation by feature restrictions or site count, each tier is implemented
via separate plugin files or license key-based feature control.

### Price Alignment Across Channels

Marketplace price ≤ price on other channels

If also selling on your own site, the price cannot be lower than the marketplace price.
Either make the marketplace the cheapest channel or use the same price.

---

## Freemium Model

A combination of a free version (WordPress.org) and a paid version (WooCommerce.com).

### Submission Flow

For Freemium, two separate submissions are required:

1. **Free version**: Submit to the WordPress.org plugin directory
2. **Paid version**: Submit to the WooCommerce.com marketplace

Each passes through independent review processes.

### Design Pattern

```php
// Free version main file
// Provides basic features, natural path to upgrade to Pro

if ( ! defined( 'MY_EXTENSION_PRO' ) ) {
    // Display feature limitations for free version
    add_action( 'my_extension_settings_after', function() {
        echo '<div class="my-extension-upgrade-notice">';
        printf(
            /* translators: %s: upgrade URL */
            esc_html__( 'Unlock advanced features with %s', 'my-extension' ),
            '<a href="https://woocommerce.com/products/my-extension/">'
                . esc_html__( 'My Extension Pro', 'my-extension' )
                . '</a>'
        );
        echo '</div>';
    });
}
```

```php
// Paid version main file
// Includes free version OR stands alone as a completely separate plugin
define( 'MY_EXTENSION_PRO', true );

// Load Pro features
require_once plugin_dir_path( __FILE__ ) . 'includes/pro/class-pro-features.php';
```

### Important Notes

- Upsell paths from the free version only allow URLs within the marketplace
- External affiliate links and tracking links are prohibited
- Do not display excessive ads/banners in the free version
- The free version alone must provide practical value

---

## Submission Steps (Vendor Dashboard)

1. Log in to the Vendor Dashboard
2. Navigate to **Submissions > Submit Product**
3. Select product type (Extension / Theme / Integration)
4. Enter product information:
   - Plugin name (compliant with trademark guidelines)
   - Description
   - Category
   - Pricing
   - Screenshots / Demo video
   - Demo site URL (important for UX verification by the review team)
   - Testing instructions (instructions for the review team)
5. Upload the ZIP file
6. Submit

### Preparing a Demo Environment

The review team will actually interact with the product to verify UX, so prepare a demo environment:

- WooCommerce + product installed and ready
- Pre-populated with test data (products, orders, etc.)
- Admin account credentials provided
- wp-env or InstaWP are convenient for demo environments

---

## Post-Release Operations

### Update Obligations

Products listed on the marketplace require ongoing maintenance:

- **Minimum update every 6 months**: Products without updates for 6+ months are subject to delisting
- **Quarterly major releases recommended**: Synchronized with the WooCommerce Core release calendar
- **Security fixes**: Emergency releases as needed
- **Minor improvements**: Monthly or as features/fixes are ready

### Version Upload Flow

1. Confirm all QIT tests pass (Quality Insights menu in Vendor Dashboard)
2. Navigate to the **Versions** tab in Vendor Dashboard
3. Upload the new version ZIP
4. Automated tests (Activation / Security / Malware) run
5. After passing tests, automatically deployed

Upload error causes:
- ZIP file name mismatch
- Missing or incorrectly formatted `changelog.txt`
- Invalid changelog format
- Version mismatch between plugin header and version entered at upload
- Version mismatch between header and `changelog.txt`

### Keeping Up with WooCommerce Core Releases

WooCommerce releases major versions approximately quarterly.
Test with beta/RC versions before new version releases to confirm compatibility:

```bash
# Testing with beta version
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --woocommerce_version=rc

# Or specify in wp-env
# .wp-env.override.json
{
  "plugins": [
    ".",
    "https://github.com/woocommerce/woocommerce/releases/download/11.0.0-beta.1/woocommerce.zip"
  ]
}
```

### Support

Marketplace vendors are obligated to provide support to purchasers:
- Tickets come via the WooCommerce.com helpdesk
- Aim for initial response within 24 hours
- Enrich documentation to increase the proportion of self-service resolution

---

## Documentation Requirements

### Product Page Documentation

Documentation displayed on the marketplace product page:
- Installation instructions
- Initial setup guide
- Feature usage
- FAQ
- Troubleshooting

### Developer Documentation

Explanation of hooks, filters, and template overrides:

```markdown
## Hooks Reference

### Actions
- `my_extension_before_process` — Fires before processing starts
  - Parameters: `$order` (WC_Order)
- `my_extension_after_process` — Fires after processing completes
  - Parameters: `$order` (WC_Order), `$result` (array)

### Filters
- `my_extension_default_settings` — Filters default settings values
  - Parameters: `$settings` (array)
  - Return: array
```

### Publishing the Changelog

The contents of `changelog.txt` are also displayed on the product page,
so write them in a way users can understand. Focus on changes that affect
users rather than internal technical changes.

---

## Timeline from Submission to Release

```
Submission
 ├─ Automated testing (immediate to a few hours)
 ├─ Business review (up to 30 days)
 ├─ Code review (1–3 weeks including feedback)
 ├─ UX review (1–2 weeks)
 ├─ Feedback response / resubmission (0 to several weeks)
 └─ Approval → Launch preparation → Go live
```

Allow 1–3 months from initial submission to going live as a safe estimate.
Prompt responses to feedback are key to shortening this period.

---

## Common Review Rejection Reasons and Remedies

| Rejection Reason | Remedy |
|-----------------|--------|
| HPOS incompatibility | Declare `declare_compatibility('custom_order_tables')` and eliminate direct DB queries |
| Security issues | Sanitize all input, escape all output, nonce validation, capability check |
| Trademark violation | Remove improper use of WooCommerce trademarks from plugin name |
| External links | Remove upsell/affiliate links to sites outside the marketplace |
| Top-level menu | Change to a submenu under WooCommerce |
| Custom telemetry | Remove custom tracking/telemetry code |
| Version mismatch | Unify versions in header, changelog, and at upload time |
| Missing changelog | Create `changelog.txt` in the correct format |
| Internationalization issues | Wrap all text in translation functions, verify text domain match |
| No demo environment | Prepare a demo site for the review team |
| Documentation not published | Before UX review, create and publish documentation in Gutenberg block format on the WooCommerce official documentation portal |
