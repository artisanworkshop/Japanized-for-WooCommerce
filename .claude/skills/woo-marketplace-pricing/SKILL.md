---
name: woo-marketplace-pricing
description: >
  Skill for recommending pricing for plugins sold on the WooCommerce.com Marketplace.
  Gathers information about the plugin's feature category, complexity, and target market,
  researches competitor pricing in the marketplace, and proposes an optimal annual subscription
  price factoring in the 70/30 revenue split. Also covers pricing tier design, Freemium model
  decisions, pricing for the Japanese market, and monthly plan design using the SaaS Billing API.
  Use when keywords like "pricing", "how much to charge", "how much to sell for",
  "annual subscription", "monthly pricing", "Freemium decision", "competitor research",
  "revenue simulation", or "price point" appear. Also reference proactively when consulting
  on business model or profitability for a WooCommerce plugin.
---

# WooCommerce Marketplace Pricing Strategy

Pricing is one of the most important decisions for marketplace success.
Too high and no one buys; too low and you cannot cover support costs.
This skill provides data-driven pricing recommendations.

## Process

### 1) Gather Plugin Information

Collect the information needed to make a pricing recommendation:

**Basic information:**
- Plugin name and feature summary
- Category (payments, shipping, marketing, product management, analytics, etc.)
- Target users (small stores, mid-market, enterprise)
- Number of core features and their complexity
- Whether the plugin integrates with external APIs or services

**Business information:**
- Price on your own site (if already selling — marketplace price must be equal or lower)
- Whether a Freemium version exists (free version on WordPress.org)
- Support capacity (solo developer vs. team)
- Expected support hours per user
- Monthly development time investment

### 2) Competitor Research

Research the price range of existing products in the same category. You can use
`scripts/analyze_competitors.sh` to retrieve competitor product information from the
WooCommerce.com marketplace.

For manual research, collect the following:
- Product names and annual prices in the same category
- Core features of each product
- Ratings and review count
- Whether a Freemium version exists

### 3) Determine the Price Range

Using the marketplace's actual price landscape as a reference, apply the following framework
to determine your price.

## Pricing Decision Framework

### Actual Marketplace Price Ranges (as of 2026)

| Category | Annual Price Range | Representative Products |
|----------|--------------------|------------------------|
| Payment gateways | $0–$79/year | Most are free (provider subsidized) |
| Shipping extensions | $49–$149/year | Table Rate Shipping $99, Distance Rate $79 |
| Product management | $49–$149/year | Product Add-Ons $49, Bundles $49 |
| Subscriptions | $199–$249/year | Subscriptions $199, Memberships $199 |
| Marketing | $49–$179/year | Affiliate $179, Smart Coupons $99 |
| Analytics/reporting | $49–$149/year | Analytics Pro $79 |
| Inventory management | $79–$199/year | Stock Manager $79 |
| Bookings/appointments | $199–$249/year | Bookings $249 |
| Compliance | $49–$99/year | GDPR $49 |
| Integrations/connections | $79–$199/year | QuickBooks $79, Zapier $99 |

### Pricing Guidelines by Complexity

**Tier 1: Simple features ($49–$79/year)**
- Focused on a single function
- Few settings (one screen)
- No external APIs
- Low support burden
- Examples: simple field additions, display customizations, single-format exports

**Tier 2: Standard features ($79–$149/year)**
- Multiple related features
- Moderate settings (2–3 screens/tabs)
- Light API integration
- Average support burden
- Examples: shipping rate calculation, product options, report generation

**Tier 3: Advanced features ($149–$249/year)**
- Complex business logic
- Rich settings (4+ screens/tabs)
- Multiple API integrations
- Requires expert support
- Examples: subscription management, booking systems, multi-language support

**Tier 4: Enterprise-grade ($249–$399+/year)**
- Business-critical functionality
- Highly customizable
- Large data processing
- Specialized support required
- Examples: ERP integration, multi-vendor, advanced fulfillment

### Price Calculation Factors

```
Recommended Price = Base Price × Complexity Multiplier × Market Multiplier

Base Price:
  - Single feature: $49
  - Multiple features: $79
  - Advanced features: $149

Complexity Multiplier:
  - 1 settings screen: ×1.0
  - 2–3 settings screens: ×1.2
  - 4+ settings screens: ×1.5
  - External API integration: +$20–50
  - Custom DB tables: +$20
  - Block editor integration: +$10

Market Multiplier:
  - Many competitors: ×0.8 (need to differentiate on price)
  - Few competitors: ×1.2 (premium pricing possible)
  - No competitors (niche): ×1.5
  - Japan market only: ×0.8–1.0 (account for market size)
```

