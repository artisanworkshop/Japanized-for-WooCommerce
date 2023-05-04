
/**
 * @file Adds the deactivation popup and handles The necessary fields editor functionality for the whole modal and the buttons
 * @author Brendan Yeong
 */

document.addEventListener('DOMContentLoaded', () => {
	addNewField();
	enableField();
	disableField();
	removeField();
	editField();
	reorderFields();
	selectAllCheckbox();
});

function reorderFields() {
	const drag = document.querySelector('#field-table');

	if (!drag) {
		return;
	}

	drag.addEventListener('drop', event => {
		event.preventDefault();
		const currentRow = document.querySelector('.field-data-row[draggable=true]');
		const targetRow = event.target.closest('.field-data-row');
		const all = Array.from(document.querySelectorAll('.field-data-row'));
		const currentRowIndex = all.indexOf(currentRow);
		const targetRowIndex = all.indexOf(targetRow);
		if (currentRowIndex !== targetRowIndex) {
			currentRow.remove();
			targetRow.insertAdjacentElement((currentRowIndex - targetRowIndex) > 0 ? 'beforebegin' : 'afterend', currentRow);
		}

		document.querySelectorAll('.field-data-row').forEach(row => {
			row.setAttribute('draggable', false);
		});
	});

	drag.addEventListener('mousedown', event => {
		const {target} = event;

		if (!target) {
			return;
		}

		if (target.closest('.dragable-icon')) {
			const row = target.closest('.field-data-row');
			row.setAttribute('draggable', true);
		}
	});

	drag.addEventListener('dragover', event => {
		// Prevent default to allow drop
		event.preventDefault();
	}, false);
}

function addNewField() {
	const newFieldPopUp = document.querySelectorAll('#field-table .field-button');

	for (const button of newFieldPopUp) {
		button.addEventListener('click', event => showModal(event));
	}
}

function editField() {
	const editFieldButton = document.querySelectorAll('#field-table .pp-edit-field');

	for (const button of editFieldButton) {
		button.addEventListener('click', editModal);
	}

	function editModal(event) {
		const selectedEditButton = document.querySelector(
			'#field-table #' + event.target.value,
		);
		showModal(event);
		insertEditData(selectedEditButton.value);
	}
}

