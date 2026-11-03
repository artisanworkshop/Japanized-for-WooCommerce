/**
 * E2E tests: the free security check on the Security tab (SecurityScan.js)
 *
 * The REST routes of the Security tab are answered by page.route(): the GET
 * answers of the tests site are changed where a test needs it, and the POST
 * and DELETE of the free check never reach the site, so nothing is sent to
 * wc.artws.info. The answers are held where a test needs requests to overlap.
 */
import { test, expect, type Page, type Route } from '@playwright/test';
import { login } from './utils/helpers';

const BASE_URL = process.env.WP_BASE_URL ?? 'http://localhost:8891';
const SECURITY_TAB = `${ BASE_URL }/wp-admin/admin.php?page=jp4wc-settings&tab=security`;
const FINDINGS = '/jp4wc/v1/security/findings';
const SCAN = '/jp4wc/v1/security/scan';
const BANDS_LABEL =
	'Also send the revenue and order bands of the last 12 months (optional)';
const DONE = 'The check has finished. The report is below.';
const RUNNING = 'Running the check on wc.artws.info…';
const CONSENTED_AT = 1793000000;

type ScanState = Record< string, unknown > & {
	payload: Record< string, unknown >;
};
type Sent = { consent?: boolean; consent_version?: string; bands?: boolean };

/**
 * Returns the REST route of a request, with pretty or plain permalinks.
 *
 * @param url Request URL.
 * @return The route, such as /jp4wc/v1/security/scan.
 */
const restRoute = ( url: URL ): string =>
	url.searchParams.get( 'rest_route' ) ??
	decodeURIComponent( url.pathname ).replace( /^.*\/wp-json/, '' );

const isRoute = ( route: string ) => ( url: URL ) => restRoute( url ) === route;

/**
 * Returns the method a request stands for: apiFetch sends DELETE as a POST
 * with X-HTTP-Method-Override.
 *
 * @param route Intercepted request.
 * @return The method.
 */
const methodOf = ( route: Route ): string =>
	(
		route.request().headers()[ 'x-http-method-override' ] ??
		route.request().method()
	).toUpperCase();

const wantsBands = ( route: Route ): boolean =>
	'1' === new URL( route.request().url() ).searchParams.get( 'bands' );

/**
 * Returns a promise with its resolve function.
 *
 * @return The promise and its resolve function.
 */
const deferred = () => {
	let resolve: () => void = () => {};
	const promise = new Promise< void >( ( done ) => {
		resolve = done;
	} );
	return { promise, resolve };
};

/**
 * Builds the state of the free check from the tests site's answer: the site
 * agreed to the current text and can send the bands.
 *
 * @param base         GET answer of the tests site.
 * @param consentBands Whether the stored consent has the bands.
 * @param withBands    Whether the preview has the bands.
 * @return The state.
 */
const consentedState = (
	base: ScanState,
	consentBands: boolean,
	withBands: boolean
): ScanState => {
	const payload = { ...base.payload };
	delete payload.revenue_band;
	delete payload.order_band;
	return {
		...base,
		consent: {
			current: true,
			consented_at: CONSENTED_AT,
			bands: consentBands,
		},
		registered: true,
		other_site: false,
		report: null,
		scanned_at: 0,
		ready: true,
		enabled: true,
		bands_available: true,
		bands_error: false,
		payload: withBands
			? { ...payload, revenue_band: 'B2', order_band: 'O2' }
			: payload,
	};
};

/**
 * Reads the tests site's GET answer of the free check, which sends nothing.
 *
 * Never called for a POST or a DELETE: route.fetch() sends the request on,
 * and the POST would run the check on wc.artws.info.
 *
 * @param route Intercepted GET request.
 * @return The answer.
 */
const realState = async ( route: Route ): Promise< ScanState > => {
	expect( route.request().method() ).toBe( 'GET' );
	return ( await ( await route.fetch() ).json() ) as ScanState;
};

