/**
 * Basic util class to use for all analytics pages.
 */
// eslint-disable-next-line no-unused-vars
class PeachPayAnalyticsUtil {
	constructor(imports = {}) {
		this.noDataStyle = `
			border-radius: 6px;
		
			padding: 12px;
			position: relative;
		
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
		`;

		this.charts = [];
		this.chartColors = {}; // Name -> color to streamline merchant experience.
		this.chartBuild = {// Attaches types to functions
			count: this.pieChart,
			interval: this.intervalChart,
		};

		this.totals = [];

		this.currencyElements = [];

		this.currentParams = {};
		this.paramListenerCallbacks = [];

		if (imports.dateFormat) {
			this.dateFormat = imports.dateFormat;
			this.dateFormatElements = {};
		}

		this.enableModalListener();

		this.listeners = {
			params: cb => {
				this.paramListenerCallbacks.push(cb);
				cb(this.currentParams);
			},
			currency: el => {
				this.updateCurrencySelectionList(el);
				this.currencyElements.push(el);
			},
			chart: item => {
				const chartSection = item.query.section.split('_');
				item.type = chartSection[chartSection.length - 1];
				item.chartReference = this.chartBuild[item.type](this, item.data, item.chart_id);

				this.charts.push({
					type: item.type,
					query: item.query,
					chartID: item.chart_id,
					chartReference: item.chartReference,
				});
				return {
					type: item.type,
					query: item.query,
					chartID: item.chart_id,
					chartReference: item.chartReference,
				};
			},
			total: item => {
				this.charts.push({
					type: null,
					query: item.query,
					chartID: item.chart_id,
				});
			},
			dateFormat: chart => {
				this.dateFormatElements[chart.chartID] = 1;
			},
		};

		const urlParams = new URLSearchParams(window.location.search);

		urlParams.forEach((param, paramKey) => {
			if (paramKey === 'page' || paramKey === 'tab' || paramKey === 'section') {
				return;
			}

			this.currentParams[paramKey] = param;
		});

		// Either 1 or 0 depending on activeness
		/**
		 * TO REITERATE:
		 * - this.currencies updates on every page click in the currency switcher popup.
		 * - this.updatedCurrencies will only be updated after the update button is clicked.
		 */
		this.currencies = {};
		this.updatedCurrencies = {}; // Only updates after the update button is pressed
		const currencyParam = urlParams.get('currency');
		if (currencyParam && currencyParam.length) {
			const currencyParams = currencyParam.split('.');
			currencyParams.forEach(param => {
				this.currencies[param] = 1;
				this.updatedCurrencies[param] = 1;
			});
		}
	}

	/**
	 * Takes in the id of a given notification modal on the page and sets up listeners for it. Expects the form:
	 *  <div id='pp-analytics-modal' class='pp-analytics-modal'>
			<div class='pp-analytics-modal-content'>
				<div class='pp-analytics-modal-title'>
					<h1 class='pp-analytics-modal-h1'></h1>
					<span id='pp-analytics-modal-close' class='pp-analytics-modal-close'>&times;</span>
				</div>
				<div class='pp-analytics-modal-message'></div>
			</div>
		</div>
	 * Note: only the following are required:
	 * - modal id: pp-analytics-modal
	 * - modal content: pp-analytics-modal-content
	 * - modal title: pp-analytics-modal-h1
	 * - modal message: pp-analytics-modal-message
	*/
	enableModalListener() {
		this.modal = document.getElementById('pp-analytics-modal');
		this.modal.style.display = 'none';

		const utils = this;

		this.modal.querySelector('.pp-analytics-modal-content').addEventListener('click', e => {
			e.stopPropagation();
		});
		this.modal.querySelector('#pp-analytics-modal-close').addEventListener('click', () => {
			utils.modal.style.display = 'none';
			utils.resetCurrencies();
		});
		this.modal.addEventListener('click', () => {
			utils.modal.style.display = 'none';
			utils.resetCurrencies();
		});
	}

	/**
	 * Sends an error to the user using the setup from above (assumes the above has been run)
	 */
	updateModal(title, message) {
		this.modal.querySelector('.pp-analytics-modal-h1').innerHTML = title;
		this.modal.querySelector('.pp-analytics-modal-message').innerHTML = message;
		this.modal.style.display = 'flex';
	}

	analyticsDefinitionModal() {
		this.updateModal('What do our analytics mean?', `
			<style>h4 { margin-bottom: 8px; }</style>
			<h4>Welcome to the analytics page!</h4>
			<p>Here you will find numbers and statistics for all things your store. Curious what devices and browsers people use most,
				perhaps which payment methods are at the top, or understanding people who abandon carts? We've got you covered.</p>				
			<p>Each of the analytics tabs (Payment methods, Device breakdown, and so on) breaks into subsections. For instance,
				on Device breakdown you have both browser (i.e. Google Chrome and Safari) breakdown and operating system (i.e.
				Mac OS and Windows) breakdown. Click between these subsections to see all the analytics related to each tab.</p>
			<h4>Currencies</h4>
			<p>Some of the analytics tabs have the option for displaying specific currencies. This by default will select
				the stores base currency and display all data related to that currency. However, if applicable the <i>currencies selected</i>
				option will display in the top right for picking specific currencies. With this, you can view different currencies overlaid
				on top of each other to better understand customer preferences. <i>Please note that only graphs with the currency codes displayed
				are affected by currency switches.</i></p>
			<h4>Interval charts</h4>
			<p>Interval charts have a number of options attached to them so that you can either look closely at specific data
				or zoom out to investigate long-term patterns. This breaks into the following options:</p>
			<ol>
				<li>Display type: Choose how you see the data; choose bar charts to just look at the numbers, or choose
					line charts to look more closely at patterns over time.</li>
				<li>Interval: Update the interval to augment how much data each chart displays. By choosing a larger interval such as
					monthly, this will average all of the data for each month to display data in a more digestible way.</li>
				<li>Time span: Time span allows you to zoom in and out on your store's history. Zooming in, you can find day to day numbers
					and the now data. Zooming out, you can view long-term numbers.</li>
			</ol>
		`);
	}

