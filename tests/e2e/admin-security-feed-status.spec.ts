/**
 * E2E tests: what the Security tab says about the vulnerability data (#316)
 *
 * The GET answer of the findings is the tests site's own, with `feed` and
 * `vulnerabilities` replaced by page.route(): no sync runs and nothing is
 * downloaded. The tab sends no other request in these tests.
 */
import { test, expect, type Page, type Route } from '@playwright/test';
import { login } from './utils/helpers';

const BASE_URL = process.env.WP_BASE_URL ?? 'http://localhost:8891';
const SECURITY_TAB = `${ BASE_URL }/wp-admin/admin.php?page=jp4wc-settings&tab=security`;
const FINDINGS = '/jp4wc/v1/security/findings';
const HOST = 'wc.artws.info';
const NOW = Math.floor( Date.now() / 1000 );

const NOT_YET =
	'The vulnerability data has not been downloaded yet, so known vulnerabilities are not checked. It is downloaded once a day.';
const UNAVAILABLE = `The vulnerability data could not be downloaded for several days, so the installed software is not checked for known vulnerabilities. Make sure this server can connect to ${ HOST }.`;
const STALE =
	'The vulnerability data has not been updated for a while, so vulnerabilities disclosed since then are not checked.';
const FAILED = /^The last download of the vulnerability data \(.+\) failed\./;
const NOT_CHECKED =
	'Known vulnerabilities are not checked until a download succeeds. It is tried again once a day.';
const UPDATED_ON =
	/^Until a download succeeds, the vulnerability data updated on .+ is used\.$/;
const REASONS: Record< string, string > = {
	connect: `This server could not connect to ${ HOST }.`,
	http: `${ HOST } returned an error.`,
	invalid: 'The downloaded data was damaged or not in the expected format.',
	zlib: 'The PHP zlib extension, which is needed to decompress the data, is not available on this server. Ask your hosting provider to enable it.',
	write: 'The data could not be saved in the uploads directory. Make sure the server can write to it.',
};

type Feed = {
	host: string;
	has_data: boolean;
	synced_at: number;
	last_attempt: number;
	failing: boolean;
	unavailable: boolean;
	failure_reason: string;
};

/**
 * Returns a feed state; the defaults are those of a site that has data.
 *
 * @param change Values that differ.
 * @return The feed state.
 */
const feed = ( change: Partial< Feed > ): Feed => ( {
	host: HOST,
	has_data: true,
	synced_at: NOW - 2 * 86400,
	last_attempt: NOW - 3600,
	failing: false,
	unavailable: false,
	failure_reason: '',
	...change,
} );

/**
 * Returns vulnerability data with no affected software.
 *
 * @param stale Whether it is out of date.
 * @return The vulnerabilities of the findings.
 */
const vulnerabilities = ( stale: boolean ) => ( {
	version: '2026-11-01.1',
	generated_at: new Date( ( NOW - 2 * 86400 ) * 1000 ).toISOString(),
	stale,
	copyrights: [],
	attribution: '',
	count: 0,
	max_severity: '',
	items: [],
} );

/**
 * Returns the notices of the tab whose text matches.
 *
 * The text is also in the a11y-speak region, which getByText() alone would find.
 *
 * @param page Page.
 * @param text Text of the notice.
 * @return The notices.
 */
const notice = ( page: Page, text: string | RegExp ) =>
	page.locator( '.components-notice__content' ).getByText( text );

/**
 * Opens the Security tab with the feed state and vulnerability data given.
 *
 * @param page  Page.
 * @param state Feed state.
 * @param data  Vulnerability data, or null.
 * @return The page errors.
 */
const openWith = async (
	page: Page,
	state: Feed,
	data: ReturnType< typeof vulnerabilities > | null
): Promise< string[] > => {
	const errors: string[] = [];
	page.on( 'pageerror', ( err ) => errors.push( err.message ) );
	await page.route(
		( url ) =>
			( url.searchParams.get( 'rest_route' ) ??
				decodeURIComponent( url.pathname ).replace(
					/^.*\/wp-json/,
					''
				) ) === FINDINGS,
		async ( route: Route ) => {
			expect( route.request().method() ).toBe( 'GET' );
			const real = ( await ( await route.fetch() ).json() ) as Record<
				string,
				unknown
			>;
			await route.fulfill( {
				json: {
					...real,
					enabled: true,
					feed: state,
					vulnerabilities: data,
				},
			} );
		}
	);
	await login( page, BASE_URL );
	await page.goto( SECURITY_TAB );
	await expect(
		page.getByRole( 'button', { name: 'Re-check now' } )
	).toBeVisible();
	return errors;
};

