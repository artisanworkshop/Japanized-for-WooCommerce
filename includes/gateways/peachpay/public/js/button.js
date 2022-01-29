// deno-lint-ignore-file camelcase
let variationID = '';
let loaded = false;
let buttonClickedBeforeLoad = false;
let buttonClickedBeforeLoadID = '';
let ppInitMessageSent = false;
const selectedOutOfStockItems = new Set();
const pendingVariationDetailRequests = 0;

// deno-lint-ignore no-unused-vars
const peachpayVersion = peachpay_data.version;

// In order to make sure the mini cart is always placed put this here and it on
// loading the window will always place it.
if (peachpay_data.hide_mini_cart !== '1' && location.hostname !== 'skregear.com') {
	self.requestAnimationFrame(peachpay_placeButtonMiniCart);
	self.addEventListener('load', peachpay_watchForMiniButtonRemoval);
}

function ppOnWindowDataFetch(endpoint, requestCallback) {
	self.addEventListener('message', async (message) => {
		if (message.data.event === endpoint) {
			try {
				const response = await requestCallback(message.data.request);
				// eslint-disable-next-line unicorn/require-post-message-target-origin
				message.ports[0].postMessage({ result: response });
			} catch (error) {
				// eslint-disable-next-line unicorn/require-post-message-target-origin
				message.ports[0].postMessage({ error });
			}
		}
	});
}

function ppOnWindowMessage(eventName, cb) {
	self.addEventListener('message', async (event) => {
		if (event.data.event === eventName) {
			await cb(event.data);
		}
	}, false);
}

ppOnWindowDataFetch('pp-place-order', placeOrder);
ppOnWindowDataFetch('pp-set-order-status', async (data) => {
	const response = await setOrderStatus(data.order.id, {
		status: data.order.status,
		message: data.order.message,
		paymentMethod: {
			method: data.order.paymentMethod,
			id: data.order.stripeCustomerId,
			transactionID: data.order.paypalTransactionId,
		},
	});

	if (response.ok) {
		return true;
	}

	return false;
});

ppOnWindowDataFetch('pp-validate-billing-address', (data) => validateAddress(formDataFromAddress(data)));

ppOnWindowDataFetch('pp-calculate-cart', async (data) => {
	const formData = new FormData();

	if (!data.initial) {
		for (const methodKey of Object.keys(data.order.selected_shipping)) {
			formData.append(`order[selected_shipping][${methodKey}]`, data.order.selected_shipping[methodKey]);
		}

		formData.append('order[shipping_location][country]', data.order.shipping_location.country);
		formData.append('order[shipping_location][state]', data.order.shipping_location.state);
		formData.append('order[shipping_location][city]', data.order.shipping_location.city);
		formData.append('order[shipping_location][postcode]', data.order.shipping_location.postcode);
	}

	const response = await fetch(`${peachpay_data.wp_site_url}/?wc-ajax=pp-cart`, {
		method: 'POST',
		headers: {
			'credentials': 'same-origin',
		},
		body: formData,
	});

	if (!response.ok) {
		const error = new Error(`Cart calculation failed on ${peachpay_data.wp_site_url}`);
		captureSentryException(error);
		throw error;
	}

	const cartCalculationResponse = await response.json();
	if (cartCalculationResponse.success && cartCalculationResponse.data) {
		peachpay_data.cart = cartCalculationResponse.data.cart_calculation_record[0].cart;
	}

	return cartCalculationResponse;
});

ppOnWindowDataFetch('pp-change-quantity', async (data) => {
	const response = await peachpay_changeQuantity(data);

	if (!response.ok) {
		throw new Error('Quantity failed to change.');
	}

	const responseData = await response.json(); // As ICartCalculationResponse
	return responseData;
});

ppOnWindowMessage('pp-complete-transaction', (message) => {
	if (message.redirectURL) {
		window.location = message.redirectURL;
	}
});

self.addEventListener('message', async (event) => {
	if (event.data === 'openModal') {
		document.querySelector('#peachpay-iframe').classList.remove('hide');
		document.querySelector('#peachpay-iframe').contentWindow.postMessage({ event: 'UI::modalOpened' }, '*');
		document.querySelector('#loading-spinner-iframe').classList.add('hide');
		document.querySelector('#pp-modal-overlay').style.display = 'block';
		document.querySelector('body').style.overflow = 'hidden';

		peachpay_hideLoadingSpinner();
	}

	if (event.data.event === 'peachpayAlert') {
		alert(event.data.message);

		if (event.data.action) {
			switch (event.data.action) {
				case 'closeModal':
					closeModal();
					break;
				default:
					// Do nothing
					break;
			}
		}
	}

	if (event.data === 'closeModal') {
		closeModal();
	}

	if (event.data === 'loaded') {
		loaded = true;
		if (buttonClickedBeforeLoad) {
			const options = {
				isMiniCart: buttonClickedBeforeLoadID === 'pp-button-mini',
				clickID: buttonClickedBeforeLoadID,
			};
			sendButtonClickedMessage(options);
		}
	}

	if (event.data.event === 'fetchCoupon') {
		fetchAndSendCoupon(event);
	}

	if (event.data.event === 'placeOrderDirectly') {
		placeOrderViaWCAjax(event.data);
	}

	if (event.data.event === 'emailExist') {
		const emailResult = await fetchEmailData(event.data.email);
		document.querySelector('#peachpay-iframe').contentWindow.postMessage({
			event: 'emailExist',
			emailResult,
		}, '*');
	}

	if (event.data.event === 'redeemGiftCard') {
		redeemGiftCard(peachpay_data, event.data.cardNumber);
	}

	if (event.data.event === 'validateAddress') {
		validate(formDataFromAddress(event.data.billingAddress));
	}

	if (event.data.event === 'setOrderStatus') {
		const response = await setOrderStatus(event.data.orderID, {
			status: event.data.status,
			message: event.data.message,
			paymentMethod: {
				method: event.data.paymentType,
				id: event.data.customerStripeId,
				transactionID: event.data.transactionID,
			},
		});

		if (response.ok && event.data.redirectURL) {
			window.location = event.data.redirectURL;
		}
	}

	if (event.data.event === 'paypalAlert' && !alert(event.data.message)) {
		document.querySelector('#peachpay-iframe').contentWindow.postMessage({
			event: 'paypalRestart',
		}, '*');
	}

	if (event.data.event === 'addLinkedProduct' || event.data.event === 'addUpsellItem') {
		const message = 'Something went wrong, please try again.';

		if (await peachpay_addLinkedProductToCart(event)) {
			document.querySelector('#peachpay-iframe').contentWindow.postMessage({
				event: 'pp-update-cart',
			}, '*');
		} else {
			alert(message);
		}
	}
});