/**
 * Returns a notice of the tab by its text. The text is also in the
 * a11y-speak region, which getByText() alone would find as well.
 *
 * @param page Page.
 * @param text Text of the notice.
 * @return The notice.
 */
const notice = ( page: Page, text: string ) =>
	page.locator( '.components-notice__content' ).getByText( text );

/**
 * Opens the Security tab and records the page errors.
 *
 * @param page Page.
 * @return The page errors.
 */
const openSecurityTab = async ( page: Page ): Promise< string[] > => {
	const errors: string[] = [];
	page.on( 'pageerror', ( err ) => errors.push( err.message ) );
	await login( page, BASE_URL );
	await page.goto( SECURITY_TAB );
	return errors;
};

test( 'the bands box follows the bands of the check that was sent (#317)', async ( {
	page,
} ) => {
	const sent: Sent[] = [];
	const unexpected: string[] = [];
	const postArrived = deferred();
	const releasePost = deferred();
	const restored = deferred();
	let findingsGets = 0;
	let bandsGets = 0;
	let base: ScanState | null = null;

	await page.route( isRoute( FINDINGS ), async ( route ) => {
		const real = ( await ( await route.fetch() ).json() ) as Record<
			string,
			unknown
		> & { checked_at: number };
		const requestedAt = real.checked_at + 30;
		findingsGets++;
		if ( 1 === findingsGets ) {
			// A re-check is pending, so the tab asks again every 5 seconds.
			await route.fulfill( {
				json: {
					...real,
					recheck: {
						requested_at: requestedAt,
						done_at: 0,
						pending: true,
						retry_after: 0,
					},
				},
			} );
			return;
		}
		// The re-check ends while the free check runs: checkedAt changes.
		await postArrived.promise;
		await route.fulfill( {
			json: {
				...real,
				checked_at: real.checked_at + 60,
				recheck: {
					requested_at: requestedAt,
					done_at: requestedAt,
					pending: false,
					retry_after: 0,
				},
			},
		} );
	} );

	await page.route( isRoute( SCAN ), async ( route ) => {
		const method = methodOf( route );
		if ( 'GET' === method ) {
			base = await realState( route );
			if ( ! wantsBands( route ) ) {
				// Until the check below is stored, the consent of the last
				// check (with the bands) is what the site holds.
				await route.fulfill( {
					json: consentedState( base, true, false ),
				} );
				return;
			}
			bandsGets++;
			if ( 1 === bandsGets ) {
				// The bands of the last check cannot be restored once.
				await route.fulfill( {
					status: 500,
					json: {
						code: 'jp4wc_test_bands',
						message: 'The bands could not be counted.',
						data: { status: 500 },
					},
				} );
				return;
			}
			await route.fulfill( { json: consentedState( base, true, true ) } );
			restored.resolve();
			return;
		}
		if ( 'POST' !== method || null === base ) {
			unexpected.push( method );
			await route.fulfill( { status: 500, json: {} } );
			return;
		}
		sent.push( route.request().postDataJSON() as Sent );
		if ( 1 === sent.length ) {
			postArrived.resolve();
			await releasePost.promise;
		}
		// The answer of a check without the bands, which stores a consent without them.
		await route.fulfill( {
			json: {
				...consentedState( base, false, false ),
				reregistered: false,
			},
		} );
	} );

	const errors = await openSecurityTab( page );
	const bandsBox = page.getByRole( 'checkbox', { name: BANDS_LABEL } );
	const runAgain = page.getByRole( 'button', {
		name: 'Run the check again',
	} );

	await expect(
		notice( page, 'The bands could not be counted.' )
	).toBeVisible();
	await expect( bandsBox ).not.toBeChecked();
	await expect( runAgain ).toBeEnabled();

	await runAgain.click();
	await postArrived.promise;
	// The re-check ended, and the bands of the stored consent were loaded
	// before the check above stored its own.
	await restored.promise;
	await expect( bandsBox ).toBeChecked();

	releasePost.resolve();
	await expect( notice( page, DONE ) ).toBeVisible();
	// Soft, so that a failure also shows what the next check sends.
	await expect.soft( bandsBox ).not.toBeChecked();

	await runAgain.click();
	await expect.poll( () => sent.length ).toBe( 2 );
	await expect( notice( page, RUNNING ) ).toHaveCount( 0 );
	expect( sent.map( ( body ) => body.bands ) ).toEqual( [ false, false ] );
	expect( unexpected ).toEqual( [] );
	expect( errors ).toEqual( [] );
} );

