# Documentation Creation Guide

A guide for creating documentation managed in the Vendor Dashboard's Documentation section.
There are two types: user-facing documentation and developer documentation.

## Documentation Workflow

1. **Organize** — Consider your target audience and decide on the section structure
2. **Write** — Write following the Content Style Guide
3. **Review** — Verify the steps while actually operating the plugin
4. **Publish** — Publish in the Vendor Dashboard
5. **Maintain** — Update with each version release

## User-Facing Documentation

### Base Template (General Extension)

```markdown
# [Product Name]

[Product Name](link) allows your WooCommerce store to [one-sentence feature description].

## Installation

1. Download the .zip file from your WooCommerce account.
2. Go to: **WordPress Admin > Plugins > Add New > Upload Plugin**
   and select the ZIP file you just downloaded.
3. Click **Install Now** and then **Activate** the extension.

More information at:
[Install and Activate Plugins/Extensions](https://woocommerce.com/document/how-to-install-and-activate-plugins-extensions/).

## Setup and Configuration

### Getting Started

After activating [Product Name], navigate to
**WooCommerce > Settings > [Tab Name]** to configure the extension.

### [Settings Section 1 Name]

[1–2 sentences describing what this section does]

| Setting | Description | Default |
|---------|-------------|---------|
| **[Setting 1]** | [What this setting does] | [Default value] |
| **[Setting 2]** | [What this setting does] | [Default value] |
| **[Setting 3]** | [What this setting does] | [Default value] |

### [Settings Section 2 Name]

[Describe settings in the same way]

## Usage

### [Use Case 1: Basic Usage]

[Explain the steps to perform the operation]

1. Go to **[Menu path]**
2. Click **[Button name]**
3. [Action to take]
4. Click **Save changes**

### [Use Case 2: Advanced Usage]

[Steps in the same format]

## Frequently Asked Questions

### [Question 1]?

[Answer]

### [Question 2]?

[Answer]

## Troubleshooting

### [Symptom of Problem 1]

**Cause:** [Cause]
**Solution:** [Solution]

### [Symptom of Problem 2]

**Cause:** [Cause]
**Solution:** [Solution]

### Need More Help?

If you're still experiencing issues, please
[submit a support ticket](https://woocommerce.com/my-account/create-a-ticket/)
and our team will be happy to assist.
```

### Payment Gateway Template

```markdown
# [Gateway Name]

[Gateway Name](link) allows your WooCommerce store to accept [payment method].

* [Feature 1]
* [Feature 2]
* [Feature 3]

## Requirements

* WooCommerce [version]+
* WordPress [version]+
* [Payment provider account]
* SSL certificate (HTTPS required)

## Installation

1. Download the .zip file from your WooCommerce account.
2. Go to: **WordPress Admin > Plugins > Add New > Upload Plugin**
   and select the ZIP file you just downloaded.
3. Click **Install Now** and then **Activate** the extension.

## Setup and Configuration

### 1. Create a [Provider Name] Account

[Account creation steps and how to obtain API keys]

### 2. Connect [Provider Name] to WooCommerce

1. Go to **WooCommerce > Settings > Payments**
2. Locate **[Gateway Name]** and click **Manage**
3. Check **Enable [Gateway Name]**
4. Enter your **API Key** and **Secret Key**
5. For initial testing, enable **Test Mode**
6. Click **Save changes**

### 3. Test Your Setup

1. Enable **Test Mode** in the gateway settings
2. Place a test order using [test card number, etc.]
3. Verify the order appears in **WooCommerce > Orders**
4. Confirm the payment in your [Provider Name] dashboard
5. When satisfied, disable **Test Mode** for live transactions

### Payment Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable/Disable** | Enable this payment method | Disabled |
| **Title** | Payment method title shown at checkout | [Default name] |
| **Description** | Payment method description at checkout | [Default text] |
| **Test Mode** | Enable test/sandbox mode | Enabled |
| **API Key** | Your [Provider Name] API key | — |
| **Secret Key** | Your [Provider Name] secret key | — |

## Refunds

[Product Name] supports both full and partial refunds:

1. Go to **WooCommerce > Orders** and select the order
2. Click **Refund**
3. Enter the refund amount
4. Click **Refund via [Gateway Name]**

## Troubleshooting

### Payment fails with "[Error message]"

**Cause:** [Cause]
**Solution:** [Solution]

### Gateway not showing at checkout

**Cause:** The gateway may not be enabled or your store currency
may not be supported.
**Solution:**
1. Go to **WooCommerce > Settings > Payments** and verify
   the gateway is enabled
2. Check that your store currency is in the supported list
3. If using block checkout, clear your browser cache
```

## Developer Documentation

### Hooks & Filters Reference

```markdown
# Developer Documentation

## Actions

### `my_extension_before_process`

Fires before the extension processes an order.

**Parameters:**
* `$order` (WC_Order) — The order being processed.

**Example:**
\```php
add_action( 'my_extension_before_process', function( $order ) {
    // Custom logic before processing
}, 10, 1 );
\```

### `my_extension_after_process`

Fires after the extension has processed an order.

**Parameters:**
* `$order` (WC_Order) — The processed order.
* `$result` (array) — Processing result data.

## Filters

### `my_extension_default_settings`

Filters the default settings array.

**Parameters:**
* `$settings` (array) — Default settings.

**Returns:** array

**Example:**
\```php
add_filter( 'my_extension_default_settings', function( $settings ) {
    $settings['custom_option'] = 'value';
    return $settings;
} );
\```

## Template Overrides

Copy template files to your theme's directory to customize output:

| Template | Description |
|----------|-------------|
| `templates/frontend/display.php` | Main frontend display |
| `templates/emails/notification.php` | Email notification |

To override, copy to:
`your-theme/my-extension/frontend/display.php`
```

## Writing Style Rules

### US English

WooCommerce.com uses US English:
- "color" not "colour"
- "customize" not "customise"
- "center" not "centre"

### Terminology

Follow official WooCommerce terminology:
- **add-on** (noun/adjective), **add on** (verb)
- **admin** — when referring to an administrator user
- **checkout** — one word (not check-out)
- **ecommerce** — no hyphen after "e" (not e-commerce)
- **email** — not e-mail
- **plugin** — interchangeable with add-on/extension
- **setup** (noun), **set up** (verb)
- **WooCommerce** — always CamelCase

### Heading Levels

- Do not use h1 (the product name automatically becomes h1)
- Use h2 for main sections
- Use h3 for subsections
- Avoid h4 and below (too deep, harder to read)

### Describing UI Paths

WordPress menu paths in bold:
```
Go to **WooCommerce > Settings > Payments**
```

Button names also in bold:
```
Click **Save changes**
```

### Including Screenshots

Rules for screenshots within documentation:
- Place alongside sections explaining settings screens or UI elements
- Mask personal information and test data
- Appropriate size (800–1200px wide recommended)
- Always include alt text
- Optimize for high-resolution displays (2x recommended)

## Documentation Maintenance

Check the following when releasing a new version:

- [ ] Add documentation for new features
- [ ] Update changed settings entries
- [ ] Remove descriptions of deleted features
- [ ] Verify screenshots match the latest UI
- [ ] Update version number references
- [ ] Add new known issues to troubleshooting
- [ ] Add new questions to FAQ (extract frequent questions from support tickets)