function showModal(event) {
	event.preventDefault();
	const $modal = document.querySelector('#ppModal');
	$modal.style.display = 'block';
	if (document.querySelector('#modal-content')) {
		setTimeout(() => {
			rememberModalChanges($modal);
		}, 100);
		return;
	}

	const section = getURLSection();

	const $modalContent = `
<div id="modal-content" class="modal-new-field-content col flex">
	<div id="modal-header" class="new-field-modal-header">
		<span id="deactivation-header" class="new-field-header bold"
			>${getPluginLocaleText('Field details', isAdminPageText = true)}
		</span>
		<div id="close" aria-hidden="true" style="font-size: 24px; cursor: pointer;">&times;</div>
		<div id="pp-warning-container">
			<div id="pp-unsaved-warning" class="pp-unsaved-banner-hide">
				<span id="pp-close-confirm-text">You have unsaved changes.<br>Are you sure you want to close?</span>
				<button class="pp-confirm-unsaved">Close</button>
			</div>
		</div>
	</div>
	<div>
		<form id="field-info" class="modal-add-field form" method="post">
			<div class="input-field flex">
				<select id="field_type" class="p-1 input-box pp-w-100" name="type_list" form="field-info">
					<option value="text">${getPluginLocaleText('Text', isAdminPageText = true)}</option>
					<option value="select">Select</option>
					<option value="radio">Radio</option>
					<option value="tel">Phone</option>
					<option value="email">Email</option>
					<option value="checkbox">Checkbox</option>
					<option hidden value="state">States/Province</option>
					<option hidden value="country">Country</option>
					<option value="header">Header</option>
					<!-- <option value="textarea">Textarea</option> -->
				</select>
				<label for="field_type" class="pp-select-label">
					${getPluginLocaleText('Type:', isAdminPageText = true)}
					<abbr class="required" title="required">*</abbr>
				</label>
				<div class="tooltip">
					<div class="pp-tooltip-field-editors">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 19H11V17H13V19ZM15.07 11.25L14.17 12.17C13.45 12.9 13 13.5 13 15H11V14.5C11 13.4 11.45 12.4 12.17 11.67L13.41 10.41C13.78 10.05 14 9.55 14 9C14 7.9 13.1 7 12 7C10.9 7 10 7.9 10 9H8C8 6.79 9.79 5 12 5C14.21 5 16 6.79 16 9C16 9.88 15.64 10.68 15.07 11.25Z" fill="#616161"/>
						</svg>
					</div>
					<span class="tooltiptext">
						${getPluginLocaleText('Choose the type of field to add. A “Select” is a drop down menu, and “Radio” is a set of bubbles that you can choose one option from.', isAdminPageText = true)}
					</span>
				</div>
			</div>
			<div class="input-field flex">
				<input
					id="field_name"
					class="input-box pp-w-100"
					type="text"
					name="field_name"
					value="${section}_"
					placeholder=" "
					pattern="[a-z_]+[a-z0-9_]*"
					oninvalid="setCustomValidity('The name should start with a lowercase letter or underscore and be followed by any number of lowercase letters, digits or underscores.')"
					oninput="setCustomValidity('')"
					required
				/>
					<label for="field_name" class="pp-form-label pp-name-label">
						${getPluginLocaleText('Name:', isAdminPageText = true)} &#40;${getPluginLocaleText('must be unique', isAdminPageText = true)}&#41;
						<abbr class="required" title="required">*</abbr>
					</label>
					<div class="tooltip">
					<div class="pp-tooltip-field-editors">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 19H11V17H13V19ZM15.07 11.25L14.17 12.17C13.45 12.9 13 13.5 13 15H11V14.5C11 13.4 11.45 12.4 12.17 11.67L13.41 10.41C13.78 10.05 14 9.55 14 9C14 7.9 13.1 7 12 7C10.9 7 10 7.9 10 9H8C8 6.79 9.79 5 12 5C14.21 5 16 6.79 16 9C16 9.88 15.64 10.68 15.07 11.25Z" fill="#616161"/>
						</svg>
					</div>
					<span class="tooltiptext">
						${getPluginLocaleText('You can use anything, but it must be different from the other fields. If you are not trying to be compatible with another plugin, you can simply type the label.', isAdminPageText = true)}
					</span>
				</div>
				<br />
			</div>
			<div class="input-field flex">
				<input
					id="field_label"
					class="input-box pp-w-100"
					type="text"
					name="field_label"
					placeholder=" "
					required
				/>
					<label for="field_label" class="pp-form-label">
						${getPluginLocaleText('Label:', isAdminPageText = true)}
						<abbr class="required" title="required">*</abbr>
					</label>
					<div class="tooltip">
					<div class="pp-tooltip-field-editors">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 19H11V17H13V19ZM15.07 11.25L14.17 12.17C13.45 12.9 13 13.5 13 15H11V14.5C11 13.4 11.45 12.4 12.17 11.67L13.41 10.41C13.78 10.05 14 9.55 14 9C14 7.9 13.1 7 12 7C10.9 7 10 7.9 10 9H8C8 6.79 9.79 5 12 5C14.21 5 16 6.79 16 9C16 9.88 15.64 10.68 15.07 11.25Z" fill="#616161"/>
						</svg>
					</div>
					<span class="tooltiptext">
						${getPluginLocaleText('This is what will show in the checkout window and on the checkout page.', isAdminPageText = true)}
					</span>
				</div>
				<br />
			</div>
			<div id="field_default_box" class="input-field flex">
				<input
					id="field_default"
					class="input-box pp-w-100"
					type="text"
					name="field_default"
					placeholder=" "
				/>
				<label for="field_default" class="pp-form-label pp-default-label">${getPluginLocaleText('Default value:', isAdminPageText = true)} </label>
				<br />
				<div class="tooltip">
					<div class="pp-tooltip-field-editors">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 19H11V17H13V19ZM15.07 11.25L14.17 12.17C13.45 12.9 13 13.5 13 15H11V14.5C11 13.4 11.45 12.4 12.17 11.67L13.41 10.41C13.78 10.05 14 9.55 14 9C14 7.9 13.1 7 12 7C10.9 7 10 7.9 10 9H8C8 6.79 9.79 5 12 5C14.21 5 16 6.79 16 9C16 9.88 15.64 10.68 15.07 11.25Z" fill="#616161"/>
						</svg>
					</div>
					<span class="tooltiptext">
						${getPluginLocaleText('You can set a value for this field that should be already prefilled when the shopper opens the checkout.', isAdminPageText = true)}
					</span>
				</div>
			</div>
			<div class="input-field flex" id="field_width_box">
				<select id="width" class="p-1 input-box pp-w-100" name="width">
					<option value="100">100%</option>
					<option value="70">70%</option>
					<option value="50">50%</option>
					<option value="30">30%</option>
				</select>
				<label for="field-type" class ="pp-select-label"> Width <abbr class="required" title="required">*</abbr></label>
				<div class="tooltip">
					<div class="pp-tooltip-field-editors">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 19H11V17H13V19ZM15.07 11.25L14.17 12.17C13.45 12.9 13 13.5 13 15H11V14.5C11 13.4 11.45 12.4 12.17 11.67L13.41 10.41C13.78 10.05 14 9.55 14 9C14 7.9 13.1 7 12 7C10.9 7 10 7.9 10 9H8C8 6.79 9.79 5 12 5C14.21 5 16 6.79 16 9C16 9.88 15.64 10.68 15.07 11.25Z" fill="#616161"/>
						</svg>
					</div>
					<span class="tooltiptext">
						${getPluginLocaleText('The width takes into account the fields next to it in the field editor. For example, if the two fields are next to each other in the field editor, and they are both set to 50%, in the checkout they will be displayed side-by-side.', isAdminPageText = true)}
					</span>
				</div>
			</div>
			<div class="input-checkboxes" >
				<div class="input-checkboxes" id="input-checkboxes-required">
					<input
						id="field_required"
						type="checkbox"
						name="field_required"
						value="yes"
					/>
					<label for="field_required" >${getPluginLocaleText('Required', isAdminPageText = true)} </label><br />
				</div>
				<div class="input-checkboxes">
					<input
						id="field_enable"
						type="checkbox"
						name="field_enable"
						value="yes"
					/>
					<label for="field_enable"> ${getPluginLocaleText('Enable', isAdminPageText = true)} </label><br />
				</div>
				<!-- <div class="input-checkboxes">
					<input
						id="field_display_email"
						type="checkbox"
						name="field_display_email"
						value="yes"
					/>
					<label for="field_display_email"> ${getPluginLocaleText('Display in email', isAdminPageText = true)} </label><br />
				</div>
				<div class="input-checkboxes">
					<input
						id="field_display_order_details"
						type="checkbox"
						name="field_display_order_details"
						value="yes"
					/>
					<label for="field_display_order_details"> ${getPluginLocaleText('Display in Order Detail', isAdminPageText = true)} </label><br />
				</div> -->
			</div>
			<div id="option-list-summary" class="p-05 hide">
				<div id="pp-option-list-dropdown" class="pp-b-radius-05 flex" aria-expanded="false">
					<label id="pp-option-label" class="select-label pp-w-100 flex">Option Lists
					<i class="pp-up-icon pp-summary-option-icon pp-option-dropdown-box-icon hide" aria-hidden="true"></i>
					<i class="pp-down-icon pp-summary-option-icon pp-option-dropdown-box-icon" aria-hidden="true"></i></label>
				</div>
				<!-- Generate the option list when needed -->
				<div id="list-summary" class="pp-option-summary-box p-05">
					<div id="list-item">

					</div>
					<div style="display: flex; justify-content: flex-end; margin: 5px 0px 0px;">
						<button type="button" class="pp-add-option pp-button-primary" title="Add new option row">${getPluginLocaleText('+ Add new option', isAdminPageText = true)}</button>
					</div>
				</div>
			</div>
			<div class="submit-field flex">
				<button
					type="button"
					style="height: 36px; width: 72px; border-radius: 2px;"
					class="pp-cancel-field pp-button-secondary"
				>
				${getPluginLocaleText('Cancel', isAdminPageText = true)}
				</button>
				<button
					type="submit"
					style="height: 36px; width: 72px; border-radius: 2px;"
					class="field-button-submit pp-button-primary"
				>
				${getPluginLocaleText('Submit', isAdminPageText = true)}
				</button>
			</div>
		</form>
	</div>
</div>
`;

	$modal.insertAdjacentHTML('afterbegin', $modalContent);
	$modal.addEventListener('click', hideAddFieldModal);
	$modal.addEventListener('click', loseFocusOptionDropList);
	$modal.addEventListener('change', showOptionList);
	$modal.addEventListener('change', showRequiredFields);

	initOptionSummaryEvents();
	restrictAddingDefaultField();

	setTimeout(() => {
		rememberModalChanges($modal);
	}, 100);
	for (const element of document.querySelectorAll('.pp-confirm-unsaved, .pp-cancel-field')) {
		element.addEventListener('click', event => {
			event.target.id = 'close';
			event.pp_confirmButton = true;
			hideAddFieldModal(event);
			document.querySelector('#pp-unsaved-warning').className = 'pp-unsaved-banner-hide';
		});
	}
}

