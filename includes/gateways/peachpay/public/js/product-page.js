document.addEventListener('DOMContentLoaded', peachpay_placeButtonProductPage);

self.addEventListener('load', () => {
	if (location.hostname === 'strandsofhumanity.com') {
		jQuery(document.body).on('wc_fragments_refreshed', () => {
			peachpay_placeButtonMiniCart();
			peachpay_strandsofhumanity();
		});
	}

	if (location.hostname === 'counterattackgame.com') {
		peachpay_watchForAddToCartButtonDisabled();
	}
});

self.addEventListener('click', () => {
	if (location.hostname === 'counterattackgame.com') {
		peachpay_watchForAddToCartButtonDisabled();
	}
});

// deno-lint-ignore camelcase
function peachpay_placeButtonProductPage() {
	const doNotTryToPlaceButton = !peachpay_isProductPage() ||
		peachpay_isExcludedSite(location.hostname) ||
		peachpay_isElementor() ||
		peachpay_data.button_hide_on_product_page;

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

	$addToCartForm = document.querySelector('.bundle_button') ||
		document.querySelector('.woocommerce-variation-add-to-cart') ||
		document.querySelector('form.cart');

	if (document.querySelector('form.grouped_form')) {
		$addToCartForm = document.querySelector('.single_add_to_cart_button');
	}

	let position = 'beforebegin';

	position = peachpay_data.product_page_button_before_after;

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

	if (location.hostname === 'www.locksandbonds.com') {
		$addToCartForm = document.querySelector('.cart [type=\'submit\']');
		position = 'afterend';
		$addToCartForm.insertAdjacentHTML(position, pp_peachpayButton);

		document.querySelector('#pp-button-container').insertAdjacentHTML('afterbegin', '<p style="font-weight: bold; width: 100%; text-align: center;">OR</p>');
		document.querySelector('#pp-button-container').style.marginTop = '0';
	} else {
		$addToCartForm.insertAdjacentHTML(position, pp_peachpayButton);
	}

	// We must do this because we changed the <img> tags to <object> tags to
	// fix a Safari issue where it would not load animated SVGs. If we have
	// the hide class on the element right away, the SVG is not loaded, so the
	// first time you click the button it's not there. Only after it is visible
	// does it load, so we allow it to be visible for a split second before
	// hiding it here.
	peachpay_hideLoadingSpinner();

	// Add the checkout window iframe to the page
	if (!document.querySelector('#pp-modal-overlay')) {
		document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
	}

	const full = peachpay_data.button_alignment_product_page === 'full';
	const width = full ? '100%' : ((peachpay_data.button_width_product_page || '220') + 'px');
	peachpay_initButton({
		width,
		alignment: peachpay_data.button_alignment_product_page || 'left',
		borderRadius: peachpay_data.button_border_radius,
	});
}

/**
 * Check if this is a site where we don't want PeachPay to appear on the product
 * page.
 *
 * We cannot have PeachPay on the product page for beyourbag.it until we add
 * compatibility for WooCommerce Attribute Swatches
 */
// deno-lint-ignore camelcase
function peachpay_isExcludedSite(hostname) {
	return hostname === 'www.infinitealoe.shop' ||
		hostname === 'www.beyourbag.it';
}

// deno-lint-ignore camelcase
function peachpay_isProductPage() {
	return !peachpay_data.is_cart_page && !peachpay_data.is_category_page;
}

// deno-lint-ignore camelcase
function peachpay_placeButtonMiniCart() {
	const miniCartButtons = document.querySelector('.woocommerce-mini-cart__buttons') ||
		document.querySelector('.xoo-wsc-footer');

	if (!miniCartButtons) {
		self.requestAnimationFrame(peachpay_placeButtonMiniCart);
		return;
	}

	if (!document.querySelector('#pp-modal-overlay')) {
		document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
	}

	const miniCart = document.querySelector('#pp-button-mini');

	// Avoid placing mini-cart twice
	if (miniCart) {
		return;
	}

	miniCartButtons.insertAdjacentHTML('beforeend', pp_peachpayButtonMiniCart);

	if (miniCartButtons.querySelector('#payment-methods-container-minicart') && peachpay_data.button_hide_payment_method_icons) {
		miniCartButtons.querySelector('#payment-methods-container-minicart').classList.add('hide');
	}

	if (miniCartButtons.querySelector('#button-icon-minicart')) {
		update_buttonIcon(peachpay_data.button_icon, 'minicart');
	}

	adjustMiniButtonPerSite();

	peachpay_initButton({
		alignment: peachpay_data.button_alignment_product_page,
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

// deno-lint-ignore camelcase
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
// deno-lint-ignore camelcase
function peachpay_isElementor() {
	return document.querySelector('.elementor-pp-button');
}
