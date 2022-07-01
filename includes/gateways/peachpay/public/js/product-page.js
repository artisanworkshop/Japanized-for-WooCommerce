document.addEventListener('DOMContentLoaded', peachpay_placeButtonProductPage);

// deno-lint-ignore camelcase
function peachpay_placeButtonProductPage() {
	const doNotTryToPlaceButton = !peachpay_isProductPage() ||
		peachpay_isElementor() ||
		!peachpay_data.button_display_on_product_page;

	if (doNotTryToPlaceButton) {
		return;
	}

	const position = peachpay_data.product_page_button_before_after || 'beforebegin';

	const getAdjacentContainer = () => {
		if (position === 'beforebegin') {
			// Button Above
			return document.querySelector('form.cart.varations_form_cart div.woocommerce-variation-add-to-cart') || // Variation products
				document.querySelector('form.cart.grouped_form .single_add_to_cart_button') || // Grouped products
				document.querySelector('form.cart.bundle_form div.quantity:not(.quantity_hidden)') || // Bundled products
				document.querySelector('form.cart div.quantity') || // Standard simple products with quantity
				document.querySelector('form.cart'); // Default case
		} else {
			// Button Below
			return document.querySelector('form.cart div.single_variation_wrap') || // Variation products
				document.querySelector('form.cart div.woocommerce-variation-add-to-cart') || // Other variation products
				document.querySelector('form.cart .single_add_to_cart_button') || // Everything else
				document.querySelector('form.cart'); // Backup default case
		}
	};

	const $addToCartForm = getAdjacentContainer();

	if (!$addToCartForm) {
		return;
	}

	$addToCartForm.insertAdjacentHTML(position, pp_peachpayButton);

	// We must do this because we changed the <img> tags to <object> tags to
	// fix a Safari issue where it would not load animated SVGs. If we have
	// the hide class on the element right away, the SVG is not loaded, so the
	// first time you click the button it's not there. Only after it is visible
	// does it load, so we allow it to be visible for a split second before
	// hiding it here.
	peachpay_hideLoadingSpinner();

	const full = peachpay_data.button_alignment_product_page === 'full';
	const width = full ? '100%' : ((peachpay_data.button_width_product_page || '220') + 'px');
	peachpay_initButton({
		width,
		alignment: peachpay_data.button_alignment_product_page || 'left',
		borderRadius: peachpay_data.button_border_radius,
	});
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

	const miniCart = document.querySelector('#pp-button-mini');

	// Avoid placing mini-cart twice
	if (miniCart) {
		return;
	}

	miniCartButtons.insertAdjacentHTML('beforeend', pp_peachpayButtonMiniCart);

	if (miniCartButtons.querySelector('#payment-methods-container-minicart') && !peachpay_data.button_display_payment_method_icons) {
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

/**
 * This is here and not in the Elementor JS file because the Elementor JS file
 * is not loaded if Elementor is not active.
 */
// deno-lint-ignore camelcase
function peachpay_isElementor() {
	return document.querySelector('.elementor-pp-button');
}
