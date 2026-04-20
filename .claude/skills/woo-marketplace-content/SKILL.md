---
name: woo-marketplace-content
description: >
  Skill for creating sales content for the WooCommerce.com Marketplace, including product pages,
  documentation, and FAQs. Generates product pages (name, short description, long description,
  media gallery, FAQ), user-facing documentation (installation instructions, setup guides,
  settings references, troubleshooting), developer documentation (hooks/filters list, template
  overrides), and visual asset briefs aligned with screenshot guidelines.
  Use when keywords like "write the product page", "create documentation", "write FAQ",
  "usage guide", "marketplace description", "plugin description", "installation instructions",
  "setup guide", "troubleshooting", "hooks reference", or "screenshot guide" appear.
  Also reference proactively when consulting on sales preparation or content creation for a
  WooCommerce plugin.
---

# WooCommerce Marketplace Content Creation

Selling a product on the WooCommerce.com marketplace requires not just code, but both a
compelling product page that drives purchases and documentation that supports users after
they buy. This skill generates content that complies with the official marketplace guidelines.

## Content Overview

A marketplace product requires content across four main categories:

```
1. Product Page (edited in Vendor Dashboard)
   ├── Product name
   ├── Short Description
   ├── Long Description
   ├── FAQ section
   ├── Media gallery / demo
   └── Icon / highlight card

2. User-Facing Documentation (Documentation section)
   ├── Installation & Setup
   ├── Settings Reference
   ├── Usage Guide (by use case)
   ├── Troubleshooting
   └── Frequently Asked Questions

3. Developer Documentation
   ├── Hooks & Filters reference
   ├── Template overrides
   ├── REST API (if applicable)
   └── Code examples

4. Visual Asset Brief
   ├── Screenshot plan
   ├── Icon specifications
   └── Demo environment checklist
```

## Process

### 1) Gather Product Information

Confirm the following information for content generation:

- Plugin name (a name that describes the feature, not a brand name)
- What the plugin does (in one sentence)
- Core features (3–5 top benefits)
- Target users (what kind of merchants will use it)
- Differentiators from competitors
- Compatibility requirements (WC/WP versions, integrations with other extensions)
- Price range
- Settings screen structure (number of tabs, key settings)

### 2) Generate Product Page Content

Generate sales-optimized copy following the marketplace's Content Style Guide.

See: `references/product-page.md`

### 3) Generate Documentation

Generate user and developer documentation following official WooCommerce documentation templates.

See: `references/documentation.md`

### 4) Visual Asset Plan

Generate a preparation guide for screenshots, icons, and the demo environment.

See: `references/visual-assets.md`

## Output Format

Generate content in Markdown format. Intended for pasting into the Vendor Dashboard, so
comply with the HTML/Markdown format supported by WooCommerce.com:

- Headings: h2, h3 (do not use h1 — the product name automatically becomes h1)
- Lists: bullet lists (`*` or `-`) and numbered lists
- Bold/italic: `**bold**`, `*italic*`
- Links: `[text](url)`
- Code blocks: backticks
- Images: upload separately via the Vendor Dashboard editor
- Emoji: prohibited (per marketplace guidelines)

## Voice & Tone

Align with the official WooCommerce voice:

- **Human**: Avoid jargon; use plain language
- **Plain-speaking**: Avoid roundabout expressions
- **Confident**: Describe features definitively ("it does X", not "it might do X")
- **Empathetic**: Show understanding of the user's challenges

Adjust tone by context:
- Product page: Enthusiastic yet professional
- Documentation: Clear and instructional
- FAQ: Friendly and reassuring
- Troubleshooting: Calm and solution-focused

## Multilingual Support

For the Japanese market, generate both English and Japanese versions.
English is the default language on the marketplace — always create the English version first,
then prepare the Japanese version. For the Japanese version, adjust to expressions that feel
natural to Japanese merchants rather than providing a literal translation.

## Validation

- Does the product name describe the feature (not just a brand name)?
- Is the short description concise and search-optimized?
- Does the long description follow the benefits → features → use cases order?
- Does the FAQ address pre-purchase concerns?
- Does the documentation cover everything from installation through each feature?
- Do screenshots cover both the frontend and backend?
- Are there any emoji in the content?
- Is any third-party trademark being used inappropriately?
