import { ICartCalculationRecord } from '../models/ICartCalculation.ts';
import { StripeElementsInstance, StripeInstance } from '../models/IStripe.ts';
import { ButtonInitEvent, ButtonPaymentProcessedEvent, ButtonUpdateEvent, StripePaymentItem, StripePaymentRequest, StripePaymentResponse, StripePaymentToken, StripeShippingAddress, StripeShippingAddressChangedEvent, StripeShippingOption, StripeShippingOptionChangedEvent, StripeUpdateDetails } from '../models/stripePaymenRequest.ts';
import { ppOnWindowMessage, ppFetchIframeData, ppIframeWindow } from '../util/dom.ts';

// Defined by external script.
declare function Stripe(key: string, options: unknown): StripeInstance;
// Defined by external script.
declare function captureSentryException(error: Error): void;

type StripeService = { stripe: StripeInstance; elements: StripeElementsInstance; paymentRequest: StripePaymentRequest };

export async function ppInitStripePaymentRequestSupport(message: ButtonInitEvent) {
    try {
        const publicKey = message.stripe.live
            ? 'pk_live_oROnIQDuexHZpnEOcUff3CRz00asaOOCAL'
            : 'pk_test_CnL2kA52V5dRqZbjlJ0sZ2gr00uBrOEmQQ';

        const stripe = Stripe(publicKey, { locale: message.stripe.locale });
        const elements = stripe.elements();
        const paymentRequest = stripe.paymentRequest({
            country: 'US', // This is our stripe account country
            currency: message.currencyCode.toLowerCase(),
            total: {
                label: 'Total',
                amount: ppCurrencyAmountToStripeFormat(message.cartCalculationRecord[0].summary.total, message.currencyCode),
            },
            displayItems: ppGetPaymentRequestDisplayItems(message.cartCalculationRecord, message.currencyCode),
            shippingOptions: ppGetPaymentRequestShippingOptions(message.cartCalculationRecord, message.currencyCode),
            requestPayerName: true,
            requestPayerEmail: true,
            requestPayerPhone: true,
            requestShipping: true,
        });

        // Decide to show buttons or not
        const availableMethods = await paymentRequest.canMakePayment();
        if (!availableMethods || (!availableMethods.applePay && !availableMethods.googlePay)) {
            ppIframeWindow()?.postMessage('pp-stripe-payment-request-stop', '*');
            return;
        }

        const stripeService: StripeService = { stripe, elements, paymentRequest };

        // Used to inject stripeService into event handlers to avoid a global or defining callback within this function.
        const injectStripeService = <T>(stripeService: StripeService, handler: (stripeService: StripeService, stripeEvent: T) => void) => (stripeEvent: T) => {
            handler(stripeService, stripeEvent);
        };

        paymentRequest.on('shippingaddresschange', ppHandleShippingAddressChangeEvent);
        paymentRequest.on('shippingoptionchange', ppHandleShippingOptionChangeEvent);
        paymentRequest.on('token', injectStripeService(stripeService, ppHandleTokenEvent));
        paymentRequest.on('cancel', injectStripeService(stripeService, ppHandleCancelEvent));

        ppOnWindowMessage('pp-update-stripe-payment-request', injectStripeService(stripeService, ppHandleExternalStripeButtonDataEvent));
        ppInsertAvailablePaymentRequestButton(stripeService);
    } catch (error: unknown) {
        if (error instanceof Error) {
            captureSentryException(new Error(`Google Pay/Apple Pay failed to initilize on ${location.hostname}. Error: ${error.message}`));
        }
    }
}

async function ppHandleTokenEvent(_stripeService: StripeService, event: StripePaymentResponse) {
    try {
        const request: StripePaymentToken = {
            token: event.token,
            payerName: event.payerName,
            payerEmail: event.payerEmail,
            payerPhone: event.payerPhone,
            shippingAddress: event.shippingAddress,
            shippingOption: event.shippingOption,
            walletName: event.walletName,
        };

        const response = await ppFetchIframeData<StripePaymentToken, ButtonPaymentProcessedEvent>('pp-stripe-payment-request-process-payment', request);

        event.complete(response.status);

        // Now that everything is complete here we can redirect the page.
        self.postMessage({
            event: 'pp-complete-transaction',
            redirectURL: response.redirectURL,
        }, '*');
    } catch (error: unknown) {
        if (error instanceof Error) {
            if (error.message === "invalid_shipping_address") {
                event.complete("invalid_shipping_address")
                return;
            }

            captureSentryException(new Error(`Google Pay/Apple Pay failed to handle token on ${location.hostname}. Error: ${error.message}`));
            event.complete('fail');
        }
    }
}

