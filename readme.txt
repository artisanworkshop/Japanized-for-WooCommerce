=== Japanized for WooCommerce  ===
Contributors: artisan-workshop-1, ssec4dev, shohei.tanaka
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@artws.info&item_name=Donation+for+Artisan&currency_code=JPY
Tags: woocommerce, ecommerce, e-commerce, Japanese
Requires at least: 6.7
Tested up to: 6.9.4
Stable tag: 2.9.9
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

Note: Paidy Checkout are also available as standalone payment plugins.

[youtube https://www.youtube.com/watch?v=mPYlDDuGzis]

== Installation ==

= Minimum Requirements =

* WordPress 6.0 or greater
* WooCommerce 8.0 or greater
* PHP version 8.3 or greater
* MySQL version 5.6 or greater
* WP Memory limit of 64 MB or greater (128 MB or higher is preferred)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Japanized For WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Japanized For WooCommerce” and click Search Plugins. Once you’ve found our eCommerce plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =
The manual installation method involves downloading and uploading our plugin to your webserver via your favorite FTP application.

== Screenshots ==

1. General Settings - Configure name reading (Yomigana) fields, address display options, honorific titles, and usage tracking preferences.
2. Shipment Settings - Set up delivery date designation, delivery time zones, holidays, and shipping notification options.
3. Payment Settings - Enable and configure Japanese payment methods including bank transfer, postal transfer, pay at store, and extra charge settings.
4. Law Settings - Configure Specified Commercial Transaction Law (特定商取引法) information including shop name, company details, owner information, and legal notices.
5. Affiliate Settings - Set up affiliate tracking for Japanese affiliate networks including A8.net, Felmat (ValueCommerce), and Access Trade.
6. Checkout Block - WooCommerce Blocks checkout page displaying Japanese address format, name reading fields, and delivery date/time selection.
7. Classic Checkout - Traditional WooCommerce checkout page with Japanese localization features including address fields and delivery options.

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

= 2.9.9 - 2026-04-23 =
* **Fixed** - COD fee not applied in Classic Checkout (Shortcode) or Block Checkout because `jp4wc_calculate_order_totals` read from `woocommerce_cod_settings` while the JP4WC admin UI saves to `wc4jp-extra_charge_*` options; settings are now merged at runtime with JP4WC values taking precedence
* **Fixed** - `extra_charge_calc_taxes` mismatch: React admin UI stored `yes`/`no` but PHP expected `no-tax`/`tax-excl`/`tax-incl`; legacy values are now normalised on read and the admin UI updated to a 3-way select
* **Fixed** - Stale `jp4wc_gateway_id` session value could cause COD fee to be silently skipped in Classic Checkout (double session-check removed; gateway ID resolved once and passed through the entire calculation)
* **Improved** - Block Checkout fee script now uses exponential back-off retry (100 ms → 10 s) instead of fixed 1500 ms / 3000 ms timeouts to wait for `wc/store/payment` store readiness
* **Added** - PRO-ready filter hooks for COD fee: `jp4wc_cod_fee_settings`, `jp4wc_cod_fee_name`, `jp4wc_cod_fee_amount`, `jp4wc_cod_fee_is_applicable`, `jp4wc_cod_fee_tax_class` (all `@since 2.9.8`)
* **Changed** - Minimum required PHP version raised from 8.1 to 8.3

= 2.9.6 - 2026-04-22 =
* **Fixed** - Paidy webhook permission check rejected all notifications from Paidy (no `x-paidy-signature` header) causing orders to remain "pending" and be auto-cancelled
* **Fixed** - Paidy webhook signature verification used wrong option name `secret_key` (non-existent); corrected to `api_secret_key` / `test_api_secret_key` based on test mode
* **Fixed** - Bank transfer (`bankjp`) account skip condition `! isset( $account_names[$i] )` was always false; replaced with `empty( $bank_names[$i] ) && empty( $name )` to properly skip blank rows
* **Added** - Deduplication of bank transfer account entries on save and load to prevent double display caused by corrupted option data
* **Fixed** - Yomigana (読み仮名) fields not displayed in HTML emails when using Block Checkout; corrected meta key fallback from `_wc_other/` to `_wc_billing/` and `_wc_shipping/` prefixes
* **Fixed** - Yomigana displayed twice on order received (thank-you) page when using Block Checkout
* **Fixed** - Yomigana appearing as a separate bold `<strong>` list below the formatted address in emails and thank-you page; now suppressed via `show_in_order_confirmation: false` and block render filter
* **Fixed** - "(よみがなを確認するには「編集」をクリックしてください。)" message incorrectly appearing in new order emails
* **Fixed** - Customer email salutation showing first name only instead of full name (姓名); email context detection now uses `WC_Email::$sending` flag for reliability with WC 10.7+ BlockEmailRenderer
* **Added** - Country is hidden from formatted address in emails and thank-you page when the store sells to only one country

= 2.9.5 - 2026-04-20 =
* **Fixed** - White screen on frontend caused by fatal error in Paidy blocks support when gateway is not registered
* **Fixed** - `WC_Gateway_Paidy not found` fatal error on admin pages when WooCommerce is installed with a non-standard path (e.g. wp-env)
* **Fixed** - `jp4wc_has_orders_in_last_5_days` undefined function error caused by `is_woocommerce_active()` returning false in non-standard install paths
* **Fixed** - Google Calendar consultation button not displaying on some hosting environments (Xserver) due to CSP/WAF blocking external script injection; replaced with a plain link
* **Added** - `jp4wc_allowed_setting_keys` filter to allow add-on plugins to register their own setting keys with the REST API
* **Added** - `jp4wc_setting_option_map` filter to allow add-on plugins to map REST keys to their existing WordPress option names
* **Added** - `jp4wc.settings.tabs` and `jp4wc.settings.tabContent` JS filters via `@wordpress/hooks` to allow add-on plugins to inject tabs into the React settings UI

= 2.9.0 & 2.9.4 - 2026-03-19 =
* **Fixed** - COD fee not calculating in Classic Checkout due to `is_admin()` returning true for admin-ajax.php requests
* **Fixed** - COD fee showing for non-COD payment methods in Classic Checkout (stale session value)
* **Fixed** - COD fee incorrectly displayed on cart page (now checkout-only)
* **Fixed** - Delivery date/time fields not appearing in Checkout Block (changed `class_exists` to `interface_exists` for IntegrationInterface)
* **Fixed** - Delivery select placeholder showing WooCommerce auto-generated text instead of admin-configured label
* **Fixed** - Paidy order completion hook firing multiple times (added `paidy_capture_id` guard)
* **Fixed** - `_load_textdomain_just_in_time` warning in WordPress 6.7+ by deferring translations to after `init`
* **Fixed** - `JP4WC_Framework` config strings now lazy-loaded at `init` priority 2 to prevent early textdomain calls
* **Fixed** - `WC_Paidy_Endpoint` instantiation deferred to `init` priority 11 to prevent early `WC_Gateway_Paidy` construction
* **Removed** - PayPal settings removed from admin settings UI and API

= Earlier versions =

[View complete changelog](https://wc.artws.info/doc/detail-woocommerce-for-japan/wc4jp-change-log/)

== Upgrade Notice ==

= 2.9 =
2.9 is a minor update, but delete PayPal Payment Gateway. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plugin.

= 2.8 =
2.8 is a minor update, but change the setting page to block. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plugin.

= 2.1 =
2.1 is a minor update, but add Paidy payment method. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plugin.

= 2.0 =
2.0 is a major update. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plugin.