	/**
	 * Attaches a chart element to the given listener so each element updates automatically
	 *
	 * @param {*} item the given input (see below)
	 * @param {*} listener The type of listener. Currently:
	 *  - currency:
	 *    - @var {Element} item:
	 *  - chart:
	 *    - @var {*} query    - the specific query that is used for this chart (comes from PHP)
	 *      - Note: chart pie versus chart line is inferred from query section
	 *    - @var {*} data     - the data that comes with this chart (used for first time update)
	 *    - @var {string} chartID - the HTML identification that represents this chart
	 *    - @var {*} options  - random options that may be need:
	 *       - bar/line charts:
	 *         - type: bar/line
	 *         - isStacked: for bar charts
	 */
	subscribe(listener, item) {
		return this.listeners[listener](item);
	}

	loading(chart) {
		const chartRef = document.querySelector('#' + chart.chartID);
		chartRef.parentElement.querySelector('.pp-analytics-loader').classList.add('loading');
	}

	loaded(chart) {
		const chartRef = document.querySelector('#' + chart.chartID);
		chartRef.parentElement.querySelector('.pp-analytics-loader').classList.add('loading');
	}

	resetCurrencies() {
		// Update info
		// Update active currency display
		if (!document.getElementById('pp-analytics-currency')) {
			return;
		}

		const activeCurrencies = document.getElementById('pp-analytics-currency').querySelectorAll('a');
		activeCurrencies[0].classList.remove('active');
		const startPosition = (activeCurrencies.length > 1 ? 1 : 0);
		for (let activeCurrenciesIndex = startPosition; activeCurrenciesIndex < activeCurrencies.length - startPosition; activeCurrenciesIndex++) {
			activeCurrencies[activeCurrenciesIndex].remove();
		}

		if (activeCurrencies.length > 1) {
			document.getElementById('pp-analytics-currency').querySelector('a:nth-of-type(2)').classList.remove('active');
		}

		const currentCurrencies = Object.keys(this.updatedCurrencies);
		const currencies = currentCurrencies.length ? [] : Object.keys(this.currencies);
		currentCurrencies.forEach(currency => {
			if (this.updatedCurrencies[currency]) {
				currencies.push(currency);
			}
		});

		if (this.updatedCurrencies['*']) {
			activeCurrencies[0].classList.add('active');
		} else {
			const selected = document.createElement('a');
			selected.innerHTML = currencies.length === 1
				? currencies[0] : currencies.length;
			selected.classList.add('active');
			if (activeCurrencies.length > 1) {
				activeCurrencies[0].after(selected);
			} else {
				document.getElementById('pp-analytics-currency').querySelector('.pp-analytics-menu').append(selected);
			}
		}

		return currencies;
	}

	async updateData(query) {
		const formData = new FormData();
		formData.append('action', 'pp-analytics-query');
		formData.append('query', JSON.stringify(query));

		// eslint-disable-next-line camelcase, no-undef
		const response = await fetch(peachpay_admin.admin_ajax, {
			method: 'POST',
			body: formData,
		});

		return response.json();
	}

	updateDateFormat(chart) {
		if (this.dateFormatElements[chart.chartID]) {
			chart.query.format = this.dateFormat[chart.query.time_span + '.' + chart.query.interval];
		}
	}

	getChartLoader(chartRef) {
		// Find closes analytics loader
		const analyticsLoader = chartRef.querySelector('.pp-analytics-loader')
			? chartRef.querySelector('.pp-analytics-loader')
			: chartRef.parentElement.querySelector('.pp-analytics-loader')
				? chartRef.parentElement.querySelector('.pp-analytics-loader')
				: chartRef.parentElement.parentElement.querySelector('.pp-analytics-loader')
					? chartRef.parentElement.parentElement.querySelector('.pp-analytics-loader')
					: chartRef.parentElement.parentElement.parentElement.querySelector('.pp-analytics-loader');

		const parentWidth = analyticsLoader.parentElement.offsetWidth;
		const parentHeight = analyticsLoader.parentElement.offsetHeight;
		analyticsLoader.style.width = parentWidth + 'px';
		analyticsLoader.style.height = parentHeight + 'px';

		const parentPaddingLeft = window.getComputedStyle(analyticsLoader.parentElement, null).getPropertyValue('padding-left');
		const parentPaddingTop = window.getComputedStyle(analyticsLoader.parentElement, null).getPropertyValue('padding-top');
		const parentBorderRadius = window.getComputedStyle(analyticsLoader.parentElement, null).getPropertyValue('border-radius');
		analyticsLoader.style['margin-left'] = 'calc(-1 * ' + parentPaddingLeft + ')';
		analyticsLoader.style['margin-top'] = 'calc(-1 * ' + parentPaddingTop + ')';
		analyticsLoader.style['border-radius'] = parentBorderRadius;

		return analyticsLoader;
	}