function closeModal() {
	if (window.location.href.includes('?open_peachpay')) {
		window.location = `${peachpay_data.wc_cart_url}`;
	}

	// This updates the background pages native cart
	if (peachpay_data.is_checkout_page) {
		jQuery(document.body).trigger('update_checkout', { update_shipping_method: false });
	} else {
		jQuery(document.body).trigger('wc_update_cart');
	}

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({ event: 'UI::modalClosed' }, '*');
	document.querySelector('#pp-modal-overlay').style.display = 'none';
	document.querySelector('body').style.overflow = 'auto';
}

function baseStoreURL() {
	return location.hostname === 'localhost' ? `http://${location.hostname}:8000` : `https://${location.hostname}`;
}

function basePeachPayAPIURL(merchantHostname) {
	if (peachpay_data.test_mode) {
		switch (merchantHostname) {
			case 'store.local':
			case 'woo.store.local':
				return 'https://dev.peachpay.local'; // Local https
			default:
				return 'https://dev.peachpay.app';
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
			return 'https://dev.peachpay.app';
		case 'store.local':
		case 'woo.store.local':
			return 'https://prod.peachpay.local'; // Local https
		default:
			return 'https://prod.peachpay.app';
	}
}

/**
 * Initializes the peachpay button.
 *
 * @param { IButtonConfigurationOptions } options Peachpay button configuration options.
 */
// deno-lint-ignore no-unused-vars
function peachpay_initButton(options) {
	peachpay_initButtonContainer(options);
	peachpay_initButtonEvents(options);

	if (ppInitMessageSent) {
		return;
	}

	ppInitMessageSent = true;

	peachpay_sendInitMessage();
}

document.addEventListener('DOMContentLoaded', () => {
	if (document.querySelector('#pp-button').classList.contains('pp-button-float') && peachpay_data.cart.length === 0) {
		const float_button = document.querySelector('#pp-button');

		float_button.addEventListener('click', () => {
			if (peachpay_data.cart.length === 0 && document.querySelector('#empty-cart-msg').classList.contains('hide')) {
				document.querySelector('#empty-cart-msg').classList.remove('hide');
				setTimeout(() => {
					document.querySelector('#empty-cart-msg').classList.add('hide');
				}, 1000);
			}
		});
	}
});

function peachpay_initButtonEvents(options) {
	const button = document.querySelector(`#pp-button${options.isMiniCart ? '-mini' : ''}`);

	// Apply some dynamic styles to the button
	if (!peachpay_isElementor()) {
		button.style.width = options.width || '100%';
		button.style.setProperty('--button-color', peachpay_data.button_color || '#ff876c');
		if (options.borderRadius && !options.isMiniCart) {
			button.style.borderRadius = options.borderRadius.toString() + 'px';
		}
	}

	tryToMatchFontSize(button, options.isMiniCart);

	// It's easier to handle extra cart features such as coupons on the actual
	// cart page since the product pages were not exactly built for that, so
	// redirect to the cart page for these extra features
	if (shouldRedirectToCartPage()) {
		button.addEventListener('click', async () => {
			peachpay_showLoadingSpinner(options.isMiniCart);

			if (!peachpay_validateProductPageForm(options.isMiniCart)) {
				peachpay_hideLoadingSpinner();
				return;
			}

			const success = await peachpay_addProductToCart(options.isMiniCart);

			if (success) {
				window.location = `${peachpay_data.wc_cart_url}?open_peachpay`;
			}

			peachpay_hideLoadingSpinner();
		});
		return;
	}

	if (document.querySelector('#pp-button') && document.querySelector('#pp-button').classList.contains('pp-button-float')) {
		const float_button = document.querySelector('#pp-button');

		float_button.addEventListener('click', (event) => openPeachPay({ event, options }));
	}

	button.addEventListener('click', (event) => openPeachPay({ event, options }));

	// If coming from an extra feature redirect, open up PeachPay right away
	const urlParameters = new URLSearchParams(location.search);
	if (urlParameters.has('open_peachpay')) {
		// Show a loading overlay so it's clear that something is loading
		document.querySelector('#peachpay-iframe').classList.add('hide');
		document.querySelector('#loading-spinner-iframe').classList.remove('hide');
		document.querySelector('#pp-modal-overlay').style.display = 'flex';
		document.querySelector('body').style.overflow = 'hidden';

		openPeachPay({
			alternateEvent: 'pp-button',
			options: options,
		});
	}
}

function tryToMatchFontSize(button, isMiniCart) {
	if (isMiniCart) {
		return;
	}

	const selector = peachpay_data.is_cart_page ? '.wc-proceed-to-checkout .button.checkout-button' : 'button[name="add-to-cart"]';
	const elementToCopy = document.querySelector(selector);
	const buttonIcon = document.querySelector('#button-icon-regular');
	if (elementToCopy) {
		button.style.fontSize = window.getComputedStyle(elementToCopy).fontSize;
		if (window.getComputedStyle(elementToCopy).fontSize > '16px' && buttonIcon) {
			if (peachpay_data.button_icon === 'mountain') {
				buttonIcon.style.height = '18px';
			} else {
				buttonIcon.style.width = '1.5rem';
				buttonIcon.style.height = '1.5rem';
			}
		}
	}
}

/**
 * Check if the the stores's product page button should redirect to cart page.
 * This is used to bypass issues that are difficult to fix on the product
 * page.
 */
function shouldRedirectToCartPage() {
	if (peachpay_data.is_cart_page || peachpay_data.is_checkout_page) {
		return false;
	}

	const sites = new Set([
		'counterattackgame.com',
	]);

	if (sites.has(location.hostname)) {
		return true;
	}

	return false;
}

/**
 * Collects information about what button was click and then sends click message.
 *
 * @param { { event?: Event,alternateEvent?: string, options: IButtonConfigurationOptions} } context The button click context.
 */
function openPeachPay(context) {
	let targetID = '';

	if (context.event) {
		targetID = context.event.currentTarget ? context.event.currentTarget.id : 'redirect';
	} else {
		targetID = context.alternateEvent;
	}

	peachpay_showLoadingSpinner(context.options.isMiniCart);

	if (!loaded || pendingVariationDetailRequests > 0) {
		buttonClickedBeforeLoad = true;
		buttonClickedBeforeLoadID = targetID;
		return;
	}

	context.options.clickID = targetID;
	sendButtonClickedMessage(context.options);
}

/**
 * Initializes the PeachPay button container.
 *
 * @param { IButtonConfigurationOptions } options PeachPay button configuration options.
 */
function peachpay_initButtonContainer(options) {
	if (options.isMiniCart) {
		// They cannot change the position of the button in the mini cart because
		// only the native style looks good
		return;
	}

	const container = document.querySelector('#pp-button-container');
	// On the product page, no position option is provided
	if (!peachpay_isElementor()) {
		container.style.textAlign = options.position || 'inherit';
	}

	container.style.justifyContent = options.position || 'inherit';
}

function peachpay_sendInitMessage() {
	const peachpay = document.querySelector('#peachpay-iframe');

	peachpay.addEventListener('load', () => {
		// For detecting support of peachpay alerts to prevent silent failures of messages
		peachpay_data.alertSupport = true;
		peachpay.contentWindow.postMessage({
			event: 'init',
			merchantHostname: location.hostname,
			isTestMode: peachpay_data.test_mode,
			isCartPage: peachpay_data.is_cart_page,
			isCheckoutPage: peachpay_data.is_checkout_page,
			isGroupedProduct: isGroupedProduct(),
			isMobile: peachpay_isMobile(),
			phpData: peachpay_data,
			browserLocale: navigator.language || 'en-US',
			// This is different from the above because it is what the site
			// owner sets, whereas browserLocale is how the user configures
			// their browser.
			pageLanguage: languageCodeToLocale(getPageLanguage()),
		}, '*');
	});
}

function getPageLanguage() {
	const $html = document.querySelector('html');
	if (!$html) {
		return 'en-US';
	}

	return document.querySelector('html').lang || 'en-US';
}

function peachpay_isProductInWCCart(formData) {
	let isBundleProduct = false;
	// Get variation id from variation product
	let variationId = Number.parseInt(formData.get('variation_id'));
	// Get variation id from bundle product that includes variation id
	for (const key of formData.keys()) {
		if (key.includes('bundle_variation')) {
			isBundleProduct = true;
			variationId = Number.parseInt(formData.get(key));
		}
	}

	// Get product id from product ('add-to-cart' is used in simple product and bundle product)
	const productId = Number.parseInt(formData.get('add-to-cart'));
	for (const item of peachpay_data.cart) {
		if (isBundleProduct) {
			// If current product is part of a bundle and also a variation product, check for variation id
			if (Number.parseInt(item.product_id) === variationId) {
				return true;
			}
		} else if ((Number.parseInt(item.product_id) === variationId || Number.parseInt(item.product_id) === productId) && !item.is_part_of_bundle) {
			return true;
		}
	}

	return false;
}

async function sendButtonClickedMessage(options) {
	const isProductPage = !peachpay_data.is_cart_page && !peachpay_data.is_checkout_page && !options.isMiniCart;
	if (isProductPage || options.isShortcode) {
		if (!peachpay_validateProductPageForm(options)) {
			peachpay_hideLoadingSpinner();
			return;
		}

		if (!peachpay_isProductInWCCart(peachpay_getAddToCartFormData(options))) {
			const success = await peachpay_addProductToCart(options);

			peachpay_hideLoadingSpinner();

			if (!success) {
				return;
			}
		}
	}

	const data = {
		event: 'buttonClicked',
		buttonID: options.clickID,
	};

	const peachpay = document.querySelector('#peachpay-iframe');
	peachpay.contentWindow.postMessage(data, '*');
}

function peachpay_validateProductPageForm(options) {
	if (options.isShortcode) {
		return true;
	}

	if (!checkValidityOf('form.cart')) {
		return false;
	}

	if (!inStockAndVariationSelected(options.isShortcode)) {
		return false;
	}

	return true;
}

function peachpay_insertFragments(fragments) {
	for (const fragmentKey of Object.keys(fragments)) {
		const $target = document.querySelector(fragmentKey);

		if (!$target) {
			continue;
		}

		$target.innerHTML = fragments[fragmentKey];
	}
}

/**
 * Used to update the rest of the native woocommerce page after peachpay cart manipulations. Not async
 * to avoid blocking
 */
function peachpay_refreshFragments(response) {
	if (response) {
		response.json().then((data) => {
			peachpay_insertFragments(data.fragments);
		}).catch(() => {
			// Do no harm.
		});

		return;
	}

	// Using promises and not (await/async) to avoid blocking.
	fetch(`${peachpay_data.wp_site_url}?wc-ajax=get_refreshed_fragments`, {
		method: 'POST',
		time: Date.now(),
	}).then((response) => {
		if (!response.ok) {
			return;
		}

		return response.json();
	}).then((data) => {
		peachpay_insertFragments(data.fragments);
	}).catch(() => {
		// Do no harm.
	});
}

function checkValidityOf(selector) {
	const $form = document.querySelector(selector);
	let result = false;
	if ($form) {
		result = $form.reportValidity();
	}

	return result;
}

function inStockAndVariationSelected(isMiniCart) {
	// Some product pages can have multiple products listed, usually through a plugin
	const multipleProducts = Array.from(document.querySelectorAll('input.variation_id')).length > 1;

	if (selectedOutOfStockItems.size > 0 && !isMiniCart) {
		alert(pp_i18n[`out-of-stock${multipleProducts ? '-bundle' : ''}`][getLanguage()]);
		// We need to reset this, otherwise after seeing the above alert, then selecting
		// a variation, the PeachPay window will open right away which is an unexpected
		// behavior.
		buttonClickedBeforeLoad = false;
		peachpay_hideLoadingSpinner();
		return false;
	}

	if (!variationSelected() && !isMiniCart) {
		alert(pp_i18n[`select-variation${multipleProducts ? '-bundle' : ''}`][getLanguage()]);
		buttonClickedBeforeLoad = false;
		return false;
	}

	return true;
}

/**
 * Since this hidden input field which represents the currently selected
 * variation is changed by WooCommerce JS, there is no 'change' event that is
 * fired. This function is used primarily for that: to dispatch a change event
 * when the input's value is changed by JS. This method also preserves the
 * behavior set by other plugins' code.
 *
 * @param input The input element.
 */
function setDetectChangeHandler(input) {
	const superProps = Object.getPrototypeOf(input);
	const superSet = Object.getOwnPropertyDescriptor(superProps, 'value').set;
	const superGet = Object.getOwnPropertyDescriptor(superProps, 'value').get;
	const newProps = {
		get: function () {
			return Reflect.apply(superGet, this, arguments);
		},
		set: function () {
			setTimeout(() => this.dispatchEvent(new Event('change')), 50);
			return Reflect.apply(superSet, this, arguments);
		},
	};
	Object.defineProperty(input, 'value', newProps);
}

function setCustomVariationChangeHandler(input) {
	setDetectChangeHandler(input);

	input.addEventListener('change', (event) => {
		if (event.target.value === '') {
			return;
		}

		variationID = event.target.value;
		updateOutOfStockSelection(variationID);
	});
}

document.addEventListener('DOMContentLoaded', () => {
	const $variations = Array.from(document.querySelectorAll('.variation_id'));
	for (const $v of $variations) {
		setCustomVariationChangeHandler($v);
	}
});

function updateOutOfStockSelection(variationID) {
	if (collectVariationStock().get(variationID)) {
		// Turn it into an array for easy iteration
		const selectedOutOfStockItemsArray = Array.from(selectedOutOfStockItems);
		for (let i = 0; i < selectedOutOfStockItems.size; i++) {
			const group = collectVariationGroup(selectedOutOfStockItemsArray[i]);
			if (group.includes(variationID) && (variationID !== selectedOutOfStockItemsArray[i])) {
				// A replacement variation has been chosen for a previously selected out-of-stock item,
				// so we can remove the out-of-stock variation from the set
				selectedOutOfStockItems.delete(selectedOutOfStockItemsArray[i]);
			}
		}
	} else {
		selectedOutOfStockItems.add(variationID);
	}
}

/**
 * A fetch function that check if the email exist and returns a result of.
 *
 * @param { string } email The email to check if it exist.
 * @returns  { boolean } 1 if the email exist and false, if the email does not exist.
 */
async function fetchEmailData(email) {
	const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/check/email`, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({
			email: email,
		}),
	});

	const result = await response.json();
	return result.emailExists;
}

function sendStopPaymentProcessingAnimationsMessage(options) {
	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'stopPaymentProcessingAnimations',
		closeModal: (options && options.closeModal) || false,
		errorMessage: (options && options.errorMessage) ? options.errorMessage : null,
	}, '*');
}

/**
 * New Place order function for use with window fetch.
 */
async function placeOrder(data) {
	// Make sure Route Shipping Protection fee by route.com is not added
	// until we add compatibility for it
	if (peachpay_data.plugin_routeapp_active) {
		await removeRouteFee();
	}

	// Field editor resolves need for next line.
	const isBeYourBag = location.hostname === 'www.beyourbag.it' ||
		location.hostname === 'woocommerce-187306-844159.cloudwaysapps.com';

	const formData = formDataFromAddress(data.order.billingAddress);
	formData.append('peachpay_checkout_nonce', peachpay_data.checkout_nonce);
	formData.append('payment_method', data.order.paymentMethod === 'paypal' ? 'peachpay_paypal' : 'peachpay_stripe');
	// Tentatively 1 for future separation of billing/shipping
	formData.append('ship_to_different_address', 1);
	formData.append('terms', isBeYourBag ? 'on' : 1);

	// The following are not found on a standard WooCommerce checkout page, but
	// may be added by themes or plugins. It doesn't hurt if the field is
	// included when not required; it will be ignored.
	formData.append('european_gdpr', 1);
	formData.append('ct-ultimate-gdpr-consent-field', 'on');
	formData.append('ct-ultimate-gdpr-consent-field-additional', '1');
	formData.append('ct-ultimate-gdpr-consent-field-label-text', '1');
	formData.append('delivery_date', data.order.deliveryDate);

	// These are for www.veilevents.com until we find a more permanent solution.
	if (location.hostname === 'www.veilevents.com') {
		formData.append(
			'shipping_company',
			`${data.order.billingAddress.billing_first_name} ${data.order.billingAddress.billing_last_name}`,
		);
		formData.append('additional_where', 'Website');
	}

	if (data.order.merchantCustomerAccountPassword && data.order.merchantCustomerAccountPassword !== '') {
		formData.append('account_password', data.order.merchantCustomerAccountPassword);
	}

	if (data.order.customerOrderNotes && data.order.customerOrderNotes !== '') {
		formData.append('order_comments', data.customerOrderNotes);
	}

	// Set selected shipping options.
	for (const [packageKey, selectedOption] of Object.entries(data.order.shippingMethods)) {
		formData.append(`shipping_method[${packageKey}]`, selectedOption);
	}

	const orderResponse = await fetch(`${baseStoreURL()}/?wc-ajax=pp-create-order`, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	});

	const orderResult = await orderResponse.json();

	if (orderResult.result === 'failure' || orderResult.success === false) {
		if (orderResult.data) {
			const message = `<ul class="woocommerce-error message-wrapper" role="alert">
			<li>
				<div class="message-container container alert-color medium-text-center">
					<span class="message-icon icon-close"></span>
					${orderResult.data}
				</div>
			</li>
			</ul>`;
			orderResult.messages = message;
		}

		const errorContext = {
			type: 'WC-Order',
			merchant: location.hostname,
			sessionID: data.session.id,
			createOrderResponseStatus: orderResponse.status,
			orderResult,
			orderResultErrorText: parseWooCommerceHTMLError(orderResult.messages),
			formData: Object.fromEntries(formData),
			debugCart: peachpay_data.debug_cart,
		};

		await fetch(`${basePeachPayAPIURL(location.hostname)}/api/v1/error-log`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ errorContext }),
		});

		document.querySelector('.woocommerce-notices-wrapper').innerHTML = orderResult.messages;
		sendStopPaymentProcessingAnimationsMessage({ closeModal: true });
		return;
	}

	await removeGiftCardsFromSession();

	// Free orders don't go through WC_PeachPay_Gateway::process_payment, so we don't have
	// a chance to add order details which are used on the server for the payment amount
	// and order number.
	if (!orderResult.details) {
		const id = orderIDFromRedirect(orderResult.redirect);
		orderResult.orderID = id;
		orderResult.number = id;
		orderResult.details = {
			id,
			order_key: orderKeyFromRedirect(orderResult.redirect),
			total: '0',
		};
	}

	return orderResult;
}

// Deno-lint-ignore camelcase
function peachpay_promptPermissions() {
	if (confirm('PeachPay requires permission to place test orders. Would you like to give permission? (Page will redirect)')) {
		window.location = peachpay_data.authorize_url;
	}

	closeModal();
}

/**
 * Places an order directly to the merchant store from the merchant host client
 * @returns { Promise<void> }
 */
async function placeOrderViaWCAjax(data) {
	// Permission is denied we should only prompt for permission instead of placing a test order.
	if (!peachpay_data.has_valid_key) {
		peachpay_promptPermissions();
		return;
	}

	// Make sure Route Shipping Protection fee by route.com is not added
	// until we add compatibility for it
	if (peachpay_data.plugin_routeapp_active) {
		await removeRouteFee();
	}

	const isBeYourBag = location.hostname === 'www.beyourbag.it' ||
		location.hostname === 'woocommerce-187306-844159.cloudwaysapps.com';

	const formData = formDataFromAddress(data.billingAddress);
	formData.append('peachpay_checkout_nonce', peachpay_data.checkout_nonce);

	formData.append('payment_method', data.isPaypal ? 'peachpay_paypal' : 'peachpay_stripe');
	// Tentatively 1 for future separation of billing/shipping
	formData.append('ship_to_different_address', 1);
	formData.append('terms', isBeYourBag ? 'on' : 1);

	// The following are not found on a standard WooCommerce checkout page, but
	// may be added by themes or plugins. It doesn't hurt if the field is
	// included when not required; it will be ignored.
	formData.append('european_gdpr', 1);
	formData.append('ct-ultimate-gdpr-consent-field', 'on');
	formData.append('ct-ultimate-gdpr-consent-field-additional', '1');
	formData.append('ct-ultimate-gdpr-consent-field-label-text', '1');
	formData.append('delivery_date', data.deliveryDate);

	// These are for www.veilevents.com until we find a more permanent solution.
	if (location.hostname === 'www.veilevents.com') {
		formData.append(
			'shipping_company',
			`${data.billingAddress.billing_first_name} ${data.billingAddress.billing_last_name}`,
		);
		formData.append('additional_where', 'Website');
	}

	if (data.merchantCustomerAccountPassword && data.merchantCustomerAccountPassword !== '') {
		formData.append('account_password', data.merchantCustomerAccountPassword);
	}

	if (data.customerOrderNotes && data.customerOrderNotes !== '') {
		formData.append('order_comments', data.customerOrderNotes);
	}

	// Set selected shipping options.
	for (const [packageKey, selectedOption] of Object.entries(data.shipping_method)) {
		formData.append(`shipping_method[${packageKey}]`, selectedOption);
	}

	if (data.additionalFields && data.additionalFields.length > 0) {
		for (const field of data.additionalFields) {
			formData.append(field.name, field.value);
		}
	}

	const orderResponse = await fetch(`${baseStoreURL()}/?wc-ajax=wc_peachpay_create_order`, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	});

	const orderResult = await orderResponse.json();

	if (orderResult.result === 'failure' || orderResult.success === false) {
		if (orderResult.data) {
			const message = `<ul class="woocommerce-error message-wrapper" role="alert">
			<li>
				<div class="message-container container alert-color medium-text-center">
					<span class="message-icon icon-close"></span>
					${orderResult.data}
				</div>
			</li>
			</ul>`;
			orderResult.messages = message;
		}

		const errorContext = {
			type: 'WC-Order',
			merchant: location.hostname,
			sessionID: data.sessionID,
			createOrderResponseStatus: orderResponse.status,
			orderResult,
			orderResultErrorText: parseWooCommerceHTMLError(orderResult.messages),
			formData: Object.fromEntries(formData),
			debugCart: peachpay_data.debug_cart,
		};

		await fetch(`${basePeachPayAPIURL(location.hostname)}/api/v1/error-log`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ errorContext }),
		});

		sendStopPaymentProcessingAnimationsMessage({ closeModal: false, errorMessage: orderResult.messages.replace(/ {2}|\r\n|\n|\r|\t/gm, '') || null });
		return;
	}

	await removeGiftCardsFromSession();

	// Free orders don't go through WC_PeachPay_Gateway::process_payment, so we don't have
	// a chance to add order details which are used on the server for the payment amount
	// and order number.
	if (!orderResult.details) {
		const id = orderIDFromRedirect(orderResult.redirect);
		orderResult.orderID = id;
		orderResult.number = id;
		orderResult.details = {
			id,
			order_key: orderKeyFromRedirect(orderResult.redirect),
			total: '0',
		};
	}

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: data.isPaypal ? 'submitPaypalOrder' : 'submitPayment',
		order: orderResult,
	}, '*');
}

function orderIDFromRedirect(url) {
	const parts = url.split('/');
	return parts[parts.length - 2];
}

function orderKeyFromRedirect(url) {
	const parameters = new URLSearchParams(url.split('?')[1]);
	return parameters.get('key');
}

function formDataFromAddress(address) {
	const formData = new FormData();

	formData.append('billing_first_name', address.billing_first_name);
	formData.append('billing_last_name', address.billing_last_name);
	formData.append('billing_company', address.billing_company);
	formData.append('billing_email', address.billing_email);
	formData.append('billing_phone', address.billing_phone);
	formData.append('billing_country', address.billing_country);
	formData.append('billing_address_1', address.billing_address_1);
	formData.append('billing_address_2', address.billing_address_2);
	formData.append('billing_city', address.billing_city);
	formData.append('billing_state', address.billing_state);
	formData.append('billing_postcode', address.billing_postcode);

	formData.append('shipping_first_name', address.billing_first_name);
	formData.append('shipping_last_name', address.billing_last_name);
	formData.append('shipping_company', address.billing_company);
	formData.append('shipping_country', address.billing_country);
	formData.append('shipping_address_1', address.billing_address_1);
	formData.append('shipping_address_2', address.billing_address_2);
	formData.append('shipping_city', address.billing_city);
	formData.append('shipping_state', address.billing_state);
	formData.append('shipping_postcode', address.billing_postcode);
	formData.append('shipping_phone', address.billing_phone);

	return formData;
}

/**
 * Adds a product to the Woocommerce Cart.
 * @param { IButtonConfigurationOptions } options PeachPay button configuration options.
 * @returns {Promise<boolean>}
 */
async function peachpay_addProductToCart(options) {
	if (options.isMiniCart) {
		return true;
	}

	try {
		const formData = peachpay_getAddToCartFormData(options);
		const response = await fetch(peachpay_data.wp_site_url + '?wc-ajax=add-to-cart', {
			method: 'POST',
			headers: { Accept: 'application/json' },
			body: formData,
		});

		if (!response.ok) {
			return false;
		}

		peachpay_refreshFragments(response);

		return true;
	} catch (error) {
		if (error instanceof Error) {
			captureSentryException(new Error(`Product page "add to cart" failed on ${peachpay_data.wp_site_url}. Error: ` + error.message));
			alert(pp_i18n['place-order-failure'][getLanguage()]);
		}

		return false;
	}
}

function peachpay_getAddToCartFormData(options) {
	if (options.isShortcode) {
		return peachpay_getShortCodeAddToCartFormData(options);
	}

	// Product page form data.
	const $form = document.querySelector('form.cart');
	const formData = new FormData($form);

	// Simple products use the submit button as the "add-to-cart" input. This input is only submitted if the
	// button is clicked so here we add the button input data manually.
	const $submitButton = $form.querySelector('button[name="add-to-cart"]');
	if ($submitButton) {
		formData.append('add-to-cart', $submitButton.value);
	}

	return formData;
}

function setOrderStatus(orderID, { status, message, paymentMethod }) {
	const data = new FormData();
	data.append('order_id', orderID);

	if (paymentMethod.method === 'stripe' || paymentMethod.method === 'Stripe') {
		data.append('_peachpay_stripe_customer_id', paymentMethod.id);
	} else {
		data.append('_peachpay_stripe_customer_id', '');
	}

	if (status === 'wc-processing') {
		data.append('payment_type', paymentMethod.method);
		data.append('transaction_id', paymentMethod.transactionID);
		data.append('action', 'peachpay_wc_ajax_order_payment_complete');
	}

	if (status === 'wc-failed') {
		data.append('action', 'peachpay_wc_ajax_order_failed');
		data.append('payment_failure_reason', message);
	}

	return fetch(peachpay_data.wp_ajax_url, {
		method: 'POST',
		body: data,
	});
}

function parseWooCommerceHTMLError(html) {
	const element = document.createElement('div');
	element.innerHTML = html;
	const iter = document.createNodeIterator(element, NodeFilter.SHOW_TEXT);
	let textNode = iter.nextNode();
	let messageText = '';
	let i = 0;
	while (textNode) {
		const text = textNode.textContent.trim();

		if (text === '' || text === '\t' || text === '\n') {
			textNode = iter.nextNode();
			continue;
		}

		messageText += text;

		if (i > 0) {
			messageText += ' ';
		}

		textNode = iter.nextNode();
		i++;
	}

	return messageText.trim();
}

async function removeRouteFee() {
	const data = new FormData();
	data.append('action', 'woo_get_ajax_data');
	data.append('checkbox', false);
	const response = await fetch(peachpay_data.wp_ajax_url, {
		method: 'POST',
		body: data,
	});
	return response.status === 200;
}

async function validate(formData) {
	const success = await validateAddress(formData);
	const event = success ? 'validateAddressSuccess' : 'hideContinueSpinner';
	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event,
	}, '*');
}

async function validateAddress(formData) {
	const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/checkout/validate`, {
		method: 'POST',
		body: formData,
	});

	if (response.status !== 200) {
		const error = await response.json();
		alert(clean(error.message));
		return false;
	}

	return true;
}

