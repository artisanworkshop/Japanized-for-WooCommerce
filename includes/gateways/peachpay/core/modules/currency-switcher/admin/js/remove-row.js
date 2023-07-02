const zeroDecimalCurrencies = new Set([
	'BIF',
	'CLP',
	'DJF',
	'GNF',
	'JPY',
	'KMF',
	'KRW',
	'MGA',
	'PYG',
	'RWF',
	'UGX',
	'VND',
	'VUV',
	'XAF',
	'XOF',
	'XPF',
	'TWD',
	'HUF'
]);

window.addEventListener('load', ()=>{
	document.querySelector('#pp-currency-table-div')?.classList.remove('pp-load-currency');
});

window.addEventListener('load', (event)=>{
	document.querySelectorAll('.rate')?.forEach( ($el) => {
		$el.addEventListener('click', (event) => {
			if(event.target.readOnly) {
				event.target.parentElement.querySelector('.pp-popup-above').style.visibility='visible';
				setTimeout(($el) => {
					$el.style.visibility='hidden';
				}, 4000, event.target.parentElement.querySelector('.pp-popup-above'));
			}
		})
	});
})

window.addEventListener('load', (event)=>{
	document.querySelectorAll('#peachpay_new_currency_code')?.forEach( ($el) => {
		$el.addEventListener('change', (event) => {
			const row = event.target.closest('tr');
			const $el = row.querySelector('.pp-rate-change-warning')
			const $rate = row.querySelector('.rate');
			if ( $rate.readOnly) {
				$el.style.visibility='visible';
				setTimeout(($el) => $el.style.visibility='hidden', 4000, $el);
			}
		})
	});
})

document.querySelector('#pp-currency-reset')?.addEventListener('click', ()=> {
	const flag = document.querySelector('#pp_currency_flag');
	const confirm = window.confirm( getPluginLocaleText("Are you sure you want to clear all currencies?"));
	if ( ! confirm ) {
		return;
	}
	flag.value='remove';
	flag.disabled = false;
	document.querySelector("#submit").click();
})

document.querySelector('#pp-enable-every-currency')?.addEventListener('click', () => {
	const flag = document.querySelector('#pp_currency_flag');
	const confirm = window.confirm( getPluginLocaleText("Are you sure you want to add all supported currencies? This will replace the current table."));
	if ( ! confirm ) {
		return;
	}
	flag.value='all';
	flag.disabled = false;
	document.querySelector("#submit")?.click();
});

// Event listener to show the custom interval setting.
document.querySelector('#enable_peachpay_currency_custom_intervals')?.addEventListener('change', hideCustomIntervals);

// Remove row event.
document.querySelectorAll('.pp-removeButton')?.forEach( (element) => {
	element.addEventListener('click', removeRowAndUpdate, {element});
});

// Event listener for zero decimal currencies setup.
document.querySelectorAll('.name')?.forEach( (element) => {
	if (zeroDecimalCurrencies.has(element.value)){
		const decimals = element.parentElement.parentElement.parentElement.querySelector('.decimals');
		decimals.value = 0;
		decimals.readOnly = true;
	}
	element.addEventListener('change', checkTwoDecimal, {element});
});

// Change the list of supported payment methods through JS.
document.querySelectorAll('#peachpay_new_currency_code')?.forEach( (element) =>{
	element.addEventListener('change', updateSupported, );
});

// Change rate to read only if auto-update enabled.
document.querySelectorAll('.auto_update')?.forEach( (element) => {
	if (element.checked){
		element.parentElement.parentElement.querySelector('.rate').readOnly = true;
	}
	element.addEventListener('change', checkAutoUpdate, {element});
});

// Add a new currency instead of using a number use an update flag now so number can't get corrupted or lost.
const $updateCurrency = document.querySelector("#pp-updateCurrency");
if ($updateCurrency) {
	$updateCurrency.addEventListener('click', () => {
		let x = document.querySelector("#hiddenAddFlag");
		x.disabled=false;
		document.querySelector("#submit").click();
	});
}

function removeRowAndUpdate() {
	const row = event.target.closest('tr');
	const children = row.querySelectorAll('input,select');
	children.forEach(element => {
		element.disabled = true;

	});
	document.querySelector("#submit").click();
}

function checkTwoDecimal() {
	const decimals = event.target.parentElement.parentElement.parentElement.querySelector('.decimals');
	if (zeroDecimalCurrencies.has(event.target.value)){
		decimals.value = 0;
		decimals.readOnly = true;
		return;
	}
	if( decimals.readOnly) {
		decimals.readOnly = false;
	}
}

function checkAutoUpdate() {
	if (event.target.checked){
		event.target.parentElement.parentElement.querySelector('.rate').readOnly = true;
		return;
	}
	event.target.parentElement.parentElement.querySelector('.rate').readOnly = false;
}

function updateSupported(event) {
	let country = event.target.value;
	const active_providers = pp_currency_data.active_providers;
	const supports = pp_currency_data.method_supports[country];

	const difference = active_providers.filter( provider =>  !supports.includes(provider));

	if(difference.length !== 0){
		const tooltip = event.target.closest('td').querySelector('.pp-currency-warning-table');
		const warning = event.target.closest('td').querySelector('.pp-method-warning');
		tooltip.classList.remove('hide');
		warning.classList.remove('hide');
		tooltip.innerHTML = getPluginLocaleText(`Not supported by the following providers: ${difference.join(', ')}`)
	} else {
		const tooltip = event.target.closest('td').querySelector('.pp-currency-warning-table');
		const warning = event.target.closest('td').querySelector('.pp-method-warning');
		tooltip.classList.add('hide');
		warning.classList.add('hide');
	}

}

function hideCustomIntervals() {
	if(event.target.checked) {
		document.querySelectorAll('.custom_interval_selector').forEach((element) =>{
			element.classList.remove('hide');
		});
	} else {
		document.querySelectorAll('.custom_interval_selector').forEach((element) =>{
			element.classList.add('hide');
		});
	}
}

document.querySelector('#submit')?.addEventListener('click', (event) => {
	let countries = document.querySelectorAll('.countries').forEach((element)=>{
		const td = element.closest('td');
		const selected = td.querySelector('.currencyCountries').selectedOptions;
		for(let i = 0; i < selected.length; i++) {
			if(!element.value.includes(selected[i].value)) {
				element.value += ',' + selected[i].value;
			}
		}
	})
});

/**
 * @deprecated Until admin settings languages is overhauled.
 */
function getPluginLocaleText(text) {
	return text;
}
