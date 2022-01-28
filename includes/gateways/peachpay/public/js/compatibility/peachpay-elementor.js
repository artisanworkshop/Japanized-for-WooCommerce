class PeachPayHandlerClass extends elementorModules.frontend.handlers.Base {
	getDefaultSettings() {
		const button = document.querySelector('#pp-data');
		const buttonText = button.dataset.text;
		const buttonColor = button.dataset.color;
		peachpay_data.button_text = buttonText;
		peachpay_data.button_color = buttonColor;
	}
}

jQuery(window).on('elementor/frontend/init', () => {
	const addHandler = ($element) => {
		elementorFrontend.elementsHandler.addHandler(PeachPayHandlerClass, {
			$element,
		});
	};

	elementorFrontend.hooks.addAction('frontend/element_ready/peachpay.default', addHandler);
});

document.addEventListener('DOMContentLoaded', () => {
	if (peachpay_isElementor()) {
		if (!document.querySelector('#pp-modal-overlay')) {
			document.querySelector('body').insertAdjacentHTML('beforeend', pp_checkoutForm);
		}

		peachpay_initButton({ width: '350' });
	}
});