function initOptionSummaryEvents() {
	const optionList = document.querySelector('#list-item');
	const addOption = document.querySelector('.pp-add-option');
	optionList.addEventListener('click', deleteOptionRow);
	optionList.addEventListener('drop', stopDraggingOptionRow);
	optionList.addEventListener('mousedown', dragOptionRow);
	optionList.addEventListener('dragover', event => {
		// Prevent default to allow drop
		event.preventDefault();
	}, false);
	document.querySelector('#pp-option-list-dropdown')?.addEventListener('click', optionSummaryDropdown);
	document.querySelector('#pp-option-list-dropdown')?.addEventListener('keypress', event => {
		if (event.key === 'Enter' || event.key === ' ') {
			optionSummaryDropdown();
		}
	});

	if (addOption) {
		addOption.addEventListener('click', addOptionRow);
	}
}

function optionSummaryDropdown() {
	let dropdown = document.querySelector('#pp-option-list-dropdown')?.getAttribute('aria-expanded');
	if (dropdown === 'false') {
		dropdown = document.querySelector('#pp-option-list-dropdown')?.getAttribute('aria-expanded');
		showOptionDropList();
	} else {
		dropdown = document.querySelector('#pp-option-list-dropdown')?.getAttribute('aria-expanded');
		hideOptionDropList();
	}
}

