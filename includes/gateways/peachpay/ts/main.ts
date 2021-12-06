import {ButtonInitEvent} from './models/stripePaymenRequest.ts';
import {ppInitStripePaymentRequestSupport} from './stripe/stripe.ts';
import {ppOnWindowMessage} from './util/dom.ts';

/**
 * Script Entry
 *
 * Adds peachpay support for Apple Pay and Google Pay buttons right above the
 * existing peachpay button.
 */
(function () {
	ppOnWindowMessage('pp-init-stripe-payment-request', async (message: ButtonInitEvent) => {
		await ppInitStripePaymentRequestSupport(message);
	});
})();

