document.addEventListener('DOMContentLoaded', () => {
	pp_placeButtonCartPage();
	if (php_data.is_cart_page && location.hostname.includes('farm2forkdelivery.ca')) {
		farm2forkdeliveryCartPage();
	}
});

window.addEventListener('load', () => {
	jQuery(document.body).on('removed_from_cart updated_cart_totals', () => {
		pp_placeButtonCartPage();

		if (document.querySelector('#button-icon-regular')) {
			update_buttonIcon(php_data.button_icon, 'regular');
		}

		updateCart();

		if (location.hostname.includes('farm2forkdelivery.ca')) {
			farm2forkdeliveryCartPage();
		}
	});
});

async function updateCart() {
	const response = await fetch(
		`${baseStoreURL()}/wp-json/peachpay/v1/cart/list`,
		{cache: 'no-store'},
	);
	const cart = await response.json();

	php_data.cart = cart.product_or_full_cart;
	php_data.wc_cart = cart.full_cart;
	php_data.cart_coupons = cart.coupons;
	php_data.cart_applied_gift_cards = cart.cart_applied_gift_cards;

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'cartChanged',
		cart: php_data.cart,
		wc_cart: php_data.wc_cart,
		cart_coupons: php_data.cart_coupons,
		cart_applied_gift_cards: php_data.cart_applied_gift_cards,
	}, '*');
}

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
function pp_placeButtonCustomCheckoutPage() {
	const expressDiv = document.querySelector('.wfacp_smart_button_wrap_st');
	if (expressDiv) {
		insertPeachPayAt(expressDiv, 'beforeend');
	}
}

function insertPeachPayAt(element, location) {
	const full = php_data.button_position_cart_page === 'full' || !php_data.button_position_cart_page;
	const width = full ? '100%' : ((php_data.button_width_cart_page || '220') + 'px');
	element.insertAdjacentHTML(location, pp_peachpayButton);

	// We must do this because we changed the <img> tags to <object> tags to
	// fix a Safari issue where it would not load animated SVGs. If we have
	// the hide class on the element right away, the SVG is not loaded, so the
	// first time you click the button it's not there. Only after it is visible
	// does it load, so we allow it to be visible for a split second before
	// hiding it here.
	hideLoadingSpinner();

	document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
	init({
		width,
		position: php_data.button_position_cart_page,
		borderRadius: php_data.button_border_radius,
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

function farm2forkdeliveryCartPage() {
	const $minNotMet = Array.from(document.querySelectorAll('#minNotMet'));
	const peachpayButton = document.querySelector('#pp-button');
	if ($minNotMet.length > 0) {
		peachpayButton.disabled = true;
		peachpayButton.style.cssText += ';opacity: 0.5 !important;';
	} else {
		peachpayButton.disabled = false;
		peachpayButton.style.cssText += ';opacity: 1 !important;';
	}
}