/**
 * Check if cursor has lost focus of option drop list, I.E. clicked elsewhere in the modal.
 */
function loseFocusOptionDropList(event) {
	const isExpanded = document.querySelector('#pp-option-list-dropdown')?.getAttribute('aria-expanded');

	if (isExpanded && isExpanded === 'true' && !event.target.closest('#pp-option-list-dropdown') && !event.target.closest('#list-summary') && !event.target.closest('.pp-remove-option')) {
		hideOptionDropList();
	}
}

function showOptionDropList() {
	document.querySelector('#pp-option-list-dropdown')?.setAttribute('aria-expanded', 'true');
	document.querySelector('.pp-up-icon.pp-summary-option-icon')?.classList.remove('hide');
	document.querySelector('.pp-down-icon.pp-summary-option-icon')?.classList.add('hide');
	document.querySelector('#list-summary')?.classList.add('pp-option-summary-contents-opened');
}

function hideOptionDropList() {
	document.querySelector('#pp-option-list-dropdown')?.setAttribute('aria-expanded', 'false');
	document.querySelector('.pp-up-icon.pp-summary-option-icon')?.classList.add('hide');
	document.querySelector('.pp-down-icon.pp-summary-option-icon')?.classList.remove('hide');
	document.querySelector('#list-summary')?.classList.remove('pp-option-summary-contents-opened');
}

function dragOptionRow(event) {
	const {target} = event;

	if (!target) {
		return;
	}

	if (target.closest('.pp-draggable-icon-option')) {
		const row = target.closest('.list-option');
		row.setAttribute('draggable', true);
	}
}

function stopDraggingOptionRow(event) {
	event.preventDefault();
	const currentRow = document.querySelector('.list-option[draggable=true]');
	const targetRow = event.target.closest('.list-option');
	const all = Array.from(document.querySelectorAll('.list-option'));
	const currentRowIndex = all.indexOf(currentRow);
	const targetRowIndex = all.indexOf(targetRow);
	if (currentRowIndex !== targetRowIndex) {
		currentRow.remove();
		targetRow.insertAdjacentElement((currentRowIndex - targetRowIndex) > 0 ? 'beforebegin' : 'afterend', currentRow);
	}

	document.querySelectorAll('.list-option').forEach(row => {
		row.setAttribute('draggable', false);
	});
}

