---
name: woo-marketplace-content
description: >
  Skill for creating sales content (product pages, documentation, FAQs, etc.) for the WooCommerce.com Marketplace.
  Generates product page content (name, short description, long description, media gallery, FAQ), user documentation
  (installation instructions, setup guide, configuration reference, troubleshooting), developer documentation
  (hooks/filters list, template overrides), and visual asset instructions aligned with screenshot guidelines.
  Use when keywords like "write product page", "create documentation", "write FAQ", "usage guide",
  "marketplace description", "plugin description", "installation instructions", "setup guide",
  "troubleshooting", "hook reference", or "screenshot guide" appear. Also reference proactively
  when consulted about preparing WooCommerce plugins for sale or content creation.
---

# WooCommerce Marketplace Content Creation

Selling a product on WooCommerce.com marketplace requires not just code, but also
a product page that drives purchases and documentation that supports users after purchase.
This skill generates content that complies with the official marketplace guidelines.

## Content Overview

Content needed for a marketplace product falls into 4 main categories:

```
1. Product Page (edited via Vendor Dashboard)
   ├── Product Name
   ├── Short Description
   ├── Long Description (Description)
   ├── FAQ Section
   ├── Media Gallery / Demo
   └── Icon / Highlight Cards

2. User Documentation (Documentation section)
   ├── Installation & Setup
   ├── Configuration Reference
   ├── Usage Guides (by use case)
   ├── Troubleshooting
   └── Frequently Asked Questions

3. Developer Documentation
   ├── Hooks & Filters List
   ├── Template Overrides
   ├── REST API (if applicable)
   └── Code Examples

4. Visual Asset Instructions
   ├── Screenshot Plan
   ├── Icon Specifications
   └── Demo Environment Checklist
```

## Process

### 1) Gathering Product Information

Confirm the information needed for content generation:

- Plugin name (a name that briefly describes the function, not just a brand name)
- What the plugin does (in one sentence)
- Key features (3–5 top benefits)
- Target users (what type of merchants will use it)
- Differentiation from competitors
- Compatibility requirements (WC/WP versions, integrations with other extensions)
- Price range
- Settings screen structure (number of tabs, main settings)

### 2) Generating Product Page Content

Generate sales-optimized copy following the marketplace Content Style Guide.

See: `references/product-page.md`

### 3) Generating Documentation

Generate user/developer documentation following the official WooCommerce documentation templates.

See: `references/documentation.md`

### 4) Visual Asset Plan

Generate a guide for preparing screenshots, icons, and demo environment.

See: `references/visual-assets.md`

## Publishing to the WooCommerce Official Documentation Portal (UX Review Requirement)

On the WooCommerce Marketplace, publishing your product documentation to the WooCommerce official
documentation portal is subject to the UX review process. **Missing documentation will block the UX review.**
Start immediately after passing code review, and publish before the UX review begins — this is mandatory.

### Gutenberg Block Editor Format

Documentation on the official documentation portal must be created in **WordPress Gutenberg block editor** format.
Create, edit, and publish documentation using the WordPress block editor from the Vendor Dashboard.

Available blocks:
- **Heading block**: H2 for main sections, H3 for subsections (do not use H1)
- **Paragraph block**: Regular text
- **List block**: Bulleted and numbered lists
- **Table block**: Settings reference tables
- **Code block**: Code snippets
- **Image block**: Screenshots (alt text required)
- Emoji and decorative HTML are prohibited (marketplace guidelines)

### Documentation Completeness Checklist (UX Review Criteria)

Items UX reviewers check in documentation:
- [ ] Installation instructions are clear and complete
- [ ] Initial setup (API integrations, initial configuration, etc.) can be reproduced step by step
- [ ] Key feature usage is covered comprehensively
- [ ] FAQ answers common questions
- [ ] Troubleshooting covers representative issues
- [ ] Screenshots supplement screen operations

See: `references/documentation.md`

---

## Output Format

Generate content in Markdown format, formatted for pasting into the Vendor Dashboard
and compliant with HTML/Markdown formats supported by WooCommerce.com:

- Headings: h2, h3 (do not use h1 — the product name automatically becomes h1)
- Lists: bulleted (`*` or `-`) and numbered lists
- Bold/italic: `**bold**`, `*italic*`
- Links: `[text](url)`
- Code blocks: backticks
- Images: upload separately via the Vendor Dashboard editor
- Emoji: prohibited (marketplace guidelines)

## Voice & Tone

Match WooCommerce's official voice:

- **Human**: Avoid jargon, use plain language
- **Plain-speaking**: Avoid roundabout expressions
- **Confident**: Describe features definitively ("it does X" not "it might do X")
- **Empathetic**: Show understanding of the user's challenges

Adjust tone by context:
- Product page: enthusiastic yet professional
- Documentation: clear and instructional
- FAQ: friendly and reassuring
- Troubleshooting: calm and solution-focused

## Multilingual Support

For the Japanese market, generate both English and Japanese versions.
English is the default marketplace language — create the English version first, then prepare the Japanese version.
For the Japanese version, adjust expressions to feel natural to Japanese merchants rather than a direct translation.

## Validation

- Does the product name describe the function (not just a brand name)?
- Is the short description concise and search-optimized?
- Does the long description follow the order: benefits → features → use cases?
- Does the FAQ address pre-purchase questions?
- Does documentation cover everything from installation to each feature?
- Do screenshots cover both front-end and back-end views?
- Are emoji absent?
- Are third-party trademarks not improperly used?
