/**
 * Frontend JavaScript for COD Fee functionality.
 *
 * This script handles the dynamic updating of the cart and checkout
 * pages when the payment method is changed to or from Cash on Delivery (COD).
 */

(function() {
    'use strict';

    // WooCommerce Blocks integration
    const { extensionCartUpdate } = window.wc?.blocksCheckout || {};
    const { subscribe, select } = window.wp?.data || {};
    
    if (extensionCartUpdate && subscribe && select) {
        // WooCommerce Blocks checkout
        let previousPaymentMethod = '';
        
        // Monitor changes in the selected payment method
        subscribe(() => {
            const store = select('wc/store/payment');
            if (!store) {
                return;
            }
            
            const currentPaymentMethod = store.getActivePaymentMethod();
            
            if (currentPaymentMethod !== previousPaymentMethod) {
                previousPaymentMethod = currentPaymentMethod;
                
                // Update cart to reflect COD fee changes
                extensionCartUpdate({
                    namespace: 'wc-cod-fee',
                    data: {
                        payment_method: currentPaymentMethod
                    }
                }).catch((error) => {
                    console.error('Failed to update cart with COD fee:', error);
                });
            }
        }, 'wc/store/payment');
    }
    
    // Classic WooCommerce checkout
    jQuery(document).ready(function($) {
        if (typeof wc_checkout_params === 'undefined') {
            return;
        }

        // Update checkout when payment method changes
        $('form.checkout').on('change', 'input[name="payment_method"]', function() {
            $(document.body).trigger('update_checkout');
        });
    });
})();