test.describe( 'vulnerability data status (#316)', () => {
	test( 'not downloaded yet, nothing failed: the notice is as before', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( { has_data: false, synced_at: 0, last_attempt: 0 } ),
			null
		);

		await expect( notice( page, NOT_YET ) ).toBeVisible();
		await expect( notice( page, FAILED ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	for ( const [ reason, text ] of Object.entries( REASONS ) ) {
		test( `failed without data (${ reason }): the reason replaces "not downloaded yet"`, async ( {
			page,
		} ) => {
			const errors = await openWith(
				page,
				feed( {
					has_data: false,
					synced_at: 0,
					failing: true,
					failure_reason: reason,
				} ),
				null
			);

			await expect(
				notice(
					page,
					/^The last download of the vulnerability data \(.+\) failed\. Reason: /
				)
			).toContainText( text );
			await expect( notice( page, NOT_CHECKED ) ).toBeVisible();
			await expect( notice( page, NOT_YET ) ).toHaveCount( 0 );
			await expect( notice( page, UPDATED_ON ) ).toHaveCount( 0 );
			expect( errors ).toEqual( [] );
		} );
	}

	test( 'failed without a reason (recorded by an earlier version)', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( { has_data: false, synced_at: 0, failing: true } ),
			null
		);

		await expect(
			notice(
				page,
				/^The last download of the vulnerability data \(.+\) failed\.$/
			)
		).toBeVisible();
		await expect( notice( page, 'Reason:' ) ).toHaveCount( 0 );
		await expect( notice( page, NOT_YET ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'failed with data: says when the data in use was updated', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( { failing: true, failure_reason: 'http' } ),
			vulnerabilities( false )
		);

		await expect( notice( page, FAILED ) ).toContainText( REASONS.http );
		await expect( notice( page, UPDATED_ON ) ).toBeVisible();
		await expect( notice( page, NOT_CHECKED ) ).toHaveCount( 0 );
		await expect( notice( page, STALE ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'failed with stored files that matched nothing: does not claim the data is used', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( { failing: true, failure_reason: 'connect' } ),
			null
		);

		await expect( notice( page, FAILED ) ).toContainText( REASONS.connect );
		await expect( notice( page, NOT_CHECKED ) ).toBeVisible();
		await expect( notice( page, UPDATED_ON ) ).toHaveCount( 0 );
		await expect( notice( page, NOT_YET ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'unavailable: the notice is as before, and the reason follows it', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( {
				has_data: false,
				synced_at: 0,
				failing: true,
				unavailable: true,
				failure_reason: 'zlib',
			} ),
			null
		);

		await expect( notice( page, UNAVAILABLE ) ).toBeVisible();
		await expect( notice( page, FAILED ) ).toContainText( REASONS.zlib );
		// The notice above already says that nothing is checked.
		await expect( notice( page, NOT_CHECKED ) ).toHaveCount( 0 );
		await expect( notice( page, NOT_YET ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'stale, nothing failed: the notice is as before', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( {} ),
			vulnerabilities( true )
		);

		await expect( notice( page, STALE ) ).toBeVisible();
		await expect( notice( page, FAILED ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );

	test( 'stale and failing: the reason follows the stale notice', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( { failing: true, failure_reason: 'connect' } ),
			vulnerabilities( true )
		);

		await expect( notice( page, STALE ) ).toBeVisible();
		await expect( notice( page, FAILED ) ).toContainText( REASONS.connect );
		await expect( notice( page, UPDATED_ON ) ).toBeVisible();
		expect( errors ).toEqual( [] );
	} );

	test( 'up to date, nothing failed: no notice about the data', async ( {
		page,
	} ) => {
		const errors = await openWith(
			page,
			feed( {} ),
			vulnerabilities( false )
		);

		for ( const text of [ NOT_YET, UNAVAILABLE, STALE ] ) {
			await expect( notice( page, text ) ).toHaveCount( 0 );
		}
		await expect( notice( page, FAILED ) ).toHaveCount( 0 );
		expect( errors ).toEqual( [] );
	} );
} );