	async updateCharts() {
		const params = new URLSearchParams(window.location.search);
		const currentParams = {};

		const updateChartPromise = this.charts.map(async chart => {
			let chartHasChange = 0;
			for (const key in chart.query) {
				if (key === 'tab' || key === 'section') {
					continue;
				}

				if (key === 'currency') {
					chart.query[key] = chart.query[key].replaceAll(',', '.');
				}

				if (params.get(key)) {
					currentParams[key] = params.get(key);
				}

				const prevChangeQueryValue = chart.query[key];
				chart.query[key] = params.get(key) ? params.get(key) : chart.query[key];
				chartHasChange = chartHasChange || prevChangeQueryValue !== chart.query[key];
				if (key === 'currency') {
					chart.query[key] = chart.query[key].replaceAll('.', ',');
				}
			}

			if (!chartHasChange) {
				const intervalType = params.get('interval_type');
				if (intervalType && intervalType.length && chart.type === 'interval') {
					const chartRef = document.querySelector('#' + chart.chartID);

					if (chart.chartReference.config.type === 'bar'
						&& chart.chartReference.config.type === (intervalType === 'line' ? 'line' : 'bar')) {
						chart.chartReference.config.options.scales.x.stacked = intervalType === 'stacked';
						chart.chartReference.config.options.scales.y.stacked = intervalType === 'stacked';
						chart.chartReference.chart.update();

						return;
					}

					const newConfig = JSON.parse(JSON.stringify(chart.chartReference.config));
					newConfig.type = intervalType === 'line' ? 'line' : 'bar';
					newConfig.options.scales.x.stacked = intervalType === 'stacked';
					newConfig.options.scales.y.stacked = intervalType === 'stacked';

					chart.chartReference.config = newConfig;
					chart.chartReference.chart.destroy();
					// eslint-disable-next-line no-undef
					chart.chartReference.chart = new Chart(chartRef, newConfig);
				}

				return;
			}

			this.updateDateFormat(chart);

			const chartRef = document.querySelector('#' + chart.chartID);
			const analyticsLoader = this.getChartLoader(chartRef);
			analyticsLoader.classList.add('loading');

			const data = await this.updateData(chart.query);
			if (chart.chartReference) {
				chart.chartReference.chart.destroy();
			}

			analyticsLoader.classList.remove('loading');
			if (chart.type) {
				chart.chartReference = this.chartBuild[chart.type](this, data, chart.chartID);
			} else {
				chartRef.querySelector('.pp-analytics-statistic').innerHTML = data;
			}
		});

		this.paramListenerCallbacks.forEach(cb => {
			cb(currentParams);
		});
		await Promise.all(updateChartPromise);
	}

	/**
	 * Updates a specific currency status in the currency selection modal.
	 *
	 * @param {Element} activeCurrencySelection see below.
	 * @param {string} itemToChange
	 * @param {boolean} isAdd
	 */
	updateOneCurrencySelection(activeCurrencySelection, itemToChange, isAdd) {
		if (itemToChange) {
			this.currencies[itemToChange] = isAdd;
			const currencyEntries = Object.entries(this.currencies);
			this.currencies['*'] = currencyEntries.reduce((toggledOnCurrencyCount, currency) => toggledOnCurrencyCount + currency[1], 1) >= currencyEntries.length;

			const currencyToChange = activeCurrencySelection.querySelector(`a[data-currency="c-${itemToChange}"]`);

			if (isAdd) {
				const extraCurrencies = activeCurrencySelection.querySelector('#extra-currencies');
				if (extraCurrencies) {
					extraCurrencies.innerHTML = '+' + (parseInt(extraCurrencies.innerHTML.split('+')[1], 10) + 1);
				} else if (activeCurrencySelection.querySelectorAll('a[data-currency^="c-"]').length > 2) {
					activeCurrencySelection.innerHTML += '<a id="extra-currencies">+1</a>';
				} else {
					activeCurrencySelection.innerHTML += `<a data-currency="c-${itemToChange}">${itemToChange}</a>`;
				}

				if (activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency')) {
					activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency').classList.remove('active');
				}

				return;
			}

			const extraCurrencies = activeCurrencySelection.querySelector('#extra-currencies');
			if (currencyToChange) {
				currencyToChange.remove();
			} else if (extraCurrencies) {
				const extraCurrenciesValue = parseInt(extraCurrencies.innerHTML.split('+')[1], 10);
				if (extraCurrenciesValue > 1) {
					extraCurrencies.innerHTML = '+' + (extraCurrenciesValue - 1);
				} else {
					extraCurrencies.remove();
				}
			}

			this.currencies['*'] = false;
			// Confirm number of active currencies.
			if (!activeCurrencySelection.querySelectorAll('a[data-currency^="c-"]').length && activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency')) {
				activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency').classList.add('active');
			}
		}
	}

	/**
	 * Updates the utils currency list and the display on the frontend
	 *
	 * @param {Element} activeCurrencySelection the given item that we're changing (for use across graphs as well)
	 * @param {string} itemToChange
	 * @param {boolean} isAdd
	 */
	updateCurrencySelectionList(activeCurrencySelection, itemToChange, isAdd) {
		this.updateOneCurrencySelection(activeCurrencySelection, itemToChange, isAdd);
		if (itemToChange && isAdd) {
			return;
		}

		const allActive = this.currencies['*'];
		if (allActive) {
			Object.keys(this.currencies).forEach(currency => {
				this.currencies[currency] = 1;
			});
		}

		const currenciesToUpdate = Object.keys(this.currencies);
		const activeCurrencySelectionList = activeCurrencySelection.querySelectorAll('a');
		Array.prototype.forEach.call(activeCurrencySelectionList, node => {
			node.remove();
		});
		let overMaxCurrencyOptionsIndex = 0;
		let activeCurrencyOptionsIndex = 0;
		for (let currencyOptionsIndex = 0; currencyOptionsIndex < currenciesToUpdate.length; currencyOptionsIndex++) {
			if ((!allActive && !this.currencies[currenciesToUpdate[currencyOptionsIndex]]) || currenciesToUpdate[currencyOptionsIndex] === '*') {
				continue;
			}

			if (activeCurrencyOptionsIndex >= 3) {
				overMaxCurrencyOptionsIndex++;
				continue;
			}

			activeCurrencyOptionsIndex++;
			activeCurrencySelection.innerHTML += `
				<a data-currency="c-${currenciesToUpdate[currencyOptionsIndex]}">${currenciesToUpdate[currencyOptionsIndex]}</a>
			`;
		}

		if (overMaxCurrencyOptionsIndex) {
			activeCurrencySelection.innerHTML += `
				<a id="extra-currencies">+${overMaxCurrencyOptionsIndex}</a>
			`;
		}

		if (!activeCurrencySelection.querySelectorAll('a[data-currency^="c-"]').length && activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency')) {
			activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency').classList.add('active');
		} else if (activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency')) {
			activeCurrencySelection.querySelector('#pp-analytics-currency-update-no-currency').classList.remove('active');
		}
	}

