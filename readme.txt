=== Japanized for WooCommerce  ===
Contributors: artisan-workshop-1, ssec4dev, shohei.tanaka
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@artws.info&item_name=Donation+for+Artisan&currency_code=JPY
Tags: woocommerce, ecommerce, e-commerce, Japanese
Requires at least: 6.7.0
Tested up to: 6.8.3
Stable tag: 2.8.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Essential Japanese localization toolkit for WooCommerce - adds address formats, payment methods, delivery scheduling, and legal compliance.

== Description ==

Japanized for WooCommerce is the essential toolkit for running a WooCommerce store in Japan. This plugin bridges the gap between WooCommerce's global features and Japan's unique e-commerce requirements.

**Why You Need This Plugin**

Running an online store in Japan requires specific features that standard WooCommerce doesn't provide out of the box:
* Japanese address formats with proper field ordering (postal code, prefecture, city, address lines)
* Name reading fields (Yomigana/Furigana) for accurate customer identification
* Delivery date and time selection that customers expect
* Popular Japanese payment methods like bank transfer and COD
* Legal compliance with Japan's Specified Commercial Transaction Act (特定商取引法)

**Who Should Use This**

This plugin is designed for:
* Japanese e-commerce businesses using WooCommerce
* International stores shipping to Japanese customers
* Anyone who needs Japanese address handling and payment methods
* Stores requiring delivery date/time selection functionality

**Seamless Integration**

Works smoothly with WooCommerce core features and popular extensions. Fully compatible with the new WooCommerce Blocks checkout experience. All features are optional - enable only what you need for your store.

= Key Features =

**Address & Name Management**
* Name reading (Yomigana/Furigana) input fields for billing and shipping addresses
* Honorific title (様/sama) automatically added after customer names
* Japanese-style address format with proper field ordering
* Auto-fill address from postal code using Yahoo! API integration
* Company name field support

**Shipping & Delivery**
* Delivery date and time selection at checkout
* Delivery time slot management
* Holiday and non-delivery day settings
* Weekend and specific date exclusions
* Delivery-related fields hidden when free shipping is applied

**Payment Methods**
* Bank Transfer (Japanese banks)
* Japan Post Bank Transfer
* Cash on Delivery (COD) with fee calculation
* COD subscription support
* Pay at Store (over-the-counter payment)
* Paidy (Buy Now, Pay Later) - Official Japanese payment gateway
* PayPal Checkout optimized for Japan

**Legal & Compliance**
* Specified Commercial Transaction Act (特定商取引法) page creator
* Shortcode support for legal information display
* Customizable legal notice templates

**Additional Features**
* Email template optimization for Japanese format
* Address validation for Japanese postal codes
* Affiliate integration (A8.net, Access Trade, Value Commerce)
* WooCommerce Blocks compatibility
* Security scanning and malware detection

Note: Paidy and PayPal Checkout are also available as standalone payment plugins.

[youtube https://www.youtube.com/watch?v=mPYlDDuGzis]

== Installation ==

= Minimum Requirements =

* WordPress 6.0 or greater
* WooCommerce 8.0 or greater
* PHP version 8.1 or greater
* MySQL version 5.6 or greater
* WP Memory limit of 64 MB or greater (128 MB or higher is preferred)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Japanized For WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Japanized For WooCommerce” and click Search Plugins. Once you’ve found our eCommerce plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =
The manual installation method involves downloading and uploading our plugin to your webserver via your favorite FTP application.

== Screenshots ==

1. Billing Address Input Form
2. Admin Panel Payment Gateways
3. Admin Panel WooCommerce for Japan Setting Screen for Address Form.
4. Admin Panel WooCommerce for Japan Setting Screen for Shipping date.
5. Admin Panel WooCommerce for Japan Setting Screen for Payment.

== Frequently Asked Questions ==

= Do I need this plugin to use WooCommerce in Japan? =

While WooCommerce can work in Japan without this plugin, Japanized for WooCommerce provides essential features that Japanese customers expect, such as delivery date selection, name reading fields (Yomigana), and local payment methods. It significantly improves the user experience for Japanese e-commerce.

= Does this plugin work with WooCommerce Blocks? =

Yes! Version 2.8.0 and later fully supports the new WooCommerce Blocks checkout experience, including delivery date selection and custom address fields.

= How do I enable the postal code auto-fill feature? =

You need to obtain a free Yahoo! Japan Application ID from the Yahoo! Developer Network. Once you have the ID, enter it in the WooCommerce → Settings → Japan Settings → Address Form section.(Classic Checkout only)

= Which payment methods are included? =

The plugin includes: Bank Transfer (Japanese banks), Japan Post Bank Transfer, Cash on Delivery (COD) with fee calculation, Pay at Store, Paidy (Buy Now, Pay Later), and PayPal Checkout optimized for Japan. Paidy and PayPal are also available as standalone plugins.

= Can I use only specific features and disable others? =

Yes, absolutely! All features are modular and can be enabled or disabled individually from the plugin settings. You only need to activate the features your store requires.

= Is the plugin compatible with multilingual sites? =

Yes, the plugin is compatible with WPML and other multilingual plugins. It automatically detects the language and adjusts features accordingly. Japanese-specific features are only applied when the site language is set to Japanese.

= How do I set up delivery date and time selection? =

Go to WooCommerce → Settings → Japan Settings → Delivery Date. You can configure available delivery times, set holidays, exclude specific days of the week, and customize the delivery date display format.

= Does this plugin modify WooCommerce core files? =

No, the plugin uses WordPress and WooCommerce hooks and filters. It doesn't modify any core files, making it safe to use and easy to update.

= Where can I get support? =

For support, please visit the [plugin support forum](https://wordpress.org/support/plugin/woocommerce-for-japan/) on WordPress.org or check the [official documentation](https://wc.artws.info/).

= Is this plugin free? =

Yes, Japanized for WooCommerce is completely free and open source under the GPLv3 license.

== Changelog ==

= 2.8.0 - 2025-01-01 =
* **New** - Complete WooCommerce Blocks checkout compatibility including delivery date and time selection
* **New** - Block-based settings page for improved admin experience
* **New** - Timezone support for delivery date selection in Checkout Block
* **Update** - Enhanced usage tracking with improved data collection and privacy controls
* **Update** - Code quality improvements with comprehensive linting fixes
* **Fixed** - Multiple bug fixes for improved stability and performance
* **Fixed** - Address field rendering issues in WooCommerce Blocks
* **Dev** - Refactored settings architecture for better maintainability
* **Dev** - Updated dependencies and improved compatibility with WordPress 6.8+

= Earlier versions =

[View complete changelog](https://wc.artws.info/doc/detail-woocommerce-for-japan/wc4jp-change-log/)

== Upgrade Notice ==

= 2.8 =
2.8 is a minor update, but change the setting page to block. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.

= 2.1 =
2.1 is a minor update, but add Paidy payment method. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.

= 2.0 =
2.0 is a major update. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.
