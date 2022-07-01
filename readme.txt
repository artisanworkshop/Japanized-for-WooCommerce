=== Japanized For WooCommerce  ===
Contributors: artisan-workshop-1, shohei.tanaka, ssec4dev
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@artws.info&item_name=Donation+for+Artisan&currency_code=JPY
Tags: woocommerce, ecommerce, e-commerce, Japanese
Requires at least: 5.0.0
Tested up to: 6.0.0
Stable tag: 2.4.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin extends the WooCommerce shop plugin for Japanese situation.

== Description ==

This plugin is an additional feature plugin that makes WooCommerce easier to use in Japan. It is not essential when using it in Japan (Japanese environment).

= Key Features =

1. Added a name reading input item
2. Add honorific title (sama) after the name
3. Automatic postal code entry function (Yahoo! application ID required)
4. Hidden function at the time of free shipping
5. Delivery date and time setting (including holiday setting)
6. Addition of payment methods (bank transfer, postal transfer, over-the-counter payment, cash on delivery subscription)
7. Addition of official postpaid payment Paidy for Japanized for WooCommerce
8. Addition of New PayPal Checkout (compatible with Japan)
9. Addition of LINE Pay payment
10. Creation of Specified Commercial Transactions Law and setting of short code
* 7-9 payments are also distributed as individual payment plug-ins.

[youtube https://www.youtube.com/watch?v=mPYlDDuGzis]

== Installation ==

= Minimum Requirements =

* WordPress 5.0 or greater
* WooCommerce 4.0 or greater
* PHP version 7.3 or greater
* MySQL version 5.6 or greater
* WP Memory limit of 64 MB or greater (128 MB or higher is preferred)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Japanized For WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Japanized For WooCommerce” and click Search Plugins. Once you’ve found our eCommerce plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =
The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application.

== Screenshots ==

1. Billing Address Input Form
2. Admin Panel Payment Gateways
3. Admin Panel WooCommerce for Japan Setting Screen for Address Form.
4. Admin Panel WooCommerce for Japan Setting Screen for Shipping date.
5. Admin Panel WooCommerce for Japan Setting Screen for Payment.

== Changelog ==

= 2.4.1 - 2022-06-03 =
* Fixed - The PayPal Payments bug.
* Fixed - Some localize text-domains.
* Dev - Add argument $order for following filter hooks, wc4jp_delivery_details_text, wc4jp_delivery_date_text, wc4jp_time_zone_text

= 2.4.0 - 2022-06-01 =
* Dev - Add New PayPal Checkout Payments.
* Dev - Add telepone setting at law page.
* Update - PeachPay 1.65.0.

[more older](https://wc.artws.info/doc/detail-woocommerce-for-japan/wc4jp-change-log/)

== Upgrade Notice ==

= 2.1 =
2.1 is a minor update, but add Paidy payment method. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.

= 2.0 =
2.0 is a major update. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.