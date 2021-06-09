/*! woocommerce-payment-fees - v1.5.2 
 *
 * A WooCommerce Extension that allows to add extra charges to your payment gateways.
 *
 * Copyright (c) 2014 Pinch Of Code <info@pinchofcode.com>
 * GPL-2 Licensed
 *
 * Date: Wed Sep 10 2014 22:58:09 PM GMT+0200
 */
!function(a){"use strict";a("body").on("change",'input[name="payment_method"]',function(){a("body").trigger("update_checkout")})}(jQuery);