function clean(message) {
	return message
		.replaceAll('Billing', 'Shipping')
		.replaceAll('billing', 'shipping')
		.replace(/(<([^>]+)>)/gi, '');
}

function isGroupedProduct() {
	return document.querySelector('form.cart.grouped_form') !== null;
}

function variationSelected() {
	let $variations = Array.from(document.querySelectorAll('input.variation_id'));

	if (location.hostname === 'www.kidtoes.com' && peachpay_data.is_cart_page === '') {
		const summary = document.querySelector('.summary-inner');
		$variations = summary.querySelectorAll('input.variation_id');
	}

	for (const variation of $variations) {
		if (variation !== null && (variation.value === '0' || variation.value === '')) {
			peachpay_hideLoadingSpinner();
			// A variation must be selected if given the option
			return false;
		}
	}

	return true;
}

// Get a map of all variations to whether that variation is in stock or not
function collectVariationStock() {
	const $forms = Array.from(document.querySelectorAll('.variations_form'));
	const variationInStock = new Map();
	for (const $form of $forms) {
		const variations = JSON.parse($form.dataset.product_variations);
		for (const v of variations) {
			variationInStock.set(String(v.variation_id), v.is_in_stock);
		}
	}

	return variationInStock;
}

// Get all the variations in the group for a single product given one variation ID
function collectVariationGroup(variationID) {
	const $forms = Array.from(document.querySelectorAll('.variations_form'));
	const group = [];
	// Go through all forms in case there are multiple products on the page, like for product bundles
	for (const $form of $forms) {
		let found = false;
		const variations = JSON.parse($form.dataset.product_variations);
		for (const v of variations) {
			if (String(v.variation_id) === variationID) {
				// We found the bundle product that has the given variation ID
				found = true;
			}
		}

		if (found) {
			// Now we add all the variation IDs to the group, which we can use to check
			// if the customer selected a non out-of-stock item after selecting an out-of-stock item
			for (const v of variations) {
				group.push(String(v.variation_id));
			}

			break;
		}
	}

	return group;
}

