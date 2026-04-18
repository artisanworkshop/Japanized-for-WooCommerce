# Visual Asset Plan

## Product Icon Specifications

| Item | Specification |
|------|--------------|
| Size | 160×160px (displayed at 80×80px) |
| Format | JPG or PNG |
| Content area | Keep within the central 112×112px |
| Corner rounding | Applied via CSS — do not include in the image |
| Content | Product logo/symbol (not a brand logo) |
| Text | Avoid (unreadable at 80px display size) |
| Transparent background | Allowed (set a highlight card background color) |

Product icon design guide:
- Simple symbols or icons work best
- Visually represent the product's function
- Design to be recognizable at small sizes
- Use colors that stand out from other products

## Highlight Card Background Color

The color displayed behind the icon. Used on the marketplace homepage and
featured pages.

Setting location: **Product Data > Woo Product Data > Highlight card background color**

Specify as a HEX color code. Ensure sufficient contrast with the icon.

## Media Gallery

### Featured Image

The most important image — displayed at the very top of the product page.

Recommended:
- A screenshot of the frontend (what shoppers see)
- A screen that immediately communicates the product's primary value
- Minimum size: 555×416px
- High resolution (Retina-ready: 2x size recommended)

### Recommended Gallery Image Set

Prepare 5–8 images in the following order:

```
1. Frontend: Main screen
   → The screen shoppers see most often. Checkout, product page, etc.

2. Frontend: Key feature
   → Visual evidence of the differentiating point.

3. Admin: Settings page
   → The primary screen merchants interact with.

4. Admin: Integration in order/product management
   → How the extension integrates into the WooCommerce admin.

5. Mobile view
   → Evidence of responsive design (frontend).

6. (Optional) Before/After
   → Comparison before and after adopting the extension.

7. (Optional) Reports/Dashboard
   → If analytics features are available.

8. (Optional) Integrations/connections
   → Screenshot of external service integration.
```

### Screenshot Capture Guide

**Preparation:**
- Set your browser to full HD (1920×1080)
- Hide browser extensions and bookmarks bar
- Use realistic test data (actual product names, not "Test Product")
- Be mindful of the administrator name and avatar in the WordPress admin bar

**During capture:**
- Adjust so the target UI is centered on screen
- Exclude unnecessary sidebars/navigation (they shrink the UI)
- Highlight important areas with a red border or callout if needed
- Capture in the standard theme, not dark mode

**Post-processing:**
- Mask personal information (email addresses, names, etc.)
- Mask the test environment URL
- Maintain consistent image size (1200px wide recommended)
- Optimize file size (compress with tinypng, etc.)
- Always set alt text

**Examples of what to avoid:**
- Browser address bar shows localhost:8888
- Administrator name is still "admin"
- Test product is named "Test Product #1"
- WP_DEBUG error notices appear on screen
- Another plugin's admin screen is visible in the background

## Demo Environment Checklist

Preparing a demo environment for both the review team and potential customers:

### Required Items

- [ ] WooCommerce + product installed and activated
- [ ] 5–10 test products registered (realistic product names and images)
- [ ] Multiple test orders exist across various statuses
- [ ] All extension features are in a demonstrable state
- [ ] Accessible from the frontend (shopper perspective)
- [ ] Accessible from the admin (administrator perspective)
- [ ] Login credentials clearly provided (for review)

### Recommended Items

- [ ] Payment test possible with test card/test mode (for payment extensions)
- [ ] Demo works in both Japanese/English locales (for Japan-market extensions)
- [ ] Functionality verifiable with block-based checkout
- [ ] Functionality also verifiable with classic checkout
- [ ] No layout issues when accessed from a mobile device

### How to Set Up the Demo Environment

**InstaWP (Recommended):**
Accessible by simply sharing a URL. Temporary environment that is safe to share.

**wp-env-based deployment:**
```json
// .wp-env.json
{
  "core": "WordPress/WordPress#6.7",
  "plugins": [
    ".",
    "https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip"
  ],
  "config": {
    "WP_DEBUG": false
  }
}
```

**Own server:**
Prepare a staging environment with Basic Auth + a demo administrator account.

## Video Content (Optional)

A video embedded on the product page can be highly effective:

- A short demo video of 1–3 minutes
- Covers setup through basic operation
- Subtle background music; narration or subtitles included
- Upload to YouTube or Vimeo and share the URL
- Thumbnail uses the product icon or a frontend screen

## Asset Generation Checklist

Verification of all assets before product launch:

- [ ] Product icon (160×160px)
- [ ] Highlight card background color (HEX)
- [ ] Featured image (555×416px or larger)
- [ ] Gallery images (5–8 images)
- [ ] Alt text set on all images
- [ ] No personal information in screenshots
- [ ] Demo environment URL + login credentials
- [ ] (Optional) Demo video URL
