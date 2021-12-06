"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
function ppOnWindowMessage(eventName, cb) {
    var _this = this;
    self.addEventListener('message', function (event) { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!(event.data.event === eventName)) return [3, 2];
                    return [4, cb(event.data)];
                case 1:
                    _a.sent();
                    _a.label = 2;
                case 2: return [2];
            }
        });
    }); }, false);
}
function ppIframeWindow() {
    var _a, _b;
    return (_b = (_a = document.querySelector('#peachpay-iframe')) === null || _a === void 0 ? void 0 : _a.contentWindow) !== null && _b !== void 0 ? _b : null;
}
function ppFetchIframeData(endpoint, request) {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            return [2, ppFetchWindowData(ppIframeWindow(), endpoint, request)];
        });
    });
}
function ppFetchWindowData(targetWindow, endpoint, request) {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            return [2, new Promise(function (resolve, reject) {
                    var timeoutId = setTimeout(function () {
                        reject(new Error("Window Fetch timed out for ".concat(endpoint, ".")));
                    }, 15000);
                    var channel = new MessageChannel();
                    channel.port1.onmessage = function (_a) {
                        var data = _a.data;
                        channel.port1.close();
                        clearTimeout(timeoutId);
                        if (data.error) {
                            reject(data.error);
                        }
                        else {
                            resolve(data.result);
                        }
                    };
                    if (!targetWindow) {
                        clearTimeout(timeoutId);
                        reject('Target window is not valid.');
                    }
                    else {
                        targetWindow.postMessage({
                            event: endpoint,
                            request: request
                        }, '*', [
                            channel.port2
                        ]);
                    }
                })];
        });
    });
}
function ppInitStripePaymentRequestSupport(message) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var publicKey, stripe, elements, paymentRequest, availableMethods, stripeService, injectStripeService, error_1;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    _b.trys.push([0, 2, , 3]);
                    publicKey = message.stripe.live ? 'pk_live_oROnIQDuexHZpnEOcUff3CRz00asaOOCAL' : 'pk_test_CnL2kA52V5dRqZbjlJ0sZ2gr00uBrOEmQQ';
                    stripe = Stripe(publicKey, {
                        locale: message.stripe.locale
                    });
                    elements = stripe.elements();
                    paymentRequest = stripe.paymentRequest({
                        country: 'US',
                        currency: message.currencyCode.toLowerCase(),
                        total: {
                            label: 'Total',
                            amount: ppCurrencyAmountToStripeFormat(message.cartCalculationRecord[0].summary.total, message.currencyCode)
                        },
                        displayItems: ppGetPaymentRequestDisplayItems(message.cartCalculationRecord, message.currencyCode),
                        shippingOptions: ppGetPaymentRequestShippingOptions(message.cartCalculationRecord, message.currencyCode),
                        requestPayerName: true,
                        requestPayerEmail: true,
                        requestPayerPhone: true,
                        requestShipping: true
                    });
                    return [4, paymentRequest.canMakePayment()];
                case 1:
                    availableMethods = _b.sent();
                    if (!availableMethods || !availableMethods.applePay && !availableMethods.googlePay) {
                        (_a = ppIframeWindow()) === null || _a === void 0 ? void 0 : _a.postMessage('pp-stripe-payment-request-stop', '*');
                        return [2];
                    }
                    stripeService = {
                        stripe: stripe,
                        elements: elements,
                        paymentRequest: paymentRequest
                    };
                    injectStripeService = function (stripeService, handler) { return function (stripeEvent) {
                        handler(stripeService, stripeEvent);
                    }; };
                    paymentRequest.on('shippingaddresschange', ppHandleShippingAddressChangeEvent);
                    paymentRequest.on('shippingoptionchange', ppHandleShippingOptionChangeEvent);
                    paymentRequest.on('token', injectStripeService(stripeService, ppHandleTokenEvent));
                    paymentRequest.on('cancel', injectStripeService(stripeService, ppHandleCancelEvent));
                    ppOnWindowMessage('pp-update-stripe-payment-request', injectStripeService(stripeService, ppHandleExternalStripeButtonDataEvent));
                    ppInsertAvailablePaymentRequestButton(stripeService);
                    return [3, 3];
                case 2:
                    error_1 = _b.sent();
                    if (error_1 instanceof Error) {
                        captureSentryException(new Error("Google Pay/Apple Pay failed to initilize on ".concat(location.hostname, ". Error: ").concat(error_1.message)));
                    }
                    return [3, 3];
                case 3: return [2];
            }
        });
    });
}
function ppHandleTokenEvent(_stripeService, event) {
    return __awaiter(this, void 0, void 0, function () {
        var request, response, error_2;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 2, , 3]);
                    request = {
                        token: event.token,
                        payerName: event.payerName,
                        payerEmail: event.payerEmail,
                        payerPhone: event.payerPhone,
                        shippingAddress: event.shippingAddress,
                        shippingOption: event.shippingOption,
                        walletName: event.walletName
                    };
                    return [4, ppFetchIframeData('pp-stripe-payment-request-process-payment', request)];
                case 1:
                    response = _a.sent();
                    event.complete(response.status);
                    self.postMessage({
                        event: 'pp-complete-transaction',
                        redirectURL: response.redirectURL
                    }, '*');
                    return [3, 3];
                case 2:
                    error_2 = _a.sent();
                    if (error_2 instanceof Error) {
                        if (error_2.message === "invalid_shipping_address") {
                            event.complete("invalid_shipping_address");
                            return [2];
                        }
                        captureSentryException(new Error("Google Pay/Apple Pay failed to handle token on ".concat(location.hostname, ". Error: ").concat(error_2.message)));
                        event.complete('fail');
                    }
                    return [3, 3];
                case 3: return [2];
            }
        });
    });
}
function ppHandleShippingAddressChangeEvent(event) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var response, error_3, isVirtual, methods;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    response = null;
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 3, , 4]);
                    return [4, ppFetchIframeData('pp-stripe-payment-request-address-change', event.shippingAddress)];
                case 2:
                    response = _c.sent();
                    return [3, 4];
                case 3:
                    error_3 = _c.sent();
                    if (error_3 instanceof Error) {
                        event.updateWith({
                            status: 'fail'
                        });
                        captureSentryException(new Error("Google Pay/Apple Pay failed requesting shipping address change on ".concat(location.hostname, ". Error: ").concat(error_3.message)));
                    }
                    return [2];
                case 4:
                    isVirtual = response.cartCalculationRecord[0].cart_meta.is_virtual;
                    methods = Object.entries((_b = (_a = response.cartCalculationRecord[0].package_record[0]) === null || _a === void 0 ? void 0 : _a.methods) !== null && _b !== void 0 ? _b : {});
                    if (!isVirtual && methods.length === 0) {
                        event.updateWith({
                            status: 'invalid_shipping_address',
                            total: {
                                label: 'Total',
                                amount: ppCurrencyAmountToStripeFormat(response.cartCalculationRecord[0].summary.total, response.currencyCode)
                            },
                            displayItems: ppGetPaymentRequestDisplayItems(response.cartCalculationRecord, response.currencyCode),
                            shippingOptions: ppGetPaymentRequestShippingOptions(response.cartCalculationRecord, response.currencyCode)
                        });
                    }
                    else {
                        event.updateWith({
                            status: 'success',
                            total: {
                                label: 'Total',
                                amount: ppCurrencyAmountToStripeFormat(response.cartCalculationRecord[0].summary.total, response.currencyCode)
                            },
                            displayItems: ppGetPaymentRequestDisplayItems(response.cartCalculationRecord, response.currencyCode),
                            shippingOptions: ppGetPaymentRequestShippingOptions(response.cartCalculationRecord, response.currencyCode)
                        });
                    }
                    return [2];
            }
        });
    });
}
function ppHandleShippingOptionChangeEvent(event) {
    return __awaiter(this, void 0, void 0, function () {
        var response, error_4;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 2, , 3]);
                    return [4, ppFetchIframeData('pp-stripe-payment-request-shipping-change', event.shippingOption)];
                case 1:
                    response = _a.sent();
                    event.updateWith({
                        status: 'success',
                        total: {
                            label: 'Total',
                            amount: ppCurrencyAmountToStripeFormat(response.cartCalculationRecord[0].summary.total, response.currencyCode)
                        },
                        displayItems: ppGetPaymentRequestDisplayItems(response.cartCalculationRecord, response.currencyCode),
                        shippingOptions: ppGetPaymentRequestShippingOptions(response.cartCalculationRecord, response.currencyCode)
                    });
                    return [3, 3];
                case 2:
                    error_4 = _a.sent();
                    if (error_4 instanceof Error) {
                        captureSentryException(new Error("Google Pay/Apple Pay failed changing shipping option on ".concat(location.hostname, ". Error: ").concat(error_4.message)));
                    }
                    event.updateWith({
                        status: 'fail'
                    });
                    return [3, 3];
                case 3: return [2];
            }
        });
    });
}
function ppHandleCancelEvent(_stripeService) {
}
function ppHandleExternalStripeButtonDataEvent(stripeService, message) {
    if (stripeService.paymentRequest.isShowing()) {
        return;
    }
    stripeService.paymentRequest.update({
        currency: message.currencyCode.toLowerCase(),
        total: {
            label: 'Total',
            amount: ppCurrencyAmountToStripeFormat(message.cartCalculationRecord[0].summary.total, message.currencyCode)
        },
        displayItems: ppGetPaymentRequestDisplayItems(message.cartCalculationRecord, message.currencyCode),
        shippingOptions: ppGetPaymentRequestShippingOptions(message.cartCalculationRecord, message.currencyCode)
    });
}
function ppInsertAvailablePaymentRequestButton(_a) {
    var elements = _a.elements, paymentRequest = _a.paymentRequest;
    var $button = elements.create('paymentRequestButton', {
        paymentRequest: paymentRequest
    });
    $button.mount('#pp-stripe-payment-request-btn');
    var $buttonContainer = document.querySelector('#pp-stripe-payment-request-btn');
    if ($buttonContainer) {
        $buttonContainer.style.display = 'block';
    }
    document.addEventListener('pp-insert-button', function () {
        $button.mount('#pp-stripe-payment-request-btn');
        var $buttonContainer = document.querySelector('#pp-stripe-payment-request-btn');
        if ($buttonContainer) {
            $buttonContainer.style.display = 'block';
        }
    });
}
function ppGetPaymentRequestShippingOptions(cartCalculationRecord, currencyCode) {
    var _a, _b, _c, _d, _e, _f;
    var options = [];
    if (cartCalculationRecord[0].cart_meta.is_virtual) {
        return [
            {
                id: 'no-shipping-available',
                label: 'No Shipping',
                amount: 0
            }
        ];
    }
    var shipingMethods = (_b = (_a = cartCalculationRecord[0].package_record[0]) === null || _a === void 0 ? void 0 : _a.methods) !== null && _b !== void 0 ? _b : {};
    for (var _i = 0, _g = Object.keys(shipingMethods); _i < _g.length; _i++) {
        var methodKey = _g[_i];
        options.push({
            id: methodKey,
            label: (_d = (_c = shipingMethods[methodKey]) === null || _c === void 0 ? void 0 : _c.title) !== null && _d !== void 0 ? _d : '',
            amount: ppCurrencyAmountToStripeFormat((_f = (_e = shipingMethods[methodKey]) === null || _e === void 0 ? void 0 : _e.total) !== null && _f !== void 0 ? _f : 0, currencyCode)
        });
    }
    return options;
}
function ppGetPaymentRequestDisplayItems(cartCalculationRecord, currencyCode) {
    var items = [];
    items.push({
        label: 'Subtotal',
        amount: ppCurrencyAmountToStripeFormat(cartCalculationRecord[0].summary.subtotal, currencyCode)
    });
    for (var _i = 0, _a = Object.entries(cartCalculationRecord[0].summary.coupons_record); _i < _a.length; _i++) {
        var _b = _a[_i], coupon = _b[0], amount = _b[1];
        if (!amount) {
            continue;
        }
        items.push({
            label: "Coupon - (".concat(coupon, ")"),
            amount: -ppCurrencyAmountToStripeFormat(amount, currencyCode)
        });
    }
    for (var _c = 0, _d = Object.entries(cartCalculationRecord[0].summary.fees_record); _c < _d.length; _c++) {
        var _e = _d[_c], fee = _e[0], amount1 = _e[1];
        if (!amount1) {
            continue;
        }
        items.push({
            label: "Fee - (".concat(fee, ")"),
            amount: ppCurrencyAmountToStripeFormat(amount1, currencyCode)
        });
    }
    if (!cartCalculationRecord[0].cart_meta.is_virtual) {
        items.push({
            label: 'Shipping',
            amount: ppCurrencyAmountToStripeFormat(cartCalculationRecord[0].summary.total_shipping, currencyCode)
        });
    }
    if (cartCalculationRecord[0].summary.total_tax > 0) {
        items.push({
            label: 'Tax',
            amount: ppCurrencyAmountToStripeFormat(cartCalculationRecord[0].summary.total_tax, currencyCode)
        });
    }
    for (var _f = 0, _g = Object.entries(cartCalculationRecord[0].summary.gift_card_record); _f < _g.length; _f++) {
        var _h = _g[_f], giftCard = _h[0], amount2 = _h[1];
        if (!amount2) {
            continue;
        }
        items.push({
            label: "Gift card - (".concat(giftCard, ")"),
            amount: -ppCurrencyAmountToStripeFormat(amount2, currencyCode)
        });
    }
    return items;
}
function ppCurrencyAmountToStripeFormat(amount, currencyCode) {
    var zeroDecimalCurrencies = new Set([
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
(function () {
    var _this = this;
    ppOnWindowMessage('pp-init-stripe-payment-request', function (message) { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, ppInitStripePaymentRequestSupport(message)];
                case 1:
                    _a.sent();
                    return [2];
            }
        });
    }); });
})();
