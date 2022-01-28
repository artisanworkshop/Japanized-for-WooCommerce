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
    });
}
function ppInitStripePaymentRequestSupport(message) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var publicKey, stripe, elements, paymentRequest, availableMethods, stripeService1, injectStripeService, error_1;
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYnVuZGxlLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiLi4vaW50ZXJtZWRpYXRlL2J1bmRsZS5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7O0FBSUEsU0FBUyxpQkFBaUIsQ0FBQyxTQUFTLEVBQUUsRUFBRTtJQUF4QyxpQkFNQztJQUxHLElBQUksQ0FBQyxnQkFBZ0IsQ0FBQyxTQUFTLEVBQUUsVUFBTyxLQUFLOzs7O3lCQUNyQyxDQUFBLEtBQUssQ0FBQyxJQUFJLENBQUMsS0FBSyxLQUFLLFNBQVMsQ0FBQSxFQUE5QixjQUE4QjtvQkFDOUIsV0FBTSxFQUFFLENBQUMsS0FBSyxDQUFDLElBQUksQ0FBQyxFQUFBOztvQkFBcEIsU0FBb0IsQ0FBQzs7Ozs7U0FFNUIsRUFBRSxLQUFLLENBQUMsQ0FBQztBQUNkLENBQUM7QUFDRCxTQUFTLGNBQWM7O0lBQ25CLE9BQU8sTUFBQSxNQUFBLFFBQVEsQ0FBQyxhQUFhLENBQUMsa0JBQWtCLENBQUMsMENBQUUsYUFBYSxtQ0FBSSxJQUFJLENBQUM7QUFDN0UsQ0FBQztBQUNELFNBQVMsaUJBQWlCLENBQUMsUUFBUSxFQUFFLE9BQU87SUFDeEMsT0FBTyxpQkFBaUIsQ0FBQyxjQUFjLEVBQUUsRUFBRSxRQUFRLEVBQUUsT0FBTyxDQUFDLENBQUM7QUFDbEUsQ0FBQztBQUNELFNBQVMsaUJBQWlCLENBQUMsWUFBWSxFQUFFLFFBQVEsRUFBRSxPQUFPO0lBQ3RELE9BQU8sSUFBSSxPQUFPLENBQUMsVUFBQyxPQUFPLEVBQUUsTUFBTTtRQUMvQixJQUFNLFNBQVMsR0FBRyxVQUFVLENBQUM7WUFDekIsTUFBTSxDQUFDLElBQUksS0FBSyxDQUFDLHFDQUE4QixRQUFRLE1BQUcsQ0FBQyxDQUFDLENBQUM7UUFDakUsQ0FBQyxFQUFFLEtBQUssQ0FBQyxDQUFDO1FBQ1YsSUFBTSxPQUFPLEdBQUcsSUFBSSxjQUFjLEVBQUUsQ0FBQztRQUNyQyxP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QUMsTUFBTSxHQUFHLEdBQUcsQ0FBQyxDQUFDO0tBQ25DO0lBQ0QsT0FBTyxJQUFJLENBQUMsS0FBSyxDQUFDLE1BQU0sQ0FBQyxDQUFDO0FBQzlCLENBQUM7QUFDRCxDQUFDO0lBQUEsaUJBSUE7SUFIRyxpQkFBaUIsQ0FBQyxnQ0FBZ0MsRUFBRSxVQUFPLE9BQU87Ozt3QkFDOUQsV0FBTSxpQ0FBaUMsQ0FBQyxPQUFPLENBQUMsRUFBQTs7b0JBQWhELFNBQWdELENBQUM7Ozs7U0FDcEQsQ0FBQyxDQUFDO0FBQ1AsQ0FBQyxDQUFDLEVBQUUsQ0FBQyIsInNvdXJjZXNDb250ZW50IjpbIi8vIGRlbm8tZm10LWlnbm9yZS1maWxlXG4vLyBkZW5vLWxpbnQtaWdub3JlLWZpbGVcbi8vIFRoaXMgY29kZSB3YXMgYnVuZGxlZCB1c2luZyBgZGVubyBidW5kbGVgIGFuZCBpdCdzIG5vdCByZWNvbW1lbmRlZCB0byBlZGl0IGl0IG1hbnVhbGx5XG5cbmZ1bmN0aW9uIHBwT25XaW5kb3dNZXNzYWdlKGV2ZW50TmFtZSwgY2IpIHtcbiAgICBzZWxmLmFkZEV2ZW50TGlzdGVuZXIoJ21lc3NhZ2UnLCBhc3luYyAoZXZlbnQpPT57XG4gICAgICAgIGlmIChldmVudC5kYXRhLmV2ZW50ID09PSBldmVudE5hbWUpIHtcbiAgICAgICAgICAgIGF3YWl0IGNiKGV2ZW50LmRhdGEpO1xuICAgICAgICB9XG4gICAgfSwgZmFsc2UpO1xufVxuZnVuY3Rpb24gcHBJZnJhbWVXaW5kb3coKSB7XG4gICAgcmV0dXJuIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNwZWFjaHBheS1pZnJhbWUnKT8uY29udGVudFdpbmRvdyA/PyBudWxsO1xufVxuZnVuY3Rpb24gcHBGZXRjaElmcmFtZURhdGEoZW5kcG9pbnQsIHJlcXVlc3QpIHtcbiAgICByZXR1cm4gcHBGZXRjaFdpbmRvd0RhdGEocHBJZnJhbWVXaW5kb3coKSwgZW5kcG9pbnQsIHJlcXVlc3QpO1xufVxuZnVuY3Rpb24gcHBGZXRjaFdpbmRvd0RhdGEodGFyZ2V0V2luZG93LCBlbmRwb2ludCwgcmVxdWVzdCkge1xuICAgIHJldHVybiBuZXcgUHJvbWlzZSgocmVzb2x2ZSwgcmVqZWN0KT0+e1xuICAgICAgICBjb25zdCB0aW1lb3V0SWQgPSBzZXRUaW1lb3V0KCgpPT57XG4gICAgICAgICAgICByZWplY3QobmV3IEVycm9yKGBXaW5kb3cgRmV0Y2ggdGltZWQgb3V0IGZvciAke2VuZHBvaW50fS5gKSk7XG4gICAgICAgIH0sIDE1MDAwKTtcbiAgICAgICAgY29uc3QgY2hhbm5lbCA9IG5ldyBNZXNzYWdlQ2hhbm5lbCgpO1xuICAgICAgICBjaGFubmVsLnBvcnQxLm9ubWVzc2FnZSA9ICh7IGRhdGEgIH0pPT57XG4gICAgICAgICAgICBjaGFubmVsLnBvcnQxLmNsb3NlKCk7XG4gICAgICAgICAgICBjbGVhclRpbWVvdXQodGltZW91dElkKTtcbiAgICAgICAgICAgIGlmIChkYXRhLmVycm9yKSB7XG4gICAgICAgICAgICAgICAgcmVqZWN0KGRhdGEuZXJyb3IpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICByZXNvbHZlKGRhdGEucmVzdWx0KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcbiAgICAgICAgaWYgKCF0YXJnZXRXaW5kb3cpIHtcbiAgICAgICAgICAgIGNsZWFyVGltZW91dCh0aW1lb3V0SWQpO1xuICAgICAgICAgICAgcmVqZWN0KCdUYXJnZXQgd2luZG93IGlzIG5vdCB2YWxpZC4nKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRhcmdldFdpbmRvdy5wb3N0TWVzc2FnZSh7XG4gICAgICAgICAgICAgICAgZXZlbnQ6IGVuZHBvaW50LFxuICAgICAgICAgICAgICAgIHJlcXVlc3Q6IHJlcXVlc3RcbiAgICAgICAgICAgIH0sICcqJywgW1xuICAgICAgICAgICAgICAgIGNoYW5uZWwucG9ydDJcbiAgICAgICAgICAgIF0pO1xuICAgICAgICB9XG4gICAgfSk7XG59XG5hc3luYyBmdW5jdGlvbiBwcEluaXRTdHJpcGVQYXltZW50UmVxdWVzdFN1cHBvcnQobWVzc2FnZSkge1xuICAgIHRyeSB7XG4gICAgICAgIGNvbnN0IHB1YmxpY0tleSA9IG1lc3NhZ2Uuc3RyaXBlLmxpdmUgPyAncGtfbGl2ZV9vUk9uSVFEdWV4SFpwbkVPY1VmZjNDUnowMGFzYU9PQ0FMJyA6ICdwa190ZXN0X0NuTDJrQTUyVjVkUnFaYmpsSjBzWjJncjAwdUJyT0VtUVEnO1xuICAgICAgICBjb25zdCBzdHJpcGUgPSBTdHJpcGUocHVibGljS2V5LCB7XG4gICAgICAgICAgICBsb2NhbGU6IG1lc3NhZ2Uuc3RyaXBlLmxvY2FsZVxuICAgICAgICB9KTtcbiAgICAgICAgY29uc3QgZWxlbWVudHMgPSBzdHJpcGUuZWxlbWVudHMoKTtcbiAgICAgICAgY29uc3QgcGF5bWVudFJlcXVlc3QgPSBzdHJpcGUucGF5bWVudFJlcXVlc3Qoe1xuICAgICAgICAgICAgY291bnRyeTogJ1VTJyxcbiAgICAgICAgICAgIGN1cnJlbmN5OiBtZXNzYWdlLmN1cnJlbmN5Q29kZS50b0xvd2VyQ2FzZSgpLFxuICAgICAgICAgICAgdG90YWw6IHtcbiAgICAgICAgICAgICAgICBsYWJlbDogJ1RvdGFsJyxcbiAgICAgICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChtZXNzYWdlLmNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LnRvdGFsLCBtZXNzYWdlLmN1cnJlbmN5Q29kZSlcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBkaXNwbGF5SXRlbXM6IHBwR2V0UGF5bWVudFJlcXVlc3REaXNwbGF5SXRlbXMobWVzc2FnZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIG1lc3NhZ2UuY3VycmVuY3lDb2RlKSxcbiAgICAgICAgICAgIHNoaXBwaW5nT3B0aW9uczogcHBHZXRQYXltZW50UmVxdWVzdFNoaXBwaW5nT3B0aW9ucyhtZXNzYWdlLmNhcnRDYWxjdWxhdGlvblJlY29yZCwgbWVzc2FnZS5jdXJyZW5jeUNvZGUpLFxuICAgICAgICAgICAgcmVxdWVzdFBheWVyTmFtZTogdHJ1ZSxcbiAgICAgICAgICAgIHJlcXVlc3RQYXllckVtYWlsOiB0cnVlLFxuICAgICAgICAgICAgcmVxdWVzdFBheWVyUGhvbmU6IHRydWUsXG4gICAgICAgICAgICByZXF1ZXN0U2hpcHBpbmc6IHRydWVcbiAgICAgICAgfSk7XG4gICAgICAgIGNvbnN0IGF2YWlsYWJsZU1ldGhvZHMgPSBhd2FpdCBwYXltZW50UmVxdWVzdC5jYW5NYWtlUGF5bWVudCgpO1xuICAgICAgICBpZiAoIWF2YWlsYWJsZU1ldGhvZHMgfHwgIWF2YWlsYWJsZU1ldGhvZHMuYXBwbGVQYXkgJiYgIWF2YWlsYWJsZU1ldGhvZHMuZ29vZ2xlUGF5KSB7XG4gICAgICAgICAgICBwcElmcmFtZVdpbmRvdygpPy5wb3N0TWVzc2FnZSgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1zdG9wJywgJyonKTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBzdHJpcGVTZXJ2aWNlMSA9IHtcbiAgICAgICAgICAgIHN0cmlwZSxcbiAgICAgICAgICAgIGVsZW1lbnRzLFxuICAgICAgICAgICAgcGF5bWVudFJlcXVlc3RcbiAgICAgICAgfTtcbiAgICAgICAgY29uc3QgaW5qZWN0U3RyaXBlU2VydmljZSA9IChzdHJpcGVTZXJ2aWNlLCBoYW5kbGVyKT0+KHN0cmlwZUV2ZW50KT0+e1xuICAgICAgICAgICAgICAgIGhhbmRsZXIoc3RyaXBlU2VydmljZSwgc3RyaXBlRXZlbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICA7XG4gICAgICAgIHBheW1lbnRSZXF1ZXN0Lm9uKCdzaGlwcGluZ2FkZHJlc3NjaGFuZ2UnLCBwcEhhbmRsZVNoaXBwaW5nQWRkcmVzc0NoYW5nZUV2ZW50KTtcbiAgICAgICAgcGF5bWVudFJlcXVlc3Qub24oJ3NoaXBwaW5nb3B0aW9uY2hhbmdlJywgcHBIYW5kbGVTaGlwcGluZ09wdGlvbkNoYW5nZUV2ZW50KTtcbiAgICAgICAgcGF5bWVudFJlcXVlc3Qub24oJ3Rva2VuJywgaW5qZWN0U3RyaXBlU2VydmljZShzdHJpcGVTZXJ2aWNlMSwgcHBIYW5kbGVUb2tlbkV2ZW50KSk7XG4gICAgICAgIHBheW1lbnRSZXF1ZXN0Lm9uKCdjYW5jZWwnLCBpbmplY3RTdHJpcGVTZXJ2aWNlKHN0cmlwZVNlcnZpY2UxLCBwcEhhbmRsZUNhbmNlbEV2ZW50KSk7XG4gICAgICAgIHBwT25XaW5kb3dNZXNzYWdlKCdwcC11cGRhdGUtc3RyaXBlLXBheW1lbnQtcmVxdWVzdCcsIGluamVjdFN0cmlwZVNlcnZpY2Uoc3RyaXBlU2VydmljZTEsIHBwSGFuZGxlRXh0ZXJuYWxTdHJpcGVCdXR0b25EYXRhRXZlbnQpKTtcbiAgICAgICAgcHBJbnNlcnRBdmFpbGFibGVQYXltZW50UmVxdWVzdEJ1dHRvbihzdHJpcGVTZXJ2aWNlMSk7XG4gICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgaWYgKGVycm9yIGluc3RhbmNlb2YgRXJyb3IpIHtcbiAgICAgICAgICAgIGNhcHR1cmVTZW50cnlFeGNlcHRpb24obmV3IEVycm9yKGBHb29nbGUgUGF5L0FwcGxlIFBheSBmYWlsZWQgdG8gaW5pdGlsaXplIG9uICR7bG9jYXRpb24uaG9zdG5hbWV9LiBFcnJvcjogJHtlcnJvci5tZXNzYWdlfWApKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmFzeW5jIGZ1bmN0aW9uIHBwSGFuZGxlVG9rZW5FdmVudChfc3RyaXBlU2VydmljZSwgZXZlbnQpIHtcbiAgICB0cnkge1xuICAgICAgICBjb25zdCByZXF1ZXN0ID0ge1xuICAgICAgICAgICAgdG9rZW46IGV2ZW50LnRva2VuLFxuICAgICAgICAgICAgcGF5ZXJOYW1lOiBldmVudC5wYXllck5hbWUsXG4gICAgICAgICAgICBwYXllckVtYWlsOiBldmVudC5wYXllckVtYWlsLFxuICAgICAgICAgICAgcGF5ZXJQaG9uZTogZXZlbnQucGF5ZXJQaG9uZSxcbiAgICAgICAgICAgIHNoaXBwaW5nQWRkcmVzczogZXZlbnQuc2hpcHBpbmdBZGRyZXNzLFxuICAgICAgICAgICAgc2hpcHBpbmdPcHRpb246IGV2ZW50LnNoaXBwaW5nT3B0aW9uLFxuICAgICAgICAgICAgd2FsbGV0TmFtZTogZXZlbnQud2FsbGV0TmFtZVxuICAgICAgICB9O1xuICAgICAgICBjb25zdCByZXNwb25zZSA9IGF3YWl0IHBwRmV0Y2hJZnJhbWVEYXRhKCdwcC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0LXByb2Nlc3MtcGF5bWVudCcsIHJlcXVlc3QpO1xuICAgICAgICBldmVudC5jb21wbGV0ZShyZXNwb25zZS5zdGF0dXMpO1xuICAgICAgICBzZWxmLnBvc3RNZXNzYWdlKHtcbiAgICAgICAgICAgIGV2ZW50OiAncHAtY29tcGxldGUtdHJhbnNhY3Rpb24nLFxuICAgICAgICAgICAgcmVkaXJlY3RVUkw6IHJlc3BvbnNlLnJlZGlyZWN0VVJMXG4gICAgICAgIH0sICcqJyk7XG4gICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgaWYgKGVycm9yIGluc3RhbmNlb2YgRXJyb3IpIHtcbiAgICAgICAgICAgIGlmIChlcnJvci5tZXNzYWdlID09PSAnaW52YWxpZF9zaGlwcGluZ19hZGRyZXNzJykge1xuICAgICAgICAgICAgICAgIGV2ZW50LmNvbXBsZXRlKCdpbnZhbGlkX3NoaXBwaW5nX2FkZHJlc3MnKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgR29vZ2xlIFBheS9BcHBsZSBQYXkgZmFpbGVkIHRvIGhhbmRsZSB0b2tlbiBvbiAke2xvY2F0aW9uLmhvc3RuYW1lfS4gRXJyb3I6ICR7ZXJyb3IubWVzc2FnZX1gKSk7XG4gICAgICAgICAgICBldmVudC5jb21wbGV0ZSgnZmFpbCcpO1xuICAgICAgICB9XG4gICAgfVxufVxuYXN5bmMgZnVuY3Rpb24gcHBIYW5kbGVTaGlwcGluZ0FkZHJlc3NDaGFuZ2VFdmVudChldmVudCkge1xuICAgIGxldCByZXNwb25zZSA9IG51bGw7XG4gICAgdHJ5IHtcbiAgICAgICAgcmVzcG9uc2UgPSBhd2FpdCBwcEZldGNoSWZyYW1lRGF0YSgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1hZGRyZXNzLWNoYW5nZScsIGV2ZW50LnNoaXBwaW5nQWRkcmVzcyk7XG4gICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgaWYgKGVycm9yIGluc3RhbmNlb2YgRXJyb3IpIHtcbiAgICAgICAgICAgIGV2ZW50LnVwZGF0ZVdpdGgoe1xuICAgICAgICAgICAgICAgIHN0YXR1czogJ2ZhaWwnXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIGNhcHR1cmVTZW50cnlFeGNlcHRpb24obmV3IEVycm9yKGBHb29nbGUgUGF5L0FwcGxlIFBheSBmYWlsZWQgcmVxdWVzdGluZyBzaGlwcGluZyBhZGRyZXNzIGNoYW5nZSBvbiAke2xvY2F0aW9uLmhvc3RuYW1lfS4gRXJyb3I6ICR7ZXJyb3IubWVzc2FnZX1gKSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBjb25zdCBpc1ZpcnR1YWwgPSByZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uY2FydF9tZXRhLmlzX3ZpcnR1YWw7XG4gICAgY29uc3QgbWV0aG9kcyA9IE9iamVjdC5lbnRyaWVzKHJlc3BvbnNlLmNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5wYWNrYWdlX3JlY29yZFswXT8ubWV0aG9kcyA/PyB7fSk7XG4gICAgaWYgKCFpc1ZpcnR1YWwgJiYgbWV0aG9kcy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgZXZlbnQudXBkYXRlV2l0aCh7XG4gICAgICAgICAgICBzdGF0dXM6ICdpbnZhbGlkX3NoaXBwaW5nX2FkZHJlc3MnLFxuICAgICAgICAgICAgdG90YWw6IHtcbiAgICAgICAgICAgICAgICBsYWJlbDogJ1RvdGFsJyxcbiAgICAgICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS50b3RhbCwgcmVzcG9uc2UuY3VycmVuY3lDb2RlKVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGRpc3BsYXlJdGVtczogcHBHZXRQYXltZW50UmVxdWVzdERpc3BsYXlJdGVtcyhyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIHJlc3BvbnNlLmN1cnJlbmN5Q29kZSksXG4gICAgICAgICAgICBzaGlwcGluZ09wdGlvbnM6IHBwR2V0UGF5bWVudFJlcXVlc3RTaGlwcGluZ09wdGlvbnMocmVzcG9uc2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCByZXNwb25zZS5jdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIGV2ZW50LnVwZGF0ZVdpdGgoe1xuICAgICAgICAgICAgc3RhdHVzOiAnc3VjY2VzcycsXG4gICAgICAgICAgICB0b3RhbDoge1xuICAgICAgICAgICAgICAgIGxhYmVsOiAnVG90YWwnLFxuICAgICAgICAgICAgICAgIGFtb3VudDogcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KHJlc3BvbnNlLmNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LnRvdGFsLCByZXNwb25zZS5jdXJyZW5jeUNvZGUpXG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgZGlzcGxheUl0ZW1zOiBwcEdldFBheW1lbnRSZXF1ZXN0RGlzcGxheUl0ZW1zKHJlc3BvbnNlLmNhcnRDYWxjdWxhdGlvblJlY29yZCwgcmVzcG9uc2UuY3VycmVuY3lDb2RlKSxcbiAgICAgICAgICAgIHNoaXBwaW5nT3B0aW9uczogcHBHZXRQYXltZW50UmVxdWVzdFNoaXBwaW5nT3B0aW9ucyhyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIHJlc3BvbnNlLmN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSk7XG4gICAgfVxufVxuYXN5bmMgZnVuY3Rpb24gcHBIYW5kbGVTaGlwcGluZ09wdGlvbkNoYW5nZUV2ZW50KGV2ZW50KSB7XG4gICAgdHJ5IHtcbiAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBwcEZldGNoSWZyYW1lRGF0YSgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1zaGlwcGluZy1jaGFuZ2UnLCBldmVudC5zaGlwcGluZ09wdGlvbik7XG4gICAgICAgIGV2ZW50LnVwZGF0ZVdpdGgoe1xuICAgICAgICAgICAgc3RhdHVzOiAnc3VjY2VzcycsXG4gICAgICAgICAgICB0b3RhbDoge1xuICAgICAgICAgICAgICAgIGxhYmVsOiAnVG90YWwnLFxuICAgICAgICAgICAgICAgIGFtb3VudDogcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KHJlc3BvbnNlLmNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LnRvdGFsLCByZXNwb25zZS5jdXJyZW5jeUNvZGUpXG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgZGlzcGxheUl0ZW1zOiBwcEdldFBheW1lbnRSZXF1ZXN0RGlzcGxheUl0ZW1zKHJlc3BvbnNlLmNhcnRDYWxjdWxhdGlvblJlY29yZCwgcmVzcG9uc2UuY3VycmVuY3lDb2RlKSxcbiAgICAgICAgICAgIHNoaXBwaW5nT3B0aW9uczogcHBHZXRQYXltZW50UmVxdWVzdFNoaXBwaW5nT3B0aW9ucyhyZXNwb25zZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIHJlc3BvbnNlLmN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSk7XG4gICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgaWYgKGVycm9yIGluc3RhbmNlb2YgRXJyb3IpIHtcbiAgICAgICAgICAgIGNhcHR1cmVTZW50cnlFeGNlcHRpb24obmV3IEVycm9yKGBHb29nbGUgUGF5L0FwcGxlIFBheSBmYWlsZWQgY2hhbmdpbmcgc2hpcHBpbmcgb3B0aW9uIG9uICR7bG9jYXRpb24uaG9zdG5hbWV9LiBFcnJvcjogJHtlcnJvci5tZXNzYWdlfWApKTtcbiAgICAgICAgfVxuICAgICAgICBldmVudC51cGRhdGVXaXRoKHtcbiAgICAgICAgICAgIHN0YXR1czogJ2ZhaWwnXG4gICAgICAgIH0pO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHBwSGFuZGxlQ2FuY2VsRXZlbnQoX3N0cmlwZVNlcnZpY2UpIHt9XG5mdW5jdGlvbiBwcEhhbmRsZUV4dGVybmFsU3RyaXBlQnV0dG9uRGF0YUV2ZW50KHN0cmlwZVNlcnZpY2UsIG1lc3NhZ2UpIHtcbiAgICBpZiAoc3RyaXBlU2VydmljZS5wYXltZW50UmVxdWVzdC5pc1Nob3dpbmcoKSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIHN0cmlwZVNlcnZpY2UucGF5bWVudFJlcXVlc3QudXBkYXRlKHtcbiAgICAgICAgY3VycmVuY3k6IG1lc3NhZ2UuY3VycmVuY3lDb2RlLnRvTG93ZXJDYXNlKCksXG4gICAgICAgIHRvdGFsOiB7XG4gICAgICAgICAgICBsYWJlbDogJ1RvdGFsJyxcbiAgICAgICAgICAgIGFtb3VudDogcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KG1lc3NhZ2UuY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkudG90YWwsIG1lc3NhZ2UuY3VycmVuY3lDb2RlKVxuICAgICAgICB9LFxuICAgICAgICBkaXNwbGF5SXRlbXM6IHBwR2V0UGF5bWVudFJlcXVlc3REaXNwbGF5SXRlbXMobWVzc2FnZS5jYXJ0Q2FsY3VsYXRpb25SZWNvcmQsIG1lc3NhZ2UuY3VycmVuY3lDb2RlKSxcbiAgICAgICAgc2hpcHBpbmdPcHRpb25zOiBwcEdldFBheW1lbnRSZXF1ZXN0U2hpcHBpbmdPcHRpb25zKG1lc3NhZ2UuY2FydENhbGN1bGF0aW9uUmVjb3JkLCBtZXNzYWdlLmN1cnJlbmN5Q29kZSlcbiAgICB9KTtcbn1cbmZ1bmN0aW9uIHBwSW5zZXJ0QXZhaWxhYmxlUGF5bWVudFJlcXVlc3RCdXR0b24oeyBlbGVtZW50cyAsIHBheW1lbnRSZXF1ZXN0ICB9KSB7XG4gICAgY29uc3QgJGJ1dHRvbiA9IGVsZW1lbnRzLmNyZWF0ZSgncGF5bWVudFJlcXVlc3RCdXR0b24nLCB7XG4gICAgICAgIHBheW1lbnRSZXF1ZXN0OiBwYXltZW50UmVxdWVzdFxuICAgIH0pO1xuICAgICRidXR0b24ubW91bnQoJyNwcC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0LWJ0bicpO1xuICAgIGNvbnN0ICRidXR0b25Db250YWluZXIxID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3BwLXN0cmlwZS1wYXltZW50LXJlcXVlc3QtYnRuJyk7XG4gICAgaWYgKCRidXR0b25Db250YWluZXIxKSB7XG4gICAgICAgICRidXR0b25Db250YWluZXIxLnN0eWxlLmRpc3BsYXkgPSAnYmxvY2snO1xuICAgIH1cbiAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdwcC1pbnNlcnQtYnV0dG9uJywgKCk9PntcbiAgICAgICAgJGJ1dHRvbi5tb3VudCgnI3BwLXN0cmlwZS1wYXltZW50LXJlcXVlc3QtYnRuJyk7XG4gICAgICAgIGNvbnN0ICRidXR0b25Db250YWluZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjcHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1idG4nKTtcbiAgICAgICAgaWYgKCRidXR0b25Db250YWluZXIpIHtcbiAgICAgICAgICAgICRidXR0b25Db250YWluZXIuc3R5bGUuZGlzcGxheSA9ICdibG9jayc7XG4gICAgICAgIH1cbiAgICB9KTtcbn1cbmZ1bmN0aW9uIHBwR2V0UGF5bWVudFJlcXVlc3RTaGlwcGluZ09wdGlvbnMoY2FydENhbGN1bGF0aW9uUmVjb3JkLCBjdXJyZW5jeUNvZGUpIHtcbiAgICBjb25zdCBvcHRpb25zID0gW107XG4gICAgaWYgKGNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5jYXJ0X21ldGEuaXNfdmlydHVhbCkge1xuICAgICAgICByZXR1cm4gW1xuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGlkOiAnbm8tc2hpcHBpbmctYXZhaWxhYmxlJyxcbiAgICAgICAgICAgICAgICBsYWJlbDogJ05vIFNoaXBwaW5nJyxcbiAgICAgICAgICAgICAgICBhbW91bnQ6IDBcbiAgICAgICAgICAgIH1cbiAgICAgICAgXTtcbiAgICB9XG4gICAgY29uc3Qgc2hpcGluZ01ldGhvZHMgPSBjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0ucGFja2FnZV9yZWNvcmRbMF0/Lm1ldGhvZHMgPz8ge307XG4gICAgZm9yIChjb25zdCBtZXRob2RLZXkgb2YgT2JqZWN0LmtleXMoc2hpcGluZ01ldGhvZHMpKXtcbiAgICAgICAgb3B0aW9ucy5wdXNoKHtcbiAgICAgICAgICAgIGlkOiBtZXRob2RLZXksXG4gICAgICAgICAgICBsYWJlbDogc2hpcGluZ01ldGhvZHNbbWV0aG9kS2V5XT8udGl0bGUgPz8gJycsXG4gICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChzaGlwaW5nTWV0aG9kc1ttZXRob2RLZXldPy50b3RhbCA/PyAwLCBjdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICByZXR1cm4gb3B0aW9ucztcbn1cbmZ1bmN0aW9uIHBwR2V0UGF5bWVudFJlcXVlc3REaXNwbGF5SXRlbXMoY2FydENhbGN1bGF0aW9uUmVjb3JkLCBjdXJyZW5jeUNvZGUpIHtcbiAgICBjb25zdCBpdGVtcyA9IFtdO1xuICAgIGl0ZW1zLnB1c2goe1xuICAgICAgICBsYWJlbDogJ1N1YnRvdGFsJyxcbiAgICAgICAgYW1vdW50OiBwcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQoY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkuc3VidG90YWwsIGN1cnJlbmN5Q29kZSlcbiAgICB9KTtcbiAgICBmb3IgKGNvbnN0IFtjb3Vwb24sIGFtb3VudF0gb2YgT2JqZWN0LmVudHJpZXMoY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLnN1bW1hcnkuY291cG9uc19yZWNvcmQpKXtcbiAgICAgICAgaWYgKCFhbW91bnQpIHtcbiAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICB9XG4gICAgICAgIGl0ZW1zLnB1c2goe1xuICAgICAgICAgICAgbGFiZWw6IGBDb3Vwb24gLSAoJHtjb3Vwb259KWAsXG4gICAgICAgICAgICBhbW91bnQ6IC1wcEN1cnJlbmN5QW1vdW50VG9TdHJpcGVGb3JtYXQoYW1vdW50LCBjdXJyZW5jeUNvZGUpXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0IFtmZWUsIGFtb3VudDFdIG9mIE9iamVjdC5lbnRyaWVzKGNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LmZlZXNfcmVjb3JkKSl7XG4gICAgICAgIGlmICghYW1vdW50MSkge1xuICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgIH1cbiAgICAgICAgaXRlbXMucHVzaCh7XG4gICAgICAgICAgICBsYWJlbDogYEZlZSAtICgke2ZlZX0pYCxcbiAgICAgICAgICAgIGFtb3VudDogcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KGFtb3VudDEsIGN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGlmICghY2FydENhbGN1bGF0aW9uUmVjb3JkWzBdLmNhcnRfbWV0YS5pc192aXJ0dWFsKSB7XG4gICAgICAgIGl0ZW1zLnB1c2goe1xuICAgICAgICAgICAgbGFiZWw6ICdTaGlwcGluZycsXG4gICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS50b3RhbF9zaGlwcGluZywgY3VycmVuY3lDb2RlKVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgaWYgKGNhcnRDYWxjdWxhdGlvblJlY29yZFswXS5zdW1tYXJ5LnRvdGFsX3RheCA+IDApIHtcbiAgICAgICAgaXRlbXMucHVzaCh7XG4gICAgICAgICAgICBsYWJlbDogJ1RheCcsXG4gICAgICAgICAgICBhbW91bnQ6IHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS50b3RhbF90YXgsIGN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGZvciAoY29uc3QgW2dpZnRDYXJkLCBhbW91bnQyXSBvZiBPYmplY3QuZW50cmllcyhjYXJ0Q2FsY3VsYXRpb25SZWNvcmRbMF0uc3VtbWFyeS5naWZ0X2NhcmRfcmVjb3JkKSl7XG4gICAgICAgIGlmICghYW1vdW50Mikge1xuICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgIH1cbiAgICAgICAgaXRlbXMucHVzaCh7XG4gICAgICAgICAgICBsYWJlbDogYEdpZnQgY2FyZCAtICgke2dpZnRDYXJkfSlgLFxuICAgICAgICAgICAgYW1vdW50OiAtcHBDdXJyZW5jeUFtb3VudFRvU3RyaXBlRm9ybWF0KGFtb3VudDIsIGN1cnJlbmN5Q29kZSlcbiAgICAgICAgfSk7XG4gICAgfVxuICAgIHJldHVybiBpdGVtcztcbn1cbmZ1bmN0aW9uIHBwQ3VycmVuY3lBbW91bnRUb1N0cmlwZUZvcm1hdChhbW91bnQsIGN1cnJlbmN5Q29kZSkge1xuICAgIGNvbnN0IHplcm9EZWNpbWFsQ3VycmVuY2llcyA9IG5ldyBTZXQoW1xuICAgICAgICAnQklGJyxcbiAgICAgICAgJ0NMUCcsXG4gICAgICAgICdESkYnLFxuICAgICAgICAnR05GJyxcbiAgICAgICAgJ0pQWScsXG4gICAgICAgICdLTUYnLFxuICAgICAgICAnS1JXJyxcbiAgICAgICAgJ01HQScsXG4gICAgICAgICdQWUcnLFxuICAgICAgICAnUldGJyxcbiAgICAgICAgJ1VHWCcsXG4gICAgICAgICdWTkQnLFxuICAgICAgICAnVlVWJyxcbiAgICAgICAgJ1hBRicsXG4gICAgICAgICdYT0YnLFxuICAgICAgICAnWFBGJywgXG4gICAgXSk7XG4gICAgaWYgKCF6ZXJvRGVjaW1hbEN1cnJlbmNpZXMuaGFzKGN1cnJlbmN5Q29kZSkpIHtcbiAgICAgICAgcmV0dXJuIE1hdGgucm91bmQoYW1vdW50ICogMTAwKTtcbiAgICB9XG4gICAgcmV0dXJuIE1hdGgucm91bmQoYW1vdW50KTtcbn1cbihmdW5jdGlvbigpIHtcbiAgICBwcE9uV2luZG93TWVzc2FnZSgncHAtaW5pdC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0JywgYXN5bmMgKG1lc3NhZ2UpPT57XG4gICAgICAgIGF3YWl0IHBwSW5pdFN0cmlwZVBheW1lbnRSZXF1ZXN0U3VwcG9ydChtZXNzYWdlKTtcbiAgICB9KTtcbn0pKCk7XG4iXX0=