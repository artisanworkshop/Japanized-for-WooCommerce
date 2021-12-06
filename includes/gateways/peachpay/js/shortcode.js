document.addEventListener('DOMContentLoaded', pp_placeShortcode);

function pp_placeShortcode() {
	if (!window.peachpayShortcodeData || !window.peachpayShortcodeData.cart) {
		return;
	}

	document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
	init({width: php_data.button_width_product_page});

	if (peachpayShortcodeData.cart.length > 0) {
		php_data.cart = peachpayShortcodeData.cart;
		php_data.is_shortcode = true;
	}
}
