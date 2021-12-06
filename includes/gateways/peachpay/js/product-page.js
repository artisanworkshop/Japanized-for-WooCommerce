document.addEventListener('DOMContentLoaded', peachpay_placeButtonProductPage);

if (location.hostname === 'skregear.com') {
	// The site skregear.com has two issues: one is that the site takes a very long time
	// to load, so waiting until the window load event is not feasible, and the
	// other is that this mini cart will refresh its HTML removing our button.
	// To fix this, we place the button early (it seems to work with this sidebar
	// unlike the other mini carts) and then replace it if it gets removed.
	document.addEventListener('DOMContentLoaded', peachpay_placeButtonMiniCart);
	document.addEventListener('DOMContentLoaded', peachpay_watchForMiniButtonRemoval);
} else {
	// We use 'load' here because the mini carts often are added by third-party scripts,
	// so we have to wait for the scripts to load and not just the DOM content
	window.addEventListener('load', peachpay_placeButtonMiniCart);
}

window.addEventListener('load', () => {
	if (location.hostname === 'strandsofhumanity.com') {
		jQuery(document.body).on('wc_fragments_refreshed', () => {
			peachpay_placeButtonMiniCart();
			updateCart();
			peachpay_strandsofhumanity();
		});
	}

	if (location.hostname === 'counterattackgame.com' || location.hostname === 'store.local') {
		peachpay_watchForAddToCartButtonDisabled();
	}
});

window.addEventListener('click', () => {
	if (location.hostname === 'counterattackgame.com' || location.hostname === 'store.local') {
		peachpay_watchForAddToCartButtonDisabled();
	}
});

function updateButtonDisabledProductPage() {
	const peachpayButton = document.querySelector('#pp-button');
	if (!peachpayButton) {
		return;
	}

	const totalPrice = php_data.cart[0].price * quantity();
	if (totalPrice < 249) {
		peachpayButton.disabled = true;
		peachpayButton.style.cssText += ';opacity: 0.5 !important;';
	} else {
		peachpayButton.disabled = false;
		peachpayButton.style.cssText += ';opacity: 1 !important;';
	}
}

function peachpay_placeButtonProductPage() {
	const doNotTryToPlaceButton = !peachpay_isProductPage()
		|| peachpay_isExcludedSite(location.hostname)
		|| peachpay_isElementor()
		|| peachpay_isSubscribeAllThings()
		|| php_data.button_hide_on_product_page;

	if (doNotTryToPlaceButton) {
		return;
	}

	let $addToCartForm = document.querySelector('form.cart');

	if (location.hostname === 'olybird.com' && peachpay_isMobile()) {
		$addToCartForm = Array.from(document.querySelectorAll('form.cart'))[1];
	}

	if ($addToCartForm === null && location.hostname === 'www.jogiachamasalo.com') {
		$addToCartForm = document.querySelector('div.cart');
	}

	if ($addToCartForm === null) {
		return;
	}

	$addToCartForm = document.querySelector('.bundle_button')
		|| document.querySelector('.woocommerce-variation-add-to-cart')
		|| document.querySelector('form.cart');

	if (document.querySelector('form.grouped_form')) {
		$addToCartForm = document.querySelector('.single_add_to_cart_button');
	}

	let position = 'beforebegin';

	if (location.hostname === 'airthreds.com') {
		$addToCartForm = document.querySelector('form.cart .qty');
		position = 'afterend';
	}

	if (location.hostname === 'simostyle.it') {
		$addToCartForm = document.querySelector('[name="add-to-cart"]');
		position = 'beforebegin';
	}

	if (location.hostname === 'www.kidtoes.com') {
		$addToCartForm = document.querySelector('.single_variation_wrap');
		position = 'beforeend';
	}

	if (location.hostname === 'rahimsapphire.co.uk') {
		position = 'beforeend';
	}

	if (location.hostname === 'www.grandbazaarist.com') {
		position = 'afterend';
	}

	if (location.hostname === 'counterattackgame.com') {
		$addToCartForm = document.querySelector('.single_add_to_cart_button');
		position = 'afterend';
	}

	const wcPaoAddonsContainer = document.querySelector('.wc-pao-addons-container');
	if (wcPaoAddonsContainer) {
		$addToCartForm = wcPaoAddonsContainer;
		position = 'afterend';
	}

	$addToCartForm.insertAdjacentHTML(position, pp_peachpayButton);

	// We must do this because we changed the <img> tags to <object> tags to
	// fix a Safari issue where it would not load animated SVGs. If we have
	// the hide class on the element right away, the SVG is not loaded, so the
	// first time you click the button it's not there. Only after it is visible
	// does it load, so we allow it to be visible for a split second before
	// hiding it here.
	hideLoadingSpinner();

	// Add the checkout window iframe to the page
	document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);

	const full = php_data.button_position_product_page === 'full';
	const width = full ? '100%' : ((php_data.button_width_product_page || '220') + 'px');
	init({
		width,
		position: php_data.button_position_product_page,
		borderRadius: php_data.button_border_radius,
	});
}