/**
 * Handles changing the shipping address for a Payment Request Customer.
 */
async function ppHandleShippingAddressChangeEvent(event: StripeShippingAddressChangedEvent) {

    let response = null;
    try {
        response = await ppFetchIframeData<StripeShippingAddress, ButtonUpdateEvent>('pp-stripe-payment-request-address-change', event.shippingAddress);
    } catch (error: unknown) {
        if (error instanceof Error) {
            event.updateWith({
                status: 'fail',
            } as unknown as StripeUpdateDetails);
            captureSentryException(new Error(`Google Pay/Apple Pay failed requesting shipping address change on ${location.hostname}. Error: ${error.message}`));
        }
        return;
    }

    const isVirtual = response.cartCalculationRecord[0].cart_meta.is_virtual;
    const methods = Object.entries(response.cartCalculationRecord[0].package_record[0]?.methods ?? {})
    if (!isVirtual && methods.length === 0) {
        event.updateWith({
            status: 'invalid_shipping_address',
            total: {
                label: 'Total',
                amount: ppCurrencyAmountToStripeFormat(response.cartCalculationRecord[0].summary.total, response.currencyCode),
            },
            displayItems: ppGetPaymentRequestDisplayItems(response.cartCalculationRecord, response.currencyCode),
            shippingOptions: ppGetPaymentRequestShippingOptions(response.cartCalculationRecord, response.currencyCode),
        });
    } else {
        event.updateWith({
            status: 'success',
            total: {
                label: 'Total',
                amount: ppCurrencyAmountToStripeFormat(response.cartCalculationRecord[0].summary.total, response.currencyCode),
            },
            displayItems: ppGetPaymentRequestDisplayItems(response.cartCalculationRecord, response.currencyCode),
            shippingOptions: ppGetPaymentRequestShippingOptions(response.cartCalculationRecord, response.currencyCode),
        });
    }


}

/**
 * Handles changing a selected shipping option for a Payment Request Customer.
 */
async function ppHandleShippingOptionChangeEvent(event: StripeShippingOptionChangedEvent) {
    try {
        const response = await ppFetchIframeData<StripeShippingOption, ButtonUpdateEvent>('pp-stripe-payment-request-shipping-change', event.shippingOption);

        event.updateWith({
            status: 'success',
            total: {
                label: 'Total',
                amount: ppCurrencyAmountToStripeFormat(response.cartCalculationRecord[0].summary.total, response.currencyCode),
            },
            displayItems: ppGetPaymentRequestDisplayItems(response.cartCalculationRecord, response.currencyCode),
            shippingOptions: ppGetPaymentRequestShippingOptions(response.cartCalculationRecord, response.currencyCode),
        });
    } catch (error: unknown) {
        if (error instanceof Error) {
            captureSentryException(new Error(`Google Pay/Apple Pay failed changing shipping option on ${location.hostname}. Error: ${error.message}`));
        }

        event.updateWith({
            status: 'fail',
        } as unknown as StripeUpdateDetails);
    }
}

/**
 * Allows cleanup or cancellation of existing payment. 
 */
function ppHandleCancelEvent(_stripeService: StripeService) {
    // TODO
    // Tell modal to reload from local storage because modal info may be a bit funky but not until a refund is done/cancel pay process(address wise).
    // It is possible a scenario can occur where a refund must be done here.(if modal is closed early)
}

/**
 * Handles updating the stripe payment request button whenever shipping, address, or other types of
 * details are changed OUTSIDE of the payment request button.
 */
function ppHandleExternalStripeButtonDataEvent(stripeService: StripeService, message: ButtonUpdateEvent) {
    if (stripeService.paymentRequest.isShowing()) {
        return;
    }

    stripeService.paymentRequest.update({
        currency: message.currencyCode.toLowerCase(),
        total: {
            label: 'Total',
            amount: ppCurrencyAmountToStripeFormat(message.cartCalculationRecord[0].summary.total, message.currencyCode),
        },
        displayItems: ppGetPaymentRequestDisplayItems(message.cartCalculationRecord, message.currencyCode),
        shippingOptions: ppGetPaymentRequestShippingOptions(message.cartCalculationRecord, message.currencyCode),
    });
}

