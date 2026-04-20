# Product Page Content

## Product Name

The product name is the first element visitors see in marketplace search. Follow these rules:

**Do:**
- Use a name that clearly describes the feature (e.g., "Appointments", "Advanced Shipping")
- Include keywords that users are likely to search for
- Choose a distinctive name

**Do not:**
- Lead with a brand name (❌ "VendorXYZ Bookings Plugin for WooCommerce")
- Choose a name that closely resembles an existing marketplace product
- Use a name that violates WooCommerce trademark guidelines
- Use the product description itself as the name

Changing the name after launch requires a request to the marketplace team.

## Short Description

Displayed on product cards and in Google search results. The most important sales copy.

**Rules:**
- Convey the product's value in 1–2 sentences
- Keep SEO in mind (include keywords naturally)
- Lead with benefits, not features

**Template:**

```
[Product Name] enables your WooCommerce store to [primary benefit].
[Differentiator or specific feature in one sentence].
```

**Example:**

```
Advanced Shipping Labels enables your WooCommerce store to automatically generate
and print shipping labels for major carriers. Save hours on order fulfillment
with batch processing and real-time rate calculation.
```

**Japanese example:**

```
Advanced Shipping Labels を使えば、主要な配送業者の送り状を自動生成・印刷できます。
バッチ処理とリアルタイム料金計算で、出荷作業を大幅に効率化します。
```

## Long Description

The main content of the product page. Create it using the following structure:

### Recommended Structure

```markdown
## [Problem the product solves / Benefit — Introduction]

[2–3 sentences explaining what kind of merchant and what challenge this solves.
Show empathy and explain why this product is needed.]

## Key Benefits

* **[Benefit 1]** — [Specific outcome in one sentence]
* **[Benefit 2]** — [Specific outcome in one sentence]
* **[Benefit 3]** — [Specific outcome in one sentence]
* **[Benefit 4]** — [Specific outcome in one sentence]

## How It Works

[Briefly describe the product's basic usage in 3–5 steps.
Leave technical details to the documentation; provide only an overview here.]

1. Install and activate the extension
2. [Setup step]
3. [Primary action]
4. [Experience the result/benefit]

## Use Cases

**[Use Case 1 Title]**
[Target user and specific scenario in 1–2 sentences]

**[Use Case 2 Title]**
[Target user and specific scenario in 1–2 sentences]

## Compatibility

* WooCommerce [minimum] and above
* WordPress [minimum] and above
* Compatible with block-based checkout
* Works with [key related extensions]
```

### AI Optimization Tips (2026 Recommendation)

WooCommerce's latest guidelines recommend writing in a way that makes it easy for AI/generative
engines to cite the content:

- Use a **direct, factual tone** ("it does X", not "it might do X")
- Format headings as "what is" / "how to" questions
- Put the most important information at the start of each section
- Include specific numbers and feature names

### Writing Principles

**Focus on Sales, Not Specs:**
Technical details belong in documentation. Lead with benefits in the description.

```
❌ "Uses WC_Payment_Gateway class with tokenization support via WC_Payment_Token API"
✅ "Securely saves customer payment methods for faster repeat purchases"
```

**Start With Top Benefits:**
Use a scannable list format and put the most persuasive points first.

**Visual Aids:**
Keep settings screen screenshots in documentation. On the product page, use images that show
the frontend appearance or results.

## FAQ Section

FAQs are an important section for resolving pre-purchase concerns and increasing conversions.

### Required FAQ Items

```markdown
**Does this extension work with the block-based checkout?**
Yes, [Product Name] is fully compatible with the WooCommerce block-based checkout
as well as the classic shortcode checkout.

**Is this extension compatible with [key related extension]?**
Yes, [Product Name] works seamlessly with [Extension Name]. [Brief explanation].

**What version of WooCommerce and WordPress do I need?**
[Product Name] requires WooCommerce [version]+ and WordPress [version]+.
We recommend always running the latest versions for the best experience.

**Do you offer support?**
Yes, we provide dedicated support through the WooCommerce.com helpdesk.
If you need assistance, please submit a support ticket from your
WooCommerce.com account.

**Can I try before I buy?**
[If a demo exists: Yes, check out our live demo at [URL].]
[If no demo: We offer a 30-day money-back guarantee, so you can
purchase with confidence.]

**Will this slow down my store?**
[Product Name] is built with performance in mind. [One sentence on specific
performance considerations].
```

### How to Write Effective FAQs

- Think from the perspective of someone hesitant to purchase
- Always cover the three areas: compatibility, performance, and support
- Link technical questions to documentation
- Provide reassurance about support
- Include direct links to the troubleshooting guide

## Media Gallery

### Featured Image
The image displayed at the top of the product page. A screenshot of the frontend (what
shoppers see) is ideal.

### Recommended Gallery Image Set

1. **Frontend main screen** — The screen shoppers actually see
2. **Admin settings page** — The primary screen merchants interact with
3. **Key feature highlight** — Visual explanation of the differentiating point
4. **Before/After** — Comparison before and after adoption (where applicable)
5. **Mobile view** — Evidence of responsive design

### Demo Environment Setup

For both the review team and potential customers:

- Show both frontend (shopper perspective) and backend (admin perspective)
- Populate with test products and order data
- Have all extension features in a working state
- Clearly provide login credentials (for review)
- InstaWP or wp-env-based demos are convenient

## Product Icon

- 160×160px (JPG or PNG), displayed at 80×80px
- Product logo (not a brand logo)
- Symbol/icon recommended (text is unreadable at small sizes)
- Keep actual content within the central 112×112px area
- Corner rounding is applied via CSS — do not include it in the image
- For transparent backgrounds, set a highlight card background color

## Highlight Card Background Color

- The color displayed behind the product icon
- Used on the marketplace homepage and featured pages
- Set at: Vendor Dashboard > Product Data > Woo Product Data > Highlight card background color
- Specified as a HEX color code
