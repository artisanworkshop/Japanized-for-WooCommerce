/**
 * Support for Woocommerce coupons.
 */

/**
 * This is used for orders that are submitted on the cart page which do not go
 * through the REST API
 *
 * @param { string } code
 * @returns boolean
 */
async function applyCoupon(code) {
	const data = new FormData();
	data.append('security', peachpay_data.apply_coupon_nonce);
	data.append('coupon_code', code);

	const response = await fetch('/?wc-ajax=apply_coupon', {
		method: 'POST',
		body: data,
	});

	const message = await response.text();
	if (message.includes('woocommerce-error')) {
		window.alert(parseWooCommerceHTMLError(message));
	}

	return response.status === 200 && !message.includes('woocommerce-error');
}

// deno-lint-ignore no-unused-vars
async function fetchAndSendCoupon(event) {
	const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/coupon/${event.data.code}`);
	const coupon = await response.json();

	if (!response.ok) {
		alert(coupon.message);
		sendStopCouponLoadingMessage();
		return;
	}

	if (!(await applyCoupon(coupon.code))) {
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
