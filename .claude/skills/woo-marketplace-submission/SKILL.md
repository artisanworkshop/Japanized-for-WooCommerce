---
name: woo-marketplace-submission
description: >
  Skill covering everything from submitting a plugin to the WooCommerce.com Marketplace through
  passing review to ongoing post-release operations. Covers generating appropriate English text for
  all Vendor Dashboard Submit Product form fields (Name, Short description, Best features,
  Benefits, Rationale, Competitive comparison, Testing instructions, Notes for reviewers, etc.),
  preparing the submission package (ZIP naming, changelog format, version consistency), strategies
  for the three-stage review process (Business / Code / UX), pricing rules (70/30 revenue split,
  SaaS Billing API), Freemium model configuration, post-release update obligations (minimum every
  6 months, quarterly major releases recommended), and documentation requirements.
  Use when keywords like "marketplace submission", "submit to Woo", "review process",
  "Vendor Dashboard", "submission form", "Submit Product", "pricing", "revenue split",
  "Freemium", "update obligations", "trademark guidelines", "SaaS Billing",
  "submission form text", or "application form" appear. Also reference proactively when
  consulting on sales strategy, business model design, or filling out the submission form for
  a WooCommerce plugin.
---

# WooCommerce Marketplace Submission & Operations

This document explains the full process from product submission to passing review and
post-release operations, starting after Vendor registration is complete.

---

## Generating Submission Form Text

For all fields in the Vendor Dashboard's Submit Product form, gather information about the
plugin and generate appropriate English text that is likely to pass review.

Covers all sections: Product Details (Name, Category, Short description), Business Details
(Best features, Benefits, Rationale, Monthly sales, Competitive comparison), Pricing,
Languages, Integrations, Testing (Instructions, Video, Demo URL, Credentials),
Product Upload (Slug), and Notes for reviewers.

Usage: Ask "write the submission form content" or "write the Submit Product English text."

See: `references/submission-form.md`

---

## Preparing the Submission Package

### ZIP File Requirements

WooCommerce.com validates the name and folder structure of the uploaded ZIP file.

```
my-extension.zip
└── my-extension/           # Directory name = plugin slug
    ├── my-extension.php    # Main file name = directory name
    ├── changelog.txt       # Required
    ├── readme.txt
    ├── includes/
    ├── build/              # Compiled JS/CSS (exclude source maps)
    ├── assets/
    ├── languages/
    └── ...
```

After uploading in the Vendor Dashboard, the expected ZIP filename is shown.
An upload error will occur if the filename does not match.

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

# Create ZIP (exclude unnecessary files)
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
* Fix - Fixed compatibility issue with WooCommerce 9.5
* Fix - Corrected translation string for Japanese locale