### Revenue Split Considerations

Actual vendor revenue after the marketplace's 70/30 split:

```
At $99/year annual price:
  Vendor share: $99 × 0.70 = $69.30/year/user
  Monthly equivalent: $5.78

At $149/year annual price:
  Vendor share: $149 × 0.70 = $104.30/year/user
  Monthly equivalent: $8.69

At $199/year annual price:
  Vendor share: $199 × 0.70 = $139.30/year/user
  Monthly equivalent: $11.61
```

### Working Backwards from Support Costs

```
Average monthly support time per user:
  Simple plugin: 5–10 min/month
  Standard plugin: 10–20 min/month
  Complex plugin: 20–40 min/month

Assuming a support rate of $30/hour:

  10 min/month = $5/month = $60/year
  20 min/month = $10/month = $120/year
  30 min/month = $15/month = $180/year

Required annual price (support costs alone):
  $60 ÷ 0.70 = $86 (minimum price)
  $120 ÷ 0.70 = $171
  $180 ÷ 0.70 = $257
```

Factor in development costs (hourly rate × monthly development time) to calculate the
break-even point.

### Freemium Decision Criteria

**When to go Freemium:**
- Many competitors exist in the category
- The free tier can provide genuine value with a natural upgrade path to Pro
- WordPress.org exposure is an important acquisition channel
- You want to accumulate reviews and word-of-mouth early on

**When to stay paid-only:**
- Niche category with few competitors
- Hard to separate features (can't leave meaningful functionality in the free tier)
- Target audience is enterprise (no need to support free users)
- External API costs are incurred per user (even free users cost money)

### Monthly Subscriptions

If offering a monthly option, implementing the SaaS Billing API is required.

```
Monthly → Annual pricing conversion:
  Monthly pricing is typically around 1/10 of the annual price
  (annual offers a discount vs. monthly)

  Example: Annual $99 → Monthly $12.99
           Annual $149 → Monthly $17.99
           Annual $199 → Monthly $24.99
```

Monthly pricing lowers the barrier to acquisition, but tends to increase churn.
Annual subscriptions produce a higher LTV (lifetime value).

### Price Alignment with Other Sales Channels

Marketplace price ≤ price on other channels (required rule)

Strategy when also selling on your own site:
- **Identical pricing**: Simple and easy to manage
- **Lower on marketplace**: Leverage marketplace traffic for acquisition
- **Higher on own site**: Satisfies marketplace rules while offering premium support on your own site

### Pricing for the Japanese Market

Plugins targeting the Japanese market are often priced lower than global products:

- The WooCommerce market in Japan is smaller than the global market
- Japanese-language support requires significant effort, but the user base is limited
- JPY reference: $79 ≈ ¥12,000; $149 ≈ ¥22,000
- Japanese merchants are accustomed to paying roughly ¥10,000–¥30,000/year for extensions

## Output Format

Price recommendations should be presented in the following format:

```
## Pricing Recommendation: [Plugin Name]

### Recommended Price
- **Annual price**: $[XX]/year
- **Vendor net revenue**: $[XX]/year (70%)
- **Monthly net revenue**: $[XX]/month

### Rationale
- **Category range**: $[XX]–$[XX]/year
- **Complexity**: [Tier X] ([reason])
- **Competitive landscape**: [Many / Few / None]
- **Differentiation**: [key differentiators]

### Competitor Comparison
| Product | Annual Price | Key Differences |
|---------|-------------|-----------------|
| [Competitor A] | $XX | [difference] |
| [Competitor B] | $XX | [difference] |

### Revenue Simulation
| Annual Users | Revenue | Vendor Revenue | Support Cost | Net Profit |
|-------------|---------|----------------|--------------|------------|
| 50 | $X | $X | $X | $X |
| 100 | $X | $X | $X | $X |
| 200 | $X | $X | $X | $X |

### Freemium Decision: [Recommended / Not Recommended]
[Reason]

### Alternatives
- **Aggressive pricing**: $[XX] (prioritize market share)
- **Conservative pricing**: $[XX] (prioritize profitability)
```

## Validation

- Is the proposed price within the category's typical range?
- Does it satisfy the rule of being equal to or less than your own site's price?
- Is the price sufficient to cover support costs?
- Is the break-even point achievable at a realistic user count?
- Does the pricing justify differentiation compared to competitors?