	currencySwitcherModal(currencies) {
		let paginationMenu = currencies.length > 12 ? '<a page="1" class="active">1</a>' : '';
		let activeCurrencySelectionList = '<div class="pp-analytics-currency-selected">Currently selected:';
		let overCurrencyCap = 0;
		let currencyOptionsList = '<div id="pp-analytics-currency-options-container1" class="pp-analytics-currency-options active">';
		let pageNumber = 1;
		for (let currencyOptionsIndex = 0; currencyOptionsIndex < currencies.length; currencyOptionsIndex++) {
			if (currencies[currencyOptionsIndex] === '*') {
				continue;
			}

			const currencyKey = 'currency' + (currencyOptionsIndex + 1);
			if ((this.currencies['*'] || this.currencies[currencies[currencyOptionsIndex]]) && (activeCurrencySelectionList.match(/<\/a>/g) || []).length < 3) {
				activeCurrencySelectionList += `<a data-currency="c-${currencies[currencyOptionsIndex]}">` + currencies[currencyOptionsIndex] + '</a>';
			} else if (this.currencies['*'] || this.currencies[currencies[currencyOptionsIndex]]) {
				overCurrencyCap++;
			}

			currencyOptionsList += `
				<div class='pp-analytics-currency-option'>
					<input type='checkbox' name='${currencyKey}' value='${currencies[currencyOptionsIndex]}'
						${this.currencies['*'] || this.currencies[currencies[currencyOptionsIndex]] ? 'checked' : ''}>
					<label for='${currencyKey}'>${currencies[currencyOptionsIndex]}</label>
				</div>`;

			if (currencyOptionsIndex % 11 === 0 && currencyOptionsIndex && currencyOptionsIndex < currencies.length - 1) {
				pageNumber++;

				paginationMenu += `<a page='${pageNumber}'>${pageNumber}</a>`;
				currencyOptionsList += `</div><div id='pp-analytics-currency-options-container${pageNumber}'  class='pp-analytics-currency-options'>`;
			}
		}

		paginationMenu += currencies.length > 12 ? '</div>' : '';
		activeCurrencySelectionList += (overCurrencyCap ? '<a id="extra-currencies">+' + overCurrencyCap + '</a>' : '')
			+ `<div id="pp-analytics-currency-update-no-currency" class="${(activeCurrencySelectionList.match(/<\/a>/g) || []).length ? '' : 'active'}">No currency selected!</div></div>`;
		currencyOptionsList += '</div>';

		return `
			<input class='pp-analytics-currency-search' type='text' placeholder='Find currency'>
			${activeCurrencySelectionList}
			${currencyOptionsList}
			<div class='pp-analytics-menu pp-analytics-currency-selectors'>
				<a id='pp-analytics-currency-clear'>Clear all</a>
				<a id='pp-analytics-currency-select-all'>Select all</a>
			</div>
			<div class='pp-analytics-button' id='pp-analytics-currency-update'>Update</div>
			${paginationMenu.length ? '<div class="pp-analytics-currency-page">' + paginationMenu + '</div>' : ''}
		`;
	}

	addOptionMenuTimeSpan(utils, analyticsElement, urlParams) {
		analyticsElement.innerHTML += `
		<div id='pp-analytics-time-span' class='pp-analytics-options'>
			<select class='pp-analytics-select' search='time_span'>
				<option value='week'>Past week</option>
				<option value='month'>Past month</option>
				<option value='year'>Past year</option>
				<option value='5year'>Past 5 years</option>
				<option value='all'>All</option>
			</select>
		</div>`;

		const timespanTypeParam = urlParams.get('time_span');
		analyticsElement.querySelector('#pp-analytics-time-span .pp-analytics-select').value = timespanTypeParam ? decodeURIComponent(timespanTypeParam) : 'year';
	}

	addOptionMenuInterval(utils, analyticsElement, urlParams) {
		analyticsElement.innerHTML += `
		<div id='pp-analytics-interval' class='pp-analytics-options'>
			<select class='pp-analytics-select' search='interval'>
				<option value='daily'>Daily</option>
				<option value='weekly'>Weekly</option>
				<option value='monthly'>Monthly</option>
				<option value='yearly'>Yearly</option>
			</select>
		</div>`;

		const intervalTypeParam = urlParams.get('interval');
		analyticsElement.querySelector('#pp-analytics-interval .pp-analytics-select').value = intervalTypeParam ? decodeURIComponent(intervalTypeParam) : 'monthly';
	}

