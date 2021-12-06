/**
 * Support for Woocommerce coupons.
 */

// Global for applied coupons
const appliedCouponCodes = [];

//
/**
 * This is used for orders that are submitted on the cart page which do not go
 * through the REST API
 *
 * @param { string } code
 * @returns boolean
 */
async function applyCoupon(code) {
	const data = new FormData();
	data.append('security', php_data.apply_coupon_nonce);
	data.append('coupon_code', code);

	const response = await fetch('/?wc-ajax=apply_coupon', {
		method: 'POST',
		body: data,
	});

	const message = await response.text();
	if (message.includes('woocommerce-error')) {
		window.alert(parseWooCommerceHTMLError(message));
	}

	// The default WooCommerce behavior for when a coupon that cannot be used
	// with other coupons is applied is to clear out all the previously applied
	// coupons and only keep the latest one. The call to update cart will make
	// sure the PeachPay checkout window doesn't show a coupon that WooCommerce
	// has silently removed.
	updateCart();

	return response.status === 200 && !message.includes('woocommerce-error');
}

async function fetchAndSendCoupon(event) {
	const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/coupon/${event.data.code}`);
	const coupon = await response.json();

	if (!response.ok) {
		alert(coupon.message);
		sendStopCouponLoadingMessage();
		return;
	}

	if (php_data.should_place_order_before_payment) {
		if (!(await applyCoupon(coupon.code))) {
			sendStopCouponLoadingMessage();
			return;
		}

		appliedCouponCodes.push(coupon.code);
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

function sendClearCouponLinesMessage() {
	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'clearCouponLines',
	}, '*');
}