function showOptionList(event) {
	if (event.target !== document.querySelector('select#field_type.input-box')) {
		return;
	}

	const optionListSummary = document.querySelector('#option-list-summary');
	const options = document.querySelector('#list-item');

	if (event.target.value === 'select' || event.target.value === 'radio') {
		optionListSummary.classList.remove('hide');
		document.querySelector('#field_default_box.input-field').classList.add('hide');
		if (options.children.length === 0) {
			addNewOptionRow(options, true, options.children.length);
		}
	} else {
		optionListSummary.classList.add('hide');
		document.querySelector('#field_default_box.input-field').classList.remove('hide');
		if (options.children.length > 0) {
			while (options.firstChild) {
				options.firstChild.remove();
			}
		}
	}
}

function showRequiredFields(event) {
	if (event.target !== document.querySelector('select#field_type.input-box')) {
		return;
	}

	if (event.target.value === 'header') {
		document.querySelector('#field_default_box.input-field').classList.add('hide');
		document.querySelector('#field_width_box.input-field').classList.add('hide');
		document.querySelector('#field_width_box.input-field #width').setAttribute('disabled', '');
		document.querySelector('#input-checkboxes-required').classList.add('hide');
	} else if (event.target.value === 'tel' || event.target.value === 'email'
		|| event.target.value === 'country' || event.target.value === 'state') {
		document.querySelector('#field_default_box.input-field').classList.add('hide');
		if (event.target.value === 'state') {
			document.querySelector('#field_label').value = 'State/Provice';
			document.querySelector('#modal-content #field_label').classList.add('hide');
		} else {
			document.querySelector('#modal-content #field_label').removeAttribute('disabled', '');
			document.querySelector('#field_label').value = '';
			document.querySelector('#input-checkboxes-required').classList.remove('hide');
			document.querySelector('#field_width_box.input-field').classList.remove('hide');
			document.querySelector('#field_width_box.input-field #width').removeAttribute('disabled', '');
		}
	} else {
		document.querySelector('#field_default_box.input-field').classList.remove('hide');
		document.querySelector('#field_width_box.input-field').classList.remove('hide');
		document.querySelector('#input-checkboxes-required').classList.remove('hide');
		document.querySelector('#modal-content #field_label').removeAttribute('disabled', '');
		document.querySelector('#field_width_box.input-field #width').removeAttribute('disabled', '');
		document.querySelector('#field_label').value = '';
	}
}

function addNewOptionRow(target, endOfContainer = false, row, value = '', name = '') {
	const newRow = `
	<div class="flex list-option" draggable="false">
		<div class="flex p-025">
			<i class="dragable-icon pp-draggable-icon-option pp-w-50" aria-hidden="true"></i>
		</div>
		<div class="flex p-025" style="width: 40%;">
			<input
				id="option-name-row${row}"
				type="text" name="option[name][]"
				placeholder=" "
				class="pp-option-input-box pp-b-radius-05 p-05 pp-w-100"
				value="${name.replaceAll('"', '&quot;').replaceAll('\\\'', '\'')}">
			<label for="option-name-row${row}" class="pp-form-label pp-option-label">Option Text</label>
		</div>
		<div class="flex p-025" style="width: 40%;">
			<input
				id="option-value-row${row}"
				type="text" name="option[value][]"
				placeholder=" "
				class="pp-option-input-box pp-b-radius-05 p-05 pp-w-100"
				value="${value}"
				pattern="[A-Za-z0-9_ ]*"
				oninvalid="makeAlertVisible(this)"
				oninput="setCustomValidity('');"
			">
			<label for="option-value-row${row}" class="pp-form-label pp-option-label">Option Value</label>
		</div>
		<div class="flex p-025" style="width: 5%;">
			<button type="button" value="-" class="pp-w-100 pp-remove-option" title="Remove row">&times;</button>
		</div>
	</div>
	`;

	target.insertAdjacentHTML(endOfContainer ? 'beforeend' : 'afterend', newRow);
}

function makeAlertVisible($el) {
	if ($el.checkedValidity === 1) {
		$el.setCustomValidity('Option values can should contain only letters, numbers, underscores, and spaces');
		$el.checkedValidity = 0;
		return;
	}

	showOptionDropList();
	$el.checkedValidity = 1;
	setTimeout($el => $el.reportValidity(), 100, $el);
}

function addOptionRow(event) {
	const {target} = event;

	if (!target) {
		return;
	}

	if (target.closest('.pp-add-option')) {
		addNewOptionRow(document.querySelector('.list-option:last-child'), false, document.querySelector('#list-item').children.length);
		const optionList = document.querySelector('#list-summary');
		if (optionList) {
			optionList.scrollTop = optionList.scrollHeight;
		}
	}
}

