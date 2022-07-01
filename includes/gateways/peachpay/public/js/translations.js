// deno-lint-ignore-file

function getLanguage() {
	// If peachpay_wordpress_settings is defined, then we have loaded this script
	// from the plugin deactivation page.
	if (window.peachpay_wordpress_settings) {
		// Transform WP_LOCALE format to our locale format
		if (peachpay_wordpress_settings.locale.includes('_')) {
			split_arr = peachpay_wordpress_settings.locale.split('_');

			return split_arr[0] + '-' + split_arr[1].toUpperCase();
		}

		return peachpay_wordpress_settings.locale;
	}

	if (!peachpay_data || !peachpay_data.language) {
		return 'en-US';
	}

	let language = peachpay_data.language;

	if (peachpay_data.language === 'detect-from-page') {
		language = languageCodeToLocale(getPageLanguage());
		const englishVariants = new Set(['en-AU', 'en-CA', 'en-GB', 'en-NZ', 'en-ZA']);
		if (englishVariants.has(language)) {
			language = 'en-US';
		}
	}
	return language;
}

// The below section is for translating our checkout window when the parent
// page changes language. For example, on the site there may be a Google
// Translate widget which allows language selection. Since the checkout
// window is in an iframe, the widget will not change its text. We have to
// listen for language changes on the page and inform the checkout window to
// update which language text is shown.

document.addEventListener('DOMContentLoaded', peachpayListenForPageTranslate);

function peachpayListenForPageTranslate() {
	const $helper = document.querySelector('.peachpay-translate-helper');
	if ($helper) {
		let previous = document.querySelector('html').lang;
		$helper.addEventListener('DOMSubtreeModified', () => {
			const current = document.querySelector('html').lang;
			if (previous !== current) {
				previous = current;
				sendPageLanguageChangeMessage(current);
			}
		});
	} else {
		self.requestAnimationFrame(peachpayListenForPageTranslate);
	}
}

function sendPageLanguageChangeMessage(language) {
	const peachpay = document.querySelector('#peachpay-iframe');
	if (!peachpay) {
		return;
	}

	peachpay.contentWindow.postMessage({
		event: 'pageLanguageChange',
		language: languageCodeToLocale(language),
	}, '*');
}

// deno-lint-ignore no-unused-vars
function getPluginLocaleText(text, isAdminPageText = false) {
	language = getLanguage();

	if (language == 'en-US' || !PEACHPAY_PLUGIN_i18n[language] || !PEACHPAY_PLUGIN_i18n[language][text]) {
		return text;
	}

	// Admin page only supports English and Japanese
	if (isAdminPageText) {
		return (language === 'ja' ? PEACHPAY_PLUGIN_i18n['ja'][text] : text);
	}

	return PEACHPAY_PLUGIN_i18n[language][text];
}

/**
 * Given a ISO 639-1 language code, get the locale that PeachPay supports for
 * that language. If there is no match, it will return the same language code.
 * This is used to turn the html lang attribute into a format that we can use
 * to decide which translation to use.
 *
 * It does not indicate whether or not we support that language in PeachPay.
 *
 * @param {string} languageCode As per ISO 639-1.
 * @returns Locale code that has country and langauge information, roughly the
 * one that WordPress uses. For example: en-US
 */
function languageCodeToLocale(languageCode) {
	switch (languageCode) {
		case 'cs':
			return 'cs-CZ';
		case 'da':
			return 'da-DK';
		case 'de':
		case 'de-AT':
		case 'de-DE':
		case 'de-CH':
			return 'de-DE';
		case 'en':
		case 'en-AU':
		case 'en-CA':
		case 'en-GB':
		case 'en-NZ':
		case 'en-ZA':
			return 'en-US';
		case 'es':
		case 'es-MX':
		case 'es-AR':
		case 'es-CL':
		case 'es-PE':
		case 'es-PR':
		case 'es-GT':
		case 'es-CO':
		case 'es-EC':
		case 'es-VE':
		case 'es-UY':
		case 'es-CR':
			return 'es-ES';
		case 'fr':
		case 'fr-BE':
		case 'fr-CA':
		case 'fr-FR':
			return 'fr-FR';
		case 'hi':
			return 'hi-IN';
		case 'it-IT':
			return 'it';
		case 'ko':
			return 'ko-KR';
		case 'lb':
			return 'lb-LU';
		case 'nl':
		case 'nl-BE':
		case 'nl-NL':
			return 'nl-NL';
		case 'pt':
		case 'pt-AO':
		case 'pt-BR':
		case 'pt-PT-ao90':
		case 'pt-PT':
			return 'pt-PT';
		case 'ru':
			return 'ru-RU';
		case 'ro':
			return 'ro-RO';
		case 'sl':
			return 'sl-SI';
		case 'sv':
			return 'sv-SE';
		case 'bg':
			return 'bg-BG';
		case 'auto':
			return navigator.language || navigator.userLanguage;
		default:
			return languageCode;
	}
}