function peachpay_showLoadingSpinner(isMiniCart) {
	if (isMiniCart) {
		return;
	}

	document.querySelector('#loading-spinner').classList.remove('hide');
	document.querySelector('#pp-button-content').classList.add('hide');

	const $ppButton = document.querySelector('#pp-button');
	if ($ppButton) {
		$ppButton.disabled = true;
	}
}

function peachpay_hideLoadingSpinner() {
	const loadingSpinner = document.querySelector('#loading-spinner');
	if (loadingSpinner) {
		loadingSpinner.classList.add('hide');
	}

	const buttonContent = document.querySelector('#pp-button-content');
	if (buttonContent) {
		buttonContent.classList.remove('hide');
	}

	const $ppButton = document.querySelector('#pp-button');
	if ($ppButton) {
		$ppButton.disabled = false;
	}
}

function spinnerURL() {
	// For now we explicity list out the sites that should have a dark loading
	// symbol because of their light button color
	const dark = location.hostname === 'www.blazecandles.co' ? '-dark' : '';
	return `${peachpay_data.plugin_asset_url}public/img/spinner${dark}.svg`;
}

function peachpay_isMobile() {
	return /Mobi/.test(navigator.userAgent);
}

window.pp_peachpayButton = `
<div id="pp-button-container" class="button-container pp-button-container ${(peachpay_data.test_mode && !peachpay_data.wp_admin_or_editor) ? 'hide' : ''}">
	<div id="pp-stripe-payment-request-btn" style="width: 100%; margin: 5px 0; display: none;">
	</div>
	<button id="pp-button" class="pp-button" type="button" style="display: block;">
		<object
			type="image/svg+xml"
			data="${spinnerURL()}"
			id="loading-spinner"
			class="pp-spinner"
			height="20"
			width="20">
		</object>
		<div id="pp-button-content">
			<span id="pp-button-text">${peachpay_data.button_text}</span>
			<img id="button-icon-regular" class=""/>
		</div>
	</button>
	<div id="payment-methods-container" class="cc-company-logos">
		<img class="${(peachpay_data.paypal) ? 'cc-logo' : 'hide'}" src="${peachpay_data.plugin_asset_url}public/img/marks/paypal.svg"/>
		<img class="cc-logo" src="${peachpay_data.plugin_asset_url}public/img/marks/visa.svg"/>
		<img class="cc-logo" src="${peachpay_data.plugin_asset_url}public/img/marks/amex.svg"/>
		<img class="cc-logo" src="${peachpay_data.plugin_asset_url}public/img/marks/discover.svg"/>
		<img class="cc-logo" src="${peachpay_data.plugin_asset_url}public/img/marks/mastercard.svg"/>
		<img class="cc-logo" src="${peachpay_data.plugin_asset_url}public/img/marks/cc-stripe-brands.svg"/>
	</div>
</div>
`;

