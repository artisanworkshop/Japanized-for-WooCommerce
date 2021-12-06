let variationID = '';
let loaded = false;
let buttonClickedBeforeLoad = false;
let buttonClickedBeforeLoadID = '';
const selectedOutOfStockItems = new Set();
let pendingVariationDetailRequests = 0;
// Deno-lint-ignore no-unused-vars
const peachpayVersion = php_data.version;

function ppOnWindowDataFetch(endpoint, requestCallback) {
	self.addEventListener('message', async message => {
		if (message.data.event === endpoint) {
			try {
				const response = await requestCallback(message.data.request);
				// eslint-disable-next-line unicorn/require-post-message-target-origin
				message.ports[0].postMessage({result: response});
			} catch (error) {
				// eslint-disable-next-line unicorn/require-post-message-target-origin
				message.ports[0].postMessage({error});
			}
		}
	});
}

function ppOnWindowMessage(eventName, cb) {
	self.addEventListener('message', async event => {
		if (event.data.event === eventName) {
			await cb(event.data);
		}
	}, false);
}

ppOnWindowDataFetch('pp-place-order', placeOrder);
ppOnWindowDataFetch('pp-set-order-status', async data => {
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

ppOnWindowDataFetch('pp-validate-billing-address', data => validateAddress(formDataFromAddress(data)));

ppOnWindowMessage('pp-complete-transaction', message => {
	if (message.redirectURL) {
		window.location = message.redirectURL;
	}
});

self.addEventListener('message', async event => {
	if (event.data === 'openModal') {
		document.querySelector('#peachpay-iframe').classList.remove('hide');
		document.querySelector('#peachpay-iframe').contentWindow.postMessage({event: 'UI::modalOpened'}, '*');
		document.querySelector('#loading-spinner-iframe').classList.add('hide');
		document.querySelector('#pp-modal-overlay').style.display = 'block';
		document.querySelector('body').style.overflow = 'hidden';
		window.scrollTo(0, 0);
		hideLoadingSpinner();
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
			sendButtonClickedMessage(buttonClickedBeforeLoadID === 'pp-button-mini', {clickID: buttonClickedBeforeLoadID});
		}
	}

	if (event.data === 'cartReady') {
		// Need to retrigger this for special items with 30+ variations where the
		// variations load async and the first variation change message might arrive
		// before the init message
		// eslint-disable-next-line unicorn/no-lonely-if
		if (variationID) {
			sendVariationChangedMessage(variationID);
		}
	}

	if (event.data.event === 'fetchCartFees') {
		fetchAndSendCartFees(event);
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

	// The event "applyCoupon" is deprecated. The call to applyCoupon inside
	// fetchAndSendCoupon replaces this call in newer plugin versions where we
	// place the order before the payment.
	if (event.data.event === 'applyCoupon') {
		if (!(await applyCoupon(event.data.code))) {
			return;
		}

		appliedCouponCodes.push(event.data.code);
	}

	if (event.data.event === 'redeemGiftCard') {
		redeemGiftCard(php_data, event.data.cardNumber);
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
		const {
			success,
			newCheckoutNonce,
		} = await addLinkedProducttoCart(event);

		php_data._wpnonce = newCheckoutNonce;

		if (success) {
			const response = await fetch('/?wc-ajax=get_refreshed_fragments', {
				method: 'POST',
				time: Date.now(),
			});
			if (response.status === 200) {
				updateCart();
			} else {
				alert(message);
			}
		} else {
			alert(message);
		}
	}

	if (event.data.event === 'changeQuantity') {
		const message = 'Sorry, something went wrong while updating the quantity. Please try again.';

		const response = await ppChangeQuantity(event);

		if (response) {
			updateCart();
		} else {
			alert(message);
		}
	}
});

function closeModal() {
	if (window.location.href.includes('?open_peachpay')) {
		window.location = `${php_data.wc_cart_url}`;
	}

	// This updates the background pages native cart
	if (php_data.is_checkout_page) {
		jQuery(document.body).trigger('update_checkout', {update_shipping_method: false});
	} else {
		jQuery(document.body).trigger('wc_update_cart');
	}

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({event: 'UI::modalClosed'}, '*');
	document.querySelector('#pp-modal-overlay').style.display = 'none';
	document.querySelector('body').style.overflow = 'auto';
}

function baseStoreURL() {
	return location.hostname === 'localhost'
		? `http://${location.hostname}:8000`
		: `https://${location.hostname}`;
}

function baseURL(merchantHostname) {
	if (php_data.test_mode) {
		switch (merchantHostname) {
			case 'localhost':
			case '127.0.0.1':
				return 'http://localhost:8080';
			case 'store.local':
			case 'woo.store.local':
				return 'https://dev-connect.peachpay.local'; // Local HTTPS
			default:
				return 'https://dev-connect.peachpaycheckout.com';
		}
	}

	switch (merchantHostname) {
		case 'localhost':
		case '127.0.0.1':
			return 'http://localhost:8080';
		case 'woo.peachpay.app':
		case 'theme1.peachpay.app':
		case 'theme2.peachpay.app':
		case 'theme3.peachpay.app':
		case 'theme4.peachpay.app':
		case 'theme5.peachpay.app':
		case 'qa.peachpay.app':
		case 'demo.peachpay.app':
			return 'https://dev-connect.peachpaycheckout.com';
		case 'store.local':
		case 'woo.store.local':
			return 'https://connect.peachpay.local'; // Local HTTPS
		default:
			return 'https://connect.peachpaycheckout.com';
	}
}

function basePeachPayAPIURL(merchantHostname) {
	if (php_data.test_mode) {
		switch (merchantHostname) {
			case 'store.local':
			case 'woo.store.local':
				return 'https://dev.peachpay.local';// Local https
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
			return 'https://prod.peachpay.local';// Local https
		default:
			return 'https://prod.peachpay.app';
	}
}

// Deno-lint-ignore no-unused-vars
function init(options) {
	initContainer(options);
	initButton(options);

	// This fixes a bug introduced by the use of MutationObserver in product-page.js
	// to reattach the sidebar cart button after the sidebar refreshes its content.
	// Without this, two submit event listeners will be added to the pay button, causing
	// duplicate payments in some scenarios.
	if (location.hostname === 'skregear.com' && options.isMiniCart) {
		return;
	}

	sendInitMessage();

	const groupedQuantity = document.querySelector('form.cart.grouped_form');
	if (groupedQuantity) {
		for (const item of php_data.cart) {
			const quantityForm = document.querySelector(`form.cart input[name="quantity[${item.product_id}]"]`);
			if (quantityForm) {
				quantityForm.addEventListener('change', () => sendGroupedQuantityChangedMessage(item.product_id));
			}
		}
	}

	const quantity = document.querySelector('form.cart input[name="quantity"]');
	if (!quantity || location.href.includes('cart')) {
		return;
	}

	quantity.addEventListener('change', sendQuantityChangedMessage);
	if (location.hostname.includes('farm2forkdelivery.ca')) {
		quantity.addEventListener('change', updateButtonDisabledProductPage);
	}
}

function initButton(options) {
	const button = document.querySelector(`#pp-button${options.isMiniCart ? '-mini' : ''}`);

	// Apply some dynamic styles to the button
	if (!peachpay_isElementor()) {
		button.style.width = options.width || '100%';
		button.style.setProperty('--button-color', php_data.button_color || '#ff876c');
		if (options.borderRadius && !options.isMiniCart) {
			button.style.borderRadius = options.borderRadius.toString() + 'px';
		}
	}

	// The candle site button has custom CSS which gets messed up with our !important on the button color
	if (location.hostname === 'www.blazecandles.co') {
		button.style.cssText += ';background: #fff !important;';
	}

	if (location.hostname.includes('farm2forkdelivery.ca')) {
		updateButtonDisabledProductPage();
	}

	tryToMatchFontSize(button, options.isMiniCart);

	// It's easier to handle extra cart features such as coupons on the actual
	// cart page since the product pages were not exactly built for that, so
	// redirect to the cart page for these extra features
	if (shouldRedirectToCartPage()) {
		button.addEventListener('click', async () => {
			const $ppButton = document.querySelector('#pp-button');
			if ($ppButton) {
				$ppButton.disabled = true;
			}

			showLoadingSpinner(options.isMiniCart);

			if (!inStockAndVariationSelected(options.isMiniCart)) {
				return;
			}

			const {
				success,
				newCheckoutNonce,
			} = await addCurrentProductToCart({
				isMiniCart: options.isMiniCart,
			});

			php_data._wpnonce = newCheckoutNonce;

			if (success) {
				window.location = `${php_data.wc_cart_url}?open_peachpay`;
			} else {
				hideLoadingSpinner();
				if ($ppButton) {
					$ppButton.disabled = false;
				}
			}
		});
		return;
	}

	button.addEventListener('click', event => openPeachPay({event}));

	// If coming from an extra feature redirect, open up PeachPay right away
	const urlParameters = new URLSearchParams(location.search);
	if (urlParameters.has('open_peachpay')) {
		// Show a loading overlay so it's clear that something is loading
		document.querySelector('#peachpay-iframe').classList.add('hide');
		document.querySelector('#loading-spinner-iframe').classList.remove('hide');
		document.querySelector('#pp-modal-overlay').style.display = 'flex';
		document.querySelector('body').style.overflow = 'hidden';
		window.scrollTo(0, 0);

		openPeachPay({alternateEvent: 'pp-button'});
	}
}

function tryToMatchFontSize(button, isMiniCart) {
	if (isMiniCart) {
		return;
	}

	const selector = php_data.is_cart_page ? '.wc-proceed-to-checkout .button.checkout-button' : 'button[name="add-to-cart"]';
	const elementToCopy = document.querySelector(selector);
	const buttonIcon = document.querySelector('#button-icon-regular');
	if (elementToCopy) {
		button.style.fontSize = window.getComputedStyle(elementToCopy).fontSize;
		if (window.getComputedStyle(elementToCopy).fontSize > '16px' && buttonIcon) {
			if (php_data.button_icon === 'mountain') {
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
	if (php_data.is_cart_page || php_data.is_checkout_page) {
		return false;
	}

	if (php_data.plugin_woocommerce_points_and_rewards_active) {
		return true;
	}

	const sites = new Set([
		'tires.pneupress.com',
		'skregear.com',
		'1874northwich.com',
		'airthreds.com',
		'counterattackgame.com',
	]);

	if (sites.has(location.hostname)) {
		return true;
	}

	return false;
}

function openPeachPay(options) {
	let targetID = '';

	if (options.event) {
		targetID = options.event.currentTarget ? options.event.currentTarget.id : 'redirect';
	} else {
		targetID = options.alternateEvent;
	}

	const isMiniCart = targetID === 'pp-button-mini';

	showLoadingSpinner(isMiniCart);

	if (!loaded || pendingVariationDetailRequests > 0) {
		buttonClickedBeforeLoad = true;
		buttonClickedBeforeLoadID = targetID;
		return;
	}

	sendButtonClickedMessage(isMiniCart, {clickID: targetID});
}

function initContainer(options) {
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

function sendInitMessage() {
	const peachpay = document.querySelector('#peachpay-iframe');
	// Ideally we should source this from wp_options. It's gone through several
	// iterations, we have always chosen the wrong thing to derive the host from.
	// In this case, WP sites can be hosted in a subdirectory. This is just a
	// quick fix for one site.
	const merchantHostname = location.hostname;

	peachpay.addEventListener('load', () => {
		// For detecting support of peachpay alerts to prevent silent failures of messages
		php_data.alertSupport = true;
		peachpay.contentWindow.postMessage({
			event: 'init',
			merchantHostname,
			isTestMode: php_data.test_mode,
			isCartPage: php_data.is_cart_page,
			isCheckoutPage: php_data.is_checkout_page,
			isGroupedProduct: isGroupedProduct(),
			isMobile: peachpay_isMobile(),
			phpData: php_data,
			quantity: isGroupedProduct() ? 0 : quantity(),
			overriddenCurrency: getOverriddenCurrency() || php_data.currency_info.overridden_code,
			browserLocale: navigator.language || 'en-US',
			// This is different from the above because it is what the site
			// owner sets, whereas browserLocale is how the user configures
			// their browser.
			pageLanguage: languageCodeToLocale(getPageLanguage()),
		}, '*');
	});
}

// Compatibility for currency switcher plugins
function getOverriddenCurrency() {
	const alg = document.querySelector('#alg_currency_select');
	if (alg) {
		return alg.options[alg.selectedIndex].value;
	}

	const wcj = document.querySelector('#wcj-currency-select');
	if (wcj) {
		return wcj.options[wcj.selectedIndex].value;
	}

	// Works on both cart and product page
	if (window.yayCurrency && window.yayCurrency.shortCode) {
		const html = yayCurrency.shortCode.trim();
		const $template = document.createElement('template');
		$template.innerHTML = html;
		const $yay = $template.content.firstChild;
		if ($yay.querySelector('option[selected]')) {
			return $yay.querySelector('option[selected]').label.split(' - ').pop();
		}
	}

	return null;
}

function getPageLanguage() {
	const $html = document.querySelector('html');
	if (!$html) {
		return 'en-US';
	}

	return document.querySelector('html').lang || 'en-US';
}

function sendButtonClickedMessage(isMiniCart, options) {
	if (isGroupedProduct() && !validGroupedQuantity()) {
		checkValidityOf('form.cart.grouped_form');
		return;
	}

	if (!isGroupedProduct() && !validQuantity()) {
		checkValidityOf('form.cart');
		return;
	}

	if (!inStockAndVariationSelected(isMiniCart)) {
		return;
	}

	if (!isMiniCart) {
		triggerQuantityChange();
	}

	const peachpay = document.querySelector('#peachpay-iframe');
	const data = {
		event: 'buttonClicked',
		// Deprecated because variation is now checked before the message is sent
		variationSelected: true,
		// Deprecated because stock checking is handled before the message is sent
		inStock: true,
		quantity: isGroupedProduct() ? 0 : quantity(),
		isMiniCart,
		buttonID: options.clickID,
		updatedCart: php_data.cart,
	};
	if (!isMiniCart && !php_data.is_cart_page && php_data.plugin_woocommerce_product_addon) {
		addPPOMFieldsToProductMeta();
		// This is really only for the meta_data because at this point the checkout
		// window may have made some modifications to its own phpData.cart
		data.updatedCart = php_data.cart;
	}

	if (!isMiniCart && !php_data.is_cart_page) {
		if (!validateRequiredWCProductAddons()) {
			return;
		}

		data.updatedCart = addWCProductAddons(data.updatedCart);
	}

	peachpay.contentWindow.postMessage(data, '*');
}

function validateRequiredWCProductAddons() {
	const $cartForm = document.querySelector('form.cart');

	// If the form is not found lets just not break things and assume the validation is ok.
	if (!$cartForm) {
		return true;
	}

	if ($cartForm.reportValidity()) {
		return true;
	}

	hideLoadingSpinner();
	return false;
}

function checkValidityOf(selector) {
	const form = document.querySelector(selector);
	if (form) {
		form.reportValidity();
	}

	hideLoadingSpinner();
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
		hideLoadingSpinner();
		return false;
	}

	if (!variationSelected() && !isMiniCart) {
		alert(pp_i18n[`select-variation${multipleProducts ? '-bundle' : ''}`][getLanguage()]);
		buttonClickedBeforeLoad = false;
		return false;
	}

	return true;
}

function triggerQuantityChange() {
	// Some themes somehow update the quantity without triggering change
	// or input events, so we need to trigger the event manually to make sure
	// we get the most recent quantity
	const input = document.querySelector('form.cart input[name="quantity"]');
	if (input) {
		input.dispatchEvent(new Event('change'));
	}

	const groupedQuantity = document.querySelector('form.cart.grouped_form');
	if (groupedQuantity) {
		for (const item of php_data.cart) {
			const quantityForm = document.querySelector(`form.cart input[name="quantity[${item.product_id}]"]`);
			if (quantityForm) {
				quantityForm.dispatchEvent(new Event('change'));
			}
		}
	}
}

function addWCProductAddons(cart) {
	if (!cart[0] || !cart[0].wc_addons_options || cart[0].wc_addons_options.length === 0) {
		return;
	}

	// Making a copy of the cart to avoid side effects.
	const updatedCart = JSON.parse(JSON.stringify(cart));

	const originalPrice = Number.parseFloat(updatedCart[0].price);

	updatedCart[0].wc_addons = [];
	if (!updatedCart[0].meta_data) {
		updatedCart[0].meta_data = [];
	}

	for (const addon of updatedCart[0].wc_addons_options) {
		const $inputs = Array.from(document.querySelectorAll(`[name="addon-${addon.field_name}"],[name="addon-${addon.field_name}[]"]`));
		if ($inputs.length === 0) {
			continue;
		}

		for (const $input of $inputs) {
			if ($input.value === '' || ($input.getAttribute('type') === 'checkbox' && !$input.checked)) {
				continue;
			}

			const addonOption = getAddonOption(addon, $input);
			const addonPrice = Number.parseFloat(addonOption.price) || (addon.type === 'custom_price' ? 1 : Number.parseFloat(addon.price)) || 0;
			// Update the cart item price appropriately
			let additionalCost = 0;
			switch (addon.price_type === 'quantity_based' ? addon.price_type : addonOption.price_type) {
				case 'percentage_based':
					// This is always based on the original price
					additionalCost = originalPrice * (addonPrice / 100);
					break;
				case 'quantity_based':
					additionalCost = addonPrice * getQuantityTypeValue(addon, $input);
					break;
				case 'flat_fee':
					additionalCost = addonPrice;
					break;
				default:
					break;
			}

			updatedCart[0].price = (Number.parseFloat(updatedCart[0].price) + additionalCost).toFixed(2);
			// Add meta data entries for rendering line item details
			updatedCart[0].meta_data.push({
				key: additionalCost > 0 ? `${addon.name} (${formatCurrencyString(additionalCost)})` : addon.name,
				value: addonOption.label || $input.value,
			});

			// Add addon meta data for tax
			updatedCart[0].wc_addons.push({
				name: addon.name,
				value: addon.type === 'custom_price' ? '' : addonOption.label || $input.value,
				price: addon.price_type === 'quantity_based' ? addonPrice * (Number.parseInt($input.value)) : addonPrice,
				field_name: addon.field_name,
				field_type: addon.type,
				price_type: addonOption.price_type,
			});
		}
	}

	return updatedCart;
}

function getAddonOption(addon, $input) {
	if (addon.options) {
		// Gets the index of the addon option or defaults to 0
		const optionIndex = $input.value.split('-').length === 1 ? 0 : (Number.parseInt($input.value.split('-').slice(-1).pop()) - 1);
		return addon.options[optionIndex];
	}

	return {// Always return atleast a dummy option. Fallbacks are already in place
		label: '',
		price: '',
		image: '',
		price_types: addon.price_type,
	};
}

function formatCostString(cost) {
	const thousandSeparator = php_data.currency_info.thousands_separator || ',';
	const decimalSeparator = php_data.currency_info.decimal_separator || '.';
	const rounding = php_data.currency_info.rounding || 'disabled';
	const numberOfDecimals = php_data.currency_info.number_of_decimals || 2;

	cost = Number.parseFloat(cost);
	switch (rounding) {
		case 'up':
			cost = Math.ceil(cost);
			break;
		case 'down':
			cost = Math.floor(cost);
			break;
		case 'nearest':
			// In the future support may need added for rounding to a specific 0.50, 0.10 type situation. That would go here.
			cost = Math.round(cost);
			break;
		default:
			break;
	}

	// This line is to ensure if the decimal length is zero and no rounding is done then the price is shown accurately because
	// we default to 2 decimals of zero when decimal length is zero(It looks better).
	cost = Number.parseFloat(cost.toFixed(php_data.currency_info.number_of_decimals));

	let formattedPrice = '';
	try {
		const currencySplit = cost.toFixed(numberOfDecimals).split('.');
		let dollarAmount = currencySplit[0];
		const centsAmount = currencySplit[1] || '';

		// We reverse the string because we want to chunk numbers from back of  the string and not front. Eases the operation of chunking.
		const rev = currencySplit[0].split('').reverse().join('');
		const revFormat = rev.match(/.{1,3}/g).join(thousandSeparator);
		dollarAmount = revFormat.split('').reverse().join('');
		formattedPrice += dollarAmount;
		if (centsAmount !== '') {
			formattedPrice += decimalSeparator + centsAmount;
		}

		return formattedPrice;
		// eslint-disable-next-line unicorn/prefer-optional-catch-binding
	} catch (_) {
		return cost;
	}
}

function formatCurrencyString(cost) {
	const currencyInfo = {
		position: php_data.currency_info.position || 'left',
		symbol: php_data.currency_info.symbol || '$',
	};

	let formattedCurrency = '';
	if (currencyInfo.position === 'left' || currencyInfo.position === 'left_space') {
		let negative = '';
		let formattedCost = formatCostString(cost);
		if (Number.parseFloat(cost) < 0) {
			negative = '-';
			formattedCost = formatCostString(Math.abs(Number.parseFloat(cost)));
		}

		formattedCurrency = `${negative}${currencyInfo.symbol}${formattedCost}`;
	} else {
		formattedCurrency = `${formatCostString(cost)}${currencyInfo.symbol}`;
	}

	return formattedCurrency;
}

function getQuantityTypeValue(addon, $input) {
	if (addon.type === 'multiple_choice') {
		return 1;
	}

	return (Number.parseInt($input.value) || 0);
}

function addPPOMFieldsToProductMeta() {
	const $inputs = Array.from(document.querySelectorAll('input.ppom-input'));
	if ($inputs.length === 0) {
		return;
	}

	// These custom fields can only appear on a single product page, or at least
	// that's the assumption
	php_data.cart[0].meta_data = [];
	for (const $input of $inputs) {
		php_data.cart[0].meta_data.push({
			key: $input.id,
			value: $input.value,
		});
	}
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

	input.addEventListener('change', async event => {
		if (event.target.value === '') {
			return;
		}

		variationID = event.target.value;
		await sendVariationChangedMessage(variationID);
		updateOutOfStockSelection(variationID);
	});
}

document.addEventListener('DOMContentLoaded', () => {
	const $variations = Array.from(document.querySelectorAll('.variation_id'));
	for (const $v of $variations) {
		setCustomVariationChangeHandler($v);
	}
});

async function sendVariationChangedMessage(variationID) {
	// Keeping track of pending requests is mostly for product bundles, which
	// can have 10+ items per product and can cause these to queue up. If
	// someone starts the checkout before all requests have returned, some of
	// their items may not have the variation selected (from PeachPay's point of
	// view).
	pendingVariationDetailRequests++;
	const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/variation/details?id=${variationID}`);
	pendingVariationDetailRequests--;

	if (pendingVariationDetailRequests === 0 && buttonClickedBeforeLoad) {
		openPeachPay({alternateEvent: 'pp-button'});
	}

	const peachpay = document.querySelector('#peachpay-iframe');
	if (!peachpay) {
		return;
	}

	const details = await response.json();

	if (details.stock_status === 'outofstock') {
		selectedOutOfStockItems.add(variationID);
	}

	peachpay.contentWindow.postMessage({
		event: 'variationChanged',
		productID: details.full_wc_product.parent_id,
		variation: Number.parseInt(variationID),
		variationTitle: details.variation_title,
		displayPrice: details.display_price,
		price: details.price,
		attributes: details.attributes,
		image: details.image,
	}, '*');
}

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

function sendQuantityChangedMessage() {
	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'quantityChanged',
		quantity: quantity(),
	}, '*');
}

async function fetchAndSendCartFees(event) {
	if (location.hostname === 'localhost') {// Does not work anyways when on localhost
		return;
	}

	const response = await fetch(`${basePeachPayAPIURL(location.hostname)}/api/v1/merchants/${location.hostname}/cart/fees`, {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify({
			items: event.data.cart,
			address: {
				country: event.data.address.country,
				state: event.data.address.state,
				city: event.data.address.city,
				postcode: event.data.address.postcode,
			},
		}),
	});

	const peachpay = document.querySelector('#peachpay-iframe');
	peachpay.contentWindow.postMessage({
		event: 'cartFees',
		cartFees: await response.json(),
	}, '*');
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
		headers: {'Content-Type': 'application/json'},
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
	}, '*');
}

/**
 * New Place order function for use with window fetch.
 */
async function placeOrder(data) {
	// Make sure Route Shipping Protection fee by route.com is not added
	// until we add compatibility for it
	if (php_data.plugin_routeapp_active) {
		await removeRouteFee();
	}

	// Field editor resolves need for next line.
	const isBeYourBag = location.hostname === 'www.beyourbag.it'
		|| location.hostname === 'woocommerce-187306-844159.cloudwaysapps.com';

	const formData = formDataFromAddress(data.order.billingAddress);
	formData.append('_wpnonce', php_data._wpnonce);
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

	const orderResponse = await fetch(`${baseStoreURL()}/?wc-ajax=wc_peachpay_create_order`, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	});

	const orderResult = await orderResponse.json();

	if (orderResult.result === 'failure' || orderResult.success === false) {
		if (orderResult.data) {
			const message
				= `<ul class="woocommerce-error message-wrapper" role="alert">
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
			debugCart: php_data.debug_cart,
		};

		await fetch(`${basePeachPayAPIURL(location.hostname)}/api/v1/error-log`, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({errorContext}),
		});

		document.querySelector('.woocommerce-notices-wrapper').innerHTML = orderResult.messages;
		sendStopPaymentProcessingAnimationsMessage({closeModal: true});
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

/**
 * Places an order directly to the merchant store from the merchant host client
 * @returns { Promise<void> }
 */
async function placeOrderViaWCAjax(data) {
	if (data.isProductPageButton) {
		const message = 'Something went wrong while placing your order.';

		try {
			const emptyCartSuccess = await emptyCart();
			if (!emptyCartSuccess) {
				throw new Error('Non 200 response');
			}
		} catch (error) {
			if (error instanceof Error) {
				captureSentryException(new Error(`Product page "empty cart" failed on ${getStoreURL()}. Error: ` + error.message));
				alert(message);
			}

			return;
		}

		try {
			const {
				success,
				newCheckoutNonce,
			} = await addCurrentProductToCart();

			if (!success) {
				throw new Error('Non 200 response');
			}

			// See comment in peachpay.php peachpay_woocommerce_ajax_add_to_cart()
			// for why this is necessary.
			php_data._wpnonce = newCheckoutNonce;
		} catch (error) {
			if (error instanceof Error) {
				captureSentryException(new Error(`Product page "add product to cart" failed on ${getStoreURL()}. Error: ` + error.message));
				alert(message);
			}

			return;
		}

		// If peachpay upsell feature is enabled, add upsell items to cart for checkout in product page
		if (!php_data.hide_peachpay_upsell) {
			/* eslint-disable no-await-in-loop */
			for (const upsellId of data.upsell_items) {
				for (const upsellItem of php_data.cart) {
					if (upsellId === upsellItem.product_id) {
						try {
							const {
								success,
								newCheckoutNonce,
							} = await addLinkedProducttoCart(upsellItem);

							if (!success) {
								throw new Error('Non 200 response');
							}

							php_data._wpnonce = newCheckoutNonce;
						} catch (error) {
							if (error instanceof Error) {
								captureSentryException(new Error(`Product page "add upsell product" failed on ${getStoreURL()}. Error: ` + error.message));
								alert(message);
							}

							return;
						}
					}
				}
			}
			/* eslint-enable no-await-in-loop */
		}

		// Reapply applied coupons since the cart was emptied
		for (const code of appliedCouponCodes) {
			// eslint-disable-next-line no-await-in-loop
			if (!(await applyCoupon(code))) {
				// Remove the coupon so the customer can try to pay again without it
				appliedCouponCodes = appliedCouponCodes.filter(c => c !== code);
				// It should actually only clear only the invalid coupons, but the below is good
				// enough for now and can be iterated upon later since it doesn't
				// actually prevent the good coupons from being submitted
				sendClearCouponLinesMessage();
				sendStopPaymentProcessingAnimationsMessage();
				return;
			}
		}
	}

	// Make sure Route Shipping Protection fee by route.com is not added
	// until we add compatibility for it
	if (php_data.plugin_routeapp_active) {
		await removeRouteFee();
	}

	const isBeYourBag = location.hostname === 'www.beyourbag.it'
		|| location.hostname === 'woocommerce-187306-844159.cloudwaysapps.com';

	const formData = formDataFromAddress(data.billingAddress);
	formData.append('_wpnonce', php_data._wpnonce);

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

	const orderResponse = await fetch(`${baseStoreURL()}/?wc-ajax=wc_peachpay_create_order`, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	});

	const orderResult = await orderResponse.json();

	if (orderResult.result === 'failure' || orderResult.success === false) {
		if (orderResult.data) {
			const message
				= `<ul class="woocommerce-error message-wrapper" role="alert">
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
			debugCart: php_data.debug_cart,
		};

		await fetch(`${basePeachPayAPIURL(location.hostname)}/api/v1/error-log`, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({errorContext}),
		});

		document.querySelector('.woocommerce-notices-wrapper').innerHTML = orderResult.messages;
		sendStopPaymentProcessingAnimationsMessage({closeModal: true});
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

	return formData;
}

/**
 * Adds a product to the Woocommerce Cart.
 *
 * @param { {
 * 		isMiniCart?: boolean
 * } } options
 * @returns {Promise<{
*  		success: boolean,
* 		newCheckoutNonce: string,
* 	}>}
 */
async function addCurrentProductToCart(options) {
	// We're doing something different for this site where we only redirect from
	// the sidebar cart after they have added an item to the cart, so there is no
	// need to add it again.
	if (location.hostname === 'skregear.com' && options && options.isMiniCart) {
		return {
			success: true,
			newCheckoutNonce: php_data._wpnonce,
		};
	}

	if (isGroupedProduct()) {
		let nextCheckoutNonce = null;
		for (const item of php_data.cart) {
			if (!item.quantity || item.quantity === '0') {
				continue;
			}

			const {
				success,
				newCheckoutNonce,
				// eslint-disable-next-line no-await-in-loop
			} = await addGroupedProductToCart(item);

			if (!success) {
				return {
					success: false,
					newCheckoutNonce: newCheckoutNonce,
				};
			}

			nextCheckoutNonce = newCheckoutNonce;
		}

		return {
			success: true,
			newCheckoutNonce: nextCheckoutNonce,
		};
	}

	const response = await fetch(woocommerce_params.ajax_url, {
		method: 'POST',
		body: php_data.is_shortcode ? gatherShortcodeProductData() : gatherProductData(),
	});

	// They have a plugin that adds a jQuery script to the ouput buffer, and
	// we don't have access to clear that buffer before the response is returned,
	// so we need to clear out the added junk manually here.
	if (location.hostname === 'skregear.com') {
		let result = await response.text();
		result = JSON.parse(result.slice(result.indexOf('{"success"')));
		return {
			success: response.status === 200,
			newCheckoutNonce: result.data._wpnonce,
		};
	}

	const result = await response.json();

	return {
		success: response.status === 200,
		newCheckoutNonce: result.data._wpnonce,
	};
}

/**
 * Adds a grouped product to the wc cart.
 *
 * @param { * } item
 * @returns
 */
async function addGroupedProductToCart(item) {
	if (!groupedQuantity(item.product_id)) {
		// Make sure the outer check does not fail
		return true;
	}

	const data = new FormData();
	data.append('action', 'peachpay_woocommerce_ajax_add_to_cart');
	data.append('product_id', item.product_id);
	data.append('quantity', groupedQuantity(item.product_id) || 0);
	data.append('variation_id', item.variation_id);

	const response = await fetch(woocommerce_params.ajax_url, {
		method: 'POST',
		body: data,
	});

	const result = await response.json();

	return {
		success: response.status === 200,
		newCheckoutNonce: result.data._wpnonce,
	};
}

function gatherProductData() {
	const defaultID = document.querySelector('form.cart .single_add_to_cart_button').value
		|| document.querySelector('input[name=add-to-cart]').value;
	const productIDField = document.querySelector('input[name=product_id]');
	const quantityField = document.querySelector('input[name=quantity]');
	const variationIDField = document.querySelector('input[name=variation_id]');

	const data = new FormData(document.querySelector('form.cart'));
	data.append('action', 'peachpay_woocommerce_ajax_add_to_cart');
	data.append('product_id', productIDField ? productIDField.value : defaultID);
	data.append('quantity', quantityField ? quantityField.value : 1);
	data.append('variation_id', variationIDField ? variationIDField.value : 0);

	return data;
}

function gatherShortcodeProductData() {
	const data = new FormData();
	data.append('action', 'peachpay_woocommerce_ajax_add_to_cart');
	data.append('product_id', php_data.cart[0].product_id);
	data.append('quantity', 1);
	data.append('variation_id', php_data.cart[0].variation_id);

	return data;
}

async function emptyCart() {
	const data = new FormData();
	data.append('action', 'peachpay_wc_ajax_empty_cart');
	const response = await fetch(php_data.wp_ajax_url, {
		method: 'POST',
		body: data,
	});
	return response.status === 200;
}

function setOrderStatus(orderID, {status, message, paymentMethod}) {
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

	return fetch(php_data.wp_ajax_url, {
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
	const response = await fetch(php_data.wp_ajax_url, {
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

function validQuantity() {
	const underMin = minQuantity() && (quantity() < minQuantity());
	const overMax = maxQuantity() && (quantity() > maxQuantity());
	return !underMin && !overMax;
}

function quantity() {
	const input = document.querySelector('form.cart input[name="quantity"]');
	if (php_data.is_checkout_page) {
		return php_data.cart[0].quantity;
	}

	return input ? input.value : 1;
}

function minQuantity() {
	const quantity = document.querySelector('form.cart input[name="quantity"]');
	return quantity ? Number.parseInt(quantity.min) : false;
}

function maxQuantity() {
	const quantity = document.querySelector('form.cart input[name="quantity"]');
	return quantity ? Number.parseInt(quantity.max) : false;
}

function sendGroupedQuantityChangedMessage(productID) {
	// Keep php_data.cart (plugin) quantities in sync with phpData.cart
	// (checkout window)
	for (const [index, item] of php_data.cart.entries()) {
		if (item.product_id === productID) {
			php_data.cart[index].quantity = groupedQuantity(productID);
		}
	}

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'groupedQuantityChanged',
		quantity: groupedQuantity(productID),
		productID,
	}, '*');
}

function isGroupedProduct() {
	return document.querySelector('form.cart.grouped_form') !== null;
}

function validGroupedQuantity() {
	for (let i = 0; i < php_data.cart.length; i++) {
		const id = php_data.cart[i].product_id;
		const under = groupedQuantity(id) < minGroupedQuantity(id);
		const over = groupedQuantity(id) > maxGroupedQuantity(id);
		if (under || over) {
			return false;
		}
	}

	return true;
}

function groupedQuantity(productID) {
	const input = document.querySelector(`form.cart input[name="quantity[${productID}]"]`);
	return input ? input.value : 1;
}

function minGroupedQuantity(productID) {
	const quantity = document.querySelector(`form.cart input[name="quantity[${productID}]"]`);
	return quantity ? Number.parseInt(quantity.min) : false;
}

function maxGroupedQuantity(productID) {
	const quantity = document.querySelector(`form.cart input[name="quantity[${productID}]"]`);
	return quantity ? Number.parseInt(quantity.max) : false;
}

function variationSelected() {
	let $variations = Array.from(document.querySelectorAll('input.variation_id'));

	if (location.hostname === 'www.kidtoes.com' && php_data.is_cart_page === '') {
		const summary = document.querySelector('.summary-inner');
		$variations = summary.querySelectorAll('input.variation_id');
	}

	for (const variation of $variations) {
		if (variation !== null && (variation.value === '0' || variation.value === '')) {
			hideLoadingSpinner();
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

function showLoadingSpinner(isMiniCart) {
	if (isMiniCart) {
		return;
	}

	document.querySelector('#loading-spinner').classList.remove('hide');
	document.querySelector('#pp-button-content').classList.add('hide');
}

function hideLoadingSpinner() {
	const loadingSpinner = document.querySelector('#loading-spinner');
	if (loadingSpinner) {
		loadingSpinner.classList.add('hide');
	}

	const buttonContent = document.querySelector('#pp-button-content');
	if (buttonContent) {
		buttonContent.classList.remove('hide');
	}
}

function spinnerURL() {
	// For now we explicity list out the sites that should have a dark loading
	// symbol because of their light button color
	const dark = location.hostname === 'www.blazecandles.co' ? '-dark' : '';
	return `${baseURL(location.hostname)}/img/spinner${dark}.svg`;
}

function peachpay_isMobile() {
	return /Mobi/.test(navigator.userAgent);
}

function getPeachPayCheckoutHTML() {
	return 'peachpay-checkout.html';
}

window.pp_peachpayButton = `
<div id="pp-button-container" class="button-container pp-button-container ${(php_data.test_mode && !php_data.wp_admin_or_editor) ? 'hide' : ''}">
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
			<span id="pp-button-text">${php_data.button_text}</span>
			<img id="button-icon-regular" class=""/>
		</div>
	</button>
	<div id="payment-methods-container" class="cc-company-logos">
		<img class="${(php_data.paypal) ? 'cc-logo' : 'hide'}" src="/wp-content/plugins/peachpay-for-woocommerce/img/paypal.svg"/>
		<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/visa.svg"/>
		<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/amex.svg"/>
		<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/discover.svg"/>
		<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/mastercard.svg"/>
		<img class="cc-logo" src="/wp-content/plugins/peachpay-for-woocommerce/img/cc-stripe-brands.svg"/>
	</div>
</div>
`;

window.pp_peachpayButtonMiniCart = `
<div id="pp-button-container-mini" class="button-container pp-button-container ${(php_data.test_mode && !php_data.wp_admin_or_editor) ? 'hide' : ''}">
	<button id="pp-button-mini" class="pp-button" type="button" style="display: block;">
		<div id="pp-button-content-mini">
			<span id="pp-button-text-mini">${php_data.button_text}</span>
			<img id="button-icon-minicart" class=""/>
		</div>
	</button>
	<div id="payment-methods-container-minicart" class="cc-company-logos">
		<img class="${(php_data.paypal) ? 'cc-logo-sidebar' : 'hide'}" src="/wp-content/plugins/peachpay-for-woocommerce/img/paypal.svg"/>
		<img class="cc-logo-sidebar" src="/wp-content/plugins/peachpay-for-woocommerce/img/visa.svg"/>
		<img class="cc-logo-sidebar" src="/wp-content/plugins/peachpay-for-woocommerce/img/amex.svg"/>
		<img class="cc-logo-sidebar" src="/wp-content/plugins/peachpay-for-woocommerce/img/discover.svg"/>
		<img class="cc-logo-sidebar" src="/wp-content/plugins/peachpay-for-woocommerce/img/mastercard.svg"/>
		<img class="cc-logo-sidebar" src="/wp-content/plugins/peachpay-for-woocommerce/img/cc-stripe-brands.svg"/>
	</div>
</div>
`;

window.pp_checkoutForm = `
<div id="pp-modal-overlay" class="pp-overlay">
	<img src="${spinnerURL()}" id="loading-spinner-iframe" class="pp-spinner-iframe hide">
	<iframe id="peachpay-iframe" src="${baseURL(location.hostname)}/${getPeachPayCheckoutHTML()}">
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

document.addEventListener('DOMContentLoaded', () => {
	// The setting button_sheen was flipped so that the default is on, and its
	// presence as a truthy value indicates that the shine should be turned off.
	if (!peachpay_isElementor() && !php_data.button_sheen) {
		peachpay_buttonShine();
	}

	if (document.querySelector('#payment-methods-container') && php_data.button_hide_payment_method_icons) {
		document.querySelector('#payment-methods-container').classList.add('hide');
	}

	if (document.querySelector('#button-icon-regular')) {
		update_buttonIcon(php_data.button_icon, 'regular');
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
});

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
	animation: shine 1.8s ease infinite;
	background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
	content: '';
	display: inherit;
	height: 200%;
	left: -200%;
	position: absolute;
	top: 0;
	transform: skewX(-20deg);
	width: 50%;
}
</style>`;

function peachpay_traveltek() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	peachpayButton.style.cssText += ';font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol" !important;';
	peachpayButton.style.cssText += ';text-transform: uppercase !important';
	peachpayButton.style.fontSize = '12px';

	if (php_data.is_cart_page) {
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
	peachpayButton.style.padding = php_data.is_cart_page ? '10px 0' : '15px';
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

	if (php_data.is_cart_page) {
		peachpayButton.style.fontSize = '0.97em';
		peachpayButton.style.padding = '0.62em';
	}
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
	buttonContainer.style.marginLeft = peachpay_isMobile() || php_data.is_cart_page ? '0' : '20px';

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
		window.requestAnimationFrame(peachpay_rahimsapphire);
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

	if (php_data.is_cart_page) {
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

function peachpayTestMode() {
	console.log('Peachpay Version: ' + php_data.version);

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
				icons.src = `/wp-content/plugins/peachpay-for-woocommerce/img/lock${beyourbag ? '-black' : ''}.svg`;
				break;
			case 'baseball':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/baseball-ball-solid.svg';
				break;
			case 'arrow':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/chevron-circle-right-solid.svg';
				break;
			case 'mountain':
				if (buttonType === 'regular') {
					icons.classList.remove('pp-btn-symbol');
					icons.classList.add('skre-icon');
				} else {
					icons.classList.remove('pp-btn-symbol-mini');
					icons.classList.add('skre-icon-mini');
				}

				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/skre.svg';
				break;
			case 'bag':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/briefcase-solid.svg';
				break;
			default:
				icons.classList.add('hide');
		}
	}
}
