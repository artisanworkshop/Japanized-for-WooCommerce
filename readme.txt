=== Japanized For WooCommerce  ===
Contributors: artisan-workshop-1, ssec4dev, shohei.tanaka
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@artws.info&item_name=Donation+for+Artisan&currency_code=JPY
Tags: woocommerce, ecommerce, e-commerce, Japanese
Requires at least: 5.0.0
Tested up to: 6.6.1
Stable tag: 2.6.17
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
8. Addition of PayPal Checkout (compatible with Japan)
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
The manual installation method involves downloading and uploading our plugin to your webserver via your favorite FTP application.

== Screenshots ==

1. Billing Address Input Form
2. Admin Panel Payment Gateways
3. Admin Panel WooCommerce for Japan Setting Screen for Address Form.
4. Admin Panel WooCommerce for Japan Setting Screen for Shipping date.
5. Admin Panel WooCommerce for Japan Setting Screen for Payment.

== Changelog ==

= 2.6.17 - 2024-08-06 =
* Change - Add LINE PAY End notice.

= 2.6.16 - 2024-07-16 =
*Fixed -fatal error at php file rquired. 

= 2.6.15 - 2024-07-11 =
* Fixed - Fixed PHP warning bug on multisite.
* Fixed - Error handling in older WC versions.
* Tweak - Bank and postal transfer processing.
* Dev - Change in display position of bank transfer and postal transfer.

= 2.6.14 - 2024-05-15 =
* Fixed - Coupon bugs for Paidy Payment.

= 2.6.13 - 2024-05-10 =
* Fixed - Coupon bugs for Paidy Payment.

= 2.6.12 - 2024-05-09 =
* Dev - Warning display for PHP version sites whose support has expired.
* Fixed - Some bugs for AtStore Payment gateway.

= 2.6.11 - 2024-05-01 =
* Fixed - Some bugs for some Payment gateway.

= 2.6.10 - 2024-02-05 =
* Fixed - Some bugs for notice.

= 2.6.9 - 2024-01-26 =
* Dev - Compatible checkout block for Paidy payments.

= 2.6.8 - 2024-01-23 =
* Dev - Compatible checkout block for at-store, bank-jp, postal office bank payments.

= 2.6.7 - 2024-01-19 =
* Fixed - Payment gateway warnign bugs.
* Dev - Add JP4WC Tracker

= 2.6.6 - 2023-11-28 =
* Fixed - Update order on-hold email template.

= 2.6.5 - 2023-10-27 =
* Fixed - Input postalcode for iPhone behavior.
* Dev - Remove peachPay plugin

= 2.6.4 - 2023-09-26 =
* Fixed - The bug of New Order at Admin.
* Fixed - the label CSS at delivery time and date.
* Fixed - Yahoo! API endpoint bug.

= 2.6.1 & 2.6.2 & 2.6.3 - 2023-08-10 =
* Fixed - The bug of display shipping phone at email.
* Fixed - Order post meta display bug for shipping date and term.

= 2.6.0 - 2023-08-03 =
* Update - Compatible HPOS
* Update - Changed the required version of WooCommerce to 6.0 or higher.
* Fixed - Some bugs

[more older](https://wc.artws.info/doc/detail-woocommerce-for-japan/wc4jp-change-log/)

== Upgrade Notice ==

= 2.1 =
2.1 is a minor update, but add Paidy payment method. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.

= 2.0 =
2.0 is a major update. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.
