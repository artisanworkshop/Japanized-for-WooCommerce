/**
 * Support for Woocommerce coupons.
 */

/**
 * Listeners for this file's functions.
 */
// deno-lint-ignore no-unused-vars
function peachpayInitCouponSupport() {
	ppOnWindowMessage('fetchCoupon', peachpayFetchAndSendCoupon);
	ppOnWindowDataFetch('pp-remove-coupon', peachpayRemoveCoupon);
}

/**
 * This is used for orders that are submitted on the cart page which do not go
 * through the REST API
 *
 * @param { string } code
 * @returns boolean
 */
async function peachpayApplyCoupon(code) {
	const data = new FormData();
	data.append('security', peachpay_data.apply_coupon_nonce);
	data.append('coupon_code', code);

	const response = await fetch('/?wc-ajax=apply_coupon', {
		method: 'POST',
		body: data,
		credentials: 'include',
	});

	const message = await response.text();
	if (message.includes('woocommerce-error')) {
		window.alert(parseWooCommerceHTMLError(message));
	}

	return response.status === 200 && !message.includes('woocommerce-error');
}

/**
 * Manages the backend removal of a coupon, sending a JSON POST request to the wc-ajax API.
 *
 * @param {string} code
 * @returns
 */
async function peachpayRemoveCoupon(eventData) {
	code = eventData.code;

	const data = new FormData();
	data.append('security', peachpay_data.remove_coupon_nonce);
	data.append('coupon', code);

	const response = await fetch('/?wc-ajax=remove_coupon', {
		method: 'POST',
		body: data,
		credentials: 'include',
	});

	const message = await response.text();
	if (message.includes('woocommerce-error')) {
		window.alert(parseWooCommerceHTMLError(message));
	}

	return response.status === 200 && !message.includes('woocommerce-error');
}

async function peachpayFetchAndSendCoupon(eventData) {
	const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/coupon/${eventData.code}`);
	const coupon = await response.json();

	if (!response.ok) {
		alert(coupon.message);
		sendStopCouponLoadingMessage();
		return;
	}

	if (!(await peachpayApplyCoupon(coupon.code))) {
		sendStopCouponLoadingMessage();
		return;
	}

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'coupon',
		coupon,
	}, '*');
}

function sendStopCouponLoadingMessage() {
	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'stopCouponLoading',
	}, '*');
}