/**
 * Inserts the available payment request button into the dom and displays it.
 */
function ppInsertAvailablePaymentRequestButton({ elements, paymentRequest }: StripeService) {
    const $button = elements.create('paymentRequestButton', {
        paymentRequest: paymentRequest,
    });

    $button.mount('#pp-stripe-payment-request-btn');
    const $buttonContainer = document.querySelector<HTMLElement>('#pp-stripe-payment-request-btn');
    if ($buttonContainer) {
        $buttonContainer.style.display = 'block';
    }

    // Reinsert button if removed.
    document.addEventListener('pp-insert-button', () => {
        $button.mount('#pp-stripe-payment-request-btn');
        const $buttonContainer = document.querySelector<HTMLElement>('#pp-stripe-payment-request-btn');
        if ($buttonContainer) {
            $buttonContainer.style.display = 'block';
        }
    });
}

/**
 * Generates the structure the payment request api expects for different shipping options.
 */
function ppGetPaymentRequestShippingOptions(cartCalculationRecord: ICartCalculationRecord, currencyCode: string): StripeShippingOption[] {
    const options: StripeShippingOption[] = [];

    if (cartCalculationRecord[0].cart_meta.is_virtual) {
        return [{
            id: 'no-shipping-available',
            label: 'No Shipping',
            amount: 0,
        }];
    }

    const shipingMethods = cartCalculationRecord[0].package_record[0]?.methods ?? {};

    for (const methodKey of Object.keys(shipingMethods)) {
        options.push({
            id: methodKey,
            label: shipingMethods[methodKey]?.title ?? '',
            amount: ppCurrencyAmountToStripeFormat(shipingMethods[methodKey]?.total ?? 0, currencyCode),
        });
    }

    return options;
}

/**
 * Display items include the summary breakdown. Stripe does not validate if the numbers
 * add up to the total but everything should anyways.
 */
function ppGetPaymentRequestDisplayItems(cartCalculationRecord: ICartCalculationRecord, currencyCode: string): StripePaymentItem[] {
    const items: StripePaymentItem[] = [];

    // Subtotal
    items.push({
        label: 'Subtotal',
        amount: ppCurrencyAmountToStripeFormat(cartCalculationRecord[0].summary.subtotal, currencyCode),
    });

    // Coupons
    for (const [coupon, amount] of Object.entries(cartCalculationRecord[0].summary.coupons_record)) {
        if (!amount) {
            continue;
        }

        items.push({
            label: `Coupon - (${coupon})`,
            amount: -ppCurrencyAmountToStripeFormat(amount, currencyCode),
        });
    }

    // Fees`
    for (const [fee, amount] of Object.entries(cartCalculationRecord[0].summary.fees_record)) {
        if (!amount) {
            continue;
        }

        items.push({
            label: `Fee - (${fee})`,
            amount: ppCurrencyAmountToStripeFormat(amount, currencyCode),
        });
    }

    // Shipping
    if (!cartCalculationRecord[0].cart_meta.is_virtual) {
        items.push({
            label: 'Shipping',
            amount: ppCurrencyAmountToStripeFormat(cartCalculationRecord[0].summary.total_shipping, currencyCode),
        });
    }

    // Tax
    if (cartCalculationRecord[0].summary.total_tax > 0) {
        items.push({
            label: 'Tax',
            amount: ppCurrencyAmountToStripeFormat(cartCalculationRecord[0].summary.total_tax, currencyCode),
        });
    }

    // Gift cards
    for (const [giftCard, amount] of Object.entries(cartCalculationRecord[0].summary.gift_card_record)) {
        if (!amount) {
            continue;
        }

        items.push({
            label: `Gift card - (${giftCard})`,
            amount: -ppCurrencyAmountToStripeFormat(amount, currencyCode),
        });
    }

    return items;
}

/**
 * Formats a given amount to use its base units for stripe using a given currency code.
 * Ex: USD $10.43 -> 1043 cents
 */
function ppCurrencyAmountToStripeFormat(amount: number, currencyCode: string): number {
    const zeroDecimalCurrencies = new Set([
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'UGX',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    ]);

    if (!zeroDecimalCurrencies.has(currencyCode)) {
        return Math.round(amount * 100);
    }

    return Math.round(amount);
}
