/**
 * JP4WC Block Checkout Field Ordering and Address Display
 * 
 * Moves yomigana fields to appear after name fields in the checkout block.
 * Fixes address preview placeholders display.
 */
(function() {
	'use strict';

	/**
	 * Reorder yomigana fields to appear after name fields.
	 */
	function reorderYomiganaFields() {
		const addressForms = document.querySelectorAll('.wc-block-components-address-form');
		
		if (!addressForms.length) {
			return false;
		}

		let moved = false;

		addressForms.forEach(function(form, index) {
		// Debug: List all input IDs in this form
		const allInputs = form.querySelectorAll('input[type="text"], input[type="tel"], input[type="email"]');
		const inputIds = Array.from(allInputs).map(function(input) { return input.id; });
		
		// Try multiple selector patterns to handle both billing and shipping
		const lastNameField = form.querySelector(
			'input[id*="last_name"]:not([id*="yomigana"])'
		);
		const firstNameField = form.querySelector(
			'input[id*="first_name"]:not([id*="yomigana"])'
		);
		const yomiganaLastName = form.querySelector(
			'input[id*="yomigana_last_name"]'
		);
		const yomiganaFirstName = form.querySelector(
			'input[id*="yomigana_first_name"]'
		);

			const lastNameContainer = lastNameField ? lastNameField.closest('.wc-block-components-text-input, .wc-block-components-field') : null;
			const firstNameContainer = firstNameField ? firstNameField.closest('.wc-block-components-text-input, .wc-block-components-field') : null;
			const yomiganaLastContainer = yomiganaLastName ? yomiganaLastName.closest('.wc-block-components-text-input, .wc-block-components-field') : null;
			const yomiganaFirstContainer = yomiganaFirstName ? yomiganaFirstName.closest('.wc-block-components-text-input, .wc-block-components-field') : null;

			if (firstNameContainer && yomiganaLastContainer && firstNameContainer.nextElementSibling !== yomiganaLastContainer) {
				firstNameContainer.insertAdjacentElement('afterend', yomiganaLastContainer);
				moved = true;
			}

			if (yomiganaLastContainer && yomiganaFirstContainer && yomiganaLastContainer.nextElementSibling !== yomiganaFirstContainer) {
				yomiganaLastContainer.insertAdjacentElement('afterend', yomiganaFirstContainer);
				moved = true;
			}
		});

		return moved;
	}

	/**
	 * Initialize field reordering and address preview fixes.
	 */
	function init() {
		reorderYomiganaFields();

		const observer = new MutationObserver(function(mutations) {
			clearTimeout(window.jp4wcReorderTimeout);
			window.jp4wcReorderTimeout = setTimeout(function() {
				reorderYomiganaFields();
			}, 100);
		});

		const checkoutBlock = document.querySelector('.wp-block-woocommerce-checkout');
		if (checkoutBlock) {
			observer.observe(checkoutBlock, {
				childList: true,
				subtree: true,
				characterData: true,
				characterDataOldValue: true
			});
		}

		// Run multiple times to ensure fields are reordered even if React re-renders
		setTimeout(function() {
			reorderYomiganaFields();
		}, 300);

		setTimeout(function() {
			reorderYomiganaFields();
		}, 1000);

		setTimeout(function() {
			reorderYomiganaFields();
		}, 2000);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