	addOptionMenuIntervalType(utils, analyticsElement, urlParams) {
		analyticsElement.innerHTML += `
		<div id='pp-analytics-chart-type' class='pp-analytics-options'>
			<div class='pp-analytics-menu'>
				<a title='Stacked' search='interval_type=stacked'><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M21 21V4.5H15V21H12V10.5H6V21H3V1.5H1.5V21C1.5 21.3978 1.65804 21.7794 1.93934 22.0607C2.22064 22.342 2.60218 22.5 3 22.5H22.5V21H21ZM16.5 6H19.5V13.5H16.5V6ZM7.5 12H10.5V16.5H7.5V12Z" fill="black"/>
				</svg></a>
				<a title='Line' search='interval_type=line'><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M3.5025 21L8.295 12L13.77 16.8675C13.9414 17.0198 14.1455 17.1308 14.3665 17.1919C14.5876 17.253 14.8197 17.2626 15.045 17.22C15.2724 17.1774 15.4868 17.0828 15.6716 16.9436C15.8563 16.8044 16.0064 16.6243 16.11 16.4175L20.25 8.175L18.885 7.5L14.7675 15.75L9.2925 10.8825C9.12541 10.7271 8.92506 10.6119 8.70672 10.5456C8.48837 10.4793 8.25778 10.4637 8.0325 10.5C7.81002 10.5369 7.59873 10.6235 7.41434 10.7534C7.22995 10.8832 7.07722 11.053 6.9675 11.25L3 18.75V1.5H1.5V21C1.5 21.3978 1.65804 21.7794 1.93934 22.0607C2.22064 22.342 2.60218 22.5 3 22.5H22.5V21H3.5025Z" fill="black"/>
				</svg></a>
				<a title='Side by side' search='interval_type=side'><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M22.5 22.5H3C2.60218 22.5 2.22064 22.342 1.93934 22.0607C1.65804 21.7794 1.5 21.3978 1.5 21V1.5H3V21H22.5V22.5Z" fill="black"/>
				<path d="M7.5 12H9V19.5H7.5V12ZM5.25 16.5H6.75V19.5H5.25V16.5ZM19.5 6H21V19.5H19.5V6ZM17.25 10.5H18.75V19.5H17.25V10.5ZM12.75 19.5H11.25V9H12.75V19.5ZM15 19.5H13.5V13.5H15V19.5Z" fill="black"/>
				</svg></a>
			</div>
		</div>`;

		const intervalTypeParam = urlParams.get('interval_type');
		analyticsElement.querySelector('#pp-analytics-chart-type a[search=\'interval_type='
			+ (intervalTypeParam ? decodeURIComponent(intervalTypeParam) : 'stacked') + '\']').classList.add('active');
		const svgGroup = analyticsElement.querySelectorAll('#pp-analytics-chart-type a[search=\'interval_type='
			+ (intervalTypeParam ? decodeURIComponent(intervalTypeParam) : 'stacked') + '\'] path');
		Array.prototype.forEach.call(svgGroup, svg => {
			svg.setAttribute('fill', '#ffffff');
		});
	}