function deleteOptionRow(event) {
	const {target} = event;

	if (!target) {
		return;
	}

	const options = document.querySelector('#list-item');
	if (target.closest('.pp-remove-option') && options.children.length > 1) {
		target.closest('.list-option').remove();
	}
}

function enableField() {
	const enableButtonField = document.querySelectorAll(
		'#field-table .enable-button',
	);
	for (const button of enableButtonField) {
		button.addEventListener('click', () => {
			disableOrEnable('yes');
		});
	}
}

function disableField() {
	const disableFieldButton = document.querySelectorAll(
		'#field-table .disable-button',
	);
	const section = getURLSection();
	for (const button of disableFieldButton) {
		button.addEventListener('click', event => {
			// #region Potentially remove in field editor next update.
			for (const $input of document.querySelectorAll(
				'#field-table tbody input[type=checkbox]:checked',
			)) {
				const doc = document.querySelector(
					`#field-table tbody .sort [name="peachpay_field_editor_${section}[${section}][` + $input.value + '][field_name]"]',
				);
				if (doc.value === 'billing_email') {
					alert('Billing email field cannot be removed');
					event.preventDefault();
					return;
				}
			}

			// #endregion
			disableOrEnable('');
		});
	}
}

function disableOrEnable(value) {
	for (const $input of document.querySelectorAll(
		'#field-table tbody input[type=checkbox]:checked',
	)) {
		const doc = document.querySelector(
			'#field-table tbody #field_' + $input.value + '.th_field_enable',
		);
		doc.innerHTML = value === 'yes' ? '&#10003;' : '-';
		const doc2 = document.querySelector(
			'#field-table tbody .field_' + $input.value + '#field_enable' + $input.id,
		);
		doc2.value = value;
		const row = document.querySelector(
			'#field-table tbody .row_' + $input.value,
		);
		if (value) {
			row.classList.remove('row-disabled');
		} else {
			row.classList.add('row-disabled');
		}
	}
}

function removeField() {
	const removeFieldButton = document.querySelectorAll('.remove-button, .pp-delete-field');
	for (const button of removeFieldButton) {
		button.addEventListener('click', removeSelectedField);
	}

	function removeSelectedField(event) {
		if (event.target.classList.contains('pp-delete-field') && defaultFieldChecker(event.target.value)) {
			return;
		}

		for (const $input of document.querySelectorAll(
			'#field-table tbody input[type=checkbox]:checked',
		)) {
			if (defaultFieldChecker($input.value)) {
				return;
			}
		}

		if (!confirm('Do you wish to remove the selected fields?')) {
			event.preventDefault();
			return;
		}

		if (event.target.classList.contains('pp-delete-field')) {
			deleteOptionField(event.target.value);
		} else {
			for (const $input of document.querySelectorAll(
				'#field-table tbody input[type=checkbox]:checked',
			)) {
				deleteOptionField($input.value);
			}
		}
	}
}

function defaultFieldChecker($value) {
	const section = getURLSection();
	const doc = document.querySelector(
		`#field-table tbody .sort [name="peachpay_field_editor_${section}[${section}][` + $value + '][field_name]"]',
	);
	if (isDefaultField(doc.value, section) || isDefaultHeader(doc.value, section)) {
		alert('Default fields cannot be completely deleted. If you want to remove this field from the checkout, edit and uncheck Enable.');
		event.preventDefault();
		return true;
	}

	return false;
}

function deleteOptionField($value) {
	const doc = document.querySelectorAll(
		'#field-table tbody .field_' + $value,
	);
	Array.prototype.forEach.call(doc, node => {
		node.remove();
	});
	const row = document.querySelector(
		'#field-table tbody .row_' + $value,
	);
	row.classList.add('row-removed');
}

function restrictAddingDefaultField() {
	const fieldNameBox = document.querySelector('input#field_name:not([class="hide"])');
	fieldNameBox.addEventListener('change', event => {
		const modalSubmitButton = document.querySelector('button.field-button-submit.pp-button-primary');
		if (!fieldNameBox) {
			return;
		}

		if ((isDefaultField(fieldNameBox.value, 'billing') || isDefaultHeader(fieldNameBox.value, 'billing')
		|| isDefaultField(fieldNameBox.value, 'shipping') || isDefaultHeader(fieldNameBox.value, 'shipping'))) {
			alert('Please enter another field name. You have entered a reserved field name.');
			modalSubmitButton.disabled = true;
			return;
		}

		modalSubmitButton.removeAttribute('disabled');
	});
}

