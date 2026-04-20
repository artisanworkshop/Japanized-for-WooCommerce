#!/bin/bash

# WooCommerce Marketplace Competitor Price Analyzer
#
# Usage: bash analyze_competitors.sh "keyword"
#
# This script searches the WooCommerce.com Marketplace for extensions
# matching the given keyword and extracts pricing information.
# Designed to be used by Claude Code as part of the pricing skill.
#
# Note: This script uses curl to fetch public marketplace pages.
# Results should be verified manually on woocommerce.com.

KEYWORD="${1:?Usage: bash analyze_competitors.sh \"keyword\"}"
ENCODED=$(echo "$KEYWORD" | sed 's/ /+/g')

echo "============================================"
echo "WooCommerce Marketplace Competitor Analysis"
echo "Search: $KEYWORD"
echo "Date: $(date +%Y-%m-%d)"
echo "============================================"
echo ""
echo "Searching woocommerce.com/product-category/woocommerce-extensions/?s=${ENCODED}"
echo ""

# Fetch search results page
RESULT=$(curl -sL "https://woocommerce.com/product-category/woocommerce-extensions/?s=${ENCODED}" \
  -H "User-Agent: Mozilla/5.0" \
  --max-time 15 2>/dev/null)

if [ -z "$RESULT" ]; then
  echo "ERROR: Could not fetch marketplace data."
  echo ""
  echo "Manual research required. Visit:"
  echo "  https://woocommerce.com/product-category/woocommerce-extensions/?s=${ENCODED}"
  echo ""
  echo "For each competitor, collect:"
  echo "  1. Product name"
  echo "  2. Annual price"
  echo "  3. Key features (3-5)"
  echo "  4. Rating and review count"
  echo "  5. Free version available?"
  exit 1
fi

# Extract product names and prices (basic HTML parsing)
echo "Found products (raw extraction — verify on woocommerce.com):"
echo "-----------------------------------------------------------"

# Try to extract product titles
echo "$RESULT" | grep -oP '(?<=<h2 class="woocommerce-loop-product__title">)[^<]+' | head -20 | while read -r title; do
  echo "  - $title"
done

echo ""
echo "-----------------------------------------------------------"
echo ""
echo "NOTE: Price extraction from HTML is unreliable."
echo "Please verify prices directly at:"
echo "  https://woocommerce.com/product-category/woocommerce-extensions/?s=${ENCODED}"
echo ""
echo "Recommended analysis template:"
echo ""
echo "| Product | Annual Price | Key Differentiator | Rating |"
echo "|---------|-------------|-------------------|--------|"
echo "| [Name]  | \$XX/year   | [Feature]          | X.X/5  |"
echo "| [Name]  | \$XX/year   | [Feature]          | X.X/5  |"
echo "| [Name]  | \$XX/year   | [Feature]          | X.X/5  |"
echo ""
echo "Price positioning options:"
echo "  - Below market:  Set price 10-20% below average (market share strategy)"
echo "  - At market:     Match average competitor price (safe positioning)"
echo "  - Above market:  Set 10-30% above average (premium positioning, needs clear differentiator)"