	addOptionMenuCurrency(utils, analyticsElement, urlParams, options) {
		options.activeCurrencies = options.activeCurrencies && options.activeCurrencies.length ? options.activeCurrencies.split(',') : [];
		let currenciesLength = options.currencies.length;
		if (!currenciesLength) {
			options.currencies = options.activeCurrencies;
		}

		currenciesLength = options.currencies.length;

		options.currencies.forEach(currency => {
			utils.currencies[currency] = 0;
		});
		options.activeCurrencies.forEach(currency => {
			utils.currencies[currency] = 1;
		});
		utils.updatedCurrencies = JSON.parse(JSON.stringify(utils.currencies));

		if (!currenciesLength) {
			return utils.updateModal('Missing currency', 'There doesn\'t seem to be any active currency. Please check the PeachPay currency tab to confirm settings.');
		}

		analyticsElement.innerHTML += `
		<div id='pp-analytics-currency' class='pp-analytics-options'>
			<div class='pp-analytics-menu'>
				<h2>Currencies selected:</h2>
				${currenciesLength > 1 ? '<a id="pp-analytics-currency-all">All</a>' : ''}
				${options.activeCurrencies && options.activeCurrencies[0] === '*' ? ''
		: `<a>${options.activeCurrencies.length === 1 ? options.activeCurrencies[0] : options.activeCurrencies.length}</a>`}
				${currenciesLength > 1 ? '<a id="pp-analytics-currency-switch" style="font-size:18px">+</a>' : ''}
			</div>
		</div>`;

		const currencyParam = urlParams.get('currency');
		if (utils.currencies['*'] || currencyParam === '*') {
			analyticsElement.querySelector('a:nth-of-type(1)').classList.add('active');

			options.currencies.forEach(currency => {
				utils.updatedCurrencies[currency] = 1;
			});
			utils.updatedCurrencies['*'] = 1;
			utils.currencies = JSON.parse(JSON.stringify(utils.updatedCurrencies));
		} else if (currenciesLength <= 1) {
			analyticsElement.querySelector('a:nth-of-type(1)').classList.add('active');
		} else {
			analyticsElement.querySelector('a:nth-of-type(2)').classList.add('active');
		}

		if (document.querySelector('#pp-analytics-currency-all')) {
			document.querySelector('#pp-analytics-currency-all').addEventListener('click', function () {
				if (this.parentNode.querySelectorAll('a').length === 3) {
					this.parentNode.querySelector('a:nth-of-type(2)').remove();
				}

				this.classList.add('active');

				options.currencies.forEach(currency => {
					utils.updatedCurrencies[currency] = 1;
				});
				utils.updatedCurrencies['*'] = 1;
				utils.currencies = JSON.parse(JSON.stringify(utils.updatedCurrencies));

				utils.currencyElements.forEach(currency => utils.updateCurrencySelectionList(currency));

				const locationHref = window.location.href.split('?')[0];
				const params = new URLSearchParams(window.location.search);
				params.set('currency', '*');
				window.history.pushState({title: 'Analytics Dashboard'}, '', locationHref + '?' + params.toString());
				utils.updateCharts();
			});
		}

		if (document.querySelector('#pp-analytics-currency-switch')) {
			document.querySelector('#pp-analytics-currency-switch').addEventListener('click', function () {
				Array.prototype.forEach.call(this.parentNode.querySelectorAll('a.active'), node => {
					node.classList.remove('active');
				});
				this.classList.add('active');

				utils.updateModal('Switch currencies', utils.currencySwitcherModal(options.currencies));

				const currencyOptions = document.getElementById('pp-analytics-modal').querySelectorAll('.pp-analytics-currency-option');
				for (let currencyOptionsIndex = 0; currencyOptionsIndex < currencyOptions.length; currencyOptionsIndex++) {
					currencyOptions[currencyOptionsIndex].addEventListener('click', function () {
						this.querySelector('input').click();
					});
					currencyOptions[currencyOptionsIndex].querySelector('input').addEventListener('click', e => {
						e.stopPropagation();
						utils.updateCurrencySelectionList(document.querySelector('#pp-analytics-modal .pp-analytics-currency-selected'),
							currencyOptions[currencyOptionsIndex].querySelector('input').value,
							currencyOptions[currencyOptionsIndex].querySelector('input').checked);
					});
				}

				const pageOptions = document.getElementById('pp-analytics-modal').querySelectorAll('.pp-analytics-currency-page a');
				for (let pageOptionsIndex = 0; pageOptionsIndex < pageOptions.length; pageOptionsIndex++) {
					pageOptions[pageOptionsIndex].addEventListener('click', function () {
						const activePage = this.parentElement.querySelector('a.active');
						document.getElementById('pp-analytics-currency-options-container' + activePage.getAttribute('page')).classList.remove('active');
						document.getElementById('pp-analytics-currency-options-container' + this.getAttribute('page')).classList.add('active');

						activePage.classList.remove('active');
						this.classList.add('active');
					});
				}

				document.getElementById('pp-analytics-modal').querySelector('.pp-analytics-currency-search').addEventListener('keyup', function () {
					const {value} = this;
					utils.searchCurrencies(document.getElementById('pp-analytics-modal').querySelectorAll('.pp-analytics-currency-option input'), value);
				});

				document.getElementById('pp-analytics-modal').querySelector('#pp-analytics-currency-update').addEventListener('click', function () {
					// Confirm at least one currency is selected.
					if (!Object.values(utils.currencies).reduce((a, b) => a + b, 0)) {
						const noCurrencySelectedEl = this.parentNode.querySelector('#pp-analytics-currency-update-no-currency');
						if (noCurrencySelectedEl) {
							noCurrencySelectedEl.classList.add('grab-attention');
							setTimeout(() => {
								noCurrencySelectedEl.classList.remove('grab-attention');
							}, 400);
						}

						return;
					}

					utils.updatedCurrencies = JSON.parse(JSON.stringify(utils.currencies));
					document.getElementById('pp-analytics-modal').querySelector('#pp-analytics-modal-close').click();

					let currencies = utils.resetCurrencies();

					currencies = utils.updatedCurrencies['*'] ? '*' : currencies.join('.');
					const params = new URLSearchParams(window.location.search);
					const locationHref = window.location.href.split('?')[0];
					params.set('currency', currencies);

					window.history.pushState({title: 'Analytics Dashboard'}, '', locationHref + '?' + params.toString());

					utils.currencyElements.forEach(currency => utils.updateCurrencySelectionList(currency));
					utils.updateCharts();
				});

				document.getElementById('pp-analytics-modal').querySelector('#pp-analytics-currency-clear').addEventListener('click', () => {
					const activeCurrencies = document.getElementById('pp-analytics-modal').querySelectorAll('.pp-analytics-currency-option');
					utils.currencies['*'] = 0;
					for (let activeCurrenciesIndex = 0; activeCurrenciesIndex < activeCurrencies.length; activeCurrenciesIndex++) {
						utils.currencies[activeCurrencies[activeCurrenciesIndex].querySelector('input').value] = 0;
						activeCurrencies[activeCurrenciesIndex].querySelector('input').checked = false;
					}

					utils.updateCurrencySelectionList(document.getElementById('pp-analytics-modal').querySelector('.pp-analytics-currency-selected'));
				});

				document.getElementById('pp-analytics-modal').querySelector('#pp-analytics-currency-select-all').addEventListener('click', () => {
					const activeCurrencies = document.getElementById('pp-analytics-modal').querySelectorAll('.pp-analytics-currency-option');
					utils.currencies['*'] = 1;

					for (let activeCurrenciesIndex = 0; activeCurrenciesIndex < activeCurrencies.length; activeCurrenciesIndex++) {
						activeCurrencies[activeCurrenciesIndex].querySelector('input').checked = true;
					}

					utils.updateCurrencySelectionList(document.getElementById('pp-analytics-modal').querySelector('.pp-analytics-currency-selected'));
				});
			});
		}
	}

	/**
	 * Builds the header options for each analytics page.
	 */
	addOptionMenu(analyticsElement, type, options) {
		const utils = this;
		const urlParams = new URLSearchParams(window.location.search);

		const optionMenus = {
			// eslint-disable-next-line camelcase
			time_span: this.addOptionMenuTimeSpan,
			interval: this.addOptionMenuInterval,
			// eslint-disable-next-line camelcase
			interval_type: this.addOptionMenuIntervalType,
			currency: this.addOptionMenuCurrency,
		};
		optionMenus[type](utils, analyticsElement, urlParams, options);

		let menuTags = analyticsElement.querySelectorAll('.pp-analytics-menu > *, .pp-analytics-select > a');
		if (!menuTags.length) {
			// Different select menu, use pp-analytics-select change
			menuTags = analyticsElement.querySelectorAll('.pp-analytics-menu > *, .pp-analytics-select');

			for (let menuTagsIndex = 0; menuTagsIndex < menuTags.length; menuTagsIndex++) {
				menuTags[menuTagsIndex].addEventListener('change', function () {
					window.onbeforeunload = null;

					const searchTerm = this.value;
					const searchKey = this.getAttribute('search');
					if (!searchTerm || !searchKey) {
						return;
					}

					const parentID = this.parentElement.id;
					const parents = document.querySelectorAll('#' + parentID);

					Array.prototype.forEach.call(parents, parent => {
						if (parent.querySelector('select')) {
							parent.querySelector('select').value = searchTerm;
						}
					});

					const locationHref = window.location.href.split('?')[0];
					const params = new URLSearchParams(window.location.search);
					params.set(searchKey, searchTerm);

					window.history.pushState({title: 'Analytics Dashboard'}, '', locationHref + '?' + params.toString());

					utils.updateCharts();
				});
			}

			return;
		}

		for (let menuTagsIndex = 0; menuTagsIndex < menuTags.length; menuTagsIndex++) {
			menuTags[menuTagsIndex].addEventListener('click', function (e) {
				window.onbeforeunload = null;
				e.preventDefault();

				const searchTerm = this.getAttribute('search');
				if (!searchTerm) {
					return;
				}

				const locationHref = window.location.href.split('?')[0];
				const params = new URLSearchParams(window.location.search);
				const searchParam = searchTerm.split('=');
				params.set(searchParam[0], searchParam[1]);

				const parentID = this.parentElement.parentElement.id;
				const parents = document.querySelectorAll('#' + parentID);

				Array.prototype.forEach.call(parents, parent => {
					const activeMenuElements = parent.parentElement.querySelectorAll('*.active');
					Array.prototype.forEach.call(activeMenuElements, node => {
						node.classList.remove('active');
						Array.prototype.forEach.call(node.querySelectorAll('path'), path => {
							path.setAttribute('fill', 'black');
						});
					});
					if (parent.querySelector('select')) {
						parent.querySelector('select').value = searchParam[1];
					}

					const currentSelector = parent.querySelector(`*[search="${searchParam[0]}=${searchParam[1]}"]`);
					currentSelector.classList.add('active');
					Array.prototype.forEach.call(currentSelector.querySelectorAll('path'), path => {
						path.setAttribute('fill', '#ffffff');
					});
				});

				window.history.pushState({title: 'Analytics Dashboard'}, '', locationHref + '?' + params.toString());

				utils.updateCharts();
			});
		}
	}