test( 'runs with and without the bands, and deletes the check data', async ( {
	page,
} ) => {
	const sent: Sent[] = [];
	const unexpected: string[] = [];
	let consentBands = false;
	let deleted = 0;
	let base: ScanState | null = null;

	await page.route( isRoute( SCAN ), async ( route ) => {
		const method = methodOf( route );
		if ( 'GET' === method ) {
			base = await realState( route );
			await route.fulfill( {
				json: consentedState( base, consentBands, wantsBands( route ) ),
			} );
			return;
		}
		if ( null === base ) {
			unexpected.push( method );
			await route.fulfill( { status: 500, json: {} } );
			return;
		}
		if ( 'DELETE' === method ) {
			deleted++;
			await route.fulfill( {
				json: {
					...base,
					consent: null,
					registered: false,
					other_site: false,
					report: null,
					scanned_at: 0,
					ready: true,
					enabled: true,
					bands_available: true,
				},
			} );
			return;
		}
		if ( 'POST' !== method ) {
			unexpected.push( method );
			await route.fulfill( { status: 500, json: {} } );
			return;
		}
		const body = route.request().postDataJSON() as Sent;
		sent.push( body );
		consentBands = true === body.bands;
		await route.fulfill( {
			json: {
				...consentedState( base, consentBands, consentBands ),
				reregistered: false,
			},
		} );
	} );

	const errors = await openSecurityTab( page );
	const bandsBox = page.getByRole( 'checkbox', { name: BANDS_LABEL } );
	const runAgain = page.getByRole( 'button', {
		name: 'Run the check again',
	} );
	const showSent = page.getByRole( 'button', {
		name: 'Show the information sent',
	} );
	const bandsPreview = page.getByText(
		'B2 (Up to 10 million yen), O2 (Up to 1,000 orders)'
	);

	await expect( runAgain ).toBeEnabled();
	await expect( bandsBox ).not.toBeChecked();
	await showSent.click();

	// With the bands: the preview shows them, and they stay chosen.
	await bandsBox.check();
	await expect( bandsPreview ).toBeVisible();
	await expect( runAgain ).toBeEnabled();
	await runAgain.click();
	await expect.poll( () => sent.length ).toBe( 1 );
	await expect( notice( page, DONE ) ).toBeVisible();
	await expect( bandsBox ).toBeChecked();
	// The check closes the preview: it still shows the bands.
	await showSent.click();
	await expect( bandsPreview ).toBeVisible();

	// Without the bands.
	await bandsBox.uncheck();
	await expect( bandsPreview ).toHaveCount( 0 );
	await expect( runAgain ).toBeEnabled();
	await runAgain.click();
	await expect.poll( () => sent.length ).toBe( 2 );
	await expect( notice( page, RUNNING ) ).toHaveCount( 0 );
	await expect( bandsBox ).not.toBeChecked();
	expect( sent.map( ( body ) => body.bands ) ).toEqual( [ true, false ] );

	// Deleting goes back to the button that opens the consent.
	await page.getByRole( 'button', { name: 'Delete check data' } ).click();
	await page.getByRole( 'button', { name: 'Delete', exact: true } ).click();
	await expect(
		notice(
			page,
			'The check data was deleted from wc.artws.info and from this site.'
		)
	).toBeVisible();
	await expect(
		page.getByRole( 'button', { name: 'Get the free check' } )
	).toBeVisible();
	expect( deleted ).toBe( 1 );
	expect( unexpected ).toEqual( [] );
	expect( errors ).toEqual( [] );
} );
