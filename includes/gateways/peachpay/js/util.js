function basePeachPayAPIURL(merchantHostname, test_mode) {
	if (test_mode) {
		switch (merchantHostname) {
			case 'store.local':
			case 'woo.store.local':
				return 'https://dev.peachpay.local';// Local https
			default:
				return 'https://dev.peachpay.app';
		}
	}

	switch (merchantHostname) {
		case 'localhost':
		case '127.0.0.1':
		case 'woo.peachpay.app':
		case 'theme1.peachpay.app':
		case 'theme2.peachpay.app':
		case 'theme3.peachpay.app':
		case 'theme4.peachpay.app':
		case 'theme5.peachpay.app':
		case 'qa.peachpay.app':
		case 'demo.peachpay.app':
			return 'https://dev.peachpay.app';
		case 'store.local':
		case 'woo.store.local':
			return 'https://prod.peachpay.local';// Local https
		default:
			return 'https://prod.peachpay.app';
	}
}

function baseURL(merchantHostname, test_mode) {
	if (test_mode) {
		switch (merchantHostname) {
			case 'localhost':
			case '127.0.0.1':
				return 'http://localhost:8080';
			case 'store.local':
			case 'woo.store.local':
				return 'https://dev-connect.peachpay.local'; // Local HTTPS
			default:
				return 'https://dev-connect.peachpaycheckout.com';
		}
	}

	switch (merchantHostname) {
		case 'localhost':
		case '127.0.0.1':
			return 'http://localhost:8080';
		case 'woo.peachpay.app':
		case 'theme1.peachpay.app':
		case 'theme2.peachpay.app':
		case 'theme3.peachpay.app':
		case 'theme4.peachpay.app':
		case 'theme5.peachpay.app':
		case 'qa.peachpay.app':
		case 'demo.peachpay.app':
			return 'https://dev-connect.peachpaycheckout.com';
		case 'store.local':
		case 'woo.store.local':
			return 'https://connect.peachpay.local'; // Local HTTPS
		default:
			return 'https://connect.peachpaycheckout.com';
	}
}

function spinnerURL(test_mode) {
	// Taken from button.js
	const dark = location.hostname === 'www.blazecandles.co' ? '-dark' : '';
	return `${baseURL(location.hostname, test_mode)}/img/spinner${dark}.svg`;
}
