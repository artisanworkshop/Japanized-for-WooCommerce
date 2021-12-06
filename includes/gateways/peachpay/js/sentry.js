// eslint-disable-next-line dot-notation
if (window['Sentry']) {// Avoid exceptions from ad blockers and such
	// eslint-disable-next-line prefer-arrow-callback
	Sentry.onLoad(function () {
		const devSite = location.hostname === 'localhost' || location.hostname === 'woo.peachpay.app';
		Sentry.init({
			environment: devSite ? 'development' : 'production',
			debug: true,
			beforeSend: function (event) {
				try {
					const exceptions = event.exception.values;

					// Reverse search because deletions may occur
					for (let i = exceptions.length - 1; i >= 0; i--) {
						if (exceptions[i].stacktrace
							&& exceptions[i].stacktrace.frames
							&& Array.isArray(exceptions[i].stacktrace.frames)
							&& exceptions[i].stacktrace.frames.length > 0) {
							const frame = exceptions[i].stacktrace.frames[0];

							if (frame.filename && frame.filename.indexOf('peachpay-for-woocommerce') >= 0) {
								continue;
							}
						}

						exceptions.splice(i, 1);
					}

					if (exceptions.length === 0) {
						return null;
					}

					return event;
					// eslint-disable-next-line unicorn/prefer-optional-catch-binding
				} catch (_) {
					return event;
				}
			},
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
function captureSentryException(error, extra, fingerprint) {
	try {
		// eslint-disable-next-line dot-notation
		if (window['Sentry']) {
			return;
		}

		Sentry.withScope(scope => {
			if (extra) {
				// Attempt extras
				try {
					Object.entries(extra).map(([key, value]) => scope.setExtra(key, value));
				} catch {}
			}

			if (fingerprint) {
				// Attempt Fingerprint
				try {
					scope.setFingerprint(fingerprint);
				} catch {}
			}

			// Capture exception with any above set extras and/or fingerprint
			Sentry.captureException(error);
		});
	} catch {
		// Sentry is not present. Don't make things worse.
	}
}
