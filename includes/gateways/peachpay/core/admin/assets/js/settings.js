document.addEventListener('DOMContentLoaded', initPreviews);
document.addEventListener('DOMContentLoaded', clickContactForm);
document.addEventListener('DOMContentLoaded', setupLeaveListener);
document.addEventListener('DOMContentLoaded', setupOCULivePreview);
document.addEventListener('DOMContentLoaded', buttonPageListener);

let submitIsClicked = false;

function setupLeaveListener() {
	const listenForChangeForm = document.querySelector('#peachpay_settings_container');

	const allInputElements = Array.from(listenForChangeForm.querySelectorAll('select, input, textarea, tr'));
	for(let i in allInputElements) {
		if(allInputElements[i].data_originalValue) continue;

		if(allInputElements[i].type == 'checkbox') {
			allInputElements[i].data_originalValue = allInputElements[i].checked;
		}else if(allInputElements[i].tagName == 'TR') {
			allInputElements[i].data_originalValue = allInputElements[i].className;
		}else {
			allInputElements[i].data_originalValue = allInputElements[i].value;
		}
	}

	//exception elements to be included
	const modal_list_item = document.querySelector('#list-item');
	if(modal_list_item) {
		modal_list_item.data_originalValue = modal_list_item.innerHTML.replace(/(\/(?=[a-z]).|\s)/g,'');
	}

	//exception elemnets to be excluded
	const exceptionElements = Array.from(listenForChangeForm.querySelectorAll('input[name=select_field], .select-all'));
	for(let i in exceptionElements) {
		if(exceptionElements[i].data_originalValue) continue;
		exceptionElements[i].data_originalValue = null;
	}

	let submitButtons = document.querySelectorAll('#submit, button[type="submit"]');
	if (!submitButtons || submitButtons.length === 0) {
		// make sure the submit button is found, or else the unsaved-settings-popup might appear when clicking it
		console.log("No submit button found. If there is one, make sure this query selector is able to find it.");
	}

	for (const button of submitButtons) {
		button.addEventListener('click', stopPopupOnSubmit);
	}
	window.onbeforeunload = onUserLeave;
}

function onUserLeave(e) {
	if(submitIsClicked) return;

	const listenForChangeForm = document.querySelector("#peachpay_settings_container");
	const mod = document.querySelector('#ppModal');
	if(someInputHasChanged(listenForChangeForm) || (mod && mod.style.display==='block' && modalHasChanged(mod))) {
		message = 'Changes you made have not been saved.';
		(e || window.event).returnValue = message;
		return message;
	}
}

function elementHasChanged(element) {
	if(element.data_originalValue === null || element.data_originalValue === undefined) return false; // ignore exception elements

	if(element.type == 'checkbox') {
		if(element.checked != element.data_originalValue) return true;
	}else if(element.tagName == 'TR') {
		if(element.className.trim() != element.data_originalValue.trim()) return true;
	}else if(element.id == 'list-item') {
		if(element.innerHTML.replace(/(\/(?=[a-z]).|\s)/g,'') != element.data_originalValue) return true;
	}else {
		if(element.value != element.data_originalValue) return true;
	}
	return false;
}

function someInputHasChanged(formElement) {
    const inputElements = Array.from(formElement.querySelectorAll('input, select, textarea, tr, #list-item'));
	for(let i in inputElements) {
		if(elementHasChanged(inputElements[i])) return true;
	}
	return false;
}

function stopPopupOnSubmit() {
	submitIsClicked = true;
}


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

function peachpay_initAdminColorInputGroup(inputGroupSelector, cssVariable) {
	const $colorPicker = document.querySelector(`${inputGroupSelector} input[type="color"]`);
	const $colorTextBox = document.querySelector(`${inputGroupSelector} input[type="text"]`);

	if (!$colorPicker || !$colorTextBox) {
		return;
	}

	const syncColorPickerToColorTextBox = () => {
		$colorTextBox.value = $colorPicker.value;
		document.documentElement.style.setProperty(cssVariable, $colorPicker.value);
	}

	const syncColorTextBoxToColorPicker = () => {
		$colorPicker.value = $colorTextBox.value;
		document.documentElement.style.setProperty(cssVariable, $colorTextBox.value);
	}

	$colorPicker.addEventListener('input', syncColorPickerToColorTextBox);
	$colorTextBox.addEventListener('input', syncColorTextBoxToColorPicker);

	document.documentElement.style.setProperty(cssVariable, $colorTextBox.value);
}