window.pp_peachpayButtonMiniCart = `
<div id="pp-button-container-mini" class="button-container pp-button-container ${(peachpay_data.test_mode && !peachpay_data.wp_admin_or_editor) ? 'hide' : ''}">
	<button id="pp-button-mini" class="pp-button" type="button" style="display: block;">
		<div id="pp-button-content-mini">
			<span id="pp-button-text-mini">${peachpay_data.button_text}</span>
			<img id="button-icon-minicart" class=""/>
		</div>
	</button>
	<div id="payment-methods-container-minicart" class="cc-company-logos">
		<img class="${(peachpay_data.paypal) ? 'cc-logo-sidebar' : 'hide'}" src="${peachpay_data.plugin_asset_url}public/img/marks/paypal.svg"/>
		<img class="cc-logo-sidebar" src="${peachpay_data.plugin_asset_url}public/img/marks/visa.svg"/>
		<img class="cc-logo-sidebar" src="${peachpay_data.plugin_asset_url}public/img/marks/amex.svg"/>
		<img class="cc-logo-sidebar" src="${peachpay_data.plugin_asset_url}public/img/marks/discover.svg"/>
		<img class="cc-logo-sidebar" src="${peachpay_data.plugin_asset_url}public/img/marks/mastercard.svg"/>
		<img class="cc-logo-sidebar" src="${peachpay_data.plugin_asset_url}public/img/marks/cc-stripe-brands.svg"/>
	</div>
</div>
`;

