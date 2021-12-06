document.addEventListener('DOMContentLoaded', initPreviews);
document.addEventListener('DOMContentLoaded', clickContactForm);

function clickContactForm() {
	const urlSearchParameters = new URLSearchParams(window.location.search);
	const parameters = Object.fromEntries(urlSearchParameters.entries());
	if ('open_help' in parameters) {
		const checkExist = setInterval(() => {
			if (document.querySelector('.eapps-form-button')) {
				const $form = document.querySelector('.eapps-form-floating-button, .eapps-form-floating-button-type-text, .eapps-form-floating-button-position-right, .eapps-form-button eapps-form-floating-button-visible');
				$form.click();
				clearInterval(checkExist);
			}
		}, 100);
	}
}

function initPreviews() {
	initProductPagePreview();
	initCartPagePreview();
	initCheckoutPagePreview();

	const colorInput = document.querySelector('#peachpay_button_color');
	colorInput.addEventListener('input', event => updateColor(event, 'product'));
	colorInput.addEventListener('input', event => updateColor(event, 'cart'));
	colorInput.addEventListener('input', event => updateColor(event, 'checkout'));

	const buttonIcon = document.querySelector('#peachpay_button_icon');
	if (buttonIcon) {
		updateIcon(buttonIcon.value, 'product');
		updateIcon(buttonIcon.value, 'cart');
		updateIcon(buttonIcon.value, 'checkout');
		buttonIcon.addEventListener('change', event => updateIcon(event.target.value, 'product'));
		buttonIcon.addEventListener('change', event => updateIcon(event.target.value, 'cart'));
		buttonIcon.addEventListener('change', event => updateIcon(event.target.value, 'checkout'));
	}

	const widthInputProduct = document.querySelector('#button_width_product_page');
	widthInputProduct.addEventListener('input', event => updateWidth(event, 'product'));

	const widthInputCart = document.querySelector('#button_width_cart_page');
	widthInputCart.addEventListener('input', event => updateWidth(event, 'cart'));

	const widthInputCheckout = document.querySelector('#button_width_checkout_page');
	if (widthInputCheckout) {
		widthInputCheckout.addEventListener('input', event => updateWidth(event, 'checkout'));
	}

	const radiusInput = document.querySelector('#button_border_radius');
	radiusInput.addEventListener('input', event => updateRadius(event, 'product'));
	radiusInput.addEventListener('input', event => updateRadius(event, 'cart'));
	radiusInput.addEventListener('input', event => updateRadius(event, 'checkout'));

	const text = document.querySelector('#peachpay_button_text');
	text.addEventListener('input', event => updateText(event, 'product'));
	text.addEventListener('input', event => updateText(event, 'cart'));
	text.addEventListener('input', event => updateText(event, 'checkout'));

	const shine = document.querySelector('#peachpay_button_sheen');
	if (!shine.checked) {
		addShine();
	}

	shine.addEventListener('input', updateShine);

	const paymentIcons = document.querySelector('#peachpay_payment_method_icons');
	if (paymentIcons) {
		if (paymentIcons.checked) {
			hidePaymentIcons();
		}

		paymentIcons.addEventListener('input', updatePaymentIcons);
	}

	const language = document.querySelector('#peachpay_language');
	language.addEventListener('input', updateLanguage);
}

function initProductPagePreview() {
	const button = document.querySelector('#pp-button-product');
	const initialWidth = document.querySelector('#button_width_product_page').value;
	button.style.width = initialWidth.toString() + 'px';

	const initialRadius = document.querySelector('#button_border_radius').value;
	button.style.borderRadius = initialRadius.toString() + 'px';
}

function initCartPagePreview() {
	const button = document.querySelector('#pp-button-cart');
	const initialWidth = document.querySelector('#button_width_cart_page').value;
	button.style.width = initialWidth.toString() + 'px';

	const initialRadius = document.querySelector('#button_border_radius').value;
	button.style.borderRadius = initialRadius.toString() + 'px';
}

function initCheckoutPagePreview() {
	const button = document.querySelector('#pp-button-checkout');
	if (!button) {
		return;
	}

	if (document.querySelector('#button_width_checkout_page')) {
		const initialWidth = document.querySelector('#button_width_checkout_page').value;
		button.style.width = initialWidth.toString() + 'px';
	}

	if (document.querySelector('#button_border_radius')) {
		const initialRadius = document.querySelector('#button_border_radius').value;
		button.style.borderRadius = initialRadius.toString() + 'px';
	}
}

function updateWidth(event, page) {
	const button = document.querySelector('#pp-button-' + page);
	button.style.width = event.target.value.toString() + 'px';
}