function initPreviews() {
	const urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('tab') !== 'button') {
		return;
	}

	initPreviewButton();
	initShopPagePreview();

	peachpay_initAdminColorInputGroup(
		"#peachpay_button_background_color",
		"--pp-button-background-color"
	);

	peachpay_initAdminColorInputGroup(
		"#peachpay_button_text_color",
		"--pp-button-text-color"
	);

	const buttonIcons = document.querySelectorAll('input[name=peachpay_button_options\\[button_icon\\]]');
	for (const icon of buttonIcons) {
		icon.addEventListener('change', event => {
			if (event.target.checked) {
				updateIcon(event.target.value, 'preview');
			}
		})
		if (icon.checked) {
			updateIcon(icon.value, 'preview');
		}
	}

	const floatButtonIcons = document.querySelectorAll('input[name=peachpay_button_options\\[floating_button_icon\\]]');
	for (const icon of floatButtonIcons) {
		icon.addEventListener('change', event => {
			if (event.target.checked) {
				updateIcon(event.target.value, 'shop');
			}
		})
		if (icon.checked) {
			updateIcon(icon.value, 'shop');
		}
	}

	const sizeInputStore = document.querySelector('#floating_button_size');
	if (sizeInputStore) {
		sizeInputStore.addEventListener('input', event => {
			const button = document.querySelector('#pp-button-shop');
			button.style.width = event.target.value.toString() + 'px';
			button.style.height = event.target.value.toString() + 'px';
		});
	}

	const iconSizeInputStore = document.querySelector('#floating_button_icon_size');
	if (iconSizeInputStore) {
		iconSizeInputStore.addEventListener('input', event => {
			const icon = document.querySelector('#button-icon-shop');
			icon.style = 'width: ' + event.target.value.toString() + 'px !important;' + 'height: ' + event.target.value.toString() + 'px !important;';
		});
	}

	const radiusInput = document.querySelector('#button_border_radius');
	radiusInput.addEventListener('input', event => updateRadius(event, 'preview'));

	const text = document.querySelector('#peachpay_button_text');
	if (text) {
		text.addEventListener('input', event => updateText(event, 'preview'));
	}

	setEffect();

	const buttonEffects = document.querySelectorAll('input[name=peachpay_button_options\\[button_effect\\]]');
	for (const effect of buttonEffects) {
		effect.addEventListener('change', setEffect);
	}

	function setEffect() {
		const fade = document.querySelector('#buttonFade');
		if (fade) {
			fade.remove();
		}

		const effect = document.querySelector('input[name=peachpay_button_options\\[button_effect\\]]:checked');
		if (effect && effect.value === 'fade') {
			addFade();
		}
	}

	const disablePeachPayFontCSS = document.querySelector('#peachpay_disable_default_font_css');
	if (disablePeachPayFontCSS && !disablePeachPayFontCSS.checked) {
		updateFontCSS(false);
	}

	disablePeachPayFontCSS.addEventListener('input', (event) => { event.target.checked ? updateFontCSS(true) : updateFontCSS(false) });

	const paymentIcons = document.querySelector('#peachpay_payment_method_icons');
	if (paymentIcons) {
		if (!paymentIcons.checked) {
			hidePaymentIcons();
		}

		paymentIcons.addEventListener('input', updatePaymentIcons);
	}
}

function initPreviewButton() {
	const button = document.querySelector('#pp-button-preview');
	if (button) {
		button.style.width = 220 + 'px';

		const initialRadius = document.querySelector('#button_border_radius').value;
		button.style.borderRadius = initialRadius.toString() + 'px';
	}
}