window.pp_checkoutForm = `
<div id="pp-modal-overlay" class="pp-overlay">
	<img src="${spinnerURL()}" id="loading-spinner-iframe" class="pp-spinner-iframe hide">
	<iframe id="peachpay-iframe" src="${peachpay_data.plugin_asset_url}public/dist/${peachpay_data.version}/checkout-modal/peachpay-checkout.html">
		Unable to load PeachPay Checkout
	</iframe>
</div>
<div class="peachpay-translate-helper" style="height:0;width:0;overflow:hidden;">translate me</div>

<style>
#peachpay-iframe {
	width: 100%;
	height: 100%;
	border: none;
}
.pp-overlay {
	background: rgba(40, 40, 40, 0.75);
	display: none;
	justify-content: center;
	position: fixed;
	width: 100%;
	height: 100%;
	z-index: 2147483647;
	overscroll-behavior: contain;
	top: 0; right: 0; bottom: 0; left: 0;
}
</style>
`;

// Per site customization to make the buttons look as native as possible
document.addEventListener('DOMContentLoaded', () => peachpay_addCustomMerchantStyles());

function peachpay_addCustomMerchantStyles() {
	// The setting button_sheen was flipped so that the default is on, and its
	// presence as a truthy value indicates that the shine should be turned off.
	if (!peachpay_isElementor() && !peachpay_data.button_sheen) {
		peachpay_buttonShine();
	}

	if (document.querySelector('#payment-methods-container') && peachpay_data.button_hide_payment_method_icons) {
		document.querySelector('#payment-methods-container').classList.add('hide');
	}

	if (document.querySelector('#button-icon-regular')) {
		update_buttonIcon(peachpay_data.button_icon, 'regular');
	}

	if (location.hostname === 'www.traveltek.com.au') {
		peachpay_traveltek();
	}

	if (location.hostname === 'katekos.com') {
		peachpay_katekos();
	}

	if (location.hostname === 'skregear.com') {
		peachpay_skregear();
	}

	if (location.hostname === 'waterluxe-osmosis.es') {
		document.querySelector('#pp-button-text').textContent = 'Compra ahora';
	}

	if (location.hostname === 'www.enotecacorsetti.com') {
		removeBorderRadius();
	}

	if (location.hostname === 'agirlsbestfriend.ie') {
		removeBorderRadius();
	}

	if (location.hostname === 'salafibookstore.com') {
		peachpay_salafibookstore();
	}

	if (location.hostname === 'scrummysweets.co') {
		const button = document.querySelector('#pp-button-text');
		if (!button) {
			return;
		}

		button.textContent = 'PRE-ORDER NOW';
	}

	if (location.hostname === 'www.irish-pure.de') {
		document.querySelector('#pp-button-text').textContent = 'Kreditkarte';
	}

	if (location.hostname === 'www.bimbiallamoda.com') {
		const button = document.querySelector('#pp-button-text');
		if (button) {
			button.textContent = 'Acquista con 1 clic';
		}
	}

	if (location.hostname === 'airthreds.com') {
		peachpay_airthreds();
	}

	if (location.hostname === 'www.kidtoes.com') {
		peachpay_kidtoes();
	}

	if (location.hostname === 'rahimsapphire.co.uk') {
		peachpay_rahimsapphire();
	}

	if (location.hostname === 'strandsofhumanity.com') {
		peachpay_strandsofhumanity();
	}

	if (location.hostname === 'www.blazecandles.co') {
		peachpay_blazeCandle();
	}

	if (location.hostname === 'www.grandbazaarist.com') {
		peachpay_grandbazaarist();
	}

	pp_placeButtonCustomCheckoutPage();
}