	/**
	 * Simple levenshtein edit distance function for searching currencies.
	 *
	 * @param {string} a string 1
	 * @param {string} b string 2
	 */
	stringDistance(a, b) {
		if (!a.length) {
			return b.length;
		}

		if (!b.length) {
			return a.length;
		}

		if (a[0] === b[0]) {
			return this.stringDistance(a.substring(1), b.substring(1));
		}

		return 1 + Math.min(
			this.stringDistance(a.substring(1), b),
			this.stringDistance(a, b.substring(1)),
			this.stringDistance(a.substring(1), b.substring(1)),
		);
	}

	/**
	 * Searches and sorts all currencies based on needle
	 *
	 * @param {*} haystack - all currencies to search (DOM array)
	 * @param {*} needle - specific currency (USD, CHF, or portion) to search for
	 */
	searchCurrencies(haystack, needle) {
		const currencies = {};

		// Loop through haystack to pull currency -> string distance pairs
		for (let haystackIndex = 0; haystackIndex < haystack.length; haystackIndex++) {
			currencies[haystack[haystackIndex].value] = this.stringDistance(needle.toLowerCase(), haystack[haystackIndex].value.toLowerCase());
		}

		// Sort based on value
		const currencySort = Object.entries(currencies).sort(([, a], [, b]) => a - b);

		// Loop through each currency container and add entries until we run out
		const analyticsModal = document.getElementById('pp-analytics-modal');
		const currencyContainers = analyticsModal.querySelectorAll('.pp-analytics-currency-options');
		for (let currencyContainersIndex = 0; currencyContainersIndex < currencyContainers.length; currencyContainersIndex++) {
			const currencyContainer = currencyContainers[currencyContainersIndex];
			for (let currencyOption = 0; currencyOption < 12; currencyOption++) {
				if (!currencySort[0]) {
					break;
				}

				const currencyKey = currencySort[0][0];

				currencyContainer.append(analyticsModal.querySelector(`input[value='${currencyKey}']`).parentElement);
				currencySort.shift();
			}
		}
	}

	/**
	 * Builds a basic pie chart given the below inputs. Will handle all error and null checks.
	 *
	 * @param data     - should come directly from wp_json_encode() and selecting the .graph key
	 *  which comes from the query_analytics() function
	 * @param chartID - the HTML id of the given chart to attempt data insertion
	 */
	pieChart(self, data, chartID) {
		if (!data) {
			data = {
				graph: {
					labels: [],
					datasets: [{data: [], backgroundColor: []}],
				},
			};
		}

		if (data.error) {
			return self.updateModal(data.error.title, data.error.message);
		}

		// Check for previous analytics no data element, remove if found.
		if (document.getElementById(chartID).parentElement.querySelector('.pp-analytics-no-data')) {
			document.getElementById(chartID).parentElement.querySelector('.pp-analytics-no-data').remove();
		}

		if (!data.graph.datasets.length || !data.graph.labels.length) {
			data.graph.labels[0] = 'No data';
			data.graph.datasets[0].data[0] = 0;
			data.graph.datasets[0].backgroundColor[0] = '#bdbdbd';

			self.pieChartNoDataToDisplay(chartID);
		}

		data.graph.labels.forEach((label, index) => {
			if (self.chartColors[label]) {
				data.graph.datasets[0].backgroundColor[index] = self.chartColors[label];
			} else {
				self.chartColors[label] = data.graph.datasets[0].backgroundColor[index];
			}
		});

		// 3. put them in a result array and then use that data set and form the pie chart.
		const pieChartContext = document.getElementById(chartID);
		const chartConfig = {
			type: 'pie',
			data: {
				labels: data.graph.labels,
				datasets: data.graph.datasets,
			},
			options: {
				plugins: {
					legend: {
						position: 'bottom',
						align: 'start',
						labels: {
							boxWidth: 15,
						},
					},
				},
				reponsive: true,
			},
		};
		return {
			// eslint-disable-next-line no-undef
			chart: new Chart(pieChartContext, chartConfig),
			config: chartConfig,
		};
	}

