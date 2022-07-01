document.addEventListener('DOMContentLoaded', peachpay_placeShortcode);

/**
 * Initializes the peachpay shortcode button.
 */
// deno-lint-ignore camelcase
function peachpay_placeShortcode() {
	if (!window.peachpayShortcodeData || !window.peachpayShortcodeData.product_id) {
		return;
	}

	peachpay_initButton({
		isShortcode: true,
		shortcodeInfo: {
			productId: peachpayShortcodeData.product_id,
			quantity: 1,
		},
		width: peachpay_data.button_width_product_page,
	});
}

/**
 * Creates a FormData object for a peachpay shortcode button.
 *
 * @param { IButtonConfigurationOptions } options PeachPay Button options.
 */
// deno-lint-ignore camelcase no-unused-vars
function peachpay_getShortCodeAddToCartFormData(options) {
	const formData = new FormData();

	formData.append('add-to-cart', options.shortcodeInfo.productId);
	formData.append('quantity', options.shortcodeInfo.quantity);

	return formData;
}