function peachpay_buttonShine() {
	document.querySelector('body').insertAdjacentHTML('beforeend', peachpay_buttonShineCSS);
}

const peachpay_buttonShineCSS = `
<style>
@keyframes shine {
	100% {
		left: 200%;
	}
}
#pp-button:after,
#pp-button-mini:after {
	animation: shine 5s ease infinite;
	background: linear-gradient(to right, rgba(255,255,255,0), rgba(255, 255, 255, 0.3), rgba(255,255,255,0));
	content: '';
	display: inherit;
	height: 200%;
	left: -200%;
	position: absolute;
	top: 0;
	transform: skewX(-20deg);
	width: 40%;
}
</style>`;

function peachpay_traveltek() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	peachpayButton.style.cssText +=
		';font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol" !important;';
	peachpayButton.style.cssText += ';text-transform: uppercase !important';
	peachpayButton.style.fontSize = '12px';

	if (peachpay_data.is_cart_page) {
		peachpayButton.style.fontSize = '1.25em';
	}
}

function peachpay_katekos() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	peachpayButton.style.cssText += 'text-transform: uppercase !important;';
	peachpayButton.style.fontSize = '20px';
	peachpayButton.style.cssText += ';font-family: Raleway !important;';
	peachpayButton.style.fontWeight = 400;
	peachpayButton.style.padding = peachpay_data.is_cart_page ? '10px 0' : '15px';
}

