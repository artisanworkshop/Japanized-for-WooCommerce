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
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
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
function eventClick(event) {
    if (event.type === 'click') {
        return true;
    }
    else if (event.type === 'keypress') {
        var key = event.key;
        if (key === 'Enter' || key === ' ') {
            return true;
        }
    }
    return false;
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
    DispatchActionType1["PEACHPAY_CUSTOMER_FIELDS"] = "PEACHPAY_CUSTOMER_FIELDS";
    DispatchActionType1["PAYMENT_INITILIZE_UI"] = "PAYMENT_INITILIZE_UI";
    DispatchActionType1["PAYMENT_SWAP_PRIMARY_WITH_SECONDARY"] = "PAYMENT_SWAP_PRIMARY_WITH_SECONDARY";
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
        version: 2,
        form_fields: {
            shipping_company: '',
            shipping_email: '',
            shipping_first_name: '',
            shipping_last_name: '',
            shipping_address_1: '',
            shipping_address_2: '',
            shipping_city: '',
            shipping_state: '',
            shipping_country: '',
            shipping_postcode: '',
            shipping_phone: '',
            billing_company: '',
            billing_email: '',
            billing_first_name: '',
            billing_last_name: '',
            billing_address_1: '',
            billing_address_2: '',
            billing_city: '',
            billing_state: '',
            billing_country: '',
            billing_postcode: '',
            billing_phone: ''
        }
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
                name: 'United States Dollar',
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
        providers: {},
        ui: {
            primaryMethods: [
                undefined,
                undefined,
                undefined
            ]
        }
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
var GLOBAL = {
    completedOrder: null,
    phpData: null,
    linkedProductsIds: []
};
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
var peachpayi18n = {
    "ar": {
        "(optional)": "(اختياري)",
        "+ ADD A COUPON CODE": "+ أضف رمز القسيمة",
        "+ NEW CARD": "+ بطاقة جديدة",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ استرداد بطاقة الهدايا / رصيد المتجر",
        "After selecting pay you will be redirected to complete your payment.": "بعد اختيار الدفع ، ستتم إعادة توجيهك لإكمال الدفع.",
        "Apartment": "شقة",
        "Back to info": "رجوع إلى المعلومات",
        "Billing": "الفواتير",
        "By clicking the button above, you agree to": "بالنقر فوق الزر أعلاه ، فإنك توافق على",
        "Cancel": "يلغي",
        "Card": "بطاقة",
        "Cart is empty": "البطاقه خاليه",
        "City": "مدينة",
        "Continue": "يكمل",
        "Country": "دولة",
        "County": "مقاطعة",
        "Coupon code": "رمز الكوبون",
        "Create a new password, or use an existing one if you already have an account for": "أنشئ كلمة مرور جديدة ، أو استخدم كلمة مرور موجودة إذا كان لديك بالفعل حساب لـ",
        "Currency": "عملة",
        "Delivery date": "تاريخ التسليم او الوصول",
        "Edit": "تعديل",
        "Email": "بريد الالكتروني",
        "Exit Checkout": "الخروج من الخروج",
        "First name": "الاسم الأول",
        "First renewal": "التجديد الأول",
        "Gift card number": "رقم بطاقة الهدية",
        "I verify that the country I have entered is the one I reside in": "أتحقق من أن البلد الذي أدخلته هو البلد الذي أقيم فيه",
        "Initial Shipment": "الشحنة الأولية",
        "Last name": "الكنية",
        "My order": "طلبي",
        "Order notes (optional)": "ملاحظات الطلب (اختياري)",
        "Order summary": "ملخص الطلب",
        "Password": "كلمه السر",
        "Pay": "دفع",
        "Payment": "قسط",
        "Personal": "الشخصية",
        "Phone number": "رقم الهاتف",
        "Place order": "مكان الامر",
        "Please go back and try again. Missing required field": "الرجاء العودة والمحاولة مجددا. حقل مطلوب مفقود",
        "Postal code": "الرمز البريدي",
        "Processing": "يعالج",
        "Province": "المحافظة",
        "Ready to check out?": "هل أنت جاهز للمغادرة؟",
        "Recurring total": "المجموع المتكرر",
        "Remove": "إزالة",
        "Secured by": "بضمان",
        "Select a Province": "اختر المحافظة",
        "Select a State": "حدد ولاية",
        "Select a country": "اختر دولة",
        "Send to": "ارسل إلى",
        "Shipping": "شحن",
        "Sorry, something went wrong. Please refresh the page and try again.": "عذرا، هناك خطأ ما. يرجى تحديث الصفحة وحاول مرة أخرى.",
        "Sorry, this store does not ship to your location.": "عذرا ، هذا المتجر لا يشحن إلى موقعك.",
        "State": "حالة",
        "Street address": ".عنوان الشارع",
        "Subtotal": "المجموع الفرعي",
        "Tax": "ضريبة",
        "Test mode: customers cannot see PeachPay": "وضع الاختبار: لا يمكن للعملاء رؤية PeachPay",
        "The password entered must be at least 8 characters long.": "يجب أن تتكون كلمة المرور المدخلة من 8 أحرف على الأقل.",
        "There are no eligible or active payment methods available for this order.": "لا توجد طرق دفع مؤهلة أو نشطة متاحة لهذا الطلب.",
        "Total": "المجموع",
        "Unknown order error occurred": "حدث خطأ طلب غير معروف",
        "VIEW SAVED CARDS": "عرض البطاقات المحفوظة",
        "Verified": "تم التحقق",
        "You entered an invalid coupon code": "لقد أدخلت رمز قسيمة غير صالح",
        "You entered an invalid gift card": "لقد أدخلت بطاقة هدية غير صالحة",
        "You might also like...": "قد يعجبك ايضا...",
        "add": "يضيف",
        "and": "و",
        "coupon": "قسيمة",
        "privacy policy": "سياسة خاصة",
        "terms": "مصلحات",
        "terms and conditions": "الأحكام والشروط",
        "the": "ال",
        "the store's": "المتجر"
    },
    "bg-BG": {
        "(optional)": "(по избор)",
        "+ ADD A COUPON CODE": "+ ДОБАВЯНЕ НА КОД НА КУПОН",
        "+ NEW CARD": "+ НОВА КАРТА",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ ИЗКУПЕТЕ КАРТА ЗА ПОДАРЪК/КРЕДИТ ЗА МАГАЗИН",
        "After selecting pay you will be redirected to complete your payment.": "След като изберете плащане, ще бъдете пренасочени да завършите плащането си.",
        "Apartment": "Апартамент",
        "Back to info": "Обратно към информацията",
        "Billing": "Таксуване",
        "By clicking the button above, you agree to": "Като щракнете върху бутона по-горе, вие се съгласявате",
        "Cancel": "Отмяна",
        "Card": "карта",
        "Cart is empty": "Количката е празна",
        "City": "град",
        "Continue": "продължи",
        "Country": "Държава",
        "County": "окръг",
        "Coupon code": "Код на купон",
        "Create a new password, or use an existing one if you already have an account for": "Създайте нова парола или използвайте съществуваща, ако вече имате акаунт за",
        "Currency": "Валута",
        "Delivery date": "Дата на доставка",
        "Edit": "редактиране",
        "Email": "електронна поща",
        "Exit Checkout": "Излезте от касата",
        "First name": "Първо име",
        "First renewal": "Първо подновяване",
        "Gift card number": "Номер на карта за подарък",
        "I verify that the country I have entered is the one I reside in": "Потвърждавам, че държавата, в която съм влязъл, е тази, в която пребивавам",
        "Initial Shipment": "Първоначално изпращане",
        "Last name": "Фамилия",
        "My order": "Моята поръчка",
        "Order notes (optional)": "Бележки за поръчка (по избор)",
        "Order summary": "Резюме на поръчката",
        "Password": "парола",
        "Pay": "Плати",
        "Payment": "Плащане",
        "Personal": "Лични",
        "Phone number": "Телефонен номер",
        "Place order": "Поръчай",
        "Please go back and try again. Missing required field": "Моля, върнете се и опитайте отново. Липсва задължително поле",
        "Postal code": "Пощенски код",
        "Processing": "Обработка",
        "Province": "провинция",
        "Ready to check out?": "Готови ли сте да проверите?",
        "Recurring total": "Повтарящо се общо",
        "Remove": "Премахване",
        "Secured by": "Осигурен от",
        "Select a Province": "Изберете провинция",
        "Select a State": "Изберете държава",
        "Select a country": "Изберете държава",
        "Send to": "Изпрати на",
        "Shipping": "Доставка",
        "Sorry, something went wrong. Please refresh the page and try again.": "Съжалявам нещо се обърка. Моля, опреснете страницата и опитайте отново.",
        "Sorry, this store does not ship to your location.": "За съжаление този магазин не доставя до вашето местоположение.",
        "State": "състояние",
        "Street address": "Адрес на улица",
        "Subtotal": "Междинна сума",
        "Tax": "данък",
        "Test mode: customers cannot see PeachPay": "Тестов режим: клиентите не могат да виждат PeachPay",
        "The password entered must be at least 8 characters long.": "Въведената парола трябва да е дълга поне 8 знака.",
        "There are no eligible or active payment methods available for this order.": "Няма налични подходящи или активни методи на плащане за тази поръчка.",
        "Total": "Обща сума",
        "Unknown order error occurred": "Възникна грешка при неизвестна поръчка",
        "VIEW SAVED CARDS": "ВИЖТЕ ЗАПАЗЕНИ КАРТИ",
        "Verified": "Потвърдено",
        "You entered an invalid coupon code": "Въведохте невалиден код на талон",
        "You entered an invalid gift card": "Въведохте невалидна карта за подарък",
        "You might also like...": "Може да харесате също и...",
        "add": "добавете",
        "and": "и",
        "coupon": "купон",
        "privacy policy": "политика за поверителност",
        "terms": "термини",
        "terms and conditions": "правила и условия",
        "the": "на",
        "the store's": "на магазина"
    },
    "ca": {
        "(optional)": "(opcional)",
        "+ ADD A COUPON CODE": "+ AFEGUEIX UN CODI DE CUPON",
        "+ NEW CARD": "+ NOVA TARGETA",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ BENEFICIA DE LA TARGETA REGAL/CREDIT DE LA BOTIGA",
        "After selecting pay you will be redirected to complete your payment.": "Després de seleccionar el pagament, se us redirigirà per completar el pagament.",
        "Apartment": "Apartament",
        "Back to info": "Torna a la informació",
        "Billing": "Facturació",
        "By clicking the button above, you agree to": "En fer clic al botó de dalt, acceptes",
        "Cancel": "Cancel · lar",
        "Card": "Targeta",
        "Cart is empty": "El carretó està buit",
        "City": "ciutat",
        "Continue": "Continua",
        "Country": "País",
        "County": "comtat",
        "Coupon code": "Codi de cupó",
        "Create a new password, or use an existing one if you already have an account for": "Creeu una contrasenya nova o utilitzeu-ne una existent si ja teniu un compte",
        "Currency": "Moneda",
        "Delivery date": "Data de lliurament",
        "Edit": "Edita",
        "Email": "Correu electrònic",
        "Exit Checkout": "Sortir de la caixa",
        "First name": "Nom",
        "First renewal": "Primera renovació",
        "Gift card number": "Número de targeta regal",
        "I verify that the country I have entered is the one I reside in": "Verifico que el país on he entrat és el on visc",
        "Initial Shipment": "Enviament inicial",
        "Last name": "Cognom",
        "My order": "El meu ordre",
        "Order notes (optional)": "Notes de comanda (opcional)",
        "Order summary": "Resum de la comanda",
        "Password": "Contrasenya",
        "Pay": "Pagar",
        "Payment": "Pagament",
        "Personal": "Personal",
        "Phone number": "Número de telèfon",
        "Place order": "Fes la comanda",
        "Please go back and try again. Missing required field": "Si us plau, torneu i torneu-ho a provar. Falta el camp obligatori",
        "Postal code": "Codi Postal",
        "Processing": "Tramitació",
        "Province": "Província",
        "Ready to check out?": "Preparat per comprovar-ho?",
        "Recurring total": "Total recurrent",
        "Remove": "Eliminar",
        "Secured by": "Assegurat per",
        "Select a Province": "Seleccioneu una província",
        "Select a State": "Seleccioneu un estat",
        "Select a country": "Seleccioneu un país",
        "Send to": "Enviar a",
        "Shipping": "Enviament",
        "Sorry, something went wrong. Please refresh the page and try again.": "Ho sentim, alguna cosa ha anat malament. Actualitzeu la pàgina i torneu-ho a provar.",
        "Sorry, this store does not ship to your location.": "Ho sentim, aquesta botiga no envia a la teva ubicació.",
        "State": "Estat",
        "Street address": "adreça",
        "Subtotal": "Subtotal",
        "Tax": "Impost",
        "Test mode: customers cannot see PeachPay": "Mode de prova: els clients no poden veure PeachPay",
        "The password entered must be at least 8 characters long.": "La contrasenya introduïda ha de tenir almenys 8 caràcters.",
        "There are no eligible or active payment methods available for this order.": "No hi ha mètodes de pagament elegibles o actius disponibles per a aquesta comanda.",
        "Total": "Total",
        "Unknown order error occurred": "S'ha produït un error de comanda desconegut",
        "VIEW SAVED CARDS": "VISUALITZA TARGETES DESADES",
        "Verified": "Verificat",
        "You entered an invalid coupon code": "Heu introduït un codi de cupó no vàlid",
        "You entered an invalid gift card": "Has introduït una targeta regal no vàlida",
        "You might also like...": "Potser també t'agrada...",
        "add": "afegir",
        "and": "i",
        "coupon": "cupó",
        "privacy policy": "política de privacitat",
        "terms": "termes",
        "terms and conditions": "termes i condicions",
        "the": "el",
        "the store's": "de la botiga"
    },
    "cs-CZ": {
        "(optional)": "(volitelný)",
        "+ ADD A COUPON CODE": "+ PŘIDEJTE KÓD KUPÓNU",
        "+ NEW CARD": "+ NOVÁ KARTA",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ UPLATNĚTE DÁRKOVOU KARTU/KREDIT OBCHOD",
        "After selecting pay you will be redirected to complete your payment.": "Po výběru platby budete přesměrováni k dokončení platby.",
        "Apartment": "Byt",
        "Back to info": "Zpět k informacím",
        "Billing": "Fakturace",
        "By clicking the button above, you agree to": "Kliknutím na tlačítko výše souhlasíte",
        "Cancel": "zrušení",
        "Card": "Kartu",
        "Cart is empty": "Košík je prázdný",
        "City": "Město",
        "Continue": "Pokračovat",
        "Country": "Země",
        "County": "okres",
        "Coupon code": "Kód kupónu",
        "Create a new password, or use an existing one if you already have an account for": "Vytvořte si nové heslo nebo použijte stávající, pokud již máte účet",
        "Currency": "Měna",
        "Delivery date": "Datum doručení",
        "Edit": "Upravit",
        "Email": "E-mailem",
        "Exit Checkout": "Ukončete službu Checkout",
        "First name": "Jméno",
        "First renewal": "První obnovení",
        "Gift card number": "Číslo dárkové karty",
        "I verify that the country I have entered is the one I reside in": "Ověřuji, že země, do které jsem zadal, je zemí, ve které bydlím",
        "Initial Shipment": "Počáteční zásilka",
        "Last name": "Příjmení",
        "My order": "Moje objednávka",
        "Order notes (optional)": "Poznámky k objednávce (volitelné)",
        "Order summary": "Přehled objednávky",
        "Password": "Heslo",
        "Pay": "Platit",
        "Payment": "Způsob platby",
        "Personal": "Osobní",
        "Phone number": "Telefonní číslo",
        "Place order": "Objednejte si",
        "Please go back and try again. Missing required field": "Vraťte se prosím zpět a zkuste to znovu. chybí povinné pole",
        "Postal code": "Poštovní směrovací číslo",
        "Processing": "zpracovává se",
        "Province": "Provincie",
        "Ready to check out?": "Jste připraveni se přihlásit?",
        "Recurring total": "Opakující se celkem",
        "Remove": "Odstranit",
        "Secured by": "Zabezpečeno",
        "Select a Province": "Vyberte provincii",
        "Select a State": "Vyberte stát",
        "Select a country": "Vyber zemi",
        "Send to": "Poslat komu",
        "Shipping": "Lodní doprava",
        "Sorry, something went wrong. Please refresh the page and try again.": "Promiň, něco se pokazilo. Obnovte stránku a zkuste to znovu.",
        "Sorry, this store does not ship to your location.": "Litujeme, tento obchod nedodává do vaší lokality.",
        "State": "Stát",
        "Street address": "adresa ulice",
        "Subtotal": "Mezisoučet",
        "Tax": "Daň",
        "Test mode: customers cannot see PeachPay": "Testovací režim: zákazníci nevidí PeachPay",
        "The password entered must be at least 8 characters long.": "Zadané heslo musí mít alespoň 8 znaků.",
        "There are no eligible or active payment methods available for this order.": "Pro tuto objednávku nejsou k dispozici žádné způsobilé ani aktivní platební metody.",
        "Total": "Celkový",
        "Unknown order error occurred": "Došlo k neznámé chybě objednávky",
        "VIEW SAVED CARDS": "ZOBRAZIT ULOŽENÉ KARTY",
        "Verified": "Ověřeno",
        "You entered an invalid coupon code": "Zadali jste neplatný kód kupónu",
        "You entered an invalid gift card": "Zadali jste neplatnou dárkovou kartu",
        "You might also like...": "Může se vám také libit...",
        "add": "přidat",
        "and": "a",
        "coupon": "kupón",
        "privacy policy": "Zásady ochrany osobních údajů",
        "terms": "podmínky",
        "terms and conditions": "pravidla a podmínky",
        "the": "a",
        "the store's": "obchody"
    },
    "da-DK": {
        "(optional)": "(valgfri)",
        "+ ADD A COUPON CODE": "+ TILFØJ EN KUPONKODE",
        "+ NEW CARD": "+ NYT KORT",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ INDLØS GAVEKORT/BUTIKSKREDIT",
        "After selecting pay you will be redirected to complete your payment.": "Når du har valgt betaling, bliver du omdirigeret til at gennemføre din betaling.",
        "Apartment": "Lejlighed",
        "Back to info": "Tilbage til info",
        "Billing": "Fakturering",
        "By clicking the button above, you agree to": "Ved at klikke på knappen ovenfor accepterer du",
        "Cancel": "Afbestille",
        "Card": "Kort",
        "Cart is empty": "Kurven er tom",
        "City": "By",
        "Continue": "Blive ved",
        "Country": "Land",
        "County": "Amt",
        "Coupon code": "Kuponkode",
        "Create a new password, or use an existing one if you already have an account for": "Opret en ny adgangskode, eller brug en eksisterende, hvis du allerede har en konto til",
        "Currency": "betalingsmiddel",
        "Delivery date": "Leveringsdato",
        "Edit": "Redigere",
        "Email": "E-mail",
        "Exit Checkout": "Afslut kassen",
        "First name": "Fornavn",
        "First renewal": "Første fornyelse",
        "Gift card number": "Gavekort nummer",
        "I verify that the country I have entered is the one I reside in": "Jeg bekræfter, at det land, jeg har indtastet, er det, jeg bor i",
        "Initial Shipment": "Indledende forsendelse",
        "Last name": "Efternavn",
        "My order": "Min bestilling",
        "Order notes (optional)": "Ordrebemærkninger (valgfrit)",
        "Order summary": "Ordreoversigt",
        "Password": "Adgangskode",
        "Pay": "Betale",
        "Payment": "Betaling",
        "Personal": "Personlig",
        "Phone number": "Telefonnummer",
        "Place order": "Angiv bestilling",
        "Please go back and try again. Missing required field": "Gå venligst tilbage og prøv igen. Mangler obligatorisk felt",
        "Postal code": "Postnummer",
        "Processing": "Forarbejdning",
        "Province": "Provins",
        "Ready to check out?": "Klar til at tjekke ud?",
        "Recurring total": "Tilbagevendende total",
        "Remove": "Fjerne",
        "Secured by": "Sikret af",
        "Select a Province": "Vælg en provins",
        "Select a State": "Vælg en stat",
        "Select a country": "Vælg et land",
        "Send to": "Send til",
        "Shipping": "Forsendelse",
        "Sorry, something went wrong. Please refresh the page and try again.": "Undskyld, noget gik galt. Opdater venligst siden og prøv igen.",
        "Sorry, this store does not ship to your location.": "Beklager, denne butik sender ikke til din placering.",
        "State": "Stat",
        "Street address": "Vejnavn",
        "Subtotal": "Subtotal",
        "Tax": "Skat",
        "Test mode: customers cannot see PeachPay": "Testtilstand: kunder kan ikke se PeachPay",
        "The password entered must be at least 8 characters long.": "Den indtastede adgangskode skal være på mindst 8 tegn.",
        "There are no eligible or active payment methods available for this order.": "Der er ingen kvalificerede eller aktive betalingsmetoder tilgængelige for denne ordre.",
        "Total": "i alt",
        "Unknown order error occurred": "Ukendt ordrefejl opstod",
        "VIEW SAVED CARDS": "SE GEMTE KORT",
        "Verified": "Verificeret",
        "You entered an invalid coupon code": "Du har indtastet en ugyldig kuponkode",
        "You entered an invalid gift card": "Du har indtastet et ugyldigt gavekort",
        "You might also like...": "Du kunne måske også lide...",
        "add": "tilføje",
        "and": "og",
        "coupon": "kupon",
        "privacy policy": "Fortrolighedspolitik",
        "terms": "vilkår",
        "terms and conditions": "vilkår og betingelser",
        "the": "det",
        "the store's": "butikkens"
    },
    "de-DE": {
        "(optional)": "(Optional)",
        "+ ADD A COUPON CODE": "+ GUTSCHEINCODE HINZUFÜGEN",
        "+ NEW CARD": "+ NEUE KARTE",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ GESCHENKKARTE EINLÖSEN/GUTSCHRIFT AUFBEWAHREN",
        "After selecting pay you will be redirected to complete your payment.": "Nachdem Sie „Bezahlen“ ausgewählt haben, werden Sie weitergeleitet, um Ihre Zahlung abzuschließen.",
        "Apartment": "Wohnung",
        "Back to info": "Zurück zu den Informationen",
        "Billing": "Abrechnung",
        "By clicking the button above, you agree to": "Indem Sie auf die Schaltfläche oben klicken, stimmen Sie zu",
        "Cancel": "Absagen",
        "Card": "Karte",
        "Cart is empty": "Einkaufswagen ist leer",
        "City": "Stadt",
        "Continue": "Fortsetzen",
        "Country": "Land",
        "County": "Bezirk",
        "Coupon code": "Gutscheincode",
        "Create a new password, or use an existing one if you already have an account for": "Erstellen Sie ein neues Passwort oder verwenden Sie ein vorhandenes, wenn Sie bereits ein Konto für haben",
        "Currency": "Währung",
        "Delivery date": "Lieferdatum",
        "Edit": "Bearbeiten",
        "Email": "Email",
        "Exit Checkout": "Kasse verlassen",
        "First name": "Vorname",
        "First renewal": "Erste Erneuerung",
        "Gift card number": "Geschenkkartennummer",
        "I verify that the country I have entered is the one I reside in": "Ich bestätige, dass das Land, in das ich eingereist bin, das Land ist, in dem ich wohne",
        "Initial Shipment": "Erstversand",
        "Last name": "Familienname, Nachname",
        "My order": "Meine Bestellung",
        "Order notes (optional)": "Bestellhinweise (optional)",
        "Order summary": "Bestellübersicht",
        "Password": "Passwort",
        "Pay": "Zahlen",
        "Payment": "Zahlung",
        "Personal": "persönlich",
        "Phone number": "Telefonnummer",
        "Place order": "Bestellung aufgeben",
        "Please go back and try again. Missing required field": "Bitte gehen Sie zurück und versuchen Sie es erneut. Pflichtfeld fehlt",
        "Postal code": "Postleitzahl",
        "Processing": "wird bearbeitet",
        "Province": "Provinz",
        "Ready to check out?": "Bereit zum Auschecken?",
        "Recurring total": "Wiederkehrende Summe",
        "Remove": "Entfernen",
        "Secured by": "Gesichert durch",
        "Select a Province": "Wählen Sie eine Provinz",
        "Select a State": "Wähle einen Staat",
        "Select a country": "Wähle ein Land",
        "Send to": "Senden an",
        "Shipping": "Versand",
        "Sorry, something went wrong. Please refresh the page and try again.": "Entschuldigung, etwas ist schief gelaufen. Bitte aktualisieren Sie die Seite und versuchen Sie es erneut.",
        "Sorry, this store does not ship to your location.": "Entschuldigung, dieser Shop liefert nicht an Ihren Standort.",
        "State": "Bundesland",
        "Street address": "Adresse",
        "Subtotal": "Zwischensumme",
        "Tax": "Steuer",
        "Test mode: customers cannot see PeachPay": "Testmodus: Kunden können PeachPay nicht sehen",
        "The password entered must be at least 8 characters long.": "Das eingegebene Passwort muss mindestens 8 Zeichen lang sein.",
        "There are no eligible or active payment methods available for this order.": "Für diese Bestellung sind keine zulässigen oder aktiven Zahlungsmethoden verfügbar.",
        "Total": "Gesamt",
        "Unknown order error occurred": "Unbekannter Bestellfehler aufgetreten",
        "VIEW SAVED CARDS": "GESPEICHERTE KARTEN ANZEIGEN",
        "Verified": "Verifiziert",
        "You entered an invalid coupon code": "Sie haben einen ungültigen Gutscheincode eingegeben",
        "You entered an invalid gift card": "Sie haben eine ungültige Geschenkkarte eingegeben",
        "You might also like...": "Das könnte dir auch gefallen...",
        "add": "hinzufügen",
        "and": "und",
        "coupon": "Coupon",
        "privacy policy": "Datenschutz-Bestimmungen",
        "terms": "Bedingungen",
        "terms and conditions": "Geschäftsbedingungen",
        "the": "das",
        "the store's": "die Läden"
    },
    "el": {
        "(optional)": "(προαιρετικός)",
        "+ ADD A COUPON CODE": "+ ΠΡΟΣΘΗΚΗ ΚΩΔΙΚΟΥ ΚΟΥΠΟΝΙΟΥ",
        "+ NEW CARD": "+ ΝΕΑ ΚΑΡΤΑ",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ ΕΞΑΡΓΥΡΩΣΤΕ ΔΩΡΟΚΑΡΤΑ/ΠΙΣΤΩΣΗ ΚΑΤΑΣΤΗΜΑΤΟΣ",
        "After selecting pay you will be redirected to complete your payment.": "Αφού επιλέξετε την πληρωμή, θα ανακατευθυνθείτε για να ολοκληρώσετε την πληρωμή σας.",
        "Apartment": "Διαμέρισμα",
        "Back to info": "Επιστροφή στις πληροφορίες",
        "Billing": "Χρέωση",
        "By clicking the button above, you agree to": "Κάνοντας κλικ στο παραπάνω κουμπί, συμφωνείτε",
        "Cancel": "Ματαίωση",
        "Card": "Κάρτα",
        "Cart is empty": "Το καλάθι είναι άδειο",
        "City": "Πόλη",
        "Continue": "Να συνεχίσει",
        "Country": "Χώρα",
        "County": "Κομητεία",
        "Coupon code": "Κωδικός κουπονιού",
        "Create a new password, or use an existing one if you already have an account for": "Δημιουργήστε έναν νέο κωδικό πρόσβασης ή χρησιμοποιήστε έναν υπάρχοντα εάν έχετε ήδη λογαριασμό",
        "Currency": "Νόμισμα",
        "Delivery date": "Ημερομηνία παράδοσης",
        "Edit": "Επεξεργασία",
        "Email": "ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΕΥΘΥΝΣΗ",
        "Exit Checkout": "Έξοδος από το ταμείο",
        "First name": "Ονομα",
        "First renewal": "Πρώτη ανανέωση",
        "Gift card number": "Αριθμός δωροκάρτας",
        "I verify that the country I have entered is the one I reside in": "Επαληθεύω ότι η χώρα στην οποία έχω εισέλθει είναι αυτή στην οποία διαμένω",
        "Initial Shipment": "Αρχική Αποστολή",
        "Last name": "Επίθετο",
        "My order": "Η παραγγελία μου",
        "Order notes (optional)": "Σημειώσεις παραγγελίας (προαιρετικά)",
        "Order summary": "Περίληψη παραγγελίας",
        "Password": "Κωδικός πρόσβασης",
        "Pay": "Πληρωμή",
        "Payment": "Πληρωμή",
        "Personal": "Προσωπικός",
        "Phone number": "Τηλεφωνικό νούμερο",
        "Place order": "Παραγγέλνω",
        "Please go back and try again. Missing required field": "Επιστρέψτε και δοκιμάστε ξανά. Λείπει υποχρεωτικό πεδίο",
        "Postal code": "Ταχυδρομικός Κώδικας",
        "Processing": "Επεξεργασία",
        "Province": "Επαρχία",
        "Ready to check out?": "Έτοιμοι για check out;",
        "Recurring total": "Επαναλαμβανόμενο σύνολο",
        "Remove": "Αφαιρώ",
        "Secured by": "Με ασφάλεια από",
        "Select a Province": "Επιλέξτε μια επαρχία",
        "Select a State": "Επιλέξτε μια πολιτεία",
        "Select a country": "Επιλέξτε χώρα",
        "Send to": "Στέλνω σε",
        "Shipping": "Αποστολή",
        "Sorry, something went wrong. Please refresh the page and try again.": "Συγνώμη, κάτι πήγε στραβά. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.",
        "Sorry, this store does not ship to your location.": "Λυπούμαστε, αυτό το κατάστημα δεν αποστέλλεται στην τοποθεσία σας.",
        "State": "κατάσταση",
        "Street address": "διεύθυνση",
        "Subtotal": "ΜΕΡΙΚΟ ΣΥΝΟΛΟ",
        "Tax": "Φόρος",
        "Test mode: customers cannot see PeachPay": "Δοκιμαστική λειτουργία: οι πελάτες δεν μπορούν να δουν το PeachPay",
        "The password entered must be at least 8 characters long.": "Ο κωδικός που εισαγάγατε πρέπει να αποτελείται από τουλάχιστον 8 χαρακτήρες.",
        "There are no eligible or active payment methods available for this order.": "Δεν υπάρχουν διαθέσιμες ή ενεργές μέθοδοι πληρωμής για αυτήν την παραγγελία.",
        "Total": "Σύνολο",
        "Unknown order error occurred": "Παρουσιάστηκε άγνωστο σφάλμα παραγγελίας",
        "VIEW SAVED CARDS": "ΠΡΟΒΟΛΗ ΑΠΟΘΗΚΕΥΜΕΝΩΝ ΚΑΡΤΩΝ",
        "Verified": "Επαληθεύτηκε",
        "You entered an invalid coupon code": "Εισαγάγατε έναν μη έγκυρο κωδικό κουπονιού",
        "You entered an invalid gift card": "Εισαγάγατε μια μη έγκυρη δωροκάρτα",
        "You might also like...": "Μπορεί επίσης να σας αρέσει...",
        "add": "Προσθήκη",
        "and": "και",
        "coupon": "κουπόνι",
        "privacy policy": "πολιτική απορρήτου",
        "terms": "όροι",
        "terms and conditions": "όροι και Προϋποθέσεις",
        "the": "ο",
        "the store's": "τα μαγαζιά"
    },
    "es-ES": {
        "(optional)": "(Opcional)",
        "+ ADD A COUPON CODE": "+ AÑADIR UN CÓDIGO DE CUPÓN",
        "+ NEW CARD": "+ NUEVA TARJETA",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ CANJEAR TARJETA DE REGALO/CRÉDITO DE LA TIENDA",
        "After selecting pay you will be redirected to complete your payment.": "Después de seleccionar pagar, será redirigido para completar su pago.",
        "Apartment": "Departamento",
        "Back to info": "volver a la información",
        "Billing": "Facturación",
        "By clicking the button above, you agree to": "Al hacer clic en el botón de arriba, usted acepta",
        "Cancel": "Cancelar",
        "Card": "Tarjeta",
        "Cart is empty": "El carrito esta vacío",
        "City": "Ciudad",
        "Continue": "Continuar",
        "Country": "País",
        "County": "Condado",
        "Coupon code": "Código promocional",
        "Create a new password, or use an existing one if you already have an account for": "Cree una nueva contraseña o use una existente si ya tiene una cuenta para",
        "Currency": "Divisa",
        "Delivery date": "Fecha de entrega",
        "Edit": "Editar",
        "Email": "Correo electrónico",
        "Exit Checkout": "Salir de la caja",
        "First name": "Nombre de pila",
        "First renewal": "Primera renovación",
        "Gift card number": "Numero de tarjeta de regalo",
        "I verify that the country I have entered is the one I reside in": "Verifico que el país en el que he entrado es en el que resido",
        "Initial Shipment": "Envío inicial",
        "Last name": "Apellido",
        "My order": "Mi pedido",
        "Order notes (optional)": "Notas de pedido (opcional)",
        "Order summary": "Resumen del pedido",
        "Password": "Clave",
        "Pay": "Pagar",
        "Payment": "Pago",
        "Personal": "Personal",
        "Phone number": "Número de teléfono",
        "Place order": "Realizar pedido",
        "Please go back and try again. Missing required field": "Por favor, regrese y vuelva a intentarlo. Faltan campos requeridos",
        "Postal code": "Código Postal",
        "Processing": "Procesando",
        "Province": "Provincia",
        "Ready to check out?": "¿Listo para pagar?",
        "Recurring total": "total recurrente",
        "Remove": "Remover",
        "Secured by": "Asegurado por",
        "Select a Province": "Seleccione una provincia",
        "Select a State": "Selecciona un Estado",
        "Select a country": "Seleccione un país",
        "Send to": "Enviar a",
        "Shipping": "Envío",
        "Sorry, something went wrong. Please refresh the page and try again.": "Perdón, algo salió mal. Actualice la página y vuelva a intentarlo.",
        "Sorry, this store does not ship to your location.": "Lo sentimos, esta tienda no realiza envíos a su ubicación.",
        "State": "Expresar",
        "Street address": "Dirección",
        "Subtotal": "Total parcial",
        "Tax": "Impuesto",
        "Test mode: customers cannot see PeachPay": "Modo de prueba: los clientes no pueden ver PeachPay",
        "The password entered must be at least 8 characters long.": "La contraseña ingresada debe tener al menos 8 caracteres.",
        "There are no eligible or active payment methods available for this order.": "No hay métodos de pago elegibles o activos disponibles para este pedido.",
        "Total": "Total",
        "Unknown order error occurred": "Se produjo un error de pedido desconocido",
        "VIEW SAVED CARDS": "VER TARJETAS GUARDADAS",
        "Verified": "Verificado",
        "You entered an invalid coupon code": "Ingresó un código de cupón no válido",
        "You entered an invalid gift card": "Ingresó una tarjeta de regalo no válida",
        "You might also like...": "También podría gustarte...",
        "add": "agregar",
        "and": "y",
        "coupon": "cupón",
        "privacy policy": "política de privacidad",
        "terms": "términos",
        "terms and conditions": "Términos y condiciones",
        "the": "la",
        "the store's": "las tiendas"
    },
    "fr-FR": {
        "(optional)": "(optionnel)",
        "+ ADD A COUPON CODE": "+ AJOUTER UN CODE PROMO",
        "+ NEW CARD": "+ NOUVELLE CARTE",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ ÉCHANGER CARTE CADEAU/CRÉDIT EN MAGASIN",
        "After selecting pay you will be redirected to complete your payment.": "Après avoir sélectionné payer, vous serez redirigé pour terminer votre paiement.",
        "Apartment": "Appartement",
        "Back to info": "Retour aux infos",
        "Billing": "Facturation",
        "By clicking the button above, you agree to": "En cliquant sur le bouton ci-dessus, vous acceptez",
        "Cancel": "Annuler",
        "Card": "Carte",
        "Cart is empty": "Le panier est vide",
        "City": "Ville",
        "Continue": "Continuez",
        "Country": "Pays",
        "County": "Comté",
        "Coupon code": "Code promo",
        "Create a new password, or use an existing one if you already have an account for": "Créez un nouveau mot de passe ou utilisez-en un existant si vous avez déjà un compte pour",
        "Currency": "Monnaie",
        "Delivery date": "La date de livraison",
        "Edit": "Modifier",
        "Email": "E-mail",
        "Exit Checkout": "Quitter la caisse",
        "First name": "Prénom",
        "First renewal": "Premier renouvellement",
        "Gift card number": "Numéro de la carte-cadeau",
        "I verify that the country I have entered is the one I reside in": "Je vérifie que le pays que j'ai entré est celui dans lequel je réside",
        "Initial Shipment": "Expédition initiale",
        "Last name": "Nom de famille",
        "My order": "Ma commande",
        "Order notes (optional)": "Notes de commande (facultatif)",
        "Order summary": "Récapitulatif de la commande",
        "Password": "Mot de passe",
        "Pay": "Payer",
        "Payment": "Paiement",
        "Personal": "Personnel",
        "Phone number": "Numéro de téléphone",
        "Place order": "Passer la commande",
        "Please go back and try again. Missing required field": "Veuillez revenir en arrière et réessayer. Champ obligatoire manquant",
        "Postal code": "Code Postal",
        "Processing": "Traitement",
        "Province": "Province",
        "Ready to check out?": "Prêt à vérifier?",
        "Recurring total": "Total récurrent",
        "Remove": "Éliminer",
        "Secured by": "Sécurisé par",
        "Select a Province": "Sélectionnez une province",
        "Select a State": "Sélectionner un état",
        "Select a country": "Choisissez un pays",
        "Send to": "Envoyer à",
        "Shipping": "Expédition",
        "Sorry, something went wrong. Please refresh the page and try again.": "Désolé, quelque chose s'est mal passé. Veuillez actualiser la page et réessayer.",
        "Sorry, this store does not ship to your location.": "Désolé, ce magasin ne livre pas à votre emplacement.",
        "State": "État",
        "Street address": "Adresse de la rue",
        "Subtotal": "Total",
        "Tax": "Impôt",
        "Test mode: customers cannot see PeachPay": "Mode test : les clients ne peuvent pas voir PeachPay",
        "The password entered must be at least 8 characters long.": "Le mot de passe saisi doit comporter au moins 8 caractères.",
        "There are no eligible or active payment methods available for this order.": "Aucun mode de paiement éligible ou actif n'est disponible pour cette commande.",
        "Total": "Total",
        "Unknown order error occurred": "Une erreur de commande inconnue s'est produite",
        "VIEW SAVED CARDS": "VOIR LES CARTES SAUVEGARDÉES",
        "Verified": "Vérifié",
        "You entered an invalid coupon code": "Vous avez saisi un code de coupon invalide",
        "You entered an invalid gift card": "Vous avez entré une carte-cadeau invalide",
        "You might also like...": "Vous pourriez aussi aimer...",
        "add": "ajouter",
        "and": "et",
        "coupon": "coupon",
        "privacy policy": "politique de confidentialité",
        "terms": "termes",
        "terms and conditions": "termes et conditions",
        "the": "la",
        "the store's": "les magasins"
    },
    "hi-IN": {
        "(optional)": "(वैकल्पिक)",
        "+ ADD A COUPON CODE": "+ एक कूपन कोड जोड़ें",
        "+ NEW CARD": "+ नया कार्ड",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ रिडीम गिफ्ट कार्ड/स्टोर क्रेडिट",
        "After selecting pay you will be redirected to complete your payment.": "वेतन का चयन करने के बाद आपको अपना भुगतान पूरा करने के लिए पुनर्निर्देशित किया जाएगा।",
        "Apartment": "अपार्टमेंट",
        "Back to info": "जानकारी पर वापस जाएं",
        "Billing": "बिलिंग",
        "By clicking the button above, you agree to": "ऊपर दिए गए बटन पर क्लिक करके, आप सहमत हैं",
        "Cancel": "रद्द करना",
        "Card": "कार्ड",
        "Cart is empty": "कार्ट खाली है",
        "City": "शहर",
        "Continue": "जारी रखें",
        "Country": "देश",
        "County": "काउंटी",
        "Coupon code": "कूपन कोड",
        "Create a new password, or use an existing one if you already have an account for": "एक नया पासवर्ड बनाएं, या किसी मौजूदा पासवर्ड का उपयोग करें यदि आपके पास पहले से ही एक खाता है",
        "Currency": "मुद्रा",
        "Delivery date": "डिलीवरी की तारीख",
        "Edit": "संपादन करना",
        "Email": "ईमेल",
        "Exit Checkout": "चेकआउट से बाहर निकलें",
        "First name": "संतोष",
        "First renewal": "पहला नवीनीकरण",
        "Gift card number": "गिफ्ट कार्ड नंबर",
        "I verify that the country I have entered is the one I reside in": "मैं सत्यापित करता/करती हूं कि जिस देश में मैंने प्रवेश किया है वह वही देश है जिसमें मैं रहता हूं",
        "Initial Shipment": "प्रारंभिक शिपमेंट",
        "Last name": "उपनाम",
        "My order": "मेरे आदेश",
        "Order notes (optional)": "आदेश नोट (वैकल्पिक)",
        "Order summary": "आदेश सारांश",
        "Password": "पासवर्ड",
        "Pay": "भुगतान करना",
        "Payment": "भुगतान",
        "Personal": "निजी",
        "Phone number": "फ़ोन नंबर",
        "Place order": "आदेश देना",
        "Please go back and try again. Missing required field": "कृपया पीछे जाएं और दोबारा कोशिश करें। आवश्यक क्षेत्र उपलभ्ध नही है",
        "Postal code": "डाक कोड",
        "Processing": "प्रसंस्करण",
        "Province": "प्रांत",
        "Ready to check out?": "चेक आउट करने के लिए तैयार हैं?",
        "Recurring total": "आवर्ती कुल",
        "Remove": "हटाना",
        "Secured by": "इसके जरिए सुरक्षित",
        "Select a Province": "एक प्रांत का चयन करें",
        "Select a State": "एक राज्य का चयन करें",
        "Select a country": "एक देश चुनें",
        "Send to": "भेजना",
        "Shipping": "शिपिंग",
        "Sorry, something went wrong. Please refresh the page and try again.": "क्षमा करें, कुछ गलत हो गया। पृष्ठ को रीफ्रेश करें और पुन: प्रयास करें।",
        "Sorry, this store does not ship to your location.": "क्षमा करें, यह स्टोर आपके स्थान पर शिप नहीं करता है।",
        "State": "राज्य",
        "Street address": "गली का पता",
        "Subtotal": "उप-योग",
        "Tax": "कर",
        "Test mode: customers cannot see PeachPay": "परीक्षण मोड: ग्राहक पीचपे नहीं देख सकते हैं",
        "The password entered must be at least 8 characters long.": "दर्ज किया गया पासवर्ड कम से कम 8 वर्ण लंबा होना चाहिए।",
        "There are no eligible or active payment methods available for this order.": "इस आदेश के लिए कोई योग्य या सक्रिय भुगतान विधियां उपलब्ध नहीं हैं।",
        "Total": "कुल",
        "Unknown order error occurred": "अज्ञात आदेश त्रुटि हुई",
        "VIEW SAVED CARDS": "सहेजे गए कार्ड देखें",
        "Verified": "सत्यापित",
        "You entered an invalid coupon code": "आपने एक अमान्य कूपन कोड दर्ज किया है",
        "You entered an invalid gift card": "आपने एक अमान्य उपहार कार्ड दर्ज किया है",
        "You might also like...": "शायद तुम्हे यह भी अच्छा लगे...",
        "add": "जोड़ें",
        "and": "और",
        "coupon": "कूपन",
        "privacy policy": "गोपनीयता नीति",
        "terms": "शर्तें",
        "terms and conditions": "नियम और शर्तें",
        "the": "",
        "the store's": "दुकानें"
    },
    "it": {
        "(optional)": "(opzionale)",
        "+ ADD A COUPON CODE": "+ AGGIUNGI UN CODICE COUPON",
        "+ NEW CARD": "+ NUOVA CARTA",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ UTILIZZA CARTA REGALO/CREDITO NEGOZIO",
        "After selecting pay you will be redirected to complete your payment.": "Dopo aver selezionato paga verrai reindirizzato per completare il pagamento.",
        "Apartment": "Appartamento",
        "Back to info": "Torna alle informazioni",
        "Billing": "Fatturazione",
        "By clicking the button above, you agree to": "Facendo clic sul pulsante in alto, accetti",
        "Cancel": "Annulla",
        "Card": "Carta",
        "Cart is empty": "Il carrello è vuoto",
        "City": "Città",
        "Continue": "Continua",
        "Country": "Paese",
        "County": "contea",
        "Coupon code": "Codice coupon",
        "Create a new password, or use an existing one if you already have an account for": "Crea una nuova password o usane una esistente se hai già un account per",
        "Currency": "Moneta",
        "Delivery date": "Data di consegna",
        "Edit": "Modificare",
        "Email": "E-mail",
        "Exit Checkout": "Esci dalla cassa",
        "First name": "nome di battesimo",
        "First renewal": "Primo rinnovo",
        "Gift card number": "Numero della carta regalo",
        "I verify that the country I have entered is the one I reside in": "Verifico che il paese in cui ho inserito è quello in cui risiedo",
        "Initial Shipment": "Spedizione iniziale",
        "Last name": "Cognome",
        "My order": "Il mio ordine",
        "Order notes (optional)": "Note sull'ordine (opzionale)",
        "Order summary": "Riepilogo dell'ordine",
        "Password": "Parola d'ordine",
        "Pay": "Paga",
        "Payment": "Pagamento",
        "Personal": "Personale",
        "Phone number": "Numero di telefono",
        "Place order": "Invia ordine",
        "Please go back and try again. Missing required field": "Torna indietro e riprova. campo richiesto mancante",
        "Postal code": "Codice postale",
        "Processing": "in lavorazione",
        "Province": "Provincia",
        "Ready to check out?": "Pronto per il check-out?",
        "Recurring total": "Totale ricorrente",
        "Remove": "Rimuovere",
        "Secured by": "Protetto da",
        "Select a Province": "Seleziona una provincia",
        "Select a State": "Seleziona uno Stato",
        "Select a country": "Seleziona un Paese",
        "Send to": "Inviare a",
        "Shipping": "Spedizione",
        "Sorry, something went wrong. Please refresh the page and try again.": "Scusa, qualcosa è andato storto. Perfavore ricarica la pagina e riprova.",
        "Sorry, this store does not ship to your location.": "Siamo spiacenti, questo negozio non effettua spedizioni nella tua posizione.",
        "State": "Stato",
        "Street address": "indirizzo",
        "Subtotal": "totale parziale",
        "Tax": "Imposta",
        "Test mode: customers cannot see PeachPay": "Modalità test: i clienti non possono vedere PeachPay",
        "The password entered must be at least 8 characters long.": "La password inserita deve essere lunga almeno 8 caratteri.",
        "There are no eligible or active payment methods available for this order.": "Non sono disponibili metodi di pagamento idonei o attivi per questo ordine.",
        "Total": "Totale",
        "Unknown order error occurred": "Si è verificato un errore di ordine sconosciuto",
        "VIEW SAVED CARDS": "VISUALIZZA LE CARTE SALVATE",
        "Verified": "Verificato",
        "You entered an invalid coupon code": "Hai inserito un codice coupon non valido",
        "You entered an invalid gift card": "Hai inserito una carta regalo non valida",
        "You might also like...": "Potrebbe piacerti anche...",
        "add": "Inserisci",
        "and": "e",
        "coupon": "buono",
        "privacy policy": "politica sulla riservatezza",
        "terms": "termini",
        "terms and conditions": "Termini e Condizioni",
        "the": "il",
        "the store's": "i negozi"
    },
    "ja": {
        "(optional)": "（オプション）",
        "+ ADD A COUPON CODE": "+クーポンコードを追加する",
        "+ NEW CARD": "+新しいカード",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ギフトカード/ストアクレジットを利用する",
        "After selecting pay you will be redirected to complete your payment.": "支払いを選択すると、支払いを完了するためにリダイレクトされます。",
        "Apartment": "アパート",
        "Back to info": "情報に戻る",
        "Billing": "請求する",
        "By clicking the button above, you agree to": "上のボタンをクリックすると、同意したことになります",
        "Cancel": "キャンセル",
        "Card": "カード",
        "Cart is empty": "カートは空です",
        "City": "街",
        "Continue": "継続する",
        "Country": "国",
        "County": "郡",
        "Coupon code": "クーポンコード",
        "Create a new password, or use an existing one if you already have an account for": "新しいパスワードを作成するか、すでにアカウントをお持ちの場合は既存のパスワードを使用してください",
        "Currency": "通貨",
        "Delivery date": "配送日",
        "Edit": "編集",
        "Email": "Eメール",
        "Exit Checkout": "チェックアウトを終了します",
        "First name": "ファーストネーム",
        "First renewal": "最初の更新",
        "Gift card number": "ギフトカード番号",
        "I verify that the country I have entered is the one I reside in": "私が入国した国が私が居住している国であることを確認します",
        "Initial Shipment": "最初の出荷",
        "Last name": "苗字",
        "My order": "私の注文",
        "Order notes (optional)": "注文メモ（オプション）",
        "Order summary": "注文の概要",
        "Password": "パスワード",
        "Pay": "支払い",
        "Payment": "支払い",
        "Personal": "個人的",
        "Phone number": "電話番号",
        "Place order": "注文する",
        "Please go back and try again. Missing required field": "戻ってもう一度やり直してください。必須フィールドがありません",
        "Postal code": "郵便番号",
        "Processing": "処理",
        "Province": "州",
        "Ready to check out?": "チェックアウトする準備はできましたか？",
        "Recurring total": "経常合計",
        "Remove": "削除する",
        "Secured by": "によって保護",
        "Select a Province": "州を選択",
        "Select a State": "州を選択",
        "Select a country": "国を選択",
        "Send to": "に送る",
        "Shipping": "運送",
        "Sorry, something went wrong. Please refresh the page and try again.": "申し訳ありませんが、問題が発生しました。ページを更新して、もう一度お試しください。",
        "Sorry, this store does not ship to your location.": "申し訳ありませんが、このストアはお住まいの地域に発送されません。",
        "State": "州",
        "Street address": "住所",
        "Subtotal": "小計",
        "Tax": "税",
        "Test mode: customers cannot see PeachPay": "テストモード：顧客はPeachPayを見ることができません",
        "The password entered must be at least 8 characters long.": "入力するパスワードは8文字以上である必要があります。",
        "There are no eligible or active payment methods available for this order.": "この注文に利用できる適格または有効な支払い方法はありません。",
        "Total": "合計",
        "Unknown order error occurred": "不明な注文エラーが発生しました",
        "VIEW SAVED CARDS": "保存されたカードを表示する",
        "Verified": "確認済み",
        "You entered an invalid coupon code": "無効なクーポンコードを入力しました",
        "You entered an invalid gift card": "無効なギフトカードを入力しました",
        "You might also like...": "あなたはおそらくそれも好きでしょう...",
        "add": "追加",
        "and": "と",
        "coupon": "クーポン",
        "privacy policy": "プライバシーポリシー",
        "terms": "条項",
        "terms and conditions": "規約と条件",
        "the": "the",
        "the store's": "店舗"
    },
    "ko-KR": {
        "(optional)": "(선택 과목)",
        "+ ADD A COUPON CODE": "+ 쿠폰 코드 추가",
        "+ NEW CARD": "+ 새 카드",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ 기프트 카드/매장 크레딧 사용",
        "After selecting pay you will be redirected to complete your payment.": "지불을 선택하면 지불을 완료하기 위해 리디렉션됩니다.",
        "Apartment": "아파트",
        "Back to info": "정보로 돌아가기",
        "Billing": "청구",
        "By clicking the button above, you agree to": "위 버튼을 클릭하면 동의하는 것으로 간주됩니다.",
        "Cancel": "취소",
        "Card": "카드",
        "Cart is empty": "장바구니가 비어 있습니다.",
        "City": "도시",
        "Continue": "계속하다",
        "Country": "국가",
        "County": "군",
        "Coupon code": "쿠폰 코드",
        "Create a new password, or use an existing one if you already have an account for": "새 비밀번호를 생성하거나 이미 계정이 있는 경우 기존 비밀번호를 사용하십시오.",
        "Currency": "통화",
        "Delivery date": "배송 날짜",
        "Edit": "편집하다",
        "Email": "이메일",
        "Exit Checkout": "체크아웃 종료",
        "First name": "이름",
        "First renewal": "첫 번째 갱신",
        "Gift card number": "기프트 카드 번호",
        "I verify that the country I have entered is the one I reside in": "내가 입력한 국가가 내가 거주하는 국가인지 확인합니다.",
        "Initial Shipment": "초기 선적",
        "Last name": "성",
        "My order": "내 주문",
        "Order notes (optional)": "주문 메모(선택 사항)",
        "Order summary": "주문 요약",
        "Password": "비밀번호",
        "Pay": "지불",
        "Payment": "지불",
        "Personal": "개인적인",
        "Phone number": "전화 번호",
        "Place order": "주문하기",
        "Please go back and try again. Missing required field": "돌아가서 다시 시도하십시오. 필수 항목 누락",
        "Postal code": "우편 번호",
        "Processing": "처리",
        "Province": "주",
        "Ready to check out?": "체크아웃할 준비가 되셨나요?",
        "Recurring total": "반복 합계",
        "Remove": "제거하다",
        "Secured by": "보안",
        "Select a Province": "주를 선택하십시오",
        "Select a State": "주 선택",
        "Select a country": "국가를 고르시 오",
        "Send to": "보내기",
        "Shipping": "배송",
        "Sorry, something went wrong. Please refresh the page and try again.": "죄송합니다. 문제가 발생했습니다. 페이지를 새로고침하고 다시 시도하십시오.",
        "Sorry, this store does not ship to your location.": "죄송합니다. 이 상점은 귀하의 위치로 배송되지 않습니다.",
        "State": "상태",
        "Street address": "주소",
        "Subtotal": "소계",
        "Tax": "세",
        "Test mode: customers cannot see PeachPay": "테스트 모드: 고객이 PeachPay를 볼 수 없습니다.",
        "The password entered must be at least 8 characters long.": "입력한 비밀번호는 8자 이상이어야 합니다.",
        "There are no eligible or active payment methods available for this order.": "이 주문에 사용할 수 있는 적격 또는 활성 결제 방법이 없습니다.",
        "Total": "총",
        "Unknown order error occurred": "알 수 없는 주문 오류가 발생했습니다",
        "VIEW SAVED CARDS": "저장된 카드 보기",
        "Verified": "확인됨",
        "You entered an invalid coupon code": "잘못된 쿠폰 코드를 입력했습니다.",
        "You entered an invalid gift card": "잘못된 기프트 카드를 입력했습니다.",
        "You might also like...": "당신은 또한 좋아할 수도 있습니다 ...",
        "add": "추가하다",
        "and": "그리고",
        "coupon": "쿠폰",
        "privacy policy": "개인 정보 정책",
        "terms": "자귀",
        "terms and conditions": "이용약관",
        "the": "그만큼",
        "the store's": "상점들"
    },
    "lb-LU": {
        "(optional)": "(optional)",
        "+ ADD A COUPON CODE": "+ ENG COUPON CODE bäizefügen",
        "+ NEW CARD": "+ NEW KAART",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ GIFT CARD / STORE CREDIT REDEEM",
        "After selecting pay you will be redirected to complete your payment.": "Nodeems Dir Bezuelung gewielt hutt, gitt Dir ëmgeleet fir Är Bezuelung ofzeschléissen.",
        "Apartment": "Appartement",
        "Back to info": "Zréck op d'Info",
        "Billing": "Rechnung",
        "By clicking the button above, you agree to": "Andeems Dir op de Knäppchen hei uewen klickt, sidd Dir averstanen",
        "Cancel": "Ofbriechen",
        "Card": "Kaart",
        "Cart is empty": "Weenchen ass eidel",
        "City": "Stad",
        "Continue": "Fuert weider",
        "Country": "Land",
        "County": "Grofschaft",
        "Coupon code": "Coupon Code",
        "Create a new password, or use an existing one if you already have an account for": "Erstellt en neit Passwuert oder benotzt en existent wann Dir schonn e Kont hutt fir",
        "Currency": "Währung",
        "Delivery date": "Liwwerungsdatum",
        "Edit": "Edit",
        "Email": "E-Mail",
        "Exit Checkout": "Exit Checkout",
        "First name": "Virnumm",
        "First renewal": "Éischt Erneierung",
        "Gift card number": "Cadeau Kaart Zuel",
        "I verify that the country I have entered is the one I reside in": "Ech verifizéieren datt d'Land wou ech aginn hunn ass dat an deem ech wunnen",
        "Initial Shipment": "Éischt Versand",
        "Last name": "Familljennumm",
        "My order": "Meng Bestellung",
        "Order notes (optional)": "Bestellungsnotizen (optional)",
        "Order summary": "Bestellung Resumé",
        "Password": "Passwuert",
        "Pay": "Bezuelen",
        "Payment": "Bezuelen",
        "Personal": "Perséinlech",
        "Phone number": "Telefonsnummer",
        "Place order": "Bestellung maachen",
        "Please go back and try again. Missing required field": "Gitt w.e.g. zréck a probéiert nach eng Kéier. Vermësst néideg Feld",
        "Postal code": "Postleitzuel",
        "Processing": "Veraarbechtung",
        "Province": "Provënz",
        "Ready to check out?": "Prett fir auszechecken?",
        "Recurring total": "Widderhuelend Ganzen",
        "Remove": "Ewechzehuelen",
        "Secured by": "Geséchert duerch",
        "Select a Province": "Wielt eng Provënz",
        "Select a State": "Wielt e Staat",
        "Select a country": "Wielt e Land",
        "Send to": "Schécken",
        "Shipping": "Liwwerung",
        "Sorry, something went wrong. Please refresh the page and try again.": "Entschëllegt, eppes ass falsch gaang. Erfrëscht w.e.g. d'Säit a probéiert nach eng Kéier.",
        "Sorry, this store does not ship to your location.": "Entschëllegt, dëse Buttek schéckt net op Är Plaz.",
        "State": "Staat",
        "Street address": "Strooss Adress",
        "Subtotal": "Subtotal",
        "Tax": "Steier",
        "Test mode: customers cannot see PeachPay": "Testmodus: Clienten kënnen PeachPay net gesinn",
        "The password entered must be at least 8 characters long.": "D'Passwuert aginn muss op d'mannst 8 Zeechen laang sinn.",
        "There are no eligible or active payment methods available for this order.": "Et gi keng berechtegt oder aktiv Bezuelmethoden verfügbar fir dës Bestellung.",
        "Total": "Ganzen",
        "Unknown order error occurred": "Onbekannte Bestellungsfehler ass geschitt",
        "VIEW SAVED CARDS": "VIEW GESPERT KAARTEN",
        "Verified": "Verifizéiert",
        "You entered an invalid coupon code": "Dir hutt en ongëlteg Coupon Code aginn",
        "You entered an invalid gift card": "Dir hutt eng ongëlteg Geschenkkaart aginn",
        "You might also like...": "Dir kënnt och gär ...",
        "add": "dobäizemaachen",
        "and": "an",
        "coupon": "Coupon",
        "privacy policy": "Privatsphär Politik",
        "terms": "Begrëffer",
        "terms and conditions": "Konditioune",
        "the": "den",
        "the store's": "dem Buttek"
    },
    "nl-NL": {
        "(optional)": "(optioneel)",
        "+ ADD A COUPON CODE": "+ EEN COUPONCODE TOEVOEGEN",
        "+ NEW CARD": "+ NIEUWE KAART",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ VERWISSEL CADEAUBON/STORE CREDIT",
        "After selecting pay you will be redirected to complete your payment.": "Na het selecteren van betalen wordt u doorgestuurd om uw betaling af te ronden.",
        "Apartment": "Appartement",
        "Back to info": "Terug naar info",
        "Billing": "Facturering",
        "By clicking the button above, you agree to": "Door op de bovenstaande knop te klikken, gaat u akkoord met:",
        "Cancel": "Annuleren",
        "Card": "Kaart",
        "Cart is empty": "Winkelwagen is leeg",
        "City": "Stad",
        "Continue": "Doorgaan",
        "Country": "Land",
        "County": "District",
        "Coupon code": "Coupon code",
        "Create a new password, or use an existing one if you already have an account for": "Maak een nieuw wachtwoord aan, of gebruik een bestaand wachtwoord als je al een account hebt voor",
        "Currency": "Munteenheid",
        "Delivery date": "Bezorgdatum",
        "Edit": "Bewerk",
        "Email": "E-mail",
        "Exit Checkout": "Afrekenen afsluiten",
        "First name": "Voornaam",
        "First renewal": "Eerste verlenging",
        "Gift card number": "Cadeaukaartnummer",
        "I verify that the country I have entered is the one I reside in": "Ik verifieer dat het land dat ik heb ingevoerd het land is waarin ik woon",
        "Initial Shipment": "Eerste zending:",
        "Last name": "Achternaam",
        "My order": "Mijn bestelling",
        "Order notes (optional)": "Bestelnotities (optioneel)",
        "Order summary": "Overzicht van de bestelling",
        "Password": "Wachtwoord",
        "Pay": "Betalen",
        "Payment": "Betaling",
        "Personal": "persoonlijk",
        "Phone number": "Telefoonnummer",
        "Place order": "Plaats bestelling",
        "Please go back and try again. Missing required field": "Ga terug en probeer het opnieuw. ontbrekende vereiste veld",
        "Postal code": "Postcode",
        "Processing": "Verwerken",
        "Province": "Provincie",
        "Ready to check out?": "Klaar om uit te checken?",
        "Recurring total": "Terugkerend totaal",
        "Remove": "Verwijderen",
        "Secured by": "Beveiligd door",
        "Select a Province": "Selecteer een provincie",
        "Select a State": "Selecteer een staat",
        "Select a country": "Selecteer een land",
        "Send to": "Verzenden naar",
        "Shipping": "Verzending",
        "Sorry, something went wrong. Please refresh the page and try again.": "Sorry, er ging iets mis. Ververs de pagina en probeer het opnieuw.",
        "Sorry, this store does not ship to your location.": "Sorry, deze winkel verzendt niet naar jouw locatie.",
        "State": "Staat",
        "Street address": "Adres",
        "Subtotal": "Subtotaal",
        "Tax": "Belasting",
        "Test mode: customers cannot see PeachPay": "Testmodus: klanten kunnen PeachPay niet zien",
        "The password entered must be at least 8 characters long.": "Het ingevoerde wachtwoord moet minimaal 8 tekens lang zijn.",
        "There are no eligible or active payment methods available for this order.": "Er zijn geen geschikte of actieve betaalmethoden beschikbaar voor deze bestelling.",
        "Total": "Totaal",
        "Unknown order error occurred": "Er is een onbekende bestellingsfout opgetreden",
        "VIEW SAVED CARDS": "OPGESLAGEN KAARTEN BEKIJKEN",
        "Verified": "Geverifieerd",
        "You entered an invalid coupon code": "U heeft een ongeldige couponcode ingevoerd",
        "You entered an invalid gift card": "Je hebt een ongeldige cadeaubon ingevoerd",
        "You might also like...": "Misschien vind je het ook leuk...",
        "add": "toevoegen",
        "and": "en",
        "coupon": "coupon",
        "privacy policy": "privacybeleid",
        "terms": "termen",
        "terms and conditions": "voorwaarden",
        "the": "de",
        "the store's": "de winkels"
    },
    "pt-PT": {
        "(optional)": "(opcional)",
        "+ ADD A COUPON CODE": "+ ADICIONE UM CÓDIGO DE CUPOM",
        "+ NEW CARD": "+ NOVO CARTÃO",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ RESGATAR CARTÃO-PRESENTE/CRÉDITO NA LOJA",
        "After selecting pay you will be redirected to complete your payment.": "Após selecionar o pagamento, você será redirecionado para concluir seu pagamento.",
        "Apartment": "Apartamento",
        "Back to info": "Voltar para informações",
        "Billing": "Cobrança",
        "By clicking the button above, you agree to": "Ao clicar no botão acima, você concorda em",
        "Cancel": "Cancelar",
        "Card": "Cartão",
        "Cart is empty": "carrinho esta vazio",
        "City": "Cidade",
        "Continue": "Continuar",
        "Country": "País",
        "County": "Condado",
        "Coupon code": "Código do cupom",
        "Create a new password, or use an existing one if you already have an account for": "Crie uma nova senha ou use uma existente se você já tiver uma conta para",
        "Currency": "Moeda",
        "Delivery date": "Data de entrega",
        "Edit": "Editar",
        "Email": "E-mail",
        "Exit Checkout": "Sair do Checkout",
        "First name": "Primeiro nome",
        "First renewal": "Primeira renovação",
        "Gift card number": "Número do cartão-presente",
        "I verify that the country I have entered is the one I reside in": "Certifico que o país em que entrei é aquele em que resido",
        "Initial Shipment": "Remessa inicial",
        "Last name": "Último nome",
        "My order": "Meu pedido",
        "Order notes (optional)": "Notas do pedido (opcional)",
        "Order summary": "Resumo do pedido",
        "Password": "Senha",
        "Pay": "Pagar",
        "Payment": "Pagamento",
        "Personal": "Pessoal",
        "Phone number": "Número de telefone",
        "Place order": "Faça a encomenda",
        "Please go back and try again. Missing required field": "Por favor volte e tente novamente. Campo obrigatório ausente",
        "Postal code": "Código postal",
        "Processing": "Em processamento",
        "Province": "Província",
        "Ready to check out?": "Pronto para conferir?",
        "Recurring total": "Total recorrente",
        "Remove": "Remover",
        "Secured by": "Assegurado por",
        "Select a Province": "Selecione uma província",
        "Select a State": "Selecione um Estado",
        "Select a country": "Selecione um pais",
        "Send to": "Enviar para",
        "Shipping": "Envio",
        "Sorry, something went wrong. Please refresh the page and try again.": "Desculpe, algo deu errado. Atualize a página e tente novamente.",
        "Sorry, this store does not ship to your location.": "Desculpe, esta loja não envia para sua localização.",
        "State": "Estado",
        "Street address": "endereço da Rua",
        "Subtotal": "Subtotal",
        "Tax": "Imposto",
        "Test mode: customers cannot see PeachPay": "Modo de teste: os clientes não podem ver o PeachPay",
        "The password entered must be at least 8 characters long.": "A senha inserida deve ter pelo menos 8 caracteres.",
        "There are no eligible or active payment methods available for this order.": "Não há métodos de pagamento qualificados ou ativos disponíveis para este pedido.",
        "Total": "Total",
        "Unknown order error occurred": "Ocorreu um erro de pedido desconhecido",
        "VIEW SAVED CARDS": "VER CARTÕES GUARDADOS",
        "Verified": "Verificado",
        "You entered an invalid coupon code": "Você inseriu um código de cupom inválido",
        "You entered an invalid gift card": "Você inseriu um vale-presente inválido",
        "You might also like...": "Você pode gostar também...",
        "add": "adicionar",
        "and": "e",
        "coupon": "cupom",
        "privacy policy": "política de Privacidade",
        "terms": "termos",
        "terms and conditions": "termos e Condições",
        "the": "a",
        "the store's": "as lojas"
    },
    "ro-RO": {
        "(optional)": "(optional)",
        "+ ADD A COUPON CODE": "+ ADĂUGAȚI UN COD CUPON",
        "+ NEW CARD": "+ CARD NOU",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ RUTILIZAȚI CARDUL CADOU/CREDIT DE MAGAZIN",
        "After selecting pay you will be redirected to complete your payment.": "După ce ați selectat plata, veți fi redirecționat pentru a finaliza plata.",
        "Apartment": "Apartament",
        "Back to info": "Înapoi la informații",
        "Billing": "Facturare",
        "By clicking the button above, you agree to": "Făcând clic pe butonul de mai sus, sunteți de acord",
        "Cancel": "Anulare",
        "Card": "Card",
        "Cart is empty": "Coșul este gol",
        "City": "Oraș",
        "Continue": "Continua",
        "Country": "Țară",
        "County": "jud",
        "Coupon code": "Cod cupon",
        "Create a new password, or use an existing one if you already have an account for": "Creați o nouă parolă sau utilizați una existentă dacă aveți deja un cont pentru",
        "Currency": "Valută",
        "Delivery date": "Data de livrare",
        "Edit": "Editați | ×",
        "Email": "E-mail",
        "Exit Checkout": "Ieșiți din casă",
        "First name": "Nume",
        "First renewal": "Prima reînnoire",
        "Gift card number": "Numărul cardului cadou",
        "I verify that the country I have entered is the one I reside in": "Verific că țara în care am intrat este cea în care locuiesc",
        "Initial Shipment": "Livrare inițială",
        "Last name": "Nume",
        "My order": "Comanda mea",
        "Order notes (optional)": "Note de comandă (opțional)",
        "Order summary": "Comanda Rezumat",
        "Password": "Parola",
        "Pay": "A plati",
        "Payment": "Plată",
        "Personal": "Personal",
        "Phone number": "Numar de telefon",
        "Place order": "Plasați comanda",
        "Please go back and try again. Missing required field": "Vă rugăm să reveniți și să încercați din nou. Lipsește câmp obligatoriu",
        "Postal code": "Cod poștal",
        "Processing": "Prelucrare",
        "Province": "Provincie",
        "Ready to check out?": "Ești gata să verifici?",
        "Recurring total": "Total recurent",
        "Remove": "Elimina",
        "Secured by": "Securizat de",
        "Select a Province": "Selectați o provincie",
        "Select a State": "Selecteaza un stat",
        "Select a country": "Selecteaza o tara",
        "Send to": "Trimite catre",
        "Shipping": "livrare",
        "Sorry, something went wrong. Please refresh the page and try again.": "Scuze, ceva a mers greșit. Vă rugăm să reîmprospătați pagina și să încercați din nou.",
        "Sorry, this store does not ship to your location.": "Ne pare rău, acest magazin nu se livrează în locația dvs.",
        "State": "Stat",
        "Street address": "adresa străzii",
        "Subtotal": "Subtotal",
        "Tax": "Impozit",
        "Test mode: customers cannot see PeachPay": "Mod de testare: clienții nu pot vedea PeachPay",
        "The password entered must be at least 8 characters long.": "Parola introdusă trebuie să aibă cel puțin 8 caractere.",
        "There are no eligible or active payment methods available for this order.": "Nu există metode de plată eligibile sau active disponibile pentru această comandă.",
        "Total": "Total",
        "Unknown order error occurred": "A apărut o eroare de comandă necunoscută",
        "VIEW SAVED CARDS": "VEZI CARDURI SALVATE",
        "Verified": "Verificat",
        "You entered an invalid coupon code": "Ați introdus un cod de cupon nevalid",
        "You entered an invalid gift card": "Ați introdus un card cadou nevalid",
        "You might also like...": "S-ar putea sa-ti placa si...",
        "add": "adăuga",
        "and": "și",
        "coupon": "cupon",
        "privacy policy": "Politica de Confidențialitate",
        "terms": "termeni",
        "terms and conditions": "Termeni și condiții",
        "the": "cel",
        "the store's": "magazinele"
    },
    "ru-RU": {
        "(optional)": "(необязательный)",
        "+ ADD A COUPON CODE": "+ ДОБАВИТЬ КОД КУПОНА",
        "+ NEW CARD": "+ НОВАЯ КАРТА",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ ПОКУПАТЬ ПОДАРОЧНУЮ КАРТУ/КРЕДИТ В МАГАЗИНЕ",
        "After selecting pay you will be redirected to complete your payment.": "После выбора оплаты вы будете перенаправлены для завершения платежа.",
        "Apartment": "Квартира",
        "Back to info": "Назад к информации",
        "Billing": "Выставление счетов",
        "By clicking the button above, you agree to": "Нажимая кнопку выше, вы соглашаетесь",
        "Cancel": "Отмена",
        "Card": "Карта",
        "Cart is empty": "Корзина пуста",
        "City": "Город",
        "Continue": "Продолжать",
        "Country": "Страна",
        "County": "округ",
        "Coupon code": "Код купона",
        "Create a new password, or use an existing one if you already have an account for": "Создайте новый пароль или используйте существующий, если у вас уже есть учетная запись для",
        "Currency": "Валюта",
        "Delivery date": "Дата доставки",
        "Edit": "Редактировать",
        "Email": "Эл. адрес",
        "Exit Checkout": "Выйти из кассы",
        "First name": "Имя",
        "First renewal": "Первое обновление",
        "Gift card number": "Номер подарочной карты",
        "I verify that the country I have entered is the one I reside in": "Я подтверждаю, что страна, в которую я въехал, является той, в которой я проживаю",
        "Initial Shipment": "Первоначальная отгрузка",
        "Last name": "Фамилия",
        "My order": "Мой заказ",
        "Order notes (optional)": "Примечания к заказу (необязательно)",
        "Order summary": "Итог заказа",
        "Password": "Пароль",
        "Pay": "Платить",
        "Payment": "Оплата",
        "Personal": "Личный",
        "Phone number": "Телефонный номер",
        "Place order": "Разместить заказ",
        "Please go back and try again. Missing required field": "Пожалуйста вернитесь и попробуйте снова. Отсутсвует необходимое поле",
        "Postal code": "Почтовый Код",
        "Processing": "Обработка",
        "Province": "Провинция",
        "Ready to check out?": "Готовы проверить?",
        "Recurring total": "Общая сумма",
        "Remove": "Удалять",
        "Secured by": "Защищено",
        "Select a Province": "Выберите провинцию",
        "Select a State": "Выберите штат",
        "Select a country": "Выберите страну",
        "Send to": "Отправить",
        "Shipping": "Перевозки",
        "Sorry, something went wrong. Please refresh the page and try again.": "Извините, что-то пошло не так. Пожалуйста, обновите страницу и повторите попытку.",
        "Sorry, this store does not ship to your location.": "Извините, этот магазин не осуществляет доставку в ваш город.",
        "State": "Состояние",
        "Street address": "адрес улицы",
        "Subtotal": "Промежуточный итог",
        "Tax": "налог",
        "Test mode: customers cannot see PeachPay": "Тестовый режим: клиенты не видят PeachPay",
        "The password entered must be at least 8 characters long.": "Введенный пароль должен содержать не менее 8 символов.",
        "There are no eligible or active payment methods available for this order.": "Для этого заказа нет подходящих или активных способов оплаты.",
        "Total": "Общее",
        "Unknown order error occurred": "Произошла неизвестная ошибка заказа",
        "VIEW SAVED CARDS": "ПОСМОТРЕТЬ СОХРАНЕННЫЕ КАРТЫ",
        "Verified": "проверено",
        "You entered an invalid coupon code": "Вы ввели неверный код купона",
        "You entered an invalid gift card": "Вы ввели недействительную подарочную карту",
        "You might also like...": "Вам также может понравиться...",
        "add": "добавлять",
        "and": "и",
        "coupon": "купон",
        "privacy policy": "политика конфиденциальности",
        "terms": "условия",
        "terms and conditions": "условия и положения",
        "the": "в",
        "the store's": "магазины"
    },
    "sl-SI": {
        "(optional)": "(neobvezno)",
        "+ ADD A COUPON CODE": "+ DODAJ KODO KUPON",
        "+ NEW CARD": "+ NOVA KARTICA",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ IZKLJUČITE DARILNO KARTICO/KREDIT TRGOVINE",
        "After selecting pay you will be redirected to complete your payment.": "Po izbiri plačila boste preusmerjeni na dokončanje plačila.",
        "Apartment": "Apartma",
        "Back to info": "Nazaj na informacije",
        "Billing": "Zaračunavanje",
        "By clicking the button above, you agree to": "S klikom na zgornji gumb se strinjate",
        "Cancel": "Prekliči",
        "Card": "Kartica",
        "Cart is empty": "Košarica je prazna",
        "City": "mesto",
        "Continue": "Nadaljuj",
        "Country": "Država",
        "County": "okrožje",
        "Coupon code": "Koda kupona",
        "Create a new password, or use an existing one if you already have an account for": "Ustvarite novo geslo ali uporabite obstoječega, če že imate račun za",
        "Currency": "valuta",
        "Delivery date": "Datum dostave",
        "Edit": "Uredi",
        "Email": "E-naslov",
        "Exit Checkout": "Izhod iz blagajne",
        "First name": "Ime",
        "First renewal": "Prva obnova",
        "Gift card number": "Številka darilne kartice",
        "I verify that the country I have entered is the one I reside in": "Potrjujem, da je država, v katero sem vstopil, tista, v kateri prebivam",
        "Initial Shipment": "Začetna pošiljka",
        "Last name": "Priimek",
        "My order": "Moj ukaz",
        "Order notes (optional)": "Opombe o naročilu (neobvezno)",
        "Order summary": "Povzetek naročila",
        "Password": "Geslo",
        "Pay": "plačati",
        "Payment": "Plačilo",
        "Personal": "Osebno",
        "Phone number": "Telefonska številka",
        "Place order": "Naročiti",
        "Please go back and try again. Missing required field": "Prosimo, vrnite se in poskusite znova. Manjka obvezno polje",
        "Postal code": "Poštna številka",
        "Processing": "Obravnavati",
        "Province": "provinca",
        "Ready to check out?": "Ste pripravljeni na odjavo?",
        "Recurring total": "Ponavljajoče se skupno",
        "Remove": "Odstrani",
        "Secured by": "Zavarovano z",
        "Select a Province": "Izberite provinco",
        "Select a State": "Izberite državo",
        "Select a country": "Izberite državo",
        "Send to": "Pošlji",
        "Shipping": "Dostava",
        "Sorry, something went wrong. Please refresh the page and try again.": "Oprostite, nekaj je šlo narobe. Osvežite stran in poskusite znova.",
        "Sorry, this store does not ship to your location.": "Žal ta trgovina ne pošilja na vašo lokacijo.",
        "State": "Država",
        "Street address": "naslov ceste",
        "Subtotal": "Vmesni seštevek",
        "Tax": "davek",
        "Test mode: customers cannot see PeachPay": "Testni način: stranke ne vidijo PeachPay",
        "The password entered must be at least 8 characters long.": "Vneseno geslo mora biti dolgo vsaj 8 znakov.",
        "There are no eligible or active payment methods available for this order.": "Za to naročilo ni na voljo nobenih ustreznih ali aktivnih plačilnih sredstev.",
        "Total": "Skupaj",
        "Unknown order error occurred": "Prišlo je do neznane napake pri naročilu",
        "VIEW SAVED CARDS": "OGLED SHRANJENIH KARTIC",
        "Verified": "Preverjeno",
        "You entered an invalid coupon code": "Vnesli ste neveljavno kodo kupona",
        "You entered an invalid gift card": "Vnesli ste neveljavno darilno kartico",
        "You might also like...": "Morda bi vam bilo všeč tudi...",
        "add": "dodaj",
        "and": "in",
        "coupon": "kupon",
        "privacy policy": "politika zasebnosti",
        "terms": "pogojev",
        "terms and conditions": "pogoji",
        "the": "the",
        "the store's": "v trgovini"
    },
    "sv-SE": {
        "(optional)": "(frivillig)",
        "+ ADD A COUPON CODE": "+ LÄGG TILL EN KUPONGKOD",
        "+ NEW CARD": "+ NYTT KORT",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ LÖS IN PRESENTKORT/BUTIKKREDIT",
        "After selecting pay you will be redirected to complete your payment.": "När du har valt betala kommer du att omdirigeras för att slutföra din betalning.",
        "Apartment": "Lägenhet",
        "Back to info": "Tillbaka till info",
        "Billing": "Fakturering",
        "By clicking the button above, you agree to": "Genom att klicka på knappen ovan godkänner du",
        "Cancel": "Avbryt",
        "Card": "Kort",
        "Cart is empty": "Varukorgen är tom",
        "City": "Stad",
        "Continue": "Fortsätta",
        "Country": "Land",
        "County": "Grevskap",
        "Coupon code": "Kupongskod",
        "Create a new password, or use an existing one if you already have an account for": "Skapa ett nytt lösenord eller använd ett befintligt om du redan har ett konto för",
        "Currency": "Valuta",
        "Delivery date": "Leveransdatum",
        "Edit": "Redigera",
        "Email": "E-post",
        "Exit Checkout": "Avsluta kassan",
        "First name": "Förnamn",
        "First renewal": "Första förnyelsen",
        "Gift card number": "Presentkortsnummer",
        "I verify that the country I have entered is the one I reside in": "Jag verifierar att det land jag har angett är det jag bor i",
        "Initial Shipment": "Första leverans",
        "Last name": "Efternamn",
        "My order": "Min order",
        "Order notes (optional)": "Beställningsanteckningar (valfritt)",
        "Order summary": "Ordersammanfattning",
        "Password": "Lösenord",
        "Pay": "Betala",
        "Payment": "Betalning",
        "Personal": "Personlig",
        "Phone number": "Telefonnummer",
        "Place order": "Beställa",
        "Please go back and try again. Missing required field": "Gå tillbaka och försök igen. Ett nödvändigt fält saknas",
        "Postal code": "Postnummer",
        "Processing": "Bearbetning",
        "Province": "Provins",
        "Ready to check out?": "Redo att checka ut?",
        "Recurring total": "Återkommande totalt",
        "Remove": "Ta bort",
        "Secured by": "Säkrad av",
        "Select a Province": "Välj en provins",
        "Select a State": "Välj en stat",
        "Select a country": "Välj ett land",
        "Send to": "Skicka till",
        "Shipping": "Frakt",
        "Sorry, something went wrong. Please refresh the page and try again.": "Förlåt, något gick fel. Uppdatera sidan och försök igen.",
        "Sorry, this store does not ship to your location.": "Tyvärr, denna butik skickar inte till din plats.",
        "State": "stat",
        "Street address": "Gatuadress",
        "Subtotal": "Delsumma",
        "Tax": "Beskatta",
        "Test mode: customers cannot see PeachPay": "Testläge: kunder kan inte se PeachPay",
        "The password entered must be at least 8 characters long.": "Lösenordet som anges måste vara minst 8 tecken långt.",
        "There are no eligible or active payment methods available for this order.": "Det finns inga kvalificerade eller aktiva betalningsmetoder tillgängliga för denna beställning.",
        "Total": "Total",
        "Unknown order error occurred": "Okänt beställningsfel uppstod",
        "VIEW SAVED CARDS": "VISA SPARADE KORT",
        "Verified": "Verifierad",
        "You entered an invalid coupon code": "Du angav en ogiltig kupongkod",
        "You entered an invalid gift card": "Du angav ett ogiltigt presentkort",
        "You might also like...": "Du kanske också gillar...",
        "add": "Lägg till",
        "and": "och",
        "coupon": "kupong",
        "privacy policy": "integritetspolicy",
        "terms": "villkor",
        "terms and conditions": "Villkor",
        "the": "de",
        "the store's": "affärerna"
    },
    "th": {
        "(optional)": "(ไม่จำเป็น)",
        "+ ADD A COUPON CODE": "+ เพิ่มรหัสคูปอง",
        "+ NEW CARD": "+ การ์ดใหม่",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ แลกบัตรของขวัญ/เครดิตร้านค้า",
        "After selecting pay you will be redirected to complete your payment.": "หลังจากเลือกชำระเงินแล้ว ระบบจะเปลี่ยนเส้นทางเพื่อชำระเงินให้เสร็จสิ้น",
        "Apartment": "อพาร์ทเม้น",
        "Back to info": "กลับไปที่ข้อมูล",
        "Billing": "การเรียกเก็บเงิน",
        "By clicking the button above, you agree to": "การคลิกปุ่มด้านบนแสดงว่าคุณยอมรับ",
        "Cancel": "ยกเลิก",
        "Card": "การ์ด",
        "Cart is empty": "รถเข็นว่างเปล่า",
        "City": "เมือง",
        "Continue": "ดำเนินการต่อ",
        "Country": "ประเทศ",
        "County": "เขต",
        "Coupon code": "รหัสคูปอง",
        "Create a new password, or use an existing one if you already have an account for": "สร้างรหัสผ่านใหม่หรือใช้รหัสผ่านที่มีอยู่หากคุณมีบัญชีอยู่แล้วสำหรับ",
        "Currency": "สกุลเงิน",
        "Delivery date": "วันที่จัดส่ง",
        "Edit": "แก้ไข",
        "Email": "อีเมล",
        "Exit Checkout": "ออกจากการชำระเงิน",
        "First name": "ชื่อจริง",
        "First renewal": "ต่ออายุครั้งแรก",
        "Gift card number": "หมายเลขบัตรของขวัญ",
        "I verify that the country I have entered is the one I reside in": "ฉันยืนยันว่าประเทศที่ฉันเข้ามาเป็นประเทศที่ฉันอาศัยอยู่",
        "Initial Shipment": "การจัดส่งครั้งแรก",
        "Last name": "นามสกุล",
        "My order": "คำสั่งของฉัน",
        "Order notes (optional)": "หมายเหตุการสั่งซื้อ (ไม่บังคับ)",
        "Order summary": "สรุปคำสั่งซื้อ",
        "Password": "รหัสผ่าน",
        "Pay": "จ่าย",
        "Payment": "การชำระเงิน",
        "Personal": "ส่วนตัว",
        "Phone number": "หมายเลขโทรศัพท์",
        "Place order": "สถานที่การสั่งซื้อ",
        "Please go back and try again. Missing required field": "โปรดกลับไปและลองอีกครั้ง ไม่มีช่องที่ต้องกรอก",
        "Postal code": "รหัสไปรษณีย์",
        "Processing": "กำลังประมวลผล",
        "Province": "จังหวัด",
        "Ready to check out?": "พร้อมที่จะเช็คเอาท์?",
        "Recurring total": "ยอดรวมที่เกิดซ้ำ",
        "Remove": "ลบ",
        "Secured by": "ปลอดภัยโดย",
        "Select a Province": "เลือกจังหวัด",
        "Select a State": "เลือกรัฐ",
        "Select a country": "เลือกประเทศ",
        "Send to": "ส่งถึง",
        "Shipping": "การส่งสินค้า",
        "Sorry, something went wrong. Please refresh the page and try again.": "ขอโทษมีบางอย่างผิดพลาด. โปรดรีเฟรชหน้าแล้วลองอีกครั้ง",
        "Sorry, this store does not ship to your location.": "ขออภัย ร้านค้านี้ไม่ได้จัดส่งไปยังตำแหน่งของคุณ",
        "State": "สถานะ",
        "Street address": "ที่อยู่ถนน",
        "Subtotal": "ยอดรวม",
        "Tax": "ภาษี",
        "Test mode: customers cannot see PeachPay": "โหมดทดสอบ: ลูกค้าไม่สามารถเห็น PeachPay",
        "The password entered must be at least 8 characters long.": "รหัสผ่านที่ป้อนต้องมีความยาวอย่างน้อย 8 ตัวอักษร",
        "There are no eligible or active payment methods available for this order.": "ไม่มีวิธีการชำระเงินที่มีสิทธิ์หรือใช้งานได้สำหรับคำสั่งซื้อนี้",
        "Total": "ทั้งหมด",
        "Unknown order error occurred": "เกิดข้อผิดพลาดในการสั่งซื้อที่ไม่รู้จัก",
        "VIEW SAVED CARDS": "ดูการ์ดที่บันทึกไว้",
        "Verified": "ตรวจสอบแล้ว",
        "You entered an invalid coupon code": "คุณป้อนรหัสคูปองไม่ถูกต้อง",
        "You entered an invalid gift card": "คุณป้อนบัตรของขวัญที่ไม่ถูกต้อง",
        "You might also like...": "คุณอาจชอบ...",
        "add": "เพิ่ม",
        "and": "และ",
        "coupon": "คูปอง",
        "privacy policy": "นโยบายความเป็นส่วนตัว",
        "terms": "เงื่อนไข",
        "terms and conditions": "ข้อกำหนดและเงื่อนไข",
        "the": "ที่",
        "the store's": "ของร้าน"
    },
    "uk": {
        "(optional)": "(необов'язково)",
        "+ ADD A COUPON CODE": "+ ДОДАТИ КОД КУПОНА",
        "+ NEW CARD": "+ НОВА КАРТКА",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ ВИКЛЮЧИТИ ПОДАРУНКУ КАРТКУ/КРЕДИТ МАГАЗИНУ",
        "After selecting pay you will be redirected to complete your payment.": "Після вибору оплати вас буде переспрямовано для завершення оплати.",
        "Apartment": "Квартира",
        "Back to info": "Повернутися до інформації",
        "Billing": "Виставлення рахунків",
        "By clicking the button above, you agree to": "Натискаючи кнопку вище, ви погоджуєтеся",
        "Cancel": "Скасувати",
        "Card": "Картка",
        "Cart is empty": "Кошик порожній",
        "City": "Місто",
        "Continue": "Продовжуйте",
        "Country": "Країна",
        "County": "повіт",
        "Coupon code": "Код купона",
        "Create a new password, or use an existing one if you already have an account for": "Створіть новий пароль або скористайтеся наявним, якщо у вас уже є обліковий запис",
        "Currency": "Валюта",
        "Delivery date": "Дата доставки",
        "Edit": "Редагувати",
        "Email": "Електронна пошта",
        "Exit Checkout": "Вийти з Checkout",
        "First name": "Ім'я",
        "First renewal": "Перше оновлення",
        "Gift card number": "Номер подарункової картки",
        "I verify that the country I have entered is the one I reside in": "Я підтверджую, що країна, в яку я ввійшов, є тією, в якій я проживаю",
        "Initial Shipment": "Початкове відправлення",
        "Last name": "Прізвище",
        "My order": "Моє замовлення",
        "Order notes (optional)": "Примітки до замовлення (необов’язково)",
        "Order summary": "Підсумок Замовлення",
        "Password": "Пароль",
        "Pay": "Платити",
        "Payment": "оплата",
        "Personal": "Особистий",
        "Phone number": "Номер телефону",
        "Place order": "Зробити замовлення",
        "Please go back and try again. Missing required field": "Будь ласка, поверніться та спробуйте ще раз. Відсутнє обов’язкове поле",
        "Postal code": "Поштовий індекс",
        "Processing": "Обробка",
        "Province": "провінція",
        "Ready to check out?": "Готові перевірити?",
        "Recurring total": "Повторюваний підсумок",
        "Remove": "Видалити",
        "Secured by": "Забезпечений",
        "Select a Province": "Виберіть провінцію",
        "Select a State": "Виберіть штат",
        "Select a country": "Виберіть країну",
        "Send to": "Відправити",
        "Shipping": "Доставка",
        "Sorry, something went wrong. Please refresh the page and try again.": "Вибачте, щось пішло не так. Оновіть сторінку та спробуйте ще раз.",
        "Sorry, this store does not ship to your location.": "На жаль, цей магазин не доставляє до вашого місцезнаходження.",
        "State": "держава",
        "Street address": "Адреса вулиці",
        "Subtotal": "Проміжний підсумок",
        "Tax": "податок",
        "Test mode: customers cannot see PeachPay": "Тестовий режим: клієнти не бачать PeachPay",
        "The password entered must be at least 8 characters long.": "Введений пароль має містити не менше 8 символів.",
        "There are no eligible or active payment methods available for this order.": "Для цього замовлення немає доступних або активних способів оплати.",
        "Total": "Всього",
        "Unknown order error occurred": "Сталася невідома помилка замовлення",
        "VIEW SAVED CARDS": "ПЕРЕГЛЯНУТИ ЗБЕРЕЖЕНІ КАРТКИ",
        "Verified": "Перевірено",
        "You entered an invalid coupon code": "Ви ввели недійсний код купона",
        "You entered an invalid gift card": "Ви ввели недійсну подарункову картку",
        "You might also like...": "Вам також може сподобатися...",
        "add": "додати",
        "and": "і",
        "coupon": "купон",
        "privacy policy": "політика конфіденційності",
        "terms": "терміни",
        "terms and conditions": "правила та умови",
        "the": "в",
        "the store's": "магазину"
    },
    "zh-CN": {
        "(optional)": "（选修的）",
        "+ ADD A COUPON CODE": "+ 添加优惠券代码",
        "+ NEW CARD": "+ 新卡",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ 兑换礼品卡/商店信用卡",
        "After selecting pay you will be redirected to complete your payment.": "选择付款后，您将被重定向以完成付款。",
        "Apartment": "公寓",
        "Back to info": "返回信息",
        "Billing": "计费",
        "By clicking the button above, you agree to": "点击上方按钮，即表示您同意",
        "Cancel": "取消",
        "Card": "卡片",
        "Cart is empty": "购物车是空的",
        "City": "城市",
        "Continue": "继续",
        "Country": "国家",
        "County": "县",
        "Coupon code": "优惠券代码",
        "Create a new password, or use an existing one if you already have an account for": "创建一个新密码，如果您已经有一个帐户，则使用现有密码",
        "Currency": "货币",
        "Delivery date": "邮寄日期",
        "Edit": "编辑",
        "Email": "电子邮件",
        "Exit Checkout": "退出结帐",
        "First name": "名",
        "First renewal": "第一次更新",
        "Gift card number": "礼品卡号",
        "I verify that the country I have entered is the one I reside in": "我确认我输入的国家是我居住的国家",
        "Initial Shipment": "初始装运",
        "Last name": "姓",
        "My order": "我的订单",
        "Order notes (optional)": "订单备注（可选）",
        "Order summary": "订单摘要",
        "Password": "密码",
        "Pay": "支付",
        "Payment": "支付",
        "Personal": "个人的",
        "Phone number": "电话号码",
        "Place order": "下订单",
        "Please go back and try again. Missing required field": "请返回重试。缺少必填字段",
        "Postal code": "邮政编码",
        "Processing": "加工",
        "Province": "省",
        "Ready to check out?": "准备好退房了吗？",
        "Recurring total": "经常性总计",
        "Remove": "消除",
        "Secured by": "担保人",
        "Select a Province": "选择一个省份",
        "Select a State": "选择一个州",
        "Select a country": "选择一个国家",
        "Send to": "发送至",
        "Shipping": "船运",
        "Sorry, something went wrong. Please refresh the page and try again.": "抱歉，出了一些问题。请刷新页面并重试。",
        "Sorry, this store does not ship to your location.": "对不起，这家商店不送货到您的位置。",
        "State": "状态",
        "Street address": "街道地址",
        "Subtotal": "小计",
        "Tax": "税",
        "Test mode: customers cannot see PeachPay": "测试模式：客户看不到PeachPay",
        "The password entered must be at least 8 characters long.": "输入的密码长度必须至少为 8 个字符。",
        "There are no eligible or active payment methods available for this order.": "此订单没有可用的合格或有效付款方式。",
        "Total": "全部的",
        "Unknown order error occurred": "发生未知订单错误",
        "VIEW SAVED CARDS": "查看保存的卡片",
        "Verified": "已验证",
        "You entered an invalid coupon code": "您输入的优惠券代码无效",
        "You entered an invalid gift card": "您输入的礼品卡无效",
        "You might also like...": "你可能还喜欢...",
        "add": "添加",
        "and": "和",
        "coupon": "优惠券",
        "privacy policy": "隐私政策",
        "terms": "条款",
        "terms and conditions": "条款和条件",
        "the": "这",
        "the store's": "商店的"
    },
    "zh-TW": {
        "(optional)": "（選修的）",
        "+ ADD A COUPON CODE": "+ 添加優惠券代碼",
        "+ NEW CARD": "+ 新卡",
        "+ REDEEM GIFT CARD/STORE CREDIT": "+ 兌換禮品卡/商店信用卡",
        "After selecting pay you will be redirected to complete your payment.": "選擇付款後，您將被重定向以完成付款。",
        "Apartment": "公寓",
        "Back to info": "返回信息",
        "Billing": "計費",
        "By clicking the button above, you agree to": "點擊上方按鈕，即表示您同意",
        "Cancel": "取消",
        "Card": "卡片",
        "Cart is empty": "購物車是空的",
        "City": "城市",
        "Continue": "繼續",
        "Country": "國家",
        "County": "縣",
        "Coupon code": "優惠券代碼",
        "Create a new password, or use an existing one if you already have an account for": "創建一個新密碼，如果您已經有一個帳戶，則使用現有密碼",
        "Currency": "貨幣",
        "Delivery date": "郵寄日期",
        "Edit": "編輯",
        "Email": "電子郵件",
        "Exit Checkout": "退出結帳",
        "First name": "名",
        "First renewal": "第一次更新",
        "Gift card number": "禮品卡號",
        "I verify that the country I have entered is the one I reside in": "我確認我輸入的國家是我居住的國家",
        "Initial Shipment": "初始裝運",
        "Last name": "姓",
        "My order": "我的訂單",
        "Order notes (optional)": "訂單備註（可選）",
        "Order summary": "訂單摘要",
        "Password": "密碼",
        "Pay": "支付",
        "Payment": "支付",
        "Personal": "個人的",
        "Phone number": "電話號碼",
        "Place order": "下訂單",
        "Please go back and try again. Missing required field": "請返回重試。缺少必填字段",
        "Postal code": "郵政編碼",
        "Processing": "加工",
        "Province": "省",
        "Ready to check out?": "準備好退房了嗎？",
        "Recurring total": "經常性總計",
        "Remove": "消除",
        "Secured by": "擔保人",
        "Select a Province": "選擇一個省份",
        "Select a State": "選擇一個州",
        "Select a country": "選擇一個國家",
        "Send to": "發送至",
        "Shipping": "船運",
        "Sorry, something went wrong. Please refresh the page and try again.": "抱歉，出了一些問題。請刷新頁面並重試。",
        "Sorry, this store does not ship to your location.": "對不起，這家商店不送貨到您的位置。",
        "State": "狀態",
        "Street address": "街道地址",
        "Subtotal": "小計",
        "Tax": "稅",
        "Test mode: customers cannot see PeachPay": "測試模式：客戶看不到PeachPay",
        "The password entered must be at least 8 characters long.": "輸入的密碼長度必須至少為 8 個字符。",
        "There are no eligible or active payment methods available for this order.": "此訂單沒有可用的合格或有效付款方式。",
        "Total": "全部的",
        "Unknown order error occurred": "發生未知訂單錯誤",
        "VIEW SAVED CARDS": "查看保存的卡片",
        "Verified": "已驗證",
        "You entered an invalid coupon code": "您輸入的優惠券代碼無效",
        "You entered an invalid gift card": "您輸入的禮品卡無效",
        "You might also like...": "你可能還喜歡...",
        "add": "添加",
        "and": "和",
        "coupon": "優惠券",
        "privacy policy": "隱私政策",
        "terms": "條款",
        "terms and conditions": "條款和條件",
        "the": "這",
        "the store's": "商店的"
    }
};
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
        case DispatchActionType.PEACHPAY_CUSTOMER_FIELDS:
            return __assign(__assign({}, state), { form_fields: __assign({}, action.payload) });
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
            return __assign(__assign({}, state), { form_fields: __assign(__assign({}, state.form_fields), { shipping_country: action.payload.country, shipping_state: action.payload.state, shipping_city: action.payload.city, shipping_postcode: action.payload.postcode }) });
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
        case DispatchActionType.PAYMENT_INITILIZE_UI:
            return __assign(__assign({}, state), { ui: {
                    primaryMethods: __spreadArray([], action.payload.primaryMethods, true)
                } });
        case DispatchActionType.PAYMENT_SWAP_PRIMARY_WITH_SECONDARY:
            {
                var payload = action.payload;
                var existingPrimary = state.ui.primaryMethods;
                return __assign(__assign({}, state), { ui: {
                        primaryMethods: [
                            existingPrimary[0],
                            existingPrimary[1],
                            payload
                        ]
                    } });
            }
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
    var wpLocale = Environment.language();
    if (wpLocale == 'en-US' || !peachpayi18n[wpLocale] || !peachpayi18n[wpLocale][key]) {
        return key;
    }
    return peachpayi18n[wpLocale][key];
}
function initAdditionalFields(message) {
    var _a, _b, _c, _d;
    renderAdditionalFields((_b = (_a = message.phpData) === null || _a === void 0 ? void 0 : _a.additional_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.additional_fields_order) !== null && _d !== void 0 ? _d : []);
}
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
    FeatureFlag1["STRIPE"] = 'stripe_payment_method';
    FeatureFlag1["STRIPE_PAYMENT_REQUEST"] = 'stripe_payment_request';
    FeatureFlag1["PAYPAL"] = 'paypal_payment_method';
    FeatureFlag1["CURRENCY_SWITCHER_INPUT"] = 'currency_switcher_input';
    FeatureFlag1["ONE_CLICK_UPSELL"] = 'peachpay_ocu';
    FeatureFlag1["RELATED_PRODUCTS"] = 'related_products';
    FeatureFlag1["QUANTITY_CHANGER"] = 'display_quantity_changer';
    FeatureFlag1["PRODUCT_IMAGES"] = 'display_product_images';
    FeatureFlag1["FIELD_EDITOR"] = 'enable_field_editor';
})(FeatureFlag || (FeatureFlag = {}));
function initOrderNotes() {
    if (Feature.enabled(FeatureFlag.ORDER_NOTES_INPUT)) {
        for (var _i = 0, _a = $qsAll('.order-notes'); _i < _a.length; _i++) {
            var $form = _a[_i];
            $form.classList.remove('hide');
        }
    }
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
        totalAppliedFees: function () { var _a, _b; return Object.entries((_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.fees_record) !== null && _b !== void 0 ? _b : {}).reduce(function (previousValue, _a) {
            var _ = _a[0], value = _a[1];
            return previousValue + (value !== null && value !== void 0 ? value : 0);
        }, 0); },
        couponTotal: function (coupon) { var _a, _b; return (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.coupons_record[coupon]) !== null && _b !== void 0 ? _b : 0; },
        totalAppliedCoupons: function () { var _a, _b; return Object.entries((_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.coupons_record) !== null && _b !== void 0 ? _b : {}).reduce(function (previousValue, _a) {
            var _ = _a[0], value = _a[1];
            return previousValue + (value !== null && value !== void 0 ? value : 0);
        }, 0); },
        couponRecord: function () { var _a; return (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.coupons_record; },
        giftCardTotal: function (giftCard) { var _a, _b, _c; return (_c = (_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.gift_card_record) === null || _b === void 0 ? void 0 : _b[giftCard]) !== null && _c !== void 0 ? _c : 0; },
        totalAppliedGiftCards: function () { var _a, _b; return Object.entries((_b = (_a = store.getState().calculatedCarts[cartKey]) === null || _a === void 0 ? void 0 : _a.summary.gift_card_record) !== null && _b !== void 0 ? _b : {}).reduce(function (previousValue, _a) {
            var _ = _a[0], value = _a[1];
            return previousValue + (value !== null && value !== void 0 ? value : 0);
        }, 0); },
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
            key: getLocaleText('Subtotal'),
            value: calculatedCart.summary.subtotal
        });
        for (var _i = 0, _a = Object.entries(calculatedCart.summary.coupons_record); _i < _a.length; _i++) {
            var _b = _a[_i], coupon = _b[0], amount = _b[1];
            if (!amount) {
                continue;
            }
            cartSummary.push({
                key: "".concat(getLocaleText('coupon'), " - (").concat(coupon, ") <button class=\"pp-coupon-remove-button\" data-coupon=\"").concat(coupon, "\" type=\"button\" tabindex=\"0\">[&times;]</button>"),
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
                key: getLocaleText('Shipping'),
                value: calculatedCart.summary.total_shipping
            });
        }
        if (MerchantConfiguration.tax.displayMode() === 'excludeTax') {
            cartSummary.push({
                key: getLocaleText('Tax'),
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
            key: getLocaleText('Total'),
            value: calculatedCart.summary.total
        });
        return {
            cartSummary: cartSummary,
            cartMeta: cartMeta
        };
    };
}
var setSessionId = createDispatchUpdate(DispatchActionType.ORDER_SESSION_ID);
var updateCustomerAddressValidation = createDispatchUpdate(DispatchActionType.ORDER_ADDRESS_VALIDATED);
var setExtraFields = createDispatchUpdate(DispatchActionType.ORDER_SET_EXTRA_FIELDS);
var setOrderError = createDispatchUpdate(DispatchActionType.ORDER_SET_ERROR_MESSAGE);
var updateCustomerStripeId = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_STRIPE_ID);
var updateCustomerFields = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_FIELDS);
var updateCustomer = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER);
var updateCustomerShippingShortAddress = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_SHIPPING);
var removeSavedPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_REMOVE_PAYMENT_METHOD);
var updateCustomerPreferredPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_SET_PREFERRED_PAYMENT_METHOD);
var addSavedPaymentMethod = createDispatchUpdate(DispatchActionType.PEACHPAY_CUSTOMER_ADD_PAYMENT_METHOD);
var PeachPayCustomer = {
    data: function () { return store.getState().peachPayCustomer; },
    email: function () { return store.getState().peachPayCustomer.form_fields.shipping_email; },
    firstName: function () { return store.getState().peachPayCustomer.form_fields.shipping_first_name; },
    lastName: function () { return store.getState().peachPayCustomer.form_fields.shipping_last_name; },
    phone: function () { return store.getState().peachPayCustomer.form_fields.shipping_phone; },
    company: function () { return store.getState().peachPayCustomer.form_fields.shipping_company; },
    address1: function () { return store.getState().peachPayCustomer.form_fields.shipping_address_1; },
    address2: function () { return store.getState().peachPayCustomer.form_fields.shipping_address_2; },
    city: function () { return store.getState().peachPayCustomer.form_fields.shipping_city; },
    state: function () { return store.getState().peachPayCustomer.form_fields.shipping_state; },
    country: function () { return store.getState().peachPayCustomer.form_fields.shipping_country; },
    postal: function () { return store.getState().peachPayCustomer.form_fields.shipping_postcode; },
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
        shipping_company: PeachPayCustomer.company(),
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
        billing_company: PeachPayCustomer.company(),
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
function collectAdditionalFieldData(fieldData, fieldOrder) {
    var _a, _b, _c, _d, _e, _f;
    var fieldDataRecord = {};
    for (var _i = 0, fieldOrder_1 = fieldOrder; _i < fieldOrder_1.length; _i++) {
        var orderNumber = fieldOrder_1[_i];
        var temporaryData = {
            name: ''
        };
        temporaryData.label = fieldData[orderNumber].field_label;
        temporaryData.name = fieldData[orderNumber].field_name;
        if (fieldData[orderNumber].field_enable && ((_a = $qs("#".concat(fieldData[orderNumber].field_name, "-existing"))) === null || _a === void 0 ? void 0 : _a.value)) {
            temporaryData.value = (_b = $qs("#".concat(fieldData[orderNumber].field_name, "-existing"))) === null || _b === void 0 ? void 0 : _b.value;
            fieldDataRecord[temporaryData.name] = (_c = temporaryData.value) !== null && _c !== void 0 ? _c : '';
        }
        if (fieldData[orderNumber].type_list === 'radio' && fieldData[orderNumber].field_enable) {
            if ((_d = $qs("input[name=".concat(fieldData[orderNumber].field_name, "]:checked"))) === null || _d === void 0 ? void 0 : _d.value) {
                temporaryData.value = (_e = $qs("input[name=".concat(fieldData[orderNumber].field_name, "]:checked"))) === null || _e === void 0 ? void 0 : _e.value;
                fieldDataRecord[temporaryData.name] = (_f = temporaryData.value) !== null && _f !== void 0 ? _f : '';
            }
        }
    }
    return fieldDataRecord;
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
    orderFormRecord: function () {
        var _a, _b, _c, _d;
        var formInfoRecord = __assign(__assign(__assign(__assign({}, PeachPayCustomer.data().form_fields), PeachPayCustomer.wcBillingAddress()), PeachPayCustomer.wcShippingAddress()), collectAdditionalFieldData((_b = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.additional_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.additional_fields_order) !== null && _d !== void 0 ? _d : []));
        for (var _i = 0, _e = Object.entries(PeachPayOrder.collectSelectedShipping()); _i < _e.length; _i++) {
            var _f = _e[_i], packageKey = _f[0], selectedOption = _f[1];
            formInfoRecord["shipping_method[".concat(packageKey, "]")] = selectedOption;
        }
        formInfoRecord['order_comments'] = collectOrderNotes();
        return formInfoRecord;
    },
    customerAddressValidated: function () { return store.getState().peachPayOrder.customerAddressValidated; },
    extraFieldsRecord: function () { return store.getState().peachPayOrder.additionalFields; }
};
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
    generateFields(fieldData, fieldOrder);
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
function generateFields(fieldData, fieldOrder) {
    var row = function (location) {
        var completeRow = '';
        var i = 0;
        var rowSpace = 100;
        var rowElement = '';
        while (fieldData[fieldOrder[i]]) {
            if (fieldData[fieldOrder[i]].type_list === 'header') {
                if (rowElement.length !== 0) {
                    rowElement += '</div>';
                    completeRow += rowElement;
                    rowSpace = 100;
                    rowElement = '';
                }
                completeRow += "<h2 id=\"".concat(fieldData[fieldOrder[i]].field_name, "\" class=\"pp-additional-label ").concat(fieldData[fieldOrder[i]].field_name, "\"><span class=\"bold\">").concat(fieldData[fieldOrder[i]].field_label, "</span></h2>");
                i++;
                continue;
            }
            if (rowSpace < fieldData[fieldOrder[i]].width) {
                rowElement += '</div>';
                completeRow += rowElement;
                rowSpace = 100;
                rowElement = '';
            }
            if (rowSpace >= fieldData[fieldOrder[i]].width) {
                if (rowElement.length === 0) {
                    rowElement = '<div class="flex">';
                }
                rowElement += field(location, fieldData[fieldOrder[i]]);
                rowSpace -= fieldData[fieldOrder[i]].width;
                i++;
            }
        }
        if (rowElement.length !== 0) {
            rowElement += '</div>';
            completeRow += rowElement;
        }
        completeRow += '</form>';
        return completeRow;
    };
    var field = function (location, fieldDataSingle) { return '<div class="pp-new-field' + (fieldDataSingle.type_list === 'radio' ? ' flex-col' : ' flex ') + "w-".concat(fieldDataSingle.width, "\">") + generateFieldElement(location, fieldDataSingle) + '</div>'; };
    var newPageElements = document.querySelector('#additional-fields-new');
    var existPageElement = document.querySelector('#additional-fields-existing');
    if (newPageElements) {
        newPageElements.innerHTML += row('-new');
    }
    if (existPageElement) {
        existPageElement.innerHTML += row('-existing');
    }
}
function generateFieldElement(location1, fieldData) {
    var _a, _b;
    var elementString = '';
    var optional = "<span class=\"optional\"> ".concat(getLocaleText('(optional)'), " </span>");
    var required = '<abbr class="required" title="required" style="color:red;">*</abbr>';
    var labelBuilder = function (location) { return "\n\t\t<label for=\"".concat(fieldData.field_name).concat(location, "\" class=\"pp-form-label-").concat(fieldData.type_list) + (+fieldData.width === 30 ? ' pp-w30-label' : '') + "\">" + "".concat(fieldData.field_label) + (fieldData.field_required ? required : optional) + '</label>'; };
    var inputBuilder = function (location) { return "<input type=".concat(fieldData.type_list, "\n\t\t\tname=").concat(fieldData.field_name, " \n\t\t\tid=\"").concat(fieldData.field_name).concat(location, "\"\n\t\t\tplaceholder=\" \"") + (fieldData.field_default ? "value=\"".concat(fieldData.field_default, "\"") : '') + "class=\"pp-input-box-".concat(fieldData.type_list) + (location === '-new' ? ' new-text' : '') + " w-100 extra-field\"" + (fieldData.field_required ? 'required' : '') + '/>'; };
    var selectBuilder = function (location, optionOrder) { return "\n\t\t<select name=".concat(fieldData.field_name, " \n\t\tid=\"").concat(fieldData.field_name).concat(location, "\"\n\t\tclass=\"pp-").concat(fieldData.type_list, "-box") + (location === '-new' ? ' new-text' : '') + " w-100 extra-field\"" + (fieldData.field_required ? 'required' : '') + ">" + optionBuilder(optionOrder) + "</select>" + labelBuilder(location); };
    var optionBuilder = function (optionOrder) {
        if (optionOrder.length === 0) {
            return;
        }
        var optionList = '<option value="">Please Select</option>';
        optionOrder.forEach(function (value) {
            if (value[0] && value[1]) {
                optionList += "<option value=\"".concat(value[0], "\">").concat(value[1].replaceAll('\\"', '"').replaceAll("\\'", "'"), "</option>");
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
                radioFields += "<label for=\"".concat(fieldData.field_name).concat(location, "-").concat(value[0], "\">").concat(value[1].replaceAll('\\"', '"').replaceAll("\\'", "'"), "</label><br>");
            }
        });
        radioFields += '</div></div>';
        return radioFields;
    };
    if (fieldData.type_list === 'text' || fieldData.type_list === 'email' || fieldData.type_list === 'tel') {
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
function checkRequiredFields() {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j;
    var infoFormValidity = (_b = (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.checkValidity()) !== null && _b !== void 0 ? _b : false;
    if (!infoFormValidity) {
        store.dispatch(updateEnvironment({
            modalPageType: 'info',
            customerExists: false
        }));
        (_c = $qs('#pp-info-form')) === null || _c === void 0 ? void 0 : _c.reportValidity();
        return false;
    }
    if (Environment.customer.existing()) {
        (_d = $qs('#additional-fields-existing')) === null || _d === void 0 ? void 0 : _d.reportValidity();
        return (_f = (_e = $qs('#additional-fields-existing')) === null || _e === void 0 ? void 0 : _e.checkValidity()) !== null && _f !== void 0 ? _f : false;
    }
    (_g = $qs('#additional-fields-new')) === null || _g === void 0 ? void 0 : _g.reportValidity();
    return (_j = (_h = $qs('#additional-fields-new')) === null || _h === void 0 ? void 0 : _h.checkValidity()) !== null && _j !== void 0 ? _j : false;
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
var setPaymentMethod = createCustomDispatchUpdate(DispatchActionType.PAYMENT_SET_METHOD, function (input) {
    if (input.index) {
        return input.provider + ':' + input.method + ':' + input.index;
    }
    else {
        return input.provider + ':' + input.method;
    }
});
var registerPaymentProvider = createDispatchUpdate(DispatchActionType.PAYMENT_REGISTER_PROVIDER);
var initilizePrimaryPaymentMethodUI = createCustomDispatchUpdate(DispatchActionType.PAYMENT_INITILIZE_UI, function () {
    var _a, _b, _c;
    var primaryMethods = [];
    var eligibleMethods = PaymentConfiguration.allEligibleMethods();
    var cardIndex = eligibleMethods.findIndex(function (pm) { return pm.provider === 'stripe' && pm.method === 'card'; });
    if (cardIndex !== -1) {
        primaryMethods.push({
            provider: 'stripe',
            method: 'card'
        });
        eligibleMethods.splice(cardIndex, 1);
    }
    var paypalIndex = eligibleMethods.findIndex(function (pm) { return pm.provider === 'paypal' && pm.method === 'default'; });
    if (paypalIndex !== -1) {
        primaryMethods.push({
            provider: 'paypal',
            method: 'default'
        });
        eligibleMethods.splice(paypalIndex, 1);
    }
    var selectedIndex = eligibleMethods.findIndex(function (pm) { return PaymentConfiguration.selectedProvider() === pm.provider && PaymentConfiguration.selectedProviderMethod() === pm.method; });
    if (selectedIndex !== -1) {
        primaryMethods.push({
            provider: PaymentConfiguration.selectedProvider(),
            method: PaymentConfiguration.selectedProviderMethod()
        });
        eligibleMethods.splice(selectedIndex, 1);
    }
    for (var _i = 0, eligibleMethods_1 = eligibleMethods; _i < eligibleMethods_1.length; _i++) {
        var pm1 = eligibleMethods_1[_i];
        if (primaryMethods.length > 2) {
            break;
        }
        primaryMethods.push({
            provider: pm1.provider,
            method: pm1.method
        });
    }
    return {
        primaryMethods: [
            (_a = primaryMethods[0]) !== null && _a !== void 0 ? _a : undefined,
            (_b = primaryMethods[1]) !== null && _b !== void 0 ? _b : undefined,
            (_c = primaryMethods[2]) !== null && _c !== void 0 ? _c : undefined
        ]
    };
});
var swapPrimaryWithSecondary = createDispatchUpdate(DispatchActionType.PAYMENT_SWAP_PRIMARY_WITH_SECONDARY);
var PaymentConfiguration = {
    data: function () { return store.getState().paymentConfiguration; },
    selectedPaymentMethod: function () { return store.getState().paymentConfiguration.selectedPaymentMethod; },
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
    isPrimaryMethod: function (providerKey, methodKey) {
        var slot1 = store.getState().paymentConfiguration.ui.primaryMethods[0];
        if (slot1 && slot1.provider === providerKey && slot1.method === methodKey) {
            return true;
        }
        var slot2 = store.getState().paymentConfiguration.ui.primaryMethods[1];
        if (slot2 && slot2.provider === providerKey && slot2.method === methodKey) {
            return true;
        }
        var slot3 = store.getState().paymentConfiguration.ui.primaryMethods[2];
        if (slot3 && slot3.provider === providerKey && slot3.method === methodKey) {
            return true;
        }
        return false;
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
    },
    allEligibleMethods: function () {
        var eligibleMethods = [];
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
                    eligibleMethods.push({
                        provider: providerKey,
                        method: methodKey,
                        config: method
                    });
                }
            }
        }
        return eligibleMethods;
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
            switch (numberOfDecimals) {
                case 0:
                    cost = Math.ceil(cost);
                    break;
                case 1:
                    cost = Math.ceil(cost * 10) / 10;
                    break;
                case 2:
                    cost = Math.ceil(cost * 100) / 100;
                    break;
                case 3:
                    cost = Math.ceil(cost * 1000) / 1000;
                    break;
                default:
                    cost = Math.ceil(cost);
                    break;
            }
            break;
        case 'down':
            switch (numberOfDecimals) {
                case 0:
                    cost = Math.floor(cost);
                    break;
                case 1:
                    cost = Math.floor(cost * 10) / 10;
                    break;
                case 2:
                    cost = Math.floor(cost * 100) / 100;
                    break;
                case 3:
                    cost = Math.floor(cost * 1000) / 1000;
                    break;
                default:
                    cost = Math.floor(cost);
                    break;
            }
            break;
        case 'nearest':
            switch (numberOfDecimals) {
                case 0:
                    cost = Math.round(cost);
                    break;
                case 1:
                    cost = Math.round(cost * 10) / 10;
                    break;
                case 2:
                    cost = Math.round(cost * 100) / 100;
                    break;
                case 3:
                    cost = Math.round(cost * 1000) / 1000;
                    break;
                default:
                    cost = Math.round(cost);
                    break;
            }
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
            return [
                getLocaleText('Select a State'),
                getLocaleText('State')
            ];
        case 'GB':
            return [
                getLocaleText('County'),
                null
            ];
        default:
            return [
                getLocaleText('Select a Province'),
                getLocaleText('Province')
            ];
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
                    return [4, fetchWindowData(iFrameWindow, 'pp-get-existing-customer-data', 2)];
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
function initShippingForm(message) {
    var _a, _b, _c, _d;
    return formGenerator((_b = (_a = message.phpData) === null || _a === void 0 ? void 0 : _a.shipping_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.shipping_fields_order) !== null && _d !== void 0 ? _d : [], 'shipping');
}
function formGenerator(fieldData, fieldOrder, section) {
    var form = "<form id=\"pp-info-form\">";
    var i = 0;
    var rowSpace = 100;
    var rowElement = '';
    var labelNameTranslations = {
        'Shipping': getLocaleText('Shipping'),
        'Billing': getLocaleText('Billing'),
        'Personal': getLocaleText('Personal')
    };
    while (fieldData[fieldOrder[i]]) {
        var label = fieldData[fieldOrder[i]].field_label in labelNameTranslations ? labelNameTranslations[fieldData[fieldOrder[i]].field_label] : fieldData[fieldOrder[i]].field_label;
        if (fieldData[fieldOrder[i]].type_list === 'header') {
            if (rowElement.length !== 0) {
                rowElement += '</div>';
                form += rowElement;
                rowSpace = 100;
                rowElement = '';
            }
            form += "<h2 id=\"".concat(fieldData[fieldOrder[i]].field_name, "-field\" class=\"").concat(fieldData[fieldOrder[i]].field_name, "\"><span class=\"bold\">").concat(label, "</span></h2>");
            i++;
            continue;
        }
        if (rowSpace < fieldData[fieldOrder[i]].width) {
            rowElement += '</div>';
            form += rowElement;
            rowSpace = 100;
            rowElement = '';
        }
        if (rowSpace >= fieldData[fieldOrder[i]].width) {
            if (rowElement.length === 0) {
                rowElement = '<div class="flex">';
            }
            rowElement += generateFieldElement1(fieldData[fieldOrder[i]], section);
            rowSpace -= fieldData[fieldOrder[i]].width;
            i++;
        }
    }
    if (rowElement.length !== 0) {
        rowElement += '</div>';
        form += rowElement;
    }
    form += '</form>';
    return form;
}
function generateFieldElement1(fieldData, section) {
    var _a, _b;
    var elementString = "<div id=\"".concat(fieldData.field_name, "-field\" class=\"") + (fieldData.type_list === 'radio' ? ' flex-col' : ' flex') + " w-".concat(fieldData.width, "\">");
    var optional = '<span class="optional"> (optional) </span>';
    var labelNameTranslations = {
        'Street address': getLocaleText('Street address'),
        'Apartment': getLocaleText('Apartment'),
        'City': getLocaleText('City'),
        'Country': getLocaleText('Country'),
        'Email': getLocaleText('Email'),
        'First name': getLocaleText('First name'),
        'Last name': getLocaleText('Last name'),
        'Phone number': getLocaleText('Phone number'),
        'Postal code': getLocaleText('Postal code'),
        'State': getLocaleText('State')
    };
    var label = fieldData.field_label in labelNameTranslations ? labelNameTranslations[fieldData.field_label] : fieldData.field_label;
    var labelBuilder = function () { return "<label for=\"".concat(fieldData.field_name, "\" \n\t\tclass=\"") + (+fieldData.width !== 30 ? 'form-label' : 'pp-w30-label form-label') + (fieldData.type_list === 'select' ? ' pp-select-label' : '') + (fieldData.type_list === 'radio' ? ' pp-radio-label' : '') + "\">" + "".concat(label) + (!fieldData.field_required && !isDefaultField(fieldData.field_name, section) ? optional : '') + '</label>'; };
    var inputBuilder = function () { return "<input type=\"".concat(fieldData.type_list, "\"\n\t\t\tname=\"").concat(fieldData.field_name, "\"\n\t\t\tid=\"").concat(fieldData.field_name, "\"\n\t\t\tplaceholder=\" \"") + (fieldData.field_default ? "value=\"".concat(fieldData.field_default.replaceAll('"', '&quot;'), "\"") : '') + "class=\"w-100 text-input\"" + (fieldData.field_required ? ' required' : '') + '/>'; };
    var selectBuilder = function (optionOrder) { return "\n\t\t<select name=".concat(fieldData.field_name, "\n\t\tid=\"").concat(fieldData.field_name, "\"\n\t\tclass=\"w-100\"") + (fieldData.field_required ? 'required' : '') + ">" + optionBuilder(optionOrder) + "</select>" + labelBuilder(); };
    var optionBuilder = function (optionOrder) {
        if (optionOrder.length === 0) {
            return;
        }
        var optionList = '<option value="">Please Select</option>';
        optionOrder.forEach(function (value) {
            if (value[0] && value[1]) {
                optionList += "<option value=\"".concat(value[0], "\">").concat(value[1].replaceAll('\\"', '"').replaceAll("\\'", "'"), "</option>");
            }
        });
        return optionList;
    };
    var radioFieldBuilder = function (optionOrder) {
        var radioFields = "<div class=\"pp-radio-field\"><div>" + (fieldData.field_label ? labelBuilder() : '') + "</div>";
        radioFields += '<div class="pp-radio-option-container">';
        var first = true;
        optionOrder.forEach(function (value) {
            if (value[0] && value[1]) {
                radioFields += "<label class=\"pp-radio-option\"><input type=".concat(fieldData.type_list, " \n\t\t\t\tname=").concat(fieldData.field_name, " \n\t\t\t\tid=\"").concat(fieldData.field_name, "-").concat(value[0], "\"") + "value=\"".concat(value[0], "\"") + "class=\"pp-radio-input\"" + (fieldData.field_required && first ? 'required' : '') + '/>';
                radioFields += "<span class=\"radio-option-label\">".concat(value[1].replaceAll('\\"', '"').replaceAll("\\'", "'"), "</span></label>");
                if (first) {
                    first = false;
                }
            }
        });
        radioFields += '</div></div>';
        return radioFields;
    };
    var stateRegionBuilder = function () { return "\n\t\t<input id=\"province\" class=\"w-100 text-input\" type=\"text\" name=\"off\" placeholder=\" \">\n\t\t<label for=\"province\" class=\"form-label\" data-i18n=\"Province\"></label>\n\t\t<select id=\"dynamic-states\" class=\"w-100 select hide\" name=\"shipping_state\" size=\"1\">\n\t\t\t<option hidden disabled selected value></option>\n\t\t</select>\n\t\t<label for=\"dynamic-states\" class=\"form-label pp-select-label hide\" data-i18n=\"State\"></label>\n\t\t"; };
    var countryFieldBuilder = function () { return "\n\t\t<select id=\"country\" class=\"w-100\" name=\"shipping_country\" size=\"1\" required>\n\t\t\t<option hidden disabled selected value data-i18n=\"Select a country\"></option>\n\t\t</select>\n\t\t<label for=\"country\" data-i18n=\"Country\" class=\"form-label pp-select-label\"></label>\n\t"; };
    if (fieldData.type_list === 'text' || fieldData.type_list === 'email' || fieldData.type_list === 'tel') {
        elementString += inputBuilder() + (fieldData.field_label ? labelBuilder() : '');
        elementString += '</div>';
        return elementString;
    }
    else if (fieldData.type_list === 'select') {
        elementString += selectBuilder((_a = fieldData.option_order) !== null && _a !== void 0 ? _a : []);
        elementString += '</div>';
        return elementString;
    }
    else if (fieldData.type_list === 'radio') {
        elementString += radioFieldBuilder((_b = fieldData.option_order) !== null && _b !== void 0 ? _b : []);
        elementString += '</div>';
        return elementString;
    }
    else if (fieldData.type_list === 'country') {
        elementString += countryFieldBuilder() + '</div>';
        return elementString;
    }
    else if (fieldData.type_list === 'state') {
        elementString += stateRegionBuilder() + '</div>';
        return elementString;
    }
    elementString += '</div>';
    return elementString;
}
function isDefaultField(name, section) {
    var defaultFieldNames = [
        section + '_email',
        section + '_phone',
        section + '_first_name',
        section + '_last_name',
        section + '_company',
        section + '_address_1',
        section + '_address_2',
        section + '_postcode',
        section + '_city',
        section + '_state',
        section + '_country',
    ];
    return defaultFieldNames.includes(name);
}
function installCustomerFormFields(message) {
    var _a;
    var form = initShippingForm(message);
    (_a = $qs('#pp-info')) === null || _a === void 0 ? void 0 : _a.insertAdjacentHTML('beforeend', form);
}
function initCustomer(message) {
    initCustomerEvents(message);
    renderCountryAndStateList(message.phpData.wc_location_info);
}
function initCustomerEvents(message) {
    var _a, _b;
    (_a = $qs('#country')) === null || _a === void 0 ? void 0 : _a.addEventListener('change', renderCorrectProvinceField);
    (_b = $qs('#pp-info-form')) === null || _b === void 0 ? void 0 : _b.addEventListener('change', function () { return setTimeout(syncCustomerFieldChanges); });
    var previousCustomerData = '';
    store.subscribe(function () {
        var _a, _b, _c, _d, _e, _f, _g, _h;
        var customer = PeachPayCustomer.data();
        if (Environment.modalUI.page() === 'info') {
            if (Carts.virtual() && Feature.enabled(FeatureFlag.VIRTUAL_PRODUCT_FIELDS)) {
                hideShippingDetails(true, (_b = (_a = message.phpData) === null || _a === void 0 ? void 0 : _a.shipping_fields) !== null && _b !== void 0 ? _b : [], (_d = (_c = message.phpData) === null || _c === void 0 ? void 0 : _c.shipping_fields_order) !== null && _d !== void 0 ? _d : []);
                $qs('#pp-location-marker', function (element) {
                    element.classList.add('hide');
                });
                $qs('#pp-address-line', function (element) {
                    element.classList.add('hide');
                });
            }
            else {
                hideShippingDetails(false, (_f = (_e = message.phpData) === null || _e === void 0 ? void 0 : _e.shipping_fields) !== null && _f !== void 0 ? _f : [], (_h = (_g = message.phpData) === null || _g === void 0 ? void 0 : _g.shipping_fields_order) !== null && _h !== void 0 ? _h : []);
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
function hideShippingDetails(hide, fieldData, fieldOrder) {
    var defaultFieldNames = [
        'shipping_address_header',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_postcode',
        'shipping_city',
        'shipping_state',
        'shipping_country',
    ];
    var customerDataRequiredFields = [];
    fieldOrder.forEach(function (order) {
        if (defaultFieldNames.includes(fieldData[order].field_name) && fieldData[order].field_required || fieldData[order].field_name === 'shipping_address_header') {
            customerDataRequiredFields.push(fieldData[order].field_name);
        }
    });
    var customerDataEnableFields = [];
    fieldOrder.forEach(function (order) {
        if (defaultFieldNames.includes(fieldData[order].field_name) && fieldData[order].field_enable || fieldData[order].field_name === 'shipping_address_header') {
            customerDataEnableFields.push(fieldData[order].field_name);
        }
    });
    if (hide) {
        customerDataEnableFields.forEach(function (fieldName) {
            $qsAll("#pp-info-form #".concat(fieldName, "-field"), function ($element) {
                $element.classList.add('hide');
            });
        });
    }
    else if (!hide) {
        customerDataEnableFields.forEach(function (fieldName) {
            $qsAll("#pp-info-form #".concat(fieldName, "-field"), function ($element) {
                $element.classList.remove('hide');
            });
        });
    }
    if (hide) {
        customerDataRequiredFields.forEach(function (fieldName) {
            $qsAll("#pp-info-form input#".concat(fieldName), function ($element) {
                $element.removeAttribute('required');
            });
        });
    }
    else if (!hide) {
        customerDataRequiredFields.forEach(function (fieldName) {
            $qsAll("#pp-info-form input#".concat(fieldName), function ($element) {
                $element.setAttribute('required', '');
            });
        });
    }
}
function syncCustomerFieldChanges() {
    var $form = $qs('#pp-info-form');
    if (!$form) {
        return;
    }
    var formData = new FormData($form);
    var formFields = PeachPayCustomer.data().form_fields;
    for (var _i = 0, _a = Array.from($form.elements); _i < _a.length; _i++) {
        var $input = _a[_i];
        var inputName = $input.name;
        if ($input.hasAttribute('disabled') || $input.classList.contains('hide') || !$input.name) {
            delete formFields[inputName];
            continue;
        }
        if ($input.type === 'radio' && $input.checked) {
            formFields[inputName] = formEntry(formData, inputName);
            continue;
        }
        formFields[inputName] = formEntry(formData, inputName);
    }
    store.dispatch(updateCustomerFields(formFields));
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
                        store.dispatch(updateCustomerFields(__assign(__assign({}, PeachPayCustomer.data().form_fields), { shipping_country: (_b = ((_a = locationInfo === null || locationInfo === void 0 ? void 0 : locationInfo.customer_default_country) !== null && _a !== void 0 ? _a : locationInfo === null || locationInfo === void 0 ? void 0 : locationInfo.store_country)) !== null && _b !== void 0 ? _b : '' })));
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
    var _a, _b, _c, _d, _e;
    var merchantShipping = MerchantConfiguration.general.wcLocationInfoData();
    if (merchantShipping) {
        var _f = stateProvinceOrCounty(PeachPayCustomer.country()), defaultOption_1 = _f[0], label_1 = _f[1];
        var stateOrProvinceOptions = (_a = merchantShipping.allowed_states_or_provinces[PeachPayCustomer.country()]) !== null && _a !== void 0 ? _a : {};
        if (stateOrProvinceOptions && Object.keys(stateOrProvinceOptions).length > 0) {
            var $stateOrProvincesSelect = $qs('#dynamic-states');
            if ($stateOrProvincesSelect) {
                $stateOrProvincesSelect.innerHTML = renderDropDownList(stateOrProvinceOptions, defaultOption_1);
                $stateOrProvincesSelect.disabled = false;
                $stateOrProvincesSelect.setAttribute('name', 'shipping_state');
                $qs('label[for="dynamic-states"]', function ($element) { return $element.textContent = label_1 !== null && label_1 !== void 0 ? label_1 : defaultOption_1; });
                $stateOrProvincesSelect.required = true;
                $stateOrProvincesSelect.classList.remove('hide');
                (_b = $qs('label[for="dynamic-states"]')) === null || _b === void 0 ? void 0 : _b.classList.remove('hide');
            }
            var $stateOrProvincesText = $qs('#province');
            if ($stateOrProvincesText) {
                $stateOrProvincesText.disabled = true;
                $stateOrProvincesText.setAttribute('name', 'off');
                $stateOrProvincesText.required = false;
                $stateOrProvincesText.value = '';
                $stateOrProvincesText.classList.add('hide');
                (_c = $qs('label[for="province"]')) === null || _c === void 0 ? void 0 : _c.classList.add('hide');
            }
        }
        else {
            var $stateOrProvincesSelect = $qs('#dynamic-states');
            if ($stateOrProvincesSelect) {
                $stateOrProvincesSelect.disabled = true;
                $stateOrProvincesSelect.setAttribute('name', 'off');
                $stateOrProvincesSelect.required = false;
                $stateOrProvincesSelect.classList.add('hide');
                (_d = $qs('label[for="dynamic-states"]')) === null || _d === void 0 ? void 0 : _d.classList.add('hide');
            }
            var $stateOrProvincesText = $qs('#province');
            if ($stateOrProvincesText) {
                $stateOrProvincesText.disabled = false;
                $stateOrProvincesText.setAttribute('name', 'shipping_state');
                $qs('label[for="province"]', function ($element) { return $element.textContent = label_1 !== null && label_1 !== void 0 ? label_1 : defaultOption_1; });
                $stateOrProvincesText.value = '';
                $stateOrProvincesText.classList.remove('hide');
                (_e = $qs('label[for="province"]')) === null || _e === void 0 ? void 0 : _e.classList.remove('hide');
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
    var selectACountry = getLocaleText('Select a country');
    var countryOptions = merchantLocationInfo.allowed_countries;
    $countries.innerHTML = renderDropDownList(countryOptions, selectACountry);
    selectDropdown($countries, merchantLocationInfo.customer_default_country || merchantLocationInfo.store_country);
    if ($countries.options.length === 2) {
        $countries.selectedIndex = 1;
    }
    $countries.dispatchEvent(new Event('change'));
}
function renderCustomerFields(customer) {
    renderCorrectProvinceField();
    Object.keys(customer.form_fields).forEach(function (key) {
        var input = $qs("#pp-info-form [name=\"".concat(key, "\"]"));
        if (input !== null && input.value !== '' && !customer.form_fields[key]) {
            return;
        }
        if (customer.form_fields[key] !== undefined && input && input.type !== 'radio' && input.type !== 'select-one') {
            $qs("#pp-info-form input[name=\"".concat(key, "\"]"), function ($element) { return $element.value = customer.form_fields[key]; });
        }
        if (customer.form_fields[key] !== undefined && input && input.type === 'select-one') {
            $qs("#pp-info-form [name=\"".concat(key, "\"]"), function ($element) { return $element.value = customer.form_fields[key]; });
        }
        if (customer.form_fields[key] !== undefined && input && input.type === 'radio') {
            $qs("#pp-info-form input[name=\"".concat(key, "\"][id=\"").concat(key, "-").concat(customer.form_fields[key], "\"]"), function ($element) { return $element.checked = true; });
        }
    });
}
function renderCustomerHeader(customer, existingCustomer) {
    var _a, _b, _c, _d;
    if (existingCustomer) {
        $qs('#existing-email', function ($element) { return $element.textContent = customer.form_fields.shipping_email; });
        $qs('#existing-name_first', function ($element) { return $element.textContent = customer.form_fields.shipping_first_name; });
        $qs('#existing-name_last', function ($element) { return $element.textContent = customer.form_fields.shipping_last_name; });
        $qs('#existing-address1', function ($element) { return $element.textContent = customer.form_fields.shipping_address_1; });
        $qs('#existing-address2', function ($element) { return $element.textContent = customer.form_fields.shipping_address_2 ? ' ' + customer.form_fields.shipping_address_2 : ''; });
        $qs('#existing-city', function ($element) { return $element.textContent = customer.form_fields.shipping_city; });
        if (customer.form_fields.shipping_country === 'JP') {
            var fullStateName_1 = (_b = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.wc_location_info) === null || _b === void 0 ? void 0 : _b.allowed_states_or_provinces.JP[customer.form_fields.shipping_state];
            $qs('#existing-state', function ($element) { return $element.textContent = fullStateName_1 !== null && fullStateName_1 !== void 0 ? fullStateName_1 : ''; });
        }
        else {
            $qs('#existing-state', function ($element) { return $element.textContent = customer.form_fields.shipping_state; });
        }
        $qs('#existing-country', function ($element) { return $element.textContent = getCountryName(customer.form_fields.shipping_country); });
        $qs('#existing-postal', function ($element) { return $element.textContent = customer.form_fields.shipping_postcode; });
    }
    else {
        var fullAddress_1 = '';
        if (customer.form_fields.shipping_country === 'JP') {
            var fullState = (_d = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.wc_location_info) === null || _d === void 0 ? void 0 : _d.allowed_states_or_provinces.JP[customer.form_fields.shipping_state];
            fullAddress_1 = "".concat(customer.form_fields.shipping_postcode, ", ").concat(fullState !== null && fullState !== void 0 ? fullState : customer.form_fields.shipping_state, ",  ").concat(customer.form_fields.shipping_city, ", ").concat(customer.form_fields.shipping_address_1).concat(customer.form_fields.shipping_address_2 ? ' ' + customer.form_fields.shipping_address_2 : '');
        }
        else {
            fullAddress_1 = "".concat(customer.form_fields.shipping_address_1).concat(customer.form_fields.shipping_address_2 ? ' ' + customer.form_fields.shipping_address_2 + ', ' : ',', " ").concat(customer.form_fields.shipping_city, ", ").concat(customer.form_fields.shipping_state, " ").concat(customer.form_fields.shipping_postcode, ", ").concat(customer.form_fields.shipping_country);
        }
        var fullName_1 = "".concat(customer.form_fields.shipping_first_name, " ").concat(customer.form_fields.shipping_last_name);
        $qs('.email', function ($element) { return $element.innerHTML = customer.form_fields.shipping_email; });
        $qs('.full-name', function ($element) { return $element.innerHTML = fullName_1; });
        $qs('.pp-address', function ($element) { return $element.innerHTML = fullAddress_1; });
    }
}
function initCurrency(message) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s;
    initCurrencyEvents();
    store.dispatch(updateMerchantCurrencyConfig({
        name: (_b = (_a = message.phpData.currency_info) === null || _a === void 0 ? void 0 : _a.name) !== null && _b !== void 0 ? _b : 'United States Dollar',
        code: (_d = (_c = message.phpData.currency_info) === null || _c === void 0 ? void 0 : _c.code) !== null && _d !== void 0 ? _d : 'USD',
        symbol: (_g = (_f = (_e = message.phpData) === null || _e === void 0 ? void 0 : _e.currency_info) === null || _f === void 0 ? void 0 : _f.symbol) !== null && _g !== void 0 ? _g : '$',
        thousands_separator: (_j = (_h = message.phpData.currency_info) === null || _h === void 0 ? void 0 : _h.thousands_separator) !== null && _j !== void 0 ? _j : ',',
        decimal_separator: (_l = (_k = message.phpData.currency_info) === null || _k === void 0 ? void 0 : _k.decimal_separator) !== null && _l !== void 0 ? _l : '.',
        number_of_decimals: (_o = (_m = message.phpData.currency_info) === null || _m === void 0 ? void 0 : _m.number_of_decimals) !== null && _o !== void 0 ? _o : 2,
        position: (_q = (_p = message.phpData.currency_info) === null || _p === void 0 ? void 0 : _p.position) !== null && _q !== void 0 ? _q : 'left',
        rounding: (_s = (_r = message.phpData.currency_info) === null || _r === void 0 ? void 0 : _r.rounding) !== null && _s !== void 0 ? _s : 'disabled'
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
function capitalizeFirstLetter(string) {
    var stringToUpper = String(string);
    return stringToUpper.charAt(0).toUpperCase() + stringToUpper.slice(1);
}
function cartIsVirtual(cart) {
    var _a;
    if ((cart === null || cart === void 0 ? void 0 : cart.length) === 0) {
        return true;
    }
    return (_a = cart === null || cart === void 0 ? void 0 : cart.every(function (v) { return v.virtual; })) !== null && _a !== void 0 ? _a : true;
}
function cartItemQuantity(cartItem) {
    var _a;
    return typeof (cartItem === null || cartItem === void 0 ? void 0 : cartItem.quantity) === 'string' ? Number.parseInt(cartItem.quantity) : (_a = cartItem === null || cartItem === void 0 ? void 0 : cartItem.quantity) !== null && _a !== void 0 ? _a : 0;
}
function cartItemLabel(item) {
    var name = item.name;
    if (MerchantConfiguration.hostName() === 'ugoprobaseball.com' && item.formatted_item_data && item.name_with_variation) {
        name = item.name_with_variation;
    }
    var variationTitle = !item.attributes && item.variation_title ? " - ".concat(item.variation_title) : '';
    var label = "".concat(name).concat(variationTitle);
    return label;
}
function cartItemDisplayAmount(item) {
    var _a, _b, _c, _d, _e;
    if (item.is_subscription) {
        var stringAmount = !((_a = item.subscription_price_string) === null || _a === void 0 ? void 0 : _a.indexOf(String((_b = item.display_price) !== null && _b !== void 0 ? _b : item.price))) ? '' : formatCostString(Number.parseFloat((_c = item.display_price) !== null && _c !== void 0 ? _c : item.price));
        return "".concat(MerchantConfiguration.currency.symbol()).concat(stringAmount).concat((_d = item.subscription_price_string) !== null && _d !== void 0 ? _d : '');
    }
    return "".concat(formatCurrencyString(Number.parseFloat((_e = item.display_price) !== null && _e !== void 0 ? _e : item.price) * cartItemQuantity(item)));
}
function cartItemVariationHTML(item) {
    if (item.formatted_item_data) {
        return "".concat(metaDataRowsHTML(item)).concat(formattedItemDataHTMLTemplate(item));
    }
    var variationRowHTML = '';
    if (!item.attributes) {
        return variationRowHTML;
    }
    var keys = Object.keys(item.attributes);
    for (var _i = 0, keys_1 = keys; _i < keys_1.length; _i++) {
        var key = keys_1[_i];
        var formattedKey = capitalizeFirstLetter(key.replace('attribute_', '').replace('pa_', '').replace(/-/g, ' '));
        var formattedValue = String(item.attributes[key]).toUpperCase();
        variationRowHTML += "<dt>".concat(formattedKey, ": </dt><dd>").concat(formattedValue, "</dd>");
    }
    return "".concat(metaDataRowsHTML(item), "<dl>").concat(variationRowHTML, "</dl>");
}
function metaDataRowsHTML(item) {
    if (!item.meta_data || Object.entries(item.meta_data).length === 0) {
        return '';
    }
    var html = '';
    for (var _i = 0, _a = item.meta_data; _i < _a.length; _i++) {
        var meta = _a[_i];
        var keyText = capitalizeFirstLetter(meta.key.replace(/_/g, ' '));
        html += "<dt>".concat(keyText, ": </dt><dd>").concat(meta.value || '(none)', "</dd>");
    }
    return "<dl>".concat(html, "</dl>");
}
function formattedItemDataHTMLTemplate(item) {
    if (!item.formatted_item_data) {
        return '';
    }
    return item.formatted_item_data.replace(/&nbsp;/g, '');
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
function getOrderService() {
    return {
        placeOrder: placeWCOrder,
        setOrderStatus: setWCOrderStatus,
        addOrderNote: addWCOrderNote,
        getOrderRedirect: getOrderRedirect
    };
}
function initShipping(message) {
    initShippingEvents();
    store.dispatch(updateMerchantGeneralConfig(__assign(__assign({}, store.getState().merchantConfiguration.general), { wcLocationInfoData: message.phpData.wc_location_info })));
    store.dispatch(updateMerchantShippingConfig({
        shippingZones: Number.parseInt(message.phpData.num_shipping_zones)
    }));
}
function setWCOrderStatus(order, wcStatus, options) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l;
    return __awaiter(this, void 0, void 0, function () {
        var request, result;
        return __generator(this, function (_m) {
            switch (_m.label) {
                case 0:
                    request = {
                        session: {
                            id: PeachPayOrder.sessionId()
                        },
                        order: {
                            id: order.order_id,
                            status: wcStatus,
                            message: (_a = options.message) !== null && _a !== void 0 ? _a : '',
                            paymentMethod: PaymentConfiguration.selectedProvider(),
                            stripeCustomerId: (_c = (_b = options === null || options === void 0 ? void 0 : options.stripe) === null || _b === void 0 ? void 0 : _b.customer_id) !== null && _c !== void 0 ? _c : undefined,
                            stripeTransactionId: (_e = (_d = options === null || options === void 0 ? void 0 : options.stripe) === null || _d === void 0 ? void 0 : _d.charge_id) !== null && _e !== void 0 ? _e : undefined,
                            paypalTransactionId: (_g = (_f = options === null || options === void 0 ? void 0 : options.paypal) === null || _f === void 0 ? void 0 : _f.transaction_id) !== null && _g !== void 0 ? _g : undefined
                        }
                    };
                    return [4, fetchHostWindowData('pp-set-order-status', request)];
                case 1:
                    result = _m.sent();
                    if (!result) return [3, 3];
                    return [4, recordTransactionStatus(order, options.paymentStatus, options.orderStatus, {
                            paypal: (_h = options === null || options === void 0 ? void 0 : options.paypal) !== null && _h !== void 0 ? _h : undefined,
                            stripe: (_j = options === null || options === void 0 ? void 0 : options.stripe) !== null && _j !== void 0 ? _j : undefined
                        })];
                case 2:
                    _m.sent();
                    return [3, 5];
                case 3: return [4, recordTransactionStatus(order, options.paymentStatus, 'unknown', {
                        paypal: (_k = options === null || options === void 0 ? void 0 : options.paypal) !== null && _k !== void 0 ? _k : undefined,
                        stripe: (_l = options === null || options === void 0 ? void 0 : options.stripe) !== null && _l !== void 0 ? _l : undefined
                    })];
                case 4:
                    _m.sent();
                    _m.label = 5;
                case 5: return [2, result];
            }
        });
    });
}
function addWCOrderNote(order, note) {
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
function placeWCOrder() {
    var _a, _b, _c;
    return __awaiter(this, void 0, void 0, function () {
        var requestMessage, _d, validAddress, errorMessage, response;
        return __generator(this, function (_e) {
            switch (_e.label) {
                case 0:
                    requestMessage = {
                        session: {
                            id: PeachPayOrder.sessionId()
                        },
                        order: {
                            paymentMethod: PaymentConfiguration.selectedProvider(),
                            billingAddress: PeachPayCustomer.wcBillingAddress(),
                            formRecord: PeachPayOrder.orderFormRecord(),
                            deliveryDate: collectDeliveryDate(),
                            merchantCustomerAccountPassword: '',
                            vatNum: '',
                            vatSelfVerify: ''
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
                    _d = validateAddress1(requestMessage.order.formRecord), validAddress = _d[0], errorMessage = _d[1];
                    if (!validAddress) {
                        captureSentryException(new Error("Invalid checkout form data at ".concat(MerchantConfiguration.hostName(), ". Error: ").concat(errorMessage)));
                        store.dispatch(setOrderError("".concat(errorMessage)));
                        return [2, {
                                result: 'failure',
                                message: "".concat(errorMessage),
                                order_id: 0,
                                order_key: '',
                                redirect: '',
                                details: {
                                    id: '',
                                    order_key: '',
                                    total: ''
                                }
                            }];
                    }
                    return [4, fetchHostWindowData('pp-place-order', requestMessage)];
                case 1:
                    response = _e.sent();
                    if (response.result === 'failure') {
                        store.dispatch(setOrderError((_c = response.message) !== null && _c !== void 0 ? _c : getLocaleText('Unknown order error occurred')));
                    }
                    return [2, response];
            }
        });
    });
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
    var _a, _b, _c;
    store.subscribe(renderShipping);
    (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.addEventListener('submit', handleInfoSubmission);
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
                case 0: return [4, fetchHostWindowData('pp-validate-billing-address', __assign(__assign({}, PeachPayCustomer.wcBillingAddress()), PeachPayCustomer.wcShippingAddress()))];
                case 1: return [2, _a.sent()];
            }
        });
    });
}
function handleInfoSubmission(event) {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    event.preventDefault();
                    syncCustomerFieldChanges();
                    store.dispatch(startModalLoading());
                    if (!(Carts.virtual() && Feature.enabled(FeatureFlag.VIRTUAL_PRODUCT_FIELDS))) return [3, 2];
                    return [4, transitionToPaymentPage()];
                case 1: return [2, _a.sent()];
                case 2: return [4, validateAddress()];
                case 3:
                    if (!_a.sent()) return [3, 5];
                    return [4, transitionToPaymentPage()];
                case 4: return [2, _a.sent()];
                case 5:
                    store.dispatch(stopModalLoading());
                    return [2];
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
    var packageMethodKey = cartKey === '0' ? shippingPackageKey : "".concat(cartKey, "_").concat(shippingPackageKey);
    var methodOptionBuilder = function (methodKey, method, selected) { return "\n<label class=\"pp-disabled-loading\" for=\"shipping_method_".concat(packageMethodKey, "_").concat(methodKey.replace(/:/g, ''), "\" style=\"margin: 0 0 3px 0; display: flex; flex-direction: row; cursor: pointer;\">\n\t<input class=\"pp-disabled-loading\" style=\"margin: 6px 0px 0px 0px;\" id=\"shipping_method_").concat(packageMethodKey, "_").concat(methodKey.replace(/:/g, ''), "\" name=\"shipping_method[").concat(packageMethodKey, "]\" value=\"").concat(methodKey, "\" type=\"radio\" ").concat(selected ? 'checked' : '', " required>\n\t").concat(method.description ? "<div style=\"width: 100%;\">\n\t\t<div>\n\t\t\t<span style=\"display: inline-block; flex-grow: 1; margin-left: 8px;\">".concat(method.title, "</span>\n\t\t\t<span style=\"display: inline-block; min-width: 30%; text-align: right;\" class=\"shipping-price pp-currency-blur\">").concat(formatCurrencyString(method.total), "<span class=\"muted\">").concat(buildSubscriptionPriceMetaData(cartMeta), "</span></span>\n\t\t</div>\n\t\t<div style=\"font-size: 12px;\">").concat(method.description, "</div>\n\t</div>") : "<span style=\"display: inline-block; flex-grow: 1; margin-left: 8px;\">".concat(method.title, "</span>\n\t<span style=\"display: inline-block; min-width: 30%; text-align: right;\" class=\"shipping-price pp-currency-blur\">").concat(formatCurrencyString(method.total), "<span class=\"muted\">").concat(buildSubscriptionPriceMetaData(cartMeta), "</span></span>"), "\n</label>"); };
    var packageNameTranslations = {
        'Shipping': getLocaleText('Shipping'),
        'Initial Shipment': getLocaleText('Initial Shipment')
    };
    var packageName = shippingPackage.package_name in packageNameTranslations ? packageNameTranslations[shippingPackage.package_name] : shippingPackage.package_name;
    var packageNameHTML = "<h4 class=\"shipping-header color-change-text\">".concat(packageName, "</h4>");
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
                renderOrderNotice(response.notices.error[i].notice);
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
function recordTransactionStatus(order, orderStatus, paymentStatus, options) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var response, text, error_3;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    _c.trys.push([0, 4, , 5]);
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/transaction/record', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                session: {
                                    id: PeachPayOrder.sessionId(),
                                    merchant_name: MerchantConfiguration.name(),
                                    merchant_url: MerchantConfiguration.hostName(),
                                    plugin_version: Environment.plugin.version(),
                                    paypal: (_a = options === null || options === void 0 ? void 0 : options.paypal) !== null && _a !== void 0 ? _a : undefined,
                                    stripe: (_b = options === null || options === void 0 ? void 0 : options.stripe) !== null && _b !== void 0 ? _b : undefined
                                },
                                order: {
                                    id: order.details.id,
                                    order_status: orderStatus,
                                    payment_status: paymentStatus,
                                    data: order
                                }
                            })
                        })];
                case 1:
                    response = _c.sent();
                    if (!!response.ok) return [3, 3];
                    return [4, response.text()];
                case 2:
                    text = _c.sent();
                    captureSentryException(new Error('Non 200 response while attempting to record transaction in DB'), {
                        response: text
                    });
                    return [2, false];
                case 3: return [2, true];
                case 4:
                    error_3 = _c.sent();
                    captureSentryException(new Error('Recording transaction to DB failed.'), {
                        exception: error_3
                    });
                    return [2, false];
                case 5: return [2];
            }
        });
    });
}
function validateAddress1(address) {
    var _a, _b, _c, _d;
    var errorLabels = {};
    var requiredFields = [];
    var billingFields = (_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.billing_fields;
    var billingFieldsOrder = (_b = GLOBAL.phpData) === null || _b === void 0 ? void 0 : _b.billing_fields_order;
    var shippingFields = (_c = GLOBAL.phpData) === null || _c === void 0 ? void 0 : _c.shipping_fields;
    var shippingFieldsOrder = (_d = GLOBAL.phpData) === null || _d === void 0 ? void 0 : _d.shipping_fields_order;
    if (billingFields != null && billingFieldsOrder != null) {
        for (var i = 0; i < billingFieldsOrder.length; i++) {
            var field = billingFields[billingFieldsOrder[i]];
            if (field.field_enable === 'yes' && field.field_required === 'yes') {
                errorLabels[field.field_name] = field.field_label;
                requiredFields.push(field.field_name);
            }
        }
    }
    if (shippingFields != null && shippingFieldsOrder != null) {
        for (var i = 0; i < shippingFieldsOrder.length; i++) {
            var field = shippingFields[shippingFieldsOrder[i]];
            if (field.field_enable === 'yes' && field.field_required === 'yes') {
                errorLabels[field.field_name] = field.field_label;
                requiredFields.push(field.field_name);
            }
        }
    }
    var _loop_2 = function (key, value) {
        if (requiredFields.includes(key)) {
            if (value.length === 0 || value === null) {
                return { value: [
                        false,
                        "".concat(getLocaleText('Please go back and try again. Missing required field'), ": ").concat(errorLabels[key], ".")
                    ] };
            }
            else {
                requiredFields = requiredFields.filter(function (e) { return e !== key; });
            }
        }
    };
    for (var _i = 0, _e = Object.entries(address); _i < _e.length; _i++) {
        var _f = _e[_i], key = _f[0], value = _f[1];
        var state_1 = _loop_2(key, value);
        if (typeof state_1 === "object")
            return state_1.value;
    }
    if (requiredFields.length !== 0) {
        return [
            false,
            "".concat(getLocaleText('Please go back and try again. Missing required field'), ": ").concat(errorLabels[requiredFields[0]], ".")
        ];
    }
    return [
        true,
        null
    ];
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
    var _loop_3 = function ($form) {
        $form.addEventListener('submit', function (event) {
            var _a, _b;
            event.preventDefault();
            if (!$form.checkValidity()) {
                $form.reportValidity();
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
        _loop_3($form);
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
function removeCoupon(code) {
    return __awaiter(this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!(code.length > 0)) return [3, 3];
                    store.dispatch(startModalLoading());
                    return [4, fetchHostWindowData('pp-remove-coupon', {
                            code: code
                        })];
                case 1:
                    _a.sent();
                    return [4, requestCartCalculation()];
                case 2:
                    _a.sent();
                    store.dispatch(stopModalLoading());
                    hideCouponLoadingState();
                    _a.label = 3;
                case 3: return [2];
            }
        });
    });
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
    var _loop_4 = function ($form) {
        $form.addEventListener('submit', function (event) {
            var _a, _b, _c;
            event.preventDefault();
            if (!$form.checkValidity()) {
                $form.reportValidity();
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
        _loop_4($form);
    }
    for (var _b = 0, _c = $qsAll('.coupon-code-option'); _b < _c.length; _b++) {
        var $openCoupon = _c[_b];
        $openCoupon.addEventListener('click', showCouponInput);
        $openCoupon.addEventListener('keypress', showCouponInput);
    }
    for (var _d = 0, _e = $qsAll('.exit-button-coupon'); _d < _e.length; _d++) {
        var $exitCoupon = _e[_d];
        $exitCoupon.addEventListener('click', hideCouponInput);
    }
    hideCouponLoadingState();
    handleCouponRemoval();
}
function handleCouponRemoval() {
    $qsAll('#pp-summary-lines-body, #pp-summary-lines-body-existing, #pp-summary-lines-body-mobile', function ($removeButtons) {
        $removeButtons.addEventListener('click', function (event) {
            var $target = event.target;
            if (!$target) {
                return;
            }
            var $removeButton = $target.closest('.pp-coupon-remove-button[data-coupon]');
            if (!$removeButton) {
                return;
            }
            var coupon = $removeButton.dataset.coupon;
            if (!coupon) {
                return;
            }
            removeCoupon(coupon);
        });
    });
}
function showCouponEntrySupport() {
    var _a;
    for (var _i = 0, _b = $qsAll('.coupon-code-option'); _i < _b.length; _i++) {
        var $form = _b[_i];
        $form.classList.remove('hide');
    }
    (_a = $qs('#coupon-code-section')) === null || _a === void 0 ? void 0 : _a.classList.remove('hide');
}
function showCouponInput(event) {
    if (!eventClick(event)) {
        return;
    }
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
    var _loop_5 = function ($input) {
        $input.addEventListener('invalid', function () {
            $input.setCustomValidity('Te rugăm sa completezi acest câmp.');
        });
        $input.addEventListener('input', function () {
            $input.setCustomValidity('');
        });
    };
    for (var _i = 0, _a = $qsAll('form input'); _i < _a.length; _i++) {
        var $input = _a[_i];
        _loop_5($input);
    }
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
        $cartContainer.addEventListener('click', handleCartContainerEvent);
        $cartContainer.addEventListener('keypress', handleCartContainerEvent);
    });
}
function handleCartContainerEvent(event) {
    return __awaiter(this, void 0, void 0, function () {
        var $target, $inputTarget_1, cartItemKey_1, $button, cartItemKey, cartItemKey;
        var _this = this;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    $target = event.target;
                    if (!($target.closest('.qty-fs') && event.type === 'keypress')) return [3, 5];
                    $inputTarget_1 = event.target;
                    cartItemKey_1 = $target.closest('.qty-fs').dataset.qid;
                    if (!($inputTarget_1.value && $inputTarget_1.checkValidity())) return [3, 4];
                    if (!eventClick(event)) return [3, 2];
                    return [4, changeQuantity(cartItemKey_1, Number.parseInt($inputTarget_1.value), true)];
                case 1:
                    _a.sent();
                    return [3, 4];
                case 2:
                    if (!(event.type === 'keypress')) return [3, 4];
                    $target.addEventListener('blur', function () { return __awaiter(_this, void 0, void 0, function () {
                        return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0: return [4, changeQuantity(cartItemKey_1, Number.parseInt($inputTarget_1.value), true)];
                                case 1:
                                    _a.sent();
                                    return [2];
                            }
                        });
                    }); }, {
                        once: true
                    });
                    return [4, new Promise(function (r) { return setTimeout(r, 750); })];
                case 3:
                    _a.sent();
                    if (!document.activeElement.classList.contains('qty-fs')) {
                        return [2];
                    }
                    $target.blur();
                    _a.label = 4;
                case 4: return [2];
                case 5:
                    if (!eventClick(event)) {
                        return [2];
                    }
                    if (!$target.closest('.qty-btn') && !$target.closest('.qty-fs') && !$target.closest('.item-remover')) {
                        return [2];
                    }
                    if (!$target.closest('.qty-btn')) return [3, 10];
                    $button = $target.closest('.qty-btn');
                    cartItemKey = $button.dataset.qid;
                    if (!$button.classList.contains('decrease-qty')) return [3, 7];
                    return [4, changeQuantity(cartItemKey, -1, false)];
                case 6:
                    _a.sent();
                    return [3, 9];
                case 7:
                    if (!$button.classList.contains('increase-qty')) return [3, 9];
                    return [4, changeQuantity(cartItemKey, 1, false)];
                case 8:
                    _a.sent();
                    _a.label = 9;
                case 9: return [3, 12];
                case 10:
                    if (!$target.closest('.item-remover')) return [3, 12];
                    cartItemKey = $target.closest('.item-remover').dataset.qid;
                    return [4, changeQuantity(cartItemKey, 0, true)];
                case 11:
                    _a.sent();
                    _a.label = 12;
                case 12: return [2];
            }
        });
    });
}
function changeQuantity(cartItemKey, amount, set) {
    if (amount === void 0) { amount = 1; }
    if (set === void 0) { set = false; }
    return __awaiter(this, void 0, void 0, function () {
        var response, error_4;
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
                    error_4 = _a.sent();
                    if (error_4 instanceof Error) {
                        captureSentryException(new Error("Quantity failed to change on ".concat(MerchantConfiguration.hostName(), ". Error").concat(error_4.message)));
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
    var $tbody = $qs('#pp-summary-body');
    var $tbodyExisting = $qs('#pp-summary-body-existing');
    var $tbodyMobile = $qs('#pp-summary-body-mobile');
    if (!$tbody || !$tbodyExisting || !$tbodyMobile) {
        return;
    }
    clearOrderSummary();
    if (DefaultCart.contents().length === 0) {
        var $message = "<tr class=\"pp-summary-item\"><td style=\"text-align: center\">".concat(getLocaleText('Cart is empty'), "</td></tr>");
        $tbody.innerHTML = $message;
        $tbodyMobile.innerHTML = $message;
        $tbodyExisting.innerHTML = $message;
        return;
    }
    var _loop_6 = function (i) {
        var item = cart[i];
        var nextItem = cart[i + 1];
        if (cartItemQuantity(item) === 0) {
            return out_i_1 = i, "continue";
        }
        var cartRowHTMLTemplate = function () {
            if (!item.is_part_of_bundle && !(nextItem === null || nextItem === void 0 ? void 0 : nextItem.is_part_of_bundle)) {
                return regularCartItemHTMLTemplate(item);
            }
            else {
                var parentItem = item;
                var bundledItems = [];
                while (i < cart.length) {
                    var bundleItem = cart[i + 1];
                    if (bundleItem && bundleItem.is_part_of_bundle) {
                        i++;
                        bundledItems.push(bundleItem);
                        continue;
                    }
                    break;
                }
                return bundleCartItemHTMLTemplate(parentItem, bundledItems);
            }
        };
        var cartRow = cartRowHTMLTemplate();
        $tbody.innerHTML += cartRow;
        $tbodyMobile.innerHTML += cartRow;
        $tbodyExisting.innerHTML += cartRow;
        out_i_1 = i;
    };
    var out_i_1;
    for (var i = 0; i < cart.length; i++) {
        _loop_6(i);
        i = out_i_1;
    }
}
function regularCartItemHTMLTemplate(item) {
    var label = cartItemLabel(item);
    var amount = cartItemDisplayAmount(item);
    var itemTemplateHTML = function () { return "\n<tr class=\"pp-item-primary pp-summary-item\">\n\t".concat(imageQuantityHTMLTemplate(item), "\n\t<td class=\"pp-label-td\">").concat(label, "</td>\n\t<td class=\"pp-amount-td\"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t").concat(removerHTMLTemplate(item), "\n</tr>\n<tr class=\"pp-item-secondary pp-summary-item\">\n\t<td colspan=\"3\">").concat(cartItemVariationHTML(item), "</td>\n</tr>"); };
    return itemTemplateHTML();
}
function bundleCartItemHTMLTemplate(parentItem, bundledItems) {
    var label = cartItemLabel(parentItem);
    var amount = cartItemDisplayAmount(parentItem);
    var itemTemplateHTML = function () { return "\n\t<tr class=\"pp-item-primary pp-summary-item\">\n\t\t".concat(imageQuantityHTMLTemplate(parentItem), "\n\t\t<td class=\"pp-label-td\">").concat(label, "</td>\n\t\t<td class=\"pp-amount-td\"><p class=\"pp-recalculate-blur\">").concat(amount, "</p></td>\n\t\t").concat(removerHTMLTemplate(parentItem), "\n\t</tr>\n\t<tr class=\"pp-item-secondary pp-summary-item\">\n\t\t<td colspan=\"3\">\n\t\t\t<table style=\"table-layout: fixed;\">\n\t\t\t\t").concat(bundledItems.map(function (item) { return bundledCartItemHTMLTemplate(item); }).join(''), "\n\t\t\t</table>\n\t\t</td>\n\t</tr>"); };
    return itemTemplateHTML();
}
function bundledCartItemHTMLTemplate(bundledItem) {
    var imageHTML = imageHTMLTemplate(bundledItem);
    var label = cartItemLabel(bundledItem);
    var variationColspanAttr = imageHTML ? 1 : 2;
    var imageTD = imageHTML ? "<td rowspan=\"2\" class=\"pp-bundle-image\">".concat(imageHTML, "</td>") : '';
    var labelTD = "<td class=\"pp-bundle-label\">".concat(label, "</td>");
    return "\n<tr>\n\t".concat(imageTD, "\n\t").concat(labelTD, "\n</tr>\n<tr>\n\t<td colspan=\"").concat(variationColspanAttr, "\">").concat(cartItemVariationHTML(bundledItem), "</td>\n</tr>\n");
}
function clearOrderSummary() {
    for (var _i = 0, _a = $qsAll('.pp-summary-item'); _i < _a.length; _i++) {
        var $item = _a[_i];
        $item.remove();
    }
}
function imageQuantityHTMLTemplate(item) {
    if (!Feature.enabled(FeatureFlag.QUANTITY_CHANGER) && !Feature.enabled(FeatureFlag.PRODUCT_IMAGES)) {
        return '';
    }
    return "\n<td rowspan=\"2\">\n\t".concat(imageHTMLTemplate(item), "\n\t").concat(quantityHTMLTemplate(item), "\n</td>");
}
function imageHTMLTemplate(item) {
    var _a, _b;
    if (!Feature.enabled(FeatureFlag.PRODUCT_IMAGES)) {
        return '';
    }
    var imageSrc = (_b = (_a = item.image) === null || _a === void 0 ? void 0 : _a[0]) !== null && _b !== void 0 ? _b : '';
    var imageClass = item.is_part_of_bundle ? 'bundle-img-size' : 'product-img-size';
    if (!imageSrc || imageSrc === 'unknown' || imageSrc === '(unknown)') {
        return '';
    }
    return "\n<div class=\"product-img-td-b0\">\n\t<img class=\"".concat(imageClass, "\" src=\"").concat(imageSrc, "\" />\n</div>");
}
function quantityHTMLTemplate(item) {
    var _a;
    if (!Feature.enabled(FeatureFlag.QUANTITY_CHANGER)) {
        return '';
    }
    var itemKey = (_a = item.item_key) !== null && _a !== void 0 ? _a : '';
    var quantity = cartItemQuantity(item);
    var maxQuantity = item.stock_qty ? item.stock_qty : '';
    return "\n<div class=\"quantity-changer\">\n\t<button type=\"button\" class=\"pr-0 decrease-qty qty-btn ".concat(quantity <= 1 ? 'scroll-end' : '', "\" data-qid=\"").concat(itemKey, "\">&#8722;</button>\n\t<form onSubmit=\"return false;\" class=\"mb-0\">\n\t\t<input type=\"number\" min=\"0\" max=\"").concat(maxQuantity, "\" class=\"qty-fs\" value=\"").concat(quantity, "\" data-qid=\"").concat(itemKey, "\" required/>\n\t</form>\n\t<button type=\"button\" class=\"pl-0 increase-qty qty-btn ").concat(item.stock_qty && quantity >= item.stock_qty ? 'scroll-end' : '', "\" data-qid=\"").concat(itemKey, "\">+</button>\n</div>");
}
function removerHTMLTemplate(item) {
    var _a;
    var itemKey = (_a = item.item_key) !== null && _a !== void 0 ? _a : '';
    return "\n<td>\n\t<button class=\"item-remover pp-disabled-processing\" data-qid=\"".concat(itemKey, "\">&times;</button>\n</td>");
}
function initSummary(message) {
    var _a;
    initSummaryEvents();
    store.dispatch(updateMerchantTaxConfig({
        displayPricesInCartAndCheckout: ((_a = message.phpData) === null || _a === void 0 ? void 0 : _a.wc_tax_price_display) === 'incl' ? 'includeTax' : 'excludeTax'
    }));
}
function initSummaryEvents() {
    var _a, _b, _c, _d, _e;
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
    (_e = $qs('#pp-summary-return')) === null || _e === void 0 ? void 0 : _e.classList.add('pp-shadow-bottom');
    var lastScrollTop = 0;
    var scrollbar = document.getElementById('pp-summary-body-existing');
    scrollbar === null || scrollbar === void 0 ? void 0 : scrollbar.addEventListener('scroll', function () {
        var _a, _b, _c, _d, _e, _f, _g;
        if (scrollbar.scrollTop !== lastScrollTop) {
            lastScrollTop = Math.floor(scrollbar.scrollTop);
            if (lastScrollTop <= 1) {
                (_a = $qs('#pp-summary-return')) === null || _a === void 0 ? void 0 : _a.classList.remove('pp-shadow-topAndbottom');
                (_b = $qs('#pp-summary-return')) === null || _b === void 0 ? void 0 : _b.classList.add('pp-shadow-bottom');
            }
            else if (lastScrollTop + scrollbar.clientHeight == scrollbar.scrollHeight) {
                (_c = $qs('#pp-summary-return')) === null || _c === void 0 ? void 0 : _c.classList.remove('pp-shadow-topAndbottom');
                (_d = $qs('#pp-summary-return')) === null || _d === void 0 ? void 0 : _d.classList.add('pp-shadow-top');
            }
            else {
                (_e = $qs('#pp-summary-return')) === null || _e === void 0 ? void 0 : _e.classList.add('pp-shadow-topAndbottom');
                (_f = $qs('#pp-summary-return')) === null || _f === void 0 ? void 0 : _f.classList.remove('pp-shadow-top');
                (_g = $qs('#pp-summary-return')) === null || _g === void 0 ? void 0 : _g.classList.remove('pp-shadow-bottom');
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
function initModal(message) {
    var _this = this;
    var _a, _b, _c, _d;
    insertCustomCheckoutCSS(message);
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
        renderTermsAndCondition(Environment.modalUI.page(), (_b = (_a = message.phpData) === null || _a === void 0 ? void 0 : _a.wc_terms_conditions) !== null && _b !== void 0 ? _b : '');
        renderFreeOrderDisplay(DefaultCart.contents().length, DefaultCart.total());
        renderHideOnMobile(Environment.customer.mobile());
        displayErrorMessage(PeachPayOrder.errorMessage());
    });
    onWindowMessage('UI::modalOpened', function (_) {
        var _a, _b, _c;
        store.dispatch(updateEnvironment({
            modalIsOpen: true
        }));
        if (Environment.modalUI.page() !== 'info') {
            var infoFormValidity = (_b = (_a = $qs('#pp-info-form')) === null || _a === void 0 ? void 0 : _a.checkValidity()) !== null && _b !== void 0 ? _b : false;
            if (!infoFormValidity) {
                store.dispatch(updateEnvironment({
                    modalPageType: 'info',
                    customerExists: false
                }));
                (_c = $qs('#pp-info-form')) === null || _c === void 0 ? void 0 : _c.reportValidity();
            }
        }
    });
    onWindowMessage('UI::modalClosed', function (_) {
        store.dispatch(updateEnvironment({
            modalIsOpen: false
        }));
        store.dispatch(stopModalLoading());
    });
    onWindowMessage('buttonClicked', function () { return __awaiter(_this, void 0, void 0, function () {
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    openCheckoutModal();
                    store.dispatch(startModalLoading());
                    return [4, requestCartCalculation(!Environment.customer.existing())];
                case 1:
                    _a.sent();
                    store.dispatch(stopModalLoading());
                    return [2];
            }
        });
    }); });
    (_a = $qs('.pp-exit')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', requestCloseModal);
    (_b = $qs('.pp-close')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', requestCloseModal);
    (_c = $qs('.pp-close')) === null || _c === void 0 ? void 0 : _c.addEventListener('keypress', requestCloseModal);
    self.addEventListener('keydown', tabToExit);
    (_d = $qs('#edit-info')) === null || _d === void 0 ? void 0 : _d.addEventListener('click', backToInfo);
    for (var _i = 0, _e = $qsAll('.pp-back-to-info-click'); _i < _e.length; _i++) {
        var $element1 = _e[_i];
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
function renderFreeOrderDisplay(cartCount, cartTotal) {
    if (cartCount > 0 && cartTotal === 0) {
        $qsAll('.pp-hide-on-free-order', function ($el) { return $el.classList.add('hide'); });
    }
    else {
        $qsAll('.pp-hide-on-free-order', function ($el) { return $el.classList.remove('hide'); });
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
function requestCloseModal(event) {
    var _a;
    if (!eventClick(event)) {
        return;
    }
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
function tabToExit(event) {
    if (event.key === 'Tab') {
        if (event.target.classList.contains('peachpay-logo-link')) {
            event.preventDefault();
            $qs('.pp-close', function ($element) { return $element.focus(); });
        }
    }
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
function renderModalPageIndicator(modalPage) {
    var _a, _b, _c, _d;
    var $leftText = $qs('#pp-checkout-status-left-text');
    if (!$leftText) {
        return;
    }
    if (modalPage === 'info') {
        (_a = $qs('#pp-checkout-status-left')) === null || _a === void 0 ? void 0 : _a.classList.add('current');
        $leftText.innerHTML = '1';
    }
    else {
        $leftText.innerHTML = '✓';
        (_b = $qs('#pp-checkout-status-left')) === null || _b === void 0 ? void 0 : _b.classList.remove('current');
    }
    if (modalPage === 'payment') {
        (_c = $qs('#pp-checkout-status-right')) === null || _c === void 0 ? void 0 : _c.classList.add('current');
    }
    else {
        (_d = $qs('#pp-checkout-status-right')) === null || _d === void 0 ? void 0 : _d.classList.remove('current');
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
function renderHideOnMobile(isMobile) {
    if (isMobile) {
        $qsAll('.pp-hide-on-mobile', function ($el) { return $el.classList.add('hide'); });
    }
    else {
        $qsAll('.pp-hide-on-mobile', function ($el) { return $el.classList.remove('hide'); });
    }
}
function renderTermsAndCondition(page, terms) {
    if (page === 'payment') {
        var merchantTermsConditions_1 = terms ? "".concat(getLocaleText("the store's"), " <a href='").concat(terms, "' target='_blank'>").concat(getLocaleText('terms and conditions'), "</a> ").concat(getLocaleText('and')) : '';
        $qsAll('.pp-tc-section', function ($el) {
            $el.innerHTML = "<label class='pp-tc-contents'>".concat(getLocaleText('By clicking the button above, you agree to'), " ").concat(merchantTermsConditions_1, " ").concat(getLocaleText('the'), " PeachPay <a href='https://peachpay.app/terms' target='_blank'>").concat(getLocaleText('terms'), "</a> ").concat(getLocaleText('and'), " <a href='https://peachpay.app/privacy' target='_blank'>").concat(getLocaleText('privacy policy'), "</a>.</label>");
            $el.classList.remove('hide');
        });
    }
    else {
        $qsAll('.pp-tc-section', function ($el) { return $el.classList.add('hide'); });
    }
}
function insertCustomCheckoutCSS(message) {
    var _a, _b;
    var $body = document.querySelector('body');
    if ($body) {
        var $style = document.createElement('style');
        $style.id = 'pp-custom-checkout-css';
        $style.appendChild(document.createTextNode((_b = (_a = message.phpData.custom_checkout_css) === null || _a === void 0 ? void 0 : _a.trim()) !== null && _b !== void 0 ? _b : ''));
        $body.insertAdjacentElement('beforeend', $style);
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
    $currencySelectTitle.innerHTML = getLocaleText('Currency');
    $currencySelectTitle.setAttribute('class', 'color-change-text');
    existingCurrencySelectContainer.id = 'pp_currency_select_div';
    existingCurrencySelectContainer.append($currencySelectTitle);
    var $newCurrencySelectContainer = existingCurrencySelectContainer.cloneNode(true);
    var mappedCurrencies = {};
    for (var _i = 0, _a = Object.keys(currencyInfo); _i < _a.length; _i++) {
        var key = _a[_i];
        var currency = currencyInfo[key];
        mappedCurrencies[key] = "(".concat(currency.symbol, ") - ").concat(currency.name);
    }
    var $options = renderCurrencyList(mappedCurrencies, MerchantConfiguration.currency.code());
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
                    store.dispatch(initilizePrimaryPaymentMethodUI());
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
function renderCurrencyList(data, defaultOption) {
    if (defaultOption === void 0) { defaultOption = ''; }
    if (!data) {
        data = {};
    }
    var list = Object.entries(data).map(function (_a) {
        var key = _a[0], value = _a[1];
        return "<option value=\"".concat(key, "\" ").concat(key === defaultOption ? 'selected' : '', "> ").concat(value, " </option>");
    });
    return list.join('');
}
function initPaymentMethods() {
    handleMorePMToggle();
    handlePMTabOptionsEvents();
    handleMoreOptionsEvents();
    handleSavedPMOptionEvents();
    handleNewPMOptionButtonEvents();
    handleSavedPMOptionButtonEvents();
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
        $qs('#pp-pms-existing', function ($el) {
            if ($el.style.overflow === 'visible') {
                $el.style.overflow = 'hidden';
            }
            else {
                $el.style.overflow = 'visible';
            }
        });
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
        return "\n<div class=\"pp-pm-selected-option\" data-provider=\"".concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"").concat(index, "\">\n\t<span>\n\t\t<img class=\"pp-pm-display-badge\" style=\"transform: scale(").concat((_b = (_a = method.assets.title) === null || _a === void 0 ? void 0 : _a.scale) !== null && _b !== void 0 ? _b : '1', ") translateX(").concat((_d = (_c = method.assets.title) === null || _c === void 0 ? void 0 : _c.translateX) !== null && _d !== void 0 ? _d : '0', "px)\" src=\"").concat((_f = (_e = method.assets.title) === null || _e === void 0 ? void 0 : _e.src) !== null && _f !== void 0 ? _f : '', "\" draggable=\"false\">\n\t</span>\n</div>");
    };
    var buildSelectedCardTemplate = function () {
        var _a, _b, _c;
        return "\n<div class=\"pp-pm-selected-option\" data-provider=\"".concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"").concat(index, "\">\n\t<span>\n\t\t<img class=\"pp-pm-display-badge\" src=\"img/marks/").concat((_a = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _a === void 0 ? void 0 : _a['brand'], ".svg\" draggable=\"false\">\n\t\t\u2022\u2022\u2022\u2022\n\t\t").concat((_c = (_b = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _b === void 0 ? void 0 : _b['last4']) !== null && _c !== void 0 ? _c : '', "\n\t</span>\n\t<span style=\"float: right;\">\n\t\t").concat(getLocaleText('Verified'), "\n\t\t<img class=\"pp-pm-checkmark\" src=\"img/check-circle-solid.svg\" draggable=\"false\">\n\t</span>\n</div>");
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
            var $pmType = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-type:not(.pp-more-options)');
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
    renderMoreOptionsTab();
    renderPMTabDisplay();
    renderPMTabOrder();
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
}
function renderPMTabOrder() {
    $qsAll('.pp-pms', function ($target) {
        var slot1 = store.getState().paymentConfiguration.ui.primaryMethods[0];
        var slot2 = store.getState().paymentConfiguration.ui.primaryMethods[1];
        var slot3 = store.getState().paymentConfiguration.ui.primaryMethods[2];
        var $tab1 = $target.querySelector(".pp-pm-type[data-provider=".concat(slot1 === null || slot1 === void 0 ? void 0 : slot1.provider, "][data-method=").concat(slot1 === null || slot1 === void 0 ? void 0 : slot1.method, "]"));
        var $tab2 = $target.querySelector(".pp-pm-type[data-provider=".concat(slot2 === null || slot2 === void 0 ? void 0 : slot2.provider, "][data-method=").concat(slot2 === null || slot2 === void 0 ? void 0 : slot2.method, "]"));
        var $tab3 = $target.querySelector(".pp-pm-type[data-provider=".concat(slot3 === null || slot3 === void 0 ? void 0 : slot3.provider, "][data-method=").concat(slot3 === null || slot3 === void 0 ? void 0 : slot3.method, "]"));
        if (!$tab1 || !$tab2) {
            return;
        }
        $tab2 === null || $tab2 === void 0 ? void 0 : $tab2.before($tab1);
        if (!$tab3) {
            return;
        }
        $tab3 === null || $tab3 === void 0 ? void 0 : $tab3.before($tab2);
    });
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
        $qsAll('.pp-pms .pp-pm-type.pp-more-options', function ($el) { return $el.insertAdjacentHTML('beforebegin', html_2); });
    }
    renderSavedPMOptionsContainer(providerKey, methodKey);
    processSavedPMOptions(providerKey, methodKey);
}
function updatePMTabOptionHTML($existingOption, providerKey, methodKey) {
    var isEligible = PaymentConfiguration.eligibleMethod(providerKey, methodKey) && PaymentConfiguration.isPrimaryMethod(providerKey, methodKey);
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
        return "\n\t\t<div class=\"pp-pm-type ".concat(isSelected ? 'selected' : '', "\" tabindex=\"0\" role=\"button\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t\t\t<span>\n\t\t\t\t<img class=\"pp-pm-badge\" style=\"transform: scale(").concat(method.assets.badge.scale, ") translateX(").concat((_a = method.assets.badge.translateX) !== null && _a !== void 0 ? _a : '0', "px)\" src=\"").concat(method.assets.badge.src, "\" draggable=\"false\">\n\t\t\t</span>\n\t\t\t<span class=\"pp-name\">").concat(method.name, "</span>\n\t\t</div>");
    }
    return "\n\t<div class=\"pp-pm-type ".concat(isSelected ? 'selected' : '', "\" tabindex=\"0\" role=\"button\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t\t<span>\n\t\t\t<img class=\"pp-pm-full-badge\" style=\"transform: scale(").concat(method.assets.badge.scale, ") translateX(").concat((_b = method.assets.badge.translateX) !== null && _b !== void 0 ? _b : '0', "px)\" src=\"").concat(method.assets.badge.src, "\" draggable=\"false\">\n\t\t</span>\n\t</div>");
}
function handleMoreOptionsEvents() {
    $qs('body', function ($el1) {
        $el1.addEventListener('click', function (e) {
            var $target = e.target;
            var $pmOptionSelector = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-more-container');
            if (!$pmOptionSelector) {
                $qsAll('.pp-pm-more-container', function ($el) { return $el.classList.add('hide'); });
            }
        });
    });
    $qsAll('.pp-pms', function ($el2) {
        $el2.addEventListener('click', function (e) {
            var _a;
            var $target = e.target;
            var $pmType = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-type.pp-more-options');
            if (!$pmType) {
                $qsAll('.pp-pm-more-container', function ($el) { return $el.classList.add('hide'); });
                return;
            }
            (_a = $pmType.querySelector('.pp-pm-more-container')) === null || _a === void 0 ? void 0 : _a.classList.toggle('hide');
            $qsAll('.pp-pm-sos-container', function ($el) { return $el.classList.add('hide'); });
            e.preventDefault();
            e.stopPropagation();
            var $option = $target === null || $target === void 0 ? void 0 : $target.closest('li[data-provider][data-method]');
            if (!$option) {
                return;
            }
            var providerKey = $option === null || $option === void 0 ? void 0 : $option.dataset.provider;
            var methodKey = $option === null || $option === void 0 ? void 0 : $option.dataset.method;
            if (!providerKey || !methodKey) {
                return;
            }
            store.dispatch(swapPrimaryWithSecondary({
                method: methodKey,
                provider: providerKey
            }));
            store.dispatch(setPaymentMethod({
                method: methodKey,
                provider: providerKey
            }));
        });
    });
}
function renderMoreOptionsTab() {
    var $existingMoreOptionsTab = $qsAll(".pp-pm-type.pp-more-options");
    if ($existingMoreOptionsTab.length) {
        updateMoreOptionsHTML($existingMoreOptionsTab);
    }
    else {
        var html_3 = buildMoreOptionsTemplate();
        $qsAll('.pp-pms div.header', function ($el) { return $el.insertAdjacentHTML('beforeend', html_3); });
    }
}
function updateMoreOptionsHTML($existingOption) {
    var isVisible = PaymentConfiguration.eligibleMethodCount() > 3;
    var availableOptionsHTML = PaymentConfiguration.allEligibleMethods().filter(function (info) { return !PaymentConfiguration.isPrimaryMethod(info.provider, info.method); }).map(function (info) { return "\n<li data-provider=\"".concat(info.provider, "\" data-method=\"").concat(info.method, "\" role=\"button\" tabindex=\"-1\">\n\t<span><img class=\"pp-more-option-badge\" src=\"").concat(info.config.assets.badge.src, "\" draggable=\"false\"></span>\n\t<span>").concat(info.config.name, "</span>\n</li>"); }).join('');
    $existingOption.forEach(function ($el) {
        if (isVisible) {
            $el.classList.remove('hide');
        }
        else {
            $el.classList.add('hide');
        }
        var $list = $el.querySelector('.pp-pm-more');
        if ($list) {
            $list.innerHTML = availableOptionsHTML;
        }
    });
}
function buildMoreOptionsTemplate() {
    return "\n\t<div class=\"pp-pm-type pp-more-options\" tabindex=\"0\" role=\"button\">\n\t\t<span>\n\t\t\t<img class=\"pp-pm-more-options\" src=\"img/dot-dot-dot.svg\" draggable=\"false\">\n\t\t</span>\n\t\t<span class=\"pp-pm-more-container hide\">\n\t\t\t<ul class=\"pp-pm-more\"></ul>\n\t\t</span>\n\t</div>";
}
function renderSavedPMOptionsContainer(providerKey, methodKey) {
    var $existingTypeOptionContainer = $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
    if ($existingTypeOptionContainer.length) {
        updateSavedPMOptionsContainerHTML($existingTypeOptionContainer, providerKey, methodKey);
    }
    else {
        var html_4 = buildSavedPMOptionsContainerTemplate(providerKey, methodKey);
        $qsAll('.pp-pms div.body', function ($el) { return $el.insertAdjacentHTML('beforeend', html_4); });
    }
}
function updateSavedPMOptionsContainerHTML($existingContainer, providerKey, methodKey) {
    var isEligible = PaymentConfiguration.eligibleMethod(providerKey, methodKey) && PaymentConfiguration.isPrimaryMethod(providerKey, methodKey);
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
    $qs('body', function ($el3) {
        $el3.addEventListener('click', function (e) {
            var $target = e.target;
            var $pmOptionSelector = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos-container,.pp-pm-sos-toggle');
            if (!$pmOptionSelector) {
                $qsAll('.pp-pm-sos-container', function ($el) { return $el.classList.add('hide'); });
            }
        });
    });
    $qsAll('.pp-pms', function ($el4) {
        $el4.addEventListener('click', function (e) {
            var _a, _b, _c, _d;
            var $target = e.target;
            var $savedOption = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-saved-option ');
            var $sosToggle = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos-toggle');
            var $sosContainer = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos');
            var $sosRemove = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-sos li[data-remove]');
            if (!$savedOption) {
                return;
            }
            $qsAll('.pp-pm-more-container', function ($el) { return $el.classList.add('hide'); });
            e.preventDefault();
            e.stopPropagation();
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
    renderSavedPMOptionButton(providerKey, methodKey, methodConfig);
}
function renderSavedPMOption(providerKey, methodKey, index, method, saved) {
    var $existingOption = $qsAll(".pp-pm-saved-option[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"][data-index=\"").concat(index, "\"]"));
    if ($existingOption.length) {
        updateSavedPMOptionHTML($existingOption, providerKey, methodKey, index, method);
    }
    else {
        var html_5 = buildSavedPMOptionTemplate(providerKey, methodKey, index, method, saved);
        $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"] .pp-pm-options"), function ($el) { return $el.insertAdjacentHTML('beforeend', html_5); });
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
    return "\n<div class=\"pp-pm-saved-option ".concat(isSelected ? 'selected' : '', " ").concat(indexActive ? '' : 'hide', "\" tabindex=\"0\" role=\"button\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\" data-index=\"").concat(index, "\">\n\t<span style=\"font-family: 'Helvetica', 'Arial',monospace;\">\n\t\t<span style=\"width: 3rem;padding-right: 4px;\">\n\t\t\t<img class=\"pp-pm-title\" src=\"img/marks/").concat((_b = (_a = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _a === void 0 ? void 0 : _a['brand']) !== null && _b !== void 0 ? _b : '', ".svg\" draggable=\"false\">\n\t\t</span>\n\t\t\u2022\u2022\u2022\u2022\n\t\t").concat((_d = (_c = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _c === void 0 ? void 0 : _c['last4']) !== null && _d !== void 0 ? _d : '', "\n\t</span>\n\t<span class=\"muted\" style=\"float:right;display: inline-block;padding: 0.2rem;\">\n\t\t").concat((_f = (_e = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _e === void 0 ? void 0 : _e['exp_month']) !== null && _f !== void 0 ? _f : 'MM', "/").concat((_h = (_g = saved === null || saved === void 0 ? void 0 : saved.metadata) === null || _g === void 0 ? void 0 : _g['exp_year']) !== null && _h !== void 0 ? _h : 'YY', "\n\t\t<img class=\"pp-pm-sos-toggle\" src=\"img/dot-dot-dot.svg\" draggable=\"false\">\n\t\t<span class=\"pp-pm-sos-container hide\">\n\t\t\t<ul class=\"pp-pm-sos\">\n\t\t\t\t<li data-remove role=\"button\" tabindex=\"-1\">").concat(getLocaleText('Remove'), "</li>\n\t\t\t\t<li data-cancel role=\"button\" tabindex=\"-1\">").concat(getLocaleText('Cancel'), "</li>\n\t\t\t</ul>\n\t\t</span>\n\t</span>\n</div>");
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
        var html_6 = buildNewPMOptionButtonTemplate(providerKey, methodKey, method);
        $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"] .pp-pm-controls"), function ($el) { return $el.insertAdjacentHTML('beforeend', html_6); });
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
    return "\n<div class=\"pp-pm-option-new pp-pm-option-control ".concat(isVisible ? '' : 'hide', "\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t<span class=\"muted\" tabindex=\"0\" role=\"button\">").concat((_a = method.addNewButton) !== null && _a !== void 0 ? _a : '', "</span>\n</div>");
}
function handleSavedPMOptionButtonEvents() {
    $qsAll('.pp-pms', function ($el) {
        $el.addEventListener('click', function (e) {
            var _a, _b;
            var $target = e.target;
            var $pmType = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-option-saved');
            var $pmSpan = $target === null || $target === void 0 ? void 0 : $target.closest('.pp-pm-option-saved span');
            if (!$pmType || !$pmSpan) {
                return;
            }
            var providerKey = (_a = $pmType.dataset.provider) !== null && _a !== void 0 ? _a : '';
            var methodKey = (_b = $pmType.dataset.method) !== null && _b !== void 0 ? _b : '';
            store.dispatch(setPaymentMethod({
                provider: providerKey,
                method: methodKey,
                index: '0'
            }));
        });
    });
}
function renderSavedPMOptionButton(providerKey, methodKey, method) {
    var $existingOption = $qsAll(".pp-pm-option-saved[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"]"));
    if ($existingOption.length) {
        updateSavedPMOptionButtonHTML($existingOption, providerKey, methodKey);
    }
    else {
        var html_7 = buildSavedPMOptionButtonTemplate(providerKey, methodKey, method);
        $qsAll(".pp-pm-container[data-provider=\"".concat(providerKey, "\"][data-method=\"").concat(methodKey, "\"] .pp-pm-controls"), function ($el) { return $el.insertAdjacentHTML('beforeend', html_7); });
    }
}
function updateSavedPMOptionButtonHTML($existingOption, providerKey, methodKey) {
    var _a;
    var isVisible = !PaymentConfiguration.selectedProviderMethodIndex() && ((_a = PeachPayCustomer.retrieveSavedPaymentMethods(providerKey, methodKey)[0]) === null || _a === void 0 ? void 0 : _a.length);
    if (isVisible && Environment.modalUI.loadingMode() !== 'processing') {
        $existingOption.forEach(function ($el) { return $el.classList.remove('hide'); });
    }
    else {
        $existingOption.forEach(function ($el) { return $el.classList.add('hide'); });
    }
}
function buildSavedPMOptionButtonTemplate(providerKey, methodKey, method) {
    var _a;
    var isVisible = PaymentConfiguration.selectedProviderMethodIndex();
    return "\n<div class=\"pp-pm-option-saved pp-pm-option-control ".concat(isVisible ? '' : 'hide', "\" data-provider=\"").concat(providerKey, "\" data-method=\"").concat(methodKey, "\">\n\t<span class=\"muted\" tabindex=\"0\" role=\"button\">").concat((_a = method.savedButton) !== null && _a !== void 0 ? _a : '', "</span>\n</div>");
}
function initOneClickUpsell() {
    if (!Feature.enabled(FeatureFlag.ONE_CLICK_UPSELL)) {
        return;
    }
    var ocuFlow = Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'pp_ocu_flow');
    if (ocuFlow === 'pp_button') {
        store.subscribe(function () {
            var _a;
            if (((_a = $qs('#pp-ocu-container')) === null || _a === void 0 ? void 0 : _a.getAttribute('aria-expanded')) === 'false') {
                displayOCUPage(ocuFlow);
            }
        });
        store.dispatch(updateEnvironment({
            modalPageType: 'ocu'
        }));
    }
    else if (ocuFlow === 'before_payment') {
        $qsAll('#pp-continue, #pp-continue-mobile', function (element) { return element.addEventListener('click', function () {
            store.subscribe(function () {
                var _a;
                if (((_a = $qs('#pp-ocu-container')) === null || _a === void 0 ? void 0 : _a.getAttribute('aria-expanded')) === 'false') {
                    displayOCUPage(ocuFlow);
                }
            });
        }); });
    }
}
function displayOCUPage(flow) {
    var ocuHeadline = Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'headline_text');
    var ocuSubHeadline = Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'sub_headline_text');
    var acceptBtnText = Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'accept_button_text');
    var declineBtnText = Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'decline_button_text');
    var ocuProducts = Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'pp_ocu_products');
    $qsAll('#pp-modal-content, #pp-related-products-section', function ($el) { return $el.style.zIndex = '-1'; });
    renderOCUPage(ocuHeadline, ocuSubHeadline, acceptBtnText, declineBtnText, ocuProducts);
    if (flow !== 'after_payment') {
        initOCUEvents();
    }
}
function renderOCUPage(ocuHeadline, subHeadline, acceptBtnText, declineBtnText, ocuProducts) {
    clearOCUSection();
    if (ocuProducts) {
        var ocuContainer = $qs('#pp-ocu-container');
        ocuContainer === null || ocuContainer === void 0 ? void 0 : ocuContainer.classList.remove('hide');
        ocuContainer === null || ocuContainer === void 0 ? void 0 : ocuContainer.setAttribute('aria-expanded', 'true');
        var ocuBody = document.createElement('div');
        ocuBody.className = 'pp-ocu-body';
        ocuBody.innerHTML = "\n                <button class=\"pp-ocu-close\">&times;</button>\n                <span class=\"pp-ocu-headline\">".concat(ocuHeadline, "</span>\n                <div class=\"pp-ocu-sub-headline ").concat(subHeadline ? '' : 'hide', "\">\n                    <span>").concat(subHeadline, "</span>\n                </div>\n                <div class=\"pp-ocu-contents\">\n                    <img class=\"pp-ocu-product-img\" src=").concat(ocuProducts.image, ">\n                    <span class=\"pp-ocu-product-name\">").concat(ocuProducts.name, "</span>\n                    <span class=\"pp-ocu-product-description\">").concat(ocuProducts.desc, "</span>\n                    <div class=\"pp-ocu-product-price\">").concat(ocuProducts.price, "</div>\n                </div>\n                <button class=\"pp-ocu-accept-button\" data-ocu_id=").concat(ocuProducts.id, ">").concat(acceptBtnText, "</button>\n                <button class=\"pp-ocu-decline-button\">").concat(declineBtnText, "</button>");
        ocuContainer === null || ocuContainer === void 0 ? void 0 : ocuContainer.prepend(ocuBody);
    }
}
function clearOCUSection() {
    for (var _i = 0, _a = $qsAll('.pp-ocu-body'); _i < _a.length; _i++) {
        var ocuProduct = _a[_i];
        ocuProduct.remove();
    }
}
function initOCUEvents() {
    var _a;
    for (var _i = 0, _b = $qsAll('.pp-ocu-close, .pp-ocu-decline-button'); _i < _b.length; _i++) {
        var ocuClose = _b[_i];
        ocuClose.addEventListener('click', function () {
            closeOCUPage();
        });
    }
    (_a = $qs('.pp-ocu-accept-button')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function (event) {
        event.target.disabled = true;
        event.target.innerHTML = '<img src="img/spinner.svg" class="ocu-spinner"/> Updating your order ...';
        addOCUProductToCart(event.target);
    });
}
function closeOCUPage() {
    var _a;
    (_a = $qs('#pp-ocu-container')) === null || _a === void 0 ? void 0 : _a.classList.add('pp-blowup-close');
    setTimeout(function () {
        var _a;
        $qs('#pp-ocu-container', function ($el) { return $el.style.backgroundColor = 'unset'; });
        $qsAll('#pp-modal-content, #pp-related-products-section', function ($el) { return $el.style.zIndex = '1'; });
        (_a = $qs('#pp-ocu-container')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
    }, 500);
}
function addOCUProductToCart(ocuProduct) {
    window.parent.postMessage({
        event: 'addLinkedProduct',
        productID: ocuProduct.dataset.ocu_id
    }, '*');
    onWindowMessage('pp-update-cart', function () {
        ocuProduct.innerHTML = '<img src="img/check-solid.svg" class="ocu-checkmark"/> Product added successfully!';
        setTimeout(closeOCUPage, 3000);
    });
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
            description: getLocaleText('After selecting pay you will be redirected to complete your payment.'),
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
        renderStripeButtonDisplay(PaymentConfiguration.selectedProvider(), Environment.modalUI.page(), Environment.modalUI.loadingMode(), DefaultCart.total());
        renderStripeButtonLoading(PaymentConfiguration.selectedProvider(), Environment.modalUI.loadingMode());
    });
}
function renderStripeButtonDisplay(provider, page, loadingMode, cartTotal) {
    if (provider === 'stripe' && page === 'payment' && cartTotal > 0) {
        $qsAll('.stripe-btn-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn-container', function ($element) { return $element.classList.add('hide'); });
    }
    if (provider === 'stripe' && page === 'payment' && loadingMode !== 'loading' && cartTotal > 0) {
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
        $qsAll('.pp-btn-spinner-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.pp-btn-spinner-container ', function ($element) { return $element.classList.add('hide'); });
    }
    if (mode === 'processing') {
        $qsAll('.stripe-btn > .button-text', function ($element) { return $element.innerHTML = getLocaleText('Processing'); });
        $qsAll('.stripe-btn-spinner', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.stripe-btn > .button-text', function ($element) { return $element.innerHTML = "".concat(getLocaleText('Pay'), " ").concat(formatCurrencyString(DefaultCart.total())); });
        $qsAll('.stripe-btn-spinner', function ($element) { return $element.classList.add('hide'); });
    }
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
        return (c == 'x' ? r : r & 0x7 | 0x8).toString(16);
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
function initStripeCardMethod() {
    var _this = this;
    var config1 = {
        name: getLocaleText('Card'),
        description: '',
        addNewButton: getLocaleText('+ NEW CARD'),
        savedButton: getLocaleText('VIEW SAVED CARDS'),
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
    };
    return {
        config: config1,
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
                hidePostalCode: false
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
                                    false,
                                    error.message
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
            description: getLocaleText('After selecting pay you will be redirected to complete your payment.'),
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
    if (!Feature.enabled(FeatureFlag.STRIPE_PAYMENT_REQUEST) || Environment.plugin.pageType() === 'product') {
        return;
    }
    var applePay = Feature.metadata(FeatureFlag.STRIPE_PAYMENT_REQUEST, 'apple_pay');
    var googlePay = Feature.metadata(FeatureFlag.STRIPE_PAYMENT_REQUEST, 'google_pay');
    if (!applePay && !googlePay) {
        return;
    }
    var initMessage = {
        event: 'pp-init-stripe-payment-request',
        isOurStore: Environment.isOurStore(),
        currencyCode: MerchantConfiguration.currency.code(),
        cartCalculationRecord: store.getState().calculatedCarts,
        stripe: {
            publicKey: context.service.getStripePublicKey(),
            connectId: context.service.getStripeConnectId(),
            applePay: applePay !== null && applePay !== void 0 ? applePay : false,
            googlePay: googlePay !== null && googlePay !== void 0 ? googlePay : false
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
    var _a, _b, _c, _d, _e, _f;
    return __awaiter(this, void 0, void 0, function () {
        var orderResponse, paymentIntentResponse, stripe, _g, error, paymentIntent, error_5;
        return __generator(this, function (_h) {
            switch (_h.label) {
                case 0:
                    store.dispatch(updateCustomerFields({
                        shipping_company: '',
                        shipping_email: request.payerEmail,
                        shipping_phone: request.payerPhone,
                        shipping_first_name: (_a = request.payerName.split(' ')[0]) !== null && _a !== void 0 ? _a : '',
                        shipping_last_name: (_b = request.payerName.split(' ')[1]) !== null && _b !== void 0 ? _b : '',
                        shipping_address_1: request.shippingAddress.addressLine[0],
                        shipping_address_2: (_c = request.shippingAddress.addressLine[1]) !== null && _c !== void 0 ? _c : '',
                        shipping_city: request.shippingAddress.city,
                        shipping_state: request.shippingAddress.region,
                        shipping_country: request.shippingAddress.country,
                        shipping_postcode: request.shippingAddress.postalCode,
                        billing_company: '',
                        billing_email: request.payerEmail,
                        billing_phone: request.payerPhone,
                        billing_first_name: (_d = request.payerName.split(' ')[0]) !== null && _d !== void 0 ? _d : '',
                        billing_last_name: (_e = request.payerName.split(' ')[1]) !== null && _e !== void 0 ? _e : '',
                        billing_address_1: request.shippingAddress.addressLine[0],
                        billing_address_2: (_f = request.shippingAddress.addressLine[1]) !== null && _f !== void 0 ? _f : '',
                        billing_city: request.shippingAddress.city,
                        billing_state: request.shippingAddress.region,
                        billing_country: request.shippingAddress.country,
                        billing_postcode: request.shippingAddress.postalCode
                    }));
                    _h.label = 1;
                case 1:
                    _h.trys.push([1, 6, , 7]);
                    return [4, validateAddress()];
                case 2:
                    if (!(_h.sent())) {
                        return [2, {
                                status: 'invalid_shipping_address'
                            }];
                    }
                    return [4, orderService.placeOrder()];
                case 3:
                    orderResponse = _h.sent();
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
                    paymentIntentResponse = _h.sent();
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
                    _g = _h.sent(), error = _g.error, paymentIntent = _g.paymentIntent;
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
                    error_5 = _h.sent();
                    if (error_5 instanceof Error) {
                        captureSentryException(new Error("Stripe payment request flow failed"), {
                            exception: error_5
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
                    store.dispatch(updateCustomerFields(__assign(__assign({}, PeachPayCustomer.data().form_fields), { shipping_address_1: (_a = request.addressLine[0]) !== null && _a !== void 0 ? _a : '', shipping_address_2: (_b = request.addressLine[1]) !== null && _b !== void 0 ? _b : '', shipping_city: (_c = request.city) !== null && _c !== void 0 ? _c : '', shipping_postcode: (_d = request.postalCode) !== null && _d !== void 0 ? _d : '', shipping_state: (_e = request.region) !== null && _e !== void 0 ? _e : '', shipping_country: (_f = request.country) !== null && _f !== void 0 ? _f : '' })));
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
    if (isPaymentMethodActive(account, 'klarna_payments')) {
        var klarnaMethod = initStripeKlarnaMethod();
        paymentMethods['klarna'] = klarnaMethod;
    }
    if (isPaymentMethodActive(account, 'afterpay_clearpay_payments')) {
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
    var _a;
    if (!account) {
        return false;
    }
    var hasCapability = Boolean(((_a = account.capabilities) === null || _a === void 0 ? void 0 : _a[selector]) === 'active');
    var enabled = Boolean(Feature.metadata(FeatureFlag.STRIPE, selector));
    return hasCapability && enabled;
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
                        if (($target === null || $target === void 0 ? void 0 : $target.classList.contains('pp-ocu-close')) || ($target === null || $target === void 0 ? void 0 : $target.classList.contains('pp-ocu-decline-button'))) {
                            closeOCUPage();
                        }
                        if ($target === null || $target === void 0 ? void 0 : $target.classList.contains('pp-ocu-accept-button')) {
                            $target.disabled = true;
                            $target.innerHTML = '<img src="img/spinner.svg" class="ocu-spinner"/> Updating your order ...';
                            window.parent.postMessage({
                                event: 'addLinkedProduct',
                                productID: $target.dataset.ocu_id
                            }, '*');
                            onWindowMessage('pp-update-cart', function () {
                                $target.innerHTML = '<img src="img/check-solid.svg" class="ocu-checkmark"/> Product added successfully!';
                                setTimeout(function () {
                                    closeOCUPage();
                                    existingPaymentMethodFlow(context, method, orderService);
                                }, 3000);
                            });
                        }
                        else {
                            existingPaymentMethodFlow(context, method, orderService);
                        }
                        return;
                    };
                    $qsAll('.stripe-btn', function ($el1) { return $el1.addEventListener('click', function (event) {
                        var _a;
                        if (Feature.enabled(FeatureFlag.ONE_CLICK_UPSELL) && 'after_payment' === Feature.metadata(FeatureFlag.ONE_CLICK_UPSELL, 'pp_ocu_flow') && checkRequiredFields() && ((_a = $qs('#pp-ocu-container')) === null || _a === void 0 ? void 0 : _a.getAttribute('aria-expanded')) === 'false') {
                            displayOCUPage('after_payment');
                            $qsAll('.pp-ocu-close, .pp-ocu-decline-button, .pp-ocu-accept-button', function ($el) { return $el.addEventListener('click', confirm); });
                        }
                        else {
                            confirm(event);
                        }
                    }); });
                    return [2];
            }
        });
    });
}
function newPaymentMethodFlow(context, providerMethod, orderService) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var paymentMethod, orderResponse, paymentIntentResponse, savedDetails, successURL, intermediateURL, _c, unhandledError, message;
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
                    store.dispatch(setOrderError(getLocaleText('Sorry, something went wrong. Please refresh the page and try again.')));
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
                    _c = _d.sent(), unhandledError = _c[0], message = _c[1];
                    if (unhandledError) {
                        store.dispatch(stopModalLoading());
                        store.dispatch(setOrderError(getLocaleText('Sorry, something went wrong. Please refresh the page and try again.')));
                        captureSentryException(new Error("Confirming Stripe payment intent failed for new payment method flow."), {
                            'confirm_failure': message,
                            'payment_method': paymentMethod,
                            'payment_intent_response': paymentIntentResponse,
                            'success_url': successURL,
                            'intermediate_url': intermediateURL
                        });
                        return [2];
                    }
                    return [2];
            }
        });
    });
}
function existingPaymentMethodFlow(context, providerMethod, orderService) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function () {
        var savedPaymentMethod, orderResponse, paymentIntentResponse, successURL, intermediateURL, _c, unhandledError, message;
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
                    store.dispatch(setOrderError(getLocaleText('Sorry, something went wrong. Please refresh the page and try again.')));
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
                    _c = _d.sent(), unhandledError = _c[0], message = _c[1];
                    if (unhandledError) {
                        store.dispatch(stopModalLoading());
                        store.dispatch(setOrderError(getLocaleText('Sorry, something went wrong. Please refresh the page and try again.')));
                        captureSentryException(new Error("Confirming payment intent failed for existing payment flow."), {
                            'confirm_failure': message,
                            'saved_method': savedPaymentMethod,
                            'payment_intent_response': paymentIntentResponse,
                            'success_url': successURL,
                            'intermediate_url': intermediateURL
                        });
                        return [2];
                    }
                    return [2];
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
                sessionId: PeachPayOrder.sessionId(),
                intentCancelURL: "".concat(Environment.apiURL(), "api/v1/stripe/payment-intent/cancel"),
                successURL: successURL,
                failureURL: failureURL,
                color: Environment.plugin.buttonColor()
            };
        }
        else {
            return {
                key: Environment.isTestOrDevSite() ? STRIPE_TEST_PK : STRIPE_LIVE_PK,
                sessionId: PeachPayOrder.sessionId(),
                intentCancelURL: "".concat(Environment.apiURL(), "api/v1/stripe/payment-intent/cancel"),
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
        var response, error_6;
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
                    error_6 = _c.sent();
                    if (error_6 instanceof Error) {
                        captureSentryException(new Error("Failed to create Stripe payment intent"), {
                            'exception': error_6
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
        var getConnectId, response, json, error_7;
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
                    error_7 = _a.sent();
                    if (error_7 instanceof Error) {
                        captureSentryException(error_7);
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
                    'JPY',
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
        var response, merchant, error_8;
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
                    error_8 = _a.sent();
                    if (error_8 instanceof Error) {
                        captureSentryException(error_8);
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
            return __awaiter(this, void 0, void 0, function () {
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0:
                            store.dispatch(startModalProcessing());
                            return [4, handlePayPalApprove(data, actions, orderService)];
                        case 1:
                            _a.sent();
                            return [2];
                    }
                });
            });
        },
        onClick: function () {
            return checkRequiredFields();
        }
    });
    $paypalButton.render('#paypal-btn-container');
    $paypalButton.render('#paypal-btn-container-mobile');
    $paypalButton.render('#paypal-btn-container-existing');
}
function createPayPalOrder() {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
        var toFixed, mockOrderResult, body, response, result;
        return __generator(this, function (_b) {
            switch (_b.label) {
                case 0:
                    toFixed = MerchantConfiguration.currency.configuration().number_of_decimals;
                    mockOrderResult = {
                        'domain': MerchantConfiguration.hostName(),
                        'merchant_name': MerchantConfiguration.name(),
                        'details': {
                            'id': '',
                            'number': '',
                            'currency': MerchantConfiguration.currency.code(),
                            'discount_total': DefaultCart.totalAppliedCoupons().toFixed(toFixed),
                            'shipping_total': DefaultCart.totalShipping().toFixed(toFixed),
                            'total': DefaultCart.total().toFixed(toFixed),
                            'total_tax': ((_a = GLOBAL.phpData) === null || _a === void 0 ? void 0 : _a.wc_prices_include_tax) ? '0' : DefaultCart.totalTax().toFixed(toFixed),
                            'shipping': paypalCustomerAddress(),
                            'line_items': getLineItems(),
                            'shipping_lines': getShippingLines(),
                            'fee_total': DefaultCart.totalAppliedFees().toFixed(toFixed)
                        },
                        'currency_meta': {
                            'currency_decimals': MerchantConfiguration.currency.configuration().number_of_decimals
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
            'subtotal_tax': '0'
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
function handlePayPalApprove(data, actions, orderService) {
    return __awaiter(this, void 0, void 0, function () {
        var orderResponse, error_9, capture, transactionId;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0: return [4, orderService.placeOrder()];
                case 1:
                    orderResponse = _a.sent();
                    if (orderResponse.result === 'failure') {
                        store.dispatch(stopModalLoading());
                        actions.restart();
                        return [2];
                    }
                    _a.label = 2;
                case 2:
                    _a.trys.push([2, 4, , 5]);
                    return [4, updatePayPalOrderWithFinalAmount(data.orderID, orderResponse)];
                case 3:
                    _a.sent();
                    return [3, 5];
                case 4:
                    error_9 = _a.sent();
                    captureSentryException(new Error('Error while updating PayPal order with final amount'), {
                        exception: error_9
                    });
                    return [3, 5];
                case 5: return [4, capturePayPalOrder(data.orderID)];
                case 6:
                    capture = _a.sent();
                    if (!!capture) return [3, 8];
                    return [4, orderService.addOrderNote(orderResponse, 'PayPal payment attempt failed. Reason: An unknown error occured while capturing the payment.')];
                case 7:
                    _a.sent();
                    store.dispatch(setOrderError('Something went wrong'));
                    actions.restart();
                    return [2];
                case 8:
                    if (!((capture === null || capture === void 0 ? void 0 : capture.status) === 'COMPLETED')) return [3, 10];
                    transactionId = capture.purchase_units[0].payments.captures[0].id;
                    return [4, orderService.setOrderStatus(orderResponse, 'wc-processing', {
                            paypal: {
                                transaction_id: transactionId
                            },
                            paymentStatus: 'success',
                            orderStatus: 'success'
                        })];
                case 9:
                    _a.sent();
                    store.dispatch(updateCustomerPreferredPaymentMethod({
                        provider: 'paypal',
                        method: 'default'
                    }));
                    saveCustomerToBrowser();
                    clearLocalSession();
                    if (window.top) {
                        window.top.location = orderService.getOrderRedirect(orderResponse);
                    }
                    return [2];
                case 10:
                    store.dispatch(stopModalLoading());
                    if (!((capture === null || capture === void 0 ? void 0 : capture.details[0].issue) === 'INSTRUMENT_DECLINED')) return [3, 12];
                    return [4, orderService.setOrderStatus(orderResponse, 'wc-failed', {
                            message: capture.details[0].description,
                            orderStatus: 'failed',
                            paymentStatus: 'failed'
                        })];
                case 11:
                    _a.sent();
                    store.dispatch(setOrderError(capture.details[0].description));
                    return [3, 14];
                case 12: return [4, orderService.setOrderStatus(orderResponse, 'wc-failed', {
                        message: 'Something went wrong',
                        orderStatus: 'failed',
                        paymentStatus: 'failed'
                    })];
                case 13:
                    _a.sent();
                    store.dispatch(setOrderError('Something went wrong'));
                    _a.label = 14;
                case 14:
                    actions.restart();
                    return [2];
            }
        });
    });
}
function capturePayPalOrder(orderID) {
    return __awaiter(this, void 0, void 0, function () {
        var response, error_10;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    _a.trys.push([0, 2, , 3]);
                    return [4, fetch(getBaseURL(MerchantConfiguration.hostName(), Environment.testMode()) + 'api/v1/paypal/capture', {
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
                case 2:
                    error_10 = _a.sent();
                    captureSentryException(new Error('Error while capturing PayPal order'), {
                        exception: error_10
                    });
                    return [2, null];
                case 3: return [2];
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
        var _a;
        if (Environment.plugin.pageType() === 'product' && relatedProducts && relatedProductsTitle && Environment.modalUI.page() !== 'ocu') {
            renderRelatedProducts(relatedProducts, relatedProductsTitle);
            updateRelatedProductButton(DefaultCart.contents());
        }
        else {
            (_a = $qs('#pp-related-products-section')) === null || _a === void 0 ? void 0 : _a.classList.add('hide');
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
    $qs('.pp-container', function ($element) { return $element.style.height = 'auto'; });
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
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
        if ((_a = $qs('.pp-rp-dropdown-down')) === null || _a === void 0 ? void 0 : _a.classList.contains('hide')) {
            (_b = $qs('.pp-rp-dropdown-up')) === null || _b === void 0 ? void 0 : _b.classList.add('hide');
            (_c = $qs('.pp-rp-dropdown-down')) === null || _c === void 0 ? void 0 : _c.classList.remove('hide');
            (_d = $qs('#pp-related-products-container')) === null || _d === void 0 ? void 0 : _d.classList.remove('pp-rp-open');
            (_e = $qs('#pp-related-products-container')) === null || _e === void 0 ? void 0 : _e.classList.add('pp-rp-close');
        }
        else if ((_f = $qs('.pp-rp-dropdown-up')) === null || _f === void 0 ? void 0 : _f.classList.contains('hide')) {
            (_g = $qs('.pp-rp-dropdown-up')) === null || _g === void 0 ? void 0 : _g.classList.remove('hide');
            (_h = $qs('.pp-rp-dropdown-down')) === null || _h === void 0 ? void 0 : _h.classList.add('hide');
            (_j = $qs('#pp-related-products-container')) === null || _j === void 0 ? void 0 : _j.classList.remove('pp-rp-close');
            (_k = $qs('#pp-related-products-container')) === null || _k === void 0 ? void 0 : _k.classList.add('pp-rp-open');
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
function setupFreeOrderButton() {
    store.subscribe(function () {
        renderFreeOrderButtonDisplay(Environment.modalUI.page(), DefaultCart.contents().length, DefaultCart.total(), Environment.modalUI.loadingMode());
        renderFreeOrderButtonLoading(Environment.modalUI.loadingMode());
    });
}
function renderFreeOrderButtonDisplay(page, cartSize, cartTotal, loadingMode) {
    if (cartSize > 0 && cartTotal === 0) {
        $qsAll('.free-btn-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.free-btn-container', function ($element) { return $element.classList.add('hide'); });
    }
    if (page === 'payment' && loadingMode !== 'loading' && cartTotal === 0) {
        $qsAll('.free-btn', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.free-btn', function ($element) { return $element.classList.add('hide'); });
    }
}
function renderFreeOrderButtonLoading(mode) {
    if (mode === 'finished') {
        $qsAll('.free-btn', function ($element) { return $element.disabled = false; });
    }
    else {
        $qsAll('.free-btn', function ($element) { return $element.disabled = true; });
    }
    if (mode === 'loading') {
        $qsAll('.pp-btn-spinner-container', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.pp-btn-spinner-container ', function ($element) { return $element.classList.add('hide'); });
    }
    if (mode === 'processing') {
        $qsAll('.free-btn > .button-text', function ($element) { return $element.innerHTML = getLocaleText('Processing'); });
        $qsAll('.free-btn-spinner', function ($element) { return $element.classList.remove('hide'); });
    }
    else {
        $qsAll('.free-btn > .button-text', function ($element) { return $element.innerHTML = getLocaleText('Place order'); });
        $qsAll('.free-btn-spinner', function ($element) { return $element.classList.add('hide'); });
    }
}
function initFreeOrderSupport(orderService) {
    var _this = this;
    var confirm = function (event) { return __awaiter(_this, void 0, void 0, function () {
        var $target, $button;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    if (!checkRequiredFields()) {
                        return [2];
                    }
                    $target = event.target;
                    $button = $target === null || $target === void 0 ? void 0 : $target.closest('button');
                    if (!$button) {
                        return [2];
                    }
                    return [4, freeOrderFlow(orderService)];
                case 1:
                    _a.sent();
                    return [2];
            }
        });
    }); };
    $qsAll('.free-btn', function ($el) { return $el.addEventListener('click', confirm); });
    setupFreeOrderButton();
}
function freeOrderFlow(orderService) {
    return __awaiter(this, void 0, void 0, function () {
        var orderResponse, successURL;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    store.dispatch(startModalProcessing());
                    return [4, orderService.placeOrder()];
                case 1:
                    orderResponse = _a.sent();
                    if (orderResponse.result === 'failure') {
                        store.dispatch(stopModalLoading());
                        return [2];
                    }
                    saveCustomerToBrowser();
                    successURL = orderService.getOrderRedirect(orderResponse);
                    if (window.top) {
                        window.top.location = successURL;
                    }
                    return [2];
            }
        });
    });
}
var autocomplete;
function initAddressAutocomplete(message) {
    if (!message.addressAutocomplete) {
        return;
    }
    var input = document.getElementById('shipping_address_1');
    var options = {
        fields: [
            'address_components',
            'formatted_address'
        ],
        types: [
            'address'
        ]
    };
    autocomplete = new google.maps.places.Autocomplete(input, options);
    autocomplete.addListener('place_changed', fillInAddress);
}
function fillInAddress() {
    var address1Field = document.querySelector('#shipping_address_1');
    var address2Field = document.querySelector('#shipping_address_2');
    var postalField = document.querySelector('#shipping_postcode');
    var cityField = document.querySelector('#shipping_city');
    var stateField = document.querySelector('#dynamic-states');
    var provinceField = document.querySelector('#province');
    address1Field.value = '';
    address2Field.value = '';
    postalField.value = '';
    cityField.value = '';
    stateField.value = '';
    provinceField.value = '';
    var place = autocomplete.getPlace();
    var country = place.address_components.find(function (component) { return component.types[0] === 'country'; });
    var countryField = document.querySelector('#country');
    if (countryField) {
        countryField.value = country.short_name;
        syncCustomerFieldChanges();
    }
    var postcode = '';
    for (var _i = 0, _a = place.address_components; _i < _a.length; _i++) {
        var component1 = _a[_i];
        var componentType = component1.types[0];
        switch (componentType) {
            case 'postal_code':
                {
                    postcode = "".concat(component1.long_name).concat(postcode);
                    break;
                }
            case 'postal_code_suffix':
                {
                    postcode = "".concat(postcode, "-").concat(component1.long_name);
                    break;
                }
            case 'locality':
            case 'postal_town':
                {
                    if (cityField) {
                        cityField.value = component1.long_name;
                    }
                    break;
                }
            case 'administrative_area_level_1':
                {
                    if (stateField) {
                        stateField.value = component1.short_name;
                    }
                    if (provinceField) {
                        provinceField.value = component1.short_name;
                    }
                    break;
                }
            case 'country':
                break;
        }
    }
    address1Field.value = getFormattedStreetAddress(place.formatted_address);
    postalField.value = postcode;
    syncCustomerFieldChanges();
    address2Field.focus();
}
function getFormattedStreetAddress(formattedAddress) {
    var addressChunks = formattedAddress.split(', ');
    if (addressChunks.length == 1) {
        return formattedAddress.split(' - ')[0];
    }
    var regexContainsHouseNumber = /\d+/g;
    if (!regexContainsHouseNumber.test(addressChunks[0]) && regexContainsHouseNumber.test(addressChunks[1])) {
        return "".concat(addressChunks[0], ", ").concat(addressChunks[1]);
    }
    return addressChunks[0];
}
function initCustomOrderMessaging() {
    store.subscribe(function () {
        renderCustomOrderMessaging(Environment.plugin.supportMessage(), Environment.modalUI.page());
    });
}
function renderCustomOrderMessaging(supportMessage, page) {
    if (supportMessage && page === 'payment') {
        $qsAll('.pp-custom-order-message', function ($el) { return $el.classList.remove('hide'); });
        var $temp_1 = document.createElement('div');
        $temp_1.innerHTML = supportMessage;
        $temp_1.querySelectorAll('a').forEach(function ($a) { return $a.setAttribute('target', '_blank'); });
        $temp_1.querySelectorAll(':not(a,br,h1,h1,h3,h4,h5,p,div,li,ul,ol,span)').forEach(function ($el) { return $el.remove(); });
        $qsAll('.pp-custom-order-message', function ($el) { return $el.innerHTML = $temp_1.innerHTML; });
    }
    else {
        $qsAll('.pp-custom-order-message', function ($el) { return $el.classList.add('hide'); });
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
                    initLanguage(message);
                    store.dispatch(updateMerchantHostName(message.merchantHostname));
                    installCustomerFormFields(message);
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
                    initOneClickUpsell();
                    initDeliveryDate();
                    initMetrics();
                    initLinkedProducts();
                    initOrderNotes();
                    initCart();
                    initSummary(message);
                    initCouponInput(message);
                    initGiftCardInput(message);
                    initShipping(message);
                    initCustomer(message);
                    initCurrency(message);
                    initMerchantAccount(message);
                    initVAT(message);
                    initRelatedProducts();
                    initAdditionalFields(message);
                    initCurrencySwitcher();
                    initPaymentMethods();
                    initModal(message);
                    initAddressAutocomplete(message);
                    initCustomOrderMessaging();
                    orderService = getOrderService();
                    initFreeOrderSupport(orderService);
                    return [4, initStripePaymentProvider(message, orderService)];
                case 1:
                    _a.sent();
                    return [4, initPayPalPaymentProvider(orderService)];
                case 2:
                    _a.sent();
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
                                    store.dispatch(initilizePrimaryPaymentMethodUI());
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