= 1.0.0 - 2025-04-01 =
* Feature - Initial release
```

Entry types: `Feature`, `Tweak`, `Fix`, `Dev`, `Update`

The version number must match in all three of the following locations:
1. The `Version:` field in the plugin header
2. The latest entry in `changelog.txt`
3. The version number entered at upload time

A mismatch in any of these will cause an upload error.

---

## Review Process

After submission, the plugin must pass three stages of review:

### 1. Automated Tests (Immediate)

Upon submission, QIT automatically runs the following tests:
- Activation Test
- Security Test
- Malware Test

The plugin cannot enter the review queue unless these pass.
If they fail, results appear in the Submission progress tab of the Vendor Dashboard.

QIT test result links are temporary signed URLs and may expire.
Re-run the tests to get fresh results when needed.

### 2. Business Review (Up to 30 days for a decision)

The review team evaluates the following:

**Revenue sharing model**: The marketplace uses a 70/30 revenue split (vendor 70%, Woo 30%).
Reviewers confirm the product is structured to share revenue properly.

**Pricing**: The marketplace price must be equal to or less than prices on other sales
channels (your own site, etc.). If your site charges $99, the marketplace must also be $99 or less.

**Trademark guidelines**: Whether the plugin name violates Woo trademark guidelines.
Pay attention to the rules around including "WooCommerce" in the plugin name.

**Product uniqueness**: Whether the product overlaps too heavily with existing marketplace
products. Differentiation must be clearly evident.

**Prohibited practices**:
- Upsell links to sites outside the marketplace
- Affiliate links
- Spam links
- Redirecting to other marketplaces

### 3. Code Review

Verifies code originality, security, and compliance with WordPress/WooCommerce quality standards:

- Code must be original
- Follows security best practices
- Complies with WordPress / WooCommerce coding standards
- HPOS compatibility
- Proper data validation and sanitization

### 4. UX Review

Since 2024, a UX review has been added to the process, lengthening review timelines.

Checked items:
- Critical flows of the product work correctly
- Complies with UX guidelines (menu placement, UI components, responsiveness, etc.)
- Setup flow is intuitive
- Accessibility support
- Consistent with the WooCommerce look and feel

Tips for passing the UX review quickly:
- Thoroughly test your own critical flows before submitting
- Reference the WooCommerce Core critical flow definitions
- Make maximum use of existing WordPress/WooCommerce UI components

### Handling Feedback

Feedback may be returned during review:
- Check feedback in the Submission progress tab of the Vendor Dashboard
- After making fixes, resubmit (ZIP replacement is only allowed when status is "Changes required")
- For other statuses, request a status change via comment
- Ensure all previous feedback has been addressed before resubmitting

---

## Pricing and Business Model

### Revenue Split

- Vendor: 70%
- WooCommerce: 30%

### Pricing Strategy

**Annual Subscription (standard model)**:
The standard pricing model on the marketplace. Provide support and updates under an annual license.

**Monthly Subscription**:
For monthly pricing, implementing the SaaS Billing API is required. API keys and sandbox
access are granted manually after product submission.

```
For monthly subscriptions:
→ SaaS Billing API implementation is required
→ API key/sandbox granted after submission
→ Design so the total amount paid monthly exceeds the annual price per user
```

**Tiered Pricing (Standard, Pro, Enterprise, etc.)**:
When differentiating by feature limits or site count, each tier is implemented either as a
separate plugin file or via license-key-based feature control.

### Price Alignment with Other Channels

Marketplace price ≤ price on other channels

If also selling on your own site, the marketplace price cannot be higher.
Either make the marketplace the cheapest channel or use identical pricing.

---

## Freemium Model

A combination of a free version (WordPress.org) and a paid version (WooCommerce.com).

### Submission Flow

For Freemium, two separate submissions are required:

1. **Free version**: Submit to the WordPress.org plugin directory
2. **Paid version**: Submit to the WooCommerce.com marketplace

Each goes through an independent review process.

### Design Pattern

```php
// Free version main file
// Provides basic functionality, natural upgrade path to Pro