function selectAllCheckbox() {
	const selectAllcheckbox = document.querySelectorAll(
		'#field-table .select-all',
	);
	for (const selectAll of selectAllcheckbox) {
		selectAll.addEventListener('change', event => {
			for (const checkbox of document.querySelectorAll(
				'#field-table input[type=checkbox]',
			)) {
				if (checkbox.checked === event.target.checked) {
					continue;
				}

				checkbox.checked = event.target.checked ? true : !checkbox.checked;
			}
		});
	}
}

/**
 * This method inserts the current field data into the modal that is to be updated.
 *
 * @param rawData the field data that is to be updated in raw JSON format.
 */
function insertEditData(rawData) {
	try {
		const data = JSON.parse(rawData);

		document.querySelector('#modal-content #field_type').value = data.type_list;
		document.querySelector('#modal-content #field_name').value
			= data.field_name;
		document.querySelector('#modal-content #field_label').value
			= data.field_label;
		document.querySelector('#modal-content #field_default').value
			= data.field_default;
		document.querySelector('#modal-content #field_required').checked = Boolean(
			data.field_required,
		);
		document.querySelector('#modal-content #field_enable').checked = Boolean(
			data.field_enable,
		);
		document.querySelector('#modal-content #width').value = data.width;
		if (document.querySelector('#modal-content #field_type').value === 'header') {
			document.querySelector('#modal-content #field_default_box').classList.add('hide');
			document.querySelector('#modal-content #field_width_box').classList.add('hide');
			document.querySelector('#modal-content #input-checkboxes-required').classList.add('hide');
		}

		if (document.querySelector('#modal-content #field_type').value === 'email' || document.querySelector('#modal-content #field_type').value === 'tel'
		|| document.querySelector('#modal-content #field_type').value === 'country' || document.querySelector('#modal-content #field_type').value === 'state') {
			document.querySelector('#modal-content #field_default_box').classList.add('hide');
			if (document.querySelector('#modal-content #field_type').value === 'state') {
				document.querySelector('#field_label').value = 'State/Provice';
				document.querySelector('#modal-content #field_label').classList.add('hide');
			}
		}

		if (data.option && document.querySelector('#modal-content #field_type').value === 'select'
		|| document.querySelector('#modal-content #field_type').value === 'radio') {
			const optionListSummary = document.querySelector('#option-list-summary');
			const options = document.querySelector('#list-item');

			optionListSummary.classList.remove('hide');
			let rowNum = 0;
			for (const value in data.option) {
				addNewOptionRow(options, true, rowNum, data.option[value][0], data.option[value][1]);
				rowNum++;
			}
		}

		document
			.querySelector('#modal-content #field-info')
			.insertAdjacentHTML(
				'beforeend',
				`<input type="hidden" name="edit-row" value="${data.row}"/>`,
			);

		const section = getURLSection();

		if (isDefaultField(document.querySelector('#modal-content #field_name').value, section)
		|| isDefaultHeader(document.querySelector('#modal-content #field_name').value, section)) {
			addHideFieldBoxes(1);
			if (document.querySelector('#modal-content #field_name').value === 'billing_email') {
				document.querySelector('#modal-content #field_required').closest('div').classList.add('hide');
				document.querySelector('#modal-content #field_enable').closest('div').classList.add('hide');
			}
		} else {
			addHideFieldBoxes();
			document.querySelector('#modal-content #field_required').closest('div').classList.remove('hide');
			document.querySelector('#modal-content #field_enable').closest('div').classList.remove('hide');
		}
	} catch (error) {
		console.log(error);
	}
}

/**
 * Hides the modal and resets the form.
 * @param {object} event
 */