	/**
	 * Builds a basic bar graph based on the information:
	 *
	 * @param data      - should come directly from wp_json_encode() which comes from the query_analytics() function
	 * @param chartID  - the HTML id of the given chart to attempt data insertion
	 */
	intervalChart(self, data, chartID) {
		if (!data) {
			data = {};
		}

		const urlParams = new URLSearchParams(window.location.search);
		let intervalTypeParam = urlParams.get('interval_type');
		intervalTypeParam = intervalTypeParam ? intervalTypeParam : 'stacked';
		const options = {type: intervalTypeParam === 'line' ? 'line' : 'bar', isStacked: intervalTypeParam === 'stacked'};

		if (data.error) {
			return self.updateModal(data.error.title, data.error.message);
		}

		// Check for previous analytics no data element, remove if found.
		if (document.getElementById(chartID).parentElement.querySelector('.pp-analytics-no-data')) {
			document.getElementById(chartID).parentElement.querySelector('.pp-analytics-no-data').remove();
		}

		if (!data.datasets || !data.datasets.length) {
			self.barChartNoDataToDisplay(chartID);

			const dataPoints = [];
			const intervalJump = 100 / data.labels.length;
			for (let d = 0; d < data.labels.length; d++) {
				dataPoints.push(intervalJump * d);
			}

			data.datasets = [
				{
					label: 'No data',
					data: dataPoints,
					tension: 0.1,
					borderColor: 'rgba(255, 255, 255, 0)',
					backgroundColor: 'rgba(255, 255, 255, 0)',
				},
			];
		}

		data.datasets.forEach(dataset => {
			const {label} = dataset;
			if (self.chartColors[label]) {
				dataset.backgroundColor = self.chartColors[label];
			} else {
				self.chartColors[label] = dataset.backgroundColor;
			}
		});

		const orderLineChartContext = document.getElementById(chartID);
		const chartConfig = {
			type: options.type,
			data: {
				labels: data.labels,
				datasets: data.datasets,
			},
			options: {
				responsive: true,
				aspectRatio: 2.5,
				plugins: {
					legend: {
						position: 'bottom',
						align: 'end',
						labels: {
							boxWidth: 15,
						},
					},
				},
				scales: {
					x: {
						stacked: options.isStacked,
					},
					y: {
						stacked: options.isStacked,
					},
				},
			},
		};
		return {
			// eslint-disable-next-line no-undef
			chart: new Chart(orderLineChartContext, chartConfig),
			config: chartConfig,
		};
	}

	/**
	 * Displays a notif over the given pie chart that tells the user there is no data.
	 *
	 * @param chart - the given element to display over. This expects the following pattern inthe HTML:
	 * <div>
	 *     <canvas id='chart-id-to-attach'></canvas>
	 * </div>
	 *
	 * Not the notification will be added to the parent div of the canvas element
	 */
	pieChartNoDataToDisplay(chartID) {
		const canvasElement = document.getElementById(chartID);

		const noDataNotice = document.createElement('div');
		noDataNotice.classList.add('pp-analytics-no-data');
		noDataNotice.innerHTML = `
			<svg width='60' height='60' viewBox='0 0 12 12' fill='none' color='#bdbdbd' xmlns='http://www.w3.org/2000/svg'>
				<path color='#bdbdbd' fill='#bdbdbd' d='M10.6667 0H1.33333C0.6 0 0 0.6 0 1.33333V10.6667C0 11.4 0.6 12 1.33333 12H10.6667C11.4 12 12 11.4 12 10.6667V1.33333C12 0.6 11.4 0 10.6667 0ZM10.6667 10.6667H1.33333V1.33333H10.6667V10.6667ZM2.66667 4.66667H4V9.33333H2.66667V4.66667ZM5.33333 2.66667H6.66667V9.33333H5.33333V2.66667ZM8 6.66667H9.33333V9.33333H8V6.66667Z' fill='#616161'/>
			</svg>
			<h2 style='margin-bottom:0;color:#bdbdbd'>No data to display</h2>`;
		noDataNotice.setAttribute('style', this.noDataStyle + `
			margin-bottom: -200px;
			width: 100%;
			height: 200px;
		`);

		canvasElement.parentElement.insertBefore(noDataNotice, canvasElement);
	}

	/**
	 * Displays a notif over the given bar chart that tells the user there is no data.
	 *
	 * @param chart - the given element to display over. This expects the following pattern inthe HTML:
	 * <div>
	 *     <canvas id='chart-id-to-attach'></canvas>
	 * </div>
	 *
	 * Not the notification will be added to the parent div of the canvas element
	 */
	barChartNoDataToDisplay(chartID) {
		const canvasElement = document.getElementById(chartID);

		const noDataNotice = document.createElement('div');
		noDataNotice.classList.add('pp-analytics-no-data');
		noDataNotice.innerHTML = `
			<svg width='60' height='60' viewBox='0 0 12 12' fill='none' color='#bdbdbd' xmlns='http://www.w3.org/2000/svg'>
				<path color='#bdbdbd' fill='#bdbdbd' d='M10.6667 0H1.33333C0.6 0 0 0.6 0 1.33333V10.6667C0 11.4 0.6 12 1.33333 12H10.6667C11.4 12 12 11.4 12 10.6667V1.33333C12 0.6 11.4 0 10.6667 0ZM10.6667 10.6667H1.33333V1.33333H10.6667V10.6667ZM2.66667 4.66667H4V9.33333H2.66667V4.66667ZM5.33333 2.66667H6.66667V9.33333H5.33333V2.66667ZM8 6.66667H9.33333V9.33333H8V6.66667Z' fill='#616161'/>
			</svg>
			<h2 style='margin-bottom:0;color:#bdbdbd'>No data to display</h2>`;

		noDataNotice.setAttribute('style', this.noDataStyle + `
			margin-bottom: -206px;
			margin-top: 87px;
			margin-right: auto;
			margin-left: auto;
			width: 180px;
			background: rgb(255,255,255);
			background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.8039504716981132) 40%, rgba(255,255,255,0.8008058176100629) 60%, rgba(255,255,255,0) 100%);
		`);

		canvasElement.parentElement.insertBefore(noDataNotice, canvasElement);
	}
}
