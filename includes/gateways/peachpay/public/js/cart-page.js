document.addEventListener('DOMContentLoaded', () => {
	pp_placeButtonCartPage();
	if (peachpay_data.is_cart_page && location.hostname.includes('farm2forkdelivery.ca')) {
		farm2forkdeliveryCartPage();
	}
});

self.addEventListener('load', () => {
	jQuery(document.body).on('removed_from_cart updated_cart_totals update_checkout', (event) => {
		renderPeachPayButton();

		// For Yith Gift card plugin that uses this event trigger only
		if (event.type === 'update_checkout') {
			setTimeout(reinsertPPButton, 3000);
		}
	});
});

// Renders the peachpay button in the cart page
function renderPeachPayButton() {
	pp_placeButtonCartPage();
	peachpay_addCustomMerchantStyles();

	if (document.querySelector('#button-icon-regular')) {
		update_buttonIcon(peachpay_data.button_icon, 'regular');
	}
}

// Reinsert the peachpay cart button if it goes missing, (only for Yith gift card plugin)
function reinsertPPButton() {
	const button = document.querySelector('#pp-button-container');
	if (!button) {
		renderPeachPayButton();
	}
}

// deno-lint-ignore camelcase
function pp_placeButtonCartPage() {
	let $button = document.querySelector('.wc-proceed-to-checkout');

	if (location.hostname === 'www.irish-pure.de') {
		$button = Array.from(document.querySelectorAll('.wc-proceed-to-checkout'))[1];
	}

	if ($button !== null) {
		const isBeYourBag = location.hostname === 'www.beyourbag.it' || location.hostname === 'woocommerce-187306-844159.cloudwaysapps.com';
		const isSkregear = location.hostname === 'skregear.com';

		if (location.hostname === 'northcoastkeyless.com' && !document.querySelector('.warranty_info')) {
			// This store added code that moves the PeachPay button, but if the
			// element they are trying to move it next to doesn't exist, such as
			// on the cart and checkout pages, then the end result is that
			// PeachPay disappears.
			$button.insertAdjacentHTML('beforeend', '<p class="warranty_info"></p>');
		}

		insertPeachPayAt($button, (isBeYourBag || isSkregear) ? 'beforebegin' : 'beforeend');

		if (isBeYourBag) {
			adjustButtonStylesForBeYourBag();
		}

		if (isSkregear) {
			document.querySelector('.wc-proceed-to-checkout').style.marginTop = '0';
			document.querySelector('.pp-button-container').style.margin = '0';
		}

		return;
	}

	const wpBlocksButton = document.querySelector('.wc-block-cart__payment-options');
	if (wpBlocksButton !== null) {
		insertPeachPayAt(wpBlocksButton, 'afterbegin');
	}
}

/**
 * Placed at custom www.infinitealoe.shop checkout page
 * wfacp_smart_button_wrap_st query from buildwoofunnels.com
 */
// deno-lint-ignore camelcase,no-unused-vars
function pp_placeButtonCustomCheckoutPage() {
	const expressDiv = document.querySelector('.wfacp_smart_button_wrap_st');
	if (expressDiv) {
		insertPeachPayAt(expressDiv, 'beforeend');
	}
}

function insertPeachPayAt(element, location) {
	const full = peachpay_data.button_alignment_cart_page === 'full' || !peachpay_data.button_alignment_cart_page;
	const width = full ? '100%' : ((peachpay_data.button_width_cart_page || '220') + 'px');
	element.insertAdjacentHTML(location, pp_peachpayButton);

	// We must do this because we changed the <img> tags to <object> tags to
	// fix a Safari issue where it would not load animated SVGs. If we have
	// the hide class on the element right away, the SVG is not loaded, so the
	// first time you click the button it's not there. Only after it is visible
	// does it load, so we allow it to be visible for a split second before
	// hiding it here.
	peachpay_hideLoadingSpinner();
	if (!document.querySelector('#pp-modal-overlay')) {
		document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
	}

	peachpay_initButton({
		width,
		alignment: peachpay_data.button_alignment_cart_page,
		borderRadius: peachpay_data.button_border_radius,
	});

	// Easy way for stripe payment button to be reinserted when removed
	document.dispatchEvent(new Event('pp-insert-button'));
}

function adjustButtonStylesForBeYourBag() {
	document.querySelector('#pp-button-text').textContent = '🇮🇹 Cassa rapida';
	const peachpayButton = document.querySelector('#pp-button');
	peachpayButton.style.fontFamily = '"Montserrat", Sans-serif';
	peachpayButton.style.cssText += ';font-family: Montserrat, sans-serif !important;';
	peachpayButton.style.cssText += ';font-size: 22px !important;';
	peachpayButton.style.fontWeight = 600;
	peachpayButton.style.color = '#000000';
	peachpayButton.style.backgroundColor = '#2cff00';
	peachpayButton.style.borderRadius = '5px';
	peachpayButton.style.padding = '40px 35px';
	peachpayButton.style.width = '100%';

	const mediaQuery = window.matchMedia('(max-width: 767px)');
	mediaQuery.addListener(resize);

	function resize(mediaQuery) {
		peachpayButton.style.padding = mediaQuery.matches ? '25px' : '40px 35px';
	}

	resize(mediaQuery);
}
