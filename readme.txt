=== Japanized For WooCommerce  ===
Contributors: artisan-workshop-1, shohei.tanaka, mt8biz
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=info@artws.info&item_name=Donation+for+Artisan&currency_code=JPY
Tags: woocommerce, ecommerce, e-commerce, Japanese
Requires at least: 4.9.0
Tested up to: 5.7.2
Stable tag: 2.2.16
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

* WordPress 4.9 or greater
* WooCommerce 3.0 or greater
* PHP version 7.2 or greater
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

= 2.2.16 - 2021-06-14 =
*Fixed - Support for product names that include double quotes at Paidy
* Fixed - Last name and first name reversal at LINE PAY

= 2.2.15 - 2021-05-17 =
* Fixed - save Shipping time bug.

= 2.2.14 - 2021-04-25 =
* Fixed - Shipping address display problem.
* Fixed - Address display for PayPal payments.
* Fixed - Save and view shipping date.

= 2.2.13 - 2021-04-15 =
* Update - JP4WC Framework 2.0.9
* Fixed - LINE PAY error display for hiragana input.
* Fixed - Display shipping phone data at e-mail.
* Fixed - Hide date and time zone specifications for virtual products.
* Fixed - Label ID missing

= 2.2.12 - 2021-01-13 =
* Fixed - Fixed holiday settings for estimated delivery dates
* Fixed - read css and js file at admin  

= 2.2.10 & 11 - 2020-12-15 =
* Fixed - PHP7.3 Supports PHP 7.3 and below.

= 2.2.9 - 2020-12-14 =
* Dev - Change the Subscription product Price string.

= 2.2.8 - 2020-12-01 =
* Fix - remove PayPal BN code.

= 2.2.7 - 2020-11-02 =
* Dev - add check required Katakana 
* Fix - some bug fix at PayPal Checkout.

= 2.2.6 - 2020-08-20 =
* Enhancement - Compatibility fixes for WordPress 5.5
* Fix - some bug fix at admin.

= 2.2.5 - 2020-07-02 =
* Fix - post code automation and linepay bug.

= 2.2.4 - 2020-06-12 =
* Fix - honorific suffix bug.

= 2.2.3 - 2020-06-11 =
* Update - Paidy payment gateway 1.1.5

= 2.2.2 - 2020-05-11 =
* Dev - Supports Paidy and LINE Pay  for virtual products
* Dev - Enabled to display the day of the week on the scheduled delivery date.
* Fix - Corrected the Japanese display of the name.
* Update - JP4WC Framework 2.0.4

= 2.2.1 - 2020-01-24 =
* Fix - LINE Pay bugs and update.

= 2.2.0 - 2020-01-16 =
* Add - LINE Pay Checkout gateway.
* Fix - Paidy payment bugs and update.

= 2.1.16 - 2019-12-30 =
* Fix - Paidy payment webhook reaction's bug.

= 2.1.15 - 2019-12-27 =
* Tweat - Paidy payment webhook reaction.

= 2.1.14 - 2019-12-19 =
* Fix - Payment display setting.
* Fix - Paidy payment bugs fixed.

= 2.1.12 &2.1.13 - 2019-12-17 =
* Fix - Paidy payment bugs at terms fixed.

= 2.1.11 - 2019-12-10 =
* Fix - Shop owner admin display setting.

= 2.1.10 - 2019-11-25 =
* Update - JP4WC Framework 2.0.3
* Fix - Post and Bank payment discription bug fixed

= 2.1.8 & 2.1.9 - 2019-11-19 =
* Fix - Paidy payment bugs at terms fixed.

= 2.1.7 - 2019-11-13 =
* Fix - Paidy payment bugs at terms fixed.

= 2.1.6 - 2019-10-19 =
* Fixed - Paidy bug fixed.

= 2.1.5 - 2019-09-27 =
* Fixed - COD double display message thanks page fixed.
* Fixed - Paidy bug fixed.

= 2.1.4 - 2019-09-26 =
* Fixed - Paidy bug fixed.

= 2.1.3 - 2019-09-21 =
* Fixed - Bug fixed.

= 2.1.2 - 2019-09-19 =
* Fixed - Supports WPML for addresses, etc.

= 2.1.1 - 2019-09-18 =
* Fixed - yomigana not require bug for Paidy Payment.

= 2.1.0 - 2019-09-09 =
* Add - Paidy payment gateway.
* Fixed - Paidy payment gateway bug for other payment plugins.

= 2.0.7 - 2019-09-07 =
* Stop - Paidy payment gateway. fatal error happened.

= 2.0.6 - 2019-09-06 =
* Add - Paidy payment gateway.

= 2.0.5 - 2019-09-04 =
* Fix - COD warnig error escape.

= 2.0.4 - 2019-09-02 =
* Fix - But some text fixed and add explanation.

= 2.0.3 - 2019-08-20 =
* Fix - download order address bug and some fixed.

= 2.0.2 - 2019-08-15 =
* Fix - COD culculation error.

= 2.0.1 - 2019-08-14 =
* Fix - file pass error.

= 2.0.0 - 2019-08-14 =
* Dev - Notation based on Specified Commercial Transaction Law  
* Dev - Add Japanized for PayPal Checkout method.
* Fix - Significant coding review and correction.

[more older](https://wc.artws.info/doc/detail-woocommerce-for-japan/wc4jp-change-log/)

== Upgrade Notice ==

= 2.1 =
2.1 is a minor update, but add Paidy payment method. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.

= 2.0 =
2.0 is a major update. Make a full site backup, update your theme and extensions.
There is no change in the database saved by this plug-in.