/**
 * Check if this is a site where we don't want PeachPay to appear on the product
 * page.
 *
 * We cannot have PeachPay on the product page for beyourbag.it until we add
 * compatibility for WooCommerce Attribute Swatches
 */
function peachpay_isExcludedSite(hostname) {
	return hostname === 'www.infinitealoe.shop'
		|| hostname === 'www.beyourbag.it';
}

function peachpay_isProductPage() {
	return !php_data.is_cart_page && !php_data.is_category_page;
}

function peachpay_placeButtonMiniCart() {
	const miniCartButtons
		= document.querySelector('.woocommerce-mini-cart__buttons')
		|| document.querySelector('.xoo-wsc-footer');

	if (!miniCartButtons) {
		return;
	}

	if (!document.querySelector('#pp-modal-overlay')) {
		document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
	}

	miniCartButtons.insertAdjacentHTML('beforeend', pp_peachpayButtonMiniCart);

	if (miniCartButtons.querySelector('#payment-methods-container-minicart') && php_data.button_hide_payment_method_icons) {
		miniCartButtons.querySelector('#payment-methods-container-minicart').classList.add('hide');
	}

	if (miniCartButtons.querySelector('#button-icon-minicart')) {
		update_buttonIcon(php_data.button_icon, 'minicart');
	}

	adjustMiniButtonPerSite();

	init({
		position: php_data.button_position_product_page,
		isMiniCart: true,
	});
}

// Specific adjustments for a more native look
function adjustMiniButtonPerSite() {
	const miniButton = document.querySelector('#pp-button-mini');

	if (location.hostname === 'skregear.com') {
		miniButton.style.padding = '3px';
		miniButton.style.fontSize = '0.97em';
		miniButton.style.cssText += ';font-family: Lato !important;';
		miniButton.style.cssText += 'text-transform: uppercase !important;';
	}

	if (location.hostname === 'salafibookstore.com') {
		miniButton.style.padding = '18px';
	}
}

function peachpay_watchForMiniButtonRemoval() {
	const element = document.querySelector('#pp-button-mini');
	let in_dom = document.body.contains(element);
	const observer = new MutationObserver(() => {
		if (document.body.contains(element)) {
			in_dom = true;
		} else if (in_dom) {
			in_dom = false;
			peachpay_placeButtonMiniCart();
		}
	});
	observer.observe(document.body, {childList: true, subtree: true});
}

function peachpay_watchForAddToCartButtonDisabled() {
	const $addToCart = document.querySelector('.single_add_to_cart_button');
	const $ppButton = document.querySelector('#pp-button');

	if (!$addToCart || !$ppButton) {
		return;
	}

	$ppButton.disabled = $addToCart.disabled;
}

/**
 * This is here and not in the Elementor JS file because the Elementor JS file
 * is not loaded if Elementor is not active.
 */
function peachpay_isElementor() {
	return document.querySelector('.elementor-pp-button');
}

function peachpay_isSubscribeAllThings() {
	return document.querySelector('.wcsatt-options-wrapper');
}