function initShopPagePreview() {
	const button = document.querySelector('#pp-button-shop');

	if (document.querySelector('#floating_button_size')) {
		const initialLength = document.querySelector('#floating_button_size').value;
		button.style.width = initialLength.toString() + 'px';
		button.style.height = initialLength.toString() + 'px';
	}

	if (document.querySelector('#floating_button_icon_size')) {
		const initialIconSize = document.querySelector('#floating_button_icon_size').value;
		const icon = document.querySelector('#button-icon-shop');
		if (icon) {
			icon.style = 'width:' + initialIconSize.toString() + 'px !important;' + 'height: ' + initialIconSize.toString() + 'px !important;';
		}
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

function updateIcon(icon_value, page) {
	const icons = document.querySelector('#button-icon-' + page);
	if (icons) {
		icons.classList.remove('skre-icon');
		icons.classList.remove('hide');
		icons.classList.add('pp-btn-symbol');
		switch (icon_value) {
			case 'lock':
				icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/lock.svg';
				break;
			case 'baseball':
				icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/baseball-ball-solid.svg';
				break;
			case 'arrow':
				icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/chevron-circle-right-solid.svg';
				break;
			case 'mountain':
				icons.classList.remove('pp-btn-symbol');
				icons.classList.add('skre-icon');
				icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/skre.svg';
				break;
			case 'bag':
				icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/briefcase-solid.svg';
				break;
			case 'shopping_cart':
				icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/shopping-cart-solid.svg';
				break;
			default:
				if (page === 'shop') {
					icons.src = peachpay_wordpress_settings.plugin_asset_url + '/public/img/shopping-cart-solid.svg';
				} else {
					icons.classList.add('hide');
				}
		}
	}
}

const buttonTextTranslations = {
	'detect-from-page': 'Express checkout',
	ar: '???????????? ????????????',
	ca: 'Pagament expr??s',
	'cs-CZ': 'Expresn?? pokladna',
	'da-DK': 'Hurtig betaling',
	'de-DE': 'Expresskauf',
	el: '?????????????? ????????????',
	'en-US': 'Express checkout',
	'es-ES': 'Chequeo r??pido',
	'fr-FR': 'Acheter maintenant',
	'hi-IN': '?????????????????? ????????????????????????',
	it: 'Cassa rapida',
	ja: '???????????????????????????????????????',
	'ko-KR': '??????????????? ????????????',
	'lb-LU': 'Express Kees',
	'nl-NL': 'Snel afrekenen',
	'pt-PT': 'Checkout expresso',
	'ro-RO': 'Cump??r?? cu 1-click',
	'ru-RU': '????????????????-??????????',
	'sl-SI': 'Hiter Nakup',
	'sv-SE': 'snabbkassa',
	th: '????????????????????????????????????',
	uk: '?????????????? -????????????',
	'zh-CN': '????????????',
	'zh-TW': '????????????',
};

function updateText(event, page) {
	if (event.target.value === '') {
		const button = document.querySelector('#pp-button-' + page);
		button.innerHTML = '<span id="pp-button-text">' + buttonTextTranslations['en-US'] + '</span>';
	} else {
		const button = document.querySelector('#pp-button-' + page);
		button.innerHTML = '<span id="pp-button-text">' + event.target.value + '</span>';
	}
}

function updateFontCSS(checked) {
	const ppButtons = document.querySelectorAll('.pp-button');
	if (ppButtons[0]) {
		ppButtons.forEach((ppButton) => {
			checked ? ppButton.classList.remove('pp-button-default-font') : ppButton.classList.add('pp-button-default-font');
		})
	}
}

function updatePaymentIcons(event) {
	hidePaymentIcons(!event.target.checked);
}

function addFade() {
	const peachpay_buttonFadeCSS = `
    <style id = buttonFade>
    #pp-button-container .pp-button {
		color: var(--pp-button-text-color) !important;
		border: 2px solid var(--pp-button-background-color);
		background: linear-gradient(to right,var(--pp-button-text-color) 50%,var(--pp-button-background-color) 50%);
		background-size: 200% 100%;
		background-origin: border-box;
		background-position: right bottom;
		transition: all .4s ease;
	}
	#pp-button-container .pp-button:hover:not(.pp-button-mute), #pp-button-container .pp-button:focus:not(.pp-button-mute) {
		color: var(--pp-button-background-color) !important;
		border: 2px solid var(--pp-button-background-color);
		background-position: left bottom;
	}
	.pp-button:hover:not(.pp-button-mute) {
		opacity: 1.00 !important;
	}
    </style>`;
	const body = document.querySelector('body');
	if (body) {
		body.insertAdjacentHTML('beforeend', peachpay_buttonFadeCSS);
	}
}

function hidePaymentIcons(hide = true) {
	const icons = document.querySelector(`#payment-methods-container-preview`);
	if (icons) {
		if (hide) {
			icons.classList.add('hide');
		} else {
			icons.classList.remove('hide');
		}
	}
}

function setupOCULivePreview() {
	const urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('tab') !== 'related_products') {
		return;
	}

	const ocuProductImg = document.querySelector('.pp-ocu-product-img');
	const ocuProductName = document.querySelector('.pp-ocu-product-name');
	const ocuProductDescription = document.querySelector('.pp-ocu-product-description');
	const ocuProductPrice = document.querySelector('.pp-ocu-product-price');
	const ocuPrimaryHeader = document.querySelector('#peachpay_one_click_upsell_primary_header');
	const ocuSecondaryHeader = document.querySelector('#peachpay_one_click_upsell_secondary_header');
	const ocuAcceptButtonText = document.querySelector('#peachpay_one_click_upsell_accept_button_text');
	const ocuDeclineButtonText = document.querySelector('#peachpay_one_click_upsell_decline_button_text');

	if ( ocuPrimaryHeader ) {
		ocuPrimaryHeader.addEventListener('input', (event) => {
			!event.target.value ? document.querySelector('.pp-one-click-upsell-headline').innerHTML = "You may also like &#8230;" : document.querySelector('.pp-one-click-upsell-headline').textContent = event.target.value;
		});
	}

	if ( ocuSecondaryHeader ) {
		ocuSecondaryHeader.addEventListener('input', (event) => {
			event.target.value ? document.querySelector('.pp-one-click-upsell-sub-headline').classList.remove('hide') : document.querySelector('.pp-one-click-upsell-sub-headline').classList.add('hide');
			document.querySelector('.pp-one-click-upsell-sub-headline').textContent = event.target.value;
		});
	}

	if ( ocuAcceptButtonText ) {
		ocuAcceptButtonText.addEventListener('input', (event) => {
			!event.target.value ? document.querySelector('.pp-ocu-accept-button').textContent = "Add to order" : document.querySelector('.pp-ocu-accept-button').textContent = event.target.value;
		});
	}

	if ( ocuDeclineButtonText ) {
		ocuDeclineButtonText.addEventListener('input', (event) => {
			!event.target.value ? document.querySelector('.pp-ocu-decline-button').textContent = "No thanks" : document.querySelector('.pp-ocu-decline-button').textContent = event.target.value;
		});
	}

	(function ($) {
		$(function(){
			$('.pp-product-search').on('select2:select', async function (e) {
				const data = e.params.data;
				const response = await getOCUProductData(data.id);
				const responseData = await response.json();

				ocuProductImg ? ocuProductImg.src = responseData.data.ocu_product_img : '';
				ocuProductName ? ocuProductName.innerText = responseData.data.ocu_product_name : '';
				ocuProductDescription ? ocuProductDescription.innerHTML = responseData.data.ocu_product_description : '';
				ocuProductPrice ? ocuProductPrice.innerHTML = responseData.data.ocu_product_price : '';
				ocuProductDescription ? $(ocuProductDescription).find("img").remove().end().html() : '';
			});
		});
	})(jQuery)
}

async function getOCUProductData( ocu_product_id ) {
	const formData = new FormData();

	formData.append('product_id', ocu_product_id);

	const response = await fetch(`/?wc-ajax=pp-ocu-product`, {
		method: 'POST',
		body: formData,
	});

	return response;
}

function buttonPageListener() {
	const productEnabler = document.querySelector('#peachpay_display_on_product_page');
	const productSettings = document.querySelector('#pp-product-page-settings');

	const cartEnabler = document.querySelector('#peachpay_enabled_on_cart_page');
	const cartSettings = document.querySelector('#pp-cart-page-settings');

	const checkoutEnabler = document.querySelector('#peachpay_enabled_on_checkout_page');
	const checkoutSettings = document.querySelector('#pp-checkout-page-settings');

	if (productEnabler && productSettings) {
		productEnabler.addEventListener('change', (event) => {
			event.target.checked ? productSettings.classList.remove('hide') : productSettings.classList.add('hide');
		});
	}

	if (cartEnabler && cartSettings) {
		cartEnabler.addEventListener('change', (event) => {
			event.target.checked ? cartSettings.classList.remove('hide') : cartSettings.classList.add('hide');
		})
	}

	if (checkoutEnabler && checkoutSettings) {
		checkoutEnabler.addEventListener('change', (event) => {
			event.target.checked ? checkoutSettings.classList.remove('hide') : checkoutSettings.classList.add('hide');
		})
	}
}
