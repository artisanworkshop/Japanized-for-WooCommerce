/*****************/
// EVENT HANDLERS:

function pp_add_fee_row_event(event) {
    event.preventDefault();

    const feesCol = event.target.closest('.pp-fees-editor').querySelector('.pp-currency-fees-col');
    let feeIndex = 0;
    if (feesCol.childElementCount === 1) {
        feesCol.querySelector('.pp-fee-editor-info').classList.add('hidden');
    } else {
        try {
            const lastRow = feesCol.lastElementChild.querySelector('#pp-fee-value-input');
            feeIndex = parseInt(lastRow.getAttribute('name').match(/\[fees\]\[(\d+)\]/)[1]) + 1;
        } catch (e) {
            feeIndex = 0;
        }
    }

    const newFeeForm = event.target.closest('.pp-fees-editor').querySelector('#pp-blank-fee-editor-form #pp-fee-editor-form').cloneNode(true);
    const updateSelectors = ['#pp-fee-value-input', '#pp-fee-reason-input', '#pp-fee-percent-toggle']
    for (selector of updateSelectors) {
        const element = newFeeForm.querySelector(selector);
        element.setAttribute('name', element.getAttribute('name_temp').replace('[fees][-1]', `[fees][${feeIndex}]`));
        element.removeAttribute('name_temp');
    }
    feesCol.append(newFeeForm);

    pp_add_fee_row_btn_listeners();
}

function pp_fee_type_selected_event(event) {
    event.preventDefault();

    if (event.target.getAttribute('active') !== null) {
        return;
    }

    const ppFeeTypeSwitch = event.target.closest('.pp-fee-type-switch');
    const ppHiddenTypeValue = ppFeeTypeSwitch.querySelector('#pp-fee-percent-toggle')
    event.target.setAttribute('active', '');

    if (event.target.value !== '%') {
        if ( ppHiddenTypeValue.getAttribute('checked') !== null ) {
            ppHiddenTypeValue.removeAttribute('checked');
        }

        ppFeeTypeSwitch.querySelectorAll('#pp-fee-type-button').forEach((element) => {
            if (element.value === '%') {
                element.removeAttribute('active');
            }
        });

        return;
    }

    ppHiddenTypeValue.setAttribute('checked', '');
    ppFeeTypeSwitch.querySelectorAll('#pp-fee-type-button').forEach((element) => {
        if (element.value !== '%') {
            element.removeAttribute('active');
        }
    });
}

/**
 * Handles reason input field changing
 */
function pp_fee_reason_change_event(event) {
    if (event.target.classList.contains('pp-fee-reason-no-input')) {
        if (event.target.value !== '') {
            event.target.classList.remove('pp-fee-reason-no-input');
        }
        return;
    }

    if (event.target.value === '') {
        event.target.classList.add('pp-fee-reason-no-input');
    }
}

/**
 * Handles the remove row button being pressed.
 */
function pp_fee_row_remove_btn_click(event) {
    event.preventDefault();
    const feesDiv = event.target.closest('.pp-currency-fees-col');

    if (event.target.closest('#pp-fee-editor-form')) {
        event.target.closest('#pp-fee-editor-form').remove();
    }

    if (feesDiv && feesDiv.childElementCount === 1) {
        feesDiv.querySelector('.pp-fee-editor-info').classList.remove('hidden');
    }
}

function pp_add_fee_row_btn_listeners() {
	document.querySelectorAll('.pp-fee-remove-btn').forEach((element) => {
		element.addEventListener('click', pp_fee_row_remove_btn_click);
	});

    document.querySelectorAll('.pp-add-new-fee-btn').forEach((element) => {
        element.addEventListener('click', pp_add_fee_row_event);
    })

    document.querySelectorAll('#pp-fee-type-button').forEach((element) => {
        element.addEventListener('click', pp_fee_type_selected_event);
    })

    document.querySelectorAll('#pp-fee-reason-input').forEach((element) => {
        element.addEventListener('change', pp_fee_reason_change_event);
    })
}

window.addEventListener('load', () => {
    document.querySelectorAll('#submit').forEach((submitBtn)=> {
        submitBtn.addEventListener('click', (event) => {
            document.querySelectorAll('#pp-fee-value-input').forEach((element) => {
                if (element.value === '') {
                    element.value = '0';
                }
            })
        })
    })

    pp_add_fee_row_btn_listeners();
});