if ( ! defined( 'MY_EXTENSION_PRO' ) ) {
    // Display feature limitation notice in free version
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
// Pro version main file
// Either includes the free version or is fully standalone
define( 'MY_EXTENSION_PRO', true );

// Load Pro features
require_once plugin_dir_path( __FILE__ ) . 'includes/pro/class-pro-features.php';
```

### Important Notes

- Upsell links from the free version are only allowed to marketplace URLs
- External affiliate links or tracking links are prohibited
- Do not display excessive ads/banners in the free version
- The free version alone should provide practical value

---

## Submission Procedure (Vendor Dashboard)

1. Log in to the Vendor Dashboard
2. Navigate to **Submissions > Submit Product**
3. Select the product type (Extension / Theme / Integration)
4. Enter product information:
   - Plugin name (compliant with trademark guidelines)
   - Description
   - Category
   - Pricing
   - Screenshots / demo video
   - Demo site URL (important — the review team uses this to verify UX)
   - Testing instructions (directions for the review team)
5. Upload the ZIP file
6. Submit

### Setting Up a Demo Environment

The review team will interact with the product directly to verify UX, so prepare a demo environment:

- WooCommerce + product installed and active
- Populated with test data (products, orders, etc.)
- Administrator account credentials provided
- wp-env or InstaWP work well for demo environments

---

## Post-Release Operations

### Update Obligations

Listed products require ongoing maintenance:

- **Updates at least every 6 months**: Products with no updates for 6+ months are subject to delisting
- **Quarterly major releases recommended**: Align with WooCommerce Core's release calendar
- **Security fixes**: Emergency releases as needed
- **Minor improvements**: Monthly or as soon as features/fixes are ready

### Version Upload Process

1. Confirm all QIT tests pass (via the Quality Insights menu in the Vendor Dashboard)
2. Navigate to the **Versions** tab in the Vendor Dashboard
3. Upload the new version ZIP
4. Automated tests (Activation / Security / Malware) run automatically
5. After passing, the update is deployed automatically

Common upload error causes:
- ZIP filename mismatch
- Missing or incorrectly formatted `changelog.txt`
- Invalid changelog format
- Mismatch between version header and version entered at upload
- Mismatch between header and `changelog.txt` version

### Staying Current with WooCommerce Core Releases

WooCommerce releases approximately one major version per quarter.
Test against beta/RC versions before each new release to confirm compatibility:

```bash
# Testing against the beta release
./vendor/bin/qit run:e2e my-extension \
  --zip=./my-extension.zip \
  --woocommerce_version=rc

# Or specify in wp-env
# .wp-env.override.json
{
  "plugins": [
    ".",
    "https://github.com/woocommerce/woocommerce/releases/download/9.7.0-beta.1/woocommerce.zip"
  ]
}
```

### Support

Marketplace vendors are obligated to provide support to buyers:
- Tickets arrive via the WooCommerce.com helpdesk
- Aim for a first response within 24 hours
- Build out documentation to increase self-service rates

---

## Documentation Requirements

### Product Page Documentation

Documentation displayed on the marketplace product page:
- Installation instructions
- Initial setup guide
- How to use features
- FAQ
- Troubleshooting

### Developer Documentation

Descriptions of hooks, filters, and template overrides:

```markdown
## Hooks Reference

### Actions
- `my_extension_before_process` — Fires before processing begins
  - Parameters: `$order` (WC_Order)
- `my_extension_after_process` — Fires after processing completes
  - Parameters: `$order` (WC_Order), `$result` (array)

### Filters
- `my_extension_default_settings` — Filters the default settings values
  - Parameters: `$settings` (array)
  - Return: array
```

### Publishing the Changelog

The contents of `changelog.txt` are also displayed on the product page, so write entries
in a way that users can understand. Focus on changes that affect users rather than
technical internal changes.

---

## Estimated Submission-to-Release Timeline

```
Submission
 ├─ Automated tests (immediate to a few hours)
 ├─ Business review (up to 30 days)
 ├─ Code review (1–3 weeks including feedback)
 ├─ UX review (1–2 weeks)
 ├─ Feedback response and resubmission (0 to several weeks)
 └─ Approval → launch preparation → public release
```

Allow 1–3 months from initial submission to public release.
Responding to feedback quickly is the key to shortening the timeline.

---

## Common Rejection Reasons and Solutions

| Rejection Reason | Solution |
|-----------------|---------|
| HPOS incompatibility | Declare `declare_compatibility('custom_order_tables')` and eliminate direct DB queries |
| Security issues | Sanitize all inputs, escape all outputs, nonce verification, capability checks |
| Trademark violation | Remove improper use of WooCommerce trademark from plugin name |
| External links | Remove upsell/affiliate links to sites outside the marketplace |
| Top-level menu | Change to a submenu under WooCommerce |
| Proprietary telemetry | Remove proprietary tracking/telemetry code |
| Version mismatch | Align version across header, changelog, and upload time |
| Missing changelog | Create `changelog.txt` with the correct format |
| Poor internationalization | Wrap all text in translation functions, confirm text domain match |
| No demo environment | Prepare a demo site for the review team |
