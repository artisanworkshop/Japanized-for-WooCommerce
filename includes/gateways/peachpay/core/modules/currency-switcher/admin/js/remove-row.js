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
	'XPF'
]);

document.querySelectorAll('.pp-removeButton').forEach( (element) => {
	element.addEventListener('click', removeRowAndUpdate, {element});
});

document.querySelectorAll('.currencyName').forEach( (element) => {
	if (zeroDecimalCurrencies.has(element.value)){
		element.parentElement.parentElement.querySelector('.currencyDecimals').value = 0;
		element.parentElement.parentElement.querySelector('.currencyDecimals').readOnly = true;
	}
	element.addEventListener('change', checkTwoDecimal, {element});
});

document.querySelectorAll('#peachpay_new_currency_code').forEach( (element) =>{
	element.addEventListener('change', updateSupported, );
});

document.querySelectorAll('.currencyAutoUpdate').forEach( (element) => {
	if (element.checked){
		element.parentElement.parentElement.querySelector('.currencyRate').readOnly = true;
	}
	element.addEventListener('change', checkAutoUpdate, {element});
});

const $updateCurrency = document.querySelector("#updateCurrency");
if ($updateCurrency) {
	$updateCurrency.addEventListener('click', () => {
		let x = document.querySelector("#hiddenCurrencyNumber");
		let num = parseInt(x.value);
		num += 1;
		x.value = num;
		document.querySelector("#submit").click();
	});
}

function removeRowAndUpdate() {
	const row = event.target.closest('tr');
	const children = row.querySelectorAll('input,select');
	children.forEach(element => {
		element.disabled = true;

	});
	let x = document.querySelector("#hiddenCurrencyNumber");
	let num = parseInt(x.value);
	num -= 1;
	num = Math.max(0,num);
	x.value = num;
	document.querySelector("#submit").click();
}

function checkTwoDecimal() {
	if (zeroDecimalCurrencies.has(event.target.value)){
		event.target.parentElement.parentElement.querySelector('.currencyDecimals').value = 0;
		event.target.parentElement.parentElement.querySelector('.currencyDecimals').readOnly = true;
		return;
	}
	event.target.parentElement.parentElement.querySelector('.currencyDecimals').readOnly = false;
}

function checkAutoUpdate() {
	if (event.target.checked){
		event.target.parentElement.parentElement.querySelector('.currencyRate').readOnly = true;
		return;
	}
	event.target.parentElement.parentElement.querySelector('.currencyRate').readOnly = false;
}

function updateSupported(event) {
	let country = event.target.value;
	const active_providers = pp_currency_data.active_providers;
	const supports = pp_currency_data.method_supports[country];

	const difference = active_providers.filter( provider =>  !supports.includes(provider));
	
	if(difference.length !== 0){
		const tooltip = event.target.closest('td').querySelector('.pp-tooltipHidden');
		const warning = event.target.closest('td').querySelector('.pp-method-warning');
		tooltip.classList.remove('hide');
		warning.classList.remove('hide');
		tooltip.innerHTML = `Not supported by the following providers: ${difference.join(', ')}`
	} else {
		const tooltip = event.target.closest('td').querySelector('.pp-tooltipHidden');
		const warning = event.target.closest('td').querySelector('.pp-method-warning');
		tooltip.classList.add('hide');
		warning.classList.add('hide');
	}

}
