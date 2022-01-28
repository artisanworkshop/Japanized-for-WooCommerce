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
    DispatchActionType1["ORDER_SESSIONID"] = 'peachpayOrder/sessionId';
    DispatchActionType1["ORDER_ADDRESS_VALIDATED"] = 'peachpayOrder/addressValidated';
    DispatchActionType1["ORDER_SET_EXTRA_FIELDS"] = 'peachpayOrder/extraFields/set';
    DispatchActionType1["ORDER_SET_ERROR_MESSAGE"] = 'peachpayOrder/errorMessage/set';
    DispatchActionType1["PEACHPAY_CUSTOMER"] = 'peachpay/customer';
    DispatchActionType1["PEACHPAY_CUSTOMER_STRIPE_ID"] = 'peachpay/customer/stripeId';
    DispatchActionType1["PEACHPAY_CUSTOMER_PAYMENT_METHOD"] = 'peachpay/customer/payment_method';
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
    DispatchActionType1["ENVIRONMENT_SET_FEATURES"] = 'ENVIRONMENT_SET_FEATURES';
    DispatchActionType1["PEACHPAY_CUSTOMER_SHIPPING"] = 'PEACHPAY_CUSTOMER_SHIPPING';
})(DispatchActionType || (DispatchActionType = {}));
var initialState = {
    environment: {
        language: 'en-US',
        plugin: {
            version: '',
            mode: 'live',
            pageType: 'cart',
            buttonColor: '#FF876C',
            featureSupport: {}
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
                thousandsSeparator: ',',
                decimalSeparator: '.',
                rounding: 'disabled',
                decimals: 2
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
    }
};
function createDispatchUpdate(type) {
    return function (payload) { return ({
        type: type,
        payload: payload
    }); };
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
        case DispatchActionType.ORDER_SESSIONID:
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
            return __assign({}, action.payload);
        case DispatchActionType.PEACHPAY_CUSTOMER_STRIPE_ID:
            return __assign(__assign({}, state), { stripe_customer_id: action.payload });
        case DispatchActionType.PEACHPAY_CUSTOMER_PAYMENT_METHOD:
            return __assign(__assign({}, state), { payment_option: action.payload });
        case DispatchActionType.PEACHPAY_CUSTOMER_SHIPPING:
            return __assign(__assign(__assign({}, state), action.payload), { postal: action.payload.postcode });
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
function rootReducer(state, action) {
    if (state === void 0) { state = initialState; }
    return __assign(__assign({}, state), { peachPayOrder: peachPayOrderReducer(state.peachPayOrder, action), environment: environmentReducer(state.environment, action), merchantCustomer: merchantCustomerReducer(state.merchantCustomer, action), peachPayCustomer: peachPayCustomerReducer(state.peachPayCustomer, action), merchantConfiguration: merchantConfigurationReducer(state.merchantConfiguration, action), calculatedCarts: cartReducer(state.calculatedCarts, action) });
}
var store = createStore(rootReducer);
function updateEnvironment(options) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j;
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
                featureSupport: store.getState().environment.plugin.featureSupport
            },
            modalUI: {
                open: (_g = options.modalIsOpen) !== null && _g !== void 0 ? _g : Environment.modalUI.open(),
                page: (_h = options.modalPageType) !== null && _h !== void 0 ? _h : Environment.modalUI.page(),
                loadingMode: (_j = options.modalLoading) !== null && _j !== void 0 ? _j : Environment.modalUI.loadingMode()
            }
        }
    };
}
function setFeatureSupport(features, phpData) {
    if (features === void 0) { features = {}; }
    if (!features[FeatureFlag.COUPON_INPUT]) {
        features[FeatureFlag.COUPON_INPUT] = {
            enabled: Boolean(phpData.enable_coupons),
            version: 0
        };
    }
    if (!features[FeatureFlag.ORDER_NOTES]) {
        features[FeatureFlag.ORDER_NOTES] = {
            enabled: Boolean(phpData.enable_order_notes),
            version: 0
        };
    }
    if (!features[FeatureFlag.GIFTCARD_INPUT]) {
        features[FeatureFlag.GIFTCARD_INPUT] = {
            enabled: Boolean(phpData.plugin_pw_woocommerce_gift_cards_active),
            version: 0
        };
    }
    if (!features[FeatureFlag.STRIPE]) {
        features[FeatureFlag.STRIPE] = {
            enabled: true,
            version: 0
        };
    }
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
    customer: {
        existing: function () { return store.getState().environment.customer.existing; },
        mobile: function () { return store.getState().environment.customer.mobile; }
    },
    plugin: {
        version: function () { return store.getState().environment.plugin.version; },
        mode: function () { return store.getState().environment.plugin.mode; },
        buttonColor: function () { return store.getState().environment.plugin.buttonColor; },
        pageType: function () { return store.getState().environment.plugin.pageType; }
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
    'empty-cart': {
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
    'linked-products-title': {
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
    'coupon-option': {
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
    'gift-option': {
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
    'send-to': {
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
    'my-order': {
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
    'ready-to-check-out': {
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
        'zh-CN': '准备退房了吗？',
        'zh-TW': '準備退房了嗎？'
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
        'zh-CN': '船运',
        'zh-TW': '船運'
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
        'zh-CN': '计费',
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
    'secure-notice': {
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
        'zh-CN': '担保人',
        'zh-TW': '擔保人'
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
    'order-summary': {
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
        'zh-CN': '全部的',
        'zh-TW': '全部的'
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
    'wc-coupon-code': {
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
    'wc-invalid-coupon': {
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
        'zh-CN': '申请',
        'zh-TW': '申請'
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
    'gift-card-number': {
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
    'invalid-gift-card': {
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
    'card-label': {
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
        'zh-CN': '卡片',
        'zh-TW': '卡片'
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
    'no-ship': {
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
        'zh-CN': '加工',
        'zh-TW': '加工'
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
    'province-select': {
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
    'state-select': {
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
    'order-notes': {
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
    'something-went-wrong': {
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
    'something-went-wrong-order': {
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
    'delivery-date': {
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
    'first-renewal-date': {
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
    'subscription-summary': {
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
    'account-password-explanation': {
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
    'account-password': {
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
    'invalid-merchant-password': {
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
    'test-mode-banner': {
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
    'verify-location': {
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
    metaData: function (flag, key) { var _a, _b, _c; return (_c = (_b = (_a = store.getState().environment.plugin.featureSupport[flag]) === null || _a === void 0 ? void 0 : _a.meta_data) === null || _b === void 0 ? void 0 : _b[key]) !== null && _c !== void 0 ? _c : null; }
};
var FeatureFlag;
(function (FeatureFlag1) {
    FeatureFlag1["CART_CALCULATION"] = 'cart_calculation';
    FeatureFlag1["COUPON_INPUT"] = 'coupon_input';
    FeatureFlag1["GIFTCARD_INPUT"] = 'giftcard_input';
    FeatureFlag1["ORDER_NOTES"] = 'order_notes_input';
    FeatureFlag1["ADDITIONAL_FIELDS"] = 'additional_fields';
    FeatureFlag1["STRIPE"] = 'stripe_payment_method';
    FeatureFlag1["STRIPE_PAYMENT_REQUEST"] = 'stripe_payment_request';
    FeatureFlag1["QUANTITY_CHANGER"] = 'quantity_changer';
})(FeatureFlag || (FeatureFlag = {}));
var updateCustomerStripeId = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_STRIPE_ID);
var updateCustomer = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER);
var updateCustomerShippingShortAddress = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_SHIPPING);
var updatePreferredPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_PAYMENT_METHOD);
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
    card: function () { return store.getState().peachPayCustomer.card; },
    preferredPaymentMethod: function () { var _a; return (_a = store.getState().peachPayCustomer.payment_option) !== null && _a !== void 0 ? _a : 'stripe'; },
    stripeId: function () { var _a; return (_a = store.getState().peachPayCustomer.stripe_customer_id) !== null && _a !== void 0 ? _a : ''; },
    stripeDetails: function () { return ({
        name: store.getState().peachPayCustomer.name_first + ' ' + store.getState().peachPayCustomer.name_last,
        email: store.getState().peachPayCustomer.email,
        phone: store.getState().peachPayCustomer.phone
    }); },
    shortAddress: function () { return ({
        country: PeachPayCustomer.country(),
        state: PeachPayCustomer.state(),
        city: PeachPayCustomer.city(),
        postcode: PeachPayCustomer.postal()
    }); },
    shippingAddress: function () { return ({
        shipping_first_name: PeachPayCustomer.firstName(),
        shipping_last_name: PeachPayCustomer.lastName(),
        shipping_company: '',
        shipping_country: PeachPayCustomer.country(),
        shipping_address_1: PeachPayCustomer.address1(),
        shipping_address_2: PeachPayCustomer.address2(),
        shipping_city: PeachPayCustomer.city(),
        shipping_state: PeachPayCustomer.state(),
        shipping_postcode: PeachPayCustomer.postal()
    }); },
    billingAddress: function () { return ({
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
    }); }
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
var updateSessionId = createDispatchUpdate(DispatchActionType.ORDER_SESSIONID);
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
    var _c = MerchantConfiguration.currency.configuration(), code = _c.code, thousandsSeparator = _c.thousandsSeparator, decimalSeparator = _c.decimalSeparator, rounding = _c.rounding, decimals = _c.decimals;
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
            return getLocaleText('state-select');
        case 'GB':
            return getLocaleText('county');
        default:
            return getLocaleText('province-select');
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
function peachpayAlert(message, action) {
    var _a;
    if (action === void 0) { action = ''; }
    if ((_a = GLOBAL === null || GLOBAL === void 0 ? void 0 : GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.alertSupport) {
        window.parent.postMessage({
            event: 'peachpayAlert',
            action: action,
            message: message
        }, '*');
    }
    else {
        alert(message);
        if (action) {
            window.parent.postMessage(action, '*');
        }
    }
}
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
        $redText.textContent = getLocaleText('invalid-merchant-password');
        $redTextExisting.textContent = getLocaleText('invalid-merchant-password');
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
    var labelHTML = getLocaleText('account-password-explanation') + ' ' + merchantHostname;
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
    if (Feature.enabled(FeatureFlag.ORDER_NOTES)) {
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
    $descriptor.innerHTML = getLocaleText('verify-location');
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
function determinePageType(isCartPage, isCheckoutPage) {
    if (isCartPage) {
        return 'cart';
    }
    if (isCheckoutPage) {
        return 'checkout';
    }
    return 'product';
}
function syncFields(event) {
    var $form = event.target.closest('form');
    var fieldRecord = {};
    for (var _i = 0, _a = Array.from($form.elements); _i < _a.length; _i++) {
        var $input = _a[_i];
        fieldRecord[$input.name] = $input.value;
    }
    store.dispatch(setExtraFields(fieldRecord));
}
function renderAdditionalFields(fieldData, fieldOrder) {
    var _a, _b, _c, _d;
    if (fieldData.length === 0 || fieldOrder.length === 0) {
        return;
    }
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
    var _loop_1 = function (key, value) {
        $qsAll("[name=\"".concat(key, "\"].extra-field"), function ($element) { return $element.value = value; });
    };
    for (var _i = 0, _a = Object.entries(extraFieldData); _i < _a.length; _i++) {
        var _b = _a[_i], key = _b[0], value = _b[1];
        _loop_1(key, value);
    }
}
function generateFields(fieldData) {
    var field = function (location) { return '<div class="new-field">' + generateFieldElement(location, fieldData) + '</div>'; };
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
    var elementString = '';
    var optional = '<span class="optional"> (optional) </span>';
    var required = '<abbr class="required" title="required" style="color:red;">*</abbr>';
    var labelBuilder = function (location) { return "\n\t\t<label for=\"".concat(fieldData.field_name).concat(location, "\" class=\"form-label-").concat(fieldData.type_list, "\" >") + "".concat(fieldData.field_label) + (fieldData.field_required ? required : optional) + '</label>'; };
    var inputBuilder = function (location) { return "<input type=".concat(fieldData.type_list, " \n\t\t\tname=").concat(fieldData.field_name, " \n\t\t\tid=\"").concat(fieldData.field_name).concat(location, "\"\n\t\t\tplaceholder=\" \"\n\t\t\tvalue=\"").concat(fieldData.field_default, "\" \n\t\t\tclass=\"input-box-").concat(fieldData.type_list, " extra-field\"") + (fieldData.field_required ? 'required' : '') + '/>'; };
    if (fieldData.type_list === 'text') {
        elementString = inputBuilder(location1) + (fieldData.field_label ? labelBuilder(location1) : '');
        return elementString;
    }
    return elementString;
}
function collectAdditionalFieldData(fieldData, fieldOrder) {
    var _a, _b;
    var fieldDataArray = [];
    for (var _i = 0, fieldOrder_2 = fieldOrder; _i < fieldOrder_2.length; _i++) {
        var orderNumber = fieldOrder_2[_i];
        if (fieldData[orderNumber].field_enable && ((_a = $qs("#".concat(fieldData[orderNumber].field_name, "-existing"))) === null || _a === void 0 ? void 0 : _a.value)) {
            var temporaryData = {
                name: ''
            };
            temporaryData.label = fieldData[orderNumber].field_label;
            temporaryData.name = fieldData[orderNumber].field_name;
            temporaryData.value = (_b = $qs("#".concat(fieldData[orderNumber].field_name, "-existing"))) === null || _b === void 0 ? void 0 : _b.value;
            fieldDataArray.push(temporaryData);
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
            if (extra) {
                try {
                    Object.entries(extra).map(function (_a) {
                        var key = _a[0], value = _a[1];
                        return scope.setExtra(key, value);
                    });
                }
                catch (_a) { }
            }
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
function getOrderService() {
    return {
        placeOrder: placeOrder,
        setOrderStatus: setOrderStatus,
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
                    if (!GLOBAL.phpData) {
                        return [2, ''];
                    }
                    request = {
                        session: {
                            id: PeachPayOrder.sessionId()
                        },
                        order: {
                            id: order.orderID,
                            status: status,
                            message: (_a = options.message) !== null && _a !== void 0 ? _a : '',
                            paymentMethod: PeachPayCustomer.preferredPaymentMethod(),
                            stripeCustomerId: PeachPayCustomer.preferredPaymentMethod() === 'stripe' ? options.stripeCustomerId : undefined,
                            paypalTransactionId: PeachPayCustomer.preferredPaymentMethod() === 'paypal' ? options.paypalTransactionId : undefined,
                            stripeTransactionId: PeachPayCustomer.preferredPaymentMethod() === 'stripe' ? options.stripeTransactionId : undefined
                        }
                    };
                    return [4, fetchHostWindowData('pp-set-order-status', request)];
                case 1:
                    if (_b.sent()) {
                        return [2, wcOrderReceivedURLWithParameters(GLOBAL.phpData.wc_order_received_url, order, MerchantConfiguration.hostName())];
                    }
                    return [2, ''];
            }
        });
    });
}
function placeOrder() {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var requestMessage;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    requestMessage = {
                        session: {
                            id: PeachPayOrder.sessionId()
                        },
                        order: {
                            paymentMethod: PeachPayCustomer.preferredPaymentMethod(),
                            billingAddress: PeachPayCustomer.billingAddress(),
                            shippingAddress: PeachPayCustomer.shippingAddress(),
                            shippingMethods: PeachPayOrder.collectSelectedShipping(),
                            deliveryDate: collectDeliveryDate(),
                            merchantCustomerAccountPassword: '',
                            vatNum: '',
                            vatSelfVerify: '',
                            customerOrderNotes: collectOrderNotes()
                        }
                    };
                    if (shouldShowMerchantCustomerAccountPasswordField()) {
                        requestMessage.order.merchantCustomerAccountPassword = getMerchantCustomerAccountPasswordValue();
                    }
                    if ((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.vat_required) {
                        requestMessage.order.vatNum = getVatNumber();
                    }
                    if ((_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.vat_self_verify) {
                        requestMessage.order.vatSelfVerify = getVerify();
                    }
                    return [4, fetchHostWindowData('pp-place-order', requestMessage)];
                case 1: return [2, _c.sent()];
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
        'billingAddress': PeachPayCustomer.billingAddress(),
        'shippingAddress': PeachPayCustomer.shippingAddress(),
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
    window.parent.postMessage(message, '*');
}
function wcOrderReceivedURLWithParameters(wcOrderReceivedURL, order, domain) {
    if (!GLOBAL.phpData) {
        return '';
    }
    var s = domain === 'localhost' ? '' : 's';
    var port = domain === 'localhost' ? ':8000' : '';
    var url = wcOrderReceivedURL ? wcOrderReceivedURL : "http".concat(s, "://").concat(domain).concat(port, "/checkout/order-received");
    var orderID = order.id || order.details.id;
    var key = order.order_key || order.details.order_key;
    if (GLOBAL.phpData.plugin_woo_thank_you_page_nextmove_lite_active || domain === 'uberbracelets.com') {
        return "".concat(url.replace('/checkout', ''), "/thank-you/?order_id=").concat(orderID, "&key=").concat(key);
    }
    if (MerchantConfiguration.hostName() === 'rapidfiresupplies.co.uk') {
        return "http".concat(s, "://").concat(domain).concat(port, "/thank-you-for-purchasing-from-us/");
    }
    return "".concat(url, "/").concat(orderID, "/?key=").concat(key);
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
    onWindowMessage('validateAddressSuccess', function (_) { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, requestCartCalculation()];
                case 1:
                    _a.sent();
                    store.dispatch(updateCustomerAddressValidation(true));
                    store.dispatch(updateEnvironment({
                        modalPageType: 'payment'
                    }));
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    }); });
    (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.addEventListener('submit', function (event) {
        event.preventDefault();
        store.dispatch(startModalLoading());
        if (!validateCartItemsWithCustomer(DefaultCart.contents(), false)) {
            store.dispatch(stopModalLoading());
            return;
        }
        window.parent.postMessage({
            event: 'validateAddress',
            billingAddress: PeachPayCustomer.billingAddress()
        }, '*');
    });
    (_b = $qs('#pp-shipping-options-existing')) === null || _b === void 0 ? void 0 : _b.addEventListener('change', handleShippingSelectionEvent);
    (_c = $qs('#pp-shipping-options')) === null || _c === void 0 ? void 0 : _c.addEventListener('change', handleShippingSelectionEvent);
}
function validateAddress() {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, fetchHostWindowData('pp-validate-billing-address', PeachPayCustomer.billingAddress())];
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
    var methodOptionBuilder = function (methodKey, method, selected) { return "\n<label for=\"shipping_method_".concat(packageMethodKey, "_").concat(methodKey.replace(/:/g, ''), "\" style=\"margin: 0 0 3px 0; display: flex; flex-direction: row; cursor: pointer;\">\n\t<input style=\"vertical-align: top;\" id=\"shipping_method_").concat(packageMethodKey, "_").concat(methodKey.replace(/:/g, ''), "\" name=\"shipping_method[").concat(packageMethodKey, "]\" value=\"").concat(methodKey, "\" type=\"radio\" ").concat(selected ? 'checked' : '', " required>\n\t<span style=\"display: inline-block; flex-grow: 1;\">").concat(method.title, "</span>\n\t<span style=\"display: inline-block; min-width: 30%; text-align: right;\" class=\"shipping-price\">").concat(formatCurrencyString(method.total), "<span class=\"muted\">").concat(buildSubscriptionPriceMetaData(cartMeta), "</span></span>\n</label>"); };
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
    if (response.data) {
        store.dispatch(setOrderError(''));
        store.dispatch(updateCartCalculation(response.data.cart_calculation_record));
        store.dispatch(updateCustomerShippingShortAddress(response.data.shipping_location));
        if (DefaultCart.contents().length === 0) {
            store.dispatch(setOrderError("<span>".concat(getLocaleText('empty-cart'), "</span>")));
        }
        else if (!shippingIsValid()) {
            store.dispatch(setOrderError("<span>".concat(getLocaleText('no-ship'), "</span>")));
        }
    }
}
function productBundlesProductPage() {
    var _a;
    return ((_a = GLOBAL === null || GLOBAL === void 0 ? void 0 : GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.plugin_woocommerce_product_bundles_active) === '1' && !(Environment.plugin.pageType() === 'cart');
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
    if (!GLOBAL.phpData) {
        return;
    }
    window.parent.postMessage({
        event: 'setOrderStatus',
        orderID: order.orderID,
        status: status,
        message: message,
        paymentType: paymentType,
        transactionID: transactionID,
        customerStripeId: PeachPayCustomer.stripeId(),
        redirectURL: wcOrderReceivedURLWithParameters(GLOBAL.phpData.wc_order_received_url, order, MerchantConfiguration.hostName())
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
            var customerData = JSON.stringify(customer);
            if (customerData !== previousCustomerData) {
                previousCustomerData = customerData;
                renderCustomerFields(customer);
            }
        }
        if (Environment.modalUI.page() === 'payment') {
            renderCustomerHeader(customer, Environment.customer.existing());
            if (Environment.customer.existing()) {
                renderExistingCustomerCheckout(customer);
            }
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
function saveCustomerToBrowser(customerID, brand, last4, paymentOption) {
    return __awaiter(this, void 0, void 0, function () {
        var firstName, lastName, email, phone, address1, address2, postal, city, state, country, customer;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    firstName = PeachPayCustomer.firstName, lastName = PeachPayCustomer.lastName, email = PeachPayCustomer.email, phone = PeachPayCustomer.phone, address1 = PeachPayCustomer.address1, address2 = PeachPayCustomer.address2, postal = PeachPayCustomer.postal, city = PeachPayCustomer.city, state = PeachPayCustomer.state, country = PeachPayCustomer.country;
                    customer = {
                        'name_first': firstName(),
                        'name_last': lastName(),
                        'email': email(),
                        'phone': phone(),
                        'address1': address1(),
                        'address2': address2(),
                        'postal': postal(),
                        'city': city(),
                        'state': state(),
                        'country': country(),
                        'stripe_customer_id': customerID,
                        'card': {
                            brand: brand,
                            last4: last4
                        },
                        'payment_option': paymentOption
                    };
                    return [4, setCustomer(customer)];
                case 1:
                    _a.sent();
                    return [2, customer];
            }
        });
    });
}
function displayCCLogo(customer) {
    var _a, _b;
    var ccBrand = '';
    switch ((_a = customer.card) === null || _a === void 0 ? void 0 : _a.brand) {
        case 'Visa':
            ccBrand = 'visa';
            break;
        case 'MasterCard':
            ccBrand = 'mastercard';
            break;
        case 'American Express':
            ccBrand = 'amex';
            break;
        case 'Discover':
            ccBrand = 'discover';
            break;
        case 'Diners Club':
            ccBrand = 'diners';
            break;
        case 'JCB':
            ccBrand = 'jcb';
            break;
        case 'UnionPay':
            ccBrand = 'unionpay';
            break;
        default:
            ccBrand = '';
    }
    (_b = $qs('#cc-' + ccBrand)) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
}
function renderExistingCustomerCheckout(customer) {
    var _a, _b, _c, _d, _e, _f;
    if (customer.payment_option === 'paypal') {
        (_a = $qs('#paypal-button-container-existing')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        if (((_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.paypal) === '1') {
            (_c = $qs('#existing-checkout-card')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
            adjustOrderSummaryHeight(true);
        }
        else {
            (_d = $qs('#existing-checkout-card-number')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
            (_e = $qs('#existing-checkout-no-card')) === null || _e === void 0 ? void 0 : _e.classList.remove('hide');
        }
    }
    else {
        (_f = $qs('#paypal-button-container-existing')) === null || _f === void 0 ? void 0 : _f.classList.add('hide');
        adjustOrderSummaryHeight(false);
    }
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
                if (defaultOption_1 === getLocaleText('province-select')) {
                    $qs('label[for="dynamic-states"]', function ($element) { return $element.textContent = getLocaleText('province'); });
                }
                else if (defaultOption_1 === getLocaleText('state-select')) {
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
                if (defaultOption_1 === getLocaleText('province-select')) {
                    $qs('label[for="province"]', function ($element) { return $element.textContent = getLocaleText('province'); });
                }
                else if (defaultOption_1 === getLocaleText('state-select')) {
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
        displayCCLogo(customer);
        $qs('#existing-last4', function ($element) { var _a, _b; return $element.textContent = (_b = (_a = customer === null || customer === void 0 ? void 0 : customer.card) === null || _a === void 0 ? void 0 : _a.last4) !== null && _b !== void 0 ? _b : ''; });
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
function adjustOrderSummaryHeight(isPaypalUsed) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
    if (((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.enable_coupons) && GLOBAL.phpData.plugin_pw_woocommerce_gift_cards_active && GLOBAL.phpData.enable_order_notes && !productBundlesProductPage()) {
        isPaypalUsed ? $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '28rem'; }) : $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '32rem'; });
    }
    else if (((_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.enable_coupons) && GLOBAL.phpData.plugin_pw_woocommerce_gift_cards_active && !GLOBAL.phpData.enable_order_notes && !productBundlesProductPage()) {
        isPaypalUsed ? $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '19rem'; }) : $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '23rem'; });
    }
    else if (((_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.enable_coupons) && !GLOBAL.phpData.plugin_pw_woocommerce_gift_cards_active && GLOBAL.phpData.enable_order_notes || !((_d = GLOBAL.phpData) === null || _d === void 0 ? void 0 : _d.enable_coupons) && ((_e = GLOBAL.phpData) === null || _e === void 0 ? void 0 : _e.plugin_pw_woocommerce_gift_cards_active) && GLOBAL.phpData.enable_order_notes && !productBundlesProductPage()) {
        isPaypalUsed ? $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '26.5rem'; }) : $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '29.5rem'; });
    }
    else if (!((_f = GLOBAL.phpData) === null || _f === void 0 ? void 0 : _f.enable_coupons) && !((_g = GLOBAL.phpData) === null || _g === void 0 ? void 0 : _g.plugin_pw_woocommerce_gift_cards_active) && ((_h = GLOBAL.phpData) === null || _h === void 0 ? void 0 : _h.enable_order_notes)) {
        isPaypalUsed ? $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '23rem'; }) : $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '26.5rem'; });
    }
    else if (((_j = GLOBAL.phpData) === null || _j === void 0 ? void 0 : _j.enable_coupons) || ((_k = GLOBAL.phpData) === null || _k === void 0 ? void 0 : _k.plugin_pw_woocommerce_gift_cards_active) && !productBundlesProductPage()) {
        isPaypalUsed ? $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '17rem'; }) : $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '21.5rem'; });
    }
    else if (isPaypalUsed) {
        $qs('#pp-summary-body-existing', function ($element) { return $element.style.maxHeight = '15rem'; });
    }
}
function initCurrency(message) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q;
    initCurrencyEvents();
    store.dispatch(updateMerchantCurrencyConfig({
        code: (_b = (_a = message.phpData.currency_info) === null || _a === void 0 ? void 0 : _a.code) !== null && _b !== void 0 ? _b : 'USD',
        symbol: (_e = (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.currency_info) === null || _d === void 0 ? void 0 : _d.symbol) !== null && _e !== void 0 ? _e : '$',
        thousandsSeparator: (_g = (_f = message.phpData.currency_info) === null || _f === void 0 ? void 0 : _f.thousands_separator) !== null && _g !== void 0 ? _g : ',',
        decimalSeparator: (_j = (_h = message.phpData.currency_info) === null || _h === void 0 ? void 0 : _h.decimal_separator) !== null && _j !== void 0 ? _j : '.',
        decimals: (_l = (_k = message.phpData.currency_info) === null || _k === void 0 ? void 0 : _k.number_of_decimals) !== null && _l !== void 0 ? _l : 2,
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
function initGiftCardInput(_message) {
    var _a;
    if (!Feature.enabled(FeatureFlag.GIFTCARD_INPUT) || productBundlesProductPage() || MerchantConfiguration.hostName() === 'skregear.com') {
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
                    if (!GLOBAL.phpData) {
                        return [2];
                    }
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
    if (!Feature.enabled(FeatureFlag.COUPON_INPUT) || Feature.version(FeatureFlag.COUPON_INPUT) < 2 && Environment.plugin.pageType() === 'product') {
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
    store.subscribe(function () {
        var cartData = JSON.stringify(DefaultCart.contents());
        if (cartData !== previousCartData) {
            previousCartData = cartData;
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
        var $message = "<tr class=\"order-summary-item\"><td style=\"text-align: center; \">".concat(getLocaleText('empty-cart'), "</td></tr>");
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
            return "\n\t\t<td class=\"item-remover-td non-bundled-item ".concat(tdClass, "\">\n\t\t\t<button class=\"item-remover\" data-qid=\"").concat(item.item_key, "\">&times;</button>\n\t\t</td>");
        };
        var $qtyChanger = function (tdClass) {
            if (tdClass === void 0) { tdClass = ''; }
            return "\n\t\t<td class=\"qty-td ".concat(tdClass, "\">\n\t\t\t<div class=\"quantity-changer\">\n\t\t\t\t<button type=\"button\" class=\"pr-0 decrease-qty qty-btn ").concat(cartItemQuantity(item) <= 1 ? 'scroll-end' : '', "\" data-qid=\"").concat(item.item_key, "\">&#8722;</button>\n\t\t\t\t<form onSubmit=\"return false;\" class=\"mb-0\">\n\t\t\t\t\t<input type=\"number\" min=\"0\" max=\"").concat(item.stock_qty ? item.stock_qty : '', "\" class=\"qty-fs\" value=\"").concat(cartItemQuantity(item), "\" data-qid=\"").concat(item.item_key, "\" required/>\n\t\t\t\t</form>\n\t\t\t\t<button type=\"button\" class=\"pl-0 increase-qty qty-btn ").concat(item.stock_qty && cartItemQuantity(item) >= item.stock_qty ? 'scroll-end' : '', "\" data-qid=\"").concat(item.item_key, "\">+</button>\n\t\t\t</div>\n\t\t</td>");
        };
        var showQuantityChanger = Feature.enabled(FeatureFlag.QUANTITY_CHANGER) && Environment.plugin.pageType() === 'cart' || Feature.enabled(FeatureFlag.QUANTITY_CHANGER) && Feature.version(FeatureFlag.QUANTITY_CHANGER) >= 2;
        if (!item.is_part_of_bundle) {
            if (i < cart.length - 1 && cart[i + 1].is_part_of_bundle) {
                $row.innerHTML += $itemRemover('remove-border');
                if (((_g = item.image) === null || _g === void 0 ? void 0 : _g[0]) && ((_h = item.image) === null || _h === void 0 ? void 0 : _h[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"product-img-td-b0\" id=\"product-img\"><img class=\"product-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t".concat(showQuantityChanger ? $qtyChanger('bundle-name remove-border') : '', "\n\t\t\t\t\t<td class=\"bundle-name\">").concat(label, "</td>\n\t\t\t\t\t<td class=\"bundle-name bold\">").concat(amount, "</td>\n\t\t\t\t");
            }
            else {
                $row.innerHTML += $itemRemover();
                if (((_j = item.image) === null || _j === void 0 ? void 0 : _j[0]) && ((_k = item.image) === null || _k === void 0 ? void 0 : _k[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"product-img-td\" id=\"product-img\"><img class=\"product-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t".concat(showQuantityChanger ? $qtyChanger('non-bundled-item') : '', "\n\t\t\t\t\t<td class=\"non-bundled-item\">").concat(label, "</td>\n\t\t\t\t\t<td class=\"non-bundled-item bold\">").concat(amount, "</td>\n\t\t\t\t");
            }
        }
        else if (item.is_part_of_bundle) {
            if (i < cart.length - 1 && !cart[i + 1].is_part_of_bundle || i === cart.length - 1) {
                if (((_l = item.image) === null || _l === void 0 ? void 0 : _l[0]) && ((_m = item.image) === null || _m === void 0 ? void 0 : _m[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"muted pl-3/2 bb-1 product-img-td\" id=\"product-img\"><img class=\"bundle-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t<td class=\"muted pl-3/2 bb-1\">".concat(label, "</td>\n\t\t\t\t\t<td class=\"muted pl-3/2 bb-1\">").concat(amount, "</td>\n\t\t\t\t");
            }
            else {
                if (((_o = item.image) === null || _o === void 0 ? void 0 : _o[0]) && ((_p = item.image) === null || _p === void 0 ? void 0 : _p[0]) !== '(unknown)') {
                    $row.innerHTML += "<td class=\"muted pl-3/2 bundle-padding product-img-td-b0\" id=\"product-img\"><img class=\"bundle-img-size\" src=\"".concat(item.image[0], "\"/></td>");
                }
                $row.innerHTML += "\n\t\t\t\t\t<td class=\"muted pl-3/2 bundle-padding\">".concat(label, "</td>\n\t\t\t\t\t<td class=\"muted pl-3/2 bundle-padding\">").concat(amount, "</td>\n\t\t\t\t");
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
                $one.innerHTML += "\n\t\t\t\t\t".concat(showQuantityChanger ? $qtyChanger('non-bundled-item remove-border') : '', "\n\t\t\t\t\t<td class=\"non-bundled-item remove-border\">").concat(label, "</td>\n\t\t\t\t\t<td class=\"non-bundled-item remove-border bold\">").concat(amount, "</td>\n\t\t\t\t");
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
function greaterThan(a, b) {
    var _a = String(a).split('.').map(function (n) { return Number(n); }), majorA = _a[0], minorA = _a[1], patchA = _a[2];
    var _b = String(b).split('.').map(function (n) { return Number(n); }), majorB = _b[0], minorB = _b[1], patchB = _b[2];
    var result = majorA - majorB || minorA - minorB || patchA - patchB;
    return result > 0;
}
function initStripeButton() {
    var _a;
    showStripePaymentOption();
    (_a = $qs('#stripe-option')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', selectStripePaymentMethod);
    store.subscribe(function () {
        renderStripeButtonDisplay(PeachPayCustomer.preferredPaymentMethod(), Environment.modalUI.page(), Environment.modalUI.loadingMode());
        renderStripePaymentMethod(PeachPayCustomer.preferredPaymentMethod(), Environment.modalUI.page());
        renderStripeButtonLoading(PeachPayCustomer.preferredPaymentMethod(), Environment.modalUI.loadingMode());
    });
}
function renderStripeButtonDisplay(method, page, loadingMode) {
    if (method === 'stripe' && page === 'payment') {
        $qsAll('.stripe-btn-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn-container', function ($element) { return $element.classList.add('hide'); });
    }
    if (method === 'stripe' && page === 'payment' && loadingMode !== 'loading') {
        $qsAll('.stripe-btn', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn', function ($element) { return $element.classList.add('hide'); });
    }
}
function renderStripeButtonLoading(method, mode) {
    if (method !== 'stripe') {
        return;
    }
    if (mode === 'finished') {
        $qsAll('.stripe-btn', function ($element) { return $element.disabled = false; });
    }
    else {
        $qsAll('.stripe-btn', function ($element) { return $element.disabled = true; });
    }
    if (mode === 'loading') {
        $qsAll('.stripe-btn-shipping-spinner', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn-shipping-spinner', function ($element) { return $element.classList.add('hide'); });
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
function showStripePaymentOption() {
    var _a;
    (_a = $qs('#stripe-option')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    for (var _i = 0, _b = $qsAll('.stripe-btn-container'); _i < _b.length; _i++) {
        var $container = _b[_i];
        $container.classList.remove('hide');
    }
}
function selectStripePaymentMethod() {
    store.dispatch(updatePreferredPaymentMethod('stripe'));
}
function renderStripePaymentMethod(method, page) {
    var _a, _b, _c, _d;
    if (method === 'stripe' && page === 'payment') {
        $qs('#stripe-pm', function ($element) { return $element.checked = true; });
        (_a = $qs('#stripe-pm')) === null || _a === void 0 ? void 0 : _a.setAttribute('checked', 'true');
        $qs('#stripe-option', function ($element) { return $element.style.backgroundColor = 'white'; });
        (_b = $qs('#card-element')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    }
    else {
        $qs('#stripe-pm', function ($element) { return $element.checked = false; });
        (_c = $qs('#stripe-pm')) === null || _c === void 0 ? void 0 : _c.removeAttribute('checked');
        $qs('#stripe-option', function ($element) { return $element.style.backgroundColor = '#f4f4f4'; });
        (_d = $qs('#card-element')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
    }
}
function initStripePaymentRequest(message, stripeService, orderService) {
    var _this = this;
    var _a;
    if (!Feature.enabled(FeatureFlag.STRIPE_PAYMENT_REQUEST) || Environment.plugin.pageType() === 'product') {
        return;
    }
    var initMessage = {
        event: 'pp-init-stripe-payment-request',
        stripe: {
            locale: message.browserLocale,
            live: !isDevEnvironment(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()))
        },
        currencyCode: MerchantConfiguration.currency.code(),
        cartCalculationRecord: store.getState().calculatedCarts
    };
    (_a = window.top) === null || _a === void 0 ? void 0 : _a.postMessage(initMessage, '*');
    onWindowDataFetch('pp-stripe-payment-request-address-change', handleStripePaymentRequestAddressChange);
    onWindowDataFetch('pp-stripe-payment-request-shipping-change', handleStripePaymentRequestShippingChange);
    onWindowDataFetch('pp-stripe-payment-request-process-payment', function (request) { return __awaiter(_this, void 0, void 0, function () { return __generator(this, function (_a) {
        switch (_a.label) {
            case 0: return [4, handleStripePaymentRequestProcessPayment(request, stripeService, orderService)];
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
function handleStripePaymentRequestProcessPayment(request, stripeService, orderService) {
    var _a, _b, _c;
    return __awaiter(this, void 0, void 0, function () {
        var stripeCustomerId, order, paymentResult, orderStatusResult, error_4;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0: return [4, stripeService.createStripeCustomer(request.token.id, {
                        name: request.payerName,
                        email: request.payerEmail,
                        phone: request.payerPhone
                    })];
                case 1:
                    stripeCustomerId = _d.sent();
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
                        postal: request.shippingAddress.postalCode,
                        card: {
                            brand: request.token.card.brand,
                            last4: request.token.card.last4
                        },
                        stripe_customer_id: stripeCustomerId,
                        payment_option: 'stripe'
                    }));
                    return [4, validateAddress()];
                case 2:
                    if (!(_d.sent())) {
                        return [2, {
                                status: 'invalid_shipping_address'
                            }];
                    }
                    saveCustomerToBrowser(stripeCustomerId, request.token.card.brand, request.token.card.last4, 'stripe');
                    _d.label = 3;
                case 3:
                    _d.trys.push([3, 10, , 11]);
                    return [4, orderService.placeOrder()];
                case 4:
                    order = _d.sent();
                    return [4, stripeService.processPayment(order)];
                case 5:
                    paymentResult = _d.sent();
                    return [4, orderService.setPaymentStatus(PeachPayOrder.sessionId(), true)];
                case 6:
                    if (!(_d.sent()).ok) {
                        return [2, {
                                status: 'fail'
                            }];
                    }
                    if (!!paymentResult) return [3, 8];
                    return [4, orderService.setOrderStatus(order, 'wc-failed', {
                            stripeCustomerId: stripeCustomerId
                        })];
                case 7:
                    _d.sent();
                    return [2, {
                            status: 'fail'
                        }];
                case 8: return [4, orderService.setOrderStatus(order, 'wc-processing', {
                        stripeCustomerId: stripeCustomerId
                    })];
                case 9:
                    orderStatusResult = _d.sent();
                    if (!orderStatusResult) {
                        return [2, {
                                status: 'fail'
                            }];
                    }
                    return [2, {
                            status: 'success',
                            redirectURL: orderStatusResult
                        }];
                case 10:
                    error_4 = _d.sent();
                    if (error_4 instanceof Error) {
                        captureSentryException(new Error("Stripe payment request process payment failed on ".concat(MerchantConfiguration.hostName(), ". Error: ").concat(error_4.message)));
                    }
                    return [2, {
                            status: 'fail'
                        }];
                case 11: return [2];
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
function initStripeSupport(message1, orderService) {
    var _a, _b, _c, _d, _e;
    return __awaiter(this, void 0, void 0, function () {
        var connectedStripeAccount, isDevMode, key, options, stripeAccount, stripeForCheckingPaymentIntent, stripe, elements, style, $card, stripeService, handleInjectedProcessPayment;
        var _this = this;
        return __generator(this, function (_f) {
            switch (_f.label) {
                case 0:
                    connectedStripeAccount = message1.phpData.connected_stripe_account;
                    isDevMode = isDevEnvironment(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()));
                    if (greaterThan(Environment.plugin.version(), '1.57.1') && !connectedStripeAccount && !Environment.testMode()) {
                        return [2];
                    }
                    if (!Feature.enabled(FeatureFlag.STRIPE)) {
                        return [2];
                    }
                    key = isDevMode ? 'pk_test_CnL2kA52V5dRqZbjlJ0sZ2gr00uBrOEmQQ' : 'pk_live_oROnIQDuexHZpnEOcUff3CRz00asaOOCAL';
                    options = {
                        locale: (_a = message1 === null || message1 === void 0 ? void 0 : message1.browserLocale) !== null && _a !== void 0 ? _a : 'auto'
                    };
                    if (!(MerchantConfiguration.hostName() !== 'woo.peachpay.app' && MerchantConfiguration.hostName() !== 'shop.peachpay.app')) return [3, 2];
                    return [4, fetchStripeAccount(MerchantConfiguration.hostName())];
                case 1:
                    stripeAccount = _f.sent();
                    if (stripeAccount) {
                        options = __assign(__assign({}, options), { stripeAccount: stripeAccount });
                    }
                    _f.label = 2;
                case 2:
                    stripeForCheckingPaymentIntent = Stripe(key, options);
                    stripe = Stripe(key, {
                        locale: (_b = message1.browserLocale) !== null && _b !== void 0 ? _b : 'auto'
                    });
                    elements = stripe.elements();
                    style = {
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
                    $card = elements.create('card', {
                        style: style,
                        hidePostalCode: true
                    });
                    $card.mount('#card-element');
                    $card.on('change', function (event) {
                        $qs('#card-errors', function ($element) { var _a, _b; return $element.textContent = (_b = (_a = event.error) === null || _a === void 0 ? void 0 : _a.message) !== null && _b !== void 0 ? _b : ''; });
                    });
                    stripeService = {
                        $card: $card,
                        elements: elements,
                        stripe: stripe,
                        stripeForPaymentIntent: stripeForCheckingPaymentIntent,
                        createStripeCustomer: addCardToStripeCustomer,
                        processPayment: function (order) { return __awaiter(_this, void 0, void 0, function () { return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0: return [4, handleStripePayment(order)];
                                case 1: return [2, _a.sent()];
                            }
                        }); }); }
                    };
                    handleInjectedProcessPayment = function (event) { return __awaiter(_this, void 0, void 0, function () {
                        return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0:
                                    if (!checkRequiredFields()) {
                                        return [2];
                                    }
                                    return [4, handleProcessPayment(event, stripeService, orderService)];
                                case 1:
                                    _a.sent();
                                    return [2];
                            }
                        });
                    }); };
                    initStripeButton();
                    initStripePaymentRequest(message1, stripeService, orderService);
                    onWindowMessage('3DS-authentication-complete', function (message) { return __awaiter(_this, void 0, void 0, function () { return __generator(this, function (_a) {
                        switch (_a.label) {
                            case 0: return [4, on3DSComplete(message.paymentIntentClientSecret, stripeService, orderService)];
                            case 1: return [2, _a.sent()];
                        }
                    }); }); });
                    onWindowMessage('submitPayment', function (message) { return __awaiter(_this, void 0, void 0, function () {
                        return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0:
                                    GLOBAL.completedOrder = message.order;
                                    return [4, legacyHandleStripePayment(message.order, orderService)];
                                case 1:
                                    _a.sent();
                                    return [2];
                            }
                        });
                    }); });
                    (_c = $qs('#pp-pay')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', handleInjectedProcessPayment);
                    (_d = $qs('#pp-pay-mobile')) === null || _d === void 0 ? void 0 : _d.addEventListener('click', handleInjectedProcessPayment);
                    (_e = $qs('#pp-pay-existing')) === null || _e === void 0 ? void 0 : _e.addEventListener('click', handleInjectedProcessPayment);
                    return [2];
            }
        });
    });
}
function handleProcessPayment(event, stripeService, orderService) {
    return __awaiter(this, void 0, void 0, function () {
        var $card, stripe, result, _a, _b, _c, error_5;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0:
                    $card = stripeService.$card, stripe = stripeService.stripe;
                    event.preventDefault();
                    store.dispatch(startModalProcessing());
                    if (!!Environment.customer.existing()) return [3, 5];
                    _d.label = 1;
                case 1:
                    _d.trys.push([1, 4, , 5]);
                    return [4, stripe.createToken($card)];
                case 2:
                    result = _d.sent();
                    if (result.error) {
                        handleTokenError(result.error);
                        return [2];
                    }
                    _b = (_a = store).dispatch;
                    _c = updateCustomerStripeId;
                    return [4, addCardToStripeCustomer(result.token.id, PeachPayCustomer.stripeDetails())];
                case 3:
                    _b.apply(_a, [_c.apply(void 0, [_d.sent()])]);
                    saveCustomerToBrowser(PeachPayCustomer.stripeId(), result.token.card.brand, result.token.card.last4, 'stripe');
                    return [3, 5];
                case 4:
                    error_5 = _d.sent();
                    if (error_5 instanceof Error) {
                        captureSentryException(new Error("Failed tokenizing or creating a new stripe customer on ".concat(MerchantConfiguration.hostName(), " Error: ").concat(error_5.message)));
                    }
                    store.dispatch(stopModalLoading());
                    return [2];
                case 5:
                    orderService.deprecated.placeOrder();
                    return [2];
            }
        });
    });
}
function fetchStripeAccount(domain) {
    return __awaiter(this, void 0, void 0, function () {
        var response;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + "api/v1/merchants/".concat(domain, "/stripe-account"))];
                case 1:
                    response = _a.sent();
                    if (response.status !== 200) {
                        return [2, ''];
                    }
                    return [2, response.text()];
            }
        });
    });
}
function handleTokenError(error) {
    $qs('#card-errors', function ($element) { return $element.textContent = error.message; });
    store.dispatch(stopModalLoading());
}
function on3DSComplete(paymentIntentClientSecret, stripeService, orderService) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var stripeForPaymentIntent, stripe, getPaymentIntent, result, paymentStatus, response;
        var _this = this;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    stripeForPaymentIntent = stripeService.stripeForPaymentIntent, stripe = stripeService.stripe;
                    (_a = $qs('#stripe-3DS-modal')) === null || _a === void 0 ? void 0 : _a.remove();
                    getPaymentIntent = function () { return __awaiter(_this, void 0, void 0, function () {
                        var domain, isOurStore;
                        return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0:
                                    domain = location.hostname;
                                    isOurStore = domain === 'woo.peachpay.app' || domain === 'shop.peachpay.app' || domain === 'localhost' || domain === 'woo.store.local' || domain === 'store.local';
                                    if (!isOurStore) return [3, 2];
                                    return [4, stripe.retrievePaymentIntent(paymentIntentClientSecret)];
                                case 1: return [2, _a.sent()];
                                case 2: return [4, stripeForPaymentIntent.retrievePaymentIntent(paymentIntentClientSecret)];
                                case 3: return [2, _a.sent()];
                            }
                        });
                    }); };
                    return [4, getPaymentIntent()];
                case 1:
                    result = _b.sent();
                    if (result.error || result.paymentIntent.status === 'requires_payment_method') {
                        handle3DSError();
                        return [2];
                    }
                    if (!(result.paymentIntent.status === 'succeeded' && GLOBAL.completedOrder)) return [3, 4];
                    return [4, orderService.setPaymentStatus(PeachPayOrder.sessionId(), true)];
                case 2:
                    paymentStatus = _b.sent();
                    if (!paymentStatus.ok) {
                        return [2];
                    }
                    return [4, paymentStatus.json()];
                case 3:
                    response = _b.sent();
                    orderService.deprecated.setOrderStatus(GLOBAL.completedOrder, {
                        status: 'wc-processing',
                        paymentType: 'Stripe',
                        transactionID: response.chargeId
                    });
                    _b.label = 4;
                case 4: return [2];
            }
        });
    });
}
function addCardToStripeCustomer(tokenID, customer) {
    return __awaiter(this, void 0, void 0, function () {
        var body, options, response, data_1, data;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    body = {
                        'customer_id': null,
                        'card_token': tokenID,
                        'name': customer.name,
                        'email': customer.email,
                        'phone': customer.phone
                    };
                    options = {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(body)
                    };
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'card', options)];
                case 1:
                    response = _a.sent();
                    if (!!response.ok) return [3, 3];
                    return [4, response.text()];
                case 2:
                    data_1 = _a.sent();
                    peachpayAlert(data_1);
                    throw new Error("Failed to create Stripe Customer on ".concat(MerchantConfiguration.hostName(), ". Error: ").concat(data_1));
                case 3: return [4, response.json()];
                case 4:
                    data = _a.sent();
                    return [2, data.customer];
            }
        });
    });
}
function show3DSecureModal(url) {
    var _a;
    var iframe = document.createElement('iframe');
    iframe.id = 'stripe-3DS-modal';
    iframe.classList.add('stripe-3ds-frame');
    iframe.src = url;
    (_a = $qs('#pp-modal-content')) === null || _a === void 0 ? void 0 : _a.append(iframe);
    showLoadingScreen();
    hideOtherScrollBars();
}
function handle3DSError() {
    hideLoadingScreen();
    showOtherScrollBars();
    peachpayAlert(getLocaleText('something-went-wrong'));
    store.dispatch(stopModalLoading());
}
function handleStripePayment(order) {
    var _a, _b, _c, _d;
    return __awaiter(this, void 0, void 0, function () {
        var body, options, response, result;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    body = {
                        sessionID: PeachPayOrder.sessionId(),
                        stripeCustomerID: PeachPayCustomer.stripeId(),
                        order: order,
                        returnURL: "".concat((_b = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.plugin_asset_url) !== null && _b !== void 0 ? _b : '', "/public/dist/").concat((_d = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.version) !== null && _d !== void 0 ? _d : '', "/checkout-modal/3ds.html")
                    };
                    options = {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(body)
                    };
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/session/pay', options)];
                case 1:
                    response = _e.sent();
                    return [4, response.json()];
                case 2:
                    result = _e.sent();
                    return [2, result.status === 'success'];
            }
        });
    });
}
function legacyHandleStripePayment(order, orderService) {
    var _a, _b, _c, _d, _e, _f;
    return __awaiter(this, void 0, void 0, function () {
        var body, options, response, result, _g;
        return __generator(this, function (_h) {
            switch (_h.label) {
                case 0:
                    body = {
                        sessionID: PeachPayOrder.sessionId(),
                        stripeCustomerID: PeachPayCustomer.stripeId(),
                        order: order,
                        returnURL: "".concat((_b = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.plugin_asset_url) !== null && _b !== void 0 ? _b : '', "/public/dist/").concat((_d = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.version) !== null && _d !== void 0 ? _d : '', "/checkout-modal/3ds.html")
                    };
                    options = {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(body)
                    };
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/session/pay', options)];
                case 1:
                    response = _h.sent();
                    return [4, response.json()];
                case 2:
                    result = _h.sent();
                    _g = result.status;
                    switch (_g) {
                        case 'success': return [3, 3];
                        case 'requires_action': return [3, 5];
                        case 'failure': return [3, 6];
                    }
                    return [3, 7];
                case 3: return [4, orderService.setPaymentStatus(PeachPayOrder.sessionId(), true)];
                case 4:
                    if (!(_h.sent()).ok) {
                        return [3, 7];
                    }
                    orderService.deprecated.setOrderStatus(order, {
                        status: 'wc-processing',
                        paymentType: 'Stripe',
                        transactionID: result.chargeId
                    });
                    return [3, 7];
                case 5:
                    show3DSecureModal((_e = result === null || result === void 0 ? void 0 : result.url) !== null && _e !== void 0 ? _e : '');
                    return [3, 7];
                case 6:
                    orderService.deprecated.setOrderStatus(order, {
                        status: 'wc-failed',
                        message: result.message
                    });
                    store.dispatch(stopModalLoading());
                    peachpayAlert((_f = result === null || result === void 0 ? void 0 : result.message) !== null && _f !== void 0 ? _f : '');
                    return [3, 7];
                case 7: return [2];
            }
        });
    });
}
function hideOtherScrollBars() {
    $qs('#pp-modal-content', function ($element) {
        if ($element.parentElement) {
            $element.parentElement.style.overflowY = 'hidden';
        }
    });
}
function showOtherScrollBars() {
    $qs('#pp-modal-content', function ($element) {
        if ($element.parentElement) {
            $element.parentElement.style.overflowY = 'scroll';
        }
    });
}
function hideLoadingScreen() {
    var _a, _b;
    (_a = $qs('#loading')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
    (_b = $qs('#loading')) === null || _b === void 0 ? void 0 : _b.classList.remove('flex-container');
}
function showLoadingScreen() {
    var _a, _b;
    (_a = $qs('#loading')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
    (_b = $qs('#loading')) === null || _b === void 0 ? void 0 : _b.classList.add('flex-container');
}
var paypalMerchantID = null;
var BN_CODE_SANDBOX = 'FLAVORsb-6jopv6540275_MP';
var BN_CODE_PRODUCTION = 'Peach_SP_PPCP';
function initPayPalEvents() {
    var _a;
    (_a = $qs('#paypal-option')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', paypalPaymentOption);
    store.subscribe(function () {
        renderPayPalButton(PeachPayCustomer.preferredPaymentMethod() === 'paypal' && Environment.modalUI.page() === 'payment');
    });
}
function paypalLoadScripts(scriptURLs) {
    return __awaiter(this, void 0, void 0, function () {
        function load(scriptURL) {
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
        }
        var promises, _i, scriptURLs_1, scriptURL2, _a, scriptURLs_2, scriptURL1;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    promises = [];
                    for (_i = 0, scriptURLs_1 = scriptURLs; _i < scriptURLs_1.length; _i++) {
                        scriptURL2 = scriptURLs_1[_i];
                        promises.push(load(scriptURL2));
                    }
                    return [4, Promise.all(promises)];
                case 1:
                    _b.sent();
                    for (_a = 0, scriptURLs_2 = scriptURLs; _a < scriptURLs_2.length; _a++) {
                        scriptURL1 = scriptURLs_2[_a];
                        paypalLoadScripts.loaded.add(scriptURL1);
                    }
                    return [2];
            }
        });
    });
}
paypalLoadScripts.loaded = new Set();
function loadPayPalScript() {
    return __awaiter(this, void 0, void 0, function () {
        var response, merchant, error_6;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 5, , 6]);
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
                    if (!(merchant.paypalMerchantID !== '')) return [3, 4];
                    paypalMerchantID = merchant.paypalMerchantID;
                    return [4, paypalLoadScripts([
                            "https://www.paypal.com/sdk/js?&client-id=".concat(merchant.clientID, "&merchant-id=").concat(merchant.paypalMerchantID, "&disable-funding=paylater,card,bancontact,blik,eps,giropay,ideal,mybank,p24,sofort&currency=").concat(MerchantConfiguration.currency.code()),
                        ])];
                case 3:
                    _a.sent();
                    _a.label = 4;
                case 4: return [3, 6];
                case 5:
                    error_6 = _a.sent();
                    if (error_6 instanceof Error) {
                        captureSentryException(new Error("Failed to retrieve paypal merchant Id or load Paypal Scripts on ".concat(location.hostname, ". Plugin Mode: ").concat(Environment.plugin.mode(), ". Error: ").concat(error_6.message)));
                    }
                    return [2, false];
                case 6: return [2, true];
            }
        });
    });
}
function initPayPalSupport(message, orderService) {
    return __awaiter(this, void 0, void 0, function () {
        var paypalChecked;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    paypalChecked = message.phpData.paypal;
                    if (!paypalChecked) {
                        return [2];
                    }
                    return [4, loadPayPalScript()];
                case 1:
                    if (!(_a.sent())) {
                        return [2];
                    }
                    initPayPalEvents();
                    if (!(paypalMerchantID && Number.parseInt(paypalChecked !== null && paypalChecked !== void 0 ? paypalChecked : '0'))) return [3, 3];
                    return [4, initPayPalButton(orderService)];
                case 2:
                    _a.sent();
                    showPayPalButton();
                    _a.label = 3;
                case 3: return [2];
            }
        });
    });
}
function initPayPalButton(orderService) {
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
            showPayPalLoadingSpinner();
            placeOrderOnStoreAndListenForCompletion(data, actions, orderService);
        },
        onClick: function () {
            return checkRequiredFields();
        }
    });
    $paypalButton.render('#paypal-button-container');
    $paypalButton.render('#paypal-button-container-mobile');
    $paypalButton.render('#paypal-button-container-existing');
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
                        domain: MerchantConfiguration.hostName(),
                        details: {
                            id: '',
                            number: '',
                            currency: MerchantConfiguration.currency.code(),
                            discount_total: DefaultCart.totalAppliedCoupons().toFixed(2),
                            shipping_total: DefaultCart.totalShipping().toFixed(2),
                            total: DefaultCart.total().toFixed(2),
                            total_tax: ((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.wc_prices_include_tax) ? '0.00' : DefaultCart.totalTax().toFixed(2),
                            shipping: paypalCustomerAddress(),
                            line_items: getLineItems(),
                            shipping_lines: getShippingLines(),
                            fee_total: DefaultCart.totalAppliedFees().toFixed(2)
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
function showPayPalLoadingSpinner(show) {
    var _a, _b, _c, _d;
    if (show === void 0) { show = true; }
    var spinners = [
        '#paypal-spinner',
        '#paypal-spinner-mobile',
        '#paypal-spinner-existing',
    ];
    for (var _i = 0, spinners_1 = spinners; _i < spinners_1.length; _i++) {
        var spinner = spinners_1[_i];
        if (show) {
            (_a = $qs(spinner)) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        }
        else {
            (_b = $qs(spinner)) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
        }
    }
    var paypalButtonContainers = [
        '#paypal-button-container',
        '#paypal-button-container-mobile',
        '#paypal-button-container-existing',
    ];
    for (var _e = 0, paypalButtonContainers_1 = paypalButtonContainers; _e < paypalButtonContainers_1.length; _e++) {
        var container = paypalButtonContainers_1[_e];
        if (show) {
            (_c = $qs(container)) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        }
        else {
            (_d = $qs(container)) === null || _d === void 0 ? void 0 : _d.classList.remove('hide');
        }
    }
}
var latestOrderAttempt = 0;
function placeOrderOnStoreAndListenForCompletion(data, actions, orderService) {
    var _this = this;
    latestOrderAttempt++;
    var orderAttempt = latestOrderAttempt;
    onWindowMessage('submitPaypalOrder', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var error_7, capture, error1_1, customer, stripeCustomerID, cardBrand, cardLast4, transactionID;
        var _a, _b, _c, _d;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    if (latestOrderAttempt !== orderAttempt) {
                        return [2];
                    }
                    _e.label = 1;
                case 1:
                    _e.trys.push([1, 3, , 4]);
                    return [4, updatePayPalOrderWithFinalAmount(data.orderID, message.order)];
                case 2:
                    _e.sent();
                    return [3, 4];
                case 3:
                    error_7 = _e.sent();
                    captureSentryException(new Error('Error while updating PayPal order with final amount: ' + JSON.stringify(error_7)));
                    return [3, 4];
                case 4:
                    capture = null;
                    _e.label = 5;
                case 5:
                    _e.trys.push([5, 7, , 8]);
                    return [4, capturePayPalOrder(data.orderID)];
                case 6:
                    capture = _e.sent();
                    return [3, 8];
                case 7:
                    error1_1 = _e.sent();
                    captureSentryException(new Error('Error while capturing PayPal order: ' + JSON.stringify(error1_1)));
                    return [3, 8];
                case 8:
                    if (!((capture === null || capture === void 0 ? void 0 : capture.status) === 'COMPLETED')) return [3, 11];
                    return [4, getCustomer()];
                case 9:
                    customer = _e.sent();
                    if (customer === null) {
                        saveCustomerToBrowser(null, null, null, 'paypal');
                    }
                    else {
                        stripeCustomerID = customer.stripe_customer_id ? customer.stripe_customer_id : null;
                        cardBrand = (_b = (_a = customer.card) === null || _a === void 0 ? void 0 : _a.brand) !== null && _b !== void 0 ? _b : null;
                        cardLast4 = (_d = (_c = customer.card) === null || _c === void 0 ? void 0 : _c.last4) !== null && _d !== void 0 ? _d : null;
                        saveCustomerToBrowser(stripeCustomerID, cardBrand, cardLast4, 'paypal');
                    }
                    return [4, orderService.setPaymentStatus(PeachPayOrder.sessionId(), true)];
                case 10:
                    if (!(_e.sent()).ok) {
                        return [2];
                    }
                    transactionID = capture.purchase_units[0].payments.captures[0].id;
                    orderService.deprecated.setOrderStatus(message.order, {
                        status: 'wc-processing',
                        paymentType: 'PayPal',
                        transactionID: transactionID
                    });
                    return [3, 12];
                case 11:
                    if ((capture === null || capture === void 0 ? void 0 : capture.details[0].issue) === 'INSTRUMENT_DECLINED') {
                        showPayPalLoadingSpinner(false);
                        window.parent.postMessage({
                            event: 'paypalAlert',
                            message: capture.details[0].description
                        }, '*');
                        restartAction(actions);
                    }
                    else {
                        showPayPalLoadingSpinner(false);
                        window.parent.postMessage({
                            event: 'paypalAlert',
                            message: 'Something went wrong.'
                        }, '*');
                        restartAction(actions);
                    }
                    _e.label = 12;
                case 12: return [2];
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
function showPayPalButton() {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j;
    (_a = $qs('#shipping-options-container')) === null || _a === void 0 ? void 0 : _a.classList.remove('mt-payment');
    $qs('#pp-shipping-options', function ($element) { return $element.style.marginBottom = '0.5rem'; });
    (_b = $qs('.form-row')) === null || _b === void 0 ? void 0 : _b.classList.remove('mb-payment');
    $qs('#payment-methods', function ($element) { return $element.style.marginTop = '1rem'; });
    (_c = $qs('#stripe-option')) === null || _c === void 0 ? void 0 : _c.classList.remove('paypal-disabled');
    (_d = $qs('#stripe-option')) === null || _d === void 0 ? void 0 : _d.classList.add('pm-option-block');
    (_e = $qs('#cc-regular')) === null || _e === void 0 ? void 0 : _e.classList.remove('hide');
    (_f = $qs('#stripe-box')) === null || _f === void 0 ? void 0 : _f.classList.remove('hide');
    (_g = $qs('#paypal-option')) === null || _g === void 0 ? void 0 : _g.classList.remove('hide');
    (_h = $qs('#pp-pay-btn')) === null || _h === void 0 ? void 0 : _h.classList.remove('paypal-disabled-btn');
    (_j = $qs('#pp-pay-btn')) === null || _j === void 0 ? void 0 : _j.classList.add('pp-pay-mt');
}
function paypalPaymentOption() {
    store.dispatch(updatePreferredPaymentMethod('paypal'));
}
function renderPayPalButton(display) {
    var _a, _b, _c, _d, _e, _f;
    if (display) {
        $qs('#paypal-pm', function ($element) { return $element.checked = true; });
        (_a = $qs('#paypal-pm')) === null || _a === void 0 ? void 0 : _a.setAttribute('checked', 'true');
        $qs('#paypal-option', function ($element) { return $element.style.backgroundColor = 'white'; });
        (_b = $qs('#paypal-button-container')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
        (_c = $qs('#paypal-button-container-mobile')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
    }
    else {
        $qs('#paypal-pm', function ($element) { return $element.checked = false; });
        (_d = $qs('#paypal-pm')) === null || _d === void 0 ? void 0 : _d.removeAttribute('checked');
        $qs('#paypal-option', function ($element) { return $element.style.backgroundColor = '#f4f4f4'; });
        (_e = $qs('#paypal-button-container')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
        (_f = $qs('#paypal-button-container-mobile')) === null || _f === void 0 ? void 0 : _f.classList.add('hide');
    }
}
function initSummary(message) {
    var _a;
    initSummaryEvents();
    store.dispatch(updateMerchantTaxConfig({
        displayPricesInCartAndCheckout: ((_a = message.phpData) === null || _a === void 0 ? void 0 : _a.wc_tax_price_display) === 'incl' ? 'includeTax' : 'excludeTax'
    }));
}
function initSummaryEvents() {
    var _a, _b, _c;
    (_a = $qs('#pp-dropdown')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', orderSummaryDropdown);
    (_b = $qs('#pp-dropdown')) === null || _b === void 0 ? void 0 : _b.addEventListener('keypress', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            orderSummaryDropdown();
        }
    });
    (_c = $qs('#pp-dropdown-new')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', orderSummaryDropdown);
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
    return "\n<tr class=\"summary-line\" data-raw-cost=\"".concat(amount, "\">\n\t<td>").concat(name, "</td>\n\t<td>").concat(formatCurrencyString(amount)).concat(priceMetaHTML, "</td>\n</tr>");
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
function initModal() {
    var _this = this;
    var _a, _b, _c;
    store.subscribe(function () {
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
        renderContinueButtonLoading(Environment.modalUI.loadingMode());
        renderInfoPageDisplay(Environment.modalUI.page());
        renderPaymentPageDisplay(Environment.modalUI.page(), Environment.customer.existing(), Environment.customer.mobile());
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
    var _a, _b, _c, _d;
    if (modalPage === 'info') {
        (_a = $qs('#pp-continue')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('#pp-continue-mobile')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    }
    else {
        (_c = $qs('#pp-continue')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        (_d = $qs('#pp-continue-mobile')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
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
var defaultFormHTML = "<form id=\"pp-info-form\">\n<h2><span class=\"bold\" data-i18n=\"personal\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"email\" class=\"w-100 text-input\" type=\"email\" name=\"email\" placeholder=\" \" required>\n\t\t<label for=\"email\" data-i18n=\"email\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"phone\" class=\"w-100 text-input\" type=\"tel\" name=\"phone\" placeholder=\" \"required>\n\t\t<label for=\"phone\" data-i18n=\"phone\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_first\" class=\"w-100 text-input\" type=\"text\" name=\"name_first\" placeholder=\" \" required>\n\t\t<label for=\"name_first\" data-i18n=\"first-name\" class=\"form-label\"></label>\n\t\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_last\" class=\"w-100 text-input\" type=\"text\" name=\"name_last\" placeholder=\" \" required>\n\t\t<label for=\"name_last\" data-i18n=\"last-name\" class=\"form-label\"></label>\n\t</div>\n</div>\n<h2 class=\"shipping-address-header\"><span class=\"bold\" data-i18n=\"shipping\"></span></h2>\n<h2 class=\"billing-address-header hide\"><span class=\"bold\" data-i18n=\"billing\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-70\">\n\t\t<input id=\"address1\" type=\"text\" name=\"address1\" class=\"w-100 text-input\" placeholder=\" \" required>\n\t\t<label for=\"address1\" data-i18n=\"street\" class=\"form-label form-label\"></label>\n\t</div>\n\t<div class=\"flex w-30\">\n\t\t<input id=\"address2\" type=\"text\" name=\"address2\" placeholder=\" \" class=\"w-100 text-input\">\n\t\t<label for=\"address2\" data-i18n=\"apt\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"postal\" class=\"w-100 text-input\" type=\"text\" name=\"postal\" placeholder=\" \" required>\n\t\t<label for=\"postal\" data-i18n=\"postal\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"city\" class=\"w-100 text-input\" type=\"text\" name=\"city\" placeholder=\" \" required>\n\t\t<label for=\"city\" data-i18n=\"city\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"state\" size=\"1\">\n\t\t\t<option hidden disabled selected value></option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label region-country-label hide\" data-i18n=\"state\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<select id=\"country\" class=\"w-100\" name=\"country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"country-label\" class=\"form-label region-country-label\"></label>\n\t</div>\n</div>\n<div id=\"checkout-delivery-date\" class=\"hide\">\n\t<h2 data-i18n=\"delivery-date\"></h2>\n\t<input type=\"date\" id=\"delivery-date\" name=\"delivery-date\" value=\"\" min=\"\">\n</div>\n</form>";
var japaneseFormHTML = "<form id=\"pp-info-form\">\n<h2><span class=\"bold\" data-i18n=\"personal\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"email\" class=\"w-100 text-input\" type=\"email\" name=\"email\" placeholder=\" \" required>\n\t\t<label for=\"email\" data-i18n=\"email\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"phone\" class=\"w-100 text-input\" type=\"tel\" name=\"phone\" placeholder=\" \" required>\n\t\t<label for=\"phone\" data-i18n=\"phone\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_last\" class=\"w-100 text-input\"  type=\"text\" name=\"name_last\" placeholder=\" \" required>\n\t\t<label for=\"name_last\" data-i18n=\"last-name\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_first\" class=\"w-100 text-input\" type=\"text\" name=\"name_first\" placeholder=\" \" required>\n\t\t<label for=\"name_first\" data-i18n=\"first-name\" class=\"form-label\"></label>\n\t</div>\n</div>\n<h2 class=\"shipping-address-header\"><span class=\"bold\" data-i18n=\"shipping\"></span></h2>\n<h2 class=\"billing-address-header hide\"><span class=\"bold\" data-i18n=\"billing\"></span></h2>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<select id=\"country\" class=\"w-100\" name=\"country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"select-country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"country-labely\" class=\"form-label region-country-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"postal\" class=\"w-100 text-input\" type=\"text\" name=\"postal\" placeholder=\" \" required>\n\t\t<label for=\"postal\" data-i18n=\"postal\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"state\" size=\"1\">\n\t\t\t<option hidden disabled selected value>State</option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label region-country-label hide\">Region</label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"city\" class=\"w-100 text-input\" type=\"text\" name=\"city\" placeholder=\" \" required>\n\t\t<label for=\"city\" data-i18n=\"city\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-70\">\n\t\t<input id=\"address1\" type=\"text\" name=\"address1\" class=\"w-100 text-input\" placeholder=\" \" required>\n\t\t<label for=\"address1\" data-i18n=\"street\" class=\"form-label form-label\"></label>\n\t</div>\n\t<div class=\"flex w-30\">\n\t\t<input id=\"address2\" type=\"text\" name=\"address2\" placeholder=\" \" class=\"w-100 text-input\">\n\t\t<label for=\"address2\" data-i18n=\"apt\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div id=\"checkout-delivery-date\" class=\"hide\">\n\t<h2 data-i18n=\"delivery-date\"></h2>\n\t<input type=\"date\" id=\"delivery-date\" name=\"delivery-date\" value=\"\" min=\"\">\n</div>\n</form>";
var checkoutFormNoPhoneNoApt = "<form id=\"pp-info-form\">\n<h2><span class=\"bold\" data-i18n=\"personal\"></span></h2>\n<div class=\"flex\">\n\t<input id=\"email\" class=\"w-100 text-input\" type=\"email\" name=\"email\" placeholder=\" \" required>\n\t<label for=\"email\" data-i18n=\"email\" class=\"form-label\"></label>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_first\" class=\"w-100 text-input\" type=\"text\" name=\"name_first\" placeholder=\" \" required>\n\t\t<label for=\"name_first\" data-i18n=\"first-name\" class=\"form-label\"></label>\n\t\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"name_last\" class=\"w-100 text-input\" type=\"text\" name=\"name_last\" placeholder=\" \" required>\n\t\t<label for=\"name_last\" data-i18n=\"last-name\" class=\"form-label\"></label>\n\t</div>\n</div>\n<h2 class=\"shipping-address-header\"><span class=\"bold\" data-i18n=\"shipping\"></span></h2>\n<h2 class=\"billing-address-header hide\"><span class=\"bold\" data-i18n=\"billing\"></span></h2>\n<div class=\"flex\">\n\t<input id=\"address1\" type=\"text\" name=\"address1\" class=\"w-100 text-input\" placeholder=\" \" required>\n\t<label for=\"address1\" data-i18n=\"street\" class=\"form-label form-label\"></label>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"postal\" class=\"w-100 text-input\" type=\"text\" name=\"postal\" placeholder=\" \" required>\n\t\t<label for=\"postal\" data-i18n=\"postal\" class=\"form-label\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<input id=\"city\" class=\"w-100 text-input\" type=\"text\" name=\"city\" placeholder=\" \" required>\n\t\t<label for=\"city\" data-i18n=\"city\" class=\"form-label\"></label>\n\t</div>\n</div>\n<div class=\"flex\">\n\t<div class=\"flex w-50\">\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"state\" size=\"1\">\n\t\t\t<option hidden disabled selected value></option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label region-country-label hide\" data-i18n=\"state\"></label>\n\t</div>\n\t<div class=\"flex w-50\">\n\t\t<select id=\"country\" class=\"w-100\" name=\"country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"country-label\" class=\"form-label region-country-label\"></label>\n\t</div>\n</div>\n<div id=\"checkout-delivery-date\" class=\"hide\">\n\t<h2 data-i18n=\"delivery-date\"></h2>\n\t<input type=\"date\" id=\"delivery-date\" name=\"delivery-date\" value=\"\" min=\"\">\n</div>\n</form>";
function initLinkedProducts() {
    var _a, _b, _c;
    (_a = $qs('.prev-btn')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', scrollLeft);
    (_b = $qs('.next-btn')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', scrollRight);
    (_c = $qs('#products-list')) === null || _c === void 0 ? void 0 : _c.addEventListener('scroll', scrollAdjuster);
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
                    var productsList = $qs('#products-list');
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
        (_a = $qs('.prev-btn')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
        (_b = $qs('.next-btn')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
    }
    else {
        (_c = $qs('.prev-btn')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
        (_d = $qs('.next-btn')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
    }
    if (!$qs('.product-body')) {
        (_e = $qs('#linked-products-section')) === null || _e === void 0 ? void 0 : _e.classList.add('hide');
    }
    scrollAdjuster();
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
function scrollRight() {
    var _a;
    (_a = $qs('.prev-btn')) === null || _a === void 0 ? void 0 : _a.classList.remove('scroll-end');
    $qs('#products-list', function ($element) { return $element.scrollLeft += 392; });
}
function scrollLeft() {
    var _a;
    (_a = $qs('.next-btn')) === null || _a === void 0 ? void 0 : _a.classList.remove('scroll-end');
    $qs('#products-list', function ($element) { return $element.scrollLeft -= 392; });
}
function scrollAdjuster() {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l;
    var scrollEnd = ((_a = $qs('#products-list')) === null || _a === void 0 ? void 0 : _a.scrollLeft) ? (_b = $qs('#products-list')) === null || _b === void 0 ? void 0 : _b.scrollLeft : 1;
    var offset = (_c = $qs('#products-list')) === null || _c === void 0 ? void 0 : _c.offsetWidth;
    var scrollWidth = (_d = $qs('#products-list')) === null || _d === void 0 ? void 0 : _d.scrollWidth;
    if (((_e = $qs('#products-list')) === null || _e === void 0 ? void 0 : _e.scrollLeft) === 0) {
        (_f = $qs('.prev-btn')) === null || _f === void 0 ? void 0 : _f.classList.add('scroll-end');
        (_g = $qs('.next-btn')) === null || _g === void 0 ? void 0 : _g.classList.remove('scroll-end');
    }
    else if (scrollEnd && scrollWidth && offset && scrollEnd + 1 >= scrollWidth - offset) {
        (_h = $qs('.next-btn')) === null || _h === void 0 ? void 0 : _h.classList.add('scroll-end');
        (_j = $qs('.prev-btn')) === null || _j === void 0 ? void 0 : _j.classList.remove('scroll-end');
    }
    else {
        (_k = $qs('.next-btn')) === null || _k === void 0 ? void 0 : _k.classList.remove('scroll-end');
        (_l = $qs('.prev-btn')) === null || _l === void 0 ? void 0 : _l.classList.remove('scroll-end');
    }
}
function sendCartItems(_cart, sessionID) {
    return __awaiter(this, void 0, void 0, function () {
        var requestBody, options, error_8;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!GLOBAL.phpData) {
                        return [2];
                    }
                    if (!GLOBAL.phpData.has_valid_key) {
                        return [2];
                    }
                    _a.label = 1;
                case 1:
                    _a.trys.push([1, 3, , 4]);
                    requestBody = {
                        'session_id': sessionID,
                        'items': [],
                        'merchant_name': MerchantConfiguration.name(),
                        'merchant_hostname': MerchantConfiguration.hostName()
                    };
                    options = {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(requestBody)
                    };
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'session/item', options)];
                case 2:
                    _a.sent();
                    return [3, 4];
                case 3:
                    error_8 = _a.sent();
                    if (error_8 instanceof Error) {
                        captureSentryException(new Error("Send cart items failed on ".concat(MerchantConfiguration.hostName(), ", Error: ").concat(error_8.message)));
                    }
                    return [3, 4];
                case 4: return [2];
            }
        });
    });
}
function initOneClickCheckout(testMode) {
    var oneClickURL = getOneClickURL(location.hostname, testMode);
    var $body = document.querySelector('body');
    $body === null || $body === void 0 ? void 0 : $body.insertAdjacentHTML('beforeend', "\n\t<iframe id=\"one-click-iframe\" \n\t\tframeborder=\"0\" \n\t\tallowtransparency=\"true\" \n\t\tscrolling=\"no\" \n\t\tallow=\"payment *\"\n\t\taria-hidden=\"true\" \n\t\ttabindex=\"-1\" \n\t\tstyle=\"border: none !important; margin: 0px !important; padding: 0px !important; width: 1px !important; min-width: 100% !important; overflow: hidden !important; display: block !important; visibility: hidden !important; position: fixed !important; height: 1px !important; pointer-events: none !important; user-select: none !important;\"\n\t\tsrc=\"".concat(oneClickURL, "one-click.html\"\n\t>\n\t\tUnable to load PeachPay One Click Checkout Support\n\t</iframe>"));
}
(function () {
    var _this = this;
    onWindowMessage('init', function (message) { return __awaiter(_this, void 0, void 0, function () {
        var orderService, sessionResponse, session;
        var _this = this;
        var _a, _b, _c, _d;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    store.dispatch(updateMerchantHostName(message.merchantHostname));
                    addFormFields(message.phpData.language);
                    GLOBAL.phpData = message.phpData;
                    if (typeof GLOBAL.phpData.has_valid_key === 'undefined') {
                        GLOBAL.phpData.has_valid_key = true;
                    }
                    store.dispatch(updateMerchantName(message.phpData.merchant_name));
                    store.dispatch(setFeatureSupport(message.phpData.feature_support, message.phpData));
                    store.dispatch(updateLanguage(message.phpData.language === 'detect-from-page' ? message.pageLanguage : message.phpData.language));
                    store.dispatch(updateEnvironment({
                        pluginIsTestMode: Boolean(message.isTestMode),
                        pluginPageType: determinePageType(message.isCartPage, message.isCheckoutPage),
                        customerIsMobile: message.isMobile,
                        pluginButtonColor: message.phpData.button_color,
                        pluginVersion: message.phpData.version
                    }));
                    initModal();
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
                    if (Feature.enabled(FeatureFlag.ADDITIONAL_FIELDS)) {
                        renderAdditionalFields((_b = (_a = message.phpData) === null || _a === void 0 ? void 0 : _a.additional_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.additional_fields_order) !== null && _d !== void 0 ? _d : []);
                    }
                    orderService = getOrderService();
                    return [4, initStripeSupport(message, orderService)];
                case 1:
                    _e.sent();
                    return [4, initPayPalSupport(message, orderService)];
                case 2:
                    _e.sent();
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'session')];
                case 3:
                    sessionResponse = _e.sent();
                    return [4, sessionResponse.json()];
                case 4:
                    session = _e.sent();
                    store.dispatch(updateSessionId(session.id));
                    return [4, sendCartItems(DefaultCart.contents(), session.id)];
                case 5:
                    _e.sent();
                    initOneClickCheckout(message.isTestMode);
                    onWindowMessage('pp-one-click-loaded', function () { return __awaiter(_this, void 0, void 0, function () {
                        var _a;
                        return __generator(this, function (_b) {
                            switch (_b.label) {
                                case 0: return [4, loadCustomer()];
                                case 1:
                                    _b.sent();
                                    store.dispatch(startModalLoading());
                                    return [4, requestCartCalculation(!Environment.customer.existing())];
                                case 2:
                                    _b.sent();
                                    store.dispatch(stopModalLoading());
                                    (_a = self.parent) === null || _a === void 0 ? void 0 : _a.postMessage('loaded', '*');
                                    return [2];
                            }
                        });
                    }); });
                    return [2];
            }
        });
    }); });
})();
function addFormFields(lang) {
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
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYnVuZGxlLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXMiOlsiLi4vaW50ZXJtZWRpYXRlL2J1bmRsZS5qcyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7QUFJQSxTQUFTLEdBQUcsQ0FBQyxRQUFRLEVBQUUsRUFBUztJQUFULG1CQUFBLEVBQUEsU0FBUztJQUM1QixJQUFNLFFBQVEsR0FBRyxRQUFRLENBQUMsYUFBYSxDQUFDLFFBQVEsQ0FBQyxDQUFDO0lBQ2xELElBQUksUUFBUSxJQUFJLEVBQUUsS0FBSyxJQUFJLEVBQUU7UUFDekIsRUFBRSxDQUFDLFFBQVEsQ0FBQyxDQUFDO0tBQ2hCO0lBQ0QsT0FBTyxRQUFRLENBQUM7QUFDcEIsQ0FBQztBQUNELFNBQVMsTUFBTSxDQUFDLFFBQVEsRUFBRSxRQUFRO0lBQzlCLElBQU0sTUFBTSxHQUFHLEtBQUssQ0FBQyxJQUFJLENBQUMsUUFBUSxDQUFDLGdCQUFnQixDQUFDLFFBQVEsQ0FBQyxDQUFDLENBQUM7SUFDL0QsSUFBSSxRQUFRLEVBQUU7UUFDVixLQUF1QixVQUFNLEVBQU4saUJBQU0sRUFBTixvQkFBTSxFQUFOLElBQU0sRUFBQztZQUF6QixJQUFNLFFBQVEsZUFBQTtZQUNmLFFBQVEsQ0FBQyxRQUFRLENBQUMsQ0FBQztTQUN0QjtLQUNKO0lBQ0QsT0FBTyxNQUFNLENBQUM7QUFDbEIsQ0FBQztBQUNELFNBQVMsZUFBZSxDQUFDLFNBQVMsRUFBRSxFQUFFO0lBQXRDLGlCQU1DO0lBTEcsSUFBSSxDQUFDLGdCQUFnQixDQUFDLFNBQVMsRUFBRSxVQUFPLEtBQUs7Ozs7eUJBQ3JDLENBQUEsS0FBSyxDQUFDLElBQUksQ0FBQyxLQUFLLEtBQUssU0FBUyxDQUFBLEVBQTlCLGNBQThCO29CQUM5QixXQUFNLEVBQUUsQ0FBQyxLQUFLLENBQUMsSUFBSSxDQUFDLEVBQUE7O29CQUFwQixTQUFvQixDQUFDOzs7OztTQUU1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SUFBSSxDQUFDLENBQUM7QUFDM0QsQ0FBQyIsInNvdXJjZXNDb250ZW50IjpbIi8vIGRlbm8tZm10LWlnbm9yZS1maWxlXG4vLyBkZW5vLWxpbnQtaWdub3JlLWZpbGVcbi8vIFRoaXMgY29kZSB3YXMgYnVuZGxlZCB1c2luZyBgZGVubyBidW5kbGVgIGFuZCBpdCdzIG5vdCByZWNvbW1lbmRlZCB0byBlZGl0IGl0IG1hbnVhbGx5XG5cbmZ1bmN0aW9uICRxcyhzZWxlY3RvciwgY2IgPSBudWxsKSB7XG4gICAgY29uc3QgJGVsZW1lbnQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKHNlbGVjdG9yKTtcbiAgICBpZiAoJGVsZW1lbnQgJiYgY2IgIT09IG51bGwpIHtcbiAgICAgICAgY2IoJGVsZW1lbnQpO1xuICAgIH1cbiAgICByZXR1cm4gJGVsZW1lbnQ7XG59XG5mdW5jdGlvbiAkcXNBbGwoc2VsZWN0b3IsIGNhbGxiYWNrKSB7XG4gICAgY29uc3QgcmVzdWx0ID0gQXJyYXkuZnJvbShkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKSk7XG4gICAgaWYgKGNhbGxiYWNrKSB7XG4gICAgICAgIGZvciAoY29uc3QgJGVsZW1lbnQgb2YgcmVzdWx0KXtcbiAgICAgICAgICAgIGNhbGxiYWNrKCRlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXR1cm4gcmVzdWx0O1xufVxuZnVuY3Rpb24gb25XaW5kb3dNZXNzYWdlKGV2ZW50TmFtZSwgY2IpIHtcbiAgICBzZWxmLmFkZEV2ZW50TGlzdGVuZXIoJ21lc3NhZ2UnLCBhc3luYyAoZXZlbnQpPT57XG4gICAgICAgIGlmIChldmVudC5kYXRhLmV2ZW50ID09PSBldmVudE5hbWUpIHtcbiAgICAgICAgICAgIGF3YWl0IGNiKGV2ZW50LmRhdGEpO1xuICAgICAgICB9XG4gICAgfSwgZmFsc2UpO1xufVxuZnVuY3Rpb24gb25XaW5kb3dEYXRhRmV0Y2goZW5kcG9pbnQsIHJlcXVlc3RDYWxsYmFjaykge1xuICAgIHNlbGYuYWRkRXZlbnRMaXN0ZW5lcignbWVzc2FnZScsIGFzeW5jIChtZXNzYWdlKT0+e1xuICAgICAgICBpZiAobWVzc2FnZS5kYXRhLmV2ZW50ID09PSBlbmRwb2ludCkge1xuICAgICAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgICAgICBjb25zdCByZXNwb25zZSA9IGF3YWl0IHJlcXVlc3RDYWxsYmFjayhtZXNzYWdlLmRhdGEucmVxdWVzdCk7XG4gICAgICAgICAgICAgICAgbWVzc2FnZS5wb3J0c1swXS5wb3N0TWVzc2FnZSh7XG4gICAgICAgICAgICAgICAgICAgIHJlc3VsdDogcmVzcG9uc2VcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICAgICAgbWVzc2FnZS5wb3J0c1swXS5wb3N0TWVzc2FnZSh7XG4gICAgICAgICAgICAgICAgICAgIGVycm9yXG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9KTtcbn1cbmZ1bmN0aW9uIGZldGNoV2luZG93RGF0YSh0YXJnZXRXaW5kb3csIGVuZHBvaW50LCByZXF1ZXN0KSB7XG4gICAgcmV0dXJuIG5ldyBQcm9taXNlKChyZXNvbHZlLCByZWplY3QpPT57XG4gICAgICAgIGNvbnN0IGNoYW5uZWwgPSBuZXcgTWVzc2FnZUNoYW5uZWwoKTtcbiAgICAgICAgY2hhbm5lbC5wb3J0MS5vbm1lc3NhZ2UgPSAoeyBkYXRhICB9KT0+e1xuICAgICAgICAgICAgY2hhbm5lbC5wb3J0MS5jbG9zZSgpO1xuICAgICAgICAgICAgaWYgKGRhdGEuZXJyb3IpIHtcbiAgICAgICAgICAgICAgICByZWplY3QoZGF0YS5lcnJvcik7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIHJlc29sdmUoZGF0YS5yZXN1bHQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9O1xuICAgICAgICBpZiAoIXRhcmdldFdpbmRvdykge1xuICAgICAgICAgICAgcmVqZWN0KG5ldyBFcnJvcignVGFyZ2V0IHdpbmRvdyBpcyBub3QgdmFsaWQuJykpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGFyZ2V0V2luZG93LnBvc3RNZXNzYWdlKHtcbiAgICAgICAgICAgICAgICBldmVudDogZW5kcG9pbnQsXG4gICAgICAgICAgICAgICAgcmVxdWVzdFxuICAgICAgICAgICAgfSwgJyonLCBbXG4gICAgICAgICAgICAgICAgY2hhbm5lbC5wb3J0MlxuICAgICAgICAgICAgXSk7XG4gICAgICAgIH1cbiAgICB9KTtcbn1cbmZ1bmN0aW9uIGZldGNoSG9zdFdpbmRvd0RhdGEoZW5kcG9pbnQsIHJlcXVlc3QpIHtcbiAgICByZXR1cm4gZmV0Y2hXaW5kb3dEYXRhKHdpbmRvdy50b3AsIGVuZHBvaW50LCByZXF1ZXN0KTtcbn1cbnZhciBEaXNwYXRjaEFjdGlvblR5cGU7XG4oZnVuY3Rpb24oRGlzcGF0Y2hBY3Rpb25UeXBlMSkge1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJJTklUXCJdID0gJ2luaXQnO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJFTlZJUk9OTUVOVFwiXSA9ICdlbnZpcm9ubWVudCc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIk9SREVSX1NFU1NJT05JRFwiXSA9ICdwZWFjaHBheU9yZGVyL3Nlc3Npb25JZCc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIk9SREVSX0FERFJFU1NfVkFMSURBVEVEXCJdID0gJ3BlYWNocGF5T3JkZXIvYWRkcmVzc1ZhbGlkYXRlZCc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIk9SREVSX1NFVF9FWFRSQV9GSUVMRFNcIl0gPSAncGVhY2hwYXlPcmRlci9leHRyYUZpZWxkcy9zZXQnO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJPUkRFUl9TRVRfRVJST1JfTUVTU0FHRVwiXSA9ICdwZWFjaHBheU9yZGVyL2Vycm9yTWVzc2FnZS9zZXQnO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJQRUFDSFBBWV9DVVNUT01FUlwiXSA9ICdwZWFjaHBheS9jdXN0b21lcic7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIlBFQUNIUEFZX0NVU1RPTUVSX1NUUklQRV9JRFwiXSA9ICdwZWFjaHBheS9jdXN0b21lci9zdHJpcGVJZCc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIlBFQUNIUEFZX0NVU1RPTUVSX1BBWU1FTlRfTUVUSE9EXCJdID0gJ3BlYWNocGF5L2N1c3RvbWVyL3BheW1lbnRfbWV0aG9kJztcbiAgICBEaXNwYXRjaEFjdGlvblR5cGUxW1wiTUVSQ0hBTlRfQ1VTVE9NRVJcIl0gPSAnbWVyY2hhbnQvY3VzdG9tZXInO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJNRVJDSEFOVF9DVVNUT01FUl9FWElTVFwiXSA9ICdtZXJjaGFudC9jdXN0b21lci9leGlzdCc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIkVOVklST05NRU5UX0xBTkdVQUdFXCJdID0gJ21vZGFsL2xhbmd1YWdlJztcbiAgICBEaXNwYXRjaEFjdGlvblR5cGUxW1wiTUVSQ0hBTlRfTkFNRVwiXSA9ICdtZXJjaGFudC9uYW1lJztcbiAgICBEaXNwYXRjaEFjdGlvblR5cGUxW1wiTUVSQ0hBTlRfSE9TVE5BTUVcIl0gPSAnbWVyY2hhbnQvaG9zdG5hbWUnO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJNRVJDSEFOVF9HRU5FUkFMXCJdID0gJ21lcmNoYW50L2dlbmVyYWwnO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJNRVJDSEFOVF9HRU5FUkFMX0NVUlJFTkNZXCJdID0gJ21lcmNoYW50L2dlbmVyYWwvY3VycmVuY3knO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJNRVJDSEFOVF9BQ0NPVU5UXCJdID0gJ21lcmNoYW50L2FjY291bnRzJztcbiAgICBEaXNwYXRjaEFjdGlvblR5cGUxW1wiTUVSQ0hBTlRfVEFYXCJdID0gJ21lcmNoYW50L3RheCc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIk1FUkNIQU5UX1NISVBQSU5HXCJdID0gJ21lcmNoYW50L3NoaXBwaW5nJztcbiAgICBEaXNwYXRjaEFjdGlvblR5cGUxW1wiREVGQVVMVF9DQVJUX0NPTlRFTlRTXCJdID0gJ2RlZmF1bHQvY2FydC9jb250ZW50cyc7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIkRFRkFVTFRfQ0FSVF9DQUxDVUxBVElPTlwiXSA9ICdkZWZhdWx0L2NhcnQvY2FsY3VsYXRpb24nO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJDQVJUX0NBTENVTEFUSU9OXCJdID0gJ2NhcnQvY2FsY3VsYXRpb24nO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJDQVJUX1NISVBQSU5HX1NFTEVDVElPTlwiXSA9ICdjYXJ0L3NoaXBwaW5nL3NlbGVjdGlvbic7XG4gICAgRGlzcGF0Y2hBY3Rpb25UeXBlMVtcIkVOVklST05NRU5UX1NFVF9GRUFUVVJFU1wiXSA9ICdFTlZJUk9OTUVOVF9TRVRfRkVBVFVSRVMnO1xuICAgIERpc3BhdGNoQWN0aW9uVHlwZTFbXCJQRUFDSFBBWV9DVVNUT01FUl9TSElQUElOR1wiXSA9ICdQRUFDSFBBWV9DVVNUT01FUl9TSElQUElORyc7XG59KShEaXNwYXRjaEFjdGlvblR5cGUgfHwgKERpc3BhdGNoQWN0aW9uVHlwZSA9IHt9KSk7XG5jb25zdCBpbml0aWFsU3RhdGUgPSB7XG4gICAgZW52aXJvbm1lbnQ6IHtcbiAgICAgICAgbGFuZ3VhZ2U6ICdlbi1VUycsXG4gICAgICAgIHBsdWdpbjoge1xuICAgICAgICAgICAgdmVyc2lvbjogJycsXG4gICAgICAgICAgICBtb2RlOiAnbGl2ZScsXG4gICAgICAgICAgICBwYWdlVHlwZTogJ2NhcnQnLFxuICAgICAgICAgICAgYnV0dG9uQ29sb3I6ICcjRkY4NzZDJyxcbiAgICAgICAgICAgIGZlYXR1cmVTdXBwb3J0OiB7fVxuICAgICAgICB9LFxuICAgICAgICBjdXN0b21lcjoge1xuICAgICAgICAgICAgZXhpc3Rpbmc6IGZhbHNlLFxuICAgICAgICAgICAgbW9iaWxlOiBmYWxzZVxuICAgICAgICB9LFxuICAgICAgICBtb2RhbFVJOiB7XG4gICAgICAgICAgICBvcGVuOiBmYWxzZSxcbiAgICAgICAgICAgIHBhZ2U6ICdpbmZvJyxcbiAgICAgICAgICAgIGxvYWRpbmdNb2RlOiAnZmluaXNoZWQnXG4gICAgICAgIH1cbiAgICB9LFxuICAgIHBlYWNoUGF5T3JkZXI6IHtcbiAgICAgICAgc2Vzc2lvbklkOiAnJyxcbiAgICAgICAgY3VzdG9tZXJBZGRyZXNzVmFsaWRhdGVkOiBmYWxzZSxcbiAgICAgICAgYWRkaXRpb25hbEZpZWxkczoge30sXG4gICAgICAgIGVycm9yTWVzc2FnZTogJydcbiAgICB9LFxuICAgIHBlYWNoUGF5Q3VzdG9tZXI6IHtcbiAgICAgICAgZW1haWw6ICcnLFxuICAgICAgICBuYW1lX2ZpcnN0OiAnJyxcbiAgICAgICAgbmFtZV9sYXN0OiAnJyxcbiAgICAgICAgYWRkcmVzczE6ICcnLFxuICAgICAgICBhZGRyZXNzMjogJycsXG4gICAgICAgIGNpdHk6ICcnLFxuICAgICAgICBzdGF0ZTogJycsXG4gICAgICAgIGNvdW50cnk6ICcnLFxuICAgICAgICBwb3N0YWw6ICcnLFxuICAgICAgICBwaG9uZTogJydcbiAgICB9LFxuICAgIG1lcmNoYW50Q3VzdG9tZXI6IHtcbiAgICAgICAgdXNlcm5hbWU6ICcnLFxuICAgICAgICBsb2dnZWRJbjogZmFsc2UsXG4gICAgICAgIHVzZXJuYW1lSXNSZWdpc3RlcmVkOiBmYWxzZVxuICAgIH0sXG4gICAgbWVyY2hhbnRDb25maWd1cmF0aW9uOiB7XG4gICAgICAgIG5hbWU6ICcnLFxuICAgICAgICBob3N0TmFtZTogJycsXG4gICAgICAgIGdlbmVyYWw6IHtcbiAgICAgICAgICAgIGN1cnJlbmN5OiB7XG4gICAgICAgICAgICAgICAgY29kZTogJ1VTRCcsXG4gICAgICAgICAgICAgICAgc3ltYm9sOiAnJCcsXG4gICAgICAgICAgICAgICAgcG9zaXRpb246ICdsZWZ0JyxcbiAgICAgICAgICAgICAgICB0aG91c2FuZHNTZXBhcmF0b3I6ICcsJyxcbiAgICAgICAgICAgICAgICBkZWNpbWFsU2VwYXJhdG9yOiAnLicsXG4gICAgICAgICAgICAgICAgcm91bmRpbmc6ICdkaXNhYmxlZCcsXG4gICAgICAgICAgICAgICAgZGVjaW1hbHM6IDJcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgc2hpcHBpbmc6IHtcbiAgICAgICAgICAgIHNoaXBwaW5nWm9uZXM6IDBcbiAgICAgICAgfSxcbiAgICAgICAgdGF4OiB7XG4gICAgICAgICAgICBkaXNwbGF5UHJpY2VzSW5DYXJ0QW5kQ2hlY2tvdXQ6ICdleGNsdWRlVGF4J1xuICAgICAgICB9LFxuICAgICAgICBhY2NvdW50c0FuZFByaXZhY3k6IHtcbiAgICAgICAgICAgIGFsbG93R3Vlc3RDaGVja291dDogdHJ1ZSxcbiAgICAgICAgICAgIGFsbG93QWNjb3VudENyZWF0aW9uT3JMb2dpbkR1cmluZ0NoZWNrb3V0OiB0cnVlLFxuICAgICAgICAgICAgYXV0b0dlbmVyYXRlVXNlcm5hbWU6IGZhbHNlLFxuICAgICAgICAgICAgYXV0b0dlbmVyYXRlUGFzc3dvcmQ6IGZhbHNlXG4gICAgICAgIH1cbiAgICB9LFxuICAgIGNhbGN1bGF0ZWRDYXJ0czoge1xuICAgICAgICAwOiB7XG4gICAgICAgICAgICBwYWNrYWdlX3JlY29yZDoge30sXG4gICAgICAgICAgICBjYXJ0OiBbXSxcbiAgICAgICAgICAgIHN1bW1hcnk6IHtcbiAgICAgICAgICAgICAgICBmZWVzX3JlY29yZDoge30sXG4gICAgICAgICAgICAgICAgY291cG9uc19yZWNvcmQ6IHt9LFxuICAgICAgICAgICAgICAgIGdpZnRfY2FyZF9yZWNvcmQ6IHt9LFxuICAgICAgICAgICAgICAgIHN1YnRvdGFsOiAwLFxuICAgICAgICAgICAgICAgIHRvdGFsX3NoaXBwaW5nOiAwLFxuICAgICAgICAgICAgICAgIHRvdGFsX3RheDogMCxcbiAgICAgICAgICAgICAgICB0b3RhbDogMFxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGNhcnRfbWV0YToge1xuICAgICAgICAgICAgICAgIGlzX3ZpcnR1YWw6IGZhbHNlXG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG59O1xuZnVuY3Rpb24gY3JlYXRlRGlzcGF0Y2hVcGRhdGUodHlwZSkge1xuICAgIHJldHVybiAocGF5bG9hZCk9Pih7XG4gICAgICAgICAgICB0eXBlLFxuICAgICAgICAgICAgcGF5bG9hZFxuICAgICAgICB9KVxuICAgIDtcbn1cbmZ1bmN0aW9uIG1lcmNoYW50Q29uZmlndXJhdGlvblJlZHVjZXIoc3RhdGUsIGFjdGlvbikge1xuICAgIHN3aXRjaChhY3Rpb24udHlwZSl7XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLk1FUkNIQU5UX0dFTkVSQUxfQ1VSUkVOQ1k6XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIC4uLnN0YXRlLFxuICAgICAgICAgICAgICAgIGdlbmVyYWw6IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uc3RhdGUuZ2VuZXJhbCxcbiAgICAgICAgICAgICAgICAgICAgY3VycmVuY3k6IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC4uLmFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9HRU5FUkFMOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICBnZW5lcmFsOiB7XG4gICAgICAgICAgICAgICAgICAgIC4uLmFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfQUNDT1VOVDpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgYWNjb3VudHNBbmRQcml2YWN5OiB7XG4gICAgICAgICAgICAgICAgICAgIC4uLmFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfVEFYOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICB0YXg6IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9TSElQUElORzpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgc2hpcHBpbmc6IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9IT1NUTkFNRTpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgaG9zdE5hbWU6IGFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9OQU1FOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICBuYW1lOiBhY3Rpb24ucGF5bG9hZFxuICAgICAgICAgICAgfTtcbiAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHt9LCBzdGF0ZSk7XG4gICAgfVxufVxuZnVuY3Rpb24gcGVhY2hQYXlPcmRlclJlZHVjZXIoc3RhdGUsIGFjdGlvbikge1xuICAgIHN3aXRjaChhY3Rpb24udHlwZSl7XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLk9SREVSX1NFU1NJT05JRDpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgc2Vzc2lvbklkOiBhY3Rpb24ucGF5bG9hZFxuICAgICAgICAgICAgfTtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuT1JERVJfQUREUkVTU19WQUxJREFURUQ6XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIC4uLnN0YXRlLFxuICAgICAgICAgICAgICAgIGN1c3RvbWVyQWRkcmVzc1ZhbGlkYXRlZDogYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLk9SREVSX1NFVF9FWFRSQV9GSUVMRFM6XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIC4uLnN0YXRlLFxuICAgICAgICAgICAgICAgIGFkZGl0aW9uYWxGaWVsZHM6IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5PUkRFUl9TRVRfRVJST1JfTUVTU0FHRTpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgZXJyb3JNZXNzYWdlOiBhY3Rpb24ucGF5bG9hZFxuICAgICAgICAgICAgfTtcbiAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGVcbiAgICAgICAgICAgIH07XG4gICAgfVxufVxuZnVuY3Rpb24gZW52aXJvbm1lbnRSZWR1Y2VyKHN0YXRlLCBhY3Rpb24pIHtcbiAgICBzd2l0Y2goYWN0aW9uLnR5cGUpe1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5FTlZJUk9OTUVOVDpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWQsXG4gICAgICAgICAgICAgICAgY3VzdG9tZXI6IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWQuY3VzdG9tZXJcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIHBsdWdpbjoge1xuICAgICAgICAgICAgICAgICAgICAuLi5hY3Rpb24ucGF5bG9hZC5wbHVnaW5cbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgICAgIG1vZGFsVUk6IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWQubW9kYWxVSVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH07XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLkVOVklST05NRU5UX0xBTkdVQUdFOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICBsYW5ndWFnZTogYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLkVOVklST05NRU5UX1NFVF9GRUFUVVJFUzpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgcGx1Z2luOiB7XG4gICAgICAgICAgICAgICAgICAgIC4uLnN0YXRlLnBsdWdpbixcbiAgICAgICAgICAgICAgICAgICAgZmVhdHVyZVN1cHBvcnQ6IGFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgbW9kYWxVSToge1xuICAgICAgICAgICAgICAgICAgICAuLi5zdGF0ZS5tb2RhbFVJXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICB9XG59XG5mdW5jdGlvbiBtZXJjaGFudEN1c3RvbWVyUmVkdWNlcihzdGF0ZSwgYWN0aW9uKSB7XG4gICAgc3dpdGNoKGFjdGlvbi50eXBlKXtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfQ1VTVE9NRVI6XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIC4uLmFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9DVVNUT01FUl9FWElTVDpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgICAgICAgICAgdXNlcm5hbWVJc1JlZ2lzdGVyZWQ6IGFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICB9O1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZVxuICAgICAgICAgICAgfTtcbiAgICB9XG59XG5mdW5jdGlvbiBwZWFjaFBheUN1c3RvbWVyUmVkdWNlcihzdGF0ZSwgYWN0aW9uKSB7XG4gICAgc3dpdGNoKGFjdGlvbi50eXBlKXtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuUEVBQ0hQQVlfQ1VTVE9NRVI6XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIC4uLmFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICB9O1xuICAgICAgICBjYXNlIERpc3BhdGNoQWN0aW9uVHlwZS5QRUFDSFBBWV9DVVNUT01FUl9TVFJJUEVfSUQ6XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIC4uLnN0YXRlLFxuICAgICAgICAgICAgICAgIHN0cmlwZV9jdXN0b21lcl9pZDogYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLlBFQUNIUEFZX0NVU1RPTUVSX1BBWU1FTlRfTUVUSE9EOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICBwYXltZW50X29wdGlvbjogYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLlBFQUNIUEFZX0NVU1RPTUVSX1NISVBQSU5HOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICAuLi5hY3Rpb24ucGF5bG9hZCxcbiAgICAgICAgICAgICAgICBwb3N0YWw6IGFjdGlvbi5wYXlsb2FkLnBvc3Rjb2RlXG4gICAgICAgICAgICB9O1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZVxuICAgICAgICAgICAgfTtcbiAgICB9XG59XG5mdW5jdGlvbiBjYXJ0UmVkdWNlcihzdGF0ZSwgYWN0aW9uKSB7XG4gICAgc3dpdGNoKGFjdGlvbi50eXBlKXtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuREVGQVVMVF9DQVJUX0NPTlRFTlRTOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICAwOiB7XG4gICAgICAgICAgICAgICAgICAgIC4uLnN0YXRlWycwJ10sXG4gICAgICAgICAgICAgICAgICAgIGNhcnQ6IGFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuREVGQVVMVF9DQVJUX0NBTENVTEFUSU9OOlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZSxcbiAgICAgICAgICAgICAgICAwOiB7XG4gICAgICAgICAgICAgICAgICAgIC4uLmFjdGlvbi5wYXlsb2FkXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfTtcbiAgICAgICAgY2FzZSBEaXNwYXRjaEFjdGlvblR5cGUuQ0FSVF9DQUxDVUxBVElPTjpcbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgLi4uYWN0aW9uLnBheWxvYWRcbiAgICAgICAgICAgIH07XG4gICAgICAgIGNhc2UgRGlzcGF0Y2hBY3Rpb25UeXBlLkNBUlRfU0hJUFBJTkdfU0VMRUNUSU9OOlxuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIGNvbnN0IHBheWxvYWQgPSBhY3Rpb24ucGF5bG9hZDtcbiAgICAgICAgICAgICAgICBjb25zdCBuZXdTdGF0ZSA9IHtcbiAgICAgICAgICAgICAgICAgICAgLi4uc3RhdGVcbiAgICAgICAgICAgICAgICB9O1xuICAgICAgICAgICAgICAgIGlmICghbmV3U3RhdGVbcGF5bG9hZC5jYXJ0S2V5XSB8fCAhbmV3U3RhdGVbcGF5bG9hZC5jYXJ0S2V5XT8ucGFja2FnZV9yZWNvcmQpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG5ld1N0YXRlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBjb25zdCBwYWNrYWdlUmVjb3JkID0gbmV3U3RhdGVbcGF5bG9hZC5jYXJ0S2V5XS5wYWNrYWdlX3JlY29yZDtcbiAgICAgICAgICAgICAgICBpZiAoIXBhY2thZ2VSZWNvcmRbcGF5bG9hZC5zaGlwcGluZ1BhY2thZ2VLZXldKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBuZXdTdGF0ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgcGFja2FnZVJlY29yZFtwYXlsb2FkLnNoaXBwaW5nUGFja2FnZUtleV0uc2VsZWN0ZWRfbWV0aG9kID0gcGF5bG9hZC5wYWNrYWdlTWV0aG9kSWQ7XG4gICAgICAgICAgICAgICAgcmV0dXJuIG5ld1N0YXRlO1xuICAgICAgICAgICAgfVxuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICAuLi5zdGF0ZVxuICAgICAgICAgICAgfTtcbiAgICB9XG59XG5mdW5jdGlvbiByb290UmVkdWNlcihzdGF0ZSA9IGluaXRpYWxTdGF0ZSwgYWN0aW9uKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgLi4uc3RhdGUsXG4gICAgICAgIHBlYWNoUGF5T3JkZXI6IHBlYWNoUGF5T3JkZXJSZWR1Y2VyKHN0YXRlLnBlYWNoUGF5T3JkZXIsIGFjdGlvbiksXG4gICAgICAgIGVudmlyb25tZW50OiBlbnZpcm9ubWVudFJlZHVjZXIoc3RhdGUuZW52aXJvbm1lbnQsIGFjdGlvbiksXG4gICAgICAgIG1lcmNoYW50Q3VzdG9tZXI6IG1lcmNoYW50Q3VzdG9tZXJSZWR1Y2VyKHN0YXRlLm1lcmNoYW50Q3VzdG9tZXIsIGFjdGlvbiksXG4gICAgICAgIHBlYWNoUGF5Q3VzdG9tZXI6IHBlYWNoUGF5Q3VzdG9tZXJSZWR1Y2VyKHN0YXRlLnBlYWNoUGF5Q3VzdG9tZXIsIGFjdGlvbiksXG4gICAgICAgIG1lcmNoYW50Q29uZmlndXJhdGlvbjogbWVyY2hhbnRDb25maWd1cmF0aW9uUmVkdWNlcihzdGF0ZS5tZXJjaGFudENvbmZpZ3VyYXRpb24sIGFjdGlvbiksXG4gICAgICAgIGNhbGN1bGF0ZWRDYXJ0czogY2FydFJlZHVjZXIoc3RhdGUuY2FsY3VsYXRlZENhcnRzLCBhY3Rpb24pXG4gICAgfTtcbn1cbmNvbnN0IHN0b3JlID0gY3JlYXRlU3RvcmUocm9vdFJlZHVjZXIpO1xuZnVuY3Rpb24gdXBkYXRlRW52aXJvbm1lbnQob3B0aW9ucykge1xuICAgIHJldHVybiB7XG4gICAgICAgIHR5cGU6IERpc3BhdGNoQWN0aW9uVHlwZS5FTlZJUk9OTUVOVCxcbiAgICAgICAgcGF5bG9hZDoge1xuICAgICAgICAgICAgbGFuZ3VhZ2U6IG9wdGlvbnMubGFuZ3VhZ2UgPz8gRW52aXJvbm1lbnQubGFuZ3VhZ2UoKSxcbiAgICAgICAgICAgIGN1c3RvbWVyOiB7XG4gICAgICAgICAgICAgICAgZXhpc3Rpbmc6IG9wdGlvbnMuY3VzdG9tZXJFeGlzdHMgPz8gRW52aXJvbm1lbnQuY3VzdG9tZXIuZXhpc3RpbmcoKSxcbiAgICAgICAgICAgICAgICBtb2JpbGU6IG9wdGlvbnMuY3VzdG9tZXJJc01vYmlsZSA/PyBFbnZpcm9ubWVudC5jdXN0b21lci5tb2JpbGUoKVxuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIHBsdWdpbjoge1xuICAgICAgICAgICAgICAgIHZlcnNpb246IG9wdGlvbnMucGx1Z2luVmVyc2lvbiA/PyBFbnZpcm9ubWVudC5wbHVnaW4udmVyc2lvbigpLFxuICAgICAgICAgICAgICAgIG1vZGU6IHR5cGVvZiBvcHRpb25zLnBsdWdpbklzVGVzdE1vZGUgPT09ICdib29sZWFuJyA/IG9wdGlvbnMucGx1Z2luSXNUZXN0TW9kZSA/ICd0ZXN0JyA6ICdsaXZlJyA6IEVudmlyb25tZW50LnBsdWdpbi5tb2RlKCksXG4gICAgICAgICAgICAgICAgYnV0dG9uQ29sb3I6IG9wdGlvbnMucGx1Z2luQnV0dG9uQ29sb3IgPz8gRW52aXJvbm1lbnQucGx1Z2luLmJ1dHRvbkNvbG9yKCksXG4gICAgICAgICAgICAgICAgcGFnZVR5cGU6IG9wdGlvbnMucGx1Z2luUGFnZVR5cGUgPz8gRW52aXJvbm1lbnQucGx1Z2luLnBhZ2VUeXBlKCksXG4gICAgICAgICAgICAgICAgZmVhdHVyZVN1cHBvcnQ6IHN0b3JlLmdldFN0YXRlKCkuZW52aXJvbm1lbnQucGx1Z2luLmZlYXR1cmVTdXBwb3J0XG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgbW9kYWxVSToge1xuICAgICAgICAgICAgICAgIG9wZW46IG9wdGlvbnMubW9kYWxJc09wZW4gPz8gRW52aXJvbm1lbnQubW9kYWxVSS5vcGVuKCksXG4gICAgICAgICAgICAgICAgcGFnZTogb3B0aW9ucy5tb2RhbFBhZ2VUeXBlID8/IEVudmlyb25tZW50Lm1vZGFsVUkucGFnZSgpLFxuICAgICAgICAgICAgICAgIGxvYWRpbmdNb2RlOiBvcHRpb25zLm1vZGFsTG9hZGluZyA/PyBFbnZpcm9ubWVudC5tb2RhbFVJLmxvYWRpbmdNb2RlKClcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH07XG59XG5mdW5jdGlvbiBzZXRGZWF0dXJlU3VwcG9ydChmZWF0dXJlcyA9IHt9LCBwaHBEYXRhKSB7XG4gICAgaWYgKCFmZWF0dXJlc1tGZWF0dXJlRmxhZy5DT1VQT05fSU5QVVRdKSB7XG4gICAgICAgIGZlYXR1cmVzW0ZlYXR1cmVGbGFnLkNPVVBPTl9JTlBVVF0gPSB7XG4gICAgICAgICAgICBlbmFibGVkOiBCb29sZWFuKHBocERhdGEuZW5hYmxlX2NvdXBvbnMpLFxuICAgICAgICAgICAgdmVyc2lvbjogMFxuICAgICAgICB9O1xuICAgIH1cbiAgICBpZiAoIWZlYXR1cmVzW0ZlYXR1cmVGbGFnLk9SREVSX05PVEVTXSkge1xuICAgICAgICBmZWF0dXJlc1tGZWF0dXJlRmxhZy5PUkRFUl9OT1RFU10gPSB7XG4gICAgICAgICAgICBlbmFibGVkOiBCb29sZWFuKHBocERhdGEuZW5hYmxlX29yZGVyX25vdGVzKSxcbiAgICAgICAgICAgIHZlcnNpb246IDBcbiAgICAgICAgfTtcbiAgICB9XG4gICAgaWYgKCFmZWF0dXJlc1tGZWF0dXJlRmxhZy5HSUZUQ0FSRF9JTlBVVF0pIHtcbiAgICAgICAgZmVhdHVyZXNbRmVhdHVyZUZsYWcuR0lGVENBUkRfSU5QVVRdID0ge1xuICAgICAgICAgICAgZW5hYmxlZDogQm9vbGVhbihwaHBEYXRhLnBsdWdpbl9wd193b29jb21tZXJjZV9naWZ0X2NhcmRzX2FjdGl2ZSksXG4gICAgICAgICAgICB2ZXJzaW9uOiAwXG4gICAgICAgIH07XG4gICAgfVxuICAgIGlmICghZmVhdHVyZXNbRmVhdHVyZUZsYWcuU1RSSVBFXSkge1xuICAgICAgICBmZWF0dXJlc1tGZWF0dXJlRmxhZy5TVFJJUEVdID0ge1xuICAgICAgICAgICAgZW5hYmxlZDogdHJ1ZSxcbiAgICAgICAgICAgIHZlcnNpb246IDBcbiAgICAgICAgfTtcbiAgICB9XG4gICAgcmV0dXJuIHtcbiAgICAgICAgdHlwZTogRGlzcGF0Y2hBY3Rpb25UeXBlLkVOVklST05NRU5UX1NFVF9GRUFUVVJFUyxcbiAgICAgICAgcGF5bG9hZDogZmVhdHVyZXNcbiAgICB9O1xufVxuY29uc3QgdXBkYXRlTGFuZ3VhZ2UgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuRU5WSVJPTk1FTlRfTEFOR1VBR0UpO1xuY29uc3Qgc3RhcnRNb2RhbExvYWRpbmcgPSAoKT0+dXBkYXRlRW52aXJvbm1lbnQoe1xuICAgICAgICBtb2RhbExvYWRpbmc6ICdsb2FkaW5nJ1xuICAgIH0pXG47XG5jb25zdCBzdGFydE1vZGFsUHJvY2Vzc2luZyA9ICgpPT51cGRhdGVFbnZpcm9ubWVudCh7XG4gICAgICAgIG1vZGFsTG9hZGluZzogJ3Byb2Nlc3NpbmcnXG4gICAgfSlcbjtcbmNvbnN0IHN0b3BNb2RhbExvYWRpbmcgPSAoKT0+dXBkYXRlRW52aXJvbm1lbnQoe1xuICAgICAgICBtb2RhbExvYWRpbmc6ICdmaW5pc2hlZCdcbiAgICB9KVxuO1xuY29uc3QgRW52aXJvbm1lbnQgPSB7XG4gICAgZW52aXJvbm1lbnQ6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLmVudmlyb25tZW50XG4gICAgLFxuICAgIGxhbmd1YWdlOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5lbnZpcm9ubWVudC5sYW5ndWFnZVxuICAgICxcbiAgICB0ZXN0TW9kZTogKCk9PnN0b3JlLmdldFN0YXRlKCkuZW52aXJvbm1lbnQucGx1Z2luLm1vZGUgPT09ICd0ZXN0J1xuICAgICxcbiAgICBjdXN0b21lcjoge1xuICAgICAgICBleGlzdGluZzogKCk9PnN0b3JlLmdldFN0YXRlKCkuZW52aXJvbm1lbnQuY3VzdG9tZXIuZXhpc3RpbmdcbiAgICAgICAgLFxuICAgICAgICBtb2JpbGU6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLmVudmlyb25tZW50LmN1c3RvbWVyLm1vYmlsZVxuICAgIH0sXG4gICAgcGx1Z2luOiB7XG4gICAgICAgIHZlcnNpb246ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLmVudmlyb25tZW50LnBsdWdpbi52ZXJzaW9uXG4gICAgICAgICxcbiAgICAgICAgbW9kZTogKCk9PnN0b3JlLmdldFN0YXRlKCkuZW52aXJvbm1lbnQucGx1Z2luLm1vZGVcbiAgICAgICAgLFxuICAgICAgICBidXR0b25Db2xvcjogKCk9PnN0b3JlLmdldFN0YXRlKCkuZW52aXJvbm1lbnQucGx1Z2luLmJ1dHRvbkNvbG9yXG4gICAgICAgICxcbiAgICAgICAgcGFnZVR5cGU6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLmVudmlyb25tZW50LnBsdWdpbi5wYWdlVHlwZVxuICAgIH0sXG4gICAgbW9kYWxVSToge1xuICAgICAgICBvcGVuOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5lbnZpcm9ubWVudC5tb2RhbFVJLm9wZW5cbiAgICAgICAgLFxuICAgICAgICBwYWdlOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5lbnZpcm9ubWVudC5tb2RhbFVJLnBhZ2VcbiAgICAgICAgLFxuICAgICAgICBsb2FkaW5nTW9kZTogKCk9PnN0b3JlLmdldFN0YXRlKCkuZW52aXJvbm1lbnQubW9kYWxVSS5sb2FkaW5nTW9kZVxuICAgIH1cbn07XG5mdW5jdGlvbiBnZXRMb2NhbGVUZXh0KGtleSkge1xuICAgIGlmICghcGVhY2hwYXlpMThuW2tleV0pIHtcbiAgICAgICAgcmV0dXJuIHBlYWNocGF5aTE4bi51bmtub3duW0Vudmlyb25tZW50Lmxhbmd1YWdlKCldO1xuICAgIH1cbiAgICByZXR1cm4gcGVhY2hwYXlpMThuW2tleV1bRW52aXJvbm1lbnQubGFuZ3VhZ2UoKV07XG59XG5jb25zdCB1cGRhdGVNZXJjaGFudEN1cnJlbmN5Q29uZmlnID0gY3JlYXRlRGlzcGF0Y2hVcGRhdGUoRGlzcGF0Y2hBY3Rpb25UeXBlLk1FUkNIQU5UX0dFTkVSQUxfQ1VSUkVOQ1kpO1xuY29uc3QgdXBkYXRlTWVyY2hhbnRUYXhDb25maWcgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfVEFYKTtcbmNvbnN0IHVwZGF0ZU1lcmNoYW50R2VuZXJhbENvbmZpZyA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9HRU5FUkFMKTtcbmNvbnN0IHVwZGF0ZU1lcmNoYW50QWNjb3VudENvbmZpZyA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9BQ0NPVU5UKTtcbmNvbnN0IHVwZGF0ZU1lcmNoYW50U2hpcHBpbmdDb25maWcgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfU0hJUFBJTkcpO1xuY29uc3QgdXBkYXRlTWVyY2hhbnRIb3N0TmFtZSA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5NRVJDSEFOVF9IT1NUTkFNRSk7XG5jb25zdCB1cGRhdGVNZXJjaGFudE5hbWUgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfTkFNRSk7XG5jb25zdCBNZXJjaGFudENvbmZpZ3VyYXRpb24gPSB7XG4gICAgbmFtZTogKCk9PnN0b3JlLmdldFN0YXRlKCkubWVyY2hhbnRDb25maWd1cmF0aW9uLm5hbWVcbiAgICAsXG4gICAgaG9zdE5hbWU6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLm1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZVxuICAgICxcbiAgICBnZW5lcmFsOiB7XG4gICAgICAgIHdjTG9jYXRpb25JbmZvRGF0YTogKCk9PnN0b3JlLmdldFN0YXRlKCkubWVyY2hhbnRDb25maWd1cmF0aW9uLmdlbmVyYWwud2NMb2NhdGlvbkluZm9EYXRhXG4gICAgfSxcbiAgICBjdXJyZW5jeToge1xuICAgICAgICBjb25maWd1cmF0aW9uOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5tZXJjaGFudENvbmZpZ3VyYXRpb24uZ2VuZXJhbC5jdXJyZW5jeVxuICAgICAgICAsXG4gICAgICAgIGNvZGU6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLm1lcmNoYW50Q29uZmlndXJhdGlvbi5nZW5lcmFsLmN1cnJlbmN5LmNvZGVcbiAgICAgICAgLFxuICAgICAgICBzeW1ib2w6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLm1lcmNoYW50Q29uZmlndXJhdGlvbi5nZW5lcmFsLmN1cnJlbmN5LnN5bWJvbFxuICAgIH0sXG4gICAgdGF4OiB7XG4gICAgICAgIGRpc3BsYXlNb2RlOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5tZXJjaGFudENvbmZpZ3VyYXRpb24udGF4LmRpc3BsYXlQcmljZXNJbkNhcnRBbmRDaGVja291dFxuICAgIH0sXG4gICAgc2hpcHBpbmc6IHtcbiAgICAgICAgc2hpcHBpbmdab25lczogKCk9PnN0b3JlLmdldFN0YXRlKCkubWVyY2hhbnRDb25maWd1cmF0aW9uLnNoaXBwaW5nLnNoaXBwaW5nWm9uZXNcbiAgICB9LFxuICAgIGFjY291bnRzOiB7XG4gICAgICAgIGxvZ2luRHVyaW5nQ2hlY2tvdXRFbmFibGVkOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5tZXJjaGFudENvbmZpZ3VyYXRpb24uYWNjb3VudHNBbmRQcml2YWN5LmFsbG93QWNjb3VudENyZWF0aW9uT3JMb2dpbkR1cmluZ0NoZWNrb3V0XG4gICAgICAgICxcbiAgICAgICAgYWxsb3dHdWVzdENoZWNrb3V0OiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5tZXJjaGFudENvbmZpZ3VyYXRpb24uYWNjb3VudHNBbmRQcml2YWN5LmFsbG93R3Vlc3RDaGVja291dFxuICAgICAgICAsXG4gICAgICAgIGdlbmVyYXRlUGFzc3dvcmRFbmFibGVkOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5tZXJjaGFudENvbmZpZ3VyYXRpb24uYWNjb3VudHNBbmRQcml2YWN5LmF1dG9HZW5lcmF0ZVBhc3N3b3JkXG4gICAgICAgICxcbiAgICAgICAgZ2VuZXJhdGVVc2VybmFtZUVuYWJsZWQ6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLm1lcmNoYW50Q29uZmlndXJhdGlvbi5hY2NvdW50c0FuZFByaXZhY3kuYXV0b0dlbmVyYXRlVXNlcm5hbWVcbiAgICB9XG59O1xuZnVuY3Rpb24gY3JlYXRlU3RvcmUocmVkdWNlciwgcHJlbG9hZGVkU3RhdGUpIHtcbiAgICBsZXQgaXNEaXNwYXRjaGluZyA9IGZhbHNlO1xuICAgIGNvbnN0IGN1cnJlbnRSZWR1Y2VyID0gcmVkdWNlcjtcbiAgICBsZXQgY3VycmVudFN0YXRlID0gcHJlbG9hZGVkU3RhdGU7XG4gICAgbGV0IGN1cnJlbnRMaXN0ZW5lcnMgPSBbXTtcbiAgICBsZXQgbmV4dExpc3RlbmVycyA9IGN1cnJlbnRMaXN0ZW5lcnM7XG4gICAgY29uc3QgZGlzcGF0Y2ggPSAoYWN0aW9uKT0+e1xuICAgICAgICBpZiAodHlwZW9mIGFjdGlvbiAhPT0gJ29iamVjdCcpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoJ1lvdSBtYXkgb25seSBkaXNwYXRjaCBwbGFpbiBvYmplY3RzLiBSZWNlaXZlZDogJyArIHR5cGVvZiBhY3Rpb24pO1xuICAgICAgICB9XG4gICAgICAgIGlmICh0eXBlb2YgYWN0aW9uLnR5cGUgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKCdZb3UgbWF5IG5vdCBoYXZlIGFuIHVuZGVmaW5lZCBcInR5cGVcIiBwcm9wZXJ0eS4nKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoaXNEaXNwYXRjaGluZykge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdSZWR1Y2VycyBtYXkgbm90IGRpc3BhdGNoIGFjdGlvbnMuJyk7XG4gICAgICAgIH1cbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGlzRGlzcGF0Y2hpbmcgPSB0cnVlO1xuICAgICAgICAgICAgY3VycmVudFN0YXRlID0gY3VycmVudFJlZHVjZXIoY3VycmVudFN0YXRlLCBhY3Rpb24pO1xuICAgICAgICB9IGZpbmFsbHl7XG4gICAgICAgICAgICBpc0Rpc3BhdGNoaW5nID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgbGlzdGVuZXJzID0gY3VycmVudExpc3RlbmVycyA9IG5leHRMaXN0ZW5lcnM7XG4gICAgICAgIGZvcihsZXQgaSA9IDA7IGkgPCBsaXN0ZW5lcnM/Lmxlbmd0aDsgaSsrKXtcbiAgICAgICAgICAgIGNvbnN0IGxpc3RlbmVyID0gbGlzdGVuZXJzW2ldO1xuICAgICAgICAgICAgbGlzdGVuZXIoKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gYWN0aW9uO1xuICAgIH07XG4gICAgY29uc3QgZ2V0U3RhdGUgPSAoKT0+e1xuICAgICAgICBpZiAoaXNEaXNwYXRjaGluZykge1xuICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdZb3UgbWF5IG5vdCBjYWxsIGdldFN0YXRlIGZyb20gd2l0aGluIGEgcmVkdWNlci4nKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gY3VycmVudFN0YXRlO1xuICAgIH07XG4gICAgY29uc3Qgc3Vic2NyaWJlID0gKGxpc3RlbmVyKT0+e1xuICAgICAgICBpZiAodHlwZW9mIGxpc3RlbmVyICE9PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKCdFeHBlY3RlZCBhIGxpc3RlbmVyIHRvIGJlIGEgZnVuY3Rpb24uIEluc3RlYWQgcmVjZWl2ZWQ6ICcgKyB0eXBlb2YgbGlzdGVuZXIpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChpc0Rpc3BhdGNoaW5nKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1lvdSBtYXkgbm90IGFkZCBhIHN1YnNjcmliZXIgZnJvbSBhIHN1YnNjcmlwdGlvbiBmdW5jdGlvbi4nKTtcbiAgICAgICAgfVxuICAgICAgICBsZXQgaXNTdWJzY3JpYmVkID0gdHJ1ZTtcbiAgICAgICAgaWYgKG5leHRMaXN0ZW5lcnMgPT09IGN1cnJlbnRMaXN0ZW5lcnMpIHtcbiAgICAgICAgICAgIG5leHRMaXN0ZW5lcnMgPSBjdXJyZW50TGlzdGVuZXJzPy5zbGljZSgpID8/IG51bGw7XG4gICAgICAgIH1cbiAgICAgICAgbmV4dExpc3RlbmVycz8ucHVzaChsaXN0ZW5lcik7XG4gICAgICAgIHJldHVybiAoKT0+e1xuICAgICAgICAgICAgaWYgKCFpc1N1YnNjcmliZWQpIHtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpZiAoaXNEaXNwYXRjaGluZykge1xuICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignWW91IG1heSBub3QgcmVtb3ZlIGEgc3Vic2NyaWJlciB3aGlsZSByZWR1Y2luZyBvciBpbnNpZGUgYSBzdWJzY3JpcHRpb24gZnVuY3Rpb24uJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBpc1N1YnNjcmliZWQgPSBmYWxzZTtcbiAgICAgICAgICAgIGlmIChuZXh0TGlzdGVuZXJzID09PSBjdXJyZW50TGlzdGVuZXJzKSB7XG4gICAgICAgICAgICAgICAgbmV4dExpc3RlbmVycyA9IGN1cnJlbnRMaXN0ZW5lcnM/LnNsaWNlKCkgPz8gbnVsbDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGNvbnN0IGluZGV4ID0gbmV4dExpc3RlbmVycz8uaW5kZXhPZihsaXN0ZW5lcikgPz8gMDtcbiAgICAgICAgICAgIG5leHRMaXN0ZW5lcnMuc2xpY2UoaW5kZXgsIDEpO1xuICAgICAgICAgICAgY3VycmVudExpc3RlbmVycyA9IG51bGw7XG4gICAgICAgIH07XG4gICAgfTtcbiAgICBkaXNwYXRjaCh7XG4gICAgICAgIHR5cGU6ICdpbml0J1xuICAgIH0pO1xuICAgIGNvbnN0IHN0b3JlMSA9IHtcbiAgICAgICAgZGlzcGF0Y2gsXG4gICAgICAgIGdldFN0YXRlLFxuICAgICAgICBzdWJzY3JpYmVcbiAgICB9O1xuICAgIHJldHVybiBzdG9yZTE7XG59XG5jb25zdCB1cGRhdGVDYXJ0Q2FsY3VsYXRpb24gPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuQ0FSVF9DQUxDVUxBVElPTik7XG5jcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuREVGQVVMVF9DQVJUX0NPTlRFTlRTKTtcbmNvbnN0IHVwZGF0ZUNhcnRQYWNrYWdlU2hpcHBpbmdNZXRob2QgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuQ0FSVF9TSElQUElOR19TRUxFQ1RJT04pO1xuZnVuY3Rpb24gY3JlYXRlQ2FydFNlbGVjdG9ycyhjYXJ0S2V5ID0gJzAnKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgc2VsZWN0ZWRTaGlwcGluZ01ldGhvZDogKHBhY2thZ2VLZXkgPSAnMCcpPT5zdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XT8ucGFja2FnZV9yZWNvcmQ/LltwYWNrYWdlS2V5XT8uc2VsZWN0ZWRfbWV0aG9kID8/ICcnXG4gICAgICAgICxcbiAgICAgICAgc2VsZWN0ZWRTaGlwcGluZ01ldGhvZERldGFpbHM6IChwYWNrYWdlS2V5ID0gJzAnKT0+c3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbY2FydEtleV0/LnBhY2thZ2VfcmVjb3JkPy5bcGFja2FnZUtleV0gPz8gbnVsbFxuICAgICAgICAsXG4gICAgICAgIGNvbnRlbnRzOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbY2FydEtleV0/LmNhcnQgPz8gW11cbiAgICAgICAgLFxuICAgICAgICBzdWJ0b3RhbDogKCk9PnN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzW2NhcnRLZXldPy5zdW1tYXJ5LnN1YnRvdGFsID8/IDBcbiAgICAgICAgLFxuICAgICAgICBmZWVUb3RhbDogKGZlZSk9PnN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzW2NhcnRLZXldPy5zdW1tYXJ5LmZlZXNfcmVjb3JkW2ZlZV0gPz8gMFxuICAgICAgICAsXG4gICAgICAgIHRvdGFsQXBwbGllZEZlZXM6ICgpPT5PYmplY3QuZW50cmllcyhzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XT8uc3VtbWFyeS5mZWVzX3JlY29yZCA/PyB7fSkucmVkdWNlKChwcmV2aW91c1ZhbHVlLCBbXywgdmFsdWVdKT0+cHJldmlvdXNWYWx1ZSArICh2YWx1ZSA/PyAwKVxuICAgICAgICAgICAgLCAwKVxuICAgICAgICAsXG4gICAgICAgIGNvdXBvblRvdGFsOiAoY291cG9uKT0+c3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbY2FydEtleV0/LnN1bW1hcnkuY291cG9uc19yZWNvcmRbY291cG9uXSA/PyAwXG4gICAgICAgICxcbiAgICAgICAgdG90YWxBcHBsaWVkQ291cG9uczogKCk9Pk9iamVjdC5lbnRyaWVzKHN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzW2NhcnRLZXldPy5zdW1tYXJ5LmNvdXBvbnNfcmVjb3JkID8/IHt9KS5yZWR1Y2UoKHByZXZpb3VzVmFsdWUsIFtfLCB2YWx1ZV0pPT5wcmV2aW91c1ZhbHVlICsgKHZhbHVlID8/IDApXG4gICAgICAgICAgICAsIDApXG4gICAgICAgICxcbiAgICAgICAgY291cG9uUmVjb3JkOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbY2FydEtleV0/LnN1bW1hcnkuY291cG9uc19yZWNvcmRcbiAgICAgICAgLFxuICAgICAgICBnaWZ0Q2FyZFRvdGFsOiAoZ2lmdENhcmQpPT5zdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XT8uc3VtbWFyeS5naWZ0X2NhcmRfcmVjb3JkPy5bZ2lmdENhcmRdID8/IDBcbiAgICAgICAgLFxuICAgICAgICB0b3RhbEFwcGxpZWRHaWZ0Q2FyZHM6ICgpPT5PYmplY3QuZW50cmllcyhzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XT8uc3VtbWFyeS5naWZ0X2NhcmRfcmVjb3JkID8/IHt9KS5yZWR1Y2UoKHByZXZpb3VzVmFsdWUsIFtfLCB2YWx1ZV0pPT5wcmV2aW91c1ZhbHVlICsgKHZhbHVlID8/IDApXG4gICAgICAgICAgICAsIDApXG4gICAgICAgICxcbiAgICAgICAgdG90YWxTaGlwcGluZzogKCk9PnN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzW2NhcnRLZXldPy5zdW1tYXJ5LnRvdGFsX3NoaXBwaW5nID8/IDBcbiAgICAgICAgLFxuICAgICAgICB0b3RhbFRheDogKCk9PnN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzW2NhcnRLZXldPy5zdW1tYXJ5LnRvdGFsX3RheCA/PyAwXG4gICAgICAgICxcbiAgICAgICAgdG90YWw6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XT8uc3VtbWFyeS50b3RhbCA/PyAwXG4gICAgfTtcbn1cbmNvbnN0IERlZmF1bHRDYXJ0ID0gY3JlYXRlQ2FydFNlbGVjdG9ycygnMCcpO1xuY29uc3QgQ2FydHMgPSB7XG4gICAgYW55U2hpcHBpbmdNZXRob2RzQXZhaWxhYmxlOiAoKT0+e1xuICAgICAgICBmb3IgKGNvbnN0IGNhcnRLZXkgb2YgT2JqZWN0LmtleXMoc3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHMpKXtcbiAgICAgICAgICAgIGNvbnN0IGNhbGN1bGF0ZWRDYXJ0ID0gc3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbY2FydEtleV07XG4gICAgICAgICAgICBpZiAoIWNhbGN1bGF0ZWRDYXJ0KSB7XG4gICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBmb3IgKGNvbnN0IHBhY2thZ2VLZXkgb2YgT2JqZWN0LmtleXMoY2FsY3VsYXRlZENhcnQucGFja2FnZV9yZWNvcmQpKXtcbiAgICAgICAgICAgICAgICBjb25zdCBzaGlwcGluZ1BhY2thZ2UgPSBjYWxjdWxhdGVkQ2FydC5wYWNrYWdlX3JlY29yZFtwYWNrYWdlS2V5XTtcbiAgICAgICAgICAgICAgICBpZiAoIXNoaXBwaW5nUGFja2FnZSB8fCBPYmplY3QuZW50cmllcyhzaGlwcGluZ1BhY2thZ2UubWV0aG9kcykubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfSxcbiAgICBjb2xsZWN0U2VsZWN0ZWRTaGlwcGluZzogKCk9PntcbiAgICAgICAgY29uc3QgY2FydHMgPSBzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0cztcbiAgICAgICAgY29uc3Qgc2VsZWN0ZWRTaGlwcGluZ01ldGhvZHMgPSBbXTtcbiAgICAgICAgZm9yIChjb25zdCBjYXJ0IG9mIE9iamVjdC52YWx1ZXMoY2FydHMpKXtcbiAgICAgICAgICAgIGlmICghY2FydCkge1xuICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZm9yIChjb25zdCBbcGFja2FnZUtleSwgcGFja2FnZVJlY29yZF0gb2YgT2JqZWN0LmVudHJpZXMoY2FydC5wYWNrYWdlX3JlY29yZCA/PyB7fSkpe1xuICAgICAgICAgICAgICAgIGlmICghcGFja2FnZVJlY29yZCkge1xuICAgICAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgc2VsZWN0ZWRTaGlwcGluZ01ldGhvZHMucHVzaCh7XG4gICAgICAgICAgICAgICAgICAgIG1ldGhvZEtleTogYCR7cGFja2FnZUtleX1gLFxuICAgICAgICAgICAgICAgICAgICBzZWxlY3RlZFNoaXBwaW5nOiBwYWNrYWdlUmVjb3JkLnNlbGVjdGVkX21ldGhvZFxuICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzZWxlY3RlZFNoaXBwaW5nTWV0aG9kcztcbiAgICB9LFxuICAgIHN1YnNjcmlwdGlvblByZXNlbnQ6ICgpPT57XG4gICAgICAgIGZvciAoY29uc3QgY2FydEtleSBvZiBPYmplY3Qua2V5cyhzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0cykpe1xuICAgICAgICAgICAgY29uc3QgY2FsY3VsYXRlZENhcnQgPSBzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XTtcbiAgICAgICAgICAgIGlmICghY2FsY3VsYXRlZENhcnQpIHtcbiAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGlmIChjYWxjdWxhdGVkQ2FydC5jYXJ0X21ldGEuc3Vic2NyaXB0aW9uKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbn07XG5jYXJ0U3VtbWFyeVZpZXdEYXRhKCcwJyk7XG5mdW5jdGlvbiBjYXJ0U3VtbWFyeVZpZXdEYXRhKGNhcnRLZXkpIHtcbiAgICByZXR1cm4gKCk9PntcbiAgICAgICAgY29uc3QgY2FsY3VsYXRlZENhcnQgPSBzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1tjYXJ0S2V5XTtcbiAgICAgICAgaWYgKCFjYWxjdWxhdGVkQ2FydCkge1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICBjYXJ0U3VtbWFyeTogbmV3IEFycmF5KCksXG4gICAgICAgICAgICAgICAgY2FydE1ldGE6IHtcbiAgICAgICAgICAgICAgICAgICAgaXNfdmlydHVhbDogZmFsc2VcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9O1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IGNhcnRTdW1tYXJ5ID0gW107XG4gICAgICAgIGNvbnN0IGNhcnRNZXRhID0gY2FsY3VsYXRlZENhcnQuY2FydF9tZXRhO1xuICAgICAgICBjYXJ0U3VtbWFyeS5wdXNoKHtcbiAgICAgICAgICAgIGtleTogZ2V0TG9jYWxlVGV4dCgnc3VidG90YWwnKSxcbiAgICAgICAgICAgIHZhbHVlOiBjYWxjdWxhdGVkQ2FydC5zdW1tYXJ5LnN1YnRvdGFsXG4gICAgICAgIH0pO1xuICAgICAgICBmb3IgKGNvbnN0IFtjb3Vwb24sIGFtb3VudF0gb2YgT2JqZWN0LmVudHJpZXMoY2FsY3VsYXRlZENhcnQuc3VtbWFyeS5jb3Vwb25zX3JlY29yZCkpe1xuICAgICAgICAgICAgaWYgKCFhbW91bnQpIHtcbiAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGNhcnRTdW1tYXJ5LnB1c2goe1xuICAgICAgICAgICAgICAgIGtleTogYCR7Z2V0TG9jYWxlVGV4dCgnY291cG9uJyl9IC0gKCR7Y291cG9ufSlgLFxuICAgICAgICAgICAgICAgIHZhbHVlOiAtYW1vdW50XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBmb3IgKGNvbnN0IFtmZWUsIGFtb3VudDFdIG9mIE9iamVjdC5lbnRyaWVzKGNhbGN1bGF0ZWRDYXJ0LnN1bW1hcnkuZmVlc19yZWNvcmQpKXtcbiAgICAgICAgICAgIGlmICghYW1vdW50MSkge1xuICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY2FydFN1bW1hcnkucHVzaCh7XG4gICAgICAgICAgICAgICAga2V5OiBgRmVlIC0gKCR7ZmVlfSlgLFxuICAgICAgICAgICAgICAgIHZhbHVlOiBhbW91bnQxXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoIWNhbGN1bGF0ZWRDYXJ0LmNhcnRfbWV0YS5pc192aXJ0dWFsKSB7XG4gICAgICAgICAgICBjYXJ0U3VtbWFyeS5wdXNoKHtcbiAgICAgICAgICAgICAgICBrZXk6IGdldExvY2FsZVRleHQoJ3NoaXBwaW5nJyksXG4gICAgICAgICAgICAgICAgdmFsdWU6IGNhbGN1bGF0ZWRDYXJ0LnN1bW1hcnkudG90YWxfc2hpcHBpbmdcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIGlmIChNZXJjaGFudENvbmZpZ3VyYXRpb24udGF4LmRpc3BsYXlNb2RlKCkgPT09ICdleGNsdWRlVGF4Jykge1xuICAgICAgICAgICAgY2FydFN1bW1hcnkucHVzaCh7XG4gICAgICAgICAgICAgICAga2V5OiBnZXRMb2NhbGVUZXh0KCd0YXgnKSxcbiAgICAgICAgICAgICAgICB2YWx1ZTogY2FsY3VsYXRlZENhcnQuc3VtbWFyeS50b3RhbF90YXhcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgICAgIGZvciAoY29uc3QgW2dpZnRDYXJkLCBhbW91bnQyXSBvZiBPYmplY3QuZW50cmllcyhjYWxjdWxhdGVkQ2FydC5zdW1tYXJ5LmdpZnRfY2FyZF9yZWNvcmQpKXtcbiAgICAgICAgICAgIGlmICghYW1vdW50Mikge1xuICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY2FydFN1bW1hcnkucHVzaCh7XG4gICAgICAgICAgICAgICAga2V5OiBgR2lmdCBjYXJkIC0gKCR7Z2lmdENhcmR9KWAsXG4gICAgICAgICAgICAgICAgdmFsdWU6IC1hbW91bnQyXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBjYXJ0U3VtbWFyeS5wdXNoKHtcbiAgICAgICAgICAgIGtleTogZ2V0TG9jYWxlVGV4dCgndG90YWwnKSxcbiAgICAgICAgICAgIHZhbHVlOiBjYWxjdWxhdGVkQ2FydC5zdW1tYXJ5LnRvdGFsXG4gICAgICAgIH0pO1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgY2FydFN1bW1hcnksXG4gICAgICAgICAgICBjYXJ0TWV0YVxuICAgICAgICB9O1xuICAgIH07XG59XG5jb25zdCBwZWFjaHBheWkxOG4gPSB7XG4gICAgYWRkOiB7XG4gICAgICAgICdkZS1ERSc6ICcrIEhpbnp1ZsO8Z2VuJyxcbiAgICAgICAgJ2VuLVVTJzogJysgQWRkJyxcbiAgICAgICAgJ2VzLUVTJzogJysgQWdyZWdhcicsXG4gICAgICAgIGZyOiAnKyBBam91dGVyJyxcbiAgICAgICAgaXQ6ICcrIEFnZ2l1bmdlcmUnLFxuICAgICAgICBqYTogJysg6L+95YqgJyxcbiAgICAgICAgJ3JvLVJPJzogJysgQWTEg3VnYScsXG4gICAgICAgIGFyOiAn2YrYttmK2YEgKycsXG4gICAgICAgIGNhOiAnKyBBZmVnZWl4JyxcbiAgICAgICAgJ2NzLUNaJzogJysgUMWZaWRhdCcsXG4gICAgICAgICdkYS1ESyc6ICcrIFRpbGbDuGplJyxcbiAgICAgICAgZWw6ICcrIM6gz4HOv8+DzrjOrs66zrcnLFxuICAgICAgICAnaGktSU4nOiAnKyDgpJzgpYvgpKHgpLzgpYfgpIInLFxuICAgICAgICAna28tS1InOiAnKyDstpTqsIDtlZjri6QnLFxuICAgICAgICAnbGItTFUnOiAnKyBBZGTDqWllcmVuJyxcbiAgICAgICAgJ25sLU5MJzogJysgVG9ldm9lZ2VuJyxcbiAgICAgICAgJ3B0LVBUJzogJysgQWRpY2lvbmFyJyxcbiAgICAgICAgJ3J1LVJVJzogJysg0JTQvtCx0LDQstC70Y/RgtGMJyxcbiAgICAgICAgJ3NsLVNJJzogJysgRG9kYWonLFxuICAgICAgICAnc3YtU0UnOiAnKyBMw6RnZyB0aWxsJyxcbiAgICAgICAgdGg6ICcrIOC5gOC4nuC4tOC5iOC4oScsXG4gICAgICAgIHVrOiAnKyDQlNC+0LTQsNGC0LgnLFxuICAgICAgICAnemgtQ04nOiAnKyDmt7vliqAnLFxuICAgICAgICAnemgtVFcnOiAnKyDmt7vliqAnXG4gICAgfSxcbiAgICAnZW1wdHktY2FydCc6IHtcbiAgICAgICAgJ2VuLVVTJzogJ0NhcnQgaXMgZW1wdHknLFxuICAgICAgICAnZGUtREUnOiAnS3VydmVuIGVyIHRvbScsXG4gICAgICAgICdlcy1FUyc6ICdFbCBjYXJyaXRvIGVzdGEgdmFjw61vJyxcbiAgICAgICAgZnI6ICdMZSBwYW5pZXIgZXN0IHZpZGUnLFxuICAgICAgICBpdDogJ0lsIGNhcnJlbGxvIMOoIHZ1b3RvJyxcbiAgICAgICAgamE6ICfjgqvjg7zjg4jjgYznqbrjgafjgZknLFxuICAgICAgICAncm8tUk8nOiAnQ2/ImXVsIGVzdGUgZ29sJyxcbiAgICAgICAgYXI6ICfYp9mE2KjYt9in2YLZhyDYrtin2YTZitmHJyxcbiAgICAgICAgY2E6ICdFbCBjYXJyZXTDsyBlc3TDoCBidWl0JyxcbiAgICAgICAgJ2NzLUNaJzogJ0tvxaHDrWsgamUgcHLDoXpkbsO9JyxcbiAgICAgICAgJ2RhLURLJzogJ0tvxaFhcmljYSBqZSBwcmF6bmEnLFxuICAgICAgICBlbDogJ86kzr8gzrrOsc67zqzOuM65IM61zq/Ovc6xzrkgzqzOtM61zrnOvycsXG4gICAgICAgICdoaS1JTic6ICfgpJXgpL7gpLDgpY3gpJ8g4KSW4KS+4KSy4KWAIOCkueCliCcsXG4gICAgICAgICdrby1LUic6ICfsnqXrsJTqtazri4jqsIAg67mE7Ja0IOyeiOyKteuLiOuLpC4nLFxuICAgICAgICAnbGItTFUnOiAnV2VlbmNoZW4gYXNzIGVpZGVsJyxcbiAgICAgICAgJ25sLU5MJzogJ1dpbmtlbHdhZ2VuIGlzIGxlZWcnLFxuICAgICAgICAncHQtUFQnOiAnY2FycmluaG8gZXN0YSB2YXppbycsXG4gICAgICAgICdydS1SVSc6ICfQmtC+0YDQt9C40L3QsCDQv9GD0YHRgtCwJyxcbiAgICAgICAgJ3NsLVNJJzogJ0tvxaFhcmljYSBqZSBwcmF6bmEnLFxuICAgICAgICAnc3YtU0UnOiAnVmFydWtvcmdlbiDDpHIgdG9tJyxcbiAgICAgICAgdGg6ICfguKPguJbguYDguILguYfguJnguKfguYjguLLguIfguYDguJvguKXguYjguLInLFxuICAgICAgICB1azogJ9Ca0L7RiNC40Log0L/QvtGA0L7QttC90ZbQuScsXG4gICAgICAgICd6aC1DTic6ICfotK3nianovabmmK/nqbrnmoQnLFxuICAgICAgICAnemgtVFcnOiAn6LSt54mp6L2m5piv56m655qEJ1xuICAgIH0sXG4gICAgJ2xpbmtlZC1wcm9kdWN0cy10aXRsZSc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0RhcyBrw7ZubnRlIGRpciBhdWNoIGdlZmFsbGVuLi4uJyxcbiAgICAgICAgJ2VuLVVTJzogJ1lvdSBtaWdodCBhbHNvIGxpa2UuLi4nLFxuICAgICAgICAnZXMtRVMnOiAnVGFtYmnDqW4gcG9kcsOtYSBndXN0YXJ0ZS4uLicsXG4gICAgICAgIGZyOiAndm91cyBwb3VycmlleiBhdXNzaSBhaW1lci4uLicsXG4gICAgICAgIGl0OiAnUG90cmViYmUgcGlhY2VydGkgYW5jaGUuLi4nLFxuICAgICAgICBqYTogJ+OBguOBquOBn+OBr+OBiuOBneOCieOBj+OBneOCjOOCguWlveOBjeOBp+OBl+OCh+OBhi4uLicsXG4gICAgICAgICdyby1STyc6ICdTLWFyIHB1dGVhIHNhLXRpIHBsYWNhIHNpLi4uJyxcbiAgICAgICAgYXI6ICfZgtivINmK2LnYrNio2YMg2KfZiti22KcnLFxuICAgICAgICBjYTogJ3BvdHNlciB0YW1iw6kgdFxcJ2FncmFkYS4uLicsXG4gICAgICAgICdjcy1DWic6ICdtb2hsbyBieSBzZSB2w6FtIGzDrWJpdC4uLicsXG4gICAgICAgICdkYS1ESyc6ICdEdSBrYW4gb2dzw6UgbGlkZS4uLicsXG4gICAgICAgIGVsOiAnzpzPgM6/z4HOtc6vIM61z4DOr8+DzrfPgiDOvc6xIM+DzrHPgiDOsc+Bzq3Pg861zrkuLi4nLFxuICAgICAgICAnaGktSU4nOiAn4KS24KS+4KSv4KSmIOCkpOClgeCkruCljeCkueClhyDgpK/gpLkg4KSt4KWAIOCkheCkmuCljeCkm+CkviDgpLLgpJfgpYcuLi4nLFxuICAgICAgICAna28tS1InOiAn64u57Iug7J2AIOuYkO2VnCDsoovslYTtlaAg7IiY64+EIOyeiOyKteuLiOuLpC4uLicsXG4gICAgICAgICdsYi1MVSc6ICdEaXIga8Orbm50IG9jaCBnw6RyZW4uLi4nLFxuICAgICAgICAnbmwtTkwnOiAnTWlzc2NoaWVuIHZpbmQgamUgZGl0IG9vayBsZXVrLi4uJyxcbiAgICAgICAgJ3B0LVBUJzogJ3ZvY8OqIHBvZGUgZ29zdGFyIHRhbWLDqW0uLi4nLFxuICAgICAgICAncnUtUlUnOiAn0JLQsNC8INGC0LDQutC20LUg0LzQvtC20LXRgiDQv9C+0L3RgNCw0LLQuNGC0YzRgdGPLi4uJyxcbiAgICAgICAgJ3NsLVNJJzogJ01vcmRhIHZhbSBibyB2xaFlxI0gdHVkaS4uLicsXG4gICAgICAgICdzdi1TRSc6ICdEdSBrYW5za2Ugb2Nrc8OlIGdpbGxhci4uLicsXG4gICAgICAgIHRoOiAn4LiE4Li44LiT4Lit4Liy4LiI4LiK4Lit4LiaLi4uJyxcbiAgICAgICAgdWs6ICfQktCw0Lwg0YLQsNC60L7QtiDQvNC+0LbQtSDRgdC/0L7QtNC+0LHQsNGC0LjRgdGPLi4uJyxcbiAgICAgICAgJ3poLUNOJzogJ+S9oOWPr+iDvei/mOWWnOasoi4uLicsXG4gICAgICAgICd6aC1UVyc6ICfkvaDlj6/og73pgoTllpzmraEuLi4nXG4gICAgfSxcbiAgICB2ZXJpZmllZDoge1xuICAgICAgICAnZGUtREUnOiAnVmVyaWZpemllcnQnLFxuICAgICAgICAnZW4tVVMnOiAnVmVyaWZpZWQnLFxuICAgICAgICAnZXMtRVMnOiAnVmVyaWZpY2FkbycsXG4gICAgICAgIGZyOiAnVsOpcmlmacOpJyxcbiAgICAgICAgaXQ6ICd2ZXJpZmljYXRvJyxcbiAgICAgICAgamE6ICfnorroqo3muIjjgb8nLFxuICAgICAgICAncm8tUk8nOiAnVmVyaWZpY2F0JyxcbiAgICAgICAgYXI6ICfYqtmFINin2YTYqtit2YLZgicsXG4gICAgICAgIGNhOiAnVmVyaWZpY2F0JyxcbiAgICAgICAgJ2NzLUNaJzogJ092xJvFmWVubycsXG4gICAgICAgICdkYS1ESyc6ICdWZXJpZmljZXJldCcsXG4gICAgICAgIGVsOiAnzpXPgM6xzrvOt864zrXPhc68zq3Ovc6/JyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkuOCkpOCljeCkr+CkvuCkquCkv+CkpCcsXG4gICAgICAgICdrby1LUic6ICftmZXsnbjrkKgnLFxuICAgICAgICAnbGItTFUnOiAnVmVyaWZpesOpaWVydCcsXG4gICAgICAgICdubC1OTCc6ICdHZXZlcmlmaWVlcmQnLFxuICAgICAgICAncHQtUFQnOiAnVmVyaWZpY2FkYScsXG4gICAgICAgICdydS1SVSc6ICfQn9GA0L7QstC10YDQtdC90L4nLFxuICAgICAgICAnc2wtU0knOiAnUHJldmVyamVubycsXG4gICAgICAgICdzdi1TRSc6ICdWZXJpZmllcmFkJyxcbiAgICAgICAgdGg6ICfguJXguKPguKfguIjguKrguK3guJrguYHguKXguYnguKcnLFxuICAgICAgICB1azogJ9Cf0LXRgNC10LLRltGA0LXQvdC+JyxcbiAgICAgICAgJ3poLUNOJzogJ+W3sumqjOivgScsXG4gICAgICAgICd6aC1UVyc6ICflt7LpqZforYknXG4gICAgfSxcbiAgICAnY291cG9uLW9wdGlvbic6IHtcbiAgICAgICAgJ2RlLURFJzogJysgRUlORU4gR1VUU0NIRUlOIENPREUgSElOWlVGw5xHRU4nLFxuICAgICAgICAnZW4tVVMnOiAnKyBBREQgQSBDT1VQT04gQ09ERScsXG4gICAgICAgICdlcy1FUyc6ICcrIEHDkUFESVIgVU4gQ8OTRElHTyBERSBDVVDDk04nLFxuICAgICAgICBmcjogJysgQUpPVVRFUiBVTiBDT0RFIENPVVBPTicsXG4gICAgICAgIGl0OiAnKyBBR0dJVU5HSSBVTiBDT0RJQ0UgQ09VUE9OJyxcbiAgICAgICAgamE6ICcrIOOCr+ODvOODneODs+OCs+ODvOODieOCkui/veWKoCcsXG4gICAgICAgICdyby1STyc6ICcrIEFExIJVR0HImkkgVU4gQ09EIERFIENVUE9OJyxcbiAgICAgICAgYXI6ICfYo9i22YEg2LHZhdiyINin2YTZgtiz2YrZhdipJyxcbiAgICAgICAgY2E6ICdBZmVnaXUgdW4gY29kaSBkZSBjdXDDsycsXG4gICAgICAgICdjcy1DWic6ICdQxZlpZGVqdGUga8OzZCBrdXDDs251JyxcbiAgICAgICAgJ2RhLURLJzogJ1RpbGbDuGogZW4ga3Vwb25rb2RlJyxcbiAgICAgICAgZWw6ICfOoM+Bzr/Pg864zq3Pg8+EzrUgzq3Ovc6xzr0gzrrPic60zrnOus+MIM66zr/Phc+Azr/Ovc65zr/PjScsXG4gICAgICAgICdoaS1JTic6ICfgpJXgpYLgpKrgpKgg4KSV4KWL4KShIOCknOCli+CkoeCkvOClh+CkgicsXG4gICAgICAgICdrby1LUic6ICfsv6Dtj7Ag7L2U65OcIOy2lOqwgCcsXG4gICAgICAgICdsYi1MVSc6ICdGw7zDvGd0IGUgQ291cG9uIENvZGUgZGVyYsOkaScsXG4gICAgICAgICdubC1OTCc6ICdWb2VnIGVlbiBjb3Vwb25jb2RlIHRvZScsXG4gICAgICAgICdwdC1QVCc6ICdBZGljaW9uYXIgdW0gY8OzZGlnbyBkZSBjdXBvbScsXG4gICAgICAgICdydS1SVSc6ICfQlNC+0LHQsNCy0YzRgtC1INC60L7QtCDQutGD0L/QvtC90LAnLFxuICAgICAgICAnc2wtU0knOiAnRG9kYWp0ZSBrb2RvIGt1cG9uYScsXG4gICAgICAgICdzdi1TRSc6ICdMw6RnZyB0aWxsIGVuIGt1cG9uZ2tvZCcsXG4gICAgICAgIHRoOiAn4LmA4Lie4Li04LmI4Lih4Lij4Lir4Lix4Liq4LiE4Li54Lib4Lit4LiHJyxcbiAgICAgICAgdWs6ICfQlNC+0LTQsNC50YLQtSDQutC+0LQg0LrRg9C/0L7QvdCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+a3u+WKoOS8mOaDoOWIuOS7o+eggScsXG4gICAgICAgICd6aC1UVyc6ICfmt7vliqDlhKrmg6DliLjku6PnorwnXG4gICAgfSxcbiAgICAnZXJyb3Itb2NjdXJyZWQnOiB7XG4gICAgICAgICdkZS1ERSc6ICdFbnRzY2h1bGRpZ3VuZywgZXR3YXMgaXN0IHNjaGllZiBnZWxhdWZlbi4gQml0dGUgYWt0dWFsaXNpZXJlbiBTaWUgZGllIFNlaXRlIHVuZCB2ZXJzdWNoZW4gU2llIGVzIGVybmV1dC4nLFxuICAgICAgICAnZW4tVVMnOiAnU29ycnksIHNvbWV0aGluZyB3ZW50IHdyb25nLiBQbGVhc2UgcmVmcmVzaCB0aGUgcGFnZSBhbmQgdHJ5IGFnYWluLicsXG4gICAgICAgICdlcy1FUyc6ICdQZXJkw7NuLCBhbGdvIHNhbGnDsyBtYWwuIEFjdHVhbGljZSBsYSBww6FnaW5hIHkgdnVlbHZhIGEgaW50ZW50YXJsby4nLFxuICAgICAgICBmcjogJ0TDqXNvbMOpLCBxdWVscXVlIGNob3NlIHNcXCdlc3QgbWFsIHBhc3PDqS4gVmV1aWxsZXogYWN0dWFsaXNlciBsYSBwYWdlIGV0IHLDqWVzc2F5ZXIuJyxcbiAgICAgICAgaXQ6ICdTY3VzYSwgcXVhbGNvc2Egw6ggYW5kYXRvIHN0b3J0by4gUGVyZmF2b3JlIHJpY2FyaWNhIGxhIHBhZ2luYSBlIHJpcHJvdmEuJyxcbiAgICAgICAgJ3JvLVJPJzogJ1NjdXplLCBjZXZhIGEgbWVycyBncmXImWl0LiBBY3R1YWxpemHIm2kgcGFnaW5hIMiZaSDDrm5jZXJjYcibaSBkaW4gbm91LicsXG4gICAgICAgIGFyOiAn2LnYsNix2KfYjCDZh9mG2KfZgyDYrti32KMg2YXYpy4g2YrYsdis2Ykg2KrYrdiv2YrYqyDYp9mE2LXZgdit2Kkg2YjYrdin2YjZhCDZhdix2Kkg2KPYrtix2YkuJyxcbiAgICAgICAgY2E6ICdIbyBzZW50aW0sIGFsZ3VuYSBjb3NhIGhhIGFuYXQgbWFsYW1lbnQuIEFjdHVhbGl0emV1IGxhIHDDoGdpbmEgaSB0b3JuZXUtaG8gYSBwcm92YXIuJyxcbiAgICAgICAgJ2NzLUNaJzogJ1Byb21pxYgsIG7Em2NvIHNlIHBva2F6aWxvLiBPYm5vdnRlIHN0csOhbmt1IGEgemt1c3RlIHRvIHpub3Z1LicsXG4gICAgICAgICdkYS1ESyc6ICdVbmRza3lsZCwgbm9nZXQgZ2lrIGdhbHQuIE9wZGF0ZXIgc2lkZW4sIG9nIHByw7h2IGlnZW4uJyxcbiAgICAgICAgZWw6ICfOo8+FzrPOvc+OzrzOtywgzrrOrM+Ezrkgz4DOrs6zzrUgz4PPhM+BzrHOss6sLiDOkc69zrHOvc61z47Pg8+EzrUgz4TOtyDPg861zrvOr860zrEgzrrOsc65IM60zr/Ous65zrzOrM+Dz4TOtSDOvs6xzr3OrC4nLFxuICAgICAgICAnaGktSU4nOiAn4KSV4KWN4KS34KSu4KS+IOCkleCksOClh+Ckgiwg4KSV4KWB4KSbIOCkl+CksuCkpCDgpLngpYsg4KSX4KSv4KS+4KWkIOCkquClg+Ckt+CljeCkoCDgpJXgpYsg4KSw4KWA4KSr4KWN4KSw4KWH4KS2IOCkleCksOClh+CkgiDgpJTgpLAg4KSq4KWB4KSoOiDgpKrgpY3gpLDgpK/gpL7gpLgg4KSV4KSw4KWH4KSC4KWkJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yjhOyGoe2VqeuLiOuLpC4g66y47KCc6rCAIOuwnOyDne2WiOyKteuLiOuLpC4g7Y6Y7J207KeA66W8IOyDiOuhnOqzoOy5qO2VmOqzoCDri6Tsi5wg7Iuc64+E7ZWY7Iut7Iuc7JikLicsXG4gICAgICAgICdsYi1MVSc6ICdFbnRzY2jDq2xsZWd0LCBlcHBlcyBhc3MgZmFsc2NoIGdhYW5nLiBFcmZyw6tzY2h0IHcuZS5nLiBkXFwnU8OkaXQgYSBwcm9iw6lpZXJ0IG5hY2ggZW5nIEvDqWllci4nLFxuICAgICAgICAnbmwtTkwnOiAnU29ycnksIGVyIGdpbmcgaWV0cyBtaXMuIFZlcnZlcnMgZGUgcGFnaW5hIGVuIHByb2JlZXIgaGV0IG9wbmlldXcuJyxcbiAgICAgICAgJ3B0LVBUJzogJ0Rlc2N1bHBlLCBhbGdvIGRldSBlcnJhZG8uIEF0dWFsaXplIGEgcMOhZ2luYSBlIHRlbnRlIG5vdmFtZW50ZS4nLFxuICAgICAgICAncnUtUlUnOiAn0JjQt9Cy0LjQvdC40YLQtSwg0YfRgtC+LdGC0L4g0L/QvtGI0LvQviDQvdC1INGC0LDQui4g0J7QsdC90L7QstC40YLQtSDRgdGC0YDQsNC90LjRhtGDINC4INC/0L7Qv9GA0L7QsdGD0LnRgtC1INC10YnQtSDRgNCw0LcuJyxcbiAgICAgICAgJ3NsLVNJJzogJ09wcm9zdGl0ZSwgbmVrYWogamUgxaFsbyBuYXJvYmUuIE9zdmXFvml0ZSBzdHJhbiBpbiBwb3NrdXNpdGUgem5vdmEuJyxcbiAgICAgICAgJ3N2LVNFJzogJ0bDtnJsw6V0LCBuw6Vnb3QgZ2ljayBmZWwuIFVwcGRhdGVyYSBzaWRhbiBvY2ggZsO2cnPDtmsgaWdlbi4nLFxuICAgICAgICB0aDogJ+C4guC4reC5guC4l+C4qeC4oeC4teC4muC4suC4h+C4reC4ouC5iOC4suC4h+C4nOC4tOC4lOC4nuC4peC4suC4lC4g4LmC4Lib4Lij4LiU4Lij4Li14LmA4Lif4Lij4LiK4Lir4LiZ4LmJ4Liy4LmB4Lil4LmJ4Lin4Lil4Lit4LiH4Lit4Li14LiB4LiE4Lij4Lix4LmJ4LiHJyxcbiAgICAgICAgdWs6ICfQktC40LHQsNGH0YLQtSwg0YnQvtGB0Ywg0L/RltGI0LvQviDQvdC1INGC0LDQui4g0J7QvdC+0LLRltGC0Ywg0YHRgtC+0YDRltC90LrRgyDRgtCwINC/0L7QstGC0L7RgNGW0YLRjCDRgdC/0YDQvtCx0YMuJyxcbiAgICAgICAgJ3poLUNOJzogJ+aKseatie+8jOWHuuS6huS4gOS6m+mXrumimOOAgiDor7fliLfmlrDpobXpnaLlubbph43or5XjgIInLFxuICAgICAgICAnemgtVFcnOiAn5oqx5q2J77yM5Ye65LqG5LiA5Lqb5ZWP6aGM44CCIOiri+WIt+aWsOmggemdouS4pumHjeippuOAgidcbiAgICB9LFxuICAgICdnaWZ0LW9wdGlvbic6IHtcbiAgICAgICAgJ2RlLURFJzogJysgR0VTQ0hFTktLQVJURS9HRVNDSEVOSy1LUkVESVQgRUlOTMOWU0VOJyxcbiAgICAgICAgJ2VuLVVTJzogJysgUkVERUVNIEdJRlQgQ0FSRC9TVE9SRSBDUkVESVQnLFxuICAgICAgICAnZXMtRVMnOiAnKyBDQU5KRUFSIFRBUkpFVEEgREUgUkVHQUxPL0NSw4lESVRPIERFIFRJRU5EQScsXG4gICAgICAgIGZyOiAnKyDDiUNIQU5HRVIgTEEgQ0FSVEUtQ0FERUFVL0xFIENSw4lESVQgRFUgTUFHQVNJTicsXG4gICAgICAgIGl0OiAnKyBVVElMSVpaQSBDQVJUQSBSRUdBTE8vQ1JFRElUTyBORUdPWklPJyxcbiAgICAgICAgamE6ICcrIOOCruODleODiOOCq+ODvOODiS/jgrnjg4jjgqLjgq/jg6zjgrjjg4Pjg4jjgpLliKnnlKjjgZnjgosnLFxuICAgICAgICAncm8tUk8nOiAnKyBSxIJzY3VtcMSDcmHIm2kgY2FyZHVsL2NhZG91bCBkZSBjcmVkaXQgY2Fkb3UnLFxuICAgICAgICBhcjogJ9in2LPYqtix2K/Yp9ivINio2LfYp9mC2Kkg2KfZhNmH2K/Yp9mK2KcgLyDYsdi12YrYryDYp9mE2YXYqtis2LEnLFxuICAgICAgICBjYTogJ0Jlc2NhbnZpYSBlbCBjcsOoZGl0IGRlIGxhIHRhcmdldGEgcmVnYWwgbyBkZSBsYSBib3RpZ2EnLFxuICAgICAgICAnY3MtQ1onOiAnVXBsYXRuxJt0ZSBkw6Fya292b3Uga2FydHUva3JlZGl0IG9iY2hvZHUnLFxuICAgICAgICAnZGEtREsnOiAnSW5kbMO4cyBnYXZla29ydC9idXRpa3NrcmVkaXQnLFxuICAgICAgICBlbDogJ86Vzr7Osc+BzrPPhc+Bz47Pg8+EzrUgz4DOuc+Dz4TPic+EzrnOus6uIM66zqzPgc+EzrEgzrTPjs+Bzr/PhS/Ous6xz4TOrM+Dz4TOt868zrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSJ4KSq4KS54KS+4KSwIOCkleCkvuCksOCljeCkoS/gpLjgpY3gpJ/gpYvgpLAg4KSV4KWN4KSw4KWH4KSh4KS/4KSfIOCksOCkv+CkoeClgOCkriDgpJXgpLDgpYfgpIInLFxuICAgICAgICAna28tS1InOiAn6riw7ZSE7Yq4IOy5tOuTnC/siqTthqDslrQg7YGs66CI65SnIOyCrOyaqScsXG4gICAgICAgICdsYi1MVSc6ICdFcmzDqWlzIEthZGRva2FhcnQvR2VzY2jDpGZ0c2tyZWRpdHQnLFxuICAgICAgICAnbmwtTkwnOiAnQ2FkZWF1a2FhcnQvd2lua2VsdGVnb2VkIGlud2lzc2VsZW4nLFxuICAgICAgICAncHQtUFQnOiAnUmVzZ2F0YXIgY2FydMOjby1wcmVzZW50ZSAvIGNyw6lkaXRvIGRhIGxvamEnLFxuICAgICAgICAncnUtUlUnOiAn0J/QvtCz0LDRgdC40YLRjCDQv9C+0LTQsNGA0L7Rh9C90YPRjiDQutCw0YDRgtGDIC8g0LrRgNC10LTQuNGCINC80LDQs9Cw0LfQuNC90LAnLFxuICAgICAgICAnc2wtU0knOiAnVW5vdsSNaXRlIGRhcmlsbm8ga2FydGljby9kb2Jyb2ltZXRqZSB2IHRyZ292aW5pJyxcbiAgICAgICAgJ3N2LVNFJzogJ0zDtnMgaW4gcHJlc2VudGtvcnQvYnV0aWtza3JlZGl0JyxcbiAgICAgICAgdGg6ICfguYHguKXguIHguJrguLHguJXguKPguILguK3guIfguILguKfguLHguI0v4LmA4LiE4Lij4LiU4Li04LiV4Lij4LmJ4Liy4LiZ4LiE4LmJ4LiyJyxcbiAgICAgICAgdWs6ICfQkNC60YLQuNCy0YPQudGC0LUg0L/QvtC00LDRgNGD0L3QutC+0LLRgyDQutCw0YDRgtC60YMv0LrRgNC10LTQuNGCINGDINC80LDQs9Cw0LfQuNC90ZYnLFxuICAgICAgICAnemgtQ04nOiAn5YWR5o2i56S85ZOB5Y2hL+WVhuW6l+S/oeeUqCcsXG4gICAgICAgICd6aC1UVyc6ICflhYzmj5vnpq7lk4HljaEv5ZWG5bqX5L+h55SoJ1xuICAgIH0sXG4gICAgJ3NlbmQtdG8nOiB7XG4gICAgICAgICdkZS1ERSc6ICdTZW5kZW4gYW4nLFxuICAgICAgICAnZW4tVVMnOiAnU2VuZCB0bycsXG4gICAgICAgICdlcy1FUyc6ICdFbnZpYXIgYScsXG4gICAgICAgIGZyOiAnRW52b3llciDDoCcsXG4gICAgICAgIGl0OiAnSW52aWFyZSBhJyxcbiAgICAgICAgamE6ICfpgIHkv6HlhYgnLFxuICAgICAgICAncm8tUk8nOiAnVHJpbWl0ZSBjYXRyZScsXG4gICAgICAgIGFyOiAn2KfYsdiz2YQg2KXZhNmJJyxcbiAgICAgICAgY2E6ICdFbnZpYSBhJyxcbiAgICAgICAgJ2NzLUNaJzogJ1Bvc2xhdCBrb211JyxcbiAgICAgICAgJ2RhLURLJzogJ1NlbmQgdGlsJyxcbiAgICAgICAgZWw6ICfOo8+Ezq3Ou869z4kgz4POtScsXG4gICAgICAgICdoaS1JTic6ICfgpK3gpYfgpJzgpKjgpL4nLFxuICAgICAgICAna28tS1InOiAn67O064K06riwJyxcbiAgICAgICAgJ2xiLUxVJzogJ1NjaMOpY2tlbicsXG4gICAgICAgICdubC1OTCc6ICdWZXJ6ZW5kZW4gbmFhcicsXG4gICAgICAgICdwdC1QVCc6ICdFbnZpYXIgcGFyYScsXG4gICAgICAgICdydS1SVSc6ICfQntGC0L/RgNCw0LLQuNGC0YwnLFxuICAgICAgICAnc2wtU0knOiAnUG/FoWxqaScsXG4gICAgICAgICdzdi1TRSc6ICdTa2lja2EgdGlsbCcsXG4gICAgICAgIHRoOiAn4Liq4LmI4LiH4LiW4Li24LiHJyxcbiAgICAgICAgdWs6ICfQktGW0LTQv9GA0LDQstC40YLQuCcsXG4gICAgICAgICd6aC1DTic6ICflj5Hnu5knLFxuICAgICAgICAnemgtVFcnOiAn55m857WmJ1xuICAgIH0sXG4gICAgJ215LW9yZGVyJzoge1xuICAgICAgICAnZGUtREUnOiAnTWVpbmUgQmVzdGVsbHVuZycsXG4gICAgICAgICdlbi1VUyc6ICdNeSBvcmRlcicsXG4gICAgICAgICdlcy1FUyc6ICdNaSBwZWRpZG8nLFxuICAgICAgICBmcjogJ01hIGNvbW1hbmRlJyxcbiAgICAgICAgaXQ6ICdJbCBtaW8gb3JkaW5lJyxcbiAgICAgICAgamE6ICfms6jmlocnLFxuICAgICAgICAncm8tUk8nOiAnQ29tYW5kYSBtZWEnLFxuICAgICAgICBhcjogJ9i32YTYqNmKJyxcbiAgICAgICAgY2E6ICdFbCBtZXUgb3JkcmUnLFxuICAgICAgICAnY3MtQ1onOiAnTW9qZSBvYmplZG7DoXZrYScsXG4gICAgICAgICdkYS1ESyc6ICdNaW4gYmVzdGlsbGluZycsXG4gICAgICAgIGVsOiAnzpcgz4DOsc+BzrHOs86zzrXOu86vzrEgzrzOv8+FJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkruClh+CksOClhyDgpIbgpKbgpYfgpLYnLFxuICAgICAgICAna28tS1InOiAn64K0IOyjvOusuCcsXG4gICAgICAgICdsYi1MVSc6ICdNZW5nIEJlc3RlbGx1bmcnLFxuICAgICAgICAnbmwtTkwnOiAnTWlqbiBiZXN0ZWxsaW5nJyxcbiAgICAgICAgJ3B0LVBUJzogJ01ldSBwZWRpZG8nLFxuICAgICAgICAncnUtUlUnOiAn0JzQvtC5INC30LDQutCw0LcnLFxuICAgICAgICAnc2wtU0knOiAnTW9qIHVrYXonLFxuICAgICAgICAnc3YtU0UnOiAnTWluIG9yZGVyJyxcbiAgICAgICAgdGg6ICfguITguLPguKrguLHguYjguIfguILguK3guIfguInguLHguJknLFxuICAgICAgICB1azogJ9Cc0L7RlCDQt9Cw0LzQvtCy0LvQtdC90L3RjycsXG4gICAgICAgICd6aC1DTic6ICfmiJHnmoTorqLljZUnLFxuICAgICAgICAnemgtVFcnOiAn5oiR55qE6KiC5ZauJ1xuICAgIH0sXG4gICAgJ3JlYWR5LXRvLWNoZWNrLW91dCc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0JlcmVpdCB6dW0gQXVzY2hlY2tlbj8nLFxuICAgICAgICAnZW4tVVMnOiAnUmVhZHkgdG8gY2hlY2sgb3V0PycsXG4gICAgICAgICdlcy1FUyc6ICfCv0xpc3RvIHBhcmEgc2FsaXI/JyxcbiAgICAgICAgZnI6ICdQcsOqdCDDoCB2w6lyaWZpZXIgPycsXG4gICAgICAgIGl0OiAnUHJvbnRvIHBlciBpbCBjaGVjay1vdXQ/JyxcbiAgICAgICAgamE6ICfmlK/miZXjgYTjgpLjgZnjgovmupblgpnjga/jgafjgY3jgb7jgZfjgZ/jgYs/JyxcbiAgICAgICAgJ3JvLVJPJzogJ1N1bnRlyJtpIGdhdGEgc8SDIHZpeml0YcibaT8nLFxuICAgICAgICBhcjogJ9mH2YQg2KPZhtiqINis2KfZh9iyINmE2YTYqtiz2KzZitmE2J8nLFxuICAgICAgICBjYTogJ0EgcHVudCBwZXIgZmVyIGVsIGNoZWNrLW91dD8nLFxuICAgICAgICAnY3MtQ1onOiAnSnN0ZSBwxZlpcHJhdmVuaSBzZSBwb2TDrXZhdD8nLFxuICAgICAgICAnZGEtREsnOiAnS2xhciB0aWwgYXQgdGpla2tlIHVkPycsXG4gICAgICAgIGVsOiAnzpXOr8+Dz4TOtSDOrc+Ezr/Ouc68zr/OuSDOs865zrEgY2hlY2sgb3V0OycsXG4gICAgICAgICdoaS1JTic6ICfgpJrgpYfgpJUg4KSG4KSJ4KSfIOCkleCksOCkqOClhyDgpJXgpYcg4KSy4KS/4KSPIOCkpOCliOCkr+CkvuCksCDgpLngpYjgpII/JyxcbiAgICAgICAgJ2tvLUtSJzogJ+yytO2BrOyVhOybg+2VoCDspIDruYTqsIAg65CY7IWo64KY7JqUPycsXG4gICAgICAgICdsYi1MVSc6ICdQcmV0dCBmaXIgemUgY2hlY2tlbj8nLFxuICAgICAgICAnbmwtTkwnOiAnS2xhYXIgb20gdWl0IHRlIGNoZWNrZW4/JyxcbiAgICAgICAgJ3B0LVBUJzogJ1Byb250byBwYXJhIGZpbmFsaXphciBhIGNvbXByYT8nLFxuICAgICAgICAncnUtUlUnOiAn0JPQvtGC0L7QstGLINC/0YDQvtCy0LXRgNC40YLRjD8nLFxuICAgICAgICAnc2wtU0knOiAnU3RlIHByaXByYXZsamVuaSBuYSBvZGphdm8/JyxcbiAgICAgICAgJ3N2LVNFJzogJ0tsYXIgYXR0IGNoZWNrYSB1dD8nLFxuICAgICAgICB0aDogJ+C4nuC4o+C5ieC4reC4oeC4l+C4teC5iOC4iOC4sOC5gOC4iuC5h+C4hOC5gOC4reC4suC4l+C5jD8nLFxuICAgICAgICB1azogJ9CT0L7RgtC+0LLRliDQv9C10YDQtdCy0ZbRgNC40YLQuD8nLFxuICAgICAgICAnemgtQ04nOiAn5YeG5aSH6YCA5oi/5LqG5ZCX77yfJyxcbiAgICAgICAgJ3poLVRXJzogJ+a6luWCmemAgOaIv+S6huWXju+8nydcbiAgICB9LFxuICAgIGluZm86IHtcbiAgICAgICAgJ2RlLURFJzogJ0luZm9ybWF0aW9uJyxcbiAgICAgICAgJ2VuLVVTJzogJ0luZm8nLFxuICAgICAgICAnZXMtRVMnOiAnSW5mb3JtYWNpw7NuJyxcbiAgICAgICAgZnI6ICdJbmZvcm1hdGlvbicsXG4gICAgICAgIGl0OiAnSW5mb3JtYXppb25pJyxcbiAgICAgICAgamE6ICfmg4XloLEnLFxuICAgICAgICAncm8tUk8nOiAnSW5mb3JtYcibaWUnLFxuICAgICAgICBhcjogJ9mF2LnZhNmI2YXYqScsXG4gICAgICAgIGNhOiAnSW5mb3JtYWNpw7MnLFxuICAgICAgICAnY3MtQ1onOiAnSW5mb3JtYWNlJyxcbiAgICAgICAgJ2RhLURLJzogJ0luZm9ybWF0aW9uJyxcbiAgICAgICAgZWw6ICfOoM67zrfPgc6/z4bOv8+Bzq/Otc+CJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CknOCkvuCkqOCkleCkvuCksOClgCcsXG4gICAgICAgICdrby1LUic6ICfsoJXrs7QnLFxuICAgICAgICAnbGItTFUnOiAnSW5mb3JtYXRpb3VuZW4nLFxuICAgICAgICAnbmwtTkwnOiAnSW5mb3JtYXRpZScsXG4gICAgICAgICdwdC1QVCc6ICdFbSBmb3JtYcOnw6NvJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CY0L3RhNC+0YDQvNCw0YbQuNGPJyxcbiAgICAgICAgJ3NsLVNJJzogJ0luZm9ybWFjaWplJyxcbiAgICAgICAgJ3N2LVNFJzogJ0luZm9ybWF0aW9uJyxcbiAgICAgICAgdGg6ICfguILguYnguK3guKHguLnguKUnLFxuICAgICAgICB1azogJ9CG0L3RhNC+0YDQvNCw0YbRltGPJyxcbiAgICAgICAgJ3poLUNOJzogJ+S/oeaBrycsXG4gICAgICAgICd6aC1UVyc6ICfkv6Hmga8nXG4gICAgfSxcbiAgICBwYXltZW50OiB7XG4gICAgICAgICdkZS1ERSc6ICdaYWhsdW5nJyxcbiAgICAgICAgJ2VuLVVTJzogJ1BheW1lbnQnLFxuICAgICAgICAnZXMtRVMnOiAnUGFnbycsXG4gICAgICAgIGZyOiAnUGFpZW1lbnQnLFxuICAgICAgICBpdDogJ1BhZ2FtZW50bycsXG4gICAgICAgIGphOiAn5pSv5omV44GEJyxcbiAgICAgICAgJ3JvLVJPJzogJ1BsYXTEgycsXG4gICAgICAgIGFyOiAn2YLYs9i3JyxcbiAgICAgICAgY2E6ICdQYWdhbWVudCcsXG4gICAgICAgICdjcy1DWic6ICdacMWvc29iIHBsYXRieScsXG4gICAgICAgICdkYS1ESyc6ICdCZXRhbGluZycsXG4gICAgICAgIGVsOiAnzqDOu863z4HPic68zq4nLFxuICAgICAgICAnaGktSU4nOiAn4KSt4KWB4KSX4KSk4KS+4KSoJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yngOu2iCcsXG4gICAgICAgICdsYi1MVSc6ICdCZXp1ZWxlbicsXG4gICAgICAgICdubC1OTCc6ICdCZXRhbGluZycsXG4gICAgICAgICdwdC1QVCc6ICdQYWdhbWVudG8nLFxuICAgICAgICAncnUtUlUnOiAn0J7Qv9C70LDRgtCwJyxcbiAgICAgICAgJ3NsLVNJJzogJ1BsYcSNaWxvJyxcbiAgICAgICAgJ3N2LVNFJzogJ0JldGFsbmluZycsXG4gICAgICAgIHRoOiAn4LiB4Liy4Lij4LiK4Liz4Lij4Liw4LmA4LiH4Li04LiZJyxcbiAgICAgICAgdWs6ICfQntC/0LvQsNGC0LAnLFxuICAgICAgICAnemgtQ04nOiAn5pSv5LuYJyxcbiAgICAgICAgJ3poLVRXJzogJ+aUr+S7mCdcbiAgICB9LFxuICAgIHBlcnNvbmFsOiB7XG4gICAgICAgICdkZS1ERSc6ICdQZXJzw7ZubGljaCcsXG4gICAgICAgICdlbi1VUyc6ICdQZXJzb25hbCcsXG4gICAgICAgICdlcy1FUyc6ICdQZXJzb25hbCcsXG4gICAgICAgIGZyOiAnQ29vcmRvbm7DqWVzJyxcbiAgICAgICAgaXQ6ICdQZXJzb25hbGUnLFxuICAgICAgICBqYTogJ+WAi+S6uicsXG4gICAgICAgICdyby1STyc6ICdQZXJzb25hbCcsXG4gICAgICAgIGFyOiAn2LTYrti12YonLFxuICAgICAgICBjYTogJ1BlcnNvbmFsJyxcbiAgICAgICAgJ2NzLUNaJzogJ09zb2Juw60nLFxuICAgICAgICAnZGEtREsnOiAnUGVyc29ubGlnJyxcbiAgICAgICAgZWw6ICfOoM+Bzr/Pg8+Jz4DOuc66z4zPgicsXG4gICAgICAgICdoaS1JTic6ICfgpKjgpL/gpJzgpYAnLFxuICAgICAgICAna28tS1InOiAn6rCc7J247J2YJyxcbiAgICAgICAgJ2xiLUxVJzogJ1BlcnPDqWlubGVjaCcsXG4gICAgICAgICdubC1OTCc6ICdwZXJzb29ubGlqaycsXG4gICAgICAgICdwdC1QVCc6ICdQZXNzb2FsJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Cb0LjRh9C90L7QtScsXG4gICAgICAgICdzbC1TSSc6ICdPc2Vibm8nLFxuICAgICAgICAnc3YtU0UnOiAnUGVyc29ubGlnJyxcbiAgICAgICAgdGg6ICfguKrguYjguKfguJnguJXguLHguKcnLFxuICAgICAgICB1azogJ9Ce0YHQvtCx0LjRgdGC0ZYnLFxuICAgICAgICAnemgtQ04nOiAn5Liq5Lq655qEJyxcbiAgICAgICAgJ3poLVRXJzogJ+WAi+S6uueahCdcbiAgICB9LFxuICAgIHNoaXBwaW5nOiB7XG4gICAgICAgICdkZS1ERSc6ICdWZXJzYW5kJyxcbiAgICAgICAgJ2VuLVVTJzogJ1NoaXBwaW5nJyxcbiAgICAgICAgJ2VzLUVTJzogJ0VudmlvJyxcbiAgICAgICAgZnI6ICdMaXZyYWlzb24nLFxuICAgICAgICBpdDogJ1NwZWRpemlvbmUnLFxuICAgICAgICBqYTogJ+eZuumAgScsXG4gICAgICAgICdyby1STyc6ICdMaXZyYXJlJyxcbiAgICAgICAgYXI6ICfYtNit2YYnLFxuICAgICAgICBjYTogJ0VudmlhbWVudCcsXG4gICAgICAgICdjcy1DWic6ICdMb2Ruw60gZG9wcmF2YScsXG4gICAgICAgICdkYS1ESyc6ICdGb3JzZW5kZWxzZScsXG4gICAgICAgIGVsOiAnzpHPgM6/z4PPhM6/zrvOricsXG4gICAgICAgICdoaS1JTic6ICfgpLbgpL/gpKrgpL/gpILgpJcnLFxuICAgICAgICAna28tS1InOiAn67Cw7IahJyxcbiAgICAgICAgJ2xiLUxVJzogJ0xpd3dlcnVuZycsXG4gICAgICAgICdubC1OTCc6ICdWZXJ6ZW5kaW5nJyxcbiAgICAgICAgJ3B0LVBUJzogJ0VudmlvJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Cf0LXRgNC10LLQvtC30LrQuCcsXG4gICAgICAgICdzbC1TSSc6ICdEb3N0YXZhJyxcbiAgICAgICAgJ3N2LVNFJzogJ0ZyYWt0JyxcbiAgICAgICAgdGg6ICfguIHguLLguKPguKrguYjguIfguKrguLTguJnguITguYnguLInLFxuICAgICAgICB1azogJ9CU0L7RgdGC0LDQstC60LAnLFxuICAgICAgICAnemgtQ04nOiAn6Ii56L+QJyxcbiAgICAgICAgJ3poLVRXJzogJ+iIuemBiydcbiAgICB9LFxuICAgIGJpbGxpbmc6IHtcbiAgICAgICAgJ2RlLURFJzogJ1JlY2hudW5nc2FkcmVzc2UnLFxuICAgICAgICAnZW4tVVMnOiAnQmlsbGluZycsXG4gICAgICAgICdlcy1FUyc6ICdEaXJlY2Npw7NuIGRlIGZhY3R1cmFjacOzbicsXG4gICAgICAgIGZyOiAnQWRyZXNzZSBkZSBmYWN0dXJhdGlvbicsXG4gICAgICAgIGl0OiAnSW5kaXJpenpvIGRpIGZhdHR1cmF6aW9uZScsXG4gICAgICAgIGphOiAn6KuL5rGCJyxcbiAgICAgICAgJ3JvLVJPJzogJ0ZhY3R1cmFyZScsXG4gICAgICAgIGFyOiAn2KfZhNmB2YjYp9iq2YrYsScsXG4gICAgICAgIGNhOiAnRmFjdHVyYWNpw7MnLFxuICAgICAgICAnY3MtQ1onOiAnRmFrdHVyYWNlJyxcbiAgICAgICAgJ2RhLURLJzogJ0Zha3R1cmVyaW5nJyxcbiAgICAgICAgZWw6ICfOp8+Bzq3Pic+DzrcnLFxuICAgICAgICAnaGktSU4nOiAn4KSs4KS/4KSy4KS/4KSC4KSXJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yyreq1rCcsXG4gICAgICAgICdsYi1MVSc6ICdSZWNobnVuZycsXG4gICAgICAgICdubC1OTCc6ICdGYWN0dXJlcmluZycsXG4gICAgICAgICdwdC1QVCc6ICdDb2JyYW7Dp2EnLFxuICAgICAgICAncnUtUlUnOiAn0JHQuNC70LvQuNC90LMnLFxuICAgICAgICAnc2wtU0knOiAnT2JyYcSNdW5hdmFuamUnLFxuICAgICAgICAnc3YtU0UnOiAnRmFrdHVyZXJpbmcnLFxuICAgICAgICB0aDogJ+C4geC4suC4o+C5gOC4o+C4teC4ouC4geC5gOC4geC5h+C4muC5gOC4h+C4tOC4mScsXG4gICAgICAgIHVrOiAn0JLQuNGB0YLQsNCy0LvQtdC90L3RjyDRgNCw0YXRg9C90LrRltCyJyxcbiAgICAgICAgJ3poLUNOJzogJ+iuoei0uScsXG4gICAgICAgICd6aC1UVyc6ICfoqIjosrsnXG4gICAgfSxcbiAgICBjb250aW51ZToge1xuICAgICAgICAnZGUtREUnOiAnV2VpdGVyJyxcbiAgICAgICAgJ2VuLVVTJzogJ0NvbnRpbnVlJyxcbiAgICAgICAgJ2VzLUVTJzogJ0NvbnRpbnVhcicsXG4gICAgICAgIGZyOiAnQ29udGludWV6JyxcbiAgICAgICAgaXQ6ICdDb250aW51YScsXG4gICAgICAgIGphOiAn57aa44GN44G4JyxcbiAgICAgICAgJ3JvLVJPJzogJ0NvbnRpbnVhJyxcbiAgICAgICAgYXI6ICfZitmD2YXZhCcsXG4gICAgICAgIGNhOiAnQ29udGludWEnLFxuICAgICAgICAnY3MtQ1onOiAnUG9rcmHEjW92YXQnLFxuICAgICAgICAnZGEtREsnOiAnQmxpdmUgdmVkJyxcbiAgICAgICAgZWw6ICfOnc6xIM+Dz4XOvc61z4fOr8+DzrXOuScsXG4gICAgICAgICdoaS1JTic6ICfgpJzgpL7gpLDgpYAg4KSw4KSW4KSo4KS+JyxcbiAgICAgICAgJ2tvLUtSJzogJ+qzhOyGje2VmOuLpCcsXG4gICAgICAgICdsYi1MVSc6ICdGdWVydCB3ZWlkZXInLFxuICAgICAgICAnbmwtTkwnOiAnRG9vcmdhYW4gbWV0JyxcbiAgICAgICAgJ3B0LVBUJzogJ1Byb3NzZWd1aXInLFxuICAgICAgICAncnUtUlUnOiAn0J/RgNC+0LTQvtC70LbQsNGC0YwnLFxuICAgICAgICAnc2wtU0knOiAnTmFkYWxqdWonLFxuICAgICAgICAnc3YtU0UnOiAnRm9ydHPDpHR0YScsXG4gICAgICAgIHRoOiAn4LiU4Liz4LmA4LiZ4Li04LiZ4LiB4Liy4Lij4LiV4LmI4LitJyxcbiAgICAgICAgdWs6ICfQn9GA0L7QtNC+0LLQttC40YLQuCcsXG4gICAgICAgICd6aC1DTic6ICfnu6fnu60nLFxuICAgICAgICAnemgtVFcnOiAn57m857qMJ1xuICAgIH0sXG4gICAgJ3NlY3VyZS1ub3RpY2UnOiB7XG4gICAgICAgICdkZS1ERSc6ICdHZXNpY2hlcnQgZHVyY2ggJyxcbiAgICAgICAgJ2VuLVVTJzogJ1NlY3VyZWQgYnknLFxuICAgICAgICAnZXMtRVMnOiAnUHJvdGVnaWRvIHBvcicsXG4gICAgICAgIGZyOiAnU8OpY3VyaXPDqSBwYXInLFxuICAgICAgICBpdDogJ1Byb3RldHRvIGRhJyxcbiAgICAgICAgamE6ICfkv53orbfjgZXjgozjgabjgYTjgb7jgZknLFxuICAgICAgICAncm8tUk8nOiAnR2FyYW50YXQgZGUnLFxuICAgICAgICBhcjogJ9io2LbZhdin2YYnLFxuICAgICAgICBjYTogJ0dhcmFudGl0IHBlcicsXG4gICAgICAgICdjcy1DWic6ICdaYWppxaF0xJtubycsXG4gICAgICAgICdkYS1ESyc6ICdTaWtyZXQgYWYnLFxuICAgICAgICBlbDogJ86Vzr7Osc+Dz4bOsc67zq/Ots61z4TOsc65IM6xz4DPjCcsXG4gICAgICAgICdoaS1JTic6ICfgpIfgpLjgpJXgpYcg4KSc4KSw4KS/4KSPIOCkuOClgeCksOCkleCljeCkt+Ckv+CkpCcsXG4gICAgICAgICdrby1LUic6ICfrs7TslYgnLFxuICAgICAgICAnbGItTFUnOiAnR2Vzw6ljaGVydCB2dW4nLFxuICAgICAgICAnbmwtTkwnOiAnQmV2ZWlsaWdkIGRvb3InLFxuICAgICAgICAncHQtUFQnOiAnQXNzZWd1cmFkbyBwb3InLFxuICAgICAgICAncnUtUlUnOiAn0J7QsdC10YHQv9C10YfQtdC90L4nLFxuICAgICAgICAnc2wtU0knOiAnWmF2YXJvdmFubyBzJyxcbiAgICAgICAgJ3N2LVNFJzogJ1PDpGtyYWQgYXYnLFxuICAgICAgICB0aDogJ+C4m+C4peC4reC4lOC4oOC4seC4ouC5guC4lOC4oicsXG4gICAgICAgIHVrOiAn0JfQsNCx0LXQt9C/0LXRh9GD0ZTRgtGM0YHRjycsXG4gICAgICAgICd6aC1DTic6ICfmi4Xkv53kuronLFxuICAgICAgICAnemgtVFcnOiAn5pOU5L+d5Lq6J1xuICAgIH0sXG4gICAgZXhpdDoge1xuICAgICAgICAnZGUtREUnOiAnS2Fzc2UgdmVybGFzc2VuJyxcbiAgICAgICAgJ2VuLVVTJzogJ0V4aXQgQ2hlY2tvdXQnLFxuICAgICAgICAnZXMtRVMnOiAnU2FsaXIgZGUgbGEgY2FqYScsXG4gICAgICAgIGZyOiAnUXVpdHRlcicsXG4gICAgICAgIGl0OiAnRXNjaSBkYWwgY2hlY2tvdXQnLFxuICAgICAgICBqYTogJ+aUr+aJleOBhOOCkue1guS6hicsXG4gICAgICAgICdyby1STyc6ICfDjm5hcG9pIGxhIHBhZ2luYSBwcm9kdXN1bHVpJyxcbiAgICAgICAgYXI6ICfYp9mE2K7YsdmI2Kwg2YXZhiDYp9mE2K7YsdmI2KwnLFxuICAgICAgICBjYTogJ1N1cnQgZGUgR29vZ2xlIENoZWNrb3V0JyxcbiAgICAgICAgJ2NzLUNaJzogJ1Vrb27EjWl0IHBva2xhZG51JyxcbiAgICAgICAgJ2RhLURLJzogJ0Fmc2x1dCBDaGVja291dCcsXG4gICAgICAgIGVsOiAnzojOvs6/zrTOv8+CIM6xz4DPjCDPhM6/IM+EzrHOvM61zq/OvycsXG4gICAgICAgICdoaS1JTic6ICfgpJrgpYfgpJXgpIbgpIngpJ8g4KS44KWHIOCkrOCkvuCkueCksCDgpKjgpL/gpJXgpLLgpYfgpIInLFxuICAgICAgICAna28tS1InOiAn7LK07YGs7JWE7JuDIOyiheujjCcsXG4gICAgICAgICdsYi1MVSc6ICdFeGl0IENoZWNrb3V0JyxcbiAgICAgICAgJ25sLU5MJzogJ0FmcmVrZW5lbiBhZnNsdWl0ZW4nLFxuICAgICAgICAncHQtUFQnOiAnU2FpciBkbyBjaGVja291dCcsXG4gICAgICAgICdydS1SVSc6ICfQktGL0LnRgtC4INC40Lcg0LrQsNGB0YHRiycsXG4gICAgICAgICdzbC1TSSc6ICdaYXByaXRlIENoZWNrb3V0JyxcbiAgICAgICAgJ3N2LVNFJzogJ0F2c2x1dGEga2Fzc2FuJyxcbiAgICAgICAgdGg6ICfguK3guK3guIHguIjguLLguIHguIHguLLguKPguIrguLPguKPguLDguYDguIfguLTguJknLFxuICAgICAgICB1azogJ9CS0LjQudGC0Lgg0LcgQ2hlY2tvdXQnLFxuICAgICAgICAnemgtQ04nOiAn6YCA5Ye657uT5biQJyxcbiAgICAgICAgJ3poLVRXJzogJ+mAgOWHuue1kOW4sydcbiAgICB9LFxuICAgICdvcmRlci1zdW1tYXJ5Jzoge1xuICAgICAgICAnZGUtREUnOiAnQmVzdGVsbHp1c2FtbWVuZmFzc3VuZycsXG4gICAgICAgICdlbi1VUyc6ICdPcmRlciBzdW1tYXJ5JyxcbiAgICAgICAgJ2VzLUVTJzogJ1Jlc3VtZW4gZGVsIHBlZGlkbycsXG4gICAgICAgIGZyOiAnUsOpY2FwaXR1bGF0aWYgZGUgbGEgY29tbWFuZGUnLFxuICAgICAgICBpdDogJ1JpZXBpbG9nbyBkZWxsXFwnb3JkaW5lJyxcbiAgICAgICAgamE6ICfms6jmlofjga7mpoLopoEnLFxuICAgICAgICAncm8tUk8nOiAnUmV6dW1hdCBDb21hbmTEgycsXG4gICAgICAgIGFyOiAn2YXZhNiu2LUg2KfZhNi32YTYqCcsXG4gICAgICAgIGNhOiAnUmVzdW0gZGUgbGEgY29tYW5kYScsXG4gICAgICAgICdjcy1DWic6ICdQxZllaGxlZCBvYmplZG7DoXZreScsXG4gICAgICAgICdkYS1ESyc6ICdPcmRyZXNhbW1lbmRyYWcnLFxuICAgICAgICBlbDogJ86gzrXPgc6vzrvOt8+Izrcgz4DOsc+BzrHOs86zzrXOu86vzrHPgicsXG4gICAgICAgICdoaS1JTic6ICfgpIbgpKbgpYfgpLYg4KS44KS+4KSw4KS+4KSC4KS2JyxcbiAgICAgICAgJ2tvLUtSJzogJ+yjvOusuCDsmpTslb0nLFxuICAgICAgICAnbGItTFUnOiAnVWVyZG51bmcgUmVzdW3DqScsXG4gICAgICAgICdubC1OTCc6ICdPdmVyemljaHQgdmFuIGRlIGJlc3RlbGxpbmcnLFxuICAgICAgICAncHQtUFQnOiAnUmVzdW1vIGRvIHBlZGlkbycsXG4gICAgICAgICdydS1SVSc6ICfQmNGC0L7QsyDQt9Cw0LrQsNC30LAnLFxuICAgICAgICAnc2wtU0knOiAnUG92emV0ZWsgbmFyb8SNaWxhJyxcbiAgICAgICAgJ3N2LVNFJzogJ09yZGVyc2FtbWFuZmF0dG5pbmcnLFxuICAgICAgICB0aDogJ+C4quC4o+C4uOC4m+C4hOC4s+C4quC4seC5iOC4h+C4i+C4t+C5ieC4rScsXG4gICAgICAgIHVrOiAn0J/RltC00YHRg9C80L7QuiDQl9Cw0LzQvtCy0LvQtdC90L3RjycsXG4gICAgICAgICd6aC1DTic6ICforqLljZXmkZjopoEnLFxuICAgICAgICAnemgtVFcnOiAn6KiC5Zau5pGY6KaBJ1xuICAgIH0sXG4gICAgc3VidG90YWw6IHtcbiAgICAgICAgJ2RlLURFJzogJ1p3aXNjaGVuc3VtbWUnLFxuICAgICAgICAnZW4tVVMnOiAnU3VidG90YWwnLFxuICAgICAgICAnZXMtRVMnOiAnU3VidG90YWwnLFxuICAgICAgICBmcjogJ1NvdXMtdG90YWwnLFxuICAgICAgICBpdDogJ1RvdGFsZSBwYXJ6aWFsZScsXG4gICAgICAgIGphOiAn5bCP6KiIJyxcbiAgICAgICAgJ3JvLVJPJzogJ1N1YnRvdGFsJyxcbiAgICAgICAgYXI6ICfYp9mE2YXYrNmF2YjYuSDYp9mE2YHYsdi52YonLFxuICAgICAgICBjYTogJ1N1YnRvdGFsJyxcbiAgICAgICAgJ2NzLUNaJzogJ01lemlzb3XEjWV0JyxcbiAgICAgICAgJ2RhLURLJzogJ1N1YnRvdGFsJyxcbiAgICAgICAgZWw6ICfOnM6VzqHOmc6azp8gzqPOpc6dzp/Om86fJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkieCkqi3gpK/gpYvgpJcnLFxuICAgICAgICAna28tS1InOiAn7IaM6rOEJyxcbiAgICAgICAgJ2xiLUxVJzogJ1N1YnRvdGFsJyxcbiAgICAgICAgJ25sLU5MJzogJ1N1YnRvdGFhbCcsXG4gICAgICAgICdwdC1QVCc6ICdTdWJ0b3RhbCcsXG4gICAgICAgICdydS1SVSc6ICfQn9GA0L7QvNC10LbRg9GC0L7Rh9C90YvQuSDQuNGC0L7QsycsXG4gICAgICAgICdzbC1TSSc6ICdWbWVzbmkgc2XFoXRldmVrJyxcbiAgICAgICAgJ3N2LVNFJzogJ0RlbHN1bW1hJyxcbiAgICAgICAgdGg6ICfguKLguK3guJTguKPguKfguKEnLFxuICAgICAgICB1azogJ9Cf0YDQvtC80ZbQttC90LjQuSDQv9GW0LTRgdGD0LzQvtC6JyxcbiAgICAgICAgJ3poLUNOJzogJ+Wwj+iuoScsXG4gICAgICAgICd6aC1UVyc6ICflsI/oqIgnXG4gICAgfSxcbiAgICB0YXg6IHtcbiAgICAgICAgJ2RlLURFJzogJ1N0ZXVlcicsXG4gICAgICAgICdlbi1VUyc6ICdUYXgnLFxuICAgICAgICAnZXMtRVMnOiAnSW1wdWVzdG8nLFxuICAgICAgICBmcjogJ0ltcMO0dCcsXG4gICAgICAgIGl0OiAnVGFzc2EnLFxuICAgICAgICBqYTogJ+eojicsXG4gICAgICAgICdyby1STyc6ICdJbXBveml0JyxcbiAgICAgICAgYXI6ICfYttix2YrYqNipJyxcbiAgICAgICAgY2E6ICdJbXBvc3RvcycsXG4gICAgICAgICdjcy1DWic6ICdEYcWIJyxcbiAgICAgICAgJ2RhLURLJzogJ1NrYXQnLFxuICAgICAgICBlbDogJ86mz4zPgc6/z4InLFxuICAgICAgICAnaGktSU4nOiAn4KSV4KSwJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yEuCcsXG4gICAgICAgICdsYi1MVSc6ICdTdGVpZXInLFxuICAgICAgICAnbmwtTkwnOiAnQmVsYXN0aW5nJyxcbiAgICAgICAgJ3B0LVBUJzogJ0ltcG9zdG8nLFxuICAgICAgICAncnUtUlUnOiAn0J3QsNC70L7QsycsXG4gICAgICAgICdzbC1TSSc6ICdEYXZlaycsXG4gICAgICAgICdzdi1TRSc6ICdCZXNrYXR0YScsXG4gICAgICAgIHRoOiAn4Lig4Liy4Lip4Li1JyxcbiAgICAgICAgdWs6ICfQn9C+0LTQsNGC0L7QuicsXG4gICAgICAgICd6aC1DTic6ICfnqI4nLFxuICAgICAgICAnemgtVFcnOiAn56iFJ1xuICAgIH0sXG4gICAgdG90YWw6IHtcbiAgICAgICAgJ2RlLURFJzogJ0dlc2FtdCcsXG4gICAgICAgICdlbi1VUyc6ICdUb3RhbCcsXG4gICAgICAgICdlcy1FUyc6ICdUb3RhbCcsXG4gICAgICAgIGZyOiAnVG90YWwnLFxuICAgICAgICBpdDogJ1RvdGFsZScsXG4gICAgICAgIGphOiAn5ZCI6KiIJyxcbiAgICAgICAgJ3JvLVJPJzogJ1RvdGFsJyxcbiAgICAgICAgYXI6ICfYp9mE2YXYrNmF2YjYuScsXG4gICAgICAgIGNhOiAnVG90YWwnLFxuICAgICAgICAnY3MtQ1onOiAnQ2Vsa292w70nLFxuICAgICAgICAnZGEtREsnOiAnaSBhbHQnLFxuICAgICAgICBlbDogJ86jz43Ovc6/zrvOvycsXG4gICAgICAgICdoaS1JTic6ICfgpJXgpYHgpLInLFxuICAgICAgICAna28tS1InOiAn7LSdJyxcbiAgICAgICAgJ2xiLUxVJzogJ0luc2dlc2FtdCcsXG4gICAgICAgICdubC1OTCc6ICdUb3RhYWwnLFxuICAgICAgICAncHQtUFQnOiAnVG90YWwnLFxuICAgICAgICAncnUtUlUnOiAn0J7QsdGJ0LjQuScsXG4gICAgICAgICdzbC1TSSc6ICdTa3VwYWonLFxuICAgICAgICAnc3YtU0UnOiAnVG90YWwnLFxuICAgICAgICB0aDogJ+C4o+C4p+C4oScsXG4gICAgICAgIHVrOiAn0JLRgdGM0L7Qs9C+JyxcbiAgICAgICAgJ3poLUNOJzogJ+WFqOmDqOeahCcsXG4gICAgICAgICd6aC1UVyc6ICflhajpg6jnmoQnXG4gICAgfSxcbiAgICBjb3Vwb246IHtcbiAgICAgICAgJ2RlLURFJzogJ0NvdXBvbicsXG4gICAgICAgICdlbi1VUyc6ICdDb3Vwb24nLFxuICAgICAgICAnZXMtRVMnOiAnQ3Vww7NuJyxcbiAgICAgICAgZnI6ICdDb3Vwb24nLFxuICAgICAgICBpdDogJ0NvdXBvbicsXG4gICAgICAgIGphOiAn44Kv44O844Od44OzJyxcbiAgICAgICAgJ3JvLVJPJzogJ0N1cG9uJyxcbiAgICAgICAgYXI6ICfZgtiz2YrZhdipJyxcbiAgICAgICAgY2E6ICdDdXDDsycsXG4gICAgICAgICdjcy1DWic6ICdLdXDDs24nLFxuICAgICAgICAnZGEtREsnOiAnS3Vwb24nLFxuICAgICAgICBlbDogJ86azr/Phc+Az4zOvc65JyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkleClguCkquCkqCcsXG4gICAgICAgICdrby1LUic6ICfsv6Dtj7AnLFxuICAgICAgICAnbGItTFUnOiAnQ291cG9uJyxcbiAgICAgICAgJ25sLU5MJzogJ0NvdXBvbicsXG4gICAgICAgICdwdC1QVCc6ICdDdXBvbScsXG4gICAgICAgICdydS1SVSc6ICfQmtGD0L/QvtC9JyxcbiAgICAgICAgJ3NsLVNJJzogJ0t1cG9uJyxcbiAgICAgICAgJ3N2LVNFJzogJ0t1cG9uZycsXG4gICAgICAgIHRoOiAn4LiE4Li54Lib4Lit4LiHJyxcbiAgICAgICAgdWs6ICfQmtGD0L/QvtC9JyxcbiAgICAgICAgJ3poLUNOJzogJ+S8mOaDoOWIuCcsXG4gICAgICAgICd6aC1UVyc6ICflhKrmg6DliLgnXG4gICAgfSxcbiAgICAnd2MtY291cG9uLWNvZGUnOiB7XG4gICAgICAgICdkZS1ERSc6ICdHdXRzY2hlaW5jb2RlJyxcbiAgICAgICAgJ2VuLVVTJzogJ0NvdXBvbiBjb2RlJyxcbiAgICAgICAgJ2VzLUVTJzogJ0PDs2RpZ28gcHJvbW9jaW9uYWwnLFxuICAgICAgICBmcjogJ0NvZGUgZGUgY291cG9uJyxcbiAgICAgICAgaXQ6ICdDb2RpY2UgY291cG9uJyxcbiAgICAgICAgamE6ICfjgq/jg7zjg53jg7PjgrPjg7zjg4knLFxuICAgICAgICAncm8tUk8nOiAnQ29kIGN1cG9uJyxcbiAgICAgICAgYXI6ICfYsdmF2LIg2KfZhNmD2YjYqNmI2YYnLFxuICAgICAgICBjYTogJ0NvZGkgZGUgY3Vww7MnLFxuICAgICAgICAnY3MtQ1onOiAnS8OzZCBrdXDDs251JyxcbiAgICAgICAgJ2RhLURLJzogJ0t1cG9ua29kZScsXG4gICAgICAgIGVsOiAnzprPic60zrnOus+Mz4IgzrrOv8+Fz4DOv869zrnOv8+NJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkleClguCkquCkqCDgpJXgpYvgpKEnLFxuICAgICAgICAna28tS1InOiAn7L+g7Y+wIOy9lOuTnCcsXG4gICAgICAgICdsYi1MVSc6ICdDb3Vwb24gQ29kZScsXG4gICAgICAgICdubC1OTCc6ICdDb3Vwb24gY29kZScsXG4gICAgICAgICdwdC1QVCc6ICdDw7NkaWdvIGRvIGN1cG9tJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Ca0L7QtCDQutGD0L/QvtC90LAnLFxuICAgICAgICAnc2wtU0knOiAnS29kYSBrdXBvbmEnLFxuICAgICAgICAnc3YtU0UnOiAnS3Vwb25nc2tvZCcsXG4gICAgICAgIHRoOiAn4Lij4Lir4Lix4Liq4LiE4Li54Lib4Lit4LiHJyxcbiAgICAgICAgdWs6ICfQmtC+0LQg0LrRg9C/0L7QvdCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+S8mOaDoOWNt+WPt+eggScsXG4gICAgICAgICd6aC1UVyc6ICflhKrmg6DljbfomZ/norwnXG4gICAgfSxcbiAgICAnd2MtaW52YWxpZC1jb3Vwb24nOiB7XG4gICAgICAgICdkZS1ERSc6ICdTaWUgaGFiZW4gZWluZW4gdW5nw7xsdGlnZW4gR3V0c2NoZWluY29kZSBlaW5nZWdlYmVuJyxcbiAgICAgICAgJ2VuLVVTJzogJ1lvdSBlbnRlcmVkIGFuIGludmFsaWQgY291cG9uIGNvZGUnLFxuICAgICAgICAnZXMtRVMnOiAnSW5ncmVzYXN0ZSB1biBjw7NkaWdvIGRlIGN1cMOzbiBubyB2w6FsaWRvJyxcbiAgICAgICAgZnI6ICdWb3VzIGF2ZXogZW50csOpIHVuIGNvZGUgZGUgY291cG9uIG5vbiB2YWxpZGUnLFxuICAgICAgICBpdDogJ0hhaSBpbnNlcml0byB1biBjb2RpY2UgY291cG9uIG5vbiB2YWxpZG8nLFxuICAgICAgICBqYTogJ+eEoeWKueOBquOCr+ODvOODneODs+OCs+ODvOODieOCkuWFpeWKm+OBl+OBvuOBl+OBnycsXG4gICAgICAgICdyby1STyc6ICdByJtpIGludHJvZHVzIHVuIGNvZCBkZSBjdXBvbiBuZXZhbGlkJyxcbiAgICAgICAgYXI6ICfZhNmC2K8g2KPYr9iu2YTYqiDYsdmF2LIg2YLYs9mK2YXYqSDYutmK2LEg2LXYp9mE2K0nLFxuICAgICAgICBjYTogJ0hldSBpbnRyb2R1w690IHVuIGNvZGkgZGUgY3Vww7Mgbm8gdsOgbGlkJyxcbiAgICAgICAgJ2NzLUNaJzogJ1phZGFsaSBqc3RlIG5lcGxhdG7DvSBrw7NkIGt1cMOzbnUnLFxuICAgICAgICAnZGEtREsnOiAnRHUgaGFyIGluZHRhc3RldCBlbiB1Z3lsZGlnIGt1cG9ua29kZScsXG4gICAgICAgIGVsOiAnzprOsc+EzrHPh8+Jz4HOr8+DzrHPhM61IM6tzr3Osc69IM68zrcgzq3Os866z4XPgc6/IM66z4nOtM65zrrPjCDOus6/z4XPgM6/zr3Ouc6/z40nLFxuICAgICAgICAnaGktSU4nOiAn4KSG4KSq4KSo4KWHIOCkj+CklSDgpIXgpK7gpL7gpKjgpY3gpK8g4KSV4KWC4KSq4KSoIOCkleCli+CkoSDgpKbgpLDgpY3gpJwg4KSV4KS/4KSv4KS+IOCkueCliCcsXG4gICAgICAgICdrby1LUic6ICfsnpjrqrvrkJwg7L+g7Y+wIOy9lOuTnOulvCDsnoXroKXtlojsirXri4jri6QuJyxcbiAgICAgICAgJ2xiLUxVJzogJ0RpciBodXR0IGVuIG9uZ8OrbHRlZ2UgQ291cG9uY29kZSBhZ2lubicsXG4gICAgICAgICdubC1OTCc6ICdVIGhlZWZ0IGVlbiBvbmdlbGRpZ2UgY291cG9uY29kZSBpbmdldm9lcmQnLFxuICAgICAgICAncHQtUFQnOiAnVm9jw6ogaW5zZXJpdSB1bSBjw7NkaWdvIGRlIGN1cG9tIGludsOhbGlkbycsXG4gICAgICAgICdydS1SVSc6ICfQktGLINCy0LLQtdC70Lgg0L3QtdCy0LXRgNC90YvQuSDQutC+0LQg0LrRg9C/0L7QvdCwJyxcbiAgICAgICAgJ3NsLVNJJzogJ1ZuZXNsaSBzdGUgbmV2ZWxqYXZubyBrb2RvIGt1cG9uYScsXG4gICAgICAgICdzdi1TRSc6ICdEdSBoYXIgYW5nZXR0IGVuIG9naWx0aWcga3Vwb25na29kJyxcbiAgICAgICAgdGg6ICfguITguLjguJPguJvguYnguK3guJnguKPguKvguLHguKrguITguLnguJvguK3guIfguYTguKHguYjguJbguLnguIHguJXguYnguK3guIcnLFxuICAgICAgICB1azogJ9CS0Lgg0LLQstC10LvQuCDQvdC10LTRltC50YHQvdC40Lkg0LrQvtC0INC60YPQv9C+0L3QsCcsXG4gICAgICAgICd6aC1DTic6ICfmgqjovpPlhaXkuobml6DmlYjnmoTkvJjmg6DliLjku6PnoIEnLFxuICAgICAgICAnemgtVFcnOiAn5oKo6Ly45YWl5LqG54Sh5pWI55qE5YSq5oOg5Yi45Luj56K8J1xuICAgIH0sXG4gICAgYXBwbHk6IHtcbiAgICAgICAgJ2RlLURFJzogJ0VpbmzDtnNlbicsXG4gICAgICAgICdlbi1VUyc6ICdBcHBseScsXG4gICAgICAgICdlcy1FUyc6ICdBcGxpY2FyJyxcbiAgICAgICAgZnI6ICdBcHBsaXF1ZXInLFxuICAgICAgICBpdDogJ0FwcGxpY2FyZScsXG4gICAgICAgIGphOiAn55Sz6L6844G/JyxcbiAgICAgICAgJ3JvLVJPJzogJ0FwbGljYScsXG4gICAgICAgIGFyOiAn2KrYt9io2YrZgicsXG4gICAgICAgIGNhOiAnQXBsaWNhcicsXG4gICAgICAgICdjcy1DWic6ICdBcGxpa292YXQnLFxuICAgICAgICAnZGEtREsnOiAnYW5zw7hnZScsXG4gICAgICAgIGVsOiAnzpnPg8+Hz43Ov8+Fzr0nLFxuICAgICAgICAnaGktSU4nOiAn4KSy4KS+4KSX4KWCIOCkleCksOCkqOCkvicsXG4gICAgICAgICdrby1LUic6ICfsoIHsmqntlZjri6QnLFxuICAgICAgICAnbGItTFUnOiAnR8OrbGxlJyxcbiAgICAgICAgJ25sLU5MJzogJ1ZhbiB0b2VwYXNzaW5nIHppam4nLFxuICAgICAgICAncHQtUFQnOiAnQXBsaWNhcicsXG4gICAgICAgICdydS1SVSc6ICfQn9C+0LTQsNGC0Ywg0LfQsNGP0LLQu9C10L3QuNC1JyxcbiAgICAgICAgJ3NsLVNJJzogJ1Vwb3JhYmknLFxuICAgICAgICAnc3YtU0UnOiAnVGlsbMOkbXBhJyxcbiAgICAgICAgdGg6ICfguJnguLPguKHguLLguYPguIrguYknLFxuICAgICAgICB1azogJ9CX0LDRgdGC0L7RgdGD0LLQsNGC0LgnLFxuICAgICAgICAnemgtQ04nOiAn55Sz6K+3JyxcbiAgICAgICAgJ3poLVRXJzogJ+eUs+iriydcbiAgICB9LFxuICAgICdnaWZ0LWNhcmQnOiB7XG4gICAgICAgICdkZS1ERSc6ICdHZXNjaGVua2thcnRlJyxcbiAgICAgICAgJ2VuLVVTJzogJ0dpZnQgY2FyZCcsXG4gICAgICAgICdlcy1FUyc6ICdUYXJqZXRhIGRlIHJlZ2FsbycsXG4gICAgICAgIGZyOiAnQ2FydGUgY2FkZWF1JyxcbiAgICAgICAgaXQ6ICdDYXJ0YSByZWdhbG8nLFxuICAgICAgICBqYTogJ+OCruODleODiOOCq+ODvOODiScsXG4gICAgICAgICdyby1STyc6ICdDYXJkIGNhZG91JyxcbiAgICAgICAgYXI6ICfZg9ix2Kog2YfYr9mK2KknLFxuICAgICAgICBjYTogJ1RhcmdldGEgcmVnYWwnLFxuICAgICAgICAnY3MtQ1onOiAnRMOhcmtvdsOhIHBvdWvDoXprYScsXG4gICAgICAgICdkYS1ESyc6ICdHYXZla29ydCcsXG4gICAgICAgIGVsOiAnzpTPic+Bzr/Ous6sz4HPhM6xJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkieCkquCkueCkvuCksCDgpKrgpKTgpY3gpLAnLFxuICAgICAgICAna28tS1InOiAn6riw7ZSE7Yq4IOy5tOuTnCcsXG4gICAgICAgICdsYi1MVSc6ICdLYWRkb2thYXJ0JyxcbiAgICAgICAgJ25sLU5MJzogJ0NhZGVhdWthYXJ0JyxcbiAgICAgICAgJ3B0LVBUJzogJ0NhcnTDo28gUHJlc2VudGUnLFxuICAgICAgICAncnUtUlUnOiAn0J/QvtC00LDRgNC+0YfQvdCw0Y8g0LrQsNGA0YLQsCcsXG4gICAgICAgICdzbC1TSSc6ICdEYXJpbG5lIGthcnRpY2UnLFxuICAgICAgICAnc3YtU0UnOiAnUHJlc2VudCBrb3J0JyxcbiAgICAgICAgdGg6ICfguJrguLHguJXguKPguILguK3guIfguILguKfguLHguI0nLFxuICAgICAgICB1azogJ9Cf0L7QtNCw0YDRg9C90LrQvtCy0LAg0LrQsNGA0YLQutCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+ekvOeJqeWNoScsXG4gICAgICAgICd6aC1UVyc6ICfnpq7nianljaEnXG4gICAgfSxcbiAgICAnZ2lmdC1jYXJkLW51bWJlcic6IHtcbiAgICAgICAgJ2RlLURFJzogJ0dlc2NoZW5ra2FydGVubnVtbWVyJyxcbiAgICAgICAgJ2VuLVVTJzogJ0dpZnQgY2FyZCBudW1iZXInLFxuICAgICAgICAnZXMtRVMnOiAnTnVtZXJvIGRlIHRhcmpldGEgZGUgcmVnYWxvJyxcbiAgICAgICAgZnI6ICdOdW3DqXJvIGRlIGxhIGNhcnRlLWNhZGVhdScsXG4gICAgICAgIGl0OiAnTnVtZXJvIGRlbGxhIGNhcnRhIHJlZ2FsbycsXG4gICAgICAgIGphOiAn44Ku44OV44OI44Kr44O844OJ55Wq5Y+3JyxcbiAgICAgICAgJ3JvLVJPJzogJ051bcSDcnVsIGNhcmR1bHVpIGNhZG91JyxcbiAgICAgICAgYXI6ICfYsdmC2YUg2KjYt9in2YLYqSDYp9mE2YfYr9mK2KknLFxuICAgICAgICBjYTogJ07Dum1lcm8gZGUgdGFyZ2V0YSByZWdhbCcsXG4gICAgICAgICdjcy1DWic6ICfEjMOtc2xvIGTDoXJrb3bDqSBrYXJ0eScsXG4gICAgICAgICdkYS1ESyc6ICdHYXZla29ydG51bW1lcicsXG4gICAgICAgIGVsOiAnzpHPgc65zrjOvM+Mz4IgzrTPic+Bzr/Ous6sz4HPhM6xz4InLFxuICAgICAgICAnaGktSU4nOiAn4KSX4KS/4KSr4KWN4KSfIOCkleCkvuCksOCljeCkoSDgpKjgpILgpKzgpLAnLFxuICAgICAgICAna28tS1InOiAn6riw7ZSE7Yq4IOy5tOuTnCDrsojtmLgnLFxuICAgICAgICAnbGItTFUnOiAnR2VzY2hlbmtrYWFydCBOdW1tZXInLFxuICAgICAgICAnbmwtTkwnOiAnQ2FkZWF1a2FhcnRudW1tZXInLFxuICAgICAgICAncHQtUFQnOiAnTsO6bWVybyBkbyBjYXJ0w6NvLXByZXNlbnRlJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Cd0L7QvNC10YAg0L/QvtC00LDRgNC+0YfQvdC+0Lkg0LrQsNGA0YLRiycsXG4gICAgICAgICdzbC1TSSc6ICfFoHRldmlsa2EgZGFyaWxuZSBrYXJ0aWNlJyxcbiAgICAgICAgJ3N2LVNFJzogJ1ByZXNlbnRrb3J0bnVtbWVyJyxcbiAgICAgICAgdGg6ICfguKvguKHguLLguKLguYDguKXguILguJrguLHguJXguKPguILguK3guIfguILguKfguLHguI0nLFxuICAgICAgICB1azogJ9Cd0L7QvNC10YAg0L/QvtC00LDRgNGD0L3QutC+0LLQvtGXINC60LDRgNGC0LrQuCcsXG4gICAgICAgICd6aC1DTic6ICfnpLzlk4HljaHlj7cnLFxuICAgICAgICAnemgtVFcnOiAn56au5ZOB5Y2h6JmfJ1xuICAgIH0sXG4gICAgJ2ludmFsaWQtZ2lmdC1jYXJkJzoge1xuICAgICAgICAnZGUtREUnOiAnU2llIGhhYmVuIGVpbmUgdW5nw7xsdGlnZSBHZXNjaGVua2thcnRlIGVpbmdlZ2ViZW4nLFxuICAgICAgICAnZW4tVVMnOiAnWW91IGVudGVyZWQgYW4gaW52YWxpZCBnaWZ0IGNhcmQnLFxuICAgICAgICAnZXMtRVMnOiAnSW5ncmVzYXN0ZSB1bmEgdGFyamV0YSBkZSByZWdhbG8gbm8gdsOhbGlkYScsXG4gICAgICAgIGZyOiAnVm91cyBhdmV6IGVudHLDqSB1bmUgY2FydGUtY2FkZWF1IG5vbiB2YWxpZGUnLFxuICAgICAgICBpdDogJ0hhaSBpbnNlcml0byB1bmEgY2FydGEgcmVnYWxvIG5vbiB2YWxpZGEnLFxuICAgICAgICBqYTogJ+eEoeWKueOBquOCruODleODiOOCq+ODvOODieOCkuWFpeWKm+OBl+OBvuOBl+OBnycsXG4gICAgICAgICdyby1STyc6ICdByJtpIGludHJvZHVzIHVuIGNhcmQgY2Fkb3UgbmV2YWxpZCcsXG4gICAgICAgIGFyOiAn2YTZgtivINij2K/YrtmE2Kog2KjYt9in2YLYqSDZh9iv2KfZitinINi62YrYsSDYtdin2YTYrdipJyxcbiAgICAgICAgY2E6ICdIZXUgaW50cm9kdcOvdCB1bmEgdGFyZ2V0YSByZWdhbCBubyB2w6BsaWRhJyxcbiAgICAgICAgJ2NzLUNaJzogJ1phZGFsaSBqc3RlIG5lcGxhdG5vdSBkw6Fya292b3Uga2FydHUnLFxuICAgICAgICAnZGEtREsnOiAnRHUgaGFyIGluZHRhc3RldCBldCB1Z3lsZGlndCBnYXZla29ydCcsXG4gICAgICAgIGVsOiAnzprOsc+EzrHPh8+Jz4HOr8+DzrHPhM61IM68zrnOsSDOvM63IM6tzrPOus+Fz4HOtyDOtM+Jz4HOv866zqzPgc+EzrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSG4KSq4KSo4KWHIOCkj+CklSDgpIXgpK7gpL7gpKjgpY3gpK8g4KSJ4KSq4KS54KS+4KSwIOCkleCkvuCksOCljeCkoSDgpKbgpLDgpY3gpJwg4KSV4KS/4KSv4KS+IOCkueCliCcsXG4gICAgICAgICdrby1LUic6ICfsnpjrqrvrkJwg6riw7ZSE7Yq4IOy5tOuTnOulvCDsnoXroKXtlojsirXri4jri6QuJyxcbiAgICAgICAgJ2xiLUxVJzogJ0RpciBodXR0IGVuZyBvbmfDq2x0ZWcgS2FkZG9rYWFydCBhZ2lubicsXG4gICAgICAgICdubC1OTCc6ICdKZSBoZWJ0IGVlbiBvbmdlbGRpZ2UgY2FkZWF1Ym9uIGluZ2V2b2VyZCcsXG4gICAgICAgICdwdC1QVCc6ICdWb2PDqiBpbnNlcml1IHVtIHZhbGUtcHJlc2VudGUgaW52w6FsaWRvJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CS0Ysg0LLQstC10LvQuCDQvdC10LTQtdC50YHRgtCy0LjRgtC10LvRjNC90YPRjiDQv9C+0LTQsNGA0L7Rh9C90YPRjiDQutCw0YDRgtGDJyxcbiAgICAgICAgJ3NsLVNJJzogJ1ZuZXNsaSBzdGUgbmV2ZWxqYXZubyBkYXJpbG5vIGthcnRpY28nLFxuICAgICAgICAnc3YtU0UnOiAnRHUgaGFyIGFuZ2V0dCBldHQgb2dpbHRpZ3QgcHJlc2VudGtvcnQnLFxuICAgICAgICB0aDogJ+C4hOC4uOC4k+C4m+C5ieC4reC4meC4muC4seC4leC4o+C4guC4reC4h+C4guC4p+C4seC4jeC4l+C4teC5iOC5hOC4oeC5iOC4luC4ueC4geC4leC5ieC4reC4hycsXG4gICAgICAgIHVrOiAn0JLQuCDQstCy0LXQu9C4INC90LXQtNGW0LnRgdC90YMg0L/QvtC00LDRgNGD0L3QutC+0LLRgyDQutCw0YDRgtC60YMnLFxuICAgICAgICAnemgtQ04nOiAn5oKo6L6T5YWl5LqG5peg5pWI55qE56S85ZOB5Y2hJyxcbiAgICAgICAgJ3poLVRXJzogJ+aCqOi8uOWFpeS6hueEoeaViOeahOemruWTgeWNoSdcbiAgICB9LFxuICAgICdnaWZ0LWNhcmQtYWxyZWFkeS1hcHBsaWVkJzoge1xuICAgICAgICAnZGUtREUnOiAnRGllc2UgR2VzY2hlbmtrYXJ0ZSB3dXJkZSBiZXJlaXRzIGFuZ2V3ZW5kZXQuJyxcbiAgICAgICAgJ2VuLVVTJzogJ1RoaXMgZ2lmdCBjYXJkIGhhcyBhbHJlYWR5IGJlZW4gYXBwbGllZC4nLFxuICAgICAgICAnZXMtRVMnOiAnRXN0YSB0YXJqZXRhIGRlIHJlZ2FsbyB5YSBzZSBhcGxpY8OzLicsXG4gICAgICAgIGZyOiAnQ2V0dGUgY2FydGUtY2FkZWF1IGEgZMOpasOgIMOpdMOpIGFwcGxpcXXDqWUuJyxcbiAgICAgICAgaXQ6ICdRdWVzdGEgY2FydGEgcmVnYWxvIMOoIGdpw6Agc3RhdGEgYXBwbGljYXRhLicsXG4gICAgICAgIGphOiAn44GT44Gu44Ku44OV44OI44Kr44O844OJ44Gv44GZ44Gn44Gr6YGp55So44GV44KM44Gm44GE44G+44GZ44CCJyxcbiAgICAgICAgJ3JvLVJPJzogJ0FjZXN0IGNhcmQgY2Fkb3UgYSBmb3N0IGRlamEgYXBsaWNhdC4nLFxuICAgICAgICBhcjogJ9iq2YUg2KrYt9io2YrZgiDYqNi32KfZgtipINin2YTZh9iv2KfZitinINmH2LDZhyDYqNin2YTZgdi52YQuJyxcbiAgICAgICAgY2E6ICdBcXVlc3RhIHRhcmdldGEgcmVnYWwgamEgc1xcJ2hhIGFwbGljYXQuJyxcbiAgICAgICAgJ2NzLUNaJzogJ1RhdG8gZMOhcmtvdsOhIGthcnRhIGppxb4gYnlsYSBwb3XFvml0YS4nLFxuICAgICAgICAnZGEtREsnOiAnRGV0dGUgZ2F2ZWtvcnQgZXIgYWxsZXJlZGUgYW52ZW5kdC4nLFxuICAgICAgICBlbDogJ86Rz4XPhM6uIM63IM60z4nPgc6/zrrOrM+Bz4TOsSDOrc+HzrXOuSDOrs60zrcgzrXPhs6xz4HOvM6/z4PPhM61zq8uJyxcbiAgICAgICAgJ2hpLUlOJzogJ+Ckr+CkuSDgpIngpKrgpLngpL7gpLAg4KSV4KS+4KSw4KWN4KShIOCkquCkueCksuClhyDgpLngpYAg4KSy4KS+4KSX4KWCIOCkleCkv+Ckr+CkviDgpJzgpL4g4KSa4KWB4KSV4KS+IOCkueCliOClpCcsXG4gICAgICAgICdrby1LUic6ICfsnbQg6riw7ZSE7Yq4IOy5tOuTnOuKlCDsnbTrr7gg7KCB7Jqp65CY7JeI7Iq164uI64ukLicsXG4gICAgICAgICdsYi1MVSc6ICdEw6tzIEthZGRva2FhcnQgZ291ZiBzY2hvIGFwcGxpesOpaWVydC4nLFxuICAgICAgICAnbmwtTkwnOiAnRGV6ZSBjYWRlYXVib24gaXMgYWwgdG9lZ2VwYXN0LicsXG4gICAgICAgICdwdC1QVCc6ICdFc3RlIHZhbGUtcHJlc2VudGUgasOhIGZvaSBhcGxpY2Fkby4nLFxuICAgICAgICAncnUtUlUnOiAn0K3RgtCwINC/0L7QtNCw0YDQvtGH0L3QsNGPINC60LDRgNGC0LAg0YPQttC1INCx0YvQu9CwINC/0YDQuNC80LXQvdC10L3QsC4nLFxuICAgICAgICAnc2wtU0knOiAnVGEgZGFyaWxuYSBrYXJ0aWNhIGplIMW+ZSBiaWxhIHVwb3JhYmxqZW5hLicsXG4gICAgICAgICdzdi1TRSc6ICdEZXR0YSBwcmVzZW50a29ydCBoYXIgcmVkYW4gdGlsbMOkbXBhdHMuJyxcbiAgICAgICAgdGg6ICfguKHguLXguIHguLLguKPguYPguIrguYnguJrguLHguJXguKPguILguK3guIfguILguKfguLHguI3guJnguLXguYnguYHguKXguYnguKcnLFxuICAgICAgICB1azogJ9Cm0Y4g0L/QvtC00LDRgNGD0L3QutC+0LLRgyDQutCw0YDRgtC60YMg0LLQttC1INC30LDRgdGC0L7RgdC+0LLQsNC90L4uJyxcbiAgICAgICAgJ3poLUNOJzogJ+atpOekvOWTgeWNoeW3suiiq+W6lOeUqOOAgicsXG4gICAgICAgICd6aC1UVyc6ICfmraTnpq7lk4HljaHlt7Looqvmh4nnlKjjgIInXG4gICAgfSxcbiAgICAnc2hpcC10byc6IHtcbiAgICAgICAgJ2RlLURFJzogJ1ZlcnNhbmQgbmFjaCcsXG4gICAgICAgICdlbi1VUyc6ICdTaGlwIHRvJyxcbiAgICAgICAgJ2VzLUVTJzogJ0VudmlhciBhJyxcbiAgICAgICAgZnI6ICdFbnZveWV6IMOgJyxcbiAgICAgICAgaXQ6ICdTcGVkaXJlIGEnLFxuICAgICAgICBqYTogJ+mFjemAgeWFiCcsXG4gICAgICAgICdyby1STyc6ICfDjm1iYXJjYSBzcHJlJyxcbiAgICAgICAgYXI6ICfYs9in2YHYsSDYudmE2Ykg2YXYqtmGINiz2YHZitmG2Kkg2YQnLFxuICAgICAgICBjYTogJ0VudmlhIGEnLFxuICAgICAgICAnY3MtQ1onOiAnRG9wcmF2aXQgZG8nLFxuICAgICAgICAnZGEtREsnOiAnU2VuZCB0aWwnLFxuICAgICAgICBlbDogJ86Rz4DOv8+Dz4TOv867zq4gz4DPgc6/z4InLFxuICAgICAgICAnaGktSU4nOiAn4KSv4KS54KS+4KSCIOCkreClh+CknOClh+CkgicsXG4gICAgICAgICdrby1LUic6ICfrsLDshqHsp4AnLFxuICAgICAgICAnbGItTFUnOiAnU2Now6lja2VuIHVuJyxcbiAgICAgICAgJ25sLU5MJzogJ1ZlcnplbmQgbmFhcicsXG4gICAgICAgICdwdC1QVCc6ICdFbnZpYXIgcGFyYScsXG4gICAgICAgICdydS1SVSc6ICfQlNC+0YHRgtCw0LLQutCwINC00L4nLFxuICAgICAgICAnc2wtU0knOiAnUG9zbGF0aSB2JyxcbiAgICAgICAgJ3N2LVNFJzogJ0ZyYWt0YSB0aWxsJyxcbiAgICAgICAgdGg6ICfguKrguYjguIfguYTguJvguJfguLXguYgnLFxuICAgICAgICB1azogJ9CS0ZbQtNC/0YDQsNCy0LjRgtC4INC00L4nLFxuICAgICAgICAnemgtQ04nOiAn6L+Q6YCB5YiwJyxcbiAgICAgICAgJ3poLVRXJzogJ+mBi+mAgeWIsCdcbiAgICB9LFxuICAgICdjYXJkLWxhYmVsJzoge1xuICAgICAgICAnZGUtREUnOiAnS3JlZGl0LSBvZGVyIERlYml0a2FydGUnLFxuICAgICAgICAnZW4tVVMnOiAnQ3JlZGl0IG9yIGRlYml0IGNhcmQnLFxuICAgICAgICAnZXMtRVMnOiAnVGFyamV0YSBkZSBjcsOpZGl0byBvIGTDqWJpdG8nLFxuICAgICAgICBmcjogJ0NhcnRlIGRlIGNyw6lkaXQgb3UgZGUgZMOpYml0JyxcbiAgICAgICAgaXQ6ICdDYXJ0YSBkaSBjcmVkaXRvIG8gZGkgZGViaXRvJyxcbiAgICAgICAgamE6ICfjgq/jg6zjgrjjg4Pjg4jjgoLjgZfjgY/jga/jg4fjg5Pjg4Pjg4jjgqvjg7zjg4knLFxuICAgICAgICAncm8tUk8nOiAnQ2FyZCBkZSBjcmVkaXQgc2F1IGRlYml0JyxcbiAgICAgICAgYXI6ICfYqNi32KfZgtipINin2YTYp9im2KrZhdin2YYg2KPZiCDYp9mE2K7YtdmFJyxcbiAgICAgICAgY2E6ICdUYXJnZXRhIGRlIGNyw6hkaXQgbyBkw6hiaXQnLFxuICAgICAgICAnY3MtQ1onOiAnS3JlZGl0bsOtIG5lYm8gZGViZXRuw60ga2FydGEnLFxuICAgICAgICAnZGEtREsnOiAnS3JlZGl0LSBlbGxlciBiZXRhbGluZ3Nrb3J0JyxcbiAgICAgICAgZWw6ICfOoM65z4PPhM+Jz4TOuc66zq4gzq4gz4fPgc61z4nPg8+EzrnOus6uIM66zqzPgc+EzrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSV4KWN4KSw4KWH4KSh4KS/4KSfIOCkr+CkviDgpKHgpYfgpKzgpL/gpJ8g4KSV4KS+4KSw4KWN4KShJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yLoOyaqey5tOuTnCDrmJDripQg7KeB67aI7Lm065OcJyxcbiAgICAgICAgJ2xiLUxVJzogJ0tyZWRpdHQtIG9kZXIgQmFua2thYXJ0JyxcbiAgICAgICAgJ25sLU5MJzogJ0NyZWRpdGNhcmQgb2YgYmFua3BhcycsXG4gICAgICAgICdwdC1QVCc6ICdDYXJ0w6NvIGRlIGNyw6lkaXRvIG91IGTDqWJpdG8nLFxuICAgICAgICAncnUtUlUnOiAn0JrRgNC10LTQuNGC0L3QsNGPINC40LvQuCDQtNC10LHQtdGC0L7QstCw0Y8g0LrQsNGA0YLQsCcsXG4gICAgICAgICdzbC1TSSc6ICdLcmVkaXRuYSBhbGkgZGViZXRuYSBrYXJ0aWNhJyxcbiAgICAgICAgJ3N2LVNFJzogJ0tyZWRpdC1lbGxlciBiZXRhbGtvcnQnLFxuICAgICAgICB0aDogJ+C4muC4seC4leC4o+C5gOC4hOC4o+C4lOC4tOC4leC4q+C4o+C4t+C4reC4muC4seC4leC4o+C5gOC4lOC4muC4tOC4lScsXG4gICAgICAgIHVrOiAn0JrRgNC10LTQuNGC0L3QsCDQsNCx0L4g0LTQtdCx0LXRgtC+0LLQsCDQutCw0YDRgtC60LAnLFxuICAgICAgICAnemgtQ04nOiAn5L+h55So5Y2h5oiW5YCf6K6w5Y2hJyxcbiAgICAgICAgJ3poLVRXJzogJ+S/oeeUqOWNoeaIluWAn+iomOWNoSdcbiAgICB9LFxuICAgIHBheToge1xuICAgICAgICAnZGUtREUnOiAnWmFobGVuJyxcbiAgICAgICAgJ2VuLVVTJzogJ1BheScsXG4gICAgICAgICdlcy1FUyc6ICdQYWdhcicsXG4gICAgICAgIGZyOiAnUGF5ZXInLFxuICAgICAgICBpdDogJ1BhZ2FyZScsXG4gICAgICAgIGphOiAn5pSv5omV44GEJyxcbiAgICAgICAgJ3JvLVJPJzogJ1BsxIN0ZciZdGUnLFxuICAgICAgICBhcjogJ9mK2K/Zgdi5JyxcbiAgICAgICAgY2E6ICdQYWdhJyxcbiAgICAgICAgJ2NzLUNaJzogJ1BsYXRpdCcsXG4gICAgICAgICdkYS1ESyc6ICdCZXRhbGUnLFxuICAgICAgICBlbDogJ86gzrvOt8+Bz4nOvM6uJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkteClh+CkpOCkqCcsXG4gICAgICAgICdrby1LUic6ICfsp4DrtognLFxuICAgICAgICAnbGItTFUnOiAnQmV6dWVsZW4nLFxuICAgICAgICAnbmwtTkwnOiAnQmV0YWxlbicsXG4gICAgICAgICdwdC1QVCc6ICdQYWdhcicsXG4gICAgICAgICdydS1SVSc6ICfQn9C70LDRgtC40YLRjCcsXG4gICAgICAgICdzbC1TSSc6ICdQbGHEjWFqJyxcbiAgICAgICAgJ3N2LVNFJzogJ0JldGFsYScsXG4gICAgICAgIHRoOiAn4LiI4LmI4Liy4LiiJyxcbiAgICAgICAgdWs6ICfQntC/0LvQsNGC0LAnLFxuICAgICAgICAnemgtQ04nOiAn5pSv5LuYJyxcbiAgICAgICAgJ3poLVRXJzogJ+aUr+S7mCdcbiAgICB9LFxuICAgIGJhY2s6IHtcbiAgICAgICAgJ2RlLURFJzogJ1p1csO8Y2sgenUgSW5mb3JtYXRpb25lbicsXG4gICAgICAgICdlbi1VUyc6ICdCYWNrIHRvIGluZm8nLFxuICAgICAgICAnZXMtRVMnOiAnVm9sdmVyIGEgaW5mb3JtYWNpw7NuJyxcbiAgICAgICAgZnI6ICdSZXRvdXIgYXV4IGluZm9ybWF0aW9ucycsXG4gICAgICAgIGl0OiAnVG9ybmEgYWxsZSBpbmZvcm1hemlvbmknLFxuICAgICAgICBqYTogJ+aDheWgseOBq+aIu+OCiycsXG4gICAgICAgICdyby1STyc6ICfDjm5hcG9pIGxhIGluZm9ybWHIm2lpJyxcbiAgICAgICAgYXI6ICfYsdis2YjYuSDYpdmE2Ykg2KfZhNmF2LnZhNmI2YXYp9iqJyxcbiAgICAgICAgY2E6ICdUb3JuYSBhIGxhIGluZm9ybWFjacOzJyxcbiAgICAgICAgJ2NzLUNaJzogJ1pwxJt0IGsgaW5mb3JtYWPDrW0nLFxuICAgICAgICAnZGEtREsnOiAnVGlsYmFnZSB0aWwgaW5mb3JtYXRpb24nLFxuICAgICAgICBlbDogJ86Vz4DOuc+Dz4TPgc6/z4bOriDPg8+EzrnPgiDPgM67zrfPgc6/z4bOv8+Bzq/Otc+CJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CknOCkvuCkqOCkleCkvuCksOClgCDgpKrgpLAg4KS14KS+4KSq4KS4IOCknOCkvuCkj+CkgicsXG4gICAgICAgICdrby1LUic6ICfsoJXrs7TroZwg64+M7JWE6rCA6riwJyxcbiAgICAgICAgJ2xiLUxVJzogJ1pyw6ljayBvcCBJbmZvcm1hdGlvdW4nLFxuICAgICAgICAnbmwtTkwnOiAnVGVydWcgbmFhciBpbmZvcm1hdGllJyxcbiAgICAgICAgJ3B0LVBUJzogJ1ZvbHRhciBwYXJhIGEgaW5mb3JtYcOnw6NvJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CS0LXRgNC90YPRgtGM0YHRjyDQuiDQuNC90YTQvtGA0LzQsNGG0LjQuCcsXG4gICAgICAgICdzbC1TSSc6ICdOYXphaiBuYSBpbmZvcm1hY2lqZScsXG4gICAgICAgICdzdi1TRSc6ICdUaWxsYmFrYSB0aWxsIGluZm9ybWF0aW9uJyxcbiAgICAgICAgdGg6ICfguIHguKXguLHguJrguYTguJvguJfguLXguYjguILguYnguK3guKHguLnguKUnLFxuICAgICAgICB1azogJ9Cd0LDQt9Cw0LQg0LTQviDRltC90YTQvtGA0LzQsNGG0ZbRlycsXG4gICAgICAgICd6aC1DTic6ICfov5Tlm57kv6Hmga8nLFxuICAgICAgICAnemgtVFcnOiAn6L+U5Zue5L+h5oGvJ1xuICAgIH0sXG4gICAgZW1haWw6IHtcbiAgICAgICAgJ2RlLURFJzogJ0VtYWlsJyxcbiAgICAgICAgJ2VuLVVTJzogJ0VtYWlsJyxcbiAgICAgICAgJ2VzLUVTJzogJ0NvcnJlbycsXG4gICAgICAgIGZyOiAnRW1haWwnLFxuICAgICAgICBpdDogJ0VtYWlsJyxcbiAgICAgICAgamE6ICfjg6Hjg7zjg6vjgqLjg4njg6zjgrknLFxuICAgICAgICAncm8tUk8nOiAnRW1haWwnLFxuICAgICAgICBhcjogJ9io2LHZitivINin2YTYp9mE2YPYqtix2YjZhtmKJyxcbiAgICAgICAgY2E6ICdDb3JyZXUgZWxlY3Ryw7JuaWMnLFxuICAgICAgICAnY3MtQ1onOiAnRS1tYWlsZW0nLFxuICAgICAgICAnZGEtREsnOiAnRSAtbWFpbCcsXG4gICAgICAgIGVsOiAnzpfOm86VzprOpM6hzp/Onc6ZzprOlyDOlM6ZzpXOpc6YzqXOnc6jzpcnLFxuICAgICAgICAnaGktSU4nOiAn4KSI4KSu4KWH4KSyJyxcbiAgICAgICAgJ2tvLUtSJzogJ+ydtOuplOydvCcsXG4gICAgICAgICdsYi1MVSc6ICdFbWFpbCcsXG4gICAgICAgICdubC1OTCc6ICdFLW1haWwnLFxuICAgICAgICAncHQtUFQnOiAnTyBlbWFpbCcsXG4gICAgICAgICdydS1SVSc6ICfQrdC7LiDQsNC00YDQtdGBJyxcbiAgICAgICAgJ3NsLVNJJzogJ0UtbmFzbG92JyxcbiAgICAgICAgJ3N2LVNFJzogJ0UtcG9zdCcsXG4gICAgICAgIHRoOiAn4Lit4Li14LmA4Lih4LilJyxcbiAgICAgICAgdWs6ICfQldC70LXQutGC0YDQvtC90L3QsCDQv9C+0YjRgtCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+eUteWtkOmCruS7ticsXG4gICAgICAgICd6aC1UVyc6ICfpm7vlrZDpg7Xku7YnXG4gICAgfSxcbiAgICBkZWxpdmVyeToge1xuICAgICAgICAnZGUtREUnOiAnTGllZmVydW5nJyxcbiAgICAgICAgJ2VuLVVTJzogJ0RlbGl2ZXJ5JyxcbiAgICAgICAgJ2VzLUVTJzogJ0VudHJlZ2EnLFxuICAgICAgICBmcjogJ0xpdnJhaXNvbicsXG4gICAgICAgIGl0OiAnQ29uc2VnbmEnLFxuICAgICAgICBqYTogJ+mFjemAgScsXG4gICAgICAgICdyby1STyc6ICdMaXZyYXJlJyxcbiAgICAgICAgYXI6ICfYqtmI2LXZitmEJyxcbiAgICAgICAgY2E6ICdMbGl1cmFtZW50JyxcbiAgICAgICAgJ2NzLUNaJzogJ2RvZMOhdmthJyxcbiAgICAgICAgJ2RhLURLJzogJ0xldmVyaW5nJyxcbiAgICAgICAgZWw6ICfOlM65zrHOvc6/zrzOricsXG4gICAgICAgICdoaS1JTic6ICfgpLXgpL/gpKTgpLDgpKMnLFxuICAgICAgICAna28tS1InOiAn67Cw64usJyxcbiAgICAgICAgJ2xiLUxVJzogJ0xpd3dlcnVuZycsXG4gICAgICAgICdubC1OTCc6ICdMZXZlcmluZycsXG4gICAgICAgICdwdC1QVCc6ICdFbnRyZWdhJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CU0L7RgdGC0LDQstC60LAnLFxuICAgICAgICAnc2wtU0knOiAnRG9zdGF2YScsXG4gICAgICAgICdzdi1TRSc6ICdMZXZlcmFucycsXG4gICAgICAgIHRoOiAn4LiI4Lix4LiU4Liq4LmI4LiHJyxcbiAgICAgICAgdWs6ICfQlNC+0YHRgtCw0LLQutCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+mAgei0pycsXG4gICAgICAgICd6aC1UVyc6ICfpgIHosqgnXG4gICAgfSxcbiAgICBjYXJkOiB7XG4gICAgICAgICdkZS1ERSc6ICdLYXJ0ZScsXG4gICAgICAgICdlbi1VUyc6ICdDYXJkJyxcbiAgICAgICAgJ2VzLUVTJzogJ1RhcmpldGEnLFxuICAgICAgICBmcjogJ0NhcnRlJyxcbiAgICAgICAgaXQ6ICdDYXJ0YScsXG4gICAgICAgIGphOiAn44Kr44O844OJJyxcbiAgICAgICAgJ3JvLVJPJzogJ0NhcmQnLFxuICAgICAgICBhcjogJ9io2LfYp9mC2KknLFxuICAgICAgICBjYTogJ1RhcmdldGEnLFxuICAgICAgICAnY3MtQ1onOiAnS2FydHUnLFxuICAgICAgICAnZGEtREsnOiAnS29ydCcsXG4gICAgICAgIGVsOiAnzprOrM+Bz4TOsScsXG4gICAgICAgICdoaS1JTic6ICfgpJXgpL7gpLDgpY3gpKEnLFxuICAgICAgICAna28tS1InOiAn7Lm065OcJyxcbiAgICAgICAgJ2xiLUxVJzogJ0thYXJ0JyxcbiAgICAgICAgJ25sLU5MJzogJ0thYXJ0JyxcbiAgICAgICAgJ3B0LVBUJzogJ0NhcnTDo28nLFxuICAgICAgICAncnUtUlUnOiAn0JrQsNGA0YLQsCcsXG4gICAgICAgICdzbC1TSSc6ICdLYXJ0aWNhJyxcbiAgICAgICAgJ3N2LVNFJzogJ0tvcnQnLFxuICAgICAgICB0aDogJ+C4geC4suC4o+C5jOC4lCcsXG4gICAgICAgIHVrOiAn0JrQsNGA0YLQutCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+WNoeeJhycsXG4gICAgICAgICd6aC1UVyc6ICfljaHniYcnXG4gICAgfSxcbiAgICBlZGl0OiB7XG4gICAgICAgICdkZS1ERSc6ICdCZWFyYmVpdGVuJyxcbiAgICAgICAgJ2VuLVVTJzogJ0VkaXQnLFxuICAgICAgICAnZXMtRVMnOiAnRWRpdGFyJyxcbiAgICAgICAgZnI6ICfDiWRpdGVyJyxcbiAgICAgICAgaXQ6ICdNb2RpZmljYXJlJyxcbiAgICAgICAgamE6ICfnt6jpm4YnLFxuICAgICAgICAncm8tUk8nOiAnRWRpdGEnLFxuICAgICAgICBhcjogJ9mK2K3YsdixJyxcbiAgICAgICAgY2E6ICdFZGl0YScsXG4gICAgICAgICdjcy1DWic6ICdVcHJhdml0JyxcbiAgICAgICAgJ2RhLURLJzogJ1JlZGlnZXJlJyxcbiAgICAgICAgZWw6ICfOlc+AzrXOvs61z4HOs86xz4POr86xJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkuOCkguCkquCkvuCkpuCkv+CkpCDgpJXgpLDgpYfgpIInLFxuICAgICAgICAna28tS1InOiAn7Y647KeR7ZWY64ukJyxcbiAgICAgICAgJ2xiLUxVJzogJ0VkaXTDqWllcmVuJyxcbiAgICAgICAgJ25sLU5MJzogJ0Jld2Vya2luZycsXG4gICAgICAgICdwdC1QVCc6ICdFZGl0YXInLFxuICAgICAgICAncnUtUlUnOiAn0KDQtdC00LDQutGC0LjRgNC+0LLQsNGC0YwnLFxuICAgICAgICAnc2wtU0knOiAnVXJlZGknLFxuICAgICAgICAnc3YtU0UnOiAnUmVkaWdlcmEnLFxuICAgICAgICB0aDogJ+C5geC4geC5ieC5hOC4gicsXG4gICAgICAgIHVrOiAn0KDQtdC00LDQs9GD0LLQsNGC0LgnLFxuICAgICAgICAnemgtQ04nOiAn57yW6L6RJyxcbiAgICAgICAgJ3poLVRXJzogJ+e3qOi8rydcbiAgICB9LFxuICAgICduby1zaGlwJzoge1xuICAgICAgICAnZGUtREUnOiAnRGllc2VyIFNob3AgbGllZmVydCBsZWlkZXIgbmljaHQgYW4gSWhyZW4gU3RhbmRvcnQuJyxcbiAgICAgICAgJ2VuLVVTJzogJ1NvcnJ5LCB0aGlzIHN0b3JlIGRvZXMgbm90IHNoaXAgdG8geW91ciBsb2NhdGlvbi4nLFxuICAgICAgICAnZXMtRVMnOiAnTG8gc2VudGltb3MsIGVzdGEgdGllbmRhIG5vIHNlIGVudsOtYSBhIHN1IHViaWNhY2nDs24uJyxcbiAgICAgICAgZnI6ICdEw6lzb2zDqSwgY2UgbWFnYXNpbiBuZSBsaXZyZSBwYXMgw6Agdm90cmUgZW1wbGFjZW1lbnQuJyxcbiAgICAgICAgaXQ6ICdTaWFtbyBzcGlhY2VudGksIHF1ZXN0byBuZWdvemlvIG5vbiB2aWVuZSBzcGVkaXRvIGFsbGEgdHVhIHBvc2l6aW9uZS4nLFxuICAgICAgICBqYTogJ+eUs+OBl+ios+OBguOCiuOBvuOBm+OCk+OBjOOAgeOBk+OBruOCueODiOOCouOBr+OBiuS9j+OBvuOBhOOBruWcsOWfn+OBq+eZuumAgeOBleOCjOOBvuOBm+OCk+OAgicsXG4gICAgICAgICdyby1STyc6ICdOZSBwYXJlIHLEg3UsIGFjZXN0IG1hZ2F6aW4gbnUgZXN0ZSBsaXZyYXQgw65uIGxvY2HIm2lhIGR2cy4nLFxuICAgICAgICBhcjogJ9i52LDYsdinINiMINmH2LDYpyDYp9mE2YXYqtis2LEg2YTYpyDZiti02K3ZhiDYpdmE2Ykg2YXZiNmC2LnZgy4nLFxuICAgICAgICBjYTogJ0FxdWVzdGEgYm90aWdhIG5vIHNcXCdlbnZpYSBhIGxhIHZvc3RyYSB1YmljYWNpw7MuJyxcbiAgICAgICAgJ2NzLUNaJzogJ0plIG7DoW0gbMOtdG8sIHRlbnRvIG9iY2hvZCB2w6FtIG5lZG9ydcSNw61tZS4nLFxuICAgICAgICAnZGEtREsnOiAnQmVrbGFnZXIsIGRlbm5lIGJ1dGlrIHNlbmRlciBpa2tlIHRpbCBkaW4gbG9rYXRpb24uJyxcbiAgICAgICAgZWw6ICfOm8+Fz4DOv8+NzrzOsc+Dz4TOtSwgzrHPhc+Ez4wgz4TOvyDOus6xz4TOrM+Dz4TOt868zrEgzrTOtc69IM6xz4DOv8+Dz4TOrc67zrvOtc+EzrHOuSDPg8+EzrfOvSDPhM6/z4DOv864zrXPg86vzrEgz4POsc+CLicsXG4gICAgICAgICdoaS1JTic6ICfgpJXgpY3gpLfgpK7gpL4g4KSV4KSw4KWH4KSCLCDgpK/gpLkg4KS44KWN4KSf4KWL4KSwIOCkhuCkquCkleClhyDgpLjgpY3gpKXgpL7gpKgg4KSq4KSwIOCktuCkv+CkqiDgpKjgpLngpYDgpIIg4KSV4KSw4KSk4KS+IOCkueCliOClpCcsXG4gICAgICAgICdrby1LUic6ICfso4TshqHtlanri4jri6QuIOydtCDsg4HsoJDsnYAg6reA7ZWY7J2YIOychOy5mOuhnCDrsLDshqHrkJjsp4Ag7JWK7Iq164uI64ukLicsXG4gICAgICAgICdsYi1MVSc6ICdFbnRzY2jDq2xsZWd0LCBkw6tzZSBCdXR0ZWsgZ8OrdHQgbmV0IG9wIMOEciBMb2NhdGlvbiB2ZXJzY2jDqWNrdC4nLFxuICAgICAgICAnbmwtTkwnOiAnU29ycnksIGRlemUgd2lua2VsIHZlcnplbmR0IG5pZXQgbmFhciBqb3V3IGxvY2F0aWUuJyxcbiAgICAgICAgJ3B0LVBUJzogJ0Rlc2N1bHBlLCBlc3RhIGxvamEgbsOjbyBlbnZpYSBwYXJhIG8gc2V1IGxvY2FsLicsXG4gICAgICAgICdydS1SVSc6ICfQmiDRgdC+0LbQsNC70LXQvdC40Y4sINC00L7RgdGC0LDQstC60LAg0LIg0Y3RgtC+0YIg0LzQsNCz0LDQt9C40L0g0L3QtSDQvtGB0YPRidC10YHRgtCy0LvRj9C10YLRgdGPLicsXG4gICAgICAgICdzbC1TSSc6ICdUYSB0cmdvdmluYSDFvmFsIG5pIGRvc3RhdmxqZW5hIG5hIHZhxaFvIGxva2FjaWpvLicsXG4gICAgICAgICdzdi1TRSc6ICdEZW4gaMOkciBidXRpa2VuIHNraWNrYXMgaW50ZSB0aWxsIGRpbiBwbGF0cy4nLFxuICAgICAgICB0aDogJ+C4guC4reC4reC4oOC4seC4oiDguKPguYnguLLguJnguITguYnguLLguJnguLXguYnguYTguKHguYjguYTguJTguYnguIjguLHguJTguKrguYjguIfguYTguJvguKLguLHguIfguJXguLPguYHguKvguJnguYjguIfguILguK3guIfguITguLjguJMnLFxuICAgICAgICB1azogJ9Cd0LAg0LbQsNC70YwsINGG0LXQuSDQvNCw0LPQsNC30LjQvSDQvdC1INC00L7RgdGC0LDQstC70Y/RlNGC0YzRgdGPINC00L4g0LLQsNGI0L7Qs9C+INC80ZbRgdGG0LXQt9C90LDRhdC+0LTQttC10L3QvdGPLicsXG4gICAgICAgICd6aC1DTic6ICfmirHmrYnvvIzov5nlrrbllYblupfkuI3lj5HotKfliLDmgqjmiYDlnKjnmoTkvY3nva7jgIInLFxuICAgICAgICAnemgtVFcnOiAn5oqx5q2J77yM6YCZ5a625ZWG5bqX5LiN55m86LKo5Yiw5oKo5omA5Zyo55qE5L2N572u44CCJ1xuICAgIH0sXG4gICAgcHJvY2Vzc2luZzoge1xuICAgICAgICAnZGUtREUnOiAnVmVyYXJiZWl0dW5nJyxcbiAgICAgICAgJ2VuLVVTJzogJ1Byb2Nlc3NpbmcnLFxuICAgICAgICAnZXMtRVMnOiAnUHJvY2VzYW1pZW50bycsXG4gICAgICAgIGZyOiAnVHJhaXRlbWVudCcsXG4gICAgICAgIGl0OiAnRWxhYm9yYXppb25lJyxcbiAgICAgICAgamE6ICfpgLLooYzkuK0nLFxuICAgICAgICAncm8tUk8nOiAnUHJlbHVjcmFyZScsXG4gICAgICAgIGFyOiAn2YrYudin2YTYrCcsXG4gICAgICAgIGNhOiAnUHJvY2Vzc2FtZW50JyxcbiAgICAgICAgJ2NzLUNaJzogJ3pwcmFjb3bDoXbDoSBzZScsXG4gICAgICAgICdkYS1ESyc6ICdGb3JhcmJlamRuaW5nJyxcbiAgICAgICAgZWw6ICfOlc+AzrXOvs61z4HOs86xz4POr86xJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkquCljeCksOCkuOCkguCkuOCljeCkleCksOCkoycsXG4gICAgICAgICdrby1LUic6ICfsspjrpqwnLFxuICAgICAgICAnbGItTFUnOiAnVmVyYWFyYmVjaHR1bmcnLFxuICAgICAgICAnbmwtTkwnOiAnVmVyd2Vya2VuJyxcbiAgICAgICAgJ3B0LVBUJzogJ0VtIHByb2Nlc3NhbWVudG8nLFxuICAgICAgICAncnUtUlUnOiAn0J7QsdGA0LDQsdC+0YLQutCwJyxcbiAgICAgICAgJ3NsLVNJJzogJ09icmF2bmF2YXRpJyxcbiAgICAgICAgJ3N2LVNFJzogJ0JlYXJiZXRuaW5nJyxcbiAgICAgICAgdGg6ICfguIHguLPguKXguLHguIfguJvguKPguLDguKHguKfguKXguJzguKUnLFxuICAgICAgICB1azogJ9Ce0LHRgNC+0LHQutCwJyxcbiAgICAgICAgJ3poLUNOJzogJ+WKoOW3pScsXG4gICAgICAgICd6aC1UVyc6ICfliqDlt6UnXG4gICAgfSxcbiAgICAncGF5bWVudC1mYWlsZWQnOiB7XG4gICAgICAgICdkZS1ERSc6ICdaYWhsdW5nIGZlaGxnZXNjaGxhZ2VuJyxcbiAgICAgICAgJ2VuLVVTJzogJ1BheW1lbnQgZmFpbGVkJyxcbiAgICAgICAgJ2VzLUVTJzogJ1BhZ28gZmFsbGlkbycsXG4gICAgICAgIGZyOiAnUGFpZW1lbnQgw6ljaG91w6knLFxuICAgICAgICBpdDogJ1BhZ2FtZW50byBub24gcml1c2NpdG8nLFxuICAgICAgICBqYTogJ+aUr+aJleOBhOWkseaVlycsXG4gICAgICAgICdyby1STyc6ICdQbGF0YSBlc3VhdGEnLFxuICAgICAgICBhcjogJ9i52YXZhNmK2Kkg2KfZhNiv2YHYuSDZgdi02YTYqicsXG4gICAgICAgIGNhOiAnRWwgcGFnYW1lbnQgaGEgZmFsbGF0JyxcbiAgICAgICAgJ2NzLUNaJzogJ1BsYXRiYSBzZWxoYWxhJyxcbiAgICAgICAgJ2RhLURLJzogJ0JldGFsaW5nIG1pc2x5a2tlZGVzJyxcbiAgICAgICAgZWw6ICfOlyDPgM67zrfPgc+JzrzOriDOsc+Azq3PhM+Fz4fOtScsXG4gICAgICAgICdoaS1JTic6ICfgpK3gpYHgpJfgpKTgpL7gpKgg4KSF4KS44KSr4KSyIOCkueClgeCkhicsXG4gICAgICAgICdrby1LUic6ICfqsrDsoJwg7Iuk7YyoJyxcbiAgICAgICAgJ2xiLUxVJzogJ0JlenVlbHVuZyBnZXNjaGVpdGVydCcsXG4gICAgICAgICdubC1OTCc6ICdCZXRhbGluZyBtaXNsdWt0JyxcbiAgICAgICAgJ3B0LVBUJzogJ1BhZ2FtZW50byBmYWxob3UnLFxuICAgICAgICAncnUtUlUnOiAn0J/Qu9Cw0YLQtdC2INC90LUg0L/RgNC+0YjQtdC7JyxcbiAgICAgICAgJ3NsLVNJJzogJ1BsYcSNaWxvIG5pIHVzcGVsbycsXG4gICAgICAgICdzdi1TRSc6ICdCZXRhbG5pbmcgbWlzc2x5Y2thZGVzJyxcbiAgICAgICAgdGg6ICfguIHguLLguKPguIrguLPguKPguLDguYDguIfguLTguJnguKXguYnguKHguYDguKvguKXguKcnLFxuICAgICAgICB1azogJ9Cd0LUg0LLQtNCw0LvQvtGB0Y8g0LfQtNGW0LnRgdC90LjRgtC4INC/0LvQsNGC0ZbQticsXG4gICAgICAgICd6aC1DTic6ICfmlK/ku5jlpLHotKUnLFxuICAgICAgICAnemgtVFcnOiAn5pSv5LuY5aSx5pWXJ1xuICAgIH0sXG4gICAgJ2ZpcnN0LW5hbWUnOiB7XG4gICAgICAgICdkZS1ERSc6ICdWb3JuYW1lJyxcbiAgICAgICAgJ2VuLVVTJzogJ0ZpcnN0IG5hbWUnLFxuICAgICAgICAnZXMtRVMnOiAnTm9tYnJlJyxcbiAgICAgICAgZnI6ICdQcsOpbm9tJyxcbiAgICAgICAgaXQ6ICdOb21lJyxcbiAgICAgICAgamE6ICflkI0nLFxuICAgICAgICAncm8tUk8nOiAnTnVtZScsXG4gICAgICAgIGFyOiAn2KfZhNin2LPZhSDYp9mE2KPZiNmEJyxcbiAgICAgICAgY2E6ICdOb20nLFxuICAgICAgICAnY3MtQ1onOiAnSm3DqW5vJyxcbiAgICAgICAgJ2RhLURLJzogJ0Zvcm5hdm4nLFxuICAgICAgICBlbDogJ86fzr3Ov868zrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSq4KS54KSy4KS+IOCkqOCkvuCkricsXG4gICAgICAgICdrby1LUic6ICfsnbTrpoQnLFxuICAgICAgICAnbGItTFUnOiAnVmlybnVtbScsXG4gICAgICAgICdubC1OTCc6ICdWb29ybmFhbScsXG4gICAgICAgICdwdC1QVCc6ICdQcmltZWlybyBub21lJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CY0LzRjycsXG4gICAgICAgICdzbC1TSSc6ICdJbWUnLFxuICAgICAgICAnc3YtU0UnOiAnRsO2cm5hbW4nLFxuICAgICAgICB0aDogJ+C4iuC4t+C5iOC4reC4iOC4o+C4tOC4hycsXG4gICAgICAgIHVrOiAn0IbQvFxcJ9GPJyxcbiAgICAgICAgJ3poLUNOJzogJ+WQjScsXG4gICAgICAgICd6aC1UVyc6ICflkI0nXG4gICAgfSxcbiAgICAnbGFzdC1uYW1lJzoge1xuICAgICAgICAnZGUtREUnOiAnTmFjaG5hbWUnLFxuICAgICAgICAnZW4tVVMnOiAnTGFzdCBuYW1lJyxcbiAgICAgICAgJ2VzLUVTJzogJ0FwZWxsaWRvJyxcbiAgICAgICAgZnI6ICdOb20nLFxuICAgICAgICBpdDogJ0NvZ25vbWUnLFxuICAgICAgICBqYTogJ+WnkycsXG4gICAgICAgICdyby1STyc6ICdOdW1lbGUgZGUgZmFtaWxpZScsXG4gICAgICAgIGFyOiAn2KfZhNmD2YbZitipJyxcbiAgICAgICAgY2E6ICdDb2dub20nLFxuICAgICAgICAnY3MtQ1onOiAnUMWZw61qbWVuw60nLFxuICAgICAgICAnZGEtREsnOiAnRWZ0ZXJuYXZuJyxcbiAgICAgICAgZWw6ICfOlc+Azq/OuM61z4TOvycsXG4gICAgICAgICdoaS1JTic6ICfgpIngpKrgpKjgpL7gpK4nLFxuICAgICAgICAna28tS1InOiAn7ISxJyxcbiAgICAgICAgJ2xiLUxVJzogJ0ZhbWlsbGplbm51bW0nLFxuICAgICAgICAnbmwtTkwnOiAnQWNodGVybmFhbScsXG4gICAgICAgICdwdC1QVCc6ICfDmmx0aW1vIG5vbWUnLFxuICAgICAgICAncnUtUlUnOiAn0KTQsNC80LjQu9C40Y8nLFxuICAgICAgICAnc2wtU0knOiAnUHJpaW1laycsXG4gICAgICAgICdzdi1TRSc6ICdFZnRlcm5hbW4nLFxuICAgICAgICB0aDogJ+C4meC4suC4oeC4quC4geC4uOC4pScsXG4gICAgICAgIHVrOiAn0J/RgNGW0LfQstC40YnQtScsXG4gICAgICAgICd6aC1DTic6ICflp5MnLFxuICAgICAgICAnemgtVFcnOiAn5aeTJ1xuICAgIH0sXG4gICAgcGhvbmU6IHtcbiAgICAgICAgJ2RlLURFJzogJ1RlbGVmb24nLFxuICAgICAgICAnZW4tVVMnOiAnUGhvbmUgbnVtYmVyJyxcbiAgICAgICAgJ2VzLUVTJzogJ1RlbMOpZm9ubycsXG4gICAgICAgIGZyOiAnVMOpbMOpcGhvbmUnLFxuICAgICAgICBpdDogJ1RlbGVmb25vJyxcbiAgICAgICAgamE6ICfpm7voqbHnlarlj7cnLFxuICAgICAgICAncm8tUk8nOiAnVGVsZWZvbicsXG4gICAgICAgIGFyOiAn2LHZgtmFINin2YTZh9in2KrZgScsXG4gICAgICAgIGNhOiAnTsO6bWVybyBkZSB0ZWzDqGZvbicsXG4gICAgICAgICdjcy1DWic6ICdUZWxlZm9ubsOtIMSNw61zbG8nLFxuICAgICAgICAnZGEtREsnOiAnVGVsZWZvbm51bW1lcicsXG4gICAgICAgIGVsOiAnzqTOt867zrXPhs+Jzr3Ouc66z4wgzr3Ov8+NzrzOtc+Bzr8nLFxuICAgICAgICAnaGktSU4nOiAn4KSr4KS84KWL4KSoIOCkqOCkguCkrOCksCcsXG4gICAgICAgICdrby1LUic6ICfsoITtmZQg67KI7Zi4JyxcbiAgICAgICAgJ2xiLUxVJzogJ1RlbGVmb25zbnVtbWVyJyxcbiAgICAgICAgJ25sLU5MJzogJ1RlbGVmb29ubnVtbWVyJyxcbiAgICAgICAgJ3B0LVBUJzogJ07Dum1lcm8gZGUgdGVsZWZvbmUnLFxuICAgICAgICAncnUtUlUnOiAn0J3QvtC80LXRgCDRgtC10LvQtdGE0L7QvdCwJyxcbiAgICAgICAgJ3NsLVNJJzogJ1RlbGVmb25za2EgxaF0ZXZpbGthJyxcbiAgICAgICAgJ3N2LVNFJzogJ1RlbGVmb25udW1tZXInLFxuICAgICAgICB0aDogJ+C4q+C4oeC4suC4ouC5gOC4peC4guC5guC4l+C4o+C4qOC4seC4nuC4l+C5jCcsXG4gICAgICAgIHVrOiAn0KLQtdC70LXRhNC+0L3QvdC40Lkg0L3QvtC80LXRgCcsXG4gICAgICAgICd6aC1DTic6ICfnlLXor53lj7fnoIEnLFxuICAgICAgICAnemgtVFcnOiAn6Zu76Kmx6Jmf56K8J1xuICAgIH0sXG4gICAgc3RyZWV0OiB7XG4gICAgICAgICdkZS1ERSc6ICdTdHJhw59lIHVuZCBIYXVzbnVtbWVyJyxcbiAgICAgICAgJ2VuLVVTJzogJ1N0cmVldCBhZGRyZXNzJyxcbiAgICAgICAgJ2VzLUVTJzogJ0RpcmVjY2nDs24nLFxuICAgICAgICBmcjogJ0FkcmVzc2UnLFxuICAgICAgICBpdDogJ0luZGlyaXp6bycsXG4gICAgICAgIGphOiAn5L2P5omA6Kmz57SwJyxcbiAgICAgICAgJ3JvLVJPJzogJ0FkcmVzxIMnLFxuICAgICAgICBhcjogJ9i52YbZiNin2YYg2KfZhNi02KfYsdi5JyxcbiAgICAgICAgY2E6ICdhZHJlw6dhJyxcbiAgICAgICAgJ2NzLUNaJzogJ2FkcmVzYSB1bGljZScsXG4gICAgICAgICdkYS1ESyc6ICdWZWpuYXZuJyxcbiAgICAgICAgZWw6ICfOtM65zrXPjc64z4XOvc+DzrcnLFxuICAgICAgICAnaGktSU4nOiAn4KSX4KSy4KWAIOCkleCkviDgpKrgpKTgpL4nLFxuICAgICAgICAna28tS1InOiAn7KO87IaMJyxcbiAgICAgICAgJ2xiLUxVJzogJ1N0cm9vc3MgQWRyZXNzJyxcbiAgICAgICAgJ25sLU5MJzogJ3dvb25hZHJlcycsXG4gICAgICAgICdwdC1QVCc6ICdlbmRlcmXDp28gZGEgUnVhJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Cw0LTRgNC10YEg0YPQu9C40YbRiycsXG4gICAgICAgICdzbC1TSSc6ICduYXNsb3YgY2VzdGUnLFxuICAgICAgICAnc3YtU0UnOiAnR2F0dWFkcmVzcycsXG4gICAgICAgIHRoOiAn4LiX4Li14LmI4Lit4Lii4Li54LmI4LiW4LiZ4LiZJyxcbiAgICAgICAgdWs6ICfQkNC00YDQtdGB0LAg0LLRg9C70LjRhtGWJyxcbiAgICAgICAgJ3poLUNOJzogJ+ihl+mBk+WcsOWdgCcsXG4gICAgICAgICd6aC1UVyc6ICfooZfpgZPlnLDlnYAnXG4gICAgfSxcbiAgICBhcHQ6IHtcbiAgICAgICAgJ2RlLURFJzogJ0FwYXJ0bWVudCAjJyxcbiAgICAgICAgJ2VuLVVTJzogJ0FwdC4gIycsXG4gICAgICAgICdlcy1FUyc6ICdBcGFydGFtZW50byAjJyxcbiAgICAgICAgZnI6ICdBcHBhcnRlbWVudCAjJyxcbiAgICAgICAgaXQ6ICdBcHBhcnRhbWVudG8gIycsXG4gICAgICAgIGphOiAn6YOo5bGL55Wq5Y+344Gq44GpJyxcbiAgICAgICAgJ3JvLVJPJzogJ0FwYXJ0YW1lbnQgIycsXG4gICAgICAgIGFyOiAn2LTZgtipICMnLFxuICAgICAgICBjYTogJ0FwYXJ0YW1lbnQgIycsXG4gICAgICAgICdjcy1DWic6ICdCeXQgIycsXG4gICAgICAgICdkYS1ESyc6ICdMZWpsaWdoZWQgIycsXG4gICAgICAgIGVsOiAnzpTOuc6xzrzOrc+BzrnPg868zrEgIycsXG4gICAgICAgICdoaS1JTic6ICfgpIXgpKrgpL7gpLDgpY3gpJ/gpK7gpYfgpILgpJ8gIycsXG4gICAgICAgICdrby1LUic6ICfslYTtjIztirggIycsXG4gICAgICAgICdsYi1MVSc6ICdBcHBhcnRlbWVudCAjJyxcbiAgICAgICAgJ25sLU5MJzogJ0FwcGFydGVtZW50ICMnLFxuICAgICAgICAncHQtUFQnOiAnQXBhcnRhbWVudG8gIycsXG4gICAgICAgICdydS1SVSc6ICfQmtCy0LDRgNGC0LjRgNCwICMnLFxuICAgICAgICAnc2wtU0knOiAnU3Rhbm92YW5qZSDFoXQuJyxcbiAgICAgICAgJ3N2LVNFJzogJ0zDpGdlbmhldCAjJyxcbiAgICAgICAgdGg6ICfguK3guJ7guLLguKPguYzguJfguYDguKHguYnguJkgIycsXG4gICAgICAgIHVrOiAn0JrQstCw0YDRgtC40YDQsCDihJYnLFxuICAgICAgICAnemgtQ04nOiAn5YWs5a+TIO+8gycsXG4gICAgICAgICd6aC1UVyc6ICflhazlr5Mg77yDJ1xuICAgIH0sXG4gICAgcG9zdGFsOiB7XG4gICAgICAgICdkZS1ERSc6ICdQb3N0bGVpdHphaGwnLFxuICAgICAgICAnZW4tVVMnOiAnUG9zdGFsIGNvZGUnLFxuICAgICAgICAnZXMtRVMnOiAnQ8OzZGlnbyBQb3N0YWwnLFxuICAgICAgICBmcjogJ0NvZGUgcG9zdGFsJyxcbiAgICAgICAgaXQ6ICdDb2RpY2UgcG9zdGFsZScsXG4gICAgICAgIGphOiAn6YO15L6/55Wq5Y+3JyxcbiAgICAgICAgJ3JvLVJPJzogJ0NvZCBwb3N0YWwnLFxuICAgICAgICBhcjogJ9in2YTYsdmF2LIg2KfZhNio2LHZitiv2YonLFxuICAgICAgICBjYTogJ0NvZGkgUG9zdGFsJyxcbiAgICAgICAgJ2NzLUNaJzogJ1BvxaF0b3Zuw60gc23Em3JvdmFjw60gxI3DrXNsbycsXG4gICAgICAgICdkYS1ESyc6ICdQb3N0bnVtbWVyJyxcbiAgICAgICAgZWw6ICfOpM6xz4fPhc60z4HOv868zrnOus+Mz4IgzprPjs60zrnOus6xz4InLFxuICAgICAgICAnaGktSU4nOiAn4KSh4KS+4KSVIOCkleCli+CkoScsXG4gICAgICAgICdrby1LUic6ICfsmrDtjrgg67KI7Zi4JyxcbiAgICAgICAgJ2xiLUxVJzogJ1Bvc3RsZWl0enVlbCcsXG4gICAgICAgICdubC1OTCc6ICdQb3N0Y29kZScsXG4gICAgICAgICdwdC1QVCc6ICdDw7NkaWdvIHBvc3RhbCcsXG4gICAgICAgICdydS1SVSc6ICfQn9C+0YfRgtC+0LLRi9C5INCa0L7QtCcsXG4gICAgICAgICdzbC1TSSc6ICdQb8WhdG5hIMWhdGV2aWxrYScsXG4gICAgICAgICdzdi1TRSc6ICdQb3N0bnVtbWVyJyxcbiAgICAgICAgdGg6ICfguKPguKvguLHguKrguYTguJvguKPguKnguJPguLXguKLguYwnLFxuICAgICAgICB1azogJ9Cf0L7RiNGC0L7QstC40Lkg0ZbQvdC00LXQutGBJyxcbiAgICAgICAgJ3poLUNOJzogJ+mCruaUv+e8lueggScsXG4gICAgICAgICd6aC1UVyc6ICfpg7XmlL/nt6jnorwnXG4gICAgfSxcbiAgICBjaXR5OiB7XG4gICAgICAgICdkZS1ERSc6ICdTdGFkdCcsXG4gICAgICAgICdlbi1VUyc6ICdDaXR5JyxcbiAgICAgICAgJ2VzLUVTJzogJ0NpdWRhZCcsXG4gICAgICAgIGZyOiAnVmlsbGUnLFxuICAgICAgICBpdDogJ0NpdHRhJyxcbiAgICAgICAgamE6ICfluIInLFxuICAgICAgICAncm8tUk8nOiAnT3JhyJknLFxuICAgICAgICBhcjogJ9mF2K/ZitmG2KknLFxuICAgICAgICBjYTogJ2NpdXRhdCcsXG4gICAgICAgICdjcy1DWic6ICdNxJtzdG8nLFxuICAgICAgICAnZGEtREsnOiAnQnknLFxuICAgICAgICBlbDogJ86gz4zOu863JyxcbiAgICAgICAgJ2hpLUlOJzogJ+CktuCkueCksCcsXG4gICAgICAgICdrby1LUic6ICfrj4Tsi5wnLFxuICAgICAgICAnbGItTFUnOiAnU3RhZCcsXG4gICAgICAgICdubC1OTCc6ICdTdGFkJyxcbiAgICAgICAgJ3B0LVBUJzogJ0NpZGFkZScsXG4gICAgICAgICdydS1SVSc6ICfQk9C+0YDQvtC0JyxcbiAgICAgICAgJ3NsLVNJJzogJ01lc3RvJyxcbiAgICAgICAgJ3N2LVNFJzogJ1N0YWQnLFxuICAgICAgICB0aDogJ+C5gOC4oeC4t+C4reC4hycsXG4gICAgICAgIHVrOiAn0JzRltGB0YLQvicsXG4gICAgICAgICd6aC1DTic6ICfln47luIInLFxuICAgICAgICAnemgtVFcnOiAn5Z+O5biCJ1xuICAgIH0sXG4gICAgcHJvdmluY2U6IHtcbiAgICAgICAgJ2RlLURFJzogJ1Byb3ZpbnonLFxuICAgICAgICAnZW4tVVMnOiAnUHJvdmluY2UnLFxuICAgICAgICAnZXMtRVMnOiAnUHJvdmluY2lhJyxcbiAgICAgICAgZnI6ICdEw6lwYXJ0ZW1lbnQnLFxuICAgICAgICBpdDogJ1Byb3ZpbmNpYScsXG4gICAgICAgIGphOiAn55yMJyxcbiAgICAgICAgJ3JvLVJPJzogJ0p1ZGV0JyxcbiAgICAgICAgYXI6ICfZhdmC2KfYt9i52KknLFxuICAgICAgICBjYTogJ1Byb3bDrW5jaWEnLFxuICAgICAgICAnY3MtQ1onOiAnUHJvdmluY2llJyxcbiAgICAgICAgJ2RhLURLJzogJ1Byb3ZpbnMnLFxuICAgICAgICBlbDogJ86Vz4DOsc+Bz4fOr86xJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkquCljeCksOCkvuCkguCkpCcsXG4gICAgICAgICdrby1LUic6ICfso7wnLFxuICAgICAgICAnbGItTFUnOiAnUHJvdsOrbnonLFxuICAgICAgICAnbmwtTkwnOiAnUHJvdmluY2llJyxcbiAgICAgICAgJ3B0LVBUJzogJ1Byb3bDrW5jaWEnLFxuICAgICAgICAncnUtUlUnOiAn0J/RgNC+0LLQuNC90YbQuNGPJyxcbiAgICAgICAgJ3NsLVNJJzogJ1Bva3JhamluYScsXG4gICAgICAgICdzdi1TRSc6ICdQcm92aW5zJyxcbiAgICAgICAgdGg6ICfguIjguLHguIfguKvguKfguLHguJQnLFxuICAgICAgICB1azogJ9Cf0YDQvtCy0ZbQvdGG0ZbRjycsXG4gICAgICAgICd6aC1DTic6ICfnnIEnLFxuICAgICAgICAnemgtVFcnOiAn55yBJ1xuICAgIH0sXG4gICAgJ3Byb3ZpbmNlLXNlbGVjdCc6IHtcbiAgICAgICAgJ2RlLURFJzogJ1fDpGhsZW4gU2llIGVpbmUgUHJvdmlueicsXG4gICAgICAgICdlbi1VUyc6ICdTZWxlY3QgYSBQcm92aW5jZScsXG4gICAgICAgICdlcy1FUyc6ICdTZWxlY2Npb25lIHVuYSBwcm92aW5jaWEnLFxuICAgICAgICBmcjogJ1PDqWxlY3Rpb25uZXogdW5lIHByb3ZpbmNlJyxcbiAgICAgICAgaXQ6ICdTZWxlemlvbmEgdW5hIHByb3ZpbmNpYScsXG4gICAgICAgIGphOiAn6YO96YGT5bqc55yM44KS6YG45oqeJyxcbiAgICAgICAgJ3JvLVJPJzogJ1NlbGVjdGHIm2kgbyBwcm92aW5jaWUnLFxuICAgICAgICBhcjogJ9in2K7YqtixINin2YTZhdit2KfZgdi42KknLFxuICAgICAgICBjYTogJ1NlbGVjY2lvbmV1IHVuYSBwcm92w61uY2lhJyxcbiAgICAgICAgJ2NzLUNaJzogJ1Z5YmVydGUgcHJvdmluY2lpJyxcbiAgICAgICAgJ2RhLURLJzogJ1bDpmxnIGVuIHByb3ZpbnMnLFxuICAgICAgICBlbDogJ86Vz4DOuc67zq3Ovs+EzrUgzrzOuc6xIM61z4DOsc+Bz4fOr86xJyxcbiAgICAgICAgJ2hpLUlOJzogJ+Ckj+CklSDgpKrgpY3gpLDgpL7gpILgpKQg4KSV4KS+IOCkmuCkr+CkqCDgpJXgpLDgpYfgpIInLFxuICAgICAgICAna28tS1InOiAn7KO866W8IOyEoO2Dne2VmOyLreyLnOyYpCcsXG4gICAgICAgICdsYi1MVSc6ICdXaWVsdCBlbmcgUHJvdsOrbnonLFxuICAgICAgICAnbmwtTkwnOiAnU2VsZWN0ZWVyIGVlbiBwcm92aW5jaWUnLFxuICAgICAgICAncHQtUFQnOiAnU2VsZWNpb25lIHVtYSBwcm92w61uY2lhJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CS0YvQsdC10YDQuNGC0LUg0L/RgNC+0LLQuNC90YbQuNGOJyxcbiAgICAgICAgJ3NsLVNJJzogJ0l6YmVyaXRlIHByb3ZpbmNvJyxcbiAgICAgICAgJ3N2LVNFJzogJ1bDpGxqIGVuIHByb3ZpbnMnLFxuICAgICAgICB0aDogJ+C5gOC4peC4t+C4reC4geC4iOC4seC4h+C4q+C4p+C4seC4lCcsXG4gICAgICAgIHVrOiAn0JLQuNCx0LXRgNGW0YLRjCDQv9GA0L7QstGW0L3RhtGW0Y4nLFxuICAgICAgICAnemgtQ04nOiAn6YCJ5oup55yB5Lu9JyxcbiAgICAgICAgJ3poLVRXJzogJ+mBuOaTh+ecgeS7vSdcbiAgICB9LFxuICAgIHN0YXRlOiB7XG4gICAgICAgICdkZS1ERSc6ICdCdW5kZXNsYW5kJyxcbiAgICAgICAgJ2VuLVVTJzogJ1N0YXRlJyxcbiAgICAgICAgJ2VzLUVTJzogJ0VzdGFkbycsXG4gICAgICAgIGZyOiAnw4l0YXQnLFxuICAgICAgICBpdDogJ1N0YXRvJyxcbiAgICAgICAgamE6ICfnnIwnLFxuICAgICAgICAncm8tUk8nOiAnU3RhdCcsXG4gICAgICAgIGFyOiAn2YjZhNin2YrYqScsXG4gICAgICAgIGNhOiAnRXN0YXQnLFxuICAgICAgICAnY3MtQ1onOiAnU3TDoXQnLFxuICAgICAgICAnZGEtREsnOiAnU3RhdCcsXG4gICAgICAgIGVsOiAnzrrOsc+EzqzPg8+EzrHPg863JyxcbiAgICAgICAgJ2hpLUlOJzogJ+CksOCkvuCknOCljeCkrycsXG4gICAgICAgICdrby1LUic6ICfsg4Htg5wnLFxuICAgICAgICAnbGItTFUnOiAnU3RhYXQnLFxuICAgICAgICAnbmwtTkwnOiAnU3RhYXQnLFxuICAgICAgICAncHQtUFQnOiAnRXN0YWRhJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Ch0L7RgdGC0L7Rj9C90LjQtScsXG4gICAgICAgICdzbC1TSSc6ICdEcsW+YXZhJyxcbiAgICAgICAgJ3N2LVNFJzogJ3N0YXQnLFxuICAgICAgICB0aDogJ+C4quC4luC4suC4meC4sCcsXG4gICAgICAgIHVrOiAn0JTQtdGA0LbQsNCy0LAnLFxuICAgICAgICAnemgtQ04nOiAn5beeJyxcbiAgICAgICAgJ3poLVRXJzogJ+W3nidcbiAgICB9LFxuICAgICdzdGF0ZS1zZWxlY3QnOiB7XG4gICAgICAgICdkZS1ERSc6ICdXw6RobGUgZWluZW4gU3RhYXQnLFxuICAgICAgICAnZW4tVVMnOiAnU2VsZWN0IGEgU3RhdGUnLFxuICAgICAgICAnZXMtRVMnOiAnU2VsZWNjaW9uYSB1biBFc3RhZG8nLFxuICAgICAgICBmcjogJ1PDqWxlY3Rpb25uZXIgdW4gw6l0YXQnLFxuICAgICAgICBpdDogJ1NlbGV6aW9uYSB1bm8gU3RhdG8nLFxuICAgICAgICBqYTogJ+W3nuOCkumBuOaKnicsXG4gICAgICAgICdyby1STyc6ICdTZWxlY3RlYXphIHVuIHN0YXQnLFxuICAgICAgICBhcjogJ9it2K/YryDZiNmE2KfZitipJyxcbiAgICAgICAgY2E6ICdTZWxlY2Npb25ldSB1biBlc3RhdCcsXG4gICAgICAgICdjcy1DWic6ICdWeWJlcnRlIHN0w6F0JyxcbiAgICAgICAgJ2RhLURLJzogJ1bDpmxnIGVuIHN0YXQnLFxuICAgICAgICBlbDogJ86Vz4DOuc67zq3Ovs+EzrUgzrzOuc6xIM+Azr/Ou865z4TOtc6vzrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSP4KSVIOCksOCkvuCknOCljeCkryDgpJXgpL4g4KSa4KSv4KSoIOCkleCksOClh+CkgicsXG4gICAgICAgICdrby1LUic6ICfso7wg7ISg7YOdJyxcbiAgICAgICAgJ2xiLUxVJzogJ1dpZWx0IGUgU3RhYXQnLFxuICAgICAgICAnbmwtTkwnOiAnU2VsZWN0ZWVyIGVlbiBzdGFhdCcsXG4gICAgICAgICdwdC1QVCc6ICdTZWxlY2lvbmUgdW0gRXN0YWRvJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CS0YvQsdC10YDQuNGC0LUg0YjRgtCw0YInLFxuICAgICAgICAnc2wtU0knOiAnSXpiZXJpdGUgZHLFvmF2bycsXG4gICAgICAgICdzdi1TRSc6ICdWw6RsaiBlbiBzdGF0JyxcbiAgICAgICAgdGg6ICfguYDguKXguLfguK3guIHguKPguLHguJAnLFxuICAgICAgICB1azogJ9CS0LjQsdC10YDRltGC0Ywg0YjRgtCw0YInLFxuICAgICAgICAnemgtQ04nOiAn6YCJ5oup5LiA5Liq5beeJyxcbiAgICAgICAgJ3poLVRXJzogJ+mBuOaTh+S4gOWAi+W3nidcbiAgICB9LFxuICAgIGNvdW50eToge1xuICAgICAgICAnZGUtREUnOiAnQmV6aXJrJyxcbiAgICAgICAgJ2VuLVVTJzogJ0NvdW50eScsXG4gICAgICAgICdlcy1FUyc6ICdDb25kYWRvJyxcbiAgICAgICAgZnI6ICdDb210w6knLFxuICAgICAgICBpdDogJ0NvbnRlYScsXG4gICAgICAgIGphOiAn6YOhJyxcbiAgICAgICAgJ3JvLVJPJzogJ0p1ZGXIm3VsJyxcbiAgICAgICAgYXI6ICfZhdmC2KfYt9i52KknLFxuICAgICAgICBjYTogJ2NvbXRhdCcsXG4gICAgICAgICdjcy1DWic6ICdva3JlcycsXG4gICAgICAgICdkYS1ESyc6ICdBbXQnLFxuICAgICAgICBlbDogJ86azr/OvM63z4TOtc6vzrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSV4KS+4KSJ4KSC4KSf4KWAJyxcbiAgICAgICAgJ2tvLUtSJzogJ+q1sCcsXG4gICAgICAgICdsYi1MVSc6ICdHcm9mc2NoYWZ0JyxcbiAgICAgICAgJ25sLU5MJzogJ2Rpc3RyaWN0JyxcbiAgICAgICAgJ3B0LVBUJzogJ2NvbmRhZG8nLFxuICAgICAgICAncnUtUlUnOiAn0L7QutGA0YPQsycsXG4gICAgICAgICdzbC1TSSc6ICdPa3Jvxb5qZScsXG4gICAgICAgICdzdi1TRSc6ICdHcmV2c2thcCcsXG4gICAgICAgIHRoOiAn4LmA4LiC4LiVJyxcbiAgICAgICAgdWs6ICfQn9C+0LLRltGCJyxcbiAgICAgICAgJ3poLUNOJzogJ+WOvycsXG4gICAgICAgICd6aC1UVyc6ICfnuKMnXG4gICAgfSxcbiAgICBjb3VudHJ5OiB7XG4gICAgICAgICdkZS1ERSc6ICdXw6RobGVuIFNpZSBlaW4gTGFuZCcsXG4gICAgICAgICdlbi1VUyc6ICdTZWxlY3QgYSBjb3VudHJ5JyxcbiAgICAgICAgJ2VzLUVTJzogJ1NlbGVjY2lvbmUgdW4gcGHDrXMnLFxuICAgICAgICBmcjogJ1BheXMnLFxuICAgICAgICBpdDogJ1NlbGV6aW9uYSB1biBwYWVzZScsXG4gICAgICAgIGphOiAn5Zu9JyxcbiAgICAgICAgJ3JvLVJPJzogJ1NlbGVjdGVhemEgbyB0YXJhJyxcbiAgICAgICAgYXI6ICfYp9iu2KrYsSDYr9mI2YTYqScsXG4gICAgICAgIGNhOiAnU2VsZWNjaW9uZXUgdW4gcGHDrXMnLFxuICAgICAgICAnY3MtQ1onOiAnVnliZXIgemVtaScsXG4gICAgICAgICdkYS1ESyc6ICdWw6ZsZyBldCBsYW5kJyxcbiAgICAgICAgZWw6ICfOlc+AzrnOu86tzr7PhM61IM+Hz47Pgc6xJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkleCli+CkiCDgpKbgpYfgpLYg4KSa4KWB4KSo4KWH4KSCJyxcbiAgICAgICAgJ2tvLUtSJzogJ+q1reqwgOulvCDqs6DrpbTsi5wg7JikJyxcbiAgICAgICAgJ2xiLUxVJzogJ1dpZWx0IGUgTGFuZCcsXG4gICAgICAgICdubC1OTCc6ICdTZWxlY3RlZXIgZWVuIGxhbmQnLFxuICAgICAgICAncHQtUFQnOiAnU2VsZWNpb25lIHVtIHBhaXMnLFxuICAgICAgICAncnUtUlUnOiAn0JLRi9Cx0LXRgNC40YLQtSDRgdGC0YDQsNC90YMnLFxuICAgICAgICAnc2wtU0knOiAnSXpiZXJpdGUgZHLFvmF2bycsXG4gICAgICAgICdzdi1TRSc6ICdWw6RsaiBldHQgbGFuZCcsXG4gICAgICAgIHRoOiAn4LmA4Lil4Li34Lit4LiB4Lib4Lij4Liw4LmA4LiX4LioJyxcbiAgICAgICAgdWs6ICfQktC40LHQtdGA0ZbRgtGMINC60YDQsNGX0L3RgycsXG4gICAgICAgICd6aC1DTic6ICfpgInmi6nkuIDkuKrlm73lrrYnLFxuICAgICAgICAnemgtVFcnOiAn6YG45pOH5LiA5YCL5ZyL5a62J1xuICAgIH0sXG4gICAgJ2NvdW50cnktbGFiZWwnOiB7XG4gICAgICAgICdkZS1ERSc6ICdMYW5kJyxcbiAgICAgICAgJ2VuLVVTJzogJ0NvdW50cnknLFxuICAgICAgICAnZXMtRVMnOiAnUGHDrXMnLFxuICAgICAgICBmcjogJ1BheXMnLFxuICAgICAgICBpdDogJ05hemlvbmUnLFxuICAgICAgICBqYTogJ+WbvScsXG4gICAgICAgICdyby1STyc6ICfImmFyxIMnLFxuICAgICAgICBhcjogJ9iv2YjZhNipJyxcbiAgICAgICAgY2E6ICdQYcOtcycsXG4gICAgICAgICdjcy1DWic6ICdaZW3EmycsXG4gICAgICAgIGVsOiAnzqfPjs+BzrEnLFxuICAgICAgICAnaGktSU4nOiAn4KSm4KWH4KS2JyxcbiAgICAgICAgJ2tvLUtSJzogJ+q1reqwgCcsXG4gICAgICAgICdsYi1MVSc6ICdMYW5kJyxcbiAgICAgICAgJ25sLU5MJzogJ0xhbmQnLFxuICAgICAgICAncHQtUFQnOiAnUGHDrXMnLFxuICAgICAgICAncnUtUlUnOiAn0KHRgtGA0LDQvdCwJyxcbiAgICAgICAgJ3NsLVNJJzogJ0Ryxb5hdmEnLFxuICAgICAgICAnc3YtU0UnOiAnTGFuZCcsXG4gICAgICAgIHRoOiAn4Lib4Lij4Liw4LmA4LiX4LioJyxcbiAgICAgICAgdWs6ICfQmtGA0LDRl9C90LAnLFxuICAgICAgICAnemgtQ04nOiAn5Zu95a62JyxcbiAgICAgICAgJ3poLVRXJzogJ+Wci+WutidcbiAgICB9LFxuICAgICdvcmRlci1ub3Rlcyc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0Jlc3RlbGxub3RpemVuIChvcHRpb25hbCk6JyxcbiAgICAgICAgJ2VuLVVTJzogJ09yZGVyIG5vdGVzIChvcHRpb25hbCk6JyxcbiAgICAgICAgJ2VzLUVTJzogJ05vdGFzIGRlIHBlZGlkbyBvcGNpb25hbDonLFxuICAgICAgICBmcjogJ05vdGVzIGRlIGNvbW1hbmRlIChmYWN1bHRhdGlmKTonLFxuICAgICAgICBpdDogJ05vdGUgZGVsbFxcJ29yZGluZSAob3B6aW9uYWxlKTonLFxuICAgICAgICBqYTogJ+azqOaWh+ODoeODou+8iOOCquODl+OCt+ODp+ODsyk6JyxcbiAgICAgICAgJ3JvLVJPJzogJ05vdGUgZGUgY29tYW5kxIMgKG9wyJtpb25hbCk6JyxcbiAgICAgICAgYXI6ICfZhdmE2KfYrdi42KfYqiDYp9mE2LfZhNioICjYp9iu2KrZitin2LHZiik6JyxcbiAgICAgICAgY2E6ICdub3RlcyBkZSBjb21hbmRhIChvcGNpb25hbCk6JyxcbiAgICAgICAgJ2NzLUNaJzogJ3Bvem7DoW1reSBrIG9iamVkbsOhdmNlICh2b2xpdGVsbsOpKTonLFxuICAgICAgICBlbDogJ8+DzrfOvM61zrnPjs+DzrXOuc+CIM+AzrHPgc6xzrPOs861zrvOr86xz4IgKM+Az4HOv86xzrnPgc61z4TOuc66z4wpOicsXG4gICAgICAgICdoaS1JTic6ICfgpIbgpKbgpYfgpLYg4KSo4KWL4KSf4KWN4KS4ICjgpLXgpYjgpJXgpLLgpY3gpKrgpL/gpJUpOicsXG4gICAgICAgICdrby1LUic6ICfso7zrrLgg66mU66qoKOyEoO2DnSDsgqztla0pOicsXG4gICAgICAgICdsYi1MVSc6ICdCZXN0ZWxsdW5nc25vdGl6ZW4gKG9wdGlvbmFsKTonLFxuICAgICAgICAnbmwtTkwnOiAnYmVzdGVsbm90aXRpZXMgKG9wdGlvbmVlbCk6JyxcbiAgICAgICAgJ3B0LVBUJzogJ25vdGFzIGRvIHBlZGlkbyAob3BjaW9uYWwpOicsXG4gICAgICAgICdydS1SVSc6ICfQv9GA0LjQvNC10YfQsNC90LjRjyDQuiDQt9Cw0LrQsNC30YMgKNC90LXQvtCx0Y/Qt9Cw0YLQtdC70YzQvdC+KTogJyxcbiAgICAgICAgJ3NsLVNJJzogJ29wb21iZSBvIG5hcm/EjWlsdSAobmVvYnZlem5vKTogJyxcbiAgICAgICAgJ3N2LVNFJzogJ2Jlc3TDpGxsbmluZ3NhbnRlY2tuaW5nYXIgKHZhbGZyaXR0KTonLFxuICAgICAgICB0aDogJ+C4q+C4oeC4suC4ouC5gOC4q+C4leC4uOC4geC4suC4o+C4quC4seC5iOC4h+C4i+C4t+C5ieC4rSAo4LmE4Lih4LmI4Lia4Lix4LiH4LiE4Lix4LiaKTonLFxuICAgICAgICB1azogJ9C/0YDQuNC80ZbRgtC60Lgg0LTQviDQt9Cw0LzQvtCy0LvQtdC90L3RjyAo0L3QtdC+0LHQvtCyXFwn0Y/Qt9C60L7QstC+KScsXG4gICAgICAgICd6aC1DTic6ICforqLljZXlpIfms6jvvIjlj6/pgInvvIk6JyxcbiAgICAgICAgJ3poLVRXJzogJ+ioguWWruWCmeiou++8iOWPr+mBuO+8iTpcIidcbiAgICB9LFxuICAgICdzb21ldGhpbmctd2VudC13cm9uZyc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0VzIGlzdCBlaW4gRmVobGVyIGF1ZmdldHJldGVuLCBhYmVyIGRpZSBaYWhsdW5nIHd1cmRlIG3DtmdsaWNoZXJ3ZWlzZSBnZWxlaXN0ZXQuIEJpdHRlIMO8YmVycHLDvGZlbiBTaWUgZGllcywgYmV2b3IgU2llIGVpbmUgd2VpdGVyZSBCZXN0ZWxsdW5nIGF1ZmdlYmVuLicsXG4gICAgICAgICdlbi1VUyc6ICdTb21ldGhpbmcgd2VudCB3cm9uZywgYnV0IHRoZSBwYXltZW50IG1heSBoYXZlIGJlZW4gbWFkZS4gUGxlYXNlIGNoZWNrIGJlZm9yZSBwbGFjaW5nIGFub3RoZXIgb3JkZXIuJyxcbiAgICAgICAgJ2VzLUVTJzogJ1NlIHByb2R1am8gdW4gZXJyb3IsIHBlcm8gZXMgcG9zaWJsZSBxdWUgc2UgaGF5YSByZWFsaXphZG8gZWwgcGFnby4gVmVyaWZpcXVlIGFudGVzIGRlIHJlYWxpemFyIG90cm8gcGVkaWRvLicsXG4gICAgICAgIGZyOiAnVW5lIGVycmV1ciBzXFwnZXN0IHByb2R1aXRlLCBtYWlzIGxlIHBhaWVtZW50IGEgcGV1dC3DqnRyZSDDqXTDqSBlZmZlY3R1w6kuIFZldWlsbGV6IHbDqXJpZmllciBhdmFudCBkZSBwYXNzZXIgdW5lIGF1dHJlIGNvbW1hbmRlLicsXG4gICAgICAgIGl0OiAnUXVhbGNvc2Egw6ggYW5kYXRvIHN0b3J0bywgbWEgaWwgcGFnYW1lbnRvIHBvdHJlYmJlIGVzc2VyZSBzdGF0byBlZmZldHR1YXRvLiBTaSBwcmVnYSBkaSBjb250cm9sbGFyZSBwcmltYSBkaSBlZmZldHR1YXJlIHVuIGFsdHJvIG9yZGluZS4nLFxuICAgICAgICBqYTogJ+S9leOBi+WVj+mhjOOBjOeZuueUn+OBl+OBvuOBl+OBn+OBjOOAgeaUr+aJleOBhOOBjOihjOOCj+OCjOOBn+WPr+iDveaAp+OBjOOBguOCiuOBvuOBmeOAgiDliKXjga7ms6jmlofjgpLjgZnjgovliY3jgavnorroqo3jgZfjgabjgY/jgaDjgZXjgYTjgIInLFxuICAgICAgICAncm8tUk8nOiAnQ2V2YSBudSBhIGZ1bmPIm2lvbmF0IGNvcmVjdCwgZGFyIGVzdGUgcG9zaWJpbCBjYSBwbGF0YSBzxIMgZmkgZm9zdCBlZmVjdHVhdMSDLiBWxIMgcnVnxINtIHPEgyB2ZXJpZmljYcibaSDDrm5haW50ZSBkZSBhIHBsYXNhIG8gYWx0xIMgY29tYW5kxIMuJyxcbiAgICAgICAgYXI6ICfYrdiv2Ksg2K7Yt9ijINmF2Kcg2Iwg2YjZhNmD2YYg2LHYqNmF2Kcg2KrZhSDYp9mE2LPYr9in2K8uINmK2LHYrNmJINin2YTYqtit2YLZgiDZgtio2YQg2KrZgtiv2YrZhSDYt9mE2Kgg2KLYrtixLicsXG4gICAgICAgIGNhOiAnU1xcJ2hhIHByb2R1w690IHVuIGVycm9yLCBwZXLDsiDDqXMgcG9zc2libGUgcXVlIHNcXCdoYWdpIGVmZWN0dWF0IGVsIHBhZ2FtZW50LiBDb21wcm92ZXUtaG8gYWJhbnMgZGUgZmVyIHVuYSBhbHRyYSBjb21hbmRhLicsXG4gICAgICAgICdjcy1DWic6ICdOxJtjbyBzZSBwb2themlsbywgYWxlIHBsYXRiYSBtb8W+bsOhIGJ5bGEgcHJvdmVkZW5hLiBQxZllZCB6YWTDoW7DrW0gZGFsxaHDrSBvYmplZG7DoXZreSBwcm9zw61tIHprb250cm9sdWp0ZS4nLFxuICAgICAgICAnZGEtREsnOiAnTm9nZXQgZ2lrIGdhbHQsIG1lbiBiZXRhbGluZ2VuIGthbiB2w6ZyZSBmb3JldGFnZXQuIEtvbnRyb2xsZXIgdmVubGlnc3QsIGbDuHIgZHUgYWZnaXZlciBlbiBhbmRlbiBvcmRyZS4nLFxuICAgICAgICBlbDogJ86azqzPhM65IM+Azq7Os861IM+Dz4TPgc6xzrLOrCwgzrHOu867zqwgzrcgz4DOu863z4HPic68zq4gzrzPgM6/z4HOtc6vIM69zrEgzq3Ph861zrkgzrPOr869zrXOuS4gzqDOsc+BzrHOus6xzrvPjiDOtc67zq3Os86+z4TOtSDPgM+BzrnOvSDOus6szr3Otc+EzrUgzqzOu867zrcgz4DOsc+BzrHOs86zzrXOu86vzrEuJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkleClgeCkmyDgpJfgpLLgpKQg4KS54KWB4KSGLCDgpLLgpYfgpJXgpL/gpKgg4KS54KWLIOCkuOCkleCkpOCkviDgpLngpYgg4KSV4KS/IOCkreClgeCkl+CkpOCkvuCkqCDgpLngpYsg4KSX4KSv4KS+IOCkueCliy4g4KSV4KWD4KSq4KSv4KS+IOCkj+CklSDgpJTgpLAg4KSG4KSm4KWH4KS2IOCkpuClh+CkqOClhyDgpLjgpYcg4KSq4KS54KSy4KWHIOCknOCkvuCkguCkmiDgpLLgpYfgpILgpaQnLFxuICAgICAgICAna28tS1InOiAn66y47KCc6rCAIOuwnOyDne2WiOyngOunjCDqsrDsoJzqsIAg7JmE66OM65CY7JeI7J2EIOyImCDsnojsirXri4jri6QuIOuLpOuluCDso7zrrLjsnYQg7ZWY6riwIOyghOyXkCDtmZXsnbjtlZjsi5zquLAg67CU656N64uI64ukLicsXG4gICAgICAgICdsYi1MVSc6ICdFcHBlcyBhc3MgZmFsc2NoIGdhYW5nLCBhd2VyIGRcXCdCZXp1ZWx1bmcgYXNzIHZsw6RpY2h0IGdlbWFhY2ggZ2lubi4gUHLDqWlmdCB3LmUuZy4gaWVyIERpciBlbmcgYW5lciBCZXN0ZWxsdW5nIHBsYXrDqWllcnQuJyxcbiAgICAgICAgJ25sLU5MJzogJ0VyIGlzIGlldHMgbWlzZ2VnYWFuLCBtYWFyIGRlIGJldGFsaW5nIGthbiB6aWpuIGdlZGFhbi4gQ29udHJvbGVlciBkaXQgdm9vcmRhdCB1IGVlbiBuaWV1d2UgYmVzdGVsbGluZyBwbGFhdHN0LicsXG4gICAgICAgICdwdC1QVCc6ICdBbGdvIGRldSBlcnJhZG8sIG1hcyBvIHBhZ2FtZW50byBwb2RlIHRlciBzaWRvIGZlaXRvLiBQb3IgZmF2b3IsIHZlcmlmaXF1ZSBhbnRlcyBkZSBmYXplciBvdXRybyBwZWRpZG8uJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Cn0YLQvi3RgtC+INC/0L7RiNC70L4g0L3QtSDRgtCw0LosINC90L4g0L7Qv9C70LDRgtCwINC80L7Qs9C70LAg0LHRi9GC0Ywg0L/RgNC+0LjQt9Cy0LXQtNC10L3QsC4g0J/QvtC20LDQu9GD0LnRgdGC0LAsINC/0YDQvtCy0LXRgNGM0YLQtSDQv9C10YDQtdC0INGA0LDQt9C80LXRidC10L3QuNC10Lwg0LTRgNGD0LPQvtCz0L4g0LfQsNC60LDQt9CwLicsXG4gICAgICAgICdzbC1TSSc6ICdOZWthaiBqZSDFoWxvIG5hcm9iZSwgbW9yZGEgcGEgamUgYmlsbyBwbGHEjWlsbyBpenZlZGVuby4gUHJlZGVuIG9kZGF0ZSBub3ZvIG5hcm/EjWlsbywgcHJldmVyaXRlLicsXG4gICAgICAgICdzdi1TRSc6ICdOw6Vnb3QgZ2ljayBmZWwsIG1lbiBiZXRhbG5pbmdlbiBrYW4gaGEgZ2pvcnRzLiBLb250cm9sbGVyYSBpbm5hbiBkdSBnw7ZyIGVuIGFubmFuIGJlc3TDpGxsbmluZy4nLFxuICAgICAgICB0aDogJ+C4oeC4teC4muC4suC4h+C4reC4ouC5iOC4suC4h+C4nOC4tOC4lOC4nuC4peC4suC4lCDguYHguJXguYjguK3guLLguIjguKHguLXguIHguLLguKPguIrguLPguKPguLDguYDguIfguLTguJnguYHguKXguYnguKcg4LmC4Lib4Lij4LiU4LiV4Lij4Lin4LiI4Liq4Lit4Lia4LiB4LmI4Lit4LiZ4LiX4Liz4LiB4Liy4Lij4Liq4Lix4LmI4LiH4LiL4Li34LmJ4Lit4Lit4Li34LmI4LiZJyxcbiAgICAgICAgdWs6ICfQqdC+0YHRjCDQv9GW0YjQu9C+INC90LUg0YLQsNC6LCDQsNC70LUg0L7Qv9C70LDRgtCwLCDQvNC+0LbQu9C40LLQviwg0LHRg9C70LAg0LfQtNGW0LnRgdC90LXQvdCwLiDQkdGD0LTRjCDQu9Cw0YHQutCwLCDQv9C10YDQtdCy0ZbRgNGC0LUsINC/0LXRgNGIINC90ZbQtiDRgNC+0LHQuNGC0Lgg0ZbQvdGI0LUg0LfQsNC80L7QstC70LXQvdC90Y8uJyxcbiAgICAgICAgJ3poLUNOJzogJ+WHuuS6hueCuemXrumimO+8jOS9huWPr+iDveW3suS7mOasvuOAgiDor7flnKjkuIvlj6bkuIDkuKrorqLljZXkuYvliY3mo4Dmn6XjgIInLFxuICAgICAgICAnemgtVFcnOiAn5Ye65LqG6bue5ZWP6aGM77yM5L2G5Y+v6IO95bey5LuY5qy+44CCIOiri+WcqOS4i+WPpuS4gOWAi+ioguWWruS5i+WJjeaqouafpeOAgidcbiAgICB9LFxuICAgICdzb21ldGhpbmctd2VudC13cm9uZy1vcmRlcic6IHtcbiAgICAgICAgJ2RlLURFJzogJ0V0d2FzIGlzdCBzY2hpZWYgZ2VsYXVmZW4sIGFiZXIga2VpbmUgU29yZ2UuIFdpciBoYWJlbiBJaHJlIEJlc3RlbGxkYXRlbiB1bmQgSWhyZSBaYWhsdW5nIGlzdCBlcmZvbGd0LiBFaW5lIHdlaXRlcmUgQmVzdGVsbHVuZyBpc3QgbmljaHQgZXJmb3JkZXJsaWNoLicsXG4gICAgICAgICdlbi1VUyc6ICdTb21ldGhpbmcgd2VudCB3cm9uZywgYnV0IGRvblxcJ3Qgd29ycnkuIFdlIGhhdmUgeW91ciBvcmRlciBkZXRhaWxzLCBhbmQgeW91ciBwYXltZW50IGhhcyBiZWVuIG1hZGUuIFRoZXJlIGlzIG5vIG5lZWQgdG8gcGxhY2UgYW5vdGhlciBvcmRlci4nLFxuICAgICAgICAnZXMtRVMnOiAnQWxnbyBzYWxpw7MgbWFsLCBwZXJvIG5vIHNlIHByZW9jdXBlLiBUZW5lbW9zIGxvcyBkZXRhbGxlcyBkZSBzdSBwZWRpZG8geSBzdSBwYWdvIHNlIGhhIHJlYWxpemFkby4gTm8gZXMgbmVjZXNhcmlvIHJlYWxpemFyIG90cm8gcGVkaWRvLicsXG4gICAgICAgIGZyOiAnUXVlbHF1ZSBjaG9zZSBzXFwnZXN0IG1hbCBwYXNzw6ksIG1haXMgbmUgdm91cyBpbnF1acOpdGV6IHBhcy4gTm91cyBhdm9ucyBsZXMgZMOpdGFpbHMgZGUgdm90cmUgY29tbWFuZGUgZXQgdm90cmUgcGFpZW1lbnQgYSDDqXTDqSBlZmZlY3R1w6kuIElsIG5cXCdlc3QgcGFzIG7DqWNlc3NhaXJlIGRlIHBhc3NlciB1bmUgYXV0cmUgY29tbWFuZGUuJyxcbiAgICAgICAgaXQ6ICdRdWFsY29zYSDDqCBhbmRhdG8gc3RvcnRvLCBtYSBub24gcHJlb2NjdXBhcnRpLiBBYmJpYW1vIGkgZGV0dGFnbGkgZGVsIHR1byBvcmRpbmUgZSBpbCBwYWdhbWVudG8gw6ggc3RhdG8gZWZmZXR0dWF0by4gTm9uIMOoIG5lY2Vzc2FyaW8gZWZmZXR0dWFyZSB1biBhbHRybyBvcmRpbmUuJyxcbiAgICAgICAgamE6ICfkvZXjgYvllY/poYzjgYznmbrnlJ/jgZfjgb7jgZfjgZ/jgYzjgIHlv4PphY3jgZfjgarjgYTjgafjgY/jgaDjgZXjgYTjgIIg44GU5rOo5paH44Gu6Kmz57Sw44GM44GC44KK44CB44GK5pSv5omV44GE44GM5a6M5LqG44GX44G+44GX44Gf44CCIOWIpeOBruazqOaWh+OCkuOBmeOCi+W/heimgeOBr+OBguOCiuOBvuOBm+OCk+OAgicsXG4gICAgICAgICdyby1STyc6ICdDZXZhIGEgbWVycyBwcm9zdCwgZGFyIG51IHbEgyBmYWNlyJtpIGdyaWppLiBBdmVtIGRldGFsaWlsZSBjb21lbnppaSBkdnMuIMiZaSBwbGF0YSBkdnMuIGEgZm9zdCBlZmVjdHVhdMSDLiBOdSBlc3RlIG5ldm9pZSBzxIMgcGxhc2HIm2kgbyBhbHTEgyBjb21hbmTEgy4nLFxuICAgICAgICBhcjogJ9it2K/YqyDYrti32KMg2YXYpyDYjCDZhNmD2YYg2YTYpyDYqtmC2YTZgi4g2YTYr9mK2YbYpyDYqtmB2KfYtdmK2YQg2LfZhNio2YMg2Iwg2YjZgtivINiq2YUg2LPYr9in2K8g2K/Zgdi52KrZgy4g2YTZitiz2Kog2YfZhtin2YMg2K3Yp9is2Kkg2YTYqtmC2K/ZitmFINi32YTYqCDYotiu2LEuJyxcbiAgICAgICAgY2E6ICdTXFwnaGEgcHJvZHXDr3QgdW4gZXJyb3IsIHBlcsOyIG5vIHVzIHByZW9jdXBldS4gVGVuaW0gbGVzIGRhZGVzIGRlIGxhIHZvc3RyYSBjb21hbmRhIGkgc+KAmWhhIGVmZWN0dWF0IGVsIHBhZ2FtZW50LiBObyBjYWwgZmVyIHVuYSBhbHRyYSBjb21hbmRhLicsXG4gICAgICAgICdjcy1DWic6ICdOxJtjbyBzZSBwb2themlsbywgYWxlIG5lYm9qdGUgc2UuIE3DoW1lIHBvZHJvYm5vc3RpIG8gdmHFocOtIG9iamVkbsOhdmNlIGEgdmHFoWUgcGxhdGJhIGJ5bGEgcHJvdmVkZW5hLiBOZW7DrSB0xZllYmEgemFkw6F2YXQgZGFsxaHDrSBvYmplZG7DoXZrdS4nLFxuICAgICAgICAnZGEtREsnOiAnTm9nZXQgZ2lrIGdhbHQsIG1lbiBiYXJlIHJvbGlnLiBWaSBoYXIgZGluZSBvcmRyZW9wbHlzbmluZ2VyLCBvZyBkaW4gYmV0YWxpbmcgZXIgZm9yZXRhZ2V0LiBEZXQgZXIgaWtrZSBuw7hkdmVuZGlndCBhdCBhZmdpdmUgZW4gYW5kZW4gb3JkcmUuJyxcbiAgICAgICAgZWw6ICfOms6sz4TOuSDPgM6uzrPOtSDPg8+Ez4HOsc6yzqwsIM6xzrvOu86sIM68zrfOvSDOsc69zrfPg8+Fz4fOtc6vz4TOtS4gzojPh86/z4XOvM61IM+EzrEgz4PPhM6/zrnPh861zq/OsSDPhM63z4Igz4DOsc+BzrHOs86zzrXOu86vzrHPgiDPg86xz4IgzrrOsc65IM63IM+AzrvOt8+Bz4nOvM6uIM+DzrHPgiDOrc+HzrXOuSDPgM+BzrHOs868zrHPhM6/z4DOv865zrfOuM61zq8uIM6UzrXOvSDPh8+BzrXOuc6szrbOtc+EzrHOuSDOvc6xIM66zqzOvc61z4TOtSDOrM67zrvOtyDPgM6xz4HOsc6zzrPOtc67zq/OsS4nLFxuICAgICAgICAnaGktSU4nOiAn4KSV4KWB4KSbIOCkl+CksuCkpCDgpLngpYsg4KSX4KSv4KS+LCDgpLLgpYfgpJXgpL/gpKgg4KSa4KS/4KSC4KSk4KS+IOCkqCDgpJXgpLDgpYfgpILgpaQg4KS54KSu4KS+4KSw4KWHIOCkquCkvuCkuCDgpIbgpKrgpJXgpYcg4KSG4KSm4KWH4KS2IOCkleCkviDgpLXgpL/gpLXgpLDgpKMg4KS54KWILCDgpJTgpLAg4KSG4KSq4KSV4KS+IOCkreClgeCkl+CkpOCkvuCkqCDgpJXgpLAg4KSm4KS/4KSv4KS+IOCkl+Ckr+CkviDgpLngpYjgpaQg4KSm4KWC4KS44KSw4KS+IOCkhuCkpuClh+CktiDgpKbgpYfgpKjgpYcg4KSV4KWAIOCkleCli+CkiCDgpIbgpLXgpLbgpY3gpK/gpJXgpKTgpL4g4KSo4KS54KWA4KSCIOCkueCliOClpCcsXG4gICAgICAgICdrby1LUic6ICfrrLjsoJzqsIAg67Cc7IOd7ZaI7KeA66eMIOqxseygle2VmOyngCDrp4jshLjsmpQuIOyjvOusuCDshLjrtoDsoJXrs7TqsIAg7J6I7Jy866mwIOqysOygnOqwgCDsmYTro4zrkJjsl4jsirXri4jri6QuIOuLpOuluCDso7zrrLjsnYQg7ZWgIO2VhOyalOqwgCDsl4bsirXri4jri6QuJyxcbiAgICAgICAgJ2xiLUxVJzogJ0VwcGVzIGFzcyBmYWxzY2ggZ2FhbmcsIGF3ZXIgbWFhY2ggZGVyIGtlbmcgU3Vlcmdlbi4gTWlyIGh1bm4gw4RyIEJlc3RlbGx1bmdzZGV0YWlsZXIsIGFuIMOEciBCZXp1ZWx1bmcgZ291ZiBnZW1hYWNoLiBFdCBhc3MgbmV0IG7DqWlkZWcgZW5nIGFuZXIgQmVzdGVsbHVuZyB6ZSBtYWFjaGVuLicsXG4gICAgICAgICdubC1OTCc6ICdFciBpcyBpZXRzIG1pc2dlZ2FhbiwgbWFhciBtYWFrIGplIGdlZW4gem9yZ2VuLiBXZSBoZWJiZW4gdXcgYmVzdGVsZ2VnZXZlbnMgZW4gdXcgYmV0YWxpbmcgaXMgZ2VkYWFuLiBIZXQgaXMgbmlldCBub2RpZyBvbSBub2cgZWVuIGJlc3RlbGxpbmcgdGUgcGxhYXRzZW4uJyxcbiAgICAgICAgJ3B0LVBUJzogJ0FsZ28gZGV1IGVycmFkbywgbWFzIG7Do28gc2UgcHJlb2N1cGUuIFRlbW9zIG9zIGRldGFsaGVzIGRvIHNldSBwZWRpZG8gZSBzZXUgcGFnYW1lbnRvIGZvaSBlZmV0dWFkby4gTsOjbyBow6EgbmVjZXNzaWRhZGUgZGUgZmF6ZXIgb3V0cm8gcGVkaWRvLicsXG4gICAgICAgICdydS1SVSc6ICfQp9GC0L4t0YLQviDQv9C+0YjQu9C+INC90LUg0YLQsNC6LCDQvdC+INC90LUg0LLQvtC70L3Rg9C50YLQtdGB0YwuINCjINC90LDRgSDQtdGB0YLRjCDQtNCw0L3QvdGL0LUg0L4g0LLQsNGI0LXQvCDQt9Cw0LrQsNC30LUsINC4INCy0LDRiCDQv9C70LDRgtC10LYg0LHRi9C7INC/0YDQvtC40LfQstC10LTQtdC9LiDQntGH0LXRgNC10LTQvdC+0Lkg0LfQsNC60LDQtyDQtNC10LvQsNGC0Ywg0L3QtSDQvdGD0LbQvdC+LicsXG4gICAgICAgICdzbC1TSSc6ICdOZWthaiBqZSDFoWxvIG5hcm9iZSwgdmVuZGFyIG5lIHNrcmJpdGUuIFBvZGF0a2UgbyBuYXJvxI1pbHUgaW1hbW8gaW4gcGxhxI1pbG8gamUgYmlsbyBvcHJhdmxqZW5vLiBEcnVnZWdhIG5hcm/EjWlsYSBuaSB0cmViYSBvZGRhdGkuJyxcbiAgICAgICAgJ3N2LVNFJzogJ07DpWdvdCBnaWNrIGZlbCwgbWVuIG9yb2EgZGlnIGludGUuIFZpIGhhciBkaW5hIGJlc3TDpGxsbmluZ3N1cHBnaWZ0ZXIgb2NoIGRpbiBiZXRhbG5pbmcgaGFyIGdqb3J0cy4gRGV0IGZpbm5zIGluZ2VuIGFubGVkbmluZyBhdHQgZ8O2cmEgZW4gYW5uYW4gYmVzdMOkbGxuaW5nLicsXG4gICAgICAgIHRoOiAn4Lih4Li14Lia4Liy4LiH4Lit4Lii4LmI4Liy4LiH4Lic4Li04LiU4Lie4Lil4Liy4LiUIOC5geC4leC5iOC5hOC4oeC5iOC4leC5ieC4reC4h+C4geC4seC4h+C4p+C4pSDguYDguKPguLLguKHguLXguKPguLLguKLguKXguLDguYDguK3guLXguKLguJTguIHguLLguKPguKrguLHguYjguIfguIvguLfguYnguK3guILguK3guIfguITguLjguJPguYHguKXguLDguIrguLPguKPguLDguYDguIfguLTguJnguYDguKPguLXguKLguJrguKPguYnguK3guKLguYHguKXguYnguKcg4LmE4Lih4LmI4LiI4Liz4LmA4Lib4LmH4LiZ4LiV4LmJ4Lit4LiH4LiX4Liz4LiB4Liy4Lij4Liq4Lix4LmI4LiH4LiL4Li34LmJ4Lit4Lit4Li34LmI4LiZJyxcbiAgICAgICAgdWs6ICfQqdC+0YHRjCDQv9GW0YjQu9C+INC90LUg0YLQsNC6LCDQsNC70LUg0L3QtSDRhdCy0LjQu9GO0LnRgtC10YHRjC4g0KMg0L3QsNGBINGUINC00LDQvdGWINCy0LDRiNC+0LPQviDQt9Cw0LzQvtCy0LvQtdC90L3Rjywg0ZYg0LLQsNGIINC/0LvQsNGC0ZbQtiDQt9C00ZbQudGB0L3QtdC90L4uINCd0LXQvNCw0ZQg0L3QtdC+0LHRhdGW0LTQvdC+0YHRgtGWINGA0L7QsdC40YLQuCDRltC90YjQtSDQt9Cw0LzQvtCy0LvQtdC90L3Rjy4nLFxuICAgICAgICAnemgtQ04nOiAn5Ye65LqG54K56Zeu6aKY77yM5L2G5Yir5ouF5b+D44CCIOaIkeS7rOacieaCqOeahOiuouWNleivpue7huS/oeaBr++8jOaCqOeahOS7mOasvuW3suWujOaIkOOAgiDml6DpnIDlho3kuIvorqLljZXjgIInLFxuICAgICAgICAnemgtVFcnOiAn5Ye65LqG6bue5ZWP6aGM77yM5L2G5Yil5pOU5b+D44CCIOaIkeWAkeacieaCqOeahOioguWWruips+e0sOS/oeaBr++8jOaCqOeahOS7mOasvuW3suWujOaIkOOAgiDnhKHpnIDlho3kuIvoqILllq7jgIInXG4gICAgfSxcbiAgICAnZGVsaXZlcnktZGF0ZSc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0xpZWZlcnRlcm1pbicsXG4gICAgICAgICdlbi1VUyc6ICdEZWxpdmVyeSBkYXRlJyxcbiAgICAgICAgJ2VzLUVTJzogJ0ZlY2hhIGRlIGVudHJlZ2EnLFxuICAgICAgICBmcjogJ0RhdGUgZGUgbGl2cmFpc29uJyxcbiAgICAgICAgaXQ6ICdEYXRhIGRpIGNvbnNlZ25hJyxcbiAgICAgICAgamE6ICfphY3pgIHml6UnLFxuICAgICAgICAncm8tUk8nOiAnRGF0YSBsaXZyxINyaWknLFxuICAgICAgICBhcjogJ9iq2KfYsdmK2K4g2KfZhNiq2LPZhNmK2YUg2KfZiCDYp9mE2YjYtdmI2YQnLFxuICAgICAgICBjYTogJ0RhdGEgZGUgbGxpdXJhbWVudCcsXG4gICAgICAgICdjcy1DWic6ICdEYXR1bSBkb3J1xI1lbsOtJyxcbiAgICAgICAgJ2RhLURLJzogJ0xldmVyaW5nc2RhdG8nLFxuICAgICAgICBlbDogJ86XzrzOtc+Bzr/OvM63zr3Or86xIM+AzrHPgc6szrTOv8+DzrfPgicsXG4gICAgICAgICdoaS1JTic6ICfgpKHgpL/gpLLgpYDgpLXgpLDgpYAg4KSV4KWAIOCkpOCkvuCksOClgOCklicsXG4gICAgICAgICdrby1LUic6ICfrsLDshqEg64Kg7KecJyxcbiAgICAgICAgJ2xiLUxVJzogJ0xpd3dlcnVuZ3NkYXR1bScsXG4gICAgICAgICdubC1OTCc6ICdCZXpvcmdkYXR1bScsXG4gICAgICAgICdwdC1QVCc6ICdEYXRhIGRlIGVudHJlZ2EnLFxuICAgICAgICAncnUtUlUnOiAn0JTQsNGC0LAg0LTQvtGB0YLQsNCy0LrQuCcsXG4gICAgICAgICdzbC1TSSc6ICdEYXR1bSBkb3N0YXZlJyxcbiAgICAgICAgJ3N2LVNFJzogJ0xldmVyYW5zZGF0dW0nLFxuICAgICAgICB0aDogJ+C4p+C4seC4meC4l+C4teC5iOC4iOC4seC4lOC4quC5iOC4hycsXG4gICAgICAgIHVrOiAn0JTQsNGC0LAg0LTQvtGB0YLQsNCy0LrQuCcsXG4gICAgICAgICd6aC1DTic6ICfpgq7lr4Tml6XmnJ8nLFxuICAgICAgICAnemgtVFcnOiAn6YO15a+E5pel5pyfJ1xuICAgIH0sXG4gICAgJ2ZpcnN0LXJlbmV3YWwtZGF0ZSc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0Vyc3RlIFZlcmzDpG5nZXJ1bmcnLFxuICAgICAgICAnZW4tVVMnOiAnRmlyc3QgcmVuZXdhbCcsXG4gICAgICAgICdlcy1FUyc6ICdQcmltZXJhIHJlbm92YWNpw7NuJyxcbiAgICAgICAgZnI6ICdQcmVtaWVyIHJlbm91dmVsbGVtZW50JyxcbiAgICAgICAgaXQ6ICdQcmltbyBSaW5ub3ZvJyxcbiAgICAgICAgamE6ICfmnIDliJ3jga7mm7TmlrAnLFxuICAgICAgICAncm8tUk8nOiAnUHJpbWEgcmXDrm5ub2lyZScsXG4gICAgICAgIGFyOiAn2KfZhNiq2KzYr9mK2K8g2KfZhNij2YjZhCcsXG4gICAgICAgIGNhOiAnUHJpbWVyYSByZW5vdmFjacOzJyxcbiAgICAgICAgJ2NzLUNaJzogJ1Bydm7DrSBvYm5vdmEnLFxuICAgICAgICAnZGEtREsnOiAnRsO4cnN0ZSBmb3JueWVsc2UnLFxuICAgICAgICBlbDogJ86gz4HPjs+EzrcgzrHOvc6xzr3Orc+Jz4POtycsXG4gICAgICAgICdoaS1JTic6ICfgpKrgpLngpLLgpL4g4KSo4KS14KWA4KSo4KWA4KSV4KSw4KSjJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yyqyDrsojsp7gg6rCx7IugJyxcbiAgICAgICAgJ2xiLUxVJzogJ8OJaXNjaHQgRXJuZWllcnVuZycsXG4gICAgICAgICdubC1OTCc6ICdFZXJzdGUgdmVybGVuZ2luZycsXG4gICAgICAgICdwdC1QVCc6ICdQcmltZWlyYSByZW5vdmHDp8OjbycsXG4gICAgICAgICdydS1SVSc6ICfQn9C10YDQstC+0LUg0L7QsdC90L7QstC70LXQvdC40LUnLFxuICAgICAgICAnc2wtU0knOiAnUHJ2YSBvYm5vdmEnLFxuICAgICAgICAnc3YtU0UnOiAnRsO2cnN0YSBmw7ZybnllbHNlbicsXG4gICAgICAgIHRoOiAn4LiV4LmI4Lit4Lit4Liy4Lii4Li44LiE4Lij4Lix4LmJ4LiH4LmB4Lij4LiBJyxcbiAgICAgICAgdWs6ICfQn9C10YDRiNC1INC+0L3QvtCy0LvQtdC90L3RjycsXG4gICAgICAgICd6aC1DTic6ICfnrKzkuIDmrKHnu63orqInLFxuICAgICAgICAnemgtVFcnOiAn56ys5LiA5qyh57qM6KiCJ1xuICAgIH0sXG4gICAgJ3N1YnNjcmlwdGlvbi1zdW1tYXJ5Jzoge1xuICAgICAgICAnZGUtREUnOiAnV2llZGVya2VocmVuZGUgU3VtbWUnLFxuICAgICAgICAnZW4tVVMnOiAnUmVjdXJyaW5nIHRvdGFsJyxcbiAgICAgICAgJ2VzLUVTJzogJ1RvdGFsIHJlY3VycmVudGUnLFxuICAgICAgICBmcjogJ1RvdGFsIHLDqWN1cnJlbnQnLFxuICAgICAgICBpdDogJ1RvdGFsZSByaWNvcnJlbnRlJyxcbiAgICAgICAgamE6ICflrprmnJ/lkIjoqIgnLFxuICAgICAgICAncm8tUk8nOiAnVG90YWwgcmVjdXJlbnQnLFxuICAgICAgICBhcjogJ9in2YTZhdis2YXZiNi5INin2YTZhdiq2YPYsdixJyxcbiAgICAgICAgY2E6ICdUb3RhbCByZWN1cnJlbnQnLFxuICAgICAgICAnY3MtQ1onOiAnT3Bha3Vqw61jw60gc2UgY2Vsa2VtJyxcbiAgICAgICAgJ2RhLURLJzogJ1RpbGJhZ2V2ZW5kZW5kZSB0b3RhbCcsXG4gICAgICAgIGVsOiAnzpXPgM6xzr3Osc67zrHOvM6yzrHOvc+MzrzOtc69zr8gz4PPjc69zr/Ou86/JyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkhuCkteCksOCljeCkpOClgCDgpJXgpYHgpLInLFxuICAgICAgICAna28tS1InOiAn67CY67O1IO2VqeqzhCcsXG4gICAgICAgICdsYi1MVSc6ICdXaWRkZXJodWVsZW5kIFRvdGFsJyxcbiAgICAgICAgJ25sLU5MJzogJ1RlcnVna2VyZW5kIHRvdGFhbCcsXG4gICAgICAgICdwdC1QVCc6ICdUb3RhbCByZWNvcnJlbnRlJyxcbiAgICAgICAgJ3J1LVJVJzogJ9Cf0L7QstGC0L7RgNGP0Y7RidCw0Y/RgdGPINGB0YPQvNC80LAnLFxuICAgICAgICAnc2wtU0knOiAnUG9uYXZsamFqb8SNZSBzZSBza3VwYWonLFxuICAgICAgICAnc3YtU0UnOiAnw4V0ZXJrb21tYW5kZSB0b3RhbHQnLFxuICAgICAgICB0aDogJ+C4ouC4reC4lOC4o+C4p+C4oeC4l+C4teC5iOC5gOC4geC4tOC4lOC4i+C5ieC4sycsXG4gICAgICAgIHVrOiAn0J/QvtCy0YLQvtGA0Y7QstCw0L3QsCDRgdGD0LzQsCcsXG4gICAgICAgICd6aC1DTic6ICfnu4/luLjmgKfmgLvorqEnLFxuICAgICAgICAnemgtVFcnOiAn57aT5bi45oCn57i96KiIJ1xuICAgIH0sXG4gICAgJ2luaXRpYWwtc3VtbWFyeSc6IHtcbiAgICAgICAgJ2RlLURFJzogJ0FuZmFuZ3NzdW1tZScsXG4gICAgICAgICdlbi1VUyc6ICdJbml0aWFsIHRvdGFsJyxcbiAgICAgICAgJ2VzLUVTJzogJ1RvdGFsIGluaWNpYWwnLFxuICAgICAgICBmcjogJ1RvdGFsIGluaXRpYWwnLFxuICAgICAgICBpdDogJ1RvdGFsZSBpbml6aWFsZScsXG4gICAgICAgIGphOiAn5Yid5pyf5ZCI6KiIJyxcbiAgICAgICAgJ3JvLVJPJzogJ1RvdGFsIGluacibaWFsJyxcbiAgICAgICAgYXI6ICfYp9mE2YXYrNmF2YjYuSDYp9mE2KPZiNmE2YonLFxuICAgICAgICBjYTogJ1RvdGFsIGluaWNpYWwnLFxuICAgICAgICAnY3MtQ1onOiAnUG/EjcOhdGXEjW7DrSBjZWxrZW0nLFxuICAgICAgICAnZGEtREsnOiAnSW5kbGVkZW5kZSB0b3RhbCcsXG4gICAgICAgIGVsOiAnzpHPgc+HzrnOus+MIM+Dz43Ovc6/zrvOvycsXG4gICAgICAgICdoaS1JTic6ICfgpKrgpY3gpLDgpL7gpLDgpILgpK3gpL/gpJUg4KSV4KWB4KSyJyxcbiAgICAgICAgJ2tvLUtSJzogJ+y0iOq4sCDtlanqs4QnLFxuICAgICAgICAnbGItTFUnOiAnVWZhbmtzIHRvdGFsJyxcbiAgICAgICAgJ25sLU5MJzogJ0luaXRpZWVsIHRvdGFhbCcsXG4gICAgICAgICdwdC1QVCc6ICdUb3RhbCBpbmljaWFsJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CY0YHRhdC+0LTQvdCw0Y8g0YHRg9C80LzQsCcsXG4gICAgICAgICdzbC1TSSc6ICdaYcSNZXRuaSBzZcWhdGV2ZWsnLFxuICAgICAgICAnc3YtU0UnOiAnSW5pdGlhbCBzdW1tYScsXG4gICAgICAgIHRoOiAn4Lii4Lit4LiU4Lij4Lin4Lih4LmA4Lij4Li04LmI4Lih4LiV4LmJ4LiZJyxcbiAgICAgICAgdWs6ICfQn9C+0YfQsNGC0LrQvtCy0LAg0YHRg9C80LAnLFxuICAgICAgICAnemgtQ04nOiAn5Yid5aeL5oC75pWwJyxcbiAgICAgICAgJ3poLVRXJzogJ+WIneWni+e4veaVuCdcbiAgICB9LFxuICAgICdyZWN1cnJpbmctc2hpcHBpbmcnOiB7XG4gICAgICAgICdkZS1ERSc6ICdXaWVkZXJrZWhyZW5kZXIgVmVyc2FuZCcsXG4gICAgICAgICdlbi1VUyc6ICdSZWN1cnJpbmcgc2hpcHBpbmcnLFxuICAgICAgICAnZXMtRVMnOiAnRW52w61vcyByZWN1cnJlbnRlcycsXG4gICAgICAgIGZyOiAnRXhww6lkaXRpb24gcsOpY3VycmVudGUnLFxuICAgICAgICBpdDogJ1RvdGFsZSBpbml6aWFsZScsXG4gICAgICAgIGphOiAn5a6a5pyf6YWN6YCBJyxcbiAgICAgICAgJ3JvLVJPJzogJ1RvdGFsIGluacibaWFsJyxcbiAgICAgICAgYXI6ICfYp9mE2LTYrdmGINin2YTZhdiq2YPYsdixJyxcbiAgICAgICAgY2E6ICdFbnZpYW1lbnQgcGVyacOyZGljJyxcbiAgICAgICAgJ2NzLUNaJzogJ09wYWtvdmFuw6EgZG9wcmF2YScsXG4gICAgICAgICdkYS1ESyc6ICdUaWxiYWdldmVuZGVuZGUgZm9yc2VuZGVsc2UnLFxuICAgICAgICBlbDogJ86Vz4DOsc69zrHOu86xzrzOss6xzr3PjM68zrXOvc63IM6xz4DOv8+Dz4TOv867zq4nLFxuICAgICAgICAnaGktSU4nOiAn4KSG4KS14KSw4KWN4KSk4KWAIOCktuCkv+CkquCkv+CkguCklycsXG4gICAgICAgICdrby1LUic6ICfrsJjrs7Ug67Cw7IahJyxcbiAgICAgICAgJ2xiLUxVJzogJ1dpZGRlcmh1ZWxlbmQgVmVyc2FuZCcsXG4gICAgICAgICdubC1OTCc6ICdUZXJ1Z2tlcmVuZGUgdmVyemVuZGluZycsXG4gICAgICAgICdwdC1QVCc6ICdSZW1lc3NhIHJlY29ycmVudGUnLFxuICAgICAgICAncnUtUlUnOiAn0J/QtdGA0LjQvtC00LjRh9C10YHQutCw0Y8g0LTQvtGB0YLQsNCy0LrQsCcsXG4gICAgICAgICdzbC1TSSc6ICdQb25hdmxqYWpvxI1hIHNlIGRvc3RhdmEnLFxuICAgICAgICAnc3YtU0UnOiAnw4V0ZXJrb21tYW5kZSBmcmFrdCcsXG4gICAgICAgIHRoOiAn4Liq4LmI4LiH4Liq4Li04LiZ4LiE4LmJ4Liy4Lib4Lij4Liw4LiI4LizJyxcbiAgICAgICAgdWs6ICfQn9C+0LLRgtC+0YDQvdCwINC00L7RgdGC0LDQstC60LAnLFxuICAgICAgICAnemgtQ04nOiAn57uP5bi45oCn6L+Q6L6TJyxcbiAgICAgICAgJ3poLVRXJzogJ+e2k+W4uOaAp+mBi+i8uCdcbiAgICB9LFxuICAgICdpbml0aWFsLXNoaXBwaW5nJzoge1xuICAgICAgICAnZGUtREUnOiAnRXJzdGVyIFZlcnNhbmQnLFxuICAgICAgICAnZW4tVVMnOiAnSW5pdGlhbCBzaGlwcGluZycsXG4gICAgICAgICdlcy1FUyc6ICdFbnbDrW8gaW5pY2lhbCcsXG4gICAgICAgIGZyOiAnRXhww6lkaXRpb24gaW5pdGlhbGUnLFxuICAgICAgICBpdDogJ1NwZWRpemlvbmUgaW5pemlhbGUnLFxuICAgICAgICBqYTogJ+WIneacn+eZuumAgScsXG4gICAgICAgICdyby1STyc6ICdFeHBlZGllcmUgaW5pyJtpYWzEgycsXG4gICAgICAgIGFyOiAn2KfZhNi02K3ZhiDYp9mE2KPZiNmE2YonLFxuICAgICAgICBjYTogJ0VudmlhbWVudCBpbmljaWFsJyxcbiAgICAgICAgJ2NzLUNaJzogJ1BvxI3DoXRlxI1uw60gb2Rlc2zDoW7DrScsXG4gICAgICAgICdkYS1ESyc6ICdGw7hyc3RlIGZvcnNlbmRlbHNlJyxcbiAgICAgICAgZWw6ICfOkc+Bz4fOuc66zq4gzrHPgM6/z4PPhM6/zrvOricsXG4gICAgICAgICdoaS1JTic6ICfgpKrgpY3gpLDgpL7gpLDgpILgpK3gpL/gpJUg4KS24KS/4KSq4KS/4KSC4KSXJyxcbiAgICAgICAgJ2tvLUtSJzogJ+y0iOq4sCDrsLDshqEnLFxuICAgICAgICAnbGItTFUnOiAnVWZhbmtzIFZlcnNhbmQnLFxuICAgICAgICAnbmwtTkwnOiAnRWVyc3RlIHZlcnplbmRpbmcnLFxuICAgICAgICAncHQtUFQnOiAnRW52aW8gaW5pY2lhbCcsXG4gICAgICAgICdydS1SVSc6ICfQn9C10YDQstC+0L3QsNGH0LDQu9GM0L3QsNGPINC00L7RgdGC0LDQstC60LAnLFxuICAgICAgICAnc2wtU0knOiAnWmHEjWV0bmEgZG9zdGF2YScsXG4gICAgICAgICdzdi1TRSc6ICdGw7Zyc3RhIGZyYWt0ZW4nLFxuICAgICAgICB0aDogJ+C4geC4suC4o+C4iOC4seC4lOC4quC5iOC4h+C4quC4tOC4meC4hOC5ieC4suC5gOC4muC4t+C5ieC4reC4h+C4leC5ieC4mScsXG4gICAgICAgIHVrOiAn0J/QvtGH0LDRgtC60L7QstCwINC00L7RgdGC0LDQstC60LAnLFxuICAgICAgICAnemgtQ04nOiAn5Yid5aeL6L+Q6L6TJyxcbiAgICAgICAgJ3poLVRXJzogJ+WIneWni+mBi+i8uCdcbiAgICB9LFxuICAgICdhY2NvdW50LXBhc3N3b3JkLWV4cGxhbmF0aW9uJzoge1xuICAgICAgICAnZGUtREUnOiAnRXJzdGVsbGVuIFNpZSBlaW4gbmV1ZXMgUGFzc3dvcnQgb2RlciB2ZXJ3ZW5kZW4gU2llIGVpbiBiZXN0ZWhlbmRlcywgd2VubiBTaWUgYmVyZWl0cyBlaW4gS29udG8gZsO8ciAuIGhhYmVuJyxcbiAgICAgICAgJ2VuLVVTJzogJ0NyZWF0ZSBhIG5ldyBwYXNzd29yZCwgb3IgdXNlIGFuIGV4aXN0aW5nIG9uZSBpZiB5b3UgYWxyZWFkeSBoYXZlIGFuIGFjY291bnQgZm9yJyxcbiAgICAgICAgJ2VzLUVTJzogJ0NyZWUgdW5hIG51ZXZhIGNvbnRyYXNlw7FhIG8gdXNlIHVuYSBleGlzdGVudGUgc2kgeWEgdGllbmUgdW5hIGN1ZW50YS4gdGVuZXInLFxuICAgICAgICBmcjogJ0Nyw6lleiB1biBub3V2ZWF1IG1vdCBkZSBwYXNzZSBvdSB1dGlsaXNlei1lbiB1biBleGlzdGFudCBzaSB2b3VzIGF2ZXogZMOpasOgIHVuIGNvbXB0ZSBwb3VyJyxcbiAgICAgICAgaXQ6ICdDcmVhIHVuYSBudW92YSBwYXNzd29yZCBvIHVzYW5lIHVuYSBlc2lzdGVudGUgc2UgaGFpIGdpw6AgdW4gYWNjb3VudCBwZXInLFxuICAgICAgICBqYTogJ+aWsOOBl+OBhOODkeOCueODr+ODvOODieOCkuS9nOaIkOOBmeOCi+OBi+OAgeOBmeOBp+OBq+OCouOCq+OCpuODs+ODiOOCkuOBiuaMgeOBoeOBruWgtOWQiOOBr+aXouWtmOOBruODkeOCueODr+ODvOODieOCkuS9v+eUqOOBl+OBpuOBj+OBoOOBleOBhCcsXG4gICAgICAgICdyby1STyc6ICdDcmVhyJtpIG8gcGFyb2zEgyBub3XEgyBzYXUgdXRpbGl6YcibaSB1bmEgZXhpc3RlbnTEgyBkYWPEgyBhdmXIm2kgZGVqYSB1biBjb250IHBlbnRydScsXG4gICAgICAgIGFyOiAn2KPZhti02KYg2YPZhNmF2Kkg2YXYsdmI2LEg2KzYr9mK2K/YqSDYjCDYo9mIINin2LPYqtiu2K/ZhSDZg9mE2YXYqSDZhdix2YjYsSDZhdmI2KzZiNiv2Kkg2KXYsNinINmD2KfZhiDZhNiv2YrZgyDYqNin2YTZgdi52YQg2K3Ys9in2Kgg2YTZgCcsXG4gICAgICAgIGNhOiAnQ3JlZXUgdW5hIGNvbnRyYXNlbnlhIG5vdmEgbyB1dGlsaXR6ZXUtbmUgdW5hIHNpIGphIHRlbml1IHVuIGNvbXB0ZScsXG4gICAgICAgICdjcy1DWic6ICdWeXR2b8WZdGUgbm92w6kgaGVzbG8gbmVibyBwb3XFvmlqdGUgc3TDoXZhasOtY8OtLCBwb2t1ZCBqacW+IG3DoXRlIMO6xI1ldCcsXG4gICAgICAgICdkYS1ESyc6ICdPcHJldCBlbiBueSBhZGdhbmdza29kZSwgZWxsZXIgYnJ1ZyBlbiBla3Npc3RlcmVuZGUsIGh2aXMgZHUgYWxsZXJlZGUgaGFyIGVuIGtvbnRvIHRpbCcsXG4gICAgICAgIGVsOiAnzpTOt868zrnOv8+Fz4HOs86uz4PPhM61IM6tzr3Osc69IM69zq3OvyDOus+JzrTOuc66z4wgz4DPgc+Mz4POss6xz4POt8+CIM6uIM+Hz4HOt8+DzrnOvM6/z4DOv865zq7Pg8+EzrUgzq3Ovc6xzr0gz4XPgM6sz4HPh86/zr3PhM6xLCDOtc6szr0gzq3Ph861z4TOtSDOrs60zrcgzrvOv86zzrHPgc65zrHPg868z4wnLFxuICAgICAgICAnaGktSU4nOiAn4KSP4KSVIOCkqOCkr+CkviDgpKrgpL7gpLjgpLXgpLDgpY3gpKEg4KSs4KSo4KS+4KSP4KSCLCDgpK/gpL4g4KSV4KS/4KS44KWAIOCkruCljOCknOClguCkpuCkviDgpKrgpL7gpLjgpLXgpLDgpY3gpKEg4KSV4KS+IOCkieCkquCkr+Cli+CklyDgpJXgpLDgpYfgpIIg4KSv4KSm4KS/IOCkhuCkquCkleClhyDgpKrgpL7gpLgg4KSq4KS54KSy4KWHIOCkuOClhyDgpLngpYAg4KSP4KSVIOCkluCkvuCkpOCkviDgpLngpYgnLFxuICAgICAgICAna28tS1InOiAn7IOIIOu5hOuwgOuyiO2YuOulvCDsg53shLHtlZjqsbDrgpgg7J2066+4IOqzhOygleydtCDsnojripQg6rK97JqwIOq4sOyhtCDruYTrsIDrsojtmLjrpbwg7IKs7Jqp7ZWY7Iut7Iuc7JikLicsXG4gICAgICAgICdsYi1MVSc6ICdFcnN0ZWxsdCBlbiBuZWl0IFBhc3N3dWVydCwgb2RlciBiZW5vdHp0IGVuIGV4aXN0ZW50IFBhc3N3dWVydCB3YW5uIERpciBzY2hvbm4gZSBLb250IGh1dHQnLFxuICAgICAgICAnbmwtTkwnOiAnTWFhayBlZW4gbmlldXcgd2FjaHR3b29yZCBhYW4sIG9mIGdlYnJ1aWsgZWVuIGJlc3RhYW5kIHdhY2h0d29vcmQgYWxzIGplIGFsIGVlbiBhY2NvdW50IGhlYnQgdm9vcicsXG4gICAgICAgICdwdC1QVCc6ICdDcmllIHVtYSBub3ZhIHNlbmhhIG91IHVzZSB1bWEgZXhpc3RlbnRlIHNlIHZvY8OqIGrDoSB0aXZlciB1bWEgY29udGEgcGFyYScsXG4gICAgICAgICdydS1SVSc6ICfQodC+0LfQtNCw0LnRgtC1INC90L7QstGL0Lkg0L/QsNGA0L7Qu9GMINC40LvQuCDQuNGB0L/QvtC70YzQt9GD0LnRgtC1INGB0YPRidC10YHRgtCy0YPRjtGJ0LjQuSwg0LXRgdC70Lgg0YMg0LLQsNGBINGD0LbQtSDQtdGB0YLRjCDRg9GH0LXRgtC90LDRjyDQt9Cw0L/QuNGB0Ywg0LTQu9GPJyxcbiAgICAgICAgJ3NsLVNJJzogJ1VzdHZhcml0ZSBub3ZvIGdlc2xvIGFsaSB1cG9yYWJpdGUgb2JzdG9qZcSNZSwgxI1lIMW+ZSBpbWF0ZSByYcSNdW4nLFxuICAgICAgICAnc3YtU0UnOiAnU2thcGEgZXR0IG55dHQgbMO2c2Vub3JkLCBlbGxlciBhbnbDpG5kIGV0dCBiZWZpbnRsaWd0IG9tIGR1IHJlZGFuIGhhciBldHQga29udG8gZsO2cicsXG4gICAgICAgIHRoOiAn4Liq4Lij4LmJ4Liy4LiH4Lij4Lir4Lix4Liq4Lic4LmI4Liy4LiZ4LmD4Lir4Lih4LmI4Lir4Lij4Li34Lit4LmD4LiK4LmJ4Lij4Lir4Lix4Liq4Lic4LmI4Liy4LiZ4LiX4Li14LmI4Lih4Li14Lit4Lii4Li54LmI4LiW4LmJ4Liy4LiE4Li44LiT4Lih4Li14Lia4Lix4LiN4LiK4Li14Liq4Liz4Lir4Lij4Lix4LiaJyxcbiAgICAgICAgdWs6ICfQodGC0LLQvtGA0ZbRgtGMINC90L7QstC40Lkg0L/QsNGA0L7Qu9GMINCw0LHQviDQstC40LrQvtGA0LjRgdGC0L7QstGD0LnRgtC1INGW0YHQvdGD0Y7Rh9C40LksINGP0LrRidC+INGDINCy0LDRgSDQstC20LUg0ZQg0L7QsdC70ZbQutC+0LLQuNC5INC30LDQv9C40YEnLFxuICAgICAgICAnemgtQ04nOiAn5Yib5bu65LiA5Liq5paw5a+G56CB77yM5aaC5p6c5oKo5bey57uP5pyJ5LiA5Liq5biQ5oi377yM5YiZ5L2/55So546w5pyJ55qE5a+G56CBJyxcbiAgICAgICAgJ3poLVRXJzogJ+WJteW7uuS4gOWAi+aWsOWvhueivO+8jOWmguaenOaCqOW3sue2k+acieS4gOWAi+W4s+aItu+8jOWJh+S9v+eUqOePvuacieeahOWvhueivCdcbiAgICB9LFxuICAgICdhY2NvdW50LXBhc3N3b3JkJzoge1xuICAgICAgICAnZGUtREUnOiAnUGFzc3dvcnQnLFxuICAgICAgICAnZW4tVVMnOiAnUGFzc3dvcmQnLFxuICAgICAgICAnZXMtRVMnOiAnQ2xhdmUnLFxuICAgICAgICBmcjogJ01vdCBkZSBwYXNzZScsXG4gICAgICAgIGl0OiAnUGFyb2xhIGRcXCdvcmRpbmUnLFxuICAgICAgICBqYTogJ+ODkeOCueODr+ODvOODiScsXG4gICAgICAgICdyby1STyc6ICdQYXJvbGEnLFxuICAgICAgICBhcjogJ9mD2YTZhdmHINin2YTYs9ixJyxcbiAgICAgICAgY2E6ICdDb250cmFzZW55YScsXG4gICAgICAgICdjcy1DWic6ICdIZXNsbycsXG4gICAgICAgICdkYS1ESyc6ICdBZGdhbmdza29kZScsXG4gICAgICAgIGVsOiAnzprPic60zrnOus+Mz4Igz4DPgc+Mz4POss6xz4POt8+CJyxcbiAgICAgICAgJ2hpLUlOJzogJ+CkquCkvuCkuOCkteCksOCljeCkoScsXG4gICAgICAgICdrby1LUic6ICfruYTrsIDrsojtmLgnLFxuICAgICAgICAnbGItTFUnOiAnUGFzc3d1ZXJ0JyxcbiAgICAgICAgJ25sLU5MJzogJ1dhY2h0d29vcmQnLFxuICAgICAgICAncHQtUFQnOiAnU2VuaGEnLFxuICAgICAgICAncnUtUlUnOiAn0J/QsNGA0L7Qu9GMJyxcbiAgICAgICAgJ3NsLVNJJzogJ0dlc2xvJyxcbiAgICAgICAgJ3N2LVNFJzogJ0zDtnNlbm9yZCcsXG4gICAgICAgIHRoOiAn4Lij4Lir4Lix4Liq4Lic4LmI4Liy4LiZJyxcbiAgICAgICAgdWs6ICfQn9Cw0YDQvtC70YwnLFxuICAgICAgICAnemgtQ04nOiAn5a+G56CBJyxcbiAgICAgICAgJ3poLVRXJzogJ+WvhueivCdcbiAgICB9LFxuICAgICdpbnZhbGlkLW1lcmNoYW50LXBhc3N3b3JkJzoge1xuICAgICAgICAnZGUtREUnOiAnRGFzIGVpbmdlZ2ViZW5lIFBhc3N3b3J0IG11c3MgbWluZGVzdGVucyA4IFplaWNoZW4gbGFuZyBzZWluLicsXG4gICAgICAgICdlbi1VUyc6ICdUaGUgcGFzc3dvcmQgZW50ZXJlZCBtdXN0IGJlIGF0IGxlYXN0IDggY2hhcmFjdGVycyBsb25nLicsXG4gICAgICAgICdlcy1FUyc6ICdMYSBjb250cmFzZcOxYSBpbmdyZXNhZGEgZGViZSB0ZW5lciBhbCBtZW5vcyA4IGNhcmFjdGVyZXMuJyxcbiAgICAgICAgZnI6ICdMZSBtb3QgZGUgcGFzc2Ugc2Fpc2kgZG9pdCBjb21wb3J0ZXIgYXUgbW9pbnMgOCBjYXJhY3TDqHJlcy4nLFxuICAgICAgICBpdDogJ0xhIHBhc3N3b3JkIGluc2VyaXRhIGRldmUgZXNzZXJlIGx1bmdhIGFsbWVubyA4IGNhcmF0dGVyaS4nLFxuICAgICAgICBqYTogJ+WFpeWKm+OBmeOCi+ODkeOCueODr+ODvOODieOBrzjmloflrZfku6XkuIrjgafjgYLjgovlv4XopoHjgYzjgYLjgorjgb7jgZnjgIInLFxuICAgICAgICAncm8tUk8nOiAnUGFyb2xhIGludHJvZHVzxIMgdHJlYnVpZSBzxIMgYWlixIMgY2VsIHB1yJtpbiA4IGNhcmFjdGVyZS4nLFxuICAgICAgICBhcjogJ9mK2KzYqCDYo9mGINiq2KrZg9mI2YYg2YPZhNmF2Kkg2KfZhNmF2LHZiNixINin2YTZhdiv2K7ZhNipINmF2YYgOCDYo9it2LHZgSDYudmE2Ykg2KfZhNij2YLZhC4nLFxuICAgICAgICBjYTogJ0xhIGNvbnRyYXNlbnlhIGludHJvZHXDr2RhIGhhIGRlIHRlbmlyIGNvbSBhIG3DrW5pbSA4IGNhcsOgY3RlcnMuJyxcbiAgICAgICAgJ2NzLUNaJzogJ1phZGFuw6kgaGVzbG8gbXVzw60gbcOtdCBhbGVzcG/FiCA4IHpuYWvFry4nLFxuICAgICAgICAnZGEtREsnOiAnRGVuIGluZHRhc3RlZGUgYWRnYW5nc2tvZGUgc2thbCB2w6ZyZSBtaW5kc3QgOCB0ZWduIGxhbmcuJyxcbiAgICAgICAgZWw6ICfOnyDOus+JzrTOuc66z4zPgiDPgM+Bz4zPg86yzrHPg863z4Igz4DOv8+FIM6tz4fOtc65IM61zrnPg86xz4fOuM61zq8gz4DPgc6tz4DOtc65IM69zrEgzq3Ph861zrkgz4TOv8+FzrvOrM+HzrnPg8+Ezr/OvSA4IM+HzrHPgc6xzrrPhM6uz4HOtc+CLicsXG4gICAgICAgICdoaS1JTic6ICfgpKbgpLDgpY3gpJwg4KSV4KS/4KSv4KS+IOCkl+Ckr+CkviDgpKrgpL7gpLjgpLXgpLDgpY3gpKEg4KSV4KSuIOCkuOClhyDgpJXgpK4gOCDgpLXgpLDgpY3gpKMg4KSy4KSC4KSs4KS+IOCkueCli+CkqOCkviDgpJrgpL7gpLngpL/gpI/gpaQnLFxuICAgICAgICAna28tS1InOiAn7J6F66Cl7ZWcIOu5hOuwgOuyiO2YuOuKlCA47J6QIOydtOyDgeydtOyWtOyVvCDtlanri4jri6QuJyxcbiAgICAgICAgJ2xiLUxVJzogJ0RcXCdQYXNzd3VlcnQgZGF0IGFnaW5uIGFzcyBtdXNzIG9wIGRcXCdtYW5uc3QgOCBaZWVjaGUgbGFhbmcgc2lubi4nLFxuICAgICAgICAnbmwtTkwnOiAnSGV0IGluZ2V2b2VyZGUgd2FjaHR3b29yZCBtb2V0IG1pbmltYWFsIDggdGVrZW5zIGxhbmcgemlqbi4nLFxuICAgICAgICAncHQtUFQnOiAnQSBzZW5oYSBpbnNlcmlkYSBkZXZlIHRlciBwZWxvIG1lbm9zIDggY2FyYWN0ZXJlcy4nLFxuICAgICAgICAncnUtUlUnOiAn0JLQstC10LTQtdC90L3Ri9C5INC/0LDRgNC+0LvRjCDQtNC+0LvQttC10L0g0YHQvtGB0YLQvtGP0YLRjCDQvdC1INC80LXQvdC10LUg0YfQtdC8INC40LcgOCDRgdC40LzQstC+0LvQvtCyLicsXG4gICAgICAgICdzbC1TSSc6ICdWbmVzZW5vIGdlc2xvIG1vcmEgYml0aSBkb2xnbyBuYWptYW5qIDggem5ha292LicsXG4gICAgICAgICdzdi1TRSc6ICdMw7ZzZW5vcmRldCBtw6VzdGUgdmFyYSBtaW5zdCA4IHRlY2tlbiBsw6VuZ3QuJyxcbiAgICAgICAgdGg6ICfguKPguKvguLHguKrguJzguYjguLLguJnguJfguLXguYjguJvguYnguK3guJnguJXguYnguK3guIfguKHguLXguITguKfguLLguKHguKLguLLguKfguK3guKLguYjguLLguIfguJnguYnguK3guKIgOCDguJXguLHguKfguK3guLHguIHguKnguKMnLFxuICAgICAgICB1azogJ9CS0LLQtdC00LXQvdC40Lkg0L/QsNGA0L7Qu9GMINC/0L7QstC40L3QtdC9INC80ZbRgdGC0LjRgtC4INC90LUg0LzQtdC90YjQtSA4INGB0LjQvNCy0L7Qu9GW0LIuJyxcbiAgICAgICAgJ3poLUNOJzogJ+i+k+WFpeeahOWvhueggemVv+W6puW/hemhu+iHs+WwkeS4uiA4IOS4quWtl+espuOAgicsXG4gICAgICAgICd6aC1UVyc6ICfovLjlhaXnmoTlr4bnorzplbfluqblv4XpoIjoh7PlsJHngrogOCDlgIvlrZfnrKbjgIInXG4gICAgfSxcbiAgICB1bmtub3duOiB7XG4gICAgICAgICdkZS1ERSc6ICdVbmJla2FubnQnLFxuICAgICAgICAnZW4tVVMnOiAnVW5rbm93bicsXG4gICAgICAgICdlcy1FUyc6ICdEZXNjb25vY2lkbycsXG4gICAgICAgIGZyOiAnSW5jb25udScsXG4gICAgICAgIGl0OiAnU2Nvbm9zY2l1dG8nLFxuICAgICAgICBqYTogJ+S4jeaYjicsXG4gICAgICAgICdyby1STyc6ICdOZWN1bm9zY3V0JyxcbiAgICAgICAgYXI6ICfZhdis2YfZiNmEJyxcbiAgICAgICAgY2E6ICdEZXNjb25lZ3V0JyxcbiAgICAgICAgJ2NzLUNaJzogJ05lem7DoW3DvScsXG4gICAgICAgICdkYS1ESyc6ICdVa2VuZHQnLFxuICAgICAgICBlbDogJ86RzrPOvc+Jz4PPhM6/z4InLFxuICAgICAgICAnaGktSU4nOiAn4KSF4KSo4KSc4KS+4KSoJyxcbiAgICAgICAgJ2tvLUtSJzogJ+yVjOugpOyngOyngCDslYrsnYAnLFxuICAgICAgICAnbGItTFUnOiAnT25iZWthbm50JyxcbiAgICAgICAgJ25sLU5MJzogJ09uYmVrZW5kJyxcbiAgICAgICAgJ3B0LVBUJzogJ0Rlc2NvbmhlY2lkYScsXG4gICAgICAgICdydS1SVSc6ICfQndC10LjQt9Cy0LXRgdGC0L3Ri9C5JyxcbiAgICAgICAgJ3NsLVNJJzogJ05lem5hbm8nLFxuICAgICAgICAnc3YtU0UnOiAnT2vDpG5kJyxcbiAgICAgICAgdGg6ICfguYTguKHguYjguKPguLnguYnguIjguLHguIEnLFxuICAgICAgICB1azogJ9Cd0LXQstGW0LTQvtC80LjQuScsXG4gICAgICAgICd6aC1DTic6ICfmnKrnn6UnLFxuICAgICAgICAnemgtVFcnOiAn5pyq55+lJ1xuICAgIH0sXG4gICAgJ3Rlc3QtbW9kZS1iYW5uZXInOiB7XG4gICAgICAgICdkZS1ERSc6ICdUZXN0bW9kdXM6IEt1bmRlbiBrw7ZubmVuIFBlYWNoUGF5IG5pY2h0IHNlaGVuJyxcbiAgICAgICAgJ2VuLVVTJzogJ1Rlc3QgbW9kZTogY3VzdG9tZXJzIGNhbm5vdCBzZWUgUGVhY2hQYXknLFxuICAgICAgICAnZXMtRVMnOiAnTW9kbyBkZSBwcnVlYmE6IGxvcyBjbGllbnRlcyBubyBwdWVkZW4gdmVyIFBlYWNoUGF5JyxcbiAgICAgICAgZnI6ICdNb2RlIHRlc3QgOiBsZXMgY2xpZW50cyBuZSBwZXV2ZW50IHBhcyB2b2lyIFBlYWNoUGF5JyxcbiAgICAgICAgaXQ6ICdNb2RhbGl0w6AgdGVzdDogaSBjbGllbnRpIG5vbiBwb3Nzb25vIHZlZGVyZSBQZWFjaFBheScsXG4gICAgICAgIGphOiAn44OG44K544OI44Oi44O844OJ77ya6aGn5a6i44GvUGVhY2hQYXnjgpLopovjgovjgZPjgajjgYzjgafjgY3jgb7jgZvjgpMnLFxuICAgICAgICAncm8tUk8nOiAnTW9kIGRlIHRlc3RhcmU6IGNsaWVuyJtpaSBudSBwb3QgdmVkZWEgUGVhY2hQYXknLFxuICAgICAgICBhcjogJ9mI2LbYuSDYp9mE2KfYrtiq2KjYp9ixOiDZhNinINmK2YXZg9mGINmE2YTYudmF2YTYp9ihINix2KTZitipIFBlYWNoUGF5JyxcbiAgICAgICAgY2E6ICdNb2RlIGRlIHByb3ZhOiBlbHMgY2xpZW50cyBubyBwb2RlbiB2ZXVyZSBQZWFjaFBheScsXG4gICAgICAgICdjcy1DWic6ICdUZXN0b3ZhY8OtIHJlxb5pbTogesOha2F6bsOtY2kgbmV2aWTDrSBQZWFjaFBheScsXG4gICAgICAgICdkYS1ESyc6ICdUZXN0dGlsc3RhbmQ6IGt1bmRlciBrYW4gaWtrZSBzZSBQZWFjaFBheScsXG4gICAgICAgIGVsOiAnzpvOtc65z4TOv8+Fz4HOs86vzrEgzrTOv866zrnOvM6uz4I6IM6/zrkgz4DOtc67zqzPhM61z4IgzrTOtc69IM68z4DOv8+Bzr/Pjc69IM69zrEgzrTOv8+Fzr0gz4TOvyBQZWFjaFBheScsXG4gICAgICAgICdoaS1JTic6ICfgpKrgpLDgpYDgpJXgpY3gpLfgpKMg4KSu4KWL4KShOiDgpJfgpY3gpLDgpL7gpLngpJUg4KSq4KWA4KSa4KSq4KWHIOCkqOCkueClgOCkgiDgpKbgpYfgpJYg4KS44KSV4KSk4KWHIOCkueCliOCkgicsXG4gICAgICAgICdrby1LUic6ICfthYzsiqTtirgg66qo65OcOiDqs6DqsJ3snbQgUGVhY2hQYXnrpbwg67O8IOyImCDsl4bsirXri4jri6QuJyxcbiAgICAgICAgJ2xiLUxVJzogJ1Rlc3Rtb2R1czogQ2xpZW50ZW4ga8Orbm5lbiBQZWFjaFBheSBuZXQgZ2VzaW5uJyxcbiAgICAgICAgJ25sLU5MJzogJ1Rlc3Rtb2R1czoga2xhbnRlbiBrdW5uZW4gUGVhY2hQYXkgbmlldCB6aWVuJyxcbiAgICAgICAgJ3B0LVBUJzogJ01vZG8gZGUgdGVzdGU6IG9zIGNsaWVudGVzIG7Do28gcG9kZW0gdmVyIG8gUGVhY2hQYXknLFxuICAgICAgICAncnUtUlUnOiAn0KLQtdGB0YLQvtCy0YvQuSDRgNC10LbQuNC8OiDQutC70LjQtdC90YLRiyDQvdC1INCy0LjQtNGP0YIgUGVhY2hQYXknLFxuICAgICAgICAnc2wtU0knOiAnVGVzdG5pIG5hxI1pbjogc3RyYW5rZSBuZSB2aWRpam8gUGVhY2hQYXknLFxuICAgICAgICAnc3YtU0UnOiAnVGVzdGzDpGdlOiBrdW5kZXIga2FuIGludGUgc2UgUGVhY2hQYXknLFxuICAgICAgICB0aDogJ+C5guC4q+C4oeC4lOC4l+C4lOC4quC4reC4mjog4Lil4Li54LiB4LiE4LmJ4Liy4LmE4Lih4LmI4Liq4Liy4Lih4Liy4Lij4LiW4LmA4Lir4LmH4LiZIFBlYWNoUGF5JyxcbiAgICAgICAgdWs6ICfQotC10YHRgtC+0LLQuNC5INGA0LXQttC40Lw6INC60LvRltGU0L3RgtC4INC90LUg0LzQvtC20YPRgtGMINCx0LDRh9C40YLQuCBQZWFjaFBheScsXG4gICAgICAgICd6aC1DTic6ICfmtYvor5XmqKHlvI/vvJrlrqLmiLfnnIvkuI3liLBQZWFjaFBheScsXG4gICAgICAgICd6aC1UVyc6ICfmuKzoqabmqKHlvI/vvJrlrqLmiLbnnIvkuI3liLBQZWFjaFBheSdcbiAgICB9LFxuICAgICd2ZXJpZnktbG9jYXRpb24nOiB7XG4gICAgICAgICdkZS1ERSc6ICdJY2ggYmVzdMOkdGlnZSwgZGFzcyBkYXMgTGFuZCwgaW4gZGVtIGljaCBlaW5nZXJlaXN0IGJpbiwgZGFzIExhbmQgaXN0LCBpbiBkZW0gaWNoIHdvaG5lJyxcbiAgICAgICAgJ2VuLVVTJzogJ0kgdmVyaWZ5IHRoYXQgdGhlIGNvdW50cnkgSSBoYXZlIGVudGVyZWQgaXMgdGhlIG9uZSBJIHJlc2lkZSBpbicsXG4gICAgICAgICdlcy1FUyc6ICdWZXJpZmljbyBxdWUgZWwgcGHDrXMgYWwgcXVlIGhlIGVudHJhZG8gZXMgZW4gZWwgcXVlIHJlc2lkbycsXG4gICAgICAgIGZyOiAnSmUgdsOpcmlmaWUgcXVlIGxlIHBheXMgZGFucyBsZXF1ZWwgamUgc3VpcyBlbnRyw6kgZXN0IGNlbHVpIGRhbnMgbGVxdWVsIGplIHLDqXNpZGUnLFxuICAgICAgICBpdDogJ1ZlcmlmaWNvIGNoZSBpbCBwYWVzZSBpbiBjdWkgc29ubyBlbnRyYXRvIHNpYSBxdWVsbG8gaW4gY3VpIHJpc2llZG8nLFxuICAgICAgICBqYTogJ+WFpeWKm+OBl+OBn+WbveOBjOWxheS9j+WbveOBp+OBguOCi+OBk+OBqOOCkueiuuiqjeOBl+OBvuOBmScsXG4gICAgICAgICdyby1STyc6ICdWZXJpZmljIGPEgyDIm2FyYSDDrm4gY2FyZSBhbSBpbnRyYXQgZXN0ZSBjZWEgw65uIGNhcmUgbG9jdWllc2MnLFxuICAgICAgICBhcjogJ9ij2KrYrdmC2YIg2YXZhiDYo9mGINin2YTYqNmE2K8g2KfZhNiw2Yog2KPYr9iu2YTYqtmHINmH2Ygg2KfZhNio2YTYryDYp9mE2LDZiiDYo9mC2YrZhSDZgdmK2YcnLFxuICAgICAgICBjYTogJ1ZlcmlmaWNvIHF1ZSBlbCBwYcOtcyBvbiBoZSBlbnRyYXQgw6lzIGVsIG9uIHZpc2MnLFxuICAgICAgICAnY3MtQ1onOiAnT3bEm8WZdWppLCDFvmUgemVtxJssIGRvIGt0ZXLDqSBqc2VtIHphZGFsLCBqZSB6ZW3DrSwgdmUga3RlcsOpIGJ5ZGzDrW0nLFxuICAgICAgICAnZGEtREsnOiAnSmVnIGJla3LDpmZ0ZXIsIGF0IGRldCBsYW5kLCBqZWcgaGFyIGluZHRhc3RldCwgZXIgZGV0LCBqZWcgYm9yIGknLFxuICAgICAgICBlbDogJ86Vz4DOsc67zrfOuM61z43PiSDPjM+Ezrkgzrcgz4fPjs+BzrEgz4PPhM63zr0gzr/PgM6/zq/OsSDOrc+Hz4kgzrXOuc+Dzq3Ou864zrXOuSDOtc6vzr3Osc65IM6xz4XPhM6uIM+Dz4TOt869IM6/z4DOv86vzrEgzrTOuc6xzrzOrc69z4knLFxuICAgICAgICAnaGktSU4nOiAn4KSu4KWI4KSCIOCkuOCkpOCljeCkr+CkvuCkquCkv+CkpCDgpJXgpLDgpKTgpL4v4KSV4KSw4KSk4KWAIOCkueClguCkgiDgpJXgpL8g4KSc4KS/4KS4IOCkpuClh+CktiDgpK7gpYfgpIIg4KSu4KWI4KSC4KSo4KWHIOCkquCljeCksOCkteClh+CktiDgpJXgpL/gpK/gpL4g4KS54KWIIOCkteCkuSDgpLXgpLngpYAg4KSm4KWH4KS2IOCkueCliCDgpJzgpL/gpLjgpK7gpYfgpIIg4KSu4KWI4KSCIOCksOCkueCkpOCkviDgpLngpYLgpIInLFxuICAgICAgICAna28tS1InOiAn64K06rCAIOyeheugpe2VnCDqta3qsIDqsIAg64K06rCAIOqxsOyjvO2VmOuKlCDqta3qsIDsnbjsp4Ag7ZmV7J247ZWp64uI64ukLicsXG4gICAgICAgICdsYi1MVSc6ICdFY2ggdmVyaWZpesOpaWVyZW4gZGF0dCBkXFwnTGFuZCB3b3UgZWNoIGFnaW5uIGh1bm4gYXNzIGRlZW4gYW4gZGVlbSBlY2ggd3VubmVuJyxcbiAgICAgICAgJ25sLU5MJzogJ0lrIHZlcmlmaWVlciBkYXQgaGV0IGxhbmQgZGF0IGlrIGhlYiBpbmdldm9lcmQgaGV0IGxhbmQgaXMgd2FhcmluIGlrIHdvb24nLFxuICAgICAgICAncHQtUFQnOiAnRXUgdmVyaWZpY28gc2UgbyBwYcOtcyBxdWUgZXUgaW5zZXJpIMOpIGFxdWVsZSBlbSBxdWUgcmVzaWRvJyxcbiAgICAgICAgJ3J1LVJVJzogJ9CvINC/0L7QtNGC0LLQtdGA0LbQtNCw0Y4sINGH0YLQviDRgdGC0YDQsNC90LAsINCyINC60L7RgtC+0YDRg9GOINGPINCy0YrQtdGF0LDQuywg0Y/QstC70Y/QtdGC0YHRjyDRgtC+0LksINCyINC60L7RgtC+0YDQvtC5INGPINC/0YDQvtC20LjQstCw0Y4nLFxuICAgICAgICAnc2ktU0knOiAnUG90cmp1amVtLCBkYSBqZSBkcsW+YXZhLCB2IGthdGVybyBzZW0gdnN0b3BpbCwgdGlzdGEsIHYga2F0ZXJpIHByZWJpdmFtJyxcbiAgICAgICAgJ3NpLVNFJzogJ0phZyB2ZXJpZmllcmFyIGF0dCBkZXQgbGFuZCBqYWcgaGFyIGFuZ2V0dCDDpHIgZGV0IGphZyBib3IgaScsXG4gICAgICAgIHRoOiAn4LiJ4Lix4LiZ4Lii4Li34LiZ4Lii4Lix4LiZ4Lin4LmI4Liy4Lib4Lij4Liw4LmA4LiX4Lio4LiX4Li14LmI4LiJ4Lix4LiZ4LmA4LiC4LmJ4Liy4Lih4Liy4LmA4Lib4LmH4LiZ4Lib4Lij4Liw4LmA4LiX4Lio4LiX4Li14LmI4LiJ4Lix4LiZ4Lit4Liy4Lio4Lix4Lii4Lit4Lii4Li54LmIJyxcbiAgICAgICAgdWs6ICfQryDQv9GW0LTRgtCy0LXRgNC00LbRg9GOLCDRidC+INC60YDQsNGX0L3QsCwg0LIg0Y/QutGDINGPINCy0LLRltC50YjQvtCyLCDRlCDRgtGW0ZTRjiwg0LIg0Y/QutGW0Lkg0Y8g0L/RgNC+0LbQuNCy0LDRjicsXG4gICAgICAgICd6aC1DTic6ICfmiJHnoa7orqTmiJHov5vlhaXnmoTlm73lrrbmmK/miJHlsYXkvY/nmoTlm73lrrYnLFxuICAgICAgICAnemgtVFcnOiAn5oiR56K66KqN5oiR6YCy5YWl55qE5ZyL5a625piv5oiR5bGF5L2P55qE5ZyL5a62J1xuICAgIH1cbn07XG5jb25zdCBGZWF0dXJlID0ge1xuICAgIGVuYWJsZWQ6IChmbGFnKT0+c3RvcmUuZ2V0U3RhdGUoKS5lbnZpcm9ubWVudC5wbHVnaW4uZmVhdHVyZVN1cHBvcnRbZmxhZ10/LmVuYWJsZWQgPz8gZmFsc2VcbiAgICAsXG4gICAgdmVyc2lvbjogKGZsYWcpPT5zdG9yZS5nZXRTdGF0ZSgpLmVudmlyb25tZW50LnBsdWdpbi5mZWF0dXJlU3VwcG9ydFtmbGFnXT8udmVyc2lvbiA/PyAwXG4gICAgLFxuICAgIG1ldGFEYXRhOiAoZmxhZywga2V5KT0+c3RvcmUuZ2V0U3RhdGUoKS5lbnZpcm9ubWVudC5wbHVnaW4uZmVhdHVyZVN1cHBvcnRbZmxhZ10/Lm1ldGFfZGF0YT8uW2tleV0gPz8gbnVsbFxufTtcbnZhciBGZWF0dXJlRmxhZztcbihmdW5jdGlvbihGZWF0dXJlRmxhZzEpIHtcbiAgICBGZWF0dXJlRmxhZzFbXCJDQVJUX0NBTENVTEFUSU9OXCJdID0gJ2NhcnRfY2FsY3VsYXRpb24nO1xuICAgIEZlYXR1cmVGbGFnMVtcIkNPVVBPTl9JTlBVVFwiXSA9ICdjb3Vwb25faW5wdXQnO1xuICAgIEZlYXR1cmVGbGFnMVtcIkdJRlRDQVJEX0lOUFVUXCJdID0gJ2dpZnRjYXJkX2lucHV0JztcbiAgICBGZWF0dXJlRmxhZzFbXCJPUkRFUl9OT1RFU1wiXSA9ICdvcmRlcl9ub3Rlc19pbnB1dCc7XG4gICAgRmVhdHVyZUZsYWcxW1wiQURESVRJT05BTF9GSUVMRFNcIl0gPSAnYWRkaXRpb25hbF9maWVsZHMnO1xuICAgIEZlYXR1cmVGbGFnMVtcIlNUUklQRVwiXSA9ICdzdHJpcGVfcGF5bWVudF9tZXRob2QnO1xuICAgIEZlYXR1cmVGbGFnMVtcIlNUUklQRV9QQVlNRU5UX1JFUVVFU1RcIl0gPSAnc3RyaXBlX3BheW1lbnRfcmVxdWVzdCc7XG4gICAgRmVhdHVyZUZsYWcxW1wiUVVBTlRJVFlfQ0hBTkdFUlwiXSA9ICdxdWFudGl0eV9jaGFuZ2VyJztcbn0pKEZlYXR1cmVGbGFnIHx8IChGZWF0dXJlRmxhZyA9IHt9KSk7XG5jb25zdCB1cGRhdGVDdXN0b21lclN0cmlwZUlkID0gY3JlYXRlRGlzcGF0Y2hVcGRhdGUoRGlzcGF0Y2hBY3Rpb25UeXBlLlBFQUNIUEFZX0NVU1RPTUVSX1NUUklQRV9JRCk7XG5jb25zdCB1cGRhdGVDdXN0b21lciA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5QRUFDSFBBWV9DVVNUT01FUik7XG5jb25zdCB1cGRhdGVDdXN0b21lclNoaXBwaW5nU2hvcnRBZGRyZXNzID0gY3JlYXRlRGlzcGF0Y2hVcGRhdGUoRGlzcGF0Y2hBY3Rpb25UeXBlLlBFQUNIUEFZX0NVU1RPTUVSX1NISVBQSU5HKTtcbmNvbnN0IHVwZGF0ZVByZWZlcnJlZFBheW1lbnRNZXRob2QgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuUEVBQ0hQQVlfQ1VTVE9NRVJfUEFZTUVOVF9NRVRIT0QpO1xuY29uc3QgUGVhY2hQYXlDdXN0b21lciA9IHtcbiAgICBkYXRhOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5wZWFjaFBheUN1c3RvbWVyXG4gICAgLFxuICAgIGVtYWlsOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5wZWFjaFBheUN1c3RvbWVyLmVtYWlsXG4gICAgLFxuICAgIGZpcnN0TmFtZTogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5uYW1lX2ZpcnN0XG4gICAgLFxuICAgIGxhc3ROYW1lOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5wZWFjaFBheUN1c3RvbWVyLm5hbWVfbGFzdFxuICAgICxcbiAgICBwaG9uZTogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5waG9uZVxuICAgICxcbiAgICBhZGRyZXNzMTogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5hZGRyZXNzMVxuICAgICxcbiAgICBhZGRyZXNzMjogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5hZGRyZXNzMlxuICAgICxcbiAgICBjaXR5OiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5wZWFjaFBheUN1c3RvbWVyLmNpdHlcbiAgICAsXG4gICAgc3RhdGU6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5Q3VzdG9tZXIuc3RhdGVcbiAgICAsXG4gICAgY291bnRyeTogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5jb3VudHJ5XG4gICAgLFxuICAgIHBvc3RhbDogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5wb3N0YWxcbiAgICAsXG4gICAgY2FyZDogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5jYXJkXG4gICAgLFxuICAgIHByZWZlcnJlZFBheW1lbnRNZXRob2Q6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5Q3VzdG9tZXIucGF5bWVudF9vcHRpb24gPz8gJ3N0cmlwZSdcbiAgICAsXG4gICAgc3RyaXBlSWQ6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5Q3VzdG9tZXIuc3RyaXBlX2N1c3RvbWVyX2lkID8/ICcnXG4gICAgLFxuICAgIHN0cmlwZURldGFpbHM6ICgpPT4oe1xuICAgICAgICAgICAgbmFtZTogc3RvcmUuZ2V0U3RhdGUoKS5wZWFjaFBheUN1c3RvbWVyLm5hbWVfZmlyc3QgKyAnICcgKyBzdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5Q3VzdG9tZXIubmFtZV9sYXN0LFxuICAgICAgICAgICAgZW1haWw6IHN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlDdXN0b21lci5lbWFpbCxcbiAgICAgICAgICAgIHBob25lOiBzdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5Q3VzdG9tZXIucGhvbmVcbiAgICAgICAgfSlcbiAgICAsXG4gICAgc2hvcnRBZGRyZXNzOiAoKT0+KHtcbiAgICAgICAgICAgIGNvdW50cnk6IFBlYWNoUGF5Q3VzdG9tZXIuY291bnRyeSgpLFxuICAgICAgICAgICAgc3RhdGU6IFBlYWNoUGF5Q3VzdG9tZXIuc3RhdGUoKSxcbiAgICAgICAgICAgIGNpdHk6IFBlYWNoUGF5Q3VzdG9tZXIuY2l0eSgpLFxuICAgICAgICAgICAgcG9zdGNvZGU6IFBlYWNoUGF5Q3VzdG9tZXIucG9zdGFsKClcbiAgICAgICAgfSlcbiAgICAsXG4gICAgc2hpcHBpbmdBZGRyZXNzOiAoKT0+KHtcbiAgICAgICAgICAgIHNoaXBwaW5nX2ZpcnN0X25hbWU6IFBlYWNoUGF5Q3VzdG9tZXIuZmlyc3ROYW1lKCksXG4gICAgICAgICAgICBzaGlwcGluZ19sYXN0X25hbWU6IFBlYWNoUGF5Q3VzdG9tZXIubGFzdE5hbWUoKSxcbiAgICAgICAgICAgIHNoaXBwaW5nX2NvbXBhbnk6ICcnLFxuICAgICAgICAgICAgc2hpcHBpbmdfY291bnRyeTogUGVhY2hQYXlDdXN0b21lci5jb3VudHJ5KCksXG4gICAgICAgICAgICBzaGlwcGluZ19hZGRyZXNzXzE6IFBlYWNoUGF5Q3VzdG9tZXIuYWRkcmVzczEoKSxcbiAgICAgICAgICAgIHNoaXBwaW5nX2FkZHJlc3NfMjogUGVhY2hQYXlDdXN0b21lci5hZGRyZXNzMigpLFxuICAgICAgICAgICAgc2hpcHBpbmdfY2l0eTogUGVhY2hQYXlDdXN0b21lci5jaXR5KCksXG4gICAgICAgICAgICBzaGlwcGluZ19zdGF0ZTogUGVhY2hQYXlDdXN0b21lci5zdGF0ZSgpLFxuICAgICAgICAgICAgc2hpcHBpbmdfcG9zdGNvZGU6IFBlYWNoUGF5Q3VzdG9tZXIucG9zdGFsKClcbiAgICAgICAgfSlcbiAgICAsXG4gICAgYmlsbGluZ0FkZHJlc3M6ICgpPT4oe1xuICAgICAgICAgICAgYmlsbGluZ19maXJzdF9uYW1lOiBQZWFjaFBheUN1c3RvbWVyLmZpcnN0TmFtZSgpLFxuICAgICAgICAgICAgYmlsbGluZ19sYXN0X25hbWU6IFBlYWNoUGF5Q3VzdG9tZXIubGFzdE5hbWUoKSxcbiAgICAgICAgICAgIGJpbGxpbmdfY29tcGFueTogJycsXG4gICAgICAgICAgICBiaWxsaW5nX2VtYWlsOiBQZWFjaFBheUN1c3RvbWVyLmVtYWlsKCksXG4gICAgICAgICAgICBiaWxsaW5nX3Bob25lOiBQZWFjaFBheUN1c3RvbWVyLnBob25lKCksXG4gICAgICAgICAgICBiaWxsaW5nX2NvdW50cnk6IFBlYWNoUGF5Q3VzdG9tZXIuY291bnRyeSgpLFxuICAgICAgICAgICAgYmlsbGluZ19hZGRyZXNzXzE6IFBlYWNoUGF5Q3VzdG9tZXIuYWRkcmVzczEoKSxcbiAgICAgICAgICAgIGJpbGxpbmdfYWRkcmVzc18yOiBQZWFjaFBheUN1c3RvbWVyLmFkZHJlc3MyKCksXG4gICAgICAgICAgICBiaWxsaW5nX2NpdHk6IFBlYWNoUGF5Q3VzdG9tZXIuY2l0eSgpLFxuICAgICAgICAgICAgYmlsbGluZ19zdGF0ZTogUGVhY2hQYXlDdXN0b21lci5zdGF0ZSgpLFxuICAgICAgICAgICAgYmlsbGluZ19wb3N0Y29kZTogUGVhY2hQYXlDdXN0b21lci5wb3N0YWwoKVxuICAgICAgICB9KVxufTtcbmZ1bmN0aW9uIHVwZGF0ZUN1c3RvbWVyTWVyY2hhbnRBY2NvdW50KG1lcmNoYW50Q3VzdG9tZXIpIHtcbiAgICByZXR1cm4ge1xuICAgICAgICB0eXBlOiBEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfQ1VTVE9NRVIsXG4gICAgICAgIHBheWxvYWQ6IG1lcmNoYW50Q3VzdG9tZXJcbiAgICB9O1xufVxuZnVuY3Rpb24gdXBkYXRlQ3VzdG9tZXJNZXJjaGFudEFjY291bnRFeGlzdGVuY2UoZXhpc3QpIHtcbiAgICByZXR1cm4ge1xuICAgICAgICB0eXBlOiBEaXNwYXRjaEFjdGlvblR5cGUuTUVSQ0hBTlRfQ1VTVE9NRVJfRVhJU1QsXG4gICAgICAgIHBheWxvYWQ6IGV4aXN0XG4gICAgfTtcbn1cbmNvbnN0IE1lcmNoYW50Q3VzdG9tZXIgPSB7XG4gICAgbG9nZ2VkSW46ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLm1lcmNoYW50Q3VzdG9tZXIubG9nZ2VkSW5cbiAgICAsXG4gICAgdXNlcm5hbWVFeGlzdDogKCk9PnN0b3JlLmdldFN0YXRlKCkubWVyY2hhbnRDdXN0b21lci51c2VybmFtZUlzUmVnaXN0ZXJlZFxufTtcbmNvbnN0IHVwZGF0ZVNlc3Npb25JZCA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5PUkRFUl9TRVNTSU9OSUQpO1xuY29uc3QgdXBkYXRlQ3VzdG9tZXJBZGRyZXNzVmFsaWRhdGlvbiA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5PUkRFUl9BRERSRVNTX1ZBTElEQVRFRCk7XG5jb25zdCBzZXRFeHRyYUZpZWxkcyA9IGNyZWF0ZURpc3BhdGNoVXBkYXRlKERpc3BhdGNoQWN0aW9uVHlwZS5PUkRFUl9TRVRfRVhUUkFfRklFTERTKTtcbmNvbnN0IHNldE9yZGVyRXJyb3IgPSBjcmVhdGVEaXNwYXRjaFVwZGF0ZShEaXNwYXRjaEFjdGlvblR5cGUuT1JERVJfU0VUX0VSUk9SX01FU1NBR0UpO1xuY29uc3QgUGVhY2hQYXlPcmRlciA9IHtcbiAgICBzZXNzaW9uSWQ6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5T3JkZXIuc2Vzc2lvbklkXG4gICAgLFxuICAgIGNvbnRlbnRzOiAoKT0+c3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbMF0uY2FydFxuICAgICxcbiAgICBlcnJvck1lc3NhZ2U6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5T3JkZXIuZXJyb3JNZXNzYWdlXG4gICAgLFxuICAgIGNvbGxlY3RTZWxlY3RlZFNoaXBwaW5nOiAoKT0+e1xuICAgICAgICBjb25zdCBjYXJ0cyA9IHN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzO1xuICAgICAgICBjb25zdCBzZWxlY3RlZFNoaXBwaW5nTWV0aG9kc1JlY29yZCA9IHt9O1xuICAgICAgICBmb3IgKGNvbnN0IGNhcnRLZXkgb2YgT2JqZWN0LmtleXMoY2FydHMpKXtcbiAgICAgICAgICAgIGNvbnN0IGNhcnQgPSBjYXJ0c1tjYXJ0S2V5XTtcbiAgICAgICAgICAgIGlmICghY2FydCkge1xuICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZm9yIChjb25zdCBwYWNrYWdlS2V5IG9mIE9iamVjdC5rZXlzKGNhcnQucGFja2FnZV9yZWNvcmQgPz8ge30pKXtcbiAgICAgICAgICAgICAgICBjb25zdCBwYWNrYWdlUmVjb3JkID0gY2FydC5wYWNrYWdlX3JlY29yZFtwYWNrYWdlS2V5XTtcbiAgICAgICAgICAgICAgICBpZiAoIXBhY2thZ2VSZWNvcmQpIHtcbiAgICAgICAgICAgICAgICAgICAgY29udGludWU7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGNvbnN0IHNoaXBwaW5nS2V5ID0gY2FydEtleSA9PT0gJzAnID8gcGFja2FnZUtleSA6IGAke2NhcnRLZXl9XyR7cGFja2FnZUtleX1gO1xuICAgICAgICAgICAgICAgIHNlbGVjdGVkU2hpcHBpbmdNZXRob2RzUmVjb3JkW3NoaXBwaW5nS2V5XSA9IHBhY2thZ2VSZWNvcmQuc2VsZWN0ZWRfbWV0aG9kO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzZWxlY3RlZFNoaXBwaW5nTWV0aG9kc1JlY29yZDtcbiAgICB9LFxuICAgIGN1c3RvbWVyQWRkcmVzc1ZhbGlkYXRlZDogKCk9PnN0b3JlLmdldFN0YXRlKCkucGVhY2hQYXlPcmRlci5jdXN0b21lckFkZHJlc3NWYWxpZGF0ZWRcbiAgICAsXG4gICAgZXh0cmFGaWVsZHNSZWNvcmQ6ICgpPT5zdG9yZS5nZXRTdGF0ZSgpLnBlYWNoUGF5T3JkZXIuYWRkaXRpb25hbEZpZWxkc1xufTtcbmZ1bmN0aW9uIGZvcm1hdEN1cnJlbmN5U3RyaW5nKGNvc3QpIHtcbiAgICBjb25zdCB7IHN5bWJvbCAsIHBvc2l0aW9uICB9ID0gTWVyY2hhbnRDb25maWd1cmF0aW9uLmN1cnJlbmN5LmNvbmZpZ3VyYXRpb24oKTtcbiAgICBpZiAodHlwZW9mIGNvc3QgIT09ICdudW1iZXInKSB7XG4gICAgICAgIGNvc3QgPSAwO1xuICAgIH1cbiAgICBsZXQgZm9ybWF0dGVkQ3VycmVuY3kgPSAnJztcbiAgICBpZiAocG9zaXRpb24gPT09ICdsZWZ0JyB8fCBwb3NpdGlvbiA9PT0gJ2xlZnRfc3BhY2UnKSB7XG4gICAgICAgIGxldCBuZWdTeW1ib2wgPSAnJztcbiAgICAgICAgbGV0IGZvcm1hdHRlZENvc3QgPSBmb3JtYXRDb3N0U3RyaW5nKGNvc3QpO1xuICAgICAgICBpZiAoY29zdCA8IDApIHtcbiAgICAgICAgICAgIG5lZ1N5bWJvbCA9ICfiiJInO1xuICAgICAgICAgICAgZm9ybWF0dGVkQ29zdCA9IGZvcm1hdENvc3RTdHJpbmcoTWF0aC5hYnMoY29zdCkpO1xuICAgICAgICB9XG4gICAgICAgIGZvcm1hdHRlZEN1cnJlbmN5ID0gYCR7bmVnU3ltYm9sfSR7c3ltYm9sfSR7cG9zaXRpb24gPT09ICdsZWZ0X3NwYWNlJyA/ICcgJyA6ICcnfSR7Zm9ybWF0dGVkQ29zdH1gO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIGZvcm1hdHRlZEN1cnJlbmN5ID0gYCR7Zm9ybWF0Q29zdFN0cmluZyhjb3N0KX0ke3Bvc2l0aW9uID09PSAncmlnaHRfc3BhY2UnID8gJyAnIDogJyd9JHtzeW1ib2x9YDtcbiAgICB9XG4gICAgcmV0dXJuIGZvcm1hdHRlZEN1cnJlbmN5O1xufVxuZnVuY3Rpb24gZm9ybWF0Q29zdFN0cmluZyhjb3N0KSB7XG4gICAgY29uc3QgeyBjb2RlICwgdGhvdXNhbmRzU2VwYXJhdG9yICwgZGVjaW1hbFNlcGFyYXRvciAsIHJvdW5kaW5nICwgZGVjaW1hbHMgIH0gPSBNZXJjaGFudENvbmZpZ3VyYXRpb24uY3VycmVuY3kuY29uZmlndXJhdGlvbigpO1xuICAgIGlmICh0eXBlb2YgY29zdCAhPT0gJ251bWJlcicpIHtcbiAgICAgICAgY29zdCA9IDA7XG4gICAgfVxuICAgIGlmIChjb2RlID09PSAnSlBZJykge1xuICAgICAgICByZXR1cm4gY29zdC50b1N0cmluZygpO1xuICAgIH1cbiAgICBjb25zdCBudW1iZXJPZkRlY2ltYWxzID0gZGVjaW1hbHMgfHwgMjtcbiAgICBzd2l0Y2gocm91bmRpbmcpe1xuICAgICAgICBjYXNlICd1cCc6XG4gICAgICAgICAgICBjb3N0ID0gTWF0aC5jZWlsKGNvc3QpO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIGNhc2UgJ2Rvd24nOlxuICAgICAgICAgICAgY29zdCA9IE1hdGguZmxvb3IoY29zdCk7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSAnbmVhcmVzdCc6XG4gICAgICAgICAgICBjb3N0ID0gTWF0aC5yb3VuZChjb3N0KTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgYnJlYWs7XG4gICAgfVxuICAgIGNvc3QgPSBOdW1iZXIucGFyc2VGbG9hdChjb3N0LnRvRml4ZWQoZGVjaW1hbHMpKTtcbiAgICBsZXQgZm9ybWF0dGVkUHJpY2UgPSAnJztcbiAgICB0cnkge1xuICAgICAgICBjb25zdCBjdXJyZW5jeVNwbGl0ID0gY29zdC50b0ZpeGVkKG51bWJlck9mRGVjaW1hbHMpLnNwbGl0KCcuJyk7XG4gICAgICAgIGxldCBkb2xsYXJBbW91bnQgPSBjdXJyZW5jeVNwbGl0WzBdO1xuICAgICAgICBjb25zdCBjZW50c0Ftb3VudCA9IGN1cnJlbmN5U3BsaXRbMV0gfHwgJyc7XG4gICAgICAgIGNvbnN0IHJldiA9IGN1cnJlbmN5U3BsaXRbMF0uc3BsaXQoJycpLnJldmVyc2UoKS5qb2luKCcnKTtcbiAgICAgICAgY29uc3QgcmV2Rm9ybWF0ID0gcmV2Lm1hdGNoKC8uezEsM30vZyk/LmpvaW4odGhvdXNhbmRzU2VwYXJhdG9yKSA/PyAnJztcbiAgICAgICAgZG9sbGFyQW1vdW50ID0gcmV2Rm9ybWF0LnNwbGl0KCcnKS5yZXZlcnNlKCkuam9pbignJyk7XG4gICAgICAgIGZvcm1hdHRlZFByaWNlICs9IGRvbGxhckFtb3VudDtcbiAgICAgICAgaWYgKGNlbnRzQW1vdW50ICE9PSAnJykge1xuICAgICAgICAgICAgZm9ybWF0dGVkUHJpY2UgKz0gZGVjaW1hbFNlcGFyYXRvciArIGNlbnRzQW1vdW50O1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBmb3JtYXR0ZWRQcmljZTtcbiAgICB9IGNhdGNoICB7XG4gICAgICAgIHJldHVybiBjb3N0LnRvRml4ZWQoZGVjaW1hbHMpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGNsZWFySW5wdXQoc2VsZWN0b3IpIHtcbiAgICBmb3IgKGNvbnN0ICRlbGVtZW50IG9mICRxc0FsbChzZWxlY3Rvcikpe1xuICAgICAgICAkZWxlbWVudC52YWx1ZSA9ICcnO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckRyb3BEb3duTGlzdChkYXRhLCBkZWZhdWx0T3B0aW9uID0gJycpIHtcbiAgICBpZiAoIWRhdGEpIHtcbiAgICAgICAgZGF0YSA9IHt9O1xuICAgIH1cbiAgICBjb25zdCBsaXN0ID0gT2JqZWN0LmVudHJpZXMoZGF0YSkubWFwKChba2V5LCB2YWx1ZV0pPT5gPG9wdGlvbiB2YWx1ZT1cIiR7a2V5fVwiPiAke3ZhbHVlfSA8L29wdGlvbj5gXG4gICAgKTtcbiAgICBpZiAoZGVmYXVsdE9wdGlvbikge1xuICAgICAgICByZXR1cm4gYDxvcHRpb24gaGlkZGVuIGRpc2FibGVkIHNlbGVjdGVkIHZhbHVlPVwiXCI+JHtkZWZhdWx0T3B0aW9ufTwvb3B0aW9uPiR7bGlzdC5qb2luKCcnKX1gO1xuICAgIH1cbiAgICByZXR1cm4gbGlzdC5qb2luKCcnKTtcbn1cbmZ1bmN0aW9uIHNlbGVjdERyb3Bkb3duKCRzZWxlY3QsIHZhbHVlKSB7XG4gICAgaWYgKCEkc2VsZWN0KSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgJHNlbGVjdC52YWx1ZSA9IHZhbHVlO1xufVxuZnVuY3Rpb24gZm9ybUVudHJ5KGZvcm1EYXRhLCBrZXkpIHtcbiAgICBpZiAoZm9ybURhdGEuZ2V0KGtleSkgPT09IG51bGwpIHtcbiAgICAgICAgcmV0dXJuICcnO1xuICAgIH1cbiAgICByZXR1cm4gZm9ybURhdGEuZ2V0KGtleSkgPz8gJyc7XG59XG5mdW5jdGlvbiBnZXRDb3VudHJ5TmFtZShjb3VudHJ5Q29kZSkge1xuICAgIGlmICghcGVhY2hwYXlDb3VudHJpZXNbY291bnRyeUNvZGVdKSB7XG4gICAgICAgIHJldHVybiAnVW5rbm93biBDb3VudHJ5IENvZGU6ICcgKyBjb3VudHJ5Q29kZTtcbiAgICB9XG4gICAgcmV0dXJuIHBlYWNocGF5Q291bnRyaWVzPy5bY291bnRyeUNvZGVdPy5uYW1lID8/ICdVbmtub3duIENvdW50cnkgQ29kZTogJyArIGNvdW50cnlDb2RlO1xufVxuZnVuY3Rpb24gc3RhdGVQcm92aW5jZU9yQ291bnR5KGNvdW50cnlDb2RlKSB7XG4gICAgc3dpdGNoKGNvdW50cnlDb2RlKXtcbiAgICAgICAgY2FzZSAnVVMnOlxuICAgICAgICBjYXNlICdNWSc6XG4gICAgICAgIGNhc2UgJ0FVJzpcbiAgICAgICAgICAgIHJldHVybiBnZXRMb2NhbGVUZXh0KCdzdGF0ZS1zZWxlY3QnKTtcbiAgICAgICAgY2FzZSAnR0InOlxuICAgICAgICAgICAgcmV0dXJuIGdldExvY2FsZVRleHQoJ2NvdW50eScpO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgcmV0dXJuIGdldExvY2FsZVRleHQoJ3Byb3ZpbmNlLXNlbGVjdCcpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGlzRVVDb3VudHJ5KGNvdW50cnlDb2RlKSB7XG4gICAgY29uc3QgRVVDb3VudHJpZXMgPSBbXG4gICAgICAgICdBVCcsXG4gICAgICAgICdCRScsXG4gICAgICAgICdCRycsXG4gICAgICAgICdDWScsXG4gICAgICAgICdDWicsXG4gICAgICAgICdESycsXG4gICAgICAgICdFRScsXG4gICAgICAgICdGSScsXG4gICAgICAgICdGUicsXG4gICAgICAgICdERScsXG4gICAgICAgICdHUicsXG4gICAgICAgICdIVScsXG4gICAgICAgICdJRScsXG4gICAgICAgICdJVCcsXG4gICAgICAgICdMVicsXG4gICAgICAgICdMVCcsXG4gICAgICAgICdMVScsXG4gICAgICAgICdNVCcsXG4gICAgICAgICdOTCcsXG4gICAgICAgICdQTCcsXG4gICAgICAgICdQVCcsXG4gICAgICAgICdSTycsXG4gICAgICAgICdTSycsXG4gICAgICAgICdTSScsXG4gICAgICAgICdFUycsXG4gICAgICAgICdTRSdcbiAgICBdO1xuICAgIGlmIChFVUNvdW50cmllcy5pbmNsdWRlcyhjb3VudHJ5Q29kZSkpIHtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfVxuICAgIHJldHVybiBmYWxzZTtcbn1cbmNvbnN0IHBlYWNocGF5Q291bnRyaWVzID0ge1xuICAgIEFGOiB7XG4gICAgICAgIG5hbWU6ICdBZmdoYW5pc3RhbidcbiAgICB9LFxuICAgIEFYOiB7XG4gICAgICAgIG5hbWU6ICfDhWxhbmQgSXNsYW5kcydcbiAgICB9LFxuICAgIEFMOiB7XG4gICAgICAgIG5hbWU6ICdBbGJhbmlhJ1xuICAgIH0sXG4gICAgRFo6IHtcbiAgICAgICAgbmFtZTogJ0FsZ2VyaWEnXG4gICAgfSxcbiAgICBBUzoge1xuICAgICAgICBuYW1lOiAnQW1lcmljYW4gU2Ftb2EnXG4gICAgfSxcbiAgICBBRDoge1xuICAgICAgICBuYW1lOiAnQW5kb3JyYSdcbiAgICB9LFxuICAgIEFPOiB7XG4gICAgICAgIG5hbWU6ICdBbmdvbGEnXG4gICAgfSxcbiAgICBBSToge1xuICAgICAgICBuYW1lOiAnQW5ndWlsbGEnXG4gICAgfSxcbiAgICBBUToge1xuICAgICAgICBuYW1lOiAnQW50YXJjdGljYSdcbiAgICB9LFxuICAgIEFHOiB7XG4gICAgICAgIG5hbWU6ICdBbnRpZ3VhIGFuZCBCYXJidWRhJ1xuICAgIH0sXG4gICAgQVI6IHtcbiAgICAgICAgbmFtZTogJ0FyZ2VudGluYSdcbiAgICB9LFxuICAgIEFNOiB7XG4gICAgICAgIG5hbWU6ICdBcm1lbmlhJ1xuICAgIH0sXG4gICAgQVc6IHtcbiAgICAgICAgbmFtZTogJ0FydWJhJ1xuICAgIH0sXG4gICAgQVU6IHtcbiAgICAgICAgbmFtZTogJ0F1c3RyYWxpYSdcbiAgICB9LFxuICAgIEFUOiB7XG4gICAgICAgIG5hbWU6ICdBdXN0cmlhJ1xuICAgIH0sXG4gICAgQVo6IHtcbiAgICAgICAgbmFtZTogJ0F6ZXJiYWlqYW4nXG4gICAgfSxcbiAgICBCUzoge1xuICAgICAgICBuYW1lOiAnQmFoYW1hcydcbiAgICB9LFxuICAgIEJIOiB7XG4gICAgICAgIG5hbWU6ICdCYWhyYWluJ1xuICAgIH0sXG4gICAgQkQ6IHtcbiAgICAgICAgbmFtZTogJ0JhbmdsYWRlc2gnXG4gICAgfSxcbiAgICBCQjoge1xuICAgICAgICBuYW1lOiAnQmFyYmFkb3MnXG4gICAgfSxcbiAgICBCWToge1xuICAgICAgICBuYW1lOiAnQmVsYXJ1cydcbiAgICB9LFxuICAgIEJFOiB7XG4gICAgICAgIG5hbWU6ICdCZWxnaXVtJ1xuICAgIH0sXG4gICAgQlo6IHtcbiAgICAgICAgbmFtZTogJ0JlbGl6ZSdcbiAgICB9LFxuICAgIEJKOiB7XG4gICAgICAgIG5hbWU6ICdCZW5pbidcbiAgICB9LFxuICAgIEJNOiB7XG4gICAgICAgIG5hbWU6ICdCZXJtdWRhJ1xuICAgIH0sXG4gICAgQlQ6IHtcbiAgICAgICAgbmFtZTogJ0JodXRhbidcbiAgICB9LFxuICAgIEJPOiB7XG4gICAgICAgIG5hbWU6ICdCb2xpdmlhLCBQbHVyaW5hdGlvbmFsIFN0YXRlIG9mJ1xuICAgIH0sXG4gICAgQlE6IHtcbiAgICAgICAgbmFtZTogJ0JvbmFpcmUsIFNpbnQgRXVzdGF0aXVzIGFuZCBTYWJhJ1xuICAgIH0sXG4gICAgQkE6IHtcbiAgICAgICAgbmFtZTogJ0Jvc25pYSBhbmQgSGVyemVnb3ZpbmEnXG4gICAgfSxcbiAgICBCVzoge1xuICAgICAgICBuYW1lOiAnQm90c3dhbmEnXG4gICAgfSxcbiAgICBCVjoge1xuICAgICAgICBuYW1lOiAnQm91dmV0IElzbGFuZCdcbiAgICB9LFxuICAgIEJSOiB7XG4gICAgICAgIG5hbWU6ICdCcmF6aWwnXG4gICAgfSxcbiAgICBJTzoge1xuICAgICAgICBuYW1lOiAnQnJpdGlzaCBJbmRpYW4gT2NlYW4gVGVycml0b3J5J1xuICAgIH0sXG4gICAgQk46IHtcbiAgICAgICAgbmFtZTogJ0JydW5laSBEYXJ1c3NhbGFtJ1xuICAgIH0sXG4gICAgQkc6IHtcbiAgICAgICAgbmFtZTogJ0J1bGdhcmlhJ1xuICAgIH0sXG4gICAgQkY6IHtcbiAgICAgICAgbmFtZTogJ0J1cmtpbmEgRmFzbydcbiAgICB9LFxuICAgIEJJOiB7XG4gICAgICAgIG5hbWU6ICdCdXJ1bmRpJ1xuICAgIH0sXG4gICAgS0g6IHtcbiAgICAgICAgbmFtZTogJ0NhbWJvZGlhJ1xuICAgIH0sXG4gICAgQ006IHtcbiAgICAgICAgbmFtZTogJ0NhbWVyb29uJ1xuICAgIH0sXG4gICAgQ0E6IHtcbiAgICAgICAgbmFtZTogJ0NhbmFkYSdcbiAgICB9LFxuICAgIENWOiB7XG4gICAgICAgIG5hbWU6ICdDYXBlIFZlcmRlJ1xuICAgIH0sXG4gICAgS1k6IHtcbiAgICAgICAgbmFtZTogJ0NheW1hbiBJc2xhbmRzJ1xuICAgIH0sXG4gICAgQ0Y6IHtcbiAgICAgICAgbmFtZTogJ0NlbnRyYWwgQWZyaWNhbiBSZXB1YmxpYydcbiAgICB9LFxuICAgIFREOiB7XG4gICAgICAgIG5hbWU6ICdDaGFkJ1xuICAgIH0sXG4gICAgQ0w6IHtcbiAgICAgICAgbmFtZTogJ0NoaWxlJ1xuICAgIH0sXG4gICAgQ046IHtcbiAgICAgICAgbmFtZTogJ0NoaW5hJ1xuICAgIH0sXG4gICAgQ1g6IHtcbiAgICAgICAgbmFtZTogJ0NocmlzdG1hcyBJc2xhbmQnXG4gICAgfSxcbiAgICBDQzoge1xuICAgICAgICBuYW1lOiAnQ29jb3MgKEtlZWxpbmcpIElzbGFuZHMnXG4gICAgfSxcbiAgICBDTzoge1xuICAgICAgICBuYW1lOiAnQ29sb21iaWEnXG4gICAgfSxcbiAgICBLTToge1xuICAgICAgICBuYW1lOiAnQ29tb3JvcydcbiAgICB9LFxuICAgIENHOiB7XG4gICAgICAgIG5hbWU6ICdDb25nbydcbiAgICB9LFxuICAgIENEOiB7XG4gICAgICAgIG5hbWU6ICdDb25nbywgdGhlIERlbW9jcmF0aWMgUmVwdWJsaWMgb2YgdGhlJ1xuICAgIH0sXG4gICAgQ0s6IHtcbiAgICAgICAgbmFtZTogJ0Nvb2sgSXNsYW5kcydcbiAgICB9LFxuICAgIENSOiB7XG4gICAgICAgIG5hbWU6ICdDb3N0YSBSaWNhJ1xuICAgIH0sXG4gICAgQ0k6IHtcbiAgICAgICAgbmFtZTogJ0PDtHRlIGRcXCdJdm9pcmUnXG4gICAgfSxcbiAgICBIUjoge1xuICAgICAgICBuYW1lOiAnQ3JvYXRpYSdcbiAgICB9LFxuICAgIENVOiB7XG4gICAgICAgIG5hbWU6ICdDdWJhJ1xuICAgIH0sXG4gICAgQ1c6IHtcbiAgICAgICAgbmFtZTogJ0N1cmHDp2FvJ1xuICAgIH0sXG4gICAgQ1k6IHtcbiAgICAgICAgbmFtZTogJ0N5cHJ1cydcbiAgICB9LFxuICAgIENaOiB7XG4gICAgICAgIG5hbWU6ICdDemVjaCBSZXB1YmxpYydcbiAgICB9LFxuICAgIERLOiB7XG4gICAgICAgIG5hbWU6ICdEZW5tYXJrJ1xuICAgIH0sXG4gICAgREo6IHtcbiAgICAgICAgbmFtZTogJ0RqaWJvdXRpJ1xuICAgIH0sXG4gICAgRE06IHtcbiAgICAgICAgbmFtZTogJ0RvbWluaWNhJ1xuICAgIH0sXG4gICAgRE86IHtcbiAgICAgICAgbmFtZTogJ0RvbWluaWNhbiBSZXB1YmxpYydcbiAgICB9LFxuICAgIEVDOiB7XG4gICAgICAgIG5hbWU6ICdFY3VhZG9yJ1xuICAgIH0sXG4gICAgRUc6IHtcbiAgICAgICAgbmFtZTogJ0VneXB0J1xuICAgIH0sXG4gICAgU1Y6IHtcbiAgICAgICAgbmFtZTogJ0VsIFNhbHZhZG9yJ1xuICAgIH0sXG4gICAgR1E6IHtcbiAgICAgICAgbmFtZTogJ0VxdWF0b3JpYWwgR3VpbmVhJ1xuICAgIH0sXG4gICAgRVI6IHtcbiAgICAgICAgbmFtZTogJ0VyaXRyZWEnXG4gICAgfSxcbiAgICBFRToge1xuICAgICAgICBuYW1lOiAnRXN0b25pYSdcbiAgICB9LFxuICAgIEVUOiB7XG4gICAgICAgIG5hbWU6ICdFdGhpb3BpYSdcbiAgICB9LFxuICAgIEZLOiB7XG4gICAgICAgIG5hbWU6ICdGYWxrbGFuZCBJc2xhbmRzIChNYWx2aW5hcyknXG4gICAgfSxcbiAgICBGTzoge1xuICAgICAgICBuYW1lOiAnRmFyb2UgSXNsYW5kcydcbiAgICB9LFxuICAgIEZKOiB7XG4gICAgICAgIG5hbWU6ICdGaWppJ1xuICAgIH0sXG4gICAgRkk6IHtcbiAgICAgICAgbmFtZTogJ0ZpbmxhbmQnXG4gICAgfSxcbiAgICBGUjoge1xuICAgICAgICBuYW1lOiAnRnJhbmNlJ1xuICAgIH0sXG4gICAgR0Y6IHtcbiAgICAgICAgbmFtZTogJ0ZyZW5jaCBHdWlhbmEnXG4gICAgfSxcbiAgICBQRjoge1xuICAgICAgICBuYW1lOiAnRnJlbmNoIFBvbHluZXNpYSdcbiAgICB9LFxuICAgIFRGOiB7XG4gICAgICAgIG5hbWU6ICdGcmVuY2ggU291dGhlcm4gVGVycml0b3JpZXMnXG4gICAgfSxcbiAgICBHQToge1xuICAgICAgICBuYW1lOiAnR2Fib24nXG4gICAgfSxcbiAgICBHTToge1xuICAgICAgICBuYW1lOiAnR2FtYmlhJ1xuICAgIH0sXG4gICAgR0U6IHtcbiAgICAgICAgbmFtZTogJ0dlb3JnaWEnXG4gICAgfSxcbiAgICBERToge1xuICAgICAgICBuYW1lOiAnR2VybWFueSdcbiAgICB9LFxuICAgIEdIOiB7XG4gICAgICAgIG5hbWU6ICdHaGFuYSdcbiAgICB9LFxuICAgIEdJOiB7XG4gICAgICAgIG5hbWU6ICdHaWJyYWx0YXInXG4gICAgfSxcbiAgICBHUjoge1xuICAgICAgICBuYW1lOiAnR3JlZWNlJ1xuICAgIH0sXG4gICAgR0w6IHtcbiAgICAgICAgbmFtZTogJ0dyZWVubGFuZCdcbiAgICB9LFxuICAgIEdEOiB7XG4gICAgICAgIG5hbWU6ICdHcmVuYWRhJ1xuICAgIH0sXG4gICAgR1A6IHtcbiAgICAgICAgbmFtZTogJ0d1YWRlbG91cGUnXG4gICAgfSxcbiAgICBHVToge1xuICAgICAgICBuYW1lOiAnR3VhbSdcbiAgICB9LFxuICAgIEdUOiB7XG4gICAgICAgIG5hbWU6ICdHdWF0ZW1hbGEnXG4gICAgfSxcbiAgICBHRzoge1xuICAgICAgICBuYW1lOiAnR3Vlcm5zZXknXG4gICAgfSxcbiAgICBHTjoge1xuICAgICAgICBuYW1lOiAnR3VpbmVhJ1xuICAgIH0sXG4gICAgR1c6IHtcbiAgICAgICAgbmFtZTogJ0d1aW5lYS1CaXNzYXUnXG4gICAgfSxcbiAgICBHWToge1xuICAgICAgICBuYW1lOiAnR3V5YW5hJ1xuICAgIH0sXG4gICAgSFQ6IHtcbiAgICAgICAgbmFtZTogJ0hhaXRpJ1xuICAgIH0sXG4gICAgSE06IHtcbiAgICAgICAgbmFtZTogJ0hlYXJkIElzbGFuZCBhbmQgTWNEb25hbGQgSXNsYW5kcydcbiAgICB9LFxuICAgIFZBOiB7XG4gICAgICAgIG5hbWU6ICdIb2x5IFNlZSAoVmF0aWNhbiBDaXR5IFN0YXRlKSdcbiAgICB9LFxuICAgIEhOOiB7XG4gICAgICAgIG5hbWU6ICdIb25kdXJhcydcbiAgICB9LFxuICAgIEhLOiB7XG4gICAgICAgIG5hbWU6ICdIb25nIEtvbmcnXG4gICAgfSxcbiAgICBIVToge1xuICAgICAgICBuYW1lOiAnSHVuZ2FyeSdcbiAgICB9LFxuICAgIElTOiB7XG4gICAgICAgIG5hbWU6ICdJY2VsYW5kJ1xuICAgIH0sXG4gICAgSU46IHtcbiAgICAgICAgbmFtZTogJ0luZGlhJ1xuICAgIH0sXG4gICAgSUQ6IHtcbiAgICAgICAgbmFtZTogJ0luZG9uZXNpYSdcbiAgICB9LFxuICAgIElSOiB7XG4gICAgICAgIG5hbWU6ICdJcmFuLCBJc2xhbWljIFJlcHVibGljIG9mJ1xuICAgIH0sXG4gICAgSVE6IHtcbiAgICAgICAgbmFtZTogJ0lyYXEnXG4gICAgfSxcbiAgICBJRToge1xuICAgICAgICBuYW1lOiAnSXJlbGFuZCdcbiAgICB9LFxuICAgIElNOiB7XG4gICAgICAgIG5hbWU6ICdJc2xlIG9mIE1hbidcbiAgICB9LFxuICAgIElMOiB7XG4gICAgICAgIG5hbWU6ICdJc3JhZWwnXG4gICAgfSxcbiAgICBJVDoge1xuICAgICAgICBuYW1lOiAnSXRhbHknXG4gICAgfSxcbiAgICBKTToge1xuICAgICAgICBuYW1lOiAnSmFtYWljYSdcbiAgICB9LFxuICAgIEpQOiB7XG4gICAgICAgIG5hbWU6ICdKYXBhbidcbiAgICB9LFxuICAgIEpFOiB7XG4gICAgICAgIG5hbWU6ICdKZXJzZXknXG4gICAgfSxcbiAgICBKTzoge1xuICAgICAgICBuYW1lOiAnSm9yZGFuJ1xuICAgIH0sXG4gICAgS1o6IHtcbiAgICAgICAgbmFtZTogJ0themFraHN0YW4nXG4gICAgfSxcbiAgICBLRToge1xuICAgICAgICBuYW1lOiAnS2VueWEnXG4gICAgfSxcbiAgICBLSToge1xuICAgICAgICBuYW1lOiAnS2lyaWJhdGknXG4gICAgfSxcbiAgICBLUDoge1xuICAgICAgICBuYW1lOiAnS29yZWEgRGVtb2NyYXRpYyBQZW9wbGVcXCdzIFJlcHVibGljIG9mJ1xuICAgIH0sXG4gICAgS1I6IHtcbiAgICAgICAgbmFtZTogJ0tvcmVhIFJlcHVibGljIG9mJ1xuICAgIH0sXG4gICAgS1c6IHtcbiAgICAgICAgbmFtZTogJ0t1d2FpdCdcbiAgICB9LFxuICAgIEtHOiB7XG4gICAgICAgIG5hbWU6ICdLeXJneXpzdGFuJ1xuICAgIH0sXG4gICAgTEE6IHtcbiAgICAgICAgbmFtZTogJ0xhbyBQZW9wbGVcXCdzIERlbW9jcmF0aWMgUmVwdWJsaWMnXG4gICAgfSxcbiAgICBMVjoge1xuICAgICAgICBuYW1lOiAnTGF0dmlhJ1xuICAgIH0sXG4gICAgTEI6IHtcbiAgICAgICAgbmFtZTogJ0xlYmFub24nXG4gICAgfSxcbiAgICBMUzoge1xuICAgICAgICBuYW1lOiAnTGVzb3RobydcbiAgICB9LFxuICAgIExSOiB7XG4gICAgICAgIG5hbWU6ICdMaWJlcmlhJ1xuICAgIH0sXG4gICAgTFk6IHtcbiAgICAgICAgbmFtZTogJ0xpYnlhJ1xuICAgIH0sXG4gICAgTEk6IHtcbiAgICAgICAgbmFtZTogJ0xpZWNodGVuc3RlaW4nXG4gICAgfSxcbiAgICBMVDoge1xuICAgICAgICBuYW1lOiAnTGl0aHVhbmlhJ1xuICAgIH0sXG4gICAgTFU6IHtcbiAgICAgICAgbmFtZTogJ0x1eGVtYm91cmcnXG4gICAgfSxcbiAgICBNTzoge1xuICAgICAgICBuYW1lOiAnTWFjYW8nXG4gICAgfSxcbiAgICBNSzoge1xuICAgICAgICBuYW1lOiAnTWFjZWRvbmlhLCB0aGUgZm9ybWVyIFl1Z29zbGF2IFJlcHVibGljIG9mJ1xuICAgIH0sXG4gICAgTUc6IHtcbiAgICAgICAgbmFtZTogJ01hZGFnYXNjYXInXG4gICAgfSxcbiAgICBNVzoge1xuICAgICAgICBuYW1lOiAnTWFsYXdpJ1xuICAgIH0sXG4gICAgTVk6IHtcbiAgICAgICAgbmFtZTogJ01hbGF5c2lhJ1xuICAgIH0sXG4gICAgTVY6IHtcbiAgICAgICAgbmFtZTogJ01hbGRpdmVzJ1xuICAgIH0sXG4gICAgTUw6IHtcbiAgICAgICAgbmFtZTogJ01hbGknXG4gICAgfSxcbiAgICBNVDoge1xuICAgICAgICBuYW1lOiAnTWFsdGEnXG4gICAgfSxcbiAgICBNSDoge1xuICAgICAgICBuYW1lOiAnTWFyc2hhbGwgSXNsYW5kcydcbiAgICB9LFxuICAgIE1ROiB7XG4gICAgICAgIG5hbWU6ICdNYXJ0aW5pcXVlJ1xuICAgIH0sXG4gICAgTVI6IHtcbiAgICAgICAgbmFtZTogJ01hdXJpdGFuaWEnXG4gICAgfSxcbiAgICBNVToge1xuICAgICAgICBuYW1lOiAnTWF1cml0aXVzJ1xuICAgIH0sXG4gICAgWVQ6IHtcbiAgICAgICAgbmFtZTogJ01heW90dGUnXG4gICAgfSxcbiAgICBNWDoge1xuICAgICAgICBuYW1lOiAnTWV4aWNvJ1xuICAgIH0sXG4gICAgRk06IHtcbiAgICAgICAgbmFtZTogJ01pY3JvbmVzaWEsIEZlZGVyYXRlZCBTdGF0ZXMgb2YnXG4gICAgfSxcbiAgICBNRDoge1xuICAgICAgICBuYW1lOiAnTW9sZG92YSwgUmVwdWJsaWMgb2YnXG4gICAgfSxcbiAgICBNQzoge1xuICAgICAgICBuYW1lOiAnTW9uYWNvJ1xuICAgIH0sXG4gICAgTU46IHtcbiAgICAgICAgbmFtZTogJ01vbmdvbGlhJ1xuICAgIH0sXG4gICAgTUU6IHtcbiAgICAgICAgbmFtZTogJ01vbnRlbmVncm8nXG4gICAgfSxcbiAgICBNUzoge1xuICAgICAgICBuYW1lOiAnTW9udHNlcnJhdCdcbiAgICB9LFxuICAgIE1BOiB7XG4gICAgICAgIG5hbWU6ICdNb3JvY2NvJ1xuICAgIH0sXG4gICAgTVo6IHtcbiAgICAgICAgbmFtZTogJ01vemFtYmlxdWUnXG4gICAgfSxcbiAgICBNTToge1xuICAgICAgICBuYW1lOiAnTXlhbm1hcidcbiAgICB9LFxuICAgIE5BOiB7XG4gICAgICAgIG5hbWU6ICdOYW1pYmlhJ1xuICAgIH0sXG4gICAgTlI6IHtcbiAgICAgICAgbmFtZTogJ05hdXJ1J1xuICAgIH0sXG4gICAgTlA6IHtcbiAgICAgICAgbmFtZTogJ05lcGFsJ1xuICAgIH0sXG4gICAgTkw6IHtcbiAgICAgICAgbmFtZTogJ05ldGhlcmxhbmRzJ1xuICAgIH0sXG4gICAgTkM6IHtcbiAgICAgICAgbmFtZTogJ05ldyBDYWxlZG9uaWEnXG4gICAgfSxcbiAgICBOWjoge1xuICAgICAgICBuYW1lOiAnTmV3IFplYWxhbmQnXG4gICAgfSxcbiAgICBOSToge1xuICAgICAgICBuYW1lOiAnTmljYXJhZ3VhJ1xuICAgIH0sXG4gICAgTkU6IHtcbiAgICAgICAgbmFtZTogJ05pZ2VyJ1xuICAgIH0sXG4gICAgTkc6IHtcbiAgICAgICAgbmFtZTogJ05pZ2VyaWEnXG4gICAgfSxcbiAgICBOVToge1xuICAgICAgICBuYW1lOiAnTml1ZSdcbiAgICB9LFxuICAgIE5GOiB7XG4gICAgICAgIG5hbWU6ICdOb3Jmb2xrIElzbGFuZCdcbiAgICB9LFxuICAgIE1QOiB7XG4gICAgICAgIG5hbWU6ICdOb3J0aGVybiBNYXJpYW5hIElzbGFuZHMnXG4gICAgfSxcbiAgICBOTzoge1xuICAgICAgICBuYW1lOiAnTm9yd2F5J1xuICAgIH0sXG4gICAgT006IHtcbiAgICAgICAgbmFtZTogJ09tYW4nXG4gICAgfSxcbiAgICBQSzoge1xuICAgICAgICBuYW1lOiAnUGFraXN0YW4nXG4gICAgfSxcbiAgICBQVzoge1xuICAgICAgICBuYW1lOiAnUGFsYXUnXG4gICAgfSxcbiAgICBQUzoge1xuICAgICAgICBuYW1lOiAnUGFsZXN0aW5pYW4gVGVycml0b3J5J1xuICAgIH0sXG4gICAgUEE6IHtcbiAgICAgICAgbmFtZTogJ1BhbmFtYSdcbiAgICB9LFxuICAgIFBHOiB7XG4gICAgICAgIG5hbWU6ICdQYXB1YSBOZXcgR3VpbmVhJ1xuICAgIH0sXG4gICAgUFk6IHtcbiAgICAgICAgbmFtZTogJ1BhcmFndWF5J1xuICAgIH0sXG4gICAgUEU6IHtcbiAgICAgICAgbmFtZTogJ1BlcnUnXG4gICAgfSxcbiAgICBQSDoge1xuICAgICAgICBuYW1lOiAnUGhpbGlwcGluZXMnXG4gICAgfSxcbiAgICBQTjoge1xuICAgICAgICBuYW1lOiAnUGl0Y2Fpcm4nXG4gICAgfSxcbiAgICBQTDoge1xuICAgICAgICBuYW1lOiAnUG9sYW5kJ1xuICAgIH0sXG4gICAgUFQ6IHtcbiAgICAgICAgbmFtZTogJ1BvcnR1Z2FsJ1xuICAgIH0sXG4gICAgUFI6IHtcbiAgICAgICAgbmFtZTogJ1B1ZXJ0byBSaWNvJ1xuICAgIH0sXG4gICAgUUE6IHtcbiAgICAgICAgbmFtZTogJ1FhdGFyJ1xuICAgIH0sXG4gICAgUkU6IHtcbiAgICAgICAgbmFtZTogJ1LDqXVuaW9uJ1xuICAgIH0sXG4gICAgUk86IHtcbiAgICAgICAgbmFtZTogJ1JvbWFuaWEnXG4gICAgfSxcbiAgICBSVToge1xuICAgICAgICBuYW1lOiAnUnVzc2lhbiBGZWRlcmF0aW9uJ1xuICAgIH0sXG4gICAgUlc6IHtcbiAgICAgICAgbmFtZTogJ1J3YW5kYSdcbiAgICB9LFxuICAgIEJMOiB7XG4gICAgICAgIG5hbWU6ICdTYWludCBCYXJ0aMOpbGVteSdcbiAgICB9LFxuICAgIFNIOiB7XG4gICAgICAgIG5hbWU6ICdTYWludCBIZWxlbmEsIEFzY2Vuc2lvbiBhbmQgVHJpc3RhbiBkYSBDdW5oYSdcbiAgICB9LFxuICAgIEtOOiB7XG4gICAgICAgIG5hbWU6ICdTYWludCBLaXR0cyBhbmQgTmV2aXMnXG4gICAgfSxcbiAgICBMQzoge1xuICAgICAgICBuYW1lOiAnU2FpbnQgTHVjaWEnXG4gICAgfSxcbiAgICBNRjoge1xuICAgICAgICBuYW1lOiAnU2FpbnQgTWFydGluIChGcmVuY2ggcGFydCknXG4gICAgfSxcbiAgICBQTToge1xuICAgICAgICBuYW1lOiAnU2FpbnQgUGllcnJlIGFuZCBNaXF1ZWxvbidcbiAgICB9LFxuICAgIFZDOiB7XG4gICAgICAgIG5hbWU6ICdTYWludCBWaW5jZW50IGFuZCB0aGUgR3JlbmFkaW5lcydcbiAgICB9LFxuICAgIFdTOiB7XG4gICAgICAgIG5hbWU6ICdTYW1vYSdcbiAgICB9LFxuICAgIFNNOiB7XG4gICAgICAgIG5hbWU6ICdTYW4gTWFyaW5vJ1xuICAgIH0sXG4gICAgU1Q6IHtcbiAgICAgICAgbmFtZTogJ1NhbyBUb21lIGFuZCBQcmluY2lwZSdcbiAgICB9LFxuICAgIFNBOiB7XG4gICAgICAgIG5hbWU6ICdTYXVkaSBBcmFiaWEnXG4gICAgfSxcbiAgICBTTjoge1xuICAgICAgICBuYW1lOiAnU2VuZWdhbCdcbiAgICB9LFxuICAgIFJTOiB7XG4gICAgICAgIG5hbWU6ICdTZXJiaWEnXG4gICAgfSxcbiAgICBTQzoge1xuICAgICAgICBuYW1lOiAnU2V5Y2hlbGxlcydcbiAgICB9LFxuICAgIFNMOiB7XG4gICAgICAgIG5hbWU6ICdTaWVycmEgTGVvbmUnXG4gICAgfSxcbiAgICBTRzoge1xuICAgICAgICBuYW1lOiAnU2luZ2Fwb3JlJ1xuICAgIH0sXG4gICAgU1g6IHtcbiAgICAgICAgbmFtZTogJ1NpbnQgTWFhcnRlbiAoRHV0Y2ggcGFydCknXG4gICAgfSxcbiAgICBTSzoge1xuICAgICAgICBuYW1lOiAnU2xvdmFraWEnXG4gICAgfSxcbiAgICBTSToge1xuICAgICAgICBuYW1lOiAnU2xvdmVuaWEnXG4gICAgfSxcbiAgICBTQjoge1xuICAgICAgICBuYW1lOiAnU29sb21vbiBJc2xhbmRzJ1xuICAgIH0sXG4gICAgU086IHtcbiAgICAgICAgbmFtZTogJ1NvbWFsaWEnXG4gICAgfSxcbiAgICBaQToge1xuICAgICAgICBuYW1lOiAnU291dGggQWZyaWNhJ1xuICAgIH0sXG4gICAgR1M6IHtcbiAgICAgICAgbmFtZTogJ1NvdXRoIEdlb3JnaWEgYW5kIHRoZSBTb3V0aCBTYW5kd2ljaCBJc2xhbmRzJ1xuICAgIH0sXG4gICAgU1M6IHtcbiAgICAgICAgbmFtZTogJ1NvdXRoIFN1ZGFuJ1xuICAgIH0sXG4gICAgRVM6IHtcbiAgICAgICAgbmFtZTogJ1NwYWluJ1xuICAgIH0sXG4gICAgTEs6IHtcbiAgICAgICAgbmFtZTogJ1NyaSBMYW5rYSdcbiAgICB9LFxuICAgIFNEOiB7XG4gICAgICAgIG5hbWU6ICdTdWRhbidcbiAgICB9LFxuICAgIFNSOiB7XG4gICAgICAgIG5hbWU6ICdTdXJpbmFtZSdcbiAgICB9LFxuICAgIFNKOiB7XG4gICAgICAgIG5hbWU6ICdTdmFsYmFyZCBhbmQgSmFuIE1heWVuJ1xuICAgIH0sXG4gICAgU1o6IHtcbiAgICAgICAgbmFtZTogJ1N3YXppbGFuZCdcbiAgICB9LFxuICAgIFNFOiB7XG4gICAgICAgIG5hbWU6ICdTd2VkZW4nXG4gICAgfSxcbiAgICBDSDoge1xuICAgICAgICBuYW1lOiAnU3dpdHplcmxhbmQnXG4gICAgfSxcbiAgICBTWToge1xuICAgICAgICBuYW1lOiAnU3lyaWFuIEFyYWIgUmVwdWJsaWMnXG4gICAgfSxcbiAgICBUVzoge1xuICAgICAgICBuYW1lOiAnVGFpd2FuJ1xuICAgIH0sXG4gICAgVEo6IHtcbiAgICAgICAgbmFtZTogJ1RhamlraXN0YW4nXG4gICAgfSxcbiAgICBUWjoge1xuICAgICAgICBuYW1lOiAnVGFuemFuaWEgVW5pdGVkIFJlcHVibGljIG9mJ1xuICAgIH0sXG4gICAgVEg6IHtcbiAgICAgICAgbmFtZTogJ1RoYWlsYW5kJ1xuICAgIH0sXG4gICAgVEw6IHtcbiAgICAgICAgbmFtZTogJ1RpbW9yLUxlc3RlJ1xuICAgIH0sXG4gICAgVEc6IHtcbiAgICAgICAgbmFtZTogJ1RvZ28nXG4gICAgfSxcbiAgICBUSzoge1xuICAgICAgICBuYW1lOiAnVG9rZWxhdSdcbiAgICB9LFxuICAgIFRPOiB7XG4gICAgICAgIG5hbWU6ICdUb25nYSdcbiAgICB9LFxuICAgIFRUOiB7XG4gICAgICAgIG5hbWU6ICdUcmluaWRhZCBhbmQgVG9iYWdvJ1xuICAgIH0sXG4gICAgVE46IHtcbiAgICAgICAgbmFtZTogJ1R1bmlzaWEnXG4gICAgfSxcbiAgICBUUjoge1xuICAgICAgICBuYW1lOiAnVHVya2V5J1xuICAgIH0sXG4gICAgVE06IHtcbiAgICAgICAgbmFtZTogJ1R1cmttZW5pc3RhbidcbiAgICB9LFxuICAgIFRDOiB7XG4gICAgICAgIG5hbWU6ICdUdXJrcyBhbmQgQ2FpY29zIElzbGFuZHMnXG4gICAgfSxcbiAgICBUVjoge1xuICAgICAgICBuYW1lOiAnVHV2YWx1J1xuICAgIH0sXG4gICAgVUc6IHtcbiAgICAgICAgbmFtZTogJ1VnYW5kYSdcbiAgICB9LFxuICAgIFVBOiB7XG4gICAgICAgIG5hbWU6ICdVa3JhaW5lJ1xuICAgIH0sXG4gICAgQUU6IHtcbiAgICAgICAgbmFtZTogJ1VuaXRlZCBBcmFiIEVtaXJhdGVzJ1xuICAgIH0sXG4gICAgR0I6IHtcbiAgICAgICAgbmFtZTogJ1VuaXRlZCBLaW5nZG9tJ1xuICAgIH0sXG4gICAgVVM6IHtcbiAgICAgICAgbmFtZTogJ1VuaXRlZCBTdGF0ZXMnXG4gICAgfSxcbiAgICBVTToge1xuICAgICAgICBuYW1lOiAnVW5pdGVkIFN0YXRlcyBNaW5vciBPdXRseWluZyBJc2xhbmRzJ1xuICAgIH0sXG4gICAgVVk6IHtcbiAgICAgICAgbmFtZTogJ1VydWd1YXknXG4gICAgfSxcbiAgICBVWjoge1xuICAgICAgICBuYW1lOiAnVXpiZWtpc3RhbidcbiAgICB9LFxuICAgIFZVOiB7XG4gICAgICAgIG5hbWU6ICdWYW51YXR1J1xuICAgIH0sXG4gICAgVkU6IHtcbiAgICAgICAgbmFtZTogJ1ZlbmV6dWVsYSwgQm9saXZhcmlhbiBSZXB1YmxpYyBvZidcbiAgICB9LFxuICAgIFZOOiB7XG4gICAgICAgIG5hbWU6ICdWaWV0bmFtJ1xuICAgIH0sXG4gICAgVkc6IHtcbiAgICAgICAgbmFtZTogJ1ZpcmdpbiBJc2xhbmRzJ1xuICAgIH0sXG4gICAgVkk6IHtcbiAgICAgICAgbmFtZTogJ1ZpcmdpbiBJc2xhbmRzLCBVLlMnXG4gICAgfSxcbiAgICBXRjoge1xuICAgICAgICBuYW1lOiAnV2FsbGlzIGFuZCBGdXR1bmEnXG4gICAgfSxcbiAgICBFSDoge1xuICAgICAgICBuYW1lOiAnV2VzdGVybiBTYWhhcmEnXG4gICAgfSxcbiAgICBZRToge1xuICAgICAgICBuYW1lOiAnWWVtZW4nXG4gICAgfSxcbiAgICBaTToge1xuICAgICAgICBuYW1lOiAnWmFtYmlhJ1xuICAgIH0sXG4gICAgWlc6IHtcbiAgICAgICAgbmFtZTogJ1ppbWJhYndlJ1xuICAgIH1cbn07XG5jb25zdCBHTE9CQUwgPSB7XG4gICAgY29tcGxldGVkT3JkZXI6IG51bGwsXG4gICAgcGhwRGF0YTogbnVsbCxcbiAgICBsaW5rZWRQcm9kdWN0c0lkczogW11cbn07XG5mdW5jdGlvbiBwZWFjaHBheUFsZXJ0KG1lc3NhZ2UsIGFjdGlvbiA9ICcnKSB7XG4gICAgaWYgKEdMT0JBTD8ucGhwRGF0YT8uYWxlcnRTdXBwb3J0KSB7XG4gICAgICAgIHdpbmRvdy5wYXJlbnQucG9zdE1lc3NhZ2Uoe1xuICAgICAgICAgICAgZXZlbnQ6ICdwZWFjaHBheUFsZXJ0JyxcbiAgICAgICAgICAgIGFjdGlvbixcbiAgICAgICAgICAgIG1lc3NhZ2VcbiAgICAgICAgfSwgJyonKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICBhbGVydChtZXNzYWdlKTtcbiAgICAgICAgaWYgKGFjdGlvbikge1xuICAgICAgICAgICAgd2luZG93LnBhcmVudC5wb3N0TWVzc2FnZShhY3Rpb24sICcqJyk7XG4gICAgICAgIH1cbiAgICB9XG59XG5hc3luYyBmdW5jdGlvbiBnZXRDdXN0b21lcigpIHtcbiAgICBjb25zdCBpRnJhbWVXaW5kb3cgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjb25lLWNsaWNrLWlmcmFtZScpPy5jb250ZW50V2luZG93O1xuICAgIGlmICghaUZyYW1lV2luZG93KSB7XG4gICAgICAgIHJldHVybiBudWxsO1xuICAgIH1cbiAgICByZXR1cm4gYXdhaXQgZmV0Y2hXaW5kb3dEYXRhKGlGcmFtZVdpbmRvdywgJ3BwLWdldC1leGlzdGluZy1jdXN0b21lci1kYXRhJyk7XG59XG5hc3luYyBmdW5jdGlvbiBzZXRDdXN0b21lcihjdXN0b21lcikge1xuICAgIGNvbnN0IGlGcmFtZVdpbmRvdyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNvbmUtY2xpY2staWZyYW1lJyk/LmNvbnRlbnRXaW5kb3c7XG4gICAgaWYgKCFpRnJhbWVXaW5kb3cpIHtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbiAgICByZXR1cm4gYXdhaXQgZmV0Y2hXaW5kb3dEYXRhKGlGcmFtZVdpbmRvdywgJ3BwLXNldC1leGlzdGluZy1jdXN0b21lci1kYXRhJywgY3VzdG9tZXIpO1xufVxuZnVuY3Rpb24gY2FydElzVmlydHVhbChjYXJ0KSB7XG4gICAgaWYgKGNhcnQ/Lmxlbmd0aCA9PT0gMCkge1xuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG4gICAgcmV0dXJuIGNhcnQ/LmV2ZXJ5KCh2KT0+di52aXJ0dWFsXG4gICAgKSA/PyB0cnVlO1xufVxuZnVuY3Rpb24gaXRlbXNJbkNhcnQoY2FydCkge1xuICAgIHJldHVybiBjYXJ0Py5sZW5ndGggPz8gMDtcbn1cbmZ1bmN0aW9uIGNhcnRJdGVtUXVhbnRpdHkoY2FydEl0ZW0pIHtcbiAgICByZXR1cm4gdHlwZW9mIGNhcnRJdGVtPy5xdWFudGl0eSA9PT0gJ3N0cmluZycgPyBOdW1iZXIucGFyc2VJbnQoY2FydEl0ZW0ucXVhbnRpdHkpIDogY2FydEl0ZW0/LnF1YW50aXR5ID8/IDA7XG59XG5mdW5jdGlvbiByZXN0cmljdGVkQ2FydFByb2R1Y3RzQnlDb3VudHJ5KGNhcnQsIHNlbGVjdGVkQ291bnRyeUNvZGUpIHtcbiAgICByZXR1cm4gY2FydC5maWx0ZXIoKHYpPT57XG4gICAgICAgIGlmICh2LndjX2NvdW50cnlfYmFzZV9yZXN0cmljdGlvbnMpIHtcbiAgICAgICAgICAgIGlmICh2LndjX2NvdW50cnlfYmFzZV9yZXN0cmljdGlvbnMudHlwZSA9PT0gJ3NwZWNpZmljJyAmJiAhdi53Y19jb3VudHJ5X2Jhc2VfcmVzdHJpY3Rpb25zLmNvdW50cmllcy5pbmNsdWRlcyhzZWxlY3RlZENvdW50cnlDb2RlKSkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHYud2NfY291bnRyeV9iYXNlX3Jlc3RyaWN0aW9ucy50eXBlID09PSAnZXhjbHVkZWQnICYmIHYud2NfY291bnRyeV9iYXNlX3Jlc3RyaWN0aW9ucy5jb3VudHJpZXMuaW5jbHVkZXMoc2VsZWN0ZWRDb3VudHJ5Q29kZSkpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfSk7XG59XG5hc3luYyBmdW5jdGlvbiB2YWxpZGF0ZUNhcnRJdGVtc1dpdGhDdXN0b21lcihjYXJ0LCB1c2VMb2NhbFN0b3JhZ2UpIHtcbiAgICBjb25zdCBjdXN0b21lciA9IGF3YWl0IGdldEN1c3RvbWVyKCk7XG4gICAgY29uc3QgY291bnRyeVZhbHVlID0gJHFzKCcjY291bnRyeScpPy52YWx1ZSA/PyAnJztcbiAgICBpZiAodXNlTG9jYWxTdG9yYWdlICYmIGN1c3RvbWVyKSB7XG4gICAgICAgIGNvbnN0IGludmFsaWRDYXJ0SXRlbXMgPSByZXN0cmljdGVkQ2FydFByb2R1Y3RzQnlDb3VudHJ5KGNhcnQsIGN1c3RvbWVyLmNvdW50cnkpO1xuICAgICAgICBpZiAoaW52YWxpZENhcnRJdGVtcy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgICB9XG4gICAgICAgIHBlYWNocGF5QWxlcnQoYFRoZSBmb2xsb3dpbmcgY2FydCBpdGVtcyBjYW5ub3QgYmUgc2hpcHBlZCB0byAke2dldENvdW50cnlOYW1lKGNvdW50cnlWYWx1ZSl9OlxcbiAke2ludmFsaWRDYXJ0SXRlbXMubWFwKCh2KT0+di5uYW1lXG4gICAgICAgICkuam9pbignLCcpfS5cXG4gUGxlYXNlIHJlbW92ZSB0aGVtIGZyb20geW91ciBjYXJ0LmAsICdjbG9zZU1vZGFsJyk7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgY29uc3QgaW52YWxpZENhcnRJdGVtcyA9IHJlc3RyaWN0ZWRDYXJ0UHJvZHVjdHNCeUNvdW50cnkoY2FydCwgY291bnRyeVZhbHVlKTtcbiAgICBpZiAoaW52YWxpZENhcnRJdGVtcy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfVxuICAgIHBlYWNocGF5QWxlcnQoYFRoZSBmb2xsb3dpbmcgY2FydCBpdGVtcyBjYW5ub3QgYmUgc2hpcHBlZCB0byAke2dldENvdW50cnlOYW1lKGNvdW50cnlWYWx1ZSl9OlxcbiAke2ludmFsaWRDYXJ0SXRlbXMubWFwKCh2KT0+di5uYW1lXG4gICAgKS5qb2luKCcsJyl9LlxcbiBQbGVhc2UgcmVtb3ZlIHRoZW0gZnJvbSB5b3VyIGNhcnQuYCwgJ2Nsb3NlTW9kYWwnKTtcbiAgICByZXR1cm4gZmFsc2U7XG59XG5mdW5jdGlvbiBidWlsZFN1YnNjcmlwdGlvblByaWNlTWV0YURhdGEobWV0YSwgX19zaG9ydCA9IGZhbHNlKSB7XG4gICAgaWYgKCFtZXRhLnN1YnNjcmlwdGlvbikge1xuICAgICAgICByZXR1cm4gJyc7XG4gICAgfVxuICAgIGlmIChOdW1iZXIucGFyc2VJbnQoU3RyaW5nKG1ldGEuc3Vic2NyaXB0aW9uLnBlcmlvZF9pbnRlcnZhbCkpID09PSAxKSB7XG4gICAgICAgIHJldHVybiBgIC8gJHttZXRhLnN1YnNjcmlwdGlvbi5wZXJpb2R9YDtcbiAgICB9XG4gICAgaWYgKF9fc2hvcnQpIHtcbiAgICAgICAgcmV0dXJuIGAgZXZlcnkgJHttZXRhLnN1YnNjcmlwdGlvbi5wZXJpb2RfaW50ZXJ2YWx9ICR7bWV0YS5zdWJzY3JpcHRpb24ucGVyaW9kfXNgO1xuICAgIH1cbiAgICByZXR1cm4gYCBldmVyeSAke21ldGEuc3Vic2NyaXB0aW9uLnBlcmlvZF9pbnRlcnZhbH0gJHttZXRhLnN1YnNjcmlwdGlvbi5wZXJpb2R9cyBmb3IgJHttZXRhLnN1YnNjcmlwdGlvbi5sZW5ndGh9ICR7bWV0YS5zdWJzY3JpcHRpb24ucGVyaW9kfXNgO1xufVxuZnVuY3Rpb24gYnVpbGRTdWJzY3JpcHRpb25GaXJzdFJlbmV3YWxTdHJpbmcobWV0YSkge1xuICAgIGlmICghbWV0YS5zdWJzY3JpcHRpb24gfHwgIW1ldGEuc3Vic2NyaXB0aW9uLmZpcnN0X3JlbmV3YWwpIHtcbiAgICAgICAgcmV0dXJuICcnO1xuICAgIH1cbiAgICBjb25zdCBkYXRlID0gbmV3IERhdGUobWV0YS5zdWJzY3JpcHRpb24uZmlyc3RfcmVuZXdhbCk7XG4gICAgY29uc3Qgb3B0aW9ucyA9IHtcbiAgICAgICAgeWVhcjogJ251bWVyaWMnLFxuICAgICAgICBtb250aDogJ2xvbmcnLFxuICAgICAgICBkYXk6ICdudW1lcmljJ1xuICAgIH07XG4gICAgcmV0dXJuIGBGaXJzdCByZW5ld2FsOiAke2RhdGUudG9Mb2NhbGVTdHJpbmcoRW52aXJvbm1lbnQubGFuZ3VhZ2UoKSwgb3B0aW9ucyl9YDtcbn1cbmZ1bmN0aW9uIGluaXRNZXJjaGFudEFjY291bnQobWVzc2FnZSkge1xuICAgIGluaXRNZXJjaGFudEFjY291bnRFdmVudHMoKTtcbiAgICBjb25zdCBhY2NvdW50RGV0YWlscyA9IG1lc3NhZ2UucGhwRGF0YS5tZXJjaGFudF9jdXN0b21lcl9hY2NvdW50O1xuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZU1lcmNoYW50QWNjb3VudENvbmZpZyh7XG4gICAgICAgIGFsbG93R3Vlc3RDaGVja291dDogYWNjb3VudERldGFpbHM/LmFsbG93X2d1ZXN0X2NoZWNrb3V0ID8/IHRydWUsXG4gICAgICAgIGFsbG93QWNjb3VudENyZWF0aW9uT3JMb2dpbkR1cmluZ0NoZWNrb3V0OiBhY2NvdW50RGV0YWlscz8ubG9naW5zX2FuZF9yZWdpc3RyYXRpb25zX2VuYWJsZWQgPz8gdHJ1ZSxcbiAgICAgICAgYXV0b0dlbmVyYXRlVXNlcm5hbWU6IGFjY291bnREZXRhaWxzPy5hdXRvX2dlbmVyYXRlX3VzZXJuYW1lID8/IGZhbHNlLFxuICAgICAgICBhdXRvR2VuZXJhdGVQYXNzd29yZDogYWNjb3VudERldGFpbHM/LmF1dG9fZ2VuZXJhdGVfcGFzc3dvcmQgPz8gZmFsc2VcbiAgICB9KSk7XG4gICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlQ3VzdG9tZXJNZXJjaGFudEFjY291bnQoe1xuICAgICAgICB1c2VybmFtZTogYWNjb3VudERldGFpbHMuZW1haWwgPz8gJycsXG4gICAgICAgIGxvZ2dlZEluOiBhY2NvdW50RGV0YWlscy5sb2dnZWRfaW4gPz8gZmFsc2UsXG4gICAgICAgIHVzZXJuYW1lSXNSZWdpc3RlcmVkOiBhY2NvdW50RGV0YWlscy5sb2dnZWRfaW4gPz8gZmFsc2VcbiAgICB9KSk7XG59XG5mdW5jdGlvbiBpbml0TWVyY2hhbnRBY2NvdW50RXZlbnRzKCkge1xuICAgIHN0b3JlLnN1YnNjcmliZSgoKT0+e1xuICAgICAgICByZW5kZXJNZXJjaGFudEN1c3RvbWVyQWNjb3VudFBhc3N3b3JkSW5wdXQoTWVyY2hhbnRDb25maWd1cmF0aW9uLmhvc3ROYW1lKCksICFFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpICYmIEVudmlyb25tZW50Lm1vZGFsVUkucGFnZSgpID09PSAncGF5bWVudCcpO1xuICAgIH0pO1xuICAgICRxcygnI3BwLWluZm8tZm9ybScpPy5hZGRFdmVudExpc3RlbmVyKCdzdWJtaXQnLCAoZXZlbnQpPT57XG4gICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgIHJlcXVlc3RNZXJjaGFudEFjY291bnRFeGlzdGVuY2UoUGVhY2hQYXlDdXN0b21lci5lbWFpbCgpKTtcbiAgICB9KTtcbiAgICBvbldpbmRvd01lc3NhZ2UoJ2VtYWlsRXhpc3QnLCAobWVzc2FnZSk9PntcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlQ3VzdG9tZXJNZXJjaGFudEFjY291bnRFeGlzdGVuY2UoQm9vbGVhbihtZXNzYWdlLmVtYWlsUmVzdWx0KSkpO1xuICAgIH0pO1xufVxuZnVuY3Rpb24gcmVxdWVzdE1lcmNoYW50QWNjb3VudEV4aXN0ZW5jZShlbWFpbCkge1xuICAgIHdpbmRvdy50b3A/LnBvc3RNZXNzYWdlKHtcbiAgICAgICAgZXZlbnQ6ICdlbWFpbEV4aXN0JyxcbiAgICAgICAgZW1haWxcbiAgICB9LCAnKicpO1xufVxuZnVuY3Rpb24gZ2V0TWVyY2hhbnRDdXN0b21lckFjY291bnRQYXNzd29yZFZhbHVlKCkge1xuICAgIGNvbnN0ICRpbnB1dCA9ICRxcygnI2FjY291bnQtcGFzc3dvcmQnKTtcbiAgICBjb25zdCAkaW5wdXRFeGlzdGluZyA9ICRxcygnI2FjY291bnQtcGFzc3dvcmQtZXhpc3RpbmcnKTtcbiAgICBpZiAoISRpbnB1dCB8fCAhJGlucHV0RXhpc3RpbmcpIHtcbiAgICAgICAgcmV0dXJuICcnO1xuICAgIH1cbiAgICBpZiAoJGlucHV0RXhpc3RpbmcudmFsdWUgIT09ICcnKSB7XG4gICAgICAgIHJldHVybiAkaW5wdXRFeGlzdGluZy52YWx1ZTtcbiAgICB9XG4gICAgcmV0dXJuICRpbnB1dC52YWx1ZTtcbn1cbmZ1bmN0aW9uIHZhbGlkYXRlTWVyY2hhbnRDdXN0b21lclBhc3N3b3JkRmllbGQoKSB7XG4gICAgY29uc3QgcGFzc3dvcmQgPSBnZXRNZXJjaGFudEN1c3RvbWVyQWNjb3VudFBhc3N3b3JkVmFsdWUoKTtcbiAgICBjb25zdCAkcmVkVGV4dCA9ICRxcygnI2FjY291bnQtcGFzc3dvcmQtZXJyb3InKTtcbiAgICBjb25zdCAkcmVkVGV4dEV4aXN0aW5nID0gJHFzKCcjYWNjb3VudC1wYXNzd29yZC1lcnJvci1leGlzdGluZycpO1xuICAgIGlmICghJHJlZFRleHQgfHwgISRyZWRUZXh0RXhpc3RpbmcpIHtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbiAgICBpZiAocGFzc3dvcmQgPT09ICcnIHx8IHBhc3N3b3JkLmxlbmd0aCA8IDgpIHtcbiAgICAgICAgJHJlZFRleHQudGV4dENvbnRlbnQgPSBnZXRMb2NhbGVUZXh0KCdpbnZhbGlkLW1lcmNoYW50LXBhc3N3b3JkJyk7XG4gICAgICAgICRyZWRUZXh0RXhpc3RpbmcudGV4dENvbnRlbnQgPSBnZXRMb2NhbGVUZXh0KCdpbnZhbGlkLW1lcmNoYW50LXBhc3N3b3JkJyk7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgJHJlZFRleHQudGV4dENvbnRlbnQgPSAnJztcbiAgICAkcmVkVGV4dEV4aXN0aW5nLnRleHRDb250ZW50ID0gJyc7XG4gICAgcmV0dXJuIHRydWU7XG59XG5mdW5jdGlvbiBzaG91bGRTaG93TWVyY2hhbnRDdXN0b21lckFjY291bnRQYXNzd29yZEZpZWxkKCkge1xuICAgIGlmIChNZXJjaGFudEN1c3RvbWVyLmxvZ2dlZEluKCkpIHtcbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbiAgICBpZiAoIUNhcnRzLnN1YnNjcmlwdGlvblByZXNlbnQoKSkge1xuICAgICAgICBpZiAoIU1lcmNoYW50Q29uZmlndXJhdGlvbi5hY2NvdW50cy5hbGxvd0d1ZXN0Q2hlY2tvdXQoKSkge1xuICAgICAgICAgICAgaWYgKE1lcmNoYW50Q29uZmlndXJhdGlvbi5hY2NvdW50cy5nZW5lcmF0ZVBhc3N3b3JkRW5hYmxlZCgpICYmICFNZXJjaGFudEN1c3RvbWVyLnVzZXJuYW1lRXhpc3QoKSkge1xuICAgICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoIU1lcmNoYW50Q29uZmlndXJhdGlvbi5hY2NvdW50cy5sb2dpbkR1cmluZ0NoZWNrb3V0RW5hYmxlZCgpICYmICFNZXJjaGFudEN1c3RvbWVyLnVzZXJuYW1lRXhpc3QoKSkge1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIGlmIChNZXJjaGFudENvbmZpZ3VyYXRpb24uYWNjb3VudHMuZ2VuZXJhdGVQYXNzd29yZEVuYWJsZWQoKSAmJiAhTWVyY2hhbnRDdXN0b21lci51c2VybmFtZUV4aXN0KCkpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgIH0gZWxzZSB7XG4gICAgICAgIGlmIChNZXJjaGFudENvbmZpZ3VyYXRpb24uYWNjb3VudHMuZ2VuZXJhdGVQYXNzd29yZEVuYWJsZWQoKSAmJiAhTWVyY2hhbnRDdXN0b21lci51c2VybmFtZUV4aXN0KCkpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZXR1cm4gdHJ1ZTtcbn1cbmZ1bmN0aW9uIHJlbmRlck1lcmNoYW50Q3VzdG9tZXJBY2NvdW50UGFzc3dvcmRJbnB1dChtZXJjaGFudEhvc3RuYW1lLCBvbk5ld1BheW1lbnRTY3JlZW4gPSBmYWxzZSkge1xuICAgIGNvbnN0ICRpbnB1dCA9ICRxcygnI3BwLWFjY291bnQtcGFzc3dvcmQnKTtcbiAgICBjb25zdCAkaW5wdXRFeGlzdGluZyA9ICRxcygnI3BwLWFjY291bnQtcGFzc3dvcmQtZXhpc3RpbmcnKTtcbiAgICBpZiAoISRpbnB1dCB8fCAhJGlucHV0RXhpc3RpbmcpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICAkaW5wdXQudmFsdWUgPSAnJztcbiAgICAkaW5wdXRFeGlzdGluZy52YWx1ZSA9ICcnO1xuICAgIGNvbnN0IGxhYmVsSFRNTCA9IGdldExvY2FsZVRleHQoJ2FjY291bnQtcGFzc3dvcmQtZXhwbGFuYXRpb24nKSArICcgJyArIG1lcmNoYW50SG9zdG5hbWU7XG4gICAgJHFzKCcjcHAtYWNjb3VudC1wYXNzd29yZC1sYWJlbCcsICgkZWxlbWVudCk9PiRlbGVtZW50LmlubmVySFRNTCA9IGxhYmVsSFRNTFxuICAgICk7XG4gICAgJHFzKCcjcHAtYWNjb3VudC1wYXNzd29yZC1sYWJlbC1leGlzdGluZycsICgkZWxlbWVudCk9PiRlbGVtZW50LmlubmVySFRNTCA9IGxhYmVsSFRNTFxuICAgICk7XG4gICAgaWYgKHNob3VsZFNob3dNZXJjaGFudEN1c3RvbWVyQWNjb3VudFBhc3N3b3JkRmllbGQoKSkge1xuICAgICAgICBpZiAob25OZXdQYXltZW50U2NyZWVuKSB7XG4gICAgICAgICAgICAkaW5wdXQuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJGlucHV0LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgICAgICAkaW5wdXRFeGlzdGluZy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJGlucHV0LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJGlucHV0RXhpc3RpbmcuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXREZWxpdmVyeURhdGUoKSB7XG4gICAgJHFzKCcjZXhpc3RpbmctZGVsaXZlcnktZGF0ZScpPy5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBjaGVja0RlbGl2ZXJ5RGF0ZUlzVmFsaWQpO1xuICAgICRxcygnI2V4aXN0aW5nLWRlbGl2ZXJ5LWRhdGUnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywgdXBkYXRlRGVsaXZlcnlEYXRlKTtcbiAgICAkcXMoJyNkZWxpdmVyeS1kYXRlJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsIGNoZWNrRGVsaXZlcnlEYXRlSXNWYWxpZCk7XG4gICAgc3RvcmUuc3Vic2NyaWJlKCgpPT57XG4gICAgICAgIHJlbmRlckRlbGl2ZXJ5RGF0ZSgpO1xuICAgIH0pO1xufVxuZnVuY3Rpb24gY29sbGVjdERlbGl2ZXJ5RGF0ZSgpIHtcbiAgICByZXR1cm4gJHFzKCcjZGVsaXZlcnktZGF0ZScpPy52YWx1ZSA/PyAnJztcbn1cbmZ1bmN0aW9uIHJlbmRlckRlbGl2ZXJ5RGF0ZSgpIHtcbiAgICBpZiAoIUdMT0JBTC5waHBEYXRhKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKCFHTE9CQUwucGhwRGF0YS5wbHVnaW5fd29vY29tbWVyY2Vfb3JkZXJfZGVsaXZlcnlfYWN0aXZlKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgJHFzKCcjZXhpc3RpbmctY2hlY2tvdXQtZGVsaXZlcnktZGF0ZScpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgJHFzKCcjY2hlY2tvdXQtZGVsaXZlcnktZGF0ZScpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgY29uc3QgdG9kYXlEYXRlID0gbmV3IERhdGUoKTtcbiAgICBjb25zdCBtYXhEYXRlID0gbmV3IERhdGUoKTtcbiAgICBtYXhEYXRlLnNldERhdGUodG9kYXlEYXRlLmdldERhdGUoKSArIChHTE9CQUwucGhwRGF0YS5wbHVnaW5fd29vY29tbWVyY2Vfb3JkZXJfZGVsaXZlcnlfb3B0aW9ucz8ud2Nfb2RfbWF4X2RlbGl2ZXJ5X2RheXMgPz8gMCkpO1xuICAgIGNvbnN0ICRzaGlwcGluZ0RhdGUgPSAkcXMoJyNkZWxpdmVyeS1kYXRlJyk7XG4gICAgY29uc3QgJGV4aXN0aW5nQ3VzdG9tZXJTaGlwcGluZ0RhdGUgPSAkcXMoJyNleGlzdGluZy1kZWxpdmVyeS1kYXRlJyk7XG4gICAgaWYgKCEkc2hpcHBpbmdEYXRlIHx8ICEkZXhpc3RpbmdDdXN0b21lclNoaXBwaW5nRGF0ZSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgICRzaGlwcGluZ0RhdGUucmVxdWlyZWQgPSB0cnVlO1xuICAgICRleGlzdGluZ0N1c3RvbWVyU2hpcHBpbmdEYXRlLm1pbiA9IHRvZGF5RGF0ZS50b0lTT1N0cmluZygpLnNsaWNlKDAsIDEwKTtcbiAgICAkc2hpcHBpbmdEYXRlLm1pbiA9IHRvZGF5RGF0ZS50b0lTT1N0cmluZygpLnNsaWNlKDAsIDEwKTtcbiAgICAkZXhpc3RpbmdDdXN0b21lclNoaXBwaW5nRGF0ZS5tYXggPSBtYXhEYXRlLnRvSVNPU3RyaW5nKCkuc2xpY2UoMCwgMTApO1xuICAgICRzaGlwcGluZ0RhdGUubWF4ID0gbWF4RGF0ZS50b0lTT1N0cmluZygpLnNsaWNlKDAsIDEwKTtcbn1cbmZ1bmN0aW9uIGNoZWNrRGVsaXZlcnlEYXRlSXNFbXB0eSgpIHtcbiAgICByZXR1cm4gKCRxcygnI2V4aXN0aW5nLWRlbGl2ZXJ5LWRhdGUnKT8udmFsdWUgPz8gJycpID09PSAnJztcbn1cbmZ1bmN0aW9uIHVwZGF0ZURlbGl2ZXJ5RGF0ZSgpIHtcbiAgICBjb25zdCAkZGVsaXZlcnlEYXRlID0gJHFzKCcjZGVsaXZlcnktZGF0ZScpO1xuICAgIGlmICghJGRlbGl2ZXJ5RGF0ZSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgICRkZWxpdmVyeURhdGUudmFsdWUgPSAkcXMoJyNleGlzdGluZy1kZWxpdmVyeS1kYXRlJyk/LnZhbHVlID8/ICcnO1xufVxuZnVuY3Rpb24gY2hlY2tEZWxpdmVyeURhdGVJc1ZhbGlkKGV2ZW50KSB7XG4gICAgaWYgKCFldmVudC50YXJnZXQpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpZiAoIXZhbGlkYXRlRGF0ZUlzQXZhaWxhYmxlKGV2ZW50LnRhcmdldC52YWx1ZSkpIHtcbiAgICAgICAgZXZlbnQudGFyZ2V0LnZhbHVlID0gJyc7XG4gICAgICAgIHBlYWNocGF5QWxlcnQoJ1BsZWFzZSBzZWxlY3QgYW5vdGhlciBkZWxpdmVyeSBkYXRlLicpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHZhbGlkYXRlRGF0ZUlzQXZhaWxhYmxlKGRhdGVTdHJpbmcpIHtcbiAgICBpZiAoIUdMT0JBTC5waHBEYXRhKSB7XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgY29uc3QgZGF5ID0gbmV3IERhdGUoZGF0ZVN0cmluZyArICdUMDA6MDA6MDAnKS5nZXREYXkoKTtcbiAgICByZXR1cm4gIUdMT0JBTC5waHBEYXRhLnBsdWdpbl93b29jb21tZXJjZV9vcmRlcl9kZWxpdmVyeV9vcHRpb25zPy5kZWxpdmVyeV91bmNoZWNrZWRfZGF5Py5pbmNsdWRlcyhTdHJpbmcoZGF5KSk7XG59XG5mdW5jdGlvbiBpbml0T3JkZXJOb3RlcygpIHtcbiAgICBpZiAoRmVhdHVyZS5lbmFibGVkKEZlYXR1cmVGbGFnLk9SREVSX05PVEVTKSkge1xuICAgICAgICBmb3IgKGNvbnN0ICRmb3JtIG9mICRxc0FsbCgnLm9yZGVyLW5vdGVzJykpe1xuICAgICAgICAgICAgJGZvcm0uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gY29sbGVjdE9yZGVyTm90ZXMoKSB7XG4gICAgY29uc3Qgb3JkZXJOb3RlcyA9ICRxcygnI29yZGVyLW5vdGVzJyk7XG4gICAgY29uc3Qgb3JkZXJOb3Rlc0V4aXN0aW5nID0gJHFzKCcjb3JkZXItbm90ZXMtZXhpc3RpbmcnKTtcbiAgICBpZiAob3JkZXJOb3RlcyAhPT0gbnVsbCAmJiBvcmRlck5vdGVzRXhpc3RpbmcgIT09IG51bGwpIHtcbiAgICAgICAgaWYgKG9yZGVyTm90ZXMudmFsdWUgIT09ICcnICYmIG9yZGVyTm90ZXNFeGlzdGluZy52YWx1ZSA9PT0gJycpIHtcbiAgICAgICAgICAgIHJldHVybiBvcmRlck5vdGVzLnZhbHVlO1xuICAgICAgICB9XG4gICAgICAgIGlmIChvcmRlck5vdGVzLnZhbHVlID09PSAnJyAmJiBvcmRlck5vdGVzRXhpc3RpbmcudmFsdWUgIT09ICcnKSB7XG4gICAgICAgICAgICByZXR1cm4gb3JkZXJOb3Rlc0V4aXN0aW5nLnZhbHVlO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJldHVybiAnJztcbn1cbmZ1bmN0aW9uIHN5bmNPcmRlck5vdGVzKGV4aXRNb2R1bGUgPSBmYWxzZSkge1xuICAgIGNvbnN0IG9yZGVyTm90ZXNFeGlzdGluZyA9ICRxcygnI29yZGVyLW5vdGVzLWV4aXN0aW5nJyk7XG4gICAgY29uc3Qgb3JkZXJOb3RlcyA9ICRxcygnI29yZGVyLW5vdGVzJyk7XG4gICAgaWYgKG9yZGVyTm90ZXMgIT09IG51bGwgJiYgb3JkZXJOb3Rlc0V4aXN0aW5nICE9PSBudWxsKSB7XG4gICAgICAgIGlmIChFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpICYmIG9yZGVyTm90ZXMudmFsdWUgIT09ICcnICYmIGV4aXRNb2R1bGUpIHtcbiAgICAgICAgICAgIG9yZGVyTm90ZXNFeGlzdGluZy52YWx1ZSA9IG9yZGVyTm90ZXMudmFsdWU7XG4gICAgICAgICAgICBvcmRlck5vdGVzLnZhbHVlID0gJyc7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgaWYgKG9yZGVyTm90ZXNFeGlzdGluZy52YWx1ZSAhPT0gJycgJiYgIWV4aXRNb2R1bGUpIHtcbiAgICAgICAgICAgIG9yZGVyTm90ZXMudmFsdWUgPSBvcmRlck5vdGVzRXhpc3RpbmcudmFsdWU7XG4gICAgICAgICAgICBvcmRlck5vdGVzRXhpc3RpbmcudmFsdWUgPSAnJztcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRWQVQobWVzc2FnZSkge1xuICAgIGluaXRWYXRFdmVudHMoKTtcbiAgICBpZiAobWVzc2FnZS5waHBEYXRhLnZhdF9zZWxmX3ZlcmlmeSA9PT0gJzEnKSB7XG4gICAgICAgIHJlbmRlclZlcmlmeUxvY2F0aW9uKCk7XG4gICAgfVxuICAgIGNvbnN0IHZhdFR5cGVzUmVxdWlyaW5nSUQgPSBtZXNzYWdlLnBocERhdGEudmF0X3JlcXVpcmVkID09PSAnMScgfHwgbWVzc2FnZS5waHBEYXRhLnZhdF9yZXF1aXJlZCA9PT0gJzInICYmIGlzRVVDb3VudHJ5KFBlYWNoUGF5Q3VzdG9tZXIuY291bnRyeSgpKTtcbiAgICBpZiAodmF0VHlwZXNSZXF1aXJpbmdJRCkge1xuICAgICAgICByZW5kZXJWQVRJRElucHV0KCk7XG4gICAgfVxufVxuZnVuY3Rpb24gaW5pdFZhdEV2ZW50cygpIHtcbiAgICAkcXMoJyNwcC1pbmZvLWZvcm0nKT8uYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgKGV2ZW50KT0+e1xuICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICBjb25zdCB2YXRUeXBlc1JlcXVpcmluZ0lEID0gR0xPQkFMLnBocERhdGE/LnZhdF9yZXF1aXJlZCA9PT0gJzEnIHx8IEdMT0JBTC5waHBEYXRhPy52YXRfcmVxdWlyZWQgPT09ICcyJyAmJiBpc0VVQ291bnRyeShQZWFjaFBheUN1c3RvbWVyLmNvdW50cnkoKSk7XG4gICAgICAgIGlmICh2YXRUeXBlc1JlcXVpcmluZ0lEKSB7XG4gICAgICAgICAgICByZW5kZXJWQVRJRElucHV0KCk7XG4gICAgICAgIH1cbiAgICB9KTtcbn1cbmZ1bmN0aW9uIHJlbmRlclZBVElESW5wdXQoKSB7XG4gICAgY29uc3QgJHByZXZpb3VzRGl2cyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNuZXdFVVZhdERpdicpO1xuICAgICRwcmV2aW91c0RpdnM/LnJlbW92ZSgpO1xuICAgIGNvbnN0ICRFVVZhdERpdiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgIGNvbnN0ICR2YXRGb3JtID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZm9ybScpO1xuICAgIGNvbnN0ICR2YXROdW1iZXIgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdpbnB1dCcpO1xuICAgICR2YXROdW1iZXIuc2V0QXR0cmlidXRlKCdwbGFjZWhvbGRlcicsICdyZXF1aXJlZCcpO1xuICAgICR2YXROdW1iZXIuc2V0QXR0cmlidXRlKCdjbGFzcycsICd2YXRJbnB1dCcpO1xuICAgIGNvbnN0ICRwcm9tcHQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzcGFuJyk7XG4gICAgJHByb21wdC5pbm5lckhUTUwgPSAnVmF0IE51bWJlcic7XG4gICAgJHZhdEZvcm0uYXBwZW5kKCR2YXROdW1iZXIpO1xuICAgICRFVVZhdERpdi5hcHBlbmQoJHByb21wdCk7XG4gICAgJEVVVmF0RGl2LmFwcGVuZCgkdmF0Rm9ybSk7XG4gICAgJEVVVmF0RGl2LnNldEF0dHJpYnV0ZSgnaWQnLCAnRXVWYXREaXYnKTtcbiAgICAkRVVWYXREaXYuc2V0QXR0cmlidXRlKCdjbGFzcycsICdjb2xvci1jaGFuZ2UtdGV4dCcpO1xuICAgIGxldCAkaW5zZXJ0aW9uTG9jYXRpb247XG4gICAgY29uc3QgJG5ld0N1c3RvbWVyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3BwLW5ldy1jdXN0b21lci1jaGVja291dCcpO1xuICAgIGlmICgkbmV3Q3VzdG9tZXI/LmNsYXNzTGlzdC5jb250YWlucygnaGlkZScpKSB7XG4gICAgICAgICRpbnNlcnRpb25Mb2NhdGlvbiA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNleGlzdGluZy1jaGVja291dC1jYXJkJyk7XG4gICAgICAgICR2YXROdW1iZXIuc2V0QXR0cmlidXRlKCdpZCcsICdwcFZhdE51bUV4aXN0aW5nJyk7XG4gICAgICAgICRFVVZhdERpdi5zZXRBdHRyaWJ1dGUoJ2NsYXNzJywgJ2NvbG9yLWNoYW5nZS10ZXh0Jyk7XG4gICAgICAgICRpbnNlcnRpb25Mb2NhdGlvbj8uaW5zZXJ0QWRqYWNlbnRFbGVtZW50KCdhZnRlcmVuZCcsICRFVVZhdERpdik7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJGluc2VydGlvbkxvY2F0aW9uID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3BheW1lbnQtbWV0aG9kcycpO1xuICAgICAgICAkdmF0TnVtYmVyLnNldEF0dHJpYnV0ZSgnaWQnLCAncHBWYXROdW1OZXcnKTtcbiAgICAgICAgJEVVVmF0RGl2LnNldEF0dHJpYnV0ZSgnY2xhc3MnLCAneC1sYXJnZScpO1xuICAgICAgICAkRVVWYXREaXYuc2V0QXR0cmlidXRlKCdpZCcsICduZXdFVVZhdERpdicpO1xuICAgICAgICAkaW5zZXJ0aW9uTG9jYXRpb24/Lmluc2VydEFkamFjZW50RWxlbWVudCgnYWZ0ZXJlbmQnLCAkRVVWYXREaXYpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGdldFZhdE51bWJlcigpIHtcbiAgICBjb25zdCAkbmV3Q3VzdG9tZXIgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjcHAtbmV3LWN1c3RvbWVyLWNoZWNrb3V0Jyk7XG4gICAgaWYgKCRuZXdDdXN0b21lcj8uY2xhc3NMaXN0LmNvbnRhaW5zKCdoaWRlJykpIHtcbiAgICAgICAgY29uc3QgJHBwVmF0ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI3BwVmF0TnVtRXhpc3RpbmcnKTtcbiAgICAgICAgaWYgKCEkcHBWYXQpIHtcbiAgICAgICAgICAgIHJldHVybiAnJztcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gJHBwVmF0LnZhbHVlID8/ICcnO1xuICAgIH1cbiAgICBjb25zdCAkcHBWYXQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjcHBWYXROdW1OZXcnKTtcbiAgICBpZiAoISRwcFZhdCkge1xuICAgICAgICByZXR1cm4gJyc7XG4gICAgfVxuICAgIHJldHVybiAkcHBWYXQudmFsdWUgPz8gJyc7XG59XG5mdW5jdGlvbiByZW5kZXJWZXJpZnlMb2NhdGlvbigpIHtcbiAgICBjb25zdCAkdmVyaWZ5RGl2ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgY29uc3QgJHZlcmlmeUNoZWNrYm94ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaW5wdXQnKTtcbiAgICBjb25zdCAkZGVzY3JpcHRvciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2xhYmVsJyk7XG4gICAgJHZlcmlmeUNoZWNrYm94LnNldEF0dHJpYnV0ZSgnaWQnLCAncHBfdmVyaWZ5X2NvdW50cnknKTtcbiAgICAkdmVyaWZ5Q2hlY2tib3guc2V0QXR0cmlidXRlKCd0eXBlJywgJ2NoZWNrYm94Jyk7XG4gICAgJHZlcmlmeUNoZWNrYm94LnNldEF0dHJpYnV0ZSgndmFsdWUnLCAnMScpO1xuICAgICRkZXNjcmlwdG9yLnNldEF0dHJpYnV0ZSgnZm9yJywgJ3BwX3ZlcmlmeV9jb3VudHJ5Jyk7XG4gICAgJGRlc2NyaXB0b3IuaW5uZXJIVE1MID0gZ2V0TG9jYWxlVGV4dCgndmVyaWZ5LWxvY2F0aW9uJyk7XG4gICAgJHZlcmlmeURpdi5hcHBlbmQoJHZlcmlmeUNoZWNrYm94KTtcbiAgICAkdmVyaWZ5RGl2LmFwcGVuZCgkZGVzY3JpcHRvcik7XG4gICAgY29uc3QgJGRpdkNsb25lID0gJHZlcmlmeURpdi5jbG9uZU5vZGUodHJ1ZSk7XG4gICAgY29uc3QgJGluc2VydGlvbkxvY2F0aW9uID0gJHFzKCcjZXhpc3RpbmctY2hlY2tvdXQtY2FyZCcpO1xuICAgIGNvbnN0ICRpbnNlcnRMb2NhdGlvbjIgPSAkcXMoJyNwYXltZW50LW1ldGhvZHMnKTtcbiAgICAkaW5zZXJ0aW9uTG9jYXRpb24/Lmluc2VydEFkamFjZW50RWxlbWVudCgnYWZ0ZXJlbmQnLCAkdmVyaWZ5RGl2KTtcbiAgICAkaW5zZXJ0TG9jYXRpb24yPy5pbnNlcnRBZGphY2VudEVsZW1lbnQoJ2FmdGVyZW5kJywgJGRpdkNsb25lKTtcbn1cbmZ1bmN0aW9uIGdldFZlcmlmeSgpIHtcbiAgICBjb25zdCAkaXNWZXJpZmllZCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJyNwcF92ZXJpZnlfY291bnRyeScpO1xuICAgIGlmICgkaXNWZXJpZmllZFswXS5jaGVja2VkIHx8ICRpc1ZlcmlmaWVkWzFdLmNoZWNrZWQpIHtcbiAgICAgICAgcmV0dXJuICcxJztcbiAgICB9XG4gICAgcmV0dXJuICcnO1xufVxuZnVuY3Rpb24gaXNEZXZFbnZpcm9ubWVudChiYXNlVXJsKSB7XG4gICAgcmV0dXJuIGJhc2VVcmwgPT09ICdodHRwczovL2Rldi5wZWFjaHBheS5hcHAvJyB8fCBiYXNlVXJsID09PSAnaHR0cHM6Ly9kZXYucGVhY2hwYXkubG9jYWwvJyB8fCBiYXNlVXJsID09PSAnaHR0cHM6Ly9wcm9kLnBlYWNocGF5LmxvY2FsLyc7XG59XG5mdW5jdGlvbiBnZXRCYXNlVVJMKG1lcmNoYW50SG9zdG5hbWUsIGlzVGVzdE1vZGUpIHtcbiAgICBpZiAoaXNUZXN0TW9kZSkge1xuICAgICAgICBzd2l0Y2gobWVyY2hhbnRIb3N0bmFtZSl7XG4gICAgICAgICAgICBjYXNlICdzdG9yZS5sb2NhbCc6XG4gICAgICAgICAgICBjYXNlICd3b28uc3RvcmUubG9jYWwnOlxuICAgICAgICAgICAgICAgIHJldHVybiAnaHR0cHM6Ly9kZXYucGVhY2hwYXkubG9jYWwvJztcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgcmV0dXJuICdodHRwczovL2Rldi5wZWFjaHBheS5hcHAvJztcbiAgICAgICAgfVxuICAgIH1cbiAgICBzd2l0Y2gobWVyY2hhbnRIb3N0bmFtZSl7XG4gICAgICAgIGNhc2UgJ2xvY2FsaG9zdCc6XG4gICAgICAgIGNhc2UgJzEyNy4wLjAuMSc6XG4gICAgICAgIGNhc2UgJ3dvby5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICd0aGVtZTEucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAndGhlbWUyLnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ3RoZW1lMy5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICd0aGVtZTQucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAndGhlbWU1LnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ3FhLnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ2RlbW8ucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgICAgIHJldHVybiAnaHR0cHM6Ly9kZXYucGVhY2hwYXkuYXBwLyc7XG4gICAgICAgIGNhc2UgJ3N0b3JlLmxvY2FsJzpcbiAgICAgICAgY2FzZSAnd29vLnN0b3JlLmxvY2FsJzpcbiAgICAgICAgICAgIHJldHVybiAnaHR0cHM6Ly9wcm9kLnBlYWNocGF5LmxvY2FsLyc7XG4gICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICByZXR1cm4gJ2h0dHBzOi8vcHJvZC5wZWFjaHBheS5hcHAvJztcbiAgICB9XG59XG5mdW5jdGlvbiBnZXRPbmVDbGlja1VSTChtZXJjaGFudEhvc3RuYW1lLCBpc1Rlc3RNb2RlKSB7XG4gICAgaWYgKGlzVGVzdE1vZGUpIHtcbiAgICAgICAgc3dpdGNoKG1lcmNoYW50SG9zdG5hbWUpe1xuICAgICAgICAgICAgY2FzZSAnc3RvcmUubG9jYWwnOlxuICAgICAgICAgICAgY2FzZSAnd29vLnN0b3JlLmxvY2FsJzpcbiAgICAgICAgICAgICAgICByZXR1cm4gJ2h0dHBzOi8vZGV2LWNvbm5lY3QucGVhY2hwYXkubG9jYWwvJztcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgcmV0dXJuICdodHRwczovL2Rldi1jb25uZWN0LXYyLnBlYWNocGF5Y2hlY2tvdXQuY29tLyc7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3dpdGNoKG1lcmNoYW50SG9zdG5hbWUpe1xuICAgICAgICBjYXNlICdsb2NhbGhvc3QnOlxuICAgICAgICBjYXNlICcxMjcuMC4wLjEnOlxuICAgICAgICBjYXNlICd3b28ucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAndGhlbWUxLnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ3RoZW1lMi5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICd0aGVtZTMucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAndGhlbWU0LnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ3RoZW1lNS5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICdxYS5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICdkZW1vLnBlYWNocGF5LmFwcCc6XG4gICAgICAgICAgICByZXR1cm4gJ2h0dHBzOi8vZGV2LWNvbm5lY3QtdjIucGVhY2hwYXljaGVja291dC5jb20vJztcbiAgICAgICAgY2FzZSAnc3RvcmUubG9jYWwnOlxuICAgICAgICBjYXNlICd3b28uc3RvcmUubG9jYWwnOlxuICAgICAgICAgICAgcmV0dXJuICdodHRwczovL2Nvbm5lY3QucGVhY2hwYXkubG9jYWwvJztcbiAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgIHJldHVybiAnaHR0cHM6Ly9jb25uZWN0LXYyLnBlYWNocGF5Y2hlY2tvdXQuY29tLyc7XG4gICAgfVxufVxuZnVuY3Rpb24gZGV0ZXJtaW5lUGFnZVR5cGUoaXNDYXJ0UGFnZSwgaXNDaGVja291dFBhZ2UpIHtcbiAgICBpZiAoaXNDYXJ0UGFnZSkge1xuICAgICAgICByZXR1cm4gJ2NhcnQnO1xuICAgIH1cbiAgICBpZiAoaXNDaGVja291dFBhZ2UpIHtcbiAgICAgICAgcmV0dXJuICdjaGVja291dCc7XG4gICAgfVxuICAgIHJldHVybiAncHJvZHVjdCc7XG59XG5mdW5jdGlvbiBzeW5jRmllbGRzKGV2ZW50KSB7XG4gICAgY29uc3QgJGZvcm0gPSBldmVudC50YXJnZXQuY2xvc2VzdCgnZm9ybScpO1xuICAgIGNvbnN0IGZpZWxkUmVjb3JkID0ge307XG4gICAgZm9yIChjb25zdCAkaW5wdXQgb2YgQXJyYXkuZnJvbSgkZm9ybS5lbGVtZW50cykpe1xuICAgICAgICBmaWVsZFJlY29yZFskaW5wdXQubmFtZV0gPSAkaW5wdXQudmFsdWU7XG4gICAgfVxuICAgIHN0b3JlLmRpc3BhdGNoKHNldEV4dHJhRmllbGRzKGZpZWxkUmVjb3JkKSk7XG59XG5mdW5jdGlvbiByZW5kZXJBZGRpdGlvbmFsRmllbGRzKGZpZWxkRGF0YSwgZmllbGRPcmRlcikge1xuICAgIGlmIChmaWVsZERhdGEubGVuZ3RoID09PSAwIHx8IGZpZWxkT3JkZXIubGVuZ3RoID09PSAwKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgJHFzKCcjYWRkaXRpb25hbC1maWVsZHMtbmV3Jyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAkcXMoJyNhZGRpdGlvbmFsLWZpZWxkcy1leGlzdGluZycpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgZm9yIChjb25zdCBpIG9mIGZpZWxkT3JkZXIpe1xuICAgICAgICBpZiAoZmllbGREYXRhW2ldLmZpZWxkX2VuYWJsZSkge1xuICAgICAgICAgICAgZ2VuZXJhdGVGaWVsZHMoZmllbGREYXRhW2ldKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICAkcXMoJyNhZGRpdGlvbmFsLWZpZWxkcy1uZXcnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywgc3luY0ZpZWxkcyk7XG4gICAgJHFzKCcjYWRkaXRpb25hbC1maWVsZHMtZXhpc3RpbmcnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywgc3luY0ZpZWxkcyk7XG4gICAgc3RvcmUuc3Vic2NyaWJlKCgpPT57XG4gICAgICAgIHJlbmRlckV4dHJhRmllbGRzKFBlYWNoUGF5T3JkZXIuZXh0cmFGaWVsZHNSZWNvcmQoKSk7XG4gICAgfSk7XG59XG5mdW5jdGlvbiByZW5kZXJFeHRyYUZpZWxkcyhleHRyYUZpZWxkRGF0YSkge1xuICAgIGZvciAoY29uc3QgW2tleSwgdmFsdWVdIG9mIE9iamVjdC5lbnRyaWVzKGV4dHJhRmllbGREYXRhKSl7XG4gICAgICAgICRxc0FsbChgW25hbWU9XCIke2tleX1cIl0uZXh0cmEtZmllbGRgLCAoJGVsZW1lbnQpPT4kZWxlbWVudC52YWx1ZSA9IHZhbHVlXG4gICAgICAgICk7XG4gICAgfVxufVxuZnVuY3Rpb24gZ2VuZXJhdGVGaWVsZHMoZmllbGREYXRhKSB7XG4gICAgY29uc3QgZmllbGQgPSAobG9jYXRpb24pPT4nPGRpdiBjbGFzcz1cIm5ldy1maWVsZFwiPicgKyBnZW5lcmF0ZUZpZWxkRWxlbWVudChsb2NhdGlvbiwgZmllbGREYXRhKSArICc8L2Rpdj4nXG4gICAgO1xuICAgIGNvbnN0IG5ld1BhZ2VFbGVtZW50cyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJyNhZGRpdGlvbmFsLWZpZWxkcy1uZXcnKTtcbiAgICBjb25zdCBleHNpdFBhZ2VFbGVtZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignI2FkZGl0aW9uYWwtZmllbGRzLWV4aXN0aW5nJyk7XG4gICAgaWYgKG5ld1BhZ2VFbGVtZW50cykge1xuICAgICAgICBuZXdQYWdlRWxlbWVudHMuaW5uZXJIVE1MICs9IGZpZWxkKCctbmV3Jyk7XG4gICAgfVxuICAgIGlmIChleHNpdFBhZ2VFbGVtZW50KSB7XG4gICAgICAgIGV4c2l0UGFnZUVsZW1lbnQuaW5uZXJIVE1MICs9IGZpZWxkKCctZXhpc3RpbmcnKTtcbiAgICB9XG59XG5mdW5jdGlvbiBnZW5lcmF0ZUZpZWxkRWxlbWVudChsb2NhdGlvbjEsIGZpZWxkRGF0YSkge1xuICAgIGxldCBlbGVtZW50U3RyaW5nID0gJyc7XG4gICAgY29uc3Qgb3B0aW9uYWwgPSAnPHNwYW4gY2xhc3M9XCJvcHRpb25hbFwiPiAob3B0aW9uYWwpIDwvc3Bhbj4nO1xuICAgIGNvbnN0IHJlcXVpcmVkID0gJzxhYmJyIGNsYXNzPVwicmVxdWlyZWRcIiB0aXRsZT1cInJlcXVpcmVkXCIgc3R5bGU9XCJjb2xvcjpyZWQ7XCI+KjwvYWJicj4nO1xuICAgIGNvbnN0IGxhYmVsQnVpbGRlciA9IChsb2NhdGlvbik9PmBcblx0XHQ8bGFiZWwgZm9yPVwiJHtmaWVsZERhdGEuZmllbGRfbmFtZX0ke2xvY2F0aW9ufVwiIGNsYXNzPVwiZm9ybS1sYWJlbC0ke2ZpZWxkRGF0YS50eXBlX2xpc3R9XCIgPmAgKyBgJHtmaWVsZERhdGEuZmllbGRfbGFiZWx9YCArIChmaWVsZERhdGEuZmllbGRfcmVxdWlyZWQgPyByZXF1aXJlZCA6IG9wdGlvbmFsKSArICc8L2xhYmVsPidcbiAgICA7XG4gICAgY29uc3QgaW5wdXRCdWlsZGVyID0gKGxvY2F0aW9uKT0+YDxpbnB1dCB0eXBlPSR7ZmllbGREYXRhLnR5cGVfbGlzdH0gXG5cdFx0XHRuYW1lPSR7ZmllbGREYXRhLmZpZWxkX25hbWV9IFxuXHRcdFx0aWQ9XCIke2ZpZWxkRGF0YS5maWVsZF9uYW1lfSR7bG9jYXRpb259XCJcblx0XHRcdHBsYWNlaG9sZGVyPVwiIFwiXG5cdFx0XHR2YWx1ZT1cIiR7ZmllbGREYXRhLmZpZWxkX2RlZmF1bHR9XCIgXG5cdFx0XHRjbGFzcz1cImlucHV0LWJveC0ke2ZpZWxkRGF0YS50eXBlX2xpc3R9IGV4dHJhLWZpZWxkXCJgICsgKGZpZWxkRGF0YS5maWVsZF9yZXF1aXJlZCA/ICdyZXF1aXJlZCcgOiAnJykgKyAnLz4nXG4gICAgO1xuICAgIGlmIChmaWVsZERhdGEudHlwZV9saXN0ID09PSAndGV4dCcpIHtcbiAgICAgICAgZWxlbWVudFN0cmluZyA9IGlucHV0QnVpbGRlcihsb2NhdGlvbjEpICsgKGZpZWxkRGF0YS5maWVsZF9sYWJlbCA/IGxhYmVsQnVpbGRlcihsb2NhdGlvbjEpIDogJycpO1xuICAgICAgICByZXR1cm4gZWxlbWVudFN0cmluZztcbiAgICB9XG4gICAgcmV0dXJuIGVsZW1lbnRTdHJpbmc7XG59XG5mdW5jdGlvbiBjb2xsZWN0QWRkaXRpb25hbEZpZWxkRGF0YShmaWVsZERhdGEsIGZpZWxkT3JkZXIpIHtcbiAgICBjb25zdCBmaWVsZERhdGFBcnJheSA9IFtdO1xuICAgIGZvciAoY29uc3Qgb3JkZXJOdW1iZXIgb2YgZmllbGRPcmRlcil7XG4gICAgICAgIGlmIChmaWVsZERhdGFbb3JkZXJOdW1iZXJdLmZpZWxkX2VuYWJsZSAmJiAkcXMoYCMke2ZpZWxkRGF0YVtvcmRlck51bWJlcl0uZmllbGRfbmFtZX0tZXhpc3RpbmdgKT8udmFsdWUpIHtcbiAgICAgICAgICAgIGNvbnN0IHRlbXBvcmFyeURhdGEgPSB7XG4gICAgICAgICAgICAgICAgbmFtZTogJydcbiAgICAgICAgICAgIH07XG4gICAgICAgICAgICB0ZW1wb3JhcnlEYXRhLmxhYmVsID0gZmllbGREYXRhW29yZGVyTnVtYmVyXS5maWVsZF9sYWJlbDtcbiAgICAgICAgICAgIHRlbXBvcmFyeURhdGEubmFtZSA9IGZpZWxkRGF0YVtvcmRlck51bWJlcl0uZmllbGRfbmFtZTtcbiAgICAgICAgICAgIHRlbXBvcmFyeURhdGEudmFsdWUgPSAkcXMoYCMke2ZpZWxkRGF0YVtvcmRlck51bWJlcl0uZmllbGRfbmFtZX0tZXhpc3RpbmdgKT8udmFsdWU7XG4gICAgICAgICAgICBmaWVsZERhdGFBcnJheS5wdXNoKHRlbXBvcmFyeURhdGEpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJldHVybiBmaWVsZERhdGFBcnJheTtcbn1cbmZ1bmN0aW9uIGNoZWNrUmVxdWlyZWRGaWVsZHMoKSB7XG4gICAgaWYgKEVudmlyb25tZW50LmN1c3RvbWVyLmV4aXN0aW5nKCkpIHtcbiAgICAgICAgcmV0dXJuICRxcygnI2FkZGl0aW9uYWwtZmllbGRzLWV4aXN0aW5nJyk/LnJlcG9ydFZhbGlkaXR5KCkgPz8gZmFsc2U7XG4gICAgfVxuICAgIHJldHVybiAkcXMoJyNhZGRpdGlvbmFsLWZpZWxkcy1uZXcnKT8ucmVwb3J0VmFsaWRpdHkoKSA/PyBmYWxzZTtcbn1cbmZ1bmN0aW9uIGNhcHR1cmVTZW50cnlFeGNlcHRpb24oZXJyb3IsIGV4dHJhLCBmaW5nZXJwcmludCkge1xuICAgIHRyeSB7XG4gICAgICAgIFNlbnRyeS53aXRoU2NvcGUoKHNjb3BlKT0+e1xuICAgICAgICAgICAgaWYgKGV4dHJhKSB7XG4gICAgICAgICAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgICAgICAgICAgT2JqZWN0LmVudHJpZXMoZXh0cmEpLm1hcCgoW2tleSwgdmFsdWVdKT0+c2NvcGUuc2V0RXh0cmEoa2V5LCB2YWx1ZSlcbiAgICAgICAgICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICAgICB9IGNhdGNoICB7fVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKGZpbmdlcnByaW50KSB7XG4gICAgICAgICAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgICAgICAgICAgc2NvcGUuc2V0RmluZ2VycHJpbnQoZmluZ2VycHJpbnQpO1xuICAgICAgICAgICAgICAgIH0gY2F0Y2ggIHt9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBTZW50cnkuY2FwdHVyZUV4Y2VwdGlvbihlcnJvcik7XG4gICAgICAgIH0pO1xuICAgIH0gY2F0Y2ggIHt9XG59XG5mdW5jdGlvbiBnZXRPcmRlclNlcnZpY2UoKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgcGxhY2VPcmRlcixcbiAgICAgICAgc2V0T3JkZXJTdGF0dXMsXG4gICAgICAgIHNldFBheW1lbnRTdGF0dXM6IHJlY29yZFN1Y2Nlc3NmdWxQYXltZW50LFxuICAgICAgICBkZXByZWNhdGVkOiB7XG4gICAgICAgICAgICBwbGFjZU9yZGVyOiBsZWdhY3lQbGFjZU9yZGVyLFxuICAgICAgICAgICAgc2V0T3JkZXJTdGF0dXM6IGxlZ2FjeVNldE9yZGVyU3RhdHVzXG4gICAgICAgIH1cbiAgICB9O1xufVxuZnVuY3Rpb24gaW5pdFNoaXBwaW5nKG1lc3NhZ2UpIHtcbiAgICBpbml0U2hpcHBpbmdFdmVudHMoKTtcbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVNZXJjaGFudEdlbmVyYWxDb25maWcoe1xuICAgICAgICAuLi5zdG9yZS5nZXRTdGF0ZSgpLm1lcmNoYW50Q29uZmlndXJhdGlvbi5nZW5lcmFsLFxuICAgICAgICB3Y0xvY2F0aW9uSW5mb0RhdGE6IG1lc3NhZ2UucGhwRGF0YS53Y19sb2NhdGlvbl9pbmZvXG4gICAgfSkpO1xuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZU1lcmNoYW50U2hpcHBpbmdDb25maWcoe1xuICAgICAgICBzaGlwcGluZ1pvbmVzOiBOdW1iZXIucGFyc2VJbnQobWVzc2FnZS5waHBEYXRhLm51bV9zaGlwcGluZ196b25lcylcbiAgICB9KSk7XG59XG5hc3luYyBmdW5jdGlvbiBzZXRPcmRlclN0YXR1cyhvcmRlciwgc3RhdHVzLCBvcHRpb25zKSB7XG4gICAgaWYgKCFHTE9CQUwucGhwRGF0YSkge1xuICAgICAgICByZXR1cm4gJyc7XG4gICAgfVxuICAgIGNvbnN0IHJlcXVlc3QgPSB7XG4gICAgICAgIHNlc3Npb246IHtcbiAgICAgICAgICAgIGlkOiBQZWFjaFBheU9yZGVyLnNlc3Npb25JZCgpXG4gICAgICAgIH0sXG4gICAgICAgIG9yZGVyOiB7XG4gICAgICAgICAgICBpZDogb3JkZXIub3JkZXJJRCxcbiAgICAgICAgICAgIHN0YXR1cyxcbiAgICAgICAgICAgIG1lc3NhZ2U6IG9wdGlvbnMubWVzc2FnZSA/PyAnJyxcbiAgICAgICAgICAgIHBheW1lbnRNZXRob2Q6IFBlYWNoUGF5Q3VzdG9tZXIucHJlZmVycmVkUGF5bWVudE1ldGhvZCgpLFxuICAgICAgICAgICAgc3RyaXBlQ3VzdG9tZXJJZDogUGVhY2hQYXlDdXN0b21lci5wcmVmZXJyZWRQYXltZW50TWV0aG9kKCkgPT09ICdzdHJpcGUnID8gb3B0aW9ucy5zdHJpcGVDdXN0b21lcklkIDogdW5kZWZpbmVkLFxuICAgICAgICAgICAgcGF5cGFsVHJhbnNhY3Rpb25JZDogUGVhY2hQYXlDdXN0b21lci5wcmVmZXJyZWRQYXltZW50TWV0aG9kKCkgPT09ICdwYXlwYWwnID8gb3B0aW9ucy5wYXlwYWxUcmFuc2FjdGlvbklkIDogdW5kZWZpbmVkLFxuICAgICAgICAgICAgc3RyaXBlVHJhbnNhY3Rpb25JZDogUGVhY2hQYXlDdXN0b21lci5wcmVmZXJyZWRQYXltZW50TWV0aG9kKCkgPT09ICdzdHJpcGUnID8gb3B0aW9ucy5zdHJpcGVUcmFuc2FjdGlvbklkIDogdW5kZWZpbmVkXG4gICAgICAgIH1cbiAgICB9O1xuICAgIGlmIChhd2FpdCBmZXRjaEhvc3RXaW5kb3dEYXRhKCdwcC1zZXQtb3JkZXItc3RhdHVzJywgcmVxdWVzdCkpIHtcbiAgICAgICAgcmV0dXJuIHdjT3JkZXJSZWNlaXZlZFVSTFdpdGhQYXJhbWV0ZXJzKEdMT0JBTC5waHBEYXRhLndjX29yZGVyX3JlY2VpdmVkX3VybCwgb3JkZXIsIE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpKTtcbiAgICB9XG4gICAgcmV0dXJuICcnO1xufVxuYXN5bmMgZnVuY3Rpb24gcGxhY2VPcmRlcigpIHtcbiAgICBjb25zdCByZXF1ZXN0TWVzc2FnZSA9IHtcbiAgICAgICAgc2Vzc2lvbjoge1xuICAgICAgICAgICAgaWQ6IFBlYWNoUGF5T3JkZXIuc2Vzc2lvbklkKClcbiAgICAgICAgfSxcbiAgICAgICAgb3JkZXI6IHtcbiAgICAgICAgICAgIHBheW1lbnRNZXRob2Q6IFBlYWNoUGF5Q3VzdG9tZXIucHJlZmVycmVkUGF5bWVudE1ldGhvZCgpLFxuICAgICAgICAgICAgYmlsbGluZ0FkZHJlc3M6IFBlYWNoUGF5Q3VzdG9tZXIuYmlsbGluZ0FkZHJlc3MoKSxcbiAgICAgICAgICAgIHNoaXBwaW5nQWRkcmVzczogUGVhY2hQYXlDdXN0b21lci5zaGlwcGluZ0FkZHJlc3MoKSxcbiAgICAgICAgICAgIHNoaXBwaW5nTWV0aG9kczogUGVhY2hQYXlPcmRlci5jb2xsZWN0U2VsZWN0ZWRTaGlwcGluZygpLFxuICAgICAgICAgICAgZGVsaXZlcnlEYXRlOiBjb2xsZWN0RGVsaXZlcnlEYXRlKCksXG4gICAgICAgICAgICBtZXJjaGFudEN1c3RvbWVyQWNjb3VudFBhc3N3b3JkOiAnJyxcbiAgICAgICAgICAgIHZhdE51bTogJycsXG4gICAgICAgICAgICB2YXRTZWxmVmVyaWZ5OiAnJyxcbiAgICAgICAgICAgIGN1c3RvbWVyT3JkZXJOb3RlczogY29sbGVjdE9yZGVyTm90ZXMoKVxuICAgICAgICB9XG4gICAgfTtcbiAgICBpZiAoc2hvdWxkU2hvd01lcmNoYW50Q3VzdG9tZXJBY2NvdW50UGFzc3dvcmRGaWVsZCgpKSB7XG4gICAgICAgIHJlcXVlc3RNZXNzYWdlLm9yZGVyLm1lcmNoYW50Q3VzdG9tZXJBY2NvdW50UGFzc3dvcmQgPSBnZXRNZXJjaGFudEN1c3RvbWVyQWNjb3VudFBhc3N3b3JkVmFsdWUoKTtcbiAgICB9XG4gICAgaWYgKEdMT0JBTC5waHBEYXRhPy52YXRfcmVxdWlyZWQpIHtcbiAgICAgICAgcmVxdWVzdE1lc3NhZ2Uub3JkZXIudmF0TnVtID0gZ2V0VmF0TnVtYmVyKCk7XG4gICAgfVxuICAgIGlmIChHTE9CQUwucGhwRGF0YT8udmF0X3NlbGZfdmVyaWZ5KSB7XG4gICAgICAgIHJlcXVlc3RNZXNzYWdlLm9yZGVyLnZhdFNlbGZWZXJpZnkgPSBnZXRWZXJpZnkoKTtcbiAgICB9XG4gICAgcmV0dXJuIGF3YWl0IGZldGNoSG9zdFdpbmRvd0RhdGEoJ3BwLXBsYWNlLW9yZGVyJywgcmVxdWVzdE1lc3NhZ2UpO1xufVxuZnVuY3Rpb24gbGVnYWN5UGxhY2VPcmRlcihpc1BheXBhbCkge1xuICAgIGlmIChHTE9CQUw/LnBocERhdGE/LnBsdWdpbl93b29jb21tZXJjZV9vcmRlcl9kZWxpdmVyeV9hY3RpdmUgJiYgY2hlY2tEZWxpdmVyeURhdGVJc0VtcHR5KCkpIHtcbiAgICAgICAgcGVhY2hwYXlBbGVydCgnUGxlYXNlIHNlbGVjdCBkZWxpdmVyeSBkYXRlLicpO1xuICAgICAgICBzdG9yZS5kaXNwYXRjaChzdG9wTW9kYWxMb2FkaW5nKCkpO1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGNvbnN0IG1lc3NhZ2UgPSB7XG4gICAgICAgICdldmVudCc6ICdwbGFjZU9yZGVyRGlyZWN0bHknLFxuICAgICAgICAnc2Vzc2lvbklEJzogUGVhY2hQYXlPcmRlci5zZXNzaW9uSWQoKSxcbiAgICAgICAgJ2JpbGxpbmdBZGRyZXNzJzogUGVhY2hQYXlDdXN0b21lci5iaWxsaW5nQWRkcmVzcygpLFxuICAgICAgICAnc2hpcHBpbmdBZGRyZXNzJzogUGVhY2hQYXlDdXN0b21lci5zaGlwcGluZ0FkZHJlc3MoKSxcbiAgICAgICAgJ3NoaXBwaW5nX21ldGhvZCc6IFBlYWNoUGF5T3JkZXIuY29sbGVjdFNlbGVjdGVkU2hpcHBpbmcoKSxcbiAgICAgICAgJ2RlbGl2ZXJ5RGF0ZSc6IGNvbGxlY3REZWxpdmVyeURhdGUoKSxcbiAgICAgICAgJ2lzUHJvZHVjdFBhZ2VCdXR0b24nOiBFbnZpcm9ubWVudC5wbHVnaW4ucGFnZVR5cGUoKSA9PT0gJ3Byb2R1Y3QnLFxuICAgICAgICAnaXNQYXlwYWwnOiBpc1BheXBhbCA/PyBmYWxzZSxcbiAgICAgICAgJ21lcmNoYW50Q3VzdG9tZXJBY2NvdW50UGFzc3dvcmQnOiAnJyxcbiAgICAgICAgJ3ZhdE51bSc6ICcnLFxuICAgICAgICAnc2VsZlZlcmlmeSc6ICcnLFxuICAgICAgICAnY3VzdG9tZXJPcmRlck5vdGVzJzogY29sbGVjdE9yZGVyTm90ZXMoKSxcbiAgICAgICAgJ2FkZGl0aW9uYWxGaWVsZHMnOiBGZWF0dXJlLmVuYWJsZWQoRmVhdHVyZUZsYWcuQURESVRJT05BTF9GSUVMRFMpID8gY29sbGVjdEFkZGl0aW9uYWxGaWVsZERhdGEoR0xPQkFMLnBocERhdGE/LmFkZGl0aW9uYWxfZmllbGRzID8/IFtdLCBHTE9CQUwucGhwRGF0YT8uYWRkaXRpb25hbF9maWVsZHNfb3JkZXIgPz8gW10pIDogW10sXG4gICAgICAgICd1cHNlbGxfaXRlbXMnOiBHTE9CQUwubGlua2VkUHJvZHVjdHNJZHNcbiAgICB9O1xuICAgIGlmIChzaG91bGRTaG93TWVyY2hhbnRDdXN0b21lckFjY291bnRQYXNzd29yZEZpZWxkKCkpIHtcbiAgICAgICAgaWYgKCF2YWxpZGF0ZU1lcmNoYW50Q3VzdG9tZXJQYXNzd29yZEZpZWxkKCkpIHtcbiAgICAgICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgbWVzc2FnZS5tZXJjaGFudEN1c3RvbWVyQWNjb3VudFBhc3N3b3JkID0gZ2V0TWVyY2hhbnRDdXN0b21lckFjY291bnRQYXNzd29yZFZhbHVlKCk7XG4gICAgfVxuICAgIGlmIChHTE9CQUwucGhwRGF0YT8udmF0X3JlcXVpcmVkKSB7XG4gICAgICAgIG1lc3NhZ2UudmF0TnVtID0gZ2V0VmF0TnVtYmVyKCk7XG4gICAgfVxuICAgIGlmIChHTE9CQUwucGhwRGF0YT8udmF0X3NlbGZfdmVyaWZ5KSB7XG4gICAgICAgIG1lc3NhZ2Uuc2VsZlZlcmlmeSA9IGdldFZlcmlmeSgpO1xuICAgIH1cbiAgICB3aW5kb3cucGFyZW50LnBvc3RNZXNzYWdlKG1lc3NhZ2UsICcqJyk7XG59XG5mdW5jdGlvbiB3Y09yZGVyUmVjZWl2ZWRVUkxXaXRoUGFyYW1ldGVycyh3Y09yZGVyUmVjZWl2ZWRVUkwsIG9yZGVyLCBkb21haW4pIHtcbiAgICBpZiAoIUdMT0JBTC5waHBEYXRhKSB7XG4gICAgICAgIHJldHVybiAnJztcbiAgICB9XG4gICAgY29uc3QgcyA9IGRvbWFpbiA9PT0gJ2xvY2FsaG9zdCcgPyAnJyA6ICdzJztcbiAgICBjb25zdCBwb3J0ID0gZG9tYWluID09PSAnbG9jYWxob3N0JyA/ICc6ODAwMCcgOiAnJztcbiAgICBjb25zdCB1cmwgPSB3Y09yZGVyUmVjZWl2ZWRVUkwgPyB3Y09yZGVyUmVjZWl2ZWRVUkwgOiBgaHR0cCR7c306Ly8ke2RvbWFpbn0ke3BvcnR9L2NoZWNrb3V0L29yZGVyLXJlY2VpdmVkYDtcbiAgICBjb25zdCBvcmRlcklEID0gb3JkZXIuaWQgfHwgb3JkZXIuZGV0YWlscy5pZDtcbiAgICBjb25zdCBrZXkgPSBvcmRlci5vcmRlcl9rZXkgfHwgb3JkZXIuZGV0YWlscy5vcmRlcl9rZXk7XG4gICAgaWYgKEdMT0JBTC5waHBEYXRhLnBsdWdpbl93b29fdGhhbmtfeW91X3BhZ2VfbmV4dG1vdmVfbGl0ZV9hY3RpdmUgfHwgZG9tYWluID09PSAndWJlcmJyYWNlbGV0cy5jb20nKSB7XG4gICAgICAgIHJldHVybiBgJHt1cmwucmVwbGFjZSgnL2NoZWNrb3V0JywgJycpfS90aGFuay15b3UvP29yZGVyX2lkPSR7b3JkZXJJRH0ma2V5PSR7a2V5fWA7XG4gICAgfVxuICAgIGlmIChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSA9PT0gJ3JhcGlkZmlyZXN1cHBsaWVzLmNvLnVrJykge1xuICAgICAgICByZXR1cm4gYGh0dHAke3N9Oi8vJHtkb21haW59JHtwb3J0fS90aGFuay15b3UtZm9yLXB1cmNoYXNpbmctZnJvbS11cy9gO1xuICAgIH1cbiAgICByZXR1cm4gYCR7dXJsfS8ke29yZGVySUR9Lz9rZXk9JHtrZXl9YDtcbn1cbmFzeW5jIGZ1bmN0aW9uIHJlcXVlc3RDYXJ0Q2FsY3VsYXRpb24oaW5pdGlhbCA9IGZhbHNlKSB7XG4gICAgY29uc3QgcmVxdWVzdERhdGEgPSBpbml0aWFsID8ge1xuICAgICAgICBpbml0aWFsOiB0cnVlXG4gICAgfSA6IHtcbiAgICAgICAgb3JkZXI6IHtcbiAgICAgICAgICAgIHNlbGVjdGVkX3NoaXBwaW5nOiBQZWFjaFBheU9yZGVyLmNvbGxlY3RTZWxlY3RlZFNoaXBwaW5nKCksXG4gICAgICAgICAgICBzaGlwcGluZ19sb2NhdGlvbjogUGVhY2hQYXlDdXN0b21lci5zaG9ydEFkZHJlc3MoKVxuICAgICAgICB9XG4gICAgfTtcbiAgICB0cnkge1xuICAgICAgICBjb25zdCByZXNwb25zZSA9IGF3YWl0IGZldGNoSG9zdFdpbmRvd0RhdGEoJ3BwLWNhbGN1bGF0ZS1jYXJ0JywgcmVxdWVzdERhdGEpO1xuICAgICAgICBjb25zdW1lQ2FydENhbGN1bGF0aW9uUmVzcG9uc2UocmVzcG9uc2UpO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgQ2FydCBjYWxjdWxhdGlvbiBWMiBmYWlsZWQgb24gJHtNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKX0uIEVycm9yOiAke2Vycm9yLm1lc3NhZ2V9YCkpO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gaW5pdFNoaXBwaW5nRXZlbnRzKCkge1xuICAgIHN0b3JlLnN1YnNjcmliZShyZW5kZXJTaGlwcGluZyk7XG4gICAgb25XaW5kb3dNZXNzYWdlKCd2YWxpZGF0ZUFkZHJlc3NTdWNjZXNzJywgYXN5bmMgKF8pPT57XG4gICAgICAgIGF3YWl0IHJlcXVlc3RDYXJ0Q2FsY3VsYXRpb24oKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlQ3VzdG9tZXJBZGRyZXNzVmFsaWRhdGlvbih0cnVlKSk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUVudmlyb25tZW50KHtcbiAgICAgICAgICAgIG1vZGFsUGFnZVR5cGU6ICdwYXltZW50J1xuICAgICAgICB9KSk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgfSk7XG4gICAgJHFzKCcjcHAtaW5mby1mb3JtJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ3N1Ym1pdCcsIChldmVudCk9PntcbiAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgIGlmICghdmFsaWRhdGVDYXJ0SXRlbXNXaXRoQ3VzdG9tZXIoRGVmYXVsdENhcnQuY29udGVudHMoKSwgZmFsc2UpKSB7XG4gICAgICAgICAgICBzdG9yZS5kaXNwYXRjaChzdG9wTW9kYWxMb2FkaW5nKCkpO1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIHdpbmRvdy5wYXJlbnQucG9zdE1lc3NhZ2Uoe1xuICAgICAgICAgICAgZXZlbnQ6ICd2YWxpZGF0ZUFkZHJlc3MnLFxuICAgICAgICAgICAgYmlsbGluZ0FkZHJlc3M6IFBlYWNoUGF5Q3VzdG9tZXIuYmlsbGluZ0FkZHJlc3MoKVxuICAgICAgICB9LCAnKicpO1xuICAgIH0pO1xuICAgICRxcygnI3BwLXNoaXBwaW5nLW9wdGlvbnMtZXhpc3RpbmcnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywgaGFuZGxlU2hpcHBpbmdTZWxlY3Rpb25FdmVudCk7XG4gICAgJHFzKCcjcHAtc2hpcHBpbmctb3B0aW9ucycpPy5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBoYW5kbGVTaGlwcGluZ1NlbGVjdGlvbkV2ZW50KTtcbn1cbmFzeW5jIGZ1bmN0aW9uIHZhbGlkYXRlQWRkcmVzcygpIHtcbiAgICByZXR1cm4gYXdhaXQgZmV0Y2hIb3N0V2luZG93RGF0YSgncHAtdmFsaWRhdGUtYmlsbGluZy1hZGRyZXNzJywgUGVhY2hQYXlDdXN0b21lci5iaWxsaW5nQWRkcmVzcygpKTtcbn1cbmFzeW5jIGZ1bmN0aW9uIGhhbmRsZVNoaXBwaW5nU2VsZWN0aW9uRXZlbnQoZXZlbnQpIHtcbiAgICBjb25zdCAkdGFyZ2V0ID0gZXZlbnQudGFyZ2V0O1xuICAgIGNvbnN0ICR0YXJnZXRDb250YWluZXIgPSAkdGFyZ2V0LmNsb3Nlc3QoJ1tkYXRhLWNhcnQta2V5XScpO1xuICAgIGNvbnN0IHNoaXBwaW5nTWV0aG9kSWQgPSAkdGFyZ2V0LnZhbHVlO1xuICAgIGNvbnN0IGNhcnRLZXkgPSAkdGFyZ2V0Q29udGFpbmVyLmRhdGFzZXQuY2FydEtleTtcbiAgICBjb25zdCBwYWNrYWdlS2V5ID0gJHRhcmdldENvbnRhaW5lci5kYXRhc2V0LnBhY2thZ2VLZXk7XG4gICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlQ2FydFBhY2thZ2VTaGlwcGluZ01ldGhvZCh7XG4gICAgICAgIGNhcnRLZXk6IGNhcnRLZXkgPz8gJycsXG4gICAgICAgIHNoaXBwaW5nUGFja2FnZUtleTogcGFja2FnZUtleSA/PyAnJyxcbiAgICAgICAgcGFja2FnZU1ldGhvZElkOiBzaGlwcGluZ01ldGhvZElkID8/ICcnXG4gICAgfSkpO1xuICAgIHN0b3JlLmRpc3BhdGNoKHN0YXJ0TW9kYWxMb2FkaW5nKCkpO1xuICAgIGF3YWl0IHJlcXVlc3RDYXJ0Q2FsY3VsYXRpb24oKTtcbiAgICBzdG9yZS5kaXNwYXRjaChzdG9wTW9kYWxMb2FkaW5nKCkpO1xufVxuZnVuY3Rpb24gcmVuZGVyU2hpcHBpbmcoKSB7XG4gICAgcmVuZGVyT3JkZXJIZWFkZXIoRGVmYXVsdENhcnQuY29udGVudHMoKSk7XG4gICAgaWYgKGNhcnRJc1ZpcnR1YWwoRGVmYXVsdENhcnQuY29udGVudHMoKSkpIHtcbiAgICAgICAgJHFzKCcjZXhpc3RpbmctY2hlY2tvdXQtZGVsaXZlcnknKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnI2V4aXN0aW5nLWNoZWNrb3V0LWRlbGl2ZXJ5Jyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgcmVuZGVyQ2FydFNoaXBwaW5nT3B0aW9ucyhzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0cyk7XG4gICAgfVxuICAgIHJlbmRlclNoaXBwaW5nU2VjdGlvbigpO1xufVxuZnVuY3Rpb24gcmVuZGVyT3JkZXJIZWFkZXIoY2FydCkge1xuICAgIGlmIChjYXJ0SXNWaXJ0dWFsKGNhcnQpKSB7XG4gICAgICAgICRxcygnLnNoaXBwaW5nLWFkZHJlc3MtaGVhZGVyJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcuYmlsbGluZy1hZGRyZXNzLWhlYWRlcicpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgIGZvciAoY29uc3QgJGVsZW1lbnQgb2YgJHFzQWxsKCcuaGlkZS1mb3ItdmlydHVhbC1jYXJ0cycpKXtcbiAgICAgICAgICAgICRlbGVtZW50LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgICAgICBmb3IgKGNvbnN0ICRlbGVtZW50MSBvZiAkcXNBbGwoJy5zaG93LWZvci12aXJ0dWFsLWNhcnRzJykpe1xuICAgICAgICAgICAgJGVsZW1lbnQxLmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnLnNoaXBwaW5nLWFkZHJlc3MtaGVhZGVyJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcuYmlsbGluZy1hZGRyZXNzLWhlYWRlcicpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgIGZvciAoY29uc3QgJGVsZW1lbnQgb2YgJHFzQWxsKCcuaGlkZS1mb3ItdmlydHVhbC1jYXJ0cycpKXtcbiAgICAgICAgICAgICRlbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgICAgICBmb3IgKGNvbnN0ICRlbGVtZW50MiBvZiAkcXNBbGwoJy5zaG93LWZvci12aXJ0dWFsLWNhcnRzJykpe1xuICAgICAgICAgICAgJGVsZW1lbnQyLmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckNhcnRTaGlwcGluZ09wdGlvbnMoY2FsY3VsYXRlZENhcnRzKSB7XG4gICAgbGV0IHNoaXBwaW5nSFRNTCA9ICcnO1xuICAgIGZvciAoY29uc3QgW2NhcnRLZXksIGNhcnRDYWxjdWxhdGlvbl0gb2YgT2JqZWN0LmVudHJpZXMoY2FsY3VsYXRlZENhcnRzKSl7XG4gICAgICAgIGlmICghY2FydENhbGN1bGF0aW9uKSB7XG4gICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgfVxuICAgICAgICBmb3IgKGNvbnN0IFtzaGlwcGluZ1BhY2thZ2VLZXksIHNoaXBwaW5nUGFja2FnZV0gb2YgT2JqZWN0LmVudHJpZXMoY2FydENhbGN1bGF0aW9uLnBhY2thZ2VfcmVjb3JkKSl7XG4gICAgICAgICAgICBpZiAoIXNoaXBwaW5nUGFja2FnZSkge1xuICAgICAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgc2hpcHBpbmdIVE1MICs9IHJlbmRlclNoaXBwaW5nUGFja2FnZU9wdGlvbnMoY2FydEtleSwgc2hpcHBpbmdQYWNrYWdlS2V5LCBzaGlwcGluZ1BhY2thZ2UsIGNhcnRDYWxjdWxhdGlvbi5jYXJ0X21ldGEpO1xuICAgICAgICB9XG4gICAgfVxuICAgICRxcygnI3BwLXNoaXBwaW5nLW9wdGlvbnMnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgPSBzaGlwcGluZ0hUTUxcbiAgICApO1xuICAgICRxcygnI3BwLXNoaXBwaW5nLW9wdGlvbnMtZXhpc3RpbmcnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgPSBzaGlwcGluZ0hUTUxcbiAgICApO1xufVxuZnVuY3Rpb24gcmVuZGVyU2hpcHBpbmdQYWNrYWdlT3B0aW9ucyhjYXJ0S2V5LCBzaGlwcGluZ1BhY2thZ2VLZXksIHNoaXBwaW5nUGFja2FnZSwgY2FydE1ldGEpIHtcbiAgICBjb25zdCBwYWNrYWdlTWV0aG9kS2V5ID0gY2FydEtleSA9PT0gJzAnID8gc2hpcHBpbmdQYWNrYWdlS2V5IDogYCR7Y2FydEtleX1fJHtzaGlwcGluZ1BhY2thZ2VLZXl9YDtcbiAgICBjb25zdCBtZXRob2RPcHRpb25CdWlsZGVyID0gKG1ldGhvZEtleSwgbWV0aG9kLCBzZWxlY3RlZCk9PmBcbjxsYWJlbCBmb3I9XCJzaGlwcGluZ19tZXRob2RfJHtwYWNrYWdlTWV0aG9kS2V5fV8ke21ldGhvZEtleS5yZXBsYWNlKC86L2csICcnKX1cIiBzdHlsZT1cIm1hcmdpbjogMCAwIDNweCAwOyBkaXNwbGF5OiBmbGV4OyBmbGV4LWRpcmVjdGlvbjogcm93OyBjdXJzb3I6IHBvaW50ZXI7XCI+XG5cdDxpbnB1dCBzdHlsZT1cInZlcnRpY2FsLWFsaWduOiB0b3A7XCIgaWQ9XCJzaGlwcGluZ19tZXRob2RfJHtwYWNrYWdlTWV0aG9kS2V5fV8ke21ldGhvZEtleS5yZXBsYWNlKC86L2csICcnKX1cIiBuYW1lPVwic2hpcHBpbmdfbWV0aG9kWyR7cGFja2FnZU1ldGhvZEtleX1dXCIgdmFsdWU9XCIke21ldGhvZEtleX1cIiB0eXBlPVwicmFkaW9cIiAke3NlbGVjdGVkID8gJ2NoZWNrZWQnIDogJyd9IHJlcXVpcmVkPlxuXHQ8c3BhbiBzdHlsZT1cImRpc3BsYXk6IGlubGluZS1ibG9jazsgZmxleC1ncm93OiAxO1wiPiR7bWV0aG9kLnRpdGxlfTwvc3Bhbj5cblx0PHNwYW4gc3R5bGU9XCJkaXNwbGF5OiBpbmxpbmUtYmxvY2s7IG1pbi13aWR0aDogMzAlOyB0ZXh0LWFsaWduOiByaWdodDtcIiBjbGFzcz1cInNoaXBwaW5nLXByaWNlXCI+JHtmb3JtYXRDdXJyZW5jeVN0cmluZyhtZXRob2QudG90YWwpfTxzcGFuIGNsYXNzPVwibXV0ZWRcIj4ke2J1aWxkU3Vic2NyaXB0aW9uUHJpY2VNZXRhRGF0YShjYXJ0TWV0YSl9PC9zcGFuPjwvc3Bhbj5cbjwvbGFiZWw+YFxuICAgIDtcbiAgICBjb25zdCBwYWNrYWdlTmFtZUhUTUwgPSBgPGg0IGNsYXNzPVwic2hpcHBpbmctaGVhZGVyIGNvbG9yLWNoYW5nZS10ZXh0XCI+JHtzaGlwcGluZ1BhY2thZ2UucGFja2FnZV9uYW1lID8/IGdldExvY2FsZVRleHQoJ3NoaXBwaW5nJyl9PC9oND5gO1xuICAgIGNvbnN0IHBhY2thZ2VNZXRob2RPcHRpb25zSFRNTCA9IE9iamVjdC5lbnRyaWVzKHNoaXBwaW5nUGFja2FnZS5tZXRob2RzKS5tYXAoKFtzaGlwcGluZ01ldGhvZEtleSwgc2hpcHBpbmdNZXRob2RdKT0+c2hpcHBpbmdNZXRob2QgPyBtZXRob2RPcHRpb25CdWlsZGVyKHNoaXBwaW5nTWV0aG9kS2V5LCBzaGlwcGluZ01ldGhvZCwgc2hpcHBpbmdQYWNrYWdlLnNlbGVjdGVkX21ldGhvZCA9PT0gc2hpcHBpbmdNZXRob2RLZXkpIDogJydcbiAgICApLmpvaW4oJycpO1xuICAgIHJldHVybiBgXG48ZGl2IGRhdGEtY2FydC1rZXk9XCIke2NhcnRLZXl9XCIgZGF0YS1wYWNrYWdlLWtleT1cIiR7c2hpcHBpbmdQYWNrYWdlS2V5fVwiPlxuXHQke3BhY2thZ2VOYW1lSFRNTH1cblx0JHtwYWNrYWdlTWV0aG9kT3B0aW9uc0hUTUx9XG48L2Rpdj5gO1xufVxuZnVuY3Rpb24gc2hpcHBpbmdJc1ZhbGlkKCkge1xuICAgIGlmIChjYXJ0SXNWaXJ0dWFsKERlZmF1bHRDYXJ0LmNvbnRlbnRzKCkpKSB7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cbiAgICBpZiAoTWVyY2hhbnRDb25maWd1cmF0aW9uLnNoaXBwaW5nLnNoaXBwaW5nWm9uZXMoKSA9PT0gMCkge1xuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG4gICAgaWYgKCFDYXJ0cy5hbnlTaGlwcGluZ01ldGhvZHNBdmFpbGFibGUoKSkge1xuICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuICAgIHJldHVybiB0cnVlO1xufVxuZnVuY3Rpb24gY29uc3VtZUNhcnRDYWxjdWxhdGlvblJlc3BvbnNlKHJlc3BvbnNlKSB7XG4gICAgaWYgKHJlc3BvbnNlLmRhdGEpIHtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc2V0T3JkZXJFcnJvcignJykpO1xuICAgICAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDYXJ0Q2FsY3VsYXRpb24ocmVzcG9uc2UuZGF0YS5jYXJ0X2NhbGN1bGF0aW9uX3JlY29yZCkpO1xuICAgICAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDdXN0b21lclNoaXBwaW5nU2hvcnRBZGRyZXNzKHJlc3BvbnNlLmRhdGEuc2hpcHBpbmdfbG9jYXRpb24pKTtcbiAgICAgICAgaWYgKERlZmF1bHRDYXJ0LmNvbnRlbnRzKCkubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICBzdG9yZS5kaXNwYXRjaChzZXRPcmRlckVycm9yKGA8c3Bhbj4ke2dldExvY2FsZVRleHQoJ2VtcHR5LWNhcnQnKX08L3NwYW4+YCkpO1xuICAgICAgICB9IGVsc2UgaWYgKCFzaGlwcGluZ0lzVmFsaWQoKSkge1xuICAgICAgICAgICAgc3RvcmUuZGlzcGF0Y2goc2V0T3JkZXJFcnJvcihgPHNwYW4+JHtnZXRMb2NhbGVUZXh0KCduby1zaGlwJyl9PC9zcGFuPmApKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIHByb2R1Y3RCdW5kbGVzUHJvZHVjdFBhZ2UoKSB7XG4gICAgcmV0dXJuIEdMT0JBTD8ucGhwRGF0YT8ucGx1Z2luX3dvb2NvbW1lcmNlX3Byb2R1Y3RfYnVuZGxlc19hY3RpdmUgPT09ICcxJyAmJiAhKEVudmlyb25tZW50LnBsdWdpbi5wYWdlVHlwZSgpID09PSAnY2FydCcpO1xufVxuZnVuY3Rpb24gcmVjb3JkU3VjY2Vzc2Z1bFBheW1lbnQoc2Vzc2lvbklELCBjbGVhclNlc3Npb24pIHtcbiAgICByZXR1cm4gZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnYXBpL3YxL3Nlc3Npb24vcGF5L3JlY29yZCcsIHtcbiAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbidcbiAgICAgICAgfSxcbiAgICAgICAgYm9keTogSlNPTi5zdHJpbmdpZnkoe1xuICAgICAgICAgICAgc2Vzc2lvbklELFxuICAgICAgICAgICAgY2xlYXJTZXNzaW9uOiBjbGVhclNlc3Npb24gfHwgZmFsc2VcbiAgICAgICAgfSlcbiAgICB9KTtcbn1cbmZ1bmN0aW9uIGxlZ2FjeVNldE9yZGVyU3RhdHVzKG9yZGVyLCB7IHN0YXR1cyAsIG1lc3NhZ2UgLCBwYXltZW50VHlwZSAsIHRyYW5zYWN0aW9uSUQgIH0pIHtcbiAgICBpZiAoIUdMT0JBTC5waHBEYXRhKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgd2luZG93LnBhcmVudC5wb3N0TWVzc2FnZSh7XG4gICAgICAgIGV2ZW50OiAnc2V0T3JkZXJTdGF0dXMnLFxuICAgICAgICBvcmRlcklEOiBvcmRlci5vcmRlcklELFxuICAgICAgICBzdGF0dXMsXG4gICAgICAgIG1lc3NhZ2UsXG4gICAgICAgIHBheW1lbnRUeXBlLFxuICAgICAgICB0cmFuc2FjdGlvbklELFxuICAgICAgICBjdXN0b21lclN0cmlwZUlkOiBQZWFjaFBheUN1c3RvbWVyLnN0cmlwZUlkKCksXG4gICAgICAgIHJlZGlyZWN0VVJMOiB3Y09yZGVyUmVjZWl2ZWRVUkxXaXRoUGFyYW1ldGVycyhHTE9CQUwucGhwRGF0YS53Y19vcmRlcl9yZWNlaXZlZF91cmwsIG9yZGVyLCBNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSlcbiAgICB9LCAnKicpO1xufVxuZnVuY3Rpb24gcmVuZGVyU2hpcHBpbmdTZWN0aW9uKCkge1xuICAgIGlmIChQZWFjaFBheU9yZGVyLmN1c3RvbWVyQWRkcmVzc1ZhbGlkYXRlZCgpKSB7XG4gICAgICAgICRxcygnI3BwLXNoaXBwaW5nLXBheW1lbnQnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnI3BwLXNoaXBwaW5nLXBheW1lbnQnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRDdXN0b21lcihtZXNzYWdlKSB7XG4gICAgaW5pdEN1c3RvbWVyRXZlbnRzKCk7XG4gICAgcmVuZGVyQ291bnRyeUFuZFN0YXRlTGlzdChtZXNzYWdlLnBocERhdGEud2NfbG9jYXRpb25faW5mbyk7XG59XG5mdW5jdGlvbiBpbml0Q3VzdG9tZXJFdmVudHMoKSB7XG4gICAgJHFzKCcjY291bnRyeScpPy5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCByZW5kZXJDb3JyZWN0UHJvdmluY2VGaWVsZCk7XG4gICAgJHFzKCcjcHAtaW5mby1mb3JtJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsICgpPT5zZXRUaW1lb3V0KHN5bmNDdXN0b21lckZpZWxkQ2hhbmdlcylcbiAgICApO1xuICAgIGxldCBwcmV2aW91c0N1c3RvbWVyRGF0YSA9ICcnO1xuICAgIHN0b3JlLnN1YnNjcmliZSgoKT0+e1xuICAgICAgICBjb25zdCBjdXN0b21lciA9IFBlYWNoUGF5Q3VzdG9tZXIuZGF0YSgpO1xuICAgICAgICBpZiAoRW52aXJvbm1lbnQubW9kYWxVSS5wYWdlKCkgPT09ICdpbmZvJykge1xuICAgICAgICAgICAgY29uc3QgY3VzdG9tZXJEYXRhID0gSlNPTi5zdHJpbmdpZnkoY3VzdG9tZXIpO1xuICAgICAgICAgICAgaWYgKGN1c3RvbWVyRGF0YSAhPT0gcHJldmlvdXNDdXN0b21lckRhdGEpIHtcbiAgICAgICAgICAgICAgICBwcmV2aW91c0N1c3RvbWVyRGF0YSA9IGN1c3RvbWVyRGF0YTtcbiAgICAgICAgICAgICAgICByZW5kZXJDdXN0b21lckZpZWxkcyhjdXN0b21lcik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICAgICAgaWYgKEVudmlyb25tZW50Lm1vZGFsVUkucGFnZSgpID09PSAncGF5bWVudCcpIHtcbiAgICAgICAgICAgIHJlbmRlckN1c3RvbWVySGVhZGVyKGN1c3RvbWVyLCBFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpKTtcbiAgICAgICAgICAgIGlmIChFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpKSB7XG4gICAgICAgICAgICAgICAgcmVuZGVyRXhpc3RpbmdDdXN0b21lckNoZWNrb3V0KGN1c3RvbWVyKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH0pO1xufVxuZnVuY3Rpb24gc3luY0N1c3RvbWVyRmllbGRDaGFuZ2VzKCkge1xuICAgIGNvbnN0ICRmb3JtID0gJHFzKCcjcHAtaW5mby1mb3JtJyk7XG4gICAgaWYgKCEkZm9ybSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGNvbnN0IGZvcm1EYXRhID0gbmV3IEZvcm1EYXRhKCRmb3JtKTtcbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDdXN0b21lcih7XG4gICAgICAgIGVtYWlsOiBmb3JtRW50cnkoZm9ybURhdGEsICdlbWFpbCcpLFxuICAgICAgICBuYW1lX2ZpcnN0OiBmb3JtRW50cnkoZm9ybURhdGEsICduYW1lX2ZpcnN0JyksXG4gICAgICAgIG5hbWVfbGFzdDogZm9ybUVudHJ5KGZvcm1EYXRhLCAnbmFtZV9sYXN0JyksXG4gICAgICAgIGFkZHJlc3MxOiBmb3JtRW50cnkoZm9ybURhdGEsICdhZGRyZXNzMScpLFxuICAgICAgICBhZGRyZXNzMjogZm9ybUVudHJ5KGZvcm1EYXRhLCAnYWRkcmVzczInKSxcbiAgICAgICAgY2l0eTogZm9ybUVudHJ5KGZvcm1EYXRhLCAnY2l0eScpLFxuICAgICAgICBzdGF0ZTogZm9ybUVudHJ5KGZvcm1EYXRhLCAnc3RhdGUnKSxcbiAgICAgICAgcG9zdGFsOiBmb3JtRW50cnkoZm9ybURhdGEsICdwb3N0YWwnKSxcbiAgICAgICAgY291bnRyeTogZm9ybUVudHJ5KGZvcm1EYXRhLCAnY291bnRyeScpLFxuICAgICAgICBwaG9uZTogZm9ybUVudHJ5KGZvcm1EYXRhLCAncGhvbmUnKVxuICAgIH0pKTtcbn1cbmFzeW5jIGZ1bmN0aW9uIGxvYWRDdXN0b21lcigpIHtcbiAgICBjb25zdCBjdXN0b21lciA9IGF3YWl0IGdldEN1c3RvbWVyKCk7XG4gICAgaWYgKGN1c3RvbWVyID09PSBudWxsKSB7XG4gICAgICAgIGNvbnN0IGxvY2F0aW9uSW5mbyA9IE1lcmNoYW50Q29uZmlndXJhdGlvbi5nZW5lcmFsLndjTG9jYXRpb25JbmZvRGF0YSgpO1xuICAgICAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDdXN0b21lcih7XG4gICAgICAgICAgICAuLi5QZWFjaFBheUN1c3RvbWVyLmRhdGEoKSxcbiAgICAgICAgICAgIGNvdW50cnk6IChsb2NhdGlvbkluZm8/LmN1c3RvbWVyX2RlZmF1bHRfY291bnRyeSA/PyBsb2NhdGlvbkluZm8/LnN0b3JlX2NvdW50cnkpID8/ICcnXG4gICAgICAgIH0pKTtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDdXN0b21lcihjdXN0b21lcikpO1xuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUVudmlyb25tZW50KHtcbiAgICAgICAgY3VzdG9tZXJFeGlzdHM6IHRydWUsXG4gICAgICAgIG1vZGFsUGFnZVR5cGU6ICdwYXltZW50J1xuICAgIH0pKTtcbn1cbmFzeW5jIGZ1bmN0aW9uIHNhdmVDdXN0b21lclRvQnJvd3NlcihjdXN0b21lcklELCBicmFuZCwgbGFzdDQsIHBheW1lbnRPcHRpb24pIHtcbiAgICBjb25zdCB7IGZpcnN0TmFtZSAsIGxhc3ROYW1lICwgZW1haWwgLCBwaG9uZSAsIGFkZHJlc3MxICwgYWRkcmVzczIgLCBwb3N0YWwgLCBjaXR5ICwgc3RhdGUgLCBjb3VudHJ5ICB9ID0gUGVhY2hQYXlDdXN0b21lcjtcbiAgICBjb25zdCBjdXN0b21lciA9IHtcbiAgICAgICAgJ25hbWVfZmlyc3QnOiBmaXJzdE5hbWUoKSxcbiAgICAgICAgJ25hbWVfbGFzdCc6IGxhc3ROYW1lKCksXG4gICAgICAgICdlbWFpbCc6IGVtYWlsKCksXG4gICAgICAgICdwaG9uZSc6IHBob25lKCksXG4gICAgICAgICdhZGRyZXNzMSc6IGFkZHJlc3MxKCksXG4gICAgICAgICdhZGRyZXNzMic6IGFkZHJlc3MyKCksXG4gICAgICAgICdwb3N0YWwnOiBwb3N0YWwoKSxcbiAgICAgICAgJ2NpdHknOiBjaXR5KCksXG4gICAgICAgICdzdGF0ZSc6IHN0YXRlKCksXG4gICAgICAgICdjb3VudHJ5JzogY291bnRyeSgpLFxuICAgICAgICAnc3RyaXBlX2N1c3RvbWVyX2lkJzogY3VzdG9tZXJJRCxcbiAgICAgICAgJ2NhcmQnOiB7XG4gICAgICAgICAgICBicmFuZCxcbiAgICAgICAgICAgIGxhc3Q0XG4gICAgICAgIH0sXG4gICAgICAgICdwYXltZW50X29wdGlvbic6IHBheW1lbnRPcHRpb25cbiAgICB9O1xuICAgIGF3YWl0IHNldEN1c3RvbWVyKGN1c3RvbWVyKTtcbiAgICByZXR1cm4gY3VzdG9tZXI7XG59XG5mdW5jdGlvbiBkaXNwbGF5Q0NMb2dvKGN1c3RvbWVyKSB7XG4gICAgbGV0IGNjQnJhbmQgPSAnJztcbiAgICBzd2l0Y2goY3VzdG9tZXIuY2FyZD8uYnJhbmQpe1xuICAgICAgICBjYXNlICdWaXNhJzpcbiAgICAgICAgICAgIGNjQnJhbmQgPSAndmlzYSc7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSAnTWFzdGVyQ2FyZCc6XG4gICAgICAgICAgICBjY0JyYW5kID0gJ21hc3RlcmNhcmQnO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIGNhc2UgJ0FtZXJpY2FuIEV4cHJlc3MnOlxuICAgICAgICAgICAgY2NCcmFuZCA9ICdhbWV4JztcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlICdEaXNjb3Zlcic6XG4gICAgICAgICAgICBjY0JyYW5kID0gJ2Rpc2NvdmVyJztcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlICdEaW5lcnMgQ2x1Yic6XG4gICAgICAgICAgICBjY0JyYW5kID0gJ2RpbmVycyc7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSAnSkNCJzpcbiAgICAgICAgICAgIGNjQnJhbmQgPSAnamNiJztcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlICdVbmlvblBheSc6XG4gICAgICAgICAgICBjY0JyYW5kID0gJ3VuaW9ucGF5JztcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgY2NCcmFuZCA9ICcnO1xuICAgIH1cbiAgICAkcXMoJyNjYy0nICsgY2NCcmFuZCk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbn1cbmZ1bmN0aW9uIHJlbmRlckV4aXN0aW5nQ3VzdG9tZXJDaGVja291dChjdXN0b21lcikge1xuICAgIGlmIChjdXN0b21lci5wYXltZW50X29wdGlvbiA9PT0gJ3BheXBhbCcpIHtcbiAgICAgICAgJHFzKCcjcGF5cGFsLWJ1dHRvbi1jb250YWluZXItZXhpc3RpbmcnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICBpZiAoR0xPQkFMLnBocERhdGE/LnBheXBhbCA9PT0gJzEnKSB7XG4gICAgICAgICAgICAkcXMoJyNleGlzdGluZy1jaGVja291dC1jYXJkJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgICAgIGFkanVzdE9yZGVyU3VtbWFyeUhlaWdodCh0cnVlKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICRxcygnI2V4aXN0aW5nLWNoZWNrb3V0LWNhcmQtbnVtYmVyJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgICAgICRxcygnI2V4aXN0aW5nLWNoZWNrb3V0LW5vLWNhcmQnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICB9XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzKCcjcGF5cGFsLWJ1dHRvbi1jb250YWluZXItZXhpc3RpbmcnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICBhZGp1c3RPcmRlclN1bW1hcnlIZWlnaHQoZmFsc2UpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckNvcnJlY3RQcm92aW5jZUZpZWxkKCkge1xuICAgIGNvbnN0IG1lcmNoYW50U2hpcHBpbmcgPSBNZXJjaGFudENvbmZpZ3VyYXRpb24uZ2VuZXJhbC53Y0xvY2F0aW9uSW5mb0RhdGEoKTtcbiAgICBpZiAobWVyY2hhbnRTaGlwcGluZykge1xuICAgICAgICBjb25zdCAkY291bnRyaWVzID0gJHFzKCcjY291bnRyeScpO1xuICAgICAgICBpZiAoISRjb3VudHJpZXMpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBkZWZhdWx0T3B0aW9uID0gc3RhdGVQcm92aW5jZU9yQ291bnR5KCRjb3VudHJpZXMudmFsdWUpO1xuICAgICAgICBjb25zdCBzdGF0ZU9yUHJvdmluY2VPcHRpb25zID0gbWVyY2hhbnRTaGlwcGluZy5hbGxvd2VkX3N0YXRlc19vcl9wcm92aW5jZXNbJGNvdW50cmllcy52YWx1ZSA/PyAnJ10gPz8ge307XG4gICAgICAgIGlmIChzdGF0ZU9yUHJvdmluY2VPcHRpb25zICYmIE9iamVjdC5rZXlzKHN0YXRlT3JQcm92aW5jZU9wdGlvbnMpLmxlbmd0aCA+IDApIHtcbiAgICAgICAgICAgIGNvbnN0ICRzdGF0ZU9yUHJvdmluY2VzU2VsZWN0ID0gJHFzKCcjZHluYW1pYy1zdGF0ZXMnKTtcbiAgICAgICAgICAgIGlmICgkc3RhdGVPclByb3ZpbmNlc1NlbGVjdCkge1xuICAgICAgICAgICAgICAgICRzdGF0ZU9yUHJvdmluY2VzU2VsZWN0LmlubmVySFRNTCA9IHJlbmRlckRyb3BEb3duTGlzdChzdGF0ZU9yUHJvdmluY2VPcHRpb25zLCBkZWZhdWx0T3B0aW9uKTtcbiAgICAgICAgICAgICAgICAkc3RhdGVPclByb3ZpbmNlc1NlbGVjdC5kaXNhYmxlZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICRzdGF0ZU9yUHJvdmluY2VzU2VsZWN0LnNldEF0dHJpYnV0ZSgnbmFtZScsICdzdGF0ZScpO1xuICAgICAgICAgICAgICAgIGlmIChkZWZhdWx0T3B0aW9uID09PSBnZXRMb2NhbGVUZXh0KCdwcm92aW5jZS1zZWxlY3QnKSkge1xuICAgICAgICAgICAgICAgICAgICAkcXMoJ2xhYmVsW2Zvcj1cImR5bmFtaWMtc3RhdGVzXCJdJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBnZXRMb2NhbGVUZXh0KCdwcm92aW5jZScpXG4gICAgICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmIChkZWZhdWx0T3B0aW9uID09PSBnZXRMb2NhbGVUZXh0KCdzdGF0ZS1zZWxlY3QnKSkge1xuICAgICAgICAgICAgICAgICAgICAkcXMoJ2xhYmVsW2Zvcj1cImR5bmFtaWMtc3RhdGVzXCJdJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBnZXRMb2NhbGVUZXh0KCdzdGF0ZScpXG4gICAgICAgICAgICAgICAgICAgICk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgJHFzKCdsYWJlbFtmb3I9XCJkeW5hbWljLXN0YXRlc1wiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gZGVmYXVsdE9wdGlvblxuICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkc3RhdGVPclByb3ZpbmNlc1NlbGVjdC5yZXF1aXJlZCA9IHRydWU7XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNTZWxlY3QuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAgICAgICAgICRxcygnbGFiZWxbZm9yPVwiZHluYW1pYy1zdGF0ZXNcIl0nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY29uc3QgJHN0YXRlT3JQcm92aW5jZXNUZXh0ID0gJHFzKCcjcHJvdmluY2UnKTtcbiAgICAgICAgICAgIGlmICgkc3RhdGVPclByb3ZpbmNlc1RleHQpIHtcbiAgICAgICAgICAgICAgICAkc3RhdGVPclByb3ZpbmNlc1RleHQuZGlzYWJsZWQgPSB0cnVlO1xuICAgICAgICAgICAgICAgICRzdGF0ZU9yUHJvdmluY2VzVGV4dC5zZXRBdHRyaWJ1dGUoJ25hbWUnLCAnb2ZmJyk7XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNUZXh0LnJlcXVpcmVkID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNUZXh0LnZhbHVlID0gJyc7XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNUZXh0LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgICAgICAgICAkcXMoJ2xhYmVsW2Zvcj1cInByb3ZpbmNlXCJdJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbnN0ICRzdGF0ZU9yUHJvdmluY2VzU2VsZWN0ID0gJHFzKCcjZHluYW1pYy1zdGF0ZXMnKTtcbiAgICAgICAgICAgIGlmICgkc3RhdGVPclByb3ZpbmNlc1NlbGVjdCkge1xuICAgICAgICAgICAgICAgICRzdGF0ZU9yUHJvdmluY2VzU2VsZWN0LmRpc2FibGVkID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICAkc3RhdGVPclByb3ZpbmNlc1NlbGVjdC5zZXRBdHRyaWJ1dGUoJ25hbWUnLCAnb2ZmJyk7XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNTZWxlY3QucmVxdWlyZWQgPSBmYWxzZTtcbiAgICAgICAgICAgICAgICAkc3RhdGVPclByb3ZpbmNlc1NlbGVjdC5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgICAgICAgICAgJHFzKCdsYWJlbFtmb3I9XCJkeW5hbWljLXN0YXRlc1wiXScpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjb25zdCAkc3RhdGVPclByb3ZpbmNlc1RleHQgPSAkcXMoJyNwcm92aW5jZScpO1xuICAgICAgICAgICAgaWYgKCRzdGF0ZU9yUHJvdmluY2VzVGV4dCkge1xuICAgICAgICAgICAgICAgICRzdGF0ZU9yUHJvdmluY2VzVGV4dC5kaXNhYmxlZCA9IGZhbHNlO1xuICAgICAgICAgICAgICAgICRzdGF0ZU9yUHJvdmluY2VzVGV4dC5zZXRBdHRyaWJ1dGUoJ25hbWUnLCAnc3RhdGUnKTtcbiAgICAgICAgICAgICAgICBpZiAoZGVmYXVsdE9wdGlvbiA9PT0gZ2V0TG9jYWxlVGV4dCgncHJvdmluY2Utc2VsZWN0JykpIHtcbiAgICAgICAgICAgICAgICAgICAgJHFzKCdsYWJlbFtmb3I9XCJwcm92aW5jZVwiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gZ2V0TG9jYWxlVGV4dCgncHJvdmluY2UnKVxuICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAoZGVmYXVsdE9wdGlvbiA9PT0gZ2V0TG9jYWxlVGV4dCgnc3RhdGUtc2VsZWN0JykpIHtcbiAgICAgICAgICAgICAgICAgICAgJHFzKCdsYWJlbFtmb3I9XCJwcm92aW5jZVwiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gZ2V0TG9jYWxlVGV4dCgnc3RhdGUnKVxuICAgICAgICAgICAgICAgICAgICApO1xuICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICRxcygnbGFiZWxbZm9yPVwicHJvdmluY2VcIl0nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC50ZXh0Q29udGVudCA9IGRlZmF1bHRPcHRpb25cbiAgICAgICAgICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNUZXh0LnZhbHVlID0gJyc7XG4gICAgICAgICAgICAgICAgJHN0YXRlT3JQcm92aW5jZXNUZXh0LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgICAgICAgICAkcXMoJ2xhYmVsW2Zvcj1cInByb3ZpbmNlXCJdJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckNvdW50cnlBbmRTdGF0ZUxpc3QobWVyY2hhbnRMb2NhdGlvbkluZm8pIHtcbiAgICBpZiAoIW1lcmNoYW50TG9jYXRpb25JbmZvKSB7XG4gICAgICAgIGNvbnNvbGUud2FybignV2FybmluZzogTm8gV0MgTG9jYXRpb24gaW5mby4gUGxlYXNlIHVwZGF0ZSB0aGUgUGVhY2hQYXkgUGx1Z2luLicpO1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGNvbnN0ICRjb3VudHJpZXMgPSAkcXMoJyNjb3VudHJ5Jyk7XG4gICAgaWYgKCEkY291bnRyaWVzKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3Qgc2VsZWN0QUNvdW50cnkgPSBnZXRMb2NhbGVUZXh0KCdjb3VudHJ5Jyk7XG4gICAgY29uc3QgY291bnRyeU9wdGlvbnMgPSBtZXJjaGFudExvY2F0aW9uSW5mby5hbGxvd2VkX2NvdW50cmllcztcbiAgICAkY291bnRyaWVzLmlubmVySFRNTCA9IHJlbmRlckRyb3BEb3duTGlzdChjb3VudHJ5T3B0aW9ucywgc2VsZWN0QUNvdW50cnkpO1xuICAgIHNlbGVjdERyb3Bkb3duKCRjb3VudHJpZXMsIG1lcmNoYW50TG9jYXRpb25JbmZvLmN1c3RvbWVyX2RlZmF1bHRfY291bnRyeSB8fCBtZXJjaGFudExvY2F0aW9uSW5mby5zdG9yZV9jb3VudHJ5KTtcbiAgICBpZiAoJGNvdW50cmllcy5vcHRpb25zLmxlbmd0aCA9PT0gMikge1xuICAgICAgICAkY291bnRyaWVzLnNlbGVjdGVkSW5kZXggPSAxO1xuICAgIH1cbiAgICAkY291bnRyaWVzLmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdjaGFuZ2UnKSk7XG59XG5mdW5jdGlvbiByZW5kZXJDdXN0b21lckZpZWxkcyhjdXN0b21lcikge1xuICAgICRxcygnI3BwLWluZm8tZm9ybSBpbnB1dFtuYW1lPVwibmFtZV9maXJzdFwiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnZhbHVlID0gY3VzdG9tZXIubmFtZV9maXJzdFxuICAgICk7XG4gICAgJHFzKCcjcHAtaW5mby1mb3JtIGlucHV0W25hbWU9XCJuYW1lX2xhc3RcIl0nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC52YWx1ZSA9IGN1c3RvbWVyLm5hbWVfbGFzdFxuICAgICk7XG4gICAgJHFzKCcjcHAtaW5mby1mb3JtIGlucHV0W25hbWU9XCJlbWFpbFwiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnZhbHVlID0gY3VzdG9tZXIuZW1haWxcbiAgICApO1xuICAgICRxcygnI3BwLWluZm8tZm9ybSBpbnB1dFtuYW1lPVwicGhvbmVcIl0nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC52YWx1ZSA9IGN1c3RvbWVyLnBob25lXG4gICAgKTtcbiAgICAkcXMoJyNwcC1pbmZvLWZvcm0gaW5wdXRbbmFtZT1cImFkZHJlc3MxXCJdJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudmFsdWUgPSBjdXN0b21lci5hZGRyZXNzMVxuICAgICk7XG4gICAgJHFzKCcjcHAtaW5mby1mb3JtIGlucHV0W25hbWU9XCJhZGRyZXNzMlwiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnZhbHVlID0gY3VzdG9tZXIuYWRkcmVzczJcbiAgICApO1xuICAgICRxcygnI3BwLWluZm8tZm9ybSBpbnB1dFtuYW1lPVwicG9zdGFsXCJdJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudmFsdWUgPSBjdXN0b21lci5wb3N0YWxcbiAgICApO1xuICAgICRxcygnI3BwLWluZm8tZm9ybSBpbnB1dFtuYW1lPVwiY2l0eVwiXScsICgkZWxlbWVudCk9PiRlbGVtZW50LnZhbHVlID0gY3VzdG9tZXIuY2l0eVxuICAgICk7XG4gICAgJHFzKCcjcHAtaW5mby1mb3JtIGlucHV0W25hbWU9XCJjb3VudHJ5XCJdJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudmFsdWUgPSBjdXN0b21lci5jb3VudHJ5XG4gICAgKTtcbiAgICByZW5kZXJDb3JyZWN0UHJvdmluY2VGaWVsZCgpO1xuICAgICRxcygnI3BwLWluZm8tZm9ybSBbbmFtZT1cInN0YXRlXCJdJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudmFsdWUgPSBjdXN0b21lci5zdGF0ZVxuICAgICk7XG59XG5mdW5jdGlvbiByZW5kZXJDdXN0b21lckhlYWRlcihjdXN0b21lciwgZXhpc3RpbmdDdXN0b21lcikge1xuICAgIGlmIChleGlzdGluZ0N1c3RvbWVyKSB7XG4gICAgICAgICRxcygnI2V4aXN0aW5nLWVtYWlsJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBjdXN0b21lci5lbWFpbFxuICAgICAgICApO1xuICAgICAgICAkcXMoJyNleGlzdGluZy1uYW1lX2ZpcnN0JywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBjdXN0b21lci5uYW1lX2ZpcnN0XG4gICAgICAgICk7XG4gICAgICAgICRxcygnI2V4aXN0aW5nLW5hbWVfbGFzdCcsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gY3VzdG9tZXIubmFtZV9sYXN0XG4gICAgICAgICk7XG4gICAgICAgICRxcygnI2V4aXN0aW5nLWFkZHJlc3MxJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBjdXN0b21lci5hZGRyZXNzMVxuICAgICAgICApO1xuICAgICAgICAkcXMoJyNleGlzdGluZy1hZGRyZXNzMicsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gY3VzdG9tZXIuYWRkcmVzczIgPyAnICcgKyBjdXN0b21lci5hZGRyZXNzMiA6ICcnXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI2V4aXN0aW5nLWNpdHknLCAoJGVsZW1lbnQpPT4kZWxlbWVudC50ZXh0Q29udGVudCA9IGN1c3RvbWVyLmNpdHlcbiAgICAgICAgKTtcbiAgICAgICAgaWYgKGN1c3RvbWVyLmNvdW50cnkgPT09ICdKUCcpIHtcbiAgICAgICAgICAgIGNvbnN0IGZ1bGxTdGF0ZU5hbWUgPSBHTE9CQUwucGhwRGF0YT8ud2NfbG9jYXRpb25faW5mbz8uYWxsb3dlZF9zdGF0ZXNfb3JfcHJvdmluY2VzLkpQW2N1c3RvbWVyLnN0YXRlXTtcbiAgICAgICAgICAgICRxcygnI2V4aXN0aW5nLXN0YXRlJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBmdWxsU3RhdGVOYW1lID8/ICcnXG4gICAgICAgICAgICApO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJHFzKCcjZXhpc3Rpbmctc3RhdGUnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC50ZXh0Q29udGVudCA9IGN1c3RvbWVyLnN0YXRlXG4gICAgICAgICAgICApO1xuICAgICAgICB9XG4gICAgICAgICRxcygnI2V4aXN0aW5nLWNvdW50cnknLCAoJGVsZW1lbnQpPT4kZWxlbWVudC50ZXh0Q29udGVudCA9IGdldENvdW50cnlOYW1lKGN1c3RvbWVyLmNvdW50cnkpXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI2V4aXN0aW5nLXBvc3RhbCcsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gY3VzdG9tZXIucG9zdGFsXG4gICAgICAgICk7XG4gICAgICAgIGRpc3BsYXlDQ0xvZ28oY3VzdG9tZXIpO1xuICAgICAgICAkcXMoJyNleGlzdGluZy1sYXN0NCcsICgkZWxlbWVudCk9PiRlbGVtZW50LnRleHRDb250ZW50ID0gY3VzdG9tZXI/LmNhcmQ/Lmxhc3Q0ID8/ICcnXG4gICAgICAgICk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgbGV0IGZ1bGxBZGRyZXNzID0gJyc7XG4gICAgICAgIGlmIChjdXN0b21lci5jb3VudHJ5ID09PSAnSlAnKSB7XG4gICAgICAgICAgICBjb25zdCBmdWxsU3RhdGUgPSBHTE9CQUwucGhwRGF0YT8ud2NfbG9jYXRpb25faW5mbz8uYWxsb3dlZF9zdGF0ZXNfb3JfcHJvdmluY2VzLkpQW2N1c3RvbWVyLnN0YXRlXTtcbiAgICAgICAgICAgIGZ1bGxBZGRyZXNzID0gYCR7Y3VzdG9tZXIucG9zdGFsfSwgJHtmdWxsU3RhdGUgPz8gY3VzdG9tZXIuc3RhdGV9LCAgJHtjdXN0b21lci5jaXR5fSwgJHtjdXN0b21lci5hZGRyZXNzMX0ke2N1c3RvbWVyLmFkZHJlc3MyID8gJyAnICsgY3VzdG9tZXIuYWRkcmVzczIgOiAnJ31gO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgZnVsbEFkZHJlc3MgPSBgJHtjdXN0b21lci5hZGRyZXNzMX0ke2N1c3RvbWVyLmFkZHJlc3MyID8gJyAnICsgY3VzdG9tZXIuYWRkcmVzczIgKyAnLCAnIDogJywnfSAke2N1c3RvbWVyLmNpdHl9LCAke2N1c3RvbWVyLnN0YXRlfSAke2N1c3RvbWVyLnBvc3RhbH0sICR7Y3VzdG9tZXIuY291bnRyeX1gO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IGZ1bGxOYW1lID0gYCR7Y3VzdG9tZXIubmFtZV9maXJzdH0gJHtjdXN0b21lci5uYW1lX2xhc3R9YDtcbiAgICAgICAgJHFzKCcuZW1haWwnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgPSBjdXN0b21lci5lbWFpbFxuICAgICAgICApO1xuICAgICAgICAkcXMoJy5mdWxsLW5hbWUnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgPSBmdWxsTmFtZVxuICAgICAgICApO1xuICAgICAgICAkcXMoJy5wcC1hZGRyZXNzJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuaW5uZXJIVE1MID0gZnVsbEFkZHJlc3NcbiAgICAgICAgKTtcbiAgICB9XG59XG5mdW5jdGlvbiBhZGp1c3RPcmRlclN1bW1hcnlIZWlnaHQoaXNQYXlwYWxVc2VkKSB7XG4gICAgaWYgKEdMT0JBTC5waHBEYXRhPy5lbmFibGVfY291cG9ucyAmJiBHTE9CQUwucGhwRGF0YS5wbHVnaW5fcHdfd29vY29tbWVyY2VfZ2lmdF9jYXJkc19hY3RpdmUgJiYgR0xPQkFMLnBocERhdGEuZW5hYmxlX29yZGVyX25vdGVzICYmICFwcm9kdWN0QnVuZGxlc1Byb2R1Y3RQYWdlKCkpIHtcbiAgICAgICAgaXNQYXlwYWxVc2VkID8gJHFzKCcjcHAtc3VtbWFyeS1ib2R5LWV4aXN0aW5nJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUubWF4SGVpZ2h0ID0gJzI4cmVtJ1xuICAgICAgICApIDogJHFzKCcjcHAtc3VtbWFyeS1ib2R5LWV4aXN0aW5nJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUubWF4SGVpZ2h0ID0gJzMycmVtJ1xuICAgICAgICApO1xuICAgIH0gZWxzZSBpZiAoR0xPQkFMLnBocERhdGE/LmVuYWJsZV9jb3Vwb25zICYmIEdMT0JBTC5waHBEYXRhLnBsdWdpbl9wd193b29jb21tZXJjZV9naWZ0X2NhcmRzX2FjdGl2ZSAmJiAhR0xPQkFMLnBocERhdGEuZW5hYmxlX29yZGVyX25vdGVzICYmICFwcm9kdWN0QnVuZGxlc1Byb2R1Y3RQYWdlKCkpIHtcbiAgICAgICAgaXNQYXlwYWxVc2VkID8gJHFzKCcjcHAtc3VtbWFyeS1ib2R5LWV4aXN0aW5nJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUubWF4SGVpZ2h0ID0gJzE5cmVtJ1xuICAgICAgICApIDogJHFzKCcjcHAtc3VtbWFyeS1ib2R5LWV4aXN0aW5nJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUubWF4SGVpZ2h0ID0gJzIzcmVtJ1xuICAgICAgICApO1xuICAgIH0gZWxzZSBpZiAoR0xPQkFMLnBocERhdGE/LmVuYWJsZV9jb3Vwb25zICYmICFHTE9CQUwucGhwRGF0YS5wbHVnaW5fcHdfd29vY29tbWVyY2VfZ2lmdF9jYXJkc19hY3RpdmUgJiYgR0xPQkFMLnBocERhdGEuZW5hYmxlX29yZGVyX25vdGVzIHx8ICFHTE9CQUwucGhwRGF0YT8uZW5hYmxlX2NvdXBvbnMgJiYgR0xPQkFMLnBocERhdGE/LnBsdWdpbl9wd193b29jb21tZXJjZV9naWZ0X2NhcmRzX2FjdGl2ZSAmJiBHTE9CQUwucGhwRGF0YS5lbmFibGVfb3JkZXJfbm90ZXMgJiYgIXByb2R1Y3RCdW5kbGVzUHJvZHVjdFBhZ2UoKSkge1xuICAgICAgICBpc1BheXBhbFVzZWQgPyAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktZXhpc3RpbmcnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5tYXhIZWlnaHQgPSAnMjYuNXJlbSdcbiAgICAgICAgKSA6ICRxcygnI3BwLXN1bW1hcnktYm9keS1leGlzdGluZycsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLm1heEhlaWdodCA9ICcyOS41cmVtJ1xuICAgICAgICApO1xuICAgIH0gZWxzZSBpZiAoIUdMT0JBTC5waHBEYXRhPy5lbmFibGVfY291cG9ucyAmJiAhR0xPQkFMLnBocERhdGE/LnBsdWdpbl9wd193b29jb21tZXJjZV9naWZ0X2NhcmRzX2FjdGl2ZSAmJiBHTE9CQUwucGhwRGF0YT8uZW5hYmxlX29yZGVyX25vdGVzKSB7XG4gICAgICAgIGlzUGF5cGFsVXNlZCA/ICRxcygnI3BwLXN1bW1hcnktYm9keS1leGlzdGluZycsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLm1heEhlaWdodCA9ICcyM3JlbSdcbiAgICAgICAgKSA6ICRxcygnI3BwLXN1bW1hcnktYm9keS1leGlzdGluZycsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLm1heEhlaWdodCA9ICcyNi41cmVtJ1xuICAgICAgICApO1xuICAgIH0gZWxzZSBpZiAoR0xPQkFMLnBocERhdGE/LmVuYWJsZV9jb3Vwb25zIHx8IEdMT0JBTC5waHBEYXRhPy5wbHVnaW5fcHdfd29vY29tbWVyY2VfZ2lmdF9jYXJkc19hY3RpdmUgJiYgIXByb2R1Y3RCdW5kbGVzUHJvZHVjdFBhZ2UoKSkge1xuICAgICAgICBpc1BheXBhbFVzZWQgPyAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktZXhpc3RpbmcnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5tYXhIZWlnaHQgPSAnMTdyZW0nXG4gICAgICAgICkgOiAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktZXhpc3RpbmcnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5tYXhIZWlnaHQgPSAnMjEuNXJlbSdcbiAgICAgICAgKTtcbiAgICB9IGVsc2UgaWYgKGlzUGF5cGFsVXNlZCkge1xuICAgICAgICAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktZXhpc3RpbmcnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5tYXhIZWlnaHQgPSAnMTVyZW0nXG4gICAgICAgICk7XG4gICAgfVxufVxuZnVuY3Rpb24gaW5pdEN1cnJlbmN5KG1lc3NhZ2UpIHtcbiAgICBpbml0Q3VycmVuY3lFdmVudHMoKTtcbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVNZXJjaGFudEN1cnJlbmN5Q29uZmlnKHtcbiAgICAgICAgY29kZTogbWVzc2FnZS5waHBEYXRhLmN1cnJlbmN5X2luZm8/LmNvZGUgPz8gJ1VTRCcsXG4gICAgICAgIHN5bWJvbDogbWVzc2FnZS5waHBEYXRhPy5jdXJyZW5jeV9pbmZvPy5zeW1ib2wgPz8gJyQnLFxuICAgICAgICB0aG91c2FuZHNTZXBhcmF0b3I6IG1lc3NhZ2UucGhwRGF0YS5jdXJyZW5jeV9pbmZvPy50aG91c2FuZHNfc2VwYXJhdG9yID8/ICcsJyxcbiAgICAgICAgZGVjaW1hbFNlcGFyYXRvcjogbWVzc2FnZS5waHBEYXRhLmN1cnJlbmN5X2luZm8/LmRlY2ltYWxfc2VwYXJhdG9yID8/ICcuJyxcbiAgICAgICAgZGVjaW1hbHM6IG1lc3NhZ2UucGhwRGF0YS5jdXJyZW5jeV9pbmZvPy5udW1iZXJfb2ZfZGVjaW1hbHMgPz8gMixcbiAgICAgICAgcG9zaXRpb246IG1lc3NhZ2UucGhwRGF0YS5jdXJyZW5jeV9pbmZvPy5wb3NpdGlvbiA/PyAnbGVmdCcsXG4gICAgICAgIHJvdW5kaW5nOiBtZXNzYWdlLnBocERhdGEuY3VycmVuY3lfaW5mbz8ucm91bmRpbmcgPz8gJ2Rpc2FibGVkJ1xuICAgIH0pKTtcbn1cbmZ1bmN0aW9uIGluaXRDdXJyZW5jeUV2ZW50cygpIHtcbiAgICBzdG9yZS5zdWJzY3JpYmUoKCk9PntcbiAgICAgICAgcmVuZGVyQ3VycmVuY3lTeW1ib2xzKCk7XG4gICAgfSk7XG59XG5mdW5jdGlvbiByZW5kZXJDdXJyZW5jeVN5bWJvbHMoKSB7XG4gICAgY29uc3QgeyBwb3NpdGlvbiAsIHN5bWJvbCAgfSA9IE1lcmNoYW50Q29uZmlndXJhdGlvbi5jdXJyZW5jeS5jb25maWd1cmF0aW9uKCk7XG4gICAgY29uc3QgcmlnaHQgPSBwb3NpdGlvbiA9PT0gJ3JpZ2h0JyB8fCBwb3NpdGlvbiA9PT0gJ3JpZ2h0X3NwYWNlJztcbiAgICBmb3IgKGNvbnN0ICRlbGVtZW50IG9mICRxc0FsbChgLmN1cnJlbmN5LXN5bWJvbCR7cmlnaHQgPyAnLWFmdGVyJyA6ICcnfWApKXtcbiAgICAgICAgJGVsZW1lbnQuaW5uZXJIVE1MID0gc3ltYm9sO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRHaWZ0Q2FyZElucHV0KF9tZXNzYWdlKSB7XG4gICAgaWYgKCFGZWF0dXJlLmVuYWJsZWQoRmVhdHVyZUZsYWcuR0lGVENBUkRfSU5QVVQpIHx8IHByb2R1Y3RCdW5kbGVzUHJvZHVjdFBhZ2UoKSB8fCBNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSA9PT0gJ3NrcmVnZWFyLmNvbScpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpbml0R2lmdENhcmRFdmVudHMoKTtcbiAgICBmb3IgKGNvbnN0ICRmb3JtIG9mICRxc0FsbCgnLmdpZnQtY2FyZC1vcHRpb24nKSl7XG4gICAgICAgICRmb3JtLmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICB9XG4gICAgJHFzKCcjZ2lmdC1jYXJkLXNlY3Rpb24nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIGlmIChGZWF0dXJlLmVuYWJsZWQoRmVhdHVyZUZsYWcuQ09VUE9OX0lOUFVUKSkge1xuICAgICAgICAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktZXhpc3RpbmcnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5tYXhIZWlnaHQgPSAnMjVyZW0nXG4gICAgICAgICk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzKCcjcHAtc3VtbWFyeS1ib2R5LWV4aXN0aW5nJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUubWF4SGVpZ2h0ID0gJzIzLjVyZW0nXG4gICAgICAgICk7XG4gICAgfVxufVxuZnVuY3Rpb24gaW5pdEdpZnRDYXJkRXZlbnRzKCkge1xuICAgIG9uV2luZG93TWVzc2FnZSgnZ2lmdENhcmRBcHBsaWVkJywgYXN5bmMgKG1lc3NhZ2UpPT57XG4gICAgICAgIGlmICghR0xPQkFMLnBocERhdGEpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBmb3IgKGNvbnN0ICRtZXNzYWdlIG9mICRxc0FsbCgnLmludmFsaWQtZ2lmdC1jYXJkJykpe1xuICAgICAgICAgICAgJG1lc3NhZ2UuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICB9XG4gICAgICAgIGlmICghbWVzc2FnZS5zdWNjZXNzKSB7XG4gICAgICAgICAgICBoaWRlR2lmdENhcmRMb2FkaW5nU3RhdGUoKTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgJG1lc3NhZ2Ugb2YgJHFzQWxsKCcuaW52YWxpZC1naWZ0LWNhcmQnKSl7XG4gICAgICAgICAgICAgICAgJG1lc3NhZ2UudGV4dENvbnRlbnQgPSBtZXNzYWdlLm1lc3NhZ2U7XG4gICAgICAgICAgICAgICAgJG1lc3NhZ2UuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RvcE1vZGFsTG9hZGluZygpKTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICBjbGVhcklucHV0KCcuZ2lmdC1jYXJkLWlucHV0Jyk7XG4gICAgICAgIGF3YWl0IHJlcXVlc3RDYXJ0Q2FsY3VsYXRpb24oKTtcbiAgICAgICAgaGlkZUdpZnRDYXJkTG9hZGluZ1N0YXRlKCk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgIGhpZGVHaWZ0Q2FyZExvYWRpbmdTdGF0ZSgpO1xuICAgIH0pO1xuICAgIGZvciAoY29uc3QgJGZvcm0gb2YgJHFzQWxsKCdmb3JtLnB3LXdjLWdpZnQtY2FyZCcpKXtcbiAgICAgICAgJGZvcm0uYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgKGV2ZW50KT0+e1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgIGlmICghJGZvcm0uY2hlY2tWYWxpZGl0eSgpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgc2hvd0dpZnRDYXJkTG9hZGluZ1N0YXRlKCk7XG4gICAgICAgICAgICBzdG9yZS5kaXNwYXRjaChzdGFydE1vZGFsTG9hZGluZygpKTtcbiAgICAgICAgICAgIGNvbnN0IGRhdGEgPSBuZXcgRm9ybURhdGEoZXZlbnQudGFyZ2V0KTtcbiAgICAgICAgICAgIGNvbnN0IGdpZnRDYXJkTnVtYmVyID0gZGF0YS5nZXQoJ2NhcmRfbnVtYmVyJyk/LnRyaW0oKSA/PyAnJztcbiAgICAgICAgICAgIHdpbmRvdy5wYXJlbnQucG9zdE1lc3NhZ2Uoe1xuICAgICAgICAgICAgICAgIGV2ZW50OiAncmVkZWVtR2lmdENhcmQnLFxuICAgICAgICAgICAgICAgIGNhcmROdW1iZXI6IGdpZnRDYXJkTnVtYmVyXG4gICAgICAgICAgICB9LCAnKicpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkZGl2IG9mICRxc0FsbCgnLmdpZnQtY2FyZC1vcHRpb24nKSl7XG4gICAgICAgICRkaXYuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBzaG93R2lmdENhcmRJbnB1dCk7XG4gICAgfVxuICAgIGZvciAoY29uc3QgJGV4aXRHaWZ0IG9mICRxc0FsbCgnLmV4aXQtYnV0dG9uLWdpZnQnKSl7XG4gICAgICAgICRleGl0R2lmdC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGhpZGVHaWZ0Q2FyZElucHV0KTtcbiAgICB9XG59XG5mdW5jdGlvbiBzaG93R2lmdENhcmRJbnB1dCgpIHtcbiAgICBmb3IgKGNvbnN0ICRjb3Vwb24gb2YgJHFzQWxsKCdmb3JtLnB3LXdjLWdpZnQtY2FyZCcpKXtcbiAgICAgICAgJGNvdXBvbi5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgfVxuICAgIGZvciAoY29uc3QgJG9wdGlvbiBvZiAkcXNBbGwoJy5naWZ0LWNhcmQtb3B0aW9uJykpe1xuICAgICAgICAkb3B0aW9uLmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICB9XG59XG5mdW5jdGlvbiBoaWRlR2lmdENhcmRJbnB1dCgpIHtcbiAgICBmb3IgKGNvbnN0ICRjb3Vwb24gb2YgJHFzQWxsKCdmb3JtLnB3LXdjLWdpZnQtY2FyZCcpKXtcbiAgICAgICAgJGNvdXBvbi5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgfVxuICAgIGZvciAoY29uc3QgJG9wdGlvbiBvZiAkcXNBbGwoJy5naWZ0LWNhcmQtb3B0aW9uJykpe1xuICAgICAgICAkb3B0aW9uLmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkaW52YWxpZCBvZiAkcXNBbGwoJy5pbnZhbGlkLWdpZnQtY2FyZCcpKXtcbiAgICAgICAgJGludmFsaWQuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbiAgICBjbGVhcklucHV0KCcuZ2lmdC1jYXJkLWlucHV0Jyk7XG59XG5mdW5jdGlvbiBoaWRlR2lmdENhcmRMb2FkaW5nU3RhdGUoKSB7XG4gICAgZm9yIChjb25zdCAkc3Bpbm5lciBvZiAkcXNBbGwoJy5naWZ0LWNhcmQtc3Bpbm5lcicpKXtcbiAgICAgICAgJHNwaW5uZXIuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0ICRib3JkZXIgb2YgJHFzQWxsKCcuZ2lmdC1jYXJkLWlucHV0Jykpe1xuICAgICAgICAkYm9yZGVyLmNsYXNzTGlzdC5yZW1vdmUoJ3JlbW92ZS1yaWdodC1ib3JkZXInKTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkYXBwbHlCdXR0b24gb2YgJHFzQWxsKCcuZ2lmdC1jYXJkLWFwcGx5Jykpe1xuICAgICAgICAkYXBwbHlCdXR0b24uZGlzYWJsZWQgPSBmYWxzZTtcbiAgICB9XG59XG5mdW5jdGlvbiBzaG93R2lmdENhcmRMb2FkaW5nU3RhdGUoKSB7XG4gICAgZm9yIChjb25zdCAkc3Bpbm5lciBvZiAkcXNBbGwoJy5naWZ0LWNhcmQtc3Bpbm5lcicpKXtcbiAgICAgICAgJHNwaW5uZXIuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0ICRib3JkZXIgb2YgJHFzQWxsKCcuZ2lmdC1jYXJkLWlucHV0Jykpe1xuICAgICAgICAkYm9yZGVyLmNsYXNzTGlzdC5hZGQoJ3JlbW92ZS1yaWdodC1ib3JkZXInKTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkYXBwbHlCdXR0b24gb2YgJHFzQWxsKCcuZ2lmdC1jYXJkLWFwcGx5Jykpe1xuICAgICAgICAkYXBwbHlCdXR0b24uZGlzYWJsZWQgPSB0cnVlO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRDb3Vwb25JbnB1dChfbWVzc2FnZSkge1xuICAgIGlmICghRmVhdHVyZS5lbmFibGVkKEZlYXR1cmVGbGFnLkNPVVBPTl9JTlBVVCkgfHwgRmVhdHVyZS52ZXJzaW9uKEZlYXR1cmVGbGFnLkNPVVBPTl9JTlBVVCkgPCAyICYmIEVudmlyb25tZW50LnBsdWdpbi5wYWdlVHlwZSgpID09PSAncHJvZHVjdCcpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBzaG93Q291cG9uRW50cnlTdXBwb3J0KCk7XG4gICAgaW5pdENvdXBvbklucHV0RXZlbnRzKCk7XG59XG5mdW5jdGlvbiBpbml0Q291cG9uSW5wdXRFdmVudHMoKSB7XG4gICAgb25XaW5kb3dNZXNzYWdlKCdjb3Vwb24nLCBhc3luYyAobWVzc2FnZSk9PntcbiAgICAgICAgZm9yIChjb25zdCAkbWVzc2FnZSBvZiAkcXNBbGwoJy53Yy1pbnZhbGlkLWNvdXBvbicpKXtcbiAgICAgICAgICAgICRtZXNzYWdlLmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAobWVzc2FnZS5kYXRhICYmIG1lc3NhZ2UuZGF0YS5zdGF0dXMgPT09IDQwNCkge1xuICAgICAgICAgICAgaGlkZUNvdXBvbkxvYWRpbmdTdGF0ZSgpO1xuICAgICAgICAgICAgZm9yIChjb25zdCAkbWVzc2FnZSBvZiAkcXNBbGwoJy53Yy1pbnZhbGlkLWNvdXBvbicpKXtcbiAgICAgICAgICAgICAgICAkbWVzc2FnZS5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgIGF3YWl0IHJlcXVlc3RDYXJ0Q2FsY3VsYXRpb24oKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RvcE1vZGFsTG9hZGluZygpKTtcbiAgICAgICAgaGlkZUNvdXBvbkxvYWRpbmdTdGF0ZSgpO1xuICAgICAgICBjbGVhcklucHV0KCcud2MtY291cG9uLWNvZGUtaW5wdXQnKTtcbiAgICB9KTtcbiAgICBvbldpbmRvd01lc3NhZ2UoJ3N0b3BDb3Vwb25Mb2FkaW5nJywgKF8pPT57XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgIGhpZGVDb3Vwb25Mb2FkaW5nU3RhdGUoKTtcbiAgICB9KTtcbiAgICBmb3IgKGNvbnN0ICRmb3JtIG9mICRxc0FsbCgnZm9ybS53Yy1jb3Vwb24tY29kZScpKXtcbiAgICAgICAgJGZvcm0uYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgKGV2ZW50KT0+e1xuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgIGlmICghJGZvcm0uY2hlY2tWYWxpZGl0eSgpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgICAgICBzaG93Q291cG9uTG9hZGluZ1N0YXRlKCk7XG4gICAgICAgICAgICBjb25zdCBkYXRhID0gbmV3IEZvcm1EYXRhKGV2ZW50LnRhcmdldCA/PyB1bmRlZmluZWQpO1xuICAgICAgICAgICAgY29uc3QgY291cG9uQ29kZSA9IGRhdGEuZ2V0KCdjb3Vwb25fY29kZScpPy50cmltKCkgPz8gJyc7XG4gICAgICAgICAgICB3aW5kb3cucGFyZW50LnBvc3RNZXNzYWdlKHtcbiAgICAgICAgICAgICAgICBldmVudDogJ2ZldGNoQ291cG9uJyxcbiAgICAgICAgICAgICAgICBjb2RlOiBjb3Vwb25Db2RlXG4gICAgICAgICAgICB9LCAnKicpO1xuICAgICAgICB9KTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkb3BlbkNvdXBvbiBvZiAkcXNBbGwoJy5jb3Vwb24tY29kZS1vcHRpb24nKSl7XG4gICAgICAgICRvcGVuQ291cG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgc2hvd0NvdXBvbklucHV0KTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkZXhpdENvdXBvbiBvZiAkcXNBbGwoJy5leGl0LWJ1dHRvbi1jb3Vwb24nKSl7XG4gICAgICAgICRleGl0Q291cG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgaGlkZUNvdXBvbklucHV0KTtcbiAgICB9XG4gICAgaGlkZUNvdXBvbkxvYWRpbmdTdGF0ZSgpO1xufVxuZnVuY3Rpb24gc2hvd0NvdXBvbkVudHJ5U3VwcG9ydCgpIHtcbiAgICBmb3IgKGNvbnN0ICRmb3JtIG9mICRxc0FsbCgnLmNvdXBvbi1jb2RlLW9wdGlvbicpKXtcbiAgICAgICAgJGZvcm0uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH1cbiAgICAkcXMoJyNjb3Vwb24tY29kZS1zZWN0aW9uJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbn1cbmZ1bmN0aW9uIHNob3dDb3Vwb25JbnB1dCgpIHtcbiAgICBmb3IgKGNvbnN0ICRjb3Vwb24gb2YgJHFzQWxsKCdmb3JtLndjLWNvdXBvbi1jb2RlJykpe1xuICAgICAgICAkY291cG9uLmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkb3B0aW9uIG9mICRxc0FsbCgnLmNvdXBvbi1jb2RlLW9wdGlvbicpKXtcbiAgICAgICAgJG9wdGlvbi5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgfVxufVxuZnVuY3Rpb24gaGlkZUNvdXBvbklucHV0KCkge1xuICAgIGZvciAoY29uc3QgJGNvdXBvbiBvZiAkcXNBbGwoJ2Zvcm0ud2MtY291cG9uLWNvZGUnKSl7XG4gICAgICAgICRjb3Vwb24uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0ICRvcHRpb24gb2YgJHFzQWxsKCcuY291cG9uLWNvZGUtb3B0aW9uJykpe1xuICAgICAgICAkb3B0aW9uLmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICB9XG4gICAgZm9yIChjb25zdCAkaW52YWxpZCBvZiAkcXNBbGwoJy53Yy1pbnZhbGlkLWNvdXBvbicpKXtcbiAgICAgICAgJGludmFsaWQuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbiAgICBjbGVhcklucHV0KCcud2MtY291cG9uLWNvZGUtaW5wdXQnKTtcbn1cbmZ1bmN0aW9uIGhpZGVDb3Vwb25Mb2FkaW5nU3RhdGUoKSB7XG4gICAgZm9yIChjb25zdCAkc3Bpbm5lciBvZiAkcXNBbGwoJy53Yy1jb3Vwb24tc3Bpbm5lcicpKXtcbiAgICAgICAgJHNwaW5uZXIuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0ICRib3JkZXIgb2YgJHFzQWxsKCcud2MtY291cG9uLWNvZGUtaW5wdXQnKSl7XG4gICAgICAgICRib3JkZXIuY2xhc3NMaXN0LnJlbW92ZSgncmVtb3ZlLXJpZ2h0LWJvcmRlcicpO1xuICAgIH1cbiAgICBmb3IgKGNvbnN0ICRhcHBseUJ1dHRvbiBvZiAkcXNBbGwoJy53Yy1jb3Vwb24tY29kZS1hcHBseScpKXtcbiAgICAgICAgJGFwcGx5QnV0dG9uLmRpc2FibGVkID0gZmFsc2U7XG4gICAgfVxufVxuZnVuY3Rpb24gc2hvd0NvdXBvbkxvYWRpbmdTdGF0ZSgpIHtcbiAgICBmb3IgKGNvbnN0ICRzcGlubmVyIG9mICRxc0FsbCgnLndjLWNvdXBvbi1zcGlubmVyJykpe1xuICAgICAgICAkc3Bpbm5lci5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgfVxuICAgIGZvciAoY29uc3QgJGJvcmRlciBvZiAkcXNBbGwoJy53Yy1jb3Vwb24tY29kZS1pbnB1dCcpKXtcbiAgICAgICAgJGJvcmRlci5jbGFzc0xpc3QuYWRkKCdyZW1vdmUtcmlnaHQtYm9yZGVyJyk7XG4gICAgfVxuICAgIGZvciAoY29uc3QgJGFwcGx5QnV0dG9uIG9mICRxc0FsbCgnLndjLWNvdXBvbi1jb2RlLWFwcGx5Jykpe1xuICAgICAgICAkYXBwbHlCdXR0b24uZGlzYWJsZWQgPSB0cnVlO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRMYW5ndWFnZShtZXNzYWdlKSB7XG4gICAgaW5pdExhbmd1YWdlRXZlbnRzKCk7XG4gICAgbGV0IGxhbmd1YWdlID0gbWVzc2FnZS5waHBEYXRhLmxhbmd1YWdlID09PSAnZGV0ZWN0LWZyb20tcGFnZScgPyBtZXNzYWdlLnBhZ2VMYW5ndWFnZSA6IG1lc3NhZ2UucGhwRGF0YS5sYW5ndWFnZTtcbiAgICBjb25zdCBlbmdsaXNoVmFyaWFudHMgPSBuZXcgU2V0KFtcbiAgICAgICAgJ2VuLUFVJyxcbiAgICAgICAgJ2VuLUNBJyxcbiAgICAgICAgJ2VuLUdCJyxcbiAgICAgICAgJ2VuLU5aJyxcbiAgICAgICAgJ2VuLVpBJ1xuICAgIF0pO1xuICAgIGlmIChlbmdsaXNoVmFyaWFudHMuaGFzKGxhbmd1YWdlKSkge1xuICAgICAgICBsYW5ndWFnZSA9ICdlbi1VUyc7XG4gICAgfVxuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUxhbmd1YWdlKGxhbmd1YWdlKSk7XG59XG5mdW5jdGlvbiBpbml0TGFuZ3VhZ2VFdmVudHMoKSB7XG4gICAgb25XaW5kb3dNZXNzYWdlKCdwYWdlTGFuZ3VhZ2VDaGFuZ2UnLCAobWVzc2FnZSk9PntcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlTGFuZ3VhZ2UobWVzc2FnZS5sYW5ndWFnZSkpO1xuICAgIH0pO1xuICAgIHN0b3JlLnN1YnNjcmliZSgoKT0+e1xuICAgICAgICByZW5kZXJMb2NhbGVUZXh0KCk7XG4gICAgfSk7XG59XG5mdW5jdGlvbiByZW5kZXJMb2NhbGVUZXh0KCkge1xuICAgIGZvciAoY29uc3QgJGVsZW1lbnQgb2YgJHFzQWxsKCdbZGF0YS1pMThuXScpKXtcbiAgICAgICAgaWYgKCRlbGVtZW50Lm5vZGVOYW1lID09PSAnSU5QVVQnICYmICRlbGVtZW50LnR5cGUgPT09ICdzdWJtaXQnKSB7XG4gICAgICAgICAgICAkZWxlbWVudC52YWx1ZSA9IGdldExvY2FsZVRleHQoJGVsZW1lbnQ/LmRhdGFzZXQ/LmkxOG4gPz8gJycpO1xuICAgICAgICB9IGVsc2UgaWYgKCRlbGVtZW50Lm5vZGVOYW1lID09PSAnSU5QVVQnKSB7XG4gICAgICAgICAgICAkZWxlbWVudC5wbGFjZWhvbGRlciA9IGdldExvY2FsZVRleHQoJGVsZW1lbnQ/LmRhdGFzZXQ/LmkxOG4gPz8gJycpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJGVsZW1lbnQudGV4dENvbnRlbnQgPSBnZXRMb2NhbGVUZXh0KCRlbGVtZW50Py5kYXRhc2V0Py5pMThuID8/ICcnKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBpZiAoRW52aXJvbm1lbnQubGFuZ3VhZ2UoKSA9PT0gJ3JvLVJPJykge1xuICAgICAgICBzZXRDdXN0b21WYWxpZGl0eU1lc3NhZ2UoKTtcbiAgICB9XG59XG5mdW5jdGlvbiBzZXRDdXN0b21WYWxpZGl0eU1lc3NhZ2UoKSB7XG4gICAgZm9yIChjb25zdCAkaW5wdXQgb2YgJHFzQWxsKCdmb3JtIGlucHV0Jykpe1xuICAgICAgICAkaW5wdXQuYWRkRXZlbnRMaXN0ZW5lcignaW52YWxpZCcsICgpPT57XG4gICAgICAgICAgICAkaW5wdXQuc2V0Q3VzdG9tVmFsaWRpdHkoJ1RlIHJ1Z8SDbSBzYSBjb21wbGV0ZXppIGFjZXN0IGPDom1wLicpO1xuICAgICAgICB9KTtcbiAgICAgICAgJGlucHV0LmFkZEV2ZW50TGlzdGVuZXIoJ2lucHV0JywgKCk9PntcbiAgICAgICAgICAgICRpbnB1dC5zZXRDdXN0b21WYWxpZGl0eSgnJyk7XG4gICAgICAgIH0pO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGNhcGl0YWxpemVGaXJzdExldHRlcihzdHJpbmcpIHtcbiAgICBjb25zdCBzdHJpbmdUb1VwcGVyID0gU3RyaW5nKHN0cmluZyk7XG4gICAgcmV0dXJuIHN0cmluZ1RvVXBwZXIuY2hhckF0KDApLnRvVXBwZXJDYXNlKCkgKyBzdHJpbmdUb1VwcGVyLnNsaWNlKDEpO1xufVxuZnVuY3Rpb24gaW5pdFF1YW50aXR5Q2hhbmdlckV2ZW50KCkge1xuICAgIGlmICghRmVhdHVyZS5lbmFibGVkKEZlYXR1cmVGbGFnLlFVQU5USVRZX0NIQU5HRVIpKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgJHFzQWxsKCcjcHAtc3VtbWFyeS1ib2R5LCAjcHAtc3VtbWFyeS1ib2R5LWV4aXN0aW5nLCAjcHAtc3VtbWFyeS1ib2R5LW1vYmlsZScsICgkY2FydENvbnRhaW5lcik9PntcbiAgICAgICAgJGNhcnRDb250YWluZXIuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBhc3luYyAoZXZlbnQxKT0+e1xuICAgICAgICAgICAgY29uc3QgJHRhcmdldCA9IGV2ZW50MS50YXJnZXQ7XG4gICAgICAgICAgICBpZiAoISR0YXJnZXQuY2xvc2VzdCgnLnF0eS1idG4nKSAmJiAhJHRhcmdldC5jbG9zZXN0KCcucXR5LWZzJykgJiYgISR0YXJnZXQuY2xvc2VzdCgnLml0ZW0tcmVtb3ZlcicpKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKCR0YXJnZXQuY2xvc2VzdCgnLnF0eS1idG4nKSkge1xuICAgICAgICAgICAgICAgIGNvbnN0ICRidXR0b24gPSAkdGFyZ2V0LmNsb3Nlc3QoJy5xdHktYnRuJyk7XG4gICAgICAgICAgICAgICAgY29uc3QgY2FydEl0ZW1LZXkgPSAkYnV0dG9uLmRhdGFzZXQucWlkO1xuICAgICAgICAgICAgICAgIGlmICgkYnV0dG9uLmNsYXNzTGlzdC5jb250YWlucygnZGVjcmVhc2UtcXR5JykpIHtcbiAgICAgICAgICAgICAgICAgICAgYXdhaXQgY2hhbmdlUXVhbnRpdHkoY2FydEl0ZW1LZXksIC0xLCBmYWxzZSk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIGlmICgkYnV0dG9uLmNsYXNzTGlzdC5jb250YWlucygnaW5jcmVhc2UtcXR5JykpIHtcbiAgICAgICAgICAgICAgICAgICAgYXdhaXQgY2hhbmdlUXVhbnRpdHkoY2FydEl0ZW1LZXksIDEsIGZhbHNlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9IGVsc2UgaWYgKCR0YXJnZXQuY2xvc2VzdCgnLnF0eS1mcycpKSB7XG4gICAgICAgICAgICAgICAgbGV0IHByZXZpb3VzVGltZW91dElkID0gbnVsbDtcbiAgICAgICAgICAgICAgICBjb25zdCBjdXJyZW50VmFsdWUgPSAkdGFyZ2V0LmNsb3Nlc3QoJy5xdHktZnMnKS52YWx1ZTtcbiAgICAgICAgICAgICAgICBjb25zdCBjYXJ0SXRlbUtleSA9ICR0YXJnZXQuY2xvc2VzdCgnLnF0eS1mcycpLmRhdGFzZXQucWlkO1xuICAgICAgICAgICAgICAgICR0YXJnZXQuYWRkRXZlbnRMaXN0ZW5lcignaW5wdXQnLCAoZXZlbnQpPT57XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0ICRpbnB1dFRhcmdldCA9IGV2ZW50LnRhcmdldDtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHByZXZpb3VzVGltZW91dElkICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjbGVhclRpbWVvdXQocHJldmlvdXNUaW1lb3V0SWQpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIHByZXZpb3VzVGltZW91dElkID0gc2V0VGltZW91dChhc3luYyAoKT0+e1xuICAgICAgICAgICAgICAgICAgICAgICAgcHJldmlvdXNUaW1lb3V0SWQgPSBudWxsO1xuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKCRpbnB1dFRhcmdldC52YWx1ZSAmJiBjdXJyZW50VmFsdWUgIT09ICRpbnB1dFRhcmdldC52YWx1ZSAmJiAkaW5wdXRUYXJnZXQuY2hlY2tWYWxpZGl0eSgpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYXdhaXQgY2hhbmdlUXVhbnRpdHkoY2FydEl0ZW1LZXksIE51bWJlci5wYXJzZUludCgkaW5wdXRUYXJnZXQudmFsdWUpLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgJGlucHV0VGFyZ2V0LnJlcG9ydFZhbGlkaXR5KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0sIDc1MCk7XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgJHRhcmdldC5hZGRFdmVudExpc3RlbmVyKCdibHVyJywgYXN5bmMgKGV2ZW50KT0+e1xuICAgICAgICAgICAgICAgICAgICBjb25zdCAkaW5wdXRUYXJnZXQgPSBldmVudC50YXJnZXQ7XG4gICAgICAgICAgICAgICAgICAgIGlmIChwcmV2aW91c1RpbWVvdXRJZCAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHByZXZpb3VzVGltZW91dElkKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBpZiAoJGlucHV0VGFyZ2V0LnZhbHVlICYmIGN1cnJlbnRWYWx1ZSAhPT0gJGlucHV0VGFyZ2V0LnZhbHVlICYmICRpbnB1dFRhcmdldC5jaGVja1ZhbGlkaXR5KCkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGF3YWl0IGNoYW5nZVF1YW50aXR5KGNhcnRJdGVtS2V5LCBOdW1iZXIucGFyc2VJbnQoJGlucHV0VGFyZ2V0LnZhbHVlKSwgdHJ1ZSk7XG4gICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAkaW5wdXRUYXJnZXQudmFsdWUgPSBjdXJyZW50VmFsdWU7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0gZWxzZSBpZiAoJHRhcmdldC5jbG9zZXN0KCcuaXRlbS1yZW1vdmVyJykpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBjYXJ0SXRlbUtleSA9ICR0YXJnZXQuY2xvc2VzdCgnLml0ZW0tcmVtb3ZlcicpLmRhdGFzZXQucWlkO1xuICAgICAgICAgICAgICAgIGF3YWl0IGNoYW5nZVF1YW50aXR5KGNhcnRJdGVtS2V5LCAwLCB0cnVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfSk7XG59XG5hc3luYyBmdW5jdGlvbiBjaGFuZ2VRdWFudGl0eShjYXJ0SXRlbUtleSwgYW1vdW50ID0gMSwgc2V0ID0gZmFsc2UpIHtcbiAgICBpZiAoRW52aXJvbm1lbnQubW9kYWxVSS5sb2FkaW5nTW9kZSgpICE9PSAnZmluaXNoZWQnKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbExvYWRpbmcoKSk7XG4gICAgdHJ5IHtcbiAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBmZXRjaEhvc3RXaW5kb3dEYXRhKCdwcC1jaGFuZ2UtcXVhbnRpdHknLCB7XG4gICAgICAgICAgICBrZXk6IGNhcnRJdGVtS2V5LFxuICAgICAgICAgICAgYW1vdW50LFxuICAgICAgICAgICAgc2V0XG4gICAgICAgIH0pO1xuICAgICAgICBjb25zdW1lQ2FydENhbGN1bGF0aW9uUmVzcG9uc2UocmVzcG9uc2UpO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgUXVhbnRpdHkgZmFpbGVkIHRvIGNoYW5nZSBvbiAke01lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpfS4gRXJyb3Ike2Vycm9yLm1lc3NhZ2V9YCkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG59XG5mdW5jdGlvbiBpbml0Q2FydCgpIHtcbiAgICBpbml0Q2FydEV2ZW50cygpO1xuICAgIGluaXRRdWFudGl0eUNoYW5nZXJFdmVudCgpO1xuICAgIG9uV2luZG93TWVzc2FnZSgncHAtdXBkYXRlLWNhcnQnLCBhc3luYyAoKT0+e1xuICAgICAgICBhd2FpdCByZXF1ZXN0Q2FydENhbGN1bGF0aW9uKCk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgfSk7XG59XG5mdW5jdGlvbiBpbml0Q2FydEV2ZW50cygpIHtcbiAgICBsZXQgcHJldmlvdXNDYXJ0RGF0YSA9ICcnO1xuICAgIHN0b3JlLnN1YnNjcmliZSgoKT0+e1xuICAgICAgICBjb25zdCBjYXJ0RGF0YSA9IEpTT04uc3RyaW5naWZ5KERlZmF1bHRDYXJ0LmNvbnRlbnRzKCkpO1xuICAgICAgICBpZiAoY2FydERhdGEgIT09IHByZXZpb3VzQ2FydERhdGEpIHtcbiAgICAgICAgICAgIHByZXZpb3VzQ2FydERhdGEgPSBjYXJ0RGF0YTtcbiAgICAgICAgICAgIHJlbmRlck9yZGVyU3VtbWFyeUl0ZW1zKERlZmF1bHRDYXJ0LmNvbnRlbnRzKCkpO1xuICAgICAgICB9XG4gICAgfSk7XG59XG5mdW5jdGlvbiByZW5kZXJPcmRlclN1bW1hcnlJdGVtcyhjYXJ0KSB7XG4gICAgY29uc3QgJHRib2R5ID0gJHFzKCcjcHAtc3VtbWFyeS1ib2R5Jyk7XG4gICAgY29uc3QgJHRib2R5RXhpc3RpbmcgPSAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktZXhpc3RpbmcnKTtcbiAgICBjb25zdCAkdGJvZHlNb2JpbGUgPSAkcXMoJyNwcC1zdW1tYXJ5LWJvZHktbW9iaWxlJyk7XG4gICAgaWYgKCEkdGJvZHkgfHwgISR0Ym9keUV4aXN0aW5nIHx8ICEkdGJvZHlNb2JpbGUpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBmdW5jdGlvbiBnZXRWYXJpYXRpb25IVE1MKGl0ZW0pIHtcbiAgICAgICAgbGV0IHZhcmlhdGlvblJvd0hUTUwgPSAnJztcbiAgICAgICAgaWYgKCFpdGVtLmF0dHJpYnV0ZXMpIHtcbiAgICAgICAgICAgIHJldHVybiB2YXJpYXRpb25Sb3dIVE1MO1xuICAgICAgICB9XG4gICAgICAgIGNvbnN0IGtleXMgPSBPYmplY3Qua2V5cyhpdGVtLmF0dHJpYnV0ZXMpO1xuICAgICAgICBpZiAoa2V5cy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgICAgIHJldHVybiB2YXJpYXRpb25Sb3dIVE1MO1xuICAgICAgICB9XG4gICAgICAgIHZhcmlhdGlvblJvd0hUTUwgPSAnJztcbiAgICAgICAgZm9yIChjb25zdCBrZXkgb2Yga2V5cyl7XG4gICAgICAgICAgICBjb25zdCBmb3JtYXR0ZWRLZXkgPSBjYXBpdGFsaXplRmlyc3RMZXR0ZXIoa2V5LnJlcGxhY2UoJ2F0dHJpYnV0ZV8nLCAnJykucmVwbGFjZSgncGFfJywgJycpLnJlcGxhY2UoLy0vZywgJyAnKSk7XG4gICAgICAgICAgICBjb25zdCBmb3JtYXR0ZWRWYWx1ZSA9IFN0cmluZyhpdGVtLmF0dHJpYnV0ZXNba2V5XSkudG9VcHBlckNhc2UoKTtcbiAgICAgICAgICAgIHZhcmlhdGlvblJvd0hUTUwgKz0gYDxicj48c3BhbiBjbGFzcz1cIiR7aXRlbS5pc19wYXJ0X29mX2J1bmRsZSA/ICcnIDogJ211dGVkJ30gcGwtMy8yXCI+JHtmb3JtYXR0ZWRLZXl9OiAke2Zvcm1hdHRlZFZhbHVlfTwvc3Bhbj5gO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiB2YXJpYXRpb25Sb3dIVE1MO1xuICAgIH1cbiAgICBjbGVhck9yZGVyU3VtbWFyeSgpO1xuICAgIGlmIChEZWZhdWx0Q2FydC5jb250ZW50cygpLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICBjb25zdCAkbWVzc2FnZSA9IGA8dHIgY2xhc3M9XCJvcmRlci1zdW1tYXJ5LWl0ZW1cIj48dGQgc3R5bGU9XCJ0ZXh0LWFsaWduOiBjZW50ZXI7IFwiPiR7Z2V0TG9jYWxlVGV4dCgnZW1wdHktY2FydCcpfTwvdGQ+PC90cj5gO1xuICAgICAgICAkdGJvZHkuaW5uZXJIVE1MID0gJG1lc3NhZ2U7XG4gICAgICAgICR0Ym9keU1vYmlsZS5pbm5lckhUTUwgPSAkbWVzc2FnZTtcbiAgICAgICAgJHRib2R5RXhpc3RpbmcuaW5uZXJIVE1MID0gJG1lc3NhZ2U7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgZm9yKGxldCBpID0gY2FydC5sZW5ndGggLSAxOyBpID49IDA7IGktLSl7XG4gICAgICAgIGNvbnN0IGl0ZW0gPSBjYXJ0W2ldO1xuICAgICAgICBpZiAoaXRlbS5xdWFudGl0eSA9PT0gJycgfHwgTnVtYmVyLnBhcnNlSW50KFN0cmluZyhpdGVtLnF1YW50aXR5KSkgPT09IDApIHtcbiAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICB9XG4gICAgICAgIGxldCBuYW1lID0gaXRlbS5uYW1lO1xuICAgICAgICBpZiAoTWVyY2hhbnRDb25maWd1cmF0aW9uLmhvc3ROYW1lKCkgPT09ICd1Z29wcm9iYXNlYmFsbC5jb20nICYmIGl0ZW0uZm9ybWF0dGVkX2l0ZW1fZGF0YSAmJiBpdGVtLm5hbWVfd2l0aF92YXJpYXRpb24pIHtcbiAgICAgICAgICAgIG5hbWUgPSBpdGVtLm5hbWVfd2l0aF92YXJpYXRpb247XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgdmFyaWF0aW9uVGl0bGUgPSAhaXRlbS5hdHRyaWJ1dGVzICYmIGl0ZW0udmFyaWF0aW9uX3RpdGxlID8gYCAtICR7aXRlbS52YXJpYXRpb25fdGl0bGUgPz8gJyd9YCA6ICcnO1xuICAgICAgICBjb25zdCBsYWJlbCA9IGAke25hbWUuYm9sZCgpfSR7dmFyaWF0aW9uVGl0bGV9ICR7bWV0YURhdGFSb3dzSFRNTChpdGVtKX0gJHtpdGVtLmZvcm1hdHRlZF9pdGVtX2RhdGEgPyBmb3JtYXR0ZWRJdGVtRGF0YUhUTUwoaXRlbSkgOiBnZXRWYXJpYXRpb25IVE1MKGl0ZW0pfWA7XG4gICAgICAgIGxldCBhbW91bnQgPSBgJHtmb3JtYXRDdXJyZW5jeVN0cmluZyhOdW1iZXIucGFyc2VGbG9hdChpdGVtLmRpc3BsYXlfcHJpY2UgPz8gaXRlbS5wcmljZSkgKiBjYXJ0SXRlbVF1YW50aXR5KGl0ZW0pKX1gO1xuICAgICAgICBpZiAoaXRlbS5pc19wYXJ0X29mX2J1bmRsZSkge1xuICAgICAgICAgICAgYW1vdW50ID0gJyc7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKGl0ZW0uaXNfc3Vic2NyaXB0aW9uKSB7XG4gICAgICAgICAgICBjb25zdCBzdHJpbmdBbW91bnQgPSAhaXRlbS5zdWJzY3JpcHRpb25fcHJpY2Vfc3RyaW5nPy5pbmRleE9mKFN0cmluZyhpdGVtLmRpc3BsYXlfcHJpY2UgPz8gaXRlbS5wcmljZSkpID8gJycgOiBmb3JtYXRDb3N0U3RyaW5nKE51bWJlci5wYXJzZUZsb2F0KGl0ZW0uZGlzcGxheV9wcmljZSA/PyBpdGVtLnByaWNlKSk7XG4gICAgICAgICAgICBhbW91bnQgPSBgJHtNZXJjaGFudENvbmZpZ3VyYXRpb24uY3VycmVuY3kuc3ltYm9sKCl9JHtzdHJpbmdBbW91bnR9JHtpdGVtLnN1YnNjcmlwdGlvbl9wcmljZV9zdHJpbmcgPz8gJyd9YDtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCAkcm93ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgndHInKTtcbiAgICAgICAgJHJvdy5jbGFzc05hbWUgPSAnb3JkZXItc3VtbWFyeS1pdGVtJztcbiAgICAgICAgY29uc3QgJGl0ZW1SZW1vdmVyID0gKHRkQ2xhc3MgPSAnJyk9PmBcblx0XHQ8dGQgY2xhc3M9XCJpdGVtLXJlbW92ZXItdGQgbm9uLWJ1bmRsZWQtaXRlbSAke3RkQ2xhc3N9XCI+XG5cdFx0XHQ8YnV0dG9uIGNsYXNzPVwiaXRlbS1yZW1vdmVyXCIgZGF0YS1xaWQ9XCIke2l0ZW0uaXRlbV9rZXl9XCI+JnRpbWVzOzwvYnV0dG9uPlxuXHRcdDwvdGQ+YFxuICAgICAgICA7XG4gICAgICAgIGNvbnN0ICRxdHlDaGFuZ2VyID0gKHRkQ2xhc3MgPSAnJyk9PmBcblx0XHQ8dGQgY2xhc3M9XCJxdHktdGQgJHt0ZENsYXNzfVwiPlxuXHRcdFx0PGRpdiBjbGFzcz1cInF1YW50aXR5LWNoYW5nZXJcIj5cblx0XHRcdFx0PGJ1dHRvbiB0eXBlPVwiYnV0dG9uXCIgY2xhc3M9XCJwci0wIGRlY3JlYXNlLXF0eSBxdHktYnRuICR7Y2FydEl0ZW1RdWFudGl0eShpdGVtKSA8PSAxID8gJ3Njcm9sbC1lbmQnIDogJyd9XCIgZGF0YS1xaWQ9XCIke2l0ZW0uaXRlbV9rZXl9XCI+JiM4NzIyOzwvYnV0dG9uPlxuXHRcdFx0XHQ8Zm9ybSBvblN1Ym1pdD1cInJldHVybiBmYWxzZTtcIiBjbGFzcz1cIm1iLTBcIj5cblx0XHRcdFx0XHQ8aW5wdXQgdHlwZT1cIm51bWJlclwiIG1pbj1cIjBcIiBtYXg9XCIke2l0ZW0uc3RvY2tfcXR5ID8gaXRlbS5zdG9ja19xdHkgOiAnJ31cIiBjbGFzcz1cInF0eS1mc1wiIHZhbHVlPVwiJHtjYXJ0SXRlbVF1YW50aXR5KGl0ZW0pfVwiIGRhdGEtcWlkPVwiJHtpdGVtLml0ZW1fa2V5fVwiIHJlcXVpcmVkLz5cblx0XHRcdFx0PC9mb3JtPlxuXHRcdFx0XHQ8YnV0dG9uIHR5cGU9XCJidXR0b25cIiBjbGFzcz1cInBsLTAgaW5jcmVhc2UtcXR5IHF0eS1idG4gJHtpdGVtLnN0b2NrX3F0eSAmJiBjYXJ0SXRlbVF1YW50aXR5KGl0ZW0pID49IGl0ZW0uc3RvY2tfcXR5ID8gJ3Njcm9sbC1lbmQnIDogJyd9XCIgZGF0YS1xaWQ9XCIke2l0ZW0uaXRlbV9rZXl9XCI+KzwvYnV0dG9uPlxuXHRcdFx0PC9kaXY+XG5cdFx0PC90ZD5gXG4gICAgICAgIDtcbiAgICAgICAgY29uc3Qgc2hvd1F1YW50aXR5Q2hhbmdlciA9IEZlYXR1cmUuZW5hYmxlZChGZWF0dXJlRmxhZy5RVUFOVElUWV9DSEFOR0VSKSAmJiBFbnZpcm9ubWVudC5wbHVnaW4ucGFnZVR5cGUoKSA9PT0gJ2NhcnQnIHx8IEZlYXR1cmUuZW5hYmxlZChGZWF0dXJlRmxhZy5RVUFOVElUWV9DSEFOR0VSKSAmJiBGZWF0dXJlLnZlcnNpb24oRmVhdHVyZUZsYWcuUVVBTlRJVFlfQ0hBTkdFUikgPj0gMjtcbiAgICAgICAgaWYgKCFpdGVtLmlzX3BhcnRfb2ZfYnVuZGxlKSB7XG4gICAgICAgICAgICBpZiAoaSA8IGNhcnQubGVuZ3RoIC0gMSAmJiBjYXJ0W2kgKyAxXS5pc19wYXJ0X29mX2J1bmRsZSkge1xuICAgICAgICAgICAgICAgICRyb3cuaW5uZXJIVE1MICs9ICRpdGVtUmVtb3ZlcigncmVtb3ZlLWJvcmRlcicpO1xuICAgICAgICAgICAgICAgIGlmIChpdGVtLmltYWdlPy5bMF0gJiYgaXRlbS5pbWFnZT8uWzBdICE9PSAnKHVua25vd24pJykge1xuICAgICAgICAgICAgICAgICAgICAkcm93LmlubmVySFRNTCArPSBgPHRkIGNsYXNzPVwicHJvZHVjdC1pbWctdGQtYjBcIiBpZD1cInByb2R1Y3QtaW1nXCI+PGltZyBjbGFzcz1cInByb2R1Y3QtaW1nLXNpemVcIiBzcmM9XCIke2l0ZW0uaW1hZ2VbMF19XCIvPjwvdGQ+YDtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgJHJvdy5pbm5lckhUTUwgKz0gYFxuXHRcdFx0XHRcdCR7c2hvd1F1YW50aXR5Q2hhbmdlciA/ICRxdHlDaGFuZ2VyKCdidW5kbGUtbmFtZSByZW1vdmUtYm9yZGVyJykgOiAnJ31cblx0XHRcdFx0XHQ8dGQgY2xhc3M9XCJidW5kbGUtbmFtZVwiPiR7bGFiZWx9PC90ZD5cblx0XHRcdFx0XHQ8dGQgY2xhc3M9XCJidW5kbGUtbmFtZSBib2xkXCI+JHthbW91bnR9PC90ZD5cblx0XHRcdFx0YDtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgJHJvdy5pbm5lckhUTUwgKz0gJGl0ZW1SZW1vdmVyKCk7XG4gICAgICAgICAgICAgICAgaWYgKGl0ZW0uaW1hZ2U/LlswXSAmJiBpdGVtLmltYWdlPy5bMF0gIT09ICcodW5rbm93biknKSB7XG4gICAgICAgICAgICAgICAgICAgICRyb3cuaW5uZXJIVE1MICs9IGA8dGQgY2xhc3M9XCJwcm9kdWN0LWltZy10ZFwiIGlkPVwicHJvZHVjdC1pbWdcIj48aW1nIGNsYXNzPVwicHJvZHVjdC1pbWctc2l6ZVwiIHNyYz1cIiR7aXRlbS5pbWFnZVswXX1cIi8+PC90ZD5gO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkcm93LmlubmVySFRNTCArPSBgXG5cdFx0XHRcdFx0JHtzaG93UXVhbnRpdHlDaGFuZ2VyID8gJHF0eUNoYW5nZXIoJ25vbi1idW5kbGVkLWl0ZW0nKSA6ICcnfVxuXHRcdFx0XHRcdDx0ZCBjbGFzcz1cIm5vbi1idW5kbGVkLWl0ZW1cIj4ke2xhYmVsfTwvdGQ+XG5cdFx0XHRcdFx0PHRkIGNsYXNzPVwibm9uLWJ1bmRsZWQtaXRlbSBib2xkXCI+JHthbW91bnR9PC90ZD5cblx0XHRcdFx0YDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIGlmIChpdGVtLmlzX3BhcnRfb2ZfYnVuZGxlKSB7XG4gICAgICAgICAgICBpZiAoaSA8IGNhcnQubGVuZ3RoIC0gMSAmJiAhY2FydFtpICsgMV0uaXNfcGFydF9vZl9idW5kbGUgfHwgaSA9PT0gY2FydC5sZW5ndGggLSAxKSB7XG4gICAgICAgICAgICAgICAgaWYgKGl0ZW0uaW1hZ2U/LlswXSAmJiBpdGVtLmltYWdlPy5bMF0gIT09ICcodW5rbm93biknKSB7XG4gICAgICAgICAgICAgICAgICAgICRyb3cuaW5uZXJIVE1MICs9IGA8dGQgY2xhc3M9XCJtdXRlZCBwbC0zLzIgYmItMSBwcm9kdWN0LWltZy10ZFwiIGlkPVwicHJvZHVjdC1pbWdcIj48aW1nIGNsYXNzPVwiYnVuZGxlLWltZy1zaXplXCIgc3JjPVwiJHtpdGVtLmltYWdlWzBdfVwiLz48L3RkPmA7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICRyb3cuaW5uZXJIVE1MICs9IGBcblx0XHRcdFx0XHQ8dGQgY2xhc3M9XCJtdXRlZCBwbC0zLzIgYmItMVwiPiR7bGFiZWx9PC90ZD5cblx0XHRcdFx0XHQ8dGQgY2xhc3M9XCJtdXRlZCBwbC0zLzIgYmItMVwiPiR7YW1vdW50fTwvdGQ+XG5cdFx0XHRcdGA7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGlmIChpdGVtLmltYWdlPy5bMF0gJiYgaXRlbS5pbWFnZT8uWzBdICE9PSAnKHVua25vd24pJykge1xuICAgICAgICAgICAgICAgICAgICAkcm93LmlubmVySFRNTCArPSBgPHRkIGNsYXNzPVwibXV0ZWQgcGwtMy8yIGJ1bmRsZS1wYWRkaW5nIHByb2R1Y3QtaW1nLXRkLWIwXCIgaWQ9XCJwcm9kdWN0LWltZ1wiPjxpbWcgY2xhc3M9XCJidW5kbGUtaW1nLXNpemVcIiBzcmM9XCIke2l0ZW0uaW1hZ2VbMF19XCIvPjwvdGQ+YDtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgJHJvdy5pbm5lckhUTUwgKz0gYFxuXHRcdFx0XHRcdDx0ZCBjbGFzcz1cIm11dGVkIHBsLTMvMiBidW5kbGUtcGFkZGluZ1wiPiR7bGFiZWx9PC90ZD5cblx0XHRcdFx0XHQ8dGQgY2xhc3M9XCJtdXRlZCBwbC0zLzIgYnVuZGxlLXBhZGRpbmdcIj4ke2Ftb3VudH08L3RkPlxuXHRcdFx0XHRgO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIGlmIChpdGVtc0luQ2FydChjYXJ0KSA9PT0gMSB8fCBpID09PSBpdGVtc0luQ2FydChjYXJ0KSAtIDEpIHtcbiAgICAgICAgICAgIGNvbnN0ICRvbmUgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCd0cicpO1xuICAgICAgICAgICAgJG9uZS5jbGFzc05hbWUgPSAnb3JkZXItc3VtbWFyeS1pdGVtJztcbiAgICAgICAgICAgIGlmIChpdGVtLmlzX3BhcnRfb2ZfYnVuZGxlKSB7XG4gICAgICAgICAgICAgICAgaWYgKGl0ZW0uaW1hZ2U/LlswXSAmJiBpdGVtLmltYWdlPy5bMF0gIT09ICcodW5rbm93biknKSB7XG4gICAgICAgICAgICAgICAgICAgICRvbmUuaW5uZXJIVE1MICs9IGA8dGQgY2xhc3M9XCJtdXRlZCBwbC0zLzIgYmItMSBwcm9kdWN0LWltZy10ZCByZW1vdmUtYm9yZGVyXCIgaWQ9XCJwcm9kdWN0LWltZ1wiPjxpbWcgY2xhc3M9XCJidW5kbGUtaW1nLXNpemVcIiBzcmM9XCIke2l0ZW0uaW1hZ2VbMF19XCIvPjwvdGQ+YDtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgJG9uZS5pbm5lckhUTUwgKz0gYFxuXHRcdFx0XHRcdDx0ZCBjbGFzcz1cIm11dGVkIHBsLTMvMiBiYi0xIHJlbW92ZS1ib3JkZXJcIj4ke2xhYmVsfTwvdGQ+XG5cdFx0XHRcdFx0PHRkIGNsYXNzPVwibXV0ZWQgcGwtMy8yIGJiLTEgcmVtb3ZlLWJvcmRlclwiPiR7YW1vdW50fTwvdGQ+XG5cdFx0XHRcdGA7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICRvbmUuaW5uZXJIVE1MICs9ICRpdGVtUmVtb3ZlcigncmVtb3ZlLWJvcmRlcicpO1xuICAgICAgICAgICAgICAgIGlmIChpdGVtLmltYWdlPy5bMF0gJiYgaXRlbS5pbWFnZT8uWzBdICE9PSAnKHVua25vd24pJykge1xuICAgICAgICAgICAgICAgICAgICAkb25lLmlubmVySFRNTCArPSBgPHRkIGNsYXNzPVwicHJvZHVjdC1pbWctdGQgcmVtb3ZlLWJvcmRlclwiIGlkPVwicHJvZHVjdC1pbWdcIj48aW1nIGNsYXNzPVwicHJvZHVjdC1pbWctc2l6ZVwiIHNyYz1cIiR7aXRlbS5pbWFnZVswXX1cIi8+PC90ZD5gO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAkb25lLmlubmVySFRNTCArPSBgXG5cdFx0XHRcdFx0JHtzaG93UXVhbnRpdHlDaGFuZ2VyID8gJHF0eUNoYW5nZXIoJ25vbi1idW5kbGVkLWl0ZW0gcmVtb3ZlLWJvcmRlcicpIDogJyd9XG5cdFx0XHRcdFx0PHRkIGNsYXNzPVwibm9uLWJ1bmRsZWQtaXRlbSByZW1vdmUtYm9yZGVyXCI+JHtsYWJlbH08L3RkPlxuXHRcdFx0XHRcdDx0ZCBjbGFzcz1cIm5vbi1idW5kbGVkLWl0ZW0gcmVtb3ZlLWJvcmRlciBib2xkXCI+JHthbW91bnR9PC90ZD5cblx0XHRcdFx0YDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgICR0Ym9keS5wcmVwZW5kKCRvbmUpO1xuICAgICAgICAgICAgJHRib2R5TW9iaWxlLnByZXBlbmQoJG9uZS5jbG9uZU5vZGUodHJ1ZSkpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJHRib2R5LnByZXBlbmQoJHJvdyk7XG4gICAgICAgICAgICAkdGJvZHlNb2JpbGUucHJlcGVuZCgkcm93LmNsb25lTm9kZSh0cnVlKSk7XG4gICAgICAgIH1cbiAgICAgICAgJHRib2R5RXhpc3RpbmcucHJlcGVuZCgkcm93LmNsb25lTm9kZSh0cnVlKSk7XG4gICAgfVxufVxuZnVuY3Rpb24gY2xlYXJPcmRlclN1bW1hcnkoKSB7XG4gICAgZm9yIChjb25zdCAkaXRlbSBvZiAkcXNBbGwoJy5vcmRlci1zdW1tYXJ5LWl0ZW0nKSl7XG4gICAgICAgICRpdGVtLnJlbW92ZSgpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIG1ldGFEYXRhUm93c0hUTUwoaXRlbSkge1xuICAgIGlmICghaXRlbS5tZXRhX2RhdGEpIHtcbiAgICAgICAgcmV0dXJuICcnO1xuICAgIH1cbiAgICBsZXQgaHRtbCA9ICcnO1xuICAgIGZvciAoY29uc3QgbWV0YSBvZiBpdGVtLm1ldGFfZGF0YSl7XG4gICAgICAgIGNvbnN0IGtleVRleHQgPSBjYXBpdGFsaXplRmlyc3RMZXR0ZXIobWV0YS5rZXkucmVwbGFjZSgvXy9nLCAnICcpKTtcbiAgICAgICAgaHRtbCArPSBgPGJyPjxzcGFuIGNsYXNzPVwibXV0ZWQgbWwtaGFsZlwiPjxiPiR7a2V5VGV4dH08L2I+OiAke21ldGEudmFsdWUgfHwgJyhub25lKSd9PC9zcGFuPmA7XG4gICAgfVxuICAgIHJldHVybiBodG1sO1xufVxuZnVuY3Rpb24gZm9ybWF0dGVkSXRlbURhdGFIVE1MKGl0ZW0pIHtcbiAgICBpZiAoIWl0ZW0uZm9ybWF0dGVkX2l0ZW1fZGF0YSkge1xuICAgICAgICByZXR1cm4gJyc7XG4gICAgfVxuICAgIHJldHVybiBpdGVtLmZvcm1hdHRlZF9pdGVtX2RhdGEucmVwbGFjZSgvJm5ic3A7L2csICcnKTtcbn1cbmZ1bmN0aW9uIGdyZWF0ZXJUaGFuKGEsIGIpIHtcbiAgICBjb25zdCBbbWFqb3JBLCBtaW5vckEsIHBhdGNoQV0gPSBTdHJpbmcoYSkuc3BsaXQoJy4nKS5tYXAoKG4pPT5OdW1iZXIobilcbiAgICApO1xuICAgIGNvbnN0IFttYWpvckIsIG1pbm9yQiwgcGF0Y2hCXSA9IFN0cmluZyhiKS5zcGxpdCgnLicpLm1hcCgobik9Pk51bWJlcihuKVxuICAgICk7XG4gICAgY29uc3QgcmVzdWx0ID0gbWFqb3JBIC0gbWFqb3JCIHx8IG1pbm9yQSAtIG1pbm9yQiB8fCBwYXRjaEEgLSBwYXRjaEI7XG4gICAgcmV0dXJuIHJlc3VsdCA+IDA7XG59XG5mdW5jdGlvbiBpbml0U3RyaXBlQnV0dG9uKCkge1xuICAgIHNob3dTdHJpcGVQYXltZW50T3B0aW9uKCk7XG4gICAgJHFzKCcjc3RyaXBlLW9wdGlvbicpPy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHNlbGVjdFN0cmlwZVBheW1lbnRNZXRob2QpO1xuICAgIHN0b3JlLnN1YnNjcmliZSgoKT0+e1xuICAgICAgICByZW5kZXJTdHJpcGVCdXR0b25EaXNwbGF5KFBlYWNoUGF5Q3VzdG9tZXIucHJlZmVycmVkUGF5bWVudE1ldGhvZCgpLCBFbnZpcm9ubWVudC5tb2RhbFVJLnBhZ2UoKSwgRW52aXJvbm1lbnQubW9kYWxVSS5sb2FkaW5nTW9kZSgpKTtcbiAgICAgICAgcmVuZGVyU3RyaXBlUGF5bWVudE1ldGhvZChQZWFjaFBheUN1c3RvbWVyLnByZWZlcnJlZFBheW1lbnRNZXRob2QoKSwgRW52aXJvbm1lbnQubW9kYWxVSS5wYWdlKCkpO1xuICAgICAgICByZW5kZXJTdHJpcGVCdXR0b25Mb2FkaW5nKFBlYWNoUGF5Q3VzdG9tZXIucHJlZmVycmVkUGF5bWVudE1ldGhvZCgpLCBFbnZpcm9ubWVudC5tb2RhbFVJLmxvYWRpbmdNb2RlKCkpO1xuICAgIH0pO1xufVxuZnVuY3Rpb24gcmVuZGVyU3RyaXBlQnV0dG9uRGlzcGxheShtZXRob2QsIHBhZ2UsIGxvYWRpbmdNb2RlKSB7XG4gICAgaWYgKG1ldGhvZCA9PT0gJ3N0cmlwZScgJiYgcGFnZSA9PT0gJ3BheW1lbnQnKSB7XG4gICAgICAgICRxc0FsbCgnLnN0cmlwZS1idG4tY29udGFpbmVyJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpXG4gICAgICAgICk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzQWxsKCcuc3RyaXBlLWJ0bi1jb250YWluZXInLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5jbGFzc0xpc3QuYWRkKCdoaWRlJylcbiAgICAgICAgKTtcbiAgICB9XG4gICAgaWYgKG1ldGhvZCA9PT0gJ3N0cmlwZScgJiYgcGFnZSA9PT0gJ3BheW1lbnQnICYmIGxvYWRpbmdNb2RlICE9PSAnbG9hZGluZycpIHtcbiAgICAgICAgJHFzQWxsKCcuc3RyaXBlLWJ0bicsICgkZWxlbWVudCk9PiRlbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKVxuICAgICAgICApO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxc0FsbCgnLnN0cmlwZS1idG4nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5jbGFzc0xpc3QuYWRkKCdoaWRlJylcbiAgICAgICAgKTtcbiAgICB9XG59XG5mdW5jdGlvbiByZW5kZXJTdHJpcGVCdXR0b25Mb2FkaW5nKG1ldGhvZCwgbW9kZSkge1xuICAgIGlmIChtZXRob2QgIT09ICdzdHJpcGUnKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKG1vZGUgPT09ICdmaW5pc2hlZCcpIHtcbiAgICAgICAgJHFzQWxsKCcuc3RyaXBlLWJ0bicsICgkZWxlbWVudCk9PiRlbGVtZW50LmRpc2FibGVkID0gZmFsc2VcbiAgICAgICAgKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICAkcXNBbGwoJy5zdHJpcGUtYnRuJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuZGlzYWJsZWQgPSB0cnVlXG4gICAgICAgICk7XG4gICAgfVxuICAgIGlmIChtb2RlID09PSAnbG9hZGluZycpIHtcbiAgICAgICAgJHFzQWxsKCcuc3RyaXBlLWJ0bi1zaGlwcGluZy1zcGlubmVyJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpXG4gICAgICAgICk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzQWxsKCcuc3RyaXBlLWJ0bi1zaGlwcGluZy1zcGlubmVyJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuY2xhc3NMaXN0LmFkZCgnaGlkZScpXG4gICAgICAgICk7XG4gICAgfVxuICAgIGlmIChtb2RlID09PSAncHJvY2Vzc2luZycpIHtcbiAgICAgICAgJHFzQWxsKCcuc3RyaXBlLWJ0biA+IC5idXR0b24tdGV4dCcsICgkZWxlbWVudCk9PiRlbGVtZW50LmlubmVySFRNTCA9IGdldExvY2FsZVRleHQoJ3Byb2Nlc3NpbmcnKVxuICAgICAgICApO1xuICAgICAgICAkcXNBbGwoJy5zdHJpcGUtYnRuLXNwaW5uZXInLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJylcbiAgICAgICAgKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICAkcXNBbGwoJy5zdHJpcGUtYnRuID4gLmJ1dHRvbi10ZXh0JywgKCRlbGVtZW50KT0+JGVsZW1lbnQuaW5uZXJIVE1MID0gYCR7Z2V0TG9jYWxlVGV4dCgncGF5Jyl9ICR7Zm9ybWF0Q3VycmVuY3lTdHJpbmcoRGVmYXVsdENhcnQudG90YWwoKSl9YFxuICAgICAgICApO1xuICAgICAgICAkcXNBbGwoJy5zdHJpcGUtYnRuLXNwaW5uZXInLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5jbGFzc0xpc3QuYWRkKCdoaWRlJylcbiAgICAgICAgKTtcbiAgICB9XG59XG5mdW5jdGlvbiBzaG93U3RyaXBlUGF5bWVudE9wdGlvbigpIHtcbiAgICAkcXMoJyNzdHJpcGUtb3B0aW9uJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICBmb3IgKGNvbnN0ICRjb250YWluZXIgb2YgJHFzQWxsKCcuc3RyaXBlLWJ0bi1jb250YWluZXInKSl7XG4gICAgICAgICRjb250YWluZXIuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHNlbGVjdFN0cmlwZVBheW1lbnRNZXRob2QoKSB7XG4gICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlUHJlZmVycmVkUGF5bWVudE1ldGhvZCgnc3RyaXBlJykpO1xufVxuZnVuY3Rpb24gcmVuZGVyU3RyaXBlUGF5bWVudE1ldGhvZChtZXRob2QsIHBhZ2UpIHtcbiAgICBpZiAobWV0aG9kID09PSAnc3RyaXBlJyAmJiBwYWdlID09PSAncGF5bWVudCcpIHtcbiAgICAgICAgJHFzKCcjc3RyaXBlLXBtJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuY2hlY2tlZCA9IHRydWVcbiAgICAgICAgKTtcbiAgICAgICAgJHFzKCcjc3RyaXBlLXBtJyk/LnNldEF0dHJpYnV0ZSgnY2hlY2tlZCcsICd0cnVlJyk7XG4gICAgICAgICRxcygnI3N0cmlwZS1vcHRpb24nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5iYWNrZ3JvdW5kQ29sb3IgPSAnd2hpdGUnXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI2NhcmQtZWxlbWVudCcpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzKCcjc3RyaXBlLXBtJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuY2hlY2tlZCA9IGZhbHNlXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI3N0cmlwZS1wbScpPy5yZW1vdmVBdHRyaWJ1dGUoJ2NoZWNrZWQnKTtcbiAgICAgICAgJHFzKCcjc3RyaXBlLW9wdGlvbicsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLmJhY2tncm91bmRDb2xvciA9ICcjZjRmNGY0J1xuICAgICAgICApO1xuICAgICAgICAkcXMoJyNjYXJkLWVsZW1lbnQnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRTdHJpcGVQYXltZW50UmVxdWVzdChtZXNzYWdlLCBzdHJpcGVTZXJ2aWNlLCBvcmRlclNlcnZpY2UpIHtcbiAgICBpZiAoIUZlYXR1cmUuZW5hYmxlZChGZWF0dXJlRmxhZy5TVFJJUEVfUEFZTUVOVF9SRVFVRVNUKSB8fCBFbnZpcm9ubWVudC5wbHVnaW4ucGFnZVR5cGUoKSA9PT0gJ3Byb2R1Y3QnKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3QgaW5pdE1lc3NhZ2UgPSB7XG4gICAgICAgIGV2ZW50OiAncHAtaW5pdC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0JyxcbiAgICAgICAgc3RyaXBlOiB7XG4gICAgICAgICAgICBsb2NhbGU6IG1lc3NhZ2UuYnJvd3NlckxvY2FsZSxcbiAgICAgICAgICAgIGxpdmU6ICFpc0RldkVudmlyb25tZW50KGdldEJhc2VVUkwoTWVyY2hhbnRDb25maWd1cmF0aW9uLmhvc3ROYW1lKCksIEVudmlyb25tZW50LnRlc3RNb2RlKCkpKVxuICAgICAgICB9LFxuICAgICAgICBjdXJyZW5jeUNvZGU6IE1lcmNoYW50Q29uZmlndXJhdGlvbi5jdXJyZW5jeS5jb2RlKCksXG4gICAgICAgIGNhcnRDYWxjdWxhdGlvblJlY29yZDogc3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNcbiAgICB9O1xuICAgIHdpbmRvdy50b3A/LnBvc3RNZXNzYWdlKGluaXRNZXNzYWdlLCAnKicpO1xuICAgIG9uV2luZG93RGF0YUZldGNoKCdwcC1zdHJpcGUtcGF5bWVudC1yZXF1ZXN0LWFkZHJlc3MtY2hhbmdlJywgaGFuZGxlU3RyaXBlUGF5bWVudFJlcXVlc3RBZGRyZXNzQ2hhbmdlKTtcbiAgICBvbldpbmRvd0RhdGFGZXRjaCgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1zaGlwcGluZy1jaGFuZ2UnLCBoYW5kbGVTdHJpcGVQYXltZW50UmVxdWVzdFNoaXBwaW5nQ2hhbmdlKTtcbiAgICBvbldpbmRvd0RhdGFGZXRjaCgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1wcm9jZXNzLXBheW1lbnQnLCBhc3luYyAocmVxdWVzdCk9PmF3YWl0IGhhbmRsZVN0cmlwZVBheW1lbnRSZXF1ZXN0UHJvY2Vzc1BheW1lbnQocmVxdWVzdCwgc3RyaXBlU2VydmljZSwgb3JkZXJTZXJ2aWNlKVxuICAgICk7XG4gICAgY29uc3QgcHJldmlvdXNVcGRhdGVEYXRhID0gJyc7XG4gICAgY29uc3QgdW5zdWJzY3JpYmVQYXltZW50UmVxdWVzdFVwZGF0ZXMgPSBzdG9yZS5zdWJzY3JpYmUoKCk9PntcbiAgICAgICAgY29uc3QgcGF5bWVudFJlcXVlc3REYXRhVXBkYXRlID0gZ2V0U3RyaXBlUGF5bWVudFJlcXVlc3RVcGRhdGUoKTtcbiAgICAgICAgY29uc3QgdXBkYXRlRGF0YSA9IEpTT04uc3RyaW5naWZ5KHBheW1lbnRSZXF1ZXN0RGF0YVVwZGF0ZSk7XG4gICAgICAgIGlmIChwcmV2aW91c1VwZGF0ZURhdGEgIT09IHVwZGF0ZURhdGEpIHtcbiAgICAgICAgICAgIHdpbmRvdy50b3A/LnBvc3RNZXNzYWdlKHBheW1lbnRSZXF1ZXN0RGF0YVVwZGF0ZSwgJyonKTtcbiAgICAgICAgfVxuICAgIH0pO1xuICAgIG9uV2luZG93TWVzc2FnZSgncHAtc3RyaXBlLXBheW1lbnQtcmVxdWVzdC1zdG9wJywgdW5zdWJzY3JpYmVQYXltZW50UmVxdWVzdFVwZGF0ZXMpO1xufVxuZnVuY3Rpb24gZ2V0U3RyaXBlUGF5bWVudFJlcXVlc3RVcGRhdGUoKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgZXZlbnQ6ICdwcC11cGRhdGUtc3RyaXBlLXBheW1lbnQtcmVxdWVzdCcsXG4gICAgICAgIGN1cnJlbmN5Q29kZTogTWVyY2hhbnRDb25maWd1cmF0aW9uLmN1cnJlbmN5LmNvZGUoKSxcbiAgICAgICAgY2FydENhbGN1bGF0aW9uUmVjb3JkOiBzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0c1xuICAgIH07XG59XG5hc3luYyBmdW5jdGlvbiBoYW5kbGVTdHJpcGVQYXltZW50UmVxdWVzdFByb2Nlc3NQYXltZW50KHJlcXVlc3QsIHN0cmlwZVNlcnZpY2UsIG9yZGVyU2VydmljZSkge1xuICAgIGNvbnN0IHN0cmlwZUN1c3RvbWVySWQgPSBhd2FpdCBzdHJpcGVTZXJ2aWNlLmNyZWF0ZVN0cmlwZUN1c3RvbWVyKHJlcXVlc3QudG9rZW4uaWQsIHtcbiAgICAgICAgbmFtZTogcmVxdWVzdC5wYXllck5hbWUsXG4gICAgICAgIGVtYWlsOiByZXF1ZXN0LnBheWVyRW1haWwsXG4gICAgICAgIHBob25lOiByZXF1ZXN0LnBheWVyUGhvbmVcbiAgICB9KTtcbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDdXN0b21lcih7XG4gICAgICAgIGVtYWlsOiByZXF1ZXN0LnBheWVyRW1haWwsXG4gICAgICAgIHBob25lOiByZXF1ZXN0LnBheWVyUGhvbmUsXG4gICAgICAgIG5hbWVfZmlyc3Q6IHJlcXVlc3QucGF5ZXJOYW1lLnNwbGl0KCcgJylbMF0gPz8gJycsXG4gICAgICAgIG5hbWVfbGFzdDogcmVxdWVzdC5wYXllck5hbWUuc3BsaXQoJyAnKVsxXSA/PyAnJyxcbiAgICAgICAgYWRkcmVzczE6IHJlcXVlc3Quc2hpcHBpbmdBZGRyZXNzLmFkZHJlc3NMaW5lWzBdLFxuICAgICAgICBhZGRyZXNzMjogcmVxdWVzdC5zaGlwcGluZ0FkZHJlc3MuYWRkcmVzc0xpbmVbMV0gPz8gJycsXG4gICAgICAgIGNpdHk6IHJlcXVlc3Quc2hpcHBpbmdBZGRyZXNzLmNpdHksXG4gICAgICAgIHN0YXRlOiByZXF1ZXN0LnNoaXBwaW5nQWRkcmVzcy5yZWdpb24sXG4gICAgICAgIGNvdW50cnk6IHJlcXVlc3Quc2hpcHBpbmdBZGRyZXNzLmNvdW50cnksXG4gICAgICAgIHBvc3RhbDogcmVxdWVzdC5zaGlwcGluZ0FkZHJlc3MucG9zdGFsQ29kZSxcbiAgICAgICAgY2FyZDoge1xuICAgICAgICAgICAgYnJhbmQ6IHJlcXVlc3QudG9rZW4uY2FyZC5icmFuZCxcbiAgICAgICAgICAgIGxhc3Q0OiByZXF1ZXN0LnRva2VuLmNhcmQubGFzdDRcbiAgICAgICAgfSxcbiAgICAgICAgc3RyaXBlX2N1c3RvbWVyX2lkOiBzdHJpcGVDdXN0b21lcklkLFxuICAgICAgICBwYXltZW50X29wdGlvbjogJ3N0cmlwZSdcbiAgICB9KSk7XG4gICAgaWYgKCFhd2FpdCB2YWxpZGF0ZUFkZHJlc3MoKSkge1xuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgc3RhdHVzOiAnaW52YWxpZF9zaGlwcGluZ19hZGRyZXNzJ1xuICAgICAgICB9O1xuICAgIH1cbiAgICBzYXZlQ3VzdG9tZXJUb0Jyb3dzZXIoc3RyaXBlQ3VzdG9tZXJJZCwgcmVxdWVzdC50b2tlbi5jYXJkLmJyYW5kLCByZXF1ZXN0LnRva2VuLmNhcmQubGFzdDQsICdzdHJpcGUnKTtcbiAgICB0cnkge1xuICAgICAgICBjb25zdCBvcmRlciA9IGF3YWl0IG9yZGVyU2VydmljZS5wbGFjZU9yZGVyKCk7XG4gICAgICAgIGNvbnN0IHBheW1lbnRSZXN1bHQgPSBhd2FpdCBzdHJpcGVTZXJ2aWNlLnByb2Nlc3NQYXltZW50KG9yZGVyKTtcbiAgICAgICAgaWYgKCEoYXdhaXQgb3JkZXJTZXJ2aWNlLnNldFBheW1lbnRTdGF0dXMoUGVhY2hQYXlPcmRlci5zZXNzaW9uSWQoKSwgdHJ1ZSkpLm9rKSB7XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIHN0YXR1czogJ2ZhaWwnXG4gICAgICAgICAgICB9O1xuICAgICAgICB9XG4gICAgICAgIGlmICghcGF5bWVudFJlc3VsdCkge1xuICAgICAgICAgICAgYXdhaXQgb3JkZXJTZXJ2aWNlLnNldE9yZGVyU3RhdHVzKG9yZGVyLCAnd2MtZmFpbGVkJywge1xuICAgICAgICAgICAgICAgIHN0cmlwZUN1c3RvbWVySWRcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgcmV0dXJuIHtcbiAgICAgICAgICAgICAgICBzdGF0dXM6ICdmYWlsJ1xuICAgICAgICAgICAgfTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBvcmRlclN0YXR1c1Jlc3VsdCA9IGF3YWl0IG9yZGVyU2VydmljZS5zZXRPcmRlclN0YXR1cyhvcmRlciwgJ3djLXByb2Nlc3NpbmcnLCB7XG4gICAgICAgICAgICBzdHJpcGVDdXN0b21lcklkXG4gICAgICAgIH0pO1xuICAgICAgICBpZiAoIW9yZGVyU3RhdHVzUmVzdWx0KSB7XG4gICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgIHN0YXR1czogJ2ZhaWwnXG4gICAgICAgICAgICB9O1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICBzdGF0dXM6ICdzdWNjZXNzJyxcbiAgICAgICAgICAgIHJlZGlyZWN0VVJMOiBvcmRlclN0YXR1c1Jlc3VsdFxuICAgICAgICB9O1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcihgU3RyaXBlIHBheW1lbnQgcmVxdWVzdCBwcm9jZXNzIHBheW1lbnQgZmFpbGVkIG9uICR7TWVyY2hhbnRDb25maWd1cmF0aW9uLmhvc3ROYW1lKCl9LiBFcnJvcjogJHtlcnJvci5tZXNzYWdlfWApKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgc3RhdHVzOiAnZmFpbCdcbiAgICAgICAgfTtcbiAgICB9XG59XG5hc3luYyBmdW5jdGlvbiBoYW5kbGVTdHJpcGVQYXltZW50UmVxdWVzdEFkZHJlc3NDaGFuZ2UocmVxdWVzdCkge1xuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUN1c3RvbWVyKHtcbiAgICAgICAgLi4uUGVhY2hQYXlDdXN0b21lci5kYXRhKCksXG4gICAgICAgIGFkZHJlc3MxOiByZXF1ZXN0LmFkZHJlc3NMaW5lWzBdID8/ICcnLFxuICAgICAgICBhZGRyZXNzMjogcmVxdWVzdC5hZGRyZXNzTGluZVsxXSA/PyAnJyxcbiAgICAgICAgY2l0eTogcmVxdWVzdC5jaXR5ID8/ICcnLFxuICAgICAgICBwb3N0YWw6IHJlcXVlc3QucG9zdGFsQ29kZSA/PyAnJyxcbiAgICAgICAgc3RhdGU6IHJlcXVlc3QucmVnaW9uID8/ICcnLFxuICAgICAgICBjb3VudHJ5OiByZXF1ZXN0LmNvdW50cnkgPz8gJydcbiAgICB9KSk7XG4gICAgYXdhaXQgcmVxdWVzdENhcnRDYWxjdWxhdGlvbigpO1xuICAgIHJldHVybiBnZXRTdHJpcGVQYXltZW50UmVxdWVzdFVwZGF0ZSgpO1xufVxuYXN5bmMgZnVuY3Rpb24gaGFuZGxlU3RyaXBlUGF5bWVudFJlcXVlc3RTaGlwcGluZ0NoYW5nZShyZXF1ZXN0KSB7XG4gICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlQ2FydFBhY2thZ2VTaGlwcGluZ01ldGhvZCh7XG4gICAgICAgIGNhcnRLZXk6ICcwJyxcbiAgICAgICAgc2hpcHBpbmdQYWNrYWdlS2V5OiAnMCcsXG4gICAgICAgIHBhY2thZ2VNZXRob2RJZDogcmVxdWVzdC5pZFxuICAgIH0pKTtcbiAgICBhd2FpdCByZXF1ZXN0Q2FydENhbGN1bGF0aW9uKCk7XG4gICAgcmV0dXJuIGdldFN0cmlwZVBheW1lbnRSZXF1ZXN0VXBkYXRlKCk7XG59XG5hc3luYyBmdW5jdGlvbiBpbml0U3RyaXBlU3VwcG9ydChtZXNzYWdlMSwgb3JkZXJTZXJ2aWNlKSB7XG4gICAgY29uc3QgY29ubmVjdGVkU3RyaXBlQWNjb3VudCA9IG1lc3NhZ2UxLnBocERhdGEuY29ubmVjdGVkX3N0cmlwZV9hY2NvdW50O1xuICAgIGNvbnN0IGlzRGV2TW9kZSA9IGlzRGV2RW52aXJvbm1lbnQoZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkpO1xuICAgIGlmIChncmVhdGVyVGhhbihFbnZpcm9ubWVudC5wbHVnaW4udmVyc2lvbigpLCAnMS41Ny4xJykgJiYgIWNvbm5lY3RlZFN0cmlwZUFjY291bnQgJiYgIUVudmlyb25tZW50LnRlc3RNb2RlKCkpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpZiAoIUZlYXR1cmUuZW5hYmxlZChGZWF0dXJlRmxhZy5TVFJJUEUpKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgY29uc3Qga2V5ID0gaXNEZXZNb2RlID8gJ3BrX3Rlc3RfQ25MMmtBNTJWNWRScVpiamxKMHNaMmdyMDB1QnJPRW1RUScgOiAncGtfbGl2ZV9vUk9uSVFEdWV4SFpwbkVPY1VmZjNDUnowMGFzYU9PQ0FMJztcbiAgICBsZXQgb3B0aW9ucyA9IHtcbiAgICAgICAgbG9jYWxlOiBtZXNzYWdlMT8uYnJvd3NlckxvY2FsZSA/PyAnYXV0bydcbiAgICB9O1xuICAgIGlmIChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSAhPT0gJ3dvby5wZWFjaHBheS5hcHAnICYmIE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpICE9PSAnc2hvcC5wZWFjaHBheS5hcHAnKSB7XG4gICAgICAgIGNvbnN0IHN0cmlwZUFjY291bnQgPSBhd2FpdCBmZXRjaFN0cmlwZUFjY291bnQoTWVyY2hhbnRDb25maWd1cmF0aW9uLmhvc3ROYW1lKCkpO1xuICAgICAgICBpZiAoc3RyaXBlQWNjb3VudCkge1xuICAgICAgICAgICAgb3B0aW9ucyA9IHtcbiAgICAgICAgICAgICAgICAuLi5vcHRpb25zLFxuICAgICAgICAgICAgICAgIHN0cmlwZUFjY291bnRcbiAgICAgICAgICAgIH07XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29uc3Qgc3RyaXBlRm9yQ2hlY2tpbmdQYXltZW50SW50ZW50ID0gU3RyaXBlKGtleSwgb3B0aW9ucyk7XG4gICAgY29uc3Qgc3RyaXBlID0gU3RyaXBlKGtleSwge1xuICAgICAgICBsb2NhbGU6IG1lc3NhZ2UxLmJyb3dzZXJMb2NhbGUgPz8gJ2F1dG8nXG4gICAgfSk7XG4gICAgY29uc3QgZWxlbWVudHMgPSBzdHJpcGUuZWxlbWVudHMoKTtcbiAgICBjb25zdCBzdHlsZSA9IHtcbiAgICAgICAgYmFzZToge1xuICAgICAgICAgICAgY29sb3I6ICcjMzMzJyxcbiAgICAgICAgICAgIGZvbnRGYW1pbHk6ICdcIkhlbHZldGljYSBOZXVlXCIsIEhlbHZldGljYSwgc2Fucy1zZXJpZicsXG4gICAgICAgICAgICBmb250U21vb3RoaW5nOiAnYW50aWFsaWFzZWQnLFxuICAgICAgICAgICAgZm9udFNpemU6ICcxOHB4JyxcbiAgICAgICAgICAgICc6OnBsYWNlaG9sZGVyJzoge1xuICAgICAgICAgICAgICAgIGNvbG9yOiAnIzk5OSdcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSxcbiAgICAgICAgaW52YWxpZDoge1xuICAgICAgICAgICAgY29sb3I6ICcjZmE3NTVhJyxcbiAgICAgICAgICAgIGljb25Db2xvcjogJyNmYTc1NWEnXG4gICAgICAgIH1cbiAgICB9O1xuICAgIGNvbnN0ICRjYXJkID0gZWxlbWVudHMuY3JlYXRlKCdjYXJkJywge1xuICAgICAgICBzdHlsZSxcbiAgICAgICAgaGlkZVBvc3RhbENvZGU6IHRydWVcbiAgICB9KTtcbiAgICAkY2FyZC5tb3VudCgnI2NhcmQtZWxlbWVudCcpO1xuICAgICRjYXJkLm9uKCdjaGFuZ2UnLCAoZXZlbnQpPT57XG4gICAgICAgICRxcygnI2NhcmQtZXJyb3JzJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBldmVudC5lcnJvcj8ubWVzc2FnZSA/PyAnJ1xuICAgICAgICApO1xuICAgIH0pO1xuICAgIGNvbnN0IHN0cmlwZVNlcnZpY2UgPSB7XG4gICAgICAgICRjYXJkLFxuICAgICAgICBlbGVtZW50cyxcbiAgICAgICAgc3RyaXBlLFxuICAgICAgICBzdHJpcGVGb3JQYXltZW50SW50ZW50OiBzdHJpcGVGb3JDaGVja2luZ1BheW1lbnRJbnRlbnQsXG4gICAgICAgIGNyZWF0ZVN0cmlwZUN1c3RvbWVyOiBhZGRDYXJkVG9TdHJpcGVDdXN0b21lcixcbiAgICAgICAgcHJvY2Vzc1BheW1lbnQ6IGFzeW5jIChvcmRlcik9PmF3YWl0IGhhbmRsZVN0cmlwZVBheW1lbnQob3JkZXIpXG4gICAgfTtcbiAgICBjb25zdCBoYW5kbGVJbmplY3RlZFByb2Nlc3NQYXltZW50ID0gYXN5bmMgKGV2ZW50KT0+e1xuICAgICAgICBpZiAoIWNoZWNrUmVxdWlyZWRGaWVsZHMoKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIGF3YWl0IGhhbmRsZVByb2Nlc3NQYXltZW50KGV2ZW50LCBzdHJpcGVTZXJ2aWNlLCBvcmRlclNlcnZpY2UpO1xuICAgIH07XG4gICAgaW5pdFN0cmlwZUJ1dHRvbigpO1xuICAgIGluaXRTdHJpcGVQYXltZW50UmVxdWVzdChtZXNzYWdlMSwgc3RyaXBlU2VydmljZSwgb3JkZXJTZXJ2aWNlKTtcbiAgICBvbldpbmRvd01lc3NhZ2UoJzNEUy1hdXRoZW50aWNhdGlvbi1jb21wbGV0ZScsIGFzeW5jIChtZXNzYWdlKT0+YXdhaXQgb24zRFNDb21wbGV0ZShtZXNzYWdlLnBheW1lbnRJbnRlbnRDbGllbnRTZWNyZXQsIHN0cmlwZVNlcnZpY2UsIG9yZGVyU2VydmljZSlcbiAgICApO1xuICAgIG9uV2luZG93TWVzc2FnZSgnc3VibWl0UGF5bWVudCcsIGFzeW5jIChtZXNzYWdlKT0+e1xuICAgICAgICBHTE9CQUwuY29tcGxldGVkT3JkZXIgPSBtZXNzYWdlLm9yZGVyO1xuICAgICAgICBhd2FpdCBsZWdhY3lIYW5kbGVTdHJpcGVQYXltZW50KG1lc3NhZ2Uub3JkZXIsIG9yZGVyU2VydmljZSk7XG4gICAgfSk7XG4gICAgJHFzKCcjcHAtcGF5Jyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgaGFuZGxlSW5qZWN0ZWRQcm9jZXNzUGF5bWVudCk7XG4gICAgJHFzKCcjcHAtcGF5LW1vYmlsZScpPy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGhhbmRsZUluamVjdGVkUHJvY2Vzc1BheW1lbnQpO1xuICAgICRxcygnI3BwLXBheS1leGlzdGluZycpPy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGhhbmRsZUluamVjdGVkUHJvY2Vzc1BheW1lbnQpO1xufVxuYXN5bmMgZnVuY3Rpb24gaGFuZGxlUHJvY2Vzc1BheW1lbnQoZXZlbnQsIHN0cmlwZVNlcnZpY2UsIG9yZGVyU2VydmljZSkge1xuICAgIGNvbnN0IHsgJGNhcmQgLCBzdHJpcGUgIH0gPSBzdHJpcGVTZXJ2aWNlO1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbFByb2Nlc3NpbmcoKSk7XG4gICAgaWYgKCFFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICBjb25zdCByZXN1bHQgPSBhd2FpdCBzdHJpcGUuY3JlYXRlVG9rZW4oJGNhcmQpO1xuICAgICAgICAgICAgaWYgKHJlc3VsdC5lcnJvcikge1xuICAgICAgICAgICAgICAgIGhhbmRsZVRva2VuRXJyb3IocmVzdWx0LmVycm9yKTtcbiAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVDdXN0b21lclN0cmlwZUlkKGF3YWl0IGFkZENhcmRUb1N0cmlwZUN1c3RvbWVyKHJlc3VsdC50b2tlbi5pZCwgUGVhY2hQYXlDdXN0b21lci5zdHJpcGVEZXRhaWxzKCkpKSk7XG4gICAgICAgICAgICBzYXZlQ3VzdG9tZXJUb0Jyb3dzZXIoUGVhY2hQYXlDdXN0b21lci5zdHJpcGVJZCgpLCByZXN1bHQudG9rZW4uY2FyZC5icmFuZCwgcmVzdWx0LnRva2VuLmNhcmQubGFzdDQsICdzdHJpcGUnKTtcbiAgICAgICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIGlmIChlcnJvciBpbnN0YW5jZW9mIEVycm9yKSB7XG4gICAgICAgICAgICAgICAgY2FwdHVyZVNlbnRyeUV4Y2VwdGlvbihuZXcgRXJyb3IoYEZhaWxlZCB0b2tlbml6aW5nIG9yIGNyZWF0aW5nIGEgbmV3IHN0cmlwZSBjdXN0b21lciBvbiAke01lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpfSBFcnJvcjogJHtlcnJvci5tZXNzYWdlfWApKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICB9XG4gICAgb3JkZXJTZXJ2aWNlLmRlcHJlY2F0ZWQucGxhY2VPcmRlcigpO1xufVxuYXN5bmMgZnVuY3Rpb24gZmV0Y2hTdHJpcGVBY2NvdW50KGRvbWFpbikge1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyBgYXBpL3YxL21lcmNoYW50cy8ke2RvbWFpbn0vc3RyaXBlLWFjY291bnRgKTtcbiAgICBpZiAocmVzcG9uc2Uuc3RhdHVzICE9PSAyMDApIHtcbiAgICAgICAgcmV0dXJuICcnO1xuICAgIH1cbiAgICByZXR1cm4gcmVzcG9uc2UudGV4dCgpO1xufVxuZnVuY3Rpb24gaGFuZGxlVG9rZW5FcnJvcihlcnJvcikge1xuICAgICRxcygnI2NhcmQtZXJyb3JzJywgKCRlbGVtZW50KT0+JGVsZW1lbnQudGV4dENvbnRlbnQgPSBlcnJvci5tZXNzYWdlXG4gICAgKTtcbiAgICBzdG9yZS5kaXNwYXRjaChzdG9wTW9kYWxMb2FkaW5nKCkpO1xufVxuYXN5bmMgZnVuY3Rpb24gb24zRFNDb21wbGV0ZShwYXltZW50SW50ZW50Q2xpZW50U2VjcmV0LCBzdHJpcGVTZXJ2aWNlLCBvcmRlclNlcnZpY2UpIHtcbiAgICBjb25zdCB7IHN0cmlwZUZvclBheW1lbnRJbnRlbnQgLCBzdHJpcGUgIH0gPSBzdHJpcGVTZXJ2aWNlO1xuICAgICRxcygnI3N0cmlwZS0zRFMtbW9kYWwnKT8ucmVtb3ZlKCk7XG4gICAgY29uc3QgZ2V0UGF5bWVudEludGVudCA9IGFzeW5jICgpPT57XG4gICAgICAgIGNvbnN0IGRvbWFpbiA9IGxvY2F0aW9uLmhvc3RuYW1lO1xuICAgICAgICBjb25zdCBpc091clN0b3JlID0gZG9tYWluID09PSAnd29vLnBlYWNocGF5LmFwcCcgfHwgZG9tYWluID09PSAnc2hvcC5wZWFjaHBheS5hcHAnIHx8IGRvbWFpbiA9PT0gJ2xvY2FsaG9zdCcgfHwgZG9tYWluID09PSAnd29vLnN0b3JlLmxvY2FsJyB8fCBkb21haW4gPT09ICdzdG9yZS5sb2NhbCc7XG4gICAgICAgIGlmIChpc091clN0b3JlKSB7XG4gICAgICAgICAgICByZXR1cm4gYXdhaXQgc3RyaXBlLnJldHJpZXZlUGF5bWVudEludGVudChwYXltZW50SW50ZW50Q2xpZW50U2VjcmV0KTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBhd2FpdCBzdHJpcGVGb3JQYXltZW50SW50ZW50LnJldHJpZXZlUGF5bWVudEludGVudChwYXltZW50SW50ZW50Q2xpZW50U2VjcmV0KTtcbiAgICAgICAgfVxuICAgIH07XG4gICAgY29uc3QgcmVzdWx0ID0gYXdhaXQgZ2V0UGF5bWVudEludGVudCgpO1xuICAgIGlmIChyZXN1bHQuZXJyb3IgfHwgcmVzdWx0LnBheW1lbnRJbnRlbnQuc3RhdHVzID09PSAncmVxdWlyZXNfcGF5bWVudF9tZXRob2QnKSB7XG4gICAgICAgIGhhbmRsZTNEU0Vycm9yKCk7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKHJlc3VsdC5wYXltZW50SW50ZW50LnN0YXR1cyA9PT0gJ3N1Y2NlZWRlZCcgJiYgR0xPQkFMLmNvbXBsZXRlZE9yZGVyKSB7XG4gICAgICAgIGNvbnN0IHBheW1lbnRTdGF0dXMgPSBhd2FpdCBvcmRlclNlcnZpY2Uuc2V0UGF5bWVudFN0YXR1cyhQZWFjaFBheU9yZGVyLnNlc3Npb25JZCgpLCB0cnVlKTtcbiAgICAgICAgaWYgKCFwYXltZW50U3RhdHVzLm9rKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBwYXltZW50U3RhdHVzLmpzb24oKTtcbiAgICAgICAgb3JkZXJTZXJ2aWNlLmRlcHJlY2F0ZWQuc2V0T3JkZXJTdGF0dXMoR0xPQkFMLmNvbXBsZXRlZE9yZGVyLCB7XG4gICAgICAgICAgICBzdGF0dXM6ICd3Yy1wcm9jZXNzaW5nJyxcbiAgICAgICAgICAgIHBheW1lbnRUeXBlOiAnU3RyaXBlJyxcbiAgICAgICAgICAgIHRyYW5zYWN0aW9uSUQ6IHJlc3BvbnNlLmNoYXJnZUlkXG4gICAgICAgIH0pO1xuICAgIH1cbn1cbmFzeW5jIGZ1bmN0aW9uIGFkZENhcmRUb1N0cmlwZUN1c3RvbWVyKHRva2VuSUQsIGN1c3RvbWVyKSB7XG4gICAgY29uc3QgYm9keSA9IHtcbiAgICAgICAgJ2N1c3RvbWVyX2lkJzogbnVsbCxcbiAgICAgICAgJ2NhcmRfdG9rZW4nOiB0b2tlbklELFxuICAgICAgICAnbmFtZSc6IGN1c3RvbWVyLm5hbWUsXG4gICAgICAgICdlbWFpbCc6IGN1c3RvbWVyLmVtYWlsLFxuICAgICAgICAncGhvbmUnOiBjdXN0b21lci5waG9uZVxuICAgIH07XG4gICAgY29uc3Qgb3B0aW9ucyA9IHtcbiAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbidcbiAgICAgICAgfSxcbiAgICAgICAgYm9keTogSlNPTi5zdHJpbmdpZnkoYm9keSlcbiAgICB9O1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnY2FyZCcsIG9wdGlvbnMpO1xuICAgIGlmICghcmVzcG9uc2Uub2spIHtcbiAgICAgICAgY29uc3QgZGF0YSA9IGF3YWl0IHJlc3BvbnNlLnRleHQoKTtcbiAgICAgICAgcGVhY2hwYXlBbGVydChkYXRhKTtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBGYWlsZWQgdG8gY3JlYXRlIFN0cmlwZSBDdXN0b21lciBvbiAke01lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpfS4gRXJyb3I6ICR7ZGF0YX1gKTtcbiAgICB9XG4gICAgY29uc3QgZGF0YSA9IGF3YWl0IHJlc3BvbnNlLmpzb24oKTtcbiAgICByZXR1cm4gZGF0YS5jdXN0b21lcjtcbn1cbmZ1bmN0aW9uIHNob3czRFNlY3VyZU1vZGFsKHVybCkge1xuICAgIGNvbnN0IGlmcmFtZSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2lmcmFtZScpO1xuICAgIGlmcmFtZS5pZCA9ICdzdHJpcGUtM0RTLW1vZGFsJztcbiAgICBpZnJhbWUuY2xhc3NMaXN0LmFkZCgnc3RyaXBlLTNkcy1mcmFtZScpO1xuICAgIGlmcmFtZS5zcmMgPSB1cmw7XG4gICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcpPy5hcHBlbmQoaWZyYW1lKTtcbiAgICBzaG93TG9hZGluZ1NjcmVlbigpO1xuICAgIGhpZGVPdGhlclNjcm9sbEJhcnMoKTtcbn1cbmZ1bmN0aW9uIGhhbmRsZTNEU0Vycm9yKCkge1xuICAgIGhpZGVMb2FkaW5nU2NyZWVuKCk7XG4gICAgc2hvd090aGVyU2Nyb2xsQmFycygpO1xuICAgIHBlYWNocGF5QWxlcnQoZ2V0TG9jYWxlVGV4dCgnc29tZXRoaW5nLXdlbnQtd3JvbmcnKSk7XG4gICAgc3RvcmUuZGlzcGF0Y2goc3RvcE1vZGFsTG9hZGluZygpKTtcbn1cbmFzeW5jIGZ1bmN0aW9uIGhhbmRsZVN0cmlwZVBheW1lbnQob3JkZXIpIHtcbiAgICBjb25zdCBib2R5ID0ge1xuICAgICAgICBzZXNzaW9uSUQ6IFBlYWNoUGF5T3JkZXIuc2Vzc2lvbklkKCksXG4gICAgICAgIHN0cmlwZUN1c3RvbWVySUQ6IFBlYWNoUGF5Q3VzdG9tZXIuc3RyaXBlSWQoKSxcbiAgICAgICAgb3JkZXIsXG4gICAgICAgIHJldHVyblVSTDogYCR7R0xPQkFMLnBocERhdGE/LnBsdWdpbl9hc3NldF91cmwgPz8gJyd9L3B1YmxpYy9kaXN0LyR7R0xPQkFMLnBocERhdGE/LnZlcnNpb24gPz8gJyd9L2NoZWNrb3V0LW1vZGFsLzNkcy5odG1sYFxuICAgIH07XG4gICAgY29uc3Qgb3B0aW9ucyA9IHtcbiAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbidcbiAgICAgICAgfSxcbiAgICAgICAgYm9keTogSlNPTi5zdHJpbmdpZnkoYm9keSlcbiAgICB9O1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnYXBpL3YxL3Nlc3Npb24vcGF5Jywgb3B0aW9ucyk7XG4gICAgY29uc3QgcmVzdWx0ID0gYXdhaXQgcmVzcG9uc2UuanNvbigpO1xuICAgIHJldHVybiByZXN1bHQuc3RhdHVzID09PSAnc3VjY2Vzcyc7XG59XG5hc3luYyBmdW5jdGlvbiBsZWdhY3lIYW5kbGVTdHJpcGVQYXltZW50KG9yZGVyLCBvcmRlclNlcnZpY2UpIHtcbiAgICBjb25zdCBib2R5ID0ge1xuICAgICAgICBzZXNzaW9uSUQ6IFBlYWNoUGF5T3JkZXIuc2Vzc2lvbklkKCksXG4gICAgICAgIHN0cmlwZUN1c3RvbWVySUQ6IFBlYWNoUGF5Q3VzdG9tZXIuc3RyaXBlSWQoKSxcbiAgICAgICAgb3JkZXIsXG4gICAgICAgIHJldHVyblVSTDogYCR7R0xPQkFMLnBocERhdGE/LnBsdWdpbl9hc3NldF91cmwgPz8gJyd9L3B1YmxpYy9kaXN0LyR7R0xPQkFMLnBocERhdGE/LnZlcnNpb24gPz8gJyd9L2NoZWNrb3V0LW1vZGFsLzNkcy5odG1sYFxuICAgIH07XG4gICAgY29uc3Qgb3B0aW9ucyA9IHtcbiAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbidcbiAgICAgICAgfSxcbiAgICAgICAgYm9keTogSlNPTi5zdHJpbmdpZnkoYm9keSlcbiAgICB9O1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnYXBpL3YxL3Nlc3Npb24vcGF5Jywgb3B0aW9ucyk7XG4gICAgY29uc3QgcmVzdWx0ID0gYXdhaXQgcmVzcG9uc2UuanNvbigpO1xuICAgIHN3aXRjaChyZXN1bHQuc3RhdHVzKXtcbiAgICAgICAgY2FzZSAnc3VjY2Vzcyc6XG4gICAgICAgICAgICBpZiAoIShhd2FpdCBvcmRlclNlcnZpY2Uuc2V0UGF5bWVudFN0YXR1cyhQZWFjaFBheU9yZGVyLnNlc3Npb25JZCgpLCB0cnVlKSkub2spIHtcbiAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIG9yZGVyU2VydmljZS5kZXByZWNhdGVkLnNldE9yZGVyU3RhdHVzKG9yZGVyLCB7XG4gICAgICAgICAgICAgICAgc3RhdHVzOiAnd2MtcHJvY2Vzc2luZycsXG4gICAgICAgICAgICAgICAgcGF5bWVudFR5cGU6ICdTdHJpcGUnLFxuICAgICAgICAgICAgICAgIHRyYW5zYWN0aW9uSUQ6IHJlc3VsdC5jaGFyZ2VJZFxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBicmVhaztcbiAgICAgICAgY2FzZSAncmVxdWlyZXNfYWN0aW9uJzpcbiAgICAgICAgICAgIHNob3czRFNlY3VyZU1vZGFsKHJlc3VsdD8udXJsID8/ICcnKTtcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICBjYXNlICdmYWlsdXJlJzpcbiAgICAgICAgICAgIG9yZGVyU2VydmljZS5kZXByZWNhdGVkLnNldE9yZGVyU3RhdHVzKG9yZGVyLCB7XG4gICAgICAgICAgICAgICAgc3RhdHVzOiAnd2MtZmFpbGVkJyxcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiByZXN1bHQubWVzc2FnZVxuICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICBzdG9yZS5kaXNwYXRjaChzdG9wTW9kYWxMb2FkaW5nKCkpO1xuICAgICAgICAgICAgcGVhY2hwYXlBbGVydChyZXN1bHQ/Lm1lc3NhZ2UgPz8gJycpO1xuICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgIGRlZmF1bHQ6XG4gICAgfVxufVxuZnVuY3Rpb24gaGlkZU90aGVyU2Nyb2xsQmFycygpIHtcbiAgICAkcXMoJyNwcC1tb2RhbC1jb250ZW50JywgKCRlbGVtZW50KT0+e1xuICAgICAgICBpZiAoJGVsZW1lbnQucGFyZW50RWxlbWVudCkge1xuICAgICAgICAgICAgJGVsZW1lbnQucGFyZW50RWxlbWVudC5zdHlsZS5vdmVyZmxvd1kgPSAnaGlkZGVuJztcbiAgICAgICAgfVxuICAgIH0pO1xufVxuZnVuY3Rpb24gc2hvd090aGVyU2Nyb2xsQmFycygpIHtcbiAgICAkcXMoJyNwcC1tb2RhbC1jb250ZW50JywgKCRlbGVtZW50KT0+e1xuICAgICAgICBpZiAoJGVsZW1lbnQucGFyZW50RWxlbWVudCkge1xuICAgICAgICAgICAgJGVsZW1lbnQucGFyZW50RWxlbWVudC5zdHlsZS5vdmVyZmxvd1kgPSAnc2Nyb2xsJztcbiAgICAgICAgfVxuICAgIH0pO1xufVxuZnVuY3Rpb24gaGlkZUxvYWRpbmdTY3JlZW4oKSB7XG4gICAgJHFzKCcjbG9hZGluZycpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgJHFzKCcjbG9hZGluZycpPy5jbGFzc0xpc3QucmVtb3ZlKCdmbGV4LWNvbnRhaW5lcicpO1xufVxuZnVuY3Rpb24gc2hvd0xvYWRpbmdTY3JlZW4oKSB7XG4gICAgJHFzKCcjbG9hZGluZycpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgJHFzKCcjbG9hZGluZycpPy5jbGFzc0xpc3QuYWRkKCdmbGV4LWNvbnRhaW5lcicpO1xufVxubGV0IHBheXBhbE1lcmNoYW50SUQgPSBudWxsO1xuY29uc3QgQk5fQ09ERV9TQU5EQk9YID0gJ0ZMQVZPUnNiLTZqb3B2NjU0MDI3NV9NUCc7XG5jb25zdCBCTl9DT0RFX1BST0RVQ1RJT04gPSAnUGVhY2hfU1BfUFBDUCc7XG5mdW5jdGlvbiBpbml0UGF5UGFsRXZlbnRzKCkge1xuICAgICRxcygnI3BheXBhbC1vcHRpb24nKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBwYXlwYWxQYXltZW50T3B0aW9uKTtcbiAgICBzdG9yZS5zdWJzY3JpYmUoKCk9PntcbiAgICAgICAgcmVuZGVyUGF5UGFsQnV0dG9uKFBlYWNoUGF5Q3VzdG9tZXIucHJlZmVycmVkUGF5bWVudE1ldGhvZCgpID09PSAncGF5cGFsJyAmJiBFbnZpcm9ubWVudC5tb2RhbFVJLnBhZ2UoKSA9PT0gJ3BheW1lbnQnKTtcbiAgICB9KTtcbn1cbmFzeW5jIGZ1bmN0aW9uIHBheXBhbExvYWRTY3JpcHRzKHNjcmlwdFVSTHMpIHtcbiAgICBmdW5jdGlvbiBsb2FkKHNjcmlwdFVSTCkge1xuICAgICAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUsIF8pPT57XG4gICAgICAgICAgICBpZiAocGF5cGFsTG9hZFNjcmlwdHMubG9hZGVkLmhhcyhzY3JpcHRVUkwpKSB7XG4gICAgICAgICAgICAgICAgcmVzb2x2ZSgpO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBjb25zdCBzY3JpcHQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzY3JpcHQnKTtcbiAgICAgICAgICAgICAgICBzY3JpcHQuYWRkRXZlbnRMaXN0ZW5lcignbG9hZCcsIHJlc29sdmUpO1xuICAgICAgICAgICAgICAgIHNjcmlwdC5zcmMgPSBzY3JpcHRVUkw7XG4gICAgICAgICAgICAgICAgc2NyaXB0LmRhdGFzZXQuZGF0YVBhcnRuZXJBdHRyaWJ1dGlvbklkID0gaXNEZXZFbnZpcm9ubWVudChnZXRCYXNlVVJMKE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpLCBFbnZpcm9ubWVudC50ZXN0TW9kZSgpKSkgPyBCTl9DT0RFX1NBTkRCT1ggOiBCTl9DT0RFX1BST0RVQ1RJT047XG4gICAgICAgICAgICAgICAgZG9jdW1lbnQuaGVhZC5hcHBlbmQoc2NyaXB0KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG4gICAgfVxuICAgIGNvbnN0IHByb21pc2VzID0gW107XG4gICAgZm9yIChjb25zdCBzY3JpcHRVUkwyIG9mIHNjcmlwdFVSTHMpe1xuICAgICAgICBwcm9taXNlcy5wdXNoKGxvYWQoc2NyaXB0VVJMMikpO1xuICAgIH1cbiAgICBhd2FpdCBQcm9taXNlLmFsbChwcm9taXNlcyk7XG4gICAgZm9yIChjb25zdCBzY3JpcHRVUkwxIG9mIHNjcmlwdFVSTHMpe1xuICAgICAgICBwYXlwYWxMb2FkU2NyaXB0cy5sb2FkZWQuYWRkKHNjcmlwdFVSTDEpO1xuICAgIH1cbn1cbnBheXBhbExvYWRTY3JpcHRzLmxvYWRlZCA9IG5ldyBTZXQoKTtcbmFzeW5jIGZ1bmN0aW9uIGxvYWRQYXlQYWxTY3JpcHQoKSB7XG4gICAgdHJ5IHtcbiAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBmZXRjaChnZXRCYXNlVVJMKE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpLCBFbnZpcm9ubWVudC50ZXN0TW9kZSgpKSArIGBhcGkvdjEvcGF5cGFsL21lcmNoYW50QW5kQ2xpZW50P21lcmNoYW50SG9zdG5hbWU9JHtNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKX1gLCB7XG4gICAgICAgICAgICBoZWFkZXJzOiB7XG4gICAgICAgICAgICAgICAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi9qc29uJ1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgICAgY29uc3QgbWVyY2hhbnQgPSBhd2FpdCByZXNwb25zZS5qc29uKCk7XG4gICAgICAgIGlmIChtZXJjaGFudC5wYXlwYWxNZXJjaGFudElEICE9PSAnJykge1xuICAgICAgICAgICAgcGF5cGFsTWVyY2hhbnRJRCA9IG1lcmNoYW50LnBheXBhbE1lcmNoYW50SUQ7XG4gICAgICAgICAgICBhd2FpdCBwYXlwYWxMb2FkU2NyaXB0cyhbXG4gICAgICAgICAgICAgICAgYGh0dHBzOi8vd3d3LnBheXBhbC5jb20vc2RrL2pzPyZjbGllbnQtaWQ9JHttZXJjaGFudC5jbGllbnRJRH0mbWVyY2hhbnQtaWQ9JHttZXJjaGFudC5wYXlwYWxNZXJjaGFudElEfSZkaXNhYmxlLWZ1bmRpbmc9cGF5bGF0ZXIsY2FyZCxiYW5jb250YWN0LGJsaWssZXBzLGdpcm9wYXksaWRlYWwsbXliYW5rLHAyNCxzb2ZvcnQmY3VycmVuY3k9JHtNZXJjaGFudENvbmZpZ3VyYXRpb24uY3VycmVuY3kuY29kZSgpfWAsIFxuICAgICAgICAgICAgXSk7XG4gICAgICAgIH1cbiAgICB9IGNhdGNoIChlcnJvcikge1xuICAgICAgICBpZiAoZXJyb3IgaW5zdGFuY2VvZiBFcnJvcikge1xuICAgICAgICAgICAgY2FwdHVyZVNlbnRyeUV4Y2VwdGlvbihuZXcgRXJyb3IoYEZhaWxlZCB0byByZXRyaWV2ZSBwYXlwYWwgbWVyY2hhbnQgSWQgb3IgbG9hZCBQYXlwYWwgU2NyaXB0cyBvbiAke2xvY2F0aW9uLmhvc3RuYW1lfS4gUGx1Z2luIE1vZGU6ICR7RW52aXJvbm1lbnQucGx1Z2luLm1vZGUoKX0uIEVycm9yOiAke2Vycm9yLm1lc3NhZ2V9YCkpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG4gICAgcmV0dXJuIHRydWU7XG59XG5hc3luYyBmdW5jdGlvbiBpbml0UGF5UGFsU3VwcG9ydChtZXNzYWdlLCBvcmRlclNlcnZpY2UpIHtcbiAgICBjb25zdCBwYXlwYWxDaGVja2VkID0gbWVzc2FnZS5waHBEYXRhLnBheXBhbDtcbiAgICBpZiAoIXBheXBhbENoZWNrZWQpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpZiAoIWF3YWl0IGxvYWRQYXlQYWxTY3JpcHQoKSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGluaXRQYXlQYWxFdmVudHMoKTtcbiAgICBpZiAocGF5cGFsTWVyY2hhbnRJRCAmJiBOdW1iZXIucGFyc2VJbnQocGF5cGFsQ2hlY2tlZCA/PyAnMCcpKSB7XG4gICAgICAgIGF3YWl0IGluaXRQYXlQYWxCdXR0b24ob3JkZXJTZXJ2aWNlKTtcbiAgICAgICAgc2hvd1BheVBhbEJ1dHRvbigpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIGluaXRQYXlQYWxCdXR0b24ob3JkZXJTZXJ2aWNlKSB7XG4gICAgY29uc3QgJHBheXBhbEJ1dHRvbiA9IHBheXBhbC5CdXR0b25zKHtcbiAgICAgICAgc3R5bGU6IHtcbiAgICAgICAgICAgIGhlaWdodDogNTVcbiAgICAgICAgfSxcbiAgICAgICAgYXN5bmMgY3JlYXRlT3JkZXIgKCkge1xuICAgICAgICAgICAgcmV0dXJuIGF3YWl0IGNyZWF0ZVBheVBhbE9yZGVyKCk7XG4gICAgICAgIH0sXG4gICAgICAgIG9uQXBwcm92ZSAoZGF0YSwgYWN0aW9ucykge1xuICAgICAgICAgICAgc2hvd1BheVBhbExvYWRpbmdTcGlubmVyKCk7XG4gICAgICAgICAgICBwbGFjZU9yZGVyT25TdG9yZUFuZExpc3RlbkZvckNvbXBsZXRpb24oZGF0YSwgYWN0aW9ucywgb3JkZXJTZXJ2aWNlKTtcbiAgICAgICAgfSxcbiAgICAgICAgb25DbGljayAoKSB7XG4gICAgICAgICAgICByZXR1cm4gY2hlY2tSZXF1aXJlZEZpZWxkcygpO1xuICAgICAgICB9XG4gICAgfSk7XG4gICAgJHBheXBhbEJ1dHRvbi5yZW5kZXIoJyNwYXlwYWwtYnV0dG9uLWNvbnRhaW5lcicpO1xuICAgICRwYXlwYWxCdXR0b24ucmVuZGVyKCcjcGF5cGFsLWJ1dHRvbi1jb250YWluZXItbW9iaWxlJyk7XG4gICAgJHBheXBhbEJ1dHRvbi5yZW5kZXIoJyNwYXlwYWwtYnV0dG9uLWNvbnRhaW5lci1leGlzdGluZycpO1xufVxuZnVuY3Rpb24gcmVzdGFydEFjdGlvbihhY3Rpb25zKSB7XG4gICAgc2VsZi5hZGRFdmVudExpc3RlbmVyKCdtZXNzYWdlJywgKGV2ZW50KT0+e1xuICAgICAgICBpZiAoZXZlbnQuZGF0YS5ldmVudCA9PT0gJ3BheXBhbFJlc3RhcnQnKSB7XG4gICAgICAgICAgICBhY3Rpb25zLnJlc3RhcnQoKTtcbiAgICAgICAgfVxuICAgIH0pO1xufVxuYXN5bmMgZnVuY3Rpb24gY3JlYXRlUGF5UGFsT3JkZXIoKSB7XG4gICAgY29uc3QgbW9ja09yZGVyUmVzdWx0ID0ge1xuICAgICAgICBkb21haW46IE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpLFxuICAgICAgICBkZXRhaWxzOiB7XG4gICAgICAgICAgICBpZDogJycsXG4gICAgICAgICAgICBudW1iZXI6ICcnLFxuICAgICAgICAgICAgY3VycmVuY3k6IE1lcmNoYW50Q29uZmlndXJhdGlvbi5jdXJyZW5jeS5jb2RlKCksXG4gICAgICAgICAgICBkaXNjb3VudF90b3RhbDogRGVmYXVsdENhcnQudG90YWxBcHBsaWVkQ291cG9ucygpLnRvRml4ZWQoMiksXG4gICAgICAgICAgICBzaGlwcGluZ190b3RhbDogRGVmYXVsdENhcnQudG90YWxTaGlwcGluZygpLnRvRml4ZWQoMiksXG4gICAgICAgICAgICB0b3RhbDogRGVmYXVsdENhcnQudG90YWwoKS50b0ZpeGVkKDIpLFxuICAgICAgICAgICAgdG90YWxfdGF4OiBHTE9CQUwucGhwRGF0YT8ud2NfcHJpY2VzX2luY2x1ZGVfdGF4ID8gJzAuMDAnIDogRGVmYXVsdENhcnQudG90YWxUYXgoKS50b0ZpeGVkKDIpLFxuICAgICAgICAgICAgc2hpcHBpbmc6IHBheXBhbEN1c3RvbWVyQWRkcmVzcygpLFxuICAgICAgICAgICAgbGluZV9pdGVtczogZ2V0TGluZUl0ZW1zKCksXG4gICAgICAgICAgICBzaGlwcGluZ19saW5lczogZ2V0U2hpcHBpbmdMaW5lcygpLFxuICAgICAgICAgICAgZmVlX3RvdGFsOiBEZWZhdWx0Q2FydC50b3RhbEFwcGxpZWRGZWVzKCkudG9GaXhlZCgyKVxuICAgICAgICB9XG4gICAgfTtcbiAgICBjb25zdCBib2R5ID0ge1xuICAgICAgICBvcmRlclJlc3VsdDogbW9ja09yZGVyUmVzdWx0LFxuICAgICAgICBzZXNzaW9uSUQ6IFBlYWNoUGF5T3JkZXIuc2Vzc2lvbklkKCksXG4gICAgICAgIHBheXBhbE1lcmNoYW50SURcbiAgICB9O1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnYXBpL3YxL3BheXBhbC9vcmRlcicsIHtcbiAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbidcbiAgICAgICAgfSxcbiAgICAgICAgYm9keTogSlNPTi5zdHJpbmdpZnkoYm9keSlcbiAgICB9KTtcbiAgICBjb25zdCByZXN1bHQgPSBhd2FpdCByZXNwb25zZS5qc29uKCk7XG4gICAgcmV0dXJuIHJlc3VsdC5pZDtcbn1cbmZ1bmN0aW9uIHBheXBhbEN1c3RvbWVyQWRkcmVzcygpIHtcbiAgICBjb25zdCB7IGZpcnN0TmFtZSAsIGxhc3ROYW1lICwgYWRkcmVzczEgLCBhZGRyZXNzMiAsIGNpdHkgLCBzdGF0ZSAsIHBvc3RhbCAsIGNvdW50cnkgLCBwaG9uZSAsIGVtYWlsICB9ID0gUGVhY2hQYXlDdXN0b21lcjtcbiAgICByZXR1cm4ge1xuICAgICAgICBmaXJzdF9uYW1lOiBmaXJzdE5hbWUoKSxcbiAgICAgICAgbGFzdF9uYW1lOiBsYXN0TmFtZSgpLFxuICAgICAgICBjb21wYW55OiAnJyxcbiAgICAgICAgYWRkcmVzc18xOiBhZGRyZXNzMSgpLFxuICAgICAgICBhZGRyZXNzXzI6IGFkZHJlc3MyKCksXG4gICAgICAgIGNpdHk6IGNpdHkoKSxcbiAgICAgICAgc3RhdGU6IHN0YXRlKCksXG4gICAgICAgIHBvc3Rjb2RlOiBwb3N0YWwoKSxcbiAgICAgICAgY291bnRyeTogY291bnRyeSgpLFxuICAgICAgICBwaG9uZTogcGhvbmUoKSxcbiAgICAgICAgZW1haWw6IGVtYWlsKClcbiAgICB9O1xufVxuZnVuY3Rpb24gZ2V0TGluZUl0ZW1zKCkge1xuICAgIGNvbnN0IGl0ZW1zID0gW107XG4gICAgZm9yIChjb25zdCBpdGVtIG9mIERlZmF1bHRDYXJ0LmNvbnRlbnRzKCkpe1xuICAgICAgICBjb25zdCBsaW5lSXRlbSA9IHtcbiAgICAgICAgICAgICdpZCc6IGl0ZW0ucHJvZHVjdF9pZCxcbiAgICAgICAgICAgICduYW1lJzogaXRlbS5uYW1lX3dpdGhfdmFyaWF0aW9uIHx8IGl0ZW0ubmFtZSArIChpdGVtLnZhcmlhdGlvbl90aXRsZSA/IGAgLSAke2l0ZW0udmFyaWF0aW9uX3RpdGxlfWAgOiAnJyksXG4gICAgICAgICAgICAncXVhbnRpdHknOiBTdHJpbmcoaXRlbS5xdWFudGl0eSksXG4gICAgICAgICAgICAnc3VidG90YWwnOiBTdHJpbmcoTnVtYmVyLnBhcnNlRmxvYXQoaXRlbS50b3RhbCkgKiBOdW1iZXIucGFyc2VJbnQoaXRlbS5xdWFudGl0eSkpLFxuICAgICAgICAgICAgJ3N1YnRvdGFsX3RheCc6ICcwLjAwJ1xuICAgICAgICB9O1xuICAgICAgICBpZiAoR0xPQkFMLnBocERhdGE/LndjX3ByaWNlc19pbmNsdWRlX3RheCAmJiBpdGVtLmRpc3BsYXlfcHJpY2UpIHtcbiAgICAgICAgICAgIGxpbmVJdGVtLnN1YnRvdGFsID0gU3RyaW5nKE51bWJlci5wYXJzZUZsb2F0KGl0ZW0uZGlzcGxheV9wcmljZSkgKiBOdW1iZXIucGFyc2VJbnQoaXRlbS5xdWFudGl0eSkpO1xuICAgICAgICB9XG4gICAgICAgIGl0ZW1zLnB1c2gobGluZUl0ZW0pO1xuICAgIH1cbiAgICBpZiAoIUdMT0JBTC5waHBEYXRhPy53Y19wcmljZXNfaW5jbHVkZV90YXgpIHtcbiAgICAgICAgaXRlbXNbMF0uc3VidG90YWxfdGF4ID0gU3RyaW5nKERlZmF1bHRDYXJ0LnRvdGFsVGF4KCkpO1xuICAgIH1cbiAgICByZXR1cm4gaXRlbXM7XG59XG5mdW5jdGlvbiBnZXRTaGlwcGluZ0xpbmVzKCkge1xuICAgIHJldHVybiB7XG4gICAgICAgIDA6ICgoKT0+e1xuICAgICAgICAgICAgY29uc3Qgc2hpcHBpbmdEZXRhaWxzID0gRGVmYXVsdENhcnQuc2VsZWN0ZWRTaGlwcGluZ01ldGhvZERldGFpbHMoJzAnKTtcbiAgICAgICAgICAgIGlmICghc2hpcHBpbmdEZXRhaWxzKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHVuZGVmaW5lZDtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIHJldHVybiB7XG4gICAgICAgICAgICAgICAgbWV0aG9kX2lkOiBzaGlwcGluZ0RldGFpbHMuc2VsZWN0ZWRfbWV0aG9kLFxuICAgICAgICAgICAgICAgIHRvdGFsOiBTdHJpbmcoRGVmYXVsdENhcnQudG90YWxTaGlwcGluZygpKSxcbiAgICAgICAgICAgICAgICBtZXRob2RfdGl0bGU6IHNoaXBwaW5nRGV0YWlscy5tZXRob2RzW3NoaXBwaW5nRGV0YWlscy5zZWxlY3RlZF9tZXRob2RdPy50aXRsZSA/PyAnJ1xuICAgICAgICAgICAgfTtcbiAgICAgICAgfSkoKVxuICAgIH07XG59XG5mdW5jdGlvbiBzaG93UGF5UGFsTG9hZGluZ1NwaW5uZXIoc2hvdyA9IHRydWUpIHtcbiAgICBjb25zdCBzcGlubmVycyA9IFtcbiAgICAgICAgJyNwYXlwYWwtc3Bpbm5lcicsXG4gICAgICAgICcjcGF5cGFsLXNwaW5uZXItbW9iaWxlJyxcbiAgICAgICAgJyNwYXlwYWwtc3Bpbm5lci1leGlzdGluZycsIFxuICAgIF07XG4gICAgZm9yIChjb25zdCBzcGlubmVyIG9mIHNwaW5uZXJzKXtcbiAgICAgICAgaWYgKHNob3cpIHtcbiAgICAgICAgICAgICRxcyhzcGlubmVyKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJHFzKHNwaW5uZXIpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29uc3QgcGF5cGFsQnV0dG9uQ29udGFpbmVycyA9IFtcbiAgICAgICAgJyNwYXlwYWwtYnV0dG9uLWNvbnRhaW5lcicsXG4gICAgICAgICcjcGF5cGFsLWJ1dHRvbi1jb250YWluZXItbW9iaWxlJyxcbiAgICAgICAgJyNwYXlwYWwtYnV0dG9uLWNvbnRhaW5lci1leGlzdGluZycsIFxuICAgIF07XG4gICAgZm9yIChjb25zdCBjb250YWluZXIgb2YgcGF5cGFsQnV0dG9uQ29udGFpbmVycyl7XG4gICAgICAgIGlmIChzaG93KSB7XG4gICAgICAgICAgICAkcXMoY29udGFpbmVyKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgJHFzKGNvbnRhaW5lcik/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmxldCBsYXRlc3RPcmRlckF0dGVtcHQgPSAwO1xuZnVuY3Rpb24gcGxhY2VPcmRlck9uU3RvcmVBbmRMaXN0ZW5Gb3JDb21wbGV0aW9uKGRhdGEsIGFjdGlvbnMsIG9yZGVyU2VydmljZSkge1xuICAgIGxhdGVzdE9yZGVyQXR0ZW1wdCsrO1xuICAgIGNvbnN0IG9yZGVyQXR0ZW1wdCA9IGxhdGVzdE9yZGVyQXR0ZW1wdDtcbiAgICBvbldpbmRvd01lc3NhZ2UoJ3N1Ym1pdFBheXBhbE9yZGVyJywgYXN5bmMgKG1lc3NhZ2UpPT57XG4gICAgICAgIGlmIChsYXRlc3RPcmRlckF0dGVtcHQgIT09IG9yZGVyQXR0ZW1wdCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICBhd2FpdCB1cGRhdGVQYXlQYWxPcmRlcldpdGhGaW5hbEFtb3VudChkYXRhLm9yZGVySUQsIG1lc3NhZ2Uub3JkZXIpO1xuICAgICAgICB9IGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgY2FwdHVyZVNlbnRyeUV4Y2VwdGlvbihuZXcgRXJyb3IoJ0Vycm9yIHdoaWxlIHVwZGF0aW5nIFBheVBhbCBvcmRlciB3aXRoIGZpbmFsIGFtb3VudDogJyArIEpTT04uc3RyaW5naWZ5KGVycm9yKSkpO1xuICAgICAgICB9XG4gICAgICAgIGxldCBjYXB0dXJlID0gbnVsbDtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGNhcHR1cmUgPSBhd2FpdCBjYXB0dXJlUGF5UGFsT3JkZXIoZGF0YS5vcmRlcklEKTtcbiAgICAgICAgfSBjYXRjaCAoZXJyb3IxKSB7XG4gICAgICAgICAgICBjYXB0dXJlU2VudHJ5RXhjZXB0aW9uKG5ldyBFcnJvcignRXJyb3Igd2hpbGUgY2FwdHVyaW5nIFBheVBhbCBvcmRlcjogJyArIEpTT04uc3RyaW5naWZ5KGVycm9yMSkpKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoY2FwdHVyZT8uc3RhdHVzID09PSAnQ09NUExFVEVEJykge1xuICAgICAgICAgICAgY29uc3QgY3VzdG9tZXIgPSBhd2FpdCBnZXRDdXN0b21lcigpO1xuICAgICAgICAgICAgaWYgKGN1c3RvbWVyID09PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgc2F2ZUN1c3RvbWVyVG9Ccm93c2VyKG51bGwsIG51bGwsIG51bGwsICdwYXlwYWwnKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgY29uc3Qgc3RyaXBlQ3VzdG9tZXJJRCA9IGN1c3RvbWVyLnN0cmlwZV9jdXN0b21lcl9pZCA/IGN1c3RvbWVyLnN0cmlwZV9jdXN0b21lcl9pZCA6IG51bGw7XG4gICAgICAgICAgICAgICAgY29uc3QgY2FyZEJyYW5kID0gY3VzdG9tZXIuY2FyZD8uYnJhbmQgPz8gbnVsbDtcbiAgICAgICAgICAgICAgICBjb25zdCBjYXJkTGFzdDQgPSBjdXN0b21lci5jYXJkPy5sYXN0NCA/PyBudWxsO1xuICAgICAgICAgICAgICAgIHNhdmVDdXN0b21lclRvQnJvd3NlcihzdHJpcGVDdXN0b21lcklELCBjYXJkQnJhbmQsIGNhcmRMYXN0NCwgJ3BheXBhbCcpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKCEoYXdhaXQgb3JkZXJTZXJ2aWNlLnNldFBheW1lbnRTdGF0dXMoUGVhY2hQYXlPcmRlci5zZXNzaW9uSWQoKSwgdHJ1ZSkpLm9rKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgY29uc3QgdHJhbnNhY3Rpb25JRCA9IGNhcHR1cmUucHVyY2hhc2VfdW5pdHNbMF0ucGF5bWVudHMuY2FwdHVyZXNbMF0uaWQ7XG4gICAgICAgICAgICBvcmRlclNlcnZpY2UuZGVwcmVjYXRlZC5zZXRPcmRlclN0YXR1cyhtZXNzYWdlLm9yZGVyLCB7XG4gICAgICAgICAgICAgICAgc3RhdHVzOiAnd2MtcHJvY2Vzc2luZycsXG4gICAgICAgICAgICAgICAgcGF5bWVudFR5cGU6ICdQYXlQYWwnLFxuICAgICAgICAgICAgICAgIHRyYW5zYWN0aW9uSURcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9IGVsc2UgaWYgKGNhcHR1cmU/LmRldGFpbHNbMF0uaXNzdWUgPT09ICdJTlNUUlVNRU5UX0RFQ0xJTkVEJykge1xuICAgICAgICAgICAgc2hvd1BheVBhbExvYWRpbmdTcGlubmVyKGZhbHNlKTtcbiAgICAgICAgICAgIHdpbmRvdy5wYXJlbnQucG9zdE1lc3NhZ2Uoe1xuICAgICAgICAgICAgICAgIGV2ZW50OiAncGF5cGFsQWxlcnQnLFxuICAgICAgICAgICAgICAgIG1lc3NhZ2U6IGNhcHR1cmUuZGV0YWlsc1swXS5kZXNjcmlwdGlvblxuICAgICAgICAgICAgfSwgJyonKTtcbiAgICAgICAgICAgIHJlc3RhcnRBY3Rpb24oYWN0aW9ucyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBzaG93UGF5UGFsTG9hZGluZ1NwaW5uZXIoZmFsc2UpO1xuICAgICAgICAgICAgd2luZG93LnBhcmVudC5wb3N0TWVzc2FnZSh7XG4gICAgICAgICAgICAgICAgZXZlbnQ6ICdwYXlwYWxBbGVydCcsXG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ1NvbWV0aGluZyB3ZW50IHdyb25nLidcbiAgICAgICAgICAgIH0sICcqJyk7XG4gICAgICAgICAgICByZXN0YXJ0QWN0aW9uKGFjdGlvbnMpO1xuICAgICAgICB9XG4gICAgfSk7XG4gICAgb3JkZXJTZXJ2aWNlLmRlcHJlY2F0ZWQucGxhY2VPcmRlcih7XG4gICAgICAgIGlzUGF5cGFsOiB0cnVlXG4gICAgfSk7XG59XG5hc3luYyBmdW5jdGlvbiBjYXB0dXJlUGF5UGFsT3JkZXIob3JkZXJJRCkge1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnYXBpL3YxL3BheXBhbC9jYXB0dXJlJywge1xuICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgaGVhZGVyczoge1xuICAgICAgICAgICAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi9qc29uJ1xuICAgICAgICB9LFxuICAgICAgICBib2R5OiBKU09OLnN0cmluZ2lmeSh7XG4gICAgICAgICAgICBvcmRlcklELFxuICAgICAgICAgICAgc2Vzc2lvbklEOiBQZWFjaFBheU9yZGVyLnNlc3Npb25JZCgpLFxuICAgICAgICAgICAgcGF5cGFsTWVyY2hhbnRJRFxuICAgICAgICB9KVxuICAgIH0pO1xuICAgIHJldHVybiByZXNwb25zZS5qc29uKCk7XG59XG5hc3luYyBmdW5jdGlvbiB1cGRhdGVQYXlQYWxPcmRlcldpdGhGaW5hbEFtb3VudChwYXlwYWxPcmRlcklELCBvcmRlclJlc3VsdCkge1xuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnYXBpL3YxL3BheXBhbC9vcmRlci91cGRhdGUnLCB7XG4gICAgICAgIG1ldGhvZDogJ1BBVENIJyxcbiAgICAgICAgaGVhZGVyczoge1xuICAgICAgICAgICAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi9qc29uJ1xuICAgICAgICB9LFxuICAgICAgICBib2R5OiBKU09OLnN0cmluZ2lmeSh7XG4gICAgICAgICAgICBzZXNzaW9uSUQ6IFBlYWNoUGF5T3JkZXIuc2Vzc2lvbklkKCksXG4gICAgICAgICAgICBwYXlwYWxNZXJjaGFudElELFxuICAgICAgICAgICAgcGF5cGFsT3JkZXJJRCxcbiAgICAgICAgICAgIG9yZGVyUmVzdWx0XG4gICAgICAgIH0pXG4gICAgfSk7XG4gICAgcmV0dXJuIHJlc3BvbnNlLmpzb24oKTtcbn1cbmZ1bmN0aW9uIHNob3dQYXlQYWxCdXR0b24oKSB7XG4gICAgJHFzKCcjc2hpcHBpbmctb3B0aW9ucy1jb250YWluZXInKT8uY2xhc3NMaXN0LnJlbW92ZSgnbXQtcGF5bWVudCcpO1xuICAgICRxcygnI3BwLXNoaXBwaW5nLW9wdGlvbnMnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5tYXJnaW5Cb3R0b20gPSAnMC41cmVtJ1xuICAgICk7XG4gICAgJHFzKCcuZm9ybS1yb3cnKT8uY2xhc3NMaXN0LnJlbW92ZSgnbWItcGF5bWVudCcpO1xuICAgICRxcygnI3BheW1lbnQtbWV0aG9kcycsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLm1hcmdpblRvcCA9ICcxcmVtJ1xuICAgICk7XG4gICAgJHFzKCcjc3RyaXBlLW9wdGlvbicpPy5jbGFzc0xpc3QucmVtb3ZlKCdwYXlwYWwtZGlzYWJsZWQnKTtcbiAgICAkcXMoJyNzdHJpcGUtb3B0aW9uJyk/LmNsYXNzTGlzdC5hZGQoJ3BtLW9wdGlvbi1ibG9jaycpO1xuICAgICRxcygnI2NjLXJlZ3VsYXInKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICRxcygnI3N0cmlwZS1ib3gnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICRxcygnI3BheXBhbC1vcHRpb24nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICRxcygnI3BwLXBheS1idG4nKT8uY2xhc3NMaXN0LnJlbW92ZSgncGF5cGFsLWRpc2FibGVkLWJ0bicpO1xuICAgICRxcygnI3BwLXBheS1idG4nKT8uY2xhc3NMaXN0LmFkZCgncHAtcGF5LW10Jyk7XG59XG5mdW5jdGlvbiBwYXlwYWxQYXltZW50T3B0aW9uKCkge1xuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZVByZWZlcnJlZFBheW1lbnRNZXRob2QoJ3BheXBhbCcpKTtcbn1cbmZ1bmN0aW9uIHJlbmRlclBheVBhbEJ1dHRvbihkaXNwbGF5KSB7XG4gICAgaWYgKGRpc3BsYXkpIHtcbiAgICAgICAgJHFzKCcjcGF5cGFsLXBtJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuY2hlY2tlZCA9IHRydWVcbiAgICAgICAgKTtcbiAgICAgICAgJHFzKCcjcGF5cGFsLXBtJyk/LnNldEF0dHJpYnV0ZSgnY2hlY2tlZCcsICd0cnVlJyk7XG4gICAgICAgICRxcygnI3BheXBhbC1vcHRpb24nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5iYWNrZ3JvdW5kQ29sb3IgPSAnd2hpdGUnXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI3BheXBhbC1idXR0b24tY29udGFpbmVyJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjcGF5cGFsLWJ1dHRvbi1jb250YWluZXItbW9iaWxlJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICAkcXMoJyNwYXlwYWwtcG0nLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5jaGVja2VkID0gZmFsc2VcbiAgICAgICAgKTtcbiAgICAgICAgJHFzKCcjcGF5cGFsLXBtJyk/LnJlbW92ZUF0dHJpYnV0ZSgnY2hlY2tlZCcpO1xuICAgICAgICAkcXMoJyNwYXlwYWwtb3B0aW9uJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUuYmFja2dyb3VuZENvbG9yID0gJyNmNGY0ZjQnXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI3BheXBhbC1idXR0b24tY29udGFpbmVyJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjcGF5cGFsLWJ1dHRvbi1jb250YWluZXItbW9iaWxlJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICB9XG59XG5mdW5jdGlvbiBpbml0U3VtbWFyeShtZXNzYWdlKSB7XG4gICAgaW5pdFN1bW1hcnlFdmVudHMoKTtcbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVNZXJjaGFudFRheENvbmZpZyh7XG4gICAgICAgIGRpc3BsYXlQcmljZXNJbkNhcnRBbmRDaGVja291dDogbWVzc2FnZS5waHBEYXRhPy53Y190YXhfcHJpY2VfZGlzcGxheSA9PT0gJ2luY2wnID8gJ2luY2x1ZGVUYXgnIDogJ2V4Y2x1ZGVUYXgnXG4gICAgfSkpO1xufVxuZnVuY3Rpb24gaW5pdFN1bW1hcnlFdmVudHMoKSB7XG4gICAgJHFzKCcjcHAtZHJvcGRvd24nKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBvcmRlclN1bW1hcnlEcm9wZG93bik7XG4gICAgJHFzKCcjcHAtZHJvcGRvd24nKT8uYWRkRXZlbnRMaXN0ZW5lcigna2V5cHJlc3MnLCAoZXZlbnQpPT57XG4gICAgICAgIGlmIChldmVudC5rZXkgPT09ICdFbnRlcicgfHwgZXZlbnQua2V5ID09PSAnICcpIHtcbiAgICAgICAgICAgIG9yZGVyU3VtbWFyeURyb3Bkb3duKCk7XG4gICAgICAgIH1cbiAgICB9KTtcbiAgICAkcXMoJyNwcC1kcm9wZG93bi1uZXcnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBvcmRlclN1bW1hcnlEcm9wZG93bik7XG4gICAgc3RvcmUuc3Vic2NyaWJlKCgpPT57XG4gICAgICAgIHJlbmRlclN1bW1hcmllcygpO1xuICAgICAgICByZW5kZXJDYXJ0VG90YWxzKCk7XG4gICAgfSk7XG59XG5mdW5jdGlvbiByZW5kZXJTdW1tYXJpZXMoKSB7XG4gICAgY2xlYXJSZW5kZXJlZFN1bW1hcmllcygpO1xuICAgIGxldCBjYXJ0U3VtbWFyaWVzSFRNTCA9ICcnO1xuICAgIGZvciAoY29uc3QgY2FydEtleSBvZiBPYmplY3Qua2V5cyhzdG9yZS5nZXRTdGF0ZSgpLmNhbGN1bGF0ZWRDYXJ0cykpe1xuICAgICAgICBsZXQgc3VtbWFyeUhUTUwgPSAnJztcbiAgICAgICAgY29uc3QgeyBjYXJ0U3VtbWFyeSAsIGNhcnRNZXRhICB9ID0gY2FydFN1bW1hcnlWaWV3RGF0YShjYXJ0S2V5KSgpO1xuICAgICAgICBjb25zdCBzdW1tYXJ5VGl0bGVIVE1MID0gY2FydEtleSA9PT0gJzAnID8gJycgOiBgXG48dHIgY2xhc3M9XCJzdW1tYXJ5LXRpdGxlXCI+XG5cdDx0ZD5SZWN1cnJpbmcgdG90YWxzPC90ZD5cblx0PHRkPjwvdGQ+XG48L3RyPmA7XG4gICAgICAgIGZvciAoY29uc3QgbGluZSBvZiBjYXJ0U3VtbWFyeSl7XG4gICAgICAgICAgICBzdW1tYXJ5SFRNTCArPSByZW5kZXJTdW1tYXJ5TGluZShsaW5lLmtleSwgbGluZS52YWx1ZSwgY2FydE1ldGEpO1xuICAgICAgICB9XG4gICAgICAgIGNhcnRTdW1tYXJpZXNIVE1MICs9IGBcbjxkaXYgY2xhc3M9XCJjYXJ0LXN1bW1hcnlcIiBkYXRhLWNhcnQta2V5PVwiJHtjYXJ0S2V5fVwiPlxuXHQ8dGFibGU+XG5cdFx0JHtzdW1tYXJ5VGl0bGVIVE1MfVxuXHRcdCR7c3VtbWFyeUhUTUx9XG5cdDwvdGFibGU+XG5cdDxwIGNsYXNzPVwiZmlyc3QtcmVuZXdhbCBtdXRlZFwiPiR7YnVpbGRTdWJzY3JpcHRpb25GaXJzdFJlbmV3YWxTdHJpbmcoY2FydE1ldGEpfTwvcD5cbjwvZGl2PmA7XG4gICAgfVxuICAgICRxcygnI3BwLXN1bW1hcnktbGluZXMtYm9keScpPy5pbnNlcnRBZGphY2VudEhUTUwoJ2JlZm9yZWVuZCcsIGNhcnRTdW1tYXJpZXNIVE1MKTtcbiAgICAkcXMoJyNwcC1zdW1tYXJ5LWxpbmVzLWJvZHktZXhpc3RpbmcnKT8uaW5zZXJ0QWRqYWNlbnRIVE1MKCdiZWZvcmVlbmQnLCBjYXJ0U3VtbWFyaWVzSFRNTCk7XG4gICAgJHFzKCcjcHAtc3VtbWFyeS1saW5lcy1ib2R5LW1vYmlsZScpPy5pbnNlcnRBZGphY2VudEhUTUwoJ2JlZm9yZWVuZCcsIGNhcnRTdW1tYXJpZXNIVE1MKTtcbn1cbmZ1bmN0aW9uIGNsZWFyUmVuZGVyZWRTdW1tYXJpZXMoKSB7XG4gICAgZm9yIChjb25zdCAkc3VtbWFyeSBvZiAkcXNBbGwoJy5jYXJ0LXN1bW1hcnknKSl7XG4gICAgICAgICRzdW1tYXJ5LnJlbW92ZSgpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlclN1bW1hcnlMaW5lKG5hbWUsIGFtb3VudCwgY2FydE1ldGEpIHtcbiAgICBsZXQgcHJpY2VNZXRhSFRNTCA9ICcnO1xuICAgIGlmIChjYXJ0TWV0YS5zdWJzY3JpcHRpb24pIHtcbiAgICAgICAgcHJpY2VNZXRhSFRNTCA9IGA8c3BhbiBjbGFzcz1cIm11dGVkXCI+JHtidWlsZFN1YnNjcmlwdGlvblByaWNlTWV0YURhdGEoY2FydE1ldGEpfTwvc3Bhbj5gO1xuICAgIH1cbiAgICByZXR1cm4gYFxuPHRyIGNsYXNzPVwic3VtbWFyeS1saW5lXCIgZGF0YS1yYXctY29zdD1cIiR7YW1vdW50fVwiPlxuXHQ8dGQ+JHtuYW1lfTwvdGQ+XG5cdDx0ZD4ke2Zvcm1hdEN1cnJlbmN5U3RyaW5nKGFtb3VudCl9JHtwcmljZU1ldGFIVE1MfTwvdGQ+XG48L3RyPmA7XG59XG5mdW5jdGlvbiBvcmRlclN1bW1hcnlEcm9wZG93bigpIHtcbiAgICBpZiAod2luZG93Lm1hdGNoTWVkaWEoJyhtYXgtd2lkdGg6IDkwMHB4KScpLm1hdGNoZXMpIHtcbiAgICAgICAgbGV0IG5ld0N1c3RvbWVyID0gJHFzKCcjcHAtZHJvcGRvd24tbmV3Jyk/LmdldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcpO1xuICAgICAgICBpZiAobmV3Q3VzdG9tZXIgPT09ICd0cnVlJykge1xuICAgICAgICAgICAgJHFzKCcjcHAtZHJvcGRvd24tbmV3Jyk/LnNldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcsICdmYWxzZScpO1xuICAgICAgICAgICAgbmV3Q3VzdG9tZXIgPSAkcXMoJyNwcC1kcm9wZG93bi1uZXcnKT8uZ2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAkcXMoJyNwcC1kcm9wZG93bi1uZXcnKT8uc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgJ3RydWUnKTtcbiAgICAgICAgICAgIG5ld0N1c3RvbWVyID0gJHFzKCcjcHAtZHJvcGRvd24tbmV3Jyk/LmdldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcpO1xuICAgICAgICB9XG4gICAgICAgIGlmIChuZXdDdXN0b21lciA9PT0gJ3RydWUnKSB7XG4gICAgICAgICAgICAkcXMoJyNkcm9wZG93bi1kb3duLW5ldycpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgICAgICAkcXMoJyNkcm9wZG93bi11cC1uZXcnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAgICAgJHFzKCcjb3JkZXItc3VtbWFyeS1jb250ZW50cy1uZXcnKT8uY2xhc3NMaXN0LmFkZCgnb3JkZXItc3VtbWFyeS1jb250ZW50cy1uZXctb3BlbmVkJyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAkcXMoJyNkcm9wZG93bi1kb3duLW5ldycpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICAgICAkcXMoJyNkcm9wZG93bi11cC1uZXcnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICAgICAgJHFzKCcjb3JkZXItc3VtbWFyeS1jb250ZW50cy1uZXcnKT8uY2xhc3NMaXN0LnJlbW92ZSgnb3JkZXItc3VtbWFyeS1jb250ZW50cy1uZXctb3BlbmVkJyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgbGV0IGV4aXN0aW5nID0gJHFzKCcjcHAtZHJvcGRvd24nKT8uZ2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJyk7XG4gICAgaWYgKGV4aXN0aW5nID09PSAndHJ1ZScpIHtcbiAgICAgICAgJHFzKCcjcHAtZHJvcGRvd24nKT8uc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgJ2ZhbHNlJyk7XG4gICAgICAgIGV4aXN0aW5nID0gJHFzKCcjcHAtZHJvcGRvd24nKT8uZ2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJyk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzKCcjcHAtZHJvcGRvd24nKT8uc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgJ3RydWUnKTtcbiAgICAgICAgZXhpc3RpbmcgPSAkcXMoJyNwcC1kcm9wZG93bicpPy5nZXRBdHRyaWJ1dGUoJ2FyaWEtZXhwYW5kZWQnKTtcbiAgICB9XG4gICAgaWYgKGV4aXN0aW5nID09PSAndHJ1ZScpIHtcbiAgICAgICAgJHFzKCcuZHJvcGRvd24tZG93bicpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgICRxcygnLmRyb3Bkb3duLXVwJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjb3JkZXItc3VtbWFyeS1jb250ZW50cycpPy5jbGFzc0xpc3QuYWRkKCdvcmRlci1zdW1tYXJ5LWNvbnRlbnRzLW9wZW5lZCcpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnLmRyb3Bkb3duLWRvd24nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAkcXMoJy5kcm9wZG93bi11cCcpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgICRxcygnI29yZGVyLXN1bW1hcnktY29udGVudHMnKT8uY2xhc3NMaXN0LnJlbW92ZSgnb3JkZXItc3VtbWFyeS1jb250ZW50cy1vcGVuZWQnKTtcbiAgICB9XG59XG5mdW5jdGlvbiByZW5kZXJDYXJ0VG90YWxzKCkge1xuICAgICRxc0FsbCgnLnBwLXN1bW1hcnktdG90YWwnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgPSAnJ1xuICAgICk7XG4gICAgZm9yIChjb25zdCBjYXJ0S2V5IG9mIE9iamVjdC5rZXlzKHN0b3JlLmdldFN0YXRlKCkuY2FsY3VsYXRlZENhcnRzKSl7XG4gICAgICAgIGNvbnN0IGNhbGN1bGF0ZWRDYXJ0ID0gc3RvcmUuZ2V0U3RhdGUoKS5jYWxjdWxhdGVkQ2FydHNbY2FydEtleV07XG4gICAgICAgIGlmICghY2FsY3VsYXRlZENhcnQpIHtcbiAgICAgICAgICAgIGNvbnRpbnVlO1xuICAgICAgICB9XG4gICAgICAgIHJlbmRlckNhcnRUb3RhbChjYWxjdWxhdGVkQ2FydC5zdW1tYXJ5LnRvdGFsLCBjYWxjdWxhdGVkQ2FydC5jYXJ0X21ldGEpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckNhcnRUb3RhbCh0b3RhbCwgY2FydE1ldGEpIHtcbiAgICBpZiAoIWNhcnRNZXRhLnN1YnNjcmlwdGlvbikge1xuICAgICAgICAkcXNBbGwoJy5wcC1zdW1tYXJ5LXRvdGFsJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuaW5uZXJIVE1MICs9IGA8c3Bhbj4ke2Zvcm1hdEN1cnJlbmN5U3RyaW5nKHRvdGFsKX08L3NwYW4+YFxuICAgICAgICApO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxc0FsbCgnLnBwLXN1bW1hcnktdG90YWwnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgKz0gYCA8c3BhbiBjbGFzcz1cIm11dGVkXCI+ICsgPC9zcGFuPjxzcGFuIGNsYXNzPVwibXV0ZWRcIj4ke2Zvcm1hdEN1cnJlbmN5U3RyaW5nKHRvdGFsKX0ke2J1aWxkU3Vic2NyaXB0aW9uUHJpY2VNZXRhRGF0YShjYXJ0TWV0YSwgdHJ1ZSl9PC9zcGFuPmBcbiAgICAgICAgKTtcbiAgICB9XG59XG5mdW5jdGlvbiBpbml0TW9kYWwoKSB7XG4gICAgc3RvcmUuc3Vic2NyaWJlKCgpPT57XG4gICAgICAgIGlmIChFbnZpcm9ubWVudC5tb2RhbFVJLm9wZW4oKSkge1xuICAgICAgICAgICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLmRpc3BsYXkgPSAnZmxleCdcbiAgICAgICAgICAgICk7XG4gICAgICAgIH0gZWxzZSBpZiAoIUVudmlyb25tZW50Lm1vZGFsVUkub3BlbigpKSB7XG4gICAgICAgICAgICAkcXMoJyNwcC1tb2RhbC1jb250ZW50JywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUuZGlzcGxheSA9ICdub25lJ1xuICAgICAgICAgICAgKTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuICAgICAgICByZW5kZXJCdXR0b25Db2xvclRoZW1lKEVudmlyb25tZW50LnBsdWdpbi5idXR0b25Db2xvcigpKTtcbiAgICAgICAgcmVuZGVyVGVzdE1vZGVCYW5uZXJEaXNwbGF5KEVudmlyb25tZW50LnRlc3RNb2RlKCkpO1xuICAgICAgICByZW5kZXJNb2RhbFBhZ2VJbmRpY2F0b3IoRW52aXJvbm1lbnQubW9kYWxVSS5wYWdlKCkpO1xuICAgICAgICByZW5kZXJNb2RhbE5hdmlnYXRpb24oRW52aXJvbm1lbnQubW9kYWxVSS5wYWdlKCkpO1xuICAgICAgICByZW5kZXJDb250aW51ZUJ1dHRvbkRpc3BsYXkoRW52aXJvbm1lbnQubW9kYWxVSS5wYWdlKCkpO1xuICAgICAgICByZW5kZXJDb250aW51ZUJ1dHRvbkxvYWRpbmcoRW52aXJvbm1lbnQubW9kYWxVSS5sb2FkaW5nTW9kZSgpKTtcbiAgICAgICAgcmVuZGVySW5mb1BhZ2VEaXNwbGF5KEVudmlyb25tZW50Lm1vZGFsVUkucGFnZSgpKTtcbiAgICAgICAgcmVuZGVyUGF5bWVudFBhZ2VEaXNwbGF5KEVudmlyb25tZW50Lm1vZGFsVUkucGFnZSgpLCBFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpLCBFbnZpcm9ubWVudC5jdXN0b21lci5tb2JpbGUoKSk7XG4gICAgICAgIGRpc3BsYXlFcnJvck1lc3NhZ2UoUGVhY2hQYXlPcmRlci5lcnJvck1lc3NhZ2UoKSk7XG4gICAgfSk7XG4gICAgb25XaW5kb3dNZXNzYWdlKCdVSTo6bW9kYWxPcGVuZWQnLCAoXyk9PntcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlRW52aXJvbm1lbnQoe1xuICAgICAgICAgICAgbW9kYWxJc09wZW46IHRydWVcbiAgICAgICAgfSkpO1xuICAgIH0pO1xuICAgIG9uV2luZG93TWVzc2FnZSgnVUk6Om1vZGFsQ2xvc2VkJywgKF8pPT57XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUVudmlyb25tZW50KHtcbiAgICAgICAgICAgIG1vZGFsSXNPcGVuOiBmYWxzZVxuICAgICAgICB9KSk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgfSk7XG4gICAgb25XaW5kb3dNZXNzYWdlKCdoaWRlQ29udGludWVTcGlubmVyJywgKF8pPT57XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgfSk7XG4gICAgb25XaW5kb3dNZXNzYWdlKCdidXR0b25DbGlja2VkJywgYXN5bmMgKCk9PntcbiAgICAgICAgb3BlbkNoZWNrb3V0TW9kYWwoKTtcbiAgICAgICAgdmFsaWRhdGVDYXJ0SXRlbXNXaXRoQ3VzdG9tZXIoRGVmYXVsdENhcnQuY29udGVudHMoKSwgdHJ1ZSk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0YXJ0TW9kYWxMb2FkaW5nKCkpO1xuICAgICAgICBhd2FpdCByZXF1ZXN0Q2FydENhbGN1bGF0aW9uKCFFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RvcE1vZGFsTG9hZGluZygpKTtcbiAgICB9KTtcbiAgICBvbldpbmRvd01lc3NhZ2UoJ3N0b3BQYXltZW50UHJvY2Vzc2luZ0FuaW1hdGlvbnMnLCAobWVzc2FnZSk9PntcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RvcE1vZGFsTG9hZGluZygpKTtcbiAgICAgICAgaWYgKG1lc3NhZ2UuY2xvc2VNb2RhbCkge1xuICAgICAgICAgICAgcmVxdWVzdENsb3NlTW9kYWwoKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAobWVzc2FnZS5lcnJvck1lc3NhZ2UpIHtcbiAgICAgICAgICAgIHN0b3JlLmRpc3BhdGNoKHNldE9yZGVyRXJyb3IobWVzc2FnZS5lcnJvck1lc3NhZ2UpKTtcbiAgICAgICAgfVxuICAgIH0pO1xuICAgICRxcygnLnBwLWV4aXQnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCByZXF1ZXN0Q2xvc2VNb2RhbCk7XG4gICAgJHFzKCcucHAtY2xvc2UnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCByZXF1ZXN0Q2xvc2VNb2RhbCk7XG4gICAgJHFzKCcjZWRpdC1pbmZvJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgYmFja1RvSW5mbyk7XG4gICAgZm9yIChjb25zdCAkZWxlbWVudDEgb2YgJHFzQWxsKCcucHAtYmFjay10by1pbmZvJykpe1xuICAgICAgICAkZWxlbWVudDEuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBiYWNrVG9JbmZvKTtcbiAgICB9XG59XG5mdW5jdGlvbiBkaXNwbGF5RXJyb3JNZXNzYWdlKGVycm9yTWVzc2FnZSkge1xuICAgIGlmIChlcnJvck1lc3NhZ2UgIT09ICcnKSB7XG4gICAgICAgICRxcygnI3NoaXBwaW5nLW9wdGlvbnMtY29udGFpbmVyJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjcHAtcGF5bWVudC1mb3JtJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjcGF5bWVudC1tZXRob2RzJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgZm9yIChjb25zdCAkZWxlbWVudDMgb2YgJHFzQWxsKCcucGF5LWJ1dHRvbi1jb250YWluZXInKSl7XG4gICAgICAgICAgICAkZWxlbWVudDMuY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICB9XG4gICAgICAgIGZvciAoY29uc3QgJGVsZW1lbnQyIG9mICRxc0FsbCgnLmhpZGUtd2hlbi1pbnZhbGlkJykpe1xuICAgICAgICAgICAgJGVsZW1lbnQyLmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgICAgICAkcXMoJyNpbnZhbGlkLW9yZGVyLW1lc3NhZ2UnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAkcXMoJyNpbnZhbGlkLW9yZGVyLW1lc3NhZ2UtZXhpc3RpbmcnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAkcXMoJyNpbnZhbGlkLW9yZGVyLW1lc3NhZ2UnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5pbm5lckhUTUwgPSBlcnJvck1lc3NhZ2VcbiAgICAgICAgKTtcbiAgICAgICAgJHFzKCcjaW52YWxpZC1vcmRlci1tZXNzYWdlLWV4aXN0aW5nJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuaW5uZXJIVE1MID0gZXJyb3JNZXNzYWdlXG4gICAgICAgICk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzKCcjc2hpcHBpbmctb3B0aW9ucy1jb250YWluZXInKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAkcXMoJyNwcC1wYXltZW50LWZvcm0nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAkcXMoJyNwYXltZW50LW1ldGhvZHMnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICBmb3IgKGNvbnN0ICRlbGVtZW50IG9mICRxc0FsbCgnLnBheS1idXR0b24tY29udGFpbmVyJykpe1xuICAgICAgICAgICAgJGVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICB9XG4gICAgICAgIGZvciAoY29uc3QgJGVsZW1lbnQ1IG9mICRxc0FsbCgnLmhpZGUtd2hlbi1pbnZhbGlkJykpe1xuICAgICAgICAgICAgJGVsZW1lbnQ1LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgICAgICAkcXMoJyNpbnZhbGlkLW9yZGVyLW1lc3NhZ2UnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICAkcXMoJyNpbnZhbGlkLW9yZGVyLW1lc3NhZ2UtZXhpc3RpbmcnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIG9wZW5DaGVja291dE1vZGFsKCkge1xuICAgIHdpbmRvdy50b3A/LnBvc3RNZXNzYWdlKCdvcGVuTW9kYWwnLCAnKicpO1xufVxuZnVuY3Rpb24gcmVxdWVzdENsb3NlTW9kYWwoKSB7XG4gICAgc3luY09yZGVyTm90ZXModHJ1ZSk7XG4gICAgd2luZG93LnRvcD8ucG9zdE1lc3NhZ2UoJ2Nsb3NlTW9kYWwnLCAnKicpO1xufVxuZnVuY3Rpb24gYmFja1RvSW5mbygpIHtcbiAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVFbnZpcm9ubWVudCh7XG4gICAgICAgIG1vZGFsUGFnZVR5cGU6ICdpbmZvJyxcbiAgICAgICAgY3VzdG9tZXJFeGlzdHM6IGZhbHNlXG4gICAgfSkpO1xuICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUN1c3RvbWVyQWRkcmVzc1ZhbGlkYXRpb24oZmFsc2UpKTtcbiAgICBzeW5jT3JkZXJOb3RlcygpO1xufVxuZnVuY3Rpb24gcmVuZGVyQ29udGludWVCdXR0b25EaXNwbGF5KG1vZGFsUGFnZSkge1xuICAgIGlmIChtb2RhbFBhZ2UgPT09ICdpbmZvJykge1xuICAgICAgICAkcXMoJyNwcC1jb250aW51ZScpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICRxcygnI3BwLWNvbnRpbnVlLW1vYmlsZScpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgJHFzKCcjcHAtY29udGludWUnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICAkcXMoJyNwcC1jb250aW51ZS1tb2JpbGUnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckNvbnRpbnVlQnV0dG9uTG9hZGluZyhsb2FkaW5nTW9kZSkge1xuICAgIGlmIChsb2FkaW5nTW9kZSA9PT0gJ2xvYWRpbmcnKSB7XG4gICAgICAgICRxcygnI3BwLWNvbnRpbnVlJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuZGlzYWJsZWQgPSB0cnVlXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI3BwLWNvbnRpbnVlLW1vYmlsZScsICgkZWxlbWVudCk9PiRlbGVtZW50LmRpc2FibGVkID0gdHJ1ZVxuICAgICAgICApO1xuICAgICAgICAkcXMoJyNjb250aW51ZS1zcGlubmVyJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjY29udGludWUtc3Bpbm5lci1tb2JpbGUnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnI3BwLWNvbnRpbnVlJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuZGlzYWJsZWQgPSBmYWxzZVxuICAgICAgICApO1xuICAgICAgICAkcXMoJyNwcC1jb250aW51ZS1tb2JpbGUnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5kaXNhYmxlZCA9IGZhbHNlXG4gICAgICAgICk7XG4gICAgICAgICRxcygnI2NvbnRpbnVlLXNwaW5uZXInKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICAkcXMoJyNjb250aW51ZS1zcGlubmVyLW1vYmlsZScpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgfVxufVxuZnVuY3Rpb24gcmVuZGVyTW9kYWxOYXZpZ2F0aW9uKG1vZGFsUGFnZSkge1xuICAgIGlmIChtb2RhbFBhZ2UgPT09ICdpbmZvJykge1xuICAgICAgICAkcXMoJy5wcC1leGl0Jyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgZm9yIChjb25zdCAkZWxlbWVudCBvZiAkcXNBbGwoJy5wcC1iYWNrLXRvLWluZm8nKSl7XG4gICAgICAgICAgICAkZWxlbWVudC5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgIH1cbiAgICB9IGVsc2UgaWYgKG1vZGFsUGFnZSA9PT0gJ3BheW1lbnQnKSB7XG4gICAgICAgICRxcygnLnBwLWV4aXQnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICBmb3IgKGNvbnN0ICRlbGVtZW50IG9mICRxc0FsbCgnLnBwLWJhY2stdG8taW5mbycpKXtcbiAgICAgICAgICAgICRlbGVtZW50LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgfVxuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlck1vZGFsUGFnZUluZGljYXRvcihtb2RhbFBhZ2UpIHtcbiAgICBpZiAobW9kYWxQYWdlID09PSAnaW5mbycpIHtcbiAgICAgICAgJHFzKCcuY29sb3ItY2hhbmdpbmctaW5mbycpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICRxcygnI2NoZWNrb3V0LXN0YXR1cycpPy5jbGFzc0xpc3QuYWRkKCdjaXJjbGUtbG9nby1vbmUnKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICAkcXMoJy5jb2xvci1jaGFuZ2luZy1pbmZvJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjY2hlY2tvdXQtc3RhdHVzJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2NpcmNsZS1sb2dvLW9uZScpO1xuICAgIH1cbiAgICBpZiAobW9kYWxQYWdlID09PSAncGF5bWVudCcpIHtcbiAgICAgICAgJHFzKCcuY29sb3ItY2hhbmdpbmctcGF5bWVudCcpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICRxcygnI2NoZWNrb3V0LXN0YXR1cycpPy5jbGFzc0xpc3QuYWRkKCdjaXJjbGUtbG9nby10d28nKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICAkcXMoJy5jb2xvci1jaGFuZ2luZy1wYXltZW50Jyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjY2hlY2tvdXQtc3RhdHVzJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2NpcmNsZS1sb2dvLXR3bycpO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlclRlc3RNb2RlQmFubmVyRGlzcGxheSh0ZXN0TW9kZSkge1xuICAgIGlmICh0ZXN0TW9kZSkge1xuICAgICAgICAkcXMoJyNwcC1tb2RhbC1jb250ZW50Jyk/LmNsYXNzTGlzdC5hZGQoJ3Rlc3QtbW9kZS1ib3JkZXInKTtcbiAgICAgICAgJHFzKCcudGVzdC1tb2RlLWJhbm5lcicpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICRxcygnI3BwLW1vZGFsLWNvbnRlbnQnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5wYWRkaW5nVG9wID0gJzEuMjVyZW0nXG4gICAgICAgICk7XG4gICAgICAgICRxcygnLnBwLWNsb3NlJywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc3R5bGUudG9wID0gJzAuOHJlbSdcbiAgICAgICAgKTtcbiAgICAgICAgJHFzKCcucHAtY2xvc2UnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zdHlsZS5yaWdodCA9ICc0cHgnXG4gICAgICAgICk7XG4gICAgfVxufVxuZnVuY3Rpb24gcmVuZGVyQnV0dG9uQ29sb3JUaGVtZShjb2xvciA9ICcjRkY4NzZDJykge1xuICAgIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zdHlsZS5zZXRQcm9wZXJ0eSgnLS1wZWFjaHBheS10aGVtZS1jb2xvcicsIGNvbG9yKTtcbn1cbmZ1bmN0aW9uIHJlbmRlclBheW1lbnRQYWdlRGlzcGxheShtb2RhbFBhZ2UsIGV4aXN0aW5nQ3VzdG9tZXIsIGlzTW9iaWxlKSB7XG4gICAgaWYgKG1vZGFsUGFnZSA9PT0gJ3BheW1lbnQnKSB7XG4gICAgICAgIGlmIChleGlzdGluZ0N1c3RvbWVyKSB7XG4gICAgICAgICAgICAkcXMoJyNwcC1uZXctY3VzdG9tZXItY2hlY2tvdXQnKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICAgICAgJHFzKCcjcHAtZXhpc3RpbmctY3VzdG9tZXItY2hlY2tvdXQnKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICAgICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcpPy5jbGFzc0xpc3QuYWRkKCdjb2wnKTtcbiAgICAgICAgICAgICRxcygnI3BwLW1vZGFsLWNvbnRlbnQnKT8uY2xhc3NMaXN0LmFkZCgndy1leGlzdGluZy1jaGVja291dCcpO1xuICAgICAgICAgICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcpPy5jbGFzc0xpc3QuYWRkKCdwLTEtNScpO1xuICAgICAgICAgICAgJHFzKCcub3JkZXItc3VtbWFyeS1oZWFkaW5nJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgICAgICRxcygnI3BwLXN1bW1hcnktYm9keScsICgkZWxlbWVudCk9PiRlbGVtZW50LnN0eWxlLmJvcmRlckJvdHRvbSA9ICdub25lJ1xuICAgICAgICAgICAgKTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgJGVsZW1lbnQ2IG9mICRxc0FsbCgnLnNwbGl0Jykpe1xuICAgICAgICAgICAgICAgICRlbGVtZW50Ni5zdHlsZS5zZXRQcm9wZXJ0eSgnZmxvYXQnLCAnbGVmdCcsICdpbXBvcnRhbnQnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICRxcygnI3BwLW5ldy1jdXN0b21lci1jaGVja291dCcpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICAgICAkcXMoJyNwcC1leGlzdGluZy1jdXN0b21lci1jaGVja291dCcpPy5jbGFzc0xpc3QuYWRkKCdoaWRlJyk7XG4gICAgICAgICAgICAkcXMoJyNwcC1tb2RhbC1jb250ZW50Jyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2NvbCcpO1xuICAgICAgICAgICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcpPy5jbGFzc0xpc3QucmVtb3ZlKCd3LWV4aXN0aW5nLWNoZWNrb3V0Jyk7XG4gICAgICAgICAgICAkcXMoJyNwcC1tb2RhbC1jb250ZW50Jyk/LmNsYXNzTGlzdC5yZW1vdmUoJ3AtMS01Jyk7XG4gICAgICAgIH1cbiAgICAgICAgJHFzKCcjZXh0cmEtZmllbGRzLXNlY3Rpb24nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgIGlmIChpc01vYmlsZSkge1xuICAgICAgICAgICAgJHFzKCcjZXh0cmEtZmllbGRzLXNlY3Rpb24nKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgICAgICB9XG4gICAgICAgICRxcygnLm9yZGVyLXN1bW1hcnktaGVhZGluZycpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICRxcygnI3BwLW5ldy1jdXN0b21lci1jaGVja291dCcpPy5jbGFzc0xpc3QucmVtb3ZlKCdoaWRlJyk7XG4gICAgICAgICRxcygnI3BwLWV4aXN0aW5nLWN1c3RvbWVyLWNoZWNrb3V0Jyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcpPy5jbGFzc0xpc3QucmVtb3ZlKCdjb2wnKTtcbiAgICAgICAgJHFzKCcjcHAtbW9kYWwtY29udGVudCcpPy5jbGFzc0xpc3QucmVtb3ZlKCd3LWV4aXN0aW5nLWNoZWNrb3V0Jyk7XG4gICAgICAgICRxcygnI3BwLW1vZGFsLWNvbnRlbnQnKT8uY2xhc3NMaXN0LnJlbW92ZSgncC0xLTUnKTtcbiAgICB9XG59XG5mdW5jdGlvbiByZW5kZXJJbmZvUGFnZURpc3BsYXkobW9kYWxQYWdlKSB7XG4gICAgaWYgKG1vZGFsUGFnZSA9PT0gJ2luZm8nKSB7XG4gICAgICAgICRxcygnI3BwLWluZm8nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgICAgICBmb3IgKGNvbnN0ICRlbGVtZW50IG9mICRxc0FsbCgnLnNwbGl0Jykpe1xuICAgICAgICAgICAgJGVsZW1lbnQuc3R5bGUuc2V0UHJvcGVydHkoJ2Zsb2F0JywgJ25vbmUnLCAnaW1wb3J0YW50Jyk7XG4gICAgICAgIH1cbiAgICB9IGVsc2Uge1xuICAgICAgICAkcXMoJyNwcC1pbmZvJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICB9XG59XG5mdW5jdGlvbiBpbml0TWV0cmljcygpIHtcbiAgICAkcXMoJyNwcC1wYXknKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKT0+cmVjb3JkTm9uUFBCdXR0b25DbGljaygncHAtcGF5JylcbiAgICApO1xuICAgICRxcygnI3BwLXBheS1tb2JpbGUnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKT0+cmVjb3JkTm9uUFBCdXR0b25DbGljaygncHAtcGF5LW1vYmlsZScpXG4gICAgKTtcbiAgICAkcXMoJyNwcC1wYXktZXhpc3RpbmcnKT8uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKT0+cmVjb3JkTm9uUFBCdXR0b25DbGljaygncHAtcGF5LWV4aXN0aW5nJylcbiAgICApO1xuICAgICRxcygnI3BwLWNvbnRpbnVlJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKCk9PnJlY29yZE5vblBQQnV0dG9uQ2xpY2soJ3BwLWNvbnRpbnVlJylcbiAgICApO1xuICAgICRxcygnI3BwLWNvbnRpbnVlLW1vYmlsZScpPy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpPT5yZWNvcmROb25QUEJ1dHRvbkNsaWNrKCdwcC1jb250aW51ZS1tb2JpbGUnKVxuICAgICk7XG4gICAgb25XaW5kb3dNZXNzYWdlKCdidXR0b25DbGlja2VkJywgKG1lc3NhZ2UpPT57XG4gICAgICAgIHJlY29yZEJ1dHRvbkNsaWNrKGJ1dHRvblR5cGVWYWxpZGF0aW9uKG1lc3NhZ2UuYnV0dG9uSUQpLCBnZXRQUEJ1dHRvbkxvY2F0aW9uKG1lc3NhZ2UpKTtcbiAgICB9KTtcbn1cbmZ1bmN0aW9uIGJ1dHRvblR5cGVWYWxpZGF0aW9uKGJ1dHRvblR5cGUpIHtcbiAgICByZXR1cm4gYnV0dG9uVHlwZSA9PT0gdW5kZWZpbmVkID8gJ3Vua25vd24nIDogYnV0dG9uVHlwZTtcbn1cbmZ1bmN0aW9uIHJlY29yZE5vblBQQnV0dG9uQ2xpY2soYnV0dG9uVHlwZSkge1xuICAgIHJlY29yZEJ1dHRvbkNsaWNrKGJ1dHRvblR5cGUsIFBQQnV0dG9uTG9jYXRpb24uTm90QXBwbGljYWJsZSk7XG59XG5mdW5jdGlvbiByZWNvcmRCdXR0b25DbGljayhidXR0b25UeXBlLCBwcEJ1dHRvbkxvY2F0aW9uKSB7XG4gICAgcG9zdEJ1dHRvbk1ldHJpY3Moe1xuICAgICAgICBkb21haW46IE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpLFxuICAgICAgICBidXR0b25UeXBlLFxuICAgICAgICBwcEJ1dHRvbkxvY2F0aW9uLFxuICAgICAgICBpc01vYmlsZTogRW52aXJvbm1lbnQuY3VzdG9tZXIubW9iaWxlKCksXG4gICAgICAgIGlzVGVzdE1vZGU6IEVudmlyb25tZW50LnRlc3RNb2RlKClcbiAgICB9KTtcbn1cbnZhciBQUEJ1dHRvbkxvY2F0aW9uO1xuKGZ1bmN0aW9uKFBQQnV0dG9uTG9jYXRpb24xKSB7XG4gICAgUFBCdXR0b25Mb2NhdGlvbjFbXCJQcm9kdWN0XCJdID0gJ3Byb2R1Y3QnO1xuICAgIFBQQnV0dG9uTG9jYXRpb24xW1wiQ2hlY2tvdXRcIl0gPSAnY2hlY2tvdXQnO1xuICAgIFBQQnV0dG9uTG9jYXRpb24xW1wiTWluaUNhcnRcIl0gPSAnbWluaS1jYXJ0JztcbiAgICBQUEJ1dHRvbkxvY2F0aW9uMVtcIkNhcnRcIl0gPSAnY2FydCc7XG4gICAgUFBCdXR0b25Mb2NhdGlvbjFbXCJOb3RBcHBsaWNhYmxlXCJdID0gJ25vdC1hcHBsaWNhYmxlJztcbn0pKFBQQnV0dG9uTG9jYXRpb24gfHwgKFBQQnV0dG9uTG9jYXRpb24gPSB7fSkpO1xuZnVuY3Rpb24gZ2V0UFBCdXR0b25Mb2NhdGlvbihtZXNzYWdlKSB7XG4gICAgaWYgKG1lc3NhZ2UuaXNNaW5pQ2FydCkge1xuICAgICAgICByZXR1cm4gUFBCdXR0b25Mb2NhdGlvbi5NaW5pQ2FydDtcbiAgICB9XG4gICAgc3dpdGNoKEVudmlyb25tZW50LnBsdWdpbi5wYWdlVHlwZSgpKXtcbiAgICAgICAgY2FzZSAnY2FydCc6XG4gICAgICAgICAgICByZXR1cm4gUFBCdXR0b25Mb2NhdGlvbi5DYXJ0O1xuICAgICAgICBjYXNlICdjaGVja291dCc6XG4gICAgICAgICAgICByZXR1cm4gUFBCdXR0b25Mb2NhdGlvbi5DaGVja291dDtcbiAgICAgICAgY2FzZSAncHJvZHVjdCc6XG4gICAgICAgICAgICByZXR1cm4gUFBCdXR0b25Mb2NhdGlvbi5Qcm9kdWN0O1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgcmV0dXJuIFBQQnV0dG9uTG9jYXRpb24uTm90QXBwbGljYWJsZTtcbiAgICB9XG59XG5mdW5jdGlvbiBwb3N0QnV0dG9uTWV0cmljcyhvcHRpb25zKSB7XG4gICAgaWYgKCFvcHRpb25zLmlzVGVzdE1vZGUpIHtcbiAgICAgICAgZmV0Y2goYGh0dHBzOi8vMmZhZDZ3M2V4Zy5leGVjdXRlLWFwaS51cy1lYXN0LTEuYW1hem9uYXdzLmNvbS92MS9idXR0b25zdGF0cz9kb21haW49JHtvcHRpb25zLmRvbWFpbn0mYnV0dG9uVHlwZT0ke29wdGlvbnMuYnV0dG9uVHlwZX0mcHBCdXR0b25Mb2NhdGlvbj0ke1N0cmluZyhvcHRpb25zLnBwQnV0dG9uTG9jYXRpb24pfSZpc01vYmlsZT0ke1N0cmluZyhvcHRpb25zLmlzTW9iaWxlKX0maXNQcm9kdWN0aW9uRGF0YT0ke1N0cmluZyhpc1Byb2R1Y3Rpb25Eb21haW4ob3B0aW9ucy5kb21haW4pKX1gKS50aGVuKCgpPT57fSkuY2F0Y2goKCk9Pnt9KTtcbiAgICB9XG59XG5mdW5jdGlvbiBpc1Byb2R1Y3Rpb25Eb21haW4oZG9tYWluKSB7XG4gICAgc3dpdGNoKGRvbWFpbil7XG4gICAgICAgIGNhc2UgJ2xvY2FsaG9zdCc6XG4gICAgICAgIGNhc2UgJzEyNy4wLjAuMSc6XG4gICAgICAgIGNhc2UgJ3N0b3JlLmxvY2FsJzpcbiAgICAgICAgY2FzZSAnd29vLnN0b3JlLmxvY2FsJzpcbiAgICAgICAgY2FzZSAnd29vLnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ3RoZW1lMS5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICd0aGVtZTIucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAndGhlbWUzLnBlYWNocGF5LmFwcCc6XG4gICAgICAgIGNhc2UgJ3RoZW1lNC5wZWFjaHBheS5hcHAnOlxuICAgICAgICBjYXNlICd0aGVtZTUucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAncWEucGVhY2hwYXkuYXBwJzpcbiAgICAgICAgY2FzZSAnZGVtby5wZWFjaHBheS5hcHAnOlxuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfVxufVxuY29uc3QgZGVmYXVsdEZvcm1IVE1MID0gYDxmb3JtIGlkPVwicHAtaW5mby1mb3JtXCI+XG48aDI+PHNwYW4gY2xhc3M9XCJib2xkXCIgZGF0YS1pMThuPVwicGVyc29uYWxcIj48L3NwYW4+PC9oMj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJlbWFpbFwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHR5cGU9XCJlbWFpbFwiIG5hbWU9XCJlbWFpbFwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJlbWFpbFwiIGRhdGEtaTE4bj1cImVtYWlsXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJwaG9uZVwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHR5cGU9XCJ0ZWxcIiBuYW1lPVwicGhvbmVcIiBwbGFjZWhvbGRlcj1cIiBcInJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJwaG9uZVwiIGRhdGEtaTE4bj1cInBob25lXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG48L2Rpdj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJuYW1lX2ZpcnN0XCIgY2xhc3M9XCJ3LTEwMCB0ZXh0LWlucHV0XCIgdHlwZT1cInRleHRcIiBuYW1lPVwibmFtZV9maXJzdFwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJuYW1lX2ZpcnN0XCIgZGF0YS1pMThuPVwiZmlyc3QtbmFtZVwiIGNsYXNzPVwiZm9ybS1sYWJlbFwiPjwvbGFiZWw+XG5cdFx0PC9kaXY+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJuYW1lX2xhc3RcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJuYW1lX2xhc3RcIiBwbGFjZWhvbGRlcj1cIiBcIiByZXF1aXJlZD5cblx0XHQ8bGFiZWwgZm9yPVwibmFtZV9sYXN0XCIgZGF0YS1pMThuPVwibGFzdC1uYW1lXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG48L2Rpdj5cbjxoMiBjbGFzcz1cInNoaXBwaW5nLWFkZHJlc3MtaGVhZGVyXCI+PHNwYW4gY2xhc3M9XCJib2xkXCIgZGF0YS1pMThuPVwic2hpcHBpbmdcIj48L3NwYW4+PC9oMj5cbjxoMiBjbGFzcz1cImJpbGxpbmctYWRkcmVzcy1oZWFkZXIgaGlkZVwiPjxzcGFuIGNsYXNzPVwiYm9sZFwiIGRhdGEtaTE4bj1cImJpbGxpbmdcIj48L3NwYW4+PC9oMj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNzBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJhZGRyZXNzMVwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cImFkZHJlc3MxXCIgY2xhc3M9XCJ3LTEwMCB0ZXh0LWlucHV0XCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdFx0PGxhYmVsIGZvcj1cImFkZHJlc3MxXCIgZGF0YS1pMThuPVwic3RyZWV0XCIgY2xhc3M9XCJmb3JtLWxhYmVsIGZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy0zMFwiPlxuXHRcdDxpbnB1dCBpZD1cImFkZHJlc3MyXCIgdHlwZT1cInRleHRcIiBuYW1lPVwiYWRkcmVzczJcIiBwbGFjZWhvbGRlcj1cIiBcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIj5cblx0XHQ8bGFiZWwgZm9yPVwiYWRkcmVzczJcIiBkYXRhLWkxOG49XCJhcHRcIiBjbGFzcz1cImZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cbjwvZGl2PlxuPGRpdiBjbGFzcz1cImZsZXhcIj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxpbnB1dCBpZD1cInBvc3RhbFwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cInBvc3RhbFwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJwb3N0YWxcIiBkYXRhLWkxOG49XCJwb3N0YWxcIiBjbGFzcz1cImZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxpbnB1dCBpZD1cImNpdHlcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJjaXR5XCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdFx0PGxhYmVsIGZvcj1cImNpdHlcIiBkYXRhLWkxOG49XCJjaXR5XCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG48L2Rpdj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJwcm92aW5jZVwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cIm9mZlwiIHBsYWNlaG9sZGVyPVwiIFwiPlxuXHRcdDxsYWJlbCBmb3I9XCJwcm92aW5jZVwiIGNsYXNzPVwiZm9ybS1sYWJlbFwiIGRhdGEtaTE4bj1cInByb3ZpbmNlXCI+PC9sYWJlbD5cblx0XHQ8c2VsZWN0IGlkPVwiZHluYW1pYy1zdGF0ZXNcIiBjbGFzcz1cInctMTAwIHNlbGVjdCBoaWRlXCIgbmFtZT1cInN0YXRlXCIgc2l6ZT1cIjFcIj5cblx0XHRcdDxvcHRpb24gaGlkZGVuIGRpc2FibGVkIHNlbGVjdGVkIHZhbHVlPjwvb3B0aW9uPlxuXHRcdDwvc2VsZWN0PlxuXHRcdDxsYWJlbCBmb3I9XCJkeW5hbWljLXN0YXRlc1wiIGNsYXNzPVwiZm9ybS1sYWJlbCByZWdpb24tY291bnRyeS1sYWJlbCBoaWRlXCIgZGF0YS1pMThuPVwic3RhdGVcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxzZWxlY3QgaWQ9XCJjb3VudHJ5XCIgY2xhc3M9XCJ3LTEwMFwiIG5hbWU9XCJjb3VudHJ5XCIgc2l6ZT1cIjFcIiByZXF1aXJlZD5cblx0XHRcdDxvcHRpb24gaGlkZGVuIGRpc2FibGVkIHNlbGVjdGVkIHZhbHVlIGRhdGEtaTE4bj1cImNvdW50cnlcIj48L29wdGlvbj5cblx0XHQ8L3NlbGVjdD5cblx0XHQ8bGFiZWwgZm9yPVwiY291bnRyeVwiIGRhdGEtaTE4bj1cImNvdW50cnktbGFiZWxcIiBjbGFzcz1cImZvcm0tbGFiZWwgcmVnaW9uLWNvdW50cnktbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cbjwvZGl2PlxuPGRpdiBpZD1cImNoZWNrb3V0LWRlbGl2ZXJ5LWRhdGVcIiBjbGFzcz1cImhpZGVcIj5cblx0PGgyIGRhdGEtaTE4bj1cImRlbGl2ZXJ5LWRhdGVcIj48L2gyPlxuXHQ8aW5wdXQgdHlwZT1cImRhdGVcIiBpZD1cImRlbGl2ZXJ5LWRhdGVcIiBuYW1lPVwiZGVsaXZlcnktZGF0ZVwiIHZhbHVlPVwiXCIgbWluPVwiXCI+XG48L2Rpdj5cbjwvZm9ybT5gO1xuY29uc3QgamFwYW5lc2VGb3JtSFRNTCA9IGA8Zm9ybSBpZD1cInBwLWluZm8tZm9ybVwiPlxuPGgyPjxzcGFuIGNsYXNzPVwiYm9sZFwiIGRhdGEtaTE4bj1cInBlcnNvbmFsXCI+PC9zcGFuPjwvaDI+XG48ZGl2IGNsYXNzPVwiZmxleFwiPlxuXHQ8ZGl2IGNsYXNzPVwiZmxleCB3LTUwXCI+XG5cdFx0PGlucHV0IGlkPVwiZW1haWxcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwiZW1haWxcIiBuYW1lPVwiZW1haWxcIiBwbGFjZWhvbGRlcj1cIiBcIiByZXF1aXJlZD5cblx0XHQ8bGFiZWwgZm9yPVwiZW1haWxcIiBkYXRhLWkxOG49XCJlbWFpbFwiIGNsYXNzPVwiZm9ybS1sYWJlbFwiPjwvbGFiZWw+XG5cdDwvZGl2PlxuXHQ8ZGl2IGNsYXNzPVwiZmxleCB3LTUwXCI+XG5cdFx0PGlucHV0IGlkPVwicGhvbmVcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGVsXCIgbmFtZT1cInBob25lXCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdFx0PGxhYmVsIGZvcj1cInBob25lXCIgZGF0YS1pMThuPVwicGhvbmVcIiBjbGFzcz1cImZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cbjwvZGl2PlxuPGRpdiBjbGFzcz1cImZsZXhcIj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxpbnB1dCBpZD1cIm5hbWVfbGFzdFwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiICB0eXBlPVwidGV4dFwiIG5hbWU9XCJuYW1lX2xhc3RcIiBwbGFjZWhvbGRlcj1cIiBcIiByZXF1aXJlZD5cblx0XHQ8bGFiZWwgZm9yPVwibmFtZV9sYXN0XCIgZGF0YS1pMThuPVwibGFzdC1uYW1lXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJuYW1lX2ZpcnN0XCIgY2xhc3M9XCJ3LTEwMCB0ZXh0LWlucHV0XCIgdHlwZT1cInRleHRcIiBuYW1lPVwibmFtZV9maXJzdFwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJuYW1lX2ZpcnN0XCIgZGF0YS1pMThuPVwiZmlyc3QtbmFtZVwiIGNsYXNzPVwiZm9ybS1sYWJlbFwiPjwvbGFiZWw+XG5cdDwvZGl2PlxuPC9kaXY+XG48aDIgY2xhc3M9XCJzaGlwcGluZy1hZGRyZXNzLWhlYWRlclwiPjxzcGFuIGNsYXNzPVwiYm9sZFwiIGRhdGEtaTE4bj1cInNoaXBwaW5nXCI+PC9zcGFuPjwvaDI+XG48aDIgY2xhc3M9XCJiaWxsaW5nLWFkZHJlc3MtaGVhZGVyIGhpZGVcIj48c3BhbiBjbGFzcz1cImJvbGRcIiBkYXRhLWkxOG49XCJiaWxsaW5nXCI+PC9zcGFuPjwvaDI+XG48ZGl2IGNsYXNzPVwiZmxleFwiPlxuXHQ8ZGl2IGNsYXNzPVwiZmxleCB3LTUwXCI+XG5cdFx0PHNlbGVjdCBpZD1cImNvdW50cnlcIiBjbGFzcz1cInctMTAwXCIgbmFtZT1cImNvdW50cnlcIiBzaXplPVwiMVwiIHJlcXVpcmVkPlxuXHRcdFx0PG9wdGlvbiBoaWRkZW4gZGlzYWJsZWQgc2VsZWN0ZWQgdmFsdWUgZGF0YS1pMThuPVwic2VsZWN0LWNvdW50cnlcIj48L29wdGlvbj5cblx0XHQ8L3NlbGVjdD5cblx0XHQ8bGFiZWwgZm9yPVwiY291bnRyeVwiIGRhdGEtaTE4bj1cImNvdW50cnktbGFiZWx5XCIgY2xhc3M9XCJmb3JtLWxhYmVsIHJlZ2lvbi1jb3VudHJ5LWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJwb3N0YWxcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJwb3N0YWxcIiBwbGFjZWhvbGRlcj1cIiBcIiByZXF1aXJlZD5cblx0XHQ8bGFiZWwgZm9yPVwicG9zdGFsXCIgZGF0YS1pMThuPVwicG9zdGFsXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG48L2Rpdj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJwcm92aW5jZVwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cIm9mZlwiIHBsYWNlaG9sZGVyPVwiIFwiPlxuXHRcdDxsYWJlbCBmb3I9XCJwcm92aW5jZVwiIGNsYXNzPVwiZm9ybS1sYWJlbFwiIGRhdGEtaTE4bj1cInByb3ZpbmNlXCI+PC9sYWJlbD5cblx0XHQ8c2VsZWN0IGlkPVwiZHluYW1pYy1zdGF0ZXNcIiBjbGFzcz1cInctMTAwIHNlbGVjdCBoaWRlXCIgbmFtZT1cInN0YXRlXCIgc2l6ZT1cIjFcIj5cblx0XHRcdDxvcHRpb24gaGlkZGVuIGRpc2FibGVkIHNlbGVjdGVkIHZhbHVlPlN0YXRlPC9vcHRpb24+XG5cdFx0PC9zZWxlY3Q+XG5cdFx0PGxhYmVsIGZvcj1cImR5bmFtaWMtc3RhdGVzXCIgY2xhc3M9XCJmb3JtLWxhYmVsIHJlZ2lvbi1jb3VudHJ5LWxhYmVsIGhpZGVcIj5SZWdpb248L2xhYmVsPlxuXHQ8L2Rpdj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxpbnB1dCBpZD1cImNpdHlcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJjaXR5XCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdFx0PGxhYmVsIGZvcj1cImNpdHlcIiBkYXRhLWkxOG49XCJjaXR5XCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG48L2Rpdj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNzBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJhZGRyZXNzMVwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cImFkZHJlc3MxXCIgY2xhc3M9XCJ3LTEwMCB0ZXh0LWlucHV0XCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdFx0PGxhYmVsIGZvcj1cImFkZHJlc3MxXCIgZGF0YS1pMThuPVwic3RyZWV0XCIgY2xhc3M9XCJmb3JtLWxhYmVsIGZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy0zMFwiPlxuXHRcdDxpbnB1dCBpZD1cImFkZHJlc3MyXCIgdHlwZT1cInRleHRcIiBuYW1lPVwiYWRkcmVzczJcIiBwbGFjZWhvbGRlcj1cIiBcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIj5cblx0XHQ8bGFiZWwgZm9yPVwiYWRkcmVzczJcIiBkYXRhLWkxOG49XCJhcHRcIiBjbGFzcz1cImZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cbjwvZGl2PlxuPGRpdiBpZD1cImNoZWNrb3V0LWRlbGl2ZXJ5LWRhdGVcIiBjbGFzcz1cImhpZGVcIj5cblx0PGgyIGRhdGEtaTE4bj1cImRlbGl2ZXJ5LWRhdGVcIj48L2gyPlxuXHQ8aW5wdXQgdHlwZT1cImRhdGVcIiBpZD1cImRlbGl2ZXJ5LWRhdGVcIiBuYW1lPVwiZGVsaXZlcnktZGF0ZVwiIHZhbHVlPVwiXCIgbWluPVwiXCI+XG48L2Rpdj5cbjwvZm9ybT5gO1xuY29uc3QgY2hlY2tvdXRGb3JtTm9QaG9uZU5vQXB0ID0gYDxmb3JtIGlkPVwicHAtaW5mby1mb3JtXCI+XG48aDI+PHNwYW4gY2xhc3M9XCJib2xkXCIgZGF0YS1pMThuPVwicGVyc29uYWxcIj48L3NwYW4+PC9oMj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxpbnB1dCBpZD1cImVtYWlsXCIgY2xhc3M9XCJ3LTEwMCB0ZXh0LWlucHV0XCIgdHlwZT1cImVtYWlsXCIgbmFtZT1cImVtYWlsXCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdDxsYWJlbCBmb3I9XCJlbWFpbFwiIGRhdGEtaTE4bj1cImVtYWlsXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cbjwvZGl2PlxuPGRpdiBjbGFzcz1cImZsZXhcIj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxpbnB1dCBpZD1cIm5hbWVfZmlyc3RcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJuYW1lX2ZpcnN0XCIgcGxhY2Vob2xkZXI9XCIgXCIgcmVxdWlyZWQ+XG5cdFx0PGxhYmVsIGZvcj1cIm5hbWVfZmlyc3RcIiBkYXRhLWkxOG49XCJmaXJzdC1uYW1lXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0XHQ8L2Rpdj5cblx0PGRpdiBjbGFzcz1cImZsZXggdy01MFwiPlxuXHRcdDxpbnB1dCBpZD1cIm5hbWVfbGFzdFwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHR5cGU9XCJ0ZXh0XCIgbmFtZT1cIm5hbWVfbGFzdFwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJuYW1lX2xhc3RcIiBkYXRhLWkxOG49XCJsYXN0LW5hbWVcIiBjbGFzcz1cImZvcm0tbGFiZWxcIj48L2xhYmVsPlxuXHQ8L2Rpdj5cbjwvZGl2PlxuPGgyIGNsYXNzPVwic2hpcHBpbmctYWRkcmVzcy1oZWFkZXJcIj48c3BhbiBjbGFzcz1cImJvbGRcIiBkYXRhLWkxOG49XCJzaGlwcGluZ1wiPjwvc3Bhbj48L2gyPlxuPGgyIGNsYXNzPVwiYmlsbGluZy1hZGRyZXNzLWhlYWRlciBoaWRlXCI+PHNwYW4gY2xhc3M9XCJib2xkXCIgZGF0YS1pMThuPVwiYmlsbGluZ1wiPjwvc3Bhbj48L2gyPlxuPGRpdiBjbGFzcz1cImZsZXhcIj5cblx0PGlucHV0IGlkPVwiYWRkcmVzczFcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJhZGRyZXNzMVwiIGNsYXNzPVwidy0xMDAgdGV4dC1pbnB1dFwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHQ8bGFiZWwgZm9yPVwiYWRkcmVzczFcIiBkYXRhLWkxOG49XCJzdHJlZXRcIiBjbGFzcz1cImZvcm0tbGFiZWwgZm9ybS1sYWJlbFwiPjwvbGFiZWw+XG48L2Rpdj5cbjxkaXYgY2xhc3M9XCJmbGV4XCI+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJwb3N0YWxcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJwb3N0YWxcIiBwbGFjZWhvbGRlcj1cIiBcIiByZXF1aXJlZD5cblx0XHQ8bGFiZWwgZm9yPVwicG9zdGFsXCIgZGF0YS1pMThuPVwicG9zdGFsXCIgY2xhc3M9XCJmb3JtLWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8aW5wdXQgaWQ9XCJjaXR5XCIgY2xhc3M9XCJ3LTEwMCB0ZXh0LWlucHV0XCIgdHlwZT1cInRleHRcIiBuYW1lPVwiY2l0eVwiIHBsYWNlaG9sZGVyPVwiIFwiIHJlcXVpcmVkPlxuXHRcdDxsYWJlbCBmb3I9XCJjaXR5XCIgZGF0YS1pMThuPVwiY2l0eVwiIGNsYXNzPVwiZm9ybS1sYWJlbFwiPjwvbGFiZWw+XG5cdDwvZGl2PlxuPC9kaXY+XG48ZGl2IGNsYXNzPVwiZmxleFwiPlxuXHQ8ZGl2IGNsYXNzPVwiZmxleCB3LTUwXCI+XG5cdFx0PGlucHV0IGlkPVwicHJvdmluY2VcIiBjbGFzcz1cInctMTAwIHRleHQtaW5wdXRcIiB0eXBlPVwidGV4dFwiIG5hbWU9XCJvZmZcIiBwbGFjZWhvbGRlcj1cIiBcIj5cblx0XHQ8bGFiZWwgZm9yPVwicHJvdmluY2VcIiBjbGFzcz1cImZvcm0tbGFiZWxcIiBkYXRhLWkxOG49XCJwcm92aW5jZVwiPjwvbGFiZWw+XG5cdFx0PHNlbGVjdCBpZD1cImR5bmFtaWMtc3RhdGVzXCIgY2xhc3M9XCJ3LTEwMCBzZWxlY3QgaGlkZVwiIG5hbWU9XCJzdGF0ZVwiIHNpemU9XCIxXCI+XG5cdFx0XHQ8b3B0aW9uIGhpZGRlbiBkaXNhYmxlZCBzZWxlY3RlZCB2YWx1ZT48L29wdGlvbj5cblx0XHQ8L3NlbGVjdD5cblx0XHQ8bGFiZWwgZm9yPVwiZHluYW1pYy1zdGF0ZXNcIiBjbGFzcz1cImZvcm0tbGFiZWwgcmVnaW9uLWNvdW50cnktbGFiZWwgaGlkZVwiIGRhdGEtaTE4bj1cInN0YXRlXCI+PC9sYWJlbD5cblx0PC9kaXY+XG5cdDxkaXYgY2xhc3M9XCJmbGV4IHctNTBcIj5cblx0XHQ8c2VsZWN0IGlkPVwiY291bnRyeVwiIGNsYXNzPVwidy0xMDBcIiBuYW1lPVwiY291bnRyeVwiIHNpemU9XCIxXCIgcmVxdWlyZWQ+XG5cdFx0XHQ8b3B0aW9uIGhpZGRlbiBkaXNhYmxlZCBzZWxlY3RlZCB2YWx1ZSBkYXRhLWkxOG49XCJjb3VudHJ5XCI+PC9vcHRpb24+XG5cdFx0PC9zZWxlY3Q+XG5cdFx0PGxhYmVsIGZvcj1cImNvdW50cnlcIiBkYXRhLWkxOG49XCJjb3VudHJ5LWxhYmVsXCIgY2xhc3M9XCJmb3JtLWxhYmVsIHJlZ2lvbi1jb3VudHJ5LWxhYmVsXCI+PC9sYWJlbD5cblx0PC9kaXY+XG48L2Rpdj5cbjxkaXYgaWQ9XCJjaGVja291dC1kZWxpdmVyeS1kYXRlXCIgY2xhc3M9XCJoaWRlXCI+XG5cdDxoMiBkYXRhLWkxOG49XCJkZWxpdmVyeS1kYXRlXCI+PC9oMj5cblx0PGlucHV0IHR5cGU9XCJkYXRlXCIgaWQ9XCJkZWxpdmVyeS1kYXRlXCIgbmFtZT1cImRlbGl2ZXJ5LWRhdGVcIiB2YWx1ZT1cIlwiIG1pbj1cIlwiPlxuPC9kaXY+XG48L2Zvcm0+YDtcbmZ1bmN0aW9uIGluaXRMaW5rZWRQcm9kdWN0cygpIHtcbiAgICAkcXMoJy5wcmV2LWJ0bicpPy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHNjcm9sbExlZnQpO1xuICAgICRxcygnLm5leHQtYnRuJyk/LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgc2Nyb2xsUmlnaHQpO1xuICAgICRxcygnI3Byb2R1Y3RzLWxpc3QnKT8uYWRkRXZlbnRMaXN0ZW5lcignc2Nyb2xsJywgc2Nyb2xsQWRqdXN0ZXIpO1xuICAgIGxldCBwcmV2aW91c0NhcnREYXRhID0gJyc7XG4gICAgc3RvcmUuc3Vic2NyaWJlKCgpPT57XG4gICAgICAgIGlmIChFbnZpcm9ubWVudC5wbHVnaW4ucGFnZVR5cGUoKSA9PT0gJ3Byb2R1Y3QnIHx8IEVudmlyb25tZW50LnBsdWdpbi5wYWdlVHlwZSgpID09PSAnY2FydCcpIHtcbiAgICAgICAgICAgIGNvbnN0IGNhcnREYXRhID0gSlNPTi5zdHJpbmdpZnkoRGVmYXVsdENhcnQuY29udGVudHMoKSk7XG4gICAgICAgICAgICBpZiAoY2FydERhdGEgIT09IHByZXZpb3VzQ2FydERhdGEpIHtcbiAgICAgICAgICAgICAgICBwcmV2aW91c0NhcnREYXRhID0gY2FydERhdGE7XG4gICAgICAgICAgICAgICAgcmVuZGVyTGlua2VkUHJvZHVjdHMoRGVmYXVsdENhcnQuY29udGVudHMoKSk7XG4gICAgICAgICAgICAgICAgc2V0QWRkQnV0dG9uQ29sb3IoRW52aXJvbm1lbnQucGx1Z2luLmJ1dHRvbkNvbG9yKCkpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfSk7XG59XG5mdW5jdGlvbiBjbGVhckxpbmtlZFByb2R1Y3RzKCkge1xuICAgIGZvciAoY29uc3QgbGlua2VkSXRlbSBvZiAkcXNBbGwoJy5wcm9kdWN0LWJvZHknKSl7XG4gICAgICAgIGxpbmtlZEl0ZW0ucmVtb3ZlKCk7XG4gICAgfVxufVxuZnVuY3Rpb24gc2V0QWRkQnV0dG9uQ29sb3IoY29sb3IgPSAnI0ZGODc2QycpIHtcbiAgICBmb3IgKGNvbnN0IGFkZEJ0biBvZiAkcXNBbGwoJy5hZGQtYnRuJykpe1xuICAgICAgICBhZGRCdG4uc3R5bGUuYmFja2dyb3VuZENvbG9yID0gY29sb3I7XG4gICAgICAgIGFkZEJ0bi5zdHlsZS5ib3JkZXIgPSAnMXB4IHNvbGlkJyArIGNvbG9yO1xuICAgIH1cbn1cbmZ1bmN0aW9uIHJlbmRlckxpbmtlZFByb2R1Y3RzKGNhcnQpIHtcbiAgICBjbGVhckxpbmtlZFByb2R1Y3RzKCk7XG4gICAgZm9yKGxldCBpID0gY2FydC5sZW5ndGggLSAxOyBpID49IDA7IGktLSl7XG4gICAgICAgIGNvbnN0IGl0ZW0gPSBjYXJ0W2ldO1xuICAgICAgICBsZXQgbGlua2VkUHJvZHVjdHM7XG4gICAgICAgIGlmIChFbnZpcm9ubWVudC5wbHVnaW4ucGFnZVR5cGUoKSA9PT0gJ3Byb2R1Y3QnICYmICFpdGVtLmlzX3BhcnRfb2ZfYnVuZGxlICYmIGl0ZW0udXBzZWxsX2l0ZW1zKSB7XG4gICAgICAgICAgICBsaW5rZWRQcm9kdWN0cyA9IGl0ZW0udXBzZWxsX2l0ZW1zO1xuICAgICAgICB9IGVsc2UgaWYgKEVudmlyb25tZW50LnBsdWdpbi5wYWdlVHlwZSgpID09PSAnY2FydCcgJiYgIWl0ZW0uaXNfcGFydF9vZl9idW5kbGUgJiYgaXRlbS5jcm9zc19zZWxsX2l0ZW1zKSB7XG4gICAgICAgICAgICBsaW5rZWRQcm9kdWN0cyA9IGl0ZW0uY3Jvc3Nfc2VsbF9pdGVtcztcbiAgICAgICAgfVxuICAgICAgICBpZiAobGlua2VkUHJvZHVjdHMpIHtcbiAgICAgICAgICAgICRxcygnI2xpbmtlZC1wcm9kdWN0cy1zZWN0aW9uJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgICAgIGZvciAoY29uc3QgbGlua2VkSXRlbSBvZiBsaW5rZWRQcm9kdWN0cyl7XG4gICAgICAgICAgICAgICAgaWYgKGxpbmtlZEl0ZW0uaGFzX3N0b2NrID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgbGlua2VkSXRlbS5oYXNfc3RvY2sgPSB0cnVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoIWxpbmtlZEl0ZW0udmFyaWFibGUgJiYgIWxpbmtlZEl0ZW0uYnVuZGxlICYmIGxpbmtlZEl0ZW0uaGFzX3N0b2NrICYmICFoYXNTYW1lTGlua2VkUHJvZHVjdChsaW5rZWRJdGVtLmlkKSkge1xuICAgICAgICAgICAgICAgICAgICBjb25zdCBwcm9kdWN0c0xpc3QgPSAkcXMoJyNwcm9kdWN0cy1saXN0Jyk7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IHByb2R1Y3RCb2R5ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XG4gICAgICAgICAgICAgICAgICAgIHByb2R1Y3RCb2R5LmNsYXNzTmFtZSA9ICdwcm9kdWN0LWJvZHknO1xuICAgICAgICAgICAgICAgICAgICBwcm9kdWN0Qm9keS5pZCA9IFN0cmluZyhsaW5rZWRJdGVtLmlkKTtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGxpbmtlZEl0ZW0uaW1nX3NyYykge1xuICAgICAgICAgICAgICAgICAgICAgICAgcHJvZHVjdEJvZHkuaW5uZXJIVE1MID0gYDxpbWcgY2xhc3M9XCJsaW5rZWQtcHJvZHVjdC1pbWdcIiBzcmM9JHtsaW5rZWRJdGVtLmltZ19zcmN9PmA7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgcHJvZHVjdEJvZHkuaW5uZXJIVE1MICs9IGA8ZGl2IGNsYXNzPVwibGlua2VkLXByb2R1Y3QtZGVzY1wiPlxuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdFx0IDxzcGFuIGNsYXNzPVwibGlua2VkLXByb2R1Y3QtbmFtZVwiPiR7bGlua2VkSXRlbS5uYW1lfTwvc3Bhbj5cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHRcdCA8c3BhbiBjbGFzcz1cImxpbmtlZC1wcm9kdWN0LXF1YW50aXR5XCI+UXVhbnRpdHk6IDE8L3NwYW4+XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0XHQgPHNwYW4gY2xhc3M9XCJsaW5rZWQtcHJvZHVjdC1wcmljZVwiPiR7Zm9ybWF0Q3VycmVuY3lTdHJpbmcoTnVtYmVyLnBhcnNlRmxvYXQobGlua2VkSXRlbS5wcmljZSkpfTwvc3Bhbj5cblx0XHRcdFx0XHRcdFx0XHRcdFx0XHQgPC9kaXY+XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0IDxidXR0b24gY2xhc3M9XCJhZGQtYnRuXCIgZGF0YS1waWQ9JHtsaW5rZWRJdGVtLmlkfSBkYXRhLWkxOG49XCJhZGRcIj48L2J1dHRvbj5gO1xuICAgICAgICAgICAgICAgICAgICBwcm9kdWN0c0xpc3Q/LnByZXBlbmQocHJvZHVjdEJvZHkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbiAgICBzZXRBZGRCdXR0b25Db2xvcigpO1xuICAgIHJlbW92ZUxpbmtlZFByb2R1Y3QoY2FydCk7XG4gICAgZm9yIChjb25zdCBhZGRCdG4gb2YgJHFzQWxsKCcuYWRkLWJ0bicpKXtcbiAgICAgICAgYWRkQnRuLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGV2ZW50KT0+e1xuICAgICAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgICAgICBldmVudC50YXJnZXQuZGlzYWJsZWQgPSB0cnVlO1xuICAgICAgICAgICAgZXZlbnQudGFyZ2V0LmlubmVySFRNTCA9ICc8aW1nIHNyYz1cImltZy9zcGlubmVyLnN2Z1wiIGNsYXNzPVwibGlua2VkLXByb2R1Y3Qtc3Bpbm5lclwiLz4nO1xuICAgICAgICAgICAgYWRkTGlua2VkUHJvZHVjdHRvQ2FydChldmVudC50YXJnZXQpO1xuICAgICAgICB9KTtcbiAgICB9XG59XG5mdW5jdGlvbiBhZGRMaW5rZWRQcm9kdWN0dG9DYXJ0KGxpbmtlZFByb2R1Y3QpIHtcbiAgICBHTE9CQUwubGlua2VkUHJvZHVjdHNJZHM/LnB1c2goTnVtYmVyLnBhcnNlSW50KGxpbmtlZFByb2R1Y3QuZGF0YXNldC5waWQpKTtcbiAgICB3aW5kb3cucGFyZW50LnBvc3RNZXNzYWdlKHtcbiAgICAgICAgZXZlbnQ6ICdhZGRMaW5rZWRQcm9kdWN0JyxcbiAgICAgICAgcHJvZHVjdElEOiBsaW5rZWRQcm9kdWN0LmRhdGFzZXQucGlkXG4gICAgfSwgJyonKTtcbn1cbmZ1bmN0aW9uIHJlbW92ZUxpbmtlZFByb2R1Y3QoY2FydCkge1xuICAgIGZvciAoY29uc3QgbGlua2VkUHJvZHVjdCBvZiAkcXNBbGwoJy5wcm9kdWN0LWJvZHknKSl7XG4gICAgICAgIGZvcihsZXQgaSA9IGNhcnQubGVuZ3RoIC0gMTsgaSA+PSAwOyBpLS0pe1xuICAgICAgICAgICAgY29uc3QgaXRlbSA9IGNhcnRbaV07XG4gICAgICAgICAgICBpZiAoaXRlbS5wcm9kdWN0X2lkID09PSBOdW1iZXIucGFyc2VJbnQobGlua2VkUHJvZHVjdC5pZCkpIHtcbiAgICAgICAgICAgICAgICBsaW5rZWRQcm9kdWN0LnJlbW92ZSgpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGlmICgkcXNBbGwoJy5wcm9kdWN0LWJvZHknKS5sZW5ndGggPiAxKSB7XG4gICAgICAgICRxcygnLnByZXYtYnRuJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcubmV4dC1idG4nKT8uY2xhc3NMaXN0LnJlbW92ZSgnaGlkZScpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnLnByZXYtYnRuJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICAgICAgJHFzKCcubmV4dC1idG4nKT8uY2xhc3NMaXN0LmFkZCgnaGlkZScpO1xuICAgIH1cbiAgICBpZiAoISRxcygnLnByb2R1Y3QtYm9keScpKSB7XG4gICAgICAgICRxcygnI2xpbmtlZC1wcm9kdWN0cy1zZWN0aW9uJyk/LmNsYXNzTGlzdC5hZGQoJ2hpZGUnKTtcbiAgICB9XG4gICAgc2Nyb2xsQWRqdXN0ZXIoKTtcbn1cbmZ1bmN0aW9uIGhhc1NhbWVMaW5rZWRQcm9kdWN0KHByb2R1Y3RJRCkge1xuICAgIGZvciAoY29uc3QgcHJvZHVjdCBvZiAkcXNBbGwoJy5wcm9kdWN0LWJvZHknKSl7XG4gICAgICAgIGlmIChOdW1iZXIucGFyc2VJbnQocHJvZHVjdC5pZCkgPT09IHByb2R1Y3RJRCkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmV0dXJuIGZhbHNlO1xufVxuZnVuY3Rpb24gc2Nyb2xsUmlnaHQoKSB7XG4gICAgJHFzKCcucHJldi1idG4nKT8uY2xhc3NMaXN0LnJlbW92ZSgnc2Nyb2xsLWVuZCcpO1xuICAgICRxcygnI3Byb2R1Y3RzLWxpc3QnLCAoJGVsZW1lbnQpPT4kZWxlbWVudC5zY3JvbGxMZWZ0ICs9IDM5MlxuICAgICk7XG59XG5mdW5jdGlvbiBzY3JvbGxMZWZ0KCkge1xuICAgICRxcygnLm5leHQtYnRuJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ3Njcm9sbC1lbmQnKTtcbiAgICAkcXMoJyNwcm9kdWN0cy1saXN0JywgKCRlbGVtZW50KT0+JGVsZW1lbnQuc2Nyb2xsTGVmdCAtPSAzOTJcbiAgICApO1xufVxuZnVuY3Rpb24gc2Nyb2xsQWRqdXN0ZXIoKSB7XG4gICAgY29uc3Qgc2Nyb2xsRW5kID0gJHFzKCcjcHJvZHVjdHMtbGlzdCcpPy5zY3JvbGxMZWZ0ID8gJHFzKCcjcHJvZHVjdHMtbGlzdCcpPy5zY3JvbGxMZWZ0IDogMTtcbiAgICBjb25zdCBvZmZzZXQgPSAkcXMoJyNwcm9kdWN0cy1saXN0Jyk/Lm9mZnNldFdpZHRoO1xuICAgIGNvbnN0IHNjcm9sbFdpZHRoID0gJHFzKCcjcHJvZHVjdHMtbGlzdCcpPy5zY3JvbGxXaWR0aDtcbiAgICBpZiAoJHFzKCcjcHJvZHVjdHMtbGlzdCcpPy5zY3JvbGxMZWZ0ID09PSAwKSB7XG4gICAgICAgICRxcygnLnByZXYtYnRuJyk/LmNsYXNzTGlzdC5hZGQoJ3Njcm9sbC1lbmQnKTtcbiAgICAgICAgJHFzKCcubmV4dC1idG4nKT8uY2xhc3NMaXN0LnJlbW92ZSgnc2Nyb2xsLWVuZCcpO1xuICAgIH0gZWxzZSBpZiAoc2Nyb2xsRW5kICYmIHNjcm9sbFdpZHRoICYmIG9mZnNldCAmJiBzY3JvbGxFbmQgKyAxID49IHNjcm9sbFdpZHRoIC0gb2Zmc2V0KSB7XG4gICAgICAgICRxcygnLm5leHQtYnRuJyk/LmNsYXNzTGlzdC5hZGQoJ3Njcm9sbC1lbmQnKTtcbiAgICAgICAgJHFzKCcucHJldi1idG4nKT8uY2xhc3NMaXN0LnJlbW92ZSgnc2Nyb2xsLWVuZCcpO1xuICAgIH0gZWxzZSB7XG4gICAgICAgICRxcygnLm5leHQtYnRuJyk/LmNsYXNzTGlzdC5yZW1vdmUoJ3Njcm9sbC1lbmQnKTtcbiAgICAgICAgJHFzKCcucHJldi1idG4nKT8uY2xhc3NMaXN0LnJlbW92ZSgnc2Nyb2xsLWVuZCcpO1xuICAgIH1cbn1cbmFzeW5jIGZ1bmN0aW9uIHNlbmRDYXJ0SXRlbXMoX2NhcnQsIHNlc3Npb25JRCkge1xuICAgIGlmICghR0xPQkFMLnBocERhdGEpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICBpZiAoIUdMT0JBTC5waHBEYXRhLmhhc192YWxpZF9rZXkpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICB0cnkge1xuICAgICAgICBjb25zdCByZXF1ZXN0Qm9keSA9IHtcbiAgICAgICAgICAgICdzZXNzaW9uX2lkJzogc2Vzc2lvbklELFxuICAgICAgICAgICAgJ2l0ZW1zJzogW10sXG4gICAgICAgICAgICAnbWVyY2hhbnRfbmFtZSc6IE1lcmNoYW50Q29uZmlndXJhdGlvbi5uYW1lKCksXG4gICAgICAgICAgICAnbWVyY2hhbnRfaG9zdG5hbWUnOiBNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKVxuICAgICAgICB9O1xuICAgICAgICBjb25zdCBvcHRpb25zID0ge1xuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICBoZWFkZXJzOiB7XG4gICAgICAgICAgICAgICAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi9qc29uJ1xuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGJvZHk6IEpTT04uc3RyaW5naWZ5KHJlcXVlc3RCb2R5KVxuICAgICAgICB9O1xuICAgICAgICBhd2FpdCBmZXRjaChnZXRCYXNlVVJMKE1lcmNoYW50Q29uZmlndXJhdGlvbi5ob3N0TmFtZSgpLCBFbnZpcm9ubWVudC50ZXN0TW9kZSgpKSArICdzZXNzaW9uL2l0ZW0nLCBvcHRpb25zKTtcbiAgICB9IGNhdGNoIChlcnJvcikge1xuICAgICAgICBpZiAoZXJyb3IgaW5zdGFuY2VvZiBFcnJvcikge1xuICAgICAgICAgICAgY2FwdHVyZVNlbnRyeUV4Y2VwdGlvbihuZXcgRXJyb3IoYFNlbmQgY2FydCBpdGVtcyBmYWlsZWQgb24gJHtNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKX0sIEVycm9yOiAke2Vycm9yLm1lc3NhZ2V9YCkpO1xuICAgICAgICB9XG4gICAgfVxufVxuZnVuY3Rpb24gaW5pdE9uZUNsaWNrQ2hlY2tvdXQodGVzdE1vZGUpIHtcbiAgICBjb25zdCBvbmVDbGlja1VSTCA9IGdldE9uZUNsaWNrVVJMKGxvY2F0aW9uLmhvc3RuYW1lLCB0ZXN0TW9kZSk7XG4gICAgY29uc3QgJGJvZHkgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdib2R5Jyk7XG4gICAgJGJvZHk/Lmluc2VydEFkamFjZW50SFRNTCgnYmVmb3JlZW5kJywgYFxuXHQ8aWZyYW1lIGlkPVwib25lLWNsaWNrLWlmcmFtZVwiIFxuXHRcdGZyYW1lYm9yZGVyPVwiMFwiIFxuXHRcdGFsbG93dHJhbnNwYXJlbmN5PVwidHJ1ZVwiIFxuXHRcdHNjcm9sbGluZz1cIm5vXCIgXG5cdFx0YWxsb3c9XCJwYXltZW50ICpcIlxuXHRcdGFyaWEtaGlkZGVuPVwidHJ1ZVwiIFxuXHRcdHRhYmluZGV4PVwiLTFcIiBcblx0XHRzdHlsZT1cImJvcmRlcjogbm9uZSAhaW1wb3J0YW50OyBtYXJnaW46IDBweCAhaW1wb3J0YW50OyBwYWRkaW5nOiAwcHggIWltcG9ydGFudDsgd2lkdGg6IDFweCAhaW1wb3J0YW50OyBtaW4td2lkdGg6IDEwMCUgIWltcG9ydGFudDsgb3ZlcmZsb3c6IGhpZGRlbiAhaW1wb3J0YW50OyBkaXNwbGF5OiBibG9jayAhaW1wb3J0YW50OyB2aXNpYmlsaXR5OiBoaWRkZW4gIWltcG9ydGFudDsgcG9zaXRpb246IGZpeGVkICFpbXBvcnRhbnQ7IGhlaWdodDogMXB4ICFpbXBvcnRhbnQ7IHBvaW50ZXItZXZlbnRzOiBub25lICFpbXBvcnRhbnQ7IHVzZXItc2VsZWN0OiBub25lICFpbXBvcnRhbnQ7XCJcblx0XHRzcmM9XCIke29uZUNsaWNrVVJMfW9uZS1jbGljay5odG1sXCJcblx0PlxuXHRcdFVuYWJsZSB0byBsb2FkIFBlYWNoUGF5IE9uZSBDbGljayBDaGVja291dCBTdXBwb3J0XG5cdDwvaWZyYW1lPmApO1xufVxuKGZ1bmN0aW9uKCkge1xuICAgIG9uV2luZG93TWVzc2FnZSgnaW5pdCcsIGFzeW5jIChtZXNzYWdlKT0+e1xuICAgICAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVNZXJjaGFudEhvc3ROYW1lKG1lc3NhZ2UubWVyY2hhbnRIb3N0bmFtZSkpO1xuICAgICAgICBhZGRGb3JtRmllbGRzKG1lc3NhZ2UucGhwRGF0YS5sYW5ndWFnZSk7XG4gICAgICAgIEdMT0JBTC5waHBEYXRhID0gbWVzc2FnZS5waHBEYXRhO1xuICAgICAgICBpZiAodHlwZW9mIEdMT0JBTC5waHBEYXRhLmhhc192YWxpZF9rZXkgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgICAgICBHTE9CQUwucGhwRGF0YS5oYXNfdmFsaWRfa2V5ID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgICBzdG9yZS5kaXNwYXRjaCh1cGRhdGVNZXJjaGFudE5hbWUobWVzc2FnZS5waHBEYXRhLm1lcmNoYW50X25hbWUpKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2goc2V0RmVhdHVyZVN1cHBvcnQobWVzc2FnZS5waHBEYXRhLmZlYXR1cmVfc3VwcG9ydCwgbWVzc2FnZS5waHBEYXRhKSk7XG4gICAgICAgIHN0b3JlLmRpc3BhdGNoKHVwZGF0ZUxhbmd1YWdlKG1lc3NhZ2UucGhwRGF0YS5sYW5ndWFnZSA9PT0gJ2RldGVjdC1mcm9tLXBhZ2UnID8gbWVzc2FnZS5wYWdlTGFuZ3VhZ2UgOiBtZXNzYWdlLnBocERhdGEubGFuZ3VhZ2UpKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlRW52aXJvbm1lbnQoe1xuICAgICAgICAgICAgcGx1Z2luSXNUZXN0TW9kZTogQm9vbGVhbihtZXNzYWdlLmlzVGVzdE1vZGUpLFxuICAgICAgICAgICAgcGx1Z2luUGFnZVR5cGU6IGRldGVybWluZVBhZ2VUeXBlKG1lc3NhZ2UuaXNDYXJ0UGFnZSwgbWVzc2FnZS5pc0NoZWNrb3V0UGFnZSksXG4gICAgICAgICAgICBjdXN0b21lcklzTW9iaWxlOiBtZXNzYWdlLmlzTW9iaWxlLFxuICAgICAgICAgICAgcGx1Z2luQnV0dG9uQ29sb3I6IG1lc3NhZ2UucGhwRGF0YS5idXR0b25fY29sb3IsXG4gICAgICAgICAgICBwbHVnaW5WZXJzaW9uOiBtZXNzYWdlLnBocERhdGEudmVyc2lvblxuICAgICAgICB9KSk7XG4gICAgICAgIGluaXRNb2RhbCgpO1xuICAgICAgICBpbml0RGVsaXZlcnlEYXRlKCk7XG4gICAgICAgIGluaXRNZXRyaWNzKCk7XG4gICAgICAgIGluaXRMaW5rZWRQcm9kdWN0cygpO1xuICAgICAgICBpbml0T3JkZXJOb3RlcygpO1xuICAgICAgICBpbml0Q2FydCgpO1xuICAgICAgICBpbml0TGFuZ3VhZ2UobWVzc2FnZSk7XG4gICAgICAgIGluaXRTdW1tYXJ5KG1lc3NhZ2UpO1xuICAgICAgICBpbml0Q291cG9uSW5wdXQobWVzc2FnZSk7XG4gICAgICAgIGluaXRHaWZ0Q2FyZElucHV0KG1lc3NhZ2UpO1xuICAgICAgICBpbml0U2hpcHBpbmcobWVzc2FnZSk7XG4gICAgICAgIGluaXRDdXN0b21lcihtZXNzYWdlKTtcbiAgICAgICAgaW5pdEN1cnJlbmN5KG1lc3NhZ2UpO1xuICAgICAgICBpbml0TWVyY2hhbnRBY2NvdW50KG1lc3NhZ2UpO1xuICAgICAgICBpbml0VkFUKG1lc3NhZ2UpO1xuICAgICAgICBpZiAoRmVhdHVyZS5lbmFibGVkKEZlYXR1cmVGbGFnLkFERElUSU9OQUxfRklFTERTKSkge1xuICAgICAgICAgICAgcmVuZGVyQWRkaXRpb25hbEZpZWxkcyhtZXNzYWdlLnBocERhdGE/LmFkZGl0aW9uYWxfZmllbGRzID8/IFtdLCBtZXNzYWdlLnBocERhdGE/LmFkZGl0aW9uYWxfZmllbGRzX29yZGVyID8/IFtdKTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBvcmRlclNlcnZpY2UgPSBnZXRPcmRlclNlcnZpY2UoKTtcbiAgICAgICAgYXdhaXQgaW5pdFN0cmlwZVN1cHBvcnQobWVzc2FnZSwgb3JkZXJTZXJ2aWNlKTtcbiAgICAgICAgYXdhaXQgaW5pdFBheVBhbFN1cHBvcnQobWVzc2FnZSwgb3JkZXJTZXJ2aWNlKTtcbiAgICAgICAgY29uc3Qgc2Vzc2lvblJlc3BvbnNlID0gYXdhaXQgZmV0Y2goZ2V0QmFzZVVSTChNZXJjaGFudENvbmZpZ3VyYXRpb24uaG9zdE5hbWUoKSwgRW52aXJvbm1lbnQudGVzdE1vZGUoKSkgKyAnc2Vzc2lvbicpO1xuICAgICAgICBjb25zdCBzZXNzaW9uID0gYXdhaXQgc2Vzc2lvblJlc3BvbnNlLmpzb24oKTtcbiAgICAgICAgc3RvcmUuZGlzcGF0Y2godXBkYXRlU2Vzc2lvbklkKHNlc3Npb24uaWQpKTtcbiAgICAgICAgYXdhaXQgc2VuZENhcnRJdGVtcyhEZWZhdWx0Q2FydC5jb250ZW50cygpLCBzZXNzaW9uLmlkKTtcbiAgICAgICAgaW5pdE9uZUNsaWNrQ2hlY2tvdXQobWVzc2FnZS5pc1Rlc3RNb2RlKTtcbiAgICAgICAgb25XaW5kb3dNZXNzYWdlKCdwcC1vbmUtY2xpY2stbG9hZGVkJywgYXN5bmMgKCk9PntcbiAgICAgICAgICAgIGF3YWl0IGxvYWRDdXN0b21lcigpO1xuICAgICAgICAgICAgc3RvcmUuZGlzcGF0Y2goc3RhcnRNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgICAgICBhd2FpdCByZXF1ZXN0Q2FydENhbGN1bGF0aW9uKCFFbnZpcm9ubWVudC5jdXN0b21lci5leGlzdGluZygpKTtcbiAgICAgICAgICAgIHN0b3JlLmRpc3BhdGNoKHN0b3BNb2RhbExvYWRpbmcoKSk7XG4gICAgICAgICAgICBzZWxmLnBhcmVudD8ucG9zdE1lc3NhZ2UoJ2xvYWRlZCcsICcqJyk7XG4gICAgICAgIH0pO1xuICAgIH0pO1xufSkoKTtcbmZ1bmN0aW9uIGFkZEZvcm1GaWVsZHMobGFuZykge1xuICAgIGxldCBmb3JtID0gZGVmYXVsdEZvcm1IVE1MO1xuICAgIGlmIChsYW5nID09PSAnamEnKSB7XG4gICAgICAgIGZvcm0gPSBqYXBhbmVzZUZvcm1IVE1MO1xuICAgIH1cbiAgICBpZiAoTWVyY2hhbnRDb25maWd1cmF0aW9uLmhvc3ROYW1lKCkgPT09ICdpbml0aWFsYXVkaW8uY29tJykge1xuICAgICAgICBmb3JtID0gY2hlY2tvdXRGb3JtTm9QaG9uZU5vQXB0O1xuICAgIH1cbiAgICAkcXMoJyNwcC1pbmZvJyk/Lmluc2VydEFkamFjZW50SFRNTCgnYmVmb3JlZW5kJywgZm9ybSk7XG59XG4iXX0=