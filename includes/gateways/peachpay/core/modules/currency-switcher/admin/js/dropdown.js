document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.chosen-container').forEach(($chosenContainer) => {
		initCurrencyTableChosenContainerEvents($chosenContainer);
	});
	
	/**
	 * Initializes event listeners within a currency table .chosen-container.
	 */
	function initCurrencyTableChosenContainerEvents($dropdown) {
		// Listens for click event on the dropdown field and adjusts the dropdown list position.
		const clickListener = (event) => {
			// Input field was clicked.
			if (!event.target.closest('.chosen-drop') && !event.target.classList.contains('search-choice-close')) {
				event.preventDefault();
				event.stopImmediatePropagation();
				
			}
		}
		$dropdown.addEventListener('click', clickListener);

		const mouseenterListener = () => {
			const bounds = $dropdown.getBoundingClientRect();
	
			const $dropdownList = $dropdown.querySelector('.chosen-drop');
			
			// Check if there's space to render the items below the dropdown; if not, render it above
			if (window.innerHeight - bounds.bottom - 20 > 200) {
				$dropdownList.style.top = `${bounds.bottom}px`;
				$dropdownList.style.height = `${window.innerHeight - bounds.bottom - 20}px`;
			} else {
				$dropdownList.style.top = `${bounds.top - 200}px`;
				$dropdownList.style.height = `200px`;
			}
			$dropdownList.style.left = `${bounds.left}px`;
			$dropdownList.style.width = `${bounds.right - bounds.left}px`;
		}
		$dropdown.addEventListener('mouseenter', mouseenterListener);

		document.querySelectorAll('.chosen-choices').forEach(($choicesContainer) => {
			// Fills the hidden input with the changed country selections
			const fillHiddenInput = function() {
				const select = $choicesContainer.closest('td').querySelector('.chosen-select');
				const selectionArray = Array.from(select.selectedOptions);
				const stringifiedSelections = selectionArray.reduce((stringified, item) => `${stringified},${item.value}`, '');

				let hiddenInput;

				if ($choicesContainer.closest('.chosen-container').id === 'pp_countries_base_chosen') {
					hiddenInput = document.getElementById('hiddenCountriesBase');
				} else {
					hiddenInput = $choicesContainer.closest('td').querySelector('input.countries');
				}

				if (hiddenInput) {
					hiddenInput.value = stringifiedSelections;
				}
			}

			// Observes changes to each currency's list of selected countries for which to enable the currency
			const selectionObserver = new MutationObserver(fillHiddenInput);

			// Observe any changes to the list of country selections
			selectionObserver.observe($choicesContainer, { childList: true });

			// Initialize the hidden input
			fillHiddenInput();
		});
	}
});