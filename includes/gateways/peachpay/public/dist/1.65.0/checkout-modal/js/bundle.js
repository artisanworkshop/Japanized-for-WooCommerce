"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
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
function $qs(selector, cb) {
    if (cb === void 0) { cb = null; }
    var $element = document.querySelector(selector);
    if ($element && cb !== null) {
        cb($element);
    }
    return $element;
}
function $qsAll(selector, callback) {
    var result = Array.from(document.querySelectorAll(selector));
    if (callback) {
        for (var _i = 0, result_1 = result; _i < result_1.length; _i++) {
            var $element = result_1[_i];
            callback($element);
        }
    }
    return result;
}
function onWindowMessage(eventName, cb) {
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
function onWindowDataFetch(endpoint, requestCallback) {
    var _this = this;
    self.addEventListener('message', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var response, error_1;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!(message.data.event === endpoint)) return [3, 4];
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    return [4, requestCallback(message.data.request)];
                case 2:
                    response = _a.sent();
                    message.ports[0].postMessage({
                        result: response
                    });
                    return [3, 4];
                case 3:
                    error_1 = _a.sent();
                    message.ports[0].postMessage({
                        error: error_1
                    });
                    return [3, 4];
                case 4: return [2];
            }
        });
    }); });
}
function fetchWindowData(targetWindow, endpoint, request) {
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
            reject(new Error('Target window is not valid.'));
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
function fetchHostWindowData(endpoint, request) {
    return fetchWindowData(window.top, endpoint, request);
}
var DispatchActionType;
(function (DispatchActionType1) {
    DispatchActionType1["INIT"] = 'init';
    DispatchActionType1["ENVIRONMENT"] = 'environment';
    DispatchActionType1["ORDER_SESSION_ID"] = 'peachpayOrder/sessionId';
    DispatchActionType1["ORDER_ADDRESS_VALIDATED"] = 'peachpayOrder/addressValidated';
    DispatchActionType1["ORDER_SET_EXTRA_FIELDS"] = 'peachpayOrder/extraFields/set';
    DispatchActionType1["ORDER_SET_ERROR_MESSAGE"] = 'peachpayOrder/errorMessage/set';
    DispatchActionType1["PEACHPAY_CUSTOMER"] = 'peachpay/customer';
    DispatchActionType1["PEACHPAY_CUSTOMER_STRIPE_ID"] = 'peachpay/customer/stripeId';
    DispatchActionType1["MERCHANT_CUSTOMER"] = 'merchant/customer';
    DispatchActionType1["MERCHANT_CUSTOMER_EXIST"] = 'merchant/customer/exist';
    DispatchActionType1["ENVIRONMENT_LANGUAGE"] = 'modal/language';
    DispatchActionType1["MERCHANT_NAME"] = 'merchant/name';
    DispatchActionType1["MERCHANT_HOSTNAME"] = 'merchant/hostname';
    DispatchActionType1["MERCHANT_GENERAL"] = 'merchant/general';
    DispatchActionType1["MERCHANT_GENERAL_CURRENCY"] = 'merchant/general/currency';
    DispatchActionType1["MERCHANT_ACCOUNT"] = 'merchant/accounts';
    DispatchActionType1["MERCHANT_TAX"] = 'merchant/tax';
    DispatchActionType1["MERCHANT_SHIPPING"] = 'merchant/shipping';
    DispatchActionType1["DEFAULT_CART_CONTENTS"] = 'default/cart/contents';
    DispatchActionType1["DEFAULT_CART_CALCULATION"] = 'default/cart/calculation';
    DispatchActionType1["CART_CALCULATION"] = 'cart/calculation';
    DispatchActionType1["CART_SHIPPING_SELECTION"] = 'cart/shipping/selection';
    DispatchActionType1["ENVIRONMENT_SET_FEATURES"] = "ENVIRONMENT_SET_FEATURES";
    DispatchActionType1["PEACHPAY_CUSTOMER_SHIPPING"] = "PEACHPAY_CUSTOMER_SHIPPING";
    DispatchActionType1["PAYMENT_SET_METHOD"] = "PAYMENT_SET_METHOD";
    DispatchActionType1["PAYMENT_REGISTER_PROVIDER"] = "PAYMENT_REGISTER_PROVIDER";
    DispatchActionType1["PEACHPAY_CUSTOMER_ADD_PAYMENT_METHOD"] = 'PEACHPAY_CUSTOMER_PAYMENT_METHOD';
    DispatchActionType1["PEACHPAY_CUSTOMER_SET_PREFERRED_PAYMENT_METHOD"] = "PEACHPAY_CUSTOMER_SET_PREFERRED_PAYMENT_METHOD";
    DispatchActionType1["PEACHPAY_CUSTOMER_REMOVE_PAYMENT_METHOD"] = "PEACHPAY_CUSTOMER_REMOVE_PAYMENT_METHOD";
})(DispatchActionType || (DispatchActionType = {}));
var initialState = {
    environment: {
        language: 'en-US',
        plugin: {
            version: '',
            mode: 'live',
            pageType: 'cart',
            buttonColor: '#FF876C',
            featureSupport: {},
            supportMessage: ''
        },
        customer: {
            existing: false,
            mobile: false
        },
        modalUI: {
            open: false,
            page: 'info',
            loadingMode: 'finished'
        }
    },
    peachPayOrder: {
        sessionId: '',
        customerAddressValidated: false,
        additionalFields: {},
        errorMessage: ''
    },
    peachPayCustomer: {
        email: '',
        name_first: '',
        name_last: '',
        address1: '',
        address2: '',
        city: '',
        state: '',
        country: '',
        postal: '',
        phone: ''
    },
    merchantCustomer: {
        username: '',
        loggedIn: false,
        usernameIsRegistered: false
    },
    merchantConfiguration: {
        name: '',
        hostName: '',
        general: {
            currency: {
                code: 'USD',
                symbol: '$',
                position: 'left',
                thousands_separator: ',',
                decimal_separator: '.',
                rounding: 'disabled',
                number_of_decimals: 2
            }
        },
        shipping: {
            shippingZones: 0
        },
        tax: {
            displayPricesInCartAndCheckout: 'excludeTax'
        },
        accountsAndPrivacy: {
            allowGuestCheckout: true,
            allowAccountCreationOrLoginDuringCheckout: true,
            autoGenerateUsername: false,
            autoGeneratePassword: false
        }
    },
    calculatedCarts: {
        0: {
            package_record: {},
            cart: [],
            summary: {
                fees_record: {},
                coupons_record: {},
                gift_card_record: {},
                subtotal: 0,
                total_shipping: 0,
                total_tax: 0,
                total: 0
            },
            cart_meta: {
                is_virtual: false
            }
        }
    },
    paymentConfiguration: {
        selectedPaymentMethod: '',
        providers: {}
    }
};
function createDispatchUpdate(type) {
    return function (payload) { return ({
        type: type,
        payload: payload
    }); };
}
function createCustomDispatchUpdate(type, transformer) {
    return function (input) {
        var payload = transformer(input);
        return {
            type: type,
            payload: payload
        };
    };
}
function isDevEnvironment(baseUrl) {
    return baseUrl === 'https://dev.peachpay.app/' || baseUrl === 'https://dev.peachpay.local/' || baseUrl === 'https://prod.peachpay.local/';
}
function getBaseURL(merchantHostname, isTestMode) {
    if (isTestMode) {
        switch (merchantHostname) {
            case 'store.local':
            case 'woo.store.local':
                return 'https://dev.peachpay.local/';
            default:
                return 'https://dev.peachpay.app/';
        }
    }
    switch (merchantHostname) {
        case 'localhost':
        case '127.0.0.1':
        case 'woo.peachpay.app':
        case 'theme1.peachpay.app':
        case 'theme2.peachpay.app':
        case 'theme3.peachpay.app':
        case 'theme4.peachpay.app':
        case 'theme5.peachpay.app':
        case 'qa.peachpay.app':
        case 'demo.peachpay.app':
            return 'https://dev.peachpay.app/';
        case 'store.local':
        case 'woo.store.local':
            return 'https://prod.peachpay.local/';
        default:
            return 'https://prod.peachpay.app/';
    }
}
function getOneClickURL(merchantHostname, isTestMode) {
    if (isTestMode) {
        switch (merchantHostname) {
            case 'store.local':
            case 'woo.store.local':
                return 'https://dev-connect.peachpay.local/';
            default:
                return 'https://dev-connect-v2.peachpaycheckout.com/';
        }
    }
    switch (merchantHostname) {
        case 'localhost':
        case '127.0.0.1':
        case 'woo.peachpay.app':
        case 'theme1.peachpay.app':
        case 'theme2.peachpay.app':
        case 'theme3.peachpay.app':
        case 'theme4.peachpay.app':
        case 'theme5.peachpay.app':
        case 'qa.peachpay.app':
        case 'demo.peachpay.app':
            return 'https://dev-connect-v2.peachpaycheckout.com/';
        case 'store.local':
        case 'woo.store.local':
            return 'https://connect.peachpay.local/';
        default:
            return 'https://connect-v2.peachpaycheckout.com/';
    }
}
function determinePageType(isCartPage, isCheckoutPage, isShopPage) {
    if (isCartPage) {
        return 'cart';
    }
    if (isCheckoutPage) {
        return 'checkout';
    }
    if (isShopPage) {
        return 'shop';
    }
    return 'product';
}
function merchantConfigurationReducer(state, action) {
    switch (action.type) {
        case DispatchActionType.MERCHANT_GENERAL_CURRENCY:
            return __assign(__assign({}, state), { general: __assign(__assign({}, state.general), { currency: __assign({}, action.payload) }) });
        case DispatchActionType.MERCHANT_GENERAL:
            return __assign(__assign({}, state), { general: __assign({}, action.payload) });
        case DispatchActionType.MERCHANT_ACCOUNT:
            return __assign(__assign({}, state), { accountsAndPrivacy: __assign({}, action.payload) });
        case DispatchActionType.MERCHANT_TAX:
            return __assign(__assign({}, state), { tax: __assign({}, action.payload) });
        case DispatchActionType.MERCHANT_SHIPPING:
            return __assign(__assign({}, state), { shipping: __assign({}, action.payload) });
        case DispatchActionType.MERCHANT_HOSTNAME:
            return __assign(__assign({}, state), { hostName: action.payload });
        case DispatchActionType.MERCHANT_NAME:
            return __assign(__assign({}, state), { name: action.payload });
        default:
            return Object.assign({}, state);
    }
}
function peachPayOrderReducer(state, action) {
    switch (action.type) {
        case DispatchActionType.ORDER_SESSION_ID:
            return __assign(__assign({}, state), { sessionId: action.payload });
        case DispatchActionType.ORDER_ADDRESS_VALIDATED:
            return __assign(__assign({}, state), { customerAddressValidated: action.payload });
        case DispatchActionType.ORDER_SET_EXTRA_FIELDS:
            return __assign(__assign({}, state), { additionalFields: __assign({}, action.payload) });
        case DispatchActionType.ORDER_SET_ERROR_MESSAGE:
            return __assign(__assign({}, state), { errorMessage: action.payload });
        default:
            return __assign({}, state);
    }
}
function environmentReducer(state, action) {
    switch (action.type) {
        case DispatchActionType.ENVIRONMENT:
            return __assign(__assign({}, action.payload), { customer: __assign({}, action.payload.customer), plugin: __assign({}, action.payload.plugin), modalUI: __assign({}, action.payload.modalUI) });
        case DispatchActionType.ENVIRONMENT_LANGUAGE:
            return __assign(__assign({}, state), { language: action.payload });
        case DispatchActionType.ENVIRONMENT_SET_FEATURES:
            return __assign(__assign({}, state), { plugin: __assign(__assign({}, state.plugin), { featureSupport: action.payload }) });
        default:
            return __assign(__assign({}, state), { modalUI: __assign({}, state.modalUI) });
    }
}
function merchantCustomerReducer(state, action) {
    switch (action.type) {
        case DispatchActionType.MERCHANT_CUSTOMER:
            return __assign({}, action.payload);
        case DispatchActionType.MERCHANT_CUSTOMER_EXIST:
            return __assign(__assign({}, state), { usernameIsRegistered: action.payload });
        default:
            return __assign({}, state);
    }
}
function peachPayCustomerReducer(state, action) {
    switch (action.type) {
        case DispatchActionType.PEACHPAY_CUSTOMER:
            return __assign(__assign({}, state), action.payload);
        case DispatchActionType.PEACHPAY_CUSTOMER_STRIPE_ID:
            return __assign(__assign({}, state), { stripe_customer_id: action.payload });
        case DispatchActionType.PEACHPAY_CUSTOMER_ADD_PAYMENT_METHOD:
            {
                var newState = __assign(__assign({}, state), { payment_methods: __assign({}, state.payment_methods) });
                var payload = action.payload;
                if (!newState.payment_methods) {
                    newState.payment_methods = {};
                }
                if (!newState.payment_methods[payload[0]]) {
                    newState.payment_methods[payload[0]] = [];
                }
                newState.payment_methods[payload[0]].unshift(payload[1]);
                return newState;
            }
        case DispatchActionType.PEACHPAY_CUSTOMER_SET_PREFERRED_PAYMENT_METHOD:
            {
                return __assign(__assign({}, state), { preferred_payment_method: action.payload });
            }
        case DispatchActionType.PEACHPAY_CUSTOMER_SHIPPING:
            return __assign(__assign(__assign({}, state), action.payload), { postal: action.payload.postcode });
        case DispatchActionType.PEACHPAY_CUSTOMER_REMOVE_PAYMENT_METHOD:
            {
                var newState = __assign({}, state);
                var payload = action.payload;
                if (!newState.payment_methods) {
                    return newState;
                }
                var savedMethods = newState.payment_methods[payload.id];
                if (!savedMethods) {
                    return newState;
                }
                savedMethods.splice(Number.parseInt(payload.index), 1);
                newState.payment_methods[payload.id] = savedMethods;
                return newState;
            }
        default:
            return __assign({}, state);
    }
}
function cartReducer(state, action) {
    var _a;
    switch (action.type) {
        case DispatchActionType.DEFAULT_CART_CONTENTS:
            return __assign(__assign({}, state), { 0: __assign(__assign({}, state['0']), { cart: action.payload }) });
        case DispatchActionType.DEFAULT_CART_CALCULATION:
            return __assign(__assign({}, state), { 0: __assign({}, action.payload) });
        case DispatchActionType.CART_CALCULATION:
            return __assign({}, action.payload);
        case DispatchActionType.CART_SHIPPING_SELECTION:
            {
                var payload = action.payload;
                var newState = __assign({}, state);
                if (!newState[payload.cartKey] || !((_a = newState[payload.cartKey]) === null || _a === void 0 ? void 0 : _a.package_record)) {
                    return newState;
                }
                var packageRecord = newState[payload.cartKey].package_record;
                if (!packageRecord[payload.shippingPackageKey]) {
                    return newState;
                }
                packageRecord[payload.shippingPackageKey].selected_method = payload.packageMethodId;
                return newState;
            }
        default:
            return __assign({}, state);
    }
}
function paymentConfigurationReducer(state, action) {
    switch (action.type) {
        case DispatchActionType.PAYMENT_SET_METHOD:
            return __assign(__assign({}, state), { selectedPaymentMethod: action.payload });
        case DispatchActionType.PAYMENT_REGISTER_PROVIDER:
            return __assign(__assign({}, state), { providers: __assign(__assign({}, state.providers), action.payload) });
        default:
            return __assign({}, state);
    }
}
function rootReducer(state, action) {
    if (state === void 0) { state = initialState; }
    return __assign(__assign({}, state), { peachPayOrder: peachPayOrderReducer(state.peachPayOrder, action), environment: environmentReducer(state.environment, action), merchantCustomer: merchantCustomerReducer(state.merchantCustomer, action), peachPayCustomer: peachPayCustomerReducer(state.peachPayCustomer, action), merchantConfiguration: merchantConfigurationReducer(state.merchantConfiguration, action), calculatedCarts: cartReducer(state.calculatedCarts, action), paymentConfiguration: paymentConfigurationReducer(state.paymentConfiguration, action) });
}
var store = createStore(rootReducer);
var updateMerchantCurrencyConfig = createDispatchUpdate(DispatchActionType.MERCHANT_GENERAL_CURRENCY);
var updateMerchantTaxConfig = createDispatchUpdate(DispatchActionType.MERCHANT_TAX);
var updateMerchantGeneralConfig = createDispatchUpdate(DispatchActionType.MERCHANT_GENERAL);
var updateMerchantAccountConfig = createDispatchUpdate(DispatchActionType.MERCHANT_ACCOUNT);
var updateMerchantShippingConfig = createDispatchUpdate(DispatchActionType.MERCHANT_SHIPPING);
var updateMerchantHostName = createDispatchUpdate(DispatchActionType.MERCHANT_HOSTNAME);
var updateMerchantName = createDispatchUpdate(DispatchActionType.MERCHANT_NAME);
var MerchantConfiguration = {
    name: function () { return store.getState().merchantConfiguration.name; },
    hostName: function () { return store.getState().merchantConfiguration.hostName; },
    general: {
        wcLocationInfoData: function () { return store.getState().merchantConfiguration.general.wcLocationInfoData; }
    },
    currency: {
        configuration: function () { return store.getState().merchantConfiguration.general.currency; },
        code: function () { return store.getState().merchantConfiguration.general.currency.code; },
        symbol: function () { return store.getState().merchantConfiguration.general.currency.symbol; }
    },
    tax: {
        displayMode: function () { return store.getState().merchantConfiguration.tax.displayPricesInCartAndCheckout; }
    },
    shipping: {
        shippingZones: function () { return store.getState().merchantConfiguration.shipping.shippingZones; }
    },
    accounts: {
        loginDuringCheckoutEnabled: function () { return store.getState().merchantConfiguration.accountsAndPrivacy.allowAccountCreationOrLoginDuringCheckout; },
        allowGuestCheckout: function () { return store.getState().merchantConfiguration.accountsAndPrivacy.allowGuestCheckout; },
        generatePasswordEnabled: function () { return store.getState().merchantConfiguration.accountsAndPrivacy.autoGeneratePassword; },
        generateUsernameEnabled: function () { return store.getState().merchantConfiguration.accountsAndPrivacy.autoGenerateUsername; }
    }
};
function updateEnvironment(options) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
    return {
        type: DispatchActionType.ENVIRONMENT,
        payload: {
            language: (_a = options.language) !== null && _a !== void 0 ? _a : Environment.language(),
            customer: {
                existing: (_b = options.customerExists) !== null && _b !== void 0 ? _b : Environment.customer.existing(),
                mobile: (_c = options.customerIsMobile) !== null && _c !== void 0 ? _c : Environment.customer.mobile()
            },
            plugin: {
                version: (_d = options.pluginVersion) !== null && _d !== void 0 ? _d : Environment.plugin.version(),
                mode: typeof options.pluginIsTestMode === 'boolean' ? options.pluginIsTestMode ? 'test' : 'live' : Environment.plugin.mode(),
                buttonColor: (_e = options.pluginButtonColor) !== null && _e !== void 0 ? _e : Environment.plugin.buttonColor(),
                pageType: (_f = options.pluginPageType) !== null && _f !== void 0 ? _f : Environment.plugin.pageType(),
                featureSupport: store.getState().environment.plugin.featureSupport,
                supportMessage: (_g = options.supportMessage) !== null && _g !== void 0 ? _g : store.getState().environment.plugin.supportMessage
            },
            modalUI: {
                open: (_h = options.modalIsOpen) !== null && _h !== void 0 ? _h : Environment.modalUI.open(),
                page: (_j = options.modalPageType) !== null && _j !== void 0 ? _j : Environment.modalUI.page(),
                loadingMode: (_k = options.modalLoading) !== null && _k !== void 0 ? _k : Environment.modalUI.loadingMode()
            }
        }
    };
}
function setFeatureSupport(features) {
    if (features === void 0) { features = {}; }
    return {
        type: DispatchActionType.ENVIRONMENT_SET_FEATURES,
        payload: features
    };
}
var updateLanguage = createDispatchUpdate(DispatchActionType.ENVIRONMENT_LANGUAGE);
var startModalLoading = function () { return updateEnvironment({
    modalLoading: 'loading'
}); };
var startModalProcessing = function () { return updateEnvironment({
    modalLoading: 'processing'
}); };
var stopModalLoading = function () { return updateEnvironment({
    modalLoading: 'finished'
}); };
var Environment = {
    environment: function () { return store.getState().environment; },
    language: function () { return store.getState().environment.language; },
    testMode: function () { return store.getState().environment.plugin.mode === 'test'; },
    apiURL: function () { return getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()); },
    isOurStore: function () {
        var domain = MerchantConfiguration.hostName().replace(/^https?:\/\//i, '');
        return domain === 'woo.peachpay.app' || domain === 'shop.peachpay.app' || domain === 'localhost' || domain === 'woo.store.local' || domain === 'store.local' || domain === 'demo.peachpay.app' || domain === 'oneclickcheckout.com';
    },
    isTestOrDevSite: function () { return isDevEnvironment(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode())); },
    customer: {
        existing: function () { return store.getState().environment.customer.existing; },
        mobile: function () { return store.getState().environment.customer.mobile; }
    },
    plugin: {
        version: function () { return store.getState().environment.plugin.version; },
        mode: function () { return store.getState().environment.plugin.mode; },
        buttonColor: function () { return store.getState().environment.plugin.buttonColor; },
        pageType: function () { return store.getState().environment.plugin.pageType; },
        supportMessage: function () { return store.getState().environment.plugin.supportMessage; }
    },
    modalUI: {
        open: function () { return store.getState().environment.modalUI.open; },
        page: function () { return store.getState().environment.modalUI.page; },
        loadingMode: function () { return store.getState().environment.modalUI.loadingMode; }
    }
};
function getLocaleText(key) {
    if (!peachpayi18n[key]) {
        return peachpayi18n.unknown[Environment.language()];
    }
    return peachpayi18n[key][Environment.language()];
}
function createStore(reducer, preloadedState) {
    var isDispatching = false;
    var currentReducer = reducer;
    var currentState = preloadedState;
    var currentListeners = [];
    var nextListeners = currentListeners;
    var dispatch = function (action) {
        if (typeof action !== 'object') {
            throw new TypeError('You may only dispatch plain objects. Received: ' + typeof action);
        }
        if (typeof action.type === 'undefined') {
            throw new TypeError('You may not have an undefined "type" property.');
        }
        if (isDispatching) {
            throw new Error('Reducers may not dispatch actions.');
        }
        try {
            isDispatching = true;
            currentState = currentReducer(currentState, action);
        }
        finally {
            isDispatching = false;
        }
        var listeners = currentListeners = nextListeners;
        for (var i = 0; i < (listeners === null || listeners === void 0 ? void 0 : listeners.length); i++) {
            var listener = listeners[i];
            listener();
        }
        return action;
    };
    var getState = function () {
        if (isDispatching) {
            throw new Error('You may not call getState from within a reducer.');
        }
        return currentState;
    };
    var subscribe = function (listener) {
        var _a;
        if (typeof listener !== 'function') {
            throw new TypeError('Expected a listener to be a function. Instead received: ' + typeof listener);
        }
        if (isDispatching) {
            throw new Error('You may not add a subscriber from a subscription function.');
        }
        var isSubscribed = true;
        if (nextListeners === currentListeners) {
            nextListeners = (_a = currentListeners === null || currentListeners === void 0 ? void 0 : currentListeners.slice()) !== null && _a !== void 0 ? _a : null;
        }
        nextListeners === null || nextListeners === void 0 ? void 0 : nextListeners.push(listener);
        return function () {
            var _a, _b;
            if (!isSubscribed) {
                return;
            }
            if (isDispatching) {
                throw new Error('You may not remove a subscriber while reducing or inside a subscription function.');
            }
            isSubscribed = false;
            if (nextListeners === currentListeners) {
                nextListeners = (_a = currentListeners === null || currentListeners === void 0 ? void 0 : currentListeners.slice()) !== null && _a !== void 0 ? _a : null;
            }
            var index = (_b = nextListeners === null || nextListeners === void 0 ? void 0 : nextListeners.indexOf(listener)) !== null && _b !== void 0 ? _b : 0;
            nextListeners.slice(index, 1);
            currentListeners = null;
        };
    };
    dispatch({
        type: 'init'
    });
    var store1 = {
        dispatch: dispatch,
        getState: getState,
        subscribe: subscribe
    };
    return store1;
}
var updateCartCalculation = createDispatchUpdate(DispatchActionType.CART_CALCULATION);
createDispatchUpdate(DispatchActionType.DEFAULT_CART_CONTENTS);
var updateCartPackageShippingMethod = createDispatchUpdate(DispatchActionType.CART_SHIPPING_SELECTION);
function createCartSelectors(cartKey) {
    if (cartKey === void 0) { cartKey = '0'; }
    return {
        selectedShippingMethod: function (packageKey) {
            var _a, _b, _c, _d;
            if (packageKey === void 0) { packageKey = '0'; }
            return (_d = (_c = (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.package_record) === null || _b === void 0 ? void 0 : _b[packageKey]) === null || _c === void 0 ? void 0 : _c.selected_method) !== null && _d !== void 0 ? _d : '';
        },
        selectedShippingMethodDetails: function (packageKey) {
            var _a, _b, _c;
            if (packageKey === void 0) { packageKey = '0'; }
            return (_c = (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.package_record) === null || _b === void 0 ? void 0 : _b[packageKey]) !== null && _c !== void 0 ? _c : null;
        },
        contents: function () { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.cart) !== null && _b !== void 0 ? _b : []; },
        subtotal: function () { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.subtotal) !== null && _b !== void 0 ? _b : 0; },
        feeTotal: function (fee) { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.fees_record[fee]) !== null && _b !== void 0 ? _b : 0; },
        totalAppliedFees: function () {
            var _a, _b;
            return Object.entries((_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.fees_record) !== null && _b !== void 0 ? _b : {}).reduce(function (previousValue, _a) {
                var _ = _a[0], value = _a[1];
                return previousValue + (value !== null && value !== void 0 ? value : 0);
            }, 0);
        },
        couponTotal: function (coupon) { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.coupons_record[coupon]) !== null && _b !== void 0 ? _b : 0; },
        totalAppliedCoupons: function () {
            var _a, _b;
            return Object.entries((_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.coupons_record) !== null && _b !== void 0 ? _b : {}).reduce(function (previousValue, _a) {
                var _ = _a[0], value = _a[1];
                return previousValue + (value !== null && value !== void 0 ? value : 0);
            }, 0);
        },
        couponRecord: function () { var _a; return (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.coupons_record; },
        giftCardTotal: function (giftCard) { var _a, _b, _c; return (_c = (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.gift_card_record) === null || _b === void 0 ? void 0 : _b[giftCard]) !== null && _c !== void 0 ? _c : 0; },
        totalAppliedGiftCards: function () {
            var _a, _b;
            return Object.entries((_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.gift_card_record) !== null && _b !== void 0 ? _b : {}).reduce(function (previousValue, _a) {
                var _ = _a[0], value = _a[1];
                return previousValue + (value !== null && value !== void 0 ? value : 0);
            }, 0);
        },
        totalShipping: function () { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.total_shipping) !== null && _b !== void 0 ? _b : 0; },
        totalTax: function () { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.total_tax) !== null && _b !== void 0 ? _b : 0; },
        total: function () { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.total) !== null && _b !== void 0 ? _b : 0; }
    };
}
var DefaultCart = createCartSelectors('0');
var Carts = {
    anyShippingMethodsAvailable: function () {
        for (var _i = 0, _a = Object.keys(store.getState().calculatedCarts); _i < _a.length; _i++) {
            var cartKey = _a[_i];
            var calculatedCart = store.getState().calculatedCarts[cartKey];
            if (!calculatedCart) {
                continue;
            }
            for (var _b = 0, _c = Object.keys(calculatedCart.package_record); _b < _c.length; _b++) {
                var packageKey = _c[_b];
                var shippingPackage = calculatedCart.package_record[packageKey];
                if (!shippingPackage || Object.entries(shippingPackage.methods).length === 0) {
                    continue;
                }
                return true;
            }
        }
        return false;
    },
    virtual: function () {
        for (var _i = 0, _a = Object.keys(store.getState().calculatedCarts); _i < _a.length; _i++) {
            var cartKey = _a[_i];
            var calculatedCart = store.getState().calculatedCarts[cartKey];
            if (!calculatedCart) {
                continue;
            }
            if (!calculatedCart.cart_meta.is_virtual) {
                return false;
            }
        }
        return true;
    },
    collectSelectedShipping: function () {
        var _a;
        var carts = store.getState().calculatedCarts;
        var selectedShippingMethods = [];
        for (var _i = 0, _b = Object.values(carts); _i < _b.length; _i++) {
            var cart = _b[_i];
            if (!cart) {
                continue;
            }
            for (var _c = 0, _d = Object.entries((_a = cart.package_record) !== null && _a !== void 0 ? _a : {}); _c < _d.length; _c++) {
                var _e = _d[_c], packageKey = _e[0], packageRecord = _e[1];
                if (!packageRecord) {
                    continue;
                }
                selectedShippingMethods.push({
                    methodKey: "".concat(packageKey),
                    selectedShipping: packageRecord.selected_method
                });
            }
        }
        return selectedShippingMethods;
    },
    subscriptionPresent: function () {
        for (var _i = 0, _a = Object.keys(store.getState().calculatedCarts); _i < _a.length; _i++) {
            var cartKey = _a[_i];
            var calculatedCart = store.getState().calculatedCarts[cartKey];
            if (!calculatedCart) {
                continue;
            }
            if (calculatedCart.cart_meta.subscription) {
                return true;
            }
        }
        return false;
    }
};
cartSummaryViewData('0');
function cartSummaryViewData(cartKey) {
    return function () {
        var calculatedCart = store.getState().calculatedCarts[cartKey];
        if (!calculatedCart) {
            return {
                cartSummary: new Array(),
                cartMeta: {
                    is_virtual: false
                }
            };
        }
        var cartSummary = [];
        var cartMeta = calculatedCart.cart_meta;
        cartSummary.push({
            key: getLocaleText('subtotal'),
            value: calculatedCart.summary.subtotal
        });
        for (var _i = 0, _a = Object.entries(calculatedCart.summary.coupons_record); _i < _a.length; _i++) {
            var _b = _a[_i], coupon = _b[0], amount = _b[1];
            if (!amount) {
                continue;
            }
            cartSummary.push({
                key: "".concat(getLocaleText('coupon'), " - (").concat(coupon, ")"),
                value: -amount
            });
        }
        for (var _c = 0, _d = Object.entries(calculatedCart.summary.fees_record); _c < _d.length; _c++) {
            var _e = _d[_c], fee = _e[0], amount1 = _e[1];
            if (!amount1) {
                continue;
            }
            cartSummary.push({
                key: "Fee - (".concat(fee, ")"),
                value: amount1
            });
        }
        if (!calculatedCart.cart_meta.is_virtual) {
            cartSummary.push({
                key: getLocaleText('shipping'),
                value: calculatedCart.summary.total_shipping
            });
        }
        if (MerchantConfiguration.tax.displayMode() === 'excludeTax') {
            cartSummary.push({
                key: getLocaleText('tax'),
                value: calculatedCart.summary.total_tax
            });
        }
        for (var _f = 0, _g = Object.entries(calculatedCart.summary.gift_card_record); _f < _g.length; _f++) {
            var _h = _g[_f], giftCard = _h[0], amount2 = _h[1];
            if (!amount2) {
                continue;
            }
            cartSummary.push({
                key: "Gift card - (".concat(giftCard, ")"),
                value: -amount2
            });
        }
        cartSummary.push({
            key: getLocaleText('total'),
            value: calculatedCart.summary.total
        });
        return {
            cartSummary: cartSummary,
            cartMeta: cartMeta
        };
    };
}
var peachpayi18n = {
    add: {
        'de-DE': '+ Hinzufügen',
        'en-US': '+ Add',
        'es-ES': '+ Agregar',
        fr: '+ Ajouter',
        it: '+ Aggiungere',
        ja: '+ 追加',
        'ro-RO': '+ Adăuga',
        ar: 'يضيف +',
        ca: '+ Afegeix',
        'cs-CZ': '+ Přidat',
        'da-DK': '+ Tilføje',
        el: '+ Προσθήκη',
        'hi-IN': '+ जोड़ें',
        'ko-KR': '+ 추가하다',
        'lb-LU': '+ Addéieren',
        'nl-NL': '+ Toevoegen',
        'pt-PT': '+ Adicionar',
        'ru-RU': '+ Добавлять',
        'sl-SI': '+ Dodaj',
        'sv-SE': '+ Lägg till',
        th: '+ เพิ่ม',
        uk: '+ Додати',
        'zh-CN': '+ 添加',
        'zh-TW': '+ 添加'
    },
    'Cart is empty': {
        'en-US': 'Cart is empty',
        'de-DE': 'Kurven er tom',
        'es-ES': 'El carrito esta vacío',
        fr: 'Le panier est vide',
        it: 'Il carrello è vuoto',
        ja: 'カートが空です',
        'ro-RO': 'Coșul este gol',
        ar: 'البطاقه خاليه',
        ca: 'El carretó està buit',
        'cs-CZ': 'Košík je prázdný',
        'da-DK': 'Košarica je prazna',
        el: 'Το καλάθι είναι άδειο',
        'hi-IN': 'कार्ट खाली है',
        'ko-KR': '장바구니가 비어 있습니다.',
        'lb-LU': 'Weenchen ass eidel',
        'nl-NL': 'Winkelwagen is leeg',
        'pt-PT': 'carrinho esta vazio',
        'ru-RU': 'Корзина пуста',
        'sl-SI': 'Košarica je prazna',
        'sv-SE': 'Varukorgen är tom',
        th: 'รถเข็นว่างเปล่า',
        uk: 'Кошик порожній',
        'zh-CN': '购物车是空的',
        'zh-TW': '购物车是空的'
    },
    'You might also like...': {
        'de-DE': 'Das könnte dir auch gefallen...',
        'en-US': 'You might also like...',
        'es-ES': 'También podría gustarte...',
        fr: 'vous pourriez aussi aimer...',
        it: 'Potrebbe piacerti anche...',
        ja: 'あなたはおそらくそれも好きでしょう...',
        'ro-RO': 'S-ar putea sa-ti placa si...',
        ar: 'قد يعجبك ايضا',
        ca: 'potser també t\'agrada...',
        'cs-CZ': 'mohlo by se vám líbit...',
        'da-DK': 'Du kan også lide...',
        el: 'Μπορεί επίσης να σας αρέσει...',
        'hi-IN': 'शायद तुम्हे यह भी अच्छा लगे...',
        'ko-KR': '당신은 또한 좋아할 수도 있습니다...',
        'lb-LU': 'Dir kënnt och gären...',
        'nl-NL': 'Misschien vind je dit ook leuk...',
        'pt-PT': 'você pode gostar também...',
        'ru-RU': 'Вам также может понравиться...',
        'sl-SI': 'Morda vam bo všeč tudi...',
        'sv-SE': 'Du kanske också gillar...',
        th: 'คุณอาจชอบ...',
        uk: 'Вам також може сподобатися...',
        'zh-CN': '你可能还喜欢...',
        'zh-TW': '你可能還喜歡...'
    },
    verified: {
        'de-DE': 'Verifiziert',
        'en-US': 'Verified',
        'es-ES': 'Verificado',
        fr: 'Vérifié',
        it: 'verificato',
        ja: '確認済み',
        'ro-RO': 'Verificat',
        ar: 'تم التحقق',
        ca: 'Verificat',
        'cs-CZ': 'Ověřeno',
        'da-DK': 'Verificeret',
        el: 'Επαληθευμένο',
        'hi-IN': 'सत्यापित',
        'ko-KR': '확인됨',
        'lb-LU': 'Verifizéiert',
        'nl-NL': 'Geverifieerd',
        'pt-PT': 'Verificada',
        'ru-RU': 'Проверено',
        'sl-SI': 'Preverjeno',
        'sv-SE': 'Verifierad',
        th: 'ตรวจสอบแล้ว',
        uk: 'Перевірено',
        'zh-CN': '已验证',
        'zh-TW': '已驗證'
    },
    '+ ADD A COUPON CODE': {
        'de-DE': '+ EINEN GUTSCHEIN CODE HINZUFÜGEN',
        'en-US': '+ ADD A COUPON CODE',
        'es-ES': '+ AÑADIR UN CÓDIGO DE CUPÓN',
        fr: '+ AJOUTER UN CODE COUPON',
        it: '+ AGGIUNGI UN CODICE COUPON',
        ja: '+ クーポンコードを追加',
        'ro-RO': '+ ADĂUGAȚI UN COD DE CUPON',
        ar: 'أضف رمز القسيمة',
        ca: 'Afegiu un codi de cupó',
        'cs-CZ': 'Přidejte kód kupónu',
        'da-DK': 'Tilføj en kuponkode',
        el: 'Προσθέστε έναν κωδικό κουπονιού',
        'hi-IN': 'कूपन कोड जोड़ें',
        'ko-KR': '쿠폰 코드 추가',
        'lb-LU': 'Füügt e Coupon Code derbäi',
        'nl-NL': 'Voeg een couponcode toe',
        'pt-PT': 'Adicionar um código de cupom',
        'ru-RU': 'Добавьте код купона',
        'sl-SI': 'Dodajte kodo kupona',
        'sv-SE': 'Lägg till en kupongkod',
        th: 'เพิ่มรหัสคูปอง',
        uk: 'Додайте код купона',
        'zh-CN': '添加优惠券代码',
        'zh-TW': '添加優惠券代碼'
    },
    'error-occurred': {
        'de-DE': 'Entschuldigung, etwas ist schief gelaufen. Bitte aktualisieren Sie die Seite und versuchen Sie es erneut.',
        'en-US': 'Sorry, something went wrong. Please refresh the page and try again.',
        'es-ES': 'Perdón, algo salió mal. Actualice la página y vuelva a intentarlo.',
        fr: 'Désolé, quelque chose s\'est mal passé. Veuillez actualiser la page et réessayer.',
        it: 'Scusa, qualcosa è andato storto. Perfavore ricarica la pagina e riprova.',
        'ro-RO': 'Scuze, ceva a mers greșit. Actualizați pagina și încercați din nou.',
        ar: 'عذرا، هناك خطأ ما. يرجى تحديث الصفحة وحاول مرة أخرى.',
        ca: 'Ho sentim, alguna cosa ha anat malament. Actualitzeu la pàgina i torneu-ho a provar.',
        'cs-CZ': 'Promiň, něco se pokazilo. Obnovte stránku a zkuste to znovu.',
        'da-DK': 'Undskyld, noget gik galt. Opdater siden, og prøv igen.',
        el: 'Συγνώμη, κάτι πήγε στραβά. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.',
        'hi-IN': 'क्षमा करें, कुछ गलत हो गया। पृष्ठ को रीफ्रेश करें और पुन: प्रयास करें।',
        'ko-KR': '죄송합니다. 문제가 발생했습니다. 페이지를 새로고침하고 다시 시도하십시오.',
        'lb-LU': 'Entschëllegt, eppes ass falsch gaang. Erfrëscht w.e.g. d\'Säit a probéiert nach eng Kéier.',
        'nl-NL': 'Sorry, er ging iets mis. Ververs de pagina en probeer het opnieuw.',
        'pt-PT': 'Desculpe, algo deu errado. Atualize a página e tente novamente.',
        'ru-RU': 'Извините, что-то пошло не так. Обновите страницу и попробуйте еще раз.',
        'sl-SI': 'Oprostite, nekaj je šlo narobe. Osvežite stran in poskusite znova.',
        'sv-SE': 'Förlåt, något gick fel. Uppdatera sidan och försök igen.',
        th: 'ขอโทษมีบางอย่างผิดพลาด. โปรดรีเฟรชหน้าแล้วลองอีกครั้ง',
        uk: 'Вибачте, щось пішло не так. Оновіть сторінку та повторіть спробу.',
        'zh-CN': '抱歉，出了一些问题。 请刷新页面并重试。',
        'zh-TW': '抱歉，出了一些問題。 請刷新頁面並重試。'
    },
    '+ REDEEM GIFT CARD/STORE CREDIT': {
        'de-DE': '+ GESCHENKKARTE/GESCHENK-KREDIT EINLÖSEN',
        'en-US': '+ REDEEM GIFT CARD/STORE CREDIT',
        'es-ES': '+ CANJEAR TARJETA DE REGALO/CRÉDITO DE TIENDA',
        fr: '+ ÉCHANGER LA CARTE-CADEAU/LE CRÉDIT DU MAGASIN',
        it: '+ UTILIZZA CARTA REGALO/CREDITO NEGOZIO',
        ja: '+ ギフトカード/ストアクレジットを利用する',
        'ro-RO': '+ RĂscumpărați cardul/cadoul de credit cadou',
        ar: 'استرداد بطاقة الهدايا / رصيد المتجر',
        ca: 'Bescanvia el crèdit de la targeta regal o de la botiga',
        'cs-CZ': 'Uplatněte dárkovou kartu/kredit obchodu',
        'da-DK': 'Indløs gavekort/butikskredit',
        el: 'Εξαργυρώστε πιστωτική κάρτα δώρου/κατάστημα',
        'hi-IN': 'उपहार कार्ड/स्टोर क्रेडिट रिडीम करें',
        'ko-KR': '기프트 카드/스토어 크레딧 사용',
        'lb-LU': 'Erléis Kaddokaart/Geschäftskreditt',
        'nl-NL': 'Cadeaukaart/winkeltegoed inwisselen',
        'pt-PT': 'Resgatar cartão-presente / crédito da loja',
        'ru-RU': 'Погасить подарочную карту / кредит магазина',
        'sl-SI': 'Unovčite darilno kartico/dobroimetje v trgovini',
        'sv-SE': 'Lös in presentkort/butikskredit',
        th: 'แลกบัตรของขวัญ/เครดิตร้านค้า',
        uk: 'Активуйте подарункову картку/кредит у магазині',
        'zh-CN': '兑换礼品卡/商店信用',
        'zh-TW': '兌換禮品卡/商店信用'
    },
    'Send to': {
        'de-DE': 'Senden an',
        'en-US': 'Send to',
        'es-ES': 'Enviar a',
        fr: 'Envoyer à',
        it: 'Inviare a',
        ja: '送信先',
        'ro-RO': 'Trimite catre',
        ar: 'ارسل إلى',
        ca: 'Envia a',
        'cs-CZ': 'Poslat komu',
        'da-DK': 'Send til',
        el: 'Στέλνω σε',
        'hi-IN': 'भेजना',
        'ko-KR': '보내기',
        'lb-LU': 'Schécken',
        'nl-NL': 'Verzenden naar',
        'pt-PT': 'Enviar para',
        'ru-RU': 'Отправить',
        'sl-SI': 'Pošlji',
        'sv-SE': 'Skicka till',
        th: 'ส่งถึง',
        uk: 'Відправити',
        'zh-CN': '发给',
        'zh-TW': '發給'
    },
    'My order': {
        'de-DE': 'Meine Bestellung',
        'en-US': 'My order',
        'es-ES': 'Mi pedido',
        fr: 'Ma commande',
        it: 'Il mio ordine',
        ja: '注文',
        'ro-RO': 'Comanda mea',
        ar: 'طلبي',
        ca: 'El meu ordre',
        'cs-CZ': 'Moje objednávka',
        'da-DK': 'Min bestilling',
        el: 'Η παραγγελία μου',
        'hi-IN': 'मेरे आदेश',
        'ko-KR': '내 주문',
        'lb-LU': 'Meng Bestellung',
        'nl-NL': 'Mijn bestelling',
        'pt-PT': 'Meu pedido',
        'ru-RU': 'Мой заказ',
        'sl-SI': 'Moj ukaz',
        'sv-SE': 'Min order',
        th: 'คำสั่งของฉัน',
        uk: 'Моє замовлення',
        'zh-CN': '我的订单',
        'zh-TW': '我的訂單'
    },
    'Ready to check out?': {
        'de-DE': 'Bereit zum Auschecken?',
        'en-US': 'Ready to check out?',
        'es-ES': '¿Listo para salir?',
        fr: 'Prêt à vérifier ?',
        it: 'Pronto per il check-out?',
        ja: '支払いをする準備はできましたか?',
        'ro-RO': 'Sunteți gata să vizitați?',
        ar: 'هل أنت جاهز للتسجيل؟',
        ca: 'A punt per fer el check-out?',
        'cs-CZ': 'Jste připraveni se podívat?',
        'da-DK': 'Klar til at tjekke ud?',
        el: 'Είστε έτοιμοι για check out;',
        'hi-IN': 'चेक आउट करने के लिए तैयार हैं?',
        'ko-KR': '체크아웃할 준비가 되셨나요?',
        'lb-LU': 'Prett fir ze checken?',
        'nl-NL': 'Klaar om uit te checken?',
        'pt-PT': 'Pronto para finalizar a compra?',
        'ru-RU': 'Готовы проверить?',
        'sl-SI': 'Ste pripravljeni na odjavo?',
        'sv-SE': 'Klar att checka ut?',
        th: 'พร้อมที่จะเช็คเอาท์?',
        uk: 'Готові перевірити?',
        'zh-CN': '准备结账了吗？',
        'zh-TW': '準備結帳了嗎？'
    },
    info: {
        'de-DE': 'Information',
        'en-US': 'Info',
        'es-ES': 'Información',
        fr: 'Information',
        it: 'Informazioni',
        ja: '情報',
        'ro-RO': 'Informație',
        ar: 'معلومة',
        ca: 'Informació',
        'cs-CZ': 'Informace',
        'da-DK': 'Information',
        el: 'Πληροφορίες',
        'hi-IN': 'जानकारी',
        'ko-KR': '정보',
        'lb-LU': 'Informatiounen',
        'nl-NL': 'Informatie',
        'pt-PT': 'Em formação',
        'ru-RU': 'Информация',
        'sl-SI': 'Informacije',
        'sv-SE': 'Information',
        th: 'ข้อมูล',
        uk: 'Інформація',
        'zh-CN': '信息',
        'zh-TW': '信息'
    },
    payment: {
        'de-DE': 'Zahlung',
        'en-US': 'Payment',
        'es-ES': 'Pago',
        fr: 'Paiement',
        it: 'Pagamento',
        ja: '支払い',
        'ro-RO': 'Plată',
        ar: 'قسط',
        ca: 'Pagament',
        'cs-CZ': 'Způsob platby',
        'da-DK': 'Betaling',
        el: 'Πληρωμή',
        'hi-IN': 'भुगतान',
        'ko-KR': '지불',
        'lb-LU': 'Bezuelen',
        'nl-NL': 'Betaling',
        'pt-PT': 'Pagamento',
        'ru-RU': 'Оплата',
        'sl-SI': 'Plačilo',
        'sv-SE': 'Betalning',
        th: 'การชำระเงิน',
        uk: 'Оплата',
        'zh-CN': '支付',
        'zh-TW': '支付'
    },
    personal: {
        'de-DE': 'Persönlich',
        'en-US': 'Personal',
        'es-ES': 'Personal',
        fr: 'Coordonnées',
        it: 'Personale',
        ja: '個人',
        'ro-RO': 'Personal',
        ar: 'شخصي',
        ca: 'Personal',
        'cs-CZ': 'Osobní',
        'da-DK': 'Personlig',
        el: 'Προσωπικός',
        'hi-IN': 'निजी',
        'ko-KR': '개인의',
        'lb-LU': 'Perséinlech',
        'nl-NL': 'persoonlijk',
        'pt-PT': 'Pessoal',
        'ru-RU': 'Личное',
        'sl-SI': 'Osebno',
        'sv-SE': 'Personlig',
        th: 'ส่วนตัว',
        uk: 'Особисті',
        'zh-CN': '个人的',
        'zh-TW': '個人的'
    },
    shipping: {
        'de-DE': 'Versand',
        'en-US': 'Shipping',
        'es-ES': 'Envio',
        fr: 'Livraison',
        it: 'Spedizione',
        ja: '発送',
        'ro-RO': 'Livrare',
        ar: 'شحن',
        ca: 'Enviament',
        'cs-CZ': 'Lodní doprava',
        'da-DK': 'Forsendelse',
        el: 'Αποστολή',
        'hi-IN': 'शिपिंग',
        'ko-KR': '배송',
        'lb-LU': 'Liwwerung',
        'nl-NL': 'Verzending',
        'pt-PT': 'Envio',
        'ru-RU': 'Перевозки',
        'sl-SI': 'Dostava',
        'sv-SE': 'Frakt',
        th: 'การส่งสินค้า',
        uk: 'Доставка',
        'zh-CN': '邮寄',
        'zh-TW': '寄貨'
    },
    billing: {
        'de-DE': 'Rechnungsadresse',
        'en-US': 'Billing',
        'es-ES': 'Dirección de facturación',
        fr: 'Adresse de facturation',
        it: 'Indirizzo di fatturazione',
        ja: '請求',
        'ro-RO': 'Facturare',
        ar: 'الفواتير',
        ca: 'Facturació',
        'cs-CZ': 'Fakturace',
        'da-DK': 'Fakturering',
        el: 'Χρέωση',
        'hi-IN': 'बिलिंग',
        'ko-KR': '청구',
        'lb-LU': 'Rechnung',
        'nl-NL': 'Facturering',
        'pt-PT': 'Cobrança',
        'ru-RU': 'Биллинг',
        'sl-SI': 'Obračunavanje',
        'sv-SE': 'Fakturering',
        th: 'การเรียกเก็บเงิน',
        uk: 'Виставлення рахунків',
        'zh-CN': '账单',
        'zh-TW': '計費'
    },
    continue: {
        'de-DE': 'Weiter',
        'en-US': 'Continue',
        'es-ES': 'Continuar',
        fr: 'Continuez',
        it: 'Continua',
        ja: '続きへ',
        'ro-RO': 'Continua',
        ar: 'يكمل',
        ca: 'Continua',
        'cs-CZ': 'Pokračovat',
        'da-DK': 'Blive ved',
        el: 'Να συνεχίσει',
        'hi-IN': 'जारी रखना',
        'ko-KR': '계속하다',
        'lb-LU': 'Fuert weider',
        'nl-NL': 'Doorgaan met',
        'pt-PT': 'Prosseguir',
        'ru-RU': 'Продолжать',
        'sl-SI': 'Nadaljuj',
        'sv-SE': 'Fortsätta',
        th: 'ดำเนินการต่อ',
        uk: 'Продовжити',
        'zh-CN': '继续',
        'zh-TW': '繼續'
    },
    'Secured by': {
        'de-DE': 'Gesichert durch ',
        'en-US': 'Secured by',
        'es-ES': 'Protegido por',
        fr: 'Sécurisé par',
        it: 'Protetto da',
        ja: '保護されています',
        'ro-RO': 'Garantat de',
        ar: 'بضمان',
        ca: 'Garantit per',
        'cs-CZ': 'Zajištěno',
        'da-DK': 'Sikret af',
        el: 'Εξασφαλίζεται από',
        'hi-IN': 'इसके जरिए सुरक्षित',
        'ko-KR': '보안',
        'lb-LU': 'Geséchert vun',
        'nl-NL': 'Beveiligd door',
        'pt-PT': 'Assegurado por',
        'ru-RU': 'Обеспечено',
        'sl-SI': 'Zavarovano s',
        'sv-SE': 'Säkrad av',
        th: 'ปลอดภัยโดย',
        uk: 'Забезпечується',
        'zh-CN': 'Secured by',
        'zh-TW': 'Secured by'
    },
    exit: {
        'de-DE': 'Kasse verlassen',
        'en-US': 'Exit Checkout',
        'es-ES': 'Salir de la caja',
        fr: 'Quitter',
        it: 'Esci dal checkout',
        ja: '支払いを終了',
        'ro-RO': 'Înapoi la pagina produsului',
        ar: 'الخروج من الخروج',
        ca: 'Surt de Google Checkout',
        'cs-CZ': 'Ukončit pokladnu',
        'da-DK': 'Afslut Checkout',
        el: 'Έξοδος από το ταμείο',
        'hi-IN': 'चेकआउट से बाहर निकलें',
        'ko-KR': '체크아웃 종료',
        'lb-LU': 'Exit Checkout',
        'nl-NL': 'Afrekenen afsluiten',
        'pt-PT': 'Sair do checkout',
        'ru-RU': 'Выйти из кассы',
        'sl-SI': 'Zaprite Checkout',
        'sv-SE': 'Avsluta kassan',
        th: 'ออกจากการชำระเงิน',
        uk: 'Вийти з Checkout',
        'zh-CN': '退出结帐',
        'zh-TW': '退出結帳'
    },
    'Order summary': {
        'de-DE': 'Bestellzusammenfassung',
        'en-US': 'Order summary',
        'es-ES': 'Resumen del pedido',
        fr: 'Récapitulatif de la commande',
        it: 'Riepilogo dell\'ordine',
        ja: '注文の概要',
        'ro-RO': 'Rezumat Comandă',
        ar: 'ملخص الطلب',
        ca: 'Resum de la comanda',
        'cs-CZ': 'Přehled objednávky',
        'da-DK': 'Ordresammendrag',
        el: 'Περίληψη παραγγελίας',
        'hi-IN': 'आदेश सारांश',
        'ko-KR': '주문 요약',
        'lb-LU': 'Uerdnung Resumé',
        'nl-NL': 'Overzicht van de bestelling',
        'pt-PT': 'Resumo do pedido',
        'ru-RU': 'Итог заказа',
        'sl-SI': 'Povzetek naročila',
        'sv-SE': 'Ordersammanfattning',
        th: 'สรุปคำสั่งซื้อ',
        uk: 'Підсумок Замовлення',
        'zh-CN': '订单摘要',
        'zh-TW': '訂單摘要'
    },
    subtotal: {
        'de-DE': 'Zwischensumme',
        'en-US': 'Subtotal',
        'es-ES': 'Subtotal',
        fr: 'Sous-total',
        it: 'Totale parziale',
        ja: '小計',
        'ro-RO': 'Subtotal',
        ar: 'المجموع الفرعي',
        ca: 'Subtotal',
        'cs-CZ': 'Mezisoučet',
        'da-DK': 'Subtotal',
        el: 'ΜΕΡΙΚΟ ΣΥΝΟΛΟ',
        'hi-IN': 'उप-योग',
        'ko-KR': '소계',
        'lb-LU': 'Subtotal',
        'nl-NL': 'Subtotaal',
        'pt-PT': 'Subtotal',
        'ru-RU': 'Промежуточный итог',
        'sl-SI': 'Vmesni seštevek',
        'sv-SE': 'Delsumma',
        th: 'ยอดรวม',
        uk: 'Проміжний підсумок',
        'zh-CN': '小计',
        'zh-TW': '小計'
    },
    tax: {
        'de-DE': 'Steuer',
        'en-US': 'Tax',
        'es-ES': 'Impuesto',
        fr: 'Impôt',
        it: 'Tassa',
        ja: '税',
        'ro-RO': 'Impozit',
        ar: 'ضريبة',
        ca: 'Impostos',
        'cs-CZ': 'Daň',
        'da-DK': 'Skat',
        el: 'Φόρος',
        'hi-IN': 'कर',
        'ko-KR': '세',
        'lb-LU': 'Steier',
        'nl-NL': 'Belasting',
        'pt-PT': 'Imposto',
        'ru-RU': 'Налог',
        'sl-SI': 'Davek',
        'sv-SE': 'Beskatta',
        th: 'ภาษี',
        uk: 'Податок',
        'zh-CN': '税',
        'zh-TW': '稅'
    },
    total: {
        'de-DE': 'Gesamt',
        'en-US': 'Total',
        'es-ES': 'Total',
        fr: 'Total',
        it: 'Totale',
        ja: '合計',
        'ro-RO': 'Total',
        ar: 'المجموع',
        ca: 'Total',
        'cs-CZ': 'Celkový',
        'da-DK': 'i alt',
        el: 'Σύνολο',
        'hi-IN': 'कुल',
        'ko-KR': '총',
        'lb-LU': 'Insgesamt',
        'nl-NL': 'Totaal',
        'pt-PT': 'Total',
        'ru-RU': 'Общий',
        'sl-SI': 'Skupaj',
        'sv-SE': 'Total',
        th: 'รวม',
        uk: 'Всього',
        'zh-CN': '总计',
        'zh-TW': '總計'
    },
    coupon: {
        'de-DE': 'Coupon',
        'en-US': 'Coupon',
        'es-ES': 'Cupón',
        fr: 'Coupon',
        it: 'Coupon',
        ja: 'クーポン',
        'ro-RO': 'Cupon',
        ar: 'قسيمة',
        ca: 'Cupó',
        'cs-CZ': 'Kupón',
        'da-DK': 'Kupon',
        el: 'Κουπόνι',
        'hi-IN': 'कूपन',
        'ko-KR': '쿠폰',
        'lb-LU': 'Coupon',
        'nl-NL': 'Coupon',
        'pt-PT': 'Cupom',
        'ru-RU': 'Купон',
        'sl-SI': 'Kupon',
        'sv-SE': 'Kupong',
        th: 'คูปอง',
        uk: 'Купон',
        'zh-CN': '优惠券',
        'zh-TW': '優惠券'
    },
    'Coupon code': {
        'de-DE': 'Gutscheincode',
        'en-US': 'Coupon code',
        'es-ES': 'Código promocional',
        fr: 'Code de coupon',
        it: 'Codice coupon',
        ja: 'クーポンコード',
        'ro-RO': 'Cod cupon',
        ar: 'رمز الكوبون',
        ca: 'Codi de cupó',
        'cs-CZ': 'Kód kupónu',
        'da-DK': 'Kuponkode',
        el: 'Κωδικός κουπονιού',
        'hi-IN': 'कूपन कोड',
        'ko-KR': '쿠폰 코드',
        'lb-LU': 'Coupon Code',
        'nl-NL': 'Coupon code',
        'pt-PT': 'Código do cupom',
        'ru-RU': 'Код купона',
        'sl-SI': 'Koda kupona',
        'sv-SE': 'Kupongskod',
        th: 'รหัสคูปอง',
        uk: 'Код купона',
        'zh-CN': '优惠卷号码',
        'zh-TW': '優惠卷號碼'
    },
    'You entered an invalid coupon code': {
        'de-DE': 'Sie haben einen ungültigen Gutscheincode eingegeben',
        'en-US': 'You entered an invalid coupon code',
        'es-ES': 'Ingresaste un código de cupón no válido',
        fr: 'Vous avez entré un code de coupon non valide',
        it: 'Hai inserito un codice coupon non valido',
        ja: '無効なクーポンコードを入力しました',
        'ro-RO': 'Ați introdus un cod de cupon nevalid',
        ar: 'لقد أدخلت رمز قسيمة غير صالح',
        ca: 'Heu introduït un codi de cupó no vàlid',
        'cs-CZ': 'Zadali jste neplatný kód kupónu',
        'da-DK': 'Du har indtastet en ugyldig kuponkode',
        el: 'Καταχωρίσατε έναν μη έγκυρο κωδικό κουπονιού',
        'hi-IN': 'आपने एक अमान्य कूपन कोड दर्ज किया है',
        'ko-KR': '잘못된 쿠폰 코드를 입력했습니다.',
        'lb-LU': 'Dir hutt en ongëltege Couponcode aginn',
        'nl-NL': 'U heeft een ongeldige couponcode ingevoerd',
        'pt-PT': 'Você inseriu um código de cupom inválido',
        'ru-RU': 'Вы ввели неверный код купона',
        'sl-SI': 'Vnesli ste neveljavno kodo kupona',
        'sv-SE': 'Du har angett en ogiltig kupongkod',
        th: 'คุณป้อนรหัสคูปองไม่ถูกต้อง',
        uk: 'Ви ввели недійсний код купона',
        'zh-CN': '您输入了无效的优惠券代码',
        'zh-TW': '您輸入了無效的優惠券代碼'
    },
    apply: {
        'de-DE': 'Einlösen',
        'en-US': 'Apply',
        'es-ES': 'Aplicar',
        fr: 'Appliquer',
        it: 'Applicare',
        ja: '申込み',
        'ro-RO': 'Aplica',
        ar: 'تطبيق',
        ca: 'Aplicar',
        'cs-CZ': 'Aplikovat',
        'da-DK': 'ansøge',
        el: 'Ισχύουν',
        'hi-IN': 'लागू करना',
        'ko-KR': '적용하다',
        'lb-LU': 'Gëlle',
        'nl-NL': 'Van toepassing zijn',
        'pt-PT': 'Aplicar',
        'ru-RU': 'Подать заявление',
        'sl-SI': 'Uporabi',
        'sv-SE': 'Tillämpa',
        th: 'นำมาใช้',
        uk: 'Застосувати',
        'zh-CN': '应用',
        'zh-TW': '應用'
    },
    'gift-card': {
        'de-DE': 'Geschenkkarte',
        'en-US': 'Gift card',
        'es-ES': 'Tarjeta de regalo',
        fr: 'Carte cadeau',
        it: 'Carta regalo',
        ja: 'ギフトカード',
        'ro-RO': 'Card cadou',
        ar: 'كرت هدية',
        ca: 'Targeta regal',
        'cs-CZ': 'Dárková poukázka',
        'da-DK': 'Gavekort',
        el: 'Δωροκάρτα',
        'hi-IN': 'उपहार पत्र',
        'ko-KR': '기프트 카드',
        'lb-LU': 'Kaddokaart',
        'nl-NL': 'Cadeaukaart',
        'pt-PT': 'Cartão Presente',
        'ru-RU': 'Подарочная карта',
        'sl-SI': 'Darilne kartice',
        'sv-SE': 'Present kort',
        th: 'บัตรของขวัญ',
        uk: 'Подарункова картка',
        'zh-CN': '礼物卡',
        'zh-TW': '禮物卡'
    },
    'Gift card number': {
        'de-DE': 'Geschenkkartennummer',
        'en-US': 'Gift card number',
        'es-ES': 'Numero de tarjeta de regalo',
        fr: 'Numéro de la carte-cadeau',
        it: 'Numero della carta regalo',
        ja: 'ギフトカード番号',
        'ro-RO': 'Numărul cardului cadou',
        ar: 'رقم بطاقة الهدية',
        ca: 'Número de targeta regal',
        'cs-CZ': 'Číslo dárkové karty',
        'da-DK': 'Gavekortnummer',
        el: 'Αριθμός δωροκάρτας',
        'hi-IN': 'गिफ्ट कार्ड नंबर',
        'ko-KR': '기프트 카드 번호',
        'lb-LU': 'Geschenkkaart Nummer',
        'nl-NL': 'Cadeaukaartnummer',
        'pt-PT': 'Número do cartão-presente',
        'ru-RU': 'Номер подарочной карты',
        'sl-SI': 'Številka darilne kartice',
        'sv-SE': 'Presentkortnummer',
        th: 'หมายเลขบัตรของขวัญ',
        uk: 'Номер подарункової картки',
        'zh-CN': '礼品卡号',
        'zh-TW': '禮品卡號'
    },
    'You entered an invalid gift card': {
        'de-DE': 'Sie haben eine ungültige Geschenkkarte eingegeben',
        'en-US': 'You entered an invalid gift card',
        'es-ES': 'Ingresaste una tarjeta de regalo no válida',
        fr: 'Vous avez entré une carte-cadeau non valide',
        it: 'Hai inserito una carta regalo non valida',
        ja: '無効なギフトカードを入力しました',
        'ro-RO': 'Ați introdus un card cadou nevalid',
        ar: 'لقد أدخلت بطاقة هدايا غير صالحة',
        ca: 'Heu introduït una targeta regal no vàlida',
        'cs-CZ': 'Zadali jste neplatnou dárkovou kartu',
        'da-DK': 'Du har indtastet et ugyldigt gavekort',
        el: 'Καταχωρίσατε μια μη έγκυρη δωροκάρτα',
        'hi-IN': 'आपने एक अमान्य उपहार कार्ड दर्ज किया है',
        'ko-KR': '잘못된 기프트 카드를 입력했습니다.',
        'lb-LU': 'Dir hutt eng ongëlteg Kaddokaart aginn',
        'nl-NL': 'Je hebt een ongeldige cadeaubon ingevoerd',
        'pt-PT': 'Você inseriu um vale-presente inválido',
        'ru-RU': 'Вы ввели недействительную подарочную карту',
        'sl-SI': 'Vnesli ste neveljavno darilno kartico',
        'sv-SE': 'Du har angett ett ogiltigt presentkort',
        th: 'คุณป้อนบัตรของขวัญที่ไม่ถูกต้อง',
        uk: 'Ви ввели недійсну подарункову картку',
        'zh-CN': '您输入了无效的礼品卡',
        'zh-TW': '您輸入了無效的禮品卡'
    },
    'gift-card-already-applied': {
        'de-DE': 'Diese Geschenkkarte wurde bereits angewendet.',
        'en-US': 'This gift card has already been applied.',
        'es-ES': 'Esta tarjeta de regalo ya se aplicó.',
        fr: 'Cette carte-cadeau a déjà été appliquée.',
        it: 'Questa carta regalo è già stata applicata.',
        ja: 'このギフトカードはすでに適用されています。',
        'ro-RO': 'Acest card cadou a fost deja aplicat.',
        ar: 'تم تطبيق بطاقة الهدايا هذه بالفعل.',
        ca: 'Aquesta targeta regal ja s\'ha aplicat.',
        'cs-CZ': 'Tato dárková karta již byla použita.',
        'da-DK': 'Dette gavekort er allerede anvendt.',
        el: 'Αυτή η δωροκάρτα έχει ήδη εφαρμοστεί.',
        'hi-IN': 'यह उपहार कार्ड पहले ही लागू किया जा चुका है।',
        'ko-KR': '이 기프트 카드는 이미 적용되었습니다.',
        'lb-LU': 'Dës Kaddokaart gouf scho applizéiert.',
        'nl-NL': 'Deze cadeaubon is al toegepast.',
        'pt-PT': 'Este vale-presente já foi aplicado.',
        'ru-RU': 'Эта подарочная карта уже была применена.',
        'sl-SI': 'Ta darilna kartica je že bila uporabljena.',
        'sv-SE': 'Detta presentkort har redan tillämpats.',
        th: 'มีการใช้บัตรของขวัญนี้แล้ว',
        uk: 'Цю подарункову картку вже застосовано.',
        'zh-CN': '此礼品卡已被应用。',
        'zh-TW': '此禮品卡已被應用。'
    },
    'ship-to': {
        'de-DE': 'Versand nach',
        'en-US': 'Ship to',
        'es-ES': 'Enviar a',
        fr: 'Envoyez à',
        it: 'Spedire a',
        ja: '配送先',
        'ro-RO': 'Îmbarca spre',
        ar: 'سافر على متن سفينة ل',
        ca: 'Envia a',
        'cs-CZ': 'Dopravit do',
        'da-DK': 'Send til',
        el: 'Αποστολή προς',
        'hi-IN': 'यहां भेजें',
        'ko-KR': '배송지',
        'lb-LU': 'Schécken un',
        'nl-NL': 'Verzend naar',
        'pt-PT': 'Enviar para',
        'ru-RU': 'Доставка до',
        'sl-SI': 'Poslati v',
        'sv-SE': 'Frakta till',
        th: 'ส่งไปที่',
        uk: 'Відправити до',
        'zh-CN': '运送到',
        'zh-TW': '運送到'
    },
    'Credit or debit card': {
        'de-DE': 'Kredit- oder Debitkarte',
        'en-US': 'Credit or debit card',
        'es-ES': 'Tarjeta de crédito o débito',
        fr: 'Carte de crédit ou de débit',
        it: 'Carta di credito o di debito',
        ja: 'クレジットもしくはデビットカード',
        'ro-RO': 'Card de credit sau debit',
        ar: 'بطاقة الائتمان أو الخصم',
        ca: 'Targeta de crèdit o dèbit',
        'cs-CZ': 'Kreditní nebo debetní karta',
        'da-DK': 'Kredit- eller betalingskort',
        el: 'Πιστωτική ή χρεωστική κάρτα',
        'hi-IN': 'क्रेडिट या डेबिट कार्ड',
        'ko-KR': '신용카드 또는 직불카드',
        'lb-LU': 'Kreditt- oder Bankkaart',
        'nl-NL': 'Creditcard of bankpas',
        'pt-PT': 'Cartão de crédito ou débito',
        'ru-RU': 'Кредитная или дебетовая карта',
        'sl-SI': 'Kreditna ali debetna kartica',
        'sv-SE': 'Kredit-eller betalkort',
        th: 'บัตรเครดิตหรือบัตรเดบิต',
        uk: 'Кредитна або дебетова картка',
        'zh-CN': '信用卡或借记卡',
        'zh-TW': '信用卡或借記卡'
    },
    pay: {
        'de-DE': 'Zahlen',
        'en-US': 'Pay',
        'es-ES': 'Pagar',
        fr: 'Payer',
        it: 'Pagare',
        ja: '支払い',
        'ro-RO': 'Plătește',
        ar: 'يدفع',
        ca: 'Paga',
        'cs-CZ': 'Platit',
        'da-DK': 'Betale',
        el: 'Πληρωμή',
        'hi-IN': 'वेतन',
        'ko-KR': '지불',
        'lb-LU': 'Bezuelen',
        'nl-NL': 'Betalen',
        'pt-PT': 'Pagar',
        'ru-RU': 'Платить',
        'sl-SI': 'Plačaj',
        'sv-SE': 'Betala',
        th: 'จ่าย',
        uk: 'Оплата',
        'zh-CN': '支付',
        'zh-TW': '支付'
    },
    back: {
        'de-DE': 'Zurück zu Informationen',
        'en-US': 'Back to info',
        'es-ES': 'Volver a información',
        fr: 'Retour aux informations',
        it: 'Torna alle informazioni',
        ja: '情報に戻る',
        'ro-RO': 'Înapoi la informații',
        ar: 'رجوع إلى المعلومات',
        ca: 'Torna a la informació',
        'cs-CZ': 'Zpět k informacím',
        'da-DK': 'Tilbage til information',
        el: 'Επιστροφή στις πληροφορίες',
        'hi-IN': 'जानकारी पर वापस जाएं',
        'ko-KR': '정보로 돌아가기',
        'lb-LU': 'Zréck op Informatioun',
        'nl-NL': 'Terug naar informatie',
        'pt-PT': 'Voltar para a informação',
        'ru-RU': 'Вернуться к информации',
        'sl-SI': 'Nazaj na informacije',
        'sv-SE': 'Tillbaka till information',
        th: 'กลับไปที่ข้อมูล',
        uk: 'Назад до інформації',
        'zh-CN': '返回信息',
        'zh-TW': '返回信息'
    },
    email: {
        'de-DE': 'Email',
        'en-US': 'Email',
        'es-ES': 'Correo',
        fr: 'Email',
        it: 'Email',
        ja: 'メールアドレス',
        'ro-RO': 'Email',
        ar: 'بريد الالكتروني',
        ca: 'Correu electrònic',
        'cs-CZ': 'E-mailem',
        'da-DK': 'E -mail',
        el: 'ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΕΥΘΥΝΣΗ',
        'hi-IN': 'ईमेल',
        'ko-KR': '이메일',
        'lb-LU': 'Email',
        'nl-NL': 'E-mail',
        'pt-PT': 'O email',
        'ru-RU': 'Эл. адрес',
        'sl-SI': 'E-naslov',
        'sv-SE': 'E-post',
        th: 'อีเมล',
        uk: 'Електронна пошта',
        'zh-CN': '电子邮件',
        'zh-TW': '電子郵件'
    },
    delivery: {
        'de-DE': 'Lieferung',
        'en-US': 'Delivery',
        'es-ES': 'Entrega',
        fr: 'Livraison',
        it: 'Consegna',
        ja: '配送',
        'ro-RO': 'Livrare',
        ar: 'توصيل',
        ca: 'Lliurament',
        'cs-CZ': 'dodávka',
        'da-DK': 'Levering',
        el: 'Διανομή',
        'hi-IN': 'वितरण',
        'ko-KR': '배달',
        'lb-LU': 'Liwwerung',
        'nl-NL': 'Levering',
        'pt-PT': 'Entrega',
        'ru-RU': 'Доставка',
        'sl-SI': 'Dostava',
        'sv-SE': 'Leverans',
        th: 'จัดส่ง',
        uk: 'Доставка',
        'zh-CN': '送货',
        'zh-TW': '送貨'
    },
    card: {
        'de-DE': 'Karte',
        'en-US': 'Card',
        'es-ES': 'Tarjeta',
        fr: 'Carte',
        it: 'Carta',
        ja: 'カード',
        'ro-RO': 'Card',
        ar: 'بطاقة',
        ca: 'Targeta',
        'cs-CZ': 'Kartu',
        'da-DK': 'Kort',
        el: 'Κάρτα',
        'hi-IN': 'कार्ड',
        'ko-KR': '카드',
        'lb-LU': 'Kaart',
        'nl-NL': 'Kaart',
        'pt-PT': 'Cartão',
        'ru-RU': 'Карта',
        'sl-SI': 'Kartica',
        'sv-SE': 'Kort',
        th: 'การ์ด',
        uk: 'Картка',
        'zh-CN': '卡',
        'zh-TW': '卡'
    },
    edit: {
        'de-DE': 'Bearbeiten',
        'en-US': 'Edit',
        'es-ES': 'Editar',
        fr: 'Éditer',
        it: 'Modificare',
        ja: '編集',
        'ro-RO': 'Edita',
        ar: 'يحرر',
        ca: 'Edita',
        'cs-CZ': 'Upravit',
        'da-DK': 'Redigere',
        el: 'Επεξεργασία',
        'hi-IN': 'संपादित करें',
        'ko-KR': '편집하다',
        'lb-LU': 'Editéieren',
        'nl-NL': 'Bewerking',
        'pt-PT': 'Editar',
        'ru-RU': 'Редактировать',
        'sl-SI': 'Uredi',
        'sv-SE': 'Redigera',
        th: 'แก้ไข',
        uk: 'Редагувати',
        'zh-CN': '编辑',
        'zh-TW': '編輯'
    },
    'Sorry, this store does not ship to your location.': {
        'de-DE': 'Dieser Shop liefert leider nicht an Ihren Standort.',
        'en-US': 'Sorry, this store does not ship to your location.',
        'es-ES': 'Lo sentimos, esta tienda no se envía a su ubicación.',
        fr: 'Désolé, ce magasin ne livre pas à votre emplacement.',
        it: 'Siamo spiacenti, questo negozio non viene spedito alla tua posizione.',
        ja: '申し訳ありませんが、このストアはお住まいの地域に発送されません。',
        'ro-RO': 'Ne pare rău, acest magazin nu este livrat în locația dvs.',
        ar: 'عذرا ، هذا المتجر لا يشحن إلى موقعك.',
        ca: 'Aquesta botiga no s\'envia a la vostra ubicació.',
        'cs-CZ': 'Je nám líto, tento obchod vám nedoručíme.',
        'da-DK': 'Beklager, denne butik sender ikke til din lokation.',
        el: 'Λυπούμαστε, αυτό το κατάστημα δεν αποστέλλεται στην τοποθεσία σας.',
        'hi-IN': 'क्षमा करें, यह स्टोर आपके स्थान पर शिप नहीं करता है।',
        'ko-KR': '죄송합니다. 이 상점은 귀하의 위치로 배송되지 않습니다.',
        'lb-LU': 'Entschëllegt, dëse Buttek gëtt net op Är Location verschéckt.',
        'nl-NL': 'Sorry, deze winkel verzendt niet naar jouw locatie.',
        'pt-PT': 'Desculpe, esta loja não envia para o seu local.',
        'ru-RU': 'К сожалению, доставка в этот магазин не осуществляется.',
        'sl-SI': 'Ta trgovina žal ni dostavljena na vašo lokacijo.',
        'sv-SE': 'Den här butiken skickas inte till din plats.',
        th: 'ขออภัย ร้านค้านี้ไม่ได้จัดส่งไปยังตำแหน่งของคุณ',
        uk: 'На жаль, цей магазин не доставляється до вашого місцезнаходження.',
        'zh-CN': '抱歉，这家商店不发货到您所在的位置。',
        'zh-TW': '抱歉，這家商店不發貨到您所在的位置。'
    },
    processing: {
        'de-DE': 'Verarbeitung',
        'en-US': 'Processing',
        'es-ES': 'Procesamiento',
        fr: 'Traitement',
        it: 'Elaborazione',
        ja: '進行中',
        'ro-RO': 'Prelucrare',
        ar: 'يعالج',
        ca: 'Processament',
        'cs-CZ': 'zpracovává se',
        'da-DK': 'Forarbejdning',
        el: 'Επεξεργασία',
        'hi-IN': 'प्रसंस्करण',
        'ko-KR': '처리',
        'lb-LU': 'Veraarbechtung',
        'nl-NL': 'Verwerken',
        'pt-PT': 'Em processamento',
        'ru-RU': 'Обработка',
        'sl-SI': 'Obravnavati',
        'sv-SE': 'Bearbetning',
        th: 'กำลังประมวลผล',
        uk: 'Обробка',
        'zh-CN': '处理中',
        'zh-TW': '正在處理'
    },
    'payment-failed': {
        'de-DE': 'Zahlung fehlgeschlagen',
        'en-US': 'Payment failed',
        'es-ES': 'Pago fallido',
        fr: 'Paiement échoué',
        it: 'Pagamento non riuscito',
        ja: '支払い失敗',
        'ro-RO': 'Plata esuata',
        ar: 'عملية الدفع فشلت',
        ca: 'El pagament ha fallat',
        'cs-CZ': 'Platba selhala',
        'da-DK': 'Betaling mislykkedes',
        el: 'Η πληρωμή απέτυχε',
        'hi-IN': 'भुगतान असफल हुआ',
        'ko-KR': '결제 실패',
        'lb-LU': 'Bezuelung gescheitert',
        'nl-NL': 'Betaling mislukt',
        'pt-PT': 'Pagamento falhou',
        'ru-RU': 'Платеж не прошел',
        'sl-SI': 'Plačilo ni uspelo',
        'sv-SE': 'Betalning misslyckades',
        th: 'การชำระเงินล้มเหลว',
        uk: 'Не вдалося здійснити платіж',
        'zh-CN': '支付失败',
        'zh-TW': '支付失敗'
    },
    'first-name': {
        'de-DE': 'Vorname',
        'en-US': 'First name',
        'es-ES': 'Nombre',
        fr: 'Prénom',
        it: 'Nome',
        ja: '名',
        'ro-RO': 'Nume',
        ar: 'الاسم الأول',
        ca: 'Nom',
        'cs-CZ': 'Jméno',
        'da-DK': 'Fornavn',
        el: 'Ονομα',
        'hi-IN': 'पहला नाम',
        'ko-KR': '이름',
        'lb-LU': 'Virnumm',
        'nl-NL': 'Voornaam',
        'pt-PT': 'Primeiro nome',
        'ru-RU': 'Имя',
        'sl-SI': 'Ime',
        'sv-SE': 'Förnamn',
        th: 'ชื่อจริง',
        uk: 'Ім\'я',
        'zh-CN': '名',
        'zh-TW': '名'
    },
    'last-name': {
        'de-DE': 'Nachname',
        'en-US': 'Last name',
        'es-ES': 'Apellido',
        fr: 'Nom',
        it: 'Cognome',
        ja: '姓',
        'ro-RO': 'Numele de familie',
        ar: 'الكنية',
        ca: 'Cognom',
        'cs-CZ': 'Příjmení',
        'da-DK': 'Efternavn',
        el: 'Επίθετο',
        'hi-IN': 'उपनाम',
        'ko-KR': '성',
        'lb-LU': 'Familljennumm',
        'nl-NL': 'Achternaam',
        'pt-PT': 'Último nome',
        'ru-RU': 'Фамилия',
        'sl-SI': 'Priimek',
        'sv-SE': 'Efternamn',
        th: 'นามสกุล',
        uk: 'Прізвище',
        'zh-CN': '姓',
        'zh-TW': '姓'
    },
    phone: {
        'de-DE': 'Telefon',
        'en-US': 'Phone number',
        'es-ES': 'Teléfono',
        fr: 'Téléphone',
        it: 'Telefono',
        ja: '電話番号',
        'ro-RO': 'Telefon',
        ar: 'رقم الهاتف',
        ca: 'Número de telèfon',
        'cs-CZ': 'Telefonní číslo',
        'da-DK': 'Telefonnummer',
        el: 'Τηλεφωνικό νούμερο',
        'hi-IN': 'फ़ोन नंबर',
        'ko-KR': '전화 번호',
        'lb-LU': 'Telefonsnummer',
        'nl-NL': 'Telefoonnummer',
        'pt-PT': 'Número de telefone',
        'ru-RU': 'Номер телефона',
        'sl-SI': 'Telefonska številka',
        'sv-SE': 'Telefonnummer',
        th: 'หมายเลขโทรศัพท์',
        uk: 'Телефонний номер',
        'zh-CN': '电话号码',
        'zh-TW': '電話號碼'
    },
    street: {
        'de-DE': 'Straße und Hausnummer',
        'en-US': 'Street address',
        'es-ES': 'Dirección',
        fr: 'Adresse',
        it: 'Indirizzo',
        ja: '住所詳細',
        'ro-RO': 'Adresă',
        ar: 'عنوان الشارع',
        ca: 'adreça',
        'cs-CZ': 'adresa ulice',
        'da-DK': 'Vejnavn',
        el: 'διεύθυνση',
        'hi-IN': 'गली का पता',
        'ko-KR': '주소',
        'lb-LU': 'Strooss Adress',
        'nl-NL': 'woonadres',
        'pt-PT': 'endereço da Rua',
        'ru-RU': 'адрес улицы',
        'sl-SI': 'naslov ceste',
        'sv-SE': 'Gatuadress',
        th: 'ที่อยู่ถนน',
        uk: 'Адреса вулиці',
        'zh-CN': '街道地址',
        'zh-TW': '街道地址'
    },
    apt: {
        'de-DE': 'Apartment #',
        'en-US': 'Apt. #',
        'es-ES': 'Apartamento #',
        fr: 'Appartement #',
        it: 'Appartamento #',
        ja: '部屋番号など',
        'ro-RO': 'Apartament #',
        ar: 'شقة #',
        ca: 'Apartament #',
        'cs-CZ': 'Byt #',
        'da-DK': 'Lejlighed #',
        el: 'Διαμέρισμα #',
        'hi-IN': 'अपार्टमेंट #',
        'ko-KR': '아파트 #',
        'lb-LU': 'Appartement #',
        'nl-NL': 'Appartement #',
        'pt-PT': 'Apartamento #',
        'ru-RU': 'Квартира #',
        'sl-SI': 'Stanovanje št.',
        'sv-SE': 'Lägenhet #',
        th: 'อพาร์ทเม้น #',
        uk: 'Квартира №',
        'zh-CN': '公寓 ＃',
        'zh-TW': '公寓 ＃'
    },
    postal: {
        'de-DE': 'Postleitzahl',
        'en-US': 'Postal code',
        'es-ES': 'Código Postal',
        fr: 'Code postal',
        it: 'Codice postale',
        ja: '郵便番号',
        'ro-RO': 'Cod postal',
        ar: 'الرمز البريدي',
        ca: 'Codi Postal',
        'cs-CZ': 'Poštovní směrovací číslo',
        'da-DK': 'Postnummer',
        el: 'Ταχυδρομικός Κώδικας',
        'hi-IN': 'डाक कोड',
        'ko-KR': '우편 번호',
        'lb-LU': 'Postleitzuel',
        'nl-NL': 'Postcode',
        'pt-PT': 'Código postal',
        'ru-RU': 'Почтовый Код',
        'sl-SI': 'Poštna številka',
        'sv-SE': 'Postnummer',
        th: 'รหัสไปรษณีย์',
        uk: 'Поштовий індекс',
        'zh-CN': '邮政编码',
        'zh-TW': '郵政編碼'
    },
    city: {
        'de-DE': 'Stadt',
        'en-US': 'City',
        'es-ES': 'Ciudad',
        fr: 'Ville',
        it: 'Citta',
        ja: '市',
        'ro-RO': 'Oraș',
        ar: 'مدينة',
        ca: 'ciutat',
        'cs-CZ': 'Město',
        'da-DK': 'By',
        el: 'Πόλη',
        'hi-IN': 'शहर',
        'ko-KR': '도시',
        'lb-LU': 'Stad',
        'nl-NL': 'Stad',
        'pt-PT': 'Cidade',
        'ru-RU': 'Город',
        'sl-SI': 'Mesto',
        'sv-SE': 'Stad',
        th: 'เมือง',
        uk: 'Місто',
        'zh-CN': '城市',
        'zh-TW': '城市'
    },
    province: {
        'de-DE': 'Provinz',
        'en-US': 'Province',
        'es-ES': 'Provincia',
        fr: 'Département',
        it: 'Provincia',
        ja: '県',
        'ro-RO': 'Judet',
        ar: 'مقاطعة',
        ca: 'Província',
        'cs-CZ': 'Provincie',
        'da-DK': 'Provins',
        el: 'Επαρχία',
        'hi-IN': 'प्रांत',
        'ko-KR': '주',
        'lb-LU': 'Provënz',
        'nl-NL': 'Provincie',
        'pt-PT': 'Província',
        'ru-RU': 'Провинция',
        'sl-SI': 'Pokrajina',
        'sv-SE': 'Provins',
        th: 'จังหวัด',
        uk: 'Провінція',
        'zh-CN': '省',
        'zh-TW': '省'
    },
    'Select a Province': {
        'de-DE': 'Wählen Sie eine Provinz',
        'en-US': 'Select a Province',
        'es-ES': 'Seleccione una provincia',
        fr: 'Sélectionnez une province',
        it: 'Seleziona una provincia',
        ja: '都道府県を選択',
        'ro-RO': 'Selectați o provincie',
        ar: 'اختر المحافظة',
        ca: 'Seleccioneu una província',
        'cs-CZ': 'Vyberte provincii',
        'da-DK': 'Vælg en provins',
        el: 'Επιλέξτε μια επαρχία',
        'hi-IN': 'एक प्रांत का चयन करें',
        'ko-KR': '주를 선택하십시오',
        'lb-LU': 'Wielt eng Provënz',
        'nl-NL': 'Selecteer een provincie',
        'pt-PT': 'Selecione uma província',
        'ru-RU': 'Выберите провинцию',
        'sl-SI': 'Izberite provinco',
        'sv-SE': 'Välj en provins',
        th: 'เลือกจังหวัด',
        uk: 'Виберіть провінцію',
        'zh-CN': '选择省份',
        'zh-TW': '選擇省份'
    },
    state: {
        'de-DE': 'Bundesland',
        'en-US': 'State',
        'es-ES': 'Estado',
        fr: 'État',
        it: 'Stato',
        ja: '県',
        'ro-RO': 'Stat',
        ar: 'ولاية',
        ca: 'Estat',
        'cs-CZ': 'Stát',
        'da-DK': 'Stat',
        el: 'κατάσταση',
        'hi-IN': 'राज्य',
        'ko-KR': '상태',
        'lb-LU': 'Staat',
        'nl-NL': 'Staat',
        'pt-PT': 'Estada',
        'ru-RU': 'Состояние',
        'sl-SI': 'Država',
        'sv-SE': 'stat',
        th: 'สถานะ',
        uk: 'Держава',
        'zh-CN': '州',
        'zh-TW': '州'
    },
    'Select a State': {
        'de-DE': 'Wähle einen Staat',
        'en-US': 'Select a State',
        'es-ES': 'Selecciona un Estado',
        fr: 'Sélectionner un état',
        it: 'Seleziona uno Stato',
        ja: '州を選択',
        'ro-RO': 'Selecteaza un stat',
        ar: 'حدد ولاية',
        ca: 'Seleccioneu un estat',
        'cs-CZ': 'Vyberte stát',
        'da-DK': 'Vælg en stat',
        el: 'Επιλέξτε μια πολιτεία',
        'hi-IN': 'एक राज्य का चयन करें',
        'ko-KR': '주 선택',
        'lb-LU': 'Wielt e Staat',
        'nl-NL': 'Selecteer een staat',
        'pt-PT': 'Selecione um Estado',
        'ru-RU': 'Выберите штат',
        'sl-SI': 'Izberite državo',
        'sv-SE': 'Välj en stat',
        th: 'เลือกรัฐ',
        uk: 'Виберіть штат',
        'zh-CN': '选择一个州',
        'zh-TW': '選擇一個州'
    },
    county: {
        'de-DE': 'Bezirk',
        'en-US': 'County',
        'es-ES': 'Condado',
        fr: 'Comté',
        it: 'Contea',
        ja: '郡',
        'ro-RO': 'Județul',
        ar: 'مقاطعة',
        ca: 'comtat',
        'cs-CZ': 'okres',
        'da-DK': 'Amt',
        el: 'Κομητεία',
        'hi-IN': 'काउंटी',
        'ko-KR': '군',
        'lb-LU': 'Grofschaft',
        'nl-NL': 'district',
        'pt-PT': 'condado',
        'ru-RU': 'округ',
        'sl-SI': 'Okrožje',
        'sv-SE': 'Grevskap',
        th: 'เขต',
        uk: 'Повіт',
        'zh-CN': '县',
        'zh-TW': '縣'
    },
    country: {
        'de-DE': 'Wählen Sie ein Land',
        'en-US': 'Select a country',
        'es-ES': 'Seleccione un país',
        fr: 'Pays',
        it: 'Seleziona un paese',
        ja: '国',
        'ro-RO': 'Selecteaza o tara',
        ar: 'اختر دولة',
        ca: 'Seleccioneu un país',
        'cs-CZ': 'Vyber zemi',
        'da-DK': 'Vælg et land',
        el: 'Επιλέξτε χώρα',
        'hi-IN': 'कोई देश चुनें',
        'ko-KR': '국가를 고르시 오',
        'lb-LU': 'Wielt e Land',
        'nl-NL': 'Selecteer een land',
        'pt-PT': 'Selecione um pais',
        'ru-RU': 'Выберите страну',
        'sl-SI': 'Izberite državo',
        'sv-SE': 'Välj ett land',
        th: 'เลือกประเทศ',
        uk: 'Виберіть країну',
        'zh-CN': '选择一个国家',
        'zh-TW': '選擇一個國家'
    },
    'country-label': {
        'de-DE': 'Land',
        'en-US': 'Country',
        'es-ES': 'País',
        fr: 'Pays',
        it: 'Nazione',
        ja: '国',
        'ro-RO': 'Țară',
        ar: 'دولة',
        ca: 'País',
        'cs-CZ': 'Země',
        el: 'Χώρα',
        'hi-IN': 'देश',
        'ko-KR': '국가',
        'lb-LU': 'Land',
        'nl-NL': 'Land',
        'pt-PT': 'País',
        'ru-RU': 'Страна',
        'sl-SI': 'Država',
        'sv-SE': 'Land',
        th: 'ประเทศ',
        uk: 'Країна',
        'zh-CN': '国家',
        'zh-TW': '國家'
    },
    'Order notes (optional):': {
        'de-DE': 'Bestellnotizen (optional):',
        'en-US': 'Order notes (optional):',
        'es-ES': 'Notas de pedido opcional:',
        fr: 'Notes de commande (facultatif):',
        it: 'Note dell\'ordine (opzionale):',
        ja: '注文メモ（オプション):',
        'ro-RO': 'Note de comandă (opțional):',
        ar: 'ملاحظات الطلب (اختياري):',
        ca: 'notes de comanda (opcional):',
        'cs-CZ': 'poznámky k objednávce (volitelné):',
        el: 'σημειώσεις παραγγελίας (προαιρετικό):',
        'hi-IN': 'आदेश नोट्स (वैकल्पिक):',
        'ko-KR': '주문 메모(선택 사항):',
        'lb-LU': 'Bestellungsnotizen (optional):',
        'nl-NL': 'bestelnotities (optioneel):',
        'pt-PT': 'notas do pedido (opcional):',
        'ru-RU': 'примечания к заказу (необязательно): ',
        'sl-SI': 'opombe o naročilu (neobvezno): ',
        'sv-SE': 'beställningsanteckningar (valfritt):',
        th: 'หมายเหตุการสั่งซื้อ (ไม่บังคับ):',
        uk: 'примітки до замовлення (необов\'язково)',
        'zh-CN': '订单备注（可选）:',
        'zh-TW': '訂單備註（可選）:"'
    },
    'Something went wrong, but the payment may have been made. Please check before placing another order.': {
        'de-DE': 'Es ist ein Fehler aufgetreten, aber die Zahlung wurde möglicherweise geleistet. Bitte überprüfen Sie dies, bevor Sie eine weitere Bestellung aufgeben.',
        'en-US': 'Something went wrong, but the payment may have been made. Please check before placing another order.',
        'es-ES': 'Se produjo un error, pero es posible que se haya realizado el pago. Verifique antes de realizar otro pedido.',
        fr: 'Une erreur s\'est produite, mais le paiement a peut-être été effectué. Veuillez vérifier avant de passer une autre commande.',
        it: 'Qualcosa è andato storto, ma il pagamento potrebbe essere stato effettuato. Si prega di controllare prima di effettuare un altro ordine.',
        ja: '何か問題が発生しましたが、支払いが行われた可能性があります。 別の注文をする前に確認してください。',
        'ro-RO': 'Ceva nu a funcționat corect, dar este posibil ca plata să fi fost efectuată. Vă rugăm să verificați înainte de a plasa o altă comandă.',
        ar: 'حدث خطأ ما ، ولكن ربما تم السداد. يرجى التحقق قبل تقديم طلب آخر.',
        ca: 'S\'ha produït un error, però és possible que s\'hagi efectuat el pagament. Comproveu-ho abans de fer una altra comanda.',
        'cs-CZ': 'Něco se pokazilo, ale platba možná byla provedena. Před zadáním další objednávky prosím zkontrolujte.',
        'da-DK': 'Noget gik galt, men betalingen kan være foretaget. Kontroller venligst, før du afgiver en anden ordre.',
        el: 'Κάτι πήγε στραβά, αλλά η πληρωμή μπορεί να έχει γίνει. Παρακαλώ ελέγξτε πριν κάνετε άλλη παραγγελία.',
        'hi-IN': 'कुछ गलत हुआ, लेकिन हो सकता है कि भुगतान हो गया हो. कृपया एक और आदेश देने से पहले जांच लें।',
        'ko-KR': '문제가 발생했지만 결제가 완료되었을 수 있습니다. 다른 주문을 하기 전에 확인하시기 바랍니다.',
        'lb-LU': 'Eppes ass falsch gaang, awer d\'Bezuelung ass vläicht gemaach ginn. Préift w.e.g. ier Dir eng aner Bestellung plazéiert.',
        'nl-NL': 'Er is iets misgegaan, maar de betaling kan zijn gedaan. Controleer dit voordat u een nieuwe bestelling plaatst.',
        'pt-PT': 'Algo deu errado, mas o pagamento pode ter sido feito. Por favor, verifique antes de fazer outro pedido.',
        'ru-RU': 'Что-то пошло не так, но оплата могла быть произведена. Пожалуйста, проверьте перед размещением другого заказа.',
        'sl-SI': 'Nekaj je šlo narobe, morda pa je bilo plačilo izvedeno. Preden oddate novo naročilo, preverite.',
        'sv-SE': 'Något gick fel, men betalningen kan ha gjorts. Kontrollera innan du gör en annan beställning.',
        th: 'มีบางอย่างผิดพลาด แต่อาจมีการชำระเงินแล้ว โปรดตรวจสอบก่อนทำการสั่งซื้ออื่น',
        uk: 'Щось пішло не так, але оплата, можливо, була здійснена. Будь ласка, перевірте, перш ніж робити інше замовлення.',
        'zh-CN': '出了点问题，但可能已付款。 请在下另一个订单之前检查。',
        'zh-TW': '出了點問題，但可能已付款。 請在下另一個訂單之前檢查。'
    },
    'Something went wrong, but don\'t worry. We have your order details, and your payment has been made. There is no need to place another order.': {
        'de-DE': 'Etwas ist schief gelaufen, aber keine Sorge. Wir haben Ihre Bestelldaten und Ihre Zahlung ist erfolgt. Eine weitere Bestellung ist nicht erforderlich.',
        'en-US': 'Something went wrong, but don\'t worry. We have your order details, and your payment has been made. There is no need to place another order.',
        'es-ES': 'Algo salió mal, pero no se preocupe. Tenemos los detalles de su pedido y su pago se ha realizado. No es necesario realizar otro pedido.',
        fr: 'Quelque chose s\'est mal passé, mais ne vous inquiétez pas. Nous avons les détails de votre commande et votre paiement a été effectué. Il n\'est pas nécessaire de passer une autre commande.',
        it: 'Qualcosa è andato storto, ma non preoccuparti. Abbiamo i dettagli del tuo ordine e il pagamento è stato effettuato. Non è necessario effettuare un altro ordine.',
        ja: '何か問題が発生しましたが、心配しないでください。 ご注文の詳細があり、お支払いが完了しました。 別の注文をする必要はありません。',
        'ro-RO': 'Ceva a mers prost, dar nu vă faceți griji. Avem detaliile comenzii dvs. și plata dvs. a fost efectuată. Nu este nevoie să plasați o altă comandă.',
        ar: 'حدث خطأ ما ، لكن لا تقلق. لدينا تفاصيل طلبك ، وقد تم سداد دفعتك. ليست هناك حاجة لتقديم طلب آخر.',
        ca: 'S\'ha produït un error, però no us preocupeu. Tenim les dades de la vostra comanda i s’ha efectuat el pagament. No cal fer una altra comanda.',
        'cs-CZ': 'Něco se pokazilo, ale nebojte se. Máme podrobnosti o vaší objednávce a vaše platba byla provedena. Není třeba zadávat další objednávku.',
        'da-DK': 'Noget gik galt, men bare rolig. Vi har dine ordreoplysninger, og din betaling er foretaget. Det er ikke nødvendigt at afgive en anden ordre.',
        el: 'Κάτι πήγε στραβά, αλλά μην ανησυχείτε. Έχουμε τα στοιχεία της παραγγελίας σας και η πληρωμή σας έχει πραγματοποιηθεί. Δεν χρειάζεται να κάνετε άλλη παραγγελία.',
        'hi-IN': 'कुछ गलत हो गया, लेकिन चिंता न करें। हमारे पास आपके आदेश का विवरण है, और आपका भुगतान कर दिया गया है। दूसरा आदेश देने की कोई आवश्यकता नहीं है।',
        'ko-KR': '문제가 발생했지만 걱정하지 마세요. 주문 세부정보가 있으며 결제가 완료되었습니다. 다른 주문을 할 필요가 없습니다.',
        'lb-LU': 'Eppes ass falsch gaang, awer maach der keng Suergen. Mir hunn Är Bestellungsdetailer, an Är Bezuelung gouf gemaach. Et ass net néideg eng aner Bestellung ze maachen.',
        'nl-NL': 'Er is iets misgegaan, maar maak je geen zorgen. We hebben uw bestelgegevens en uw betaling is gedaan. Het is niet nodig om nog een bestelling te plaatsen.',
        'pt-PT': 'Algo deu errado, mas não se preocupe. Temos os detalhes do seu pedido e seu pagamento foi efetuado. Não há necessidade de fazer outro pedido.',
        'ru-RU': 'Что-то пошло не так, но не волнуйтесь. У нас есть данные о вашем заказе, и ваш платеж был произведен. Очередной заказ делать не нужно.',
        'sl-SI': 'Nekaj je šlo narobe, vendar ne skrbite. Podatke o naročilu imamo in plačilo je bilo opravljeno. Drugega naročila ni treba oddati.',
        'sv-SE': 'Något gick fel, men oroa dig inte. Vi har dina beställningsuppgifter och din betalning har gjorts. Det finns ingen anledning att göra en annan beställning.',
        th: 'มีบางอย่างผิดพลาด แต่ไม่ต้องกังวล เรามีรายละเอียดการสั่งซื้อของคุณและชำระเงินเรียบร้อยแล้ว ไม่จำเป็นต้องทำการสั่งซื้ออื่น',
        uk: 'Щось пішло не так, але не хвилюйтесь. У нас є дані вашого замовлення, і ваш платіж здійснено. Немає необхідності робити інше замовлення.',
        'zh-CN': '出了点问题，但别担心。 我们有您的订单详细信息，您的付款已完成。 无需再下订单。',
        'zh-TW': '出了點問題，但別擔心。 我們有您的訂單詳細信息，您的付款已完成。 無需再下訂單。'
    },
    'Delivery date': {
        'de-DE': 'Liefertermin',
        'en-US': 'Delivery date',
        'es-ES': 'Fecha de entrega',
        fr: 'Date de livraison',
        it: 'Data di consegna',
        ja: '配送日',
        'ro-RO': 'Data livrării',
        ar: 'تاريخ التسليم او الوصول',
        ca: 'Data de lliurament',
        'cs-CZ': 'Datum doručení',
        'da-DK': 'Leveringsdato',
        el: 'Ημερομηνία παράδοσης',
        'hi-IN': 'डिलीवरी की तारीख',
        'ko-KR': '배송 날짜',
        'lb-LU': 'Liwwerungsdatum',
        'nl-NL': 'Bezorgdatum',
        'pt-PT': 'Data de entrega',
        'ru-RU': 'Дата доставки',
        'sl-SI': 'Datum dostave',
        'sv-SE': 'Leveransdatum',
        th: 'วันที่จัดส่ง',
        uk: 'Дата доставки',
        'zh-CN': '邮寄日期',
        'zh-TW': '郵寄日期'
    },
    'First renewal': {
        'de-DE': 'Erste Verlängerung',
        'en-US': 'First renewal',
        'es-ES': 'Primera renovación',
        fr: 'Premier renouvellement',
        it: 'Primo Rinnovo',
        ja: '最初の更新',
        'ro-RO': 'Prima reînnoire',
        ar: 'التجديد الأول',
        ca: 'Primera renovació',
        'cs-CZ': 'První obnova',
        'da-DK': 'Første fornyelse',
        el: 'Πρώτη ανανέωση',
        'hi-IN': 'पहला नवीनीकरण',
        'ko-KR': '첫 번째 갱신',
        'lb-LU': 'Éischt Erneierung',
        'nl-NL': 'Eerste verlenging',
        'pt-PT': 'Primeira renovação',
        'ru-RU': 'Первое обновление',
        'sl-SI': 'Prva obnova',
        'sv-SE': 'Första förnyelsen',
        th: 'ต่ออายุครั้งแรก',
        uk: 'Перше оновлення',
        'zh-CN': '第一次续订',
        'zh-TW': '第一次續訂'
    },
    'Recurring total': {
        'de-DE': 'Wiederkehrende Summe',
        'en-US': 'Recurring total',
        'es-ES': 'Total recurrente',
        fr: 'Total récurrent',
        it: 'Totale ricorrente',
        ja: '定期合計',
        'ro-RO': 'Total recurent',
        ar: 'المجموع المتكرر',
        ca: 'Total recurrent',
        'cs-CZ': 'Opakující se celkem',
        'da-DK': 'Tilbagevendende total',
        el: 'Επαναλαμβανόμενο σύνολο',
        'hi-IN': 'आवर्ती कुल',
        'ko-KR': '반복 합계',
        'lb-LU': 'Widderhuelend Total',
        'nl-NL': 'Terugkerend totaal',
        'pt-PT': 'Total recorrente',
        'ru-RU': 'Повторяющаяся сумма',
        'sl-SI': 'Ponavljajoče se skupaj',
        'sv-SE': 'Återkommande totalt',
        th: 'ยอดรวมที่เกิดซ้ำ',
        uk: 'Повторювана сума',
        'zh-CN': '经常性总计',
        'zh-TW': '經常性總計'
    },
    'initial-summary': {
        'de-DE': 'Anfangssumme',
        'en-US': 'Initial total',
        'es-ES': 'Total inicial',
        fr: 'Total initial',
        it: 'Totale iniziale',
        ja: '初期合計',
        'ro-RO': 'Total inițial',
        ar: 'المجموع الأولي',
        ca: 'Total inicial',
        'cs-CZ': 'Počáteční celkem',
        'da-DK': 'Indledende total',
        el: 'Αρχικό σύνολο',
        'hi-IN': 'प्रारंभिक कुल',
        'ko-KR': '초기 합계',
        'lb-LU': 'Ufanks total',
        'nl-NL': 'Initieel totaal',
        'pt-PT': 'Total inicial',
        'ru-RU': 'Исходная сумма',
        'sl-SI': 'Začetni seštevek',
        'sv-SE': 'Initial summa',
        th: 'ยอดรวมเริ่มต้น',
        uk: 'Початкова сума',
        'zh-CN': '初始总数',
        'zh-TW': '初始總數'
    },
    'recurring-shipping': {
        'de-DE': 'Wiederkehrender Versand',
        'en-US': 'Recurring shipping',
        'es-ES': 'Envíos recurrentes',
        fr: 'Expédition récurrente',
        it: 'Totale iniziale',
        ja: '定期配送',
        'ro-RO': 'Total inițial',
        ar: 'الشحن المتكرر',
        ca: 'Enviament periòdic',
        'cs-CZ': 'Opakovaná doprava',
        'da-DK': 'Tilbagevendende forsendelse',
        el: 'Επαναλαμβανόμενη αποστολή',
        'hi-IN': 'आवर्ती शिपिंग',
        'ko-KR': '반복 배송',
        'lb-LU': 'Widderhuelend Versand',
        'nl-NL': 'Terugkerende verzending',
        'pt-PT': 'Remessa recorrente',
        'ru-RU': 'Периодическая доставка',
        'sl-SI': 'Ponavljajoča se dostava',
        'sv-SE': 'Återkommande frakt',
        th: 'ส่งสินค้าประจำ',
        uk: 'Повторна доставка',
        'zh-CN': '经常性运输',
        'zh-TW': '經常性運輸'
    },
    'initial-shipping': {
        'de-DE': 'Erster Versand',
        'en-US': 'Initial shipping',
        'es-ES': 'Envío inicial',
        fr: 'Expédition initiale',
        it: 'Spedizione iniziale',
        ja: '初期発送',
        'ro-RO': 'Expediere inițială',
        ar: 'الشحن الأولي',
        ca: 'Enviament inicial',
        'cs-CZ': 'Počáteční odeslání',
        'da-DK': 'Første forsendelse',
        el: 'Αρχική αποστολή',
        'hi-IN': 'प्रारंभिक शिपिंग',
        'ko-KR': '초기 배송',
        'lb-LU': 'Ufanks Versand',
        'nl-NL': 'Eerste verzending',
        'pt-PT': 'Envio inicial',
        'ru-RU': 'Первоначальная доставка',
        'sl-SI': 'Začetna dostava',
        'sv-SE': 'Första frakten',
        th: 'การจัดส่งสินค้าเบื้องต้น',
        uk: 'Початкова доставка',
        'zh-CN': '初始运输',
        'zh-TW': '初始運輸'
    },
    'Create a new password, or use an existing one if you already have an account for': {
        'de-DE': 'Erstellen Sie ein neues Passwort oder verwenden Sie ein bestehendes, wenn Sie bereits ein Konto für . haben',
        'en-US': 'Create a new password, or use an existing one if you already have an account for',
        'es-ES': 'Cree una nueva contraseña o use una existente si ya tiene una cuenta. tener',
        fr: 'Créez un nouveau mot de passe ou utilisez-en un existant si vous avez déjà un compte pour',
        it: 'Crea una nuova password o usane una esistente se hai già un account per',
        ja: '新しいパスワードを作成するか、すでにアカウントをお持ちの場合は既存のパスワードを使用してください',
        'ro-RO': 'Creați o parolă nouă sau utilizați una existentă dacă aveți deja un cont pentru',
        ar: 'أنشئ كلمة مرور جديدة ، أو استخدم كلمة مرور موجودة إذا كان لديك بالفعل حساب لـ',
        ca: 'Creeu una contrasenya nova o utilitzeu-ne una si ja teniu un compte',
        'cs-CZ': 'Vytvořte nové heslo nebo použijte stávající, pokud již máte účet',
        'da-DK': 'Opret en ny adgangskode, eller brug en eksisterende, hvis du allerede har en konto til',
        el: 'Δημιουργήστε έναν νέο κωδικό πρόσβασης ή χρησιμοποιήστε έναν υπάρχοντα, εάν έχετε ήδη λογαριασμό',
        'hi-IN': 'एक नया पासवर्ड बनाएं, या किसी मौजूदा पासवर्ड का उपयोग करें यदि आपके पास पहले से ही एक खाता है',
        'ko-KR': '새 비밀번호를 생성하거나 이미 계정이 있는 경우 기존 비밀번호를 사용하십시오.',
        'lb-LU': 'Erstellt en neit Passwuert, oder benotzt en existent Passwuert wann Dir schonn e Kont hutt',
        'nl-NL': 'Maak een nieuw wachtwoord aan, of gebruik een bestaand wachtwoord als je al een account hebt voor',
        'pt-PT': 'Crie uma nova senha ou use uma existente se você já tiver uma conta para',
        'ru-RU': 'Создайте новый пароль или используйте существующий, если у вас уже есть учетная запись для',
        'sl-SI': 'Ustvarite novo geslo ali uporabite obstoječe, če že imate račun',
        'sv-SE': 'Skapa ett nytt lösenord, eller använd ett befintligt om du redan har ett konto för',
        th: 'สร้างรหัสผ่านใหม่หรือใช้รหัสผ่านที่มีอยู่ถ้าคุณมีบัญชีสำหรับ',
        uk: 'Створіть новий пароль або використовуйте існуючий, якщо у вас вже є обліковий запис',
        'zh-CN': '创建一个新密码，如果您已经有一个帐户，则使用现有的密码',
        'zh-TW': '創建一個新密碼，如果您已經有一個帳戶，則使用現有的密碼'
    },
    'Password': {
        'de-DE': 'Passwort',
        'en-US': 'Password',
        'es-ES': 'Clave',
        fr: 'Mot de passe',
        it: 'Parola d\'ordine',
        ja: 'パスワード',
        'ro-RO': 'Parola',
        ar: 'كلمه السر',
        ca: 'Contrasenya',
        'cs-CZ': 'Heslo',
        'da-DK': 'Adgangskode',
        el: 'Κωδικός πρόσβασης',
        'hi-IN': 'पासवर्ड',
        'ko-KR': '비밀번호',
        'lb-LU': 'Passwuert',
        'nl-NL': 'Wachtwoord',
        'pt-PT': 'Senha',
        'ru-RU': 'Пароль',
        'sl-SI': 'Geslo',
        'sv-SE': 'Lösenord',
        th: 'รหัสผ่าน',
        uk: 'Пароль',
        'zh-CN': '密码',
        'zh-TW': '密碼'
    },
    'The password entered must be at least 8 characters long.': {
        'de-DE': 'Das eingegebene Passwort muss mindestens 8 Zeichen lang sein.',
        'en-US': 'The password entered must be at least 8 characters long.',
        'es-ES': 'La contraseña ingresada debe tener al menos 8 caracteres.',
        fr: 'Le mot de passe saisi doit comporter au moins 8 caractères.',
        it: 'La password inserita deve essere lunga almeno 8 caratteri.',
        ja: '入力するパスワードは8文字以上である必要があります。',
        'ro-RO': 'Parola introdusă trebuie să aibă cel puțin 8 caractere.',
        ar: 'يجب أن تتكون كلمة المرور المدخلة من 8 أحرف على الأقل.',
        ca: 'La contrasenya introduïda ha de tenir com a mínim 8 caràcters.',
        'cs-CZ': 'Zadané heslo musí mít alespoň 8 znaků.',
        'da-DK': 'Den indtastede adgangskode skal være mindst 8 tegn lang.',
        el: 'Ο κωδικός πρόσβασης που έχει εισαχθεί πρέπει να έχει τουλάχιστον 8 χαρακτήρες.',
        'hi-IN': 'दर्ज किया गया पासवर्ड कम से कम 8 वर्ण लंबा होना चाहिए।',
        'ko-KR': '입력한 비밀번호는 8자 이상이어야 합니다.',
        'lb-LU': 'D\'Passwuert dat aginn ass muss op d\'mannst 8 Zeeche laang sinn.',
        'nl-NL': 'Het ingevoerde wachtwoord moet minimaal 8 tekens lang zijn.',
        'pt-PT': 'A senha inserida deve ter pelo menos 8 caracteres.',
        'ru-RU': 'Введенный пароль должен состоять не менее чем из 8 символов.',
        'sl-SI': 'Vneseno geslo mora biti dolgo najmanj 8 znakov.',
        'sv-SE': 'Lösenordet måste vara minst 8 tecken långt.',
        th: 'รหัสผ่านที่ป้อนต้องมีความยาวอย่างน้อย 8 ตัวอักษร',
        uk: 'Введений пароль повинен містити не менше 8 символів.',
        'zh-CN': '输入的密码长度必须至少为 8 个字符。',
        'zh-TW': '輸入的密碼長度必須至少為 8 個字符。'
    },
    unknown: {
        'de-DE': 'Unbekannt',
        'en-US': 'Unknown',
        'es-ES': 'Desconocido',
        fr: 'Inconnu',
        it: 'Sconosciuto',
        ja: '不明',
        'ro-RO': 'Necunoscut',
        ar: 'مجهول',
        ca: 'Desconegut',
        'cs-CZ': 'Neznámý',
        'da-DK': 'Ukendt',
        el: 'Αγνωστος',
        'hi-IN': 'अनजान',
        'ko-KR': '알려지지 않은',
        'lb-LU': 'Onbekannt',
        'nl-NL': 'Onbekend',
        'pt-PT': 'Desconhecida',
        'ru-RU': 'Неизвестный',
        'sl-SI': 'Neznano',
        'sv-SE': 'Okänd',
        th: 'ไม่รู้จัก',
        uk: 'Невідомий',
        'zh-CN': '未知',
        'zh-TW': '未知'
    },
    'Test mode: customers cannot see PeachPay': {
        'de-DE': 'Testmodus: Kunden können PeachPay nicht sehen',
        'en-US': 'Test mode: customers cannot see PeachPay',
        'es-ES': 'Modo de prueba: los clientes no pueden ver PeachPay',
        fr: 'Mode test : les clients ne peuvent pas voir PeachPay',
        it: 'Modalità test: i clienti non possono vedere PeachPay',
        ja: 'テストモード：顧客はPeachPayを見ることができません',
        'ro-RO': 'Mod de testare: clienții nu pot vedea PeachPay',
        ar: 'وضع الاختبار: لا يمكن للعملاء رؤية PeachPay',
        ca: 'Mode de prova: els clients no poden veure PeachPay',
        'cs-CZ': 'Testovací režim: zákazníci nevidí PeachPay',
        'da-DK': 'Testtilstand: kunder kan ikke se PeachPay',
        el: 'Λειτουργία δοκιμής: οι πελάτες δεν μπορούν να δουν το PeachPay',
        'hi-IN': 'परीक्षण मोड: ग्राहक पीचपे नहीं देख सकते हैं',
        'ko-KR': '테스트 모드: 고객이 PeachPay를 볼 수 없습니다.',
        'lb-LU': 'Testmodus: Clienten kënnen PeachPay net gesinn',
        'nl-NL': 'Testmodus: klanten kunnen PeachPay niet zien',
        'pt-PT': 'Modo de teste: os clientes não podem ver o PeachPay',
        'ru-RU': 'Тестовый режим: клиенты не видят PeachPay',
        'sl-SI': 'Testni način: stranke ne vidijo PeachPay',
        'sv-SE': 'Testläge: kunder kan inte se PeachPay',
        th: 'โหมดทดสอบ: ลูกค้าไม่สามารถเห็น PeachPay',
        uk: 'Тестовий режим: клієнти не можуть бачити PeachPay',
        'zh-CN': '测试模式：客户看不到PeachPay',
        'zh-TW': '測試模式：客戶看不到PeachPay'
    },
    'I verify that the country I have entered is the one I reside in': {
        'de-DE': 'Ich bestätige, dass das Land, in dem ich eingereist bin, das Land ist, in dem ich wohne',
        'en-US': 'I verify that the country I have entered is the one I reside in',
        'es-ES': 'Verifico que el país al que he entrado es en el que resido',
        fr: 'Je vérifie que le pays dans lequel je suis entré est celui dans lequel je réside',
        it: 'Verifico che il paese in cui sono entrato sia quello in cui risiedo',
        ja: '入力した国が居住国であることを確認します',
        'ro-RO': 'Verific că țara în care am intrat este cea în care locuiesc',
        ar: 'أتحقق من أن البلد الذي أدخلته هو البلد الذي أقيم فيه',
        ca: 'Verifico que el país on he entrat és el on visc',
        'cs-CZ': 'Ověřuji, že země, do které jsem zadal, je zemí, ve které bydlím',
        'da-DK': 'Jeg bekræfter, at det land, jeg har indtastet, er det, jeg bor i',
        el: 'Επαληθεύω ότι η χώρα στην οποία έχω εισέλθει είναι αυτή στην οποία διαμένω',
        'hi-IN': 'मैं सत्यापित करता/करती हूं कि जिस देश में मैंने प्रवेश किया है वह वही देश है जिसमें मैं रहता हूं',
        'ko-KR': '내가 입력한 국가가 내가 거주하는 국가인지 확인합니다.',
        'lb-LU': 'Ech verifizéieren datt d\'Land wou ech aginn hunn ass deen an deem ech wunnen',
        'nl-NL': 'Ik verifieer dat het land dat ik heb ingevoerd het land is waarin ik woon',
        'pt-PT': 'Eu verifico se o país que eu inseri é aquele em que resido',
        'ru-RU': 'Я подтверждаю, что страна, в которую я въехал, является той, в которой я проживаю',
        'si-SI': 'Potrjujem, da je država, v katero sem vstopil, tista, v kateri prebivam',
        'si-SE': 'Jag verifierar att det land jag har angett är det jag bor i',
        th: 'ฉันยืนยันว่าประเทศที่ฉันเข้ามาเป็นประเทศที่ฉันอาศัยอยู่',
        uk: 'Я підтверджую, що країна, в яку я ввійшов, є тією, в якій я проживаю',
        'zh-CN': '我确认我进入的国家是我居住的国家',
        'zh-TW': '我確認我進入的國家是我居住的國家'
    }
};
var Feature = {
    enabled: function (flag) { var _a, _b; return (_b = (_a = store.getState().environment.plugin.featureSupport[flag]) === null || _a === void 0 ? void 0 : _a.enabled) !== null && _b !== void 0 ? _b : false; },
    version: function (flag) { var _a, _b; return (_b = (_a = store.getState().environment.plugin.featureSupport[flag]) === null || _a === void 0 ? void 0 : _a.version) !== null && _b !== void 0 ? _b : 0; },
    metadata: function (flag, key) { var _a, _b, _c; return (_c = (_b = (_a = store.getState().environment.plugin.featureSupport[flag]) === null || _a === void 0 ? void 0 : _a.metadata) === null || _b === void 0 ? void 0 : _b[key]) !== null && _c !== void 0 ? _c : null; }
};
var FeatureFlag;
(function (FeatureFlag1) {
    FeatureFlag1["COUPON_INPUT"] = 'coupon_input';
    FeatureFlag1["GIFTCARD_INPUT"] = 'giftcard_input';
    FeatureFlag1["ORDER_NOTES_INPUT"] = 'order_notes_input';
    FeatureFlag1["VIRTUAL_PRODUCT_FIELDS"] = 'enable_virtual_product_fields';
    FeatureFlag1["ADDITIONAL_FIELDS"] = 'additional_fields';
    FeatureFlag1["STRIPE"] = 'stripe_payment_method';
    FeatureFlag1["STRIPE_PAYMENT_REQUEST"] = 'stripe_payment_request';
    FeatureFlag1["PAYPAL"] = 'paypal_payment_method';
    FeatureFlag1["QUANTITY_CHANGER"] = 'quantity_changer';
    FeatureFlag1["CURRENCY_SWITCHER_INPUT"] = 'currency_switcher_input';
    FeatureFlag1["RELATED_PRODUCTS"] = 'related_products';
})(FeatureFlag || (FeatureFlag = {}));
var setPaymentMethod = createCustomDispatchUpdate(DispatchActionType.PAYMENT_SET_METHOD, function (input) {
    if (input.index) {
        return input.provider + ':' + input.method + ':' + input.index;
    }
    else {
        return input.provider + ':' + input.method;
    }
});
var registerPaymentProvider = createDispatchUpdate(DispatchActionType.PAYMENT_REGISTER_PROVIDER);
var updateCustomerStripeId = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_STRIPE_ID);
var updateCustomer = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER);
var updateCustomerShippingShortAddress = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_SHIPPING);
var removeSavedPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_REMOVE_PAYMENT_METHOD);
var updateCustomerPreferredPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_SET_PREFERRED_PAYMENT_METHOD);
var addSavedPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_ADD_PAYMENT_METHOD);
var PeachPayCustomer = {
    data: function () { return store.getState().peachPayCustomer; },
    email: function () { return store.getState().peachPayCustomer.email; },
    firstName: function () { return store.getState().peachPayCustomer.name_first; },
    lastName: function () { return store.getState().peachPayCustomer.name_last; },
    phone: function () { return store.getState().peachPayCustomer.phone; },
    address1: function () { return store.getState().peachPayCustomer.address1; },
    address2: function () { return store.getState().peachPayCustomer.address2; },
    city: function () { return store.getState().peachPayCustomer.city; },
    state: function () { return store.getState().peachPayCustomer.state; },
    country: function () { return store.getState().peachPayCustomer.country; },
    postal: function () { return store.getState().peachPayCustomer.postal; },
    stripeId: function () { var _a; return (_a = store.getState().peachPayCustomer.stripe_customer_id) !== null && _a !== void 0 ? _a : ''; },
    stripeBillingDetails: function () { return ({
        name: PeachPayCustomer.firstName() + ' ' + PeachPayCustomer.lastName(),
        email: PeachPayCustomer.email(),
        phone: PeachPayCustomer.phone(),
        address: {
            city: PeachPayCustomer.city(),
            country: PeachPayCustomer.country(),
            line1: PeachPayCustomer.address1(),
            line2: PeachPayCustomer.address2(),
            postal_code: PeachPayCustomer.postal(),
            state: PeachPayCustomer.state()
        }
    }); },
    stripeShippingDetails: function () { return ({
        name: PeachPayCustomer.firstName() + ' ' + PeachPayCustomer.lastName(),
        phone: PeachPayCustomer.phone(),
        address: {
            city: PeachPayCustomer.city(),
            country: PeachPayCustomer.country(),
            line1: PeachPayCustomer.address1(),
            line2: PeachPayCustomer.address2(),
            postal_code: PeachPayCustomer.postal(),
            state: PeachPayCustomer.state()
        }
    }); },
    shortAddress: function () { return ({
        country: PeachPayCustomer.country(),
        state: PeachPayCustomer.state(),
        city: PeachPayCustomer.city(),
        postcode: PeachPayCustomer.postal()
    }); },
    wcShippingAddress: function () { return ({
        shipping_first_name: PeachPayCustomer.firstName(),
        shipping_last_name: PeachPayCustomer.lastName(),
        shipping_company: '',
        shipping_country: PeachPayCustomer.country(),
        shipping_address_1: PeachPayCustomer.address1(),
        shipping_address_2: PeachPayCustomer.address2(),
        shipping_city: PeachPayCustomer.city(),
        shipping_state: PeachPayCustomer.state(),
        shipping_postcode: PeachPayCustomer.postal(),
        shipping_phone: PeachPayCustomer.phone()
    }); },
    wcBillingAddress: function () { return ({
        billing_first_name: PeachPayCustomer.firstName(),
        billing_last_name: PeachPayCustomer.lastName(),
        billing_company: '',
        billing_email: PeachPayCustomer.email(),
        billing_phone: PeachPayCustomer.phone(),
        billing_country: PeachPayCustomer.country(),
        billing_address_1: PeachPayCustomer.address1(),
        billing_address_2: PeachPayCustomer.address2(),
        billing_city: PeachPayCustomer.city(),
        billing_state: PeachPayCustomer.state(),
        billing_postcode: PeachPayCustomer.postal()
    }); },
    retrieveSavedPaymentMethod: function (providerKey, methodKey, savedIndex) {
        var _a, _b, _c, _d, _e, _f, _g;
        var savedKey = providerKey + ':' + methodKey;
        var savedData = (_c = (_b = (_a = store.getState().peachPayCustomer.payment_methods) === null || _a === void 0 ? void 0 : _a[savedKey]) === null || _b === void 0 ? void 0 : _b[Number.parseInt(savedIndex !== null && savedIndex !== void 0 ? savedIndex : '-1')]) !== null && _c !== void 0 ? _c : null;
        var methodData = (_g = (_f = (_e = (_d = store.getState().paymentConfiguration.providers) === null || _d === void 0 ? void 0 : _d[providerKey]) === null || _e === void 0 ? void 0 : _e.methods) === null || _f === void 0 ? void 0 : _f[methodKey]) !== null && _g !== void 0 ? _g : null;
        return [
            savedData,
            methodData
        ];
    },
    retrieveSavedPaymentMethods: function (providerKey, methodKey) {
        var _a, _b, _c, _d, _e, _f;
        var savedKey = providerKey + ':' + methodKey;
        var savedData = (_b = (_a = store.getState().peachPayCustomer.payment_methods) === null || _a === void 0 ? void 0 : _a[savedKey]) !== null && _b !== void 0 ? _b : null;
        var methodData = (_f = (_e = (_d = (_c = store.getState().paymentConfiguration.providers) === null || _c === void 0 ? void 0 : _c[providerKey]) === null || _d === void 0 ? void 0 : _d.methods) === null || _e === void 0 ? void 0 : _e[methodKey]) !== null && _f !== void 0 ? _f : null;
        return [
            savedData,
            methodData
        ];
    },
    preferredPaymentMethod: function () { var _a; return (_a = store.getState().peachPayCustomer.preferred_payment_method) !== null && _a !== void 0 ? _a : null; }
};
var PaymentConfiguration = {
    data: function () { return store.getState().paymentConfiguration; },
    selectedMethod: function () { return store.getState().paymentConfiguration.selectedPaymentMethod; },
    selectedProvider: function () { var _a, _b; return (_b = (_a = store.getState().paymentConfiguration.selectedPaymentMethod.split(':')) === null || _a === void 0 ? void 0 : _a[0]) !== null && _b !== void 0 ? _b : ''; },
    selectedProviderMethod: function () { var _a, _b; return (_b = (_a = store.getState().paymentConfiguration.selectedPaymentMethod.split(':')) === null || _a === void 0 ? void 0 : _a[1]) !== null && _b !== void 0 ? _b : ''; },
    selectedProviderMethodIndex: function () { var _a, _b; return (_b = (_a = store.getState().paymentConfiguration.selectedPaymentMethod.split(':')) === null || _a === void 0 ? void 0 : _a[2]) !== null && _b !== void 0 ? _b : ''; },
    isProviderAndMethodSelected: function (provider, method, index) {
        if (index === void 0) { index = ''; }
        var selected = store.getState().paymentConfiguration.selectedPaymentMethod;
        if (!selected) {
            return false;
        }
        var selectedArray = selected.split(':');
        if (index) {
            return selectedArray[0] === provider && selectedArray[1] === method && selectedArray[2] === index;
        }
        return selectedArray[0] === provider && selectedArray[1] === method;
    },
    eligibleMethod: function (providerKey, methodKey, index) {
        var _a, _b;
        var provider = store.getState().paymentConfiguration.providers[providerKey];
        if (!provider) {
            return false;
        }
        var method = provider.methods[methodKey];
        if (!method) {
            return false;
        }
        var saved = PeachPayCustomer.retrieveSavedPaymentMethod(providerKey, methodKey, index)[0];
        if (!saved && index) {
            return false;
        }
        var currencyCode = MerchantConfiguration.currency.code();
        if (!method.supports.currencies.includes(currencyCode) && !method.supports.currencies.includes('ALL')) {
            return false;
        }
        var customerCountryCode = PeachPayCustomer.country();
        if (!method.supports.customerCountries.includes(customerCountryCode) && !method.supports.customerCountries.includes('ALL')) {
            return false;
        }
        var merchantCountryCode = (_b = (_a = MerchantConfiguration.general.wcLocationInfoData()) === null || _a === void 0 ? void 0 : _a.store_country) !== null && _b !== void 0 ? _b : 'US';
        if (!method.supports.merchantCountries.includes(merchantCountryCode) && !method.supports.merchantCountries.includes('ALL')) {
            return false;
        }
        if (Carts.subscriptionPresent()) {
            if (!method.supports.productTypes.includes('subscriptions')) {
                return false;
            }
        }
        return true;
    },
    eligibleMethodCount: function () {
        var count = 0;
        var data = PaymentConfiguration.data();
        for (var providerKey in data.providers) {
            var provider = data.providers[providerKey];
            if (!provider) {
                continue;
            }
            for (var methodKey in provider.methods) {
                var method = provider.methods[methodKey];
                if (!method) {
                    continue;
                }
                if (PaymentConfiguration.eligibleMethod(providerKey, methodKey)) {
                    count++;
                }
            }
        }
        return count;
    },
    firstEligibleMethod: function () {
        var data = PaymentConfiguration.data();
        for (var providerKey in data.providers) {
            var provider = data.providers[providerKey];
            if (!provider) {
                continue;
            }
            for (var methodKey in provider.methods) {
                var method = provider.methods[methodKey];
                if (!method) {
                    continue;
                }
                if (PaymentConfiguration.eligibleMethod(providerKey, methodKey)) {
                    var saved = PeachPayCustomer.retrieveSavedPaymentMethods(providerKey, methodKey)[0];
                    if (saved === null || saved === void 0 ? void 0 : saved.length) {
                        return {
                            provider: providerKey,
                            method: methodKey,
                            index: '0'
                        };
                    }
                    else {
                        return {
                            provider: providerKey,
                            method: methodKey
                        };
                    }
                }
            }
        }
        return null;
    },
    checkEligibleOrFindAlternate: function (method) {
        if (!method) {
            return PaymentConfiguration.firstEligibleMethod();
        }
        if (PaymentConfiguration.eligibleMethod(method.provider, method.method, method.index)) {
            return method;
        }
        return PaymentConfiguration.firstEligibleMethod();
    }
};
function updateCustomerMerchantAccount(merchantCustomer) {
    return {
        type: DispatchActionType.MERCHANT_CUSTOMER,
        payload: merchantCustomer
    };
}
function updateCustomerMerchantAccountExistence(exist) {
    return {
        type: DispatchActionType.MERCHANT_CUSTOMER_EXIST,
        payload: exist
    };
}
var MerchantCustomer = {
    loggedIn: function () { return store.getState().merchantCustomer.loggedIn; },
    usernameExist: function () { return store.getState().merchantCustomer.usernameIsRegistered; }
};
var setSessionId = createDispatchUpdate(DispatchActionType.ORDER_SESSION_ID);
var updateCustomerAddressValidation = createDispatchUpdate(DispatchActionType.ORDER_ADDRESS_VALIDATED);
var setExtraFields = createDispatchUpdate(DispatchActionType.ORDER_SET_EXTRA_FIELDS);
var setOrderError = createDispatchUpdate(DispatchActionType.ORDER_SET_ERROR_MESSAGE);
var PeachPayOrder = {
    sessionId: function () { return store.getState().peachPayOrder.sessionId; },
    contents: function () { return store.getState().calculatedCarts[0].cart; },
    errorMessage: function () { return store.getState().peachPayOrder.errorMessage; },
    collectSelectedShipping: function () {
        var _a;
        var carts = store.getState().calculatedCarts;
        var selectedShippingMethodsRecord = {};
        for (var _i = 0, _b = Object.keys(carts); _i < _b.length; _i++) {
            var cartKey = _b[_i];
            var cart = carts[cartKey];
            if (!cart) {
                continue;
            }
            for (var _c = 0, _d = Object.keys((_a = cart.package_record) !== null && _a !== void 0 ? _a : {}); _c < _d.length; _c++) {
                var packageKey = _d[_c];
                var packageRecord = cart.package_record[packageKey];
                if (!packageRecord) {
                    continue;
                }
                var shippingKey = cartKey === '0' ? packageKey : "".concat(cartKey, "_").concat(packageKey);
                selectedShippingMethodsRecord[shippingKey] = packageRecord.selected_method;
            }
        }
        return selectedShippingMethodsRecord;
    },
    customerAddressValidated: function () { return store.getState().peachPayOrder.customerAddressValidated; },
    extraFieldsRecord: function () { return store.getState().peachPayOrder.additionalFields; }
};
function formatCurrencyString(cost) {
    var _a = MerchantConfiguration.currency.configuration(), symbol = _a.symbol, position = _a.position;
    if (typeof cost !== 'number') {
        cost = 0;
    }
    var formattedCurrency = '';
    if (position === 'left' || position === 'left_space') {
        var negSymbol = '';
        var formattedCost = formatCostString(cost);
        if (cost < 0) {
            negSymbol = '−';
            formattedCost = formatCostString(Math.abs(cost));
        }
        formattedCurrency = "".concat(negSymbol).concat(symbol).concat(position === 'left_space' ? ' ' : '').concat(formattedCost);
    }
    else {
        formattedCurrency = "".concat(formatCostString(cost)).concat(position === 'right_space' ? ' ' : '').concat(symbol);
    }
    return formattedCurrency;
}
function formatCostString(cost) {
    var _a, _b;
    var _c = MerchantConfiguration.currency.configuration(), code = _c.code, thousandsSeparator = _c.thousands_separator, decimalSeparator = _c.decimal_separator, rounding = _c.rounding, decimals = _c.number_of_decimals;
    if (typeof cost !== 'number') {
        cost = 0;
    }
    if (code === 'JPY') {
        return cost.toString();
    }
    var numberOfDecimals = decimals || 2;
    switch (rounding) {
        case 'up':
            cost = Math.ceil(cost);
            break;
        case 'down':
            cost = Math.floor(cost);
            break;
        case 'nearest':
            cost = Math.round(cost);
            break;
        default:
            break;
    }
    cost = Number.parseFloat(cost.toFixed(decimals));
    var formattedPrice = '';
    try {
        var currencySplit = cost.toFixed(numberOfDecimals).split('.');
        var dollarAmount = currencySplit[0];
        var centsAmount = currencySplit[1] || '';
        var rev = currencySplit[0].split('').reverse().join('');
        var revFormat = (_b = (_a = rev.match(/.{1,3}/g)) === null || _a === void 0 ? void 0 : _a.join(thousandsSeparator)) !== null && _b !== void 0 ? _b : '';
        dollarAmount = revFormat.split('').reverse().join('');
        formattedPrice += dollarAmount;
        if (centsAmount !== '') {
            formattedPrice += decimalSeparator + centsAmount;
        }
        return formattedPrice;
    }
    catch (_d) {
        return cost.toFixed(decimals);
    }
}
function clearInput(selector) {
    for (var _i = 0, _a = $qsAll(selector); _i < _a.length; _i++) {
        var $element = _a[_i];
        $element.value = '';
    }
}
function renderDropDownList(data, defaultOption) {
    if (defaultOption === void 0) { defaultOption = ''; }
    if (!data) {
        data = {};
    }
    var list = Object.entries(data).map(function (_a) {
        var key = _a[0], value = _a[1];
        return "<option value=\"".concat(key, "\"> ").concat(value, " </option>");
    });
    if (defaultOption) {
        return "<option hidden disabled selected value=\"\">".concat(defaultOption, "</option>").concat(list.join(''));
    }
    return list.join('');
}
function selectDropdown($select, value) {
    if (!$select) {
        return;
    }
    $select.value = value;
}
function scrollRight(related) {
    var _a;
    (_a = $qs('.pp-prev-btn' + related)) === null || _a === void 0 ? void 0 : _a.classList.remove('scroll-end');
    $qs('#pp-products-list' + related, function ($element) { return $element.scrollLeft += parseInt("".concat(related.length === 0 ? '392' : '168')); });
}
function scrollLeft(related) {
    var _a;
    (_a = $qs('.pp-next-btn' + related)) === null || _a === void 0 ? void 0 : _a.classList.remove('scroll-end');
    $qs('#pp-products-list' + related, function ($element) { return $element.scrollLeft -= parseInt("".concat(related.length === 0 ? '392' : '168')); });
}
function scrollAdjuster(related) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l;
    var scrollEnd = ((_a = $qs('#pp-products-list' + related)) === null || _a === void 0 ? void 0 : _a.scrollLeft) ? (_b = $qs('#pp-products-list' + related)) === null || _b === void 0 ? void 0 : _b.scrollLeft : 1;
    var offset = (_c = $qs('#pp-products-list' + related)) === null || _c === void 0 ? void 0 : _c.offsetWidth;
    var scrollWidth = (_d = $qs('#pp-products-list' + related)) === null || _d === void 0 ? void 0 : _d.scrollWidth;
    if (((_e = $qs('#pp-products-list' + related)) === null || _e === void 0 ? void 0 : _e.scrollLeft) === 0) {
        (_f = $qs('.pp-prev-btn' + related)) === null || _f === void 0 ? void 0 : _f.classList.add('scroll-end');
        (_g = $qs('.pp-next-btn' + related)) === null || _g === void 0 ? void 0 : _g.classList.remove('scroll-end');
    }
    else if (scrollEnd && scrollWidth && offset && scrollEnd + 1 >= scrollWidth - offset) {
        (_h = $qs('.pp-next-btn' + related)) === null || _h === void 0 ? void 0 : _h.classList.add('scroll-end');
        (_j = $qs('.pp-prev-btn' + related)) === null || _j === void 0 ? void 0 : _j.classList.remove('scroll-end');
    }
    else {
        (_k = $qs('.pp-next-btn' + related)) === null || _k === void 0 ? void 0 : _k.classList.remove('scroll-end');
        (_l = $qs('.pp-prev-btn' + related)) === null || _l === void 0 ? void 0 : _l.classList.remove('scroll-end');
    }
}
function formEntry(formData, key) {
    var _a;
    if (formData.get(key) === null) {
        return '';
    }
    return (_a = formData.get(key)) !== null && _a !== void 0 ? _a : '';
}
function getCountryName(countryCode) {
    var _a, _b;
    if (!peachpayCountries[countryCode]) {
        return 'Unknown Country Code: ' + countryCode;
    }
    return (_b = (_a = peachpayCountries === null || peachpayCountries === void 0 ? void 0 : peachpayCountries[countryCode]) === null || _a === void 0 ? void 0 : _a.name) !== null && _b !== void 0 ? _b : 'Unknown Country Code: ' + countryCode;
}
function stateProvinceOrCounty(countryCode) {
    switch (countryCode) {
        case 'US':
        case 'MY':
        case 'AU':
            return getLocaleText('Select a State');
        case 'GB':
            return getLocaleText('county');
        default:
            return getLocaleText('Select a Province');
    }
}
function isEUCountry(countryCode) {
    var EUCountries = [
        'AT',
        'BE',
        'BG',
        'CY',
        'CZ',
        'DK',
        'EE',
        'FI',
        'FR',
        'DE',
        'GR',
        'HU',
        'IE',
        'IT',
        'LV',
        'LT',
        'LU',
        'MT',
        'NL',
        'PL',
        'PT',
        'RO',
        'SK',
        'SI',
        'ES',
        'SE'
    ];
    if (EUCountries.includes(countryCode)) {
        return true;
    }
    return false;
}
var peachpayCountries = {
    AF: {
        name: 'Afghanistan'
    },
    AX: {
        name: 'Åland Islands'
    },
    AL: {
        name: 'Albania'
    },
    DZ: {
        name: 'Algeria'
    },
    AS: {
        name: 'American Samoa'
    },
    AD: {
        name: 'Andorra'
    },
    AO: {
        name: 'Angola'
    },
    AI: {
        name: 'Anguilla'
    },
    AQ: {
        name: 'Antarctica'
    },
    AG: {
        name: 'Antigua and Barbuda'
    },
    AR: {
        name: 'Argentina'
    },
    AM: {
        name: 'Armenia'
    },
    AW: {
        name: 'Aruba'
    },
    AU: {
        name: 'Australia'
    },
    AT: {
        name: 'Austria'
    },
    AZ: {
        name: 'Azerbaijan'
    },
    BS: {
        name: 'Bahamas'
    },
    BH: {
        name: 'Bahrain'
    },
    BD: {
        name: 'Bangladesh'
    },
    BB: {
        name: 'Barbados'
    },
    BY: {
        name: 'Belarus'
    },
    BE: {
        name: 'Belgium'
    },
    BZ: {
        name: 'Belize'
    },
    BJ: {
        name: 'Benin'
    },
    BM: {
        name: 'Bermuda'
    },
    BT: {
        name: 'Bhutan'
    },
    BO: {
        name: 'Bolivia, Plurinational State of'
    },
    BQ: {
        name: 'Bonaire, Sint Eustatius and Saba'
    },
    BA: {
        name: 'Bosnia and Herzegovina'
    },
    BW: {
        name: 'Botswana'
    },
    BV: {
        name: 'Bouvet Island'
    },
    BR: {
        name: 'Brazil'
    },
    IO: {
        name: 'British Indian Ocean Territory'
    },
    BN: {
        name: 'Brunei Darussalam'
    },
    BG: {
        name: 'Bulgaria'
    },
    BF: {
        name: 'Burkina Faso'
    },
    BI: {
        name: 'Burundi'
    },
    KH: {
        name: 'Cambodia'
    },
    CM: {
        name: 'Cameroon'
    },
    CA: {
        name: 'Canada'
    },
    CV: {
        name: 'Cape Verde'
    },
    KY: {
        name: 'Cayman Islands'
    },
    CF: {
        name: 'Central African Republic'
    },
    TD: {
        name: 'Chad'
    },
    CL: {
        name: 'Chile'
    },
    CN: {
        name: 'China'
    },
    CX: {
        name: 'Christmas Island'
    },
    CC: {
        name: 'Cocos (Keeling) Islands'
    },
    CO: {
        name: 'Colombia'
    },
    KM: {
        name: 'Comoros'
    },
    CG: {
        name: 'Congo'
    },
    CD: {
        name: 'Congo, the Democratic Republic of the'
    },
    CK: {
        name: 'Cook Islands'
    },
    CR: {
        name: 'Costa Rica'
    },
    CI: {
        name: 'Côte d\'Ivoire'
    },
    HR: {
        name: 'Croatia'
    },
    CU: {
        name: 'Cuba'
    },
    CW: {
        name: 'Curaçao'
    },
    CY: {
        name: 'Cyprus'
    },
    CZ: {
        name: 'Czech Republic'
    },
    DK: {
        name: 'Denmark'
    },
    DJ: {
        name: 'Djibouti'
    },
    DM: {
        name: 'Dominica'
    },
    DO: {
        name: 'Dominican Republic'
    },
    EC: {
        name: 'Ecuador'
    },
    EG: {
        name: 'Egypt'
    },
    SV: {
        name: 'El Salvador'
    },
    GQ: {
        name: 'Equatorial Guinea'
    },
    ER: {
        name: 'Eritrea'
    },
    EE: {
        name: 'Estonia'
    },
    ET: {
        name: 'Ethiopia'
    },
    FK: {
        name: 'Falkland Islands (Malvinas)'
    },
    FO: {
        name: 'Faroe Islands'
    },
    FJ: {
        name: 'Fiji'
    },
    FI: {
        name: 'Finland'
    },
    FR: {
        name: 'France'
    },
    GF: {
        name: 'French Guiana'
    },
    PF: {
        name: 'French Polynesia'
    },
    TF: {
        name: 'French Southern Territories'
    },
    GA: {
        name: 'Gabon'
    },
    GM: {
        name: 'Gambia'
    },
    GE: {
        name: 'Georgia'
    },
    DE: {
        name: 'Germany'
    },
    GH: {
        name: 'Ghana'
    },
    GI: {
        name: 'Gibraltar'
    },
    GR: {
        name: 'Greece'
    },
    GL: {
        name: 'Greenland'
    },
    GD: {
        name: 'Grenada'
    },
    GP: {
        name: 'Guadeloupe'
    },
    GU: {
        name: 'Guam'
    },
    GT: {
        name: 'Guatemala'
    },
    GG: {
        name: 'Guernsey'
    },
    GN: {
        name: 'Guinea'
    },
    GW: {
        name: 'Guinea-Bissau'
    },
    GY: {
        name: 'Guyana'
    },
    HT: {
        name: 'Haiti'
    },
    HM: {
        name: 'Heard Island and McDonald Islands'
    },
    VA: {
        name: 'Holy See (Vatican City State)'
    },
    HN: {
        name: 'Honduras'
    },
    HK: {
        name: 'Hong Kong'
    },
    HU: {
        name: 'Hungary'
    },
    IS: {
        name: 'Iceland'
    },
    IN: {
        name: 'India'
    },
    ID: {
        name: 'Indonesia'
    },
    IR: {
        name: 'Iran, Islamic Republic of'
    },
    IQ: {
        name: 'Iraq'
    },
    IE: {
        name: 'Ireland'
    },
    IM: {
        name: 'Isle of Man'
    },
    IL: {
        name: 'Israel'
    },
    IT: {
        name: 'Italy'
    },
    JM: {
        name: 'Jamaica'
    },
    JP: {
        name: 'Japan'
    },
    JE: {
        name: 'Jersey'
    },
    JO: {
        name: 'Jordan'
    },
    KZ: {
        name: 'Kazakhstan'
    },
    KE: {
        name: 'Kenya'
    },
    KI: {
        name: 'Kiribati'
    },
    KP: {
        name: 'Korea Democratic People\'s Republic of'
    },
    KR: {
        name: 'Korea Republic of'
    },
    KW: {
        name: 'Kuwait'
    },
    KG: {
        name: 'Kyrgyzstan'
    },
    LA: {
        name: 'Lao People\'s Democratic Republic'
    },
    LV: {
        name: 'Latvia'
    },
    LB: {
        name: 'Lebanon'
    },
    LS: {
        name: 'Lesotho'
    },
    LR: {
        name: 'Liberia'
    },
    LY: {
        name: 'Libya'
    },
    LI: {
        name: 'Liechtenstein'
    },
    LT: {
        name: 'Lithuania'
    },
    LU: {
        name: 'Luxembourg'
    },
    MO: {
        name: 'Macao'
    },
    MK: {
        name: 'Macedonia, the former Yugoslav Republic of'
    },
    MG: {
        name: 'Madagascar'
    },
    MW: {
        name: 'Malawi'
    },
    MY: {
        name: 'Malaysia'
    },
    MV: {
        name: 'Maldives'
    },
    ML: {
        name: 'Mali'
    },
    MT: {
        name: 'Malta'
    },
    MH: {
        name: 'Marshall Islands'
    },
    MQ: {
        name: 'Martinique'
    },
    MR: {
        name: 'Mauritania'
    },
    MU: {
        name: 'Mauritius'
    },
    YT: {
        name: 'Mayotte'
    },
    MX: {
        name: 'Mexico'
    },
    FM: {
        name: 'Micronesia, Federated States of'
    },
    MD: {
        name: 'Moldova, Republic of'
    },
    MC: {
        name: 'Monaco'
    },
    MN: {
        name: 'Mongolia'
    },
    ME: {
        name: 'Montenegro'
    },
    MS: {
        name: 'Montserrat'
    },
    MA: {
        name: 'Morocco'
    },
    MZ: {
        name: 'Mozambique'
    },
    MM: {
        name: 'Myanmar'
    },
    NA: {
        name: 'Namibia'
    },
    NR: {
        name: 'Nauru'
    },
    NP: {
        name: 'Nepal'
    },
    NL: {
        name: 'Netherlands'
    },
    NC: {
        name: 'New Caledonia'
    },
    NZ: {
        name: 'New Zealand'
    },
    NI: {
        name: 'Nicaragua'
    },
    NE: {
        name: 'Niger'
    },
    NG: {
        name: 'Nigeria'
    },
    NU: {
        name: 'Niue'
    },
    NF: {
        name: 'Norfolk Island'
    },
    MP: {
        name: 'Northern Mariana Islands'
    },
    NO: {
        name: 'Norway'
    },
    OM: {
        name: 'Oman'
    },
    PK: {
        name: 'Pakistan'
    },
    PW: {
        name: 'Palau'
    },
    PS: {
        name: 'Palestinian Territory'
    },
    PA: {
        name: 'Panama'
    },
    PG: {
        name: 'Papua New Guinea'
    },
    PY: {
        name: 'Paraguay'
    },
    PE: {
        name: 'Peru'
    },
    PH: {
        name: 'Philippines'
    },
    PN: {
        name: 'Pitcairn'
    },
    PL: {
        name: 'Poland'
    },
    PT: {
        name: 'Portugal'
    },
    PR: {
        name: 'Puerto Rico'
    },
    QA: {
        name: 'Qatar'
    },
    RE: {
        name: 'Réunion'
    },
    RO: {
        name: 'Romania'
    },
    RU: {
        name: 'Russian Federation'
    },
    RW: {
        name: 'Rwanda'
    },
    BL: {
        name: 'Saint Barthélemy'
    },
    SH: {
        name: 'Saint Helena, Ascension and Tristan da Cunha'
    },
    KN: {
        name: 'Saint Kitts and Nevis'
    },
    LC: {
        name: 'Saint Lucia'
    },
    MF: {
        name: 'Saint Martin (French part)'
    },
    PM: {
        name: 'Saint Pierre and Miquelon'
    },
    VC: {
        name: 'Saint Vincent and the Grenadines'
    },
    WS: {
        name: 'Samoa'
    },
    SM: {
        name: 'San Marino'
    },
    ST: {
        name: 'Sao Tome and Principe'
    },
    SA: {
        name: 'Saudi Arabia'
    },
    SN: {
        name: 'Senegal'
    },
    RS: {
        name: 'Serbia'
    },
    SC: {
        name: 'Seychelles'
    },
    SL: {
        name: 'Sierra Leone'
    },
    SG: {
        name: 'Singapore'
    },
    SX: {
        name: 'Sint Maarten (Dutch part)'
    },
    SK: {
        name: 'Slovakia'
    },
    SI: {
        name: 'Slovenia'
    },
    SB: {
        name: 'Solomon Islands'
    },
    SO: {
        name: 'Somalia'
    },
    ZA: {
        name: 'South Africa'
    },
    GS: {
        name: 'South Georgia and the South Sandwich Islands'
    },
    SS: {
        name: 'South Sudan'
    },
    ES: {
        name: 'Spain'
    },
    LK: {
        name: 'Sri Lanka'
    },
    SD: {
        name: 'Sudan'
    },
    SR: {
        name: 'Suriname'
    },
    SJ: {
        name: 'Svalbard and Jan Mayen'
    },
    SZ: {
        name: 'Swaziland'
    },
    SE: {
        name: 'Sweden'
    },
    CH: {
        name: 'Switzerland'
    },
    SY: {
        name: 'Syrian Arab Republic'
    },
    TW: {
        name: 'Taiwan'
    },
    TJ: {
        name: 'Tajikistan'
    },
    TZ: {
        name: 'Tanzania United Republic of'
    },
    TH: {
        name: 'Thailand'
    },
    TL: {
        name: 'Timor-Leste'
    },
    TG: {
        name: 'Togo'
    },
    TK: {
        name: 'Tokelau'
    },
    TO: {
        name: 'Tonga'
    },
    TT: {
        name: 'Trinidad and Tobago'
    },
    TN: {
        name: 'Tunisia'
    },
    TR: {
        name: 'Turkey'
    },
    TM: {
        name: 'Turkmenistan'
    },
    TC: {
        name: 'Turks and Caicos Islands'
    },
    TV: {
        name: 'Tuvalu'
    },
    UG: {
        name: 'Uganda'
    },
    UA: {
        name: 'Ukraine'
    },
    AE: {
        name: 'United Arab Emirates'
    },
    GB: {
        name: 'United Kingdom'
    },
    US: {
        name: 'United States'
    },
    UM: {
        name: 'United States Minor Outlying Islands'
    },
    UY: {
        name: 'Uruguay'
    },
    UZ: {
        name: 'Uzbekistan'
    },
    VU: {
        name: 'Vanuatu'
    },
    VE: {
        name: 'Venezuela, Bolivarian Republic of'
    },
    VN: {
        name: 'Vietnam'
    },
    VG: {
        name: 'Virgin Islands'
    },
    VI: {
        name: 'Virgin Islands, U.S'
    },
    WF: {
        name: 'Wallis and Futuna'
    },
    EH: {
        name: 'Western Sahara'
    },
    YE: {
        name: 'Yemen'
    },
    ZM: {
        name: 'Zambia'
    },
    ZW: {
        name: 'Zimbabwe'
    }
};
var GLOBAL = {
    completedOrder: null,
    phpData: null,
    linkedProductsIds: []
};
function getCustomer() {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var iFrameWindow;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    iFrameWindow = (_a = document.querySelector('#one-click-iframe')) === null || _a === void 0 ? void 0 : _a.contentWindow;
                    if (!iFrameWindow) {
                        return [2, null];
                    }
                    return [4, fetchWindowData(iFrameWindow, 'pp-get-existing-customer-data')];
                case 1: return [2, _b.sent()];
            }
        });
    });
}
function setCustomer(customer) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var iFrameWindow;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    iFrameWindow = (_a = document.querySelector('#one-click-iframe')) === null || _a === void 0 ? void 0 : _a.contentWindow;
                    if (!iFrameWindow) {
                        return [2, false];
                    }
                    return [4, fetchWindowData(iFrameWindow, 'pp-set-existing-customer-data', customer)];
                case 1: return [2, _b.sent()];
            }
        });
    });
}
var defaultFormHTML = "<form id=\"pp-info-form\">\n<h2><span class=\"bold\" data-i18n=\"personal\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"email\" class=\"w-100 text-input\" type=\"email\" name=\"email\" placeholder=\" \" required>\n\t\t<label for=\"email\" data-i18n=\"email\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"phone\" class=\"w-100 text-input\" type=\"tel\" name=\"phone\" placeholder=\" \"required>\n\t\t<label for=\"phone\" data-i18n=\"phone\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_first\" class=\"w-100 text-input\" type=\"text\" name=\"name_first\" placeholder=\" \" required>\n\t\t<label for=\"name_first\" data-i18n=\"first-name\" class=\"form-label\"></label>\n\t\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_last\" class=\"w-100 text-input\" type=\"text\" name=\"name_last\" placeholder=\" \" required>\n\t\t<label for=\"name_last\" data-i18n=\"last-name\" class=\"form-label\"></label>\n\t</div>\n</div>\n<fieldset id=\"pp-shipping\" style=\"border: none; margin: 0; padding: 0;\">\n<h2 class=\"shipping-address-header\"><span class=\"bold\" data-i18n=\"shipping\"></span></h2>\n<h2 class=\"billing-address-header hide\"><span class=\"bold\" data-i18n=\"billing\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-70\">\n\t\t<input id=\"address1\" type=\"text\" name=\"address1\" class=\"w-100 text-input\" placeholder=\" \" required>\n\t\t<label for=\"address1\" data-i18n=\"street\" class=\"form-label form-label\"></label>\n\t</div>\n\t<div class=\"flex w-30\">\n\t\t<input id=\"address2\" type=\"text\" name=\"address2\" placeholder=\" \" class=\"w-100 text-input\">\n\t\t<label for=\"address2\" data-i18n=\"apt\" class=\"pp-apartment-label form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"postal\" class=\"w-100 text-input\" type=\"text\" name=\"postal\" placeholder=\" \" required>\n\t\t<label for=\"postal\" data-i18n=\"postal\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"city\" class=\"w-100 text-input\" type=\"text\" name=\"city\" placeholder=\" \" required>\n\t\t<label for=\"city\" data-i18n=\"city\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"state\" size=\"1\">\n\t\t\t<option hidden disabled selected value></option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label region-country-label hide\" data-i18n=\"state\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<select id=\"country\" class=\"w-100\" name=\"country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"country-label\" class=\"form-label region-country-label\"></label>\n\t</div>\n</div>\n<div id=\"checkout-delivery-date\" class=\"hide\">\n\t<h2 data-i18n=\"Delivery date\"></h2>\n\t<input type=\"date\" id=\"delivery-date\" name=\"delivery-date\" value=\"\" min=\"\">\n</div>\n</fieldset>\n</form>";
var japaneseFormHTML = "<form id=\"pp-info-form\">\n<h2><span class=\"bold\" data-i18n=\"personal\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"email\" class=\"w-100 text-input\" type=\"email\" name=\"email\" placeholder=\" \" required>\n\t\t<label for=\"email\" data-i18n=\"email\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"phone\" class=\"w-100 text-input\" type=\"tel\" name=\"phone\" placeholder=\" \" required>\n\t\t<label for=\"phone\" data-i18n=\"phone\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_last\" class=\"w-100 text-input\"  type=\"text\" name=\"name_last\" placeholder=\" \" required>\n\t\t<label for=\"name_last\" data-i18n=\"last-name\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_first\" class=\"w-100 text-input\" type=\"text\" name=\"name_first\" placeholder=\" \" required>\n\t\t<label for=\"name_first\" data-i18n=\"first-name\" class=\"form-label\"></label>\n\t</div>\n</div>\n<fieldset id=\"pp-shipping\" style=\"border: none; margin: 0; padding: 0;\">\n<h2 class=\"shipping-address-header\"><span class=\"bold\" data-i18n=\"shipping\"></span></h2>\n<h2 class=\"billing-address-header hide\"><span class=\"bold\" data-i18n=\"billing\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<select id=\"country\" class=\"w-100\" name=\"country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"select-country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"country-labely\" class=\"form-label region-country-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"postal\" class=\"w-100 text-input\" type=\"text\" name=\"postal\" placeholder=\" \" required>\n\t\t<label for=\"postal\" data-i18n=\"postal\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"state\" size=\"1\">\n\t\t\t<option hidden disabled selected value>State</option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label region-country-label hide\">Region</label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"city\" class=\"w-100 text-input\" type=\"text\" name=\"city\" placeholder=\" \" required>\n\t\t<label for=\"city\" data-i18n=\"city\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-70\">\n\t\t<input id=\"address1\" type=\"text\" name=\"address1\" class=\"w-100 text-input\" placeholder=\" \" required>\n\t\t<label for=\"address1\" data-i18n=\"street\" class=\"form-label form-label\"></label>\n\t</div>\n\t<div class=\"flex w-30\">\n\t\t<input id=\"address2\" type=\"text\" name=\"address2\" placeholder=\" \" class=\"w-100 text-input\">\n\t\t<label for=\"address2\" data-i18n=\"apt\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div id=\"checkout-delivery-date\" class=\"hide\">\n\t<h2 data-i18n=\"Delivery date\"></h2>\n\t<input type=\"date\" id=\"delivery-date\" name=\"delivery-date\" value=\"\" min=\"\">\n</div>\n</fieldset>\n</form>";
var checkoutFormNoPhoneNoApt = "<form id=\"pp-info-form\">\n<h2><span class=\"bold\" data-i18n=\"personal\"></span></h2>\n<div class=\"flex\">\n\t<input id=\"email\" class=\"w-100 text-input\" type=\"email\" name=\"email\" placeholder=\" \" required>\n\t<label for=\"email\" data-i18n=\"email\" class=\"form-label\"></label>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_first\" class=\"w-100 text-input\" type=\"text\" name=\"name_first\" placeholder=\" \" required>\n\t\t<label for=\"name_first\" data-i18n=\"first-name\" class=\"form-label\"></label>\n\t\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_last\" class=\"w-100 text-input\" type=\"text\" name=\"name_last\" placeholder=\" \" required>\n\t\t<label for=\"name_last\" data-i18n=\"last-name\" class=\"form-label\"></label>\n\t</div>\n</div>\n<fieldset id=\"pp-shipping\" style=\"border: none; margin: 0; padding: 0;\">\n<h2 class=\"shipping-address-header\"><span class=\"bold\" data-i18n=\"shipping\"></span></h2>\n<h2 class=\"billing-address-header hide\"><span class=\"bold\" data-i18n=\"billing\"></span></h2>\n<div class=\"flex\">\n\t<input id=\"address1\" type=\"text\" name=\"address1\" class=\"w-100 text-input\" placeholder=\" \" required>\n\t<label for=\"address1\" data-i18n=\"street\" class=\"form-label form-label\"></label>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"postal\" class=\"w-100 text-input\" type=\"text\" name=\"postal\" placeholder=\" \" required>\n\t\t<label for=\"postal\" data-i18n=\"postal\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"city\" class=\"w-100 text-input\" type=\"text\" name=\"city\" placeholder=\" \" required>\n\t\t<label for=\"city\" data-i18n=\"city\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"state\" size=\"1\">\n\t\t\t<option hidden disabled selected value></option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label region-country-label hide\" data-i18n=\"state\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<select id=\"country\" class=\"w-100\" name=\"country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"country-label\" class=\"form-label region-country-label\"></label>\n\t</div>\n</div>\n<div id=\"checkout-delivery-date\" class=\"hide\">\n\t<h2 data-i18n=\"Delivery date\"></h2>\n\t<input type=\"date\" id=\"delivery-date\" name=\"delivery-date\" value=\"\" min=\"\">\n</div>\n</fieldset>\n</form>";
function installCustomerFormFields(lang) {
    var _a;
    var form = defaultFormHTML;
    if (lang === 'ja') {
        form = japaneseFormHTML;
    }
    if (MerchantConfiguration.hostName() === 'initialaudio.com') {
        form = checkoutFormNoPhoneNoApt;
    }
    (_a = $qs('#pp-info')) === null || _a === void 0 ? void 0 : _a.insertAdjacentHTML('beforeend', form);
}
function initCustomer(message) {
    initCustomerEvents();
    renderCountryAndStateList(message.phpData.wc_location_info);
}
function initCustomerEvents() {
    var _a, _b;
    (_a = $qs('#country')) === null || _a === void 0 ? void 0 : _a.addEventListener('change', renderCorrectProvinceField);
    (_b = $qs('#pp-info-form')) === null || _b === void 0 ? void 0 : _b.addEventListener('change', function () { return setTimeout(syncCustomerFieldChanges); });
    var previousCustomerData = '';
    store.subscribe(function () {
        var customer = PeachPayCustomer.data();
        if (Environment.modalUI.page() === 'info') {
            if (Carts.virtual() && Feature.enabled(FeatureFlag.VIRTUAL_PRODUCT_FIELDS)) {
                $qs('#pp-shipping', function (element) {
                    element.classList.add('hide');
                    element.setAttribute('disabled', 'true');
                });
                $qs('#pp-location-marker', function (element) {
                    element.classList.add('hide');
                });
                $qs('#pp-address-line', function (element) {
                    element.classList.add('hide');
                });
            }
            else {
                $qs('#pp-shipping', function (element) {
                    element.classList.remove('hide');
                    element.removeAttribute('disabled');
                });
                $qs('#pp-location-marker', function (element) {
                    element.classList.remove('hide');
                });
                $qs('#pp-address-line', function (element) {
                    element.classList.remove('hide');
                });
            }
            var customerData = JSON.stringify(customer);
            if (customerData !== previousCustomerData) {
                previousCustomerData = customerData;
                renderCustomerFields(customer);
            }
        }
        if (Environment.modalUI.page() === 'payment') {
            renderCustomerHeader(customer, Environment.customer.existing());
        }
    });
}
function syncCustomerFieldChanges() {
    var $form = $qs('#pp-info-form');
    if (!$form) {
        return;
    }
    var formData = new FormData($form);
    store.dispatch(updateCustomer({
        email: formEntry(formData, 'email'),
        name_first: formEntry(formData, 'name_first'),
        name_last: formEntry(formData, 'name_last'),
        address1: formEntry(formData, 'address1'),
        address2: formEntry(formData, 'address2'),
        city: formEntry(formData, 'city'),
        state: formEntry(formData, 'state'),
        postal: formEntry(formData, 'postal'),
        country: formEntry(formData, 'country'),
        phone: formEntry(formData, 'phone')
    }));
}
function loadCustomer() {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var customer, locationInfo;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0: return [4, getCustomer()];
                case 1:
                    customer = _c.sent();
                    if (customer === null) {
                        locationInfo = MerchantConfiguration.general.wcLocationInfoData();
                        store.dispatch(updateCustomer(__assign(__assign({}, PeachPayCustomer.data()), { country: (_b = ((_a = locationInfo === null || locationInfo === void 0 ? void 0 : locationInfo.customer_default_country) !== null && _a !== void 0 ? _a : locationInfo === null || locationInfo === void 0 ? void 0 : locationInfo.store_country)) !== null && _b !== void 0 ? _b : '' })));
                        return [2];
                    }
                    store.dispatch(updateCustomer(customer));
                    store.dispatch(updateEnvironment({
                        customerExists: true,
                        modalPageType: 'payment'
                    }));
                    return [2];
            }
        });
    });
}
function saveCustomerToBrowser() {
    return __awaiter(this, void 0, void 0, function () {
        var customer;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    customer = PeachPayCustomer.data();
                    return [4, setCustomer(customer)];
                case 1:
                    _a.sent();
                    return [2, customer];
            }
        });
    });
}
function renderCorrectProvinceField() {
    var _a, _b, _c, _d, _e, _f;
    var merchantShipping = MerchantConfiguration.general.wcLocationInfoData();
    if (merchantShipping) {
        var $countries = $qs('#country');
        if (!$countries) {
            return;
        }
        var defaultOption_1 = stateProvinceOrCounty($countries.value);
        var stateOrProvinceOptions = (_b = merchantShipping.allowed_states_or_provinces[(_a = $countries.value) !== null && _a !== void 0 ? _a : '']) !== null && _b !== void 0 ? _b : {};
        if (stateOrProvinceOptions && Object.keys(stateOrProvinceOptions).length > 0) {
            var $stateOrProvincesSelect = $qs('#dynamic-states');
            if ($stateOrProvincesSelect) {
                $stateOrProvincesSelect.innerHTML = renderDropDownList(stateOrProvinceOptions, defaultOption_1);
                $stateOrProvincesSelect.disabled = false;
                $stateOrProvincesSelect.setAttribute('name', 'state');
                if (defaultOption_1 === getLocaleText('Select a Province')) {
                    $qs('label[for="dynamic-states"]', function ($element) { return $element.textContent = getLocaleText('province'); });
                }
                else if (defaultOption_1 === getLocaleText('Select a State')) {
                    $qs('label[for="dynamic-states"]', function ($element) { return $element.textContent = getLocaleText('state'); });
                }
                else {
                    $qs('label[for="dynamic-states"]', function ($element) { return $element.textContent = defaultOption_1; });
                }
                $stateOrProvincesSelect.required = true;
                $stateOrProvincesSelect.classList.remove('hide');
                (_c = $qs('label[for="dynamic-states"]')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
            }
            var $stateOrProvincesText = $qs('#province');
            if ($stateOrProvincesText) {
                $stateOrProvincesText.disabled = true;
                $stateOrProvincesText.setAttribute('name', 'off');
                $stateOrProvincesText.required = false;
                $stateOrProvincesText.value = '';
                $stateOrProvincesText.classList.add('hide');
                (_d = $qs('label[for="province"]')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
            }
        }
        else {
            var $stateOrProvincesSelect = $qs('#dynamic-states');
            if ($stateOrProvincesSelect) {
                $stateOrProvincesSelect.disabled = true;
                $stateOrProvincesSelect.setAttribute('name', 'off');
                $stateOrProvincesSelect.required = false;
                $stateOrProvincesSelect.classList.add('hide');
                (_e = $qs('label[for="dynamic-states"]')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
            }
            var $stateOrProvincesText = $qs('#province');
            if ($stateOrProvincesText) {
                $stateOrProvincesText.disabled = false;
                $stateOrProvincesText.setAttribute('name', 'state');
                if (defaultOption_1 === getLocaleText('Select a Province')) {
                    $qs('label[for="province"]', function ($element) { return $element.textContent = getLocaleText('province'); });
                }
                else if (defaultOption_1 === getLocaleText('Select a State')) {
                    $qs('label[for="province"]', function ($element) { return $element.textContent = getLocaleText('state'); });
                }
                else {
                    $qs('label[for="province"]', function ($element) { return $element.textContent = defaultOption_1; });
                }
                $stateOrProvincesText.value = '';
                $stateOrProvincesText.classList.remove('hide');
                (_f = $qs('label[for="province"]')) === null || _f === void 0 ? void 0 : _f.classList.remove('hide');
            }
        }
    }
}
function renderCountryAndStateList(merchantLocationInfo) {
    if (!merchantLocationInfo) {
        console.warn('Warning: No WC Location info. Please update the PeachPay Plugin.');
        return;
    }
    var $countries = $qs('#country');
    if (!$countries) {
        return;
    }
    var selectACountry = getLocaleText('country');
    var countryOptions = merchantLocationInfo.allowed_countries;
    $countries.innerHTML = renderDropDownList(countryOptions, selectACountry);
    selectDropdown($countries, merchantLocationInfo.customer_default_country || merchantLocationInfo.store_country);
    if ($countries.options.length === 2) {
        $countries.selectedIndex = 1;
    }
    $countries.dispatchEvent(new Event('change'));
}
function renderCustomerFields(customer) {
    $qs('#pp-info-form input[name="name_first"]', function ($element) { return $element.value = customer.name_first; });
    $qs('#pp-info-form input[name="name_last"]', function ($element) { return $element.value = customer.name_last; });
    $qs('#pp-info-form input[name="email"]', function ($element) { return $element.value = customer.email; });
    $qs('#pp-info-form input[name="phone"]', function ($element) { return $element.value = customer.phone; });
    $qs('#pp-info-form input[name="address1"]', function ($element) { return $element.value = customer.address1; });
    $qs('#pp-info-form input[name="address2"]', function ($element) { return $element.value = customer.address2; });
    $qs('#pp-info-form input[name="postal"]', function ($element) { return $element.value = customer.postal; });
    $qs('#pp-info-form input[name="city"]', function ($element) { return $element.value = customer.city; });
    $qs('#pp-info-form input[name="country"]', function ($element) { return $element.value = customer.country; });
    renderCorrectProvinceField();
    $qs('#pp-info-form [name="state"]', function ($element) { return $element.value = customer.state; });
}
function renderCustomerHeader(customer, existingCustomer) {
    var _a, _b, _c, _d;
    if (existingCustomer) {
        $qs('#existing-email', function ($element) { return $element.textContent = customer.email; });
        $qs('#existing-name_first', function ($element) { return $element.textContent = customer.name_first; });
        $qs('#existing-name_last', function ($element) { return $element.textContent = customer.name_last; });
        $qs('#existing-address1', function ($element) { return $element.textContent = customer.address1; });
        $qs('#existing-address2', function ($element) { return $element.textContent = customer.address2 ? ' ' + customer.address2 : ''; });
        $qs('#existing-city', function ($element) { return $element.textContent = customer.city; });
        if (customer.country === 'JP') {
            var fullStateName_1 = (_b = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.wc_location_info) === null || _b === void 0 ? void 0 : _b.allowed_states_or_provinces.JP[customer.state];
            $qs('#existing-state', function ($element) { return $element.textContent = fullStateName_1 !== null && fullStateName_1 !== void 0 ? fullStateName_1 : ''; });
        }
        else {
            $qs('#existing-state', function ($element) { return $element.textContent = customer.state; });
        }
        $qs('#existing-country', function ($element) { return $element.textContent = getCountryName(customer.country); });
        $qs('#existing-postal', function ($element) { return $element.textContent = customer.postal; });
    }
    else {
        var fullAddress_1 = '';
        if (customer.country === 'JP') {
            var fullState = (_d = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.wc_location_info) === null || _d === void 0 ? void 0 : _d.allowed_states_or_provinces.JP[customer.state];
            fullAddress_1 = "".concat(customer.postal, ", ").concat(fullState !== null && fullState !== void 0 ? fullState : customer.state, ",  ").concat(customer.city, ", ").concat(customer.address1).concat(customer.address2 ? ' ' + customer.address2 : '');
        }
        else {
            fullAddress_1 = "".concat(customer.address1).concat(customer.address2 ? ' ' + customer.address2 + ', ' : ',', " ").concat(customer.city, ", ").concat(customer.state, " ").concat(customer.postal, ", ").concat(customer.country);
        }
        var fullName_1 = "".concat(customer.name_first, " ").concat(customer.name_last);
        $qs('.email', function ($element) { return $element.innerHTML = customer.email; });
        $qs('.full-name', function ($element) { return $element.innerHTML = fullName_1; });
        $qs('.pp-address', function ($element) { return $element.innerHTML = fullAddress_1; });
    }
}
function initCurrency(message) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q;
    initCurrencyEvents();
    store.dispatch(updateMerchantCurrencyConfig({
        code: (_b = (_a = message.phpData.currency_info) === null || _a === void 0 ? void 0 : _a.code) !== null && _b !== void 0 ? _b : 'USD',
        symbol: (_e = (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.currency_info) === null || _d === void 0 ? void 0 : _d.symbol) !== null && _e !== void 0 ? _e : '$',
        thousands_separator: (_g = (_f = message.phpData.currency_info) === null || _f === void 0 ? void 0 : _f.thousands_separator) !== null && _g !== void 0 ? _g : ',',
        decimal_separator: (_j = (_h = message.phpData.currency_info) === null || _h === void 0 ? void 0 : _h.decimal_separator) !== null && _j !== void 0 ? _j : '.',
        number_of_decimals: (_l = (_k = message.phpData.currency_info) === null || _k === void 0 ? void 0 : _k.number_of_decimals) !== null && _l !== void 0 ? _l : 2,
        position: (_o = (_m = message.phpData.currency_info) === null || _m === void 0 ? void 0 : _m.position) !== null && _o !== void 0 ? _o : 'left',
        rounding: (_q = (_p = message.phpData.currency_info) === null || _p === void 0 ? void 0 : _p.rounding) !== null && _q !== void 0 ? _q : 'disabled'
    }));
}
function initCurrencyEvents() {
    store.subscribe(function () {
        renderCurrencySymbols();
    });
}
function renderCurrencySymbols() {
    var _a = MerchantConfiguration.currency.configuration(), position = _a.position, symbol = _a.symbol;
    var right = position === 'right' || position === 'right_space';
    for (var _i = 0, _b = $qsAll(".currency-symbol".concat(right ? '-after' : '')); _i < _b.length; _i++) {
        var $element = _b[_i];
        $element.innerHTML = symbol;
    }
}
function peachpayAlert(message, action) {
    if (action === void 0) { action = ''; }
    window.parent.postMessage({
        event: 'peachpayAlert',
        action: action,
        message: message
    }, '*');
}
function cartIsVirtual(cart) {
    var _a;
    if ((cart === null || cart === void 0 ? void 0 : cart.length) === 0) {
        return true;
    }
    return (_a = cart === null || cart === void 0 ? void 0 : cart.every(function (v) { return v.virtual; })) !== null && _a !== void 0 ? _a : true;
}
function itemsInCart(cart) {
    var _a;
    return (_a = cart === null || cart === void 0 ? void 0 : cart.length) !== null && _a !== void 0 ? _a : 0;
}
function cartItemQuantity(cartItem) {
    var _a;
    return typeof (cartItem === null || cartItem === void 0 ? void 0 : cartItem.quantity) === 'string' ? Number.parseInt(cartItem.quantity) : (_a = cartItem === null || cartItem === void 0 ? void 0 : cartItem.quantity) !== null && _a !== void 0 ? _a : 0;
}
function restrictedCartProductsByCountry(cart, selectedCountryCode) {
    return cart.filter(function (v) {
        if (v.wc_country_base_restrictions) {
            if (v.wc_country_base_restrictions.type === 'specific' && !v.wc_country_base_restrictions.countries.includes(selectedCountryCode)) {
                return true;
            }
            if (v.wc_country_base_restrictions.type === 'excluded' && v.wc_country_base_restrictions.countries.includes(selectedCountryCode)) {
                return true;
            }
        }
        return false;
    });
}
function validateCartItemsWithCustomer(cart, useLocalStorage) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var customer, countryValue, invalidCartItems_1, invalidCartItems;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0: return [4, getCustomer()];
                case 1:
                    customer = _c.sent();
                    countryValue = (_b = (_a = $qs('#country')) === null || _a === void 0 ? void 0 : _a.value) !== null && _b !== void 0 ? _b : '';
                    if (useLocalStorage && customer) {
                        invalidCartItems_1 = restrictedCartProductsByCountry(cart, customer.country);
                        if (invalidCartItems_1.length === 0) {
                            return [2, true];
                        }
                        peachpayAlert("The following cart items cannot be shipped to ".concat(getCountryName(countryValue), ":\n ").concat(invalidCartItems_1.map(function (v) { return v.name; }).join(','), ".\n Please remove them from your cart."), 'closeModal');
                        return [2, false];
                    }
                    invalidCartItems = restrictedCartProductsByCountry(cart, countryValue);
                    if (invalidCartItems.length === 0) {
                        return [2, true];
                    }
                    peachpayAlert("The following cart items cannot be shipped to ".concat(getCountryName(countryValue), ":\n ").concat(invalidCartItems.map(function (v) { return v.name; }).join(','), ".\n Please remove them from your cart."), 'closeModal');
                    return [2, false];
            }
        });
    });
}
function buildSubscriptionPriceMetaData(meta, __short) {
    if (__short === void 0) { __short = false; }
    if (!meta.subscription) {
        return '';
    }
    if (Number.parseInt(String(meta.subscription.period_interval)) === 1) {
        return " / ".concat(meta.subscription.period);
    }
    if (__short) {
        return " every ".concat(meta.subscription.period_interval, " ").concat(meta.subscription.period, "s");
    }
    return " every ".concat(meta.subscription.period_interval, " ").concat(meta.subscription.period, "s for ").concat(meta.subscription.length, " ").concat(meta.subscription.period, "s");
}
function buildSubscriptionFirstRenewalString(meta) {
    if (!meta.subscription || !meta.subscription.first_renewal) {
        return '';
    }
    var date = new Date(meta.subscription.first_renewal);
    var options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return "First renewal: ".concat(date.toLocaleString(Environment.language(), options));
}
function initMerchantAccount(message) {
    var _a, _b, _c, _d, _e, _f, _g;
    initMerchantAccountEvents();
    var accountDetails = message.phpData.merchant_customer_account;
    store.dispatch(updateMerchantAccountConfig({
        allowGuestCheckout: (_a = accountDetails === null || accountDetails === void 0 ? void 0 : accountDetails.allow_guest_checkout) !== null && _a !== void 0 ? _a : true,
        allowAccountCreationOrLoginDuringCheckout: (_b = accountDetails === null || accountDetails === void 0 ? void 0 : accountDetails.logins_and_registrations_enabled) !== null && _b !== void 0 ? _b : true,
        autoGenerateUsername: (_c = accountDetails === null || accountDetails === void 0 ? void 0 : accountDetails.auto_generate_username) !== null && _c !== void 0 ? _c : false,
        autoGeneratePassword: (_d = accountDetails === null || accountDetails === void 0 ? void 0 : accountDetails.auto_generate_password) !== null && _d !== void 0 ? _d : false
    }));
    store.dispatch(updateCustomerMerchantAccount({
        username: (_e = accountDetails.email) !== null && _e !== void 0 ? _e : '',
        loggedIn: (_f = accountDetails.logged_in) !== null && _f !== void 0 ? _f : false,
        usernameIsRegistered: (_g = accountDetails.logged_in) !== null && _g !== void 0 ? _g : false
    }));
}
function initMerchantAccountEvents() {
    var _a;
    store.subscribe(function () {
        renderMerchantCustomerAccountPasswordInput(MerchantConfiguration.hostName(), !Environment.customer.existing() && Environment.modalUI.page() === 'payment');
    });
    (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.addEventListener('submit', function (event) {
        event.preventDefault();
        requestMerchantAccountExistence(PeachPayCustomer.email());
    });
    onWindowMessage('emailExist', function (message) {
        store.dispatch(updateCustomerMerchantAccountExistence(Boolean(message.emailResult)));
    });
}
function requestMerchantAccountExistence(email) {
    var _a;
    (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage({
        event: 'emailExist',
        email: email
    }, '*');
}
function getMerchantCustomerAccountPasswordValue() {
    var $input = $qs('#account-password');
    var $inputExisting = $qs('#account-password-existing');
    if (!$input || !$inputExisting) {
        return '';
    }
    if ($inputExisting.value !== '') {
        return $inputExisting.value;
    }
    return $input.value;
}
function validateMerchantCustomerPasswordField() {
    var password = getMerchantCustomerAccountPasswordValue();
    var $redText = $qs('#account-password-error');
    var $redTextExisting = $qs('#account-password-error-existing');
    if (!$redText || !$redTextExisting) {
        return false;
    }
    if (password === '' || password.length < 8) {
        $redText.textContent = getLocaleText('The password entered must be at least 8 characters long.');
        $redTextExisting.textContent = getLocaleText('The password entered must be at least 8 characters long.');
        return false;
    }
    $redText.textContent = '';
    $redTextExisting.textContent = '';
    return true;
}
function shouldShowMerchantCustomerAccountPasswordField() {
    if (MerchantCustomer.loggedIn()) {
        return false;
    }
    if (!Carts.subscriptionPresent()) {
        if (!MerchantConfiguration.accounts.allowGuestCheckout()) {
            if (MerchantConfiguration.accounts.generatePasswordEnabled() && !MerchantCustomer.usernameExist()) {
                return false;
            }
        }
        else {
            return false;
        }
        if (!MerchantConfiguration.accounts.loginDuringCheckoutEnabled() && !MerchantCustomer.usernameExist()) {
            return false;
        }
        if (MerchantConfiguration.accounts.generatePasswordEnabled() && !MerchantCustomer.usernameExist()) {
            return false;
        }
    }
    else {
        if (MerchantConfiguration.accounts.generatePasswordEnabled() && !MerchantCustomer.usernameExist()) {
            return false;
        }
    }
    return true;
}
function renderMerchantCustomerAccountPasswordInput(merchantHostname, onNewPaymentScreen) {
    if (onNewPaymentScreen === void 0) { onNewPaymentScreen = false; }
    var $input = $qs('#pp-account-password');
    var $inputExisting = $qs('#pp-account-password-existing');
    if (!$input || !$inputExisting) {
        return;
    }
    $input.value = '';
    $inputExisting.value = '';
    var labelHTML = getLocaleText('Create a new password, or use an existing one if you already have an account for') + ' ' + merchantHostname;
    $qs('#pp-account-password-label', function ($element) { return $element.innerHTML = labelHTML; });
    $qs('#pp-account-password-label-existing', function ($element) { return $element.innerHTML = labelHTML; });
    if (shouldShowMerchantCustomerAccountPasswordField()) {
        if (onNewPaymentScreen) {
            $input.classList.remove('hide');
        }
        else {
            $input.classList.add('hide');
        }
        $inputExisting.classList.remove('hide');
    }
    else {
        $input.classList.add('hide');
        $inputExisting.classList.add('hide');
    }
}
function initDeliveryDate() {
    var _a, _b, _c;
    (_a = $qs('#existing-delivery-date')) === null || _a === void 0 ? void 0 : _a.addEventListener('change', checkDeliveryDateIsValid);
    (_b = $qs('#existing-delivery-date')) === null || _b === void 0 ? void 0 : _b.addEventListener('change', updateDeliveryDate);
    (_c = $qs('#delivery-date')) === null || _c === void 0 ? void 0 : _c.addEventListener('change', checkDeliveryDateIsValid);
    store.subscribe(function () {
        renderDeliveryDate();
    });
}
function collectDeliveryDate() {
    var _a, _b;
    return (_b = (_a = $qs('#delivery-date')) === null || _a === void 0 ? void 0 : _a.value) !== null && _b !== void 0 ? _b : '';
}
function renderDeliveryDate() {
    var _a, _b, _c, _d;
    if (!GLOBAL.phpData) {
        return;
    }
    if (!GLOBAL.phpData.plugin_woocommerce_order_delivery_active) {
        return;
    }
    (_a = $qs('#existing-checkout-delivery-date')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    (_b = $qs('#checkout-delivery-date')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    var todayDate = new Date();
    var maxDate = new Date();
    maxDate.setDate(todayDate.getDate() + ((_d = (_c = GLOBAL.phpData.plugin_woocommerce_order_delivery_options) === null || _c === void 0 ? void 0 : _c.wc_od_max_delivery_days) !== null && _d !== void 0 ? _d : 0));
    var $shippingDate = $qs('#delivery-date');
    var $existingCustomerShippingDate = $qs('#existing-delivery-date');
    if (!$shippingDate || !$existingCustomerShippingDate) {
        return;
    }
    $shippingDate.required = true;
    $existingCustomerShippingDate.min = todayDate.toISOString().slice(0, 10);
    $shippingDate.min = todayDate.toISOString().slice(0, 10);
    $existingCustomerShippingDate.max = maxDate.toISOString().slice(0, 10);
    $shippingDate.max = maxDate.toISOString().slice(0, 10);
}
function checkDeliveryDateIsEmpty() {
    var _a, _b;
    return ((_b = (_a = $qs('#existing-delivery-date')) === null || _a === void 0 ? void 0 : _a.value) !== null && _b !== void 0 ? _b : '') === '';
}
function updateDeliveryDate() {
    var _a, _b;
    var $deliveryDate = $qs('#delivery-date');
    if (!$deliveryDate) {
        return;
    }
    $deliveryDate.value = (_b = (_a = $qs('#existing-delivery-date')) === null || _a === void 0 ? void 0 : _a.value) !== null && _b !== void 0 ? _b : '';
}
function checkDeliveryDateIsValid(event) {
    if (!event.target) {
        return;
    }
    if (!validateDateIsAvailable(event.target.value)) {
        event.target.value = '';
        peachpayAlert('Please select another delivery date.');
    }
}
function validateDateIsAvailable(dateString) {
    var _a, _b;
    if (!GLOBAL.phpData) {
        return false;
    }
    var day = new Date(dateString + 'T00:00:00').getDay();
    return !((_b = (_a = GLOBAL.phpData.plugin_woocommerce_order_delivery_options) === null || _a === void 0 ? void 0 : _a.delivery_unchecked_day) === null || _b === void 0 ? void 0 : _b.includes(String(day)));
}
function initOrderNotes() {
    if (Feature.enabled(FeatureFlag.ORDER_NOTES_INPUT)) {
        for (var _i = 0, _a = $qsAll('.order-notes'); _i < _a.length; _i++) {
            var $form = _a[_i];
            $form.classList.remove('hide');
        }
    }
}
function collectOrderNotes() {
    var orderNotes = $qs('#order-notes');
    var orderNotesExisting = $qs('#order-notes-existing');
    if (orderNotes !== null && orderNotesExisting !== null) {
        if (orderNotes.value !== '' && orderNotesExisting.value === '') {
            return orderNotes.value;
        }
        if (orderNotes.value === '' && orderNotesExisting.value !== '') {
            return orderNotesExisting.value;
        }
    }
    return '';
}
function syncOrderNotes(exitModule) {
    if (exitModule === void 0) { exitModule = false; }
    var orderNotesExisting = $qs('#order-notes-existing');
    var orderNotes = $qs('#order-notes');
    if (orderNotes !== null && orderNotesExisting !== null) {
        if (Environment.customer.existing() && orderNotes.value !== '' && exitModule) {
            orderNotesExisting.value = orderNotes.value;
            orderNotes.value = '';
            return;
        }
        if (orderNotesExisting.value !== '' && !exitModule) {
            orderNotes.value = orderNotesExisting.value;
            orderNotesExisting.value = '';
        }
    }
}
function initVAT(message) {
    initVatEvents();
    if (message.phpData.vat_self_verify === '1') {
        renderVerifyLocation();
    }
    var vatTypesRequiringID = message.phpData.vat_required === '1' || message.phpData.vat_required === '2' && isEUCountry(PeachPayCustomer.country());
    if (vatTypesRequiringID) {
        renderVATIDInput();
    }
}
function initVatEvents() {
    var _a;
    (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.addEventListener('submit', function (event) {
        var _a, _b;
        event.preventDefault();
        var vatTypesRequiringID = ((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.vat_required) === '1' || ((_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.vat_required) === '2' && isEUCountry(PeachPayCustomer.country());
        if (vatTypesRequiringID) {
            renderVATIDInput();
        }
    });
}
function renderVATIDInput() {
    var $previousDivs = document.querySelector('#newEUVatDiv');
    $previousDivs === null || $previousDivs === void 0 ? void 0 : $previousDivs.remove();
    var $EUVatDiv = document.createElement('div');
    var $vatForm = document.createElement('form');
    var $vatNumber = document.createElement('input');
    $vatNumber.setAttribute('placeholder', 'required');
    $vatNumber.setAttribute('class', 'vatInput');
    var $prompt = document.createElement('span');
    $prompt.innerHTML = 'Vat Number';
    $vatForm.append($vatNumber);
    $EUVatDiv.append($prompt);
    $EUVatDiv.append($vatForm);
    $EUVatDiv.setAttribute('id', 'EuVatDiv');
    $EUVatDiv.setAttribute('class', 'color-change-text');
    var $insertionLocation;
    var $newCustomer = document.querySelector('#pp-new-customer-checkout');
    if ($newCustomer === null || $newCustomer === void 0 ? void 0 : $newCustomer.classList.contains('hide')) {
        $insertionLocation = document.querySelector('#existing-checkout-card');
        $vatNumber.setAttribute('id', 'ppVatNumExisting');
        $EUVatDiv.setAttribute('class', 'color-change-text');
        $insertionLocation === null || $insertionLocation === void 0 ? void 0 : $insertionLocation.insertAdjacentElement('afterend', $EUVatDiv);
    }
    else {
        $insertionLocation = document.querySelector('#payment-methods');
        $vatNumber.setAttribute('id', 'ppVatNumNew');
        $EUVatDiv.setAttribute('class', 'x-large');
        $EUVatDiv.setAttribute('id', 'newEUVatDiv');
        $insertionLocation === null || $insertionLocation === void 0 ? void 0 : $insertionLocation.insertAdjacentElement('afterend', $EUVatDiv);
    }
}
function getVatNumber() {
    var _a, _b;
    var $newCustomer = document.querySelector('#pp-new-customer-checkout');
    if ($newCustomer === null || $newCustomer === void 0 ? void 0 : $newCustomer.classList.contains('hide')) {
        var $ppVat_1 = document.querySelector('#ppVatNumExisting');
        if (!$ppVat_1) {
            return '';
        }
        return (_a = $ppVat_1.value) !== null && _a !== void 0 ? _a : '';
    }
    var $ppVat = document.querySelector('#ppVatNumNew');
    if (!$ppVat) {
        return '';
    }
    return (_b = $ppVat.value) !== null && _b !== void 0 ? _b : '';
}
function renderVerifyLocation() {
    var $verifyDiv = document.createElement('div');
    var $verifyCheckbox = document.createElement('input');
    var $descriptor = document.createElement('label');
    $verifyCheckbox.setAttribute('id', 'pp_verify_country');
    $verifyCheckbox.setAttribute('type', 'checkbox');
    $verifyCheckbox.setAttribute('value', '1');
    $descriptor.setAttribute('for', 'pp_verify_country');
    $descriptor.innerHTML = getLocaleText('I verify that the country I have entered is the one I reside in');
    $verifyDiv.append($verifyCheckbox);
    $verifyDiv.append($descriptor);
    var $divClone = $verifyDiv.cloneNode(true);
    var $insertionLocation = $qs('#existing-checkout-card');
    var $insertLocation2 = $qs('#payment-methods');
    $insertionLocation === null || $insertionLocation === void 0 ? void 0 : $insertionLocation.insertAdjacentElement('afterend', $verifyDiv);
    $insertLocation2 === null || $insertLocation2 === void 0 ? void 0 : $insertLocation2.insertAdjacentElement('afterend', $divClone);
}
function getVerify() {
    var $isVerified = document.querySelectorAll('#pp_verify_country');
    if ($isVerified[0].checked || $isVerified[1].checked) {
        return '1';
    }
    return '';
}
function initAddionalFields(message) {
    var _a, _b, _c, _d;
    if (Feature.enabled(FeatureFlag.ADDITIONAL_FIELDS)) {
        renderAdditionalFields((_b = (_a = message.phpData) === null || _a === void 0 ? void 0 : _a.additional_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.additional_fields_order) !== null && _d !== void 0 ? _d : []);
    }
}
function syncFields(event) {
    var $form = event.target.closest('form');
    var fieldRecord = {};
    for (var _i = 0, _a = Array.from($form.elements); _i < _a.length; _i++) {
        var $input = _a[_i];
        if ($input.type === 'radio') {
            if ($input.checked) {
                fieldRecord[$input.name] = $input.value;
            }
            else {
                continue;
            }
        }
        else {
            fieldRecord[$input.name] = $input.value;
        }
    }
    store.dispatch(setExtraFields(fieldRecord));
}
function renderAdditionalFields(fieldData, fieldOrder) {
    var _a, _b, _c, _d;
    if (fieldData.length === 0 || fieldOrder.length === 0) {
        return;
    }
    $qsAll('#additional-fields-new, #additional-fields-existing', function ($element) {
        $element.addEventListener('submit', function (e) {
            e.preventDefault();
            return false;
        });
    });
    (_a = $qs('#additional-fields-new')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    (_b = $qs('#additional-fields-existing')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    for (var _i = 0, fieldOrder_1 = fieldOrder; _i < fieldOrder_1.length; _i++) {
        var i = fieldOrder_1[_i];
        if (fieldData[i].field_enable) {
            generateFields(fieldData[i]);
        }
    }
    (_c = $qs('#additional-fields-new')) === null || _c === void 0 ? void 0 : _c.addEventListener('change', syncFields);
    (_d = $qs('#additional-fields-existing')) === null || _d === void 0 ? void 0 : _d.addEventListener('change', syncFields);
    store.subscribe(function () {
        renderExtraFields(PeachPayOrder.extraFieldsRecord());
    });
}
function renderExtraFields(extraFieldData) {
    var _a;
    var _loop_1 = function (key, value) {
        if (((_a = $qs("[name=\"".concat(key, "\"].extra-field"))) === null || _a === void 0 ? void 0 : _a.type) === 'radio') {
            $qsAll("[name=\"".concat(key, "\"].extra-field"), function ($element) { return $element.checked = false; });
            $qsAll("[name=\"".concat(key, "\"][value=\"").concat(value, "\"].extra-field"), function ($element) { return $element.checked = true; });
        }
        else {
            $qsAll("[name=\"".concat(key, "\"].extra-field"), function ($element) { return $element.value = value; });
        }
    };
    for (var _i = 0, _b = Object.entries(extraFieldData); _i < _b.length; _i++) {
        var _c = _b[_i], key = _c[0], value = _c[1];
        _loop_1(key, value);
    }
}
function generateFields(fieldData) {
    var field = function (location) { return '<div class="new-field' + (fieldData.type_list === 'radio' ? ' flex-col' : ' flex') + '">' + generateFieldElement(location, fieldData) + '</div>'; };
    var newPageElements = document.querySelector('#additional-fields-new');
    var exsitPageElement = document.querySelector('#additional-fields-existing');
    if (newPageElements) {
        newPageElements.innerHTML += field('-new');
    }
    if (exsitPageElement) {
        exsitPageElement.innerHTML += field('-existing');
    }
}
function generateFieldElement(location1, fieldData) {
    var _a, _b;
    var elementString = '';
    var optional = '<span class="optional"> (optional) </span>';
    var required = '<abbr class="required" title="required" style="color:red;">*</abbr>';
    var labelBuilder = function (location) { return "\n\t\t<label for=\"".concat(fieldData.field_name).concat(location, "\" class=\"pp-form-label-").concat(fieldData.type_list, "\" >") + "".concat(fieldData.field_label) + (fieldData.field_required ? required : optional) + '</label>'; };
    var inputBuilder = function (location) { return "<input type=".concat(fieldData.type_list, " \n\t\t\tname=").concat(fieldData.field_name, " \n\t\t\tid=\"").concat(fieldData.field_name).concat(location, "\"\n\t\t\tplaceholder=\" \"") + (fieldData.field_default ? "value=\"".concat(fieldData.field_default, "\"") : '') + "class=\"pp-input-box-".concat(fieldData.type_list) + (location === '-new' ? ' new-text' : '') + " w-100 extra-field\"" + (fieldData.field_required ? 'required' : '') + '/>'; };
    var selectBuilder = function (location, optionOrder) { return "\n\t\t<div class=\"flex w-100\">\n\t\t\t<select name=".concat(fieldData.field_name, " \n\t\t\tid=\"").concat(fieldData.field_name).concat(location, "\"\n\t\t\tclass=\"pp-").concat(fieldData.type_list, "-box") + (location === '-new' ? ' new-text' : '') + " w-100 extra-field\"" + (fieldData.field_required ? 'required' : '') + ">" + optionBuilder(optionOrder) + "</select>" + labelBuilder(location) + "</div>\n\t\t"; };
    var optionBuilder = function (optionOrder) {
        if (optionOrder.length === 0) {
            return;
        }
        var optionList = '<option value="">Please Select</option>';
        optionOrder.forEach(function (value) {
            if (value[0] && value[1]) {
                optionList += "<option value=\"".concat(value[0], "\">").concat(value[1], "</option>");
            }
        });
        return optionList;
    };
    var radioFieldBuilder = function (location, optionOrder) {
        var radioFields = "<div><div>" + (fieldData.field_label ? labelBuilder(location) : '') + "</div>";
        radioFields += '<div class="new-field-text">';
        optionOrder.forEach(function (value) {
            if (value[0] && value[1]) {
                radioFields += "<input type=".concat(fieldData.type_list, " \n\t\t\t\tname=").concat(fieldData.field_name, " \n\t\t\t\tid=\"").concat(fieldData.field_name).concat(location, "-").concat(value[0], "\"") + "value=\"".concat(value[0], "\"") + "class=\"input-".concat(fieldData.type_list, " extra-field\"") + (fieldData.field_required ? 'required' : '') + '/>';
                radioFields += "<label for=\"".concat(fieldData.field_name).concat(location, "-").concat(value[0], "\">").concat(value[1], "</label><br>");
            }
        });
        radioFields += '</div></div>';
        return radioFields;
    };
    if (fieldData.type_list === 'text') {
        elementString = inputBuilder(location1) + (fieldData.field_label ? labelBuilder(location1) : '');
        return elementString;
    }
    else if (fieldData.type_list === 'select') {
        elementString += selectBuilder(location1, (_a = fieldData.option_order) !== null && _a !== void 0 ? _a : []);
        return elementString;
    }
    else if (fieldData.type_list === 'radio') {
        elementString += radioFieldBuilder(location1, (_b = fieldData.option_order) !== null && _b !== void 0 ? _b : []);
        return elementString;
    }
    return elementString;
}
function collectAdditionalFieldData(fieldData, fieldOrder) {
    var _a, _b, _c, _d;
    var fieldDataArray = [];
    for (var _i = 0, fieldOrder_2 = fieldOrder; _i < fieldOrder_2.length; _i++) {
        var orderNumber = fieldOrder_2[_i];
        var temporaryData = {
            name: ''
        };
        temporaryData.label = fieldData[orderNumber].field_label;
        temporaryData.name = fieldData[orderNumber].field_name;
        if (fieldData[orderNumber].field_enable && ((_a = $qs("#".concat(fieldData[orderNumber].field_name, "-existing"))) === null || _a === void 0 ? void 0 : _a.value)) {
            temporaryData.value = (_b = $qs("#".concat(fieldData[orderNumber].field_name, "-existing"))) === null || _b === void 0 ? void 0 : _b.value;
            fieldDataArray.push(temporaryData);
        }
        if (fieldData[orderNumber].type_list === 'radio' && fieldData[orderNumber].field_enable) {
            if ((_c = $qs("input[name=".concat(fieldData[orderNumber].field_name, "]:checked"))) === null || _c === void 0 ? void 0 : _c.value) {
                temporaryData.value = (_d = $qs("input[name=".concat(fieldData[orderNumber].field_name, "]:checked"))) === null || _d === void 0 ? void 0 : _d.value;
                fieldDataArray.push(temporaryData);
            }
        }
    }
    return fieldDataArray;
}
function checkRequiredFields() {
    var _a, _b, _c, _d;
    if (Environment.customer.existing()) {
        return (_b = (_a = $qs('#additional-fields-existing')) === null || _a === void 0 ? void 0 : _a.reportValidity()) !== null && _b !== void 0 ? _b : false;
    }
    return (_d = (_c = $qs('#additional-fields-new')) === null || _c === void 0 ? void 0 : _c.reportValidity()) !== null && _d !== void 0 ? _d : false;
}
function captureSentryException(error, extra, fingerprint) {
    try {
        Sentry.withScope(function (scope) {
            if (!extra) {
                extra = {};
            }
            extra['session_id'] = PeachPayOrder.sessionId();
            extra['merchant_name'] = MerchantConfiguration.name();
            extra['merchant_url'] = MerchantConfiguration.hostName();
            extra['plugin_version'] = Environment.plugin.version();
            try {
                Object.entries(extra).map(function (_a) {
                    var key = _a[0], value = _a[1];
                    return scope.setExtra(key, value);
                });
            }
            catch (_a) { }
            if (fingerprint) {
                try {
                    scope.setFingerprint(fingerprint);
                }
                catch (_b) { }
            }
            Sentry.captureException(error);
        });
    }
    catch (_a) { }
}
function initSentry(message) {
    try {
        if (window['Sentry']) {
            var isDev = isDevEnvironment(getBaseURL(message.merchantHostname, message.isTestMode));
            Sentry.init({
                dsn: 'https://39b5a2e17e264bb5a6ea5abe9bc6cf61@o470066.ingest.sentry.io/5660513',
                environment: isDev ? 'development' : 'production',
                release: "peachpay-plugin@".concat(message.phpData.version),
                ignoreErrors: [
                    'TypeError: Failed to fetch',
                    'TypeError: NetworkError when attempting to fetch resource.',
                    'TypeError: cancelled',
                    'TypeError: cancelado',
                    'TypeError: Abgebrochen',
                    'TypeError: annulé',
                    'Window navigated away',
                    'annullato',
                    'Load failed',
                ]
            });
        }
    }
    catch (_a) { }
}
function uuidv4() {
    var d = new Date().getTime(), d2 = performance && performance.now && performance.now() * 1000 || 0;
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16;
        if (d > 0) {
            r = (d + r) % 16 | 0;
            d = Math.floor(d / 16);
        }
        else {
            r = (d2 + r) % 16 | 0;
            d2 = Math.floor(d2 / 16);
        }
        return (c == 'x' ? r : r & 7 | 8).toString(16);
    });
}
var PEACHPAY_SESSION_KEY = 'PP_SESSION_KEY';
function createSession() {
    var key = "session_".concat(uuidv4());
    return {
        key: key,
        created_on: new Date().toJSON()
    };
}
function getSession() {
    var getData = function () {
        try {
            return localStorage.getItem(PEACHPAY_SESSION_KEY);
        }
        catch (_a) {
            return null;
        }
    };
    var sessionData = getData();
    var parseSession = function (data) {
        if (!data) {
            return createSession();
        }
        var session = JSON.parse(data);
        var currentDate = new Date();
        var sessionDate = new Date(session.created_on);
        if (currentDate.getMilliseconds() - sessionDate.getMilliseconds() > 432000000) {
            return createSession();
        }
        return session;
    };
    return parseSession(sessionData);
}
function saveSession(session) {
    try {
        localStorage.setItem(PEACHPAY_SESSION_KEY, JSON.stringify(session));
        return true;
    }
    catch (_a) {
        return false;
    }
}
function loadSession() {
    var session = getSession();
    store.dispatch(setSessionId(session.key));
    saveSession(session);
}
function clearLocalSession() {
    try {
        localStorage.removeItem(PEACHPAY_SESSION_KEY);
    }
    catch (_a) { }
}
function getOrderService() {
    return {
        placeOrder: placeOrder,
        setOrderStatus: setOrderStatus,
        addOrderNote: addOrderNote,
        getOrderRedirect: getOrderRedirect,
        setPaymentStatus: recordSuccessfulPayment,
        deprecated: {
            placeOrder: legacyPlaceOrder,
            setOrderStatus: legacySetOrderStatus
        }
    };
}
function initShipping(message) {
    initShippingEvents();
    store.dispatch(updateMerchantGeneralConfig(__assign(__assign({}, store.getState().merchantConfiguration.general), { wcLocationInfoData: message.phpData.wc_location_info })));
    store.dispatch(updateMerchantShippingConfig({
        shippingZones: Number.parseInt(message.phpData.num_shipping_zones)
    }));
}
function setOrderStatus(order, status, options) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var request;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    request = {
                        session: {
                            id: PeachPayOrder.sessionId()
                        },
                        order: {
                            id: order.order_id,
                            status: status,
                            message: (_a = options.message) !== null && _a !== void 0 ? _a : '',
                            paymentMethod: PaymentConfiguration.selectedProvider(),
                            stripeCustomerId: PaymentConfiguration.selectedProvider() === 'stripe' ? options.stripeCustomerId : undefined,
                            paypalTransactionId: PaymentConfiguration.selectedProvider() === 'paypal' ? options.paypalTransactionId : undefined,
                            stripeTransactionId: PaymentConfiguration.selectedProvider() === 'stripe' ? options.stripeTransactionId : undefined
                        }
                    };
                    return [4, fetchHostWindowData('pp-set-order-status', request)];
                case 1:
                    if (_b.sent()) {
                        return [2, order.redirect];
                    }
                    return [2, ''];
            }
        });
    });
}
function addOrderNote(order, note) {
    return __awaiter(this, void 0, void 0, function () {
        var request;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    request = {
                        id: order.order_id,
                        note: note
                    };
                    return [4, fetchHostWindowData('pp-add-order-note', request)];
                case 1: return [2, _a.sent()];
            }
        });
    });
}
function placeOrder() {
    var _a, _b, _c, _d, _e, _f, _g;
    return __awaiter(this, void 0, void 0, function () {
        var requestMessage, response;
        return __generator(this, function (_h) {
            switch (_h.label) {
                case 0:
                    requestMessage = {
                        session: {
                            id: PeachPayOrder.sessionId()
                        },
                        order: {
                            paymentMethod: PaymentConfiguration.selectedProvider(),
                            billingAddress: PeachPayCustomer.wcBillingAddress(),
                            shippingAddress: PeachPayCustomer.wcShippingAddress(),
                            shippingMethods: PeachPayOrder.collectSelectedShipping(),
                            deliveryDate: collectDeliveryDate(),
                            merchantCustomerAccountPassword: '',
                            vatNum: '',
                            vatSelfVerify: '',
                            customerOrderNotes: collectOrderNotes(),
                            additionalFields: Feature.enabled(FeatureFlag.ADDITIONAL_FIELDS) ? collectAdditionalFieldData((_b = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.additional_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.additional_fields_order) !== null && _d !== void 0 ? _d : []) : []
                        }
                    };
                    if (shouldShowMerchantCustomerAccountPasswordField()) {
                        requestMessage.order.merchantCustomerAccountPassword = getMerchantCustomerAccountPasswordValue();
                    }
                    if ((_e = GLOBAL.phpData) === null || _e === void 0 ? void 0 : _e.vat_required) {
                        requestMessage.order.vatNum = getVatNumber();
                    }
                    if ((_f = GLOBAL.phpData) === null || _f === void 0 ? void 0 : _f.vat_self_verify) {
                        requestMessage.order.vatSelfVerify = getVerify();
                    }
                    return [4, fetchHostWindowData('pp-place-order', requestMessage)];
                case 1:
                    response = _h.sent();
                    if (response.result === 'failure') {
                        store.dispatch(setOrderError((_g = response.message) !== null && _g !== void 0 ? _g : getLocaleText('Unknown order error occurred')));
                    }
                    return [2, response];
            }
        });
    });
}
function legacyPlaceOrder(isPaypal) {
    var _a, _b, _c, _d, _e, _f, _g;
    if (((_a = GLOBAL === null || GLOBAL === void 0 ? void 0 : GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.plugin_woocommerce_order_delivery_active) && checkDeliveryDateIsEmpty()) {
        peachpayAlert('Please select delivery date.');
        store.dispatch(stopModalLoading());
        return;
    }
    var message = {
        'event': 'placeOrderDirectly',
        'sessionID': PeachPayOrder.sessionId(),
        'billingAddress': PeachPayCustomer.wcBillingAddress(),
        'shippingAddress': PeachPayCustomer.wcShippingAddress(),
        'shipping_method': PeachPayOrder.collectSelectedShipping(),
        'deliveryDate': collectDeliveryDate(),
        'isProductPageButton': Environment.plugin.pageType() === 'product',
        'isPaypal': isPaypal !== null && isPaypal !== void 0 ? isPaypal : false,
        'merchantCustomerAccountPassword': '',
        'vatNum': '',
        'selfVerify': '',
        'customerOrderNotes': collectOrderNotes(),
        'additionalFields': Feature.enabled(FeatureFlag.ADDITIONAL_FIELDS) ? collectAdditionalFieldData((_c = (_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.additional_fields) !== null && _c !== void 0 ? _c : [], (_e = (_d = GLOBAL.phpData) === null || _d === void 0 ? void 0 : _d.additional_fields_order) !== null && _e !== void 0 ? _e : []) : [],
        'upsell_items': GLOBAL.linkedProductsIds
    };
    if (shouldShowMerchantCustomerAccountPasswordField()) {
        if (!validateMerchantCustomerPasswordField()) {
            store.dispatch(stopModalLoading());
            return;
        }
        message.merchantCustomerAccountPassword = getMerchantCustomerAccountPasswordValue();
    }
    if ((_f = GLOBAL.phpData) === null || _f === void 0 ? void 0 : _f.vat_required) {
        message.vatNum = getVatNumber();
    }
    if ((_g = GLOBAL.phpData) === null || _g === void 0 ? void 0 : _g.vat_self_verify) {
        message.selfVerify = getVerify();
    }
    clearLocalSession();
    window.parent.postMessage(message, '*');
}
function getOrderRedirect(order) {
    var _a;
    var url = order.redirect;
    var orderId = order.order_id;
    var orderKey = order.order_key;
    if ((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.plugin_woo_thank_you_page_nextmove_lite_active) {
        return "".concat(url.replace('/checkout', ''), "/thank-you/?order_id=").concat(orderId, "&key=").concat(orderKey);
    }
    if (MerchantConfiguration.hostName() === 'rapidfiresupplies.co.uk') {
        return "https://".concat(MerchantConfiguration.hostName(), "/thank-you-for-purchasing-from-us/");
    }
    return order.redirect;
}
function requestCartCalculation(initial) {
    if (initial === void 0) { initial = false; }
    return __awaiter(this, void 0, void 0, function () {
        var requestData, response, error_2;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    requestData = initial ? {
                        initial: true
                    } : {
                        order: {
                            selected_shipping: PeachPayOrder.collectSelectedShipping(),
                            shipping_location: PeachPayCustomer.shortAddress()
                        }
                    };
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    return [4, fetchHostWindowData('pp-calculate-cart', requestData)];
                case 2:
                    response = _a.sent();
                    consumeCartCalculationResponse(response);
                    return [3, 4];
                case 3:
                    error_2 = _a.sent();
                    if (error_2 instanceof Error) {
                        captureSentryException(new Error("Cart calculation V2 failed on ".concat(MerchantConfiguration.hostName(), ". Error: ").concat(error_2.message)));
                    }
                    return [3, 4];
                case 4: return [2];
            }
        });
    });
}
function initShippingEvents() {
    var _this = this;
    var _a, _b, _c;
    store.subscribe(renderShipping);
    onWindowMessage('validateAddressSuccess', function () { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, transitionToPaymentPage()];
                case 1:
                    _a.sent();
                    return [2];
            }
        });
    }); });
    (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.addEventListener('submit', function (event) { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    event.preventDefault();
                    store.dispatch(startModalLoading());
                    if (!(Carts.virtual() && Feature.enabled(FeatureFlag.VIRTUAL_PRODUCT_FIELDS))) return [3, 2];
                    return [4, transitionToPaymentPage()];
                case 1:
                    _a.sent();
                    return [3, 3];
                case 2:
                    window.parent.postMessage({
                        event: 'validateAddress',
                        billingAddress: PeachPayCustomer.wcBillingAddress()
                    }, '*');
                    _a.label = 3;
                case 3: return [2];
            }
        });
    }); });
    (_b = $qs('#pp-shipping-options-existing')) === null || _b === void 0 ? void 0 : _b.addEventListener('change', handleShippingSelectionEvent);
    (_c = $qs('#pp-shipping-options')) === null || _c === void 0 ? void 0 : _c.addEventListener('change', handleShippingSelectionEvent);
}
function transitionToPaymentPage() {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, requestCartCalculation()];
                case 1:
                    _a.sent();
                    if (!PeachPayOrder.errorMessage()) {
                        store.dispatch(updateCustomerAddressValidation(true));
                        store.dispatch(updateEnvironment({
                            modalPageType: 'payment'
                        }));
                    }
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    });
}
function validateAddress() {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, fetchHostWindowData('pp-validate-billing-address', PeachPayCustomer.wcBillingAddress())];
                case 1: return [2, _a.sent()];
            }
        });
    });
}
function handleShippingSelectionEvent(event) {
    return __awaiter(this, void 0, void 0, function () {
        var $target, $targetContainer, shippingMethodId, cartKey, packageKey;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    $target = event.target;
                    $targetContainer = $target.closest('[data-cart-key]');
                    shippingMethodId = $target.value;
                    cartKey = $targetContainer.dataset.cartKey;
                    packageKey = $targetContainer.dataset.packageKey;
                    store.dispatch(updateCartPackageShippingMethod({
                        cartKey: cartKey !== null && cartKey !== void 0 ? cartKey : '',
                        shippingPackageKey: packageKey !== null && packageKey !== void 0 ? packageKey : '',
                        packageMethodId: shippingMethodId !== null && shippingMethodId !== void 0 ? shippingMethodId : ''
                    }));
                    store.dispatch(startModalLoading());
                    return [4, requestCartCalculation()];
                case 1:
                    _a.sent();
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    });
}
function renderShipping() {
    var _a, _b;
    renderOrderHeader(DefaultCart.contents());
    if (cartIsVirtual(DefaultCart.contents())) {
        (_a = $qs('#existing-checkout-delivery')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
    }
    else {
        (_b = $qs('#existing-checkout-delivery')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
        renderCartShippingOptions(store.getState().calculatedCarts);
    }
    renderShippingSection();
}
function renderOrderHeader(cart) {
    var _a, _b, _c, _d;
    if (cartIsVirtual(cart)) {
        (_a = $qs('.shipping-address-header')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
        (_b = $qs('.billing-address-header')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
        for (var _i = 0, _e = $qsAll('.hide-for-virtual-carts'); _i < _e.length; _i++) {
            var $element = _e[_i];
            $element.classList.add('hide');
        }
        for (var _f = 0, _g = $qsAll('.show-for-virtual-carts'); _f < _g.length; _f++) {
            var $element1 = _g[_f];
            $element1.classList.remove('hide');
        }
    }
    else {
        (_c = $qs('.shipping-address-header')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
        (_d = $qs('.billing-address-header')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
        for (var _h = 0, _j = $qsAll('.hide-for-virtual-carts'); _h < _j.length; _h++) {
            var $element = _j[_h];
            $element.classList.remove('hide');
        }
        for (var _k = 0, _l = $qsAll('.show-for-virtual-carts'); _k < _l.length; _k++) {
            var $element2 = _l[_k];
            $element2.classList.add('hide');
        }
    }
}
function renderCartShippingOptions(calculatedCarts) {
    var shippingHTML = '';
    for (var _i = 0, _a = Object.entries(calculatedCarts); _i < _a.length; _i++) {
        var _b = _a[_i], cartKey = _b[0], cartCalculation = _b[1];
        if (!cartCalculation) {
            continue;
        }
        for (var _c = 0, _d = Object.entries(cartCalculation.package_record); _c < _d.length; _c++) {
            var _e = _d[_c], shippingPackageKey = _e[0], shippingPackage = _e[1];
            if (!shippingPackage) {
                continue;
            }
            shippingHTML += renderShippingPackageOptions(cartKey, shippingPackageKey, shippingPackage, cartCalculation.cart_meta);
        }
    }
    $qs('#pp-shipping-options', function ($element) { return $element.innerHTML = shippingHTML; });
    $qs('#pp-shipping-options-existing', function ($element) { return $element.innerHTML = shippingHTML; });
}
function renderShippingPackageOptions(cartKey, shippingPackageKey, shippingPackage, cartMeta) {
    var _a;
    var packageMethodKey = cartKey === '0' ? shippingPackageKey : "".concat(cartKey, "_").concat(shippingPackageKey);
    var methodOptionBuilder = function (methodKey, method, selected) { return "\n<label class=\"pp-disabled-loading\" for=\"shipping_method_".concat(packageMethodKey, "_").concat(methodKey.replace(/:/g, ''), "\" style=\"margin: 0 0 3px 0; display: flex; flex-direction: row; cursor: pointer;\">\n\t<input class=\"pp-disabled-loading\" style=\"margin: 6px 0px 0px 0px;\" id=\"shipping_method_").concat(packageMethodKey, "_").concat(methodKey.replace(/:/g, ''), "\" name=\"shipping_method[").concat(packageMethodKey, "]\" value=\"").concat(methodKey, "\" type=\"radio\" ").concat(selected ? 'checked' : '', " required>\n\t<span style=\"display: inline-block; flex-grow: 1; margin-left: 8px;\">").concat(method.title, "</span>\n\t<span style=\"display: inline-block; min-width: 30%; text-align: right;\" class=\"shipping-price pp-currency-blur\">").concat(formatCurrencyString(method.total), "<span class=\"muted\">").concat(buildSubscriptionPriceMetaData(cartMeta), "</span></span>\n</label>"); };
    var packageNameHTML = "<h4 class=\"shipping-header color-change-text\">".concat((_a = shippingPackage.package_name) !== null && _a !== void 0 ? _a : getLocaleText('shipping'), "</h4>");
    var packageMethodOptionsHTML = Object.entries(shippingPackage.methods).map(function (_a) {
        var shippingMethodKey = _a[0], shippingMethod = _a[1];
        return shippingMethod ? methodOptionBuilder(shippingMethodKey, shippingMethod, shippingPackage.selected_method === shippingMethodKey) : '';
    }).join('');
    return "\n<div data-cart-key=\"".concat(cartKey, "\" data-package-key=\"").concat(shippingPackageKey, "\">\n\t").concat(packageNameHTML, "\n\t").concat(packageMethodOptionsHTML, "\n</div>");
}
function shippingIsValid() {
    if (cartIsVirtual(DefaultCart.contents())) {
        return true;
    }
    if (MerchantConfiguration.shipping.shippingZones() === 0) {
        return true;
    }
    if (!Carts.anyShippingMethodsAvailable()) {
        return false;
    }
    return true;
}
function consumeCartCalculationResponse(response) {
    if (response.notices) {
        if (response.notices.error) {
            var cartErrors = '';
            for (var i = 0; i < response.notices.error.length; i++) {
                cartErrors += response.notices.error[i].notice;
            }
            store.dispatch(setOrderError(cartErrors));
        }
        if (response.notices.success) {
            for (var i = 0; i < response.notices.success.length; i++) {
                renderOrderNotice(response.notices.success[i].notice);
            }
        }
        if (response.notices.notice) {
            for (var i = 0; i < response.notices.notice.length; i++) {
                renderOrderNotice(response.notices.notice[i].notice);
            }
        }
    }
    if (response.data) {
        store.dispatch(setOrderError(''));
        store.dispatch(updateCartCalculation(response.data.cart_calculation_record));
        store.dispatch(updateCustomerShippingShortAddress(response.data.shipping_location));
        if (DefaultCart.contents().length === 0) {
            store.dispatch(setOrderError("<span>".concat(getLocaleText('Cart is empty'), "</span>")));
        }
        else if (!shippingIsValid()) {
            if (PeachPayCustomer.country() === '' || PeachPayCustomer.city() === '' || PeachPayCustomer.postal() === '') {
                return;
            }
            store.dispatch(setOrderError("<span>".concat(getLocaleText('Sorry, this store does not ship to your location.'), "</span>")));
        }
    }
}
function stripHtml(html) {
    var temporalDivElement = document.createElement('div');
    temporalDivElement.innerHTML = html;
    temporalDivElement.querySelectorAll('a').forEach(function ($el) { return $el.remove(); });
    return temporalDivElement.textContent || temporalDivElement.innerText || '';
}
function renderOrderNotice(data) {
    var _a, _b, _c;
    var message = stripHtml(data);
    var buildNotice = function (divStyle) {
        if (divStyle === void 0) { divStyle = ''; }
        return "<div style=\"".concat(divStyle, "\" class=\"pp-notice\">").concat(message, "</div>");
    };
    $qsAll('.pp-notice-container').forEach(function (element) {
        element.classList.remove('hide');
        if (window.innerWidth > 900 && element.id === 'pp-notice-container-mobile') {
            element.classList.add('hide');
        }
        setTimeout(function () {
            element.classList.add('hide');
        }, 10050);
    });
    (_a = $qs('#pp-notice-container-existing')) === null || _a === void 0 ? void 0 : _a.insertAdjacentHTML('afterbegin', buildNotice());
    (_b = $qs('#pp-notice-container-new')) === null || _b === void 0 ? void 0 : _b.insertAdjacentHTML('afterbegin', buildNotice());
    if (Environment.customer.mobile() || window.innerWidth <= 900) {
        (_c = $qs('#pp-notice-container-mobile')) === null || _c === void 0 ? void 0 : _c.insertAdjacentHTML('afterbegin', buildNotice());
    }
    $qsAll('.pp-notice', function ($el) { return setTimeout(function () {
        $el === null || $el === void 0 ? void 0 : $el.remove();
    }, 10000); });
}
function recordSuccessfulPayment(sessionID, clearSession) {
    return fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/session/pay/record', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            sessionID: sessionID,
            clearSession: clearSession || false
        })
    });
}
function legacySetOrderStatus(order, _a) {
    var status = _a.status, message = _a.message, paymentType = _a.paymentType, transactionID = _a.transactionID;
    window.parent.postMessage({
        event: 'setOrderStatus',
        orderID: order.order_id,
        status: status,
        message: message,
        paymentType: paymentType,
        transactionID: transactionID,
        customerStripeId: PeachPayCustomer.stripeId(),
        redirectURL: order.redirect
    }, '*');
}
function renderShippingSection() {
    var _a, _b;
    if (PeachPayOrder.customerAddressValidated()) {
        (_a = $qs('#pp-shipping-payment')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    }
    else {
        (_b = $qs('#pp-shipping-payment')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
    }
}
function initGiftCardInput(_message) {
    var _a;
    if (!Feature.enabled(FeatureFlag.GIFTCARD_INPUT) || MerchantConfiguration.hostName() === 'skregear.com') {
        return;
    }
    initGiftCardEvents();
    for (var _i = 0, _b = $qsAll('.gift-card-option'); _i < _b.length; _i++) {
        var $form = _b[_i];
        $form.classList.remove('hide');
    }
    (_a = $qs('#gift-card-section')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    if (Feature.enabled(FeatureFlag.COUPON_INPUT)) {
        $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '25rem'; });
    }
    else {
        $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '23.5rem'; });
    }
}
function initGiftCardEvents() {
    var _this = this;
    onWindowMessage('giftCardApplied', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var _i, _a, $message, _b, _c, $message;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0:
                    for (_i = 0, _a = $qsAll('.invalid-gift-card'); _i < _a.length; _i++) {
                        $message = _a[_i];
                        $message.classList.add('hide');
                    }
                    if (!message.success) {
                        hideGiftCardLoadingState();
                        for (_b = 0, _c = $qsAll('.invalid-gift-card'); _b < _c.length; _b++) {
                            $message = _c[_b];
                            $message.textContent = message.message;
                            $message.classList.remove('hide');
                        }
                        store.dispatch(stopModalLoading());
                        return [2];
                    }
                    clearInput('.gift-card-input');
                    return [4, requestCartCalculation()];
                case 1:
                    _d.sent();
                    hideGiftCardLoadingState();
                    store.dispatch(stopModalLoading());
                    hideGiftCardLoadingState();
                    return [2];
            }
        });
    }); });
    var _loop_2 = function ($form) {
        $form.addEventListener('submit', function (event) {
            var _a, _b;
            event.preventDefault();
            if (!$form.checkValidity()) {
                return;
            }
            showGiftCardLoadingState();
            store.dispatch(startModalLoading());
            var data = new FormData(event.target);
            var giftCardNumber = (_b = (_a = data.get('card_number')) === null || _a === void 0 ? void 0 : _a.trim()) !== null && _b !== void 0 ? _b : '';
            window.parent.postMessage({
                event: 'redeemGiftCard',
                cardNumber: giftCardNumber
            }, '*');
        });
    };
    for (var _i = 0, _a = $qsAll('form.pw-wc-gift-card'); _i < _a.length; _i++) {
        var $form = _a[_i];
        _loop_2($form);
    }
    for (var _b = 0, _c = $qsAll('.gift-card-option'); _b < _c.length; _b++) {
        var $div = _c[_b];
        $div.addEventListener('click', showGiftCardInput);
    }
    for (var _d = 0, _e = $qsAll('.exit-button-gift'); _d < _e.length; _d++) {
        var $exitGift = _e[_d];
        $exitGift.addEventListener('click', hideGiftCardInput);
    }
}
function showGiftCardInput() {
    for (var _i = 0, _a = $qsAll('form.pw-wc-gift-card'); _i < _a.length; _i++) {
        var $coupon = _a[_i];
        $coupon.classList.remove('hide');
    }
    for (var _b = 0, _c = $qsAll('.gift-card-option'); _b < _c.length; _b++) {
        var $option = _c[_b];
        $option.classList.add('hide');
    }
}
function hideGiftCardInput() {
    for (var _i = 0, _a = $qsAll('form.pw-wc-gift-card'); _i < _a.length; _i++) {
        var $coupon = _a[_i];
        $coupon.classList.add('hide');
    }
    for (var _b = 0, _c = $qsAll('.gift-card-option'); _b < _c.length; _b++) {
        var $option = _c[_b];
        $option.classList.remove('hide');
    }
    for (var _d = 0, _e = $qsAll('.invalid-gift-card'); _d < _e.length; _d++) {
        var $invalid = _e[_d];
        $invalid.classList.add('hide');
    }
    clearInput('.gift-card-input');
}
function hideGiftCardLoadingState() {
    for (var _i = 0, _a = $qsAll('.gift-card-spinner'); _i < _a.length; _i++) {
        var $spinner = _a[_i];
        $spinner.classList.add('hide');
    }
    for (var _b = 0, _c = $qsAll('.gift-card-input'); _b < _c.length; _b++) {
        var $border = _c[_b];
        $border.classList.remove('remove-right-border');
    }
    for (var _d = 0, _e = $qsAll('.gift-card-apply'); _d < _e.length; _d++) {
        var $applyButton = _e[_d];
        $applyButton.disabled = false;
    }
}
function showGiftCardLoadingState() {
    for (var _i = 0, _a = $qsAll('.gift-card-spinner'); _i < _a.length; _i++) {
        var $spinner = _a[_i];
        $spinner.classList.remove('hide');
    }
    for (var _b = 0, _c = $qsAll('.gift-card-input'); _b < _c.length; _b++) {
        var $border = _c[_b];
        $border.classList.add('remove-right-border');
    }
    for (var _d = 0, _e = $qsAll('.gift-card-apply'); _d < _e.length; _d++) {
        var $applyButton = _e[_d];
        $applyButton.disabled = true;
    }
}
function initCouponInput(_message) {
    if (!Feature.enabled(FeatureFlag.COUPON_INPUT)) {
        return;
    }
    showCouponEntrySupport();
    initCouponInputEvents();
}
function initCouponInputEvents() {
    var _this = this;
    onWindowMessage('coupon', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var _i, _a, $message, _b, _c, $message;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0:
                    for (_i = 0, _a = $qsAll('.wc-invalid-coupon'); _i < _a.length; _i++) {
                        $message = _a[_i];
                        $message.classList.add('hide');
                    }
                    if (message.data && message.data.status === 404) {
                        hideCouponLoadingState();
                        for (_b = 0, _c = $qsAll('.wc-invalid-coupon'); _b < _c.length; _b++) {
                            $message = _c[_b];
                            $message.classList.remove('hide');
                        }
                        return [2];
                    }
                    store.dispatch(startModalLoading());
                    return [4, requestCartCalculation()];
                case 1:
                    _d.sent();
                    store.dispatch(stopModalLoading());
                    hideCouponLoadingState();
                    clearInput('.wc-coupon-code-input');
                    return [2];
            }
        });
    }); });
    onWindowMessage('stopCouponLoading', function (_) {
        store.dispatch(stopModalLoading());
        hideCouponLoadingState();
    });
    var _loop_3 = function ($form) {
        $form.addEventListener('submit', function (event) {
            var _a, _b, _c;
            event.preventDefault();
            if (!$form.checkValidity()) {
                return;
            }
            store.dispatch(startModalLoading());
            showCouponLoadingState();
            var data = new FormData((_a = event.target) !== null && _a !== void 0 ? _a : undefined);
            var couponCode = (_c = (_b = data.get('coupon_code')) === null || _b === void 0 ? void 0 : _b.trim()) !== null && _c !== void 0 ? _c : '';
            window.parent.postMessage({
                event: 'fetchCoupon',
                code: couponCode
            }, '*');
        });
    };
    for (var _i = 0, _a = $qsAll('form.wc-coupon-code'); _i < _a.length; _i++) {
        var $form = _a[_i];
        _loop_3($form);
    }
    for (var _b = 0, _c = $qsAll('.coupon-code-option'); _b < _c.length; _b++) {
        var $openCoupon = _c[_b];
        $openCoupon.addEventListener('click', showCouponInput);
    }
    for (var _d = 0, _e = $qsAll('.exit-button-coupon'); _d < _e.length; _d++) {
        var $exitCoupon = _e[_d];
        $exitCoupon.addEventListener('click', hideCouponInput);
    }
    hideCouponLoadingState();
}
function showCouponEntrySupport() {
    var _a;
    for (var _i = 0, _b = $qsAll('.coupon-code-option'); _i < _b.length; _i++) {
        var $form = _b[_i];
        $form.classList.remove('hide');
    }
    (_a = $qs('#coupon-code-section')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
}
function showCouponInput() {
    for (var _i = 0, _a = $qsAll('form.wc-coupon-code'); _i < _a.length; _i++) {
        var $coupon = _a[_i];
        $coupon.classList.remove('hide');
    }
    for (var _b = 0, _c = $qsAll('.coupon-code-option'); _b < _c.length; _b++) {
        var $option = _c[_b];
        $option.classList.add('hide');
    }
}
function hideCouponInput() {
    for (var _i = 0, _a = $qsAll('form.wc-coupon-code'); _i < _a.length; _i++) {
        var $coupon = _a[_i];
        $coupon.classList.add('hide');
    }
    for (var _b = 0, _c = $qsAll('.coupon-code-option'); _b < _c.length; _b++) {
        var $option = _c[_b];
        $option.classList.remove('hide');
    }
    for (var _d = 0, _e = $qsAll('.wc-invalid-coupon'); _d < _e.length; _d++) {
        var $invalid = _e[_d];
        $invalid.classList.add('hide');
    }
    clearInput('.wc-coupon-code-input');
}
function hideCouponLoadingState() {
    for (var _i = 0, _a = $qsAll('.wc-coupon-spinner'); _i < _a.length; _i++) {
        var $spinner = _a[_i];
        $spinner.classList.add('hide');
    }
    for (var _b = 0, _c = $qsAll('.wc-coupon-code-input'); _b < _c.length; _b++) {
        var $border = _c[_b];
        $border.classList.remove('remove-right-border');
    }
    for (var _d = 0, _e = $qsAll('.wc-coupon-code-apply'); _d < _e.length; _d++) {
        var $applyButton = _e[_d];
        $applyButton.disabled = false;
    }
}
function showCouponLoadingState() {
    for (var _i = 0, _a = $qsAll('.wc-coupon-spinner'); _i < _a.length; _i++) {
        var $spinner = _a[_i];
        $spinner.classList.remove('hide');
    }
    for (var _b = 0, _c = $qsAll('.wc-coupon-code-input'); _b < _c.length; _b++) {
        var $border = _c[_b];
        $border.classList.add('remove-right-border');
    }
    for (var _d = 0, _e = $qsAll('.wc-coupon-code-apply'); _d < _e.length; _d++) {
        var $applyButton = _e[_d];
        $applyButton.disabled = true;
    }
}
function initLanguage(message) {
    initLanguageEvents();
    var language = message.phpData.language === 'detect-from-page' ? message.pageLanguage : message.phpData.language;
    var englishVariants = new Set([
        'en-AU',
        'en-CA',
        'en-GB',
        'en-NZ',
        'en-ZA'
    ]);
    if (englishVariants.has(language)) {
        language = 'en-US';
    }
    store.dispatch(updateLanguage(language));
}
function initLanguageEvents() {
    onWindowMessage('pageLanguageChange', function (message) {
        store.dispatch(updateLanguage(message.language));
    });
    store.subscribe(function () {
        renderLocaleText();
    });
}
function renderLocaleText() {
    var _a, _b, _c, _d, _e, _f;
    for (var _i = 0, _g = $qsAll('[data-i18n]'); _i < _g.length; _i++) {
        var $element = _g[_i];
        if ($element.nodeName === 'INPUT' && $element.type === 'submit') {
            $element.value = getLocaleText((_b = (_a = $element === null || $element === void 0 ? void 0 : $element.dataset) === null || _a === void 0 ? void 0 : _a.i18n) !== null && _b !== void 0 ? _b : '');
        }
        else if ($element.nodeName === 'INPUT') {
            $element.placeholder = getLocaleText((_d = (_c = $element === null || $element === void 0 ? void 0 : $element.dataset) === null || _c === void 0 ? void 0 : _c.i18n) !== null && _d !== void 0 ? _d : '');
        }
        else {
            $element.textContent = getLocaleText((_f = (_e = $element === null || $element === void 0 ? void 0 : $element.dataset) === null || _e === void 0 ? void 0 : _e.i18n) !== null && _f !== void 0 ? _f : '');
        }
    }
    if (Environment.language() === 'ro-RO') {
        setCustomValidityMessage();
    }
}
function setCustomValidityMessage() {
    var _loop_4 = function ($input) {
        $input.addEventListener('invalid', function () {
            $input.setCustomValidity('Te rugăm sa completezi acest câmp.');
        });
        $input.addEventListener('input', function () {
            $input.setCustomValidity('');
        });
    };
    for (var _i = 0, _a = $qsAll('form input'); _i < _a.length; _i++) {
        var $input = _a[_i];
        _loop_4($input);
    }
}
function capitalizeFirstLetter(string) {
    var stringToUpper = String(string);
    return stringToUpper.charAt(0).toUpperCase() + stringToUpper.slice(1);
}
function initQuantityChangerEvent() {
    var _this = this;
    if (!Feature.enabled(FeatureFlag.QUANTITY_CHANGER)) {
        $qsAll('#pp-summary-body, #pp-summary-body-existing, #pp-summary-body-mobile', function ($removeButtons) {
            $removeButtons.addEventListener('click', function (event) { return __awaiter(_this, void 0, void 0, function () {
                var $target, cartItemKey;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0:
                            $target = event.target;
                            if (!$target.closest('.item-remover')) return [3, 2];
                            cartItemKey = $target.closest('.item-remover').dataset.qid;
                            return [4, changeQuantity(cartItemKey, 0, true)];
                        case 1:
                            _a.sent();
                            _a.label = 2;
                        case 2: return [2];
                    }
                });
            }); });
        });
        return;
    }
    $qsAll('#pp-summary-body, #pp-summary-body-existing, #pp-summary-body-mobile', function ($cartContainer) {
        $cartContainer.addEventListener('click', function (event1) { return __awaiter(_this, void 0, void 0, function () {
            var $target, $button, cartItemKey, previousTimeoutId_1, currentValue_1, cartItemKey_1, cartItemKey;
            var _this = this;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        $target = event1.target;
                        if (!$target.closest('.qty-btn') && !$target.closest('.qty-fs') && !$target.closest('.item-remover')) {
                            return [2];
                        }
                        if (!$target.closest('.qty-btn')) return [3, 5];
                        $button = $target.closest('.qty-btn');
                        cartItemKey = $button.dataset.qid;
                        if (!$button.classList.contains('decrease-qty')) return [3, 2];
                        return [4, changeQuantity(cartItemKey, -1, false)];
                    case 1:
                        _a.sent();
                        return [3, 4];
                    case 2:
                        if (!$button.classList.contains('increase-qty')) return [3, 4];
                        return [4, changeQuantity(cartItemKey, 1, false)];
                    case 3:
                        _a.sent();
                        _a.label = 4;
                    case 4: return [3, 8];
                    case 5:
                        if (!$target.closest('.qty-fs')) return [3, 6];
                        previousTimeoutId_1 = null;
                        currentValue_1 = $target.closest('.qty-fs').value;
                        cartItemKey_1 = $target.closest('.qty-fs').dataset.qid;
                        $target.addEventListener('input', function (event) {
                            var $inputTarget = event.target;
                            if (previousTimeoutId_1 !== null) {
                                clearTimeout(previousTimeoutId_1);
                            }
                            previousTimeoutId_1 = setTimeout(function () { return __awaiter(_this, void 0, void 0, function () {
                                return __generator(this, function (_a) {
                                    switch (_a.label) {
                                        case 0:
                                            previousTimeoutId_1 = null;
                                            if (!($inputTarget.value && currentValue_1 !== $inputTarget.value && $inputTarget.checkValidity())) return [3, 2];
                                            return [4, changeQuantity(cartItemKey_1, Number.parseInt($inputTarget.value), true)];
                                        case 1:
                                            _a.sent();
                                            return [3, 3];
                                        case 2:
                                            $inputTarget.reportValidity();
                                            _a.label = 3;
                                        case 3: return [2];
                                    }
                                });
                            }); }, 750);
                        });
                        $target.addEventListener('blur', function (event) { return __awaiter(_this, void 0, void 0, function () {
                            var $inputTarget;
                            return __generator(this, function (_a) {
                                switch (_a.label) {
                                    case 0:
                                        $inputTarget = event.target;
                                        if (previousTimeoutId_1 !== null) {
                                            clearTimeout(previousTimeoutId_1);
                                        }
                                        if (!($inputTarget.value && currentValue_1 !== $inputTarget.value && $inputTarget.checkValidity())) return [3, 2];
                                        return [4, changeQuantity(cartItemKey_1, Number.parseInt($inputTarget.value), true)];
                                    case 1:
                                        _a.sent();
                                        return [3, 3];
                                    case 2:
                                        $inputTarget.value = currentValue_1;
                                        _a.label = 3;
                                    case 3: return [2];
                                }
                            });
                        }); });
                        return [3, 8];
                    case 6:
                        if (!$target.closest('.item-remover')) return [3, 8];
                        cartItemKey = $target.closest('.item-remover').dataset.qid;
                        return [4, changeQuantity(cartItemKey, 0, true)];
                    case 7:
                        _a.sent();
                        _a.label = 8;
                    case 8: return [2];
                }
            });
        }); });
    });
}
function changeQuantity(cartItemKey, amount, set) {
    if (amount === void 0) { amount = 1; }
    if (set === void 0) { set = false; }
    return __awaiter(this, void 0, void 0, function () {
        var response, error_3;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (Environment.modalUI.loadingMode() !== 'finished') {
                        return [2];
                    }
                    store.dispatch(startModalLoading());
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    return [4, fetchHostWindowData('pp-change-quantity', {
                            key: cartItemKey,
                            amount: amount,
                            set: set
                        })];
                case 2:
                    response = _a.sent();
                    consumeCartCalculationResponse(response);
                    return [3, 4];
                case 3:
                    error_3 = _a.sent();
                    if (error_3 instanceof Error) {
                        captureSentryException(new Error("Quantity failed to change on ".concat(MerchantConfiguration.hostName(), ". Error").concat(error_3.message)));
                    }
                    return [3, 4];
                case 4:
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    });
}
function initCart() {
    var _this = this;
    initCartEvents();
    initQuantityChangerEvent();
    onWindowMessage('pp-update-cart', function () { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, requestCartCalculation()];
                case 1:
                    _a.sent();
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    }); });
}
function initCartEvents() {
    var previousCartData = '';
    var previousCurrencyData = '';
    store.subscribe(function () {
        var cartData = JSON.stringify(DefaultCart.contents());
        var currencyData = JSON.stringify(MerchantConfiguration.currency.configuration());
        if (cartData !== previousCartData || currencyData !== previousCurrencyData) {
            previousCartData = cartData;
            previousCurrencyData = currencyData;
            renderOrderSummaryItems(DefaultCart.contents());
        }
    });
}
function renderOrderSummaryItems(cart) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t;
    var $tbody = $qs('#pp-summary-body');
    var $tbodyExisting = $qs('#pp-summary-body-existing');
    var $tbodyMobile = $qs('#pp-summary-body-mobile');
    if (!$tbody || !$tbodyExisting || !$tbodyMobile) {
        return;
    }
    function getVariationHTML(item) {
        var variationRowHTML = '';
        if (!item.attributes) {
            return variationRowHTML;
        }
        var keys = Object.keys(item.attributes);
        if (keys.length === 0) {
            return variationRowHTML;
        }
        variationRowHTML = '';
        for (var _i = 0, keys_1 = keys; _i < keys_1.length; _i++) {
            var key = keys_1[_i];
            var formattedKey = capitalizeFirstLetter(key.replace('attribute_', '').replace('pa_', '').replace(/-/g, ' '));
            var formattedValue = String(item.attributes[key]).toUpperCase();
            variationRowHTML += "<br><span class=\"".concat(item.is_part_of_bundle ? '' : 'muted', " pl-3/2\">").concat(formattedKey, ": ").concat(formattedValue, "</span>");
        }
        return variationRowHTML;
    }
    clearOrderSummary();
    if (DefaultCart.contents().length === 0) {
        var $message = "<tr class=\"order-summary-item\"><td style=\"text-align: center; \">".concat(getLocaleText('Cart is empty'), "</td></tr>");
        $tbody.innerHTML = $message;
        $tbodyMobile.innerHTML = $message;
        $tbodyExisting.innerHTML = $message;
        return;
    }
    var _loop_5 = function (i) {
        var item = cart[i];
        if (item.quantity === '' || Number.parseInt(String(item.quantity)) === 0) {
            return "continue";
        }
        var name_1 = item.name;
        if (MerchantConfiguration.hostName() === 'ugoprobaseball.com' && item.formatted_item_data && item.name_with_variation) {
            name_1 = item.name_with_variation;
        }
        var variationTitle = !item.attributes && item.variation_title ? " - ".concat((_a = item.variation_title) !== null && _a !== void 0 ? _a : '') : '';
        var label = "".concat(name_1.bold()).concat(variationTitle, " ").concat(metaDataRowsHTML(item), " ").concat(item.formatted_item_data ? formattedItemDataHTML(item) : getVariationHTML(item));
        var amount = "".concat(formatCurrencyString(Number.parseFloat((_b = item.display_price) !== null && _b !== void 0 ? _b : item.price) * cartItemQuantity(item)));
        if (item.is_part_of_bundle) {
            amount = '';
        }
        if (item.is_subscription) {
            var stringAmount = !((_c = item.subscription_price_string) === null || _c === void 0 ? void 0 : _c.indexOf(String((_d = item.display_price) !== null && _d !== void 0 ? _d : item.price))) ? '' : formatCostString(Number.parseFloat((_e = item.display_price) !== null && _e !== void 0 ? _e : item.price));
            amount = "".concat(MerchantConfiguration.currency.symbol()).concat(stringAmount).concat((_f = item.subscription_price_string) !== null && _f !== void 0 ? _f : '');
        }
        var $row = document.createElement('tr');
        $row.className = 'order-summary-item';
        var $itemRemover = function (tdClass) {
            if (tdClass === void 0) { tdClass = ''; }
            return "\n\t\t<td class=\"item-remover-td non-bundled-item ".concat(tdClass, "\">\n\t\t\t<button class=\"item-remover pp-disabled-processing\" data-qid=\"").concat(item.item_key, "\">&times;</button>\n\t\t</td>");
        };
        var $qtyChanger = function (tdClass) {
            if (tdClass === void 0) { tdClass = ''; }
            return "\n\t\t<td class=\"qty-td ".concat(tdClass, "\">\n\t\t\t<div class=\"quantity-changer\">\n\t\t\t\t<button type=\"button\" class=\"pr-0 decrease-qty qty-btn ").concat(cartItemQuantity(item) <= 1 ? 'scroll-end' : '', "\" data-qid=\"").concat(item.item_key, "\">&#8722;</button>\n\t\t\t\t<form onSubmit=\"return false;\" class=\"mb-0\">\n\t\t\t\t\t<input type=\"number\" min=\"0\" max=\"").concat(item.stock_qty ? item.stock_qty : '', "\" class=\"qty-fs\" value=\"").concat(cartItemQuantity(item), "\" data-qid=\"").concat(item.item_key, "\" required/>\n\t\t\t\t</form>\n\t\t\t\t<button type=\"button\" class=\"pl-0 increase-qty qty-btn ").concat(item.stock_qty && cartItemQuantity(item) >= item.stock_qty ? 'scroll-end' : '', "\" data-qid=\"").concat(item.item_key, "\">+</button>\n\t\t\t</div>\n\t\t</td>");
        };
        var itemAmount = "<span class='muted'> &times ".concat(cartItemQuantity(item), "</span>");
        var showQuantityChanger = Feature.enabled(FeatureFlag.QUANTITY_CHANGER) && Environment.plugin.pageType() === 'cart' || Feature.enabled(FeatureFlag.QUANTITY_CHANGER) && Feature.version(FeatureFlag.QUANTITY_CHANGER) >= 2;
        if (!item.is_part_of_bundle) {
            if (i < cart.length - 1 && cart[i + 1].is_part_of_bundle) {
                $row.innerHTML += $itemRemover('remove-border');
                if (((_g = item.image) === null || _g === void 0 ? void 0 : _g[0]) && ((_h = item.image) === null || _h === void 0 ? void 0 : _h[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"product-img-td-b0\" id=\"product-img\"><img class=\"product-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t".concat(showQuantityChanger ? $qtyChanger('bundle-name remove-border') : '', "\n\t\t\t\t\t<td class=\"bundle-name\">").concat(label, " ").concat(showQuantityChanger ? '' : itemAmount, "</td>\n\t\t\t\t\t<td class=\"bundle-name bold\"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t\t\t\t");
            }
            else {
                $row.innerHTML += $itemRemover();
                if (((_j = item.image) === null || _j === void 0 ? void 0 : _j[0]) && ((_k = item.image) === null || _k === void 0 ? void 0 : _k[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"product-img-td\" id=\"product-img\"><img class=\"product-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t".concat(showQuantityChanger ? $qtyChanger('non-bundled-item') : '', "\n\t\t\t\t\t<td class=\"non-bundled-item\">").concat(label, " ").concat(showQuantityChanger ? '' : itemAmount, "</td>\n\t\t\t\t\t<td class=\"non-bundled-item bold \"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t\t\t\t");
            }
        }
        else if (item.is_part_of_bundle) {
            if (i < cart.length - 1 && !cart[i + 1].is_part_of_bundle || i === cart.length - 1) {
                if (((_l = item.image) === null || _l === void 0 ? void 0 : _l[0]) && ((_m = item.image) === null || _m === void 0 ? void 0 : _m[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"muted pl-3/2 bb-1 product-img-td\" id=\"product-img\"><img class=\"bundle-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t<td class=\"muted pl-3/2 bb-1\">".concat(label, "</td>\n\t\t\t\t\t<td class=\"muted pl-3/2 bb-1\"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t\t\t\t");
            }
            else {
                if (((_o = item.image) === null || _o === void 0 ? void 0 : _o[0]) && ((_p = item.image) === null || _p === void 0 ? void 0 : _p[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"muted pl-3/2 bundle-padding product-img-td-b0\" id=\"product-img\"><img class=\"bundle-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t<td class=\"muted pl-3/2 bundle-padding\">".concat(label, "</td>\n\t\t\t\t\t<td class=\"muted pl-3/2 bundle-padding\"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t\t\t\t");
            }
        }
        if (itemsInCart(cart) === 1 || i === itemsInCart(cart) - 1) {
            var $one = document.createElement('tr');
            $one.className = 'order-summary-item';
            if (item.is_part_of_bundle) {
                if (((_q = item.image) === null || _q === void 0 ? void 0 : _q[0]) && ((_r = item.image) === null || _r === void 0 ? void 0 : _r[0]) !== '(unknown)') {
                    $one.innerHTML += "<td class=\"muted pl-3/2 bb-1 product-img-td remove-border\" id=\"product-img\"><img class=\"bundle-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $one.innerHTML += "\n\t\t\t\t\t<td class=\"muted pl-3/2 bb-1 remove-border\">".concat(label, "</td>\n\t\t\t\t\t<td class=\"muted pl-3/2 bb-1 remove-border\">").concat(amount, "</td>\n\t\t\t\t");
            }
            else {
                $one.innerHTML += $itemRemover('remove-border');
                if (((_s = item.image) === null || _s === void 0 ? void 0 : _s[0]) && ((_t = item.image) === null || _t === void 0 ? void 0 : _t[0]) !== '(unknown)') {
                    $one.innerHTML += "<td class=\"product-img-td remove-border\" id=\"product-img\"><img class=\"product-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $one.innerHTML += "\n\t\t\t\t\t".concat(showQuantityChanger ? $qtyChanger('non-bundled-item remove-border') : '', "\n\t\t\t\t\t<td class=\"non-bundled-item remove-border\">").concat(label, " ").concat(showQuantityChanger ? '' : itemAmount, "</td>\n\t\t\t\t\t<td class=\"non-bundled-item remove-border bold\"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t\t\t\t");
            }
            $tbody.prepend($one);
            $tbodyMobile.prepend($one.cloneNode(true));
        }
        else {
            $tbody.prepend($row);
            $tbodyMobile.prepend($row.cloneNode(true));
        }
        $tbodyExisting.prepend($row.cloneNode(true));
    };
    for (var i = cart.length - 1; i >= 0; i--) {
        _loop_5(i);
    }
}
function clearOrderSummary() {
    for (var _i = 0, _a = $qsAll('.order-summary-item'); _i < _a.length; _i++) {
        var $item = _a[_i];
        $item.remove();
    }
}
function metaDataRowsHTML(item) {
    if (!item.meta_data) {
        return '';
    }
    var html = '';
    for (var _i = 0, _a = item.meta_data; _i < _a.length; _i++) {
        var meta = _a[_i];
        var keyText = capitalizeFirstLetter(meta.key.replace(/_/g, ' '));
        html += "<br><span class=\"muted ml-half\"><b>".concat(keyText, "</b>: ").concat(meta.value || '(none)', "</span>");
    }
    return html;
}
function formattedItemDataHTML(item) {
    if (!item.formatted_item_data) {
        return '';
    }
    return item.formatted_item_data.replace(/&nbsp;/g, '');
}
function initSummary(message) {
    var _a;
    initSummaryEvents();
    store.dispatch(updateMerchantTaxConfig({
        displayPricesInCartAndCheckout: ((_a = message.phpData) === null || _a === void 0 ? void 0 : _a.wc_tax_price_display) === 'incl' ? 'includeTax' : 'excludeTax'
    }));
}
function initSummaryEvents() {
    var _a, _b, _c, _d;
    (_a = $qs('#pp-dropdown')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', orderSummaryDropdown);
    (_b = $qs('#pp-dropdown')) === null || _b === void 0 ? void 0 : _b.addEventListener('keypress', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            orderSummaryDropdown();
        }
    });
    (_c = $qs('#pp-dropdown-new')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', orderSummaryDropdown);
    (_d = $qs('#pp-modal-content')) === null || _d === void 0 ? void 0 : _d.addEventListener('mousedown', function (e) {
        var _a, _b, _c, _d, _e, _f;
        var target = e.target;
        if (!(((_a = $qs('#order-summary-contents')) === null || _a === void 0 ? void 0 : _a.contains(target)) || ((_b = $qs('#order-summary-contents-new')) === null || _b === void 0 ? void 0 : _b.contains(target)))) {
            if (!(((_c = $qs('#pp-dropdown')) === null || _c === void 0 ? void 0 : _c.contains(target)) || ((_d = $qs('#pp-dropdown-new')) === null || _d === void 0 ? void 0 : _d.contains(target)))) {
                if (((_e = $qs('#pp-dropdown')) === null || _e === void 0 ? void 0 : _e.getAttribute('aria-expanded')) === 'true' || ((_f = $qs('#pp-dropdown-new')) === null || _f === void 0 ? void 0 : _f.getAttribute('aria-expanded')) === 'true') {
                    orderSummaryDropdown();
                }
            }
        }
    });
    store.subscribe(function () {
        renderSummaries();
        renderCartTotals();
    });
}
function renderSummaries() {
    var _a, _b, _c;
    clearRenderedSummaries();
    var cartSummariesHTML = '';
    for (var _i = 0, _d = Object.keys(store.getState().calculatedCarts); _i < _d.length; _i++) {
        var cartKey = _d[_i];
        var summaryHTML = '';
        var _e = cartSummaryViewData(cartKey)(), cartSummary = _e.cartSummary, cartMeta = _e.cartMeta;
        var summaryTitleHTML = cartKey === '0' ? '' : "\n<tr class=\"summary-title\">\n\t<td>Recurring totals</td>\n\t<td></td>\n</tr>";
        for (var _f = 0, cartSummary_1 = cartSummary; _f < cartSummary_1.length; _f++) {
            var line = cartSummary_1[_f];
            summaryHTML += renderSummaryLine(line.key, line.value, cartMeta);
        }
        cartSummariesHTML += "\n<div class=\"cart-summary\" data-cart-key=\"".concat(cartKey, "\">\n\t<table>\n\t\t").concat(summaryTitleHTML, "\n\t\t").concat(summaryHTML, "\n\t</table>\n\t<p class=\"first-renewal muted\">").concat(buildSubscriptionFirstRenewalString(cartMeta), "</p>\n</div>");
    }
    (_a = $qs('#pp-summary-lines-body')) === null || _a === void 0 ? void 0 : _a.insertAdjacentHTML('beforeend', cartSummariesHTML);
    (_b = $qs('#pp-summary-lines-body-existing')) === null || _b === void 0 ? void 0 : _b.insertAdjacentHTML('beforeend', cartSummariesHTML);
    (_c = $qs('#pp-summary-lines-body-mobile')) === null || _c === void 0 ? void 0 : _c.insertAdjacentHTML('beforeend', cartSummariesHTML);
}
function clearRenderedSummaries() {
    for (var _i = 0, _a = $qsAll('.cart-summary'); _i < _a.length; _i++) {
        var $summary = _a[_i];
        $summary.remove();
    }
}
function renderSummaryLine(name, amount, cartMeta) {
    var priceMetaHTML = '';
    if (cartMeta.subscription) {
        priceMetaHTML = "<span class=\"muted\">".concat(buildSubscriptionPriceMetaData(cartMeta), "</span>");
    }
    return "\n<tr class=\"summary-line\" data-raw-cost=\"".concat(amount, "\">\n\t<td>").concat(name, "</td>\n\t<td class=\"pp-recalculate-blur\" >").concat(formatCurrencyString(amount)).concat(priceMetaHTML, "</td>\n</tr>");
}
function orderSummaryDropdown() {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x;
    if (window.matchMedia('(max-width: 900px)').matches) {
        var newCustomer = (_a = $qs('#pp-dropdown-new')) === null || _a === void 0 ? void 0 : _a.getAttribute('aria-expanded');
        if (newCustomer === 'true') {
            (_b = $qs('#pp-dropdown-new')) === null || _b === void 0 ? void 0 : _b.setAttribute('aria-expanded', 'false');
            newCustomer = (_c = $qs('#pp-dropdown-new')) === null || _c === void 0 ? void 0 : _c.getAttribute('aria-expanded');
        }
        else {
            (_d = $qs('#pp-dropdown-new')) === null || _d === void 0 ? void 0 : _d.setAttribute('aria-expanded', 'true');
            newCustomer = (_e = $qs('#pp-dropdown-new')) === null || _e === void 0 ? void 0 : _e.getAttribute('aria-expanded');
        }
        if (newCustomer === 'true') {
            (_f = $qs('#dropdown-down-new')) === null || _f === void 0 ? void 0 : _f.classList.add('hide');
            (_g = $qs('#dropdown-up-new')) === null || _g === void 0 ? void 0 : _g.classList.remove('hide');
            (_h = $qs('#order-summary-contents-new')) === null || _h === void 0 ? void 0 : _h.classList.add('order-summary-contents-new-opened');
        }
        else {
            (_j = $qs('#dropdown-down-new')) === null || _j === void 0 ? void 0 : _j.classList.remove('hide');
            (_k = $qs('#dropdown-up-new')) === null || _k === void 0 ? void 0 : _k.classList.add('hide');
            (_l = $qs('#order-summary-contents-new')) === null || _l === void 0 ? void 0 : _l.classList.remove('order-summary-contents-new-opened');
        }
    }
    var existing = (_m = $qs('#pp-dropdown')) === null || _m === void 0 ? void 0 : _m.getAttribute('aria-expanded');
    if (existing === 'true') {
        (_o = $qs('#pp-dropdown')) === null || _o === void 0 ? void 0 : _o.setAttribute('aria-expanded', 'false');
        existing = (_p = $qs('#pp-dropdown')) === null || _p === void 0 ? void 0 : _p.getAttribute('aria-expanded');
    }
    else {
        (_q = $qs('#pp-dropdown')) === null || _q === void 0 ? void 0 : _q.setAttribute('aria-expanded', 'true');
        existing = (_r = $qs('#pp-dropdown')) === null || _r === void 0 ? void 0 : _r.getAttribute('aria-expanded');
    }
    if (existing === 'true') {
        (_s = $qs('.dropdown-down')) === null || _s === void 0 ? void 0 : _s.classList.add('hide');
        (_t = $qs('.dropdown-up')) === null || _t === void 0 ? void 0 : _t.classList.remove('hide');
        (_u = $qs('#order-summary-contents')) === null || _u === void 0 ? void 0 : _u.classList.add('order-summary-contents-opened');
    }
    else {
        (_v = $qs('.dropdown-down')) === null || _v === void 0 ? void 0 : _v.classList.remove('hide');
        (_w = $qs('.dropdown-up')) === null || _w === void 0 ? void 0 : _w.classList.add('hide');
        (_x = $qs('#order-summary-contents')) === null || _x === void 0 ? void 0 : _x.classList.remove('order-summary-contents-opened');
    }
}
function renderCartTotals() {
    $qsAll('.pp-summary-total', function ($element) { return $element.innerHTML = ''; });
    for (var _i = 0, _a = Object.keys(store.getState().calculatedCarts); _i < _a.length; _i++) {
        var cartKey = _a[_i];
        var calculatedCart = store.getState().calculatedCarts[cartKey];
        if (!calculatedCart) {
            continue;
        }
        renderCartTotal(calculatedCart.summary.total, calculatedCart.cart_meta);
    }
}
function renderCartTotal(total, cartMeta) {
    if (!cartMeta.subscription) {
        $qsAll('.pp-summary-total', function ($element) { return $element.innerHTML += "<span>".concat(formatCurrencyString(total), "</span>"); });
    }
    else {
        $qsAll('.pp-summary-total', function ($element) { return $element.innerHTML += " <span class=\"muted\"> + </span><span class=\"muted\">".concat(formatCurrencyString(total)).concat(buildSubscriptionPriceMetaData(cartMeta, true), "</span>"); });
    }
}
function initModal(message1) {
    var _this = this;
    var _a, _b, _c;
    var prevCurrencyCode = '';
    store.subscribe(function () {
        var _a, _b;
        if (Environment.modalUI.open()) {
            $qs('#pp-modal-content', function ($element) { return $element.style.display = 'flex'; });
        }
        else if (!Environment.modalUI.open()) {
            $qs('#pp-modal-content', function ($element) { return $element.style.display = 'none'; });
            return;
        }
        renderButtonColorTheme(Environment.plugin.buttonColor());
        renderTestModeBannerDisplay(Environment.testMode());
        renderModalPageIndicator(Environment.modalUI.page());
        renderModalNavigation(Environment.modalUI.page());
        renderContinueButtonDisplay(Environment.modalUI.page());
        renderContinueError(Environment.modalUI.page(), PeachPayOrder.errorMessage());
        renderContinueButtonLoading(Environment.modalUI.loadingMode());
        ppDisabledLoading(Environment.modalUI.loadingMode());
        ppDisableProcessing(Environment.modalUI.loadingMode());
        ppBlurOnRecalculate(Environment.modalUI.loadingMode(), prevCurrencyCode !== MerchantConfiguration.currency.code());
        prevCurrencyCode = MerchantConfiguration.currency.code();
        centerModal();
        renderInfoPageDisplay(Environment.modalUI.page());
        renderPaymentPageDisplay(Environment.modalUI.page(), Environment.customer.existing(), Environment.customer.mobile());
        renderTermsAndCondition(Environment.modalUI.page(), (_b = (_a = message1.phpData) === null || _a === void 0 ? void 0 : _a.wc_terms_conditions) !== null && _b !== void 0 ? _b : '');
        renderSupportMessage(Environment.plugin.supportMessage(), Environment.modalUI.page());
        displayErrorMessage(PeachPayOrder.errorMessage());
    });
    onWindowMessage('UI::modalOpened', function (_) {
        store.dispatch(updateEnvironment({
            modalIsOpen: true
        }));
    });
    onWindowMessage('UI::modalClosed', function (_) {
        store.dispatch(updateEnvironment({
            modalIsOpen: false
        }));
        store.dispatch(stopModalLoading());
    });
    onWindowMessage('hideContinueSpinner', function (_) {
        store.dispatch(stopModalLoading());
    });
    onWindowMessage('buttonClicked', function () { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    openCheckoutModal();
                    validateCartItemsWithCustomer(DefaultCart.contents(), true);
                    store.dispatch(startModalLoading());
                    return [4, requestCartCalculation(!Environment.customer.existing())];
                case 1:
                    _a.sent();
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    }); });
    onWindowMessage('stopPaymentProcessingAnimations', function (message) {
        store.dispatch(stopModalLoading());
        if (message.closeModal) {
            requestCloseModal();
        }
        if (message.errorMessage) {
            store.dispatch(setOrderError(message.errorMessage));
        }
    });
    (_a = $qs('.pp-exit')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', requestCloseModal);
    (_b = $qs('.pp-close')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', requestCloseModal);
    (_c = $qs('#edit-info')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', backToInfo);
    for (var _i = 0, _d = $qsAll('.pp-back-to-info'); _i < _d.length; _i++) {
        var $element1 = _d[_i];
        $element1.addEventListener('click', backToInfo);
    }
}
function ppBlurOnRecalculate(loadingMode, currencyChanged) {
    if (loadingMode === 'loading') {
        $qsAll('.pp-recalculate-blur', function (element) {
            element.classList.add('pp-blur');
        });
        if (currencyChanged) {
            $qsAll('.pp-currency-blur', function (element) {
                element.classList.add('pp-blur');
            });
        }
    }
    else {
        $qsAll('.pp-recalculate-blur', function (element) {
            element.classList.remove('pp-blur');
        });
        $qsAll('.pp-currency-blur', function (element) {
            element.classList.remove('pp-blur');
        });
    }
}
function displayErrorMessage(errorMessage) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
    if (errorMessage !== '') {
        (_a = $qs('#shipping-options-container')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
        (_b = $qs('#pp-payment-form')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
        (_c = $qs('#payment-methods')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        for (var _i = 0, _l = $qsAll('.pay-button-container'); _i < _l.length; _i++) {
            var $element3 = _l[_i];
            $element3.classList.add('hide');
        }
        for (var _m = 0, _o = $qsAll('.hide-when-invalid'); _m < _o.length; _m++) {
            var $element2 = _o[_m];
            $element2.classList.add('hide');
        }
        (_d = $qs('#invalid-order-message')) === null || _d === void 0 ? void 0 : _d.classList.remove('hide');
        (_e = $qs('#invalid-order-message-existing')) === null || _e === void 0 ? void 0 : _e.classList.remove('hide');
        $qs('#invalid-order-message', function ($element) { return $element.innerHTML = errorMessage; });
        $qs('#invalid-order-message-existing', function ($element) { return $element.innerHTML = errorMessage; });
    }
    else {
        (_f = $qs('#shipping-options-container')) === null || _f === void 0 ? void 0 : _f.classList.remove('hide');
        (_g = $qs('#pp-payment-form')) === null || _g === void 0 ? void 0 : _g.classList.remove('hide');
        (_h = $qs('#payment-methods')) === null || _h === void 0 ? void 0 : _h.classList.remove('hide');
        for (var _p = 0, _q = $qsAll('.pay-button-container'); _p < _q.length; _p++) {
            var $element = _q[_p];
            $element.classList.remove('hide');
        }
        for (var _r = 0, _s = $qsAll('.hide-when-invalid'); _r < _s.length; _r++) {
            var $element5 = _s[_r];
            $element5.classList.remove('hide');
        }
        (_j = $qs('#invalid-order-message')) === null || _j === void 0 ? void 0 : _j.classList.add('hide');
        (_k = $qs('#invalid-order-message-existing')) === null || _k === void 0 ? void 0 : _k.classList.add('hide');
    }
}
function openCheckoutModal() {
    var _a;
    (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage('openModal', '*');
}
function requestCloseModal() {
    var _a;
    syncOrderNotes(true);
    (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage('closeModal', '*');
}
function backToInfo() {
    store.dispatch(updateEnvironment({
        modalPageType: 'info',
        customerExists: false
    }));
    store.dispatch(updateCustomerAddressValidation(false));
    syncOrderNotes();
}
function renderContinueButtonDisplay(modalPage) {
    var _a, _b, _c, _d, _e, _f;
    if (modalPage === 'info') {
        (_a = $qs('#pp-continue')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('#pp-continue-mobile')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
        (_c = $qs('.pay-button-container-mobile')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
    }
    else {
        (_d = $qs('#pp-continue')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
        (_e = $qs('#pp-continue-mobile')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
        (_f = $qs('.pay-button-container-mobile')) === null || _f === void 0 ? void 0 : _f.classList.remove('hide');
    }
}
function renderContinueError(modalPage, errorMessage) {
    $qsAll('.pp-continue-order-error', function (element) {
        element.innerHTML = '';
        element.classList.remove('pp-error');
    });
    if (modalPage === 'info' && errorMessage !== '') {
        $qsAll('.pp-continue-order-error', function (element) {
            element.innerHTML = errorMessage;
            element.classList.add('pp-error');
        });
    }
}
function renderContinueButtonLoading(loadingMode) {
    var _a, _b, _c, _d;
    if (loadingMode === 'loading') {
        $qs('#pp-continue', function ($element) { return $element.disabled = true; });
        $qs('#pp-continue-mobile', function ($element) { return $element.disabled = true; });
        (_a = $qs('#continue-spinner')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('#continue-spinner-mobile')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    }
    else {
        $qs('#pp-continue', function ($element) { return $element.disabled = false; });
        $qs('#pp-continue-mobile', function ($element) { return $element.disabled = false; });
        (_c = $qs('#continue-spinner')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        (_d = $qs('#continue-spinner-mobile')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
    }
}
function ppDisableProcessing(loadingMode) {
    if (loadingMode === 'processing') {
        $qsAll('.pp-disabled-processing', function ($element) {
            $element.classList.add('pp-disabled');
            $element.disabled = true;
        });
    }
    else {
        $qsAll('.pp-disabled-processing', function ($element) {
            $element.classList.remove('pp-disabled');
            $element.disabled = false;
        });
    }
}
function ppDisabledLoading(loadingMode) {
    if (loadingMode !== 'finished') {
        $qsAll('.pp-disabled-loading', function ($element) {
            $element.classList.add('pp-disabled');
            $element.tabIndex = -1;
        });
    }
    else {
        $qsAll('.pp-disabled-loading', function ($element) {
            $element.classList.remove('pp-disabled');
            $element.tabIndex = -1;
        });
    }
}
function renderModalNavigation(modalPage) {
    var _a, _b;
    if (modalPage === 'info') {
        (_a = $qs('.pp-exit')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        for (var _i = 0, _c = $qsAll('.pp-back-to-info'); _i < _c.length; _i++) {
            var $element = _c[_i];
            $element.classList.add('hide');
        }
    }
    else if (modalPage === 'payment') {
        (_b = $qs('.pp-exit')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
        for (var _d = 0, _e = $qsAll('.pp-back-to-info'); _d < _e.length; _d++) {
            var $element = _e[_d];
            $element.classList.remove('hide');
        }
    }
}
function renderSupportMessage(supportMessage, page) {
    if (supportMessage && page === 'payment') {
        $qsAll('.pp-help', function ($el) { return $el.classList.remove('hide'); });
        var $temp_1 = document.createElement('div');
        $temp_1.innerHTML = supportMessage;
        $temp_1.querySelectorAll('a').forEach(function ($a) { return $a.setAttribute('target', '_blank'); });
        $temp_1.querySelectorAll('[style],[class]').forEach(function ($el) { return $el.removeAttribute('style'); });
        $temp_1.querySelectorAll(':not(a,br)').forEach(function ($el) { return $el.remove(); });
        $qsAll('.pp-help p ', function ($el) { return $el.innerHTML = $temp_1.innerHTML; });
    }
    else {
        $qsAll('.pp-help', function ($el) { return $el.classList.add('hide'); });
    }
}
function renderModalPageIndicator(modalPage) {
    var _a, _b, _c, _d, _e, _f, _g, _h;
    if (modalPage === 'info') {
        (_a = $qs('.color-changing-info')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('#checkout-status')) === null || _b === void 0 ? void 0 : _b.classList.add('circle-logo-one');
    }
    else {
        (_c = $qs('.color-changing-info')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        (_d = $qs('#checkout-status')) === null || _d === void 0 ? void 0 : _d.classList.remove('circle-logo-one');
    }
    if (modalPage === 'payment') {
        (_e = $qs('.color-changing-payment')) === null || _e === void 0 ? void 0 : _e.classList.remove('hide');
        (_f = $qs('#checkout-status')) === null || _f === void 0 ? void 0 : _f.classList.add('circle-logo-two');
    }
    else {
        (_g = $qs('.color-changing-payment')) === null || _g === void 0 ? void 0 : _g.classList.add('hide');
        (_h = $qs('#checkout-status')) === null || _h === void 0 ? void 0 : _h.classList.remove('circle-logo-two');
    }
}
function renderTestModeBannerDisplay(testMode) {
    var _a, _b;
    if (testMode) {
        (_a = $qs('#pp-modal-content')) === null || _a === void 0 ? void 0 : _a.classList.add('test-mode-border');
        (_b = $qs('.test-mode-banner')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
        $qs('#pp-modal-content', function ($element) { return $element.style.paddingTop = '1.25rem'; });
        $qs('.pp-close', function ($element) { return $element.style.top = '0.8rem'; });
        $qs('.pp-close', function ($element) { return $element.style.right = '4px'; });
    }
}
function renderButtonColorTheme(color) {
    if (color === void 0) { color = '#FF876C'; }
    document.documentElement.style.setProperty('--peachpay-theme-color', color);
    document.documentElement.style.setProperty('--peachpay-theme-color-opaque', color + '80');
    document.documentElement.style.setProperty('--peachpay-theme-color-light', color + '20');
}
function renderPaymentPageDisplay(modalPage, existingCustomer, isMobile) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u;
    if (modalPage === 'payment') {
        if (existingCustomer) {
            (_a = $qs('#pp-new-customer-checkout')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
            (_b = $qs('#pp-existing-customer-checkout')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
            (_c = $qs('#pp-modal-content')) === null || _c === void 0 ? void 0 : _c.classList.add('col');
            (_d = $qs('#pp-modal-content')) === null || _d === void 0 ? void 0 : _d.classList.add('w-existing-checkout');
            (_e = $qs('#pp-modal-content')) === null || _e === void 0 ? void 0 : _e.classList.add('p-1-5');
            (_f = $qs('.order-summary-heading')) === null || _f === void 0 ? void 0 : _f.classList.add('hide');
            $qs('#pp-summary-body', function ($element) { return $element.style.borderBottom = 'none'; });
            for (var _i = 0, _v = $qsAll('.split'); _i < _v.length; _i++) {
                var $element6 = _v[_i];
                $element6.style.setProperty('float', 'left', 'important');
            }
        }
        else {
            (_g = $qs('#pp-new-customer-checkout')) === null || _g === void 0 ? void 0 : _g.classList.remove('hide');
            (_h = $qs('#pp-existing-customer-checkout')) === null || _h === void 0 ? void 0 : _h.classList.add('hide');
            (_j = $qs('#pp-modal-content')) === null || _j === void 0 ? void 0 : _j.classList.remove('col');
            (_k = $qs('#pp-modal-content')) === null || _k === void 0 ? void 0 : _k.classList.remove('w-existing-checkout');
            (_l = $qs('#pp-modal-content')) === null || _l === void 0 ? void 0 : _l.classList.remove('p-1-5');
        }
        (_m = $qs('#extra-fields-section')) === null || _m === void 0 ? void 0 : _m.classList.remove('hide');
    }
    else {
        if (isMobile) {
            (_o = $qs('#extra-fields-section')) === null || _o === void 0 ? void 0 : _o.classList.add('hide');
        }
        (_p = $qs('.order-summary-heading')) === null || _p === void 0 ? void 0 : _p.classList.remove('hide');
        (_q = $qs('#pp-new-customer-checkout')) === null || _q === void 0 ? void 0 : _q.classList.remove('hide');
        (_r = $qs('#pp-existing-customer-checkout')) === null || _r === void 0 ? void 0 : _r.classList.add('hide');
        (_s = $qs('#pp-modal-content')) === null || _s === void 0 ? void 0 : _s.classList.remove('col');
        (_t = $qs('#pp-modal-content')) === null || _t === void 0 ? void 0 : _t.classList.remove('w-existing-checkout');
        (_u = $qs('#pp-modal-content')) === null || _u === void 0 ? void 0 : _u.classList.remove('p-1-5');
    }
}
function renderInfoPageDisplay(modalPage) {
    var _a, _b;
    if (modalPage === 'info') {
        (_a = $qs('#pp-info')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        for (var _i = 0, _c = $qsAll('.split'); _i < _c.length; _i++) {
            var $element = _c[_i];
            $element.style.setProperty('float', 'none', 'important');
        }
    }
    else {
        (_b = $qs('#pp-info')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
    }
}
function centerModal() {
    var modal = $qs('#pp-modal-content');
    if ((modal === null || modal === void 0 ? void 0 : modal.clientHeight) && modal.clientHeight < window.innerHeight) {
        document.documentElement.style.setProperty('height', '100%');
    }
    else {
        document.documentElement.style.removeProperty('height');
    }
}
function renderTermsAndCondition(page, terms) {
    if (page === 'payment') {
        var merchantTermsConditions_1 = terms ? "the store's <a href=\"".concat(terms, "\" target=\"_blank\">terms and conditions</a> and") : '';
        $qsAll('.pp-tc-section', function ($el) {
            $el.innerHTML = "<label class=\"pp-tc-contents\">By clicking the button above, you agree to ".concat(merchantTermsConditions_1, " the PeachPay <a href=\"https://peachpay.app/terms\" target=\"_blank\">terms</a> and <a href=\"https://peachpay.app/privacy\" target=\"_blank\">privacy policy</a>.</label>");
            $el.classList.remove('hide');
        });
    }
    else {
        $qsAll('.pp-tc-section', function ($el) { return $el.classList.add('hide'); });
    }
}
function initMetrics() {
    var _a, _b, _c, _d, _e;
    (_a = $qs('#pp-pay')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () { return recordNonPPButtonClick('pp-pay'); });
    (_b = $qs('#pp-pay-mobile')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () { return recordNonPPButtonClick('pp-pay-mobile'); });
    (_c = $qs('#pp-pay-existing')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', function () { return recordNonPPButtonClick('pp-pay-existing'); });
    (_d = $qs('#pp-continue')) === null || _d === void 0 ? void 0 : _d.addEventListener('click', function () { return recordNonPPButtonClick('pp-continue'); });
    (_e = $qs('#pp-continue-mobile')) === null || _e === void 0 ? void 0 : _e.addEventListener('click', function () { return recordNonPPButtonClick('pp-continue-mobile'); });
    onWindowMessage('buttonClicked', function (message) {
        recordButtonClick(buttonTypeValidation(message.buttonID), getPPButtonLocation(message));
    });
}
function buttonTypeValidation(buttonType) {
    return buttonType === undefined ? 'unknown' : buttonType;
}
function recordNonPPButtonClick(buttonType) {
    recordButtonClick(buttonType, PPButtonLocation.NotApplicable);
}
function recordButtonClick(buttonType, ppButtonLocation) {
    postButtonMetrics({
        domain: MerchantConfiguration.hostName(),
        buttonType: buttonType,
        ppButtonLocation: ppButtonLocation,
        isMobile: Environment.customer.mobile(),
        isTestMode: Environment.testMode()
    });
}
var PPButtonLocation;
(function (PPButtonLocation1) {
    PPButtonLocation1["Product"] = 'product';
    PPButtonLocation1["Checkout"] = 'checkout';
    PPButtonLocation1["MiniCart"] = 'mini-cart';
    PPButtonLocation1["Cart"] = 'cart';
    PPButtonLocation1["NotApplicable"] = 'not-applicable';
})(PPButtonLocation || (PPButtonLocation = {}));
function getPPButtonLocation(message) {
    if (message.isMiniCart) {
        return PPButtonLocation.MiniCart;
    }
    switch (Environment.plugin.pageType()) {
        case 'cart':
            return PPButtonLocation.Cart;
        case 'checkout':
            return PPButtonLocation.Checkout;
        case 'product':
            return PPButtonLocation.Product;
        default:
            return PPButtonLocation.NotApplicable;
    }
}
function postButtonMetrics(options) {
    if (!options.isTestMode) {
        fetch("https://2fad6w3exg.execute-api.us-east-1.amazonaws.com/v1/buttonstats?domain=".concat(options.domain, "&buttonType=").concat(options.buttonType, "&ppButtonLocation=").concat(String(options.ppButtonLocation), "&isMobile=").concat(String(options.isMobile), "&isProductionData=").concat(String(isProductionDomain(options.domain)))).then(function () { }).catch(function () { });
    }
}
function isProductionDomain(domain) {
    switch (domain) {
        case 'localhost':
        case '127.0.0.1':
        case 'store.local':
        case 'woo.store.local':
        case 'woo.peachpay.app':
        case 'theme1.peachpay.app':
        case 'theme2.peachpay.app':
        case 'theme3.peachpay.app':
        case 'theme4.peachpay.app':
        case 'theme5.peachpay.app':
        case 'qa.peachpay.app':
        case 'demo.peachpay.app':
            return false;
        default:
            return true;
    }
}
function initLinkedProducts() {
    var _a, _b, _c;
    (_a = $qs('.pp-prev-btn')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () { return scrollLeft(''); });
    (_b = $qs('.pp-next-btn')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () { return scrollRight(''); });
    (_c = $qs('#pp-products-list')) === null || _c === void 0 ? void 0 : _c.addEventListener('scroll', function () { return scrollAdjuster(''); });
    var previousCartData = '';
    store.subscribe(function () {
        if (Environment.plugin.pageType() === 'product' || Environment.plugin.pageType() === 'cart') {
            var cartData = JSON.stringify(DefaultCart.contents());
            if (cartData !== previousCartData) {
                previousCartData = cartData;
                renderLinkedProducts(DefaultCart.contents());
                setAddButtonColor(Environment.plugin.buttonColor());
            }
        }
    });
}
function clearLinkedProducts() {
    for (var _i = 0, _a = $qsAll('.product-body'); _i < _a.length; _i++) {
        var linkedItem = _a[_i];
        linkedItem.remove();
    }
}
function setAddButtonColor(color) {
    if (color === void 0) { color = '#FF876C'; }
    for (var _i = 0, _a = $qsAll('.add-btn'); _i < _a.length; _i++) {
        var addBtn = _a[_i];
        addBtn.style.backgroundColor = color;
        addBtn.style.border = '1px solid' + color;
    }
}
function renderLinkedProducts(cart) {
    var _a;
    clearLinkedProducts();
    for (var i = cart.length - 1; i >= 0; i--) {
        var item = cart[i];
        var linkedProducts = void 0;
        if (Environment.plugin.pageType() === 'product' && !item.is_part_of_bundle && item.upsell_items) {
            linkedProducts = item.upsell_items;
        }
        else if (Environment.plugin.pageType() === 'cart' && !item.is_part_of_bundle && item.cross_sell_items) {
            linkedProducts = item.cross_sell_items;
        }
        if (linkedProducts) {
            (_a = $qs('#linked-products-section')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
            for (var _i = 0, linkedProducts_1 = linkedProducts; _i < linkedProducts_1.length; _i++) {
                var linkedItem = linkedProducts_1[_i];
                if (linkedItem.has_stock === undefined) {
                    linkedItem.has_stock = true;
                }
                if (!linkedItem.variable && !linkedItem.bundle && linkedItem.has_stock && !hasSameLinkedProduct(linkedItem.id)) {
                    var productsList = $qs('#pp-products-list');
                    var productBody = document.createElement('div');
                    productBody.className = 'product-body';
                    productBody.id = String(linkedItem.id);
                    if (linkedItem.img_src) {
                        productBody.innerHTML = "<img class=\"linked-product-img\" src=".concat(linkedItem.img_src, ">");
                    }
                    productBody.innerHTML += "<div class=\"linked-product-desc\">\n\t\t\t\t\t\t\t\t\t\t\t\t <span class=\"linked-product-name\">".concat(linkedItem.name, "</span>\n\t\t\t\t\t\t\t\t\t\t\t\t <span class=\"linked-product-quantity\">Quantity: 1</span>\n\t\t\t\t\t\t\t\t\t\t\t\t <span class=\"linked-product-price\">").concat(formatCurrencyString(Number.parseFloat(linkedItem.price)), "</span>\n\t\t\t\t\t\t\t\t\t\t\t </div>\n\t\t\t\t\t\t\t\t\t\t\t <button class=\"add-btn\" data-pid=").concat(linkedItem.id, " data-i18n=\"add\"></button>");
                    productsList === null || productsList === void 0 ? void 0 : productsList.prepend(productBody);
                }
            }
        }
    }
    setAddButtonColor();
    removeLinkedProduct(cart);
    for (var _b = 0, _c = $qsAll('.add-btn'); _b < _c.length; _b++) {
        var addBtn = _c[_b];
        addBtn.addEventListener('click', function (event) {
            store.dispatch(startModalLoading());
            event.target.disabled = true;
            event.target.innerHTML = '<img src="img/spinner.svg" class="linked-product-spinner"/>';
            addLinkedProducttoCart(event.target);
        });
    }
}
function addLinkedProducttoCart(linkedProduct) {
    var _a;
    (_a = GLOBAL.linkedProductsIds) === null || _a === void 0 ? void 0 : _a.push(Number.parseInt(linkedProduct.dataset.pid));
    window.parent.postMessage({
        event: 'addLinkedProduct',
        productID: linkedProduct.dataset.pid
    }, '*');
}
function removeLinkedProduct(cart) {
    var _a, _b, _c, _d, _e;
    for (var _i = 0, _f = $qsAll('.product-body'); _i < _f.length; _i++) {
        var linkedProduct = _f[_i];
        for (var i = cart.length - 1; i >= 0; i--) {
            var item = cart[i];
            if (item.product_id === Number.parseInt(linkedProduct.id)) {
                linkedProduct.remove();
            }
        }
    }
    if ($qsAll('.product-body').length > 1) {
        (_a = $qs('.pp-prev-btn')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('.pp-next-btn')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    }
    else {
        (_c = $qs('.pp-prev-btn')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        (_d = $qs('.pp-next-btn')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
    }
    if (!$qs('.product-body')) {
        (_e = $qs('#linked-products-section')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
    }
    scrollAdjuster('');
}
function hasSameLinkedProduct(productID) {
    for (var _i = 0, _a = $qsAll('.product-body'); _i < _a.length; _i++) {
        var product = _a[_i];
        if (Number.parseInt(product.id) === productID) {
            return true;
        }
    }
    return false;
}
function installOneClickCheckout(testMode) {
    var oneClickURL = getOneClickURL(location.hostname, testMode);
    var $body = document.querySelector('body');
    $body === null || $body === void 0 ? void 0 : $body.insertAdjacentHTML('beforeend', "\n\t<iframe id=\"one-click-iframe\" \n\t\tframeborder=\"0\" \n\t\tallowtransparency=\"true\" \n\t\tscrolling=\"no\" \n\t\tallow=\"payment *\"\n\t\taria-hidden=\"true\" \n\t\ttabindex=\"-1\" \n\t\tstyle=\"border: none !important; margin: 0px !important; padding: 0px !important; width: 1px !important; min-width: 100% !important; overflow: hidden !important; display: block !important; visibility: hidden !important; position: fixed !important; height: 1px !important; pointer-events: none !important; user-select: none !important;\"\n\t\tsrc=\"".concat(oneClickURL, "one-click.html\"\n\t>\n\t\tUnable to load PeachPay One Click Checkout Support\n\t</iframe>"));
}
function initCurrencySwitcher() {
    if (Feature.enabled(FeatureFlag.CURRENCY_SWITCHER_INPUT)) {
        renderCurrencySelector();
    }
}
function renderCurrencySelector() {
    var $previousLocation = $qs('#pp_currency_select');
    if ($previousLocation) {
        $previousLocation.remove();
    }
    var currencies = Feature.metadata(FeatureFlag.CURRENCY_SWITCHER_INPUT, 'currencies');
    var currencyInfo = Feature.metadata(FeatureFlag.CURRENCY_SWITCHER_INPUT, 'currency_info');
    if (!currencies || !currencyInfo) {
        return;
    }
    var $insertionLocationExisting = $qs('#pp-pms-existing-container');
    var $insertionLocationNew = $qs('#pp-pms-new-container');
    var existingCurrencySelectContainer = document.createElement('div');
    var $currencySelectTitle = document.createElement('span');
    $currencySelectTitle.innerHTML = 'Currency';
    $currencySelectTitle.setAttribute('class', 'color-change-text');
    existingCurrencySelectContainer.id = 'pp_currency_select_div';
    existingCurrencySelectContainer.append($currencySelectTitle);
    var $newCurrencySelectContainer = existingCurrencySelectContainer.cloneNode(true);
    var mappedCurrencies = {};
    for (var _i = 0, _a = Object.keys(currencyInfo); _i < _a.length; _i++) {
        var key = _a[_i];
        var currency = currencyInfo[key];
        mappedCurrencies[key] = "(".concat(currency.symbol, ") - ").concat(currency.code);
    }
    var $options = renderDropDownList(mappedCurrencies);
    var $existingCurrencySelect = document.createElement('select');
    $existingCurrencySelect.innerHTML = $options;
    $existingCurrencySelect.classList.add('pp-currency-selector');
    selectDropdown($existingCurrencySelect, MerchantConfiguration.currency.code());
    var $divExisting = document.createElement('div');
    $divExisting.classList.add('pp-currency-selector-container');
    $divExisting.append($existingCurrencySelect);
    existingCurrencySelectContainer.append($divExisting);
    var $newCurrencySelect = document.createElement('select');
    $newCurrencySelect.innerHTML = $options;
    $newCurrencySelect.classList.add('pp-currency-selector');
    selectDropdown($newCurrencySelect, MerchantConfiguration.currency.code());
    var $divNew = document.createElement('div');
    $divNew.classList.add('pp-currency-selector-container');
    $divNew.append($newCurrencySelect);
    $newCurrencySelectContainer.append($divNew);
    $existingCurrencySelect.addEventListener('change', currencyEventListener);
    $newCurrencySelect.addEventListener('change', currencyEventListener);
    $insertionLocationExisting === null || $insertionLocationExisting === void 0 ? void 0 : $insertionLocationExisting.insertAdjacentElement('beforebegin', existingCurrencySelectContainer);
    $insertionLocationNew === null || $insertionLocationNew === void 0 ? void 0 : $insertionLocationNew.insertAdjacentElement('beforebegin', $newCurrencySelectContainer);
}
function sendCurrencySwitchMessage(currency) {
    var _a;
    var message = {
        event: 'currencyUpdate',
        currency: currency
    };
    (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage(message, '*');
}
function currencyEventListener(event) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var currencyInfo, $target, method;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    event.preventDefault();
                    currencyInfo = Feature.metadata(FeatureFlag.CURRENCY_SWITCHER_INPUT, 'currency_info');
                    $target = event.target;
                    $target.blur();
                    if (!((currencyInfo === null || currencyInfo === void 0 ? void 0 : currencyInfo[$target.value]) && $target.value !== MerchantConfiguration.currency.code())) return [3, 2];
                    store.dispatch(startModalLoading());
                    store.dispatch(updateMerchantCurrencyConfig(__assign(__assign({}, MerchantConfiguration.currency.configuration()), { code: (_a = currencyInfo === null || currencyInfo === void 0 ? void 0 : currencyInfo[$target.value].code) !== null && _a !== void 0 ? _a : MerchantConfiguration.currency.code() })));
                    method = PaymentConfiguration.checkEligibleOrFindAlternate({
                        provider: PaymentConfiguration.selectedProvider(),
                        method: PaymentConfiguration.selectedProviderMethod(),
                        index: PaymentConfiguration.selectedProviderMethodIndex()
                    });
                    if (method) {
                        store.dispatch(setPaymentMethod(method));
                    }
                    else {
                        store.dispatch(setOrderError(getLocaleText('There are no eligible or active payment methods available for this order.')));
                    }
                    sendCurrencySwitchMessage($target.value);
                    return [4, requestCartCalculation()];
                case 1:
                    _b.sent();
                    store.dispatch(updateMerchantCurrencyConfig(currencyInfo === null || currencyInfo === void 0 ? void 0 : currencyInfo[$target.value]));
                    $qsAll('.pp-currency-selector', function ($el) {
                        selectDropdown($el, MerchantConfiguration.currency.code());
                    });
                    store.dispatch(stopModalLoading());
                    _b.label = 2;
                case 2: return [2];
            }
        });
    });
}
function initPaymentMethods() {
    handleMorePMToggle();
    handlePMTabOptionsEvents();
    handleSavedPMOptionEvents();
    handleNewPMOptionButtonEvents();
    store.subscribe(function () {
        renderSelectedPM();
        processPMTabOptions();
        setTabIndex();
    });
}
function handleMorePMToggle() {
    var _a, _b;
    (_a = $qs('#pp-pm-expander img')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
        var _a, _b;
        (_a = $qs('#pp-pms-existing')) === null || _a === void 0 ? void 0 : _a.classList.toggle('hide');
        (_b = $qs('#pp-pm-expander img')) === null || _b === void 0 ? void 0 : _b.classList.toggle('selected');
        setTabIndex();
    });
    (_b = $qs('#pp-pm-expander img')) === null || _b === void 0 ? void 0 : _b.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            var $target = e.target;
            $target.click();
        }
    });
}
function setTabIndex() {
    var _a;
    var isHidden = (_a = $qs('#pp-pms-existing')) === null || _a === void 0 ? void 0 : _a.classList.contains('hide');
    $qsAll('#pp-pms-existing [tabindex]', function ($el) {
        $el.setAttribute('tabindex', isHidden ? '-1' : '0');
    });
}
function renderSelectedPM() {
    var _a, _b, _c, _d, _e, _f, _g;
    var providerKey = PaymentConfiguration.selectedProvider();
    var methodKey = PaymentConfiguration.selectedProviderMethod();
    var savedIndex = PaymentConfiguration.selectedProviderMethodIndex();
    var _h = PeachPayCustomer.retrieveSavedPaymentMethod(providerKey, methodKey, savedIndex), savedMethod = _h[0], methodConfig = _h[1];
    if (!methodConfig) {
        return;
    }
    if (methodConfig.reusable && !savedMethod && !savedIndex) {
        (_a = $qs('#pp-selected-pm')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
        (_b = $qs('#pp-pms-existing hr')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
        (_c = $qs('#pp-pm-expander')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        if (!$qs('#pp-pm-expander img.selected')) {
            (_d = $qs('#pp-pm-expander img')) === null || _d === void 0 ? void 0 : _d.click();
        }
        return;
    }
    else {
        (_e = $qs('#pp-selected-pm')) === null || _e === void 0 ? void 0 : _e.classList.remove('hide');
        (_f = $qs('#pp-pms-existing hr')) === null || _f === void 0 ? void 0 : _f.classList.remove('hide');
        (_g = $qs('#pp-pm-expander')) === null || _g === void 0 ? void 0 : _g.classList.remove('hide');
    }
    $qsAll('#pp-selected-pm .pp-pm-selected-option', function ($el) { return $el.classList.add('hide'); });
    var $existingSelectedOption = $qs("#pp-selected-pm .pp-pm-selected-option[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"][data-index=\"").concat(savedIndex, "\"]"));
    if ($existingSelectedOption) {
        $existingSelectedOption.classList.remove('hide');
    }
    else {
        var html_1 = buildSelectedPMTemplate(providerKey, methodKey, savedIndex, methodConfig, savedMethod);
        $qs('#pp-selected-pm', function ($el) { return $el.insertAdjacentHTML('beforeend', html_1); });
    }
}
function buildSelectedPMTemplate(providerKey, methodKey, index, method, saved) {
    var buildSelectedNonReusableTemplate = function () {
        var _a, _b, _c, _d, _e, _f;
        return "\n<div class=\"pp-pm-selected-option\" data-provider=\"".concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"").concat(index, "\">\n\t<span>\n\t\t<img class=\"pp-pm-display-badge\" style=\"transform: scale(").concat((_b = (_a = method.assets.title) === null || _a === void 0 ? void 0 : _a.scale) !== null && _b !== void 0 ? _b : '1', ") translateX(").concat((_d = (_c = method.assets.title) === null || _c === void 0 ? void 0 : _c.translateX) !== null && _d !== void 0 ? _d : '0', "px)\" src=\"").concat((_f = (_e = method.assets.title) === null || _e === void 0 ? void 0 : _e.src) !== null && _f !== void 0 ? _f : '', "\">\n\t</span>\n</div>");
    };
    var buildSelectedCardTemplate = function () {
        var _a, _b, _c;
        return "\n<div class=\"pp-pm-selected-option\" data-provider=\"".concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"").concat(index, "\">\n\t<span>\n\t\t<img class=\"pp-pm-display-badge\" src=\"img/marks/").concat((_a = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _a === void 0 ? void 0 : _a['brand'], ".svg\">\n\t\t\u2022\u2022\u2022\u2022\n\t\t").concat((_c = (_b = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _b === void 0 ? void 0 : _b['last4']) !== null && _c !== void 0 ? _c : '', "\n\t</span>\n\t<span style=\"float: right;\">\n\t\t").concat(getLocaleText('verified'), "\n\t\t<img class=\"pp-pm-checkmark\" src=\"img/check-circle-solid.svg\">\n\t</span>\n</div>");
    };
    if (!saved) {
        return buildSelectedNonReusableTemplate();
    }
    else {
        if (saved.type === 'card') {
            return buildSelectedCardTemplate();
        }
        else {
            return buildSelectedNonReusableTemplate();
        }
    }
}
function handlePMTabOptionsEvents() {
    $qsAll('.pp-pms', function ($el) {
        $el.addEventListener('click', function (e) {
            var _a, _b;
            var $target = e.target;
            var $pmType = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-type');
            if (!$pmType) {
                return;
            }
            var providerKey = (_a = $pmType.dataset.provider) !== null && _a !== void 0 ? _a : '';
            var methodKey = (_b = $pmType.dataset.method) !== null && _b !== void 0 ? _b : '';
            var _c = PeachPayCustomer.retrieveSavedPaymentMethods(providerKey, methodKey), savedMethods = _c[0], method = _c[1];
            if ((method === null || method === void 0 ? void 0 : method.reusable) && (savedMethods === null || savedMethods === void 0 ? void 0 : savedMethods.length)) {
                store.dispatch(setPaymentMethod({
                    provider: providerKey,
                    method: methodKey,
                    index: '0'
                }));
                return;
            }
            store.dispatch(setPaymentMethod({
                provider: providerKey,
                method: methodKey
            }));
        });
        $el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                var $target = e.target;
                $target.click();
            }
        });
    });
}
function processPMTabOptions() {
    var data = PaymentConfiguration.data();
    for (var providerKey in data.providers) {
        var provider = data.providers[providerKey];
        if (!provider) {
            continue;
        }
        for (var methodKey in provider.methods) {
            var method = provider.methods[methodKey];
            if (!method) {
                continue;
            }
            renderPMTabOption(providerKey, methodKey, method);
        }
    }
    renderPMTabDisplay();
}
function renderPMTabDisplay() {
    if (PaymentConfiguration.eligibleMethodCount() <= 1) {
        $qsAll('.pp-pms div.header', function ($el) { return $el.classList.add('hide'); });
    }
    else {
        $qsAll('.pp-pms div.header', function ($el) { return $el.classList.remove('hide'); });
    }
}
function renderPMTabOption(providerKey, methodKey, method) {
    var $existingTypeOption = $qsAll(".pp-pm-type[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
    if ($existingTypeOption.length) {
        updatePMTabOptionHTML($existingTypeOption, providerKey, methodKey);
    }
    else {
        var html_2 = buildPMTabOptionTemplate(providerKey, methodKey, method);
        $qsAll('.pp-pms div.header', function ($el) { return $el.insertAdjacentHTML('beforeend', html_2); });
    }
    renderSavedPMOptionsContainer(providerKey, methodKey);
    processSavedPMOptions(providerKey, methodKey);
}
function updatePMTabOptionHTML($existingOption, providerKey, methodKey) {
    var isEligible = PaymentConfiguration.eligibleMethod(providerKey, methodKey);
    var isSelected = PaymentConfiguration.isProviderAndMethodSelected(providerKey, methodKey);
    if (isSelected) {
        $existingOption.forEach(function ($el) { return $el.classList.add('selected'); });
    }
    else {
        $existingOption.forEach(function ($el) { return $el.classList.remove('selected'); });
    }
    if (isEligible) {
        $existingOption.forEach(function ($el) { return $el.classList.remove('hide'); });
    }
    else {
        $existingOption.forEach(function ($el) { return $el.classList.add('hide'); });
    }
}
function buildPMTabOptionTemplate(providerKey, methodKey, method) {
    var _a, _b;
    var isSelected = PaymentConfiguration.isProviderAndMethodSelected(providerKey, methodKey);
    if (providerKey === 'stripe' && methodKey === 'card') {
        return "\n\t\t<div class=\"pp-pm-type ".concat(isSelected ? 'selected' : '', "\" tabindex=\"0\" role=\"button\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t\t\t<span>\n\t\t\t\t<img class=\"pp-pm-badge\" style=\"transform: scale(").concat(method.assets.badge.scale, ") translateX(").concat((_a = method.assets.badge.translateX) !== null && _a !== void 0 ? _a : '0', "px)\" src=\"").concat(method.assets.badge.src, "\">\n\t\t\t</span>\n\t\t\t<span class=\"pp-name\">").concat(method.name, "</span>\n\t\t</div>");
    }
    return "\n\t<div class=\"pp-pm-type ".concat(isSelected ? 'selected' : '', "\" tabindex=\"0\" role=\"button\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t\t<span>\n\t\t\t<img class=\"pp-pm-full-badge\" style=\"transform: scale(").concat(method.assets.badge.scale, ") translateX(").concat((_b = method.assets.badge.translateX) !== null && _b !== void 0 ? _b : '0', "px)\" src=\"").concat(method.assets.badge.src, "\">\n\t\t</span>\n\t</div>");
}
function renderSavedPMOptionsContainer(providerKey, methodKey) {
    var $existingTypeOptionContainer = $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
    if ($existingTypeOptionContainer.length) {
        updateSavedPMOptionsContainerHTML($existingTypeOptionContainer, providerKey, methodKey);
    }
    else {
        var html_3 = buildSavedPMOptionsContainerTemplate(providerKey, methodKey);
        $qsAll('.pp-pms div.body', function ($el) { return $el.insertAdjacentHTML('beforeend', html_3); });
    }
}
function updateSavedPMOptionsContainerHTML($existingContainer, providerKey, methodKey) {
    var isEligible = PaymentConfiguration.eligibleMethod(providerKey, methodKey);
    var isVisible = PaymentConfiguration.isProviderAndMethodSelected(providerKey, methodKey);
    if (isVisible && isEligible) {
        $existingContainer.forEach(function ($el) { return $el.classList.remove('hide'); });
    }
    else {
        $existingContainer.forEach(function ($el) { return $el.classList.add('hide'); });
    }
}
function buildSavedPMOptionsContainerTemplate(providerKey, methodKey) {
    var isVisible = PaymentConfiguration.isProviderAndMethodSelected(providerKey, methodKey);
    return "\n<div class=\"pp-pm-container ".concat(isVisible ? '' : 'hide', "\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t<div class=\"pp-pm-options\"></div>\n\t<div class=\"pp-pm-controls\"></div>\n</div>");
}
function handleSavedPMOptionEvents() {
    $qs('body', function ($el1) {
        $el1.addEventListener('click', function (e) {
            var $target = e.target;
            var $pmOptionSelector = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos-container,.pp-pm-sos-toggle');
            if (!$pmOptionSelector) {
                $qsAll('.pp-pm-sos-container', function ($el) { return $el.classList.add('hide'); });
            }
        });
    });
    $qsAll('.pp-pms', function ($el2) {
        $el2.addEventListener('click', function (e) {
            var _a, _b, _c, _d;
            e.preventDefault();
            e.stopPropagation();
            var $target = e.target;
            var $savedOption = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-saved-option ');
            var $sosToggle = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos-toggle');
            var $sosContainer = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos');
            var $sosRemove = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos li[data-remove]');
            if (!$savedOption) {
                return;
            }
            var providerKey = (_a = $savedOption.dataset.provider) !== null && _a !== void 0 ? _a : '';
            var methodKey = (_b = $savedOption.dataset.method) !== null && _b !== void 0 ? _b : '';
            var savedIndex = (_c = $savedOption.dataset.index) !== null && _c !== void 0 ? _c : '';
            $qsAll('.pp-pm-sos-container', function ($el) { return $el.classList.add('hide'); });
            if (!$sosToggle && !$sosContainer) {
                store.dispatch(setPaymentMethod({
                    provider: providerKey,
                    method: methodKey,
                    index: savedIndex
                }));
            }
            else if ($sosContainer) {
                if ($sosRemove) {
                    var $existingOptions = $qsAll(".pp-pm-saved-option[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
                    $existingOptions.forEach(function ($option) {
                        if ($option.dataset.index) {
                            $option.remove();
                        }
                    });
                    var $existingSelectedOptions = $qsAll("#pp-selected-pm .pp-pm-selected-option[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
                    $existingSelectedOptions.forEach(function ($option) {
                        if ($option.dataset.index) {
                            $option.remove();
                        }
                    });
                    store.dispatch(removeSavedPaymentMethod({
                        id: providerKey + ':' + methodKey,
                        index: savedIndex
                    }));
                    saveCustomerToBrowser();
                    var pm = PaymentConfiguration.checkEligibleOrFindAlternate({
                        provider: PaymentConfiguration.selectedProvider(),
                        method: PaymentConfiguration.selectedProviderMethod(),
                        index: PaymentConfiguration.selectedProviderMethodIndex()
                    });
                    if (pm) {
                        store.dispatch(setPaymentMethod(pm));
                    }
                }
            }
            else {
                (_d = $savedOption.querySelector('.pp-pm-sos-container')) === null || _d === void 0 ? void 0 : _d.classList.toggle('hide');
            }
        });
    });
}
function processSavedPMOptions(providerKey, methodKey) {
    var _a = PeachPayCustomer.retrieveSavedPaymentMethods(providerKey, methodKey), savedMethods = _a[0], methodConfig = _a[1];
    if (!methodConfig) {
        return;
    }
    renderSavedPMOption(providerKey, methodKey, '', methodConfig);
    if (!methodConfig.reusable) {
        return;
    }
    savedMethods === null || savedMethods === void 0 ? void 0 : savedMethods.forEach(function (saved, index) { return renderSavedPMOption(providerKey, methodKey, String(index), methodConfig, saved); });
    renderNewPMOptionButton(providerKey, methodKey, methodConfig);
}
function renderSavedPMOption(providerKey, methodKey, index, method, saved) {
    var $existingOption = $qsAll(".pp-pm-saved-option[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"][data-index=\"").concat(index, "\"]"));
    if ($existingOption.length) {
        updateSavedPMOptionHTML($existingOption, providerKey, methodKey, index, method);
    }
    else {
        var html_4 = buildSavedPMOptionTemplate(providerKey, methodKey, index, method, saved);
        $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"] .pp-pm-options"), function ($el) { return $el.insertAdjacentHTML('beforeend', html_4); });
    }
}
function updateSavedPMOptionHTML($existingOption, providerKey, methodKey, index, method) {
    var isSelected = PaymentConfiguration.isProviderAndMethodSelected(providerKey, methodKey, index);
    if (isSelected && index) {
        $existingOption.forEach(function ($el) { return $el.classList.add('selected'); });
    }
    else {
        $existingOption.forEach(function ($el) { return $el.classList.remove('selected'); });
    }
    var indexActive = PaymentConfiguration.selectedProviderMethodIndex();
    if (method.reusable) {
        if (indexActive && index) {
            $existingOption.forEach(function ($el) { return $el.classList.remove('hide'); });
        }
        else if (!indexActive && !index) {
            $existingOption.forEach(function ($el) { return $el.classList.remove('hide'); });
        }
        else {
            $existingOption.forEach(function ($el) { return $el.classList.add('hide'); });
        }
    }
    else {
        $existingOption.forEach(function ($el) { return $el.classList.remove('hide'); });
    }
}
function buildSavedPMOptionTemplate(providerKey, methodKey, index, method, saved) {
    var _a, _b, _c, _d, _e, _f, _g, _h;
    var isSelected = PaymentConfiguration.isProviderAndMethodSelected(providerKey, methodKey, index);
    var indexActive = PaymentConfiguration.selectedProviderMethodIndex();
    if (!method.reusable || !index || !saved) {
        return "\n<div class=\"pp-pm-saved-option ".concat(indexActive ? 'hide' : '', "\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"\">\n\t").concat(method.description, "\n</div>");
    }
    return "\n<div class=\"pp-pm-saved-option ".concat(isSelected ? 'selected' : '', " ").concat(indexActive ? '' : 'hide', "\" tabindex=\"0\" role=\"button\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"").concat(index, "\">\n\t<span style=\"font-family: 'Helvetica', 'Arial',monospace;\">\n\t\t<span style=\"width: 3rem;padding-right: 4px;\">\n\t\t\t<img class=\"pp-pm-title\" src=\"img/marks/").concat((_b = (_a = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _a === void 0 ? void 0 : _a['brand']) !== null && _b !== void 0 ? _b : '', ".svg\">\n\t\t</span>\n\t\t\u2022\u2022\u2022\u2022\n\t\t").concat((_d = (_c = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _c === void 0 ? void 0 : _c['last4']) !== null && _d !== void 0 ? _d : '', "\n\t</span>\n\t<span class=\"muted\" style=\"float:right;display: inline-block;padding: 0.2rem;\">\n\t\t").concat((_f = (_e = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _e === void 0 ? void 0 : _e['exp_month']) !== null && _f !== void 0 ? _f : 'MM', "/").concat((_h = (_g = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _g === void 0 ? void 0 : _g['exp_year']) !== null && _h !== void 0 ? _h : 'YY', "\n\t\t<img class=\"pp-pm-sos-toggle\" src=\"img/dot-dot-dot.svg\">\n\t\t<span class=\"pp-pm-sos-container hide\">\n\t\t\t<div class=\"pp-pm-sos\">\n\t\t\t\t<li data-remove role=\"button\" tabindex=\"-1\">Remove</li>\n\t\t\t\t<li data-cancel role=\"button\" tabindex=\"-1\">Cancel</li>\n\t\t\t</div>\n\t\t</span>\n\t</span>\n</div>");
}
function handleNewPMOptionButtonEvents() {
    $qsAll('.pp-pms', function ($el) {
        $el.addEventListener('click', function (e) {
            var _a, _b;
            var $target = e.target;
            var $pmType = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-option-new');
            var $pmSpan = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-option-new span');
            if (!$pmType || !$pmSpan) {
                return;
            }
            var providerKey = (_a = $pmType.dataset.provider) !== null && _a !== void 0 ? _a : '';
            var methodKey = (_b = $pmType.dataset.method) !== null && _b !== void 0 ? _b : '';
            store.dispatch(setPaymentMethod({
                provider: providerKey,
                method: methodKey
            }));
        });
    });
}
function renderNewPMOptionButton(providerKey, methodKey, method) {
    var $existingOption = $qsAll(".pp-pm-option-new[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
    if ($existingOption.length) {
        updateNewPMOptionButtonHTML($existingOption);
    }
    else {
        var html_5 = buildNewPMOptionButtonTemplate(providerKey, methodKey, method);
        $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"] .pp-pm-controls"), function ($el) { return $el.insertAdjacentHTML('beforeend', html_5); });
    }
}
function updateNewPMOptionButtonHTML($existingOption) {
    var isVisible = PaymentConfiguration.selectedProviderMethodIndex();
    if (isVisible) {
        $existingOption.forEach(function ($el) { return $el.classList.remove('hide'); });
    }
    else {
        $existingOption.forEach(function ($el) { return $el.classList.add('hide'); });
    }
}
function buildNewPMOptionButtonTemplate(providerKey, methodKey, method) {
    var _a;
    var isVisible = PaymentConfiguration.selectedProviderMethodIndex();
    return "\n<div class=\"pp-pm-option-new ".concat(isVisible ? '' : 'hide', "\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t<span class=\"muted\" tabindex=\"0\" role=\"button\">").concat((_a = method.addNew) !== null && _a !== void 0 ? _a : '', "</span>\n</div>");
}
function getStripeInstance(context, usePeachPayStripe) {
    if (usePeachPayStripe) {
        return [
            context.peachpayStripe,
            context.elements
        ];
    }
    return [
        context.connectStripe,
        context.elements
    ];
}
function initStripeAfterPayMethod() {
    var _this = this;
    return {
        config: {
            name: 'Afterpay',
            description: 'After selecting pay you will be redirected to complete your payment.',
            reusable: false,
            assets: {
                title: {
                    src: 'img/marks/afterpay.svg'
                },
                badge: {
                    src: 'img/marks/afterpay.svg'
                }
            },
            supports: {
                currencies: [
                    'USD',
                    'CAD',
                    'GBP',
                    'AUD',
                    'NZD'
                ],
                productTypes: [],
                merchantCountries: [
                    'US',
                    'GB',
                    'ES',
                    'IT',
                    'IE',
                    'CA',
                    'AU',
                    'FR',
                    'NZ'
                ],
                customerCountries: [
                    'US',
                    'CA',
                    'GB',
                    'AU',
                    'NZ'
                ]
            }
        },
        createPaymentMethod: function (context) { return __awaiter(_this, void 0, void 0, function () {
            var stripe, config, _a, paymentMethod, error;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        stripe = getStripeInstance(context, Environment.isOurStore())[0];
                        config = {
                            type: 'afterpay_clearpay',
                            billing_details: __assign({}, PeachPayCustomer.stripeBillingDetails())
                        };
                        return [4, stripe.createPaymentMethod(config)];
                    case 1:
                        _a = _b.sent(), paymentMethod = _a.paymentMethod, error = _a.error;
                        if (error) {
                            return [2, null];
                        }
                        return [2, paymentMethod];
                }
            });
        }); },
        confirm: function (context, clientSecret, options) { return __awaiter(_this, void 0, void 0, function () {
            var stripe, error;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        stripe = getStripeInstance(context, Environment.isOurStore())[0];
                        return [4, stripe.confirmAfterpayClearpayPayment(clientSecret, {
                                return_url: options.intermediateURL
                            })];
                    case 1:
                        error = (_a.sent()).error;
                        if (error) {
                            return [2, [
                                    true,
                                    error.message
                                ]];
                        }
                        return [2, [
                                false
                            ]];
                }
            });
        }); }
    };
}
function setupStripeButton() {
    store.subscribe(function () {
        renderStripeButtonDisplay(PaymentConfiguration.selectedProvider(), Environment.modalUI.page(), Environment.modalUI.loadingMode());
        renderStripeButtonLoading(PaymentConfiguration.selectedProvider(), Environment.modalUI.loadingMode());
    });
}
function renderStripeButtonDisplay(provider, page, loadingMode) {
    if (provider === 'stripe' && page === 'payment') {
        $qsAll('.stripe-btn-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn-container', function ($element) { return $element.classList.add('hide'); });
    }
    if (provider === 'stripe' && page === 'payment' && loadingMode !== 'loading') {
        $qsAll('.stripe-btn', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn', function ($element) { return $element.classList.add('hide'); });
    }
}
function renderStripeButtonLoading(provider, mode) {
    if (provider !== 'stripe') {
        return;
    }
    if (mode === 'finished') {
        $qsAll('.stripe-btn', function ($element) { return $element.disabled = false; });
    }
    else {
        $qsAll('.stripe-btn', function ($element) { return $element.disabled = true; });
    }
    if (mode === 'loading') {
        $qsAll('.pp-stripe-btn-spinner-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.pp-stripe-btn-spinner-container ', function ($element) { return $element.classList.add('hide'); });
    }
    if (mode === 'processing') {
        $qsAll('.stripe-btn > .button-text', function ($element) { return $element.innerHTML = getLocaleText('processing'); });
        $qsAll('.stripe-btn-spinner', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn > .button-text', function ($element) { return $element.innerHTML = "".concat(getLocaleText('pay'), " ").concat(formatCurrencyString(DefaultCart.total())); });
        $qsAll('.stripe-btn-spinner', function ($element) { return $element.classList.add('hide'); });
    }
}
function initStripeCardMethod() {
    var _this = this;
    return {
        config: {
            name: 'Card',
            description: '',
            addNew: '+ NEW CARD',
            reusable: true,
            assets: {
                title: {
                    src: 'img/marks/credit-card-regular.svg'
                },
                badge: {
                    src: 'img/marks/credit-card-regular.svg'
                }
            },
            supports: {
                currencies: [
                    'ALL'
                ],
                productTypes: [
                    'subscriptions'
                ],
                merchantCountries: [
                    'ALL'
                ],
                customerCountries: [
                    'ALL'
                ]
            }
        },
        saveMethod: function (paymentMethod) {
            var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
            return [
                'stripe:card',
                {
                    id: paymentMethod.id,
                    name: 'Card',
                    provider: 'stripe',
                    type: 'card',
                    metadata: {
                        brand: (_b = (_a = paymentMethod.card) === null || _a === void 0 ? void 0 : _a.brand) !== null && _b !== void 0 ? _b : '',
                        last4: (_d = (_c = paymentMethod.card) === null || _c === void 0 ? void 0 : _c.last4) !== null && _d !== void 0 ? _d : '',
                        exp_month: (_f = (_e = paymentMethod.card) === null || _e === void 0 ? void 0 : _e.exp_month) !== null && _f !== void 0 ? _f : '',
                        exp_year: (_h = (_g = paymentMethod.card) === null || _g === void 0 ? void 0 : _g.exp_year) !== null && _h !== void 0 ? _h : '',
                        funding: (_k = (_j = paymentMethod.card) === null || _j === void 0 ? void 0 : _j.funding) !== null && _k !== void 0 ? _k : 'unknown'
                    }
                }
            ];
        },
        activate: function (context) {
            var _a = getStripeInstance(context, true), _ = _a[0], elements = _a[1];
            var style = {
                base: {
                    color: '#333',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '18px',
                    '::placeholder': {
                        color: '#999'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };
            var $card = elements.create('card', {
                style: style,
                hidePostalCode: true
            });
            $qsAll("div.pp-pm-saved-option[data-provider=\"stripe\"][data-method=\"card\"][data-index=\"\"]", function ($el) { return $el.innerHTML = ''; });
            var previousCustomerMode = null;
            store.subscribe(function () {
                var currentCustomerMode = Environment.customer.existing();
                if (previousCustomerMode !== currentCustomerMode) {
                    previousCustomerMode = currentCustomerMode;
                    $card.unmount();
                    if (Environment.customer.existing()) {
                        $card.mount("#pp-pms-existing div.pp-pm-saved-option[data-provider=\"stripe\"][data-method=\"card\"][data-index=\"\"]");
                    }
                    else {
                        $card.mount("#pp-pms-new div.pp-pm-saved-option[data-provider=\"stripe\"][data-method=\"card\"][data-index=\"\"]");
                    }
                }
            });
        },
        createPaymentMethod: function (context) { return __awaiter(_this, void 0, void 0, function () {
            var _a, stripe, elements, $card, config, _b, paymentMethod, error;
            return __generator(this, function (_c) {
                switch (_c.label) {
                    case 0:
                        _a = getStripeInstance(context, true), stripe = _a[0], elements = _a[1];
                        $card = elements.getElement('card');
                        if (!$card) {
                            return [2, null];
                        }
                        config = {
                            type: 'card',
                            card: $card,
                            billing_details: __assign({}, PeachPayCustomer.stripeBillingDetails())
                        };
                        return [4, stripe.createPaymentMethod(config)];
                    case 1:
                        _b = _c.sent(), paymentMethod = _b.paymentMethod, error = _b.error;
                        if (error) {
                            displayCardError(error.message);
                            return [2, null];
                        }
                        return [2, paymentMethod];
                }
            });
        }); },
        displayPaymentIntentError: displayCardError,
        confirm: function (context, clientSecret, options) { return __awaiter(_this, void 0, void 0, function () {
            var stripe, _a, error, paymentIntent;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        stripe = getStripeInstance(context, Environment.isOurStore())[0];
                        return [4, stripe.confirmCardPayment(clientSecret)];
                    case 1:
                        _a = _b.sent(), error = _a.error, paymentIntent = _a.paymentIntent;
                        if (error) {
                            displayCardError(error.message);
                            store.dispatch(stopModalLoading());
                            return [2, [
                                    false
                                ]];
                        }
                        if (paymentIntent.status === 'succeeded') {
                            if (window.top) {
                                clearLocalSession();
                                window.top.location = options.directURL;
                            }
                        }
                        return [2, [
                                false
                            ]];
                }
            });
        }); }
    };
}
function displayCardError(error) {
    var _a;
    $qsAll('.pp-stripe-pm-error-text', function ($el) { return $el.remove(); });
    var $container = $qsAll("div.pp-pm-saved-option[data-provider=\"stripe\"][data-method=\"card\"][data-index=\"\"]");
    $container.forEach(function ($el) {
        $el.insertAdjacentHTML('afterend', "<div class=\"pp-stripe-pm-error-text\"><span>".concat(error, "</span></div>"));
    });
    (_a = $qs('#pp-selected-pm')) === null || _a === void 0 ? void 0 : _a.insertAdjacentHTML('beforeend', "<div class=\"pp-stripe-pm-error-text\"><span>".concat(error, "</span></div>"));
    setTimeout(function () {
        $qsAll('.pp-stripe-pm-error-text', function ($el) { return $el.remove(); });
    }, 5000);
}
function initStripeKlarnaMethod() {
    var _this = this;
    return {
        config: {
            name: 'Klarna',
            description: 'After selecting pay you will be redirected to complete your payment.',
            reusable: false,
            assets: {
                title: {
                    src: 'img/marks/klarna.svg'
                },
                badge: {
                    src: 'img/marks/klarna.svg'
                }
            },
            supports: {
                currencies: [
                    'EUR',
                    'USD',
                    'GBP',
                    'DKK',
                    'SEK',
                    'NOK'
                ],
                productTypes: [],
                merchantCountries: [
                    'US',
                    'AT',
                    'BE',
                    'DK',
                    'EE',
                    'FI',
                    'FR',
                    'DE',
                    'GR',
                    'IE',
                    'IT',
                    'LV',
                    'LT',
                    'NL',
                    'NO',
                    'SK',
                    'SI',
                    'ES',
                    'SE',
                    'GB',
                ],
                customerCountries: [
                    'US',
                    'AT',
                    'BE',
                    'DK',
                    'FI',
                    'FR',
                    'DE',
                    'IE',
                    'IT',
                    'NL',
                    'NO',
                    'ES',
                    'SE',
                    'GB',
                ]
            }
        },
        createPaymentMethod: function (context) { return __awaiter(_this, void 0, void 0, function () {
            var stripe, config, _a, paymentMethod, error;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        stripe = getStripeInstance(context, Environment.isOurStore())[0];
                        config = {
                            type: 'klarna',
                            billing_details: __assign({}, PeachPayCustomer.stripeBillingDetails())
                        };
                        return [4, stripe.createPaymentMethod(config)];
                    case 1:
                        _a = _b.sent(), paymentMethod = _a.paymentMethod, error = _a.error;
                        if (error) {
                            return [2, null];
                        }
                        return [2, paymentMethod];
                }
            });
        }); },
        confirm: function (context, clientSecret, options) { return __awaiter(_this, void 0, void 0, function () {
            var stripe, error;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        stripe = getStripeInstance(context, Environment.isOurStore())[0];
                        return [4, stripe.confirmKlarnaPayment(clientSecret, {
                                return_url: options.intermediateURL
                            })];
                    case 1:
                        error = (_a.sent()).error;
                        if (error) {
                            return [2, [
                                    true,
                                    error.message
                                ]];
                        }
                        return [2, [
                                false
                            ]];
                }
            });
        }); }
    };
}
function activateStripePaymentRequest(context, orderService) {
    var _this = this;
    var _a;
    if (!Feature.enabled(FeatureFlag.STRIPE_PAYMENT_REQUEST) || Feature.enabled(FeatureFlag.ADDITIONAL_FIELDS) || Environment.plugin.pageType() === 'product') {
        return;
    }
    var initMessage = {
        event: 'pp-init-stripe-payment-request',
        isOurStore: Environment.isOurStore(),
        currencyCode: MerchantConfiguration.currency.code(),
        cartCalculationRecord: store.getState().calculatedCarts,
        stripe: {
            publicKey: context.service.getStripePublicKey(),
            connectId: context.service.getStripeConnectId()
        }
    };
    (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage(initMessage, '*');
    onWindowDataFetch('pp-stripe-payment-request-address-change', handleStripePaymentRequestAddressChange);
    onWindowDataFetch('pp-stripe-payment-request-shipping-change', handleStripePaymentRequestShippingChange);
    onWindowDataFetch('pp-stripe-payment-request-confirm', function (request) { return __awaiter(_this, void 0, void 0, function () { return __generator(this, function (_a) {
        switch (_a.label) {
            case 0: return [4, handleStripePaymentRequestProcessPayment(request, context, orderService)];
            case 1: return [2, _a.sent()];
        }
    }); }); });
    var previousUpdateData = '';
    var unsubscribePaymentRequestUpdates = store.subscribe(function () {
        var _a;
        var paymentRequestDataUpdate = getStripePaymentRequestUpdate();
        var updateData = JSON.stringify(paymentRequestDataUpdate);
        if (previousUpdateData !== updateData) {
            (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage(paymentRequestDataUpdate, '*');
        }
    });
    onWindowMessage('pp-stripe-payment-request-stop', unsubscribePaymentRequestUpdates);
}
function getStripePaymentRequestUpdate() {
    return {
        event: 'pp-update-stripe-payment-request',
        currencyCode: MerchantConfiguration.currency.code(),
        cartCalculationRecord: store.getState().calculatedCarts
    };
}
function handleStripePaymentRequestProcessPayment(request, context, orderService) {
    var _a, _b, _c;
    return __awaiter(this, void 0, void 0, function () {
        var orderResponse, paymentIntentResponse, stripe, _d, error, paymentIntent, error_4;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    store.dispatch(updateCustomer({
                        email: request.payerEmail,
                        phone: request.payerPhone,
                        name_first: (_a = request.payerName.split(' ')[0]) !== null && _a !== void 0 ? _a : '',
                        name_last: (_b = request.payerName.split(' ')[1]) !== null && _b !== void 0 ? _b : '',
                        address1: request.shippingAddress.addressLine[0],
                        address2: (_c = request.shippingAddress.addressLine[1]) !== null && _c !== void 0 ? _c : '',
                        city: request.shippingAddress.city,
                        state: request.shippingAddress.region,
                        country: request.shippingAddress.country,
                        postal: request.shippingAddress.postalCode
                    }));
                    _e.label = 1;
                case 1:
                    _e.trys.push([1, 6, , 7]);
                    return [4, validateAddress()];
                case 2:
                    if (!(_e.sent())) {
                        return [2, {
                                status: 'invalid_shipping_address'
                            }];
                    }
                    return [4, orderService.placeOrder()];
                case 3:
                    orderResponse = _e.sent();
                    if (orderResponse.result === 'failure') {
                        captureSentryException(new Error("Order failed to be placed for the payment request flow."), {
                            'payment_request': request,
                            'order_response': orderResponse
                        });
                        return [2, {
                                status: 'fail'
                            }];
                    }
                    return [4, context.service.createPaymentIntent(orderResponse, {
                            paymentMethodType: 'none'
                        })];
                case 4:
                    paymentIntentResponse = _e.sent();
                    if (!paymentIntentResponse.success) {
                        captureSentryException(new Error("Creating payment intent failed for payment request flow."), {
                            'payment_request': request,
                            'payment_intent_response': paymentIntentResponse
                        });
                        return [2, {
                                status: 'fail'
                            }];
                    }
                    stripe = getStripeInstance(context, Environment.isOurStore())[0];
                    return [4, stripe.confirmCardPayment(paymentIntentResponse.data.stripe.client_secret, {
                            payment_method: {
                                card: {
                                    token: request.token.id
                                },
                                billing_details: PeachPayCustomer.stripeBillingDetails()
                            }
                        })];
                case 5:
                    _d = _e.sent(), error = _d.error, paymentIntent = _d.paymentIntent;
                    if (!error && paymentIntent.status === 'succeeded') {
                        clearLocalSession();
                        return [2, {
                                status: 'success',
                                redirectURL: orderService.getOrderRedirect(orderResponse)
                            }];
                    }
                    return [2, {
                            status: 'fail'
                        }];
                case 6:
                    error_4 = _e.sent();
                    if (error_4 instanceof Error) {
                        captureSentryException(new Error("Stripe payment request flow failed"), {
                            exception: error_4
                        });
                    }
                    return [2, {
                            status: 'fail'
                        }];
                case 7: return [2];
            }
        });
    });
}
function handleStripePaymentRequestAddressChange(request) {
    var _a, _b, _c, _d, _e, _f;
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_g) {
            switch (_g.label) {
                case 0:
                    store.dispatch(updateCustomer(__assign(__assign({}, PeachPayCustomer.data()), { address1: (_a = request.addressLine[0]) !== null && _a !== void 0 ? _a : '', address2: (_b = request.addressLine[1]) !== null && _b !== void 0 ? _b : '', city: (_c = request.city) !== null && _c !== void 0 ? _c : '', postal: (_d = request.postalCode) !== null && _d !== void 0 ? _d : '', state: (_e = request.region) !== null && _e !== void 0 ? _e : '', country: (_f = request.country) !== null && _f !== void 0 ? _f : '' })));
                    return [4, requestCartCalculation()];
                case 1:
                    _g.sent();
                    return [2, getStripePaymentRequestUpdate()];
            }
        });
    });
}
function handleStripePaymentRequestShippingChange(request) {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    store.dispatch(updateCartPackageShippingMethod({
                        cartKey: '0',
                        shippingPackageKey: '0',
                        packageMethodId: request.id
                    }));
                    return [4, requestCartCalculation()];
                case 1:
                    _a.sent();
                    return [2, getStripePaymentRequestUpdate()];
            }
        });
    });
}
function setupPoweredByStripe() {
    store.subscribe(function () {
        if (Feature.enabled(FeatureFlag.STRIPE)) {
            $qsAll('.powered-by-stripe', function ($el) { return $el === null || $el === void 0 ? void 0 : $el.classList.remove('hide'); });
        }
        else {
            $qsAll('.powered-by-stripe', function ($el) { return $el === null || $el === void 0 ? void 0 : $el.classList.add('hide'); });
        }
    });
}
var STRIPE_TEST_PK = 'pk_test_CnL2kA52V5dRqZbjlJ0sZ2gr00uBrOEmQQ';
var STRIPE_LIVE_PK = 'pk_live_oROnIQDuexHZpnEOcUff3CRz00asaOOCAL';
function initStripePaymentProvider(message, orderService) {
    return __awaiter(this, void 0, void 0, function () {
        var stripePublicKey, peachpayStripeOptions, peachpayStripe, connectStripeOptions, connectStripe, elements, context, capabilities, paymentMethods;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!Feature.enabled(FeatureFlag.STRIPE)) {
                        return [2];
                    }
                    stripePublicKey = Environment.isTestOrDevSite() ? STRIPE_TEST_PK : STRIPE_LIVE_PK;
                    peachpayStripeOptions = buildStripeOptions(message, true);
                    peachpayStripe = Stripe(stripePublicKey, peachpayStripeOptions);
                    connectStripeOptions = buildStripeOptions(message, false);
                    connectStripe = Stripe(stripePublicKey, connectStripeOptions);
                    elements = peachpayStripe.elements();
                    context = {
                        peachpayStripe: peachpayStripe,
                        connectStripe: connectStripe,
                        elements: elements,
                        service: {
                            getStripePublicKey: function () { return stripePublicKey; },
                            getStripeConnectId: function () { var _a; return (_a = Feature.metadata(FeatureFlag.STRIPE, 'connected_stripe_account')) !== null && _a !== void 0 ? _a : ''; },
                            createPaymentIntent: createPaymentIntent
                        }
                    };
                    return [4, getStripeAccountCapabilities()];
                case 1:
                    capabilities = _a.sent();
                    paymentMethods = setupStripeMethods(capabilities);
                    activateStripeMethods(context, paymentMethods, orderService);
                    setupStripeButton();
                    setupPoweredByStripe();
                    activateStripePaymentRequest(context, orderService);
                    return [2];
            }
        });
    });
}
function setupStripeMethods(account) {
    var paymentMethods = {};
    var cardMethod = initStripeCardMethod();
    paymentMethods['card'] = cardMethod;
    if (isPaymentMethodActive(account, function (capabilities) { return capabilities.klarna_payments; })) {
        var klarnaMethod = initStripeKlarnaMethod();
        paymentMethods['klarna'] = klarnaMethod;
    }
    if (isPaymentMethodActive(account, function (capabilities) { return capabilities.afterpay_clearpay_payments; })) {
        var afterpayMethod = initStripeAfterPayMethod();
        paymentMethods['afterpay_clearpay'] = afterpayMethod;
    }
    var stripeProvider = {
        'methods': {}
    };
    for (var method in paymentMethods) {
        stripeProvider.methods[method] = paymentMethods[method].config;
    }
    store.dispatch(registerPaymentProvider({
        'stripe': stripeProvider
    }));
    return paymentMethods;
}
function isPaymentMethodActive(account, selector) {
    if (!account) {
        return false;
    }
    var capability = selector(account.capabilities);
    return Boolean(capability) && capability === 'active';
}
function activateStripeMethods(context, methods, orderService) {
    return __awaiter(this, void 0, void 0, function () {
        var activations, key, method, confirm;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    activations = [];
                    for (key in methods) {
                        method = methods[key];
                        if (method.activate) {
                            activations.push(method === null || method === void 0 ? void 0 : method.activate(context));
                        }
                    }
                    return [4, Promise.all(activations)];
                case 1:
                    _a.sent();
                    confirm = function (event) {
                        if (!checkRequiredFields()) {
                            return;
                        }
                        var $target = event.target;
                        var $button = $target === null || $target === void 0 ? void 0 : $target.closest('button');
                        if (!$button) {
                            return;
                        }
                        var method = methods[PaymentConfiguration.selectedProviderMethod()];
                        if (!method) {
                            return;
                        }
                        existingPaymentMethodFlow(context, method, orderService);
                        return;
                    };
                    $qsAll('.stripe-btn', function ($el) { return $el.addEventListener('click', confirm); });
                    return [2];
            }
        });
    });
}
function newPaymentMethodFlow(context, providerMethod, orderService) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var paymentMethod, orderResponse, paymentIntentResponse, savedDetails, successURL, intermediateURL, _c, failed, message;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0: return [4, providerMethod.createPaymentMethod(context)];
                case 1:
                    paymentMethod = _d.sent();
                    if (!paymentMethod) {
                        store.dispatch(stopModalLoading());
                        return [2];
                    }
                    return [4, orderService.placeOrder()];
                case 2:
                    orderResponse = _d.sent();
                    if (orderResponse.result === 'failure') {
                        store.dispatch(stopModalLoading());
                        return [2];
                    }
                    return [4, createPaymentIntent(orderResponse, {
                            paymentMethodId: paymentMethod.id,
                            paymentMethodType: paymentMethod.type
                        })];
                case 3:
                    paymentIntentResponse = _d.sent();
                    if (!!paymentIntentResponse.success) return [3, 8];
                    store.dispatch(stopModalLoading());
                    if (!(paymentIntentResponse.message && providerMethod.displayPaymentIntentError)) return [3, 5];
                    providerMethod.displayPaymentIntentError(paymentIntentResponse.message);
                    return [4, orderService.addOrderNote(orderResponse, 'Payment attempt failed. Reason: ' + paymentIntentResponse.message)];
                case 4:
                    _d.sent();
                    return [3, 7];
                case 5: return [4, orderService.addOrderNote(orderResponse, 'Payment attempt failed. Reason: An unknown error occured while creating the Stripe Payment Intent')];
                case 6:
                    _d.sent();
                    store.dispatch(setOrderError(getLocaleText('error-occurred')));
                    captureSentryException(new Error("Creating payment intent failed for new payment method flow."), {
                        'payment_method': paymentMethod,
                        'payment_intent_response': paymentIntentResponse
                    });
                    _d.label = 7;
                case 7: return [2];
                case 8:
                    store.dispatch(updateCustomerStripeId(paymentIntentResponse.data.stripe.customer_id));
                    if (providerMethod.saveMethod) {
                        savedDetails = providerMethod.saveMethod(paymentMethod);
                        store.dispatch(addSavedPaymentMethod(savedDetails));
                    }
                    store.dispatch(updateCustomerPreferredPaymentMethod({
                        provider: PaymentConfiguration.selectedProvider(),
                        method: PaymentConfiguration.selectedProviderMethod(),
                        index: providerMethod.config.reusable ? '0' : ''
                    }));
                    saveCustomerToBrowser();
                    successURL = orderService.getOrderRedirect(orderResponse);
                    intermediateURL = buildRedirectStateURL(successURL, (_b = (_a = window.top) === null || _a === void 0 ? void 0 : _a.location.href) !== null && _b !== void 0 ? _b : '');
                    return [4, providerMethod.confirm(context, paymentIntentResponse.data.stripe.client_secret, {
                            intermediateURL: intermediateURL,
                            directURL: orderResponse.redirect
                        })];
                case 9:
                    _c = _d.sent(), failed = _c[0], message = _c[1];
                    if (!failed) return [3, 11];
                    return [4, orderService.addOrderNote(orderResponse, 'Payment attempt failed. Reason: ' + message)];
                case 10:
                    _d.sent();
                    store.dispatch(stopModalLoading());
                    store.dispatch(setOrderError(getLocaleText('error-occurred')));
                    captureSentryException(new Error("Confirming Stripe payment intent failed for new payment method flow."), {
                        'confirm_failure': message,
                        'payment_method': paymentMethod,
                        'payment_intent_response': paymentIntentResponse,
                        'success_url': successURL,
                        'intermediate_url': intermediateURL
                    });
                    return [2];
                case 11: return [2];
            }
        });
    });
}
function existingPaymentMethodFlow(context, providerMethod, orderService) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var savedPaymentMethod, orderResponse, paymentIntentResponse, successURL, intermediateURL, _c, failed, message;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0:
                    store.dispatch(startModalProcessing());
                    if (!providerMethod.config.reusable || !PaymentConfiguration.selectedProviderMethodIndex()) {
                        return [2, newPaymentMethodFlow(context, providerMethod, orderService)];
                    }
                    savedPaymentMethod = PeachPayCustomer.retrieveSavedPaymentMethod(PaymentConfiguration.selectedProvider(), PaymentConfiguration.selectedProviderMethod(), PaymentConfiguration.selectedProviderMethodIndex())[0];
                    if (!savedPaymentMethod || !savedPaymentMethod.id) {
                        store.dispatch(stopModalLoading());
                        return [2];
                    }
                    return [4, orderService.placeOrder()];
                case 1:
                    orderResponse = _d.sent();
                    if (orderResponse.result === 'failure') {
                        store.dispatch(stopModalLoading());
                        return [2];
                    }
                    return [4, createPaymentIntent(orderResponse, {
                            paymentMethodId: savedPaymentMethod.id,
                            paymentMethodType: PaymentConfiguration.selectedProviderMethod()
                        })];
                case 2:
                    paymentIntentResponse = _d.sent();
                    if (!!paymentIntentResponse.success) return [3, 7];
                    store.dispatch(stopModalLoading());
                    if (!(paymentIntentResponse.message && providerMethod.displayPaymentIntentError)) return [3, 4];
                    return [4, orderService.addOrderNote(orderResponse, 'Payment attempt failed. Reason: ' + paymentIntentResponse.message)];
                case 3:
                    _d.sent();
                    providerMethod.displayPaymentIntentError(paymentIntentResponse.message);
                    return [3, 6];
                case 4: return [4, orderService.addOrderNote(orderResponse, 'Payment attempt failed. Reason: An unknown error occured while creating the Stripe Payment Intent')];
                case 5:
                    _d.sent();
                    store.dispatch(setOrderError(getLocaleText('error-occurred')));
                    captureSentryException(new Error("Creating Stripe payment intent failed for existing payment flow."), {
                        'saved_method': savedPaymentMethod,
                        'payment_intent_response': paymentIntentResponse
                    });
                    _d.label = 6;
                case 6: return [2];
                case 7:
                    store.dispatch(updateCustomerPreferredPaymentMethod({
                        provider: PaymentConfiguration.selectedProvider(),
                        method: PaymentConfiguration.selectedProviderMethod(),
                        index: PaymentConfiguration.selectedProviderMethodIndex()
                    }));
                    saveCustomerToBrowser();
                    successURL = orderService.getOrderRedirect(orderResponse);
                    intermediateURL = buildRedirectStateURL(successURL, (_b = (_a = window.top) === null || _a === void 0 ? void 0 : _a.location.href) !== null && _b !== void 0 ? _b : '');
                    return [4, providerMethod.confirm(context, paymentIntentResponse.data.stripe.client_secret, {
                            intermediateURL: intermediateURL,
                            directURL: orderResponse.redirect
                        })];
                case 8:
                    _c = _d.sent(), failed = _c[0], message = _c[1];
                    if (!failed) return [3, 10];
                    return [4, orderService.addOrderNote(orderResponse, 'Payment failed. Reason: ' + message)];
                case 9:
                    _d.sent();
                    store.dispatch(stopModalLoading());
                    store.dispatch(setOrderError(getLocaleText('error-occurred')));
                    captureSentryException(new Error("Confirming payment intent failed for existing payment flow."), {
                        'confirm_failure': message,
                        'saved_method': savedPaymentMethod,
                        'payment_intent_response': paymentIntentResponse,
                        'success_url': successURL,
                        'intermediate_url': intermediateURL
                    });
                    return [2];
                case 10: return [2];
            }
        });
    });
}
function buildRedirectStateURL(successURL, failureURL) {
    var _a;
    var createState = function () {
        if (Environment.isOurStore()) {
            return {
                key: Environment.isTestOrDevSite() ? STRIPE_TEST_PK : STRIPE_LIVE_PK,
                successURL: successURL,
                failureURL: failureURL,
                color: Environment.plugin.buttonColor()
            };
        }
        else {
            return {
                key: Environment.isTestOrDevSite() ? STRIPE_TEST_PK : STRIPE_LIVE_PK,
                connectId: Feature.metadata(FeatureFlag.STRIPE, 'connected_stripe_account'),
                successURL: successURL,
                failureURL: failureURL,
                color: Environment.plugin.buttonColor()
            };
        }
    };
    var state = btoa(JSON.stringify(createState()));
    var url = new URL((_a = Feature.metadata(FeatureFlag.STRIPE, 'redirect_url_base')) !== null && _a !== void 0 ? _a : '');
    var params = new URLSearchParams();
    params.append('state', state);
    url.search = params.toString();
    return url.toString();
}
function buildStripeOptions(message, isPeachPayConfig) {
    var _a, _b;
    if (isPeachPayConfig) {
        return {
            locale: (_a = message === null || message === void 0 ? void 0 : message.browserLocale) !== null && _a !== void 0 ? _a : 'auto'
        };
    }
    else {
        var stripeConnectAccount = Feature.metadata(FeatureFlag.STRIPE, 'connected_stripe_account');
        return {
            locale: (_b = message === null || message === void 0 ? void 0 : message.browserLocale) !== null && _b !== void 0 ? _b : 'auto',
            stripeAccount: stripeConnectAccount !== null && stripeConnectAccount !== void 0 ? stripeConnectAccount : ''
        };
    }
}
function createPaymentIntent(order, options) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var response, error_5;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    _c.trys.push([0, 2, , 3]);
                    return [4, fetchHostWindowData('pp-create-stripe-payment-intent', {
                            session: {
                                id: PeachPayOrder.sessionId(),
                                stripe: {
                                    customer_id: PeachPayCustomer.stripeId(),
                                    connect_id: (_a = Feature.metadata(FeatureFlag.STRIPE, 'connected_stripe_account')) !== null && _a !== void 0 ? _a : '',
                                    payment_method_id: (_b = options.paymentMethodId) !== null && _b !== void 0 ? _b : '',
                                    payment_method_type: options.paymentMethodType
                                }
                            },
                            order: {
                                id: order.order_id,
                                data: order
                            }
                        })];
                case 1:
                    response = _c.sent();
                    if (!response) {
                        return [2, {
                                success: false
                            }];
                    }
                    return [2, response];
                case 2:
                    error_5 = _c.sent();
                    if (error_5 instanceof Error) {
                        captureSentryException(new Error("Failed to create Stripe payment intent"), {
                            'exception': error_5
                        });
                    }
                    return [2, {
                            success: false
                        }];
                case 3: return [2];
            }
        });
    });
}
function getStripeAccountCapabilities() {
    return __awaiter(this, void 0, void 0, function () {
        var getConnectId, response, json, error_6;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 3, , 4]);
                    getConnectId = function () {
                        var _a;
                        if (Environment.isOurStore()) {
                            return '';
                        }
                        else {
                            return (_a = Feature.metadata(FeatureFlag.STRIPE, 'connected_stripe_account')) !== null && _a !== void 0 ? _a : '';
                        }
                    };
                    return [4, fetch("".concat(Environment.apiURL(), "api/v1/stripe/capabilities"), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                session: {
                                    id: PeachPayOrder.sessionId(),
                                    merchant_url: MerchantConfiguration.hostName(),
                                    stripe: {
                                        connect_id: getConnectId()
                                    }
                                }
                            })
                        })];
                case 1:
                    response = _a.sent();
                    if (!response || !response.ok) {
                        return [2, null];
                    }
                    return [4, response.json()];
                case 2:
                    json = _a.sent();
                    if (!json.success) {
                        return [2, null];
                    }
                    return [2, json.data];
                case 3:
                    error_6 = _a.sent();
                    if (error_6 instanceof Error) {
                        captureSentryException(error_6);
                    }
                    return [2, null];
                case 4: return [2];
            }
        });
    });
}
function setupPayPalButton() {
    store.subscribe(function () {
        renderPayPalButtonDisplay(PaymentConfiguration.selectedProvider(), Environment.modalUI.page(), Environment.modalUI.loadingMode());
        renderPayPalButtonLoading(PaymentConfiguration.selectedProvider(), Environment.modalUI.loadingMode());
    });
}
function renderPayPalButtonDisplay(provider, page, loadingMode) {
    var _a, _b, _c, _d, _e, _f;
    if (provider === 'paypal' && page === 'payment' && loadingMode === 'finished') {
        (_a = $qs('#paypal-btn-container')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('#paypal-btn-container-existing')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
        (_c = $qs('#paypal-btn-container-mobile')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
    }
    else {
        (_d = $qs('#paypal-btn-container')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
        (_e = $qs('#paypal-btn-container-existing')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
        (_f = $qs('#paypal-btn-container-mobile')) === null || _f === void 0 ? void 0 : _f.classList.add('hide');
    }
}
function renderPayPalButtonLoading(provider, loadingMode) {
    if (provider === 'paypal' && loadingMode !== 'finished') {
        $qsAll('.pp-paypal-spinner-container', function ($el) { return $el.classList.remove('hide'); });
    }
    else {
        $qsAll('.pp-paypal-spinner-container', function ($el) { return $el.classList.add('hide'); });
    }
}
function initPayPalDefaultMethod() {
    return {
        config: {
            name: 'PayPal',
            description: 'After selecting pay, a window will appear where you can complete your payment.',
            reusable: false,
            assets: {
                title: {
                    src: 'img/marks/paypal.svg'
                },
                badge: {
                    src: 'img/marks/paypal.svg'
                }
            },
            supports: {
                currencies: [
                    'AUD',
                    'CAD',
                    'CZK',
                    'DKK',
                    'EUR',
                    'HKD',
                    'HUF',
                    'ILS',
                    'MXN',
                    'TWD',
                    'NZD',
                    'NOK',
                    'PHP',
                    'PLN',
                    'GBP',
                    'RUB',
                    'SGD',
                    'SEK',
                    'CHF',
                    'TBH',
                    'USD',
                ],
                productTypes: [],
                merchantCountries: [
                    'ALL'
                ],
                customerCountries: [
                    'ALL'
                ]
            }
        }
    };
}
var paypalMerchantID = null;
var BN_CODE_SANDBOX = 'FLAVORsb-6jopv6540275_MP';
var BN_CODE_PRODUCTION = 'Peach_SP_PPCP';
paypalLoadScripts.loaded = new Set();
function initPayPalPaymentProvider(orderService) {
    var _this = this;
    if (!Feature.enabled(FeatureFlag.PAYPAL)) {
        return;
    }
    setupPayPalMethods();
    setupPayPalButton();
    var previousInitilizedCode = '';
    var previousCurrencyCode = '';
    store.subscribe(function () { return __awaiter(_this, void 0, void 0, function () {
        var currentCurrencyCode;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    currentCurrencyCode = MerchantConfiguration.currency.code();
                    if (previousCurrencyCode === currentCurrencyCode) {
                        return [2];
                    }
                    previousCurrencyCode = currentCurrencyCode;
                    if (!(PaymentConfiguration.eligibleMethod('paypal', 'default') && previousInitilizedCode !== currentCurrencyCode)) return [3, 2];
                    previousInitilizedCode = currentCurrencyCode;
                    return [4, loadPayPalScript()];
                case 1:
                    if (_a.sent()) {
                        initPayPalPaymentFlow(orderService);
                    }
                    _a.label = 2;
                case 2: return [2];
            }
        });
    }); });
}
function setupPayPalMethods() {
    var paymentMethods = {};
    var defaultMethod = initPayPalDefaultMethod();
    paymentMethods['default'] = defaultMethod;
    var paypalProvider = {
        'methods': {}
    };
    for (var method in paymentMethods) {
        paypalProvider.methods[method] = paymentMethods[method].config;
    }
    store.dispatch(registerPaymentProvider({
        'paypal': paypalProvider
    }));
    return paymentMethods;
}
function paypalLoadScripts(scriptURLs) {
    return __awaiter(this, void 0, void 0, function () {
        var load, promises, _i, scriptURLs_1, scriptURL2, _a, scriptURLs_2, scriptURL1;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    load = function (scriptURL) {
                        return new Promise(function (resolve, _) {
                            if (paypalLoadScripts.loaded.has(scriptURL)) {
                                resolve();
                            }
                            else {
                                var script = document.createElement('script');
                                script.addEventListener('load', resolve);
                                script.src = scriptURL;
                                script.dataset.dataPartnerAttributionId = isDevEnvironment(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode())) ? BN_CODE_SANDBOX : BN_CODE_PRODUCTION;
                                document.head.append(script);
                            }
                        });
                    };
                    promises = [];
                    for (_i = 0, scriptURLs_1 = scriptURLs; _i < scriptURLs_1.length; _i++) {
                        scriptURL2 = scriptURLs_1[_i];
                        promises.push(load(scriptURL2));
                    }
                    return [4, Promise.all(promises)];
                case 1:
                    _b.sent();
                    paypalLoadScripts.loaded = new Set();
                    for (_a = 0, scriptURLs_2 = scriptURLs; _a < scriptURLs_2.length; _a++) {
                        scriptURL1 = scriptURLs_2[_a];
                        paypalLoadScripts.loaded.add(scriptURL1);
                    }
                    return [2];
            }
        });
    });
}
function loadPayPalScript() {
    return __awaiter(this, void 0, void 0, function () {
        var response, merchant, error_7;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 4, , 5]);
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + "api/v1/paypal/merchantAndClient?merchantHostname=".concat(MerchantConfiguration.hostName()), {
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        })];
                case 1:
                    response = _a.sent();
                    return [4, response.json()];
                case 2:
                    merchant = _a.sent();
                    paypalMerchantID = merchant.paypalMerchantID;
                    return [4, paypalLoadScripts([
                            "https://www.paypal.com/sdk/js?&client-id=".concat(merchant.clientID, "&merchant-id=").concat(merchant.paypalMerchantID, "&disable-funding=paylater,card,bancontact,blik,eps,giropay,ideal,mybank,p24,sofort,mercadopago,sepa,venmo&currency=").concat(MerchantConfiguration.currency.code()),
                        ])];
                case 3:
                    _a.sent();
                    return [3, 5];
                case 4:
                    error_7 = _a.sent();
                    if (error_7 instanceof Error) {
                        captureSentryException(error_7);
                    }
                    return [2, false];
                case 5: return [2, true];
            }
        });
    });
}
function initPayPalPaymentFlow(orderService) {
    var $paypalButton = paypal.Buttons({
        style: {
            height: 55
        },
        createOrder: function () {
            return __awaiter(this, void 0, void 0, function () {
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0: return [4, createPayPalOrder()];
                        case 1: return [2, _a.sent()];
                    }
                });
            });
        },
        onApprove: function (data, actions) {
            store.dispatch(startModalProcessing());
            placeOrderOnStoreAndListenForCompletion(data, actions, orderService);
        },
        onClick: function () {
            return checkRequiredFields();
        }
    });
    $paypalButton.render('#paypal-btn-container');
    $paypalButton.render('#paypal-btn-container-mobile');
    $paypalButton.render('#paypal-btn-container-existing');
}
function restartAction(actions) {
    self.addEventListener('message', function (event) {
        if (event.data.event === 'paypalRestart') {
            actions.restart();
        }
    });
}
function createPayPalOrder() {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var mockOrderResult, body, response, result;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    mockOrderResult = {
                        'domain': MerchantConfiguration.hostName(),
                        'merchant_name': MerchantConfiguration.name(),
                        'details': {
                            'id': '',
                            'number': '',
                            'currency': MerchantConfiguration.currency.code(),
                            'discount_total': DefaultCart.totalAppliedCoupons().toFixed(2),
                            'shipping_total': DefaultCart.totalShipping().toFixed(2),
                            'total': DefaultCart.total().toFixed(2),
                            'total_tax': ((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.wc_prices_include_tax) ? '0.00' : DefaultCart.totalTax().toFixed(2),
                            'shipping': paypalCustomerAddress(),
                            'line_items': getLineItems(),
                            'shipping_lines': getShippingLines(),
                            'fee_total': DefaultCart.totalAppliedFees().toFixed(2)
                        }
                    };
                    body = {
                        orderResult: mockOrderResult,
                        sessionID: PeachPayOrder.sessionId(),
                        paypalMerchantID: paypalMerchantID
                    };
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/paypal/order', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(body)
                        })];
                case 1:
                    response = _b.sent();
                    return [4, response.json()];
                case 2:
                    result = _b.sent();
                    return [2, result.id];
            }
        });
    });
}
function paypalCustomerAddress() {
    var firstName = PeachPayCustomer.firstName, lastName = PeachPayCustomer.lastName, address1 = PeachPayCustomer.address1, address2 = PeachPayCustomer.address2, city = PeachPayCustomer.city, state = PeachPayCustomer.state, postal = PeachPayCustomer.postal, country = PeachPayCustomer.country, phone = PeachPayCustomer.phone, email = PeachPayCustomer.email;
    return {
        first_name: firstName(),
        last_name: lastName(),
        company: '',
        address_1: address1(),
        address_2: address2(),
        city: city(),
        state: state(),
        postcode: postal(),
        country: country(),
        phone: phone(),
        email: email()
    };
}
function getLineItems() {
    var _a, _b;
    var items = [];
    for (var _i = 0, _c = DefaultCart.contents(); _i < _c.length; _i++) {
        var item = _c[_i];
        var lineItem = {
            'id': item.product_id,
            'name': item.name_with_variation || item.name + (item.variation_title ? " - ".concat(item.variation_title) : ''),
            'quantity': String(item.quantity),
            'subtotal': String(Number.parseFloat(item.total) * Number.parseInt(item.quantity)),
            'subtotal_tax': '0.00'
        };
        if (((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.wc_prices_include_tax) && item.display_price) {
            lineItem.subtotal = String(Number.parseFloat(item.display_price) * Number.parseInt(item.quantity));
        }
        items.push(lineItem);
    }
    if (!((_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.wc_prices_include_tax)) {
        items[0].subtotal_tax = String(DefaultCart.totalTax());
    }
    return items;
}
function getShippingLines() {
    return {
        0: (function () {
            var _a, _b;
            var shippingDetails = DefaultCart.selectedShippingMethodDetails('0');
            if (!shippingDetails) {
                return undefined;
            }
            return {
                method_id: shippingDetails.selected_method,
                total: String(DefaultCart.totalShipping()),
                method_title: (_b = (_a = shippingDetails.methods[shippingDetails.selected_method]) === null || _a === void 0 ? void 0 : _a.title) !== null && _b !== void 0 ? _b : ''
            };
        })()
    };
}
var latestOrderAttempt = 0;
function placeOrderOnStoreAndListenForCompletion(data, actions, orderService) {
    var _this = this;
    latestOrderAttempt++;
    var orderAttempt = latestOrderAttempt;
    onWindowMessage('submitPaypalOrder', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var error_8, error1_1, capture, error2_1, transactionID;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (latestOrderAttempt !== orderAttempt) {
                        return [2];
                    }
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    return [4, updatePayPalOrderWithFinalAmount(data.orderID, message.order)];
                case 2:
                    _a.sent();
                    return [3, 4];
                case 3:
                    error_8 = _a.sent();
                    captureSentryException(new Error('Error while updating PayPal order with final amount: ' + JSON.stringify(error_8)));
                    return [3, 4];
                case 4:
                    _a.trys.push([4, 6, , 7]);
                    return [4, updatePayPalOrderWithFinalAmount(data.orderID, message.order)];
                case 5:
                    _a.sent();
                    return [3, 7];
                case 6:
                    error1_1 = _a.sent();
                    captureSentryException(new Error('Error while updating PayPal order with final amount: ' + JSON.stringify(error1_1)));
                    return [3, 7];
                case 7:
                    capture = null;
                    _a.label = 8;
                case 8:
                    _a.trys.push([8, 10, , 11]);
                    return [4, capturePayPalOrder(data.orderID)];
                case 9:
                    capture = _a.sent();
                    return [3, 11];
                case 10:
                    error2_1 = _a.sent();
                    captureSentryException(new Error('Error while capturing PayPal order: ' + JSON.stringify(error2_1)));
                    return [3, 11];
                case 11:
                    if (!((capture === null || capture === void 0 ? void 0 : capture.status) === 'COMPLETED')) return [3, 13];
                    store.dispatch(updateCustomerPreferredPaymentMethod({
                        provider: 'paypal',
                        method: 'default'
                    }));
                    saveCustomerToBrowser();
                    return [4, orderService.setPaymentStatus(PeachPayOrder.sessionId(), true)];
                case 12:
                    if (!(_a.sent()).ok) {
                        return [2];
                    }
                    transactionID = capture.purchase_units[0].payments.captures[0].id;
                    orderService.deprecated.setOrderStatus(message.order, {
                        status: 'wc-processing',
                        paymentType: 'PayPal',
                        transactionID: transactionID
                    });
                    return [3, 14];
                case 13:
                    if ((capture === null || capture === void 0 ? void 0 : capture.details[0].issue) === 'INSTRUMENT_DECLINED') {
                        store.dispatch(stopModalLoading());
                        orderService.deprecated.setOrderStatus(message.order, {
                            status: 'wc-failed',
                            message: capture.details[0].description
                        });
                        window.parent.postMessage({
                            event: 'paypalAlert',
                            message: capture.details[0].description
                        }, '*');
                        restartAction(actions);
                    }
                    else {
                        store.dispatch(stopModalLoading());
                        orderService.deprecated.setOrderStatus(message.order, {
                            status: 'wc-failed',
                            message: 'Something went wrong.'
                        });
                        window.parent.postMessage({
                            event: 'paypalAlert',
                            message: 'Something went wrong.'
                        }, '*');
                        restartAction(actions);
                    }
                    _a.label = 14;
                case 14: return [2];
            }
        });
    }); });
    orderService.deprecated.placeOrder({
        isPaypal: true
    });
}
function capturePayPalOrder(orderID) {
    return __awaiter(this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/paypal/capture', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            orderID: orderID,
                            sessionID: PeachPayOrder.sessionId(),
                            paypalMerchantID: paypalMerchantID
                        })
                    })];
                case 1:
                    response = _a.sent();
                    return [2, response.json()];
            }
        });
    });
}
function updatePayPalOrderWithFinalAmount(paypalOrderID, orderResult) {
    return __awaiter(this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/paypal/order/update', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            sessionID: PeachPayOrder.sessionId(),
                            paypalMerchantID: paypalMerchantID,
                            paypalOrderID: paypalOrderID,
                            orderResult: orderResult
                        })
                    })];
                case 1:
                    response = _a.sent();
                    return [2, response.json()];
            }
        });
    });
}
function initRelatedProducts() {
    var _a, _b, _c;
    if (!Feature.enabled(FeatureFlag.RELATED_PRODUCTS)) {
        return;
    }
    relatedProductsDropdown();
    (_a = $qs('.pp-next-btn-related')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () { return scrollRight('-related'); });
    (_b = $qs('.pp-prev-btn-related')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () { return scrollLeft('-related'); });
    (_c = $qs('#pp-products-list-related')) === null || _c === void 0 ? void 0 : _c.addEventListener('scroll', function () { return scrollAdjuster('-related'); });
    var relatedProductsTitle = Feature.metadata(FeatureFlag.RELATED_PRODUCTS, 'related_products_title') ? Feature.metadata(FeatureFlag.RELATED_PRODUCTS, 'related_products_title') : 'Related Products';
    var relatedProducts = Feature.metadata(FeatureFlag.RELATED_PRODUCTS, 'related_products');
    store.subscribe(function () {
        if (Environment.plugin.pageType() === 'product' && relatedProducts && relatedProductsTitle) {
            renderRelatedProducts(relatedProducts, relatedProductsTitle);
            updateRelatedProductButton(DefaultCart.contents());
        }
    });
}
function clearRelatedProducts() {
    for (var _i = 0, _a = $qsAll('.pp-related-product-body'); _i < _a.length; _i++) {
        var relatedProduct = _a[_i];
        relatedProduct.remove();
    }
}
function renderRelatedProducts(relatedProducts, title) {
    var _a, _b, _c, _d, _e;
    clearRelatedProducts();
    $qs('.pp-container', function ($element) { var _a; return $element.style.height = ((_a = $qs('#pp-existing-customer-checkout')) === null || _a === void 0 ? void 0 : _a.classList.contains('hide')) ? '' : 'auto'; });
    $qs('#pp-modal-content', function ($element) { return $element.style.marginBottom = '0px'; });
    (_a = $qs('#pp-related-products-section')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    (_b = $qs('#pp-related-products-section-mobile')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    (_c = $qs('#pp-related-products-section-mobile-existing')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
    for (var _i = 0, _f = $qsAll('.related-products-title'); _i < _f.length; _i++) {
        var element = _f[_i];
        element.innerHTML = title;
    }
    var relatedList = $qs('#pp-products-list-related-main');
    var relatedListMobile = $qs('.pp-products-list-related-mobile');
    var relatedListMobileExisting = $qs('.pp-products-list-related-mobile-existing');
    if (relatedProducts.length <= 3) {
        (_d = $qs('.pp-prev-btn-related')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
        (_e = $qs('.pp-next-btn-related')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
    }
    for (var _g = 0, relatedProducts_1 = relatedProducts; _g < relatedProducts_1.length; _g++) {
        var relatedItem = relatedProducts_1[_g];
        if (relatedItem.has_stock) {
            var rpBody = document.createElement('div');
            rpBody.className = 'pp-related-product-body';
            var rpURl = relatedItem.permalink;
            rpBody.innerHTML = "<a href=\"".concat(rpURl, "\" class=\"pp-rp-content\" target=\"_parent\">\n                                    <img class=\"pp-related-product-img\" src=").concat(relatedItem.img_src, ">\n                                    <span class=\"pp-rp-title\">").concat(relatedItem.name, "</span>\n                                    <span class=\"").concat(relatedItem.sale ? 'pp-rp-sale' : 'hide', "\">SALE</span>\n                                    <span class=\"pp-rp-price\">").concat(relatedItem.price, "</span>\n                                </a>");
            rpBody.innerHTML += relatedItem.bundle || relatedItem.variable ? "<a href=\"".concat(rpURl, "\" class=\"pp-variable-button\" target=\"_parent\">Options</a>") : "<button data-rpid=".concat(relatedItem.id, " class=\"pp-rp-add-btn\">+ ADD</button>");
            relatedList === null || relatedList === void 0 ? void 0 : relatedList.prepend(rpBody);
            relatedListMobile === null || relatedListMobile === void 0 ? void 0 : relatedListMobile.prepend(rpBody.cloneNode(true));
            relatedListMobileExisting === null || relatedListMobileExisting === void 0 ? void 0 : relatedListMobileExisting.prepend(rpBody.cloneNode(true));
        }
    }
    for (var _h = 0, _j = $qsAll('.pp-rp-add-btn'); _h < _j.length; _h++) {
        var addBtn = _j[_h];
        addBtn.addEventListener('click', function (event) {
            store.dispatch(startModalLoading());
            addRelatedProducttoCart(event.target);
        });
    }
}
function addRelatedProducttoCart(relatedProduct) {
    window.parent.postMessage({
        event: 'addLinkedProduct',
        productID: relatedProduct.dataset.rpid
    }, '*');
}
function relatedProductsDropdown() {
    var _a;
    (_a = $qs('#pp-rp-dropdown-btn')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
        var _a, _b, _c, _d, _e, _f, _g, _h;
        if ((_a = $qs('.pp-rp-dropdown-down')) === null || _a === void 0 ? void 0 : _a.classList.contains('hide')) {
            (_b = $qs('.pp-rp-dropdown-up')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
            (_c = $qs('.pp-rp-dropdown-down')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
            (_d = $qs('#pp-related-products-container')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
        }
        else if ((_e = $qs('.pp-rp-dropdown-up')) === null || _e === void 0 ? void 0 : _e.classList.contains('hide')) {
            (_f = $qs('.pp-rp-dropdown-up')) === null || _f === void 0 ? void 0 : _f.classList.remove('hide');
            (_g = $qs('.pp-rp-dropdown-down')) === null || _g === void 0 ? void 0 : _g.classList.add('hide');
            (_h = $qs('#pp-related-products-container')) === null || _h === void 0 ? void 0 : _h.classList.remove('hide');
        }
    });
}
function updateRelatedProductButton(cart) {
    for (var _i = 0, _a = $qsAll('.pp-rp-add-btn'); _i < _a.length; _i++) {
        var relatedProduct = _a[_i];
        for (var i = cart.length - 1; i >= 0; i--) {
            var item = cart[i];
            if (relatedProduct.dataset.rpid && item.product_id === Number.parseInt(relatedProduct.dataset.rpid)) {
                relatedProduct.innerHTML = 'Added';
            }
        }
    }
}
(function () {
    var _this = this;
    onWindowMessage('init', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var orderService;
        var _this = this;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    initSentry(message);
                    GLOBAL.phpData = message.phpData;
                    store.dispatch(updateMerchantHostName(message.merchantHostname));
                    installCustomerFormFields(message.phpData.language);
                    loadSession();
                    store.dispatch(updateMerchantHostName(message.merchantHostname));
                    store.dispatch(updateMerchantName(message.phpData.merchant_name));
                    store.dispatch(setFeatureSupport(message.phpData.feature_support));
                    store.dispatch(updateLanguage(message.phpData.language === 'detect-from-page' ? message.pageLanguage : message.phpData.language));
                    store.dispatch(updateEnvironment({
                        pluginIsTestMode: Boolean(message.isTestMode),
                        pluginPageType: determinePageType(message.isCartPage, message.isCheckoutPage, message.isShopPage),
                        customerIsMobile: message.isMobile,
                        pluginButtonColor: message.phpData.button_color,
                        pluginVersion: message.phpData.version,
                        supportMessage: message.phpData.support_message
                    }));
                    initDeliveryDate();
                    initMetrics();
                    initLinkedProducts();
                    initOrderNotes();
                    initCart();
                    initLanguage(message);
                    initSummary(message);
                    initCouponInput(message);
                    initGiftCardInput(message);
                    initShipping(message);
                    initCustomer(message);
                    initCurrency(message);
                    initMerchantAccount(message);
                    initVAT(message);
                    initRelatedProducts();
                    initAddionalFields(message);
                    initCurrencySwitcher();
                    initPaymentMethods();
                    initModal(message);
                    orderService = getOrderService();
                    return [4, initStripePaymentProvider(message, orderService)];
                case 1:
                    _a.sent();
                    initPayPalPaymentProvider(orderService);
                    onWindowMessage('pp-one-click-loaded', function () { return __awaiter(_this, void 0, void 0, function () {
                        var selectedMethod;
                        var _a;
                        return __generator(this, function (_b) {
                            switch (_b.label) {
                                case 0: return [4, loadCustomer()];
                                case 1:
                                    _b.sent();
                                    store.dispatch(startModalLoading());
                                    if (!store.getState().peachPayCustomer.preferred_payment_method) {
                                        store.dispatch(updateEnvironment({
                                            modalPageType: 'info',
                                            customerExists: false
                                        }));
                                    }
                                    selectedMethod = PaymentConfiguration.checkEligibleOrFindAlternate(PeachPayCustomer.preferredPaymentMethod());
                                    if (selectedMethod) {
                                        store.dispatch(setPaymentMethod(selectedMethod));
                                    }
                                    else {
                                        store.dispatch(setOrderError(getLocaleText('There are no eligible or active payment methods available for this order.')));
                                    }
                                    return [4, requestCartCalculation()];
                                case 2:
                                    _b.sent();
                                    store.dispatch(stopModalLoading());
                                    (_a = self.parent) === null || _a === void 0 ? void 0 : _a.postMessage('pp-loaded', '*');
                                    return [2];
                            }
                        });
                    }); });
                    installOneClickCheckout(message.isTestMode);
                    return [2];
            }
        });
    }); });
})();