function hideAddFieldModal(event) {
	if (
		!event.target.id
		|| (event.target.id !== 'ppModal' && event.target.id !== 'close')
	) {
		if (event.target.id === 'pp-unsaved-warning' || event.target.id === 'pp-close-confirm-text') {
			return;
		}

		const banner = document.querySelector('#pp-unsaved-warning');
		banner.className = 'pp-unsaved-banner-hide';
		return;
	}

	if (event.target.id === 'close') {
		if (modalHasChanged(document.querySelector('#ppModal'))) {
			const banner = document.querySelector('#pp-unsaved-warning');
			if (banner.className == 'pp-unsaved-banner-hide') {
				if (!event.pp_confirmButton) {
					banner.className = 'pp-unsaved-banner-show';
					return;
				}
			}
		}
	}

	resetDefaults();

	if (document.querySelector('#modal-content #field-info input[type=hidden]')) {
		const hidden = document.querySelector(
			'#modal-content #field-info input[type=hidden]',
		);
		hidden.remove();
	}

	const options = document.querySelector('#list-item');
	const optionListSummary = document.querySelector('#option-list-summary');

	optionListSummary.classList.add('hide');

	document.querySelector('#list-summary')?.classList.remove('pp-option-summary-contents-opened');
	document.querySelector('#pp-option-list-dropdown')?.setAttribute('aria-expanded', 'false');
	document.querySelector('.pp-up-icon.pp-summary-option-icon')?.classList.add('hide');
	document.querySelector('.pp-down-icon.pp-summary-option-icon')?.classList.remove('hide');
	document.querySelector('#field_default_box.input-field').classList.remove('hide');
	document.querySelector('#field_width_box.input-field').classList.remove('hide');
	document.querySelector('#input-checkboxes-required').classList.remove('hide');
	document.querySelector('#field_label').classList.remove('hide');

	document.querySelector('#modal-content #field_required').closest('div').classList.remove('hide');
	document.querySelector('#modal-content #field_enable').closest('div').classList.remove('hide');

	addHideFieldBoxes();

	if (options.children.length > 0) {
		while (options.firstChild) {
			options.firstChild.remove();
		}
	}

	if (event.target.id === 'close') {
		const modal = document.querySelector('#ppModal');
		modal.style.display = 'none';
		return;
	}

	event.target.style.display = 'none';
}

function resetDefaults() {
	document.querySelector('#modal-content #field_type').value = 'text';
	document.querySelector('#modal-content #field_name').value = getURLSection() + '_';
	document.querySelector('#modal-content #field_label').value = '';
	document.querySelector('#modal-content #field_default').value = '';
	document.querySelector('#modal-content #field_required').checked = false;
	document.querySelector('#modal-content #field_enable').checked = false;
	document.querySelector('#modal-content #width').value = 100;
}

function addHideFieldBoxes(hide = 0) {
	if (hide) {
		document.querySelector('#modal-content #field_name').closest('div').classList.add('hide');
		document.querySelector('#modal-content #field_type').closest('div').classList.add('hide');
		document.querySelector('#modal-content #field_label').closest('div').classList.add('hide');
	} else {
		document.querySelector('#modal-content #field_name').closest('div').classList.remove('hide');
		document.querySelector('#modal-content #field_type').closest('div').classList.remove('hide');
		document.querySelector('#modal-content #field_label').closest('div').classList.remove('hide');
	}
}

function getURLSection() {
	const params = new URLSearchParams(document.location.search);
	return params.get('section');
}

function isDefaultField(name, section) {
	const defaultFieldNames = [
		section + '_email',
		section + '_phone',
		section + '_first_name',
		section + '_last_name',
		section + '_company',
		section + '_address_1',
		section + '_address_2',
		section + '_postcode',
		section + '_city',
		section + '_state',
		section + '_country',
	];
	return defaultFieldNames.includes(name);
}

function isDefaultHeader(name, section) {
	const defaultHeadersNames = [
		section + '_personal_header',
		section + '_address_header',
	];

	return defaultHeadersNames.includes(name);
}

function rememberModalChanges(mod) {
	mod.data_AllInputs = getModalInputValues(mod);
}

function getModalInputValues(mod) {
	const values = [];
	const elements = Array.from(mod.querySelectorAll('input, textarea, checkbox, select'));

	for (let i = 0; i < elements.length; i++) {
		const element = elements[i];
		if (element.type == 'checkbox') {
			values.push(element.checked);
			continue;
		}

		values.push(element.value);
	}

	return values.join(',');
}

function modalHasChanged(mod) {
	return (mod.data_AllInputs !== getModalInputValues(mod));
}

/**
 * @deprecated Until admin settings languages is overhauled.
 */
function getPluginLocaleText(text) {
	return text;
}