function peachpay_skregear() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	peachpayButton.style.borderRadius = '0';
	peachpayButton.style.fontSize = '16px';
	peachpayButton.style.fontWeight = 700;
	peachpayButton.style.cssText += ';font-family: Lato !important;';
	peachpayButton.style.cssText += 'text-transform: uppercase !important;';

	if (peachpay_data.is_cart_page) {
		peachpayButton.style.fontSize = '0.97em';
		peachpayButton.style.padding = '0.62em';
	}

	// Scrolling issue fix
	document.querySelector('html').style['overflow-x'] = 'visible';
}

function removeBorderRadius() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	peachpayButton.style.borderRadius = '0';
}

function peachpay_salafibookstore() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	peachpayButton.style.borderRadius = '0';
	peachpayButton.style.cssText += 'text-transform: uppercase !important;';
}

function peachpay_airthreds() {
	const button = document.querySelector('#pp-button');
	if (!button) {
		return;
	}

	button.style.borderRadius = '50px';
	button.style.fontSize = '20px';
	button.style.fontWeight = 'inherit';
	button.style.height = '49px';
	button.style.padding = '0';
	button.style.cssText += ';text-transform: none !important;';
	button.style.cssText += ';font-family: inherit !important;';
	if (peachpay_isMobile()) {
		button.style.cssText += ';width: 100% !important;';
	}

	const buttonContainer = document.querySelector('#pp-button-container');
	buttonContainer.style.display = peachpay_isMobile() ? 'block' : 'inline';
	buttonContainer.style.verticalAlign = 'bottom';
	buttonContainer.style.marginLeft = peachpay_isMobile() || peachpay_data.is_cart_page ? '0' : '20px';

	const addToCartButton = document.querySelector('.single_add_to_cart_button');
	if (addToCartButton) {
		addToCartButton.style.display = 'block';
		addToCartButton.style.marginTop = '1.5rem';
	}

	const quantity = document.querySelector('form.cart .quantity');
	if (quantity) {
		quantity.style.width = '100%';
	}
}

function peachpay_kidtoes() {
	const buttonContainer = document.querySelector('#pp-button-content');
	if (!buttonContainer) {
		return;
	}

	buttonContainer.style.color = '#ffffff';
}

function peachpay_rahimsapphire() {
	const button = document.querySelector('#pp-button');
	if (button) {
		button.classList.add('rahimsapphire');
		button.style.cssText += ';text-transform: none !important;';
		document.querySelector('#pp-button-text').textContent = 'Quick Checkout';
	}

	const miniButton = document.querySelector('#pp-button-mini');
	if (miniButton) {
		document.querySelector('#pp-button-text-mini').textContent = 'Quick Checkout';
		miniButton.classList.add('rahimsapphire');
		miniButton.style.paddingTop = '16px';
		miniButton.style.paddingBottom = '16px';
		miniButton.style.fontSize = '100%';
		miniButton.style.transition = 'all .2s linear';
		miniButton.style.cssText += ';text-transform: none !important;';
	} else {
		self.requestAnimationFrame(peachpay_rahimsapphire);
	}
}

function peachpay_strandsofhumanity() {
	const sidebarButton = document.querySelector('#pp-button-mini');
	if (sidebarButton) {
		document.querySelector('#pp-button-text-mini').textContent = 'Express Checkout';
		sidebarButton.style.fontSize = '18px';
		sidebarButton.style.cssText += ';font-family: "Playfair Display", serif !important;';
	}
}

function peachpay_blazeCandle() {
	const button = document.querySelector('#pp-button');
	if (!button) {
		return;
	}

	if (peachpay_data.is_cart_page) {
		button.style.fontWeight = 'normal';
		button.classList.add('blazecandle');
		button.style.setProperty('--button-color', 'none');
		button.style.background = 'none';
		button.style.color = 'black';
		button.style.border = '1px solid #121212';
		button.style.borderRadius = '100px';
	}
}

function peachpay_grandbazaarist() {
	const sidebarButton = document.querySelector('#pp-button-mini');
	if (sidebarButton) {
		sidebarButton.style.marginRight = '1em';
		sidebarButton.style.cssText += ';width: 95% !important;';
	}
}

// deno-lint-ignore no-unused-vars
function peachpayTestMode() {
	console.log('Peachpay Version: ' + peachpay_data.version);

	if (document.querySelector('#pp-button-container')) {
		document.querySelector('#pp-button-container').classList.remove('hide');
	}

	if (document.querySelector('#pp-button-container-mini')) {
		document.querySelector('#pp-button-container-mini').classList.remove('hide');
	}
}

/**
 * Assign css selectors and different svg source to the two different PeachPay
 * button types (regular and mini-cart).
 *
 * @param {string} icon_value
 * @param {string} buttonType
 */
function update_buttonIcon(icon_value, buttonType) {
	const icons = document.querySelector('#button-icon-' + buttonType);
	if (icons) {
		if (buttonType === 'regular') {
			icons.classList.remove('skre-icon');
			icons.classList.add('pp-btn-symbol');
		} else {
			icons.classList.remove('skre-icon-mini');
			icons.classList.add('pp-btn-symbol-mini');
		}

		icons.classList.remove('hide');

		const beyourbag = location.hostname === 'www.beyourbag.it';

		switch (icon_value) {
			case 'lock':
				icons.src = `${peachpay_data.plugin_asset_url}public/img/lock${beyourbag ? '-black' : ''}.svg`;
				break;
			case 'baseball':
				icons.src = `${peachpay_data.plugin_asset_url}public/img/baseball-ball-solid.svg`;
				break;
			case 'arrow':
				icons.src = `${peachpay_data.plugin_asset_url}public/img/chevron-circle-right-solid.svg`;
				break;
			case 'mountain':
				if (buttonType === 'regular') {
					icons.classList.remove('pp-btn-symbol');
					icons.classList.add('skre-icon');
				} else {
					icons.classList.remove('pp-btn-symbol-mini');
					icons.classList.add('skre-icon-mini');
				}

				icons.src = `${peachpay_data.plugin_asset_url}public/img/skre.svg`;
				break;
			case 'bag':
				icons.src = `${peachpay_data.plugin_asset_url}public/img/briefcase-solid.svg`;
				break;
			default:
				icons.classList.add('hide');
		}
	}
}