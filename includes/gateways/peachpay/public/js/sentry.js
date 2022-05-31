if (window['Sentry']) { // Avoid exceptions from ad blockers and such
	Sentry.onLoad(function () {
		const isDev = isDevEnvironment(basePeachPayAPIURL(location.hostnam));
		Sentry.init({
			environment: isDev ? 'development' : 'production',
			release: `peachpay-plugin@${peachpay_data.version}`,
			// Ignore errors produced by Chrome, Firefox, and Safari upon
			// navigating away from a page that has a fetch request in progress.
			// See https://forum.sentry.io/t/typeerror-failed-to-fetch-reported-over-and-overe/8447/2
			ignoreErrors: [
				'TypeError: Failed to fetch',
				'TypeError: NetworkError when attempting to fetch resource.',
				'TypeError: cancelled',
				'TypeError: cancelado',
				'TypeError: Abgebrochen',
				'TypeError: annulé',
				'Window navigated away',
				'annullato',
				'Load failed',
			],
			allowUrls: [
				/peachpay-for-woocommerce/i,
			],
		});
	});
}

/**
 * Used to capture a exception with sentry
 *
 * @param { Error } error The error/exception to report
 * @param { Record<string,string> | null | undefined  } extra Details to include with the sentry report
 * @param { any[] | null | undefined } fingerprint Fingerprint to identify a sequence of events?
 */
// deno-lint-ignore no-unused-vars
function captureSentryException(error, extra, fingerprint) {
	try {
		// eslint-disable-next-line dot-notation
		if (window['Sentry']) {
			return;
		}

		Sentry.withScope((scope) => {
			if (extra) {
				// Attempt extras
				try {
					Object.entries(extra).map(([key, value]) => scope.setExtra(key, value));
				} catch {
					// Do no harm.
				}
			}

			if (fingerprint) {
				// Attempt Fingerprint
				try {
					scope.setFingerprint(fingerprint);
				} catch {
					// Do no harm.
				}
			}

			// Capture exception with any above set extras and/or fingerprint
			Sentry.captureException(error);
		});
	} catch {
		// Sentry is not present. Don't make things worse.
	}
}

/**
 * Captures a message of some sort of application event. This can be an
 * error or just a situation the application encountered that may need
 * reported.
 *
 * @param { string } eventMessage
 */
// deno-lint-ignore no-unused-vars
function captureSentryEvent(eventMessage) {
	try {
		// eslint-disable-next-line dot-notation
		if (window['Sentry']) {
			return;
		}

		Sentry.captureMessage(eventMessage);
	} catch {
		//Do no harm
	}
}
