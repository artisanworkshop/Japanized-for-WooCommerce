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
    return ppFetchWindowData(ppIframeWindow(), endpoint, request);
}
function ppFetchWindowData(targetWindow, endpoint, request) {
    return new Promise(function (resolve, reject) {
        var channel = new MessageChannel();
        channel.port1.onmessage = function (_a) {
            var data = _a.data;
            channel.port1.close();
            if (data.error) {
                reject(data.error);
            }
            else {
                resolve(data.result);
            }
        };
        if (!targetWindow) {
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
    });
}
function buildStripeOptions(isOurStore, connectId) {
    if (isOurStore) {
        return {
            locale: 'auto'
        };
    }
    else {
        return {
            locale: 'auto',
            stripeAccount: connectId
        };
    }
}
function ppInitStripePaymentRequestSupport(message) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var stripe, elements, paymentRequest, availableMethods, stripeService1, injectStripeService, error_1;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    _b.trys.push([0, 2, , 3]);
                    stripe = Stripe(message.stripe.publicKey, buildStripeOptions(message.isOurStore, message.stripe.connectId));
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
                    if (!availableMethods) {
                        (_a = ppIframeWindow()) === null || _a === void 0 ? void 0 : _a.postMessage('pp-stripe-payment-request-stop', '*');
                        return [2];
                    }
                    stripeService1 = {
                        stripe: stripe,
                        elements: elements,
                        paymentRequest: paymentRequest
                    };
                    injectStripeService = function (stripeService, handler) { return function (stripeEvent) {
                        handler(stripeService, stripeEvent);
                    }; };
                    paymentRequest.on('shippingaddresschange', ppHandleShippingAddressChangeEvent);
                    paymentRequest.on('shippingoptionchange', ppHandleShippingOptionChangeEvent);
                    paymentRequest.on('token', injectStripeService(stripeService1, ppHandleTokenEvent));
                    paymentRequest.on('cancel', injectStripeService(stripeService1, ppHandleCancelEvent));
                    ppOnWindowMessage('pp-update-stripe-payment-request', injectStripeService(stripeService1, ppHandleExternalStripeButtonDataEvent));
                    ppInsertAvailablePaymentRequestButton(stripeService1);
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
                    return [4, ppFetchIframeData('pp-stripe-payment-request-confirm', request)];
                case 1:
                    response = _a.sent();
                    event.complete(response.status);
                    if (response.status !== 'success') {
                        return [2];
                    }
                    window.location = response.redirectURL;
                    return [3, 3];
                case 2:
                    error_2 = _a.sent();
                    if (error_2 instanceof Error) {
                        if (error_2.message === 'invalid_shipping_address') {
                            event.complete('invalid_shipping_address');
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
function ppHandleCancelEvent(_stripeService) { }
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
    var $buttonContainer1 = document.querySelector('#pp-stripe-payment-request-btn');
    if ($buttonContainer1) {
        $buttonContainer1.style.display = 'block';
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
    ppOnWindowMessage('pp-init-stripe-payment-request', function (message) {
        loadScript('https://js.stripe.com/v3/', function () { return __awaiter(_this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4, ppInitStripePaymentRequestSupport(message)];
                    case 1:
                        _a.sent();
                        return [2];
                }
            });
        }); });
    });
})();
function loadScript(src, callback) {
    if (document.querySelector("script[src=\"".concat(src, "\"]")) || window['Stripe']) {
        callback();
        return;
    }
    var $script = document.createElement('script');
    $script.type = 'text/javascript';
    $script.src = src;
    $script.onreadystatechange = callback;
    $script.onload = callback;
    document.head.appendChild($script);
}
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYnVuZGxlLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiLi4vaW50ZXJtZWRpYXRlL2J1bmRsZS5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBSUEsU0FBUyxpQkFBaUIsQ0FBQyxTQUFTLEVBQUUsRUFBRTtJQUF4QyxpQkFNQztJQUxHLElBQUksQ0FBQyxnQkFBZ0IsQ0FBQyxTQUFTLEVBQUUsVUFBTyxLQUFLOzs7O3lCQUNyQyxDQUFBLEtBQUssQ0FBQyxJQUFJLENBQUMsS0FBSyxLQUFLLFNBQVMsQ0FBQSxFQUE5QixjQUE4QjtvQkFDOUIsV0FBTSxFQUFFLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxFQUFBOztvQkFBcEIsU0FBb0IsQ0FBQzs7Ozs7U0FFNUIsRUFBRSxLQUFLLENBQUMsQ0FBQztBQUNkLENBQUM7QUFDRCxTQUFTLGNBQWM7O0lBQ25CLE9BQU8sTUFBQSxNQUFBLFFBQVEsQ0FBQyxhQUFhLENBQUMsa0JBQWtCLENBQUMsMENBQUUsYUFBYSxtQ0FBSSxJQUFJLENBQUM7QUFDN0UsQ0FBQztBQUNELFNBQVMsaUJBQWlCLENBQUMsUUFBUSxFQUFFLE9BQU87SUFDeEMsT0FBTyxpQkFBaUIsQ0FBQyxjQUFjLEVBQUUsRUFBRSxRQUFRLEVBQUUsT0FBTyxDQUFDLENBQUM7QUFDbEUsQ0FBQztBQUNELFNBQVMsaUJBQWlCLENBQUMsWUFBWSxFQUFFLFFBQVEsRUFBRSxPQUFPO0lBQ3RELE9BQU8sSUFBSSxPQUFPLENBQUMsVUFBQyxPQUFPLEVBQUUsTUFBTTtRQUMvQixJQUFNLE9BQU8sR0FBRyxJQUFJLGNBQWMsRUFBRSxDQUFDO1FBQ3JDLE9BQU8sQ0FBQyxLQUFLLENBQUMsU0FBUyxHQUFHLFVBQUMsRUFBUztnQkFBUCxJQUFJLFVBQUE7WUFDN0IsT0FBTyxDQUFDLEtBQUssQ0FBQyxLQUFLLEVBQUUsQ0FBQztZQUN0QixJQUFJLElBQUksQ0FBQyxLQUFLLEVB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RyxpQkFBaUIsQ0FBQyxnQ0FBZ0MsRUFBRSxVQUFDLE9BQU87UUFDeEQsVUFBVSxDQUFDLDJCQUEyQixFQUFFOzs7NEJBQ3BDLFdBQU0saUNBQWlDLENBQUMsT0FBTyxDQUFDLEVBQUE7O3dCQUFoRCxTQUFnRCxDQUFDOzs7O2FBQ3BELENBQUMsQ0FBQztJQUNQLENBQUMsQ0FBQyxDQUFDO0FBQ1AsQ0FBQyxDQUFDLEVBQUUsQ0FBQztBQUNMLFNBQVMsVUFBVSxDQUFDLEdBQUcsRUFBRSxRQUFRO0lBQzdCLElBQUksUUFBUSxDQUFDLGFBQWEsQ0FBQyx1QkFBZSxHQUFHLFFBQUksQ0FBQyxJQUFJLE1BQU0sQ0FBQyxRQUFRLENBQUMsRUFBRTtRQUNwRSxRQUFRLEVBQUUsQ0FBQztRQUNYLE9BQU87S0FDVjtJQUNELElBQU0sT0FBTyxHQUFHLFFBQVEsQ0FBQyxhQUFhLENBQUMsUUFBUSxDQUFDLENBQUM7SUFDakQsT0FBTyxDQUFDLElBQUksR0FBRyxpQkFBaUIsQ0FBQztJQUNqQyxPQUFPLENBQUMsR0FBRyxHQUFHLEdBQUcsQ0FBQztJQUNsQixPQUFPLENBQUMsa0JBQWtCLEdBQUcsUUFBUSxDQUFDO0lBQ3RDLE9BQU8sQ0FBQyxNQUFNLEdBQUcsUUFBUSxDQUFDO0lBQzFCLFFBQVEsQ0FBQyxJQUFJLENBQUMsV0FBVyxDQUFDLE9BQU8sQ0FBQyxDQUFDO0FBQ3ZDLENBQUMiLCJzb3VyY2VzQ29udGVudCI6WyIvLyBkZW5vLWZtdC1pZ25vcmUtZmlsZVxuLy8gZGVuby1saW50LWlnbm9yZS1maWxlXG4vLyBUaGlzIGNvZGUgd2FzIGJ1bmRsZWQgdXNpbmcgYGRlbm8gYnVuZGxlYCBhbmQgaXQncyBub3QgcmVjb21tZW5kZWQgdG8gZWRpdCBpdCBtYW51YWxseVxuXG5mdW5jdGlvbiBwcE9uV2luZG93TWVzc2FnZShldmVudE5hbWUsIGNiKSB7XG4gICAgc2VsZi5hZGRFdmVudExpc3RlbmVyKCdtZXNzYWdlJywgYXN5bmMgKGV2ZW50KT0+e1xuICAgICAgICBpZiAoZXZlbnQuZGF0YS5ldmVudCA9PT0gZXZlbnROYW1lKSB7XG4gICAgICAgICAgICBhd2FpdCBjYihldmVudC5kYXRhKTtcbiAgICAgICAgfVxuICAgIH0sIGZhbHNlKTtcbn1cbmZ1bmN0aW9uIHBwSWZyYW1lV2luZG93KCkge1xuICAgIHJldHVybiBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjcGVhY2hwYXktaWZyYW1lJyk/LmNvbnRlbnRXaW5kb3cgPz8gbnVsbDtcbn1cbmZ1bmN0aW9uIHBwRmV0Y2hJZnJhbWVEYXRhKGVuZHBvaW50LCByZXF1ZXN0KSB7XG4gICAgcmV0dXJuIHBwRmV0Y2hXaW5kb3dEYXRhKHBwSWZyYW1lV2luZG93KCksIGVuZHBvaW50LCByZXF1ZXN0KTtcbn1cbmZ1bmN0aW9uIHBwRmV0Y2hXaW5kb3dEYXRhKHRhcmdldFdpbmRvdywgZW5kcG9pbnQsIHJlcXVlc3QpIHtcbiAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUsIHJlamVjdCk9PntcbiAgICAgICAgY29uc3QgY2hhbm5lbCA9IG5ldyBNZXNzYWdlQ2hhbm5lbCgpO1xuICAgICAgICBjaGFubmVsLnBvcnQxLm9ubWVzc2FnZSA9ICh7IGRhdGEgIH0pPT57XG4gICAgICAgICAgICBjaGFubmVsLnBvcnQxLmNsb3NlKCk7XG4gICAgICAgICAgICBpZiAoZGF0YS5lcnJvcikge1xuICAgICAgICAgICAgICAgIHJlamVjdChkYXRhLmVycm9yKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgcmVzb2x2ZShkYXRhLnJlc3VsdCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIGlmICghdGFyZ2V0V2luZG93KSB7XG4gICAgICAgICAgICByZWplY3QoJ1RhcmdldCB3aW5kb3cgaXMgbm90IHZhbGlkLicpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGFyZ2V0V2luZG93LnBvc3RNZXNzYWdlKHtcbiAgICAgICAgICAgICAgICBldmVudDogZW5kcG9pbnQsXG4gICAgICAgICAgICAgICAgcmVxdWVzdDogcmVxdWVzdFxuICAgICAgICAgICAgfSwgJyonLCBbXG4gICAgICAgICAgICAgICAgY2hhbm5lbC5wb3J0MlxuICAgICAgICAgICAgXSk7XG4gICAgICAgIH1cbiAgICB9KTtcbn1cbmZ1bmN0aW9uIGJ1aWxkU3RyaXBlT3B0aW9ucyhpc091clN0b3JlLCBjb25uZWN0SWQpIHtcbiAgICBpZiAoaXNPdXJTdG9yZSkge1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgbG9jYWxlOiAnYXV0bydcbiAgICAgICAgfTtcbiAgICB9IGVsc2Uge1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgbG9jYWxlOiAnYXV0bycsXG4gICAgICAgICAgICBzdHJpcGVBY2NvdW50OiBjb25uZWN0SWRcbiAgICAgICAgfTtcbiAgICB9XG59XG5hc3luYyBmdW5jdGlvbiBwcEluaXRTdHJpcGVQYXltZW50UmVxdWVzdFN1cHBvcnQobWVzc2FnZSkge1xuICAgIHRyeSB7XG4gICAgICAgIGNvbnN0IHN0cmlwZSA9IFN0cmlwZShtZXNzYWdlLnN0cmlwZS5wdWJsaWNLZXksIGJ1aWxkU3RyaXBlT3B0aW9ucyhtZXNzYWdlLmlzT3VyU3RvcmUsIG1lc3NhZ2Uuc3RyaXBlLmNvbm5lY3RJZCkpO1xuICAgICAgICBjb25zdCBlbGVtZW50cyA9IHN0cmlwZS5lbGVtZW50cygpO1xuICAgICAgICBjb25zdCBwYXltZW50UmVxdWVzdCA9IHN0cmlwZS5wYXltZW50UmVxdWVzdCh7XG4gICAgICAgICAgICBjb3VudHJ5OiAnVVMnLFxuICAgICAgICAgICAgY3VycmVuY3k6IG1lc3NhZ2UuY3VycmVuY3lDb2RlLnRvTG93ZXJDYXNlKCksXG4gICAgICAgICAgICB0b3RhbDoge1xuICAgICAgICAgICAgICAgIGxhYmVsOiAnVG90YWwnLFxuICAgICAgICAgICAgICAgIGFtb3VudDogcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KG1lc3NhZ2UuY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkudG90YWwsIG1lc3NhZ2UuY3VycmVuY3lDb2RlKVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGRpc3BsYXlJdGVtczogcHBHZXRQYXltZW50UmVxdWVzdERpc3BsYXlJdGVtcyhtZXNzYWdlLmNhcnRDYWxjdWxhdGlvblJlY29yZCwgbWVzc2FnZS5jdXJyZW5jeUNvZGUpLFxuICAgICAgICAgICAgc2hpcHBpbmdPcHRpb25zOiBwcEdldFBheW1lbnRSZXF1ZXN0U2hpcHBpbmdPcHRpb25zKG1lc3NhZ2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCBtZXNzYWdlLmN1cnJlbmN5Q29kZSksXG4gICAgICAgICAgICByZXF1ZXN0UGF5ZXJOYW1lOiB0cnVlLFxuICAgICAgICAgICAgcmVxdWVzdFBheWVyRW1haWw6IHRydWUsXG4gICAgICAgICAgICByZXF1ZXN0UGF5ZXJQaG9uZTogdHJ1ZSxcbiAgICAgICAgICAgIHJlcXVlc3RTaGlwcGluZzogdHJ1ZVxuICAgICAgICB9KTtcbiAgICAgICAgY29uc3QgYXZhaWxhYmxlTWV0aG9kcyA9IGF3YWl0IHBheW1lbnRSZXF1ZXN0LmNhbk1ha2VQYXltZW50KCk7XG4gICAgICAgIGlmICghYXZhaWxhYmxlTWV0aG9kcykge1xuICAgICAgICAgICAgcHBJZnJhbWVXaW5kb3coKT8ucG9zdE1lc3NhZ2UoJ3BwLXN0cmlwZS1wYXltZW50LXJlcXVlc3Qtc3RvcCcsICcqJyk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgY29uc3Qgc3RyaXBlU2VydmljZTEgPSB7XG4gICAgICAgICAgICBzdHJpcGUsXG4gICAgICAgICAgICBlbGVtZW50cyxcbiAgICAgICAgICAgIHBheW1lbnRSZXF1ZXN0XG4gICAgICAgIH07XG4gICAgICAgIGNvbnN0IGluamVjdFN0cmlwZVNlcnZpY2UgPSAoc3RyaXBlU2VydmljZSwgaGFuZGxlcik9PihzdHJpcGVFdmVudCk9PntcbiAgICAgICAgICAgICAgICBoYW5kbGVyKHN0cmlwZVNlcnZpY2UsIHN0cmlwZUV2ZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgO1xuICAgICAgICBwYXltZW50UmVxdWVzdC5vbignc2hpcHBpbmdhZGRyZXNzY2hhbmdlJywgcHBIYW5kbGVTaGlwcGluZ0FkZHJlc3NDaGFuZ2VFdmVudCk7XG4gICAgICAgIHBheW1lbnRSZXF1ZXN0Lm9uKCdzaGlwcGluZ29wdGlvbmNoYW5nZScsIHBwSGFuZGxlU2hpcHBpbmdPcHRpb25DaGFuZ2VFdmVudCk7XG4gICAgICAgIHBheW1lbnRSZXF1ZXN0Lm9uKCd0b2tlbicsIGluamVjdFN0cmlwZVNlcnZpY2Uoc3RyaXBlU2VydmljZTEsIHBwSGFuZGxlVG9rZW5FdmVudCkpO1xuICAgICAgICBwYXltZW50UmVxdWVzdC5vbignY2FuY2VsJywgaW5qZWN0U3RyaXBlU2VydmljZShzdHJpcGVTZXJ2aWNlMSwgcHBIYW5kbGVDYW5jZWxFdmVudCkpO1xuICAgICAgICBwcE9uV2luZG93TWVzc2FnZSgncHAtdXBkYXRlLXN0cmlwZS1wYXltZW50LXJlcXVlc3QnLCBpbmplY3RTdHJpcGVTZXJ2aWNlKHN0cmlwZVNlcnZpY2UxLCBwcEhhbmRsZUV4dGVybmFsU3RyaXBlQnV0dG9uRGF0YUV2ZW50KSk7XG4gICAgICAgIHBwSW5zZXJ0QXZhaWxhYmxlUGF5bWVudFJlcXVlc3RCdXR0b24oc3RyaXBlU2VydmljZTEpO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgR29vZ2xlIFBheS9BcHBsZSBQYXkgZmFpbGVkIHRvIGluaXRpbGl6ZSBvbiAke2xvY2F0aW9uLmhvc3RuYW1lfS4gRXJyb3I6ICR7ZXJyb3IubWVzc2FnZX1gKSk7XG4gICAgICAgIH1cbiAgICB9XG59XG5hc3luYyBmdW5jdGlvbiBwcEhhbmRsZVRva2VuRXZlbnQoX3N0cmlwZVNlcnZpY2UsIGV2ZW50KSB7XG4gICAgdHJ5IHtcbiAgICAgICAgY29uc3QgcmVxdWVzdCA9IHtcbiAgICAgICAgICAgIHRva2VuOiBldmVudC50b2tlbixcbiAgICAgICAgICAgIHBheWVyTmFtZTogZXZlbnQucGF5ZXJOYW1lLFxuICAgICAgICAgICAgcGF5ZXJFbWFpbDogZXZlbnQucGF5ZXJFbWFpbCxcbiAgICAgICAgICAgIHBheWVyUGhvbmU6IGV2ZW50LnBheWVyUGhvbmUsXG4gICAgICAgICAgICBzaGlwcGluZ0FkZHJlc3M6IGV2ZW50LnNoaXBwaW5nQWRkcmVzcyxcbiAgICAgICAgICAgIHNoaXBwaW5nT3B0aW9uOiBldmVudC5zaGlwcGluZ09wdGlvbixcbiAgICAgICAgICAgIHdhbGxldE5hbWU6IGV2ZW50LndhbGxldE5hbWVcbiAgICAgICAgfTtcbiAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBwcEZldGNoSWZyYW1lRGF0YSgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1jb25maXJtJywgcmVxdWVzdCk7XG4gICAgICAgIGV2ZW50LmNvbXBsZXRlKHJlc3BvbnNlLnN0YXR1cyk7XG4gICAgICAgIGlmIChyZXNwb25zZS5zdGF0dXMgIT09ICdzdWNjZXNzJykge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIHdpbmRvdy5sb2NhdGlvbiA9IHJlc3BvbnNlLnJlZGlyZWN0VVJMO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBpZiAoZXJyb3IubWVzc2FnZSA9PT0gJ2ludmFsaWRfc2hpcHBpbmdfYWRkcmVzcycpIHtcbiAgICAgICAgICAgICAgICBldmVudC5jb21wbGV0ZSgnaW52YWxpZF9zaGlwcGluZ19hZGRyZXNzJyk7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY2FwdHVyZVNlbnRyeUV4Y2VwdGlvbihuZXcgRXJyb3IoYEdvb2dsZSBQYXkvQXBwbGUgUGF5IGZhaWxlZCB0byBoYW5kbGUgdG9rZW4gb24gJHtsb2NhdGlvbi5ob3N0bmFtZX0uIEVycm9yOiAke2Vycm9yLm1lc3NhZ2V9YCkpO1xuICAgICAgICAgICAgZXZlbnQuY29tcGxldGUoJ2ZhaWwnKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmFzeW5jIGZ1bmN0aW9uIHBwSGFuZGxlU2hpcHBpbmdBZGRyZXNzQ2hhbmdlRXZlbnQoZXZlbnQpIHtcbiAgICBsZXQgcmVzcG9uc2UgPSBudWxsO1xuICAgIHRyeSB7XG4gICAgICAgIHJlc3BvbnNlID0gYXdhaXQgcHBGZXRjaElmcmFtZURhdGEoJ3BwLXN0cmlwZS1wYXltZW50LXJlcXVlc3QtYWRkcmVzcy1jaGFuZ2UnLCBldmVudC5zaGlwcGluZ0FkZHJlc3MpO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBldmVudC51cGRhdGVXaXRoKHtcbiAgICAgICAgICAgICAgICBzdGF0dXM6ICdmYWlsJ1xuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgR29vZ2xlIFBheS9BcHBsZSBQYXkgZmFpbGVkIHJlcXVlc3Rpbmcgc2hpcHBpbmcgYWRkcmVzcyBjaGFuZ2Ugb24gJHtsb2NhdGlvbi5ob3N0bmFtZX0uIEVycm9yOiAke2Vycm9yLm1lc3NhZ2V9YCkpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3QgaXNWaXJ0dWFsID0gcmVzcG9uc2UuY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLmNhcnRfbWV0YS5pc192aXJ0dWFsO1xuICAgIGNvbnN0IG1ldGhvZHMgPSBPYmplY3QuZW50cmllcyhyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0ucGFja2FnZV9yZWNvcmRbMF0/Lm1ldGhvZHMgPz8ge30pO1xuICAgIGlmICghaXNWaXJ0dWFsICYmIG1ldGhvZHMubGVuZ3RoID09PSAwKSB7XG4gICAgICAgIGV2ZW50LnVwZGF0ZVdpdGgoe1xuICAgICAgICAgICAgc3RhdHVzOiAnaW52YWxpZF9zaGlwcGluZ19hZGRyZXNzJyxcbiAgICAgICAgICAgIHRvdGFsOiB7XG4gICAgICAgICAgICAgICAgbGFiZWw6ICdUb3RhbCcsXG4gICAgICAgICAgICAgICAgYW1vdW50OiBwcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQocmVzcG9uc2UuY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkudG90YWwsIHJlc3BvbnNlLmN1cnJlbmN5Q29kZSlcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBkaXNwbGF5SXRlbXM6IHBwR2V0UGF5bWVudFJlcXVlc3REaXNwbGF5SXRlbXMocmVzcG9uc2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCByZXNwb25zZS5jdXJyZW5jeUNvZGUpLFxuICAgICAgICAgICAgc2hpcHBpbmdPcHRpb25zOiBwcEdldFBheW1lbnRSZXF1ZXN0U2hpcHBpbmdPcHRpb25zKHJlc3BvbnNlLmNhcnRDYWxjdWxhdGlvblJlY29yZCwgcmVzcG9uc2UuY3VycmVuY3lDb2RlKVxuICAgICAgICB9KTtcbiAgICB9IGVsc2Uge1xuICAgICAgICBldmVudC51cGRhdGVXaXRoKHtcbiAgICAgICAgICAgIHN0YXR1czogJ3N1Y2Nlc3MnLFxuICAgICAgICAgICAgdG90YWw6IHtcbiAgICAgICAgICAgICAgICBsYWJlbDogJ1RvdGFsJyxcbiAgICAgICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS50b3RhbCwgcmVzcG9uc2UuY3VycmVuY3lDb2RlKVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGRpc3BsYXlJdGVtczogcHBHZXRQYXltZW50UmVxdWVzdERpc3BsYXlJdGVtcyhyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIHJlc3BvbnNlLmN1cnJlbmN5Q29kZSksXG4gICAgICAgICAgICBzaGlwcGluZ09wdGlvbnM6IHBwR2V0UGF5bWVudFJlcXVlc3RTaGlwcGluZ09wdGlvbnMocmVzcG9uc2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCByZXNwb25zZS5jdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH1cbn1cbmFzeW5jIGZ1bmN0aW9uIHBwSGFuZGxlU2hpcHBpbmdPcHRpb25DaGFuZ2VFdmVudChldmVudCkge1xuICAgIHRyeSB7XG4gICAgICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgcHBGZXRjaElmcmFtZURhdGEoJ3BwLXN0cmlwZS1wYXltZW50LXJlcXVlc3Qtc2hpcHBpbmctY2hhbmdlJywgZXZlbnQuc2hpcHBpbmdPcHRpb24pO1xuICAgICAgICBldmVudC51cGRhdGVXaXRoKHtcbiAgICAgICAgICAgIHN0YXR1czogJ3N1Y2Nlc3MnLFxuICAgICAgICAgICAgdG90YWw6IHtcbiAgICAgICAgICAgICAgICBsYWJlbDogJ1RvdGFsJyxcbiAgICAgICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS50b3RhbCwgcmVzcG9uc2UuY3VycmVuY3lDb2RlKVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGRpc3BsYXlJdGVtczogcHBHZXRQYXltZW50UmVxdWVzdERpc3BsYXlJdGVtcyhyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIHJlc3BvbnNlLmN1cnJlbmN5Q29kZSksXG4gICAgICAgICAgICBzaGlwcGluZ09wdGlvbnM6IHBwR2V0UGF5bWVudFJlcXVlc3RTaGlwcGluZ09wdGlvbnMocmVzcG9uc2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCByZXNwb25zZS5jdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgR29vZ2xlIFBheS9BcHBsZSBQYXkgZmFpbGVkIGNoYW5naW5nIHNoaXBwaW5nIG9wdGlvbiBvbiAke2xvY2F0aW9uLmhvc3RuYW1lfS4gRXJyb3I6ICR7ZXJyb3IubWVzc2FnZX1gKSk7XG4gICAgICAgIH1cbiAgICAgICAgZXZlbnQudXBkYXRlV2l0aCh7XG4gICAgICAgICAgICBzdGF0dXM6ICdmYWlsJ1xuICAgICAgICB9KTtcbiAgICB9XG59XG5mdW5jdGlvbiBwcEhhbmRsZUNhbmNlbEV2ZW50KF9zdHJpcGVTZXJ2aWNlKSB7fVxuZnVuY3Rpb24gcHBIYW5kbGVFeHRlcm5hbFN0cmlwZUJ1dHRvbkRhdGFFdmVudChzdHJpcGVTZXJ2aWNlLCBtZXNzYWdlKSB7XG4gICAgaWYgKHN0cmlwZVNlcnZpY2UucGF5bWVudFJlcXVlc3QuaXNTaG93aW5nKCkpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBzdHJpcGVTZXJ2aWNlLnBheW1lbnRSZXF1ZXN0LnVwZGF0ZSh7XG4gICAgICAgIGN1cnJlbmN5OiBtZXNzYWdlLmN1cnJlbmN5Q29kZS50b0xvd2VyQ2FzZSgpLFxuICAgICAgICB0b3RhbDoge1xuICAgICAgICAgICAgbGFiZWw6ICdUb3RhbCcsXG4gICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChtZXNzYWdlLmNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LnRvdGFsLCBtZXNzYWdlLmN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSxcbiAgICAgICAgZGlzcGxheUl0ZW1zOiBwcEdldFBheW1lbnRSZXF1ZXN0RGlzcGxheUl0ZW1zKG1lc3NhZ2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCBtZXNzYWdlLmN1cnJlbmN5Q29kZSksXG4gICAgICAgIHNoaXBwaW5nT3B0aW9uczogcHBHZXRQYXltZW50UmVxdWVzdFNoaXBwaW5nT3B0aW9ucyhtZXNzYWdlLmNhcnRDYWxjdWxhdGlvblJlY29yZCwgbWVzc2FnZS5jdXJyZW5jeUNvZGUpXG4gICAgfSk7XG59XG5mdW5jdGlvbiBwcEluc2VydEF2YWlsYWJsZVBheW1lbnRSZXF1ZXN0QnV0dG9uKHsgZWxlbWVudHMgLCBwYXltZW50UmVxdWVzdCAgfSkge1xuICAgIGNvbnN0ICRidXR0b24gPSBlbGVtZW50cy5jcmVhdGUoJ3BheW1lbnRSZXF1ZXN0QnV0dG9uJywge1xuICAgICAgICBwYXltZW50UmVxdWVzdDogcGF5bWVudFJlcXVlc3RcbiAgICB9KTtcbiAgICAkYnV0dG9uLm1vdW50KCcjcHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1idG4nKTtcbiAgICBjb25zdCAkYnV0dG9uQ29udGFpbmVyMSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNwcC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0LWJ0bicpO1xuICAgIGlmICgkYnV0dG9uQ29udGFpbmVyMSkge1xuICAgICAgICAkYnV0dG9uQ29udGFpbmVyMS5zdHlsZS5kaXNwbGF5ID0gJ2Jsb2NrJztcbiAgICB9XG4gICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcigncHAtaW5zZXJ0LWJ1dHRvbicsICgpPT57XG4gICAgICAgICRidXR0b24ubW91bnQoJyNwcC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0LWJ0bicpO1xuICAgICAgICBjb25zdCAkYnV0dG9uQ29udGFpbmVyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3BwLXN0cmlwZS1wYXltZW50LXJlcXVlc3QtYnRuJyk7XG4gICAgICAgIGlmICgkYnV0dG9uQ29udGFpbmVyKSB7XG4gICAgICAgICAgICAkYnV0dG9uQ29udGFpbmVyLnN0eWxlLmRpc3BsYXkgPSAnYmxvY2snO1xuICAgICAgICB9XG4gICAgfSk7XG59XG5mdW5jdGlvbiBwcEdldFBheW1lbnRSZXF1ZXN0U2hpcHBpbmdPcHRpb25zKGNhcnRDYWxjdWxhdGlvblJlY29yZCwgY3VycmVuY3lDb2RlKSB7XG4gICAgY29uc3Qgb3B0aW9ucyA9IFtdO1xuICAgIGlmIChjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uY2FydF9tZXRhLmlzX3ZpcnR1YWwpIHtcbiAgICAgICAgcmV0dXJuIFtcbiAgICAgICAgICAgIHtcbiAgICAgICAgICAgICAgICBpZDogJ25vLXNoaXBwaW5nLWF2YWlsYWJsZScsXG4gICAgICAgICAgICAgICAgbGFiZWw6ICdObyBTaGlwcGluZycsXG4gICAgICAgICAgICAgICAgYW1vdW50OiAwXG4gICAgICAgICAgICB9XG4gICAgICAgIF07XG4gICAgfVxuICAgIGNvbnN0IHNoaXBpbmdNZXRob2RzID0gY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnBhY2thZ2VfcmVjb3JkWzBdPy5tZXRob2RzID8/IHt9O1xuICAgIGZvciAoY29uc3QgbWV0aG9kS2V5IG9mIE9iamVjdC5rZXlzKHNoaXBpbmdNZXRob2RzKSl7XG4gICAgICAgIG9wdGlvbnMucHVzaCh7XG4gICAgICAgICAgICBpZDogbWV0aG9kS2V5LFxuICAgICAgICAgICAgbGFiZWw6IHNoaXBpbmdNZXRob2RzW21ldGhvZEtleV0/LnRpdGxlID8/ICcnLFxuICAgICAgICAgICAgYW1vdW50OiBwcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQoc2hpcGluZ01ldGhvZHNbbWV0aG9kS2V5XT8udG90YWwgPz8gMCwgY3VycmVuY3lDb2RlKVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgcmV0dXJuIG9wdGlvbnM7XG59XG5mdW5jdGlvbiBwcEdldFBheW1lbnRSZXF1ZXN0RGlzcGxheUl0ZW1zKGNhcnRDYWxjdWxhdGlvblJlY29yZCwgY3VycmVuY3lDb2RlKSB7XG4gICAgY29uc3QgaXRlbXMgPSBbXTtcbiAgICBpdGVtcy5wdXNoKHtcbiAgICAgICAgbGFiZWw6ICdTdWJ0b3RhbCcsXG4gICAgICAgIGFtb3VudDogcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KGNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LnN1YnRvdGFsLCBjdXJyZW5jeUNvZGUpXG4gICAgfSk7XG4gICAgZm9yIChjb25zdCBbY291cG9uLCBhbW91bnRdIG9mIE9iamVjdC5lbnRyaWVzKGNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LmNvdXBvbnNfcmVjb3JkKSl7XG4gICAgICAgIGlmICghYW1vdW50KSB7XG4gICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgfVxuICAgICAgICBpdGVtcy5wdXNoKHtcbiAgICAgICAgICAgIGxhYmVsOiBgQ291cG9uIC0gKCR7Y291cG9ufSlgLFxuICAgICAgICAgICAgYW1vdW50OiAtcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KGFtb3VudCwgY3VycmVuY3lDb2RlKVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZm9yIChjb25zdCBbZmVlLCBhbW91bnQxXSBvZiBPYmplY3QuZW50cmllcyhjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS5mZWVzX3JlY29yZCkpe1xuICAgICAgICBpZiAoIWFtb3VudDEpIHtcbiAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICB9XG4gICAgICAgIGl0ZW1zLnB1c2goe1xuICAgICAgICAgICAgbGFiZWw6IGBGZWUgLSAoJHtmZWV9KWAsXG4gICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChhbW91bnQxLCBjdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBpZiAoIWNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5jYXJ0X21ldGEuaXNfdmlydHVhbCkge1xuICAgICAgICBpdGVtcy5wdXNoKHtcbiAgICAgICAgICAgIGxhYmVsOiAnU2hpcHBpbmcnLFxuICAgICAgICAgICAgYW1vdW50OiBwcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQoY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkudG90YWxfc2hpcHBpbmcsIGN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGlmIChjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS50b3RhbF90YXggPiAwKSB7XG4gICAgICAgIGl0ZW1zLnB1c2goe1xuICAgICAgICAgICAgbGFiZWw6ICdUYXgnLFxuICAgICAgICAgICAgYW1vdW50OiBwcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQoY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkudG90YWxfdGF4LCBjdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0IFtnaWZ0Q2FyZCwgYW1vdW50Ml0gb2YgT2JqZWN0LmVudHJpZXMoY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkuZ2lmdF9jYXJkX3JlY29yZCkpe1xuICAgICAgICBpZiAoIWFtb3VudDIpIHtcbiAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICB9XG4gICAgICAgIGl0ZW1zLnB1c2goe1xuICAgICAgICAgICAgbGFiZWw6IGBHaWZ0IGNhcmQgLSAoJHtnaWZ0Q2FyZH0pYCxcbiAgICAgICAgICAgIGFtb3VudDogLXBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChhbW91bnQyLCBjdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICByZXR1cm4gaXRlbXM7XG59XG5mdW5jdGlvbiBwcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQoYW1vdW50LCBjdXJyZW5jeUNvZGUpIHtcbiAgICBjb25zdCB6ZXJvRGVjaW1hbEN1cnJlbmNpZXMgPSBuZXcgU2V0KFtcbiAgICAgICAgJ0JJRicsXG4gICAgICAgICdDTFAnLFxuICAgICAgICAnREpGJyxcbiAgICAgICAgJ0dORicsXG4gICAgICAgICdKUFknLFxuICAgICAgICAnS01GJyxcbiAgICAgICAgJ0tSVycsXG4gICAgICAgICdNR0EnLFxuICAgICAgICAnUFlHJyxcbiAgICAgICAgJ1JXRicsXG4gICAgICAgICdVR1gnLFxuICAgICAgICAnVk5EJyxcbiAgICAgICAgJ1ZVVicsXG4gICAgICAgICdYQUYnLFxuICAgICAgICAnWE9GJyxcbiAgICAgICAgJ1hQRicsIFxuICAgIF0pO1xuICAgIGlmICghemVyb0RlY2ltYWxDdXJyZW5jaWVzLmhhcyhjdXJyZW5jeUNvZGUpKSB7XG4gICAgICAgIHJldHVybiBNYXRoLnJvdW5kKGFtb3VudCAqIDEwMCk7XG4gICAgfVxuICAgIHJldHVybiBNYXRoLnJvdW5kKGFtb3VudCk7XG59XG4oZnVuY3Rpb24oKSB7XG4gICAgcHBPbldpbmRvd01lc3NhZ2UoJ3BwLWluaXQtc3RyaXBlLXBheW1lbnQtcmVxdWVzdCcsIChtZXNzYWdlKT0+e1xuICAgICAgICBsb2FkU2NyaXB0KCdodHRwczovL2pzLnN0cmlwZS5jb20vdjMvJywgYXN5bmMgKCk9PntcbiAgICAgICAgICAgIGF3YWl0IHBwSW5pdFN0cmlwZVBheW1lbnRSZXF1ZXN0U3VwcG9ydChtZXNzYWdlKTtcbiAgICAgICAgfSk7XG4gICAgfSk7XG59KSgpO1xuZnVuY3Rpb24gbG9hZFNjcmlwdChzcmMsIGNhbGxiYWNrKSB7XG4gICAgaWYgKGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoYHNjcmlwdFtzcmM9XCIke3NyY31cIl1gKSB8fCB3aW5kb3dbJ1N0cmlwZSddKSB7XG4gICAgICAgIGNhbGxiYWNrKCk7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3QgJHNjcmlwdCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3NjcmlwdCcpO1xuICAgICRzY3JpcHQudHlwZSA9ICd0ZXh0L2phdmFzY3JpcHQnO1xuICAgICRzY3JpcHQuc3JjID0gc3JjO1xuICAgICRzY3JpcHQub25yZWFkeXN0YXRlY2hhbmdlID0gY2FsbGJhY2s7XG4gICAgJHNjcmlwdC5vbmxvYWQgPSBjYWxsYmFjaztcbiAgICBkb2N1bWVudC5oZWFkLmFwcGVuZENoaWxkKCRzY3JpcHQpO1xufVxuIl19