function updateRadius(event, page) {
	const button = document.querySelector('#pp-button-' + page);
	button.style.borderRadius = event.target.value.toString() + 'px';
}

function updateColor(event, page) {
	const button = document.querySelector('#pp-button-' + page);
	button.style.setProperty('--button-color', event.target.value);
}

function updateIcon(icon_value, page) {
	const icons = document.querySelector('#button-icon-' + page);
	if (icons) {
		icons.classList.remove('skre-icon');
		icons.classList.remove('hide');
		icons.classList.add('pp-btn-symbol');
		switch (icon_value) {
			case 'lock':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/lock.svg';
				break;
			case 'baseball':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/baseball-ball-solid.svg';
				break;
			case 'arrow':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/chevron-circle-right-solid.svg';
				break;
			case 'mountain':
				icons.classList.remove('pp-btn-symbol');
				icons.classList.add('skre-icon');
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/skre.svg';
				break;
			case 'bag':
				icons.src = '/wp-content/plugins/peachpay-for-woocommerce/img/briefcase-solid.svg';
				break;
			default:
				icons.classList.add('hide');
		}
	}
}

const buttonTextTranslations = {
	'detect-from-page': 'Express checkout',
	ar: 'الخروج السريع',
	ca: 'Pagament exprés',
	'cs-CZ': 'Expresní pokladna',
	'da-DK': 'Hurtig betaling',
	'de-DE': 'Expresskauf',
	el: 'Γρήγορο ταμείο',
	'en-US': 'Express checkout',
	'es-ES': 'Chequeo rápido',
	fr: 'Acheter maintenant',
	'hi-IN': 'स्पष्ट नियंत्रण',
	it: 'Cassa rapida',
	ja: 'エクスプレスチェックアウト',
	'ko-KR': '익스프레스 체크아웃',
	'lb-LU': 'Express Kees',
	'nl-NL': 'Snel afrekenen',
	'pt-PT': 'Checkout expresso',
	'ro-RO': 'Cumpără cu 1-click',
	'ru-RU': 'Экспресс-касса',
	'sl-SI': 'Hitra odjava',
	'sv-SE': 'snabbkassa',
	th: 'ชำระเงินด่วน',
	uk: 'Експрес -оплата',
	'zh-CN': '快速结帐',
	'zh-TW': '快速結帳',
};

function updateText(event, page) {
	if (event.target.value === '') {
		const button = document.querySelector('#pp-button-' + page);
		const language = document.querySelector('#peachpay_language');
		button.innerHTML = '<span id="pp-button-text">' + buttonTextTranslations[language.value] + '</span>';
	} else {
		const button = document.querySelector('#pp-button-' + page);
		button.innerHTML = '<span id="pp-button-text">' + event.target.value + '</span>';
	}
}

function updateShine(event) {
	if (event.target.checked) {
		const buttonCSS = document.querySelector('#buttonShine');
		buttonCSS.remove();
	} else {
		addShine();
	}
}

function updateLanguage() {
	const productButton = document.querySelector('#pp-button-product');
	const cartButton = document.querySelector('#pp-button-cart');
	const checkoutButton = document.querySelector('#pp-button-checkout');

	const translatedText = buttonTextTranslations[this.value];
	productButton.innerHTML = '<span id="pp-button-text">' + translatedText + '</span>';
	cartButton.innerHTML = '<span id="pp-button-text">' + translatedText + '</span>';
	checkoutButton.innerHTML = '<span id="pp-button-text">' + translatedText + '</span>';
}

function updatePaymentIcons(event) {
	hidePaymentIcons(event.target.checked);
}

function addShine() {
	const peachpay_buttonShineCSS = `
    <style id = buttonShine>
    @keyframes shine {
        100% {
            left: 200%;
        }
    }
    .pp-button:after,
    .pp-button-mini:after {
        animation: shine 1.8s ease infinite;
        background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
        content: '';
        display: inherit;
        height: 200%;
        left: -200%;
        position: absolute;
        top: 0;
        transform: skewX(-20deg);
        width: 50%;
    }
    </style>`;
	document.querySelector('body').insertAdjacentHTML('beforeend', peachpay_buttonShineCSS);
}

function hidePaymentIcons(hide = true) {
	const buttonLocations = ['product', 'cart', 'checkout'];
	for (const location of buttonLocations) {
		const icons = document.querySelector(`#payment-methods-container-${location}`);
		if (icons) {
			if (hide) {
				icons.classList.add('hide');
			} else {
				icons.classList.remove('hide');
			}
		}
	}
}
